<?php
require_once "../includes/auth.php";
require_once "../includes/superadmin_only.php";

require_once dirname(__DIR__) . '/includes/db.php';

/* Fetch batches with strength count */
$result = $conn->query("
    SELECT b.*,
    (SELECT COUNT(*) FROM students s WHERE s.batch_id = b.id) AS strength
    FROM batches b
    ORDER BY b.batch_name ASC
");

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>



<div class="page-header">
    <h2>Batch Management</h2>
</div>

<table class="batch-table">
    <tr>
        <th>Batch</th>
        <th>Time Slot</th>
        <th>Strength</th>
        <th>Capacity</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

<?php while($batch = $result->fetch_assoc()): 

    $strength = $batch['strength'];
    $capacity = $batch['capacity'];
    $is_full = $strength >= $capacity;

?>

<tr>
    <td><strong>Batch <?php echo $batch['batch_name']; ?></strong></td>

    <td><?php echo $batch['time_slot']; ?></td>

    <td>
        <?php echo $strength; ?>
    </td>

    <td>
        <span class="capacity-badge <?php echo $is_full ? 'capacity-full' : 'capacity-ok'; ?>">
            <?php echo $strength . " / " . $capacity; ?>
        </span>
    </td>

    <td>
        <span class="<?php echo $batch['status']=='Active' ? 'status-active' : 'status-inactive'; ?>">
            <?php echo $batch['status']; ?>
        </span>
    </td>

    <td style="display:flex; gap:8px;">

   <a href="view.php?id=<?php echo $batch['id']; ?>" 
   class="action-btn view-btn">
    View
</a>

    <a href="edit.php?id=<?php echo $batch['id']; ?>" 
       class="action-btn">
        Edit
    </a>

</td>
</tr>

<?php endwhile; ?>

</table>

<?php require_once "../includes/footer.php"; ?>