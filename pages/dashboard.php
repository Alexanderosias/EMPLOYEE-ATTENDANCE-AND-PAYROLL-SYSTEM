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
                        <a href="leave_page.php">
                            <img src="icons/swap.png" alt="Leave" class="icon" />
                            Leave
                        </a>
                    </li>
                    <?php
                    $userRoles = $_SESSION['roles'] ?? [];
                    if (in_array('head_admin', $userRoles)):
                    ?>
                        <li>
                            <a href="holidays_events_page.php">
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
                <div class="metrics-cards">
                    <div class="card card-blue">
                        <p>Total Employees</p>
                        <h3 id="total-employees-count">--</h3>
                    </div>
                    <div class="card card-green">
                        <p>Present Today</p>
                        <h3 id="present-today-count">--</h3>
                    </div>
                    <div class="card card-orange">
                        <p>Late Today</p>
                        <h3 id="late-today-count">--</h3>
                    </div>
                    <div class="card card-purple">
                        <p>Pending Payroll</p>
                        <h3 id="pending-payroll-count">--</h3>
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
                            <canvas id="attendanceChartCanvas"></canvas>
                        </div>
                    </section>

                    <section class="dashboard-section chart-section">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Holidays &amp; Events</h3>
                        <div id="calendar-container">
                            <div id="calendar-header">
                                <button id="cal-prev" aria-label="Previous Month">◀</button>
                                <span id="cal-title"></span>
                                <button id="cal-next" aria-label="Next Month">▶</button>
                            </div>
                            <div id="calendar-grid"></div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/sidebar_update.js"></script>
    <script src="../js/auto_logout.js"></script>
    <script src="../js/current_time.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const yearInput = document.getElementById('year-filter');
            const periodSelect = document.getElementById('period-filter');
            const currentYear = new Date().getFullYear();
            if (yearInput) yearInput.value = currentYear;

            const metricsUrl = '../views/dashboard_handler.php?action=metrics';
            const chartUrl = (period, year) => `../views/dashboard_handler.php?action=attendance_chart&period=${encodeURIComponent(period)}&year=${encodeURIComponent(year)}`;
            const eventsUrl = (year, month) => `../views/dashboard_handler.php?action=events&year=${year}&month=${month}`;

            function loadMetrics() {
                fetch(metricsUrl)
                  .then(r => r.json())
                  .then(res => {
                      if (!res.success) return;
                      const d = res.data || {};
                      const elTot = document.getElementById('total-employees-count');
                      const elPre = document.getElementById('present-today-count');
                      const elLate = document.getElementById('late-today-count');
                      const elPend = document.getElementById('pending-payroll-count');
                      if (elTot) elTot.textContent = (d.total_employees ?? 0);
                      if (elPre) elPre.textContent = (d.present_today ?? 0);
                      if (elLate) elLate.textContent = (d.late_today ?? 0);
                      if (elPend) elPend.textContent = (d.pending_payroll ?? 0);
                  })
                  .catch(() => {});
            }

            let attChart;
            function loadChart() {
                const period = periodSelect ? periodSelect.value : 'weekly';
                const year = yearInput ? parseInt(yearInput.value || currentYear, 10) : currentYear;
                fetch(chartUrl(period, year))
                  .then(r => r.json())
                  .then(res => {
                      if (!res.success) return;
                      const ctx = document.getElementById('attendanceChartCanvas').getContext('2d');
                      const labels = res.data.labels || [];
                      const present = res.data.present || [];
                      const late = res.data.late || [];
                      const data = {
                        labels,
                        datasets: [
                          { label: 'Present', data: present, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.2)', tension: 0.3 },
                          { label: 'Late', data: late, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.2)', tension: 0.3 }
                        ]
                      };
                      const options = { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } };
                      if (attChart) { attChart.data = data; attChart.options = options; attChart.update(); }
                      else { attChart = new Chart(ctx, { type: 'line', data, options }); }
                  })
                  .catch(() => {});
            }

            function daysInMonth(year, month) { return new Date(year, month + 1, 0).getDate(); }
            function renderCalendarGrid(year, month, events) {
                const grid = document.getElementById('calendar-grid');
                const title = document.getElementById('cal-title');
                if (!grid || !title) return;
                grid.innerHTML = '';

                const firstDay = new Date(year, month, 1).getDay();
                const totalDays = daysInMonth(year, month);
                const monthName = new Date(year, month, 1).toLocaleString('default', { month: 'long' });
                title.textContent = `${monthName} ${year}`;

                const weekDays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                weekDays.forEach(w => {
                  const h = document.createElement('div');
                  h.className = 'cal-cell cal-head';
                  h.textContent = w;
                  grid.appendChild(h);
                });

                for (let i = 0; i < firstDay; i++) {
                  const e = document.createElement('div');
                  e.className = 'cal-cell cal-empty';
                  grid.appendChild(e);
                }

                const map = {};
                (events || []).forEach(ev => {
                  const sd = new Date(ev.start_date);
                  const ed = new Date(ev.end_date);
                  for (let d = new Date(sd); d <= ed; d.setDate(d.getDate() + 1)) {
                    if (d.getFullYear() === year && d.getMonth() === month) {
                      const key = d.getDate();
                      if (!map[key]) map[key] = [];
                      map[key].push(ev);
                    }
                  }
                });

                const today = new Date();
                const isSameDay = (y, m, day) => (
                  y === today.getFullYear() &&
                  m === today.getMonth() &&
                  day === today.getDate()
                );

                for (let day = 1; day <= totalDays; day++) {
                  const cell = document.createElement('div');
                  cell.className = 'cal-cell cal-day';
                  if (isSameDay(year, month, day)) {
                    cell.classList.add('cal-today');
                  }

                  const n = document.createElement('span');
                  n.className = 'cal-daynum';
                  n.textContent = day;
                  cell.appendChild(n);

                  const evs = map[day] || [];
                  if (evs.length > 0) {
                    cell.classList.add('has-event');
                    const dot = document.createElement('span');
                    dot.className = 'cal-dot';
                    cell.appendChild(dot);

                    const names = [];
                    evs.forEach(ev => {
                      const label = ev.name || '';
                      if (!label) return;
                      if (ev.category === 'holiday') {
                        names.push(`Holiday: ${label}`);
                      } else {
                        names.push(`Event: ${label}`);
                      }
                    });

                    if (names.length > 0) {
                      cell.title = names.join('\n');
                      const list = document.createElement('div');
                      list.className = 'cal-events';

                      const maxShow = 2;
                      names.slice(0, maxShow).forEach(text => {
                        const line = document.createElement('div');
                        line.textContent = text;
                        list.appendChild(line);
                      });

                      if (names.length > maxShow) {
                        const more = document.createElement('div');
                        more.className = 'cal-events-more';
                        more.textContent = `+${names.length - maxShow} more`;
                        list.appendChild(more);
                      }

                      cell.appendChild(list);
                    }
                  }

                  grid.appendChild(cell);
                }
            }

            function loadEvents(year, month) {
                const url = `../views/dashboard_handler.php?action=events&year=${year}&month=${month+1}`;
                fetch(url)
                  .then(r => r.json())
                  .then(res => {
                    if (!res.success) return;
                    renderCalendarGrid(year, month, res.data || []);
                  })
                  .catch(() => {});
            }

            let calYear = new Date().getFullYear();
            let calMonth = new Date().getMonth();
            const btnPrev = document.getElementById('cal-prev');
            const btnNext = document.getElementById('cal-next');
            if (btnPrev) btnPrev.addEventListener('click', () => { calMonth--; if (calMonth<0){calMonth=11;calYear--;} loadEvents(calYear, calMonth); });
            if (btnNext) btnNext.addEventListener('click', () => { calMonth++; if (calMonth>11){calMonth=0;calYear++;} loadEvents(calYear, calMonth); });

            if (periodSelect) periodSelect.addEventListener('change', loadChart);
            if (yearInput) yearInput.addEventListener('change', loadChart);

            loadMetrics();
            loadChart();
            loadEvents(calYear, calMonth);
        });
    </script>

</body>

</html>