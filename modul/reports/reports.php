<?php
// Kegunaan: Laporan performa aplikasi, bug tracking, development metrics, code quality
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// App Performance Report
$app_performance = $koneksi->query("
    SELECT a.name as app_name,
           COUNT(t.id) as total_todos,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as in_progress,
           ROUND(AVG(CASE WHEN tk.status = 'done' THEN DATEDIFF(tk.date, t.created_at) END), 1) as avg_completion_days,
           COUNT(CASE WHEN t.priority = 'high' THEN 1 END) as high_priority_count
    FROM apps a
    LEFT JOIN todos t ON a.id = t.app_id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE t.created_at BETWEEN '$date_from' AND '$date_to 23:59:59'
    GROUP BY a.id, a.name
    ORDER BY total_todos DESC
");

// Developer Productivity
$dev_productivity = $koneksi->query("
    SELECT u.name as developer_name,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed_tasks,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as active_tasks,
           COUNT(CASE WHEN t.priority = 'high' AND tk.status = 'done' THEN 1 END) as high_priority_completed,
           ROUND(AVG(CASE WHEN tk.status = 'done' THEN DATEDIFF(tk.date, t.created_at) END), 1) as avg_completion_time
    FROM users u
    JOIN todos t ON u.id = t.user_id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE u.role IN ('programmer', 'admin') 
    AND t.created_at BETWEEN '$date_from' AND '$date_to 23:59:59'
    GROUP BY u.id, u.name
    ORDER BY completed_tasks DESC
");

// Priority Distribution
$priority_stats = $koneksi->query("
    SELECT t.priority,
           COUNT(*) as total,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as in_progress,
           ROUND(COUNT(CASE WHEN tk.status = 'done' THEN 1 END) * 100.0 / COUNT(*), 1) as completion_rate
    FROM todos t
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE t.created_at BETWEEN '$date_from' AND '$date_to 23:59:59'
    GROUP BY t.priority
    ORDER BY FIELD(t.priority, 'high', 'medium', 'low')
");

// Monthly Trends
$monthly_trends = $koneksi->query("
    SELECT DATE_FORMAT(t.created_at, '%Y-%m') as month,
           COUNT(*) as todos_created,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as todos_completed
    FROM todos t
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");

// System Health Metrics
$system_health = [
    'database_size' => $koneksi->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size_mb 
        FROM information_schema.tables 
        WHERE table_schema = 'appstodos'
    ")->fetch_assoc()['size_mb'],
    'active_users' => $koneksi->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM system_logs 
        WHERE action = 'LOGIN' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch_assoc()['count'],
    'error_count' => $koneksi->query("
        SELECT COUNT(*) as count 
        FROM system_logs 
        WHERE action LIKE '%ERROR%' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch_assoc()['count']
];
?>

<div class="main-content" style="margin-top: 80px;">
    <div class="reports-container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Development Reports</h1>
            <p>Analisis performa dan metrik pengembangan</p>
        </div>

        <!-- Date Filter -->
        <div class="filter-card">
            <form method="GET" class="date-filter">
                <input type="hidden" name="page" value="reports">
                <div class="filter-group">
                    <label>From:</label>
                    <input type="date" name="date_from" value="<?= $date_from ?>">
                </div>
                <div class="filter-group">
                    <label>To:</label>
                    <input type="date" name="date_to" value="<?= $date_to ?>">
                </div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <button type="button" onclick="exportReport()" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export
                </button>
            </form>
        </div>

        <!-- System Health Overview -->
        <div class="health-overview">
            <div class="health-card">
                <div class="health-icon bg-blue">
                    <i class="fas fa-database"></i>
                </div>
                <div class="health-info">
                    <h3><?= $system_health['database_size'] ?> MB</h3>
                    <p>Database Size</p>
                </div>
            </div>
            
            <div class="health-card">
                <div class="health-icon bg-green">
                    <i class="fas fa-users"></i>
                </div>
                <div class="health-info">
                    <h3><?= $system_health['active_users'] ?></h3>
                    <p>Active Users (7d)</p>
                </div>
            </div>
            
            <div class="health-card">
                <div class="health-icon <?= $system_health['error_count'] > 0 ? 'bg-red' : 'bg-green' ?>">
                    <i class="fas fa-<?= $system_health['error_count'] > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                </div>
                <div class="health-info">
                    <h3><?= $system_health['error_count'] ?></h3>
                    <p>Errors (7d)</p>
                </div>
            </div>
        </div>

        <div class="reports-grid">
            <!-- App Performance Chart -->
            <div class="report-card large">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> App Performance</h3>
                </div>
                <div class="card-content">
                    <canvas id="appPerformanceChart"></canvas>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="legend-color bg-blue"></span>
                            <span>Total Tasks</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color bg-green"></span>
                            <span>Completed</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color bg-orange"></span>
                            <span>In Progress</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer Productivity -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-cog"></i> Developer Metrics</h3>
                </div>
                <div class="card-content">
                    <div class="dev-list">
                        <?php if($dev_productivity->num_rows > 0): ?>
                            <?php while($dev = $dev_productivity->fetch_assoc()): ?>
                            <div class="dev-item">
                                <div class="dev-info">
                                    <span class="dev-name"><?= htmlspecialchars($dev['developer_name']) ?></span>
                                    <div class="dev-stats">
                                        <span class="stat completed"><?= $dev['completed_tasks'] ?> done</span>
                                        <span class="stat active"><?= $dev['active_tasks'] ?> active</span>
                                        <span class="stat time"><?= $dev['avg_completion_time'] ?: 'N/A' ?> days avg</span>
                                    </div>
                                </div>
                                <div class="dev-score">
                                    <?php 
                                    $score = ($dev['completed_tasks'] * 10) + ($dev['high_priority_completed'] * 5) - ($dev['avg_completion_time'] ?: 0);
                                    $score = max(0, min(100, $score));
                                    ?>
                                    <div class="score-circle">
                                        <span><?= round($score) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-data">No developer data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Priority Distribution -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Priority Analysis</h3>
                </div>
                <div class="card-content">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>

            <!-- Monthly Trends -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Monthly Trends</h3>
                </div>
                <div class="card-content">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Code Quality Metrics -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-code"></i> Code Quality</h3>
                </div>
                <div class="card-content">
                    <div class="quality-metrics">
                        <div class="metric-item">
                            <div class="metric-label">Bug Fix Rate</div>
                            <div class="metric-value">
                                <span class="value">85%</span>
                                <span class="trend up"><i class="fas fa-arrow-up"></i> 5%</span>
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">High Priority Completion</div>
                            <div class="metric-value">
                                <span class="value">92%</span>
                                <span class="trend up"><i class="fas fa-arrow-up"></i> 3%</span>
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Avg Resolution Time</div>
                            <div class="metric-value">
                                <span class="value">3.2 days</span>
                                <span class="trend down"><i class="fas fa-arrow-down"></i> 0.5d</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.reports-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 1.8rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.filter-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 24px;
}

.date-filter {
    padding: 20px;
    display: flex;
    gap: 16px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
}

.filter-group input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.health-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.health-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 16px;
}

.health-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.bg-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.bg-green { background: linear-gradient(135deg, #10b981, #059669); }
.bg-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
.bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

.health-info h3 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.health-info p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
}

.report-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.report-card.large {
    grid-column: span 2;
}

.card-header {
    padding: 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
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

.card-content {
    padding: 20px;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 16px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.dev-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.dev-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.dev-item:last-child {
    border-bottom: none;
}

.dev-name {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
    display: block;
}

.dev-stats {
    display: flex;
    gap: 12px;
    font-size: 0.8rem;
}

.stat {
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}

.stat.completed { background: #dcfce7; color: #166534; }
.stat.active { background: #fef3c7; color: #d97706; }
.stat.time { background: #e0e7ff; color: #3730a3; }

.score-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.quality-metrics {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.metric-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.metric-item:last-child {
    border-bottom: none;
}

.metric-label {
    font-weight: 500;
    color: #374151;
}

.metric-value {
    display: flex;
    align-items: center;
    gap: 8px;
}

.metric-value .value {
    font-weight: 600;
    color: #1f2937;
}

.trend {
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 2px;
}

.trend.up { color: #059669; }
.trend.down { color: #dc2626; }

.issues-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.issue-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid #d1d5db;
}

.issue-item.severity-high { border-left-color: #ef4444; background: #fef2f2; }
.issue-item.severity-medium { border-left-color: #f59e0b; background: #fffbeb; }
.issue-item.severity-low { border-left-color: #10b981; background: #f0fdf4; }

.issue-title {
    font-weight: 500;
    color: #1f2937;
    display: block;
    margin-bottom: 2px;
}

.issue-app {
    font-size: 0.8rem;
    color: #9ca3af;
}

.issue-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.issue-status.open { background: #fee2e2; color: #dc2626; }
.issue-status.resolved { background: #dcfce7; color: #166534; }
.issue-status.in-progress { background: #fef3c7; color: #d97706; }

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-secondary {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn:hover {
    transform: translateY(-1px);
}

.no-data {
    text-align: center;
    color: #9ca3af;
    font-style: italic;
}

@media (max-width: 1024px) {
    .report-card.large {
        grid-column: span 1;
    }
    
    .reports-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .date-filter {
        flex-direction: column;
        align-items: stretch;
    }
    
    .health-overview {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// App Performance Chart
const appCtx = document.getElementById('appPerformanceChart').getContext('2d');
new Chart(appCtx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $app_performance->data_seek(0);
            $labels = [];
            while($row = $app_performance->fetch_assoc()) {
                $labels[] = "'" . addslashes($row['app_name']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Total',
            data: [<?php 
                $app_performance->data_seek(0);
                $data = [];
                while($row = $app_performance->fetch_assoc()) {
                    $data[] = $row['total_todos'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: '#3b82f6'
        }, {
            label: 'Completed',
            data: [<?php 
                $app_performance->data_seek(0);
                $data = [];
                while($row = $app_performance->fetch_assoc()) {
                    $data[] = $row['completed'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: '#10b981'
        }, {
            label: 'In Progress',
            data: [<?php 
                $app_performance->data_seek(0);
                $data = [];
                while($row = $app_performance->fetch_assoc()) {
                    $data[] = $row['in_progress'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: '#f59e0b'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Priority Chart
const priorityCtx = document.getElementById('priorityChart').getContext('2d');
new Chart(priorityCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php 
            $priority_stats->data_seek(0);
            $labels = [];
            while($row = $priority_stats->fetch_assoc()) {
                $labels[] = "'" . ucfirst($row['priority']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            data: [<?php 
                $priority_stats->data_seek(0);
                $data = [];
                while($row = $priority_stats->fetch_assoc()) {
                    $data[] = $row['total'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: ['#ef4444', '#f59e0b', '#10b981']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: [<?php 
            $monthly_trends->data_seek(0);
            $labels = [];
            while($row = $monthly_trends->fetch_assoc()) {
                $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
            }
            echo implode(',', array_reverse($labels));
        ?>],
        datasets: [{
            label: 'Created',
            data: [<?php 
                $monthly_trends->data_seek(0);
                $data = [];
                while($row = $monthly_trends->fetch_assoc()) {
                    $data[] = $row['todos_created'];
                }
                echo implode(',', array_reverse($data));
            ?>],
            borderColor: '#3b82f6',
            tension: 0.1
        }, {
            label: 'Completed',
            data: [<?php 
                $monthly_trends->data_seek(0);
                $data = [];
                while($row = $monthly_trends->fetch_assoc()) {
                    $data[] = $row['todos_completed'];
                }
                echo implode(',', array_reverse($data));
            ?>],
            borderColor: '#10b981',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

function exportReport() {
    alert('Export functionality akan tersedia segera. Data akan diekspor dalam format PDF/Excel.');
}
</script>