<?php
require_once "../includes/auth.php";
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

/* Fetch batch */
$stmt = $conn->prepare("SELECT * FROM batches WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$batch = $result->fetch_assoc();

if (!$batch) {
    header("Location: list.php");
    exit();
}

/* Get current strength */
$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE batch_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($strength);
$stmt->fetch();
$stmt->close();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $time_slot = trim($_POST['time_slot']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    /* Validation */

    if ($capacity < $strength) {
        $error = "Capacity cannot be less than current strength ($strength students).";
    }

    if ($capacity > 20) {
        $error = "Maximum capacity allowed is 20.";
    }

    if (empty($time_slot)) {
        $error = "Time slot cannot be empty.";
    }

    if (empty($error)) {

        $stmt = $conn->prepare("
            UPDATE batches 
            SET time_slot=?, capacity=?, status=? 
            WHERE id=?
        ");

        $stmt->bind_param("sisi", $time_slot, $capacity, $status, $id);
        $stmt->execute();

        $success = "Batch updated successfully.";

        /* Refresh batch data */
        $stmt = $conn->prepare("SELECT * FROM batches WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $batch = $result->fetch_assoc();
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.section-card{
    background:#fff;
    padding:35px;
    border-radius:16px;
    border:1px solid #E6DCD4;
    box-shadow:0 20px 40px rgba(0,0,0,0.05);
    margin-bottom:30px;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:25px 30px;
}

.full-width{
    grid-column:1/-1;
}

input, select{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #D8CCC3;
}

.submit-btn{
    background:#7A1E3A;
    color:#fff;
    padding:12px 25px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-top:20px;
}

.info-box{
    background:#F9F3F6;
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
    font-size:14px;
}
</style>

<h2>Edit Batch <?php echo $batch['batch_name']; ?></h2>

<?php if(!empty($error)): ?>
<div style="color:#8B0000; margin-bottom:15px;">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if(!empty($success)): ?>
<div style="color:#1E5631; margin-bottom:15px;">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<div class="section-card">

<div class="info-box">
    <strong>Current Strength:</strong> <?php echo $strength; ?> students<br>
    <strong>Current Capacity:</strong> <?php echo $batch['capacity']; ?>
</div>

<form method="POST">

<div class="form-grid">

<div>
<label>Time Slot</label>
<input type="text" name="time_slot"
value="<?php echo htmlspecialchars($batch['time_slot']); ?>"
required>
</div>

<div>
<label>Capacity (Max 20)</label>
<input type="number" name="capacity"
value="<?php echo $batch['capacity']; ?>"
min="<?php echo $strength; ?>"
max="20"
required>
</div>

<div class="full-width">
<label>Status</label>
<select name="status">
<option value="Active" <?php if($batch['status']=="Active") echo "selected"; ?>>
Active
</option>
<option value="Inactive" <?php if($batch['status']=="Inactive") echo "selected"; ?>>
Inactive
</option>
</select>
</div>

</div>

<button type="submit" class="submit-btn">
Update Batch
</button>

</form>

</div>

<?php require_once "../includes/footer.php"; ?>