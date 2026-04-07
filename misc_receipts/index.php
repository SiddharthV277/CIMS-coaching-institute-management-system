<?php
require_once "../includes/auth.php";
require_once "../includes/superadmin_only.php";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once dirname(__DIR__) . '/includes/db.php';

// Fetch receipts
$query = "SELECT * FROM misc_receipts ORDER BY received_date DESC, id DESC";
$result = $conn->query($query);
?>

<div class="header-action">
    <h2>Miscellaneous Receipts</h2>
    <a href="add.php" class="btn">Add New Receipt</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['receipt_no']); ?></td>
                            <td><?php echo date("d-m-Y", strtotime($row['received_date'])); ?></td>
                            <td>
                                <?php if ($row['amount_type'] === 'fees'): ?>
                                    <span style="background: rgba(13,148,136,0.1); color: #0D9488; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Fees</span>
                                <?php else: ?>
                                    <span style="background: rgba(124,58,237,0.1); color: #7C3AED; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Other</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold;">₹<?php echo number_format($row['amount'], 2); ?></td>
                            <td>
                                <?php if ($row['amount_type'] === 'fees'): ?>
                                    <div style="font-size: 13px;">
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                        <span style="color: var(--text-muted);"><?php echo htmlspecialchars($row['reg_no']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 13px;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No miscellaneous receipts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
