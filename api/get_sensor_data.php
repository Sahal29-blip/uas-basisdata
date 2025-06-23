<?php
// api/get_sensor_data.php
// API untuk mendapatkan data sensor terbaru (10 data terakhir)
header('Content-Type: application/json');
include '../includes/db_config.php';

$response = ['success' => false, 'data' => []];

$sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $response['success'] = true;
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
}

$conn->close();
echo json_encode($response);
?>
