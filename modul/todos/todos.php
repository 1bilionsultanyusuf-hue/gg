<?php
// Handle CRUD Operations for Todos
$message = '';
$error = '';

// CREATE - Add new todo
if (isset($_POST['add_todo'])) {
    $app_id = trim($_POST['app_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($app_id)) {
        $stmt = $koneksi->prepare("INSERT INTO todos (app_id, title, description, priority, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $app_id, $title, $description, $priority, $user_id);
        
        if ($stmt->execute()) {
            $message = "Todo '$title' berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan todo!";
        }
    } else {
        $error = "Judul dan aplikasi harus diisi!";
    }
}

// UPDATE - Edit todo
if (isset($_POST['edit_todo'])) {
    $id = $_POST['todo_id'];
    $app_id = trim($_POST['app_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    
    if (!empty($title) && !empty($app_id)) {
        $stmt = $koneksi->prepare("UPDATE todos SET app_id = ?, title = ?, description = ?, priority = ? WHERE id = ?");
        $stmt->bind_param("isssi", $app_id, $title, $description, $priority, $id);
        
        if ($stmt->execute()) {
            $message = "Todo berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui todo!";
        }
    } else {
        $error = "Judul dan aplikasi harus diisi!";
    }
}

// DELETE - Remove todo
if (isset($_POST['delete_todo'])) {
    $id = $_POST['todo_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Todo berhasil dihapus!";
    } else {
        $error = "Gagal menghapus todo!";
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$filter_app = isset($_GET['app']) ? (int)$_GET['app'] : 0;

// Build WHERE clause for filters
$where_conditions = [];
$params = [];
$param_types = '';

// ALWAYS exclude taken todos
$where_conditions[] = "tk.id_todos IS NULL";

if (!empty($search)) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

if (!empty($filter_priority) && in_array($filter_priority, ['high', 'medium', 'low'])) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $filter_priority;
    $param_types .= 's';
}

if (!empty($filter_app) && $filter_app > 0) {
    $where_conditions[] = "t.app_id = ?";
    $params[] = $filter_app;
    $param_types .= 'i';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// PAGINATION SETUP - 5 ITEMS PER PAGE
$items_per_page = 5;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total 
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    $where_clause
";

if (!empty($params)) {
    $count_stmt = $koneksi->prepare($count_query);
    if ($param_types && !empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_todos = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_todos = $koneksi->query($count_query)->fetch_assoc()['total'];
}

// Calculate total pages (maximum 10 pages)
$max_pages = 10;
$total_items = min($total_todos, $max_pages * $items_per_page);
$total_pages = $total_todos > 0 ? min(ceil($total_todos / $items_per_page), $max_pages) : 1;

// Get todos data with PAGINATION
$todos_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as user_name,
           tk.status as taken_status,
           tk.date as taken_date
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    $where_clause
    ORDER BY t.created_at DESC
    LIMIT $items_per_page OFFSET $offset
";

if (!empty($params)) {
    $todos_stmt = $koneksi->prepare($todos_query);
    $todos_stmt->bind_param($param_types, ...$params);
    $todos_stmt->execute();
    $todos_result = $todos_stmt->get_result();
} else {
    $todos_result = $koneksi->query($todos_query);
}

// Get apps for dropdown
$apps_query = "SELECT id, name FROM apps ORDER BY name";
$apps_result = $koneksi->query($apps_query);

// Get statistics (exclude taken todos)
$total_todos_stat = $koneksi->query("SELECT COUNT(*) as count FROM todos t LEFT JOIN taken tk ON t.id = tk.id_todos WHERE tk.id_todos IS NULL")->fetch_assoc()['count'];
$high_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos t LEFT JOIN taken tk ON t.id = tk.id_todos WHERE t.priority = 'high' AND tk.id_todos IS NULL")->fetch_assoc()['count'];
$medium_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos t LEFT JOIN taken tk ON t.id = tk.id_todos WHERE t.priority = 'medium' AND tk.id_todos IS NULL")->fetch_assoc()['count'];
$low_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos t LEFT JOIN taken tk ON t.id = tk.id_todos WHERE t.priority = 'low' AND tk.id_todos IS NULL")->fetch_assoc()['count'];

function getPriorityIcon($priority) {
    $icons = [
        'high' => 'fas fa-fire',
        'medium' => 'fas fa-equals',
        'low' => 'fas fa-chevron-down'
    ];
    return $icons[$priority] ?? 'fas fa-circle';
}
?>

<!-- Success/Error Messages -->
<?php if ($message): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?= $message ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-triangle"></i>
    <?= $error ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="header-content">
        <h1 class="page-title">Manajemen Todo</h1>
        <p class="page-subtitle">
            Kelola dan monitor semua tugas dalam sistem
        </p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card bg-gradient-blue">
        <div class="stat-icon">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $total_todos_stat ?></h3>
            <p class="stat-label">Todo Tersedia</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-red <?= $filter_priority == 'high' ? 'active' : '' ?>" onclick="filterByPriority('high')">
        <div class="stat-icon">
            <i class="fas fa-fire"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $high_priority ?></h3>
            <p class="stat-label">Prioritas Tinggi</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-orange <?= $filter_priority == 'medium' ? 'active' : '' ?>" onclick="filterByPriority('medium')">
        <div class="stat-icon">
            <i class="fas fa-equals"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $medium_priority ?></h3>
            <p class="stat-label">Prioritas Sedang</p>
        </div>
    </div>

    <div class="stat-card bg-gradient-green <?= $filter_priority == 'low' ? 'active' : '' ?>" onclick="filterByPriority('low')">
        <div class="stat-icon">
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-number"><?= $low_priority ?></h3>
            <p class="stat-label">Prioritas Rendah</p>
        </div>
    </div>
</div>

<!-- Todos Container -->
<div class="todos-container">
    <div class="section-header">
        <div class="section-title-wrapper">
            <h2 class="section-title">Daftar Todo</h2>
            <span class="section-count"><?= $total_todos ?> todo</span>
        </div>
        
        <!-- Filters -->
        <div class="filters-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari judul atau deskripsi..." 
                       value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
            </div>
            
            <div class="filter-dropdown">
                <select id="priorityFilter" onchange="applyFilters()">
                    <option value="">Semua Prioritas</option>
                    <option value="high" <?= $filter_priority == 'high' ? 'selected' : '' ?>>High Priority</option>
                    <option value="medium" <?= $filter_priority == 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                    <option value="low" <?= $filter_priority == 'low' ? 'selected' : '' ?>>Low Priority</option>
                </select>
            </div>

            <div class="filter-dropdown">
                <select id="appFilter" onchange="applyFilters()">
                    <option value="">Semua Aplikasi</option>
                    <?php 
                    $apps_result->data_seek(0);
                    while($app = $apps_result->fetch_assoc()): 
                    ?>
                    <option value="<?= $app['id'] ?>" <?= $filter_app == $app['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($app['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($filter_priority || $search || $filter_app): ?>
            <button class="btn-clear-filter" onclick="clearFilters()" title="Hapus Filter">
                <i class="fas fa-times"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add New Todo Button -->
    <div class="todo-list-item add-new-item" onclick="openAddTodoModal()">
        <div class="add-new-content">
            <div class="add-new-icon">
                <i class="fas fa-plus"></i>
            </div>
            <div class="add-new-text">
                <h3>Tambah Todo Baru</h3>
                <p>Klik untuk menambahkan todo baru</p>
            </div>
        </div>
    </div>
    
    <!-- Todos List -->
    <div class="todos-list">
        <?php if ($todos_result->num_rows > 0): ?>
            <?php while($todo = $todos_result->fetch_assoc()): ?>
            <div class="todo-list-item" data-todo-id="<?= $todo['id'] ?>">
                <div class="todo-priority-container">
                    <div class="todo-priority-badge priority-<?= $todo['priority'] ?>">
                        <i class="<?= getPriorityIcon($todo['priority']) ?>"></i>
                    </div>
                </div>
                
                <div class="todo-list-content">
                    <div class="todo-list-main">
                        <h3 class="todo-list-title"><?= htmlspecialchars($todo['title']) ?></h3>
                        <p class="todo-list-description">
                            <?= htmlspecialchars(substr($todo['description'], 0, 80)) ?>
                            <?= strlen($todo['description']) > 80 ? '...' : '' ?>
                        </p>
                        <div class="todo-list-details">
                            <span class="detail-badge app">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($todo['app_name']) ?>
                            </span>
                            <span class="detail-badge user">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($todo['user_name']) ?>
                            </span>
                            <span class="detail-badge date">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y H:i', strtotime($todo['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="todo-status-container">
                    <div class="status-badge status-available">
                        <i class="fas fa-hand-paper"></i>
                        Available
                    </div>
                </div>
                
                <div class="todo-list-actions">
                    <button class="action-btn-small edit" 
                            onclick="editTodo(<?= $todo['id'] ?>, <?= $todo['app_id'] ?>, '<?= htmlspecialchars($todo['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($todo['description'], ENT_QUOTES) ?>', '<?= $todo['priority'] ?>')" 
                            title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn-small delete" 
                            onclick="deleteTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars($todo['title'], ENT_QUOTES) ?>')" 
                            title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>Tidak ada todo ditemukan</h3>
                <p>Tidak ada todo yang sesuai dengan filter yang diterapkan.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_todos > 0): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            <span class="pagination-current">Halaman <?= $current_page ?> dari <?= $total_pages ?></span>
            <span class="pagination-total">Menampilkan <?= min($items_per_page, $total_todos - $offset) ?> dari <?= min($total_items, $total_todos) ?> todo</span>
        </div>
        
        <div class="pagination-controls">
            <!-- Previous Page -->
            <?php if ($current_page > 1): ?>
            <a href="?page=todos&priority=<?= $filter_priority ?>&app=<?= $filter_app ?>&search=<?= urlencode($search) ?>&pg=<?= $current_page - 1 ?>" class="pagination-btn pagination-btn-prev" title="Sebelumnya">
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
                        <a href="?page=todos&priority=<?= $filter_priority ?>&app=<?= $filter_app ?>&search=<?= urlencode($search) ?>&pg=<?= $i ?>" class="pagination-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            
            <!-- Next Page -->
            <?php if ($current_page < $total_pages): ?>
            <a href="?page=todos&priority=<?= $filter_priority ?>&app=<?= $filter_app ?>&search=<?= urlencode($search) ?>&pg=<?= $current_page + 1 ?>" class="pagination-btn pagination-btn-next" title="Selanjutnya">
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

<!-- Add/Edit Todo Modal -->
<div id="todoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Todo Baru</h3>
            <button class="modal-close" onclick="closeTodoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="todoForm" method="POST" action="?page=todos">
                <input type="hidden" id="todoId" name="todo_id">
                <div class="form-group">
                    <label for="todoApp">Aplikasi *</label>
                    <select id="todoApp" name="app_id" required>
                        <option value="">Pilih Aplikasi</option>
                        <?php 
                        $apps_result->data_seek(0);
                        while($app = $apps_result->fetch_assoc()): 
                        ?>
                        <option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="todoTitle">Judul Todo *</label>
                    <input type="text" id="todoTitle" name="title" required 
                           placeholder="Masukkan judul todo">
                </div>
                <div class="form-group">
                    <label for="todoDescription">Deskripsi</label>
                    <textarea id="todoDescription" name="description" rows="4"
                              placeholder="Deskripsi detail tentang todo"></textarea>
                </div>
                <div class="form-group">
                    <label for="todoPriority">Prioritas</label>
                    <div class="priority-selector">
                        <label class="priority-option">
                            <input type="radio" name="priority" value="low" id="priorityLow">
                            <span class="priority-badge priority-low">
                                <i class="fas fa-chevron-down"></i>
                                Low
                            </span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="medium" id="priorityMedium" checked>
                            <span class="priority-badge priority-medium">
                                <i class="fas fa-equals"></i>
                                Medium
                            </span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="high" id="priorityHigh">
                            <span class="priority-badge priority-high">
                                <i class="fas fa-fire"></i>
                                High
                            </span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTodoModal()">
                Batal
            </button>
            <button type="submit" id="submitBtn" form="todoForm" name="add_todo" class="btn btn-primary">
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
            <p id="deleteMessage">Apakah Anda yakin ingin menghapus todo ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <form id="deleteForm" method="POST" action="?page=todos" style="display: inline;">
                <input type="hidden" id="deleteTodoId" name="todo_id">
                <button type="submit" name="delete_todo" class="btn btn-danger">
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

.bg-gradient-red { 
    background: linear-gradient(135deg, #dc2626, #ef4444); 
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

/* Todos Container */
.todos-container {
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

/* Todos List */
.todos-list {
    max-height: 500px;
    overflow-y: auto;
}

.todos-list::-webkit-scrollbar {
    width: 6px;
}

.todos-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.todos-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.todos-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Todo List Items */
.todo-list-item {
    display: flex;
    align-items: center;
    padding: 14px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    cursor: pointer;
    gap: 16px;
    min-height: 80px;
}

.todo-list-item:hover {
    background: #f8fafc;
}

.todo-list-item:last-child {
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

.todo-priority-container {
    display: flex;
    align-items: center;
    margin-right: 16px;
    flex-shrink: 0;
}

.todo-priority-badge {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
}

.todo-priority-badge.priority-high {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.todo-priority-badge.priority-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.todo-priority-badge.priority-low {
    background: linear-gradient(135deg, #10b981, #059669);
}

/* Todo List Content */
.todo-list-content {
    flex: 1;
    min-width: 0;
}

.todo-list-main {
    flex: 1;
    min-width: 0;
}

.todo-list-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.todo-list-description {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0 0 8px 0;
}

.todo-list-details {
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

/* Todo Status Container */
.todo-status-container {
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

.status-badge.status-available {
    background: linear-gradient(90deg, #6b7280, #9ca3af);
}

/* Todo List Actions */
.todo-list-actions {
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
    margin-left: auto;
}

.todo-list-item:hover .todo-list-actions {
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

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Priority Selector */
.priority-selector {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.priority-option {
    cursor: pointer;
}

.priority-option input[type="radio"] {
    display: none;
}

.priority-badge {
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

.priority-badge.priority-low {
    background: linear-gradient(135deg, #10b981, #059669);
}

.priority-badge.priority-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.priority-badge.priority-high {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.priority-option input[type="radio"]:checked + .priority-badge {
    border-color: #1f2937;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transform: translateY(-2px);
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
    
    .filter-dropdown select {
        min-width: auto;
        flex: 1;
    }
    
    .todo-list-item {
        flex-wrap: wrap;
    }
    
    .todo-status-container {
        margin: 0;
        order: 3;
        width: 100%;
        margin-top: 8px;
    }
    
    .todo-list-actions {
        opacity: 1;
    }
    
    .todos-list {
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
    .todo-list-item {
        padding: 12px 16px;
    }
    
    .section-header {
        padding: 16px 20px 12px;
    }
    
    .add-new-item {
        margin: 12px 16px;
    }
    
    .priority-selector {
        flex-direction: column;
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

function openAddTodoModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Todo Baru';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_todo';
    document.getElementById('todoForm').reset();
    document.getElementById('todoId').value = '';
    document.getElementById('priorityMedium').checked = true;
    currentEditId = null;
    document.getElementById('todoModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editTodo(id, app_id, title, description, priority) {
    document.getElementById('modalTitle').textContent = 'Edit Todo';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('submitBtn').name = 'edit_todo';
    document.getElementById('todoId').value = id;
    document.getElementById('todoApp').value = app_id;
    document.getElementById('todoTitle').value = title;
    document.getElementById('todoDescription').value = description;
    
    if (priority === 'low') {
        document.getElementById('priorityLow').checked = true;
    } else if (priority === 'medium') {
        document.getElementById('priorityMedium').checked = true;
    } else if (priority === 'high') {
        document.getElementById('priorityHigh').checked = true;
    }
    
    currentEditId = id;
    document.getElementById('todoModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteTodo(id, title) {
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus todo "${title}"?`;
    document.getElementById('deleteTodoId').value = id;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeTodoModal() {
    document.getElementById('todoModal').classList.remove('show');
    document.body.style.overflow = '';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

function filterByPriority(priority) {
    let url = new URL(window.location);
    const currentPriority = url.searchParams.get('priority');
    
    if (currentPriority === priority) {
        url.searchParams.delete('priority');
    } else {
        url.searchParams.set('priority', priority);
    }
    
    url.searchParams.set('page', 'todos');
    url.searchParams.set('pg', '1');
    
    window.location.href = url.toString();
}

function applyFilters() {
    const priorityFilter = document.getElementById('priorityFilter').value;
    const appFilter = document.getElementById('appFilter').value;
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('priority');
    url.searchParams.delete('app');
    url.searchParams.delete('search');
    url.searchParams.set('page', 'todos');
    url.searchParams.set('pg', '1');
    
    if (priorityFilter) {
        url.searchParams.set('priority', priorityFilter);
    }
    if (appFilter) {
        url.searchParams.set('app', appFilter);
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
    url.searchParams.delete('priority');
    url.searchParams.delete('app');
    url.searchParams.delete('search');
    url.searchParams.set('page', 'todos');
    url.searchParams.set('pg', '1');
    window.location.href = url.toString();
}

function jumpToPage() {
    const select = document.getElementById('pageJumpSelect');
    const page = parseInt(select.value);
    const priorityFilter = document.getElementById('priorityFilter') ? document.getElementById('priorityFilter').value : '';
    const appFilter = document.getElementById('appFilter') ? document.getElementById('appFilter').value : '';
    const searchValue = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    
    let url = new URL(window.location);
    url.searchParams.set('page', 'todos');
    url.searchParams.set('pg', page);
    
    if (priorityFilter) url.searchParams.set('priority', priorityFilter);
    if (appFilter) url.searchParams.set('app', appFilter);
    if (searchValue) url.searchParams.set('search', searchValue);
    
    window.location.href = url.toString();
}

document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeTodoModal();
        closeDeleteModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTodoModal();
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

if (document.getElementById('todoForm')) {
    document.getElementById('todoForm').addEventListener('submit', function(e) {
        const title = document.getElementById('todoTitle').value.trim();
        const appId = document.getElementById('todoApp').value;
        
        if (!title) {
            e.preventDefault();
            alert('Judul todo harus diisi!');
            document.getElementById('todoTitle').focus();
            return false;
        }
        
        if (!appId) {
            e.preventDefault();
            alert('Aplikasi harus dipilih!');
            document.getElementById('todoApp').focus();
            return false;
        }
    });
}

const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.target.classList.contains('modal') && mutation.target.classList.contains('show')) {
            setTimeout(() => {
                const firstInput = mutation.target.querySelector('input[type="text"], select');
                if (firstInput) {
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