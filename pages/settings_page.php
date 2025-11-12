<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Settings Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/settings_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <div id="successMessageBox" style="display: none;">
    Login successful!
  </div>
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
          <li class="active">
            <a href="#">
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
        <div>
          <h2>SYSTEM SETTINGS</h2>
        </div>
        <div>
          <p id="current-datetime"></p>
        </div>
      </header>

      <!-- Comprehensive Settings Content -->
      <div class="settings-container">
        <!-- Company Information Card -->
        <div class="settings-card">
          <div class="settings-section">
            <h4>
              <img src="icons/building.png" alt="Company Information" class="section-icon" />
              School Information
            </h4>
            <div class="form-row">
              <div class="form-group">
                <label for="companyName">School</label>
                <input type="text" id="companyName" value="Asian Development Foundation College" />
              </div>
              <div class="form-group">
                <label for="taxId">Tax ID</label>
                <input type="text" id="taxId" value="123-456-789-000" />
              </div>
            </div>
            <div class="form-group">
              <label for="companyAddress">School Address</label>
              <textarea id="companyAddress" rows="3">P. Burgos St., Tacloban City, Leyte, Philippines</textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="companyContact">Contact Number</label>
                <input type="tel" id="companyContact" value="+63 (2) 123-4567" />
              </div>
              <div class="form-group">
                <label for="companyEmail">School Email</label>
                <input type="email" id="companyEmail" value="adfc@example.com" />
              </div>
            </div>
            <div class="form-group">
              <label for="logoUpload">School Logo</label>
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
            <button class="btn btn-primary" onclick="saveCompanyInfo()">
              <img src="icons/save.png" alt="Save" class="btn-icon" />
              Save School Information
            </button>
          </div>
        </div>

        <!-- Time & Date Settings Card -->
        <div class="settings-card">
          <div class="settings-section">
            <h4>
              <img src="icons/clock.png" alt="Time & Date Settings" class="section-icon" />
              Time & Date Settings
            </h4>
            <div class="form-row">
              <div class="form-group">
                <label for="autoLogoutTime">Auto Logout Time (hours after last activity)</label>
                <input type="number" id="autoLogoutTime" value="1" min="0.5" max="24" step="0.5" />
                <p class="small-text">Set to 0 to disable. Employees will be automatically marked out after inactivity.
                </p>
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

            <div class="btn-group">
              <button class="btn btn-primary" onclick="saveTimeDateSettings()">
                <img src="icons/save.png" alt="Save" class="btn-icon" />
                Save Time & Date Settings
              </button>
            </div>
          </div>

          <!-- Payroll & Leave Settings Card -->
          <div class="settings-card">
            <div class="settings-section">
              <h4>
                <img src="icons/cash.png" alt="Payroll & Leave Settings" class="section-icon" />
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
                Save Payroll & Leave Settings
              </button>
            </div>
          </div>

          <!-- Attendance Settings Card -->
          <div class="settings-card">
            <div class="settings-section">
              <h4>
                <img src="icons/calendar-deadline-date.png" alt="Attendance Settings" class="section-icon" />
                Attendance Settings
              </h4>
              <div class="form-row">
                <div class="form-group">
                  <label for="lateThreshold">Late Threshold (minutes)</label>
                  <input type="number" id="lateThreshold" value="15" min="0" max="60" />
                </div>
                <div class="form-group">
                  <label for="undertimeThreshold">Undertime Threshold (minutes)</label>
                  <input type="number" id="undertimeThreshold" value="30" min="0" max="120" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="regularOvertimeRate">Regular Overtime Rate (x)</label>
                  <input type="number" id="regularOvertimeRate" value="1.25" min="1" max="2" step="0.25" />
                </div>
                <div class="form-group">
                  <label for="holidayOvertimeRate">Holiday Overtime Rate (x)</label>
                  <input type="number" id="holidayOvertimeRate" value="2" min="1" max="3" step="0.25" />
                </div>
              </div>
            </div>

            <div class="btn-group">
              <button class="btn btn-primary" onclick="saveAttendanceSettings()">
                <img src="icons/save.png" alt="Save" class="btn-icon" />
                Save Attendance Settings
              </button>
            </div>
          </div>
          <!-- Backup & Restore Settings Card -->
          <div class="settings-card">
            <div class="settings-section">
              <h4>
                <img src="icons/backup.png" alt="Backup & Restore Settings" class="section-icon" />
                Backup & Restore Settings
              </h4>
              <div class="form-row">
                <div class="form-group">
                  <label for="backupFrequency">Backup Frequency</label>
                  <select id="backupFrequency">
                    <option value="daily">Daily</option>
                    <option value="weekly" selected>Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="sessionTimeout">Session Timeout (minutes)</label>
                  <input type="number" id="sessionTimeout" value="30" min="5" max="120" />
                </div>
              </div>
              <div class="form-group">
                <label>Backup Location</label>
                <input type="text" value="Local Server (/backups)" readonly />
                <p class="small-text">Backups are stored securely on the server. Use the buttons below to manage.</p>
              </div>
            </div>

            <div class="btn-group">
              <button class="btn btn-secondary" onclick="exportData()">
                <img src="icons/export.png" alt="Export" class="btn-icon" />
                Download Latest Backup
              </button>
              <button class="btn btn-primary" onclick="createBackup()">
                <img src="icons/backup.png" alt="Create Backup" class="btn-icon" />
                Create Backup Now
              </button>
              <button class="btn btn-secondary" onclick="restoreBackup()"
                style="background: #FF6B6B; color: white; border-color: #FF6B6B;">
                <img src="icons/restore.png" alt="Restore" class="btn-icon" />
                Restore from Backup
              </button>
            </div>
          </div>
        </div>

        <!-- Status Message (for feedback) -->
        <div id="statusMessage" class="status-message"></div>
    </main>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/current_time.js"></script>
  <script>
    let currentLogoFile = null; // Track uploaded logo file

    // Logo Upload Functions
    function triggerLogoUpload() {
      document.getElementById('logoUpload').click();
    }

    document.getElementById('logoUpload').addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        if (!file.type.startsWith('image/')) {
          showStatus('Please select a valid image file.', 'error');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          showStatus('Image size must be less than 2MB.', 'error');
          return;
        }
        currentLogoFile = file;
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('logoPreview').src = e.target.result;
          showStatus('Logo selected. Click Save to upload.', 'success');
        };
        reader.readAsDataURL(file);
      }
    });

    // SSS Total Calculation (Auto-update on input change)
    function updateSSSTotal() {
      const er = parseFloat(document.getElementById('sssERContribution').value) || 0;
      const ee = parseFloat(document.getElementById('sssEEContribution').value) || 0;
      document.getElementById('sssTotal').value = (er + ee).toFixed(2);
    }

    document.getElementById('sssERContribution').addEventListener('input', updateSSSTotal);
    document.getElementById('sssEEContribution').addEventListener('input', updateSSSTotal);

    // Save Functions (Simulate - replace with API calls)
    function saveCompanyInfo() {
      if (currentLogoFile) {
        // In real app: Upload logo via FormData
        showStatus('Company information and logo updated successfully!', 'success');
        currentLogoFile = null;
      } else {
        showStatus('Company information updated successfully!', 'success');
      }
    }

    function saveTimeDateSettings() {
      showStatus('Time & date settings updated successfully!', 'success');
    }

    function savePayrollLeaveSettings() {
      showStatus('Payroll & leave settings updated successfully!', 'success');
    }

    function saveAttendanceSettings() {
      showStatus('Attendance settings updated successfully!', 'success');
    }

    function saveTaxDeductionSettings() {
      // In real app: Collect all tax fields and send to backend for validation/computation setup
      showStatus('Tax & deduction settings updated successfully! Computations will use the configured rules.', 'success');
    }

    function exportData() {
      showStatus('Backup download started.', 'success');
      // In real app: window.open('/api/backup/download', '_blank');
    }

    function createBackup() {
      showStatus('Backup created successfully!', 'success');
      // In real app: fetch('/api/backup/create', { method: 'POST' });
    }

    function restoreBackup() {
      if (confirm('Are you sure you want to restore from backup? This may overwrite current data.')) {
        showStatus('Restore initiated. Please wait for completion.', 'success');
        // In real app: Handle file upload and restore API
      }
    }

    function showStatus(message, type) {
      const statusDiv = document.getElementById('statusMessage');
      statusDiv.textContent = message;
      statusDiv.className = `status-message ${type}`;
      statusDiv.style.display = 'block';
      setTimeout(() => {
        statusDiv.style.display = 'none';
        statusDiv.className = 'status-message';
      }, 5000);
    }

    // Auto-hide success message on load if present
    window.addEventListener('load', () => {
      const successBox = document.getElementById('successMessageBox');
      if (successBox && successBox.style.display !== 'none') {
        showStatus(successBox.textContent, 'success');
        successBox.style.display = 'none';
      }
      updateSSSTotal(); // Initialize SSS total
    });
  </script>

</body>

</html>