<?php
require_once 'config/config.php';

// Jika belum login, redirect ke login
if (!isLoggedIn()) {
    redirect('modul/auth/login.php');
}

// Jika sudah login, redirect ke dashboard
redirect('modul/dashboard/dashboard.php');
?>