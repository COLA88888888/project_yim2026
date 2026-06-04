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
if (!hasPermission('subscriptions', 'view')) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້</div></div>";
    exit();
}

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$activeTab = $_GET['tab'] ?? 'subscription';
if (!in_array($activeTab, ['subscription', 'daily'])) {
    $activeTab = 'subscription';
}

// Build Where Clause for Subscriptions (memberships)
$whereClauseSub = "";
if ($startDate !== '' && $endDate !== '') {
    $whereClauseSub = "WHERE DATE(ms.created_at) >= '$startDate' AND DATE(ms.created_at) <= '$endDate'";
} elseif ($startDate !== '') {
    $whereClauseSub = "WHERE DATE(ms.created_at) >= '$startDate'";
} elseif ($endDate !== '') {
    $whereClauseSub = "WHERE DATE(ms.created_at) <= '$endDate'";
}

// Build Where Clause for Daily Check-ins
$whereClauseDaily = "";
if ($startDate !== '' && $endDate !== '') {
    $whereClauseDaily = "WHERE DATE(checkin_date) >= '$startDate' AND DATE(checkin_date) <= '$endDate'";
} elseif ($startDate !== '') {
    $whereClauseDaily = "WHERE DATE(checkin_date) >= '$startDate'";
} elseif ($endDate !== '') {
    $whereClauseDaily = "WHERE DATE(checkin_date) <= '$endDate'";
}

$transactions = [];
$total_revenue = 0;

// 1. Fetch Subscription Revenues
$sqlSub = "SELECT ms.*, mb.fname, mb.lname, mb.member_code, p.package_name
           FROM memberships ms
           LEFT JOIN members mb ON ms.member_id = mb.member_id
           LEFT JOIN packages p ON ms.package_id = p.package_id
           $whereClauseSub
           ORDER BY ms.membership_id DESC";
$resultSub = mysqli_query($conn, $sqlSub);
if ($resultSub) {
    while ($row = mysqli_fetch_assoc($resultSub)) {
        $row['type'] = 'subscription';
        $row['datetime'] = $row['created_at'];
        $transactions[] = $row;
        $total_revenue += (float)$row['price_paid'];
    }
}

// 2. Fetch Daily Check-in Revenues
$sqlDaily = "SELECT d.*, u.fname AS staff_fname, u.lname AS staff_lname
             FROM daily_checkins d
             LEFT JOIN users u ON d.user_id = u.user_id
             $whereClauseDaily
             ORDER BY d.id DESC";
$resultDaily = mysqli_query($conn, $sqlDaily);
if ($resultDaily) {
    while ($row = mysqli_fetch_assoc($resultDaily)) {
        $row['type'] = 'daily';
        $row['datetime'] = $row['created_at'];
        $transactions[] = $row;
        $total_revenue += (float)$row['price_paid'];
    }
}

// Sort combined transactions chronologically descending
usort($transactions, function($a, $b) {
    return strcmp($b['datetime'] ?? '', $a['datetime'] ?? '');
});

// Stats cards (Total sums including both subscriptions and daily checkins)
// 1. Today
$sub_today = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price_paid) FROM memberships WHERE DATE(created_at) = CURDATE()"))[0] ?? 0;
$daily_today = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price_paid) FROM daily_checkins WHERE checkin_date = CURDATE()"))[0] ?? 0;
$revenue_today = (float)$sub_today + (float)$daily_today;

// 2. Month
$sub_month = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price_paid) FROM memberships WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))[0] ?? 0;
$daily_month = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price_paid) FROM daily_checkins WHERE MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE())"))[0] ?? 0;
$revenue_month = (float)$sub_month + (float)$daily_month;

// 3. All time
$sub_all = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price_paid) FROM memberships"))[0] ?? 0;
$daily_all = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(price_paid) FROM daily_checkins"))[0] ?? 0;
$revenue_all = (float)$sub_all + (float)$daily_all;

// Initial values based on active tab
$initial_today = ($activeTab === 'daily') ? (float)$daily_today : (float)$sub_today;
$initial_month = ($activeTab === 'daily') ? (float)$daily_month : (float)$sub_month;
$initial_all = ($activeTab === 'daily') ? (float)$daily_all : (float)$sub_all;

// Payment method breakdown (calculated from combined list within filtered range)
$cash_total     = 0;
$transfer_total = 0;
foreach ($transactions as $t) {
    $pm = trim($t['payment_method'] ?? '');
    if (mb_strpos($pm, 'ສົດ') !== false || mb_strtolower($pm) === 'cash' || mb_strpos(mb_strtolower($pm), 'cash') !== false) {
        $cash_total += (float)$t['price_paid'];
    } else {
        // Since we only use Cash and Transfer, classify other methods (e.g. Transfer, QR) as Transfer
        $transfer_total += (float)$t['price_paid'];
    }
}

// ===== 6-Month Revenue Trend =====
$loMonths = ['ມັງກອນ','ກຸມພາ','ມີນາ','ເມສາ','ພຶດສະພາ','ມິຖຸນາ','ກໍລະກົດ','ສິງຫາ','ກັນຍາ','ຕຸລາ','ພະຈິກ','ທັນວາ'];
$chartLabels = []; $chartSubData = []; $chartDailyData = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $y  = (int)date('Y', $ts);
    $m  = (int)date('n', $ts);
    $chartLabels[]    = $loMonths[$m - 1] . ' ' . ($y + 543);
    $subQ   = mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM memberships WHERE YEAR(created_at)=$y AND MONTH(created_at)=$m");
    $dailyQ = mysqli_query($conn, "SELECT COALESCE(SUM(price_paid),0) FROM daily_checkins WHERE YEAR(checkin_date)=$y AND MONTH(checkin_date)=$m");
    $chartSubData[]   = (float)(mysqli_fetch_row($subQ)[0]   ?? 0);
    $chartDailyData[] = (float)(mysqli_fetch_row($dailyQ)[0] ?? 0);
}

// ===== Staff Performance (daily_checkins) =====
$staffRows = [];
$staffResult = mysqli_query($conn, "
    SELECT COALESCE(NULLIF(TRIM(CONCAT(u.fname,' ',u.lname)),''),'ລະບົບ') AS staff_name,
           COUNT(d.id) AS txn_count,
           COALESCE(SUM(d.price_paid),0) AS total_revenue
    FROM daily_checkins d LEFT JOIN users u ON d.user_id=u.user_id
    GROUP BY d.user_id ORDER BY total_revenue DESC LIMIT 10
");
if ($staffResult) while ($r = mysqli_fetch_assoc($staffResult)) $staffRows[] = $r;
$staffMax = !empty($staffRows) ? max(1, (float)max(array_column($staffRows, 'total_revenue'))) : 1;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານລາຍຮັບ</title>
    <!-- Google Fonts - Noto Sans Lao Looped -->
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
            font-family: 'Noto Sans Lao Looped', sans-serif;
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
            font-size: 1.60rem;
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
        
        .payment-card-rev {
            border-radius: 16px;
            border: none;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            padding: 10px 14px !important;
        }
        .payment-card-rev:hover {
            transform: translateY(-4px);
        }
        .payment-card-rev .card-title {
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0.9;
        }
        .payment-card-rev .card-value {
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 2px 0;
        }
        .payment-card-rev .card-desc {
            font-size: 0.72rem;
            opacity: 0.85;
            font-weight: 500;
        }
        .payment-card-icon-right {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.1);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .payment-card-rev:hover .payment-card-icon-right {
            color: rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.18);
            transform: scale(1.08) rotate(5deg);
        }
        .payment-card-rev * {
            position: relative;
            z-index: 2;
        }
        
        /* Custom tabs styling */
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            background: transparent;
            padding: 12px 20px;
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
        
        /* Tight grid layout */
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
            /* Show all paginated rows when printing */
            .page-hidden {
                display: table-row !important;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-chart-bar text-primary me-2"></i> ລາຍງານລາຍຮັບ ແລະ ບັນທຶກການເງິນ
            </h4>
            <p class="text-muted small mb-0">ສະຫຼຸບລາຍຮັບຂອງຍິມຈາກການສະໝັກແພັກເກດຂອງສະມາຊິກ</p>
        </div>
        <div>
            <button class="btn btn-secondary rounded-pill px-4 shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> ພິມລາຍງານ
            </button>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row-tight no-print">
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%); box-shadow: 0 8px 20px rgba(11, 163, 150, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຮັບມື້ນີ້</small>
                        <h3 id="stat-today"><?= formatCurrency($initial_today) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-day mr-1"></i> ລາຍຮັບປະຈຳວັນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #3E64FF 0%, #5B86E5 100%); box-shadow: 0 8px 20px rgba(62, 100, 255, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຮັບເດືອນນີ້</small>
                        <h3 id="stat-month"><?= formatCurrency($initial_month) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-alt mr-1"></i> ລາຍຮັບປະຈຳເດືອນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card-rev bg-gradient" style="background: linear-gradient(135deg, #9C33FD 0%, #FF5252 100%); box-shadow: 0 8px 20px rgba(156, 51, 253, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຮັບທັງໝົດ</small>
                        <h3 id="stat-all"><?= formatCurrency($initial_all) ?></h3>
                        <small class="text-white-50"><i class="fas fa-chart-line mr-1"></i> ຍອດລວມສະສົມ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Breakdown Cards -->
    <div class="row-tight no-print">
        <div class="col-md-4 col-sm-6">
            <div class="card payment-card-rev bg-gradient" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.12);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <span class="card-title">ເງິນສົດ</span>
                        <div class="card-value" id="cashTotal"><?= number_format($cash_total, 0, '.', ',') ?> ກີບ</div>
                        <?php $cash_pct = $total_revenue > 0 ? round($cash_total / $total_revenue * 100, 1) : 0; ?>
                        <div class="card-desc"><i class="fas fa-percentage me-1"></i><?= $cash_pct ?>% ຂອງທັງໝົດ</div>
                    </div>
                    <div class="payment-card-icon-right">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card payment-card-rev bg-gradient" style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.12);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <span class="card-title">ເງິນໂອນ</span>
                        <div class="card-value" id="transferTotal"><?= number_format($transfer_total, 0, '.', ',') ?> ກີບ</div>
                        <?php $transfer_pct = $total_revenue > 0 ? round($transfer_total / $total_revenue * 100, 1) : 0; ?>
                        <div class="card-desc"><i class="fas fa-percentage me-1"></i><?= $transfer_pct ?>% ຂອງທັງໝົດ</div>
                    </div>
                    <div class="payment-card-icon-right">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="card payment-card-rev bg-gradient" style="background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%); box-shadow: 0 6px 15px rgba(139, 92, 246, 0.12);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <span class="card-title">ລວມເງິນທັງໝົດ</span>
                        <div class="card-value" id="grandTotal"><?= number_format($total_revenue, 0, '.', ',') ?> ກີບ</div>
                        <div class="card-desc"><i class="fas fa-list-ol me-1"></i><?= count($transactions) ?> ລາຍການ</div>
                    </div>
                    <div class="payment-card-icon-right">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Analytics: 6-Month Trend + Staff Performance ===== -->
    <div class="row g-3 mb-4 no-print">
        <!-- 6-Month Revenue Trend Chart -->
        <div class="col-lg-7">
            <div class="card card-custom h-100" style="border-radius:16px;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0">
                            <i class="fas fa-chart-bar text-primary me-2"></i>ແນວໂນ້ມລາຍຮັບ 6 ເດືອນ
                        </h6>
                        <div class="d-flex gap-3 align-items-center" style="font-size:0.75rem;color:#6c757d;">
                            <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#3E64FF;margin-right:4px;"></span>ສະມາຊິກ</span>
                            <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#10b981;margin-right:4px;"></span>ລາຍວັນ</span>
                        </div>
                    </div>
                    <canvas id="revenueChart" style="max-height:240px;"></canvas>
                </div>
            </div>
        </div>
        <!-- Staff Performance -->
        <div class="col-lg-5">
            <div class="card card-custom h-100" style="border-radius:16px;">
                <div class="card-body p-3 p-md-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-medal text-warning me-2"></i>ຜົນງານພະນັກງານ (ລູກຄ້າລາຍວັນ)
                    </h6>
                    <?php if (empty($staffRows)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>ຍັງບໍ່ມີຂໍ້ມູນ
                    </div>
                    <?php else: ?>
                    <?php
                    $barColors = ['#3E64FF','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#6b7280'];
                    foreach ($staffRows as $idx => $s):
                        $pct = $staffMax > 0 ? round((float)$s['total_revenue'] / $staffMax * 100) : 0;
                        $clr = $barColors[$idx % count($barColors)];
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold" style="font-size:0.88rem;color:#1e293b;">
                                <i class="fas fa-circle me-1" style="font-size:0.45rem;color:<?= $clr ?>;vertical-align:middle;"></i>
                                <?= htmlspecialchars($s['staff_name']) ?>
                            </span>
                            <span class="fw-bold" style="font-size:0.83rem;color:<?= $clr ?>;">
                                <?= number_format((float)$s['total_revenue'],0,'.',',') ?> ກີບ
                            </span>
                        </div>
                        <div class="progress" style="height:7px;border-radius:8px;background:#f1f5f9;">
                            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $clr ?>;border-radius:8px;"></div>
                        </div>
                        <div class="text-muted mt-1" style="font-size:0.7rem;"><?= (int)$s['txn_count'] ?> ລາຍການ</div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="form-label fw-bold small">ເລີ່ມແຕ່ວັນທີ</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="form-label fw-bold small">ຫາວັນທີ</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search mr-1"></i> ຄົ້ນຫາ</button>
                    <!-- <a href="revenue_report.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i></a> -->
                </div>
            </form>
        </div>
    </div>

    <!-- Print Title Sheet -->
    <div class="d-none d-print-block text-center mb-4">
        <h2>ລາຍງານລາຍຮັບລະບົບຍິມ & ຟິດເນັດ</h2>
        <?php if ($startDate !== '' || $endDate !== ''): ?>
            <p>ໄລຍະເວລາ: <?= $startDate ? date('d/m/Y', strtotime($startDate)) : 'ເລີ່ມຕົ້ນ' ?> ຫາ <?= $endDate ? date('d/m/Y', strtotime($endDate)) : 'ປັດຈຸບັນ' ?></p>
        <?php else: ?>
            <p>ລາຍງານລາຍຮັບທັງໝົດໃນລະບົບ</p>
        <?php endif; ?>
        <p class="small text-muted">ວັນທີດຶງລາຍງານ: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <!-- Table Card -->
    <div class="card card-custom">
        <div class="bg-light px-3 pt-2 border-bottom no-print" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <ul class="nav nav-tabs border-bottom-0" id="reportTabs" role="tablist">

                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'subscription' ? 'active text-primary' : 'text-secondary' ?> px-3 py-2" id="subscription-tab" data-type="subscription" style="border: none; background: transparent;">
                        <i class="fas fa-id-card me-1"></i> ລາຍຮັບສະມາຊິກ
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'daily' ? 'active text-primary' : 'text-secondary' ?> px-3 py-2" id="daily-tab" data-type="daily" style="border: none; background: transparent;">
                        <i class="fas fa-user-clock me-1"></i> ລາຍຮັບລູກຄ້າລາຍວັນ
                    </button>
                </li>
            </ul></div>
        <div class="card-body p-0">
            <!-- Search & Control Header -->
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 no-print">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="text-muted small">
                        ລາຍການທັງໝົດ: <span class="fw-bold text-primary" id="revCount"><?= count($transactions) ?></span> ລາຍການ
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
                    <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາລາຍຮັບ...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr id="header-row">
                            <!-- Default Headers (Subscription & Combined View) -->
                            <th class="text-center cell-default">ລະຫັດສະມາຊິກ</th>
                            <th class="cell-default">ຊື່ສະມາຊິກ</th>
                            <th class="cell-default">ແພັກເກດ</th>
                            <th class="text-center cell-default">ຊຳລະໂດຍ</th>
                            <th class="text-center cell-default">ວັນທີສະໝັກ</th>
                            <th class="text-end cell-default" style="width: 180px;">ຍອດເງິນຊຳລະ</th>

                            <!-- Daily Only Headers -->
                            <th class="text-center cell-daily-only d-none">ຊຳລະໂດຍ</th>
                            <th class="text-center cell-daily-only d-none">ວັນທີເຂົ້າໃຊ້</th>
                            <th class="text-end cell-daily-only d-none" style="width: 180px;">ຍອດເງິນຊຳລະ</th>
                            <th class="text-center cell-daily-only d-none">ພະນັກງານບັນທຶກ</th>
                        </tr>
                    </thead>
                    <tbody id="revenueTableBody">
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $t): ?>
                                <?php if ($t['type'] === 'subscription'): ?>
                                    <tr class="revenue-row"
                                        data-price="<?= (float)$t['price_paid'] ?>"
                                        data-payment="<?= htmlspecialchars(mb_strtolower($t['payment_method'] ?? ''), ENT_QUOTES) ?>"
                                        data-type="subscription">
                                        <td class="text-center cell-default"><code><?= htmlspecialchars($t['member_code'] ?? '-') ?></code></td>
                                        <td class="fw-bold text-dark cell-default"><?= htmlspecialchars(($t['fname'] ?? 'ລົບແລ້ວ') . ' ' . ($t['lname'] ?? '')) ?></td>
                                        <td class="cell-default"><span class="badge bg-light text-primary border"><?= htmlspecialchars($t['package_name'] ?: 'ບໍ່ລະບຸ') ?></span></td>
                                        <td class="text-center cell-default"><?= htmlspecialchars($t['payment_method']) ?></td>
                                        <td class="text-center text-muted cell-default"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td class="text-end fw-bold text-success cell-default"><?= formatCurrency($t['price_paid']) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr class="revenue-row"
                                        data-price="<?= (float)$t['price_paid'] ?>"
                                        data-payment="<?= htmlspecialchars(mb_strtolower($t['payment_method'] ?? ''), ENT_QUOTES) ?>"
                                        data-type="daily">
                                        <!-- Default Columns (Used in 'All' tab) -->
                                        <td class="text-center cell-default"><span class="badge bg-secondary text-white">ລູກຄ້າລາຍວັນ</span></td>
                                        <td class="fw-bold text-dark cell-default">ລູກຄ້າລາຍວັນ (ເພດ <?= htmlspecialchars($t['gender'] ?? '-') ?>)</td>
                                        <td class="cell-default"><span class="badge bg-light text-info border">ເຊັກອິນລາຍວັນ</span></td>
                                        <td class="text-center cell-default"><?= htmlspecialchars($t['payment_method']) ?></td>
                                        <td class="text-center text-muted cell-default"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td class="text-end fw-bold text-success cell-default"><?= formatCurrency($t['price_paid']) ?></td>

                                        <!-- Daily Customer Tab Columns -->
                                        <td class="text-center cell-daily-only d-none">
                                            <span class="badge px-3 py-2 bg-light text-dark border" style="border-radius:12px;font-size:0.85rem;font-weight:700;">
                                                <?= htmlspecialchars($t['payment_method']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center text-muted cell-daily-only d-none"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td class="text-end fw-bold text-success cell-daily-only d-none"><?= formatCurrency($t['price_paid']) ?></td>
                                        <?php 
                                            $staffName = trim(($t['staff_fname'] ?? '') . ' ' . ($t['staff_lname'] ?? ''));
                                            if ($staffName === '') $staffName = 'Admin';
                                        ?>
                                        <td class="text-center fw-bold text-dark cell-daily-only d-none"><?= htmlspecialchars($staffName) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-3 d-block text-secondary"></i>
                                    ບໍ່ພົບຂໍ້ມູນລາຍຮັບ
                                </td>
                            </tr>
                        <?php endif; ?>

                        <!-- Footer row calculating Sum -->
                        <tr id="summaryFooterRow" style="background-color:#f8fafc;border-top:2px solid #dee2e6;">
                            <!-- Default Footer View -->
                            <!-- <td colspan="3" class="fw-bold text-end text-muted cell-default" style="font-size:0.9rem;">
                                <span class="me-3"><i class="fas fa-money-bill-wave text-success me-1"></i>ເງິນສົດ: <span id="footerCash" class="text-success fw-bold"><?= number_format($cash_total,0,'.',',') ?> ກີບ</span></span>
                                <span class="me-3"><i class="fas fa-mobile-alt text-primary me-1"></i>ເງິນໂອນ: <span id="footerTransfer" class="text-primary fw-bold"><?= number_format($transfer_total,0,'.',',') ?> ກີບ</span></span>
                            </td> -->
                            <td colspan="2" class="text-end fw-bold text-dark cell-default" style="font-size:1rem;">ລວມທັງໝົດ:</td>

                            <!-- Daily Tab Footer View -->
                            <!-- <td colspan="2" class="fw-bold text-end text-muted cell-daily-only d-none" style="font-size:0.9rem;">
                                <span class="me-3"><i class="fas fa-money-bill-wave text-success me-1"></i>ເງິນສົດ: <span id="footerCashDaily" class="text-success fw-bold"><?= number_format($cash_total,0,'.',',') ?> ກີບ</span></span>
                                <span class="me-3"><i class="fas fa-mobile-alt text-primary me-1"></i>ເງິນໂອນ: <span id="footerTransferDaily" class="text-primary fw-bold"><?= number_format($transfer_total,0,'.',',') ?> ກີບ</span></span>
                            </td> -->
                            <td colspan="1" class="text-end fw-bold text-dark cell-daily-only d-none" style="font-size:1rem;">ລວມທັງໝົດ:</td>

                            <td id="totalRevenueSum" class="text-end fw-bold text-success" style="font-size:1.15rem;"><?= formatCurrency($total_revenue) ?></td>
                        </tr>
                    </tbody></table>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 no-print" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="text-muted small" id="paginationInfo">
                ສະແດງ 1-10 ຈາກທັງໝົດ 10 ລາຍການ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<script>
const statsData = {
    subscription: {
        today: <?= (float)$sub_today ?>,
        month: <?= (float)$sub_month ?>,
        all:  <?= (float)$sub_all ?>
    },
    daily: {
        today: <?= (float)$daily_today ?>,
        month: <?= (float)$daily_month ?>,
        all:  <?= (float)$daily_all ?>
    }
};

$(document).ready(function() {
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

    var activeType = '<?= $activeTab ?>';
    
    $('#reportTabs button').on('click', function() {
        $('#reportTabs button').removeClass('active text-primary').addClass('text-secondary');
        $(this).addClass('active text-primary').removeClass('text-secondary');
        activeType = $(this).data('type');
        updateFilteredRows();
        showPage(1);
    });

    function updateFilteredRows() {
        var query = $('#searchInput').val().toLowerCase().trim();
        filteredRows = [];
        var totalSum = 0, cashSum = 0, transferSum = 0;

        // Toggle table header and row cell columns based on selected tab
        if (activeType === 'daily') {
            $('.cell-default').addClass('d-none');
            $('.cell-daily-only').removeClass('d-none');
        } else {
            $('.cell-daily-only').addClass('d-none');
            $('.cell-default').removeClass('d-none');
        }

        $('.revenue-row').removeClass('page-hidden');

        $('.revenue-row').each(function() {
            var text = $(this).text().toLowerCase();
            var rowType = $(this).data('type') || 'subscription';
            var matchesType = (activeType === 'all' || rowType === activeType);
            
            if (text.indexOf(query) > -1 && matchesType) {
                filteredRows.push(this);
                var price   = parseFloat($(this).data('price')) || 0;
                var payment = ($(this).data('payment') || '').toLowerCase();
                totalSum += price;
                if (payment.indexOf('ສົດ') > -1 || payment === 'cash' || payment.indexOf('cash') > -1) {
                    cashSum += price;
                } else {
                    transferSum += price;
                }
            } else {
                $(this).hide();
            }
        });

        $('#revCount').text(filteredRows.length);

        // Update dynamic sums
        var fmt = function(n) { return n.toLocaleString('en-US') + ' ກີບ'; };
        $('#totalRevenueSum').text(fmt(totalSum));
        $('#cashTotal').text(fmt(cashSum));
        $('#transferTotal').text(fmt(transferSum));
        
        $('#grandTotal').text(fmt(totalSum));
        $('#footerCash').text(fmt(cashSum));
        $('#footerTransfer').text(fmt(transferSum));
        
        // Update top summary cards dynamically depending on active tab
        $('#stat-today').text(fmt(statsData[activeType].today));
        $('#stat-month').text(fmt(statsData[activeType].month));
        $('#stat-all').text(fmt(statsData[activeType].all));
        

        if (filteredRows.length === 0 && $('.revenue-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#revenueTableBody').append(
                    `<tr id="emptySearchResult"><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນລາຍຮັບ</td></tr>`
                );
            }
            $('#summaryFooterRow').hide();
        } else {
            $('#emptySearchResult').remove();
            $('#summaryFooterRow').show();
        }
    }

    function showPage(page) {
        currentPage = page;
        var totalItems = filteredRows.length;
        
        if (totalItems === 0) {
            $('.revenue-row').hide().addClass('page-hidden');
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.revenue-row').hide().addClass('page-hidden');
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show().removeClass('page-hidden');
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ລາຍການ');
        
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
        
        for (var p = startPage; p <= endPage; p++) {
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
</script>

<script>
// ===== Revenue Trend Chart (Chart.js) =====
(function() {
    var labels    = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    var subData   = <?= json_encode($chartSubData) ?>;
    var dailyData = <?= json_encode($chartDailyData) ?>;
    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'ລາຍຮັບສະມາຊິກ',
                    data: subData,
                    backgroundColor: 'rgba(62,100,255,0.82)',
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: 'ລາຍຮັບລາຍວັນ',
                    data: dailyData,
                    backgroundColor: 'rgba(16,185,129,0.82)',
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
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
                    ticks: { font: { family: "'Noto Sans Lao Looped', sans-serif", size: 11 } }
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
                        font: { size: 11 }
                    }
                }
            }
        }
    });
})();
</script>
</body>
</html>
