<?php
session_start();

// Database configuration
require_once 'koneksi/database.php';

// Helper functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/modul/auth/login.php');
    }
}

// Get database connection
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Get user by username
function getUserByUsername($username) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE name = :username OR email = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user by ID
function getUserById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update last login
function updateLastLogin($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    return $stmt->execute();
}

// Get all users with pagination
function getUsers($page = 1, $limit = 10, $search = '', $role = '', $status = '') {
    $db = getDB();
    $offset = ($page - 1) * $limit;
    
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (name LIKE :search OR email LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    if (!empty($role)) {
        $where .= " AND role = :role";
        $params[':role'] = $role;
    }
    
    if (!empty($status)) {
        $where .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    $sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get total users count
function getTotalUsers($search = '', $role = '', $status = '') {
    $db = getDB();
    
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where .= " AND (name LIKE :search OR email LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    if (!empty($role)) {
        $where .= " AND role = :role";
        $params[':role'] = $role;
    }
    
    if (!empty($status)) {
        $where .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    $sql = "SELECT COUNT(*) as total FROM users $where";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Get dashboard stats
function getDashboardStats() {
    $db = getDB();
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Completed tasks (todos with status done)
    $stmt = $db->query("SELECT COUNT(*) as total FROM taken WHERE status = 'done'");
    $completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total reports
    $stmt = $db->query("SELECT COUNT(*) as total FROM todos");
    $totalReports = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return [
        'total_users' => $totalUsers,
        'completed_tasks' => $completedTasks,
        'total_reports' => $totalReports
    ];
}

// Format date
function formatDate($date) {
    if (!$date) return 'Never';
    
    $now = new DateTime();
    $dateTime = new DateTime($date);
    $diff = $now->diff($dateTime);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            return $diff->i . ' minutes ago';
        }
        return $diff->h . ' hours ago';
    } elseif ($diff->days == 1) {
        return '1 day ago';
    } elseif ($diff->days < 7) {
        return $diff->days . ' days ago';
    } elseif ($diff->days < 30) {
        return floor($diff->days / 7) . ' weeks ago';
    } else {
        return $dateTime->format('M d, Y');
    }
}

// Get user initials for avatar
function getUserInitials($name) {
    $names = explode(' ', trim($name));
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper($n[0]);
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'U';
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
        case 'done':
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'pending':
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800';
        case 'suspended':
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'inactive':
            return 'bg-gray-100 text-gray-800';
        case 'accepted':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Get priority badge class
function getPriorityBadgeClass($priority) {
    switch ($priority) {
        case 'high':
            return 'bg-red-100 text-red-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>