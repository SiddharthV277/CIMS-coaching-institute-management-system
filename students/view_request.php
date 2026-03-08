<?php
require_once "../includes/auth.php";
$conn = new mysqli("localhost", "root", "", "cims");

if (!isset($_GET['id'])) {
    header("Location: pending.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT ar.*, a.username AS reviewer_name
    FROM admission_requests ar
    LEFT JOIN admins a ON ar.reviewed_by = a.id
    WHERE ar.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    header("Location: pending.php");
    exit();
}

$error = "";

/* ================= FETCH COURSE FEE ================= */

$course_fee = 0;
$stmt_fee = $conn->prepare("SELECT fees FROM courses WHERE course_name=? LIMIT 1");
$stmt_fee->bind_param("s", $request['course']);
$stmt_fee->execute();
$stmt_fee->bind_result($course_fee);
$stmt_fee->fetch();
$stmt_fee->close();

/* ================= STAFF SHORTLIST ================= */

if (isset($_POST['save_shortlist']) 
    && in_array($_SESSION['role'], ['staff','superadmin'])) {

    if (!in_array($request['status'], ['Approved','Rejected'])) {
        /* ================= DISCOUNT LOGIC ================= */

$discount_type = $_POST['discount_type'] ?? NULL;

$regFee = 550;
$examFee = 670;

$baseAmount = $course_fee - $regFee - $examFee;
if($baseAmount < 0) $baseAmount = 0;

$input_percent = floatval($_POST['discount_percent'] ?? 0);
$input_amount = floatval($_POST['discount_amount'] ?? 0);

/* PRIORITY LOGIC */

/* If flat amount entered */
if($input_amount > 0){

    if($input_amount > $baseAmount){
        $input_amount = $baseAmount;
    }

    $discount_amount = $input_amount;
    $discount_percent = ($discount_amount / $baseAmount) * 100;
}

/* Else if percentage entered */
elseif($input_percent > 0){

    if($input_percent > 100){
        $input_percent = 100;
    }

    $discount_percent = $input_percent;
    $discount_amount = ($baseAmount * $discount_percent) / 100;
}

/* Else no discount */
else{
    $discount_percent = 0;
    $discount_amount = 0;
}

$final_total = $baseAmount - $discount_amount;
if($final_total < 0){
    $final_total = 0;
}
/* ================= PAYMENT VALIDATION ================= */

$amount = floatval($_POST['payment_amount']);

if ($amount <= 0) {
    $error = "Payment amount must be greater than 0.";
}

if ($amount > $final_total) {
    $error = "Amount cannot exceed final payable amount after discount.";
}

        $remark = $_POST['remark'];
        $amount = floatval($_POST['payment_amount']);
        $structure = $_POST['payment_structure'];
        $mode = $_POST['payment_mode'];
        $payment_date = $_POST['payment_date'];

        if ($amount <= 0) $error = "Payment amount must be greater than 0.";
        if ($amount > $course_fee) $error = "Amount cannot exceed total course fee.";

        $receipt_name = $request['receipt_image'];

        if (!empty($_FILES['receipt']['name'])) {

            $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','pdf'];

            if (!in_array($ext, $allowed)) {
                $error = "Receipt must be JPG, PNG or PDF.";
            }
            elseif ($_FILES['receipt']['size'] > 2*1024*1024) {
                $error = "Receipt must be under 2MB.";
            }
            else {
                if (!empty($receipt_name) &&
                    file_exists("../uploads/requests_receipts/".$receipt_name)) {
                    unlink("../uploads/requests_receipts/".$receipt_name);
                }

                $receipt_name = time()."_receipt.".$ext;

                move_uploaded_file(
                    $_FILES['receipt']['tmp_name'],
                    "../uploads/requests_receipts/".$receipt_name
                );
            }
        }

        if (empty($receipt_name)) {
            $error = "Receipt upload is mandatory.";
        }

        if (empty($error)) {

            $stmt = $conn->prepare("
                UPDATE admission_requests
                SET remark=?,
    payment_amount=?,
    payment_structure=?,
    payment_mode=?,
    payment_date=?,
    receipt_image=?,
    discount_type=?,
    discount_percent=?,
    discount_amount=?,
    final_total=?,
                    status='Shortlisted', reviewed_by=?, reviewed_at=NOW()
                WHERE id=?
            ");

            $stmt->bind_param(
                "sdssssssddii",
                $remark,
                $amount,
                $structure,
                $mode,
                $payment_date,
                $receipt_name,
                $discount_type,
$discount_percent,
$discount_amount,
$final_total,
                $_SESSION['admin_id'],
                $id
            );

            $stmt->execute();
            header("Location:view_request.php?id=".$id);
            exit();
        }
    }
}

/* ================= SUPERADMIN APPROVE ================= */

if (isset($_POST['approve']) && $_SESSION['role'] === 'superadmin') {

    if ($request['status'] === 'Shortlisted') {

        $conn->begin_transaction();

        try {
            $batch_id = intval($_POST['batch_id']);

/* Get capacity */
$stmt = $conn->prepare("SELECT capacity FROM batches WHERE id=?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$stmt->bind_result($capacity);
$stmt->fetch();
$stmt->close();

/* Count current students */
$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE batch_id=?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$stmt->bind_result($current_count);
$stmt->fetch();
$stmt->close();

if ($current_count >= $capacity) {
    throw new Exception("This batch is full.");
}

            $year = date("Y");

/* Get last admission number of this year */
$stmt = $conn->prepare("
    SELECT admission_no 
    FROM students 
    WHERE admission_no LIKE CONCAT('VIG', ?, '-%')
    ORDER BY id DESC 
    LIMIT 1
");
$stmt->bind_param("s", $year);
$stmt->execute();
$stmt->bind_result($last_adm_no);
$stmt->fetch();
$stmt->close();

if ($last_adm_no) {
    $last_sequence = intval(substr($last_adm_no, -3));
    $sequence = $last_sequence + 1;
} else {
    $sequence = 1;
}

$admission_no = "VIG".$year."-".str_pad($sequence, 3, "0", STR_PAD_LEFT);

            if (!empty($request['photo']) &&
                file_exists("../uploads/requests/".$request['photo'])) {
                rename(
                    "../uploads/requests/".$request['photo'],
                    "../uploads/students/".$request['photo']
                );
            }

            $fees_paid = $request['payment_amount'];
            $status_student = "Active";
            $date_option = $_POST['date_option'] ?? 'same';

if($date_option === 'change' && !empty($_POST['custom_admission_date'])){
    $final_admission_date = $_POST['custom_admission_date'];
} else {
    $final_admission_date = $request['admission_date'];
}

/* Fallback safety */
if(empty($final_admission_date) || $final_admission_date == '0000-00-00'){
    $final_admission_date = date("Y-m-d");
}

$stmt = $conn->prepare("
INSERT INTO students (
    admission_no,
    full_name,
    dob,
    gender,
    phone,
    email,
    photo,
    father_name,
    mother_name,
    guardian_phone,
    address,
    city,
    state,
    pincode,
    course,
    batch,
    admission_date,
    course_duration,
    total_fees,
    fees_paid,
    status,
    sequence_no,
    medium,
    institution_name,
    institution_address,
    degree,
    percentage,
    main_subjects,
    passing_year,
    discount_type,
    discount_percent,
    discount_amount,
    final_total,
    payment_structure,
    heard_about,
    referred_student_name,
    referred_student_phone,
    heard_other_text,
    batch_id
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"ssssssssssssssssssddsissssssssdddsssssi",
$admission_no,
$request['full_name'],
$request['dob'],
$request['gender'],
$request['phone'],
$request['email'],
$request['photo'],
$request['father_name'],
$request['mother_name'],
$request['guardian_phone'],
$request['address'],
$request['city'],
$request['state'],
$request['pincode'],
$request['course'],
$request['batch'],
$final_admission_date,
$request['course_duration'],
$course_fee,
$fees_paid,
$status_student,
$sequence,
$request['medium'],
$request['institution_name'],
$request['institution_address'],
$request['degree'],
$request['percentage'],
$request['main_subjects'],
$request['passing_year'],
$request['discount_type'],
$request['discount_percent'],
$request['discount_amount'],
$request['final_total'],
$request['payment_structure'],
$request['heard_about'],
$request['referred_student_name'],
$request['referred_student_phone'],
$request['heard_other_text'],
$batch_id
);
            $stmt->execute();
            $student_id = $stmt->insert_id;

            if (!empty($request['receipt_image']) &&
                file_exists("../uploads/requests_receipts/".$request['receipt_image'])) {
                rename(
                    "../uploads/requests_receipts/".$request['receipt_image'],
                    "../uploads/receipts/".$request['receipt_image']
                );
            }

            $stmt = $conn->prepare("
                INSERT INTO payments (
                    student_id, amount, payment_structure,
                    payment_mode, payment_date,
                    receipt_image, received_by
                )
                VALUES (?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "idssssi",
                $student_id,
                $request['payment_amount'],
                $request['payment_structure'],
                $request['payment_mode'],
                $request['payment_date'],
                $request['receipt_image'],
                $request['reviewed_by']
            );

            $stmt->execute();

            $stmt = $conn->prepare("UPDATE admission_requests SET status='Approved' WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();

            $conn->commit();
            header("Location:pending.php");
            exit();

        } catch (Exception $e) {
    $conn->rollback();
    $error = "Approval failed: " . $e->getMessage();
}
    }
}

/* ================= REJECT ================= */

if (isset($_POST['reject']) && $_SESSION['role']==='superadmin') {

    $stmt = $conn->prepare("UPDATE admission_requests SET status='Rejected' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();

    header("Location:view_request.php?id=".$id);
    exit();
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.section-card{
    background:#fff;
    padding:30px;
    border-radius:16px;
    border:1px solid #E6DCD4;
    margin-bottom:25px;
}

/* Scope payment form styling properly */
.section-card form textarea,
.section-card form input,
.section-card form select{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #D8CCC3;
    margin-top:5px;
}

.section-card form label{
    font-weight:500;
    display:block;
    margin-top:15px;
    margin-bottom:5px;
}

.submit-btn{
    background:#7A1E3A;
    color:#fff;
    padding:10px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-top:15px;
}

.action-btn{
    background:#1E5631;
    color:#fff;
    padding:10px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-right:10px;
}

.reject-btn{
    background:#8B0000;
}

.profile-container{
    display:flex;
    gap:30px;
    flex-wrap:wrap;
}

.profile-photo img{
    width:180px;
    border-radius:12px;
    border:1px solid #ddd;
}

.profile-details{
    flex:1;
    min-width:300px;
}
</style>
<div class="section-card">
<h3>Admission Status</h3>
<p><strong><?php echo $request['status']; ?></strong></p>
</div>

<div class="section-card">
<h3>Student Profile</h3>

<div class="profile-container">

<div class="profile-photo">
<?php if(!empty($request['photo'])): ?>
<img src="/cims/uploads/requests/<?php echo $request['photo']; ?>">
<?php endif; ?>
</div>

<div class="profile-details">

<p><strong>Name:</strong> <?php echo $request['full_name']; ?></p>
<p><strong>DOB:</strong> <?php echo $request['dob']; ?></p>
<p><strong>Gender:</strong> <?php echo $request['gender']; ?></p>
<p><strong>Phone:</strong> <?php echo $request['phone']; ?></p>
<p><strong>Email:</strong> <?php echo $request['email']; ?></p>

<hr>

<p><strong>Father:</strong> <?php echo $request['father_name']; ?></p>
<p><strong>Mother:</strong> <?php echo $request['mother_name']; ?></p>
<p><strong>Guardian Phone:</strong> <?php echo $request['guardian_phone']; ?></p>

<hr>

<p><strong>Address:</strong><br>
<?php echo $request['address']; ?><br>
<?php echo $request['city']; ?>,
<?php echo $request['state']; ?> -
<?php echo $request['pincode']; ?>
</p>


<hr>

<p><strong>Course:</strong> <?php echo $request['course']; ?></p>
<p><strong>Batch:</strong></p>

<p><?php echo $request['batch']; ?></p>


</div>

</div>

</div>
<div class="section-card">
    <h3>Qualification Details</h3>

    <div class="info-grid">

        <div class="info-item">
            <strong>Medium of Education: </strong>
            <?php echo !empty($request['medium']) 
                ? htmlspecialchars($request['medium']) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item">
            <strong>Institution Name: </strong>
            <?php echo !empty($request['institution_name']) 
                ? htmlspecialchars($request['institution_name']) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item full-width">
            <strong>Institution Address: </strong>
            <?php echo !empty($request['institution_address']) 
                ? nl2br(htmlspecialchars($request['institution_address'])) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item">
            <strong>Degree / Diploma: </strong>
            <?php echo !empty($request['degree']) 
                ? htmlspecialchars($request['degree']) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item">
            <strong>Percentage: </strong>
            <?php echo !empty($request['percentage']) 
                ? htmlspecialchars($request['percentage']) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item">
            <strong>Main Subjects: </strong>
            <?php echo !empty($request['main_subjects']) 
                ? htmlspecialchars($request['main_subjects']) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item">
            <strong>Year of Passing: </strong>
            <?php echo !empty($request['passing_year']) 
                ? htmlspecialchars($request['passing_year']) 
                : "Not Provided"; ?>
        </div>

    </div>
</div>
<div class="section-card">
    <h3>Referral Information</h3>

    <div class="info-grid">

        <div class="info-item">
            <strong>Heard About Us: </strong>
            <?php echo !empty($request['heard_about']) 
                ? htmlspecialchars($request['heard_about']) 
                : "Not Mentioned"; ?>
        </div>

        <?php if (!empty($request['heard_about']) && 
                  strpos($request['heard_about'], 'Student') !== false): ?>

        <div class="info-item">
            <strong>Referred Student Name: </strong>
            <?php echo !empty($request['referred_student_name']) 
                ? htmlspecialchars($request['referred_student_name']) 
                : "Not Provided"; ?>
        </div>

        <div class="info-item">
            <strong>Referred Student Phone: </strong>
            <?php echo !empty($request['referred_student_phone']) 
                ? htmlspecialchars($request['referred_student_phone']) 
                : "Not Provided"; ?>
        </div>

        <?php endif; ?>

        <?php if (!empty($request['heard_about']) && 
                  strpos($request['heard_about'], 'Others') !== false): ?>

        <div class="info-item full-width">
            <strong>Other Source: </strong>
            <?php echo !empty($request['heard_other_text']) 
                ? htmlspecialchars($request['heard_other_text']) 
                : "Not Provided"; ?>
        </div>

        <?php endif; ?>

    </div>
</div>

<?php if(in_array($_SESSION['role'], ['staff','superadmin']) 
   && !in_array($request['status'],['Approved','Rejected'])): ?>

<div class="section-card">
<h3>Staff Shortlist & Payment</h3>

<?php if(!empty($error)): ?>
<p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<label>Remark</label>
<textarea name="remark"><?php echo $request['remark']; ?></textarea>

<br><br>

<label>Course Total Fees (Official)</label>
<input type="number" value="<?php echo $course_fee; ?>" readonly>


<br><br>

<hr style="margin:25px 0;">

<label>Registration Fee</label>
<input type="number" value="550" readonly>

<label>Examination Fee</label>
<input type="number" value="670" readonly>

<label>Base Amount (Eligible for Discount)</label>
<input type="number"
id="baseAmount"
value="<?php
$base = $course_fee - 550 - 670;
if($base < 0) $base = 0;
echo $base;
?>"
readonly>

<label>Discount Type</label>
<select name="discount_type">
<option value="">No Discount</option>
<option value="General" <?php if($request['discount_type']=="General") echo "selected"; ?>>General</option>
<option value="Scholarship" <?php if($request['discount_type']=="Scholarship") echo "selected"; ?>>Scholarship</option>
<option value="Referral" <?php if($request['discount_type']=="Referral") echo "selected"; ?>>Referral</option>
</select>

<label>Discount Percentage (%)</label>
<input type="number"
step="0.01"
name="discount_percent"
id="discountPercent"
value="<?php echo $request['discount_percent']; ?>">

<label>Discount Amount</label>
<input type="number"
step="0.01"
name="discount_amount"
id="discountAmount"
value="<?php echo $request['discount_amount']; ?>">

<label>Final Payable Total</label>
<input type="number"
name="final_total"
id="finalTotal"
value="<?php echo $request['final_total']; ?>"
readonly>

<label>Amount Paid *</label>
<input type="number" step="0.01" name="payment_amount"
id="amountPaid"
value="<?php echo $request['payment_amount']; ?>" required>

<br><br>

<label>Remaining Balance</label>
<input type="number"
id="remainingBalance"
value="<?php
if($request['final_total']>0){
echo $request['final_total'] - $request['payment_amount'];
}else{
echo $request['payment_amount'] ? $course_fee - $request['payment_amount'] : "";
}
?>"
readonly>

<br><br>

<label>Payment Structure *</label>
<select name="payment_structure" required>
<option value="Full" <?php if($request['payment_structure']=='Full') echo "selected"; ?>>Full</option>
<option value="Monthly" <?php if($request['payment_structure']=='Monthly') echo "selected"; ?>>Monthly</option>
<option value="Quarterly" <?php if($request['payment_structure']=='Quarterly') echo "selected"; ?>>Quarterly</option>
<option value="Custom" <?php if($request['payment_structure']=='Custom') echo "selected"; ?>>Custom</option>
</select>

<br><br>

<label>Payment Mode *</label>
<select name="payment_mode" required>
<option value="Cash" <?php if($request['payment_mode']=='Cash') echo "selected"; ?>>Cash</option>
<option value="Online" <?php if($request['payment_mode']=='Online') echo "selected"; ?>>Online</option>
</select>

<br><br>

<label>Payment Date *</label>
<input type="text" name="payment_date"
value="<?php echo $request['payment_date']; ?>" required>

<br><br>

<label>Upload Receipt *</label>
<input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf">

<?php if(!empty($request['receipt_image'])): ?>
<p>
Current Receipt:
<a href="/cims/uploads/requests_receipts/<?php echo $request['receipt_image']; ?>" target="_blank">
View Receipt
</a>
</p>
<?php endif; ?>

<br><br>


<button type="submit" name="save_shortlist" class="submit-btn">
Save & Shortlist
</button>


</form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

    const totalFees = <?php echo floatval($course_fee); ?>;
    const regFee = 550;
    const examFee = 670;

    const baseAmount = Math.max(totalFees - regFee - examFee, 0);

    const discountPercent = document.getElementById("discountPercent");
    const discountAmount = document.getElementById("discountAmount");
    const finalTotal = document.getElementById("finalTotal");
    const amountPaid = document.getElementById("amountPaid");
    const remainingBalance = document.getElementById("remainingBalance");

    function updateFromPercent(){
        let percent = parseFloat(discountPercent.value) || 0;

        if(percent > 100) percent = 100;

        let amount = (baseAmount * percent) / 100;

        discountAmount.value = amount.toFixed(2);
        updateFinal(amount);
    }

    function updateFromAmount(){
        let amount = parseFloat(discountAmount.value) || 0;

        if(amount > baseAmount) amount = baseAmount;

        let percent = (amount / baseAmount) * 100;

        discountPercent.value = percent.toFixed(2);
        updateFinal(amount);
    }

    function updateFinal(discount){
        let newTotal = baseAmount - discount;
        if(newTotal < 0) newTotal = 0;

        finalTotal.value = newTotal.toFixed(2);
        updateBalance(newTotal);
    }

    function updateBalance(total){
        let paid = parseFloat(amountPaid.value) || 0;
        let due = total - paid;
        if(due < 0) due = 0;

        remainingBalance.value = due.toFixed(2);
    }

    discountPercent.addEventListener("input", updateFromPercent);
    discountAmount.addEventListener("input", updateFromAmount);
    amountPaid.addEventListener("input", function(){
        updateBalance(parseFloat(finalTotal.value));
    });

});
</script>

<?php endif; ?>

<?php if($_SESSION['role']==='superadmin' && $request['status']==='Shortlisted'): ?>

<div class="section-card">
<h3>Review & Decision</h3>

<p><strong>Staff Remark:</strong> <?php echo $request['remark']; ?></p>
<p><strong>Amount Paid:</strong> <?php echo $request['payment_amount']; ?></p>
<p><strong>Structure:</strong> <?php echo $request['payment_structure']; ?></p>
<p><strong>Mode:</strong> <?php echo $request['payment_mode']; ?></p>
<p><strong>Date:</strong> <?php echo $request['payment_date']; ?></p>

<?php if(!empty($request['receipt_image'])): ?>
<p>
<a href="/cims/uploads/requests_receipts/<?php echo $request['receipt_image']; ?>" target="_blank">
View Uploaded Receipt
</a>
</p>
<?php endif; ?>

<form method="POST"
onsubmit="return confirm('Are you absolutely sure you want to approve this admission? This action cannot be undone.');">

<label>Admission Date</label>

<input type="date"
value="<?php echo $request['admission_date']; ?>"
readonly>

<br><br>

<label>Use Submitted Date?</label>
<select name="date_option" id="dateOption" required>
<option value="same">Same as form submitted</option>
<option value="change">Change</option>
</select>

<br><br>

<div id="customDateBox" style="display:none;">
<label>Select New Admission Date</label>
<input type="date" name="custom_admission_date">
</div>

<label>Assign Final Batch *</label>

<select name="batch_id" required>
<option value="">Select Batch</option>

<?php
$result = $conn->query("SELECT * FROM batches WHERE status='Active'");
while($batch = $result->fetch_assoc()):

$countStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE batch_id=?");
$countStmt->bind_param("i", $batch['id']);
$countStmt->execute();
$countStmt->bind_result($count);
$countStmt->fetch();
$countStmt->close();
?>
<option value="<?= $batch['id']; ?>">
Batch <?= $batch['batch_name']; ?>
(<?= $batch['time_slot']; ?>)
[<?= $count ?>/<?= $batch['capacity']; ?>]
</option>
<?php endwhile; ?>
</select>

<br><br>

<button type="submit" name="approve" class="action-btn">Approve</button>

</form>

<br>

<form method="POST"
onsubmit="return confirm('Are you sure you want to reject this admission?');">
<button type="submit" name="reject" class="action-btn reject-btn">Reject</button>
</form>

</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    const option = document.getElementById("dateOption");
    const box = document.getElementById("customDateBox");

    option.addEventListener("change", function(){
        if(this.value === "change"){
            box.style.display = "block";
        } else {
            box.style.display = "none";
        }
    });
});
</script>

<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>