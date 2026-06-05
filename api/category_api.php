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

// ຄວາມປອດໄພ & ສິດທິການໃຊ້ງານ
if (($action === 'get' || $action === 'list') && !hasPermission('product_categories', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('product_categories', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('product_categories', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('product_categories', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ດຶງຂໍ້ມູນປະເພດສິນຄ້າດ່ຽວ (Get Category)
if ($action === 'get') {
    $categoryId = clean($conn, $_GET['category_id'] ?? '');
    
    if ($categoryId === '') {
        jsonErr('ລະຫັດປະເພດສິນຄ້າບໍ່ຖືກຕ້ອງ');
    }
    
    $stmt = mysqli_prepare($conn, 'SELECT * FROM product_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $categoryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$category) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນປະເພດສິນຄ້າ', 404);
    }
    
    jsonOk('OK', ['category' => $category]);
}

// 2. ດຶງຂໍ້ມູນປະເພດສິນຄ້າທັງໝົດ (List Categories)
if ($action === 'list') {
    $categories = [];
    $sql = "SELECT * FROM product_categories ORDER BY category_id ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    jsonOk('OK', ['categories' => $categories]);
}

// 3. ເພີ່ມປະເພດສິນຄ້າໃໝ່ (Create Category)
if ($action === 'create') {
    $categoryCode = clean($conn, $_POST['category_code'] ?? '');
    $categoryName = clean($conn, $_POST['category_name'] ?? '');

    if ($categoryCode === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດປະເພດສິນຄ້າ');
    }
    if ($categoryName === '') {
        jsonErr('ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ');
    }

    // ກວດສອບລະຫັດຊ້ຳ
    $checkSql = "SELECT COUNT(*) FROM product_categories WHERE category_code = ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 's', $categoryCode);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ລະຫັດປະເພດສິນຄ້ານີ້ມີໃນລະບົບແລ້ວ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO product_categories (category_code, category_name) VALUES (?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $categoryCode, $categoryName);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກປະເພດສິນຄ້າບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ເພີ່ມປະເພດສິນຄ້າ", "ລະຫັດ: $categoryCode, ຊື່: $categoryName");

    jsonOk('ເພີ່ມປະເພດສິນຄ້າສຳເລັດ');
}

// 4. ແກ້ໄຂປະເພດສິນຄ້າ (Update Category)
if ($action === 'update') {
    $categoryId = clean($conn, $_POST['category_id'] ?? '');
    $categoryCode = clean($conn, $_POST['category_code'] ?? '');
    $categoryName = clean($conn, $_POST['category_name'] ?? '');

    if ($categoryId === '') {
        jsonErr('ລະຫັດປະເພດສິນຄ້າບໍ່ຖືກຕ້ອງ');
    }
    if ($categoryCode === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດປະເພດສິນຄ້າ');
    }
    if ($categoryName === '') {
        jsonErr('ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ');
    }

    // ກວດສອບລະຫັດຊ້ຳ (ຍົກເວັ້ນ ID ປັດຈຸບັນ)
    $checkSql = "SELECT COUNT(*) FROM product_categories WHERE category_code = ? AND category_id != ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 'si', $categoryCode, $categoryId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ລະຫັດປະເພດສິນຄ້ານີ້ມີໃນລະບົບແລ້ວ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE product_categories SET category_code=?, category_name=? WHERE category_id=?'
    );
    mysqli_stmt_bind_param($stmt, 'ssi', $categoryCode, $categoryName, $categoryId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂປະເພດສິນຄ້າບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ແກ້ໄຂປະເພດສິນຄ້າ", "ລະຫັດ: $categoryId, ລະຫັດປະເພດ: $categoryCode, ຊື່: $categoryName");

    jsonOk('ແກ້ໄຂຂໍ້ມູນປະເພດສິນຄ້າສຳເລັດ');
}

// 5. ລົບປະເພດສິນຄ້າ (Delete Category)
if ($action === 'delete') {
    $categoryId = clean($conn, $_POST['category_id'] ?? '');

    if ($categoryId === '') {
        jsonErr('ລະຫັດປະເພດສິນຄ້າບໍ່ຖືກຕ້ອງ');
    }

    // ກວດສອບກ່ອນວ່າມີສິນຄ້າຢູ່ໃນປະເພດນີ້ຢູ່ບໍ່
    $checkSql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 'i', $categoryId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ບໍ່ສາມາດລົບໄດ້ ເພາະຍັງມີສິນຄ້າຢູ່ໃນປະເພດນີ້');
    }

    // ດຶງຊື່ເພື່ອເຮັດ Log
    $stmtName = mysqli_prepare($conn, 'SELECT category_name FROM product_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $categoryId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $cRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    $categoryName = $cRow ? $cRow['category_name'] : $categoryId;

    // ລົບປະເພດສິນຄ້າ
    $stmt = mysqli_prepare($conn, 'DELETE FROM product_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $categoryId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບປະເພດສິນຄ້າບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ລົບປະເພດສິນຄ້າ", "ຊື່: $categoryName");

    jsonOk('ລົບປະເພດສິນຄ້າສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
