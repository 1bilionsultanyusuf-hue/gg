<?php
session_start();

// Database configuration
require_once 'database.php';

// Helper functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/modul/auth/login.php');
    }
}

// Get database connection
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>