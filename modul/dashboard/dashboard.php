<?php
// Get real-time statistics from database dengan integrasi taken
$total_apps = $koneksi->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'];
$total_users = $koneksi->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];

// Get active tasks (from taken table with status 'in_progress')
$active_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'in_progress'")->fetch_assoc()['count'];

// Get completed tasks (from taken table with status 'done')
$completed_tasks = $koneksi->query("SELECT COUNT(*) as count FROM taken WHERE status = 'done'")->fetch_assoc()['count'];

// Get available tasks (todos yang belum di-take)
$available_tasks_query = "
    SELECT COUNT(*) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.id IS NULL
";
$available_tasks = $koneksi->query($available_tasks_query)->fetch_assoc()['count'];

// Get NEW tasks (created in last 24 hours and NOT TAKEN yet)
$new_tasks_query = "
    SELECT COUNT(*) as count 
    FROM todos t 
    LEFT JOIN taken tk ON t.id = tk.id_todos 
    WHERE tk.id IS NULL 
    AND t.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
";
$new_tasks_count = $koneksi->query($new_tasks_query)->fetch_assoc()['count'];

// PAGINATION SETUP - CHANGED TO 5 ITEMS PER PAGE
$items_per_page = 5;
$current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Calculate total pages (maximum 10 pages, showing 50 tasks total)
$max_pages = 10;
$total_items = min($available_tasks, $max_pages * $items_per_page); // Max 50 tasks
$total_pages = $available_tasks > 0 ? min(ceil($available_tasks / $items_per_page), $max_pages) : 1;

// Get recent todos with PAGINATION - ONLY SHOW AVAILABLE (NOT TAKEN) TASKS
$recent_todos = $koneksi->query("
    SELECT t.*, a.name as app_name, u.name as user_name,
           CASE 
               WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1
               ELSE 0
           END as is_new
    FROM todos t 
    LEFT JOIN apps a ON t.app_id = a.id 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN taken tk ON t.id = tk.id_todos
    WHERE tk.id IS NULL
    ORDER BY t.created_at DESC 
    LIMIT $items_per_page OFFSET $offset
");

function getPriorityIcon($priority) {
    $icons = [
        'high' => 'exclamation-triangle',
        'medium' => 'minus-circle',
        'low' => 'arrow-down'
    ];
    return $icons[$priority] ?? 'circle';
}
?>

<div class="dashboard-container">
    <!-- Simple Notification Bar (Only if there are NEW tasks) -->
    <?php if ($new_tasks_count > 0): ?>
    <div class="notification-simple">
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
            <span class="notification-count"><?= $new_tasks_count ?></span>
        </div>
        <div class="notification-text">
            <strong><?= $new_tasks_count ?></strong> tugas baru tersedia
        </div>
    </div>
    <?php endif; ?>

    <!-- Task List Section - Styled like Todos page -->
    <div class="todos-container">
        <div class="section-header">
            <div class="section-title-wrapper">
                <h2 class="section-title">
                    <i class="fas fa-tasks"></i>
                    Tugas Tersedia
                </h2>
                <span class="section-count"><?= $available_tasks ?> tugas</span>
                <?php if ($new_tasks_count > 0): ?>
                <span class="section-count-new">
                    <i class="fas fa-sparkles"></i>
                    <?= $new_tasks_count ?> baru
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="todos-list">
            <?php if ($recent_todos->num_rows > 0): ?>
                <?php while($todo = $recent_todos->fetch_assoc()): ?>
                <div class="todo-list-item">
                    <div class="todo-priority-container">
                        <div class="todo-priority-badge priority-<?= $todo['priority'] ?>">
                            <i class="fas fa-<?= getPriorityIcon($todo['priority']) ?>"></i>
                        </div>
                    </div>
                    
                    <div class="todo-list-content">
                        <div class="todo-list-main">
                            <h3 class="todo-list-title">
                                <?= htmlspecialchars($todo['title']) ?>
                                <?php if ($todo['is_new'] == 1): ?>
                                <span class="badge-new">NEW</span>
                                <?php endif; ?>
                            </h3>
                            <p class="todo-list-description">
                                <?= htmlspecialchars(substr($todo['description'], 0, 100)) ?>
                                <?= strlen($todo['description']) > 100 ? '...' : '' ?>
                            </p>
                        </div>
                        
                        <div class="todo-list-details">
                            <span class="detail-badge">
                                <i class="fas fa-cube"></i>
                                <?= htmlspecialchars($todo['app_name']) ?>
                            </span>
                            <span class="detail-badge">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars($todo['user_name']) ?>
                            </span>
                            <span class="detail-badge">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d M Y, H:i', strtotime($todo['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="todo-list-actions">
                        <span class="status-available">
                            <i class="fas fa-hand-paper"></i>
                            Available
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h3>Semua Tugas Sudah Diambil</h3>
                    <p>Tidak ada tugas yang tersedia saat ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination - Show pages 1-10 -->
        <?php if ($available_tasks > 0): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <span class="pagination-current">Halaman <?= $current_page ?> dari <?= $total_pages ?></span>
                <span class="pagination-total">Menampilkan <?= min($items_per_page, $available_tasks - $offset) ?> dari <?= min($total_items, $available_tasks) ?> tugas pertama</span>
            </div>
            
            <div class="pagination-controls">
                <!-- Previous Page -->
                <?php if ($current_page > 1): ?>
                <a href="?pg=<?= $current_page - 1 ?>" class="pagination-btn pagination-btn-prev" title="Sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                    <span>Prev</span>
                </a>
                <?php else: ?>
                <span class="pagination-btn pagination-btn-prev pagination-btn-disabled">
                    <i class="fas fa-chevron-left"></i>
                    <span>Prev</span>
                </span>
                <?php endif; ?>
                
                <!-- Page Numbers 1-10 -->
                <div class="pagination-numbers">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="pagination-number pagination-number-active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?pg=<?= $i ?>" class="pagination-number"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <!-- Next Page -->
                <?php if ($current_page < $total_pages): ?>
                <a href="?pg=<?= $current_page + 1 ?>" class="pagination-btn pagination-btn-next" title="Selanjutnya">
                    <span>Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="pagination-btn pagination-btn-next pagination-btn-disabled">
                    <span>Next</span>
                    <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Quick Jump -->
            <div class="pagination-jump">
                <span>Ke halaman:</span>
                <select id="pageJumpSelect" class="pagination-jump-select" onchange="jumpToPage()">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $current_page ? 'selected' : '' ?>>
                        Halaman <?= $i ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Base Styles */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 16px;
    background: #f8fafc;
    min-height: calc(100vh - 60px);
}

.notification-simple {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-icon {
    position: relative;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.notification-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #fbbf24;
    color: #78350f;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 8px;
    min-width: 16px;
    text-align: center;
}

.notification-text {
    font-size: 0.9rem;
}

/* Todos Container */
.todos-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.section-header {
    padding: 20px 24px;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
}

.section-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-count {
    background: #f3f4f6;
    color: #6b7280;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.section-count-new {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

/* Todos List */
.todos-list {
    max-height: 600px;
    overflow-y: auto;
}

.todos-list::-webkit-scrollbar {
    width: 8px;
}

.todos-list::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.todos-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.todos-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Todo List Items */
.todo-list-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
}

.todo-list-item:hover {
    background: #f8fafc;
}

.todo-list-item:last-child {
    border-bottom: none;
}

.todo-priority-container {
    margin-right: 16px;
    flex-shrink: 0;
}

.todo-priority-badge {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.todo-priority-badge.priority-high {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.todo-priority-badge.priority-medium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.todo-priority-badge.priority-low {
    background: linear-gradient(135deg, #10b981, #059669);
}

/* Todo Content */
.todo-list-content {
    flex: 1;
    min-width: 0;
}

.todo-list-main {
    margin-bottom: 8px;
}

.todo-list-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge-new {
    display: inline-flex;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.todo-list-description {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.todo-list-details {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.detail-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #6b7280;
}

.detail-badge i {
    width: 14px;
    font-size: 0.7rem;
}

/* Todo Actions */
.todo-list-actions {
    margin-left: 16px;
    flex-shrink: 0;
}

.status-available {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    background: linear-gradient(90deg, #6b7280, #9ca3af);
    box-shadow: 0 2px 8px rgba(107, 114, 128, 0.2);
}

/* Empty State */
.no-data {
    text-align: center;
    padding: 60px 40px;
    color: #9ca3af;
}

.no-data-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #d1d5db;
}

.no-data h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #6b7280;
    margin-bottom: 8px;
}

.no-data p {
    font-size: 0.95rem;
    margin-bottom: 0;
}

/* Pagination Styles */
.pagination-container {
    padding: 20px 24px;
    border-top: 2px solid #f1f5f9;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.pagination-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.85rem;
}

.pagination-current {
    font-weight: 700;
    color: #1f2937;
    font-size: 0.9rem;
}

.pagination-total {
    color: #6b7280;
    font-size: 0.8rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
}

.pagination-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pagination-btn-prev,
.pagination-btn-next {
    background: linear-gradient(135deg, #f8fafc, #ffffff);
}

.pagination-btn-disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-numbers {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pagination-number {
    min-width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
}

.pagination-number:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
    transform: translateY(-1px);
}

.pagination-number-active {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-color: #2563eb;
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.pagination-number-active:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.pagination-jump {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
}

.pagination-jump-select {
    height: 38px;
    padding: 0 32px 0 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
    background-size: 12px;
    font-size: 0.85rem;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.pagination-jump-select:hover {
    border-color: #d1d5db;
    background-color: #f9fafb;
}

.pagination-jump-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .pagination-container {
        justify-content: center;
    }
    
    .pagination-info {
        width: 100%;
        text-align: center;
        align-items: center;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 12px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 20px;
    }
    
    .todo-list-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 20px;
    }
    
    .todo-priority-container {
        margin-right: 0;
    }
    
    .todo-list-actions {
        width: 100%;
        margin-left: 0;
    }
    
    .status-available {
        width: 100%;
        justify-content: center;
    }
    
    .todos-list {
        max-height: none;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 12px;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }
    
    .pagination-numbers {
        order: 1;
        flex-wrap: wrap;
    }
    
    .pagination-btn span {
        display: none;
    }
    
    .pagination-btn {
        padding: 8px 12px;
    }
    
    .pagination-jump {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .section-title {
        font-size: 1.1rem;
    }
    
    .todo-list-details {
        flex-direction: column;
        gap: 8px;
    }
    
    .pagination-number {
        min-width: 34px;
        height: 34px;
        font-size: 0.8rem;
    }
    
    .pagination-btn {
        height: 34px;
    }
    
    .pagination-jump-select {
        height: 34px;
        font-size: 0.8rem;
    }
}
</style>

<script>
// Add smooth scroll animation to task items
document.addEventListener('DOMContentLoaded', function() {
    const taskItems = document.querySelectorAll('.todo-list-item');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.4s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 50);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    taskItems.forEach(item => {
        observer.observe(item);
    });
});

// Quick jump to page function
function jumpToPage() {
    const select = document.getElementById('pageJumpSelect');
    const page = parseInt(select.value);
    
    if (page >= 1) {
        window.location.href = '?pg=' + page;
    }
}
</script>