<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS Employees Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/employees.css">
  <link rel="stylesheet" href="src/styles.css">
  <link rel="stylesheet" href="css/status-message.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
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
          <li class="active">
            <a href="#">
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
            <h2>Manage Employees</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>
      <div class="scrollbar-container">
        <div class="search-add-bar">
          <div class="filter-add-group">
            <div class="search-input-group">
              <input type="text" id="search-input" class="search-input" placeholder="Search employee name" />
              <button id="search-btn" class="search-btn" aria-label="Search">
                <img src="icons/search.png" alt="Search" />
              </button>
            </div>

            <div class="filter-group">
              <select id="filter-department" class="filter-select" aria-label="Filter by Department">
                <option value="">All Departments</option>
              </select>
            </div>

            <div class="filter-group">
              <select id="filter-job-position" class="filter-select" aria-label="Filter by Job Position">
                <option value="">All Job Positions</option>
              </select>
            </div>
          </div>

          <div class="buttons-group">
            <!-- <button id="import-employee-btn" class="import-employee-btn" aria-label="Import Employees" title="Import">
            <img src="icons/import.png" alt="Import" />
          </button> -->
            <button id="add-employee-btn" class="add-employee-btn" aria-label="Add Employee">
              Add Employee <img src="icons/add-emp.png" alt="Add Employee" />
            </button>
          </div>

        </div>

        <div class="employee-list" id="employee-list-container">
          <!-- Dynamic employee cards will be inserted here by JS -->
        </div>
      </div>
    </main>

    <!-- Add Employee Modal -->
    <div id="add-employee-modal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="modal-title"
      aria-modal="true">
      <div class="modal-content">
        <header class="modal-header">
          <h3 id="modal-title">Add Employee</h3>
          <button type="button" class="modal-close-btn" aria-label="Close modal">&times;</button>
        </header>
        <form id="add-employee-form" novalidate>
          <div class="modal-body">
            <div class="avatar-upload" style="text-align:center;">
              <label for="avatar-input" style="display:block; margin-bottom:0.5rem; font-weight:600;">Upload
                Avatar</label>
              <div class="avatar-preview" aria-live="polite" aria-atomic="true"
                style="margin: 0 auto 0.5rem; width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 2px solid #ccc; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                <img src="img/user.jpg" alt="Avatar preview" id="avatar-preview-img"
                  style="width: 100%; height: 100%; object-fit: cover;" />
              </div>
              <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none;" />
              <button type="button" id="upload-image-btn" class="btn btn-primary"
                style="background-color: royalblue; border:none; padding: 0.5rem 1.25rem; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 1rem; margin: 0 auto; display: block; width: max-content;">
                Upload Image
              </button>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="first_name" required placeholder="Enter first name" />
              </div>

              <div class="form-group">
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="last_name" required placeholder="Enter last name" />
              </div>

              <div class="form-group">
                <label for="job-position">Job Position</label>
                <select id="job-position" name="job_position_id" required>
                  <option value="" disabled selected>Select job position</option>
                </select>
              </div>

              <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department_id" required>
                  <option value="" disabled selected>Select department</option>
                </select>
              </div>

              <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" required placeholder="Enter address" />
              </div>

              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                  <option value="" disabled selected>Select gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label for="marital-status">Marital Status</label>
                <select id="marital-status" name="marital_status" required>
                  <option value="" disabled selected>Select marital status</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Divorced">Divorced</option>
                  <option value="Widowed">Widowed</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter email address" />
              </div>

              <div class="form-group">
                <label for="contact-number">Contact Number</label>
                <input type="tel" id="contact-number" name="contact_number" required
                  placeholder="Enter contact number (11 digits)" />
              </div>

              <div class="form-group">
                <label for="rate-per-hour">Rate per Hour</label>
                <input type="number" id="rate-per-hour" name="rate_per_hour" min="0" step="10" readonly
                  placeholder="Auto-filled from job position" value="0.00" />
              </div>

              <div class="form-group">
                <label for="rate-per-day">Rate per Day</label>
                <input type="number" id="rate-per-day" name="rate_per_day" min="0" step="10" readonly
                  placeholder="Auto-filled from job position" value="0.00" />
              </div>

              <div class="form-group">
                <label for="annual-paid-leave-days">Annual Paid Leave Days</label>
                <input type="number" id="annual-paid-leave-days" name="annual_paid_leave_days" min="0" step="1" required readonly />
              </div>

              <div class="form-group">
                <label for="annual-unpaid-leave-days">Annual Unpaid Leave Days</label>
                <input type="number" id="annual-unpaid-leave-days" name="annual_unpaid_leave_days" min="0" step="1" required readonly />
              </div>

              <div class="form-group">
                <label for="annual-sick-leave-days">Annual Sick Leave Days</label>
                <input type="number" id="annual-sick-leave-days" name="annual_sick_leave_days" min="0" step="1" required readonly />
              </div>

              <div class="emergency-contact-section">
                <label>Emergency Contact Info</label>
                <div class="form-group">
                  <label for="emergency-name">Name</label>
                  <input type="text" id="emergency-name" name="emergency_contact_name" required
                    placeholder="Emergency contact full name" />
                </div>
                <div class="form-group">
                  <label for="emergency-phone">Phone Number</label>
                  <input type="tel" id="emergency-phone" name="emergency_contact_phone" required
                    placeholder="Enter contact number (11 digits)" />
                </div>
                <div class="form-group">
                  <label for="emergency-relationship">Relationship</label>
                  <input type="text" id="emergency-relationship" name="emergency_contact_relationship" required
                    placeholder="Relationship to employee" />
                </div>
              </div>

            </div>
          </div>

          <footer class="modal-footer">
            <button type="submit" class="btn btn-primary">Add Employee</button>
            <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
          </footer>
        </form>
      </div>
    </div>

    <!-- Update Employee Modal -->
    <div id="update-employee-modal" class="modal" aria-hidden="true" role="dialog"
      aria-labelledby="update-modal-title" aria-modal="true">
      <div class="modal-content">
        <header class="modal-header">
          <h3 id="update-modal-title">Update Employee</h3>
          <button type="button" class="modal-close-btn" aria-label="Close modal">&times;</button>
        </header>
        <form id="update-employee-form" novalidate>
          <input type="hidden" name="id" id="update-employee-id" />
          <div class="modal-body">
            <div class="avatar-upload" style="text-align:center;">
              <label for="update-avatar-input" style="display:block; margin-bottom:0.5rem; font-weight:600;">Upload
                Avatar</label>
              <div class="avatar-preview" aria-live="polite" aria-atomic="true"
                style="margin: 0 auto 0.5rem; width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 2px solid #ccc; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                <img id="update-avatar-preview-img" src="img/user.jpg" alt="Avatar preview"
                  style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;" />
              </div>
              <input type="file" id="update-avatar-input" name="avatar" accept="image/*" style="display:none;" />
              <button type="button" id="update-upload-image-btn" class="btn btn-primary"
                style="background-color: royalblue; border:none; padding: 0.5rem 1.25rem; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 1rem; margin: 0 auto; display: block; width: max-content;">
                Upload Image
              </button>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="update-first-name">First Name</label>
                <input type="text" id="update-first-name" name="first_name" required placeholder="Enter first name" />
              </div>

              <div class="form-group">
                <label for="update-last-name">Last Name</label>
                <input type="text" id="update-last-name" name="last_name" required placeholder="Enter last name" />
              </div>

              <div class="form-group">
                <label for="update-job-position">Job Position</label>
                <select id="update-job-position" name="job_position_id" required>
                  <option value="" disabled selected>Select job position</option>
                </select>
              </div>

              <div class="form-group">
                <label for="update-department">Department</label>
                <select id="update-department" name="department_id" required>
                  <option value="" disabled selected>Select department</option>
                </select>
              </div>

              <div class="form-group">
                <label for="update-address">Address</label>
                <input type="text" id="update-address" name="address" required placeholder="Enter address" />
              </div>

              <div class="form-group">
                <label for="update-gender">Gender</label>
                <select id="update-gender" name="gender" required>
                  <option value="" disabled selected>Select gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label for="update-marital-status">Marital Status</label>
                <select id="update-marital-status" name="marital_status" required>
                  <option value="" disabled selected>Select marital status</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Divorced">Divorced</option>
                  <option value="Widowed">Widowed</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label for="update-email">Email Address</label>
                <input type="email" id="update-email" name="email" required placeholder="Enter email address" />
              </div>

              <div class="form-group">
                <label for="update-contact-number">Contact Number</label>
                <input type="tel" id="update-contact-number" name="contact_number" required
                  placeholder="Enter contact number (11 digits)" />
              </div>

              <div class="form-group">
                <label for="update-rate-per-hour">Rate per Hour</label>
                <input type="number" id="update-rate-per-hour" name="rate_per_hour" min="0" step="10" readonly
                  placeholder="Auto-filled from job position" value="0.00" />
              </div>

              <div class="form-group">
                <label for="update-rate-per-day">Rate per Day</label>
                <input type="number" id="update-rate-per-day" name="rate_per_day" min="0" step="10" readonly
                  placeholder="Auto-filled from job position" value="0.00" />
              </div>

              <div class="form-group">
                <label for="update-annual-paid-leave-days">Annual Paid Leave Days</label>
                <input type="number" id="update-annual-paid-leave-days" name="annual_paid_leave_days" min="0" step="1" required readonly />
              </div>

              <div class="form-group">
                <label for="update-annual-unpaid-leave-days">Annual Unpaid Leave Days</label>
                <input type="number" id="update-annual-unpaid-leave-days" name="annual_unpaid_leave_days" min="0" step="1" required readonly />
              </div>

              <div class="form-group">
                <label for="update-annual-sick-leave-days">Annual Sick Leave Days</label>
                <input type="number" id="update-annual-sick-leave-days" name="annual_sick_leave_days" min="0" step="1" required readonly />
              </div>

              <div class="emergency-contact-section">
                <label>Emergency Contact Info</label>
                <div class="form-group">
                  <label for="update-emergency-name">Name</label>
                  <input type="text" id="update-emergency-name" name="emergency_contact_name" required
                    placeholder="Emergency contact full name" />
                </div>
                <div class="form-group">
                  <label for="update-emergency-phone">Phone Number</label>
                  <input type="tel" id="update-emergency-phone" name="emergency_contact_phone" required
                    placeholder="Enter contact number (11 digits)" />
                </div>
                <div class="form-group">
                  <label for="update-emergency-relationship">Relationship</label>
                  <input type="text" id="update-emergency-relationship" name="emergency_contact_relationship" required
                    placeholder="Relationship to employee" />
                </div>
              </div>
            </div>
          </div>

          <footer class="modal-footer">
            <button type="submit" class="btn btn-primary">Update Employee</button>
            <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
          </footer>
        </form>
      </div>
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

  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/auto_logout.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/employees.js"></script>
</body>

</html>