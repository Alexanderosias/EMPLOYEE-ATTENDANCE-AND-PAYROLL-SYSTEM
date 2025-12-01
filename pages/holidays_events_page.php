<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAAPS Holidays and Events</title>
    <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/status-message.css">
    <link rel="stylesheet" href="src/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<style>
    #add-event-btn,
    #add-holiday-btn {
        background-color: #2563EB;
    }

    #add-event-btn:hover,
    #add-holiday-btn:hover {
        background-color: #3B82F6;
    }

    table {
        border-radius: 5px;
    }
</style>

<body>
    <!-- Status Message (for feedback) -->
    <div id="status-message" class="status-message"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
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
                        <li class="active">
                            <a href="#">
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <h2>Manage Holidays and Events</h2>
                    </div>
                    <div>
                        <p id="current-datetime"></p>
                    </div>
                </div>
                <div class="bottom-border"></div>
            </header>
            <div class="scrollbar-container">
                <!-- Contents here -->
                <!-- Page Header -->
                <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;" class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h4 class="text-2xl font-bold text-gray-800">Holidays & Events</h4>
                        <p class="text-gray-500 mt-1">Manage all School holidays and special events</p>
                    </div>
                    <div class="flex space-x-2 mt-4 sm:mt-0">
                        <button id="add-event-btn" class="px-4 py-2 text-white rounded-md hover:bg-blue-500 transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Add Event
                        </button>
                        <button id="add-holiday-btn" class="px-4 py-2 text-white rounded-md hover:bg-blue-500 transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Add Holiday
                        </button>
                    </div>
                </div>

                <!-- Filters / Quick Search -->
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label style="margin-right: 5px;" for="date-range-start" class="text-sm font-medium text-gray-700">Date Range:</label>
                            <input type="date" id="date-range-start" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span style="margin: 0 10px;" class="text-gray-500">to</span>
                            <input type="date" id="date-range-end" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex items-center gap-2">
                            <label style="margin-right: 5px;" for="type-filter" class="text-sm font-medium text-gray-700">Type:</label>
                            <select id="type-filter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="all">All</option>
                                <option value="regular">Regular</option>
                                <option value="special_non_working">Special Non-Working</option>
                                <option value="special_working">Special Working</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div style="position: relative;" class="flex items-center gap-2 flex-1">
                            <span style="position: absolute; right: 10px;">
                                <i style="color: #ff0001;" class="fas fa-search text-gray-400"></i>
                            </span>
                            <input style="padding: 0.48rem 30px 0.48rem 10px;" type="text" id="search-input" placeholder="Search by name" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Search">
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded shadow border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Type / Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Start Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">End Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="holidays-events-table" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (Optional, can be added later) -->
                <div class="mt-4 flex justify-end">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <!-- Pagination buttons here -->
                    </nav>
                </div>
            </div>
        </main>

        <!-- Add Holiday Modal -->
        <div style="background-color: rgba(0, 0, 0, 0.5); z-index: 1500;" id="add-holiday-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="holiday-modal-title" aria-modal="true" aria-hidden="true">
            <div style="padding: 20px;" class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-2xl bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="holiday-modal-title">Add Holiday</h3>
                        <button id="close-holiday-modal-x" class="text-gray-400 hover:text-gray-500 focus:outline-none" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form id="holiday-form" class="mt-2 space-y-4">
                        <div>
                            <label for="holiday-name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" id="holiday-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="holiday-type" class="block text-sm font-medium text-gray-700">Type</label>
                            <select id="holiday-type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select Type</option>
                                <option value="regular">Regular</option>
                                <option value="special_non_working">Special Non-Working</option>
                                <option value="special_working">Special Working</option>
                            </select>
                        </div>
                        <div style="width: 100%; display: flex; gap: 10px;" class="flex gap-4">
                            <div style="width: 50%;">
                                <label for="holiday-start" class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input type="date" id="holiday-start" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div style="width: 50%;">
                                <label for="holiday-end" class="block text-sm font-medium text-gray-700">End Date</label>
                                <input type="date" id="holiday-end" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;" class="flex justify-end gap-3">
                            <button type="button" id="cancel-holiday-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                            <button type="submit" id="save-holiday-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Event Modal -->
        <div style="background-color: rgba(0, 0, 0, 0.5); z-index: 1500;" id="add-event-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="event-modal-title" aria-modal="true" aria-hidden="true">
            <div style="padding: 20px;" class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-2xl bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="event-modal-title">Add Event</h3>
                        <button id="close-event-modal-x" class="text-gray-400 hover:text-gray-500 focus:outline-none" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form id="event-form" class="mt-2 space-y-4">
                        <div>
                            <label for="event-name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" id="event-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div style="width: 100%; display: flex; gap: 10px;" class="flex gap-4">
                            <div style="width: 50%;">
                                <label for="event-start" class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input type="date" id="event-start" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div style="width: 50%;">
                                <label for="event-end" class="block text-sm font-medium text-gray-700">End Date</label>
                                <input type="date" id="event-end" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                        </div>
                        <div>
                            <label for="event-paid" class="block text-sm font-medium text-gray-700">Paid</label>
                            <select id="event-paid" name="paid" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select</option>
                                <option value="yes">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="no">Unpaid</option>
                            </select>
                        </div>
                        <div>
                            <label for="event-description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="event-description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;" class="flex justify-end gap-3">
                            <button type="button" id="cancel-event-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                            <button type="submit" id="save-event-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div style="background-color: rgba(0, 0, 0, 0.5); z-index: 1500;" id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-40 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center" role="dialog" aria-labelledby="confirmation-title" aria-modal="true" aria-hidden="true">
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
    <script src="../js/auto_logout.js"></script>
    <script src="../js/holidays.js"></script>
</body>

</html>