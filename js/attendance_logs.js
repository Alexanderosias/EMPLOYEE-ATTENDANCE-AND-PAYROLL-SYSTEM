let rowsPerPage = 5; // default rows per page
let currentPage = 1;
let attendanceData = [];
let filteredData = [];

const tableBody = document.getElementById("attendance-table-body");
const filterStartInput = document.getElementById("filter-start");
const filterEndInput = document.getElementById("filter-end");
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

// Format a time or datetime string into 12-hour format with AM/PM for display
function formatTimeTo12Hour(value) {
  if (!value) return "";
  let timePart = String(value).trim();

  // If value includes a date or other prefix, take the time portion
  if (timePart.includes(" ")) {
    const parts = timePart.split(" ");
    timePart = parts[parts.length - 1];
  } else if (timePart.includes("T")) {
    const parts = timePart.split("T");
    timePart = parts[parts.length - 1];
  }

  const match = /^(\d{1,2}):(\d{2})(?::\d{2})?$/.exec(timePart);
  if (!match) return String(value);

  let hours = parseInt(match[1], 10);
  const minutes = match[2];
  if (Number.isNaN(hours)) return String(value);

  const ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12;
  if (hours === 0) hours = 12;

  const hoursStr = hours.toString().padStart(2, "0");
  return `${hoursStr}:${minutes} ${ampm}`;
}

// Fetch data from the server
async function fetchAttendanceData() {
  try {
    const response = await fetch("../views/attendance_logs_handler.php");
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    attendanceData = await response.json();
    applyDefaultTodayFilter();
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

    const baseStatus = (log.base_status || log.status || "").trim();
    const displayStatus = (log.display_status || log.status || "").trim();

    const statusClass =
      baseStatus === "Absent"
        ? "text-red-600"
        : baseStatus === "Late" || baseStatus === "Undertime"
        ? "text-yellow-600"
        : baseStatus === "On Leave"
        ? "text-purple-600"
        : baseStatus === "Holiday"
        ? "text-blue-600"
        : "text-green-600";

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
          <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${statusClass}">${displayStatus}</td>
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

function getTodayYmd() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, "0");
  const d = String(now.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

function applyDefaultTodayFilter() {
  if (!Array.isArray(attendanceData)) {
    attendanceData = [];
  }
  const todayStr = getTodayYmd();
  filteredData = attendanceData.filter((log) => log.date === todayStr);
  currentPage = 1;
  renderTablePage(currentPage);
}

filterBtn.addEventListener("click", () => {
  const start = filterStartInput ? filterStartInput.value : "";
  const end = filterEndInput ? filterEndInput.value : "";

  if (!start && !end) {
    applyDefaultTodayFilter();
    return;
  }

  const data = Array.isArray(attendanceData) ? attendanceData : [];
  filteredData = data.filter((log) => {
    const date = log.date || "";
    if (!date) return false;
    if (start && date < start) return false;
    if (end && date > end) return false;
    return true;
  });
  currentPage = 1;
  renderTablePage(currentPage);
});

clearFilterBtn.addEventListener("click", () => {
  if (filterStartInput) filterStartInput.value = "";
  if (filterEndInput) filterEndInput.value = "";
  applyDefaultTodayFilter();
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
      await fetchOvertimeRequests();
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
      // Refresh data to get calculated status and overtime list
      fetchAttendanceData();
      fetchOvertimeRequests();
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

// ----- Overtime Requests (Head Admin) -----

const otTableBody = document.getElementById("ot-requests-body");
const otStatusMessage = document.getElementById("ot-status-message");
const otStatusFilter = document.getElementById("ot-status-filter");
const otSummary = document.getElementById("ot-summary");

function showOtMessage(msg, type = "info") {
  if (!otStatusMessage) return;
  otStatusMessage.textContent = msg;
  otStatusMessage.className = `text-sm mt-2 ${
    type === "error" ? "text-red-600" : type === "success" ? "text-green-600" : "text-gray-600"
  }`;
}

async function fetchOvertimeRequests() {
  if (!otTableBody) return; // Not a head_admin or section not present
  otTableBody.innerHTML = "";
  try {
    let url = "../views/overtime_requests_handler.php?action=list";
    if (otStatusFilter && otStatusFilter.value) {
      const v = encodeURIComponent(otStatusFilter.value);
      url += `&status=${v}`;
    }
    const res = await fetch(url);
    const text = await res.text();
    let out;
    try {
      out = JSON.parse(text);
    } catch (e) {
      showOtMessage("Failed to parse overtime data.", "error");
      return;
    }
    if (!out.success) {
      showOtMessage(out.message || "Failed to load overtime requests.", "error");
      return;
    }
    const list = Array.isArray(out.data) ? out.data : [];
    renderOvertimeRequests(list);
  } catch (e) {
    showOtMessage(e.message || "Error loading overtime requests.", "error");
  }
}

function renderOvertimeRequests(list) {
  if (!otTableBody) return;
  otTableBody.innerHTML = "";

  if (!list.length) {
    otTableBody.innerHTML =
      '<tr><td colspan="9" class="px-4 py-3 text-center text-sm text-gray-500">No overtime requests found.</td></tr>';
    showOtMessage("", "info");
    if (otSummary) {
      otSummary.textContent = "";
    }
    return;
  }

  const statusOrder = {
    Pending: 0,
    AutoApproved: 1,
    Approved: 2,
    Rejected: 3,
  };

  const sorted = list.slice().sort((a, b) => {
    const sa = statusOrder[a.status] !== undefined ? statusOrder[a.status] : 99;
    const sb = statusOrder[b.status] !== undefined ? statusOrder[b.status] : 99;
    if (sa !== sb) return sa - sb;

    const da = a.date || "";
    const db = b.date || "";
    if (da > db) return -1;
    if (da < db) return 1;

    const ta = a.actual_out_time || "";
    const tb = b.actual_out_time || "";
    if (ta > tb) return -1;
    if (ta < tb) return 1;

    const ia = typeof a.id === "number" ? a.id : parseInt(a.id || 0, 10) || 0;
    const ib = typeof b.id === "number" ? b.id : parseInt(b.id || 0, 10) || 0;
    return ib - ia;
  });

  sorted.forEach((r) => {
    const tr = document.createElement("tr");
    tr.className = "hover:bg-gray-50";

    const raw = typeof r.raw_ot_minutes === "number" ? r.raw_ot_minutes : parseInt(r.raw_ot_minutes || 0, 10) || 0;
    const approved = typeof r.approved_ot_minutes === "number" ? r.approved_ot_minutes : parseInt(r.approved_ot_minutes || 0, 10) || 0;
    const status = r.status || "Pending";

    tr.innerHTML = `
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${
        r.employee_name || ""
      }</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${r.date || ""}</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${formatTimeTo12Hour(
        r.scheduled_end_time
      )}</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${formatTimeTo12Hour(
        r.actual_out_time
      )}</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">${raw}</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
        <input type="number" class="ot-approved-input border border-gray-300 rounded px-2 py-1 text-sm w-24 text-right" min="0" max="${raw}" value="${approved}" />
      </td>
      <td class="px-4 py-3 whitespace-nowrap text-sm ${
        status === "Pending"
          ? "text-yellow-600"
          : status === "Rejected"
          ? "text-red-600"
          : "text-green-600"
      }">${status}</td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
        <input type="text" class="ot-remarks-input border border-gray-300 rounded px-2 py-1 text-sm w-full" value="${
          r.remarks ? String(r.remarks).replace(/"/g, "&quot;") : ""
        }" />
      </td>
      <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
        <button type="button" class="ot-approve-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs mr-1">Approve</button>
        <button type="button" class="ot-reject-btn bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">Reject</button>
      </td>
    `;

    const approvedInput = tr.querySelector(".ot-approved-input");
    const remarksInput = tr.querySelector(".ot-remarks-input");
    const approveBtn = tr.querySelector(".ot-approve-btn");
    const rejectBtn = tr.querySelector(".ot-reject-btn");

    const id = r.id;

    if (approveBtn) {
      approveBtn.addEventListener("click", () => {
        const val = parseInt(approvedInput.value || "0", 10) || 0;
        const mins = val < 0 ? 0 : val > raw ? raw : val;
        const remarks = remarksInput.value || "";
        updateOvertimeRequest(id, "Approved", mins, remarks);
      });
    }

    if (rejectBtn) {
      rejectBtn.addEventListener("click", () => {
        if (!confirm("Reject this overtime request? Approved minutes will be set to 0.")) return;
        const remarks = remarksInput.value || "";
        updateOvertimeRequest(id, "Rejected", 0, remarks);
      });
    }

    otTableBody.appendChild(tr);
  });

  updateOtSummary(list);
}

function updateOtSummary(list) {
  if (!otSummary) return;
  if (!list || !list.length) {
    otSummary.textContent = "";
    return;
  }

  let totalRaw = 0;
  let totalApproved = 0;
  let pending = 0;
  let approved = 0;
  let rejected = 0;
  let autoApproved = 0;

  list.forEach((r) => {
    const raw =
      typeof r.raw_ot_minutes === "number"
        ? r.raw_ot_minutes
        : parseInt(r.raw_ot_minutes || 0, 10) || 0;
    const appr =
      typeof r.approved_ot_minutes === "number"
        ? r.approved_ot_minutes
        : parseInt(r.approved_ot_minutes || 0, 10) || 0;

    totalRaw += raw;
    totalApproved += appr;

    const s = r.status || "Pending";
    if (s === "Pending") pending += 1;
    else if (s === "Approved") approved += 1;
    else if (s === "Rejected") rejected += 1;
    else if (s === "AutoApproved") autoApproved += 1;
  });

  const parts = [];
  parts.push(`Total: ${list.length}`);
  if (pending) parts.push(`Pending: ${pending}`);
  if (approved) parts.push(`Approved: ${approved}`);
  if (rejected) parts.push(`Rejected: ${rejected}`);
  if (autoApproved) parts.push(`Auto-approved: ${autoApproved}`);
  parts.push(`Raw mins: ${totalRaw}`);
  parts.push(`Approved mins: ${totalApproved}`);

  otSummary.textContent = parts.join(" | ");
}

async function updateOvertimeRequest(id, status, approvedMinutes, remarks) {
  try {
    const res = await fetch("../views/overtime_requests_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "update",
        id,
        status,
        approved_minutes: approvedMinutes,
        remarks,
      }),
    });
    const out = await res.json();
    if (!out.success) {
      showOtMessage(out.message || "Failed to update overtime request.", "error");
      return;
    }
    showOtMessage(out.message || "Overtime request updated.", "success");
    fetchOvertimeRequests();
  } catch (e) {
    showOtMessage(e.message || "Error updating overtime request.", "error");
  }
}

// Initial fetch for overtime requests (only if section exists)
if (otStatusFilter) {
  otStatusFilter.addEventListener("change", () => {
    fetchOvertimeRequests();
  });
}

fetchOvertimeRequests();
