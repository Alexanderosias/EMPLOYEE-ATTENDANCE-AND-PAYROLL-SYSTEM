const BASE_PATH = ''; // Change to '' for localhost:8000, or '/newpath' for Hostinger

const API_BASE = BASE_PATH + '/views/schedules.php';  // Backend endpoint

function showToast(message, type = 'info') {
  let toast = document.getElementById('toast-notification');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.style.cssText = `
      position: fixed; top: 20px; right: 20px; padding: 12px 20px; 
      background: #333; color: white; border-radius: 4px; z-index: 1000; 
      display: none; min-width: 300px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      font-size: 14px; text-align: center;
    `;
    document.body.appendChild(toast);
  }

  toast.textContent = message;
  toast.style.background = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#333';
  toast.style.display = 'block';
  setTimeout(() => { toast.style.display = 'none'; }, 4000);
}

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

let employees = [];
let weeklySchedule = {};  // Will be populated from API
let selectedDayOfWeek = null;
let selectedShiftId = null;

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

// --- Fetch Employees ---
async function fetchEmployees() {
  try {
    const response = await fetch(`${API_BASE}?action=list_employees`);
    if (!response.ok) throw new Error('Failed to fetch employees');
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Error fetching employees');
    employees = data.data.map(emp => ({
      id: emp.id,
      name: `${emp.first_name} ${emp.last_name} (${emp.position_name})`,
      jobPosition: emp.position_name,
      department: emp.department_name
    }));
    populateFilters();
    renderEmployeeOptions();
  } catch (error) {
    console.error('Error fetching employees:', error);
    showToast('Failed to load employees.', 'error');
  }
}

// --- Fetch Schedules for Employee ---
async function fetchSchedules(employeeId) {
  try {
    const response = await fetch(`${API_BASE}?action=list_schedules&employee_id=${employeeId}`);
    if (!response.ok) throw new Error('Failed to fetch schedules');
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Error fetching schedules');
    weeklySchedule[employeeId] = {};
    data.data.forEach(schedule => {
      const day = schedule.day_of_week;
      if (!weeklySchedule[employeeId][day]) weeklySchedule[employeeId][day] = [];
      weeklySchedule[employeeId][day].push({
        id: schedule.id,
        employeeId: schedule.employee_id,
        dayOfWeek: day,
        start: schedule.start_time,
        end: schedule.end_time,
        details: schedule.shift_name
      });
    });
  } catch (error) {
    console.error('Error fetching schedules:', error);
    showToast('Failed to load schedules.', 'error');
  }
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

  // Fetch schedules if not already loaded
  if (!weeklySchedule[selectedEmployeeId]) {
    fetchSchedules(selectedEmployeeId).then(() => renderWeeklySchedule());
    return;
  }

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
        deleteSchedule(shift.id, selectedEmployeeId);
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
async function saveSchedule(scheduleData) {
  try {
    const action = selectedShiftId ? 'update_schedule' : 'add_schedule';
    const formData = new FormData();
    formData.append('action', action);
    formData.append('employee_id', scheduleData.employeeId);
    formData.append('day_of_week', scheduleData.dayOfWeek);
    formData.append('shift_name', scheduleData.details);
    formData.append('start_time', scheduleData.start);
    formData.append('end_time', scheduleData.end);
    if (selectedShiftId) formData.append('id', selectedShiftId);

    const response = await fetch(API_BASE, { method: 'POST', body: formData });
    let errorMessage = 'Failed to save schedule.';
    if (!response.ok) {
      try {
        const data = await response.json();
        errorMessage = data.message || errorMessage;
      } catch (e) {
        // If not JSON, keep generic message
      }
      throw new Error(errorMessage);
    }
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Error saving schedule');

    showToast('Schedule saved successfully.', 'success');
    // Refresh schedules for the employee
    await fetchSchedules(scheduleData.employeeId);
    renderWeeklySchedule();
    closeModal();
  } catch (error) {
    console.error('Error saving schedule:', error);
    showToast(error.message, 'error');  // Now shows the specific backend error
  }
}


async function deleteSchedule(shiftId, employeeId) {
  try {
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('id', shiftId);

    const response = await fetch(API_BASE, { method: 'POST', body: formData });
    if (!response.ok) throw new Error('Failed to delete schedule');
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Error deleting schedule');

    showToast('Schedule deleted successfully.', 'success');
    // Refresh schedules
    await fetchSchedules(employeeId);
    renderWeeklySchedule();
  } catch (error) {
    console.error('Error deleting schedule:', error);
    showToast('Failed to delete schedule.', 'error');
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
  fetchEmployees();
});

employeeSelect.addEventListener('change', renderWeeklySchedule);
closeModalBtn.addEventListener('click', closeModal);

saveShiftBtn.addEventListener('click', () => {
  const details = modalShiftDetailsInput.value.trim();
  const start = modalShiftStartInput.value;
  const end = modalShiftEndInput.value;

  if (!details) {
    showToast('Please enter the shift/class name.', 'error');
    modalShiftDetailsInput.focus();
    return;
  }
  if (!start || !end) {
    showToast('Please enter start and end times.', 'error');
    if (!start) modalShiftStartInput.focus();
    else modalShiftEndInput.focus();
    return;
  }

  const scheduleData = {
    employeeId: employeeSelect.value,
    dayOfWeek: selectedDayOfWeek,
    start: start,
    end: end,
    details: details,
  };
  saveSchedule(scheduleData);
});


deleteShiftBtn.addEventListener('click', () => {
  if (selectedShiftId) {
    deleteSchedule(selectedShiftId, employeeSelect.value);
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
