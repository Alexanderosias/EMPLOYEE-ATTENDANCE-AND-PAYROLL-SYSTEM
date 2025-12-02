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
const snapshotModal = document.getElementById("snapshot-modal");
const snapshotModalCloseBtn = document.getElementById("modal-close-btn");
const snapshotModalEmployeeName = document.getElementById("modal-employee-name");
const snapshotModalContainer = document.getElementById("modal-snapshots-container");
const fullscreenOverlay = document.getElementById("fullscreen-overlay");
const fullscreenImage = fullscreenOverlay ? fullscreenOverlay.querySelector("img") : null;

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
      '<tr><td colspan="8" class="text-center py-4 text-red-500">Error loading data</td></tr>';
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
      '<tr><td colspan="8" class="text-center py-4 text-gray-500">No records found</td></tr>';
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
          <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
            ${
              log.hasSnapshot
                ? `<i class="fas fa-eye text-blue-600 cursor-pointer snapshot-icon" data-index="${start + index}"></i>`
                : "N/A"
            }
          </td>
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
  document.querySelectorAll(".snapshot-icon").forEach((icon) => {
    icon.addEventListener("click", (event) => {
      const index = parseInt(event.currentTarget.getAttribute("data-index"), 10);
      if (!Number.isNaN(index)) {
        openSnapshotModal(index);
      }
    });
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

importFileInput.addEventListener("change", async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const name = file.name.toLowerCase();
  if (!name.endsWith(".zip")) {
    if (typeof showMessage === "function") {
      showMessage("Please select a .zip package exported from the scanner.", "error");
    } else {
      alert("Please select a .zip package exported from the scanner.");
    }
    event.target.value = "";
    return;
  }

  const formData = new FormData();
  formData.append("action", "import_attendance");
  formData.append("file", file);

  try {
    const response = await fetch("../views/attendance_logs_handler.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (result.success) {
      if (typeof showMessage === "function") {
        showMessage(
          `Import completed. Imported ${result.imported || 0} logs.`,
          "success"
        );
      } else {
        alert(`Import completed. Imported ${result.imported || 0} logs.`);
      }
      await fetchAttendanceData();
    } else {
      const msg = result.message || "Import failed.";
      if (typeof showMessage === "function") {
        showMessage(msg, "error");
      } else {
        alert(msg);
      }
    }
  } catch (e) {
    if (typeof showMessage === "function") {
      showMessage("Import failed: " + e.message, "error");
    } else {
      alert("Import failed: " + e.message);
    }
  } finally {
    event.target.value = "";
  }
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
        // Auto refresh the page after deletion
        window.location.reload();
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

if (snapshotModal && snapshotModalCloseBtn) {
  snapshotModalCloseBtn.addEventListener("click", () => {
    snapshotModal.classList.remove("flex");
    snapshotModal.classList.add("hidden");
  });
  snapshotModal.addEventListener("click", (e) => {
    if (e.target === snapshotModal) {
      snapshotModal.classList.remove("flex");
      snapshotModal.classList.add("hidden");
    }
  });
}

if (fullscreenOverlay && fullscreenImage) {
  fullscreenOverlay.addEventListener("click", () => {
    fullscreenOverlay.style.display = "none";
    fullscreenImage.src = "";
  });
}

function openSnapshotModal(index) {
  const record = filteredData[index];
  if (!record || !snapshotModal || !snapshotModalContainer || !snapshotModalEmployeeName) {
    return;
  }

  snapshotModalEmployeeName.textContent = record.name || "";
  snapshotModalContainer.innerHTML = "";

  let snapshots = Array.isArray(record.snapshots) ? record.snapshots.slice() : [];
  if ((!snapshots || snapshots.length === 0) && record.snapshot_path) {
    snapshots = [{ image_path: record.snapshot_path, captured_at: null }];
  }

  if (!snapshots || snapshots.length === 0) {
    snapshotModalContainer.innerHTML = "<p>No snapshots available.</p>";
  } else {
    snapshots
      .filter((s) => s && s.image_path)
      .sort((a, b) => {
        const aTime = a.captured_at ? new Date(a.captured_at).getTime() : 0;
        const bTime = b.captured_at ? new Date(b.captured_at).getTime() : 0;
        return bTime - aTime;
      })
      .forEach((snap) => {
        const snapCard = document.createElement("div");
        snapCard.className = "modal-snapshot-card";

        const snapImg = document.createElement("img");
        snapImg.src = `../${snap.image_path}`;
        snapImg.alt = "Snapshot";
        snapImg.className = "modal-snapshot-img";
        snapImg.style.width = "150px";
        snapImg.style.height = "150px";
        snapImg.style.objectFit = "cover";
        snapImg.onerror = () => {
          snapCard.innerHTML = "<p>Snapshot not found</p>";
        };
        snapCard.appendChild(snapImg);

        const snapInfo = document.createElement("div");
        snapInfo.className = "modal-snapshot-info";
        snapInfo.innerHTML = snap.captured_at
          ? `Captured: ${new Date(snap.captured_at).toLocaleString()}`
          : "Captured: N/A";
        snapCard.appendChild(snapInfo);

        snapCard.addEventListener("click", () => {
          if (fullscreenOverlay && fullscreenImage) {
            fullscreenImage.src = `../${snap.image_path}`;
            fullscreenOverlay.style.display = "flex";
          }
        });

        snapshotModalContainer.appendChild(snapCard);
      });
  }

  snapshotModal.classList.remove("hidden");
  snapshotModal.classList.add("flex");
}

// Initial fetch
fetchAttendanceData();
