<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

// ── Symptom keyword database ──────────────────────
// Each entry: keywords to match → doctor + advice
$symptom_db = [
    [
        'keywords' => ['fever','temperature','chills','sweating','hot'],
        'doctor'   => 'General Physician',
        'urgency'  => 'medium',
        'advice'   => [
            'Rest and stay hydrated',
            'Take paracetamol if temperature exceeds 38°C',
            'Use a cool damp cloth on your forehead',
            'See a doctor if fever lasts more than 3 days',
        ],
    ],
    [
        'keywords' => ['headache','migraine','head pain','head'],
        'doctor'   => 'Neurologist',
        'urgency'  => 'medium',
        'advice'   => [
            'Rest in a dark quiet room',
            'Avoid screens for a few hours',
            'Stay hydrated — dehydration causes headaches',
            'Apply a cold or warm pack to your forehead',
        ],
    ],
    [
        'keywords' => ['chest pain','chest','heart','palpitation','breathless'],
        'doctor'   => 'Cardiologist',
        'urgency'  => 'high',
        'advice'   => [
            '⚠️ Chest pain can be serious — seek help immediately',
            'Sit down and rest, do not exert yourself',
            'Loosen tight clothing',
            'Call emergency services if pain is severe',
        ],
    ],
    [
        'keywords' => ['skin','rash','itching','acne','itch','pimple'],
        'doctor'   => 'Dermatologist',
        'urgency'  => 'low',
        'advice'   => [
            'Avoid scratching — it worsens irritation',
            'Apply a cool damp cloth to the affected area',
            'Use fragrance-free soap and moisturiser',
            'Avoid known allergens or harsh chemicals',
        ],
    ],
    [
        'keywords' => ['cough','cold','sore throat','throat','sneeze','runny nose'],
        'doctor'   => 'General Physician',
        'urgency'  => 'low',
        'advice'   => [
            'Drink warm liquids — honey + ginger tea works well',
            'Inhale steam twice a day',
            'Gargle with warm salt water for sore throat',
            'Rest and avoid cold food or drinks',
        ],
    ],
    [
        'keywords' => ['eye','vision','blur','sight','eyes','red eye'],
        'doctor'   => 'Ophthalmologist',
        'urgency'  => 'medium',
        'advice'   => [
            'Do not rub your eyes',
            'Rinse with clean water if irritated',
            'Avoid screens and bright lights',
            'See a doctor if vision is affected',
        ],
    ],
    [
        'keywords' => ['ear','hearing','tinnitus','nose','sinus'],
        'doctor'   => 'ENT Specialist',
        'urgency'  => 'medium',
        'advice'   => [
            'Avoid inserting objects into the ear',
            'Steam inhalation helps with sinus congestion',
            'Stay away from loud noises',
            'See a doctor if pain or discharge is present',
        ],
    ],
    [
        'keywords' => ['joint','knee','back pain','back','bone','fracture','sprain'],
        'doctor'   => 'Orthopedist',
        'urgency'  => 'medium',
        'advice'   => [
            'Rest the affected area — avoid strain',
            'Apply ice pack for 15 mins every 2 hours',
            'Elevate the limb if swollen',
            'Do not attempt to straighten a suspected fracture',
        ],
    ],
    [
        'keywords' => ['stomach','vomit','nausea','diarrhea','acidity','gas','bloat'],
        'doctor'   => 'General Physician',
        'urgency'  => 'low',
        'advice'   => [
            'Drink ORS or coconut water to stay hydrated',
            'Eat light food — khichdi, curd rice, toast',
            'Avoid spicy, oily, or heavy food',
            'See a doctor if vomiting persists more than 24 hours',
        ],
    ],
    [
        'keywords' => ['anxiety','stress','depression','sleep','insomnia','panic'],
        'doctor'   => 'Psychiatrist',
        'urgency'  => 'medium',
        'advice'   => [
            'Practice slow deep breathing for 5 minutes',
            'Step outside for fresh air and a short walk',
            'Talk to someone you trust',
            'Reduce caffeine and screen time before bed',
        ],
    ],
    [
        'keywords' => ['tooth','gum','dental','teeth','mouth pain'],
        'doctor'   => 'Dentist',
        'urgency'  => 'low',
        'advice'   => [
            'Rinse with warm salt water',
            'Avoid very hot or very cold food',
            'Use clove oil on the affected area for temporary relief',
            'Book a dental appointment as soon as possible',
        ],
    ],
    [
        'keywords' => ['sugar','diabetes','thirst','urination','fatigue'],
        'doctor'   => 'General Physician',
        'urgency'  => 'medium',
        'advice'   => [
            'Avoid sugary drinks and processed food',
            'Monitor your blood sugar if you have a kit',
            'Drink plenty of water',
            'See a doctor for a proper blood test',
        ],
    ],
];

// ── Process input ─────────────────────────────────
$matches    = [];
$input_raw  = "";
$no_match   = false;

if (isset($_POST['symptoms']) && trim($_POST['symptoms']) !== '') {

    $input_raw = trim($_POST['symptoms']);
    $input     = strtolower($input_raw); // lowercase for matching

    foreach ($symptom_db as $entry) {
        foreach ($entry['keywords'] as $kw) {
            if (strpos($input, $kw) !== false) {
                $matches[] = $entry;
                break; // one match per entry is enough
            }
        }
    }

    if (empty($matches)) $no_match = true;
}
?>
<html>
<head>
  <title>Symptom Checker — Smart Health</title>
  <style>
    body{font-family:sans-serif;background:#f0f4f8;margin:0}
    .navbar{background:#3b82f6;color:#fff;padding:14px 24px;
            display:flex;justify-content:space-between;align-items:center}
    .navbar h1{margin:0;font-size:17px}
    .navbar a{color:#fff;font-size:13px;text-decoration:none;
              background:rgba(255,255,255,.2);padding:6px 14px;border-radius:20px}
    .content{padding:30px;max-width:680px;margin:0 auto}
    .card{background:#fff;padding:24px;border-radius:10px;
          box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:20px}
    h2{margin:0 0 16px;font-size:15px;color:#1a202c}

    textarea{width:100%;padding:12px;border:1.5px solid #cbd5e0;
             border-radius:8px;font-size:14px;resize:vertical;
             min-height:90px;box-sizing:border-box;font-family:sans-serif}
    textarea:focus{outline:none;border-color:#3b82f6}
    button{padding:11px 28px;background:#3b82f6;color:#fff;
           border:none;border-radius:6px;font-size:14px;cursor:pointer;
           margin-top:10px}
    button:hover{background:#2563eb}

    .hint{font-size:12px;color:#a0aec0;margin-top:6px}

    .result-card{border-radius:10px;padding:20px;margin-bottom:14px;
                 border:1px solid #e2e8f0}
    .urgency-high   {border-left:4px solid #ef4444;background:#fff5f5}
    .urgency-medium {border-left:4px solid #f59e0b;background:#fffbeb}
    .urgency-low    {border-left:4px solid #10b981;background:#f0fff4}

    .result-top{display:flex;justify-content:space-between;
                align-items:flex-start;margin-bottom:12px}
    .doc-suggest{font-size:14px;font-weight:600;color:#1a202c}
    .doc-suggest span{font-size:12px;color:#3b82f6;font-weight:500;
                      display:block;margin-top:2px}
    .urgency-badge{font-size:11px;font-weight:600;padding:3px 10px;
                   border-radius:99px;white-space:nowrap}
    .ub-high  {background:#fee2e2;color:#991b1b}
    .ub-medium{background:#fef3c7;color:#92400e}
    .ub-low   {background:#d1fae5;color:#065f46}

    .advice-list{margin:0;padding:0 0 0 18px;
                 font-size:13px;color:#4a5568;line-height:1.9}
    .actions{margin-top:14px;display:flex;gap:8px;flex-wrap:wrap}
    .btn-sm{padding:7px 16px;border:none;border-radius:6px;
            font-size:12px;font-weight:500;cursor:pointer;
            text-decoration:none;display:inline-block}
    .btn-book{background:#3b82f6;color:#fff}
    .btn-rec {background:#eff6ff;color:#1e40af}
    .btn-book:hover{background:#2563eb}
    .btn-rec:hover {background:#dbeafe}

    .no-match{text-align:center;padding:24px;color:#718096}
    .no-match .icon{font-size:36px;margin-bottom:10px}

    .quick-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
    .chip{font-size:12px;padding:5px 12px;border-radius:99px;
          background:var(--color-background-secondary,#f8fafc);
          border:1px solid #e2e8f0;cursor:pointer;color:#4a5568}
    .chip:hover{background:#eff6ff;border-color:#93c5fd;color:#1e40af}
  </style>
</head>
<body>

<div class="navbar">
  <h1>🩺 Symptom Checker</h1>
  <a href="dashboard.php">← Dashboard</a>
</div>

<div class="content">

  <div class="card">
    <h2>Describe your symptoms</h2>
    <form method="POST">
      <textarea
        name="symptoms"
        placeholder="e.g. I have fever and headache since 2 days..."
        ><?= htmlspecialchars($input_raw) ?></textarea>

      <p class="hint">
        Type naturally. Quick examples:
      </p>

      <!-- Quick fill chips -->
      <div class="quick-chips">
        <span class="chip" onclick="fill('fever and headache')">fever + headache</span>
        <span class="chip" onclick="fill('cough and sore throat')">cough + throat</span>
        <span class="chip" onclick="fill('skin rash and itching')">skin rash</span>
        <span class="chip" onclick="fill('chest pain and breathless')">chest pain</span>
        <span class="chip" onclick="fill('back pain and joint pain')">back + joint</span>
        <span class="chip" onclick="fill('stomach pain and nausea')">stomach + nausea</span>
      </div>

      <button type="submit">🔍 Check Symptoms</button>
    </form>
  </div>

  <?php if (!empty($matches)): ?>

    <h3 style="font-size:14px;color:#4a5568;margin-bottom:12px">
      Results for: <i>"<?= htmlspecialchars($input_raw) ?>"</i>
    </h3>

    <?php foreach($matches as $m): ?>
    <div class="result-card urgency-<?= $m['urgency'] ?>">

      <div class="result-top">
        <div class="doc-suggest">
          👨‍⚕️ See a <?= $m['doctor'] ?>
          <span>Based on your symptoms</span>
        </div>
        <?php
          $urg_labels = [
            'high'   => '🔴 Urgent',
            'medium' => '🟡 Moderate',
            'low'    => '🟢 Not urgent',
          ];
        ?>
        <span class="urgency-badge ub-<?= $m['urgency'] ?>">
          <?= $urg_labels[$m['urgency']] ?>
        </span>
      </div>

      <ul class="advice-list">
        <?php foreach($m['advice'] as $tip): ?>
          <li><?= $tip ?></li>
        <?php endforeach; ?>
      </ul>

      <div class="actions">
        <a class="btn-sm btn-book"
           href="book_appointment.php">
          📅 Book Appointment
        </a>
        <a class="btn-sm btn-rec"
           href="recommend.php">
          📍 Find Nearby Hospital
        </a>
      </div>

    </div>
    <?php endforeach; ?>

    <p style="font-size:12px;color:#a0aec0;text-align:center;margin-top:6px">
      ⚠️ This is a basic guide only — always consult a real doctor.
    </p>

  <?php elseif($no_match): ?>
    <div class="card no-match">
      <div class="icon">🤔</div>
      <p>No matching symptoms found.</p>
      <p><small>Try words like: fever, headache, cough, rash, chest pain</small></p>
      <a class="btn-sm btn-book" href="book_appointment.php">
        Book a General Physician
      </a>
    </div>
  <?php endif; ?>

</div>

<script>
function fill(text) {
  document.querySelector('textarea[name="symptoms"]').value = text;
}
</script>
</body>
</html>