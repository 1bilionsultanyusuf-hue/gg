<?php
// Jangan panggil session_start() atau require config.php lagi
// session_start(); // HAPUS
// require_once('modul/config/config.php'); // HAPUS

// Sekarang langsung pakai $koneksi dari index.php
$result = $koneksi->query("SELECT * FROM apps");
while($row = $result->fetch_assoc()){
    echo $row['name'] . "<br>";
}
?>
