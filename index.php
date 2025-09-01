<?php
// index.php - full ready
session_start();
// Handle logout
if(isset($_GET['logout'])){
    session_start();
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
// Logout
// ==========================
if(isset($_GET['logout'])){
    session_destroy();
    header('Location: modul/auth/login.php');
    exit;
}

// ==========================
// Tentukan page
// ==========================
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'apps', 'users', 'todos', 'profile', 'taken', 'logout'];
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
                case 'pelaporan': 
                    include "modul/pelaporan/pelaporan.php"; 
                    break;
                case 'profile': 
                    include "modul/profile/profile.php"; 
                    break;
                case 'taken':
                    include "modul/taken/taken.php";
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

</body>
</html>
