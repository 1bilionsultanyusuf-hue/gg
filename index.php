<?php
// index.php - full ready with role-based access
session_start();

// Handle logout
if(isset($_GET['logout'])){
    session_unset();
    session_destroy();
    header('Location: modul/auth/logout.php');
    exit;
}

// ==========================
// Pastikan user login
// ==========================
if(!isset($_SESSION['user_id'])){
    header('Location: modul/auth/login.php');
    exit;
}

// ==========================
// Include config database
// ==========================
require_once 'config.php';

// ==========================
// Tentukan page dan role-based access
// ==========================
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Role-based page access control
$role_access = [
    'dashboard' => ['admin', 'programmer', 'support'],
    'apps' => ['admin', 'programmer', 'support'],
    'users' => ['admin'],
    'todos' => ['admin', 'programmer', 'support'],
    'profile' => ['admin', 'programmer'],
    'taken' => ['admin', 'programmer', 'support'],
    'reports' => ['admin', 'programmer'],
    'settings' => ['admin'],
    'logs' => ['admin'],
    'backup' => ['admin'],
    'logout' => ['admin', 'programmer', 'support']
];

// Get allowed pages for current user role
$user_role = $_SESSION['user_role'];
$allowed_pages = array_keys(array_filter($role_access, function($roles) use ($user_role) {
    return in_array($user_role, $roles);
}));

// Check if user has access to requested page
if (isset($role_access[$page]) && !in_array($user_role, $role_access[$page])) {
    // Redirect to dashboard with access denied message
    $_SESSION['access_error'] = "Anda tidak memiliki akses ke halaman tersebut.";
    header('Location: index.php?page=dashboard');
    exit;
}

// Validate page exists in allowed pages
if(!in_array($page, $allowed_pages)) $page = 'dashboard';

// ==========================
// Include header
// ==========================
include 'modul/layouts/header.php';
?>

<!-- Topbar -->
<?php include 'modul/layouts/topbar.php'; ?>

<!-- Main Wrapper -->
<div class="content-wrapper">
    <!-- Sidebar -->
    <?php include 'modul/layouts/sidebar.php'; ?>

    <!-- Content Area -->
    <div class="content">
        <main class="main-content">
            <?php
            // Display access error message if exists
            if(isset($_SESSION['access_error'])):
            ?>
            <div class="access-error-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?= $_SESSION['access_error'] ?>
                <button onclick="this.parentElement.style.display='none';">×</button>
            </div>
            <?php 
                unset($_SESSION['access_error']);
            endif;
            
            // Include appropriate page based on role and access
            switch($page){
                case 'apps': 
                    include "modul/data/apps.php"; 
                    break;
                case 'users': 
                    include "modul/data/users.php"; 
                    break;
                case 'todos': 
                    include "modul/todos/todos.php"; 
                    break;
                case 'profile': 
                    include "modul/profile/profile.php"; 
                    break;
                case 'taken':
                    include "modul/taken/taken.php";
                    break;
                case 'reports':
                    include "modul/reports/reports.php";
                    break;
                case 'settings':
                    include "modul/settings/settings.php";
                    break;
                case 'logs':
                    include "modul/admin/logs.php";
                    break;
                case 'backup':
                    include "modul/admin/backup.php";
                    break;
                case 'logout':
                    include "modul/auth/logout.php";
                    break;
                default: 
                    include "modul/dashboard/dashboard.php"; 
                    break;
            }
            ?>
        </main>

        <!-- Footer -->
        <?php include 'modul/layouts/footer.php'; ?>
    </div>

    <!-- Overlay for Mobile -->
    <div id="overlay" class="overlay"></div>
</div>

<!-- Scripts -->
<script src="style/js/main.js"></script>

<!-- Access Error Alert Styles -->
<style>
.access-error-alert {
    background: linear-gradient(90deg, #fee2e2, #fecaca);
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #fca5a5;
    animation: slideDown 0.3s ease;
    position: relative;
}

.access-error-alert button {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #dc2626;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.access-error-alert button:hover {
    background: rgba(220, 38, 38, 0.1);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

</body>
</html>