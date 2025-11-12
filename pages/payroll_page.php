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
        <img src="img/adfc_logo_by_jintokai_d4pchwp-fullview.png" alt="Logo" class="logo" />
        <span class="app-name">EAAPS Admin</span>
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
          <?php if ($_SESSION['role'] === 'head_admin'): ?>
            <li>
              <a href="user_page.php">
                <img src="icons/add-user.png" alt="Users" class="icon" />
                Users
              </a>
            </li>
          <?php endif; ?>
          <li>
            <a href="settings_page.php">
              <img src="icons/coghweel.png" alt="Settings" class="icon" />
              Settings
            </a>
          </li>
        </ul>
      </nav>

      <a class="logout-btn" href="../index.html">
        <img src="icons/sign-out-option.png" alt="Logout" class="logout-icon" />
        Logout
      </a>
    </aside>

    <main class="main-content">
      <header class="dashboard-header">
        <h2>PAYROLL</h2>
        <p id="current-datetime" aria-live="polite"></p>
      </header>

      <section class="summary-cards" aria-label="Payroll summary">
        <div class="summary-card blue">
          <p class="label">Total Gross Pay</p>
          <p class="value" id="total-gross-pay">₱0.00</p>
        </div>

        <div class="summary-card red">
          <p class="label">Total Taxes & Deductions</p>
          <p class="value" id="total-deductions">₱0.00</p>
        </div>

        <div class="summary-card green">
          <p class="label">Total Net Pay</p>
          <p class="value" id="total-net-pay">₱0.00</p>
        </div>
      </section>

      <section class="payroll-dashboard" aria-label="Payroll dashboard">
        <div class="payroll-header">
          <div>
            <label for="payroll-schedule">Payroll Schedule</label>
            <select id="payroll-schedule" name="payroll-schedule">
              <option value="" disabled selected>Select payroll schedule</option>
              <option value="biweekly">Every 15 Days (Bi-weekly)</option>
              <option value="monthly">Every 1 Month (Monthly)</option>
              <option value="weekly">Every 7 Days (Weekly)</option>
              <option value="daily">Daily</option>
            </select>
          </div>

          <div>
            <p>Next Payroll: <span>11/12/25</span></p>
          </div>
        </div>

        <section class="tax-settings" aria-label="Tax rate settings">
          <div id="tax-settings-header" tabindex="0" role="button" aria-expanded="false"
            aria-controls="tax-settings-content" class="tax-settings-header"
            aria-label="Toggle tax rate settings">
            Tax Rate Settings
            <svg id="toggle-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
              fill="none" stroke="#374151" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"
              style="transition: transform 0.3s ease;">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>

          <div id="tax-settings-content" class="tax-settings-content">
            <div class="tax-sections-row">
              <div class="section">
                <h3>
                  PhilHealth
                  <a href="https://www.philhealth.gov.ph/" target="_blank" rel="noopener"
                    class="tax-link" aria-label="PhilHealth official site">🔗</a>
                </h3>
                <label for="philhealth-rate">Rate</label>
                <input type="number" step="0.001" id="philhealth-rate" value="0.05" />
                <label for="philhealth-floor">Salary Floor</label>
                <input type="number" id="philhealth-floor" value="10000" />
                <label for="philhealth-ceiling">Salary Ceiling</label>
                <input type="number" id="philhealth-ceiling" value="100000" />
                <label for="philhealth-fixed-floor">Fixed Amount (Floor)</label>
                <input type="number" id="philhealth-fixed-floor" value="500" />
                <label for="philhealth-fixed-ceiling">Fixed Amount (Ceiling)</label>
                <input type="number" id="philhealth-fixed-ceiling" value="5000" />
              </div>

              <div class="section">
                <h3>
                  Pag-IBIG
                  <a href="https://www.pagibigfund.gov.ph/" target="_blank" rel="noopener"
                    class="tax-link" aria-label="Pag-IBIG official site">🔗</a>
                </h3>
                <label for="pagibig-rate">Employee Rate</label>
                <input type="number" step="0.001" id="pagibig-rate" value="0.02" />
                <label for="pagibig-low-rate">Low-Income Rate</label>
                <input type="number" step="0.001" id="pagibig-low-rate" value="0.01" />
                <label for="pagibig-threshold">Low-Income Threshold</label>
                <input type="number" id="pagibig-threshold" value="1500" />
              </div>
            </div>

            <div class="section">
              <h3>
                SSS Contribution Table (JSON)
                <a href="https://www.sss.gov.ph/" target="_blank" rel="noopener"
                  class="tax-link" aria-label="SSS official site">🔗</a>
              </h3>

              <textarea id="sss-table" aria-label="SSS Contribution Table JSON"
                spellcheck="false" rows="10" style="font-family: monospace; font-size: 0.85rem;">
[
  {"salaryRange":[0,3249.99],"sssContribution":135},
  {"salaryRange":[3250,3749.99],"sssContribution":157.5},
  {"salaryRange":[3750,4249.99],"sssContribution":180},
  {"salaryRange":[4250,4749.99],"sssContribution":202.5},
  {"salaryRange":[4750,5249.99],"sssContribution":225},
  {"salaryRange":[5250,5749.99],"sssContribution":247.5},
  {"salaryRange":[5750,6249.99],"sssContribution":270},
  {"salaryRange":[6250,6749.99],"sssContribution":292.5},
  {"salaryRange":[6750,7249.99],"sssContribution":315},
  {"salaryRange":[7250,7749.99],"sssContribution":337.5},
  {"salaryRange":[7750,8249.99],"sssContribution":360},
  {"salaryRange":[8250,8749.99],"sssContribution":382.5},
  {"salaryRange":[8750,9249.99],"sssContribution":405},
  {"salaryRange":[9250,9749.99],"sssContribution":427.5},
  {"salaryRange":[9750,10249.99],"sssContribution":450},
  {"salaryRange":[10250,10749.99],"sssContribution":472.5},
  {"salaryRange":[10750,11249.99],"sssContribution":495},
  {"salaryRange":[11250,11749.99],"sssContribution":517.5},
  {"salaryRange":[11750,12249.99],"sssContribution":540},
  {"salaryRange":[12250,12749.99],"sssContribution":562.5},
  {"salaryRange":[12750,13249.99],"sssContribution":585},
  {"salaryRange":[13250,13749.99],"sssContribution":607.5},
  {"salaryRange":[13750,14249.99],"sssContribution":630},
  {"salaryRange":[14250,14749.99],"sssContribution":652.5},
  {"salaryRange":[14750,15249.99],"sssContribution":675},
  {"salaryRange":[15250,15749.99],"sssContribution":697.5},
  {"salaryRange":[15750,16249.99],"sssContribution":720},
  {"salaryRange":[16250,16749.99],"sssContribution":742.5},
  {"salaryRange":[16750,17249.99],"sssContribution":765},
  {"salaryRange":[17250,17749.99],"sssContribution":787.5},
  {"salaryRange":[17750,18249.99],"sssContribution":810},
  {"salaryRange":[18250,18749.99],"sssContribution":832.5},
  {"salaryRange":[18750,19249.99],"sssContribution":855},
  {"salaryRange":[19250,19749.99],"sssContribution":877.5},
  {"salaryRange":[19750,20249.99],"sssContribution":900},
  {"salaryRange":[20250,20749.99],"sssContribution":922.5},
  {"salaryRange":[20750,21249.99],"sssContribution":945},
  {"salaryRange":[21250,21749.99],"sssContribution":967.5},
  {"salaryRange":[21750,22249.99],"sssContribution":990}
]
              </textarea>
            </div>

            <button id="save-tax-rates-btn" class="tax-settings-save-btn" type="button">
              Save Tax Rates
            </button>
          </div>
        </section>

        <div class="below-settings">
          <div class="toggle-container">
            <label class="toggle-label">Auto-Apply All Government Deductions</label>
            <label class="toggle-switch">
              <input type="checkbox" id="autoApplyDeductions" checked />
              <span class="slider"></span>
            </label>
          </div>

          <button id="mark-as-paid-btn" class="mark-paid-btn" disabled type="button">
            Mark all as Paid
          </button>
        </div>

        <div class="payroll-table-container" aria-label="Payroll records table">
          <table class="payroll-table" role="table" aria-describedby="pay-period-display">
            <thead>
              <tr>
                <th scope="col">Employee Name</th>
                <th scope="col">Gross Pay</th>
                <th scope="col">PhilHealth</th>
                <th scope="col">SSS</th>
                <th scope="col">Pag-IBIG</th>
                <th scope="col">Other Deductions</th>
                <th scope="col">Total Deductions</th>
                <th scope="col">Net Pay</th>
                <th scope="col">Action</th>
              </tr>
            </thead>

            <tbody id="payroll-table-body">
              <!-- Rows inserted by JS -->
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <script src="../js/payroll.js"></script>
  <script src="../js/current_time.js"></script>
</body>

</html>