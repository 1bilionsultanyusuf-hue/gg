<?php
// assets/images/avatar_generator.php
// Generator avatar default untuk testing sementara

$gender = $_GET['gender'] ?? 'male';
$name = $_GET['name'] ?? 'User';
$size = $_GET['size'] ?? 150;

// Sanitize input
$gender = in_array($gender, ['male', 'female']) ? $gender : 'male';
$size = max(50, min(500, intval($size))); // Limit size between 50-500px
$name = htmlspecialchars(substr($name, 0, 50)); // Limit name length

// Set color based on gender
if ($gender === 'female') {
    $bg_color = 'ff69b4'; // Hot Pink
    $text_color = 'ffffff';
    $icon = '👩';
} else {
    $bg_color = '4169e1'; // Royal Blue  
    $text_color = 'ffffff';
    $icon = '👨';
}

// Use UI Avatars service
$avatar_url = "https://ui-avatars.com/api/?" . http_build_query([
    'name' => $name,
    'background' => $bg_color,
    'color' => $text_color,
    'size' => $size,
    'font-size' => 0.33,
    'rounded' => true,
    'bold' => true,
    'format' => 'svg'
]);

// Set headers untuk caching
header("Cache-Control: public, max-age=86400"); // Cache 24 hours
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
header("Content-Type: image/svg+xml");

// Redirect atau proxy gambar
if (function_exists('curl_init')) {
    // Proxy gambar melalui server
    $ch = curl_init($avatar_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Avatar Generator)');
    
    $image_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $image_data !== false) {
        echo $image_data;
    } else {
        // Fallback ke redirect
        header("Location: " . $avatar_url);
    }
} else {
    // Fallback ke redirect jika cURL tidak tersedia
    header("Location: " . $avatar_url);
}
exit;
?>