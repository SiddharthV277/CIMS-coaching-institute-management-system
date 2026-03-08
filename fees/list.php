<?php
require_once "../includes/auth.php";
$conn = new mysqli("localhost", "root", "", "cims");

/* ================= FETCH STUDENTS WITH INSTALLMENT DATA ================= */

$query = "
SELECT 
    s.id,
    s.full_name,
    s.admission_no,
    s.total_fees,
    s.fees_paid,
    s.final_total,
    s.payment_structure,

    COUNT(fi.id) AS total_installments,
    SUM(CASE WHEN fi.status='paid' THEN 1 ELSE 0 END) AS paid_installments,
    SUM(CASE WHEN fi.status='pending' THEN 1 ELSE 0 END) AS pending_installments,
    SUM(CASE WHEN fi.status='pending' AND fi.due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_installments

FROM students s
LEFT JOIN fee_installments fi ON s.id = fi.student_id
GROUP BY s.id
ORDER BY s.full_name ASC
";

$result = $conn->query($query);

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.table-wrapper {
    background:#fff;
    padding:30px;
    border-radius:16px;
    border:1px solid #E6DCD4;
}

.fee-table {
    width:100%;
    border-collapse:collapse;
}

.fee-table th,
.fee-table td {
    padding:12px;
    border-bottom:1px solid #E6DCD4;
    font-size:14px;
}

.fee-table th {
    background:#F9F3F6;
}

.status-clear { color:#27AE60; font-weight:600; }
.status-overdue { color:#C0392B; font-weight:600; }
.status-active { color:#D35400; font-weight:600; }
.status-plan { color:#7F8C8D; font-weight:600; }

.action-btn {
    padding:6px 12px;
    border-radius:6px;
    background:#7A1E3A;
    color:#fff;
    text-decoration:none;
    font-size:13px;
}
</style>

<h2>Fee Management</h2>

<div class="table-wrapper">

<table class="fee-table">
<tr>
    <th>Admission No</th>
    <th>Name</th>
    <th>Structure</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Remaining</th>
    <th>Installments</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): 

$final_total = $row['final_total'] > 0 ? $row['final_total'] : $row['total_fees'];
$remaining = $final_total - $row['fees_paid'];

$total_installments = (int)$row['total_installments'];
$paid_installments = (int)$row['paid_installments'];
$overdue_installments = (int)$row['overdue_installments'];

$status = "";
$status_class = "";

if ($remaining <= 0) {
    $status = "CLEAR";
    $status_class = "status-clear";
}
elseif ($total_installments == 0) {
    $status = "PLAN NOT CREATED";
    $status_class = "status-plan";
}
elseif ($overdue_installments > 0) {
    $status = "OVERDUE";
    $status_class = "status-overdue";
}
else {
    $status = "ACTIVE";
    $status_class = "status-active";
}

?>

<tr>
    <td><?php echo $row['admission_no']; ?></td>
    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
    <td><?php echo $row['payment_structure']; ?></td>
    <td>₹<?php echo number_format($final_total,2); ?></td>
    <td>₹<?php echo number_format($row['fees_paid'],2); ?></td>
    <td>₹<?php echo number_format(max($remaining,0),2); ?></td>
    <td>
        <?php
        if($total_installments > 0){
            echo $paid_installments . " / " . $total_installments;
        } else {
            echo "-";
        }
        ?>
    </td>
    <td class="<?php echo $status_class; ?>">
        <?php echo $status; ?>
    </td>
    <td>
        <a href="manage.php?student_id=<?php echo $row['id']; ?>" class="action-btn">
            Manage
        </a>
    </td>
</tr>

<?php endwhile; ?>

</table>

</div>

<?php require_once "../includes/footer.php"; ?>