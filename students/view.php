<?php
require_once "../includes/auth.php";

$conn = new mysqli("localhost", "root", "", "cims");

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

/* Fetch Payment History */
$payments = [];
$pstmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY id DESC");
$pstmt->bind_param("i", $id);
$pstmt->execute();
$presult = $pstmt->get_result();
while($row = $presult->fetch_assoc()){
    $payments[] = $row;
}

$total_paid = 0;
foreach($payments as $pay){
    $total_paid += $pay['amount'];
}

$due = $student['total_fees'] - $total_paid;

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.profile-header {
    display: flex;
    align-items: center;
    gap: 30px;
    background: #fff;
    padding: 35px;
    border-radius: 18px;
    border: 1px solid #E6DCD4;
    box-shadow: 0 25px 50px rgba(60,40,30,0.06);
    margin-bottom: 35px;
}

.profile-photo {
    width: 170px;
    height: 200px;
    object-fit: cover;
    border-radius: 14px;
    border: 1px solid #E6DCD4;
    background: #f5f5f5;
}

.profile-info h2 {
    margin: 0 0 8px 0;
}

.profile-meta {
    color: #7C6F68;
    font-size: 14px;
}

.section-card {
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    border: 1px solid #E6DCD4;
    box-shadow: 0 20px 45px rgba(60,40,30,0.05);
    margin-bottom: 30px;
}

.section-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    border-left: 4px solid #7A1E3A;
    padding-left: 12px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 40px;
}

.info-item strong {
    display: block;
    font-size: 13px;
    color: #7C6F68;
    margin-bottom: 3px;
}

.fee-highlight {
    padding: 20px;
    border-radius: 12px;
    background: #F9F3F6;
    border: 1px solid #E6DCD4;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
}

.payment-table th,
.payment-table td {
    padding: 10px;
    border-bottom: 1px solid #E6DCD4;
    text-align: left;
    font-size: 14px;
}

.payment-table th {
    background: #F9F3F6;
}

.action-wrapper {
    margin-top: 20px;
    display: flex;
    gap: 15px;
}
</style>

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

<!-- BASIC INFORMATION -->
<div class="section-card">
<h3>Basic Information</h3>
<div class="info-grid">
    <div class="info-item">
        <strong>Date of Birth</strong>
        <?php echo $student['dob']; ?>
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
        <strong>Admission Date</strong>
        <?php echo $student['admission_date']; ?>
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
        <strong>Total Fees</strong>
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

<table class="payment-table">
<tr>
    <th>Amount</th>
    <th>Structure</th>
    <th>Mode</th>
    <th>Date</th>
    <th>Receipt</th>
</tr>

<?php foreach($payments as $pay): ?>
<tr>
    <td>₹<?php echo number_format($pay['amount'],2); ?></td>
    <td><?php echo $pay['payment_structure']; ?></td>
    <td><?php echo $pay['payment_mode']; ?></td>
    <td><?php echo $pay['payment_date']; ?></td>
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
<?php endif; ?>

<div class="action-wrapper">
    <a href="edit.php?id=<?php echo $student['id']; ?>" class="action-btn btn-edit">
        Edit Student
    </a>
    <a href="list.php" class="action-btn btn-toggle">
        Back to List
    </a>
</div>

<?php require_once "../includes/footer.php"; ?>