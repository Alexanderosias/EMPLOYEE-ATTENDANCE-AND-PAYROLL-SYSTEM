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
    <title>View Attendance</title>
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
                    <li class="active">
                        <a href="#">
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
                        <h2>View Attendance</h2>
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
                        <h3 class="text-2xl font-semibold" style="color: var(--royal-blue);">My Attendance</h3>
                        <p class="text-gray-600">View your logs, status, and snapshots.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Present (This Month)</p>
                            <p id="emp-att-present" class="text-3xl font-bold text-green-600">--</p>
                        </div>
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Late (This Month)</p>
                            <p id="emp-att-late" class="text-3xl font-bold text-amber-600">--</p>
                        </div>
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Absent (This Month)</p>
                            <p id="emp-att-absent" class="text-3xl font-bold text-rose-600">--</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-end gap-3 mb-4">
                        <div>
                            <label for="att-start" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input id="att-start" type="date" class="border rounded px-3 py-2" />
                        </div>
                        <div>
                            <label for="att-end" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input id="att-end" type="date" class="border rounded px-3 py-2" />
                        </div>
                        <button id="att-apply" class="btn btn-primary">Apply</button>
                    </div>

                    <div class="rounded-xl border bg-white shadow-sm p-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="text-left p-2">Date</th>
                                    <th class="text-left p-2">Time In</th>
                                    <th class="text-left p-2">Time Out</th>
                                    <th class="text-left p-2">Status</th>
                                    <th class="text-left p-2">Expected</th>
                                    <th class="text-left p-2">Snapshot</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-rows"></tbody>
                        </table>
                        <div id="att-none" class="p-4 text-center text-gray-500 hidden">No records found.</div>
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

        function pad(n){return n.toString().padStart(2,'0');}
        function ymd(d){return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;}
        function statusBadge(s){
            let cls = 'bg-gray-100 text-gray-700 border-gray-200';
            if (s === 'Present') cls = 'bg-green-50 text-green-700 border-green-200';
            else if (s === 'Late') cls = 'bg-amber-50 text-amber-700 border-amber-200';
            else if (s === 'Absent') cls = 'bg-rose-50 text-rose-700 border-rose-200';
            else if (s === 'Undertime') cls = 'bg-blue-50 text-blue-700 border-blue-200';
            return `<span class="inline-flex items-center px-2 py-0.5 rounded-full border text-xs ${cls}">${s}</span>`;
        }

        async function loadSummary(){
            try{
                const res = await fetch('../views/employee_attendance_handler.php?action=summary');
                const out = await res.json();
                if(!out.success) throw new Error(out.message||'Failed to load summary');
                document.getElementById('emp-att-present').textContent = out.data.present ?? 0;
                document.getElementById('emp-att-late').textContent = out.data.late ?? 0;
                document.getElementById('emp-att-absent').textContent = out.data.absent ?? 0;
            }catch(e){ showStatus(e.message||'Failed to load summary', 'error'); }
        }

        async function loadList(startStr, endStr){
            try{
                const url = `../views/employee_attendance_handler.php?action=list&start=${encodeURIComponent(startStr)}&end=${encodeURIComponent(endStr)}`;
                const res = await fetch(url);
                const out = await res.json();
                if(!out.success) throw new Error(out.message||'Failed to load logs');
                const rows = Array.isArray(out.data) ? out.data : [];
                const tbody = document.getElementById('attendance-rows');
                const none = document.getElementById('att-none');
                tbody.innerHTML = '';
                if(rows.length === 0){ none.classList.remove('hidden'); return; } else { none.classList.add('hidden'); }
                rows.forEach(r => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b last:border-b-0';
                    const exp = (r.expected_start_time||'') && (r.expected_end_time||'') ? `${r.expected_start_time} - ${r.expected_end_time}` : '-';
                    const snap = r.snapshot_path ? `<a href="../${r.snapshot_path}" target="_blank" class="text-blue-600 underline">View</a>` : '-';
                    tr.innerHTML = `
                        <td class="p-2 whitespace-nowrap">${r.date}</td>
                        <td class="p-2 whitespace-nowrap">${r.time_in || '-'}</td>
                        <td class="p-2 whitespace-nowrap">${r.time_out || '-'}</td>
                        <td class="p-2 whitespace-nowrap">${statusBadge(r.status||'-')}</td>
                        <td class="p-2 whitespace-nowrap">${exp}</td>
                        <td class="p-2 whitespace-nowrap">${snap}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }catch(e){ showStatus(e.message||'Failed to load logs', 'error'); }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const end = new Date();
            const start = new Date(); start.setDate(end.getDate()-30);
            const elS = document.getElementById('att-start');
            const elE = document.getElementById('att-end');
            elS.value = ymd(start);
            elE.value = ymd(end);
            document.getElementById('att-apply').addEventListener('click', () => {
                const s = elS.value; const e = elE.value; if(!s||!e) return;
                loadList(s,e);
            });
            loadSummary();
            loadList(elS.value, elE.value);
        });
    </script>
</body>

</html>