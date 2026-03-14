<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

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

        /* 2️⃣ Check how many paid installments exist */
        $stmt = $conn->prepare("
            SELECT COUNT(*), IFNULL(MAX(installment_no), 0)
            FROM fee_installments 
            WHERE student_id = ? AND status = 'paid'
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->bind_result($paid_count, $max_installment_paid);
        $stmt->fetch();
        $stmt->close();
        
        /* If no installments exist but they paid something, synthesize receipt 1 */
        if ($paid_amount > 0 && $paid_count == 0) {
            $stmt = $conn->prepare("
                INSERT INTO fee_installments
                (student_id, installment_no, amount, paid_amount, due_date, status, fine_amount, created_at)
                VALUES (?, 1, ?, ?, ?, 'paid', 0, NOW())
            ");
            $stmt->bind_param("idds", $student_id, $paid_amount, $paid_amount, $admission_date);
            $stmt->execute();
            $stmt->close();
            
            $max_installment_paid = 1;
            $paid_count = 1;
        }

        $remaining_installments = $total_installments - $paid_count;
        $start_index = $max_installment_paid + 1;

        /* 3️⃣ Generate new pending installments logically spaced */
        if ($remaining_installments > 0 && $remaining > 0) {

            $amount_per_installment =
                floor(($remaining / $remaining_installments) * 100) / 100;

            $last_amount =
                $remaining - ($amount_per_installment * ($remaining_installments - 1));

            for ($i = $start_index; $i <= $total_installments; $i++) {

                // Calculate due date based on index offset
                $month_offset = $i - 1; 
                $due_date = date(
                    'Y-m-d',
                    strtotime("+".$month_offset." month", strtotime($admission_date))
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
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
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

<?php 
$first_pending_found = false;
while($row = $installments->fetch_assoc()): 
    $is_overdue = ($row['status'] == 'pending' && strtotime($row['due_date']) < strtotime('today'));
?>
<tr style="<?php echo $is_overdue ? 'background-color: #ffeaea;' : ''; ?>">
<td><?php echo $row['installment_no']; ?></td>
<td>₹<?php echo number_format($row['amount'],2); ?></td>
<td style="<?php echo $is_overdue ? 'color: red; font-weight: bold;' : ''; ?>">
    <?php echo date('d-m-Y', strtotime($row['due_date'])); ?>
</td>
<td class="<?php echo $row['status']=='paid'?'status-paid':'status-pending'; ?>">
<?php 
if ($row['status'] == 'paid') {
    echo "PAID";
} else {
    echo "PENDING " . ($is_overdue ? " (OVERDUE)" : "");
    if (!$first_pending_found) {
        $first_pending_found = true;
        echo '<br><br><button type="button" onclick="openPayModal(' . $row['id'] . ', ' . $row['amount'] . ', \'' . $row['installment_no'] . '\')" style="background:#27AE60; color:#fff; border:none; padding:5px 10px; border-radius:4px; font-weight:bold; cursor:pointer;">Pay Now</button>';
    }
}
?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
<?php endif; ?>

<!-- Payment Verification Modal -->
<div id="payModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#fff; padding:30px; border-radius:12px; width:400px; text-align:center; position:relative;">
        <h3 style="margin-top:0; color:#333;">Process Installment Payment</h3>
        <p style="color:#666; font-size:14px; margin-bottom:20px;">Please enter your admin password to officially record this <strong id="modalDisplayAmount"></strong> payment for Installment #<span id="modalDisplayInstallmentNo"></span>.</p>
        
        <form action="process_installment_payment.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <input type="hidden" name="installment_id" id="modalInstallmentId" value="">
            <input type="hidden" name="payment_amount" id="modalPaymentAmount" value="">
            
            <input type="password" name="admin_password" placeholder="Admin Password" required style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #ccc; border-radius:6px; margin-bottom:20px;">
            
            <div style="display:flex; justify-content:space-between; gap:10px;">
                <button type="button" onclick="closePayModal()" style="padding:10px 20px; background:#ddd; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px; background:#27AE60; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayModal(id, amount, i_no) {
    document.getElementById('modalInstallmentId').value = id;
    document.getElementById('modalPaymentAmount').value = amount;
    document.getElementById('modalDisplayAmount').innerText = '₹' + parseFloat(amount).toFixed(2);
    document.getElementById('modalDisplayInstallmentNo').innerText = i_no;
    document.getElementById('payModal').style.display = 'flex';
}
function closePayModal() {
    document.getElementById('payModal').style.display = 'none';
}
</script>

<?php require_once "../includes/footer.php"; ?>
