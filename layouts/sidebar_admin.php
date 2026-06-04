<!-- ============================================ -->
<!-- SIDEBAR ສຳລັບ ADMIN (ແຖບເມນູທາງຊ້າຍ)       -->
<!-- ============================================ -->

<aside class="main-sidebar elevation-4">
  <!-- Logo & Brand -->
  <a href="menu_admin.php" class="brand-link text-center" style="padding: 14px 10px; height: auto; display: block; border-bottom: 1px solid rgba(255,255,255,0.15);">
    <img src="<?php echo $site_logo; ?>" alt="Logo" class="elevation-3" style="width: 80px; height: 80px; object-fit: cover; margin: 0 auto; opacity: 1; border-radius: 5px;">
    <span class="brand-text font-weight-light d-block mt-2" style="font-size: 14px;"><b><?php echo htmlspecialchars($site_name); ?></b></span>
  </a>

  <!-- ພາກເມນູ Sidebar -->
  <div class="sidebar">
    <!-- ເມນູລາຍການ -->
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

        <!-- ເມນູ: ເຊັກອິນລູກຄ້າລາຍວັນ -->
        <li class="nav-item">
          <a href="pages/daily_checkin.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-user-plus text-info"></i>
            <p>ເຊັກອິນລູກຄ້າລາຍວັນ</p>
          </a>
        </li>

        <!-- ເມນູ: ເຊັກອິນເຂົ້າໃຊ້ບໍລິການ -->
        <li class="nav-item">
          <a href="pages/checkin_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-id-card text-success"></i>
            <p>ເຊັກອິນເຂົ້າໃຊ້ບໍລິການ</p>
          </a>
        </li>

        <!-- ເມນູ: ລົງທະບຽນແພັກເກດ/ຊຳລະເງິນ -->
        <li class="nav-item">
          <a href="pages/subscriptions_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-file-invoice-dollar text-warning"></i>
            <p>ລົງທະບຽນແພັກເກດ</p>
          </a>
        </li>

        <!-- ຫົວຂໍ້: ຈັດການຂໍ້ມູນ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ຈັດການຂໍ້ມູນ</li>

        <!-- ເມນູ: ຂໍ້ມູນສະມາຊິກ -->
        <li class="nav-item">
          <a href="pages/members_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-users"></i>
            <p>ຂໍ້ມູນສະມາຊິກ</p>
          </a>
        </li>

        <!-- ເມນູ: ຂໍ້ມູນແພັກເກດ -->
        <li class="nav-item">
          <a href="pages/packages_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-tags"></i>
            <p>ແພັກເກດຍິມ</p>
          </a>
        </li>

        <!-- ເມນູ: ຈັດການເຄື່ອງອອກກຳລັງກາຍ -->
        <li class="nav-item">
          <a href="pages/equipment_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-dumbbell"></i>
            <p>ເຄື່ອງອອກກຳລັງກາຍ</p>
          </a>
        </li>

        <!-- ເມນູ: ຈັດການລັອກເກີເກັບເຄື່ອງ -->
        <li class="nav-item">
          <a href="pages/lockers_manage.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-lock"></i>
            <p>ລັອກເກີເກັບເຄື່ອງ</p>
          </a>
        </li>

        <!-- ຫົວຂໍ້: ລາຍງານຂໍ້ມູນ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ລາຍງານຂໍ້ມູນ</li>

        <!-- ເມນູ: ລາຍງານລາຍຮັບ -->
        <li class="nav-item">
          <a href="pages/revenue_report.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-chart-bar text-primary"></i>
            <p>ລາຍງານລາຍຮັບ</p>
          </a>
        </li>

        <!-- ເມນູ: ລາຍງານລາຍຮັບລູກຄ້າລາຍວັນ -->
        <li class="nav-item">
          <a href="pages/revenue_report.php?tab=daily" target="frame" class="nav-link">
            <i class="nav-icon fas fa-user-clock text-info"></i>
            <p>ລາຍງານລາຍຮັບລາຍວັນ</p>
          </a>
        </li>

        <!-- ເມນູ: ລາຍງານສະມາຊິກຂາດການຕິດຕໍ່ -->
        <li class="nav-item">
          <a href="pages/inactive_members_report.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-user-slash" style="color:#ff9f43;"></i>
            <p>ສະມາຊິກຂາດການຕິດຕໍ່</p>
          </a>
        </li>

        <!-- ເມນູ: ລາຍງານອຸປະກອນ (dropdown) -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-dumbbell text-warning"></i>
            <p>
              ລາຍງານອຸປະກອນ
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <!-- ອຸປະກອນດີ -->
            <li class="nav-item">
              <a href="pages/equipment_good.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-check-circle text-success"></i>
                <p>ອຸປະກອນດີ / ໃຊ້ໄດ້</p>
              </a>
            </li>
            <!-- ອຸປະກອນເພ -->
            <li class="nav-item">
              <a href="pages/equipment_broken.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-tools text-danger"></i>
                <p>ອຸປະກອນເພ / ຊຳລຸດ</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- ຫົວຂໍ້: ຈັດການລະບົບ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ຈັດການຜູ້ໃຊ້ງານ</li>
        
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-user-cog"></i>
            <p>
              ຈັດການພະນັກງານ
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
             <!-- ເມນູ: ຈັດການຜູ້ໃຊ້ງານ -->
            <li class="nav-item">
              <a href="pages/users_manage.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-users-cog"></i>
                <p>ລາຍຊື່ພະນັກງານ</p>
              </a>
            </li>
            
            <!-- ເມນູ: ກຳນົດສິດ -->
            <li class="nav-item">
              <a href="pages/permission_manage.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-key"></i>
                <p>ກຳນົດສິດການໃຊ້ງານ</p>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
