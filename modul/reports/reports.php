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

// Daily Activity (Last 7 days)
$daily_activity = $koneksi->query("
    SELECT DATE(t.created_at) as date,
           COUNT(*) as todos_created,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as todos_completed
    FROM todos t
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(t.created_at)
    ORDER BY date ASC
");

// System Health Metrics
$system_health = [
    'database_size' => $koneksi->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size_mb 
        FROM information_schema.tables 
        WHERE table_schema = 'appstodos'
    ")->fetch_assoc()['size_mb'] ?? 0,
    'active_users' => $koneksi->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM system_logs 
        WHERE action = 'LOGIN' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch_assoc()['count'] ?? 0,
    'error_count' => $koneksi->query("
        SELECT COUNT(*) as count 
        FROM system_logs 
        WHERE action LIKE '%ERROR%' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch_assoc()['count'] ?? 0,
    'total_apps' => $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'] ?? 0
];

// Status Distribution
$status_distribution = $koneksi->query("
    SELECT IFNULL(tk.status, 'pending') as status,
           COUNT(*) as count
    FROM todos t
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE t.created_at BETWEEN '$date_from' AND '$date_to 23:59:59'
    GROUP BY IFNULL(tk.status, 'pending')
");
?>

<div class="main-content" style="margin-top: 80px;">
    <div class="reports-container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Development Reports</h1>
            <p>Analisis performa dan metrik pengembangan aplikasi</p>
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
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
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
                    <p>System Errors (7d)</p>
                </div>
            </div>

            <div class="health-card">
                <div class="health-icon bg-purple">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="health-info">
                    <h3><?= $system_health['total_apps'] ?></h3>
                    <p>Total Apps</p>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="reports-grid">
            <!-- App Performance Chart -->
            <div class="report-card large">
                <div class="card-header">
                    <h3><i class="fas fa-chart-column"></i> App Performance</h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="appPerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Priority Distribution -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Priority Distribution</h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="priorityChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <?php 
                        $priority_stats->data_seek(0);
                        $colors = ['#ef4444', '#f59e0b', '#10b981'];
                        $i = 0;
                        while($row = $priority_stats->fetch_assoc()): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: <?= $colors[$i] ?>"></div>
                            <span><?= ucfirst($row['priority']) ?> (<?= $row['total'] ?>)</span>
                        </div>
                        <?php $i++; endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-donut"></i> Status Distribution</h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends -->
            <div class="report-card large">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Monthly Trends</h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Daily Activity -->
            <div class="report-card large">
                <div class="card-header">
                    <h3><i class="fas fa-chart-area"></i> Daily Activity (Last 7 Days)</h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Developer Productivity -->
            <div class="report-card">
                <div class="card-header">
                    <h3><i class="fas fa-users-cog"></i> Developer Productivity</h3>
                </div>
                <div class="card-content">
                    <?php if($dev_productivity->num_rows > 0): ?>
                    <div class="dev-list">
                        <?php while($dev = $dev_productivity->fetch_assoc()): ?>
                        <div class="dev-item">
                            <div class="dev-info">
                                <span class="dev-name"><?= htmlspecialchars($dev['developer_name']) ?></span>
                                <div class="dev-stats">
                                    <span class="stat completed"><?= $dev['completed_tasks'] ?> Done</span>
                                    <span class="stat active"><?= $dev['active_tasks'] ?> Active</span>
                                    <span class="stat time"><?= $dev['avg_completion_time'] ?? 0 ?>d Avg</span>
                                </div>
                            </div>
                            <div class="score-circle">
                                <?= $dev['completed_tasks'] ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-data">No developer data available for selected period</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- App Details Table -->
            <div class="report-card large">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> App Details</h3>
                </div>
                <div class="card-content">
                    <?php if($app_performance->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>App Name</th>
                                    <th>Total Tasks</th>
                                    <th>Completed</th>
                                    <th>In Progress</th>
                                    <th>High Priority</th>
                                    <th>Avg Days</th>
                                    <th>Completion Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $app_performance->data_seek(0);
                                while($app = $app_performance->fetch_assoc()): 
                                $completion_rate = $app['total_todos'] > 0 ? round(($app['completed'] / $app['total_todos']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($app['app_name']) ?></strong></td>
                                    <td><?= $app['total_todos'] ?></td>
                                    <td><span class="badge badge-success"><?= $app['completed'] ?></span></td>
                                    <td><span class="badge badge-warning"><?= $app['in_progress'] ?></span></td>
                                    <td><span class="badge badge-danger"><?= $app['high_priority_count'] ?></span></td>
                                    <td><?= $app['avg_completion_days'] ?? '-' ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $completion_rate ?>%"></div>
                                            <span><?= $completion_rate ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="no-data">No app performance data available for selected period</div>
                    <?php endif; ?>
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
    margin-bottom: 32px;
    text-align: center;
    padding: 20px 0;
}

.page-header h1 {
    font-size: 2.2rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 8px;
    font-weight: 700;
}

.page-header p {
    color: #6b7280;
    font-size: 1.1rem;
    margin: 0;
}

.filter-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 32px;
    border: 1px solid #e2e8f0;
}

.date-filter {
    padding: 24px;
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
}

.filter-group input {
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.filter-group input:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.health-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.health-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    border: 1px solid #e2e8f0;
    transition: transform 0.3s ease;
}

.health-card:hover {
    transform: translateY(-2px);
}

.health-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
}

.bg-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.bg-green { background: linear-gradient(135deg, #10b981, #059669); }
.bg-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
.bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.bg-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.health-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.health-info p {
    font-size: 0.95rem;
    color: #6b7280;
    margin: 0;
    font-weight: 500;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.report-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.report-card.large {
    grid-column: span 2;
}

.card-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-bottom: 1px solid #e2e8f0;
}

.card-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-content {
    padding: 24px;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 16px;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 3px;
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
    padding: 16px;
    border-radius: 8px;
    background: #f9fafb;
    border: 1px solid #f3f4f6;
}

.dev-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
    display: block;
    font-size: 1rem;
}

.dev-stats {
    display: flex;
    gap: 8px;
    font-size: 0.8rem;
}

.stat {
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 500;
}

.stat.completed { background: #dcfce7; color: #166534; }
.stat.active { background: #fef3c7; color: #d97706; }
.stat.time { background: #e0e7ff; color: #3730a3; }

.score-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.data-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.data-table tr:hover {
    background: #f9fafb;
}

.badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success { background: #dcfce7; color: #166534; }
.badge-warning { background: #fef3c7; color: #d97706; }
.badge-danger { background: #fee2e2; color: #dc2626; }

.progress-bar {
    position: relative;
    height: 20px;
    background: #f3f4f6;
    border-radius: 10px;
    overflow: hidden;
    min-width: 80px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #10b981, #059669);
    transition: width 0.3s ease;
}

.progress-bar span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: 500;
    color: #1f2937;
}

.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.btn-secondary {
    background: white;
    color: #6b7280;
    border: 2px solid #d1d5db;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.no-data {
    text-align: center;
    color: #9ca3af;
    font-style: italic;
    padding: 40px;
    background: #f9fafb;
    border-radius: 8px;
}

@media (max-width: 1200px) {
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
    
    .chart-legend {
        gap: 16px;
    }
    
    .dev-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js Global Configuration
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.color = '#6b7280';

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
            label: 'Total Tasks',
            data: [<?php 
                $app_performance->data_seek(0);
                $data = [];
                while($row = $app_performance->fetch_assoc()) {
                    $data[] = $row['total_todos'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: '#3b82f6',
            borderWidth: 1
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
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: '#10b981',
            borderWidth: 1
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
            backgroundColor: 'rgba(245, 158, 11, 0.8)',
            borderColor: '#f59e0b',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                }
            },
            x: {
                grid: {
                    display: false
                }
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
            backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: [<?php 
            $status_distribution->data_seek(0);
            $labels = [];
            while($row = $status_distribution->fetch_assoc()) {
                $labels[] = "'" . ucfirst(str_replace('_', ' ', $row['status'])) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            data: [<?php 
                $status_distribution->data_seek(0);
                $data = [];
                while($row = $status_distribution->fetch_assoc()) {
                    $data[] = $row['count'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
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
            label: 'Tasks Created',
            data: [<?php 
                $monthly_trends->data_seek(0);
                $data = [];
                while($row = $monthly_trends->fetch_assoc()) {
                    $data[] = $row['todos_created'];
                }
                echo implode(',', array_reverse($data));
            ?>],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Tasks Completed',
            data: [<?php 
                $monthly_trends->data_seek(0);
                $data = [];
                while($row = $monthly_trends->fetch_assoc()) {
                    $data[] = $row['todos_completed'];
                }
                echo implode(',', array_reverse($data));
            ?>],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Daily Activity Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $daily_activity->data_seek(0);
            $labels = [];
            while($row = $daily_activity->fetch_assoc()) {
                $labels[] = "'" . date('M j', strtotime($row['date'])) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Created',
            data: [<?php 
                $daily_activity->data_seek(0);
                $data = [];
                while($row = $daily_activity->fetch_assoc()) {
                    $data[] = $row['todos_created'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: 'rgba(59, 130, 246, 0.7)',
            borderColor: '#3b82f6',
            borderWidth: 1
        }, {
            label: 'Completed',
            data: [<?php 
                $daily_activity->data_seek(0);
                $data = [];
                while($row = $daily_activity->fetch_assoc()) {
                    $data[] = $row['todos_completed'];
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: '#10b981',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

function exportReport() {
    // Simple export functionality
    const reportData = {
        period: '<?= $date_from ?> to <?= $date_to ?>',
        generated: new Date().toISOString(),
        system_health: <?= json_encode($system_health) ?>,
        summary: 'Development Reports - ' + new Date().toLocaleDateString()
    };
    
    // Create downloadable JSON file
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(reportData, null, 2));
    const downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "development_report_" + new Date().toISOString().split('T')[0] + ".json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
    
    // You can replace this with actual PDF/Excel export functionality
    setTimeout(() => {
        alert('Report exported successfully! You can integrate this with libraries like jsPDF or xlsx for PDF/Excel export.');
    }, 100);
}

// Auto refresh data every 5 minutes
setInterval(() => {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000);

// Add smooth animations when page loads
window.addEventListener('load', () => {
    const cards = document.querySelectorAll('.health-card, .report-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>