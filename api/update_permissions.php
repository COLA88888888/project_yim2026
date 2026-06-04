<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id']) || $_SESSION['status'] !== 'ຜູ້ບໍລິຫານ') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ບໍ່ມີສິດເຂົ້າເຖິງ']);
    exit();
}
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_POST['user_id'] ?? '');
$permissions = $_POST['permissions'] ?? '[]';

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາລະບຸ user_id']);
    exit();
}

// ກວດສອບວ່າ permissions ຖືກຕ້ອງ
$perms_array = json_decode($permissions, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມນ permissions ບໍ່ືກຕ້ອງ']);
    exit();
}

// ອັບເດດ permissions ໃນຖານຂໍ້ມູນ
$sql = "UPDATE users SET permissions = ? WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $permissions, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true, 
            'message' => 'ບັນທຶກສິດສຳເລັດ',
            'user_id' => $user_id,
            'permissions' => $perms_array
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດບັນທຶກໄດ້: ' . mysqli_error($conn)]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>
