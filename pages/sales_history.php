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
    return new Intl.NumberFormat('lo-LA').format(amount) + ' ₭';
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
                    <div class="print-receipt-container text-center">
                        <h4 class="fw-bold mb-1">GYM & FITNESS</h4>
                        <p class="text-muted small mb-3">ບ້ານ ໂພນສະຫວ່າງ, ມ. ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ</p>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <div class="text-start small mb-2">
                            <div><b>ເລກບິນ:</b> ${s.sale_code}</div>
                            <div><b>ວັນທີ:</b> ${datetime}</div>
                            <div><b>ພະນັກງານຂາຍ:</b> ${s.staff_fname} ${s.staff_lname}</div>
                        </div>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <table>
                            <thead>
                                <tr style="border-bottom: 1px dotted #333;">
                                    <th class="text-start">ລາຍການ</th>
                                    <th class="text-center" style="width: 50px;">ຈຳນວນ</th>
                                    <th class="text-end" style="width: 80px;">ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                items.forEach(item => {
                    let itemTotal = item.price * item.quantity;
                    html += `
                        <tr>
                            <td class="text-start">${item.product_name}</td>
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
                            <span>ລວມທັງໝົດ:</span>
                            <span>${formatCurrency(s.total_amount)}</span>
                        </div>
                        <div class="text-start small mt-2">
                            <div><b>ຊຳລະໂດຍ:</b> ${s.payment_method}</div>
                        </div>
                        <div style="border-top: 1px dashed #333; margin: 8px 0;"></div>
                        <p class="text-center font-weight-bold small mt-3">*** ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ ***</p>
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
    
    let printWindow = window.open('', '_blank', 'width=350,height=600');
    printWindow.document.write('<html><head><title>ພິມໃບບິນຮັບເງິນ</title>');
    printWindow.document.write('<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Noto Sans Lao", sans-serif; padding: 20px; text-align: center; color: #000; }');
    printWindow.document.write('table { width: 100%; } th, td { padding: 4px 0; font-size: 12px; }');
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
