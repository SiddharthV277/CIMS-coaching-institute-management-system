<?php

$conn = new mysqli("localhost","root","","cims");

$month = intval($_POST['month']);
$year  = intval($_POST['year']);

$month_name = date("F", mktime(0,0,0,$month,1));

$base_dir = "../exports/";

$year_dir  = $base_dir.$year;
$month_dir = $year_dir."/".$month_name;

/* Create folders */

$overwrite = isset($_POST['overwrite']) ? intval($_POST['overwrite']) : 0;

/* If data already exists and overwrite not allowed */

if(file_exists($month_dir) && !$overwrite){

die("Analytics already exists for this month. Export cancelled.");

}

/* If overwrite is allowed */

if(file_exists($month_dir) && $overwrite){

array_map('unlink', glob("$month_dir/*.csv"));

}

if(!file_exists($year_dir)){
    mkdir($year_dir,0777,true);
}

if(!file_exists($month_dir)){
    mkdir($month_dir,0777,true);
}

//////////////////////////////////////////
// 1️⃣ STUDENTS ANALYTICS EXPORT
//////////////////////////////////////////

$students_file = $month_dir."/students_analytics.csv";
$output = fopen($students_file,"w");

if(!$output){

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

echo '

<div class="analytics-card">

<h2>⚠ Export Failed</h2>

<p>
One of the analytics files appears to be open in another program.
</p>

<p>
Please close the CSV file (for example in Excel) and refresh the page to update the export.
</p>

<a href="export_page.php" class="export-btn">
Return to Export Page
</a>

</div>

';

require_once "../includes/footer.php";
exit();

}

fputcsv($output,[

"Admission No",
"Sequence No",
"Student Name",
"Gender",
"DOB",
"Phone",
"Email",

"Father Name",
"Mother Name",
"Guardian Phone",
"Address",
"City",
"State",
"Pincode",

"Medium",
"Institution Name",
"Institution Address",
"Degree",
"Percentage",
"Main Subjects",
"Passing Year",

"Course",
"Batch Name",
"Batch Time",
"Admission Date",
"Course Duration",

"Total Fees",
"Discount Type",
"Discount %",
"Discount Amount",
"Final Total",
"Fees Paid",
"Remaining Fees",
"Payment Structure",

"Heard About",
"Referred Student Name",
"Referred Student Phone",
"Other Source",

"Status",
"Created At",
"Updated At"
]);

$query = "

SELECT

s.admission_no,
s.sequence_no,
s.full_name,
s.gender,
s.dob,
s.phone,
s.email,

s.father_name,
s.mother_name,
s.guardian_phone,
s.address,
s.city,
s.state,
s.pincode,

s.medium,
s.institution_name,
s.institution_address,
s.degree,
s.percentage,
s.main_subjects,
s.passing_year,

s.course,
b.batch_name,
b.time_slot,
s.admission_date,
s.course_duration,

s.total_fees,
s.discount_type,
s.discount_percent,
s.discount_amount,
s.final_total,
s.fees_paid,
(s.final_total - s.fees_paid) AS remaining_fees,
s.payment_structure,

s.heard_about,
s.referred_student_name,
s.referred_student_phone,
s.heard_other_text,

s.status,
s.created_at,
s.updated_at

FROM students s
LEFT JOIN batches b ON s.batch_id = b.id

WHERE MONTH(s.admission_date)=?
AND YEAR(s.admission_date)=?

";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii",$month,$year);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){

fputcsv($output,[

$row['admission_no'],
$row['sequence_no'],
$row['full_name'],
$row['gender'],
$row['dob'],
$row['phone'],
$row['email'],

$row['father_name'],
$row['mother_name'],
$row['guardian_phone'],
$row['address'],
$row['city'],
$row['state'],
$row['pincode'],

$row['medium'],
$row['institution_name'],
$row['institution_address'],   // FIXED COLUMN
$row['degree'],
$row['percentage'],
$row['main_subjects'],
$row['passing_year'],

$row['course'],
$row['batch_name'],
$row['time_slot'],
$row['admission_date'],
$row['course_duration'],

$row['total_fees'],
$row['discount_type'],
$row['discount_percent'],
$row['discount_amount'],
$row['final_total'],
$row['fees_paid'],
$row['remaining_fees'],
$row['payment_structure'],

$row['heard_about'],
$row['referred_student_name'],
$row['referred_student_phone'],
$row['heard_other_text'],

$row['status'],
$row['created_at'],
$row['updated_at']

]);

}

fclose($output);


//////////////////////////////////////////
// 2️⃣ PAYMENTS ANALYTICS EXPORT
//////////////////////////////////////////

$payments_file = $month_dir."/payments_analytics.csv";
$output = fopen($payments_file,"w");

if(!$output){

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

echo '

<div class="analytics-card">

<h2>⚠ Export Failed</h2>

<p>
One of the analytics files appears to be open in another program.
</p>

<p>
Please close the CSV file (for example in Excel) and refresh the page to update the export.
</p>

<a href="export_page.php" class="export-btn">
Return to Export Page
</a>

</div>

';

require_once "../includes/footer.php";
exit();

}

fputcsv($output,[

"Payment ID",
"Student Name",
"Course",
"Batch",
"Payment Date",
"Amount",
"Payment Mode",
"Payment Structure",
"Received By"

]);

$query = "

SELECT

p.id,
s.full_name,
s.course,
b.batch_name,
p.payment_date,
p.amount,
p.payment_mode,
p.payment_structure,
a.username

FROM payments p

LEFT JOIN students s ON p.student_id = s.id
LEFT JOIN batches b ON s.batch_id = b.id
LEFT JOIN admins a ON p.received_by = a.id

WHERE MONTH(p.payment_date)=?
AND YEAR(p.payment_date)=?

";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii",$month,$year);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    fputcsv($output,$row);
}

fclose($output);


//////////////////////////////////////////
// 3️⃣ BATCH ANALYTICS EXPORT
//////////////////////////////////////////

$batches_file = $month_dir."/batches_analytics.csv";
$output = fopen($batches_file,"w");

if(!$output){

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

echo '

<div class="analytics-card">

<h2>⚠ Export Failed</h2>

<p>
One of the analytics files appears to be open in another program.
</p>

<p>
Please close the CSV file (for example in Excel) and refresh the page to update the export.
</p>

<a href="export_page.php" class="export-btn">
Return to Export Page
</a>

</div>

';

require_once "../includes/footer.php";
exit();

}

fputcsv($output,[

"Batch Name",
"Time Slot",
"Capacity",
"Students Enrolled",
"Occupancy %"

]);

$query = "

SELECT

b.batch_name,
b.time_slot,
b.capacity,
COUNT(s.id) AS students_enrolled,
ROUND((COUNT(s.id)/b.capacity)*100,2) AS occupancy_percentage

FROM batches b

LEFT JOIN students s ON s.batch_id = b.id

GROUP BY b.id

";

$result = $conn->query($query);

while($row = $result->fetch_assoc()){
    fputcsv($output,$row);
}

fclose($output);


//////////////////////////////////////////
// SUCCESS MESSAGE
//////////////////////////////////////////

require_once "../includes/auth.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>

.analytics-card{
background:#fff;
padding:30px;
border-radius:14px;
border:1px solid #E6DCD4;
max-width:700px;
}

.analytics-title{
font-size:24px;
margin-bottom:10px;
}

.analytics-sub{
color:#555;
margin-bottom:20px;
}

.file-list{
margin:20px 0;
}

.file-item{
background:#F7F4F1;
padding:12px;
border-radius:8px;
margin-bottom:10px;
display:flex;
justify-content:space-between;
align-items:center;
}

.download-btn{
background:#7A1E3A;
color:#fff;
padding:6px 12px;
border-radius:6px;
text-decoration:none;
font-size:14px;
}

.download-btn:hover{
background:#5d172c;
}

.export-btn{
background:#1E5631;
color:#fff;
padding:10px 20px;
border-radius:8px;
text-decoration:none;
display:inline-block;
margin-top:20px;
}

</style>

<h2>Analytics Export</h2>

<div class="analytics-card">

<div class="analytics-title">
📊 Export Completed Successfully
</div>

<div class="analytics-sub">
Year: <b><?php echo $year; ?></b><br>
Month: <b><?php echo $month_name; ?></b>
</div>

<div class="file-list">

<div class="file-item">
<span>students_analytics.csv</span>
<a class="download-btn"
href="<?php echo $students_file; ?>"
download>Download</a>
</div>

<div class="file-item">
<span>payments_analytics.csv</span>
<a class="download-btn"
href="<?php echo $payments_file; ?>"
download>Download</a>
</div>

<div class="file-item">
<span>batches_analytics.csv</span>
<a class="download-btn"
href="<?php echo $batches_file; ?>"
download>Download</a>
</div>

</div>

<p>
<strong>Storage Location</strong><br>
exports/<?php echo $year; ?>/<?php echo $month_name; ?>
</p>

<a href="export_page.php" class="export-btn">
Export Another Month
</a>

</div>

<?php require_once "../includes/footer.php"; ?>

?>