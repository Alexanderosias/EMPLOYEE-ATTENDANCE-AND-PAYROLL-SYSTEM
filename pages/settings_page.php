<?php
require_once '../views/auth.php';  // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Settings Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/status-message.css">
  <link rel="stylesheet" href="css/settings_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script>
    window.userRole = '<?php echo $_SESSION['role']; ?>';
  </script>
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
            <li class="active">
              <a href="#">
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
            <h2>System Settings</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>
      <div class="scrollbar-container">

        <!-- Simplified Settings Content -->
        <div class="settings-container">
          <!-- System Information Card -->
          <div class="settings-card">
            <div class="settings-section">
              <h4>
                <img src="icons/management.png" alt="System Information" class="section-icon" />
                System Information
              </h4>
              <div class="form-group">
                <label for="systemName">System Name</label>
                <input type="text" id="systemName" value="EAAPS Admin" maxlength="12" />
              </div>
              <div class="form-group">
                <label for="logoUpload">System Logo</label>
                <div class="logo-preview-container">
                  <img id="logoPreview" class="logo-preview" src="img/adfc_logo_by_jintokai_d4pchwp-fullview.png"
                    alt="Current Logo" onclick="triggerLogoUpload()" />
                  <input type="file" id="logoUpload" accept="image/*" />
                </div>
                <p class="upload-hint">Click the logo preview to upload a new image (PNG/JPG, <2MB). Current logo will be
                    replaced on save.</p>
              </div>
            </div>

            <div class="btn-group">
              <button class="btn btn-primary" onclick="saveSystemInfo()">
                <img src="icons/save.png" alt="Save" class="btn-icon" />
                Save System Information
              </button>
            </div>
          </div>

          <div class="settings-card">
            <div class="settings-section">
              <h4>
                <img src="icons/time_date_settings.png" alt="Time & Date Settings" class="section-icon" />
                Time & Date Settings
              </h4>
              <div class="form-row">
                <div class="form-group">
                  <label for="autoLogoutTime">Auto Logout Time (minutes after last activity)</label>
                  <input type="number" id="autoLogoutTime" value="60" min="10" max="1440" step="1" />
                  <p class="small-text">Set to 0 to disable. Employees will be automatically marked out after inactivity. Minimum: 10 minutes if enabled.</p>
                </div>
                <div class="form-group">
                  <label for="dateFormat">Date Format</label>
                  <select id="dateFormat">
                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                    <option value="DD/MM/YYYY" selected>DD/MM/YYYY</option>
                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="btn-group">
              <button class="btn btn-primary" onclick="saveTimeDateSettings()">
                <img src="icons/save.png" alt="Save" class="btn-icon" />
                Save Time & Date Settings
              </button>
            </div>
          </div>

          <!-- Leave Settings Card -->
          <div class="settings-card">
            <div class="settings-section">
              <h4>
                <img src="icons/leave.png" alt="Leave Settings" class="section-icon" />
                Leave Settings
              </h4>
              <div class="form-row">
                <div class="form-group">
                  <label for="annualLeaveDays">Annual Paid Leave Days</label>
                  <input type="number" id="annualLeaveDays" value="15" min="0" max="30" />
                </div>
                <div class="form-group">
                  <label for="unpaidLeaveDays">Annual Unpaid Leave Days</label>
                  <input type="number" id="unpaidLeaveDays" value="5" min="0" max="20" />
                </div>
              </div>
              <div class="form-group">
                <label for="sickLeaveDays">Annual Sick Leave Days</label>
                <input type="number" id="sickLeaveDays" value="10" min="0" max="20" />
              </div>
            </div>

            <div class="btn-group">
              <button class="btn btn-primary" onclick="savePayrollLeaveSettings()">
                <img src="icons/save.png" alt="Save" class="btn-icon" />
                Save Leave Settings
              </button>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/settings.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/auto_logout.js"></script>

</body>

</html>