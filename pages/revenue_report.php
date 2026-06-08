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
if (!hasPermission('report_finance', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger fw-bold text-center p-4' style='border-radius:12px;'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

$hasSub = true;
$hasDaily = true;
$hasSales = true;
$hasStock = true;
$hasExp = true;

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$activeTab = $_GET['tab'] ?? 'overview';
if (!in_array($activeTab, ['overview', 'subscription', 'daily', 'pos', 'stock_in', 'expense'])) {
    $activeTab = 'overview';
}

// Enforce tab-level permission constraints and fallback
if ($activeTab === 'overview') {
    if (!($hasSub && $hasDaily && $hasSales && $hasStock && $hasExp)) {
        if ($hasSub) $activeTab = 'subscription';
        elseif ($hasDaily) $activeTab = 'daily';
        elseif ($hasSales) $activeTab = 'pos';
        elseif ($hasStock) $activeTab = 'stock_in';
        elseif ($hasExp) $activeTab = 'expense';
    }
} else {
    $ok = false;
    if ($activeTab === 'subscription' && $hasSub) $ok = true;
    elseif ($activeTab === 'daily' && $hasDaily) $ok = true;
    elseif ($activeTab === 'pos' && $hasSales) $ok = true;
    elseif ($activeTab === 'stock_in' && $hasStock) $ok = true;
    elseif ($activeTab === 'expense' && $hasExp) $ok = true;
    
    if (!$ok) {
        echo "<div class='container mt-5'><div class='alert alert-danger fw-bold text-center p-4' style='border-radius:12px;'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງຂໍ້ມູນໃນໜ້ານີ້</div></div>";
        exit();
    }
}


// 1. Build Where Clauses for all data models depending on the date filters
$whereClauseSub = "";
$whereClauseDaily = "";
$whereClauseSales = "";
$whereClauseStock = "";
$whereClauseExp = "";

if ($startDate !== '' && $endDate !== '') {
    $whereClauseSub   = "WHERE DATE(ms.created_at) >= '$startDate' AND DATE(ms.created_at) <= '$endDate'";
    $whereClauseDaily = "WHERE DATE(checkin_date) >= '$startDate' AND DATE(checkin_date) <= '$endDate'";
    $whereClauseSales = "WHERE DATE(s.sale_date) >= '$startDate' AND DATE(s.sale_date) <= '$endDate'";
    $whereClauseStock = "WHERE DATE(st.stock_in_date) >= '$startDate' AND DATE(st.stock_in_date) <= '$endDate'";
    $whereClauseExp   = "WHERE e.expense_date >= '$startDate' AND e.expense_date <= '$endDate'";
} elseif ($startDate !== '') {
    $whereClauseSub   = "WHERE DATE(ms.created_at) >= '$startDate'";
    $whereClauseDaily = "WHERE DATE(checkin_date) >= '$startDate'";
    $whereClauseSales = "WHERE DATE(s.sale_date) >= '$startDate'";
    $whereClauseStock = "WHERE DATE(st.stock_in_date) >= '$startDate'";
    $whereClauseExp   = "WHERE e.expense_date >= '$startDate'";
} elseif ($endDate !== '') {
    $whereClauseSub   = "WHERE DATE(ms.created_at) <= '$endDate'";
    $whereClauseDaily = "WHERE DATE(checkin_date) <= '$endDate'";
    $whereClauseSales = "WHERE DATE(s.sale_date) <= '$endDate'";
    $whereClauseStock = "WHERE DATE(st.stock_in_date) <= '$endDate'";
    $whereClauseExp   = "WHERE e.expense_date <= '$endDate'";
}

// 2. Fetch Data Lists
// --- Subscriptions ---
$subscriptionsList = [];
$subRevenueSum = 0;
$subRes = mysqli_query($conn, "SELECT ms.*, mb.fname, mb.lname, mb.member_code, p.package_name 
                               FROM memberships ms 
                               LEFT JOIN members mb ON ms.member_id = mb.member_id 
                               LEFT JOIN packages p ON ms.package_id = p.package_id 
                               $whereClauseSub 
                               ORDER BY ms.membership_id DESC");
if ($subRes) {
    while ($row = mysqli_fetch_assoc($subRes)) {
        $subscriptionsList[] = $row;
        $subRevenueSum += (float)$row['price_paid'];
    }
}

// --- Daily Check-ins ---
$dailyList = [];
$dailyRevenueSum = 0;
$dailyRes = mysqli_query($conn, "SELECT d.*, u.fname AS staff_fname, u.lname AS staff_lname 
                                 FROM daily_checkins d 
                                 LEFT JOIN users u ON d.user_id = u.user_id 
                                 $whereClauseDaily 
                                 ORDER BY d.id DESC");
if ($dailyRes) {
    while ($row = mysqli_fetch_assoc($dailyRes)) {
        $dailyList[] = $row;
        $dailyRevenueSum += (float)$row['price_paid'];
    }
}

// --- POS Sales ---
$salesList = [];
$salesRevenueSum = 0;
$salesCostSum = 0;
$salesRes = mysqli_query($conn, "SELECT s.*, u.fname AS staff_fname, u.lname AS staff_lname,
                                       COALESCE(SUM(sd.quantity * p.cost_price), 0) AS total_cost
                                 FROM sales s 
                                 LEFT JOIN users u ON s.user_id = u.user_id 
                                 LEFT JOIN sale_details sd ON s.sale_id = sd.sale_id
                                 LEFT JOIN products p ON sd.product_id = p.product_id
                                 $whereClauseSales 
                                 GROUP BY s.sale_id
                                 ORDER BY s.sale_id DESC");
if ($salesRes) {
    while ($row = mysqli_fetch_assoc($salesRes)) {
        $salesList[] = $row;
        $salesRevenueSum += (float)$row['total_amount'];
        $salesCostSum += (float)$row['total_cost'];
    }
}
$salesProfitSum = $salesRevenueSum - $salesCostSum;

// --- Stock Imports ---
$stockList = [];
$stockExpenseSum = 0;
$stockRes = mysqli_query($conn, "SELECT st.*, u.fname AS staff_fname, u.lname AS staff_lname 
                                 FROM stock_in st 
                                 LEFT JOIN users u ON st.user_id = u.user_id 
                                 $whereClauseStock 
                                 ORDER BY st.stock_in_id DESC");
if ($stockRes) {
    while ($row = mysqli_fetch_assoc($stockRes)) {
        $stockList[] = $row;
        $stockExpenseSum += (float)$row['total_amount'];
    }
}

// --- General Expenses ---
$expensesList = [];
$generalExpenseSum = 0;
$expRes = mysqli_query($conn, "SELECT e.*, u.fname AS staff_fname, u.lname AS staff_lname 
                               FROM expenses e 
                               LEFT JOIN users u ON e.user_id = u.user_id 
                               $whereClauseExp 
                               ORDER BY e.expense_date DESC, e.expense_id DESC");
if ($expRes) {
    while ($row = mysqli_fetch_assoc($expRes)) {
        $expensesList[] = $row;
        $generalExpenseSum += (float)$row['amount'];
    }
}

// 3. Consolidated Totals
$totalRevenue = $subRevenueSum + $dailyRevenueSum + $salesRevenueSum;
$totalExpenses = $stockExpenseSum + $generalExpenseSum;
$netIncome = $totalRevenue - $totalExpenses;

// Cash/Transfer breakdowns for revenue sources
$sub_cash = 0;
$sub_transfer = 0;
$daily_cash = 0;
$daily_transfer = 0;
$pos_cash = 0;
$pos_transfer = 0;

foreach ($subscriptionsList as $s) {
    $pm = trim($s['payment_method'] ?? '');
    if (mb_strpos($pm, 'ສົດ') !== false || mb_strtolower($pm) === 'cash' || mb_strpos(mb_strtolower($pm), 'cash') !== false) {
        $sub_cash += (float)$s['price_paid'];
    } else {
        $sub_transfer += (float)$s['price_paid'];
    }
}
foreach ($dailyList as $d) {
    $pm = trim($d['payment_method'] ?? '');
    if (mb_strpos($pm, 'ສົດ') !== false || mb_strtolower($pm) === 'cash' || mb_strpos(mb_strtolower($pm), 'cash') !== false) {
        $daily_cash += (float)$d['price_paid'];
    } else {
        $daily_transfer += (float)$d['price_paid'];
    }
}
foreach ($salesList as $s) {
    $pm = trim($s['payment_method'] ?? '');
    if (mb_strpos($pm, 'ສົດ') !== false || mb_strtolower($pm) === 'cash' || mb_strpos(mb_strtolower($pm), 'cash') !== false) {
        $pos_cash += (float)$s['total_amount'];
    } else {
        $pos_transfer += (float)$s['total_amount'];
    }
}

$cash_total = $sub_cash + $daily_cash + $pos_cash;
$transfer_total = $sub_transfer + $daily_transfer + $pos_transfer;

// 4. Generate 6-Month Finance Trend (Chart.js)
$chartLabels = [];
$chartRevData = [];
$chartExpData = [];
$loMonths = ['ມັງກອນ','ກຸມພາ','ມີນາ','ເມສາ','ພຶດສະພາ','ມິຖຸນາ','ກໍລະກົດ','ສິງຫາ','ກັນຍາ','ຕຸລາ','ພະຈິກ','ທັນວາ'];

for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $y  = (int)date('Y', $ts);
    $m  = (int)date('n', $ts);
    $chartLabels[] = $loMonths[$m - 1] . ' ' . ($y + 543);
    
    // Subscriptions
    $subQ = mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM memberships WHERE YEAR(created_at)=$y AND MONTH(created_at)=$m");
    $subVal = (float)(mysqli_fetch_row($subQ)[0] ?? 0);
    
    // Daily check-ins
    $dailyQ = mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM daily_checkins WHERE YEAR(checkin_date)=$y AND MONTH(checkin_date)=$m");
    $dailyVal = (float)(mysqli_fetch_row($dailyQ)[0] ?? 0);
    
    // POS Sales
    $salesQ = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE YEAR(sale_date)=$y AND MONTH(sale_date)=$m");
    $salesVal = (float)(mysqli_fetch_row($salesQ)[0] ?? 0);
    
    $totalRev = $subVal + $dailyVal + $salesVal;
    $chartRevData[] = $totalRev;
    
    // Imports
    $importQ = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM stock_in WHERE YEAR(stock_in_date)=$y AND MONTH(stock_in_date)=$m");
    $importVal = (float)(mysqli_fetch_row($importQ)[0] ?? 0);
    
    // General expenses
    $expQ = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE YEAR(expense_date)=$y AND MONTH(expense_date)=$m");
    $expVal = (float)(mysqli_fetch_row($expQ)[0] ?? 0);
    
    $totalExp = $importVal + $expVal;
    $chartExpData[] = $totalExp;
}

// Additional tab-specific calculations
// --- Subscriptions ---
$sub_today_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM memberships WHERE DATE(created_at) = CURDATE()"))[0] ?? 0;
$sub_today_cash = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM memberships WHERE DATE(created_at) = CURDATE() AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')"))[0] ?? 0;
$sub_today_transfer = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM memberships WHERE DATE(created_at) = CURDATE() AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')"))[0] ?? 0;
$sub_month_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM memberships WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))[0] ?? 0;

// --- Daily ---
$daily_today_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM daily_checkins WHERE checkin_date = CURDATE()"))[0] ?? 0;
$daily_today_cash = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM daily_checkins WHERE checkin_date = CURDATE() AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')"))[0] ?? 0;
$daily_today_transfer = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM daily_checkins WHERE checkin_date = CURDATE() AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')"))[0] ?? 0;
$daily_month_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM daily_checkins WHERE MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE())"))[0] ?? 0;

// --- POS Sales ---
$pos_today_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(sale_date) = CURDATE()"))[0] ?? 0;
$pos_today_cash = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(sale_date) = CURDATE() AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')"))[0] ?? 0;
$pos_today_transfer = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(sale_date) = CURDATE() AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')"))[0] ?? 0;
$pos_month_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())"))[0] ?? 0;

// --- Stock Imports ---
$stock_today_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM stock_in WHERE DATE(stock_in_date) = CURDATE()"))[0] ?? 0;
$stock_month_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM stock_in WHERE MONTH(stock_in_date) = MONTH(CURDATE()) AND YEAR(stock_in_date) = YEAR(CURDATE())"))[0] ?? 0;
$stock_all_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) FROM stock_in"))[0] ?? 0;

// --- Expenses ---
$exp_today_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date = CURDATE()"))[0] ?? 0;
$exp_month_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())"))[0] ?? 0;
$exp_all_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM expenses"))[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານການເງິນ ລາຍຮັບ-ລາຍຈ່າຍ</title>
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    
    <style>
        body {
            font-family: 'Noto Sans Lao', 'Noto Sans Lao Looped', sans-serif;
            background-color: #f4f6f9;
        }
        .stat-card-rev {
            border-radius: 16px;
            border: none;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            padding: 14px 18px !important;
        }
        .stat-card-rev:hover {
            transform: translateY(-4px);
        }
        .stat-card-rev h3 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 2px;
            margin-top: 2px;
            letter-spacing: -0.5px;
        }
        .stat-card-rev small {
            font-size: 0.8rem;
            font-weight: 500;
            opacity: 0.85;
        }
        .stat-card-icon-right {
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.1);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .stat-card-rev:hover .stat-card-icon-right {
            color: rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.18);
            transform: scale(1.08) rotate(5deg);
        }
        .stat-card-rev * {
            position: relative;
            z-index: 2;
        }
        
        .row-tight {
            display: flex;
            flex-wrap: wrap;
            margin-right: -8px !important;
            margin-left: -8px !important;
        }
        .row-tight > [class*='col-'] {
            padding-right: 8px !important;
            padding-left: 8px !important;
            margin-bottom: 16px !important;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            background: transparent;
            padding: 12px 18px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .nav-tabs .nav-link:focus, .nav-tabs .nav-link:active {
            outline: none !important;
            box-shadow: none !important;
        }
        .nav-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: rgba(13, 110, 253, 0.2);
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd !important;
            border-bottom: 3px solid #0d6efd !important;
            background: transparent !important;
            font-weight: 700;
        }
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .no-print {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: none !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-chart-line text-primary me-2"></i> ລາຍງານການເງິນ ລາຍຮັບ-ລາຍຈ່າຍ
            </h4>
            <p class="text-muted small mb-0">ຕິດຕາມສະຫຼຸບລາຍຮັບ-ລາຍຈ່າຍ ແລະ ສັງລວມກຳໄລສຸດທິຂອງສະໂມສອນ</p>
        </div>
        <div>
            <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-0">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                
                <div class="input-group input-group-sm" style="width: 170px;">
                    <span class="input-group-text bg-light text-muted border-end-0" style="font-size: 0.75rem;">ເລີ່ມ</span>
                    <input type="date" name="start_date" class="form-control ps-1 border-start-0" style="font-size: 0.8rem;" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                
                <div class="input-group input-group-sm" style="width: 170px;">
                    <span class="input-group-text bg-light text-muted border-end-0" style="font-size: 0.75rem;">ຫາ</span>
                    <input type="date" name="end_date" class="form-control ps-1 border-start-0" style="font-size: 0.8rem;" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                
                <button type="submit" class="btn btn-sm btn-primary rounded-1" title="ຄົ້ນຫາ">
                    <i class="fas fa-search"></i>
                </button>
                
                <a href="revenue_report.php?tab=<?= htmlspecialchars($activeTab) ?>" class="btn btn-sm btn-light border rounded-1" title="ໂຫຼດຄືນໃໝ່">
                    <i class="fas fa-sync-alt"></i>
                </a>
                
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 ms-1 shadow-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> ພິມ
                </button>
            </form>
        </div>
    </div>

    <!-- Print Title Sheet -->
    <div class="d-none d-print-block text-center mb-4">
        <h2>ລາຍງານການເງິນ ລາຍຮັບ-ລາຍຈ່າຍ ຍິມ & ຟິດເນັດ</h2>
        <?php if ($startDate !== '' || $endDate !== ''): ?>
            <p>ໄລຍະເວລາ: <?= $startDate ? date('d/m/Y', strtotime($startDate)) : 'ເລີ່ມຕົ້ນ' ?> ຫາ <?= $endDate ? date('d/m/Y', strtotime($endDate)) : 'ປັດຈຸບັນ' ?></p>
        <?php else: ?>
            <p>ລາຍງານສະຫຼຸບການເງິນທັງໝົດໃນລະບົບ</p>
        <?php endif; ?>
        <p class="small text-muted">ວັນທີດຶງລາຍງານ: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <!-- Stats Cards Row (Dynamic based on selected tab) -->
    <?php if ($activeTab === 'overview'): ?>
    <div class="row-tight mb-2">
        <!-- Card 1: Total Revenue -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຮັບລວມທັງໝົດ</small>
                        <h3><?= formatCurrency($totalRevenue) ?></h3>
                        <small class="text-white-50"><i class="fas fa-arrow-alt-circle-up mr-1"></i> ສົດ: <?= formatCurrency($cash_total) ?> | ໂອນ: <?= formatCurrency($transfer_total) ?></small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-arrow-circle-up"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Total Expenses -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); box-shadow: 0 8px 20px rgba(255, 88, 88, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍລວມທັງໝົດ</small>
                        <h3><?= formatCurrency($totalExpenses) ?></h3>
                        <small class="text-white-50"><i class="fas fa-arrow-alt-circle-down mr-1"></i> Stock Imports + General Exp</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-arrow-circle-down"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Net Profit/Loss -->
        <div class="col-md-4">
            <?php 
            $isProfit = ($netIncome >= 0);
            $gradientColor = $isProfit ? "linear-gradient(135deg, #3a7bd5 0%, #3a6073 100%)" : "linear-gradient(135deg, #870000 0%, #190000 100%)";
            $shadowColor = $isProfit ? "rgba(58, 123, 213, 0.15)" : "rgba(135, 0, 0, 0.15)";
            ?>
            <div class="card stat-card-rev bg-gradient" style="background: <?= $gradientColor ?>; box-shadow: 0 8px 20px <?= $shadowColor ?>;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ກຳໄລ / ຂາດທຶນ ສຸດທິ</small>
                        <h3><?= formatCurrency($netIncome) ?></h3>
                        <small class="text-white-50"><i class="fas fa-balance-scale mr-1"></i> ຍອດຄົງເຫຼືອທັງໝົດ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas <?= $isProfit ? 'fa-smile' : 'fa-sad-tear' ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'subscription'): ?>
    <div class="row-tight mb-2">
        <!-- Card 1: Total Subscription Revenue -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຮັບຄ່າສະໝັກສະມາຊິກລວມ</small>
                        <h3><?= formatCurrency($subRevenueSum) ?></h3>
                        <small class="text-white-50"><i class="fas fa-file-invoice-dollar mr-1"></i> ທັງໝົດຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Cash Subscriptions -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); box-shadow: 0 8px 20px rgba(0, 114, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຮັບເປັນເງິນສົດ</small>
                        <h3><?= formatCurrency($sub_cash) ?></h3>
                        <small class="text-white-50"><i class="fas fa-money-bill-wave mr-1"></i> ຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Transfer Subscriptions -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #7F00FF 0%, #E100FF 100%); box-shadow: 0 8px 20px rgba(225, 0, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຮັບເປັນເງິນໂອນ</small>
                        <h3><?= formatCurrency($sub_transfer) ?></h3>
                        <small class="text-white-50"><i class="fas fa-credit-card mr-1"></i> ຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'daily'): ?>
    <div class="row-tight mb-2">
        <!-- Card 1: Total Daily Revenue -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຮັບຄ່າລາຍວັນລວມ</small>
                        <h3><?= formatCurrency($dailyRevenueSum) ?></h3>
                        <small class="text-white-50"><i class="fas fa-user-clock mr-1"></i> ທັງໝົດຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Cash Daily -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); box-shadow: 0 8px 20px rgba(0, 114, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຮັບເປັນເງິນສົດ</small>
                        <h3><?= formatCurrency($daily_cash) ?></h3>
                        <small class="text-white-50"><i class="fas fa-money-bill-wave mr-1"></i> ຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Transfer Daily -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #7F00FF 0%, #E100FF 100%); box-shadow: 0 8px 20px rgba(225, 0, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຮັບເປັນເງິນໂອນ</small>
                        <h3><?= formatCurrency($daily_transfer) ?></h3>
                        <small class="text-white-50"><i class="fas fa-credit-card mr-1"></i> ຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'pos'): ?>
    <div class="row-tight mb-2">
        <!-- Card 1: POS Sales Today -->
        <div class="col-md-3">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 8px 20px rgba(56, 239, 125, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຍອດຂາຍມື້ນີ້</small>
                        <h3><?= formatCurrency($pos_today_total) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-day mr-1"></i> ປະຈຳວັນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Cash POS Today -->
        <div class="col-md-3">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); box-shadow: 0 8px 20px rgba(0, 114, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຍອດຂາຍມື້ນີ້ (ເງິນສົດ)</small>
                        <h3><?= formatCurrency($pos_today_cash) ?></h3>
                        <small class="text-white-50"><i class="fas fa-money-bill-wave mr-1"></i> ປະຈຳວັນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Transfer POS Today -->
        <div class="col-md-3">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #7F00FF 0%, #E100FF 100%); box-shadow: 0 8px 20px rgba(225, 0, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຍອດຂາຍມື້ນີ້ (ເງິນໂອນ)</small>
                        <h3><?= formatCurrency($pos_today_transfer) ?></h3>
                        <small class="text-white-50"><i class="fas fa-credit-card mr-1"></i> ປະຈຳວັນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 4: POS Sales This Month -->
        <div class="col-md-3">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #FF8008 0%, #FFC837 100%); box-shadow: 0 8px 20px rgba(255, 128, 8, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຍອດຂາຍເດືອນນີ້</small>
                        <h3><?= formatCurrency($pos_month_total) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-alt mr-1"></i> ປະຈຳເດືອນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'stock_in'): ?>
    <div class="row-tight mb-2">
        <!-- Card 1: Filtered Stock Imports -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); box-shadow: 0 8px 20px rgba(255, 88, 88, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ຕົ້ນທຶນນຳເຂົ້າສິນຄ້າລວມ</small>
                        <h3><?= formatCurrency($stockExpenseSum) ?></h3>
                        <small class="text-white-50"><i class="fas fa-file-import mr-1"></i> ຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-file-import"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: Stock Imports This Month -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #e65c00 0%, #F9D423 100%); box-shadow: 0 8px 20px rgba(230, 92, 0, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ນຳເຂົ້າສິນຄ້າເດືອນນີ້</small>
                        <h3><?= formatCurrency($stock_month_total) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-alt mr-1"></i> ປະຈຳເດືອນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Stock Imports Accumulative -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #1f4037 0%, #99f2c8 100%); box-shadow: 0 8px 20px rgba(31, 64, 55, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ນຳເຂົ້າສິນຄ້າສະສົມທັງໝົດ</small>
                        <h3><?= formatCurrency($stock_all_total) ?></h3>
                        <small class="text-white-50"><i class="fas fa-warehouse mr-1"></i> ລວມສະສົມ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-warehouse"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'expense'): ?>
    <div class="row-tight mb-2">
        <!-- Card 1: Filtered General Expenses -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); box-shadow: 0 8px 20px rgba(255, 88, 88, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍທົ່ວໄປລວມ</small>
                        <h3><?= formatCurrency($generalExpenseSum) ?></h3>
                        <small class="text-white-50"><i class="fas fa-minus-circle mr-1"></i> ຕາມວັນທີທີ່ເລືອກ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-minus-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: General Expenses This Month -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #e65c00 0%, #F9D423 100%); box-shadow: 0 8px 20px rgba(230, 92, 0, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍທົ່ວໄປເດືອນນີ້</small>
                        <h3><?= formatCurrency($exp_month_total) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-alt mr-1"></i> ປະຈຳເດືອນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Accumulative General Expenses -->
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #1f4037 0%, #99f2c8 100%); box-shadow: 0 8px 20px rgba(31, 64, 55, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍທົ່ວໄປສະສົມທັງໝົດ</small>
                        <h3><?= formatCurrency($exp_all_total) ?></h3>
                        <small class="text-white-50"><i class="fas fa-wallet mr-1"></i> ລວມສະສົມ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Active Tab Pane -->
    <div class="card card-custom">
        <div class="card-body p-0">

            <!-- CASE 1: OVERVIEW -->
            <?php if ($activeTab === 'overview'): ?>
            <div class="p-4">
                
                <!-- Section: Revenues -->
                <div class="mb-4">
                    <h6 class="fw-bold text-success mb-3">
                        <i class="fas fa-plus-circle me-1"></i> ສະຫຼຸບລາຍຮັບ (Revenues)
                    </h6>
                    <div class="row g-3">
                        <!-- Subscriptions Card -->
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 border border-light shadow-sm bg-white" style="border-radius: 12px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark"><i class="fas fa-id-card text-success me-1"></i> ຄ່າສະໝັກສະມາຊິກ</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($subRevenueSum) ?></h4>
                                    <div class="d-flex justify-content-between text-muted small" style="font-size: 0.75rem;">
                                        <span>ເງິນສົດ: <?= formatCurrency($sub_cash) ?></span>
                                        <span>ເງິນໂອນ: <?= formatCurrency($sub_transfer) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daily Check-ins Card -->
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 border border-light shadow-sm bg-white" style="border-radius: 12px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark"><i class="fas fa-user-clock text-info me-1"></i> ຄ່າລາຍວັນ</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($dailyRevenueSum) ?></h4>
                                    <div class="d-flex justify-content-between text-muted small" style="font-size: 0.75rem;">
                                        <span>ເງິນສົດ: <?= formatCurrency($daily_cash) ?></span>
                                        <span>ເງິນໂອນ: <?= formatCurrency($daily_transfer) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- POS Sales Card -->
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 border border-light shadow-sm bg-white" style="border-radius: 12px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark"><i class="fas fa-cash-register text-primary me-1"></i> ຍອດຂາຍ POS</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($salesRevenueSum) ?></h4>
                                    <div class="d-flex justify-content-between text-muted small" style="font-size: 0.75rem;">
                                        <span>ເງິນສົດ: <?= formatCurrency($pos_cash) ?></span>
                                        <span>ເງິນໂອນ: <?= formatCurrency($pos_transfer) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Revenue Card -->
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 border-success shadow-sm" style="border-radius: 12px; background-color: #f4fff7; border: 1px solid #c3e6cb !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-success"><i class="fas fa-calculator me-1"></i> ລວມລາຍຮັບທັງໝົດ</span>
                                    </div>
                                    <h4 class="fw-bold text-success mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($totalRevenue) ?></h4>
                                    <div class="d-flex justify-content-between text-success-800 small" style="font-size: 0.75rem; color: #155724;">
                                        <span>ເງິນສົດ: <?= formatCurrency($cash_total) ?></span>
                                        <span>ເງິນໂອນ: <?= formatCurrency($transfer_total) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section: Expenses -->
                <div class="mb-4">
                    <h6 class="fw-bold text-danger mb-3">
                        <i class="fas fa-minus-circle me-1"></i> ສະຫຼຸບລາຍຈ່າຍ (Expenses)
                    </h6>
                    <div class="row g-3">
                        <!-- Stock Imports Card -->
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 border border-light shadow-sm bg-white" style="border-radius: 12px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark"><i class="fas fa-file-import text-warning me-1"></i> ຕົ້ນທຶນນຳເຂົ້າສິນຄ້າ</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($stockExpenseSum) ?></h4>
                                    <span class="text-muted small" style="font-size: 0.75rem;">ນຳເຂົ້າສິນຄ້າຮ້ານຄ້າ</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- General Expenses Card -->
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 border border-light shadow-sm bg-white" style="border-radius: 12px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-dark"><i class="fas fa-minus-circle text-danger me-1"></i> ລາຍຈ່າຍທົ່ວໄປ</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($generalExpenseSum) ?></h4>
                                    <span class="text-muted small" style="font-size: 0.75rem;">ຄ່າໃຊ້ຈ່າຍບໍລິຫານ ແລະ ອື່ນໆ</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Expenses Card -->
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 border-danger shadow-sm" style="border-radius: 12px; background-color: #fff5f5; border: 1px solid #f5c6cb !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-danger"><i class="fas fa-wallet me-1"></i> ລວມລາຍຈ່າຍທັງໝົດ</span>
                                    </div>
                                    <h4 class="fw-bold text-danger mb-2" style="letter-spacing: -0.5px;"><?= formatCurrency($totalExpenses) ?></h4>
                                    <span class="text-danger small" style="font-size: 0.75rem; color: #721c24;">ລວມລາຍຈ່າຍທັງໝົດຂອງລະບົບ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Section: Charts -->
                <div class="row g-4">
                    <!-- Chart 1: Bar/Line Trend -->
                    <div class="col-lg-8">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar text-primary me-2"></i>ແນວໂນ້ມລາຍຮັບ - ລາຍຈ່າຍ 6 ເດືອນຫຼ້າສຸດ</h6>
                        <div class="card border border-light shadow-sm p-3 bg-white" style="border-radius: 12px;">
                            <canvas id="financialChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                    <!-- Chart 2: Doughnut Revenue Sources -->
                    <div class="col-lg-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie text-secondary me-2"></i>ສັດສ່ວນແຫຼ່ງລາຍຮັບ (Revenue Sources)</h6>
                        <div class="card border border-light shadow-sm p-3 bg-white d-flex flex-column justify-content-center align-items-center" style="border-radius: 12px; min-height: 330px;">
                            <div style="width: 100%; max-width: 220px; position: relative;">
                                <canvas id="revenueSourcesChart" style="max-height: 220px; max-width: 220px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CASE 2: SUBSCRIPTIONS -->
            <?php elseif ($activeTab === 'subscription'): ?>
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
                <div class="text-muted small">
                    ລາຍການສະໝັກສະມາຊິກ: <span class="fw-bold text-primary"><?= count($subscriptionsList) ?></span> ລາຍການ
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="subSearchInput" class="form-control" placeholder="ຄົ້ນຫາຊື່, ລະຫັດ, ແພັກເກດ...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">ລະຫັດສະມາຊິກ</th>
                            <th>ຊື່ສະມາຊິກ</th>
                            <th>ແພັກເກດ</th>
                            <th class="text-center">ຊຳລະໂດຍ</th>
                            <th class="text-center">ວັນທີສະໝັກ</th>
                            <th class="text-end" style="width: 180px;">ຍອດເງິນຊຳລະ</th>
                        </tr>
                    </thead>
                    <tbody id="subTableBody">
                        <?php if (empty($subscriptionsList)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນລາຍຮັບ</td></tr>
                        <?php else: ?>
                            <?php foreach ($subscriptionsList as $s): ?>
                                <tr class="sub-row">
                                    <td class="text-center"><code><?= htmlspecialchars($s['member_code'] ?? '-') ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars(($s['fname'] ?? 'ລົບແລ້ວ') . ' ' . ($s['lname'] ?? '')) ?></td>
                                    <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($s['package_name'] ?: 'ບໍ່ລະບຸ') ?></span></td>
                                    <td class="text-center"><?= htmlspecialchars($s['payment_method']) ?></td>
                                    <td class="text-center text-muted"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($s['price_paid']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">ລວມຄ່າສະໝັກສະມາຊິກທັງໝົດ:</td>
                            <td class="text-end text-success" style="font-size:1.1rem;"><?= formatCurrency($subRevenueSum) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- CASE 3: DAILY -->
            <?php elseif ($activeTab === 'daily'): ?>
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
                <div class="text-muted small">
                    ລາຍການລູກຄ້າລາຍວັນ: <span class="fw-bold text-primary"><?= count($dailyList) ?></span> ລາຍການ
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dailySearchInput" class="form-control" placeholder="ຄົ້ນຫາເພດ, ພະນັກງານ, ວິທີຊຳລະ...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">ລະຫັດ / ລາຍການ</th>
                            <th>ລາຍລະອຽດ</th>
                            <th class="text-center">ຊຳລະໂດຍ</th>
                            <th class="text-center">ວັນທີເຂົ້າໃຊ້</th>
                            <th class="text-center">ພະນັກງານບັນທຶກ</th>
                            <th class="text-end" style="width: 180px;">ຍອດເງິນຊຳລະ</th>
                        </tr>
                    </thead>
                    <tbody id="dailyTableBody">
                        <?php if (empty($dailyList)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນລາຍຮັບ</td></tr>
                        <?php else: ?>
                            <?php foreach ($dailyList as $d): ?>
                                <tr class="daily-row">
                                    <td class="text-center"><span class="badge bg-secondary text-white">ລາຍວັນ</span></td>
                                    <td class="fw-bold text-dark">ລູກຄ້າລາຍວັນ (ເພດ <?= htmlspecialchars($d['gender'] ?? '-') ?>)</td>
                                    <td class="text-center"><?= htmlspecialchars($d['payment_method']) ?></td>
                                    <td class="text-center text-muted"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                                    <?php 
                                        $staffName = trim(($d['staff_fname'] ?? '') . ' ' . ($d['staff_lname'] ?? ''));
                                        if ($staffName === '') $staffName = 'Admin';
                                    ?>
                                    <td class="text-center"><?= htmlspecialchars($staffName) ?></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($d['price_paid']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">ລວມຄ່າລາຍວັນທັງໝົດ:</td>
                            <td class="text-end text-success" style="font-size:1.1rem;"><?= formatCurrency($dailyRevenueSum) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- CASE 4: POS SALES -->
            <?php elseif ($activeTab === 'pos'): ?>
            <div class="row-tight p-3 no-print">
                <div class="col-md-4">
                    <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <small class="text-white-50 font-weight-bold">ຍອດຂາຍສິນຄ້າລວມ</small>
                                <h3><?= formatCurrency($salesRevenueSum) ?></h3>
                            </div>
                            <div class="stat-card-icon-right">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%);">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <small class="text-white-50 font-weight-bold">ຕົ້ນທຶນສິນຄ້າລວມ</small>
                                <h3><?= formatCurrency($salesCostSum) ?></h3>
                            </div>
                            <div class="stat-card-icon-right">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php $isPOSProfit = ($salesProfitSum >= 0); ?>
                    <div class="card stat-card-rev bg-gradient" style="background: <?= $isPOSProfit ? 'linear-gradient(135deg, #3a7bd5 0%, #3a6073 100%)' : 'linear-gradient(135deg, #870000 0%, #190000 100%)' ?>;">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <small class="text-white-50 font-weight-bold">ກຳໄລຈາກການຂາຍ</small>
                                <h3><?= formatCurrency($salesProfitSum) ?></h3>
                            </div>
                            <div class="stat-card-icon-right">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
                <div class="text-muted small">
                    ລາຍການຂາຍສິນຄ້າ POS: <span class="fw-bold text-primary"><?= count($salesList) ?></span> ລາຍການ
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="posSearchInput" class="form-control" placeholder="ຄົ້ນຫາລະຫັດໃບບິນ, ພະນັກງານ, ວິທີຊຳລະ...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 140px;">ວັນທີຂາຍ</th>
                            <th>ລະຫັດໃບບິນ</th>
                            <th class="text-end" style="width: 150px;">ຍອດຂາຍ</th>
                            <th class="text-end" style="width: 150px;">ຕົ້ນທຶນ</th>
                            <th class="text-end" style="width: 150px;">ກຳໄລ</th>
                            <th class="text-center">ຊຳລະໂດຍ</th>
                            <th class="text-center">ພະນັກງານຂາຍ</th>
                        </tr>
                    </thead>
                    <tbody id="posTableBody">
                        <?php if (empty($salesList)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນການຂາຍ</td></tr>
                        <?php else: ?>
                            <?php foreach ($salesList as $s): ?>
                                <tr class="pos-row">
                                    <td class="text-center fw-bold"><?= date('d/m/Y H:i', strtotime($s['sale_date'])) ?></td>
                                    <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($s['sale_code']) ?></span></td>
                                    <td class="text-end fw-bold text-success"><?= formatCurrency($s['total_amount']) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= formatCurrency($s['total_cost']) ?></td>
                                    <?php 
                                        $pft = (float)$s['total_amount'] - (float)$s['total_cost'];
                                        $pftClass = ($pft >= 0) ? 'text-primary' : 'text-danger';
                                    ?>
                                    <td class="text-end fw-bold <?= $pftClass ?>"><?= formatCurrency($pft) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($s['payment_method']) ?></td>
                                    <?php 
                                        $staffName = trim(($s['staff_fname'] ?? '') . ' ' . ($s['staff_lname'] ?? ''));
                                        if ($staffName === '') $staffName = 'Admin';
                                    ?>
                                    <td class="text-center"><?= htmlspecialchars($staffName) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-end">ລວມທັງໝົດ:</td>
                            <td class="text-end text-success"><?= formatCurrency($salesRevenueSum) ?></td>
                            <td class="text-end text-danger"><?= formatCurrency($salesCostSum) ?></td>
                            <td class="text-end <?= ($salesProfitSum >= 0) ? 'text-primary' : 'text-danger' ?>"><?= formatCurrency($salesProfitSum) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- CASE 5: STOCK IN COSTS -->
            <?php elseif ($activeTab === 'stock_in'): ?>
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
                <div class="text-muted small">
                    ລາຍການນຳເຂົ້າສິນຄ້າ: <span class="fw-bold text-danger"><?= count($stockList) ?></span> ລາຍການ
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="stockSearchInput" class="form-control" placeholder="ຄົ້ນຫາລະຫັດ, ພະນັກງານ...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 140px;">ວັນທີນຳເຂົ້າ</th>
                            <th>ລະຫັດນຳເຂົ້າ</th>
                            <th class="text-center">ພະນັກງານບັນທຶກ</th>
                            <th class="text-end" style="width: 180px;">ລວມຄ່າໃຊ້ຈ່າຍ</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <?php if (empty($stockList)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນການນຳເຂົ້າ</td></tr>
                        <?php else: ?>
                            <?php foreach ($stockList as $st): ?>
                                <tr class="stock-row">
                                    <td class="text-center fw-bold"><?= date('d/m/Y H:i', strtotime($st['stock_in_date'])) ?></td>
                                    <td><span class="badge bg-light text-secondary border fw-bold">#<?= str_pad($st['stock_in_id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                    <?php 
                                        $staffName = trim(($st['staff_fname'] ?? '') . ' ' . ($st['staff_lname'] ?? ''));
                                        if ($staffName === '') $staffName = 'Admin';
                                    ?>
                                    <td class="text-center"><?= htmlspecialchars($staffName) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= formatCurrency($st['total_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end">ລວມຕົ້ນທຶນນຳເຂົ້າສິນຄ້າທັງໝົດ:</td>
                            <td class="text-end text-danger" style="font-size:1.1rem;"><?= formatCurrency($stockExpenseSum) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- CASE 6: GENERAL EXPENSES -->
            <?php elseif ($activeTab === 'expense'): ?>
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
                <div class="text-muted small">
                    ລາຍການລາຍຈ່າຍທົ່ວໄປ: <span class="fw-bold text-danger"><?= count($expensesList) ?></span> ລາຍການ
                </div>
                <div class="search-box flex-grow-1" style="max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="expSearchInput" class="form-control" placeholder="ຄົ້ນຫາຫົວຂໍ້, ປະເພດລາຍຈ່າຍ, ຜູ້ບັນທຶກ...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 120px;">ວັນທີລາຍຈ່າຍ</th>
                            <th>ຫົວຂໍ້ລາຍຈ່າຍ</th>
                            <th>ປະເພດລາຍຈ່າຍ</th>
                            <th>ໝາຍເຫດ</th>
                            <th class="text-center">ຜູ້ບັນທຶກ</th>
                            <th class="text-end" style="width: 180px;">ຈຳນວນເງິນ</th>
                        </tr>
                    </thead>
                    <tbody id="expTableBody">
                        <?php if (empty($expensesList)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນລາຍຈ່າຍ</td></tr>
                        <?php else: ?>
                            <?php foreach ($expensesList as $e): ?>
                                <tr class="exp-row">
                                    <td class="text-center fw-bold"><?= date('d/m/Y', strtotime($e['expense_date'])) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($e['title']) ?></td>
                                    <td><span class="badge bg-light text-danger border"><?= htmlspecialchars($e['category']) ?></span></td>
                                    <td class="text-muted small"><?= htmlspecialchars($e['notes'] ?: '-') ?></td>
                                    <?php 
                                        $staffName = trim(($e['staff_fname'] ?? '') . ' ' . ($e['staff_lname'] ?? ''));
                                        if ($staffName === '') $staffName = 'Admin';
                                    ?>
                                    <td class="text-center"><?= htmlspecialchars($staffName) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= formatCurrency($e['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">ລວມລາຍຈ່າຍທົ່ວໄປທັງໝົດ:</td>
                            <td class="text-end text-danger" style="font-size:1.1rem;"><?= formatCurrency($generalExpenseSum) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

        </div>
        
        <!-- Pagination controls dynamically shown except in Overview tab -->
        <?php if ($activeTab !== 'overview'): ?>
        <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 no-print" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="text-muted small" id="pagerInfo">ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ</div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="pagerControls"></ul>
            </nav>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Helper Pagination Script for Tabs
function initTablePagination(searchInputId, pagerInfoId, pagerControlsId, rowsClass, itemsPerPage) {
    let currentPage = 1;
    let limit = itemsPerPage || 10;
    
    function updateTable() {
        let query = $(searchInputId).val().toLowerCase().trim();
        let visibleRows = [];
        
        $(rowsClass).each(function() {
            let text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                visibleRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        let totalItems = visibleRows.length;
        let totalPages = Math.ceil(totalItems / limit) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        let startIndex = (currentPage - 1) * limit;
        let endIndex = Math.min(startIndex + limit, totalItems);
        
        $(rowsClass).hide();
        for (let i = startIndex; i < endIndex; i++) {
            $(visibleRows[i]).show();
        }
        
        if (totalItems === 0) {
            $(pagerInfoId).text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ');
            $(pagerControlsId).html('');
            return;
        }
        
        $(pagerInfoId).text(`ສະແດງ ${startIndex + 1} ຫາ ${endIndex} ຈາກທັງໝົດ ${totalItems} ລາຍການ`);
        
        // Render pagination controls
        let controlsHtml = '';
        if (currentPage === 1) {
            controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-left"></i></a></li>`;
        } else {
            controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a></li>`;
        }
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (let p = startPage; p <= endPage; p++) {
            if (p <= 0) continue;
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
        
        $(pagerControlsId).html(controlsHtml);
        
        $(pagerControlsId + ' a[data-page]').off('click').on('click', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            updateTable();
        });
    }
    
    $(searchInputId).on('input', function() {
        currentPage = 1;
        updateTable();
    });
    
    updateTable();
}

$(document).ready(function() {
    // Initialize corresponding pagination depending on which tab is active
    let activeTab = '<?= $activeTab ?>';
    if (activeTab === 'subscription') {
        initTablePagination('#subSearchInput', '#pagerInfo', '#pagerControls', '.sub-row', 10);
    } else if (activeTab === 'daily') {
        initTablePagination('#dailySearchInput', '#pagerInfo', '#pagerControls', '.daily-row', 10);
    } else if (activeTab === 'pos') {
        initTablePagination('#posSearchInput', '#pagerInfo', '#pagerControls', '.pos-row', 10);
    } else if (activeTab === 'stock_in') {
        initTablePagination('#stockSearchInput', '#pagerInfo', '#pagerControls', '.stock-row', 10);
    } else if (activeTab === 'expense') {
        initTablePagination('#expSearchInput', '#pagerInfo', '#pagerControls', '.exp-row', 10);
    }
});
</script>

<?php if ($activeTab === 'overview'): ?>
<script>
// ===== Chart Rendering (Chart.js) =====
(function() {
    // Set global default font family for Chart.js
    Chart.defaults.font.family = "'Noto Sans Lao Looped', 'Noto Sans Lao', sans-serif";

    let labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    let revData = <?= json_encode($chartRevData) ?>;
    let expData = <?= json_encode($chartExpData) ?>;
    let ctx = document.getElementById('financialChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'ລາຍຮັບລວມ (Revenues)',
                    data: revData,
                    backgroundColor: 'rgba(56, 239, 125, 0.85)',
                    borderColor: 'rgb(56, 239, 125)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: 'ລາຍຈ່າຍລວມ (Expenses)',
                    data: expData,
                    backgroundColor: 'rgba(255, 88, 88, 0.85)',
                    borderColor: 'rgb(255, 88, 88)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: "'Noto Sans Lao Looped', 'Noto Sans Lao', sans-serif", size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: function(c) {
                            return c.dataset.label + ': ' + c.raw.toLocaleString('en-US') + ' ກີບ';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Noto Sans Lao Looped', 'Noto Sans Lao', sans-serif", size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        callback: function(v) {
                            return v >= 1000000 
                                ? (v/1000000).toFixed(1) + 'M' 
                                : v >= 1000 ? (v/1000).toFixed(0) + 'K' : v;
                        },
                        font: { family: "'Noto Sans Lao Looped', 'Noto Sans Lao', sans-serif", size: 11 }
                    }
                }
            }
        }
    });

    // ===== Doughnut Chart (Revenue Sources) =====
    let subSum = <?= (float)$subRevenueSum ?>;
    let dailySum = <?= (float)$dailyRevenueSum ?>;
    let salesSum = <?= (float)$salesRevenueSum ?>;
    
    let doughnutCtx = document.getElementById('revenueSourcesChart');
    if (doughnutCtx) {
        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['ຄ່າສະໝັກສະມາຊິກ', 'ຄ່າລາຍວັນ', 'ຍອດຂາຍ POS'],
                datasets: [{
                    data: [subSum, dailySum, salesSum],
                    backgroundColor: [
                        'rgba(255, 159, 64, 0.85)',
                        'rgba(54, 162, 235, 0.85)',
                        'rgba(75, 192, 192, 0.85)'
                    ],
                    borderColor: [
                        'rgb(255, 159, 64)',
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)'
                    ],
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Noto Sans Lao Looped', 'Noto Sans Lao', sans-serif",
                                size: 11
                            },
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return c.label + ': ' + c.raw.toLocaleString('en-US') + ' ກີບ';
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
})();
</script>
<?php endif; ?>
</body>
</html>
