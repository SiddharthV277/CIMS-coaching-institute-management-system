<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: passed.php");
    exit();
}

$id = intval($_GET['id']);
$error = "";

// Fetch the student
$stmt = $conn->prepare("SELECT full_name, registration_no, status, course, batch, total_fees, fees_paid FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student || $student['status'] !== 'Completed') {
    die("Student not found or not eligible for upgrade.");
}

$courses = [];
$res_c = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
while ($row = $res_c->fetch_assoc()) {
    $courses[] = $row;
}

$batches = [];
$res = $conn->query("SELECT id, batch_name, time_slot FROM batches WHERE status='Active' ORDER BY batch_name ASC");
while ($row = $res->fetch_assoc()) {
    $batches[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Add CSRF check if we are keeping strict
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Session expired. Please refresh.";
    } else {
        $new_batch_id = intval($_POST['batch_id']);
        $new_course = $_POST['course_name'];
        $new_course_duration = trim($_POST['course_duration']);
        $new_total_fees = floatval($_POST['total_fees']);
        $new_payment_structure = trim($_POST['payment_structure']);
        
        $discount_amount = floatval($_POST['discount_amount'] ?? 0);
        $discount_percent = floatval($_POST['discount_percent'] ?? 0);
        
        $regFee = isset($_POST['include_reg_fee']) ? 550 : 0;
        $examFee = isset($_POST['include_exam_fee']) ? 670 : 0;
        
        $base_amount = $new_total_fees - 550 - 670;
        if($base_amount < 0) $base_amount = 0;
        
        if ($discount_amount > 0) {
            $discount_percent = ($discount_amount / $base_amount) * 100;
        } elseif ($discount_percent > 0) {
            $discount_amount = ($base_amount * $discount_percent) / 100;
        }
        
        $amount_after_discount = $base_amount - $discount_amount;
        if($amount_after_discount < 0) $amount_after_discount = 0;
        
        $final_total_value = $amount_after_discount + $regFee + $examFee;
        $discount_type = ($discount_amount > 0) ? "Amount" : "Percent";
        
        // Find batch name 
        $b_name = "";
        foreach ($batches as $b) {
            if ($b['id'] == $new_batch_id) {
                $b_name = $b['batch_name'];
                break;
            }
        }
        
        if (empty($b_name)) {
            $error = "Invalid batch selected.";
        } elseif (empty($new_course)) {
            $error = "Course selection is required.";
        } else {
            // Begin upgrade
            $conn->begin_transaction();
            try {
                // We clear the old fee installments
                $conn->query("DELETE FROM fee_installments WHERE student_id = $id");
                
                // Keep the old payments? Or delete? Usually payments are kept in accounting, 
                // but if we are resetting "fees_paid" to 0, history might look weird.
                // For simplicity, let's keep the past payments in the payments table, 
                // but reset the student's current tracking fields.
                
                $stmt_upg = $conn->prepare("
                    UPDATE students 
                    SET 
                        batch_id = ?, 
                        batch = ?, 
                        course = ?, 
                        course_duration = ?, 
                        total_fees = ?, 
                        discount_type = ?,
                        discount_percent = ?,
                        discount_amount = ?,
                        final_total = ?, 
                        fees_paid = 0, 
                        payment_structure = ?, 
                        status = 'Active' 
                    WHERE id = ?
                ");
                $stmt_upg->bind_param("issssddddsi", $new_batch_id, $b_name, $new_course, $new_course_duration, $new_total_fees, $discount_type, $discount_percent, $discount_amount, $final_total_value, $new_payment_structure, $id);
                
                if (!$stmt_upg->execute()) {
                    throw new Exception("Error updating student record.");
                }
                
                $conn->commit();
                
                header("Location: view.php?id=" . $id . "&success=upgraded");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="table-container">
    <div class="table-header">
        <h2>Upgrade Plan: <?php echo htmlspecialchars($student['full_name'] ?? ''); ?></h2>
        <a href="passed.php" class="btn btn-secondary">Cancel</a>
    </div>

    <?php if ($error): ?>
        <div style="color:red; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="section-card">
        <h3>Current Record Info</h3>
        <p><strong>Reg. No:</strong> <?php echo htmlspecialchars($student['registration_no'] ?? ''); ?></p>
        <p><strong>Previous Course:</strong> <?php echo htmlspecialchars($student['course'] ?? ''); ?> (<?php echo htmlspecialchars($student['batch'] ?? ''); ?>)</p>
        <p style="color:red; font-size:0.9em;">Note: Upgrading will wipe out their old unpaid fee installments and reset their total fees tracking to 0, placing them back in the Active students roster.</p>
    </div>

    <form method="POST" class="section-card">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        
        <div class="form-grid">
            <div class="input-group">
                <label>New Course Selection</label>
                <select name="course_name" id="courseSelect" required>
                    <option value="">-- Select New Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['course_name']); ?>"
                                data-duration="<?php echo htmlspecialchars($c['duration_months']); ?>"
                                data-fee="<?php echo htmlspecialchars($c['fees']); ?>">
                            <?php echo htmlspecialchars($c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-group">
                <label>New Batch Assignment</label>
                <select name="batch_id" required>
                    <option value="">-- Select New Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['id']; ?>">
                            <?php echo htmlspecialchars('Batch ' . $b['batch_name'] . ' (' . $b['time_slot'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-group">
                <label>New Course Duration</label>
                <input type="text" name="course_duration" id="durationField" placeholder="e.g. 6 Months" required>
            </div>
            
            <div class="input-group">
                <label>Total Course Fees</label>
                <input type="number" step="0.01" name="total_fees" id="totalFeesField" required>
                <div style="margin-top: 10px; display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size: 13px; font-weight:normal; cursor:pointer;">
                        <input type="checkbox" name="include_reg_fee" id="includeRegFee" value="1" checked onchange="calculate()"> Include Registration Fee (₹550)
                    </label>
                    <label style="font-size: 13px; font-weight:normal; cursor:pointer;">
                        <input type="checkbox" name="include_exam_fee" id="includeExamFee" value="1" checked onchange="calculate()"> Include Examination Fee (₹670)
                    </label>
                </div>
            </div>
            
            <div class="input-group">
                <label>Discount Amount (₹)</label>
                <input type="number" step="0.01" name="discount_amount" id="discountAmount">
            </div>

            <div class="input-group">
                <label>Discount Percentage (%)</label>
                <input type="number" step="0.01" name="discount_percent" id="discountPercent">
            </div>

            <div class="input-group">
                <label>Amount After Discount</label>
                <input type="number" id="amountAfterDiscount" readonly>
            </div>

            <div class="input-group">
                <label>Final Payable Total (+ Reg & Exam)</label>
                <input type="number" id="finalTotalDisplay" readonly>
            </div>
            
            <div class="input-group">
                <label>Payment Structure</label>
                <select name="payment_structure">
                    <option value="Lumpsum">Lumpsum (One-time)</option>
                    <option value="Installments">Installments</option>
                </select>
            </div>
        </div>
        
    <br>
        <button type="submit" class="add-btn btn-primary">Process Upgrade</button>
    </form>
</div>

<script>
const REG = 550;
const EXAM = 670;

let originalTotal = 0;

function calculate(trigger = null) {
    let base = originalTotal - REG - EXAM;
    if (base < 0) base = 0;

    let discountAmountInput = document.getElementById("discountAmount");
    let discountPercentInput = document.getElementById("discountPercent");

    let discountAmt = parseFloat(discountAmountInput.value) || 0;
    let discountPct = parseFloat(discountPercentInput.value) || 0;

    if (trigger === "amount") {
        discountPct = base > 0 ? (discountAmt / base) * 100 : 0;
        discountPercentInput.value = discountPct.toFixed(2);
    }

    if (trigger === "percent") {
        discountAmt = (base * discountPct) / 100;
        discountAmountInput.value = discountAmt.toFixed(2);
    }

    let includeReg = document.getElementById("includeRegFee").checked ? REG : 0;
    let includeExam = document.getElementById("includeExamFee").checked ? EXAM : 0;

    if (discountAmt > base) {
        discountAmt = base;
        discountAmountInput.value = base.toFixed(2);
    }

    let finalTotal = (base - discountAmt) + includeReg + includeExam;

    document.getElementById("amountAfterDiscount").value = (base - discountAmt).toFixed(2);
    document.getElementById("finalTotalDisplay").value = finalTotal.toFixed(2);
}

document.getElementById('courseSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('durationField').value = selected.getAttribute('data-duration') ? selected.getAttribute('data-duration') + ' Months' : '';
    originalTotal = parseFloat(selected.getAttribute('data-fee')) || 0;
    document.getElementById('totalFeesField').value = originalTotal;
    calculate();
});

document.getElementById('totalFeesField').addEventListener('input', function() {
    originalTotal = parseFloat(this.value) || 0;
    calculate();
});

document.getElementById("discountAmount").addEventListener("input", function(){
    calculate("amount");
});

document.getElementById("discountPercent").addEventListener("input", function(){
    calculate("percent");
});
</script>

<?php require_once "../includes/footer.php"; ?>
