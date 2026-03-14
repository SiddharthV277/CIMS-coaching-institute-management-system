<?php require_once "includes/header.php"; ?>
<?php require_once "includes/sidebar.php"; ?>

<?php
require_once __DIR__ . '/includes/db.php';

/* Collection Stats */
$res = $conn->query("
    SELECT 
        SUM(amount) as total,
        SUM(CASE WHEN MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) THEN amount ELSE 0 END) as month_total
    FROM payments
");
$row = $res->fetch_assoc();
$totalCollection = $row['total'] ?? 0;
$monthCollection = $row['month_total'] ?? 0;

/* Installment Stats */
$res = $conn->query("
    SELECT 
        SUM(CASE WHEN due_date < CURDATE() AND status != 'paid' THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN status != 'paid' THEN 1 ELSE 0 END) as pending
    FROM fee_installments
");
$row = $res->fetch_assoc();
$overdueCount = $row['overdue'] ?? 0;
$pendingInstallments = $row['pending'] ?? 0;
?>

<?php

/* Batch Stats */
$batchStats = $conn->query("
    SELECT 
        COUNT(*) as total_batches,
        SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active_batches,
        SUM(capacity) as total_capacity
    FROM batches
")->fetch_assoc();

/* Student Count */
$studentStats = $conn->query("
    SELECT COUNT(*) as total_students
    FROM students
")->fetch_assoc();

/* Occupancy % */
$total_capacity = $batchStats['total_capacity'] ?? 0;
$total_students = $studentStats['total_students'] ?? 0;

$occupancy = ($total_capacity > 0)
    ? round(($total_students / $total_capacity) * 100)
    : 0;
?>

<div class="section-title">
    Dashboard
</div>

<div class="section-subtitle">
    Overview of your institute system.
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-title">Total Staff</div>
        <div class="stat-value">2</div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Active Sessions</div>
        <div class="stat-value">1</div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Role</div>
        <div class="stat-value"><?php echo ucfirst($_SESSION['role']); ?></div>
    </div>

</div>

<div class="section-title" style="margin-top:40px;">
    Academic Overview
</div>

<div class="stats-row">

    <div class="stat-card">
        <div class="stat-title">Total Students</div>
        <div class="stat-value"><?php echo $total_students; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Active Batches</div>
        <div class="stat-value">
            <?php echo $batchStats['active_batches']; ?>
            / <?php echo $batchStats['total_batches']; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Overall Capacity</div>
        <div class="stat-value">
            <?php echo $total_students; ?>
            / <?php echo $total_capacity; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Occupancy</div>
        <div class="stat-value">
            <?php echo $occupancy; ?>%
        </div>
    </div>

    

</div>
<div class="stats-row">

    <div class="stat-card">
        <div class="stat-title">Total Collection</div>
        <div class="stat-value">
            ₹<?php echo number_format($totalCollection,2); ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-title">This Month</div>
        <div class="stat-value">
            ₹<?php echo number_format($monthCollection,2); ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Overdue Installments</div>
        <div class="stat-value" style="color:#8B0000;">
            <?php echo $overdueCount; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Pending Installments</div>
        <div class="stat-value">
            <?php echo $pendingInstallments; ?>
        </div>
    </div>

</div>

<div class="card-grid">
    <div class="card">
        <h4>Staff Management</h4>
        <p>Manage institute staff accounts and permissions.</p>
        <a href="staff/list.php">Open Module</a>

        
    </div>
    <div class="card">
    <h4>Fee Management</h4>
    <p>Installments, payments, overdue tracking & reports.</p>
    <a href="fees/list.php">Open Module</a>
</div>

    <div class="card">
        <h4>Student Management</h4>
<p>Admissions, records, and fee tracking system.</p>
<a href="students/list.php">Open Module</a>
    </div>
    <div class="card">
    <h4>Batch Management</h4>
    <p>Manage batches, timings, capacity and student allocation.</p>
    <a href="batches/list.php">Open Module</a>
</div>

    <div class="card">
        <h4>Results & Certificates</h4>
        <p>Generate marksheets and certificates instantly.</p>
        <a href="#">Coming Soon</a>
    </div>
    

    
</div>


<?php require_once "includes/footer.php"; ?>