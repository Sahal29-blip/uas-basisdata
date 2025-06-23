<?php
// api/get_chart_data.php - API khusus untuk data chart (10 data terbaru)
header('Content-Type: application/json');
include '../includes/db_config.php';

$response = ['success' => false, 'data' => []];

// Mengambil 10 data terbaru untuk chart
$sql = "SELECT DATE_FORMAT(timestamp, '%H:%i') as time_label, 
        temperature, humidity, motion_detected, timestamp
        FROM sensor_data 
        ORDER BY timestamp DESC 
        LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $chart_data = [];
    while ($row = $result->fetch_assoc()) {
        $chart_data[] = $row;
    }
    
    // Reverse array agar urutan waktu dari lama ke baru untuk chart
    $response['success'] = true;
    $response['data'] = array_reverse($chart_data);
    $response['count'] = count($chart_data);
}

$conn->close();
echo json_encode($response);
?>
