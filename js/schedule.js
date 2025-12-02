const BASE_PATH = ""; // Change to '' for localhost:8000, or '/newpath' for Hostinger

const API_BASE = BASE_PATH + "/views/schedules.php"; // Backend endpoint

function showStatus(message, type = "success") {
  const statusDiv = document.getElementById("status-message");
  if (!statusDiv) return;

  statusDiv.textContent = message;
  statusDiv.className = `status-message ${type}`;
  statusDiv.classList.add("show");

  setTimeout(() => {
    statusDiv.classList.remove("show");
  }, 3000);
}

const employeeSelect = document.getElementById("employee-select");
const weeklyScheduleContainer = document.getElementById(
  "weekly-schedule-container"
);
const scheduleModal = document.getElementById("schedule-modal");
const closeModalBtn = document.getElementById("close-modal-btn");
const modalTitle = document.getElementById("modal-title");
const modalEmployeeNameInput = document.getElementById("modal-employee-name");
const modalDayOfWeekInput = document.getElementById("modal-day-of-week");
const modalShiftStartInput = document.getElementById("modal-shift-start");
const modalShiftEndInput = document.getElementById("modal-shift-end");
const shiftTypeRadios = document.querySelectorAll('input[name="shift-type"]');
const modalShiftDetailsInput = document.getElementById("modal-shift-details");
const saveShiftBtn = document.getElementById("save-shift-btn");
const deleteShiftBtn = document.getElementById("delete-shift-btn");

const searchInput = document.getElementById("employee-search-input");
const searchBtn = document.getElementById("employee-search-btn");
const filterJobPosition = document.getElementById("filter-job-position");
const filterDepartment = document.getElementById("filter-department");

let employees = [];
let weeklySchedule = {}; // Will be populated from API
let selectedDayOfWeek = null;
let selectedShiftId = null;

const COLORS = [
  "bg-blue-500",
  "bg-green-500",
  "bg-purple-500",
  "bg-yellow-500",
  "bg-red-500",
  "bg-indigo-500",
  "bg-pink-500",
  "bg-teal-500",
];
const DAY_NAMES = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];

// Flexible confirmation modal function
function showConfirmation(
  message,
  confirmText = "Confirm",
  confirmColor = "blue"
) {
  return new Promise((resolve) => {
    const confirmationModal = document.getElementById("confirmation-modal");
    const confirmationMessage = document.getElementById("confirmation-message");
    const confirmationConfirmBtn = document.getElementById(
      "confirmation-confirm-btn"
    );
    const confirmationCancelBtn = document.getElementById(
      "confirmation-cancel-btn"
    );
    const confirmationCloseX = document.getElementById("confirmation-close-x");

    // Set message
    confirmationMessage.textContent = message;

    // Set button text and color
    confirmationConfirmBtn.textContent = confirmText;
    confirmationConfirmBtn.className = `px-4 py-2 bg-${confirmColor}-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-${confirmColor}-600 focus:outline-none focus:ring-2 focus:ring-${confirmColor}-300`;

    // Show modal
    confirmationModal.classList.remove("hidden");
    confirmationModal.setAttribute("aria-hidden", "false");

    // Handle confirm
    const handleConfirm = () => {
      cleanup();
      resolve(true);
    };

    // Handle cancel
    const handleCancel = () => {
      cleanup();
      resolve(false);
    };

    // Cleanup function
    const cleanup = () => {
      confirmationModal.classList.add("hidden");
      confirmationModal.setAttribute("aria-hidden", "true");
      confirmationConfirmBtn.removeEventListener("click", handleConfirm);
      confirmationCancelBtn.removeEventListener("click", handleCancel);
      confirmationCloseX.removeEventListener("click", handleCancel);
    };

    // Attach event listeners
    confirmationConfirmBtn.addEventListener("click", handleConfirm);
    confirmationCancelBtn.addEventListener("click", handleCancel);
    confirmationCloseX.addEventListener("click", handleCancel);

    // Close on Escape
    const handleEscape = (e) => {
      if (
        e.key === "Escape" &&
        !confirmationModal.classList.contains("hidden")
      ) {
        handleCancel();
        document.removeEventListener("keydown", handleEscape);
      }
    };
    document.addEventListener("keydown", handleEscape);
  });
}

// --- Helpers ---
function getEmployeeColor(employeeId) {
  const index = employees.findIndex((emp) => emp.id === employeeId);
  return COLORS[index % COLORS.length];
}

// --- Populate Filters ---
function populateFilters() {
  const jobPositions = [...new Set(employees.map((e) => e.jobPosition))].sort();
  const departments = [...new Set(employees.map((e) => e.department))].sort();

  filterJobPosition.innerHTML = '<option value="">All Job Positions</option>';
  filterDepartment.innerHTML = '<option value="">All Departments</option>';

  jobPositions.forEach((pos) => {
    const option = document.createElement("option");
    option.value = pos;
    option.textContent = pos;
    filterJobPosition.appendChild(option);
  });

  departments.forEach((dep) => {
    const option = document.createElement("option");
    option.value = dep;
    option.textContent = dep;
    filterDepartment.appendChild(option);
  });
}

// --- Fetch Employees ---
async function fetchEmployees() {
  try {
    const response = await fetch(`${API_BASE}?action=list_employees`);
    if (!response.ok) throw new Error("Failed to fetch employees");
    const data = await response.json();
    if (!data.success)
      throw new Error(data.message || "Error fetching employees");
    employees = data.data.map((emp) => ({
      id: emp.id,
      name: `${emp.first_name} ${emp.last_name} (${emp.position_name})`,
      jobPosition: emp.position_name,
      department: emp.department_name,
    }));
    populateFilters();
    renderEmployeeOptions();
  } catch (error) {
    console.error("Error fetching employees:", error);
    showStatus("Failed to load employees.", "error");
  }
}

// --- Fetch Schedules for Employee ---
async function fetchSchedules(employeeId) {
  try {
    const response = await fetch(
      `${API_BASE}?action=list_schedules&employee_id=${employeeId}`
    );
    if (!response.ok) throw new Error("Failed to fetch schedules");
    const data = await response.json();
    if (!data.success)
      throw new Error(data.message || "Error fetching schedules");
    weeklySchedule[employeeId] = {};
    data.data.forEach((schedule) => {
      const day = schedule.day_of_week;
      if (!weeklySchedule[employeeId][day])
        weeklySchedule[employeeId][day] = [];
      weeklySchedule[employeeId][day].push({
        id: schedule.id,
        employeeId: schedule.employee_id,
        dayOfWeek: day,
        start: schedule.start_time,
        end: schedule.end_time,
        details: schedule.shift_name,
        is_working: schedule.is_working,
        break_minutes: schedule.break_minutes,
      });
    });
  } catch (error) {
    console.error("Error fetching schedules:", error);
    showStatus("Failed to load schedules.", "error");
  }
}

// --- Render Employee Options ---
function renderEmployeeOptions() {
  const searchTerm = searchInput.value.trim().toLowerCase();
  const selectedJob = filterJobPosition.value;
  const selectedDept = filterDepartment.value;

  employeeSelect.innerHTML = "";

  const filteredEmployees = employees.filter((emp) => {
    const matchesSearch = emp.name.toLowerCase().includes(searchTerm);
    const matchesJob = selectedJob === "" || emp.jobPosition === selectedJob;
    const matchesDept = selectedDept === "" || emp.department === selectedDept;
    return matchesSearch && matchesJob && matchesDept;
  });

  if (filteredEmployees.length === 0) {
    const option = document.createElement("option");
    option.textContent = "No employees found";
    option.disabled = true;
    employeeSelect.appendChild(option);
  } else {
    filteredEmployees.forEach((emp) => {
      const option = document.createElement("option");
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
  weeklyScheduleContainer.innerHTML = "";
  const selectedEmployeeId = employeeSelect.value;
  if (!selectedEmployeeId) return;

  // Fetch schedules if not already loaded
  if (!weeklySchedule[selectedEmployeeId]) {
    fetchSchedules(selectedEmployeeId).then(() => renderWeeklySchedule());
    return;
  }

  const employeeSchedule = weeklySchedule[selectedEmployeeId] || {};

  for (let day = 0; day < 7; day++) {
    const dayCard = document.createElement("div");
    dayCard.className = "day-card";
    dayCard.dataset.dayOfWeek = day;

    const dayHeader = document.createElement("h3");
    dayHeader.className = "day-header";
    dayHeader.textContent = DAY_NAMES[day];
    dayCard.appendChild(dayHeader);

    const shifts = employeeSchedule[day] || [];
    shifts.forEach((shift) => {
      const shiftBadge = document.createElement("div");
      shiftBadge.className = `${getEmployeeColor(
        selectedEmployeeId
      )} shift-badge ${shift.is_working ? "" : "break-shift"}`;
      shiftBadge.textContent = `${shift.start} - ${shift.end} (${shift.details})`;

      const deleteBtn = document.createElement("button");
      deleteBtn.className = "delete-btn";
      deleteBtn.innerHTML = "&times;";
      deleteBtn.onclick = async (e) => {
        e.stopPropagation();
        const confirmed = await showConfirmation(
          "Are you sure you want to delete this schedule? This action cannot be undone.",
          "Delete Schedule",
          "red"
        );
        if (confirmed) {
          deleteSchedule(shift.id, selectedEmployeeId);
        }
      };

      shiftBadge.appendChild(deleteBtn);
      dayCard.appendChild(shiftBadge);
    });

    const addShiftBtn = document.createElement("button");
    addShiftBtn.className =
      "w-full mt-2 py-2 px-4 bg-gray-200 text-gray-700 font-bold rounded-md hover:bg-gray-300 transition-colors duration-200 text-sm";
    addShiftBtn.textContent = "+ Add Class/Shift";
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
    const action = selectedShiftId ? "update_schedule" : "add_schedule";
    const formData = new FormData();
    formData.append("action", action);
    formData.append("employee_id", scheduleData.employeeId);
    formData.append("day_of_week", scheduleData.dayOfWeek);
    formData.append("shift_name", scheduleData.details);
    formData.append("start_time", scheduleData.start);
    formData.append("end_time", scheduleData.end);
    formData.append("is_working", scheduleData.is_working);
    formData.append("break_minutes", scheduleData.break_minutes);
    if (selectedShiftId) formData.append("id", selectedShiftId);

    const response = await fetch(API_BASE, { method: "POST", body: formData });
    let errorMessage = "Failed to save schedule.";
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
    if (!data.success) throw new Error(data.message || "Error saving schedule");

    showStatus("Schedule saved successfully.", "success");
    // Refresh schedules for the employee
    await fetchSchedules(scheduleData.employeeId);
    renderWeeklySchedule();
    closeModal();
  } catch (error) {
    console.error("Error saving schedule:", error);
    showStatus(error.message, "error"); // Now shows the specific backend error
  }
}

async function deleteSchedule(shiftId, employeeId) {
  try {
    const formData = new FormData();
    formData.append("action", "delete_schedule");
    formData.append("id", shiftId);

    const response = await fetch(API_BASE, { method: "POST", body: formData });
    if (!response.ok) throw new Error("Failed to delete schedule");
    const data = await response.json();
    if (!data.success)
      throw new Error(data.message || "Error deleting schedule");

    showStatus("Schedule deleted successfully.", "success");
    // Refresh schedules
    await fetchSchedules(employeeId);
    renderWeeklySchedule();
  } catch (error) {
    console.error("Error deleting schedule:", error);
    showStatus("Failed to delete schedule.", "error");
  }
}

// --- Modal ---
function openModal(shift) {
  scheduleModal.classList.remove("hidden");
  selectedDayOfWeek = shift.dayOfWeek;
  selectedShiftId = shift.id || null;

  modalEmployeeNameInput.value =
    employeeSelect.options[employeeSelect.selectedIndex]?.text || "";
  modalDayOfWeekInput.value = DAY_NAMES[selectedDayOfWeek];

  if (shift.id) {
    modalTitle.textContent = "Edit Class";
    saveShiftBtn.textContent = "Update Class";
    deleteShiftBtn.classList.remove("hidden");
    modalShiftStartInput.value = shift.start;
    modalShiftEndInput.value = shift.end;
    modalShiftDetailsInput.value = shift.details;
    const isWorking = shift.is_working !== undefined ? shift.is_working : true; // Default to working if not set
    document.querySelector(
      `input[name="shift-type"][value="${isWorking ? "working" : "break"}"]`
    ).checked = true;
    toggleShiftDetails(); // Update visibility
  } else {
    modalTitle.textContent = "Add Class";
    saveShiftBtn.textContent = "Save Class";
    deleteShiftBtn.classList.add("hidden");
    modalShiftStartInput.value = "";
    modalShiftEndInput.value = "";
    modalShiftDetailsInput.value = "";
    document.querySelector(
      'input[name="shift-type"][value="working"]'
    ).checked = true;
    toggleShiftDetails();
  }
}

function toggleShiftDetails() {
  const selectedType = document.querySelector(
    'input[name="shift-type"]:checked'
  ).value;
  if (selectedType === "break") {
    modalShiftDetailsInput.style.display = "none";
    modalShiftDetailsInput.value = "BREAK"; // Auto-set
  } else {
    modalShiftDetailsInput.style.display = "block";
    if (modalShiftDetailsInput.value === "BREAK")
      modalShiftDetailsInput.value = ""; // Reset if was break
  }
}

// Add event listener for radios
shiftTypeRadios.forEach((radio) => {
  radio.addEventListener("change", toggleShiftDetails);
});

function closeModal() {
  scheduleModal.classList.add("hidden");
  selectedDayOfWeek = null;
  selectedShiftId = null;
}

// --- Event Listeners ---
document.addEventListener("DOMContentLoaded", () => {
  fetchEmployees();
});

employeeSelect.addEventListener("change", renderWeeklySchedule);
closeModalBtn.addEventListener("click", closeModal);

// Update saveShiftBtn click
saveShiftBtn.addEventListener("click", () => {
  const selectedType = document.querySelector(
    'input[name="shift-type"]:checked'
  ).value;
  const details = modalShiftDetailsInput.value.trim();
  const start = modalShiftStartInput.value;
  const end = modalShiftEndInput.value;
  if (selectedType === "working" && !details) {
    showStatus("Please enter the shift/class name.", "error");
    modalShiftDetailsInput.focus();
    return;
  }
  if (!start || !end) {
    showStatus("Please enter start and end times.", "error");
    if (!start) modalShiftStartInput.focus();
    else modalShiftEndInput.focus();
    return;
  }

  // Calculate break_minutes if break
  let breakMinutes = 0;
  let isWorking = 1;
  if (selectedType === "break") {
    isWorking = 0;
    const startDate = new Date(`1970-01-01T${start}:00`);
    const endDate = new Date(`1970-01-01T${end}:00`);
    breakMinutes = Math.floor((endDate - startDate) / (1000 * 60)); // Minutes
  }
  const scheduleData = {
    employeeId: employeeSelect.value,
    dayOfWeek: selectedDayOfWeek,
    start: start,
    end: end,
    details: selectedType === "break" ? "BREAK" : details,
    is_working: isWorking,
    break_minutes: breakMinutes,
  };
  saveSchedule(scheduleData);
});

deleteShiftBtn.addEventListener("click", () => {
  if (selectedShiftId) {
    deleteSchedule(selectedShiftId, employeeSelect.value);
    closeModal();
  }
});

searchInput.addEventListener("input", renderEmployeeOptions);
filterJobPosition.addEventListener("change", renderEmployeeOptions);
filterDepartment.addEventListener("change", renderEmployeeOptions);
searchBtn.addEventListener("click", () => {
  renderEmployeeOptions();
  searchInput.focus();
});

// ============================================
// EXCEL IMPORT/EXPORT FUNCTIONALITY
// ============================================

// Download Excel Template
document
  .getElementById("download-template-btn")
  ?.addEventListener("click", (e) => {
    e.preventDefault();
    downloadExcelTemplate();
  });

function downloadExcelTemplate() {
  const template = [
    [
      "First Name",
      "Last Name",
      "Day of Week",
      "Shift Name",
      "Start Time",
      "End Time",
      "Is Working",
      "Break Minutes",
    ],
    ["Juan", "Cruz", "Monday", "Morning Shift", "08:00", "17:00", "1", "0"],
    ["Juan", "Cruz", "Monday", "Afternoon Shift", "13:00", "21:00", "1", "0"],
    ["Juan", "Cruz", "Monday", "Break", "11:00", "12:00", "0", "60"],
    ["", "", "", "", "", "", "", ""],
    ["INSTRUCTIONS:", "", "", "", "", "", "", ""],
    [
      "- First Name: Employee first name (must match exactly)",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
    ],
    [
      "- Last Name: Employee last name (must match exactly)",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
    ],
    [
      "- Day of Week: Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, or Sunday",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
    ],
    [
      "- Start/End Time: Format as HH:MM (e.g., 08:00, 17:30)",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
    ],
    [
      "- Is Working: 1 = Working shift, 0 = Break shift",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
    ],
    [
      "- Break Minutes: Total break duration in minutes",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
    ],
  ];

  const ws = XLSX.utils.aoa_to_sheet(template);

  // Set column widths
  ws["!cols"] = [
    { wch: 15 },
    { wch: 15 },
    { wch: 15 },
    { wch: 20 },
    { wch: 12 },
    { wch: 12 },
    { wch: 12 },
    { wch: 15 },
  ];

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Schedule Template");
  XLSX.writeFile(wb, "schedule_import_template.xlsx");

  showStatus("Template downloaded successfully", "success");
}

// Import Excel File Handler
document.getElementById("import-excel-btn")?.addEventListener("click", () => {
  document.getElementById("import-excel-input").click();
});

document
  .getElementById("import-excel-input")
  ?.addEventListener("change", async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.name.match(/\.(xlsx|xls)$/)) {
      showStatus("Please select a valid Excel file (.xlsx or .xls)", "error");
      e.target.value = "";
      return;
    }

    try {
      showStatus("Reading Excel file...", "success");
      const data = await readExcelFile(file);
      await processImportData(data);
      e.target.value = ""; // Reset input
    } catch (error) {
      showStatus(error.message, "error");
      e.target.value = "";
    }
  });

// Read Excel File
async function readExcelFile(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = (e) => {
      try {
        const workbook = XLSX.read(e.target.result, { type: "binary" });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const data = XLSX.utils.sheet_to_json(firstSheet, {
          header: 1,
          defval: "",
        });
        resolve(data);
      } catch (error) {
        reject(new Error("Failed to read Excel file: " + error.message));
      }
    };

    reader.onerror = () => reject(new Error("Failed to read file"));
    reader.readAsBinaryString(file);
  });
}

// Process and Validate Import Data
async function processImportData(data) {
  if (data.length < 2) {
    throw new Error("Excel file is empty or has no data rows");
  }

  const headers = data[0].map((h) => h.toString().trim());
  const rows = data.slice(1);

  // Validate required headers
  const requiredHeaders = [
    "First Name",
    "Last Name",
    "Day of Week",
    "Shift Name",
    "Start Time",
    "End Time",
  ];
  const missingHeaders = requiredHeaders.filter((h) => !headers.includes(h));

  if (missingHeaders.length > 0) {
    throw new Error(`Missing required columns: ${missingHeaders.join(", ")}`);
  }

  // Get column indices
  const colMap = {};
  headers.forEach((header, index) => {
    colMap[header] = index;
  });

  // Fetch all employees to validate names
  showStatus("Validating employee names...", "success");
  let employeeList = [];
  try {
    const response = await fetch(`${API_BASE}?action=list_employees`);
    const result = await response.json();
    if (result.success) {
      employeeList = result.data;
    } else {
      throw new Error("Failed to fetch employee list");
    }
  } catch (error) {
    throw new Error("Cannot validate employees: " + error.message);
  }

  const schedules = [];
  const errors = [];
  const notFoundEmployees = new Set();

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const rowNum = i + 2; // Excel row number (1-indexed + header)

    // Skip empty rows
    if (!row || row.every((cell) => !cell && cell !== 0)) continue;

    // Skip instruction rows
    if (row[0] && row[0].toString().toUpperCase().includes("INSTRUCTION"))
      break;

    try {
      const firstName = row[colMap["First Name"]]?.toString().trim();
      const lastName = row[colMap["Last Name"]]?.toString().trim();
      const dayOfWeek = validateDayOfWeek(row[colMap["Day of Week"]]);
      const shiftName = row[colMap["Shift Name"]]?.toString().trim();
      const startTime = validateTime(row[colMap["Start Time"]]);
      const endTime = validateTime(row[colMap["End Time"]]);
      const isWorking =
        row[colMap["Is Working"]] !== undefined &&
        row[colMap["Is Working"]] !== ""
          ? parseInt(row[colMap["Is Working"]])
          : 1;
      const breakMinutes =
        row[colMap["Break Minutes"]] !== undefined &&
        row[colMap["Break Minutes"]] !== ""
          ? parseInt(row[colMap["Break Minutes"]])
          : 0;

      // Validate names
      if (!firstName || !lastName) {
        throw new Error("Missing name");
      }

      // Find employee by name (case-insensitive)
      const employee = employeeList.find(
        (emp) =>
          emp.first_name.toLowerCase() === firstName.toLowerCase() &&
          emp.last_name.toLowerCase() === lastName.toLowerCase()
      );

      if (!employee) {
        notFoundEmployees.add(`${firstName} ${lastName}`);
        throw new Error("Employee not found");
      }

      // Validate is_working and break_minutes relationship
      if (isWorking === 1 && breakMinutes !== 0) {
        throw new Error("Working shift cannot have break minutes");
      }

      // Auto-set shift name to "BREAK" if it's a break shift (is_working = 0)
      let finalShiftName = shiftName;
      if (isWorking === 0) {
        finalShiftName = "BREAK";
        // If no shift name provided for break shift, use default
        if (!shiftName || shiftName === "") {
          finalShiftName = "BREAK";
        }
      } else {
        // For working shifts, shift name is required
        if (!shiftName || shiftName === "") {
          throw new Error("Shift name required");
        }
      }

      // Validate times
      if (startTime >= endTime) {
        throw new Error("Invalid time range");
      }

      // Validate is_working
      if (isWorking !== 0 && isWorking !== 1) {
        throw new Error("Is Working must be 0 or 1");
      }

      schedules.push({
        employee_id: employee.id,
        employee_name: `${firstName} ${lastName}`,
        day_of_week: dayOfWeek,
        shift_name: finalShiftName,
        start_time: startTime,
        end_time: endTime,
        is_working: isWorking,
        break_minutes: breakMinutes,
      });
    } catch (error) {
      errors.push(`Row ${rowNum}: ${error.message}`);
    }
  }

  // Show validation errors
  if (errors.length > 0) {
    let errorMessage = `Import validation failed:\n\n${errors
      .slice(0, 10)
      .join("\n")}${
      errors.length > 10 ? `\n\n...and ${errors.length - 10} more errors` : ""
    }`;

    if (notFoundEmployees.size > 0) {
      errorMessage += `\n\n⚠️ Employees not found in database:\n${Array.from(
        notFoundEmployees
      ).join("\n")}`;
      errorMessage += `\n\nPlease check spelling or add these employees first.`;
    }

    throw new Error(errorMessage);
  }

  if (schedules.length === 0) {
    throw new Error("No valid schedules found in file");
  }

  // Show confirmation with employee names
  const uniqueEmployees = [...new Set(schedules.map((s) => s.employee_name))];
  const confirmed = await showConfirmation(
    `Found ${schedules.length} schedule(s) for ${
      uniqueEmployees.length
    } employee(s):\n\n${uniqueEmployees.join(
      ", "
    )}\n\nDuplicates and overlaps will be skipped. Continue?`,
    "Import",
    "green"
  );

  if (!confirmed) {
    showStatus("Import cancelled", "error");
    return;
  }

  await bulkImportSchedules(schedules);
}

// Validate Day of Week
function validateDayOfWeek(day) {
  const days = {
    sunday: 0,
    monday: 1,
    tuesday: 2,
    wednesday: 3,
    thursday: 4,
    friday: 5,
    saturday: 6,
    sun: 0,
    mon: 1,
    tue: 2,
    wed: 3,
    thu: 4,
    fri: 5,
    sat: 6,
  };

  if (!day) throw new Error("Day required");

  const normalized = day.toString().toLowerCase().trim();

  if (days.hasOwnProperty(normalized)) {
    return days[normalized];
  }

  throw new Error("Invalid day");
}

// Validate Time Format
function validateTime(time) {
  if (!time && time !== 0) throw new Error("Time required");

  const timeStr = time.toString().trim();

  // Handle HH:MM format
  if (timeStr.includes(":")) {
    const parts = timeStr.split(":");
    if (parts.length === 2) {
      const hours = parseInt(parts[0]);
      const minutes = parseInt(parts[1]);

      if (isNaN(hours) || isNaN(minutes)) {
        throw new Error("Invalid time format");
      }

      if (hours < 0 || hours > 23) {
        throw new Error("Invalid hours");
      }

      if (minutes < 0 || minutes > 59) {
        throw new Error("Invalid minutes");
      }

      return `${hours.toString().padStart(2, "0")}:${minutes
        .toString()
        .padStart(2, "0")}`;
    }
  }

  // Handle Excel decimal time format (0.5 = 12:00 PM)
  const decimal = parseFloat(timeStr);
  if (!isNaN(decimal) && decimal >= 0 && decimal < 1) {
    const totalMinutes = Math.round(decimal * 24 * 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours.toString().padStart(2, "0")}:${minutes
      .toString()
      .padStart(2, "0")}`;
  }

  throw new Error("Invalid time format");
}

// Bulk Import Schedules
async function bulkImportSchedules(schedules) {
  let imported = 0;
  let skipped = 0;
  let failed = 0;
  const failedDetails = [];

  showStatus(`Importing ${schedules.length} schedule(s)...`, "success");

  for (let i = 0; i < schedules.length; i++) {
    const schedule = schedules[i];

    try {
      const formData = new URLSearchParams({
        employee_id: schedule.employee_id,
        day_of_week: schedule.day_of_week,
        shift_name: schedule.shift_name,
        start_time: schedule.start_time,
        end_time: schedule.end_time,
        is_working: schedule.is_working,
        break_minutes: schedule.break_minutes,
      });

      const response = await fetch(`${API_BASE}?action=add_schedule`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        imported++;
      } else {
        // Check if it's a duplicate/overlap (expected) or real error
        if (
          result.message.includes("already exists") ||
          result.message.includes("overlaps")
        ) {
          skipped++;
        } else {
          failed++;
          failedDetails.push(
            `Employee ${schedule.employee_id}, ${
              DAY_NAMES[schedule.day_of_week]
            }: ${result.message}`
          );
        }
      }
    } catch (error) {
      failed++;
      failedDetails.push(
        `Employee ${schedule.employee_id}, ${
          DAY_NAMES[schedule.day_of_week]
        }: ${error.message}`
      );
    }
  }

  // Show detailed results
  let message = `Import complete!\n\n`;
  message += `✓ Imported: ${imported}\n`;
  message += `⊘ Skipped (duplicates/overlaps): ${skipped}\n`;
  message += `✗ Failed: ${failed}`;

  if (failedDetails.length > 0 && failedDetails.length <= 5) {
    message += `\n\nFailed imports:\n${failedDetails.join("\n")}`;
  } else if (failedDetails.length > 5) {
    message += `\n\nFailed imports (first 5):\n${failedDetails
      .slice(0, 5)
      .join("\n")}\n...and ${failedDetails.length - 5} more`;
  }

  showStatus(message, failed > 0 ? "error" : "success");

  // Refresh current employee's schedule if viewing one
  const selectedEmpId = parseInt(employeeSelect.value);
  if (selectedEmpId) {
    await fetchSchedules(selectedEmpId);
  }

  // Reload employee list in case new ones were added
  await fetchEmployees();
}
