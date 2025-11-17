<?php
// Enhanced Reports Module with Category Filter

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

// Handle report deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    $report_id = (int)$_POST['report_id'];
    
    $report_check = $koneksi->prepare("SELECT user_id FROM reports WHERE id = ?");
    $report_check->bind_param('i', $report_id);
    $report_check->execute();
    $report_data = $report_check->get_result()->fetch_assoc();
    
    $can_delete = false;
    if ($current_permissions['can_delete_all']) {
        $can_delete = true;
    } elseif ($current_permissions['can_delete_own'] && $report_data['user_id'] == $current_user_id) {
        $can_delete = true;
    }
    
    if ($can_delete) {
        $delete_stmt = $koneksi->prepare("DELETE FROM reports WHERE id = ?");
        $delete_stmt->bind_param('i', $report_id);
        if ($delete_stmt->execute()) {
            $message = 'Laporan berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus laporan!';
        }
    } else {
        $error = 'Anda tidak memiliki izin untuk menghapus laporan ini!';
    }
}

// Handle report editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_report'])) {
    $report_id = (int)$_POST['report_id'];
    $activity = $_POST['activity'];
    
    $report_check = $koneksi->prepare("SELECT user_id FROM reports WHERE id = ?");
    $report_check->bind_param('i', $report_id);
    $report_check->execute();
    $report_data = $report_check->get_result()->fetch_assoc();
    
    $can_edit = false;
    if ($current_permissions['can_edit_all']) {
        $can_edit = true;
    } elseif ($current_permissions['can_edit_own'] && $report_data['user_id'] == $current_user_id) {
        $can_edit = true;
    }
    
    if ($can_edit) {
        if (empty($activity)) {
            $error = 'Aktivitas harus diisi!';
        } else {
            $update_stmt = $koneksi->prepare("UPDATE reports SET activity = ? WHERE id = ?");
            $update_stmt->bind_param('si', $activity, $report_id);
            if ($update_stmt->execute()) {
                $message = 'Laporan berhasil diperbarui!';
            } else {
                $error = 'Gagal memperbarui laporan!';
            }
        }
    } else {
        $error = 'Anda tidak memiliki izin untuk mengedit laporan ini!';
    }
}

// Handle form submission for new report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    if (!$current_permissions['can_create']) {
        $error = 'Anda tidak memiliki izin untuk membuat laporan!';
    } else {
        $activity = $_POST['activity'];
        $report_date = $_POST['report_date'] ?? date('Y-m-d');
        
        if (empty($activity)) {
            $error = 'Aktivitas harus diisi!';
        } else {
            $insert_report = $koneksi->prepare("
                INSERT INTO reports (date, user_id, activity) 
                VALUES (?, ?, ?)
            ");
            $insert_report->bind_param('sis', $report_date, $current_user_id, $activity);
            
            if ($insert_report->execute()) {
                $message = 'Laporan berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan laporan: ' . $koneksi->error;
            }
        }
    }
}

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
        $date_start = date('Y-m-01'); // First day of current month
        break;
    case 'tahunan':
        $date_start = date('Y-01-01'); // First day of current year
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
$report_title = 'Laporan Siswa';

// Build report based on category
if (!empty($category_filter)) {
    switch($category_filter) {
        case 'apps':
            $report_title = 'Laporan Aplikasi';
            $query = "
                SELECT a.id, a.name as app_name, a.description, 
                       COUNT(t.id) as total_todos,
                       COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed_todos,
                       COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as progress_todos,
                       a.created_at as date
                FROM apps a
                LEFT JOIN todos t ON a.id = t.app_id
                LEFT JOIN taken tk ON t.id = tk.id_todos
                WHERE DATE(a.created_at) BETWEEN ? AND ?
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param('ss', $date_start, $date_end);
            $stmt->execute();
            $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'users':
            $report_title = 'Laporan Pengguna';
            $query = "
                SELECT u.id, u.name, u.email, u.role, u.gender, u.phone,
                       COUNT(DISTINCT t.id) as total_todos_created,
                       COUNT(DISTINCT tk.id) as total_taken,
                       COUNT(DISTINCT CASE WHEN tk.status = 'done' THEN tk.id END) as completed_taken,
                       u.created_at as date
                FROM users u
                LEFT JOIN todos t ON u.id = t.user_id
                LEFT JOIN taken tk ON u.id = tk.user_id
                WHERE DATE(u.created_at) BETWEEN ? AND ?
                GROUP BY u.id
                ORDER BY u.created_at DESC
            ";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param('ss', $date_start, $date_end);
            $stmt->execute();
            $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'todos':
            $report_title = 'Laporan Todo';
            $query = "
                SELECT t.id, t.title, t.description, t.priority,
                       a.name as app_name,
                       u.name as creator_name,
                       tk.status,
                       taker.name as taker_name,
                       t.created_at as date
                FROM todos t
                LEFT JOIN apps a ON t.app_id = a.id
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN taken tk ON t.id = tk.id_todos
                LEFT JOIN users taker ON tk.user_id = taker.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                ORDER BY t.created_at DESC
            ";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param('ss', $date_start, $date_end);
            $stmt->execute();
            $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'taken':
            $report_title = 'Laporan Taken';
            $query = "
                SELECT tk.id, tk.status, tk.date,
                       t.title as todo_title,
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
            break;
    }
}

// Helper functions
function getRoleColor($role) {
    $colors = ['admin' => '#dc2626', 'client' => '#7c3aed', 'programmer' => '#0066ff', 'support' => '#10b981'];
    return $colors[$role] ?? '#6b7280';
}

function getPriorityColor($priority) {
    $colors = ['high' => '#dc2626', 'medium' => '#f59e0b', 'low' => '#10b981'];
    return $colors[$priority] ?? '#6b7280';
}

function getStatusColor($status) {
    $colors = ['done' => '#10b981', 'in_progress' => '#f59e0b', 'pending' => '#3b82f6'];
    return $colors[$status] ?? '#6b7280';
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
    border-radius: 8px;
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
    width: 100%;
    padding: 18px 24px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-preview:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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

.badge-role-admin { background: #fee2e2; color: #dc2626; }
.badge-role-programmer { background: #dbeafe; color: #2563eb; }
.badge-role-support { background: #d1fae5; color: #059669; }
.badge-role-client { background: #ede9fe; color: #7c3aed; }

.badge-priority-high { background: #fee2e2; color: #dc2626; }
.badge-priority-medium { background: #fef3c7; color: #d97706; }
.badge-priority-low { background: #d1fae5; color: #059669; }

.badge-status-done { background: #d1fae5; color: #059669; }
.badge-status-in_progress { background: #fef3c7; color: #d97706; }
.badge-status-pending { background: #dbeafe; color: #2563eb; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #acaf9cff;
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
                        <option value="apps" <?= $category_filter == 'apps' ? 'selected' : '' ?>>Apps</option>
                        <option value="users" <?= $category_filter == 'users' ? 'selected' : '' ?>>Users</option>
                        <option value="todos" <?= $category_filter == 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="taken" <?= $category_filter == 'taken' ? 'selected' : '' ?>>Taken</option>
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

            <button type="submit" class="btn-preview">
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
                    <?php if ($category_filter == 'apps'): ?>
                        <th>No</th>
                        <th>Nama Aplikasi</th>
                        <th>Deskripsi</th>
                        <th>Total Todos</th>
                        <th>Selesai</th>
                        <th>Progress</th>
                        <th>Tanggal Dibuat</th>
                    <?php elseif ($category_filter == 'users'): ?>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Gender</th>
                        <th>Todos Dibuat</th>
                        <th>Taken</th>
                        <th>Selesai</th>
                    <?php elseif ($category_filter == 'todos'): ?>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Aplikasi</th>
                        <th>Prioritas</th>
                        <th>Dibuat Oleh</th>
                        <th>Diambil Oleh</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    <?php elseif ($category_filter == 'taken'): ?>
                        <th>No</th>
                        <th>Todo</th>
                        <th>Aplikasi</th>
                        <th>Prioritas</th>
                        <th>Diambil Oleh</th>
                        <th>Dibuat Oleh</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($report_data as $row): 
                ?>
                <tr>
                    <?php if ($category_filter == 'apps'): ?>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['app_name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                        <td><?= $row['total_todos'] ?></td>
                        <td><?= $row['completed_todos'] ?></td>
                        <td><?= $row['progress_todos'] ?></td>
                        <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                    <?php elseif ($category_filter == 'users'): ?>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><span class="badge badge-role-<?= $row['role'] ?>"><?= ucfirst($row['role']) ?></span></td>
                        <td><?= $row['gender'] == 'female' ? 'Perempuan' : 'Laki-laki' ?></td>
                        <td><?= $row['total_todos_created'] ?></td>
                        <td><?= $row['total_taken'] ?></td>
                        <td><?= $row['completed_taken'] ?></td>
                    <?php elseif ($category_filter == 'todos'): ?>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                        <td><?= htmlspecialchars($row['app_name']) ?></td>
                        <td><span class="badge badge-priority-<?= $row['priority'] ?>"><?= ucfirst($row['priority']) ?></span></td>
                        <td><?= htmlspecialchars($row['creator_name'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($row['taker_name'] ?: 'Belum diambil') ?></td>
                        <td><?= $row['status'] ? '<span class="badge badge-status-' . $row['status'] . '">' . ucfirst(str_replace('_', ' ', $row['status'])) . '</span>' : '-' ?></td>
                        <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                    <?php elseif ($category_filter == 'taken'): ?>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['todo_title']) ?></strong></td>
                        <td><?= htmlspecialchars($row['app_name']) ?></td>
                        <td><span class="badge badge-priority-<?= $row['priority'] ?>"><?= ucfirst($row['priority']) ?></span></td>
                        <td><?= htmlspecialchars($row['taker_name']) ?></td>
                        <td><?= htmlspecialchars($row['creator_name'] ?: '-') ?></td>
                        <td><span class="badge badge-status-<?= $row['status'] ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                    <?php endif; ?>
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
            // Same day
            startDate = new Date();
            break;
        case 'mingguan':
            // 7 days ago
            startDate.setDate(today.getDate() - 7);
            break;
        case 'bulanan':
            // First day of current month
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
        case 'tahunan':
            // First day of current year
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

// Initialize dates on page load if period is selected
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('date_start') && urlParams.has('period')) {
        updateDates();
    }
});
</script>