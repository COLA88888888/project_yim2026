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
if (!hasPermission('subscriptions', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Fetch memberships subscriptions
$subscriptions = [];
$sql = "SELECT ms.*, mb.fname, mb.lname, mb.member_code, p.package_name, p.duration_days
        FROM memberships ms
        LEFT JOIN members mb ON ms.member_id = mb.member_id
        LEFT JOIN packages p ON ms.package_id = p.package_id
        ORDER BY ms.membership_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $subscriptions[] = $row;
    }
}

// Fetch active members for select dropdown
$members = [];
$memRes = mysqli_query($conn, "SELECT member_id, fname, lname, member_code FROM members WHERE status != 'Inactive' ORDER BY fname ASC");
if ($memRes) {
    while ($row = mysqli_fetch_assoc($memRes)) {
        $members[] = $row;
    }
}

// Fetch packages for select dropdown
$packages = [];
$pkgRes = mysqli_query($conn, "SELECT package_id, package_name, price, duration_days FROM packages ORDER BY price ASC");
if ($pkgRes) {
    while ($row = mysqli_fetch_assoc($pkgRes)) {
        $packages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລົງທະບຽນແພັກເກດຍິມ</title>
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
        /* Receipt Print Styling (CSS only for print) */
        @media print {
            body * {
                visibility: hidden;
            }
            #printArea, #printArea * {
                visibility: visible;
            }
            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i> ລົງທະບຽນແພັກເກດ & ຊຳລະເງິນ
            </h4>
            <p class="text-muted small mb-0">ລົງທະບຽນສະໝັກແພັກເກດເຂົ້າໃຊ້ບໍລິການໃຫ້ສະມາຊິກ, ບັນທຶກລາຍຮັບ ແລະ ພິມໃບບິນ</p>
        </div>
        <div>
            <?php if (hasPermission('subscriptions', 'add')): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-cart-plus me-1"></i> ລົງທະບຽນໃໝ່
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
                        ລາຍການທັງໝົດ: <span class="fw-bold text-primary" id="subCount"><?= count($subscriptions) ?></span> ລາຍການ
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
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາການລົງທະບຽນ...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">ລະຫັດບັດ</th>
                            <th>ສະມາຊິກ</th>
                            <th>ແພັກເກດ</th>
                            <th class="text-center">ວັນເລີ່ມຕົ້ນ</th>
                            <th class="text-center">ວັນໝົດອາຍຸ</th>
                            <th class="text-end">ຄ່າບໍລິການ</th>
                            <th class="text-center">ຊຳລະໂດຍ</th>
                            <th class="text-center">ສະຖານະ</th>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody id="subTableBody">
                        <?php if (empty($subscriptions)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-receipt fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີຂໍ້ມູນການລົງທະບຽນ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscriptions as $s): ?>
                                <tr class="sub-row">
                                    <td class="text-center"><code><?= htmlspecialchars($s['member_code'] ?? 'ບໍ່ລະບຸ') ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars(($s['fname'] ?? 'ລົບແລ້ວ') . ' ' . ($s['lname'] ?? '')) ?></td>
                                    <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($s['package_name'] ?? 'ບໍ່ລະບຸ') ?></span></td>
                                    <td class="text-center"><?= date('d/m/Y', strtotime($s['start_date'])) ?></td>
                                    <td class="text-center fw-bold"><?= date('d/m/Y', strtotime($s['end_date'])) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($s['price_paid']) ?></td>
                                    <td class="text-center text-muted"><?= htmlspecialchars($s['payment_method']) ?></td>
                                    <td class="text-center">
                                        <?php if ($s['status'] === 'Active' && $s['end_date'] >= date('Y-m-d')): ?>
                                            <span class="badge bg-success-light text-success px-3 py-1.5" style="border-radius: 20px;">Active / ປົກກະຕິ</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-light text-danger px-3 py-1.5" style="border-radius: 20px;">Expired / ໝົດອາຍຸ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-info btn-sm btn-action" onclick="printReceipt(<?= $s['membership_id'] ?>)" title="ພິມໃບບິນ">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <?php if (hasPermission('subscriptions', 'delete')): ?>
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteSubscription(<?= $s['membership_id'] ?>)" title="ລົບ">
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

<!-- Modal ລົງທະບຽນ -->
<div class="modal fade" id="subModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-cart-plus me-1"></i> ລົງທະບຽນສະໝັກແພັກເກດ</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="subForm">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ເລືອກສະມາຊິກ</label>
                        <select name="member_id" id="member_id" class="form-control" style="height: 45px;">
                            <option value="">-- ເລືອກສະມາຊິກ --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['member_id'] ?>"><?= htmlspecialchars($m['fname'] . ' ' . $m['lname']) ?> (<?= htmlspecialchars($m['member_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ເລືອກແພັກເກດ</label>
                        <select name="package_id" id="package_id" class="form-control" style="height: 45px;">
                            <option value="">-- ເລືອກແພັກເກດ --</option>
                            <?php foreach ($packages as $p): ?>
                                <option value="<?= $p['package_id'] ?>" data-price="<?= $p['price'] ?>" data-days="<?= $p['duration_days'] ?>"><?= htmlspecialchars($p['package_name']) ?> (<?= formatCurrency($p['price']) ?> - <?= $p['duration_days'] ?> ມື້)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ວັນເລີ່ມຕົ້ນແພັກເກດ</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ຈ່າຍຈິງ (ກີບ)</label>
                            <input type="text" name="price_paid" id="price_paid" class="form-control price-input" placeholder="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ວິທີຊຳລະເງິນ</label>
                        <select name="payment_method" id="payment_method_sel" class="form-control" style="height: 45px;">
                            <option value="ເງິນສົດ">ເງິນສົດ (Cash)</option>
                            <option value="ໂອນຜ່ານ QR">ໂອນຜ່ານ QR (OnePay)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="submit" class="btn btn-success fw-bold px-4" id="saveBtn"><i class="fas fa-save me-1"></i> ລົງທະບຽນ & ບັນທຶກ</button>
                    <button type="button" class="btn btn-secondary fw-bold" data-dismiss="modal">ຍົກເລີກ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dynamic Printable Receipt Template (hidden in desktop view) -->
<div id="printArea" style="display: none; font-family: 'Noto Sans Lao Looped', Arial, sans-serif; padding: 30px; color: #333;">
    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0; font-weight: 700; color: #111;">ຍິມ & ຟິດເນັດ 2026</h2>
        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">ໂທລະສັບ: 020 99999999 | ທີ່ຢູ່: ຍິມ ຟິດເນັດ, ນະຄອນຫຼວງວຽງຈັນ</p>
        <h3 style="margin: 15px 0 0 0; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 10px;">ໃບບິນຮັບເງິນ / Receipt</h3>
    </div>
    
    <table style="width: 100%; margin-bottom: 20px; font-size: 0.95rem; line-height: 1.6;">
        <tr>
            <td style="width: 50%;"><strong>ເລກທີບິນ:</strong> <span id="printReceiptId">#000</span></td>
            <td style="width: 50%; text-align: right;"><strong>ວັນທີຊຳລະ:</strong> <span id="printReceiptDate">01/01/2026</span></td>
        </tr>
        <tr>
            <td><strong>ລະຫັດສະມາຊິກ:</strong> <span id="printMemberCode">GYM0000</span></td>
            <td style="text-align: right;"><strong>ຊື່ສະມາຊິກ:</strong> <span id="printMemberName">ຊື່ ນາມສະກຸນ</span></td>
        </tr>
    </table>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 35px; font-size: 0.95rem;">
        <thead>
            <tr style="border-top: 2px solid #000; border-bottom: 2px solid #000; background-color: #f9f9f9;">
                <th style="padding: 10px; text-align: left;">ລາຍການແພັກເກດ</th>
                <th style="padding: 10px; text-align: center;">ໄລຍະເວລາ</th>
                <th style="padding: 10px; text-align: center;">ວັນເລີ່ມຕົ້ນ - ວັນໝົດອາຍຸ</th>
                <th style="padding: 10px; text-align: right;">ລາຄາຈ່າຍຈິງ</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 12px 10px;" id="printPackageName">ແພັກເກດລາຍເດືອນ</td>
                <td style="padding: 12px 10px; text-align: center;" id="printDuration">30 ມື້</td>
                <td style="padding: 12px 10px; text-align: center;" id="printDates">01/01/2026 - 31/01/2026</td>
                <td style="padding: 12px 10px; text-align: right; font-weight: bold;" id="printPrice">250,000 ₭</td>
            </tr>
        </tbody>
    </table>

    <div style="float: right; width: 300px; text-align: right; font-size: 1rem; line-height: 1.8;">
        <p style="margin: 0;"><strong>ຊຳລະດ້ວຍ:</strong> <span id="printPaymentMethod">ເງິນສົດ</span></p>
        <p style="margin: 10px 0 0 0; font-size: 1.2rem; border-top: 2px double #000; padding-top: 10px;"><strong>ຍອດລວມທັງໝົດ:</strong> <span id="printTotal" style="color: #28a745; font-weight: bold;">250,000 ₭</span></p>
    </div>
    
    <div style="clear: both; margin-top: 80px; text-align: center; font-size: 0.85rem; color: #666; border-top: 1px dashed #ccc; padding-top: 15px;">
        <p>ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການຍິມຂອງພວກເຮົາ! ຂໍໃຫ້ມີສຸຂະພາບທີ່ແຂງແຮງ.</p>
        <p style="margin-top: 5px;">Thank you for your business! Stay healthy.</p>
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
    // Dropdown change auto fill price
    $('#package_id').on('change', function() {
        let selected = $(this).find('option:selected');
        let price = selected.data('price') || 0;
        $('#price_paid').val(price).trigger('input');
    });

    // ============ ການສົ່ງຟອມບັນທຶກການສະໝັກແພັກເກດ (Save Subscription Form) ============
    $('#subForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບ
        
        // 1. ກວດສອບສະມາຊິກ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#member_id').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກສະມາຊິກ', confirmButtonColor: '#007bff' });
            return;
        }
        // 2. ກວດສອບແພັກເກດ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#package_id').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກແພັກເກດ', confirmButtonColor: '#007bff' });
            return;
        }
        // 3. ກວດສອບວັນທີເລີ່ມຕົ້ນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#start_date').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກວັນທີເລີ່ມຕົ້ນ', confirmButtonColor: '#007bff' });
            return;
        }
        // 4. ກວດສອບລາຄາທີ່ຈ່າຍ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#price_paid').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລາຄາທີ່ຈ່າຍ', confirmButtonColor: '#007bff' });
            return;
        }
        // 5. ກວດສອບວິທີຊຳລະເງິນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#payment_method_sel').val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກວິທີຊຳລະເງິນ', confirmButtonColor: '#007bff' });
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
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງລົງທະບຽນ...');

        $.ajax({
            url: '../api/subscription_api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ລົງທະບຽນ & ບັນທຶກ');
                if (res.success) {
                    $('#subModal').modal('hide');
                    if (window.parent && typeof window.parent.refreshNotifications === 'function') {
                        window.parent.refreshNotifications();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'ລົງທະບຽນສຳເລັດ',
                        text: res.message,
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '<i class="fas fa-print me-1"></i> ພິມໃບບິນຮັບເງິນ',
                        cancelButtonText: 'ປິດ'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            printReceipt(res.membership_id);
                        } else {
                            location.reload();
                        }
                    });
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ລົງທະບຽນ & ບັນທຶກ');
                let msg = 'ເກີດຂໍ້ຜິດພາດໃນການລົງທະບຽນ';
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
        
        $('.sub-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#subCount').text(filteredRows.length);
        
        if (filteredRows.length === 0 && $('.sub-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#subTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນການລົງທະບຽນ</td></tr>`
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
            $('.sub-row').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.sub-row').hide();
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

    // Check if search parameter exists in URL query
    let urlParams = new URLSearchParams(window.location.search);
    let searchParam = urlParams.get('search');
    if (searchParam) {
        $('#searchInput').val(searchParam);
    }

    // Run pagination
    updateFilteredRows();
    showPage(1);
});

function openCreateModal() {
    $('#subForm')[0].reset();
    $('#start_date').val(new Date().toISOString().substring(0, 10));
    $('#subModal').modal('show');
}

function deleteSubscription(membershipId) {
    if (!membershipId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບລາຍການສະໝັກນີ້ແທ້ບໍ່? ລະບົບຈະຄືນສະຖານະສະມາຊິກເປັນໝົດອາຍຸ ຖ້າບໍ່ມີແພັກເກດອື່ນເຮັດວຽກ!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/subscription_api.php',
                type: 'POST',
                data: { action: 'delete', membership_id: membershipId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        if (window.parent && typeof window.parent.refreshNotifications === 'function') {
                            window.parent.refreshNotifications();
                        }
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
                    let msg = 'ບໍ່ສາມາດລົບຂໍ້ມູນໄດ້';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
                }
            });
        }
    });
}

function printReceipt(membershipId) {
    if (!membershipId) return;

    $.ajax({
        url: '../api/subscription_api.php',
        type: 'GET',
        data: { action: 'get', membership_id: membershipId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let s = res.subscription;
                
                // Format values
                let dateStr = new Date(s.created_at).toLocaleDateString('lo-LA', {day: 'numeric', month: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'});
                let startFormatted = new Date(s.start_date).toLocaleDateString('lo-LA');
                let endFormatted = new Date(s.end_date).toLocaleDateString('lo-LA');
                let priceFormatted = Number(s.price_paid).toLocaleString() + ' ₭';

                // Populate print area
                $('#printReceiptId').text('#' + String(s.membership_id).padStart(5, '0'));
                $('#printReceiptDate').text(dateStr);
                $('#printMemberCode').text(s.member_code);
                $('#printMemberName').text(s.fname + ' ' + s.lname);
                $('#printPackageName').text(s.package_name);
                $('#printDuration').text(s.duration_days + ' ມື້');
                $('#printDates').text(startFormatted + ' - ' + endFormatted);
                $('#printPrice').text(priceFormatted);
                $('#printPaymentMethod').text(s.payment_method);
                $('#printTotal').text(priceFormatted);

                // Print sequence
                let printContent = document.getElementById('printArea').innerHTML;
                let originalContent = document.body.innerHTML;
                
                // Open new window to print beautifully without sidebar/navbar interferance
                let printWindow = window.open('', '_blank', 'height=600,width=800');
                printWindow.document.write('<html><head><title>Print Receipt</title>');
                printWindow.document.write('<style>');
                printWindow.document.write('body { font-family: "Noto Sans Lao Looped", sans-serif; padding: 20px; }');
                printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
                printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; }');
                printWindow.document.write('th { background-color: #f2f2f2; }');
                printWindow.document.write('</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(printContent);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                
                // Delay slightly for font loading inside iframe window
                setTimeout(function() {
                    printWindow.focus();
                    printWindow.print();
                    printWindow.close();
                    
                    // Reload if printing newly registered one, to update table list
                    if (window.location.search.indexOf('new=1') === -1) {
                        location.reload();
                    }
                }, 500);
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນໃບບິນໄດ້' });
        }
    });
}
</script>
</body>
</html>
