<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Check permissions
if (!hasPermission('members', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Fetch members
$members = [];
$sql = "SELECT * FROM members ORDER BY member_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຂໍ້ມູນສະມາຊິກ</title>
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
            font-family: 'Noto Sans Lao Looped', sans-serif;
            background-color: #f4f6f9;
        }
        .avatar-circle-member {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .avatar-preview-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #007bff;
            overflow: hidden;
            margin: 0 auto 15px auto;
            position: relative;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .avatar-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0,0,0,0.5);
            color: white;
            text-align: center;
            padding: 4px 0;
            font-size: 0.75rem;
            transition: all 0.3s;
            opacity: 0;
        }
        .avatar-preview-container:hover .avatar-overlay {
            opacity: 1;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-users text-primary me-2"></i> ຈັດການຂໍ້ມູນສະມາຊິກ
            </h4>
            <p class="text-muted small mb-0">ເພີ່ມ, ແກ້ໄຂ, ລົບ ແລະ ກວດສອບຂໍ້ມູນສະມາຊິກຍິມທັງໝົດ</p>
        </div>
        <div>
            <?php if (hasPermission('members', 'add')): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-user-plus me-1"></i> ເພີ່ມສະມາຊິກໃໝ່
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <!-- Search & Control Header -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        ສະມາຊິກທັງໝົດ: <span class="fw-bold text-primary" id="memberCount"><?= count($members) ?></span> ຄົນ
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
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາສະມາຊິກ...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">ຮູບ</th>
                            <th class="text-center">ລະຫັດບັດ</th>
                            <th>ຊື່ ແລະ ນາມສະກຸນ</th>
                            <th class="text-center">ເພດ</th>
                            <th class="text-center">ເບີໂທລະສັບ</th>
                            <th style="min-width: 200px;">ທີ່ຢູ່</th>
                            <th class="text-center">ສະຖານະ</th>
                            <?php if (hasPermission('members', 'edit') || hasPermission('members', 'delete')): ?>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="memberTableBody">
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="<?= (hasPermission('members', 'edit') || hasPermission('members', 'delete')) ? 8 : 7 ?>" class="text-center py-5 text-muted">
                                    <i class="fas fa-users fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນສະມາຊິກ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $m): 
                                $img_path = '../assets/img/members/' . ($m['profile_img'] ?: 'default.png');
                                if (!file_exists(__DIR__ . '/../' . $img_path) || empty($m['profile_img'])) {
                                    $img_path = '../assets/img/members/default.png';
                                }
                            ?>
                                <tr class="member-row">
                                    <td class="text-center">
                                        <img src="<?= htmlspecialchars($img_path) ?>" class="avatar-circle-member" alt="Avatar">
                                    </td>
                                    <td class="text-center fw-bold"><code><?= htmlspecialchars($m['member_code']) ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($m['fname']) ?> <?= htmlspecialchars($m['lname']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($m['gender']) ?></td>
                                    <td class="text-center text-secondary"><?= htmlspecialchars($m['tel']) ?></td>
                                    <td class="text-muted small"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($m['address']) ?></td>
                                    <td class="text-center">
                                        <?php if ($m['status'] === 'Active'): ?>
                                            <span class="badge bg-success-light text-success px-3 py-1.5" style="border-radius: 20px;">Active</span>
                                        <?php elseif ($m['status'] === 'Expired'): ?>
                                            <span class="badge bg-danger-light text-danger px-3 py-1.5" style="border-radius: 20px;">Expired</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-light text-secondary px-3 py-1.5" style="border-radius: 20px;"><?= htmlspecialchars($m['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (hasPermission('members', 'edit') || hasPermission('members', 'delete')): ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <?php if (hasPermission('members', 'edit')): ?>
                                            <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(<?= $m['member_id'] ?>)" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('members', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteMember(<?= $m['member_id'] ?>)" title="ລົບ">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
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
        <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="text-muted small" id="paginationInfo">
                ສະແດງ 1-10 ຈາກທັງໝົດ 10 ຄົນ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal ເພີ່ມ/ແກ້ໄຂຂໍ້ມູນສະມາຊິກ -->
<div class="modal fade" id="memberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-user-plus me-1"></i> ເພີ່ມສະມາຊິກໃໝ່</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="memberForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="member_id" id="formMemberId">
                
                <div class="modal-body p-4">
                    <!-- Avatar Preview -->
                    <div class="avatar-preview-container" onclick="$('#profile_img').click();">
                        <img src="../assets/img/members/default.png" id="avatarPreview" class="avatar-preview-img">
                        <div class="avatar-overlay"><i class="fas fa-camera"></i></div>
                    </div>
                    <input type="file" name="profile_img" id="profile_img" accept="image/*" style="display: none;">

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">ລະຫັດບັດສະມາຊິກ (ຖ້າຫວ່າງຈະສ້າງໃຫ້ອັດຕະໂນມັດ)</label>
                            <input type="text" name="member_code" id="member_code" class="form-control" placeholder="ປ້ອນລະຫັດບັດ (ເຊັ່ນ: GYM0001)...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ຊື່ສະມາຊິກ</label>
                            <input type="text" name="fname" id="fname" class="form-control" placeholder="ປ້ອນຊື່...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ນາມສະກຸນ</label>
                            <input type="text" name="lname" id="lname" class="form-control" placeholder="ປ້ອນນາມສະກຸນ...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ເພດ</label>
                            <select name="gender" id="gender" class="form-control">
                                <option value="ຊາຍ">ຊາຍ</option>
                                <option value="ຍິງ">ຍິງ</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ວັນເດືອນປີເກີດ</label>
                            <input type="date" name="dob" id="dob" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ເບີໂທລະສັບ</label>
                            <input type="text" name="tel" id="tel" class="form-control" placeholder="020...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ສະຖານະ</label>
                            <select name="status" id="status" class="form-control">
                                <option value="Active">ປົກກະຕິ</option>
                                <option value="Expired">ໝົດອາຍຸ</option>
                                <option value="Inactive">ຢຸດໃຊ້ງານ</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">ທີ່ຢູ່</label>
                            <textarea name="address" id="address" class="form-control" rows="2" placeholder="ປ້ອນທີ່ຢູ່..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="submit" class="btn btn-success fw-bold px-4" id="saveBtn"><i class="fas fa-save me-1"></i> ບັນທຶກ</button>
                    <button type="button" class="btn btn-secondary fw-bold" data-dismiss="modal">ຍົກເລີກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Preview Image immediately
    $('#profile_img').on('change', function() {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // ============ ການສົ່ງຟອມບັນທຶກສະມາຊິກ (Save Member Form) ============
    $('#memberForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບ
        
        // 1. ກວດສອບຊື່ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#fname').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່', confirmButtonColor: '#007bff' });
            return;
        }
        // 2. ກວດສອບນາມສະກຸນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#lname').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນນາມສະກຸນ', confirmButtonColor: '#007bff' });
            return;
        }
        // 3. ກວດສອບເພດ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#gender').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກເພດ', confirmButtonColor: '#007bff' });
            return;
        }
        // 4. ກວດສອບເບີໂທລະສັບ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#tel').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນເບີໂທລະສັບ', confirmButtonColor: '#007bff' });
            return;
        }
        // 5. ກວດສອບສະຖານະ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#status').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກສະຖານະ', confirmButtonColor: '#007bff' });
            return;
        }
        // 6. ກວດສອບທີ່ຢູ່ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#address').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນທີ່ຢູ່ປັດຈຸບັນ', confirmButtonColor: '#007bff' });
            return;
        }
        
        let formData = new FormData(this);
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/member_api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກ');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ສຳເລັດ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#memberModal').modal('hide');
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກ');
                let msg = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຂໍ້ມູນ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
            }
        });
    });

    // Pagination & Search in JavaScript
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

    function updateFilteredRows() {
        var query = $('#searchInput').val().toLowerCase().trim();
        filteredRows = [];
        
        $('.member-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#memberCount').text(filteredRows.length);
        
        if (filteredRows.length === 0 && $('.member-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#memberTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນສະມາຊິກ</td></tr>`
                );
            }
        } else {
            $('#emptySearchResult').remove();
        }
    }

    function showPage(page) {
        currentPage = page;
        var totalItems = filteredRows.length;
        
        if (totalItems === 0) {
            $('.member-row').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ຄົນ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.member-row').hide();
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show();
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ຄົນ');
        
        renderControls(totalPages);
    }

    function renderControls(totalPages) {
        var controlsHtml = '';
        if (currentPage === 1) {
            controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-left"></i></a></li>`;
        } else {
            controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a></li>`;
        }
        
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (var p = startPage; p <= endPage; p++) {
            if (p === currentPage) {
                controlsHtml += `<li class="page-item active"><a class="page-link" href="javascript:void(0)">${p}</a></li>`;
            } else {
                controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${p}">${p}</a></li>`;
            }
        }
        
        if (currentPage === totalPages) {
            controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-right"></i></a></li>`;
        } else {
            controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a></li>`;
        }
        
        $('#paginationControls').html(controlsHtml);
        
        $('#paginationControls a[data-page]').off('click').on('click', function(e) {
            e.preventDefault();
            showPage(parseInt($(this).data('page')));
        });
    }

    $('#searchInput').on('input', function() {
        updateFilteredRows();
        showPage(1);
    });

    // Run pagination
    updateFilteredRows();
    showPage(1);
});

function openCreateModal() {
    $('#formAction').val('create');
    $('#formMemberId').val('');
    $('#memberForm')[0].reset();
    $('#avatarPreview').attr('src', '../assets/img/members/default.png');
    $('#modalTitle').html('<i class="fas fa-user-plus me-1"></i> ເພີ່ມສະມາຊິກໃໝ່');
    $('#memberModal').modal('show');
}

function openEditModal(memberId) {
    if (!memberId) return;

    $.ajax({
        url: '../api/member_api.php',
        type: 'GET',
        data: { action: 'get', member_id: memberId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let m = res.member;
                $('#formAction').val('update');
                $('#formMemberId').val(m.member_id);
                $('#member_code').val(m.member_code);
                $('#fname').val(m.fname);
                $('#lname').val(m.lname);
                $('#gender').val(m.gender);
                $('#dob').val(m.dob);
                $('#tel').val(m.tel);
                $('#status').val(m.status);
                $('#address').val(m.address);
                
                let imgName = m.profile_img || 'default.png';
                $('#avatarPreview').attr('src', '../assets/img/members/' + imgName);
                
                $('#modalTitle').html('<i class="fas fa-edit me-1"></i> ແກ້ໄຂຂໍ້ມູນສະມາຊິກ');
                $('#memberModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນສະມາຊິກໄດ້' });
        }
    });
}

function deleteMember(memberId) {
    if (!memberId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບສະມາຊິກຄົນນີ້ແທ້ບໍ່? ຂໍ້ມູນການສະໝັກ ແລະ ເຊັກອິນທັງໝົດຈະຖືກລົບນຳ!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/member_api.php',
                type: 'POST',
                data: { action: 'delete', member_id: memberId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ລົບສຳເລັດ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                },
                error: function(xhr) {
                    let msg = 'ບໍ່ສາມາດລົບສະມາຊິກໄດ້';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
                }
            });
        }
    });
}
</script>
</body>
</html>
