<?php
require_once '../views/auth.php'; // Ensure user is logged in
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Profile Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/profile_details.css">
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
          <li class="active">
            <a href="#">
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
            <h2>Profile Details</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>

      <div class="scrollbar-container">

        <!-- Profile Overview Card -->
        <div class="profile-card" id="profileCard">
          <div class="profile-header">
            <div class="upload-overlay" onclick="triggerImageUpload()">
              <img src="icons/camera.png" alt="Upload Photo" class="overlay-icon" />
            </div>
            <img id="profileImage" class="profile-avatar" src="icons/profile-picture.png" alt="Profile Picture"
              onclick="triggerImageUpload()" />
            <input type="file" id="imageUpload" accept="image/*" />
            <div class="profile-info">
              <h3 id="fullName">Loading...</h3>
              <p id="roleDisplay">Loading...</p>
            </div>
          </div>

          <!-- Personal Information Section -->
          <div class="profile-section">
            <h4>
              <img src="icons/user-edit.png" alt="Personal Information" class="section-icon" />
              Personal Information
            </h4>
            <!-- Note for linked users -->
            <div id="editNote" class="note" style="display: none; background: #f0f8ff; border: 1px solid #007bff; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
              <strong>Note:</strong> Personal details and avatar cannot be edited here because this user is linked to an employee. Please update these details in the Employees page.
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" id="firstName" readonly />
              </div>
              <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" id="lastName" readonly />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" readonly />
              </div>
              <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" readonly />
              </div>
            </div>
            <div class="form-group">
              <label for="address">Address</label>
              <textarea id="address" rows="3" readonly></textarea>
            </div>
          </div>

          <!-- Account Settings Section -->
          <div class="profile-section">
            <h4>
              <img src="icons/settings.png" alt="Account Settings" class="section-icon" />
              Account Settings
            </h4>
            <div class="form-row">
              <div class="form-group">
                <label for="joinDate">Date Joined</label>
                <input type="date" id="joinDate" readonly />
              </div>
              <div class="form-group">
                <label for="department">Department</label>
                <select id="department" disabled>
                  <!-- Options populated by JS -->
                </select>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="btn-group">
            <button class="btn btn-secondary" onclick="editProfile()">
              <img src="icons/edit.png" alt="Edit" class="btn-icon" />
              Edit Profile
            </button>
            <button class="btn btn-primary" onclick="saveProfile()" style="display: none;">
              <img src="icons/save.png" alt="Save" class="btn-icon" />
              Save Changes
            </button>
          </div>
        </div>

        <!-- Security Card -->
        <div class="profile-card">
          <h4 style="color: var(--royal-blue); font-size: 1.25rem; margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center;">
            <img src="icons/computer-security-shield.png" alt="Security" class="section-icon" style="margin-right: 0.5rem;" />
            Security & Password
          </h4>
          <div class="form-group">
            <label for="confirmPassword">Current Password</label>
            <input type="password" id="confirmPassword" placeholder="Enter current password" />
            <button type="button" id="confirmPasswordToggle" class="password-toggle">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="currentPassword">New Password</label>
              <input type="password" id="currentPassword" placeholder="Enter new password" />
              <button type="button" id="currentPasswordToggle" class="password-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
            <div class="form-group">
              <label for="newPassword">Confirm New Password</label>
              <input type="password" id="newPassword" placeholder="Confirm new password" />
              <button type="button" id="newPasswordToggle" class="password-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
          </div>
          <button class="btn btn-primary" onclick="changePassword()" style="margin-top: 1rem;">
            <img src="icons/key.png" alt="Update Password" class="btn-icon" />
            Update Password
          </button>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/sidebar_update.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/profile_details.js"></script>
  <script src="../js/auto_logout.js"></script>
</body>

</html>