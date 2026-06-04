<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('daily_checkin', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Fetch today's records server-side for initial render
$today = date('Y-m-d');
$rows = [];
$res = mysqli_query($conn, "SELECT * FROM daily_checkins WHERE checkin_date = '$today' ORDER BY id DESC");
if ($res) while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;

$total_revenue = 0; $male_count = 0; $female_count = 0;
foreach ($rows as $r) {
    $total_revenue += (float)$r['price_paid'];
    if ($r['gender'] === 'ຊາຍ') $male_count++;
    if ($r['gender'] === 'ຍິງ') $female_count++;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຊັກອິນລູກຄ້າລາຍວັນ</title>
    <link rel="stylesheet" href="../assets/css/local-font.css">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif; background-color: #f4f6f9; }

        /* Gender Toggle Buttons */
        .gender-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 100%; padding: 20px 12px; border-radius: 16px; border: 2px solid #dee2e6;
            background: #ffffff; cursor: pointer; transition: all 0.2s ease; font-weight: 700;
            font-size: 1rem; color: #6c757d; gap: 8px;
        }
        .gender-btn i { font-size: 2rem; }
        .gender-btn:hover { border-color: #adb5bd; background: #f8f9fa; }
        .gender-btn.male-active {
            border-color: #1d6ef5; background: linear-gradient(135deg,#e8f0fe,#c2d6ff);
            color: #1d4ed8; box-shadow: 0 4px 14px rgba(29,110,245,0.18);
        }
        .gender-btn.female-active {
            border-color: #e91e8c; background: linear-gradient(135deg,#fce7f3,#fbcfe8);
            color: #be185d; box-shadow: 0 4px 14px rgba(233,30,140,0.16);
        }

        /* Stat mini-cards */
        .stat-mini {
            border-radius: 14px; padding: 16px 20px; color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .stat-mini .stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1.2; }
        .stat-mini .stat-label { font-size: 0.8rem; opacity: 0.85; font-weight: 600; }

        /* Price input */
        .price-input { font-weight: 700; font-size: 1.1rem; }

        /* Tight grid layout */
        .row-tight {
            display: flex;
            flex-wrap: wrap;
            margin-right: -8px !important;
            margin-left: -8px !important;
        }
        .row-tight > [class*='col-'] {
            padding-right: 8px !important;
            padding-left: 8px !important;
            margin-bottom: 16px !important;
        }
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-user-plus text-info me-2"></i> ເຊັກອິນລູກຄ້າລາຍວັນ
            </h4>
            <p class="text-muted small mb-0">ບັນທຶກຂໍ້ມູນລູກຄ້າທີ່ເຂົ້າໃຊ້ບໍລິການໂດຍບໍ່ສະໝັກສະມາຊິກ ຄິດຄ່າບໍລິການລາຍວັນ</p>
        </div>
        <div class="no-print">
            <button class="btn btn-outline-secondary rounded-pill px-4" onclick="window.print()">
                <i class="fas fa-print me-1"></i> ພິມລາຍງານ
            </button>
        </div>
    </div>

    <div class="row">
        <!-- ===== LEFT CARD: Form ===== -->
        <div class="col-lg-4 col-md-5 mb-4 no-print">
            <div class="card card-custom h-auto">
                <div class="card-header bg-info text-white fw-bold" style="border-top-left-radius:16px;border-top-right-radius:16px;padding:14px 20px;">
                    <i class="fas fa-clipboard-check me-2"></i> ຟອມເຊັກອິນ
                </div>
                <div class="card-body p-4">
                    <form id="checkinForm">

                        <!-- Gender Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">ເພດ <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="gender-btn" id="btn-male" onclick="selectGender('ຊາຍ')">
                                        <i class="fas fa-mars text-primary"></i>
                                        ຊາຍ
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="gender-btn" id="btn-female" onclick="selectGender('ຍິງ')">
                                        <i class="fas fa-venus" style="color:#e91e8c;"></i>
                                        ຍິງ
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="gender" id="gender_val">
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">ວິທີການຊຳລະເງິນ</label>
                            <select name="payment_method" id="payment_method" class="form-control" style="height:44px;font-weight:600;">
                                <option value="ເງິນສົດ">ເງິນສົດ</option>
                                <option value="ເງິນໂອນ">ເງິນໂອນ</option>
                            </select>
                        </div>

                        <!-- Price -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">ລາຄາ:</label>
                            <input type="text" name="price_paid" id="price_paid" class="form-control price-input"
                                   value="" placeholder="ກະລຸນາໃສ່ລາຄາ..." >
                        </div>

                        <!-- Date -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">ວັນທີ</label>
                            <input type="date" name="checkin_date" id="checkin_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" style="height:44px;font-weight:600;">
                        </div>

                        <button type="submit" class="btn btn-info text-white fw-bold w-100 rounded-pill py-2" id="submitBtn" style="font-size:1.05rem;">
                            <i class="fas fa-plus-circle me-1"></i> ບັນທຶກການເຊັກອິນ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== RIGHT CARD: Stats + History ===== -->
        <div class="col-lg-8 col-md-7 mb-4">

            <!-- Stats Cards Row -->
            <div class="row-tight">
                <div class="col-sm-4">
                    <div class="stat-mini" style="background:linear-gradient(135deg,#11998e,#38ef7d);">
                        <div class="stat-label"><i class="fas fa-dollar-sign me-1"></i> ລາຍຮັບວັນນີ້</div>
                        <div class="stat-value" id="statRevenue"><?= number_format($total_revenue, 0, '.', ',') ?> ₭</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-mini" style="background:linear-gradient(135deg,#1d6ef5,#6366f1);">
                        <div class="stat-label"><i class="fas fa-mars me-1"></i> ລູກຄ້າຊາຍ</div>
                        <div class="stat-value" id="statMale"><?= $male_count ?> ຄົນ</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-mini" style="background:linear-gradient(135deg,#e91e8c,#f953c6);">
                        <div class="stat-label"><i class="fas fa-venus me-1"></i> ລູກຄ້າຍິງ</div>
                        <div class="stat-value" id="statFemale"><?= $female_count ?> ຄົນ</div>
                    </div>
                </div>
            </div>

            <!-- History Table Card -->
            <div class="card card-custom">
                <div class="card-body p-0">
                    <!-- Search & Control Header -->
                    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="text-muted small">
                                ລາຍການມື້ນີ້: <span class="fw-bold text-info" id="totalCount"><?= count($rows) ?></span> ຄົນ
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">ສະແດງ:</span>
                                <select id="pageSizeSelect" class="form-control form-control-sm" style="width:80px;border-radius:8px;font-weight:bold;height:32px;">
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="all">ທັງໝົດ</option>
                                </select>
                            </div>
                        </div>
                        <div class="search-box flex-grow-1" style="max-width:350px;">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາ...">
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ວັນ-ເວລາ</th>
                                    <th class="text-center">ເພດ</th>
                                    <th class="text-end">ລາຄາ</th>
                                    <th class="text-center">ຊຳລະໂດຍ</th>
                                    <?php if (hasPermission('daily_checkin', 'delete')): ?>
                                    <th class="text-center" style="width:90px;">ຈັດການ</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <?php if (empty($rows)): ?>
                                    <tr id="emptyRow">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-user-slash fa-2x mb-3 d-block text-secondary"></i>
                                            ຍັງບໍ່ມີການເຊັກອິນໃນວັນນີ້
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                    <tr class="history-row" data-id="<?= $r['id'] ?>">
                                        <td class="text-muted small">
                                            <span class="d-block fw-bold text-dark" style="font-size:0.9rem;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                                            <span style="font-size:0.82rem;"><i class="fas fa-clock fa-xs me-1"></i><?= date('H:i:s', strtotime($r['created_at'])) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($r['gender'] === 'ຊາຍ'): ?>
                                                <span class="badge px-3 py-2" style="background:#dbeafe;color:#1d4ed8;border-radius:12px;font-size:0.85rem;font-weight:700;">
                                                    <i class="fas fa-mars me-1"></i> ຊາຍ
                                                </span>
                                            <?php else: ?>
                                                <span class="badge px-3 py-2" style="background:#fce7f3;color:#be185d;border-radius:12px;font-size:0.85rem;font-weight:700;">
                                                    <i class="fas fa-venus me-1"></i> ຍິງ
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-success"><?= number_format((float)$r['price_paid'], 0, '.', ',') ?> ₭</td>
                                        <td class="text-center text-muted small"><?= htmlspecialchars($r['payment_method']) ?></td>
                                        <?php if (hasPermission('daily_checkin', 'delete')): ?>
                                        <td class="text-center">
                                            <button class="btn btn-danger btn-sm btn-action" onclick="deleteRecord(<?= $r['id'] ?>)" title="ລົບ">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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
                <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
                    <div class="text-muted small" id="paginationInfo">ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ</div>
                    <nav><ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul></nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============ ການເລືອກເພດ (Gender Selection) ============
// ຟັງຊັນນີ້ໃຊ້ຈັດການສະຖານະການເລືອກເພດ ແລະ ເຊື່ອງ/ສະແດງປຸ່ມເພດທີ່ຖືກເລືອກ
var selectedGender = '';

function selectGender(g) {
    selectedGender = g;
    $('#gender_val').val(g);
    $('#btn-male').removeClass('male-active female-active');
    $('#btn-female').removeClass('male-active female-active');
    if (g === 'ຊາຍ') $('#btn-male').addClass('male-active');
    else               $('#btn-female').addClass('female-active');
}

// ============ ການກຳນົດຮູບແບບລາຄາ (Price Format & Input Control) ============
// ຟັງຊັນນີ້ອະນຸຍາດໃຫ້ປ້ອນລາຄາໄດ້ຢ່າງເສລີ ແລະ ໃສ່ເຄື່ອງໝາຍຈຸດ (,) ຄັ່ນຫຼັກພັນອັດຕະໂນມັດ
$(document).on('input', '#price_paid', function() {
    let val = this.value.replace(/\D/g, '');
    this.value = val === '' ? '' : val.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
});

// ============ Stats Refresh ============
function refreshStats() {
    var date = $('#checkin_date').val() || '<?= $today ?>';
    $.getJSON('../api/daily_checkin_api.php', { action: 'get_stats', date: date }, function(res) {
        if (res.success) {
            var s = res.stats;
            var rev = parseFloat(s.total_revenue || 0);
            $('#statRevenue').text(rev.toLocaleString('en') + ' ₭');
            $('#statMale').text((s.male_count || 0) + ' ຄົນ');
            $('#statFemale').text((s.female_count || 0) + ' ຄົນ');
        }
    });
}

// ============ Add Row to Table ============
function prependRow(id, timeStr, gender, pricePaid, payMethod) {
    $('#emptyRow').remove();
    var newIdx = $('.history-row').length + 1;
    var genderBadge = gender === 'ຊາຍ'
        ? '<span class="badge px-3 py-2" style="background:#dbeafe;color:#1d4ed8;border-radius:12px;font-size:0.85rem;font-weight:700;"><i class="fas fa-mars me-1"></i> ຊາຍ</span>'
        : '<span class="badge px-3 py-2" style="background:#fce7f3;color:#be185d;border-radius:12px;font-size:0.85rem;font-weight:700;"><i class="fas fa-venus me-1"></i> ຍິງ</span>';
    var priceFormatted = parseFloat(pricePaid.replace(',', '')).toLocaleString('en') + ' ₭';
    var deleteBtn = <?= hasPermission('daily_checkin', 'delete') ? 'true' : 'false' ?> ?
        '<td class="text-center"><button class="btn btn-danger btn-sm btn-action" onclick="deleteRecord(' + id + ')" title="ລົບ"><i class="fas fa-trash-alt"></i></button></td>' : '';
    var parts = timeStr.split('|');
    var datePart = parts[0] || '';
    var timePart = parts[1] || timeStr;
    var row = '<tr class="history-row" data-id="' + id + '">'
        + '<td class="text-muted small"><span class="d-block fw-bold text-dark" style="font-size:0.9rem;">' + datePart + '</span><span style="font-size:0.82rem;"><i class="fas fa-clock fa-xs me-1"></i>' + timePart + '</span></td>'
        + '<td class="text-center">' + genderBadge + '</td>'
        + '<td class="text-end fw-bold text-success">' + priceFormatted + '</td>'
        + '<td class="text-center text-muted small">' + payMethod + '</td>'
        + deleteBtn
        + '</tr>';
    $('#historyTableBody').prepend(row);
    updateFilteredRows();
    showPage(1);
    $('#totalCount').text($('.history-row').length);
}

// ============ Form Submit ============
$(document).ready(function() {
    // Init pagination on load
    updateFilteredRows();
    showPage(1);

    // ============ ການສົ່ງຟອມບັນທຶກການເຊັກອິນ (Form Submit) ============
    $('#checkinForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບເມື່ອສົ່ງຟອມ

        // 1. ກວດສອບວ່າເລືອກເພດແລ້ວຫຼືບໍ່ (ໃຊ້ SweetAlert ແທນ required ຂອງບຣາວເຊີ)
        if (!selectedGender) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'ກະລຸນາເລືອກເພດ', 
                text: 'ກະລຸນາເລືອກ ເພດຊາຍ ຫຼື ເພດຍິງ ກ່ອນ', 
                confirmButtonColor: '#17a2b8',
                confirmButtonText: 'ຕົກລົງ'
            });
            return;
        }

        // 2. ກວດສອບການປ້ອນລາຄາ (ໃຊ້ SweetAlert ແທນ required ຂອງບຣາວເຊີ)
        var pricePaid = $('#price_paid').val().trim();
        if (!pricePaid) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'ກະລຸນາປ້ອນລາຄາ', 
                text: 'ກະລຸນາປ້ອນລາຄາຄ່າບໍລິການລາຍວັນກ່ອນ', 
                confirmButtonColor: '#17a2b8',
                confirmButtonText: 'ຕົກລົງ'
            });
            return;
        }

        // 3. ກວດສອບການເລືອກວັນທີ (ໃຊ້ SweetAlert ແທນ required ຂອງບຣາວເຊີ)
        var date = $('#checkin_date').val();
        if (!date) {
            Swal.fire({
                icon: 'warning',
                title: 'ກະລຸນາເລືອກວັນທີ',
                text: 'ກະລຸນາເລືອກວັນທີເຊັກອິນກ່ອນ',
                confirmButtonColor: '#17a2b8',
                confirmButtonText: 'ຕົກລົງ'
            });
            return;
        }

        var now = new Date();
        var dd = now.getDate().toString().padStart(2,'0');
        var mm = (now.getMonth()+1).toString().padStart(2,'0');
        var yyyy = now.getFullYear();
        var hh = now.getHours().toString().padStart(2,'0');
        var mi = now.getMinutes().toString().padStart(2,'0');
        var ss = now.getSeconds().toString().padStart(2,'0');
        var timeStr = dd + '/' + mm + '/' + yyyy + '|' + hh + ':' + mi + ':' + ss;

        var gender = $('#gender_val').val();
        var payMethod = $('#payment_method').val();

        var btn = $('#submitBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/daily_checkin_api.php',
            type: 'POST',
            data: { action: 'create', gender: gender, price_paid: pricePaid, payment_method: payMethod, checkin_date: date },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle me-1"></i> ບັນທຶກການເຊັກອິນ');
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'ເຊັກອິນສຳເລັດ!', timer: 1200, showConfirmButton: false });
                    prependRow(res.id, timeStr, gender, pricePaid, payMethod);
                    refreshStats();
                    // Reset form fields
                    selectedGender = '';
                    $('#gender_val').val('');
                    $('#btn-male, #btn-female').removeClass('male-active female-active');
                    $('#price_paid').val('');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle me-1"></i> ບັນທຶກການເຊັກອິນ');
                var msg = 'ເກີດຂໍ້ຜິດພາດ';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
            }
        });
    });

    // Re-run stats when date changes
    $('#checkin_date').on('change', function() {
        refreshStats();
        location.reload();
    });
});

// ============ Delete ============
function deleteRecord(id) {
    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບລາຍການນີ້ແທ້ບໍ່?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/daily_checkin_api.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('tr[data-id="' + id + '"]').remove();
                        if ($('.history-row').length === 0) {
                            $('#historyTableBody').append('<tr id="emptyRow"><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-user-slash fa-2x mb-3 d-block text-secondary"></i>ຍັງບໍ່ມີການເຊັກອິນໃນວັນນີ້</td></tr>');
                        }
                        $('#totalCount').text($('.history-row').length);
                        updateFilteredRows();
                        showPage(1);
                        refreshStats();
                        Swal.fire({ icon: 'success', title: 'ລົບສຳເລັດ', timer: 1200, showConfirmButton: false });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ລົບລາຍການບໍ່ສຳເລັດ' });
                }
            });
        }
    });
}

// ============ Pagination & Search ============
var itemsPerPage = 10, currentPage = 1, filteredRows = [];

$('#pageSizeSelect').on('change', function() {
    itemsPerPage = $(this).val() === 'all' ? 999999 : parseInt($(this).val());
    showPage(1);
});

function updateFilteredRows() {
    var q = $('#searchInput').val().toLowerCase().trim();
    filteredRows = [];
    $('.history-row').each(function() {
        if ($(this).text().toLowerCase().indexOf(q) > -1) {
            filteredRows.push(this);
        } else {
            $(this).hide();
        }
    });
    if (filteredRows.length === 0 && $('.history-row').length > 0) {
        if ($('#emptySearch').length === 0)
            $('#historyTableBody').append('<tr id="emptySearch"><td colspan="5" class="text-center py-4 text-muted">ບໍ່ພົບຂໍ້ມູນ</td></tr>');
    } else {
        $('#emptySearch').remove();
    }
}

function showPage(page) {
    currentPage = page;
    var total = filteredRows.length;
    if (total === 0) { $('.history-row').hide(); $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ'); $('#paginationControls').html(''); return; }
    var totalPages = Math.ceil(total / itemsPerPage) || 1;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    var s = (currentPage-1) * itemsPerPage, e = Math.min(s + itemsPerPage, total);
    $('.history-row').hide();
    for (var i = s; i < e; i++) $(filteredRows[i]).show();
    $('#paginationInfo').text('ສະແດງ ' + (s+1) + ' ຫາ ' + e + ' ຈາກທັງໝົດ ' + total + ' ລາຍການ');
    var html = '';
    html += currentPage === 1 ? '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>' : '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage-1) + '">&laquo;</a></li>';
    var sp = Math.max(1, currentPage-2), ep = Math.min(totalPages, sp+4);
    if (ep-sp < 4) sp = Math.max(1, ep-4);
    for (var p = sp; p <= ep; p++) {
        html += p === currentPage ? '<li class="page-item active"><a class="page-link" href="#">' + p + '</a></li>'
            : '<li class="page-item"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
    }
    html += currentPage === totalPages ? '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>' : '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage+1) + '">&raquo;</a></li>';
    $('#paginationControls').html(html);
    $('#paginationControls a[data-page]').off('click').on('click', function(e) { e.preventDefault(); showPage(parseInt($(this).data('page'))); });
}

$('#searchInput').on('input', function() { updateFilteredRows(); showPage(1); });
</script>
</body>
</html>
