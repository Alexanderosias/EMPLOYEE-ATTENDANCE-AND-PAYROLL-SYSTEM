START TRANSACTION;

ALTER TABLE `departments`            ADD PRIMARY KEY (`department_id`);
ALTER TABLE `job_positions`          ADD PRIMARY KEY (`position_id`);
ALTER TABLE `attendance_logs`        ADD PRIMARY KEY (`log_id`);
ALTER TABLE `leave_requests`         ADD PRIMARY KEY (`leave_id`);
ALTER TABLE `qr_codes`               ADD PRIMARY KEY (`qr_id`);
ALTER TABLE `payroll`                ADD PRIMARY KEY (`payroll_id`);
ALTER TABLE `employee_schedules`     ADD PRIMARY KEY (`id`);
ALTER TABLE `users_employee`         ADD PRIMARY KEY (`id`);
ALTER TABLE `snapshots`              ADD PRIMARY KEY (`snapshot_id`);
ALTER TABLE `thirteenth_month_payroll` ADD PRIMARY KEY (`id`);
ALTER TABLE `payroll_audit`          ADD PRIMARY KEY (`audit_id`);


ALTER TABLE `employees`              MODIFY `employee_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `departments`            MODIFY `department_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `job_positions`          MODIFY `position_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `attendance_logs`        MODIFY `log_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `leave_requests`         MODIFY `leave_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `qr_codes`               MODIFY `qr_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payroll`                MODIFY `payroll_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `employee_schedules`     MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users_employee`         MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `snapshots`              MODIFY `snapshot_id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `thirteenth_month_payroll` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payroll_audit`          MODIFY `audit_id` INT(11) NOT NULL AUTO_INCREMENT;



ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_department` 
  FOREIGN KEY (`department_id`) 
  REFERENCES `departments` (`department_id`) 
  ON UPDATE CASCADE 
  ON DELETE SET NULL;


ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_position` 
  FOREIGN KEY (`position_id`) 
  REFERENCES `job_positions` (`position_id`) 
  ON UPDATE CASCADE 
  ON DELETE SET NULL;

ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_user` 
  FOREIGN KEY (`user_id`) 
  REFERENCES `users_employee` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_attendance_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;


ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;


ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_approver` 
  FOREIGN KEY (`approved_by`) 
  REFERENCES `users_employee` (`id`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qrcodes_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` 
  FOREIGN KEY (`employee_id`) 
  REFERENCES `employees` (`employee_id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;



DELETE t1
FROM thirteenth_month_payroll t1
JOIN thirteenth_month_payroll t2
  ON t1.employee_id = t2.employee_id
 AND t1.year = t2.year
 AND t1.id > t2.id;


DELETE ot
FROM overtime_requests ot
LEFT JOIN employees e ON ot.employee_id = e.employee_id
WHERE ot.employee_id IS NOT NULL
  AND e.employee_id IS NULL;


DELETE ot
FROM overtime_requests ot
LEFT JOIN attendance_logs al ON ot.attendance_log_id = al.log_id
WHERE ot.attendance_log_id IS NOT NULL
  AND al.log_id IS NULL;


DELETE s
FROM snapshots s
LEFT JOIN attendance_logs al ON s.attendance_log_id = al.log_id
WHERE s.attendance_log_id IS NOT NULL
  AND al.log_id IS NULL;


ALTER TABLE `thirteenth_month_payroll`
  ADD UNIQUE KEY `uniq_thirteenth_employee_year` (`employee_id`, `year`);

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

ALTER TABLE `snapshots`
  ADD CONSTRAINT `fk_snapshots_attendance` 
    FOREIGN KEY (`attendance_log_id`) 
    REFERENCES `attendance_logs` (`log_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

COMMIT;
