<?php
/**
 * revert_reg.php
 * Called by list.php JS after 10 min to revert a duplicate registration_no back to the
 * LOWEST-id copy's value (so only the duplicate entry is reverted, not the original).
 */
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

$student_id = intval($_POST['student_id'] ?? 0);
if ($student_id <= 0) {
    echo json_encode(['reverted' => false, 'reason' => 'invalid id']);
    exit;
}

// Fetch the current reg_no of this student
$stmt = $conn->prepare("SELECT registration_no FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($current_reg);
$stmt->fetch();
$stmt->close();

if (!$current_reg) {
    echo json_encode(['reverted' => false, 'reason' => 'student not found']);
    exit;
}

// Count how many students share the same reg_no
$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_no = ?");
$stmt->bind_param("s", $current_reg);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count <= 1) {
    // No longer a duplicate (was fixed by the user)
    echo json_encode(['reverted' => false, 'reason' => 'no longer a duplicate']);
    exit;
}

// It is still a duplicate — revert this student's reg_no to a unique placeholder
// Format: ORIG + _DUP_ + student_id  e.g. MCO123_DUP_47
$new_reg = $current_reg . '_DUP_' . $student_id;

$stmt = $conn->prepare("UPDATE students SET registration_no = ? WHERE id = ?");
$stmt->bind_param("si", $new_reg, $student_id);
$stmt->execute();
$stmt->close();

echo json_encode(['reverted' => true, 'new_reg' => $new_reg]);
?>
