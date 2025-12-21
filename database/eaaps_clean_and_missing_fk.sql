-- ============================================================
-- EAAPS CLEANUP AND MISSING FOREIGN KEYS
-- ============================================================
-- Run this AFTER importing systemintegration.sql and running:
--   1) add_primary_keys_auto_increment.sql
--
-- This script does three things for EAAPS tables:
--   - Cleans duplicate / orphan rows that would block constraints
--   - Enforces the UNIQUE constraint for thirteenth_month_payroll
--   - Adds the remaining foreign keys that existed in eaaps_db.sql
--     but are not present in systemintegration.sql (overtime_requests,
--     snapshots).
--
-- NOTE: Run this once on a clean systemintegration-based database.
--       If you run it again and the index / FKs already exist, you may
--       see benign "duplicate key" / "duplicate constraint" errors.
-- ============================================================

START TRANSACTION;

-- ============================================================
-- 1. CLEAN DUPLICATE 13TH MONTH RECORDS
-- ============================================================
-- Old EAAPS allowed multiple rows per (employee_id, year).
-- Keep the lowest id per (employee_id, year), delete the rest.

DELETE t1
FROM thirteenth_month_payroll t1
JOIN thirteenth_month_payroll t2
  ON t1.employee_id = t2.employee_id
 AND t1.year = t2.year
 AND t1.id > t2.id;

-- ============================================================
-- 2. CLEAN ORPHAN OVERTIME_REQUESTS AND SNAPSHOTS
-- ============================================================
-- Remove rows whose employee_id / attendance_log_id no longer exist
-- so that foreign keys can be applied without errors.

-- Orphan overtime_requests by employee
DELETE ot
FROM overtime_requests ot
LEFT JOIN employees e ON ot.employee_id = e.employee_id
WHERE ot.employee_id IS NOT NULL
  AND e.employee_id IS NULL;

-- Orphan overtime_requests by attendance_log
DELETE ot
FROM overtime_requests ot
LEFT JOIN attendance_logs al ON ot.attendance_log_id = al.log_id
WHERE ot.attendance_log_id IS NOT NULL
  AND al.log_id IS NULL;

-- Orphan snapshots by attendance_log
DELETE s
FROM snapshots s
LEFT JOIN attendance_logs al ON s.attendance_log_id = al.log_id
WHERE s.attendance_log_id IS NOT NULL
  AND al.log_id IS NULL;

-- ============================================================
-- 3. ENFORCE UNIQUE 13TH MONTH PER EMPLOYEE/YEAR
-- ============================================================

ALTER TABLE `thirteenth_month_payroll`
  ADD UNIQUE KEY `uniq_thirteenth_employee_year` (`employee_id`, `year`);

-- ============================================================
-- 4. ADD MISSING FOREIGN KEYS (FROM eaaps_db.sql)
-- ============================================================
-- These relationships existed in the old EAAPS schema but are
-- missing in systemintegration.sql. Column names are updated to
-- match the new schema (log_id, employee_id).

-- Link overtime_requests to attendance_logs and employees
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `fk_ot_attendance` 
    FOREIGN KEY (`attendance_log_id`) 
    REFERENCES `attendance_logs` (`log_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ot_employee` 
    FOREIGN KEY (`employee_id`) 
    REFERENCES `employees` (`employee_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Link snapshots to attendance_logs
ALTER TABLE `snapshots`
  ADD CONSTRAINT `fk_snapshots_attendance` 
    FOREIGN KEY (`attendance_log_id`) 
    REFERENCES `attendance_logs` (`log_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

COMMIT;
