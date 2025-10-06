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

// Get NEW tasks (created in last 24 hours and not taken yet)
$new_tasks_query = "
    SELECT COUNT(*) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.id IS NULL 
    AND t.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
";
$new_tasks_count = $koneksi->query($new_tasks_query)->fetch_assoc()['count'];

// Get recent activities dengan detail lengkap dan flag NEW - ambil semua tugas
$recent_todos = $koneksi->query("
    SELECT t.*, a.name as app_name, u.name as user_name, tk.status as taken_status, tk.date as taken_date,
           CASE 
               WHEN tk.status IS NULL THEN 'available'
               ELSE tk.status 
           END as task_status,
           CASE 
               WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tk.id IS NULL THEN 1
               ELSE 0
           END as is_new
    FROM todos t 
    LEFT JOIN apps a ON t.app_id = a.id 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN taken tk ON t.id = tk.id_todos
    ORDER BY t.created_at DESC 
    LIMIT 20
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

    <!-- Task List Section -->
    <div class="task-list-section">
        <!-- Notification Alert Inside Task List -->
        <?php if ($new_tasks_count > 0): ?>
        <div class="notification-alert-inline">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="notification-content">
                <strong>Ada <?= $new_tasks_count ?> tugas baru!</strong>
                <span>Tugas baru menunggu untuk dikerjakan.</span>
            </div>
            <a href="?page=todos" class="notification-action">
                Lihat Tugas
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="task-list-header">
            <h2>
                <i class="fas fa-list-ul"></i>
                Daftar Tugas
            </h2>
            <div class="task-list-info">
                <span class="total-tasks"><?= $total_todos ?> Total</span>
                <?php if ($new_tasks_count > 0): ?>
                <span class="new-tasks-badge"><?= $new_tasks_count ?> Baru</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="task-list-container">
            <?php
            // Reset pointer untuk loop kedua
            $recent_todos->data_seek(0);
            if ($recent_todos->num_rows > 0): 
            ?>
                <?php while($todo = $recent_todos->fetch_assoc()): ?>
                <div class="task-item <?= $todo['is_new'] == 1 ? 'task-new' : '' ?>">
                    <div class="task-priority-indicator priority-<?= $todo['priority'] ?>">
                        <i class="fas fa-<?= getPriorityIcon($todo['priority']) ?>"></i>
                    </div>
                    
                    <div class="task-content">
                        <div class="task-header-row">
                            <div class="task-title-group">
                                <h3 class="task-title"><?= htmlspecialchars($todo['title']) ?></h3>
                                <?php if ($todo['is_new'] == 1): ?>
                                <span class="badge-new-large">NEW</span>
                                <?php endif; ?>
                            </div>
                            <div class="task-status-badge status-<?= $todo['task_status'] ?>">
                                <i class="fas fa-<?= $todo['task_status'] == 'done' ? 'check-circle' : ($todo['task_status'] == 'in_progress' ? 'clock' : 'circle') ?>"></i>
                                <span>
                                    <?= $todo['task_status'] == 'done' ? 'Selesai' : ($todo['task_status'] == 'in_progress' ? 'Dikerjakan' : 'Tersedia') ?>
                                </span>
                            </div>
                        </div>
                        
                        <p class="task-description">
                            <?= htmlspecialchars(substr($todo['description'], 0, 150)) ?>
                            <?= strlen($todo['description']) > 150 ? '...' : '' ?>
                        </p>
                        
                        <div class="task-meta-info">
                            <div class="task-meta-item">
                                <i class="fas fa-user-circle"></i>
                                <span class="meta-label">Dibuat oleh:</span>
                                <span class="meta-value"><?= htmlspecialchars($todo['user_name']) ?></span>
                            </div>
                            <div class="task-meta-item">
                                <i class="fas fa-cube"></i>
                                <span class="meta-label">Aplikasi:</span>
                                <span class="meta-value"><?= htmlspecialchars($todo['app_name']) ?></span>
                            </div>
                            <div class="task-meta-item">
                                <i class="fas fa-flag"></i>
                                <span class="meta-label">Prioritas:</span>
                                <span class="meta-value priority-text-<?= $todo['priority'] ?>">
                                    <?= ucfirst($todo['priority']) ?>
                                </span>
                            </div>
                            <div class="task-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="meta-label">Dibuat:</span>
                                <span class="meta-value"><?= date('d M Y, H:i', strtotime($todo['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="task-actions">
                        <a href="?page=todos" class="task-action-btn">
                            <i class="fas fa-eye"></i>
                            <span>Lihat Detail</span>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state-large">
                    <i class="fas fa-inbox"></i>
                    <h3>Tidak Ada Tugas</h3>
                    <p>Belum ada tugas yang tersedia saat ini</p>
                    <a href="?page=todos" class="btn-empty-action">
                        <i class="fas fa-plus"></i>
                        Tambah Tugas Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($recent_todos->num_rows > 0): ?>
        <div class="task-list-footer">
            <a href="?page=todos" class="btn-view-all-tasks">
                <i class="fas fa-th-list"></i>
                Lihat Semua Tugas
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
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
        'support' => 'fas fa-headset',
        'client' => 'fas fa-briefcase'
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

/* Notification Alert Styles */
.notification-alert {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
    animation: slideInDown 0.5s ease, pulse 2s infinite;
    position: relative;
    overflow: hidden;
}

.notification-alert::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: shine 3s infinite;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
    }
    50% {
        box-shadow: 0 4px 30px rgba(239, 68, 68, 0.5);
    }
}

@keyframes shine {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}

.notification-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    backdrop-filter: blur(10px);
    animation: bellRing 1s ease infinite;
}

@keyframes bellRing {
    0%, 100% {
        transform: rotate(0deg);
    }
    10%, 30% {
        transform: rotate(-10deg);
    }
    20%, 40% {
        transform: rotate(10deg);
    }
}

.notification-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notification-content strong {
    font-size: 1rem;
    font-weight: 600;
}

.notification-content span {
    font-size: 0.85rem;
    opacity: 0.95;
}

.notification-action {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.notification-action:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(4px);
    text-decoration: none;
    color: white;
}

/* NEW Badge Styles */
.badge-new {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 6px;
    animation: newBadgePulse 2s infinite;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
}

@keyframes newBadgePulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 2px 12px rgba(239, 68, 68, 0.6);
    }
}

.badge-new-header {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 700;
    margin-left: 8px;
    animation: newBadgePulse 2s infinite;
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

/* Task List Section */
.task-list-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.task-list-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
}

.task-list-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.task-list-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.total-tasks {
    background: #f3f4f6;
    color: #6b7280;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.new-tasks-badge {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    animation: newBadgePulse 2s infinite;
}

.task-list-container {
    padding: 16px 24px;
    max-height: 600px;
    overflow-y: auto;
}

.task-list-container::-webkit-scrollbar {
    width: 6px;
}

.task-list-container::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.task-list-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.task-list-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Task Item */
.task-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}

.task-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.task-item.task-new {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.03), rgba(220, 38, 38, 0.01));
    border-left: 4px solid #ef4444;
    border-color: #fecaca;
}

.task-item.task-new:hover {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.02));
    border-color: #fca5a5;
}

.task-priority-indicator {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.task-content {
    flex: 1;
    min-width: 0;
}

.task-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 16px;
}

.task-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.task-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.4;
}

.badge-new-large {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: newBadgePulse 2s infinite;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
}

.task-status-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    flex-shrink: 0;
}

.task-status-badge.status-available {
    background: linear-gradient(135deg, #6b7280, #9ca3af);
}

.task-status-badge.status-in_progress {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.task-status-badge.status-done {
    background: linear-gradient(135deg, #10b981, #059669);
}

.task-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 16px;
}

.task-meta-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.task-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
}

.task-meta-item i {
    width: 18px;
    color: #9ca3af;
    font-size: 0.9rem;
}

.meta-label {
    font-weight: 500;
    color: #9ca3af;
}

.meta-value {
    color: #374151;
    font-weight: 500;
}

.priority-text-high {
    color: #ef4444;
    font-weight: 700;
}

.priority-text-medium {
    color: #f59e0b;
    font-weight: 700;
}

.priority-text-low {
    color: #10b981;
    font-weight: 700;
}

.task-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}

.task-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.task-action-btn:hover {
    background: linear-gradient(135deg, #5568d3, #6a4091);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

/* Empty State */
.empty-state-large {
    text-align: center;
    padding: 60px 40px;
    color: #9ca3af;
}

.empty-state-large i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #d1d5db;
}

.empty-state-large h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.empty-state-large p {
    font-size: 0.95rem;
    margin-bottom: 24px;
}

.btn-empty-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-empty-action:hover {
    background: linear-gradient(135deg, #5568d3, #6a4091);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

/* Task List Footer */
.task-list-footer {
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
    text-align: center;
}

.btn-view-all-tasks {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 12px 28px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.btn-view-all-tasks:hover {
    background: linear-gradient(135deg, #5568d3, #6a4091);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

.btn-view-all-tasks i:last-child {
    transition: transform 0.3s ease;
}

.btn-view-all-tasks:hover i:last-child {
    transform: translateX(4px);
}

/* Remove old unused styles */
.content-grid-compact,
.content-card-compact,
.card-header-compact,
.view-all-compact,
.activity-list-compact,
.activity-item-compact,
.activity-icon-compact,
.activity-content-compact,
.activity-meta-compact,
.task-status-compact,
.app-performance-compact,
.app-perf-item-compact,
.app-name-compact,
.app-stats-mini-compact,
.mini-stat-compact,
.progress-bar-mini-compact,
.progress-fill-compact,
.user-distribution-compact,
.role-item-compact,
.role-info-compact,
.role-name-compact,
.role-count-compact,
.role-bar-compact,
.role-progress-compact,
.quick-actions-compact,
.quick-btn-compact,
.empty-state-compact {
    display: none !important;
}

/* Responsive Task List */
@media (max-width: 768px) {
    .task-list-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .task-item {
        flex-direction: column;
        gap: 16px;
    }
    
    .task-priority-indicator {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .task-header-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .task-meta-info {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .task-actions {
        width: 100%;
    }
    
    .task-action-btn {
        width: 100%;
    }
    
    .task-list-container {
        max-height: none;
        padding: 12px 16px;
    }
}

@media (max-width: 480px) {
    .task-list-header {
        padding: 16px;
    }
    
    .task-list-header h2 {
        font-size: 1.1rem;
    }
    
    .task-item {
        padding: 16px;
    }
    
    .task-title {
        font-size: 1rem;
    }
    
    .badge-new-large {
        font-size: 0.65rem;
        padding: 3px 8px;
    }
}bottom: 4px;
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
.role-client { background: linear-gradient(90deg, #7c3aed, #a855f7); }

/* Compact Quick Actions */


/* Real-time Clock Update */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 rgba(239, 68, 68, 0);
    }
    50% {
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
    }
}

@keyframes shine {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}

.notification-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    backdrop-filter: blur(10px);
    animation: bellRing 1s ease infinite;
}

@keyframes bellRing {
    0%, 100% {
        transform: rotate(0deg);
    }
    10%, 30% {
        transform: rotate(-10deg);
    }
    20%, 40% {
        transform: rotate(10deg);
    }
}

.notification-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notification-content strong {
    font-size: 1rem;
    font-weight: 600;
}

.notification-content span {
    font-size: 0.85rem;
    opacity: 0.95;
}

.notification-action {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.notification-action:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(4px);
    text-decoration: none;
    color: white;
}

@keyframes newBadgePulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 2px 12px rgba(239, 68, 68, 0.6);
    }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-container-compact {
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
    
    .notification-alert {
        flex-direction: column;
        text-align: center;
        gap: 12px;
        padding: 16px;
    }
    
    .notification-icon {
        margin: 0 auto;
    }
    
    .notification-action {
        width: 100%;
        justify-content: center;
    }
    
    .stats-container-compact {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
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
    
    .task-list-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .task-item {
        flex-direction: column;
        gap: 16px;
    }
    
    .task-priority-indicator {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .task-header-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .task-meta-info {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .task-actions {
        width: 100%;
    }
    
    .task-action-btn {
        width: 100%;
    }
    
    .task-list-container {
        max-height: none;
        padding: 12px 16px;
    }
}

@media (max-width: 480px) {
    .stats-container-compact {
        grid-template-columns: 1fr;
    }
    
    .notification-alert {
        padding: 12px;
    }
    
    .notification-content strong {
        font-size: 0.9rem;
    }
    
    .notification-content span {
        font-size: 0.75rem;
    }
    
    .task-list-header {
        padding: 16px;
    }
    
    .task-list-header h2 {
        font-size: 1.1rem;
    }
    
    .task-item {
        padding: 16px;
    }
    
    .task-title {
        font-size: 1rem;
    }
    
    .badge-new-large {
        font-size: 0.65rem;
        padding: 3px 8px;
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

/* Special styling for items with NEW badge */
.activity-item-compact:has(.badge-new) {
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.02));
    border-radius: 8px;
    padding: 10px 8px;
    margin: 4px 0;
    border-left: 3px solid #ef4444;
}

.activity-item-compact:has(.badge-new):hover {
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.08), rgba(220, 38, 38, 0.04));
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
        if (card.href) {
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
    
    // Add notification sound effect (optional)
    const notificationAlert = document.querySelector('.notification-alert');
    if (notificationAlert) {
        // Play subtle notification animation on load
        setTimeout(() => {
            notificationAlert.style.animation = 'slideInDown 0.5s ease, pulse 2s infinite';
        }, 500);
    }
    
    // Auto-refresh new task count every 5 minutes
    setInterval(function() {
        // You can add AJAX call here to check for new tasks without page reload
        // For now, we'll just add a visual pulse to remind users
        const badge = document.querySelector('.badge-new-header');
        if (badge) {
            badge.style.animation = 'none';
            setTimeout(() => {
                badge.style.animation = 'newBadgePulse 2s infinite';
            }, 10);
        }
    }, 300000); // 5 minutes
});

// Add smooth scroll to notification action
document.querySelectorAll('.notification-action').forEach(link => {
    link.addEventListener('click', function(e) {
        // Let the link navigate normally, but add a subtle animation
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateX(4px)';
        }, 100);
    });
});

// Optional: Add desktop notification support
if ('Notification' in window && Notification.permission === 'granted') {
    const newTasksCount = document.querySelector('.badge-new-header');
    if (newTasksCount && parseInt(newTasksCount.textContent) > 0) {
        // You can uncomment this to enable desktop notifications
        // new Notification('Tugas Baru!', {
        //     body: `Ada ${newTasksCount.textContent} tugas baru menunggu`,
        //     icon: 'path/to/icon.png',
        //     badge: 'path/to/badge.png'
        // });
    }
}

// Request notification permission on first load (optional)
// if ('Notification' in window && Notification.permission === 'default') {
//     Notification.requestPermission();
// }
</script>