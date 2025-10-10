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
$current_user_name = $current_user['name'];

// CREATE - Add new taken (otomatis masuk ke user yang login)
if (isset($_POST['add_taken'])) {
    $id_todos = (int)$_POST['id_todos'];
    $status = trim($_POST['status']);
    $date = trim($_POST['date']);
    $user_id = $_SESSION['user_id']; // Otomatis ambil dari session
    
    if (!empty($id_todos) && !empty($status)) {
        $check_stmt = $koneksi->prepare("SELECT id FROM taken WHERE id_todos = ?");
        $check_stmt->bind_param("i", $id_todos);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Todo ini sudah diambil oleh user lain!";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO taken (id_todos, status, date, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $id_todos, $status, $date, $user_id);
            
            if ($stmt->execute()) {
                $message = "Todo berhasil diambil dan ditambahkan ke daftar Anda!";
            } else {
                $error = "Gagal mengambil todo!";
            }
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}

// UPDATE - Edit taken (hanya milik user sendiri)
if (isset($_POST['edit_taken'])) {
    $id = (int)$_POST['taken_id'];
    $status = trim($_POST['status']);
    $date = trim($_POST['date']);
    
    // Cek apakah taken ini milik user yang login
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $current_user_id) {
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
        } else {
            $error = "Anda tidak memiliki akses untuk mengedit taken ini!";
        }
    } else {
        $error = "Taken tidak ditemukan!";
    }
}

// DELETE - Remove taken (hanya milik user sendiri)
if (isset($_POST['delete_taken'])) {
    $id = (int)$_POST['taken_id'];
    
    // Cek apakah taken ini milik user yang login
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $current_user_id) {
            $stmt = $koneksi->prepare("DELETE FROM taken WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Taken berhasil dihapus!";
            } else {
                $error = "Gagal menghapus taken!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk menghapus taken ini!";
        }
    } else {
        $error = "Taken tidak ditemukan!";
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause - HANYA MILIK USER YANG LOGIN
$where_conditions = [];
$params = [];
$param_types = '';

// Semua user (termasuk admin) hanya lihat taken miliknya sendiri
$where_conditions[] = "tk.user_id = ?";
$params[] = $current_user_id;
$param_types .= 'i';

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

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// PAGINATION SETUP - 5 ITEMS PER PAGE
$items_per_page = 5;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users todo_creator ON td.user_id = todo_creator.id
    $where_clause
";

if (!empty($params)) {
    $count_stmt = $koneksi->prepare($count_query);
    if ($param_types && !empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_taken = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_taken = $koneksi->query($count_query)->fetch_assoc()['total'];
}

// Calculate total pages
$max_pages = 10;
$total_items = min($total_taken, $max_pages * $items_per_page);
$total_pages = $total_taken > 0 ? min(ceil($total_taken / $items_per_page), $max_pages) : 1;

// Get taken data with PAGINATION - TAMBAHKAN INFO PEMBUAT TODO
$taken_query = "
    SELECT tk.*, 
           td.title as todo_title,
           td.description as todo_description,
           td.priority as todo_priority,
           u.name as user_name,
           a.name as app_name,
           todo_creator.name as todo_creator_name
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users todo_creator ON td.user_id = todo_creator.id
    $where_clause
    ORDER BY tk.date DESC, tk.created_at DESC
    LIMIT $items_per_page OFFSET $offset
";

if (!empty($params)) {
    $taken_stmt = $koneksi->prepare($taken_query);
    $taken_stmt->bind_param($param_types, ...$params);
    $taken_stmt->execute();
    $taken_result = $taken_stmt->get_result();
} else {
    $taken_result = $koneksi->query($taken_query);
}

// Get available todos (yang belum diambil siapapun) - TAMBAHKAN INFO PEMBUAT
$available_todos_query = "
    SELECT td.id, td.title, td.priority, a.name as app_name, u.name as creator_name
    FROM todos td
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users u ON td.user_id = u.id
    LEFT JOIN taken tk ON td.id = tk.id_todos
    WHERE tk.id IS NULL
    ORDER BY td.priority DESC, td.created_at DESC
";
$available_todos_result = $koneksi->query($available_todos_query);

// Get statistics - UNTUK USER YANG LOGIN SAJA (dengan error handling)
$stat_stmt = $koneksi->prepare("SELECT COUNT(*) as count FROM taken WHERE user_id = ?");
$stat_stmt->bind_param("i", $current_user_id);
$stat_stmt->execute();
$stat_result = $stat_stmt->get_result();
$total_taken_stat = $stat_result ? $stat_result->fetch_assoc()['count'] : 0;
$stat_stmt->close();

$stat_stmt = $koneksi->prepare("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress' AND user_id = ?");
$stat_stmt->bind_param("i", $current_user_id);
$stat_stmt->execute();
$stat_result = $stat_stmt->get_result();
$in_progress = $stat_result ? $stat_result->fetch_assoc()['count'] : 0;
$stat_stmt->close();

$stat_stmt = $koneksi->prepare("SELECT COUNT(*) as count FROM taken WHERE status = 'done' AND user_id = ?");
$stat_stmt->bind_param("i", $current_user_id);
$stat_stmt->execute();
$stat_result = $stat_stmt->get_result();
$done = $stat_result ? $stat_result->fetch_assoc()['count'] : 0;
$stat_stmt->close();

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
        <h1 class="page-title">Todo Saya</h1>
        <p class="page-subtitle">
            Kelola dan monitor todo yang sudah Anda ambil
        </p>
        <div class="user-info-badge">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($current_user_name) ?></span>
            <span class="user-role-badge role-<?= $current_user_role ?>">
                <?= ucfirst($current_user_role) ?>
            </span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card bg-gradient-blue">
        <div class="stat-icon">
            <i class="fas fa-hand-paper"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $total_taken_stat ?></h3>
            <p class="stat-label">Todo Saya</p>
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
            <h2 class="section-title">Daftar Todo</h2>
            <span class="section-count"><?= $total_taken ?> taken</span>
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
            
            <?php if ($filter_status || $search): ?>
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
            <?php while($taken = $taken_result->fetch_assoc()): ?>
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
                        <div class="taken-list-details">
                            <span class="detail-badge app">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($taken['app_name']) ?>
                            </span>
                            <span class="detail-badge creator">
                                <i class="fas fa-user-tag"></i>
                                Dibuat: <?= htmlspecialchars($taken['todo_creator_name']) ?>
                            </span>
                            <span class="detail-badge date">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y', strtotime($taken['date'])) ?>
                            </span>
                        </div>
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
                <h3>Belum ada todo yang diambil</h3>
                <p>Anda belum mengambil todo apapun. Klik tombol di atas untuk mengambil todo!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_taken > 0): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            <span class="pagination-current">Halaman <?= $current_page ?> dari <?= $total_pages ?></span>
            <span class="pagination-total">Menampilkan <?= min($items_per_page, $total_taken - $offset) ?> dari <?= min($total_items, $total_taken) ?> taken</span>
        </div>
        
        <div class="pagination-controls">
            <?php if ($current_page > 1): ?>
            <a href="?page=taken&status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&pg=<?= $current_page - 1 ?>" class="pagination-btn pagination-btn-prev">
                <i class="fas fa-chevron-left"></i>
                <span>Prev</span>
            </a>
            <?php else: ?>
            <span class="pagination-btn pagination-btn-prev pagination-btn-disabled">
                <i class="fas fa-chevron-left"></i>
                <span>Prev</span>
            </span>
            <?php endif; ?>
            
            <div class="pagination-numbers">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="pagination-number pagination-number-active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=taken&status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&pg=<?= $i ?>" class="pagination-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            
            <?php if ($current_page < $total_pages): ?>
            <a href="?page=taken&status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&pg=<?= $current_page + 1 ?>" class="pagination-btn pagination-btn-next">
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
                            [<?= ucfirst($todo['priority']) ?>] <?= htmlspecialchars($todo['title']) ?> - <?= htmlspecialchars($todo['app_name']) ?> (by: <?= htmlspecialchars($todo['creator_name']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($available_todos_result->num_rows == 0): ?>
                    <p class="form-help-text">
                        <i class="fas fa-info-circle"></i>
                        Tidak ada todo yang tersedia saat ini
                    </p>
                    <?php endif; ?>
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
    margin: 0 0 12px 0;
}

.user-info-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-radius: 20px;
    border: 1px solid #cbd5e1;
    font-size: 0.9rem;
    color: #475569;
    margin-top: 8px;
}

.user-info-badge i {
    font-size: 1.1rem;
    color: #64748b;
}

.user-role-badge {
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.user-role-badge.role-admin {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
}

.user-role-badge.role-user {
    background: linear-gradient(135deg, #0066ff, #0044cc);
    color: white;
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
    gap: 16px;
    min-height: 80px;
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

.detail-badge.creator {
    color: #808080;
    font-weight: 500;
}

.detail-badge.creator i {
    color: #808080;
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
    margin-left: auto;
}

.taken-list-item:hover .taken-list-actions {
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
    background: white;
    font-size: 0.85rem;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
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

.form-help-text {
    margin-top: 8px;
    font-size: 0.8rem;
    color: #f59e0b;
    display: flex;
    align-items: center;
    gap: 6px;
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

.status-selector .status-badge {
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-option input[type="radio"]:checked + .status-badge {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .taken-list-item {
        flex-wrap: wrap;
    }
    
    .taken-status-container {
        width: 100%;
        margin: 8px 0 0 0;
    }
    
    .taken-list-actions {
        opacity: 1;
    }
}
</style>

<script>
function openAddTakenModal() {
    document.getElementById('modalTitle').textContent = 'Ambil Todo';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_taken';
    document.getElementById('takenForm').reset();
    document.getElementById('takenId').value = '';
    document.getElementById('todoSelectGroup').style.display = 'block';
    document.getElementById('takenDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('statusInProgress').checked = true;
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
    
    if (status === 'in_progress') {
        document.getElementById('statusInProgress').checked = true;
    } else if (status === 'done') {
        document.getElementById('statusDone').checked = true;
    }
    
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
    url.searchParams.set('pg', '1');
    window.location.href = url.toString();
}

function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.set('page', 'taken');
    url.searchParams.set('pg', '1');
    
    if (statusFilter) {
        url.searchParams.set('status', statusFilter);
    } else {
        url.searchParams.delete('status');
    }
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
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
    url.searchParams.delete('search');
    url.searchParams.set('page', 'taken');
    url.searchParams.set('pg', '1');
    window.location.href = url.toString();
}

function jumpToPage() {
    const select = document.getElementById('pageJumpSelect');
    const page = parseInt(select.value);
    const statusFilter = document.getElementById('statusFilter') ? document.getElementById('statusFilter').value : '';
    const searchValue = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    
    let url = new URL(window.location);
    url.searchParams.set('page', 'taken');
    url.searchParams.set('pg', page);
    
    if (statusFilter) url.searchParams.set('status', statusFilter);
    if (searchValue) url.searchParams.set('search', searchValue);
    
    window.location.href = url.toString();
}

document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeTakenModal();
        closeDeleteModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTakenModal();
        closeDeleteModal();
    }
});

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
</script>