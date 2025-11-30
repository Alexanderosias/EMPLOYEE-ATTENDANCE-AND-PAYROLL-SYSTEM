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
    <title>Profile</title>
    <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../pages/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
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
                    <li>
                        <a href="employee_leave.php">
                            <img src="../pages/icons/swap.png" alt="Leave" class="icon" />
                            Leave
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
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
                        <h2>Profile</h2>
                    </div>
                    <div>
                        <p id="current-datetime"></p>
                    </div>
                </div>
                <div class="bottom-border"></div>
            </header>

            <div class="scrollbar-container">
                <!-- Employee Profile Contents -->

            </div>
        </main>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/current_time.js"></script>
</body>

</html>