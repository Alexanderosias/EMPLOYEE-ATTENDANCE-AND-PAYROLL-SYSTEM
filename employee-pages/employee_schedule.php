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
                <section class="px-6 py-6">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold" style="color: var(--royal-blue);">My Weekly Schedule</h3>
                        <p class="text-gray-600">View your assigned shifts for the week.</p>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-6">
                        <div class="w-full lg:w-3/5">
                            <div class="relative">
                                <button id="prev-btn" type="button" aria-label="Previous"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 z-10 bg-white border shadow rounded-full w-10 h-10 flex items-center justify-center text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div id="schedule-carousel" class="no-scrollbar flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-2">
                                </div>
                                <button id="next-btn" type="button" aria-label="Next"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 z-10 bg-white border shadow rounded-full w-10 h-10 flex items-center justify-center text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div id="no-schedule" class="hidden text-center text-gray-600 mt-4">No schedules found.</div>
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

        function renderSchedule(weekly) {
            const carousel = document.getElementById('schedule-carousel');
            const noEl = document.getElementById('no-schedule');
            carousel.innerHTML = '';

            const todayIdx = new Date().getDay();
            let hasAny = false;

            for (let i = 0; i < 7; i++) {
                const ringCls = i === todayIdx ? ' ring-2 ring-blue-500' : '';
                const dayBox = document.createElement('div');
                dayBox.className = 'day-card snap-start shrink-0 w-[420px] sm:w-[520px] lg:w-[600px] rounded-xl border shadow-sm p-4 bg-white transition hover:-translate-y-0.5 hover:shadow-md overflow-y-auto' + ringCls;

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between mb-3';
                header.innerHTML = `<span class="font-semibold text-gray-800">${dayNames[i]}</span>${i === todayIdx ? '<span class="text-xs px-2 py-1 rounded bg-blue-50 text-blue-600">Today</span>' : ''}`;
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
                        chip.className = 'mb-2 rounded-lg border px-3 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 text-indigo-800 transition hover:from-indigo-100 hover:to-blue-100 hover:shadow-sm';
                        const work = parseInt(s.is_working, 10) === 1;
                        chip.innerHTML = `
                            <div class="flex items-center justify-between">
                                <span class="font-medium">${s.shift_name || 'Shift'}</span>
                                <span class="text-xs ${work ? 'text-green-700' : 'text-red-700'}">${work ? 'Working' : 'Off'}</span>
                            </div>
                            <div class="text-sm text-gray-700">${formatTime(s.start_time)} - ${formatTime(s.end_time)}</div>
                            ${s.break_minutes ? `<div class="text-xs text-gray-500">Break: ${s.break_minutes} min</div>` : ''}
                        `;
                        dayBox.appendChild(chip);
                    });
                }
                carousel.appendChild(dayBox);
            }

            scrollToToday();

            noEl.classList.toggle('hidden', hasAny);
            // adjust heights after rendering
            setTimeout(adjustHeights, 0);
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
            // adjust heights after calendar render
            setTimeout(adjustHeights, 0);
        }

        function adjustHeights() {
            const calendar = document.getElementById('calendarCard');
            const carousel = document.getElementById('schedule-carousel');
            if (!calendar || !carousel) return;
            const h = Math.round(calendar.getBoundingClientRect().height);
            if (h > 0) {
                carousel.style.height = h + 'px';
                carousel.querySelectorAll('.day-card').forEach(c => { c.style.height = h + 'px'; });
            }
            // keep today's card visible after layout changes
            scrollToToday();
        }

        function scrollToToday() {
            const carousel = document.getElementById('schedule-carousel');
            const firstCard = carousel.querySelector('.day-card');
            if (!firstCard) return;
            const todayIdx = new Date().getDay();
            const gap = 16; // gap-4
            const cardWidth = firstCard.getBoundingClientRect().width + gap;
            const containerWidth = carousel.clientWidth;
            let targetLeft = todayIdx * cardWidth - (containerWidth - cardWidth) / 2; // center today's card
            if (targetLeft < 0) targetLeft = 0;
            const maxLeft = carousel.scrollWidth - containerWidth;
            if (targetLeft > maxLeft) targetLeft = maxLeft;
            carousel.scrollTo({ left: targetLeft, behavior: 'smooth' });
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadSchedule();
            loadCalendar();
            document.getElementById('prev-btn').addEventListener('click', () => scrollByCard(-1));
            document.getElementById('next-btn').addEventListener('click', () => scrollByCard(1));
            window.addEventListener('resize', () => { adjustHeights(); });
            // Drag-to-scroll for carousel
            const carousel = document.getElementById('schedule-carousel');

            let isDown = false, startX = 0, scrollStart = 0;
            carousel.addEventListener('mousedown', (e) => {
                isDown = true;
                carousel.classList.add('cursor-grabbing');
                startX = e.pageX - carousel.getBoundingClientRect().left;
                scrollStart = carousel.scrollLeft;
            });
            ['mouseleave','mouseup'].forEach(evt => carousel.addEventListener(evt, () => {
                isDown = false;
                carousel.classList.remove('cursor-grabbing');
            }));
            carousel.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - carousel.getBoundingClientRect().left;
                carousel.scrollLeft = scrollStart - (x - startX);
            });
        });
    </script>
</body>

</html>