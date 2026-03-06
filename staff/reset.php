<?php
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

/* FETCH USER */
$stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

if (!$username) {
    header("Location: list.php");
    exit();
}

$error = "";

/* PROCESS FORM BEFORE ANY HTML */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_password = trim($_POST['password']);

    if (empty($new_password)) {
        $error = "Password cannot be empty.";
    } else {

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $id);
        $update->execute();

        header("Location: list.php?success=reset");
        exit();
    }
}

/* LOAD LAYOUT AFTER PROCESSING */
require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="form-container">

    <h2>Reset Password</h2>
    <p>Username: <strong><?php echo htmlspecialchars($username); ?></strong></p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>New Password</label>

            <div class="password-wrapper">
                <input type="password" name="password" id="resetPassword" required>
                <span class="toggle-eye" onclick="togglePassword('resetPassword', this)">👁</span>
            </div>

        </div>

        <button type="submit" class="submit-btn">Update Password</button>

    </form>

</div>

<script>
function togglePassword(fieldId, element) {
    const field = document.getElementById(fieldId);

    if (field.type === "password") {
        field.type = "text";
        element.textContent = "⌣";
    } else {
        field.type = "password";
        element.textContent = "👁";
    }
}
</script>

<?php require_once "../includes/footer.php"; ?>