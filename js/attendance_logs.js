const attendanceData = [
      {
        id: 'E001',
        name: 'Francis Rivas',
        date: '2025-09-15',
        timeIn: '08:00 AM',
        timeOut: '05:00 PM',
        status: 'Present'
      },
      {
        id: 'E002',
        name: 'Adela Onlao',
        date: '2025-09-15',
        timeIn: '08:15 AM',
        timeOut: '05:10 PM',
        status: 'Present'
      },
      {
        id: 'E003',
        name: 'Dennis Gresola',
        date: '2025-09-15',
        timeIn: '08:05 AM',
        timeOut: '04:55 PM',
        status: 'Present'
      },
      {
        id: 'E004',
        name: 'Rodolfo Puyat',
        date: '2025-09-15',
        timeIn: 'Absent',
        timeOut: '-',
        status: 'Absent'
      },
      {
        id: 'E001',
        name: 'Edsun Caldoza',
        date: '2025-09-16',
        timeIn: '08:02 AM',
        timeOut: '05:01 PM',
        status: 'Present'
      },
      {
        id: 'E002',
        name: 'Ciala Dismaya',
        date: '2025-09-16',
        timeIn: '08:10 AM',
        timeOut: '05:05 PM',
        status: 'Present'
      },
      {
        id: 'E003',
        name: 'Martin Romouldez',
        date: '2025-09-16',
        timeIn: '08:00 AM',
        timeOut: '05:00 PM',
        status: 'Present'
      },
      {
        id: 'E004',
        name: 'Allan Cayetano',
        date: '2025-09-16',
        timeIn: '08:20 AM',
        timeOut: '05:15 PM',
        status: 'Late'
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

        tr.innerHTML = `
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.id}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.name}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.date}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeIn}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeOut}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${log.status === 'Absent' ? 'text-red-600' : log.status === 'Late' ? 'text-yellow-600' : 'text-green-600'}">${log.status}</td>
      <td class="px-6 py-3 whitespace-nowrap text-center text-sm actions-cell">
        <img src="icons/update.png" alt="Edit" class="edit-icon" title="Edit Attendance" data-index="${start + index}" />
      </td>
    `;
        tableBody.appendChild(tr);
      });

      pageInfo.textContent = `Page ${currentPage}`;
      updatePaginationButtons();

      document.querySelectorAll('.edit-icon').forEach(icon => {
        icon.addEventListener('click', onEditClick);
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

    function onEditClick(event) {
      const index = parseInt(event.target.getAttribute('data-index'), 10);
      currentEditIndex = index;
      const record = filteredData[index];

      editTimeIn.value = record.timeIn;
      editTimeOut.value = record.timeOut;
      editStatus.value = record.status;

      modalOverlay.classList.add('active');
      editTimeIn.focus();
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

      const record = filteredData[currentEditIndex];
      record.timeIn = timeInVal;
      record.timeOut = timeOutVal;
      record.status = statusVal;

      const originalIndex = attendanceData.findIndex(r =>
        r.id === record.id && r.date === record.date
      );
      if (originalIndex !== -1) {
        attendanceData[originalIndex] = { ...record };
      }

      renderTablePage(currentPage);
      modalOverlay.classList.remove('active');
      currentEditIndex = null;
    });

    // Initial render
    renderTablePage(currentPage);