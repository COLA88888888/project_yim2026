<?php
if (!isset($base_path)) {
    $base_path = '';
}
?>
<!DOCTYPE html>
<html lang="lo">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ (Gym System)</title>
    
    <!-- Local Font: Noto Sans Lao Looped (Offline) -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css?v=<?php echo time(); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/fontawesome-free/css/all.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/daterangepicker/daterangepicker.css">

    <style>
      body { font-family: 'Noto Sans Lao', 'Noto Sans Lao Looped', sans-serif; }
      * { font-family: 'Noto Sans Lao', 'Noto Sans Lao Looped', sans-serif; }
      
      /* Mobile header alignment override - force left alignment of card titles */
      @media (max-width: 767.98px) {
          .report-page .card .p-3.border-bottom > div:first-child,
          .report-page .card .p-3.border-bottom > div:last-child {
              width: 100% !important;
              display: block !important;
              text-align: left !important;
              justify-content: flex-start !important;
          }
      }


    </style>
  </head>
  <body class="hold-transition sidebar-mini layout-fixed">