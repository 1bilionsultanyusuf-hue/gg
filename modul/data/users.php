<?php
// Get users data with todo statistics
$users_query = "
    SELECT u.*, 
           COUNT(t.id) as total_todos,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as active_todos,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed_todos,
           MAX(t.created_at) as last_activity
    FROM users u
    LEFT JOIN todos t ON u.id = t.user_id
    LEFT JOIN taken tk ON t.id = tk.id_todos AND tk.user_id = u.id
    GROUP BY u.id
    ORDER BY u.name
";
$users_result = $koneksi->query($users_query);

// Get user statistics
$total_users = $koneksi->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admin_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$programmer_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'programmer'")->fetch_assoc()['count'];
$support_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'support'")->fetch_assoc()['count'];

// Handle search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
?>

<div class="main-content" style="margin-top: 80px;">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-users mr-3"></i>
                Manajemen Pengguna
            </h1>
            <p class="page-subtitle">
                Kelola data pengguna dan hak akses sistem
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAddUserModal()">
                <i class="fas fa-user-plus mr-2"></i>
                Tambah Pengguna
            </button>
            <button class="btn btn-secondary" onclick="exportUsers()">
                <i class="fas fa-download mr-2"></i>
                Export Data
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_users ?></h3>
                <p class="stat-label">Total Pengguna</p>
                <span class="stat-change positive">
                    <i class="fas fa-user-check"></i> Registered
                </span>
            </div>
        </div>

        <div class="stat-card bg-gradient-red">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $admin_count ?></h3>
                <p class="stat-label">Administrator</p>
                <span class="stat-change neutral">
                    <i class="fas fa-crown"></i> Admin Role
                </span>
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $programmer_count ?></h3>
                <p class="stat-label">Programmer</p>
                <span class="stat-change positive">
                    <i class="fas fa-laptop-code"></i> Dev Team
                </span>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-headset"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $support_count ?></h3>
                <p class="stat-label">Support</p>
                <span class="stat-change positive">
                    <i class="fas fa-hands-helping"></i> Support Team
                </span>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari nama atau email pengguna..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="filter-controls">
            <select id="roleFilter" onchange="filterUsers()">
                <option value="">Semua Role</option>
                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Administrator</option>
                <option value="programmer" <?= $role_filter == 'programmer' ? 'selected' : '' ?>>Programmer</option>
                <option value="support" <?= $role_filter == 'support' ? 'selected' : '' ?>>Support</option>
            </select>
            <select id="sortBy" onchange="sortUsers()">
                <option value="name">Urutkan: Nama</option>
                <option value="role">Urutkan: Role</option>
                <option value="activity">Urutkan: Aktivitas</option>
            </select>
        </div>
    </div>

    <!-- Users Grid -->
    <div class="users-grid" id="usersGrid">
        <?php while($user = $users_result->fetch_assoc()): ?>
        <div class="user-card" data-role="<?= $user['role'] ?>" data-name="<?= strtolower($user['name']) ?>" data-email="<?= strtolower($user['email']) ?>">
            <div class="user-card-header">
                <div class="user-avatar">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=<?= getRoleColor($user['role']) ?>&color=fff&size=80" 
                         alt="<?= htmlspecialchars($user['name']) ?>"
                         onerror="this.src='https://ui-avatars.com/api/?name=User&background=0066ff&color=fff&size=80'">
                </div>
                <div class="user-role-badge role-<?= $user['role'] ?>">
                    <?= getRoleIcon($user['role']) ?>
                    <?= ucfirst($user['role']) ?>
                </div>
            </div>
            
            <div class="user-content">
                <h3 class="user-name"><?= htmlspecialchars($user['name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                
                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $user['total_todos'] ?></span>
                        <span class="stat-name">Total Tugas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value active"><?= $user['active_todos'] ?></span>
                        <span class="stat-name">Aktif</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value completed"><?= $user['completed_todos'] ?></span>
                        <span class="stat-name">Selesai</span>
                    </div>
                </div>
                
                <div class="user-activity">
                    <i class="fas fa-clock"></i>
                    <span>Aktivitas terakhir: 
                        <?= $user['last_activity'] ? date('d/m/Y', strtotime($user['last_activity'])) : 'Belum ada' ?>
                    </span>
                </div>
            </div>
            
            <div class="user-actions">
                <button class="action-btn primary" onclick="viewUser(<?= $user['id'] ?>)" title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="action-btn secondary" onclick="editUser(<?= $user['id'] ?>)" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn success" onclick="assignTask(<?= $user['id'] ?>)" title="Assign Tugas">
                    <i class="fas fa-tasks"></i>
                </button>
                <?php if($_SESSION['user_role'] == 'admin'): ?>
                <button class="action-btn danger" onclick="deleteUser(<?= $user['id'] ?>)" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php
// Helper functions
function getRoleColor($role) {
    $colors = [
        'admin' => 'dc2626',
        'programmer' => '0066ff',
        'support' => '10b981'
    ];
    return $colors[$role] ?? '6b7280';
}

function getRoleIcon($role) {
    $icons = [
        'admin' => '<i class="fas fa-crown"></i>',
        'programmer' => '<i class="fas fa-code"></i>',
        'support' => '<i class="fas fa-headset"></i>'
    ];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}
?>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tambah Pengguna Baru</h3>
            <button class="modal-close" onclick="closeAddUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addUserForm" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="userName">Nama Lengkap *</label>
                        <input type="text" id="userName" name="name" required 
                               placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email *</label>
                        <input type="email" id="userEmail" name="email" required 
                               placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="userRole">Role *</label>
                        <select id="userRole" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">Administrator</option>
                            <option value="programmer">Programmer</option>
                            <option value="support">Support</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="userPassword">Password *</label>
                        <input type="password" id="userPassword" name="password" required 
                               placeholder="Masukkan password">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">
                Batal
            </button>
            <button type="submit" form="addUserForm" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
        </div>
    </div>
</div>

<style>
/* Users Page Styles */
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

.header-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
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
    background: #f1f5f9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.bg-gradient-blue { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.bg-gradient-red { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); color: white; }
.bg-gradient-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); color: white; }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 8px;
}

.filter-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    display: flex;
    gap: 24px;
    align-items: center;
}

.search-box {
    position: relative;
    flex: 1;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.search-box input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
}

.search-box input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
}

.filter-controls {
    display: flex;
    gap: 12px;
}

.filter-controls select {
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}

.user-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.user-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.user-card-header {
    padding: 24px 24px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #f3f4f6;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-role-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
    display: flex;
    align-items: center;
    gap: 6px;
}

.user-role-badge i {
    font-size: 0.7rem;
}

.role-admin { background: linear-gradient(90deg, #dc2626, #ef4444); }
.role-programmer { background: linear-gradient(90deg, #0066ff, #33ccff); }
.role-support { background: linear-gradient(90deg, #10b981, #34d399); }

.user-content {
    padding: 20px 24px;
}

.user-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.user-email {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 16px;
}

.user-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-value.active {
    color: #f59e0b;
}

.stat-value.completed {
    color: #10b981;
}

.stat-name {
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 2px;
}

.user-activity {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #6b7280;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.user-actions {
    padding: 0 24px 24px;
    display: flex;
    justify-content: center;
    gap: 8px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.action-btn.primary {
    background: #0066ff;
    color: white;
}

.action-btn.primary:hover {
    background: #0044cc;
    transform: translateY(-2px);
}

.action-btn.secondary {
    background: #6b7280;
    color: white;
}

.action-btn.secondary:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.action-btn.success {
    background: #10b981;
    color: white;
}

.action-btn.success:hover {
    background: #059669;
    transform: translateY(-2px);
}

.action-btn.danger {
    background: #ef4444;
    color: white;
}

.action-btn.danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
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
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
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
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .header-actions {
        width: 100%;
        justify-content: center;
    }
    
    .filter-section {
        flex-direction: column;
        gap: 16px;
    }
    
    .filter-controls {
        width: 100%;
        flex-direction: column;
    }
    
    .users-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .user-stats {
        flex-direction: column;
        gap: 8px;
    }
    
    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .user-actions {
        flex-wrap: wrap;
    }
}
</style>

<script>
// Users Management JavaScript
let usersData = [];

// Initialize users data
document.addEventListener('DOMContentLoaded', function() {
    loadUsersData();
    setupSearchFilter();
});

function loadUsersData() {
    const userCards = document.querySelectorAll('.user-card');
    usersData = Array.from(userCards).map(card => {
        return {
            element: card,
            name: card.dataset.name,
            role: card.dataset.role,
            email: card.dataset.email
        };
    });
}

function setupSearchFilter() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        filterUsers();
    });
}

function filterUsers() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    
    usersData.forEach(user => {
        const matchesSearch = user.name.includes(searchTerm) || user.email.includes(searchTerm);
        const matchesRole = !roleFilter || user.role === roleFilter;
        
        if (matchesSearch && matchesRole) {
            user.element.style.display = 'block';
            user.element.style.animation = 'fadeIn 0.3s ease';
        } else {
            user.element.style.display = 'none';
        }
    });
    
    updateResultsCount();
}

function sortUsers() {
    const sortBy = document.getElementById('sortBy').value;
    const grid = document.getElementById('usersGrid');
    
    const sortedUsers = [...usersData].sort((a, b) => {
        switch(sortBy) {
            case 'name':
                return a.name.localeCompare(b.name);
            case 'role':
                return a.role.localeCompare(b.role);
            case 'activity':
                // This would need actual activity data
                return Math.random() - 0.5;
            default:
                return 0;
        }
    });
    
    // Reorder DOM elements
    sortedUsers.forEach(user => {
        grid.appendChild(user.element);
    });
    
    usersData = sortedUsers;
}

function updateResultsCount() {
    const visibleUsers = usersData.filter(user => user.element.style.display !== 'none').length;
    const totalUsers = usersData.length;
    
    // You could add a results counter here
    console.log(`Showing ${visibleUsers} of ${totalUsers} users`);
}

function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('addUserForm').reset();
}

function viewUser(userId) {
    alert('View user details functionality coming soon! User ID: ' + userId);
}

function editUser(userId) {
    alert('Edit user functionality coming soon! User ID: ' + userId);
}

function assignTask(userId) {
    alert('Assign task functionality coming soon! User ID: ' + userId);
}

function deleteUser(userId) {
    if(confirm('Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.')) {
        alert('Delete user functionality coming soon! User ID: ' + userId);
    }
}

function exportUsers() {
    alert('Export users functionality coming soon!');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeAddUserModal();
    }
});

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addUserForm');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(form);
            const userData = {
                name: formData.get('name'),
                email: formData.get('email'),
                role: formData.get('role'),
                password: formData.get('password')
            };
            
            // Validate form
            if(!userData.name || !userData.email || !userData.role || !userData.password) {
                alert('Semua field harus diisi!');
                return;
            }
            
            // Here you would normally send to server
            console.log('New user data:', userData);
            alert('User berhasil ditambahkan! (Demo mode)');
            closeAddUserModal();
        });
    }
});

// Add fade in animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);
</script>