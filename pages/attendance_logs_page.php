<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS Attendance Logs Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/attendance_logs.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="src/styles.css" />
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
          <li class="active">
            <a href="#">
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
            <h2>Attendance Logs</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>
      <div class="scrollbar-container">
        <section class="attendance-logs max-w-7xl mx-auto">
          <div style="display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center;">
            <div class="flex items-center space-x-2">
              <label for="rows-per-page" class="text-gray-700 text-sm font-medium">Show</label>
              <select id="rows-per-page"
                class="border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="5" selected>5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              <span class="text-gray-700 text-sm">entries</span>
              <input type="date" id="filter-date"
                class="border border-gray-300 rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <button id="filter-btn"
                class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">Filter</button>
              <button id="clear-filter-btn"
                class="bg-gray-300 text-gray-700 px-4 py-1 rounded hover:bg-gray-400 transition">Clear</button>
            </div>

            <div class="flex space-x-2 items-center">
              <!-- Import Attendance Control -->
              <div class="import-attendance-container" title="Import Attendance Excel File">
                <label for="import-attendance-file">
                  <img src="icons/import.png" alt="Import Icon" />
                  Import Attendance
                </label>
                <input type="file" id="import-attendance-file" accept=".xls,.xlsx" />
              </div>
            </div>
          </div>

          <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-200">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Avatar</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee Name</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time In</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time Out</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status</th>
                  <th scope="col"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider actions-header">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody id="attendance-table-body" class="bg-white divide-y divide-gray-200">
                <!-- Attendance log rows will be inserted here dynamically -->
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-end space-x-2">
            <button id="prev-page" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
              disabled>Previous</button>
            <span id="page-info" class="self-center text-gray-700 text-sm">Page 1</span>
            <button id="next-page" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</button>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Edit Modal -->
  <div class="modal-overlay" id="edit-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <div class="modal">
      <h3 id="edit-modal-title">Edit Attendance</h3>
      <form id="edit-attendance-form">
        <label for="edit-time-in">Time In</label>
        <input type="time" id="edit-time-in" name="timeIn" required />

        <label for="edit-time-out">Time Out</label>
        <input type="time" id="edit-time-out" name="timeOut" /> <!-- Optional, no required -->

        <div class="modal-buttons">
          <button type="button" class="cancel-btn" id="edit-cancel-btn">Cancel</button>
          <button type="submit" class="save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/auto_logout.js"></script>
  <script src="../js/attendance_logs.js"></script>
</body>

</html>