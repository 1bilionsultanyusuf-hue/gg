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

// Helper function untuk base URL
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script_name);
    
    // Normalize path
    if ($path == '/') {
        $path = '';
    }
    
    return $protocol . $host . $path . '/';
}

// Helper function untuk asset path
function asset($path) {
    return getBaseUrl() . 'assets/' . $path;
}

// Helper function untuk upload path
function upload($path) {
    return getBaseUrl() . 'uploads/' . $path;
}

// Function untuk mendapatkan foto profil dengan gender support
function getProfilePhotoUrl($user_id) {
    global $koneksi;
    
    $user_query = "SELECT profile_photo, name, gender FROM users WHERE id = ?";
    $stmt = $koneksi->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    // Check if custom profile photo exists
    if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
        return $user_data['profile_photo'] . '?v=' . time(); // Cache busting
    }
    
    // Return gender-based default avatar
    $gender = $user_data['gender'] ?? 'male';
    
    if ($gender === 'female') {
        return asset('images/default_profile_female.jpg');
    } else {
        return asset('images/default_profile_male.jpg');
    }
}

// Function untuk mendapatkan gender user
function getUserGender($user_id) {
    global $koneksi;
    
    $stmt = $koneksi->prepare("SELECT gender FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['gender'] ?? 'male';
}
?>