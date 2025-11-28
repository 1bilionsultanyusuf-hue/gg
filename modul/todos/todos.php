<?php
// Handle CRUD Operations for Todos
$message = '';
$error = '';

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// DELETE - Remove todo (only for admin and support)
if (isset($_POST['delete_todo']) && in_array($_SESSION['user_role'], ['admin', 'support'])) {
    $id = $_POST['todo_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Todo berhasil dihapus!";
        header("Location: ?page=todos");
        exit;
    } else {
        $error = "Gagal menghapus todo!";
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$filter_app = isset($_GET['app']) ? (int)$_GET['app'] : 0;
$filter_status = isset($_GET['taken_status']) ? trim($_GET['taken_status']) : '';

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

if ($filter_status === 'available') {
    $where_conditions[] = "tk.id_todos IS NULL";
} elseif ($filter_status === 'taken') {
    $where_conditions[] = "tk.id_todos IS NOT NULL";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// PAGINATION SETUP
$items_per_page = 5;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    LEFT JOIN users taker ON tk.user_id = taker.id
    LEFT JOIN users sender ON t.user_id = sender.id
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

$total_pages = $total_todos > 0 ? ceil($total_todos / $items_per_page) : 1;

// Get todos data with sender and taker info - sorted by taken status and priority
$todos_query = "
    SELECT t.*, 
           a.name as app_name,
           tk.status as taken_status,
           tk.date as taken_date,
           taker.name as taker_name,
           taker.id as taker_id,
           sender.name as sender_name,
           sender.id as sender_id,
           CASE WHEN tk.id_todos IS NULL THEN 0 ELSE 1 END as is_taken
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    LEFT JOIN users taker ON tk.user_id = taker.id
    LEFT JOIN users sender ON t.user_id = sender.id
    $where_clause
    ORDER BY 
        is_taken ASC,
        CASE t.priority
            WHEN 'high' THEN 1
            WHEN 'medium' THEN 2
            WHEN 'low' THEN 3
            ELSE 4
        END,
        t.created_at DESC
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

function getPriorityBadge($priority) {
    $badges = [
        'high' => '<span class="priority-badge badge-high">High</span>',
        'medium' => '<span class="priority-badge badge-medium">Medium</span>',
        'low' => '<span class="priority-badge badge-low">Low</span>'
    ];
    return $badges[$priority] ?? '<span class="priority-badge">-</span>';
}

function getStatusBadge($taker_id, $status) {
    if (!$taker_id) {
        return '<span class="status-badge badge-available">Available</span>';
    }
    return $status == 'done' 
        ? '<span class="status-badge badge-done">Completed</span>' 
        : '<span class="status-badge badge-progress">In Progress</span>';
}

// Check if user can edit/delete todos
$canManageTodos = in_array($_SESSION['user_role'], ['admin', 'support']);
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f6fa;
    color: #2c3e50;
}

.container {
    max-width: 100%;
    margin: 0;
    padding: 20px 30px;
    background: #f5f6fa;
}

/* Page Header */
.page-header {
    margin-bottom: 16px;
    padding: 8px 30px;
    background: #f5f6fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 8px;
}

/* Filters */
.filters-container {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

/* Content Box */
.content-box {
    background: white;
    border-radius: 0;
    padding: 26px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter-select {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    min-width: 170px;
    background: white;
    cursor: pointer;
}

.search-input {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    min-width: 270px;
}

.btn-clear {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-clear:hover {
    background: #c0392b;
}

.btn-add-todo {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #0d8af5;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: auto;
    text-decoration: none;
}

.btn-add-todo:hover {
    background: #0b7ad6;
}

/* Table Container */
.table-container {
    background: white;
    border-radius: 0;
    overflow: hidden;
    border: 1px solid #ddd;
    margin-bottom: 0;
}

/* Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
    border: none;
    table-layout: fixed;
}

.data-table thead {
    background: linear-gradient(135deg, #747f88ff 0%, #747f88ff 100%);
    color: white;
}

.data-table th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 1.02rem;
    text-transform: capitalize;
    border-right: 2px solid rgba(255, 255, 255, 0.3);
    border-bottom: 2px solid #0b7ad6;
}

.data-table th:last-child {
    border-right: none;
}

.data-table th:first-child {
    width: 70px;
    text-align: center;
}

<?php if ($canManageTodos): ?>
.data-table th:nth-child(2) { /* Judul */
    width: 180px;
}

.data-table th:nth-child(3) { /* Aplikasi */
    width: 130px;
}

.data-table th:nth-child(4) { /* Deskripsi */
    width: 250px;
}

.data-table th:nth-child(5) { /* Prioritas */
    width: 110px;
}

.data-table th:nth-child(6) { /* Status */
    width: 120px;
}

.data-table th:nth-child(7) { /* Dikirim Oleh */
    width: 140px;
}

.data-table th:nth-child(8) { /* Diambil Oleh */
    width: 140px;
}

.data-table th:last-child {
    width: 100px;
    text-align: center;
}
<?php else: ?>
.data-table th:nth-child(2) { /* Judul */
    width: 200px;
}

.data-table th:nth-child(3) { /* Aplikasi */
    width: 150px;
}

.data-table th:nth-child(4) { /* Deskripsi */
    width: 280px;
}

.data-table th:nth-child(5) { /* Prioritas */
    width: 130px;
}

.data-table th:nth-child(6) { /* Status */
    width: 140px;
}

.data-table th:nth-child(7) { /* Dikirim Oleh */
    width: 160px;
}

.data-table th:nth-child(8) { /* Diambil Oleh */
    width: 160px;
}
<?php endif; ?>

.data-table tbody tr {
    border-bottom: 2px solid #e0e0e0;
    transition: all 0.3s ease;
}

.data-table tbody tr:hover {
    background: #e8eef5 !important;
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.data-table tbody tr.clickable-row {
    cursor: pointer;
}

.data-table td {
    padding: 15px 20px;
    font-size: 0.96rem;
    color: #555;
    border-right: 2px solid #e0e0e0;
    background: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.data-table td:last-child {
    border-right: none;
}

.data-table td:first-child {
    text-align: center;
    font-weight: 600;
    color: #777;
    background: white;
}

/* Truncate text in table cells */
.truncate-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    max-width: 100%;
}

/* User Info Styling */
.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-name {
    font-weight: 500;
    color: #333;
}

.user-empty {
    color: #999;
    font-style: italic;
    font-size: 0.9rem;
}

/* Badges */
.priority-badge,
.status-badge {
    display: inline-block;
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 0.86rem;
    font-weight: 500;
}

.badge-high {
    background: #fee;
    color: #e74c3c;
}

.badge-medium {
    background: #fff4e6;
    color: #f39c12;
}

.badge-low {
    background: #e8f5e9;
    color: #27ae60;
}

.badge-available {
    background: #e3f2fd;
    color: #2196f3;
}

.badge-progress {
    background: #fff4e6;
    color: #f39c12;
}

.badge-done {
    background: #e8f5e9;
    color: #27ae60;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 7px;
    justify-content: center;
}

.btn-action {
    width: 37px;
    height: 37px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 0.96rem;
    text-decoration: none;
}

.btn-edit {
    background: #e3f2fd;
    color: #2196f3;
}

.btn-edit:hover {
    background: #2196f3;
    color: white;
}

.btn-delete {
    background: #ffebee;
    color: #e74c3c;
}

.btn-delete:hover {
    background: #e74c3c;
    color: white;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 22px 0;
    gap: 7px;
    background: transparent;
}

.page-btn {
    min-width: 39px;
    height: 39px;
    border: 2px solid #ddd;
    background: white;
    color: #555;
    border-radius: 50%;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.96rem;
    font-weight: 500;
}

.page-btn:hover {
    border-color: #0d8af5;
    color: #0d8af5;
    background: #e3f2fd;
}

.page-btn.active {
    background: #0d8af5;
    color: white;
    border-color: #0d8af5;
}

/* No Data */
.no-data {
    text-align: center;
    padding: 50px 20px;
    color: #999;
    border: none !important;
}

.no-data i {
    font-size: 2.8rem;
    margin-bottom: 12px;
    color: #ddd;
}

.no-data h3 {
    font-size: 1.15rem;
    margin-bottom: 6px;
}

.no-data p {
    font-size: 0.92rem;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
}

/* Custom Searchable Dropdown */
.searchable-dropdown {
    position: relative;
    width: 100%;
}

.dropdown-selected {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.96rem;
}

.dropdown-selected:hover {
    border-color: #0d8af5;
}

.dropdown-selected.active {
    border-color: #0d8af5;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.dropdown-arrow {
    transition: transform 0.2s;
}

.dropdown-arrow.rotated {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #0d8af5;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.dropdown-menu.show {
    display: block;
}

.dropdown-search {
    position: sticky;
    top: 0;
    background: white;
    padding: 8px;
    border-bottom: 1px solid #eee;
}

.dropdown-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.dropdown-search input:focus {
    outline: none;
    border-color: #0d8af5;
}

.dropdown-options {
    max-height: 240px;
    overflow-y: auto;
}

.dropdown-option {
    padding: 10px 16px;
    cursor: pointer;
    font-size: 0.96rem;
    transition: background 0.2s;
}

.dropdown-option:hover {
    background: #e3f2fd;
}

.dropdown-option.selected {
    background: #0d8af5;
    color: white;
}

.dropdown-option.hidden {
    display: none;
}

.dropdown-no-result {
    padding: 20px;
    text-align: center;
    color: #999;
    font-size: 0.9rem;
}

.modal-header {
    padding: 18px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 1.2rem;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.4rem;
    color: #999;
    cursor: pointer;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.modal-close:hover {
    background: #f5f5f5;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.modal-body p {
    color: #6b7280;
    line-height: 1.5;
}

.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-secondary {
    padding: 9px 18px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-secondary:hover {
    background: #f5f5f5;
}

.btn-primary {
    padding: 9px 18px;
    border: none;
    background: #0d8af5;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-primary:hover {
    background: #0b7ad6;
}

.btn-danger {
    background: #e74c3c;
}

.btn-danger:hover {
    background: #c0392b;
}

/* Alert */
.alert {
    padding: 11px 17px;
    border-radius: 6px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Responsive */
@media (max-width: 768px) {
    .filters-container {
        flex-direction: column;
    }
    
    .filter-select,
    .search-input {
        width: 100%;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .data-table {
        min-width: 1000px;
    }
}
</style>

<!-- Alerts -->
<?php if ($message): ?>
<div style="padding: 0 30px 14px 30px;">
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="padding: 0 30px 14px 30px;">
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Data Todo dan Manajemen Tugas</h1>
</div>

<div class="container">
    <div class="content-box">
        <!-- Filters -->
        <div class="filters-container">
            <input type="text" class="search-input" id="searchInput" placeholder="Cari todo..." 
                   value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
            
            <select class="filter-select" id="priorityFilter" onchange="applyFilters()">
                <option value="">Semua Prioritas</option>
                <option value="high" <?= $filter_priority == 'high' ? 'selected' : '' ?>>High Priority</option>
                <option value="medium" <?= $filter_priority == 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                <option value="low" <?= $filter_priority == 'low' ? 'selected' : '' ?>>Low Priority</option>
            </select>

            <select class="filter-select" id="appFilter" onchange="applyFilters()">
                <option value="">Semua Aplikasi</option>
                <?php 
                $apps_query = "SELECT id, name FROM apps ORDER BY name";
                $apps_result = $koneksi->query($apps_query);
                while($app = $apps_result->fetch_assoc()): 
                ?>
                <option value="<?= $app['id'] ?>" <?= $filter_app == $app['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($app['name']) ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                <option value="">Semua Status</option>
                <option value="available" <?= $filter_status == 'available' ? 'selected' : '' ?>>Available</option>
                <option value="taken" <?= $filter_status == 'taken' ? 'selected' : '' ?>>Sudah Diambil</option>
            </select>
            
            <?php if ($filter_priority || $search || $filter_app || $filter_status): ?>
            <button class="btn-clear" onclick="clearFilters()">
                <i class="fas fa-times"></i> Clear
            </button>
            <?php endif; ?>
            
            <?php if ($canManageTodos): ?>
            <button class="btn-add-todo" onclick="showSelectAppModal()">
                <i class="fas fa-plus"></i>
                <span>Tambah Todo</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul Todo</th>
                        <th>Aplikasi</th>
                        <th>Deskripsi</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Dikirim Oleh</th>
                        <th>Diambil Oleh</th>
                        <?php if ($canManageTodos): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($todos_result->num_rows > 0): ?>
                        <?php 
                        $no = $offset + 1;
                        while($todo = $todos_result->fetch_assoc()): 
                        ?>
                        <tr class="clickable-row" data-href="?page=detail_todos&id=<?= $todo['id'] ?>">
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="truncate-text" title="<?= htmlspecialchars($todo['title']) ?>">
                                    <?= htmlspecialchars($todo['title']) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="truncate-text" title="<?= htmlspecialchars($todo['app_name']) ?>">
                                    <?= htmlspecialchars($todo['app_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="truncate-text" title="<?= htmlspecialchars($todo['description']) ?>">
                                    <?= htmlspecialchars($todo['description']) ?>
                                </span>
                            </td>
                            <td><?= getPriorityBadge($todo['priority']) ?></td>
                            <td><?= getStatusBadge($todo['taker_id'], $todo['taken_status']) ?></td>
                            <td>
                                <?php if ($todo['sender_name']): ?>
                                    <div class="user-info">
                                        <span class="user-name truncate-text" title="<?= htmlspecialchars($todo['sender_name']) ?>">
                                            <?= htmlspecialchars($todo['sender_name']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="user-empty">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($todo['taker_name']): ?>
                                    <div class="user-info">
                                        <span class="user-name truncate-text" title="<?= htmlspecialchars($todo['taker_name']) ?>">
                                            <?= htmlspecialchars($todo['taker_name']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="user-empty">Belum diambil</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManageTodos): ?>
                            <td>
                                <div class="action-buttons">
                                    <a href="?page=edit_todos&id=<?= $todo['id'] ?>" 
                                       class="btn-action btn-edit" 
                                       onclick="event.stopPropagation()"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn-action btn-delete" 
                                            onclick="event.stopPropagation(); deleteTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars($todo['title'], ENT_QUOTES) ?>')"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $canManageTodos ? '9' : '8' ?>" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <h3>Belum ada data</h3>
                                <p>Tidak ada todo yang sesuai dengan pencarian</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_todos > $items_per_page): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=todos&priority=<?= $filter_priority ?>&app=<?= $filter_app ?>&search=<?= urlencode($search) ?>&taken_status=<?= $filter_status ?>&pg=<?= $i ?>" 
                   class="page-btn <?= $i == $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canManageTodos): ?>
<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Konfirmasi Hapus</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 12px;">Apakah Anda yakin ingin menghapus todo:</p>
            <p style="font-weight: 600; color: #e74c3c;" id="deleteTodoTitle"></p>
            <form id="deleteForm" method="POST" action="?page=todos">
                <input type="hidden" id="deleteId" name="todo_id">
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Batal</button>
            <button type="submit" form="deleteForm" name="delete_todo" class="btn-primary btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </div>
    </div>
</div>

<!-- Select App Modal for Adding Todo -->
<div id="selectAppModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Pilih Aplikasi</h3>
            <button class="modal-close" onclick="closeSelectAppModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 16px; color: #6b7280;">Pilih aplikasi untuk todo baru:</p>
            
            <!-- Custom Searchable Dropdown -->
            <div class="searchable-dropdown">
                <div class="dropdown-selected" id="dropdownSelected" onclick="toggleDropdown()">
                    <span id="selectedText">-- Pilih Aplikasi --</span>
                    <i class="fas fa-chevron-down dropdown-arrow" id="dropdownArrow"></i>
                </div>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-search">
                        <input type="text" 
                               id="dropdownSearch" 
                               placeholder="Cari aplikasi..." 
                               onkeyup="filterDropdownOptions()"
                               onclick="event.stopPropagation()">
                    </div>
                    <div class="dropdown-options" id="dropdownOptions">
                        <?php 
                        $apps_query = "SELECT id, name FROM apps ORDER BY name";
                        $apps_result = $koneksi->query($apps_query);
                        while($app = $apps_result->fetch_assoc()): 
                        ?>
                        <div class="dropdown-option" 
                             data-value="<?= $app['id'] ?>" 
                             data-name="<?= htmlspecialchars($app['name']) ?>"
                             onclick="selectDropdownOption(this)">
                            <?= htmlspecialchars($app['name']) ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="dropdown-no-result" id="dropdownNoResult" style="display: none;">
                        <i class="fas fa-search"></i>
                        <p>Aplikasi tidak ditemukan</p>
                    </div>
                </div>
            </div>
            
            <input type="hidden" id="selectedAppId" value="">
            <input type="hidden" id="selectedAppName" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeSelectAppModal()">Batal</button>
            <button type="button" class="btn-primary" onclick="redirectToAddTodo()">
                <i class="fas fa-arrow-right"></i> Lanjutkan
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Make table rows clickable
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.clickable-row');
    rows.forEach(row => {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.href;
        });
    });
});

// Search functionality
let searchTimeout;
function handleSearch(event) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const priority = document.getElementById('priorityFilter').value;
    const app = document.getElementById('appFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = '?page=todos';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (priority) url += '&priority=' + priority;
    if (app) url += '&app=' + app;
    if (status) url += '&taken_status=' + status;
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = '?page=todos';
}

<?php if ($canManageTodos): ?>
// Delete modal functions
function deleteTodo(id, title) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTodoTitle').textContent = title;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Select App Modal functions
function showSelectAppModal() {
    document.getElementById('selectAppModal').classList.add('show');
    resetDropdown();
    document.body.style.overflow = 'hidden';
}

function closeSelectAppModal() {
    document.getElementById('selectAppModal').classList.remove('show');
    closeDropdown();
    document.body.style.overflow = '';
}

// Custom Dropdown functions
function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    const arrow = document.getElementById('dropdownArrow');
    const selected = document.getElementById('dropdownSelected');
    
    if (menu.classList.contains('show')) {
        closeDropdown();
    } else {
        menu.classList.add('show');
        arrow.classList.add('rotated');
        selected.classList.add('active');
        document.getElementById('dropdownSearch').focus();
    }
}

function closeDropdown() {
    const menu = document.getElementById('dropdownMenu');
    const arrow = document.getElementById('dropdownArrow');
    const selected = document.getElementById('dropdownSelected');
    
    menu.classList.remove('show');
    arrow.classList.remove('rotated');
    selected.classList.remove('active');
}

function selectDropdownOption(element) {
    const appId = element.getAttribute('data-value');
    const appName = element.getAttribute('data-name');
    
    // Update selected text
    document.getElementById('selectedText').textContent = appName;
    
    // Update hidden inputs
    document.getElementById('selectedAppId').value = appId;
    document.getElementById('selectedAppName').value = appName;
    
    // Update selected styling
    document.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    element.classList.add('selected');
    
    // Close dropdown
    closeDropdown();
}

function filterDropdownOptions() {
    const searchValue = document.getElementById('dropdownSearch').value.toLowerCase();
    const options = document.querySelectorAll('.dropdown-option');
    const noResult = document.getElementById('dropdownNoResult');
    let hasVisibleOption = false;
    
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        
        if (text.includes(searchValue)) {
            option.classList.remove('hidden');
            hasVisibleOption = true;
        } else {
            option.classList.add('hidden');
        }
    });
    
    // Show/hide no result message
    if (hasVisibleOption) {
        noResult.style.display = 'none';
    } else {
        noResult.style.display = 'block';
    }
}

function resetDropdown() {
    // Reset selected text
    document.getElementById('selectedText').textContent = '-- Pilih Aplikasi --';
    
    // Reset hidden inputs
    document.getElementById('selectedAppId').value = '';
    document.getElementById('selectedAppName').value = '';
    
    // Reset search
    document.getElementById('dropdownSearch').value = '';
    
    // Show all options
    document.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.classList.remove('hidden');
        opt.classList.remove('selected');
    });
    
    // Hide no result message
    document.getElementById('dropdownNoResult').style.display = 'none';
    
    // Close dropdown
    closeDropdown();
}

function redirectToAddTodo() {
    const appId = document.getElementById('selectedAppId').value;
    const appName = document.getElementById('selectedAppName').value;
    
    if (!appId) {
        alert('Silakan pilih aplikasi terlebih dahulu!');
        return;
    }
    
    window.location.href = '?page=tambah_apps&action=add_todo&app_id=' + appId + '&app_name=' + encodeURIComponent(appName);
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.searchable-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        closeDropdown();
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const selectAppModal = document.getElementById('selectAppModal');
    
    if (event.target == deleteModal) {
        closeDeleteModal();
    }
    if (event.target == selectAppModal) {
        closeSelectAppModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
        closeSelectAppModal();
    }
});
<?php endif; ?>
</script>