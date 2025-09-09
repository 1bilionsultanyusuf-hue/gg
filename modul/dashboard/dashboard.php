<?php
// Get real-time statistics from database dengan integrasi taken
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];
$total_users = $koneksi->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];

// Get active tasks (from taken table with status 'in_progress')
$active_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];

// Get completed tasks (from taken table with status 'done')
$completed_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'done'")->fetch_assoc()['count'];

// Get available tasks (todos yang belum di-take)
$available_tasks_query = "
    SELECT COUNT(*) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.id IS NULL
";
$available_tasks = $koneksi->query($available_tasks_query)->fetch_assoc()['count'];

// Get recent activities dengan detail lengkap
$recent_todos = $koneksi->query("
    SELECT t.*, a.name as app_name, u.name as user_name, tk.status as taken_status, tk.date as taken_date,
           CASE 
               WHEN tk.status IS NULL THEN 'available'
               ELSE tk.status 
           END as task_status
    FROM todos t 
    LEFT JOIN apps a ON t.app_id = a.id 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN taken tk ON t.id = tk.id_todos
    ORDER BY t.created_at DESC 
    LIMIT 5
");

// Get user role statistics
$user_stats = $koneksi->query("
    SELECT role, COUNT(*) as count,
           ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users)), 1) as percentage
    FROM users 
    GROUP BY role
    ORDER BY count DESC
");

// Get priority statistics
$priority_stats = $koneksi->query("
    SELECT priority, COUNT(*) as count 
    FROM todos 
    GROUP BY priority
");

// Get app statistics
$app_performance = $koneksi->query("
    SELECT a.name, COUNT(t.id) as total_todos,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as in_progress,
           COUNT(CASE WHEN tk.status IS NULL THEN 1 END) as available
    FROM apps a
    LEFT JOIN todos t ON a.id = t.app_id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    GROUP BY a.id, a.name
    HAVING total_todos > 0
    ORDER BY total_todos DESC
    LIMIT 3
");
?>

<div class="dashboard-container">
    <!-- Welcome Section - Enhanced -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">Dashboard IT Management</h1>
            <p class="welcome-subtitle">
                Selamat datang, <?= $_SESSION['user_name'] ?> (<?= ucfirst($_SESSION['user_role']) ?>)
            </p>
        </div>
        <div class="welcome-date">
            <div class="date-info">
                <i class="fas fa-calendar-alt"></i>
                <span><?= date('d M Y') ?></span>
            </div>
            <div class="time-info">
                <i class="fas fa-clock"></i>
                <span id="currentTime"><?= date('H:i') ?></span>
            </div>
        </div>
    </div>

    <!-- Main Statistics Cards - Clickable dan Terintegrasi -->
    <div class="stats-container">
        <a href="?page=apps" class="stat-card stat-blue clickable">
            <div class="stat-icon">
                <i class="fas fa-th-large"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_apps ?></h3>
                <p>Aplikasi Terdaftar</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>Kelola Apps</span>
                </div>
            </div>
        </a>

        <a href="?page=users" class="stat-card stat-green clickable">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_users ?></h3>
                <p>Total Pengguna</p>
                <div class="stat-trend">
                    <i class="fas fa-user-plus"></i>
                    <span>Kelola Users</span>
                </div>
            </div>
        </a>

        <a href="?page=todos" class="stat-card stat-orange clickable">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= $active_tasks ?></h3>
                <p>Tugas Aktif</p>
                <div class="stat-trend">
                    <i class="fas fa-tasks"></i>
                    <span>Lihat Progress</span>
                </div>
            </div>
        </a>

        <a href="?page=taken" class="stat-card stat-purple clickable">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $completed_tasks ?></h3>
                <p>Tugas Selesai</p>
                <div class="stat-trend">
                    <i class="fas fa-chart-line"></i>
                    <span>Lihat Hasil</span>
                </div>
            </div>
        </a>
    </div>

    <!-- Secondary Stats -->
    <div class="secondary-stats">
        <div class="stat-item">
            <div class="stat-value"><?= $available_tasks ?></div>
            <div class="stat-label">Tugas Tersedia</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $total_todos ?></div>
            <div class="stat-label">Total Todos</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $completed_tasks + $active_tasks ?></div>
            <div class="stat-label">Tugas Diambil</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">
                <?= $completed_tasks > 0 ? round(($completed_tasks / ($completed_tasks + $active_tasks)) * 100) : 0 ?>%
            </div>
            <div class="stat-label">Success Rate</div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
       
<?php
function getPriorityIcon($priority) {
    $icons = [
        'high' => 'exclamation-triangle',
        'medium' => 'minus',
        'low' => 'arrow-down'
    ];
    return $icons[$priority] ?? 'circle';
}

function getRoleIcon($role) {
    $icons = [
        'admin' => 'fas fa-crown',
        'programmer' => 'fas fa-code',
        'support' => 'fas fa-headset'
    ];
    return $icons[$role] ?? 'fas fa-user';
}
?>

<style>
/* Enhanced Dashboard Styles dengan Integrasi */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8fafc;
    min-height: calc(100vh - 60px);
}

/* Enhanced Welcome Section */
.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px 30px;
    border-radius: 16px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.welcome-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.welcome-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 6px;
}

.welcome-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 12px;
}

.quick-stats {
    display: flex;
    gap: 20px;
    margin-top: 8px;
}

.quick-stats span {
    font-size: 0.9rem;
    padding: 4px 12px;
    background: rgba(255,255,255,0.15);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.welcome-date {
    text-align: right;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.date-info, .time-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

/* Clickable Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.stat-card.clickable:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    text-decoration: none;
    color: inherit;
}

.stat-card.stat-blue {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.stat-card.stat-green {
    background: linear-gradient(135deg, #56ab2f, #a8e6cf);
    color: white;
}

.stat-card.stat-orange {
    background: linear-gradient(135deg, #ff7b7b, #ff9999);
    color: white;
}

.stat-card.stat-purple {
    background: linear-gradient(135deg, #a18cd1, #fbc2eb);
    color: white;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    backdrop-filter: blur(10px);
}

.stat-info h3 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-info p {
    font-size: 0.95rem;
    opacity: 0.9;
    margin-bottom: 8px;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    opacity: 0.8;
}

/* Secondary Stats */
.secondary-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.stat-item {
    text-align: center;
    padding: 16px;
}

.stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1f2937;
    display: block;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.content-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.content-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-all {
    font-size: 0.85rem;
    color: #3b82f6;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.view-all:hover {
    background: #eff6ff;
    text-decoration: none;
}

/* Activity List */
.activity-list {
    padding: 20px 24px;
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.priority-high { background: linear-gradient(135deg, #ef4444, #dc2626); }
.priority-medium { background: linear-gradient(135deg, #f59e0b, #d97706); }
.priority-low { background: linear-gradient(135deg, #10b981, #059669); }

.activity-content {
    flex: 1;
}

.activity-content h4 {
    font-size: 0.95rem;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 6px;
    line-height: 1.3;
}

.activity-meta {
    display: flex;
    gap: 16px;
    font-size: 0.8rem;
    color: #9ca3af;
    flex-wrap: wrap;
}

.activity-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.task-status {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    flex-shrink: 0;
}

.status-available {
    background: #f3f4f6;
    color: #6b7280;
}

.status-in_progress {
    background: #fef3c7;
    color: #d97706;
}

.status-done {
    background: #dcfce7;
    color: #059669;
}

/* App Performance */
.app-performance {
    padding: 20px 24px;
}

.app-perf-item {
    padding: 16px 0;
    border-bottom: 1px solid #f8fafc;
}

.app-perf-item:last-child {
    border-bottom: none;
}

.app-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.app-stats-mini {
    display: flex;
    gap: 16px;
    margin-bottom: 8px;
}

.mini-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.mini-stat .value {
    font-size: 1.1rem;
    font-weight: 600;
}

.mini-stat .label {
    font-size: 0.7rem;
    color: #9ca3af;
}

.mini-stat.completed .value { color: #10b981; }
.mini-stat.progress .value { color: #f59e0b; }
.mini-stat.available .value { color: #6b7280; }

.progress-bar-mini {
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}

/* User Distribution */
.user-distribution {
    padding: 20px 24px;
}

.role-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}

.role-item:last-child {
    border-bottom: none;
}

.role-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.role-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #374151;
}

.role-count {
    font-size: 0.85rem;
    color: #6b7280;
}

.role-bar {
    flex: 1;
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
    margin: 0 12px;
}

.role-progress {
    height: 100%;
    transition: width 0.3s ease;
}

.role-admin { background: linear-gradient(90deg, #dc2626, #ef4444); }
.role-programmer { background: linear-gradient(90deg, #0066ff, #33ccff); }
.role-support { background: linear-gradient(90deg, #10b981, #34d399); }

.role-percentage {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    min-width: 40px;
    text-align: right;
}

/* Quick Actions */
.quick-actions {
    padding: 20px 24px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    border-radius: 12px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    font-weight: 500;
}

.quick-btn:hover {
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
}

.quick-btn i {
    font-size: 1.2rem;
}

.btn-blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.btn-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.btn-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); }
.btn-purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 12px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .secondary-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px;
    }
    
    .welcome-section {
        flex-direction: column;
        text-align: center;
        gap: 16px;
        padding: 20px;
    }
    
    .quick-stats {
        justify-content: center;
    }
    
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .secondary-stats {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .activity-meta {
        flex-direction: column;
        gap: 4px;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .stat-info h3 {
        font-size: 1.8rem;
    }
}

/* Real-time Clock Update */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.time-info span {
    animation: pulse 2s infinite;
}
</style>

<script>
// Real-time clock update
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Update time every minute
setInterval(updateTime, 60000);

// Add click animations to stat cards
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card.clickable');
    
    statCards.forEach(card => {
        card.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(-2px) scale(0.98)';
        });
        
        card.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-4px) scale(1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add hover effect to quick action buttons
    const quickBtns = document.querySelectorAll('.quick-btn');
    
    quickBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    });
});

// Auto-refresh dashboard data setiap 5 menit
function refreshDashboard() {
    // Hanya refresh jika berada di halaman dashboard
    if (window.location.search.includes('page=dashboard') || !window.location.search.includes('page=')) {
        setTimeout(() => {
            window.location.reload();
        }, 300000); // 5 menit
    }
}

// Inisialisasi refresh
refreshDashboard();
</script>