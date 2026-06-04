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

if ($action === 'get' && !hasPermission('users', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('users', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('users', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('users', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

function usernameExists($conn, $username, $excludeId = '')
{
    $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ss', $username, $excludeId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function validateUserInput($data, $isUpdate = false)
{
    $required = [
        'fname' => 'ກະລຸນາປ້ອນຊື່',
        'lname' => 'ກະລຸນາປ້ອນນາມສະກຸນ',
        'gender' => 'ກະລຸນາເລືອກເພດ',
        'dob' => 'ກະລຸນາປ້ອນວັນເດືອນປີເກີດ',
        'tel' => 'ກະລຸນາປ້ອນເບີໂທ',
        'address' => 'ກະລຸນາປ້ອນທີ່ຢູ່',
        'status' => 'ກະລຸນາເລືອກສະຖານະ',
        'username' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້',
    ];

    foreach ($required as $field => $msg) {
        if ($data[$field] === '') {
            jsonErr($msg);
        }
    }

    if (!in_array($data['status'], ['ຜູ້ບໍລິຫານ', 'ພະນັກງານ'], true)) {
        jsonErr('ສະຖານະບໍ່ຖືກຕ້ອງ');
    }

    if (!$isUpdate) {
        if ($data['password'] === '') {
            jsonErr('ກະລຸນາປ້ອນລະຫັດຜ່ານ');
        }
        if ($data['password'] !== $data['confirm_password']) {
            jsonErr('ລະຫັດຜ່ານບໍ່ຕົງກັນ');
        }
    } elseif ($data['password'] !== '' && $data['password'] !== $data['confirm_password']) {
        jsonErr('ລະຫັດຜ່ານບໍ່ຕົງກັນ');
    }
}

function buildPermissions($status, $permUsers)
{
    if ($status === 'ຜູ້ບໍລິຫານ') {
        return '[]';
    }
    $perms = [];
    if ($permUsers === '1') {
        $perms[] = 'users';
    }
    return json_encode($perms, JSON_UNESCAPED_UNICODE);
}

// ຟັງຊັນອັບໂຫຼດຮູບພາບໂປຣຟາຍ
function uploadProfileImage($fileInputName, $oldImage = '')
{
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null; // ບໍ່ມີການອັບໂຫຼດໄຟລ໌ ຫຼື ໄຟລ໌ມີບັນຫາ
    }

    $file = $_FILES[$fileInputName];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];

    // ດຶງນາມສະກຸນໄຟລ໌
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // ກວດສອບປະເພດໄຟລ໌
    if (!in_array($ext, $allowed, true)) {
        jsonErr('ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ (ອະນຸຍາດສະເພາະ .jpg, .jpeg, .png, .gif, .webp)');
    }

    // ກວດສອບຂະໜາດໄຟລ໌ (ບໍ່ໃຫ້ເກີນ 2MB)
    if ($fileSize > 2 * 1024 * 1024) {
        jsonErr('ຂະໜາດຮູບພາບຕ້ອງບໍ່ເກີນ 2MB');
    }

    // ຕັ້ງຊື່ໄຟລ໌ໃໝ່ໃຫ້ບໍ່ຊ້ຳກັນ
    $newFileName = 'user_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/img/users/';

    // ສ້າງໂຟນເດີ ຖ້າຍັງບໍ່ມີ
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $destPath = $uploadDir . $newFileName;

    // ຍ້າຍໄຟລ໌ໄປຍັງໂຟນເດີປາຍທາງ
    if (!move_uploaded_file($fileTmp, $destPath)) {
        jsonErr('ບໍ່ສາມາດອັບໂຫຼດຮູບພາບໄດ້');
    }

    // ຖ້າມີຮູບເກົ່າ ແລະ ບໍ່ແມ່ນຮູບ default, ໃຫ້ລົບຮູບເກົ່າຖິ້ມເພື່ອປະຢັດພື້ນທີ່
    if ($oldImage !== '' && $oldImage !== 'default.png') {
        $oldFilePath = $uploadDir . $oldImage;
        if (file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    return $newFileName;
}

// ==========================================
// ການກວດສອບ ແລະ ປະມວນຜົນແຕ່ລະ Action (ໃຊ້ IF-ELSE)
// ==========================================

// 1. ດຶງຂໍ້ມູນຜູ້ໃຊ້ງານ (Get User)
if ($action === 'get') {
    $userId = clean($conn, $_GET['user_id'] ?? '');
    if ($userId === '') {
        jsonErr('ລະຫັດຜູ້ໃຊ້ບໍ່ຖືກຕ້ອງ');
    }
    
    // ເພີ່ມ profile_img ເຂົ້າໄປໃນ SQL Select
    $stmt = mysqli_prepare($conn, 'SELECT user_id, fname, lname, gender, dob, tel, address, status, username, remark, permissions, profile_img FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$user) {
        jsonErr('ບໍ່ພົບຜູ້ໃຊ້', 404);
    }
    
    $perms = json_decode($user['permissions'] ?? '[]', true);
    $user['perm_users'] = is_array($perms) && in_array('users', $perms, true) ? '1' : '0';
    unset($user['permissions']);
    
    jsonOk('OK', ['user' => $user]);
}

// 2. ເພີ່ມຜູ້ໃຊ້ງານໃໝ່ (Create User)
if ($action === 'create') {
    $data = [
        'user_id' => clean($conn, $_POST['user_id'] ?? ''),
        'fname' => clean($conn, $_POST['fname'] ?? ''),
        'lname' => clean($conn, $_POST['lname'] ?? ''),
        'gender' => clean($conn, $_POST['gender'] ?? ''),
        'dob' => clean($conn, $_POST['dob'] ?? ''),
        'tel' => clean($conn, $_POST['tel'] ?? ''),
        'address' => clean($conn, $_POST['address'] ?? ''),
        'status' => clean($conn, $_POST['status'] ?? ''),
        'username' => clean($conn, $_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'remark' => clean($conn, $_POST['remark'] ?? ''),
    ];
    validateUserInput($data, false);

    if ($data['user_id'] === '') {
        jsonErr('ກະລຸນາປ້ອນລະຫັດຜູ້ນຳໃຊ້');
    }

    // ກວດສອບວ່າ ລະຫັດຜູ້ນຳໃຊ້ ຊ້ຳກັນບໍ່
    $stmtCheck = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 's', $data['user_id']);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $idExists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);
    if ($idExists) {
        jsonErr('ລະຫັດຜູ້ນຳໃຊ້ "' . $data['user_id'] . '" ມີໃນລະບົບແລ້ວ');
    }

    // ກວດສອບວ່າ ຊື່ເຂົ້າລະບົບ (username) ຊ້ຳກັນບໍ່
    if (usernameExists($conn, $data['username'])) {
        jsonErr('ຊື່ຜູ້ໃຊ້ "' . $data['username'] . '" ຖືກໃຊ້ແລ້ວ');
    }

    // ຈັດການການອັບໂຫຼດຮູບພາບ
    $profileImg = uploadProfileImage('profile_img');
    if ($profileImg === null) {
        $profileImg = 'default.png'; // ຖ້າບໍ່ມີການອັບໂຫຼດ, ໃຫ້ໃຊ້ຮູບເລີ່ມຕົ້ນ
    }

    $permissions = buildPermissions($data['status'], $_POST['perm_users'] ?? '0');
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO users (user_id, fname, lname, gender, dob, tel, address, status, username, password, remark, permissions, profile_img)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssssssss',
        $data['user_id'],
        $data['fname'],
        $data['lname'],
        $data['gender'],
        $data['dob'],
        $data['tel'],
        $data['address'],
        $data['status'],
        $data['username'],
        $hash,
        $data['remark'],
        $permissions,
        $profileImg
    );

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    jsonOk('ບັນທຶກຜູ້ໃຊ້ ' . $data['fname'] . ' ' . $data['lname'] . ' ສຳເລັດ');
}

// 3. ແກ້ໄຂຂໍ້ມູນຜູ້ໃຊ້ງານ (Update User)
if ($action === 'update') {
    $userId = clean($conn, $_POST['user_id'] ?? '');
    if ($userId === '') {
        jsonErr('ລະຫັດຜູ້ໃຊ້ບໍ່ຖືກຕ້ອງ');
    }

    $data = [
        'fname' => clean($conn, $_POST['fname'] ?? ''),
        'lname' => clean($conn, $_POST['lname'] ?? ''),
        'gender' => clean($conn, $_POST['gender'] ?? ''),
        'dob' => clean($conn, $_POST['dob'] ?? ''),
        'tel' => clean($conn, $_POST['tel'] ?? ''),
        'address' => clean($conn, $_POST['address'] ?? ''),
        'status' => clean($conn, $_POST['status'] ?? ''),
        'username' => clean($conn, $_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'remark' => clean($conn, $_POST['remark'] ?? ''),
    ];
    validateUserInput($data, true);

    if (usernameExists($conn, $data['username'], $userId)) {
        jsonErr('ຊື່ຜູ້ໃຊ້ "' . $data['username'] . '" ຖືກໃຊ້ແລ້ວ');
    }

    // ດຶງຂໍ້ມູນຮູບເກົ່າເພື່ອໄປກວດສອບໃນການລົບອອກ
    $stmtOld = mysqli_prepare($conn, 'SELECT profile_img FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtOld, 's', $userId);
    mysqli_stmt_execute($stmtOld);
    $resOld = mysqli_stmt_get_result($stmtOld);
    $oldUser = mysqli_fetch_assoc($resOld);
    mysqli_stmt_close($stmtOld);
    
    $oldImage = $oldUser ? $oldUser['profile_img'] : 'default.png';

    // ຈັດການການອັບໂຫຼດຮູບພາບໃໝ່ (ຖ້າມີການອັບໂຫຼດ ຈະລົບຮູບເກົ່າໃຫ້ອັດຕະໂນມັດ)
    $newProfileImg = uploadProfileImage('profile_img', $oldImage);

    $permissions = buildPermissions($data['status'], $_POST['perm_users'] ?? '0');

    if ($data['password'] !== '') {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        if ($newProfileImg !== null) {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE users SET fname=?, lname=?, gender=?, dob=?, tel=?, address=?, status=?, username=?, password=?, remark=?, permissions=?, profile_img=? WHERE user_id=?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssssss',
                $data['fname'],
                $data['lname'],
                $data['gender'],
                $data['dob'],
                $data['tel'],
                $data['address'],
                $data['status'],
                $data['username'],
                $hash,
                $data['remark'],
                $permissions,
                $newProfileImg,
                $userId
            );
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE users SET fname=?, lname=?, gender=?, dob=?, tel=?, address=?, status=?, username=?, password=?, remark=?, permissions=? WHERE user_id=?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssss',
                $data['fname'],
                $data['lname'],
                $data['gender'],
                $data['dob'],
                $data['tel'],
                $data['address'],
                $data['status'],
                $data['username'],
                $hash,
                $data['remark'],
                $permissions,
                $userId
            );
        }
    } else {
        if ($newProfileImg !== null) {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE users SET fname=?, lname=?, gender=?, dob=?, tel=?, address=?, status=?, username=?, remark=?, permissions=?, profile_img=? WHERE user_id=?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssss',
                $data['fname'],
                $data['lname'],
                $data['gender'],
                $data['dob'],
                $data['tel'],
                $data['address'],
                $data['status'],
                $data['username'],
                $data['remark'],
                $permissions,
                $newProfileImg,
                $userId
            );
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE users SET fname=?, lname=?, gender=?, dob=?, tel=?, address=?, status=?, username=?, remark=?, permissions=? WHERE user_id=?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssss',
                $data['fname'],
                $data['lname'],
                $data['gender'],
                $data['dob'],
                $data['tel'],
                $data['address'],
                $data['status'],
                $data['username'],
                $data['remark'],
                $permissions,
                $userId
            );
        }
    }

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    // ຖ້າຫາກເປັນການແກ້ໄຂຂໍ້ມູນຂອງຕົວເອງທີ່ກຳລັງ Login ຢູ່, ໃຫ້ອັບເດດ Session ພ້ອມ
    if ($userId === ($_SESSION['user_id'] ?? '')) {
        if ($newProfileImg !== null) {
            $_SESSION['profile_img'] = $newProfileImg;
        }
        $_SESSION['fname'] = $data['fname'];
        $_SESSION['lname'] = $data['lname'];
        $_SESSION['status'] = $data['status'];
    }

    jsonOk('ແກ້ໄຂຂໍ້ມູນສຳເລັດ');
}

// 4. ລົບຂໍ້ມູນຜູ້ໃຊ້ງານ (Delete User)
if ($action === 'delete') {
    $userId = clean($conn, $_POST['user_id'] ?? '');
    if ($userId === '') {
        jsonErr('ລະຫັດຜູ້ໃຊ້ບໍ່ຖືກຕ້ອງ');
    }
    if ($userId === ($_SESSION['user_id'] ?? '')) {
        jsonErr('ບໍ່ສາມາດລົບບັນຊີຂອງຕົນເອງໄດ້');
    }

    // ດຶງຊື່ໄຟລ໌ຮູບພາບເພື່ອລົບອອກຈາກໂຟນເດີ
    $stmtImg = mysqli_prepare($conn, 'SELECT profile_img FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtImg, 's', $userId);
    mysqli_stmt_execute($stmtImg);
    $resImg = mysqli_stmt_get_result($stmtImg);
    $userImgRow = mysqli_fetch_assoc($resImg);
    mysqli_stmt_close($stmtImg);
    
    if ($userImgRow) {
        $oldImg = $userImgRow['profile_img'];
        if ($oldImg !== 'default.png' && !empty($oldImg)) {
            $filePath = __DIR__ . '/../assets/img/users/' . $oldImg;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // ລົບຂໍ້ມູນອອກຈາກຖານຂໍ້ມູນ
    $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $userId);
    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        jsonErr('ບໍ່ພົບຜູ້ໃຊ້ທີ່ຈະລົບ', 404);
    }
    mysqli_stmt_close($stmt);
    
    jsonOk('ລົບຜູ້ໃຊ້ສຳເລັດ');
}

// ຖ້າບໍ່ມີ Action ໃດກົງກັນເລີຍ
jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ', 400);
