<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('stock_in', 'view')) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}

// Fetch stock-in list
$imports = [];
$sql = "SELECT s.*, u.fname as staff_fname, u.lname as staff_lname 
        FROM stock_in s 
        LEFT JOIN users u ON s.user_id = u.user_id 
        ORDER BY s.stock_in_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $imports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ປະຫວັດການນຳເຂົ້າສິນຄ້າ</title>
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
        .print-receipt-container {
            font-family: 'Noto Sans Lao', 'Noto Sans Lao Looped', Arial, sans-serif;
            color: #000;
            padding: 15px;
            font-size: 11px;
            max-width: 340px;
            margin: 0 auto;
            background: #fff;
            border: 1px dashed #ccc;
            border-radius: 4px;
            line-height: 1.3;
        }
        .receipt-header { text-align: center; margin-bottom: 8px; }
        .receipt-logo { font-size: 16px; font-weight: bold; margin: 0 0 2px 0; color: #111; }
        .receipt-address { font-size: 9px; color: #666; margin: 0 0 4px 0; }
        .receipt-title { font-size: 12px; font-weight: bold; margin: 6px 0; text-transform: uppercase; color: #28a745; }
        .receipt-divider { border-top: 1px dashed #000; margin: 8px 0; }
        .receipt-meta { font-size: 9.5px; margin-bottom: 6px; }
        .receipt-meta div { margin-bottom: 3px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        .receipt-table th { font-weight: bold; border-bottom: 1px solid #000; padding: 4px 0; font-size: 10px; }
        .receipt-table td { padding: 5px 0; font-size: 10.5px; vertical-align: top; }
        .receipt-total-section { font-size: 11px; margin: 6px 0; }
        .receipt-total-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .receipt-total-row.grand-total { font-size: 13px; font-weight: bold; margin-top: 4px; border-top: 1px dashed #000; padding-top: 4px; }
        .receipt-footer { text-align: center; margin-top: 12px; font-size: 9.5px; font-weight: bold; }
        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            background: #fff;
            overflow: hidden;
        }
        .table-custom thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px 20px;
        }
        .table-custom tbody td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-history text-primary me-2"></i> ປະຫວັດການນຳເຂົ້າສິນຄ້າ
            </h4>
            <p class="text-muted small mb-0">ເບິ່ງລາຍການໃບບິນນຳເຂົ້າສິນຄ້າ/ເພີ່ມສະຕັອກຍ້ອນຫຼັງ ແລະ ກວດສອບລາຍລະອຽດການຮັບສິນຄ້າ</p>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <!-- Search & Control Header -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        ໃບບິນນຳເຂົ້າທັງໝົດ: <span class="fw-bold text-primary" id="importsCount"><?= count($imports) ?></span> ໃບບິນ
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
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາໃບບິນ (ເລກບິນ ຫຼື ຜູ້ບັນທຶກ)...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ເລກໃບບິນ</th>
                            <th>ວັນທີນຳເຂົ້າ</th>
                            <th class="text-end">ຍອດລວມຕົ້ນທຶນ</th>
                            <th>ຜູ້ບັນທຶກ</th>
                        </tr>
                    </thead>
                    <tbody id="importsTableBody">
                        <?php if (empty($imports)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-invoice fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີປະຫວັດການນຳເຂົ້າສິນຄ້າ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($imports as $i): ?>
                                <?php 
                                    $formattedId = 'IM-' . str_pad($i['stock_in_id'], 5, '0', STR_PAD_LEFT);
                                ?>
                                <tr class="import-row cursor-pointer" onclick="viewImportReceipt(<?= $i['stock_in_id'] ?>)">
                                    <td class="fw-bold"><code class="text-primary"><?= htmlspecialchars($formattedId) ?></code></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($i['stock_in_date'])) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($i['total_amount']) ?></td>
                                    <td><?= htmlspecialchars($i['staff_fname'] . ' ' . $i['staff_lname']) ?></td>
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
                ສະແດງ 1-10 ຈາກທັງໝົດ 10 ໃບບິນ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Receipt Modal to show print layout -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 450px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-file-invoice text-success me-1"></i> ໃບບິນນຳເຂົ້າສິນຄ້າ</h5>
                <button type="button" class="close border-0 bg-transparent" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="receiptPrintArea">
                <!-- Receipt details parsed in JS -->
            </div>
            <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-secondary fw-bold w-100" data-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<script>
function formatCurrency(amount) {
    return new Intl.NumberFormat('lo-LA').format(amount) + ' ກີບ';
}

$(document).ready(function() {
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
        
        $('.import-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#importsCount').text(filteredRows.length);
        
        if (filteredRows.length === 0 && $('.import-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#importsTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນປະຫວັດການນຳເຂົ້າ</td></tr>`
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
            $('.import-row').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ໃບບິນ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.import-row').hide();
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show();
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ໃບບິນ');
        
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

function viewImportReceipt(stockInId) {
    if (!stockInId) return;

    $.ajax({
        url: '../api/stock_in_api.php',
        type: 'GET',
        data: { action: 'get', stock_in_id: stockInId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let s = res.stock;
                let items = res.items;
                let datetime = new Date(s.stock_in_date).toLocaleString('lo-LA');
                let formattedId = 'IM-' + String(s.stock_in_id).padStart(5, '0');
                
                let html = `
                    <div class="print-receipt-container">
                        <div class="receipt-header">
                            <h4 class="receipt-logo">GYM & FITNESS</h4>
                            <p class="receipt-address">ບ້ານ ໂພນສະຫວ່າງ, ມ. ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ</p>
                            <h5 class="receipt-title">ໃບບິນນຳເຂົ້າສິນຄ້າ</h5>
                        </div>
                        <div class="receipt-divider"></div>
                        <div class="receipt-meta">
                            <div><b>ເລກໃບບິນ:</b> ${formattedId}</div>
                            <div><b>ວັນທີ:</b> ${datetime}</div>
                            <div><b>ຜູ້ບັນທຶກ:</b> ${s.staff_fname} ${s.staff_lname}</div>
                        </div>
                        <div class="receipt-divider"></div>
                        <table class="receipt-table">
                            <thead>
                                <tr>
                                    <th class="text-start">ລາຍການ</th>
                                    <th class="text-center" style="width: 70px;">ຕົ້ນທຶນ</th>
                                    <th class="text-center" style="width: 50px;">ຈຳນວນ</th>
                                    <th class="text-end" style="width: 80px;">ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                items.forEach(item => {
                    let itemTotal = item.cost_price * item.quantity;
                    let qtyDisplay = Number(item.quantity).toLocaleString('en-US');
                    html += `
                        <tr>
                            <td class="text-start">
                                <div>${item.product_name}</div>
                                <div style="font-size: 8.5px; color: #555;">(${item.product_code})</div>
                            </td>
                            <td class="text-center">${new Intl.NumberFormat('lo-LA').format(item.cost_price)}</td>
                            <td class="text-center">${qtyDisplay}</td>
                            <td class="text-end">${new Intl.NumberFormat('lo-LA').format(itemTotal)}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                        <div class="receipt-divider"></div>
                        <div class="receipt-total-section">
                            <div class="receipt-total-row grand-total">
                                <span>ຍອດລວມທັງໝົດ:</span>
                                <span class="text-success">${formatCurrency(s.total_amount)}</span>
                            </div>
                        </div>
                        <div class="receipt-divider"></div>
                        <p class="receipt-footer">*** ບັນທຶກການຮັບສິນຄ້າຮຽບຮ້ອຍ ***</p>
                    </div>
                `;
                
                $('#receiptPrintArea').html(html);
                $('#receiptModal').modal('show');
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
