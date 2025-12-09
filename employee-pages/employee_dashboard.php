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
  <title>Dashboard</title>
  <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="../pages/css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<style>
  .main-content {
    user-select: none;
  }

  .dashboard-card {
    transform: translateY(0);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }
</style>

<body>
  <div class="flex min-h-screen">
    <!-- Sidebar (Kept as is for consistency) -->
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
              <img src="../pages/icons/swap.png" alt="Leave" class="icon" />
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

    <!-- Main Content -->
    <main class="main-content">
      <header class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <div>
            <h2>Dashboard</h2>
          </div>
          <div>
            <p id="current-datetime"></p>
          </div>
        </div>
        <div class="bottom-border"></div>
      </header>

      <div class="scrollbar-container">

        <div class="space-y-6">
          <!-- Top Row: Key Metrics -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Today's Attendance Status -->
            <div class="dashboard-card bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-clock"></i> Today's Attendance
              </h3>
              <div id="today-attendance" class="space-y-2">
                <div class="text-xl font-bold" id="attendance-status">Loading...</div>
                <div class="text-sm">Time-in: <span id="time-in">--:--</span></div>
                <div class="text-sm">Time-out: <span id="time-out">--:--</span></div>
                <div class="text-sm">Total Hours: <span id="total-hours">0</span></div>
                <div class="text-sm">Late: <span id="late-status">No</span></div>
                <div class="text-sm">Undertime: <span id="undertime-status">No</span></div>
              </div>
            </div>

            <!-- Monthly Attendance Summary -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-bar text-green-600"></i> Monthly Summary
              </h3>
              <div class="grid grid-cols-2 gap-2 mb-4">
                <div class="text-sm">Present: <span id="total-present" class="font-semibold">0</span></div>
                <div class="text-sm">Absent: <span id="total-absent" class="font-semibold">0</span></div>
                <div class="text-sm">Lates: <span id="total-lates" class="font-semibold">0</span></div>
                <div class="text-sm">Undertime: <span id="total-undertime" class="font-semibold">0</span></div>
                <div class="text-sm">Overtime: <span id="total-overtime" class="font-semibold">0</span></div>
                <div class="text-sm">Working Days: <span id="working-days" class="font-semibold">0</span></div>
              </div>
              <canvas id="attendance-chart" class="w-full h-32"></canvas>
            </div>

            <!-- Notifications -->
            <div class="dashboard-card bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-xl shadow-lg">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-bell text-yellow-600"></i> Notifications
              </h3>
              <ul id="notifications-list" class="space-y-2 text-sm">
                <li>Loading...</li>
              </ul>
            </div>
          </div>

          <!-- Middle Row: Schedule and Payroll -->
          <div id="dashboard-content" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Work Schedule -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-calendar-alt text-green-600"></i> Work Schedule
              </h3>
              <div id="work-schedule" class="space-y-2 text-sm">
                <div>Weekly Schedule: <span id="weekly-schedule" class="font-semibold">Mon-Fri</span></div>
                <div>Rest Day: <span id="rest-day" class="font-semibold">Saturday</span></div>
                <div>Shift Time: <span id="shift-time" class="font-semibold">8:00 AM - 5:00 PM</span></div>
                <div>Department: <span id="dept-name" class="font-semibold">IT</span></div>
                <div>Position: <span id="pos-name" class="font-semibold">Developer</span></div>
              </div>
            </div>

            <!-- Payroll Summary -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-money-bill-wave text-green-600"></i> Payroll Summary
              </h3>
              <div id="payroll-summary" class="space-y-2 text-sm mb-4">
                <div>Total Hours: <span id="payroll-hours" class="font-semibold">0</span></div>
                <div>Gross Pay: ₱<span id="gross-pay" class="font-semibold">0.00</span></div>
                <div>Deductions: ₱<span id="deductions" class="font-semibold">0.00</span></div>
                <div>Net Pay: ₱<span id="net-pay" class="font-semibold">0.00</span></div>
              </div>
              <button id="download-payslip" class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-download"></i> Download Payslip
              </button>
            </div>
          </div>

          <!-- Bottom Row: Leave, Profile, QR -->
          <div id="dashboard-content" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Leave Requests -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-calendar-times text-green-600"></i> Leave Requests
              </h3>
              <button id="request-leave-btn" onclick="window.location.href='employee_leave.php#leave-modal'" class="w-full bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition-colors flex items-center justify-center gap-2 mb-4">
                <i class="fas fa-plus"></i> Request Leave
              </button>
              <div id="leave-requests" class="text-sm">
                <p>Loading...</p>
              </div>
            </div>

            <!-- Personal Profile Overview -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-user text-green-600"></i> Personal Profile
              </h3>
              <div id="profile-overview" class="flex items-center gap-4 mb-4">
                <img id="profile-avatar" src="../pages/img/user.jpg" alt="Avatar" class="w-16 h-16 rounded-full object-cover">
                <div class="space-y-1 text-sm">
                  <div>Name: <span id="profile-name" class="font-semibold">Loading...</span></div>
                  <div>ID: <span id="profile-id" class="font-semibold">000</span></div>
                  <div>Department: <span id="profile-dept" class="font-semibold">N/A</span></div>
                  <div>Position: <span id="profile-pos" class="font-semibold">N/A</span></div>
                  <div>Contact: <span id="profile-contact" class="font-semibold">N/A</span></div>
                </div>
              </div>
              <button id="edit-profile-btn" class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-edit"></i> Edit Profile
              </button>
            </div>

            <!-- QR Code -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-qrcode text-green-600"></i> QR Code
              </h3>
              <div id="qr-code" class="flex justify-center mb-4">
                <img id="qr-image" src="" alt="QR Code" class="w-32 h-32">
              </div>
              <button id="download-qr" class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-download"></i> Download QR
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/employee_dashboard.js"></script>
</body>

</html>