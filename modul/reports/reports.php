<?php
// modul/data/reports.php
// Handle filters and pagination
$message = '';
$error = '';

// Filter parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$app_filter = isset($_GET['app']) ? $_GET['app'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination - perbaiki bagian ini
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Pastikan page minimal 1
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Pastikan offset tidak negatif
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

// Get report data - ganti query ini
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
?>

<div class="main-content" style="margin-top: 80px;">
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
                    <?php while($user = $users_options->fetch_assoc()): ?>
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
                    <?php while($app = $apps_options->fetch_assoc()): ?>
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
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <span>Halaman <?= $page ?> dari <?= $total_pages ?></span>
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=1<?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?= $page - 1 ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?page=<?= $i ?><?= buildFilterQuery() ?>" 
                   class="page-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?= $total_pages ?><?= buildFilterQuery() ?>" class="page-btn">
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
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
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

<style>
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

.reports-list::-webkit-scrollbar {
    width: 6px;
}

.reports-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.reports-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
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

.report-item:last-child {
    border-bottom: none;
}

/* Report User */
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
    margin: 0;
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

    .filters-section {
        padding: 16px;
    }

    .reports-header {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
}
</style>

<script>
// Filter functions
function applyFilters() {
    const roleFilter = document.getElementById('roleFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const userFilter = document.getElementById('userFilter').value;
    const appFilter = document.getElementById('appFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('app');
    url.searchParams.delete('status');
    url.searchParams.delete('page'); // Reset to page 1 when filtering
    
    if (roleFilter) url.searchParams.set('role', roleFilter);
    if (dateFilter) url.searchParams.set('date', dateFilter);
    if (userFilter) url.searchParams.set('user', userFilter);
    if (appFilter) url.searchParams.set('app', appFilter);
    if (statusFilter) url.searchParams.set('status', statusFilter);
    
    window.location.href = url.toString();
}

function clearAllFilters() {
    let url = new URL(window.location);
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('app');
    url.searchParams.delete('status');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function refreshReport() {
    window.location.reload();
}

function exportReport() {
    // Create export URL with current filters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    
    // You can implement PDF export functionality here
    alert('Fitur export PDF akan segera tersedia!');
}

function viewDetails(takenId) {
    // Open modal or navigate to detail page
    alert('Detail untuk ID: ' + takenId);
}

function viewTodo(todoId) {
    // Navigate to todo detail or open modal
    alert('Todo ID: ' + todoId);
}

// Auto-refresh every 5 minutes
setInterval(function() {
    const lastUpdate = document.createElement('div');
    lastUpdate.className = 'auto-refresh-notice';
    lastUpdate.innerHTML = '<i class="fas fa-sync-alt"></i> Data diperbarui otomatis';
    lastUpdate.style.cssText = `
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
    
    document.body.appendChild(lastUpdate);
    
    setTimeout(() => {
        lastUpdate.remove();
    }, 3000);
    
    // Refresh page silently
    window.location.reload();
}, 300000); // 5 minutes

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }
`;
document.head.appendChild(style);

// Initialize filters on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set today as default date if no filters are applied
    const dateFilter = document.getElementById('dateFilter');
    const urlParams = new URLSearchParams(window.location.search);
    
    if (!urlParams.has('date') && !urlParams.has('role') && !urlParams.has('user') && !urlParams.has('app') && !urlParams.has('status')) {
        const today = new Date().toISOString().split('T')[0];
        dateFilter.value = today;
    }
});
</script>

<!-- Detail Modal Template (for future implementation) -->
<div id="detailModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Detail Aktivitas</h3>
            <button class="modal-close" onclick="closeDetailModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="detailContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">
                Tutup
            </button>
        </div>
    </div>
</div>

<style>
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
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
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
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Additional utility classes for enhanced functionality */
.activity-status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.status-done { background: #10b981; }
.status-in-progress { background: #f59e0b; }

.loading-skeleton {
    background: linear-gradient(90deg, #f3f4f6, #e5e7eb, #f3f4f6);
    background-size: 200px 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

/* Print styles for reports */
@media print {
    .page-header .header-actions,
    .filters-section,
    .pagination-container,
    .report-actions {
        display: none !important;
    }
    
    .main-content {
        margin-top: 0 !important;
    }
    
    .report-item {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>