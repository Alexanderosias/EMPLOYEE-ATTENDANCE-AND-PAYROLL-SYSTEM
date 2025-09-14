const employeeSelect = document.getElementById('employee-select');
const weeklyScheduleContainer = document.getElementById('weekly-schedule-container');
const scheduleModal = document.getElementById('schedule-modal');
const closeModalBtn = document.getElementById('close-modal-btn');
const modalTitle = document.getElementById('modal-title');
const modalEmployeeNameInput = document.getElementById('modal-employee-name');
const modalDayOfWeekInput = document.getElementById('modal-day-of-week');
const modalShiftStartInput = document.getElementById('modal-shift-start');
const modalShiftEndInput = document.getElementById('modal-shift-end');
const modalShiftDetailsInput = document.getElementById('modal-shift-details');
const saveShiftBtn = document.getElementById('save-shift-btn');
const deleteShiftBtn = document.getElementById('delete-shift-btn');

const searchInput = document.getElementById('employee-search-input');
const searchBtn = document.getElementById('employee-search-btn');
const filterJobPosition = document.getElementById('filter-job-position');
const filterDepartment = document.getElementById('filter-department');

// --- Mock Data ---
let employees = [
  { id: 'emp_01', name: 'Francis Rivas (Instructor)', jobPosition: 'Instructor', department: 'Math' },
  { id: 'emp_02', name: 'Adela Onlao (Instructor)', jobPosition: 'Instructor', department: 'Physics' },
  { id: 'emp_03', name: 'John Smith', jobPosition: 'Developer', department: 'IT' },
  { id: 'emp_04', name: 'Jane Doe', jobPosition: 'Designer', department: 'Marketing' }
];

let weeklySchedule = {
  'emp_01': {
    3: [{ id: 'shift_01', employeeId: 'emp_01', dayOfWeek: 3, start: '09:00', end: '11:00', details: 'Math' }],
    4: [{ id: 'shift_02', employeeId: 'emp_01', dayOfWeek: 4, start: '13:00', end: '15:00', details: 'Physics' }],
  },
  'emp_02': {
    1: [{ id: 'shift_03', employeeId: 'emp_02', dayOfWeek: 1, start: '10:00', end: '14:00', details: 'Register Duty' }],
  },
  'emp_03': {
    2: [{ id: 'shift_04', employeeId: 'emp_03', dayOfWeek: 2, start: '09:00', end: '17:00', details: 'Development' }],
  },
  'emp_04': {
    5: [{ id: 'shift_05', employeeId: 'emp_04', dayOfWeek: 5, start: '11:00', end: '16:00', details: 'Design Work' }],
  }
};

let selectedDayOfWeek = null;
let selectedShiftId = null;
let nextShiftId = 6;

const COLORS = [
  'bg-blue-500', 'bg-green-500', 'bg-purple-500',
  'bg-yellow-500', 'bg-red-500', 'bg-indigo-500',
  'bg-pink-500', 'bg-teal-500'
];
const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// --- Helpers ---
function getEmployeeColor(employeeId) {
  const index = employees.findIndex(emp => emp.id === employeeId);
  return COLORS[index % COLORS.length];
}

// --- Populate Filters ---
function populateFilters() {
  const jobPositions = [...new Set(employees.map(e => e.jobPosition))].sort();
  const departments = [...new Set(employees.map(e => e.department))].sort();

  filterJobPosition.innerHTML = '<option value="">All Job Positions</option>';
  filterDepartment.innerHTML = '<option value="">All Departments</option>';

  jobPositions.forEach(pos => {
    const option = document.createElement('option');
    option.value = pos;
    option.textContent = pos;
    filterJobPosition.appendChild(option);
  });

  departments.forEach(dep => {
    const option = document.createElement('option');
    option.value = dep;
    option.textContent = dep;
    filterDepartment.appendChild(option);
  });
}

// --- Render Employee Options ---
function renderEmployeeOptions() {
  const searchTerm = searchInput.value.trim().toLowerCase();
  const selectedJob = filterJobPosition.value;
  const selectedDept = filterDepartment.value;

  employeeSelect.innerHTML = '';

  const filteredEmployees = employees.filter(emp => {
    const matchesSearch = emp.name.toLowerCase().includes(searchTerm);
    const matchesJob = selectedJob === '' || emp.jobPosition === selectedJob;
    const matchesDept = selectedDept === '' || emp.department === selectedDept;
    return matchesSearch && matchesJob && matchesDept;
  });

  if (filteredEmployees.length === 0) {
    const option = document.createElement('option');
    option.textContent = 'No employees found';
    option.disabled = true;
    employeeSelect.appendChild(option);
  } else {
    filteredEmployees.forEach(emp => {
      const option = document.createElement('option');
      option.value = emp.id;
      option.textContent = emp.name;
      employeeSelect.appendChild(option);
    });
  }

  // After changing options, re-render schedule for selected employee
  renderWeeklySchedule();
}

// --- Render Weekly Schedule ---
function renderWeeklySchedule() {
  weeklyScheduleContainer.innerHTML = '';
  const selectedEmployeeId = employeeSelect.value;
  if (!selectedEmployeeId) return;

  const employeeSchedule = weeklySchedule[selectedEmployeeId] || {};

  for (let day = 0; day < 7; day++) {
    const dayCard = document.createElement('div');
    dayCard.className = 'day-card';
    dayCard.dataset.dayOfWeek = day;

    const dayHeader = document.createElement('h3');
    dayHeader.className = 'day-header';
    dayHeader.textContent = DAY_NAMES[day];
    dayCard.appendChild(dayHeader);

    const shifts = employeeSchedule[day] || [];
    shifts.forEach(shift => {
      const shiftBadge = document.createElement('div');
      shiftBadge.className = `${getEmployeeColor(selectedEmployeeId)} shift-badge`;
      shiftBadge.textContent = `${shift.start} - ${shift.end}${shift.details ? ` (${shift.details})` : ''}`;

      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'delete-btn';
      deleteBtn.innerHTML = '&times;';
      deleteBtn.onclick = e => {
        e.stopPropagation();
        deleteWeeklyTemplate(shift.id, selectedEmployeeId);
      };
      shiftBadge.appendChild(deleteBtn);
      dayCard.appendChild(shiftBadge);
    });

    const addShiftBtn = document.createElement('button');
    addShiftBtn.className = 'w-full mt-2 py-2 px-4 bg-gray-200 text-gray-700 font-bold rounded-md hover:bg-gray-300 transition-colors duration-200 text-sm';
    addShiftBtn.textContent = '+ Add Class/Shift';
    addShiftBtn.onclick = () => {
      openModal({ dayOfWeek: day, employeeId: selectedEmployeeId });
    };
    dayCard.appendChild(addShiftBtn);

    weeklyScheduleContainer.appendChild(dayCard);
  }
}

// --- CRUD Functions ---
function saveWeeklyTemplate(templateData) {
  const { employeeId, dayOfWeek } = templateData;

  if (!weeklySchedule[employeeId]) weeklySchedule[employeeId] = {};
  if (!weeklySchedule[employeeId][dayOfWeek]) weeklySchedule[employeeId][dayOfWeek] = [];

  if (selectedShiftId) {
    const shiftIndex = weeklySchedule[employeeId][dayOfWeek].findIndex(s => s.id === selectedShiftId);
    if (shiftIndex !== -1) {
      weeklySchedule[employeeId][dayOfWeek][shiftIndex] = { ...templateData, id: selectedShiftId };
    }
  } else {
    const newShift = { ...templateData, id: `shift_${nextShiftId++}` };
    weeklySchedule[employeeId][dayOfWeek].push(newShift);
  }

  renderWeeklySchedule();
  closeModal();
}

function deleteWeeklyTemplate(shiftId, employeeId) {
  const employeeSchedule = weeklySchedule[employeeId];
  if (employeeSchedule) {
    for (const day in employeeSchedule) {
      const shifts = employeeSchedule[day];
      const index = shifts.findIndex(s => s.id === shiftId);
      if (index !== -1) {
        shifts.splice(index, 1);
        break;
      }
    }
    renderWeeklySchedule();
  }
}

// --- Modal ---
function openModal(shift) {
  scheduleModal.classList.remove('hidden');
  selectedDayOfWeek = shift.dayOfWeek;
  selectedShiftId = shift.id || null;

  modalEmployeeNameInput.value = employeeSelect.options[employeeSelect.selectedIndex]?.text || '';
  modalDayOfWeekInput.value = DAY_NAMES[selectedDayOfWeek];

  if (shift.id) {
    modalTitle.textContent = 'Edit Class';
    saveShiftBtn.textContent = 'Update Class';
    deleteShiftBtn.classList.remove('hidden');
    modalShiftStartInput.value = shift.start;
    modalShiftEndInput.value = shift.end;
    modalShiftDetailsInput.value = shift.details;
  } else {
    modalTitle.textContent = 'Add Class';
    saveShiftBtn.textContent = 'Save Class';
    deleteShiftBtn.classList.add('hidden');
    modalShiftStartInput.value = '';
    modalShiftEndInput.value = '';
    modalShiftDetailsInput.value = '';
  }
}

function closeModal() {
  scheduleModal.classList.add('hidden');
  selectedDayOfWeek = null;
  selectedShiftId = null;
}

// --- Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
  populateFilters();
  renderEmployeeOptions();
});

employeeSelect.addEventListener('change', renderWeeklySchedule);
closeModalBtn.addEventListener('click', closeModal);

saveShiftBtn.addEventListener('click', () => {
  const templateData = {
    employeeId: employeeSelect.value,
    dayOfWeek: selectedDayOfWeek,
    start: modalShiftStartInput.value,
    end: modalShiftEndInput.value,
    details: modalShiftDetailsInput.value,
  };
  if (templateData.start && templateData.end) {
    saveWeeklyTemplate(templateData);
  } else {
    alert('Please enter start and end times.');
  }
});

deleteShiftBtn.addEventListener('click', () => {
  if (selectedShiftId) {
    deleteWeeklyTemplate(selectedShiftId, employeeSelect.value);
    closeModal();
  }
});

searchInput.addEventListener('input', renderEmployeeOptions);
filterJobPosition.addEventListener('change', renderEmployeeOptions);
filterDepartment.addEventListener('change', renderEmployeeOptions);
searchBtn.addEventListener('click', () => {
  renderEmployeeOptions();
  searchInput.focus();
});
