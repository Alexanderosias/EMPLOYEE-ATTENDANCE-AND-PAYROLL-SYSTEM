<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Manage Users</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="src/styles.css">
  <link rel="stylesheet" href="css/users.css">
  <link rel="stylesheet" href="css/status-message.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <li class="active">
              <a href="#">
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
            <h2>Manage Users</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>
      <div class="scrollbar-container">
        <!-- Main Content Section -->
        <section class="users-section bg-white p-6 rounded-lg shadow-md">
          <div class="section-header flex justify-between items-center mb-4">
            <div class="flex items-center space-x-4">
              <h3 class="text-xl font-semibold text-gray-700">Users List</h3>
              <div style="margin-left: 30px;" class="flex items-center space-x-2">
                <label for="entries-select" class="text-sm text-gray-600">Show</label>
                <select id="entries-select" class="border border-gray-300 rounded px-2 py-1 text-sm">
                  <option value="5" selected>5</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
                <span class="text-sm text-gray-600">entries</span>
              </div>
            </div>
            <button id="addUserBtn"
              class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">Add New
              User</button>
          </div>
          <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-200">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avatar</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Email</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Phone</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Department</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Role</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Created At</th>
                  <th scope="col"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                <!-- User rows will be populated here -->
              </tbody>
            </table>
          </div>
          <div class="flex justify-end mt-4">
            <div class="flex items-center space-x-2">
              <button id="prev-page" class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
              <span id="page-info" class="text-sm text-gray-700">Page 1 of 1</span>
              <button id="next-page" class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
          </div>
        </section>
      </div>
    </main>

    <!-- Add User Modal -->
    <div class="modal-overlay" id="add-user-modal-overlay" role="dialog" aria-modal="true"
      aria-labelledby="add-user-modal-title">
      <div class="modal">
        <h3 id="add-user-modal-title">Add New User</h3>
        <form id="add-user-form">
          <!-- Avatar Upload Section -->
          <div class="avatar-upload-section">
            <div class="avatar-preview">
              <img id="add-avatar-preview" src="img/user.jpg" alt="Avatar Preview" class="avatar-circle" />
            </div>
            <button type="button" id="add-upload-avatar-btn" class="upload-avatar-btn">Upload Avatar</button>
            <input type="file" id="add-avatar-input" name="avatar" accept="image/*" style="display: none;" />
          </div>

          <label for="email">Email</label>
          <input type="email" id="email" name="email" required />

          <div style="display: flex; gap: 1rem;">
            <div style="flex: 1;">
              <label for="first-name">First Name</label>
              <input type="text" id="first-name" name="firstName" required />
            </div>
            <div style="flex: 1;">
              <label for="last-name">Last Name</label>
              <input type="text" id="last-name" name="lastName" required />
            </div>
          </div>

          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" />

          <label for="address">Address</label>
          <textarea id="address" name="address"></textarea>

          <label for="department">Department</label>
          <select id="department" name="departmentId" required>
            <!-- Options populated by JS -->
          </select>

          <div class="role-active-row">
            <div>
              <label for="roles">Roles</label>
              <div>
                <label><input type="checkbox" name="roles[]" value="employee"> Employee</label>
                <label><input type="checkbox" name="roles[]" value="admin"> Admin</label>
                <label><input type="checkbox" name="roles[]" value="head_admin"> Head Admin</label>
              </div>
            </div>
            <div>
              <label for="add-is-active-display">Status</label>
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="text" id="add-is-active-display" readonly style="flex: 1;" />
                <input type="checkbox" id="add-is-active" name="isActive" />
              </div>
            </div>
          </div>

          <!-- Password Field -->
          <div class="password-row">
            <label for="add-password" class="form-label">Password</label>
            <input id="add-password" type="password" name="password" placeholder=" " required>
            <!-- Password Toggle Icon -->
            <button type="button" id="add-password-toggle"
              class="eye-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>

          <div class="modal-buttons">
            <button type="button" class="cancel-btn" id="add-user-cancel-btn">Cancel</button>
            <button type="submit" class="save-btn">Add User</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="edit-user-modal-overlay">
      <div class="modal">
        <h3 id="edit-user-modal-title">Edit User</h3>
        <form id="edit-user-form">
          <!-- Avatar Upload Section -->
          <div class="avatar-upload-section">
            <div class="avatar-preview">
              <img id="edit-avatar-preview" src="img/user.jpg" alt="Avatar Preview" class="avatar-circle" />
            </div>
            <button type="button" id="edit-upload-avatar-btn" class="upload-avatar-btn">Upload Avatar</button>
            <input type="file" id="edit-avatar-input" name="avatar" accept="image/*" style="display: none;" />
          </div>
          <div class="name-fields">
            <div>
              <label for="edit-first-name">First Name</label>
              <input type="text" id="edit-first-name" name="firstName" required />
            </div>
            <div>
              <label for="edit-last-name">Last Name</label>
              <input type="text" id="edit-last-name" name="lastName" required />
            </div>
          </div>
          <label for="edit-email">Email</label>
          <input type="email" id="edit-email" name="email" required />
          <label for="edit-phone">Phone Number</label>
          <input type="text" id="edit-phone" name="phone" />
          <label for="edit-address">Address</label>
          <textarea id="edit-address" name="address"></textarea>
          <label for="edit-department">Department</label>
          <select id="edit-department" name="departmentId" required>
            <!-- Options populated by JS -->
          </select>
          <div class="role-active-row">
            <div>
              <label for="edit-roles">Roles</label>
              <div>
                <label><input type="checkbox" name="edit-roles[]" value="employee"> Employee</label>
                <label><input type="checkbox" name="edit-roles[]" value="admin"> Admin</label>
                <label><input type="checkbox" name="edit-roles[]" value="head_admin"> Head Admin</label>
              </div>
            </div>
            <div>
              <label for="edit-is-active-display">Status</label>
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="text" id="edit-is-active-display" readonly style="flex: 1;" />
                <input type="checkbox" id="edit-is-active" name="isActive" />
              </div>
            </div>
          </div>
          <div class="modal-buttons">
            <button type="button" class="cancel-btn" id="edit-user-cancel-btn">Cancel</button>
            <button type="submit" class="save-btn">Update</button>
          </div>
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
  <script src="../js/current_time.js"></script>
  <script>
    const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? 0); ?>;
  </script>
  <script src="../js/users_page.js"></script>
  <script src="../js/auto_logout.js"></script>

</body>

</html>