<?php
session_start();
require_once "../config/db.php";

// Admin marks a token as 'serving' or 'done'
if (isset($_POST['action'])) {
    $queue_id = intval($_POST['queue_id']);
    $action   = $_POST['action']; // 'serving' or 'done'

    if ($action == 'serving') {
        // First set all others to 'waiting' for this doctor today
        $appt = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT appointment_id FROM queue WHERE id='$queue_id'"));
        $doc_row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT doctor_id, date FROM appointments
             WHERE id='{$appt['appointment_id']}'"));

        mysqli_query($conn, "
            UPDATE queue SET status='waiting'
            WHERE appointment_id IN (
                SELECT id FROM appointments
                WHERE doctor_id='{$doc_row['doctor_id']}'
                AND date='{$doc_row['date']}'
            )
        ");
    }

    // Now update this specific token
    mysqli_query($conn,
        "UPDATE queue SET status='$action' WHERE id='$queue_id'");

    header("Location: update_queue.php");
    exit();
}

// Show all today's queues grouped by doctor
$queues = mysqli_query($conn, "
    SELECT
        q.id, q.token_number, q.status,
        u.name  AS patient_name,
        d.name  AS doctor_name,
        a.date, a.time_slot
    FROM queue q
    JOIN appointments a ON a.id = q.appointment_id
    JOIN users        u ON u.id = a.user_id
    JOIN doctors      d ON d.id = a.doctor_id
    WHERE a.date = CURDATE()
    ORDER BY d.name, q.token_number ASC
");
?>
<html>
<head>
  <title>Manage Queue — Admin</title>
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#1e40af; color:#fff; padding:14px 24px; font-size:17px; font-weight:600; }
    .content { padding:30px; max-width:800px; margin:0 auto; }
    .card { background:#fff; padding:22px; border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }
    h2 { margin:0 0 16px; font-size:15px; color:#1a202c; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th { background:#eff6ff; padding:10px 12px; text-align:left;
         color:#1e40af; font-weight:600; border-bottom:2px solid #bfdbfe; }
    td { padding:10px 12px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
    tr:hover td { background:#f7faff; }
    .badge { font-size:11px; padding:3px 10px; border-radius:99px; font-weight:600; }
    .waiting { background:#fef3c7; color:#92400e; }
    .serving { background:#d1fae5; color:#065f46; }
    .done    { background:#e0e7ff; color:#3730a3; }
    .btn { padding:5px 12px; border:none; border-radius:5px;
           font-size:12px; cursor:pointer; font-weight:500; margin-right:4px; }
    .btn-serve { background:#10b981; color:#fff; }
    .btn-done  { background:#6366f1; color:#fff; }
    .btn-serve:hover { background:#059669; }
    .btn-done:hover  { background:#4f46e5; }
    .empty { color:#a0aec0; font-size:13px; text-align:center; padding:20px; }
  </style>
</head>
<body>
<div class="navbar">🏥 Smart Health — Manage Today's Queue</div>
<div class="content">
  <div class="card">
    <h2>📋 Today's Patients — <?= date('D, d M Y') ?></h2>
    <table>
      <tr>
        <th>Token</th>
        <th>Patient</th>
        <th>Doctor</th>
        <th>Time</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
      <?php
        $found = false;
        while($q = mysqli_fetch_assoc($queues)):
          $found = true;
      ?>
      <tr>
        <td><b>#<?= $q['token_number'] ?></b></td>
        <td><?= $q['patient_name'] ?></td>
        <td><?= $q['doctor_name'] ?></td>
        <td><?= $q['time_slot'] ?></td>
        <td>
          <span class="badge <?= $q['status'] ?>">
            <?= ucfirst($q['status']) ?>
          </span>
        </td>
        <td>
          <?php if($q['status'] != 'done'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="queue_id" value="<?= $q['id'] ?>">
            <?php if($q['status'] == 'waiting'): ?>
              <button name="action" value="serving" class="btn btn-serve">
                ▶ Call In
              </button>
            <?php endif; ?>
            <button name="action" value="done" class="btn btn-done">
              ✓ Done
            </button>
          </form>
          <?php else: ?>
            <span style="color:#a0aec0;font-size:12px">Complete</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if(!$found): ?>
        <tr><td colspan="6" class="empty">No appointments today</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
</body></html>