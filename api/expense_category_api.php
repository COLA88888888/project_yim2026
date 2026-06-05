<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

// Check permission / Session
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
if (($action === 'get' || $action === 'list') && !hasPermission('expenses', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('expenses', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('expenses', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('expenses', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. Get Single Category
if ($action === 'get') {
    $categoryId = clean($conn, $_GET['category_id'] ?? '');
    
    if ($categoryId === '') {
        jsonErr('ລະຫັດປະເພດລາຍຈ່າຍບໍ່ຖືກຕ້ອງ');
    }
    
    $stmt = mysqli_prepare($conn, 'SELECT * FROM expense_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $categoryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$category) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນປະເພດລາຍຈ່າຍ', 404);
    }
    
    jsonOk('OK', ['category' => $category]);
}

// 2. List Categories
if ($action === 'list') {
    $categories = [];
    $sql = "SELECT * FROM expense_categories ORDER BY category_id ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    jsonOk('OK', ['categories' => $categories]);
}

// 3. Create Category
if ($action === 'create') {
    $categoryCode = clean($conn, $_POST['category_code'] ?? '');
    $categoryName = clean($conn, $_POST['category_name'] ?? '');

    if ($categoryCode === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດປະເພດລາຍຈ່າຍ');
    }
    if ($categoryName === '') {
        jsonErr('ກະລຸນາປ້ອນຊື່ປະເພດລາຍຈ່າຍ');
    }

    // Check duplicate code
    $checkSql = "SELECT COUNT(*) FROM expense_categories WHERE category_code = ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 's', $categoryCode);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ລະຫັດປະເພດລາຍຈ່າຍນີ້ມີໃນລະບົບແລ້ວ');
    }

    // Check duplicate name
    $checkSql2 = "SELECT COUNT(*) FROM expense_categories WHERE category_name = ?";
    $stmtCheck2 = mysqli_prepare($conn, $checkSql2);
    mysqli_stmt_bind_param($stmtCheck2, 's', $categoryName);
    mysqli_stmt_execute($stmtCheck2);
    $resCheck2 = mysqli_stmt_get_result($stmtCheck2);
    $countRow2 = mysqli_fetch_row($resCheck2);
    mysqli_stmt_close($stmtCheck2);

    if ($countRow2 && $countRow2[0] > 0) {
        jsonErr('ຊື່ປະເພດລາຍຈ່າຍນີ້ມີໃນລະບົບແລ້ວ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO expense_categories (category_code, category_name) VALUES (?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $categoryCode, $categoryName);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກປະເພດລາຍຈ່າຍບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ເພີ່ມປະເພດລາຍຈ່າຍ", "ລະຫັດ: $categoryCode, ຊື່: $categoryName");

    jsonOk('ເພີ່ມປະເພດລາຍຈ່າຍສຳເລັດ');
}

// 4. Update Category
if ($action === 'update') {
    $categoryId = clean($conn, $_POST['category_id'] ?? '');
    $categoryCode = clean($conn, $_POST['category_code'] ?? '');
    $categoryName = clean($conn, $_POST['category_name'] ?? '');

    if ($categoryId === '') {
        jsonErr('ລະຫັດປະເພດລາຍຈ່າຍບໍ່ຖືກຕ້ອງ');
    }
    if ($categoryCode === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດປະເພດລາຍຈ່າຍ');
    }
    if ($categoryName === '') {
        jsonErr('ກະລຸນາປ້ອນຊື່ປະເພດລາຍຈ່າຍ');
    }

    // Check duplicate code (excluding current ID)
    $checkSql = "SELECT COUNT(*) FROM expense_categories WHERE category_code = ? AND category_id != ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 'si', $categoryCode, $categoryId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ລະຫັດປະເພດລາຍຈ່າຍນີ້ມີໃນລະບົບແລ້ວ');
    }

    // Check duplicate name (excluding current ID)
    $checkSql2 = "SELECT COUNT(*) FROM expense_categories WHERE category_name = ? AND category_id != ?";
    $stmtCheck2 = mysqli_prepare($conn, $checkSql2);
    mysqli_stmt_bind_param($stmtCheck2, 'si', $categoryName, $categoryId);
    mysqli_stmt_execute($stmtCheck2);
    $resCheck2 = mysqli_stmt_get_result($stmtCheck2);
    $countRow2 = mysqli_fetch_row($resCheck2);
    mysqli_stmt_close($stmtCheck2);

    if ($countRow2 && $countRow2[0] > 0) {
        jsonErr('ຊື່ປະເພດລາຍຈ່າຍນີ້ມີໃນລະບົບແລ້ວ');
    }

    // Get the old category name first
    $stmtOld = mysqli_prepare($conn, 'SELECT category_name FROM expense_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtOld, 'i', $categoryId);
    mysqli_stmt_execute($stmtOld);
    $resOld = mysqli_stmt_get_result($stmtOld);
    $oldRow = mysqli_fetch_assoc($resOld);
    mysqli_stmt_close($stmtOld);

    if ($oldRow) {
        $oldName = $oldRow['category_name'];
        // Start transaction
        mysqli_begin_transaction($conn);
        try {
            // Update expenses table category name
            $stmtExpUpdate = mysqli_prepare($conn, 'UPDATE expenses SET category = ? WHERE category = ?');
            mysqli_stmt_bind_param($stmtExpUpdate, 'ss', $categoryName, $oldName);
            mysqli_stmt_execute($stmtExpUpdate);
            mysqli_stmt_close($stmtExpUpdate);

            // Update category table name and code
            $stmtCatUpdate = mysqli_prepare($conn, 'UPDATE expense_categories SET category_code = ?, category_name = ? WHERE category_id = ?');
            mysqli_stmt_bind_param($stmtCatUpdate, 'ssi', $categoryCode, $categoryName, $categoryId);
            mysqli_stmt_execute($stmtCatUpdate);
            mysqli_stmt_close($stmtCatUpdate);

            mysqli_commit($conn);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            jsonErr('ແກ້ໄຂປະເພດລາຍຈ່າຍບໍ່ສຳເລັດ: ' . $e->getMessage(), 500);
        }
    } else {
        jsonErr('ບໍ່ພົບຂໍ້ມູນປະເພດລາຍຈ່າຍ', 404);
    }
    
    logActivity($pdo, "ແກ້ໄຂປະເພດລາຍຈ່າຍ", "ລະຫັດ: $categoryId, ລະຫັດປະເພດ: $categoryCode, ຊື່: $categoryName");

    jsonOk('ແກ້ໄຂຂໍ້ມູນປະເພດລາຍຈ່າຍສຳເລັດ');
}

// 5. Delete Category
if ($action === 'delete') {
    $categoryId = clean($conn, $_POST['category_id'] ?? '');

    if ($categoryId === '') {
        jsonErr('ລະຫັດປະເພດລາຍຈ່າຍບໍ່ຖືກຕ້ອງ');
    }

    // Get name for check and activity log
    $stmtName = mysqli_prepare($conn, 'SELECT category_name FROM expense_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $categoryId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $cRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    if (!$cRow) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນປະເພດລາຍຈ່າຍ', 404);
    }

    $categoryName = $cRow['category_name'];

    // Check if there are expenses currently using this category name
    $checkSql = "SELECT COUNT(*) FROM expenses WHERE category = ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 's', $categoryName);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ບໍ່ສາມາດລົບໄດ້ ເພາະຍັງມີຂໍ້ມູນລາຍຈ່າຍທີ່ໃຊ້ປະເພດນີ້ຢູ່');
    }

    // Delete category
    $stmt = mysqli_prepare($conn, 'DELETE FROM expense_categories WHERE category_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $categoryId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບປະເພດລາຍຈ່າຍບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ລົບປະເພດລາຍຈ່າຍ", "ຊື່: $categoryName");

    jsonOk('ລົບປະເພດລາຍຈ່າຍສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
