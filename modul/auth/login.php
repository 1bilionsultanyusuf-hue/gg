<?php
$error = '';
if(isset($_POST['login'])){
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Dummy login
    if($user=='jagung' && $pass=='bakar'){
        header('Location: ../../index.php?page=dashboard&login=success');
        exit;
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
    
    /* Rainbow animated background */
    background: linear-gradient(270deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #8f00ff);
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
</style>
</head>
<body>
    <div class="login-container">
        <h1>us:jagung pw:bakar</h1>
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