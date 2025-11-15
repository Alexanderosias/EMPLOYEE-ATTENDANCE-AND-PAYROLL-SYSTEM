<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Employee Leave</title>
  <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="../pages/css/dashboard.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="flex bg-gray-100">
  <!-- Sidebar (Preserved with Custom CSS) -->
  <aside class="sidebar">
    <a class="sidebar-header" href="#">
      <img src="../pages/img/adfc_logo_by_jintokai_d4pchwp-fullview.png" alt="Logo" class="logo" />
      <span class="app-name">EAAPS Employee</span>
    </a>
    <nav class="sidebar-nav">
      <ul>
        <li>
          <a href="employee_dashboard.php">
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
        <li class="active">
          <a href="#">
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

  <!-- Main Content (Tailwind Applied) -->
  <main class="main-content">
    <header class="dashboard-header">
      <div>
        <h2>Leave Management</h2>
      </div>
      <div>
        <p id="current-datetime"></p>
      </div>
    </header>

    <!-- Leave Balances -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Annual Paid Leave</h3>
        <p class="text-2xl font-bold text-green-600" id="paid-leave-balance">15</p>
        <p class="text-sm text-gray-500">Days remaining</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Annual Unpaid Leave</h3>
        <p class="text-2xl font-bold text-blue-600" id="unpaid-leave-balance">5</p>
        <p class="text-sm text-gray-500">Days remaining</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Sick Leave</h3>
        <p class="text-2xl font-bold text-red-600" id="sick-leave-balance">10</p>
        <p class="text-sm text-gray-500">Days remaining</p>
      </div>
    </div>

    <!-- Request Leave Button -->
    <div class="mb-6">
      <button id="request-leave-btn" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200">
        <i class="fas fa-plus mr-2"></i>Request New Leave
      </button>
    </div>

    <!-- Leave Requests Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Leave Requests</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody id="leave-requests-table" class="bg-white divide-y divide-gray-200">
            <!-- Dynamic rows -->
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Leave Request Modal (Tailwind Applied) -->
  <div id="leave-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex-items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-calendar-plus mr-2"></i>Request Leave</h3>
      </div>
      <form id="leave-form" class="p-6">
        <div class="mb-4">
          <label for="leave-type" class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
          <select id="leave-type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <option value="Sick">Sick Leave</option>
            <option value="Vacation">Vacation Leave</option>
            <option value="Unpaid">Unpaid Leave</option>
          </select>
        </div>
        <div class="mb-4">
          <label for="leave-start" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
          <input type="date" id="leave-start" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div class="mb-4">
          <label for="leave-end" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
          <input type="date" id="leave-end" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div class="mb-4">
          <label for="leave-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
          <textarea id="leave-reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required></textarea>
        </div>
        <div class="flex justify-end space-x-3">
          <button type="button" id="cancel-leave-btn" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/employee_leave.js"></script>
</body>

</html>