<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EAAPS Employee Leave</title>
  <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="../pages/css/dashboard.css">
  <link rel="stylesheet" href="css/employee_leave.css">
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

  <!-- Main Content (Tailwind Applied) -->
  <main class="main-content">
    <header class="dashboard-header">
      <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <div>
          <h2>Request Leave</h2>
        </div>
        <div>
          <p id="current-datetime"></p>
        </div>
      </div>
      <div class="bottom-border"></div>
    </header>

    <div class="scrollbar-container">

      <!-- Leave Balances (Preserved) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div id="cards" class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Annual Paid Leave</h3>
          <p class="text-2xl font-bold text-green-600" id="paid-leave-balance">15</p>
          <p class="text-sm text-gray-500">Days remaining</p>
        </div>
        <div id="cards" class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Annual Unpaid Leave</h3>
          <p class="text-2xl font-bold text-blue-600" id="unpaid-leave-balance">5</p>
          <p class="text-sm text-gray-500">Days remaining</p>
        </div>
        <div id="cards" class="bg-white p-6 rounded-lg shadow-md">
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

      <!-- Filters (Matching Admin Style) -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center gap-4 flex-1">
          <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
              <i class="fas fa-search text-gray-400"></i>
            </span>
            <input type="text" id="search-input" placeholder="Search by type or reason" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" aria-label="Search leave requests">
          </div>
          <select id="status-filter" class="py-2 px-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white" aria-label="Filter by status">
            <option value="all">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Declined</option>
          </select>
        </div>
      </div>

      <!-- Leave Requests Table (Matching Admin Style) -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th> <!-- New column -->
            </tr>
          </thead>
          <tbody id="leave-requests-table" class="bg-white divide-y divide-gray-200">
            <!-- Dynamic rows -->
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Leave Request Modal (Updated with Balance and Auto-Calc) -->
  <div id="leave-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="modal-title" aria-modal="true" aria-hidden="true">
    <div id="leave-modal-content" class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Request Leave</h3>
          <button id="close-modal-x" class="text-gray-400 hover:text-gray-500 focus:outline-none" aria-label="Close modal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form id="leave-form" class="mt-2 space-y-4">
          <div>
            <label for="leave-type" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Leave Type</label>
            <select id="leave-type" name="leave-type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
              <option value="">Select Type</option>
              <option value="Paid">Paid Leave</option>
              <option value="Unpaid">Unpaid Leave</option>
              <option value="Sick">Sick Leave</option>
            </select>
          </div>
          <div id="balance-display" class="text-sm text-gray-600 hidden">
            Available: <span id="available-balance">0</span> days
          </div>
          <!-- Start and End Date Fields Side-by-Side -->
          <div class="flex gap-4">
            <div class="flex-1">
              <label for="leave-start" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Start Date</label>
              <input type="date" id="leave-start" name="leave-start" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="flex-1">
              <label for="leave-end" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">End Date</label>
              <input type="date" id="leave-end" name="leave-end" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
          </div>
          <div>
            <label for="total-days" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Days</label>
            <input type="text" id="total-days" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
          </div>
          <div>
            <label for="leave-reason" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Reason</label>
            <textarea style="resize: vertical; min-height: 100px; max-height: 250px;" id="leave-reason" name="leave-reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required></textarea>
          </div>
          <div id="error-message" class="text-red-600 text-sm hidden"></div>
          <div class="flex flex-col gap-3">
            <div class="flex gap-3">
              <button type="button" id="cancel-leave-btn" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-300 focus:outline-none" aria-label="Cancel request">
                Cancel
              </button>
              <button type="submit" id="submit-leave-btn" class="flex-1 px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300" aria-label="Submit request">
                Submit
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Leave Request Modal -->
  <div id="view-leave-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="title" aria-modal="true" aria-hidden="true">
    <div class="relative mx-auto p-5 border shadow-lg rounded-md bg-white">
      <div style="min-width: 450px; max-width: 650px;" class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="view-modal-title">Leave Request Details</h3>
          <button id="view-close-modal-x" class="text-gray-400 hover:text-gray-500 focus:outline-none" aria-label="Close modal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="mt-2 space-y-4">
          <!-- Employee Info -->
          <div class="flex items-center">
            <img id="view-modal-avatar" class="h-12 w-12 rounded-full object-cover" alt="Avatar">
            <div id="name-container" class="ml-4">
              <div class="text-sm font-medium text-gray-900" id="view-modal-name"></div>
              <div class="text-sm text-gray-500" id="view-modal-email"></div>
            </div>
          </div>
          <!-- Details -->
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Leave Type</label>
            <p class="text-sm text-gray-900" id="view-modal-type"></p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Date Range</label>
            <p class="text-sm text-gray-900" id="view-modal-dates"></p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Reason</label>
            <p style="max-height: 250px; overflow-y: auto;" class="text-sm text-gray-900 bg-gray-50 p-3 rounded text-justify" id="view-modal-reason"></p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</label>
            <span id="view-modal-status" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"></span>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-5">
          <button id="cancel-request-btn" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
            Cancel Request
          </button>
        </div>
        <div class="mt-2">
          <button id="view-close-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="../js/dashboard.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/employee_leave.js"></script>
</body>

</html>