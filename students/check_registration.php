<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

$reg_no = trim($_GET['reg_no'] ?? '');
$exclude_id = intval($_GET['exclude_id'] ?? 0);

if ($reg_no === '') {
    echo json_encode(['exists' => false]);
    exit();
}

if ($exclude_id > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_no = ? AND id != ?");
    $stmt->bind_param("si", $reg_no, $exclude_id);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_no = ?");
    $stmt->bind_param("s", $reg_no);
}

$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode(['exists' => $count > 0]);
