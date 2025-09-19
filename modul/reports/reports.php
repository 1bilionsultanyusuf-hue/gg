<?php
// modul/data/reports.php
// Handle add report functionality
if (isset($_POST['add_report'])) {
    $user_id = $_SESSION['user_id'];
    $todo_id = (int)$_POST['todo_id'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    // Validate input
    if (empty($todo_id) || empty($date) || empty($status)) {
        $error = 'Semua field wajib diisi!';
    } else {
        // Check if todo exists and user has access
        $todo_check = $koneksi->prepare("
            SELECT t.*, a.name as app_name 
            FROM todos t 
            LEFT JOIN apps a ON t.app_id = a.id 
            WHERE t.id = ?
        ");
        $todo_check->bind_param('i', $todo_id);
        $todo_check->execute();
        $todo_result = $todo_check->get_result();
        
        if ($todo_result->num_rows > 0) {
            // Insert new report
            $insert_stmt = $koneksi->prepare("
                INSERT INTO taken (user_id, id_todos, date, status, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param('iisss', $user_id, $todo_id, $date, $status, $notes);
            
            if ($insert_stmt->execute()) {
                $message = 'Laporan berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan laporan: ' . $koneksi->error;
            }
        } else {
            $error = 'Todo tidak ditemukan!';
        }
    }
}

// Handle filters and pagination - PERBAIKI NAMA PARAMETER
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$app_filter = isset($_GET['app']) ? $_GET['app'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// GANTI 'page' dengan 'p' untuk pagination agar tidak konflik
$current_page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$records_per_page = 15;
$offset = ($current_page - 1) * $records_per_page;
$offset = max(0, $offset);

// Build WHERE conditions
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(tk.date) = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

if (!empty($user_filter)) {
    $where_conditions[] = "u.id = ?";
    $params[] = $user_filter;
    $param_types .= 'i';
}

if (!empty($app_filter)) {
    $where_conditions[] = "a.id = ?";
    $params[] = $app_filter;
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "tk.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM taken tk
    LEFT JOIN todos t ON tk.id_todos = t.id
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON tk.user_id = u.id
    $where_clause
";

if (!empty($params)) {
    $count_stmt = $koneksi->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $koneksi->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Get report data
$reports_query = "
    SELECT tk.*, 
           t.title as todo_title,
           t.description as todo_description,
           t.priority,
           t.created_at as todo_created,
           a.name as app_name,
           u.name as user_name,
           u.role as user_role,
           u.email as user_email
    FROM taken tk
    LEFT JOIN todos t ON tk.id_todos = t.id
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON tk.user_id = u.id
    $where_clause
    ORDER BY tk.date DESC, tk.id DESC
    LIMIT ? OFFSET ?
";

if (!empty($params)) {
    $reports_stmt = $koneksi->prepare($reports_query);
    $params[] = $records_per_page;
    $params[] = $offset;
    $param_types .= 'ii';
    $reports_stmt->bind_param($param_types, ...$params);
    $reports_stmt->execute();
    $reports_result = $reports_stmt->get_result();
} else {
    $reports_stmt = $koneksi->prepare($reports_query);
    $reports_stmt->bind_param('ii', $records_per_page, $offset);
    $reports_stmt->execute();
    $reports_result = $reports_stmt->get_result();
}

// Get filter options
$users_options = $koneksi->query("SELECT id, name, role FROM users ORDER BY name");
$apps_options = $koneksi->query("SELECT id, name FROM apps ORDER BY name");

// Get todos for add report form - only active todos
$todos_options = $koneksi->query("
    SELECT t.*, a.name as app_name 
    FROM todos t 
    LEFT JOIN apps a ON t.app_id = a.id 
    WHERE t.status != 'done' 
    ORDER BY t.created_at DESC
");

// Get statistics for dashboard
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_week = date('Y-m-d', strtotime('-7 days'));

$stats_today = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE DATE(date) = '$today'")->fetch_assoc()['count'];
$stats_completed_today = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE DATE(date) = '$today' AND status = 'done'")->fetch_assoc()['count'];
$stats_in_progress = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];
$stats_this_week = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE date >= '$this_week'")->fetch_assoc()['count'];

// Helper functions
function getRoleColor($role) {
    $colors = [
        'admin' => '#dc2626',
        'client' => '#7c3aed',
        'programmer' => '#0066ff',
        'support' => '#10b981'
    ];
    return $colors[$role] ?? '#6b7280';
}

function getRoleIcon($role) {
    $icons = [
        'admin' => 'fas fa-crown',
        'client' => 'fas fa-briefcase',
        'programmer' => 'fas fa-code',
        'support' => 'fas fa-headset'
    ];
    return $icons[$role] ?? 'fas fa-user';
}

function getPriorityColor($priority) {
    $colors = [
        'high' => '#ef4444',
        'medium' => '#f59e0b',
        'low' => '#10b981'
    ];
    return $colors[$priority] ?? '#6b7280';
}

function getStatusColor($status) {
    $colors = [
        'done' => '#10b981',
        'in_progress' => '#f59e0b'
    ];
    return $colors[$status] ?? '#6b7280';
}

function getActivityDescription($report) {
    $role = strtolower($report['user_role']);
    $status = $report['status'];
    $todo_title = $report['todo_title'];
    $app_name = $report['app_name'];
    
    switch($role) {
        case 'programmer':
            if($status == 'done') {
                return "Menyelesaikan pengembangan: '$todo_title' pada aplikasi $app_name";
            } else {
                return "Sedang mengerjakan: '$todo_title' pada aplikasi $app_name";
            }
        case 'support':
            if($status == 'done') {
                return "Menyelesaikan support: '$todo_title' pada aplikasi $app_name";
            } else {
                return "Sedang menangani: '$todo_title' pada aplikasi $app_name";
            }
        case 'client':
            if($status == 'done') {
                return "Menyelesaikan review: '$todo_title' pada aplikasi $app_name";
            } else {
                return "Sedang mereview: '$todo_title' pada aplikasi $app_name";
            }
        default:
            return "Mengerjakan: '$todo_title' pada aplikasi $app_name";
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    
    return date('d M Y', strtotime($datetime));
}

// PERBAIKI FUNCTION buildFilterQuery
function buildFilterQuery() {
    global $role_filter, $date_filter, $user_filter, $app_filter, $status_filter;
    
    $params = [];
    if ($role_filter) $params[] = "role=$role_filter";
    if ($date_filter) $params[] = "date=$date_filter";
    if ($user_filter) $params[] = "user=$user_filter";
    if ($app_filter) $params[] = "app=$app_filter";
    if ($status_filter) $params[] = "status=$status_filter";
    
    return !empty($params) ? '&' . implode('&', $params) : '';
}
?>

<div class="main-content">
    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-chart-line mr-3"></i>
                Laporan Aktivitas
            </h1>
            <p class="page-subtitle">
                Monitor dan analisis aktivitas semua pengguna dalam sistem
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" onclick="openAddReportModal()">
                <i class="fas fa-plus mr-2"></i>
                Tambah Laporan
            </button>
            <button class="btn btn-secondary" onclick="exportReport()">
                <i class="fas fa-download mr-2"></i>
                Export PDF
            </button>
            <button class="btn btn-primary" onclick="refreshReport()">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_today ?></h3>
                <p class="stat-label">Aktivitas Hari Ini</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_completed_today ?></h3>
                <p class="stat-label">Selesai Hari Ini</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_in_progress ?></h3>
                <p class="stat-label">Sedang Dikerjakan</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-purple">
            <div class="stat-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_this_week ?></h3>
                <p class="stat-label">Minggu Ini</p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-header">
            <h3>Filter Laporan</h3>
            <?php if($role_filter || $date_filter || $user_filter || $app_filter || $status_filter): ?>
            <button class="btn-clear-filters" onclick="clearAllFilters()">
                <i class="fas fa-times mr-2"></i>
                Hapus Semua Filter
            </button>
            <?php endif; ?>
        </div>
        
        <div class="filters-grid">
            <div class="filter-group">
                <label>Role</label>
                <select id="roleFilter" onchange="applyFilters()">
                    <option value="">Semua Role</option>
                    <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="client" <?= $role_filter == 'client' ? 'selected' : '' ?>>Client</option>
                    <option value="programmer" <?= $role_filter == 'programmer' ? 'selected' : '' ?>>Programmer</option>
                    <option value="support" <?= $role_filter == 'support' ? 'selected' : '' ?>>Support</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Tanggal</label>
                <input type="date" id="dateFilter" value="<?= htmlspecialchars($date_filter) ?>" onchange="applyFilters()">
            </div>

            <div class="filter-group">
                <label>Pengguna</label>
                <select id="userFilter" onchange="applyFilters()">
                    <option value="">Semua Pengguna</option>
                    <?php 
                    $users_options->data_seek(0); // Reset pointer
                    while($user = $users_options->fetch_assoc()): 
                    ?>
                    <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['name']) ?> (<?= ucfirst($user['role']) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Aplikasi</label>
                <select id="appFilter" onchange="applyFilters()">
                    <option value="">Semua Aplikasi</option>
                    <?php 
                    $apps_options->data_seek(0); // Reset pointer
                    while($app = $apps_options->fetch_assoc()): 
                    ?>
                    <option value="<?= $app['id'] ?>" <?= $app_filter == $app['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($app['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Status</label>
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="">Semua Status</option>
                    <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="done" <?= $status_filter == 'done' ? 'selected' : '' ?>>Done</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="reports-container">
        <div class="reports-header">
            <h3>Daftar Aktivitas</h3>
            <span class="records-count">
                Menampilkan <?= min($records_per_page, $total_records) ?> dari <?= $total_records ?> aktivitas
            </span>
        </div>

        <?php if ($reports_result->num_rows > 0): ?>
        <div class="reports-list">
            <?php while($report = $reports_result->fetch_assoc()): ?>
            <div class="report-item">
                <div class="report-user">
                    <div class="user-avatar" style="background: <?= getRoleColor($report['user_role']) ?>">
                        <i class="<?= getRoleIcon($report['user_role']) ?>"></i>
                    </div>
                    <div class="user-info">
                        <h4 class="user-name"><?= htmlspecialchars($report['user_name']) ?></h4>
                        <span class="user-role" style="color: <?= getRoleColor($report['user_role']) ?>">
                            <?= ucfirst($report['user_role']) ?>
                        </span>
                    </div>
                </div>

                <div class="report-content">
                    <div class="activity-description">
                        <p><?= getActivityDescription($report) ?></p>
                        <?php if (!empty($report['notes'])): ?>
                        <p class="activity-notes">
                            <i class="fas fa-sticky-note"></i>
                            <?= htmlspecialchars($report['notes']) ?>
                        </p>
                        <?php endif; ?>
                        <div class="activity-meta">
                            <span class="priority-badge" style="background: <?= getPriorityColor($report['priority']) ?>">
                                <?= ucfirst($report['priority']) ?> Priority
                            </span>
                            <span class="status-badge" style="background: <?= getStatusColor($report['status']) ?>">
                                <i class="fas fa-<?= $report['status'] == 'done' ? 'check-circle' : 'clock' ?>"></i>
                                <?= $report['status'] == 'done' ? 'Selesai' : 'In Progress' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="activity-details">
                        <div class="detail-item">
                            <i class="fas fa-cube"></i>
                            <span><?= htmlspecialchars($report['app_name']) ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= date('d M Y', strtotime($report['date'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <span><?= timeAgo($report['date']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="report-actions">
                    <button class="btn-action" onclick="viewDetails(<?= $report['id'] ?>)" title="Detail">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action" onclick="viewTodo(<?= $report['id_todos'] ?>)" title="Lihat Todo">
                        <i class="fas fa-tasks"></i>
                    </button>
                    <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_id'] == $report['user_id']): ?>
                    <button class="btn-action btn-edit" onclick="editReport(<?= $report['id'] ?>)" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination - GANTI 'page' dengan 'p' -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <span>Halaman <?= $current_page ?> dari <?= $total_pages ?></span>
            </div>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                <a href="?page=reports&p=1<?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=reports&p=<?= $current_page - 1 ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?page=reports&p=<?= $i ?><?= buildFilterQuery() ?>" 
                   class="page-btn <?= $i == $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                <a href="?page=reports&p=<?= $current_page + 1 ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=reports&p=<?= $total_pages ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-double-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Tidak ada aktivitas ditemukan</h3>
            <p>Belum ada aktivitas yang sesuai dengan filter yang diterapkan.</p>
            <button class="btn btn-primary" onclick="openAddReportModal()">
                <i class="fas fa-plus mr-2"></i>
                Tambah Laporan Pertama
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Report Modal -->
<div id="addReportModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <form method="POST" action="">
            <div class="modal-header">
                <h3>Tambah Laporan Baru</h3>
                <button type="button" class="modal-close" onclick="closeAddReportModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="todo_id">Todo/Task <span class="required">*</span></label>
                        <select name="todo_id" id="todo_id" required class="form-control">
                            <option value="">Pilih Todo/Task</option>
                            <?php while($todo = $todos_options->fetch_assoc()): ?>
                            <option value="<?= $todo['id'] ?>">
                                <?= htmlspecialchars($todo['title']) ?> 
                                (<?= htmlspecialchars($todo['app_name']) ?>) 
                                - <?= ucfirst($todo['priority']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="report_date">Tanggal <span class="required">*</span></label>
                        <input type="date" name="date" id="report_date" required class="form-control" 
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="report_status">Status <span class="required">*</span></label>
                        <select name="status" id="report_status" required class="form-control">
                            <option value="">Pilih Status</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="report_notes">Catatan</label>
                        <textarea name="notes" id="report_notes" class="form-control" 
                                  placeholder="Tambahkan catatan atau detail pekerjaan..." rows="4"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddReportModal()">
                    Batal
                </button>
                <button type="submit" name="add_report" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Simpan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Enhanced Styles for Reports Page */

/* Alert Messages */
.alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 500;
    position: relative;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-close {
    position: absolute;
    right: 16px;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: currentColor;
    opacity: 0.7;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.alert-close:hover {
    opacity: 1;
    background: rgba(0,0,0,0.1);
}

/* Page Header */
.page-header {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 12px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    font-size: 0.9rem;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
}

.btn-secondary {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
}

.btn-success {
    background: linear-gradient(90deg, #10b981, #34d399);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(90deg, #059669, #10b981);
    transform: translateY(-2px);
}

.mr-2 { margin-right: 8px; }
.mr-3 { margin-right: 12px; }

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.bg-gradient-blue { background: linear-gradient(135deg, #0066ff, #33ccff); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
.bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
.bg-gradient-purple { background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-content h3 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-content p {
    font-size: 0.9rem;
    opacity: 0.9;
    margin: 0;
}

/* Filters Section */
.filters-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filters-header h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.btn-clear-filters {
    padding: 8px 16px;
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
}

.btn-clear-filters:hover {
    background: #fecaca;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.filter-group select,
.filter-group input {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
    background: white;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

/* Reports Container */
.reports-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.reports-header {
    padding: 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reports-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.records-count {
    color: #6b7280;
    font-size: 0.9rem;
    background: #f3f4f6;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Reports List */
.reports-list {
    max-height: 800px;
    overflow-y: auto;
}

.report-item {
    display: flex;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.3s ease;
}

.report-item:hover {
    background: #f8fafc;
}

.report-user {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
    flex-shrink: 0;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.user-info h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 2px 0;
}

.user-role {
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
}

/* Report Content */
.report-content {
    flex: 1;
    margin-left: 20px;
    margin-right: 20px;
}

.activity-description p {
    font-size: 0.95rem;
    color: #1f2937;
    margin: 0 0 8px 0;
    line-height: 1.4;
}

.activity-notes {
    font-size: 0.85rem;
    color: #6b7280;
    font-style: italic;
    margin: 8px 0;
    padding: 8px 12px;
    background: #f8fafc;
    border-left: 3px solid #e2e8f0;
    border-radius: 0 8px 8px 0;
}

.activity-notes i {
    margin-right: 6px;
    color: #9ca3af;
}

.activity-meta {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.priority-badge,
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    display: flex;
    align-items: center;
    gap: 4px;
}

.activity-details {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #6b7280;
}

.detail-item i {
    width: 14px;
    font-size: 0.8rem;
}

/* Report Actions */
.report-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-action:hover {
    background: #e2e8f0;
    color: #0066ff;
    transform: scale(1.05);
}

.btn-edit:hover {
    color: #f59e0b;
}

/* Pagination */
.pagination-container {
    padding: 20px 24px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    color: #6b7280;
    font-size: 0.9rem;
}

.pagination {
    display: flex;
    gap: 8px;
}

.page-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 0.85rem;
}

.page-btn:hover {
    background: #f1f5f9;
    color: #0066ff;
}

.page-btn.active {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    border-color: transparent;
}

/* No Data State */
.no-data {
    text-align: center;
    padding: 80px 24px;
    color: #6b7280;
}

.no-data-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f3f4f6;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #9ca3af;
}

.no-data h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.no-data p {
    font-size: 0.95rem;
    margin: 0 0 20px 0;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 24px 24px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: #f3f4f6;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #e5e7eb;
    color: #374151;
}

.modal-body {
    padding: 20px 24px;
}

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.required {
    color: #ef4444;
}

.form-control {
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

@keyframes slideUp {
    from { 
        opacity: 0; 
        transform: translateY(30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .filters-grid {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .report-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
    }

    .report-user {
        min-width: auto;
        width: 100%;
    }

    .report-content {
        margin: 0;
        width: 100%;
    }

    .activity-details {
        flex-direction: column;
        gap: 8px;
    }

    .report-actions {
        width: 100%;
        justify-content: center;
    }

    .pagination-container {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }

    .pagination {
        justify-content: center;
    }

    .modal-content {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 16px;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .filters-section,
    .modal-body {
        padding: 16px;
    }

    .reports-header {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }

    .header-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<script>
// Modal functions
function openAddReportModal() {
    document.getElementById('addReportModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddReportModal() {
    document.getElementById('addReportModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal when clicking overlay
document.getElementById('addReportModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddReportModal();
    }
});

// Filter functions - PERBAIKI URL CONSTRUCTION
function applyFilters() {
    const roleFilter = document.getElementById('roleFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const userFilter = document.getElementById('userFilter').value;
    const appFilter = document.getElementById('appFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    let url = new URL(window.location);
    
    // Pastikan parameter 'page' untuk navigasi sistem tetap ada
    url.searchParams.set('page', 'reports');
    
    // Hapus parameter pagination dan filter lama
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('app');
    url.searchParams.delete('status');
    url.searchParams.delete('p'); // Reset ke halaman 1 saat filter berubah
    
    // Tambahkan filter baru jika ada
    if (roleFilter) url.searchParams.set('role', roleFilter);
    if (dateFilter) url.searchParams.set('date', dateFilter);
    if (userFilter) url.searchParams.set('user', userFilter);
    if (appFilter) url.searchParams.set('app', appFilter);
    if (statusFilter) url.searchParams.set('status', statusFilter);
    
    window.location.href = url.toString();
}

function clearAllFilters() {
    let url = new URL(window.location);
    url.searchParams.set('page', 'reports'); // Pastikan tetap di halaman reports
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('app');
    url.searchParams.delete('status');
    url.searchParams.delete('p');
    window.location.href = url.toString();
}

function refreshReport() {
    window.location.reload();
}

function exportReport() {
    // Implementasi export PDF
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    
    // Untuk sementara tampilkan alert
    headerSystem.showNotification('Fitur export PDF akan segera tersedia!', 'info');
}

function viewDetails(takenId) {
    // Navigate to detail atau buka modal
    headerSystem.showNotification('Melihat detail laporan ID: ' + takenId, 'info');
    // window.location.href = `?page=reports&detail=${takenId}`;
}

function viewTodo(todoId) {
    // Navigate to todo detail
    window.location.href = `?page=todos&todo_id=${todoId}`;
}

function editReport(reportId) {
    // Implementasi edit report
    headerSystem.showNotification('Edit laporan ID: ' + reportId, 'info');
    // Bisa buka modal edit atau navigate ke halaman edit
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#addReportModal form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const todoId = document.getElementById('todo_id').value;
            const date = document.getElementById('report_date').value;
            const status = document.getElementById('report_status').value;
            
            if (!todoId || !date || !status) {
                e.preventDefault();
                headerSystem.showNotification('Mohon lengkapi semua field yang wajib diisi!', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitBtn.disabled = true;
        });
    }
    
    // Set default date filter jika tidak ada filter
    const urlParams = new URLSearchParams(window.location.search);
    const dateFilter = document.getElementById('dateFilter');
    
    if (!urlParams.has('role') && !urlParams.has('date') && !urlParams.has('user') && 
        !urlParams.has('app') && !urlParams.has('status')) {
        // Set today as default if no filters
        const today = new Date().toISOString().split('T')[0];
        if (dateFilter && !dateFilter.value) {
            dateFilter.value = today;
        }
    }
});

// Auto-refresh setiap 5 menit
setInterval(function() {
    if (!document.getElementById('addReportModal').style.display || 
        document.getElementById('addReportModal').style.display === 'none') {
        
        // Hanya refresh jika tidak ada modal yang terbuka
        const notification = document.createElement('div');
        notification.className = 'auto-refresh-notice';
        notification.innerHTML = '<i class="fas fa-sync-alt"></i> Data diperbarui otomatis';
        notification.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            z-index: 1000;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
            window.location.reload();
        }, 2000);
    }
}, 300000); // 5 minutes

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + N untuk tambah laporan
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        openAddReportModal();
    }
    
    // Escape untuk tutup modal
    if (e.key === 'Escape') {
        closeAddReportModal();
    }
});

// Animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }
`;
document.head.appendChild(style);

console.log('✅ Reports page loaded successfully');
</script>