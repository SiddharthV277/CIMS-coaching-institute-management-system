<?php
require_once __DIR__ . "/auth.php";

if ($_SESSION['role'] !== 'superadmin') {
    header("Location: /cims/no_permission.php");
    exit();
}
?>