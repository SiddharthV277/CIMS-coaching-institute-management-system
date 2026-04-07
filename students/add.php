<?php
require_once "../includes/auth.php";

if ($_SESSION['role'] !== 'superadmin') {
    header("Location: list.php");
    exit();
}

require_once dirname(__DIR__) . '/includes/db.php';

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
    
    $registration_no = trim($_POST['registration_no'] ?? '');
    if ($registration_no === '') {
        $error = "Registration number is required.";
    } else {
        if (substr($registration_no, 0, 3) !== 'MCO') {
            $registration_no = 'MCO' . $registration_no;
        }
    }

    /* ================= CALCULATION ENGINE ================= */

    $original_total = floatval($_POST['original_total']);
    $base_amount = $original_total - REG_FEE - EXAM_FEE;
    if ($base_amount < 0) $base_amount = 0;

    $reg_cost = isset($_POST['include_reg_fee']) ? REG_FEE : 0;
    $exam_cost = isset($_POST['include_exam_fee']) ? EXAM_FEE : 0;

    $discount_amount = floatval($_POST['discount_amount']);
    $discount_percent = floatval($_POST['discount_percent']);

    if ($base_amount > 0 && $discount_amount > 0) {
        $discount_percent = ($discount_amount / $base_amount) * 100;
    } elseif ($base_amount > 0 && $discount_percent > 0) {
        $discount_amount = ($base_amount * $discount_percent) / 100;
    } else {
        $discount_amount = 0;
        $discount_percent = 0;
    }

    if ($base_amount > 0 && $discount_amount > $base_amount) {
        $error = "Discount cannot exceed base amount.";
    }

    $new_base = $base_amount - $discount_amount;
    $total_fees = $new_base + $reg_cost + $exam_cost;

    $payment_amount = floatval($_POST['payment_amount']);

    if ($payment_amount < 0) {
        $error = "Payment amount cannot be negative.";
    } elseif ($payment_amount <= 0 && $total_fees > 0) {
        $error = "Payment amount must be greater than 0 when there are fees due.";
    }

    if ($total_fees > 0 && $payment_amount > $total_fees) {
        $error = "Payment cannot exceed final total fees.";
    }

    /* ================= PHOTO ================= */

    $photo = NULL;
    if (!empty($_POST['camera_photo'])) {
        $base64_string = $_POST['camera_photo'];
        list($type, $data) = explode(';', $base64_string);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        
        $new_name = $registration_no.".jpg";
        file_put_contents("../uploads/students/".$new_name, $data);
        $photo = $new_name;
    }
    elseif (!empty($_FILES['photo']['name'])) {

        $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($extension, $allowed)) {
            $error = "Only JPG and PNG allowed.";
        }
        elseif ($_FILES['photo']['size'] > 1 * 1024 * 1024) {
            $error = "Image must be under 1MB.";
        }
        else {
            $new_name = $registration_no.".".$extension;
            move_uploaded_file($_FILES['photo']['tmp_name'],"../uploads/students/".$new_name);
            $photo = $new_name;
        }
    }

    /* ================= RECEIPT NUMBER ================= */

    $receipt_number = trim($_POST['receipt_number'] ?? '');

    if (empty($error)) {
        $batch_id = intval($_POST['batch_id']);

        // Server-side uniqueness check for registration_no
        if ($registration_no !== NULL) {
            $chk = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_no = ?");
            $chk->bind_param("s", $registration_no);
            $chk->execute();
            $chk->bind_result($dup);
            $chk->fetch();
            $chk->close();
            if ($dup > 0) {
                $error = "Registration number already exists.";
            }
        }
    }

    if (empty($error)) {


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
registration_no,
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
batch_id,
receipt_number
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

            $heard_about = isset($_POST['heard_about']) ? implode(",", $_POST['heard_about']) : "";
            /* ===== PREP VARIABLES FOR BINDING ===== */

$discount_type = ($discount_amount > 0) ? "Amount" : "Percent";
$final_total_value = $new_base + $reg_cost + $exam_cost;   

// Fetch batch name
$stmt_b = $conn->prepare("SELECT batch_name FROM batches WHERE id=?");
$stmt_b->bind_param("i", $batch_id);
$stmt_b->execute();
$stmt_b->bind_result($batch_name);
$stmt_b->fetch();
$stmt_b->close();

$batch_value = $batch_name ? $batch_name : $batch_id;

$course = $_POST['course'] === 'Other' ? $_POST['custom_course_name'] : $_POST['course'];
$duration = $_POST['course'] === 'Other' ? $_POST['custom_course_duration'] . " Months" : $_POST['course_duration'];
$fee = $_POST['course'] === 'Other' ? $_POST['custom_total_fees'] : $total_fees;

$stmt->bind_param(
"ssssssssssssssssssddsissssssssdddsssssis",
$registration_no,
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
$course,
$batch_value,                
$_POST['admission_date'],
$duration,
$fee,                 
$payment_amount,             
$status,
$sequence,                   
$_POST['medium'],
$_POST['institution_name'],
$_POST['institution_address'],
$_POST['degree'],
$_POST['percentage'],        
$_POST['main_subjects'],
$_POST['passing_year'],
$discount_type,
$discount_percent,           
$discount_amount,            
$final_total_value,          
$_POST['payment_structure'],
$heard_about,
$_POST['referred_student_name'],
$_POST['referred_student_phone'],
$_POST['heard_other_text'],
$batch_id,
$receipt_number
);
            $stmt->execute();
            $student_id = $stmt->insert_id;

            /* ================= REFERRAL LOGIC ================= */
            if (!empty($_POST['referred_student_name'])) {
                $claimed_name = trim($_POST['referred_student_name']);
                $claimed_phone = trim($_POST['referred_student_phone'] ?? '');
                
                $chk_ref = $conn->prepare("SELECT id FROM referral_accounts WHERE referrer_name = ? AND (referrer_phone = ? OR ? = '')");
                $chk_ref->bind_param("sss", $claimed_name, $claimed_phone, $claimed_phone);
                $chk_ref->execute();
                $ref_res = $chk_ref->get_result();

                if ($ref_res->num_rows > 0) {
                    $referrer_id = $ref_res->fetch_assoc()['id'];
                    $stmt_r = $conn->prepare("INSERT INTO referred_students (referral_account_id, referred_student_name, referred_student_phone, admitted_student_id, added_by_admin) VALUES (?, ?, ?, ?, 'System Auto-Match')");
                    $stmt_r->bind_param("issi", $referrer_id, $_POST['full_name'], $_POST['phone'], $student_id);
                    $stmt_r->execute();

                    $stmt_u = $conn->prepare("UPDATE referral_accounts SET total_points = total_points + 1, remaining_points = remaining_points + 1 WHERE id = ?");
                    $stmt_u->bind_param("i", $referrer_id);
                    $stmt_u->execute();
                }
            }
            /* ================================================== */

            $stmt = $conn->prepare("
                INSERT INTO payments (
                    student_id, amount, payment_structure,
                    payment_mode, payment_date,
                    receipt_number, received_by
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
                $receipt_number,
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
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

<!-- Basic Info -->
<div class="section-card">
<h3>Basic Information</h3>
<div class="form-grid">

<div>
    <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Registration No</label>
    <input type="text" name="registration_no" id="registrationNo" placeholder="Registration No" required>
    <small id="regNoWarning" style="color:red; display:none;">This registration number already exists!</small>
</div>
<input type="text" name="full_name" placeholder="Full Name" required>
<div>
    <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Date of Birth (DD-MM-YYYY)</label>
    <input type="text" name="dob" class="flatpickr-date" placeholder="DD-MM-YYYY">
</div>
<select name="gender">
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>
<input type="text" name="phone" placeholder="Phone">
<input type="email" name="email" placeholder="Email">

<div class="full-width">
    <label style="display:block; margin-bottom:10px;">Student Photograph</label>
    
    <div style="display:flex; gap:15px; margin-bottom:15px;">
        <label style="cursor:pointer;"><input type="radio" name="photo_source" value="upload" checked onchange="togglePhotoSource()"> Upload File</label>
        <label style="cursor:pointer;"><input type="radio" name="photo_source" value="camera" onchange="togglePhotoSource()"> Take Photo</label>
    </div>

    <!-- Upload Interface -->
    <div id="uploadInterface">
        <input type="file" name="photo" id="photoFile" accept="image/jpeg,image/png">
        <small style="color:#666;">Max size 1MB. Allowed: JPG, PNG.</small>
    </div>

    <!-- Camera Interface -->
    <div id="cameraInterface" style="display:none; text-align:center; background:#f9f9f9; padding:15px; border-radius:12px; border:1px solid #ddd;">
        <video id="cameraStream" style="width:100%; max-width:400px; border-radius:8px; background:#000; transform: scaleX(-1);" autoplay playsinline></video>
        <canvas id="cameraCanvas" style="display:none;"></canvas>
        <img id="photoPreview" style="display:none; width:100%; max-width:400px; border-radius:8px; margin: 0 auto;">
        
        <input type="hidden" name="camera_photo" id="cameraPhotoData">
        
        <div style="margin-top:10px; display:flex; gap:10px; justify-content:center;">
            <button type="button" id="btnCapture" onclick="takePhoto()" style="padding:10px 20px; font-size:14px; background:#C0392B; color:#fff; border:none; border-radius:8px; cursor:pointer;">📸 Capture Photo</button>
            <button type="button" id="btnRetake" onclick="retakePhoto()" style="display:none; padding:10px 20px; font-size:14px; background:#555; color:#fff; border:none; border-radius:8px; cursor:pointer;">🔄 Retake</button>
        </div>
    </div>
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
            <option value="Other">Other (Custom Course)</option>
        </select>
        
        <div id="customCourseBox" style="display:none; background:#f9f9f9; padding:15px; border-radius:12px; border:1px solid #ddd; margin-bottom:15px; grid-column:1/-1;">
            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                <input type="text" name="custom_course_name" id="customCourseName" placeholder="Custom Course Name">
                <input type="number" name="custom_course_duration" id="customDuration" placeholder="Duration (Months)">
                <input type="number" name="custom_total_fees" id="customFees" placeholder="Total Fees (₹)">
            </div>
        </div>

        <input type="text" name="course_duration" id="durationField" readonly placeholder="Course Duration">

        <div>
            <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Admission Date (DD-MM-YYYY)</label>
            <input type="text" name="admission_date" class="flatpickr-date" required placeholder="DD-MM-YYYY">
        </div>

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
            <div style="margin-top: 10px; display:flex; flex-direction:column; gap:8px;">
                <label style="font-size: 13px; font-weight:normal; cursor:pointer;">
                    <input type="checkbox" name="include_reg_fee" id="includeRegFee" value="1" checked onchange="calculate()"> Include Registration Fee (₹550)
                </label>
                <label style="font-size: 13px; font-weight:normal; cursor:pointer;">
                    <input type="checkbox" name="include_exam_fee" id="includeExamFee" value="1" checked onchange="calculate()"> Include Examination Fee (₹670)
                </label>
            </div>
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
            <label>Amount After Discount</label>
            <input type="number" id="amountAfterDiscount" readonly>
        </div>

        <div>
            <label>Final Payable Total (+ Reg & Exam Fees)</label>
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
               min="0"
               name="payment_amount"
               id="paymentAmount"
               placeholder="Amount Paid"
               required>

        <div>
            <label>Remaining Balance</label>
            <input type="number" id="remainingDisplay" readonly>
        </div>

        <select name="payment_structure" id="paymentStructure" required>
            <option value="Full">Full</option>
            <option value="Monthly">Monthly</option>
            <option value="Quarterly">Quarterly</option>
            <option value="Custom">Custom</option>
        </select>

        <select name="payment_mode" required>
            <option value="Cash">Cash</option>
            <option value="Online">Online</option>
        </select>

        <div>
            <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Payment Date (DD-MM-YYYY)</label>
            <input type="text" name="payment_date" class="flatpickr-date" required placeholder="DD-MM-YYYY">
        </div>

        <div class="full-width">
            <label>Receipt Number</label>
            <input type="text" name="receipt_number" placeholder="Receipt / Transaction Number">
        </div>

    </div>
</div>

<button type="submit" class="submit-btn">Create Student</button>

</form>

<script>
// Camera Logic
let videoStream = null;

function togglePhotoSource() {
    let source = document.querySelector('input[name="photo_source"]:checked').value;
    let uploadDiv = document.getElementById('uploadInterface');
    let cameraDiv = document.getElementById('cameraInterface');
    let fileInput = document.getElementById('photoFile');
    let cameraData = document.getElementById('cameraPhotoData');
    
    if (source === 'upload') {
        uploadDiv.style.display = 'block';
        cameraDiv.style.display = 'none';
        cameraData.value = ""; // Clear camera data
        stopCamera();
    } else {
        uploadDiv.style.display = 'none';
        cameraDiv.style.display = 'block';
        fileInput.value = ""; // Clear file input
        startCamera();
    }
}

function startCamera() {
    let video = document.getElementById('cameraStream');
    let preview = document.getElementById('photoPreview');
    let btnCapture = document.getElementById('btnCapture');
    let btnRetake = document.getElementById('btnRetake');
    
    // Reset view
    video.style.display = 'block';
    preview.style.display = 'none';
    btnCapture.style.display = 'inline-block';
    btnRetake.style.display = 'none';
    document.getElementById('cameraPhotoData').value = "";

    navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
    .then(function(stream) {
        videoStream = stream;
        video.srcObject = stream;
    })
    .catch(function(err) {
        alert("Camera access denied or device not found.");
        document.querySelector('input[value="upload"]').click(); // Revert to upload
    });
}

function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
}

function takePhoto() {
    let video = document.getElementById('cameraStream');
    let canvas = document.getElementById('cameraCanvas');
    let preview = document.getElementById('photoPreview');
    let cameraData = document.getElementById('cameraPhotoData');
    let btnCapture = document.getElementById('btnCapture');
    let btnRetake = document.getElementById('btnRetake');
    
    // Set canvas dimensions to match video stream
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    let ctx = canvas.getContext('2d');
    
    // Mirror the image horizontally if the video is mirrored
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Compress at 60% JPEG quality
    let dataUrl = canvas.toDataURL('image/jpeg', 0.6);
    
    // Apply data to hidden input and preview
    cameraData.value = dataUrl;
    preview.src = dataUrl;
    
    // Switch to preview mode
    video.style.display = 'none';
    preview.style.display = 'block';
    btnCapture.style.display = 'none';
    btnRetake.style.display = 'inline-block';
    
    // Pause stream
    stopCamera();
}

function retakePhoto() {
    startCamera();
}

const REG = 550;
const EXAM = 670;

let originalTotal = 0;

function calculate(trigger = null) {

    let base = originalTotal - REG - EXAM;
    if (base < 0) base = 0;

    let discountAmountInput = document.getElementById("discountAmount");
    let discountPercentInput = document.getElementById("discountPercent");
    let paymentInput = document.getElementById("paymentAmount");

    let includeReg = document.getElementById("includeRegFee").checked ? REG : 0;
    let includeExam = document.getElementById("includeExamFee").checked ? EXAM : 0;

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

    let finalTotal = (base - discountAmt) + includeReg + includeExam;

    if(document.getElementById("amountAfterDiscount")) {
        document.getElementById("amountAfterDiscount").value = (base - discountAmt).toFixed(2);
    }
    document.getElementById("finalTotalDisplay").value = finalTotal.toFixed(2);
    let remaining = finalTotal - (parseFloat(paymentInput.value) || 0);
    document.getElementById("remainingDisplay").value = remaining.toFixed(2);

    document.getElementById("hiddenOriginal").value = originalTotal;
    document.getElementById("hiddenDiscountAmount").value = discountAmt;
    document.getElementById("hiddenDiscountPercent").value = discountPct;

    // Payment Structure Restrictions
    let paymentStruct = document.getElementById("paymentStructure");
    let fullOpt = paymentStruct.querySelector("option[value='Full']");

    // If final total is 0, always lock to Full (free course)
    if (finalTotal <= 0) {
        fullOpt.disabled = false;
        paymentStruct.value = "Full";
        paymentStruct.style.pointerEvents = "none";
        paymentStruct.style.opacity = "0.7";
    } else if (remaining <= 0) {
        // Paid in full
        fullOpt.disabled = false;
        paymentStruct.value = "Full";
        paymentStruct.style.pointerEvents = "none";
        paymentStruct.style.opacity = "0.7";
    } else {
        // Partial payment — Full not allowed
        fullOpt.disabled = true;
        paymentStruct.style.pointerEvents = "auto";
        paymentStruct.style.opacity = "1";
        if (paymentStruct.value === "Full") {
            paymentStruct.value = "Monthly";
        }
    }
}

/* Course Change */
document.getElementById("courseSelect").addEventListener("change", function(){
    let selected = this.options[this.selectedIndex];

    if (selected.value === 'Other') {
        document.getElementById("customCourseBox").style.display = "block";
        document.getElementById("durationField").value = "Custom";
        originalTotal = 0;
        document.getElementById("originalFeeDisplay").value = 0;
        document.getElementById("customCourseName").required = true;
        document.getElementById("customDuration").required = true;
        document.getElementById("customFees").required = true;
    } else {
        document.getElementById("customCourseBox").style.display = "none";
        document.getElementById("customCourseName").required = false;
        document.getElementById("customDuration").required = false;
        document.getElementById("customFees").required = false;

        originalTotal = parseFloat(selected.getAttribute("data-fee")) || 0;

        document.getElementById("durationField").value =
            selected.getAttribute("data-duration") + " Months";

        document.getElementById("originalFeeDisplay").value = originalTotal;
    }
    
    calculate();
});

/* Custom Fee Input */
document.getElementById("customFees").addEventListener("input", function(){
    originalTotal = parseFloat(this.value) || 0;
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
<script>
// Registration No duplicate check
document.getElementById('registrationNo').addEventListener('blur', function() {
    const val = this.value.trim();
    const warn = document.getElementById('regNoWarning');
    if (!val) { warn.style.display = 'none'; return; }
    fetch('check_registration.php?reg_no=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => { warn.style.display = data.exists ? 'block' : 'none'; });
});
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Init Flatpickr on all date text inputs
document.querySelectorAll('.flatpickr-date').forEach(function(el) {
    flatpickr(el, {
        dateFormat: 'd-m-Y',
        allowInput: true
    });
});

// Before submit, convert dd-mm-yyyy -> yyyy-mm-dd for backend
document.querySelector('form').addEventListener('submit', function() {
    document.querySelectorAll('.flatpickr-date').forEach(function(el) {
        const parts = el.value.split('-');
        if (parts.length === 3 && parts[2].length === 4) {
            el.value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>