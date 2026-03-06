# Coaching Institute Management System (CIMS)

## Overview
The **Coaching Institute Management System (CIMS)** is a web-based application developed to simplify and automate the administrative operations of a coaching institute.  
The system helps manage student admissions, fee installments, staff records, and administrative workflows in a structured digital format.

This project was developed as part of my **BCA coursework** to demonstrate backend development, database integration, and system design using PHP and MySQL.

---

## Features

- Student admission management
- Fee installment tracking system
- Batch management
- Staff management
- Secure login system
- Admin dashboard for centralized control
- Role-based access control
- Student records database
- Installment regeneration and tracking

---

## Technologies Used

| Technology | Purpose |
|------------|--------|
| PHP | Backend development |
| MySQL | Database management |
| HTML | Page structure |
| CSS | User interface styling |
| XAMPP | Local server environment |

---

## Project Structure

```
CIMS
│
├── assets/        # Images and UI resources
├── batches/       # Batch management module
├── fees/          # Fee management and installments
├── includes/      # Database connections and shared functions
├── staff/         # Staff management module
├── students/      # Student management module
│
├── admission.php
├── dashboard.php
├── login.php
├── logout.php
├── no_permission.php
├── test.php
```

---

## Installation & Setup

1. Install **XAMPP** or any PHP server environment.
2. Clone or download this repository.

```
git clone https://github.com/SiddharthV277/CIMS-coaching-institute-management-system.git
```

3. Move the project folder into:

```
xampp/htdocs/
```

4. Start **Apache** and **MySQL** from the XAMPP control panel.

5. Open **phpMyAdmin** and import the database file:

```
database/cims.sql
```

6. Open the system in your browser:

```
http://localhost/cims
```

---

## Future Improvements

- Online fee payment integration
- Student attendance tracking
- SMS / Email notification system
- Reporting and analytics dashboard
- Multi-branch institute support

---

## Author

**Siddharth Verma**  
BCA Student | System Builder  

Turning real-world administrative problems into structured digital systems using code.

📍 Ranchi, Jharkhand, India

---


## License

This project is developed for educational purposes.
