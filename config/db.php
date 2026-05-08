<?php

// ── Database credentials ──────────────────────────
$host   = "localhost";   // always localhost in XAMPP
$user   = "root";        // default XAMPP username
$pass   = "root";           // default XAMPP password (blank)
$dbname = "smart_health"; // the database we created

// ── Connect ───────────────────────────────────────
$conn = mysqli_connect($host, $user, $pass, $dbname);

// ── Check connection ──────────────────────────────
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>