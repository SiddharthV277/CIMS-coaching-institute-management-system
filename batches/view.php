<?php
require_once "../includes/auth.php";
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$batch_id = intval($_GET['id']);

/* Fetch batch */
$stmt = $conn->prepare("SELECT * FROM batches WHERE id=?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$result = $stmt->get_result();
$batch = $result->fetch_assoc();

if (!$batch) {
    header("Location: list.php");
    exit();
}

/* Current strength */
$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE batch_id=?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$stmt->bind_result($strength);
$stmt->fetch();
$stmt->close();

/* Move student logic */
$error = "";

if (isset($_POST['move_student'])) {

    $student_id = intval($_POST['student_id']);
    $new_batch_id = intval($_POST['new_batch_id']);

    if ($new_batch_id == $batch_id) {
        $error = "Student is already in this batch.";
    } else {

        /* Check capacity of new batch */
        $stmt = $conn->prepare("SELECT capacity FROM batches WHERE id=?");
        $stmt->bind_param("i", $new_batch_id);
        $stmt->execute();
        $stmt->bind_result($new_capacity);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE batch_id=?");
        $stmt->bind_param("i", $new_batch_id);
        $stmt->execute();
        $stmt->bind_result($new_strength);
        $stmt->fetch();
        $stmt->close();

        if ($new_strength >= $new_capacity) {
            $error = "Target batch is full.";
        } else {

            $stmt = $conn->prepare("UPDATE students SET batch_id=? WHERE id=?");
            $stmt->bind_param("ii", $new_batch_id, $student_id);
            $stmt->execute();

            header("Location: view.php?id=".$batch_id);
            exit();
        }
    }
}

/* Fetch students in this batch */
$stmt = $conn->prepare("
    SELECT id, full_name, admission_no, phone, status
    FROM students
    WHERE batch_id=?
    ORDER BY full_name ASC
");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$students = $stmt->get_result();

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

.student-table{
    width:100%;
    border-collapse:collapse;
}

.student-table th,
.student-table td{
    padding:10px;
    border-bottom:1px solid #E6DCD4;
    font-size:14px;
}

.student-table th{
    background:#F9F3F6;
}

.move-btn{
    background:#7A1E3A;
    color:#fff;
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
</style>

<h2>Batch <?php echo $batch['batch_name']; ?></h2>

<div class="section-card">
<strong>Time Slot:</strong> <?php echo $batch['time_slot']; ?><br>
<strong>Strength:</strong> <?php echo $strength; ?> / <?php echo $batch['capacity']; ?><br>
<strong>Status:</strong> <?php echo $batch['status']; ?>
</div>

<?php if(!empty($error)): ?>
<div style="color:red; margin-bottom:15px;">
<?php echo $error; ?>
</div>
<?php endif; ?>

<div class="section-card">

<h3>Students in this Batch</h3>

<?php if($students->num_rows == 0): ?>
<p>No students assigned yet.</p>
<?php else: ?>

<table class="student-table">
<tr>
    <th>Admission No</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Status</th>
    <th>Move</th>
</tr>

<?php while($student = $students->fetch_assoc()): ?>
<tr>
    <td><?php echo $student['admission_no']; ?></td>
    <td><?php echo $student['full_name']; ?></td>
    <td><?php echo $student['phone']; ?></td>
    <td><?php echo $student['status']; ?></td>
    <td>

        <form method="POST" style="display:flex; gap:5px;">
            <input type="hidden" name="student_id"
                   value="<?php echo $student['id']; ?>">

            <select name="new_batch_id" required>

                <?php
                $all_batches = $conn->query("SELECT * FROM batches WHERE status='Active'");
                while($b = $all_batches->fetch_assoc()):
                ?>
                <option value="<?php echo $b['id']; ?>">
                    Batch <?php echo $b['batch_name']; ?>
                </option>
                <?php endwhile; ?>

            </select>

            <button type="submit" name="move_student" class="move-btn">
                Move
            </button>
        </form>

    </td>
</tr>
<?php endwhile; ?>

</table>

<?php endif; ?>

</div>

<?php require_once "../includes/footer.php"; ?>