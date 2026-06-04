<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

// Check permissions / Session
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

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. Get Single Expense
if ($action === 'get') {
    $expenseId = clean($conn, $_GET['expense_id'] ?? '');
    
    if ($expenseId === '') {
        jsonErr('ລະຫັດລາຍຈ່າຍບໍ່ຖືກຕ້ອງ');
    }
    
    $stmt = mysqli_prepare($conn, 'SELECT e.*, u.fname, u.lname FROM expenses e LEFT JOIN users u ON e.user_id = u.user_id WHERE e.expense_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $expenseId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $expense = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$expense) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນລາຍຈ່າຍ', 404);
    }
    
    jsonOk('OK', ['expense' => $expense]);
}

// 2. List Expenses (with date filters)
if ($action === 'list') {
    $startDate = clean($conn, $_GET['start_date'] ?? '');
    $endDate = clean($conn, $_GET['end_date'] ?? '');
    
    $where = "WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($startDate !== '') {
        $where .= " AND e.expense_date >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if ($endDate !== '') {
        $where .= " AND e.expense_date <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    $sql = "SELECT e.*, u.fname, u.lname 
            FROM expenses e 
            LEFT JOIN users u ON e.user_id = u.user_id 
            $where 
            ORDER BY e.expense_date DESC, e.expense_id DESC";
            
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $expenses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $expenses[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    jsonOk('OK', ['expenses' => $expenses]);
}

// 3. Create Expense
if ($action === 'create') {
    $title       = clean($conn, $_POST['title'] ?? '');
    $category    = clean($conn, $_POST['category'] ?? '');
    $amount      = clean($conn, $_POST['amount'] ?? '');
    $expenseDate = clean($conn, $_POST['expense_date'] ?? '');
    $notes       = clean($conn, $_POST['notes'] ?? '');
    $userId      = $_SESSION['user_id'];

    if ($title === '') {
        jsonErr('ກະລຸນາປ້ອນຫົວຂໍ້ລາຍຈ່າຍ');
    }
    if ($category === '') {
        jsonErr('ກະລຸນາເລືອກປະເພດລາຍຈ່າຍ');
    }
    if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
        jsonErr('ກະລຸນາປ້ອນຈຳນວນເງິນໃຫ້ຖືກຕ້ອງ');
    }
    if ($expenseDate === '') {
        jsonErr('ກະລຸນາເລືອກວັນທີລາຍຈ່າຍ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO expenses (title, category, amount, expense_date, notes, user_id) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $amountFloat = (float)$amount;
    mysqli_stmt_bind_param($stmt, 'ssdsss', $title, $category, $amountFloat, $expenseDate, $notes, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກລາຍຈ່າຍບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ເພີ່ມລາຍຈ່າຍ", "ຫົວຂໍ້: $title, ປະເພດ: $category, ຈຳນວນ: " . number_format($amountFloat) . " ກີບ");

    jsonOk('ເພີ່ມລາຍຈ່າຍສຳເລັດ');
}

// 4. Update Expense
if ($action === 'update') {
    $expenseId   = clean($conn, $_POST['expense_id'] ?? '');
    $title       = clean($conn, $_POST['title'] ?? '');
    $category    = clean($conn, $_POST['category'] ?? '');
    $amount      = clean($conn, $_POST['amount'] ?? '');
    $expenseDate = clean($conn, $_POST['expense_date'] ?? '');
    $notes       = clean($conn, $_POST['notes'] ?? '');

    if ($expenseId === '') {
        jsonErr('ລະຫັດລາຍຈ່າຍບໍ່ຖືກຕ້ອງ');
    }
    if ($title === '') {
        jsonErr('ກະລຸນາປ້ອນຫົວຂໍ້ລາຍຈ່າຍ');
    }
    if ($category === '') {
        jsonErr('ກະລຸນາເລືອກປະເພດລາຍຈ່າຍ');
    }
    if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
        jsonErr('ກະລຸນາປ້ອນຈຳນວນເງິນໃຫ້ຖືກຕ້ອງ');
    }
    if ($expenseDate === '') {
        jsonErr('ກະລຸນາເລືອກວັນທີລາຍຈ່າຍ');
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE expenses SET title=?, category=?, amount=?, expense_date=?, notes=? WHERE expense_id=?'
    );
    $amountFloat = (float)$amount;
    mysqli_stmt_bind_param($stmt, 'ssdssi', $title, $category, $amountFloat, $expenseDate, $notes, $expenseId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂລາຍຈ່າຍບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ແກ້ໄຂລາຍຈ່າຍ", "ລະຫັດ: $expenseId, ຫົວຂໍ້: $title, ຈຳນວນ: " . number_format($amountFloat) . " ກີບ");

    jsonOk('ແກ້ໄຂລາຍຈ່າຍສຳເລັດ');
}

// 5. Delete Expense
if ($action === 'delete') {
    $expenseId = clean($conn, $_POST['expense_id'] ?? '');

    if ($expenseId === '') {
        jsonErr('ລະຫັດລາຍຈ່າຍບໍ່ຖືກຕ້ອງ');
    }

    // Get details for activity log
    $stmtDetails = mysqli_prepare($conn, 'SELECT title, amount FROM expenses WHERE expense_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtDetails, 'i', $expenseId);
    mysqli_stmt_execute($stmtDetails);
    $resDetails = mysqli_stmt_get_result($stmtDetails);
    $expRow = mysqli_fetch_assoc($resDetails);
    mysqli_stmt_close($stmtDetails);

    $title = $expRow ? $expRow['title'] : $expenseId;
    $amountVal = $expRow ? (float)$expRow['amount'] : 0;

    $stmt = mysqli_prepare($conn, 'DELETE FROM expenses WHERE expense_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $expenseId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບລາຍຈ່າຍບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ລົບລາຍຈ່າຍ", "ຫົວຂໍ້: $title, ຈຳນວນ: " . number_format($amountVal) . " ກີບ");

    jsonOk('ລົບລາຍຈ່າຍສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
