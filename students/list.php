<?php
require_once "../includes/auth.php";

require_once dirname(__DIR__) . '/includes/db.php';

/* Fetch students */
$result = $conn->query("
    SELECT id, admission_no, registration_no, full_name, course, batch, phone,
           total_fees, fees_paid, final_total, status,
           admission_date, course_duration
    FROM students
    WHERE status != 'Completed'
    ORDER BY id DESC
");

/* SUCCESS MESSAGE */
$success_message = "";
$error_message = "";

if (isset($_GET['success'])) {
    if ($_GET['success'] === "added") {
        $success_message = "Student added successfully.";
    } elseif ($_GET['success'] === "completed") {
        $success_message = "Student marked as completed and moved to archives.";
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'invalid_password') {
    $error_message = "Invalid password. Action denied.";
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<?php if (!empty($success_message)): ?>
    <div class="success-popup">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="error-popup" style="background: #F8D7DA; color: #721C24; padding: 15px; text-align: center; border-radius: 8px; margin-bottom: 20px;">
        <?php echo $error_message; ?>
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
            $final_total = $row['final_total'] > 0 ? $row['final_total'] : $row['total_fees'];
            $due = $final_total - $row['fees_paid'];
            
            // Check for Subscription Expiry
            $duration_months = (int) preg_replace('/\D/', '', $row['course_duration']);
            if($duration_months == 0) $duration_months = 12; // Fallback if format is weird
            
            $expiry_time = strtotime("+" . $duration_months . " months", strtotime($row['admission_date']));
            $is_expired = (time() > $expiry_time);
        ?>

            <tr>

                <td><strong><?php echo $row['admission_no']; ?></strong></td>

                <td><?php echo htmlspecialchars($row['registration_no'] ?? ''); ?></td>

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
                        <?php if ($is_expired): ?>
                            <br><span class="role-badge" style="background: #FFF3CD; color: #856404; border: 1px solid #FFEEBA; margin-top: 5px; font-size: 11px;">⚠️ Expired</span>
                        <?php endif; ?>
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

                    <a href="javascript:void(0);" 
                       class="action-btn btn-edit"
                       onclick="openVerifyModal(<?php echo $row['id']; ?>);">
                       Done
                    </a>
                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>
    </table>

</div>

<!-- Password Verification Modal -->
<div id="verifyModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#fff; padding:30px; border-radius:12px; width:400px; text-align:center; position:relative;">
        <h3 style="margin-top:0; color:#333;">Security Verification</h3>
        <p style="color:#666; font-size:14px; margin-bottom:20px;">Please enter your admin password to mark this student as completed.</p>
        
        <form action="mark_completed.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="student_id" id="verifyStudentId" value="">
            
            <input type="password" name="verify_password" placeholder="Admin Password" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; margin-bottom:20px;">
            
            <div style="display:flex; justify-content:space-between; gap:10px;">
                <button type="button" onclick="closeVerifyModal()" style="padding:10px 20px; background:#ddd; border:none; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px; background:#C0392B; color:#fff; border:none; border-radius:6px; cursor:pointer;">Confirm Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVerifyModal(studentId) {
    document.getElementById('verifyStudentId').value = studentId;
    document.getElementById('verifyModal').style.display = 'flex';
}
function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}

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