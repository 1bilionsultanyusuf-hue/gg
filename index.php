<?php
// index.php - Fixed with proper layout structure

// Logout handling
if(isset($_GET['logout'])){
    header('Location: modul/auth/login.php');
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'apps', 'users', 'todos', 'pelaporan', 'profile', 'taken', 'settings'];
if (!in_array($page, $allowed_pages)) $page = 'dashboard';

// Include header (HTML head, styles, dll)
include 'modul/layouts/header.php';
?>

<!-- FIXED HEADER -->
<?php include 'modul/layouts/topbar.php'; ?>

<!-- MAIN CONTAINER -->
<div class="content-wrapper">
    <!-- FIXED SIDEBAR -->
    <?php include 'modul/layouts/sidebar.php'; ?>

    <!-- CONTENT AREA -->
    <div class="content">
        <!-- MAIN CONTENT -->
        <main class="main-content">
            <?php
            switch ($page) {
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
                case 'settings':
                    include "modul/settings/settings.php";
                    break;
                default: 
                    include "modul/dashboard/dashboard.php"; 
                    break;
            }
            ?>
        </main>

        <!-- FOOTER -->
        <?php include 'modul/layouts/footer.php'; ?>
    </div>

    <!-- OVERLAY FOR MOBILE -->
    <div id="overlay" class="overlay"></div>
</div>

<!-- SCRIPTS -->
<script src="style/js/main.js"></script>

</body>
</html>