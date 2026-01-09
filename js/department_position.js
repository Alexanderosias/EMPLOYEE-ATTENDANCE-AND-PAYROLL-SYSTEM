document.addEventListener("DOMContentLoaded", () => {
  const BASE_PATH = "/eaaps"; // XAMPP Apache base path

  const API_BASE = BASE_PATH + "/views/departments_positions.php"; // Backend endpoint

  const isHeadAdmin =
    window.isHeadAdmin === true || window.isHeadAdmin === "true";

  // Company-wide hours per day (from Time & Date settings)
  let companyHoursPerDay = 8;

  async function loadCompanyHours() {
    try {
      const resp = await fetch("../views/settings_handler.php?action=load");
      if (!resp.ok) return;
      const result = await resp.json();
      const val = parseFloat(
        result?.data?.time_date?.company_hours_per_day
      );
      if (!isNaN(val) && val > 0) companyHoursPerDay = val;
    } catch (e) {
      // Keep default 8 if load fails
    }
  }

  // Modal helper functions
  function openModal(modal) {
    modal.setAttribute("aria-hidden", "false");
    const input = modal.querySelector("input");
    if (input) {
      input.focus();
    }
  }

  function closeModal(modal) {
    modal.setAttribute("aria-hidden", "true");
    modal.querySelector("form")?.reset();
  }

  // Delete confirmation modal elements
  const deleteConfirmationModal = document.getElementById(
    "delete-confirmation-modal"
  );
  const deleteConfirmationMessage = document.getElementById(
    "delete-confirmation-message"
  );
  const deleteWarningMessage = document.getElementById(
    "delete-warning-message"
  );
  const confirmDeleteBtn = document.getElementById("confirm-delete-btn");
  let itemToDelete = null;
  let itemId = null;
  let itemType = null; // 'department' or 'position'

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

  // Setup delete confirmation modal
  deleteConfirmationModal
    .querySelectorAll(".modal-close-btn")
    .forEach((btn) => {
      btn.addEventListener("click", () => closeModal(deleteConfirmationModal));
    });

  deleteConfirmationModal.addEventListener("click", (e) => {
    if (e.target === deleteConfirmationModal)
      closeModal(deleteConfirmationModal);
  });

  confirmDeleteBtn.addEventListener("click", () => {
    if (itemId && itemType) {
      deleteItem(itemId, itemType);
    }
  });

  // Update Job Position modal elements
  const updateJobPositionModal = document.getElementById("update-job-position-modal");
  const updateJobPositionForm = document.getElementById("update-job-position-form");
  const updId = document.getElementById("upd-position-id");
  const updName = document.getElementById("upd-position-name");
  const updRate = document.getElementById("upd-position-rate");
  const updFreq = document.getElementById("upd-position-frequency");

  // Close handlers for Update modal
  if (updateJobPositionModal) {
    updateJobPositionModal.querySelectorAll(".modal-close-btn").forEach((btn) => {
      btn.addEventListener("click", () => closeModal(updateJobPositionModal));
    });
    updateJobPositionModal.addEventListener("click", (e) => {
      if (e.target === updateJobPositionModal) closeModal(updateJobPositionModal);
    });
  }

  function openUpdatePositionModal(pos) {
    if (!isHeadAdmin) return;
    if (!updateJobPositionModal) return;
    updId.value = String(pos.id);
    updName.value = pos.name || "";
    updRate.value = (parseFloat(pos.rate_per_day || 0) || 0).toString();
    updFreq.value = String(pos.payroll_frequency || 'bi-weekly').toLowerCase();
    openModal(updateJobPositionModal);
  }

  // Submit Update Job Position
  if (updateJobPositionForm) {
    updateJobPositionForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!isHeadAdmin) return;
      const formData = new FormData(updateJobPositionForm);
      formData.append("action", "update_position");
      try {
        const response = await fetch(API_BASE, { method: "POST", body: formData });
        const result = await response.json();
        if (result.success) {
          showStatus(result.message || "Job position updated.");
          closeModal(updateJobPositionModal);
          fetchPositions();
        } else {
          showStatus(result.message || "Failed to update position.", "error");
        }
      } catch (error) {
        showStatus("Failed to update position. " + error.message, "error");
      }
    });
  }

  // Delete function
  async function deleteItem(id, type) {
    try {
      const response = await fetch(
        `${API_BASE}?action=delete_${type}&id=${id}`,
        { method: "DELETE" }
      );
      const result = await response.json();
      if (result.success) {
        showStatus(result.message || "Item deleted successfully.");
        closeModal(deleteConfirmationModal);
        if (type === "department") {
          fetchDepartments();
        } else {
          fetchPositions();
        }
      } else {
        showStatus("Error: " + result.message, "error");
      }
    } catch (error) {
      console.error("Delete error:", error);
      showStatus("Failed to delete item. Check console for details.", "error");
    }
    itemToDelete = null;
    itemId = null;
    itemType = null;
  }

  // Departments
  const addDepartmentBtn = document.getElementById("add-department-btn");
  const addDepartmentModal = document.getElementById("add-department-modal");
  const addDepartmentForm = document.getElementById("add-department-form");
  const departmentsList = document.getElementById("departments-list");

  if (addDepartmentBtn) {
    addDepartmentBtn.addEventListener("click", () =>
      openModal(addDepartmentModal)
    );
  }

  addDepartmentModal.querySelectorAll(".modal-close-btn").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(addDepartmentModal));
  });

  addDepartmentModal.addEventListener("click", (e) => {
    if (e.target === addDepartmentModal) closeModal(addDepartmentModal);
  });

  addDepartmentForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(addDepartmentForm);
    formData.append("action", "add_department");

    try {
      const response = await fetch(API_BASE, {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.success) {
        showStatus(result.message || "Department added successfully.");
        closeModal(addDepartmentModal);
        fetchDepartments(); // Refresh list
      } else {
        showStatus("Error: " + result.message, "error");
      }
    } catch (error) {
      console.error("Add department error:", error);
      showStatus(
        "Failed to add department. Check console for details.",
        "error"
      );
    }
  });

  // Job Positions
  const addJobPositionBtn = document.getElementById("add-job-position-btn");
  const addJobPositionModal = document.getElementById("add-job-position-modal");
  const addJobPositionForm = document.getElementById("add-job-position-form");
  const jobPositionsList = document.getElementById("job-positions-list");

  if (addJobPositionBtn) {
    addJobPositionBtn.addEventListener("click", () =>
      openModal(addJobPositionModal)
    );
  }

  addJobPositionModal.querySelectorAll(".modal-close-btn").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(addJobPositionModal));
  });

  addJobPositionModal.addEventListener("click", (e) => {
    if (e.target === addJobPositionModal) closeModal(addJobPositionModal);
  });

  addJobPositionForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(addJobPositionForm);
    formData.append("action", "add_position");

    // Client-side validation for rate_per_day
    const rateInput = document.getElementById("job-position-rate");
    const rateValue = parseFloat(rateInput.value);
    if (isNaN(rateValue) || rateValue < 0) {
      showStatus("Rate per day must be a positive number.", "error");
      rateInput.focus();
      return;
    }

    try {
      const response = await fetch(API_BASE, {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.success) {
        showStatus(result.message || "Job position added successfully.");
        closeModal(addJobPositionModal);
        fetchPositions(); // Refresh list
      } else {
        showStatus("Error: " + result.message, "error");
      }
    } catch (error) {
      console.error("Add position error:", error);
      showStatus(
        "Failed to add job position. Check console for details.",
        "error"
      );
    }
  });

  // Function to create list item with delete button (disabled if employees assigned)
  function createListItem(id, text, count, type) {
    const li = document.createElement("li");
    li.setAttribute("data-id", id);

    const itemContent = document.createElement("span");
    itemContent.className = "item-content";
    itemContent.textContent = `${text} (${count})`;

    const itemActions = document.createElement("div");
    itemActions.className = "item-actions";

    if (isHeadAdmin) {
      const deleteBtn = document.createElement("button");
      deleteBtn.className = "btn-delete";
      deleteBtn.setAttribute("aria-label", `Delete ${text}`);

      const deleteImg = document.createElement("img");
      deleteImg.src = "./icons/delete.png"; // Adjust path as needed
      deleteImg.alt = "Delete";
      deleteImg.className = "delete-icon";

      deleteBtn.appendChild(deleteImg);

      const typeName = type === "department" ? "department" : "job position";
      if (count > 0) {
        deleteBtn.disabled = true;
        deleteBtn.setAttribute("aria-disabled", "true");
        deleteBtn.title = `Cannot delete this ${typeName} because it has ${count} employee(s) assigned. Please reassign employees first.`;
        deleteBtn.classList.add("disabled-delete");
      } else {
        deleteBtn.addEventListener("click", () => {
          showDeleteConfirmation(li, id, text, count, type);
        });
        deleteBtn.title = `Delete ${typeName} "${text}"`;
      }

      itemActions.appendChild(deleteBtn);
    }
    li.appendChild(itemContent);
    li.appendChild(itemActions);

    return li;
  }

  // Function to show delete confirmation (only if count === 0)
  function showDeleteConfirmation(listItem, id, itemName, employeeCount, type) {
    const typeName = type === "department" ? "department" : "job position";
    if (employeeCount > 0) {
      // Prevention: Don't open modal; show alert instead (redundant with disabled button, but safety)
      showStatus(
        `Cannot delete "${itemName}": This ${typeName} has ${employeeCount} employee(s) assigned. Please reassign them first.`,
        "error"
      );
      return; // Exit early
    }

    // Proceed only if count === 0
    deleteConfirmationMessage.textContent = `Are you sure you want to delete the ${typeName} "${itemName}"?`;

    // No warning needed since count === 0, but keep for future
    deleteWarningMessage.style.display = "none";

    itemToDelete = listItem;
    itemId = id;
    itemType = type;
    openModal(deleteConfirmationModal);
  }

  // Fetch and render departments
  async function fetchDepartments() {
    try {
      const response = await fetch(`${API_BASE}?action=list_departments`);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const result = await response.json();
      departmentsList.innerHTML = ""; // Clear
      if (!result.success) {
        showStatus("Error loading departments: " + result.message, "error");
        departmentsList.innerHTML = "<li>Error loading departments.</li>";
        return;
      }
      const departments = result.data;
      const summaryDept = document.getElementById("summary-dept-count");
      if (summaryDept) {
        summaryDept.textContent = Array.isArray(departments)
          ? departments.length
          : 0;
      }
      if (departments.length === 0) {
        departmentsList.innerHTML = "<li>No departments found.</li>";
      } else {
        departments.forEach((dept) => {
          const li = createListItem(
            dept.id,
            dept.name,
            dept.employee_count,
            "department"
          );
          departmentsList.appendChild(li);
        });
      }
    } catch (error) {
      console.error("Error fetching departments:", error);
      showStatus(
        "Failed to load departments: " +
          error.message +
          ". Check the API path and server logs.",
        "error"
      );
      departmentsList.innerHTML = "<li>Error loading departments.</li>";
    }
  }

  // Fetch and render job positions
  async function fetchPositions() {
    try {
      const response = await fetch(`${API_BASE}?action=list_positions`);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      const result = await response.json();
      jobPositionsList.innerHTML = ""; // Clear
      if (!result.success) {
        showStatus("Error loading job positions: " + result.message, "error");
        jobPositionsList.innerHTML = "<li>Error loading job positions.</li>";
        return;
      }
      const positions = result.data;
      const summaryPos = document.getElementById("summary-pos-count");
      if (summaryPos) {
        summaryPos.textContent = Array.isArray(positions)
          ? positions.length
          : 0;
      }
      if (positions.length === 0) {
        jobPositionsList.innerHTML = "<li>No job positions found.</li>";
      } else {
        positions.forEach((pos) => {
          const ratePerDayNum = parseFloat(pos.rate_per_day || 0);
          const ratePerHourNum = companyHoursPerDay > 0 ? ratePerDayNum / companyHoursPerDay : 0;
          const freq = String(pos.payroll_frequency || 'bi-weekly').toLowerCase();
          const freqLabelMap = { 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly' };
          const freqLabel = freqLabelMap[freq] || freq;

          const displayText = `${pos.name} - ₱${ratePerDayNum.toFixed(2)}/day (₱${ratePerHourNum.toFixed(2)}/hr) • ${freqLabel}`;
          const li = createListItem(pos.id, displayText, pos.employee_count, "position");

          if (isHeadAdmin) {
            const actions = li.querySelector('.item-actions');
            if (actions) {
              const editBtn = document.createElement('button');
              editBtn.className = 'btn-delete';
              editBtn.setAttribute('aria-label', `Update ${pos.name}`);
              const editImg = document.createElement('img');
              editImg.src = './icons/update.png';
              editImg.alt = 'Update';
              editImg.className = 'delete-icon';
              editBtn.appendChild(editImg);
              editBtn.title = `Update job position "${pos.name}"`;
              editBtn.addEventListener('click', () => openUpdatePositionModal(pos));
              actions.appendChild(editBtn);
            }
          }

          jobPositionsList.appendChild(li);
        });
      }
    } catch (error) {
      console.error("Error fetching positions:", error);
      showStatus(
        "Failed to load job positions: " +
          error.message +
          ". Check the API path and server logs.",
        "error"
      );
      jobPositionsList.innerHTML = "<li>Error loading job positions.</li>";
    }
  }

  // Initialize on load: load company hours then render lists
  loadCompanyHours().finally(() => {
    fetchDepartments();
    fetchPositions();
  });
});
