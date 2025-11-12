<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Schedule Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/schedule.css">
  <link rel="stylesheet" href="src/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <div id="successMessageBox" style="display: none;">
    Login successful!
  </div>

  <div class="dashboard-container">
    <!-- Sidebar -->
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
          <li class="active">
            <a href="#">
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

    <!-- Main Content -->
    <main class="main-content">
      <header class="dashboard-header">
        <div>
          <h2>MANAGE SCHEDULES</h2>
        </div>
        <div>
          <p id="current-datetime"></p>
        </div>
      </header>

      <!-- Schedule Management Section -->
      <div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row items-center justify-between pb-4 border-b border-gray-200 mb-6">
          <div class="mb-4 sm:mb-0">
            <h1 class="text-3xl font-bold text-gray-800">Add Schedules</h1>
            <p class="text-gray-500 mt-1">Manage weekly schedules for employees.</p>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mb-4">
          <div class="relative w-full sm:w-1/3 mb-2 sm:mb-0">
            <input type="text" id="employee-search-input" placeholder="Search employees..."
              class="w-full border border-gray-300 rounded-md focus:outline-none focus:ring-0" aria-label="Search employees" />
            <button id="employee-search-btn" type="button"
              class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none bg-white"
              aria-label="Search">
              <img src="icons/search.png" alt="Search icon" class="w-5 h-5" />
            </button>

          </div>

          <select id="filter-job-position" class="w-full sm:w-1/4 p-2 border border-gray-300 rounded-md mb-2 sm:mb-0"
            aria-label="Filter by job position">
            <option value="">All Job Positions</option>
          </select>

          <select id="filter-department" class="w-full sm:w-1/4 p-2 border border-gray-300 rounded-md"
            aria-label="Filter by department">
            <option value="">All Departments</option>
          </select>
        </div>

        <div class="mb-6">
          <label for="employee-select" class="block text-sm font-medium text-gray-700 mb-1">Select Employee</label>
          <select id="employee-select"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 text-sm">
            <!-- Options will be added by JS -->
          </select>
        </div>
        <div id="weekly-schedule-container" class="weekly-grid" aria-live="polite">
          <!-- Day cards will be inserted here -->
        </div>
      </div>

      <!-- Modal for Adding/Editing Shifts -->
      <div id="schedule-modal" class="modal-overlay hidden">
        <div class="modal-content relative">
          <button id="close-modal-btn" class="modal-close-btn">&times;</button>
          <h2 class="text-2xl font-bold text-gray-800 mb-4" id="modal-title">Add Class</h2>
          <div class="flex flex-col space-y-4">
            <div>
              <label for="modal-employee-name" class="block text-sm font-medium text-gray-700">Employee</label>
              <input type="text" id="modal-employee-name"
                class="w-full rounded-md border-gray-300 shadow-sm p-2 text-sm mt-1 bg-gray-100" readonly>
            </div>
            <div>
              <label for="modal-day-of-week" class="block text-sm font-medium text-gray-700">Day of Week</label>
              <input type="text" id="modal-day-of-week"
                class="w-full rounded-md border-gray-300 shadow-sm p-2 text-sm mt-1 bg-gray-100" readonly>
            </div>
            <div>
              <label for="modal-shift-details" class="block text-sm font-medium text-gray-700">Shift/Class
                Name</label>
              <input type="text" id="modal-shift-details" placeholder="e.g., Algebra 101"
                class="w-full rounded-md border-gray-300 shadow-sm p-2 text-sm mt-1">
            </div>
            <div>
              <label for="modal-shift-start" class="block text-sm font-medium text-gray-700">Start Time</label>
              <input type="time" id="modal-shift-start"
                class="w-full rounded-md border-gray-300 shadow-sm p-2 text-sm mt-1">
            </div>
            <div>
              <label for="modal-shift-end" class="block text-sm font-medium text-gray-700">End Time</label>
              <input type="time" id="modal-shift-end"
                class="w-full rounded-md border-gray-300 shadow-sm p-2 text-sm mt-1">
            </div>

            <button id="save-shift-btn"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-md shadow-md hover:bg-blue-700 transition-colors duration-200">
              Save Class
            </button>
            <button id="delete-shift-btn"
              class="w-full px-4 py-2 bg-red-600 text-white rounded-md shadow-md hover:bg-red-700 transition-colors duration-200 hidden">
              Delete Class
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/schedule.js"></script>
  <script src="../js/current_time.js"></script>
</body>

</html>