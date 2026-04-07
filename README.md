# Coaching Institute Management System (CIMS)

A comprehensive PHP-based web application tailored for managing coaching institutes, tuition centers, and educational organizations. 

## Features
* **Student Management:** End-to-end admission tracking, registration, profile management, and archiving of passed-out students.
* **Course & Batch Scheduling:** Organized batch slots and duration management for multiple course types.
* **Fee & Finance System:** Track installments, automate fee alerts, generate printable receipts, and overview complete financial collections.
* **Role-based Authentication:** Restricted access levels for `superadmin` and `staff` roles.
* **Referral & Reward Program:** Manage student/faculty referrals, allocate and track redeemable reward points.
* **Task Engine:** Built-in task assignment module for organizational tracking via an interactive dashboard.
* **Analytics & Reporting:** Clean, interactive, and exportable (CSV) analytics module to analyze collections and registration metrics.

## Tech Stack
* **Frontend:** HTML, CSS, JavaScript, Bootstrap
* **Backend:** PHP 8+
* **Database:** MySQL / MariaDB (InnoDB)

## Installation Guide (XAMPP / WAMP)
1. Clone the repository into your local web server environment directory (e.g., `htdocs` for XAMPP).
2. Start the **Apache** and **MySQL** modules from your Control Panel.
3. Open `phpMyAdmin` and create a database named `cims`.
4. Import the freshly provided schema file: `database/cims.sql`.
5. Ensure your `includes/db.php` configuration aligns with your local setup (default: root user, no password).
6. Access the application at `http://localhost/cims`.

## Default Access Credentials
A default superadmin account is automatically injected via the provided database schema:
* **Username:** `botadmin`
* **Password:** `password`

> **Note:** Please change these credentials or create a new superadmin immediately after your first login for security purposes.

## Recent Updates
* System fully sanitized: Removed old production entries, sensitive data, test files, and old logs. 
* Database truncated seamlessly while preserving full schema configuration.
* Directory structures for `uploads/` are secured utilizing `.gitkeep`.
