<?php
// admin/edit.php
session_start();
include '../includes/db_config.php';

check_admin_login(); // Mengecek status login admin

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $timestamp = $_POST['timestamp'];
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $motion_detected = $_POST['motion_detected'];
    $stmt = $conn->prepare("UPDATE sensor_data SET timestamp = ?, temperature = ?, humidity = ?, motion_detected = ? WHERE id = ?");
    $stmt->bind_param("sddii", $timestamp, $temperature, $humidity, $motion_detected, $id);
    $stmt->close();
}

$conn->close();
header("Location: index.php");
exit();
?>
