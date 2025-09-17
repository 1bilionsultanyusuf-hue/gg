[file name]: login.php
[file content begin]
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
        $stmt = $koneksi->prepare("SELECT id, name, email, password, role, gender FROM users WHERE name=? OR email=? LIMIT 1");
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
                
                // Simpan session dengan gender support
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['role'];
                $_SESSION['user_gender'] = $row['gender'] ?? 'male'; // Default to male
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
$test_users = $koneksi->query("SELECT name, email, role, gender FROM users LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - IT | CORE</title>
<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #0066ff 0%, #33ccff 100%);
    overflow: hidden;
    position: relative;
}

/* Realistic Animated Background */
.bubble {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    animation: float 15s infinite linear;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

.bubble::before {
    content: '';
    position: absolute;
    top: 10%;
    left: 10%;
    width: 20%;
    height: 20%;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    filter: blur(1px);
}

.bubble::after {
    content: '';
    position: absolute;
    top: 20%;
    right: 15%;
    width: 15%;
    height: 15%;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    filter: blur(1px);
}

@keyframes float {
    0% {
        transform: translateY(100vh) translateX(0px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100px) translateX(100px) rotate(360deg);
        opacity: 0;
    }
}

/* Login Container */
.login-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 50px 40px;
    border-radius: 24px;
    box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    width: 100%;
    max-width: 420px;
    position: relative;
    z-index: 10;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.login-title {
    color: #0066ff;
    margin-bottom: 40px;
    font-size: 2.8rem;
    font-weight: 800;
    text-align: center;
    text-shadow: 0 2px 4px rgba(76, 29, 149, 0.1);
    letter-spacing: -0.02em;
}

.form-group {
    margin-bottom: 24px;
    position: relative;
}

.form-group input {
    width: 100%;
    padding: 18px 20px;
    border: 2px solid rgba(107, 114, 128, 0.2);
    border-radius: 16px;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.8);
    color: #374151;
    outline: none;
}

.form-group input::placeholder {
    color: #0066ff;
    font-weight: 400;
}

.form-group input:focus {
    border-color: #0066ff;
    box-shadow: 
        0 0 0 4px rgba(102, 126, 234, 0.1),
        0 1px 3px 0 rgba(0, 0, 0, 0.1);
    background: rgba(255, 255, 255, 1);
    transform: translateY(-1px);
}

.password-container {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #0066ff;
    font-size: 1.2rem;
    background: none;
    border: none;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-password:hover {
    color: #0044cc;
}

.login-btn {
    width: 100%;
    padding: 18px 20px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, #33ccff 0%, #0044cc 100%);
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
    letter-spacing: 0.025em;
}

.login-btn:hover {
    background: linear-gradient(135deg, #00aaff 0%, #0066ff 100%);
    transform: translateY(-2px);
    box-shadow: 0 15px 35px -5px rgba(102, 126, 234, 0.5);
}

.login-btn:active {
    transform: translateY(0);
    box-shadow: 0 5px 15px -3px rgba(102, 126, 234, 0.4);
}

/* Register Link */
.register-link {
    text-align: center;
    margin-bottom: 24px;
    padding: 16px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.register-link p {
    margin: 0;
    color: #374151;
    font-size: 0.95rem;
    font-weight: 500;
}

.register-link a {
    color: #0066ff;
    text-decoration: none;
    font-weight: 700;
    transition: color 0.3s ease;
}

.register-link a:hover {
    color: #0044cc;
    text-decoration: underline;
}

.test-accounts {
    background: rgba(249, 250, 251, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(229, 231, 235, 0.5);
    border-radius: 16px;
    padding: 20px;
    margin-top: 24px;
    text-align: left;
}

.test-accounts h4 {
    margin: 0 0 16px 0;
    color: #374151;
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
}

.test-account {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 0.85rem;
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
}

.test-account:last-child {
    border-bottom: none;
}

.account-info {
    color: #6b7280;
    font-weight: 500;
}

.account-role {
    color: #667eea;
    font-weight: 600;
    padding: 4px 8px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    font-size: 0.75rem;
}

.gender-badge {
    color: #ec4899;
    font-weight: 500;
    padding: 2px 6px;
    background: rgba(236, 72, 153, 0.1);
    border-radius: 6px;
    font-size: 0.7rem;
    margin-left: 4px;
}

.gender-badge.male {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

/* Error & Success Styles */
.error-popup {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(248, 113, 113, 0.95);
    backdrop-filter: blur(10px);
    color: white;
    padding: 16px 28px;
    border-radius: 16px;
    box-shadow: 0 10px 40px -10px rgba(248, 113, 113, 0.4);
    font-weight: 600;
    z-index: 9999;
    animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1), fadeOut 0.5s ease 4s forwards;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.success-popup {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 16px 28px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2);
    font-weight: 600;
    color: #059669;
    z-index: 9999;
    animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.success-icon {
    width: 32px;
    height: 32px;
    border: 3px solid #059669;
    border-radius: 50%;
    position: relative;
    animation: spin 0.6s ease;
}

.checkmark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    font-size: 18px;
    color: #059669;
    animation: scaleUp 0.4s 0.6s forwards;
}

@keyframes slideDown {
    from { 
        opacity: 0; 
        transform: translateX(-50%) translateY(-30px) scale(0.95); 
    }
    to { 
        opacity: 1; 
        transform: translateX(-50%) translateY(0) scale(1); 
    }
}

@keyframes fadeOut {
    to { 
        opacity: 0; 
        transform: translateX(-50%) translateY(-30px) scale(0.95); 
    }
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
    animation: shake 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}

/* Responsive Design */
@media (max-width: 480px) {
    .login-container {
        margin: 20px;
        padding: 40px 24px;
    }
    
    .login-title {
        font-size: 2.2rem;
    }
    
    .form-group input,
    .login-btn {
        padding: 16px 18px;
    }
}
</style>
</head>
<body>

<!-- Realistic Animated Background -->
<?php 
$bubbleSizes = [40, 60, 80, 100, 120, 140, 30, 50];
$bubbleDelays = [0, 3, 6, 9, 12, 15, 18, 21];
$bubblePositions = [10, 25, 40, 55, 70, 85, 95, 15];
for($i = 0; $i < 8; $i++): 
?>
<div class="bubble" style="
    width: <?= $bubbleSizes[$i] ?>px; 
    height: <?= $bubbleSizes[$i] ?>px; 
    left: <?= $bubblePositions[$i] ?>%; 
    animation-delay: <?= $bubbleDelays[$i] ?>s;
    animation-duration: <?= rand(20, 30) ?>s;
"></div>
<?php endfor; ?>

<!-- Error Popup -->
<?php if($error): ?>
    <div class="error-popup"><?= htmlspecialchars($error) ?></div>
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
            <div class="password-container">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <button type="button" class="toggle-password" id="togglePassword">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" id="eyeIcon">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" id="eyeSlashIcon" style="display: none;">
                        <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
                        <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                        <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit" name="login" class="login-btn">
            Login
        </button>
    </form>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeSlashIcon = document.getElementById('eyeSlashIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeSlashIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeSlashIcon.style.display = 'none';
            }
        });
    </script>
[file content end]