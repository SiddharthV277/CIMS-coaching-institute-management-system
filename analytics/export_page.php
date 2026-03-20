<?php
require_once "../includes/auth.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

require_once dirname(__DIR__) . '/includes/db.php';

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


<div class="analytics-container">

<div class="analytics-card">

<div class="analytics-title">
📊 Monthly Analytics Export
</div>

<form method="GET">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

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

<div class="table-responsive">
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
</div>

<form action="export_process.php" method="POST" id="exportForm">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
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

