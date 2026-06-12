<?php
session_start();
require_once 'config/db.php';
require_once 'config/dashboard_stats.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['checked'])) {
    echo "<script>window.top.location.href = 'index.php?expired=1';</script>";
    exit();
}

$stats = getDashboardQuickStats($conn);

// ດຶງສະຖິຕິການເຂົ້າໃຊ້ບໍລິການໃນ 7 ວັນຫຼ້າສຸດ (Last 7 Days Check-ins)
$checkin_labels = [];
$checkin_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('d/m', strtotime("-$i days"));
    $checkin_labels[] = $display_date;
    
    // ນັບຈຳນວນຄົນເຊັກອິນໃນວັນນີ້
    $sql = "SELECT COUNT(*) FROM checkins WHERE DATE(checkin_time) = '$date'";
    $res = $conn ? mysqli_query($conn, $sql) : false;
    $count = 0;
    if ($res) {
        $row = mysqli_fetch_row($res);
        $count = (int)($row[0] ?? 0);
    }
    $checkin_counts[] = $count;
}

// ດຶງສະຖິຕິແພັກເກດຍອດນິຍົມ (Doughnut Chart)
$package_labels = [];
$package_counts = [];
$package_breakdown = [];
$pkg_query = mysqli_query($conn, "
    SELECT p.package_name, COUNT(m.membership_id) AS qty, SUM(COALESCE(m.price_paid, 0)) as revenue
    FROM packages p
    LEFT JOIN memberships m ON p.package_id = m.package_id
    GROUP BY p.package_id, p.package_name
    ORDER BY qty DESC
");
if ($pkg_query) {
    while ($row = mysqli_fetch_assoc($pkg_query)) {
        $package_breakdown[] = $row;
        $package_labels[] = $row['package_name'] ?? 'ບໍ່ມີຊື່';
        $package_counts[] = (int)($row['qty'] ?? 0);
    }
}

$base_path = '';
require_once 'layouts/header.php';
?>

<link rel="stylesheet" href="assets/css/pages/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="assets/css/pages/homepage-custom.css?v=<?php echo time(); ?>">

<?php
$has_any_card = hasPermission('dashboard', 'view');
?>

<div class="dashboard-page">

  <?php if ($has_any_card): ?>
  <!-- Row of 4 cards as requested -->
  <!-- Row of 6 cards for segregated tracking -->
  <div class="stat-cards-row-custom">
    <!-- Card 1: Total Members -->
    <?php if (hasPermission('dashboard', 'view') || hasPermission('members', 'view')): ?>
    <a href="pages/members_manage.php" class="stat-card-custom gc-blue">
      <div class="stat-card-top-custom">
        <div>
          <div class="stat-card-label-custom">ສະມາຊິກທັງໝົດ</div>
          <div class="stat-card-value-custom"><?= number_format($stats['total_members']) ?> <span class="stat-card-unit-custom">ຄົນ</span></div>
        </div>
        <div class="stat-card-icon-custom"><i class="fas fa-users"></i></div>
      </div>
      <div class="stat-card-footer-custom">
        <i class="fas fa-user-check"></i> ສະມາຊິກທີ່ເຄື່ອນໄຫວ: <?= $stats['active_members'] ?> ຄົນ
      </div>
    </a>
    <?php endif; ?>

    <!-- Card 2: Member Check-ins Today -->
    <?php if (hasPermission('dashboard', 'view') || hasPermission('checkin', 'view')): ?>
    <a href="pages/checkin_manage.php" class="stat-card-custom gc-green">
      <div class="stat-card-top-custom">
        <div>
          <div class="stat-card-label-custom">ສະມາຊິກເຂົ້າໃຊ້ວັນນີ້</div>
          <div class="stat-card-value-custom"><?= number_format($stats['checkins_today']) ?> <span class="stat-card-unit-custom">ຄັ້ງ</span></div>
        </div>
        <div class="stat-card-icon-custom"><i class="fas fa-id-card"></i></div>
      </div>
      <div class="stat-card-footer-custom">
        <i class="fas fa-clock"></i> ການເຊັກອິນເຂົ້າໃຊ້ໃນມື້ນີ້
      </div>
    </a>
    <?php endif; ?>

    <!-- Card 3: Daily Customers Today -->
    <?php if (hasPermission('dashboard', 'view') || hasPermission('daily_checkin', 'view')): ?>
    <a href="pages/daily_checkin.php" class="stat-card-custom gc-cyan">
      <div class="stat-card-top-custom">
        <div>
          <div class="stat-card-label-custom">ລູກຄ້າລາຍວັນວັນນີ້</div>
          <div class="stat-card-value-custom"><?= number_format($stats['daily_checkins_today']) ?> <span class="stat-card-unit-custom">ຄົນ</span></div>
        </div>
        <div class="stat-card-icon-custom"><i class="fas fa-user-check"></i></div>
      </div>
      <div class="stat-card-footer-custom">
        <i class="fas fa-users-cog"></i> ບັນທຶກການເຊັກອິນລາຍວັນ
      </div>
    </a>
    <?php endif; ?>

    <!-- Card 4: Member Subscription Revenue Month -->
    <?php if (hasPermission('dashboard', 'view') || hasPermission('subscriptions', 'view')): ?>
    <a href="pages/revenue_report.php?tab=subscription" class="stat-card-custom gc-indigo">
      <div class="stat-card-top-custom">
        <div>
          <div class="stat-card-label-custom">ລາຍຮັບສະມາຊິກ (ເດືອນນີ້)</div>
          <div class="stat-card-value-custom" style="font-size: 1.35rem;"><?= formatCurrency($stats['sub_revenue_month']) ?></div>
          <div class="small mt-1 text-white-50">
            ສົດ: <?= number_format($stats['sub_month_cash']) ?> | ໂອນ: <?= number_format($stats['sub_month_transfer']) ?>
          </div>
        </div>
        <div class="stat-card-icon-custom"><i class="fas fa-file-invoice-dollar"></i></div>
      </div>
      <div class="stat-card-footer-custom">
        <i class="fas fa-calendar-day"></i> ມື້ນີ້: <?= formatCurrency($stats['sub_revenue_today']) ?> (ສົດ: <?= number_format($stats['sub_today_cash']) ?> | ໂອນ: <?= number_format($stats['sub_today_transfer']) ?>)
      </div>
    </a>
    <?php endif; ?>

    <!-- Card 5: Daily Customer Revenue Month -->
    <?php if (hasPermission('dashboard', 'view') || hasPermission('daily_checkin', 'view')): ?>
    <a href="pages/revenue_report.php?tab=daily" class="stat-card-custom gc-pink">
      <div class="stat-card-top-custom">
        <div>
          <div class="stat-card-label-custom">ລາຍຮັບລາຍວັນ (ເດືອນນີ້)</div>
          <div class="stat-card-value-custom" style="font-size: 1.35rem;"><?= formatCurrency($stats['daily_revenue_month']) ?></div>
          <div class="small mt-1 text-white-50">
            ສົດ: <?= number_format($stats['daily_month_cash']) ?> | ໂອນ: <?= number_format($stats['daily_month_transfer']) ?>
          </div>
        </div>
        <div class="stat-card-icon-custom"><i class="fas fa-money-bill-wave"></i></div>
      </div>
      <div class="stat-card-footer-custom">
        <i class="fas fa-calendar-day"></i> ມື້ນີ້: <?= formatCurrency($stats['daily_revenue_today']) ?> (ສົດ: <?= number_format($stats['daily_today_cash']) ?> | ໂອນ: <?= number_format($stats['daily_today_transfer']) ?>)
      </div>
    </a>
    <?php endif; ?>

    <!-- Card 6: Broken Equipment -->
    <?php if (hasPermission('dashboard', 'view') || hasPermission('equipment', 'view')): ?>
    <a href="pages/equipment_manage.php" class="stat-card-custom gc-red">
      <div class="stat-card-top-custom">
        <div>
          <div class="stat-card-label-custom">ເຄື່ອງອອກກຳລັງກາຍເພ</div>
          <div class="stat-card-value-custom"><?= number_format($stats['broken_equipment']) ?> <span class="stat-card-unit-custom">ເຄື່ອງ</span></div>
        </div>
        <div class="stat-card-icon-custom"><i class="fas fa-dumbbell"></i></div>
      </div>
      <div class="stat-card-footer-custom">
        <i class="fas fa-tools"></i> ທັງໝົດ: <?= $stats['total_equipment'] ?> (ດີ: <?= $stats['good_equipment'] ?>)
      </div>
    </a>
    <?php endif; ?>
  </div>

  <!-- Charts Section -->
  <div class="row mt-4">
    <?php 
    $show_checkin_chart = hasPermission('dashboard', 'view') || hasPermission('checkin', 'view');
    $show_package_chart = hasPermission('dashboard', 'view') || hasPermission('subscriptions', 'view');
    $left_col_class = $show_package_chart ? 'col-lg-8' : 'col-lg-12';
    $right_col_class = $show_checkin_chart ? 'col-lg-4' : 'col-lg-12';
    ?>

    <!-- Left Column: Check-in statistics -->
    <?php if ($show_checkin_chart): ?>
    <div class="<?= $left_col_class ?> mb-4">
      <div class="card chart-card-custom h-100">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <h5 class="chart-card-title"><i class="fas fa-chart-line text-primary mr-2"></i> ສະຖິຕິການເຂົ້າໃຊ້ບໍລິການ (7 ວັນຫຼ້າສຸດ)</h5>
        </div>
        <div class="card-body">
          <div class="chart-container-custom">
            <canvas id="checkinChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Right Column: Popular packages -->
    <?php if ($show_package_chart): ?>
    <div class="<?= $right_col_class ?> mb-4">
      <div class="card chart-card-custom h-100">
        <div class="card-header-custom">
          <h5 class="chart-card-title"><i class="fas fa-chart-pie text-success mr-2"></i> ແພັກເກດຍອດນິຍົມ</h5>
        </div>
        <div class="card-body d-flex flex-column justify-content-between">
          <div class="chart-container-custom doughnut-container">
            <canvas id="packageChart"></canvas>
          </div>
          
          <div class="condition-stats-custom mt-4">
            <?php 
            $colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6610f2'];
            $idx = 0;
            foreach (array_slice($package_breakdown, 0, 3) as $pb):
              $c = $colors[$idx % count($colors)];
              $idx++;
            ?>
              <div class="stat-item-custom">
                <span class="dot" style="background-color: <?= $c ?>;"></span>
                <span class="label"><?= htmlspecialchars($pb['package_name']) ?>:</span>
                <span class="value font-weight-bold ml-1"><?= $pb['qty'] ?> ຄັ້ງ</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php else: ?>
    <?php
    // Fetch current staff user details for the Welcome Card
    $session_user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $user_q = mysqli_query($conn, "SELECT fname, lname, status, tel, profile_img FROM users WHERE user_id = '$session_user_id' LIMIT 1");
    $user_data = $user_q ? mysqli_fetch_assoc($user_q) : null;
    $display_name = $user_data ? trim($user_data['fname'] . ' ' . $user_data['lname']) : 'ພະນັກງານ';
    $profile_img = $user_data && $user_data['profile_img'] ? $user_data['profile_img'] : 'default.png';
    $profile_img_path = 'assets/img/users/' . $profile_img;
    if (!file_exists(__DIR__ . '/' . $profile_img_path) || empty($user_data['profile_img'])) {
        $profile_img_path = 'assets/img/users/default.png';
    }
    $status = $user_data && $user_data['status'] ? $user_data['status'] : 'ພະນັກງານ';
    $tel = $user_data && $user_data['tel'] ? $user_data['tel'] : '-';
    ?>
    <!-- Glassmorphic Welcome Card -->
    <div class="welcome-container-custom">
        <div class="welcome-card-premium">
            <div class="welcome-card-header-gradient">
                <div class="welcome-avatar-wrapper">
                    <img src="<?= htmlspecialchars($profile_img_path) ?>" alt="Avatar" class="welcome-avatar">
                </div>
            </div>
            <div class="welcome-card-body">
                <h3 class="welcome-greeting">ສະບາຍດີ, <?= htmlspecialchars($display_name) ?>!</h3>
                <p class="welcome-text">ຍິນດີຕ້ອນຮັບເຂົ້າສູ່ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ</p>
                
                <div class="welcome-clock-wrapper">
                    <div class="clock-item">
                        <i class="far fa-calendar-alt text-primary mr-2"></i>
                        <span><?= date('d/m/Y') ?></span>
                    </div>
                    <div class="clock-item ml-4">
                        <i class="far fa-clock text-success mr-2"></i>
                        <span id="liveClock">00:00:00</span>
                    </div>
                </div>
                
                <div class="welcome-info-grid">
                    <div class="info-row">
                        <span class="info-label">ສະຖານະ:</span>
                        <span class="info-value badge bg-primary text-white" style="font-size:0.85rem; padding: 4px 12px;"><?= htmlspecialchars($status) ?></span>
                    </div>
                    <div class="info-row mt-2">
                        <span class="info-label">ເບີໂທລະສັບ:</span>
                        <span class="info-value text-dark fw-bold"><?= htmlspecialchars($tel) ?></span>
                    </div>
                </div>
                
                <div class="welcome-shortcuts">
                    <h6 class="shortcuts-title">ເມນູທີ່ທ່ານສາມາດເຂົ້າເຖິງໄດ້:</h6>
                    <div class="shortcuts-grid">
                        <?php if (hasPermission('dashboard', 'view')): ?>
                            <a href="Homepage.php" class="shortcut-btn btn-active"><i class="fas fa-chart-line mr-2"></i>ດາດສ໌ບອດ</a>
                        <?php endif; ?>
                        <?php if (hasPermission('checkin', 'view')): ?>
                            <a href="pages/checkin_manage.php" class="shortcut-btn"><i class="fas fa-id-card mr-2 text-success"></i>ເຊັກອິນເຂົ້າໃຊ້</a>
                        <?php endif; ?>
                        <?php if (hasPermission('subscriptions', 'view')): ?>
                            <a href="pages/subscriptions_manage.php" class="shortcut-btn"><i class="fas fa-file-invoice-dollar mr-2 text-warning"></i>ລົງທະບຽນແພັກເກດ</a>
                        <?php endif; ?>
                        <?php if (hasPermission('members', 'view')): ?>
                            <a href="pages/members_manage.php" class="shortcut-btn"><i class="fas fa-users mr-2 text-primary"></i>ຂໍ້ມູນສະມາຊິກ</a>
                        <?php endif; ?>
                        <?php if (hasPermission('daily_checkin', 'view')): ?>
                            <a href="pages/daily_checkin.php" class="shortcut-btn"><i class="fas fa-user-plus mr-2 text-info"></i>ເຊັກອິນລາຍວັນ</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
  <?php endif; ?>

</div>


<script src="plugins/chart.js/Chart.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // ຕັ້ງຄ່າ Font ພາສາລາວໃຫ້ກັບ Chart.js ທັງໝົດ
    if (typeof Chart !== 'undefined') {
        if (Chart.defaults && Chart.defaults.font) {
            Chart.defaults.font.family = "'Noto Sans Lao Looped', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        }
        if (Chart.defaults && Chart.defaults.global) {
            Chart.defaults.global.defaultFontFamily = "'Noto Sans Lao Looped', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        }
    }

    // 1. Check-in Line Chart
    <?php if (hasPermission('dashboard', 'view') || hasPermission('checkin', 'view')): ?>
    const checkinLabels = <?php echo json_encode($checkin_labels); ?>;
    const checkinData = <?php echo json_encode($checkin_counts); ?>;
    
    const ctxCheckin = document.getElementById('checkinChart').getContext('2d');
    new Chart(ctxCheckin, {
        type: 'line',
        data: {
            labels: checkinLabels,
            datasets: [{
                label: 'ຈຳນວນຄົນເຂົ້າໃຊ້ບໍລິການ',
                data: checkinData,
                backgroundColor: 'rgba(54, 162, 235, 0.15)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    grid: { color: 'rgba(0, 0, 0, 0.04)' },
                    ticks: { precision: 0 }
                }
            }
        }
    });
    <?php endif; ?>

    // 2. Package Doughnut Chart
    <?php if (hasPermission('dashboard', 'view') || hasPermission('subscriptions', 'view')): ?>
    const packageLabels = <?php echo json_encode($package_labels); ?>;
    const packageData = <?php echo json_encode($package_counts); ?>;
    
    const ctxPackage = document.getElementById('packageChart').getContext('2d');
    new Chart(ctxPackage, {
        type: 'doughnut',
        data: {
            labels: packageLabels,
            datasets: [{
                data: packageData,
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#17a2b8',
                    '#6610f2'
                ],
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif; ?>

    // Intercept clicks on links for smooth exit transitions
    const dashboardPage = document.querySelector('.dashboard-page');
    const cards = document.querySelectorAll('.stat-card-custom');
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const targetUrl = this.getAttribute('href');
            
            if (dashboardPage) {
                dashboardPage.style.transition = 'opacity 0.35s cubic-bezier(0.25, 1, 0.5, 1), transform 0.35s cubic-bezier(0.25, 1, 0.5, 1)';
                dashboardPage.style.opacity = '0';
                dashboardPage.style.transform = 'translateY(-12px)';
            }
            
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 300);
        });
    });

    // 3. Live Clock for Welcome Card
    function updateClock() {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const timeStr = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        const clockEl = document.getElementById('liveClock');
        if (clockEl) clockEl.textContent = timeStr;
    }
    setInterval(updateClock, 1000);
    updateClock();
});
</script>


<?php
require_once 'layouts/footer.php';
?>
