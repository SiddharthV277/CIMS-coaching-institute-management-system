<div align="center">
  <h1>📚 CIMS: Coaching Institute Management System</h1>
  <p><i>A full-stack, enterprise-grade management application built for educational centers.</i></p>
</div>

---

## 🎯 Overview
**CIMS** (Coaching Institute Management System) is a comprehensive web application designed from the ground up to digitize and scale the daily operations of modern coaching centers, tuition institutes, and educational organizations. 

Built predominantly with **PHP 8, MySQL (InnoDB), and Vanilla JavaScript**, this platform bridges the gap between complex financial tracking, student lifecycle management, and staff operations—packing all organizational workflows into a centralized, lightning-fast dashboard.

This project was built to address the chaotic nature of manual ledger books, disjointed student tracking, and inefficient staff delegation. It showcases my ability to map out complex business rules (such as dynamic fee installments, gamified referral systems, and role-based permissions) and translate them into a production-ready application.

---

## 🚀 The Journey: From Planning to Production

### Phase 1: Requirement Analysis & Architecture
The primary challenge of a coaching institute is the high volume of micro-transactions and interrelated data. A student isn't just an entity; they belong to shifting `batches`, have customized `courses`, carry variable `fee installments` based on personalized `discounts`, and may act as a `referrer` for other students.

**Database Design:**
I architected a fully relational MySQL database encompassing over 15 interconnected tables. Key focus areas included:
- **Normalization:** Splitting temporal data (like `payments` and `fee_installments`) from static profiles (`students`, `admins`).
- **Cascading Integrity:** Using strict foreign keys (`ON DELETE CASCADE`) between `students` -> `payments` to ensure no database orphans exist.
- **Audit Trails:** Every transactional log explicitly records `created_at`, `updated_at`, and `received_by` indices for financial accountability.

### Phase 2: Core Engineering & Feature Development
I built the system focusing on six major operative pillars:

#### 🧑‍🎓 1. Student Lifecycle Engine
- Managed the complete flow: `Admission Request` -> `Active Student` -> `Completed/Archived Student`.
- Handled edge cases such as batch migrations, customized course durations, and integrated duplicate-entry flags based on phone numbers and names.

#### 💸 2. Financial & Installment Module
- Engineered a flexible payment tracking system accommodating **Full, Monthly, Quarterly, and Custom** payment structures.
- Integrated automated logic to generate dynamic **Fee Installments** upon an admission's approval.
- Built a **Snoozable Alert System** (`fee_alerts`) to visually warn staff members about overdue payments and calculated fines.

#### 🤝 3. Gamified Referral System
- Designed a structured `referral_accounts` and `referral_redeem_history` schema. 
- Existing students can refer juniors, granting the referrer "Points" that can be securely redeemed for discounts or rewards. Admin overrides govern the actual point redemption to prevent fraud.

#### 🔐 4. Role-Based Access Control (RBAC)
- Constructed complete dashboard segregation between `Superadmin` and `Staff` roles.
- Staff members can capture daily payments, manage basic tasks, and admit students. Superadmins wield destructive control (archiving, analytics wiping, system configuration). 

#### 📊 5. Analytics & Export Engine
- Deployed real-time data visualization parsing financial collections and registration growth.
- Built a backend buffer system to generate complex **CSV / Excel Data Exports** for offline tallying.

#### 📋 6. Internal Task Delegation
- Implemented a Kanban-style task engine (`tasks` table) allowing superadmins to assign specific daily goals to staff members, tracking `pending` vs `completed` timestamps dynamically.

### Phase 3: Refinement & Production Deployment
Moving from an MVP to a production-ready system required heavy optimization:
1. **Sanitization:** Implemented rigorous input validations to prevent SQL Injection (utilizing Prepared Statements across the board).
2. **Local Network Porting:** Prepared the application to be seamlessly hosted on local institutional networks (via Apache) or tunneled over secure connections (Cloudflare Tunnels).
3. **Repository Clean-up:** Purged all local development `.log` files, structured a robust upload containment system (using `.gitkeep` directories for `receipts/` and `students/` photos), and truncated out all mock databases while preserving the relational schemas for seamless open-source distribution.

---

## 🛠 Tech Stack
* **Core Logic:** PHP 8+ (Procedural / Object-Oriented Hybrid)
* **Datastore:** MariaDB / MySQL (InnoDB Storage Engine)
* **Frontend:** HTML5, CSS3, ES6 JavaScript 
* **UI Framework:** Bootstrap 5 & Custom Vanilla CSS Tokens
* **Version Control:** Git & GitHub

---

## ⚙️ Installation & Usage (Developer Setup)

To spin up this application locally for testing or institutional deployment:

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/SiddharthV277/CIMS-coaching-institute-management-system.git htdocs/cims
   ```
2. **Database Initialization:**
   - Launch your local web server environment (e.g., XAMPP, WAMP).
   - Ensure both Apache and MySQL modules are running.
   - Navigate to `phpMyAdmin` and create a database named `cims`.
   - Import the sanitized schema provided at `database/cims.sql`.
3. **Configure Environment:**
   - Review `includes/db.php`. By default, it looks for `root` with no password over `localhost`. Modify if your local database dictates otherwise.
4. **Boot up:**
   - Open your browser to `http://localhost/cims`.
   - Log in using the default superadmin credentials:
     - **Username:** `botadmin`
     - **Password:** `password`

> **Security Warning:** The provided default account utilizes a standard bcrypt hash. Please archive or modify this account via the Staff Management portal immediately upon deploying to a production network.

---

<div align="center">
  <i>Developed with precision by Siddharth.</i>
</div>
