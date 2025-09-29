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

// Pagination setup
$limit = 10; // Items per page
$page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$filter_app = isset($_GET['app']) ? (int)$_GET['app'] : 0;

// Build WHERE clause for filters
$where_conditions = [];
$params = [];
$param_types = '';

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

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

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
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $koneksi->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Get todos data with pagination
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
    LIMIT ? OFFSET ?
";

// Prepare parameters for pagination query
$pagination_params = $params; // Copy existing params
$pagination_param_types = $param_types;

// Add LIMIT and OFFSET parameters
$pagination_params[] = $limit;
$pagination_params[] = $offset;
$pagination_param_types .= 'ii';

$todos_stmt = $koneksi->prepare($todos_query);
if (!empty($pagination_params) && $pagination_param_types) {
    $todos_stmt->bind_param($pagination_param_types, ...$pagination_params);
}
$todos_stmt->execute();
$todos_result = $todos_stmt->get_result();

// Get apps for dropdown
$apps_query = "SELECT id, name FROM apps ORDER BY name";
$apps_result = $koneksi->query($apps_query);

// Get statistics
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];
$high_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'high'")->fetch_assoc()['count'];
$medium_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'medium'")->fetch_assoc()['count'];
$low_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'low'")->fetch_assoc()['count'];

function getPriorityIcon($priority) {
    $icons = [
        'high' => 'fas fa-exclamation-triangle',
        'medium' => 'fas fa-minus',
        'low' => 'fas fa-arrow-down'
    ];
    return $icons[$priority] ?? 'fas fa-circle';
}

function getPriorityColor($priority) {
    $colors = [
        'high' => '#ef4444',
        'medium' => '#f59e0b',
        'low' => '#10b981'
    ];
    return $colors[$priority] ?? '#6b7280';
}
?>

<div class="main-content" style="margin-top: 80px;">
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
            <h1 class="page-title">
                <i class="fas fa-tasks mr-3"></i>
                Manajemen Todo
            </h1>
            <p class="page-subtitle">
                Kelola dan monitor semua tugas dalam sistem
            </p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-list-check"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_todos ?></h3>
                <p class="stat-label">Total Todo</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-red <?= $filter_priority == 'high' ? 'active' : '' ?>" onclick="filterByPriority('high')">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $high_priority ?></h3>
                <p class="stat-label">High Priority</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange <?= $filter_priority == 'medium' ? 'active' : '' ?>" onclick="filterByPriority('medium')">
            <div class="stat-icon">
                <i class="fas fa-minus-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $medium_priority ?></h3>
                <p class="stat-label">Medium Priority</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green <?= $filter_priority == 'low' ? 'active' : '' ?>" onclick="filterByPriority('low')">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $low_priority ?></h3>
                <p class="stat-label">Low Priority</p>
            </div>
        </div>
    </div>

    <!-- Todos Container -->
    <div class="todos-container">
        <div class="section-header">
            <div class="section-title-container">
                <h2 class="section-title">Daftar Todo</h2>
                <span class="section-count"><?= $total_records ?> todo</span>
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
                <?php $no = $offset + 1; while($todo = $todos_result->fetch_assoc()): ?>
                <div class="todo-list-item" data-todo-id="<?= $todo['id'] ?>">
                    <div class="todo-priority-container">
                        <div class="todo-priority-badge priority-<?= $todo['priority'] ?>">
                            <i class="<?= getPriorityIcon($todo['priority']) ?>"></i>
                        </div>
                        <div class="todo-number"><?= $no++ ?></div>
                    </div>
                    
                    <div class="todo-list-content">
                        <div class="todo-list-main">
                            <div class="todo-title-section">
                                <h3 class="todo-list-title"><?= htmlspecialchars($todo['title']) ?></h3>
                                <span class="todo-priority-text priority-<?= $todo['priority'] ?>"><?= ucfirst($todo['priority']) ?></span>
                            </div>
                            <div class="todo-description">
                                <?= htmlspecialchars($todo['description']) ?>
                            </div>
                            <div class="todo-details">
                                <span class="todo-app">
                                    <i class="fas fa-cube"></i>
                                    <?= htmlspecialchars($todo['app_name']) ?>
                                </span>
                                <span class="todo-user">
                                    <i class="fas fa-user"></i>
                                    <?= htmlspecialchars($todo['user_name']) ?>
                                </span>
                                <span class="todo-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m/Y H:i', strtotime($todo['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="todo-status-container">
                        <?php if($todo['taken_status']): ?>
                        <div class="status-badge status-<?= $todo['taken_status'] ?>">
                            <i class="fas fa-<?= $todo['taken_status'] == 'done' ? 'check-circle' : 'clock' ?>"></i>
                            <?= $todo['taken_status'] == 'done' ? 'Completed' : 'In Progress' ?>
                        </div>
                        <?php else: ?>
                        <div class="status-badge status-available">
                            <i class="fas fa-hand-paper"></i>
                            Available
                        </div>
                        <?php endif; ?>
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
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php
                // Build query string for pagination
                $query_params = ['page' => 'todos'];
                if (!empty($search)) $query_params['search'] = $search;
                if (!empty($filter_priority)) $query_params['priority'] = $filter_priority;
                if (!empty($filter_app)) $query_params['app'] = $filter_app;
                
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
                
                if ($start > 1) {
                    echo '<span class="pagination-dots">...</span>';
                }
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?page_num=<?= $i ?><?= $query_string ?>" 
                   class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($end < $total_pages) {
                    echo '<span class="pagination-dots">...</span>';
                } ?>

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
                    <select id="todoPriority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
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
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
    transition: all 0.3s ease;
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
.bg-gradient-red { background: linear-gradient(135deg, #dc2626, #ef4444); color: white; }
.bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }

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

/* Todos Container */
.todos-container {
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

.filter-dropdown select {
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    min-width: 150px;
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
    max-height: 600px;
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

.todo-list-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    cursor: pointer;
}

.todo-list-item:hover {
    background: #f8fafc;
}

.todo-list-item:last-child {
    border-bottom: none;
}

/* Add New Item */
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

/* Todo Priority Container */
.todo-priority-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 16px;
    flex-shrink: 0;
}

.todo-priority-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    margin-bottom: 8px;
}

.todo-priority-badge.priority-high {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.todo-priority-badge.priority-medium {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.todo-priority-badge.priority-low {
    background: linear-gradient(90deg, #10b981, #059669);
}

.todo-number {
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

/* Todo List Content */
.todo-list-content {
    flex: 1;
    min-width: 0;
}

.todo-list-main {
    flex: 1;
    min-width: 0;
}

.todo-title-section {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.todo-list-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    flex: 1;
}

.todo-priority-text {
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    text-transform: uppercase;
}

.todo-priority-text.priority-high {
    background: #ef4444;
}

.todo-priority-text.priority-medium {
    background: #f59e0b;
}

.todo-priority-text.priority-low {
    background: #10b981;
}

.todo-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 12px;
}

.todo-details {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #6b7280;
    flex-wrap: wrap;
}

.todo-app,
.todo-user,
.todo-date {
    display: flex;
    align-items: center;
    gap: 6px;
}

.todo-app i,
.todo-user i,
.todo-date i {
    width: 16px;
    font-size: 0.8rem;
}

/* Todo Status Container */
.todo-status-container {
    margin: 0 20px;
    flex-shrink: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.status-badge.status-done {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.status-badge.status-in_progress {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.status-badge.status-available {
    background: linear-gradient(90deg, #6b7280, #9ca3af);
}

/* Todo List Actions */
.todo-list-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.todo-list-item:hover .todo-list-actions {
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
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    
    .filters-container {
        justify-content: stretch;
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
        gap: 12px;
        padding: 16px;
    }
    
    .todo-priority-container {
        flex-direction: row;
        align-items: center;
        gap: 8px;
        margin-right: 0;
        margin-bottom: 8px;
    }
    
    .todo-number {
        margin-bottom: 0;
    }
    
    .todo-details {
        flex-direction: column;
        gap: 8px;
    }
    
    .todo-status-container {
        margin: 8px 0 0 0;
        order: 3;
    }
    
    .todo-list-actions {
        opacity: 1;
        margin-top: 8px;
        justify-content: center;
        order: 4;
    }
    
    .pagination-container {
        flex-direction: column;
        text-align: center;
    }
    
    .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .todos-list {
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

function openAddTodoModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Todo Baru';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_todo';
    document.getElementById('todoForm').reset();
    document.getElementById('todoId').value = '';
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
    document.getElementById('todoPriority').value = priority;
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

// Filter by priority from stat cards
function filterByPriority(priority) {
    let url = new URL(window.location);
    const currentPriority = url.searchParams.get('priority');
    
    // Toggle filter: if already filtering by this priority, clear the filter
    if (currentPriority === priority) {
        url.searchParams.delete('priority');
    } else {
        url.searchParams.set('priority', priority);
    }
    
    // Keep page parameter but reset to page 1
    url.searchParams.set('page', 'todos');
    url.searchParams.delete('page_num');
    
    window.location.href = url.toString();
}

// Filter functions
function applyFilters() {
    const priorityFilter = document.getElementById('priorityFilter').value;
    const appFilter = document.getElementById('appFilter').value;
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('priority');
    url.searchParams.delete('app');
    url.searchParams.delete('search');
    url.searchParams.delete('page_num'); // Reset to first page when filtering
    url.searchParams.set('page', 'todos'); // Keep the page parameter
    
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
    url.searchParams.delete('page_num');
    url.searchParams.set('page', 'todos');
    window.location.href = url.toString();
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeTodoModal();
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
</script>