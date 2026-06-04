<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Fetch categories
$categories = [];
$sql = "SELECT * FROM product_categories ORDER BY category_id ASC";
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
    <title>ຈັດການປະເພດສິນຄ້າ</title>
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
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-folder text-primary me-2"></i> ຈັດການປະເພດສິນຄ້າ
            </h4>
            <p class="text-muted small mb-0">ກຳນົດ ແລະ ບໍລິຫານປະເພດສິນຄ້າໃນຮ້ານຄ້າ (ເຊັ່ນ ເຄື່ອງດື່ມ, ອາຫານເສີມ, ເຄື່ອງກິລາ)</p>
        </div>
        <div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ເພີ່ມປະເພດສິນຄ້າໃໝ່
            </button>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <!-- Search & Control Header -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        ປະເພດສິນຄ້າທັງໝົດ: <span class="fw-bold text-primary" id="categoryCount"><?= count($categories) ?></span> ລາຍການ
                    </div>
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາປະເພດສິນຄ້າ...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 150px;">ລະຫັດປະເພດ</th>
                            <th>ຊື່ປະເພດສິນຄ້າ</th>
                            <th>ວັນທີບັນທຶກ</th>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນປະເພດສິນຄ້າ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <tr class="category-row">
                                    <td><span class="badge bg-light text-dark border"><?= $c['category_id'] ?></span></td>
                                    <td><span class="badge bg-info text-white"><?= htmlspecialchars($c['category_code']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($c['category_name']) ?></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(<?= $c['category_id'] ?>)" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteCategory(<?= $c['category_id'] ?>)" title="ລົບ">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal ເພີ່ມ/ແກ້ໄຂປະເພດສິນຄ້າ -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-plus me-1"></i> ເພີ່ມປະເພດສິນຄ້າໃໝ່</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="categoryForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="category_id" id="formCategoryId">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ລະຫັດປະເພດສິນຄ້າ</label>
                        <input type="text" name="category_code" id="category_code" class="form-control" placeholder="ຕົວຢ່າງ: CAT001..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ປະເພດສິນຄ້າ</label>
                        <input type="text" name="category_name" id="category_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ..." required>
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
    // ============ ການສົ່ງຟອມບັນທຶກປະເພດສິນຄ້າ ============
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#category_code').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລະຫັດປະເພດສິນຄ້າ', confirmButtonColor: '#007bff' });
            return;
        }
        if ($('#category_name').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ', confirmButtonColor: '#007bff' });
            return;
        }
        
        let formData = $(this).serialize();
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/category_api.php',
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
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
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
    $('#modalTitle').html('<i class="fas fa-plus me-1"></i> ເພີ່ມປະເພດສິນຄ້າໃໝ່');
    $('#categoryModal').modal('show');
}

function openEditModal(categoryId) {
    if (!categoryId) return;

    $.ajax({
        url: '../api/category_api.php',
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
                
                $('#modalTitle').html('<i class="fas fa-edit me-1"></i> ແກ້ໄຂຂໍ້ມູນປະເພດສິນຄ້າ');
                $('#categoryModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນປະເພດສິນຄ້າໄດ້' });
        }
    });
}

function deleteCategory(categoryId) {
    if (!categoryId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບປະເພດສິນຄ້ານີ້ແທ້ບໍ່?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/category_api.php',
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
                    let msg = 'ບໍ່ສາມາດລົບປະເພດສິນຄ້າໄດ້';
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
