<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
</head>
<body style="font-family:Arial; text-align:center; padding:100px; background:#F4EFEA;">

    <h2 style="color:#7A1E3A;">Access Denied</h2>
    <p>You do not have permission to access this page.</p>
    <a href="dashboard.php">Return to Dashboard</a>

</body>
</html>