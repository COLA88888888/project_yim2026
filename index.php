<?php
session_start();
if (isset($_SESSION['checked'])) {
    header("Location: menu_admin.php");
    exit();
}
require_once 'config/db.php';
$bct_logo = 'assets/img/logo/gym_logo.png?v=' . time();
$bct_bg = 'assets/img/logo/gym_bg.png?v=' . time();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ (Gym & Fitness System)</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">

    <link rel="shortcut icon" href="<?php echo $bct_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/pages/login.css?v=<?php echo time(); ?>">
    <style>
        .hero-bg {
            background: url('<?php echo $bct_bg; ?>') !important;
            background-size: cover !important;
            background-position: center !important;
        }
    </style>
</head>
<body>

    <div class="hero-bg"></div>
    <div class="hero-bg-accent"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="hotel-logo-wrapper">
                <img src="<?php echo $bct_logo; ?>" alt="Logo">
            </div>
            <div class="login-header">
                <h3>ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ <br>
                    <h5>Gym & Fitness Management System</h5>
                </h3>
            </div>

            <form id="loginForm">
                <div class="form-group mb-3">
                    <label class="form-label">ຊື່ຜູ້ໃຊ້:</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888;">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="username" class="form-control" placeholder="ປ້ອນຊື່ຜູ້ໃຊ້ງານ..." autofocus style="padding-left: 45px;">
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label">ລະຫັດຜ່ານ:</label>
                    <div class="password-container">
                        <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; z-index: 5;">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" class="form-control" placeholder="ປ້ອນລະຫັດຜ່ານ..." style="padding-left: 45px; padding-right: 45px;">
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
               
                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="fas fa-user-check mr-2"></i> ເຂົ້າສູ່ລະບົບ
                </button>
            </form>
        </div>
    </div>

    <div class="footer-info">
         ລະບົບບໍລິຫານຈັດການຍິມ & ຟິດເນັດ<br>
         &copy; 2026 ພັດທະນາໂດຍ: ທ. ໄຊຍາ ຈັນທະສອນ ນັກສຶກສາ ປີ 3 ວິທະຍາໄລ ບີຊີທີ
    </div>

    <!-- Use local jQuery for offline compatibility -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="sweetalert/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            console.log("Login page ready"); // Debug log

            // Check if session/token has expired
            const urlParams = new URLSearchParams(window.location.search);
            /*
            if (urlParams.has('expired')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Token ໝົດອາຍຸແລ້ວ',
                    text: 'ກະລຸນາເຂົ້າສູ່ລະບົບໃໝ່',
                    confirmButtonColor: '#007bff',
                    confirmButtonText: 'ຕົກລົງ'
                });
            }
            */

            // Toggle Password
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $('#eyeIcon').toggleClass('fa-eye fa-eye-slash');
            });


            // ເຫດການເມື່ອມີການກົດປຸ່ມ Submit (ສົ່ງຟອມ) ເຂົ້າສູ່ລະບົບ
            $('#loginForm').on('submit', function(e) {
                e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບແບບປົກກະຕິຂອງຟອມ
                
                // ດຶງຄ່າຈາກຊ່ອງປ້ອນຂໍ້ມູນ Username ແລະ Password
                var username = $('#username').val();
                var password = $('#password').val();
                var btn = $('#btnLogin');

                // ກວດສອບຖ້າບໍ່ໄດ້ປ້ອນ Username ຫຼື Password
                if (!username || !password) {
                    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຊື່ຜູ້ໃ້ຊ້ ແລະ ລະຫັດຜ່ານ' });
                    return;
                }

                // ປ່ຽນປຸ່ມເຂົ້າສູ່ລະບົບເປັນສະຖານະກຳລັງໂຫຼດ ແລະ ບໍ່ໃຫ້ກົດຊໍ້າ
                btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i> ກຳລັງກວດສອບ...');

                // ສົ່ງຂໍ້ມູນໄປກວດສອບທີ່ server ດ້ວຍເທັກໂນໂລຢີ AJAX (ແບບບໍ່ຕ້ອງ reload ໜ້າເວັບ)
                $.ajax({
                    url: 'Check_user.php', // ໄຟລ໌ backend ທີ່ໃຊ້ກວດສອບ
                    type: 'POST', // ວິທີການສົ່ງຂໍ້ມູນແບບ POST (ປອດໄພກວ່າ GET)
                    data: { username: username, password: password }, // ຂໍ້ມູນທີ່ສົ່ງໄປ
                    dataType: 'json', // ປະເພດຂໍ້ມູນທີ່ຕອບກັບມາ (JSON format)
                    success: function(response) {
                        console.log("Login Response:", response); // Debug log
                        if (response.success) {
                            // ຖ້າກວດສອບຜ່ານ, ໃຫ້ສົ່ງຜູ້ໃຊ້ໄປຫາໜ້າທີ່ໄດ້ຮັບຈາກ server (menu_admin.php ຫຼື menu_user.php)
                            window.location.href = response.redirect;
                        } else {
                            // ຖ້າລະຫັດຜິດ ຫຼື ບໍ່ພົບຜູ້ໃຊ້, ໃຫ້ເປີດປຸ່ມໃຫ້ກົດໄດ້ຄືນ ແລະ ສະແດງຂໍ້ຄວາມຜິດພາດດ້ວຍ SweetAlert
                            btn.prop('disabled', false).text('ເຂົ້າສູ່ລະບົບ');
                            Swal.fire({
                                icon: 'error',
                                title: 'ຜິດພາດ',
                                text: response.message,
                                confirmButtonColor: '#007bff'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // ກໍລະນີເຊື່ອມຕໍ່ຖານຂໍ້ມູນ ຫຼື ເຊີເວີຂັດຂ້ອງ (Error 500, 404, etc.)
                        console.error("AJAX Error:", status, error); // Debug log
                        btn.prop('disabled', false).text('ເຂົ້າສູ່ລະບົບ');
                        var errMsg = 'ມີຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ກັບເຊີເວີ (HTTP status: ' + xhr.status + ')';
                        if (xhr.responseText) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.message) {
                                    errMsg = res.message;
                                }
                            } catch(e) {}
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'ຜິດພາດ',
                            text: errMsg,
                            confirmButtonColor: '#007bff'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
