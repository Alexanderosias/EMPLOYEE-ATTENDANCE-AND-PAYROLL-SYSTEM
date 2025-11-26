document.addEventListener("DOMContentLoaded", () => {
  const BASE_PATH = ""; // Change to "views/" if getting 404 (depending on your directory structure)
  const API_BASE = BASE_PATH + "/views/employee_handler.php";

  // Declare ALL DOM elements at the top to avoid initialization errors
  const modal = document.getElementById("leave-modal");
  const requestBtn = document.getElementById("request-leave-btn");
  const cancelBtn = document.getElementById("cancel-leave-btn");
  const closeModalX = document.getElementById("close-modal-x");
  const submitBtn = document.getElementById("submit-leave-btn");
  const errorMsg = document.getElementById("error-message");
  const leaveTypeSelect = document.getElementById("leave-type");
  const startDateInput = document.getElementById("leave-start");
  const endDateInput = document.getElementById("leave-end");
  const totalDaysInput = document.getElementById("total-days");
  const availableBalanceSpan = document.getElementById("available-balance");
  const balanceDisplay = document.getElementById("balance-display");
  const searchInput = document.getElementById("search-input");
  const statusFilter = document.getElementById("status-filter");

  // View modal elements
  const viewModal = document.getElementById("view-leave-modal");
  const viewCloseBtn = document.getElementById("view-close-modal-btn");
  const viewCloseX = document.getElementById("view-close-modal-x");
  const cancelRequestBtn = document.getElementById("cancel-request-btn");

  // Load data
  loadLeaveBalances();
  loadLeaveRequests();

  // Function definitions (after declarations)
  function openModal() {
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    closeModalX.focus();
    resetForm();
  }

  function closeModal() {
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
    resetForm();
  }

  function resetForm() {
    document.getElementById("leave-form").reset();
    balanceDisplay.classList.add("hidden");
    totalDaysInput.value = "";
    errorMsg.classList.add("hidden");
    submitBtn.disabled = false;
  }

  function openViewModal() {
    viewModal.classList.remove("hidden");
    viewModal.setAttribute("aria-hidden", "false");
  }

  function closeViewModal() {
    viewModal.classList.add("hidden");
    viewModal.setAttribute("aria-hidden", "true");
  }

  async function viewLeaveRequest(requestId) {
    try {
      const response = await fetch(
        `${API_BASE}?action=view_leave_request&id=${requestId}`
      );
      const result = await response.json();
      if (result.success) {
        const req = result.data;
        document.getElementById("view-modal-name").textContent =
          req.first_name + " " + req.last_name;
        document.getElementById("view-modal-email").textContent = req.email;
        let avatarPath = req.avatar_path || "img/user.jpg";
        // Fix path if it's from uploads (assuming uploads is in root)
        if (avatarPath.startsWith("uploads/")) {
          avatarPath = "../" + avatarPath;
        }
        // Add cache buster
        document.getElementById("view-modal-avatar").src =
          avatarPath + "?t=" + new Date().getTime();
        document.getElementById("view-modal-type").textContent = req.leave_type;
        document.getElementById(
          "view-modal-dates"
        ).textContent = `${req.start_date} - ${req.end_date} (${req.days} Days)`;
        document.getElementById("view-modal-reason").textContent = req.reason;
        document.getElementById("view-modal-status").textContent = req.status;
        document.getElementById(
          "view-modal-status"
        ).className = `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(
          req.status
        )}`;
        // Show cancel button only if Pending
        cancelRequestBtn.style.display =
          req.status === "Pending" ? "block" : "none";
        cancelRequestBtn.setAttribute("data-id", requestId);
        openViewModal();
      } else {
        alert("Error: " + result.message);
      }
    } catch (error) {
      alert("Error: " + error.message);
    }
  }

  function getTypeClass(type) {
    switch (type) {
      case "Paid":
        return "bg-green-100 text-green-800";
      case "Unpaid":
        return "bg-blue-100 text-blue-800";
      case "Sick":
        return "bg-red-100 text-red-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  }

  function getStatusClass(status) {
    switch (status) {
      case "Approved":
        return "bg-green-100 text-green-800";
      case "Rejected":
        return "bg-red-100 text-red-800";
      default:
        return "bg-yellow-100 text-yellow-800";
    }
  }

  async function loadLeaveBalances() {
    try {
      const response = await fetch(`${API_BASE}?action=leave_balances`);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        document.getElementById("paid-leave-balance").textContent =
          result.data.paid;
        document.getElementById("unpaid-leave-balance").textContent =
          result.data.unpaid;
        document.getElementById("sick-leave-balance").textContent =
          result.data.sick;
      } else {
        console.error("API Error:", result.message);
      }
    } catch (error) {
      console.error("Fetch Error:", error);
    }
  }

  async function loadLeaveRequests() {
    const response = await fetch(`${API_BASE}?action=leave_requests`);
    const result = await response.json();
    if (result.success) {
      const tbody = document.getElementById("leave-requests-table");
      tbody.innerHTML = result.data
        .map(
          (req) => `
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getTypeClass(
                req.type
              )}">${req.type}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
              req.start_date
            }</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
              req.end_date
            }</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
              req.days
            }</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate" title="${
              req.reason
            }">${req.reason}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(
                req.status
              )}">${req.status}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <button class="view-request-btn text-blue-600 hover:text-blue-800 mr-3" data-id="${
                req.id
              }" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              ${
                req.status === "Pending"
                  ? `<button class="cancel-request-btn-table text-red-600 hover:text-red-800" data-id="${req.id}" title="Cancel Request">
                      <i class="fas fa-times"></i>
                    </button>`
                  : ""
              }
            </td>
          </tr>
        `
        )
        .join("");

      // Attach event listeners to view buttons
      document.querySelectorAll(".view-request-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          const requestId = e.currentTarget.getAttribute("data-id");
          viewLeaveRequest(requestId);
        });
      });

      // Attach event listeners to cancel buttons in table
      document.querySelectorAll(".cancel-request-btn-table").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
          const requestId = e.currentTarget.getAttribute("data-id");
          if (confirm("Are you sure you want to cancel this request?")) {
            try {
              const formData = new FormData();
              formData.append("action", "cancel_leave_request");
              formData.append("id", requestId);
              const response = await fetch(API_BASE, {
                method: "POST",
                body: formData,
              });
              const result = await response.json();
              if (result.success) {
                alert("Request canceled successfully");
                loadLeaveRequests(); // Refresh table
                loadLeaveBalances(); // Refresh balances
              } else {
                alert("Error: " + result.message);
              }
            } catch (error) {
              alert("Error: " + error.message);
            }
          }
        });
      });
    }
  }

  // Event listeners (after functions are defined)
  requestBtn.addEventListener("click", openModal);
  cancelBtn.addEventListener("click", closeModal);
  if (closeModalX) closeModalX.addEventListener("click", closeModal);

  // Close on click outside (main modal)
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      closeModal();
    }
  });

  // Close on Escape (main modal)
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !modal.classList.contains("hidden")) {
      closeModal();
    }
  });

  // View modal event listeners
  viewCloseBtn.addEventListener("click", closeViewModal);
  if (viewCloseX) viewCloseX.addEventListener("click", closeViewModal);

  // Close on click outside (view modal)
  viewModal.addEventListener("click", (e) => {
    if (e.target === viewModal) {
      closeViewModal();
    }
  });

  // Close on Escape (view modal)
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !viewModal.classList.contains("hidden")) {
      closeViewModal();
    }
  });

  cancelRequestBtn.addEventListener("click", async () => {
    const requestId = cancelRequestBtn.getAttribute("data-id");
    if (confirm("Are you sure you want to cancel this request?")) {
      try {
        const formData = new FormData();
        formData.append("action", "cancel_leave_request");
        formData.append("id", requestId);
        const response = await fetch(API_BASE, {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.success) {
          alert("Request canceled successfully");
          closeViewModal();
          loadLeaveRequests(); // Refresh table
          loadLeaveBalances(); // Refresh balances
        } else {
          alert("Error: " + result.message);
        }
      } catch (error) {
        alert("Error: " + error.message);
      }
    }
  });

  // Search and filter
  searchInput.addEventListener("input", filterRequests);
  statusFilter.addEventListener("change", filterRequests);

  function filterRequests() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value;
    const rows = document.querySelectorAll("#leave-requests-table tr");

    rows.forEach((row) => {
      const type = row.cells[0].textContent.toLowerCase();
      const reason = row.cells[4].textContent.toLowerCase();
      const status = row.cells[5].textContent.trim();

      const matchesSearch =
        type.includes(searchTerm) || reason.includes(searchTerm);
      const matchesStatus = statusValue === "all" || status === statusValue;

      row.style.display = matchesSearch && matchesStatus ? "" : "none";
    });
  }

  // Dynamic modal logic
  leaveTypeSelect.addEventListener("change", async () => {
    const selectedType = leaveTypeSelect.value;
    if (!selectedType) {
      balanceDisplay.classList.add("hidden");
      return;
    }
    try {
      const response = await fetch(`${API_BASE}?action=leave_balances`);
      const result = await response.json();
      if (result.success) {
        const balanceKey = selectedType.toLowerCase(); // 'paid', 'unpaid', 'sick'
        const available = result.data[balanceKey];
        availableBalanceSpan.textContent = available;
        balanceDisplay.classList.remove("hidden");
        validateForm(); // Re-validate after balance load
      } else {
        console.error("Error loading balances:", result.message);
      }
    } catch (error) {
      console.error("Fetch error:", error);
    }
  });

  // Calculate total days on date change
  startDateInput.addEventListener("change", calculateDays);
  endDateInput.addEventListener("change", calculateDays);

  function calculateDays() {
    const start = startDateInput.value;
    const end = endDateInput.value;
    if (start && end) {
      const startDate = new Date(start);
      const endDate = new Date(end);
      if (startDate <= endDate) {
        const days =
          Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // Inclusive
        totalDaysInput.value = days;
      } else {
        totalDaysInput.value = "";
      }
    } else {
      totalDaysInput.value = "";
    }
    validateForm();
  }

  // Validation function
  function validateForm() {
    const selectedType = leaveTypeSelect.value;
    const start = startDateInput.value;
    const end = endDateInput.value;
    const reason = document.getElementById("leave-reason").value.trim();
    const totalDays = parseInt(totalDaysInput.value) || 0;
    const available = parseInt(availableBalanceSpan.textContent) || 0;

    let error = "";
    if (!selectedType) error = "Please select a leave type.";
    else if (!start || !end) error = "Please select start and end dates.";
    else if (new Date(start) > new Date(end))
      error = "Start date must be before or equal to end date.";
    else if (totalDays <= 0) error = "Total days must be greater than 0.";
    else if (totalDays > available)
      error = `Requested days (${totalDays}) exceed your available ${selectedType} balance (${available}).`;
    else if (!reason) error = "Please provide a reason.";

    if (error) {
      errorMsg.textContent = error;
      errorMsg.classList.remove("hidden");
      submitBtn.disabled = true;
    } else {
      errorMsg.classList.add("hidden");
      submitBtn.disabled = false;
    }
  }

  // Attach validation to all inputs
  leaveTypeSelect.addEventListener("change", validateForm);
  startDateInput.addEventListener("change", validateForm);
  endDateInput.addEventListener("change", validateForm);
  document
    .getElementById("leave-reason")
    .addEventListener("input", validateForm);

  // Form submission
  document
    .getElementById("leave-form")
    .addEventListener("submit", async (e) => {
      e.preventDefault();
      if (submitBtn.disabled) return; // Prevent if invalid

      const formData = new FormData(e.target);
      formData.append("action", "request_leave");

      try {
        const response = await fetch(API_BASE, {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.success) {
          alert("Leave requested successfully");
          closeModal();
          loadLeaveRequests(); // Refresh table
          loadLeaveBalances(); // Refresh balances
        } else {
          errorMsg.textContent = result.message;
          errorMsg.classList.remove("hidden");
        }
      } catch (error) {
        errorMsg.textContent = "Network error: " + error.message;
        errorMsg.classList.remove("hidden");
      }
    });
});
