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
if (!hasPermission('lockers', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

// Fetch lockers
$lockers = [];
$sql = "SELECT * FROM lockers ORDER BY locker_code ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $lockers[] = $row;
    }
}

// Extract unique floors for filter buttons
$floors = [];
foreach ($lockers as $l) {
    $floorVal = trim($l['locker_floor'] ?? '');
    if ($floorVal !== '' && !in_array($floorVal, $floors)) {
        $floors[] = $floorVal;
    }
}
sort($floors);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການລັອກເກີເກັບເຄື່ອງ</title>
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
        <?php if (!hasPermission('lockers', 'edit')): ?>
        .locker-card {
            cursor: default !important;
        }
        <?php endif; ?>
        
        /* Premium Locker Grid & Card Styles */
        .locker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .locker-card {
            position: relative;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            padding: 24px 16px 18px 16px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            min-height: 200px;
        }
        
        .locker-card:hover {
            box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.12), 0 4px 8px -2px rgba(0, 0, 0, 0.06);
        }
        
        /* Available Locker Status (Soft Green) */
        .locker-card.status-Available {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: #bbf7d0;
        }
        .locker-card.status-Available .locker-icon {
            color: #16a34a;
        }
        .locker-card.status-Available .locker-code {
            color: #14532d;
        }
        .locker-card.status-Available .locker-status-text {
            color: #16a34a;
        }
        
        /* Occupied Locker Status (Soft Yellow/Orange) */
        .locker-card.status-Occupied {
            background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%);
            border-color: #fef08a;
        }
        .locker-card.status-Occupied .locker-icon {
            color: #ca8a04;
        }
        .locker-card.status-Occupied .locker-code {
            color: #713f12;
        }
        .locker-card.status-Occupied .locker-status-text {
            color: #ca8a04;
        }
        
        /* Broken Locker Status (Soft Danger Red) */
        .locker-card.status-Broken {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #fca5a5;
        }
        .locker-card.status-Broken .locker-icon {
            color: #dc2626;
        }
        .locker-card.status-Broken .locker-code {
            color: #7f1d1d;
        }
        .locker-card.status-Broken .locker-status-text {
            color: #dc2626;
        }
        
        .locker-icon {
            font-size: 2.2rem;
            margin-bottom: 8px;
            transition: transform 0.3s ease;
        }
        
        .locker-card:hover .locker-icon {
            transform: scale(1.15) rotate(4deg);
        }
        
        .locker-code {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .locker-status-text {
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        
        .locker-floor-tag {
            font-size: 0.76rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.65);
            background: rgba(255, 255, 255, 0.85);
            padding: 3px 12px;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-top: auto;
        }
        
        /* Absolute Actions (Always Visible) */
        .locker-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 5px;
            z-index: 10;
        }
        
        .locker-action-btn {
            background: #ffffff;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .locker-action-btn.btn-edit:hover {
            background: #ffc107;
            color: #ffffff !important;
        }
        
        .locker-action-btn.btn-delete:hover {
            background: #dc3545;
            color: #ffffff !important;
        }
        
        /* Floor filtering pills styling */
        .floor-filter-btn {
            border-radius: 20px;
            font-weight: bold;
            padding: 6px 18px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            border-color: rgba(0, 123, 255, 0.15);
            background-color: #fff;
            color: #495057;
        }
        
        .floor-filter-btn:hover {
            background-color: rgba(0, 123, 255, 0.05);
            color: #007bff;
        }
        
        .floor-filter-btn.active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.15);
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-lock text-primary me-2"></i> ຈັດການລັອກເກີເກັບເຄື່ອງ
            </h4>
            <p class="text-muted small mb-0">ບໍລິຫານ ແລະ ກຳນົດສະຖານະຕູ້ລັອກເກີເກັບເຄື່ອງຂອງສະມາຊິກ</p>
        </div>
        <div>
            <?php if (hasPermission('lockers', 'add')): ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ເພີ່ມລັອກເກີໃໝ່
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
                        ລັອກເກີທັງໝົດ: <span class="fw-bold text-primary" id="lockerCount"><?= count($lockers) ?></span> ຕູ້
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">ສະແດງ:</span>
                        <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 80px; border-radius: 8px; font-weight: bold; height: 32px;">
                            <option value="15" selected>15</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">ທັງໝົດ</option>
                        </select>
                    </div>
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາລະຫັດ ຫຼື ຊັ້ນ...">
                </div>
            </div>
            
            <!-- Floor Filters Subbar -->
            <div class="px-3 pt-3 pb-2 border-bottom d-flex flex-wrap gap-2 align-items-center bg-light">
                <span class="text-muted small fw-bold me-2"><i class="fas fa-filter text-primary me-1"></i> ແຍກຕາມຊັ້ນ:</span>
                <button class="btn btn-sm floor-filter-btn active" data-floor="all">ທັງໝົດ</button>
                <?php foreach ($floors as $floor): ?>
                    <button class="btn btn-sm floor-filter-btn" data-floor="<?= htmlspecialchars($floor) ?>"><?= htmlspecialchars($floor) ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Locker Grid View -->
            <div class="locker-grid" id="lockerGridContainer">
                <?php if (empty($lockers)): ?>
                    <div class="text-center py-5 text-muted w-100" style="grid-column: 1 / -1;" id="noLockerMessage">
                        <i class="fas fa-lock fa-3x mb-3 d-block"></i>
                        ຍັງບໍ່ມີຂໍ້ມູນລັອກເກີ
                    </div>
                <?php else: ?>
                    <?php foreach ($lockers as $l): ?>
                        <div class="locker-card status-<?= htmlspecialchars($l['status']) ?>" 
                             data-id="<?= $l['locker_id'] ?>" 
                             data-code="<?= htmlspecialchars(strtolower($l['locker_code'])) ?>" 
                             data-floor="<?= htmlspecialchars(strtolower($l['locker_floor'] ?: '')) ?>"
                             data-status="<?= htmlspecialchars($l['status']) ?>"
                             <?php if (hasPermission('lockers', 'edit')): ?>
                             onclick="toggleLocker(<?= $l['locker_id'] ?>, '<?= $l['status'] ?>')"
                             <?php endif; ?>>
                            
                            <!-- Action overlay buttons (edit/delete only) -->
                            <?php if (hasPermission('lockers', 'edit') || hasPermission('lockers', 'delete')): ?>
                            <div class="locker-actions">
                                <?php if (hasPermission('lockers', 'edit')): ?>
                                <button class="locker-action-btn btn-edit text-warning" onclick="event.stopPropagation(); openEditModal(<?= $l['locker_id'] ?>)" title="ແກ້ໄຂ">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (hasPermission('lockers', 'delete')): ?>
                                <button class="locker-action-btn btn-delete text-danger" onclick="event.stopPropagation(); deleteLocker(<?= $l['locker_id'] ?>)" title="ລົບ">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Icon representing status -->
                            <div class="locker-icon">
                                <?php if ($l['status'] === 'Available'): ?>
                                    <i class="fas fa-lock-open"></i>
                                <?php elseif ($l['status'] === 'Occupied'): ?>
                                    <i class="fas fa-lock"></i>
                                <?php else: ?>
                                    <i class="fas fa-tools"></i>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Locker code & text status -->
                            <div>
                                <div class="locker-code"><?= htmlspecialchars($l['locker_code']) ?></div>
                                <div class="locker-status-text">
                                    <?php if ($l['status'] === 'Available'): ?>
                                        ຫວ່າງ
                                    <?php elseif ($l['status'] === 'Occupied'): ?>
                                        ໃຊ້ງານຢູ່
                                    <?php else: ?>
                                        ເພ / ຊຳລຸດ
                                    <?php endif; ?>
                                </div>
                                <?php if ($l['status'] === 'Occupied' && !empty($l['assigned_at'])): ?>
                                <div style="font-size:0.7rem; color:#92400e; margin-top:3px; font-weight:600; line-height:1.3;">
                                    <i class="fas fa-clock fa-xs"></i>
                                    <?= date('d/m H:i', strtotime($l['assigned_at'])) ?>
                                </div>
                                <?php elseif ($l['status'] !== 'Broken'): ?>
                                <div style="font-size:0.68rem; color:rgba(0,0,0,0.4); margin-top:3px;">
                                    <?= $l['status'] === 'Available' ? 'ຄລິກ = ວ່າງໃຊ້ງານ' : 'ຄລິກ = ສົ່ງຄືນ' ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Floor designation tag -->
                            <span class="locker-floor-tag"><?= htmlspecialchars($l['locker_floor'] ?: '-') ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="text-muted small" id="paginationInfo">
                ສະແດງ 1-15 ຈາກທັງໝົດ 15 ຕູ້
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal ເພີ່ມ/ແກ້ໄຂລັອກເກີ -->
<div class="modal fade" id="lockerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-plus me-1"></i> ເພີ່ມລັອກເກີໃໝ່</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3 text-white">&times;</span>
                </button>
            </div>
            <form id="lockerForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="locker_id" id="formLockerId">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ລະຫັດລັອກເກີ</label>
                        <input type="text" name="locker_code" id="locker_code" class="form-control" placeholder="ຕົວຢ່າງ: L-01, Locker-A...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ຊັ້ນ / ຕຳແໜ່ງ</label>
                        <input type="text" name="locker_floor" id="locker_floor" class="form-control" placeholder="ຕົວຢ່າງ: ຊັ້ນ 1, ໂຊນ A...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ສະຖານະ</label>
                        <select name="status" id="status" class="form-control" style="font-weight: 500;">
                            <option value="Available">ຫວ່າງ (Available)</option>
                            <option value="Occupied">ໃຊ້ງານຢູ່ (Occupied)</option>
                            <option value="Broken">ເພ / ຊຳລຸດ (Broken)</option>
                        </select>
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
    // ============ ການສົ່ງຟອມບັນທຶກຕູ້ລັອກເກີ (Save Locker Form) ============
    $('#lockerForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບ
        
        // ກວດສອບຄວາມຖືກຕ້ອງຂອງຂໍ້ມູນ (ໃຊ້ SweetAlert ແທນ required)
        if ($('#locker_code').val().trim() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນລະຫັດລັອກເກີ', confirmButtonColor: '#007bff' });
            return;
        }
        
        let formData = $(this).serialize();
        let saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/lockers_api.php',
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
                    $('#lockerModal').modal('hide');
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

    // Pagination, Search & Floor Filters in JavaScript
    var itemsPerPage = 15;
    var currentPage = 1;
    var filteredRows = [];
    var selectedFloor = 'all';

    $('#pageSizeSelect').on('change', function() {
        var val = $(this).val();
        if (val === 'all') {
            itemsPerPage = 999999;
        } else {
            itemsPerPage = parseInt(val);
        }
        showPage(1);
    });

    // Floor filter click handler
    $('.floor-filter-btn').on('click', function() {
        $('.floor-filter-btn').removeClass('active');
        $(this).addClass('active');
        selectedFloor = $(this).data('floor').toString().toLowerCase().trim();
        updateFilteredRows();
        showPage(1);
    });

    function updateFilteredRows() {
        var query = $('#searchInput').val().toLowerCase().trim();
        filteredRows = [];
        
        $('.locker-card').each(function() {
            var code = $(this).data('code').toString();
            var floor = $(this).data('floor').toString();
            
            // Check floor matching
            var matchesFloor = (selectedFloor === 'all' || floor === selectedFloor);
            
            // Check query matching (either code or floor contains search text)
            var matchesSearch = (code.indexOf(query) > -1 || floor.indexOf(query) > -1);
            
            if (matchesFloor && matchesSearch) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#lockerCount').text(filteredRows.length);
        
        if (filteredRows.length === 0 && $('.locker-card').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#lockerGridContainer').append(
                    `<div id="emptySearchResult" class="text-center py-5 text-muted w-100" style="grid-column: 1 / -1;"><i class="fas fa-search fa-2x mb-3 d-block text-secondary"></i>ບໍ່ພົບຂໍ້ມູນລັອກເກີທີ່ທ່ານຄົ້ນຫາ</div>`
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
            $('.locker-card').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ຕູ້');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.locker-card').hide();
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show();
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ຕູ້');
        
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

    // Run pagination on init
    updateFilteredRows();
    showPage(1);
});

function openCreateModal() {
    $('#formAction').val('create');
    $('#formLockerId').val('');
    $('#lockerForm')[0].reset();
    $('#modalTitle').html('<i class="fas fa-plus me-1"></i> ເພີ່ມລັອກເກີໃໝ່');
    $('#lockerModal').modal('show');
}

function openEditModal(lockerId) {
    if (!lockerId) return;
    
    // Prevent overlay conflicts if triggered by card click
    $.ajax({
        url: '../api/lockers_api.php',
        type: 'GET',
        data: { action: 'get', locker_id: lockerId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let l = res.locker;
                $('#formAction').val('update');
                $('#formLockerId').val(l.locker_id);
                $('#locker_code').val(l.locker_code);
                $('#locker_floor').val(l.locker_floor);
                $('#status').val(l.status);
                
                $('#modalTitle').html('<i class="fas fa-edit me-1"></i> ແກ້ໄຂຂໍ້ມູນລັອກເກີ');
                $('#lockerModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນລັອກເກີໄດ້' });
        }
    });
}

function toggleLocker(lockerId, currentStatus) {
    if (!lockerId) return;
    if (currentStatus === 'Broken') return; // ບໍ່ toggle locker ທີ່ເພ

    var newStatus = (currentStatus === 'Available') ? 'Occupied' : 'Available';
    var confirmMsg = (currentStatus === 'Available')
        ? 'ຕ້ອງການໝາຍລັອກເກີນີ້ວ່າ "ໃຊ້ງານຢູ່" (Occupied)?'
        : 'ລູກຄ້າ ສົ່ງຄືນລັອກເກີ? ຕ້ອງການປ່ຽນກັບໄປ "ຫວ່າງ" (Available)?';
    var confirmBtnText = (currentStatus === 'Available')
        ? '<i class="fas fa-lock me-1"></i> ໃຊ້ງານ'
        : '<i class="fas fa-lock-open me-1"></i> ສົ່ງຄືນ';
    var confirmColor = (currentStatus === 'Available') ? '#f59e0b' : '#16a34a';

    Swal.fire({
        title: (currentStatus === 'Available') ? '🔒ໃຊ້ງານ' : '🔓 ສົ່ງຄືນລັອກເກີ',
        text: confirmMsg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'ຍົກເລີກ'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/lockers_api.php',
                type: 'POST',
                data: { action: 'toggle_status', locker_id: lockerId, new_status: newStatus },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        var Toast = Swal.mixin({
                            toast: true, position: 'top-end',
                            showConfirmButton: false, timer: 1500, timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: newStatus === 'Occupied' ? 'ໝາຍວ່າໃຊ້ງານແລ້ວ' : 'ສົ່ງຄືນສຳເລັດ - ຫວ່າງແລ້ວ'
                        });
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'ເກີດຂໍ້ຜິດພາດ';
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
                }
            });
        }
    });
}

function deleteLocker(lockerId) {
    if (!lockerId) return;

    Swal.fire({
        title: 'ຢືນຢັນການລົບ',
        text: 'ທ່ານຕ້ອງການລົບລັອກເກີນີ້ແທ້ບໍ່?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ຢືນຢັນການລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/lockers_api.php',
                type: 'POST',
                data: { action: 'delete', locker_id: lockerId },
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
                    let msg = 'ບໍ່ສາມາດລົບລັອກເກີໄດ້';
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
