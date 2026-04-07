<?php require_once "includes/header.php"; ?>
<?php require_once "includes/sidebar.php"; ?>

<?php
require_once __DIR__ . '/includes/db.php';

/* Collection Stats from payments */
$res = $conn->query("
    SELECT 
        SUM(amount) as total,
        SUM(CASE WHEN MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) THEN amount ELSE 0 END) as month_total
    FROM payments
    WHERE payment_date >= '2026-03-01'
");
$row = $res->fetch_assoc();
$totalCollection = $row['total'] ?? 0;
$monthCollection = $row['month_total'] ?? 0;

/* Collection Stats from misc_receipts */
$resMisc = $conn->query("
    SELECT 
        SUM(amount) as total,
        SUM(CASE WHEN MONTH(received_date)=MONTH(CURDATE()) AND YEAR(received_date)=YEAR(CURDATE()) THEN amount ELSE 0 END) as month_total
    FROM misc_receipts
    WHERE received_date >= '2026-03-01'
");
$rowMisc = $resMisc->fetch_assoc();
$totalCollection += $rowMisc['total'] ?? 0;
$monthCollection += $rowMisc['month_total'] ?? 0;

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

/* Active Tasks for Current User */
$admin_id = $_SESSION['admin_id'];
$tasks_stmt = $conn->prepare("SELECT t.id, t.heading, t.created_at, a.username as assigner_name FROM tasks t LEFT JOIN admins a ON t.assigned_by = a.id WHERE t.assigned_to = ? AND t.status = 'pending' ORDER BY t.created_at DESC");
$tasks_stmt->bind_param("i", $admin_id);
$tasks_stmt->execute();
$active_tasks = $tasks_stmt->get_result();
?>

<style>
/* ── Dashboard-specific enhancements ── */
.dash-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 36px;
    flex-wrap: wrap;
    gap: 12px;
}
.dash-hero-text h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -0.5px;
    margin-bottom: 4px;
}
.dash-hero-text p {
    font-size: 15px;
    color: var(--text-muted);
}
.dash-date {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted);
    box-shadow: var(--shadow-sm);
}

/* ── Section label ── */
.dash-section-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

/* ── Stat grid ── */
.dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

/* ── Enhanced stat card ── */
.dash-stat-card {
    background: var(--bg-card);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
    padding: 24px 24px 20px;
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.dash-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}
.dash-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
}
.dash-stat-card.accent-primary::before { background: linear-gradient(90deg, #7A1E3A, #9C2F4E); }
.dash-stat-card.accent-blue::before   { background: linear-gradient(90deg, #2563EB, #3B82F6); }
.dash-stat-card.accent-green::before  { background: linear-gradient(90deg, #16A34A, #22C55E); }
.dash-stat-card.accent-amber::before  { background: linear-gradient(90deg, #D97706, #F59E0B); }
.dash-stat-card.accent-violet::before { background: linear-gradient(90deg, #7C3AED, #A78BFA); }
.dash-stat-card.accent-teal::before   { background: linear-gradient(90deg, #0D9488, #2DD4BF); }

.dash-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.icon-primary { background: rgba(122,30,58,0.1); }
.icon-blue    { background: rgba(37,99,235,0.1); }
.icon-green   { background: rgba(22,163,74,0.1); }
.icon-amber   { background: rgba(217,119,6,0.1); }
.icon-violet  { background: rgba(124,58,237,0.1); }
.icon-teal    { background: rgba(13,148,136,0.1); }

.dash-stat-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-muted);
}
.dash-stat-value {
    font-size: 30px;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1;
}

/* ── Module cards ── */
.dash-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.dash-module-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: var(--shadow-md);
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: var(--transition);
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
}
.dash-module-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(122,30,58,0.2);
}
.dash-module-card:hover .module-arrow {
    transform: translateX(4px);
}
.module-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(122,30,58,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 6px;
}
.dash-module-card h4 {
    font-size: 17px;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
}
.dash-module-card p {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.5;
    flex-grow: 1;
    margin: 0;
}
.module-link-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-color);
}
.module-arrow {
    display: inline-block;
    transition: transform 0.2s ease;
}
.coming-soon-badge {
    display: inline-block;
    background: rgba(122,30,58,0.08);
    color: var(--primary-color);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 3px 8px;
    border-radius: 20px;
    margin-top: 10px;
    align-self: flex-start;
}
</style>

<!-- Hero -->
<div class="dash-hero">
    <div class="dash-hero-text">
        <h1>Welcome back, <?php echo ucfirst($_SESSION['username'] ?? 'Admin'); ?> 👋</h1>
        <p>Here's what's happening at your institute today.</p>
    </div>
    <div class="dash-date">
        📅 <?php echo date('l, d M Y'); ?>
    </div>
</div>

<?php if ($active_tasks->num_rows > 0): ?>
<!-- Active Tasks -->
<div class="dash-section-label" style="color:#c0392b;">Action Required: Your Pending Tasks</div>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-bottom:36px;">
    <?php while($t = $active_tasks->fetch_assoc()): ?>
    <div style="background:#fff; border-left:4px solid #e74c3c; border-radius:8px; padding:15px; box-shadow:0 2px 4px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h4 style="margin:0 0 5px 0; color:#2c3e50; font-size:15px; font-weight:bold;"><?php echo htmlspecialchars($t['heading']); ?></h4>
            <div style="font-size:12px; color:#7f8c8d;">
                Assigned by <strong><?php echo htmlspecialchars($t['assigner_name']); ?></strong><br>
                <?php echo date("M d, Y", strtotime($t['created_at'])); ?>
            </div>
        </div>
        <a href="tasks/view.php?id=<?php echo $admin_id; ?>" style="background:#e74c3c; color:white; padding:6px 12px; border-radius:4px; font-size:11px; font-weight:bold; text-decoration:none; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(231,76,60,0.3);">View Board</a>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<!-- System Info -->
<div class="dash-section-label">System</div>
<div class="dash-stats-grid" style="margin-bottom:36px;">

    <div class="dash-stat-card accent-primary">
        <div class="dash-stat-icon icon-primary">👤</div>
        <div class="dash-stat-label">Role</div>
        <div class="dash-stat-value" style="font-size:22px;"><?php echo ucfirst($_SESSION['role']); ?></div>
    </div>

    <div class="dash-stat-card accent-blue">
        <div class="dash-stat-icon icon-blue">🎓</div>
        <div class="dash-stat-label">Total Students</div>
        <div class="dash-stat-value"><?php echo $total_students; ?></div>
    </div>

    <div class="dash-stat-card accent-green">
        <div class="dash-stat-icon icon-green">📚</div>
        <div class="dash-stat-label">Active Batches</div>
        <div class="dash-stat-value"><?php echo $batchStats['active_batches']; ?><span style="font-size:16px;font-weight:500;color:var(--text-muted);"> / <?php echo $batchStats['total_batches']; ?></span></div>
    </div>

    <div class="dash-stat-card accent-amber">
        <div class="dash-stat-icon icon-amber">🏫</div>
        <div class="dash-stat-label">Occupancy</div>
        <div class="dash-stat-value"><?php echo $occupancy; ?><span style="font-size:16px;font-weight:500;color:var(--text-muted);">%</span></div>
    </div>

</div>

<!-- Finance Overview -->
<div class="dash-section-label">Finance</div>
<div class="dash-stats-grid">

    <a href="analytics/collections.php?view=all" class="dash-stat-card accent-teal" style="text-decoration:none;">
        <div class="dash-stat-icon icon-teal">💰</div>
        <div class="dash-stat-label">Total Collection</div>
        <div class="dash-stat-value" style="font-size:24px;">₹<?php echo number_format($totalCollection, 2); ?></div>
    </a>

    <a href="analytics/collections.php?view=monthly" class="dash-stat-card accent-violet" style="text-decoration:none;">
        <div class="dash-stat-icon icon-violet">📆</div>
        <div class="dash-stat-label">This Month</div>
        <div class="dash-stat-value" style="font-size:24px;">₹<?php echo number_format($monthCollection, 2); ?></div>
    </a>

    <div class="dash-stat-card accent-green">
        <div class="dash-stat-icon icon-green">🏷️</div>
        <div class="dash-stat-label">Capacity Filled</div>
        <div class="dash-stat-value"><?php echo $total_students; ?><span style="font-size:16px;font-weight:500;color:var(--text-muted);"> / <?php echo $total_capacity; ?></span></div>
    </div>

</div>

<!-- Modules -->
<div class="dash-section-label" style="margin-top:8px;">Modules</div>
<div class="dash-modules-grid">

    <a href="students/list.php" class="dash-module-card">
        <div class="module-icon-wrap">🎓</div>
        <h4>Student Management</h4>
        <p>Admissions, records, and fee tracking system.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

    <a href="fees/list.php" class="dash-module-card">
        <div class="module-icon-wrap">💳</div>
        <h4>Fee Management</h4>
        <p>Installments, payments, overdue tracking &amp; reports.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

    <a href="batches/list.php" class="dash-module-card">
        <div class="module-icon-wrap">📚</div>
        <h4>Batch Management</h4>
        <p>Manage batches, timings, capacity and student allocation.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

    <a href="faculty/list.php" class="dash-module-card">
        <div class="module-icon-wrap">🧑‍💼</div>
        <h4>Faculty Management</h4>
        <p>Manage institute faculty accounts and permissions.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>
    
    <a href="students/pending.php" class="dash-module-card">
        <div class="module-icon-wrap">🔔</div>
        <h4>Admission Requests</h4>
        <p>Review and approve pending student admissions.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

    <a href="tasks/index.php" class="dash-module-card">
        <div class="module-icon-wrap">✅</div>
        <h4>Tasks</h4>
        <p>Assign and track faculty responsibilities and workflow.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

    <a href="referrals/index.php" class="dash-module-card">
        <div class="module-icon-wrap">🤝</div>
        <h4>Referred Students</h4>
        <p>Track student referrals and assign reward points.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

    <a href="results/pending.php" class="dash-module-card">
        <div class="module-icon-wrap">🏆</div>
        <h4>Results &amp; Certificates</h4>
        <p>Generate marksheets and certificates for completed students.</p>
        <div class="module-link-row">Open Module <span class="module-arrow">→</span></div>
    </a>

</div>

<?php require_once "includes/footer.php"; ?>