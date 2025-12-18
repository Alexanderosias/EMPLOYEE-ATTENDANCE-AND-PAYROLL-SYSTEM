# Employee Attendance and Payroll System (EAAPS)

EAAPS is a web-based Employee Attendance and Payroll System built on PHP (XAMPP stack) designed for managing:

- **Employee records** (personal info, departments, job positions)
- **Attendance logs** (time in/out, schedules, QR-based check-in)
- **Leave requests** (submission, approval, tracking)
- **Payroll** (period previews, finalization, frequency-based filtering)
- **User accounts & roles** (employees, admins, head admins)
- **QR codes & snapshots** (for attendance tracking)

The system targets deployment on a shared MySQL database (e.g., `systemintegration`) and uses a dedicated EAAPS schema layer (tables such as `employees`, `departments`, `job_positions`, `attendance_logs`, `leave_requests`, `payroll`, `qr_codes`, `employee_schedules`, `users_employee`).

## Tech Stack

- **Backend:** PHP 8.x (XAMPP / Apache)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, vanilla JS
- **Libraries (Composer):**
  - `phpmailer/phpmailer` – email sending
  - `kreait/firebase-php` – Firebase integration
  - `shuchkin/simplexlsx` – Excel import
  - `chillerlan/php-qrcode` – QR code generation

Additional JS/CSS tooling is present under `node_modules` (for build tooling / styles), but runtime in XAMPP is pure PHP/JS.

## Main Modules / Pages

- **Dashboard** – Overview of key metrics.
- **Employees (`employees_page.php`)**
  - Add/edit employees, link them to departments & job positions.
  - Generates and regenerates QR codes per employee.
- **Schedules (`schedule_page.php`)**
  - Manage employee work schedules (`employee_schedules`).
- **Departments & Positions (`department_position.php`)**
  - Manage `departments` and `job_positions`, including payroll frequency.
- **Attendance Logs (`attendance_logs_page.php`)**
  - View and filter logs from `attendance_logs`.
- **Payroll (`payroll_page.php`)**
  - Preview and finalize payroll periods.
  - Filter by payroll frequency and (optionally) role.
- **Leave (`leave_page.php`)**
  - Submit and approve leave requests (`leave_requests`).
- **QR & Snapshots (`qr_codes_and_snapshots.php`)**
  - View QR codes and attendance snapshots.
- **Reports (`reports_page.php`)**
  - Export and view reports (attendance, payroll, etc.).
- **Profile (`profile_details_page.php`)**
  - View/update the logged-in user profile.
- **Users (`user_page.php`)**
  - Manage `users_employee` (admins/head_admins/linked employees).

## Database Notes

- EAAPS uses the **`systemintegration`** database as its base.
- EAAPS-specific tables are aligned with the `systemintegration.sql` schema but patched using `database/add_foreign_keys.sql` to:
  - Ensure **PRIMARY KEY + AUTO_INCREMENT** on key tables (including `users_employee.id`).
  - Add all necessary **foreign keys** between EAAPS tables (employees, departments, positions, users, leave, payroll, QR, schedules).

Before running the app for the first time on a new DB, import `database/systemintegration.sql` and then run `database/add_foreign_keys.sql`.

For detailed setup instructions (XAMPP, PHP extensions, Composer, database import), see **`SETUP.md`**.
