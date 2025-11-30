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

  // Confirmation modal elements
  const confirmationModal = document.getElementById("confirmation-modal");
  const confirmationMessage = document.getElementById("confirmation-message");
  const confirmationConfirmBtn = document.getElementById(
    "confirmation-confirm-btn"
  );
  const confirmationCancelBtn = document.getElementById(
    "confirmation-cancel-btn"
  );
  const confirmationCloseX = document.getElementById("confirmation-close-x");

  // Flexible confirmation function
  function showConfirmation(
    message,
    confirmText = "Confirm",
    confirmColor = "blue"
  ) {
    return new Promise((resolve) => {
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

  // Status message function
  function showStatus(message, type = "success") {
    const statusDiv = document.getElementById("status-message");
    statusDiv.textContent = message;
    statusDiv.className = `status-message ${type}`;
    statusDiv.classList.add("show");

    // Auto-hide after 3 seconds
    setTimeout(() => {
      statusDiv.classList.remove("show");
    }, 3000);
  }

  // Helper function for image check
  function isImage(filename) {
    const ext = filename.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "gif"].includes(ext);
  }

  // Load data
  loadLeaveBalances();
  loadLeaveRequests();

  // Function definitions (after declarations)
  function openModal() {
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    closeModalX.focus();
    resetForm();
    // Set min date to today for start and end dates
    const today = new Date().toISOString().split("T")[0];
    startDateInput.setAttribute("min", today);
    endDateInput.setAttribute("min", today);
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

  async function checkOverlap(startDate, endDate) {
    try {
      const response = await fetch(
        `${API_BASE}?action=check_overlap&start=${startDate}&end=${endDate}`
      );
      const result = await response.json();
      if (result.success) {
        return result.overlap
          ? "The selected dates overlap with an existing leave request."
          : null;
      } else {
        console.error("Overlap check error:", result.message);
        return null; // Don't block if check fails
      }
    } catch (error) {
      console.error("Overlap check fetch error:", error);
      return null;
    }
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

        if (avatarPath.startsWith("uploads/")) {
          avatarPath = "../" + avatarPath;
        }
        if (req.proof_path) {
          const proofImg = document.getElementById("view-modal-proof-img");
          const proofLink = document.getElementById("view-modal-proof-link");
          const proofNone = document.getElementById("view-modal-proof-none");
          proofNone.classList.add("hidden");
          if (isImage(req.proof_path)) {
            proofImg.src = "../" + req.proof_path; // Adjust path
            proofImg.classList.remove("hidden");
            proofImg.style.cursor = "pointer";
            proofImg.onclick = function () {
              const lightbox = document.getElementById("image-lightbox");
              const lightboxImg = document.getElementById("lightbox-image");
              lightboxImg.src = this.src;
              lightbox.classList.remove("hidden");
            };
            proofLink.classList.add("hidden");
          } else {
            proofLink.href = "../" + req.proof_path;
            proofLink.textContent = `Download ${req.proof_path
              .split("/")
              .pop()}`;
            proofLink.classList.remove("hidden");
            proofImg.classList.add("hidden");
          }
        } else {
          document
            .getElementById("view-modal-proof-none")
            .classList.remove("hidden");
          document
            .getElementById("view-modal-proof-img")
            .classList.add("hidden");
          document
            .getElementById("view-modal-proof-link")
            .classList.add("hidden");
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
        showStatus(result.message, "error");
      }
    } catch (error) {
      showStatus("Error: " + error.message, "error");
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

      // Check if there are no requests
      if (!result.data || result.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
              <div class="flex flex-col items-center justify-center">
                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg font-medium">No leave requests to show</p>
                <p class="text-sm">You haven't submitted any leave requests yet.</p>
              </div>
            </td>
          </tr>
        `;
        return; // Exit early since there's nothing to process
      }

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
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              ${
                req.proof_path
                  ? isImage(req.proof_path)
                    ? '<i class="fas fa-image text-blue-600" title="Image proof"></i>'
                    : '<i class="fas fa-file text-gray-600" title="File proof"></i>'
                  : '<span class="text-gray-400">N/A</span>'
              }
            </td>
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
          const confirmed = await showConfirmation(
            "Are you sure you want to cancel this leave request?",
            "Cancel Request",
            "red"
          );

          if (confirmed) {
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
                showStatus("Request canceled successfully", "success");
                loadLeaveRequests(); // Refresh table
                loadLeaveBalances(); // Refresh balances
              } else {
                showStatus(result.message, "error");
              }
            } catch (error) {
              showStatus("Error: " + error.message, "error");
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
    const confirmed = await showConfirmation(
      "Are you sure you want to cancel this leave request? This action cannot be undone.",
      "Cancel Request",
      "red"
    );

    if (confirmed) {
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
          showStatus("Request canceled successfully", "success");
          closeViewModal();
          loadLeaveRequests(); // Refresh table
          loadLeaveBalances(); // Refresh balances
        } else {
          showStatus(result.message, "error");
        }
      } catch (error) {
        showStatus("Error: " + error.message, "error");
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
      const status = row.cells[6].textContent.trim(); // Updated to 6
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
  async function validateForm() {
    const selectedType = leaveTypeSelect.value;
    const start = startDateInput.value;
    const end = endDateInput.value;
    const reason = document.getElementById("leave-reason").value.trim();
    const totalDays = parseInt(totalDaysInput.value) || 0;
    const available = parseInt(availableBalanceSpan.textContent) || 0;

    let error = "";
    if (!selectedType) error = "Please select a leave type.";
    else if (!start || !end) error = "Please select start and end dates.";
    else {
      const today = new Date().toISOString().split("T")[0];
      if (start < today) error = "Start date cannot be in the past.";
      else if (end < today) error = "End date cannot be in the past.";
      else if (new Date(start) > new Date(end))
        error = "Start date must be before or equal to end date.";
      else {
        // Check for overlap
        const overlapError = await checkOverlap(start, end);
        if (overlapError) error = overlapError;
      }
    }
    if (totalDays <= 0) error = "Total days must be greater than 0.";
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

      const proofFile = document.getElementById("leave-proof").files[0];
      if (proofFile && proofFile.size > 10 * 1024 * 1024) {
        // 10MB
        showStatus("Proof file must be less than 10MB.", "error");
        return;
      }

      const formData = new FormData(e.target);
      formData.append("action", "request_leave");

      try {
        const response = await fetch(API_BASE, {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.success) {
          showStatus("Leave request submitted successfully", "success");
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
