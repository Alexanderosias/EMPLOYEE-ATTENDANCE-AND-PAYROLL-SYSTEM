let rowsPerPage = 5; // default rows per page
let currentPage = 1;
let attendanceData = [];
let filteredData = [];

const tableBody = document.getElementById("attendance-table-body");
const filterDateInput = document.getElementById("filter-date");
const filterBtn = document.getElementById("filter-btn");
const clearFilterBtn = document.getElementById("clear-filter-btn");
const prevPageBtn = document.getElementById("prev-page");
const nextPageBtn = document.getElementById("next-page");
const pageInfo = document.getElementById("page-info");
const rowsPerPageSelect = document.getElementById("rows-per-page");
const importFileInput = document.getElementById("import-attendance-file");

// Modal elements
const modalOverlay = document.getElementById("edit-modal-overlay");
const editForm = document.getElementById("edit-attendance-form");
const editTimeIn = document.getElementById("edit-time-in");
const editTimeOut = document.getElementById("edit-time-out");
const editStatus = document.getElementById("edit-status");
const editCancelBtn = document.getElementById("edit-cancel-btn");

let currentEditIndex = null;

// Function to convert 12-hour time (e.g., "01:59 PM") to 24-hour (e.g., "13:59")
function convertTo24Hour(time12h) {
  if (!time12h || time12h === "-") return "";
  const [time, period] = time12h.split(" ");
  let [hours, minutes] = time.split(":");
  hours = parseInt(hours, 10);
  if (period === "PM" && hours !== 12) hours += 12;
  if (period === "AM" && hours === 12) hours = 0;
  return `${hours.toString().padStart(2, "0")}:${minutes}`;
}

// Fetch data from the server
async function fetchAttendanceData() {
  try {
    const response = await fetch("../views/attendance_logs_handler.php");
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    attendanceData = await response.json();
    filteredData = attendanceData;
    renderTablePage(currentPage);
  } catch (error) {
    console.error("Error fetching attendance data:", error);
    tableBody.innerHTML =
      '<tr><td colspan="7" class="text-center py-4 text-red-500">Error loading data</td></tr>';
  }
}

function renderTablePage(page) {
  tableBody.innerHTML = "";
  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const pageData = filteredData.slice(start, end);

  if (pageData.length === 0 && page > 1) {
    currentPage--;
    renderTablePage(currentPage);
    updatePaginationButtons();
    return;
  }

  if (pageData.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="7" class="text-center py-4 text-gray-500">No records found</td></tr>';
    pageInfo.textContent = `Page 0`;
    updatePaginationButtons();
    return;
  }

  pageData.forEach((log, index) => {
    const tr = document.createElement("tr");
    tr.className = "hover:bg-gray-50";

    // Avatar path handling
    // The DB returns 'uploads/avatars/...'
    // We are in 'pages/', so we need '../uploads/avatars/...'
    // If null, use default 'img/user.jpg'
    let avatarPath = "img/user.jpg";
    if (log.avatar_path) {
      avatarPath = `../${log.avatar_path}`;
    }

    tr.innerHTML = `
          <td class="px-6 py-3 whitespace-nowrap">
            <img src="${avatarPath}" alt="Avatar" class="h-10 w-10 rounded-full object-cover" style="width: 60px; height: 60px;">
          </td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
            log.name
          }</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
            log.date
          }</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
            log.timeIn
          }</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
            log.timeOut
          }</td>
          <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${
            log.status === "Absent"
              ? "text-red-600"
              : log.status === "Late"
              ? "text-yellow-600"
              : "text-green-600"
          }">${log.status}</td>
          <td class="px-6 py-3 whitespace-nowrap text-center text-sm actions-cell">
            <div class="flex justify-center items-center h-full space-x-2" style="transform: translateY(18px);">
              <img src="icons/update.png" alt="Edit" class="edit-icon" title="Edit Attendance" data-index="${
                start + index
              }" style="cursor: pointer; width: 20px; height: 20px;" />
              <img src="icons/delete.png" alt="Delete" class="delete-icon" title="Delete Attendance" data-index="${
                start + index
              }" style="cursor: pointer; width: 20px; height: 20px;" />
            </div>
          </td>
        `;
    tableBody.appendChild(tr);
  });

  pageInfo.textContent = `Page ${currentPage}`;
  updatePaginationButtons();

  // Attach event listeners for edit icon
  document.querySelectorAll(".edit-icon").forEach((icon) => {
    icon.addEventListener("click", onEditClick);
  });

  // Attach event listeners for delete icon
  document.querySelectorAll(".delete-icon").forEach((icon) => {
    icon.addEventListener("click", onDeleteClick);
  });
}

function updatePaginationButtons() {
  prevPageBtn.disabled = currentPage === 1 || filteredData.length === 0;
  nextPageBtn.disabled = currentPage * rowsPerPage >= filteredData.length;
}

filterBtn.addEventListener("click", () => {
  const selectedDate = filterDateInput.value;
  if (selectedDate) {
    filteredData = attendanceData.filter((log) => log.date === selectedDate);
  } else {
    filteredData = attendanceData;
  }
  currentPage = 1;
  renderTablePage(currentPage);
});

clearFilterBtn.addEventListener("click", () => {
  filterDateInput.value = "";
  filteredData = attendanceData;
  currentPage = 1;
  renderTablePage(currentPage);
});

prevPageBtn.addEventListener("click", () => {
  if (currentPage > 1) {
    currentPage--;
    renderTablePage(currentPage);
  }
});

nextPageBtn.addEventListener("click", () => {
  if (currentPage * rowsPerPage < filteredData.length) {
    currentPage++;
    renderTablePage(currentPage);
  }
});

rowsPerPageSelect.addEventListener("change", (e) => {
  rowsPerPage = parseInt(e.target.value, 10);
  currentPage = 1;
  renderTablePage(currentPage);
});

importFileInput.addEventListener("change", (event) => {
  const file = event.target.files[0];
  if (!file) return;
  alert(
    `Selected file: ${file.name}\n\nImplement Excel parsing and data import here.`
  );
  importFileInput.value = "";
});

function onEditClick(event) {
  const index = parseInt(event.target.getAttribute("data-index"), 10);
  currentEditIndex = index;
  const record = filteredData[index];

  // Convert 12-hour times to 24-hour for the time inputs
  editTimeIn.value = convertTo24Hour(record.timeIn);
  editTimeOut.value = convertTo24Hour(record.timeOut);

  // Time-out is now always enabled and editable
  modalOverlay.classList.add("active");
  editTimeIn.focus();
}

editForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (currentEditIndex === null) return;

  const timeInVal = editTimeIn.value.trim();
  const timeOutVal = editTimeOut.value.trim();

  // Validation: Time-out cannot be entered without time-in
  if (timeOutVal && !timeInVal) {
    showMessage("Time-out cannot be entered without time-in.", "error");
    return;
  }

  // Validation: Time-in cannot be later than or equal to time-out
  if (timeInVal && timeOutVal) {
    const timeInDate = new Date(`1970-01-01T${timeInVal}:00`);
    const timeOutDate = new Date(`1970-01-01T${timeOutVal}:00`);
    if (timeInDate >= timeOutDate) {
      showMessage(
        "Time-in cannot be later than or equal to time-out.",
        "error"
      );
      return;
    }
  }

  const record = filteredData[currentEditIndex];

  try {
    const response = await fetch("../views/attendance_logs_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "update",
        id: record.id,
        timeIn: timeInVal,
        timeOut: timeOutVal,
      }),
    });

    const result = await response.json();
    if (result.success) {
      // Refresh data to get calculated status
      fetchAttendanceData();
      modalOverlay.classList.remove("active");
      currentEditIndex = null;
    } else {
      showMessage(result.message, "error");
    }
  } catch (error) {
    console.error("Error updating record:", error);
    showMessage("An error occurred while updating.", "error");
  }
});

async function onDeleteClick(event) {
  const index = parseInt(event.target.getAttribute("data-index"), 10);
  const record = filteredData[index];

  if (
    confirm(
      `Are you sure you want to delete the attendance record for ${record.name}?`
    )
  ) {
    try {
      const response = await fetch("../views/attendance_logs_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "delete",
          id: record.id,
        }),
      });

      const result = await response.json();
      if (result.success) {
        // Remove from local data
        attendanceData = attendanceData.filter((item) => item.id !== record.id);
        filteredData = filteredData.filter((item) => item.id !== record.id);
        renderTablePage(currentPage);
      } else {
        alert("Failed to delete: " + result.message);
      }
    } catch (error) {
      console.error("Error deleting record:", error);
      alert("An error occurred while deleting.");
    }
  }
}

editCancelBtn.addEventListener("click", () => {
  modalOverlay.classList.remove("active");
  currentEditIndex = null;
});

modalOverlay.addEventListener("click", (e) => {
  if (e.target === modalOverlay) {
    modalOverlay.classList.remove("active");
    currentEditIndex = null;
  }
});

editForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (currentEditIndex === null) return;

  const timeInVal = editTimeIn.value.trim();
  const timeOutVal = editTimeOut.value.trim();

  // Validation: Time-out cannot be entered without time-in
  if (timeOutVal && !timeInVal) {
    showMessage("Time-out cannot be entered without time-in.", "error");
    return;
  }

  // Validation: Time-in cannot be later than or equal to time-out
  if (timeInVal && timeOutVal) {
    const timeInDate = new Date(`1970-01-01T${timeInVal}:00`);
    const timeOutDate = new Date(`1970-01-01T${timeOutVal}:00`);
    if (timeInDate >= timeOutDate) {
      showMessage(
        "Time-in cannot be later than or equal to time-out.",
        "error"
      );
      return;
    }
  }

  const record = filteredData[currentEditIndex];

  try {
    const response = await fetch("../views/attendance_logs_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "update",
        id: record.id,
        timeIn: timeInVal,
        timeOut: timeOutVal,
      }),
    });

    const result = await response.json();
    if (result.success) {
      // Refresh data to get calculated status
      fetchAttendanceData();
      modalOverlay.classList.remove("active");
      currentEditIndex = null;
    } else {
      showMessage(result.message, "error");
    }
  } catch (error) {
    console.error("Error updating record:", error);
    showMessage("An error occurred while updating.", "error");
  }
});

// Initial fetch
fetchAttendanceData();
