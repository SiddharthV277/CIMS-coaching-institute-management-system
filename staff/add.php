<?php
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {

        $check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username already exists.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            $stmt->execute();

            header("Location: list.php?success=added");
            exit();
        }
    }
}

/* NOW LOAD HTML */
require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="form-container">

    <h2>Add New Staff</h2>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
    <input type="password" name="password" id="addPassword" required>
    <span class="toggle-eye" onclick="togglePassword('addPassword', this)">👁</span>
</div>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="staff">Staff</option>
                <option value="superadmin">Superadmin</option>
            </select>
        </div>

        <button type="submit" class="submit-btn">Create Staff</button>

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