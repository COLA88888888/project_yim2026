<?php
session_start();

// ກວດສອບວ່າເຂົ້າສູ່ລະບົບແລ້ວບໍ່
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    header('Location: index.php?expired=1');
    exit();
}

// Redirect all users to menu_admin.php to load the full admin sidebar and options
header("Location: menu_admin.php");
exit();

require_once 'config/db.php';

$settings = getSystemSettings($conn);
$site_name = $settings['gym_name'] ?? 'ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ';
$raw_logo = $settings['logo_path'] ?? 'assets/img/logo/gym_logo.png';
$site_logo = (strpos($raw_logo, '../') === 0) ? substr($raw_logo, 3) : $raw_logo;
$site_logo .= '?v=' . time();
$hotel_logo = $site_logo;
$hotel_name = $site_name;

// ============================================
// 3. ການກວດສອບຄວາມປອດໄພ ແລະ ສິດຜູ້ໃຊ້
// ============================================
$display_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if ($display_name === '') {
    $display_name = 'ຜູ້ໃຊ້';
}

$profile_img = $_SESSION['profile_img'] ?? 'default.png';
if (empty($profile_img) || !file_exists(__DIR__ . '/assets/img/users/' . $profile_img)) {
    $profile_img = 'default.png';
}
$profile_img_path = 'assets/img/users/' . $profile_img;
$nav_img_path = $profile_img_path;
?>
<html>
    <!-- Local Font: Noto Sans Lao Looped (Works Offline) -->
    <link rel="stylesheet" href="assets/css/local-font.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="assets/css/pages/menu-sidebar.css?v=<?php echo time(); ?>">
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($hotel_name); ?></title>
  <link rel="shortcut icon" href="<?php echo $hotel_logo; ?>" type="image/x-icon">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
	<script src="sweetalert/dist/sweetalert2.all.min.js"></script>		
	<script src="plugins/jquery/jquery.min.js"></script>
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

  <!-- Main Sidebar Container -->
  <?php include 'layouts/sidebar_user.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <?php 
      $iframe_src = 'Homepage.php';
    ?>
    <iframe width="100%" height="100%" frameborder="0" name="frame" src="<?php echo $iframe_src; ?>"></iframe>

       
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
   
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="dist/js/pages/dashboard.js"></script>

<script>
  $(function() {
    // ===== Active Menu Highlight =====
    var $navLinks = $('.nav-sidebar .nav-link[target="frame"]');
    
    // Clear on refresh, default to first page
    sessionStorage.removeItem('activeMenu');
    $navLinks.first().addClass('active');

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

    // Sync active menu with iframe
    $('iframe[name="frame"]').on('load', function() {
      $(this).removeClass('iframe-loading');
      try {
        // Close mobile sidebar when clicking inside the iframe
        var iframeDoc = this.contentWindow.document;
        $(iframeDoc).on('click', function() {
          closeMobileSidebar();
        });

        var iframeSrc = this.contentWindow.location.href.toLowerCase();
        $navLinks.removeClass('active');
        $('.nav-sidebar .nav-item > .nav-link').removeClass('active');
        $navLinks.each(function() {
          var href = $(this).attr('href');
          var hrefBase = href ? href.split('?')[0].toLowerCase() : '';
          
          if (hrefBase && iframeSrc.indexOf(hrefBase) !== -1) {
            $(this).addClass('active');
            var $parentLi = $(this).closest('.nav-treeview').closest('.nav-item');
            if ($parentLi.length) {
              $parentLi.addClass('menu-open menu-is-opening');
              $parentLi.children('.nav-link').addClass('active');
            }
          }
        });
      } catch(e) {}
    });

    // Smooth transitions for iframe menu links
    $(document).on('click', 'a[target="frame"]', function() {
      $('iframe[name="frame"]').addClass('iframe-loading');
    });

    // Click handler
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

<!-- update !-->

