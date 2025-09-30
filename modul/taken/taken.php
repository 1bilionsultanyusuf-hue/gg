<?php
// Handle CRUD Operations for Taken (Todo Assignments)
$message = '';
$error = '';
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// TAKE - Assign todo to user
if (isset($_POST['take_todo'])) {
    $todo_id = (int)$_POST['todo_id'];
    
    if (!$current_user_id) {
        $error = "Session expired! Silakan login kembali.";
    } else {
        $date = date('Y-m-d');
        
        $check_stmt = $koneksi->prepare("SELECT id, status FROM taken WHERE id_todos = ? AND status IN ('in_progress', 'done')");
        $check_stmt->bind_param("i", $todo_id);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->fetch_assoc();
        
        if (!$exists) {
            $check_released = $koneksi->prepare("SELECT id FROM taken WHERE id_todos = ? AND status = 'released'");
            $check_released->bind_param("i", $todo_id);
            $check_released->execute();
            $released = $check_released->get_result()->fetch_assoc();
            
            if ($released) {
                $stmt = $koneksi->prepare("UPDATE taken SET status = 'in_progress', taken_by = ?, date = ? WHERE id = ?");
                $stmt->bind_param("isi", $current_user_id, $date, $released['id']);
            } else {
                $stmt = $koneksi->prepare("INSERT INTO taken (id_todos, date, status, taken_by) VALUES (?, ?, 'in_progress', ?)");
                $stmt->bind_param("isi", $todo_id, $date, $current_user_id);
            }
            
            if ($stmt->execute()) {
                $message = "Todo berhasil diambil dan masuk ke In Progress!";
            } else {
                $error = "Gagal mengambil todo: " . $stmt->error;
            }
        } else {
            $error = "Todo sudah diambil oleh user lain!";
        }
    }
}

// UPDATE STATUS
if (isset($_POST['update_status'])) {
    $taken_id = (int)$_POST['taken_id'];
    $status = trim($_POST['status']);
    
    if (!$current_user_id) {
        $error = "Session expired! Silakan login kembali.";
    } else {
        if (in_array($status, ['in_progress', 'done'])) {
            $verify_stmt = $koneksi->prepare("SELECT taken_by FROM taken WHERE id = ?");
            $verify_stmt->bind_param("i", $taken_id);
            $verify_stmt->execute();
            $taken = $verify_stmt->get_result()->fetch_assoc();
            
            if ($taken && (int)$taken['taken_by'] == $current_user_id) {
                $stmt = $koneksi->prepare("UPDATE taken SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $taken_id);
                
                if ($stmt->execute()) {
                    $message = $status == 'done' ? "Todo selesai! Great job! 🎉" : "Status berhasil diubah!";
                } else {
                    $error = "Gagal memperbarui status: " . $stmt->error;
                }
            } else {
                $error = "Anda tidak memiliki akses!";
            }
        }
    }
}

// RELEASE
if (isset($_POST['release_todo'])) {
    $taken_id = (int)$_POST['taken_id'];
    
    if (!$current_user_id) {
        $error = "Session expired! Silakan login kembali.";
    } else {
        $verify_stmt = $koneksi->prepare("SELECT taken_by FROM taken WHERE id = ?");
        $verify_stmt->bind_param("i", $taken_id);
        $verify_stmt->execute();
        $taken = $verify_stmt->get_result()->fetch_assoc();
        
        if ($taken && (int)$taken['taken_by'] == $current_user_id) {
            $stmt = $koneksi->prepare("UPDATE taken SET status = 'released' WHERE id = ?");
            $stmt->bind_param("i", $taken_id);
            
            if ($stmt->execute()) {
                $message = "Todo berhasil dilepas kembali ke pool!";
            } else {
                $error = "Gagal melepas todo: " . $stmt->error;
            }
        } else {
            $error = "Anda tidak memiliki akses!";
        }
    }
}

// Get Available Todos
$available_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as creator_name
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN taken tk ON t.id = tk.id_todos AND tk.status IN ('in_progress', 'done')
    WHERE tk.id IS NULL
    ORDER BY 
        t.priority = 'high' DESC,
        t.priority = 'medium' DESC,
        t.created_at DESC
    LIMIT 20
";
$available_result = $koneksi->query($available_query);

// Get My In Progress Todos
$my_progress_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as creator_name,
           tk.id as taken_id,
           tk.date as taken_date
    FROM todos t
    INNER JOIN taken tk ON t.id = tk.id_todos
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE tk.taken_by = ? AND tk.status = 'in_progress'
    ORDER BY tk.date DESC
";
$progress_stmt = $koneksi->prepare($my_progress_query);
$progress_stmt->bind_param("i", $current_user_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();

// Get My Completed Todos
$my_completed_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as creator_name,
           tk.id as taken_id,
           tk.date as taken_date
    FROM todos t
    INNER JOIN taken tk ON t.id = tk.id_todos
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE tk.taken_by = ? AND tk.status = 'done'
    ORDER BY tk.date DESC
    LIMIT 10
";
$completed_stmt = $koneksi->prepare($my_completed_query);
$completed_stmt->bind_param("i", $current_user_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();

// Statistics
$stats = [
    'available' => $koneksi->query("
        SELECT COUNT(DISTINCT t.id) as count FROM todos t 
        LEFT JOIN taken tk ON t.id = tk.id_todos AND tk.status IN ('in_progress', 'done')
        WHERE tk.id IS NULL
    ")->fetch_assoc()['count'],
    
    'my_progress' => $koneksi->query("
        SELECT COUNT(*) as count FROM taken 
        WHERE taken_by = $current_user_id AND status = 'in_progress'
    ")->fetch_assoc()['count'],
    
    'my_completed' => $koneksi->query("
        SELECT COUNT(*) as count FROM taken 
        WHERE taken_by = $current_user_id AND status = 'done'
    ")->fetch_assoc()['count'],
];

function getPriorityBadge($priority) {
    $badges = [
        'high' => '<span class="priority-badge high"><i class="fas fa-flag"></i> High</span>',
        'medium' => '<span class="priority-badge medium"><i class="fas fa-flag"></i> Medium</span>',
        'low' => '<span class="priority-badge low"><i class="fas fa-flag"></i> Low</span>'
    ];
    return $badges[$priority] ?? '';
}
?>

<div class="kanban-container" style="margin-top: 80px;">
    <!-- Messages -->
    <?php if ($message): ?>
    <div class="toast toast-success">
        <i class="fas fa-check-circle"></i>
        <span><?= $message ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="toast toast-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="kanban-header">
        <div>
            <h1 class="kanban-title">
                <i class="fas fa-clipboard-check"></i>
                Task Board
            </h1>
            <p class="kanban-subtitle">Kelola tugas Anda dengan sistem Kanban</p>
        </div>
        <div class="header-stats">
            <div class="stat-chip">
                <i class="fas fa-circle"></i>
                <span><?= $stats['available'] ?> Available</span>
            </div>
            <div class="stat-chip active">
                <i class="fas fa-spinner"></i>
                <span><?= $stats['my_progress'] ?> In Progress</span>
            </div>
            <div class="stat-chip done">
                <i class="fas fa-check"></i>
                <span><?= $stats['my_completed'] ?> Done</span>
            </div>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-board">
        <!-- Column 1: Available -->
        <div class="kanban-column">
            <div class="column-header available">
                <div class="column-title">
                    <i class="fas fa-inbox"></i>
                    <h3>Available Tasks</h3>
                </div>
                <span class="column-count"><?= $available_result->num_rows ?></span>
            </div>
            <div class="column-content">
                <?php if ($available_result->num_rows > 0): ?>
                    <?php while($todo = $available_result->fetch_assoc()): ?>
                    <div class="task-card">
                        <div class="card-header">
                            <?= getPriorityBadge($todo['priority']) ?>
                            <span class="app-badge">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($todo['app_name']) ?>
                            </span>
                        </div>
                        <h4 class="card-title"><?= htmlspecialchars($todo['title']) ?></h4>
                        <p class="card-description"><?= htmlspecialchars($todo['description']) ?></p>
                        <div class="card-meta">
                            <span class="meta-item">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($todo['creator_name']) ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <?= date('d M Y', strtotime($todo['created_at'])) ?>
                            </span>
                        </div>
                        <form method="POST" class="card-action">
                            <input type="hidden" name="todo_id" value="<?= $todo['id'] ?>">
                            <button type="submit" name="take_todo" class="btn-take-card">
                                <i class="fas fa-hand-paper"></i>
                                Ambil Task
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada task tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Column 2: In Progress -->
        <div class="kanban-column">
            <div class="column-header progress">
                <div class="column-title">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h3>In Progress</h3>
                </div>
                <span class="column-count"><?= $progress_result->num_rows ?></span>
            </div>
            <div class="column-content">
                <?php if ($progress_result->num_rows > 0): ?>
                    <?php while($todo = $progress_result->fetch_assoc()): ?>
                    <div class="task-card my-card">
                        <div class="card-header">
                            <?= getPriorityBadge($todo['priority']) ?>
                            <span class="app-badge">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($todo['app_name']) ?>
                            </span>
                        </div>
                        <h4 class="card-title"><?= htmlspecialchars($todo['title']) ?></h4>
                        <p class="card-description"><?= htmlspecialchars($todo['description']) ?></p>
                        <div class="card-meta">
                            <span class="meta-item">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($todo['creator_name']) ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                Diambil: <?= date('d M Y', strtotime($todo['taken_date'])) ?>
                            </span>
                        </div>
                        <div class="card-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="taken_id" value="<?= $todo['taken_id'] ?>">
                                <input type="hidden" name="status" value="done">
                                <button type="submit" name="update_status" class="btn-complete-card">
                                    <i class="fas fa-check"></i>
                                    Selesai
                                </button>
                            </form>
                            <button class="btn-release-card" onclick="confirmRelease(<?= $todo['taken_id'] ?>, '<?= htmlspecialchars($todo['title'], ENT_QUOTES) ?>')">
                                <i class="fas fa-times"></i>
                                Lepas
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-hourglass-half"></i>
                        <p>Belum ada task dikerjakan</p>
                        <small>Ambil task dari kolom Available</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Column 3: Completed -->
        <div class="kanban-column">
            <div class="column-header done">
                <div class="column-title">
                    <i class="fas fa-check-circle"></i>
                    <h3>Completed</h3>
                </div>
                <span class="column-count"><?= $completed_result->num_rows ?></span>
            </div>
            <div class="column-content">
                <?php if ($completed_result->num_rows > 0): ?>
                    <?php while($todo = $completed_result->fetch_assoc()): ?>
                    <div class="task-card completed-card">
                        <div class="card-header">
                            <?= getPriorityBadge($todo['priority']) ?>
                            <span class="app-badge">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($todo['app_name']) ?>
                            </span>
                        </div>
                        <h4 class="card-title"><?= htmlspecialchars($todo['title']) ?></h4>
                        <p class="card-description"><?= htmlspecialchars($todo['description']) ?></p>
                        <div class="card-meta">
                            <span class="meta-item">
                                <i class="fas fa-check-double"></i>
                                Selesai: <?= date('d M Y', strtotime($todo['taken_date'])) ?>
                            </span>
                        </div>
                        <div class="completed-badge">
                            <i class="fas fa-trophy"></i>
                            Completed
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <p>Belum ada task selesai</p>
                        <small>Selesaikan task untuk melihatnya di sini</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Release Modal -->
<div id="releaseModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="modal-title">Lepas Task?</h3>
        <p class="modal-text" id="releaseText">Task akan kembali ke Available dan bisa diambil orang lain</p>
        <form id="releaseForm" method="POST" class="modal-actions">
            <input type="hidden" id="releaseId" name="taken_id">
            <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
            <button type="submit" name="release_todo" class="btn-modal-confirm">Ya, Lepas</button>
        </form>
    </div>
</div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.kanban-container {
    padding: 20px;
    background: linear-gradient(135deg, #3b82f6 0%, #ffffff 100%);
    min-height: 100vh;
}

/* Toast Messages */
.toast {
    position: fixed;
    top: 100px;
    right: 20px;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1000;
    animation: slideInRight 0.3s ease;
    max-width: 400px;
}

.toast-success {
    background: white;
    color: #10b981;
    border-left: 4px solid #10b981;
}

.toast-error {
    background: white;
    color: #ef4444;
    border-left: 4px solid #ef4444;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Header */
.kanban-header {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.kanban-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 4px;
}

.kanban-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
}

.header-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.stat-chip {
    padding: 8px 16px;
    border-radius: 20px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat-chip.active {
    background: #dbeafe;
    color: #2563eb;
}

.stat-chip.done {
    background: #d1fae5;
    color: #059669;
}

/* Kanban Board */
.kanban-board {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
}

.kanban-column {
    background: rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 16px;
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 240px);
}

.column-header {
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.column-header.available {
    background: linear-gradient(135deg, #64748b, #475569);
}

.column-header.progress {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.column-header.done {
    background: linear-gradient(135deg, #10b981, #059669);
}

.column-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

.column-title h3 {
    font-size: 1.1rem;
    font-weight: 600;
}

.column-count {
    background: rgba(255,255,255,0.3);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
}

.column-content {
    flex: 1;
    overflow-y: auto;
    padding-right: 4px;
}

.column-content::-webkit-scrollbar {
    width: 6px;
}

.column-content::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
}

.column-content::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
}

/* Task Cards */
.task-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.task-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.task-card.my-card {
    border-left: 4px solid #3b82f6;
}

.task-card.completed-card {
    opacity: 0.8;
    border-left: 4px solid #10b981;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}

.priority-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.priority-badge.high {
    background: #fee2e2;
    color: #dc2626;
}

.priority-badge.medium {
    background: #fef3c7;
    color: #d97706;
}

.priority-badge.low {
    background: #d1fae5;
    color: #059669;
}

.app-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    background: #f3f4f6;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.card-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    line-height: 1.4;
}

.card-description {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.meta-item {
    font-size: 0.8rem;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 6px;
}

.card-action {
    margin-top: 12px;
}

.btn-take-card {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-take-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(59,130,246,0.4);
}

.card-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.btn-complete-card {
    flex: 1;
    padding: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.9rem;
}

.btn-complete-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}

.btn-release-card {
    flex: 1;
    padding: 10px;
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.9rem;
}

.btn-release-card:hover {
    background: #fee2e2;
    color: #dc2626;
}

.completed-badge {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 8px;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 12px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: rgba(255,255,255,0.7);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 12px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 4px;
}

.empty-state small {
    font-size: 0.85rem;
    opacity: 0.7;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 2000;
    padding: 20px;
    align-items: center;
    justify-content: center;
}

.modal-overlay.show {
    display: flex;
}

.modal-box {
    background: white;
    border-radius: 16px;
    padding: 32px;
    max-width: 400px;
    width: 100%;
    text-align: center;
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.modal-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.modal-icon.warning {
    background: #fef3c7;
    color: #d97706;
}

.modal-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
}

.modal-text {
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    gap: 12px;
}

.btn-modal-cancel {
    flex: 1;
    padding: 12px;
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-modal-cancel:hover {
    background: #e5e7eb;
}

.btn-modal-confirm {
    flex: 1;
    padding: 12px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-modal-confirm:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(245,158,11,0.4);
}

/* Responsive */
@media (max-width: 1024px) {
    .kanban-board {
        grid-template-columns: 1fr;
    }
    
    .kanban-column {
        max-height: 500px;
    }
}

@media (max-width: 768px) {
    .kanban-container {
        padding: 12px;
    }
    
    .kanban-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .kanban-title {
        font-size: 1.5rem;
    }
    
    .header-stats {
        width: 100%;
        justify-content: space-between;
    }
    
    .stat-chip {
        flex: 1;
        justify-content: center;
    }
    
    .toast {
        right: 12px;
        left: 12px;
        max-width: none;
    }
}

@media (max-width: 480px) {
    .card-actions {
        flex-direction: column;
    }
    
    .btn-complete-card,
    .btn-release-card {
        width: 100%;
    }
    
    .header-stats {
        flex-direction: column;
    }
    
    .stat-chip {
        width: 100%;
    }
}
</style>

<script>
function confirmRelease(takenId, title) {
    const modal = document.getElementById('releaseModal');
    const text = document.getElementById('releaseText');
    const input = document.getElementById('releaseId');
    
    if (modal && text && input) {
        text.innerHTML = `Task "<strong>${title}</strong>" akan kembali ke Available dan bisa diambil orang lain`;
        input.value = takenId;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    const modal = document.getElementById('releaseModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking overlay
window.addEventListener('click', function(e) {
    const modal = document.getElementById('releaseModal');
    if (e.target === modal) {
        closeModal();
    }
});

// Close modal with ESC key
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Auto-hide toasts
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(function(toast) {
        setTimeout(function() {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
    });
});

// Prevent form resubmission
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Add slide out animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>