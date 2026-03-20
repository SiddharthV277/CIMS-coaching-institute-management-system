<?php
require_once "../includes/auth.php";

require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT s.*, b.batch_name, b.time_slot
    FROM students s
    LEFT JOIN batches b ON s.batch_id = b.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header("Location: list.php");
    exit();
}

/* Fetch Staff Remark from original Admission Request */
$staff_remark = null;
$stmt_rem = $conn->prepare("SELECT remark FROM admission_requests WHERE (phone = ? OR email = ?) AND status='Approved' ORDER BY id DESC LIMIT 1");
$stmt_rem->bind_param("ss", $student['phone'], $student['email']);
$stmt_rem->execute();
$stmt_rem->bind_result($staff_remark);
$stmt_rem->fetch();
$stmt_rem->close();

/* Fetch Payment History */
$payments = [];
$pstmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY id DESC");
$pstmt->bind_param("i", $id);
$pstmt->execute();
$presult = $pstmt->get_result();
while($row = $presult->fetch_assoc()){
    $payments[] = $row;
}

$final_total = $student['final_total'] > 0 ? $student['final_total'] : $student['total_fees'];
$total_paid = $student['fees_paid'];
$due = $final_total - $total_paid;

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'upgraded'): ?>
    <div class="success-popup" style="margin-bottom: 20px;">
        Student upgraded successfully and moved back to the active tracking list.
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
<?php endif; ?>

<div class="profile-header">

<?php if (!empty($student['photo'])): ?>
    <img src="/cims/uploads/students/<?php echo $student['photo']; ?>" class="profile-photo">
<?php else: ?>
    <div class="profile-photo"></div>
<?php endif; ?>

<div class="profile-info">
    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
    <div class="profile-meta">
        Admission No: <strong><?php echo $student['admission_no']; ?></strong><br>
        <?php if (!empty($student['registration_no'])): ?>
        Registration No: <strong><?php echo htmlspecialchars($student['registration_no']); ?></strong><br>
        <?php endif; ?>
        <?php if (!empty($student['receipt_number'])): ?>
        Receipt No: <strong><?php echo htmlspecialchars($student['receipt_number']); ?></strong><br>
        <?php endif; ?>
        Course: <?php echo $student['course']; ?><br>
        Batch:
<?php 
echo !empty($student['batch_name']) 
? "Batch ".$student['batch_name']." (".$student['time_slot'].")"
: "Not Assigned";
?>
    </div>
</div>

</div>

<!-- STAFF REMARKS -->
<div class="section-card">
    <h3>Admission Staff Remarks</h3>
    <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #7A1E3A; border-radius: 4px; font-style: italic; color: #555;">
        <?php echo !empty($staff_remark) ? nl2br(htmlspecialchars($staff_remark)) : "No admission remarks recorded for this student."; ?>
    </div>
</div>

<!-- BASIC INFORMATION -->
<div class="section-card">
<h3>Basic Information</h3>
<div class="info-grid">
    <div class="info-item">
        <strong>Date of Birth (DD-MM-YYYY)</strong>
        <?php echo date('d-m-Y', strtotime($student['dob'])); ?>
    </div>
    <div class="info-item">
        <strong>Gender</strong>
        <?php echo $student['gender']; ?>
    </div>
    <div class="info-item">
        <strong>Phone</strong>
        <?php echo $student['phone']; ?>
    </div>
    <div class="info-item">
        <strong>Email</strong>
        <?php echo $student['email']; ?>
    </div>
</div>
</div>

<!-- GUARDIAN -->
<div class="section-card">
<h3>Guardian & Address</h3>
<div class="info-grid">
    <div class="info-item">
        <strong>Father Name</strong>
        <?php echo $student['father_name']; ?>
    </div>
    <div class="info-item">
        <strong>Mother Name</strong>
        <?php echo $student['mother_name']; ?>
    </div>
    <div class="info-item">
        <strong>Guardian Phone</strong>
        <?php echo $student['guardian_phone']; ?>
    </div>
    <div class="info-item">
        <strong>Address</strong>
        <?php echo nl2br($student['address']); ?>
    </div>
    <div class="info-item">
        <strong>City</strong>
        <?php echo $student['city']; ?>
    </div>
    <div class="info-item">
        <strong>State</strong>
        <?php echo $student['state']; ?>
    </div>
    <div class="info-item">
        <strong>PIN Code</strong>
        <?php echo $student['pincode']; ?>
    </div>
</div>
</div>

<!-- ACADEMIC -->
<div class="section-card">
<h3>Academic Information</h3>
<div class="info-grid">
    <div class="info-item">
        <strong>Admission Date (DD-MM-YYYY)</strong>
        <?php echo date('d-m-Y', strtotime($student['admission_date'])); ?>
    </div>
    <div class="info-item">
        <strong>Course Duration</strong>
        <?php echo $student['course_duration']; ?>
    </div>
    <div class="info-item">
        <strong>Medium</strong>
        <?php echo $student['medium']; ?>
    </div>
    <div class="info-item">
        <strong>Institution</strong>
        <?php echo $student['institution_name']; ?>
    </div>
    <div class="info-item">
        <strong>Degree</strong>
        <?php echo $student['degree']; ?>
    </div>
    <div class="info-item">
        <strong>Percentage</strong>
        <?php echo $student['percentage']; ?>
    </div>
    <div class="info-item">
        <strong>Main Subjects</strong>
        <?php echo $student['main_subjects']; ?>
    </div>
    <div class="info-item">
        <strong>Year of Passing</strong>
        <?php echo $student['passing_year']; ?>
    </div>
</div>
</div>

<div class="section-card">
<h3>Referral Information</h3>

<div class="info-grid">

<div class="info-item">
<strong>Heard About Us</strong>
<?php echo $student['heard_about'] ?: "Not Mentioned"; ?>
</div>

<?php if(strpos($student['heard_about'],'Student') !== false): ?>
<div class="info-item">
<strong>Referred Student Name</strong>
<?php echo $student['referred_student_name']; ?>
</div>

<div class="info-item">
<strong>Referred Student Phone</strong>
<?php echo $student['referred_student_phone']; ?>
</div>
<?php endif; ?>

<?php if(strpos($student['heard_about'],'Others') !== false): ?>
<div class="info-item">
<strong>Other Source</strong>
<?php echo $student['heard_other_text']; ?>
</div>
<?php endif; ?>

</div>
</div>

<!-- FEES -->
<div class="section-card">
<h3>Fee Information</h3>

<div class="fee-highlight">
<div class="info-grid">
    <div class="info-item">
    <strong>Discount</strong>
    ₹<?php echo number_format($student['discount_amount'],2); ?>
</div>
    <div class="info-item">
        <strong>Total Fees (incl. Reg & Exam)</strong>
        ₹<?php echo number_format($student['total_fees'],2); ?>
    </div>
    <div class="info-item">
        <strong>Fees Paid</strong>
        ₹<?php echo number_format($total_paid,2); ?>
    </div>
    <div class="info-item">
        <strong>Due Amount</strong>
        <?php if ($due > 0): ?>
            <span style="color:#C0392B;font-weight:600;">
                ₹<?php echo number_format($due,2); ?>
            </span>
        <?php else: ?>
            <span style="color:#27AE60;font-weight:600;">Paid</span>
        <?php endif; ?>
    </div>
</div>
</div>

</div>

<!-- PAYMENT HISTORY -->
<?php if(count($payments) > 0): ?>
<div class="section-card">
<h3>Payment History</h3>

<div class="table-responsive">
<table class="payment-table">
<tr>
    <th>Amount</th>
    <th>Structure</th>
    <th>Mode</th>
    <th>Date (DD-MM-YYYY)</th>
    <th>Receipt</th>
</tr>

<?php foreach($payments as $pay): ?>
<tr>
    <td>₹<?php echo number_format($pay['amount'],2); ?></td>
    <td><?php echo $pay['payment_structure']; ?></td>
    <td><?php echo $pay['payment_mode']; ?></td>
    <td><?php echo date('d-m-Y', strtotime($pay['payment_date'])); ?></td>
    <td>
        <?php if(!empty($pay['receipt_image'])): ?>
        <a href="/cims/uploads/receipts/<?php echo $pay['receipt_image']; ?>" target="_blank">
            View
        </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>

</table>
</div>
</div>
<?php endif; ?>

<div class="action-wrapper">
    <a href="edit.php?id=<?php echo $student['id']; ?>" class="action-btn btn-edit">
        Edit Student
    </a>
    <a href="print.php?id=<?php echo $student['id']; ?>" target="_blank" class="action-btn" style="background:#555; color:#fff;">
        Print Details
    </a>
    <a href="list.php" class="action-btn btn-toggle">
        Back to List
    </a>
</div>

<?php require_once "../includes/footer.php"; ?>