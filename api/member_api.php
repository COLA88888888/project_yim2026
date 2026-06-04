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
if ($action === 'get' && !hasPermission('members', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('members', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('members', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('members', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// ຟັງຊັນອັບໂຫຼດຮູບພາບສະມາຊິກ
function uploadMemberImage($fileInputName, $oldImage = '')
{
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fileInputName];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        jsonErr('ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ (ອະນຸຍາດສະເພາະ .jpg, .jpeg, .png, .gif, .webp)');
    }

    if ($fileSize > 2 * 1024 * 1024) {
        jsonErr('ຂະໜາດຮູບພາບຕ້ອງບໍ່ເກີນ 2MB');
    }

    $newFileName = 'member_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/img/members/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $destPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $destPath)) {
        jsonErr('ບໍ່ສາມາດອັບໂຫຼດຮູບພາບໄດ້');
    }

    if ($oldImage !== '' && $oldImage !== 'default.png') {
        $oldFilePath = $uploadDir . $oldImage;
        if (file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    return $newFileName;
}

// 1. ດຶງຂໍ້ມູນສະມາຊິກ (Get Member)
if ($action === 'get') {
    $memberId = clean($conn, $_GET['member_id'] ?? '');
    $memberCode = clean($conn, $_GET['member_code'] ?? '');
    
    if ($memberId !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT * FROM members WHERE member_id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $memberId);
    } elseif ($memberCode !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT * FROM members WHERE member_code = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $memberCode);
    } else {
        jsonErr('ລະຫັດສະມາຊິກບໍ່ຖືກຕ້ອງ');
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $member = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$member) {
        jsonErr('ບໍ່ພົບສະມາຊິກ', 404);
    }
    
    // ດຶງຂໍ້ມູນການສະໝັກຫຼ້າສຸດ (ຖ້າມີ)
    $subSql = "SELECT m.*, p.package_name, p.duration_days, p.price 
               FROM memberships m 
               LEFT JOIN packages p ON m.package_id = p.package_id 
               WHERE m.member_id = ? 
               ORDER BY m.end_date DESC LIMIT 1";
    $stmtSub = mysqli_prepare($conn, $subSql);
    mysqli_stmt_bind_param($stmtSub, 'i', $member['member_id']);
    mysqli_stmt_execute($stmtSub);
    $resSub = mysqli_stmt_get_result($stmtSub);
    $sub = mysqli_fetch_assoc($resSub);
    mysqli_stmt_close($stmtSub);

    $member['active_subscription'] = $sub;
    
    jsonOk('OK', ['member' => $member]);
}

// 2. ເພີ່ມສະມາຊິກໃໝ່ (Create Member)
if ($action === 'create') {
    $memberCode = clean($conn, $_POST['member_code'] ?? '');
    $fname = clean($conn, $_POST['fname'] ?? '');
    $lname = clean($conn, $_POST['lname'] ?? '');
    $gender = clean($conn, $_POST['gender'] ?? '');
    $dob = clean($conn, $_POST['dob'] ?? '');
    $tel = clean($conn, $_POST['tel'] ?? '');
    $address = clean($conn, $_POST['address'] ?? '');
    $status = clean($conn, $_POST['status'] ?? 'Active');

    if ($fname === '' || $lname === '') {
        jsonErr('ກະລຸນາປ້ອນຊື່ ແລະ ນາມສະກຸນ');
    }

    // ຖ້າບໍ່ມີ member_code, ໃຫ້ສ້າງອັດຕະໂນມັດ (GYM + ປີ/ເດືອນ + 4 ຫຼັກແບບສຸ່ມ)
    if ($memberCode === '') {
        $memberCode = 'GYM' . date('ym') . sprintf('%04d', rand(1, 9999));
    }

    // ກວດສອບລະຫັດບັດສະມາຊິກຊ້ຳ
    $stmtCheck = mysqli_prepare($conn, 'SELECT member_id FROM members WHERE member_code = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 's', $memberCode);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);

    if ($exists) {
        jsonErr('ລະຫັດສະມາຊິກ "' . $memberCode . '" ນີ້ມີໃນລະບົບແລ້ວ');
    }

    // ອັບໂຫຼດຮູບພາບ
    $profileImg = uploadMemberImage('profile_img');
    if ($profileImg === null) {
        $profileImg = 'default.png';
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO members (member_code, fname, lname, gender, dob, tel, address, profile_img, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    
    // Check if dob is empty, set to NULL if so
    $dob_param = ($dob === '') ? null : $dob;
    
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssss',
        $memberCode,
        $fname,
        $lname,
        $gender,
        $dob_param,
        $tel,
        $address,
        $profileImg,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ບັນທຶກສະມາຊິກບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    
    $newMemberId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ເພີ່ມສະມາຊິກ", "ຊື່: $fname $lname, ລະຫັດ: $memberCode");
    
    jsonOk('ເພີ່ມສະມາຊິກສຳເລັດ', ['member_id' => $newMemberId, 'member_code' => $memberCode]);
}

// 3. ແກ້ໄຂຂໍ້ມູນສະມາຊິກ (Update Member)
if ($action === 'update') {
    $memberId = clean($conn, $_POST['member_id'] ?? '');
    $memberCode = clean($conn, $_POST['member_code'] ?? '');
    $fname = clean($conn, $_POST['fname'] ?? '');
    $lname = clean($conn, $_POST['lname'] ?? '');
    $gender = clean($conn, $_POST['gender'] ?? '');
    $dob = clean($conn, $_POST['dob'] ?? '');
    $tel = clean($conn, $_POST['tel'] ?? '');
    $address = clean($conn, $_POST['address'] ?? '');
    $status = clean($conn, $_POST['status'] ?? 'Active');

    if ($memberId === '') {
        jsonErr('ລະຫັດສະມາຊິກບໍ່ຖືກຕ້ອງ');
    }
    if ($fname === '' || $lname === '') {
        jsonErr('ກະລຸນາປ້ອນຊື່ ແລະ ນາມສະກຸນ');
    }

    // ກວດສອບລະຫັດບັດສະມາຊິກຊ້ຳ (ຍົກເວັ້ນ id ຕົວເອງ)
    $stmtCheck = mysqli_prepare($conn, 'SELECT member_id FROM members WHERE member_code = ? AND member_id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 'si', $memberCode, $memberId);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);

    if ($exists) {
        jsonErr('ລະຫັດສະມາຊິກ "' . $memberCode . '" ນີ້ມີໃນລະບົບແລ້ວ');
    }

    // ດຶງຮູບເກົ່າ
    $stmtOld = mysqli_prepare($conn, 'SELECT profile_img FROM members WHERE member_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtOld, 'i', $memberId);
    mysqli_stmt_execute($stmtOld);
    $resOld = mysqli_stmt_get_result($stmtOld);
    $oldMember = mysqli_fetch_assoc($resOld);
    mysqli_stmt_close($stmtOld);
    
    $oldImage = $oldMember ? $oldMember['profile_img'] : 'default.png';

    // ອັບໂຫຼດຮູບໃໝ່
    $newProfileImg = uploadMemberImage('profile_img', $oldImage);

    // SQL dynamically based on image update
    $dob_param = ($dob === '') ? null : $dob;
    
    if ($newProfileImg !== null) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE members SET member_code=?, fname=?, lname=?, gender=?, dob=?, tel=?, address=?, status=?, profile_img=? WHERE member_id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssi',
            $memberCode,
            $fname,
            $lname,
            $gender,
            $dob_param,
            $tel,
            $address,
            $status,
            $newProfileImg,
            $memberId
        );
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE members SET member_code=?, fname=?, lname=?, gender=?, dob=?, tel=?, address=?, status=? WHERE member_id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssi',
            $memberCode,
            $fname,
            $lname,
            $gender,
            $dob_param,
            $tel,
            $address,
            $status,
            $memberId
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂສະມາຊິກບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);
    
    logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນສະມາຊິກ", "ຊື່: $fname $lname, ລະຫັດ: $memberCode");

    jsonOk('ແກ້ໄຂຂໍ້ມູນສະມາຊິກສຳເລັດ');
}

// 4. ລົບສະມາຊິກ (Delete Member)
if ($action === 'delete') {
    $memberId = clean($conn, $_POST['member_id'] ?? '');
    
    if ($memberId === '') {
        jsonErr('ລະຫັດສະມາຊິກບໍ່ຖືກຕ້ອງ');
    }

    // ດຶງຊື່ໄຟລ໌ຮູບພາບເພື່ອລົບອອກຈາກໂຟນເດີ
    $stmtImg = mysqli_prepare($conn, 'SELECT profile_img, fname, lname, member_code FROM members WHERE member_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtImg, 'i', $memberId);
    mysqli_stmt_execute($stmtImg);
    $resImg = mysqli_stmt_get_result($stmtImg);
    $row = mysqli_fetch_assoc($resImg);
    mysqli_stmt_close($stmtImg);
    
    if ($row) {
        $oldImg = $row['profile_img'];
        if ($oldImg !== 'default.png' && !empty($oldImg)) {
            $filePath = __DIR__ . '/../assets/img/members/' . $oldImg;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // ລົບຂໍ້ມູນການສະໝັກ (Memberships) ຂອງສະມາຊິກນີ້
        mysqli_query($conn, "DELETE FROM memberships WHERE member_id = '$memberId'");
        // ລົບປະຫວັດການເຊັກອິນ
        mysqli_query($conn, "DELETE FROM checkins WHERE member_id = '$memberId'");

        // ລົບສະມາຊິກ
        $stmt = mysqli_prepare($conn, 'DELETE FROM members WHERE member_id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $memberId);
        
        if (!mysqli_stmt_execute($stmt)) {
            jsonErr('ລົບສະມາຊິກບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
        }
        mysqli_stmt_close($stmt);
        
        logActivity($pdo, "ລົບສະມາຊິກ", "ຊື່: {$row['fname']} {$row['lname']}, ລະຫັດ: {$row['member_code']}");
        
        jsonOk('ລົບສະມາຊິກສຳເລັດ');
    } else {
        jsonErr('ບໍ່ພົບສະມາຊິກທີ່ຈະລົບ', 404);
    }
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
