<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Fetch sales list
$sales = [];
$sql = "SELECT s.*, u.fname as staff_fname, u.lname as staff_lname 
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.user_id 
        ORDER BY s.sale_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sales[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ປະຫວັດການຂາຍສິນຄ້າ</title>
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
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-history text-primary me-2"></i> ປະຫວັດການຂາຍສິນຄ້າ
            </h4>
            <p class="text-muted small mb-0">ເບິ່ງລາຍການບິນຂາຍຍ້ອນຫຼັງ, ກວດສອບລາຍລະອຽດສິນຄ້າ ແລະ ພິມໃບບິນຮັບເງິນຄືນ</p>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <!-- Search & Control Header -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        ໃບບິນຂາຍທັງໝົດ: <span class="fw-bold text-primary" id="salesCount"><?= count($sales) ?></span> ໃບບິນ
                    </div>
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາໃບບິນ (ເລກບິນ ຫຼື ວິທີຊຳລະ)...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ເລກໃບບິນ</th>
                            <th>ວັນທີຂາຍ</th>
                            <th class="text-end">ຍອດລວມ</th>
                            <th>ວິທີຊຳລະເງິນ</th>
                            <th>ພະນັກງານຂາຍ</th>
                            <th class="text-center" style="width: 100px;">ໃບບິນ</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-receipt fa-2x mb-3 d-block"></i>
                                    ຍັງບໍ່ມີປະຫວັດການຂາຍສິນຄ້າ
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $s): ?>
                                <tr class="sale-row">
                                    <td class="fw-bold"><code class="text-primary"><?= htmlspecialchars($s['sale_code']) ?></code></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($s['sale_date'])) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($s['total_amount']) ?></td>
                                    <td>
                                        <?php if ($s['payment_method'] === 'ເງິນສົດ'): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($s['payment_method']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($s['payment_method']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($s['staff_fname'] . ' ' . $s['staff_lname']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-info btn-sm btn-action" onclick="viewReceipt(<?= $s['sale_id'] ?>)" title="ເບິ່ງໃບບິນ">
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
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-file-invoice text-success me-1"></i> ໃບບິນຮັບເງິນ</h5>
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
    // Search sales list in JS
    $('#searchInput').on('input', function() {
        var query = $(this).val().toLowerCase().trim();
        var count = 0;
        
        $('.sale-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                $(this).show();
                count++;
            } else {
                $(this).hide();
            }
        });
        
        $('#salesCount').text(count);
    });
});

function viewReceipt(saleId) {
    if (!saleId) return;

    $.ajax({
        url: '../api/sales_api.php',
        type: 'GET',
        data: { action: 'get', sale_id: saleId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let s = res.sale;
                let items = res.items;
                let datetime = new Date(s.sale_date).toLocaleString('lo-LA');
                
                let html = `
                    <div class="print-receipt-container">
                        <div class="receipt-header">
                            <h4 class="receipt-logo">GYM & FITNESS</h4>
                            <p class="receipt-address">ບ້ານ ໂພນສະຫວ່າງ, ມ. ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ</p>
                            <h5 class="receipt-title">ໃບບິນຮັບເງິນ / RECEIPT</h5>
                        </div>
                        <div class="receipt-divider"></div>
                        <div class="receipt-meta">
                            <div><b>ເລກບິນ:</b> ${s.sale_code}</div>
                            <div><b>ວັນທີ:</b> ${datetime}</div>
                            <div><b>ພະນັກງານຂາຍ:</b> ${s.staff_fname} ${s.staff_lname}</div>
                        </div>
                        <div class="receipt-divider"></div>
                        <table class="receipt-table">
                            <thead>
                                <tr>
                                    <th class="text-start">ລາຍການ</th>
                                    <th class="text-center" style="width: 60px;">ຈຳນວນ</th>
                                    <th class="text-end" style="width: 90px;">ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                items.forEach(item => {
                    let itemTotal = item.price * item.quantity;
                    let qtyDisplay = Number(item.quantity).toLocaleString('en-US');
                    html += `
                        <tr>
                            <td class="text-start">${item.product_name}</td>
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
                            <div class="receipt-total-row">
                                <span>ຊຳລະໂດຍ:</span>
                                <span class="fw-bold">${s.payment_method}</span>
                            </div>
                            <div class="receipt-total-row grand-total">
                                <span>ຍອດລວມທັງໝົດ:</span>
                                <span class="text-success">${formatCurrency(s.total_amount)}</span>
                            </div>
                        </div>
                        <div class="receipt-divider"></div>
                        <p class="receipt-footer">*** ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ ***</p>
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
    
    let printWindow = window.open('', '_blank', 'width=380,height=600');
    printWindow.document.write('<html><head><title>ພິມໃບບິນຮັບເງິນ</title>');
    // Set base href to resolve local relative font files
    printWindow.document.write('<base href="' + window.location.origin + window.location.pathname + '">');
    printWindow.document.write('<link rel="stylesheet" href="../assets/css/local-font.css">');
    printWindow.document.write('<style>');
    printWindow.document.write('@media print { @page { size: 80mm auto; margin: 0; } body { margin: 0; padding: 4mm; } }');
    printWindow.document.write('body { font-family: "Noto Sans Lao", "Noto Sans Lao Looped", Arial, sans-serif; width: 72mm; margin: 0 auto; color: #000; background: #fff; font-size: 11px; line-height: 1.3; }');
    printWindow.document.write('.text-center { text-align: center; } .text-start { text-align: left; } .text-end { text-align: right; }');
    printWindow.document.write('.receipt-header { text-align: center; margin-bottom: 8px; }');
    printWindow.document.write('.receipt-logo { font-size: 16px; font-weight: bold; margin: 0 0 2px 0; color: #111; }');
    printWindow.document.write('.receipt-address { font-size: 9px; color: #666; margin: 0 0 4px 0; }');
    printWindow.document.write('.receipt-title { font-size: 12px; font-weight: bold; margin: 6px 0; text-transform: uppercase; color: #28a745; }');
    printWindow.document.write('.receipt-divider { border-top: 1px dashed #000; margin: 8px 0; }');
    printWindow.document.write('.receipt-meta { font-size: 9.5px; margin-bottom: 6px; } .receipt-meta div { margin-bottom: 3px; }');
    printWindow.document.write('.receipt-table { width: 100%; border-collapse: collapse; margin: 4px 0; }');
    printWindow.document.write('.receipt-table th { font-weight: bold; border-bottom: 1px solid #000; padding: 4px 0; font-size: 10px; }');
    printWindow.document.write('.receipt-table td { padding: 5px 0; font-size: 10.5px; vertical-align: top; }');
    printWindow.document.write('.receipt-total-section { font-size: 11px; margin: 6px 0; } .receipt-total-row { display: flex; justify-content: space-between; padding: 2px 0; }');
    printWindow.document.write('.receipt-total-row.grand-total { font-size: 13px; font-weight: bold; margin-top: 4px; border-top: 1px dashed #000; padding-top: 4px; }');
    printWindow.document.write('.receipt-footer { text-align: center; margin-top: 12px; font-size: 9.5px; font-weight: bold; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    
    // Wait for fonts to load
    setTimeout(function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>
</body>
</html>
