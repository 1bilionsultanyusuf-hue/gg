<?php
// Simplified Reports Module

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
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// PAGINATION SETUP - 5 ITEMS PER PAGE
$items_per_page = 5;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

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
    $where_conditions[] = "(r.activity LIKE ?)";
    $params[] = "%$search%";
    $param_types .= 's';
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
    $total_reports = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_reports = $koneksi->query($count_query)->fetch_assoc()['total'];
}

// Calculate total pages (maximum 10 pages)
$max_pages = 10;
$total_items = min($total_reports, $max_pages * $items_per_page);
$total_pages = $total_reports > 0 ? min(ceil($total_reports / $items_per_page), $max_pages) : 1;

// Get report data with PAGINATION
$reports_query = "
    SELECT r.*, 
           u.name as user_name,
           u.email as user_email,
           u.role as user_role
    FROM reports r
    LEFT JOIN users u ON r.user_id = u.id
    $where_clause
    ORDER BY r.date DESC, r.created_at DESC
    LIMIT $items_per_page OFFSET $offset
";

if (!empty($params)) {
    $reports_stmt = $koneksi->prepare($reports_query);
    $reports_stmt->bind_param($param_types, ...$params);
    $reports_stmt->execute();
    $reports_result = $reports_stmt->get_result();
} else {
    $reports_result = $koneksi->query($reports_query);
}

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
$this_week = date('Y-m-d', strtotime('-7 days'));
$this_month = date('Y-m-01');

if ($current_permissions['can_view_all']) {
    $stats_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today'")->fetch_assoc()['count'];
    $stats_week = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date >= '$this_week'")->fetch_assoc()['count'];
    $stats_month = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date >= '$this_month'")->fetch_assoc()['count'];
    $stats_total = $koneksi->query("SELECT COUNT(*) as count FROM reports")->fetch_assoc()['count'];
} else {
    $stats_today = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date = '$today' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_week = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date >= '$this_week' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_month = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE date >= '$this_month' AND user_id = $current_user_id")->fetch_assoc()['count'];
    $stats_total = $koneksi->query("SELECT COUNT(*) as count FROM reports WHERE user_id = $current_user_id")->fetch_assoc()['count'];
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
        <h1 class="page-title">Manajemen Laporan</h1>
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
            <p class="stat-label">Hari Ini</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-green">
        <div class="stat-icon">
            <i class="fas fa-calendar-week"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $stats_week ?></h3>
            <p class="stat-label">Minggu Ini</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-orange">
        <div class="stat-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $stats_month ?></h3>
            <p class="stat-label">Bulan Ini</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-purple">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $stats_total ?></h3>
            <p class="stat-label">Total Laporan</p>
        </div>
    </div>
</div>

<!-- Reports Container -->
<div class="reports-container">
    <div class="section-header">
        <div class="section-title-wrapper">
            <h2 class="section-title">Daftar Laporan</h2>
            <span class="section-count"><?= $total_reports ?> laporan</span>
        </div>
        
        <!-- Filters -->
        <div class="filters-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari aktivitas..." 
                       value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
            </div>
            
            <?php if ($current_permissions['can_view_all']): ?>
            <div class="filter-dropdown">
                <select id="roleFilter" onchange="applyFilters()">
                    <option value="">Semua Role</option>
                    <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="client" <?= $role_filter == 'client' ? 'selected' : '' ?>>Client</option>
                    <option value="programmer" <?= $role_filter == 'programmer' ? 'selected' : '' ?>>Programmer</option>
                    <option value="support" <?= $role_filter == 'support' ? 'selected' : '' ?>>Support</option>
                </select>
            </div>

            <div class="filter-dropdown">
                <select id="userFilter" onchange="applyFilters()">
                    <option value="">Semua User</option>
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
            
            <?php if ($role_filter || $date_filter || $user_filter || $search): ?>
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
                <p>Klik untuk menambahkan laporan aktivitas</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Reports List -->
    <div class="reports-list">
        <?php if ($reports_result->num_rows > 0): ?>
            <?php while($report = $reports_result->fetch_assoc()): ?>
            <div class="report-list-item">
                <div class="report-user-section">
                    <div class="user-avatar" style="background: <?= getRoleColor($report['user_role']) ?>">
                        <i class="<?= getRoleIcon($report['user_role']) ?>"></i>
                    </div>
                </div>
                
                <div class="report-list-content">
                    <div class="report-header-row">
                        <div class="report-user-info">
                            <h4 class="user-name"><?= htmlspecialchars($report['user_name']) ?></h4>
                            <span class="user-role" style="color: <?= getRoleColor($report['user_role']) ?>">
                                <?= ucfirst($report['user_role']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="report-activity">
                        <p><?= nl2br(htmlspecialchars($report['activity'])) ?></p>
                    </div>
                    
                    <div class="report-list-details">
                        <span class="detail-badge date">
                            <i class="fas fa-calendar"></i>
                            <?= date('d M Y', strtotime($report['date'])) ?>
                        </span>
                        <span class="detail-badge time">
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
                            onclick="editReport(<?= $report['id'] ?>, '<?= htmlspecialchars(addslashes($report['activity'])) ?>')" 
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
    <?php if ($total_reports > 0): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            <span class="pagination-current">Halaman <?= $current_page ?> dari <?= $total_pages ?></span>
            <span class="pagination-total">Menampilkan <?= min($items_per_page, $total_reports - $offset) ?> dari <?= min($total_items, $total_reports) ?> laporan</span>
        </div>
        
        <div class="pagination-controls">
            <!-- Previous Page -->
            <?php if ($current_page > 1): ?>
            <a href="?page=reports&role=<?= $role_filter ?>&date=<?= $date_filter ?>&user=<?= $user_filter ?>&search=<?= urlencode($search) ?>&pg=<?= $current_page - 1 ?>" class="pagination-btn pagination-btn-prev" title="Sebelumnya">
                <i class="fas fa-chevron-left"></i>
                <span>Prev</span>
            </a>
            <?php else: ?>
            <span class="pagination-btn pagination-btn-prev pagination-btn-disabled">
                <i class="fas fa-chevron-left"></i>
                <span>Prev</span>
            </span>
            <?php endif; ?>
            
            <!-- Page Numbers 1-10 -->
            <div class="pagination-numbers">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="pagination-number pagination-number-active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=reports&role=<?= $role_filter ?>&date=<?= $date_filter ?>&user=<?= $user_filter ?>&search=<?= urlencode($search) ?>&pg=<?= $i ?>" class="pagination-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            
            <!-- Next Page -->
            <?php if ($current_page < $total_pages): ?>
            <a href="?page=reports&role=<?= $role_filter ?>&date=<?= $date_filter ?>&user=<?= $user_filter ?>&search=<?= urlencode($search) ?>&pg=<?= $current_page + 1 ?>" class="pagination-btn pagination-btn-next" title="Selanjutnya">
                <span>Next</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php else: ?>
            <span class="pagination-btn pagination-btn-next pagination-btn-disabled">
                <span>Next</span>
                <i class="fas fa-chevron-right"></i>
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Quick Jump -->
        <div class="pagination-jump">
            <span>Ke halaman:</span>
            <select id="pageJumpSelect" class="pagination-jump-select" onchange="jumpToPage()">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <option value="<?= $i ?>" <?= $i == $current_page ? 'selected' : '' ?>>
                    Halaman <?= $i ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>
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
                    <label for="reportDate">Tanggal *</label>
                    <input type="date" id="reportDate" name="report_date" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="reportActivity">Aktivitas *</label>
                    <textarea id="reportActivity" name="activity" rows="6" required
                              placeholder="Jelaskan aktivitas yang dilakukan..."></textarea>
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
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
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

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Page Header */
.page-header {
    background: white;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.page-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}

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
    background: #e2e8f0;
}

.btn-danger {
    background: linear-gradient(90deg, #ef4444, #dc2626);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(90deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
}

.mr-2 {
    margin-right: 8px;
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.15);
}

.bg-gradient-blue { 
    background: linear-gradient(135deg, #0066ff, #33ccff); 
    color: white; 
}

.bg-gradient-green { 
    background: linear-gradient(135deg, #10b981, #34d399); 
    color: white; 
}

.bg-gradient-orange { 
    background: linear-gradient(135deg, #f59e0b, #fbbf24); 
    color: white; 
}

.bg-gradient-purple { 
    background: linear-gradient(135deg, #7c3aed, #a855f7); 
    color: white; 
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}

.stat-content .stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 2px;
}

.stat-content .stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

/* Reports Container */
.reports-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.section-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.section-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.section-count {
    color: #6b7280;
    font-size: 0.85rem;
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
    min-width: 200px;
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
    min-width: 140px;
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
    max-height: 500px;
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

.reports-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Report List Items */
.report-list-item {
    display: flex;
    align-items: center;
    padding: 14px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    position: relative;
    gap: 16px;
    min-height: 80px;
}

.report-list-item:hover {
    background: #f8fafc;
}

.report-list-item:last-child {
    border-bottom: none;
}

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
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #374151;
}

.add-new-text p {
    font-size: 0.8rem;
    margin: 0;
    color: #9ca3af;
}

/* Report User Section */
.report-user-section {
    display: flex;
    align-items: center;
    margin-right: 16px;
    flex-shrink: 0;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
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
    margin-bottom: 8px;
    gap: 12px;
}

.report-user-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.user-role {
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.report-activity {
    color: #374151;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 8px;
}

.report-activity p {
    margin: 0;
}

.report-list-details {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.detail-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #6b7280;
}

.detail-badge i {
    width: 14px;
    font-size: 0.7rem;
}

.owner-badge {
    padding: 3px 8px;
    background: #0066ff;
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Report List Actions */
.report-list-actions {
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
    margin-left: auto;
}

.report-list-item:hover .report-list-actions {
    opacity: 1;
}

.action-btn-small {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
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

/* Pagination Styles */
.pagination-container {
    padding: 20px 24px;
    border-top: 2px solid #f1f5f9;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.pagination-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.85rem;
}

.pagination-current {
    font-weight: 700;
    color: #1f2937;
    font-size: 0.9rem;
}

.pagination-total {
    color: #6b7280;
    font-size: 0.8rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
}

.pagination-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pagination-btn-prev,
.pagination-btn-next {
    background: linear-gradient(135deg, #f8fafc, #ffffff);
}

.pagination-btn-disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-numbers {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pagination-number {
    min-width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
}

.pagination-number:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
    transform: translateY(-1px);
}

.pagination-number-active {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-color: #2563eb;
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.pagination-number-active:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.pagination-jump {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
}

.pagination-jump-select {
    height: 38px;
    padding: 0 32px 0 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
    background-size: 12px;
    font-size: 0.85rem;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.pagination-jump-select:hover {
    border-color: #d1d5db;
    background-color: #f9fafb;
}

.pagination-jump-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
    align-items: flex-start;
}

.modal-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.delete-modal .modal-header {
    flex-direction: column;
    text-align: center;
    align-items: center;
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
    box-sizing: border-box;
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
    min-height: 120px;
}

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .pagination-container {
        justify-content: center;
    }
    
    .pagination-info {
        width: 100%;
        text-align: center;
        align-items: center;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 16px 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-container {
        justify-content: stretch;
        width: 100%;
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
    }
    
    .report-header-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .report-list-actions {
        opacity: 1;
    }
    
    .reports-list {
        max-height: none;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 12px;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }
    
    .pagination-numbers {
        order: 1;
        flex-wrap: wrap;
    }
    
    .pagination-btn span {
        display: none;
    }
    
    .pagination-btn {
        padding: 8px 12px;
    }
    
    .pagination-jump {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .report-list-item {
        padding: 12px 16px;
    }
    
    .section-header {
        padding: 16px 20px 12px;
    }
    
    .add-new-item {
        margin: 12px 16px;
    }
    
    .pagination-number {
        min-width: 34px;
        height: 34px;
        font-size: 0.8rem;
    }
    
    .pagination-btn {
        height: 34px;
    }
    
    .pagination-jump-select {
        height: 34px;
        font-size: 0.8rem;
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

function editReport(id, activity) {
    document.getElementById('modalTitle').textContent = 'Edit Laporan';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('submitBtn').name = 'edit_report';
    document.getElementById('reportId').value = id;
    document.getElementById('reportActivity').value = activity;
    
    currentEditId = id;
    document.getElementById('reportModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteReport(id, activity) {
    const truncated = activity.length > 50 ? activity.substring(0, 50) + '...' : activity;
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus laporan "${truncated}"?`;
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

function applyFilters() {
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const dateFilter = document.getElementById('dateFilter')?.value || '';
    const userFilter = document.getElementById('userFilter')?.value || '';
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('role');
    url.searchParams.delete('date');
    url.searchParams.delete('user');
    url.searchParams.delete('search');
    url.searchParams.set('page', 'reports');
    url.searchParams.set('pg', '1');
    
    if (roleFilter) url.searchParams.set('role', roleFilter);
    if (dateFilter) url.searchParams.set('date', dateFilter);
    if (userFilter) url.searchParams.set('user', userFilter);
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
    url.searchParams.delete('search');
    url.searchParams.set('page', 'reports');
    url.searchParams.set('pg', '1');
    window.location.href = url.toString();
}

function jumpToPage() {
    const select = document.getElementById('pageJumpSelect');
    const page = parseInt(select.value);
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const dateFilter = document.getElementById('dateFilter')?.value || '';
    const userFilter = document.getElementById('userFilter')?.value || '';
    const searchValue = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    
    let url = new URL(window.location);
    url.searchParams.set('page', 'reports');
    url.searchParams.set('pg', page);
    
    if (roleFilter) url.searchParams.set('role', roleFilter);
    if (dateFilter) url.searchParams.set('date', dateFilter);
    if (userFilter) url.searchParams.set('user', userFilter);
    if (searchValue) url.searchParams.set('search', searchValue);
    
    window.location.href = url.toString();
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeReportModal();
        closeDeleteModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
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

// Form validation
if (document.getElementById('reportForm')) {
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        const activity = document.getElementById('reportActivity').value.trim();
        
        if (!activity) {
            e.preventDefault();
            alert('Aktivitas harus diisi!');
            document.getElementById('reportActivity').focus();
            return false;
        }
    });
}

// Auto-focus first input when modal opens
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.target.classList.contains('modal') && mutation.target.classList.contains('show')) {
            setTimeout(() => {
                const firstInput = mutation.target.querySelector('input[type="date"], textarea');
                if (firstInput && firstInput.offsetParent !== null) {
                    firstInput.focus();
                }
            }, 300);
        }
    });
});

document.querySelectorAll('.modal').forEach(modal => {
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});
</script>