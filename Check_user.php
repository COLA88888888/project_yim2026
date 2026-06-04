<?php
header('Content-Type: application/json');
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ']);
    exit();
}

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດເຊື່ອມຕໍ່ຖານຂໍ້ມູນໄດ້ (Database connection failed)']);
    exit();
}

try {
    // 1. First, find the user by username
    // ຄຳສັ່ງ SQL: ດຶງຂໍ້ມູນຜູ້ໃຊ້ທັງໝົດຈາກຕາຕະລາງ users ໂດຍຄົ້ນຫາຈາກ ຊື່ຜູ້ໃຊ້ (username) ທີ່ປ້ອນເຂົ້າມາ
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Check password using password_verify (for $2y$ hashes)
        $password_matches = false;
        
        if (password_verify($password, $user['password'])) {
            $password_matches = true;
        } 
        // 3. Fallback: Check if it's legacy hashes or plain text
        else {
            // ຄຳສັ່ງ SQL: ກວດສອບຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ ໂດຍໃຊ້ຟັງຊັນການເຂົ້າລະຫັດ PASSWORD() ແບບເກົ່າຂອງ MySQL
            $stmtLegacy = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = PASSWORD(?)");
            $stmtLegacy->execute([$username, $password]);
            if ($stmtLegacy->fetch()) {
                $password_matches = true;
            }
            else if ($password === $user['password']) {
                $password_matches = true;
            }
        }

        if ($password_matches) {
            $_SESSION['checked'] = 1;
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['status'] = $user['status'];
            $_SESSION['permissions'] = $user['permissions'];
            $_SESSION['profile_img'] = $user['profile_img'];

            $redirect = 'menu_admin.php';
            
            logActivity($pdo, "ເຂົ້າສູ່ລະບົບ", "ຊື່ຜູ້ໃຊ້: $username");

            echo json_encode([
                'success' => true, 
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຊື່ຜູ້ໃຊ້ນີ້ໃນລະບົບ']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
