<?php
require_once "../includes/auth.php";
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pending.php");
    exit();
}

$student_id = intval($_GET['id']);

/* Fetch student data */
$stmt = $conn->prepare("
    SELECT id, registration_no, full_name, father_name, mother_name, 
           course, admission_date, course_duration, photo, status
    FROM students
    WHERE id = ? AND status = 'Result Pending'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: pending.php");
    exit();
}

/* Build MCO registration number */
$reg_no_raw = $student['registration_no'] ?? '';
$reg_no_display = $reg_no_raw;
if (!str_starts_with(strtoupper($reg_no_display), 'MCO')) {
    $reg_no_display = 'MCO' . $reg_no_display;
}

/* Calculate suggested start/end month from admission date and course duration */
$start_month = $student['admission_date'] ? date('Y-m', strtotime($student['admission_date'])) : '';
$duration_months = (int) preg_replace('/\D/', '', $student['course_duration'] ?? '12');
if ($duration_months < 1) $duration_months = 12;
$end_month = $student['admission_date']
    ? date('Y-m', strtotime('+' . $duration_months . ' months', strtotime($student['admission_date'])))
    : '';

/* Photo path */
$photo_path = '';
if (!empty($student['photo'])) {
    $photo_path = '/cims/uploads/' . $student['photo'];
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
?>

<style>
.result-form-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(15,23,42,0.07);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

.result-student-banner {
    display: flex;
    align-items: center;
    gap: 20px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
    border-radius: 12px;
    padding: 20px 28px;
    margin-bottom: 28px;
}

.result-student-banner .student-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #eab308;
}

.result-student-banner .student-avatar-placeholder {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #334155;
    border: 3px solid #eab308;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    color: #eab308;
}

.result-student-banner .student-info h3 {
    margin: 0 0 4px 0;
    font-size: 20px;
    font-weight: 700;
}

.result-student-banner .student-info p {
    margin: 0;
    font-size: 13px;
    color: #94a3b8;
}

.result-student-banner .student-badge {
    margin-left: auto;
    background: #eab308;
    color: #0f172a;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.section-divider {
    display: flex;
    align-items: center;
    margin: 24px 0 16px;
    color: #64748b;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
}

.section-divider::before, .section-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e2e8f0;
}

.section-divider span { padding: 0 14px; }

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-field label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-field input, .form-field select {
    height: 42px;
    padding: 0 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #0f172a;
    background: #fff;
    transition: border-color 0.2s;
    font-family: inherit;
}

.form-field input:focus, .form-field select:focus {
    outline: none;
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15,23,42,0.06);
}

.form-field input[readonly] {
    background: #f8fafc;
    color: #0f172a;
    font-weight: 700;
    border-color: #e2e8f0;
    cursor: default;
}

/* Subject rows */
.subject-row {
    display: flex;
    gap: 10px;
    align-items: center;
    animation: fadeIn 0.25s ease;
}

@keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

.subject-row input, .subject-row select {
    height: 40px;
    padding: 0 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    color: #0f172a;
    font-family: inherit;
    background: #fff;
    transition: border-color 0.2s;
}

.subject-row input:focus, .subject-row select:focus {
    outline: none;
    border-color: #0f172a;
}

.subject-remove-btn {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 20px;
    padding: 0 4px;
    line-height: 1;
    transition: transform 0.2s;
    flex-shrink: 0;
}

.subject-remove-btn:hover { transform: scale(1.2); }

.add-subject-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    padding: 8px 16px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.add-subject-btn:hover {
    background: #f1f5f9;
    border-color: #0f172a;
    color: #0f172a;
}

.calc-result-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-top: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.calc-item {
    text-align: center;
}

.calc-item .calc-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    margin-bottom: 4px;
}

.calc-item .calc-value {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
}

.calc-item .calc-value.highlight {
    color: #0f172a;
    background: #eab308;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 15px;
}

.photo-upload-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    margin-top: 16px;
}

.photo-preview {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}

.photo-upload-label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
}

.photo-upload-sub {
    font-size: 12px;
    color: #94a3b8;
}

.submit-row {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn-save-result {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: #0f172a;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    letter-spacing: 0.04em;
}

.btn-save-result:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(15,23,42,0.2);
}

.btn-save-result:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Print actions area */
#printActions {
    display: none;
}

#printActions.show {
    display: block;
    animation: fadeIn 0.4s ease;
}

.print-actions-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 14px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.print-actions-card .success-text {
    color: white;
}

.print-actions-card .success-text h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px;
}

.print-actions-card .success-text p {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
}

.print-btns {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    font-family: inherit;
    text-decoration: none;
}

.btn-print-marksheet {
    background: #eab308;
    color: #0f172a;
}

.btn-print-marksheet:hover {
    background: #ca8a04;
    transform: translateY(-1px);
}

.btn-print-cert {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.25);
}

.btn-print-cert:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-1px);
}
</style>

<div class="table-container">

    <div class="table-header" style="margin-bottom:24px;">
        <div>
            <h2>Generate Result</h2>
            <a href="pending.php" style="font-size:13px; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:6px; margin-top:4px;">
                ← Back to Result Pending List
            </a>
        </div>
    </div>

    <form id="resultForm">
        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">

        <!-- Student Info Banner -->
        <div class="result-student-banner">
            <?php if ($photo_path): ?>
                <img src="<?php echo htmlspecialchars($photo_path); ?>" class="student-avatar" alt="Photo">
            <?php else: ?>
                <div class="student-avatar-placeholder">
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="student-info">
                <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                <p>
                    <?php echo htmlspecialchars($reg_no_display); ?> &nbsp;·&nbsp;
                    <?php echo htmlspecialchars($student['course']); ?> &nbsp;·&nbsp;
                    <?php echo htmlspecialchars($student['course_duration'] ?? ''); ?>
                </p>
            </div>
            <div class="student-badge">Ready for Result</div>
        </div>

        <div class="result-form-card">

            <!-- Basic Info Section -->
            <div class="section-divider"><span>Student Details</span></div>

            <div class="form-grid-2">
                <div class="form-field">
                    <label>Student Full Name</label>
                    <input type="text" name="studentName" id="studentName"
                           value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="form-field">
                    <label>Registration No.</label>
                    <input type="text" name="enrollmentNo" id="enrollmentNo"
                           value="<?php echo htmlspecialchars($reg_no_display); ?>" required>
                </div>
                <div class="form-field">
                    <label>Father's Name</label>
                    <input type="text" name="fatherName" id="fatherName"
                           value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" required>
                </div>
                <div class="form-field">
                    <label>Mother's Name</label>
                    <input type="text" name="motherName" id="motherName"
                           value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-grid-3">
                <div class="form-field">
                    <label>Course / Program</label>
                    <input type="text" name="courseName" id="courseName"
                           value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>" required>
                </div>
                <div class="form-field">
                    <label>Course Start Month</label>
                    <input type="month" name="startDate" id="startDate"
                           value="<?php echo $start_month; ?>" required>
                </div>
                <div class="form-field">
                    <label>Course End Month</label>
                    <input type="month" name="endDate" id="endDate"
                           value="<?php echo $end_month; ?>" required>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-field">
                    <label>Date of Issue</label>
                    <input type="date" name="issueDate" id="issueDate"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-field">
                    <label>Guardian Name (for certificate)</label>
                    <input type="text" name="guardianName" id="guardianName"
                           value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
                </div>
            </div>

            <!-- Subjects Section -->
            <div class="section-divider"><span>Subjects &amp; Marks (Max 10)</span></div>

            <div id="subjectsContainer" style="display:flex; flex-direction:column; gap:10px; margin-bottom:12px;"></div>

            <button type="button" class="add-subject-btn" id="addSubjectBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Subject
            </button>

            <!-- Auto-calculated results -->
            <div class="calc-result-grid">
                <div class="calc-item">
                    <div class="calc-label">Obtained</div>
                    <div class="calc-value" id="calcObtained">—</div>
                </div>
                <div class="calc-item">
                    <div class="calc-label">Maximum</div>
                    <div class="calc-value" id="calcMax">—</div>
                </div>
                <div class="calc-item">
                    <div class="calc-label">Percentage</div>
                    <div class="calc-value highlight" id="calcPercentage">—</div>
                </div>
                <div class="calc-item">
                    <div class="calc-label">Division</div>
                    <div class="calc-value" id="calcDivision">—</div>
                </div>
                <div class="calc-item">
                    <div class="calc-label">Grade</div>
                    <div class="calc-value" id="calcGrade">—</div>
                </div>
            </div>

            <!-- Photo Upload -->
            <div class="section-divider"><span>Student Photo</span></div>

            <div class="photo-upload-row">
                <?php if ($photo_path): ?>
                    <img src="<?php echo htmlspecialchars($photo_path); ?>" class="photo-preview" id="photoPreview" alt="Current Photo">
                <?php else: ?>
                    <div id="photoPreviewPlaceholder" style="width:60px; height:60px; border-radius:8px; background:#f1f5f9; border:2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; font-size:22px;">📷</div>
                    <img src="" class="photo-preview" id="photoPreview" alt="Photo Preview" style="display:none;">
                <?php endif; ?>
                <div>
                    <div class="photo-upload-label">Upload Photo for Certificate &amp; Marksheet</div>
                    <div class="photo-upload-sub">Existing photo will be used if not overridden. JPG/PNG preferred.</div>
                    <input type="file" id="studentPhoto" name="studentPhoto" accept="image/*" style="margin-top:8px; font-size:13px;">
                </div>
            </div>

            <!-- Hidden fields for calculated data (populated by JS) -->
            <input type="hidden" name="obtainedMarks" id="hiddenObtained">
            <input type="hidden" name="maxMarks" id="hiddenMax">
            <input type="hidden" name="percentage" id="hiddenPercentage">
            <input type="hidden" name="division" id="hiddenDivision">
            <input type="hidden" name="grade" id="hiddenGrade">

            <!-- Submit -->
            <div class="submit-row">
                <div id="statusMsg" style="align-self:center; font-size:14px; font-weight:500;"></div>
                <button type="submit" class="btn-save-result" id="saveBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    Save &amp; Generate
                </button>
            </div>
        </div>
    </form>

    <!-- Print Actions (shown after successful save) -->
    <div id="printActions">
        <div class="print-actions-card">
            <div class="success-text">
                <h3>✅ Result Generated Successfully!</h3>
                <p>Student has been marked as Completed. Select an action to proceed.</p>
            </div>
            <div class="print-btns">
                <button class="btn-print btn-print-marksheet" id="printMarksheetBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Print Marksheet
                </button>
                <button class="btn-print btn-print-cert" id="printCertBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                    Generate Certificate
                </button>
            </div>
        </div>
    </div>

</div>

<script>
const existingPhotoUrl = <?php echo $photo_path ? json_encode('http://' . $_SERVER['HTTP_HOST'] . $photo_path) : 'null'; ?>;

document.addEventListener('DOMContentLoaded', () => {
    const container  = document.getElementById('subjectsContainer');
    const addBtn     = document.getElementById('addSubjectBtn');
    let subjectCount = 0;

    /* ── Calculate totals ── */
    function calculateTotals() {
        const marks = document.querySelectorAll('.subject-mark');
        let obtained = 0, valid = 0;
        marks.forEach(inp => {
            if (inp.value !== '') { obtained += Number(inp.value); valid++; }
        });
        const max = valid * 100;
        const pct = max > 0 ? ((obtained / max) * 100).toFixed(1) : 0;

        let grade = '', division = '';
        const p = Number(pct);
        if      (p >= 90) { grade = 'Distinction'; division = 'Distinction'; }
        else if (p >= 75) { grade = 'A+';          division = '1st Division'; }
        else if (p >= 60) { grade = 'A';           division = '1st Division'; }
        else if (p >= 50) { grade = 'B';           division = '2nd Division'; }
        else if (p >= 40) { grade = 'C';           division = '3rd Division'; }
        else if (max > 0) { grade = 'Fail';        division = 'Fail'; }

        document.getElementById('calcObtained').textContent  = obtained || '—';
        document.getElementById('calcMax').textContent        = max || '—';
        document.getElementById('calcPercentage').textContent = pct ? pct + '%' : '—';
        document.getElementById('calcDivision').textContent   = division || '—';
        document.getElementById('calcGrade').textContent      = grade || '—';

        document.getElementById('hiddenObtained').value  = obtained;
        document.getElementById('hiddenMax').value        = max;
        document.getElementById('hiddenPercentage').value = pct ? pct + '%' : '';
        document.getElementById('hiddenDivision').value   = division;
        document.getElementById('hiddenGrade').value      = grade;
    }

    /* ── Add subject row ── */
    function addSubjectRow() {
        if (subjectCount >= 10) return;
        subjectCount++;

        const row = document.createElement('div');
        row.className = 'subject-row';
        row.innerHTML = `
            <input type="text" name="subjectName[]" placeholder="Subject Name" required style="flex:2;">
            <select name="subjectType[]" required style="flex:1; appearance:none; background:#fff url('data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'16\\' height=\\'16\\' viewBox=\\'0 0 24 24\\' fill=\\'none\\' stroke=\\'%2394a3b8\\' stroke-width=\\'2\\'%3E%3Cpolyline points=\\'6 9 12 15 18 9\\'%3E%3C/polyline%3E%3C/svg%3E') no-repeat right 10px center; padding-right:28px;">
                <option value="Theory">Theory</option>
                <option value="Practical">Practical</option>
            </select>
            <input type="number" name="subjectMarks[]" class="subject-mark" placeholder="Marks (0–100)" min="0" max="100" required style="flex:1;">
            <button type="button" class="subject-remove-btn" title="Remove">&#x2715;</button>
        `;

        row.querySelector('.subject-mark').addEventListener('input', calculateTotals);
        row.querySelector('.subject-remove-btn').addEventListener('click', () => {
            row.remove();
            subjectCount--;
            calculateTotals();
            if (subjectCount < 10) addBtn.style.display = 'inline-flex';
        });

        container.appendChild(row);
        if (subjectCount >= 10) addBtn.style.display = 'none';
    }

    addBtn.addEventListener('click', addSubjectRow);

    /* Start with 3 rows */
    addSubjectRow();
    addSubjectRow();
    addSubjectRow();

    /* ── Photo Preview ── */
    const photoInput = document.getElementById('studentPhoto');
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPreviewPlaceholder');

    photoInput.addEventListener('change', () => {
        const file = photoInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                photoPreview.src = e.target.result;
                photoPreview.style.display = 'block';
                if (photoPlaceholder) photoPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    /* ── Form Submit ── */
    const form = document.getElementById('resultForm');
    const saveBtn = document.getElementById('saveBtn');
    const statusMsg = document.getElementById('statusMsg');

    form.addEventListener('submit', e => {
        e.preventDefault();
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span>Saving...</span>';
        statusMsg.textContent = '';

        function finalize(data) {
            localStorage.setItem('currentStudentData', JSON.stringify(data));

            /* Tell the server to mark student as Completed */
            fetch('finalize.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'student_id=' + encodeURIComponent(data.student_id)
            })
            .then(r => {
                if (!r.ok) throw new Error('Server error: ' + r.status);
                return r.json();
            })
            .then(resp => {
                if (resp.success) {
                    statusMsg.textContent = '';
                    document.getElementById('printActions').classList.add('show');
                    saveBtn.innerHTML = '<span>✔ Saved</span>';
                } else {
                    statusMsg.style.color = '#ef4444';
                    statusMsg.textContent = '❌ ' + (resp.message || 'Unknown error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg><span>Save &amp; Generate</span>`;
                }
            })
            .catch(err => {
                statusMsg.style.color = '#ef4444';
                statusMsg.textContent = '❌ ' + err.message;
                saveBtn.disabled = false;
                saveBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg><span>Retry</span>`;
            });
        }

        /* Collect form data */
        const formData = new FormData(form);
        const studentData = { subjectName: [], subjectType: [], subjectMarks: [] };

        for (let [key, value] of formData.entries()) {
            if (key === 'subjectName[]')  { studentData.subjectName.push(value); continue; }
            if (key === 'subjectType[]')  { studentData.subjectType.push(value); continue; }
            if (key === 'subjectMarks[]') { studentData.subjectMarks.push(value); continue; }
            studentData[key] = value;
        }

        /* Ensure MCO prefix */
        if (studentData.enrollmentNo && !studentData.enrollmentNo.toUpperCase().startsWith('MCO')) {
            studentData.enrollmentNo = 'MCO' + studentData.enrollmentNo;
        }

        /* Handle photo */
        const photoFile = photoInput.files[0];
        if (photoFile) {
            const reader = new FileReader();
            reader.onload = evt => { studentData.photoUrl = evt.target.result; finalize(studentData); };
            reader.onerror = () => { finalize(studentData); };
            reader.readAsDataURL(photoFile);
        } else if (existingPhotoUrl) {
            /* Use existing photo by fetching it as blob */
            fetch(existingPhotoUrl)
                .then(r => r.blob())
                .then(blob => {
                    const blobReader = new FileReader();
                    blobReader.onload = evt => { studentData.photoUrl = evt.target.result; finalize(studentData); };
                    blobReader.readAsDataURL(blob);
                })
                .catch(() => finalize(studentData));
        } else {
            finalize(studentData);
        }
    });

    /* ── Print Buttons ── */
    document.getElementById('printMarksheetBtn').addEventListener('click', () => {
        window.open('/cims/results/result_placeholder.html', '_blank');
    });

    document.getElementById('printCertBtn').addEventListener('click', () => {
        window.open('/cims/results/certificate_placeholder.html', '_blank');
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>
