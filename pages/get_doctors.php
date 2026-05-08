<?php
require_once "../config/db.php";

$hospital_id = intval($_GET['hospital_id'] ?? 0);

if ($hospital_id > 0) {
    $result = mysqli_query($conn, "
        SELECT id, name, specialization
        FROM doctors
        WHERE hospital_id = '$hospital_id'
        ORDER BY name ASC
    ");

    $doctors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }

    // Return as JSON so JavaScript can read it
    header('Content-Type: application/json');
    echo json_encode($doctors);

} else {
    echo json_encode([]);
}
?>