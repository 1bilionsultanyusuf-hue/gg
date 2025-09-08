<?php
// Handle CRUD Operations for Users
$message = '';
$error = '';

// CREATE - Add new user
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $gender = trim($_POST['gender']); 
    
    if (!empty($name) && !empty($email) && !empty($role) && !empty($password) && !empty($gender)) {
        // Check if email already exists
        $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO users (name, email, role, password, gender) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $role, $password, $gender);
            
            if ($stmt->execute()) {
                $message = "Pengguna '$name' berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan pengguna!";
            }
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
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $gender = trim($_POST['gender']); 
    
    if (!empty($name) && !empty($email) && !empty($role) && !empty($gender)) {
        $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $id);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email sudah digunakan pengguna lain!";
        } else {
            if (!empty($password)) {
                $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ?, gender = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $role, $password, $gender, $id);
            } else {
                $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, role = ?, gender = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $role, $gender, $id);
            }
            
            if ($stmt->execute()) {
                $message = "Pengguna berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui pengguna!";
            }
        }
    } else {
        $error = "Nama, email, role, dan gender harus diisi!";
    }
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
            $error = "Gagal menghapus pengguna!";
        }
    }
}

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

// Helper functions
function getRoleColor($role) {
    $colors = ['admin'=>'#dc2626','programmer'=>'#0066ff','support'=>'#10b981'];
    return $colors[$role] ?? '#6b7280';
}

function getRoleIcon($role) {
    $icons = ['admin'=>'fas fa-crown','programmer'=>'fas fa-code','support'=>'fas fa-headset'];
    return $icons[$role] ?? 'fas fa-user';
}

function getProfilePhoto($user) {
    // Jika ada foto upload dari DB
    if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
        return $user['profile_photo'] . '?v=' . time();
    }
    
    // Default avatar berdasarkan gender dan role
    $gender = $user['gender'] ?? 'male';
    $role_color = getRoleColor($user['role']);
    $name = urlencode($user['name']);
    
    return "https://ui-avatars.com/api/?name={$name}&background=" . substr($role_color, 1) . "&color=fff&size=80";
}
?>

<div class="main-content" style="margin-top: 80px;">
    <!-- Alert Messages -->
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
            <h1 class="page-title">
                <i class="fas fa-users mr-3"></i> 
                Manajemen Pengguna
            </h1>
            <p class="page-subtitle">Kelola data pengguna dan hak akses sistem</p>
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

    <!-- Users Grid -->
    <div class="users-grid">
        <?php while($user = $users_result->fetch_assoc()): ?>
        <div class="user-card">
            <div class="user-card-header">
                <div class="user-avatar">
                    <img src="<?= getProfilePhoto($user) ?>" 
                         alt="<?= htmlspecialchars($user['name']) ?>"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=<?= substr(getRoleColor($user['role']), 1) ?>&color=fff&size=80'">
                </div>
                <div class="user-role-badge role-<?= $user['role'] ?>">
                    <i class="<?= getRoleIcon($user['role']) ?>"></i>
                    <?= ucfirst($user['role']) ?>
                </div>
                <div class="user-actions">
                    <button class="action-btn edit-btn" 
                            onclick="editUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>','<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>','<?= $user['role'] ?>','<?= $user['gender'] ?? 'male' ?>')" 
                            title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if($_SESSION['user_role'] == 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                    <button class="action-btn delete-btn" 
                            onclick="deleteUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')" 
                            title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="user-content">
                <h3 class="user-name"><?= htmlspecialchars($user['name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                <div class="user-gender">
                    <i class="fas fa-<?= ($user['gender'] ?? 'male') == 'male' ? 'mars' : 'venus' ?>"></i>
                    <span><?= ($user['gender'] ?? 'male') == 'male' ? 'Laki-laki' : 'Perempuan' ?></span>
                </div>
                
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
                    <span>Terakhir: <?= $user['last_activity'] ? date('d/m/Y', strtotime($user['last_activity'])) : 'Belum ada aktivitas' ?></span>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <!-- Add New User Card -->
        <div class="user-card add-new-card" onclick="openAddUserModal()">
            <div class="add-new-content">
                <div class="add-new-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Tambah Pengguna</h3>
                <p>Klik untuk menambahkan pengguna baru</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit User -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Pengguna Baru</h3>
            <button class="modal-close" onclick="closeUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="userForm" method="POST">
                <input type="hidden" id="userId" name="user_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="userGender">Jenis Kelamin *</label>
                        <select id="userGender" name="gender" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="male">👨 Laki-laki</option>
                            <option value="female">👩 Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="userName">Nama Lengkap *</label>
                        <input type="text" id="userName" name="name" required placeholder="Masukkan nama lengkap">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="userEmail">Email *</label>
                        <input type="email" id="userEmail" name="email" required placeholder="user@example.com">
                    </div>
                    <div class="form-group">
                        <label for="userRole">Role *</label>
                        <select id="userRole" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">👑 Administrator</option>
                            <option value="programmer">💻 Programmer</option>
                            <option value="support">🎧 Support</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="userPassword">Password <span id="passwordRequiredText">*</span></label>
                    <input type="password" id="userPassword" name="password" placeholder="Masukkan password">
                    <small id="passwordHelp" class="form-help" style="display:none;">Kosongkan jika tidak ingin mengubah password</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeUserModal()">
                Batal
            </button>
            <button type="submit" id="submitBtn" form="userForm" name="add_user" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div id="deleteModal" class="modal">
    <div class="modal-content delete-modal">
        <div class="modal-header">
            <div class="delete-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Konfirmasi Hapus</h3>
            <p id="deleteMessage">Apakah Anda yakin ingin menghapus pengguna ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <form id="deleteForm" method="POST" style="display:inline;">
                <input type="hidden" id="deleteUserId" name="user_id">
                <button type="submit" name="delete_user" class="btn btn-danger">
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

.bg-gradient-blue { background: linear-gradient(135deg, #0066ff, #33ccff); color: white; }
.bg-gradient-red { background: linear-gradient(135deg, #dc2626, #ef4444); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
.bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }

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

/* Users Grid */
.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.user-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #f1f5f9;
}

.user-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.user-card-header {
    padding: 20px 24px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 12px;
    border: 4px solid #f8fafc;
    position: relative;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.user-card:hover .user-avatar img {
    transform: scale(1.1);
}

.user-role-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
    margin-bottom: 8px;
}

.role-admin { background: linear-gradient(90deg, #dc2626, #ef4444); }
.role-programmer { background: linear-gradient(90deg, #0066ff, #33ccff); }
.role-support { background: linear-gradient(90deg, #10b981, #34d399); }

.user-actions {
    position: absolute;
    top: 16px;
    right: 16px;
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.user-card:hover .user-actions {
    opacity: 1;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.edit-btn {
    background: #dbeafe;
    color: #1d4ed8;
}

.edit-btn:hover {
    background: #bfdbfe;
    transform: scale(1.1);
}

.delete-btn {
    background: #fee2e2;
    color: #dc2626;
}

.delete-btn:hover {
    background: #fecaca;
    transform: scale(1.1);
}

.user-content {
    padding: 0 24px 20px;
    text-align: center;
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
    margin-bottom: 12px;
}

.user-gender {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #9ca3af;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

.user-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 16px;
    padding: 12px 0;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
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
    font-size: 0.75rem;
    color: #9ca3af;
}

.user-activity {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #9ca3af;
    font-size: 0.8rem;
}

/* Add New Card */
.add-new-card {
    border: 2px dashed #d1d5db;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.add-new-card:hover {
    border-color: #0066ff;
    background: #eff6ff;
}

.add-new-content {
    text-align: center;
    color: #6b7280;
}

.add-new-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;
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

.delete-modal {
    max-width: 400px;
    text-align: center;
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