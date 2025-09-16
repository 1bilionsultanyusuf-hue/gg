<?php
// modul/auth/logout.php - Direct logout handling
session_start();

// Simpan nama user sebelum destroy session untuk pesan (opsional)
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';

// Destroy semua session data
$_SESSION = array();

// Hapus session cookie jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Langsung redirect ke login tanpa menampilkan halaman logout
header('Location: login.php?logout=success');
exit();
?>