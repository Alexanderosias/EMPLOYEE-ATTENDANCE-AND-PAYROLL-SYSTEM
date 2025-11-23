<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS Departments and Positions Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/department_position.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
  <div id="successMessageBox" style="display: none;">
    Login successful!
  </div>
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
          <li class="active">
            <a href="#">
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
            <h2>Manage Departments and Job Positions</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>

      <div class="scrollbar-container">
        <!-- Insert Departments and Job Positions container here -->
        <div class="container">
          <!-- Departments Section -->
          <section class="section-box" aria-labelledby="departments-title">
            <h3 id="departments-title">Departments</h3>
            <ul id="departments-list" class="item-list" aria-live="polite" aria-relevant="additions removals">
              <!-- Dynamic items inserted by JS -->
            </ul>
            <button id="add-department-btn" class="btn-add" aria-haspopup="dialog" aria-controls="add-department-modal">
              <img src="icons/addition.png" alt="Add icon" class="inline mr-2 align-middle" />
              Add Department
            </button>
          </section>

          <!-- Job Positions Section -->
          <section class="section-box" aria-labelledby="job-positions-title">
            <h3 id="job-positions-title">Job Positions</h3>
            <ul id="job-positions-list" class="item-list" aria-live="polite" aria-relevant="additions removals">
              <!-- Dynamic items inserted by JS, e.g., <li>Instructor - $15.50/hr <button>Edit</button> <button>Delete</button></li> -->
            </ul>
            <button id="add-job-position-btn" class="btn-add" aria-haspopup="dialog"
              aria-controls="add-job-position-modal">
              <img src="icons/addition.png" alt="Add icon" class="inline mr-2 align-middle" />
              Add Job Position
            </button>
          </section>
        </div>

        <!-- Add Department Modal -->
        <div id="add-department-modal" class="modal" aria-hidden="true" role="dialog"
          aria-labelledby="add-department-title" aria-modal="true">
          <div class="modal-content">
            <header class="modal-header">
              <h4 id="add-department-title">Add Department</h4>
              <button type="button" class="modal-close-btn" aria-label="Close modal">&times;</button>
            </header>
            <form id="add-department-form" novalidate>
              <div class="modal-body">
                <label for="department-name">Department Name</label>
                <input type="text" id="department-name" name="name" required placeholder="Enter department name" />
              </div>
              <footer class="modal-footer">
                <button type="submit" class="btn btn-primary">Add Department</button>
                <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
              </footer>
            </form>
          </div>
        </div>

        <!-- Add Job Position Modal -->
        <div id="add-job-position-modal" class="modal" aria-hidden="true" role="dialog"
          aria-labelledby="add-job-position-title" aria-modal="true">
          <div class="modal-content">
            <header class="modal-header">
              <h4 id="add-job-position-title">Add Job Position</h4>
              <button type="button" class="modal-close-btn" aria-label="Close modal">&times;</button>
            </header>
            <form id="add-job-position-form" novalidate>
              <div class="modal-body">
                <label for="job-position-name">Job Position Name</label>
                <input type="text" id="job-position-name" name="name" required placeholder="Enter job position name" />
                <label for="job-position-rate">Rate per Hour</label>
                <input type="number" id="job-position-rate" name="rate_per_hour" step="0.01" min="0" required
                  placeholder="Enter rate per hour (e.g., 15.50)" />
              </div>
              <footer class="modal-footer">
                <button type="submit" class="btn btn-primary">Add Job Position</button>
                <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
              </footer>
            </form>
          </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="delete-confirmation-modal" class="modal" aria-hidden="true" role="dialog"
          aria-labelledby="delete-confirmation-title" aria-modal="true">
          <div class="modal-content">
            <header class="modal-header">
              <h4 id="delete-confirmation-title">Confirm Deletion</h4>
              <button type="button" class="modal-close-btn" aria-label="Close modal">&times;</button>
            </header>
            <div class="modal-body">
              <p id="delete-confirmation-message">Are you sure you want to delete this item?</p>
              <p id="delete-warning-message" class="warning-text" style="display: none;"></p>
            </div>
            <footer class="modal-footer">
              <button type="button" id="confirm-delete-btn" class="btn btn-danger">Delete</button>
              <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
            </footer>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/department_position.js"></script>
  <script src="../js/auto_logout.js"></script>
</body>

</html>