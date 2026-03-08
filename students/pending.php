<?php
require_once "../includes/auth.php";
$conn = new mysqli("localhost", "root", "", "cims");

$result = $conn->query("
    SELECT *
    FROM admission_requests
    ORDER BY created_at DESC
");

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="main-content">

    <div class="card">

        <div class="card-header">
            <h2>Admission Requests</h2>
        </div>

        <div class="card-body">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Batch</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Duplicate</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>

                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                        <td><?php echo htmlspecialchars($row['batch']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>

                        <td>
                            <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($row['duplicate_flag'] === 'Might Be Duplicate'): ?>
                                <span class="warning-badge">
                                    ⚠ Might Be Duplicate
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-small">
                                View
                            </a>
                        </td>

                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>