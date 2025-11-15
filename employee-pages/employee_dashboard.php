<?php
// require_once '../views/auth.php'; // Ensure employee is logged in
// if ($_SESSION['role'] !== 'employee') {
  // header('Location: dashboard.php'); // Redirect if not employee
  // exit;
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Employee Dashboard</title>
  <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="../pages/css/dashboard.css">
  <link rel="stylesheet" href="css/employee_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <a class="sidebar-header" href="#">
        <img src="../pages/img/adfc_logo_by_jintokai_d4pchwp-fullview.png" alt="Logo" class="logo" />
        <span class="app-name">EAAPS Employee</span>
      </a>
      <nav class="sidebar-nav">
        <ul>
          <li class="active">
            <a href="#">
              <img src="../pages/icons/home.png" alt="Dashboard" class="icon" />
              Dashboard
            </a>
          </li>
          <li>
            <a href="employee_attendance.php">
              <img src="../pages/icons/clock.png" alt="Attendance" class="icon" />
              Attendance
            </a>
          </li>
          <li>
            <a href="employee_schedule.php">
              <img src="../pages/icons/calendar-deadline-date.png" alt="Schedule" class="icon" />
              Schedule
            </a>
          </li>
          <li>
            <a href="employee_payroll.php">
              <img src="../pages/icons/cash.png" alt="Payroll" class="icon" />
              Payroll
            </a>
          </li>
          <li>
            <a href="employee_leave.php">
              <img src="../pages/icons/clipboard.png" alt="Leave" class="icon" />
              Leave
            </a>
          </li>
          <li>
            <a href="employee_profile.php">
              <img src="../pages/icons/user.png" alt="Profile" class="icon" />
              Profile
            </a>
          </li>
        </ul>
      </nav>
      <a class="logout-btn" href="../index.html">
        <img src="../pages/icons/sign-out-option.png" alt="Logout" class="logout-icon" />
        Logout
      </a>
    </aside>

    <main class="main-content">
      <header class="dashboard-header">
        <div>
          <h2>EMPLOYEE DASHBOARD</h2>
        </div>
        <div>
          <p id="current-datetime"></p>
        </div>
      </header>

      <!-- Top Row: Key Metrics -->
      <div class="dashboard-grid top-row">
        <!-- Today's Attendance Status -->
        <div class="card highlight">
          <h3><i class="fas fa-clock"></i> Today's Attendance</h3>
          <div id="today-attendance" class="card-content">
            <div class="status-indicator" id="attendance-status">Loading...</div>
            <div class="metric">Time-in: <span id="time-in">--:--</span></div>
            <div class="metric">Time-out: <span id="time-out">--:--</span></div>
            <div class="metric">Total Hours: <span id="total-hours">0</span></div>
            <div class="metric">Late: <span id="late-status">No</span></div>
            <div class="metric">Undertime: <span id="undertime-status">No</span></div>
          </div>
        </div>

        <!-- Monthly Attendance Summary -->
        <div class="card">
          <h3><i class="fas fa-chart-bar"></i> Monthly Summary</h3>
          <div class="summary-grid">
            <div class="metric">Present: <span id="total-present">0</span></div>
            <div class="metric">Absent: <span id="total-absent">0</span></div>
            <div class="metric">Lates: <span id="total-lates">0</span></div>
            <div class="metric">Undertime: <span id="total-undertime">0</span></div>
            <div class="metric">Overtime: <span id="total-overtime">0</span></div>
            <div class="metric">Working Days: <span id="working-days">0</span></div>
          </div>
          <canvas id="attendance-chart" class="chart"></canvas>
        </div>

        <!-- Notifications -->
        <div class="card notifications">
          <h3><i class="fas fa-bell"></i> Notifications</h3>
          <ul id="notifications-list" class="notification-list">
            <li>Loading...</li>
          </ul>
        </div>
      </div>

      <!-- Middle Row: Schedule and Payroll -->
      <div class="dashboard-grid middle-row">
        <!-- Work Schedule -->
        <div class="card">
          <h3><i class="fas fa-calendar-alt"></i> Work Schedule</h3>
          <div id="work-schedule" class="card-content">
            <div class="metric">Weekly Schedule: <span id="weekly-schedule">Mon-Fri</span></div>
            <div class="metric">Rest Day: <span id="rest-day">Saturday</span></div>
            <div class="metric">Shift Time: <span id="shift-time">8:00 AM - 5:00 PM</span></div>
            <div class="metric">Department: <span id="dept-name">IT</span></div>
            <div class="metric">Position: <span id="pos-name">Developer</span></div>
          </div>
        </div>

        <!-- Payroll Summary -->
        <div class="card">
          <h3><i class="fas fa-money-bill-wave"></i> Payroll Summary</h3>
          <div id="payroll-summary" class="card-content">
            <div class="metric">Total Hours: <span id="payroll-hours">0</span></div>
            <div class="metric">Gross Pay: ₱<span id="gross-pay">0.00</span></div>
            <div class="metric">Deductions: ₱<span id="deductions">0.00</span></div>
            <div class="metric">Net Pay: ₱<span id="net-pay">0.00</span></div>
          </div>
          <button id="download-payslip" class="btn-primary"><i class="fas fa-download"></i> Download Payslip</button>
        </div>
      </div>

      <!-- Bottom Row: Leave, Profile, QR -->
      <div class="dashboard-grid bottom-row">
        <!-- Leave Requests -->
        <div class="card">
          <h3><i class="fas fa-calendar-times"></i> Leave Requests</h3>
          <button id="request-leave-btn" class="btn-secondary"><i class="fas fa-plus"></i> Request Leave</button>
          <div id="leave-requests" class="leave-list">
            <p>Loading...</p>
          </div>
        </div>

        <!-- Personal Profile Overview -->
        <div class="card">
          <h3><i class="fas fa-user"></i> Personal Profile</h3>
          <div id="profile-overview" class="profile-preview">
            <img id="profile-avatar" src="../pages/img/user.jpg" alt="Avatar" class="avatar">
            <div class="profile-details">
              <div class="metric">Name: <span id="profile-name">Loading...</span></div>
              <div class="metric">ID: <span id="profile-id">000</span></div>
              <div class="metric">Department: <span id="profile-dept">N/A</span></div>
              <div class="metric">Position: <span id="profile-pos">N/A</span></div>
              <div class="metric">Contact: <span id="profile-contact">N/A</span></div>
            </div>
          </div>
          <button id="edit-profile-btn" class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</button>
        </div>

        <!-- QR Code -->
        <div class="card">
          <h3><i class="fas fa-qrcode"></i> QR Code</h3>
          <div id="qr-code" class="qr-container">
            <img id="qr-image" src="" alt="QR Code" class="qr-img">
          </div>
          <button id="download-qr" class="btn-primary"><i class="fas fa-download"></i> Download QR</button>
        </div>
      </div>
    </main>
  </div>

  <!-- Leave Request Modal -->
  <div id="leave-modal" class="modal">
    <div class="modal-content">
      <h3><i class="fas fa-calendar-plus"></i> Request Leave</h3>
      <form id="leave-form">
        <label for="leave-type"><i class="fas fa-list"></i> Type:</label>
        <select id="leave-type" required>
          <option value="Sick">Sick</option>
          <option value="Vacation">Vacation</option>
        </select>
        <label for="leave-start"><i class="fas fa-calendar-day"></i> Start Date:</label>
        <input type="date" id="leave-start" required>
        <label for="leave-end"><i class="fas fa-calendar-day"></i> End Date:</label>
        <input type="date" id="leave-end" required>
        <label for="leave-reason"><i class="fas fa-comment"></i> Reason:</label>
        <textarea id="leave-reason" required></textarea>
        <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Submit</button>
      </form>
    </div>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/employee_dashboard.js"></script>
</body>
</html>