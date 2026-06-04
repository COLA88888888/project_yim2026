<?php

function dashboardScalar($conn, $sql)
{
    if (!$conn) {
        return 0;
    }
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_row($result);
    return $row ? ($row[0] ?? 0) : 0;
}

function getDashboardQuickStats($conn)
{
    $stats = [
        'total_members' => 0,
        'active_members' => 0,
        'checkins_today' => 0,
        'total_packages' => 0,
        'revenue_today' => 0,
        'revenue_month' => 0,
        'sub_revenue_today' => 0,
        'daily_revenue_today' => 0,
        'sub_revenue_month' => 0,
        'daily_revenue_month' => 0,
        'daily_checkins_today' => 0,
        'total_equipment' => 0,
        'good_equipment' => 0,
        'broken_equipment' => 0,
        'total_users' => 0,
    ];

    if (!$conn) {
        return $stats;
    }

    // 1. ຈຳນວນສະມາຊິກທັງໝົດ
    $stats['total_members'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM members');
    
    // 2. ຈຳນວນສະມາຊິກທີ່ຍັງເຄື່ອນໄຫວ (Active)
    $stats['active_members'] = (int) dashboardScalar($conn, "SELECT COUNT(*) FROM members WHERE status = 'Active'");
    
    // 3. ຈຳນວນຄົນເຊັກອິນມື້ນີ້
    $stats['checkins_today'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM checkins WHERE DATE(checkin_time) = CURDATE()');
    
    // 4. ຈຳນວນແພັກເກດທັງໝົດ
    $stats['total_packages'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM packages');
    
    // 5. ລາຍຮັບມື້ນີ້ ແຍກຕາມປະເພດ
    $sub_today = (float) dashboardScalar($conn, 'SELECT COALESCE(SUM(price_paid), 0) FROM memberships WHERE DATE(created_at) = CURDATE()');
    $daily_today = (float) dashboardScalar($conn, 'SELECT COALESCE(SUM(price_paid), 0) FROM daily_checkins WHERE checkin_date = CURDATE()');
    $stats['sub_revenue_today'] = $sub_today;
    $stats['daily_revenue_today'] = $daily_today;
    $stats['revenue_today'] = $sub_today + $daily_today;
    
    // 5.1 ລາຍຮັບມື້ນີ້ ແຍກຕາມຊ່ອງທາງ (ເງິນສົດ vs ເງິນໂອນ)
    $stats['sub_today_cash'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM memberships WHERE DATE(created_at) = CURDATE() AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    $stats['sub_today_transfer'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM memberships WHERE DATE(created_at) = CURDATE() AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    $stats['daily_today_cash'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM daily_checkins WHERE checkin_date = CURDATE() AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    $stats['daily_today_transfer'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM daily_checkins WHERE checkin_date = CURDATE() AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    
    // 6. ລາຍຮັບເດືອນນີ້ ແຍກຕາມປະເພດ
    $sub_month = (float) dashboardScalar($conn, 'SELECT COALESCE(SUM(price_paid), 0) FROM memberships WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())');
    $daily_month = (float) dashboardScalar($conn, 'SELECT COALESCE(SUM(price_paid), 0) FROM daily_checkins WHERE MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE())');
    $stats['sub_revenue_month'] = $sub_month;
    $stats['daily_revenue_month'] = $daily_month;
    $stats['revenue_month'] = $sub_month + $daily_month;

    // 6.2 ລາຍຮັບເດືອນນີ້ ແຍກຕາມຊ່ອງທາງ (ເງິນສົດ vs ເງິນໂອນ)
    $stats['sub_month_cash'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM memberships WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    $stats['sub_month_transfer'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM memberships WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    $stats['daily_month_cash'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM daily_checkins WHERE MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE()) AND (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");
    $stats['daily_month_transfer'] = (float) dashboardScalar($conn, "SELECT COALESCE(SUM(price_paid), 0) FROM daily_checkins WHERE MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE()) AND NOT (payment_method LIKE '%ສົດ%' OR payment_method = 'Cash' OR payment_method = 'cash')");

    // 6.1 ຈຳນວນລູກຄ້າລາຍວັນວັນນີ້
    $stats['daily_checkins_today'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM daily_checkins WHERE checkin_date = CURDATE()');
    
    // 7. ຈຳນວນເຄື່ອງອອກກຳລັງກາຍທັງໝົດ
    $stats['total_equipment'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM equipment');
    
    // 8. ເຄື່ອງສະພາບດີ
    $stats['good_equipment'] = (int) dashboardScalar($conn, "SELECT COUNT(*) FROM equipment WHERE status = 'ດີ'");
    
    // 9. ເຄື່ອງສະພາບເພ
    $stats['broken_equipment'] = (int) dashboardScalar($conn, "SELECT COUNT(*) FROM equipment WHERE status = 'ເພ'");
    
    // 10. ພະນັກງານທັງໝົດ
    $stats['total_users'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM users');

    return $stats;
}
?>
