<?php
require_once "../includes/auth.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once dirname(__DIR__) . '/includes/db.php';

$year = $_GET['year'] ?? date("Y");
$view = $_GET['view'] ?? 'all'; // can be used for UI highlighting if we had multiple views

$monthly_data = [];
for ($i = 1; $i <= 12; $i++) {
    $monthly_data[$i] = [
        'payments' => 0,
        'misc' => 0,
        'total' => 0
    ];
}

// Fetch payments grouped by month
$stmt_p = $conn->prepare("
    SELECT MONTH(payment_date) as m, SUM(amount) as s 
    FROM payments 
    WHERE YEAR(payment_date) = ? AND payment_date >= '2026-03-01'
    GROUP BY MONTH(payment_date)
");
$stmt_p->bind_param("i", $year);
$stmt_p->execute();
$res_p = $stmt_p->get_result();
while($row = $res_p->fetch_assoc()) {
    $monthly_data[$row['m']]['payments'] += $row['s'];
}
$stmt_p->close();

// Fetch misc receipts grouped by month
$stmt_m = $conn->prepare("
    SELECT MONTH(received_date) as m, SUM(amount) as s 
    FROM misc_receipts 
    WHERE YEAR(received_date) = ? AND received_date >= '2026-03-01'
    GROUP BY MONTH(received_date)
");
$stmt_m->bind_param("i", $year);
$stmt_m->execute();
$res_m = $stmt_m->get_result();
while($row = $res_m->fetch_assoc()) {
    $monthly_data[$row['m']]['misc'] += $row['s'];
}
$stmt_m->close();

// Calculate totals
$grand_total = 0;
foreach ($monthly_data as $m => $data) {
    $tot = $data['payments'] + $data['misc'];
    $monthly_data[$m]['total'] = $tot;
    $grand_total += $tot;
}
?>

<div class="header-action" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2>Collection Analytics</h2>
    
    <div style="display:flex; gap:10px; align-items:center;">
        <form method="GET" style="display:flex; gap:10px;">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <select name="year" style="padding:8px 12px; border:1px solid var(--border-color); border-radius:var(--radius-sm); font-family:inherit;">
                <?php
                $currentY = date("Y");
                for ($y = $currentY; $y >= 2026; $y--) {
                    $sel = ($y == $year) ? 'selected' : '';
                    echo "<option value='$y' $sel>$y</option>";
                }
                ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        <form method="GET" action="export_collections.php">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <button type="submit" class="btn" style="background-color: #16A34A;">Export CSV</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-top:0; color:var(--text-main);">Year: <?php echo htmlspecialchars($year); ?></h3>
    <p style="color:var(--text-muted); margin-bottom:0;">
        <strong>Total Collected This Year:</strong> <span style="font-size:18px; color:var(--text-main); font-weight:bold;">₹<?php echo number_format($grand_total, 2); ?></span>
    </p>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Students' Payments</th>
                    <th>Misc Receipts</th>
                    <th>Total Collection</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($monthly_data as $m => $d): ?>
                    <?php if ($year == 2026 && $m < 3) continue; ?>
                    <?php if ($d['total'] > 0 || $m <= date('n')): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo date("F", mktime(0,0,0,$m,1)); ?></td>
                        <td>₹<?php echo number_format($d['payments'], 2); ?></td>
                        <td>₹<?php echo number_format($d['misc'], 2); ?></td>
                        <td style="font-weight:bold; color:var(--primary-color);">₹<?php echo number_format($d['total'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
