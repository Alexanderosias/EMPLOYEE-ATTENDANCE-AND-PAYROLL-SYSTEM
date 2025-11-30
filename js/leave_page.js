document.addEventListener("DOMContentLoaded", () => {
  const API_BASE = "../views/leave_handler.php"; // Path to handler

  // Elements
  const modal = document.getElementById("leave-modal");
  const closeModalX = document.getElementById("close-modal-x");
  const closeModalBtn = document.getElementById("close-modal-btn");
  const searchInput = document.getElementById("search-input");
  const statusFilter = document.getElementById("status-filter");
  const dateInputs = document.querySelectorAll('input[type="date"]');
  const tableBody = document.getElementById("leave-table-body");

  // Load initial data
  loadLeaveRequests();

  // Status message function
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

  // Helper function for image check
  function isImage(filename) {
    if (!filename) return false;
    const ext = filename.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext);
  }

  // Confirmation modal function
  function showConfirmation(
    message,
    confirmText = "Confirm",
    confirmColor = "blue"
  ) {
    return new Promise((resolve) => {
      const confirmationModal = document.getElementById("confirmation-modal");
      const confirmationMessage = document.getElementById(
        "confirmation-message"
      );
      const confirmationConfirmBtn = document.getElementById(
        "confirmation-confirm-btn"
      );
      const confirmationCancelBtn = document.getElementById(
        "confirmation-cancel-btn"
      );
      const confirmationCloseX = document.getElementById(
        "confirmation-close-x"
      );

      confirmationMessage.textContent = message;
      confirmationConfirmBtn.textContent = confirmText;
      confirmationConfirmBtn.className = `px-4 py-2 bg-${confirmColor}-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-${confirmColor}-600 focus:outline-none focus:ring-2 focus:ring-${confirmColor}-300`;

      confirmationModal.classList.remove("hidden");
      confirmationModal.setAttribute("aria-hidden", "false");

      const handleConfirm = () => {
        cleanup();
        resolve(true);
      };
      const handleCancel = () => {
        cleanup();
        resolve(false);
      };
      const cleanup = () => {
        confirmationModal.classList.add("hidden");
        confirmationModal.setAttribute("aria-hidden", "true");
        confirmationConfirmBtn.removeEventListener("click", handleConfirm);
        confirmationCancelBtn.removeEventListener("click", handleCancel);
        confirmationCloseX.removeEventListener("click", handleCancel);
      };

      confirmationConfirmBtn.addEventListener("click", handleConfirm);
      confirmationCancelBtn.addEventListener("click", handleCancel);
      confirmationCloseX.addEventListener("click", handleCancel);

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

  // Modal functions
  function openModal() {
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    closeModalX.focus();
  }

  function closeModal() {
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
  }

  if (closeModalX) closeModalX.addEventListener("click", closeModal);
  if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);

  modal.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !modal.classList.contains("hidden")) closeModal();
  });

  // Load and populate table
  async function loadLeaveRequests() {
    const search = searchInput.value;
    const status = statusFilter.value;
    const startDate = dateInputs[0].value;
    const endDate = dateInputs[1].value;

    const params = new URLSearchParams({
      action: "leave_requests",
      search,
      status,
      start_date: startDate,
      end_date: endDate,
    });

    try {
      const response = await fetch(`${API_BASE}?${params}`);
      const result = await response.json();
      if (result.success) {
        populateTable(result.data);
      } else {
        console.error("Error loading requests:", result.message);
      }
    } catch (error) {
      console.error("Fetch error:", error);
    }
  }

  function populateTable(requests) {
    if (requests.length === 0) {
      tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              <div style="padding: 2rem 0;" class="flex flex-col items-center justify-center">
                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg font-medium">Nothing to show</p>
                <p class="text-sm">There are no requests at the moment.</p>
              </div>
            </td>
          </tr>
      `;
      return;
    }

    tableBody.innerHTML = requests
      .map(
        (req) => `
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="flex items-center">
            <div id="img-container" class="flex-shrink-0">
              <img class="h-8 w-8 rounded-full object-cover" src="${
                req.avatar_path
              }" alt="Avatar">
            </div>
            <div id="name-container" class="ml-4">
              <div class="text-sm font-medium text-gray-900">${
                req.first_name
              } ${req.last_name}</div>
              <div class="text-sm text-gray-500">${req.email}</div>
            </div>
          </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getTypeClass(
            req.leave_type
          )}">${req.leave_type}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
          ${req.start_date} - ${req.end_date}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
          <span title="${req.reason}">
            ${
              req.reason.length > 15
                ? req.reason.substring(0, 15) + "..."
                : req.reason
            }
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
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
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
          <button class="text-blue-600 hover:text-blue-900 mr-5 btn-view-leave" data-id="${
            req.id
          }" title="View">
            <i class="fas fa-eye text-lg"></i>
          </button>
          ${
            req.status === "Pending"
              ? `
            <button class="text-green-600 hover:text-green-900 mr-5 btn-approve" data-id="${req.id}" title="Approve">
              <i class="fas fa-check text-lg"></i>
            </button>
            <button class="text-red-600 hover:text-red-900 btn-decline" data-id="${req.id}" title="Decline">
              <i class="fas fa-times text-lg"></i>
            </button>
          `
              : ""
          }
        </td>
      </tr>
    `
      )
      .join("");

    // Attach event listeners
    document.querySelectorAll(".btn-view-leave").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.currentTarget.getAttribute("data-id");
        viewLeaveRequest(id);
      });
    });

    document.querySelectorAll(".btn-approve").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.currentTarget.getAttribute("data-id");
        handleAction(id, "approve_leave", "Request approved");
      });
    });

    document.querySelectorAll(".btn-decline").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.currentTarget.getAttribute("data-id");
        handleAction(id, "decline_leave", "Request declined");
      });
    });

    // Modal approve/decline buttons
    document
      .getElementById("modal-approve-btn")
      .addEventListener("click", (e) => {
        const id = e.currentTarget.getAttribute("data-id");
        handleAction(id, "approve_leave", "Request approved");
        // Remove closeModal() here to allow overlay
      });

    document
      .getElementById("modal-decline-btn")
      .addEventListener("click", (e) => {
        const id = e.currentTarget.getAttribute("data-id");
        handleAction(id, "decline_leave", "Request declined");
        // Remove closeModal() here to allow overlay
      });
  }

  async function viewLeaveRequest(id) {
    try {
      const response = await fetch(
        `${API_BASE}?action=view_leave_request&id=${id}`
      );
      const result = await response.json();
      if (result.success) {
        const req = result.data;
        // Use getElementById for reliable access
        document.getElementById("modal-avatar").src = req.avatar_path;
        document.getElementById(
          "modal-name"
        ).textContent = `${req.first_name} ${req.last_name}`;
        document.getElementById("modal-email").textContent = req.email;
        document.getElementById("modal-type").textContent = req.leave_type;
        document.getElementById(
          "modal-dates"
        ).textContent = `${req.start_date} - ${req.end_date} (${req.days} Days)`;
        document.getElementById("modal-reason").textContent = req.reason;

        // Handle proof display
        if (req.proof_path) {
          const proofImg = document.getElementById("view-modal-proof-img");
          const proofLink = document.getElementById("view-modal-proof-link");
          const proofNone = document.getElementById("view-modal-proof-none");
          proofNone.classList.add("hidden");

          if (isImage(req.proof_path)) {
            proofImg.src = "../" + req.proof_path;
            proofImg.classList.remove("hidden");
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

        document.getElementById("modal-status").textContent = req.status;
        document.getElementById(
          "modal-status"
        ).className = `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(
          req.status
        )}`;
        // Hide approve/decline buttons if not Pending
        const approveBtn = document.getElementById("modal-approve-btn");
        const declineBtn = document.getElementById("modal-decline-btn");
        if (req.status !== "Pending") {
          approveBtn.style.display = "none";
          declineBtn.style.display = "none";
        } else {
          approveBtn.style.display = "block";
          declineBtn.style.display = "block";
        }

        openModal(); // Now called successfully
        document
          .getElementById("modal-approve-btn")
          .setAttribute("data-id", id);
        document
          .getElementById("modal-decline-btn")
          .setAttribute("data-id", id);
      } else {
        showStatus("Error: " + result.message, "error");
      }
    } catch (error) {
      console.error("Error in viewLeaveRequest:", error);
      showStatus("Error: " + error.message, "error");
    }
  }

  async function handleAction(id, action, successMsg) {
    const actionText = action === "approve_leave" ? "approve" : "decline";
    const confirmed = await showConfirmation(
      `Are you sure you want to ${actionText} this request?`,
      `Confirm ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
      action === "approve_leave" ? "green" : "red"
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append("action", action);
    formData.append("id", id);

    try {
      const response = await fetch(API_BASE, {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.success) {
        showStatus(successMsg, "success");
        loadLeaveRequests(); // Refresh table
        closeModal(); // Close leave modal after action
        // Confirmation modal is already closed by showConfirmation
      } else {
        showStatus("Error: " + result.message, "error");
      }
    } catch (error) {
      showStatus("Error: " + error.message, "error");
    }
  }

  // Filters
  searchInput.addEventListener("input", loadLeaveRequests);
  statusFilter.addEventListener("change", loadLeaveRequests);
  dateInputs.forEach((input) =>
    input.addEventListener("change", loadLeaveRequests)
  );

  // Helpers
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
});
