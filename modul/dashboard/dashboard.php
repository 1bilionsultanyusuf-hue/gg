<?php
session_start();
require_once "modul/config/config.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Selamat datang, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h2>
    <a href="logout.php">Logout</a>
</body>
</html>
