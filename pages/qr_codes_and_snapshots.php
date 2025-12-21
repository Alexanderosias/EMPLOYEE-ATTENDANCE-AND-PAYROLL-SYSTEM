<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS QR Codes & Snapshots Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/qr_and_snapshots.css">
  <link rel="stylesheet" href="src/styles.css">
  <link rel="stylesheet" href="css/status-message.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
  <div id="successMessageBox" style="display: none;">
    Login successful!
  </div>

  <!-- Status Message (for feedback) -->
  <div id="status-message" class="status-message"></div>

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
          <li class="active">
            <a href="#">
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
            <h2>QR Codes and Snapshots</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>

      <div class="scrollbar-container">
        <div style="width: 100%;">
          <div class="search-input-group">
            <input type="text" id="employee-search" class="search-input" placeholder="Search employee name" />
            <img src="icons/search.png" alt="Search Icon" class="search-icon" />
          </div>
        </div>

        <!-- Employees container -->
        <section id="employees-container">
          <!-- Employee cards will be injected here -->
        </section>
      </div>
    </main>
  </div>

  <div id="snapshot-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[80vh] overflow-y-auto p-6 relative">
      <button id="modal-close-btn" class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-xl font-bold">&times;</button>
        <h3 id="modal-employee-name" class="text-xl font-semibold mb-4"></h3>
        <div id="modal-snapshots-container">
          <!-- Snapshot cards inserted here -->
        </div>
    </div>
  </div>

  <!-- Fullscreen image overlay -->
  <div id="fullscreen-overlay" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-60 cursor-zoom-out">
    <img src="" alt="Fullscreen Snapshot" />
  </div>

  <!-- Confirmation Modal -->
  <div style="background-color: rgba(0, 0, 0, 0.5);" id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="confirmation-title" aria-modal="true" aria-hidden="true">
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

  <script src="../js/dashboard.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/qr_and_snapshots.js"></script>
  <script src="../js/auto_logout.js"></script>
</body>

</html>