<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

// ດຶງປະຫວັດການເຊັກອິນມື້ນີ້
$checkins = [];
$today_sql = "SELECT c.*, m.fname, m.lname, m.member_code, m.profile_img, p.package_name 
              FROM checkins c 
              LEFT JOIN members m ON c.member_id = m.member_id 
              LEFT JOIN memberships ms ON m.member_id = ms.member_id AND ms.status = 'Active' 
              LEFT JOIN packages p ON ms.package_id = p.package_id 
              WHERE DATE(c.checkin_time) = CURDATE() 
              ORDER BY c.checkin_time DESC";
$result = mysqli_query($conn, $today_sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $checkins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຊັກອິນເຂົ້າໃຊ້ບໍລິການ</title>
    <!-- Google Fonts - Noto Sans Lao Looped -->
    <link rel="stylesheet" href="../assets/css/local-font.css">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    
    <style>
        body {
            font-family: 'Noto Sans Lao Looped', sans-serif;
            background-color: #f4f6f9;
        }
        .checkin-container {
            max-width: 100%;
            margin: 0 auto;
        }
        .placeholder-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 2px dashed #ccd3e0;
            padding: 40px 15px;
            text-align: center;
            color: #999;
            transition: all 0.3s ease;
        }
        .placeholder-card i {
            font-size: 3rem;
            color: #bdc6d4;
            margin-bottom: 12px;
            display: inline-block;
            animation: pulse-scan 2s infinite;
        }
        @keyframes pulse-scan {
            0% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.08); color: #244886; }
            100% { opacity: 0.6; transform: scale(1); }
        }
        .member-card-preview {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
            display: none;
        }
        .member-card-header {
            background: linear-gradient(135deg, #244886 0%, #3a6fb3 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        .member-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.4);
            background-color: #eee;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.95rem;
            padding: 6px 16px;
            border-radius: 30px;
            font-weight: 700;
        }
        .today-list-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: none;
        }
        .search-large {
            height: 42px;
            font-size: 0.95rem;
            border-radius: 21px 0 0 21px !important;
            padding-left: 15px;
            border: 2px solid #ddd;
            border-right: none;
        }
        .search-large:focus {
            border-color: #244886;
            outline: none;
            box-shadow: none;
        }
        .btn-search-large {
            border-radius: 0 21px 21px 0 !important;
            padding-left: 15px;
            padding-right: 15px;
            font-weight: 600;
            height: 42px;
            background-color: #244886;
            border: 2px solid #244886;
            font-size: 0.95rem;
        }
        .btn-search-large:hover {
            background-color: #1b3664;
            border-color: #1b3664;
        }
        .checkin-btn {
            height: 42px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 21px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4 checkin-container">
    <!-- Header -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-id-card text-success me-2"></i> ເຊັກອິນເຂົ້າໃຊ້ບໍລິການ
        </h4>
        <p class="text-muted small mb-0">ປ້ອນລະຫັດບັດ ຫຼື ສະແກນບາໂຄດຂອງສະມາຊິກເພື່ອເຊັກອິນ ແລະ ກວດສອບອາຍຸແພັກເກດ</p>
    </div>

    <div class="row">
        <!-- Left Side: Search & Preview (col-lg-4 col-md-5) -->
        <div class="col-lg-4 col-md-5 mb-4">
            <!-- 1. Search Box Card -->
            <div class="card card-body p-3 mb-3 today-list-card">
                <h5 class="fw-bold mb-2" style="font-size: 1.1rem;"><i class="fas fa-search text-primary me-2"></i> ຄົ້ນຫາສະມາຊິກເພື່ອເຊັກອິນ</h5>
                <form id="verifyForm">
                    <div class="input-group">
                        <input type="text" id="searchVal" class="form-control search-large" placeholder="ປ້ອນລະຫັດ ຫຼື ເບີໂທ..." autofocus autocomplete="off">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary btn-search-large"><i class="fas fa-search me-1"></i> ກວດສອບ</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- 2. Member Card Preview -->
            <!-- Placeholder Card when no card scanned -->
            <div class="placeholder-card mb-3" id="placeholderCard">
                <i class="fas fa-qrcode"></i>
                <h5 class="fw-bold mb-1 text-dark">ລໍຖ້າການສະແກນບັດ</h5>
                <p class="text-muted small mb-0">ປ້ອນລະຫັດບັດ ຫຼື ສະແກນບາໂຄດເພື່ອເລີ່ມຕົ້ນເຊັກອິນ</p>
            </div>
            
            <div class="card member-card-preview" id="memberCard" style="width: 100%;">
                <div class="member-card-header text-center">
                    <img src="../assets/img/members/default.png" id="cardAvatar" class="member-avatar mb-2" alt="Avatar">
                    <h4 class="fw-bold mb-1 text-white" id="cardName">ຊື່ ນາມສະກຸນ</h4>
                    <span class="badge bg-light text-dark mb-0" id="cardCode">GYM000000</span>
                </div>
                <div class="card-body p-3">
                    <div class="row text-center mb-3">
                        <div class="col-6 border-end">
                            <small class="text-muted d-block">ເພດ</small>
                            <span class="fw-bold text-dark" id="cardGender" style="font-size: 1.1rem;">ຊາຍ</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">ເບີໂທລະສັບ</small>
                            <span class="fw-bold text-dark" id="cardTel" style="font-size: 1rem;">020 99999999</span>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="fas fa-box me-1"></i> ແພັກເກດປັດຈຸບັນ:</span>
                            <span class="fw-bold text-primary" id="cardPackage">ລາຍເດືອນ</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="fas fa-calendar-alt me-1"></i> ວັນໝົດອາຍຸ:</span>
                            <span class="fw-bold text-dark" id="cardEndDate">31/12/2026</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small"><i class="fas fa-hourglass-half me-1"></i> ມື້ທີ່ເຫຼືອ:</span>
                            <span class="fw-bold text-info" id="cardRemaining">ເຫຼືອ 30 ມື້</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> ສະຖານະ:</span>
                            <span class="status-badge bg-success text-white py-1 px-3" id="cardStatus" style="font-size: 0.9rem;">Active / ປົກກະຕິ</span>
                        </div>
                    </div>
                    
                    <div id="checkinActionDiv" class="mt-3">
                        <!-- Dynamic Button based on status -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Today's Check-in List (col-lg-8 col-md-7) -->
        <div class="col-lg-8 col-md-7 mb-4">
            <div class="card card-custom">
                <div class="card-body p-0">
                    <!-- Search & Control Header -->
                    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-list-ul text-success me-2"></i> ປະຫວັດການເຊັກອິນມື້ນີ້</h5>
                            <span class="badge bg-success ms-2" id="todayCount"><?= count($checkins) ?> ຄັ້ງ</span>
                            <div class="d-flex align-items-center gap-2 ms-2">
                                <span class="text-muted small">ສະແດງ:</span>
                                <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 75px; border-radius: 8px; font-weight: bold; height: 30px; padding: 2px 6px;">
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                    <option value="50">50</option>
                                    <option value="all">ທັງໝົດ</option>
                                </select>
                            </div>
                        </div>
                        <!-- Table Search Filter Input -->
                        <div class="search-box" style="max-width: 250px; position: relative; width: 100%;">
                            <input type="text" id="tableSearchInput" class="form-control form-control-sm" placeholder="ຄົ້ນຫາໃນປະຫວັດ..." style="border-radius: 20px; padding-left: 30px; border: 1.5px solid #ddd;">
                            <i class="fas fa-search text-muted" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.85rem;"></i>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 80px;">ຮູບ</th>
                                    <th>ສະມາຊິກ</th>
                                    <th class="text-center">ລະຫັດບັດ</th>
                                    <th>ແພັກເກດ</th>
                                    <th class="text-center">ວັນ-ເວລາ ເຊັກອິນ</th>
                                </tr>
                            </thead>
                            <tbody id="todayCheckinBody">
                                <?php if (empty($checkins)): ?>
                                    <tr id="emptyCheckinRow">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-user-clock fa-2x mb-3 d-block text-secondary"></i>
                                            ຍັງບໍ່ມີການເຊັກອິນເຂົ້າໃຊ້ໃນມື້ນີ້
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($checkins as $c): 
                                        $img_path = '../assets/img/members/' . ($c['profile_img'] ?: 'default.png');
                                        if (!file_exists(__DIR__ . '/../' . $img_path) || empty($c['profile_img'])) {
                                            $img_path = '../assets/img/members/default.png';
                                        }
                                    ?>
                                        <tr class="checkin-row">
                                            <td class="text-center">
                                                <img src="<?= htmlspecialchars($img_path) ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd;">
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($c['fname'] . ' ' . $c['lname']) ?></td>
                                            <td class="text-center"><code><?= htmlspecialchars($c['member_code']) ?></code></td>
                                            <td><span class="badge bg-light text-primary border" style="font-size: 0.9rem; padding: 6px 12px;"><?= htmlspecialchars($c['package_name'] ?: 'ບໍ່ລະບຸ') ?></span></td>
                                            <td class="text-center">
                                                <span class="d-block fw-bold text-dark" style="font-size:0.9rem;"><?= date('d/m/Y', strtotime($c['checkin_time'])) ?></span>
                                                <span class="text-muted" style="font-size:0.85rem;"><i class="fas fa-clock fa-xs me-1"></i><?= date('H:i:s', strtotime($c['checkin_time'])) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Pagination Footer -->
                <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <div class="text-muted small" id="paginationInfo">
                        ສະແດງ 1-10 ຈາກທັງໝົດ 10 ຄັ້ງ
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Web Audio API offline synth sound function
function playBeep(type) {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        if (type === 'success') {
            // Success sound: Two clean short high beeps
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.25);
        } else {
            // Error sound: Low buzz tone
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(120, ctx.currentTime);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.4);
        }
    } catch (e) {
        console.log("Audio not supported or interaction needed");
    }
}

$(document).ready(function() {
    let currentMember = null;
    let autoHideTimer = null;

    function showPlaceholder() {
        $('#memberCard').fadeOut(300, function() {
            $('#placeholderCard').fadeIn(300);
        });
    }

    function showMemberCard() {
        if (autoHideTimer) {
            clearTimeout(autoHideTimer);
            autoHideTimer = null;
        }
        $('#placeholderCard').hide();
        $('#memberCard').fadeIn(300);
    }

    // ============ ການສົ່ງຟອມເພື່ອເຊັກອິນສະມາຊິກ (Verify Member Form) ============
    $('#verifyForm').on('submit', function(e) {
        e.preventDefault(); // ຢຸດການ reload ໜ້າເວັບ
        
        // ກວດສອບຄວາມຖືກຕ້ອງຂອງຂໍ້ມູນປ້ອນເຂົ້າ (ໃຊ້ SweetAlert ແທນ required ຂອງ HTML5 ບຣາວເຊີ)
        let search = $('#searchVal').val().trim();
        if (search === '') {
            Swal.fire({
                icon: 'warning',
                title: 'ກະລຸນາປ້ອນຂໍ້ມູນ',
                text: 'ກະລຸນາປ້ອນລະຫັດບັດ ຫຼື ເບີໂທລະສັບກ່ອນ',
                confirmButtonColor: '#007bff',
                confirmButtonText: 'ຕົກລົງ'
            });
            return;
        }

        if (autoHideTimer) {
            clearTimeout(autoHideTimer);
            autoHideTimer = null;
        }

        $.ajax({
            url: '../api/checkin_api.php',
            type: 'GET',
            data: { action: 'verify', search: search },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    currentMember = res.member;
                    let sub = res.subscription;

                    // Update UI Card
                    let imgName = currentMember.profile_img || 'default.png';
                    $('#cardAvatar').attr('src', '../assets/img/members/' + imgName);
                    $('#cardName').text(currentMember.fname + ' ' + currentMember.lname);
                    $('#cardCode').text(currentMember.member_code);
                    $('#cardGender').text(currentMember.gender);
                    $('#cardTel').text(currentMember.tel);
                    
                    if (sub) {
                        $('#cardPackage').text(sub.package_name);
                        $('#cardEndDate').text(sub.end_date);
                        $('#cardRemaining').text('ເຫຼືອ ' + res.remaining_days + ' ມື້');
                    } else {
                        $('#cardPackage').text('ບໍ່ມີແພັກເກດ');
                        $('#cardEndDate').text('-');
                        $('#cardRemaining').text('0 ມື້');
                    }

                    // Update Status Badge
                    $('#cardStatus')
                        .removeClass('bg-success bg-danger bg-warning')
                        .addClass('bg-' + res.status_color)
                        .text(res.status_msg);

                    // Update Action Button
                    let actionHtml = '';
                    if (res.is_active) {
                        if (res.checked_in_today) {
                            actionHtml = `<div class="alert alert-warning text-center fw-bold mb-0 small"><i class="fas fa-check-double me-1"></i> ເຊັກອິນເຂົ້າໃຊ້ແລ້ວມື້ນີ້</div>
                                          <button class="btn btn-success checkin-btn w-100 mt-2" onclick="performCheckin(${currentMember.member_id})"><i class="fas fa-sign-in-alt me-1"></i> ເຊັກອິນອີກຄັ້ງ</button>`;
                        } else {
                            actionHtml = `<button class="btn btn-success checkin-btn w-100" onclick="performCheckin(${currentMember.member_id})"><i class="fas fa-sign-in-alt me-1"></i> ກົດເຊັກອິນເຂົ້າໃຊ້ງານ</button>`;
                        }
                    } else {
                        playBeep('error');
                        actionHtml = `<div class="alert alert-danger text-center fw-bold mb-0 small"><i class="fas fa-exclamation-circle me-1"></i> ແພັກເກດໝົດອາຍຸ ຫຼື ບໍ່ພົບຂໍ້ມູນການສະໝັກ</div>`;
                    }
                    $('#checkinActionDiv').html(actionHtml);
                    
                    // Display Card
                    showMemberCard();

                    // If active and not checked in today, auto checkin for fast scan!
                    if (res.is_active && !res.checked_in_today) {
                        performCheckin(currentMember.member_id);
                    }
                }
            },
            error: function(xhr) {
                playBeep('error');
                let msg = 'ເກີດຂໍ້ຜິດພາດໃນການກວດສອບ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'ຜິດພາດ',
                    text: msg,
                    confirmButtonColor: '#007bff'
                });
                showPlaceholder();
            }
        });
    });

    // Pagination & Search in JavaScript
    var itemsPerPage = 10;
    var currentPage = 1;
    var filteredRows = [];

    $('#pageSizeSelect').on('change', function() {
        var val = $(this).val();
        if (val === 'all') {
            itemsPerPage = 999999;
        } else {
            itemsPerPage = parseInt(val);
        }
        showPage(1);
    });

    function updateFilteredRows() {
        var query = $('#tableSearchInput').val().toLowerCase().trim();
        filteredRows = [];
        
        $('.checkin-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                filteredRows.push(this);
            } else {
                $(this).hide();
            }
        });
        
        $('#todayCount').text(filteredRows.length + ' ຄັ້ງ');
        
        if (filteredRows.length === 0 && $('.checkin-row').length > 0) {
            if ($('#emptySearchResult').length === 0) {
                $('#todayCheckinBody').append(
                    `<tr id="emptySearchResult"><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>ບໍ່ພົບຂໍ້ມູນການເຊັກອິນ</td></tr>`
                );
            }
        } else {
            $('#emptySearchResult').remove();
        }
    }

    function showPage(page) {
        currentPage = page;
        var totalItems = filteredRows.length;
        
        if (totalItems === 0) {
            $('.checkin-row').hide();
            $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ຄັ້ງ');
            $('#paginationControls').html('');
            return;
        }
        
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        
        $('.checkin-row').hide();
        for (var i = startIndex; i < endIndex; i++) {
            $(filteredRows[i]).show();
        }
        
        $('#paginationInfo').text('ສະແດງ ' + (startIndex + 1) + ' ຫາ ' + endIndex + ' ຈາກທັງໝົດ ' + totalItems + ' ຄັ້ງ');
        
        renderControls(totalPages);
    }

    function renderControls(totalPages) {
        var controlsHtml = '';
        if (currentPage === 1) {
            controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-left"></i></a></li>`;
        } else {
            controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a></li>`;
        }
        
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (var p = startPage; p <= endPage; p++) {
            if (p === currentPage) {
                controlsHtml += `<li class="page-item active"><a class="page-link" href="javascript:void(0)">${p}</a></li>`;
            } else {
                controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${p}">${p}</a></li>`;
            }
        }
        
        if (currentPage === totalPages) {
            controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-right"></i></a></li>`;
        } else {
            controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a></li>`;
        }
        
        $('#paginationControls').html(controlsHtml);
        
        $('#paginationControls a[data-page]').off('click').on('click', function(e) {
            e.preventDefault();
            showPage(parseInt($(this).data('page')));
        });
    }

    $('#tableSearchInput').on('input', function() {
        updateFilteredRows();
        showPage(1);
    });

    // Run pagination
    updateFilteredRows();
    showPage(1);

    // Make functions globally accessible

    window.performCheckin = function(memberId) {
        if (!memberId) return;
        
        $.ajax({
            url: '../api/checkin_api.php',
            type: 'POST',
            data: { action: 'checkin', member_id: memberId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    playBeep('success');
                    
                    // Show a non-blocking toast alert instead of a hard modal
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'ເຊັກອິນສຳເລັດ: ' + res.member_name
                    });
                    
                    // Clear input search
                    $('#searchVal').val('').focus();
                    
                    // Set auto-hide timer to return to placeholder after 5 seconds
                    autoHideTimer = setTimeout(showPlaceholder, 5000);
                    
                    // Reload checklist table
                    reloadTodayCheckins();
                }
            },
            error: function(xhr) {
                playBeep('error');
                let msg = 'ເຊັກອິນບໍ່ສຳເລັດ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'ຜິດພາດ',
                    text: msg,
                    confirmButtonColor: '#007bff'
                });
                showPlaceholder();
            }
        });
    };

    window.reloadTodayCheckins = function() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            success: function(html) {
                // Parse newly loaded list
                let newTableBody = $(html).find('#todayCheckinBody').html();
                
                $('#todayCheckinBody').html(newTableBody);
                
                // Re-initialize pagination with new rows!
                updateFilteredRows();
                showPage(1);
            }
        });
    };
});
</script>
</body>
</html>
