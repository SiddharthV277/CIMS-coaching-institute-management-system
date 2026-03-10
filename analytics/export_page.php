<?php
require_once "../includes/auth.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

$conn = new mysqli("localhost","root","","cims");

$month = $_GET['month'] ?? date("n");
$year  = $_GET['year']  ?? date("Y");

$data_available = false;
$preview = [];

if(isset($_GET['month']) && isset($_GET['year'])){

$query = "
SELECT
s.admission_no,
s.full_name,
s.course,
b.batch_name,
s.admission_date,
s.final_total,
s.fees_paid,
(s.final_total - s.fees_paid) AS remaining,
s.payment_structure,
s.heard_about

FROM students s
LEFT JOIN batches b ON s.batch_id = b.id

WHERE MONTH(s.admission_date)=?
AND YEAR(s.admission_date)=?
LIMIT 10
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii",$month,$year);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $preview[] = $row;
}

if(count($preview) > 0){
$data_available = true;
}

}

?>

<style>

.analytics-container{
max-width:1100px;
}

.analytics-card{
background:#fff;
padding:30px;
border-radius:14px;
border:1px solid #E6DCD4;
margin-bottom:25px;
}

.analytics-title{
font-size:22px;
margin-bottom:20px;
}

.form-row{
display:flex;
gap:20px;
align-items:center;
flex-wrap:wrap;
}

select,input{
padding:10px;
border-radius:8px;
border:1px solid #D8CCC3;
}

.preview-table{
width:100%;
border-collapse:collapse;
margin-top:20px;
}

.preview-table th{
background:#7A1E3A;
color:#fff;
padding:10px;
font-size:14px;
}

.preview-table td{
border:1px solid #eee;
padding:8px;
font-size:13px;
}

.export-btn{
background:#1E5631;
color:#fff;
padding:12px 22px;
border-radius:8px;
border:none;
cursor:pointer;
margin-top:15px;
}

.no-data{
padding:20px;
background:#fff3cd;
border-radius:8px;
margin-top:15px;
}

</style>

<div class="analytics-container">

<div class="analytics-card">

<div class="analytics-title">
📊 Monthly Analytics Export
</div>

<form method="GET">

<div class="form-row">

<label>Month</label>

<select name="month">

<?php
for($m=1;$m<=12;$m++){
$selected = ($m==$month) ? "selected":"";
echo "<option value='$m' $selected>".date("F",mktime(0,0,0,$m,1))."</option>";
}
?>

</select>

<label>Year</label>

<input type="number" name="year" value="<?php echo $year; ?>">

<button type="submit" class="export-btn">
Preview Data
</button>

</div>

</form>

</div>

<?php if(isset($_GET['month'])): ?>

<div class="analytics-card">

<div class="analytics-title">
📋 Data Preview (First 10 Records)
</div>

<?php if($data_available): ?>

<table class="preview-table">

<tr>
<th>Admission No</th>
<th>Name</th>
<th>Course</th>
<th>Batch</th>
<th>Admission Date</th>
<th>Total Fees</th>
<th>Fees Paid</th>
<th>Remaining</th>
<th>Payment Structure</th>
<th>Source</th>
</tr>

<?php foreach($preview as $row): ?>

<tr>

<td><?php echo $row['admission_no']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['course']; ?></td>
<td><?php echo $row['batch_name']; ?></td>
<td><?php echo $row['admission_date']; ?></td>
<td><?php echo $row['final_total']; ?></td>
<td><?php echo $row['fees_paid']; ?></td>
<td><?php echo $row['remaining']; ?></td>
<td><?php echo $row['payment_structure']; ?></td>
<td><?php echo $row['heard_about']; ?></td>

</tr>

<?php endforeach; ?>

</table>

<form action="export_process.php" method="POST" id="exportForm">
<input type="hidden" name="overwrite" id="overwriteField" value="0">    

<input type="hidden" name="month" value="<?php echo $month; ?>">
<input type="hidden" name="year" value="<?php echo $year; ?>">

<button class="export-btn">
Export Full Dataset
</button>

</form>

<?php else: ?>

<div class="no-data">
No admissions found for this month.
</div>

<?php endif; ?>

</div>

<?php endif; ?>

</div>

<script>

document.getElementById("exportForm").addEventListener("submit", function(e){

    const year  = document.querySelector("[name='year']").value;
    const month = document.querySelector("[name='month']").value;

    fetch("check_export.php?year="+year+"&month="+month)
    .then(res => res.json())
    .then(data => {

        if(data.exists){

            let choice = confirm(
                "Analytics data already exists for this month.\n\nPress OK to REPLACE it.\nPress Cancel to stop."
            );

            if(choice){
                document.getElementById("overwriteField").value = 1;
                document.getElementById("exportForm").submit();
            }

        }else{

            document.getElementById("exportForm").submit();

        }

    });

    e.preventDefault();

});

</script>

<?php require_once "../includes/footer.php"; ?>

