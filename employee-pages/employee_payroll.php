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
    <title>Payroll</title>
    <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../pages/css/dashboard.css">
    <link rel="stylesheet" href="css/status-message.css">
    <link rel="stylesheet" href="css/employee_payroll.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('eaaps_employee_sidebar_collapsed');
                if (saved === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {
                // ignore
            }
        })();
    </script>
</head>

<body class="employee-layout">
    <div id="status-message" class="status-message"></div>
    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img id="sidebarProfileAvatar" src="../pages/icons/profile-picture.png" alt="Profile" class="logo" />
                <span class="app-name" id="sidebarProfileName">Employee</span>
                <button type="button" class="header-menu-btn" aria-label="Open navigation menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="employee_dashboard.php" title="Dashboard">
                            <img src="../pages/icons/home.png" alt="Dashboard" class="icon" style="height:24px; width:auto;" />
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="employee_attendance.php" title="Attendance">
                            <img src="../pages/icons/clock.png" alt="Attendance" class="icon" style="height:24px; width:auto;" />
                            Attendance
                        </a>
                    </li>
                    <li>
                        <a href="employee_schedule.php" title="Schedule">
                            <img src="../pages/icons/calendar-deadline-date.png" alt="Schedule" class="icon" style="height:24px; width:auto;" />
                            Schedule
                        </a>
                    </li>
                    <li class="active">
                        <a href="#" title="Payroll">
                            <img src="../pages/icons/cash.png" alt="Payroll" class="icon" style="height:24px; width:auto;" />
                            Payroll
                        </a>
                    </li>
                    <li>
                        <a href="employee_leave.php" title="Leave">
                            <img src="../pages/icons/swap.png" alt="Leave" class="icon" style="height:24px; width:auto;" />
                            Leave
                        </a>
                    </li>
                    <li>
                        <a href="employee_profile.php" title="Profile">
                            <img src="../pages/icons/user.png" alt="Profile" class="icon" style="height:24px; width:auto;" />
                            Profile
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="employee-mobile-menu">
                <a href="employee_dashboard.php" class="employee-mobile-menu-item">
                    <img src="../pages/icons/home.png" alt="Dashboard" class="icon" />
                    Dashboard
                </a>
                <a href="employee_attendance.php" class="employee-mobile-menu-item">
                    <img src="../pages/icons/clock.png" alt="Attendance" class="icon" />
                    Attendance
                </a>
                <a href="employee_schedule.php" class="employee-mobile-menu-item">
                    <img src="../pages/icons/calendar-deadline-date.png" alt="Schedule" class="icon" />
                    Schedule
                </a>
                <a href="employee_payroll.php" class="employee-mobile-menu-item">
                    <img src="../pages/icons/cash.png" alt="Payroll" class="icon" />
                    Payroll
                </a>
                <a href="employee_leave.php" class="employee-mobile-menu-item">
                    <img src="../pages/icons/swap.png" alt="Leave" class="icon" />
                    Leave
                </a>
                <a href="employee_profile.php" class="employee-mobile-menu-item">
                    <img src="../pages/icons/user.png" alt="Profile" class="icon" />
                    Profile
                </a>
                <a href="../index.html" class="employee-mobile-menu-item employee-mobile-menu-logout">
                    <img src="../pages/icons/sign-out-option.png" alt="Logout" class="icon" />
                    Logout
                </a>
            </div>

            <a class="logout-btn" href="../index.html" title="Logout">
                <img src="../pages/icons/sign-out-option.png" alt="Logout" class="logout-icon" style="height:24px; width:auto;" />
                Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button id="sidebarToggle" class="sidebar-toggle-btn" type="button" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2>Payroll</h2>
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
                        <h3 class="text-2xl font-semibold" style="color: var(--royal-blue);">My Payroll</h3>
                        <p class="text-gray-600">View your payroll summary and payslips.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Total Gross</p>
                            <p id="emp-pay-gross" class="text-2xl font-bold text-gray-900">₱0.00</p>
                        </div>
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Total Deductions</p>
                            <p id="emp-pay-deductions" class="text-2xl font-bold text-rose-600">₱0.00</p>
                        </div>
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Total Net</p>
                            <p id="emp-pay-net" class="text-2xl font-bold text-green-600">₱0.00</p>
                        </div>
                        <div class="rounded-xl border bg-white shadow-sm p-4">
                            <p class="text-sm text-gray-500">Payslips</p>
                            <p class="text-sm text-gray-500"><span id="emp-pay-paid" class="font-semibold">0</span> Paid • <span id="emp-pay-unpaid" class="font-semibold">0</span> Unpaid</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-end gap-3 mb-4 payroll-filters">
                        <div>
                            <label for="pay-start" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input id="pay-start" type="date" class="border rounded px-3 py-2" />
                        </div>
                        <div>
                            <label for="pay-end" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input id="pay-end" type="date" class="border rounded px-3 py-2" />
                        </div>
                        <div>
                            <label for="pay-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="pay-status" class="border rounded px-3 py-2">
                                <option value="">All</option>
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                            </select>
                        </div>
                        <button id="pay-apply" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500">Apply</button>
                    </div>

                    <div class="rounded-xl border bg-white shadow-sm p-2 overflow-x-auto">
                        <table class="min-w-full text-sm payroll-table">

                            <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="text-left p-2">Period</th>
                                    <th class="text-left p-2">Gross</th>
                                    <th class="text-left p-2">Deductions</th>
                                    <th class="text-left p-2">Net</th>
                                    <th class="text-left p-2">Status</th>
                                    <th class="text-left p-2">Payment Date</th>
                                </tr>
                            </thead>
                            <tbody id="emp-pay-rows"></tbody>
                        </table>
                        <div id="emp-pay-none" class="p-4 text-center text-gray-500 hidden">No records found.</div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/employee_sidebar_toggle.js"></script>
    <script src="../js/employee_sidebar_profile.js"></script>
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
        function formatPhp(n){ const num = parseFloat(n||'0'); if (isNaN(num)) return '₱0.00'; return '₱' + num.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }

        async function loadSummary(){
            try{
                const s = document.getElementById('pay-start').value;
                const e = document.getElementById('pay-end').value;
                const q = new URLSearchParams({action:'summary', start:s, end:e});
                const res = await fetch('../views/employee_payroll_handler.php?'+q.toString());
                const out = await res.json();
                if(!out.success) throw new Error(out.message||'Failed to load summary');
                document.getElementById('emp-pay-gross').textContent = formatPhp(out.data.total_gross);
                document.getElementById('emp-pay-deductions').textContent = formatPhp(out.data.total_deductions);
                document.getElementById('emp-pay-net').textContent = formatPhp(out.data.total_net);
                document.getElementById('emp-pay-paid').textContent = out.data.paid_count ?? 0;
                document.getElementById('emp-pay-unpaid').textContent = out.data.unpaid_count ?? 0;
            }catch(e){ showStatus(e.message||'Failed to load summary', 'error'); }
        }

        async function loadList(){
            try{
                const s = document.getElementById('pay-start').value;
                const e = document.getElementById('pay-end').value;
                const st = document.getElementById('pay-status').value;
                const q = new URLSearchParams({action:'list', start:s, end:e});
                if(st) q.append('status', st);
                const res = await fetch('../views/employee_payroll_handler.php?'+q.toString());
                const out = await res.json();
                const tbody = document.getElementById('emp-pay-rows');
                const none = document.getElementById('emp-pay-none');
                tbody.innerHTML = '';
                if(!out.success){
                    none.classList.remove('hidden');
                    showStatus(out.message||'Failed to load list', 'error');
                    return;
                }
                const rows = Array.isArray(out.data) ? out.data : [];
                if(rows.length === 0){
                    none.classList.remove('hidden');
                    return;
                } else {
                    none.classList.add('hidden');
                }
                rows.forEach(r => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b last:border-b-0';
                    const ded = `PH: ${formatPhp(r.philhealth)} • SSS: ${formatPhp(r.sss)} • Pag-IBIG: ${formatPhp(r.pagibig)} • Other: ${formatPhp(r.other)}`;
                    const status = (r.paid_status || '').trim();
                    let statusClass = 'bg-gray-100 text-gray-700 border-gray-200';
                    if (status === 'Paid') statusClass = 'bg-green-50 text-green-700 border-green-200';
                    else if (status === 'Unpaid') statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    const statusHtml = `<span class="inline-flex items-center px-2 py-0.5 rounded-full border text-xs ${statusClass}">${status || '-'}</span>`;
                    tr.innerHTML = `
                        <td class="p-2 whitespace-nowrap">${r.period_start} to ${r.period_end}</td>
                        <td class="p-2 whitespace-nowrap">${formatPhp(r.gross)}</td>
                        <td class="p-2 whitespace-nowrap">${ded}</td>
                        <td class="p-2 whitespace-nowrap">${formatPhp(r.net)}</td>
                        <td class="p-2 whitespace-nowrap">${statusHtml}</td>
                        <td class="p-2 whitespace-nowrap">${r.payment_date || '-'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }catch(e){ showStatus(e.message||'Failed to load list', 'error'); }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const end = new Date();
            const start = new Date(); start.setMonth(end.getMonth()-3);
            const elS = document.getElementById('pay-start');
            const elE = document.getElementById('pay-end');
            elS.value = ymd(start);
            elE.value = ymd(end);
            document.getElementById('pay-apply').addEventListener('click', () => { loadSummary(); loadList(); });
            loadSummary();
            loadList();
        });
    </script>

</body>

</html>