<?php
// Handle CRUD Operations for Users
$message = '';
$error = '';

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// DELETE - Remove user
if (isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];
    
    if ($id == $_SESSION['user_id']) {
        $error = "Anda tidak dapat menghapus akun sendiri!";
    } else {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Pengguna berhasil dihapus!";
        } else {
            $error = "Gagal menghapus pengguna: " . $stmt->error;
        }
    }
}

// Handle filter
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// PAGINATION SETUP - 10 ITEMS PER PAGE
$items_per_page = 10;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total users count with filters
$count_query = "SELECT COUNT(*) as count FROM users $where_clause";
if (!empty($params)) {
    $count_stmt = $koneksi->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_assoc()['count'];
} else {
    $total_users = $koneksi->query($count_query)->fetch_assoc()['count'];
}

// Calculate total pages (maximum 10 pages)
$max_pages = 10;
$total_items = min($total_users, $max_pages * $items_per_page);
$total_pages = $total_users > 0 ? min(ceil($total_users / $items_per_page), $max_pages) : 1;

// Get users data with PAGINATION
$users_query = "
    SELECT * FROM users
    $where_clause
    ORDER BY name
    LIMIT $items_per_page OFFSET $offset
";

if (!empty($params)) {
    $stmt = $koneksi->prepare($users_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $users_result = $stmt->get_result();
} else {
    $users_result = $koneksi->query($users_query);
}

// Helper functions
function getRoleColor($role) {
    $colors = [
        'admin' => '#dc2626',
        'programmer' => '#2196f3',
        'support' => '#27ae60'
    ];
    return $colors[$role] ?? '#6b7280';
}

function getProfilePhoto($user) {
    if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
        return $user['profile_photo'] . '?v=' . time();
    }
    
    $gender = $user['gender'] ?? 'male';
    $role_color = getRoleColor($user['role']);
    $name = urlencode($user['name']);
    
    return "https://ui-avatars.com/api/?name={$name}&background=" . substr($role_color, 1) . "&color=fff&size=80";
}

function getGenderIcon($gender) {
    return $gender == 'female' ? 'fas fa-venus' : 'fas fa-mars';
}

function getGenderText($gender) {
    return $gender == 'female' ? 'Perempuan' : 'Laki-laki';
}

function getRoleDisplayName($role) {
    $names = [
        'admin' => 'Administrator',
        'programmer' => 'Programmer',
        'support' => 'Support'
    ];
    return $names[$role] ?? ucfirst($role);
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

.page-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
}

.btn-add-user {
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
    text-decoration: none;
}

.btn-add-user:hover {
    background: #0b7ad6;
}

/* Content Box */
.content-box {
    background: white;
    border-radius: 0;
    padding: 26px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Section Header */
.section-header {
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.section-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}

.section-count {
    background: #e3f2fd;
    color: #0d8af5;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Filters */
.filters-container {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    min-width: 270px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.search-box input {
    width: 100%;
    padding: 11px 16px 11px 36px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
}

.search-box input:focus {
    outline: none;
    border-color: #0d8af5;
}

.filter-dropdown select {
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    min-width: 170px;
    background: white;
    cursor: pointer;
}

.filter-dropdown select:focus {
    outline: none;
    border-color: #0d8af5;
}

.btn-clear-filter {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-clear-filter:hover {
    background: #c0392b;
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
.users-table {
    width: 100%;
    border-collapse: collapse;
    border: none;
    table-layout: fixed;
}

.users-table thead {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    color: white;
}

.users-table th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 1.02rem;
    text-transform: capitalize;
    border-right: 2px solid rgba(255, 255, 255, 0.3);
    border-bottom: 2px solid #0b7ad6;
}

.users-table th:last-child {
    border-right: none;
}

.users-table th:first-child {
    width: 70px;
    text-align: center;
}

.users-table th:nth-child(2) { /* Pengguna */
    width: 250px;
}

.users-table th:nth-child(3) { /* Email */
    width: 220px;
}

.users-table th:nth-child(4) { /* Telepon */
    width: 150px;
}

.users-table th:nth-child(5) { /* Role */
    width: 140px;
}

.users-table th:nth-child(6) { /* Gender */
    width: 130px;
}

.users-table th:last-child { /* Aksi */
    width: 100px;
    text-align: center;
}

.users-table tbody tr {
    border-bottom: 2px solid #e0e0e0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.users-table tbody tr:hover {
    background: #e8eef5 !important;
    transform: scale(1.005);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.users-table td {
    padding: 15px 20px;
    font-size: 0.96rem;
    color: #555;
    border-right: 2px solid #e0e0e0;
    background: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.users-table td:last-child {
    border-right: none;
}

.users-table td:first-child {
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

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.user-name {
    font-weight: 500;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Contact Info */
.contact-email {
    color: #555;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.contact-phone {
    color: #6b7280;
    font-size: 0.85rem;
}

/* Role Badge */
.role-badge {
    display: inline-block;
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 0.86rem;
    font-weight: 500;
    color: white;
}

.role-admin { background: #dc2626; }
.role-programmer { background: #2196f3; }
.role-support { background: #27ae60; }

/* Gender */
.gender-text {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #555;
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

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #0d8af5;
    color: white;
}

.btn-primary:hover {
    background: #0b7ad6;
}

.btn-secondary {
    padding: 9px 18px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
}

.btn-secondary:hover {
    background: #f5f5f5;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .section-header {
        flex-direction: column;
        align-items: stretch;
    }

    .filters-container {
        width: 100%;
        flex-direction: column;
    }

    .search-box {
        min-width: auto;
    }

    .filter-dropdown select {
        width: 100%;
    }

    .table-container {
        overflow-x: auto;
    }

    .users-table {
        min-width: 1000px;
    }
}
</style>

<!-- Alerts -->
<?php if ($message): ?>
<div class="container">
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="container">
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="header-content">
        <h1 class="page-title">Manajemen Pengguna</h1>
    </div>
</div>

<div class="container">
    <div class="content-box">
        <!-- Section Header -->
        <div class="section-header">
            <div class="section-title-wrapper">
                <h2 class="section-title">Daftar Pengguna</h2>
                <span class="section-count"><?= $total_users ?> pengguna</span>
            </div>
            
            <!-- Filters -->
            <div class="filters-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama atau email..." 
                           value="<?= htmlspecialchars($search) ?>" onkeyup="handleSearch(event)">
                </div>
                
                <div class="filter-dropdown">
                    <select id="roleFilter" onchange="applyFilters()">
                        <option value="">Semua Role</option>
                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="programmer" <?= $role_filter == 'programmer' ? 'selected' : '' ?>>Programmer</option>
                        <option value="support" <?= $role_filter == 'support' ? 'selected' : '' ?>>Support</option>
                    </select>
                </div>
                
                <?php if ($role_filter || $search): ?>
                <button class="btn-clear-filter" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
                <?php endif; ?>
                
                <a href="?page=tambah_users" class="btn-add-user">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Pengguna</span>
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pengguna</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Role</th>
                        <th>Gender</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php 
                        $no = $offset + 1;
                        while($user = $users_result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="user-info">
                                    <img src="<?= getProfilePhoto($user) ?>" 
                                         alt="<?= htmlspecialchars($user['name']) ?>"
                                         class="user-avatar"
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=<?= substr(getRoleColor($user['role']), 1) ?>&color=fff&size=80'">
                                    <strong class="user-name truncate-text" title="<?= htmlspecialchars($user['name']) ?>">
                                        <?= htmlspecialchars($user['name']) ?>
                                    </strong>
                                </div>
                            </td>
                            <td>
                                <span class="contact-email truncate-text" title="<?= htmlspecialchars($user['email']) ?>">
                                    <?= htmlspecialchars($user['email']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($user['phone'])): ?>
                                    <span class="contact-phone"><?= htmlspecialchars($user['phone']) ?></span>
                                <?php else: ?>
                                    <span class="contact-phone" style="color: #999; font-style: italic;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="role-badge role-<?= $user['role'] ?>">
                                    <?= getRoleDisplayName($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="gender-text">
                                    <i class="<?= getGenderIcon($user['gender'] ?? 'male') ?>"></i>
                                    <?= getGenderText($user['gender'] ?? 'male') ?>
                                </span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div class="action-buttons">
                                    <a href="?page=tambah_users&action=edit&id=<?= $user['id'] ?>" 
                                       class="btn-action btn-edit" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($_SESSION['user_role'] == 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn-action btn-delete" 
                                            onclick="deleteUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')" 
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <h3>Belum ada data</h3>
                                <p>Tidak ada pengguna yang sesuai dengan pencarian</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_users > $items_per_page): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=users&role=<?= $role_filter ?>&search=<?= urlencode($search) ?>&pg=<?= $i ?>" 
                   class="page-btn <?= $i == $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Hapus -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Konfirmasi Hapus</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 12px;">Apakah Anda yakin ingin menghapus pengguna:</p>
            <p style="font-weight: 600; color: #e74c3c;" id="deleteUserName"></p>
            <form id="deleteForm" method="POST" action="?page=users">
                <input type="hidden" id="deleteUserId" name="user_id">
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <button type="submit" form="deleteForm" name="delete_user" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </div>
    </div>
</div>

<script>
function deleteUser(id, name) {
    document.getElementById('deleteModal').classList.add('show');
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserId').value = id;
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
    const roleFilter = document.getElementById('roleFilter').value;
    const searchValue = document.getElementById('searchInput').value;
    
    let url = '?page=users';
    if (roleFilter) url += '&role=' + roleFilter;
    if (searchValue) url += '&search=' + encodeURIComponent(searchValue);
    url += '&pg=1';
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = '?page=users&pg=1';
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