<?php

$year  = intval($_GET['year']);
$month = intval($_GET['month']);

$month_name = date("F", mktime(0,0,0,$month,1));

$path = "../exports/".$year."/".$month_name;

echo json_encode([
    "exists" => file_exists($path)
]);

?>