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
        // ຜູ້ບໍລິຫານມີສິດທຸກຢ່າງ
        if (isset($_SESSION['status']) && $_SESSION['status'] === 'ຜູ້ບໍລິຫານ') {
            return true;
        }
        
        // ຖ້າບໍ່ມີການເຂົ້າສູ່ລະບົບ ຫຼື ບໍ່ມີສິດທິທີ່ກຳນົດໄວ້
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        
        $perm_str = $_SESSION['permissions'];
        $perms = [];
        try {
            $parsed = json_decode($perm_str, true);
            if (is_array($parsed)) {
                // ໂຄງສ້າງແບບເກົ່າ (e.g. ["users", "assets"])
                if (isset($parsed[0])) {
                    foreach ($parsed as $mod) {
                        $perms[$mod] = ['view' => true, 'add' => true, 'edit' => true, 'delete' => true];
                    }
                } else {
                    // ໂຄງສ້າງແບບໃໝ່ (Object)
                    $perms = $parsed;
                }
            }
        } catch (Exception $e) {
            return false;
        }
        
        if (isset($perms[$module])) {
            return !empty($perms[$module][$action]);
        }
        
        return false;
    }
}

if (!function_exists('getPermissionLimit')) {
    function getPermissionLimit($module) {
        return 0; // Visibility limit feature removed, always return 0 (no limit)
    }
}

// ===== System Settings Initialization & Helper =====
if ($conn) {
    // Create system_settings table if it doesn't exist
    $createSettingsTable = "CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `gym_name` varchar(100) NOT NULL,
        `tel` varchar(50) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `logo_path` varchar(255) DEFAULT NULL,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($conn, $createSettingsTable);

    // Seed initial setting row if empty
    $checkSettings = mysqli_query($conn, "SELECT COUNT(*) FROM system_settings");
    $rowCount = 0;
    if ($checkSettings) {
        $rowCount = (int)mysqli_fetch_row($checkSettings)[0];
    }
    if ($rowCount === 0) {
        mysqli_query($conn, "INSERT INTO system_settings (gym_name, tel, address, logo_path) VALUES 
            ('GYM & FITNESS', '020 99999999', 'ບ້ານ ໂພນສະຫວ່າງ, ມ. ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ', '../assets/img/logo/gym_logo.png')");
    }
}

if (!function_exists('getSystemSettings')) {
    function getSystemSettings($conn) {
        $res = mysqli_query($conn, "SELECT * FROM system_settings LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return $row;
        }
        return [
            'gym_name' => 'GYM & FITNESS',
            'tel' => '020 99999999',
            'address' => 'ບ້ານ ໂພນສະຫວ່າງ, ມ. ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ',
            'logo_path' => '../assets/img/logo/gym_logo.png'
        ];
    }
}
?>