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
    <title>Schedules</title>
    <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../pages/css/dashboard.css">
    <link rel="stylesheet" href="css/status-message.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Hide horizontal scrollbar on the carousel but keep scroll functionality */
        #schedule-carousel { -ms-overflow-style: none; scrollbar-width: none; cursor: grab; }
        #schedule-carousel::-webkit-scrollbar { display: none; height: 0; }
        #schedule-carousel.cursor-grabbing { cursor: grabbing; }
    </style>
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
                <section>
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold" style="color: var(--royal-blue);">My Weekly Schedule</h3>
                        <p class="text-gray-600">View your assigned shifts for the week.</p>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-6">
                        <div class="w-full lg:w-3/5">
                            <div class="rounded-xl border bg-white shadow-sm p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Weekly Overview</h4>
                                        <p class="text-xs text-gray-500">Your assigned shifts for each day.</p>
                                    </div>
                                    <span id="current-week-label" class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-600">This week</span>
                                </div>
                                <div id="weekly-schedule" class="divide-y divide-gray-100"></div>
                                <div id="no-schedule" class="hidden text-center text-gray-500 mt-4 text-sm">No schedules found.</div>
                            </div>
                        </div>

                        <div class="w-full lg:w-2/5">
                            <div id="calendarCard" class="rounded-xl border bg-white shadow-sm p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-gray-800">Calendar</h4>
                                    <div id="monthLabel" class="text-sm text-gray-600"></div>
                                </div>
                                <div class="grid grid-cols-7 gap-2 text-xs text-gray-500 mb-2">
                                    <div class="text-center">Sun</div>
                                    <div class="text-center">Mon</div>
                                    <div class="text-center">Tue</div>
                                    <div class="text-center">Wed</div>
                                    <div class="text-center">Thu</div>
                                    <div class="text-center">Fri</div>
                                    <div class="text-center">Sat</div>
                                </div>
                                <div id="calendarGrid" class="grid grid-cols-7 gap-2"></div>
                                <div class="mt-3 flex items-center gap-4 text-xs text-gray-600">
                                    <div class="flex items-center gap-2"><span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span> Holiday</div>
                                    <div class="flex items-center gap-2"><span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span> Event</div>
                                </div>
                            </div>
                        </div>
                    </div>
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

        function setWeekLabel() {
            const el = document.getElementById('current-week-label');
            if (!el) return;
            const today = new Date();
            const day = today.getDay(); // 0 (Sun) - 6 (Sat)
            const monday = new Date(today);
            const diffToMonday = (day + 6) % 7; // convert Sunday-based index to Monday-based
            monday.setDate(today.getDate() - diffToMonday);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            const opts = { month: 'short', day: 'numeric' };
            const startLabel = monday.toLocaleDateString(undefined, opts);
            const endLabel = sunday.toLocaleDateString(undefined, opts);
            el.textContent = `${startLabel} – ${endLabel}`;
        }

        function renderSchedule(weekly) {
            const container = document.getElementById('weekly-schedule');
            const noEl = document.getElementById('no-schedule');
            if (!container || !noEl) return;

            container.innerHTML = '';

            const todayIdx = new Date().getDay();
            let hasAny = false;

            for (let i = 0; i < 7; i++) {
                const items = weekly[i] || [];
                if (items.length > 0) {
                    hasAny = true;
                }

                const row = document.createElement('div');
                row.className = 'flex items-start justify-between py-2 px-1';

                const left = document.createElement('div');
                left.className = 'flex flex-col';

                let headerHtml = `<div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-800">${dayNames[i]}</span>`;
                if (i === todayIdx) {
                    headerHtml += '<span class="text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">Today</span>';
                }
                headerHtml += '</div>';

                let descText;
                let descClass;
                if (items.length === 0) {
                    descText = 'No shift';
                    descClass = 'text-xs text-gray-400 mt-1';
                } else {
                    const parts = items
                        .filter(s => s.start_time && s.end_time)
                        .map(s => `${formatTime(s.start_time)} – ${formatTime(s.end_time)}`);
                    descText = parts.length ? parts.join(', ') : 'No time set';
                    descClass = 'text-xs text-gray-600 mt-1';
                }

                left.innerHTML = headerHtml + `<div class="${descClass}">${descText}</div>`;
                row.appendChild(left);

                const right = document.createElement('div');
                right.className = 'mt-1 text-xs';

                if (items.length === 0) {
                    right.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">Off</span>';
                } else {
                    const anyWorking = items.some(s => parseInt(s.is_working, 10) === 1);
                    const label = anyWorking ? 'Working' : 'Off';
                    const cls = anyWorking
                        ? 'inline-flex items-center px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200'
                        : 'inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200';
                    right.innerHTML = `<span class="${cls}">${label}</span>`;
                }

                row.appendChild(right);
                container.appendChild(row);
            }

            noEl.classList.toggle('hidden', hasAny);
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

        function pad(n) { return n.toString().padStart(2, '0'); }
        function ymd(d) { return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }

        async function loadCalendar() {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            const startStr = ymd(start);
            const endStr = ymd(end);
            document.getElementById('monthLabel').textContent = now.toLocaleString('default', { month: 'long', year: 'numeric' });
            try {
                const res = await fetch(`../views/holidays_handler.php?action=list_all&start_date=${startStr}&end_date=${endStr}`);
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load calendar');
                renderCalendar(data.data || [], start, end);
            } catch (e) {
                showStatus(e.message || 'Failed to load calendar', 'error');
                renderCalendar([], start, end);
            }
        }

        function renderCalendar(items, start, end) {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            const map = {};
            items.forEach((it) => {
                const s = new Date(it.start_date);
                const e = new Date(it.end_date);
                const d0 = new Date(Math.max(s, start));
                const d1 = new Date(Math.min(e, end));
                for (let d = new Date(d0); d <= d1; d.setDate(d.getDate() + 1)) {
                    const key = ymd(d);
                    if (!map[key]) map[key] = { holidays: [], events: [] };
                    if (it.table === 'holidays') map[key].holidays.push(it.name);
                    else map[key].events.push(it.name);
                }
            });

            const firstDow = start.getDay();

            const daysInMonth = end.getDate();
            for (let i = 0; i < firstDow; i++) {
                const cell = document.createElement('div');
                cell.className = 'min-h-[64px] rounded-lg border border-dashed border-gray-200 bg-gray-50';
                grid.appendChild(cell);
            }
            const todayStr = ymd(new Date());
            for (let day = 1; day <= daysInMonth; day++) {
                const cur = new Date(start.getFullYear(), start.getMonth(), day);
                const key = ymd(cur);
                const data = map[key] || { holidays: [], events: [] };
                const isToday = key === todayStr;
                const cell = document.createElement('div');
                cell.className = 'min-h-[64px] rounded-lg border bg-white p-2 text-sm transition hover:shadow-sm hover:-translate-y-0.5 ' + (isToday ? 'ring-2 ring-blue-500' : '');

                cell.title = [...data.holidays, ...data.events].join(', ');

                const header = document.createElement('div');
                header.className = 'text-xs text-gray-600 font-medium';
                header.textContent = day;
                cell.appendChild(header);
                const list = document.createElement('div');
                list.className = 'mt-1 space-y-1';

                const addPill = (name, type) => {
                    const pill = document.createElement('div');
                    if (type === 'holiday') pill.className = 'inline-flex items-center gap-1 px-2 py-0.5 text-[11px] rounded-full bg-rose-50 text-rose-700 border border-rose-200';
                    else pill.className = 'inline-flex items-center gap-1 px-2 py-0.5 text-[11px] rounded-full bg-amber-50 text-amber-700 border border-amber-200';
                    const dot = document.createElement('span');
                    dot.className = 'inline-block w-1.5 h-1.5 rounded-full ' + (type === 'holiday' ? 'bg-rose-500' : 'bg-amber-500');
                    const label = document.createElement('span');
                    label.textContent = name.length > 14 ? name.slice(0, 13) + '…' : name;
                    pill.appendChild(dot);
                    pill.appendChild(label);
                    list.appendChild(pill);
                };
                data.holidays.slice(0, 2).forEach(n => addPill(n, 'holiday'));
                data.events.slice(0, 2 - Math.min(2, data.holidays.length)).forEach(n => addPill(n, 'event'));

                const extra = data.holidays.length + data.events.length - list.childElementCount;
                if (extra > 0) {
                    const more = document.createElement('div');
                    more.className = 'text-[11px] text-gray-500';
                    more.textContent = `+${extra} more`;
                    list.appendChild(more);
                }
                cell.appendChild(list);
                grid.appendChild(cell);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setWeekLabel();
            loadSchedule();
            loadCalendar();
        });
    </script>
</body>

</html>