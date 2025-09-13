<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Periksa koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
// Debugging
if (isset($_POST['take_task'])) {
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session user_id: " . $_SESSION['user_id']);
}
// Handle CRUD Operations for Taken Tasks
$message = '';
$error = '';

// TAKE NEW TASK - User mengambil tugas
if (isset($_POST['take_task'])) {
    $todo_id = (int)$_POST['todo_id'];
    $user_id = $_SESSION['user_id'];
    
    // Debug info
    error_log("Attempting to take task: $todo_id by user: $user_id");
    
    // Check if task exists and is not taken
    $check_query = "
        SELECT t.id, t.title, tk.id as taken_id 
        FROM todos t 
        LEFT JOIN taken tk ON t.id = tk.id_todos 
        WHERE t.id = ?
    ";
    $check_stmt = $koneksi->prepare($check_query);
    $check_stmt->bind_param("i", $todo_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        $error = "Tugas tidak ditemukan!";
        error_log("Task not found: $todo_id");
    } else {
        $task_data = $check_result->fetch_assoc();
        if ($task_data['taken_id']) {
            $error = "Tugas ini sudah diambil oleh orang lain!";
            error_log("Task already taken: $todo_id");
        } else {
            // Insert into taken table
            $insert_stmt = $koneksi->prepare("INSERT INTO taken (id_todos, user_id, status, date) VALUES (?, ?, 'in_progress', NOW())");
            $insert_stmt->bind_param("ii", $todo_id, $user_id);
            
            if ($insert_stmt->execute()) {
                $message = "Tugas '" . htmlspecialchars($task_data['title']) . "' berhasil diambil!";
                error_log("Task taken successfully: $todo_id by user: $user_id");
                
                // Optional: Add to system logs if table exists
                $log_stmt = $koneksi->prepare("INSERT INTO system_logs (user_id, action, description, created_at) VALUES (?, 'TASK_TAKEN', ?, NOW())");
                if ($log_stmt) {
                    $log_desc = "User mengambil tugas: " . $task_data['title'];
                    $log_stmt->bind_param("is", $user_id, $log_desc);
                    $log_stmt->execute();
                }
            } else {
                $error = "Gagal mengambil tugas: " . $koneksi->error;
                error_log("Failed to take task: " . $koneksi->error);
            }
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
            $stmt = $koneksi->prepare("UPDATE taken SET status = ?, date = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $taken_id);
            
            if ($stmt->execute()) {
                $message = "Status tugas berhasil diperbarui menjadi " . ucfirst(str_replace('_', ' ', $status)) . "!";
                
                // Log activity
                $log_stmt = $koneksi->prepare("INSERT INTO system_logs (user_id, action, description, created_at) VALUES (?, 'TASK_STATUS_UPDATED', ?, NOW())");
                $log_desc = "User mengubah status tugas ID: " . $taken_id . " menjadi " . $status;
                $log_stmt->bind_param("is", $user_id, $log_desc);
                $log_stmt->execute();
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
                
                // Log activity
                $log_stmt = $koneksi->prepare("INSERT INTO system_logs (user_id, action, description, created_at) VALUES (?, 'TASK_RELEASED', ?, NOW())");
                $log_desc = "User melepas tugas ID: " . $taken_id;
                $log_stmt->bind_param("is", $user_id, $log_desc);
                $log_stmt->execute();
            } else {
                $error = "Gagal melepas tugas!";
            }
        } else {
            $error = "Anda tidak memiliki akses untuk melepas tugas ini!";
        }
    }
}

// Get taken tasks with detailed info - dengan filter user yang lebih baik
$filter_clause = "";
if ($_SESSION['user_role'] != 'admin') {
    // Non-admin hanya bisa lihat tugas mereka sendiri dan tugas yang sudah completed
    $filter_clause = " AND (tk.user_id = " . $_SESSION['user_id'] . " OR tk.status = 'done')";
}

$taken_query = "
    SELECT tk.*, 
           t.title as todo_title,
           t.description as todo_description,
           t.priority,
           u.name as user_name,
           a.name as app_name,
           t.created_at as todo_created_at,
           DATEDIFF(NOW(), tk.date) as days_taken
    FROM taken tk
    JOIN todos t ON tk.id_todos = t.id
    JOIN users u ON tk.user_id = u.id
    JOIN apps a ON t.app_id = a.id
    WHERE 1=1 $filter_clause
    ORDER BY tk.date DESC
";
$taken_result = $koneksi->query($taken_query);

// Get available tasks (not taken yet) - dengan prioritas lebih baik
$available_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as creator_name,
           CASE 
               WHEN t.priority = 'high' THEN 3
               WHEN t.priority = 'medium' THEN 2
               ELSE 1
           END as priority_order
    FROM todos t
    JOIN apps a ON t.app_id = a.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE tk.id IS NULL
    ORDER BY priority_order DESC, t.created_at DESC
    LIMIT 50
";
$available_result = $koneksi->query($available_query);

// Tambahkan debugging
if (!$available_result) {
    error_log("Available query error: " . $koneksi->error);
}

// Get statistics dengan data yang lebih akurat
$total_taken = $koneksi->query("SELECT COUNT(*) as count FROM taken")->fetch_assoc()['count'];
$in_progress = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'done'")->fetch_assoc()['count'];
$my_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc()['count'];
$available_count = $koneksi->query("
    SELECT COUNT(*) as count FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.id IS NULL
")->fetch_assoc()['count'];

function getPriorityIcon($priority) {
    return ['high' => 'exclamation-triangle', 'medium' => 'minus', 'low' => 'arrow-down'][$priority] ?? 'circle';
}

function getPriorityColor($priority) {
    return ['high' => '#ef4444', 'medium' => '#f59e0b', 'low' => '#10b981'][$priority] ?? '#6b7280';
}

function getStatusBadge($status) {
    $badges = [
        'in_progress' => ['text' => 'Sedang Dikerjakan', 'class' => 'badge-warning'],
        'done' => ['text' => 'Selesai', 'class' => 'badge-success'],
        'pending' => ['text' => 'Menunggu', 'class' => 'badge-info']
    ];
    return $badges[$status] ?? ['text' => ucfirst($status), 'class' => 'badge-secondary'];
}
?>

<div class="main-content" style="margin-top: 80px;">
    <!-- Success/Error Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
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
                Ambil dan kelola tugas yang tersedia untuk dikerjakan
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-info btn-outline" onclick="refreshPage()">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </button>
            <button class="btn btn-primary" onclick="openAvailableTasksModal()">
                <i class="fas fa-plus mr-2"></i>
                Ambil Tugas Baru
                <?php if($available_count > 0): ?>
                <span class="badge-count"><?= $available_count ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-item">
            <div class="stat-icon bg-blue">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $available_count ?></span>
                <span class="stat-label">Tersedia</span>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon bg-orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $in_progress ?></span>
                <span class="stat-label">Dikerjakan</span>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon bg-green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $completed ?></span>
                <span class="stat-label">Selesai</span>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon bg-purple">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $my_tasks ?></span>
                <span class="stat-label">Tugas Saya</span>
            </div>
        </div>
    </div>

    <!-- Tasks Container -->
    <div class="tasks-container">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-list-check mr-2"></i>
                Tasks Yang Sedang Dikerjakan
            </h3>
            <div class="section-actions">
                <select id="statusFilter" onchange="filterTasks()" class="form-select">
                    <option value="all">Semua Status</option>
                    <option value="in_progress">Sedang Dikerjakan</option>
                    <option value="done">Selesai</option>
                </select>
            </div>
        </div>

        <?php if ($taken_result->num_rows > 0): ?>
        <div class="tasks-grid" id="tasksGrid">
            <?php while($task = $taken_result->fetch_assoc()): 
                $status_badge = getStatusBadge($task['status']);
            ?>
            <div class="task-card status-<?= $task['status'] ?>" data-status="<?= $task['status'] ?>">
                <div class="task-card-header">
                    <div class="task-priority priority-<?= $task['priority'] ?>">
                        <i class="fas fa-<?= getPriorityIcon($task['priority']) ?>"></i>
                        <?= ucfirst($task['priority']) ?> Priority
                    </div>
                    <div class="task-status <?= $status_badge['class'] ?>">
                        <?= $status_badge['text'] ?>
                    </div>
                </div>

                <div class="task-content">
                    <h4 class="task-title"><?= htmlspecialchars($task['todo_title']) ?></h4>
                    <p class="task-description">
                        <?= htmlspecialchars(substr($task['todo_description'], 0, 120)) ?>
                        <?php if(strlen($task['todo_description']) > 120): ?>
                        <span class="read-more" onclick="toggleDescription(this)">... Lihat selengkapnya</span>
                        <span class="full-desc" style="display: none;">
                            <?= htmlspecialchars($task['todo_description']) ?>
                            <span class="read-less" onclick="toggleDescription(this)">Lihat lebih sedikit</span>
                        </span>
                        <?php endif; ?>
                    </p>

                    <div class="task-meta">
                        <div class="meta-row">
                            <div class="meta-item">
                                <i class="fas fa-cube"></i>
                                <span><?= htmlspecialchars($task['app_name']) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span>Dikerjakan: <?= htmlspecialchars($task['user_name']) ?></span>
                            </div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Diambil: <?= date('d/m/Y H:i', strtotime($task['date'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-hourglass-half"></i>
                                <span><?= $task['days_taken'] ?> hari</span>
                            </div>
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
                        <button class="btn btn-warning btn-sm" onclick="updateTaskStatus(<?= $task['id'] ?>, 'pending')">
                            <i class="fas fa-pause mr-1"></i>
                            Pending
                        </button>
                        <?php elseif ($task['status'] == 'done'): ?>
                        <button class="btn btn-info btn-sm" onclick="updateTaskStatus(<?= $task['id'] ?>, 'in_progress')">
                            <i class="fas fa-redo mr-1"></i>
                            Kerjakan Lagi
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-danger btn-sm" onclick="releaseTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['todo_title'], ENT_QUOTES) ?>')">
                            <i class="fas fa-times mr-1"></i>
                            Lepas Tugas
                        </button>
                    <?php else: ?>
                        <div class="task-owner-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Task milik <?= htmlspecialchars($task['user_name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>Belum Ada Tugas Yang Dikerjakan</h3>
            <p>Klik "Ambil Tugas Baru" untuk mulai mengerjakan tugas yang tersedia</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Available Tasks Modal -->
<div id="availableTasksModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>
                <i class="fas fa-clipboard-list mr-2"></i>
                Pilih Tugas Yang Tersedia
                <span class="task-count-badge"><?= $available_count ?> tugas</span>
            </h3>
            <button class="modal-close" onclick="closeAvailableTasksModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <?php if ($available_result->num_rows > 0): ?>
            <div class="modal-filters">
                <select id="priorityFilter" onchange="filterAvailableTasks()" class="form-select">
                    <option value="all">Semua Prioritas</option>
                    <option value="high">High Priority</option>
                    <option value="medium">Medium Priority</option>
                    <option value="low">Low Priority</option>
                </select>
            </div>
            
            <div class="available-tasks-list" id="availableTasksList">
                <?php 
                $available_result->data_seek(0);
                while($task = $available_result->fetch_assoc()): 
                ?>
                <div class="available-task-item priority-<?= $task['priority'] ?>" data-priority="<?= $task['priority'] ?>">
                    <div class="task-info">
                        <div class="task-header-inline">
                            <h4 class="task-title-inline"><?= htmlspecialchars($task['title']) ?></h4>
                            <div class="priority-badge priority-<?= $task['priority'] ?>">
                                <i class="fas fa-<?= getPriorityIcon($task['priority']) ?>"></i>
                                <?= ucfirst($task['priority']) ?>
                            </div>
                        </div>
                        <p class="task-desc-inline">
                            <?= htmlspecialchars(substr($task['description'], 0, 150)) ?>
                            <?= strlen($task['description']) > 150 ? '...' : '' ?>
                        </p>
                        <div class="task-meta-inline">
                            <span class="meta-badge">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($task['app_name']) ?>
                            </span>
                            <span class="meta-badge">
                                <i class="fas fa-user"></i>
                                Dibuat: <?= htmlspecialchars($task['creator_name']) ?>
                            </span>
                            <span class="meta-badge">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y', strtotime($task['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="task-action-inline">
                        <form method="POST" style="display: inline;" onsubmit="return confirmTakeTask(this, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>')">
                            <input type="hidden" name="todo_id" value="<?= $task['id'] ?>">
                            <button type="submit" name="take_task" class="btn btn-primary btn-sm take-task-btn">
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
                <p>Tidak ada tugas yang tersedia saat ini. Coba lagi nanti!</p>
                <button class="btn btn-primary" onclick="refreshPage()">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh Halaman
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($available_result->num_rows > 0): ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAvailableTasksModal()">
                Tutup
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Release Task Confirmation Modal -->
<div id="releaseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header text-center">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Konfirmasi Lepas Tugas</h3>
            <p id="releaseMessage">Apakah Anda yakin ingin melepas tugas ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeReleaseModal()">
                <i class="fas fa-times mr-2"></i>
                Batal
            </button>
            <form id="releaseForm" method="POST" style="display: inline;">
                <input type="hidden" id="releaseTakenId" name="taken_id">
                <button type="submit" name="release_task" class="btn btn-warning">
                    <i class="fas fa-hand-paper mr-2"></i>
                    Ya, Lepas Tugas
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Enhanced Styles */
* {
    box-sizing: border-box;
}

.main-content {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Alert Styles */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease;
    position: relative;
}

.alert-success {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
    border: 1px solid #86efac;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #dc2626;
    border: 1px solid #f87171;
}

.alert-close {
    position: absolute;
    right: 16px;
    background: none;
    border: none;
    color: currentColor;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.alert-close:hover {
    opacity: 1;
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #ffffff, #f8fafc);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #e2e8f0;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.page-subtitle {
    color: #6b7280;
    font-size: 1rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

/* Quick Stats */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-item {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.3s ease;
    border: 1px solid #e2e8f0;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
}

.bg-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.bg-green { background: linear-gradient(135deg, #10b981, #059669); }
.bg-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.stat-details {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 4px;
}

/* Tasks Container */
.tasks-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.section-header {
    padding: 24px 32px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f9fafb, #f3f4f6);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
}

.section-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

/* Form Select */
.form-select {
    padding: 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Tasks Grid */
.tasks-grid {
    padding: 32px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
    gap: 24px;
}

/* Task Card */
.task-card {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.task-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.task-card.status-done {
    border-left: 4px solid #10b981;
}

.task-card.status-in_progress {
    border-left: 4px solid #f59e0b;
}

.task-card-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, #f9fafb, #f1f5f9);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
}

.task-priority {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
}

.task-priority.priority-high { background: linear-gradient(135deg, #ef4444, #dc2626); }
.task-priority.priority-medium { background: linear-gradient(135deg, #f59e0b, #d97706); }
.task-priority.priority-low { background: linear-gradient(135deg, #10b981, #059669); }

.badge-success { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
.badge-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
.badge-info { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
.badge-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }

.task-content {
    padding: 24px;
}

.task-title {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.4;
}

.task-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 20px;
}

.read-more, .read-less {
    color: #3b82f6;
    cursor: pointer;
    text-decoration: underline;
    font-weight: 500;
}

.task-meta {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
    flex: 1;
}

.meta-item i {
    width: 16px;
    font-size: 0.8rem;
    color: #9ca3af;
}

.task-actions {
    padding: 20px 24px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.task-owner-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 0.9rem;
    padding: 8px 12px;
    background: #f9fafb;
    border-radius: 8px;
}

/* Buttons */
.btn {
    padding: 10px 18px;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.btn-sm {
    padding: 8px 14px;
    font-size: 0.85rem;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-success:hover:not(:disabled) {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover:not(:disabled) {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: translateY(-2px);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover:not(:disabled) {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
}

.btn-info {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: white;
}

.btn-info:hover:not(:disabled) {
    background: linear-gradient(135deg, #0891b2, #0e7490);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 2px solid #06b6d4;
    color: #06b6d4;
}

.btn-outline:hover:not(:disabled) {
    background: #06b6d4;
    color: white;
}

.btn-secondary {
    background: #f8fafc;
    color: #64748b;
    border: 2px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.badge-count {
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 6px;
    font-weight: 600;
}

.mr-1 { margin-right: 4px; }
.mr-2 { margin-right: 8px; }
.mr-3 { margin-right: 12px; }

/* Empty State */
.empty-state {
    padding: 80px 32px;
    text-align: center;
    color: #9ca3af;
}

.empty-state i {
    font-size: 5rem;
    margin-bottom: 24px;
    opacity: 0.4;
    color: #d1d5db;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #6b7280;
    margin-bottom: 12px;
    font-weight: 600;
}

.empty-state p {
    font-size: 1rem;
    margin-bottom: 24px;
    line-height: 1.6;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
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
    border-radius: 20px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    animation: slideUp 0.3s ease;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-lg {
    max-width: 900px;
}

.modal-header {
    padding: 28px 32px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.modal-header h3 {
    font-size: 1.4rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.task-count-badge {
    background: #3b82f6;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 8px;
}

.text-center { text-align: center; }

.warning-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px 32px;
    flex: 1;
    overflow-y: auto;
}

.modal-footer {
    padding: 20px 32px 28px;
    display: flex;
    justify-content: center;
    gap: 12px;
    border-top: 1px solid #f3f4f6;
}

/* Modal Filters */
.modal-filters {
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    align-items: center;
}

.modal-filters .form-select {
    min-width: 180px;
}

/* Available Tasks List */
.available-tasks-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 4px;
}

.available-task-item {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: all 0.3s ease;
    border-left-width: 4px;
    background: white;
}

.available-task-item.priority-high { border-left-color: #ef4444; }
.available-task-item.priority-medium { border-left-color: #f59e0b; }
.available-task-item.priority-low { border-left-color: #10b981; }

.available-task-item:hover {
    background: #f9fafb;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.task-info {
    flex: 1;
    margin-right: 16px;
}

.task-header-inline {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.task-title-inline {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.4;
    flex: 1;
}

.priority-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 0.7rem;
    font-weight: 500;
    color: white;
    flex-shrink: 0;
}

.priority-badge.priority-high { background: #ef4444; }
.priority-badge.priority-medium { background: #f59e0b; }
.priority-badge.priority-low { background: #10b981; }

.task-desc-inline {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 16px;
    line-height: 1.5;
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
    padding: 3px 8px;
    background: #f3f4f6;
    border-radius: 12px;
    font-size: 0.75rem;
    color: #6b7280;
}

.meta-badge i {
    font-size: 0.7rem;
    width: 10px;
}

.task-action-inline {
    flex-shrink: 0;
}

.empty-state-modal {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state-modal i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.4;
    color: #d1d5db;
}

.empty-state-modal h3 {
    color: #6b7280;
    margin-bottom: 12px;
    font-size: 1.3rem;
}

.empty-state-modal p {
    margin-bottom: 24px;
    font-size: 1rem;
    line-height: 1.6;
}

/* Animations */
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Loading States */
.loading {
    position: relative;
    color: transparent !important;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    color: white;
}

@keyframes spin {
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .tasks-grid {
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 20px;
        padding: 24px;
    }
    
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 16px;
    }
    
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
        padding: 24px;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .tasks-grid {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .quick-stats {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .stat-item {
        padding: 20px;
    }
    
    .meta-row {
        flex-direction: column;
        gap: 8px;
    }
    
    .task-actions {
        flex-direction: column;
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
        gap: 6px;
    }
    
    .modal-content {
        margin: 10px;
        width: calc(100% - 20px);
        max-height: calc(100vh - 20px);
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-header {
        padding: 20px 20px 0;
    }
    
    .modal-footer {
        padding: 16px 20px 20px;
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.6rem;
    }
    
    .tasks-grid {
        padding: 16px;
    }
    
    .task-content, .task-actions {
        padding: 16px 20px;
    }
    
    .section-header {
        padding: 20px;
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
}

/* Custom Scrollbar */
.available-tasks-list::-webkit-scrollbar {
    width: 6px;
}

.available-tasks-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.available-tasks-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.available-tasks-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Focus States */
.btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Print Styles */
@media print {
    .modal, .btn, .alert {
        display: none !important;
    }
    
    .task-card {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<script>
// Enhanced JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeTaskManagement();
});

function initializeTaskManagement() {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentElement) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });

    // Initialize tooltips and interactions
    addInteractiveFeatures();
    
    // Setup keyboard shortcuts
    setupKeyboardShortcuts();
}

// Modal Functions
function openAvailableTasksModal() {
    const modal = document.getElementById('availableTasksModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Focus on first task for accessibility
    setTimeout(() => {
        const firstTask = modal.querySelector('.take-task-btn');
        if (firstTask) firstTask.focus();
    }, 300);
}

function closeAvailableTasksModal() {
    const modal = document.getElementById('availableTasksModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

function releaseTask(takenId, taskTitle) {
    const cleanTitle = taskTitle.replace(/'/g, "\\'");
    document.getElementById('releaseMessage').innerHTML = 
        `Apakah Anda yakin ingin melepas tugas "<strong>${cleanTitle}</strong>"?<br>
        <small class="text-muted">Tugas akan tersedia kembali untuk diambil orang lain.</small>`;
    document.getElementById('releaseTakenId').value = takenId;
    document.getElementById('releaseModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReleaseModal() {
    document.getElementById('releaseModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Task Status Update
function updateTaskStatus(takenId, status) {
    const statusText = {
        'done': 'selesai',
        'in_progress': 'sedang dikerjakan',
        'pending': 'pending'
    };
    
    if (confirm(`Apakah Anda yakin ingin mengubah status tugas menjadi "${statusText[status]}"?`)) {
        showLoadingState();
        
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

// Enhanced Task Taking with confirmation
function confirmTakeTask(form, taskTitle) {
    const cleanTitle = taskTitle.replace(/'/g, "\\'");
    
    if (confirm(`Apakah Anda yakin ingin mengambil tugas:\n"${cleanTitle}"?\n\nTugas ini akan menjadi tanggung jawab Anda.`)) {
        const button = form.querySelector('button[name="take_task"]');
        
        // Add loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Mengambil tugas...';
        button.disabled = true;
        button.classList.add('loading');
        
        // Close modal after short delay for UX
        setTimeout(() => {
            closeAvailableTasksModal();
        }, 500);
        
        return true;
    }
    return false;
}

// Filter Functions
function filterTasks() {
    const filter = document.getElementById('statusFilter').value;
    const tasks = document.querySelectorAll('.task-card');
    
    tasks.forEach(task => {
        const status = task.getAttribute('data-status');
        if (filter === 'all' || status === filter) {
            task.style.display = 'block';
            task.style.animation = 'slideDown 0.3s ease';
        } else {
            task.style.display = 'none';
        }
    });
}

function filterAvailableTasks() {
    const filter = document.getElementById('priorityFilter').value;
    const tasks = document.querySelectorAll('.available-task-item');
    
    tasks.forEach(task => {
        const priority = task.getAttribute('data-priority');
        if (filter === 'all' || priority === filter) {
            task.style.display = 'flex';
            task.style.animation = 'slideDown 0.3s ease';
        } else {
            task.style.display = 'none';
        }
    });
}

// Description Toggle
function toggleDescription(element) {
    const parent = element.closest('.task-description');
    const fullDesc = parent.querySelector('.full-desc');
    const readMore = parent.querySelector('.read-more');
    
    if (element.classList.contains('read-more')) {
        readMore.style.display = 'none';
        fullDesc.style.display = 'inline';
    } else {
        readMore.style.display = 'inline';
        fullDesc.style.display = 'none';
    }
}

// Utility Functions
function refreshPage() {
    showLoadingState();
    location.reload();
}

function showLoadingState() {
    // Add loading overlay
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Memuat...</p>
        </div>
    `;
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        flex-direction: column;
        gap: 12px;
    `;
    document.body.appendChild(overlay);
}

// Interactive Features
function addInteractiveFeatures() {
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', createRipple);
    });
    
    // Add task card hover effects
    const taskCards = document.querySelectorAll('.task-card');
    taskCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

function createRipple(event) {
    const button = event.currentTarget;
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    const radius = diameter / 2;
    
    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
    circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
    circle.classList.add('ripple');
    
    const ripple = button.querySelector('.ripple');
    if (ripple) ripple.remove();
    
    button.appendChild(circle);
}

// Keyboard Shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N: Open new task modal
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            openAvailableTasksModal();
        }
        
        // Escape: Close modals
        if (e.key === 'Escape') {
            closeAvailableTasksModal();
            closeReleaseModal();
        }
        
        // Ctrl/Cmd + R: Refresh
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            refreshPage();
        }
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeAvailableTasksModal();
        closeReleaseModal();
    }
});

// Auto-refresh every 5 minutes when page is visible
setInterval(() => {
    if (document.visibilityState === 'visible' && !document.querySelector('.modal.show')) {
        // Check for new available tasks silently
        checkForNewTasks();
    }
}, 300000);

function checkForNewTasks() {
    // This could be implemented with AJAX to check for new tasks without full refresh
    console.log('Checking for new tasks...');
}

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.4);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>