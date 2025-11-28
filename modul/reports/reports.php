<?php
// Enhanced Reports Module with Laporan Tugas

// Get current logged in user info
$current_user_id = $_SESSION['user_id'];
$current_user_query = $koneksi->prepare("SELECT role, name FROM users WHERE id = ?");
$current_user_query->bind_param('i', $current_user_id);
$current_user_query->execute();
$current_user_result = $current_user_query->get_result();

if ($current_user_result->num_rows === 0) {
    die('User tidak ditemukan dalam sistem!');
}

$current_user = $current_user_result->fetch_assoc();
$current_user_role = $current_user['role'];

// Define role permissions
$role_permissions = [
    'admin' => [
        'can_create' => true,
        'can_view_all' => true,
        'can_edit_all' => true,
        'can_delete_all' => true,
        'can_view_own' => true,
        'can_edit_own' => true,
        'can_delete_own' => true
    ],
    'client' => [
        'can_create' => true,
        'can_view_all' => false,
        'can_edit_all' => false,
        'can_delete_all' => false,
        'can_view_own' => true,
        'can_edit_own' => true,
        'can_delete_own' => true
    ],
    'programmer' => [
        'can_create' => true,
        'can_view_all' => true,
        'can_edit_all' => false,
        'can_delete_all' => false,
        'can_view_own' => true,
        'can_edit_own' => true,
        'can_delete_own' => true
    ],
    'support' => [
        'can_create' => true,
        'can_view_all' => true,
        'can_edit_all' => false,
        'can_delete_all' => false,
        'can_view_own' => true,
        'can_edit_own' => true,
        'can_delete_own' => true
    ]
];

$current_permissions = $role_permissions[$current_user_role] ?? [];

// Filter parameters
$message = $message ?? '';
$error = $error ?? '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$period_filter = isset($_GET['period']) ? $_GET['period'] : 'harian';

// Calculate date range based on period
$date_start = '';
$date_end = date('Y-m-d');

switch($period_filter) {
    case 'harian':
        $date_start = date('Y-m-d');
        break;
    case 'mingguan':
        $date_start = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'bulanan':
        $date_start = date('Y-m-01');
        break;
    case 'tahunan':
        $date_start = date('Y-01-01');
        break;
    default:
        $date_start = date('Y-m-d');
}

// Allow manual date override if provided
if (isset($_GET['date_start']) && !empty($_GET['date_start'])) {
    $date_start = $_GET['date_start'];
}
if (isset($_GET['date_end']) && !empty($_GET['date_end'])) {
    $date_end = $_GET['date_end'];
}

// Initialize report data
$report_data = [];
$report_title = 'Laporan Tugas';

// Build report based on category
if (!empty($category_filter)) {
    if ($category_filter == 'laporan_tugas') {
        $report_title = 'Laporan Tugas';
        $query = "
            SELECT tk.id, tk.status, tk.date,
                   t.title as todo_title,
                   t.description as todo_description,
                   t.priority,
                   a.name as app_name,
                   u.name as taker_name,
                   creator.name as creator_name,
                   tk.created_at
            FROM taken tk
            LEFT JOIN todos t ON tk.id_todos = t.id
            LEFT JOIN apps a ON t.app_id = a.id
            LEFT JOIN users u ON tk.user_id = u.id
            LEFT JOIN users creator ON t.user_id = creator.id
            WHERE tk.date BETWEEN ? AND ?
            ORDER BY tk.date DESC
        ";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param('ss', $date_start, $date_end);
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<style>
/* Page Container */
.reports-page {
    background: #f5f6fa;
    min-height: 100vh;
    padding: 0;
}

/* Page Header */
.reports-header {
    background: #f5f6fa;
    padding: 10px 30px;
    margin-bottom: 16px;
}

.reports-header h1 {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 0;
}

/* Filter Section */
.filter-section {
    background: white;
    border-radius: 0;
    padding: 26px 30px;
    margin: 0 30px 0 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.filter-input-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.filter-input {
    min-width: 100%;
}

.filter-select,
.filter-date {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 20px;
    font-size: 0.95rem;
    background: white;
    color: #1f2937;
    transition: all 0.3s ease;
}

.filter-select:focus,
.filter-date:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.date-group {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.date-input-wrapper {
    flex: 1;
}

.btn-preview {
    width: 10%;
    padding: 18px 25px;
    background: linear-gradient(135deg, #3baef6ff, #3baef6ff);
    color: white;
    border: none;
    border-radius: 15px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-preview:hover:not(:disabled) {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-preview:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Report Table Section */
.report-table-section {
    background: white;
    border-radius: 0;
    padding: 26px 30px;
    margin: 20px 30px 30px 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.report-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f3f4f6;
}

.report-table-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.report-info {
    text-align: right;
    color: #6b7280;
    font-size: 0.9rem;
}

/* Table Styles */
.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.report-table thead {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.report-table th {
    padding: 16px;
    text-align: left;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
}

.report-table th:last-child {
    border-right: none;
}

.report-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.report-table tbody tr:hover {
    background: #f9fafb;
}

.report-table td {
    padding: 14px 16px;
    color: #374151;
    font-size: 0.9rem;
}

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-priority-high { background: #fee2e2; color: #dc2626; }
.badge-priority-medium { background: #fef3c7; color: #d97706; }
.badge-priority-low { background: #d1fae5; color: #059669; }

.badge-status-done { background: #d1fae5; color: #059669; }
.badge-status-in_progress { background: #fef3c7; color: #d97706; }
.badge-status-pending { background: #dbeafe; color: #2563eb; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: #d1d5db;
}

.empty-state h3 {
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: #6b7280;
}

.empty-state p {
    font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 768px) {
    .reports-page {
        padding: 0;
    }
    
    .reports-header {
        padding: 8px 15px;
    }
    
    .filter-section {
        margin: 0 15px;
        padding: 20px;
    }
    
    .report-table-section {
        margin: 20px 15px 30px 15px;
        padding: 20px;
    }
    
    .filter-input-group {
        grid-template-columns: 1fr;
    }
    
    .date-group {
        flex-direction: column;
    }
    
    .filter-input,
    .date-input-wrapper {
        min-width: 100%;
    }
    
    .report-table {
        font-size: 0.85rem;
    }
    
    .report-table th,
    .report-table td {
        padding: 10px 8px;
    }
}
</style>

<div class="reports-page">
    <!-- Page Header -->
    <div class="reports-header">
        <h1>Laporan</h1>
        <p>Generate dan preview laporan berdasarkan kategori dan periode waktu</p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="?page=reports" id="reportForm">
            <input type="hidden" name="page" value="reports">
            
            <div class="filter-input-group">
                <div class="filter-input">
                    <label class="filter-label">Laporan:</label>
                    <select name="category" class="filter-select" id="categorySelect" required onchange="updateDates()">
                        <option value="">Cari laporan...</option>
                        <option value="laporan_tugas" <?= $category_filter == 'laporan_tugas' ? 'selected' : '' ?>>Laporan Tugas</option>
                    </select>
                </div>
                
                <div class="filter-input">
                    <label class="filter-label">Filter Berdasarkan:</label>
                    <select name="period" class="filter-select" id="periodSelect" onchange="updateDates()">
                        <option value="harian" <?= $period_filter == 'harian' ? 'selected' : '' ?>>Harian</option>
                        <option value="mingguan" <?= $period_filter == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                        <option value="bulanan" <?= $period_filter == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                        <option value="tahunan" <?= $period_filter == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                    </select>
                </div>
            </div>

            <div class="date-group">
                <div class="date-input-wrapper">
                    <label class="filter-label">Tanggal Mulai:</label>
                    <input type="date" name="date_start" id="dateStart" class="filter-date" 
                           value="<?= htmlspecialchars($date_start) ?>" required>
                </div>
                <div class="date-input-wrapper">
                    <label class="filter-label">Tanggal Selesai:</label>
                    <input type="date" name="date_end" id="dateEnd" class="filter-date" 
                           value="<?= htmlspecialchars($date_end) ?>" required>
                </div>
            </div>

            <button type="button" class="btn-preview" onclick="previewReport()">
                <i class="fas fa-eye"></i> PREVIEW
            </button>
        </form>
    </div>

    <!-- Report Table Section -->
    <?php if (!empty($category_filter) && !empty($report_data)): ?>
    <div class="report-table-section">
        <div class="report-table-header">
            <h2 class="report-table-title"><?= htmlspecialchars($report_title) ?></h2>
            <div class="report-info">
                <div>Periode: <?= date('d/m/Y', strtotime($date_start)) ?> - <?= date('d/m/Y', strtotime($date_end)) ?></div>
                <div>Total Data: <?= count($report_data) ?></div>
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Tugas</th>
                    <th>Aplikasi</th>
                    <th>Prioritas</th>
                    <th>Pengambil Tugas</th>
                    <th>Pembuat Tugas</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($report_data as $row): 
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['todo_title']) ?></strong></td>
                    <td><?= htmlspecialchars($row['app_name']) ?></td>
                    <td><span class="badge badge-priority-<?= $row['priority'] ?>"><?= ucfirst($row['priority']) ?></span></td>
                    <td><?= htmlspecialchars($row['taker_name']) ?></td>
                    <td><?= htmlspecialchars($row['creator_name'] ?: '-') ?></td>
                    <td><span class="badge badge-status-<?= $row['status'] ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif (!empty($category_filter)): ?>
    <div class="report-table-section">
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Tidak Ada Data</h3>
            <p>Tidak ada data untuk periode yang dipilih</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Function to preview report - open coba.php in new tab with auto print
function previewReport() {
    const category = document.getElementById('categorySelect').value;
    const period = document.getElementById('periodSelect').value;
    const dateStart = document.getElementById('dateStart').value;
    const dateEnd = document.getElementById('dateEnd').value;
    
    // Validasi form
    if (!category) {
        alert('Silakan pilih kategori laporan terlebih dahulu!');
        return;
    }
    
    if (!dateStart || !dateEnd) {
        alert('Silakan pilih tanggal mulai dan tanggal selesai!');
        return;
    }
    
    // Validasi tanggal
    if (new Date(dateStart) > new Date(dateEnd)) {
        alert('Tanggal mulai tidak boleh lebih besar dari tanggal selesai!');
        return;
    }
    
    // Show loading indicator
    const btn = document.querySelector('.btn-preview');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuka...';
    btn.disabled = true;
    
    // Build URL parameters
    const params = new URLSearchParams({
        category: category,
        period: period,
        date_start: dateStart,
        date_end: dateEnd,
        auto_print: '1' // Flag untuk trigger auto print
    });
    
    // Open in new tab
    const url = 'modul/laporan/coba.php?' + params.toString();
    window.open(url, '_blank');
    
    // Reset button after short delay
    setTimeout(function() {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 1000);
}

// Function to update date ranges based on period selection
function updateDates() {
    const period = document.getElementById('periodSelect').value;
    const today = new Date();
    const dateStart = document.getElementById('dateStart');
    const dateEnd = document.getElementById('dateEnd');
    
    // Set end date to today
    dateEnd.value = formatDate(today);
    
    let startDate = new Date();
    
    switch(period) {
        case 'harian':
            startDate = new Date();
            break;
        case 'mingguan':
            startDate.setDate(today.getDate() - 7);
            break;
        case 'bulanan':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
        case 'tahunan':
            startDate = new Date(today.getFullYear(), 0, 1);
            break;
    }
    
    dateStart.value = formatDate(startDate);
}

// Helper function to format date as YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Initialize dates on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('date_start') && urlParams.has('period')) {
        updateDates();
    }
});
</script>