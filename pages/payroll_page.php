<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS Payroll Page - Main Content</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/payroll.css" />
  <link rel="stylesheet" href="css/status-message.css" />
</head>

<body>
  <div id="status-message" class="status-message" role="alert" aria-live="assertive"></div>

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
          <li class="active">
            <a href="#">
              <img src="icons/cash.png" alt="Payroll" class="icon" />
              Payroll
            </a>
          </li>
          <li>
            <a href="leave_page.php">
              <img src="icons/swap.png" alt="Leave" class="icon" />
              Leave
            </a>
          </li>
          <?php
          $userRoles = $_SESSION['roles'] ?? [];
          if (in_array('head_admin', $userRoles)):
          ?>
            <li>
              <a href="holidays_events_page.php">
                <img src="icons/holiday.png" alt="Holidays and Events" class="icon" />
                Holidays and Events
              </a>
            </li>
          <?php endif; ?>
          <li>
            <a href="qr_codes_and_snapshots.php">
              <img src="icons/snapshot.png" alt="Qr&Snapshots" class="icon" />
              QR and Snapshots
            </a>
          </li>
          <li>
            <a href="reports_page.php">
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
            <h2>Manage Payroll</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>

      <div class="scrollbar-container">
        <!-- Summary Cards -->
        <div class="summary-cards">
          <div class="summary-card">
            <p class="label">Total Gross</p>
            <p class="value" id="sum-gross">₱0.00</p>
          </div>
          <div class="summary-card">
            <p class="label">Total Deductions</p>
            <p class="value" id="sum-deductions">₱0.00</p>
          </div>
          <div class="summary-card green">
            <p class="label">Net Pay</p>
            <p class="value" id="sum-net">₱0.00</p>
          </div>
          <div class="summary-card">
            <p class="label">Pay Period</p>
            <p class="value" id="sum-period">—</p>
          </div>
        </div>

        <!-- Filters / Controls -->
        <div class="payroll-header">
          <div>
            <label for="period-start">Pay Period</label>
            <div style="display:flex; gap:0.5rem; align-items:center;">
              <input type="date" id="period-start" />
              <span>to</span>
              <input type="date" id="period-end" />
            </div>
          </div>
          <div>
            <label for="freq-select">Frequency</label>
            <select id="freq-select">
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="bi-weekly">Bi-Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
            <span class="pay-period-display" id="period-display"></span>
          </div>
          <div>
            <label for="role-filter">Job Role</label>
            <select id="role-filter">
              <option value="">All Roles</option>
            </select>
          </div>
          <button type="button" id="btn-recalc" class="mark-paid-btn" style="margin-top:24px;">Recalculate Preview</button>
        </div>

        <!-- Next Payroll per Role -->
        <section style="margin-bottom:1.5rem;">
          <h3 class="text-xl font-semibold text-gray-800" style="margin: 0 0 0.75rem 2px;">Next Payroll per Role</h3>
          <div class="payroll-table-container">
            <table class="payroll-table">
              <thead>
                <tr>
                  <th>Job Role</th>
                  <th>Frequency</th>
                  <th>Next Payroll Date</th>
                  <th>Period Window</th>
                </tr>
              </thead>
              <tbody id="role-next-payroll-body">
                <tr>
                  <td colspan="4" style="text-align:center; color:#6b7280;">No data yet (UI only)</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Flexible Deductions Panel -->
        <section class="deductions-card">
          <div class="deductions-card-header">
            <h3>Deductions</h3>
            <p class="muted">Add any deduction type (government or custom).</p>
          </div>
          <div class="deductions-rows-header">
            <div>Type</div>
            <div>Label</div>
            <div>Amount (₱)</div>
            <div>Scope</div>
            <div>Recurring</div>
            <div>Action</div>
          </div>
          <div id="deduction-rows" class="deductions-rows">
            <div class="deduction-row">
              <select>
                <option>SSS</option>
                <option>PhilHealth</option>
                <option>Pag-IBIG</option>
                <option>Tax</option>
                <option>Loan</option>
                <option>Other</option>
              </select>
              <input type="text" placeholder="e.g., Union Fee" />
              <input type="number" step="0.01" placeholder="0.00" />
              <select>
                <option>Per Employee</option>
                <option>Per Role</option>
                <option>Global</option>
              </select>
              <label class="recurring"><input type="checkbox" /> Yes</label>
              <button type="button" class="btn-remove-row">Remove</button>
            </div>
          </div>
          <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
            <button type="button" id="btn-add-deduction" class="tax-settings-save-btn" style="max-width:none;">Add Deduction</button>
          </div>
        </section>

        <!-- Payroll Preview -->
        <section>
          <h3 class="text-xl font-semibold text-gray-800" style="margin: 0 0 0.75rem 2px;">Payroll Preview</h3>
          <div class="payroll-table-container">
            <table class="payroll-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Role</th>
                  <th>Hours/Days</th>
                  <th>Gross Pay</th>
                  <th>Deductions</th>
                  <th>Net Pay</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="payroll-preview-body">
                <tr>
                  <td colspan="7" style="text-align:center; color:#6b7280;">Preview will appear here after recalculation (UI only).</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script src="../js/payroll.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/auto_logout.js"></script>

</body>

</html>