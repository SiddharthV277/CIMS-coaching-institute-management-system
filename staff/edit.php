<?php
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

/* Fetch user */
$stmt = $conn->prepare("SELECT username, role FROM admins WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username, $current_role);
$stmt->fetch();
$stmt->close();

if (!$username) {
    header("Location: list.php");
    exit();
}

$error = "";

/* Process form BEFORE HTML */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_role = $_POST['role'];

    /* Prevent self role change */
    if ($id == $_SESSION['admin_id']) {
        $error = "You cannot change your own role.";
    } else {

        $update = $conn->prepare("UPDATE admins SET role = ? WHERE id = ?");
        $update->bind_param("si", $new_role, $id);
        $update->execute();

        header("Location: list.php?success=role_updated");
        exit();
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="form-container">

    <h2>Edit Staff Role</h2>
    <p>Username: <strong><?php echo htmlspecialchars($username); ?></strong></p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Select Role</label>
            <select name="role">
                <option value="staff" 
                    <?php if ($current_role === 'staff') echo "selected"; ?>>
                    Staff
                </option>

                <option value="superadmin"
                    <?php if ($current_role === 'superadmin') echo "selected"; ?>>
                    Superadmin
                </option>
            </select>
        </div>

        <button type="submit" class="submit-btn">Update Role</button>

    </form>

</div>

<?php require_once "../includes/footer.php"; ?>