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
            height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 10px;
        }
        .cart-items-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .qty-btn:hover {
            background-color: #007bff;
            color: #fff;
        }
        .qty-btn.minus:hover {
            background-color: #dc3545;
            color: #fff;
        }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 6px;
            border-bottom: 1px solid #f1f3f5;
        }
        .cart-item-img {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            object-fit: cover;
            background: #f8f9fa;
            flex-shrink: 0;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .cart-item-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .qty-badge-overlay {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            z-index: 10;
        }
        .pos-product-card {
            position: relative;
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
    <div class="row">
        <!-- Left: Product Grid selection -->
        <div class="col-lg-7">
            <!-- Search & Filter bar -->
            <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1"><i class="fas fa-cash-register text-primary me-2"></i> ຂາຍສິນຄ້າ (POS)</h4>
                    <p class="text-muted small mb-0">ເລືອກສິນຄ້າເພື່ອອອກບິນຂາຍ ແລະ ຕັດສະຕັອກສິນຄ້າອັດຕະໂນມັດ</p>
                </div>
                <div class="search-box" style="width: 100%; max-width: 350px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control text-primary" style="font-weight: bold; border-color: #007bff;" placeholder="ຄົ້ນຫາ ຫຼື ຍິງບາໂຄ້ດ..." autofocus>
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
                        <div class="pos-product-card <?= $cardClass ?>" onclick="<?= $outOfStock ? 'void(0)' : 'addToCartById(' . $p['product_id'] . ')' ?>">
                            <div class="mb-2 text-center rounded d-flex align-items-center justify-content-center" style="height: 110px; overflow: hidden; background-color: #f8f9fa; border-radius: 8px;">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; flex-direction:column; color:#adb5bd;">
                                        <i class="fas fa-box fa-2x mb-1"></i>
                                        <small style="font-size:9px;"><?= htmlspecialchars($p['product_code']) ?></small>
                                    </div>
                                <?php else: ?>
                                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; color:#adb5bd;">
                                        <i class="fas fa-box fa-2x mb-1"></i>
                                        <small style="font-size:9px;"><?= htmlspecialchars($p['product_code']) ?></small>
                                    </div>
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
                        <span class="h3 fw-bold text-success mb-0" id="cartTotalText">0 ກີບ</span>
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
    return new Intl.NumberFormat('lo-LA').format(amount) + ' ກີບ';
}

function addToCartById(productId) {
    let product = productsList.find(p => p.product_id == productId);
    if (product) {
        addToCart(product);
    }
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
            image: product.image || '',
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

    // Reset all qty badges on product cards
    $('.pos-product-card .qty-badge-overlay').remove();

    if (cart.length === 0) {
        container.append(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-shopping-basket fa-3x mb-3 d-block" style="color:#e2e8f0;"></i>
                <span style="font-size:0.9rem;">ກະຕ່າສິນຄ້າວ່າງເປົ່າ</span>
            </div>
        `);
        $('#cartTotalText').text('0 ກີບ');
        $('#checkoutBtn').prop('disabled', true);
        return;
    }

    cart.forEach((item, index) => {
        let subtotal = item.sale_price * item.quantity;
        let priceNum = new Intl.NumberFormat('lo-LA').format(item.sale_price);
        let subtotalNum = new Intl.NumberFormat('lo-LA').format(subtotal);
        let qtyNum = Number(item.quantity).toLocaleString('en-US');

        // Build image cell
        let imgHtml = '';
        if (item.image) {
            imgHtml = `<img src="../uploads/products/${item.image}" alt="" onerror="this.src=''; this.parentElement.innerHTML='<i class=\'fas fa-box\' style=\'color:#adb5bd;\'></i>';">`;
        } else {
            imgHtml = `<i class="fas fa-box" style="color:#adb5bd; font-size:1.3rem;"></i>`;
        }

        container.append(`
            <div class="cart-item">
                <div class="cart-item-img">${imgHtml}</div>
                <div style="flex:1; min-width:0;">
                    <div class="fw-bold text-dark text-truncate" style="font-size:0.88rem; line-height:1.2;">${item.product_name}</div>
                    <div class="text-muted" style="font-size:0.78rem; margin-top:2px;">${priceNum} × ${qtyNum} = <span class="fw-bold text-success">${subtotalNum} ກີບ</span></div>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button class="qty-btn minus" onclick="updateQty(${index}, -1)" title="ຫຼຸດ"><i class="fas fa-minus" style="font-size:0.7rem;"></i></button>
                    <span class="fw-bold text-dark" style="font-size:1rem; min-width:26px; text-align:center;">${item.quantity}</span>
                    <button class="qty-btn" onclick="updateQty(${index}, 1)" title="ເພີ່ມ"><i class="fas fa-plus" style="font-size:0.7rem;"></i></button>
                </div>
            </div>
        `);

        // Add qty badge on matching product card
        let cardEl = $(`.product-card-container[data-code="${item.product_code.toLowerCase()}"] .pos-product-card`);
        if (cardEl.length) {
            cardEl.append(`<div class="qty-badge-overlay">${item.quantity}</div>`);
        }
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

$(document).ready(function() {
    // Barcode scanning & Enter key handler in search input
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            let query = $(this).val().trim();
            if (query === '') return;

            // Find exact barcode match
            let product = productsList.find(p => p.product_code == query);
            if (product) {
                addToCart(product);
                $(this).val('');
                // Reset search grid filter to show all products
                $('.product-card-container').show();
            } else {
                // If it is numeric (looks like a barcode), show error
                if (/^\d+$/.test(query)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ບໍ່ພົບສິນຄ້າ',
                        text: 'ບໍ່ພົບລະຫັດບາໂຄ້ດນີ້ໃນລະບົບສິນຄ້າ: ' + query
                    });
                }
            }
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
