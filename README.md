# CSE 391 Assignment 3: Car Workshop Online Appointment System

An interactive online appointment management web application built with **HTML5**, **CSS3**, **JavaScript (AJAX)**, **PHP**, and **MySQL** (with automatic SQLite zero-config fallback).

---

## 🌟 Key Features & Requirements Matrix

| Requirement | Implementation Detail | Status |
| :--- | :--- | :---: |
| **5 Senior Mechanics** | Pre-populated 5 senior mechanics (Karim Rahman, Tanvir Ahmed, Rahim Uddin, Shafiqul Islam, Mahfuz Khan). | ✅ |
| **Daily Slot Limit (Max 4)** | Each mechanic can take maximum 4 active appointments per date. Real-time availability badges show `X/4 Free Slots`. | ✅ |
| **User Panel** | Input fields: Client Name, Address, Phone, Car License Number, Car Engine Number, Appointment Date, Mechanic selector. | ✅ |
| **Real-time Slot Checking** | Selecting or changing the appointment date dynamically fetches live remaining slots for all 5 mechanics via AJAX. | ✅ |
| **Duplicate Booking Check** | Prevents a client (by Phone or Car License) from booking multiple appointments on the same date across any mechanics. | ✅ |
| **Mechanic Overbook Check** | Rejects booking requests if the chosen mechanic has reached capacity (4/4) on that date. | ✅ |
| **Admin Panel** | Displays appointment table showing Client Name, Phone, Car Reg Number, Appointment Date, Mechanic Name, Engine No, and Address. | ✅ |
| **Admin Reassignment & Edit** | Admin can change appointment dates and reassign mechanics to any available mechanic on the new date. | ✅ |
| **Input Validation** | Full client-side JavaScript regex validation & server-side PHP sanitization and error handling. | ✅ |
| **Help & Instructions Facility** | Embedded Help Modal with full guidelines, rules, and step-by-step instructions (Part 1 requirement). | ✅ |
| **Design Excellence (Part 2)** | Modern responsive layout, glassmorphism, HSL color tokens, toast notifications, status badges, and interactive modals. | ✅ |

---

## 🚀 How to Run the Application

### Option A: Quick Run with PHP Built-in Web Server (No Database Setup Needed)

1. Open PowerShell / Command Prompt in this directory (`c:\Users\Sandip Kumar Paul\Downloads\CSE391\A3_Taz`).
2. Run the following command:
   ```bash
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   - **User Booking Panel**: [http://localhost:8000](http://localhost:8000)
   - **Admin Dashboard**: [http://localhost:8000/admin.php](http://localhost:8000/admin.php)

> *Note: If MySQL is not running or not configured, the system automatically uses a local `workshop.sqlite` database file with seeded mechanics, requiring zero setup!*

---

### Option B: Running on XAMPP / WAMP / Apache + MySQL

1. Copy the project folder into your XAMPP `htdocs` directory:
   `C:\xampp\htdocs\A3_Taz`
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open **phpMyAdmin** ([http://localhost/phpmyadmin](http://localhost/phpmyadmin)).
4. Create a new database named `car_workshop_db`.
5. Import `database.sql` into `car_workshop_db`.
6. Configure `config.php` if your MySQL port or password differs (default: host `127.0.0.1`, port `3306`, user `root`, pass ``).
7. Open your browser and navigate to:
   - **User Booking Panel**: [http://localhost/A3_Taz/index.php](http://localhost/A3_Taz/index.php)
   - **Admin Dashboard**: [http://localhost/A3_Taz/admin.php](http://localhost/A3_Taz/admin.php)

---

## 📂 File Directory Structure

- `index.php` - Main User Panel page for booking appointments and viewing real-time mechanic availability.
- `admin.php` - Admin Panel dashboard for viewing, searching, filtering, and editing client appointments.
- `api.php` - Backend JSON API endpoint for slot checking, appointment booking, search/filter, and editing.
- `config.php` - Database connection setup (MySQL with auto-SQLite fallback and initial seeding).
- `database.sql` - MySQL schema DDL script and seed data for 5 senior mechanics.
- `style.css` - Vanilla CSS design system, responsive grids, HSL colors, badges, and animations.
- `app.js` - User Panel AJAX logic, client-side validation, and slot rendering.
- `admin.js` - Admin Panel AJAX logic, search/filtering, table rendering, and edit modal handler.
- `README.md` - Documentation and instructions.

---
*Created for CSE 391: Programming for the Internet Assignment 3.*
