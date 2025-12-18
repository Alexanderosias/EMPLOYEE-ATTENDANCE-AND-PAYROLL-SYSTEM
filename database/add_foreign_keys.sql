-- ============================================================
-- FOREIGN KEY CONSTRAINTS FOR EAAPS TABLES IN SYSTEMINTEGRATION
-- ============================================================
-- This script adds all missing foreign key relationships for EAAPS tables
-- Run this after importing systemintegration.sql

-- ============================================================
-- 1. EMPLOYEES TABLE FOREIGN KEYS
-- ============================================================

-- Link employees to departments
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_department` 
  FOREIGN KEY (`department_id`) 
  REFERENCES `departments` (`department_id`) 
  ON UPDATE CASCADE 
  ON DELETE SET NULL;

-- Link employees to job_positions
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_position` 
  FOREIGN KEY (`position_id`) 
  REFERENCES `job_positions` (`position_id`) 
  ON UPDATE CASCADE 
  ON DELETE SET NULL;

-- Link employees to users_employee (for login/user account)
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_user` 
  FOREIGN KEY (`user_id`) 
  REFERENCES `users_employee` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- ============================================================
-- 2. ATTENDANCE_LOGS TABLE FOREIGN KEYS
-- ============================================================

-- Link attendance_logs to employees
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_attendance_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- ============================================================
-- 3. LEAVE_REQUESTS TABLE FOREIGN KEYS
-- ============================================================

-- Link leave_requests to employees
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- Link leave_requests approved_by to users_employee
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_approver` 
  FOREIGN KEY (`approved_by`) 
  REFERENCES `users_employee` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

-- ============================================================
-- 4. QR_CODES TABLE FOREIGN KEYS
-- ============================================================

-- Link qr_codes to employees
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qrcodes_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- ============================================================
-- 5. PAYROLL TABLE FOREIGN KEYS
-- ============================================================

-- Link payroll to employees
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

-- ============================================================
-- 6. EMPLOYEE_SCHEDULES TABLE FOREIGN KEYS (already exists)
-- ============================================================
-- Already has: fk_employee_schedules_employee

-- ============================================================
-- 7. ADDITIONAL TABLES (if they exist in systemintegration)
-- ============================================================

-- If overtime_requests table exists:
-- ALTER TABLE `overtime_requests`
--   ADD CONSTRAINT `fk_ot_employee` 
--   FOREIGN KEY (`employee_id`) 
--   REFERENCES `employees` (`employee_id`) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;

-- If snapshots table exists:
-- ALTER TABLE `snapshots`
--   ADD CONSTRAINT `fk_snapshots_attendance` 
--   FOREIGN KEY (`attendance_log_id`) 
--   REFERENCES `attendance_logs` (`log_id`) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;

-- ============================================================
-- PRIMARY KEYS (must be set BEFORE AUTO_INCREMENT)
-- ============================================================
-- Note: If PRIMARY KEY already exists, these will fail (which is ok)
-- You can ignore "Duplicate key name" errors

ALTER TABLE `employees` ADD PRIMARY KEY (`employee_id`);
ALTER TABLE `departments` ADD PRIMARY KEY (`department_id`);
ALTER TABLE `job_positions` ADD PRIMARY KEY (`position_id`);
ALTER TABLE `attendance_logs` ADD PRIMARY KEY (`log_id`);
ALTER TABLE `leave_requests` ADD PRIMARY KEY (`leave_id`);
ALTER TABLE `qr_codes` ADD PRIMARY KEY (`qr_id`);
ALTER TABLE `payroll` ADD PRIMARY KEY (`payroll_id`);
ALTER TABLE `employee_schedules` ADD PRIMARY KEY (`id`);
ALTER TABLE `users_employee` ADD PRIMARY KEY (`id`);

-- ============================================================
-- AUTO_INCREMENT (set AFTER PRIMARY KEYS are defined)
-- ============================================================
-- These will only work if PRIMARY KEY exists

ALTER TABLE `employees` MODIFY `employee_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `departments` MODIFY `department_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `job_positions` MODIFY `position_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `attendance_logs` MODIFY `log_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `leave_requests` MODIFY `leave_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `qr_codes` MODIFY `qr_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payroll` MODIFY `payroll_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `employee_schedules` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users_employee` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;

COMMIT;
