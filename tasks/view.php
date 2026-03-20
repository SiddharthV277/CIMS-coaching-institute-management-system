<?php
require_once "../includes/auth.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$target_id = intval($_GET['id']);
$is_superadmin = ($_SESSION['role'] === 'superadmin');

require_once dirname(__DIR__) . '/includes/db.php';

$success = "";
$error = "";

// Fetch the target user details
$stmt = $conn->prepare("SELECT username, role FROM admins WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$res = $stmt->get_result();
$target_user = $res->fetch_assoc();

if (!$target_user) {
    die("User not found.");
}

// ================= ADD TASK (SUPERADMIN ONLY) =================
if ($is_superadmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_task'])) {
    $heading = trim($_POST['heading']);
    $description = trim($_POST['description']);
    $assigned_by = $_SESSION['admin_id'];

    if (!empty($heading)) {
        $stmt = $conn->prepare("INSERT INTO tasks (assigned_to, assigned_by, heading, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $target_id, $assigned_by, $heading, $description);
        if ($stmt->execute()) {
            $success = "Task assigned successfully to " . htmlspecialchars($target_user['username']) . ".";
        }
        else {
            $error = "Failed to assign task.";
        }
    }
    else {
        $error = "Task heading is required.";
    }
}

// ================= MARK COMPLETE (SUPERADMIN ONLY) =================
if ($is_superadmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_complete'])) {
    $task_id = intval($_POST['task_id']);
    $entered_pwd = $_POST['superadmin_password'];
    $superadmin_id = $_SESSION['admin_id'];

    // Verify Password
    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $superadmin_id);
    $stmt->execute();
    $pwd_res = $stmt->get_result()->fetch_assoc();

    if ($pwd_res && password_verify($entered_pwd, $pwd_res['password'])) {
        // Correct Password -> Mark Complete
        $stmt = $conn->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        if ($stmt->execute()) {
            $success = "Task successfully marked as completed.";
        }
        else {
            $error = "Failed to update task status.";
        }
    }
    else {
        $error = "Authentication failed. Incorrect password.";
    }
}

// Fetch Pending Tasks
$pending_stmt = $conn->prepare("SELECT t.*, a.username as assigner_name FROM tasks t LEFT JOIN admins a ON t.assigned_by = a.id WHERE t.assigned_to = ? AND t.status = 'pending' ORDER BY t.created_at DESC");
$pending_stmt->bind_param("i", $target_id);
$pending_stmt->execute();
$pending_tasks = $pending_stmt->get_result();

// Fetch Completed Tasks
$completed_stmt = $conn->prepare("SELECT t.*, a.username as assigner_name FROM tasks t LEFT JOIN admins a ON t.assigned_by = a.id WHERE t.assigned_to = ? AND t.status = 'completed' ORDER BY t.completed_at DESC");
$completed_stmt->bind_param("i", $target_id);
$completed_stmt->execute();
$completed_tasks = $completed_stmt->get_result();

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="main-content">
    
    <?php if ($success): ?>
        <div class="alert alert-success" style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo $success; ?>
        </div>
    <?php
endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo $error; ?>
        </div>
    <?php
endif; ?>

    <div class="header-banner" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h2>Task Board: <span style="color:#27ae60;"><?php echo htmlspecialchars($target_user['username']); ?></span></h2>
        <a href="index.php" class="btn-secondary" style="background:#ffffff; color:#2c3e50; padding:10px 20px; text-decoration:none; border-radius:8px; font-weight:600; box-shadow:0 2px 5px rgba(0,0,0,0.08); border:1px solid #dcdde1;">&larr; Back to Users</a>
    </div>

    <?php if ($is_superadmin): ?>
    <div class="card" style="margin-bottom: 30px; border-left: 4px solid #3498db;">
        <div class="card-body">
            <h3 style="margin-top:0; color:#2c3e50;">Assign New Task</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="add_task" value="1">
                
                <div style="display:flex; gap:15px; align-items:flex-start;">
                    <div style="flex:1;">
                        <input type="text" name="heading" placeholder="Task Heading" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; margin-bottom:10px;">
                        <textarea name="description" placeholder="Task Description (Optional)" rows="2" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; resize:vertical;"></textarea>
                    </div>
                    <button type="submit" style="background:linear-gradient(135deg, #3498db, #2980b9); color:white; padding:12px 25px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; font-size:15px;">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
    <?php
endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- PENDING TASKS -->
        <div class="card">
            <div class="card-header" style="background:#fff3cd; padding:15px; border-bottom:1px solid #ffeeba;">
                <h3 style="margin:0; color:#856404; display:flex; align-items:center; gap:10px;">
                    <span style="background:#856404; color:white; border-radius:50%; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; font-size:12px;"><?php echo $pending_tasks->num_rows; ?></span>
                    Pending Tasks
                </h3>
            </div>
            <div class="card-body" style="padding:15px;">
                <?php if ($pending_tasks->num_rows > 0): ?>
                    <?php while ($task = $pending_tasks->fetch_assoc()): ?>
                    <div style="background:#fdfdfd; border:1px solid #eee; padding:15px; border-radius:8px; margin-bottom:15px; position:relative; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                        <div style="margin-bottom:10px;">
                            <h4 style="margin:0; color:#2c3e50; font-size:16px;"><?php echo htmlspecialchars($task['heading']); ?></h4>
                            <small style="color:#7f8c8d;">Assigned by: <strong><?php echo htmlspecialchars($task['assigner_name']); ?></strong> on <?php echo date("M d, g:i A", strtotime($task['created_at'])); ?></small>
                        </div>
                        <?php if (!empty($task['description'])): ?>
                            <p style="font-size:14px; color:#555; background:#f9f9f9; padding:8px 12px; border-radius:4px; border-left:3px solid #ccc; margin:0 0 15px 0;"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                        <?php
        else: ?>
                            <div style="margin-bottom: 15px;"></div>
                        <?php
        endif; ?>
                        
                        <?php if ($is_superadmin): ?>
                            <button onclick="openVerifyModal(<?php echo $task['id']; ?>, '<?php echo addslashes($task['heading']); ?>')" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; font-size:13px; font-weight:bold; display:flex; align-items:center; gap:5px;"><span style="font-size:16px;">✓</span> Mark Complete</button>
                        <?php
        endif; ?>
                    </div>
                    <?php
    endwhile; ?>
                <?php
else: ?>
                    <p style="text-align:center; color:#999; padding:20px;">No pending tasks. Awesome!</p>
                <?php
endif; ?>
            </div>
        </div>

        <!-- COMPLETED TASKS -->
        <div class="card">
            <div class="card-header" style="background:#d4edda; padding:15px; border-bottom:1px solid #c3e6cb;">
                <h3 style="margin:0; color:#155724; display:flex; align-items:center; gap:10px;">
                    <span style="background:#155724; color:white; border-radius:50%; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; font-size:12px;"><?php echo $completed_tasks->num_rows; ?></span>
                    Completed Tasks
                </h3>
            </div>
            <div class="card-body" style="padding:15px;">
                <?php if ($completed_tasks->num_rows > 0): ?>
                    <?php while ($task = $completed_tasks->fetch_assoc()): ?>
                    <div style="background:#fcfcfc; border:1px solid #e2e3e5; padding:15px; border-radius:8px; margin-bottom:15px; opacity:0.8;">
                        <h4 style="margin:0; color:#7f8c8d; font-size:16px; text-decoration:line-through;"><?php echo htmlspecialchars($task['heading']); ?></h4>
                        <div style="font-size:12px; color:#95a5a6; margin-top:5px;">
                            Assigned by: <?php echo htmlspecialchars($task['assigner_name']); ?><br>
                            Completed on: <strong style="color:#27ae60;"><?php echo date("M d, Y - g:i A", strtotime($task['completed_at'])); ?></strong>
                        </div>
                    </div>
                    <?php
    endwhile; ?>
                <?php
else: ?>
                    <p style="text-align:center; color:#999; padding:20px;">No completed tasks yet.</p>
                <?php
endif; ?>
            </div>
        </div>

    </div>
</div>

<?php if ($is_superadmin): ?>
<!-- VERIFICATION MODAL -->
<div id="verifyModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000;">
    <div style="background:#fff; width:90%; max-width:400px; margin: 15% auto; padding: 30px; border-radius:12px; position:relative; text-align:center;">
        <span onclick="closeVerifyModal()" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:24px; color:#999;">&times;</span>
        
        <div style="width:60px; height:60px; background:#fdedec; color:#e74c3c; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; margin:0 auto 15px;">
            🔒
        </div>
        
        <h3 style="margin-top:0; color:#2c3e50;">Security Verification</h3>
        <p style="color:#7f8c8d; font-size:14px; margin-bottom:20px;">To mark "<strong id="modalTaskName" style="color:#333;"></strong>" as complete, please verify your identity by entering your superadmin password.</p>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="mark_complete" value="1">
            <input type="hidden" name="task_id" id="modalTaskId">
            
            <input type="password" name="superadmin_password" placeholder="Enter your password" required style="width:100%; padding:12px; border-radius:6px; border:1px solid #ccc; font-size:15px; margin-bottom:20px; text-align:center; letter-spacing:2px;">
            
            <button type="submit" style="width:100%; background:linear-gradient(135deg, #27ae60, #2ecc71); color:white; padding:12px; border:none; border-radius:6px; font-weight:bold; font-size:16px; cursor:pointer; box-shadow:0 4px 6px rgba(39,174,96,0.3);">Verify & Complete</button>
        </form>
    </div>
</div>

<script>
function openVerifyModal(taskId, taskName) {
    document.getElementById('modalTaskId').value = taskId;
    document.getElementById('modalTaskName').innerText = taskName;
    document.getElementById('verifyModal').style.display = 'block';
}

function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}
</script>
<?php
endif; ?>

<?php require_once "../includes/footer.php"; ?>
