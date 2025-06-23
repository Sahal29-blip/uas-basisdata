<?php
// admin/logout.php
session_start();
session_destroy();
header("Location: login.php"); // Kembali ke halaman login.php setelah logout
exit();
?>
