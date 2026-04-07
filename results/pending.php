<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

/* Fetch students with Result Pending status */
$result = $conn->query("
    SELECT id, registration_no, full_name, course, batch, phone, admission_date, course_duration, status
    FROM students
    WHERE status = 'Result Pending'
    ORDER BY CAST(REGEXP_REPLACE(registration_no, '[^0-9]', '') AS UNSIGNED) DESC
");

$success_message = "";
if (isset($_GET['success']) && $_GET['success'] === 'completed') {
    $success_message = "Student result saved and moved to Passed Students.";
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
        <h2>Result Generation</h2>
        <span style="font-size:13px; color:#64748b; font-weight:400;">Students who have completed their course and are awaiting result entry.</span>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div style="text-align:center; padding:60px 20px; color:#64748b;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px; display:block; margin-left:auto; margin-right:auto;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <p style="font-size:16px; font-weight:600; color:#0f172a; margin-bottom:8px;">No students pending result generation</p>
            <p style="font-size:14px;">Mark students as "Done" from Student Records to begin generating their results.</p>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Reg. No</th>
                <th>Name</th>
                <th>Course</th>
                <th>Batch</th>
                <th>Phone</th>
                <th>Admission Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['registration_no'] ?? ''); ?></strong></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo htmlspecialchars($row['batch']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo $row['admission_date'] ? date('d-m-Y', strtotime($row['admission_date'])) : '-'; ?></td>
                <td>
                    <span class="role-badge" style="background:#FFF3CD; color:#856404; border:1px solid #FFEEBA;">
                        ⏳ Result Pending
                    </span>
                </td>
                <td>
                    <a href="generate.php?id=<?php echo $row['id']; ?>"
                       class="action-btn btn-edit" style="background:#0f172a; color:white;">
                       Generate Result
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>

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
