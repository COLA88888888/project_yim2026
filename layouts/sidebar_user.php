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

        <!-- ເມນູ: ຈັດການລາຍຈ່າຍ -->
        <?php if (hasPermission('expenses', 'view')): ?>
        <li class="nav-item">
          <a href="pages/expenses.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-minus-circle text-danger"></i>
            <p>ຈັດການລາຍຈ່າຍ</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="pages/expense_categories.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-tags text-danger"></i>
            <p>ປະເພດລາຍຈ່າຍ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ຫົວຂໍ້: ຂາຍສິນຄ້າ & ສາງ -->
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ຂາຍສິນຄ້າ & ສາງ</li>

        <!-- ເມນູ: ຂາຍສິນຄ້າ (POS) -->
        <?php if (hasPermission('sales', 'view')): ?>
        <li class="nav-item">
          <a href="pages/sales.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-cash-register text-success"></i>
            <p>ຂາຍສິນຄ້າ (POS)</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ປະຫວັດການຂາຍ -->
        <?php if (hasPermission('sales_history', 'view')): ?>
        <li class="nav-item">
          <a href="pages/sales_history.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-history text-info"></i>
            <p>ປະຫວັດການຂາຍ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ນຳເຂົ້າສິນຄ້າ -->
        <?php if (hasPermission('stock_in', 'view')): ?>
        <li class="nav-item">
          <a href="pages/stock_in.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-file-import text-warning"></i>
            <p>ນຳເຂົ້າສິນຄ້າ</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="pages/stock_in_history.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-file-invoice text-success"></i>
            <p>ປະຫວັດການນຳເຂົ້າ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ຈັດການຂໍ້ມູນສິນຄ້າ -->
        <?php if (hasPermission('products', 'view')): ?>
        <li class="nav-item">
          <a href="pages/products.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-box"></i>
            <p>ຂໍ້ມູນສິນຄ້າ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ຈັດການປະເພດສິນຄ້າ -->
        <?php if (hasPermission('product_categories', 'view')): ?>
        <li class="nav-item">
          <a href="pages/product_categories.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-folder"></i>
            <p>ປະເພດສິນຄ້າ</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ຫົວຂໍ້: ລາຍງານຂໍ້ມູນ -->
        <?php if (hasPermission('subscriptions', 'view') || hasPermission('members', 'view') || hasPermission('equipment', 'view')): ?>
        <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; letter-spacing: 1.5px; padding-top: 20px;">ລາຍງານຂໍ້ມູນ</li>

        <!-- ເມນູ: ລາຍງານການເງິນ (dropdown) -->
        <?php if (hasPermission('subscriptions', 'view') || hasPermission('daily_checkin', 'view') || hasPermission('sales', 'view') || hasPermission('stock_in', 'view') || hasPermission('expenses', 'view')): ?>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-line text-primary"></i>
            <p>
              ລາຍງານການເງິນ
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <!-- ພາບລວມການເງິນ -->
            <?php if (hasPermission('subscriptions', 'view') && hasPermission('daily_checkin', 'view') && hasPermission('sales', 'view') && hasPermission('stock_in', 'view') && hasPermission('expenses', 'view')): ?>
            <li class="nav-item">
              <a href="pages/revenue_report.php?tab=overview" target="frame" class="nav-link">
                <i class="nav-icon fas fa-columns text-primary"></i>
                <p>ພາບລວມການເງິນ</p>
              </a>
            </li>
            <?php endif; ?>
            <!-- ຄ່າສະໝັກສະມາຊິກ -->
            <?php if (hasPermission('subscriptions', 'view')): ?>
            <li class="nav-item">
              <a href="pages/revenue_report.php?tab=subscription" target="frame" class="nav-link">
                <i class="nav-icon fas fa-file-invoice-dollar text-warning"></i>
                <p>ຄ່າສະໝັກສະມາຊິກ</p>
              </a>
            </li>
            <?php endif; ?>
            <!-- ຄ່າລາຍວັນ -->
            <?php if (hasPermission('daily_checkin', 'view')): ?>
            <li class="nav-item">
              <a href="pages/revenue_report.php?tab=daily" target="frame" class="nav-link">
                <i class="nav-icon fas fa-user-clock text-info"></i>
                <p>ຄ່າລາຍວັນລູກຄ້າ</p>
              </a>
            </li>
            <?php endif; ?>
            <!-- ຍອດຂາຍ POS -->
            <?php if (hasPermission('sales', 'view')): ?>
            <li class="nav-item">
              <a href="pages/revenue_report.php?tab=pos" target="frame" class="nav-link">
                <i class="nav-icon fas fa-cash-register text-success"></i>
                <p>ຍອດຂາຍ POS</p>
              </a>
            </li>
            <?php endif; ?>
            <!-- ຕົ້ນທຶນນຳເຂົ້າສິນຄ້າ -->
            <?php if (hasPermission('stock_in', 'view')): ?>
            <li class="nav-item">
              <a href="pages/revenue_report.php?tab=stock_in" target="frame" class="nav-link">
                <i class="nav-icon fas fa-file-import text-secondary"></i>
                <p>ຕົ້ນທຶນນຳເຂົ້າສິນຄ້າ</p>
              </a>
            </li>
            <?php endif; ?>
            <!-- ລາຍຈ່າຍທົ່ວໄປ -->
            <?php if (hasPermission('expenses', 'view')): ?>
            <li class="nav-item">
              <a href="pages/revenue_report.php?tab=expense" target="frame" class="nav-link">
                <i class="nav-icon fas fa-minus-circle text-danger"></i>
                <p>ລາຍຈ່າຍທົ່ວໄປ</p>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ລາຍງານສະມາຊິກຂາດການຕິດຕໍ່ -->
        <?php if (hasPermission('members', 'view')): ?>
        <li class="nav-item">
          <a href="pages/inactive_members_report.php" target="frame" class="nav-link">
            <i class="nav-icon fas fa-user-slash" style="color:#ff9f43;"></i>
            <p>ສະມາຊິກຂາດການຕິດຕໍ່</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- ເມນູ: ລາຍງານອຸປະກອນ (dropdown) -->
        <?php if (hasPermission('equipment', 'view')): ?>
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
        <?php endif; ?>
        <?php endif; ?>

      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
