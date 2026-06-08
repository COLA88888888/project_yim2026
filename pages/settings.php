<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Only Admin (ຜູ້ບໍລິຫານ) has access to system settings
if (isset($_SESSION['status']) && $_SESSION['status'] !== 'ຜູ້ບໍລິຫານ') {
    echo "<div class='container mt-5'><div class='alert alert-danger fw-bold text-center p-4' style='border-radius:12px;'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Handle settings update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    header('Content-Type: application/json');
    $gym_name = trim($_POST['gym_name'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($gym_name)) {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ຍິມ / ສະໂມສອນ']);
        exit();
    }

    $currentSettings = getSystemSettings($conn);
    $logo_path = $currentSettings['logo_path'];

    // Handle File Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['logo']['tmp_name'];
        $fileName = $_FILES['logo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg'];
        if (in_array($fileExtension, $allowedExtensions)) {
            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0777, true);
            }
            $newFileName = 'gym_logo_' . time() . '.' . $fileExtension;
            $dest_path = '../uploads/' . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $logo_path = '../uploads/' . $newFileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດອັບໂຫຼດຮູບໂລໂກ້ໄດ້']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ອັບໂຫຼດໄດ້ສະເພາະຮູບພາບ JPG, PNG, SVG ເທົ່ານັ້ນ']);
            exit();
        }
    }

    // Update in database
    $gym_name_esc = mysqli_real_escape_string($conn, $gym_name);
    $tel_esc = mysqli_real_escape_string($conn, $tel);
    $address_esc = mysqli_real_escape_string($conn, $address);
    $logo_path_esc = mysqli_real_escape_string($conn, $logo_path);

    $updateQuery = "UPDATE system_settings SET gym_name = '$gym_name_esc', tel = '$tel_esc', address = '$address_esc', logo_path = '$logo_path_esc' LIMIT 1";
    if (mysqli_query($conn, $updateQuery)) {
        echo json_encode(['success' => true, 'message' => 'ບັນທຶກການຕັ້ງຄ່າຂໍ້ມູນຍິມສຳເລັດແລ້ວ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດບັນທຶກຂໍ້ມູນລົງຖານຂໍ້ມູນໄດ້']);
    }
    exit();
}

$settings = getSystemSettings($conn);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຕັ້ງຄ່າລະບົບຂໍ້ມູນຍິມ</title>
    <!-- Google Fonts - Noto Sans Lao Looped -->
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    
    <style>
        body {
            font-family: 'Noto Sans Lao Looped', 'Noto Sans Lao', sans-serif;
            background-color: #f4f6f9;
        }
        .logo-preview-box {
            width: 140px;
            height: 140px;
            border: 2px dashed #ddd;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fafafa;
            position: relative;
        }
        .logo-preview-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-cogs text-primary me-2"></i> ຕັ້ງຄ່າຂໍ້ມູນຍິມ / System Settings
            </h4>
            <p class="text-muted small mb-0">ຈັດການ ແລະ ແກ້ໄຂຂໍ້ມູນທົ່ວໄປຂອງສະໂມສອນຍິມ ເຊັ່ນ: ຊື່ຍິມ, ທີ່ຢູ່, ເບີໂທ, ແລະ ໂລໂກ້ ທີ່ຈະສະແດງໃນໃບບິນຮັບເງິນ</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card card-custom shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-edit text-success me-1"></i> ແກ້ໄຂຂໍ້ມູນສະໂມສອນ</h5>
                </div>
                <div class="card-body p-4">
                    <form id="settingsForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row g-4">
                            <!-- Logo Upload Column -->
                            <div class="col-md-4 text-center d-flex flex-column align-items-center">
                                <label class="form-label fw-bold text-muted small mb-2 d-block">ໂລໂກ້ສະໂມສອນ</label>
                                <div class="logo-preview-box mb-3 shadow-sm">
                                    <img id="logoPreview" src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo Preview">
                                </div>
                                <div class="w-100">
                                    <label class="btn btn-sm btn-outline-primary rounded-pill px-3 mb-0" style="cursor: pointer;">
                                        <i class="fas fa-upload me-1"></i> ເລືອກຮູບພາບ
                                        <input type="file" name="logo" id="logoInput" accept="image/*" style="display: none;">
                                    </label>
                                    <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">JPG, PNG, SVG (ແນະນຳອັດຕາສ່ວນ 1:1)</small>
                                </div>
                            </div>
                            
                            <!-- Form Details Column -->
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">ຊື່ສະໂມສອນຍິມ / Gym Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-dumbbell text-muted"></i></span>
                                        <input type="text" name="gym_name" id="gym_name" class="form-control" placeholder="ປ້ອນຊື່ຍິມ ຫຼື ສະໂມສອນ..." value="<?= htmlspecialchars($settings['gym_name']) ?>" required style="height: 45px;">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">ເບີໂທລະສັບ / Telephone</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-phone-alt text-muted"></i></span>
                                        <input type="text" name="tel" id="tel" class="form-control" placeholder="ປ້ອນເບີໂທລະສັບຕິດຕໍ່..." value="<?= htmlspecialchars($settings['tel']) ?>" style="height: 45px;">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">ສະຖານທີ່ / ທີ່ຢູ່ / Gym Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt text-muted"></i></span>
                                        <textarea name="address" id="address" class="form-control" rows="3" placeholder="ປ້ອນທີ່ຢູ່ ຫຼື ທີ່ຕັ້ງສະໂມສອນ..." style="border-top-left-radius: 0; border-bottom-left-radius: 0;"><?= htmlspecialchars($settings['address']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" id="saveSettingsBtn" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">
                                <i class="fas fa-save me-1"></i> ບັນທຶກການຕັ້ງຄ່າ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Logo image preview on select
    $('#logoInput').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#logoPreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Form Submission via AJAX
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#gym_name').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ຄຳເຕືອນ', text: 'ກະລຸນາປ້ອນຊື່ຍິມ' });
            return;
        }

        let formData = new FormData(this);
        let saveBtn = $('#saveSettingsBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: 'settings.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກການຕັ້ງຄ່າ');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ສຳເລັດ',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (window.top) {
                            window.top.location.reload();
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
                }
            },
            error: function() {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກການຕັ້ງຄ່າ');
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ກັບເຊີເວີ' });
            }
        });
    });
});
</script>
</body>
</html>
