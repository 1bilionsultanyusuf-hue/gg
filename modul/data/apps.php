<?php
// Handle CRUD Operations for Apps
$message = '';
$error = '';

// Handle Todo creation for specific app
if (isset($_POST['add_todo_to_app'])) {
    $app_id = trim($_POST['app_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($app_id)) {
        $stmt = $koneksi->prepare("INSERT INTO todos (app_id, title, description, priority, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $app_id, $title, $description, $priority, $user_id);
        
        if ($stmt->execute()) {
            // Get app name for message
            $app_stmt = $koneksi->prepare("SELECT name FROM apps WHERE id = ?");
            $app_stmt->bind_param("i", $app_id);
            $app_stmt->execute();
            $app_name = $app_stmt->get_result()->fetch_assoc()['name'];
            
            $message = "Todo '$title' berhasil ditambahkan ke aplikasi '$app_name'!";
        } else {
            $error = "Gagal menambahkan todo!";
        }
    } else {
        $error = "Judul todo harus diisi!";
    }
}

// CREATE - Add new app
if (isset($_POST['add_app'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $koneksi->prepare("INSERT INTO apps (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $message = "Aplikasi '$name' berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan aplikasi!";
        }
    } else {
        $error = "Nama aplikasi harus diisi!";
    }
}

// UPDATE - Edit app
if (isset($_POST['edit_app'])) {
    $id = $_POST['app_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $koneksi->prepare("UPDATE apps SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        
        if ($stmt->execute()) {
            $message = "Aplikasi berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui aplikasi!";
        }
    } else {
        $error = "Nama aplikasi harus diisi!";
    }
}

// DELETE - Remove app
if (isset($_POST['delete_app'])) {
    $id = $_POST['app_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM apps WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Aplikasi berhasil dihapus!";
    } else {
        $error = "Gagal menghapus aplikasi!";
    }
}

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

// Get app for editing if requested
$edit_app = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = $koneksi->prepare("SELECT * FROM apps WHERE id = ?");
    $edit_query->bind_param("i", $edit_id);
    $edit_query->execute();
    $edit_app = $edit_query->get_result()->fetch_assoc();
}

// Get statistics
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];
$high_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'high'")->fetch_assoc()['count'];
$avg_todos = $total_apps > 0 ? round($total_todos / $total_apps, 1) : 0;
?>

<div class="main-content" style="margin-top: 80px;">
    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= $message ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Manajemen Aplikasi</h1>
            <p class="page-subtitle">
                Kelola dan monitor semua aplikasi dalam sistem
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAddAppModal()">
                <i class="fas fa-plus mr-2"></i>
                Tambah Aplikasi
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
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_todos ?></h3>
                <p class="stat-label">Total Tugas</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $high_priority ?></h3>
                <p class="stat-label">High Priority</p>
            </div>
        </div>
    </div>
    
    <!-- Applications List -->
    <div class="apps-container">
        <div class="section-header">
            <h2 class="section-title">Daftar Aplikasi</h2>
            <span class="section-count"><?= $total_apps ?> aplikasi</span>
        </div>
        
        <!-- Add New App Button -->
        <div class="app-list-item add-new-item" onclick="openAddAppModal()">
            <div class="add-new-content">
                <div class="add-new-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="add-new-text">
                    <h3>Tambah Aplikasi Baru</h3>
                    <p>Klik untuk menambahkan aplikasi</p>
                </div>
            </div>
        </div>
        
        <div class="apps-list">
            <?php while($app = $apps_result->fetch_assoc()): ?>
            <div class="app-list-item" data-app-id="<?= $app['id'] ?>">
                <div class="app-list-icon">
                    <i class="fas fa-<?= getAppIcon($app['name']) ?>"></i>
                </div>
                
                <div class="app-list-content">
                    <div class="app-list-main">
                        <h3 class="app-list-title"><?= htmlspecialchars($app['name']) ?></h3>
                        <p class="app-list-description">
                            <?= htmlspecialchars(substr($app['description'], 0, 80)) ?>
                            <?= strlen($app['description']) > 80 ? '...' : '' ?>
                        </p>
                    </div>
                    
                    <div class="app-list-stats">
                        <div class="stat-badge total">
                            <span class="stat-number"><?= $app['total_todos'] ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-badge active">
                            <span class="stat-number"><?= $app['active_todos'] ?></span>
                            <span class="stat-label">Aktif</span>
                        </div>
                        <div class="stat-badge completed">
                            <span class="stat-number"><?= $app['completed_todos'] ?></span>
                            <span class="stat-label">Selesai</span>
                        </div>
                    </div>
                </div>
                
                <div class="app-list-progress">
                    <div class="progress-info">
                        <span class="progress-percentage">
                            <?= $app['total_todos'] > 0 ? round(($app['completed_todos'] / $app['total_todos']) * 100) : 0 ?>%
                        </span>
                    </div>
                    <div class="progress-bar-small">
                        <div class="progress-fill-small" 
                             style="width: <?= $app['total_todos'] > 0 ? ($app['completed_todos'] / $app['total_todos']) * 100 : 0 ?>%">
                        </div>
                    </div>
                </div>

                <!-- App-specific actions -->
                <div class="app-actions">
                    <button class="btn btn-todo-small" onclick="openAddTodoForAppModal(<?= $app['id'] ?>, '<?= htmlspecialchars($app['name'], ENT_QUOTES) ?>')" title="Tambah Todo">
                        <i class="fas fa-plus"></i>
                        <span>Todo</span>
                    </button>
                </div>
                
                <div class="app-list-actions">
                    <button class="action-btn-small edit" onclick="editApp(<?= $app['id'] ?>, '<?= htmlspecialchars($app['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($app['description'], ENT_QUOTES) ?>')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn-small delete" onclick="deleteApp(<?= $app['id'] ?>, '<?= htmlspecialchars($app['name'], ENT_QUOTES) ?>')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php
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

<!-- Add/Edit App Modal -->
<div id="appModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Aplikasi Baru</h3>
            <button class="modal-close" onclick="closeAppModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="appForm" method="POST">
                <input type="hidden" id="appId" name="app_id">
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
            <button type="button" class="btn btn-secondary" onclick="closeAppModal()">
                Batal
            </button>
            <button type="submit" id="submitBtn" form="appForm" name="add_app" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
        </div>
    </div>
</div>

<!-- Add Todo to App Modal -->
<div id="todoToAppModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="todoModalTitle">Tambah Todo ke Aplikasi</h3>
            <button class="modal-close" onclick="closeTodoToAppModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="selected-app-info">
                <div class="app-info-badge">
                    <i class="fas fa-cube"></i>
                    <span id="selectedAppName">Aplikasi</span>
                </div>
            </div>
            <form id="todoToAppForm" method="POST">
                <input type="hidden" id="todoAppId" name="app_id">
                <div class="form-group">
                    <label for="todoTitle">Judul Todo *</label>
                    <input type="text" id="todoTitle" name="title" required 
                           placeholder="Masukkan judul todo">
                </div>
                <div class="form-group">
                    <label for="todoDescription">Deskripsi</label>
                    <textarea id="todoDescription" name="description" rows="4"
                              placeholder="Deskripsi detail tentang todo"></textarea>
                </div>
                <div class="form-group">
                    <label for="todoPriority">Prioritas</label>
                    <div class="priority-selector">
                        <label class="priority-option">
                            <input type="radio" name="priority" value="low" checked>
                            <span class="priority-badge priority-low">
                                <i class="fas fa-arrow-down"></i>
                                Low
                            </span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="medium">
                            <span class="priority-badge priority-medium">
                                <i class="fas fa-minus"></i>
                                Medium
                            </span>
                        </label>
                        <label class="priority-option">
                            <input type="radio" name="priority" value="high">
                            <span class="priority-badge priority-high">
                                <i class="fas fa-exclamation-triangle"></i>
                                High
                            </span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTodoToAppModal()">
                Batal
            </button>
            <button type="submit" form="todoToAppForm" name="add_todo_to_app" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Tambah Todo
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content delete-modal">
        <div class="modal-header">
            <div class="delete-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Konfirmasi Hapus</h3>
            <p id="deleteMessage">Apakah Anda yakin ingin menghapus aplikasi ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <form id="deleteForm" method="POST" style="display: inline;">
                <input type="hidden" id="deleteAppId" name="app_id">
                <button type="submit" name="delete_app" class="btn btn-danger">
                    <i class="fas fa-trash mr-2"></i>Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Alert Messages */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
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

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Page Header */
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

.page-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
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

.btn-danger {
    background: linear-gradient(90deg, #ef4444, #dc2626);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(90deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
}

/* New Todo Button Styles */
.btn-todo-small {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    border: none;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(16,185,129,0.2);
}

.btn-todo-small:hover {
    background: linear-gradient(135deg, #059669, #10b981);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

.btn-todo-small i {
    font-size: 0.75rem;
}

.mr-2 {
    margin-right: 8px;
}

/* Statistics Grid */
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

.bg-gradient-blue { background: linear-gradient(135deg, #0066ff, #33ccff); color: white; }
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
}

/* Apps Container */
.apps-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.section-header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.section-count {
    color: #6b7280;
    font-size: 0.9rem;
    background: #f3f4f6;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Apps List */
.apps-list {
    max-height: 500px;
    overflow-y: auto;
}

.apps-list::-webkit-scrollbar {
    width: 6px;
}

.apps-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.apps-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.apps-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.app-list-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    cursor: pointer;
}

.app-list-item:hover {
    background: #f8fafc;
}

.app-list-item:last-child {
    border-bottom: none;
}

/* Add New Item */
.add-new-item {
    border: 2px dashed #d1d5db !important;
    background: #f9fafb !important;
    margin: 16px 24px;
    border-radius: 12px;
    justify-content: center;
}

.add-new-item:hover {
    border-color: #0066ff !important;
    background: #eff6ff !important;
}

.add-new-content {
    display: flex;
    align-items: center;
    gap: 16px;
    color: #6b7280;
}

.add-new-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.add-new-text h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #374151;
}

.add-new-text p {
    font-size: 0.85rem;
    margin: 0;
    color: #9ca3af;
}

/* App List Item Components */
.app-list-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-right: 16px;
    flex-shrink: 0;
}

.app-list-content {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.app-list-main {
    flex: 1;
    min-width: 0;
}

.app-list-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.app-list-description {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

.app-list-stats {
    display: flex;
    gap: 12px;
    margin-right: 20px;
}

.stat-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 50px;
}

.stat-badge .stat-number {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.stat-badge.total .stat-number { color: #3b82f6; }
.stat-badge.active .stat-number { color: #f59e0b; }
.stat-badge.completed .stat-number { color: #10b981; }

.stat-badge .stat-label {
    font-size: 0.7rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 500;
    margin-top: 2px;
}

.app-list-progress {
    width: 80px;
    margin-right: 20px;
    flex-shrink: 0;
}

.progress-info {
    text-align: center;
    margin-bottom: 6px;
}

.progress-percentage {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
}

.progress-bar-small {
    width: 100%;
    height: 4px;
    background: #f3f4f6;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill-small {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.3s ease;
}

/* App Actions Container */
.app-actions {
    margin-right: 16px;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.app-list-item:hover .app-actions {
    opacity: 1;
}

.app-list-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.app-list-item:hover .app-list-actions {
    opacity: 1;
}

.action-btn-small {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.action-btn-small:hover {
    transform: scale(1.1);
}

.action-btn-small.edit:hover {
    background: #dbeafe;
    color: #2563eb;
}

.action-btn-small.delete:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* Selected App Info in Modal */
.selected-app-info {
    margin-bottom: 20px;
    padding: 12px;
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 8px;
}

.app-info-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #0ea5e9;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Priority Selector in Modal */
.priority-selector {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.priority-option {
    cursor: pointer;
}

.priority-option input[type="radio"] {
    display: none;
}

.priority-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 20px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    font-weight: 500;
    color: white;
}

.priority-badge.priority-low {
    background: linear-gradient(135deg, #10b981, #059669);
}

.priority-badge.priority-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.priority-badge.priority-high {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.priority-option input[type="radio"]:checked + .priority-badge {
    border-color: #1f2937;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

.priority-option:hover .priority-badge {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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

.delete-modal {
    max-width: 400px;
    text-align: center;
}

.delete-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
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

.delete-modal .modal-header {
    flex-direction: column;
    text-align: center;
}

.delete-modal .modal-header p {
    margin: 8px 0 0 0;
    color: #6b7280;
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
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
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
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .app-list-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .app-list-stats {
        margin-right: 0;
        gap: 8px;
    }
    
    .app-list-progress {
        width: 100%;
        margin-right: 0;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .add-new-content {
        flex-direction: column;
        text-align: center;
    }
    
    .apps-list {
        max-height: none;
    }

    .app-actions {
        margin-right: 0;
        margin-bottom: 8px;
        order: 3;
        opacity: 1;
    }

    .app-list-actions {
        opacity: 1;
        order: 4;
        margin-top: 8px;
        justify-content: center;
    }

    .app-list-item {
        flex-wrap: wrap;
    }
    
    .priority-selector {
        flex-direction: column;
        gap: 8px;
    }

    .priority-badge {
        justify-content: center;
        padding: 12px 20px;
    }
}

@media (max-width: 480px) {
    .app-list-item {
        padding: 12px 16px;
    }
    
    .section-header {
        padding: 20px 16px 12px;
    }
    
    .add-new-item {
        margin: 12px 16px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let currentEditId = null;
let currentTodoAppId = null;
let currentTodoAppName = '';

function openAddAppModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Aplikasi Baru';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_app';
    document.getElementById('appForm').reset();
    document.getElementById('appId').value = '';
    currentEditId = null;
    document.getElementById('appModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editApp(id, name, description) {
    document.getElementById('modalTitle').textContent = 'Edit Aplikasi';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('submitBtn').name = 'edit_app';
    document.getElementById('appId').value = id;
    document.getElementById('appName').value = name;
    document.getElementById('appDescription').value = description;
    currentEditId = id;
    document.getElementById('appModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteApp(id, name) {
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus aplikasi "${name}"? Semua data terkait akan ikut terhapus.`;
    document.getElementById('deleteAppId').value = id;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function openAddTodoForAppModal(appId, appName) {
    document.getElementById('todoModalTitle').textContent = `Tambah Todo ke "${appName}"`;
    document.getElementById('selectedAppName').textContent = appName;
    document.getElementById('todoAppId').value = appId;
    document.getElementById('todoToAppForm').reset();
    
    // Reset priority to low (default)
    document.querySelector('input[name="priority"][value="low"]').checked = true;
    
    currentTodoAppId = appId;
    currentTodoAppName = appName;
    document.getElementById('todoToAppModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAppModal() {
    document.getElementById('appModal').classList.remove('show');
    document.body.style.overflow = '';
}

function closeTodoToAppModal() {
    document.getElementById('todoToAppModal').classList.remove('show');
    document.body.style.overflow = '';
    currentTodoAppId = null;
    currentTodoAppName = '';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeAppModal();
        closeTodoToAppModal();
        closeDeleteModal();
    }
});

// Auto-hide alerts after 5 seconds
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

// Handle escape key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAppModal();
        closeTodoToAppModal();
        closeDeleteModal();
    }
});

// Form validation for todo modal
document.getElementById('todoToAppForm').addEventListener('submit', function(e) {
    const title = document.getElementById('todoTitle').value.trim();
    if (!title) {
        e.preventDefault();
        alert('Judul todo harus diisi!');
        document.getElementById('todoTitle').focus();
        return false;
    }
});

// Form validation for app modal
document.getElementById('appForm').addEventListener('submit', function(e) {
    const name = document.getElementById('appName').value.trim();
    if (!name) {
        e.preventDefault();
        alert('Nama aplikasi harus diisi!');
        document.getElementById('appName').focus();
        return false;
    }
});

// Auto-focus on modal inputs when opened
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.target.classList.contains('modal') && mutation.target.classList.contains('show')) {
            setTimeout(() => {
                const firstInput = mutation.target.querySelector('input[type="text"], textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 300);
        }
    });
});

// Observe modal state changes
document.querySelectorAll('.modal').forEach(modal => {
    observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
});
</script>