<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}
require_once '../config/db.php';

if (!hasPermission('expenses', 'view')) {
    echo "<script>window.top.location.href = '../index.php?expired=1';</script>";
    exit();
}

// Calculate top summary statistics for general expenses
$exp_today = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(amount) FROM expenses WHERE expense_date = CURDATE()"))[0] ?? 0;
$exp_month = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())"))[0] ?? 0;
$exp_all = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(amount) FROM expenses"))[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການລາຍຈ່າຍທົ່ວໄປ</title>
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../icon/css/all.min.css">
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/pages/users-manage.css">
    
    <style>
        body {
            font-family: 'Noto Sans Lao', 'Noto Sans Lao Looped', sans-serif;
            background-color: #f4f6f9;
        }
        .stat-card-exp {
            border-radius: 16px;
            border: none;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            padding: 14px 18px !important;
        }
        .stat-card-exp:hover {
            transform: translateY(-4px);
        }
        .stat-card-exp h3 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 2px;
            margin-top: 2px;
            letter-spacing: -0.5px;
        }
        .stat-card-exp small {
            font-size: 0.8rem;
            font-weight: 500;
            opacity: 0.85;
        }
        .stat-card-icon-right {
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.1);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .stat-card-exp:hover .stat-card-icon-right {
            color: rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.18);
            transform: scale(1.08) rotate(5deg);
        }
        .stat-card-exp * {
            position: relative;
            z-index: 2;
        }
        .row-tight {
            display: flex;
            flex-wrap: wrap;
            margin-right: -8px !important;
            margin-left: -8px !important;
        }
        .row-tight > [class*='col-'] {
            padding-right: 8px !important;
            padding-left: 8px !important;
            margin-bottom: 16px !important;
        }
        .btn-action {
            border-radius: 8px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-minus-circle text-danger me-2"></i> ຈັດການລາຍຈ່າຍທົ່ວໄປ
            </h4>
            <p class="text-muted small mb-0">ບັນທຶກ ແລະ ຄຸ້ມຄອງລາຍຈ່າຍຕ່າງໆພາຍໃນຍິມ (ຄ່ານ້ຳ/ໄຟ, ຄ່າເຊົ່າ, ເງິນເດືອນ, ແລະ ອື່ນໆ)</p>
        </div>
        <div>
            <?php if (hasPermission('expenses', 'add')): ?>
            <button class="btn btn-danger rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i> ບັນທຶກລາຍຈ່າຍໃໝ່
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row-tight mb-2">
        <div class="col-md-4">
            <div class="card stat-card-exp bg-gradient" style="background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); box-shadow: 0 8px 20px rgba(255, 88, 88, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍມື້ນີ້</small>
                        <h3 id="stat-today"><?= formatCurrency($exp_today) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-day mr-1"></i> ປະຈຳວັນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card-exp bg-gradient" style="background: linear-gradient(135deg, #e65c00 0%, #F9D423 100%); box-shadow: 0 8px 20px rgba(230, 92, 0, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍເດືອນນີ້</small>
                        <h3 id="stat-month"><?= formatCurrency($exp_month) ?></h3>
                        <small class="text-white-50"><i class="fas fa-calendar-alt mr-1"></i> ປະຈຳເດືອນ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card-exp bg-gradient" style="background: linear-gradient(135deg, #1f4037 0%, #99f2c8 100%); box-shadow: 0 8px 20px rgba(31, 64, 55, 0.15);">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <small class="text-white-50 font-weight-bold">ລາຍຈ່າຍທັງໝົດ</small>
                        <h3 id="stat-all"><?= formatCurrency($exp_all) ?></h3>
                        <small class="text-white-50"><i class="fas fa-wallet mr-1"></i> ລວມສະສົມ</small>
                    </div>
                    <div class="stat-card-icon-right">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Control Card -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <div class="row align-items-end g-2">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold small">ເລີ່ມແຕ່ວັນທີ</label>
                    <input type="date" id="filterStartDate" class="form-control">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold small">ຫາວັນທີ</label>
                    <input type="date" id="filterEndDate" class="form-control">
                </div>
                <div class="col-md-2 col-sm-6">
                    <button type="button" class="btn btn-secondary w-100" onclick="clearFilters()"><i class="fas fa-sync-alt me-1"></i> ລ້າງຄ່າ</button>
                </div>
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-bold small">ຄົ້ນຫາ</label>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="ຄົ້ນຫາຫົວຂໍ້, ປະເພດລາຍຈ່າຍ...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="text-muted small">
                    ພົບຂໍ້ມູນທັງໝົດ: <span class="fw-bold text-danger" id="expCount">0</span> ລາຍການ
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">ສະແດງ:</span>
                    <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 80px; border-radius: 8px; font-weight: bold; height: 32px;">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="all">ທັງໝົດ</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 120px;">ວັນທີ</th>
                            <th>ຫົວຂໍ້ລາຍຈ່າຍ</th>
                            <th>ປະເພດລາຍຈ່າຍ</th>
                            <th class="text-end" style="width: 160px;">ຈຳນວນເງິນ</th>
                            <th>ໝາຍເຫດ</th>
                            <th class="text-center">ຜູ້ບັນທຶກ</th>
                            <?php if (hasPermission('expenses', 'edit') || hasPermission('expenses', 'delete')): ?>
                            <th class="text-center" style="width: 150px;">ຈັດການ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="expenseTableBody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-top px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="text-muted small" id="paginationInfo">
                ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Expense -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius: 16px; border: none;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">ເພີ່ມລາຍຈ່າຍໃໝ່</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="expenseForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="expense_id" id="formExpenseId">
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ວັນທີລາຍຈ່າຍ <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" id="formExpenseDate" class="form-control form-control-lg rounded-3" style="font-size:0.95rem;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ຫົວຂໍ້ລາຍຈ່າຍ <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="formTitle" class="form-control form-control-lg rounded-3" placeholder="ປ້ອນຫົວຂໍ້ລາຍຈ່າຍ (ເຊັ່ນ ຄ່າໄຟຟ້າເດືອນ 5)" style="font-size:0.95rem;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ປະເພດລາຍຈ່າຍ <span class="text-danger">*</span></label>
                        <select name="category" id="formCategory" class="form-control form-control-lg rounded-3" style="font-size:0.95rem;" required>
                            <option value="">-- ເລືອກປະເພດລາຍຈ່າຍ --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ຈຳນວນເງິນ (ກີບ) <span class="text-danger">*</span></label>
                        <input type="text" name="amount" id="formAmount" class="form-control form-control-lg rounded-3 price-input" placeholder="0" style="font-size:0.95rem; font-weight: bold; color: #dc3545;" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-muted small">ໝາຍເຫດ</label>
                        <textarea name="notes" id="formNotes" class="form-control rounded-3" rows="3" placeholder="ໝາຍເຫດເພີ່ມເຕີມ..." style="font-size:0.95rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm" id="btnSubmit">
                        <i class="fas fa-save me-1"></i> ບັນທຶກ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const canEditExp = <?= hasPermission('expenses', 'edit') ? 'true' : 'false' ?>;
const canDeleteExp = <?= hasPermission('expenses', 'delete') ? 'true' : 'false' ?>;
let itemsPerPage = 10;
let currentPage = 1;
let allExpenses = [];
let filteredExpenses = [];

// Helper categories badge colors
const badgeColors = {
    'ຄ່ານ້ຳ/ຄ່າໄຟ': 'bg-warning text-dark',
    'ຄ່າເຊົ່າສະຖານທີ່': 'bg-primary text-white',
    'ເງິນເດືອນພະນັກງານ': 'bg-success text-white',
    'ຄ່າບຳລຸງຮັກສາອຸປະກອນ': 'bg-info text-dark',
    'ອື່ນໆ': 'bg-secondary text-white'
};

function formatNumber(num) {
    return Number(num).toLocaleString('en-US');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    let parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

$(document).ready(function() {
    // Input formatters for number input
    $(document).on('input', '.price-input', function() {
        let val = this.value.replace(/\D/g, "");
        if (val === '') {
            this.value = '';
        } else {
            this.value = formatNumber(val);
        }
    });

    // Page size selection change
    $('#pageSizeSelect').on('change', function() {
        let val = $(this).val();
        itemsPerPage = (val === 'all') ? 999999 : parseInt(val);
        showPage(1);
    });

    // Filtering inputs
    $('#filterStartDate, #filterEndDate').on('change', function() {
        applyFiltersAndSearch();
    });
    $('#searchInput').on('input', function() {
        applyFiltersAndSearch();
    });

    // Fetch initial list
    fetchExpenses();
    loadExpenseCategories();
    
    // Form submission
    $('#expenseForm').on('submit', function(e) {
        e.preventDefault();
        submitForm();
    });
});

function fetchExpenses() {
    $.ajax({
        url: '../api/expense_api.php',
        type: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                allExpenses = res.expenses;
                applyFiltersAndSearch();
                updateStats();
            } else {
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ເຊີເວີໄດ້' });
        }
    });
}

function updateStats() {
    let todayTotal = 0;
    let monthTotal = 0;
    let allTotal = 0;
    
    let now = new Date();
    let currentYear = now.getFullYear();
    let currentMonth = now.getMonth() + 1; // 1-12
    let todayStr = now.toISOString().slice(0, 10); // YYYY-MM-DD
    
    allExpenses.forEach(exp => {
        let amt = parseFloat(exp.amount) || 0;
        allTotal += amt;
        
        if (exp.expense_date === todayStr) {
            todayTotal += amt;
        }
        
        let expDate = new Date(exp.expense_date);
        if (expDate.getFullYear() === currentYear && (expDate.getMonth() + 1) === currentMonth) {
            monthTotal += amt;
        }
    });
    
    $('#stat-today').text(formatNumber(todayTotal) + ' ກີບ');
    $('#stat-month').text(formatNumber(monthTotal) + ' ກີບ');
    $('#stat-all').text(formatNumber(allTotal) + ' ກີບ');
}

function applyFiltersAndSearch() {
    let startVal = $('#filterStartDate').val();
    let endVal = $('#filterEndDate').val();
    let query = $('#searchInput').val().toLowerCase().trim();
    
    filteredExpenses = allExpenses.filter(exp => {
        // Date filters
        if (startVal !== '' && exp.expense_date < startVal) return false;
        if (endVal !== '' && exp.expense_date > endVal) return false;
        
        // Search text query
        if (query !== '') {
            let title = (exp.title || '').toLowerCase();
            let category = (exp.category || '').toLowerCase();
            let notes = (exp.notes || '').toLowerCase();
            let staff = ((exp.fname || '') + ' ' + (exp.lname || '')).toLowerCase();
            
            if (title.indexOf(query) === -1 && 
                category.indexOf(query) === -1 && 
                notes.indexOf(query) === -1 && 
                staff.indexOf(query) === -1) {
                return false;
            }
        }
        return true;
    });
    
    $('#expCount').text(filteredExpenses.length);
    showPage(1);
}

function clearFilters() {
    $('#filterStartDate').val('');
    $('#filterEndDate').val('');
    $('#searchInput').val('');
    applyFiltersAndSearch();
}

function showPage(page) {
    currentPage = page;
    let totalItems = filteredExpenses.length;
    let tbody = $('#expenseTableBody');
    tbody.html('');
    
    if (totalItems === 0) {
        let cols = (canEditExp || canDeleteExp) ? 7 : 6;
        tbody.append(`
            <tr>
                <td colspan="${cols}" class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                    ບໍ່ພົບຂໍ້ມູນລາຍຈ່າຍ
                </td>
            </tr>
        `);
        $('#paginationInfo').text('ສະແດງ 0 ຫາ 0 ຈາກທັງໝົດ 0 ລາຍການ');
        $('#paginationControls').html('');
        return;
    }
    
    let totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    
    let startIndex = (currentPage - 1) * itemsPerPage;
    let endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    
    for (let i = startIndex; i < endIndex; i++) {
        let exp = filteredExpenses[i];
        let badge = badgeColors[exp.category] || 'bg-secondary text-white';
        let staffName = (exp.fname) ? `${exp.fname} ${exp.lname}` : 'Admin';
        
        let actionsTd = '';
        if (canEditExp || canDeleteExp) {
            let editBtn = canEditExp ? `
                <button class="btn btn-warning btn-sm btn-action" onclick="openEditModal(${exp.expense_id})" title="ແກ້ໄຂ">
                    <i class="fas fa-edit"></i>
                </button>` : '';
            let deleteBtn = canDeleteExp ? `
                <button class="btn btn-danger btn-sm btn-action" onclick="deleteExpense(${exp.expense_id})" title="ລົບ">
                    <i class="fas fa-trash-alt"></i>
                </button>` : '';
            actionsTd = `
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        ${editBtn}
                        ${deleteBtn}
                    </div>
                </td>`;
        }
        
        tbody.append(`
            <tr>
                <td class="text-center fw-bold">${formatDate(exp.expense_date)}</td>
                <td class="fw-bold text-dark">${exp.title}</td>
                <td><span class="badge ${badge} border px-3 py-2" style="border-radius:12px; font-weight:700;">${exp.category}</span></td>
                <td class="text-end fw-bold text-danger" style="font-size:1.05rem;">${formatNumber(exp.amount)} ກີບ</td>
                <td class="text-muted small">${exp.notes || '-'}</td>
                <td class="text-center"><span class="badge bg-light text-dark border">${staffName}</span></td>
                ${actionsTd}
            </tr>
        `);
    }
    
    $('#paginationInfo').text(`ສະແດງ ${startIndex + 1} ຫາ ${endIndex} ຈາກທັງໝົດ ${totalItems} ລາຍການ`);
    renderControls(totalPages);
}

function renderControls(totalPages) {
    let controlsHtml = '';
    
    if (currentPage === 1) {
        controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-left"></i></a></li>`;
    } else {
        controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="showPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></a></li>`;
    }
    
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    for (let p = startPage; p <= endPage; p++) {
        if (p <= 0) continue;
        if (p === currentPage) {
            controlsHtml += `<li class="page-item active"><a class="page-link" href="javascript:void(0)">${p}</a></li>`;
        } else {
            controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="showPage(${p})">${p}</a></li>`;
        }
    }
    
    if (currentPage === totalPages) {
        controlsHtml += `<li class="page-item disabled"><a class="page-link" href="javascript:void(0)"><i class="fas fa-chevron-right"></i></a></li>`;
    } else {
        controlsHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="showPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></a></li>`;
    }
    
    $('#paginationControls').html(controlsHtml);
}

function openCreateModal() {
    $('#expenseForm')[0].reset();
    $('#modalTitle').text('ເພີ່ມລາຍຈ່າຍໃໝ່');
    $('#formAction').val('create');
    $('#formExpenseId').val('');
    
    // Set default date as today
    let today = new Date().toISOString().slice(0, 10);
    $('#formExpenseDate').val(today);
    
    $('#expenseModal').modal('show');
}

function openEditModal(expenseId) {
    $.ajax({
        url: '../api/expense_api.php',
        type: 'GET',
        data: { action: 'get', expense_id: expenseId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let e = res.expense;
                $('#modalTitle').text('ແກ້ໄຂຂໍ້ມູນລາຍຈ່າຍ');
                $('#formAction').val('update');
                $('#formExpenseId').val(e.expense_id);
                $('#formExpenseDate').val(e.expense_date);
                $('#formTitle').val(e.title);
                $('#formCategory').val(e.category);
                $('#formAmount').val(formatNumber(e.amount));
                $('#formNotes').val(e.notes);
                
                $('#expenseModal').modal('show');
            } else {
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດດຶງຂໍ້ມູນຈາກເຊີເວີໄດ້' });
        }
    });
}

function submitForm() {
    // Remove commas from amount before posting
    let amountInput = $('#formAmount');
    let rawAmount = amountInput.val().replace(/,/g, '');
    
    let formData = new FormData($('#expenseForm')[0]);
    formData.set('amount', rawAmount); // replace formatted with raw number
    
    $.ajax({
        url: '../api/expense_api.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#expenseModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'ສຳເລັດ', text: res.message, timer: 1500, showConfirmButton: false });
                fetchExpenses();
            } else {
                Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດບັນທຶກຂໍ້ມູນໄດ້' });
        }
    });
}

function deleteExpense(expenseId) {
    Swal.fire({
        title: 'ກະລຸນາຢືນຢັນການລົບ',
        text: "ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບລາຍການລາຍຈ່າຍນີ້? ຂໍ້ມູນຈະບໍ່ສາມາດກູ້ຄືນໄດ້!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ຢືນຢັນລົບ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/expense_api.php',
                type: 'POST',
                data: { action: 'delete', expense_id: expenseId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'ລົບສຳເລັດ', text: res.message, timer: 1500, showConfirmButton: false });
                        fetchExpenses();
                    } else {
                        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດລົບຂໍ້ມູນໄດ້' });
                }
            });
        }
    });
}

function loadExpenseCategories() {
    $.ajax({
        url: '../api/expense_category_api.php',
        type: 'GET',
        data: { action: 'list' },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let select = $('#formCategory');
                select.html('<option value="">-- ເລືອກປະເພດລາຍຈ່າຍ --</option>');
                res.categories.forEach(cat => {
                    select.append(`<option value="${cat.category_name}">${cat.category_name}</option>`);
                });
            }
        }
    });
}
</script>
</body>
</html>
