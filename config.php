<?php
// Mulai session jika dibutuhkan login
session_start();

// Informasi koneksi database
$host = "localhost";      // Ganti sesuai host database
$user = "root";           // Ganti sesuai username database
$password = "";           // Ganti sesuai password database
$database = "appstodos"; // Ganti sesuai nama database kamu

// Membuat koneksi
$koneksi = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Set charset UTF-8
$koneksi->set_charset("utf8");

// ==========================
// Contoh penggunaan query
// ==========================

// Ambil semua apps
/*
$result = $koneksi->query("SELECT * FROM apps");
while($row = $result->fetch_assoc()){
    echo $row['name'] . " - " . $row['description'] . "<br>";
}
*/

// Ambil semua users
/*
$result = $koneksi->query("SELECT * FROM users");
while($row = $result->fetch_assoc()){
    echo $row['name'] . " | " . $row['email'] . " | " . $row['role'] . "<br>";
}
*/
?>
