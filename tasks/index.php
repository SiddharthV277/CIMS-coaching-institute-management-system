<?php
require_once "../includes/auth.php";

// Allow both superadmin and faculty/admin to view this page
if (!isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/includes/db.php';

// Fetch all active faculty and admins
$query = "
    SELECT a.id, a.username, a.role, 
           (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = a.id AND t.status = 'pending') as pending_count
    FROM admins a
    WHERE a.status = 'active'
    ORDER BY FIELD(a.role, 'superadmin', 'admin', 'faculty'), a.username ASC
";
$result = $conn->query($query);

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.user-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    border: 1px solid #eee;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-decoration: none;
    color: inherit;
    margin-bottom: 15px;
}
.user-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    border-color: #3498db;
}
.user-info h3 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 18px;
}
.user-role {
    font-size: 13px;
    padding: 3px 8px;
    border-radius: 4px;
    background: #ecf0f1;
    color: #7f8c8d;
    text-transform: uppercase;
    font-weight: 600;
}
.role-superadmin { background: #fdedec; color: #c0392b; }
.role-admin { background: #e8f8f5; color: #16a085; }
.role-faculty { background: #ebf5fb; color: #2980b9; }

.task-badge {
    background: #e67e22;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
}
.task-badge.empty {
    background: #bdc3c7;
}
</style>

<div class="main-content">
    <div class="header-banner" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h2>Faculty Task Management</h2>
    </div>

    <p style="margin-bottom: 20px; color: #555;">Select a faculty member or administrator below to view their task board.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <a href="view.php?id=<?php echo $row['id']; ?>" class="user-card">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($row['username']); ?></h3>
                        <span class="user-role role-<?php echo strtolower($row['role']); ?>"><?php echo htmlspecialchars($row['role']); ?></span>
                    </div>
                    <?php if ($row['pending_count'] > 0): ?>
                        <div class="task-badge"><?php echo $row['pending_count']; ?> Pending</div>
                    <?php else: ?>
                        <div class="task-badge empty">Done</div>
                    <?php endif; ?>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No active users found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
