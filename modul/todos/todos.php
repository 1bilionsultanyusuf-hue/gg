<?php
// Handle CRUD Operations for Todos
$message = '';
$error = '';

// CREATE - Add new todo
if (isset($_POST['add_todo'])) {
    $app_id = trim($_POST['app_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($app_id)) {
        $stmt = $koneksi->prepare("INSERT INTO todos (app_id, title, description, priority, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $app_id, $title, $description, $priority, $user_id);
        
        if ($stmt->execute()) {
            $message = "Todo '$title' berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan todo!";
        }
    } else {
        $error = "Judul dan aplikasi harus diisi!";
    }
}

// UPDATE - Edit todo
if (isset($_POST['edit_todo'])) {
    $id = $_POST['todo_id'];
    $app_id = trim($_POST['app_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    
    if (!empty($title) && !empty($app_id)) {
        $stmt = $koneksi->prepare("UPDATE todos SET app_id = ?, title = ?, description = ?, priority = ? WHERE id = ?");
        $stmt->bind_param("isssi", $app_id, $title, $description, $priority, $id);
        
        if ($stmt->execute()) {
            $message = "Todo berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui todo!";
        }
    } else {
        $error = "Judul dan aplikasi harus diisi!";
    }
}

// DELETE - Remove todo
if (isset($_POST['delete_todo'])) {
    $id = $_POST['todo_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Todo berhasil dihapus!";
    } else {
        $error = "Gagal menghapus todo!";
    }
}

// Get todos data with related info
$todos_query = "
    SELECT t.*, 
           a.name as app_name,
           u.name as user_name,
           tk.status as taken_status,
           tk.date as taken_date
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    ORDER BY t.created_at DESC
";
$todos_result = $koneksi->query($todos_query);

// Get apps for dropdown
$apps_query = "SELECT id, name FROM apps ORDER BY name";
$apps_result = $koneksi->query($apps_query);

// Get statistics
$total_todos = $koneksi->query("SELECT COUNT(*) as count FROM todos")->fetch_assoc()['count'];
$high_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'high'")->fetch_assoc()['count'];
$medium_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'medium'")->fetch_assoc()['count'];
$low_priority = $koneksi->query("SELECT COUNT(*) as count FROM todos WHERE priority = 'low'")->fetch_assoc()['count'];
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
            <h1 class="page-title">
                <i class="fas fa-tasks mr-3"></i>
                Manajemen Todo
            </h1>
            <p class="page-subtitle">
                Kelola dan monitor semua tugas dalam sistem
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAddTodoModal()">
                <i class="fas fa-plus mr-2"></i>
                Tambah Todo
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-gradient-blue">
            <div class="stat-icon">
                <i class="fas fa-list-check"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $total_todos ?></h3>
                <p class="stat-label">Total Todo</p>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .priority-high { border-left-color: #ef4444; }
        .priority-medium { border-left-color: #f59e0b; }
        .priority-low { border-left-color: #10b981; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-blue-600 mb-2">
                    <i class="fas fa-tasks mr-3"></i>Todo Management System
                </h1>
                <p class="text-gray-600">Kelola tugas dan aktivitas development tim</p>
            </div>

            <!-- Add Todo Form -->
            <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
                <h3 class="text-lg font-semibold mb-4">Tambah Todo Baru</h3>
                <form id="addTodoForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Aplikasi</label>
                        <select id="appSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Pilih Aplikasi</option>
                            <option value="1">Aplikasi Keuangan</option>
                            <option value="2">Aplikasi Inventaris</option>
                            <option value="3">Aplikasi CRM</option>
                            <option value="4">Aplikasi HRIS</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioritas</label>
                        <select id="prioritySelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Judul</label>
                        <input type="text" id="titleInput" placeholder="Judul todo..." 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea id="descriptionInput" placeholder="Deskripsi detail..." rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Tambah Todo
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filter & Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow">
                    <h3 class="font-semibold mb-3">Filter</h3>
                    <div class="flex gap-3">
                        <select id="filterApp" class="border border-gray-300 rounded px-3 py-1 text-sm">
                            <option value="">Semua App</option>
                            <option value="1">Keuangan</option>
                            <option value="2">Inventaris</option>
                            <option value="3">CRM</option>
                            <option value="4">HRIS</option>
                        </select>
                        <select id="filterPriority" class="border border-gray-300 rounded px-3 py-1 text-sm">
                            <option value="">Semua Prioritas</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <select id="filterStatus" class="border border-gray-300 rounded px-3 py-1 text-sm">
                            <option value="">Semua Status</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                            <option value="not_taken">Not Taken</option>
                        </select>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-4 gap-3">
                    <div class="bg-white p-3 rounded-lg shadow text-center">
                        <div id="totalTodos" class="text-xl font-bold text-gray-700">5</div>
                        <div class="text-xs text-gray-600">Total</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow text-center">
                        <div id="inProgressTodos" class="text-xl font-bold text-blue-500">3</div>
                        <div class="text-xs text-gray-600">Progress</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow text-center">
                        <div id="doneTodos" class="text-xl font-bold text-green-500">2</div>
                        <div class="text-xs text-gray-600">Done</div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow text-center">
                        <div id="highPriorityTodos" class="text-xl font-bold text-red-500">2</div>
                        <div class="text-xs text-gray-600">High</div>
                    </div>
                </div>
            </div>

            <!-- Todo List -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-list mr-2"></i>Daftar Todos
                    </h2>
                </div>
                
                <div class="p-6">
                    <div id="todoList" class="space-y-4">
                        <!-- Todos will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </main>

        <div class="stat-card bg-gradient-red">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $high_priority ?></h3>
                <p class="stat-label">High Priority</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-orange">
            <div class="stat-icon">
                <i class="fas fa-minus-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $medium_priority ?></h3>
                <p class="stat-label">Medium Priority</p>
            </div>
        </div>

        <div class="stat-card bg-gradient-green">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?= $low_priority ?></h3>
                <p class="stat-label">Low Priority</p>
            </div>
        </div>
    </div>

    <!-- Todos Grid -->
    <div class="todos-grid">
        <?php while($todo = $todos_result->fetch_assoc()): ?>
        <div class="todo-card priority-<?= $todo['priority'] ?>">
            <div class="todo-card-header">
                <div class="todo-priority priority-<?= $todo['priority'] ?>">
                    <i class="fas fa-<?= getPriorityIcon($todo['priority']) ?>"></i>
                    <?= ucfirst($todo['priority']) ?>
                </div>
                <div class="todo-actions">
                    <button class="action-btn" onclick="editTodo(<?= $todo['id'] ?>, <?= $todo['app_id'] ?>, '<?= htmlspecialchars($todo['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($todo['description'], ENT_QUOTES) ?>', '<?= $todo['priority'] ?>')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn danger" onclick="deleteTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars($todo['title'], ENT_QUOTES) ?>')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="todo-content">
                <h3 class="todo-title"><?= htmlspecialchars($todo['title']) ?></h3>
                <p class="todo-description">
                    <?= htmlspecialchars(substr($todo['description'], 0, 120)) ?>
                    <?= strlen($todo['description']) > 120 ? '...' : '' ?>
                </p>
                
                <div class="todo-meta">
                    <div class="meta-item">
                        <i class="fas fa-cube"></i>
                        <span><?= htmlspecialchars($todo['app_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($todo['user_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?= date('d/m/Y', strtotime($todo['created_at'])) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="todo-footer">
                <?php if($todo['taken_status']): ?>
                <div class="status-badge status-<?= $todo['taken_status'] ?>">
                    <i class="fas fa-<?= $todo['taken_status'] == 'done' ? 'check-circle' : 'clock' ?>"></i>
                    <?= $todo['taken_status'] == 'done' ? 'Completed' : 'In Progress' ?>
                </div>
                <?php else: ?>
                <div class="status-badge status-available">
                    <i class="fas fa-hand-paper"></i>
                    Available
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        
        <!-- Add New Todo Card -->
        <div class="todo-card add-new-card" onclick="openAddTodoModal()">
            <div class="add-new-content">
                <div class="add-new-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3>Tambah Todo Baru</h3>
                <p>Klik untuk menambahkan todo</p>
            </div>
        </div>
    </div>
</div>

<?php
function getPriorityIcon($priority) {
    $icons = [
        'high' => 'exclamation-triangle',
        'medium' => 'minus',
        'low' => 'arrow-down'
    ];
    return $icons[$priority] ?? 'circle';
}
?>

<!-- Add/Edit Todo Modal -->
<div id="todoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Todo Baru</h3>
            <button class="modal-close" onclick="closeTodoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="todoForm" method="POST">
                <input type="hidden" id="todoId" name="todo_id">
                <div class="form-group">
                    <label for="todoApp">Aplikasi *</label>
                    <select id="todoApp" name="app_id" required>
                        <option value="">Pilih Aplikasi</option>
                        <?php 
                        $apps_result->data_seek(0);
                        while($app = $apps_result->fetch_assoc()): 
                        ?>
                        <option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
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
                    <select id="todoPriority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTodoModal()">
                Batal
            </button>
            <button type="submit" id="submitBtn" form="todoForm" name="add_todo" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Simpan
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
            <p id="deleteMessage">Apakah Anda yakin ingin menghapus todo ini?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                Batal
            </button>
            <form id="deleteForm" method="POST" style="display: inline;">
                <input type="hidden" id="deleteTodoId" name="todo_id">
                <button type="submit" name="delete_todo" class="btn btn-danger">
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

.bg-gradient-blue { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.bg-gradient-red { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; }
.bg-gradient-orange { background: linear-gradient(135deg, #ff7b7b, #ff9999); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); color: white; }

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

/* Todos Grid */
.todos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.todo-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    border-left: 4px solid #e5e7eb;
}

.todo-card.priority-high {
    border-left-color: #ef4444;
}

.todo-card.priority-medium {
    border-left-color: #f59e0b;
}

.todo-card.priority-low {
    border-left-color: #10b981;
}

.todo-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.todo-card-header {
    padding: 20px 24px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.todo-priority {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.todo-priority.priority-high {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.todo-priority.priority-medium {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.todo-priority.priority-low {
    background: linear-gradient(90deg, #10b981, #059669);
}

.todo-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.todo-card:hover .todo-actions {
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

.todo-content {
    padding: 20px 24px;
}

.todo-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.todo-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 16px;
}

.todo-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
}

.meta-item i {
    width: 16px;
    font-size: 0.8rem;
}

.todo-footer {
    padding: 20px 24px;
    border-top: 1px solid #f3f4f6;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.status-badge.status-done {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.status-badge.status-in_progress {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.status-badge.status-available {
    background: linear-gradient(90deg, #6b7280, #9ca3af);
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
    
    .todos-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
let currentEditId = null;

function openAddTodoModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Todo Baru';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
    document.getElementById('submitBtn').name = 'add_todo';
    document.getElementById('todoForm').reset();
    document.getElementById('todoId').value = '';
    currentEditId = null;
    document.getElementById('todoModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editTodo(id, app_id, title, description, priority) {
    document.getElementById('modalTitle').textContent = 'Edit Todo';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
    document.getElementById('submitBtn').name = 'edit_todo';
    document.getElementById('todoId').value = id;
    document.getElementById('todoApp').value = app_id;
    document.getElementById('todoTitle').value = title;
    document.getElementById('todoDescription').value = description;
    document.getElementById('todoPriority').value = priority;
    currentEditId = id;
    document.getElementById('todoModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function deleteTodo(id, title) {
    document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus todo "${title}"?`;
    document.getElementById('deleteTodoId').value = id;
    document.getElementById('deleteModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeTodoModal() {
    document.getElementById('todoModal').classList.remove('show');
    document.body.style.overflow = '';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal')) {
        closeTodoModal();
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
</script>
    <script>
        // Simulated database data
        let apps = [
            {id: 1, name: 'Aplikasi Keuangan', description: 'Digunakan untuk mencatat arus kas dan laporan keuangan.'},
            {id: 2, name: 'Aplikasi Inventaris', description: 'Mencatat stok barang dan pengeluaran inventaris kantor.'},
            {id: 3, name: 'Aplikasi CRM', description: 'Mengelola interaksi dan hubungan dengan pelanggan.'},
            {id: 4, name: 'Aplikasi HRIS', description: 'Mengatur data kepegawaian dan absensi.'}
        ];

        let users = [
            {id: 1, name: 'Budi Santoso', email: 'budi@example.com', role: 'programmer'},
            {id: 2, name: 'Siti Aminah', email: 'siti@example.com', role: 'support'},
            {id: 3, name: 'Joko Widodo', email: 'joko@example.com', role: 'admin'},
            {id: 4, name: 'Dewi Lestari', email: 'dewi@example.com', role: 'programmer'}
        ];

        let todos = [
            {id: 1, app_id: 1, title: 'Perbaiki fitur export PDF', description: 'Export PDF gagal jika data terlalu besar', priority: 'high', user_id: 1, created_at: '2025-08-01'},
            {id: 2, app_id: 1, title: 'Tambahkan grafik arus kas', description: 'Menampilkan grafik bulanan untuk arus kas masuk dan keluar', priority: 'medium', user_id: 1, created_at: '2025-08-02'},
            {id: 3, app_id: 2, title: 'Perbaikan filter stok', description: 'Filter tidak bisa memproses nama barang dengan karakter khusus', priority: 'low', user_id: 4, created_at: '2025-08-03'},
            {id: 4, app_id: 3, title: 'Integrasi dengan email client', description: 'Menambahkan fitur notifikasi otomatis ke email pelanggan', priority: 'high', user_id: 2, created_at: '2025-08-01'},
            {id: 5, app_id: 4, title: 'Bug absensi shift malam', description: 'Absensi shift malam tidak masuk ke laporan bulanan', priority: 'medium', user_id: 4, created_at: '2025-08-02'}
        ];

        let taken = [
            {id: 1, id_todos: 1, date: '2025-08-01', status: 'in_progress', user_id: 1},
            {id: 2, id_todos: 2, date: '2025-08-02', status: 'done', user_id: 1},
            {id: 3, id_todos: 3, date: '2025-08-03', status: 'in_progress', user_id: 4},
            {id: 4, id_todos: 4, date: '2025-08-01', status: 'done', user_id: 2},
            {id: 5, id_todos: 5, date: '2025-08-02', status: 'in_progress', user_id: 4}
        ];

        let nextTodoId = 6;
        let nextTakenId = 6;

        // Helper functions
        function getAppName(appId) {
            const app = apps.find(a => a.id === appId);
            return app ? app.name : 'Unknown App';
        }

        function getUserName(userId) {
            const user = users.find(u => u.id === userId);
            return user ? user.name : 'Unknown User';
        }

        function getTodoStatus(todoId) {
            const takenRecord = taken.find(t => t.id_todos === todoId);
            return takenRecord ? takenRecord.status : 'not_taken';
        }

        function getPriorityColor(priority) {
            switch(priority) {
                case 'high': return 'border-red-500 bg-red-50';
                case 'medium': return 'border-yellow-500 bg-yellow-50';
                case 'low': return 'border-green-500 bg-green-50';
                default: return 'border-gray-300 bg-gray-50';
            }
        }

        function getPriorityBadge(priority) {
            const colors = {
                'high': 'bg-red-100 text-red-800',
                'medium': 'bg-yellow-100 text-yellow-800',
                'low': 'bg-green-100 text-green-800'
            };
            return `<span class="px-2 py-1 rounded-full text-xs font-medium ${colors[priority]}">${priority.toUpperCase()}</span>`;
        }

        function getStatusBadge(status) {
            const colors = {
                'done': 'bg-green-100 text-green-800',
                'in_progress': 'bg-blue-100 text-blue-800',
                'not_taken': 'bg-gray-100 text-gray-800'
            };
            const labels = {
                'done': 'Done',
                'in_progress': 'In Progress',
                'not_taken': 'Not Taken'
            };
            return `<span class="px-2 py-1 rounded-full text-xs font-medium ${colors[status]}">${labels[status]}</span>`;
        }

        // Render functions
        function renderTodos(filteredTodos = null) {
            const todosToShow = filteredTodos || todos;
            const todoList = document.getElementById('todoList');
            
            if (todosToShow.length === 0) {
                todoList.innerHTML = '<div class="text-center py-8 text-gray-500">Tidak ada todos yang ditemukan</div>';
                return;
            }

            todoList.innerHTML = todosToShow.map(todo => {
                const status = getTodoStatus(todo.id);
                const appName = getAppName(todo.app_id);
                const userName = getUserName(todo.user_id);
                const isDone = status === 'done';
                
                return `
                    <div class="todo-item p-4 rounded-lg border-l-4 ${getPriorityColor(todo.priority)} ${isDone ? 'opacity-75' : ''} fade-in">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="font-semibold ${isDone ? 'line-through text-gray-500' : 'text-gray-800'}">${todo.title}</h3>
                                    ${getPriorityBadge(todo.priority)}
                                    ${getStatusBadge(status)}
                                </div>
                                <p class="text-gray-600 text-sm mb-2 ${isDone ? 'line-through' : ''}">${todo.description}</p>
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <span><i class="fas fa-cube mr-1"></i>${appName}</span>
                                    <span><i class="fas fa-user mr-1"></i>${userName}</span>
                                    <span><i class="fas fa-calendar mr-1"></i>${todo.created_at}</span>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-4">
                                ${status === 'not_taken' ? `
                                    <button onclick="takeTodo(${todo.id})" 
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                        <i class="fas fa-play mr-1"></i>Take
                                    </button>
                                ` : ''}
                                ${status === 'in_progress' ? `
                                    <button onclick="completeTodo(${todo.id})" 
                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                        <i class="fas fa-check mr-1"></i>Done
                                    </button>
                                ` : ''}
                                ${status === 'done' ? `
                                    <button onclick="undoTodo(${todo.id})" 
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                        <i class="fas fa-undo mr-1"></i>Undo
                                    </button>
                                ` : ''}
                                <button onclick="deleteTodo(${todo.id})" 
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateStats() {
            const totalTodos = todos.length;
            const inProgressTodos = taken.filter(t => t.status === 'in_progress').length;
            const doneTodos = taken.filter(t => t.status === 'done').length;
            const highPriorityTodos = todos.filter(t => t.priority === 'high').length;

            document.getElementById('totalTodos').textContent = totalTodos;
            document.getElementById('inProgressTodos').textContent = inProgressTodos;
            document.getElementById('doneTodos').textContent = doneTodos;
            document.getElementById('highPriorityTodos').textContent = highPriorityTodos;
        }

        // Todo actions
        function takeTodo(todoId) {
            const newTaken = {
                id: nextTakenId++,
                id_todos: todoId,
                date: new Date().toISOString().split('T')[0],
                status: 'in_progress',
                user_id: 1 // Current user (simulated)
            };
            taken.push(newTaken);
            renderTodos();
            updateStats();
        }

        function completeTodo(todoId) {
            const takenRecord = taken.find(t => t.id_todos === todoId);
            if (takenRecord) {
                takenRecord.status = 'done';
                renderTodos();
                updateStats();
            }
        }

        function undoTodo(todoId) {
            const takenRecord = taken.find(t => t.id_todos === todoId);
            if (takenRecord) {
                takenRecord.status = 'in_progress';
                renderTodos();
                updateStats();
            }
        }

        function deleteTodo(todoId) {
            if (confirm('Apakah Anda yakin ingin menghapus todo ini?')) {
                todos = todos.filter(t => t.id !== todoId);
                taken = taken.filter(t => t.id_todos !== todoId);
                renderTodos();
                updateStats();
            }
        }

        // Filter functionality
        function filterTodos() {
            const appFilter = document.getElementById('filterApp').value;
            const priorityFilter = document.getElementById('filterPriority').value;
            const statusFilter = document.getElementById('filterStatus').value;

            let filtered = todos.filter(todo => {
                const status = getTodoStatus(todo.id);
                
                return (!appFilter || todo.app_id == appFilter) &&
                       (!priorityFilter || todo.priority === priorityFilter) &&
                       (!statusFilter || status === statusFilter);
            });

            renderTodos(filtered);
        }

        // Add new todo
        document.getElementById('addTodoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newTodo = {
                id: nextTodoId++,
                app_id: parseInt(document.getElementById('appSelect').value),
                title: document.getElementById('titleInput').value,
                description: document.getElementById('descriptionInput').value,
                priority: document.getElementById('prioritySelect').value,
                user_id: 1, // Current user (simulated)
                created_at: new Date().toISOString().split('T')[0]
            };

            todos.push(newTodo);
            renderTodos();
            updateStats();
            
            // Reset form
            e.target.reset();
        });

        // Event listeners for filters
        document.getElementById('filterApp').addEventListener('change', filterTodos);
        document.getElementById('filterPriority').addEventListener('change', filterTodos);
        document.getElementById('filterStatus').addEventListener('change', filterTodos);

        // Initial render
        renderTodos();
        updateStats();
    </script>
</body>
</html>
