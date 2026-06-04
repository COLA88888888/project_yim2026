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
if ($action === 'get' && !hasPermission('subscriptions', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('subscriptions', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('subscriptions', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ດຶງຂໍ້ມູນການສະໝັກ (Get Subscription Detail)
if ($action === 'get') {
    $membershipId = clean($conn, $_GET['membership_id'] ?? '');

    if ($membershipId === '') {
        jsonErr('ລະຫັດການສະໝັກບໍ່ຖືກຕ້ອງ');
    }

    $sql = "SELECT ms.*, mb.fname, mb.lname, mb.member_code, p.package_name 
            FROM memberships ms 
            LEFT JOIN members mb ON ms.member_id = mb.member_id 
            LEFT JOIN packages p ON ms.package_id = p.package_id 
            WHERE ms.membership_id = ? LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $membershipId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $subscription = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$subscription) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນການສະໝັກ', 404);
    }

    jsonOk('OK', ['subscription' => $subscription]);
}

// 2. ລົງທະບຽນສະໝັກແພັກເກດໃໝ່ (Create Subscription & Payment)
if ($action === 'create') {
    $memberId = clean($conn, $_POST['member_id'] ?? '');
    $packageId = clean($conn, $_POST['package_id'] ?? '');
    $startDate = clean($conn, $_POST['start_date'] ?? '');
    $pricePaid = clean($conn, $_POST['price_paid'] ?? '');
    $paymentMethod = clean($conn, $_POST['payment_method'] ?? 'ເງິນສົດ');

    if ($memberId === '' || $packageId === '') {
        jsonErr('ກະລຸນາເລືອກສະມາຊິກ ແລະ ແພັກເກດ');
    }

    if ($startDate === '') {
        $startDate = date('Y-m-d');
    }

    // ດຶງຂໍ້ມູນແພັກເກດເພື່ອຄຳນວນວັນໝົດອາຍຸ
    $pkgSql = "SELECT duration_days, price, package_name FROM packages WHERE package_id = ? LIMIT 1";
    $stmtPkg = mysqli_prepare($conn, $pkgSql);
    mysqli_stmt_bind_param($stmtPkg, 'i', $packageId);
    mysqli_stmt_execute($stmtPkg);
    $resPkg = mysqli_stmt_get_result($stmtPkg);
    $pkg = mysqli_fetch_assoc($resPkg);
    mysqli_stmt_close($stmtPkg);

    if (!$pkg) {
        jsonErr('ບໍ່ພົບແພັກເກດທີ່ເລືອກ');
    }

    if ($pricePaid === '') {
        $pricePaid = $pkg['price'];
    } else {
        $pricePaid = (float)$pricePaid;
    }

    $durationDays = (int)$pkg['duration_days'];

    // ຄຳນວນວັນໝົດອາຍຸ: start_date + duration_days
    $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $durationDays . ' days'));

    // ປິດການໃຊ້ງານ (Expire) ແພັກເກດເກົ່າຂອງສະມາຊິກຄົນນີ້ທີ່ກຳລັງ Active ຢູ່
    $expireOldSql = "UPDATE memberships SET status = 'Expired' WHERE member_id = ? AND status = 'Active'";
    $stmtExp = mysqli_prepare($conn, $expireOldSql);
    mysqli_stmt_bind_param($stmtExp, 'i', $memberId);
    mysqli_stmt_execute($stmtExp);
    mysqli_stmt_close($stmtExp);

    $userId = $_SESSION['user_id'] ?? 'U001';
    // ເພີ່ມຂໍ້ມູນການສະໝັກໃໝ່
    $insSql = "INSERT INTO memberships (member_id, package_id, start_date, end_date, price_paid, payment_method, payment_status, status, user_id) 
               VALUES (?, ?, ?, ?, ?, ?, 'Paid', 'Active', ?)";
    $stmtIns = mysqli_prepare($conn, $insSql);
    mysqli_stmt_bind_param($stmtIns, 'iissdss', $memberId, $packageId, $startDate, $endDate, $pricePaid, $paymentMethod, $userId);
    
    if (!mysqli_stmt_execute($stmtIns)) {
        jsonErr('ບັນທຶກການສະໝັກແພັກເກດບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    
    $newMembershipId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmtIns);

    // ອັບເດດສະຖານະສະມາຊິກໃຫ້ເປັນ Active ໃນຕາຕະລາງ members
    $updMemberSql = "UPDATE members SET status = 'Active' WHERE member_id = ?";
    $stmtUpd = mysqli_prepare($conn, $updMemberSql);
    mysqli_stmt_bind_param($stmtUpd, 'i', $memberId);
    mysqli_stmt_execute($stmtUpd);
    mysqli_stmt_close($stmtUpd);

    // ດຶງຂໍ້ມູນສະມາຊິກເພື່ອເຮັດ Log
    $memSql = "SELECT fname, lname, member_code FROM members WHERE member_id = ? LIMIT 1";
    $resMem = mysqli_query($conn, "SELECT fname, lname, member_code FROM members WHERE member_id = '$memberId'");
    $mem = mysqli_fetch_assoc($resMem);
    $memName = $mem ? ($mem['fname'] . ' ' . $mem['lname']) : $memberId;
    $memCode = $mem ? $mem['member_code'] : '';

    logActivity($pdo, "ລົງທະບຽນແພັກເກດ", "ສະມາຊິກ: $memName (ລະຫັດ: $memCode), ແພັກເກດ: {$pkg['package_name']}, ຈ່າຍ: $pricePaid ກີບ, ໝົດອາຍຸ: $endDate");

    jsonOk('ລົງທະບຽນ ແລະ ຊຳລະເງິນສຳເລັດ', [
        'membership_id' => $newMembershipId,
        'end_date' => $endDate
    ]);
}

// 3. ລົບ/ຍົກເລີກການສະໝັກ (Delete/Cancel Subscription)
if ($action === 'delete') {
    $membershipId = clean($conn, $_POST['membership_id'] ?? '');

    if ($membershipId === '') {
        jsonErr('ລະຫັດການສະໝັກບໍ່ຖືກຕ້ອງ');
    }

    // ດຶງຂໍ້ມູນກ່ອນລົບເພື່ອເຮັດ Log ແລະ ອັບເດດສະຖານະສະມາຊິກ
    $sqlSub = "SELECT ms.*, mb.fname, mb.lname, mb.member_code, p.package_name 
               FROM memberships ms 
               LEFT JOIN members mb ON ms.member_id = mb.member_id 
               LEFT JOIN packages p ON ms.package_id = p.package_id 
               WHERE ms.membership_id = ? LIMIT 1";
    $stmtSub = mysqli_prepare($conn, $sqlSub);
    mysqli_stmt_bind_param($stmtSub, 'i', $membershipId);
    mysqli_stmt_execute($stmtSub);
    $resSub = mysqli_stmt_get_result($stmtSub);
    $sub = mysqli_fetch_assoc($resSub);
    mysqli_stmt_close($stmtSub);

    if (!$sub) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນການສະໝັກທີ່ຈະລົບ', 404);
    }

    $memberId = $sub['member_id'];
    $memName = $sub['fname'] . ' ' . $sub['lname'];
    $memCode = $sub['member_code'];
    $packageName = $sub['package_name'];

    // ລົບຂໍ້ມູນ
    $stmtDel = mysqli_prepare($conn, 'DELETE FROM memberships WHERE membership_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtDel, 'i', $membershipId);
    
    if (!mysqli_stmt_execute($stmtDel)) {
        jsonErr('ລົບການສະໝັກບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmtDel);

    // ກວດສອບເບິ່ງວ່າສະມາຊິກຄົນນີ້ຍັງມີແພັກເກດ Active ອື່ນຢູ່ບໍ່
    $checkActive = mysqli_query($conn, "SELECT COUNT(*) FROM memberships WHERE member_id = '$memberId' AND status = 'Active' AND end_date >= CURDATE()");
    $activeCount = mysqli_fetch_row($checkActive)[0];

    // ຖ້າບໍ່ມີແພັກເກດທີ່ຍັງໃຊ້ງານໄດ້, ໃຫ້ປ່ຽນສະຖານະສະມາຊິກເປັນ Expired
    if ($activeCount == 0) {
        mysqli_query($conn, "UPDATE members SET status = 'Expired' WHERE member_id = '$memberId'");
    }

    logActivity($pdo, "ລົບການສະໝັກແພັກເກດ", "ສະມາຊິກ: $memName (ລະຫັດ: $memCode), ແພັກເກດ: $packageName");

    jsonOk('ລົບຂໍ້ມູນການສະໝັກສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
