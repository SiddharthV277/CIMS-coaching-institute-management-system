<?php
require_once "../includes/auth.php";

require_once dirname(__DIR__) . '/includes/db.php';

/* Fetch students */
$result = $conn->query("
    SELECT id, registration_no, full_name, course, batch, phone,
           total_fees, fees_paid, status
    FROM students
    WHERE status = 'Completed'
    ORDER BY CAST(REGEXP_REPLACE(registration_no, '[^0-9]', '') AS UNSIGNED) DESC
");

/* SUCCESS MESSAGE */
$success_message = "";
if (isset($_GET['success'])) {
    if ($_GET['success'] === "added") {
        $success_message = "Student added successfully.";
    } elseif ($_GET['success'] === "archived") {
        $success_message = "Student archived successfully.";
    } elseif ($_GET['success'] === "upgraded") {
        $success_message = "Student upgraded and moved back to active list.";
    }
}

if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
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
        <h2>Passed Students</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reg. No</th>
                <th>Name</th>
                <th>Course</th>
                <th>Batch</th>
                <th>Phone</th>
                <th>Total Fees</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php while ($row = $result->fetch_assoc()): 

            $due = $row['total_fees'] - $row['fees_paid'];
        ?>

            <tr>

                <td><strong><?php echo htmlspecialchars($row['registration_no'] ?? ''); ?></strong></td>

                <td><?php echo htmlspecialchars($row['full_name']); ?></td>

                <td><?php echo htmlspecialchars($row['course']); ?></td>

                <td><?php echo htmlspecialchars($row['batch']); ?></td>

                <td><?php echo htmlspecialchars($row['phone']); ?></td>

                <td>₹<?php echo number_format($row['total_fees'], 2); ?></td>

                <td>₹<?php echo number_format($row['fees_paid'], 2); ?></td>

                <td>
                    <?php if ($due > 0): ?>
                        <span class="role-badge status-inactive">
                            ₹<?php echo number_format($due, 2); ?>
                        </span>
                    <?php else: ?>
                        <span class="role-badge status-active">
                            Paid
                        </span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($row['status'] === 'Active'): ?>
                        <span class="role-badge status-active">Active</span>
                    <?php else: ?>
                        <span class="role-badge status-inactive">
                            <?php echo $row['status']; ?>
                        </span>
                    <?php endif; ?>
                </td>

                <td>
                    <a href="archive.php?id=<?php echo $row['id']; ?>" 
                       class="action-btn btn-edit">
                       Archive
                    </a>

                    <a href="upgrade.php?id=<?php echo $row['id']; ?>" 
                       class="action-btn btn-edit">
                       Upgrade
                    </a>
                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>
    </table>

</div>

<script>
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
