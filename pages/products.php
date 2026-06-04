<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Fetch products
$products = [];
$sql = "SELECT p.*, c.category_name, c.category_code 
        FROM products p 
        LEFT JOIN product_categories c ON p.category_id = c.category_id 
        ORDER BY p.product_id ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Fetch categories for dropdown
$categories = [];
$sqlCat = "SELECT * FROM product_categories ORDER BY category_id ASC";
$resCat = mysqli_query($conn, $sqlCat);
if ($resCat) {
    while ($row = mysqli_fetch_assoc($resCat)) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຂໍ້ມູນສິນຄ້າ</title>
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
                <i class="fas fa-box text-primary me-2"></i> ຈັດການຂໍ້ມູນສິນຄ້າ
            </h4>
            <p class="text-muted small mb-0">ກຳນົດ ແລະ ບໍລິຫານຂໍ້ມູນສິນຄ້າ, ລາຄາຂາຍ, ລາຄາຕົ້ນທຶນ ແລະ ຈຳນວນສິນຄ້າໃນສາງ</p>
        </div>
        <div>
            <?php if (!empty($categories)): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ເພີ່ມສິນຄ້າໃໝ່
            </button>
            <?php else: ?>
            <a href="product_categories.php" class="btn btn-warning rounded-pill px-4 shadow-sm">
                <i class="fas fa-exclamation-triangle me-1"></i> ກະລຸນາເພີ່ມປະເພດສິນຄ້າກ່ອນ
            </a>
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
                        ສິນຄ້າທັງໝົດ: <span class="fw-bold text-primary" id="productCount"><?= count($products) ?></span> ລາຍການ
                    </div>
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາສິນຄ້າ (ຊື່, ລະຫັດ, ຫຼື ປະເພດ)...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;" class="text-center">ຮູບ</th>
                            <th>ລະຫັດສິນຄ້າ</th>
                            <th>ຊື່ສິນຄ້າ</th>
                            <th>ປະເພດສິນຄ້າ</th>
                            <th class="text-end">ຕົ້ນທຶນ</th>
                            <th class="text-end">ລາຄາຂາຍ</th>
                            <th class="text-center">ຈຳນວນໃນສາງ</th>
                            <th>ໜ່ວຍ</th>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-boxes fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນສິນຄ້າ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr class="product-row">
                                    <td class="text-center">
                                        <?php if (!empty($p['image']) && file_exists('../uploads/products/' . $p['image'])): ?>
                                            <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="product" class="img-thumbnail rounded-circle" style="width: 45px; height: 45px; object-fit: cover; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center mx-auto" style="width: 45px; height: 45px;">
                                                <i class="fas fa-image text-muted" style="font-size: 1.2rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><code class="text-primary"><?= htmlspecialchars($p['product_code']) ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category_name']) ?> (<?= htmlspecialchars($p['category_code']) ?>)</span></td>
                                    <td class="text-end text-muted small"><?= formatCurrency($p['cost_price']) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($p['sale_price']) ?></td>
                                    <td class="text-center">
                                        <?php if ($p['quantity'] <= 5): ?>
                                            <span class="badge bg-danger text-white"><?= $p['quantity'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success text-white"><?= $p['quantity'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($p['unit']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(<?= $p['product_id'] ?>)" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteProduct(<?= $p['product_id'] ?>)" title="ລົບ">
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

<!-- Modal ເພີ່ມ/ແກ້ໄຂສິນຄ້າ -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-plus me-1"></i> ເພີ່ມສິນຄ້າໃໝ່</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="product_id" id="formProductId">
                
                <div class="modal-body p-4">
                    <!-- Image upload and preview row -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຮູບພາບສິນຄ້າ</label>
                        <div class="d-flex align-items-center gap-3 border p-2 rounded bg-light">
                            <div id="imagePreviewContainer" class="border rounded bg-white d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; overflow: hidden; min-width: 80px;">
                                <i class="fas fa-image text-muted fa-2x" id="previewIcon"></i>
                                <img src="" id="previewImg" class="img-fluid d-none" style="object-fit: cover; width: 100%; height: 100%;">
                            </div>
                            <div>
                                <input type="file" name="image" id="image" class="form-control form-control-sm mb-1" accept="image/*" onchange="previewProductImage(this)">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">ອະນຸຍາດ: JPG, PNG, WEBP (ສູງສຸດ 2MB)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ລະຫັດສິນຄ້າ:</label>
                            <input type="text" name="product_code" id="product_code" class="form-control" placeholder="ກະລຸນາປ້ອນລະຫັດສິນຄ້າ..." >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ປະເພດສິນຄ້າ:</label>
                            <select name="category_id" id="category_id" class="form-control" style="font-weight: bold;">
                                <option value="">-- ເລືອກປະເພດ --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ສິນຄ້າ:</label>
                        <input type="text" name="product_name" id="product_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ສິນຄ້າ..." >
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ລາຄາຕົ້ນທຶນ:</label>
                            <input type="text" name="cost_price" id="cost_price" class="form-control price-input" placeholder="ກະລຸນາປ້ອນລາຄາຕົ້ນທຶນ..." >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ລາຄາຂາຍ:</label>
                            <input type="text" name="sale_price" id="sale_price" class="form-control price-input" placeholder="ກະລຸນາປ້ອນລາຄາຂາຍ..." >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ໜ່ວຍນັບ:</label>
                        <input type="text" name="unit" id="unit" class="form-control" placeholder="ກະລຸນາປ້ອນໜ່ວຍນັບ..." >
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
// Function to format price with commas
$(document).on('input', '.price-input', function() {
    let val = this.value.replace(/\D/g, "");
    if (val === '') {
        this.value = '';
        return;
    }
    let formatted = val.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    let cursorPosition = this.selectionStart;
    let originalLength = this.value.length;
    this.value = formatted;
    let lengthDifference = formatted.length - originalLength;
    this.setSelectionRange(cursorPosition + lengthDifference, cursorPosition + lengthDifference);
});

$(document).on('keypress', '.price-input', function(e) {
    if (e.which < 48 || e.which > 57) {
        e.preventDefault();
    }
});

$(document).ready(function() {
    // ============ ການສົ່ງຟອມບັນທຶກສິນຄ້າ ============
    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#product_code').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລະຫັດສິນຄ້າ', confirmButtonColor: '#007bff' });
            return;
        }
        if ($('#category_id').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກປະເພດສິນຄ້າ', confirmButtonColor: '#007bff' });
            return;
        }
        if ($('#product_name').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່ສິນຄ້າ', confirmButtonColor: '#007bff' });
            return;
        }
        if ($('#sale_price').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລາຄາຂາຍ', confirmButtonColor: '#007bff' });
            return;
        }
        
        // Use FormData to support file upload
        let formData = new FormData(this);
        
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/product_api.php',
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
                    $('#productModal').modal('hide');
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

    // Search Products in JavaScript
    $('#searchInput').on('input', function() {
        var query = $(this).val().toLowerCase().trim();
        var count = 0;
        
        $('.product-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                $(this).show();
                count++;
            } else {
                $(this).hide();
            }
        });
        
        $('#productCount').text(count);
    });
});

function previewProductImage(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $('#previewIcon').addClass('d-none');
            $('#previewImg').attr('src', e.target.result).removeClass('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        resetImagePreview();
    }
}

function resetImagePreview(imageUrl = null) {
    if (imageUrl) {
        $('#previewIcon').addClass('d-none');
        $('#previewImg').attr('src', imageUrl).removeClass('d-none');
    } else {
        $('#previewIcon').removeClass('d-none');
        $('#previewImg').addClass('d-none').attr('src', '');
        $('#image').val('');
    }
}

function openCreateModal() {
    $('#formAction').val('create');
    $('#formProductId').val('');
    $('#productForm')[0].reset();
    $('#unit').val('ອັນ');
    resetImagePreview();
    $('#modalTitle').html('<i class="fas fa-plus me-1"></i> ເພີ່ມສິນຄ້າໃໝ່');
    $('#productModal').modal('show');
}

function openEditModal(productId) {
    if (!productId) return;

    $.ajax({
        url: '../api/product_api.php',
        type: 'GET',
        data: { action: 'get', product_id: productId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let p = res.product;
                $('#formAction').val('update');
                $('#formProductId').val(p.product_id);
                $('#product_code').val(p.product_code);
                $('#category_id').val(p.category_id);
                $('#product_name').val(p.product_name);
                $('#cost_price').val(Math.round(parseFloat(p.cost_price) || 0)).trigger('input');
                $('#sale_price').val(Math.round(parseFloat(p.sale_price) || 0)).trigger('input');
                $('#unit').val(p.unit);
                
                if (p.image && p.image !== '') {
                    resetImagePreview('../uploads/products/' + p.image);
                } else {
                    resetImagePreview();
                }
                
                $('#modalTitle').html('<i class="fas fa-edit me-1"></i> ແກ້ໄຂຂໍ້ມູນສິນຄ້າ');
                $('#productModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນສິນຄ້າໄດ້' });
        }
    });
}

function deleteProduct(productId) {
    if (!productId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບສິນຄ້ານີ້ແທ້ບໍ່?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/product_api.php',
                type: 'POST',
                data: { action: 'delete', product_id: productId },
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
                    let msg = 'ບໍ່ສາມາດລົບສິນຄ້າໄດ້';
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
