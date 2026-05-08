<?php
session_start();
require_once "../config/db.php";

// Handle approve / reject
if (isset($_POST['order_id'], $_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $action   = $_POST['action']; // 'approved' or 'rejected'
    mysqli_query($conn,
        "UPDATE orders SET status='$action' WHERE id='$order_id'");
    header("Location: manage_orders.php"); exit();
}

$orders = mysqli_query($conn, "
    SELECT o.*, u.name AS patient_name,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id
    ORDER BY o.id DESC");
?>
<!DOCTYPE html><html><head>
<title>Manage Orders</title>
<style>
  body{font-family:sans-serif;background:#f0f4f8;margin:0}
  .navbar{background:#1e40af;color:#fff;padding:14px 24px;font-size:17px;font-weight:600}
  .content{padding:30px;max-width:800px;margin:0 auto}
  .card{background:#fff;padding:22px;border-radius:10px;
        box-shadow:0 2px 10px rgba(0,0,0,.07)}
  h2{margin:0 0 16px;font-size:15px}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{background:#eff6ff;padding:10px 12px;text-align:left;
     color:#1e40af;font-weight:600;border-bottom:2px solid #bfdbfe}
  td{padding:10px 12px;border-bottom:1px solid #e2e8f0;vertical-align:middle}
  .badge{font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600}
  .pending{background:#fef3c7;color:#92400e}
  .approved{background:#d1fae5;color:#065f46}
  .rejected{background:#fee2e2;color:#991b1b}
  .btn{padding:5px 12px;border:none;border-radius:5px;
       font-size:12px;cursor:pointer;font-weight:500;margin-right:4px}
  .btn-approve{background:#10b981;color:#fff}
  .btn-reject {background:#ef4444;color:#fff}
</style></head><body>
<div class="navbar">📦 Smart Health — Manage Orders</div>
<div class="content">
  <div class="card">
    <h2>All Medicine Orders</h2>
    <table>
      <tr>
        <th>#</th><th>Patient</th><th>Items</th>
        <th>Total</th><th>Status</th><th>Action</th>
      </tr>
      <?php while($o=mysqli_fetch_assoc($orders)): ?>
      <tr>
        <td><b>#<?= $o['id'] ?></b></td>
        <td><?= $o['patient_name'] ?></td>
        <td><?= $o['item_count'] ?> item(s)</td>
        <td><b>₹<?= number_format($o['total'],2) ?></b></td>
        <td><span class="badge <?= $o['status'] ?>">
          <?= ucfirst($o['status']) ?></span></td>
        <td>
          <?php if($o['status'] == 'pending'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <button name="action" value="approved" class="btn btn-approve">✓ Approve</button>
            <button name="action" value="rejected" class="btn btn-reject">✗ Reject</button>
          </form>
          <?php else: ?>
            <span style="color:#a0aec0;font-size:12px">Done</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div></body></html>