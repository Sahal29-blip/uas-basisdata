<?php

header('Content-Type: application/json');
include '../includes/db_config.php';

$response = ['success' => false, 'data' => null];

$sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $latest = $result->fetch_assoc();

    // Tambahkan nilai sinyal random (misal 50-100)
    $latest['signal_strength'] = rand(50, 100);

    // Hitung total records, motion, dan suhu rata-rata hari ini
    $sql_stats = "SELECT COUNT(*) as total_records, SUM(motion_detected) as motion_count, AVG(temperature) as avg_temp FROM sensor_data WHERE DATE(timestamp) = CURDATE()";
    $result_stats = $conn->query($sql_stats);
    $total_records = 0;
    $motion_count = 0;
    $avg_temp = 0;
    if ($result_stats && $row = $result_stats->fetch_assoc()) {
        $total_records = $row['total_records'];
        $motion_count = is_null($row['motion_count']) ? 0 : (int)$row['motion_count'];
        $avg_temp = is_null($row['avg_temp']) ? 0 : round($row['avg_temp'], 1);
    }

    // Ambil waktu pertama data hari ini
    $sql_first = "SELECT MIN(timestamp) as first_time FROM sensor_data WHERE DATE(timestamp) = CURDATE()";
    $result_first = $conn->query($sql_first);
    $first_time = null;
    if ($result_first && $row = $result_first->fetch_assoc()) {
        $first_time = $row['first_time'];
    }

    $latest['first_time'] = $first_time;
    $latest['total_records'] = $total_records;
    $latest['motion_count'] = $motion_count;
    $latest['avg_temp'] = $avg_temp;

    $response['success'] = true;
    $response['data'] = $latest;
}

$conn->close();
echo json_encode($response);
?>