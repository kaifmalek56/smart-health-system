<?php
session_start();
require_once "../config/db.php";

// Already logged in? Go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    // Find user by email
    $result = mysqli_query($conn,
        "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($result);

    // Check password against hashed version
    if ($user && password_verify($pass, $user['password'])) {

        // Save user info in session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        header("Location: dashboard.php");
        exit();

    } else {
        $error = "Wrong email or password!";
    }
}
?>
<html>
<head>
  <title>Login — Smart Health</title>
  <style>
    body { font-family: sans-serif; background: #f0f4f8;
           display:flex; justify-content:center; padding:40px; }
    .box { background:#fff; padding:30px; border-radius:10px;
           width:360px; box-shadow:0 2px 12px rgba(0,0,0,.08); }
    h2   { margin:0 0 20px; color:#1a202c; }
    input { width:100%; padding:10px; margin-bottom:14px;
            border:1px solid #cbd5e0; border-radius:6px;
            box-sizing:border-box; font-size:14px; }
    button { width:100%; padding:11px; background:#3b82f6;
             color:#fff; border:none; border-radius:6px;
             font-size:15px; cursor:pointer; }
    button:hover { background:#2563eb; }
    .error { color:#e53e3e; margin-bottom:12px; font-size:13px; }
    .link  { text-align:center; margin-top:14px; font-size:13px; }
  </style>
</head>
<body>
<div class="box">
  <h2>Login</h2>

  <?php if($error) echo "<p class='error'>$error</p>"; ?>

  <form method="POST">
    <input type="email"    name="email"    placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password"      required>
    <button type="submit">Login</button>
  </form>

  <p class="link">No account?
    <a href="register.php">Register here</a></p>
</div>
</body></html>