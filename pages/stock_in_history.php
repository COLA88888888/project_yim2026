<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

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
            font-family: 'Noto Sans Lao', Arial, sans-serif;
            color: #000;
            padding: 10px;
            font-size: 0.85rem;
        }
        .print-receipt-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .print-receipt-container th, .print-receipt-container td {
            padding: 4px 0;
        }
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
                            <th class="text-center" style="width: 100px;">ລາຍລະອຽດ</th>
                        </tr>
                    </thead>
                    <tbody id="importsTableBody">
                        <?php if (empty($imports)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-invoice fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີປະຫວັດການນຳເຂົ້າສິນຄ້າ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($imports as $i): ?>
                                <?php 
                                    $formattedId = 'IM-' . str_pad($i['stock_in_id'], 5, '0', STR_PAD_LEFT);
                                ?>
                                <tr class="import-row">
                                    <td class="fw-bold"><code class="text-primary"><?= htmlspecialchars($formattedId) ?></code></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($i['stock_in_date'])) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($i['total_amount']) ?></td>
                                    <td><?= htmlspecialchars($i['staff_fname'] . ' ' . $i['staff_lname']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-info btn-sm btn-action" onclick="viewImportReceipt(<?= $i['stock_in_id'] ?>)" title="ເບິ່ງໃບບິນ">
                                            <i class="fas fa-file-invoice"></i>
                                        </button>
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
                <button class="btn btn-primary fw-bold px-4 w-100" onclick="printReceipt()"><i class="fas fa-print me-1"></i> ພິມໃບບິນ</button>
            </div>
        </div>
    </div>
</div>

<script>
function formatCurrency(amount) {
    return new Intl.NumberFormat('lo-LA').format(amount) + ' ກີບ';
}

$(document).ready(function() {
    // Search imports list in JS
    $('#searchInput').on('input', function() {
        var query = $(this).val().toLowerCase().trim();
        var count = 0;
        
        $('.import-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                $(this).show();
                count++;
            } else {
                $(this).hide();
            }
        });
        
        $('#importsCount').text(count);
    });
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
                        <div class="text-center mb-3">
                            <h4 class="fw-bold mb-1">GYM & FITNESS</h4>
                            <p class="text-muted small mb-0">ບ້ານ ໂພນສະຫວ່າງ, ມ. ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ</p>
                            <h5 class="fw-bold text-success mt-2">ໃບບິນນຳເຂົ້າສິນຄ້າ</h5>
                        </div>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <div class="text-start small mb-2">
                            <div><b>ເລກໃບບິນ:</b> ${formattedId}</div>
                            <div><b>ວັນທີ:</b> ${datetime}</div>
                            <div><b>ຜູ້ບັນທຶກ:</b> ${s.staff_fname} ${s.staff_lname}</div>
                        </div>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <table>
                            <thead>
                                <tr style="border-bottom: 1px dotted #333;">
                                    <th class="text-start">ລາຍການ</th>
                                    <th class="text-center" style="width: 80px;">ຕົ້ນທຶນ</th>
                                    <th class="text-center" style="width: 60px;">ຈຳນວນ</th>
                                    <th class="text-end" style="width: 90px;">ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                items.forEach(item => {
                    let itemTotal = item.cost_price * item.quantity;
                    html += `
                        <tr>
                            <td class="text-start">${item.product_name} <br><small class="text-muted">(${item.product_code})</small></td>
                            <td class="text-center">${formatCurrency(item.cost_price)}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatCurrency(itemTotal)}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <div class="d-flex justify-content-between align-items-center fw-bold h6">
                            <span>ຍອດລວມທັງໝົດ:</span>
                            <span class="text-success">${formatCurrency(s.total_amount)}</span>
                        </div>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <p class="text-center font-weight-bold small mt-3">*** ບັນທຶກການຮັບສິນຄ້າຮຽບຮ້ອຍ ***</p>
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

function printReceipt() {
    let printContents = document.getElementById('receiptPrintArea').innerHTML;
    
    let printWindow = window.open('', '_blank', 'width=500,height=600');
    printWindow.document.write('<html><head><title>ພິມໃບບິນນຳເຂົ້າ</title>');
    printWindow.document.write('<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Noto Sans Lao", sans-serif; padding: 20px; color: #000; }');
    printWindow.document.write('table { width: 100%; } th, td { padding: 6px 0; font-size: 13px; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>
</body>
</html>
