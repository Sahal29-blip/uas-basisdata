<?php
// untuk update data sensor pada halaman admin
include '../includes/db_config.php';
header('Content-Type: application/json');

$data = [];
$sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 10"; // Untuk menampilkan 10 data terbaru 
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
$conn->close();

echo json_encode(['success' => true, 'data' => $data]);
?>