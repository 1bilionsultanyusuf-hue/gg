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

// CREATE - Add new taken
if (isset($_POST['add_taken'])) {
    $id_todos = (int)$_POST['id_todos'];
    $status = 'in_progress';
    $date = date('Y-m-d');
    $user_id = $_SESSION['user_id'];
    
    if (!empty($id_todos)) {
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
        $error = "Pilih todo yang ingin diambil!";
    }
}

// PENDING - Mark taken as pending
if (isset($_POST['pending_taken'])) {
    $id = (int)$_POST['taken_id'];
    
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $current_user_id) {
            $status = 'pending';
            $date = date('Y-m-d');
            $stmt = $koneksi->prepare("UPDATE taken SET status = ?, date = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $date, $id);
            
            if ($stmt->execute()) {
                $message = "Todo berhasil ditandai pending!";
            } else {
                $error = "Gagal menandai todo pending!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk mem-pending taken ini!";
        }
    } else {
        $error = "Taken tidak ditemukan!";
    }
}

// RESUME - Resume from pending to in_progress
if (isset($_POST['resume_taken'])) {
    $id = (int)$_POST['taken_id'];
    
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $current_user_id) {
            $status = 'in_progress';
            $date = date('Y-m-d');
            $stmt = $koneksi->prepare("UPDATE taken SET status = ?, date = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $date, $id);
            
            if ($stmt->execute()) {
                $message = "Todo berhasil dilanjutkan!";
            } else {
                $error = "Gagal melanjutkan todo!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk melanjutkan taken ini!";
        }
    } else {
        $error = "Taken tidak ditemukan!";
    }
}

// COMPLETE - Mark as done
if (isset($_POST['complete_taken'])) {
    $id = (int)$_POST['taken_id'];
    
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $current_user_id) {
            $status = 'done';
            $date = date('Y-m-d');
            $stmt = $koneksi->prepare("UPDATE taken SET status = ?, date = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $date, $id);
            
            if ($stmt->execute()) {
                $message = "Todo berhasil ditandai selesai!";
            } else {
                $error = "Gagal menandai todo selesai!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk mengedit taken ini!";
        }
    } else {
        $error = "Taken tidak ditemukan!";
    }
}

// CANCEL - Cancel taken
if (isset($_POST['cancel_taken'])) {
    $id = (int)$_POST['taken_id'];
    
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
                $message = "Todo berhasil dibatalkan dan kembali tersedia untuk user lain!";
            } else {
                $error = "Gagal membatalkan todo!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk membatalkan taken ini!";
        }
    } else {
        $error = "Taken tidak ditemukan!";
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

$where_conditions[] = "tk.user_id = ?";
$params[] = $current_user_id;
$param_types .= 'i';

if (!empty($search)) {
    $where_conditions[] = "(td.title LIKE ? OR td.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

if (!empty($filter_status) && in_array($filter_status, ['in_progress', 'done', 'pending'])) {
    $where_conditions[] = "tk.status = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

if (!empty($filter_priority) && in_array($filter_priority, ['high', 'medium', 'low'])) {
    $where_conditions[] = "td.priority = ?";
    $params[] = $filter_priority;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// PAGINATION SETUP
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

$total_pages = $total_taken > 0 ? ceil($total_taken / $items_per_page) : 1;

// Get taken data with PAGINATION
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

// Get available todos
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

function getPriorityBadgeTaken($priority) {
    $badges = [
        'high' => '<span class="priority-badge badge-high">High</span>',
        'medium' => '<span class="priority-badge badge-medium">Medium</span>',
        'low' => '<span class="priority-badge badge-low">Low</span>'
    ];
    return $badges[$priority] ?? '<span class="priority-badge">-</span>';
}

function getStatusBadgeTaken($status) {
    $badges = [
        'done' => '<span class="status-badge badge-done">Completed</span>',
        'pending' => '<span class="status-badge badge-pending">Pending</span>',
        'in_progress' => '<span class="status-badge badge-progress">In Progress</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">-</span>';
}
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

.container-taken {
    max-width: 100%;
    margin: 0;
    padding: 20px 30px;
    background: #f5f6fa;
}

/* Alert Messages */
.alert-taken {
    padding: 11px 17px;
    border-radius: 6px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
}

.alert-taken.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-taken.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Page Header */
.page-header-taken {
    margin-bottom: 16px;
    padding: 8px 30px;
    background: #f5f6fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title-taken {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 8px;
}

/* Content Box */
.content-box-taken {
    background: white;
    border-radius: 0;
    padding: 26px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Filters */
.filters-container-taken {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.filter-select-taken {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    min-width: 170px;
    background: white;
    cursor: pointer;
}

.search-input-taken {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    min-width: 270px;
}

.btn-clear-taken {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-clear-taken:hover {
    background: #c0392b;
}

.btn-add-taken {
    background: #0d8af5;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.btn-add-taken:hover {
    background: #0b7ad6;
}

/* Table Container */
.table-container-taken {
    background: white;
    border-radius: 0;
    overflow: hidden;
    border: 1px solid #ddd;
    margin-bottom: 0;
}

/* Table */
.data-table-taken {
    width: 100%;
    border-collapse: collapse;
    border: none;
    table-layout: fixed;
}

.data-table-taken thead {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    color: white;
}

.data-table-taken th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 1.02rem;
    text-transform: capitalize;
    border-right: 2px solid rgba(255, 255, 255, 0.3);
    border-bottom: 2px solid #0b7ad6;
}

.data-table-taken th:last-child {
    border-right: none;
}

.data-table-taken th:nth-child(1) { width: 60px; text-align: center; }
.data-table-taken th:nth-child(2) { width: 200px; }
.data-table-taken th:nth-child(3) { width: 130px; }
.data-table-taken th:nth-child(4) { width: 120px; text-align: center; }
.data-table-taken th:nth-child(5) { width: 130px; text-align: center; }
.data-table-taken th:nth-child(6) { width: 130px; }
.data-table-taken th:nth-child(7) { width: 100px; text-align: center; }
.data-table-taken th:nth-child(8) { width: 180px; text-align: center; }

.data-table-taken tbody tr {
    border-bottom: 2px solid #e0e0e0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.data-table-taken tbody tr:hover {
    background: #e8eef5 !important;
    transform: scale(1.002);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.data-table-taken td {
    padding: 15px 20px;
    font-size: 0.96rem;
    color: #555;
    border-right: 2px solid #e0e0e0;
    background: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.data-table-taken td:last-child {
    border-right: none;
}

.data-table-taken td:first-child {
    text-align: center;
    font-weight: 600;
    color: #777;
    background: white;
}

.data-table-taken td:nth-child(4),
.data-table-taken td:nth-child(5),
.data-table-taken td:nth-child(7) {
    text-align: center;
}

.data-table-taken td:nth-child(8) {
    white-space: normal;
    text-align: center;
}

/* Truncate text */
.truncate-text-taken {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    max-width: 100%;
}

/* User Info */
.user-info-taken {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-name-taken {
    font-weight: 500;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-empty-taken {
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

.badge-progress {
    background: #fff4e6;
    color: #f39c12;
}

.badge-done {
    background: #e8f5e9;
    color: #27ae60;
}

.badge-pending {
    background: #e3f2fd;
    color: #2196f3;
}

/* Action Buttons */
.action-buttons-taken {
    display: flex;
    gap: 7px;
    justify-content: center;
}

.btn-action-taken {
    padding: 7px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    white-space: nowrap;
}

.btn-complete-taken {
    background: #e8f5e9;
    color: #27ae60;
}

.btn-complete-taken:hover {
    background: #27ae60;
    color: white;
}

.btn-pending-taken {
    background: #e3f2fd;
    color: #2196f3;
}

.btn-pending-taken:hover {
    background: #2196f3;
    color: white;
}

.btn-resume-taken {
    background: #fff9e6;
    color: #ff9800;
}

.btn-resume-taken:hover {
    background: #ff9800;
    color: white;
}

.btn-cancel-taken {
    background: #fff4e6;
    color: #f39c12;
}

.btn-cancel-taken:hover {
    background: #f39c12;
    color: white;
}

/* Pagination */
.pagination-taken {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 22px 0;
    gap: 7px;
    background: transparent;
}

.page-btn-taken {
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

.page-btn-taken:hover {
    border-color: #0d8af5;
    color: #0d8af5;
    background: #e3f2fd;
}

.page-btn-taken.active {
    background: #0d8af5;
    color: white;
    border-color: #0d8af5;
}

/* No Data */
.no-data-taken {
    text-align: center;
    padding: 50px 20px;
    color: #999;
    border: none !important;
}

.no-data-taken i {
    font-size: 2.8rem;
    margin-bottom: 12px;
    color: #ddd;
}

.no-data-taken h3 {
    font-size: 1.15rem;
    margin-bottom: 6px;
}

.no-data-taken p {
    font-size: 0.92rem;
}

/* Modal */
.modal-taken {
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

.modal-taken.show {
    display: flex;
}

.modal-content-taken {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header-taken {
    padding: 18px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-taken h3 {
    font-size: 1.2rem;
    color: #333;
}

.modal-close-taken {
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

.modal-close-taken:hover {
    background: #f5f5f5;
    color: #666;
}

.modal-body-taken {
    padding: 20px;
}

.form-group-taken {
    margin-bottom: 16px;
}

.form-group-taken label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #555;
    font-size: 0.9rem;
}

.form-group-taken input,
.form-group-taken select {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
}

.form-group-taken input[readonly] {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
}

.modal-footer-taken {
    padding: 14px 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.btn-secondary-taken {
    padding: 9px 18px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-secondary-taken:hover {
    background: #f5f5f5;
}

.btn-primary-taken {
    padding: 9px 18px;
    border: none;
    background: #0d8af5;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-primary-taken:hover {
    background: #0b7ad6;
}

/* Confirmation Modal */
.confirm-modal-taken .modal-content-taken {
    max-width: 400px;
    text-align: center;
}

.confirm-icon-taken {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
}

.confirm-icon-taken.complete {
    background: #27ae60;
}

.confirm-icon-taken.pending {
    background: #2196f3;
}

.confirm-icon-taken.resume {
    background: #ff9800;
}

.confirm-icon-taken.cancel {
    background: #f39c12;
}

.confirm-message-taken {
    font-size: 1rem;
    color: #555;
    margin-bottom: 8px;
}

.confirm-note-taken {
    font-size: 0.85rem;
    color: #999;
}

/* Responsive */
@media (max-width: 1200px) {
    .data-table-taken {
        font-size: 0.88rem;
    }
    
    .data-table-taken th,
    .data-table-taken td {
        padding: 12px 14px;
    }
}

@media (max-width: 768px) {
    .filters-container-taken {
        flex-direction: column;
    }
    
    .filter-select-taken,
    .search-input-taken,
    .btn-add-taken {
        width: 100%;
        margin-left: 0;
    }
    
    .table-container-taken {
        overflow-x: auto;
    }
    
    .data-table-taken {
        min-width: 1200px;
    }
}
</style>

<div class="container-taken">
    <!-- Alerts -->
    <?php if ($message): ?>
    <div class="alert-taken alert-success">
        <i class="fas fa-check-circle"></i>
        <?= $message ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-taken alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $error ?>
    </div>
    <?php endif; ?>
</div>

<!-- Page Header -->
<div class="page-header-taken">
    <h1 class="page-title-taken">Todo Saya - Daftar Tugas</h1>
</div>

<div class="container-taken">
    <!-- Main Content Container -->
    <div class="content-box-taken">
        <!-- Filters -->
        <div class="filters-container-taken">
            <input type="text" class="search-input-taken" id="searchInputTaken" placeholder="Cari todo..." 
                   value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearchTaken(event)">
            
            <select class="filter-select-taken" id="statusFilterTaken" onchange="applyFiltersTaken()">
                <option value="">Semua Status</option>
                <option value="in_progress" <?= $filter_status == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="done" <?= $filter_status == 'done' ? 'selected' : '' ?>>Completed</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>

            <select class="filter-select-taken" id="priorityFilterTaken" onchange="applyFiltersTaken()">
                <option value="">Semua Prioritas</option>
                <option value="high" <?= $filter_priority == 'high' ? 'selected' : '' ?>>High Priority</option>
                <option value="medium" <?= $filter_priority == 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                <option value="low" <?= $filter_priority == 'low' ? 'selected' : '' ?>>Low Priority</option>
            </select>
            
            <?php if ($filter_priority || $search || $filter_status): ?>
            <button class="btn-clear-taken" onclick="clearFiltersTaken()">
                <i class="fas fa-times"></i> Clear
            </button>
            <?php endif; ?>

            <button class="btn-add-taken" onclick="openAddTakenModal()">
                <i class="fas fa-plus"></i> Ambil Todo
            </button>
        </div>

        <!-- Table -->
        <div class="table-container-taken">
            <table class="data-table-taken">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul Todo</th>
                        <th>Aplikasi</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Dibuat Oleh</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($taken_result->num_rows > 0): ?>
                        <?php 
                        $no = $offset + 1;
                        while($taken = $taken_result->fetch_assoc()): 
                        ?>
                        <tr onclick="window.location.href='?page=detail_taken&id=<?= $taken['id'] ?>'">
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="truncate-text-taken" title="<?= htmlspecialchars($taken['todo_title']) ?>">
                                    <?= htmlspecialchars($taken['todo_title']) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="truncate-text-taken" title="<?= htmlspecialchars($taken['app_name']) ?>">
                                    <?= htmlspecialchars($taken['app_name']) ?>
                                </span>
                            </td>
                            <td><?= getPriorityBadgeTaken($taken['todo_priority']) ?></td>
                            <td><?= getStatusBadgeTaken($taken['status']) ?></td>
                            <td>
                                <?php if ($taken['todo_creator_name']): ?>
                                    <div class="user-info-taken">
                                        <span class="user-name-taken truncate-text-taken" title="<?= htmlspecialchars($taken['todo_creator_name']) ?>">
                                            <?= htmlspecialchars($taken['todo_creator_name']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="user-empty-taken">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($taken['date'])) ?></td>
                            <td onclick="event.stopPropagation()">
                                <?php if ($taken['status'] == 'done'): ?>
                                    <div style="text-align: center; color: #999; font-size: 1.2rem;">-</div>
                                <?php else: ?>
                                <div class="action-buttons-taken">
                                    <button class="btn-action-taken btn-complete-taken" 
                                            onclick="completeTaken(<?= $taken['id'] ?>, '<?= htmlspecialchars($taken['todo_title'], ENT_QUOTES) ?>')"
                                            title="Selesai">
                                        <i class="fas fa-check"></i> Selesai
                                    </button>
                                    
                                    <?php if ($taken['status'] == 'pending'): ?>
                                    <button class="btn-action-taken btn-resume-taken" 
                                            onclick="resumeTaken(<?= $taken['id'] ?>, '<?= htmlspecialchars($taken['todo_title'], ENT_QUOTES) ?>')"
                                            title="Lanjutkan">
                                        <i class="fas fa-play"></i> Lanjutkan
                                    </button>
                                    <?php else: ?>
                                    <button class="btn-action-taken btn-pending-taken" 
                                            onclick="pendingTaken(<?= $taken['id'] ?>, '<?= htmlspecialchars($taken['todo_title'], ENT_QUOTES) ?>')"
                                            title="Pending">
                                        <i class="fas fa-pause"></i> Pending
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn-action-taken btn-cancel-taken" 
                                            onclick="cancelTaken(<?= $taken['id'] ?>, '<?= htmlspecialchars($taken['todo_title'], ENT_QUOTES) ?>')"
                                            title="Cancel">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data-taken">
                                <i class="fas fa-inbox"></i>
                                <h3>Belum ada data</h3>
                                <p>Anda belum mengambil todo apapun</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_taken > $items_per_page): ?>
        <div class="pagination-taken">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=taken&status=<?= $filter_status ?>&priority=<?= $filter_priority ?>&search=<?= urlencode($search) ?>&pg=<?= $i ?>" 
                   class="page-btn-taken <?= $i == $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Taken Modal -->
<div id="takenModalAdd" class="modal-taken">
    <div class="modal-content-taken">
        <div class="modal-header-taken">
            <h3>Ambil Todo</h3>
            <button class="modal-close-taken" onclick="closeTakenModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body-taken">
            <form id="takenFormAdd" method="POST" action="?page=taken">
                <div class="form-group-taken">
                    <label for="takenTodoSelect">Pilih Todo *</label>
                    <select id="takenTodoSelect" name="id_todos" required>
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
                
                <div class="form-group-taken">
                    <label for="takenAddDate">Tanggal Diambil</label>
                    <input type="date" id="takenAddDate" name="date" 
                           value="<?= date('Y-m-d') ?>" readonly>
                </div>
            </form>
        </div>
        <div class="modal-footer-taken">
            <button type="button" class="btn-secondary-taken" onclick="closeTakenModal()">
                Batal
            </button>
            <button type="submit" form="takenFormAdd" name="add_taken" class="btn-primary-taken">
                <i class="fas fa-hand-paper"></i> Ambil Todo
            </button>
        </div>
    </div>
</div>

<!-- Complete Confirmation Modal -->
<div id="completeModalTaken" class="modal-taken confirm-modal-taken">
    <div class="modal-content-taken">
        <div class="modal-header-taken" style="flex-direction: column; align-items: center; text-align: center;">
            <div class="confirm-icon-taken complete">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Tandai Selesai</h3>
        </div>
        <div class="modal-body-taken">
            <p class="confirm-message-taken" id="completeMessageTaken">Apakah Anda yakin todo ini sudah selesai?</p>
            <p class="confirm-note-taken">Status akan diubah menjadi "Completed"</p>
        </div>
        <div class="modal-footer-taken">
            <button type="button" class="btn-secondary-taken" onclick="closeCompleteModal()">
                Batal
            </button>
            <form id="completeFormTaken" method="POST" action="?page=taken" style="display: inline;">
                <input type="hidden" id="completeTakenIdInput" name="taken_id">
                <button type="submit" name="complete_taken" class="btn-primary-taken" style="background: #27ae60;">
                    <i class="fas fa-check"></i> Selesai
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Pending Confirmation Modal -->
<div id="pendingModalTaken" class="modal-taken confirm-modal-taken">
    <div class="modal-content-taken">
        <div class="modal-header-taken" style="flex-direction: column; align-items: center; text-align: center;">
            <div class="confirm-icon-taken pending">
                <i class="fas fa-pause-circle"></i>
            </div>
            <h3>Tandai Pending</h3>
        </div>
        <div class="modal-body-taken">
            <p class="confirm-message-taken" id="pendingMessageTaken">Apakah Anda yakin ingin menandai todo ini sebagai pending?</p>
            <p class="confirm-note-taken">Status akan diubah menjadi "Pending"</p>
        </div>
        <div class="modal-footer-taken">
            <button type="button" class="btn-secondary-taken" onclick="closePendingModal()">
                Batal
            </button>
            <form id="pendingFormTaken" method="POST" action="?page=taken" style="display: inline;">
                <input type="hidden" id="pendingTakenIdInput" name="taken_id">
                <button type="submit" name="pending_taken" class="btn-primary-taken" style="background: #2196f3;">
                    <i class="fas fa-pause"></i> Pending
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Resume Confirmation Modal -->
<div id="resumeModalTaken" class="modal-taken confirm-modal-taken">
    <div class="modal-content-taken">
        <div class="modal-header-taken" style="flex-direction: column; align-items: center; text-align: center;">
            <div class="confirm-icon-taken resume">
                <i class="fas fa-play-circle"></i>
            </div>
            <h3>Lanjutkan Tugas</h3>
        </div>
        <div class="modal-body-taken">
            <p class="confirm-message-taken" id="resumeMessageTaken">Apakah Anda yakin ingin melanjutkan todo ini?</p>
            <p class="confirm-note-taken">Status akan diubah menjadi "In Progress"</p>
        </div>
        <div class="modal-footer-taken">
            <button type="button" class="btn-secondary-taken" onclick="closeResumeModal()">
                Batal
            </button>
            <form id="resumeFormTaken" method="POST" action="?page=taken" style="display: inline;">
                <input type="hidden" id="resumeTakenIdInput" name="taken_id">
                <button type="submit" name="resume_taken" class="btn-primary-taken" style="background: #ff9800;">
                    <i class="fas fa-play"></i> Lanjutkan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancelModalTaken" class="modal-taken confirm-modal-taken">
    <div class="modal-content-taken">
        <div class="modal-header-taken" style="flex-direction: column; align-items: center; text-align: center;">
            <div class="confirm-icon-taken cancel">
                <i class="fas fa-times-circle"></i>
            </div>
            <h3>Cancel Tugas</h3>
        </div>
        <div class="modal-body-taken">
            <p class="confirm-message-taken" id="cancelMessageTaken">Apakah Anda yakin ingin membatalkan tugas ini?</p>
            <p class="confirm-note-taken">Todo akan kembali tersedia untuk user lain</p>
        </div>
        <div class="modal-footer-taken">
            <button type="button" class="btn-secondary-taken" onclick="closeCancelModal()">
                Batal
            </button>
            <form id="cancelFormTaken" method="POST" action="?page=taken" style="display: inline;">
                <input type="hidden" id="cancelTakenIdInput" name="taken_id">
                <button type="submit" name="cancel_taken" class="btn-primary-taken" style="background: #f39c12;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openAddTakenModal() {
    document.getElementById('takenFormAdd').reset();
    document.getElementById('takenAddDate').value = '<?= date('Y-m-d') ?>';
    document.getElementById('takenModalAdd').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function completeTaken(id, title) {
    document.getElementById('completeMessageTaken').textContent = `Tandai "${title}" sebagai selesai?`;
    document.getElementById('completeTakenIdInput').value = id;
    document.getElementById('completeModalTaken').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function pendingTaken(id, title) {
    document.getElementById('pendingMessageTaken').textContent = `Tandai "${title}" sebagai pending?`;
    document.getElementById('pendingTakenIdInput').value = id;
    document.getElementById('pendingModalTaken').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function resumeTaken(id, title) {
    document.getElementById('resumeMessageTaken').textContent = `Lanjutkan tugas "${title}"?`;
    document.getElementById('resumeTakenIdInput').value = id;
    document.getElementById('resumeModalTaken').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function cancelTaken(id, title) {
    document.getElementById('cancelMessageTaken').textContent = `Cancel tugas "${title}"?`;
    document.getElementById('cancelTakenIdInput').value = id;
    document.getElementById('cancelModalTaken').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeTakenModal() {
    document.getElementById('takenModalAdd').classList.remove('show');
    document.body.style.overflow = '';
}

function closeCompleteModal() {
    document.getElementById('completeModalTaken').classList.remove('show');
    document.body.style.overflow = '';
}

function closePendingModal() {
    document.getElementById('pendingModalTaken').classList.remove('show');
    document.body.style.overflow = '';
}

function closeResumeModal() {
    document.getElementById('resumeModalTaken').classList.remove('show');
    document.body.style.overflow = '';
}

function closeCancelModal() {
    document.getElementById('cancelModalTaken').classList.remove('show');
    document.body.style.overflow = '';
}

function applyFiltersTaken() {
    const statusFilter = document.getElementById('statusFilterTaken').value;
    const priorityFilter = document.getElementById('priorityFilterTaken').value;
    const searchValue = document.getElementById('searchInputTaken').value;
    
    let url = new URL(window.location);
    url.searchParams.set('page', 'taken');
    url.searchParams.set('pg', '1');
    
    if (statusFilter) {
        url.searchParams.set('status', statusFilter);
    } else {
        url.searchParams.delete('status');
    }
    
    if (priorityFilter) {
        url.searchParams.set('priority', priorityFilter);
    } else {
        url.searchParams.delete('priority');
    }
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    window.location.href = url.toString();
}

function handleSearchTaken(event) {
    if (event.key === 'Enter') {
        applyFiltersTaken();
    }
}

function clearFiltersTaken() {
    let url = new URL(window.location);
    url.searchParams.delete('status');
    url.searchParams.delete('priority');
    url.searchParams.delete('search');
    url.searchParams.set('page', 'taken');
    url.searchParams.set('pg', '1');
    window.location.href = url.toString();
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal-taken')) {
        closeTakenModal();
        closeCompleteModal();
        closePendingModal();
        closeResumeModal();
        closeCancelModal();
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTakenModal();
        closeCompleteModal();
        closePendingModal();
        closeResumeModal();
        closeCancelModal();
    }
});

// Auto hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-taken');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>