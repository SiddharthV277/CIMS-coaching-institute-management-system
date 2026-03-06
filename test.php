<?php

$conn = new mysqli("localhost", "root", "", "cims");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

echo "Database Connected Successfully!";

?>
