<?php
// Get statistics from database
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];
$total_users = $koneksi->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];
$active_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'done'")->fetch_assoc()['count'];

// Get recent activities
$recent_todos = $koneksi->query("
    SELECT t.*, a.name as app_name, u.name as user_name 
    FROM todos t 
    LEFT JOIN apps a ON t.app_id = a.id 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 4
");

// Get user role statistics
$user_stats = $koneksi->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
?>

<div class="dashboard-container">
    <!-- Welcome Section - Compact -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">Dashboard IT Management</h1>
            <p class="welcome-subtitle">Selamat datang, <?= $_SESSION['user_name'] ?> (<?= ucfirst($_SESSION['user_role']) ?>)</p>
        </div>
        <div class="welcome-date">
            <i class="fas fa-calendar-alt"></i>
            <?= date('d M Y') ?>
        </div>
    </div>

    <!-- Statistics Cards - Optimized -->
    <div class="stats-container">
        <div class="stat-card stat-blue">
            <div class="stat-icon">
                <i class="fas fa-th-large"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_apps ?></h3>
                <p>Aplikasi</p>
            </div>
        </div>

        <div class="stat-card stat-green">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_users ?></h3>
                <p>Pengguna</p>
            </div>
        </div>

        <div class="stat-card stat-orange">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <h3><?= $active_tasks ?></h3>
                <p>Tugas Aktif</p>
            </div>
        </div>

        <div class="stat-card stat-purple">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $completed_tasks ?></h3>
                <p>Selesai</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Recent Activities -->
        <div class="content-card">
            <div class="card-header">
                <h3>Aktivitas Terbaru</h3>
                <a href="?page=todos" class="view-all">Lihat Semua</a>
            </div>
            <div class="activity-list">
                <?php while($todo = $recent_todos->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-icon priority-<?= $todo['priority'] ?>">
                        <i class="fas fa-<?= getPriorityIcon($todo['priority']) ?>"></i>
                    </div>
                    <div class="activity-content">
                        <h4><?= htmlspecialchars($todo['title']) ?></h4>
                        <div class="activity-meta">
                            <span class="app-name"><?= htmlspecialchars($todo['app_name']) ?></span>
                            <span class="user-name"><?= htmlspecialchars($todo['user_name']) ?></span>
                            <span class="date"><?= date('d/m/Y', strtotime($todo['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="priority-badge priority-<?= $todo['priority'] ?>">
                        <?= ucfirst($todo['priority']) ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="quick-actions">
                <a href="?page=todos" class="quick-btn btn-blue">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="?page=users" class="quick-btn btn-green">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah User</span>
                </a>
                <a href="?page=pelaporan" class="quick-btn btn-orange">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
                <a href="?page=profile" class="quick-btn btn-purple">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>

        <!-- System Overview -->
        <div class="content-card overview-card">
            <div class="card-header">
                <h3>System Overview</h3>
            </div>
            <div class="overview-content">
                <div class="overview-item">
                    <div class="overview-label">Database Status</div>
                    <div class="status-indicator online">Online</div>
                </div>
                <div class="overview-item">
                    <div class="overview-label">Server Performance</div>
                    <div class="status-indicator good">Good</div>
                </div>
                <div class="overview-item">
                    <div class="overview-label">Total Tasks</div>
                    <div class="overview-value"><?= $total_todos ?></div>
                </div>
            </div>
        </div>

        <!-- Role Distribution -->
        <div class="content-card">
            <div class="card-header">
                <h3>User Roles</h3>
            </div>
            <div class="role-distribution">
                <?php 
                $colors = ['admin' => '#ef4444', 'programmer' => '#3b82f6', 'support' => '#10b981'];
                while($role = $user_stats->fetch_assoc()): 
                ?>
                <div class="role-item">
                    <div class="role-info">
                        <span class="role-name"><?= ucfirst($role['role']) ?></span>
                        <span class="role-count"><?= $role['count'] ?></span>
                    </div>
                    <div class="role-bar">
                        <div class="role-progress" 
                             style="width: <?= ($role['count'] / $total_users) * 100 ?>%; background: <?= $colors[$role['role']] ?>">
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php
function getPriorityIcon($priority) {
    $icons = [
        'high' => 'exclamation-triangle',
        'medium' => 'minus',
        'low' => 'arrow-down'
    ];
    return $icons[$priority] ?? 'circle';
}
?>

<style>
/* Optimized Dashboard Styles */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 16px;
    background: #f8fafc;
    min-height: calc(100vh - 60px);
}

/* Welcome Section - More Compact */
.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.welcome-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.welcome-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
}

.welcome-date {
    font-size: 0.85rem;
    opacity: 0.8;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Stats Container - Smaller Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.stat-blue .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-green .stat-icon { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.stat-orange .stat-icon { background: linear-gradient(135deg, #ff7b7b, #ff9999); }
.stat-purple .stat-icon { background: linear-gradient(135deg, #a18cd1, #fbc2eb); }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 2px;
}

.stat-info p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

/* Content Grid - More Compact */
.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.content-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.view-all {
    font-size: 0.8rem;
    color: #3b82f6;
    text-decoration: none;
}

.view-all:hover {
    text-decoration: underline;
}

/* Activity List - Compact */
.activity-list {
    padding: 16px 20px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.priority-high { background: #ef4444; }
.priority-medium { background: #f59e0b; }
.priority-low { background: #10b981; }

.activity-content {
    flex: 1;
}

.activity-content h4 {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
}

.activity-meta {
    display: flex;
    gap: 12px;
    font-size: 0.75rem;
    color: #9ca3af;
}

.priority-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    color: white;
}

/* Quick Actions - Smaller */
.quick-actions {
    padding: 16px 20px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 12px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.quick-btn:hover {
    transform: translateY(-2px);
}

.quick-btn i {
    font-size: 1.2rem;
    margin-bottom: 6px;
}

.btn-blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.btn-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.btn-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); }
.btn-purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); }

/* Overview Card */
.overview-content {
    padding: 16px 20px;
}

.overview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f8fafc;
}

.overview-item:last-child {
    border-bottom: none;
}

.overview-label {
    font-size: 0.85rem;
    color: #6b7280;
}

.status-indicator {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
}

.status-indicator.online {
    background: #dcfce7;
    color: #166534;
}

.status-indicator.good {
    background: #dbeafe;
    color: #1e40af;
}

.overview-value {
    font-weight: 600;
    color: #1f2937;
}

/* Role Distribution */
.role-distribution {
    padding: 16px 20px;
}

.role-item {
    margin-bottom: 14px;
}

.role-item:last-child {
    margin-bottom: 0;
}

.role-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 0.85rem;
}

.role-name {
    color: #374151;
    font-weight: 500;
}

.role-count {
    color: #6b7280;
}

.role-bar {
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
}

.role-progress {
    height: 100%;
    transition: width 0.3s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 12px;
    }
    
    .welcome-section {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .stat-info h3 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .activity-meta {
        flex-direction: column;
        gap: 2px;
    }
}
</style>