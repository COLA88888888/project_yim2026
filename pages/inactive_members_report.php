<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('report_inactive_members', 'view')) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}

$days_threshold = (int)($_GET['days'] ?? 14);
if ($days_threshold < 1) $days_threshold = 14;

// ດຶງສະມາຊິກທີ່ Active ແຕ່ບໍ່ໄດ້ເຊັກອິນ >= $days_threshold ວັນ ຫຼື ຍັງບໍ່ເຄີຍເຊັກອິນເລີຍ
$sql = "
    SELECT
        mb.member_id,
        mb.member_code,
        mb.fname,
        mb.lname,
        mb.gender,
        mb.tel,
        mb.profile_img,
        ms_active.end_date AS active_until,
        p.package_name,
        MAX(c.checkin_time) AS last_checkin,
        CASE
            WHEN MAX(c.checkin_time) IS NULL THEN NULL
            ELSE DATEDIFF(NOW(), MAX(c.checkin_time))
        END AS days_since_checkin
    FROM members mb
    INNER JOIN memberships ms_active ON ms_active.member_id = mb.member_id
        AND ms_active.status = 'Active'
        AND ms_active.end_date >= CURDATE()
    INNER JOIN packages p ON p.package_id = ms_active.package_id
    LEFT JOIN checkins c ON c.member_id = mb.member_id
    WHERE mb.status = 'Active'
    GROUP BY mb.member_id, ms_active.end_date, p.package_name
    HAVING (days_since_checkin IS NULL OR days_since_checkin >= ?)
    ORDER BY days_since_checkin DESC, mb.fname ASC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $days_threshold);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$members = [];
while ($row = mysqli_fetch_assoc($result)) $members[] = $row;
mysqli_stmt_close($stmt);

$total = count($members);
$never_checkin = array_filter($members, fn($m) => $m['last_checkin'] === null);
$never_count = count($never_checkin);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານສະມາຊິກຂາດການຕິດຕໍ່</title>
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    <style>
        body { font-family: 'Noto Sans Lao Looped', sans-serif; background-color: #f4f6f9; }

        /* ===== Hero Stats Strip ===== */
        .hero-strip {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #3a6fb3 100%);
            border-radius: 20px;
            padding: 28px 32px;
            color: white;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .hero-strip::before {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .hero-strip::after {
            content: '';
            position: absolute;
            right: 80px; bottom: -80px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .hero-kpi {
            text-align: center;
            padding: 0 20px;
            border-right: 1px solid rgba(255,255,255,0.15);
        }
        .hero-kpi:last-child { border-right: none; }
        .hero-kpi .kpi-num {
            font-size: 2.4rem;
            font-weight: 900;
            line-height: 1;
        }
        .hero-kpi .kpi-label {
            font-size: 0.78rem;
            opacity: 0.8;
            font-weight: 600;
            margin-top: 4px;
        }
        .hero-kpi .kpi-sub {
            font-size: 0.68rem;
            opacity: 0.6;
            margin-top: 2px;
        }

        /* ===== Filter Strip ===== */
        .filter-strip {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 2px solid #dee2e6;
            background: white;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #1e3c72;
            border-color: #1e3c72;
            color: white;
        }

        /* ===== Member Row Card ===== */
        .member-row {
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #f0f4f8;
            transition: all 0.25s ease;
            animation: rowFadeIn 0.4s ease both;
        }
        .member-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border-color: #d0dce8;
        }
        @keyframes rowFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .member-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e8f0fe;
            flex-shrink: 0;
        }
        .member-info { flex: 1; min-width: 0; }
        .member-name { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 2px; }
        .member-code { font-size: 0.78rem; color: #94a3b8; font-weight: 600; }
        .member-pkg {
            font-size: 0.8rem;
            color: #3b82f6;
            font-weight: 700;
            background: #eff6ff;
            border-radius: 8px;
            padding: 2px 10px;
            display: inline-block;
            margin-top: 4px;
        }
        .absent-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 800;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .absent-never   { background: #fef2f2; color: #ef4444; border: 1.5px solid #fecaca; }
        .absent-critical { background: #fff7ed; color: #ea580c; border: 1.5px solid #fed7aa; }
        .absent-warn    { background: #fefce8; color: #ca8a04; border: 1.5px solid #fde68a; }
        .contact-btn {
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
        }
        .contact-btn.call { background: #d1fae5; color: #065f46; }
        .contact-btn.call:hover { background: #a7f3d0; color: #064e3b; }
        .contact-btn.whatsapp { background: #dcfce7; color: #15803d; }
        .contact-btn.whatsapp:hover { background: #bbf7d0; }
        .expire-info { font-size: 0.76rem; color: #94a3b8; white-space: nowrap; flex-shrink: 0; }
        .expire-info strong { color: #475569; display: block; }

        /* ===== Empty State ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
        }
        .empty-state i { font-size: 3.5rem; color: #10b981; margin-bottom: 16px; }
        .empty-state h5 { font-weight: 800; color: #1e293b; }
        .empty-state p { color: #94a3b8; }

        @media (max-width: 576px) {
            .member-row { flex-wrap: wrap; }
            .hero-kpi { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.1); padding: 10px 0; }
            .hero-strip .d-flex { flex-wrap: wrap; }
        }
        @media print {
            .no-print { display: none !important; }
            .hero-strip { background: #1e3c72 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">

    <!-- ===== Page Header ===== -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-user-slash text-warning me-2"></i> ສະມາຊິກຂາດການຕິດຕໍ່
            </h4>
            <p class="text-muted small mb-0">ສະມາຊິກທີ່ຍັງມີແພັກເກດ Active ແຕ່ບໍ່ໄດ້ເຊັກອິນເຂົ້ານຳໃຊ້ > <strong><?= $days_threshold ?> ວັນ</strong></p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary rounded-pill px-4" onclick="window.print()">
                <i class="fas fa-print me-1"></i> ພິມລາຍງານ
            </button>
        </div>
    </div>

    <!-- ===== Hero Strip ===== -->
    <div class="hero-strip mb-4 no-print">
        <div class="d-flex align-items-center flex-wrap gap-3 justify-content-center justify-content-md-start">
            <div class="hero-kpi">
                <div class="kpi-num"><?= $total ?></div>
                <div class="kpi-label">ສະມາຊິກຂາດການຕິດຕໍ່</div>
                <div class="kpi-sub">(ຫຼາຍກວ່າ <?= $days_threshold ?> ວັນ)</div>
            </div>
            <div class="hero-kpi">
                <div class="kpi-num"><?= $never_count ?></div>
                <div class="kpi-label">ຍັງບໍ່ເຄີຍເຊັກອິນ</div>
                <div class="kpi-sub">ນັບຈາກວັນລົງທະບຽນ</div>
            </div>
            <div class="hero-kpi">
                <div class="kpi-num"><?= $total - $never_count ?></div>
                <div class="kpi-label">ຫຍຸດໃຊ້ງານ</div>
                <div class="kpi-sub">(ເຄີຍມາ ແຕ່ຫາຍໄປ)</div>
            </div>
            <div class="ms-auto text-end d-none d-md-block" style="position:relative;z-index:2;">
                <div style="font-size:4rem;opacity:0.12;"><i class="fas fa-person-running"></i></div>
            </div>
        </div>
    </div>

    <!-- ===== Quick Filter Buttons ===== -->
    <div class="filter-strip no-print">
        <span class="text-muted small fw-bold me-1">ໄລຍະເວລາ: </span>
        <a href="?days=7"  class="filter-btn <?= $days_threshold == 7  ? 'active' : '' ?>"><i class="fas fa-clock me-1"></i> 7 ວັນ</a>
        <a href="?days=14" class="filter-btn <?= $days_threshold == 14 ? 'active' : '' ?>"><i class="fas fa-clock me-1"></i> 14 ວັນ</a>
        <a href="?days=21" class="filter-btn <?= $days_threshold == 21 ? 'active' : '' ?>"><i class="fas fa-clock me-1"></i> 21 ວັນ</a>
        <a href="?days=30" class="filter-btn <?= $days_threshold == 30 ? 'active' : '' ?>"><i class="fas fa-clock me-1"></i> 30 ວັນ</a>
        <a href="?days=60" class="filter-btn <?= $days_threshold == 60 ? 'active' : '' ?>"><i class="fas fa-clock me-1"></i> 60 ວັນ</a>
        <div class="ms-auto">
            <div class="input-group" style="max-width:280px;">
                <span class="input-group-text bg-white border-end-0" style="border-radius:30px 0 0 30px;border:1.5px solid #dee2e6;">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="ຄົ້ນຫາຊື່, ລະຫັດ, ໂທ..." style="border-radius:0 30px 30px 0;border:1.5px solid #dee2e6;border-left:none;">
            </div>
        </div>
    </div>

    <!-- ===== Member List ===== -->
    <div id="memberList">
        <?php if (empty($members)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle d-block mb-3"></i>
            <h5>ດີຫຼາຍ! ບໍ່ມີສະມາຊິກທີ່ຂາດການຕິດຕໍ່</h5>
            <p>ສະມາຊິກທຸກຄົນໄດ້ເຂົ້າມາໃຊ້ບໍລິການພາຍໃນ <?= $days_threshold ?> ວັນ</p>
        </div>
        <?php else: ?>
        <?php foreach ($members as $i => $m):
            $img = !empty($m['profile_img']) && file_exists(__DIR__ . '/../assets/img/members/' . $m['profile_img'])
                ? '../assets/img/members/' . $m['profile_img']
                : '../assets/img/members/default.png';

            $days = $m['days_since_checkin'];
            $isNever = $m['last_checkin'] === null;

            if ($isNever) {
                $badgeClass = 'absent-never';
                $badgeText  = '<i class="fas fa-ghost me-1"></i> ບໍ່ເຄີຍເຊັກອິນ';
            } elseif ($days >= 30) {
                $badgeClass = 'absent-critical';
                $badgeText  = '<i class="fas fa-fire me-1"></i> ຫ່ານ ' . $days . ' ວັນ';
            } else {
                $badgeClass = 'absent-warn';
                $badgeText  = '<i class="fas fa-exclamation-triangle me-1"></i> ຫ່ານ ' . $days . ' ວັນ';
            }

            $expireDate = $m['active_until'] ? date('d/m/Y', strtotime($m['active_until'])) : '-';
            $daysLeft   = $m['active_until'] ? max(0, (int)floor((strtotime($m['active_until']) - time()) / 86400)) : 0;
            $tel        = htmlspecialchars($m['tel'] ?? '');
            $telRaw     = preg_replace('/[^0-9]/', '', $m['tel'] ?? '');
        ?>
        <div class="member-row searchable" data-index="<?= $i ?>">
            <img src="<?= htmlspecialchars($img) ?>" class="member-avatar" alt="Avatar"
                 onerror="this.src='../assets/img/members/default.png'">

            <div class="member-info">
                <div class="member-name">
                    <?= htmlspecialchars($m['fname'] . ' ' . $m['lname']) ?>
                </div>
                <div class="member-code"><?= htmlspecialchars($m['member_code']) ?>
                    <?php if ($m['gender']): ?>
                    &nbsp;·&nbsp;
                    <?= $m['gender'] === 'ຊາຍ'
                        ? '<i class="fas fa-mars text-primary"></i>'
                        : '<i class="fas fa-venus" style="color:#e91e8c;"></i>' ?>
                    <?php endif; ?>
                </div>
                <span class="member-pkg"><i class="fas fa-box me-1"></i><?= htmlspecialchars($m['package_name']) ?></span>
            </div>

            <div class="expire-info text-center d-none d-md-block">
                <strong>ໝົດອາຍຸ</strong> <?= $expireDate ?>
                <span class="d-block mt-1 <?= $daysLeft <= 5 ? 'text-danger fw-bold' : '' ?>">
                    ເຫຼືອ <?= $daysLeft ?> ວັນ
                </span>
            </div>

            <span class="absent-badge <?= $badgeClass ?>"><?= $badgeText ?></span>

            <?php if ($tel): ?>
            <div class="d-flex flex-column gap-1 no-print">
                <a href="tel:<?= $telRaw ?>" class="contact-btn call">
                    <i class="fas fa-phone-alt"></i> <?= $tel ?>
                </a>
                <a href="https://wa.me/856<?= ltrim($telRaw, '0') ?>" target="_blank" class="contact-btn whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
            <?php else: ?>
            <span class="text-muted small no-print">ບໍ່ມີໝາຍເລກ</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Count footer -->
        <div class="text-center py-3 text-muted small no-print" id="listCount">
            ສະແດງທັງໝົດ <strong><?= $total ?></strong> ສະມາຊິກ
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// ===== Live Search =====
$('#searchInput').on('input', function() {
    var q = this.value.toLowerCase().trim();
    var shown = 0;
    $('.searchable').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(q) > -1) {
            $(this).show();
            shown++;
        } else {
            $(this).hide();
        }
    });
    $('#listCount').html('ສະແດງ <strong>' + shown + '</strong> ຈາກທັງໝົດ <strong><?= $total ?></strong> ສະມາຊິກ');
});

// ===== Staggered animation delay =====
$('.member-row').each(function(i) {
    $(this).css('animation-delay', (i * 0.04) + 's');
});
</script>
</body>
</html>
