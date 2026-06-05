<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('users', 'view')) {
    header('Location: ../Homepage.php');
    exit();
}

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

$users = [];
$sql_report = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id DESC");
if ($sql_report) {
    while($row = mysqli_fetch_assoc($sql_report)){
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts - Noto Sans Lao Looped -->
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <title>ຈັດການຜູ້ໃຊ້ງານ</title>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css?v=2">
    <style>
        .hover-scale {
            transition: filter 0.2s ease;
        }
        .hover-scale:hover {
            filter: brightness(0.95);
        }
        .cursor-pointer {
            cursor: pointer;
        }
        @media (min-width: 768px) {
            .border-md-right {
                border-right: 1px solid #e2e8f0;
            }
        }
        /* Pagination Styles */
        .pagination .page-link {
            border-radius: 6px !important;
            margin: 0 2px;
            color: #4a5568;
            border: 1px solid #e2e8f0;
            padding: 5px 10px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: white !important;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        .pagination .page-link:hover {
            background-color: #edf2f7;
            color: #0b5ed7;
        }
        .pagination .page-item.disabled .page-link {
            color: #a0aec0;
            background-color: #f7fafc;
            border-color: #e2e8f0;
        }
        @media (max-width: 767.98px) {
            .card-footer {
                padding: 6px 8px !important;
                flex-direction: column !important;
                gap: 6px !important;
                align-items: center !important;
                text-align: center !important;
            }
            .pagination .page-link {
                padding: 3px 6px !important;
                font-size: 0.65rem !important;
                margin: 0 1px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fas fa-users text-info"></i> ຈັດການຜູ້ໃຊ້ງານ
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <?php if (hasPermission('users', 'add')): ?>
            <button type="button" class="btn btn-primary text-white fw-bold btn-sm shadow-sm" onclick="openUserModal('create')">
                <i class="fas fa-user-plus"></i> ເພີ່ມຜູ້ໃຊ້
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <!-- Search & Control Header -->
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 bg-light" style="border-radius: 8px 8px 0 0;">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <div class="text-muted small">
                    ຜູ້ນຳໃຊ້ທັງໝົດ: <span class="fw-bold text-primary" id="userCount"><?= count($users) ?></span> ຄົນ
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">ສະແດງ:</span>
                    <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 80px; border-radius: 8px; font-weight: bold; height: 32px;">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="all">ທັງໝົດ</option>
                    </select>
                </div>
            </div>
            <div class="search-box flex-grow-1" style="max-width: 400px;">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາຜູ້ນຳໃຊ້...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0 small">
                    <thead>
                        <tr class="bg-primary text-white text-center">
                            <th class="p-2">ລະຫັດ</th>
                            <th class="p-2">ຊື່</th>
                            <th class="p-2">ນາມສະກຸນ</th>
                            <th class="p-2">ເພດ</th>
                            <th class="p-2">ວັນເກີດ</th>
                            <th class="p-2">ເບີໂທ</th>
                            <th class="p-2">ຊື່ເຂົ້າລະບົບ</th>
                            <th class="p-2">ສະຖານະ</th>
                            <th class="p-2 text-start">ໝາຍເຫດ</th>
                              <?php $can_edit = hasPermission('users', 'edit'); $can_delete = hasPermission('users', 'delete'); ?>
                             <?php if ($can_edit || $can_delete): ?>
                             <th class="p-2" style="min-width:145px;">ຈັດການ</th>
                             <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?> 
                            <tr>
                                 <td colspan="<?= ($can_edit || $can_delete) ? 10 : 9 ?>" class="text-center text-muted">
                                     <i class="fas fa-users"></i>
                                     ຍັງບໍ່ມີຂໍ້ມູນຜູ້ໃຊ້
                                 </td>
                            </tr>
                        <?php else: ?> 
                        <?php $i = 1; foreach ($users as $row): ?>
                        <tr class="user-row">
                            <td class="text-center"><?= $i++ ?></td>
                            <td><b><?= htmlspecialchars($row['fname']) ?></b></td>
                            <td><?= htmlspecialchars($row['lname']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['gender']) ?></td>
                            <td class="text-center"><?= $row['dob'] ? htmlspecialchars($row['dob']) : '-' ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['tel']) ?></td>
                            <td class="text-center"><code><?= htmlspecialchars($row['username']) ?></code></td>
                            <td class="text-center">
                                <span class="badge <?= $row['status'] === 'ຜູ້ບໍລິຫານ' ? 'bg-danger' : 'bg-info' ?> text-white">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= $row['remark'] ? htmlspecialchars($row['remark']) : '-' ?></small></td>
                            <?php if ($can_edit || $can_delete): ?>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-info btn-sm text-white btn-view-details" data-id="<?= htmlspecialchars($row['user_id'], ENT_QUOTES) ?>" title="ເບິ່ງລາຍລະອຽດ" style="padding: 0.25rem 0.5rem; border-radius: 5px; border: none; box-shadow: none;">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($can_edit): ?>
                                <button type="button" class="btn btn-warning btn-sm" onclick="openUserModal('edit', '<?= htmlspecialchars($row['user_id'], ENT_QUOTES) ?>')" title="ແກ້ໄຂ" style="padding: 0.25rem 0.5rem; border-radius: 5px; margin-left: 5px; border: none; box-shadow: none;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($can_delete && $row['user_id'] !== $current_user_id): ?>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser('<?= htmlspecialchars($row['user_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['fname'] . ' ' . $row['lname'], ENT_QUOTES) ?>')" title="ລົບ" style="padding: 0.25rem 0.5rem; border-radius: 5px; margin-left: 5px; border: none; box-shadow: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
            <div class="text-muted small" id="paginationInfo">
                ສະແດງ 1-10 ຈາກທັງໝົດ 10 ຄົນ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls">
                    <!-- Dynamic page links will be inserted here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-primary shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="userModalTitle"><i class="fas fa-user-edit"></i> ຟອມບັນທຶກຜູ້ນຳໃຊ້</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h4 text-white">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm" onsubmit="return false;">
                    <input type="hidden" id="form_mode" name="form_mode" value="create">
                    
                    <!-- ພາກສ່ວນອັບໂຫຼດຮູບພາບໂປຣຟາຍ -->
                    <div class="text-center mb-4 position-relative">
                        <div class="d-inline-block position-relative cursor-pointer" onclick="$('#profile_img_input').click()">
                            <img id="profile_img_preview" src="../assets/img/users/default.png" 
                                 class="rounded-circle border border-primary p-1 shadow-sm hover-scale" 
                                 style="width: 100px; height: 100px; object-fit: cover; transition: all 0.3s;"
                                 alt="Profile Image Preview">
                            <div class="position-absolute bg-primary text-white rounded-circle shadow d-flex align-items-center justify-content-center" 
                                 style="bottom: 0px; right: 0px; width: 30px; height: 30px; border: 2px solid white;">
                                <i class="fas fa-camera" style="font-size: 12px;"></i>
                            </div>
                        </div>
                        <input type="file" id="profile_img_input" name="profile_img" accept="image/*" class="d-none" onchange="previewImage(this)">
                        <div class="small text-muted mt-2">ຄລິກໃສ່ຮູບພາບເພື່ອອັບໂຫຼດ (JPG, PNG, WEBP, ບໍ່ເກີນ 2MB)</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ລະຫັດຜູ້ນຳໃຊ້: <span class="text-danger">*</span></label>
                                <input type="text" id="user_id" name="user_id" class="form-control" placeholder="ກະລຸນາປ້ອນລະຫັດຜູ້ນຳໃຊ້">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ຊື່: <span class="text-danger">*</span></label>
                                <input type="text" id="fname" name="fname" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ນາມສະກຸນ: <span class="text-danger">*</span></label>
                                <input type="text" id="lname" name="lname" class="form-control" placeholder="ກະລຸນາປ້ອນນາມສະກຸນ">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary d-block mb-1">ເພດ: <span class="text-danger">*</span></label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_f" value="ຍິງ">
                                    <label class="form-check-label" for="gender_f">ຍິງ:</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_m" value="ຊາຍ">
                                    <label class="form-check-label" for="gender_m">ຊາຍ</label>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ວັນເດືອນປີເກີດ: <span class="text-danger">*</span></label>
                                <input type="date" id="dob" name="dob" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ເບີໂທ: <span class="text-danger">*</span></label>
                                <input type="text" id="tel" name="tel" class="form-control" placeholder="020...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ສະຖານະ: <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">ເລືອກສະຖານະ...</option>
                                    <option value="ຜູ້ບໍລິຫານ">ຜູ້ບໍລິຫານ</option>
                                    <option value="ພະນັກງານ">ພະນັກງານ</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ຊື່ເຂົ້າລະບົບ: <span class="text-danger">*</span></label>
                                <input type="text" id="username" name="username" class="form-control" placeholder="username" autocomplete="off">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1" id="passwordLabel">ລະຫັດຜ່ານ <span class="text-danger" id="passwordRequired">*</span></label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control" placeholder="ລະຫັດຜ່ານ" autocomplete="new-password">
                                    <span class="input-group-text bg-white cursor-pointer" onclick="togglePassword('password', this.querySelector('i'))">
                                        <i class="fas fa-eye text-secondary"></i>
                                    </span>
                                </div>
                                <small class="text-muted" id="passwordHint" style="display:none;">ປ່ອຍວ່າງຖ້າບໍ່ຕ້ອງການປ່ຽນລະຫັດ</small>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ຢືນຢັນລະຫັດ:</label>
                                <div class="input-group">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="ຢືນຢັນລະຫັດ" autocomplete="new-password">
                                    <span class="input-group-text bg-white cursor-pointer" onclick="togglePassword('confirm_password', this.querySelector('i'))">
                                        <i class="fas fa-eye text-secondary"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ທີ່ຢູ່: <span class="text-danger">*</span></label>
                                <textarea id="address" name="address" class="form-control" rows="2" placeholder="ບ້ານ, ເມືອງ, ແຂວງ"></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold text-secondary mb-1">ໝາຍເຫດ:</label>
                                <textarea id="remark" name="remark" class="form-control" rows="2" placeholder="ໝາຍເຫດ (ບໍ່ບັງຄັບ)"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" id="submitBtn" class="btn btn-success fw-bold" onclick="saveUser()"><i class="fas fa-save"></i> ບັນທຶກ</button>
                <button type="reset" form="userForm" class="btn btn-outline-secondary fw-bold"><i class="fas fa-redo"></i> ລ້າງ</button>
                <button type="button" class="btn btn-secondary fw-bold" data-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Viewing User Details -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: none;">
      <div class="modal-header text-white border-0" style="border-radius: 16px 16px 0 0; background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
        <h5 class="modal-title fw-bold" id="userDetailsModalLabel">
          <i class="fas fa-user-circle mr-2"></i> ລາຍລະອຽດຜູ້ໃຊ້ງານ
        </h5>
        <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close" style="opacity: 0.8; outline: none;">
          <span aria-hidden="true" class="h4 text-white">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4">
        <div class="row align-items-center">
            <!-- Left Column: Profile and Primary Info -->
            <div class="col-md-4 text-center border-md-right pb-3 pb-md-0">
                <img id="viewUserImg" src="../assets/img/users/default.png" 
                     class="rounded-circle border border-primary p-1 shadow-sm mb-3" 
                     style="width: 120px; height: 120px; object-fit: cover; border: 2.5px solid #0d6efd !important;"
                     alt="User Profile">
                <h4 class="fw-bold text-dark mb-1" id="viewUserName"></h4>
                <span class="badge bg-primary px-3 py-2 font-weight-bold text-white shadow-sm" style="font-size: 0.85rem; border-radius: 8px;" id="viewUserStatus"></span>
            </div>
            
            <!-- Right Column: Profile details in horizontal key-value format -->
            <div class="col-md-8 ps-md-4">
                <div class="row small g-2">
                    <div class="col-6 py-2 border-bottom">
                        <span class="text-muted fw-bold d-block">ລະຫັດຜູ້ນຳໃຊ້:</span>
                        <span class="fw-bold text-dark" id="viewUserId">-</span>
                    </div>
                    <div class="col-6 py-2 border-bottom">
                        <span class="text-muted fw-bold d-block">ເພດ:</span>
                        <span class="fw-bold text-dark" id="viewUserGender">-</span>
                    </div>
                    <div class="col-6 py-2 border-bottom">
                        <span class="text-muted fw-bold d-block">ວັນເດືອນປີເກີດ:</span>
                        <span class="fw-bold text-dark" id="viewUserDob">-</span>
                    </div>
                    <div class="col-6 py-2 border-bottom">
                        <span class="text-muted fw-bold d-block">ເບີໂທລະສັບ:</span>
                        <span class="fw-bold text-dark" id="viewUserTel">-</span>
                    </div>
                    <div class="col-12 py-2 border-bottom">
                        <span class="text-muted fw-bold d-block">ຊື່ເຂົ້າລະບົບ (Username):</span>
                        <code class="fw-bold" id="viewUserUsername">-</code>
                    </div>
                    <div class="col-12 py-2 border-bottom">
                        <span class="text-muted fw-bold d-block mb-1">ທີ່ຢູ່ (Address):</span>
                        <div class="p-2 bg-light rounded text-secondary" id="viewUserAddress" style="min-height: 40px; font-size: 0.85rem; white-space: pre-wrap;">-</div>
                    </div>
                    <div class="col-12 py-2">
                        <span class="text-muted fw-bold d-block mb-1">ໝາຍເຫດ (Remark):</span>
                        <div class="p-2 bg-light rounded text-secondary" id="viewUserRemark" style="min-height: 40px; font-size: 0.85rem; white-space: pre-wrap;">-</div>
                    </div>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 16px 16px;">
        <button type="button" class="btn btn-secondary fw-bold px-4" data-dismiss="modal" style="border-radius: 8px;">
          <i class="fas fa-times mr-1"></i> ປິດ
        </button>
      </div>
    </div>
  </div>
</div>

<script>
        // ຟັງຊັນສະແດງຕົວຢ່າງຮູບພາບເມື່ອເລືອກໄຟລ໌
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                
                // ກວດສອບປະເພດໄຟລ໌
                var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (allowed.indexOf(file.type) === -1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ',
                        text: 'ກະລຸນາເລືອກສະເພາະຮູບພາບ (.jpg, .png, .gif, .webp)',
                        confirmButtonText: 'ຕົກລົງ'
                    });
                    input.value = '';
                    return;
                }
                
                // ກວດສອບຂະໜາດ (ບໍ່ເກີນ 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ຂະໜາດຮູບພາບໃຫຍ່ເກີນໄປ',
                        text: 'ກະລຸນາເລືອກຮູບທີ່ມີຂະໜາດບໍ່ເກີນ 2MB',
                        confirmButtonText: 'ຕົກລົງ'
                    });
                    input.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#profile_img_preview').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            }
        }

        // ຟັງຊັນເປີດ Modal ສຳລັບເພີ່ມ/ແກ້ໄຂຜູ້ໃຊ້
        function openUserModal(mode, userId = null) {
            if (mode === 'create') {
                $('#userModalTitle').html('<i class="fas fa-user-plus"></i> ເພີ່ມຜູ້ໃຊ້ງານ');
                $('#userForm')[0].reset();
                $('#profile_img_preview').attr('src', '../assets/img/users/default.png');
                $('#profile_img_input').val('');
                $('#form_mode').val('create');
                $('#user_id').val('').prop('readonly', false);
                $('#password').attr('placeholder', 'ປ້ອນລະຫັດຜ່ານ...');
            } else if (mode === 'edit' && userId) {
                $('#userModalTitle').html('<i class="fas fa-user-edit"></i> ແກ້ໄຂຂໍ້ມູນຜູ້ໃຊ້');
                $('#form_mode').val('update');
                $('#user_id').prop('readonly', true);
                $('#password').attr('placeholder', 'ປ່ອຍວ່າງ = ບໍ່ປ່ຽນລະຫັດ');
                
                // ໂຫຼດຂໍ້ມູນຜູ້ໃຊ້
                $.getJSON('../api/users_api.php', { action: 'get', user_id: userId }, function(res) {
                    if (!res.success) {
                        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
                        return;
                    }
                    var u = res.user;
                    $('#user_id').val(u.user_id);
                    $('#fname').val(u.fname);
                    $('#lname').val(u.lname);
                    $( "input[name='gender'][value='" + u.gender + "']" ).prop('checked', true);
                    $('#dob').val(u.dob);
                    $('#tel').val(u.tel);
                    $('#address').val(u.address);
                    $('#status').val(u.status);
                    $('#username').val(u.username);
                    $('#remark').val(u.remark);

                    // ຕັ້ງຄ່າຮູບພາບຕົວຢ່າງ
                    var profileImg = u.profile_img ? u.profile_img : 'default.png';
                    $('#profile_img_preview').attr('src', '../assets/img/users/' + profileImg);
                    $('#profile_img_input').val(''); // ລ້າງການເລືອກໄຟລ໌ເກົ່າ
                });
            }
            
            $('#userModal').modal('show');
        }
        
        // ຟັງຊັນບັນທຶກຂໍ້ມູນ
        function saveUser() {
            var fname = $("#fname").val().trim();
            var lname = $("#lname").val().trim();
            var gender = $("input[name='gender']:checked").val();
            var dob = $("#dob").val();
            var tel = $("#tel").val().trim();
            var address = $("#address").val().trim();
            var status = $("#status").val();
            var username = $("#username").val().trim();
            var password = $("#password").val();
            var confirm_password = $("#confirm_password").val();
            var userId = $('#user_id').val().trim();
            var action = $('#form_mode').val();
            
            // ການກວດສອບຄວາມຖືກຕ້ອງຂອງຂໍ້ມູນຝັ່ງ Client (Client-side Validation ໂດຍໃຊ້ SweetAlert ແທນ required)
            if (userId === "") { Swal.fire({ title: "ກະລຸນາປ້ອນລະຫັດຜູ້ນຳໃຊ້...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (fname === "") { Swal.fire({ title: "ກະລຸນາປ້ອນຊື່...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (lname === "") { Swal.fire({ title: "ກະລຸນາປ້ອນນາມສະກຸນ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (gender === undefined) { Swal.fire({ title: "ກະລຸນາເລືອກເພດ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (dob === "") { Swal.fire({ title: "ກະລຸນາປ້ອນວັນເດືອນປີເກີດ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (tel === "") { Swal.fire({ title: "ກະລຸນາປ້ອນເບີໂທ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (address === "") { Swal.fire({ title: "ກະລຸນາປ້ອນທີ່ຢູ່...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (status === "") { Swal.fire({ title: "ກະລຸນາເລືອກສະຖານະ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            if (username === "") { Swal.fire({ title: "ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ງານ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            
            // ກໍລະນີເພີ່ມຜູ້ໃຊ້ໃໝ່ (create), ຕ້ອງກວດສອບວ່າໄດ້ປ້ອນລະຫັດຜ່ານ ແລະ ຢືນຢັນລະຫັດຜ່ານແລ້ວບໍ
            if (action === 'create') {
                if (password === "") { Swal.fire({ title: "ກະລຸນາປ້ອນລະຫັດຜ່ານ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
                if (confirm_password === "") { Swal.fire({ title: "ກະລຸນາຢືນຢັນລະຫັດຜ່ານ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' }); return; }
            }
            
            if (password !== "" || confirm_password !== "") {
                if (password !== confirm_password) {
                    Swal.fire({ title: "ລະຫັດຜ່ານບໍ່ຕົງກັນ...!", text: 'ກົດ ຕົກລົງ ເພື່ອດຳເນີນຕໍ່', icon: "warning", confirmButtonText: 'ຕົກລົງ' });
                    return;
                }
            }

            var formData = new FormData($('#userForm')[0]);
            formData.append('action', action);
            
            $('#submitBtn').prop('disabled', true);
            
            $.ajax({
                url: '../api/users_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#submitBtn').prop('disabled', false);
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ສຳເລັດ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            $('#userModal').modal('hide');
                            
                            // ກວດສອບວ່າແມ່ນບັນຊີຂອງຜູ້ນຳໃຊ້ທີ່ກຳລັງ Login ຢູ່ບໍ
                            var currentUserId = '<?= htmlspecialchars($current_user_id, ENT_QUOTES) ?>';
                            var isCurrentUser = (userId === currentUserId);
                            
                            if (isCurrentUser) {
                                window.parent.location.reload(); // ໂຫຼດໜ້າຫຼັກ (Parent Navbar) ໃໝ່ເພື່ອໃຫ້ Navbar ອັບເດດຮູບ ແລະ ຊື່ທັນທີ!
                            } else {
                                location.reload(); // ໂຫຼດສະເພາະ Iframe ຄືເກົ່າ
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
                    }
                },
                error: function(xhr) {
                    $('#submitBtn').prop('disabled', false);
                    var msg = 'ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບ Server ໄດ້';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res && res.message) {
                            msg = res.message;
                        }
                    } catch(e) {}
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
                }
            });
        }
        
        // ຟັງຊັນລົບຜູ້ໃຊ້
        function deleteUser(userId, name) {
            Swal.fire({
                title: 'ຢືນຢັນການລົບ?',
                text: "ທ່ານຕ້ອງການລົບຜູ້ໃຊ້ " + name + " ບໍ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ລົບ',
                cancelButtonText: 'ຍົກເລີກ'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                
                $.ajax({
                    url: '../api/users_api.php',
                    type: 'POST',
                    data: { action: 'delete', user_id: userId },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ສຳເລັດ',
                                text: res.message,
                                timer: 1200,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
                        }
                    },
                    error: function(xhr) {
                        var msg = 'ບໍ່ສາມາດລົບຂໍ້ມູນໄດ້';
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res && res.message) {
                                msg = res.message;
                            }
                        } catch(e) {}
                        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
                    }
                });
            });
        }
        
        // ຟັງຊັນສະແດງ/ເຊື່ອງລະຫັດຜ່ານ
        function togglePassword(inputId, icon) {
            var input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        // ຟັງຊັນຄົ້ນຫາຜູ້ນຳໃຊ້ Real-time ພ້ອມ Pagination
        $(document).ready(function() {
            var itemsPerPage = 10;
            var currentPage = 1;
            var filteredRows = [];

            $('#pageSizeSelect').on('change', function() {
                var val = $(this).val();
                if (val === 'all') {
                    itemsPerPage = 999999;
                } else {
                    itemsPerPage = parseInt(val);
                }
                showPage(1);
            });

            // Initialize pagination
            function initPagination() {
                updateFilteredRows();
                showPage(1);
            }

            // Update the list of rows that match the search query
            function updateFilteredRows() {
                var query = $('#searchInput').val().toLowerCase().trim();
                filteredRows = [];
                
                $('.user-row').each(function() {
                    var text = $(this).text().toLowerCase();
                    if (text.indexOf(query) > -1) {
                        filteredRows.push(this);
                    } else {
                        $(this).hide(); // Hide non-matching rows immediately
                    }
                });
                
                $('#userCount').text(filteredRows.length);
                
                // Handle empty search state
                if (filteredRows.length === 0 && $('.user-row').length > 0) {
                    if ($('#emptySearchResult').length === 0) {
                        $('tbody').append(
                            `<tr id="emptySearchResult"><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນທີ່ທ່ານຄົ້ນຫາ</td></tr>`
                        );
                    }
                } else {
                    $('#emptySearchResult').remove();
                }
            }

            // Display rows for a specific page
            function showPage(page) {
                currentPage = page;
                var totalItems = filteredRows.length;
                
                if (totalItems === 0) {
                    $('.user-row').hide();
                    $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ຄົນ');
                    $('#paginationControls').html('');
                    return;
                }
                
                var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
                
                if (currentPage < 1) currentPage = 1;
                if (currentPage > totalPages) currentPage = totalPages;
                
                var startIndex = (currentPage - 1) * itemsPerPage;
                var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
                
                // Show/hide rows based on page bounds
                $('.user-row').hide();
                for (var i = startIndex; i < endIndex; i++) {
                    $(filteredRows[i]).show();
                }
                
                // Update pagination info text (Lao language)
                $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ຄົນ');
                
                // Render control buttons
                renderControls(totalPages);
            }

            // Render pagination control buttons dynamically
            function renderControls(totalPages) {
                var controlsHtml = '';
                
                // Previous Button
                if (currentPage === 1) {
                    controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-left"></i></a></li>`;
                } else {
                    controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a></li>`;
                }
                
                // Page Numbers
                // To prevent too many page buttons on mobile, show at most 5 pages around the current page
                var startPage = Math.max(1, currentPage - 2);
                var endPage = Math.min(totalPages, startPage + 4);
                if (endPage - startPage < 4) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                if (startPage > 1) {
                    controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)">...</a></li>`;
                    }
                }
                
                for (var p = startPage; p <= endPage; p++) {
                    if (p === currentPage) {
                        controlsHtml += `<li class="page-item active"><a class="page-link" href="javascript:void(0)">${p}</a></li>`;
                    } else {
                        controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${p}">${p}</a></li>`;
                    }
                }
                
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)">...</a></li>`;
                    }
                    controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${totalPages}">${totalPages}</a></li>`;
                }
                
                // Next Button
                if (currentPage === totalPages) {
                    controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-right"></i></a></li>`;
                } else {
                    controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a></li>`;
                }
                
                $('#paginationControls').html(controlsHtml);
                
                // Attach click handlers
                $('#paginationControls a[data-page]').off('click').on('click', function(e) {
                    e.preventDefault();
                    var targetPage = parseInt($(this).data('page'));
                    showPage(targetPage);
                });
            }

            // Real-time search handler
            $('#searchInput').on('input', function() {
                updateFilteredRows();
                showPage(1); // Always reset to page 1 on search
            });

            // Run on startup
            initPagination();

            // ຟັງຊັນສະແດງລາຍລະອຽດຜູ້ນຳໃຊ້
            $(document).on("click", ".btn-view-details", function() {
                var id = $(this).data("id");
                
                // Reset to loading state
                $("#viewUserImg").attr("src", "../assets/img/users/default.png");
                $("#viewUserName").text("ກຳລັງໂຫຼດ...");
                $("#viewUserStatus").text("-").removeClass("bg-danger bg-info bg-primary");
                $("#viewUserId").text("-");
                $("#viewUserGender").text("-");
                $("#viewUserDob").text("-");
                $("#viewUserTel").text("-");
                $("#viewUserUsername").text("-");
                $("#viewUserAddress").text("-");
                $("#viewUserRemark").text("-");
                
                $("#userDetailsModal").modal("show");
                
                $.ajax({
                    url: "../api/users_api.php",
                    type: "GET",
                    data: {
                        action: "get",
                        user_id: id
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            var u = res.user;
                            
                            var img = u.profile_img ? u.profile_img : 'default.png';
                            $("#viewUserImg").attr("src", "../assets/img/users/" + img);
                            $("#viewUserName").text((u.fname || "") + " " + (u.lname || ""));
                            
                            var statusClass = u.status === 'ຜູ້ບໍລິຫານ' ? 'bg-danger' : 'bg-info';
                            $("#viewUserStatus").text(u.status || "ພະນັກງານ").addClass(statusClass);
                            
                            $("#viewUserId").text(u.user_id || "-");
                            $("#viewUserGender").text(u.gender || "-");
                            
                            var dobStr = "-";
                            if (u.dob && u.dob !== "0000-00-00") {
                                var parts = u.dob.split("-");
                                if (parts.length === 3) {
                                    dobStr = parts[2] + "/" + parts[1] + "/" + parts[0];
                                } else {
                                    dobStr = u.dob;
                                }
                            }
                            $("#viewUserDob").text(dobStr);
                            $("#viewUserTel").text(u.tel || "-");
                            $("#viewUserUsername").text(u.username || "-");
                            $("#viewUserAddress").text(u.address || "ບໍ່ມີທີ່ຢູ່");
                            $("#viewUserRemark").text(u.remark || "ບໍ່ມີໝາຍເຫດ");
                        }
                    },
                    error: function(xhr) {
                        var errMsg = "ບໍ່ສາມາດດຶງຂໍ້ມູນຜູ້ໃຊ້ໄດ້";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: "ຜິດພາດ",
                            text: errMsg,
                            icon: "error",
                            confirmButtonText: "ຕົກລົງ"
                        });
                    }
                });
            });

            // ກວດສອບການລ້າງຟອມ (Reset Form) ເພື່ອລີເຊັດຮູບພາບ Preview ໃຫ້ຖືກຕ້ອງ
            $('#userForm').on('reset', function() {
                var mode = $('#form_mode').val();
                setTimeout(function() {
                    if (mode === 'update') {
                        var userId = $('#user_id').val();
                        $.getJSON('../api/users_api.php', { action: 'get', user_id: userId }, function(res) {
                            if (res.success) {
                                var img = res.user.profile_img ? res.user.profile_img : 'default.png';
                                $('#profile_img_preview').attr('src', '../assets/img/users/' + img);
                            }
                        });
                    } else {
                        $('#profile_img_preview').attr('src', '../assets/img/users/default.png');
                    }
                    $('#profile_img_input').val('');
                }, 50);
            });
        });
    </script>
</body>
</html>
