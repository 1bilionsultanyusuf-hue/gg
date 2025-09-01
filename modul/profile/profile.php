<?php
// Get current user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $koneksi->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Get user statistics
$user_stats_query = "
    SELECT 
        COUNT(t.id) as total_todos,
        COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as active_todos,
        COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed_todos
    FROM todos t
    LEFT JOIN taken tk ON t.id = tk.id_todos AND tk.user_id = ?
    WHERE t.user_id = ?
";
$stmt = $koneksi->prepare($user_stats_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

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

        <!-- Statistics Cards -->
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_todos'] ?></h3>
                    <p>Total Tugas</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['active_todos'] ?></h3>
                    <p>Tugas Aktif</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['completed_todos'] ?></h3>
                    <p>Tugas Selesai</p>
                </div>
            </div>
        </div>

        <!-- Profile Form and Actions -->
        <div class="profile-content-grid">
            <!-- Edit Profile Form -->
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
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <input type="text" value="<?= ucfirst($user_data['role']) ?>" disabled class="form-disabled">
                            <small class="form-help">Role tidak dapat diubah</small>
                        </div>
                        
                        <hr class="form-divider">
                        
                        <h4 class="form-section-title">Ubah Password (Opsional)</h4>
                        
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

            <!-- Account Actions -->
            <div class="profile-card">
                <div class="card-header">
                    <h3>Pengaturan Akun</h3>
                </div>
                <div class="card-content">
                    <div class="account-actions">
                        <div class="action-item">
                            <div class="action-icon bg-blue">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="action-content">
                                <h4>Keamanan Akun</h4>
                                <p>Kelola password dan keamanan akun</p>
                            </div>
                            <button class="action-btn btn-outline" onclick="focusPasswordSection()">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        
                        <div class="action-item">
                            <div class="action-icon bg-green">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="action-content">
                                <h4>Export Data</h4>
                                <p>Download data aktivitas Anda</p>
                            </div>
                            <button class="action-btn btn-outline" onclick="exportUserData()">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        
                        <div class="action-item danger">
                            <div class="action-icon bg-red">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <div class="action-content">
                                <h4>Logout</h4>
                                <p>Keluar dari sistem</p>
                            </div>
                            <button class="action-btn btn-danger" onclick="confirmLogout(event)">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getRoleIcon($role) {
    $icons = [
        'admin' => '<i class="fas fa-crown"></i>',
        'programmer' => '<i class="fas fa-code"></i>',
        'support' => '<i class="fas fa-headset"></i>'
    ];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}
?>

<style>
/* Profile Page Styles */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
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
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0066ff;
    cursor: pointer;
    transition: all 0.3s ease;
}

.avatar-edit-btn:hover {
    background: white;
    transform: scale(1.1);
}

.profile-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.profile-email {
    font-size: 1.2rem;
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

.profile-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.stat-item {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-4px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.bg-blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.bg-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); }
.bg-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.bg-red { background: linear-gradient(135deg, #ff6b6b, #ee5a52); }

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-content p {
    color: #6b7280;
    font-size: 0.9rem;
}

.profile-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
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

.account-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.action-item:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.action-item.danger:hover {
    border-color: #fca5a5;
    background: #fef2f2;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.action-content {
    flex: 1;
}

.action-content h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.action-content p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.btn-danger {
    background: #fee2e2;
    border-color: #fecaca;
    color: #dc2626;
}

.btn-danger:hover {
    background: #fecaca;
    border-color: #f87171;
}

/* Single column layout for content grid */
.content-grid.single-column {
    display: block;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-banner {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .profile-stats {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .profile-content-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .profile-name {
        font-size: 2rem;
    }
    
    .profile-email {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .profile-banner {
        padding: 24px;
    }
    
    .profile-avatar-large img {
        width: 120px;
        height: 120px;
    }
    
    .stat-item {
        padding: 16px;
        gap: 16px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .stat-content h3 {
        font-size: 1.5rem;
    }
}
</style>

<script>
function focusPasswordSection() {
    document.getElementById('current_password').focus();
    document.getElementById('current_password').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
    });
}

function exportUserData() {
    alert('Export data functionality coming soon!');
}

function confirmLogout(e) {
    e.preventDefault();
    
    if(confirm('Apakah Anda yakin ingin logout dari sistem?')) {
        // Create logout success animation
        const popup = document.createElement('div');
        popup.className = 'logout-popup';
        popup.innerHTML = `
            <div class="popup-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="popup-text">Logout berhasil!</div>
        `;
        document.body.appendChild(popup);

        // Add popup styles
        const style = document.createElement('style');
        style.innerHTML = `
        .logout-popup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            font-weight: bold;
            color: #ef4444;
            z-index: 10000;
            opacity: 1;
            transition: all 0.3s ease;
        }
        .popup-icon {
            width: 30px;
            height: 30px;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            animation: pulse 0.5s ease;
        }
        .popup-text { font-size: 14px; }
        @keyframes pulse { 
            0%{transform:scale(1);} 
            50%{transform:scale(1.1);} 
            100%{transform:scale(1);} 
        }
        `;
        document.head.appendChild(style);

        // Redirect after animation
        setTimeout(() => { popup.style.opacity = '0'; }, 1500);
        setTimeout(() => { 
            window.location.href = '?logout=1'; 
        }, 2000);
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.profile-form');
    const newPassword = document.getElementById('new_password');
    const currentPassword = document.getElementById('current_password');
    
    // Password change validation
    newPassword.addEventListener('input', function() {
        if (this.value && !currentPassword.value) {
            currentPassword.required = true;
            currentPassword.style.borderColor = '#ef4444';
        } else {
            currentPassword.required = false;
            currentPassword.style.borderColor = '#d1d5db';
        }
    });
    
    currentPassword.addEventListener('input', function() {
        if (this.value) {
            this.style.borderColor = '#d1d5db';
        }
    });
});
</script>