<?php
// Kegunaan: Monitor login, logout, perubahan data, error logs, security logs
// Create logs table if not exists
$koneksi->query("
    CREATE TABLE IF NOT EXISTS system_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(100),
        table_name VARCHAR(50),
        record_id INT,
        old_data TEXT,
        new_data TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");

// Add sample logs for demonstration
if (isset($_GET['add_sample'])) {
    $sample_logs = [
        ['user_id' => 1, 'action' => 'LOGIN', 'ip_address' => '192.168.1.100'],
        ['user_id' => 2, 'action' => 'CREATE', 'table_name' => 'todos', 'record_id' => 1],
        ['user_id' => 1, 'action' => 'UPDATE', 'table_name' => 'users', 'record_id' => 2],
        ['user_id' => 3, 'action' => 'DELETE', 'table_name' => 'apps', 'record_id' => 1],
        ['user_id' => 2, 'action' => 'LOGOUT', 'ip_address' => '192.168.1.101']
    ];
    
    foreach($sample_logs as $log) {
        $stmt = $koneksi->prepare("INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", 
            $log['user_id'], 
            $log['action'], 
            $log['table_name'] ?? null, 
            $log['record_id'] ?? null, 
            $log['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        $stmt->execute();
    }
    header('Location: ?page=logs');
    exit;
}

// Pagination
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 20;
$offset = ($page_num - 1) * $limit;

// Filters
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if ($action_filter) {
    $where_conditions[] = "l.action = ?";
    $params[] = $action_filter;
    $param_types .= 's';
}

if ($user_filter) {
    $where_conditions[] = "l.user_id = ?";
    $params[] = $user_filter;
    $param_types .= 'i';
}

if ($date_filter) {
    $where_conditions[] = "DATE(l.created_at) = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get logs with user info
$logs_query = "
    SELECT l.*, u.name as user_name, u.email as user_email
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    $where_clause
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
";

if ($params) {
    $stmt = $koneksi->prepare($logs_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $logs_result = $stmt->get_result();
} else {
    $logs_result = $koneksi->query($logs_query);
}

// Count total logs
$count_query = "SELECT COUNT(*) as total FROM system_logs l LEFT JOIN users u ON l.user_id = u.id $where_clause";
if ($params) {
    $stmt = $koneksi->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $total_logs = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_logs = $koneksi->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_logs / $limit);

// Get statistics
$today_logs = $koneksi->query("SELECT COUNT(*) as count FROM system_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$login_count = $koneksi->query("SELECT COUNT(*) as count FROM system_logs WHERE action = 'LOGIN' AND DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$error_count = $koneksi->query("SELECT COUNT(*) as count FROM system_logs WHERE action LIKE '%ERROR%'")->fetch_assoc()['count'];

// Get users for filter
$users = $koneksi->query("SELECT id, name FROM users ORDER BY name");
?>

<div class="main-content" style="margin-top: 80px;">
    <div class="logs-container">
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> System Logs</h1>
            <p>Monitor aktivitas dan keamanan sistem</p>
            <a href="?page=logs&add_sample=1" class="btn btn-secondary">Add Sample Logs</a>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $today_logs ?></h3>
                    <p>Logs Hari Ini</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $login_count ?></h3>
                    <p>Login Hari Ini</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $error_count ?></h3>
                    <p>Total Errors</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $total_logs ?></h3>
                    <p>Total Logs</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="filters-form">
                <input type="hidden" name="page" value="logs">
                
                <div class="filter-group">
                    <label>Action:</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        <option value="LOGIN" <?= $action_filter == 'LOGIN' ? 'selected' : '' ?>>Login</option>
                        <option value="LOGOUT" <?= $action_filter == 'LOGOUT' ? 'selected' : '' ?>>Logout</option>
                        <option value="CREATE" <?= $action_filter == 'CREATE' ? 'selected' : '' ?>>Create</option>
                        <option value="UPDATE" <?= $action_filter == 'UPDATE' ? 'selected' : '' ?>>Update</option>
                        <option value="DELETE" <?= $action_filter == 'DELETE' ? 'selected' : '' ?>>Delete</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>User:</label>
                    <select name="user">
                        <option value="">All Users</option>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?= $date_filter ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="?page=logs" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="logs-card">
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>IP Address</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($logs_result->num_rows > 0): ?>
                            <?php while($log = $logs_result->fetch_assoc()): ?>
                            <tr class="log-row action-<?= strtolower($log['action']) ?>">
                                <td class="log-time">
                                    <div class="time-main"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                    <div class="time-date"><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td class="log-user">
                                    <div class="user-name"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></div>
                                    <div class="user-email"><?= htmlspecialchars($log['user_email'] ?? '') ?></div>
                                </td>
                                <td class="log-action">
                                    <span class="action-badge action-<?= strtolower($log['action']) ?>">
                                        <?= getActionIcon($log['action']) ?>
                                        <?= $log['action'] ?>
                                    </span>
                                </td>
                                <td class="log-table">
                                    <?= $log['table_name'] ? htmlspecialchars($log['table_name']) : '-' ?>
                                </td>
                                <td class="log-record">
                                    <?= $log['record_id'] ?: '-' ?>
                                </td>
                                <td class="log-ip">
                                    <?= htmlspecialchars($log['ip_address'] ?: '-') ?>
                                </td>
                                <td class="log-detail">
                                    <?php if($log['old_data'] || $log['new_data']): ?>
                                    <button class="btn-detail" onclick="showLogDetail(<?= $log['id'] ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php else: ?>
                                    <span class="no-detail">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-logs">
                                    <i class="fas fa-inbox"></i>
                                    <p>Tidak ada logs yang ditemukan</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=logs&p=<?= $i ?>&action=<?= $action_filter ?>&user=<?= $user_filter ?>&date=<?= $date_filter ?>" 
                       class="page-btn <?= $i == $page_num ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function getActionIcon($action) {
    $icons = [
        'LOGIN' => '<i class="fas fa-sign-in-alt"></i>',
        'LOGOUT' => '<i class="fas fa-sign-out-alt"></i>',
        'CREATE' => '<i class="fas fa-plus"></i>',
        'UPDATE' => '<i class="fas fa-edit"></i>',
        'DELETE' => '<i class="fas fa-trash"></i>',
        'ERROR' => '<i class="fas fa-exclamation-triangle"></i>'
    ];
    return $icons[$action] ?? '<i class="fas fa-info"></i>';
}
?>

<style>
.logs-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 1.8rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
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
.bg-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-info p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

.filters-card, .logs-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 24px;
}

.filters-form {
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

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    min-width: 140px;
}

.logs-table-container {
    overflow-x: auto;
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th {
    background: #f8fafc;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    font-size: 0.9rem;
}

.logs-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.85rem;
}

.log-row:hover {
    background: #f9fafb;
}

.log-time {
    min-width: 100px;
}

.time-main {
    font-weight: 600;
    color: #1f2937;
}

.time-date {
    font-size: 0.75rem;
    color: #9ca3af;
}

.user-name {
    font-weight: 500;
    color: #1f2937;
}

.user-email {
    font-size: 0.75rem;
    color: #6b7280;
}

.action-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.action-badge.action-login { background: #10b981; }
.action-badge.action-logout { background: #f59e0b; }
.action-badge.action-create { background: #3b82f6; }
.action-badge.action-update { background: #8b5cf6; }
.action-badge.action-delete { background: #ef4444; }
.action-badge.action-error { background: #dc2626; }

.btn-detail {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    color: #6b7280;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.3s ease;
}

.btn-detail:hover {
    background: #f3f4f6;
    color: #374151;
}

.no-detail, .no-logs {
    text-align: center;
    color: #9ca3af;
}

.no-logs {
    padding: 40px;
}

.no-logs i {
    font-size: 2rem;
    margin-bottom: 8px;
    display: block;
}

.pagination {
    padding: 20px;
    display: flex;
    justify-content: center;
    gap: 8px;
}

.page-btn {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.3s ease;
}

.page-btn:hover {
    background: #f3f4f6;
    text-decoration: none;
}

.page-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

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

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
    text-decoration: none;
}

/* Log Detail Modal */
.log-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    padding: 20px;
}

.log-modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
}

.modal-body {
    padding: 20px;
}

.data-comparison {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.data-section {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
}

.data-section h4 {
    margin-bottom: 8px;
    color: #374151;
    font-size: 0.9rem;
}

.data-content {
    background: white;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    font-family: monospace;
    font-size: 0.8rem;
    white-space: pre-wrap;
    max-height: 200px;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex: 1;
    }
    
    .data-comparison {
        grid-template-columns: 1fr;
    }
    
    .logs-table {
        font-size: 0.8rem;
    }
    
    .logs-table th,
    .logs-table td {
        padding: 8px 12px;
    }
}
</style>

<script>
function showLogDetail(logId) {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'log-modal show';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Log Detail</h3>
                <button onclick="this.closest('.log-modal').remove()" style="float: right; background: none; border: none; font-size: 1.2rem;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="loading">Loading...</div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Fetch log details (in real app, this would be AJAX call)
    setTimeout(() => {
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="data-comparison">
                <div class="data-section">
                    <h4>Data Sebelumnya:</h4>
                    <div class="data-content">{"name": "Old App Name", "description": "Old description"}</div>
                </div>
                <div class="data-section">
                    <h4>Data Sesudahnya:</h4>
                    <div class="data-content">{"name": "New App Name", "description": "New description updated"}</div>
                </div>
            </div>
            <div style="margin-top: 16px;">
                <strong>User Agent:</strong><br>
                <small style="color: #6b7280;">Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36</small>
            </div>
        `;
    }, 500);
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Auto refresh logs every 30 seconds
setInterval(() => {
    if (window.location.search.includes('page=logs')) {
        // In real app, this would update the table via AJAX
        console.log('Auto refreshing logs...');
    }
}, 30000);
</script>