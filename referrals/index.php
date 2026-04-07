<?php
require_once "../includes/auth.php";

if (!in_array($_SESSION['role'], ['admin', 'superadmin', 'faculty'])) {
    header("Location: ../dashboard.php");
    exit();
}

require_once dirname(__DIR__) . '/includes/db.php';

$success = "";
$error = "";

/* ================= MANUAL ADD REFERRAL ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_referral'])) {
    
    $referrer_type = $_POST['referrer_type'];
    $account_id = null;
    $ref_name = "";
    $ref_phone = "";
    
    $client_name = trim($_POST['referred_student_name']);
    $client_phone = trim($_POST['referred_student_phone']);
    $admin_name = $_SESSION['username'] ?? 'Admin';

    if ($referrer_type === 'existing') {
        $account_id = intval($_POST['referrer_account_id']);
    } else {
        $ref_name = trim($_POST['new_referrer_name']);
        $ref_phone = trim($_POST['new_referrer_phone']);
        
        // Check if name/phone already exists in referral_accounts
        $stmt = $conn->prepare("SELECT id FROM referral_accounts WHERE referrer_name = ? AND referrer_phone = ?");
        $stmt->bind_param("ss", $ref_name, $ref_phone);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $account_id = $res->fetch_assoc()['id'];
        } else {
            // Create new
            $stmt = $conn->prepare("INSERT INTO referral_accounts (referrer_name, referrer_phone) VALUES (?, ?)");
            $stmt->bind_param("ss", $ref_name, $ref_phone);
            if ($stmt->execute()) {
                $account_id = $stmt->insert_id;
            } else {
                $error = "Error creating referrer account.";
            }
        }
    }

    if ($account_id && empty($error)) {
        $conn->begin_transaction();
        try {
            // Add to referred_students
            $stmt = $conn->prepare("INSERT INTO referred_students (referral_account_id, referred_student_name, referred_student_phone, added_by_admin) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $account_id, $client_name, $client_phone, $admin_name);
            $stmt->execute();

            // Increase points
            $stmt = $conn->prepare("UPDATE referral_accounts SET total_points = total_points + 1, remaining_points = remaining_points + 1 WHERE id = ?");
            $stmt->bind_param("i", $account_id);
            $stmt->execute();

            $conn->commit();
            $success = "Referral successfully added!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to add referral: " . $e->getMessage();
        }
    }
}

/* ================= REDEEM POINTS ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['redeem_points'])) {
    $account_id = intval($_POST['redeem_account_id']);
    $points_to_redeem = intval($_POST['points_amount']);
    $redeem_type = $_POST['redeem_type'];
    $admin_name = $_SESSION['username'] ?? 'Admin';

    // Verify points
    $stmt = $conn->prepare("SELECT remaining_points FROM referral_accounts WHERE id = ?");
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($points_to_redeem > 0 && $points_to_redeem <= $row['remaining_points']) {
            $conn->begin_transaction();
            try {
                // Deduct points
                $stmt = $conn->prepare("UPDATE referral_accounts SET remaining_points = remaining_points - ?, redeemed_points = redeemed_points + ? WHERE id = ?");
                $stmt->bind_param("iii", $points_to_redeem, $points_to_redeem, $account_id);
                $stmt->execute();

                // Log history
                $stmt = $conn->prepare("INSERT INTO referral_redeem_history (referral_account_id, redeem_type, points_used, admin_name) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isis", $account_id, $redeem_type, $points_to_redeem, $admin_name);
                $stmt->execute();

                $conn->commit();
                $success = "Successfully redeemed $points_to_redeem points!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to redeem: " . $e->getMessage();
            }
        } else {
            $error = "Invalid points amount.";
        }
    }
}

/* ================= FETCH DATA ================= */
// Fetch all existing referrers for the dropdown
$referrers_result = $conn->query("SELECT id, referrer_name, referrer_phone FROM referral_accounts ORDER BY referrer_name ASC");
$referrers = [];
while ($row = $referrers_result->fetch_assoc()) {
    $referrers[] = $row;
}

// Fetch pending count directly from students table
$pending_res = $conn->query("
    SELECT COUNT(*) as cnt 
    FROM students s
    LEFT JOIN referred_students rs ON s.id = rs.admitted_student_id
    WHERE s.referred_student_name IS NOT NULL 
      AND s.referred_student_name != ''
      AND rs.id IS NULL
");
$pending_count = $pending_res ? $pending_res->fetch_assoc()['cnt'] : 0;

// Fetch Accounts List
$accounts_res = $conn->query("SELECT * FROM referral_accounts ORDER BY total_points DESC, created_at DESC");

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.btn-premium-action {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #fff;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.btn-pending {
    background: linear-gradient(135deg, #e67e22, #d35400);
}
.btn-pending:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(230,126,34,0.3);
}
.btn-add {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
}
.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(39,174,96,0.3);
}
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
.btn-view {
    background: #3498db;
}
.btn-view:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52,152,219,0.3);
}
.btn-redeem {
    background: #9b59b6;
}
.btn-redeem:hover {
    background: #8e44ad;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(155,89,182,0.3);
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
        <h2>Referred Students</h2>
        <div style="display:flex; gap: 20px;">
            <a href="pending.php" class="btn-premium-action btn-pending" style="position: relative;">
                Pending Referrals
                <?php if ($pending_count > 0): ?>
                    <span style="position:absolute; top:-8px; right:-10px; background:#c0392b; color:white; border-radius:50%; min-width:24px; height:24px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.2); border: 2px solid #fff; padding:0 4px;"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
            <button onclick="openAddModal()" class="btn-premium-action btn-add">+ Add Referral</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Referrer Name</th>
                        <th>Phone</th>
                        <th>Total Points</th>
                        <th>Redeemed</th>
                        <th>Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($accounts_res->num_rows > 0): ?>
                        <?php while($row = $accounts_res->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['referrer_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['referrer_phone']); ?></td>
                            <td><span style="background:#E8F8F5; color:#1ABC9C; padding:4px 8px; border-radius:4px; font-weight:bold;"><?php echo $row['total_points']; ?></span></td>
                            <td><span style="background:#FDEDEC; color:#E74C3C; padding:4px 8px; border-radius:4px; font-weight:bold;"><?php echo $row['redeemed_points']; ?></span></td>
                            <td><span style="background:#FEF9E7; color:#F1C40F; padding:4px 8px; border-radius:4px; font-weight:bold;"><?php echo $row['remaining_points']; ?></span></td>
                            <td>
                                <a href="view.php?id=<?php echo $row['id']; ?>" class="btn-table-action btn-view">View</a>
                                <?php if($row['remaining_points'] > 0): ?>
                                    <button onclick="openRedeemModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['referrer_name']); ?>', <?php echo $row['remaining_points']; ?>)" class="btn-table-action btn-redeem">Redeem</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px;">No referral accounts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>

<!-- ADD REFERRAL MODAL -->
<div id="addReferralModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="background:#fff; width:90%; max-width:500px; margin: 10% auto; padding: 25px; border-radius:12px; position:relative;">
        <span onclick="closeAddModal()" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:24px;">&times;</span>
        <h3 style="margin-top:0;">Add Manual Referral</h3>
        
        <form method="POST" style="margin-top:20px;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="add_referral" value="1">
            
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Referrer Type</label>
                <select name="referrer_type" id="referrerType" onchange="toggleReferrerFields()" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
                    <option value="existing">Existing Referrer Account</option>
                    <option value="new">New Referrer</option>
                </select>
            </div>

            <div id="existingReferrerField" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Select Referrer</label>
                <select name="referrer_account_id" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
                    <option value="">-- Select Referrer --</option>
                    <?php foreach($referrers as $ref): ?>
                        <option value="<?php echo $ref['id']; ?>"><?php echo htmlspecialchars($ref['referrer_name']); ?> (<?php echo htmlspecialchars($ref['referrer_phone']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="newReferrerFields" style="display:none; margin-bottom:15px; border:1px solid #eee; padding: 15px; border-radius:6px; background:#fafafa;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">New Referrer Details</label>
                <input type="text" name="new_referrer_name" placeholder="Referrer Name" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; margin-bottom:10px;">
                <input type="text" name="new_referrer_phone" placeholder="Referrer Phone" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
            </div>

            <hr style="border:0; height:1px; background:#ddd; margin:20px 0;">

            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Referred Student Details</label>
                <input type="text" name="referred_student_name" placeholder="Name" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; margin-bottom:10px;">
                <input type="text" name="referred_student_phone" placeholder="Phone (Optional)" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
            </div>

            <button type="submit" class="btn-primary" style="width:100%; background:#27AE60; font-size:16px;">Save Referral</button>
        </form>
    </div>
</div>

<!-- REDEEM POINTS MODAL -->
<div id="redeemModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="background:#fff; width:90%; max-width:400px; margin: 10% auto; padding: 25px; border-radius:12px; position:relative;">
        <span onclick="closeRedeemModal()" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:24px;">&times;</span>
        <h3 style="margin-top:0;">Redeem Points</h3>
        
        <form method="POST" style="margin-top:20px;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="redeem_points" value="1">
            <input type="hidden" name="redeem_account_id" id="redeemAccountId">
            
            <p><strong>Referrer:</strong> <span id="redeemReferrerName"></span></p>
            <p><strong>Available Points:</strong> <span id="redeemAvailablePoints" style="color:#27AE60; font-weight:bold;"></span></p>

            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Redeem Method</label>
                <select name="redeem_type" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
                    <option value="Cashback">Cashback</option>
                    <option value="Discount (New Student Admission)">Discount (New Student Admission)</option>
                    <option value="Discount (Course Upgrade)">Discount (Course Upgrade)</option>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Points to Redeem</label>
                <input type="number" name="points_amount" id="redeemPointsInput" min="1" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">
            </div>

            <button type="submit" class="btn-primary" style="width:100%; background:#8E44AD; font-size:16px;">Confirm Redemption</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addReferralModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addReferralModal').style.display = 'none';
}

function toggleReferrerFields() {
    var type = document.getElementById('referrerType').value;
    if (type === 'new') {
        document.getElementById('existingReferrerField').style.display = 'none';
        document.getElementById('newReferrerFields').style.display = 'block';
    } else {
        document.getElementById('existingReferrerField').style.display = 'block';
        document.getElementById('newReferrerFields').style.display = 'none';
    }
}

function openRedeemModal(id, title, maxPoints) {
    document.getElementById('redeemAccountId').value = id;
    document.getElementById('redeemReferrerName').innerText = title;
    document.getElementById('redeemAvailablePoints').innerText = maxPoints;
    
    var input = document.getElementById('redeemPointsInput');
    input.max = maxPoints;
    input.value = 1;
    
    document.getElementById('redeemModal').style.display = 'block';
}

function closeRedeemModal() {
    document.getElementById('redeemModal').style.display = 'none';
}
</script>

<?php require_once "../includes/footer.php"; ?>
