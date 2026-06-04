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
if ($action === 'get' && !hasPermission('packages', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('packages', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('packages', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('packages', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ດຶງຂໍ້ມູນແພັກເກດ (Get Package)
if ($action === 'get') {
    $packageId = clean($conn, $_GET['package_id'] ?? '');
    
    if ($packageId === '') {
        jsonErr('ລະຫັດແພັກເກດບໍ່ຖືກຕ້ອງ');
    }
    
    $stmt = mysqli_prepare($conn, 'SELECT * FROM packages WHERE package_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $packageId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $package = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$package) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນແພັກເກດ', 404);
    }
    
    jsonOk('OK', ['package' => $package]);
}

// 2. ເພີ່ມແພັກເກດໃໝ່ (Create Package)
if ($action === 'create') {
    $packageName = clean($conn, $_POST['package_name'] ?? '');
    $durationDays = clean($conn, $_POST['duration_days'] ?? '');
    $price = clean($conn, $_POST['price'] ?? '');
    $description = clean($conn, $_POST['description'] ?? '');

    if ($packageName === '' || $durationDays === '' || $price === '') {
        jsonErr('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ (ຊື່ແພັກເກດ, ຈຳນວນມື້, ແລະ ລາຄາ)');
    }

    $durationDays = (int)$durationDays;
    $price = (float)$price;

    if ($durationDays <= 0) {
        jsonErr('ຈຳນວນມື້ຕ້ອງຫຼາຍກວ່າ 0');
    }
    if ($price < 0) {
        jsonErr('ລາຄາຕ້ອງບໍ່ຕໍ່າກວ່າ 0');
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO packages (package_name, duration_days, price, description) VALUES (?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'sids', $packageName, $durationDays, $price, $description);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກແພັກເກດບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ເພີ່ມແພັກເກດ", "ຊື່: $packageName, ລາຄາ: $price ກີບ, ໄລຍະເວລາ: $durationDays ມື້");

    jsonOk('ເພີ່ມແພັກເກດສຳເລັດ');
}

// 3. ແກ້ໄຂແພັກເກດ (Update Package)
if ($action === 'update') {
    $packageId = clean($conn, $_POST['package_id'] ?? '');
    $packageName = clean($conn, $_POST['package_name'] ?? '');
    $durationDays = clean($conn, $_POST['duration_days'] ?? '');
    $price = clean($conn, $_POST['price'] ?? '');
    $description = clean($conn, $_POST['description'] ?? '');

    if ($packageId === '') {
        jsonErr('ລະຫັດແພັກເກດບໍ່ຖືກຕ້ອງ');
    }
    if ($packageName === '' || $durationDays === '' || $price === '') {
        jsonErr('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ (ຊື່ແພັກເກດ, ຈຳນວນມື້, ແລະ ລາຄາ)');
    }

    $durationDays = (int)$durationDays;
    $price = (float)$price;

    if ($durationDays <= 0) {
        jsonErr('ຈຳນວນມື້ຕ້ອງຫຼາຍກວ່າ 0');
    }
    if ($price < 0) {
        jsonErr('ລາຄາຕ້ອງບໍ່ຕໍ່າກວ່າ 0');
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE packages SET package_name=?, duration_days=?, price=?, description=? WHERE package_id=?'
    );
    mysqli_stmt_bind_param($stmt, 'sidsi', $packageName, $durationDays, $price, $description, $packageId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂແພັກເກດບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ແກ້ໄຂແພັກເກດ", "ລະຫັດ: $packageId, ຊື່: $packageName");

    jsonOk('ແກ້ໄຂຂໍ້ມູນແພັກເກດສຳເລັດ');
}

// 4. ລົບແພັກເກດ (Delete Package)
if ($action === 'delete') {
    $packageId = clean($conn, $_POST['package_id'] ?? '');

    if ($packageId === '') {
        jsonErr('ລະຫັດແພັກເກດບໍ່ຖືກຕ້ອງ');
    }

    // ກວດສອບກ່ອນວ່າມີສະມາຊິກໃຊ້ແພັກເກດນີ້ຢູ່ບໍ່
    $checkSql = "SELECT COUNT(*) FROM memberships WHERE package_id = ? AND status = 'Active'";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 'i', $packageId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $countRow = mysqli_fetch_row($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($countRow && $countRow[0] > 0) {
        jsonErr('ບໍ່ສາມາດລົບແພັກເກດນີ້ໄດ້ ເພາະຍັງມີສະມາຊິກທີ່ກຳລັງໃຊ້ງານແພັກເກດນີ້ຢູ່');
    }

    // ດຶງຊື່ເພື່ອເຮັດ Log
    $stmtName = mysqli_prepare($conn, 'SELECT package_name FROM packages WHERE package_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $packageId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $pRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    $packageName = $pRow ? $pRow['package_name'] : $packageId;

    // ລົບແພັກເກດ
    $stmt = mysqli_prepare($conn, 'DELETE FROM packages WHERE package_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $packageId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບແພັກເກດບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ລົບແພັກເກດ", "ຊື່: $packageName");

    jsonOk('ລົບແພັກເກດສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
