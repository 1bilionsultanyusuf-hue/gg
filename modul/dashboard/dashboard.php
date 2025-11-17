<?php
// Get Dashboard Statistics
// Total Applications
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];

// Total Todos
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];

// Total Users
$total_users = $koneksi->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// High Priority Todos
$high_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'high'")->fetch_assoc()['count'];

// Active Todos (in progress)
$active_todos = $koneksi->query("
    SELECT COUNT(DISTINCT t.id) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.status = 'in_progress'
")->fetch_assoc()['count'];

// Completed Todos
$completed_todos = $koneksi->query("
    SELECT COUNT(DISTINCT t.id) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.status = 'done'
")->fetch_assoc()['count'];

// Users by Role
$admin_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$programmer_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'programmer'")->fetch_assoc()['count'];
$support_count = $koneksi->query("SELECT COUNT(*) as count FROM users WHERE role = 'support'")->fetch_assoc()['count'];

// Recent Apps (Last 5)
$recent_apps = $koneksi->query("
    SELECT a.*, 
           COUNT(t.id) as total_todos
    FROM apps a
    LEFT JOIN todos t ON a.id = t.app_id
    GROUP BY a.id
    ORDER BY a.id DESC
    LIMIT 5
");

// Recent Users (Last 5)
$recent_users = $koneksi->query("
    SELECT * FROM users
    ORDER BY id DESC
    LIMIT 5
");

// Helper functions
function getAppIcon($appName) {
    $icons = [
        'keuangan' => 'money-bill-wave',
        'inventaris' => 'boxes',
        'crm' => 'users',
        'hris' => 'user-tie',
        'default' => 'cube'
    ];
    
    $name = strtolower($appName);
    foreach($icons as $key => $icon) {
        if(strpos($name, $key) !== false) {
            return $icon;
        }
    }
    return $icons['default'];
}

function getRoleColor($role) {
    $colors = [
        'admin' => '#dc2626',
        'programmer' => '#2196f3',
        'support' => '#27ae60'
    ];
    return $colors[$role] ?? '#6b7280';
}

function getRoleDisplayName($role) {
    $names = [
        'admin' => 'Administrator',
        'programmer' => 'Programmer',
        'support' => 'Support'
    ];
    return $names[$role] ?? ucfirst($role);
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

.dashboard-container {
    padding: 20px 30px;
    max-width: 100%;
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding: 8px 0;
}

.page-title {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
}

/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 4px 20px rgba(13, 138, 245, 0.3);
}

.welcome-card h2 {
    font-size: 1.6rem;
    margin-bottom: 8px;
}

.welcome-card p {
    font-size: 0.95rem;
    opacity: 0.95;
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.stat-info h3 {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.blue {
    background: linear-gradient(135deg, #0066ff, #33ccff);
}

.stat-icon.green {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-icon.purple {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.stat-icon.orange {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-icon.red {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.stat-icon.cyan {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.stat-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
    color: #6b7280;
    font-size: 0.8rem;
}

.stat-footer i {
    font-size: 0.75rem;
}

/* Secondary Stats Grid */
.secondary-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}

.secondary-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
}

.secondary-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    flex-shrink: 0;
}

.secondary-stat-content h4 {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 4px;
}

.secondary-stat-content .number {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1f2937;
}

/* Recent Activity Section */
.activity-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 28px;
}

.activity-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f3f4f6;
}

.activity-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.activity-header .view-all {
    color: #0d8af5;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: color 0.2s;
}

.activity-header .view-all:hover {
    color: #0b7ad6;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.activity-item:hover {
    background: #f8fafc;
}

.activity-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.activity-item-content {
    flex: 1;
    min-width: 0;
}

.activity-item-title {
    font-weight: 500;
    color: #1f2937;
    font-size: 0.9rem;
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.activity-item-subtitle {
    font-size: 0.8rem;
    color: #6b7280;
}

.activity-item-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    flex-shrink: 0;
}

/* User Activity Items */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.role-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.role-admin { background: #dc2626; }
.role-programmer { background: #2196f3; }
.role-support { background: #27ae60; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 32px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 12px;
    color: #d1d5db;
}

.empty-state p {
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid,
    .secondary-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .activity-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px 20px;
    }
    
    .stats-grid,
    .secondary-stats {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .welcome-card {
        padding: 20px 24px;
    }
    
    .welcome-card h2 {
        font-size: 1.3rem;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-number {
        font-size: 1.8rem;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card">
        <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
        <p>Berikut adalah ringkasan sistem Anda hari ini</p>
    </div>

    <!-- Main Statistics -->
    <div class="stats-grid">
        <!-- Total Apps -->
        <div class="stat-card" onclick="window.location.href='?page=apps'">
            <div class="stat-card-header">
                <div class="stat-info">
                    <h3>Total Aplikasi</h3>
                    <div class="stat-number"><?= $total_apps ?></div>
                </div>
                <div class="stat-icon blue">
                    <i class="fas fa-cubes"></i>
                </div>
            </div>
            <div class="stat-footer">
                <i class="fas fa-arrow-right"></i>
                <span>Lihat semua aplikasi</span>
            </div>
        </div>

        <!-- Total Todos -->
        <div class="stat-card" onclick="window.location.href='?page=todos'">
            <div class="stat-card-header">
                <div class="stat-info">
                    <h3>Total Tugas</h3>
                    <div class="stat-number"><?= $total_todos ?></div>
                </div>
                <div class="stat-icon green">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
            <div class="stat-footer">
                <i class="fas fa-arrow-right"></i>
                <span>Lihat semua tugas</span>
            </div>
        </div>

        <!-- Total Users -->
        <div class="stat-card" onclick="window.location.href='?page=users'">
            <div class="stat-card-header">
                <div class="stat-info">
                    <h3>Total Pengguna</h3>
                    <div class="stat-number"><?= $total_users ?></div>
                </div>
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-footer">
                <i class="fas fa-arrow-right"></i>
                <span>Kelola pengguna</span>
            </div>
        </div>
    </div>
       
<script>
// Add smooth scroll behavior
document.querySelectorAll('a[href^="?page="]').forEach(link => {
    link.addEventListener('click', function(e) {
        // Let the default behavior happen (navigate to the page)
    });
});

// Add animation on load
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.stat-card, .secondary-stat-card, .activity-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>