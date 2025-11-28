<?php
// Handle CRUD Operations for Apps
$message = '';
$error = '';

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// DELETE - Remove app
if (isset($_POST['delete_app'])) {
    $id = $_POST['app_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM apps WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Aplikasi berhasil dihapus!";
    } else {
        $error = "Gagal menghapus aplikasi!";
    }
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with search
$where_clause = '';
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_clause = "WHERE a.name LIKE ? OR a.description LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types = 'ss';
}

// PAGINATION SETUP - 5 ITEMS PER PAGE
$items_per_page = 5;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total apps count with search
$count_query = "SELECT COUNT(*) as count FROM apps a $where_clause";
if (!empty($params)) {
    $count_stmt = $koneksi->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_apps = $count_stmt->get_result()->fetch_assoc()['count'];
} else {
    $total_apps = $koneksi->query($count_query)->fetch_assoc()['count'];
}

// Calculate total pages
$total_pages = $total_apps > 0 ? ceil($total_apps / $items_per_page) : 1;

// Get apps data with PAGINATION
$apps_query = "
    SELECT a.*, 
           COUNT(t.id) as total_todos,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as active_todos,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed_todos
    FROM apps a
    LEFT JOIN todos t ON a.id = t.app_id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    $where_clause
    GROUP BY a.id
    ORDER BY a.name
    LIMIT $items_per_page OFFSET $offset
";

if (!empty($params)) {
    $stmt = $koneksi->prepare($apps_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $apps_result = $stmt->get_result();
} else {
    $apps_result = $koneksi->query($apps_query);
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

.container {
    max-width: 100%;
    margin: 0;
    padding: 20px 30px;
    background: #f5f6fa;
}

/* Alert Messages */
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

.search-input {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    min-width: 270px;
}

.search-input:focus {
    outline: none;
    border-color: #0d8af5;
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

.btn-add-app {
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

.btn-add-app:hover {
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
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
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

.data-table th:nth-child(2) {
    width: 250px;
}

.data-table th:nth-child(3) {
    width: auto;
}

.data-table th:nth-child(4) {
    width: 120px;
    text-align: center;
}

.data-table th:last-child {
    width: 150px;
    text-align: center;
}

.data-table tbody tr {
    border-bottom: 2px solid #e0e0e0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.data-table tbody tr:hover {
    background: #e8eef5 !important;
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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

.data-table td:nth-child(4) {
    text-align: center;
}

.truncate-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    max-width: 100%;
}

.app-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.app-name {
    font-weight: 500;
    color: #333;
}

.todo-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0d8af5, #0b7ad6);
    color: white;
    border-radius: 50%;
    font-weight: 600;
    font-size: 1rem;
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

.btn-todo {
    background: #dcfce7;
    color: #10b981;
}

.btn-todo:hover {
    background: #10b981;
    color: white;
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

/* Responsive */
@media (max-width: 768px) {
    .filters-container {
        flex-direction: column;
    }
    
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
    <h1 class="page-title">Manajemen Aplikasi</h1>
</div>

<div class="container">
    <div class="content-box">
        <!-- Filters -->
        <div class="filters-container">
            <input type="text" class="search-input" id="searchInput" placeholder="Cari nama atau deskripsi..." 
                   value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
            
            <?php if ($search): ?>
            <button class="btn-clear" onclick="clearFilters()">
                <i class="fas fa-times"></i> Clear
            </button>
            <?php endif; ?>
            
            <a href="?page=tambah_apps&action=add" class="btn-add-app">
                <i class="fas fa-plus"></i>
                <span>Tambah Aplikasi</span>
            </a>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Aplikasi</th>
                        <th>Deskripsi</th>
                        <th>Total Todo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($apps_result && $apps_result->num_rows > 0): ?>
                        <?php 
                        $no = $offset + 1;
                        while($app = $apps_result->fetch_assoc()): 
                        ?>
                        <tr onclick="window.location.href='?page=detail_apps&id=<?= $app['id'] ?>'">
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="app-info">
                                    <strong class="app-name truncate-text" title="<?= htmlspecialchars($app['name']) ?>">
                                        <?= htmlspecialchars($app['name']) ?>
                                    </strong>
                                </div>
                            </td>
                            <td>
                                <span class="truncate-text" title="<?= htmlspecialchars($app['description']) ?>">
                                    <?= htmlspecialchars($app['description'] ?: '-') ?>
                                </span>
                            </td>
                            <td>
                                <span class="todo-count" title="Total Todos: <?= $app['total_todos'] ?>">
                                    <?= $app['total_todos'] ?>
                                </span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div class="action-buttons">
                                    <a href="?page=tambah_apps&action=edit&id=<?= $app['id'] ?>" 
                                       class="btn-action btn-edit" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn-action btn-delete" 
                                            onclick="deleteApp(<?= $app['id'] ?>, '<?= htmlspecialchars($app['name'], ENT_QUOTES) ?>')" 
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <h3>Belum ada data</h3>
                                <p>Tidak ada aplikasi yang sesuai dengan pencarian</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_apps > $items_per_page): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=apps&search=<?= urlencode($search) ?>&pg=<?= $i ?>" 
                   class="page-btn <?= $i == $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Konfirmasi Hapus</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 12px;">Apakah Anda yakin ingin menghapus aplikasi:</p>
            <p style="font-weight: 600; color: #e74c3c;" id="deleteAppName"></p>
            <p style="margin-top: 12px; color: #999; font-size: 0.85rem;">Semua data terkait akan ikut terhapus.</p>
            <form id="deleteForm" method="POST" action="?page=apps">
                <input type="hidden" id="deleteAppId" name="app_id">
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <button type="submit" form="deleteForm" name="delete_app" class="btn-primary btn-danger">
                <i class="fas fa-trash"></i>
                <span>Hapus</span>
            </button>
        </div>
    </div>
</div>

<script>
function deleteApp(id, name) {
    document.getElementById('deleteModal').classList.add('show');
    document.getElementById('deleteAppName').textContent = name;
    document.getElementById('deleteAppId').value = id;
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Search functionality
let searchTimeout;
function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    } else {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    
    let url = '?page=apps';
    if (search) url += '&search=' + encodeURIComponent(search);
    url += '&pg=1';
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = '?page=apps&pg=1';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target == deleteModal) {
        closeDeleteModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>