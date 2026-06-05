<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Only Admin (ຜູ້ບໍລິຫານ) has access to permission management
if (isset($_SESSION['status']) && $_SESSION['status'] !== 'ຜູ້ບໍລິຫານ') {
    echo "<div class='container mt-5'><div class='alert alert-danger fw-bold text-center p-4' style='border-radius:12px;'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// ດຶງຂໍ້ມູນພະນັກງານທັງໝົດ
$users = [];
$sql = "SELECT user_id, fname, lname, gender, dob, tel, address, status, username, remark, permissions FROM users WHERE status = 'ພະນັກງານ' ORDER BY user_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ກຳນົດສິດທິຜູ້ນຳໃຊ້</title>
    <!-- Google Fonts - Noto Sans Lao Looped -->
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/permission-manage.css?v=3">
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header Page -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-key text-primary me-2"></i> ກຳນົດສິດທິຜູ້ນຳໃຊ້
            </h4>
            <p class="text-muted small mb-0">ຈັດການ ແລະ ກຳນົດສິດທິໃນການເຂົ້າເຖິງລະບົບຂອງພະນັກງານແຕ່ລະຄົນ</p>
        </div>
    </div>

    <!-- 2-Card Layout Row -->
    <div class="row">
        <!-- Card 1: Staff List (Left Column) -->
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="card card-custom h-100">
                <div class="card-body p-0">
                    <!-- Search & Control Header -->
                    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="text-muted small">
                                ພົບພະນັກງານທັງໝົດ: <span class="fw-bold text-primary" id="staffCount"><?= count($users) ?></span> ຄົນ
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
                            <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາດ້ວຍ ຊື່, ນາມສະກຸນ ຫຼື ຊື່ຜູ້ໃຊ້ງານ...">
                        </div>
                    </div>

                    <!-- Responsive Table Grid -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 180px;">ພະນັກງານ</th>
                                    <th class="text-center" style="width: 100px;">ສະຖານະ</th>
                                </tr>
                            </thead>
                            <tbody id="staffTableBody">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="2">
                                            <div class="empty-state">
                                                <i class="fas fa-users-cog"></i>
                                                <h5>ບໍ່ມີຂໍ້ມູນພະນັກງານ</h5>
                                                <p class="small text-muted mb-0">ຍັງບໍ່ມີຜູ້ໃຊ້ທີ່ມີສະຖານະເປັນ "ພະນັກງານ" ໃນລະບົບ</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): 
                                        $perms = $u['permissions'] ?? '[]';
                                        $initials = mb_substr($u['fname'], 0, 1, 'utf-8') . mb_substr($u['lname'], 0, 1, 'utf-8');
                                    ?>
                                        <tr class="staff-row cursor-pointer" id="staff-row-<?= htmlspecialchars($u['user_id']) ?>" data-user='<?= htmlspecialchars(json_encode($u, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>' onclick="selectStaff('<?= addslashes($u['user_id']) ?>')">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-circle">
                                                        <?= htmlspecialchars(mb_strtoupper($initials, 'utf-8')) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark staff-name"><?= htmlspecialchars($u['fname']) ?> <?= htmlspecialchars($u['lname']) ?></div>
                                                        <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-primary border border-primary px-3 py-1.5" style="border-radius: 20px;">
                                                    <?= htmlspecialchars($u['status']) ?>
                                                </span>
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

        <!-- Card 2: Permissions Configuration (Right Column) -->
        <div class="col-lg-7 col-md-12 mb-4">
            <!-- Placeholder Card when no employee is selected -->
            <div class="card card-custom p-5 text-center text-muted h-100" id="placeholderCard" style="min-height: 400px; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #cbd5e0; background-color: #f8fafc; border-radius: 16px;">
                <i class="fas fa-user-shield fa-4x mb-3 text-secondary" style="opacity: 0.5;"></i>
                <h5 class="fw-bold text-dark">ເລືອກພະນັກງານ</h5>
                <p class="small mb-0">ກະລຸນາຄລິກເລືອກລາຍຊື່ພະນັກງານໃນກາດຊ້າຍມື ເພື່ອເລີ່ມກຳນົດສິດທິການເຂົ້າເຖິງລະບົບ</p>
            </div>

            <!-- Permission Form Card (hidden by default) -->
            <div class="card card-custom" id="permissionCard" style="display: none; border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px; padding: 14px 20px;">
                    <h5 class="card-title fw-bold mb-0 text-white"><i class="fas fa-user-shield me-2"></i>ກຳນົດສິດທິ: <span id="permUserName" class="text-warning fw-bold"></span></h5>
                </div>
                <div class="card-body p-3 bg-light">
                    <input type="hidden" id="permUserId">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover bg-white mb-0" id="permTable">
                            <thead class="bg-light">
                                <tr style="white-space: nowrap;">
                                    <th style="min-width: 150px;">ໂມດູນ</th>
                                    <th class="text-center" style="width: 70px;">ເບິ່ງ</th>
                                    <th class="text-center" style="width: 70px;">ເພີ່ມ</th>
                                    <th class="text-center" style="width: 70px;">ແກ້ໄຂ</th>
                                    <th class="text-center" style="width: 70px;">ລົບ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-module="dashboard">
                                    <td class="fw-bold"><i class="fas fa-chart-line text-success me-2"></i> ດາດສ໌ບອດ (Dashboard)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center text-muted">-</td>
                                </tr>
                                <tr data-module="checkin">
                                    <td class="fw-bold"><i class="fas fa-id-card text-success me-2"></i> ເຊັກອິນເຂົ້າໃຊ້ (Check-in)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="subscriptions">
                                    <td class="fw-bold"><i class="fas fa-file-invoice-dollar text-warning me-2"></i> ລົງທະບຽນແພັກເກດ & ຊຳລະເງິນ</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="members">
                                    <td class="fw-bold"><i class="fas fa-users text-primary me-2"></i> ຈັດການສະມາຊິກ (Members)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="packages">
                                    <td class="fw-bold"><i class="fas fa-tags text-danger me-2"></i> ຈັດການແພັກເກດ (Packages)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="equipment">
                                    <td class="fw-bold"><i class="fas fa-dumbbell text-secondary me-2"></i> ເຄື່ອງອອກກຳລັງກາຍ</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="lockers">
                                    <td class="fw-bold"><i class="fas fa-lock text-info me-2"></i> ລັອກເກີເກັບເຄື່ອງ (Lockers)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="daily_checkin">
                                    <td class="fw-bold"><i class="fas fa-user-plus text-info me-2"></i> ເຊັກອິນລາຍວັນ (Daily Check-in)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="users">
                                    <td class="fw-bold"><i class="fas fa-user-cog text-info me-2"></i> ຈັດການພະນັກງານ</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="expenses">
                                    <td class="fw-bold"><i class="fas fa-minus-circle text-danger me-2"></i> ຈັດການລາຍຈ່າຍ (Expenses)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="sales">
                                    <td class="fw-bold"><i class="fas fa-cash-register text-success me-2"></i> ຂາຍສິນຄ້າ (POS)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="sales_history">
                                    <td class="fw-bold"><i class="fas fa-history text-info me-2"></i> ປະຫວັດການຂາຍ (Sales History)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="stock_in">
                                    <td class="fw-bold"><i class="fas fa-file-import text-warning me-2"></i> ນຳເຂົ້າສິນຄ້າ (Stock In)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="products">
                                    <td class="fw-bold"><i class="fas fa-box text-primary me-2"></i> ຈັດການຂໍ້ມູນສິນຄ້າ (Products)</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                                <tr data-module="product_categories">
                                    <td class="fw-bold"><i class="fas fa-folder text-warning me-2"></i> ຈັດການປະເພດສິນຄ້າ</td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-view" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-add" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-edit" value="1"><span class="slider-custom"></span></label></td>
                                    <td class="text-center"><label class="switch-custom"><input type="checkbox" class="perm-cb perm-delete" value="1"><span class="slider-custom"></span></label></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top d-flex justify-content-end gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; padding: 12px 20px;">
                    <button type="button" id="savePermBtn" class="btn btn-success fw-bold px-4" onclick="savePermissions()"><i class="fas fa-save me-1"></i> ບັນທຶກສິດທິ</button>
                </div>
            </div>
        </div>
    </div>

<script>
// ແປງ permission string ແບບເກົ່າ ຫຼື ໃໝ່ ໃຫ້ເປັນ Object ທີ່ສາມາດໃຊ້ໄດ້ໃນ UI
function parsePermissions(permStr) {
    let perms = {};
    try {
        let parsed = JSON.parse(permStr || '[]');
        if (Array.isArray(parsed)) {
            // ໂຄງສ້າງແບບເກົ່າ (e.g. ["users", "assets"])
            parsed.forEach(mod => {
                perms[mod] = { view: true, add: true, edit: true, delete: true, limit: 0 };
            });
        } else {
            // ໂຄງສ້າງແບບໃໝ່ Object
            perms = parsed;
        }
    } catch (e) {}
    return perms;
}

function selectStaff(userId) {
    // Find the row by matching the id attribute safely
    var row = $('[id="staff-row-' + userId + '"]');
    if (!row.length) { console.warn('Row not found for userId:', userId); return; }

    var user = row.data('user');
    if (typeof user === 'string') {
        try { user = JSON.parse(user); } catch(e) { console.error('JSON parse error:', e, user); return; }
    } else if (!user) {
        var rawData = row.attr('data-user');
        try { user = JSON.parse(rawData); } catch(e) { console.error('JSON parse error:', e, rawData); return; }
    }

    // Highlight active row
    $('.staff-row').removeClass('active-row table-primary');
    row.addClass('active-row table-primary');

    // Switch panels
    $('#placeholderCard').hide();
    $('#permissionCard').show();

    // Set form fields
    $('#permUserId').val(user.user_id);
    $('#permUserName').text(user.fname + ' ' + user.lname + ' (@' + user.username + ')');

    // Reset all checkboxes first
    $('.perm-cb').prop('checked', false);

    // Load existing permissions into the UI
    var perms = parsePermissions(user.permissions);
    $('#permTable tbody tr').each(function() {
        var mod = $(this).data('module');
        if (perms[mod]) {
            var p = perms[mod];
            $(this).find('.perm-view').prop('checked', !!p.view);
            $(this).find('.perm-add').prop('checked', !!p.add);
            $(this).find('.perm-edit').prop('checked', !!p.edit);
            $(this).find('.perm-delete').prop('checked', !!p.delete);
        }
    });
}

function savePermissions() {
    let userId = $('#permUserId').val();
    let perms = {};
    
    $('#permTable tbody tr').each(function() {
        let mod = $(this).data('module');
        let view = $(this).find('.perm-view').is(':checked');
        let add = $(this).find('.perm-add').is(':checked');
        let edit = $(this).find('.perm-edit').is(':checked');
        let del = $(this).find('.perm-delete').is(':checked');
        
        if (view || add || edit || del) {
            perms[mod] = {
                view: view,
                add: add,
                edit: edit,
                delete: del
            };
        }
    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });

    $('#savePermBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

    $.ajax({
        url: '../api/update_permissions.php',
        type: 'POST',
        data: {
            user_id: userId,
            permissions: JSON.stringify(perms)
        },
        dataType: 'json',
        success: function(res) {
            $('#savePermBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກສິດທິ');
            if (res.success) {
                Toast.fire({
                    icon: 'success',
                    title: 'ບັນທຶກສິດທິສຳເລັດແລ້ວ'
                });
                
                // Update the local storage on the row dynamically so we do NOT need a page reload
                let row = $('#staff-row-' + userId);
                let user = row.data('user');
                user.permissions = JSON.stringify(perms);
                row.data('user', user);
            } else {
                Toast.fire({ icon: 'error', title: res.message || 'ບໍ່ສາມາດບັນທຶກສິດໄດ້' });
            }
        },
        error: function(xhr) {
            $('#savePermBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກສິດທິ');
            Toast.fire({ icon: 'error', title: 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ກັບ Server' });
        }
    });
}

// ຜູກ View ກັບສິດອື່ນໆ: ຖ້າ View ປິດ, ສິດອື່ນຕ້ອງປິດນຳ
$(document).on('change', '.perm-view', function() {
    if (!$(this).is(':checked')) {
        let tr = $(this).closest('tr');
        tr.find('.perm-add, .perm-edit, .perm-delete').prop('checked', false);
    }
});

// ຖ້າ Add/Edit/Delete ເປີດ, ຕ້ອງເປີດ View ໂດຍອັດຕະໂນມັດ
$(document).on('change', '.perm-add, .perm-edit, .perm-delete', function() {
    if ($(this).is(':checked')) {
        $(this).closest('tr').find('.perm-view').prop('checked', true);
    }
});

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
        
        $('.staff-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide(); // Hide non-matching rows immediately
            }
        });
        
        $('#staffCount').text(filteredRows.length);
        
        // Handle empty search state
        if (filteredRows.length === 0 && $('.staff-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#staffTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນທີ່ທ່ານຄົ້ນຫາ</td></tr>`
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
            $('.staff-row').hide();
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
        $('.staff-row').hide();
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
});
</script>

<!-- Inline CSS Override to bypass iframe/browser stylesheet caching on mobile -->
<style>
/* Pagination Styles */
.staff-row.cursor-pointer {
    transition: all 0.2s ease;
}
.staff-row.cursor-pointer:hover {
    background-color: rgba(13, 110, 253, 0.08) !important;
}
.staff-row.active-row {
    background-color: rgba(13, 110, 253, 0.15) !important;
    border-left: 4px solid #0d6efd !important;
}

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
    background-color: #3182ce !important;
    border-color: #3182ce !important;
    color: white !important;
    box-shadow: 0 4px 6px rgba(49, 130, 206, 0.2);
}
.pagination .page-link:hover {
    background-color: #edf2f7;
    color: #2b6cb0;
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
    .container-fluid {
        padding: 8px 10px !important;
    }
    .container-fluid .d-flex.flex-wrap.justify-content-between.align-items-center {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 2px !important;
        margin-bottom: 8px !important;
    }
    .container-fluid h4.fw-bold.text-dark {
        font-size: 0.95rem !important;
        margin-bottom: 0px !important;
    }
    .container-fluid p.text-muted.small {
        font-size: 0.65rem !important;
    }
    .container-fluid .d-flex.flex-wrap.justify-content-between.align-items-center > div {
        width: 100% !important;
    }

    /* Search Box & Controls Header Mobile Compression */
    .card .p-3.border-bottom {
        padding: 8px 10px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 6px !important;
    }
    .card .p-3.border-bottom .search-box {
        width: 100% !important;
        max-width: 100% !important;
    }
    .search-box input {
        height: 30px !important;
        font-size: 0.72rem !important;
        padding-left: 28px !important;
        padding-right: 10px !important;
        border-radius: 15px !important;
    }
    .search-box i {
        left: 10px !important;
        font-size: 0.75rem !important;
    }
    .card .p-3.border-bottom .text-muted.small {
        font-size: 0.68rem !important;
        width: 100% !important;
        text-align: left !important;
        margin-top: 2px !important;
    }

    /* Main Table Mobile Compression */
    .table-custom th {
        font-size: 0.65rem !important;
        padding: 6px 4px !important;
        white-space: nowrap !important;
    }
    .table-custom td {
        font-size: 0.68rem !important;
        padding: 6px 4px !important;
        white-space: nowrap !important;
        height: auto !important;
    }
    .avatar-circle {
        width: 26px !important;
        height: 26px !important;
        font-size: 0.7rem !important;
        border-width: 1px !important;
    }
    .staff-row .fw-bold.text-dark {
        font-size: 0.68rem !important;
        font-weight: 600 !important;
    }
    .staff-row code,
    .staff-row .badge {
        font-size: 0.60rem !important;
        padding: 1px 4px !important;
    }
    .staff-row .btn-sm {
        padding: 3px 8px !important;
        font-size: 0.65rem !important;
        border-radius: 12px !important;
    }

    /* Restrict Address column on narrow screens to look extremely clean */
    .table-custom th:nth-child(3),
    .table-custom td:nth-child(3) {
        max-width: 110px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.62rem !important;
    }

    /* Permission Modal Table Mobile Compression */
    #permTable th {
        font-size: 0.7rem !important;
        padding: 4px 2px !important;
        white-space: nowrap !important;
    }
    #permTable td {
        font-size: 0.68rem !important;
        padding: 4px 2px !important;
    }
    #permTable .fw-bold {
        font-size: 0.68rem !important;
        font-weight: 500 !important;
    }
    #permTable .fw-bold i {
        font-size: 0.72rem !important;
        margin-right: 2px !important;
    }

    /* Switch Custom Responsive Sizing */
    .switch-custom {
        width: 32px !important;
        height: 16px !important;
    }
    .slider-custom:before {
        height: 10px !important;
        width: 10px !important;
        left: 3px !important;
        bottom: 3px !important;
    }
    input:checked + .slider-custom:before {
        transform: translateX(16px) !important;
    }
}
</style>
</body>
</html>
