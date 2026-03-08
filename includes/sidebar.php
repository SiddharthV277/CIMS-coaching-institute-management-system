<?php
/* Ensure session exists */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* DB Connection for badge */
$conn = new mysqli("localhost", "root", "", "cims");

/* Get pending + shortlisted count */
$pending_count = 0;

if ($conn->connect_error === false) {

    $result = $conn->query("
        SELECT COUNT(*) as total
        FROM admission_requests
        WHERE status IN ('Pending','Shortlisted')
    ");

    if ($result) {
        $pending_count = $result->fetch_assoc()['total'];
    }
}
?>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #F6F4F2;
}

.sidebar {
    width: 240px;
    background: #1E1E2F;
    color: #fff;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 20px;
}

.brand {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 30px;
    letter-spacing: 1px;
}

.nav-link {
    display: block;
    color: #D1D1E0;
    text-decoration: none;
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: 0.2s ease;
    font-size: 14px;
}

.nav-link:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.badge {
    background: #C0392B;
    color: #fff;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    margin-left: 8px;
}

.content {
    margin-left: 240px;
    min-height: 100vh;
    padding: 30px 40px;
    box-sizing: border-box;
}
</style>

<div class="sidebar">

    <div class="brand">
        VIGYAAN
    </div>

    <a href="/cims/dashboard.php" class="nav-link">
        Dashboard
    </a>

    <a href="/cims/staff/list.php" class="nav-link">
        Staff Management
    </a>

    <a href="/cims/students/list.php" class="nav-link">
        Student Records
    </a>
    <a href="/cims/batches/list.php" class="nav-link">
        Batch Management
    </a>

    <a href="/cims/students/pending.php" class="nav-link">
        Admission Requests
        <?php if ($pending_count > 0): ?>
            <span class="badge"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="/cims/fees/list.php" class="nav-link">
        Fees Management
    </a>

</div>

<div class="content">