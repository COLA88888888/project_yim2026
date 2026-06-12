<?php
// ============================================
// 1. ພາກເລີ່ມ SESSION ແລະ ກວດສອບການເຂົ້າສູ່ລະບົບ
// ============================================
// ເລີ່ມໃຊ້ງານ Session ເພື່ອເກັບຂໍ້ມູນຜູ້ໃຊ້
session_start();


// ============================================
// 3. ການກວດສອບຄວາມປອດໄພ ແລະ ສິດຜູ້ໃຊ້
// ============================================
// ກວດສອບວ່າຜູ້ໃຊ້ເຂົ້າສູ່ລະບົບແລ້ວຫຼືບໍ່
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    header('Location: index.php?expired=1');
    exit();
}

// ກວດສອບວ່າເປັນ admin ບໍ່
// if ($_SESSION['status'] !== 'ຜູ້ບໍລິຫານ') {
//     header("Location: menu_user.php");
//     exit();
// }

require_once 'config/db.php';

$settings = getSystemSettings($conn);
$site_name = $settings['gym_name'] ?? 'ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ';
$raw_logo = $settings['logo_path'] ?? 'assets/img/logo/gym_logo.png';
$site_logo = (strpos($raw_logo, '../') === 0) ? substr($raw_logo, 3) : $raw_logo;
$site_logo .= '?v=' . time();
$display_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if ($display_name === '') {
    $display_name = 'ຜູ້ໃຊ້';
}

$profile_img = $_SESSION['profile_img'] ?? 'default.png';
if (empty($profile_img) || !file_exists(__DIR__ . '/assets/img/users/' . $profile_img)) {
    $profile_img = 'default.png';
}
$profile_img_path = 'assets/img/users/' . $profile_img;
?>
<!-- ============================================ -->
<!-- 5. ພາກ HTML HEAD ແລະ CSS ຕົກແຕ່ງ           -->
<!-- ============================================ -->
 <html>
  <!-- ໂຫຼດຟອນພາສາລາວແບບ Local (ໃຊ້ໄດ້ໂດຍບໍ່ຕ້ອງໃຊ້ອິນເຕີເນັດ) -->
  <link rel="stylesheet" href="assets/css/local-font.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="assets/css/pages/menu-sidebar.css?v=<?php echo time(); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($site_name); ?></title>
  <link rel="shortcut icon" href="<?php echo $site_logo; ?>" type="image/x-icon">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- SweetAlert2 (Popup Dialogs) -->
  <script src="sweetalert/dist/sweetalert2.all.min.js"></script>
  <style>
    iframe[name="frame"] {
      transition: opacity 0.35s cubic-bezier(0.25, 1, 0.5, 1);
      opacity: 1;
    }
    iframe[name="frame"].iframe-loading {
      opacity: 0;
      transform: scale(0.99);
      transition: opacity 0.25s cubic-bezier(0.25, 1, 0.5, 1), transform 0.25s cubic-bezier(0.25, 1, 0.5, 1);
    }
  </style>
</head>
<body class="hold-transition sidebar-mini sidebar-no-expand layout-fixed">

<!-- ============================================ -->
<!-- 6. ພາກຫຼັກ WRAPPER ແລະ ແຖບເມນູ NAVBAR      -->
<!-- ============================================ -->
<div class="wrapper">

  <!-- ແຖບເມນູດ້ານເທິງ (Top Navigation Bar) -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left side: Menu -->
    <ul class="navbar-nav">
      <!-- Sidebar toggle -->
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button" style="color: #495057; font-size: 1.2rem;">
          <i class="fas fa-bars"></i>
        </a>
      </li>
      <!-- Home link -->
      <li class="nav-item d-none d-sm-inline-block">
        <a href="Homepage.php" target="frame" class="nav-link" style="color: #495057; font-weight: 600; font-size: 14px; padding: 0.5rem 1rem; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
          ໜ້າຫຼັກ
        </a>
      </li>
    </ul>

    <!-- Right side: Fullscreen + User Profile -->
    <ul class="navbar-nav ml-auto">
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown" id="notification-dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" style="color: #495057; font-size: 1.1rem; padding: 0.5rem 0.8rem; position: relative;">
          <i class="far fa-bell"></i>
          <span class="badge navbar-badge" id="noti-count" style="display: none; position: absolute; top: 4px; right: 2px; font-size: 0.6rem; padding: 2px 4.5px; border-radius: 50%; background-color: #ef4444; color: #fff;">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); border: 1px solid #e9ecef; width: 320px;">
          <span class="dropdown-item dropdown-header font-weight-bold text-dark border-bottom" id="noti-header">ບໍ່ມີການແຈ້ງເຕືອນ</span>
          <div id="noti-items" style="max-height: 300px; overflow-y: auto;">
            <!-- Loaded via AJAX -->
          </div>
          <div class="text-center text-muted py-2 bg-light" style="font-size: 0.8rem; cursor: default;">
            ລະບົບກວດສອບອັດຕະໂນມັດ
          </div>
        </div>
      </li>

      <!-- Fullscreen button -->
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button" style="color: #495057; font-size: 1.1rem; padding: 0.5rem 0.8rem;" title="ຂະຫຍາຍໜ້າຈໍ">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      
      <!-- User dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#" style="color: #495057; padding: 0.4rem 0.8rem; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
          <!-- User name (visible only on computer screen) -->
          <span class="d-none d-md-inline" style="font-weight: 600; margin-right: 0.5rem; font-size: 0.95rem;"><?php echo htmlspecialchars($display_name); ?></span>
          <!-- Profile picture (visible on both mobile and computer screen) -->
          <img src="<?php echo htmlspecialchars($profile_img_path); ?>" alt="User Profile" class="img-circle elevation-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #dee2e6;">
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); border: 1px solid #e9ecef;">
          <!-- User header -->
          <div class="dropdown-item" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0;">
            <div class="media">
              <img src="<?php echo htmlspecialchars($profile_img_path); ?>" alt="User Avatar" class="img-size-50 img-circle mr-3" style="width: 50px; height: 50px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
              <div class="media-body">
                <h3 class="dropdown-item-title" style="color: white; font-weight: 600; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($display_name); ?></h3>
                <p class="text-sm mb-0" style="color: rgba(255,255,255,0.9);"><i class="fas fa-circle text-success mr-1"></i> ອອນລາຍ</p>
              </div>
            </div>
          </div>
          <div class="dropdown-divider" style="margin: 0;"></div>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item text-danger" onclick="confirmLogout(); return false;">
            <i class="fas fa-sign-out-alt mr-2"></i> ອອກຈາກລະບົບ
          </a>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- ແຖບເມນູທາງຊ້າຍ (Main Sidebar) -->
  <?php 
  if (isset($_SESSION['status']) && $_SESSION['status'] === 'ຜູ້ບໍລິຫານ') {
      include 'layouts/sidebar_admin.php'; 
  } else {
      include 'layouts/sidebar_user.php'; 
  }
  ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	<?php 
    $iframe_src = 'Homepage.php'; // Default fallback
    if (!hasPermission('dashboard', 'view')) {
        $menu_mapping = [
            'checkin' => 'pages/checkin_manage.php',
            'subscriptions' => 'pages/subscriptions_manage.php',
            'daily_checkin' => 'pages/daily_checkin.php',
            'members' => 'pages/members_manage.php',
            'packages' => 'pages/packages_manage.php',
            'equipment' => 'pages/equipment_manage.php',
            'lockers' => 'pages/lockers_manage.php',
            'expenses' => 'pages/expenses.php',
            'sales' => 'pages/sales.php',
            'sales_history' => 'pages/sales_history.php',
            'stock_in' => 'pages/stock_in.php',
            'products' => 'pages/products.php',
            'product_categories' => 'pages/product_categories.php',
            'report_finance' => 'pages/revenue_report.php',
            'report_inactive_members' => 'pages/inactive_members_report.php',
            'report_equipment' => 'pages/equipment_good.php'
        ];

        foreach ($menu_mapping as $module => $page) {
            if (hasPermission($module, 'view')) {
                $iframe_src = $page;
                break;
            }
        }
    }
  ?>
	<iframe width="100%" height="100%" frameborder="0" name="frame" src="<?php echo $iframe_src; ?>"></iframe>
  </div>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<script>
  $(function() {
    // ===== Active Menu Highlight =====
    var $navLinks = $('.nav-sidebar .nav-link[target="frame"]');
    
    // Clear active on full page load, then highlight based on iframe src
    sessionStorage.removeItem('activeMenu');
    // Default: highlight first loaded page
    var defaultSrc = '<?php echo $iframe_src; ?>';
    $navLinks.each(function() {
      if ($(this).attr('href') === defaultSrc) {
        $(this).addClass('active');
        // If it's a sub-menu, expand it
        var $parentLi = $(this).closest('.nav-treeview').closest('.nav-item');
        if ($parentLi.length) {
          $parentLi.children('.nav-link').addClass('active');
          $parentLi.addClass('menu-open');
        }
      }
    });

    // Auto-close mobile sidebar helper
    function closeMobileSidebar() {
      if ($('body').hasClass('sidebar-open')) {
        $('body').removeClass('sidebar-open');
        $('body').addClass('sidebar-collapse sidebar-closed');
        $('#sidebar-overlay').remove();
        $('.sidebar-overlay').remove();
      }
    }

    // Close sidebar on clicking the parent page's overlay or anywhere outside
    $(document).on('click', '#sidebar-overlay, .sidebar-overlay', function() {
      closeMobileSidebar();
    });

    // Listen for iframe load to sync active menu and bind iframe clicks
    $('iframe[name="frame"]').on('load', function() {
      $(this).removeClass('iframe-loading');
      try {
        // Close mobile sidebar when clicking inside the iframe
        var iframeDoc = this.contentWindow.document;
        $(iframeDoc).on('click', function() {
          closeMobileSidebar();
        });

        var iframePath = this.contentWindow.location.pathname;
        var iframeSearch = this.contentWindow.location.search;
        // Get the relative path after the project folder, or just use the full pathname to check
        if (iframePath !== 'about:blank' && iframePath !== '' && iframePath !== '/') {
          $navLinks.removeClass('active');
          $('.nav-sidebar .nav-item > .nav-link').removeClass('active');
          
          var matched = false;
          // First pass: try exact match including query parameters
          $navLinks.each(function() {
            var href = $(this).attr('href');
            if (href && href !== '#') {
              var hrefPath = href.split('?')[0];
              var hrefQuery = href.split('?')[1] || '';
              if (iframePath.indexOf(hrefPath) !== -1 && hrefQuery !== '' && iframeSearch.indexOf(hrefQuery) !== -1) {
                $(this).addClass('active');
                var $parentLi = $(this).closest('.nav-treeview').closest('.nav-item');
                if ($parentLi.length) {
                  $parentLi.children('.nav-link').addClass('active');
                  $parentLi.addClass('menu-open');
                }
                matched = true;
              }
            }
          });

          // Second pass: fallback to path-only match if no query parameter matched
          if (!matched) {
            $navLinks.each(function() {
              var href = $(this).attr('href');
              if (href && href !== '#') {
                var hrefPath = href.split('?')[0];
                var hrefQuery = href.split('?')[1] || '';
                if (iframePath.indexOf(hrefPath) !== -1 && hrefQuery === '') {
                  $(this).addClass('active');
                  var $parentLi = $(this).closest('.nav-treeview').closest('.nav-item');
                  if ($parentLi.length) {
                    $parentLi.children('.nav-link').addClass('active');
                    $parentLi.addClass('menu-open');
                  }
                }
              }
            });
          }
        }
      } catch(e) { /* cross-origin */ }
    });

    // Smooth transitions for iframe menu links
    $(document).on('click', 'a[target="frame"]', function() {
      $('iframe[name="frame"]').addClass('iframe-loading');
    });

    // Click handler for all sidebar links that target the iframe
    $navLinks.on('click', function() {
      var $clicked = $(this);
      $navLinks.removeClass('active');
      $('.nav-sidebar .nav-item > .nav-link').removeClass('active');
      $clicked.addClass('active');
      
      var $parentLi = $clicked.closest('.nav-treeview').closest('.nav-item');
      if ($parentLi.length) {
        $parentLi.children('.nav-link').addClass('active');
      }

      // Auto-close sidebar on small screens
      if ($(window).width() <= 991) {
        setTimeout(function() {
          closeMobileSidebar();
        }, 150);
      }
    });

    // ===== Load & Manage Expiration & Stock Notifications =====
    function loadNotifications() {
      $.getJSON('api/notifications_api.php', function(res) {
        if (res.success) {
          var list = res.notifications;
          var count = list.length;
          var $badge = $('#noti-count');
          var $header = $('#noti-header');
          var $itemsContainer = $('#noti-items');
          
          $itemsContainer.empty();
          
          if (count > 0) {
            $badge.text(count).show();
            $header.text('ມີ ' + count + ' ການແຈ້ງເຕືອນ');
            
            list.forEach(function(item) {
              var html = '';
              if (item.type === 'membership') {
                var icon = 'fa-exclamation-triangle text-warning';
                var textClass = 'text-warning';
                var statusText = '';
                var formattedDate = new Date(item.end_date).toLocaleDateString('lo-LA', {day: 'numeric', month: 'numeric', year: 'numeric'});
                
                if (item.days_left < 0) {
                  icon = 'fa-times-circle text-danger';
                  textClass = 'text-danger';
                  statusText = 'ໝົດອາຍຸແລ້ວ ' + Math.abs(item.days_left) + ' ມື້ (' + formattedDate + ')';
                } else if (item.days_left === 0) {
                  icon = 'fa-exclamation-circle text-danger';
                  textClass = 'text-danger';
                  statusText = 'ໝົດອາຍຸມື້ນີ້ (' + formattedDate + ')';
                } else {
                  statusText = 'ເຫຼືອ ' + item.days_left + ' ມື້ (ໝົດອາຍຸ ' + formattedDate + ')';
                }
                
                html = '<a href="pages/subscriptions_manage.php?search=' + encodeURIComponent(item.member_code) + '" target="frame" class="dropdown-item py-3 border-bottom" style="white-space: normal; display: flex; align-items: start; gap: 10px;">'
                  + '<i class="fas ' + icon + ' mt-1" style="font-size: 1.1rem; flex-shrink: 0;"></i>'
                  + '<div>'
                  + '<span class="d-block font-weight-bold text-dark" style="font-size: 0.88rem; line-height: 1.2;">' + htmlEncode(item.fname) + ' ' + htmlEncode(item.lname) + ' (' + htmlEncode(item.member_code) + ')</span>'
                  + '<span class="d-block text-muted mt-1" style="font-size: 0.78rem; line-height: 1.3;">ແພັກເກດ: ' + htmlEncode(item.package_name) + '</span>'
                  + '<span class="d-block font-weight-bold ' + textClass + ' mt-1" style="font-size: 0.78rem; line-height: 1.2;">' + statusText + '</span>'
                  + '</div>'
                  + '</a>';
              } else if (item.type === 'low_stock') {
                html = '<a href="pages/products.php" target="frame" class="dropdown-item py-3 border-bottom" style="white-space: normal; display: flex; align-items: start; gap: 10px;">'
                  + '<i class="fas fa-boxes text-warning mt-1" style="font-size: 1.1rem; flex-shrink: 0;"></i>'
                  + '<div>'
                  + '<span class="d-block font-weight-bold text-dark" style="font-size: 0.88rem; line-height: 1.2;">' + htmlEncode(item.product_name) + ' (' + htmlEncode(item.product_code) + ')</span>'
                  + '<span class="d-block text-warning font-weight-bold mt-1" style="font-size: 0.78rem; line-height: 1.2;">ສິນຄ້າໃກ້ໝົດ! ເຫຼືອພຽງ: ' + item.quantity + '</span>'
                  + '</div>'
                  + '</a>';
              } else if (item.type === 'out_of_stock') {
                html = '<a href="pages/products.php" target="frame" class="dropdown-item py-3 border-bottom" style="white-space: normal; display: flex; align-items: start; gap: 10px;">'
                  + '<i class="fas fa-ban text-danger mt-1" style="font-size: 1.1rem; flex-shrink: 0;"></i>'
                  + '<div>'
                  + '<span class="d-block font-weight-bold text-dark" style="font-size: 0.88rem; line-height: 1.2;">' + htmlEncode(item.product_name) + ' (' + htmlEncode(item.product_code) + ')</span>'
                  + '<span class="d-block text-danger font-weight-bold mt-1" style="font-size: 0.78rem; line-height: 1.2;">ສິນຄ້າໝົດແລ້ວ!</span>'
                  + '</div>'
                  + '</a>';
              }
              $itemsContainer.append(html);
            });
          } else {
            $badge.hide();
            $header.text('ບໍ່ມີການແຈ້ງເຕືອນ');
            $itemsContainer.append('<div class="text-center py-4 text-muted"><i class="far fa-bell-slash fa-2x mb-2 d-block"></i>ບໍ່ມີການແຈ້ງເຕືອນໃນເວລານີ້</div>');
          }
        }
      }).fail(function() {
        console.log('Failed to load notifications');
      });
    }

    function htmlEncode(str) {
      return $('<div>').text(str).html();
    }

    // Initial load
    loadNotifications();
    
    // Auto refresh every 30 seconds
    setInterval(loadNotifications, 30000);

    // Global refresh function for iframes
    window.refreshNotifications = function() {
      loadNotifications();
    };

  });
</script>
<script>
function confirmLogout() {
    Swal.fire({
        title: 'ຢືນຢັນການອອກຈາກລະບົບ',
        text: "ທ່ານຕ້ອງການອອກຈາກລະບົບບໍ?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ອອກຈາກລະບົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    })
}

// Block back button after logout
window.addEventListener('pageshow', function (event) {
    if (event.persisted || (typeof window.performance != 'undefined' && window.performance.navigation.type === 2)) {
        window.location.reload();
    } 
});
</script>
</body>
</html>
