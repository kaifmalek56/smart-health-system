<?php
session_start();
require_once "../config/db.php";

$success = "";
$error   = "";

// Fetch all hospitals for the dropdown
$hospitals = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name           = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $hospital_id    = intval($_POST['hospital_id']);
    $available_days = trim($_POST['available_days']);

    if ($name == "" || $specialization == "" || $hospital_id == 0) {
        $error = "Name, specialization and hospital are required!";
    } else {
        $sql = "INSERT INTO doctors
                    (name, specialization, hospital_id, available_days)
                VALUES
                    ('$name','$specialization','$hospital_id','$available_days')";

        if (mysqli_query($conn, $sql)) {
            $success = "✅ Dr. $name added successfully!";
        } else {
            $error = "❌ Failed: " . mysqli_error($conn);
        }
    }

    // Re-fetch hospitals after POST so dropdown still works
    $hospitals = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY name ASC");
}

// Fetch all doctors with their hospital name (JOIN)
$doctors = mysqli_query($conn, "
    SELECT d.*, h.name AS hospital_name
    FROM doctors d
    JOIN hospitals h ON d.hospital_id = h.id
    ORDER BY d.id DESC
");
?>
<html>
<head>
  <title>Add Doctor — Admin</title>
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#1e40af; color:#fff; padding:14px 24px;
              font-size:17px; font-weight:600; }
    .nav-links { float:right; font-size:13px; }
    .nav-links a { color:#fff; margin-left:16px; text-decoration:none; }
    .content { padding:30px; max-width:800px; margin:0 auto; }
    .card { background:#fff; padding:24px; border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:24px; }
    h2 { margin:0 0 18px; color:#1a202c; font-size:16px; }
    label { font-size:13px; color:#4a5568; display:block; margin-bottom:4px; }
    input, select { width:100%; padding:10px; margin-bottom:14px;
                    border:1px solid #cbd5e0; border-radius:6px;
                    box-sizing:border-box; font-size:14px;
                    background:#fff; }
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
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
    .badge  { font-size:11px; padding:2px 8px; border-radius:99px; }
    .b-blue { background:#eff6ff; color:#1e40af; }
    .b-green{ background:#f0fdf4; color:#166534; }
  </style>
</head>
<body>

<div class="navbar">
  🏥 Smart Health — Admin Panel
  <span class="nav-links">
    <a href="add_hospital.php">Hospitals</a>
    <a href="add_doctor.php">Doctors</a>
  </span>
</div>

<div class="content">

  <!-- ADD FORM -->
  <div class="card">
    <h2>➕ Add New Doctor</h2>

    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error)   echo "<p class='error'>$error</p>";   ?>

    <form method="POST">

      <label>Doctor's Full Name *</label>
      <input type="text" name="name"
             placeholder="e.g. Dr. Ravi Sharma" required>

      <div class="row2">

        <div>
          <label>Specialization *</label>
          <select name="specialization" required>
            <option value="">-- Select --</option>
            <option>General Physician</option>
            <option>Cardiologist</option>
            <option>Dermatologist</option>
            <option>Orthopedist</option>
            <option>Neurologist</option>
            <option>Pediatrician</option>
            <option>Gynecologist</option>
            <option>ENT Specialist</option>
            <option>Ophthalmologist</option>
            <option>Psychiatrist</option>
            <option>Dentist</option>
            <option>Urologist</option>
            <option>IVF</option>
          </select>
        </div>

        <div>
          <label>Hospital *</label>
          <select name="hospital_id" required>
            <option value="0">-- Select Hospital --</option>
            <?php while($h = mysqli_fetch_assoc($hospitals)): ?>
              <option value="<?= $h['id'] ?>">
                <?= $h['name'] ?> — <?= $h['location'] ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

      </div>

      <label>Available Days</label>
      <input type="text" name="available_days"
             placeholder="e.g. Mon-Fri or Mon, Wed, Fri"
             value="Mon-Sat">

      <button type="submit">Add Doctor</button>
    </form>
  </div>

  <!-- DOCTORS LIST -->
  <div class="card">
    <h2>👨‍⚕️ All Doctors</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Specialization</th>
        <th>Hospital</th>
        <th>Available Days</th>
      </tr>
      <?php while($d = mysqli_fetch_assoc($doctors)): ?>
      <tr>
        <td><span class="badge b-blue"><?= $d['id'] ?></span></td>
        <td><?= $d['name'] ?></td>
        <td><span class="badge b-green"><?= $d['specialization'] ?></span></td>
        <td><?= $d['hospital_name'] ?></td>
        <td><?= $d['available_days'] ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

</div>
</body></html>