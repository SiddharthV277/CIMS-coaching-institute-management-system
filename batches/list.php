<?php
require_once "../includes/auth.php";
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

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

<style>
.page-header{
    margin-bottom:25px;
}

.batch-table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,0.05);
}

.batch-table th{
    background:#7A1E3A;
    color:#fff;
    padding:14px;
    text-align:left;
    font-weight:500;
}

.batch-table td{
    padding:14px;
    border-bottom:1px solid #E6DCD4;
    font-size:14px;
}

.batch-table tr:hover{
    background:#F9F3F6;
}

.capacity-badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
}

.capacity-ok{
    background:#E8F8F0;
    color:#1E5631;
}

.capacity-full{
    background:#FDECEA;
    color:#8B0000;
}

.status-active{
    color:#1E5631;
    font-weight:600;
}

.status-inactive{
    color:#8B0000;
    font-weight:600;
}

.action-btn{
    background:#7A1E3A;
    color:#fff;
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
    font-size:13px;
}
.view-btn{
    background:#1E5631;
}
</style>

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