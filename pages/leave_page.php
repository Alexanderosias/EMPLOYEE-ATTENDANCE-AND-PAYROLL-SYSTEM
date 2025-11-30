<?php
require_once '../views/auth.php';  // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAAPS Leave Page</title>
    <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="src/styles.css">
    <link rel="stylesheet" href="css/leave_page.css">
    <link rel="stylesheet" href="css/status-message.css">
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
                    <li class="active">
                        <a href="#">
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
                        <h2>Leave Management</h2>
                    </div>
                    <div>
                        <p id="current-datetime"></p>
                    </div>
                </div>
                <div class="bottom-border"></div>
            </header>
            <div class="scrollbar-container">

                <!-- Leave Page Content -->
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="search-input-group">
                                <input type="text" id="search-input" class="search-input" placeholder="Search employee..." />
                                <button id="search-btn" class="search-btn" aria-label="Search">
                                    <img src="icons/search.png" alt="Search" />
                                </button>
                            </div>
                            <select id="status-filter" class="py-2 px-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                                <option value="all">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Declined</option>
                            </select>
                        </div>
                        <!-- Date Range -->
                        <div class="flex items-center gap-2">
                            <input type="date" class="py-2 px-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-gray-500 ml-2 mr-2">to</span>
                            <input type="date" class="py-2 px-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                        <table class="divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 28%;">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 12px;">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 14px;">Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 15px;">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 8px;">Proof</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 10px;">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 13px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="leave-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Note -->
                    <div class="mt-6 text-center text-sm text-gray-500">
                        Only Head Admins may modify leave policies.
                    </div>
                </div>
            </div>
        </main>
        <!-- Modal (Hidden by default) -->
        <div id="leave-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="modal-title" aria-modal="true" aria-hidden="true">
            <div style="min-width: 500px; max-width: 700px;" id="leave-modal-content" class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Leave Request Details</h3>
                        <button id="close-modal-x" class="text-gray-400 hover:text-gray-500 focus:outline-none" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-2 space-y-4">
                        <!-- Employee Info -->
                        <div class="flex items-center">
                            <img id="modal-avatar" class="h-12 w-12 rounded-full object-cover" src="img/sample.jpg" alt="Avatar">
                            <div class="ml-4">
                                <div id="modal-name" class="text-sm font-medium text-gray-900"></div>
                                <div id="modal-email" class="text-sm text-gray-500"></div>
                            </div>
                        </div>
                        <!-- Details -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Leave Type</label>
                            <p id="modal-type" class="text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Date Range</label>
                            <p id="modal-dates" class="text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Reason</label>
                            <p style="max-height: 250px; overflow-y: auto;" id="modal-reason" class="text-sm text-gray-900 bg-gray-50 p-3 rounded text-justify"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</label>
                            <span id="modal-status" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"></span>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; gap: 10px;" class="mt-6 flex justify-end gap-5">
                        <button class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                            Decline
                        </button>
                        <button class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                            Approve
                        </button>
                    </div>
                    <div class="mt-2">
                        <button id="close-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none">
                            Close
                        </button>
                        <button id="modal-approve-btn" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                            Approve
                        </button>
                    </div>
                    <div class="mt-2">
                        <button id="close-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none">
                            Close
                        </button>
                    </div>
                </div>
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

    <script src="../js/sidebar_update.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/current_time.js"></script>
    <script src="../js/leave_page.js"></script>
    <script src="../js/auto_logout.js"></script>

</body>

</html>