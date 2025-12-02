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
    <title>Schedule</title>
    <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../pages/css/dashboard.css">
    <link rel="stylesheet" href="css/status-message.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div id="status-message" class="status-message"></div>
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
                    <li class="active">
                        <a href="#">
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
                        <h2>View Schedule</h2>
                    </div>
                    <div>
                        <p id="current-datetime"></p>
                    </div>
                </div>
                <div class="bottom-border"></div>
            </header>

            <div class="scrollbar-container">
                <section class="px-6 py-6">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold" style="color: var(--royal-blue);">My Weekly Schedule</h3>
                        <p class="text-gray-600">View your assigned shifts for the week.</p>
                    </div>

                    <div class="relative">
                        <button id="prev-btn" type="button" aria-label="Previous"
                                class="absolute left-2 top-1/2 -translate-y-1/2 z-10 bg-white border shadow rounded-full w-10 h-10 flex items-center justify-center text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div id="schedule-carousel" class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-2">
                            <!-- Day cards injected here -->
                        </div>
                        <button id="next-btn" type="button" aria-label="Next"
                                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 bg-white border shadow rounded-full w-10 h-10 flex items-center justify-center text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <div id="no-schedule" class="hidden text-center text-gray-600 mt-8">No schedules found.</div>
                </section>
            </div>
        </main>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/current_time.js"></script>
    <script>
        function showStatus(message, type = 'success') {
            const el = document.getElementById('status-message');
            if (!el) return;
            el.textContent = message;
            el.className = 'status-message ' + type;
            el.classList.add('show');
            if (el._hideTimer) clearTimeout(el._hideTimer);
            el._hideTimer = setTimeout(() => { el.classList.remove('show'); }, 3500);
        }

        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        function formatTime(t) {
            if (!t) return '';
            const [h, m] = t.split(':');
            let hh = parseInt(h, 10);
            const ampm = hh >= 12 ? 'PM' : 'AM';
            hh = hh % 12; if (hh === 0) hh = 12;
            return `${hh}:${m} ${ampm}`;
        }

        function renderSchedule(weekly) {
            const carousel = document.getElementById('schedule-carousel');
            const noEl = document.getElementById('no-schedule');
            carousel.innerHTML = '';

            const todayIdx = new Date().getDay();
            let hasAny = false;

            for (let i = 0; i < 7; i++) {
                const ringCls = i === todayIdx ? ' ring-2 ring-blue-500' : '';
                const dayBox = document.createElement('div');
                dayBox.className = 'day-card snap-start shrink-0 w-[340px] sm:w-[400px] lg:w-[460px] rounded-xl border shadow-sm p-4 bg-white' + ringCls;

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between mb-3';
                header.innerHTML = `<span class=\"font-semibold text-gray-800\">${dayNames[i]}</span>${i === todayIdx ? '<span class=\"text-xs px-2 py-1 rounded bg-blue-50 text-blue-600\">Today</span>' : ''}`;
                dayBox.appendChild(header);

                const items = weekly[i] || [];
                if (items.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'text-sm text-gray-400';
                    empty.textContent = 'No shift';
                    dayBox.appendChild(empty);
                } else {
                    hasAny = true;
                    items.sort((a, b) => (a.start_time > b.start_time ? 1 : -1));
                    items.forEach((s) => {
                        const chip = document.createElement('div');
                        chip.className = 'mb-2 rounded-lg border px-3 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 text-indigo-800';
                        const work = parseInt(s.is_working, 10) === 1;
                        chip.innerHTML = `
                            <div class=\"flex items-center justify-between\">
                                <span class=\"font-medium\">${s.shift_name || 'Shift'}</span>
                                <span class=\"text-xs ${work ? 'text-green-700' : 'text-red-700'}\">${work ? 'Working' : 'Off'}</span>
                            </div>
                            <div class=\"text-sm text-gray-700\">${formatTime(s.start_time)} - ${formatTime(s.end_time)}</div>
                            ${s.break_minutes ? `<div class=\"text-xs text-gray-500\">Break: ${s.break_minutes} min</div>` : ''}
                        `;
                        dayBox.appendChild(chip);
                    });
                }
                carousel.appendChild(dayBox);
            }

            // After render, scroll to today's card
            const firstCard = carousel.querySelector('.day-card');
            if (firstCard) {
                const gap = 16; // gap-4
                const cardWidth = firstCard.getBoundingClientRect().width + gap;
                carousel.scrollTo({ left: todayIdx * cardWidth, behavior: 'smooth' });
            }

            noEl.classList.toggle('hidden', hasAny);
        }

        function scrollByCard(direction = 1) {
            const carousel = document.getElementById('schedule-carousel');
            const card = carousel.querySelector('.day-card');
            if (!card) return;
            const gap = 16; // gap-4
            const amount = card.getBoundingClientRect().width + gap;
            carousel.scrollBy({ left: direction * amount, behavior: 'smooth' });
        }

        async function loadSchedule() {
            try {
                const profRes = await fetch('../views/employee_profile_handler.php?action=get_profile');
                const prof = await profRes.json();
                if (!prof.success || !prof.data || !prof.data.id) throw new Error(prof.message || 'Profile fetch failed');
                const employeeId = prof.data.id;

                const schedRes = await fetch(`../views/schedules.php?action=list_schedules&employee_id=${employeeId}`);
                const sched = await schedRes.json();
                if (!sched.success) throw new Error(sched.message || 'Schedules fetch failed');
                const list = Array.isArray(sched.data) ? sched.data : [];

                const weekly = [[], [], [], [], [], [], []];
                list.forEach((s) => {
                    const idx = typeof s.day_of_week === 'number' ? s.day_of_week : parseInt(s.day_of_week, 10) || 0;
                    if (idx >= 0 && idx <= 6) weekly[idx].push(s);
                });
                renderSchedule(weekly);
            } catch (e) {
                showStatus(e.message || 'Failed to load schedules', 'error');
                renderSchedule([[], [], [], [], [], [], []]);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadSchedule();
            document.getElementById('prev-btn').addEventListener('click', () => scrollByCard(-1));
            document.getElementById('next-btn').addEventListener('click', () => scrollByCard(1));
        });
    </script>
</body>

</html>