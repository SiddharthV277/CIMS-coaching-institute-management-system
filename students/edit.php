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

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $photo = $student['photo'];

    /* PHOTO UPDATE */
    if (!empty($_FILES['photo']['name'])) {

        $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($extension, $allowed)) {
            $error = "Invalid image format. Only JPG and PNG allowed.";
        }
        elseif ($_FILES['photo']['size'] > 1 * 1024 * 1024) {
            $error = "Image size must be under 1MB.";
        }
        else {
            $new_name = $student['registration_no'] . "_" . time() . "." . $extension;

            if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])) {
                unlink("../uploads/students/" . $student['photo']);
            }

            move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                "../uploads/students/" . $new_name
            );

            $photo = $new_name;
        }
    }

    if (empty($error)) {
        $registration_no = trim($_POST['registration_no'] ?? '');
        if ($registration_no === '') {
            $error = "Registration number is required.";
        } else {
            if (substr($registration_no, 0, 3) !== 'MCO') {
                $registration_no = 'MCO' . $registration_no;
            }
        }

        // Duplicate check removed — duplicates are highlighted in the student list and can be corrected there.
    }

    if (empty($error)) {
        $receipt_number = trim($_POST['receipt_number'] ?? '');

        $conn->begin_transaction();

try {

$heard_about = isset($_POST['heard_about'])
? implode(",", $_POST['heard_about'])
: NULL;

$stmt = $conn->prepare("
UPDATE students SET
full_name=?, dob=?, gender=?, phone=?, email=?,
father_name=?, mother_name=?, guardian_phone=?,
address=?, city=?, state=?, pincode=?,
medium=?, institution_name=?, institution_address=?,
degree=?, percentage=?, main_subjects=?, passing_year=?,
photo=?,
heard_about=?, referred_student_name=?,
referred_student_phone=?, heard_other_text=?,
course_duration=?, registration_no=?, receipt_number=?
WHERE id=?
");

$course_duration_with_months = $_POST['course_duration'] . " Months";

// Convert dob from dd-mm-yyyy to Y-m-d for DB
$dob_raw = trim($_POST['dob'] ?? '');
$dob_parsed = $dob_raw ? DateTime::createFromFormat('d-m-Y', $dob_raw) : null;
$dob_db = $dob_parsed ? $dob_parsed->format('Y-m-d') : null;

$stmt->bind_param(
"sssssssssssssssssssssssssssi",
$_POST['full_name'],
$dob_db,
$_POST['gender'],
$_POST['phone'],
$_POST['email'],
$_POST['father_name'],
$_POST['mother_name'],
$_POST['guardian_phone'],
$_POST['address'],
$_POST['city'],
$_POST['state'],
$_POST['pincode'],
$_POST['medium'],
$_POST['institution_name'],
$_POST['institution_address'],
$_POST['degree'],
$_POST['percentage'],
$_POST['main_subjects'],
$_POST['passing_year'],
$photo,
$heard_about,
$_POST['referred_student_name'],
$_POST['referred_student_phone'],
$_POST['heard_other_text'],
$course_duration_with_months,
$registration_no,
$receipt_number,
$id
);

$stmt->execute();

$conn->commit();

header("Location: view.php?id=".$id."&success=updated");
exit();

} catch(Exception $e){
$conn->rollback();
$error = "Update failed.";
}
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>



<h2>Edit Student</h2>

<?php if (!empty($error)): ?>
<div class="error-msg"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

<div class="section-card">
<h3>Basic Information</h3>
<div class="form-grid">
<input type="text" name="full_name" value="<?php echo $student['full_name']; ?>" required>
<div>
    <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Registration No</label>
    <input type="text" name="registration_no" id="registrationNo" value="<?php echo htmlspecialchars($student['registration_no'] ?? ''); ?>" placeholder="Registration No" required>

</div>
<div>
    <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Date of Birth (DD-MM-YYYY)</label>
    <input type="text" name="dob" class="flatpickr-date" value="<?php echo $student['dob'] ? date('d-m-Y', strtotime($student['dob'])) : ''; ?>" placeholder="DD-MM-YYYY">
</div>
<select name="gender">
<option value="Male" <?php if($student['gender']=="Male") echo "selected"; ?>>Male</option>
<option value="Female" <?php if($student['gender']=="Female") echo "selected"; ?>>Female</option>
<option value="Other" <?php if($student['gender']=="Other") echo "selected"; ?>>Other</option>
</select>
<input type="text" name="phone" value="<?php echo $student['phone']; ?>">
<input type="email" name="email" value="<?php echo $student['email']; ?>">

<div class="full-width">
<label>Current Photo</label><br>
<?php if(!empty($student['photo'])): ?>
<img src="/cims/uploads/students/<?php echo $student['photo']; ?>" class="profile-preview">
<?php endif; ?>
<input type="file" name="photo" accept="image/jpeg,image/png">
<small>Upload new photo (optional, max 1MB)</small>
</div>
</div>
</div>

<div class="section-card">
<h3>Guardian Details</h3>
<div class="form-grid">
<input type="text" name="father_name" value="<?php echo $student['father_name']; ?>">
<input type="text" name="mother_name" value="<?php echo $student['mother_name']; ?>">
<input type="text" name="guardian_phone" value="<?php echo $student['guardian_phone']; ?>">
<textarea name="address" class="full-width"><?php echo $student['address']; ?></textarea>
<input type="text" name="city" value="<?php echo $student['city']; ?>">
<input type="text" name="state" value="<?php echo $student['state']; ?>">
<input type="text" name="pincode" value="<?php echo $student['pincode']; ?>">
</div>
</div>

<div class="section-card">
<h3>Academic Details</h3>
<div class="form-grid">
<input type="text" name="medium" value="<?php echo $student['medium']; ?>">
<input type="text" name="institution_name" value="<?php echo $student['institution_name']; ?>">
<textarea name="institution_address" class="full-width"><?php echo $student['institution_address']; ?></textarea>
<input type="text" name="degree" value="<?php echo $student['degree']; ?>">
<input type="text" name="percentage" value="<?php echo $student['percentage']; ?>">
<input type="text" name="main_subjects" value="<?php echo $student['main_subjects']; ?>">
<input type="text" name="passing_year" value="<?php echo $student['passing_year']; ?>">
</div>
</div>

<div class="section-card">
<h3>Referral Information</h3>
<div class="form-grid full-width">

<?php $heard_array = explode(",", $student['heard_about']); ?>

<label><input type="checkbox" name="heard_about[]" value="Student"
<?= in_array("Student",$heard_array)?"checked":"" ?>> Student</label>

<label><input type="checkbox" name="heard_about[]" value="Banner"
<?= in_array("Banner",$heard_array)?"checked":"" ?>> Banner</label>

<label><input type="checkbox" name="heard_about[]" value="Direct Mail"
<?= in_array("Direct Mail",$heard_array)?"checked":"" ?>> Direct Mail</label>

<label><input type="checkbox" name="heard_about[]" value="Social Media"
<?= in_array("Social Media",$heard_array)?"checked":"" ?>> Social Media</label>

<label><input type="checkbox" name="heard_about[]" value="Others"
<?= in_array("Others",$heard_array)?"checked":"" ?>> Others</label>

<input type="text" name="referred_student_name"
value="<?= $student['referred_student_name']; ?>"
placeholder="Referred Student Name">

<input type="text" name="referred_student_phone"
value="<?= $student['referred_student_phone']; ?>"
placeholder="Referred Student Phone">

<input type="text" name="heard_other_text"
value="<?= $student['heard_other_text']; ?>"
placeholder="Other Source">

</div>
</div>

<div class="section-card">
<h3>Course & Fee Details</h3>
<div class="form-grid">

<?php
$batchName = '';
$stmtBatch = $conn->prepare("SELECT batch_name FROM batches WHERE id=?");
$stmtBatch->bind_param("i", $student['batch_id']);
$stmtBatch->execute();
$stmtBatch->bind_result($batchName);
$stmtBatch->fetch();
$stmtBatch->close();
?>

<div class="floating-field">
<label>Course</label>
<input type="text"
value="<?php echo $student['course']; ?>"
readonly>
</div>

<div class="floating-field">
<label>Batch</label>
<input type="text"
value="Batch <?php echo $batchName; ?>"
readonly>
</div>

<div class="floating-field">
<label>Discount Amount</label>
<input type="number"
value="<?php echo $student['discount_amount']; ?>"
readonly>
</div>

<div class="floating-field">
<label>Admission Date (DD-MM-YYYY)</label>
<input type="text"
value="<?php echo $student['admission_date'] ? date('d-m-Y', strtotime($student['admission_date'])) : ''; ?>"
readonly>
</div>

<div class="floating-field">
<label>Course Duration (Months)</label>
<input type="number"
name="course_duration"
value="<?php echo intval($student['course_duration']); ?>">
</div>

<div class="floating-field">
<label>Total Fees (incl. Reg & Exam)</label>
<input type="number"
value="<?php echo $student['total_fees']; ?>"
readonly>
</div>

<div class="floating-field">
<label>Fees Paid</label>
<input type="number"
value="<?php echo $student['fees_paid']; ?>"
readonly>
</div>

<div class="floating-field">
<label>Receipt Number</label>
<input type="text"
name="receipt_number"
value="<?php echo htmlspecialchars($student['receipt_number'] ?? ''); ?>"
placeholder="Receipt / Transaction Number">
</div>

</div>
</div>
<button type="submit" class="submit-btn">Update Student</button>

</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.querySelectorAll('.flatpickr-date').forEach(function(el) {
    flatpickr(el, {
        dateFormat: 'd-m-Y',
        altInput: false,
        allowInput: true
    });
});
</script>
<?php require_once "../includes/footer.php"; ?>