<?php
// Handle CRUD Operations
$message = '';
$error = '';

// CREATE - Add new user
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    if (!empty($name) && !empty($email) && !empty($role) && !empty($password)) {
        // Check if email already exists
        $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows == 0) {
            $stmt = $koneksi->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $role, $password);
            
            if ($stmt->execute()) {
                $message = "User '$name' berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan user!";
            }
        } else {
            $error = "Email sudah terdaftar!";
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}

// UPDATE - Edit user
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    if (!empty($name) && !empty($email) && !empty($role)) {
        // Check if email already exists for other users
        $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $id);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows == 0) {
            if (!empty($password)) {
                $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $role, $password, $id);
            } else {
                $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $role, $id);
            }
            
            if ($stmt->execute()) {
                $message = "User berhasil diperbarui!";
                // Update session if editing own profile
                if ($id == $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = $role;
                }
            } else {
                $error = "Gagal memperbarui user!";
            }
        } else {
            $error = "Email sudah digunakan user lain!";
        }
    } else {
        $error = "Nama, email, dan role harus diisi!";
    }
}

// DELETE - Remove user
if (isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];
    
    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {
        $error = "Tidak dapat menghapus akun sendiri!";
    } else {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "User berhasil dihapus!";
        } else {
            $error = "Gagal menghapus user!";
        }
    }
}

// Get users data with statistics
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

// Get statistics
$total_users = $koneksi->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admin_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$programmer_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'programmer'")->fetch_assoc()['count'];
$support_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'support'")->fetch_assoc()['count'];
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
            </div>
        </div>

        <div class="stat-card bg-gradient-red">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $admin_count ?></h3>
                <p class="stat-label">Administrator</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $programmer_count ?></h3>
                <p class="stat-label">Programmer</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-headset"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $support_count ?></h3>
                <p class="stat-label">Support</p>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="filter-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari nama atau email..." onkeyup="filterUsers()">
        </div>
        <div class="filter-controls">
            <select id="roleFilter" onchange="filterUsers()">
                <option value="">Semua Role</option>
                <option value="admin">Administrator</option>
                <option value="programmer">Programmer</option>
                <option value="support">Support</option>
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
                         alt="<?= htmlspecialchars($user['name']) ?>">
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
                        <span class="stat-name">Total</span>
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
                    <span>Terakhir: <?= $user['last_activity'] ? date('d/m/Y', strtotime($user['last_activity'])) : 'Belum ada' ?></span>
                </div>
            </div>
            
            <div class="user-actions">
                <button class="action-btn primary" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>', '<?= $user['role'] ?>')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <?php if($_SESSION['user_role'] == 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                <button class="action-btn danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php
function getRoleColor($role) {
    $colors = ['admin' => 'dc2626', 'programmer' => '0066ff', 'support' => '10b981'];
    return $colors[$role] ?? '6b7280';
}

function getRoleIcon($role) {
    $icons = ['admin' => '<i class="fas fa-crown"></i>', 'programmer' => '<i class="fas fa-code"></i>', 'support' => '<i class="fas fa-headset"></i>'];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}
?>

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="userModalTitle">Tambah Pengguna Baru</h3>
            <button class="modal-close" onclick="closeUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="userForm" method="POST">
                <input type="hidden" id="userId" name="user_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="userName">Nama Lengkap *</label>
                        <input type="text" id="userName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email *</label>
                        <input type="email" id="userEmail" name="email" required>
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
                        <label for="userPassword">Password <span id="passwordRequired">*</span></label>
                        <input type="password" id="userPassword" name="password">
                        <small class="form-help" id="passwordHelp">Masukkan password</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Batal</button>
            <button type="submit" form="userForm" id="userSubmitBtn" name="add_user" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content delete-modal">
        <div class="modal-header">
            <div class="delete-icon">
                <i class="fas fa-user-times"></i>
            </div>
            <h3>Konfirmasi Hapus User</h3>
            <p id="deleteUserMessage">Apakah Anda yakin ingin menghapus user ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteUserModal()">Batal</button>
            <form id="deleteUserForm" method="POST" style="display: inline;">
                <input type="hidden" id="deleteUserId" name="user_id">
                <button type="submit" name="delete_user" class="btn btn-danger">
                    <i class="fas fa-trash mr-2"></i>Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* User Card Styling */
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

.stat-value.active { color: #f59e0b; }
.stat-value.completed { color: #10b981; }

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
}

.action-btn.primary {
    background: #0066ff;
    color: white;
}

.action-btn.danger {
    background: #ef4444;
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
}

/* Modal Styles */
.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
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

.delete-modal .modal-header {
    flex-direction: column;
    text-align: center;
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
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
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

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
let currentEditUserId = null;

function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Tambah Pengguna Baru';
    document.getElementById('userSubmitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('userSubmitBtn').name = 'add_user';
    document.getElementById('passwordRequired').textContent = '*';
    document.getElementById('passwordHelp').textContent = 'Masukkan password';
    document.getElementById('userPassword').required = true;
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    currentEditUserId = null;
    document.getElementById('userModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editUser(id, name, email, role) {
    document.getElementById('userModalTitle').textContent = 'Edit Pengguna';
    document.getElementById('userSubmitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('userSubmitBtn').name = 'edit_user';
    document.getElementById('passwordRequired').textContent = '';
    document.getElementById('passwordHelp').textContent = 'Kosongkan jika tidak ingin mengubah password';
    document.getElementById('userPassword').required = false;
    
    document.getElementById('userId').value = id;
    document.getElementById('userName').value = name;
    document.getElementById('userEmail').value = email;
    document.getElementById('userRole').value = role;
    document.getElementById('userPassword').value = '';
    
    currentEditUserId = id;
    document.getElementById('userModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteUser(id, name) {
    document.getElementById('deleteUserMessage').textContent = `Apakah Anda yakin ingin menghapus user "${name}"? Semua data terkait akan ikut terhapus.`;
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
    document.body.style.overflow = '';
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.remove('show');
    document.body.style.overflow = '';
}

function filterUsers() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const userCards = document.querySelectorAll('.user-card');
    
    userCards.forEach(card => {
        const name = card.dataset.name;
        const email = card.dataset.email;
        const role = card.dataset.role;
        
        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchesRole = !roleFilter || role === roleFilter;
        
        if (matchesSearch && matchesRole) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeUserModal();
        closeDeleteUserModal();
    }
});

// Auto-hide alerts
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