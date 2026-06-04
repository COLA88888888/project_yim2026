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
if ($action === 'get' && !hasPermission('equipment', 'view')) {
    jsonErr('ບໍ່ມີສິດເຂົ້າເຖິງ', 403);
}
if ($action === 'create' && !hasPermission('equipment', 'add')) {
    jsonErr('ບໍ່ມີສິດເພີ່ມຂໍ້ມູນ', 403);
}
if ($action === 'update' && !hasPermission('equipment', 'edit')) {
    jsonErr('ບໍ່ມີສິດແກ້ໄຂຂໍ້ມູນ', 403);
}
if ($action === 'delete' && !hasPermission('equipment', 'delete')) {
    jsonErr('ບໍ່ມີສິດລົບຂໍ້ມູນ', 403);
}

function clean($conn, $value)
{
    return trim($value ?? '');
}

// 1. ດຶງຂໍ້ມູນອຸປະກອນ (Get Equipment)
if ($action === 'get') {
    $equipmentId = clean($conn, $_GET['equipment_id'] ?? '');

    if ($equipmentId === '') {
        jsonErr('ລະຫັດອຸປະກອນບໍ່ຖືກຕ້ອງ');
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM equipment WHERE equipment_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $equipmentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $eq = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$eq) {
        jsonErr('ບໍ່ພົບຂໍ້ມູນອຸປະກອນ', 404);
    }

    jsonOk('OK', ['equipment' => $eq]);
}

// Helper function to handle equipment image upload
function uploadEquipmentImage()
{
    if (!isset($_FILES['equipment_img']) || $_FILES['equipment_img']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $fileTmpPath = $_FILES['equipment_img']['tmp_name'];
    $fileName = $_FILES['equipment_img']['name'];
    $fileSize = $_FILES['equipment_img']['size'];
    
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        jsonErr('ນາມສະກຸນໄຟລ໌ຮູບບໍ່ຖືກຕ້ອງ (ອະນຸຍາດສະເພາະ JPG, PNG, WEBP, GIF)');
    }
    
    // Limit to 5MB
    if ($fileSize > 5 * 1024 * 1024) {
        jsonErr('ຂະໜາດໄຟລ໌ຮູບໃຫຍ່ເກີນໄປ (ອະນຸຍາດບໍ່ເກີນ 5MB)');
    }
    
    $newFileName = 'eq_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
    $uploadFileDir = __DIR__ . '/../assets/img/equipment/';
    $dest_path = $uploadFileDir . $newFileName;
    
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        return $newFileName;
    }
    
    return null;
}

// 2. ເພີ່ມອຸປະກອນໃໝ່ (Create Equipment)
if ($action === 'create') {
    $eqCode = clean($conn, $_POST['equipment_code'] ?? '');
    $eqName = clean($conn, $_POST['equipment_name'] ?? '');
    $brandModel = clean($conn, $_POST['brand_model'] ?? '');
    $quantity = clean($conn, $_POST['quantity'] ?? '1');
    $status = clean($conn, $_POST['status'] ?? 'ດີ');
    $purchaseDate = clean($conn, $_POST['purchase_date'] ?? '');
    $price = clean($conn, $_POST['price'] ?? '0.00');
    $description = clean($conn, $_POST['description'] ?? '');

    if ($eqCode === '' || $eqName === '') {
        jsonErr('ກະລຸນາປ້ອນ ລະຫັດອຸປະກອນ ແລະ ຊື່ອຸປະກອນ');
    }

    $quantity = (int)$quantity;
    $price = (float)$price;

    // ກວດສອບລະຫັດຊ້ຳ
    $stmtCheck = mysqli_prepare($conn, 'SELECT equipment_id FROM equipment WHERE equipment_code = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 's', $eqCode);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);

    if ($exists) {
        jsonErr('ລະຫັດອຸປະກອນ "' . $eqCode . '" ນີ້ມີໃນລະບົບແລ້ວ');
    }

    // Handle Image Upload
    $equipmentImg = uploadEquipmentImage() ?: 'default_eq.png';

    $purchaseDateParam = ($purchaseDate === '') ? null : $purchaseDate;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO equipment (equipment_code, equipment_name, brand_model, quantity, status, purchase_date, price, description, equipment_img) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'sssisddss', $eqCode, $eqName, $brandModel, $quantity, $status, $purchaseDateParam, $price, $description, $equipmentImg);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('// ບັນທຶກອຸປະກອນບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    logActivity($pdo, "ເພີ່ມເຄື່ອງອອກກຳລັງກາຍ", "ຊື່: $eqName, ລະຫັດ: $eqCode, ຈຳນວນ: $quantity");

    jsonOk('ບັນທຶກອຸປະກອນສຳເລັດ');
}

// 3. ແກ້ໄຂອຸປະກອນ (Update Equipment)
if ($action === 'update') {
    $equipmentId = clean($conn, $_POST['equipment_id'] ?? '');
    $eqCode = clean($conn, $_POST['equipment_code'] ?? '');
    $eqName = clean($conn, $_POST['equipment_name'] ?? '');
    $brandModel = clean($conn, $_POST['brand_model'] ?? '');
    $quantity = clean($conn, $_POST['quantity'] ?? '1');
    $status = clean($conn, $_POST['status'] ?? 'ດີ');
    $purchaseDate = clean($conn, $_POST['purchase_date'] ?? '');
    $price = clean($conn, $_POST['price'] ?? '0.00');
    $description = clean($conn, $_POST['description'] ?? '');

    if ($equipmentId === '') {
        jsonErr('ລະຫັດອຸປະກອນບໍ່ຖືກຕ້ອງ');
    }
    if ($eqCode === '' || $eqName === '') {
        jsonErr('ກະລຸນາປ້ອນ ລະຫັດອຸປະກອນ ແລະ ຊື່ອຸປະກອນ');
    }

    $quantity = (int)$quantity;
    $price = (float)$price;

    // ກວດສອບລະຫັດຊ້ຳ (ຍົກເວັ້ນຕົວເອງ)
    $stmtCheck = mysqli_prepare($conn, 'SELECT equipment_id FROM equipment WHERE equipment_code = ? AND equipment_id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 'si', $eqCode, $equipmentId);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
    mysqli_stmt_close($stmtCheck);

    if ($exists) {
        jsonErr('ລະຫັດອຸປະກອນ "' . $eqCode . '" ນີ້ມີໃນລະບົບແລ້ວ');
    }

    // Fetch old image to delete or keep
    $stmtOld = mysqli_prepare($conn, 'SELECT equipment_img FROM equipment WHERE equipment_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtOld, 'i', $equipmentId);
    mysqli_stmt_execute($stmtOld);
    $resOld = mysqli_stmt_get_result($stmtOld);
    $oldEq = mysqli_fetch_assoc($resOld);
    mysqli_stmt_close($stmtOld);
    
    $equipmentImg = $oldEq ? $oldEq['equipment_img'] : 'default_eq.png';
    
    // Handle image upload
    $newImg = uploadEquipmentImage();
    if ($newImg !== null) {
        // Delete old image if it's not the default
        if ($equipmentImg !== 'default_eq.png' && $equipmentImg !== '') {
            $oldImgPath = __DIR__ . '/../assets/img/equipment/' . $equipmentImg;
            if (file_exists($oldImgPath)) {
                @unlink($oldImgPath);
            }
        }
        $equipmentImg = $newImg;
    }

    $purchaseDateParam = ($purchaseDate === '') ? null : $purchaseDate;

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE equipment SET equipment_code=?, equipment_name=?, brand_model=?, quantity=?, status=?, purchase_date=?, price=?, description=?, equipment_img=? WHERE equipment_id=?'
    );
    mysqli_stmt_bind_param($stmt, 'sssisddssi', $eqCode, $eqName, $brandModel, $quantity, $status, $purchaseDateParam, $price, $description, $equipmentImg, $equipmentId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ແກ້ໄຂອຸປະກອນບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    logActivity($pdo, "ແກ້ໄຂເຄື່ອງອອກກຳລັງກາຍ", "ຊື່: $eqName, ລະຫັດ: $eqCode");

    jsonOk('ແກ້ໄຂຂໍ້ມູນອຸປະກອນສຳເລັດ');
}

// 4. ລົບອຸປະກອນ (Delete Equipment)
if ($action === 'delete') {
    $equipmentId = clean($conn, $_POST['equipment_id'] ?? '');

    if ($equipmentId === '') {
        jsonErr('ລະຫັດອຸປະກອນບໍ່ຖືກຕ້ອງ');
    }

    // ດຶງຂໍ້ມູນເພື່ອເຮັດ Log ແລະ ລົບຮູບ
    $stmtName = mysqli_prepare($conn, 'SELECT equipment_name, equipment_code, equipment_img FROM equipment WHERE equipment_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtName, 'i', $equipmentId);
    mysqli_stmt_execute($stmtName);
    $resName = mysqli_stmt_get_result($stmtName);
    $eqRow = mysqli_fetch_assoc($resName);
    mysqli_stmt_close($stmtName);

    $eqName = $eqRow ? $eqRow['equipment_name'] : $equipmentId;
    $eqCode = $eqRow ? $eqRow['equipment_code'] : '';
    $eqImg = $eqRow ? $eqRow['equipment_img'] : '';

    // Delete image file from disk
    if ($eqImg !== 'default_eq.png' && $eqImg !== '') {
        $imgPath = __DIR__ . '/../assets/img/equipment/' . $eqImg;
        if (file_exists($imgPath)) {
            @unlink($imgPath);
        }
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM equipment WHERE equipment_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $equipmentId);

    if (!mysqli_stmt_execute($stmt)) {
        jsonErr('ລົບອຸປະກອນບໍ່ສຳເລັດ: ' . mysqli_error($conn), 500);
    }
    mysqli_stmt_close($stmt);

    logActivity($pdo, "ລົບເຄື່ອງອອກກຳລັງກາຍ", "ຊື່: $eqName, ລະຫັດ: $eqCode");

    jsonOk('ລົບອຸປະກອນສຳເລັດ');
}

jsonErr('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ');
?>
