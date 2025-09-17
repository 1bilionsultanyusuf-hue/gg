<?php
// reports.php - List Format CRUD Management System for Reports

// Handle CRUD operations
$action = $_GET['action'] ?? 'list';
$report_id = $_GET['id'] ?? null;
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Handle form submissions
if ($_POST) {
    switch ($_POST['action']) {
        case 'create':
            $stmt = $koneksi->prepare("INSERT INTO reports (title, description, report_type, date_from, date_to, filters, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $filters = json_encode($_POST['filters'] ?? []);
            $stmt->bind_param("ssssssi", $_POST['title'], $_POST['description'], $_POST['report_type'], $_POST['date_from'], $_POST['date_to'], $filters, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Report created successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error creating report: ' . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            header('Location: index.php?page=reports');
            exit;
            
        case 'update':
            $stmt = $koneksi->prepare("UPDATE reports SET title=?, description=?, report_type=?, date_from=?, date_to=?, filters=?, updated_at=NOW() WHERE id=?");
            $filters = json_encode($_POST['filters'] ?? []);
            $stmt->bind_param("ssssssi", $_POST['title'], $_POST['description'], $_POST['report_type'], $_POST['date_from'], $_POST['date_to'], $filters, $_POST['report_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Report updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error updating report: ' . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            header('Location: index.php?page=reports');
            exit;
            
        case 'delete':
            $stmt = $koneksi->prepare("UPDATE reports SET status='deleted', updated_at=NOW() WHERE id=?");
            $stmt->bind_param("i", $_POST['report_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Report deleted successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error deleting report: ' . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            header('Location: index.php?page=reports');
            exit;
    }
}

// Get report data for edit
$edit_report = null;
if ($action == 'edit' && $report_id) {
    $stmt = $koneksi->prepare("SELECT * FROM reports WHERE id = ? AND status != 'deleted'");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $edit_report = $stmt->get_result()->fetch_assoc();
}

// Check if reports table exists
$table_exists = false;
try {
    $result = $koneksi->query("SHOW TABLES LIKE 'reports'");
    $table_exists = $result && $result->num_rows > 0;
} catch (Exception $e) {
    $table_exists = false;
}

if (!$table_exists) {
    echo '<div class="main-content" style="margin-top: 80px;">
        <div class="setup-message">
            <div class="setup-card">
                <i class="fas fa-database"></i>
                <h2>Database Setup Required</h2>
                <p>The reports table does not exist. Please run the SQL script to create the required table.</p>
            </div>
        </div>
    </div>';
    return;
}

// Get all reports
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

$where_conditions = ["r.status != 'deleted'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter !== 'all') {
    $where_conditions[] = "r.report_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

$reports_query = "
    SELECT r.*, 
           COALESCE(u.name, 'Unknown User') as creator_name
    FROM reports r
    LEFT JOIN users u ON r.created_by = u.id
    WHERE $where_clause
    ORDER BY r.created_at DESC
";

if (!empty($params)) {
    $stmt = $koneksi->prepare($reports_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $reports = $stmt->get_result();
} else {
    $reports = $koneksi->query($reports_query);
}

// Report types for dropdown
$report_types = [
    'performance' => 'App Performance',
    'development' => 'Development Metrics', 
    'productivity' => 'Developer Productivity',
    'system' => 'System Health',
    'custom' => 'Custom Report'
];

// Helper function to get report icon
function getReportIcon($type) {
    $icons = [
        'performance' => 'chart-column',
        'development' => 'code',
        'productivity' => 'users-cog',
        'system' => 'server',
        'custom' => 'chart-bar'
    ];
    return $icons[$type] ?? 'chart-bar';
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    return $status === 'active' ? 'badge-success' : 'badge-secondary';
}
?>

<div class="main-content" style="margin-top: 80px;">
    <div class="reports-container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-chart-bar"></i> Reports Management</h1>
                    <p>Create, manage and view detailed reports</p>
                </div>
                <div class="header-actions">
                    <?php if ($action == 'list'): ?>
                    <button onclick="showCreateForm()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Report
                    </button>
                    <?php else: ?>
                    <a href="index.php?page=reports" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button onclick="this.parentElement.style.display='none';">×</button>
        </div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="filters-form">
                <input type="hidden" name="page" value="reports">
                
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search reports..." 
                           value="<?= htmlspecialchars($search) ?>" class="search-input">
                </div>
                
                <div class="filter-group">
                    <select name="status">
                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="type">
                        <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>All Types</option>
                        <?php foreach ($report_types as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $type_filter == $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filter
                </button>
                
                <a href="index.php?page=reports" class="btn btn-outline">
                    <i class="fas fa-times"></i> Clear
                </a>
            </form>
        </div>

        <!-- Reports List -->
        <div class="list-container">
            <?php if ($reports && $reports->num_rows > 0): ?>
            <div class="list-header">
                <span class="results-count"><?= $reports->num_rows ?> report(s) found</span>
            </div>
            
            <div class="reports-list">
                <?php while ($report = $reports->fetch_assoc()): ?>
                <div class="list-item">
                    <div class="item-icon">
                        <i class="fas fa-<?= getReportIcon($report['report_type']) ?>"></i>
                    </div>
                    
                    <div class="item-content">
                        <div class="item-header">
                            <h3 class="item-title"><?= htmlspecialchars($report['title']) ?></h3>
                            <span class="badge <?= getStatusBadgeClass($report['status']) ?>">
                                <?= ucfirst($report['status']) ?>
                            </span>
                        </div>
                        
                        <div class="item-meta">
                            <span class="meta-item">
                                <i class="fas fa-tag"></i>
                                <?= $report_types[$report['report_type']] ?? ucfirst($report['report_type']) ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <?= date('M j, Y', strtotime($report['date_from'])) ?> - <?= date('M j, Y', strtotime($report['date_to'])) ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($report['creator_name']) ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                <?= date('M j, Y H:i', strtotime($report['created_at'])) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($report['description'])): ?>
                        <p class="item-description"><?= htmlspecialchars($report['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-actions">
                        <a href="index.php?page=reports&action=view&id=<?= $report['id'] ?>" 
                           class="btn btn-sm btn-primary" title="View Report">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="index.php?page=reports&action=edit&id=<?= $report['id'] ?>" 
                           class="btn btn-sm btn-secondary" title="Edit Report">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteReport(<?= $report['id'] ?>)" 
                                class="btn btn-sm btn-danger" title="Delete Report">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-chart-bar"></i>
                <h3>No Reports Found</h3>
                <p>
                    <?php if (!empty($search) || $status_filter != 'all' || $type_filter != 'all'): ?>
                        No reports match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        Create your first report to get started with analytics.
                    <?php endif; ?>
                </p>
                <?php if (empty($search) && $status_filter == 'all' && $type_filter == 'all'): ?>
                <button onclick="showCreateForm()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create First Report
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($action == 'create' || $action == 'edit'): ?>
        <!-- Create/Edit Form -->
        <div class="form-container">
            <div class="form-header">
                <h2>
                    <i class="fas fa-<?= $action == 'edit' ? 'edit' : 'plus' ?>"></i>
                    <?= $action == 'edit' ? 'Edit Report' : 'Create New Report' ?>
                </h2>
            </div>

            <form method="POST" class="report-form">
                <input type="hidden" name="action" value="<?= $action == 'edit' ? 'update' : 'create' ?>">
                <?php if ($action == 'edit'): ?>
                <input type="hidden" name="report_id" value="<?= $report_id ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Report Title *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?= htmlspecialchars($edit_report['title'] ?? '') ?>"
                               placeholder="Enter report title">
                    </div>

                    <div class="form-group">
                        <label for="report_type">Report Type *</label>
                        <select id="report_type" name="report_type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($report_types as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($edit_report['report_type'] ?? '') == $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_from">Start Date *</label>
                        <input type="date" id="date_from" name="date_from" required
                               value="<?= $edit_report['date_from'] ?? date('Y-m-d', strtotime('-30 days')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_to">End Date *</label>
                        <input type="date" id="date_to" name="date_to" required
                               value="<?= $edit_report['date_to'] ?? date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" 
                              placeholder="Enter report description"><?= htmlspecialchars($edit_report['description'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $action == 'edit' ? 'Update Report' : 'Create Report' ?>
                    </button>
                    <a href="index.php?page=reports" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <?php elseif ($action == 'view'): ?>
        <!-- View Report - Simple Version -->
        <div class="view-container">
            <div class="view-header">
                <h2>Report Details</h2>
                <div class="view-actions">
                    <a href="index.php?page=reports&action=edit&id=<?= $report_id ?>" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button onclick="deleteReport(<?= $report_id ?>)" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            
            <?php
            $stmt = $koneksi->prepare("SELECT r.*, u.name as creator_name FROM reports r LEFT JOIN users u ON r.created_by = u.id WHERE r.id = ?");
            $stmt->bind_param("i", $report_id);
            $stmt->execute();
            $current_report = $stmt->get_result()->fetch_assoc();
            ?>
            
            <?php if ($current_report): ?>
            <div class="view-content">
                <div class="detail-group">
                    <label>Title:</label>
                    <span><?= htmlspecialchars($current_report['title']) ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Type:</label>
                    <span><?= $report_types[$current_report['report_type']] ?? ucfirst($current_report['report_type']) ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Period:</label>
                    <span><?= date('M j, Y', strtotime($current_report['date_from'])) ?> - <?= date('M j, Y', strtotime($current_report['date_to'])) ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Status:</label>
                    <span class="badge <?= getStatusBadgeClass($current_report['status']) ?>">
                        <?= ucfirst($current_report['status']) ?>
                    </span>
                </div>
                
                <div class="detail-group">
                    <label>Created By:</label>
                    <span><?= htmlspecialchars($current_report['creator_name']) ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Created:</label>
                    <span><?= date('M j, Y H:i', strtotime($current_report['created_at'])) ?></span>
                </div>
                
                <?php if (!empty($current_report['description'])): ?>
                <div class="detail-group">
                    <label>Description:</label>
                    <p><?= htmlspecialchars($current_report['description']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Report Not Found</h3>
                <p>The requested report does not exist or has been deleted.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
            <button onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this report? This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="report_id" id="deleteReportId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Report
                </button>
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>
</div>

<style>
.reports-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 24px;
}

.header-content {
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.header-left h1 {
    font-size: 1.8rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
    font-weight: 600;
}

.header-left p {
    color: #6b7280;
    margin: 0;
    font-size: 0.95rem;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.alert button {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: inherit;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Filters */
.filters-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    flex: 1;
    min-width: 200px;
}

.search-input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.search-input:focus,
.filter-group select:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

/* List Container */
.list-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.list-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
    border-radius: 8px 8px 0 0;
}

.results-count {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

.reports-list {
    padding: 0;
}

.list-item {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s ease;
}

.list-item:hover {
    background: #f9fafb;
}

.list-item:last-child {
    border-bottom: none;
}

.item-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    flex-shrink: 0;
}

.item-icon i {
    font-size: 1.2rem;
    color: #3730a3;
}

.item-content {
    flex: 1;
    min-width: 0;
}

.item-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.item-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 8px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    color: #6b7280;
}

.meta-item i {
    font-size: 0.75rem;
}

.item-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
    margin: 8px 0 0 0;
}

.item-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

/* Badges */
.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #dcfce7;
    color: #166534;
}

.badge-secondary {
    background: #f3f4f6;
    color: #6b7280;
}

/* Buttons */
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
    transition: all 0.2s ease;
}

.btn-sm {
    padding: 6px 10px;
    font-size: 0.8rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-outline {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Forms */
.form-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-header {
    padding: 20px 24px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 8px 8px 0 0;
}

.form-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.report-form {
    padding: 24px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

/* View Container */
.view-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-header {
    padding: 20px 24px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.view-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.view-actions {
    display: flex;
    gap: 8px;
}

.view-content {
    padding: 24px;
}

.detail-group {
    display: flex;
    align-items: flex-start;
    margin-bottom: 16px;
    gap: 16px;
}

.detail-group label {
    font-weight: 600;
    color: #374151;
    min-width: 120px;
    font-size: 0.9rem;
}

.detail-group span,
.detail-group p {
    color: #6b7280;
    margin: 0;
    flex: 1;
}

.detail-group p {
    line-height: 1.5;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 15% auto;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    width: 90%;
    max-width: 400px;
}

.modal-header {
    padding: 20px;
    background: #fee2e2;
    border-bottom: 1px solid #fca5a5;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    color: #dc2626;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header button {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #dc2626;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 20px;
}

.modal-body p {
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

.modal-actions {
    padding: 0 20px 20px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

/* No Data */
.no-data {
    text-align: center;
    padding: 60px 40px;
    color: #6b7280;
}

.no-data i {
    font-size: 2.5rem;
    color: #d1d5db;
    margin-bottom: 16px;
}

.no-data h3 {
    font-size: 1.3rem;
    color: #374151;
    margin-bottom: 8px;
    font-weight: 600;
}

.no-data p {
    margin-bottom: 20px;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .reports-container {
        padding: 16px;
    }
    
    .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .filters-form {
        flex-direction: column;
        gap: 12px;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .list-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
    }
    
    .item-icon {
        margin-right: 0;
    }
    
    .item-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .item-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .item-actions {
        width: 100%;
        justify-content: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .view-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .view-actions {
        width: 100%;
        justify-content: center;
    }
    
    .detail-group {
        flex-direction: column;
        gap: 4px;
    }
    
    .detail-group label {
        min-width: auto;
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
    
    .item-title {
        font-size: 1rem;
    }
    
    .meta-item {
        font-size: 0.75rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style>

<script>
// JavaScript functions for CRUD operations

function showCreateForm() {
    window.location.href = 'index.php?page=reports&action=create';
}

function deleteReport(reportId) {
    document.getElementById('deleteReportId').value = reportId;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.report-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const reportType = document.getElementById('report_type').value;
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (!title || !reportType || !dateFrom || !dateTo) {
                e.preventDefault();
                showNotification('Please fill in all required fields.', 'error');
                return;
            }
            
            if (new Date(dateFrom) > new Date(dateTo)) {
                e.preventDefault();
                showNotification('Start date must be before end date.', 'error');
                return;
            }
            
            if (new Date(dateTo) > new Date()) {
                e.preventDefault();
                showNotification('End date cannot be in the future.', 'error');
                return;
            }
        });
    }
    
    // Auto-submit filters on change
    const filterSelects = document.querySelectorAll('.filters-form select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
    
    // Search input with enter key
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
});

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
        <button onclick="this.parentElement.remove();">×</button>
    `;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N = New Report
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        if (window.location.href.includes('page=reports') && !window.location.href.includes('action=')) {
            showCreateForm();
        }
    }
    
    // Escape = Close modal
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Smooth scroll for better UX
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href*="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Add loading states for better UX
function addLoadingState(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    
    return function() {
        button.innerHTML = originalText;
        button.disabled = false;
    };
}

// Enhanced form submission with loading state
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                addLoadingState(submitBtn);
            }
        });
    });
});
</script>