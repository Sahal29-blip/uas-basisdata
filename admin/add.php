<?php
// admin/add.php
session_start();
include '../includes/db_config.php';

check_admin_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $timestamp = $_POST['timestamp'];
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $motion_detected = $_POST['motion_detected'];
    $stmt = $conn->prepare("INSERT INTO sensor_data (timestamp, temperature, humidity, motion_detected) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sddi", $timestamp, $temperature, $humidity, $motion_detected);
    $stmt->close();
}

$conn->close();
header("Location: index.php");
exit();
?>
