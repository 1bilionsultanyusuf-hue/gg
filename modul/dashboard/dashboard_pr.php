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
    LIMIT 4
");

// Get user role statistics
$user_stats = $koneksi->query("
    SELECT role, COUNT(*) as count,
           ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users)), 1) as percentage
    FROM users 
    GROUP BY role
    ORDER BY count DESC
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
    <!-- Compact Welcome Section -->
    <div class="welcome-section-compact">
        <div class="welcome-content">
            <h1 class="welcome-title">Dashboard</h1>
            <p class="welcome-subtitle">
                Selamat datang, <?= $_SESSION['user_name'] ?> (<?= ucfirst($_SESSION['user_role']) ?>)
            </p>
        </div>
        <div class="welcome-date">
            <div class="date-time-info">
                <i class="fas fa-calendar-alt"></i>
                <span><?= date('d M Y') ?> • <span id="currentTime"><?= date('H:i') ?></span></span>
            </div>
        </div>
    </div>

    <!-- Compact Main Statistics Cards -->
    <div class="stats-container-compact">
        <a href="?page=apps" class="stat-card-compact stat-blue">
            <div class="stat-icon-compact">
                <i class="fas fa-th-large"></i>
            </div>
            <div class="stat-info-compact">
                <h3><?= $total_apps ?></h3>
                <p>Aplikasi</p>
            </div>
        </a>

        <a href="?page=users" class="stat-card-compact stat-green">
            <div class="stat-icon-compact">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info-compact">
                <h3><?= $total_users ?></h3>
                <p>Users</p>
            </div>
        </a>

        <a href="?page=taken" class="stat-card-compact stat-orange">
            <div class="stat-icon-compact">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info-compact">
                <h3><?= $active_tasks ?></h3>
                <p>Taken</p>
            </div>
        </a>

        <a href="?page=taken" class="stat-card-compact stat-purple">
            <div class="stat-icon-compact">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info-compact">
                <h3><?= $completed_tasks ?></h3>
                <p>Selesai</p>
            </div>
        </a>
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
/* Compact Dashboard Styles */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 16px;
    background: #f8fafc;
    min-height: calc(100vh - 60px);
}

/* Compact Welcome Section */
.welcome-section-compact {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.welcome-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.welcome-subtitle {
    font-size: 0.85rem;
    opacity: 0.9;
}

.date-time-info {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
}

/* Compact Stats Cards */
.stats-container-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.stat-card-compact {
    background: white;
    padding: 16px;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    min-height: 70px;
}

.stat-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
}

.stat-card-compact.stat-blue { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.stat-card-compact.stat-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); color: white; }
.stat-card-compact.stat-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); color: white; }
.stat-card-compact.stat-purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); color: white; }
.stat-card-compact.stat-gray { background: linear-gradient(135deg, #6b7280, #9ca3af); color: white; }
.stat-card-compact.stat-indigo { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; }

.stat-icon-compact {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

.stat-info-compact h3 {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 2px;
}

.stat-info-compact p {
    font-size: 0.8rem;
    opacity: 0.9;
    margin: 0;
}

/* Compact Content Grid */
.content-grid-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
}

.content-card-compact {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: all 0.3s ease;
}

.content-card-compact:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.card-header-compact {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header-compact h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.view-all-compact {
    font-size: 0.75rem;
    color: #3b82f6;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.view-all-compact:hover {
    background: #eff6ff;
    text-decoration: none;
}

/* Compact Activity List */
.activity-list-compact {
    padding: 12px 16px;
    max-height: 220px;
    overflow-y: auto;
}

.activity-item-compact {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
}

.activity-item-compact:last-child {
    border-bottom: none;
}

.activity-icon-compact {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.priority-high { background: linear-gradient(135deg, #ef4444, #dc2626); }
.priority-medium { background: linear-gradient(135deg, #f59e0b, #d97706); }
.priority-low { background: linear-gradient(135deg, #10b981, #059669); }

.activity-content-compact {
    flex: 1;
}

.activity-content-compact h4 {
    font-size: 0.8rem;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
    line-height: 1.2;
}

.activity-meta-compact {
    display: flex;
    gap: 8px;
    font-size: 0.7rem;
    color: #9ca3af;
}

.activity-meta-compact span {
    display: flex;
    align-items: center;
    gap: 3px;
}

.task-status-compact {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.status-available { background: #f3f4f6; color: #6b7280; }
.status-in_progress { background: #fef3c7; color: #d97706; }
.status-done { background: #dcfce7; color: #059669; }

/* Compact App Performance */
.app-performance-compact {
    padding: 12px 16px;
}

.app-perf-item-compact {
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
}

.app-perf-item-compact:last-child {
    border-bottom: none;
}

.app-name-compact {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
    font-size: 0.8rem;
}

.app-stats-mini-compact {
    display: flex;
    gap: 8px;
    margin-bottom: 4px;
}

.mini-stat-compact {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
}

.mini-stat-compact.completed { background: #dcfce7; color: #059669; }
.mini-stat-compact.progress { background: #fef3c7; color: #d97706; }
.mini-stat-compact.available { background: #f3f4f6; color: #6b7280; }

.progress-bar-mini-compact {
    height: 4px;
    background: #f3f4f6;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill-compact {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}

/* Compact User Distribution */
.user-distribution-compact {
    padding: 12px 16px;
}

.role-item-compact {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    border-bottom: 1px solid #f8fafc;
}

.role-item-compact:last-child {
    border-bottom: none;
}

.role-info-compact {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.role-name-compact {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    color: #374151;
    font-size: 0.8rem;
}

.role-count-compact {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
}

.role-bar-compact {
    flex: 1;
    height: 4px;
    background: #f3f4f6;
    border-radius: 2px;
    overflow: hidden;
    margin: 0 8px;
    max-width: 60px;
}

.role-progress-compact {
    height: 100%;
    transition: width 0.3s ease;
}

.role-admin { background: linear-gradient(90deg, #dc2626, #ef4444); }
.role-programmer { background: linear-gradient(90deg, #0066ff, #33ccff); }
.role-support { background: linear-gradient(90deg, #10b981, #34d399); }

/* Compact Quick Actions */
.quick-actions-compact {
    padding: 12px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
    gap: 8px;
}

.quick-btn-compact {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.75rem;
    font-weight: 500;
}

.quick-btn-compact:hover {
    transform: translateY(-1px);
    text-decoration: none;
    color: white;
}

.quick-btn-compact i {
    font-size: 1rem;
}

.btn-blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.btn-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.btn-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); }
.btn-purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); }

/* Empty State Compact */
.empty-state-compact {
    text-align: center;
    padding: 20px;
    color: #9ca3af;
}

.empty-state-compact i {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.empty-state-compact p {
    font-size: 0.8rem;
    margin: 0;
}

/* Real-time Clock Update */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

#currentTime {
    animation: pulse 2s infinite;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-container-compact {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .content-grid-compact {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 12px;
    }
    
    .welcome-section-compact {
        flex-direction: column;
        text-align: center;
        gap: 8px;
        padding: 12px 16px;
    }
    
    .stats-container-compact {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .content-grid-compact {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .stat-card-compact {
        padding: 12px;
        min-height: 60px;
    }
    
    .stat-icon-compact {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }
    
    .stat-info-compact h3 {
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .stats-container-compact {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-compact {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Scrollbar styling */
.activity-list-compact::-webkit-scrollbar {
    width: 4px;
}

.activity-list-compact::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.activity-list-compact::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.activity-list-compact::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
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

// Add click animations to compact stat cards
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card-compact');
    
    statCards.forEach(card => {
        if (card.href) { // Only for clickable cards
            card.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(0) scale(0.98)';
            });
            
            card.addEventListener('mouseup', function() {
                this.style.transform = 'translateY(-2px) scale(1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        }
    });
});
</script>