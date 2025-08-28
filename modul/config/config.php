<?php
// TIDAK ADA session_start di sini!

// Koneksi database (Laragon default)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "appstodos"; // pastikan ini nama DB yang kamu import

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    exit("Koneksi gagal: " . $e->getMessage());
}

// === Helpers ===
function getDB(): PDO {
    global $pdo;
    return $pdo;
}

function getDashboardStats(): array {
    $db = getDB();
    $totalUsers     = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $completedTasks = (int) $db->query("SELECT COUNT(*) FROM taken WHERE status='done'")->fetchColumn();
    $totalReports   = (int) $db->query("SELECT COUNT(*) FROM todos")->fetchColumn();

    return [
        'total_users'     => $totalUsers,
        'completed_tasks' => $completedTasks,
        'total_reports'   => $totalReports,
    ];
}

function getPriorityBadgeClass($priority): string {
    switch ($priority) {
        case 'high':   return "bg-red-100 text-red-700";
        case 'medium': return "bg-yellow-100 text-yellow-700";
        case 'low':    return "bg-green-100 text-green-700";
        default:       return "bg-gray-100 text-gray-700";
    }
}

function getStatusBadgeClass($status): string {
    switch ($status) {
        case 'done':        return "bg-green-100 text-green-700";
        case 'in_progress': return "bg-blue-100 text-blue-700";
        default:            return "bg-gray-100 text-gray-700";
    }
}

function formatDate($date): string {
    return date("M d, Y", strtotime($date));
}
// TIDAK ADA '?>' PENUTUP
