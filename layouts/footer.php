<?php
if (!isset($base_path)) {
    $base_path = '';
}
?>
    <script src="<?php echo $base_path; ?>plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo $base_path; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_path; ?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <script src="<?php echo $base_path; ?>dist/js/adminlte.js"></script>
    <style>
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
    
  </body>
</html>
