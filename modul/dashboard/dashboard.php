<?php
require_once __DIR__ . '/../config/config.php';

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent activities
$db = getDB();

// Recent todos
$recentTodos = $db->query("
    SELECT t.title, t.priority, t.created_at, u.name as user_name 
    FROM todos t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Recent taken tasks
$recentTaken = $db->query("
    SELECT tk.date, tk.status, t.title, u.name as user_name
    FROM taken tk
    JOIN todos t ON tk.id_todos = t.id
    JOIN users u ON tk.user_id = u.id
    ORDER BY tk.date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 overflow-y-auto p-6 bg-gray-50">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users"></i>
                </div>
                <div class="ml-4">
                    <span class="text-gray-500">Total Users</span>
                    <h3 class="text-2xl font-semibold"><?= $stats['total_users'] ?></h3>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="ml-4">
                    <span class="text-gray-500">Completed Tasks</span>
                    <h3 class="text-2xl font-semibold"><?= $stats['completed_tasks'] ?></h3>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="ml-4">
                    <span class="text-gray-500">Total Todos</span>
                    <h3 class="text-2xl font-semibold"><?= $stats['total_reports'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Overview Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Todos -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-list-check mr-2 text-blue-600"></i>
                Recent Todos
            </h3>
            <div class="space-y-3">
                <?php if (!empty($recentTodos)): ?>
                    <?php foreach ($recentTodos as $todo): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?= htmlspecialchars($todo['title']) ?></h4>
                            <p class="text-sm text-gray-600">by <?= htmlspecialchars($todo['user_name']) ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= getPriorityBadgeClass($todo['priority']) ?>">
                                <?= ucfirst($todo['priority']) ?>
                            </span>
                            <span class="text-xs text-gray-500"><?= formatDate($todo['created_at']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent todos found</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-clock mr-2 text-green-600"></i>
                Recent Activities
            </h3>
            <div class="space-y-3">
                <?php if (!empty($recentTaken)): ?>
                    <?php foreach ($recentTaken as $activity): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?= htmlspecialchars($activity['title']) ?></h4>
                            <p class="text-sm text-gray-600">by <?= htmlspecialchars($activity['user_name']) ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadgeClass($activity['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $activity['status'])) ?>
                            </span>
                            <span class="text-xs text-gray-500"><?= date('M d', strtotime($activity['date'])) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent activities found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="?page=todos" class="block bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg text-center transition-colors">
            <i class="fas fa-plus-circle text-2xl mb-2"></i>
            <div class="font-medium">Add New Todo</div>
        </a>
        <a href="?page=users" class="block bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-center transition-colors">
            <i class="fas fa-users text-2xl mb-2"></i>
            <div class="font-medium">Manage Users</div>
        </a>
        <a href="?page=pelaporan" class="block bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg text-center transition-colors">
            <i class="fas fa-chart-line text-2xl mb-2"></i>
            <div class="font-medium">View Reports</div>
        </a>
    </div>
</main>