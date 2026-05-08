<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error   = "";
$token   = null;

$slots = [
    "09:00 AM","09:30 AM","10:00 AM","10:30 AM",
    "11:00 AM","11:30 AM","12:00 PM",
    "02:00 PM","02:30 PM","03:00 PM","03:30 PM",
    "04:00 PM","04:30 PM","05:00 PM"
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $date      = $_POST['date'];
    $time_slot = $_POST['time_slot'];

    if (!$doctor_id || !$date || !$time_slot) {
        $error = "Please fill in all fields.";
    } elseif ($date < date('Y-m-d')) {
        $error = "You cannot book a past date!";
    } else {
        $check = mysqli_query($conn, "
            SELECT id FROM appointments
            WHERE doctor_id = '$doctor_id'
            AND   date      = '$date'
            AND   time_slot = '$time_slot'
        ");

        if (mysqli_num_rows($check) > 0) {
            $error = "❌ This slot is already booked! Please pick a different time.";
        } else {
            $insert = mysqli_query($conn, "
                INSERT INTO appointments (user_id, doctor_id, date, time_slot)
                VALUES ('$user_id','$doctor_id','$date','$time_slot')
            ");

            if ($insert) {
                $appointment_id = mysqli_insert_id($conn);
                $max = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT MAX(token_number) AS max_token FROM queue
                     WHERE appointment_id IN (
                         SELECT id FROM appointments
                         WHERE doctor_id='$doctor_id' AND date='$date'
                     )"
                ));
                $token = ($max['max_token'] ?? 0) + 1;
                mysqli_query($conn, "
                    INSERT INTO queue (appointment_id, token_number)
                    VALUES ('$appointment_id', '$token')
                ");
                $success = "✅ Appointment booked! Your token number is #$token";
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment — Smart Health</title>
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#3b82f6; color:#fff; padding:14px 24px;
              display:flex; justify-content:space-between; align-items:center; }
    .navbar h1 { margin:0; font-size:17px; }
    .navbar a  { color:#fff; font-size:13px; text-decoration:none;
                 background:rgba(255,255,255,.2); padding:6px 14px; border-radius:20px; }
    .content { padding:30px; max-width:620px; margin:0 auto; }
    .card { background:#fff; padding:26px; border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }
    h2 { margin:0 0 20px; color:#1a202c; font-size:16px; }
    label { font-size:13px; color:#4a5568; display:block; margin-bottom:5px; }
    select, input[type=date] {
      width:100%; padding:10px; margin-bottom:16px;
      border:1px solid #cbd5e0; border-radius:6px;
      box-sizing:border-box; font-size:14px; background:#fff; }
    button { width:100%; padding:12px; background:#3b82f6; color:#fff;
             border:none; border-radius:6px; font-size:15px; cursor:pointer; }
    button:hover { background:#2563eb; }
    .error   { background:#fff5f5; color:#c53030; border:1px solid #feb2b2;
               padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
    .success { background:#f0fff4; color:#276749; border:1px solid #9ae6b4;
               padding:16px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:500; }
    .token-box { text-align:center; padding:20px; background:#eff6ff; border-radius:10px; margin-top:14px; }
    .token-box .num { font-size:48px; font-weight:700; color:#1e40af; }
    .token-box p { margin:4px 0 0; color:#3b82f6; font-size:13px; }
  </style>
</head>
<body>

<div class="navbar">
  <h1>📅 Book Appointment</h1>
  <a href="dashboard.php">← Dashboard</a>
</div>

<div class="content">

  <?php if ($token): ?>
  <div class="card">
    <div class="success"><?= $success ?></div>
    <div class="token-box">
      <p>Your Queue Token</p>
      <div class="num">#<?= $token ?></div>
      <p>Save this number — check your wait time on Queue Status</p>
    </div>
    <br>
    <a href="queue_status.php">
      <button style="background:#1e40af">⏱ Check Queue Status</button>
    </a>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>📋 Book Your Appointment</h2>

    <?php if($error) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">

      <label>Step 1 — Select Hospital *</label>
      <select id="hospitalSelect" onchange="loadDoctors(this.value)">
        <option value="">-- Choose a Hospital --</option>
        <?php
          $hosp_list = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY name");
          while($h = mysqli_fetch_assoc($hosp_list)):
        ?>
        <option value="<?= $h['id'] ?>"><?= $h['name'] ?></option>
        <?php endwhile; ?>
      </select>

      <label>Step 2 — Select Doctor *</label>
      <select name="doctor_id" id="doctorSelect" required>
        <option value="">-- Choose a hospital first --</option>
      </select>

      <label>Step 3 — Appointment Date *</label>
      <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>

      <label>Step 4 — Time Slot *</label>
      <select name="time_slot" required>
        <option value="">-- Choose a Time --</option>
        <?php foreach($slots as $slot): ?>
          <option><?= $slot ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Confirm Booking</button>
    </form>
  </div>

</div>

<script>
function loadDoctors(hospitalId) {
  const doctorSelect = document.getElementById('doctorSelect');
  doctorSelect.innerHTML = '<option value="">Loading...</option>';

  if (!hospitalId) {
    doctorSelect.innerHTML = '<option value="">-- Choose a hospital first --</option>';
    return;
  }

  fetch('get_doctors.php?hospital_id=' + hospitalId)
    .then(res => res.json())
    .then(doctors => {
      doctorSelect.innerHTML = '<option value="">-- Choose a Doctor --</option>';
      if (doctors.length === 0) {
        doctorSelect.innerHTML = '<option value="">No doctors at this hospital</option>';
        return;
      }
      doctors.forEach(doc => {
        const opt = document.createElement('option');
        opt.value = doc.id;
        opt.textContent = doc.name + ' — ' + doc.specialization;
        doctorSelect.appendChild(opt);
      });
    });
}
</script>
</body>
</html>