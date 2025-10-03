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
        // Validasi tidak boleh ada spasi dalam email, password, dan username
        if (strpos($name, ' ') !== false) {
            $error = "Username tidak boleh mengandung spasi!";
        } elseif (strpos($email, ' ') !== false) {
            $error = "Email tidak boleh mengandung spasi!";
        } elseif (strpos($password, ' ') !== false) {
            $error = "Password tidak boleh mengandung spasi!";
        } else {
            // Check if email already exists
            $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email sudah terdaftar!";
            } else {
                // Hash password untuk keamanan
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $koneksi->prepare("INSERT INTO users (name, email, role, password, gender) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $role, $hashed_password, $gender);
                
                if ($stmt->execute()) {
                    $message = "Pengguna '$name' berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan pengguna: " . $stmt->error;
                }
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
        // Validasi tidak boleh ada spasi dalam email, password, dan username
        if (strpos($name, ' ') !== false) {
            $error = "Username tidak boleh mengandung spasi!";
        } elseif (strpos($email, ' ') !== false) {
            $error = "Email tidak boleh mengandung spasi!";
        } elseif (!empty($password) && strpos($password, ' ') !== false) {
            $error = "Password tidak boleh mengandung spasi!";
        } else {
            $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $email, $id);
            $check_email->execute();
            $result = $check_email->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email sudah digunakan pengguna lain!";
            } else {
                if (!empty($password)) {
                    // Hash password baru
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ?, gender = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $name, $email, $role, $hashed_password, $gender, $id);
                } else {
                    $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, role = ?, gender = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $role, $gender, $id);
                }
                
                if ($stmt->execute()) {
                    $message = "Pengguna berhasil diperbarui!";
                } else {
                    $error = "Gagal memperbarui pengguna: " . $stmt->error;
                }
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
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

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
    $where_clause
    GROUP BY u.id
    ORDER BY u.name
";

if (!empty($params)) {
    $stmt = $koneksi->prepare($users_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $users_result = $stmt->get_result();
} else {
    $users_result = $koneksi->query($users_query);
}

// Get user statistics
$admin_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$client_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'")->fetch_assoc()['count'];
$programmer_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'programmer'")->fetch_assoc()['count'];
$support_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'support'")->fetch_assoc()['count'];

// Helper functions
function getRoleColor($role) {
    $colors = [
        'admin' => '#dc2626',
        'client' => '#7c3aed',
        'programmer' => '#0066ff',
        'support' => '#10b981'
    ];
    return $colors[$role] ?? '#6b7280';
}

function getRoleIcon($role) {
    $icons = [
        'admin' => 'fas fa-crown',
        'client' => 'fas fa-briefcase',
        'programmer' => 'fas fa-code',
        'support' => 'fas fa-headset'
    ];
    return $icons[$role] ?? 'fas fa-user';
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
        'client' => 'Client',
        'programmer' => 'Programmer',
        'support' => 'Support'
    ];
    return $names[$role] ?? ucfirst($role);
}
?>

<div class="main-content">
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
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-red <?= $role_filter == 'admin' ? 'active' : '' ?>" onclick="filterByRole('admin')">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $admin_count ?></h3>
                <p class="stat-label">Administrator</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-purple <?= $role_filter == 'client' ? 'active' : '' ?>" onclick="filterByRole('client')">
            <div class="stat-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $client_count ?></h3>
                <p class="stat-label">Client</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green <?= $role_filter == 'programmer' ? 'active' : '' ?>" onclick="filterByRole('programmer')">
            <div class="stat-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $programmer_count ?></h3>
                <p class="stat-label">Programmer</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange <?= $role_filter == 'support' ? 'active' : '' ?>" onclick="filterByRole('support')">
            <div class="stat-icon">
                <i class="fas fa-headset"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $support_count ?></h3>
                <p class="stat-label">Support</p>
            </div>
        </div>
    </div>

    <!-- Users Container -->
    <div class="users-container">
        <div class="section-header">
            <div class="section-title-container">
                <h2 class="section-title">Daftar Pengguna</h2>
                <span class="section-count"><?= $users_result->num_rows ?> pengguna</span>
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
                        <option value="client" <?= $role_filter == 'client' ? 'selected' : '' ?>>Client</option>
                        <option value="programmer" <?= $role_filter == 'programmer' ? 'selected' : '' ?>>Programmer</option>
                        <option value="support" <?= $role_filter == 'support' ? 'selected' : '' ?>>Support</option>
                    </select>
                </div>
                
                <?php if ($role_filter || $search): ?>
                <button class="btn-clear-filter" onclick="clearFilters()" title="Hapus Filter">
                    <i class="fas fa-times"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add New User Button -->
        <div class="user-list-item add-new-item" onclick="openAddUserModal()">
            <div class="add-new-content">
                <div class="add-new-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="add-new-text">
                    <h3>Tambah Pengguna Baru</h3>
                    <p>Klik untuk menambahkan pengguna baru</p>
                </div>
            </div>
        </div>
        
        <!-- Users List -->
        <div class="users-list">
            <?php if ($users_result->num_rows > 0): ?>
                <?php while($user = $users_result->fetch_assoc()): ?>
                <div class="user-list-item" data-user-id="<?= $user['id'] ?>">
                    <div class="user-avatar-container">
                        <img src="<?= getProfilePhoto($user) ?>" 
                             alt="<?= htmlspecialchars($user['name']) ?>"
                             class="user-avatar-list"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=<?= substr(getRoleColor($user['role']), 1) ?>&color=fff&size=80'">
                        <div class="user-role-badge-small role-<?= $user['role'] ?>">
                            <i class="<?= getRoleIcon($user['role']) ?>"></i>
                        </div>
                    </div>
                    
                    <div class="user-list-content">
                        <div class="user-list-main">
                            <div class="user-name-section">
                                <h3 class="user-list-name"><?= htmlspecialchars($user['name']) ?></h3>
                                <span class="user-role-text role-badge-<?= $user['role'] ?>"><?= getRoleDisplayName($user['role']) ?></span>
                            </div>
                            <div class="user-details">
                                <span class="user-email">
                                    <i class="fas fa-envelope"></i>
                                    <?= htmlspecialchars($user['email']) ?>
                                </span>
                                <span class="user-gender">
                                    <i class="<?= getGenderIcon($user['gender'] ?? 'male') ?>"></i>
                                    <?= getGenderText($user['gender'] ?? 'male') ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="user-list-stats">
                            <div class="stat-badge total">
                                <span class="stat-number"><?= $user['total_todos'] ?></span>
                                <span class="stat-label">Total</span>
                            </div>
                            <div class="stat-badge active">
                                <span class="stat-number"><?= $user['active_todos'] ?></span>
                                <span class="stat-label">Aktif</span>
                            </div>
                            <div class="stat-badge completed">
                                <span class="stat-number"><?= $user['completed_todos'] ?></span>
                                <span class="stat-label">Selesai</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-list-progress">
                        <div class="progress-info">
                            <span class="progress-percentage">
                                <?= $user['total_todos'] > 0 ? round(($user['completed_todos'] / $user['total_todos']) * 100) : 0 ?>%
                            </span>
                        </div>
                        <div class="progress-bar-small">
                            <div class="progress-fill-small" 
                                 style="width: <?= $user['total_todos'] > 0 ? ($user['completed_todos'] / $user['total_todos']) * 100 : 0 ?>%">
                            </div>
                        </div>
                        <div class="last-activity">
                            <i class="fas fa-clock"></i>
                            <span><?= $user['last_activity'] ? date('d/m/Y', strtotime($user['last_activity'])) : 'Belum ada' ?></span>
                        </div>
                    </div>
                    
                    <div class="user-list-actions">
                        <button class="action-btn-small edit" 
                                onclick="editUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>','<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>','<?= $user['role'] ?>','<?= $user['gender'] ?? 'male' ?>')" 
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if($_SESSION['user_role'] == 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                        <button class="action-btn-small delete" 
                                onclick="deleteUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')" 
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3>Tidak ada pengguna ditemukan</h3>
                    <p>Tidak ada pengguna yang sesuai dengan filter yang diterapkan.</p>
                </div>
            <?php endif; ?>
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
                        <label for="userName">Username *</label>
                        <input type="text" id="userName" name="name" required placeholder="Masukkan username (tanpa spasi)" 
                               oninput="validateNoSpaces(this)">
                        <small class="form-help">Username tidak boleh mengandung spasi</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="userEmail">Email *</label>
                        <input type="email" id="userEmail" name="email" required placeholder="user@example.com" 
                               oninput="validateNoSpaces(this)">
                        <small class="form-help">Email tidak boleh mengandung spasi</small>
                    </div>
                    <div class="form-group">
                        <label for="userRole">Role *</label>
                        <select id="userRole" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">👑 Administrator</option>
                            <option value="client">💼 Client</option>
                            <option value="programmer">💻 Programmer</option>
                            <option value="support">🎧 Support</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="userPassword">Password <span id="passwordRequiredText">*</span></label>
                    <input type="password" id="userPassword" name="password" placeholder="Masukkan password (tanpa spasi)" 
                           oninput="validateNoSpaces(this)">
                    <small id="passwordHelp" class="form-help" style="display:none;">Kosongkan jika tidak ingin mengubah password</small>
                    <small class="form-help">Password tidak boleh mengandung spasi</small>
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
/* LAYOUT FIX - Dekat dengan Sidebar */
.main-content {
    margin-left: 0 !important;
    padding: 20px !important;
    max-width: 100% !important;
    width: 100% !important;
    box-sizing: border-box;
}

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
    transition: transform 0.3s ease;
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
.bg-gradient-purple { background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; }
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

/* Users Container */
.users-container {
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

/* Users List */
.users-list {
    max-height: 600px;
    overflow-y: auto;
}

.users-list::-webkit-scrollbar {
    width: 6px;
}

.users-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.users-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.users-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.user-list-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    cursor: pointer;
}

.user-list-item:hover {
    background: #f8fafc;
}

.user-list-item:last-child {
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

/* User Avatar Container */
.user-avatar-container {
    position: relative;
    margin-right: 16px;
    flex-shrink: 0;
}

.user-avatar-list {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f8fafc;
}

.user-role-badge-small {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    color: white;
    border: 2px solid white;
}

.role-admin { background: linear-gradient(90deg, #dc2626, #ef4444); }
.role-client { background: linear-gradient(90deg, #7c3aed, #a855f7); }
.role-programmer { background: linear-gradient(90deg, #0066ff, #33ccff); }
.role-support { background: linear-gradient(90deg, #10b981, #34d399); }

/* User List Content */
.user-list-content {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    min-width: 0;
}

.user-list-main {
    flex: 1;
    min-width: 0;
}

.user-name-section {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 6px;
}

.user-list-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.user-role-text {
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    text-transform: uppercase;
}

.role-badge-admin { background: #dc2626; }
.role-badge-client { background: #7c3aed; }
.role-badge-programmer { background: #0066ff; }
.role-badge-support { background: #10b981; }

.user-details {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #6b7280;
}

.user-email,
.user-gender {
    display: flex;
    align-items: center;
    gap: 6px;
}

.user-list-stats {
    display: flex;
    gap: 16px;
    margin-right: 20px;
}

.stat-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 45px;
}

.stat-badge .stat-number {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.stat-badge.total .stat-number { color: #3b82f6; }
.stat-badge.active .stat-number { color: #f59e0b; }
.stat-badge.completed .stat-number { color: #10b981; }

.stat-badge .stat-label {
    font-size: 0.7rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 500;
    margin-top: 2px;
}

.user-list-progress {
    width: 100px;
    margin-right: 20px;
    flex-shrink: 0;
}

.progress-info {
    text-align: center;
    margin-bottom: 6px;
}

.progress-percentage {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
}

.progress-bar-small {
    width: 100%;
    height: 4px;
    background: #f3f4f6;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-fill-small {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}

.last-activity {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 0.7rem;
    color: #9ca3af;
}

.user-list-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.user-list-item:hover .user-list-actions {
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

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.delete-modal {
    max-width: 400px;
    text-align: center;
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
    flex: 1;
}

.modal-close {
    background: #f3f4f6;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #e5e7eb;
    color: #374151;
}

.modal-body {
    padding: 20px 24px;
}

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Form Styles */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

.form-group input::placeholder {
    color: #9ca3af;
}

.form-help {
    display: block;
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 4px;
}

.form-group input.error {
    border-color: #dc2626 !important;
    background-color: #fee2e2 !important;
}

/* Delete Modal Specific */
.delete-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
}

.delete-modal .modal-header {
    flex-direction: column;
    text-align: center;
    padding: 24px;
}

.delete-modal .modal-header h3 {
    margin-bottom: 8px;
}

.delete-modal .modal-header p {
    color: #6b7280;
    margin: 0;
}

.delete-modal .modal-footer {
    padding: 0 24px 24px;
    justify-content: center;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 16px !important;
    }
    
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
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
    
    .user-list-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .user-list-stats {
        margin-right: 0;
        gap: 12px;
    }
    
    .user-list-progress {
        width: 100%;
        margin-right: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .modal-content {
        margin: 10px;
        max-width: none;
        width: calc(100% - 20px);
    }
    
    .user-list-actions {
        opacity: 1;
        position: static;
        justify-content: center;
        margin-top: 8px;
    }
    
    .users-list {
        max-height: none;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 12px !important;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .user-list-item {
        padding: 12px 16px;
    }
    
    .section-header {
        padding: 20px 16px 12px;
    }
    
    .add-new-item {
        margin: 12px 16px;
    }
    
    .add-new-content {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .user-details {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<script>
// Validate no spaces function
function validateNoSpaces(input) {
    if (input.value.includes(' ')) {
        input.setCustomValidity('Field ini tidak boleh mengandung spasi');
        input.style.borderColor = '#dc2626';
        input.style.backgroundColor = '#fee2e2';
    } else {
        input.setCustomValidity('');
        input.style.borderColor = '';
        input.style.backgroundColor = '';
    }
}

// JavaScript functions untuk modal dan CRUD operations
function openAddUserModal() {
    document.getElementById('userModal').classList.add('show');
    document.getElementById('modalTitle').textContent = 'Tambah Pengguna Baru';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('submitBtn').name = 'add_user';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('passwordRequiredText').style.display = 'inline';
    document.getElementById('passwordHelp').style.display = 'none';
    document.getElementById('userPassword').required = true;
    document.body.style.overflow = 'hidden';
    
    const inputs = document.querySelectorAll('#userForm input');
    inputs.forEach(input => {
        input.style.borderColor = '';
        input.style.backgroundColor = '';
        input.setCustomValidity('');
    });
}

function editUser(id, name, email, role, gender) {
    document.getElementById('userModal').classList.add('show');
    document.getElementById('modalTitle').textContent = 'Edit Pengguna';
    document.getElementById('userId').value = id;
    document.getElementById('userName').value = name;
    document.getElementById('userEmail').value = email;
    document.getElementById('userRole').value = role;
    document.getElementById('userGender').value = gender;
    document.getElementById('submitBtn').name = 'edit_user';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Perbarui';
    document.getElementById('passwordRequiredText').style.display = 'none';
    document.getElementById('passwordHelp').style.display = 'block';
    document.getElementById('userPassword').required = false;
    document.getElementById('userPassword').value = '';
    document.body.style.overflow = 'hidden';
    
    const inputs = document.querySelectorAll('#userForm input');
    inputs.forEach(input => {
        input.style.borderColor = '';
        input.style.backgroundColor = '';
        input.setCustomValidity('');
    });
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
    document.getElementById('userForm').reset();
    document.body.style.overflow = '';
    
    const inputs = document.querySelectorAll('#userForm input');
    inputs.forEach(input => {
        input.style.borderColor = '';
        input.style.backgroundColor = '';
        input.setCustomValidity('');
    });
}

function deleteUser(id, name) {
    document.getElementById('deleteModal').classList.add('show');
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus pengguna "${name}"?`;
    document.getElementById('deleteUserId').value = id;
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Filter functions
function filterByRole(role) {
    let url = new URL(window.location);
    url.searchParams.delete('search');
    
    if (url.searchParams.get('role') === role) {
        url.searchParams.delete('role');
    } else {
        url.searchParams.set('role', role);
    }
    
    window.location.href = url.toString();
}

function applyFilters() {
    const roleFilter = document.getElementById('roleFilter').value;
    const searchValue = document.getElementById('searchInput').value;
    
    let url = new URL(window.location);
    url.searchParams.delete('role');
    url.searchParams.delete('search');
    
    if (roleFilter) {
        url.searchParams.set('role', roleFilter);
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
    url.searchParams.delete('role');
    url.searchParams.delete('search');
    window.location.href = url.toString();
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const userModal = document.getElementById('userModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === userModal) {
        closeUserModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
});
</script>