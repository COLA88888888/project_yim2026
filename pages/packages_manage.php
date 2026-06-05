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
if (!hasPermission('packages', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Fetch packages
$packages = [];
$sql = "SELECT * FROM packages ORDER BY package_id ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $packages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການແພັກເກດຍິມ</title>
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
                <i class="fas fa-tags text-primary me-2"></i> ຈັດການແພັກເກດຍິມ
            </h4>
            <p class="text-muted small mb-0">ກຳນົດ ແລະ ບໍລິຫານແພັກເກດການເຂົ້າໃຊ້ງານ (ເຊັ່ນ 1 ເດືອນ, 3 ເດືອນ, 6 ເດືອນ, 1 ປີ)</p>
        </div>
        <div>
            <?php if (hasPermission('packages', 'add')): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ເພີ່ມແພັກເກດໃໝ່
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
                        ແພັກເກດທັງໝົດ: <span class="fw-bold text-primary" id="packageCount"><?= count($packages) ?></span> ແພັກເກດ
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
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາແພັກເກດ...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ຊື່ແພັກເກດ</th>
                            <th class="text-center">ໄລຍະເວລາ (ວັນ)</th>
                            <th class="text-end" style="min-width: 150px;">ລາຄາ</th>
                            <th>ລາຍລະອຽດແພັກເກດ</th>
                            <?php if (hasPermission('packages', 'edit') || hasPermission('packages', 'delete')): ?>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="packageTableBody">
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="<?= (hasPermission('packages', 'edit') || hasPermission('packages', 'delete')) ? 5 : 4 ?>" class="text-center py-5 text-muted">
                                    <i class="fas fa-tags fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນແພັກເກດ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($packages as $p): ?>
                                <tr class="package-row">
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($p['package_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $p['duration_days'] ?> ມື້</span></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($p['price']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($p['description']) ?></td>
                                    <?php if (hasPermission('packages', 'edit') || hasPermission('packages', 'delete')): ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <?php if (hasPermission('packages', 'edit')): ?>
                                            <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(<?= $p['package_id'] ?>)" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('packages', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deletePackage(<?= $p['package_id'] ?>)" title="ລົບ">
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
        <!-- <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="text-muted small" id="paginationInfo">
                ສະແດງ 1-10 ຈາກທັງໝົດ 10 ແພັກເກດ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div> -->
    </div>
</div>

<!-- Modal ເພີ່ມ/ແກ້ໄຂແພັກເກດ -->
<div class="modal fade" id="packageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-plus me-1"></i> ເພີ່ມແພັກເກດໃໝ່</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="packageForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="package_id" id="formPackageId">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊື່ແພັກເກດ</label>
                        <input type="text" name="package_name" id="package_name" class="form-control" placeholder="ກະລຸນາປ້ອນຊື່ແພັກເກດ..." >
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ໄລຍະເວລາ (ຈຳນວນວັນ)</label>
                            <input type="number" name="duration_days" id="duration_days" class="form-control" placeholder="30" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ລາຄາແພັກເກດ</label>
                            <input type="text" name="price" id="price" class="form-control price-input" placeholder="250,000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ລາຍລະອຽດ</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="ປ້ອນລາຍລະອຽດແພັກເກດ..."></textarea>
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
    // ============ ການສົ່ງຟອມບັນທຶກແພັກເກດ (Save Package Form) ============
    $('#packageForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບ
        
        // 1. ກວດສອບຊື່ແພັກເກດ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#package_name').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່ແພັກເກດ', confirmButtonColor: '#007bff' });
            return;
        }
        // 2. ກວດສອບຈຳນວນວັນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#duration_days').val().trim() === '' || parseInt($('#duration_days').val()) < 1) {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຈຳນວນວັນໃຫ້ຖືກຕ້ອງ', confirmButtonColor: '#007bff' });
            return;
        }
        // 3. ກວດສອບລາຄາແພັກເກດ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#price').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລາຄາແພັກເກດ', confirmButtonColor: '#007bff' });
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
        
        let formData = $(this).serialize();
        
        // Restore formatted values
        originalValues.forEach(function(item) {
            item.element.val(item.val);
        });
        
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/package_api.php',
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
                    $('#packageModal').modal('hide');
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
        
        $('.package-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#packageCount').text(filteredRows.length);
        
        if (filteredRows.length === 0 && $('.package-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#packageTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນແພັກເກດ</td></tr>`
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
            $('.package-row').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ແພັກເກດ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.package-row').hide();
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show();
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ແພັກເກດ');
        
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
    $('#formPackageId').val('');
    $('#packageForm')[0].reset();
    $('#modalTitle').html('<i class="fas fa-plus me-1"></i> ເພີ່ມແພັກເກດໃໝ່');
    $('#packageModal').modal('show');
}

function openEditModal(packageId) {
    if (!packageId) return;

    $.ajax({
        url: '../api/package_api.php',
        type: 'GET',
        data: { action: 'get', package_id: packageId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let p = res.package;
                $('#formAction').val('update');
                $('#formPackageId').val(p.package_id);
                $('#package_name').val(p.package_name);
                $('#duration_days').val(p.duration_days);
                $('#price').val(p.price).trigger('input');
                $('#description').val(p.description);
                
                $('#modalTitle').html('<i class="fas fa-edit me-1"></i> ແກ້ໄຂຂໍ້ມູນແພັກເກດ');
                $('#packageModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນແພັກເກດໄດ້' });
        }
    });
}

function deletePackage(packageId) {
    if (!packageId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບແພັກເກດນີ້ແທ້ບໍ່?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/package_api.php',
                type: 'POST',
                data: { action: 'delete', package_id: packageId },
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
                    let msg = 'ບໍ່ສາມາດລົບແພັກເກດໄດ້';
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
