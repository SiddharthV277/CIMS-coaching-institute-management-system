<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_GET['id'])) {
    die("Invalid Student ID.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT s.*, b.batch_name, b.time_slot
    FROM students s
    LEFT JOIN batches b ON s.batch_id = b.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

/* Fetch Payment History */
$payments = [];
$pstmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY id DESC");
$pstmt->bind_param("i", $id);
$pstmt->execute();
$presult = $pstmt->get_result();
while($row = $presult->fetch_assoc()){
    $payments[] = $row;
}

$final_total = $student['final_total'] > 0 ? $student['final_total'] : $student['total_fees'];
$total_paid = $student['fees_paid'];
$due = $final_total - $total_paid;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px; /* Even smaller font */
            line-height: 1.2;
        }

        .print-container {
            max-width: 100%;
            margin: 0 auto;
            border: none;
            padding: 0;
            border-radius: 0;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #7A1E3A;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header-logo {
            flex: 1;
        }

        .header-logo img {
            width: 120px;
        }

        .header-text {
            flex: 3;
            text-align: center;
        }

        .header-text h1 {
            color: #7A1E3A;
            margin: 0 0 2px 0;
            font-size: 18px;
        }

        .header-text p {
            margin: 0;
            color: #555;
            font-size: 11px;
        }

        .photo-box {
            width: 80px;
            height: 100px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f9f9f9;
            flex-shrink: 0;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-box span {
            color: #aaa;
            font-size: 10px;
        }

        h3 {
            background: #f4f4f4;
            padding: 4px 8px;
            margin: 10px 0 6px 0;
            font-size: 12px;
            color: #444;
            border-left: 3px solid #7A1E3A;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns to save space */
            gap: 6px 15px;
            margin-bottom: 5px;
        }

        .info-item {
            font-size: 11px;
        }

        .info-item strong {
            display: inline-block;
            width: 90px;
            color: #555;
            font-size: 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background: #f9f9f9;
            color: #555;
            font-weight: 600;
        }

        .fee-summary {
            display: flex;
            justify-content: space-around;
            background: #fafafa;
            padding: 6px 10px;
            border-radius: 4px;
            margin-top: 5px;
            border: 1px solid #eee;
        }

        .fee-item {
            text-align: center;
        }

        .fee-item strong {
            display: block;
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .fee-val {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .fee-due { color: #C0392B; }
        .fee-paid { color: #27AE60; }

        .footer-note {
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px dashed #ccc;
            padding-top: 8px;
        }

        @media print {
            body { 
                font-size: 10pt; /* Smaller point size */
            }
            @page { 
                margin: 0.4cm; /* Tightest possible margin */
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="print-container">

    <div class="header">
        <div class="header-logo">
            <img src="/cims/assets/images/vigyaan-logo.png" alt="Vigyaan Logo">
        </div>
        <div class="header-text">
            <h1>Vigyaan Coaching Institute</h1>
            <p>Student Profile & Admission Record</p>
            <p style="margin-top:10px;">
                <strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?><br>
                <?php if (!empty($student['registration_no'])): ?>
                <strong>Registration No:</strong> <?php echo htmlspecialchars($student['registration_no']); ?><br>
                <?php endif; ?>
                <strong>Date (DD-MM-YYYY):</strong> <?php echo date('d-m-Y', strtotime($student['admission_date'])); ?>
            </p>
        </div>
        <div class="photo-box">
            <?php if (!empty($student['photo'])): ?>
                <img src="/cims/uploads/students/<?php echo $student['photo']; ?>" alt="Student Photo">
            <?php else: ?>
                <span>No Photo</span>
            <?php endif; ?>
        </div>
    </div>

    <h3>Basic Information</h3>
    <div class="info-grid">
        <div class="info-item">
            <strong>Full Name:</strong>
            <?php echo htmlspecialchars($student['full_name']); ?>
        </div>
        <div class="info-item">
            <strong>Course / Batch:</strong>
            <?php echo htmlspecialchars($student['course']); ?> 
            <?php if($student['batch_name']) echo " (Batch ".$student['batch_name'].")"; ?>
        </div>
        <div class="info-item">
            <strong>Date of Birth (DD-MM-YYYY):</strong>
            <?php echo date('d-m-Y', strtotime($student['dob'])); ?>
        </div>
        <div class="info-item">
            <strong>Gender:</strong>
            <?php echo htmlspecialchars($student['gender']); ?>
        </div>
        <div class="info-item">
            <strong>Phone:</strong>
            <?php echo htmlspecialchars($student['phone']); ?>
        </div>
        <div class="info-item">
            <strong>Email:</strong>
            <?php echo htmlspecialchars($student['email']); ?>
        </div>
    </div>

    <h3>Guardian / Address Details</h3>
    <div class="info-grid">
        <div class="info-item">
            <strong>Father's Name:</strong>
            <?php echo htmlspecialchars($student['father_name']); ?>
        </div>
        <div class="info-item">
            <strong>Mother's Name:</strong>
            <?php echo htmlspecialchars($student['mother_name']); ?>
        </div>
        <div class="info-item">
            <strong>Guardian Phone:</strong>
            <?php echo htmlspecialchars($student['guardian_phone']); ?>
        </div>
        <div class="info-item">
            <strong>Full Address:</strong>
            <?php echo htmlspecialchars($student['address']) . ", " . htmlspecialchars($student['city']) . " - " . htmlspecialchars($student['pincode']); ?>
        </div>
    </div>

    <h3>Educational Qualifications</h3>
    <div class="info-grid">
        <div class="info-item">
            <strong>Medium:</strong>
            <?php echo htmlspecialchars($student['medium']); ?>
        </div>
        <div class="info-item">
            <strong>Degree/Class:</strong>
            <?php echo htmlspecialchars($student['degree']); ?>
        </div>
        <div class="info-item">
            <strong>Institution:</strong>
            <?php echo htmlspecialchars($student['institution_name']); ?>
        </div>
        <div class="info-item">
            <strong>Percentage:</strong>
            <?php echo htmlspecialchars($student['percentage']); ?>%
        </div>
    </div>

    <h3>Fee Breakdown</h3>
    <div class="fee-summary">
        <div class="fee-item">
            <strong>Total Fees</strong>
            <div class="fee-val">₹<?php echo number_format($final_total, 2); ?></div>
        </div>
        <div class="fee-item">
            <strong>Amount Paid</strong>
            <div class="fee-val">₹<?php echo number_format($total_paid, 2); ?></div>
        </div>
        <div class="fee-item">
            <strong>Balance Due</strong>
            <div class="fee-val <?php echo $due > 0 ? 'fee-due' : 'fee-paid'; ?>">
                <?php echo $due > 0 ? '₹'.number_format($due, 2) : 'Paid in Full'; ?>
            </div>
        </div>
    </div>

    <?php if(count($payments) > 0): ?>
    <h3>Payment Transactions</h3>
    <table>
        <thead>
            <tr>
                <th>Date (DD-MM-YYYY)</th>
                <th>Mode</th>
                <th>Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($payments as $pay): ?>
            <tr>
                <td><?php echo date('d-m-Y', strtotime($pay['payment_date'])); ?></td>
                <td><?php echo htmlspecialchars($pay['payment_mode']); ?></td>
                <td><?php echo htmlspecialchars($pay['payment_structure']); ?></td>
                <td>₹<?php echo number_format($pay['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="footer-note">
        This is a computer-generated document and does not require a physical signature.<br>
        Generated on <?php echo date('d M Y, h:i A'); ?>
    </div>

</div>

</body>
</html>
