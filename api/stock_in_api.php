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

// 1. ດຶງລາຍລະອຽດໃບບິນນຳເຂົ້າ (Get Stock In Invoice Details)
if ($action === 'get') {
    $stockInId = clean($conn, $_GET['stock_in_id'] ?? '');
    
    if ($stockInId === '') {
        jsonErr('ລະຫັດໃບບິນນຳເຂົ້າບໍ່ຖືກຕ້ອງ');
    }
    
    // ດຶງໃບບິນຫຼັກ
    $stmtStock = mysqli_prepare($conn, '
        SELECT s.*, u.fname as staff_fname, u.lname as staff_lname 
        FROM stock_in s
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE s.stock_in_id = ? LIMIT 1
    ');
    mysqli_stmt_bind_param($stmtStock, 'i', $stockInId);
    mysqli_stmt_execute($stmtStock);
    $resStock = mysqli_stmt_get_result($stmtStock);
    $stock = mysqli_fetch_assoc($resStock);
    mysqli_stmt_close($stmtStock);
    
    if (!$stock) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນໃບບິນນຳເຂົ້າ', 404);
    }
    
    // ດຶງລາຍການສິນຄ້າໃນໃບບິນ
    $items = [];
    $stmtItems = mysqli_prepare($conn, '
        SELECT sd.*, p.product_name, p.product_code 
        FROM stock_in_details sd
        LEFT JOIN products p ON sd.product_id = p.product_id 
        WHERE sd.stock_in_id = ?
    ');
    mysqli_stmt_bind_param($stmtItems, 'i', $stockInId);
    mysqli_stmt_execute($stmtItems);
    $resItems = mysqli_stmt_get_result($stmtItems);
    while ($row = mysqli_fetch_assoc($resItems)) {
        $items[] = $row;
    }
    mysqli_stmt_close($stmtItems);
    
    jsonOk('OK', ['stock' => $stock, 'items' => $items]);
}

// 2. ເພີ່ມໃບບິນນຳເຂົ້າສິນຄ້າໃໝ່ (Create Stock In)
if ($action === 'create') {
    $supplier = clean($conn, $_POST['supplier'] ?? '');
    $totalAmount = clean($conn, $_POST['total_amount'] ?? '0');
    $itemsJson = $_POST['items'] ?? '[]';
    $userId = $_SESSION['user_id'];

    $items = json_decode($itemsJson, true);
    if (!is_array($items) || empty($items)) {
        jsonErr('ກະລຸນາເພີ່ມສິນຄ້າທີ່ຈະນຳເຂົ້າຢ່າງໜ້ອຍ 1 ລາຍການ');
    }

    $totalAmount = (float)$totalAmount;

    // ເລີ່ມໃຊ້ງານ Database Transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. Insert ຂໍ້ມູນລົງໃນຕາຕະລາງ stock_in
        $stmtStock = mysqli_prepare(
            $conn,
            'INSERT INTO stock_in (supplier, total_amount, user_id) VALUES (?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmtStock, 'sds', $supplier, $totalAmount, $userId);
        if (!mysqli_stmt_execute($stmtStock)) {
            throw new Exception('ບໍ່ສາມາດບັນທຶກໃບບິນນຳເຂົ້າໄດ້: ' . mysqli_error($conn));
        }
        $stockInId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtStock);

        // 2. ວົນລູບ Insert ລາຍການສິນຄ້າ ແລະ ອັບເດດຈຳນວນສິນຄ້າໃນຄັງ
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            $costPrice = (float)($item['cost_price'] ?? 0);

            if ($productId <= 0 || $qty <= 0 || $costPrice < 0) {
                throw new Exception('ຂໍ້ມູນສິນຄ້າທີ່ນຳເຂົ້າບໍ່ຖືກຕ້ອງ');
            }

            // Insert ຂໍ້ມູນລົງໃນ stock_in_details
            $stmtDetail = mysqli_prepare(
                $conn,
                'INSERT INTO stock_in_details (stock_in_id, product_id, quantity, cost_price) VALUES (?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmtDetail, 'iiid', $stockInId, $productId, $qty, $costPrice);
            if (!mysqli_stmt_execute($stmtDetail)) {
                throw new Exception('ບໍ່ສາມາດບັນທຶກລາຍລະອຽດການນຳເຂົ້າໄດ້: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmtDetail);

            // ອັບເດດ ຈຳນວນສິນຄ້າ (Quantity) ແລະ ລາຄາຕົ້ນທຶນ (Cost Price) ໃນຕາຕະລາງ products
            $stmtUpdateProduct = mysqli_prepare(
                $conn,
                'UPDATE products SET quantity = quantity + ?, cost_price = ? WHERE product_id = ?'
            );
            mysqli_stmt_bind_param($stmtUpdateProduct, 'idi', $qty, $costPrice, $productId);
            if (!mysqli_stmt_execute($stmtUpdateProduct)) {
                throw new Exception('ບໍ່ສາມາດອັບເດດຈຳນວນສິນຄ້າໃນຄັງໄດ້: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmtUpdateProduct);
        }

        // Commit ທຸລະກຳເມື່ອເຮັດວຽກທຸກຂັ້ນຕອນສຳເລັດ
        mysqli_commit($conn);
        
        logActivity($pdo, "ນຳເຂົ້າສິນຄ້າ", "ໃບບິນ: #$stockInId, ຍອດລວມ: $totalAmount ກີບ");

        jsonOk('ບັນທຶກການນຳເຂົ້າສິນຄ້າສຳເລັດ', ['stock_in_id' => $stockInId]);

    } catch (Exception $e) {
        // Rollback ຄືນຂໍ້ມູນທັງໝົດຖ້າມີຂໍ້ຜິດພາດ
        mysqli_rollback($conn);
        jsonErr($e->getMessage(), 500);
    }
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
