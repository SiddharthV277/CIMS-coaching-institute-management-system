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

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_batch_id = intval($_POST['batch_id']);

if($new_batch_id != $student['batch_id']){

$stmt = $conn->prepare("SELECT capacity FROM batches WHERE id=?");
$stmt->bind_param("i",$new_batch_id);
$stmt->execute();
$stmt->bind_result($capacity);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE batch_id=?");
$stmt->bind_param("i",$new_batch_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if($count >= $capacity){
$error = "Selected batch is full.";
}
}

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
            $new_name = $student['admission_no'] . "." . $extension;

            move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                "../uploads/students/" . $new_name
            );

            $photo = $new_name;
        }
    }

    if (empty($error)) {

        $conn->begin_transaction();

try {

$heard_about = isset($_POST['heard_about'])
? implode(",", $_POST['heard_about'])
: NULL;

$discount_amount = floatval($_POST['discount_amount']);

$stmt_fee = $conn->prepare("SELECT fees FROM courses WHERE course_name=?");
$stmt_fee->bind_param("s", $_POST['course']);
$stmt_fee->execute();
$stmt_fee->bind_result($original_fee);
$stmt_fee->fetch();
$stmt_fee->close();

$regFee = 550;
$examFee = 670;

$base = $original_fee - $regFee - $examFee;
if($base < 0) $base = 0;

if($discount_amount > $base){
$discount_amount = $base;
}

$new_total = ($base - $discount_amount) + $regFee + $examFee;

$stmt = $conn->prepare("
UPDATE students SET
full_name=?, dob=?, gender=?, phone=?, email=?,
father_name=?, mother_name=?, guardian_phone=?,
address=?, city=?, state=?, pincode=?,
course=?, batch_id=?, admission_date=?, course_duration=?,
medium=?, institution_name=?, institution_address=?,
degree=?, percentage=?, main_subjects=?, passing_year=?,
discount_amount=?, total_fees=?, photo=?,
heard_about=?, referred_student_name=?,
referred_student_phone=?, heard_other_text=?
WHERE id=?
");

$stmt->bind_param(
"ssssssssssssssissssssssssddsssssi",
$_POST['full_name'],
$_POST['dob'],
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
$_POST['course'],
$new_batch_id,
$_POST['admission_date'],
$_POST['course_duration'],
$_POST['medium'],
$_POST['institution_name'],
$_POST['institution_address'],
$_POST['degree'],
$_POST['percentage'],
$_POST['main_subjects'],
$_POST['passing_year'],
$discount_amount,
$new_total,
$photo,
$heard_about,
$_POST['referred_student_name'],
$_POST['referred_student_phone'],
$_POST['heard_other_text'],
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

<style>
.section-card {
    background: #fff;
    padding: 35px;
    border-radius: 18px;
    border: 1px solid #E6DCD4;
    box-shadow: 0 25px 50px rgba(60,40,30,0.05);
    margin-bottom: 35px;
}
.section-card h3 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 18px;
    font-weight: 600;
    border-left: 4px solid #7A1E3A;
    padding-left: 12px;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px 30px;
}
.full-width {
    grid-column: 1 / -1;
}
.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #D8CCC3;
    font-size: 14px;
}
.profile-preview {
    width: 120px;
    border-radius: 12px;
    margin-bottom: 10px;
}
.submit-btn {
    background: linear-gradient(135deg, #7A1E3A, #64182F);
    color: #fff;
    padding: 14px 30px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
}
.floating-field {
    position: relative;
}

.floating-field input {
    width: 100%;
    padding: 18px 14px 10px 14px;
    border-radius: 10px;
    border: 1px solid #D8CCC3;
    font-size: 14px;
    background: #fff;
}

.floating-field label {
    position: absolute;
    top: -8px;
    left: 12px;
    background: #fff;
    padding: 0 6px;
    font-size: 12px;
    color: #7A1E3A;
    font-weight: 500;
}
</style>

<h2>Edit Student</h2>

<?php if (!empty($error)): ?>
<div class="error-msg"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="section-card">
<h3>Basic Information</h3>
<div class="form-grid">
<input type="text" name="full_name" value="<?php echo $student['full_name']; ?>" required>
<input type="date" name="dob" value="<?php echo $student['dob']; ?>">
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
<label>Admission Date</label>
<input type="date"
value="<?php echo $student['admission_date']; ?>"
readonly>
</div>

<div class="floating-field">
<label>Course Duration</label>
<input type="text"
value="<?php echo $student['course_duration']; ?>"
readonly>
</div>

<div class="floating-field">
<label>Total Fees</label>
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

</div>
</div>
<button type="submit" class="submit-btn">Update Student</button>

</form>

<?php require_once "../includes/footer.php"; ?> 