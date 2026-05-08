<?php
session_start();
require_once "../config/db.php";

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name     = trim($_POST['name']);
    $location = trim($_POST['location']);
    $phone    = trim($_POST['phone']);

    if ($name == "") {
        $error = "Hospital name is required!";
    } else {
        $sql = "INSERT INTO hospitals (name, location, phone)
                VALUES ('$name', '$location', '$phone')";

        if (mysqli_query($conn, $sql)) {
            $success = "✅ Hospital added successfully!";
        } else {
            $error = "❌ Failed: " . mysqli_error($conn);
        }
    }
}

// Fetch all hospitals to show in the list below
$hospitals = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY id DESC");
?>
<html>
<head>
  <title>Add Hospital — Admin</title>
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#1e40af; color:#fff; padding:14px 24px;
              font-size:17px; font-weight:600; }
    .content { padding:30px; max-width:700px; margin:0 auto; }
    .card { background:#fff; padding:24px; border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:24px; }
    h2 { margin:0 0 18px; color:#1a202c; font-size:16px; }
    label { font-size:13px; color:#4a5568; display:block; margin-bottom:4px; }
    input { width:100%; padding:10px; margin-bottom:14px;
            border:1px solid #cbd5e0; border-radius:6px;
            box-sizing:border-box; font-size:14px; }
    button { padding:10px 24px; background:#1e40af; color:#fff;
             border:none; border-radius:6px; font-size:14px; cursor:pointer; }
    button:hover { background:#1e3a8a; }
    .success { color:#38a169; margin-bottom:14px; font-size:13px; font-weight:500; }
    .error   { color:#e53e3e; margin-bottom:14px; font-size:13px; font-weight:500; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th { background:#eff6ff; padding:10px 12px; text-align:left;
         color:#1e40af; font-weight:600; border-bottom:2px solid #bfdbfe; }
    td { padding:10px 12px; border-bottom:1px solid #e2e8f0; color:#2d3748; }
    tr:hover td { background:#f7faff; }
    .badge { font-size:11px; background:#eff6ff; color:#1e40af;
             padding:2px 8px; border-radius:99px; }
  </style>
</head>
<body>

<div class="navbar">🏥 Smart Health — Admin Panel</div>

<div class="content">

  <!-- ADD FORM -->
  <div class="card">
    <h2>➕ Add New Hospital</h2>

    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error)   echo "<p class='error'>$error</p>";   ?>

    <form method="POST">
      <label>Hospital Name *</label>
      <input type="text" name="name"
             placeholder="e.g. Apollo Hospital" required>

      <label>Location</label>
      <input type="text" name="location"
             placeholder="e.g. Ahmedabad, Gujarat">

      <label>Phone Number</label>
      <input type="text" name="phone"
             placeholder="e.g. 079-12345678">

      <button type="submit">Add Hospital</button>
    </form>
  </div>

  <!-- HOSPITALS LIST -->
  <div class="card">
    <h2>🏥 All Hospitals</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Location</th>
        <th>Phone</th>
      </tr>
      <?php while($h = mysqli_fetch_assoc($hospitals)): ?>
      <tr>
        <td><span class="badge"><?= $h['id'] ?></span></td>
        <td><?= $h['name'] ?></td>
        <td><?= $h['location'] ?></td>
        <td><?= $h['phone'] ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

</div>
</body>
</html>