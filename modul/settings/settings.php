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
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $file = $_FILES['profile_photo'];
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $photo_error = "Hanya file JPG, PNG, dan GIF yang diizinkan";
        }
        // Validate file size
        elseif ($file['size'] > $max_size) {
            $photo_error = "Ukuran file maksimal 5MB";
        }
        else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Delete old photo if exists
            if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
                unlink($user_data['profile_photo']);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $update_photo = "UPDATE users SET profile_photo = ? WHERE id = ?";
                $stmt = $koneksi->prepare($update_photo);
                $stmt->bind_param("si", $upload_path, $user_id);
                
                if ($stmt->execute()) {
                    $photo_success = "Foto profil berhasil diperbarui!";
                    $user_data['profile_photo'] = $upload_path;
                } else {
                    $photo_error = "Gagal menyimpan foto ke database";
                    unlink($upload_path); // Delete uploaded file
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
    
    // Validation
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
    
    // Check if email already exists for other users
    $check_email = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $email_result = $check_email->get_result();
    
    if ($email_result->num_rows > 0) {
        $errors[] = "Email sudah digunakan pengguna lain";
    }
    
    // Check if current password is correct (only if changing password)
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
        // Update query
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
            // Refresh user data
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

function getRoleIcon($role) {
    $icons = [
        'admin' => '<i class="fas fa-crown"></i>',
        'manager' => '<i class="fas fa-user-tie"></i>',
        'programmer' => '<i class="fas fa-code"></i>',
        'support' => '<i class="fas fa-headset"></i>'
    ];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}

// Get profile photo URL - Local function untuk profile page
function getProfilePhotoUrlLocal($user_data) {
    if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
        return $user_data['profile_photo'] . '?v=' . time();
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=0066ff&color=fff&size=150";
}
?>

<div class="main-content">
    <div class="profile-container">
        


        <!-- Photo Upload Messages -->
        <?php if (!empty($photo_success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $photo_success ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($photo_error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $photo_error ?>
        </div>
        <?php endif; ?>

        <!-- Profile Photo Section -->
        <div class="profile-card">
            <div class="card-header">
                <h3>Foto Profil</h3>
                <p>Ubah foto profil Anda</p>
            </div>
            <div class="card-content text-center">
                <div class="profile-photo-section">
                    <div class="profile-avatar-large">
                        <img id="profileImage" src="<?= getProfilePhotoUrlLocal($user_data) ?>" 
                             alt="<?= htmlspecialchars($user_data['name']) ?>"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_data['name']) ?>&background=0066ff&color=fff&size=150'">
                        <div class="avatar-edit-btn" onclick="triggerPhotoUpload()">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <div class="photo-info">
                        <h4><?= htmlspecialchars($user_data['name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($user_data['email']) ?></p>
                        <div class="role-badge role-<?= $user_data['role'] ?>">
                            <?= getRoleIcon($user_data['role']) ?>
                            <?= ucfirst($user_data['role']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="profile-card">
            <div class="card-header">
                <h3>Informasi Profil</h3>
                <p>Perbarui informasi pribadi Anda</p>
            </div>
            <div class="card-content">
                <?php if (!empty($profile_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $profile_success ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach($errors as $error): ?>
                        <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Username</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($user_data['name']) ?>" 
                                   required pattern="[^\s]+"
                                   title="Username tidak boleh mengandung spasi">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($user_data['email']) ?>" 
                                   required pattern="[^\s]+"
                                   title="Email tidak boleh mengandung spasi">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Jenis Kelamin</label>
                            <select id="gender" name="gender">
                                <option value="male" <?= ($user_data['gender'] ?? 'male') == 'male' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="female" <?= ($user_data['gender'] ?? 'male') == 'female' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <input type="text" value="<?= ucfirst($user_data['role']) ?>" disabled class="form-disabled">
                            <small class="form-help">Role tidak dapat diubah</small>
                        </div>
                    </div>
                    
                    <hr class="form-divider">
                    
                    <h4 class="form-section-title">Ubah Password (Opsional)</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_password">Password Lama</label>
                            <input type="password" id="current_password" name="current_password" 
                                   placeholder="Masukkan password lama jika ingin mengubah">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" 
                                   pattern="[^\s]+"
                                   title="Password tidak boleh mengandung spasi"
                                   placeholder="Masukkan password baru">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Perubahan
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo mr-2"></i>
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Hidden Photo Upload Form -->
<form id="photoUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
    <input type="file" id="photoInput" name="profile_photo" accept="image/*" onchange="handleFileSelect()">
    <input type="hidden" name="upload_photo" value="1">
</form>

<!-- Photo Upload Modal -->
<div id="photoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Preview Foto Profil</h3>
            <button type="button" class="modal-close" onclick="closePhotoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body text-center">
            <div class="photo-preview">
                <img id="previewImage" src="" alt="Preview">
            </div>
            <div class="file-info">
                <p id="fileInfo"></p>
            </div>
            <p class="mt-3">Apakah Anda ingin menggunakan foto ini sebagai foto profil?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePhotoModal()">
                Batal
            </button>
            <button type="button" class="btn btn-primary" onclick="confirmPhotoUpload()">
                <i class="fas fa-check mr-2"></i>
                Gunakan Foto Ini
            </button>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Mengupload foto...</p>
    </div>
</div>

<style>
/* Profile Page Styles */
.profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

/* Profile Cards */
.profile-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.profile-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.card-header {
    padding: 24px 24px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.card-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.card-header p {
    color: #64748b;
    margin: 0;
    font-size: 0.95rem;
}

.card-content {
    padding: 24px;
}

/* Profile Photo Section */
.profile-photo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.profile-avatar-large {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar-large img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid #e2e8f0;
    object-fit: cover;
    transition: all 0.3s ease;
}

.profile-avatar-large:hover img {
    transform: scale(1.05);
    border-color: #667eea;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0,102,255,0.3);
}

.avatar-edit-btn:hover {
    background: linear-gradient(135deg, #0044cc, #00aaff);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,102,255,0.4);
}

.photo-info h4 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.text-muted {
    color: #64748b !important;
    font-size: 0.95rem;
}

/* Role Badge */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    margin-top: 8px;
}

.role-admin { 
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.role-manager { 
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.role-programmer { 
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.role-support { 
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

/* Alert Messages */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

.alert-success {
    background: linear-gradient(90deg, #dcfce7, #bbf7d0);
    color: #166534;
    border: 1px solid #22c55e;
}

.alert-error {
    background: linear-gradient(90deg, #fee2e2, #fecaca);
    color: #dc2626;
    border: 1px solid #ef4444;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Form Styles */
.profile-form {
    max-width: 100%;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Email validation styling */
.form-group input[type="email"]:invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-disabled {
    background: #f3f4f6 !important;
    color: #9ca3af !important;
    cursor: not-allowed;
}

.form-help {
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 4px;
    display: block;
}

.form-divider {
    border: 0;
    height: 1px;
    background: #e5e7eb;
    margin: 32px 0 24px 0;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 16px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-start;
    margin-top: 24px;
}

/* Buttons */
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
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(90deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(90deg, #5a67d8, #6b46c1);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.mr-2 {
    margin-right: 8px;
}

.mt-3 {
    margin-top: 16px;
}

.text-center {
    text-align: center;
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
    max-width: 500px;
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
    margin: 0;
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

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.photo-preview {
    padding: 20px;
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    margin-bottom: 16px;
}

.photo-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: 3px solid #fff;
}

.file-info {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 8px;
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-content {
    text-align: center;
    color: white;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Success animation */
.upload-success {
    animation: successPulse 0.6s ease-in-out;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-container {
        padding: 12px;
    }
    
    .profile-header {
        padding: 20px;
        text-align: left;
    }
    
    .profile-header h1 {
        font-size: 1.6rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .card-content {
        padding: 20px;
    }
    
    .card-header {
        padding: 20px 20px 12px;
    }
    
    .profile-avatar-large img {
        width: 100px;
        height: 100px;
    }
    
    .avatar-edit-btn {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .profile-container {
        padding: 10px;
    }
    
    .card-content {
        padding: 16px;
    }
    
    .card-header {
        padding: 16px;
    }
    
    .profile-header {
        padding: 16px;
        margin-bottom: 20px;
    }
    
    .profile-header h1 {
        font-size: 1.4rem;
    }
}
</style>

<script>
// Profile Page JavaScript
let selectedFile = null;

// Photo upload functions
function triggerPhotoUpload() {
    document.getElementById('photoInput').click();
}

function handleFileSelect() {
    const input = document.getElementById('photoInput');
    const file = input.files[0];
    
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB');
            input.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Hanya file JPG, PNG, dan GIF yang diizinkan');
            input.value = '';
            return;
        }
        
        selectedFile = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            
            // Show file info
            const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('fileInfo').textContent = `${file.name} (${fileSize})`;
            
            // Show modal
            showPhotoModal();
        };
        reader.readAsDataURL(file);
    }
}

function confirmPhotoUpload() {
    if (selectedFile) {
        // Show loading overlay
        showLoading();
        
        // Submit form
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
    
    // Reset file input
    document.getElementById('photoInput').value = '';
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Email validation - prevent spaces
function validateInputs() {
    const emailInput = document.getElementById('email');
    const nameInput = document.getElementById('name');
    const passwordInput = document.getElementById('new_password');
    
    // Email validation
    emailInput.addEventListener('input', function() {
        // Remove any spaces from email
        this.value = this.value.replace(/\s/g, '');
        
        if (this.value !== this.value.replace(/\s/g, '')) {
            this.setCustomValidity('Email tidak boleh mengandung spasi');
        } else {
            this.setCustomValidity('');
        }
    });
    
    emailInput.addEventListener('paste', function(e) {
        setTimeout(() => {
            this.value = this.value.replace(/\s/g, '');
        }, 10);
    });
    
    // Username validation
    nameInput.addEventListener('input', function() {
        // Remove any spaces from username
        this.value = this.value.replace(/\s/g, '');
        
        if (this.value !== this.value.replace(/\s/g, '')) {
            this.setCustomValidity('Username tidak boleh mengandung spasi');
        } else {
            this.setCustomValidity('');
        }
    });
    
    nameInput.addEventListener('paste', function(e) {
        setTimeout(() => {
            this.value = this.value.replace(/\s/g, '');
        }, 10);
    });
    
    // Password validation
    passwordInput.addEventListener('input', function() {
        // Remove any spaces from password
        this.value = this.value.replace(/\s/g, '');
        
        if (this.value !== this.value.replace(/\s/g, '')) {
            this.setCustomValidity('Password tidak boleh mengandung spasi');
        } else {
            this.setCustomValidity('');
        }
    });
    
    passwordInput.addEventListener('paste', function(e) {
        setTimeout(() => {
            this.value = this.value.replace(/\s/g, '');
        }, 10);
    });
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

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts
    autoHideAlerts();
    
    // Initialize input validation
    validateInputs();
    
    // Add success animation if photo was uploaded
    <?php if (!empty($photo_success)): ?>
    const profileImage = document.getElementById('profileImage');
    if (profileImage) {
        profileImage.classList.add('upload-success');
        setTimeout(() => {
            profileImage.classList.remove('upload-success');
        }, 600);
    }
    <?php endif; ?>
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closePhotoModal();
        }
    });
    
    // Hide loading on page load
    hideLoading();
});

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) {
        closePhotoModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modal
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});
</script>