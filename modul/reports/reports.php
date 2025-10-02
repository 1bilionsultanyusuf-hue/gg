<?php
// modul/data/reports.php
// Enhanced Reports Module with Consistent Design

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
    $problem = $_POST['problem'] ?? '';
    $status = $_POST['status'];
    $responsible_person = $_POST['responsible_person'] ?? '';
    
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
        
        if (empty($activity) || empty($status)) {
            $error = 'Semua field wajib harus diisi!';
        } else {
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

// Filter parameters
$message = $message ?? '';
$error = $error ?? '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$limit = 10;
$page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE conditions
$where_conditions = [];
$params = [];
$param_types = '';

if (!$current_permissions['can_view_all']) {
    $where_conditions[] = "r.user_id = ?";
    $params[] = $current_user_id;
    $param_types .= 'i';
}

if (!empty($search)) {
    $where_conditions[] = "(r.activity LIKE ? OR r.problem LIKE ? OR r.responsible_person LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'sss';
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

// Get total count
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

$total_pages = ceil($total_records / $limit);

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

$pagination_params = $params;
$pagination_param_types = $param_types;
$pagination_params[] = $limit;
$pagination_params[] = $offset;
$pagination_param_types .= 'ii';

$reports_stmt = $koneksi->prepare($reports_query);
if (!empty($pagination_params) && $pagination_param_types) {
    $reports_stmt->bind_param($pagination_param_types, ...$pagination_params);
}
$reports_stmt->execute();
$reports_result = $reports_stmt->get_result();

// Get filter options
if ($current_permissions['can_view_all']) {
    $users_options = $koneksi->query("SELECT id, name, role FROM users ORDER BY name");
} else {
    $users_options = $koneksi->prepare("SELECT id, name, role FROM users WHERE id = ? ORDER BY name");
    $users_options->bind_param('i', $current_user_id);
    $users_options->execute();
    $users_options = $users_options->get_result();
}

// Get statistics
$today = date('Y-m-d');

if ($current_permissions['can_view_all']) {
    $stats_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today'")->fetch_assoc()['count'];
    $stats_done = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'done'")->fetch_assoc()['count'];
    $stats_in_progress = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'in_progress'")->fetch_assoc()['count'];
    $stats_pending = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch_assoc()['count'];
} else {
    $stats_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_done = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'done' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_in_progress = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'in_progress' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_pending = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending' AND user_id = $current_user_id")->fetch_assoc()['count'];
}

// Helper functions
function getRoleColor($role) {
    $colors = ['admin' => '#dc2626', 'client' => '#7c3aed', 'programmer' => '#0066ff', 'support' => '#10b981'];
    return $colors[$role] ?? '#6b7280';
}

function getRoleIcon($role) {
    $icons = ['admin' => 'fas fa-crown', 'client' => 'fas fa-briefcase', 'programmer' => 'fas fa-code', 'support' => 'fas fa-headset'];
    return $icons[$role] ?? 'fas fa-user';
}

function getStatusColor($status) {
    $colors = ['done' => '#10b981', 'in_progress' => '#f59e0b', 'pending' => '#6b7280'];
    return $colors[$status] ?? '#6b7280';
}

function getStatusIcon($status) {
    $icons = ['done' => 'fas fa-check-circle', 'in_progress' => 'fas fa-clock', 'pending' => 'fas fa-pause-circle'];
    return $icons[$status] ?? 'fas fa-question-circle';
}

function getStatusText($status) {
    $texts = ['done' => 'Selesai', 'in_progress' => 'In Progress', 'pending' => 'Pending'];
    return $texts[$status] ?? 'Unknown';
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
    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-chart-line mr-3"></i>
                Manajemen Laporan
            </h1>
            <p class="page-subtitle">
                Kelola dan monitor semua laporan aktivitas dalam sistem
            </p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_today ?></h3>
                <p class="stat-label">Laporan Hari Ini</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green <?= $status_filter == 'done' ? 'active' : '' ?>" onclick="filterByStatus('done')">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_done ?></h3>
                <p class="stat-label">Selesai</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange <?= $status_filter == 'in_progress' ? 'active' : '' ?>" onclick="filterByStatus('in_progress')">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_in_progress ?></h3>
                <p class="stat-label">In Progress</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-purple <?= $status_filter == 'pending' ? 'active' : '' ?>" onclick="filterByStatus('pending')">
            <div class="stat-icon">
                <i class="fas fa-pause-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $stats_pending ?></h3>
                <p class="stat-label">Pending</p>
            </div>
        </div>
    </div>

    <!-- Reports Container -->
    <div class="reports-container">
        <div class="section-header">
            <div class="section-title-container">
                <h2 class="section-title">Daftar Laporan</h2>
                <span class="section-count"><?= $total_records ?> laporan</span>
            </div>
            
            <!-- Filters -->
            <div class="filters-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cari aktivitas, problem, atau PJ..." 
                           value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
                </div>
                
                <?php if ($current_permissions['can_view_all']): ?>
                <div class="filter-dropdown">
                    <select id="roleFilter" onchange="applyFilters()">
                        <option value="">Semua Role</option>
                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="client" <?= $role_filter == 'client' ? 'selected' : '' ?>>Client</option>
                        <option value="programmer" <?= $role_filter == 'programmer' ? 'selected' : '' ?>>Programmer</option>
                        <option value="support" <?= $role_filter == 'support' ? 'selected' : '' ?>>Support</option>
                    </select>
                </div>

                <div class="filter-dropdown">
                    <select id="userFilter" onchange="applyFilters()">
                        <option value="">Semua Pengguna</option>
                        <?php 
                        $users_options->data_seek(0);
                        while($user = $users_options->fetch_assoc()): 
                        ?>
                        <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-dropdown">
                    <input type="date" id="dateFilter" value="<?= htmlspecialchars($date_filter) ?>" onchange="applyFilters()">
                </div>

                <div class="filter-dropdown">
                    <select id="statusFilter" onchange="applyFilters()">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="done" <?= $status_filter == 'done' ? 'selected' : '' ?>>Done</option>
                    </select>
                </div>
                
                <?php if ($role_filter || $date_filter || $user_filter || $status_filter || $search): ?>
                <button class="btn-clear-filter" onclick="clearFilters()" title="Hapus Filter">
                    <i class="fas fa-times"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add New Report Button -->
        <?php if ($current_permissions['can_create']): ?>
        <div class="report-list-item add-new-item" onclick="openAddReportModal()">
            <div class="add-new-content">
                <div class="add-new-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="add-new-text">
                    <h3>Tambah Laporan Baru</h3>
                    <p>Klik untuk menambahkan laporan aktivitas baru</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Reports List -->
        <div class="reports-list">
            <?php if ($reports_result->num_rows > 0): ?>
                <?php $no = $offset + 1; while($report = $reports_result->fetch_assoc()): ?>
                <div class="report-list-item">
                    <div class="report-status-indicator status-<?= $report['status'] ?>"></div>
                    
                    <div class="report-user-section">
                        <div class="user-avatar" style="background: <?= getRoleColor($report['user_role']) ?>">
                            <i class="<?= getRoleIcon($report['user_role']) ?>"></i>
                        </div>
                        <div class="report-number"><?= $no++ ?></div>
                    </div>
                    
                    <div class="report-list-content">
                        <div class="report-header-row">
                            <div class="report-user-info">
                                <h4 class="user-name"><?= htmlspecialchars($report['user_name']) ?></h4>
                                <span class="user-role" style="color: <?= getRoleColor($report['user_role']) ?>">
                                    <?= ucfirst($report['user_role']) ?>
                                </span>
                            </div>
                            <div class="report-status-badge">
                                <span class="status-badge status-<?= $report['status'] ?>">
                                    <i class="<?= getStatusIcon($report['status']) ?>"></i>
                                    <?= getStatusText($report['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="report-activity">
                            <p><strong>Aktivitas:</strong> <?= htmlspecialchars($report['activity']) ?></p>
                        </div>
                        
                        <?php if (!empty($report['problem'])): ?>
                        <div class="report-problem">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><strong>Problem:</strong> <?= htmlspecialchars($report['problem']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($report['responsible_person'])): ?>
                        <div class="report-responsible">
                            <i class="fas fa-user-shield"></i>
                            <span><strong>PJ:</strong> <?= htmlspecialchars($report['responsible_person']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="report-details">
                            <span class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <?= date('d M Y', strtotime($report['date'])) ?>
                            </span>
                            <span class="detail-item">
                                <i class="fas fa-clock"></i>
                                <?= date('H:i', strtotime($report['created_at'])) ?>
                            </span>
                            <?php if ($report['user_id'] == $current_user_id): ?>
                            <span class="owner-badge">
                                <i class="fas fa-user"></i>
                                Milik Anda
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="report-list-actions">
                        <?php if (canEditReport($report['user_id'])): ?>
                        <button class="action-btn-small edit" 
                                onclick="editReport(<?= $report['id'] ?>, '<?= htmlspecialchars(addslashes($report['activity'])) ?>', '<?= htmlspecialchars(addslashes($report['problem'])) ?>', '<?= $report['status'] ?>', '<?= htmlspecialchars(addslashes($report['responsible_person'] ?? '')) ?>')" 
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if (canDeleteReport($report['user_id'])): ?>
                        <button class="action-btn-small delete" 
                                onclick="deleteReport(<?= $report['id'] ?>, '<?= htmlspecialchars(addslashes($report['activity'])) ?>')" 
                                title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Tidak ada laporan ditemukan</h3>
                    <p>Tidak ada laporan yang sesuai dengan filter yang diterapkan.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php
                $query_params = ['page' => 'reports'];
                if (!empty($search)) $query_params['search'] = $search;
                if (!empty($role_filter)) $query_params['role'] = $role_filter;
                if (!empty($date_filter)) $query_params['date'] = $date_filter;
                if (!empty($user_filter)) $query_params['user'] = $user_filter;
                if (!empty($status_filter)) $query_params['status'] = $status_filter;
                $query_string = '&' . http_build_query($query_params);
                ?>
                
                <?php if ($page > 1): ?>
                <a href="?page_num=1<?= $query_string ?>" class="pagination-btn">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page_num=<?= $page - 1 ?><?= $query_string ?>" class="pagination-btn">
                    <i class="fas fa-angle-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1) echo '<span class="pagination-dots">...</span>';
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?page_num=<?= $i ?><?= $query_string ?>" 
                   class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($end < $total_pages) echo '<span class="pagination-dots">...</span>'; ?>

                <?php if ($page < $total_pages): ?>
                <a href="?page_num=<?= $page + 1 ?><?= $query_string ?>" class="pagination-btn">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page_num=<?= $total_pages ?><?= $query_string ?>" class="pagination-btn">
                    <i class="fas fa-angle-double-right"></i>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="pagination-info">
                Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_records) ?> dari <?= $total_records ?> data
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Report Modal -->
<div id="reportModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Laporan Baru</h3>
            <button class="modal-close" onclick="closeReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="reportForm" method="POST" action="?page=reports">
                <input type="hidden" id="reportId" name="report_id">
                
                <div class="form-group">
                    <label for="reportStatus">Status *</label>
                    <select id="reportStatus" name="status" required>
                        <option value="">Pilih Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress" selected>In Progress</option>
                        <option value="done">Selesai</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reportDate">Tanggal</label>
                    <input type="date" id="reportDate" name="report_date" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="responsiblePerson">Penanggung Jawab</label>
                    <input type="text" id="responsiblePerson" name="responsible_person" 
                           placeholder="Nama penanggung jawab (opsional)">
                </div>
                
                <div class="form-group">
                    <label for="reportActivity">Aktivitas *</label>
                    <textarea id="reportActivity" name="activity" rows="4" required
                              placeholder="Jelaskan aktivitas yang dilakukan..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="reportProblem">Problem/Kendala</label>
                    <textarea id="reportProblem" name="problem" rows="3" 
                              placeholder="Jelaskan problem atau kendala (opsional)..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeReportModal()">
                Batal
            </button>
            <button type="submit" id="submitBtn" form="reportForm" name="add_report" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content delete-modal">
        <div class="modal-header">
            <div class="delete-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Konfirmasi Hapus</h3>
            <p id="deleteMessage">Apakah Anda yakin ingin menghapus laporan ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <form id="deleteForm" method="POST" action="?page=reports" style="display: inline;">
                <input type="hidden" id="deleteReportId" name="report_id">
                <button type="submit" name="delete_report" class="btn btn-danger">
                    <i class="fas fa-trash mr-2"></i>Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Alert Messages */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Page Header */
.page-header {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
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

.mr-2 { margin-right: 8px; }
.mr-3 { margin-right: 12px; }

/* Buttons */
.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,102,255,0.3);
}

.btn-secondary {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
}

.btn-danger {
    background: linear-gradient(90deg, #ef4444, #dc2626);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(90deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    position: relative;
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.15);
}

.stat-card.active {
    border-color: rgba(255,255,255,0.8);
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
}

.stat-card.active::after {
    content: '✓';
    position: absolute;
    top: 12px;
    right: 12px;
    color: white;
    font-size: 1.2rem;
    font-weight: bold;
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

/* Reports Container */
.reports-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.section-header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.section-title-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.section-count {
    color: #6b7280;
    font-size: 0.9rem;
    background: #f3f4f6;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Filters Container */
.filters-container {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    min-width: 250px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.9rem;
}

.search-box input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.search-box input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
}

.filter-dropdown select,
.filter-dropdown input {
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    min-width: 150px;
    transition: all 0.3s ease;
}

.filter-dropdown select:focus,
.filter-dropdown input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
}

.btn-clear-filter {
    width: 36px;
    height: 36px;
    border: 1px solid #dc2626;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-clear-filter:hover {
    background: #fecaca;
    transform: scale(1.1);
}

/* Reports List */
.reports-list {
    max-height: 600px;
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

.report-list-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    position: relative;
}

.report-list-item:hover {
    background: #f8fafc;
}

.report-list-item:last-child {
    border-bottom: none;
}

/* Add New Item */
.add-new-item {
    border: 2px dashed #d1d5db !important;
    background: #f9fafb !important;
    margin: 16px 24px;
    border-radius: 12px;
    justify-content: center;
    cursor: pointer;
}

.add-new-item:hover {
    border-color: #0066ff !important;
    background: #eff6ff !important;
}

.add-new-content {
    display: flex;
    align-items: center;
    gap: 16px;
    color: #6b7280;
}

.add-new-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.add-new-text h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #374151;
}

.add-new-text p {
    font-size: 0.85rem;
    margin: 0;
    color: #9ca3af;
}

/* Report Status Indicator */
.report-status-indicator {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.report-status-indicator.status-done {
    background: linear-gradient(180deg, #10b981, #34d399);
}

.report-status-indicator.status-in_progress {
    background: linear-gradient(180deg, #f59e0b, #fbbf24);
}

.report-status-indicator.status-pending {
    background: linear-gradient(180deg, #6b7280, #9ca3af);
}

/* Report User Section */
.report-user-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 16px;
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
    margin-bottom: 8px;
}

.report-number {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    background: #f3f4f6;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Report List Content */
.report-list-content {
    flex: 1;
    min-width: 0;
}

.report-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    gap: 12px;
}

.report-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.user-role {
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
}

.report-status-badge .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.status-badge.status-done {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.status-badge.status-in_progress {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.status-badge.status-pending {
    background: linear-gradient(90deg, #6b7280, #9ca3af);
}

.report-activity {
    color: #1f2937;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 8px;
}

.report-problem {
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

.report-problem i {
    color: #f59e0b;
}

.report-responsible {
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

.report-responsible i {
    color: #0288d1;
}

.report-details {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #6b7280;
    flex-wrap: wrap;
    margin-top: 8px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-item i {
    width: 14px;
    font-size: 0.8rem;
}

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

/* Report List Actions */
.report-list-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.report-list-item:hover .report-list-actions {
    opacity: 1;
}

.action-btn-small {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.action-btn-small:hover {
    transform: scale(1.1);
}

.action-btn-small.edit:hover {
    background: #dbeafe;
    color: #2563eb;
}

.action-btn-small.delete:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* No Data State */
.no-data {
    text-align: center;
    padding: 60px 24px;
    color: #6b7280;
}

.no-data-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f3f4f6;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #9ca3af;
}

.no-data h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.no-data p {
    font-size: 0.9rem;
    margin: 0;
}

/* Pagination */
.pagination-container {
    padding: 24px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-btn {
    min-width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pagination-btn:hover {
    background: #f8fafc;
    border-color: #0066ff;
    color: #0066ff;
    transform: translateY(-2px);
}

.pagination-btn.active {
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: white;
    border-color: #0066ff;
}

.pagination-dots {
    color: #9ca3af;
    padding: 0 8px;
    font-weight: 500;
}

.pagination-info {
    color: #6b7280;
    font-size: 0.9rem;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

.modal.show {
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

.delete-modal {
    max-width: 400px;
    text-align: center;
}

.delete-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
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
}

.delete-modal .modal-header {
    flex-direction: column;
    text-align: center;
}

.delete-modal .modal-header p {
    margin: 8px 0 0 0;
    color: #6b7280;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-container {
        justify-content: stretch;
    }
    
    .search-box {
        min-width: auto;
        flex: 1;
    }
    
    .filter-dropdown select,
    .filter-dropdown input {
        min-width: auto;
        flex: 1;
    }
    
    .report-list-item {
        flex-wrap: wrap;
        gap: 12px;
        padding: 16px;
    }
    
    .report-user-section {
        flex-direction: row;
        gap: 8px;
        margin-right: 0;
    }
    
    .report-header-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .report-details {
        flex-direction: column;
        gap: 8px;
    }
    
    .report-list-actions {
        opacity: 1;
        margin-top: 8px;
        justify-content: center;
        width: 100%;
    }
    
    .pagination-container {
        flex-direction: column;
        text-align: center;
    }
    
    .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .reports-list {
        max-height: none;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination-btn {
        min-width: 35px;
        height: 35px;
        font-size: 0.85rem;
    }
    
    .add-new-content {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
}
</style>

<script>
let currentEditId = null;

function openAddReportModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Laporan Baru';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_report';
    document.getElementById('reportForm').reset();
    document.getElementById('reportId').value = '';
    document.getElementById('reportDate').value = new Date().toISOString().split('T')[0];
    currentEditId = null;
    document.getElementById('reportModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editReport(id, activity, problem, status, responsiblePerson) {
    document.getElementById('modalTitle').textContent = 'Edit Laporan';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('submitBtn').name = 'edit_report';
    document.getElementById('reportId').value = id;
    document.getElementById('reportActivity').value = activity;
    document.getElementById('reportProblem').value = problem;
    document.getElementById('reportStatus').value = status;
    document.getElementById('responsiblePerson').value = responsiblePerson;
    currentEditId = id;
    document.getElementById('reportModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteReport(id, activity) {
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus laporan "${activity.substring(0, 50)}${activity.length > 50 ? '...' : ''}"?`;
    document.getElementById('deleteReportId').value = id;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('show');
    document.body.style.overflow = '';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Filter by status from stat cards
function filterByStatus(status) {
    let url = new URL(window.location);
    const currentStatus = url.searchParams.get('status');
    
    if (currentStatus === status) {
        url.searchParams.delete('status');
    } else {
        url.searchParams.set('status', status);
    }
    
    url.searchParams.set('page', 'reports');
    url.searchParams.delete('page_num');
    
    window.location.href = url.toString();
}

// Filter functions
function applyFilters() {
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const dateFilter = document.getElementById('dateFilter')?.value || '';
    const userFilter = document.getElementById('userFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('status');
    url.searchParams.delete('search');
    url.searchParams.delete('page_num');
    url.searchParams.set('page', 'reports');
    
    if (roleFilter) url.searchParams.set('role', roleFilter);
    if (dateFilter) url.searchParams.set('date', dateFilter);
    if (userFilter) url.searchParams.set('user', userFilter);
    if (statusFilter) url.searchParams.set('status', statusFilter);
    if (searchValue) url.searchParams.set('search', searchValue);
    
    window.location.href = url.toString();
}

function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

function clearFilters() {
    let url = new URL(window.location);
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('status');
    url.searchParams.delete('search');
    url.searchParams.delete('page_num');
    url.searchParams.set('page', 'reports');
    window.location.href = url.toString();
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeReportModal();
        closeDeleteModal();
    }
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N to open add report modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const modal = document.getElementById('reportModal');
        if (modal) {
            openAddReportModal();
        }
    }
    
    // Escape to close any open modal
    if (e.key === 'Escape') {
        closeReportModal();
        closeDeleteModal();
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>