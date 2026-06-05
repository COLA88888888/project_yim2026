<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('expenses', 'view')) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}

// Fetch expense categories
$categories = [];
$sql = "SELECT * FROM expense_categories ORDER BY category_id ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການປະເພດລາຍຈ່າຍ</title>
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    
    <style>
        body {
            font-family: 'Noto Sans Lao', 'Noto Sans Lao Looped', sans-serif;
            background-color: #f4f6f9;
        }
        .card-custom {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .table-custom th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 700;
        }
        .btn-action {
            border-radius: 8px;
            padding: 5px 10px;
        }
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .search-box input {
            padding-left: 36px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-tags text-danger me-2"></i> ຈັດການປະເພດລາຍຈ່າຍ
            </h4>
            <p class="text-muted small mb-0">ກຳນົດ ແລະ ບໍລິຫານປະເພດລາຍຈ່າຍພາຍໃນຍິມ (ເຊັ່ນ ຄ່ານ້ຳ/ຄ່າໄຟ, ຄ່າເຊົ່າ, ເງິນເດືອນພະນັກງານ)</p>
        </div>
        <div>
            <?php if (hasPermission('expenses', 'add')): ?>
            <button class="btn btn-danger rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ເພີ່ມປະເພດລາຍຈ່າຍໃໝ່
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
                        ປະເພດລາຍຈ່າຍທັງໝົດ: <span class="fw-bold text-danger" id="categoryCount"><?= count($categories) ?></span> ລາຍການ
                    </div>
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາປະເພດລາຍຈ່າຍ...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 150px;">ລະຫັດປະເພດລາຍຈ່າຍ</th>
                            <th>ຊື່ປະເພດລາຍຈ່າຍ</th>
                            <th style="width: 250px;">ວັນທີບັນທຶກ</th>
                            <?php if (hasPermission('expenses', 'edit') || hasPermission('expenses', 'delete')): ?>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="<?= (hasPermission('expenses', 'edit') || hasPermission('expenses', 'delete')) ? 4 : 3 ?>" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນປະເພດລາຍຈ່າຍ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <tr class="category-row">
                                    <td><span class="badge bg-info text-white"><?= htmlspecialchars($c['category_code']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($c['category_name']) ?></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                    <?php if (hasPermission('expenses', 'edit') || hasPermission('expenses', 'delete')): ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <?php if (hasPermission('expenses', 'edit')): ?>
                                            <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(<?= $c['category_id'] ?>)" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('expenses', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteCategory(<?= $c['category_id'] ?>)" title="ລົບ">
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
    </div>
</div>

<!-- Modal ເພີ່ມ/ແກ້ໄຂປະເພດລາຍຈ່າຍ -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">ເພີ່ມປະເພດລາຍຈ່າຍໃໝ່</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="categoryForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="category_id" id="formCategoryId">
                
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ລະຫັດປະເພດລາຍຈ່າຍ <span class="text-danger">*</span></label>
                        <input type="text" name="category_code" id="category_code" class="form-control form-control-lg rounded-3" placeholder="ຕົວຢ່າງ: EXP001..." style="font-size:0.95rem;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ຊື່ປະເພດລາຍຈ່າຍ <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="category_name" class="form-control form-control-lg rounded-3" placeholder="ກະລຸນາປ້ອນຊື່ປະເພດລາຍຈ່າຍ..." style="font-size:0.95rem;" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm" id="saveBtn">
                        <i class="fas fa-save me-1"></i> ບັນທຶກ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ============ ການສົ່ງຟອມບັນທຶກປະເພດລາຍຈ່າຍ ============
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#category_code').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລະຫັດປະເພດລາຍຈ່າຍ', confirmButtonColor: '#dc3545' });
            return;
        }
        if ($('#category_name').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່ປະເພດລາຍຈ່າຍ', confirmButtonColor: '#dc3545' });
            return;
        }
        
        let formData = $(this).serialize();
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/expense_category_api.php',
            type: 'POST',
            data: formData,
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
                    $('#categoryModal').modal('hide');
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກ');
                let msg = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຂໍ້ມູນ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg, confirmButtonColor: '#dc3545' });
            }
        });
    });

    // Search Category in Javascript
    $('#searchInput').on('input', function() {
        var query = $(this).val().toLowerCase().trim();
        var count = 0;
        
        $('.category-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                $(this).show();
                count++;
            } else {
                $(this).hide();
            }
        });
        
        $('#categoryCount').text(count);
    });
});

function openCreateModal() {
    $('#formAction').val('create');
    $('#formCategoryId').val('');
    $('#categoryForm')[0].reset();
    $('#modalTitle').text('ເພີ່ມປະເພດລາຍຈ່າຍໃໝ່');
    $('#categoryModal').modal('show');
}

function openEditModal(categoryId) {
    if (!categoryId) return;

    $.ajax({
        url: '../api/expense_category_api.php',
        type: 'GET',
        data: { action: 'get', category_id: categoryId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let c = res.category;
                $('#formAction').val('update');
                $('#formCategoryId').val(c.category_id);
                $('#category_code').val(c.category_code);
                $('#category_name').val(c.category_name);
                
                $('#modalTitle').text('ແກ້ໄຂຂໍ້ມູນປະເພດລາຍຈ່າຍ');
                $('#categoryModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນປະເພດລາຍຈ່າຍໄດ້', confirmButtonColor: '#dc3545' });
        }
    });
}

function deleteCategory(categoryId) {
    if (!categoryId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບປະເພດລາຍຈ່າຍນີ້ແທ້ບໍ່? ຂໍ້ມູນຈະບໍ່ສາມາດກູ້ຄືນໄດ້!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/expense_category_api.php',
                type: 'POST',
                data: { action: 'delete', category_id: categoryId },
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
                    let msg = 'ບໍ່ສາມາດລົບປະເພດລາຍຈ່າຍໄດ້';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg, confirmButtonColor: '#dc3545' });
                }
            });
        }
    });
}
</script>
</body>
</html>
