<?php
require_once __DIR__ . "/auth.php";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vigyaan Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/cims/assets/css/style.css">
</head>

<body>

<div class="topbar">

<div class="topbar-left">
<span class="welcome-text">
Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
</span>
</div>

<div class="topbar-right">

<?php if($_SESSION['role']==='superadmin'): ?>
<a href="/cims/analytics/export_page.php" class="analytics-btn">
📊 Export Analytics
</a>
<?php endif; ?>

<a href="/cims/logout.php" class="logout-btn">
Logout
</a>

</div>

</div>

<div class="layout">