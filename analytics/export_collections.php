<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

$year = $_GET['year'] ?? date("Y");

$monthly_data = [];
for ($i = 1; $i <= 12; $i++) {
    $monthly_data[$i] = [
        'payments' => 0,
        'misc' => 0,
        'total' => 0
    ];
}

// Fetch payments grouped by month
$stmt_p = $conn->prepare("
    SELECT MONTH(payment_date) as m, SUM(amount) as s 
    FROM payments 
    WHERE YEAR(payment_date) = ? AND payment_date >= '2026-03-01'
    GROUP BY MONTH(payment_date)
");
$stmt_p->bind_param("i", $year);
$stmt_p->execute();
$res_p = $stmt_p->get_result();
while($row = $res_p->fetch_assoc()) {
    $monthly_data[$row['m']]['payments'] += $row['s'];
}
$stmt_p->close();

// Fetch misc receipts grouped by month
$stmt_m = $conn->prepare("
    SELECT MONTH(received_date) as m, SUM(amount) as s 
    FROM misc_receipts 
    WHERE YEAR(received_date) = ? AND received_date >= '2026-03-01'
    GROUP BY MONTH(received_date)
");
$stmt_m->bind_param("i", $year);
$stmt_m->execute();
$res_m = $stmt_m->get_result();
while($row = $res_m->fetch_assoc()) {
    $monthly_data[$row['m']]['misc'] += $row['s'];
}
$stmt_m->close();

// Download Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=collections_' . $year . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Month', 'Students Payments', 'Misc Receipts', 'Total Collection']);

foreach ($monthly_data as $m => $d) {
    if ($year == 2026 && $m < 3) continue;
    if ($d['payments'] == 0 && $d['misc'] == 0 && $m > date('n')) {
        continue;
    }
    $tot = $d['payments'] + $d['misc'];
    $month_name = date("F", mktime(0,0,0,$m,1));
    fputcsv($output, [$month_name, $d['payments'], $d['misc'], $tot]);
}

fclose($output);
?>
