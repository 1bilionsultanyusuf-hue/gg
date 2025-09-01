<?php
// modul/auth/logout.php - Proper logout handling
session_start();

// Simpan nama user sebelum destroy session untuk pesan
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

// Set pesan logout untuk ditampilkan
$logout_message = "Goodbye, $user_name! Anda telah berhasil logout.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logout - IT | CORE</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    overflow: hidden;
}

.logout-container {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    text-align: center;
    max-width: 400px;
    width: 100%;
    position: relative;
    z-index: 10;
}

.logout-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    animation: pulse 2s ease-in-out infinite;
}

.logout-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
}

.logout-message {
    color: #6b7280;
    margin-bottom: 30px;
    line-height: 1.5;
}

.redirect-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #9ca3af;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #0066ff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.login-link {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.login-link:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.fade-out {
    animation: fadeOut 0.5s ease forwards;
}

@keyframes fadeOut {
    to { opacity: 0; transform: translateY(-20px); }
}
</style>
</head>
<body>

<div class="logout-container">
    <div class="logout-icon">
        <i class="fas fa-sign-out-alt"></i>
    </div>
    
    <h1 class="logout-title">Logout Berhasil</h1>
    <p class="logout-message"><?= htmlspecialchars($logout_message) ?></p>
    
    <div class="redirect-info">
        <div class="spinner"></div>
        <span>Mengalihkan ke halaman login dalam <span id="countdown">3</span> detik...</span>
    </div>
    
    <a href="login.php" class="login-link">
        <i class="fas fa-sign-in-alt mr-2"></i>
        Login Kembali
    </a>
</div>

<script>
let countdown = 3;
const countdownElement = document.getElementById('countdown');
const container = document.querySelector('.logout-container');

const timer = setInterval(() => {
    countdown--;
    countdownElement.textContent = countdown;
    
    if (countdown <= 0) {
        clearInterval(timer);
        container.classList.add('fade-out');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 500);
    }
}, 1000);
</script>

</body>
</html>