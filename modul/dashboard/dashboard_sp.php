<?php
// Get statistics data
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM taken WHERE user_id = ?) as total_taken,
        (SELECT COUNT(*) FROM taken WHERE user_id = ? AND status = 'done' AND DATE(date) = CURDATE()) as taken_today
";
$stats_stmt = $koneksi->prepare($stats_query);
$stats_stmt->bind_param('ii', $_SESSION['user_id'], $_SESSION['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get today's completed task reports (tasks marked as done today)
$reports_query = "
    SELECT tk.*, 
           td.title as todo_title,
           td.description as todo_description,
           td.priority as todo_priority,
           a.name as app_name,
           u.name as user_name,
           DATE_FORMAT(tk.date, '%Y-%m-%d') as formatted_date
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users u ON tk.user_id = u.id
    WHERE tk.user_id = ? 
    AND tk.status = 'done'
    AND DATE(tk.date) = CURDATE()
    ORDER BY tk.id DESC
    LIMIT 15
";
$reports_stmt = $koneksi->prepare($reports_query);
$reports_stmt->bind_param('i', $_SESSION['user_id']);
$reports_stmt->execute();
$reports_result = $reports_stmt->get_result();
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

/* Main Grid Layout */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    align-items: start;
}

/* Stats Cards Container */
.stats-section {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 28px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    min-height: 180px;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
    margin-bottom: 16px;
}

.stat-icon.purple {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.green {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-content {
    width: 100%;
}

.stat-label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 2.4rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
    margin-bottom: 6px;
}

.stat-meta {
    font-size: 0.8rem;
    color: #9ca3af;
}

/* Reports Section */
.reports-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    height: calc(100vh - 140px);
    min-height: 600px;
    display: flex;
    flex-direction: column;
}

.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
    flex-shrink: 0;
}

.reports-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.reports-list {
    overflow-y: auto;
    flex: 1;
    padding-right: 8px;
}

.reports-list::-webkit-scrollbar {
    width: 6px;
}

.reports-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.reports-list::-webkit-scrollbar-thumb {
    background: #0d8af5;
    border-radius: 10px;
}

.reports-list::-webkit-scrollbar-thumb:hover {
    background: #0b7ad6;
}

.report-item {
    padding: 14px 16px;
    border-left: 4px solid #27ae60;
    background: #f0fdf4;
    border-radius: 8px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    position: relative;
}

.report-item:hover {
    background: #dcfce7;
    transform: translateX(4px);
    cursor: pointer;
}

.report-item:last-child {
    margin-bottom: 0;
}

.report-status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #27ae60;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
    gap: 8px;
    padding-right: 80px;
}

.report-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
    line-height: 1.3;
}

.report-date {
    font-size: 0.78rem;
    color: #6b7280;
}

.report-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 0.82rem;
    color: #6b7280;
}

.report-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.report-meta-item i {
    width: 14px;
    font-size: 0.75rem;
}

.completion-time {
    font-size: 0.75rem;
    color: #27ae60;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
}

.no-reports {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.no-reports i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: #d1d5db;
}

.no-reports h3 {
    font-size: 1.15rem;
    margin-bottom: 8px;
    color: #6b7280;
}

.no-reports p {
    font-size: 0.9rem;
}

/* Priority Badge */
.priority-badge-dash {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 500;
    white-space: nowrap;
    flex-shrink: 0;
}

.priority-badge-dash.high {
    background: #fee;
    color: #e74c3c;
}

.priority-badge-dash.medium {
    background: #fff4e6;
    color: #f39c12;
}

.priority-badge-dash.low {
    background: #e8f5e9;
    color: #27ae60;
}

/* Responsive */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr 350px;
    }
}

@media (max-width: 968px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-section {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .reports-section {
        height: auto;
        min-height: 500px;
        max-height: 600px;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px 20px;
    }
    
    .stats-section {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 24px 20px;
        min-height: 160px;
    }
    
    .stat-icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .reports-section {
        padding: 20px;
        height: auto;
        min-height: 400px;
        max-height: 500px;
    }
    
    .report-header {
        flex-direction: column;
        gap: 8px;
        padding-right: 0;
    }
    
    .report-status-badge {
        position: static;
        align-self: flex-start;
        margin-top: 8px;
    }
    
    .priority-badge-dash {
        align-self: flex-start;
    }
}
</style>

<div class="dashboard-container">
    <!-- Main Grid: Stats on Left, Reports on Right -->
    <div class="dashboard-grid">
        <!-- Left Side: Stats Cards (2 columns) -->
        <div class="stats-section">
            <!-- Total Taken Card -->
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Taken</div>
                    <div class="stat-value"><?= $stats['total_taken'] ?></div>
                    <div class="stat-meta">Total tugas yang diambil</div>
                </div>
            </div>

            <!-- Taken Hari Ini Card -->
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Diselesaikan Hari Ini</div>
                    <div class="stat-value"><?= $stats['taken_today'] ?></div>
                    <div class="stat-meta"><?= date('d M Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Right Side: Laporan Tugas Hari Ini -->
        <div class="reports-section">
            <div class="reports-header">
                <h2 class="reports-title">Laporan Tugas Diselesaikan Hari Ini</h2>
            </div>
            
            <div class="reports-list">
                <?php if ($reports_result->num_rows > 0): ?>
                    <?php while($report = $reports_result->fetch_assoc()): ?>
                    <div class="report-item" onclick="window.location.href='?page=detail_taken&id=<?= $report['id'] ?>'">
                        <span class="report-status-badge">
                            <i class="fas fa-check-circle"></i> Selesai
                        </span>
                        <div class="report-header">
                            <div style="flex: 1; min-width: 0;">
                                <div class="report-title"><?= htmlspecialchars($report['todo_title']) ?></div>
                                <div class="report-date">Diambil: <?= date('d/m/Y', strtotime($report['created_at'])) ?></div>
                                <?php if ($report['updated_at']): ?>
                                <div class="completion-time">
                                    <i class="fas fa-clock"></i>
                                    Diselesaikan: <?= date('H:i', strtotime($report['updated_at'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="priority-badge-dash <?= strtolower($report['todo_priority']) ?>">
                                <?= ucfirst($report['todo_priority']) ?>
                            </span>
                        </div>
                        <div class="report-meta">
                            <div class="report-meta-item">
                                <i class="fas fa-cube"></i>
                                <span><?= htmlspecialchars($report['app_name']) ?></span>
                            </div>
                            <?php if ($report['catatan']): ?>
                            <div class="report-meta-item">
                                <i class="fas fa-sticky-note"></i>
                                <span><?= htmlspecialchars(substr($report['catatan'], 0, 50)) ?><?= strlen($report['catatan']) > 50 ? '...' : '' ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-reports">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>Belum Ada Laporan</h3>
                        <p>Belum ada tugas yang diselesaikan hari ini</p>
                    </div>
                <?php endif; ?>
            </div>
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
    
    // Animate reports section
    setTimeout(() => {
        const reportsSection = document.querySelector('.reports-section');
        if (reportsSection) {
            reportsSection.style.opacity = '0';
            reportsSection.style.transform = 'translateX(20px)';
            setTimeout(() => {
                reportsSection.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                reportsSection.style.opacity = '1';
                reportsSection.style.transform = 'translateX(0)';
            }, 50);
        }
    }, 200);
    
    // Animate report items
    setTimeout(() => {
        const reportItems = document.querySelectorAll('.report-item');
        reportItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(10px)';
            setTimeout(() => {
                item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 60);
        });
    }, 400);
});
</script>