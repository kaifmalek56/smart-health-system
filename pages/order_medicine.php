<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}
$user_id = $_SESSION['user_id'];
$success = ""; $error = "";

// ── Handle order submission ───────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $quantities = $_POST['qty'] ?? []; // array: medicine_id => qty

    // Filter only medicines where qty > 0
    $items = [];
    foreach ($quantities as $med_id => $qty) {
        if (intval($qty) > 0) {
            $items[intval($med_id)] = intval($qty);
        }
    }

    if (empty($items)) {
        $error = "Please select at least one medicine!";
    } else {

        // Step 1: Calculate total
        $total = 0;
        foreach ($items as $med_id => $qty) {
            $med = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT price FROM medicines WHERE id='$med_id'"));
            $total += $med['price'] * $qty;
        }

        // Step 2: Create the order
        mysqli_query($conn, "
            INSERT INTO orders (user_id, total)
            VALUES ('$user_id', '$total')");
        $order_id = mysqli_insert_id($conn);

        // Step 3: Save each item into order_items
        foreach ($items as $med_id => $qty) {
            $med = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT price FROM medicines WHERE id='$med_id'"));
            mysqli_query($conn, "
                INSERT INTO order_items
                    (order_id, medicine_id, quantity, price)
                VALUES
                    ('$order_id','$med_id','$qty','{$med['price']}')");
        }

        $success = "✅ Order #$order_id placed! Total: ₹$total";
    }
}

// Fetch all medicines
$medicines = mysqli_query($conn,
    "SELECT * FROM medicines WHERE stock > 0 ORDER BY name");

// Fetch this user's past orders
$past_orders = mysqli_query($conn, "
    SELECT o.id, o.total, o.status, o.created_at,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = '$user_id'
    GROUP BY o.id
    ORDER BY o.id DESC");
?>
<!DOCTYPE html><html><head>
<title>Order Medicine — Smart Health</title>
<style>
  body{font-family:sans-serif;background:#f0f4f8;margin:0}
  .navbar{background:#3b82f6;color:#fff;padding:14px 24px;
          display:flex;justify-content:space-between;align-items:center}
  .navbar h1{margin:0;font-size:17px}
  .navbar a{color:#fff;font-size:13px;text-decoration:none;
            background:rgba(255,255,255,.2);padding:6px 14px;border-radius:20px}
  .content{padding:30px;max-width:750px;margin:0 auto}
  .card{background:#fff;padding:24px;border-radius:10px;
        box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:20px}
  h2{margin:0 0 16px;font-size:15px;color:#1a202c}
  .med-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
  .med-item{border:1px solid #e2e8f0;border-radius:8px;padding:14px;
            transition:border-color .15s}
  .med-item:hover{border-color:#93c5fd}
  .med-name{font-size:13px;font-weight:600;color:#1a202c;margin-bottom:4px}
  .med-unit{font-size:11px;color:#a0aec0;margin-bottom:8px}
  .med-price{font-size:15px;font-weight:700;color:#1e40af;margin-bottom:10px}
  .qty-row{display:flex;align-items:center;gap:8px}
  .qty-row label{font-size:12px;color:#718096}
  .qty-row input{width:60px;padding:6px 8px;border:1px solid #cbd5e0;
                 border-radius:5px;font-size:13px;text-align:center}
  .total-bar{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;
             padding:14px 18px;margin:16px 0;display:flex;
             justify-content:space-between;align-items:center}
  .total-bar .lbl{font-size:13px;color:#3b82f6}
  .total-bar .amt{font-size:20px;font-weight:700;color:#1e40af}
  button{width:100%;padding:12px;background:#3b82f6;color:#fff;
         border:none;border-radius:6px;font-size:15px;cursor:pointer}
  button:hover{background:#2563eb}
  .success{background:#f0fff4;color:#276749;border:1px solid #9ae6b4;
            padding:14px;border-radius:8px;margin-bottom:16px;font-size:14px;font-weight:500}
  .error{background:#fff5f5;color:#c53030;border:1px solid #feb2b2;
          padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{background:#eff6ff;padding:9px 12px;text-align:left;
     color:#1e40af;font-weight:600;border-bottom:2px solid #bfdbfe}
  td{padding:9px 12px;border-bottom:1px solid #e2e8f0}
  .badge{font-size:11px;padding:2px 8px;border-radius:99px;font-weight:500}
  .pending{background:#fef3c7;color:#92400e}
  .approved{background:#d1fae5;color:#065f46}
  .rejected{background:#fee2e2;color:#991b1b}
</style></head><body>

<div class="navbar">
  <h1>💊 Order Medicine</h1>
  <a href="dashboard.php">← Dashboard</a>
</div>

<div class="content">

  <?php if($success) echo "<div class='success'>$success</div>"; ?>
  <?php if($error)   echo "<div class='error'>$error</div>";   ?>

  <!-- ORDER FORM -->
  <div class="card">
    <h2>🛒 Select Medicines</h2>
    <form method="POST" id="orderForm">

      <div class="med-grid">
        <?php while($m = mysqli_fetch_assoc($medicines)): ?>
        <div class="med-item">
          <div class="med-name"><?= $m['name'] ?></div>
          <div class="med-unit"><?= $m['unit'] ?></div>
          <div class="med-price">₹<?= number_format($m['price'],2) ?></div>
          <div class="qty-row">
            <label>Qty:</label>
            <input type="number"
                   name="qty[<?= $m['id'] ?>]"
                   value="0" min="0" max="<?= $m['stock'] ?>"
                   data-price="<?= $m['price'] ?>"
                   onchange="updateTotal()">
          </div>
        </div>
        <?php endwhile; ?>
      </div>

      <div class="total-bar">
        <span class="lbl">Order Total</span>
        <span class="amt" id="totalDisplay">₹0.00</span>
      </div>

      <button type="submit">Place Order</button>
    </form>
  </div>

  <!-- PAST ORDERS -->
  <div class="card">
    <h2>📦 Your Past Orders</h2>
    <table>
      <tr>
        <th>Order #</th><th>Items</th><th>Total</th>
        <th>Status</th><th>Date</th>
      </tr>
      <?php
        $found = false;
        while($o = mysqli_fetch_assoc($past_orders)):
          $found = true;
      ?>
      <tr>
        <td><b>#<?= $o['id'] ?></b></td>
        <td><?= $o['item_count'] ?> item(s)</td>
        <td><b>₹<?= number_format($o['total'],2) ?></b></td>
        <td>
          <span class="badge <?= $o['status'] ?>">
            <?= ucfirst($o['status']) ?>
          </span>
        </td>
        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      <?php if(!$found): ?>
        <tr><td colspan="5" style="text-align:center;color:#a0aec0;padding:20px">
          No orders yet
        </td></tr>
      <?php endif; ?>
    </table>
  </div>

</div>

<!-- Live total calculator -->
<script>
function updateTotal() {
  const inputs = document.querySelectorAll('input[data-price]');
  let total = 0;
  inputs.forEach(input => {
    const qty   = parseInt(input.value) || 0;
    const price = parseFloat(input.dataset.price) || 0;
    total += qty * price;
  });
  document.getElementById('totalDisplay').textContent =
    '₹' + total.toFixed(2);
}
</script>
</body></html>