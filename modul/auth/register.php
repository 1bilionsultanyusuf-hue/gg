<?php
session_start();
require_once '../../config.php';

$error = '';
$success = '';

// Jika user sudah login, langsung ke dashboard
if(isset($_SESSION['user_id'])){
    header('Location: ../../index.php?page=dashboard');
    exit;
}

// Proses registrasi
if(isset($_POST['register'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $gender = trim($_POST['gender']);
    $role = 'user'; // Default role untuk user baru

    // Validasi input
    if(empty($name) || empty($email) || empty($password) || empty($gender)){
        $error = 'Semua field harus diisi!';
    } 
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'Format email tidak valid!';
    }
    elseif(strlen($password) < 6){
        $error = 'Password minimal 6 karakter!';
    }
    else {
        // Cek apakah email atau username sudah ada
        $check_stmt = $koneksi->prepare("SELECT id FROM users WHERE name=? OR email=? LIMIT 1");
        $check_stmt->bind_param("ss", $name, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if($check_result->num_rows > 0){
            $error = 'Username atau email sudah digunakan!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            $insert_stmt = $koneksi->prepare("INSERT INTO users (name, email, password, role, gender, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $gender);
            
            if($insert_stmt->execute()){
                $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
            } else {
                $error = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - IT | CORE</title>
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

/* Register Container - sama dengan login container */
.register-container {
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

.register-title {
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

.form-group input,
.form-group select {
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

.form-group select {
    color: #374151;
    cursor: pointer;
}

.form-group select option {
    padding: 10px;
    background: white;
    color: #374151;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #0066ff;
    box-shadow: 
        0 0 0 4px rgba(102, 126, 234, 0.1),
        0 1px 3px 0 rgba(0, 0, 0, 0.1);
    background: rgba(255, 255, 255, 1);
    transform: translateY(-1px);
}

.register-btn {
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

.register-btn:hover {
    background: linear-gradient(135deg, #00aaff 0%, #0066ff 100%);
    transform: translateY(-2px);
    box-shadow: 0 15px 35px -5px rgba(102, 126, 234, 0.5);
}

.register-btn:active {
    transform: translateY(0);
    box-shadow: 0 5px 15px -3px rgba(102, 126, 234, 0.4);
}

/* Login Link - sama dengan register link di login */
.login-link {
    text-align: center;
    margin-bottom: 24px;
    padding: 16px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.login-link p {
    margin: 0;
    color: #374151;
    font-size: 0.95rem;
    font-weight: 500;
}

.login-link a {
    color: #0066ff;
    text-decoration: none;
    font-weight: 700;
    transition: color 0.3s ease;
}

.login-link a:hover {
    color: #0044cc;
    text-decoration: underline;
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
    .register-container {
        margin: 20px;
        padding: 40px 24px;
    }
    
    .register-title {
        font-size: 2.2rem;
    }
    
    .form-group input,
    .form-group select,
    .register-btn {
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
<?php if($success): ?>
<div class="success-popup">
    <div class="success-icon">
        <div class="checkmark">✓</div>
    </div>
    <div>Registrasi Berhasil!</div>
</div>
<script>
setTimeout(() => {
    window.location.href = 'login.php';
}, 2000);
</script>
<?php endif; ?>

<!-- Register Form -->
<div class="register-container <?= $error ? 'shake' : '' ?>">
    <h1 class="register-title">Sign Up</h1>
    
    <form method="post">
        <div class="form-group">
            <input type="text" name="name" placeholder="Username" required 
                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>
        
        <div class="form-group">
            <input type="email" name="email" placeholder="Email address" required 
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        
        <div class="form-group">
            <select name="gender" required>
                <option value="">Choose Gender</option>
                <option value="male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
            </select>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Create password" required>
        </div>
        
        <button type="submit" name="register" class="register-btn">
            Sign Up
        </button>
    </form>
    
    <!-- Login Link -->
    <div class="login-link">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

</body>
</html>