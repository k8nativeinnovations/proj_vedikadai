<?php
include 'config.php';

$error = "";

/* If admin already logged in → dashboard */
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin2.php");
    exit();
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, password FROM admin WHERE username = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $admin_id, $db_password);
        mysqli_stmt_fetch($stmt);

        if ($password === $db_password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin_id;
            header("Location: admin2.php");
            exit();
        }
    }
    $error = "❌ Invalid username or password";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login | Murugan Vedikadai</title>

<style>
body{
    margin:0;
    font-family: Arial, sans-serif;
    background: radial-gradient(circle at top, #ffcc00, #8b0000);
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.login-box{
    width:420px;
    background:#fff7e6;
    padding:35px;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.35);
    text-align:center;
    border:4px solid gold;
}

.logo{
    font-size:36px;
    font-weight:bold;
    color:#8b0000;
    margin-bottom:5px;
}

.sub-logo{
    font-size:20px;
    color:#444;
    margin-bottom:20px;
}

h2{
    font-size:32px;
    color:#c41e3d;
    margin-bottom:15px;
}

input{
    width:100%;
    padding:16px;
    font-size:22px;
    margin:14px 0;
    border-radius:10px;
    border:2px solid #ccc;
}

input:focus{
    border-color:#ff9800;
    outline:none;
}

button{
    width:100%;
    padding:18px;
    font-size:26px;
    background:linear-gradient(135deg,#ff9800,#ff5722);
    border:none;
    color:white;
    font-weight:bold;
    border-radius:12px;
    cursor:pointer;
    margin-top:10px;
}

button:hover{
    background:linear-gradient(135deg,#ff5722,#e65100);
}

.error{
    color:#b00020;
    font-size:20px;
    margin-bottom:10px;
    font-weight:bold;
}

.hint{
    font-size:18px;
    margin-top:15px;
    color:#333;
    background:#fff3cd;
    padding:12px;
    border-radius:10px;
    border:1px dashed #ff9800;
}
</style>
</head>

<body>

<div class="login-box">

    <div class="logo">🔐 ADMIN PANEL</div>
    <div class="sub-logo">Thiruchendur Murugan Vedikadai</div>

    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="👤 Admin Username" required autofocus>
        <input type="password" name="password" placeholder="🔑 Password" required>

        <button type="submit" name="login">
            🔓 LOGIN
        </button>
    </form>

   

</div>

</body>
</html>
