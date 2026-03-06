<?php
session_start();
$conn = new mysqli("localhost", "root", "", "cims");

$success = false;
$error = "";
$duplicate_flag = "No";

/* Fetch Courses */
$courses_result = $conn->query("SELECT * FROM courses");
$courses = [];
while($row = $courses_result->fetch_assoc()){
    $courses[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ================= PHOTO UPLOAD ================= */

    $photo = NULL;

    if (!empty($_FILES['photo']['name'])) {

        $file_name = $_FILES['photo']['name'];
        $tmp_name  = $_FILES['photo']['tmp_name'];
        $file_size = $_FILES['photo']['size'];

        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($extension, $allowed)) {
            $error = "Only JPG and PNG images allowed.";
        }
        elseif ($file_size > 1 * 1024 * 1024) {
            $error = "Image must be under 1MB.";
        }
        else {
            $new_name = time()."_".rand(1000,9999).".".$extension;

            move_uploaded_file(
                $tmp_name,
                "uploads/requests/".$new_name
            );

            $photo = $new_name;
        }
    }

    /* ================= DUPLICATE CHECK ================= */

    if (empty($error)) {

        $check = $conn->query("
            SELECT full_name, dob, phone, email
            FROM admission_requests
        ");

        $exact_match = false;
        $three_match = false;

        while ($row = $check->fetch_assoc()) {

            $match = 0;

            if ($row['full_name'] === $_POST['full_name']) $match++;
            if ($row['dob'] === $_POST['dob']) $match++;
            if ($row['phone'] === $_POST['phone']) $match++;
            if ($row['email'] === $_POST['email']) $match++;

            if ($match == 4) {
                $exact_match = true;
                break;
            }

            if ($match >= 3) {
                $three_match = true;
            }
        }

        if ($exact_match) {
            $error = "An admission request with identical details already exists.";
        }
        else {
            if ($three_match) {
                $duplicate_flag = "Might Be Duplicate";
            }
        }
    }

    /* ================= INSERT ================= */

    if (empty($error)) {

        $status = "Pending";

        $heard_about = isset($_POST['heard_about']) ? implode(", ", $_POST['heard_about']) : NULL;
$referred_name = $_POST['referred_student_name'] ?? NULL;
$referred_phone = $_POST['referred_student_phone'] ?? NULL;
$heard_other_text = $_POST['heard_other_text'] ?? NULL;

        $stmt = $conn->prepare("
            INSERT INTO admission_requests (
                full_name, dob, gender, phone, email, photo,
                father_name, mother_name, guardian_phone,
                address, city, state, pincode,
                course, batch, admission_date, course_duration,
                total_fees,
                medium, institution_name, institution_address,
                degree, percentage, main_subjects, passing_year,
                status, duplicate_flag,
heard_about, referred_student_name,
referred_student_phone, heard_other_text
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

       $stmt->bind_param(
"sssssssssssssssssdsssssssssssss",
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
$_POST['batch'],
$_POST['admission_date'],
$_POST['course_duration'],
$_POST['total_fees'],
$_POST['medium'],
$_POST['institution_name'],
$_POST['institution_address'],
$_POST['degree'],
$_POST['percentage'],
$_POST['main_subjects'],
$_POST['passing_year'],
$status,
$duplicate_flag,
$heard_about,
$referred_name,
$referred_phone,
$heard_other_text
);
        $stmt->execute();
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Vigyaan Admission</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">

<style>
body{
margin:0;
font-family:'Poppins',sans-serif;
background:linear-gradient(135deg,#7A1E3A,#D47C6B);
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
}

.wrapper{
max-width:1000px;
width:92%;
background:#fff;
padding:50px;
border-radius:24px;
box-shadow:0 40px 80px rgba(0,0,0,0.25);
}

.logo{text-align:center;margin-bottom:15px;}
.logo img{width:130px;}

h1{
font-family:'Playfair Display',serif;
text-align:center;
margin:10px 0;
}

.subtitle{
text-align:center;
color:#555;
margin-bottom:30px;
}

.form-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px 30px;
}

.full-width{grid-column:1/-1;}

.section-title{
grid-column:1/-1;
margin-top:25px;
font-weight:600;
color:#7A1E3A;
}

input,select,textarea{
width:100%;
padding:13px;
border-radius:12px;
border:1px solid #ddd;
font-size:14px;
}

textarea{min-height:90px;}

button{
background:#7A1E3A;
color:#fff;
border:none;
padding:15px 30px;
border-radius:12px;
cursor:pointer;
}

.success-box{text-align:center;padding:40px;}

.error{
background:#F8D7DA;
padding:12px;
border-radius:10px;
margin-bottom:20px;
color:#721C24;
}

@media(max-width:768px){
.form-grid{grid-template-columns:1fr;}
}
.floating-group {
    position: relative;
}

.floating-group input {
    width: 100%;
    padding: 16px 12px 6px 12px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
    background: transparent;
}

.floating-group label {
    position: absolute;
    left: 12px;
    top: 14px;
    font-size: 14px;
    color: #777;
    pointer-events: none;
    transition: 0.2s ease;
    background: white;
    padding: 0 5px;
}

.floating-group input:focus + label,
.floating-group input:not(:placeholder-shown) + label {
    top: -8px;
    font-size: 12px;
    color: #7A1E3A;
}
.checkbox-group{
display:flex;
flex-wrap:wrap;
gap:18px 30px;
margin-top:15px;
}

.custom-check{
position:relative;
padding-left:32px;
cursor:pointer;
font-size:14px;
user-select:none;
display:inline-flex;
align-items:center;
color:#444;
}

.custom-check input{
position:absolute;
opacity:0;
cursor:pointer;
height:0;
width:0;
}

.checkmark{
position:absolute;
left:0;
height:20px;
width:20px;
background:#fff;
border:2px solid #D8CCC3;
border-radius:6px;
transition:0.2s ease;
}

/* Hover */
.custom-check:hover .checkmark{
border-color:#7A1E3A;
}

/* Checked */
.custom-check input:checked ~ .checkmark{
background:#7A1E3A;
border-color:#7A1E3A;
}

/* Tick */
.checkmark:after{
content:"";
position:absolute;
display:none;
}

.custom-check input:checked ~ .checkmark:after{
display:block;
}

.custom-check .checkmark:after{
left:6px;
top:2px;
width:5px;
height:10px;
border:solid white;
border-width:0 2px 2px 0;
transform:rotate(45deg);
}
</style>
</head>

<body>

<div class="wrapper">

<?php if($success): ?>

<div class="success-box">
<div class="logo">
<img src="assets/images/vigyaan-logo.png">
</div>
<h1>Application Submitted 🎉</h1>
<p>Your admission request has been received successfully.</p>
</div>

<?php else: ?>

<div class="logo">
<img src="assets/images/vigyaan-logo.png">
</div>

<h1>Admission Form</h1>
<p class="subtitle">Begin your journey with Vigyaan</p>

<?php if(!empty($error)): ?>
<div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<div class="form-grid">

<div class="section-title">Basic Information</div>

<input type="text" name="full_name" placeholder="Full Name" required>
<div class="floating-group">
    <input type="date" name="dob" required>
    <label>D.O.B</label>
</div>  

<select name="gender" required>
<option value="">Select Gender</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>

<input type="text" name="phone" placeholder="Phone" required>
<input type="email" name="email" placeholder="Email">

<div class="full-width">
<label>Upload Photograph (Max 1MB)</label>
<input type="file" name="photo" accept="image/jpeg,image/png">
</div>

<div class="section-title">Parent & Address Details</div>

<input type="text" name="father_name" placeholder="Father Name" required>
<input type="text" name="mother_name" placeholder="Mother Name" required>
<input type="text" name="guardian_phone" placeholder="Guardian Phone" required>

<textarea name="address" placeholder="Full Address" class="full-width" required></textarea>
<input type="text" name="city" placeholder="City" required>
<input type="text" name="state" placeholder="State" required>
<input type="text" name="pincode" placeholder="Pincode" required>

<div class="section-title">Course Details</div>

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

<select name="batch" required>
<option value="">Select Preferred Time Slot</option>

<option value="A">Batch A (6:30AM TO 8:00AM)</option>
<option value="B">Batch B (8:00AM TO 9:30AM)</option>
<option value="C">Batch C (9:30AM TO 11:00AM)</option>
<option value="D">Batch D (11:00AM TO 12:30PM)</option>
<option value="E">Batch E (12:30PM TO 2:00PM)</option>
<option value="F">Batch F (2:00PM TO 3:30PM)</option>
<option value="G">Batch G (3:30PM TO 5:00PM)</option>
<option value="H">Batch H (5:00PM TO 6:30PM)</option>

</select>

<div class="floating-group">
    <input type="date" name="admission_date" required>
    <label>Date of Form-filling</label>
</div>
<input type="text" name="course_duration" id="durationField" placeholder="Course Duration" readonly>
<input type="number" name="total_fees" id="feeField" placeholder="Total Fees" readonly>

<div class="section-title">Qualification</div>

<input type="text" name="medium" placeholder="Medium of Education">
<input type="text" name="institution_name" placeholder="Institution Name">
<textarea name="institution_address" placeholder="Institution Address" class="full-width"></textarea>
<input type="text" name="degree" placeholder="Degree / Diploma">
<input type="text" name="percentage" placeholder="Percentage">
<input type="text" name="main_subjects" placeholder="Main Subjects">
<input type="text" name="passing_year" placeholder="Year of Passing">

<div class="section-title">How Did You Hear About Us?</div>

<div class="full-width">

<div class="checkbox-group">

<label class="custom-check">
    <input type="checkbox" name="heard_about[]" value="Student" onchange="toggleReferral()">
    <span class="checkmark"></span>
    Student
</label>

<label class="custom-check">
    <input type="checkbox" name="heard_about[]" value="Banner">
    <span class="checkmark"></span>
    Banner
</label>

<label class="custom-check">
    <input type="checkbox" name="heard_about[]" value="Direct Mail">
    <span class="checkmark"></span>
    Direct Mail
</label>

<label class="custom-check">
    <input type="checkbox" name="heard_about[]" value="Social Media">
    <span class="checkmark"></span>
    Social Media
</label>

<label class="custom-check">
    <input type="checkbox" name="heard_about[]" value="Others" onchange="toggleOther()">
    <span class="checkmark"></span>
    Others
</label>

</div>

</div>

<div id="studentReferralBox" class="full-width" style="display:none;margin-top:15px;">
<input type="text" name="referred_student_name" placeholder="Referred Student Name (Existing Student)">
<input type="text" name="referred_student_phone" placeholder="Referred Student Phone Number">
</div>

<div id="otherBox" class="full-width" style="display:none;margin-top:15px;">
<input type="text" name="heard_other_text" placeholder="Please specify how you heard about us">
</div>

<div class="full-width" style="text-align:center;margin-top:20px;">
<button type="submit">Submit Application</button>
</div>

</div>
</form>

<?php endif; ?>

</div>

<script>
document.getElementById("courseSelect").addEventListener("change",function(){
let selected=this.options[this.selectedIndex];
document.getElementById("durationField").value=
selected.getAttribute("data-duration")+" Months";
document.getElementById("feeField").value=
selected.getAttribute("data-fee");
});
</script>
<script>
function toggleReferral() {
    const studentChecked = document.querySelector('input[value="Student"]').checked;
    document.getElementById("studentReferralBox").style.display =
        studentChecked ? "block" : "none";
}

function toggleOther() {
    const otherChecked = document.querySelector('input[value="Others"]').checked;
    document.getElementById("otherBox").style.display =
        otherChecked ? "block" : "none";
}
</script>

</body>
</html>