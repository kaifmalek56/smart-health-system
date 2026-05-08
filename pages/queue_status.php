<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Get this user's latest appointment + queue token
$my = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        a.id         AS appointment_id,
        a.date,
        a.time_slot,
        a.doctor_id,
        q.token_number,
        q.status     AS queue_status,
        d.name       AS doctor_name,
        d.specialization,
        h.name       AS hospital_name
    FROM appointments a
    JOIN queue   q ON q.appointment_id = a.id
    JOIN doctors d ON d.id = a.doctor_id
    JOIN hospitals h ON h.id = d.hospital_id
    WHERE a.user_id = '$user_id'
    ORDER BY a.id DESC
    LIMIT 1
"));

if ($my) {
    $my_token  = $my['token_number'];
    $doctor_id = $my['doctor_id'];
    $appt_date = $my['date'];

    // Get the currently serving token for this doctor today
    $serving_row = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT token_number FROM queue
        WHERE status = 'serving'
        AND appointment_id IN (
            SELECT id FROM appointments
            WHERE doctor_id = '$doctor_id'
            AND date = '$appt_date'
        )
        ORDER BY token_number DESC
        LIMIT 1
    "));

    $now_serving = $serving_row['token_number'] ?? 0;
    $ahead       = max(0, $my_token - $now_serving - 1);
    $wait_mins   = $ahead * 5; // 5 min per patient

    // Total tokens for this doctor today (for progress bar)
    $total_row = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT MAX(token_number) AS total FROM queue
        WHERE appointment_id IN (
            SELECT id FROM appointments
            WHERE doctor_id='$doctor_id' AND date='$appt_date'
        )
    "));
    $total    = $total_row['total'] ?? 1;
    $progress = round(($now_serving / $total) * 100);
}
?>
<html>
<head>
  <title>Queue Status — Smart Health</title>
  <meta http-equiv="refresh" content="30"> <!-- auto-refresh every 30s -->
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#3b82f6; color:#fff; padding:14px 24px;
              display:flex; justify-content:space-between; align-items:center; }
    .navbar h1 { margin:0; font-size:17px; }
    .navbar a  { color:#fff; font-size:13px; text-decoration:none;
                 background:rgba(255,255,255,.2);
                 padding:6px 14px; border-radius:20px; }
    .content { padding:30px; max-width:560px; margin:0 auto; }

    .queue-card {
      background: linear-gradient(135deg, #1e40af, #3b82f6);
      border-radius:14px; padding:30px; color:#fff;
      text-align:center; margin-bottom:20px;
      box-shadow:0 4px 20px rgba(59,130,246,.4);
    }
    .queue-card .label { font-size:13px; opacity:.75; margin-bottom:6px; }
    .queue-card .token { font-size:72px; font-weight:700; line-height:1; }
    .stats-grid {
      display:grid; grid-template-columns:1fr 1fr 1fr;
      gap:12px; margin-top:20px;
    }
    .stat-box {
      background:rgba(255,255,255,.15);
      border-radius:10px; padding:14px 8px;
    }
    .stat-box .num { font-size:26px; font-weight:700; }
    .stat-box .lbl { font-size:11px; opacity:.75; margin-top:3px; }
    .progress-wrap { margin-top:20px; }
    .progress-label { display:flex; justify-content:space-between;
                      font-size:12px; opacity:.7; margin-bottom:6px; }
    .progress-bar { background:rgba(255,255,255,.2);
                    border-radius:99px; height:10px; overflow:hidden; }
    .progress-fill { background:#fff; border-radius:99px; height:100%;
                     transition:width .5s ease; }

    .info-card {
      background:#fff; border-radius:10px; padding:20px;
      box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:16px;
    }
    .info-card h3 { margin:0 0 14px; font-size:14px; color:#1a202c; }
    .info-row { display:flex; justify-content:space-between;
                font-size:13px; padding:7px 0;
                border-bottom:1px solid #f0f4f8; }
    .info-row:last-child { border-bottom:none; }
    .info-row .key { color:#718096; }
    .info-row .val { color:#1a202c; font-weight:500; }

    .status-badge {
      display:inline-block; padding:4px 12px;
      border-radius:99px; font-size:12px; font-weight:600;
      margin-top:14px;
    }
    .waiting  { background:#fef3c7; color:#92400e; }
    .serving  { background:#d1fae5; color:#065f46; }
    .done     { background:#e0e7ff; color:#3730a3; }

    .no-appt { background:#fff; border-radius:10px; padding:30px;
               text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.07); }
    .no-appt h3 { color:#4a5568; margin-bottom:10px; }
    .no-appt a button { padding:10px 24px; background:#3b82f6;
                        color:#fff; border:none; border-radius:6px;
                        font-size:14px; cursor:pointer; }
    .refresh-note { text-align:center; font-size:12px;
                    color:#a0aec0; margin-top:10px; }
  </style>
</head>
<body>

<div class="navbar">
  <h1>⏱️ Queue Status</h1>
  <a href="dashboard.php">← Dashboard</a>
</div>

<div class="content">

<?php if ($my): ?>

  <!-- MAIN TOKEN CARD -->
  <div class="queue-card">
    <div class="label">Your Queue Token</div>
    <div class="token">#<?= $my_token ?></div>

    <div class="stats-grid">
      <div class="stat-box">
        <div class="num"><?= $now_serving ?: '—' ?></div>
        <div class="lbl">Now Serving</div>
      </div>
      <div class="stat-box">
        <div class="num"><?= $ahead ?></div>
        <div class="lbl">Ahead of You</div>
      </div>
      <div class="stat-box">
        <div class="num">~<?= $wait_mins ?>m</div>
        <div class="lbl">Est. Wait</div>
      </div>
    </div>

    <div class="progress-wrap">
      <div class="progress-label">
        <span>Token #1</span>
        <span><?= $progress ?>% through queue</span>
        <span>Token #<?= $total ?></span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill"
             style="width:<?= $progress ?>%"></div>
      </div>
    </div>

    <div>
      <?php
        $qs = $my['queue_status'];
        $badge = $qs == 'serving' ? 'serving' :
                 ($qs == 'done'    ? 'done'    : 'waiting');
        $label = $qs == 'serving' ? '🟢 You are being served now!' :
                 ($qs == 'done'    ? '✅ Done — visit complete'    :
                                      '🟡 Waiting in queue');
      ?>
      <span class="status-badge <?= $badge ?>">
        <?= $label ?>
      </span>
    </div>
  </div>

  <!-- APPOINTMENT DETAILS -->
  <div class="info-card">
    <h3>📋 Your Appointment Details</h3>
    <div class="info-row">
      <span class="key">Doctor</span>
      <span class="val"><?= $my['doctor_name'] ?></span>
    </div>
    <div class="info-row">
      <span class="key">Specialization</span>
      <span class="val"><?= $my['specialization'] ?></span>
    </div>
    <div class="info-row">
      <span class="key">Hospital</span>
      <span class="val"><?= $my['hospital_name'] ?></span>
    </div>
    <div class="info-row">
      <span class="key">Date</span>
      <span class="val"><?= date('D, d M Y', strtotime($my['date'])) ?></span>
    </div>
    <div class="info-row">
      <span class="key">Time Slot</span>
      <span class="val"><?= $my['time_slot'] ?></span>
    </div>
  </div>

  <p class="refresh-note">
    🔄 Page auto-refreshes every 30 seconds
  </p>

<?php else: ?>

  <div class="no-appt">
    <h3>No active appointment found</h3>
    <p style="color:#718096;font-size:13px">
      Book an appointment first to see your queue status.
    </p>
    <a href="book_appointment.php">
      <button>📅 Book Now</button>
    </a>
  </div>

<?php endif; ?>

</div>
</body></html>