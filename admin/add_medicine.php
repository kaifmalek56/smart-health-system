<?php
session_start();
require_once "../config/db.php";
$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name  = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $unit  = trim($_POST['unit']);
    if (!$name || !$price) {
        $error = "Name and price are required!";
    } else {
        mysqli_query($conn, "
            INSERT INTO medicines (name, price, stock, unit)
            VALUES ('$name','$price','$stock','$unit')");
        $success = "✅ Medicine added!";
    }
}
$meds = mysqli_query($conn, "SELECT * FROM medicines ORDER BY name");
?>
<!DOCTYPE html><html><head>
<title>Add Medicine</title>
<style>
  body{font-family:sans-serif;background:#f0f4f8;margin:0}
  .navbar{background:#1e40af;color:#fff;padding:14px 24px;font-size:17px;font-weight:600}
  .content{padding:30px;max-width:700px;margin:0 auto}
  .card{background:#fff;padding:24px;border-radius:10px;
        box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:20px}
  h2{margin:0 0 18px;font-size:15px;color:#1a202c}
  label{font-size:13px;color:#4a5568;display:block;margin-bottom:4px}
  input,select{width:100%;padding:10px;margin-bottom:14px;
    border:1px solid #cbd5e0;border-radius:6px;
    box-sizing:border-box;font-size:14px}
  .row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  button{padding:10px 24px;background:#1e40af;color:#fff;
    border:none;border-radius:6px;font-size:14px;cursor:pointer}
  .success{color:#38a169;font-size:13px;margin-bottom:12px;font-weight:500}
  .error{color:#e53e3e;font-size:13px;margin-bottom:12px;font-weight:500}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{background:#eff6ff;padding:10px 12px;text-align:left;
     color:#1e40af;font-weight:600;border-bottom:2px solid #bfdbfe}
  td{padding:10px 12px;border-bottom:1px solid #e2e8f0}
  .price{font-weight:600;color:#1e40af}
</style></head><body>
<div class="navbar">💊 Smart Health — Add Medicine</div>
<div class="content">
  <div class="card">
    <h2>➕ Add New Medicine</h2>
    <?php if($success) echo "<p class='success'>$success</p>"; ?>
    <?php if($error)   echo "<p class='error'>$error</p>";   ?>
    <form method="POST">
      <label>Medicine Name *</label>
      <input type="text" name="name" placeholder="e.g. Paracetamol 500mg" required>
      <div class="row2">
        <div>
          <label>Price (₹) *</label>
          <input type="number" name="price" step="0.01" placeholder="e.g. 25.00" required>
        </div>
        <div>
          <label>Stock Quantity</label>
          <input type="number" name="stock" value="100">
        </div>
      </div>
      <label>Unit</label>
      <input type="text" name="unit" placeholder="e.g. strip of 10" value="strip of 10">
      <button type="submit">Add Medicine</button>
    </form>
  </div>
  <div class="card">
    <h2>💊 All Medicines</h2>
    <table>
      <tr><th>Name</th><th>Price</th><th>Stock</th><th>Unit</th></tr>
      <?php while($m=mysqli_fetch_assoc($meds)): ?>
      <tr>
        <td><?= $m['name'] ?></td>
        <td class="price">₹<?= number_format($m['price'],2) ?></td>
        <td><?= $m['stock'] ?></td>
        <td><?= $m['unit'] ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div></body></html>