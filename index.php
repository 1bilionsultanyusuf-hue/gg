<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'apps', 'users', 'todos', 'pelaporan'];
if (!in_array($page, $allowed_pages)) $page = 'dashboard';

include 'modul/layouts/header.php';
include 'modul/layouts/sidebar.php';
?>

<div class="content-wrapper flex flex-1">
    <div class="content flex-1 flex flex-col ml-64">
        <?php include 'modul/layouts/topbar.php'; ?>

        <main class="flex-1 p-6 bg-gray-50 overflow-y-auto min-h-screen">
            <?php
            switch ($page) {
                case 'apps': include "modul/data/apps.php"; break;
                case 'users': include "modul/data/users.php"; break;
                case 'todos': include "modul/todos/todos.php"; break;
                case 'pelaporan': include "modul/pelaporan/pelaporan.php"; break;
                default: include "modul/dashboard/dashboard.php"; break;
            }
            ?>
        </main>

        <?php include 'modul/layouts/footer.php'; ?>
    </div>

    <!-- Overlay hanya menutupi content -->
    <div id="overlay" class="overlay"></div>
</div>

<script src="style/js/main.js"></script>
</body>
</html>