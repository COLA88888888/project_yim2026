<!-- ============================================ -->
<!-- SIDEBAR ສຳລັບ USER (ແຖບເມນູທາງຊ້າຍ)        -->
<!-- ສະແດງເມນູຕາມສິດ permissions ທີ່ກຳນົດໄວ້        -->
<!-- ============================================ -->

<aside class="main-sidebar elevation-4">
  <!-- Brand Logo -->
  <a href="#" class="brand-link text-center" style="padding: 14px 10px; height: auto; display: block; border-bottom: 1px solid rgba(255,255,255,0.15);">
    <img src="<?php echo $site_logo; ?>" alt="Logo" class="elevation-3" style="width: 80px; height: 80px; object-fit: cover; margin: 0 auto; opacity: 1; border-radius: 5px;">
    <span class="brand-text font-weight-light d-block mt-2" style="font-size: 15px;"><b><?php echo htmlspecialchars($site_name); ?></b></span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar Menu -->
    <nav class="mt-2 pb-5">
      <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="true">
      
        <!-- ຫົວຂໍ້: ໜ້າຫຼັກ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; letter-spacing: 1px;">ໜ້າຫຼັກ</li>
        
        <!-- ເມນູ: ດາດສ໌ບອດ -->
        <li class="nav-item">
          <a href="Homepage.php" target="frame" class="nav-link active">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>ດາດສ໌ບອດ</p>
          </a>
        </li>

        <!-- ຫົວຂໍ້: ການບໍລິການຍິມ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ບໍລິການຍິມ</li>

        <!-- ເມນູ: ເcheck-in -->
        <?php if (hasPermission('checkin', 'view')): ?>
        <li class="nav-item">
          <a href="pages/checkin_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-id-card text-success"></i>
            <p>ເຊັກອິນເຂົ້າໃຊ້ບໍລິການ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ລົງທະບຽນແພັກເກດ -->
        <?php if (hasPermission('subscriptions', 'view')): ?>
        <li class="nav-item">
          <a href="pages/subscriptions_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-file-invoice-dollar text-warning"></i>
            <p>ລົງທະບຽນແພັກເກດ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ເຊັກອິນລູກຄ້າລາຍວັນ -->
        <?php if (hasPermission('daily_checkin', 'view')): ?>
        <li class="nav-item">
          <a href="pages/daily_checkin.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-user-plus text-info"></i>
            <p>ເຊັກອິນລູກຄ້າລາຍວັນ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ຫົວຂໍ້: ຈັດການຂໍ້ມູນ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ຈັດການຂໍ້ມູນ</li>

        <!-- ເມນູ: ຂໍ້ມູນສະມາຊິກ -->
        <?php if (hasPermission('members', 'view')): ?>
        <li class="nav-item">
          <a href="pages/members_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-users"></i>
            <p>ຂໍ້ມູນສະມາຊິກ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ຈັດການເຄື່ອງອອກກຳລັງກາຍ -->
        <?php if (hasPermission('equipment', 'view')): ?>
        <li class="nav-item">
          <a href="pages/equipment_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-dumbbell"></i>
            <p>ເຄື່ອງອອກກຳລັງກາຍ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ຈັດການລັອກເກີເກັບເຄື່ອງ -->
        <?php if (hasPermission('lockers', 'view')): ?>
        <li class="nav-item">
          <a href="pages/lockers_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-lock"></i>
            <p>ລັອກເກີເກັບເຄື່ອງ</p>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
