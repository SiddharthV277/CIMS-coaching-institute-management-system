<?php
require_once "../includes/auth.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

require_once dirname(__DIR__) . '/includes/db.php';
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM referral_accounts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

if (!$account) {
    echo "Referrer not found.";
    exit();
}

$ref_stmt = $conn->prepare("SELECT * FROM referred_students WHERE referral_account_id = ? ORDER BY added_date DESC");
$ref_stmt->bind_param("i", $id);
$ref_stmt->execute();
$referred_result = $ref_stmt->get_result();

$hist_stmt = $conn->prepare("SELECT * FROM referral_redeem_history WHERE referral_account_id = ? ORDER BY date DESC");
$hist_stmt->bind_param("i", $id);
$hist_stmt->execute();
$history_result = $hist_stmt->get_result();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
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

    <div class="header-banner" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h2>Referral Details: <?php echo htmlspecialchars($account['referrer_name']); ?></h2>
        <a href="index.php" class="btn-back"><span style="margin-right:8px; font-size:18px;">&larr;</span> Back to Referrals</a>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom:20px;">
        <!-- Points Summary -->
        <div class="card" style="padding:20px; background:#fdfefe;">
            <h3 style="margin-top:0; color:#2c3e50;">Points Summary</h3>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($account['referrer_phone'] ?? 'N/A'); ?></p>
            <p><strong>Total Earned:</strong> <span style="color:#27AE60; font-weight:bold;"><?php echo $account['total_points']; ?></span></p>
            <p><strong>Redeemed:</strong> <span style="color:#E74C3C; font-weight:bold;"><?php echo $account['redeemed_points']; ?></span></p>
            <p><strong>Remaining Balance:</strong> <span style="color:#F1C40F; font-size:18px; font-weight:bold;"><?php echo $account['remaining_points']; ?></span></p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header" style="background:#f8f9fa; padding:15px; border-bottom:1px solid #ddd;">
            <h3 style="margin:0;">List of Referred Students</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Student Phone</th>
                        <th>Added By</th>
                        <th>Points Awarded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($referred_result->num_rows > 0): ?>
                        <?php while($user = $referred_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date("d-M-Y H:i", strtotime($user['added_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['referred_student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['referred_student_phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['added_by_admin']); ?></td>
                            <td><span style="color:#27AE60; font-weight:bold;">+1</span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 20px;">No students referred yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="background:#f8f9fa; padding:15px; border-bottom:1px solid #ddd;">
            <h3 style="margin:0;">Redemption History</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Redemption Type</th>
                        <th>Points Used</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_result->num_rows > 0): ?>
                        <?php while($hist = $history_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date("d-M-Y H:i", strtotime($hist['date'])); ?></td>
                            <td><?php echo htmlspecialchars($hist['redeem_type']); ?></td>
                            <td><span style="color:#E74C3C; font-weight:bold;">-<?php echo $hist['points_used']; ?></span></td>
                            <td><?php echo htmlspecialchars($hist['admin_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 20px;">No points redeemed yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
