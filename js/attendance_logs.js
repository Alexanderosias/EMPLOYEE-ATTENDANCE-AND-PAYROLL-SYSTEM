
// Sample attendance data with separate AM/PM in/out times
const attendanceData = [
  {
    id: 'E001',
    name: 'Francis Rivas',
    date: '2025-09-15',
    timeInAM: '08:00 AM',
    timeOutAM: '12:00 PM',
    timeInPM: '01:00 PM',
    timeOutPM: '05:00 PM',
    status: 'Present'
  },
  {
    id: 'E002',
    name: 'Adela Onlao',
    date: '2025-09-15',
    timeInAM: '08:15 AM',
    timeOutAM: '12:05 PM',
    timeInPM: '01:10 PM',
    timeOutPM: '05:10 PM',
    status: 'Present'
  },
  {
    id: 'E003',
    name: 'Dennis Gresola',
    date: '2025-09-15',
    timeInAM: '08:05 AM',
    timeOutAM: '12:00 PM',
    timeInPM: '01:00 PM',
    timeOutPM: '04:55 PM',
    status: 'Present'
  },
  {
    id: 'E004',
    name: 'Rodolfo Puyat',
    date: '2025-09-15',
    timeInAM: 'Absent',
    timeOutAM: '-',
    timeInPM: '-',
    timeOutPM: '-',
    status: 'Absent'
  },
  {
    id: 'E001',
    name: 'Edsun Caldoza',
    date: '2025-09-16',
    timeInAM: '08:02 AM',
    timeOutAM: '12:00 PM',
    timeInPM: '01:00 PM',
    timeOutPM: '05:01 PM',
    status: 'Present'
  },
  {
    id: 'E002',
    name: 'Ciala Dismaya',
    date: '2025-09-16',
    timeInAM: '08:10 AM',
    timeOutAM: '12:00 PM',
    timeInPM: '01:05 PM',
    timeOutPM: '05:05 PM',
    status: 'Present'
  },
  {
    id: 'E003',
    name: 'Martin Romouldez',
    date: '2025-09-16',
    timeInAM: '08:00 AM',
    timeOutAM: '12:00 PM',
    timeInPM: '01:00 PM',
    timeOutPM: '05:00 PM',
    status: 'Present'
  },
  {
    id: 'E004',
    name: 'Allan Cayetano',
    date: '2025-09-16',
    timeInAM: '08:20 AM',
    timeOutAM: '12:00 PM',
    timeInPM: '01:15 PM',
    timeOutPM: '05:15 PM',
    status: 'Late'
  },
  // Add more sample data as needed
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

  pageData.forEach(log => {
    const tr = document.createElement('tr');
    tr.className = 'hover:bg-gray-50';

    tr.innerHTML = `
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.id}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.name}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.date}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeInAM}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeOutAM}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeInPM}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${log.timeOutPM}</td>
              <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${log.status === 'Absent' ? 'text-red-600' : log.status === 'Late' ? 'text-yellow-600' : 'text-green-600'}">${log.status}</td>
            `;
    tableBody.appendChild(tr);
  });

  pageInfo.textContent = `Page ${currentPage}`;
  updatePaginationButtons();
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

// Initial render
renderTablePage(currentPage);