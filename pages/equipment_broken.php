<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('equipment', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

$equipment = [];
$sql = "SELECT * FROM equipment WHERE status = 'ເພ' ORDER BY equipment_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $equipment[] = $row;
    }
}

$total_qty = 0;
foreach ($equipment as $e) {
    $total_qty += (int)($e['quantity'] ?? 1);
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ອຸປະກອນເພ / ຊຳລຸດ</title>
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif; background-color: #f4f6f9; }

        /* Stat Banner */
        .stat-banner {
            border-radius: 16px;
            color: white;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.10);
        }
        .stat-banner .stat-icon {
            font-size: 2.5rem;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            width: 60px; height: 60px;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-banner .stat-num { font-size: 2rem; font-weight: 800; line-height: 1.1; }
        .stat-banner .stat-label { font-size: 0.85rem; opacity: 0.85; font-weight: 600; }

        /* Broken row highlight */
        .eq-row:hover td { background: #fff5f5 !important; }

        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-tools text-danger me-2"></i> ອຸປະກອນເພ / ຊຳລຸດ
            </h4>
            <p class="text-muted small mb-0">ລາຍຊື່ອຸປະກອນ ແລະ ເຄື່ອງຫຼິ້ນທີ່ຢູ່ໃນສະພາບຊຳລຸດ ຕ້ອງສ້ອມແປງ ຫຼື ປ່ຽນໃໝ່</p>
        </div>
        <div class="no-print d-flex gap-2">
            <a href="equipment_manage.php" target="frame" class="btn btn-outline-primary rounded-pill px-4">
                <i class="fas fa-arrow-left me-1"></i> ກັບຄືນ
            </a>
            <button class="btn btn-outline-secondary rounded-pill px-4" onclick="window.print()">
                <i class="fas fa-print me-1"></i> ພິມ
            </button>
        </div>
    </div>

    <!-- Stat Banners -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="stat-banner" style="background: linear-gradient(135deg,#e53e3e,#fc8181);">
                <div class="stat-icon"><i class="fas fa-tools"></i></div>
                <div>
                    <div class="stat-label">ລາຍການ (ໂດດ) ທີ່ຊຳລຸດ</div>
                    <div class="stat-num"><?= count($equipment) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="stat-banner" style="background: linear-gradient(135deg,#dd6b20,#f6ad55);">
                <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                <div>
                    <div class="stat-label">ຈຳນວນທັງໝົດ (ອັນ/ເຄື່ອງ)</div>
                    <div class="stat-num"><?= number_format($total_qty) ?></div>
                </div>
            </div>
        </div>
        <?php if (!empty($equipment)): ?>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="stat-banner" style="background: linear-gradient(135deg,#6b46c1,#9f7aea);">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-label">ຕ້ອງດຳເນີນການ</div>
                    <div class="stat-num" style="font-size:1.1rem;margin-top:4px;">ສ້ອມ / ປ່ຽນໃໝ່</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Table Card -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <!-- Header Controls -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        ລາຍການຊຳລຸດ: <span class="fw-bold text-danger" id="matchCount"><?= count($equipment) ?></span> ລາຍການ
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">ຕໍ່ໜ້າ:</span>
                        <select id="pageSizeSelect" class="form-control form-control-sm" style="width:80px;border-radius:8px;font-weight:bold;height:32px;">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="all">ທັງໝົດ</option>
                        </select>
                    </div>
                </div>
                <div class="search-box flex-grow-1" style="max-width:380px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາຊື່ / ລະຫັດ / ຍີ່ຫໍ້...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ລະຫັດ</th>
                            <th>ຊື່ອຸປະກອນ</th>
                            <th>ຍີ່ຫໍ້ / ລຸ້ນ</th>
                            <th class="text-center">ຈຳນວນ</th>
                            <th class="text-end">ລາຄາຊື້</th>
                            <th class="text-center">ວັນທີຊື້</th>
                            <th>ໝາຍເຫດ</th>
                            <th class="text-center">ສະພາບ</th>
                            <th class="text-center" style="width:100px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody id="eqTableBody">
                        <?php if (empty($equipment)): ?>
                        <tr id="emptyRow">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-smile fa-2x mb-3 d-block text-success"></i>
                                ບໍ່ມີອຸປະກອນທີ່ຊຳລຸດ! ທຸກເຄື່ອງຢູ່ໃນສະພາບດີ 🎉
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($equipment as $e): ?>
                            <tr class="eq-row">
                                <td><code><?= htmlspecialchars($e['equipment_code']) ?></code></td>
                                <td class="fw-bold text-danger"><?= htmlspecialchars($e['equipment_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($e['brand_model'] ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $e['quantity'] ?> ເຄື່ອງ</span>
                                </td>
                                <td class="text-end fw-bold"><?= formatCurrency($e['price']) ?></td>
                                <td class="text-center text-muted"><?= $e['purchase_date'] ? date('d/m/Y', strtotime($e['purchase_date'])) : '-' ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($e['description'] ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge px-3 py-2" style="background:#fee2e2;color:#991b1b;border-radius:20px;font-size:0.82rem;font-weight:700;">
                                        <i class="fas fa-tools me-1"></i> ເພ / ຊຳລຸດ
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-info btn-sm btn-action text-white" onclick="viewEquipment(<?= $e['equipment_id'] ?>)" title="ເບິ່ງລາຍລະອຽດ">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
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
            <nav><ul class="pagination pagination-sm mb-0" id="paginationControls"></ul></nav>
        </div>
    </div>
</div>

<script>
var itemsPerPage = 10, currentPage = 1, filteredRows = [];

function updateFilteredRows() {
    var q = $('#searchInput').val().toLowerCase().trim();
    filteredRows = [];
    $('.eq-row').each(function() {
        if ($(this).text().toLowerCase().indexOf(q) > -1) filteredRows.push(this);
        else $(this).hide();
    });
    $('#matchCount').text(filteredRows.length);
    if (filteredRows.length === 0 && $('.eq-row').length > 0) {
        if ($('#emptySearch').length === 0)
            $('#eqTableBody').append('<tr id="emptySearch"><td colspan="9" class="text-center py-4 text-muted">ບໍ່ພົບຂໍ້ມູນ</td></tr>');
    } else { $('#emptySearch').remove(); }
}

function showPage(page) {
    currentPage = page;
    var total = filteredRows.length;
    if (total === 0) { $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ'); $('#paginationControls').html(''); return; }
    var totalPages = Math.ceil(total / itemsPerPage) || 1;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    var s = (currentPage-1)*itemsPerPage, e = Math.min(s+itemsPerPage, total);
    $('.eq-row').hide();
    for (var i = s; i < e; i++) $(filteredRows[i]).show();
    $('#paginationInfo').text('ສະແດງ '+(s+1)+' ຫາ '+e+' ຈາກທັງໝົດ '+total+' ລາຍການ');
    var html = '';
    html += currentPage===1?'<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>':'<li class="page-item"><a class="page-link" href="#" data-page="'+(currentPage-1)+'">&laquo;</a></li>';
    var sp=Math.max(1,currentPage-2),ep=Math.min(totalPages,sp+4);
    if(ep-sp<4) sp=Math.max(1,ep-4);
    for(var p=sp;p<=ep;p++) html+=p===currentPage?'<li class="page-item active"><a class="page-link" href="#">'+p+'</a></li>':'<li class="page-item"><a class="page-link" href="#" data-page="'+p+'">'+p+'</a></li>';
    html+=currentPage===totalPages?'<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>':'<li class="page-item"><a class="page-link" href="#" data-page="'+(currentPage+1)+'">&raquo;</a></li>';
    $('#paginationControls').html(html);
    $('#paginationControls a[data-page]').off('click').on('click',function(e){e.preventDefault();showPage(parseInt($(this).data('page')));});
}

$('#pageSizeSelect').on('change', function() {
    itemsPerPage = $(this).val()==='all'?999999:parseInt($(this).val());
    showPage(1);
});
$('#searchInput').on('input', function() { updateFilteredRows(); showPage(1); });

$(document).ready(function() { updateFilteredRows(); showPage(1); });

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
</body>
</html>
