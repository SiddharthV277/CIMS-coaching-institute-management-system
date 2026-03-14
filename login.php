<?php
session_start();
$conn = new mysqli("localhost", "root", "", "cims");

$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed. Please refresh.");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, password, role, status FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->bind_result($id, $hashed_password, $role, $status);
        $stmt->fetch();

        /* 👇 THIS IS WHERE YOUR CODE GOES 👇 */

        if ($status !== 'active') {
            $error = "Account is inactive.";
        }
        elseif (password_verify($password, $hashed_password)) {

            $_SESSION['admin_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            header("Location: dashboard.php");
            exit();
        }
        else {
            $error = "Invalid password.";
        }

    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vigyaan Admin Portal</title>

<style>

body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #F4EFEA;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-container {
    background: #FFFFFF;
    width: 400px;
    padding: 45px 40px;
    border-radius: 14px;
    box-shadow: 0 20px 45px rgba(60,40,30,0.08);
    text-align: center;
    border: 1px solid #E6DCD4;
}

.logo-image {
    width: 140px;
    margin-bottom: 15px;
}

.logo-text {
    font-size: 30px;
    font-weight: 800;
    letter-spacing: 3px;
    margin-bottom: 5px;
    color: #3B2F2F;
}

.logo-sub {
    font-size: 13px;
    letter-spacing: 2px;
    color: #7A1E3A;
    margin-bottom: 28px;
}

h2 {
    margin-bottom: 25px;
    font-weight: 600;
    color: #3B2F2F;
}

.input-group {
    margin-bottom: 18px;
    text-align: left;
}

label {
    font-size: 13px;
    color: #6A5A55;
}

input {
    width: 100%;
    padding: 11px;
    margin-top: 6px;
    border-radius: 6px;
    border: 1px solid #D8CCC3;
    font-size: 14px;
    transition: 0.3s ease;
}

input:focus {
    border-color: #7A1E3A;
    outline: none;
    box-shadow: 0 0 0 2px rgba(122,30,58,0.15);
}

button {
    width: 100%;
    padding: 12px;
    background-color: #7A1E3A;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s ease;
    margin-top: 5px;
}

button:hover {
    background-color: #64182F;
}

.error-message {
    background: #FCE8E6;
    color: #B00020;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 13px;
}

.footer-text {
    margin-top: 22px;
    font-size: 12px;
    color: #8C7C75;
}

</style>
</head>

<body>

<div class="login-container">

    <img src="assets/images/vigyaan-logo.png" alt="Vigyaan Logo" class="logo-image">

    <div class="logo-text">VIGYAAN</div>
    <div class="logo-sub">ADMIN PORTAL</div>

    <h2>Staff Login</h2>

    <?php if (!empty($error)) : ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <div class="password-wrapper">
    <input type="password" name="password" id="loginPassword" required>
    <span class="toggle-eye" onclick="togglePassword('loginPassword', this)">👁</span>
</div>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="footer-text">
        © 2026 Vigyaan Institute
    </div>

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
</body>
</html>