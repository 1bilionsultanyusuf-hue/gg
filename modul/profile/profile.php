<?php
// Get current user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $koneksi->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

function getRoleIcon($role) {
    $icons = [
        'admin' => '<i class="fas fa-crown"></i>',
        'manager' => '<i class="fas fa-user-tie"></i>',
        'programmer' => '<i class="fas fa-code"></i>',
        'support' => '<i class="fas fa-headset"></i>'
    ];
    return $icons[$role] ?? '<i class="fas fa-user"></i>';
}

function getProfilePhotoUrlLocal($user_data) {
    if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
        return $user_data['profile_photo'] . '?v=' . time();
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=0066ff&color=fff&size=150";
}
?>

<div class="main-content">
    <div class="profile-container">
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-banner">
                <div class="profile-avatar-large">
                    <img src="<?= getProfilePhotoUrlLocal($user_data) ?>" 
                         alt="<?= htmlspecialchars($user_data['name']) ?>"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_data['name']) ?>&background=0066ff&color=fff&size=150'">
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
                    <p class="profile-joined">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Bergabung sejak <?= date('d M Y', strtotime($user_data['created_at'] ?? 'now')) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Profile Stats -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Total Tugas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Tugas Selesai</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Tugas Pending</p>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="profile-card">
            <div class="card-header">
                <h3>Informasi Profil</h3>
                <a href="index.php?page=settings" class="btn btn-primary">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Profil
                </a>
            </div>
            <div class="card-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Nama Lengkap</label>
                        <p><?= htmlspecialchars($user_data['name']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?= htmlspecialchars($user_data['email']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Jenis Kelamin</label>
                        <p><?= ucfirst($user_data['gender'] ?? 'Tidak diset') ?></p>
                    </div>
                    <div class="info-item">
                        <label>Role</label>
                        <p>
                            <?= getRoleIcon($user_data['role']) ?>
                            <?= ucfirst($user_data['role']) ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <p>
                            <span class="status-badge status-active">
                                <i class="fas fa-circle"></i>
                                Aktif
                            </span>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Terakhir Login</label>
                        <p>
                            <i class="fas fa-clock mr-1"></i>
                            <?= date('d M Y H:i', strtotime($user_data['last_login'] ?? 'now')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Profile Container */
.profile-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

/* Profile Header */
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 32px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.profile-banner {
    display: flex;
    align-items: center;
    gap: 32px;
}

.profile-avatar-large img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.profile-info h1 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.profile-info p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 8px;
}

.profile-joined {
    font-size: 0.9rem !important;
    opacity: 0.8 !important;
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
    backdrop-filter: blur(10px);
}

/* Profile Stats */
.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.stat-info p {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
}

/* Profile Card */
.profile-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
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

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
}

.info-item label {
    display: block;
    font-weight: 500;
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.info-item p {
    color: #1f2937;
    font-size: 1rem;
    font-weight: 500;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-active i {
    color: #22c55e;
    font-size: 0.7rem;
}

/* Button */
.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(90deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(90deg, #5a67d8, #6b46c1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.mr-1 { margin-right: 4px; }
.mr-2 { margin-right: 8px; }

/* Colors */
.bg-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.bg-green { background: linear-gradient(135deg, #10b981, #059669); }
.bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

/* Responsive Design */
@media (max-width: 768px) {
    .profile-banner {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .profile-stats {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .card-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .btn {
        text-align: center;
        justify-content: center;
    }
}
</style>