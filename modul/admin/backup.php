<?php
// Kegunaan: Backup database, restore, schedule backup, monitor backup history
$message = '';
$error = '';

// Backup directory
$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = $backup_dir . $backup_name;
    
    // Get database info from config
    global $koneksi;
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'appstodos';
    
    try {
        // Create mysqldump command
        $command = "mysqldump --host=$host --user=$user --password=$pass --single-transaction --routines --triggers $db > $backup_path";
        
        // For Windows, might need different approach
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "mysqldump -h$host -u$user -p$pass --single-transaction --routines --triggers $db > $backup_path";
        }
        
        // Execute backup (simplified version)
        if (createDatabaseBackup($koneksi, $backup_path)) {
            // Log backup creation
            $stmt = $koneksi->prepare("INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'BACKUP_CREATED', 'database', NULL, ?)");
            $stmt->bind_param("is", $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);
            $stmt->execute();
            
            $message = "Backup berhasil dibuat: $backup_name";
        } else {
            $error = "Gagal membuat backup database!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle backup deletion
if (isset($_POST['delete_backup'])) {
    $backup_file = $_POST['backup_file'];
    $backup_path = $backup_dir . basename($backup_file);
    
    if (file_exists($backup_path) && unlink($backup_path)) {
        $message = "Backup berhasil dihapus!";
    } else {
        $error = "Gagal menghapus backup!";
    }
}

// Get backup files
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backup_dir . $file;
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => filemtime($filepath),
                'path' => $filepath
            ];
        }
    }
    // Sort by date descending
    usort($backup_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Get database statistics
$tables_info = [];
$tables = ['apps', 'users', 'todos', 'taken', 'system_logs'];
foreach ($tables as $table) {
    $result = $koneksi->query("SELECT COUNT(*) as count FROM $table");
    $count = $result->fetch_assoc()['count'];
    
    $result = $koneksi->query("SHOW TABLE STATUS LIKE '$table'");
    $info = $result->fetch_assoc();
    
    $tables_info[] = [
        'name' => $table,
        'rows' => $count,
        'size' => $info['Data_length'] + $info['Index_length']
    ];
}

function createDatabaseBackup($connection, $backup_path) {
    try {
        $tables = ['apps', 'users', 'todos', 'taken', 'system_logs'];
        $backup_content = "-- IT|CORE Database Backup\n";
        $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $result = $connection->query("SHOW CREATE TABLE `$table`");
            if ($result && $row = $result->fetch_assoc()) {
                $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                $backup_content .= $row['Create Table'] . ";\n\n";
            }
            
            // Get table data
            $result = $connection->query("SELECT * FROM `$table`");
            if ($result && $result->num_rows > 0) {
                $backup_content .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $value === null ? 'NULL' : "'" . $connection->real_escape_string($value) . "'";
                    }
                    $rows[] = '(' . implode(',', $values) . ')';
                }
                $backup_content .= implode(",\n", $rows) . ";\n\n";
            }
        }
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return file_put_contents($backup_path, $backup_content) !== false;
    } catch (Exception $e) {
        return false;
    }
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<div class="main-content" style="margin-top: 80px;">
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $message ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="backup-container">
        <div class="page-header">
            <h1><i class="fas fa-database"></i> Database Backup</h1>
            <p>Kelola backup dan restore database sistem</p>
        </div>

        <div class="backup-grid">
            <!-- Quick Actions -->
            <div class="backup-card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> Quick Actions</h3>
                </div>
                <div class="card-content">
                    <form method="POST">
                        <button type="submit" name="create_backup" class="btn btn-primary btn-large">
                            <i class="fas fa-save"></i>
                            <div>
                                <span>Create Backup</span>
                                <small>Buat backup database sekarang</small>
                            </div>
                        </button>
                    </form>
                    
                    <div class="action-info">
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span>Last Backup: <?= !empty($backup_files) ? date('d M Y H:i', $backup_files[0]['date']) : 'Never' ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-hdd"></i>
                            <span>Total Backups: <?= count($backup_files) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Statistics -->
            <div class="backup-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Database Statistics</h3>
                </div>
                <div class="card-content">
                    <div class="db-stats">
                        <?php foreach ($tables_info as $table): ?>
                        <div class="table-stat">
                            <div class="table-info">
                                <span class="table-name"><?= $table['name'] ?></span>
                                <span class="table-rows"><?= number_format($table['rows']) ?> rows</span>
                            </div>
                            <div class="table-size"><?= formatFileSize($table['size']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="total-size">
                        Total Size: <?= formatFileSize(array_sum(array_column($tables_info, 'size'))) ?>
                    </div>
                </div>
            </div>

            <!-- Backup Schedule -->
            <div class="backup-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Backup Schedule</h3>
                </div>
                <div class="card-content">
                    <div class="schedule-option">
                        <input type="radio" id="manual" name="schedule" checked>
                        <label for="manual">Manual Only</label>
                    </div>
                    <div class="schedule-option">
                        <input type="radio" id="daily" name="schedule">
                        <label for="daily">Daily at 2:00 AM</label>
                    </div>
                    <div class="schedule-option">
                        <input type="radio" id="weekly" name="schedule">
                        <label for="weekly">Weekly (Sunday 2:00 AM)</label>
                    </div>
                    
                    <button class="btn btn-secondary btn-small">
                        <i class="fas fa-save"></i> Save Schedule
                    </button>
                </div>
            </div>

            <!-- Backup History -->
            <div class="backup-card full-width">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Backup History</h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($backup_files)): ?>
                    <div class="backup-table">
                        <?php foreach ($backup_files as $backup): ?>
                        <div class="backup-row">
                            <div class="backup-info">
                                <div class="backup-name">
                                    <i class="fas fa-file-archive"></i>
                                    <?= htmlspecialchars($backup['name']) ?>
                                </div>
                                <div class="backup-meta">
                                    <span class="backup-date"><?= date('d M Y H:i:s', $backup['date']) ?></span>
                                    <span class="backup-size"><?= formatFileSize($backup['size']) ?></span>
                                </div>
                            </div>
                            <div class="backup-actions">
                                <a href="<?= $backup['path'] ?>" class="btn btn-info btn-small" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button class="btn btn-warning btn-small" onclick="restoreBackup('<?= $backup['name'] ?>')">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus backup ini?')">
                                    <input type="hidden" name="backup_file" value="<?= $backup['name'] ?>">
                                    <button type="submit" name="delete_backup" class="btn btn-danger btn-small">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-backups">
                        <i class="fas fa-database"></i>
                        <h4>Belum Ada Backup</h4>
                        <p>Buat backup pertama untuk melindungi data Anda</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.backup-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.backup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
}

.backup-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.backup-card.full-width {
    grid-column: 1 / -1;
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

.btn-large {
    width: 100%;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    text-align: left;
}

.btn-large i {
    font-size: 1.5rem;
}

.btn-large div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.btn-large span {
    font-weight: 600;
    font-size: 1rem;
}

.btn-large small {
    color: rgba(255,255,255,0.8);
    font-size: 0.8rem;
}

.action-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #6b7280;
}

.db-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.table-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}

.table-stat:last-child {
    border-bottom: none;
}

.table-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.table-name {
    font-weight: 500;
    color: #1f2937;
}

.table-rows {
    font-size: 0.8rem;
    color: #9ca3af;
}

.table-size {
    font-weight: 600;
    color: #6b7280;
    font-family: monospace;
}

.total-size {
    padding-top: 12px;
    border-top: 2px solid #e5e7eb;
    font-weight: 600;
    color: #1f2937;
    text-align: right;
}

.schedule-option {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.schedule-option input[type="radio"] {
    margin: 0;
}

.schedule-option label {
    font-size: 0.9rem;
    color: #374151;
}

.backup-table {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.backup-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.backup-info {
    flex: 1;
}

.backup-name {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.backup-meta {
    display: flex;
    gap: 16px;
    font-size: 0.8rem;
    color: #9ca3af;
}

.backup-actions {
    display: flex;
    gap: 8px;
}

.no-backups {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.no-backups i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: #d1d5db;
}

.no-backups h4 {
    font-size: 1.2rem;
    color: #6b7280;
    margin-bottom: 8px;
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

.btn-small {
    padding: 6px 12px;
    font-size: 0.8rem;
}

.btn-primary {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.btn-secondary {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-info {
    background: #3b82f6;
    color: white;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

@media (max-width: 768px) {
    .backup-grid {
        grid-template-columns: 1fr;
    }
    
    .backup-row {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .backup-actions {
        justify-content: center;
    }
}
</style>

<script>
function restoreBackup(backupName) {
    if (confirm(`PERINGATAN: Restore akan mengganti semua data saat ini dengan data dari backup "${backupName}". Apakah Anda yakin?`)) {
        if (confirm('Ini adalah tindakan yang tidak dapat dibatalkan. Pastikan Anda telah membuat backup terbaru. Lanjutkan?')) {
            // In real implementation, this would call restore endpoint
            alert('Fitur restore akan segera tersedia. Untuk saat ini, gunakan phpMyAdmin atau mysql command line.');
        }
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>