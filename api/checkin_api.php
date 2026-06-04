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
if (!hasPermission('checkin', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ກວດສອບສະຖານະສະມາຊິກ (Verify member subscription)
if ($action === 'verify') {
    $search = clean($conn, $_GET['search'] ?? '');

    if ($search === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດສະມາຊິກ ຫຼື ເບີໂທລະສັບ');
    }

    // ຄົ້ນຫາສະມາຊິກ ໂດຍໃຊ້ລະຫັດສະມາຊິກ ຫຼື ເບີໂທລະສັບ
    $sql = "SELECT * FROM members WHERE member_code = ? OR tel = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $search, $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $member = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$member) {
        jsonErr('ບໍ່ພົບສະມາຊິກໃນລະບົບ');
    }

    $memberId = $member['member_id'];

    // ດຶງຂໍ້ມູນການສະໝັກຫຼ້າສຸດ
    $subSql = "SELECT ms.*, p.package_name, p.duration_days, p.price 
               FROM memberships ms 
               LEFT JOIN packages p ON ms.package_id = p.package_id 
               WHERE ms.member_id = ? 
               ORDER BY ms.end_date DESC LIMIT 1";
    $stmtSub = mysqli_prepare($conn, $subSql);
    mysqli_stmt_bind_param($stmtSub, 'i', $memberId);
    mysqli_stmt_execute($stmtSub);
    $resSub = mysqli_stmt_get_result($stmtSub);
    $sub = mysqli_fetch_assoc($resSub);
    mysqli_stmt_close($stmtSub);

    $is_active = false;
    $remaining_days = 0;
    $status_msg = "Expired / ຍັງບໍ່ໄດ້ສະໝັກແພັກເກດ";
    $status_color = "danger";

    if ($sub) {
        $today = date('Y-m-d');
        $endDate = $sub['end_date'];
        
        if ($endDate >= $today && $sub['status'] === 'Active') {
            $is_active = true;
            $status_msg = "Active / ປົກກະຕິ";
            $status_color = "success";
            
            // ຄຳນວນມື້ທີ່ເຫຼືອ
            $diff = strtotime($endDate) - strtotime($today);
            $remaining_days = round($diff / (60 * 60 * 24));
            if ($remaining_days < 0) $remaining_days = 0;
        } else {
            $status_msg = "Expired / ແພັກເກດໝົດອາຍຸ";
        }
    }

    // ກວດສອບການເຊັກອິນມື້ນີ້
    $checkinTodaySql = "SELECT COUNT(*) FROM checkins WHERE member_id = ? AND DATE(checkin_time) = CURDATE()";
    $stmtCh = mysqli_prepare($conn, $checkinTodaySql);
    mysqli_stmt_bind_param($stmtCh, 'i', $memberId);
    mysqli_stmt_execute($stmtCh);
    $resCh = mysqli_stmt_get_result($stmtCh);
    $chRow = mysqli_fetch_row($resCh);
    $checked_in_today = ($chRow && $chRow[0] > 0);
    mysqli_stmt_close($stmtCh);

    jsonOk('ກວດສອບຂໍ້ມູນສຳເລັດ', [
        'member' => $member,
        'subscription' => $sub,
        'is_active' => $is_active,
        'remaining_days' => $remaining_days,
        'status_msg' => $status_msg,
        'status_color' => $status_color,
        'checked_in_today' => $checked_in_today
    ]);
}

// 2. ບັນທຶກການເຊັກອິນ (Perform checkin)
if ($action === 'checkin') {
    $memberId = clean($conn, $_POST['member_id'] ?? '');

    if ($memberId === '') {
        jsonErr('ລະຫັດສະມາຊິກບໍ່ຖືກຕ້ອງ');
    }

    // ດຶງຂໍ້ມູນສະມາຊິກ ແລະ ການສະໝັກເພື່ອທຳການກວດສອບອີກຄັ້ງກ່ອນເຊັກອິນ
    $resMem = mysqli_query($conn, "SELECT * FROM members WHERE member_id = '$memberId' LIMIT 1");
    $member = mysqli_fetch_assoc($resMem);

    if (!$member) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນສະມາຊິກ');
    }

    // ດຶງຂໍ້ມູນການສະໝັກຫຼ້າສຸດ
    $subSql = "SELECT * FROM memberships WHERE member_id = ? ORDER BY end_date DESC LIMIT 1";
    $stmtSub = mysqli_prepare($conn, $subSql);
    mysqli_stmt_bind_param($stmtSub, 'i', $memberId);
    mysqli_stmt_execute($stmtSub);
    $resSub = mysqli_stmt_get_result($stmtSub);
    $sub = mysqli_fetch_assoc($resSub);
    mysqli_stmt_close($stmtSub);

    $is_active = false;
    if ($sub) {
        $today = date('Y-m-d');
        if ($sub['end_date'] >= $today && $sub['status'] === 'Active') {
            $is_active = true;
        }
    }

    if (!$is_active) {
        jsonErr('ບໍ່ສາມາດເຊັກອິນໄດ້ ເພາະສະມາຊິກຄົນນີ້ບໍ່ມີແພັກເກດທີ່ຍັງໃຊ້ງານໄດ້ ຫຼື ໝົດອາຍຸແລ້ວ');
    }

    // ບັນທຶກລົງຕາຕະລາງ checkins
    $insSql = "INSERT INTO checkins (member_id, checkin_time) VALUES (?, NOW())";
    $stmtIns = mysqli_prepare($conn, $insSql);
    mysqli_stmt_bind_param($stmtIns, 'i', $memberId);
    
    if (!mysqli_stmt_execute($stmtIns)) {
        jsonErr('ເຊັກອິນບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmtIns);

    logActivity($pdo, "ເຊັກອິນເຂົ້າໃຊ້ບໍລິການ", "ສະມາຊິກ: {$member['fname']} {$member['lname']} (ລະຫັດ: {$member['member_code']})");

    jsonOk('ເຊັກອິນເຂົ້າໃຊ້ບໍລິການສຳເລັດແລ້ວ', [
        'checkin_time' => date('Y-m-d H:i:s'),
        'member_name' => $member['fname'] . ' ' . $member['lname']
    ]);
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
