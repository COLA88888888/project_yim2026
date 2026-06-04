<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// Fetch categories that have products
$categories = [];
$resCat = mysqli_query($conn, "SELECT DISTINCT c.* FROM product_categories c JOIN products p ON c.category_id = p.category_id ORDER BY c.category_id ASC");
if ($resCat) {
    while ($row = mysqli_fetch_assoc($resCat)) {
        $categories[] = $row;
    }
}

// Fetch all active products
$products = [];
$resProd = mysqli_query($conn, "SELECT p.*, c.category_name, c.category_code FROM products p LEFT JOIN product_categories c ON p.category_id = c.category_id ORDER BY p.product_name ASC");
if ($resProd) {
    while ($row = mysqli_fetch_assoc($resProd)) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຂາຍສິນຄ້າ / Point of Sale</title>
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
        .pos-product-card {
            background-color: #fff;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            padding: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .pos-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.06);
            border-color: #007bff;
        }
        .pos-product-card img {
            transition: transform 0.25s ease;
        }
        .pos-product-card:hover img {
            transform: scale(1.06);
        }
        .pos-product-card.out-of-stock {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .pos-product-card.out-of-stock:hover {
            transform: none;
            box-shadow: none;
            border-color: rgba(0,0,0,0.06);
        }
        .pos-category-tab {
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid #dee2e6;
            margin-right: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-block;
            background-color: #fff;
            color: #495057;
        }
        .pos-category-tab.active, .pos-category-tab:hover {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .cart-wrapper {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: calc(100vh - 130px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .cart-items-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f3f5;
            padding: 12px 6px;
        }
        .qty-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1px solid #dee2e6;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.15s;
        }
        .qty-btn:hover {
            background-color: #e9ecef;
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
        .print-receipt-container border-top-dotted {
            border-top: 1px dotted #000;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <!-- Left: Product Grid selection -->
        <div class="col-lg-7">
            <!-- Search & Filter bar -->
            <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1"><i class="fas fa-cash-register text-primary me-2"></i> ຂາຍສິນຄ້າ (POS)</h4>
                    <p class="text-muted small mb-0">ເລືອກສິນຄ້າເພື່ອອອກບິນຂາຍ ແລະ ຕັດສະຕັອກສິນຄ້າອັດຕະໂນມັດ</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="search-box" style="width: 180px;">
                        <i class="fas fa-barcode"></i>
                        <input type="text" id="barcodeInput" class="form-control text-primary" style="font-weight: bold; border-color: #007bff;" placeholder="ຍິງບາໂຄ້ດຂາຍ..." autofocus>
                    </div>
                    <div class="search-box" style="width: 200px;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາສິນຄ້າ...">
                    </div>
                </div>
            </div>

            <!-- Category Tab List -->
            <div class="mb-4">
                <div class="pos-category-tab active" data-category="all">ທັງໝົດ</div>
                <?php foreach ($categories as $c): ?>
                    <div class="pos-category-tab" data-category="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Products Grid -->
            <div class="row px-2" id="productsGrid" style="max-height: calc(100vh - 240px); overflow-y: auto; padding-bottom: 20px;">
                <?php foreach ($products as $p): ?>
                    <?php 
                        $outOfStock = $p['quantity'] <= 0; 
                        $cardClass = $outOfStock ? 'out-of-stock' : '';
                    ?>
                    <div class="col-6 col-sm-4 col-md-4 px-1 mb-2 product-card-container" data-category="<?= $p['category_id'] ?>" data-name="<?= htmlspecialchars(strtolower($p['product_name'])) ?>" data-code="<?= htmlspecialchars(strtolower($p['product_code'])) ?>">
                        <div class="pos-product-card <?= $cardClass ?>" onclick="<?= $outOfStock ? 'void(0)' : 'addToCart(' . json_encode($p) . ')' ?>">
                            <div class="mb-2 text-center rounded d-flex align-items-center justify-content-center shadow-sm" style="height: 110px; overflow: hidden; background-color: #fafafa; border: 1px solid #f0f0f0;">
                                <?php if (!empty($p['image']) && file_exists('../uploads/products/' . $p['image'])): ?>
                                    <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="product" style="width: 100%; height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="fas fa-image text-muted fa-2x"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="badge bg-light text-dark border mb-2 small"><i class="fas fa-folder me-1 text-primary"></i><?= htmlspecialchars($p['category_name']) ?></span>
                                <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.9rem;" title="<?= htmlspecialchars($p['product_name']) ?>"><?= htmlspecialchars($p['product_name']) ?></h6>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-2">
                                <span class="fw-bold text-success" style="font-size: 1.05rem;"><?= formatCurrency($p['sale_price']) ?></span>
                                <?php if ($outOfStock): ?>
                                    <span class="badge bg-danger text-white">ໝົດສະຕັອກ</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">ເຫຼືອ: <?= $p['quantity'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Sales Cart & Checkout -->
        <div class="col-lg-5">
            <div class="cart-wrapper">
                <div class="border-bottom p-3 bg-light d-flex justify-content-between align-items-center" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-shopping-basket text-primary me-2"></i> ກະຕ່າສິນຄ້າ</h6>
                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="clearCart()"><i class="fas fa-trash-alt me-1"></i>ລ້າງກະຕ່າ</button>
                </div>

                <!-- Items list -->
                <div class="cart-items-container" id="cartItems">
                    <!-- Loaded dynamically in JS -->
                    <div class="text-center py-5 text-muted" id="emptyCartMessage">
                        <i class="fas fa-shopping-cart fa-3x mb-3 d-block text-light"></i>
                        ກະຕ່າສິນຄ້າວ່າງເປົ່າ
                    </div>
                </div>

                <!-- Summary & Checkout Footer -->
                <div class="border-top p-3 bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <div class="mb-3 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark">ວິທີການຊຳລະເງິນ:</span>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary active px-3 py-1 btn-sm rounded-start" style="font-weight: 600;">
                                <input type="radio" name="payment_method" id="payCash" value="ເງິນສົດ" checked style="display: none;"> ເງິນສົດ
                            </label>
                            <label class="btn btn-outline-primary px-3 py-1 btn-sm rounded-end" style="font-weight: 600;">
                                <input type="radio" name="payment_method" id="payQR" value="ໂອນຜ່ານ QR" style="display: none;"> ໂອນຜ່ານ QR
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="h5 fw-bold text-dark mb-0">ຍອດຊຳລະທັງໝົດ:</span>
                        <span class="h3 fw-bold text-success mb-0" id="cartTotalText">0 ₭</span>
                    </div>

                    <button class="btn btn-success w-100 fw-bold py-2 shadow-sm" id="checkoutBtn" disabled onclick="checkout()">
                        <i class="fas fa-check-circle me-1"></i> ຢືນຢັນການຊຳລະເງິນ
                    </button>
                </div>
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
let cart = [];
let productsList = <?= json_encode($products) ?>;

function formatCurrency(amount) {
    return new Intl.NumberFormat('lo-LA').format(amount) + ' ₭';
}

function addToCart(product) {
    let productId = product.product_id;
    let existIndex = cart.findIndex(x => x.product_id === productId);
    
    if (existIndex > -1) {
        if (cart[existIndex].quantity + 1 > product.quantity) {
            Swal.fire({ icon: 'warning', title: 'ຈຳນວນສິນຄ້າໃນຄັງບໍ່ພຽງພໍ' });
            return;
        }
        cart[existIndex].quantity += 1;
    } else {
        if (product.quantity < 1) {
            Swal.fire({ icon: 'warning', title: 'ສິນຄ້າໝົດສະຕັອກ' });
            return;
        }
        cart.push({
            product_id: product.product_id,
            product_name: product.product_name,
            product_code: product.product_code,
            sale_price: parseFloat(product.sale_price),
            quantity: 1,
            unit: product.unit,
            max_qty: parseInt(product.quantity)
        });
    }
    renderCart();
}

function updateQty(index, offset) {
    let item = cart[index];
    if (item.quantity + offset < 1) {
        cart.splice(index, 1);
    } else if (item.quantity + offset > item.max_qty) {
        Swal.fire({ icon: 'warning', title: 'ຈຳນວນສິນຄ້າໃນຄັງບໍ່ພຽງພໍ' });
        return;
    } else {
        item.quantity += offset;
    }
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
}

function calculateTotal() {
    let total = 0;
    cart.forEach(item => {
        total += item.sale_price * item.quantity;
    });
    return total;
}

function renderCart() {
    let container = $('#cartItems');
    container.empty();

    if (cart.length === 0) {
        container.append(`
            <div class="text-center py-5 text-muted" id="emptyCartMessage">
                <i class="fas fa-shopping-cart fa-3x mb-3 d-block text-light"></i>
                ກະຕ່າສິນຄ້າວ່າງເປົ່າ
            </div>
        `);
        $('#cartTotalText').text('0 ₭');
        $('#checkoutBtn').prop('disabled', true);
        return;
    }

    cart.forEach((item, index) => {
        let subtotal = item.sale_price * item.quantity;
        container.append(`
            <div class="cart-item">
                <div style="max-width: 60%;">
                    <span class="d-block fw-bold text-dark" style="font-size: 0.9rem; line-height: 1.2;">${item.product_name}</span>
                    <span class="text-muted small">${formatCurrency(item.sale_price)}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="qty-btn" onclick="updateQty(${index}, -1)">-</button>
                    <span class="fw-bold px-1" style="font-size: 0.95rem; min-width: 20px; text-align: center;">${item.quantity}</span>
                    <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                </div>
                <div class="text-end" style="min-width: 90px;">
                    <span class="fw-bold text-dark">${formatCurrency(subtotal)}</span>
                </div>
            </div>
        `);
    });

    let total = calculateTotal();
    $('#cartTotalText').text(formatCurrency(total));
    $('#checkoutBtn').prop('disabled', false);
}

function checkout() {
    if (cart.length === 0) return;

    let paymentMethod = $('input[name="payment_method"]:checked').val() || 'ເງິນສົດ';
    let totalAmount = calculateTotal();
    let checkoutBtn = $('#checkoutBtn');

    checkoutBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງຊຳລະເງິນ...');

    $.ajax({
        url: '../api/sales_api.php',
        type: 'POST',
        data: {
            action: 'create',
            payment_method: paymentMethod,
            total_amount: totalAmount,
            items: JSON.stringify(cart)
        },
        dataType: 'json',
        success: function(res) {
            checkoutBtn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> ຢືນຢັນການຊຳລະເງິນ');
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'ຂາຍສຳເລັດ',
                    text: res.message,
                    timer: 1000,
                    showConfirmButton: false
                });
                
                // Load receipt details into Modal
                loadReceipt(res.sale_id);
            }
        },
        error: function(xhr) {
            checkoutBtn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> ຢືນຢັນການຊຳລະເງິນ');
            let msg = 'ເກີດຂໍ້ຜິດພາດໃນການຊຳລະເງິນ';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: msg });
        }
    });
}

function loadReceipt(saleId) {
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
                
                // Clear cart after modal closed
                $('#receiptModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    location.reload();
                });
            }
        }
    });
}

function printReceipt() {
    let printContents = document.getElementById('receiptPrintArea').innerHTML;
    let originalContents = document.body.innerHTML;
    
    // Simple window print styling
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

$(document).ready(function() {
    // Barcode scanning input handler
    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            let barcode = $(this).val().trim();
            if (barcode === '') return;

            let product = productsList.find(p => p.product_code == barcode);
            if (product) {
                addToCart(product);
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

    // Category tabs filter
    $('.pos-category-tab').on('click', function() {
        $('.pos-category-tab').removeClass('active');
        $(this).addClass('active');
        
        let cat = $(this).data('category');
        if (cat === 'all') {
            $('.product-card-container').show();
        } else {
            $('.product-card-container').hide();
            $(`.product-card-container[data-category="${cat}"]`).show();
        }
        $('#searchInput').val(''); // clear search when switching categories
    });

    // POS Search bar
    $('#searchInput').on('input', function() {
        let query = $(this).val().toLowerCase().trim();
        $('.pos-category-tab').removeClass('active');
        $('.pos-category-tab[data-category="all"]').addClass('active'); // reset category tab to all
        
        $('.product-card-container').each(function() {
            let name = $(this).data('name');
            let code = $(this).data('code');
            if (name.indexOf(query) > -1 || code.indexOf(query) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Make Payment method toggle active label logic
    $('input[name="payment_method"]').on('change', function() {
        $('input[name="payment_method"]').parent().removeClass('active');
        $(this).parent().addClass('active');
    });
});
</script>
</body>
</html>
