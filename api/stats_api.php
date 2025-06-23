<?php
// Kode untuk bagian chart pada halaman admin
include '../includes/db_config.php';
$sql = "SELECT COUNT(*) as total, AVG(temperature) as avg_temp, AVG(humidity) as avg_hum, SUM(motion_detected) as motion_events FROM sensor_data WHERE DATE(timestamp) = CURDATE()";
// Memilih data dari database untuk dijadikan data chart, total data yang tersimpan hari ini, rata-rata suhu, rata-rata kelembaban, dan jumlah gerakan yang terdeteksi hari ini
$res = $conn->query($sql);
$row = $res->fetch_assoc();
echo json_encode([
    'total' => (int)$row['total'],
    'avg_temp' => round((float)$row['avg_temp'], 1),
    'avg_hum' => round((float)$row['avg_hum'], 1),
    'motion_events' => (int)$row['motion_events']
]);


?>