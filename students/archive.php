<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: passed.php");
    exit();
}

$id = intval($_GET['id']);
$error = "";

// 1. Verify existence and completion status
$stmt = $conn->prepare("SELECT registration_no, full_name, status FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}
if ($student['status'] !== 'Completed') {
    die("Only 'Completed' students can be archived.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "CSRF token validation failed. Please refresh the page and try again.";
    } else {
        $result_summary = trim($_POST['result_summary']);
        if (empty($result_summary)) {
            $error = "Result Summary is required.";
        } else {
            $conn->begin_transaction();

            try {
                // 2. Archive student into passed_out_students
                $archive_query = "
                    INSERT INTO passed_out_students (
                        registration_no, full_name, dob, gender, phone, email, photo,
                        father_name, mother_name, guardian_phone, address, city, state, pincode,
                        course, batch, admission_date, course_duration, total_fees, fees_paid,
                        status, sequence_no, medium, institution_name, institution_address, degree,
                        percentage, main_subjects, passing_year, discount_type, discount_percent,
                        discount_amount, final_total, payment_structure, heard_about,
                        referred_student_name, referred_student_phone, heard_other_text, batch_id,
                        result_summary
                    )
                    SELECT 
                        registration_no, full_name, dob, gender, phone, email, photo,
                        father_name, mother_name, guardian_phone, address, city, state, pincode,
                        course, batch, admission_date, course_duration, total_fees, fees_paid,
                        status, sequence_no, medium, institution_name, institution_address, degree,
                        percentage, main_subjects, passing_year, discount_type, discount_percent,
                        discount_amount, final_total, payment_structure, heard_about,
                        referred_student_name, referred_student_phone, heard_other_text, batch_id,
                        ?
                    FROM students
                    WHERE id = ?
                ";
                
                $stmt_archive = $conn->prepare($archive_query);
                $stmt_archive->bind_param("si", $result_summary, $id);
                if (!$stmt_archive->execute()) {
                    throw new Exception("Failed to insert into passed_out_students: " . $stmt_archive->error);
                }
                $stmt_archive->close();

                // 3. Delete from original students table
                $stmt_delete = $conn->prepare("DELETE FROM students WHERE id = ?");
                $stmt_delete->bind_param("i", $id);
                if (!$stmt_delete->execute()) {
                    throw new Exception("Failed to delete from students table.");
                }
                $stmt_delete->close();

                $conn->commit();
                header("Location: passed.php?success=archived");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error archiving student: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<div class="table-container">
    <div class="table-header">
        <h2>Archive Student: <?php echo htmlspecialchars($student['full_name']); ?></h2>
        <a href="passed.php" class="btn btn-secondary">Cancel</a>
    </div>

    <?php if ($error): ?>
        <div style="color:red; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="section-card">
        <h3>Current Record Info</h3>
        <p><strong>Reg. No:</strong> <?php echo htmlspecialchars($student['registration_no'] ?? ''); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($student['status'] ?? ''); ?></p>
        <p style="color:red; font-size:0.9em;">Note: Archiving a student will remove them from the active database and move their record permanently to the passed out students log.</p>
    </div>

    <form method="POST" class="section-card">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        
        <div class="form-grid" style="grid-template-columns: 1fr;">
            <div class="input-group">
                <label>Result Summary / Comments</label>
                <textarea name="result_summary" rows="4" placeholder="e.g. Graduated with honors, Final Score: 85%" required></textarea>
            </div>
        </div>
        
        <br>
        <button type="submit" class="add-btn btn-primary" onclick="return confirm('Are you sure you want to permanently archive this student?');">Archive Student</button>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>
