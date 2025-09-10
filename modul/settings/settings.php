<?php
// Kegunaan: Mengatur konfigurasi sistem, maintenance mode, email settings, database settings
$message = '';
$error = '';

// Handle settings update
if (isset($_POST['update_settings'])) {
    $system_name = trim($_POST['system_name']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $max_file_size = trim($_POST['max_file_size']);
    $session_timeout = trim($_POST['session_timeout']);
    
    // Save to settings file atau database
    $settings = [
        'system_name' => $system_name,
        'maintenance_mode' => $maintenance_mode,
        'max_file_size' => $max_file_size,
        'session_timeout' => $session_timeout,
        'updated_by' => $_SESSION['user_name'],
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if (file_put_contents('config/settings.json', json_encode($settings, JSON_PRETTY_PRINT))) {
        $message = "Pengaturan sistem berhasil disimpan!";
    } else {
        $error = "Gagal menyimpan pengaturan sistem!";
    }
}

// Load current settings
$current_settings = [];
if (file_exists('config/settings.json')) {
    $current_settings = json_decode(file_get_contents('config/settings.json'), true);
}

// Default values
$system_name = $current_settings['system_name'] ?? 'IT | CORE System';
$maintenance_mode = $current_settings['maintenance_mode'] ?? 0;
$max_file_size = $current_settings['max_file_size'] ?? '5';
$session_timeout = $current_settings['session_timeout'] ?? '3600';
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

    <div class="settings-container">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Pengaturan Sistem</h1>
            <p>Kelola konfigurasi dan pengaturan sistem</p>
        </div>

        <div class="settings-grid">
            <!-- System Configuration -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-server"></i> Konfigurasi Sistem</h3>
                </div>
                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label>Nama Sistem</label>
                        <input type="text" name="system_name" value="<?= htmlspecialchars($system_name) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mode Maintenance</label>
                        <div class="toggle-switch">
                            <input type="checkbox" name="maintenance_mode" id="maintenance" <?= $maintenance_mode ? 'checked' : '' ?>>
                            <label for="maintenance" class="toggle-label">
                                <span class="toggle-inner"></span>
                                <span class="toggle-switch-btn"></span>
                            </label>
                            <span class="toggle-text">Aktifkan mode maintenance</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Maksimal Upload File (MB)</label>
                        <input type="number" name="max_file_size" value="<?= $max_file_size ?>" min="1" max="100">
                    </div>
                    
                    <div class="form-group">
                        <label>Session Timeout (detik)</label>
                        <input type="number" name="session_timeout" value="<?= $session_timeout ?>" min="300" max="86400">
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>

            <!-- System Information -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Informasi Sistem</h3>
                </div>
                <div class="system-info">
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?= phpversion() ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server:</span>
                        <span class="info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Database:</span>
                        <span class="info-value">MySQL <?= $koneksi->server_info ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Upload Max Size:</span>
                        <span class="info-value"><?= ini_get('upload_max_filesize') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Memory Limit:</span>
                        <span class="info-value"><?= ini_get('memory_limit') ?></span>
                    </div>
                </div>
            </div>

            <!-- Database Stats -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-database"></i> Statistik Database</h3>
                </div>
                <div class="db-stats">
                    <?php
                    $tables = ['apps', 'users', 'todos', 'taken'];
                    foreach($tables as $table):
                        $count = $koneksi->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
                    ?>
                    <div class="stat-item">
                        <span class="stat-label"><?= ucfirst($table) ?>:</span>
                        <span class="stat-value"><?= $count ?> records</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <button class="action-btn btn-warning" onclick="clearCache()">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                    <button class="action-btn btn-info" onclick="checkUpdates()">
                        <i class="fas fa-sync"></i> Check Updates
                    </button>
                    <button class="action-btn btn-danger" onclick="exportData()">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.settings-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
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

.settings-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-group input[type="text"],
.form-group input[type="number"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toggle-switch input[type="checkbox"] {
    display: none;
}

.toggle-label {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    background: #ccc;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.3s;
}

.toggle-switch input:checked + .toggle-label {
    background: #10b981;
}

.toggle-switch-btn {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s;
}

.toggle-switch input:checked + .toggle-label .toggle-switch-btn {
    transform: translateX(26px);
}

.toggle-text {
    font-size: 0.9rem;
    color: #6b7280;
}

.system-info, .db-stats {
    padding: 20px;
}

.info-item, .stat-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}

.info-item:last-child, .stat-item:last-child {
    border-bottom: none;
}

.info-label, .stat-label {
    font-weight: 500;
    color: #374151;
}

.info-value, .stat-value {
    color: #6b7280;
    font-family: monospace;
}

.quick-actions {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}

.btn-warning { background: #f59e0b; color: white; }
.btn-info { background: #3b82f6; color: white; }
.btn-danger { background: #ef4444; color: white; }

.action-btn:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
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
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function clearCache() {
    if (confirm('Apakah Anda yakin ingin menghapus cache sistem?')) {
        fetch('ajax/clear_cache.php', {method: 'POST'})
        .then(response => response.json())
        .then(data => {
            alert(data.message || 'Cache berhasil dihapus!');
        });
    }
}

function checkUpdates() {
    alert('Fitur check updates akan segera tersedia.');
}

function exportData() {
    if (confirm('Export semua data sistem?')) {
        window.location.href = 'ajax/export_data.php';
    }
}
</script>