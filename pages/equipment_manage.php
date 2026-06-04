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
if (!hasPermission('equipment', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Fetch equipment
$equipment = [];
$sql = "SELECT * FROM equipment ORDER BY equipment_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $equipment[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການເຄື່ອງອອກກຳລັງກາຍ</title>
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
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-dumbbell text-primary me-2"></i> ຈັດການເຄື່ອງອອກກຳລັງກາຍ
            </h4>
            <p class="text-muted small mb-0">ບໍລິຫານຈັດການ ແລະ ຕິດຕາມສະພາບອຸປະກອນ, ເຄື່ອງຫຼິ້ນຟິດເນັດທັງໝົດໃນຍິມ</p>
        </div>
        <div>
            <?php if (hasPermission('equipment', 'add')): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ເພີ່ມເຄື່ອງມືໃໝ່
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
                        ອຸປະກອນທັງໝົດ: <span class="fw-bold text-primary" id="eqCount"><?= count($equipment) ?></span> ລາຍການ
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
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາອຸປະກອນ...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">ລະຫັດບາໂຄດ</th>
                            <th>ຊື່ອຸປະກອນ/ເຄື່ອງຫຼິ້ນ</th>
                            <th>ຍີ່ຫໍ້ - ລຸ້ນ</th>
                            <th class="text-center">ຈຳນວນ</th>
                            <th class="text-end">ລາຄາຊື້</th>
                            <th class="text-center">ວັນທີຊື້</th>
                            <th class="text-center">ສະພາບການໃຊ້ງານ</th>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody id="eqTableBody">
                        <?php if (empty($equipment)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-dumbbell fa-2x mb-3 d-block text-secondary"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນເຄື່ອງອອກກຳລັງກາຍ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipment as $e): ?>
                                <tr class="eq-row">
                                    <td class="text-center"><code><?= htmlspecialchars($e['equipment_code']) ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($e['equipment_name']) ?></td>
                                    <td><?= htmlspecialchars($e['brand_model'] ?: '-') ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $e['quantity'] ?> ເຄື່ອງ</span></td>
                                    <td class="text-end fw-bold"><?= formatCurrency($e['price']) ?></td>
                                    <td class="text-center text-muted"><?= $e['purchase_date'] ? date('d/m/Y', strtotime($e['purchase_date'])) : '-' ?></td>
                                    <td class="text-center">
                                        <?php if ($e['status'] === 'ດີ'): ?>
                                            <span class="badge bg-success-light text-success px-3 py-1.5" style="border-radius: 20px;"><i class="fas fa-check-circle me-1"></i>ດີ / ພ້ອມໃຊ້</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-light text-danger px-3 py-1.5" style="border-radius: 20px;"><i class="fas fa-wrench me-1"></i>ເພ / ຊຳລຸດ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-info btn-sm btn-action text-white" onclick="viewEquipment(<?= $e['equipment_id'] ?>)" title="ເບິ່ງລາຍລະອຽດ">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (hasPermission('equipment', 'edit')): ?>
                                            <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(<?= $e['equipment_id'] ?>)" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('equipment', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteEquipment(<?= $e['equipment_id'] ?>)" title="ລົບ">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
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
                ສະແດງ 1-10 ຈາກທັງໝົດ 10 ລາຍການ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal ເພີ່ມ/ແກ້ໄຂອຸປະກອນ -->
<div class="modal fade" id="eqModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-plus me-1"></i> ເພີ່ມເຄື່ອງອອກກຳລັງກາຍ</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="eqForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="equipment_id" id="formEqId">
                
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ລະຫັດອຸປະກອນ (ບາໂຄດ)</label>
                            <input type="text" name="equipment_code" id="equipment_code" class="form-control" placeholder="ຕົວຢ່າງ: EQ-001...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ຊື່ອຸປະກອນ/ເຄື່ອງຫຼິ້ນ</label>
                            <input type="text" name="equipment_name" id="equipment_name" class="form-control" placeholder="ຕົວຢ່າງ: Dumbbell 10kg...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ຍີ່ຫໍ້ - ລຸ້ນ</label>
                            <input type="text" name="brand_model" id="brand_model" class="form-control" placeholder="ຕົວຢ່າງ: GymMax X1...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ຈຳນວນ (ເຄື່ອງ/ອັນ)</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ລາຄາຊື້ (ກີບ)</label>
                            <input type="text" name="price" id="price" class="form-control price-input" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ວັນທີຊື້</label>
                            <input type="date" name="purchase_date" id="purchase_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ສະພາບອຸປະກອນ</label>
                            <select name="status" id="status" class="form-control">
                                <option value="ດີ">ດີ / ພ້ອມໃຊ້ງານ (Good)</option>
                                <option value="ເພ">ເພ / ຊຳລຸດ (Broken)</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">ຮູບພາບອຸປະກອນ</label>
                            <div class="d-flex align-items-center gap-3">
                                <img id="imagePreview" src="../assets/img/equipment/default_eq.png" alt="Preview" class="img-thumbnail" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                                <div class="flex-grow-1">
                                    <input type="file" name="equipment_img" id="equipment_img_input" class="form-control" accept="image/*" onchange="previewImage(this)">
                                    <small class="text-muted">ອະນຸຍາດສະເພາະ JPG, PNG, WEBP, GIF ບໍ່ເກີນ 5MB</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">ລາຍລະອຽດເພີ່ມເຕີມ/ໝາຍເຫດ</label>
                            <textarea name="description" id="description" class="form-control" rows="2" placeholder="ປ້ອນລາຍລະອຽດ..."></textarea>
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

<!-- Modal ເບິ່ງລາຍລະອຽດອຸປະກອນ -->
<div class="modal fade" id="viewEqModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-info text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold"><i class="fas fa-eye me-1"></i> ລາຍລະອຽດອຸປະກອນ</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="row align-items-center">
                    <!-- Left: Large Image Display -->
                    <div class="col-md-5 text-center mb-3 mb-md-0">
                        <img id="viewEqImg" src="../assets/img/equipment/default_eq.png" alt="Equipment Image" class="img-fluid rounded shadow-sm w-100" style="max-height: 280px; object-fit: cover; border-radius: 12px !important; border: 3px solid #dee2e6;">
                    </div>
                    
                    <!-- Right: Details -->
                    <div class="col-md-7">
                        <h4 class="fw-bold text-dark mb-1" id="viewEqName">ຊື່ອຸປະກອນ</h4>
                        <p class="text-muted mb-3" style="font-size: 0.95rem;">ຍີ່ຫໍ້ - ລຸ້ນ: <span id="viewEqBrand" class="fw-bold text-secondary">ຍີ່ຫໍ້</span></p>
                        
                        <hr class="my-2">
                        
                        <div class="row g-2" style="font-size: 0.9rem;">
                            <div class="col-6 mb-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">ລະຫັດບາໂຄດ:</span>
                                <code class="h6 fw-bold" id="viewEqCode">-</code>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">ຈຳນວນ:</span>
                                <span id="viewEqQty">-</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">ລາຄາຊື້:</span>
                                <span class="h6 fw-bold text-success" id="viewEqPrice">-</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">ວັນທີຊື້:</span>
                                <span class="h6 fw-bold text-dark" id="viewEqDate">-</span>
                            </div>
                            <div class="col-12 mb-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">ສະພາບອຸປະກອນ:</span>
                                <span id="viewEqStatus">-</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">ໝາຍເຫດ/ລາຍລະອຽດ:</span>
                                <p class="text-dark bg-light p-2 rounded mb-0" id="viewEqDesc" style="white-space: pre-wrap; font-size: 0.85rem; border: 1px solid #e9ecef; min-height: 50px;"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-secondary fw-bold px-4" data-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to format number with commas
function formatNumberWithCommas(value) {
    value = value.replace(/\D/g, "");
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

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
    // ============ ການສົ່ງຟອມບັນທຶກອຸປະກອນ (Save Equipment Form) ============
    $('#eqForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບ
        
        // 1. ກວດສອບລະຫັດອຸປະກອນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#equipment_code').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລະຫັດອຸປະກອນ', confirmButtonColor: '#007bff' });
            return;
        }
        // 2. ກວດສອບຊື່ອຸປະກອນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#equipment_name').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່ອຸປະກອນ', confirmButtonColor: '#007bff' });
            return;
        }
        // 3. ກວດສອບຈຳນວນອຸປະກອນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#quantity').val().trim() === '' || parseInt($('#quantity').val()) < 1) {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຈຳນວນທີ່ຖືກຕ້ອງ', confirmButtonColor: '#007bff' });
            return;
        }
        // 4. ກວດສອບລາຄາອຸປະກອນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#price').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລາຄາ', confirmButtonColor: '#007bff' });
            return;
        }
        
        // Temporarily remove commas from price inputs for serialization
        let priceInputs = $(this).find('.price-input');
        let originalValues = [];
        priceInputs.each(function() {
            originalValues.push({
                element: $(this),
                val: $(this).val()
            });
            $(this).val($(this).val().replace(/,/g, ''));
        });
        
        let formData = new FormData(this);
        
        // Restore formatted values
        originalValues.forEach(function(item) {
            item.element.val(item.val);
        });
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/equipment_api.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
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
                    $('#eqModal').modal('hide');
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
        
        $('.eq-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#eqCount').text(filteredRows.length);
        
        if (filteredRows.length === 0 && $('.eq-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#eqTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນເຄື່ອງອອກກຳລັງກາຍ</td></tr>`
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
            $('.eq-row').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.eq-row').hide();
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show();
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ລາຍການ');
        
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

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        $('#imagePreview').attr('src', '../assets/img/equipment/default_eq.png');
    }
}

function openCreateModal() {
    $('#formAction').val('create');
    $('#formEqId').val('');
    $('#eqForm')[0].reset();
    $('#imagePreview').attr('src', '../assets/img/equipment/default_eq.png');
    $('#equipment_img_input').val('');
    $('#modalTitle').html('<i class="fas fa-plus me-1"></i> ເພີ່ມເຄື່ອງອອກກຳລັງກາຍ');
    $('#eqModal').modal('show');
}

function openEditModal(equipmentId) {
    if (!equipmentId) return;

    $.ajax({
        url: '../api/equipment_api.php',
        type: 'GET',
        data: { action: 'get', equipment_id: equipmentId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let e = res.equipment;
                $('#formAction').val('update');
                $('#formEqId').val(e.equipment_id);
                $('#equipment_code').val(e.equipment_code);
                $('#equipment_name').val(e.equipment_name);
                $('#brand_model').val(e.brand_model);
                $('#quantity').val(e.quantity);
                $('#price').val(e.price).trigger('input');
                $('#purchase_date').val(e.purchase_date);
                $('#status').val(e.status);
                $('#description').val(e.description);
                
                $('#imagePreview').attr('src', '../assets/img/equipment/' + (e.equipment_img || 'default_eq.png'));
                $('#equipment_img_input').val('');
                $('#modalTitle').html('<i class="fas fa-edit me-1"></i> ແກ້ໄຂຂໍ້ມູນອຸປະກອນ');
                $('#eqModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນອຸປະກອນໄດ້' });
        }
    });
}

function deleteEquipment(equipmentId) {
    if (!equipmentId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບອຸປະກອນນີ້ແທ້ບໍ່?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/equipment_api.php',
                type: 'POST',
                data: { action: 'delete', equipment_id: equipmentId },
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
                    let msg = 'ບໍ່ສາມາດລົບອຸປະກອນໄດ້';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
                }
            });
        }
    });
}

function viewEquipment(equipmentId) {
    if (!equipmentId) return;

    $.ajax({
        url: '../api/equipment_api.php',
        type: 'GET',
        data: { action: 'get', equipment_id: equipmentId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let e = res.equipment;
                $('#viewEqImg').attr('src', '../assets/img/equipment/' + (e.equipment_img || 'default_eq.png'));
                $('#viewEqName').text(e.equipment_name);
                $('#viewEqBrand').text(e.brand_model || '-');
                $('#viewEqCode').text(e.equipment_code);
                $('#viewEqQty').html('<span class="badge bg-light text-dark border">' + e.quantity + ' ເຄື່ອງ</span>');
                
                // Format price
                let formattedPrice = parseFloat(e.price).toLocaleString('en-US') + ' ₭';
                $('#viewEqPrice').text(formattedPrice);
                
                // Format date
                let formattedDate = '-';
                if (e.purchase_date) {
                    let d = new Date(e.purchase_date);
                    let day = ("0" + d.getDate()).slice(-2);
                    let month = ("0" + (d.getMonth() + 1)).slice(-2);
                    let year = d.getFullYear();
                    formattedDate = day + '/' + month + '/' + year;
                }
                $('#viewEqDate').text(formattedDate);
                
                // Status badge
                let statusBadge = '';
                if (e.status === 'ດີ') {
                    statusBadge = '<span class="badge bg-success-light text-success px-3 py-1.5" style="border-radius: 20px;"><i class="fas fa-check-circle me-1"></i>ດີ / ພ້ອມໃຊ້</span>';
                } else {
                    statusBadge = '<span class="badge bg-danger-light text-danger px-3 py-1.5" style="border-radius: 20px;"><i class="fas fa-wrench me-1"></i>ເພ / ຊຳລຸດ</span>';
                }
                $('#viewEqStatus').html(statusBadge);
                
                // Description
                $('#viewEqDesc').text(e.description || '-');
                
                $('#viewEqModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນອຸປະກອນໄດ້' });
        }
    });
}
</script>
</body>
</html>
