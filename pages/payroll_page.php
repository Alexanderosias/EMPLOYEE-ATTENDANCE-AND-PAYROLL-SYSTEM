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
  <link rel="stylesheet" href="src/styles.css" />
  <link rel="stylesheet" href="css/status-message.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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

        <!-- Next Payroll per Role -->
        <section style="margin-bottom:1.5rem; margin-top: 30px;">
          <h3 class="text-xl font-semibold text-gray-800" style="margin: 0 0 0.75rem 2px;">Next Payroll per Role</h3>
          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.5rem 2px; gap:0.75rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <div>
              Show
              <select id="role-next-page-size">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              entries
            </div>
          </div>
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
          <div style="display:flex; justify-content:flex-end; align-items:center; margin: 0.25rem 0 0 2px; gap:0.25rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <button type="button" id="role-next-page-prev" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Prev</button>
            <span id="role-next-page-info">Page 1 of 1</span>
            <button type="button" id="role-next-page-next" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Next</button>
          </div>
        </section>

        <!-- Filters / Controls -->
        <section class="payroll-filters-card">
          <div class="payroll-filters-card-header">
            <h3 class="text-xl font-semibold text-gray-800" style="margin: 0;">Payroll Filters &amp; Schedule</h3>
            <button type="button" id="toggle-filters" class="filters-toggle-btn" aria-expanded="true">Hide filters</button>
          </div>
          <div class="payroll-filters-card-body" id="payroll-filters-body">
            <div class="payroll-header">
              <div style="width: 35%;">
                <div>
                  <label for="period-start">Pay Period</label>
                  <div style="display:flex; gap:0.5rem; align-items:center; flex-direction: column;">
                    <input type="date" id="period-start" />
                    <span>to</span>
                    <input type="date" id="period-end" />
                  </div>
                </div>
              </div>
              <div style="width: 35%;">
                <div style="display:flex; gap:0.5rem; margin-left: 20px; flex-direction: column;">
                  <div>
                    <label for="role-filter">Job Role</label>
                    <select id="role-filter">
                      <option value="">All Roles</option>
                    </select>
                  </div>
                  <div>
                    <label for="freq-select">Payroll Frequency</label>
                    <select id="freq-select">
                      <option value="">—</option>
                      <option value="weekly">Weekly</option>
                      <option value="bi-weekly">Bi-Weekly</option>
                      <option value="monthly">Monthly</option>
                    </select>
                  </div>  
                </div>
              </div>
              <div style="width: 30%; display: flex; flex-direction: column; justify-content: flex-start; gap: 20px; margin-left: 20px; margin-top: 30px; align-self: flex-start;">
                <button type="button" id="btn-recalc" style="width: 180px;" class="mark-paid-btn">Recalculate Preview</button>
                <button type="button" id="btn-finalize" style="width: 180px;" class="mark-paid-btn">Finalize Payroll</button>
              </div>
            </div>
            <div class="payroll-period-helper">
              <span class="pay-period-display" id="period-display"></span>
            </div>
          </div> <!-- end payroll-filters-card-body -->
        </section> <!-- end payroll-filters-card -->

        <!-- Payroll Preview -->
        <section>
          <h3 class="text-xl font-semibold text-gray-800" style="margin: 0 0 0.75rem 2px;">Payroll Preview</h3>
          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.5rem 2px; gap:0.75rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <div>
              Show
              <select id="preview-page-size">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              entries
            </div>
            <button type="button" id="btn-export-preview" class="pagination-btn" style="padding:4px 12px; font-size:0.8rem;">Export CSV</button>
          </div>

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
          <div style="display:flex; justify-content:flex-end; align-items:center; margin: 0.25rem 0 0 2px; gap:0.25rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <button type="button" id="preview-page-prev" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Prev</button>
            <span id="preview-page-info">Page 1 of 1</span>
            <button type="button" id="preview-page-next" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Next</button>
          </div>
        </section>

        <!-- Flexible Deductions Panel -->
        <section class="deductions-card">
          <div class="deductions-card-header">
            <h3>Deductions</h3>
            <p class="muted">Set manual or additional deductions by type.</p>
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
              <div>
                <span>Other</span>
                <input type="hidden" class="ded-type" value="Other" />
              </div>
              <input type="text" class="ded-label" placeholder="Optional label" />
              <input type="number" step="0.01" class="ded-amount" placeholder="0.00" />
              <div class="ded-scope-wrapper">
                <select class="ded-scope">
                  <option value="per_employee">Per Employee</option>
                  <option value="per_role">Per Role</option>
                  <option value="global">Global</option>
                </select>
                <div class="ded-scope-roles"></div>
              </div>
              <label class="recurring"><input type="checkbox" class="ded-recurring" /> Yes</label>
              <div style="text-align:center; font-size:0.8rem; color:#6b7280;">—</div>
            </div>
          </div>
          <div style="margin-top:0.75rem;">
            <button type="button" id="btn-add-deduction" class="mark-paid-btn">Add Deduction</button>
          </div>
          <p style="margin-top:0.5rem; font-size:0.8rem; color:#6b7280;">
            <strong>Legend:</strong> Government contributions (SSS, PhilHealth, Pag-IBIG, Income Tax) are computed automatically from settings. Use the <strong>Other</strong> row for manual company or employee-specific deductions (e.g., loans, penalties, adjustments).
          </p>
        </section>

        <!-- Finalized Payroll Records -->
        <section style="margin-top:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.75rem 2px; gap:0.75rem; flex-wrap:wrap;">
            <h3 class="text-xl font-semibold text-gray-800" style="margin: 0;">Finalized Payroll Records</h3>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
              <button type="button" id="btn-refresh-finalized" class="mark-paid-btn">Refresh Records</button>
              <button type="button" id="btn-mark-period-paid" class="mark-paid-btn">Mark ALL as Paid</button>
            </div>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.5rem 2px; gap:0.75rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <div>
              Show
              <select id="finalized-page-size">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              entries
            </div>
            <button type="button" id="btn-export-finalized" class="pagination-btn" style="padding:4px 12px; font-size:0.8rem;">Export CSV</button>
          </div>

          <div class="payroll-table-container">
            <table class="payroll-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Role</th>
                  <th>Period</th>
                  <th>Net Pay</th>
                  <th>Paid Status</th>
                  <th>Payment Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="payroll-finalized-body">
                <tr>
                  <td colspan="7" style="text-align:center; color:#6b7280;">No finalized records loaded yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div style="display:flex; justify-content:flex-end; align-items:center; margin: 0.25rem 0 0 2px; gap:0.25rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <button type="button" id="finalized-page-prev" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Prev</button>
            <span id="finalized-page-info">Page 1 of 1</span>
            <button type="button" id="finalized-page-next" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Next</button>
          </div>
        </section>

        <!-- 13th Month Preview -->
        <section style="margin-top:1.5rem;">

          <h3 class="text-xl font-semibold text-gray-800" style="margin: 0 0 0.5rem 2px;">13th Month Preview</h3>
          <div style="margin: 0 0 0.75rem 2px; font-size:0.9rem; color:#374151; display:flex; gap:1.5rem; flex-wrap:wrap;">
            <div>Year Basic Total: <span id="thirteenth-total-basic">₱0.00</span></div>
            <div>Year 13th Month Total: <span id="thirteenth-total-13th">₱0.00</span></div>
          </div>
          <div class="payroll-header" style="margin-bottom: 0.75rem; display: flex; align-items: center;">
            <div>
              <label for="thirteenth-year">Year</label>
              <input type="number" id="thirteenth-year" min="2000" max="2100" />
            </div>
            <div>
              <label for="thirteenth-role">Job Role</label>
              <select id="thirteenth-role">
                <option value="">All Roles</option>
              </select>
            </div>
            <div style="display:flex; gap:0.5rem; align-self: flex-end;">
              <button type="button" id="btn-thirteenth-recalc" class="mark-paid-btn">Recalculate 13th Month</button>
              <button type="button" id="btn-thirteenth-finalize" class="mark-paid-btn">Finalize 13th Month</button>
            </div>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.5rem 2px; gap:0.75rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <div>
              Show
              <select id="thirteenth-page-size">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              entries
            </div>
            <button type="button" id="btn-export-thirteenth-preview" class="pagination-btn" style="padding:4px 12px; font-size:0.8rem;">Export CSV</button>
          </div>

          <div class="payroll-table-container">
            <table class="payroll-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Role</th>
                  <th>Total Basic (Year)</th>
                  <th>13th Month</th>
                </tr>
              </thead>
              <tbody id="thirteenth-body">
                <tr>
                  <td colspan="4" style="text-align:center; color:#6b7280;">13th month preview will appear here.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div style="display:flex; justify-content:flex-end; align-items:center; margin: 0.25rem 0 0 2px; gap:0.25rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <button type="button" id="thirteenth-page-prev" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Prev</button>
            <span id="thirteenth-page-info">Page 1 of 1</span>
            <button type="button" id="thirteenth-page-next" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Next</button>
          </div>
        </section>

        <!-- Finalized 13th Month Records -->
        <section style="margin-top:1.5rem;">

          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.75rem 2px; gap:0.75rem; flex-wrap:wrap;">
            <h3 class="text-xl font-semibold text-gray-800" style="margin: 0;">Finalized 13th Month Records</h3>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
              <button type="button" id="btn-thirteenth-refresh" class="mark-paid-btn">Refresh Records</button>
              <button type="button" id="btn-thirteenth-mark-year-paid" class="mark-paid-btn">Mark ALL as Paid</button>
            </div>
          </div>
          <p style="margin: 0 0 0.5rem 2px; font-size:0.85rem; color:#4b5563;">
            Uses the 13th Month Year and Job Role filters above.
          </p>
          <div style="display:flex; justify-content:space-between; align-items:center; margin: 0 0 0.5rem 2px; gap:0.75rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <div>
              Show
              <select id="thirteenth-finalized-page-size">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              entries
            </div>
            <button type="button" id="btn-export-thirteenth-finalized" class="pagination-btn" style="padding:4px 12px; font-size:0.8rem;">Export CSV</button>
          </div>

          <div class="payroll-table-container">
            <table class="payroll-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Role</th>
                  <th>Year</th>
                  <th>Period</th>
                  <th>13th Month</th>
                  <th>Paid Status</th>
                  <th>Payment Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="thirteenth-finalized-body">
                <tr>
                  <td colspan="8" style="text-align:center; color:#6b7280;">No 13th month records loaded yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div style="display:flex; justify-content:flex-end; align-items:center; margin: 0.25rem 0 0 2px; gap:0.25rem; flex-wrap:wrap; font-size:0.85rem; color:#4b5563;">
            <button type="button" id="thirteenth-finalized-page-prev" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Prev</button>
            <span id="thirteenth-finalized-page-info">Page 1 of 1</span>
            <button type="button" id="thirteenth-finalized-page-next" class="pagination-btn" style="padding:4px 10px; font-size:0.8rem;">Next</button>
          </div>
        </section>

      </div>
    </main>
  </div>

  <!-- Confirmation Modal -->
  <div style="background-color: rgba(0, 0, 0, 0.5); z-index: 1500;" id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="confirmation-title" aria-modal="true" aria-hidden="true">
    <div style="padding: 20px;" class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="confirmation-title">Confirm Action</h3>
          <button id="confirmation-close-x" class="text-gray-400 hover:text-gray-500 focus:outline-none" aria-label="Close modal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="mt-2">
          <p class="text-sm text-gray-600" id="confirmation-message">Are you sure you want to proceed?</p>
        </div>
        <div style="display: flex; gap: 10px;" class="mt-6 flex justify-end gap-3">
          <button id="confirmation-cancel-btn" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-300 focus:outline-none">
            Cancel
          </button>
          <button id="confirmation-confirm-btn" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
            Confirm
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="payslip-modal" class="payslip-modal">
    <div class="payslip-dialog">
      <div class="payslip-header">
        <h3>Payslip</h3>
        <div style="display:flex; gap:0.5rem;">
          <button type="button" id="payslip-print" class="pagination-btn" style="padding:4px 12px; font-size:0.8rem;">Print</button>
          <button type="button" id="payslip-close" class="pagination-btn" style="padding:4px 12px; font-size:0.8rem;">Close</button>
        </div>
      </div>
      <div id="payslip-body" class="payslip-body"></div>
    </div>
  </div>

  <script>

    document.addEventListener('DOMContentLoaded', function () {
      var toggle = document.getElementById('toggle-filters');
      var body = document.getElementById('payroll-filters-body');
      if (!toggle || !body) return;
      toggle.addEventListener('click', function () {
        var isHidden = body.style.display === 'none';
        body.style.display = isHidden ? '' : 'none';
        toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        toggle.textContent = isHidden ? 'Hide filters' : 'Show filters';
      });
    });
  </script>

  <script src="../js/payroll.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/auto_logout.js"></script>

</body>

</html>