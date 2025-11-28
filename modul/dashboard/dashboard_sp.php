<?php
// Get statistics data untuk support (hanya todos)
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM todos) as total_todos,
        (SELECT COUNT(*) FROM todos WHERE id NOT IN (SELECT id_todos FROM taken WHERE status != 'pending')) as pending_todos,
        (SELECT COUNT(*) FROM apps) as total_apps,
        (SELECT COUNT(*) FROM taken WHERE status = 'done' AND DATE(date) = CURDATE()) as completed_today
";
$stats_stmt = $koneksi->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get recent todos for support
$recent_todos_query = "
    SELECT td.*, 
           a.name as app_name,
           u.name as creator_name,
           DATE_FORMAT(td.created_at, '%Y-%m-%d %H:%i') as formatted_date,
           CASE 
               WHEN NOT EXISTS (SELECT 1 FROM taken WHERE id_todos = td.id) THEN 'pending'
               WHEN EXISTS (SELECT 1 FROM taken WHERE id_todos = td.id AND status = 'done') THEN 'done'
               ELSE 'taken'
           END as todo_status
    FROM todos td
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users u ON td.user_id = u.id
    ORDER BY td.created_at DESC
    LIMIT 2
";
$recent_todos_stmt = $koneksi->prepare($recent_todos_query);
$recent_todos_stmt->execute();
$recent_todos_result = $recent_todos_stmt->get_result();
?>

<style>
/* Quick Action Button in Stat Cards */
.quick-action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    text-decoration: none;
}

.quick-action-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Color variations for quick action buttons */
.quick-action-btn.btn-blue {
    background: linear-gradient(135deg, #0066ff, #33ccff);
}

.quick-action-btn.btn-blue:hover {
    background: linear-gradient(135deg, #0052cc, #2eb8e6);
}

.quick-action-btn.btn-green {
    background: linear-gradient(135deg, #10b981, #059669);
}

.quick-action-btn.btn-green:hover {
    background: linear-gradient(135deg, #0d9668, #047857);
}


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

/* Statistics Grid - 4 columns in one row */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.orange {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.cyan {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.green {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

/* Recent Todos Section - Full Width */
.todos-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 28px;
}

.todos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.todos-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.todos-title i {
    color: #0d8af5;
}

.view-all-link {
    color: #0d8af5;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s;
}

.view-all-link:hover {
    color: #0b7ad6;
}

.todos-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.todo-item {
    padding: 18px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: white;
    transition: all 0.3s ease;
    position: relative;
    cursor: pointer;
}

.todo-item:hover {
    border-color: #0d8af5;
    background: #f8fafc;
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(13, 138, 245, 0.15);
}

.todo-item-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.todo-left {
    flex: 1;
    min-width: 0;
}

.todo-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 10px;
    line-height: 1.4;
}

.todo-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.85rem;
    color: #6b7280;
}

.todo-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.todo-meta-item i {
    width: 16px;
    font-size: 0.85rem;
    color: #9ca3af;
}

.todo-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.priority-badge-dash {
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.priority-badge-dash.high {
    background: #fee2e2;
    color: #dc2626;
}

.priority-badge-dash.medium {
    background: #fef3c7;
    color: #f59e0b;
}

.priority-badge-dash.low {
    background: #d1fae5;
    color: #059669;
}

.todo-status-badge {
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.todo-status-badge.pending {
    background: #fef3c7;
    color: #f59e0b;
}

.todo-status-badge.taken {
    background: #dbeafe;
    color: #2563eb;
}

.todo-status-badge.done {
    background: #d1fae5;
    color: #059669;
}

.no-todos {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.no-todos i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: #d1d5db;
}

.no-todos h3 {
    font-size: 1.15rem;
    margin-bottom: 8px;
    color: #6b7280;
}

.no-todos p {
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
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
    
    .todo-item-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .todo-right {
        width: 100%;
        justify-content: flex-start;
    }
    
    .todo-meta {
        flex-direction: column;
        gap: 8px;
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

    <!-- Main Statistics - 4 Cards in One Row -->
    <div class="stats-grid">
        <!-- Total Apps -->
        <div class="stat-card" onclick="window.location.href='?page=apps'">
            <div class="stat-card-header">
                <div class="stat-info">
                    <h3>Total Aplikasi</h3>
                    <div class="stat-number"><?= $stats['total_apps'] ?></div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div class="stat-icon blue">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <a href="?page=tambah_apps&action=add" 
                       class="quick-action-btn btn-blue" 
                       onclick="event.stopPropagation()"
                       title="Tambah Aplikasi">
                        <i class="fas fa-plus"></i>
                    </a>
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
                    <div class="stat-number"><?= $stats['total_todos'] ?></div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div class="stat-icon green">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <?php if (in_array($_SESSION['user_role'], ['admin', 'support'])): ?>
                    <button class="quick-action-btn btn-green" 
                            onclick="event.stopPropagation(); showSelectAppModal()"
                            title="Tambah Todo">
                        <i class="fas fa-plus"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-footer">
                <i class="fas fa-arrow-right"></i>
                <span>Lihat semua tugas</span>
            </div>
        </div>

        <!-- Pending Todos Card -->
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-info">
                    <h3>Todos Pending</h3>
                    <div class="stat-number"><?= $stats['pending_todos'] ?></div>
                </div>
                <div class="stat-icon orange">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
            <div class="stat-footer">
                <i class="fas fa-clock"></i>
                <span>Menunggu diambil</span>
            </div>
        </div>

        <!-- Completed Today Card -->
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-info">
                    <h3>Diselesaikan Hari Ini</h3>
                    <div class="stat-number"><?= $stats['completed_today'] ?></div>
                </div>
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-footer">
                <i class="fas fa-calendar-day"></i>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Todos Section - Full Width -->
    <div class="todos-section">
        <div class="todos-header">
            <h2 class="todos-title">
                <i class="fas fa-clipboard-list"></i>
                Todos Terbaru
            </h2>
            <a href="?page=todos" class="view-all-link">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="todos-list">
            <?php if ($recent_todos_result->num_rows > 0): ?>
                <?php while($todo = $recent_todos_result->fetch_assoc()): ?>
                <div class="todo-item" onclick="window.location.href='?page=detail_todos&id=<?= $todo['id'] ?>'">
                    <div class="todo-item-content">
                        <div class="todo-left">
                            <div class="todo-title"><?= htmlspecialchars($todo['title']) ?></div>
                            <div class="todo-meta">
                                <div class="todo-meta-item">
                                    <i class="fas fa-cube"></i>
                                    <span><?= htmlspecialchars($todo['app_name']) ?></span>
                                </div>
                                <div class="todo-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?= htmlspecialchars($todo['creator_name']) ?></span>
                                </div>
                                <div class="todo-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y H:i', strtotime($todo['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="todo-right">
                            <span class="priority-badge-dash <?= strtolower($todo['priority']) ?>">
                                <?= ucfirst($todo['priority']) ?>
                            </span>
                            <span class="todo-status-badge <?= strtolower($todo['todo_status']) ?>">
                                <?php if ($todo['todo_status'] == 'pending'): ?>
                                    <i class="fas fa-clock"></i> Available
                                <?php elseif ($todo['todo_status'] == 'taken'): ?>
                                    <i class="fas fa-hourglass-half"></i> Taken
                                <?php else: ?>
                                    <i class="fas fa-check-circle"></i> Done
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-todos">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Belum Ada Todos</h3>
                    <p>Belum ada todo yang dibuat dalam sistem</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add animation on load
document.addEventListener('DOMContentLoaded', function() {
    // Animate stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animate todos section
    setTimeout(() => {
        const todosSection = document.querySelector('.todos-section');
        if (todosSection) {
            todosSection.style.opacity = '0';
            todosSection.style.transform = 'translateY(20px)';
            setTimeout(() => {
                todosSection.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                todosSection.style.opacity = '1';
                todosSection.style.transform = 'translateY(0)';
            }, 50);
        }
    }, 400);
    
    // Animate todo items
    setTimeout(() => {
        const todoItems = document.querySelectorAll('.todo-item');
        todoItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'scale(0.95)';
            setTimeout(() => {
                item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                item.style.opacity = '1';
                item.style.transform = 'scale(1)';
            }, index * 50);
        });
    }, 600);
});
</script>