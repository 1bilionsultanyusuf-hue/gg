<?php
// Get programming-specific statistics from database
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];
$total_programming_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];

// Get priority-based statistics
$high_priority_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'high'")->fetch_assoc()['count'];
$medium_priority_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'medium'")->fetch_assoc()['count'];
$low_priority_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'low'")->fetch_assoc()['count'];

// Get new tasks (available tasks not taken yet) - exclude current user's tasks
$new_tasks_query = "
    SELECT COUNT(*) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.id IS NULL AND t.user_id != {$_SESSION['user_id']}
";
$new_tasks = $koneksi->query($new_tasks_query)->fetch_assoc()['count'];

// Get recent new tasks from other users with detailed info
$recent_new_tasks = $koneksi->query("
    SELECT t.*, a.name as app_name, u.name as creator_name
    FROM todos t 
    LEFT JOIN apps a ON t.app_id = a.id 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE tk.id IS NULL AND t.user_id != {$_SESSION['user_id']}
    ORDER BY t.created_at DESC 
    LIMIT 6
");

// Get my active tasks statistics
$my_active_tasks = $koneksi->query("
    SELECT COUNT(*) as count 
    FROM taken tk 
    WHERE tk.user_id = {$_SESSION['user_id']} AND tk.status = 'in_progress'
")->fetch_assoc()['count'];

// Get my completed tasks this week
$my_completed_week = $koneksi->query("
    SELECT COUNT(*) as count 
    FROM taken tk 
    WHERE tk.user_id = {$_SESSION['user_id']} 
    AND tk.status = 'done' 
    AND tk.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['count'];

// Get app performance for programmers
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
    LIMIT 4
");
?>

<div class="dashboard-container">
    <!-- Programming Dashboard Welcome Section -->
    <div class="welcome-section-programming">
        <div class="welcome-content">
            <h1 class="welcome-title">Programming Dashboard</h1>
            <p class="welcome-subtitle">
                Selamat datang, <?= $_SESSION['user_name'] ?> (<?= ucfirst($_SESSION['user_role']) ?>)
            </p>
            <div class="programmer-stats">
                <span class="stat-mini">
                    <i class="fas fa-tasks"></i>
                    <?= $my_active_tasks ?> Aktif
                </span>
                <span class="stat-mini">
                    <i class="fas fa-check-circle"></i>
                    <?= $my_completed_week ?> Selesai Minggu Ini
                </span>
            </div>
        </div>
        <div class="welcome-actions">
            <a href="?page=taken" class="quick-action-btn">
                <i class="fas fa-hand-paper"></i>
                Ambil Tugas
            </a>
            <div class="date-time-info">
                <i class="fas fa-calendar-alt"></i>
                <span><?= date('d M Y') ?> • <span id="currentTime"><?= date('H:i') ?></span></span>
            </div>
        </div>
    </div>

    <!-- Programming Statistics Cards -->
    <div class="stats-container-programming">
        <div class="stat-card-programming stat-new-tasks">
            <div class="stat-icon-programming">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="stat-info-programming">
                <h3><?= $new_tasks ?></h3>
                <p>Tugas Baru</p>
                <span class="stat-desc">Tersedia untuk diambil</span>
            </div>
        </div>

        <div class="stat-card-programming stat-high-priority">
            <div class="stat-icon-programming">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info-programming">
                <h3><?= $high_priority_todos ?></h3>
                <p>High Priority</p>
                <span class="stat-desc">Perlu perhatian segera</span>
            </div>
        </div>

        <div class="stat-card-programming stat-medium-priority">
            <div class="stat-icon-programming">
                <i class="fas fa-minus-circle"></i>
            </div>
            <div class="stat-info-programming">
                <h3><?= $medium_priority_todos ?></h3>
                <p>Medium Priority</p>
                <span class="stat-desc">Prioritas sedang</span>
            </div>
        </div>

        <div class="stat-card-programming stat-low-priority">
            <div class="stat-icon-programming">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-info-programming">
                <h3><?= $low_priority_todos ?></h3>
                <p>Low Priority</p>
                <span class="stat-desc">Dapat dikerjakan nanti</span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid-programming">
        <!-- New Tasks Available -->
        <div class="content-card-programming">
            <div class="card-header-programming">
                <h3>
                    <i class="fas fa-inbox"></i>
                    Tugas Baru Tersedia
                </h3>
                <a href="?page=taken" class="view-all-programming">Lihat Semua</a>
            </div>
            <div class="new-tasks-list">
                <?php if ($recent_new_tasks->num_rows > 0): ?>
                    <?php while($task = $recent_new_tasks->fetch_assoc()): ?>
                    <div class="task-item-programming priority-<?= $task['priority'] ?>">
                        <div class="task-priority-indicator priority-<?= $task['priority'] ?>">
                            <i class="fas fa-<?= getPriorityIcon($task['priority']) ?>"></i>
                        </div>
                        <div class="task-content-programming">
                            <h4 class="task-title-programming"><?= htmlspecialchars($task['title']) ?></h4>
                            <p class="task-description-programming">
                                <?= htmlspecialchars(substr($task['description'], 0, 80)) ?>
                                <?= strlen($task['description']) > 80 ? '...' : '' ?>
                            </p>
                            <div class="task-meta-programming">
                                <span class="meta-item-programming">
                                    <i class="fas fa-user"></i>
                                    Dari: <?= htmlspecialchars($task['creator_name']) ?>
                                </span>
                                <span class="meta-item-programming">
                                    <i class="fas fa-cube"></i>
                                    <?= htmlspecialchars($task['app_name']) ?>
                                </span>
                                <span class="meta-item-programming">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m H:i', strtotime($task['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="task-action-programming">
                            <form method="POST" action="?page=taken" style="display: inline;">
                                <input type="hidden" name="todo_id" value="<?= $task['id'] ?>">
                                <button type="submit" name="take_task" class="btn-take-task">
                                    <i class="fas fa-hand-paper"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state-programming">
                        <i class="fas fa-check-double"></i>
                        <p>Tidak ada tugas baru saat ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- App Performance -->
        <div class="content-card-programming">
            <div class="card-header-programming">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    Performa Aplikasi
                </h3>
                <a href="?page=apps" class="view-all-programming">Detail</a>
            </div>
            <div class="app-performance-programming">
                <?php if ($app_performance->num_rows > 0): ?>
                    <?php while($app = $app_performance->fetch_assoc()): ?>
                    <div class="app-item-programming">
                        <div class="app-header-programming">
                            <h4><?= htmlspecialchars($app['name']) ?></h4>
                            <span class="app-total"><?= $app['total_todos'] ?> tugas</span>
                        </div>
                        <div class="app-stats-programming">
                            <div class="app-stat completed">
                                <i class="fas fa-check"></i>
                                <?= $app['completed'] ?> Selesai
                            </div>
                            <div class="app-stat progress">
                                <i class="fas fa-clock"></i>
                                <?= $app['in_progress'] ?> Proses
                            </div>
                            <div class="app-stat available">
                                <i class="fas fa-inbox"></i>
                                <?= $app['available'] ?> Tersedia
                            </div>
                        </div>
                        <?php if ($app['total_todos'] > 0): ?>
                        <div class="progress-bar-programming">
                            <?php $completion_rate = ($app['completed'] / $app['total_todos']) * 100; ?>
                            <div class="progress-fill-programming" style="width: <?= $completion_rate ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state-programming">
                        <i class="fas fa-cube"></i>
                        <p>Belum ada data aplikasi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions for Programmers -->
        <div class="content-card-programming">
            <div class="card-header-programming">
                <h3>
                    <i class="fas fa-rocket"></i>
                    Aksi Cepat
                </h3>
            </div>
            <div class="quick-actions-programming">
                <a href="?page=taken" class="quick-action-programming btn-primary">
                    <i class="fas fa-hand-paper"></i>
                    <span>Ambil Tugas</span>
                    <small>Cari tugas baru</small>
                </a>
                <a href="?page=todos" class="quick-action-programming btn-secondary">
                    <i class="fas fa-plus"></i>
                    <span>Buat Tugas</span>
                    <small>Tambah todo baru</small>
                </a>
                <a href="?page=taken&filter=my_tasks" class="quick-action-programming btn-info">
                    <i class="fas fa-list-check"></i>
                    <span>Tugas Saya</span>
                    <small>Lihat progress</small>
                </a>
                <a href="?page=apps" class="quick-action-programming btn-success">
                    <i class="fas fa-cube"></i>
                    <span>Aplikasi</span>
                    <small>Kelola apps</small>
                </a>
            </div>
        </div>

        <!-- My Recent Activity -->
        <div class="content-card-programming">
            <div class="card-header-programming">
                <h3>
                    <i class="fas fa-history"></i>
                    Aktivitas Terakhir
                </h3>
            </div>
            <div class="recent-activity-programming">
                <?php 
                $my_recent_activity = $koneksi->query("
                    SELECT tk.*, t.title, t.priority, a.name as app_name
                    FROM taken tk
                    JOIN todos t ON tk.id_todos = t.id
                    JOIN apps a ON t.app_id = a.id
                    WHERE tk.user_id = {$_SESSION['user_id']}
                    ORDER BY tk.date DESC
                    LIMIT 5
                ");
                ?>
                <?php if ($my_recent_activity->num_rows > 0): ?>
                    <?php while($activity = $my_recent_activity->fetch_assoc()): ?>
                    <div class="activity-item-programming">
                        <div class="activity-status status-<?= $activity['status'] ?>">
                            <i class="fas fa-<?= $activity['status'] == 'done' ? 'check-circle' : 'clock' ?>"></i>
                        </div>
                        <div class="activity-info-programming">
                            <h4><?= htmlspecialchars($activity['title']) ?></h4>
                            <div class="activity-meta-programming">
                                <span class="priority-tag priority-<?= $activity['priority'] ?>">
                                    <?= ucfirst($activity['priority']) ?>
                                </span>
                                <span><?= htmlspecialchars($activity['app_name']) ?></span>
                                <span><?= date('d/m H:i', strtotime($activity['date'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state-programming">
                        <i class="fas fa-history"></i>
                        <p>Belum ada aktivitas</p>
                    </div>
                <?php endif; ?>
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
/* Programming Dashboard Styles */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: #f1f5f9;
    min-height: calc(100vh - 80px);
}

/* Programming Welcome Section */
.welcome-section-programming {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #06b6d4 100%);
    color: white;
    padding: 24px 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 32px rgba(30, 64, 175, 0.3);
    position: relative;
    overflow: hidden;
}

.welcome-section-programming::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(180deg); }
}

.welcome-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.welcome-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 12px;
}

.programmer-stats {
    display: flex;
    gap: 16px;
}

.stat-mini {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    background: rgba(255,255,255,0.15);
    padding: 6px 12px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.welcome-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.quick-action-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 12px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
}

.quick-action-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.date-time-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Programming Stats Cards */
.stats-container-programming {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card-programming {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.stat-card-programming:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
}

.stat-new-tasks {
    border-left-color: #10b981;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.stat-high-priority {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.stat-medium-priority {
    border-left-color: #f59e0b;
    background: linear-gradient(135deg, #fefbeb 0%, #fed7aa 100%);
}

.stat-low-priority {
    border-left-color: #6366f1;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
}

.stat-icon-programming {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-new-tasks .stat-icon-programming { background: #10b981; color: white; }
.stat-high-priority .stat-icon-programming { background: #ef4444; color: white; }
.stat-medium-priority .stat-icon-programming { background: #f59e0b; color: white; }
.stat-low-priority .stat-icon-programming { background: #6366f1; color: white; }

.stat-info-programming h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: #1f2937;
}

.stat-info-programming p {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
}

.stat-desc {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Content Grid */
.content-grid-programming {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 20px;
}

.content-card-programming {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.content-card-programming:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.card-header-programming {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.card-header-programming h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-all-programming {
    font-size: 0.85rem;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.view-all-programming:hover {
    background: #eff6ff;
    text-decoration: none;
}

/* New Tasks List */
.new-tasks-list {
    padding: 20px 24px;
    max-height: 400px;
    overflow-y: auto;
}

.task-item-programming {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.task-item-programming:hover {
    background: #f9fafb;
    margin: 0 -24px;
    padding-left: 24px;
    padding-right: 24px;
}

.task-item-programming:last-child {
    border-bottom: none;
}

.task-priority-indicator {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.task-priority-indicator.priority-high { background: #ef4444; }
.task-priority-indicator.priority-medium { background: #f59e0b; }
.task-priority-indicator.priority-low { background: #10b981; }

.task-content-programming {
    flex: 1;
    min-width: 0;
}

.task-title-programming {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
    line-height: 1.3;
}

.task-description-programming {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 8px;
    line-height: 1.4;
}

.task-meta-programming {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.meta-item-programming {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #9ca3af;
}

.meta-item-programming i {
    font-size: 0.7rem;
}

.task-action-programming {
    flex-shrink: 0;
}

.btn-take-task {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-take-task:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* App Performance */
.app-performance-programming {
    padding: 20px 24px;
}

.app-item-programming {
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}

.app-item-programming:last-child {
    border-bottom: none;
}

.app-header-programming {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.app-header-programming h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.app-total {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

.app-stats-programming {
    display: flex;
    gap: 16px;
    margin-bottom: 8px;
}

.app-stat {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 12px;
}

.app-stat.completed { background: #dcfce7; color: #059669; }
.app-stat.progress { background: #fed7aa; color: #ea580c; }
.app-stat.available { background: #e0e7ff; color: #4338ca; }

.progress-bar-programming {
    height: 4px;
    background: #f1f5f9;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill-programming {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.5s ease;
}

/* Quick Actions */
.quick-actions-programming {
    padding: 20px 24px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
}

.quick-action-programming {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 12px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.quick-action-programming:hover {
    transform: translateY(-2px);
    text-decoration: none;
}

.quick-action-programming i {
    font-size: 1.5rem;
    margin-bottom: 4px;
}

.quick-action-programming span {
    font-weight: 600;
    font-size: 0.9rem;
}

.quick-action-programming small {
    font-size: 0.75rem;
    opacity: 0.8;
}

.btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
.btn-secondary { background: linear-gradient(135deg, #64748b, #475569); color: white; }
.btn-info { background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; }
.btn-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }

/* Recent Activity */
.recent-activity-programming {
    padding: 20px 24px;
}

.activity-item-programming {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.activity-item-programming:last-child {
    border-bottom: none;
}

.activity-status {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.activity-status.status-done { background: #10b981; }
.activity-status.status-in_progress { background: #f59e0b; }

.activity-info-programming {
    flex: 1;
}

.activity-info-programming h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.activity-meta-programming {
    display: flex;
    gap: 12px;
    align-items: center;
}

.priority-tag {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 8px;
    color: white;
}

.priority-tag.priority-high { background: #ef4444; }
.priority-tag.priority-medium { background: #f59e0b; }
.priority-tag.priority-low { background: #10b981; }

.activity-meta-programming span {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Empty State */
.empty-state-programming {
    text-align: center;
    padding: 32px 20px;
    color: #9ca3af;
}

.empty-state-programming i {
    font-size: 2.5rem;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-state-programming p {
    font-size: 0.9rem;
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

/* Scrollbar styling for task lists */
.new-tasks-list::-webkit-scrollbar,
.app-performance-programming::-webkit-scrollbar,
.recent-activity-programming::-webkit-scrollbar {
    width: 4px;
}

.new-tasks-list::-webkit-scrollbar-track,
.app-performance-programming::-webkit-scrollbar-track,
.recent-activity-programming::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.new-tasks-list::-webkit-scrollbar-thumb,
.app-performance-programming::-webkit-scrollbar-thumb,
.recent-activity-programming::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.new-tasks-list::-webkit-scrollbar-thumb:hover,
.app-performance-programming::-webkit-scrollbar-thumb:hover,
.recent-activity-programming::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-container-programming {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .content-grid-programming {
        grid-template-columns: 1fr;
    }
    
    .welcome-section-programming {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .programmer-stats {
        justify-content: center;
    }
    
    .welcome-actions {
        align-items: center;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px;
    }
    
    .welcome-section-programming {
        padding: 20px 24px;
    }
    
    .welcome-title {
        font-size: 1.5rem;
    }
    
    .stats-container-programming {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .stat-card-programming {
        padding: 20px;
    }
    
    .content-grid-programming {
        gap: 16px;
    }
    
    .card-header-programming {
        padding: 16px 20px;
    }
    
    .new-tasks-list,
    .app-performance-programming,
    .recent-activity-programming,
    .quick-actions-programming {
        padding: 16px 20px;
    }
    
    .task-item-programming:hover {
        margin: 0 -20px;
        padding-left: 20px;
        padding-right: 20px;
    }
    
    .quick-actions-programming {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .dashboard-container {
        padding: 12px;
    }
    
    .welcome-section-programming {
        padding: 16px 20px;
    }
    
    .programmer-stats {
        flex-direction: column;
        gap: 8px;
    }
    
    .stat-card-programming {
        padding: 16px;
        gap: 12px;
    }
    
    .stat-icon-programming {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }
    
    .stat-info-programming h3 {
        font-size: 1.6rem;
    }
    
    .task-meta-programming {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .app-stats-programming {
        flex-direction: column;
        gap: 8px;
    }
    
    .activity-meta-programming {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .quick-actions-programming {
        grid-template-columns: 1fr;
    }
}

/* Loading and Interaction States */
.btn-take-task:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.btn-take-task:disabled:hover {
    background: #9ca3af;
    transform: none;
    box-shadow: none;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-radius: 50%;
    border-top-color: #3b82f6;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Hover effects for cards */
.task-item-programming {
    cursor: pointer;
    border-radius: 8px;
    margin: 0 -4px;
    padding: 16px 4px;
}

.app-item-programming:hover {
    background: #f9fafb;
    border-radius: 8px;
    margin: 0 -24px;
    padding: 16px 24px;
}

.activity-item-programming:hover {
    background: #f9fafb;
    border-radius: 8px;
    margin: 0 -24px;
    padding: 12px 24px;
}

/* Additional utility classes */
.text-success { color: #10b981; }
.text-warning { color: #f59e0b; }
.text-danger { color: #ef4444; }
.text-info { color: #3b82f6; }
.text-muted { color: #6b7280; }

.bg-success-light { background: #ecfdf5; }
.bg-warning-light { background: #fffbeb; }
.bg-danger-light { background: #fef2f2; }
.bg-info-light { background: #eff6ff; }

/* Animation for new task notifications */
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.task-item-programming.new-notification {
    animation: slideInRight 0.5s ease-out;
}

/* Focus states for accessibility */
.btn-take-task:focus,
.quick-action-btn:focus,
.quick-action-programming:focus,
.view-all-programming:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
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

// Add loading state to take task buttons
document.addEventListener('DOMContentLoaded', function() {
    const takeTaskButtons = document.querySelectorAll('.btn-take-task');
    
    takeTaskButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Add loading state
            this.innerHTML = '<div class="loading-spinner"></div>';
            this.disabled = true;
            
            // Show confirmation
            const taskTitle = this.closest('.task-item-programming')
                .querySelector('.task-title-programming').textContent;
            
            if (!confirm(`Apakah Anda yakin ingin mengambil tugas "${taskTitle}"?`)) {
                e.preventDefault();
                // Reset button state
                this.innerHTML = '<i class="fas fa-hand-paper"></i>';
                this.disabled = false;
                return false;
            }
        });
    });
    
    // Add smooth scroll to quick actions
    const quickActionLinks = document.querySelectorAll('.quick-action-programming[href^="?"]');
    quickActionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add a subtle loading effect
            this.style.opacity = '0.7';
            setTimeout(() => {
                this.style.opacity = '1';
            }, 200);
        });
    });
    
    // Auto-refresh new tasks every 30 seconds (optional)
    // Uncomment if you want real-time updates
    /*
    setInterval(function() {
        // You can implement AJAX call here to refresh new tasks
        console.log('Checking for new tasks...');
    }, 30000);
    */
    
    // Add hover effects to task items
    const taskItems = document.querySelectorAll('.task-item-programming');
    taskItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Add click-to-expand functionality for task descriptions
    const taskDescriptions = document.querySelectorAll('.task-description-programming');
    taskDescriptions.forEach(desc => {
        if (desc.textContent.includes('...')) {
            desc.style.cursor = 'pointer';
            desc.title = 'Klik untuk melihat selengkapnya';
            
            desc.addEventListener('click', function() {
                // You can implement expand functionality here
                alert('Fitur expand deskripsi dapat diimplementasikan sesuai kebutuhan');
            });
        }
    });
    
    // Add performance monitoring for dashboard load time
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Programming Dashboard loaded in ${Math.round(loadTime)}ms`);
    });
});

// Function to manually refresh dashboard data
function refreshDashboard() {
    location.reload();
}

// Function to show notification for new tasks
function showNewTaskNotification(count) {
    if (count > 0) {
        const notification = document.createElement('div');
        notification.className = 'new-task-notification';
        notification.innerHTML = `
            <i class="fas fa-bell"></i>
            <span>${count} tugas baru tersedia!</span>
            <button onclick="this.parentElement.remove()">×</button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Keyboard shortcuts for programmer productivity
document.addEventListener('keydown', function(e) {
    // Alt + T = Take first available task
    if (e.altKey && e.key === 't') {
        e.preventDefault();
        const firstTakeButton = document.querySelector('.btn-take-task');
        if (firstTakeButton) {
            firstTakeButton.click();
        }
    }
    
    // Alt + R = Refresh dashboard
    if (e.altKey && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }
    
    // Alt + M = Go to My Tasks
    if (e.altKey && e.key === 'm') {
        e.preventDefault();
        window.location.href = '?page=taken&filter=my_tasks';
    }
});
</script>