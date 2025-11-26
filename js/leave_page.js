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
        document.getElementById("modal-status").textContent = req.status;
        document.getElementById(
          "modal-status"
        ).className = `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(
          req.status
        )}`;
        openModal(); // Now called successfully
      } else {
        alert("Error: " + result.message);
      }
    } catch (error) {
      console.error("Error in viewLeaveRequest:", error);
      alert("Error: " + error.message);
    }
  }

  async function handleAction(id, action, successMsg) {
    if (
      !confirm(
        `Are you sure you want to ${
          action === "approve_leave" ? "approve" : "decline"
        } this request?`
      )
    )
      return;

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
        alert(successMsg);
        loadLeaveRequests(); // Refresh table
      } else {
        alert("Error: " + result.message);
      }
    } catch (error) {
      alert("Error: " + error.message);
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
