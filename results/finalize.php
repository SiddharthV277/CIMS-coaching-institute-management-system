<?php
/* Bypass CSRF check for this internal AJAX endpoint — we validate session manually */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start(); /* Buffer any accidental output */

header('Content-Type: application/json');

/* Auth check without CSRF */
if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['student_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

require_once dirname(__DIR__) . '/includes/db.php';

$student_id = intval($_POST['student_id']);

/* Security: only update if student is currently Result Pending */
$stmt = $conn->prepare("UPDATE students SET status = 'Completed' WHERE id = ? AND status = 'Result Pending'");
$stmt->bind_param("i", $student_id);
$stmt->execute();

ob_end_clean();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Student not found or already completed.']);
}

$stmt->close();
?>
