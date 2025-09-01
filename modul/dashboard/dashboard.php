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
    LIMIT 5
");

// Get user role statistics
$user_stats = $koneksi->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
?>

<div class="main-content">
    <!-- Welcome Section -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1 class="welcome-title">
                <i class="fas fa-tachometer-alt mr-3"></i>
                Selamat Datang, <?= $_SESSION['user_name'] ?>!
            </h1>
            <p class="welcome-subtitle">
                Dashboard IT Management System - Kelola aplikasi, user, dan tugas dengan mudah
            </p>
            <div class="welcome-stats">
                <span class="welcome-stat">
                    <i class="fas fa-clock mr-1"></i>
                    <?= date('l, d F Y') ?>
                </span>
                <span class="welcome-stat">
                    <i class="fas fa-user-circle mr-1"></i>
                    Role: <?= ucfirst($_SESSION['user_role']) ?>
                </span>
            </div>
        </div>
        <div class="welcome-graphic">
            <i class="fas fa-chart-line"></i>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-blue">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="stat-details">
                    <h3 class="stat-number"><?= $total_apps ?></h3>
                    <p class="stat-label">Total Aplikasi</p>
                    <span class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        Active
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card bg-green">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3 class="stat-number"><?= $total_users ?></h3>
                    <p class="stat-label">Total Users</p>
                    <span class="stat-trend">
                        <i class="fas fa-user-check"></i>
                        Registered
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card bg-orange">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-details">
                    <h3 class="stat-number"><?= $active_tasks ?></h3>
                    <p class="stat-label">Tugas Aktif</p>
                    <span class="stat-trend">
                        <i class="fas fa-sync"></i>
                        In Progress
                    </span>
                </div>
            </div>
        </div>

        <div class="stat-card bg-purple">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3 class="stat-number"><?= $completed_tasks ?></h3>
                    <p class="stat-label">Tugas Selesai</p>
                    <span class="stat-trend">
                        <i class="fas fa-trophy"></i>
                        Completed
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Activities -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-history mr-2"></i>
                    Aktivitas Terbaru
                </h2>
                <a href="?page=todos" class="card-action">Lihat Semua</a>
            </div>
            <div class="card-content">
                <div class="activity-list">
                    <?php while($todo = $recent_todos->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-icon priority-<?= $todo['priority'] ?>">
                            <i class="fas fa-<?= $todo['priority'] == 'high' ? 'exclamation' : ($todo['priority'] == 'medium' ? 'minus' : 'arrow-down') ?>"></i>
                        </div>
                        <div class="activity-content">
                            <h4 class="activity-title"><?= htmlspecialchars($todo['title']) ?></h4>
                            <p class="activity-description"><?= htmlspecialchars(substr($todo['description'], 0, 80)) ?>...</p>
                            <div class="activity-meta">
                                <span class="meta-app">
                                    <i class="fas fa-cube mr-1"></i>
                                    <?= htmlspecialchars($todo['app_name']) ?>
                                </span>
                                <span class="meta-user">
                                    <i class="fas fa-user mr-1"></i>
                                    <?= htmlspecialchars($todo['user_name']) ?>
                                </span>
                                <span class="meta-date">
                                    <i class="fas fa-calendar mr-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($todo['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="activity-priority">
                            <span class="priority-badge priority-<?= $todo['priority'] ?>">
                                <?= ucfirst($todo['priority']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-bolt mr-2"></i>
                    Quick Actions
                </h2>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <a href="?page=todos" class="quick-action-btn bg-blue">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Tugas</span>
                    </a>
                    <a href="?page=users" class="quick-action-btn bg-green">
                        <i class="fas fa-user-plus"></i>
                        <span>Tambah User</span>
                    </a>
                    <a href="?page=pelaporan" class="quick-action-btn bg-orange">
                        <i class="fas fa-chart-bar"></i>
                        <span>Lihat Laporan</span>
                    </a>
                    <a href="?page=profile" class="quick-action-btn bg-purple">
                        <i class="fas fa-user-cog"></i>
                        <span>Edit Profile</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-server mr-2"></i>
                    System Status
                </h2>
            </div>
            <div class="card-content">
                <div class="system-stats">
                    <div class="system-item">
                        <div class="system-icon bg-green">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="system-info">
                            <h4>Database</h4>
                            <p class="status-online">Online</p>
                        </div>
                        <div class="system-indicator online"></div>
                    </div>
                    
                    <div class="system-item">
                        <div class="system-icon bg-blue">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div class="system-info">
                            <h4>Server Status</h4>
                            <p class="status-online">Operational</p>
                        </div>
                        <div class="system-indicator online"></div>
                    </div>
                    
                    <div class="system-item">
                        <div class="system-icon bg-orange">
                            <i class="fas fa-memory"></i>
                        </div>
                        <div class="system-info">
                            <h4>Memory Usage</h4>
                            <p class="status-warning">75%</p>
                        </div>
                        <div class="system-indicator warning"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Role Distribution -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-users-cog mr-2"></i>
                    Distribusi Role User
                </h2>
            </div>
            <div class="card-content">
                <div class="role-distribution">
                    <?php 
                    $colors = ['admin' => 'red', 'programmer' => 'blue', 'support' => 'green'];
                    while($role = $user_stats->fetch_assoc()): 
                    ?>
                    <div class="role-item">
                        <div class="role-info">
                            <h4><?= ucfirst($role['role']) ?></h4>
                            <span class="role-count"><?= $role['count'] ?> users</span>
                        </div>
                        <div class="role-bar">
                            <div class="role-progress bg-<?= $colors[$role['role']] ?>" 
                                 style="width: <?= ($role['count'] / $total_users) * 100 ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Specific Styles */
.welcome-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.welcome-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.welcome-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 16px;
}

.welcome-stats {
    display: flex;
    gap: 24px;
}

.welcome-stat {
    font-size: 0.9rem;
    opacity: 0.8;
}

.welcome-graphic {
    font-size: 4rem;
    opacity: 0.3;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    border-radius: 16px;
    padding: 24px;
    color: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.stat-card.bg-blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card.bg-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.stat-card.bg-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); }
.stat-card.bg-purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); }

.stat-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 8px;
}

.stat-trend {
    font-size: 0.8rem;
    opacity: 0.8;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.dashboard-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
}

.card-action {
    color: #0066ff;
    font-size: 0.9rem;
    text-decoration: none;
}

.card-content {
    padding: 24px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
}

.activity-icon.priority-high { background: #ef4444; }
.activity-icon.priority-medium { background: #f59e0b; }
.activity-icon.priority-low { background: #10b981; }

.activity-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #1f2937;
}

.activity-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 8px;
}

.activity-meta {
    display: flex;
    gap: 16px;
    font-size: 0.8rem;
    color: #9ca3af;
}

.priority-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.priority-badge.priority-high { background: #ef4444; }
.priority-badge.priority-medium { background: #f59e0b; }
.priority-badge.priority-low { background: #10b981; }

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border-radius: 12px;
    color: white;
    text-decoration: none;
    transition: transform 0.3s ease;
    font-weight: 500;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 2rem;
    margin-bottom: 8px;
}

.system-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f3f4f6;
}

.system-item:last-child {
    border-bottom: none;
}

.system-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.system-info h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #1f2937;
}

.status-online { color: #10b981; }
.status-warning { color: #f59e0b; }

.system-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-left: auto;
}

.system-indicator.online { background: #10b981; }
.system-indicator.warning { background: #f59e0b; }

.role-item {
    margin-bottom: 16px;
}

.role-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.role-info h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.role-count {
    font-size: 0.9rem;
    color: #6b7280;
}

.role-bar {
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}

.role-progress {
    height: 100%;
    transition: width 0.3s ease;
}

.role-progress.bg-red { background: #ef4444; }
.role-progress.bg-blue { background: #3b82f6; }
.role-progress.bg-green { background: #10b981; }

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-stats {
        flex-direction: column;
        gap: 8px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>