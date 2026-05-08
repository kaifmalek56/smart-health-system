<?php
session_start();
require_once "../config/db.php";

// ── Handle order approve/reject ───────────────────
if (isset($_POST['order_id'], $_POST['action'])) {
    $oid = intval($_POST['order_id']);
    $act = $_POST['action']; // 'approved' or 'rejected'
    mysqli_query($conn,
        "UPDATE orders SET status='$act' WHERE id='$oid'");
    header("Location: dashboard.php#orders");
    exit();
}

// ── Handle appointment status change ─────────────
if (isset($_POST['appt_id'], $_POST['appt_status'])) {
    $aid = intval($_POST['appt_id']);
    $ast = $_POST['appt_status'];
    mysqli_query($conn,
        "UPDATE appointments SET status='$ast' WHERE id='$aid'");
    header("Location: dashboard.php#appointments");
    exit();
}

// ── Stats for summary cards ───────────────────────
$total_users   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM users"))['n'];

$total_appts   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM appointments"))['n'];

$today_appts   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM appointments WHERE date = CURDATE()"))['n'];

$pending_orders= mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM orders WHERE status='pending'"))['n'];

$total_doctors = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM doctors"))['n'];

$total_hospitals=mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM hospitals"))['n'];

// ── All users ─────────────────────────────────────
$users = mysqli_query($conn,
    "SELECT * FROM users ORDER BY id DESC");

// ── All appointments with doctor + user info ──────
$appts = mysqli_query($conn, "
    SELECT a.*, u.name AS patient, d.name AS doctor,
           d.specialization, h.name AS hospital
    FROM appointments a
    JOIN users    u ON u.id = a.user_id
    JOIN doctors  d ON d.id = a.doctor_id
    JOIN hospitals h ON h.id = d.hospital_id
    ORDER BY a.id DESC
    LIMIT 50
");

// ── All orders ────────────────────────────────────
$orders = mysqli_query($conn, "
    SELECT o.*, u.name AS patient,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id
    ORDER BY o.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard — Smart Health</title>
<style>
  * { box-sizing:border-box; }
  body { font-family:sans-serif; background:#f0f4f8; margin:0; }

  /* ── Navbar ── */
  .navbar {
    background:#1e40af; color:#fff;
    padding:0 24px; height:56px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:99;
  }
  .navbar .logo { font-size:16px; font-weight:700; }
  .nav-links { display:flex; gap:6px; }
  .nav-links a {
    color:#fff; text-decoration:none; font-size:12px;
    padding:6px 12px; border-radius:6px;
    background:rgba(255,255,255,.12);
  }
  .nav-links a:hover { background:rgba(255,255,255,.25); }

  /* ── Layout ── */
  .content { padding:28px; max-width:1100px; margin:0 auto; }

  /* ── Stat cards ── */
  .stats { display:grid;
           grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
           gap:14px; margin-bottom:28px; }
  .stat {
    background:#fff; border-radius:10px; padding:18px 20px;
    box-shadow:0 1px 6px rgba(0,0,0,.07);
    border-left:4px solid #3b82f6;
  }
  .stat .num  { font-size:32px; font-weight:700; color:#1e40af; line-height:1; }
  .stat .lbl  { font-size:12px; color:#718096; margin-top:5px; }
  .stat.green { border-left-color:#10b981; }
  .stat.green .num { color:#065f46; }
  .stat.amber { border-left-color:#f59e0b; }
  .stat.amber .num { color:#92400e; }
  .stat.purple{ border-left-color:#8b5cf6; }
  .stat.purple .num{ color:#5b21b6; }

  /* ── Section cards ── */
  .card {
    background:#fff; border-radius:10px; padding:22px;
    box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:24px;
  }
  .card-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:16px;
  }
  .card-header h2 { margin:0; font-size:15px; color:#1a202c; }
  .card-header a  {
    font-size:12px; color:#3b82f6; text-decoration:none;
    padding:5px 12px; border:1px solid #bfdbfe;
    border-radius:6px;
  }
  .card-header a:hover { background:#eff6ff; }

  /* ── Tables ── */
  .tbl { width:100%; border-collapse:collapse; font-size:13px; }
  .tbl th {
    background:#f8faff; padding:10px 12px; text-align:left;
    color:#1e40af; font-weight:600;
    border-bottom:2px solid #bfdbfe;
  }
  .tbl td {
    padding:10px 12px; border-bottom:1px solid #f0f4f8;
    vertical-align:middle; color:#2d3748;
  }
  .tbl tr:hover td { background:#fafbff; }
  .tbl tr:last-child td { border-bottom:none; }

  /* ── Badges ── */
  .badge { font-size:11px; font-weight:600;
           padding:3px 9px; border-radius:99px; }
  .b-pending  { background:#fef3c7; color:#92400e; }
  .b-confirmed{ background:#d1fae5; color:#065f46; }
  .b-approved { background:#d1fae5; color:#065f46; }
  .b-cancelled{ background:#fee2e2; color:#991b1b; }
  .b-rejected { background:#fee2e2; color:#991b1b; }

  /* ── Action buttons ── */
  .btn { padding:5px 11px; border:none; border-radius:5px;
         font-size:11px; font-weight:600; cursor:pointer;
         margin-right:3px; }
  .btn-confirm  { background:#10b981; color:#fff; }
  .btn-cancel   { background:#ef4444; color:#fff; }
  .btn-approve  { background:#10b981; color:#fff; }
  .btn-reject   { background:#ef4444; color:#fff; }
  .btn:hover    { opacity:.85; }

  /* ── Empty state ── */
  .empty { text-align:center; color:#a0aec0;
           padding:24px; font-size:13px; }

  /* ── Quick links ── */
  .quick-links {
    display:flex; flex-wrap:wrap; gap:10px; margin-bottom:24px;
  }
  .ql {
    display:flex; align-items:center; gap:8px;
    background:#fff; border:1px solid #e2e8f0;
    border-radius:8px; padding:12px 16px;
    text-decoration:none; color:#2d3748; font-size:13px;
    font-weight:500; transition:border-color .15s;
  }
  .ql:hover { border-color:#93c5fd; color:#1e40af; }
  .ql .icon { font-size:18px; }
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
  <div class="logo">🏥 Smart Health — Admin</div>
  <div class="nav-links">
    <a href="add_hospital.php">Hospitals</a>
    <a href="add_doctor.php">Doctors</a>
    <a href="add_medicine.php">Medicines</a>
    <a href="update_queue.php">Queue</a>
  </div>
</div>

<div class="content">

 <!-- STAT CARDS -->
  <div class="stats">
    <div class="stat">
      <div class="num"><?= $total_users ?></div>
      <div class="lbl">👤 Total Users</div>
    </div>
    <div class="stat green">
      <div class="num"><?= $today_appts ?></div>
      <div class="lbl">📅 Today's Appts</div>
    </div>
    <div class="stat">
      <div class="num"><?= $total_appts ?></div>
      <div class="lbl">📋 Total Appts</div>
    </div>
    <div class="stat amber">
      <div class="num"><?= $pending_orders ?></div>
      <div class="lbl">💊 Pending Orders</div>
    </div>
    <div class="stat purple">
      <div class="num"><?= $total_doctors ?></div>
      <div class="lbl">👨‍⚕️ Doctors</div>
    </div>
    <div class="stat green">
      <div class="num"><?= $total_hospitals ?></div>
      <div class="lbl">🏥 Hospitals</div>
    </div>
  </div>

  <!-- QUICK LINKS -->
  <div class="quick-links">
    <a class="ql" href="add_hospital.php">
      <span class="icon">🏥</span> Add Hospital</a>
    <a class="ql" href="add_doctor.php">
      <span class="icon">👨‍⚕️</span> Add Doctor</a>
    <a class="ql" href="add_medicine.php">
      <span class="icon">💊</span> Add Medicine</a>
    <a class="ql" href="update_queue.php">
      <span class="icon">⏱️</span> Manage Queue</a>
  </div>

  <!-- SECTION 1: ALL USERS -->
  <div class="card" id="users">
    <div class="card-header">
      <h2>👤 All Users</h2>
    </div>
    <table class="tbl">
      <tr>
        <th>ID</th><th>Name</th>
        <th>Email</th><th>Joined</th>
      </tr>
      <?php
        $found = false;
        while($u = mysqli_fetch_assoc($users)):
          $found = true;
      ?>
      <tr>
        <td><b>#<?= $u['id'] ?></b></td>
        <td><?= $u['name'] ?></td>
        <td><?= $u['email'] ?></td>
        <td><?= date('d M Y',strtotime($u['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      <?php if(!$found): ?>
        <tr><td colspan="4" class="empty">No users yet</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- SECTION 2: APPOINTMENTS -->
  <div class="card" id="appointments">
    <div class="card-header">
      <h2>📅 All Appointments</h2>
    </div>
    <table class="tbl">
      <tr>
        <th>#</th><th>Patient</th>
        <th>Doctor</th><th>Hospital</th>
        <th>Date</th><th>Time</th>
        <th>Status</th><th>Action</th>
      </tr>
      <?php
        $found = false;
        while($a = mysqli_fetch_assoc($appts)):
          $found = true;
      ?>
      <tr>
        <td><b>#<?= $a['id'] ?></b></td>
        <td><?= $a['patient'] ?></td>
        <td><?= $a['doctor'] ?>
          <small style="display:block;color:#a0aec0">
            <?= $a['specialization'] ?>
          </small></td>
        <td><?= $a['hospital'] ?></td>
        <td><?= date('d M Y',strtotime($a['date'])) ?></td>
        <td><?= $a['time_slot'] ?></td>
        <td>
          <span class="badge b-<?= $a['status'] ?>">
            <?= ucfirst($a['status']) ?>
          </span>
        </td>
        <td>
          <?php if($a['status'] == 'pending'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="appt_id"
                   value="<?= $a['id'] ?>">
            <button name="appt_status" value="confirmed"
                    class="btn btn-confirm">✓ Confirm</button>
            <button name="appt_status" value="cancelled"
                    class="btn btn-cancel">✗ Cancel</button>
          </form>
          <?php else: ?>
            <span style="color:#a0aec0;font-size:12px">Done</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if(!$found): ?>
        <tr><td colspan="8" class="empty">
          No appointments yet
        </td></tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- SECTION 3: MEDICINE ORDERS -->
  <div class="card" id="orders">
    <div class="card-header">
      <h2>💊 Medicine Orders</h2>
    </div>
    <table class="tbl">
      <tr>
        <th>#</th><th>Patient</th>
        <th>Items</th><th>Total</th>
        <th>Date</th><th>Status</th>
        <th>Action</th>
      </tr>
      <?php
        $found = false;
        while($o = mysqli_fetch_assoc($orders)):
          $found = true;
      ?>
      <tr>
        <td><b>#<?= $o['id'] ?></b></td>
        <td><?= $o['patient'] ?></td>
        <td><?= $o['item_count'] ?> item(s)</td>
        <td><b>₹<?= number_format($o['total'],2) ?></b></td>
        <td><?= date('d M Y',strtotime($o['created_at'])) ?></td>
        <td>
          <span class="badge b-<?= $o['status'] ?>">
            <?= ucfirst($o['status']) ?>
          </span>
        </td>
        <td>
          <?php if($o['status'] == 'pending'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="order_id"
                   value="<?= $o['id'] ?>">
            <button name="action" value="approved"
                    class="btn btn-approve">✓ Approve</button>
            <button name="action" value="rejected"
                    class="btn btn-reject">✗ Reject</button>
          </form>
          <?php else: ?>
            <span style="color:#a0aec0;font-size:12px">Done</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if(!$found): ?>
        <tr><td colspan="7" class="empty">
          No orders yet
        </td></tr>
      <?php endif; ?>
    </table>
  </div>

</div>
</body>
</html>