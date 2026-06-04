<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('sales', 'view')) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}

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
$gymSettings = getSystemSettings($conn);
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
            box-shadow: 0 8px 16px rgba(0,0,0,0.06);
            border-color: #007bff;
        }
        .pos-product-card.out-of-stock {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .pos-product-card.out-of-stock:hover {
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
            top: 6px;
            right: 6px;
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
                                <?php if ($p['quantity'] <= 0): ?>
                                    <span class="badge bg-danger text-white">ໝົດສະຕັອກ</span>
                                <?php elseif ($p['quantity'] <= 10): ?>
                                    <span class="badge bg-warning text-dark">ໃກ້ໝົດ: <?= $p['quantity'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-primary text-white">ເຫຼືອ: <?= $p['quantity'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <!-- No Products Message -->
                <div id="noProductsMessage" class="col-12 text-center py-5 text-muted" style="display: none;">
                    <i class="fas fa-box-open fa-3x mb-3 text-light d-block"></i>
                    <span class="fw-bold" style="font-size: 1.1rem; color: #6c757d;">ບໍ່ມີສິນຄ້າທີ່ທ່ານຄົ້ນຫາ</span>
                </div>
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
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary" style="font-size:0.85rem;">ຈຳນວນລາຍການ:</span>
                        <span class="fw-bold text-dark" id="cartCountText">0 ລາຍການ</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="h6 fw-bold text-dark mb-0">ຍອດຊຳລະທັງໝົດ:</span>
                        <span class="h4 fw-bold text-success mb-0" id="cartTotalText">0 ກີບ</span>
                    </div>
                    <button class="btn btn-success w-100 fw-bold py-2 shadow-sm rounded-pill" id="checkoutBtn" disabled onclick="checkout()">
                        <i class="fas fa-cash-register me-2"></i> ຊຳລະເງິນ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Payment Modal ===== -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:450px;" role="document">
        <div class="modal-content border-0 shadow" style="border-radius: 8px; overflow:hidden;">

            <!-- Clean Header -->
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark mb-0" style="font-size:1.1rem;">
                    <i class="fas fa-cash-register mr-2 text-primary"></i>ຊຳລະເງິນ (Payment)
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body" style="padding: 20px;">

                <!-- Total Amount Display -->
                <div class="text-center py-3 mb-4 bg-light rounded" style="border: 1px solid #dee2e6;">
                    <div class="text-muted small mb-1">ຍອດຕ້ອງຊຳລະທັງໝົດ</div>
                    <div class="fw-bold text-primary" id="pmTotalDisplay" style="font-size: 2rem; line-height: 1.2;">0 ກີບ</div>
                    <div class="text-muted small mt-1" id="pmItemCount"></div>
                </div>

                <!-- ✅ ວິທີການຊຳລະ -->
                <div class="mb-4">
                    <label class="fw-bold text-dark small mb-2"><i class="fas fa-wallet mr-2 text-primary"></i>ເລືອກວິທີຊຳລະ</label>
                    <div class="d-flex gap-2">
                        <!-- ເງິນສົດ -->
                        <button type="button" id="btnCash"
                            onclick="selectPayMethod(this,'ເງິນສົດ')"
                            class="flex-fill py-2.5 fw-bold btn btn-primary"
                            style="font-size:0.95rem; border-radius: 4px; transition: all 0.15s; margin: 0;">
                            <i class="fas fa-money-bill-wave mr-1"></i> ເງິນສົດ
                        </button>
                        <!-- ເງິນໂອນ -->
                        <button type="button" id="btnTransfer"
                            onclick="selectPayMethod(this,'ເງິນໂອນ')"
                            class="flex-fill py-2.5 fw-bold btn btn-outline-secondary"
                            style="font-size:0.95rem; border-radius: 4px; transition: all 0.15s; margin: 0;">
                            <i class="fas fa-qrcode mr-1"></i> ເງິນໂອນ
                        </button>
                    </div>
                </div>

                <!-- ✅ ຮັບເງິນ (ສະແດງສະເພາະເງິນສົດ) -->
                <div id="cashReceivedSection" class="mb-3">
                    <label class="fw-bold text-dark small mb-2"><i class="fas fa-hand-holding-usd mr-2 text-success"></i>ຈຳນວນເງິນທີ່ຮັບ (ກີບ)</label>
                    <div class="input-group">
                        <input type="text" id="pmReceived"
                            class="form-control fw-bold text-center form-control-lg"
                            placeholder="0"
                            oninput="calcChange()"
                            style="font-size:1.5rem; height:50px;">
                        <div class="input-group-append">
                            <button class="btn btn-success fw-bold px-3" type="button" onclick="setFullAmount()" style="font-size:0.88rem;">
                                <i class="fas fa-wallet mr-1"></i> ເຕັມຈຳນວນ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ✅ ເງິນທອນ -->
                <div id="changeSection" class="alert alert-success border-success p-3 mb-0" style="display:none; border-radius: 6px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-coins mr-2"></i>ເງິນທອນ</span>
                        <span class="h4 fw-bold mb-0" id="pmChange">0 ກີບ</span>
                    </div>
                </div>

                <!-- ❌ ເງິນບໍ່ພຽງພໍ -->
                <div id="shortSection" class="alert alert-danger border-danger p-3 mb-0" style="display:none; border-radius: 6px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-exclamation-triangle mr-2"></i>ເງິນບໍ່ພຽງພໍ</span>
                        <span class="fw-bold" id="pmShort"></span>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light border-top py-3 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary px-4 fw-bold" data-dismiss="modal" style="border-radius: 4px; margin-right: 8px;">
                    <i class="fas fa-times mr-1"></i> ຍົກເລີກ
                </button>
                <button type="button" class="btn btn-success px-4 fw-bold" id="confirmPayBtn" onclick="confirmPayment()" style="border-radius: 4px;">
                    <i class="fas fa-check-circle mr-1"></i> ຢືນຢັນຊຳລະ
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ===== Receipt Modal ===== -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow:hidden;">
            <div class="modal-header py-3" style="background:linear-gradient(90deg,#28a745,#20c997);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fas fa-file-invoice me-2"></i> ໃບບິນຮັບເງິນ
                </h5>
                <button type="button" class="close border-0 bg-transparent text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="h3">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3" id="receiptPrintArea"></div>
            <div class="modal-footer" style="background:#f8f9fa; border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
                <button class="btn btn-outline-secondary flex-fill rounded-pill" data-dismiss="modal" style="margin-right: 8px;">
                    <i class="fas fa-times me-1"></i> ປິດ
                </button>
                <button class="btn btn-primary flex-fill rounded-pill fw-bold" onclick="printReceipt()">
                    <i class="fas fa-print me-1"></i> ພິມໃບບິນ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let productsList = <?= json_encode($products) ?>;
const gymSettings = <?= json_encode($gymSettings) ?>;

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

function removeFromCart(index) {
    cart.splice(index, 1);
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
                <div style="flex:1; min-width:0; margin-left: 6px; margin-right: 6px;">
                    <div class="fw-bold text-dark text-truncate" style="font-size:0.88rem; line-height:1.2;" title="${item.product_name}">${item.product_name}</div>
                    <div class="text-muted" style="font-size:0.78rem; margin-top:2px;">${priceNum} × ${qtyNum}</div>
                </div>
                <div class="d-flex align-items-center flex-shrink-0">
                    <div class="d-flex align-items-center" style="gap: 6px;">
                        <button class="qty-btn minus" onclick="updateQty(${index}, -1)" title="ຫຼຸດ"><i class="fas fa-minus" style="font-size:0.7rem;"></i></button>
                        <span class="fw-bold text-dark" style="font-size:0.95rem; min-width:24px; text-align:center;">${item.quantity}</span>
                        <button class="qty-btn" onclick="updateQty(${index}, 1)" title="ເພີ່ມ"><i class="fas fa-plus" style="font-size:0.7rem;"></i></button>
                    </div>
                    <div class="text-right ml-3" style="min-width: 100px;">
                        <span class="fw-bold text-success" style="font-size:0.9rem;">${subtotalNum} ກີບ</span>
                    </div>
                    <button class="btn btn-link text-danger p-0 border-0 ml-3" onclick="removeFromCart(${index})" title="ລົບລາຍການ" style="font-size:1rem; outline:none; box-shadow:none;">
                        <i class="fas fa-trash-alt"></i>
                    </button>
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

// ======== Payment Modal JS ========
let currentPaymentMethod = 'ເງິນສົດ';

function checkout() {
    if (cart.length === 0) return;
    let total = calculateTotal();
    let count = cart.reduce((s, i) => s + i.quantity, 0);

    $('#pmTotalDisplay').text(formatCurrency(total));
    $('#pmItemCount').text(count + ' ລາຍການ · ' + cart.length + ' ສິນຄ້າ');

    // Auto-fill full amount
    $('#pmReceived').val(new Intl.NumberFormat('en-US').format(total));
    $('#changeSection').show();
    $('#pmChange').text('0 ກີບ');
    $('#shortSection').hide();
    $('#cashReceivedSection').show();

    currentPaymentMethod = 'ເງິນສົດ';
    // Reset button styles
    $('#btnCash').css({ background:'#007bff', color:'#fff', border:'1px solid #007bff', boxShadow:'none' });
    $('#btnTransfer').css({ background:'#fff', color:'#495057', border:'1px solid #ced4da', boxShadow:'none' });

    $('#confirmPayBtn').prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> ຢືນຢັນຊຳລະ');
    $('#paymentModal').modal('show');
    setTimeout(function() { $('#pmReceived').select(); }, 400);
}

function selectPayMethod(btn, method) {
    currentPaymentMethod = method;
    if (method === 'ເງິນສົດ') {
        $('#btnCash').css({ background:'#007bff', color:'#fff', border:'1px solid #007bff', boxShadow:'none' });
        $('#btnTransfer').css({ background:'#fff', color:'#495057', border:'1px solid #ced4da', boxShadow:'none' });
        $('#cashReceivedSection').slideDown(200);
        calcChange();
    } else {
        $('#btnTransfer').css({ background:'#28a745', color:'#fff', border:'1px solid #28a745', boxShadow:'none' });
        $('#btnCash').css({ background:'#fff', color:'#495057', border:'1px solid #ced4da', boxShadow:'none' });
        $('#cashReceivedSection').slideUp(200);
        $('#changeSection').hide();
        $('#shortSection').hide();
        $('#confirmPayBtn').prop('disabled', false);
    }
}

function setFullAmount() {
    let total = calculateTotal();
    $('#pmReceived').val(new Intl.NumberFormat('en-US').format(total));
    calcChange();
    $('#pmReceived').select();
}

function calcChange() {
    let total = calculateTotal();
    let rawVal = ($('#pmReceived').val() || '').replace(/[^0-9]/g, '');
    let received = parseFloat(rawVal) || 0;
    if (received <= 0) {
        $('#changeSection').hide();
        $('#shortSection').hide();
        $('#confirmPayBtn').prop('disabled', true);
        return;
    }
    let change = received - total;
    if (change >= 0) {
        $('#changeSection').show();
        $('#shortSection').hide();
        $('#pmChange').text(new Intl.NumberFormat('lo-LA').format(change) + ' ກີບ');
        $('#confirmPayBtn').prop('disabled', false);
    } else {
        $('#changeSection').hide();
        $('#shortSection').show();
        $('#pmShort').text('ຂາດ ' + new Intl.NumberFormat('lo-LA').format(Math.abs(change)) + ' ກີບ');
        $('#confirmPayBtn').prop('disabled', true);
    }
}

function confirmPayment() {
    let total = calculateTotal();
    let received, change;
    if (currentPaymentMethod === 'ເງິນໂອນ') {
        received = total; change = 0;
    } else {
        let rawVal = ($('#pmReceived').val() || '').replace(/[^0-9]/g, '');
        received = parseFloat(rawVal) || 0;
        if (received < total) {
            Swal.fire({ icon:'warning', title:'ເງິນບໍ່ພຽງພໍ', text:'ກະລຸນາໃສ່ຈຳນວນເງິນທີ່ຮັບໃຫ້ຄົບ' });
            return;
        }
        change = received - total;
    }
    let btn = $('#confirmPayBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> ກຳລັງດຳເນີນການ...');
    $.ajax({
        url: '../api/sales_api.php',
        type: 'POST',
        data: {
            action: 'create',
            payment_method: currentPaymentMethod,
            total_amount: total,
            received_amount: received,
            change_amount: change,
            items: JSON.stringify(cart)
        },
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> ຢືນຢັນຊຳລະ');
            if (res.success) {
                $('#paymentModal').modal('hide');
                setTimeout(function() { loadReceipt(res.sale_id, received, change, currentPaymentMethod); }, 450);
            } else {
                Swal.fire({ icon:'error', title:'ຜິດພາດ', text:res.message||'ເກີດຂໍ້ຜິດພາດ' });
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> ຢືນຢັນຊຳລະ');
            let msg = xhr.responseJSON&&xhr.responseJSON.message ? xhr.responseJSON.message : 'ເກີດຂໍ້ຜິດພາດ';
            Swal.fire({ icon:'error', title:'ຜິດພາດ', text:msg });
        }
    });
}

function loadReceipt(saleId, receivedAmt, changeAmt, payMethod) {
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

                let itemRows = '';
                items.forEach(function(item) {
                    let itemTotal = item.price * item.quantity;
                    let qtyDisplay = Number(item.quantity).toLocaleString('en-US');
                    itemRows += `<tr>
                        <td class="text-start">${item.product_name}</td>
                        <td class="text-center">${qtyDisplay}</td>
                        <td class="text-end">${new Intl.NumberFormat('lo-LA').format(itemTotal)}</td>
                    </tr>`;
                });

                let cashRows = '';
                let pm = payMethod || s.payment_method;
                if (pm === 'ເງິນສົດ') {
                    cashRows = `
                        <div class="receipt-total-row">
                            <span>ຮັບເງິນ:</span>
                            <span class="fw-bold">${formatCurrency(receivedAmt)}</span>
                        </div>
                        <div class="receipt-total-row" style="color:#16a34a; font-weight:bold;">
                            <span>ເງິນທອນ:</span>
                            <span>${formatCurrency(changeAmt)}</span>
                        </div>`;
                }

                let html = `
                    <div class="print-receipt-container">
                        <div class="receipt-header">
                            <div class="mb-1">
                                <img src="${gymSettings.logo_path}" alt="${gymSettings.gym_name}" style="max-height: 70px; width: auto; display: inline-block;">
                            </div>
                            <p class="receipt-address">${gymSettings.address}</p>
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
                                    <th class="text-center" style="width:50px;">ຈຳ</th>
                                    <th class="text-end" style="width:90px;">ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>${itemRows}</tbody>
                        </table>
                        <div class="receipt-divider"></div>
                        <div class="receipt-total-section">
                            <div class="receipt-total-row">
                                <span>ວິທີຊຳລະ:</span>
                                <span class="fw-bold">${pm}</span>
                            </div>
                            <div class="receipt-total-row grand-total">
                                <span>ຍອດລວມທັງໝົດ:</span>
                                <span class="text-success">${formatCurrency(s.total_amount)}</span>
                            </div>
                            ${cashRows}
                        </div>
                        <div class="receipt-divider"></div>
                        <p class="receipt-footer">*** ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ ***</p>
                    </div>
                `;

                $('#receiptPrintArea').html(html);
                $('#receiptModal').modal('show');

                // Automatically trigger receipt print once modal is rendered
                setTimeout(function() {
                    printReceipt();
                }, 600);

                $('#receiptModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    location.reload();
                });
            }
        }
    });
}

function printReceipt() {
    let printContents = document.getElementById('receiptPrintArea').innerHTML;
    
    // Remove any existing print frame
    $('#receiptPrintFrame').remove();
    
    // Create a hidden iframe
    let $iframe = $('<iframe id="receiptPrintFrame" style="position: absolute; width: 0; height: 0; border: none;"></iframe>');
    $('body').append($iframe);
    
    let iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
    
    iframeDoc.open();
    iframeDoc.write('<html><head><title>ພິມໃບບິນຮັບເງິນ</title>');
    // Set base href to resolve local relative font files
    iframeDoc.write('<base href="' + window.location.origin + window.location.pathname + '">');
    iframeDoc.write('<link rel="stylesheet" href="../assets/css/local-font.css">');
    iframeDoc.write('<style>');
    iframeDoc.write('@media print { @page { size: 80mm auto; margin: 0; } body { margin: 0; padding: 4mm; } }');
    iframeDoc.write('body { font-family: "Noto Sans Lao", "Noto Sans Lao Looped", Arial, sans-serif; width: 72mm; margin: 0 auto; color: #000; background: #fff; font-size: 11px; line-height: 1.3; }');
    iframeDoc.write('.text-center { text-align: center; } .text-start { text-align: left; } .text-end { text-align: right; }');
    iframeDoc.write('.receipt-header { text-align: center; margin-bottom: 8px; }');
    iframeDoc.write('.receipt-logo { font-size: 16px; font-weight: bold; margin: 0 0 2px 0; color: #111; }');
    iframeDoc.write('.receipt-address { font-size: 9px; color: #666; margin: 0 0 4px 0; }');
    iframeDoc.write('.receipt-title { font-size: 12px; font-weight: bold; margin: 6px 0; text-transform: uppercase; color: #28a745; }');
    iframeDoc.write('.receipt-divider { border-top: 1px dashed #000; margin: 8px 0; }');
    iframeDoc.write('.receipt-meta { font-size: 9.5px; margin-bottom: 6px; } .receipt-meta div { margin-bottom: 3px; }');
    iframeDoc.write('.receipt-table { width: 100%; border-collapse: collapse; margin: 4px 0; }');
    iframeDoc.write('.receipt-table th { font-weight: bold; border-bottom: 1px solid #000; padding: 4px 0; font-size: 10px; }');
    iframeDoc.write('.receipt-table td { padding: 5px 0; font-size: 10.5px; vertical-align: top; }');
    iframeDoc.write('.receipt-total-section { font-size: 11px; margin: 6px 0; } .receipt-total-row { display: flex; justify-content: space-between; padding: 2px 0; }');
    iframeDoc.write('.receipt-total-row.grand-total { font-size: 13px; font-weight: bold; margin-top: 4px; border-top: 1px dashed #000; padding-top: 4px; }');
    iframeDoc.write('.receipt-footer { text-align: center; margin-top: 12px; font-size: 9.5px; font-weight: bold; }');
    iframeDoc.write('</style></head><body>');
    iframeDoc.write(printContents);
    iframeDoc.write('</body></html>');
    iframeDoc.close();
    
    // Wait for fonts/assets to load inside the iframe
    setTimeout(function() {
        $iframe[0].contentWindow.focus();
        $iframe[0].contentWindow.print();
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
            
            // If not found, try to find exact name match
            if (!product) {
                product = productsList.find(p => p.product_name && p.product_name.toLowerCase() === query.toLowerCase());
            }

            if (product) {
                addToCart(product);
                $(this).val('');
                // Reset search grid filter to show all products
                $('.product-card-container').show();
                $('#noProductsMessage').hide();
            }
        }
    });

    // Category tabs filter
    $('.pos-category-tab').on('click', function() {
        $('.pos-category-tab').removeClass('active');
        $(this).addClass('active');
        
        let cat = $(this).data('category');
        let visibleCount = 0;
        if (cat === 'all') {
            $('.product-card-container').show();
            visibleCount = $('.product-card-container').length;
        } else {
            $('.product-card-container').hide();
            let matched = $(`.product-card-container[data-category="${cat}"]`);
            matched.show();
            visibleCount = matched.length;
        }

        if (visibleCount === 0) {
            $('#noProductsMessage').show();
        } else {
            $('#noProductsMessage').hide();
        }
        $('#searchInput').val(''); // clear search when switching categories
    });

    // POS Search bar
    $('#searchInput').on('input', function() {
        let rawQuery = $(this).val().trim();
        let query = rawQuery.toLowerCase();
        
        // If it matches a product code (barcode) exactly, add to cart immediately!
        let exactProduct = productsList.find(p => p.product_code == rawQuery);
        if (exactProduct) {
            addToCart(exactProduct);
            $(this).val('');
            $('.product-card-container').show();
            $('#noProductsMessage').hide();
            return;
        }

        $('.pos-category-tab').removeClass('active');
        $('.pos-category-tab[data-category="all"]').addClass('active'); // reset category tab to all
        
        let visibleCount = 0;
        $('.product-card-container').each(function() {
            let name = String($(this).attr('data-name') || '');
            let code = String($(this).attr('data-code') || '');
            if (name.indexOf(query) > -1 || code.indexOf(query) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0) {
            $('#noProductsMessage').show();
        } else {
            $('#noProductsMessage').hide();
        }
    });

    // Format payment received with commas as the user types
    $(document).on('input', '#pmReceived', function(e) {
        let cursorPosition = this.selectionStart;
        let originalLength = this.value.length;
        let rawValue = this.value.replace(/[^0-9]/g, '');
        if (rawValue === '') {
            this.value = '';
            calcChange();
            return;
        }
        let formattedValue = new Intl.NumberFormat('en-US').format(parseInt(rawValue, 10));
        this.value = formattedValue;
        let lengthDifference = formattedValue.length - originalLength;
        let newCursorPosition = cursorPosition + lengthDifference;
        if (newCursorPosition < 0) newCursorPosition = 0;
        this.setSelectionRange(newCursorPosition, newCursorPosition);
        calcChange();
    });

    // Payment modal: allow Enter key in pmReceived to confirm
    $(document).on('keypress', '#pmReceived', function(e) {
        if (e.which === 13) confirmPayment();
    });
});
</script>
</body>
</html>
