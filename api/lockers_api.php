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
if ($action === 'get' && !hasPermission('lockers', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('lockers', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('lockers', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('lockers', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}
if (in_array($action, ['assign', 'release', 'toggle_status']) && !hasPermission('lockers', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນລັອກເກີ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ດຶງຂໍ້ມູນລັອກເກີ (Get Locker)
if ($action === 'get') {
    $lockerId = clean($conn, $_GET['locker_id'] ?? '');

    if ($lockerId === '') {
        jsonErr('ລະຫັດລັອກເກີບໍ່ຖືກຕ້ອງ');
    }

    $stmt = mysqli_prepare($conn, 'SELECT l.*, CONCAT(COALESCE(m.fname,""), " ", COALESCE(m.lname,"")) as member_full_name, m.member_code as member_code_ref FROM lockers l LEFT JOIN members m ON l.member_id = m.member_id WHERE l.locker_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $lockerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $locker = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$locker) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນລັອກເກີ', 404);
    }

    jsonOk('OK', ['locker' => $locker]);
}

// 1b. ດຶງລາຍການລັອກເກີຫວ່າງ (Get Available Lockers)
if ($action === 'get_available') {
    $stmt = mysqli_prepare($conn, "SELECT locker_id, locker_code, locker_floor FROM lockers WHERE status = 'Available' ORDER BY locker_code ASC");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $available = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $available[] = $row;
    }
    mysqli_stmt_close($stmt);
    jsonOk('OK', ['lockers' => $available]);
}

// 1c. Assign ລັອກເກີໃຫ້ສະມາຊິກ (Assign Locker)
if ($action === 'assign') {
    $lockerId = clean($conn, $_POST['locker_id'] ?? '');
    $memberId = clean($conn, $_POST['member_id'] ?? '');
    $memberName = clean($conn, $_POST['member_name'] ?? '');

    if ($lockerId === '' || $memberId === '') {
        jsonErr('ຂໍ້ມູນບໍ່ຄົບ');
    }

    // ກວດສອບ locker ຍັງຫວ່າງຢູ່ບໍ
    $stmtChk = mysqli_prepare($conn, "SELECT status FROM lockers WHERE locker_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtChk, 'i', $lockerId);
    mysqli_stmt_execute($stmtChk);
    $resChk = mysqli_stmt_get_result($stmtChk);
    $lockerRow = mysqli_fetch_assoc($resChk);
    mysqli_stmt_close($stmtChk);

    if (!$lockerRow) {
        jsonErr('ບໍ່ພົບລັອກເກີ', 404);
    }
    if ($lockerRow['status'] !== 'Available') {
        jsonErr('ລັອກເກີນີ້ຖືກໃຊ້ງານຢູ່ ຫຼື ເພ/ຊຳລຸດ');
    }

    $now = date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($conn, "UPDATE lockers SET status='Occupied', member_id=?, member_name=?, assigned_at=? WHERE locker_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'issi', $memberId, $memberName, $now, $lockerId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ມອບໝາຍລັອກເກີບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    jsonOk('ມອບໝາຍລັອກເກີສຳເລັດ');
}

// 1d. Release ລັອກເກີ (Release Locker - ສົ່ງຄືນ)
if ($action === 'release') {
    $lockerId = clean($conn, $_POST['locker_id'] ?? '');

    if ($lockerId === '') {
        jsonErr('ລະຫັດລັອກເກີບໍ່ຖືກຕ້ອງ');
    }

    // ດຶງຊື່ locker code ສຳລັບ log
    $stmtName = mysqli_prepare($conn, 'SELECT locker_code, member_name FROM lockers WHERE locker_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $lockerId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $lockerRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    $stmt = mysqli_prepare($conn, "UPDATE lockers SET status='Available', member_id=NULL, member_name=NULL, assigned_at=NULL WHERE locker_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $lockerId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ສົ່ງຄືນລັອກເກີບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    jsonOk('ສົ່ງຄືນລັອກເກີສຳເລັດ');
}

// 2. ເພີ່ມລັອກເກີໃໝ່ (Create Locker)
if ($action === 'create') {
    $lockerCode = clean($conn, $_POST['locker_code'] ?? '');
    $lockerFloor = clean($conn, $_POST['locker_floor'] ?? '');
    $status = clean($conn, $_POST['status'] ?? 'Available');

    if ($lockerCode === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດລັອກເກີ');
    }

    // ກວດສອບລະຫັດຊ້ຳ
    $stmtCheck = mysqli_prepare($conn, 'SELECT locker_id FROM lockers WHERE locker_code = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 's', $lockerCode);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);

    if ($exists) {
        jsonErr('ລະຫັດລັອກເກີ "' . $lockerCode . '" ນີ້ມີໃນລະບົບແລ້ວ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO lockers (locker_code, locker_floor, status) VALUES (?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'sss', $lockerCode, $lockerFloor, $status);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກລັອກເກີບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    logActivity($pdo, "ເພີ່ມລັອກເກີ", "ລະຫັດ: $lockerCode, ຊັ້ນ: $lockerFloor");

    jsonOk('ບັນທຶກລັອກເກີສຳເລັດ');
}

// 3. ແກ້ໄຂລັອກເກີ (Update Locker)
if ($action === 'update') {
    $lockerId = clean($conn, $_POST['locker_id'] ?? '');
    $lockerCode = clean($conn, $_POST['locker_code'] ?? '');
    $lockerFloor = clean($conn, $_POST['locker_floor'] ?? '');
    $status = clean($conn, $_POST['status'] ?? 'Available');

    if ($lockerId === '') {
        jsonErr('ລະຫັດລັອກເກີບໍ່ຖືກຕ້ອງ');
    }
    if ($lockerCode === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດລັອກເກີ');
    }

    // ກວດສອບລະຫັດຊ້ຳ (ຍົກເວັ້ນຕົວເອງ)
    $stmtCheck = mysqli_prepare($conn, 'SELECT locker_id FROM lockers WHERE locker_code = ? AND locker_id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 'si', $lockerCode, $lockerId);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);

    if ($exists) {
        jsonErr('ລະຫັດລັອກເກີ "' . $lockerCode . '" ນີ້ມີໃນລະບົບແລ້ວ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE lockers SET locker_code=?, locker_floor=?, status=? WHERE locker_id=?'
    );
    mysqli_stmt_bind_param($stmt, 'sssi', $lockerCode, $lockerFloor, $status, $lockerId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂລັອກເກີບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    logActivity($pdo, "ແກ້ໄຂລັອກເກີ", "ລະຫັດ: $lockerCode, ຊັ້ນ: $lockerFloor, ສະຖານະ: $status");

    jsonOk('ແກ້ໄຂຂໍ້ມູນລັອກເກີສຳເລັດ');
}

// 4. ລົບລັອກເກີ (Delete Locker)
if ($action === 'delete') {
    $lockerId = clean($conn, $_POST['locker_id'] ?? '');

    if ($lockerId === '') {
        jsonErr('ລະຫັດລັອກເກີບໍ່ຖືກຕ້ອງ');
    }

    // ດຶງລະຫັດເພື່ອເຮັດ Log
    $stmtName = mysqli_prepare($conn, 'SELECT locker_code FROM lockers WHERE locker_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $lockerId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $lockerRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    $lockerCode = $lockerRow ? $lockerRow['locker_code'] : $lockerId;

    $stmt = mysqli_prepare($conn, 'DELETE FROM lockers WHERE locker_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $lockerId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບລັອກເກີບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    logActivity($pdo, "ລົບລັອກເກີ", "ລະຫັດ: $lockerCode");

    jsonOk('ລົບລັອກເກີສຳເລັດ');
}

// 5. Toggle Status ລັອກເກີ (Available <-> Occupied)
if ($action === 'toggle_status') {
    $lockerId = clean($conn, $_POST['locker_id'] ?? '');
    $newStatus = clean($conn, $_POST['new_status'] ?? '');

    if ($lockerId === '') {
        jsonErr('ລະຫັດລັອກເກີບໍ່ຖືກຕ້ອງ');
    }
    if (!in_array($newStatus, ['Available', 'Occupied'])) {
        jsonErr('ສະຖານະບໍ່ຖືກຕ້ອງ');
    }

    // Set or clear assigned_at timestamp
    if ($newStatus === 'Available') {
        $stmt = mysqli_prepare($conn, "UPDATE lockers SET status=?, member_id=NULL, member_name=NULL, assigned_at=NULL WHERE locker_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $newStatus, $lockerId);
    } else {
        $now = date('Y-m-d H:i:s');
        $stmt = mysqli_prepare($conn, "UPDATE lockers SET status=?, assigned_at=? WHERE locker_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ssi', $newStatus, $now, $lockerId);
    }

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ປ່ຽນສະຖານະລັອກເກີບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    $statusLabel = ($newStatus === 'Occupied') ? 'ໃຊ້ງານ' : 'ຫວ່າງ';
    jsonOk('ປ່ຽນສະຖານະລັອກເກີເປັນ ' . $statusLabel . ' ສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
