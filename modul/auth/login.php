<?php
$error = '';
$loginSuccess = false;

if(isset($_POST['login'])){
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Dummy login
    if($user=='pr' && $pass=='pr'){
        $loginSuccess = true; // menandai login berhasil
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
    background: linear-gradient(270deg, #0066ff, #33ccff);
    background-size: 1400% 1400%;
    animation: rainbow 20s ease infinite;
}

@keyframes rainbow {
    0% {background-position:0% 50%;}
    50% {background-position:100% 50%;}
    100% {background-position:0% 50%;}
}

.login-container {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 400px;
    text-align: center;
    position: relative;
    z-index: 1;
}

h1 {
    color: #0066ff;
    margin-bottom: 20px;
    font-size: 2.2rem;
}

.error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 15px;
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
    box-shadow: 0 0 0 2px rgba(79,70,229,0.3);
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
    background: linear-gradient(90deg, #0066ff, #33ccff);
}

p.info {
    margin-top: 15px;
    font-size: 0.9rem;
    color: #6b7280;
}

/* ===== Pop-up Login Success Tengah Atas ===== */
.popup-container {
    position: fixed;
    top: 20px; /* posisi atas */
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
    animation: scaleCheck 0.5s 0.5s forwards; /* delay setelah spin */
}

.popup-text {
    font-size: 16px;
}

/* Animasi */
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

<?php if($loginSuccess): ?>
<div class="popup-container">
    <div class="popup-circle">
        <div class="checkmark">✔</div>
    </div>
    <div class="popup-text">Login Berhasil!</div>
</div>

<script>
// animasi fade out
setTimeout(() => {
    document.querySelector('.popup-container').style.opacity = '0';
}, 1800);

setTimeout(() => {
    window.location.href='../../index.php?page=dashboard&login=success';
}, 2000);
</script>
<?php endif; ?>

<div class="login-container">
    <h1>SANTAI</h1>
    <?php if($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username">
        <input type="password" name="password" placeholder="Password">
        <button type="submit" name="login">Login</button>
    </form>
    <p class="info">Username: ? | Password: ? (dummy/prototype)</p>
</div>

</body>
</html>