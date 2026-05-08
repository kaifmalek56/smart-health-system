<?php
session_start();
require_once "../config/db.php";

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    // Check if email already exists
    $check = mysqli_query($conn,
        "SELECT id FROM users WHERE email='$email'");

    if (mysqli_num_rows($check) > 0) {
        $error = "Email already registered!";

    } else {
        // Hash the password (never store plain text)
        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password)
                VALUES ('$name', '$email', '$hashed')";

        if (mysqli_query($conn, $sql)) {
            $success = "Account created! Login now";
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}
?>
<html>
<head>
  <title>Register — Smart Health</title>
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
    .error   { color:#e53e3e; margin-bottom:12px; font-size:13px; }
    .success { color:#38a169; margin-bottom:12px; font-size:13px; }
    .link    { text-align:center; margin-top:14px; font-size:13px; }
  </style>
</head>
<body>
<div class="box">
  <h2>Create Account</h2>

  <?php if($error)   echo "<p class='error'>$error</p>";   ?>
  <?php if($success) echo "<p class='success'>$success</p>"; ?>

  <form method="POST">
    <input type="text"     name="name"     placeholder="Full Name"     required>
    <input type="email"    name="email"    placeholder="Email Address"  required>
    <input type="password" name="password" placeholder="Password"       required>
    <button type="submit">Register</button>
  </form>

  <p class="link">Already have an account?
    <a href="login.php">Login</a></p>
</div>
</body></html>