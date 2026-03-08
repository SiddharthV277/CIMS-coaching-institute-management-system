<?php
require_once __DIR__ . "/auth.php";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vigyaan Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
    box-sizing: border-box;
}
      body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: #F4EFEA;
    color: #3B2F2F;
}

/* Topbar */
.topbar {
    background: #FFFFFF;
    padding: 18px 30px;
    border-bottom: 1px solid #E6DCD4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.topbar h3 {
    margin: 0;
    font-weight: 600;
}

.logout-btn {
    text-decoration: none;
    background: #7A1E3A;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    transition: 0.3s ease;
}

.logout-btn:hover {
    background: #64182F;
}

/* Layout */
.layout {
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 240px;
    background: linear-gradient(180deg, #7A1E3A, #64182F);
    min-height: 100vh;
    padding-top: 25px;
}

.brand {
    color: white;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 40px;
    letter-spacing: 2px;
}

.nav-link {
    display: block;
    color: rgba(255,255,255,0.85);
    padding: 14px 25px;
    text-decoration: none;
    transition: 0.3s ease;
    font-size: 14px;
}

.nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: #ffffff;
}

/* Content */
.content {
    flex: 1;
    padding: 40px;
}

/* Section title */
.section-title {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 10px;
}

.section-subtitle {
    font-size: 14px;
    color: #7C6F68;
    margin-bottom: 30px;
}

/* Stats row */
.stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: #FFFFFF;
    padding: 25px;
    border-radius: 12px;
    flex: 1;
    border: 1px solid #E6DCD4;
    box-shadow: 0 15px 35px rgba(60,40,30,0.06);
    position: relative;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 4px;
    width: 100%;
    background: #7A1E3A;
    border-radius: 12px 12px 0 0;
}

.stat-title {
    font-size: 13px;
    color: #7C6F68;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 26px;
    font-weight: 700;
}

/* Action cards */
.card-grid {
    display: flex;
    gap: 25px;
}

.card {
    background: #FFFFFF;
    padding: 30px;
    border-radius: 14px;
    flex: 1;
    border: 1px solid #E6DCD4;
    box-shadow: 0 20px 40px rgba(60,40,30,0.07);
    transition: 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
}

.card h4 {
    margin-top: 0;
    margin-bottom: 12px;
}

.card p {
    font-size: 14px;
    color: #7C6F68;
}

.card a {
    display: inline-block;
    margin-top: 15px;
    padding: 8px 14px;
    background: #7A1E3A;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.card a:hover {
    background: #64182F;

}
/* TABLE STYLING */

.table-container {
    background: #FFFFFF;
    padding: 25px;
    border-radius: 14px;
    border: 1px solid #E6DCD4;
    box-shadow: 0 20px 40px rgba(60,40,30,0.07);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.add-btn {
    background: #7A1E3A;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.add-btn:hover {
    background: #64182F;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    font-size: 13px;
    padding: 12px;
    border-bottom: 1px solid #E6DCD4;
    color: #7C6F68;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #F1EAE5;
    font-size: 14px;
}

tr:hover {
    background: #F9F5F2;
}

/* BADGES */

.role-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.role-superadmin {
    background: #7A1E3A;
    color: white;
}

.role-staff {
    background: #E6DCD4;
    color: #3B2F2F;
}

.status-active {
    background: #D4EDDA;
    color: #155724;
}

.status-inactive {
    background: #F8D7DA;
    color: #721C24;
}

.action-btn {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    text-decoration: none;
    margin-right: 5px;
    display: inline-block;
}

.btn-edit {
    background: #E6DCD4;
    color: #3B2F2F;
}

.btn-toggle {
    background: #7A1E3A;
    color: white;
}
/* FORM STYLING */

.form-container {
    background: #FFFFFF;
    padding: 30px;
    border-radius: 14px;
    border: 1px solid #E6DCD4;
    box-shadow: 0 20px 40px rgba(60,40,30,0.07);
    max-width: 500px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 13px;
    margin-bottom: 6px;
    color: #7C6F68;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #D8CCC3;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #7A1E3A;
    box-shadow: 0 0 0 2px rgba(122,30,58,0.15);
}

.submit-btn {
    background: #7A1E3A;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}

.submit-btn:hover {
    background: #64182F;
}

.error-msg {
    background: #F8D7DA;
    color: #721C24;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}
/* PASSWORD TOGGLE */

.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding: 10px 42px 10px 10px; /* space for icon */
    border-radius: 6px;
    border: 1px solid #D8CCC3;
    font-size: 14px;
}

.toggle-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 15px;
    color: #7C6F68;
    user-select: none;
}
/* SUCCESS POPUP */

.success-popup {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #D4EDDA;
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    border: 1px solid #C3E6CB;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    font-size: 14px;
    animation: fadeIn 0.3s ease;
    z-index: 999;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
/* STUDENT FORM IMPROVED */

.section-card {
    background: #FFFFFF;
    padding: 30px;
    border-radius: 16px;
    border: 1px solid #E6DCD4;
    box-shadow: 0 20px 45px rgba(60,40,30,0.06);
    margin-bottom: 35px;
}

.section-card h3 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 18px;
    font-weight: 600;
    position: relative;
    padding-left: 14px;
}

.section-card h3::before {
    content: "";
    position: absolute;
    left: 0;
    top: 4px;
    height: 18px;
    width: 4px;
    background: #7A1E3A;
    border-radius: 4px;
}

/* GRID */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px 30px;
}

/* FULL WIDTH ELEMENTS */

.full-width {
    grid-column: 1 / -1;
}

/* LABELS */

.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    padding: 11px 12px;
    border-radius: 8px;
    border: 1px solid #D8CCC3;
    font-size: 14px;
    transition: 0.2s ease;
}

.form-grid textarea {
    min-height: 80px;
    resize: vertical;
}

.form-grid input:focus,
.form-grid select:focus,
.form-grid textarea:focus {
    outline: none;
    border-color: #7A1E3A;
    box-shadow: 0 0 0 2px rgba(122,30,58,0.12);
}

/* SUBMIT BUTTON SPACING */

.submit-btn {
    margin-top: 10px;
    padding: 12px 22px;
    font-size: 14px;
    border-radius: 8px;
}
.photo-upload-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.photo-label {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.photo-note {
    font-size: 12px;
    color: #7C6F68;
}

input[type="file"] {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #D8CCC3;
    background: #FAF8F6;
    cursor: pointer;
}
.page-title {
    position: sticky;
    top: 0;
    background: #F6F4F2;
    padding: 10px 0 20px 0;
    z-index: 5;
}
/* ================= REFERRAL STYLING ================= */

.referral-options {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.ref-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f5f7fa;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.2s ease;
}

.ref-checkbox:hover {
    background: #e2e8f0;
}

.ref-checkbox input {
    accent-color: #4f46e5;
}
    </style>
</head>

<body>

<div class="topbar">
    <h3>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h3>
    <a href="/cims/logout.php" class="logout-btn">Logout</a>
</div>

<div class="layout">