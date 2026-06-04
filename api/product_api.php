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

function handleProductImageUpload($conn, $existingImage = null) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return $existingImage;
    }

    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    
    // Validate file size (2MB limit)
    if ($file['size'] > 2 * 1024 * 1024) {
        jsonErr('ຂະໜາດຮູບພາບຕ້ອງບໍ່ເກີນ 2MB');
    }

    // Validate MIME type
    if (!in_array($file['type'], $allowedTypes)) {
        jsonErr('ຮູບແບບໄຟລ໌ຮູບພາບບໍ່ຖືກຕ້ອງ (ອະນຸຍາດສະເພາະ JPG, PNG, WEBP)');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $allowedExts)) {
        jsonErr('ຮູບແບບໄຟລ໌ຮູບພາບບໍ່ຖືກຕ້ອງ (ອະນຸຍາດສະເພາະ JPG, PNG, WEBP)');
    }

    // Create directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique name
    $newFileName = uniqid('prod_', true) . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        jsonErr('ບໍ່ສາມາດອັບໂຫຼດຮູບພາບໄດ້');
    }

    // Delete old image if it exists
    if ($existingImage && $existingImage !== '') {
        $oldPath = $uploadDir . $existingImage;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $newFileName;
}

// 1. ດຶງຂໍ້ມູນສິນຄ້າດ່ຽວ (Get Product)
if ($action === 'get') {
    $productId = clean($conn, $_GET['product_id'] ?? '');
    
    if ($productId === '') {
        jsonErr('ລະຫັດສິນຄ້າບໍ່ຖືກຕ້ອງ');
    }
    
    $stmt = mysqli_prepare($conn, 'SELECT * FROM products WHERE product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$product) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນສິນຄ້າ', 404);
    }
    
    jsonOk('OK', ['product' => $product]);
}

// 2. ດຶງຂໍ້ມູນສິນຄ້າທັງໝົດ (List Products)
if ($action === 'list') {
    $products = [];
    $sql = "SELECT p.*, c.category_name, c.category_code 
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.category_id 
            ORDER BY p.product_id ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    jsonOk('OK', ['products' => $products]);
}

// 3. ເພີ່ມສິນຄ້າໃໝ່ (Create Product)
if ($action === 'create') {
    $productCode = clean($conn, $_POST['product_code'] ?? '');
    $productName = clean($conn, $_POST['product_name'] ?? '');
    $categoryId = clean($conn, $_POST['category_id'] ?? '');
    $costPrice = str_replace(',', '', clean($conn, $_POST['cost_price'] ?? '0'));
    $salePrice = str_replace(',', '', clean($conn, $_POST['sale_price'] ?? ''));
    $unit = clean($conn, $_POST['unit'] ?? '');

    if ($productCode === '' || $productName === '' || $categoryId === '' || $salePrice === '') {
        jsonErr('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ (ລະຫັດ, ຊື່ສິນຄ້າ, ປະເພດ, ແລະ ລາຄາຂາຍ)');
    }

    $categoryId = (int)$categoryId;
    $costPrice = (float)$costPrice;
    $salePrice = (float)$salePrice;

    if ($salePrice < 0 || $costPrice < 0) {
        jsonErr('ລາຄາຕ້ອງບໍ່ຕໍ່າກວ່າ 0');
    }

    // ກວດສອບລະຫັດສິນຄ້າຊໍ້າກັນ
    $checkSql = "SELECT COUNT(*) FROM products WHERE product_code = ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 's', $productCode);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ລະຫັດສິນຄ້ານີ້ມີຢູ່ໃນລະບົບແລ້ວ');
    }

    // handle image upload
    $imageName = handleProductImageUpload($conn);

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO products (product_code, product_name, category_id, cost_price, sale_price, quantity, unit, image) VALUES (?, ?, ?, ?, ?, 0, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ssiddss', $productCode, $productName, $categoryId, $costPrice, $salePrice, $unit, $imageName);

    if (!mysqli_stmt_execute($stmt)) {
        if ($imageName) {
            @unlink(__DIR__ . '/../uploads/products/' . $imageName);
        }
        jsonErr('ບັນທຶກສິນຄ້າບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ເພີ່ມສິນຄ້າ", "ລະຫັດ: $productCode, ຊື່: $productName, ລາຄາຂາຍ: $salePrice");

    jsonOk('ເພີ່ມສິນຄ້າສຳເລັດ');
}

// 4. ແກ້ໄຂສິນຄ້າ (Update Product)
if ($action === 'update') {
    $productId = clean($conn, $_POST['product_id'] ?? '');
    $productCode = clean($conn, $_POST['product_code'] ?? '');
    $productName = clean($conn, $_POST['product_name'] ?? '');
    $categoryId = clean($conn, $_POST['category_id'] ?? '');
    $costPrice = str_replace(',', '', clean($conn, $_POST['cost_price'] ?? '0'));
    $salePrice = str_replace(',', '', clean($conn, $_POST['sale_price'] ?? ''));
    $unit = clean($conn, $_POST['unit'] ?? '');

    if ($productId === '') {
        jsonErr('ລະຫັດສິນຄ້າບໍ່ຖືກຕ້ອງ');
    }
    if ($productCode === '' || $productName === '' || $categoryId === '' || $salePrice === '') {
        jsonErr('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ (ລະຫັດ, ຊື່ສິນຄ້າ, ປະເພດ, ແລະ ລາຄາຂາຍ)');
    }

    $productId = (int)$productId;
    $categoryId = (int)$categoryId;
    $costPrice = (float)$costPrice;
    $salePrice = (float)$salePrice;

    if ($salePrice < 0 || $costPrice < 0) {
        jsonErr('ລາຄາຕ້ອງບໍ່ຕໍ່າກວ່າ 0');
    }

    // ກວດສອບລະຫັດສິນຄ້າຊໍ້າກັນ (ຍົກເວັ້ນສິນຄ້າຕົວເອງ)
    $checkSql = "SELECT COUNT(*) FROM products WHERE product_code = ? AND product_id != ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 'si', $productCode, $productId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ລະຫັດສິນຄ້ານີ້ຖືກໃຊ້ໂດຍສິນຄ້າອື່ນແລ້ວ');
    }

    // ດຶງຮູບພາບເກົ່າ
    $stmtImg = mysqli_prepare($conn, 'SELECT image FROM products WHERE product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtImg, 'i', $productId);
    mysqli_stmt_execute($stmtImg);
    $resImg = mysqli_stmt_get_result($stmtImg);
    $imgRow = mysqli_fetch_assoc($resImg);
    mysqli_stmt_close($stmtImg);
    $existingImage = $imgRow ? $imgRow['image'] : null;

    // handle image upload
    $imageName = handleProductImageUpload($conn, $existingImage);

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE products SET product_code=?, product_name=?, category_id=?, cost_price=?, sale_price=?, unit=?, image=? WHERE product_id=?'
    );
    mysqli_stmt_bind_param($stmt, 'ssiddssi', $productCode, $productName, $categoryId, $costPrice, $salePrice, $unit, $imageName, $productId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂສິນຄ້າບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ແກ້ໄຂສິນຄ້າ", "ລະຫັດ: $productId, ຊື່: $productName");

    jsonOk('ແກ້ໄຂຂໍ້ມູນສິນຄ້າສຳເລັດ');
}

// 5. ລົບສິນຄ້າ (Delete Product)
if ($action === 'delete') {
    $productId = clean($conn, $_POST['product_id'] ?? '');

    if ($productId === '') {
        jsonErr('ລະຫັດສິນຄ້າບໍ່ຖືກຕ້ອງ');
    }

    // ກວດສອບວ່າມີການເຄື່ອນໄຫວໃນສາງ (Stock In Details) ຫຼື ບໍ່
    $checkStockSql = "SELECT COUNT(*) FROM stock_in_details WHERE product_id = ?";
    $stmtCheck1 = mysqli_prepare($conn, $checkStockSql);
    mysqli_stmt_bind_param($stmtCheck1, 'i', $productId);
    mysqli_stmt_execute($stmtCheck1);
    $resCheck1 = mysqli_stmt_get_result($stmtCheck1);
    $countRow1 = mysqli_fetch_row($resCheck1);
    mysqli_stmt_close($stmtCheck1);

    if ($countRow1 && $countRow1[0] > 0) {
        jsonErr('ບໍ່ສາມາດລົບສິນຄ້ານີ້ໄດ້ ເພາະເຄີຍມີປະຫວັດການນຳເຂົ້າສິນຄ້າແລ້ວ');
    }

    // ກວດສອບວ່າມີການຂາຍ (Sale Details) ໄປແລ້ວ ຫຼື ບໍ່
    $checkSalesSql = "SELECT COUNT(*) FROM sale_details WHERE product_id = ?";
    $stmtCheck2 = mysqli_prepare($conn, $checkSalesSql);
    mysqli_stmt_bind_param($stmtCheck2, 'i', $productId);
    mysqli_stmt_execute($stmtCheck2);
    $resCheck2 = mysqli_stmt_get_result($stmtCheck2);
    $countRow2 = mysqli_fetch_row($resCheck2);
    mysqli_stmt_close($stmtCheck2);

    if ($countRow2 && $countRow2[0] > 0) {
        jsonErr('ບໍ່ສາມາດລົບສິນຄ້ານີ້ໄດ້ ເພາະເຄີຍມີປະຫວັດການຂາຍສິນຄ້າແລ້ວ');
    }

    // ດຶງຊື່ ແລະ ຮູບພາບ ເພື່ອເຮັດ Log ແລະ ລົບໄຟລ໌
    $stmtName = mysqli_prepare($conn, 'SELECT product_name, image FROM products WHERE product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $productId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $pRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    $productName = $pRow ? $pRow['product_name'] : $productId;
    $productImage = $pRow ? $pRow['image'] : '';

    // ລົບສິນຄ້າ
    $stmt = mysqli_prepare($conn, 'DELETE FROM products WHERE product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $productId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບສິນຄ້າບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    // ລົບໄຟລ໌ຮູບພາບ
    if ($productImage && $productImage !== '') {
        $oldPath = __DIR__ . '/../uploads/products/' . $productImage;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }
    
    logActivity($pdo, "ລົບສິນຄ້າ", "ຊື່: $productName");

    jsonOk('ລົບສິນຄ້າສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
