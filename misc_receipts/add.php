<?php
require_once "../includes/auth.php";
require_once "../includes/superadmin_only.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once dirname(__DIR__) . '/includes/db.php';

$errors = [];
$success = '';

// Preserve submitted values so form re-populates on error
$old = [
    'receipt_no'   => '',
    'amount'       => '',
    'amount_type'  => 'other',
    'received_date'=> date('Y-m-d'),
    'student_name' => '',
    'reg_no'       => '',
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old['receipt_no']    = trim($_POST['receipt_no'] ?? '');
    $old['amount']        = trim(str_replace(',', '', $_POST['amount'] ?? ''));
    $old['amount_type']   = $_POST['amount_type'] ?? 'other';
    $old['received_date'] = $_POST['received_date'] ?? date('Y-m-d');
    $old['student_name']  = trim($_POST['student_name'] ?? '');
    $old['reg_no']        = trim($_POST['reg_no'] ?? '');

    // --- Field-specific validation ---
    if ($old['receipt_no'] === '') {
        $errors['receipt_no'] = "Receipt No. is required.";
    }

    // Allow 0; only reject if empty string or non-numeric
    if ($old['amount'] === '') {
        $errors['amount'] = "Amount is required.";
    } elseif (!is_numeric($old['amount'])) {
        $errors['amount'] = "Amount must be a valid number.";
    } elseif ((float)$old['amount'] < 0) {
        $errors['amount'] = "Amount cannot be negative.";
    }

    if (empty($old['received_date'])) {
        $errors['received_date'] = "Received Date is required.";
    } elseif ($old['received_date'] < '2026-03-01') {
        $errors['received_date'] = "Date cannot be before March 2026.";
    }

    if ($old['amount_type'] === 'fees') {
        if ($old['student_name'] === '') {
            $errors['student_name'] = "Student Name is required for Fees type.";
        }
        if ($old['reg_no'] === '') {
            $errors['reg_no'] = "Registration No. is required for Fees type.";
        }
    }

    // --- Save only when no errors ---
    if (empty($errors)) {
        $student_name = ($old['amount_type'] === 'fees') ? $old['student_name'] : null;
        $reg_no       = ($old['amount_type'] === 'fees') ? $old['reg_no']       : null;
        $amount       = (float)$old['amount'];

        $stmt = $conn->prepare("INSERT INTO misc_receipts (receipt_no, amount, amount_type, student_name, reg_no, received_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssss", $old['receipt_no'], $amount, $old['amount_type'], $student_name, $reg_no, $old['received_date']);

        if ($stmt->execute()) {
            $success = "Miscellaneous receipt added successfully!";
            // Clear form after success
            $old = [
                'receipt_no'   => '',
                'amount'       => '',
                'amount_type'  => 'other',
                'received_date'=> date('Y-m-d'),
                'student_name' => '',
                'reg_no'       => '',
            ];
        } else {
            $errors['db'] = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Helper: render inline field error
function fieldErr($errors, $key) {
    if (isset($errors[$key])) {
        echo '<span style="color:#dc2626;font-size:12px;margin-top:4px;display:block;">⚠ ' . htmlspecialchars($errors[$key]) . '</span>';
    }
}

// Helper: red border if field has error
function errBorder($errors, $key) {
    return isset($errors[$key]) ? 'border-color:#dc2626;' : '';
}
?>

<div class="header-action">
    <h2>Add Miscellaneous Receipt</h2>
    <a href="index.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card" style="max-width: 600px;">

    <?php if (!empty($success)): ?>
        <div style="background:#d1fae5; color:#065f46; padding:15px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errors['db'])): ?>
        <div style="background:#fef2f2; color:#991b1b; padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; border-left:4px solid #dc2626;">
            <?php echo htmlspecialchars($errors['db']); ?>
        </div>
    <?php elseif (!empty($errors)): ?>
        <div style="background:#fef2f2; color:#991b1b; padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:13px; border-left:4px solid #dc2626;">
            ⚠️ Please fix the highlighted fields below before saving.
        </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div class="form-row">
            <div class="form-group" style="flex:1;">
                <label>Receipt No. *</label>
                <input type="text" name="receipt_no" placeholder="e.g. REC/2026/001"
                    value="<?php echo htmlspecialchars($old['receipt_no']); ?>"
                    style="<?php echo errBorder($errors,'receipt_no'); ?>">
                <?php fieldErr($errors, 'receipt_no'); ?>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Amount (₹) *</label>
                <input type="number" step="0.01" min="0" name="amount" placeholder="0.00"
                    value="<?php echo htmlspecialchars($old['amount']); ?>"
                    style="<?php echo errBorder($errors,'amount'); ?>">
                <?php fieldErr($errors, 'amount'); ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex:1;">
                <label>Amount Type *</label>
                <select name="amount_type" id="amount_type">
                    <option value="other" <?php echo $old['amount_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    <option value="fees"  <?php echo $old['amount_type'] === 'fees'  ? 'selected' : ''; ?>>Fees</option>
                </select>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Received Date *</label>
                <input type="text" name="received_date" class="flatpickr-date" min="01-03-2026"
                    value="<?php echo $old['received_date'] ? date('d-m-Y', strtotime($old['received_date'])) : date('d-m-Y'); ?>"
                    style="<?php echo errBorder($errors,'received_date'); ?>">
                <?php fieldErr($errors, 'received_date'); ?>
            </div>
        </div>

        <div id="fees_details_section" style="display:none; background:rgba(13,148,136,0.05); border:1px solid rgba(13,148,136,0.2); padding:20px; border-radius:8px; margin-bottom:20px;">
            <p style="margin-top:0; margin-bottom:15px; font-weight:600; font-size:14px; color:#0D9488;">Student Details (For Fees)</p>
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>Student Name</label>
                    <input type="text" name="student_name" id="student_name" placeholder="Enter student name"
                        value="<?php echo htmlspecialchars($old['student_name']); ?>"
                        style="<?php echo errBorder($errors,'student_name'); ?>">
                    <?php fieldErr($errors, 'student_name'); ?>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Registration No.</label>
                    <input type="text" name="reg_no" id="reg_no" placeholder="e.g. MCO..."
                        value="<?php echo htmlspecialchars($old['reg_no']); ?>"
                        style="<?php echo errBorder($errors,'reg_no'); ?>">
                    <?php fieldErr($errors, 'reg_no'); ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn" style="width:100%;">Save Receipt</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const typeSelect     = document.getElementById("amount_type");
    const detailsSection = document.getElementById("fees_details_section");
    const nameInput      = document.getElementById("student_name");
    const regInput       = document.getElementById("reg_no");

    function toggleDetails() {
        if (typeSelect.value === 'fees') {
            detailsSection.style.display = 'block';
            nameInput.setAttribute("required", "required");
            regInput.setAttribute("required", "required");
        } else {
            detailsSection.style.display = 'none';
            nameInput.removeAttribute("required");
            regInput.removeAttribute("required");
        }
    }

    typeSelect.addEventListener("change", toggleDetails);
    toggleDetails(); // Restore correct state after a POST error
});
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.querySelectorAll('.flatpickr-date').forEach(function(el) {
    flatpickr(el, { dateFormat: 'd-m-Y', allowInput: true, minDate: '01-03-2026' });
});
document.querySelector('form').addEventListener('submit', function() {
    document.querySelectorAll('.flatpickr-date').forEach(function(el) {
        const parts = el.value.split('-');
        if (parts.length === 3 && parts[2].length === 4) {
            el.value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>
