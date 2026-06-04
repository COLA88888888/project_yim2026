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

$today = date('Y-m-d');
$notifications = [];

// 1. Fetch membership expiration notifications
$sql = "SELECT ms.*, mb.fname, mb.lname, mb.member_code, p.package_name, DATEDIFF(ms.end_date, ?) AS days_left
        FROM memberships ms
        JOIN members mb ON ms.member_id = mb.member_id
        JOIN packages p ON ms.package_id = p.package_id
        WHERE (
            (ms.end_date >= ? AND ms.end_date <= DATE_ADD(?, INTERVAL 3 DAY))
            OR
            (ms.end_date < ? AND ms.end_date >= DATE_SUB(?, INTERVAL 7 DAY))
        )
        AND ms.membership_id = (
            SELECT MAX(membership_id)
            FROM memberships
            WHERE member_id = ms.member_id
        )
        ORDER BY ms.end_date ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'sssss', $today, $today, $today, $today, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = [
            'type' => 'membership',
            'membership_id' => (int)$row['membership_id'],
            'member_id' => (int)$row['member_id'],
            'member_code' => $row['member_code'],
            'fname' => $row['fname'],
            'lname' => $row['lname'],
            'package_name' => $row['package_name'],
            'end_date' => $row['end_date'],
            'days_left' => (int)$row['days_left']
        ];
    }
    mysqli_stmt_close($stmt);
}

// 2. Fetch low stock products (quantity > 0 AND quantity <= 10)
$sql_low_stock = "SELECT product_id, product_name, product_code, quantity FROM products WHERE quantity > 0 AND quantity <= 10 ORDER BY quantity ASC";
$res_low_stock = mysqli_query($conn, $sql_low_stock);
if ($res_low_stock) {
    while ($row = mysqli_fetch_assoc($res_low_stock)) {
        $notifications[] = [
            'type' => 'low_stock',
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['product_name'],
            'product_code' => $row['product_code'],
            'quantity' => (int)$row['quantity']
        ];
    }
}

// 3. Fetch out of stock products (quantity = 0)
$sql_out_of_stock = "SELECT product_id, product_name, product_code, quantity FROM products WHERE quantity = 0 ORDER BY product_name ASC";
$res_out_of_stock = mysqli_query($conn, $sql_out_of_stock);
if ($res_out_of_stock) {
    while ($row = mysqli_fetch_assoc($res_out_of_stock)) {
        $notifications[] = [
            'type' => 'out_of_stock',
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['product_name'],
            'product_code' => $row['product_code'],
            'quantity' => (int)$row['quantity']
        ];
    }
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
exit;
?>
