<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

// ກວດສອບສິດເຂົ້າເຖິງ
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ບໍ່ມີສິດເຂົ້າເຖິງ']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonOk($message, $extra = [])
{
    echo json_encode(array_merge(['success' => true, 'message' => $message], $extra));
    exit;
}

function jsonErr($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ດຶງລາຍລະອຽດໃບບິນຂາຍ (Get Sale Invoice Details)
if ($action === 'get') {
    $saleId = clean($conn, $_GET['sale_id'] ?? '');
    
    if ($saleId === '') {
        jsonErr('ລະຫັດໃບບິນຂາຍບໍ່ຖືກຕ້ອງ');
    }
    
    // ດຶງໃບບິນຫຼັກ
    $stmtSale = mysqli_prepare($conn, '
        SELECT s.*, u.fname as staff_fname, u.lname as staff_lname 
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE s.sale_id = ? LIMIT 1
    ');
    mysqli_stmt_bind_param($stmtSale, 'i', $saleId);
    mysqli_stmt_execute($stmtSale);
    $resSale = mysqli_stmt_get_result($stmtSale);
    $sale = mysqli_fetch_assoc($resSale);
    mysqli_stmt_close($stmtSale);
    
    if (!$sale) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນໃບບິນຂາຍ', 404);
    }
    
    // ດຶງລາຍການສິນຄ້າໃນໃບບິນ
    $items = [];
    $stmtItems = mysqli_prepare($conn, '
        SELECT sd.*, p.product_name, p.product_code, p.unit 
        FROM sale_details sd
        LEFT JOIN products p ON sd.product_id = p.product_id 
        WHERE sd.sale_id = ?
    ');
    mysqli_stmt_bind_param($stmtItems, 'i', $saleId);
    mysqli_stmt_execute($stmtItems);
    $resItems = mysqli_stmt_get_result($stmtItems);
    while ($row = mysqli_fetch_assoc($resItems)) {
        $items[] = $row;
    }
    mysqli_stmt_close($stmtItems);
    
    jsonOk('OK', ['sale' => $sale, 'items' => $items]);
}

// 2. ເພີ່ມການຂາຍໃໝ່ / ຊຳລະເງິນ (Create Sale / POS Checkout)
if ($action === 'create') {
    $paymentMethod = clean($conn, $_POST['payment_method'] ?? 'ເງິນສົດ');
    $totalAmount = clean($conn, $_POST['total_amount'] ?? '0');
    $itemsJson = $_POST['items'] ?? '[]';
    $userId = $_SESSION['user_id'];

    $items = json_decode($itemsJson, true);
    if (!is_array($items) || empty($items)) {
        jsonErr('ກະລຸນາເລືອກສິນຄ້າໃສ່ກະຕ່າຢ່າງໜ້ອຍ 1 ລາຍການ');
    }

    $totalAmount = (float)$totalAmount;

    // ເລີ່ມໃຊ້ງານ Database Transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. ສ້າງລະຫັດໃບບິນຂາຍອັດຕະໂນມັດ (SL-YYMMDD-XXXX)
        $prefix = 'SL-' . date('ymd') . '-';
        $queryCode = mysqli_query($conn, "SELECT sale_code FROM sales WHERE sale_code LIKE '$prefix%' ORDER BY sale_id DESC LIMIT 1 FOR UPDATE");
        $seqNum = 1;
        if ($queryCode && mysqli_num_rows($queryCode) > 0) {
            $rowCode = mysqli_fetch_assoc($queryCode);
            $lastCode = $rowCode['sale_code'];
            $parts = explode('-', $lastCode);
            $lastSeq = (int)end($parts);
            $seqNum = $lastSeq + 1;
        }
        $saleCode = $prefix . str_pad($seqNum, 4, '0', STR_PAD_LEFT);

        // 2. Insert ຂໍ້ມູນລົງໃນຕາຕະລາງ sales
        $stmtSale = mysqli_prepare(
            $conn,
            'INSERT INTO sales (sale_code, total_amount, payment_method, user_id) VALUES (?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmtSale, 'sdss', $saleCode, $totalAmount, $paymentMethod, $userId);
        if (!mysqli_stmt_execute($stmtSale)) {
            throw new Exception('ບໍ່ສາມາດບັນທຶກໃບບິນຂາຍໄດ້: ' . mysqli_error($conn));
        }
        $saleId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtSale);

        // 3. ວົນລູບ Insert ລາຍລະອຽດສິນຄ້າ ແລະ ຕັດສະຕັອກສິນຄ້າ
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            $price = (float)($item['sale_price'] ?? 0);
            $subtotal = $qty * $price;

            if ($productId <= 0 || $qty <= 0 || $price < 0) {
                throw new Exception('ຂໍ້ມູນສິນຄ້າໃນກະຕ່າບໍ່ຖືກຕ້ອງ');
            }

            // ກວດສອບຈຳນວນສິນຄ້າໃນຄັງກ່ອນ (Select for update to lock the row)
            $stmtCheckStock = mysqli_prepare($conn, 'SELECT quantity, product_name FROM products WHERE product_id = ? FOR UPDATE');
            mysqli_stmt_bind_param($stmtCheckStock, 'i', $productId);
            mysqli_stmt_execute($stmtCheckStock);
            $resStock = mysqli_stmt_get_result($stmtCheckStock);
            $productRow = mysqli_fetch_assoc($resStock);
            mysqli_stmt_close($stmtCheckStock);

            if (!$productRow) {
                throw new Exception('ບໍ່ພົບສິນຄ້າໃນລະບົບ');
            }

            $currentStock = (int)$productRow['quantity'];
            $productName = $productRow['product_name'];

            if ($currentStock < $qty) {
                throw new Exception("ສິນຄ້າ '$productName' ມີຈຳນວນບໍ່ພໍໃນຄັງ (ຄັງເຫຼືອ: $currentStock)");
            }

            // Insert ຂໍ້ມູນລົງໃນ sale_details
            $stmtDetail = mysqli_prepare(
                $conn,
                'INSERT INTO sale_details (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmtDetail, 'iiidd', $saleId, $productId, $qty, $price, $subtotal);
            if (!mysqli_stmt_execute($stmtDetail)) {
                throw new Exception('ບໍ່ສາມາດບັນທຶກລາຍລະອຽດການຂາຍໄດ້: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmtDetail);

            // ຕັດສະຕັອກ (Decrease Quantity)
            $stmtUpdateProduct = mysqli_prepare(
                $conn,
                'UPDATE products SET quantity = quantity - ? WHERE product_id = ?'
            );
            mysqli_stmt_bind_param($stmtUpdateProduct, 'ii', $qty, $productId);
            if (!mysqli_stmt_execute($stmtUpdateProduct)) {
                throw new Exception('ບໍ່ສາມາດອັບເດດຈຳນວນສິນຄ້າໄດ້: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmtUpdateProduct);
        }

        // Commit ທຸລະກຳເມື່ອເຮັດວຽກທຸກຂັ້ນຕອນສຳເລັດ
        mysqli_commit($conn);
        
        logActivity($pdo, "ຂາຍສິນຄ້າ", "ໃບບິນ: $saleCode, ຍອດລວມ: $totalAmount ກີບ");

        jsonOk('ຊຳລະເງິນສຳເລັດ', ['sale_id' => $saleId, 'sale_code' => $saleCode]);

    } catch (Exception $e) {
        // Rollback ຄືນຂໍ້ມູນທັງໝົດຖ້າມີຂໍ້ຜິດພາດ
        mysqli_rollback($conn);
        jsonErr($e->getMessage(), 500);
    }
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
