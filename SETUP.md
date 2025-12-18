# EAAPS Setup Guide (XAMPP / Localhost)

This guide explains how to set up the Employee Attendance and Payroll System (EAAPS) on another device using XAMPP.

## 1. Prerequisites

- **XAMPP 8.x** (PHP 8.1+ recommended)
- **Git** (optional, if cloning from repository)
- **Composer** (PHP dependency manager)

## 2. Project Folder Location

Place the project in your XAMPP `htdocs` folder, for example:

```text
C:\xampp\htdocs\eaaps
```

Your main entry point is typically:

```text
http://localhost/eaaps/pages/dashboard.php
```

or, if you use PHP's built-in server from the project root:

```bash
php -S localhost:8000
# then open http://localhost:8000/pages/dashboard.php
```

> Note: When using the built-in PHP server, be sure your working directory is the project root (`eaaps`).

## 3. XAMPP Configuration

### 3.1 Enable Required Apache Modules (usually already enabled)

Open **XAMPP Control Panel → Apache → Config → httpd.conf** and ensure standard modules like `rewrite_module` are enabled (most defaults are fine).

### 3.2 Enable Required PHP Extensions

Open your **`php.ini`** (XAMPP Control Panel → Apache → Config → `php.ini`) and make sure the following extensions are enabled (no leading `;`):

```ini
extension=mysqli
extension=gd
extension=mbstring
extension=openssl
extension=json
extension=fileinfo
```

After editing `php.ini`, **restart Apache** from the XAMPP Control Panel.

> `extension=gd` is required for QR code PNG generation (used by `chillerlan/php-qrcode`).

## 4. Database Setup

EAAPS uses a shared database schema based on `systemintegration.sql` with extra EAAPS constraints.

### 4.1 Create Database

1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database, for example:
   - Name: `systemintegration`
   - Collation: `utf8mb4_general_ci` (or similar)

### 4.2 Import Base Schema

1. In phpMyAdmin, select the `systemintegration` database.
2. Go to the **Import** tab.
3. Choose file:
   - `database/systemintegration.sql`
4. Click **Go** and wait for the import to finish.

### 4.3 Apply EAAPS Foreign Keys and AUTO_INCREMENT Fixes

After importing `systemintegration.sql`, run the EAAPS patch script:

1. Still in phpMyAdmin, select the `systemintegration` database.
2. Go to the **Import** tab (or SQL tab).
3. Import/execute:
   - `database/add_foreign_keys.sql`

This script will:

- Ensure **PRIMARY KEY + AUTO_INCREMENT** on EAAPS tables:
  - `employees (employee_id)`
  - `departments (department_id)`
  - `job_positions (position_id)`
  - `attendance_logs (log_id)`
  - `leave_requests (leave_id)`
  - `qr_codes (qr_id)`
  - `payroll (payroll_id)`
  - `employee_schedules (id)`
  - `users_employee (id)`  ← important for user creation
- Add **foreign keys** between EAAPS tables (employees, departments, positions, users, leave, payroll, QR, schedules).

> If you see errors like `Duplicate key name` or `PRIMARY KEY already exists`, those are usually safe to ignore as long as the final structure is correct.

## 5. Configure Database Connection

EAAPS uses a connection helper `views/conn.php`.

Open `views/conn.php` and confirm the settings:

```php
$host = 'localhost';
$user = 'root';          // or your MySQL username
$pass = '';              // or your MySQL password
$dbname = 'systemintegration';
```

Adjust as needed for your environment (different DB name, password, etc.).

## 6. Install PHP Libraries (Composer)

The project uses Composer for backend libraries. The root `composer.json` contains:

```json
{
  "require": {
    "phpmailer/phpmailer": "^6.10",
    "kreait/firebase-php": "^5.26",
    "shuchkin/simplexlsx": "^1.1",
    "chillerlan/php-qrcode": "^5.0"
  }
}
```

### 6.1 If `vendor` Folder is Missing or Corrupted

If you copied the project to a new PC and the `vendor` folder is missing or incomplete:

1. Open a terminal (PowerShell / Command Prompt).
2. Set the working directory to the project root (do **not** type `cd` in the command here, just ensure you are in the correct folder):

   ```bash
   cd C:\xampp\htdocs\eaaps
   ```

3. Run Composer install:

   ```bash
   composer install
   ```

4. Composer will download and install all required libraries into `vendor/`.

If Composer is not installed, download it from **https://getcomposer.org/** and add it to your PATH.

### 6.2 Regenerating Autoload Files

If you updated dependencies or something seems out of sync:

```bash
composer dump-autoload
```

## 7. File/Folder Permissions (Windows)

On Windows/XAMPP, usually default permissions work. Ensure that the following folders are writable by PHP:

- `uploads/avatars/`
- `uploads/qrcodes/` (or similar for QR images)
- `uploads/proofs/`
- Any other upload directories used by the system.

If folders are missing, create them manually under the project root.

## 8. Running the Application

### Option A: Via Apache (recommended for XAMPP)

1. Start **Apache** and **MySQL** in XAMPP.
2. Open browser and navigate to:

   ```text
   http://localhost/eaaps/pages/dashboard.php
   ```

3. Log in using an existing admin/head_admin account from the `users_employee` table.

### Option B: PHP Built-in Server (for quick dev)

1. Open terminal in project root (`C:\xampp\htdocs\eaaps`).
2. Run:

   ```bash
   php -S localhost:8000
   ```

3. Open browser:

   ```text
   http://localhost:8000/pages/dashboard.php
   ```

> When using the built-in server, static assets and relative paths should still work because the entry scripts are inside `pages/` and `views/`.

## 9. Common Issues & Fixes

### 9.1 Blank Pages or JSON Errors

- Check `views/*` handlers (e.g., `employees.php`, `users_handler.php`, `departments_handler.php`).
- Enable `display_errors` in PHP (for debugging only, not on production) or check Apache error log:
  - `xampp\apache\logs\error.log`

### 9.2 QR Codes Not Generating

- Make sure `extension=gd` is enabled in `php.ini` and Apache was restarted.
- Ensure `vendor/chillerlan/php-qrcode` exists (run `composer install` if missing).
- Confirm that the QR output directory (e.g., `qrcodes/` or `uploads/qrcodes/`) is writable.

### 9.3 Missing Classes / Autoload Errors

Error examples:

- `Class "PHPMailer\PHPMailer\PHPMailer" not found`
- `Class "chillerlan\QRCode\QRCode" not found`
- `Class "Kreait\Firebase\Factory" not found`

Fix:

1. Check that `vendor/` exists in the project root.
2. If not, run `composer install`.
3. Make sure any `require 'vendor/autoload.php';` paths in your PHP files are correct (usually done inside a central bootstrap or in the specific modules that need them).

### 9.4 Database Foreign Key / AUTO_INCREMENT Issues

If user IDs or employee IDs are zero or foreign keys are not enforced:

1. Re-confirm that you imported `database/systemintegration.sql` first.
2. Then re-run `database/add_foreign_keys.sql` on the same database.
3. Verify that `users_employee.id` is **PRIMARY KEY + AUTO_INCREMENT** in phpMyAdmin.

---

For additional customization (roles, new modules, UI changes), follow the existing patterns in `views/*` and `pages/*`. This setup guide is focused on getting EAAPS running cleanly on a new machine.
