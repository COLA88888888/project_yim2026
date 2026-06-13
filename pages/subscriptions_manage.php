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
        ORDER BY ms.created_at DESC, ms.membership_id DESC";
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
$gymSettings = getSystemSettings($conn);
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
                        <label class="form-label fw-bold">ລະຫັດສະມາຊິກ</label>
                        <input type="text" id="member_code_search" class="form-control" placeholder="ປ້ອນ ຫຼື ສະແກນລະຫັດສະມາຊິກ..." style="height: 45px;" autocomplete="off">
                        <input type="hidden" name="member_id" id="member_id">
                        <div id="member_display_info" class="mt-2 px-3 py-2 rounded border small bg-light">
                            <span class="text-muted"><i class="fas fa-info-circle me-1"></i> ປ້ອນລະຫັດສະມາຊິກເພື່ອສະແດງຂໍ້ມູນ</span>
                        </div>
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
<div id="printArea" style="display: none;">
    <div class="print-receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo-container">
                <img src="<?= htmlspecialchars($gymSettings['logo_path']) ?>" alt="<?= htmlspecialchars($gymSettings['gym_name']) ?>" style="max-height: 70px; width: auto; display: inline-block;">
            </div>
            <p class="receipt-address"><?= htmlspecialchars($gymSettings['address']) ?></p>
            <h5 class="receipt-title">ໃບບິນຮັບເງິນ / RECEIPT</h5>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-meta">
            <table>
                <tr>
                    <td style="width: 45%;"><b>ເລກທີບິນ:</b></td>
                    <td style="width: 55%; font-weight: bold;" id="printReceiptId">#00000</td>
                </tr>
                <tr>
                    <td><b>ວັນທີຊຳລະ:</b></td>
                    <td id="printReceiptDate">01/01/2026</td>
                </tr>
                <tr>
                    <td><b>ລະຫັດສະມາຊິກ:</b></td>
                    <td id="printMemberCode">GYM0000</td>
                </tr>
                <tr>
                    <td><b>ຊື່ສະມາຊິກ:</b></td>
                    <td id="printMemberName">ຊື່ ນາມສະກຸນ</td>
                </tr>
            </table>
        </div>
        <div class="receipt-divider"></div>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th class="text-start">ລາຍການແພັກເກດ</th>
                    <th class="text-end" style="width: 95px;">ລາຄາຈ່າຍຈິງ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start">
                        <div id="printPackageName" style="font-weight: bold;">ແພັກເກດລາຍເດືອນ</div>
                        <div style="font-size: 8.5px; color: #555; margin-top: 2px;">
                            ໄລຍະເວລາ: <span id="printDuration">30 ມື້</span><br>
                            (<span id="printDates">01/01/2026 - 31/01/2026</span>)
                        </div>
                    </td>
                    <td class="text-end" style="font-weight: bold; vertical-align: top;" id="printPrice">250,000 ກີບ</td>
                </tr>
            </tbody>
        </table>
        <div class="receipt-divider"></div>
        <div class="receipt-total-section">
            <div class="receipt-total-row">
                <span>ຊຳລະດ້ວຍ:</span>
                <span id="printPaymentMethod" style="font-weight: bold;">ເງິນສົດ</span>
            </div>
            <div class="receipt-total-row grand-total">
                <span>ຍອດລວມທັງໝົດ:</span>
                <span id="printTotal" style="color: #28a745; font-weight: bold;">250,000 ກີບ</span>
            </div>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-footer">
            <p>ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການຍິມຂອງພວກເຮົາ!<br>ຂໍໃຫ້ມີສຸຂະພາບທີ່ແຂງແຮງ.</p>
            <p style="margin-top: 4px;">Thank you for your business! Stay healthy.</p>
        </div>
    </div>
</div>

<script>
const activeMembers = <?= json_encode($members) ?>;

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
    // Lookup member by code (both local check and API fallback)
    function lookupMember(code) {
        code = code.trim();
        if (code === '') {
            $('#member_id').val('');
            $('#member_display_info').html('<span class="text-muted"><i class="fas fa-info-circle me-1"></i> ປ້ອນລະຫັດສະມາຊິກເພື່ອສະແດງຂໍ້ມູນ</span>')
                .removeClass('border-success border-danger bg-success-light bg-danger-light text-success text-danger');
            return;
        }

        // Local search first
        let member = activeMembers.find(m => m.member_code.toLowerCase() === code.toLowerCase());
        if (member) {
            $('#member_id').val(member.member_id);
            $('#member_display_info').html(`
                <div class="d-flex align-items-center text-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>
                        <strong>ຊື່ສະມາຊິກ:</strong> ${member.fname} ${member.lname} 
                        <span class="badge bg-success ms-2">${member.member_code}</span>
                    </div>
                </div>
            `).addClass('border-success bg-success-light').removeClass('border-danger bg-danger-light text-danger');
            return;
        }

        // API fallback search
        $.ajax({
            url: '../api/member_api.php',
            type: 'GET',
            data: { action: 'get', member_code: code },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.member) {
                    let m = res.member;
                    if (m.status === 'Inactive') {
                        $('#member_id').val('');
                        $('#member_display_info').html(`
                            <div class="d-flex align-items-center text-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <div>
                                    <strong>ສະມາຊິກ:</strong> ${m.fname} ${m.lname} (ລະຫັດຖືກລະງັບ / Inactive)
                                </div>
                            </div>
                        `).addClass('border-danger bg-danger-light').removeClass('border-success bg-success-light text-success');
                    } else {
                        $('#member_id').val(m.member_id);
                        $('#member_display_info').html(`
                            <div class="d-flex align-items-center text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <div>
                                    <strong>ຊື່ສະມາຊິກ:</strong> ${m.fname} ${m.lname} 
                                    <span class="badge bg-success ms-2">${m.member_code}</span>
                                </div>
                            </div>
                        `).addClass('border-success bg-success-light').removeClass('border-danger bg-danger-light text-danger');
                    }
                } else {
                    $('#member_id').val('');
                    $('#member_display_info').html(`
                        <div class="d-flex align-items-center text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>ບໍ່ພົບຂໍ້ມູນລະຫັດສະມາຊິກນີ້</div>
                        </div>
                    `).addClass('border-danger bg-danger-light').removeClass('border-success bg-success-light text-success');
                }
            },
            error: function() {
                $('#member_id').val('');
                $('#member_display_info').html(`
                    <div class="d-flex align-items-center text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>ບໍ່ພົບຂໍ້ມູນລະຫັດສະມາຊິກນີ້</div>
                    </div>
                `).addClass('border-danger bg-danger-light').removeClass('border-success bg-success-light text-success');
            }
        });
    }

    // Input events for searching
    $('#member_code_search').on('input change', function() {
        lookupMember($(this).val());
    });

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
                            printReceipt(res.membership_id, true);
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
    $('#member_code_search').val('');
    $('#member_id').val('');
    $('#member_display_info').html('<span class="text-muted"><i class="fas fa-info-circle me-1"></i> ປ້ອນລະຫັດສະມາຊິກເພື່ອສະແດງຂໍ້ມູນ</span>')
        .removeClass('border-success border-danger bg-success-light bg-danger-light text-success text-danger');
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

function printReceipt(membershipId, isNew = false) {
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
                let priceFormatted = Number(s.price_paid).toLocaleString() + ' ກີບ';

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

                // Print sequence using a hidden iframe (same reliable method as sales.php)
                let printContent = document.getElementById('printArea').innerHTML;
                
                // Remove any existing print frame
                $('#receiptPrintFrame').remove();
                
                // Create a hidden iframe
                let $iframe = $('<iframe id="receiptPrintFrame" style="position: absolute; width: 0; height: 0; border: none;"></iframe>');
                $('body').append($iframe);
                
                let iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
                
                iframeDoc.open();
                iframeDoc.write('<html><head><title>Print Receipt</title>');
                // Set base href to resolve local relative font files
                iframeDoc.write('<base href="' + window.location.origin + window.location.pathname + '">');
                iframeDoc.write('<link rel="stylesheet" href="../assets/css/local-font.css">');
                iframeDoc.write('<style>');
                iframeDoc.write('@media print { @page { size: 80mm auto; margin: 0; } body { margin: 0; padding: 4mm; } }');
                iframeDoc.write('body { font-family: "Noto Sans Lao", "Noto Sans Lao Looped", Arial, sans-serif; width: 72mm; margin: 0 auto; color: #000; background: #fff; font-size: 11px; line-height: 1.3; }');
                iframeDoc.write('.text-center { text-align: center; } .text-start { text-align: left; } .text-end { text-align: right; }');
                iframeDoc.write('.receipt-header { text-align: center; margin-bottom: 15px; }');
                iframeDoc.write('.receipt-logo-container { margin-bottom: 12px; }');
                iframeDoc.write('.receipt-logo { font-size: 16px; font-weight: bold; margin: 0 0 4px 0; color: #111; }');
                iframeDoc.write('.receipt-address { font-size: 9.5px; color: #555; margin: 0 0 6px 0; line-height: 1.4; }');
                iframeDoc.write('.receipt-title { font-size: 13px; font-weight: bold; margin: 10px 0; text-transform: uppercase; color: #28a745; letter-spacing: 0.5px; }');
                iframeDoc.write('.receipt-divider { border-top: 1px dashed #000; margin: 12px 0; }');
                iframeDoc.write('.receipt-meta { font-size: 10px; margin-bottom: 10px; } .receipt-meta table { width: 100%; } .receipt-meta td { padding: 3px 0; }');
                iframeDoc.write('.receipt-table { width: 100%; border-collapse: collapse; margin: 8px 0; }');
                iframeDoc.write('.receipt-table th { font-weight: bold; border-bottom: 1px solid #000; padding: 6px 0; font-size: 10.5px; }');
                iframeDoc.write('.receipt-table td { padding: 8px 0; font-size: 11px; vertical-align: top; line-height: 1.4; }');
                iframeDoc.write('.receipt-total-section { font-size: 11px; margin: 8px 0; } .receipt-total-row { display: flex; justify-content: space-between; padding: 4px 0; }');
                iframeDoc.write('.receipt-total-row.grand-total { font-size: 13.5px; font-weight: bold; margin-top: 8px; border-top: 1px dashed #000; padding-top: 8px; }');
                iframeDoc.write('.receipt-footer { text-align: center; margin-top: 20px; font-size: 10px; font-weight: bold; line-height: 1.4; }');
                iframeDoc.write('</style>');
                iframeDoc.write('</head><body>');
                iframeDoc.write(printContent);
                iframeDoc.write('</body></html>');
                iframeDoc.close();
                
                // Wait for fonts/assets to load inside the iframe
                setTimeout(function() {
                    $iframe[0].contentWindow.focus();
                    $iframe[0].contentWindow.print();
                    
                    if (isNew) {
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
