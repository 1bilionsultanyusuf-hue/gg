<?php
// modul/data/reports.php
// Enhanced Reports Module with Role-Based Access Control

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
    
    // Check permissions
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
    $problem = $_POST['problem'] ?? '';
    $status = $_POST['status'];
    $responsible_person = $_POST['responsible_person'] ?? '';
    
    // Check permissions
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
        if (empty($activity) || empty($status)) {
            $error = 'Semua field wajib harus diisi!';
        } else {
            $update_stmt = $koneksi->prepare("UPDATE reports SET activity = ?, problem = ?, status = ?, responsible_person = ? WHERE id = ?");
            $update_stmt->bind_param('ssssi', $activity, $problem, $status, $responsible_person, $report_id);
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
        $problem = $_POST['problem'] ?? '';
        $status = $_POST['status'];
        $responsible_person = $_POST['responsible_person'] ?? '';
        $report_date = $_POST['report_date'] ?? date('Y-m-d');
        
        // Validate required fields
        if (empty($activity) || empty($status)) {
            $error = 'Semua field wajib harus diisi!';
        } else {
            // Insert new report
            $insert_report = $koneksi->prepare("
                INSERT INTO reports (date, user_id, activity, problem, status, responsible_person) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert_report->bind_param('sissss', $report_date, $current_user_id, $activity, $problem, $status, $responsible_person);
            
            if ($insert_report->execute()) {
                $message = 'Laporan berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan laporan: ' . $koneksi->error;
            }
        }
    }
}

// Handle filters and pagination
$message = $message ?? '';
$error = $error ?? '';

// Filter parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;
$offset = max(0, $offset);

// Build WHERE conditions based on permissions
$where_conditions = [];
$params = [];
$param_types = '';

// Apply view permissions
if (!$current_permissions['can_view_all']) {
    $where_conditions[] = "r.user_id = ?";
    $params[] = $current_user_id;
    $param_types .= 'i';
}

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

if (!empty($date_filter)) {
    $where_conditions[] = "r.date = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

if (!empty($user_filter)) {
    $where_conditions[] = "r.user_id = ?";
    $params[] = $user_filter;
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM reports r
    LEFT JOIN users u ON r.user_id = u.id
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
    SELECT r.*, 
           u.name as user_name,
           u.email as user_email,
           u.role as user_role
    FROM reports r
    LEFT JOIN users u ON r.user_id = u.id
    $where_clause
    ORDER BY r.date DESC, r.created_at DESC
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

// Get filter options (only show users current user can see)
if ($current_permissions['can_view_all']) {
    $users_options = $koneksi->query("SELECT id, name, role FROM users ORDER BY name");
} else {
    $users_options = $koneksi->prepare("SELECT id, name, role FROM users WHERE id = ? ORDER BY name");
    $users_options->bind_param('i', $current_user_id);
    $users_options->execute();
    $users_options = $users_options->get_result();
}

// Get statistics for dashboard (based on permission)
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_week = date('Y-m-d', strtotime('-7 days'));

if ($current_permissions['can_view_all']) {
    $stats_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today'")->fetch_assoc()['count'];
    $stats_completed_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today' AND status = 'done'")->fetch_assoc()['count'];
    $stats_in_progress = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'in_progress'")->fetch_assoc()['count'];
    $stats_pending = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch_assoc()['count'];
} else {
    $stats_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_completed_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today' AND status = 'done' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_in_progress = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'in_progress' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_pending = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending' AND user_id = $current_user_id")->fetch_assoc()['count'];
}

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

function getStatusColor($status) {
    $colors = [
        'done' => '#10b981',
        'in_progress' => '#f59e0b',
        'pending' => '#6b7280'
    ];
    return $colors[$status] ?? '#6b7280';
}

function getStatusIcon($status) {
    $icons = [
        'done' => 'fas fa-check-circle',
        'in_progress' => 'fas fa-clock',
        'pending' => 'fas fa-pause-circle'
    ];
    return $icons[$status] ?? 'fas fa-question-circle';
}

function getStatusText($status) {
    $texts = [
        'done' => 'Selesai',
        'in_progress' => 'In Progress',
        'pending' => 'Pending'
    ];
    return $texts[$status] ?? 'Unknown';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    
    return date('d M Y', strtotime($datetime));
}

function buildFilterQuery() {
    global $role_filter, $date_filter, $user_filter, $status_filter;
    
    $params = [];
    if ($role_filter) $params[] = "role=$role_filter";
    if ($date_filter) $params[] = "date=$date_filter";
    if ($user_filter) $params[] = "user=$user_filter";
    if ($status_filter) $params[] = "status=$status_filter";
    
    return !empty($params) ? '&' . implode('&', $params) : '';
}

function canEditReport($report_user_id) {
    global $current_permissions, $current_user_id;
    
    if ($current_permissions['can_edit_all']) return true;
    if ($current_permissions['can_edit_own'] && $report_user_id == $current_user_id) return true;
    
    return false;
}

function canDeleteReport($report_user_id) {
    global $current_permissions, $current_user_id;
    
    if ($current_permissions['can_delete_all']) return true;
    if ($current_permissions['can_delete_own'] && $report_user_id == $current_user_id) return true;
    
    return false;
}
?>

<div class="main-content" style="margin-top: 80px;">
    <!-- Permission Info Banner -->
    <div class="permission-info">
        <div class="permission-content">
            <div class="permission-user">
                <div class="user-avatar" style="background: <?= getRoleColor($current_user_role) ?>">
                    <i class="<?= getRoleIcon($current_user_role) ?>"></i>
                </div>
                <div>
                    <h4><?= htmlspecialchars($current_user['name']) ?></h4>
                    <span style="color: <?= getRoleColor($current_user_role) ?>"><?= ucfirst($current_user_role) ?></span>
                </div>
            </div>
            <div class="permission-badges">
                <?php if ($current_permissions['can_view_all']): ?>
                <span class="perm-badge view-all"><i class="fas fa-eye"></i> Lihat Semua</span>
                <?php endif; ?>
                <?php if ($current_permissions['can_create']): ?>
                <span class="perm-badge create"><i class="fas fa-plus"></i> Buat Laporan</span>
                <?php endif; ?>
                <?php if ($current_permissions['can_edit_all']): ?>
                <span class="perm-badge edit-all"><i class="fas fa-edit"></i> Edit Semua</span>
                <?php elseif ($current_permissions['can_edit_own']): ?>
                <span class="perm-badge edit-own"><i class="fas fa-edit"></i> Edit Milik Sendiri</span>
                <?php endif; ?>
                <?php if ($current_permissions['can_delete_all']): ?>
                <span class="perm-badge delete-all"><i class="fas fa-trash"></i> Hapus Semua</span>
                <?php elseif ($current_permissions['can_delete_own']): ?>
                <span class="perm-badge delete-own"><i class="fas fa-trash"></i> Hapus Milik Sendiri</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= htmlspecialchars($error) ?>
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
                <?= $current_permissions['can_view_all'] ? 'Monitor dan analisis aktivitas semua pengguna dalam sistem' : 'Monitor dan kelola laporan aktivitas Anda' ?>
            </p>
        </div>
        <div class="header-actions">
            <?php if ($current_permissions['can_create']): ?>
            <button class="btn btn-success" onclick="openAddReportModal()">
                <i class="fas fa-plus mr-2"></i>
                Tambah Laporan
            </button>
            <?php endif; ?>
            <?php if ($current_permissions['can_view_all']): ?>
            <button class="btn btn-secondary" onclick="exportReport()">
                <i class="fas fa-download mr-2"></i>
                Export PDF
            </button>
            <?php endif; ?>
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
                <p class="stat-label"><?= $current_permissions['can_view_all'] ? 'Aktivitas Hari Ini' : 'Aktivitas Saya Hari Ini' ?></p>
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
                <i class="fas fa-pause-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_pending ?></h3>
                <p class="stat-label">Pending</p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <?php if ($current_permissions['can_view_all']): ?>
    <div class="filters-section">
        <div class="filters-header">
            <h3>Filter Laporan</h3>
            <?php if($role_filter || $date_filter || $user_filter || $status_filter): ?>
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
                <label>Status</label>
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="done" <?= $status_filter == 'done' ? 'selected' : '' ?>>Done</option>
                </select>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                        <p><strong>Aktivitas:</strong> <?= htmlspecialchars($report['activity']) ?></p>
                        <?php if (!empty($report['problem'])): ?>
                        <div class="activity-notes">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><strong>Problem:</strong> <?= htmlspecialchars($report['problem']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($report['responsible_person'])): ?>
                        <div class="responsible-info">
                            <i class="fas fa-user-shield"></i>
                            <span><strong>Penanggung Jawab:</strong> <?= htmlspecialchars($report['responsible_person']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="activity-meta">
                            <span class="status-badge" style="background: <?= getStatusColor($report['status']) ?>">
                                <i class="<?= getStatusIcon($report['status']) ?>"></i>
                                <?= getStatusText($report['status']) ?>
                            </span>
                            <?php if ($report['user_id'] == $current_user_id): ?>
                            <span class="owner-badge">
                                <i class="fas fa-user"></i>
                                Milik Anda
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="activity-details">
                        <div class="detail-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= date('d M Y', strtotime($report['date'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <span><?= timeAgo($report['created_at']) ?></span>
                        </div>
                        <?php if (!empty($report['user_email']) && $current_permissions['can_view_all']): ?>
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($report['user_email']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="report-actions">
                    <button class="btn-action" onclick="viewDetails(<?= $report['id'] ?>)" title="Detail">
                        <i class="fas fa-eye"></i>
                    </button>
                    <?php if (canEditReport($report['user_id'])): ?>
                    <button class="btn-action btn-edit" onclick="editReport(<?= $report['id'] ?>, '<?= htmlspecialchars(addslashes($report['activity'])) ?>', '<?= htmlspecialchars(addslashes($report['problem'])) ?>', '<?= $report['status'] ?>', '<?= htmlspecialchars(addslashes($report['responsible_person'] ?? '')) ?>')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php endif; ?>
                    <?php if (canDeleteReport($report['user_id'])): ?>
                    <button class="btn-action btn-delete" onclick="deleteReport(<?= $report['id'] ?>)" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
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
                <a href="?module=reports&page=1<?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?module=reports&page=<?= $page - 1 ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?module=reports&page=<?= $i ?><?= buildFilterQuery() ?>" 
                   class="page-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a href="?module=reports&page=<?= $page + 1 ?><?= buildFilterQuery() ?>" class="page-btn">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?module=reports&page=<?= $total_pages ?><?= buildFilterQuery() ?>" class="page-btn">
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

<!-- Add Report Modal -->
<?php if ($current_permissions['can_create']): ?>
<div id="addReportModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>
                <i class="fas fa-plus mr-2"></i>
                Tambah Laporan Aktivitas
            </h3>
            <button class="modal-close" onclick="closeAddReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" onsubmit="return validateAddReportForm()">
            <input type="hidden" name="add_report" value="1">
            
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option value="">Pilih Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Selesai</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="report_date">Tanggal</label>
                        <input type="date" id="report_date" name="report_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="responsible_person">Penanggung Jawab</label>
                        <input type="text" id="responsible_person" name="responsible_person" 
                               placeholder="Nama penanggung jawab (opsional)...">
                    </div>

                    <div class="form-group full-width">
                        <label for="activity">Aktivitas <span class="required">*</span></label>
                        <textarea id="activity" name="activity" rows="4" required
                                  placeholder="Jelaskan aktivitas yang dilakukan..."></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="problem">Problem/Kendala</label>
                        <textarea id="problem" name="problem" rows="3" 
                                  placeholder="Jelaskan problem atau kendala yang dihadapi (opsional)..."></textarea>
                    </div>
                </div>

                <div class="selected-info" id="selectedInfo" style="display: none;">
                    <h4>Ringkasan Laporan:</h4>
                    <div id="summaryContent"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddReportModal()">
                    <i class="fas fa-times mr-2"></i>
                    Batal
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-2"></i>
                    Simpan Laporan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Edit Report Modal -->
<div id="editReportModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>
                <i class="fas fa-edit mr-2"></i>
                Edit Laporan Aktivitas
            </h3>
            <button class="modal-close" onclick="closeEditReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" onsubmit="return validateEditReportForm()">
            <input type="hidden" name="edit_report" value="1">
            <input type="hidden" name="report_id" id="edit_report_id">
            
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_status">Status <span class="required">*</span></label>
                        <select id="edit_status" name="status" required>
                            <option value="">Pilih Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Selesai</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_responsible_person">Penanggung Jawab</label>
                        <input type="text" id="edit_responsible_person" name="responsible_person" 
                               placeholder="Nama penanggung jawab (opsional)...">
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_activity">Aktivitas <span class="required">*</span></label>
                        <textarea id="edit_activity" name="activity" rows="4" required
                                  placeholder="Jelaskan aktivitas yang dilakukan..."></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="edit_problem">Problem/Kendala</label>
                        <textarea id="edit_problem" name="problem" rows="3" 
                                  placeholder="Jelaskan problem atau kendala yang dihadapi (opsional)..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditReportModal()">
                    <i class="fas fa-times mr-2"></i>
                    Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Perbarui Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>
                <i class="fas fa-exclamation-triangle mr-2" style="color: #ef4444;"></i>
                Konfirmasi Hapus
            </h3>
            <button class="modal-close" onclick="closeDeleteConfirmModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus laporan ini?</p>
            <p style="color: #6b7280; font-size: 0.9rem;">Tindakan ini tidak dapat dibatalkan.</p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteConfirmModal()">
                <i class="fas fa-times mr-2"></i>
                Batal
            </button>
            <button type="button" class="btn btn-danger" onclick="confirmDeleteReport()">
                <i class="fas fa-trash mr-2"></i>
                Hapus Laporan
            </button>
        </div>
    </div>
</div>

<style>
/* Permission Info Banner */
.permission-info {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.permission-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.permission-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.permission-user .user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.permission-user h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 2px 0;
}

.permission-user span {
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
}

.permission-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.perm-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.perm-badge.view-all { background: #3b82f6; }
.perm-badge.create { background: #10b981; }
.perm-badge.edit-all { background: #f59e0b; }
.perm-badge.edit-own { background: #fbbf24; }
.perm-badge.delete-all { background: #ef4444; }
.perm-badge.delete-own { background: #f87171; }

.owner-badge {
    padding: 4px 8px;
    background: #0066ff;
    color: white;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Responsible Person Info */
.responsible-info {
    background: #e0f2fe;
    border: 1px solid #b3e5fc;
    border-radius: 6px;
    padding: 8px 12px;
    margin: 8px 0;
    font-size: 0.85rem;
    color: #0277bd;
    display: flex;
    align-items: center;
    gap: 8px;
}

.responsible-info i {
    color: #0288d1;
}

/* Page Layout */
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

/* Alert Messages */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    font-weight: 500;
    animation: slideInDown 0.3s ease;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Button Styles */
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

.btn-danger {
    background: linear-gradient(90deg, #ef4444, #f87171);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(90deg, #dc2626, #ef4444);
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

.activity-notes {
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 6px;
    padding: 8px 12px;
    margin: 8px 0;
    font-size: 0.85rem;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.activity-notes i {
    color: #f59e0b;
}

.activity-meta {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

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

.btn-delete:hover {
    color: #ef4444;
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
    display: flex;
    align-items: center;
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
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
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

.form-group select,
.form-group input,
.form-group textarea {
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

/* Selected Info */
.selected-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 16px;
    margin-top: 20px;
}

.selected-info h4 {
    color: #0c4a6e;
    font-size: 1rem;
    margin: 0 0 12px 0;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    border-bottom: 1px solid #e0f2fe;
    font-size: 0.85rem;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: #64748b;
    font-weight: 500;
}

.summary-value {
    color: #1e293b;
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

/* Animations */
@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(100%); }
    to { opacity: 1; transform: translateX(0); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .permission-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }

    .header-actions {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
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

    .modal-footer {
        flex-direction: column;
        gap: 12px;
    }

    .modal-footer .btn {
        width: 100%;
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

    .header-actions {
        flex-direction: column;
        width: 100%;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .permission-badges {
        justify-content: center;
    }
}
</style>

<script>
// Global variables for delete confirmation
let deleteReportId = null;

// Filter functions - Fixed to properly handle URL construction
function applyFilters() {
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const dateFilter = document.getElementById('dateFilter')?.value || '';
    const userFilter = document.getElementById('userFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    
    // Use module parameter to maintain current module
    let url = new URL(window.location);
    url.searchParams.set('module', 'reports'); // Ensure we stay in reports module
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('status');
    url.searchParams.delete('page');
    
    if (roleFilter) url.searchParams.set('role', roleFilter);
    if (dateFilter) url.searchParams.set('date', dateFilter);
    if (userFilter) url.searchParams.set('user', userFilter);
    if (statusFilter) url.searchParams.set('status', statusFilter);
    
    window.location.href = url.toString();
}

function clearAllFilters() {
    let url = new URL(window.location);
    url.searchParams.set('module', 'reports'); // Keep the module parameter
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('status');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function refreshReport() {
    window.location.reload();
}

function exportReport() {
    alert('Fitur export PDF akan segera tersedia!');
}

// Add Report Modal Functions
function openAddReportModal() {
    const modal = document.getElementById('addReportModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        updateSummary();
    }
}

function closeAddReportModal() {
    const modal = document.getElementById('addReportModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form
        document.querySelector('#addReportModal form').reset();
        const selectedInfo = document.getElementById('selectedInfo');
        if (selectedInfo) selectedInfo.style.display = 'none';
        
        // Set current date
        const dateField = document.getElementById('report_date');
        if (dateField) dateField.value = new Date().toISOString().split('T')[0];
    }
}

function validateAddReportForm() {
    const activity = document.getElementById('activity').value;
    const status = document.getElementById('status').value;
    
    if (!activity || !status) {
        alert('Harap lengkapi semua field yang wajib diisi!');
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin menyimpan laporan ini?');
}

// Edit Report Modal Functions - Updated to include responsible person parameter
function editReport(reportId, activity, problem, status, responsiblePerson = '') {
    const modal = document.getElementById('editReportModal');
    if (modal) {
        document.getElementById('edit_report_id').value = reportId;
        document.getElementById('edit_activity').value = activity;
        document.getElementById('edit_problem').value = problem;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_responsible_person').value = responsiblePerson;
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeEditReportModal() {
    const modal = document.getElementById('editReportModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form
        document.querySelector('#editReportModal form').reset();
    }
}

function validateEditReportForm() {
    const activity = document.getElementById('edit_activity').value;
    const status = document.getElementById('edit_status').value;
    
    if (!activity || !status) {
        alert('Harap lengkapi semua field yang wajib diisi!');
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin memperbarui laporan ini?');
}

// Delete Report Functions
function deleteReport(reportId) {
    deleteReportId = reportId;
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeDeleteConfirmModal() {
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        deleteReportId = null;
    }
}

function confirmDeleteReport() {
    if (deleteReportId) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_report';
        deleteInput.value = '1';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'report_id';
        idInput.value = deleteReportId;
        
        form.appendChild(deleteInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        
        form.submit();
    }
    closeDeleteConfirmModal();
}

// Update summary when form changes - Updated to include responsible person
function updateSummary() {
    const activityField = document.getElementById('activity');
    const statusSelect = document.getElementById('status');
    const problemField = document.getElementById('problem');
    const responsibleField = document.getElementById('responsible_person');
    const selectedInfo = document.getElementById('selectedInfo');
    const summaryContent = document.getElementById('summaryContent');
    
    if (!activityField || !statusSelect || !problemField || !selectedInfo || !summaryContent || !responsibleField) {
        return;
    }
    
    function generateSummary() {
        const activity = activityField.value;
        const status = statusSelect.value;
        const problem = problemField.value;
        const responsible = responsibleField.value;
        
        if (activity && status) {
            const statusText = status === 'done' ? 'Selesai' : (status === 'in_progress' ? 'In Progress' : 'Pending');
            
            let summary = `
                <div class="summary-item">
                    <span class="summary-label">Aktivitas:</span>
                    <span class="summary-value">${activity.substring(0, 50)}${activity.length > 50 ? '...' : ''}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Status:</span>
                    <span class="summary-value">${statusText}</span>
                </div>
            `;
            
            if (responsible) {
                summary += `
                    <div class="summary-item">
                        <span class="summary-label">Penanggung Jawab:</span>
                        <span class="summary-value">${responsible.substring(0, 30)}${responsible.length > 30 ? '...' : ''}</span>
                    </div>
                `;
            }
            
            if (problem) {
                summary += `
                    <div class="summary-item">
                        <span class="summary-label">Problem:</span>
                        <span class="summary-value">${problem.substring(0, 50)}${problem.length > 50 ? '...' : ''}</span>
                    </div>
                `;
            }
            
            summaryContent.innerHTML = summary;
            selectedInfo.style.display = 'block';
        } else {
            selectedInfo.style.display = 'none';
        }
    }
    
    // Add event listeners
    activityField.addEventListener('input', generateSummary);
    statusSelect.addEventListener('change', generateSummary);
    problemField.addEventListener('input', generateSummary);
    responsibleField.addEventListener('input', generateSummary);
}

// View functions
function viewDetails(reportId) {
    alert('Detail untuk Report ID: ' + reportId + '\nFitur detail akan segera tersedia!');
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
    
    window.location.reload();
}, 300000);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set today as default date if no filters are applied
    const dateFilter = document.getElementById('dateFilter');
    const urlParams = new URLSearchParams(window.location.search);
    
    if (dateFilter && !urlParams.has('date') && !urlParams.has('role') && !urlParams.has('user') && !urlParams.has('status')) {
        const today = new Date().toISOString().split('T')[0];
        dateFilter.value = today;
    }
    
    // Set current date for add report form
    const reportDate = document.getElementById('report_date');
    if (reportDate) {
        reportDate.value = new Date().toISOString().split('T')[0];
    }
    
    // Initialize summary update
    updateSummary();
    
    // Close modals when clicking outside
    const modals = ['addReportModal', 'editReportModal', 'deleteConfirmModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    if (modalId === 'addReportModal') closeAddReportModal();
                    else if (modalId === 'editReportModal') closeEditReportModal();
                    else if (modalId === 'deleteConfirmModal') closeDeleteConfirmModal();
                }
            });
        }
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideInDown 0.3s ease reverse';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N to open add report modal (only if user can create)
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const modal = document.getElementById('addReportModal');
        if (modal) {
            openAddReportModal();
        }
    }
    
    // Escape to close any open modal
    if (e.key === 'Escape') {
        const modals = ['addReportModal', 'editReportModal', 'deleteConfirmModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && modal.style.display === 'flex') {
                if (modalId === 'addReportModal') closeAddReportModal();
                else if (modalId === 'editReportModal') closeEditReportModal();
                else if (modalId === 'deleteConfirmModal') closeDeleteConfirmModal();
            }
        });
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>