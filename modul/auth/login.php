<?php
session_start();
require_once '../../config.php';

$error = '';
$loginSuccess = false;

// Jika user sudah login, langsung ke dashboard
if(isset($_SESSION['user_id'])){
    header('Location: ../../index.php?page=dashboard');
    exit;
}

// Proses login
if(isset($_POST['login'])){
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if(empty($user) || empty($pass)){
        $error = 'Username dan password harus diisi!';
    } else {
        $stmt = $koneksi->prepare("SELECT id, name, email, password, role FROM users WHERE name=? OR email=? LIMIT 1");
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            $row = $result->fetch_assoc();
            
            // Cek password - PERBAIKAN: gunakan password_verify() untuk hash atau plain text
            $password_match = false;
            
            // Jika password di database sudah di-hash
            if (password_verify($pass, $row['password'])) {
                $password_match = true;
            } 
            // Jika masih plain text (untuk development/dummy data)
            elseif ($pass === $row['password']) {
                $password_match = true;
            }
            
            if($password_match){
                $loginSuccess = true;
                
                // Regenerate session ID untuk keamanan
                session_regenerate_id(true);
                
                // Simpan session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['role'];
                $_SESSION['login_time'] = time();
                
                // Update last login (optional)
                $update_login = $koneksi->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_login->bind_param("i", $row['id']);
                $update_login->execute();
                
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
}

// Untuk testing, tampilkan user yang tersedia
$test_users = $koneksi->query("SELECT name, email, role FROM users LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - IT | CORE</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #0066ff, #33ccff);
    overflow: hidden;
}

/* Animated Background */
.bubble {
    position: absolute;
    border-radius: 50%;
    opacity: 0.1;
    background: white;
    animation: float 20s linear infinite;
}

@keyframes float {
    0% { transform: translateY(100vh) scale(0); opacity: 0; }
    10% { opacity: 0.1; }
    90% { opacity: 0.1; }
    100% { transform: translateY(-100vh) scale(1); opacity: 0; }
}

/* Login Container */
.login-container {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 400px;
    text-align: center;
    position: relative;
    z-index: 10;
    backdrop-filter: blur(10px);
}

.login-title {
    color: #0066ff;
    margin-bottom: 30px;
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,102,255,0.2);
}

.form-group {
    margin-bottom: 20px;
    text-align: left;
}

.form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.form-group input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0,102,255,0.1);
    background: white;
}

.login-btn {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.login-btn:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,102,255,0.3);
}

.test-accounts {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-top: 20px;
    text-align: left;
}

.test-accounts h4 {
    margin: 0 0 10px 0;
    color: #374151;
    font-size: 0.9rem;
    font-weight: 600;
}

.test-account {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 0.8rem;
    border-bottom: 1px solid #e5e7eb;
}

.test-account:last-child {
    border-bottom: none;
}

.account-info {
    color: #6b7280;
}

.account-role {
    color: #0066ff;
    font-weight: 500;
}

/* Error & Success Styles */
.error-popup {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: #fee2e2;
    color: #dc2626;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(220,38,38,0.3);
    font-weight: 600;
    z-index: 9999;
    animation: slideDown 0.5s ease, fadeOut 0.5s ease 4s forwards;
}

.success-popup {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border-radius: 12px;
    padding: 15px 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    font-weight: 600;
    color: #10b981;
    z-index: 9999;
    animation: slideDown 0.5s ease;
}

.success-icon {
    width: 30px;
    height: 30px;
    border: 3px solid #10b981;
    border-radius: 50%;
    position: relative;
    animation: spin 0.6s ease;
}

.checkmark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    font-size: 16px;
    color: #10b981;
    animation: scaleUp 0.4s 0.6s forwards;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateX(-50%) translateY(-30px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

@keyframes fadeOut {
    to { opacity: 0; transform: translateX(-50%) translateY(-30px); }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes scaleUp {
    0% { transform: translate(-50%, -50%) scale(0); }
    50% { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1); }
}

.shake {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}
</style>
</head>
<body>

<!-- Animated Background -->
<?php for($i = 0; $i < 8; $i++): ?>
<div class="bubble" style="
    width: <?= rand(30, 80) ?>px; 
    height: <?= rand(30, 80) ?>px; 
    left: <?= rand(0, 100) ?>%; 
    animation-delay: <?= rand(0, 10) ?>s;
"></div>
<?php endfor; ?>

<!-- Error Popup -->
<?php if($error): ?>
    <div class="error-popup"><?= $error ?></div>
<?php endif; ?>

<!-- Success Popup -->
<?php if($loginSuccess): ?>
<div class="success-popup">
    <div class="success-icon">
        <div class="checkmark">✓</div>
    </div>
    <div>Login Berhasil!</div>
</div>
<script>
setTimeout(() => {
    window.location.href = '../../index.php?page=dashboard';
}, 2000);
</script>
<?php endif; ?>

<!-- Login Form -->
<div class="login-container <?= $error ? 'shake' : '' ?>">
    <h1 class="login-title">IT | CORE</h1>
    
    <form method="post">
        <div class="form-group">
            <input type="text" name="username" placeholder="Username atau Email" required 
                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" name="login" class="login-btn">
            <i class="fas fa-sign-in-alt mr-2"></i>Login
        </button>
    </form>
    
    <!-- Test Accounts Info -->
    <div class="test-accounts">
        <h4><i class="fas fa-info-circle mr-2"></i>Akun Testing:</h4>
        <?php while($test_user = $test_users->fetch_assoc()): ?>
        <div class="test-account">
            <span class="account-info"><?= htmlspecialchars($test_user['name']) ?></span>
            <span class="account-role"><?= ucfirst($test_user['role']) ?></span>
        </div>
        <?php endwhile; ?>
        <div style="margin-top: 8px; font-size: 0.75rem; color: #9ca3af;">
            Password: gunakan nama user atau lihat db.sql
        </div>
    </div>
</div>

</body>
</html>