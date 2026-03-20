<?php
require_once "../includes/auth.php";

if (!in_array($_SESSION['role'], ['admin', 'superadmin', 'staff'])) {
    header("Location: ../dashboard.php");
    exit();
}

require_once dirname(__DIR__) . '/includes/db.php';

$success = "";
$error = "";

/* ================= APPROVE REFERRAL DIRECT ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve_referral_direct'])) {
    
    $pending_id = intval($_POST['pending_id']); // This is the admitted student ID
    $admin_name = $_SESSION['username'] ?? 'Admin';

    // Fetch the admitted student record to get the claimed referrer's details
    $stmt = $conn->prepare("SELECT id as admitted_student_id, full_name as admitted_student_name, phone as admitted_student_phone, referred_student_name as claimed_referrer_name, referred_student_phone as claimed_referrer_phone FROM students WHERE id = ?");
    $stmt->bind_param("i", $pending_id);
    $stmt->execute();
    $pending_record = $stmt->get_result()->fetch_assoc();

    if ($pending_record && !empty($pending_record['claimed_referrer_name'])) {
        $conn->begin_transaction();
        try {
            $ref_name = trim($pending_record['claimed_referrer_name']);
            $ref_phone = trim($pending_record['claimed_referrer_phone'] ?? '');

            // Check if referrer already has a referral account by name and phone
            $stmt = $conn->prepare("SELECT id FROM referral_accounts WHERE referrer_name = ? AND (referrer_phone = ? OR ? = '')");
            $stmt->bind_param("sss", $ref_name, $ref_phone, $ref_phone);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $account_id = $row['id'];
            } else {
                // Create the newly discovered referrer account
                $stmt = $conn->prepare("INSERT INTO referral_accounts (referrer_name, referrer_phone) VALUES (?, ?)");
                $stmt->bind_param("ss", $ref_name, $ref_phone);
                $stmt->execute();
                $account_id = $stmt->insert_id;
            }

            // 1. Add to referred_students
            $stmt = $conn->prepare("INSERT INTO referred_students (referral_account_id, referred_student_name, referred_student_phone, admitted_student_id, added_by_admin) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issis", $account_id, $pending_record['admitted_student_name'], $pending_record['admitted_student_phone'], $pending_record['admitted_student_id'], $admin_name);
            $stmt->execute();

            // 2. Increase points
            $stmt = $conn->prepare("UPDATE referral_accounts SET total_points = total_points + 1, remaining_points = remaining_points + 1 WHERE id = ?");
            $stmt->bind_param("i", $account_id);
            $stmt->execute();

            $conn->commit();
            $success = "Referral approved. 1 point assigned to " . htmlspecialchars($ref_name) . "!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Approval failed: " . $e->getMessage();
        }
    } else {
        $error = "Invalid request or pending record not found.";
    }
}

/* ================= REJECT REFERRAL ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reject_referral'])) {
    $pending_id = intval($_POST['pending_id']);
    
    // Clear the referrer name in students table so it doesn't show in pending anymore
    $stmt = $conn->prepare("UPDATE students SET referred_student_name = '' WHERE id = ?");
    $stmt->bind_param("i", $pending_id);
    if($stmt->execute()){
         $success = "Referral request rejected.";
    } else {
         $error = "Failed to reject referral.";
    }
}

// Fetch all Pending Referrals directly from students table
$pending_res = $conn->query("
    SELECT s.id as pending_id, s.id as admitted_student_id, s.full_name as admitted_student_name, s.phone as admitted_student_phone, 
           s.referred_student_name as claimed_referrer_name, s.referred_student_phone as claimed_referrer_phone, 
           s.admission_date as created_at
    FROM students s
    LEFT JOIN referred_students rs ON s.id = rs.admitted_student_id
    WHERE s.referred_student_name IS NOT NULL 
      AND s.referred_student_name != ''
      AND rs.id IS NULL
    ORDER BY s.id DESC
");



require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.btn-table-action {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 13px;
    color: #fff;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s ease;
    margin: 2px;
}
.btn-approve {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
}
.btn-approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(39,174,96,0.3);
}
.btn-reject {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
}
.btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(192,57,43,0.3);
}
.btn-back {
    background: #ffffff;
    color: #2c3e50;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    border: 1px solid #dcdde1;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
}
.btn-back:hover {
    background: #f8f9fa;
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    transform: translateY(-2px);
    color: #2980b9;
    border-color: #3498db;
}
</style>

<div class="main-content">
    
    <?php if($success): ?>
        <div class="alert alert-success" style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger" style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="header-banner" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h2>Pending Referrals</h2>
        <a href="index.php" class="btn-back"><span style="margin-right:8px; font-size:18px;">&larr;</span> Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Newly Admitted Student</th>
                        <th>Claimed Referrer Name & Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_res && $pending_res->num_rows > 0): ?>
                        <?php while($row = $pending_res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date("d-M-Y", strtotime($row['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['admitted_student_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['admitted_student_phone'] ?? ''); ?></small>
                            </td>
                            <td>
                                <strong style="color:#C0392B;"><?php echo htmlspecialchars($row['claimed_referrer_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['claimed_referrer_phone'] ?? ''); ?></small>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this referral and assign a point to <?php echo addslashes($row['claimed_referrer_name']); ?>?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="approve_referral_direct" value="1">
                                    <input type="hidden" name="pending_id" value="<?php echo $row['pending_id']; ?>">
                                    <button type="submit" class="btn-table-action btn-approve">Approve</button>
                                </form>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this referral claim?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="reject_referral" value="1">
                                    <input type="hidden" name="pending_id" value="<?php echo $row['pending_id']; ?>">
                                    <button type="submit" class="btn-table-action btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 20px;">No pending referrals.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>



<?php require_once "../includes/footer.php"; ?>
