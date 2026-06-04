<?php
session_start();
require_once 'config/db.php';

// Log activity before destroying session
if (isset($_SESSION['fname'])) {
    $user_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
    logActivity($pdo, "ອອກຈາກລະບົບ", "ຜູ້ໃຊ້: " . $user_name);
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logging out...</title>
    <script>
        // Redirect top level window to prevent index.php from opening inside an iframe
        if (window.top) {
            window.top.location.href = 'index.php';
        } else {
            window.location.href = 'index.php';
        }
    </script>
</head>
<body>
    <p>ກຳລັງອອກຈາກລະບົບ...</p>
</body>
</html>

