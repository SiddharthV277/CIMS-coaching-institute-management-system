<?php
require_once "../includes/superadmin_only.php";

require_once dirname(__DIR__) . '/includes/db.php';

$result = $conn->query("SELECT id, username, role, status, created_at FROM admins ORDER BY id DESC");

/* SUCCESS MESSAGE HANDLING */
$success_message = "";

if (isset($_GET['success'])) {
    if ($_GET['success'] === "added") {
        $success_message = "Faculty added successfully.";
    }
    elseif ($_GET['success'] === "role_updated") {
    $success_message = "Role updated successfully.";
}   
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<?php if (!empty($success_message)): ?>
    <div class="success-popup">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<div class="table-container">

    <div class="table-header">
        <h2>Faculty Management</h2>
        <a href="add.php" class="add-btn">+ Add Faculty</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php while ($row = $result->fetch_assoc()): ?>

            <tr>
                <td><?php echo $row['id']; ?></td>

                <td><?php echo htmlspecialchars($row['username']); ?></td>

                <td>
                    <?php if ($row['role'] === 'superadmin'): ?>
                        <span class="role-badge role-superadmin">Superadmin</span>
                    <?php else: ?>
                        <span class="role-badge role-faculty">Faculty</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($row['status'] === 'active'): ?>
                        <span class="role-badge status-active">Active</span>
                    <?php else: ?>
                        <span class="role-badge status-inactive">Inactive</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php echo date("d M Y", strtotime($row['created_at'])); ?>
                </td>

                <td>

    <a href="edit.php?id=<?php echo $row['id']; ?>" 
       class="action-btn btn-edit">
       Edit Role
    </a>

    <a href="reset.php?id=<?php echo $row['id']; ?>" 
       class="action-btn btn-edit">
       Reset Password
    </a>

    <a href="toggle.php?id=<?php echo $row['id']; ?>" 
       class="action-btn btn-toggle">
       Toggle Status
    </a>

</td>

            </tr>

        <?php endwhile; ?>

        </tbody>
    </table>

</div>

<script>
/* AUTO HIDE SUCCESS POPUP */
setTimeout(function() {
    const popup = document.querySelector(".success-popup");
    if (popup) {
        popup.style.transition = "opacity 0.5s ease";
        popup.style.opacity = "0";
        setTimeout(() => popup.remove(), 500);
    }
}, 3000);
</script>

<?php require_once "../includes/footer.php"; ?>