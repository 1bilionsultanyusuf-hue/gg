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

// ----------------------
// Helper functions
// ----------------------
function getRoleColor($role) {
    $colors = ['admin'=>'dc2626','programmer'=>'0066ff','support'=>'10b981'];
    return $colors[$role] ?? '6b7280';
}
function getRoleIcon($role) {
    $icons = ['admin'=>'<i class="fas fa-crown"></i>','programmer'=>'<i class="fas fa-code"></i>','support'=>'<i class="fas fa-headset"></i>'];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}
function getProfilePhoto($user) {
    // Jika ada foto upload dari DB
    if (!empty($user['profile_photo'])) {
        return $user['profile_photo'];
    }
    // Jika belum ada foto, pakai default gender
    if ($user['gender'] === 'male') {
        return "../../style/img/default_male.png";
    } elseif ($user['gender'] === 'female') {
        return "../../style/img/default_female.png";
    }
    // fallback ke UI avatars
    return "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=" . getRoleColor($user['role']) . "&color=fff&size=80";
}
?>

<div class="main-content" style="margin-top: 80px;">
    <?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title"><i class="fas fa-users mr-3"></i> Manajemen Pengguna</h1>
            <p class="page-subtitle">Kelola data pengguna dan hak akses sistem</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAddUserModal()">
                <i class="fas fa-user-plus mr-2"></i> Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><h3 class="stat-number"><?= $total_users ?></h3><p>Total Pengguna</p></div></div>
        <div class="stat-card bg-gradient-red"><div class="stat-icon"><i class="fas fa-user-shield"></i></div><div class="stat-content"><h3 class="stat-number"><?= $admin_count ?></h3><p>Administrator</p></div></div>
        <div class="stat-card bg-gradient-green"><div class="stat-icon"><i class="fas fa-code"></i></div><div class="stat-content"><h3 class="stat-number"><?= $programmer_count ?></h3><p>Programmer</p></div></div>
        <div class="stat-card bg-gradient-orange"><div class="stat-icon"><i class="fas fa-headset"></i></div><div class="stat-content"><h3 class="stat-number"><?= $support_count ?></h3><p>Support</p></div></div>
    </div>

    <!-- Users Grid -->
    <div class="users-grid">
        <?php while($user = $users_result->fetch_assoc()): ?>
        <div class="user-card">
            <div class="user-card-header">
                <div class="user-avatar">
                    <img src="<?= getProfilePhoto($user) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($user['name']) ?>">
                </div>
                <div class="user-role-badge role-<?= $user['role'] ?>"><?= getRoleIcon($user['role']) ?> <?= ucfirst($user['role']) ?></div>
                <div class="user-actions">
                    <button class="action-btn" onclick="editUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>','<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>','<?= $user['role'] ?>','<?= $user['gender'] ?>')" title="Edit"><i class="fas fa-edit"></i></button>
                    <?php if($_SESSION['user_role'] == 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                    <button class="action-btn danger" onclick="deleteUser(<?= $user['id'] ?>,'<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="user-content">
                <h3 class="user-name"><?= htmlspecialchars($user['name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                <p class="user-gender">Gender: <?= $user['gender'] == 'male' ? 'Laki-laki' : 'Perempuan' ?></p>
                <div class="user-stats">
                    <div class="stat-item"><span class="stat-value"><?= $user['total_todos'] ?></span><span>Total Tugas</span></div>
                    <div class="stat-item"><span class="stat-value active"><?= $user['active_todos'] ?></span><span>Aktif</span></div>
                    <div class="stat-item"><span class="stat-value completed"><?= $user['completed_todos'] ?></span><span>Selesai</span></div>
                </div>
                <div class="user-activity"><i class="fas fa-clock"></i><span>Aktivitas terakhir: <?= $user['last_activity'] ? date('d/m/Y', strtotime($user['last_activity'])) : 'Belum ada' ?></span></div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal Tambah/Edit User -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Tambah Pengguna Baru</h3><button class="modal-close" onclick="closeUserModal()"><i class="fas fa-times"></i></button></div>
        <div class="modal-body">
            <form id="userForm" method="POST">
                <input type="hidden" id="userId" name="user_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="userGender">Jenis Kelamin *</label>
                        <select id="userGender" name="gender" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="male">Laki-laki</option>
                            <option value="female">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="userName">Nama Lengkap *</label>
                        <input type="text" id="userName" name="name" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email *</label>
                        <input type="email" id="userEmail" name="email" required placeholder="user@example.com">
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
                        <label for="userPassword">Password <span id="passwordRequiredText">*</span></label>
                        <input type="password" id="userPassword" name="password" placeholder="Masukkan password">
                        <small id="passwordHelp" class="form-help">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeUserModal()">Batal</button><button type="submit" id="submitBtn" form="userForm" name="add_user" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Simpan</button></div>
    </div>
</div>

<!-- Modal Hapus -->
<div id="deleteModal" class="modal">
    <div class="modal-content delete-modal">
        <div class="modal-header"><div class="delete-icon"><i class="fas fa-trash-alt"></i></div><h3>Konfirmasi Hapus</h3><p id="deleteMessage">Apakah Anda yakin ingin menghapus pengguna ini?</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button><form id="deleteForm" method="POST" style="display:inline;"><input type="hidden" id="deleteUserId" name="user_id"><button type="submit" name="delete_user" class="btn btn-danger"><i class="fas fa-trash mr-2"></i>Hapus</button></form></div>
    </div>
</div>

<script>
let currentEditId = null;
function openAddUserModal(){document.getElementById('modalTitle').textContent='Tambah Pengguna Baru';document.getElementById('submitBtn').innerHTML='<i class="fas fa-save mr-2"></i>Simpan';document.getElementById('submitBtn').name='add_user';document.getElementById('userForm').reset();document.getElementById('userId').value='';document.getElementById('userPassword').required=true;document.getElementById('passwordRequiredText').style.display='inline';document.getElementById('passwordHelp').style.display='none';currentEditId=null;document.getElementById('userModal').classList.add('show');}
function editUser(id,name,email,role,gender){document.getElementById('modalTitle').textContent='Edit Pengguna';document.getElementById('submitBtn').innerHTML='<i class="fas fa-save mr-2"></i>Update';document.getElementById('submitBtn').name='edit_user';document.getElementById('userId').value=id;document.getElementById('userName').value=name;document.getElementById('userEmail').value=email;document.getElementById('userRole').value=role;document.getElementById('userGender').value=gender;document.getElementById('userPassword').value='';document.getElementById('userPassword').required=false;document.getElementById('passwordRequiredText').style.display='none';document.getElementById('passwordHelp').style.display='block';currentEditId=id;document.getElementById('userModal').classList.add('show');}
function deleteUser(id,name){document.getElementById('deleteMessage').textContent=`Apakah Anda yakin ingin menghapus pengguna "${name}"?`;document.getElementById('deleteUserId').value=id;document.getElementById('deleteModal').classList.add('show');}
function closeUserModal(){document.getElementById('userModal').classList.remove('show');}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('show');}
</script>
