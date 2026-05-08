<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

// ── Problem → Specialization map ─────────────────
$problem_map = [
    'skin'    => ['label' => '🩺 Skin Issue',          'spec' => 'Dermatologist'],
    'heart'   => ['label' => '❤️ Heart Problem',        'spec' => 'Cardiologist'],
    'bone'    => ['label' => '🦴 Bone / Joint Pain',    'spec' => 'Orthopedist'],
    'neuro'   => ['label' => '🧠 Headache / Nerve',     'spec' => 'Neurologist'],
    'child'   => ['label' => '👶 Child Health',          'spec' => 'Pediatrician'],
    'eye'     => ['label' => '👁️ Eye Problem',           'spec' => 'Ophthalmologist'],
    'ent'     => ['label' => '👂 Ear / Nose / Throat',  'spec' => 'ENT Specialist'],
    'general' => ['label' => '🤒 Fever / Cold / General','spec' => 'General Physician'],
    'diabetes'=> ['label' => '🧬 Diabetes / Sugar',     'spec' => 'General Physician'],
    'dental'  => ['label' => '🦷 Tooth / Gum',           'spec' => 'Dentist'],
    'mental'  => ['label' => '🧘 Mental Health',          'spec' => 'Psychiatrist'],
    'women'   => ['label' => '🚺 Women\'s Health',       'spec' => 'Gynecologist'],
];

$results       = [];
$selected_spec = "";
$selected_label= "";

if (isset($_GET['problem']) && isset($problem_map[$_GET['problem']])) {

    $key            = $_GET['problem'];
    $selected_spec = $problem_map[$key]['spec'];
    $selected_label= $problem_map[$key]['label'];

    // Find doctors with matching specialization + their hospital
    $res = mysqli_query($conn, "
        SELECT
            d.id         AS doctor_id,
            d.name       AS doctor_name,
            d.available_days,
            h.name       AS hospital_name,
            h.location,
            h.phone
        FROM doctors d
        JOIN hospitals h ON h.id = d.hospital_id
        WHERE d.specialization = '$selected_spec'
        ORDER BY h.name ASC
    ");

    while ($row = mysqli_fetch_assoc($res)) {
        $results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Find a Specialist — Smart Health</title>
  <style>
    body { font-family:sans-serif; background:#f0f4f8; margin:0; }
    .navbar { background:#3b82f6; color:#fff; padding:14px 24px;
              display:flex; justify-content:space-between; align-items:center; }
    .navbar h1 { margin:0; font-size:17px; }
    .navbar a  { color:#fff; font-size:13px; text-decoration:none;
                 background:rgba(255,255,255,.2);
                 padding:6px 14px; border-radius:20px; }
    .content { padding:30px; max-width:720px; margin:0 auto; }
    .card { background:#fff; padding:24px; border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }
    h2 { margin:0 0 18px; font-size:15px; color:#1a202c; }

    /* Problem picker grid */
    .problem-grid {
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(150px,1fr));
      gap:10px;
    }
    .prob-btn {
      display:block; text-decoration:none;
      border:1.5px solid #e2e8f0; border-radius:10px;
      padding:14px 10px; text-align:center;
      color:#2d3748; font-size:13px; font-weight:500;
      transition:all .15s; background:#fff;
    }
    .prob-btn:hover  { border-color:#93c5fd; background:#eff6ff; color:#1e40af; }
    .prob-btn.active { border-color:#3b82f6; background:#eff6ff; color:#1e40af; }
    .prob-btn .icon  { font-size:24px; display:block; margin-bottom:6px; }

    /* Results */
    .result-header {
      background:linear-gradient(135deg,#1e40af,#3b82f6);
      color:#fff; padding:16px 20px; border-radius:10px;
      margin-bottom:16px; display:flex;
      justify-content:space-between; align-items:center;
    }
    .result-header h3 { margin:0; font-size:15px; }
    .result-header .count {
      background:rgba(255,255,255,.2); padding:4px 12px;
      border-radius:99px; font-size:12px;
    }
    .doc-card {
      border:1px solid #e2e8f0; border-radius:10px;
      padding:16px 18px; margin-bottom:12px;
      display:flex; justify-content:space-between;
      align-items:flex-start; gap:14px;
      transition:border-color .15s;
    }
    .doc-card:hover { border-color:#93c5fd; }
    .doc-info { flex:1; }
    .doc-name { font-size:14px; font-weight:600; color:#1a202c; margin-bottom:4px; }
    .doc-spec { font-size:12px; color:#3b82f6; font-weight:500; margin-bottom:8px; }
    .doc-meta { font-size:12px; color:#718096; line-height:1.7; }
    .doc-meta span { margin-right:12px; }
    .doc-actions { display:flex; flex-direction:column; gap:6px; }
    .btn-book {
      padding:8px 16px; background:#3b82f6; color:#fff;
      border:none; border-radius:6px; font-size:12px;
      cursor:pointer; text-decoration:none; text-align:center;
      white-space:nowrap;
    }
    .btn-book:hover { background:#2563eb; }
    .spec-badge {
      font-size:11px; padding:3px 10px; border-radius:99px;
      background:#eff6ff; color:#1e40af; font-weight:500;
    }
    .no-results {
      text-align:center; padding:30px; color:#a0aec0; font-size:14px;
    }
    .no-results .icon { font-size:36px; margin-bottom:10px; }
    .tip-box {
      background:#fffbeb; border:1px solid #fcd34d;
      border-radius:8px; padding:12px 16px;
      font-size:13px; color:#92400e; margin-top:16px;
    }
  </style>
</head>
<body>

<div class="navbar">
  <h1>📍 Find a Specialist</h1>
  <a href="dashboard.php">← Dashboard</a>
</div>

<div class="content">

  <!-- PROBLEM PICKER -->
  <div class="card">
    <h2>What is your health concern?</h2>
    <div class="problem-grid">
      <?php foreach($problem_map as $key => $info): ?>
        <?php
          // Split emoji + text for display
          preg_match('/^(\S+)\s(.+)$/', $info['label'], $m);
          $icon = $m[1] ?? '🏥';
          $text = $m[2] ?? $info['label'];
          $active = (isset($_GET['problem']) && $_GET['problem'] == $key)
                    ? 'active' : '';
        ?>
        <a href="?problem=<?= $key ?>"
           class="prob-btn <?= $active ?>">
          <span class="icon"><?= $icon ?></span>
          <?= $text ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RESULTS -->
  <?php if ($selected_spec): ?>
  <div class="card">

    <div class="result-header">
      <h3>
        Results for: <?= $selected_label ?>
      </h3>
      <span class="count">
        <?= count($results) ?> doctor(s) found
      </span>
    </div>

    <?php if (empty($results)): ?>
      <div class="no-results">
        <div class="icon">😔</div>
        No <?= $selected_spec ?> found in our system yet.<br>
        <small>Ask admin to add doctors with this specialization.</small>
      </div>
    <?php else: ?>

      <?php foreach($results as $r): ?>
      <div class="doc-card">
        <div class="doc-info">
          <div class="doc-name"><?= $r['doctor_name'] ?></div>
          <div class="doc-spec">
            <span class="spec-badge"><?= $selected_spec ?></span>
          </div>
          <div class="doc-meta">
            <span>🏥 <?= $r['hospital_name'] ?></span>
            <span>📍 <?= $r['location'] ?></span><br>
            <span>📞 <?= $r['phone'] ?: 'N/A' ?></span>
            <span>📅 <?= $r['available_days'] ?></span>
          </div>
        </div>
        <div class="doc-actions">
          <a class="btn-book"
             href="book_appointment.php">
            📅 Book Now
          </a>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="tip-box">
        💡 <b>Tip:</b> Click "Book Now" to schedule an appointment
        with any of these doctors directly.
      </div>

    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>
</body></html>