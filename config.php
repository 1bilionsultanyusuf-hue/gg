<?php
// config.php
// Hapus session_start() dari sini
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'appstodos';

$koneksi = new mysqli($host, $user, $pass, $db);

if($koneksi->connect_error){
    die("Koneksi gagal: " . $koneksi->connect_error);
}
?>
