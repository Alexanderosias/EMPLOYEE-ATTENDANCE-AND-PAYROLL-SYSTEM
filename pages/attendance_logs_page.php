<?php
require_once '../views/auth.php'; // path relative to the page
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EAAPS Attendance Logs Page</title>
  <link rel="icon" href="img/adfc_logo.png" type="image/x-icon">
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="src/styles.css" />
  <style>
    /* Style for Import Attendance container */
    .import-attendance-container {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .import-attendance-container label {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.3rem;
      background-color: #2563eb;
      color: white;
      padding: 0.4rem 0.8rem;
      border-radius: 0.375rem;
      font-weight: 600;
      font-size: 0.9rem;
      user-select: none;
      transition: background-color 0.3s;
    }

    .import-attendance-container label:hover {
      background-color: #1d4ed8;
    }

    .import-attendance-container label img {
      width: 20px;
      height: 20px;
    }

    .import-attendance-container input[type="file"] {
      display: none;
    }

    /* Add cursor pointer to edit and delete icons */
    .edit-icon,
    .delete-icon {
      cursor: pointer;
      width: 20px;
      height: 20px;
      margin-left: 8px;
      vertical-align: middle;
    }

    /* Center Actions column text */
    th.actions-header,
    td.actions-cell {
      text-align: center;
      white-space: nowrap;
    }

    .actions-cell {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      /* space between icons */
    }


    /* Modal styles */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal {
      background: white;
      border-radius: 0.5rem;
      padding: 1.5rem;
      width: 420px;
      max-width: 90vw;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
      position: relative;
    }

    .modal h3 {
      margin-top: 0;
      margin-bottom: 1rem;
      font-size: 1.25rem;
      font-weight: 700;
      color: #2563eb;
    }

    .modal label {
      display: block;
      margin-bottom: 0.25rem;
      font-weight: 600;
      color: #374151;
    }

    .modal input,
    .modal select {
      width: 100%;
      padding: 0.4rem 0.6rem;
      margin-bottom: 1rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 1rem;
      color: #374151;
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }

    .modal-buttons button {
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      border: none;
      transition: background-color 0.3s;
    }

    .modal-buttons .save-btn {
      background-color: #2563eb;
      color: white;
    }

    .modal-buttons .save-btn:hover {
      background-color: #1d4ed8;
    }

    .modal-buttons .cancel-btn {
      background-color: #e5e7eb;
      color: #374151;
    }

    .modal-buttons .cancel-btn:hover {
      background-color: #d1d5db;
    }
  </style>
</head>

<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <a class="sidebar-header" href="#">
        <img src="img/adfc_logo_by_jintokai_d4pchwp-fullview.png" alt="Logo" class="logo" />
        <span class="app-name">EAAPS Admin</span>
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
          <li class="active">
            <a href="#">
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
          <?php endif; ?>
          <li>
            <a href="settings_page.php">
              <img src="icons/coghweel.png" alt="Settings" class="icon" />
              Settings
            </a>
          </li>
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
          <h2>ATTENDANCE LOGS</h2>
        </div>
        <div>
          <p id="current-datetime"></p>
        </div>
      </header>
      <section class="attendance-logs max-w-7xl mx-auto">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center;">
          <div class="flex items-center space-x-2">
            <label for="rows-per-page" class="text-gray-700 text-sm font-medium">Show</label>
            <select id="rows-per-page"
              class="border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="5" selected>5</option>
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <span class="text-gray-700 text-sm">entries</span>
            <input type="date" id="filter-date"
              class="border border-gray-300 rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <button id="filter-btn"
              class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">Filter</button>
            <button id="clear-filter-btn"
              class="bg-gray-300 text-gray-700 px-4 py-1 rounded hover:bg-gray-400 transition">Clear</button>
          </div>

          <div class="flex space-x-2 items-center">
            <!-- Import Attendance Control -->
            <div class="import-attendance-container" title="Import Attendance Excel File">
              <label for="import-attendance-file">
                <img src="icons/import.png" alt="Import Icon" />
                Import Attendance
              </label>
              <input type="file" id="import-attendance-file" accept=".xls,.xlsx" />
            </div>
          </div>
        </div>

        <div class="overflow-x-auto bg-white rounded shadow">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Employee ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Employee Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Time In</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Time Out</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Partial Absence Hours</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status</th>
                <th scope="col"
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider actions-header">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody id="attendance-table-body" class="bg-white divide-y divide-gray-200">
              <!-- Attendance log rows will be inserted here dynamically -->
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end space-x-2">
          <button id="prev-page" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
            disabled>Previous</button>
          <span id="page-info" class="self-center text-gray-700 text-sm">Page 1</span>
          <button id="next-page" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</button>
        </div>
      </section>
    </main>
  </div>

  <!-- Edit Modal -->
  <div class="modal-overlay" id="edit-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <div class="modal">
      <h3 id="edit-modal-title">Edit Attendance</h3>
      <form id="edit-attendance-form">
        <label for="edit-time-in">Time In</label>
        <input type="text" id="edit-time-in" name="timeIn" placeholder="e.g., 08:00 AM" required />

        <label for="edit-time-out">Time Out</label>
        <input type="text" id="edit-time-out" name="timeOut" placeholder="e.g., 05:00 PM" required />

        <label>Partial Absence Time</label>
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
          <input type="time" id="partial-absence-start" name="partialAbsenceStart" />
          <input type="time" id="partial-absence-end" name="partialAbsenceEnd" />
        </div>

        <label for="edit-status">Status</label>
        <select id="edit-status" name="status" required>
          <option value="Present">Present</option>
          <option value="Absent">Absent</option>
          <option value="Late">Late</option>
          <option value="On Leave">On Leave</option>
        </select>

        <div class="modal-buttons">
          <button type="button" class="cancel-btn" id="edit-cancel-btn">Cancel</button>
          <button type="submit" class="save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Sample attendance data with single timeIn, timeOut, and partialAbsenceHours
    const attendanceData = [{
        id: 'E001',
        name: 'Francis Rivas',
        date: '2025-09-15',
        timeIn: '08:00 AM',
        timeOut: '05:00 PM',
        partialAbsenceHours: 0,
        status: 'Present'
      },
      {
        id: 'E002',
        name: 'Adela Onlao',
        date: '2025-09-15',
        timeIn: '08:15 AM',
        timeOut: '05:10 PM',
        partialAbsenceHours: 0,
        status: 'Present'
      },
    ];

    let rowsPerPage = 5; // default rows per page
    let currentPage = 1;
    let filteredData = attendanceData;

    const tableBody = document.getElementById('attendance-table-body');
    const filterDateInput = document.getElementById('filter-date');
    const filterBtn = document.getElementById('filter-btn');
    const clearFilterBtn = document.getElementById('clear-filter-btn');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const pageInfo = document.getElementById('page-info');
    const rowsPerPageSelect = document.getElementById('rows-per-page');
    const importFileInput = document.getElementById('import-attendance-file');

    // Modal elements
    const modalOverlay = document.getElementById('edit-modal-overlay');
    const editForm = document.getElementById('edit-attendance-form');
    const editTimeIn = document.getElementById('edit-time-in');
    const editTimeOut = document.getElementById('edit-time-out');
    const editStatus = document.getElementById('edit-status');
    const editCancelBtn = document.getElementById('edit-cancel-btn');
    const partialAbsenceStart = document.getElementById('partial-absence-start');
    const partialAbsenceEnd = document.getElementById('partial-absence-end');

    let currentEditIndex = null;

    function renderTablePage(page) {
      tableBody.innerHTML = '';
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const pageData = filteredData.slice(start, end);

      if (pageData.length === 0 && page > 1) {
        currentPage--;
        renderTablePage(currentPage);
        updatePaginationButtons();
        return;
      }

      pageData.forEach((log, index) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';

        // Format partialAbsenceHours to 1 decimal place or 0 if falsy
        const partialAbsenceDisplay = log.partialAbsenceHours ? log.partialAbsenceHours.toFixed(1) : '0';

        tr.innerHTML = `
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.id}</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.name}</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.date}</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeIn}</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeOut}</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${partialAbsenceDisplay}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${log.status === 'Absent'
            ? 'text-red-600' : log.status === 'Late' ? 'text-yellow-600' : 'text-green-600'}">${log.status}</td>
          <td class="px-6 py-3 whitespace-nowrap text-center text-sm actions-cell">
            <img src="icons/update.png" alt="Edit" class="edit-icon" title="Edit Attendance" data-index="${start + index}" />
            <img src="icons/delete.png" alt="Delete Partial Absence" class="delete-icon" title="Delete Partial Absence" data-index="${start + index}" />
          </td>
        `;
        tableBody.appendChild(tr);
      });

      pageInfo.textContent = `Page ${currentPage}`;
      updatePaginationButtons();

      // Attach event listeners for edit and delete icons
      document.querySelectorAll('.edit-icon').forEach(icon => {
        icon.addEventListener('click', onEditClick);
      });
      document.querySelectorAll('.delete-icon').forEach(icon => {
        icon.addEventListener('click', onDeletePartialAbsence);
      });
    }

    function updatePaginationButtons() {
      prevPageBtn.disabled = currentPage === 1;
      nextPageBtn.disabled = currentPage * rowsPerPage >= filteredData.length;
    }

    filterBtn.addEventListener('click', () => {
      const selectedDate = filterDateInput.value;
      if (selectedDate) {
        filteredData = attendanceData.filter(log => log.date === selectedDate);
      } else {
        filteredData = attendanceData;
      }
      currentPage = 1;
      renderTablePage(currentPage);
    });

    clearFilterBtn.addEventListener('click', () => {
      filterDateInput.value = '';
      filteredData = attendanceData;
      currentPage = 1;
      renderTablePage(currentPage);
    });

    prevPageBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        renderTablePage(currentPage);
      }
    });

    nextPageBtn.addEventListener('click', () => {
      if (currentPage * rowsPerPage < filteredData.length) {
        currentPage++;
        renderTablePage(currentPage);
      }
    });

    rowsPerPageSelect.addEventListener('change', (e) => {
      rowsPerPage = parseInt(e.target.value, 10);
      currentPage = 1;
      renderTablePage(currentPage);
    });

    importFileInput.addEventListener('change', (event) => {
      const file = event.target.files[0];
      if (!file) return;
      alert(`Selected file: ${file.name}\n\nImplement Excel parsing and data import here.`);
      importFileInput.value = '';
    });

    function parseTimeToMinutes(timeStr) {
      if (!timeStr) return null;
      const [hours, minutes] = timeStr.split(':').map(Number);
      if (isNaN(hours) || isNaN(minutes)) return null;
      return hours * 60 + minutes;
    }

    function calculatePartialAbsenceHours(startTime, endTime) {
      const startMinutes = parseTimeToMinutes(startTime);
      const endMinutes = parseTimeToMinutes(endTime);
      if (startMinutes === null || endMinutes === null || endMinutes <= startMinutes) return 0;
      return (endMinutes - startMinutes) / 60; // decimal hours
    }

    function onEditClick(event) {
      const index = parseInt(event.target.getAttribute('data-index'), 10);
      currentEditIndex = index;
      const record = filteredData[index];

      editTimeIn.value = record.timeIn;
      editTimeOut.value = record.timeOut;
      editStatus.value = record.status;

      // Clear partial absence time inputs on open
      partialAbsenceStart.value = '';
      partialAbsenceEnd.value = '';

      modalOverlay.classList.add('active');
      editTimeIn.focus();
    }

    function onDeletePartialAbsence(event) {
      const index = parseInt(event.target.getAttribute('data-index'), 10);
      const record = filteredData[index];
      if (!record) return;

      // Confirm deletion
      if (!confirm(`Delete Partial Absence Hours for ${record.name} on ${record.date}?`)) return;

      // Reset partialAbsenceHours to 0
      record.partialAbsenceHours = 0;

      // Update original data as well
      const originalIndex = attendanceData.findIndex(r =>
        r.id === record.id && r.date === record.date
      );
      if (originalIndex !== -1) {
        attendanceData[originalIndex].partialAbsenceHours = 0;
      }

      renderTablePage(currentPage);
    }

    editCancelBtn.addEventListener('click', () => {
      modalOverlay.classList.remove('active');
      currentEditIndex = null;
    });

    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        modalOverlay.classList.remove('active');
        currentEditIndex = null;
      }
    });

    editForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if (currentEditIndex === null) return;

      const timeInVal = editTimeIn.value.trim();
      const timeOutVal = editTimeOut.value.trim();
      const statusVal = editStatus.value;

      // Calculate partial absence hours from inputs
      const startTime = partialAbsenceStart.value;
      const endTime = partialAbsenceEnd.value;
      const newPartialHours = calculatePartialAbsenceHours(startTime, endTime);

      const record = filteredData[currentEditIndex];

      // Update timeIn, timeOut, status
      record.timeIn = timeInVal;
      record.timeOut = timeOutVal;
      record.status = statusVal;

      // Add new partial absence hours to existing value if > 0
      if (newPartialHours > 0) {
        record.partialAbsenceHours = (record.partialAbsenceHours || 0) + newPartialHours;
      }

      // Update original data as well
      const originalIndex = attendanceData.findIndex(r =>
        r.id === record.id && r.date === record.date
      );
      if (originalIndex !== -1) {
        attendanceData[originalIndex] = {
          ...record
        };
      }

      renderTablePage(currentPage);
      modalOverlay.classList.remove('active');
      currentEditIndex = null;
    });

    // Initial render
    renderTablePage(currentPage);
  </script>

  <script src="../js/dashboard.js"></script>
  <script src="../js/current_time.js"></script>
  <script src="../js/attendance_logs.js"></script>
</body>

</html>