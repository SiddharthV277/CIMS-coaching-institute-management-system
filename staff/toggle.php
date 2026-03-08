<?php
require_once "../includes/superadmin_only.php";

$conn = new mysqli("localhost", "root", "", "cims");

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    // Prevent disabling yourself
    if ($id == $_SESSION['admin_id']) {
        header("Location: list.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT status FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    if ($current_status === 'active') {
        $new_status = 'inactive';
    } else {
        $new_status = 'active';
    }

    $update = $conn->prepare("UPDATE admins SET status = ? WHERE id = ?");
    $update->bind_param("si", $new_status, $id);
    $update->execute();
}

header("Location: list.php");
exit();