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
    $error = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login | Murugan Vedikadai</title>
<link rel="stylesheet" href="styles.css">
</head>

<body class="login-page">

<main class="login-card" role="main">
  <h1>Admin Panel</h1>
  <p class="subtitle">Thiruchendur Murugan Vedikadai</p>

  <h2>Login</h2>

  <?php if ($error): ?>
    <div class="login-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <div class="form-group">
      <label for="login-username">Username</label>
      <input type="text" id="login-username" name="username" class="form-input"
             required autofocus autocomplete="username" placeholder="Admin username">
    </div>

    <div class="form-group">
      <label for="login-password">Password</label>
      <input type="password" id="login-password" name="password" class="form-input"
             required autocomplete="current-password" placeholder="Password">
    </div>

    <button type="submit" name="login" class="btn btn--accent btn--block btn--lg mt-1">
      Login
    </button>
  </form>

  <p class="mt-2" style="font-size:0.85rem;color:#666;">
    <a href="index.php" style="color:var(--primary);">Back to Shop</a>
  </p>
</main>

</body>
</html>
