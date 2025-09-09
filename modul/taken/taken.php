<?php
// Handle CRUD Operations for Taken Tasks
$message = '';
$error = '';

// TAKE NEW TASK - User mengambil tugas
if (isset($_POST['take_task'])) {
    $todo_id = (int)$_POST['todo_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if task already taken
    $check_taken = $koneksi->prepare("SELECT id FROM taken WHERE id_todos = ?");
    $check_taken->bind_param("i", $todo_id);
    $check_taken->execute();
    $result = $check_taken->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Tugas ini sudah diambil oleh orang lain!";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO taken (id_todos, user_id, status, date) VALUES (?, ?, 'in_progress', NOW())");
        $stmt->bind_param("ii", $todo_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "Tugas berhasil diambil dan sedang dikerjakan!";
        } else {
            $error = "Gagal mengambil tugas!";
        }
    }
}

// UPDATE STATUS - Change task status
if (isset($_POST['update_status'])) {
    $taken_id = (int)$_POST['taken_id'];
    $status = $_POST['status'];
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $taken_id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $user_id || $_SESSION['user_role'] == 'admin') {
            $stmt = $koneksi->prepare("UPDATE taken SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $taken_id);
            
            if ($stmt->execute()) {
                $message = "Status tugas berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui status!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk mengubah status tugas ini!";
        }
    }
}

// RELEASE TASK - User melepas tugas
if (isset($_POST['release_task'])) {
    $taken_id = (int)$_POST['taken_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $taken_id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $user_id || $_SESSION['user_role'] == 'admin') {
            $stmt = $koneksi->prepare("DELETE FROM taken WHERE id = ?");
            $stmt->bind_param("i", $taken_id);
            
            if ($stmt->execute()) {
                $message = "Tugas berhasil dilepas dan tersedia kembali!";
            } else {
                $error = "Gagal melepas tugas!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk melepas tugas ini!";
        }
    }
}

// Get taken tasks with detailed info
$taken_query = "
    SELECT tk.*, 
           t.title as todo_title,
           t.description as todo_description,
           t.priority,
           u.name as user_name,
           a.name as app_name,
           t.created_at as todo_created_at
    FROM taken tk
    JOIN todos t ON tk.id_todos = t.id
    JOIN users u ON tk.user_id = u.id
    JOIN apps a ON t.app_id = a.id
    ORDER BY tk.date DESC
";
$taken_result = $koneksi->query($taken_query);

// Get available tasks (not taken yet)
$available_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as creator_name
    FROM todos t
    JOIN apps a ON t.app_id = a.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE tk.id IS NULL
    ORDER BY t.priority = 'high' DESC, t.priority = 'medium' DESC, t.created_at DESC
";
$available_result = $koneksi->query($available_query);

// Get statistics
$total_taken = $koneksi->query("SELECT COUNT(*) as count FROM taken")->fetch_assoc()['count'];
$in_progress = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'done'")->fetch_assoc()['count'];
$my_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc()['count'];

function getPriorityIcon($priority) {
    return ['high' => 'exclamation-triangle', 'medium' => 'minus', 'low' => 'arrow-down'][$priority] ?? 'circle';
}

function getPriorityColor($priority) {
    return ['high' => '#ef4444', 'medium' => '#f59e0b', 'low' => '#10b981'][$priority] ?? '#6b7280';
}
?>

<div class="main-content" style="margin-top: 80px;">
    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-hand-paper mr-3"></i>
                Task Management
            </h1>
            <p class="page-subtitle">
                Ambil dan kelola tugas yang tersedia
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAvailableTasksModal()">
                <i class="fas fa-plus mr-2"></i>
                Ambil Tugas Baru
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_taken ?></h3>
                <p class="stat-label">Total Tasks Taken</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $in_progress ?></h3>
                <p class="stat-label">In Progress</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $completed ?></h3>
                <p class="stat-label">Completed</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-purple">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $my_tasks ?></h3>
                <p class="stat-label">My Tasks</p>
            </div>
        </div>
    </div>

    <!-- Taken Tasks List -->
    <div class="tasks-container">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-list-check mr-2"></i>
                Tasks Currently Taken
            </h3>
        </div>

        <?php if ($taken_result->num_rows > 0): ?>
        <div class="tasks-grid">
            <?php while($task = $taken_result->fetch_assoc()): ?>
            <div class="task-card status-<?= $task['status'] ?>">
                <div class="task-card-header">
                    <div class="task-priority priority-<?= $task['priority'] ?>">
                        <i class="fas fa-<?= getPriorityIcon($task['priority']) ?>"></i>
                        <?= ucfirst($task['priority']) ?>
                    </div>
                    <div class="task-status status-<?= $task['status'] ?>">
                        <?= $task['status'] == 'in_progress' ? 'In Progress' : 'Completed' ?>
                    </div>
                </div>

                <div class="task-content">
                    <h4 class="task-title"><?= htmlspecialchars($task['todo_title']) ?></h4>
                    <p class="task-description">
                        <?= htmlspecialchars(substr($task['todo_description'], 0, 100)) ?>
                        <?= strlen($task['todo_description']) > 100 ? '...' : '' ?>
                    </p>

                    <div class="task-meta">
                        <div class="meta-item">
                            <i class="fas fa-cube"></i>
                            <span><?= htmlspecialchars($task['app_name']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span>Diambil oleh: <?= htmlspecialchars($task['user_name']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>Tanggal: <?= date('d/m/Y H:i', strtotime($task['date'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="task-actions">
                    <?php if ($task['user_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] == 'admin'): ?>
                        <?php if ($task['status'] == 'in_progress'): ?>
                        <button class="btn btn-success btn-sm" onclick="updateTaskStatus(<?= $task['id'] ?>, 'done')">
                            <i class="fas fa-check mr-1"></i>
                            Selesai
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-warning btn-sm" onclick="releaseTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['todo_title'], ENT_QUOTES) ?>')">
                            <i class="fas fa-times mr-1"></i>
                            Lepas Tugas
                        </button>
                    <?php else: ?>
                        <span class="text-muted">Task milik <?= htmlspecialchars($task['user_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Belum Ada Tugas Yang Diambil</h3>
            <p>Klik "Ambil Tugas Baru" untuk mulai mengerjakan tugas</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Available Tasks Modal -->
<div id="availableTasksModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Pilih Tugas Yang Tersedia</h3>
            <button class="modal-close" onclick="closeAvailableTasksModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <?php if ($available_result->num_rows > 0): ?>
            <div class="available-tasks-list">
                <?php 
                $available_result->data_seek(0);
                while($task = $available_result->fetch_assoc()): 
                ?>
                <div class="available-task-item priority-<?= $task['priority'] ?>">
                    <div class="task-info">
                        <div class="task-header-inline">
                            <h4 class="task-title-inline"><?= htmlspecialchars($task['title']) ?></h4>
                            <div class="priority-badge priority-<?= $task['priority'] ?>">
                                <i class="fas fa-<?= getPriorityIcon($task['priority']) ?>"></i>
                                <?= ucfirst($task['priority']) ?>
                            </div>
                        </div>
                        <p class="task-desc-inline">
                            <?= htmlspecialchars(substr($task['description'], 0, 120)) ?>
                            <?= strlen($task['description']) > 120 ? '...' : '' ?>
                        </p>
                        <div class="task-meta-inline">
                            <span class="meta-badge">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($task['app_name']) ?>
                            </span>
                            <span class="meta-badge">
                                <i class="fas fa-user"></i>
                                Dibuat oleh: <?= htmlspecialchars($task['creator_name']) ?>
                            </span>
                            <span class="meta-badge">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y', strtotime($task['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="task-action-inline">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="todo_id" value="<?= $task['id'] ?>">
                            <button type="submit" name="take_task" class="btn btn-primary btn-sm">
                                <i class="fas fa-hand-paper mr-1"></i>
                                Ambil Tugas
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state-modal">
                <i class="fas fa-check-double"></i>
                <h3>Semua Tugas Sudah Diambil</h3>
                <p>Tidak ada tugas yang tersedia saat ini</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Release Task Confirmation Modal -->
<div id="releaseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Lepas Tugas</h3>
            <p id="releaseMessage">Apakah Anda yakin ingin melepas tugas ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeReleaseModal()">
                Batal
            </button>
            <form id="releaseForm" method="POST" style="display: inline;">
                <input type="hidden" id="releaseTakenId" name="taken_id">
                <button type="submit" name="release_task" class="btn btn-warning">
                    <i class="fas fa-times mr-2"></i>Lepas Tugas
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Base Styles */
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
    display: flex;
    align-items: center;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}

/* Buttons */
.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.85rem;
}

.btn-primary {
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
}

.btn-success {
    background: linear-gradient(90deg, #10b981, #34d399);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(90deg, #059669, #10b981);
    transform: translateY(-2px);
}

.btn-warning {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(90deg, #d97706, #f59e0b);
    transform: translateY(-2px);
}

.btn-secondary {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.mr-1 { margin-right: 4px; }
.mr-2 { margin-right: 8px; }
.mr-3 { margin-right: 12px; }

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
.bg-gradient-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
.bg-gradient-purple { background: linear-gradient(135deg, #a855f7, #c084fc); color: white; }

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

/* Tasks Container */
.tasks-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.section-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
}

/* Tasks Grid */
.tasks-grid {
    padding: 24px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.task-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
}

.task-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.task-card.status-done {
    border-left: 4px solid #10b981;
}

.task-card.status-in_progress {
    border-left: 4px solid #f59e0b;
}

.task-card-header {
    padding: 16px 20px;
    background: #f9fafb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-priority {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.task-priority.priority-high { background: #ef4444; }
.task-priority.priority-medium { background: #f59e0b; }
.task-priority.priority-low { background: #10b981; }

.task-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.task-status.status-in_progress { background: #f59e0b; }
.task-status.status-done { background: #10b981; }

.task-content {
    padding: 20px;
}

.task-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.task-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 16px;
}

.task-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #9ca3af;
}

.meta-item i {
    width: 14px;
    font-size: 0.75rem;
}

.task-actions {
    padding: 16px 20px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    gap: 8px;
}

/* Empty State */
.empty-state {
    padding: 60px 24px;
    text-align: center;
    color: #9ca3af;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.2rem;
    color: #6b7280;
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

.modal-lg {
    max-width: 800px;
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

.warning-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
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
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 0 24px 24px;
    display: flex;
    justify-content: center;
    gap: 12px;
}

/* Available Tasks List */
.available-tasks-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.available-task-item {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    border-left-width: 4px;
}

.available-task-item.priority-high { border-left-color: #ef4444; }
.available-task-item.priority-medium { border-left-color: #f59e0b; }
.available-task-item.priority-low { border-left-color: #10b981; }

.available-task-item:hover {
    background: #f9fafb;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.task-info {
    flex: 1;
}

.task-header-inline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.task-title-inline {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.priority-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
    color: white;
}

.priority-badge.priority-high { background: #ef4444; }
.priority-badge.priority-medium { background: #f59e0b; }
.priority-badge.priority-low { background: #10b981; }

.task-desc-inline {
    color: #6b7280;
    font-size: 0.85rem;
    margin-bottom: 12px;
    line-height: 1.4;
}

.task-meta-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.meta-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: #f3f4f6;
    border-radius: 8px;
    font-size: 0.75rem;
    color: #6b7280;
}

.meta-badge i {
    font-size: 0.7rem;
}

.task-action-inline {
    margin-left: 16px;
}

.empty-state-modal {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.empty-state-modal i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-modal h3 {
    color: #6b7280;
    margin-bottom: 8px;
}

.text-muted {
    color: #9ca3af;
    font-size: 0.85rem;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .tasks-grid {
        grid-template-columns: 1fr;
        padding: 16px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .available-task-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .task-header-inline {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .task-meta-inline {
        flex-direction: column;
        gap: 4px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 10px;
        width: calc(100% - 20px);
    }
}
</style>

<script>
// Modal Functions
function openAvailableTasksModal() {
    document.getElementById('availableTasksModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAvailableTasksModal() {
    document.getElementById('availableTasksModal').classList.remove('show');
    document.body.style.overflow = '';
}

function releaseTask(takenId, taskTitle) {
    document.getElementById('releaseMessage').textContent = `Apakah Anda yakin ingin melepas tugas "${taskTitle}"? Tugas akan tersedia kembali untuk diambil orang lain.`;
    document.getElementById('releaseTakenId').value = takenId;
    document.getElementById('releaseModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReleaseModal() {
    document.getElementById('releaseModal').classList.remove('show');
    document.body.style.overflow = '';
}

function updateTaskStatus(takenId, status) {
    if (confirm('Apakah Anda yakin ingin mengubah status tugas ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="taken_id" value="${takenId}">
            <input type="hidden" name="status" value="${status}">
            <input type="hidden" name="update_status" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeAvailableTasksModal();
        closeReleaseModal();
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

    // Add loading state to buttons
    const takeTaskButtons = document.querySelectorAll('button[name="take_task"]');
    takeTaskButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Mengambil...';
            this.disabled = true;
        });
    });
});

// Add confirmation for taking tasks
function confirmTakeTask(form, taskTitle) {
    if (confirm(`Apakah Anda yakin ingin mengambil tugas "${taskTitle}"?`)) {
        const button = form.querySelector('button[name="take_task"]');
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Mengambil...';
        button.disabled = true;
        return true;
    }
    return false;
}
</script>