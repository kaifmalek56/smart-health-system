<?php
session_start();
require_once "../config/db.php";

// Not logged in? Kick them to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch logged-in user's full data from DB
$id     = $_SESSION['user_id'];
$result = mysqli_query($conn,
    "SELECT * FROM users WHERE id='$id'");
$user   = mysqli_fetch_assoc($result);

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard — Smart Health</title>
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#3b82f6; color:#fff; padding:14px 24px;
              display:flex; justify-content:space-between; align-items:center; }
    .navbar h1 { margin:0; font-size:18px; }
    .navbar a  { color:#fff; text-decoration:none; font-size:13px;
                 background:rgba(255,255,255,.2);
                 padding:6px 14px; border-radius:20px; }
    .navbar a:hover { background:rgba(255,255,255,.35); }
    .content { padding:30px; max-width:800px; margin:0 auto; }
    .welcome { background:#fff; padding:24px; border-radius:10px;
               box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }
    .welcome h2 { margin:0 0 6px; color:#1a202c; }
    .welcome p  { margin:0; color:#718096; font-size:14px; }
    .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
    .card { background:#fff; padding:20px; border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.07);
            text-align:center; text-decoration:none; color:#1a202c;
            transition:transform .15s; }
    .card:hover { transform:translateY(-2px); }
    .card .icon { font-size:28px; margin-bottom:8px; }
    .card h3 { margin:0 0 4px; font-size:14px; }
    .card p  { margin:0; font-size:12px; color:#718096; }
  </style>
</head>
<body>

<div class="navbar">
  <h1>🏥 Smart Health</h1>
  <a href="?logout=1">Logout</a>
</div>

<div class="content">
  <div class="welcome">
    <h2>Welcome back, <?php echo $user['name']; ?>! 👋</h2>
    <p>What would you like to do today?</p>
  </div>

  <div class="cards">
	 <a class="card" href="symptoms.php">
	  <div class="icon">🩺</div>
	  <h3>Symptom Checker</h3>
	  <p>Describe symptoms, get advice</p>
	</a>
	<a class="card" href="recommend.php">
		<div class="icon">📍</div>
		<h3>Find a Specialist</h3>
		<p>Get hospital recommendations</p>
	</a>
    <a class="card" href="book_appointment.php">
      <div class="icon">📅</div>
      <h3>Book Appointment</h3>
      <p>Schedule a doctor visit</p>
    </a>
    <a class="card" href="queue_status.php">
      <div class="icon">⏱️</div>
      <h3>Queue Status</h3>
      <p>Check your token & wait time</p>
    </a>
    <a class="card" href="order_medicine.php">
      <div class="icon">💊</div>
      <h3>Order Medicine</h3>
      <p>Upload prescription & order</p>
    </a>
  </div>
</div>
</body></html>