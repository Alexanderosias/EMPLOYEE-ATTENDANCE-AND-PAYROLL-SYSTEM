<?php
require_once '../views/auth.php'; // Check login
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EAAPS Dashboard</title>
    <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
    <div id="successMessageBox" style="display: none;">
        Login successful!
    </div>
    <div class="dashboard-container">
        <aside class="sidebar">
            <a class="sidebar-header" href="#">
                <img alt="Logo" class="logo" id="sidebarLogo" />
                <span class="app-name" id="sidebarAppName"></span>
            </a>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="#">
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
                <div>
                    <h2>DASHBOARD</h2>
                </div>
                <div>
                    <p id="current-datetime"></p>
                </div>
            </header>

            <!-- Info Banners -->
            <div class="info-banner" id="payroll-date-banner">
                Next Payroll Date: <span id="next-payroll-date">--</span> &nbsp;&nbsp;|&nbsp;&nbsp; Last Payroll Date:
                <span id="last-payroll-date">--</span>
            </div>

            <div class="metrics-cards">
                <div class="card card-blue">
                    <p>Total Employees</p>
                    <h3>42</h3>
                </div>
                <div class="card card-green">
                    <p>Present Today</p>
                    <h3>38</h3>
                </div>
                <div class="card card-orange">
                    <p>Late Today</p>
                    <h3>4</h3>
                </div>
                <div class="card card-purple">
                    <p>Pending Payroll</p>
                    <h3>3</h3>
                </div>
            </div>

            <!-- Side by side charts container -->
            <div class="charts-container">
                <!-- Attendance Trends Section -->
                <section class="dashboard-section monthly-attendance-reports chart-section">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-800">Attendance Trends</h3>
                        <div class="filter-group">
                            <label for="year-filter">Year:</label>
                            <input type="number" id="year-filter" min="2000" max="2100" step="1"
                                aria-label="Select Year" />
                            <label for="period-filter">Period:</label>
                            <select id="period-filter" aria-label="Select Period">
                                <option value="daily">Daily</option>
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div id="attendance-chart">
                        Attendance chart will be rendered here.
                    </div>
                </section>

                <!-- Salary Distribution Section -->
                <section class="dashboard-section salary-distribution chart-section">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Salary Distribution</h3>
                    <div id="salary-distribution-chart">
                        Salary distribution chart will be rendered here.
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/sidebar_update.js"></script>
    <script src="../js/auto_logout.js"></script>
    <script src="../js/current_time.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Set current year as default in year filter
            const yearInput = document.getElementById('year-filter');
            const currentYear = new Date().getFullYear();
            yearInput.value = currentYear;

            // Placeholder: Set example payroll dates and pending count
            document.getElementById('next-payroll-date').textContent = '2024-07-15';
            document.getElementById('last-payroll-date').textContent = '2024-06-15';
            document.getElementById('pending-payroll-count').textContent = '3';

            // TODO: Integrate chart libraries (e.g., Chart.js) and backend data here
        });
    </script>
</body>

</html>