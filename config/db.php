<?php
$server = "localhost";
$username = "root";
$password = "";
$database = "db_gym2026";

$conn = mysqli_connect($server, $username, $password, $database);
mysqli_set_charset($conn, "utf8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($conn && isset($_SESSION['user_id'])) {
    $session_user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    // ຄຳສັ່ງ SQL: ເລືອກສະຖານະ (status) ແລະ ສິດການໃຊ້ງານ (permissions) ຈາກຕາຕະລາງ users ຂອງຜູ້ໃຊ້ຄົນນັ້ນ ເພື່ອມາອັບເດດ Session
    $refresh_sql = "SELECT status, permissions FROM users WHERE user_id = '$session_user_id' LIMIT 1";
    $refresh_result = mysqli_query($conn, $refresh_sql);
    if ($refresh_result && $refresh_row = mysqli_fetch_assoc($refresh_result)) {
        $_SESSION['status'] = $refresh_row['status'];
        $_SESSION['permissions'] = $refresh_row['permissions'];
    }
}

try {
    $pdo = new PDO(
        "mysql:host={$server};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    $pdo = null;
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        return number_format((float)$amount, 0, '.', ',') . ' ກີບ';
    }
}

if (!function_exists('logActivity')) {
    function logActivity($pdo, $action, $detail = '')
    {
        if (!$pdo) {
            return;
        }
        try {
            // ຄຳສັ່ງ SQL: ບັນທຶກປະຫວັດການເຮັດວຽກຂອງຜູ້ໃຊ້ (ເຊັ່ນ: ການເຂົ້າສູ່ລະບົບ, ການເພີ່ມ/ລົບ/ແກ້ໄຂຂໍ້ມູນ) ລົງໃນຕາຕະລາງ activity_logs
            $stmt = $pdo->prepare('INSERT INTO activity_logs (action, detail, created_at) VALUES (?, ?, NOW())');
            $stmt->execute([$action, $detail]);
        } catch (Exception $e) {
            // activity_logs table may not exist in all deployments
        }
    }
}

// === Permission Helper Functions ===
if (!function_exists('hasPermission')) {
    function hasPermission($module, $action = 'view') {
        return true; // Bypass all permissions, grant full access
    }
}

if (!function_exists('getPermissionLimit')) {
    function getPermissionLimit($module) {
        return 0; // Visibility limit feature removed, always return 0 (no limit)
    }
}
?>