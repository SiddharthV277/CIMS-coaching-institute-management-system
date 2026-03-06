<?php
require_once "../includes/auth.php";
$conn = new mysqli("localhost", "root", "", "cims");

if (!isset($_GET['student_id'])) {
    header("Location: list.php");
    exit();
}

$student_id = intval($_GET['student_id']);

/* ================= FETCH STUDENT ================= */

$stmt = $conn->prepare("
    SELECT id, full_name, admission_no,
           total_fees, fees_paid, final_total,
           payment_structure, admission_date
    FROM students
    WHERE id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header("Location: list.php");
    exit();
}

$final_total = ($student['final_total'] > 0)
    ? $student['final_total']
    : $student['total_fees'];

$remaining = $final_total - $student['fees_paid'];

/* ================= GENERATE / REGENERATE PLAN ================= */

if (isset($_POST['generate_plan'])) {

    $total_installments = intval($_POST['total_installments']);

    if ($total_installments < 1) {
        die("Invalid installment count.");
    }

    $conn->begin_transaction();

    try {

        /* 1️⃣ Delete ONLY pending installments */
        $stmt = $conn->prepare("
            DELETE FROM fee_installments
            WHERE student_id = ? AND status = 'pending'
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        $paid_amount = $student['fees_paid'];
        $remaining = $final_total - $paid_amount;
        $admission_date = $student['admission_date'];

        /* 2️⃣ Ensure installment #1 exists only once */
        if ($paid_amount > 0) {

            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM fee_installments 
                WHERE student_id = ? AND installment_no = 1
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->bind_result($first_exists);
            $stmt->fetch();
            $stmt->close();

            if ($first_exists == 0) {
                $stmt = $conn->prepare("
                    INSERT INTO fee_installments
                    (student_id, installment_no, amount, due_date, status, fine_amount, created_at)
                    VALUES (?, 1, ?, ?, 'paid', 0, NOW())
                ");
                $stmt->bind_param("ids", $student_id, $paid_amount, $admission_date);
                $stmt->execute();
                $stmt->close();
            }
        }

        $start_index = ($paid_amount > 0) ? 2 : 1;
        $remaining_installments = $total_installments - ($paid_amount > 0 ? 1 : 0);

        /* 3️⃣ Generate new pending installments */
        if ($remaining_installments > 0 && $remaining > 0) {

            $amount_per_installment =
                floor(($remaining / $remaining_installments) * 100) / 100;

            $last_amount =
                $remaining - ($amount_per_installment * ($remaining_installments - 1));

            for ($i = $start_index; $i <= $total_installments; $i++) {

                $due_date = date(
                    'Y-m-d',
                    strtotime("+".($i-1)." month", strtotime($admission_date))
                );

                $amount_to_insert = ($i == $total_installments)
                    ? $last_amount
                    : $amount_per_installment;

                $stmt = $conn->prepare("
                    INSERT INTO fee_installments
                    (student_id, installment_no, amount, due_date, status, fine_amount, created_at)
                    VALUES (?, ?, ?, ?, 'pending', 0, NOW())
                ");
                $stmt->bind_param(
                    "iids",
                    $student_id,
                    $i,
                    $amount_to_insert,
                    $due_date
                );
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();

        header("Location: manage.php?student_id=".$student_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Regeneration failed.");
    }
}

/* ================= FETCH INSTALLMENTS ================= */

$stmt = $conn->prepare("
    SELECT *
    FROM fee_installments
    WHERE student_id=?
    ORDER BY installment_no ASC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$installments = $stmt->get_result();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.card {
    background:#fff;
    padding:30px;
    border-radius:18px;
    border:1px solid #E6DCD4;
    margin-bottom:30px;
    box-shadow:0 10px 25px rgba(0,0,0,0.05);
}

.summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:15px;
    margin-top:15px;
}

.summary-box {
    background:#F9F5F2;
    padding:18px;
    border-radius:12px;
    text-align:center;
}

.summary-box strong {
    display:block;
    font-size:13px;
    color:#777;
}

.summary-box span {
    font-size:18px;
    font-weight:700;
}

.table {
    width:100%;
    border-collapse:collapse;
}

.table th {
    background:#F3ECE6;
    padding:14px;
    text-align:left;
}

.table td {
    padding:14px;
    border-bottom:1px solid #E6DCD4;
}

.table tr:hover {
    background:#FAF7F5;
}

.btn {
    padding:10px 20px;
    border:none;
    border-radius:8px;
    background:#7A1E3A;
    color:#fff;
    font-weight:600;
    cursor:pointer;
}

.status-paid {
    color:#27AE60;
    font-weight:700;
}

.status-pending {
    color:#C0392B;
    font-weight:700;
}
</style>

<h2>Manage Fees</h2>

<div class="card">
    <h3><?php echo $student['full_name']; ?></h3>

    <div class="summary-grid">
        <div class="summary-box">
            <strong>Admission No</strong>
            <span><?php echo $student['admission_no']; ?></span>
        </div>
        <div class="summary-box">
            <strong>Structure</strong>
            <span><?php echo $student['payment_structure']; ?></span>
        </div>
        <div class="summary-box">
            <strong>Total</strong>
            <span>₹<?php echo number_format($final_total,2); ?></span>
        </div>
        <div class="summary-box">
            <strong>Paid</strong>
            <span>₹<?php echo number_format($student['fees_paid'],2); ?></span>
        </div>
        <div class="summary-box">
            <strong>Remaining</strong>
            <span>₹<?php echo number_format(max($remaining,0),2); ?></span>
        </div>
    </div>
</div>

<?php if ($remaining > 0): ?>
<div class="card">
<form method="POST">
<label><strong>Number of Installments</strong></label><br><br>
<input type="number" name="total_installments" min="1" max="24" required>
<button type="submit" name="generate_plan" class="btn">
Generate / Regenerate Plan
</button>
</form>
</div>
<?php endif; ?>

<?php if ($installments->num_rows > 0): ?>
<div class="card">
<table class="table">
<tr>
<th>#</th>
<th>Amount</th>
<th>Due Date</th>
<th>Status</th>
</tr>

<?php while($row = $installments->fetch_assoc()): ?>
<tr>
<td><?php echo $row['installment_no']; ?></td>
<td>₹<?php echo number_format($row['amount'],2); ?></td>
<td><?php echo $row['due_date']; ?></td>
<td class="<?php echo $row['status']=='paid'?'status-paid':'status-pending'; ?>">
<?php echo strtoupper($row['status']); ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>