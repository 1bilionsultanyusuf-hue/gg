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