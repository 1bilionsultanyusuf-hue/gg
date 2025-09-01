<?php
// Get apps data with todo counts
$apps_query = "
    SELECT a.*, 
           COUNT(t.id) as total_todos,
           COUNT(CASE WHEN tk.status = 'in_progress' THEN 1 END) as active_todos,
           COUNT(CASE WHEN tk.status = 'done' THEN 1 END) as completed_todos
    FROM apps a
    LEFT JOIN todos t ON a.id = t.app_id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    GROUP BY a.id
    ORDER BY a.name
";
$apps_result = $koneksi->query($apps_query);

// Get total statistics
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];
$high_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'high'")->fetch_assoc()['count'];
$avg_todos = $total_apps > 0 ? round($total_todos / $total_apps, 1) : 0;
?>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-th-large mr-3"></i>
                Manajemen Aplikasi
            </h1>
            <p class="page-subtitle">
                Kelola dan monitor semua aplikasi dalam sistem
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAddAppModal()">
                <i class="fas fa-plus mr-2"></i>
                Tambah Aplikasi
            </button>
            <button class="btn btn-secondary" onclick="exportApps()">
                <i class="fas fa-download mr-2"></i>
                Export Data
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_apps ?></h3>
                <p class="stat-label">Total Aplikasi</p>
                <span class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> Active
                </span>
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_todos ?></h3>
                <p class="stat-label">Total Tugas</p>
                <span class="stat-change positive">
                    <i class="fas fa-plus"></i> All Apps
                </span>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $high_priority ?></h3>
                <p class="stat-label">High Priority</p>
                <span class="stat-change warning">
                    <i class="fas fa-fire"></i> Urgent
                </span>
            </div>
        </div>

        <div class="stat-card bg-gradient-purple">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $avg_todos ?></h3>
                <p class="stat-label">Rata-rata Tugas</p>
                <span class="stat-change neutral">
                    <i class="fas fa-equals"></i> Per App
                </span>
            </div>
        </div>
    </div>

    <!-- Applications Grid -->
    <div class="apps-grid">
        <?php while($app = $apps_result->fetch_assoc()): ?>
        <div class="app-card" data-app-id="<?= $app['id'] ?>">
            <div class="app-card-header">
                <div class="app-icon">
                    <i class="fas fa-<?= getAppIcon($app['name']) ?>"></i>
                </div>
                <div class="app-actions">
                    <button class="action-btn" onclick="editApp(<?= $app['id'] ?>)" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn" onclick="viewAppDetails(<?= $app['id'] ?>)" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn danger" onclick="deleteApp(<?= $app['id'] ?>)" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="app-content">
                <h3 class="app-title"><?= htmlspecialchars($app['name']) ?></h3>
                <p class="app-description">
                    <?= htmlspecialchars(substr($app['description'], 0, 120)) ?>
                    <?= strlen($app['description']) > 120 ? '...' : '' ?>
                </p>
            </div>
            
            <div class="app-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $app['total_todos'] ?></span>
                    <span class="stat-name">Total Tugas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value active"><?= $app['active_todos'] ?></span>
                    <span class="stat-name">Aktif</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value completed"><?= $app['completed_todos'] ?></span>
                    <span class="stat-name">Selesai</span>
                </div>
            </div>
            
            <div class="app-footer">
                <div class="progress-bar">
                    <div class="progress-fill" 
                         style="width: <?= $app['total_todos'] > 0 ? ($app['completed_todos'] / $app['total_todos']) * 100 : 0 ?>%">
                    </div>
                </div>
                <span class="progress-text">
                    <?= $app['total_todos'] > 0 ? round(($app['completed_todos'] / $app['total_todos']) * 100) : 0 ?>% Complete
                </span>
            </div>
        </div>
        <?php endwhile; ?>
        
        <!-- Add New App Card -->
        <div class="app-card add-new-card" onclick="openAddAppModal()">
            <div class="add-new-content">
                <div class="add-new-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3>Tambah Aplikasi Baru</h3>
                <p>Klik untuk menambahkan aplikasi baru ke sistem</p>
            </div>
        </div>
    </div>
</div>

<?php
// Function to get appropriate icon for app
function getAppIcon($appName) {
    $icons = [
        'keuangan' => 'money-bill-wave',
        'inventaris' => 'boxes',
        'crm' => 'users',
        'hris' => 'user-tie',
        'default' => 'cube'
    ];
    
    $name = strtolower($appName);
    foreach($icons as $key => $icon) {
        if(strpos($name, $key) !== false) {
            return $icon;
        }
    }
    return $icons['default'];
}
?>

<!-- Add App Modal -->
<div id="addAppModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tambah Aplikasi Baru</h3>
            <button class="modal-close" onclick="closeAddAppModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addAppForm" method="POST">
                <div class="form-group">
                    <label for="appName">Nama Aplikasi *</label>
                    <input type="text" id="appName" name="name" required 
                           placeholder="Masukkan nama aplikasi">
                </div>
                <div class="form-group">
                    <label for="appDescription">Deskripsi</label>
                    <textarea id="appDescription" name="description" rows="4"
                              placeholder="Deskripsi singkat tentang aplikasi"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAddAppModal()">
                Batal
            </button>
            <button type="submit" form="addAppForm" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
        </div>
    </div>
</div>

<style>
/* Apps Page Styles */
.page-header {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-primary {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
}

.btn-secondary {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.bg-gradient-blue { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); color: white; }
.bg-gradient-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); color: white; }
.bg-gradient-purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); color: white; }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 8px;
}

.stat-change {
    font-size: 0.8rem;
    opacity: 0.8;
}

.apps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.app-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.app-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.app-card-header {
    padding: 20px 24px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.app-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.app-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.app-card:hover .app-actions {
    opacity: 1;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: #e2e8f0;
}

.action-btn.danger:hover {
    background: #fee2e2;
    color: #dc2626;
}

.app-content {
    padding: 20px 24px;
}

.app-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.app-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
}

.app-stats {
    padding: 0 24px 20px;
    display: flex;
    justify-content: space-between;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-value.active {
    color: #f59e0b;
}

.stat-value.completed {
    color: #10b981;
}

.stat-name {
    font-size: 0.8rem;
    color: #9ca3af;
}

.app-footer {
    padding: 20px 24px;
    border-top: 1px solid #f3f4f6;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.8rem;
    color: #6b7280;
}

.add-new-card {
    border: 2px dashed #d1d5db;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.add-new-card:hover {
    border-color: #0066ff;
    background: #eff6ff;
}

.add-new-content {
    text-align: center;
    color: #6b7280;
}

.add-new-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;
}

.add-new-content h3 {
    color: #374151;
    margin-bottom: 8px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

.modal-header {
    padding: 24px 24px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
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

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
}

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .header-actions {
        width: 100%;
        justify-content: center;
    }
    
    .apps-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Apps Management JavaScript
function openAddAppModal() {
    document.getElementById('addAppModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAddAppModal() {
    document.getElementById('addAppModal').classList.remove('show');
    document.body.style.overflow = '';
}

function editApp(appId) {
    alert('Edit app functionality coming soon! App ID: ' + appId);
}

function viewAppDetails(appId) {
    alert('View app details functionality coming soon! App ID: ' + appId);
}

function deleteApp(appId) {
    if(confirm('Apakah Anda yakin ingin menghapus aplikasi ini?')) {
        alert('Delete app functionality coming soon! App ID: ' + appId);
    }
}

function exportApps() {
    alert('Export functionality coming soon!');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeAddAppModal();
    }
});
</script>