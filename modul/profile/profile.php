<?php
// Get current user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $koneksi->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Initialize messages
$photo_success = '';
$photo_error = '';
$profile_success = '';
$errors = [];

// Handle photo upload
if (isset($_POST['upload_photo'])) {
    $upload_dir = 'uploads/profiles/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $file = $_FILES['profile_photo'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $photo_error = "Hanya file JPG, PNG, dan GIF yang diizinkan";
        }
        elseif ($file['size'] > $max_size) {
            $photo_error = "Ukuran file maksimal 5MB";
        }
        else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
                unlink($user_data['profile_photo']);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $update_photo = "UPDATE users SET profile_photo = ? WHERE id = ?";
                $stmt = $koneksi->prepare($update_photo);
                $stmt->bind_param("si", $upload_path, $user_id);
                
                if ($stmt->execute()) {
                    $photo_success = "Foto profil berhasil diperbarui!";
                    $user_data['profile_photo'] = $upload_path;
                } else {
                    $photo_error = "Gagal menyimpan foto ke database";
                    unlink($upload_path);
                }
            } else {
                $photo_error = "Gagal mengupload file";
            }
        }
    } else {
        $photo_error = "Tidak ada file yang dipilih atau terjadi error";
    }
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'] ?? 'male';
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    
    if (empty($name)) {
        $errors[] = "Nama tidak boleh kosong";
    } elseif (strpos($name, ' ') !== false) {
        $errors[] = "Username tidak boleh mengandung spasi";
    }
    
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } elseif (strpos($email, ' ') !== false) {
        $errors[] = "Email tidak boleh mengandung spasi";
    }
    
    $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $email_result = $check_email->get_result();
    
    if ($email_result->num_rows > 0) {
        $errors[] = "Email sudah digunakan pengguna lain";
    }
    
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Password lama harus diisi untuk mengubah password";
        } elseif ($current_password !== $user_data['password']) {
            $errors[] = "Password lama tidak benar";
        } elseif (strpos($new_password, ' ') !== false) {
            $errors[] = "Password baru tidak boleh mengandung spasi";
        }
    }
    
    if (empty($errors)) {
        if (!empty($new_password)) {
            $update_query = "UPDATE users SET name = ?, email = ?, gender = ?, password = ? WHERE id = ?";
            $stmt = $koneksi->prepare($update_query);
            $stmt->bind_param("ssssi", $name, $email, $gender, $new_password, $user_id);
        } else {
            $update_query = "UPDATE users SET name = ?, email = ?, gender = ? WHERE id = ?";
            $stmt = $koneksi->prepare($update_query);
            $stmt->bind_param("sssi", $name, $email, $gender, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $profile_success = "Profil berhasil diperbarui!";
            $user_data['name'] = $name;
            $user_data['email'] = $email;
            $user_data['gender'] = $gender;
            if (!empty($new_password)) {
                $user_data['password'] = $new_password;
            }
        } else {
            $errors[] = "Gagal memperbarui profil";
        }
    }
}

function getProfilePhotoUrlLocal($user_data) {
    if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
        return $user_data['profile_photo'] . '?v=' . time();
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=0066ff&color=fff&size=200";
}

function getRoleIcon($role) {
    $icons = [
        'admin' => '<i class="fas fa-crown"></i>',
        'manager' => '<i class="fas fa-user-tie"></i>',
        'programmer' => '<i class="fas fa-code"></i>',
        'support' => '<i class="fas fa-headset"></i>'
    ];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
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

/* Container - Match apps.php and users.php */
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
    animation: slideDown 0.3s ease;
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

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Page Header - Match apps.php and users.php */
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

/* Profile Layout */
.profile-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 24px;
}

/* Left Side */
.profile-left {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.profile-photo-card {
    background: white;
    border-radius: 0;
    padding: 24px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.profile-picture-wrapper {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto 20px;
    border-radius: 50%;
    border: 4px solid #0066ff;
    overflow: hidden;
}

.profile-picture-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.edit-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 102, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    cursor: pointer;
}

.profile-picture-wrapper:hover .edit-overlay {
    opacity: 1;
}

.edit-overlay i {
    font-size: 2rem;
    color: white;
}

.btn-change-photo {
    width: 100%;
    padding: 12px;
    background: #0066ff;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.btn-change-photo:hover {
    background: #0052cc;
    transform: translateY(-2px);
}

.profile-info-card {
    background: white;
    border-radius: 0;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.profile-info-card h3 {
    font-size: 1.3rem;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.user-id {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0 0 12px 0;
}

.role-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.role-admin { 
    background: #fee2e2;
    color: #dc2626;
}

.role-manager { 
    background: #ede9fe;
    color: #7c3aed;
}

.role-programmer { 
    background: #dbeafe;
    color: #1d4ed8;
}

.role-support { 
    background: #d1fae5;
    color: #059669;
}

.btn-edit-account {
    width: 100%;
    padding: 12px;
    background: #f97316;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.btn-edit-account:hover {
    background: #ea580c;
    transform: translateY(-2px);
}

/* Right Side - Match content-box style */
.profile-right {
    background: white;
    border-radius: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.info-header {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    color: white;
    padding: 16px 24px;
    font-weight: 600;
    font-size: 1.125rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-form {
    padding: 26px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 0.9rem;
    color: #555;
    font-weight: 500;
    margin-bottom: 6px;
}

.form-group input,
.form-group select {
    padding: 9px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    background: #f9fafb;
    transition: all 0.3s;
}

.form-group input:disabled,
.form-group select:disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.form-group input:not(:disabled):focus,
.form-group select:not(:disabled):focus {
    outline: none;
    border-color: #0d8af5;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

/* Password Section */
.password-section {
    margin-top: 24px;
    animation: slideDown 0.3s ease;
}

.section-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 24px 0;
}

.section-title {
    font-size: 1.1rem;
    color: #1f2937;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Form Actions */
.form-actions {
    margin-top: 24px;
    display: flex;
    justify-content: flex-end;
    animation: slideDown 0.3s ease;
}

.btn-save {
    padding: 10px 24px;
    background: #22d3ee;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.btn-save:hover {
    background: #06b6d4;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-overlay.show {
    display: flex;
}

.modal-box {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

.modal-header {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    font-size: 1.2rem;
    color: #1f2937;
    margin: 0;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.3rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 4px;
    transition: all 0.3s;
}

.btn-close:hover {
    color: #374151;
}

.modal-body {
    padding: 24px;
    text-align: center;
}

.photo-preview {
    margin-bottom: 16px;
}

.photo-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 50%;
    border: 3px solid #0066ff;
}

.file-info {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.confirm-text {
    color: #374151;
    margin: 16px 0 0 0;
}

.modal-footer {
    padding: 14px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-cancel,
.btn-confirm {
    padding: 9px 18px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.btn-cancel {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #ddd;
}

.btn-cancel:hover {
    background: #e5e7eb;
}

.btn-confirm {
    background: #0066ff;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-confirm:hover {
    background: #0052cc;
}

/* Loading Overlay */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: white;
}

.loading-overlay.show {
    display: flex;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: #0066ff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 1024px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 12px;
    }
    
    .page-header {
        padding: 8px 12px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .profile-picture-wrapper {
        width: 150px;
        height: 150px;
    }
    
    .info-form {
        padding: 20px;
    }
}
</style>

<!-- Alerts -->
<?php if (!empty($photo_success)): ?>
<div class="container">
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= $photo_success ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($photo_error)): ?>
<div class="container">
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $photo_error ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($profile_success)): ?>
<div class="container">
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= $profile_success ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="container">
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach($errors as $error): ?>
            <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="header-content">
        <h1 class="page-title">Profil Siswa</h1>
    </div>
</div>

<div class="container">
    <div class="profile-layout">
        <!-- Left Side - Profile Photo -->
        <div class="profile-left">
            <div class="profile-photo-card">
                <div class="profile-picture-wrapper">
                    <img id="profileImage" src="<?= getProfilePhotoUrlLocal($user_data) ?>" 
                         alt="<?= htmlspecialchars($user_data['name']) ?>"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_data['name']) ?>&background=0066ff&color=fff&size=200'">
                    <div class="edit-overlay" onclick="triggerPhotoUpload()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <button type="button" class="btn-change-photo" onclick="triggerPhotoUpload()">
                    <i class="fas fa-camera"></i> Ganti Foto
                </button>
            </div>
            
            <div class="profile-info-card">
                <h3><?= htmlspecialchars($user_data['name']) ?></h3>
                <p class="user-id"><?= htmlspecialchars($user_data['id']) ?></p>
                <div class="role-tag role-<?= $user_data['role'] ?>">
                    <?= getRoleIcon($user_data['role']) ?>
                    <?= ucfirst($user_data['role']) ?>
                </div>
            </div>
            
            <button type="button" class="btn-edit-account" onclick="togglePasswordForm()">
                <i class="fas fa-edit"></i> EDIT AKUN
            </button>
        </div>

        <!-- Right Side - Information -->
        <div class="profile-right">
            <div class="info-header">
                <i class="fas fa-info-circle"></i> Informasi Profil
            </div>
            
            <form method="POST" class="info-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user_data['name']) ?>" 
                               required pattern="[^\s]+" disabled id="input_name">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" 
                               required pattern="[^\s]+" disabled id="input_email">
                    </div>
                    
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="gender" disabled id="input_gender">
                            <option value="male" <?= ($user_data['gender'] ?? 'male') == 'male' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="female" <?= ($user_data['gender'] ?? 'male') == 'female' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?= ucfirst($user_data['role']) ?>" disabled>
                    </div>
                </div>
                
                <!-- Password Section (Hidden by default) -->
                <div id="passwordSection" class="password-section" style="display: none;">
                    <div class="section-divider"></div>
                    <h4 class="section-title">
                        <i class="fas fa-lock"></i> Ubah Password
                    </h4>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="current_password" id="input_current_password" 
                                   placeholder="Masukkan password lama">
                        </div>
                        
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" id="input_new_password" 
                                   pattern="[^\s]+" placeholder="Masukkan password baru">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Photo Upload Form -->
<form id="photoUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
    <input type="file" id="photoInput" name="profile_photo" accept="image/*" onchange="handleFileSelect()">
    <input type="hidden" name="upload_photo" value="1">
</form>

<!-- Photo Preview Modal -->
<div id="photoModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Preview Foto Profil</h3>
            <button type="button" class="btn-close" onclick="closePhotoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="photo-preview">
                <img id="previewImage" src="" alt="Preview">
            </div>
            <p id="fileInfo" class="file-info"></p>
            <p class="confirm-text">Gunakan foto ini sebagai foto profil?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closePhotoModal()">Batal</button>
            <button type="button" class="btn-confirm" onclick="confirmPhotoUpload()">
                <i class="fas fa-check"></i> Ya, Gunakan Foto Ini
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
    <p>Mengupload foto...</p>
</div>

<script>
let selectedFile = null;
let isEditMode = false;

function triggerPhotoUpload() {
    document.getElementById('photoInput').click();
}

function handleFileSelect() {
    const input = document.getElementById('photoInput');
    const file = input.files[0];
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB');
            input.value = '';
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Hanya file JPG, PNG, dan GIF yang diizinkan');
            input.value = '';
            return;
        }
        
        selectedFile = file;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            
            const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('fileInfo').textContent = `${file.name} (${fileSize})`;
            
            showPhotoModal();
        };
        reader.readAsDataURL(file);
    }
}

function confirmPhotoUpload() {
    if (selectedFile) {
        showLoading();
        document.getElementById('photoUploadForm').submit();
    }
}

function showPhotoModal() {
    const modal = document.getElementById('photoModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePhotoModal() {
    const modal = document.getElementById('photoModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
    selectedFile = null;
    document.getElementById('photoInput').value = '';
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

function togglePasswordForm() {
    isEditMode = !isEditMode;
    
    const passwordSection = document.getElementById('passwordSection');
    const formActions = document.getElementById('formActions');
    const nameInput = document.getElementById('input_name');
    const emailInput = document.getElementById('input_email');
    const genderInput = document.getElementById('input_gender');
    const currentPasswordInput = document.getElementById('input_current_password');
    const newPasswordInput = document.getElementById('input_new_password');
    const editBtn = document.querySelector('.btn-edit-account');
    
    if (isEditMode) {
        passwordSection.style.display = 'block';
        formActions.style.display = 'flex';
        
        nameInput.disabled = false;
        emailInput.disabled = false;
        genderInput.disabled = false;
        
        nameInput.style.background = 'white';
        emailInput.style.background = 'white';
        genderInput.style.background = 'white';
        
        editBtn.innerHTML = '<i class="fas fa-edit"></i> EDIT AKUN';
        editBtn.style.background = '#f97316';
    }
}

// Input validation
function validateInputs() {
    const emailInput = document.getElementById('input_email');
    const nameInput = document.getElementById('input_name');
    const passwordInput = document.getElementById('input_new_password');
    
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, '');
        });
        
        emailInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                this.value = this.value.replace(/\s/g, '');
            }, 10);
        });
    }
    
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, '');
        });
        
        nameInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                this.value = this.value.replace(/\s/g, '');
            }, 10);
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, '');
        });
        
        passwordInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                this.value = this.value.replace(/\s/g, '');
            }, 10);
        });
    }
}

// Auto-hide alerts
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 300);
        }, 5000);
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    autoHideAlerts();
    validateInputs();
    hideLoading();
    
    // Success animation for profile image
    <?php if (!empty($photo_success)): ?>
    const profileImage = document.getElementById('profileImage');
    if (profileImage) {
        profileImage.style.animation = 'successPulse 0.6s ease-in-out';
        setTimeout(() => {
            profileImage.style.animation = '';
        }, 600);
    }
    <?php endif; ?>
    
    // Close modal on outside click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closePhotoModal();
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
        if (isEditMode) {
            togglePasswordForm();
        }
    }
});

// Prevent form submission on Enter key (except in textarea)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit') {
        e.preventDefault();
    }
});
</script>