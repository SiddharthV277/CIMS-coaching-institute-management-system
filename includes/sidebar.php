<?php
/* Ensure session exists */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* DB Connection for badge */
require_once __DIR__ . '/db.php';

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
    
    <a href="/cims/students/passed.php" class="nav-link">
        Passed Students
    </a>

    <a href="/cims/batches/list.php" class="nav-link">
        Batch Management
    </a>

    <a href="/cims/students/pending.php" class="nav-link">
        Admission Requests
        <?php if ($pending_count > 0): ?>
            <span class="nav-badge"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="/cims/fees/list.php" class="nav-link">
        Fees Management
    </a>

</div>

<div class="content">