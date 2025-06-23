<?php
// admin/delete.php
session_start();
include '../includes/db_config.php';

check_admin_login(); // Mengecek status admin login

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM sensor_data WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->close();
}

$conn->close();
header("Location: index.php");
exit();
?>
