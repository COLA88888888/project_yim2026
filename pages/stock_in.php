<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Fetch products for selection
$products = [];
$sql = "SELECT product_id, product_name, product_code, unit, cost_price, sale_price FROM products ORDER BY product_name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ນຳເຂົ້າສິນຄ້າ / ເພີ່ມສະຕັອກ</title>
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
        .form-section {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-file-import text-primary me-2"></i> ນຳເຂົ້າສິນຄ້າ / ເພີ່ມສະຕັອກ
            </h4>
            <p class="text-muted small mb-0">ບັນທຶກການຮັບສິນຄ້າເຂົ້າສາງ, ປັບປຸງລາຄາຕົ້ນທຶນ ແລະ ຈຳນວນສິນຄ້າໃນຄັງແບບອັດຕະໂນມັດ</p>
        </div>
    </div>

    <div class="row">
        <!-- Left: Product Adder Form -->
        <div class="col-lg-5">
            <div class="form-section">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
                    <i class="fas fa-cart-plus text-primary me-1"></i> ເລືອກສິນຄ້າ
                </h5>
                <div class="mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-barcode me-1 text-primary"></i> ຍິງບາໂຄ້ດສິນຄ້າ...</label>
                    <input type="text" id="barcodeInput" class="form-control" placeholder="ຍິງບາໂຄ້ດຢູ່ບ່ອນນີ້..." style="font-weight: bold; font-size: 1.1rem; border-color: #3f51b5;" autofocus>
                </div>

                <div class="mb-3" style="display: none;">
                    <label class="form-label fw-bold">ເລືອກສິນຄ້າ</label>
                    <select id="productSelect" class="form-control" style="font-weight: bold;">
                        <option value="">-- ເລືອກສິນຄ້າ --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['product_id'] ?>" 
                                    data-code="<?= htmlspecialchars($p['product_code']) ?>" 
                                    data-name="<?= htmlspecialchars($p['product_name']) ?>" 
                                    data-cost="<?= $p['cost_price'] ?>"
                                    data-unit="<?= htmlspecialchars($p['unit']) ?>">
                                <?= htmlspecialchars($p['product_name']) ?> (<?= htmlspecialchars($p['product_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="productNameDiv">
                    <label class="form-label fw-bold">ຊື່ສິນຄ້າ:</label>
                    <input type="text" id="selectedProductName" class="form-control bg-light" readonly placeholder="ຊື່ສິນຄ້າ..." style="font-weight: bold; font-size: 1.1rem; border-color: #28a745;">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ຕົ້ນທຶນນຳເຂົ້າ:</label>
                        <input type="text" id="itemCost" class="form-control bg-light price-input" readonly placeholder="0" style="font-weight: bold; color: #495057;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ຈຳນວນນຳເຂົ້າ:</label>
                        <input type="number" id="itemQty" class="form-control" placeholder="1" min="1" value="1">
                    </div>
                </div>

                <button type="button" id="addItemBtn" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-plus me-1"></i> ເພີ່ມເຂົ້າລາຍການ
                </button>
            </div>

            <!-- Supplier Info Section Removed -->
        </div>

        <!-- Right: Cart/Grid Section -->
        <div class="col-lg-7">
            <div class="card card-custom h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="fas fa-list text-primary me-1"></i> ລາຍການສິນຄ້າທີ່ກຳລັງນຳເຂົ້າ
                    </h5>
                </div>
                <div class="card-body p-0 d-flex flex-column justify-content-between" style="min-height: 350px;">
                    <!-- Grid List -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ລະຫັດ</th>
                                    <th>ຊື່ສິນຄ້າ</th>
                                    <th class="text-end" style="width: 130px;">ຕົ້ນທຶນ</th>
                                    <th class="text-center" style="width: 100px;">ຈຳນວນ</th>
                                    <th class="text-end" style="width: 130px;">ຍອດລວມ</th>
                                    <th class="text-center" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr id="emptyCartRow">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-file-import fa-2x mb-2 d-block"></i>
                                        ຍັງບໍ່ມີລາຍການສິນຄ້ານຳເຂົ້າ
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Amount & Save Button -->
                    <div class="p-3 border-top bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="h5 fw-bold text-dark mb-0">ຍອດລວມຕົ້ນທຶນທັງໝົດ:</span>
                            <span class="h4 fw-bold text-success mb-0" id="totalAmountText">0 ກີບ</span>
                        </div>
                        <button type="button" id="saveImportBtn" class="btn btn-success w-100 fw-bold py-2 shadow-sm" disabled>
                            <i class="fas fa-save me-1"></i> ບັນທຶກການນຳເຂົ້າສິນຄ້າ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

// Format numbers
function formatCurrency(amount) {
    return new Intl.NumberFormat('lo-LA').format(amount) + ' ກີບ';
}

$(document).on('input', '.price-input', function() {
    let val = this.value.replace(/\D/g, "");
    if (val === '') {
        this.value = '';
        return;
    }
    this.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
});

$(document).ready(function() {
    // Fill cost price when selecting product
    $('#productSelect').on('change', function() {
        let opt = $(this).find('option:selected');
        if (opt.val() !== '') {
            let cost = Math.round(parseFloat(opt.data('cost')) || 0);
            $('#itemCost').val(cost).trigger('input');
            $('#itemQty').val(1);
            $('#selectedProductName').val(opt.data('name'));
        } else {
            $('#itemCost').val('');
            $('#itemQty').val(1);
            $('#selectedProductName').val('');
        }
    });

    // Barcode scanning input handlers
    $('#barcodeInput').on('input', function() {
        let barcode = $(this).val().trim();
        if (barcode === '') return;
        $('#productSelect option').each(function() {
            if ($(this).data('code') == barcode) {
                $('#productSelect').val($(this).val()).trigger('change');
                return false; // break loop
            }
        });
    });

    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            let barcode = $(this).val().trim();
            if (barcode === '') return;

            let found = false;
            $('#productSelect option').each(function() {
                if ($(this).data('code') == barcode) {
                    $('#productSelect').val($(this).val()).trigger('change');
                    found = true;
                    return false; // break loop
                }
            });

            if (found) {
                $('#addItemBtn').trigger('click');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'ບໍ່ພົບສິນຄ້າ',
                    text: 'ບໍ່ພົບລະຫັດບາໂຄ້ດນີ້ໃນລະບົບສິນຄ້າ: ' + barcode
                });
            }
            $(this).val('').focus();
        }
    });

    // Add Item to Cart
    $('#addItemBtn').on('click', function() {
        let opt = $('#productSelect').find('option:selected');
        if (opt.val() === '') {
            Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກສິນຄ້າ' });
            return;
        }
        
        let productId = parseInt(opt.val());
        let code = opt.data('code');
        let name = opt.data('name');
        let cost = parseFloat($('#itemCost').val().replace(/,/g, '')) || 0;
        let qty = parseInt($('#itemQty').val()) || 0;
        let unit = opt.data('unit');

        if (qty <= 0) {
            Swal.fire({ icon: 'warning', title: 'ຈຳນວນນຳເຂົ້າຕ້ອງຫຼາຍກວ່າ 0' });
            return;
        }

        // Check if already in cart, if yes update qty & cost
        let existIndex = cart.findIndex(x => x.product_id === productId);
        if (existIndex > -1) {
            cart[existIndex].quantity += qty;
            cart[existIndex].cost_price = cost; // update to new cost
        } else {
            cart.push({
                product_id: productId,
                product_code: code,
                product_name: name,
                cost_price: cost,
                quantity: qty,
                unit: unit
            });
        }

        // Clear select and barcode input
        $('#productSelect').val('').trigger('change');
        $('#barcodeInput').val('').focus();
        renderCart();
    });

    // Remove Item from Cart
    $(document).on('click', '.remove-item-btn', function() {
        let idx = $(this).data('index');
        cart.splice(idx, 1);
        renderCart();
    });

    // Save Import
    $('#saveImportBtn').on('click', function() {
        if (cart.length === 0) return;

        let totalAmount = calculateTotal();
        let saveBtn = $(this);

        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງບັນທຶກ...');

        $.ajax({
            url: '../api/stock_in_api.php',
            type: 'POST',
            data: {
                action: 'create',
                total_amount: totalAmount,
                items: JSON.stringify(cart)
            },
            dataType: 'json',
            success: function(res) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກການນຳເຂົ້າສິນຄ້າ');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ນຳເຂົ້າສຳເລັດ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ບັນທຶກການນຳເຂົ້າສິນຄ້າ');
                let msg = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
            }
        });
    });
});

function calculateTotal() {
    let total = 0;
    cart.forEach(item => {
        total += item.cost_price * item.quantity;
    });
    return total;
}

function renderCart() {
    let tbody = $('#cartTableBody');
    tbody.empty();

    if (cart.length === 0) {
        tbody.append(`
            <tr id="emptyCartRow">
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="fas fa-file-import fa-2x mb-2 d-block"></i>
                    ຍັງບໍ່ມີລາຍການສິນຄ້ານຳເຂົ້າ
                </td>
            </tr>
        `);
        $('#totalAmountText').text('0 ກີບ');
        $('#saveImportBtn').prop('disabled', true);
        return;
    }

    cart.forEach((item, index) => {
        let subtotal = item.cost_price * item.quantity;
        tbody.append(`
            <tr>
                <td><code>${item.product_code}</code></td>
                <td class="fw-bold text-dark">${item.product_name}</td>
                <td class="text-end">${formatCurrency(item.cost_price)}</td>
                <td class="text-center"><span class="badge bg-light text-dark border">${item.quantity}</span></td>
                <td class="text-end fw-bold">${formatCurrency(subtotal)}</td>
                <td class="text-center">
                    <button class="btn btn-link text-danger p-0 remove-item-btn" data-index="${index}" title="ລົບອອກ">
                        <i class="fas fa-times-circle" style="font-size: 1.1rem;"></i>
                    </button>
                </td>
            </tr>
        `);
    });

    let total = calculateTotal();
    $('#totalAmountText').text(formatCurrency(total));
    $('#saveImportBtn').prop('disabled', false);
}
</script>
</body>
</html>
