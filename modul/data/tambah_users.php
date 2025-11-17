<?php
// File: tambah_users.php
$message = '';
$error = '';
$edit_mode = false;
$edit_user_data = null;

// Check if edit mode
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['id']);
    $edit_query = $koneksi->prepare("SELECT * FROM users WHERE id = ?");
    $edit_query->bind_param("i", $edit_id);
    $edit_query->execute();
    $edit_user_data = $edit_query->get_result()->fetch_assoc();
    
    if (!$edit_user_data) {
        echo "<script>window.location.href = '?page=users';</script>";
        exit;
    }
}

// CREATE - Add new user
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_input = trim($_POST['phone']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $gender = trim($_POST['gender']);
    
    $phone = str_replace(' ', '', $phone_input);
    
    if (!empty($name) && !empty($email) && !empty($role) && !empty($password) && !empty($gender)) {
        if (strpos($name, ' ') !== false) {
            $error = "Username tidak boleh mengandung spasi!";
        } elseif (strpos($email, ' ') !== false) {
            $error = "Email tidak boleh mengandung spasi!";
        } elseif (strpos($password, ' ') !== false) {
            $error = "Password tidak boleh mengandung spasi!";
        } else {
            $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email sudah terdaftar!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $koneksi->prepare("INSERT INTO users (name, email, phone, role, password, gender) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $email, $phone, $role, $hashed_password, $gender);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Pengguna '$name' berhasil ditambahkan!";
                    $message = "Data berhasil disimpan!";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '?page=users';
                        }, 1500);
                    </script>";
                } else {
                    $error = "Gagal menambahkan pengguna: " . $stmt->error;
                }
            }
        }
    } else {
        $error = "Nama, email, role, password, dan gender harus diisi!";
    }
}

// UPDATE - Edit user
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_input = trim($_POST['phone']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $gender = trim($_POST['gender']);
    
    $phone = str_replace(' ', '', $phone_input);
    
    if (!empty($name) && !empty($email) && !empty($role) && !empty($gender)) {
        if (strpos($name, ' ') !== false) {
            $error = "Username tidak boleh mengandung spasi!";
            $edit_mode = true;
        } elseif (strpos($email, ' ') !== false) {
            $error = "Email tidak boleh mengandung spasi!";
            $edit_mode = true;
        } elseif (!empty($password) && strpos($password, ' ') !== false) {
            $error = "Password tidak boleh mengandung spasi!";
            $edit_mode = true;
        } else {
            $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $email, $id);
            $check_email->execute();
            $result = $check_email->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email sudah digunakan pengguna lain!";
                $edit_mode = true;
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ?, gender = ? WHERE id = ?");
                    $stmt->bind_param("ssssssi", $name, $email, $phone, $role, $hashed_password, $gender, $id);
                } else {
                    $stmt = $koneksi->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, gender = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $name, $email, $phone, $role, $gender, $id);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Pengguna berhasil diperbarui!";
                    $message = "Data berhasil diperbarui!";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '?page=users';
                        }, 1500);
                    </script>";
                } else {
                    $error = "Gagal memperbarui pengguna: " . $stmt->error;
                    $edit_mode = true;
                }
            }
        }
    } else {
        $error = "Nama, email, role, dan gender harus diisi!";
        $edit_mode = true;
    }
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
    margin-bottom: 4px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.page-subtitle a {
    color: #0d8af5;
    text-decoration: none;
    transition: all 0.2s;
}

.page-subtitle a:hover {
    color: #0b7ad6;
    text-decoration: underline;
}

/* Form Box */
.form-box {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 900px;
    margin: 0 auto;
}

.form-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.form-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
}

/* Form */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 0;
}

.form-group-full {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
    font-size: 0.95rem;
}

.form-group label span {
    color: #ef4444;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0d8af5;
    box-shadow: 0 0 0 3px rgba(13, 138, 245, 0.1);
}

.form-group input.error {
    border-color: #ef4444;
    background: #fef2f2;
}

.form-help {
    display: block;
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 5px;
}

.password-toggle {
    position: relative;
}

.password-toggle input {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    font-size: 1.1rem;
}

.toggle-password:hover {
    color: #6b7280;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #e5e7eb;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    border: none;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 138, 245, 0.3);
}

.btn-secondary {
    background: white;
    color: #6b7280;
    border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

/* Info Box */
.info-box {
    background: #f0f9ff;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: start;
    gap: 10px;
}

.info-box i {
    color: #0d8af5;
    font-size: 1.1rem;
    margin-top: 2px;
}

.info-box p {
    color: #1e40af;
    font-size: 0.88rem;
    line-height: 1.5;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .form-box {
        padding: 20px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
        margin-bottom: 0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Alerts -->
<?php if ($message): ?>
<div class="container">
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
        <span style="margin-left: auto; font-size: 0.85rem;">Redirecting...</span>
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
        <h1 class="page-title"><?= $edit_mode ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></h1>
    </div>
</div>

<div class="container">
    <div class="form-box">
        <!-- Form Header -->
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-<?= $edit_mode ? 'user-edit' : 'user-plus' ?>"></i>
            </div>
            <h2 class="form-title">
                <?= $edit_mode ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' ?>
            </h2>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>
                <strong>Catatan:</strong> Field yang ditandai dengan <span style="color: #ef4444;">*</span> wajib diisi. 
                <?= $edit_mode ? 'Kosongkan password jika tidak ingin mengubahnya.' : 'Pastikan data yang Anda masukkan sudah benar.' ?>
            </p>
        </div>
        
        <!-- Form -->
        <form method="POST" action="">
            <?php if ($edit_mode && $edit_user_data): ?>
                <input type="hidden" name="user_id" value="<?= $edit_user_data['id'] ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="userName">Username <span>*</span></label>
                    <input type="text" 
                           id="userName" 
                           name="name" 
                           required 
                           placeholder="Masukkan username" 
                           value="<?= $edit_mode && $edit_user_data ? htmlspecialchars($edit_user_data['name']) : '' ?>"
                           oninput="validateNoSpaces(this)">
                    <small class="form-help">Username tidak boleh mengandung spasi</small>
                </div>
                
                <div class="form-group">
                    <label for="userEmail">Email <span>*</span></label>
                    <input type="email" 
                           id="userEmail" 
                           name="email" 
                           required 
                           placeholder="contoh@email.com" 
                           value="<?= $edit_mode && $edit_user_data ? htmlspecialchars($edit_user_data['email']) : '' ?>"
                           oninput="validateNoSpaces(this)">
                    <small class="form-help">Email tidak boleh mengandung spasi</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="userPhone">Nomor Telepon</label>
                    <input type="tel" 
                           id="userPhone" 
                           name="phone" 
                           placeholder="08xxxxxxxxxx" 
                           maxlength="20"
                           value="<?= $edit_mode && $edit_user_data ? htmlspecialchars($edit_user_data['phone']) : '' ?>">
                    <small class="form-help">Opsional - Format bebas</small>
                </div>
                
                <div class="form-group">
                    <label for="userGender">Jenis Kelamin <span>*</span></label>
                    <select id="userGender" name="gender" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="male" <?= ($edit_mode && $edit_user_data && $edit_user_data['gender'] == 'male') ? 'selected' : '' ?>>
                            <i class="fas fa-mars"></i> Laki-laki
                        </option>
                        <option value="female" <?= ($edit_mode && $edit_user_data && $edit_user_data['gender'] == 'female') ? 'selected' : '' ?>>
                            <i class="fas fa-venus"></i> Perempuan
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="userRole">Role <span>*</span></label>
                    <select id="userRole" name="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin" <?= ($edit_mode && $edit_user_data && $edit_user_data['role'] == 'admin') ? 'selected' : '' ?>>
                            Administrator
                        </option>
                        <option value="programmer" <?= ($edit_mode && $edit_user_data && $edit_user_data['role'] == 'programmer') ? 'selected' : '' ?>>
                            Programmer
                        </option>
                        <option value="support" <?= ($edit_mode && $edit_user_data && $edit_user_data['role'] == 'support') ? 'selected' : '' ?>>
                            Support
                        </option>
                    </select>
                    <small class="form-help">Tentukan hak akses pengguna</small>
                </div>
                
                <div class="form-group">
                    <label for="userPassword">
                        Password 
                        <?= $edit_mode ? '' : '<span>*</span>' ?>
                    </label>
                    <div class="password-toggle">
                        <input type="password" 
                               id="userPassword" 
                               name="password" 
                               placeholder="Masukkan password" 
                               <?= $edit_mode ? '' : 'required' ?>
                               oninput="validateNoSpaces(this)">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <small class="form-help">
                        <?= $edit_mode ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 6 karakter, tanpa spasi' ?>
                    </small>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <a href="?page=users" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </a>
                <button type="submit" name="<?= $edit_mode ? 'edit_user' : 'add_user' ?>" class="btn btn-primary">
                    <i class="fas fa-<?= $edit_mode ? 'check' : 'save' ?>"></i>
                    <?= $edit_mode ? 'Perbarui Data' : 'Simpan Data' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function validateNoSpaces(input) {
    if (input.value.includes(' ')) {
        input.setCustomValidity('Field ini tidak boleh mengandung spasi');
        input.classList.add('error');
    } else {
        input.setCustomValidity('');
        input.classList.remove('error');
    }
}

function togglePassword() {
    const passwordInput = document.getElementById('userPassword');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form validation before submit
document.querySelector('form').addEventListener('submit', function(e) {
    const inputs = this.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Mohon lengkapi semua field yang wajib diisi!');
    }
});
</script>