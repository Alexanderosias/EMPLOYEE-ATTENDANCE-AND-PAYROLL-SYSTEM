<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS Reports Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/reports.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <a class="sidebar-header" href="#">
        <img alt="Logo" class="logo" id="sidebarLogo" />
        <span class="app-name" id="sidebarAppName"></span>
      </a>
      <nav class="sidebar-nav">
        <ul>
          <li>
            <a href="dashboard.php">
              <img src="icons/home.png" alt="Dashboard" class="icon" />
              Dashboard
            </a>
          </li>
          <li>
            <a href="employees_page.php">
              <img src="icons/group.png" alt="Employees" class="icon" />
              Employees
            </a>
          </li>
          <li>
            <a href="schedule_page.php">
              <img src="icons/calendar-deadline-date.png" alt="Schedules" class="icon" />
              Schedules
            </a>
          </li>
          <li>
            <a href="department_position.php">
              <img src="icons/networking.png" alt="departments&Positions" class="icon" />
              Departments and Positions
            </a>
          </li>
          <li>
            <a href="attendance_logs_page.php">
              <img src="icons/clock.png" alt="Attendance Logs" class="icon" />
              Attendance Logs
            </a>
          </li>
          <li>
            <a href="payroll_page.php">
              <img src="icons/cash.png" alt="Payroll" class="icon" />
              Payroll
            </a>
          </li>
          <li>
            <a href="qr_codes_and_snapshots.php">
              <img src="icons/snapshot.png" alt="Qr&Snapshots" class="icon" />
              QR and Snapshots
            </a>
          </li>
          <li class="active">
            <a href="#">
              <img src="icons/clipboard.png" alt="Reports" class="icon" />
              Reports
            </a>
          </li>
          <li>
            <a href="profile_details_page.php">
              <img src="icons/user.png" alt="Profile" class="icon" />
              Profile
            </a>
          </li>
          <?php
          $userRoles = $_SESSION['roles'] ?? [];
          if (in_array('head_admin', $userRoles)):
          ?>
            <li>
              <a href="user_page.php">
                <img src="icons/add-user.png" alt="Users" class="icon" />
                Users
              </a>
            </li>
            <li>
              <a href="settings_page.php">
                <img src="icons/coghweel.png" alt="Settings" class="icon" />
                Settings
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>

      <a class="logout-btn" href="../index.html">
        <img src="icons/sign-out-option.png" alt="Logout" class="logout-icon" />
        Logout
      </a>
    </aside>

    <main class="main-content">
      <header class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <div>
            <h2>Reports</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>

      <div class="scrollbar-container">
        <article class="reports-content" aria-label="Reports content">

          <!-- Attendance Reports -->
          <section class="report-card" id="attendance-report">
            <h3>Attendance Reports</h3>
            <select class="frequency-select" aria-label="Select attendance report frequency">
              <option value="daily">Daily Attendance Report</option>
              <option value="weekly">Weekly Attendance Report</option>
              <option value="biweekly">Bi-Monthly / 15-Day Report</option>
              <option value="monthly">Monthly Attendance Report</option>
              <option value="yearly">Yearly Attendance Summary</option>
              <option value="employee">Employee Attendance Record</option>
            </select>
            <div class="btn-container">
              <button class="generate-btn"><i class="fas fa-file-alt"></i> Generate</button>
              <button class="print-btn" disabled><i class="fas fa-print"></i> Print</button>
            </div>
            <pre class="report-output" aria-live="polite" aria-atomic="true" tabindex="0">
Select a report and click Generate.
          </pre>
          </section>

          <!-- Payroll Reports -->
          <section class="report-card" id="payroll-report">
            <h3>Payroll Reports</h3>
            <select class="frequency-select" aria-label="Select payroll report frequency">
              <option value="register">Payroll Register Report</option>
              <option value="payslip">Payslip Report</option>
              <option value="overtime">Overtime Report</option>
              <option value="deductions">Deductions Report</option>
              <option value="bonus">13th Month / Bonus Report</option>
              <option value="yearend">Year-End Payroll Summary</option>
            </select>
            <div class="btn-container">
              <button class="generate-btn"><i class="fas fa-file-alt"></i> Generate</button>
              <button class="print-btn" disabled><i class="fas fa-print"></i> Print</button>
            </div>
            <pre class="report-output" aria-live="polite" aria-atomic="true" tabindex="0">
Select a report and click Generate.
          </pre>
          </section>

          <!-- Other Useful Reports -->
          <section class="report-card" id="other-report">
            <h3>Other Useful Reports</h3>
            <select class="frequency-select" aria-label="Select other report frequency">
              <option value="leave">Leave Report</option>
              <option value="deptpos">Department/Position-wise Report</option>
              <option value="performance">Employee Performance Report (Attendance-Based)</option>
              <option value="audit">Audit Report</option>
            </select>
            <div class="btn-container">
              <button class="generate-btn"><i class="fas fa-file-alt"></i> Generate</button>
              <button class="print-btn" disabled><i class="fas fa-print"></i> Print</button>
            </div>
            <pre class="report-output" aria-live="polite" aria-atomic="true" tabindex="0">
Select a report and click Generate.
          </pre>
          </section>

        </article>
      </div>
    </main>
  </div>

  <script src="../js/current_time.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/reports.js"></script>
  <script src="../js/auto_logout.js"></script>
</body>

</html>