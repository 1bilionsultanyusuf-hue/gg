<?php
// Handle CRUD Operations for Taken
$message = '';
$error = '';

// Get current user info
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

// CREATE - Add new taken
if (isset($_POST['add_taken'])) {
    $id_todos = (int)$_POST['id_todos'];
    $status = trim($_POST['status']);
    $date = trim($_POST['date']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($id_todos) && !empty($status)) {
        $check_stmt = $koneksi->prepare("SELECT id FROM taken WHERE id_todos = ?");
        $check_stmt->bind_param("i", $id_todos);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Todo ini sudah diambil!";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO taken (id_todos, status, date, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $id_todos, $status, $date, $user_id);
            
            if ($stmt->execute()) {
                $message = "Todo berhasil diambil!";
            } else {
                $error = "Gagal mengambil todo!";
            }
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}

// UPDATE - Edit taken
if (isset($_POST['edit_taken'])) {
    $id = (int)$_POST['taken_id'];
    $status = trim($_POST['status']);
    $date = trim($_POST['date']);
    
    if (!empty($status)) {
        $stmt = $koneksi->prepare("UPDATE taken SET status = ?, date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $date, $id);
        
        if ($stmt->execute()) {
            $message = "Status berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui status!";
        }
    } else {
        $error = "Status harus diisi!";
    }
}

// DELETE - Remove taken
if (isset($_POST['delete_taken'])) {
    $id = (int)$_POST['taken_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM taken WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Taken berhasil dihapus!";
    } else {
        $error = "Gagal menghapus taken!";
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_user = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(td.title LIKE ? OR td.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

if (!empty($filter_status) && in_array($filter_status, ['in_progress', 'done'])) {
    $where_conditions[] = "tk.status = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

if (!empty($filter_user) && $filter_user > 0) {
    $where_conditions[] = "tk.user_id = ?";
    $params[] = $filter_user;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN apps a ON td.app_id = a.id
    $where_clause
";

if (!empty($params)) {
    $count_stmt = $koneksi->prepare($count_query);
    if ($param_types && !empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $koneksi->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Get taken data
$taken_query = "
    SELECT tk.*, 
           td.title as todo_title,
           td.description as todo_description,
           td.priority as todo_priority,
           u.name as user_name,
           a.name as app_name
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN apps a ON td.app_id = a.id
    $where_clause
    ORDER BY tk.date DESC, tk.created_at DESC
    LIMIT ? OFFSET ?
";

$pagination_params = $params;
$pagination_param_types = $param_types;
$pagination_params[] = $limit;
$pagination_params[] = $offset;
$pagination_param_types .= 'ii';

$taken_stmt = $koneksi->prepare($taken_query);
if (!empty($pagination_params) && $pagination_param_types) {
    $taken_stmt->bind_param($pagination_param_types, ...$pagination_params);
}
$taken_stmt->execute();
$taken_result = $taken_stmt->get_result();

// Get available todos
$available_todos_query = "
    SELECT td.id, td.title, td.priority, a.name as app_name
    FROM todos td
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN taken tk ON td.id = tk.id_todos
    WHERE tk.id IS NULL
    ORDER BY td.priority DESC, td.created_at DESC
";
$available_todos_result = $koneksi->query($available_todos_query);

// Get users for filter
$users_query = "SELECT id, name FROM users ORDER BY name";
$users_result = $koneksi->query($users_query);

// Get statistics
$total_taken = $koneksi->query("SELECT COUNT(*) as count FROM taken")->fetch_assoc()['count'];
$in_progress = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];
$done = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'done'")->fetch_assoc()['count'];
$available = $koneksi->query("SELECT COUNT(*) as count FROM todos td LEFT JOIN taken tk ON td.id = tk.id_todos WHERE tk.id IS NULL")->fetch_assoc()['count'];

function getPriorityIcon($priority) {
    $icons = [
        'high' => 'fas fa-exclamation-triangle',
        'medium' => 'fas fa-minus',
        'low' => 'fas fa-arrow-down'
    ];
    return $icons[$priority] ?? 'fas fa-circle';
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
        <h1 class="page-title">Todo yang Diambil</h1>
        <p class="page-subtitle">
            Kelola dan monitor todo yang sudah diambil
        </p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card bg-gradient-blue">
        <div class="stat-icon">
            <i class="fas fa-hand-paper"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $total_taken ?></h3>
            <p class="stat-label">Total Diambil</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-orange <?= $filter_status == 'in_progress' ? 'active' : '' ?>" onclick="filterByStatus('in_progress')">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $in_progress ?></h3>
            <p class="stat-label">In Progress</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-green <?= $filter_status == 'done' ? 'active' : '' ?>" onclick="filterByStatus('done')">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $done ?></h3>
            <p class="stat-label">Selesai</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-purple">
        <div class="stat-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $available ?></h3>
            <p class="stat-label">Tersedia</p>
        </div>
    </div>
</div>

<!-- Taken Container -->
<div class="taken-container">
    <div class="section-header">
        <div class="section-title-wrapper">
            <h2 class="section-title">Daftar Todo Diambil</h2>
            <span class="section-count"><?= $total_records ?> taken</span>
        </div>
        
        <!-- Filters -->
        <div class="filters-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari judul todo..." 
                       value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
            </div>
            
            <div class="filter-dropdown">
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="">Semua Status</option>
                    <option value="in_progress" <?= $filter_status == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="done" <?= $filter_status == 'done' ? 'selected' : '' ?>>Done</option>
                </select>
            </div>

            <div class="filter-dropdown">
                <select id="userFilter" onchange="applyFilters()">
                    <option value="">Semua User</option>
                    <?php 
                    $users_result->data_seek(0);
                    while($user = $users_result->fetch_assoc()): 
                    ?>
                    <option value="<?= $user['id'] ?>" <?= $filter_user == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($filter_status || $search || $filter_user): ?>
            <button class="btn-clear-filter" onclick="clearFilters()" title="Hapus Filter">
                <i class="fas fa-times"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add New Taken Button -->
    <div class="taken-list-item add-new-item" onclick="openAddTakenModal()">
        <div class="add-new-content">
            <div class="add-new-icon">
                <i class="fas fa-plus"></i>
            </div>
            <div class="add-new-text">
                <h3>Ambil Todo Baru</h3>
                <p>Klik untuk mengambil todo yang tersedia</p>
            </div>
        </div>
    </div>
    
    <!-- Taken List -->
    <div class="taken-list">
        <?php if ($taken_result->num_rows > 0): ?>
            <?php $no = $offset + 1; while($taken = $taken_result->fetch_assoc()): ?>
            <div class="taken-list-item" data-taken-id="<?= $taken['id'] ?>">
                <div class="taken-priority-container">
                    <div class="taken-priority-badge priority-<?= $taken['todo_priority'] ?>">
                        <i class="<?= getPriorityIcon($taken['todo_priority']) ?>"></i>
                    </div>
                </div>
                
                <div class="taken-list-content">
                    <div class="taken-list-main">
                        <h3 class="taken-list-title"><?= htmlspecialchars($taken['todo_title']) ?></h3>
                        <p class="taken-list-description">
                            <?= htmlspecialchars(substr($taken['todo_description'], 0, 80)) ?>
                            <?= strlen($taken['todo_description']) > 80 ? '...' : '' ?>
                        </p>
                    </div>
                    
                    <div class="taken-list-details">
                        <span class="detail-badge app">
                            <i class="fas fa-cube"></i>
                            <?= htmlspecialchars($taken['app_name']) ?>
                        </span>
                        <span class="detail-badge user">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($taken['user_name']) ?>
                        </span>
                        <span class="detail-badge date">
                            <i class="fas fa-calendar"></i>
                            <?= date('d/m/Y', strtotime($taken['date'])) ?>
                        </span>
                    </div>
                </div>
                
                <div class="taken-status-container">
                    <div class="status-badge status-<?= $taken['status'] ?>">
                        <i class="fas fa-<?= $taken['status'] == 'done' ? 'check-circle' : 'clock' ?>"></i>
                        <?= $taken['status'] == 'done' ? 'Completed' : 'In Progress' ?>
                    </div>
                </div>
                
                <div class="taken-list-actions">
                    <button class="action-btn-small edit" 
                            onclick="editTaken(<?= $taken['id'] ?>, '<?= $taken['status'] ?>', '<?= $taken['date'] ?>')" 
                            title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn-small delete" 
                            onclick="deleteTaken(<?= $taken['id'] ?>, '<?= htmlspecialchars($taken['todo_title'], ENT_QUOTES) ?>')" 
                            title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-hand-paper"></i>
                </div>
                <h3>Tidak ada taken ditemukan</h3>
                <p>Belum ada todo yang diambil atau sesuai dengan filter.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <div class="pagination">
            <?php
            $query_params = ['page' => 'taken'];
            if (!empty($search)) $query_params['search'] = $search;
            if (!empty($filter_status)) $query_params['status'] = $filter_status;
            if (!empty($filter_user)) $query_params['user'] = $filter_user;
            
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

<!-- Add/Edit Taken Modal -->
<div id="takenModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Ambil Todo</h3>
            <button class="modal-close" onclick="closeTakenModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="takenForm" method="POST" action="?page=taken">
                <input type="hidden" id="takenId" name="taken_id">
                <div class="form-group" id="todoSelectGroup">
                    <label for="takenTodo">Pilih Todo *</label>
                    <select id="takenTodo" name="id_todos" required>
                        <option value="">Pilih Todo yang Tersedia</option>
                        <?php 
                        $available_todos_result->data_seek(0);
                        while($todo = $available_todos_result->fetch_assoc()): 
                        ?>
                        <option value="<?= $todo['id'] ?>">
                            [<?= ucfirst($todo['priority']) ?>] <?= htmlspecialchars($todo['title']) ?> - <?= htmlspecialchars($todo['app_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="takenStatus">Status *</label>
                    <div class="status-selector">
                        <label class="status-option">
                            <input type="radio" name="status" value="in_progress" id="statusInProgress" checked>
                            <span class="status-badge status-in_progress">
                                <i class="fas fa-clock"></i>
                                In Progress
                            </span>
                        </label>
                        <label class="status-option">
                            <input type="radio" name="status" value="done" id="statusDone">
                            <span class="status-badge status-done">
                                <i class="fas fa-check-circle"></i>
                                Done
                            </span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="takenDate">Tanggal *</label>
                    <input type="date" id="takenDate" name="date" required 
                           value="<?= date('Y-m-d') ?>">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTakenModal()">
                Batal
            </button>
            <button type="submit" id="submitBtn" form="takenForm" name="add_taken" class="btn btn-primary">
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
            <p id="deleteMessage">Apakah Anda yakin ingin menghapus taken ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <form id="deleteForm" method="POST" action="?page=taken" style="display: inline;">
                <input type="hidden" id="deleteTakenId" name="taken_id">
                <button type="submit" name="delete_taken" class="btn btn-danger">
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

.bg-gradient-blue { 
    background: linear-gradient(135deg, #0066ff, #33ccff); 
    color: white; 
}

.bg-gradient-orange { 
    background: linear-gradient(135deg, #f59e0b, #fbbf24); 
    color: white; 
}

.bg-gradient-green { 
    background: linear-gradient(135deg, #10b981, #34d399); 
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

/* Taken Container */
.taken-container {
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

.filter-dropdown select {
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    min-width: 140px;
    transition: all 0.3s ease;
}

.filter-dropdown select:focus {
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

/* Taken List */
.taken-list {
    max-height: 500px;
    overflow-y: auto;
}

.taken-list::-webkit-scrollbar {
    width: 6px;
}

.taken-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.taken-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.taken-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Taken List Items */
.taken-list-item {
    display: flex;
    align-items: center;
    padding: 14px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    cursor: pointer;
}

.taken-list-item:hover {
    background: #f8fafc;
}

.taken-list-item:last-child {
    border-bottom: none;
}

.add-new-item {
    border: 2px dashed #d1d5db !important;
    background: #f9fafb !important;
    margin: 16px 24px;
    border-radius: 12px;
    justify-content: center;
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

.taken-priority-container {
    display: flex;
    align-items: center;
    margin-right: 16px;
    flex-shrink: 0;
}

.taken-priority-badge {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
}

.taken-priority-badge.priority-high {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.taken-priority-badge.priority-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.taken-priority-badge.priority-low {
    background: linear-gradient(135deg, #10b981, #059669);
}

/* Taken List Content */
.taken-list-content {
    flex: 1;
    min-width: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.taken-list-main {
    flex: 1;
    min-width: 0;
}

.taken-list-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.taken-list-description {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0 0 8px 0;
}

.taken-list-details {
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

/* Taken Status Container */
.taken-status-container {
    margin: 0 16px;
    flex-shrink: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
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

/* Taken List Actions */
.taken-list-actions {
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.taken-list-item:hover .taken-list-actions {
    opacity: 1;
}

.action-btn-small {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
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
    padding: 20px 24px;
    border-top: 1px solid #f3f4f6;
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
    gap: 6px;
}

.pagination-btn {
    min-width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    font-size: 0.85rem;
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
    padding: 0 4px;
    font-weight: 500;
}

.pagination-info {
    color: #6b7280;
    font-size: 0.85rem;
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

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Status Selector */
.status-selector {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.status-option {
    cursor: pointer;
}

.status-option input[type="radio"] {
    display: none;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 20px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    font-weight: 500;
    color: white;
}

.status-selector .status-badge.status-in_progress {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.status-selector .status-badge.status-done {
    background: linear-gradient(135deg, #10b981, #059669);
}

.status-option input[type="radio"]:checked + .status-badge {
    border-color: #1f2937;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
    
    .filter-dropdown select {
        min-width: auto;
        flex: 1;
    }
    
    .taken-list-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .taken-status-container {
        margin: 0;
    }
    
    .taken-list-actions {
        opacity: 1;
    }
    
    .taken-list-item {
        flex-wrap: wrap;
    }
    
    .taken-list {
        max-height: none;
    }
}

@media (max-width: 480px) {
    .taken-list-item {
        padding: 12px 16px;
    }
    
    .section-header {
        padding: 16px 20px 12px;
    }
    
    .add-new-item {
        margin: 12px 16px;
    }
    
    .status-selector {
        flex-direction: column;
    }
    
    .pagination-container {
        flex-direction: column;
        text-align: center;
    }
    
    .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>

<script>
let currentEditId = null;

function openAddTakenModal() {
    document.getElementById('modalTitle').textContent = 'Ambil Todo';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_taken';
    document.getElementById('takenForm').reset();
    document.getElementById('takenId').value = '';
    document.getElementById('todoSelectGroup').style.display = 'block';
    document.getElementById('takenDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('statusInProgress').checked = true;
    currentEditId = null;
    document.getElementById('takenModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editTaken(id, status, date) {
    document.getElementById('modalTitle').textContent = 'Edit Status';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('submitBtn').name = 'edit_taken';
    document.getElementById('takenId').value = id;
    document.getElementById('takenDate').value = date;
    document.getElementById('todoSelectGroup').style.display = 'none';
    
    // Set status radio button
    if (status === 'in_progress') {
        document.getElementById('statusInProgress').checked = true;
    } else if (status === 'done') {
        document.getElementById('statusDone').checked = true;
    }
    
    currentEditId = id;
    document.getElementById('takenModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteTaken(id, title) {
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus taken "${title}"?`;
    document.getElementById('deleteTakenId').value = id;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeTakenModal() {
    document.getElementById('takenModal').classList.remove('show');
    document.body.style.overflow = '';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

function filterByStatus(status) {
    let url = new URL(window.location);
    const currentStatus = url.searchParams.get('status');
    
    if (currentStatus === status) {
        url.searchParams.delete('status');
    } else {
        url.searchParams.set('status', status);
    }
    
    url.searchParams.set('page', 'taken');
    url.searchParams.delete('page_num');
    
    window.location.href = url.toString();
}

function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const userFilter = document.getElementById('userFilter').value;
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('status');
    url.searchParams.delete('user');
    url.searchParams.delete('search');
    url.searchParams.delete('page_num');
    url.searchParams.set('page', 'taken');
    
    if (statusFilter) {
        url.searchParams.set('status', statusFilter);
    }
    if (userFilter) {
        url.searchParams.set('user', userFilter);
    }
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    }
    
    window.location.href = url.toString();
}

function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

function clearFilters() {
    let url = new URL(window.location);
    url.searchParams.delete('status');
    url.searchParams.delete('user');
    url.searchParams.delete('search');
    url.searchParams.delete('page_num');
    url.searchParams.set('page', 'taken');
    window.location.href = url.toString();
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeTakenModal();
        closeDeleteModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTakenModal();
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
if (document.getElementById('takenForm')) {
    document.getElementById('takenForm').addEventListener('submit', function(e) {
        const todoSelect = document.getElementById('takenTodo');
        
        if (todoSelect.style.display !== 'none' && !todoSelect.value) {
            e.preventDefault();
            alert('Todo harus dipilih!');
            todoSelect.focus();
            return false;
        }
    });
}

// Auto-focus first input when modal opens
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.target.classList.contains('modal') && mutation.target.classList.contains('show')) {
            setTimeout(() => {
                const firstInput = mutation.target.querySelector('select, input[type="date"]');
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