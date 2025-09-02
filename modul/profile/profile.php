<?php
// Get current user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $koneksi->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Handle form submission
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Nama tidak boleh kosong";
    }
    
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    // Check if current password is correct (only if changing password)
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Password lama harus diisi untuk mengubah password";
        } elseif ($current_password !== $user_data['password']) {
            $errors[] = "Password lama tidak benar";
        }
    }
    
    if (empty($errors)) {
        // Update query
        if (!empty($new_password)) {
            $update_query = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
            $stmt = $koneksi->prepare($update_query);
            $stmt->bind_param("sssi", $name, $email, $new_password, $user_id);
        } else {
            $update_query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $stmt = $koneksi->prepare($update_query);
            $stmt->bind_param("ssi", $name, $email, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $success_message = "Profil berhasil diperbarui!";
            // Refresh user data
            $user_data['name'] = $name;
            $user_data['email'] = $email;
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
        'programmer' => '<i class="fas fa-code"></i>',
        'support' => '<i class="fas fa-headset"></i>'
    ];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}
?>

<div class="main-content" style="margin-top: 80px;">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-banner">
                <div class="profile-avatar-large">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_data['name']) ?>&background=0066ff&color=fff&size=150" 
                         alt="<?= htmlspecialchars($user_data['name']) ?>"
                         onerror="this.src='https://ui-avatars.com/api/?name=User&background=0066ff&color=fff&size=150'">
                    <div class="avatar-edit-btn">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?= htmlspecialchars($user_data['name']) ?></h1>
                    <p class="profile-email"><?= htmlspecialchars($user_data['email']) ?></p>
                    <div class="profile-role">
                        <span class="role-badge role-<?= $user_data['role'] ?>">
                            <?= getRoleIcon($user_data['role']) ?>
                            <?= ucfirst($user_data['role']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form - Full Width -->
        <div class="profile-card">
            <div class="card-header">
                <h3>Edit Profil</h3>
            </div>
            <div class="card-content">
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $success_message ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul>
                        <?php foreach($errors as $error): ?>
                        <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($user_data['name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($user_data['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" value="<?= ucfirst($user_data['role']) ?>" disabled class="form-disabled">
                        <small class="form-help">Role tidak dapat diubah</small>
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

<style>
/* Profile Page Styles */
.profile-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
}

.profile-header {
    margin-bottom: 32px;
}

.profile-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 32px;
    color: white;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.profile-avatar-large {
    position: relative;
}

.profile-avatar-large img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 35px;
    height: 35px;
    background: rgba(255,255,255,0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0066ff;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.avatar-edit-btn:hover {
    background: white;
    transform: scale(1.1);
}

.profile-name {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.profile-email {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 16px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    background: rgba(255,255,255,0.2);
    color: white;
}

.profile-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 24px 24px 0;
    border-bottom: 1px solid #f1f5f9;
}

.card-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.card-content {
    padding: 24px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
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

.alert ul {
    margin: 0;
    padding-left: 16px;
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
    margin-bottom: 6px;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
}

.form-disabled {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
}

.form-help {
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 4px;
    display: block;
}

.form-divider {
    border: none;
    height: 1px;
    background: #e5e7eb;
    margin: 24px 0;
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
    margin-top: 24px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
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

/* Responsive Design */
@media (max-width: 768px) {
    .profile-banner {
        flex-direction: column;
        text-align: center;
        gap: 20px;
        padding: 30px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .profile-name {
        font-size: 1.8rem;
    }
    
    .profile-email {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .profile-container {
        padding: 0 16px;
    }
    
    .profile-banner {
        padding: 24px 16px;
    }
    
    .profile-avatar-large img {
        width: 100px;
        height: 100px;
    }
    
    .avatar-edit-btn {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    
    .card-content {
        padding: 20px;
    }
    
    .profile-name {
        font-size: 1.5rem;
    }
}
