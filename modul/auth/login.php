<?php
$error = '';
$loginSuccess = false;

if(isset($_POST['login'])){
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Dummy login
    if($user=='pr' && $pass=='pr'){
        $loginSuccess = true;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: #0066ff;
    overflow: hidden;
}

/* ===== Bubble Background ===== */
.bubble {
    position: absolute;
    bottom: -100px;
    border-radius: 50%;
    opacity: 0.3;
    background: #33ccff;
    animation: rise 20s linear infinite;
}

@keyframes rise {
    0% { transform: translateY(0) scale(0.5); }
    50% { transform: translateY(-50vh) scale(1); }
    100% { transform: translateY(-120vh) scale(0.5); }
}

/* ===== Login Form ===== */
.login-container {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 400px;
    text-align: center;
    position: relative;
    z-index: 10;
}

h1 {
    color: #0066ff;
    margin-bottom: 20px;
    font-size: 2.2rem;
}
 
input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 10px;
    outline: none;
    font-size: 1rem;
}

input:focus {
    border-color: #0066ff;
    box-shadow: 0 0 0 2px rgba(0,102,255,0.3);
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #0066ff, #33ccff);
    color: white;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: linear-gradient(90deg, #0044cc, #00aaff);
}

p.info {
    margin-top: 15px;
    font-size: 0.9rem;
    color: #6b7280;
}

/* ===== Error Popup ===== */
.error-popup {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: #fee2e2;
    color: #b91c1c;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    font-weight: bold;
    font-family: 'Segoe UI', sans-serif;
    z-index: 9999;
    opacity: 0;
    animation: fadeIn 0.5s forwards, fadeOut 0.5s forwards 5s;
}

/* Shake effect */
@keyframes shake {
    0% { transform: translateX(0); }
    20% { transform: translateX(-10px); }
    40% { transform: translateX(10px); }
    60% { transform: translateX(-10px); }
    80% { transform: translateX(10px); }
    100% { transform: translateX(0); }
}

/* Fade in */
@keyframes fadeIn {
    0% { opacity: 0; transform: translateX(-50%) translateY(-20px);}
    100% { opacity: 1; transform: translateX(-50%) translateY(0);}
}

/* Fade out after 5s */
@keyframes fadeOut {
    0% { opacity: 1; transform: translateX(-50%) translateY(0);}
    100% { opacity: 0; transform: translateX(-50%) translateY(-20px);}
}

/* ===== Pop-up Login Success ===== */
.popup-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border-radius: 12px;
    padding: 15px 25px;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    font-weight: bold;
    font-family: 'Segoe UI', sans-serif;
    color: #10b981;
    z-index: 9999;
    opacity: 1;
    transition: all 0.3s ease;
}

.popup-circle {
    width: 30px;
    height: 30px;
    border: 3px solid #10b981;
    border-radius: 50%;
    position: relative;
    animation: spin 0.5s ease forwards;
}

.checkmark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    font-size: 16px;
    color: #10b981;
    animation: scaleCheck 0.5s 0.5s forwards;
}

.popup-text {
    font-size: 16px;
}
 
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes scaleCheck {
    0% { transform: translate(-50%, -50%) scale(0); }
    50% { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
</style>
</head>
<body>

<!-- Bubble background -->
<div class="bubble" style="width:40px; height:40px; left:5%; animation-delay: 0s;"></div>
<div class="bubble" style="width:60px; height:60px; left:20%; animation-delay: 3s;"></div>
<div class="bubble" style="width:50px; height:50px; left:40%; animation-delay: 6s;"></div>
<div class="bubble" style="width:70px; height:70px; left:60%; animation-delay: 2s;"></div>
<div class="bubble" style="width:30px; height:30px; left:80%; animation-delay: 4s;"></div>

<!-- Error Popup -->
<?php if($error): ?>
    <div class="error-popup" id="errorPopup"><?= $error ?></div>
<?php endif; ?>

<!-- Login Success Popup -->
<?php if($loginSuccess): ?>
<div class="popup-container">
    <div class="popup-circle">
        <div class="checkmark">✔</div>
    </div>
    <div class="popup-text">Login Berhasil!</div>
  </div>
 <script>
setTimeout(() => {
    document.querySelector('.popup-container').style.opacity = '0';
}, 1800);

setTimeout(() => {
    window.location.href='../../index.php?page=dashboard&login=success';
}, 2000);
</script>
<?php endif; ?>

<div class="login-container <?php if($error) echo 'shake'; ?>">
    <h1>SANTAI</h1>
     <form method="post">
        <input type="text" name="username" placeholder="Username">
        <input type="password" name="password" placeholder="Password">
        <button type="submit" name="login">Login</button>
    </form>
    <p class="info">Username: ? | Password: ? (dummy/prototype)</p>
</div>

<script>
if(document.getElementById('errorPopup')){
    // tambahkan class shake
    const form = document.querySelector('.login-container');
    form.style.animation = 'shake 0.5s';
    setTimeout(() => {
        form.style.animation = '';
    }, 500);
}
</script>

</body>
</html>