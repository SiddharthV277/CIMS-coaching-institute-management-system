<?php
require_once "../includes/auth.php";

if ($_SESSION['role'] !== 'superadmin') {
    header("Location: list.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cims");

$error = "";

define("REG_FEE", 550);
define("EXAM_FEE", 670);

/* Fetch Courses */
$courses_result = $conn->query("SELECT * FROM courses");
$courses = [];
while($row = $courses_result->fetch_assoc()){
    $courses[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $year = date("Y");

    $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE YEAR(admission_date)=?");
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $sequence = $count + 1;
    $admission_no = "VIG".$year."-".str_pad($sequence,3,"0",STR_PAD_LEFT);

    /* ================= CALCULATION ENGINE ================= */

    $original_total = floatval($_POST['original_total']);
    $base_amount = $original_total - REG_FEE - EXAM_FEE;

    $discount_amount = floatval($_POST['discount_amount']);
    $discount_percent = floatval($_POST['discount_percent']);

    if ($discount_amount > 0) {
        $discount_percent = ($discount_amount / $base_amount) * 100;
    } elseif ($discount_percent > 0) {
        $discount_amount = ($base_amount * $discount_percent) / 100;
    }

    if ($discount_amount > $base_amount) {
        $error = "Discount cannot exceed base amount.";
    }

    $new_base = $base_amount - $discount_amount;
    $total_fees = $new_base + REG_FEE + EXAM_FEE;

    $payment_amount = floatval($_POST['payment_amount']);

    if ($payment_amount <= 0) {
        $error = "Payment amount must be greater than 0.";
    }

    if ($payment_amount > $total_fees) {
        $error = "Payment cannot exceed final total fees.";
    }

    /* ================= PHOTO ================= */

    $photo = NULL;
    if (!empty($_FILES['photo']['name'])) {

        $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($extension, $allowed)) {
            $error = "Only JPG and PNG allowed.";
        }
        elseif ($_FILES['photo']['size'] > 1 * 1024 * 1024) {
            $error = "Image must be under 1MB.";
        }
        else {
            $new_name = $admission_no.".".$extension;
            move_uploaded_file($_FILES['photo']['tmp_name'],"../uploads/students/".$new_name);
            $photo = $new_name;
        }
    }

    /* ================= RECEIPT ================= */

    $receipt_name = NULL;

    if (empty($_FILES['receipt']['name'])) {
        $error = "Receipt upload is mandatory.";
    } else {

        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','pdf'];

        if (!in_array($ext, $allowed)) {
            $error = "Receipt must be JPG, PNG or PDF.";
        }
        elseif ($_FILES['receipt']['size'] > 2*1024*1024) {
            $error = "Receipt must be under 2MB.";
        }
        else {
            $receipt_name = $admission_no."_receipt.".$ext;
            move_uploaded_file($_FILES['receipt']['tmp_name'],"../uploads/receipts/".$receipt_name);
        }
    }

    if (empty($error)) {
        $batch_id = intval($_POST['batch_id']);



        $conn->begin_transaction();

        try {
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

            $status = "Active";

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

            $heard_about = isset($_POST['heard_about']) ? implode(",", $_POST['heard_about']) : "";
            /* ===== PREP VARIABLES FOR BINDING ===== */

$discount_type = ($discount_amount > 0) ? "Amount" : "Percent";
$final_total_value = $total_fees;   // Your calculated final total
$batch_value = $batch_id;          // If you store batch separately

$stmt->bind_param(
"sssssssssssssssisddsisssssdssdddssssssi",
$admission_no,
$_POST['full_name'],
$_POST['dob'],
$_POST['gender'],
$_POST['phone'],
$_POST['email'],
$photo,
$_POST['father_name'],
$_POST['mother_name'],
$_POST['guardian_phone'],
$_POST['address'],
$_POST['city'],
$_POST['state'],
$_POST['pincode'],
$_POST['course'],
$batch_value,                // i
$_POST['admission_date'],
$_POST['course_duration'],
$total_fees,                 // d
$payment_amount,             // d
$status,
$sequence,                   // i
$_POST['medium'],
$_POST['institution_name'],
$_POST['institution_address'],
$_POST['degree'],
$_POST['percentage'],        // d
$_POST['main_subjects'],
$_POST['passing_year'],
$discount_type,
$discount_percent,           // d
$discount_amount,            // d
$final_total_value,          // d
$_POST['payment_structure'],
$heard_about,
$_POST['referred_student_name'],
$_POST['referred_student_phone'],
$_POST['heard_other_text'],
$batch_id                    // i
);
            $stmt->execute();
            $student_id = $stmt->insert_id;

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
                $payment_amount,
                $_POST['payment_structure'],
                $_POST['payment_mode'],
                $_POST['payment_date'],
                $receipt_name,
                $_SESSION['admin_id']
            );

            $stmt->execute();

$conn->commit();
header("Location: list.php?success=added");
exit();

} catch (Exception $e) {
    $conn->rollback();
    $error = "Error: " . $e->getMessage();
}
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<h2>Add New Student (Direct Admission)</h2>

<?php if(!empty($error)): ?>
<div class="error-msg"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<!-- Basic Info -->
<div class="section-card">
<h3>Basic Information</h3>
<div class="form-grid">

<input type="text" name="full_name" placeholder="Full Name" required>
<input type="date" name="dob">
<select name="gender">
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>
<input type="text" name="phone" placeholder="Phone">
<input type="email" name="email" placeholder="Email">

<div class="full-width">
<label>Photo (Max 1MB)</label>
<input type="file" name="photo" accept="image/jpeg,image/png">
</div>

</div>
</div>

<!-- Guardian -->
<div class="section-card">
<h3>Guardian Details</h3>
<div class="form-grid">
<input type="text" name="father_name" placeholder="Father Name">
<input type="text" name="mother_name" placeholder="Mother Name">
<input type="text" name="guardian_phone" placeholder="Guardian Phone">
<textarea name="address" class="full-width" placeholder="Address"></textarea>
<input type="text" name="city" placeholder="City">
<input type="text" name="state" placeholder="State">
<input type="text" name="pincode" placeholder="PIN Code">
</div>
</div>
<!-- ================= QUALIFICATION SECTION ================= -->
<div class="section-card">
    <h3>Educational Qualification</h3>
    <div class="form-grid">

        <div>
            <label>Medium (e.g., CBSE / ICSE / State Board)</label>
            <input type="text"
                   name="medium"
                   placeholder="CBSE / ICSE / State Board">
        </div>

        <div>
            <label>Degree / Class</label>
            <input type="text"
                   name="degree"
                   placeholder="e.g., 12th, BCA, B.Com">
        </div>

        <div class="full-width">
            <label>Institution Name</label>
            <input type="text"
                   name="institution_name"
                   placeholder="School / College Name">
        </div>

        <div class="full-width">
            <label>Institution Address</label>
            <textarea name="institution_address"
                      placeholder="Institution Address"></textarea>
        </div>

        <div>
            <label>Percentage (%)</label>
            <input type="number"
                   step="0.01"
                   name="percentage"
                   max="100"
                   placeholder="Percentage">
        </div>

        <div>
            <label>Passing Year</label>
            <input type="number"
                   name="passing_year"
                   placeholder="e.g., 2023">
        </div>

        <div class="full-width">
            <label>Main Subjects</label>
            <input type="text"
                   name="main_subjects"
                   placeholder="e.g., Maths, Accounts, Computer">
        </div>

    </div>
</div>

<!-- ================= REFERRAL SECTION ================= -->
<div class="section-card">
    <h3>Referral Information</h3>

    <div class="referral-options">

        <label class="ref-checkbox">
            <input type="checkbox" name="heard_about[]" value="Student">
            <span>Student</span>
        </label>

        <label class="ref-checkbox">
            <input type="checkbox" name="heard_about[]" value="Banner">
            <span>Banner</span>
        </label>

        <label class="ref-checkbox">
            <input type="checkbox" name="heard_about[]" value="Direct Mail">
            <span>Direct Mail</span>
        </label>

        <label class="ref-checkbox">
            <input type="checkbox" name="heard_about[]" value="Social Media">
            <span>Social Media</span>
        </label>

        <label class="ref-checkbox">
            <input type="checkbox" name="heard_about[]" value="Others">
            <span>Others</span>
        </label>

    </div>

    <!-- Conditional Fields -->
    <div class="form-grid" style="margin-top:15px;">

        <input type="text"
               name="referred_student_name"
               id="refName"
               placeholder="Referred Student Name"
               style="display:none;">

        <input type="text"
               name="referred_student_phone"
               id="refPhone"
               placeholder="Referred Student Phone"
               style="display:none;">

        <input type="text"
               name="heard_other_text"
               id="otherText"
               placeholder="Please specify"
               style="display:none;">

    </div>
</div>

<!-- Course -->
<div class="section-card">
    <h3>Course Details</h3>
    <div class="form-grid">

        <select name="course" id="courseSelect" required>
            <option value="">Select Course</option>
            <?php foreach($courses as $course): ?>
                <option value="<?php echo $course['course_name']; ?>"
                        data-duration="<?php echo $course['duration_months']; ?>"
                        data-fee="<?php echo $course['fees']; ?>">
                    <?php echo $course['course_name']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="course_duration" id="durationField" readonly placeholder="Course Duration">

        <input type="date" name="admission_date" required>

        <select name="batch_id" required>
<option value="">Select Batch</option>
<?php
$result = $conn->query("SELECT * FROM batches WHERE status='Active'");
while($batch = $result->fetch_assoc()):
?>
<option value="<?= $batch['id']; ?>">
Batch <?= $batch['batch_name']; ?> 
(<?= $batch['time_slot']; ?>)
</option>
<?php endwhile; ?>
</select>

    </div>
</div>

<!-- ================= DISCOUNT SECTION ================= -->
<div class="section-card">
    <h3>Fee & Discount</h3>
    <div class="form-grid">

        <div>
            <label>Total Course Fees</label>
            <input type="number" id="originalFeeDisplay" readonly>
        </div>

        <div>
            <label>Discount Amount (₹)</label>
            <input type="number" step="0.01" id="discountAmount">
        </div>

        <div>
            <label>Discount Percentage (%)</label>
            <input type="number" step="0.01" id="discountPercent">
        </div>

        <div>
            <label>Final Total</label>
            <input type="number" id="finalTotalDisplay" readonly>
        </div>

    </div>
</div>

<!-- Hidden backend fields -->
<input type="hidden" name="original_total" id="hiddenOriginal">
<input type="hidden" name="discount_amount" id="hiddenDiscountAmount">
<input type="hidden" name="discount_percent" id="hiddenDiscountPercent">

<!-- Payment -->
<div class="section-card">
    <h3>Initial Payment</h3>
    <div class="form-grid">

        <input type="number"
               step="0.01"
               name="payment_amount"
               id="paymentAmount"
               placeholder="Amount Paid"
               required>

        <div>
            <label>Remaining Balance</label>
            <input type="number" id="remainingDisplay" readonly>
        </div>

        <select name="payment_structure" required>
            <option value="Full">Full</option>
            <option value="Monthly">Monthly</option>
            <option value="Quarterly">Quarterly</option>
            <option value="Custom">Custom</option>
        </select>

        <select name="payment_mode" required>
            <option value="Cash">Cash</option>
            <option value="Online">Online</option>
        </select>

        <input type="date" name="payment_date" required>

        <div class="full-width">
            <label>Upload Receipt (Max 2MB)</label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>

    </div>
</div>

<button type="submit" class="submit-btn">Create Student</button>

</form>

<script>
const REG = 550;
const EXAM = 670;

let originalTotal = 0;

function calculate(trigger = null) {

    let base = originalTotal - REG - EXAM;
    if (base < 0) base = 0;

    let discountAmountInput = document.getElementById("discountAmount");
    let discountPercentInput = document.getElementById("discountPercent");
    let paymentInput = document.getElementById("paymentAmount");

    let discountAmt = parseFloat(discountAmountInput.value) || 0;
    let discountPct = parseFloat(discountPercentInput.value) || 0;

    if (trigger === "amount") {
        discountPct = base > 0 ? (discountAmt / base) * 100 : 0;
        discountPercentInput.value = discountPct.toFixed(2);
    }

    if (trigger === "percent") {
        discountAmt = (base * discountPct) / 100;
        discountAmountInput.value = discountAmt.toFixed(2);
    }

    if (discountAmt > base) {
        discountAmt = base;
        discountAmountInput.value = base.toFixed(2);
    }

    let finalTotal = (base - discountAmt) + REG + EXAM;

    document.getElementById("finalTotalDisplay").value = finalTotal.toFixed(2);
    document.getElementById("remainingDisplay").value =
        (finalTotal - (parseFloat(paymentInput.value) || 0)).toFixed(2);

    document.getElementById("hiddenOriginal").value = originalTotal;
    document.getElementById("hiddenDiscountAmount").value = discountAmt;
    document.getElementById("hiddenDiscountPercent").value = discountPct;
}

/* Course Change */
document.getElementById("courseSelect").addEventListener("change", function(){
    let selected = this.options[this.selectedIndex];

    originalTotal = parseFloat(selected.getAttribute("data-fee")) || 0;

    document.getElementById("durationField").value =
        selected.getAttribute("data-duration") + " Months";

    document.getElementById("originalFeeDisplay").value = originalTotal;

    calculate();
});

/* Discount Inputs */
document.getElementById("discountAmount").addEventListener("input", function(){
    calculate("amount");
});

document.getElementById("discountPercent").addEventListener("input", function(){
    calculate("percent");
});

/* Payment Input */
document.getElementById("paymentAmount").addEventListener("input", function(){
    calculate();
});
</script>
<script>
const checkboxes = document.querySelectorAll('input[name="heard_about[]"]');

function handleReferralToggle() {
    let values = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    document.getElementById("refName").style.display =
        values.includes("Student") ? "block" : "none";

    document.getElementById("refPhone").style.display =
        values.includes("Student") ? "block" : "none";

    document.getElementById("otherText").style.display =
        values.includes("Others") ? "block" : "none";
}

checkboxes.forEach(cb => {
    cb.addEventListener("change", handleReferralToggle);
});
</script>

<?php require_once "../includes/footer.php"; ?>