<?php
require_once "../includes/auth.php";

$conn = new mysqli("localhost", "root", "", "cims");

/* Fetch students */
$result = $conn->query("
    SELECT id, admission_no, full_name, course, batch, phone,
           total_fees, fees_paid, status
    FROM students
    ORDER BY id DESC
");

/* SUCCESS MESSAGE */
$success_message = "";

if (isset($_GET['success'])) {
    if ($_GET['success'] === "added") {
        $success_message = "Student added successfully.";
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
        <h2>Student Management</h2>
        <a href="add.php" class="add-btn">+ Add Student</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Admission No</th>
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

                <td><strong><?php echo $row['admission_no']; ?></strong></td>

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
                    <a href="view.php?id=<?php echo $row['id']; ?>" 
                       class="action-btn btn-edit">
                       View
                    </a>

                    <a href="edit.php?id=<?php echo $row['id']; ?>" 
                       class="action-btn btn-edit">
                       Edit
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