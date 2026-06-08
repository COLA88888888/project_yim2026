<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ບໍ່ມີສິດເຂົ້າເຖິງ']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonOk($message, $extra = []) {
    echo json_encode(array_merge(['success' => true, 'message' => $message], $extra));
    exit;
}
function jsonErr($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
function clean($value) {
    return trim($value ?? '');
}

// Permission checks
if ($action === 'create' && !hasPermission('daily_checkin', 'add')) jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
if ($action === 'delete' && !hasPermission('daily_checkin', 'delete')) jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);

// 1. Create daily check-in
if ($action === 'create') {
    $gender         = clean($_POST['gender'] ?? '');
    $price_paid     = str_replace(',', '', clean($_POST['price_paid'] ?? '0'));
    $payment_method = clean($_POST['payment_method'] ?? 'ເງິນສົດ');
    $checkin_date   = clean($_POST['checkin_date'] ?? date('Y-m-d'));

    if ($gender === '') jsonErr('ກະລຸນາເລືອກເພດ');
    if (!is_numeric($price_paid) || (float)$price_paid < 0) jsonErr('ກະລຸນາປ້ອນລາຄາທີ່ຖືກຕ້ອງ');

    $userId = $_SESSION['user_id'] ?? 'U001';
    $stmt = mysqli_prepare($conn,
        'INSERT INTO daily_checkins (gender, price_paid, payment_method, checkin_date, user_id) VALUES (?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'sdsss', $gender, $price_paid, $payment_method, $checkin_date, $userId);
    if (!mysqli_stmt_execute($stmt)) jsonErr('ບັນທຶກບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $userRes = mysqli_query($conn, "SELECT fname, lname FROM users WHERE user_id = '$userId' LIMIT 1");
    $userRow = mysqli_fetch_assoc($userRes);
    $staffName = $userRow ? ($userRow['fname'] . ' ' . $userRow['lname']) : 'Admin';

    logActivity($pdo, 'ເຊັກອິນລາຍວັນ', "ເພດ: $gender, ລາຄາ: $price_paid, ຊຳລະ: $payment_method, ວັນທີ: $checkin_date");

    jsonOk('ບັນທຶກການເຊັກອິນສຳເລັດ', ['id' => $newId, 'staff_name' => $staffName]);
}

// 2. Delete daily check-in
if ($action === 'delete') {
    $id = (int)clean($_POST['id'] ?? '');
    if ($id <= 0) jsonErr('ID ບໍ່ຖືກຕ້ອງ');

    $stmt = mysqli_prepare($conn, 'DELETE FROM daily_checkins WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) jsonErr('ລົບບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    mysqli_stmt_close($stmt);

    jsonOk('ລົບຂໍ້ມູນສຳເລັດ');
}

// 3. Get stats for a given date (default today)
if ($action === 'get_stats') {
    $date = clean($_GET['date'] ?? date('Y-m-d'));

    $statsRes = mysqli_query($conn,
        "SELECT
            COALESCE(SUM(price_paid), 0) AS total_revenue,
            SUM(CASE WHEN gender = 'ຊາຍ' THEN 1 ELSE 0 END) AS male_count,
            SUM(CASE WHEN gender = 'ຍິງ' THEN 1 ELSE 0 END) AS female_count,
            COUNT(*) AS total_count
         FROM daily_checkins
         WHERE checkin_date = '$date'"
    );
    $stats = mysqli_fetch_assoc($statsRes);
    jsonOk('OK', ['stats' => $stats]);
}

// 4. List daily check-ins for a given date (default today)
if ($action === 'list') {
    $date = clean($_GET['date'] ?? date('Y-m-d'));

    $rows = [];
    $res = mysqli_query($conn,
        "SELECT * FROM daily_checkins WHERE checkin_date = '$date' ORDER BY created_at DESC, id DESC"
    );
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    }
    jsonOk('OK', ['rows' => $rows]);
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
