<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list.php");
    exit();
}

$student_id = intval($_POST['student_id']);
$verify_password = $_POST['verify_password'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 0;

/* Fetch admin hashed password */
$stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

if (!password_verify($verify_password, $hashed_password)) {
    header("Location: list.php?error=invalid_password");
    exit();
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE students SET status = 'Completed' WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Error updating student status.");
    }
    $conn->commit();
    header("Location: list.php?success=completed");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    die("Error marking as completed: " . htmlspecialchars($e->getMessage()));
}
?>
