function showStatus(message, type) {
  const statusDiv = document.getElementById("status-message");
  if (!statusDiv) {
    console.error("Status message div not found!");
    return;
  }
  statusDiv.textContent = message;
  statusDiv.className = `status-message ${type}`;
  statusDiv.classList.add("show");

  // Auto-hide after 3 seconds
  setTimeout(() => {
    statusDiv.classList.remove("show");
  }, 3000);
}

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

document.addEventListener("DOMContentLoaded", () => {
  const BASE_PATH = ""; // Change to '' for localhost:8000, or '/newpath' for Hostinger

  const API_BASE = BASE_PATH + "/views/employees.php";

  let schoolSettings = {}; // Store school settings globally

  // Removed: checkAndSyncPending() and related Firebase sync logic

  window.addEventListener("online", () => {
    showStatus("Back online – ready to save changes.", "success");
  });
  window.addEventListener("offline", () => {
    showStatus("Offline mode: Changes may be delayed.", "error");
  });

  function updateDateTime() {
    const now = new Date();
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    };
    document.getElementById("current-datetime").textContent =
      now.toLocaleDateString("en-US", options);
  }
  updateDateTime();
  setInterval(updateDateTime, 60000);

  const employeeListContainer = document.getElementById(
    "employee-list-container"
  );
  const searchInput = document.getElementById("search-input");
  const searchBtn = document.getElementById("search-btn");
  const filterDepartment = document.getElementById("filter-department");
  const filterJobPosition = document.getElementById("filter-job-position");
  let departments = [];
  let jobPositions = [];
  let positionRateMap = {}; // Map to store position ID to rate_per_hour

  function getDetailItemText(card, label) {
    const items = card.querySelectorAll(".detail-item, .expanded-item");
    for (const item of items) {
      if (item.textContent.trim().startsWith(label)) {
        return item.textContent;
      }
    }
    return "";
  }

  function formatPhoneForDisplay(cleanPhone) {
    if (!cleanPhone || typeof cleanPhone !== "string") return "";
    const digits = cleanPhone.replace(/\D/g, "");
    if (digits.length !== 11) return cleanPhone;
    return `${digits.slice(0, 3)} ${digits.slice(3, 7)} ${digits.slice(7, 11)}`;
  }

  function cleanPhoneNumber(phone) {
    return phone.replace(/\D/g, "");
  }

  function autoFormatPhoneInput(input) {
    let value = input.value.replace(/\s/g, "");
    let cursorPos = input.selectionStart;

    if (value.length > 3) {
      value = value.slice(0, 3) + " " + value.slice(3);
      cursorPos++;
    }

    if (value.length > 8) {
      value = value.slice(0, 8) + " " + value.slice(8);
      cursorPos++;
    }

    const digitsOnly = value.replace(/\D/g, "").slice(0, 11);
    value = digitsOnly.slice(0, 3);
    if (digitsOnly.length > 3) value += " " + digitsOnly.slice(3, 7);
    if (digitsOnly.length > 7) value += " " + digitsOnly.slice(7, 11);

    input.value = value;

    const newCursor = Math.min(cursorPos, input.value.length);
    input.setSelectionRange(newCursor, newCursor);
  }

  function validatePhoneFormat(phone) {
    const phoneRegex = /^\d{3} \d{4} \d{4}$/;
    return phoneRegex.test(phone);
  }

  async function loadSelectOptions() {
    try {
      const [deptRes, posRes] = await Promise.all([
        fetch(`${API_BASE}?action=departments`),
        fetch(`${API_BASE}?action=positions`),
      ]);
      if (!deptRes.ok || !posRes.ok) throw new Error("Failed to load options");
      const deptData = await deptRes.json();
      const posData = await posRes.json();
      if (!deptData.success || !posData.success)
        throw new Error("Invalid options response");
      departments = deptData.data || [];
      jobPositions = posData.data || [];
      if (!Array.isArray(departments)) departments = [];
      if (!Array.isArray(jobPositions)) jobPositions = [];

      // Build rate map for quick lookup
      positionRateMap = {};
      jobPositions.forEach((pos) => {
        positionRateMap[pos.id] = {
          rate_per_hour: parseFloat(pos.rate_per_hour) || 0,
          rate_per_day: parseFloat(pos.rate_per_day) || 0,
        };
      });

      filterDepartment.innerHTML =
        '<option value="">All Departments</option>' +
        departments
          .map((d) => `<option value="${d.name}">${d.name}</option>`)
          .join("");
      filterJobPosition.innerHTML =
        '<option value="">All Job Positions</option>' +
        jobPositions
          .map((p) => `<option value="${p.name}">${p.name}</option>`)
          .join("");

      const addDeptSelect = document.getElementById("department");
      const addPosSelect = document.getElementById("job-position");
      addDeptSelect.innerHTML =
        '<option value="" disabled selected>Select department</option>' +
        departments
          .map((d) => `<option value="${d.id}">${d.name}</option>`)
          .join("");
      addPosSelect.innerHTML =
        '<option value="" disabled selected>Select job position</option>' +
        jobPositions
          .map((p) => `<option value="${p.id}">${p.name}</option>`)
          .join("");
      const updateDeptSelect = document.getElementById("update-department");
      const updatePosSelect = document.getElementById("update-job-position");
      if (updateDeptSelect)
        updateDeptSelect.innerHTML = addDeptSelect.innerHTML;
      if (updatePosSelect) updatePosSelect.innerHTML = addPosSelect.innerHTML;
    } catch (error) {
      console.error("Error loading options:", error);
      showStatus(
        "Failed to load departments/positions. Please refresh.",
        "error"
      );
    }
  }

  async function loadSchoolSettings() {
    try {
      const response = await fetch(`${API_BASE}?action=get_school_settings`);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || "Failed to load school settings");
      }
      schoolSettings = result.data;

      // Only set add modal fields (for new employees)
      document.getElementById("annual-paid-leave-days").value =
        schoolSettings.annual_paid_leave_days;
      document.getElementById("annual-unpaid-leave-days").value =
        schoolSettings.annual_unpaid_leave_days;
      document.getElementById("annual-sick-leave-days").value =
        schoolSettings.annual_sick_leave_days;

      // Make them readonly in add modal
      document.getElementById("annual-paid-leave-days").readOnly = true;
      document.getElementById("annual-unpaid-leave-days").readOnly = true;
      document.getElementById("annual-sick-leave-days").readOnly = true;
    } catch (error) {
      console.error("Error loading school settings:", error);
      showStatus("Failed to load leave settings. Using defaults.", "error");
      // Fallback to defaults if fetch fails
      schoolSettings = {
        annual_paid_leave_days: 15,
        annual_unpaid_leave_days: 5,
        annual_sick_leave_days: 10,
      };
      document.getElementById("annual-paid-leave-days").value =
        schoolSettings.annual_paid_leave_days;
      document.getElementById("annual-unpaid-leave-days").value =
        schoolSettings.annual_unpaid_leave_days;
      document.getElementById("annual-sick-leave-days").value =
        schoolSettings.annual_sick_leave_days;
    }
  }

  async function fetchEmployees() {
    employeeListContainer.innerHTML =
      '<p style="text-align: center; color: #666;">Loading employees...</p>';

    try {
      const response = await fetch(`${API_BASE}?action=list_employees`);
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const responseData = await response.json();
      console.log("Raw JSON response:", responseData);
      if (!responseData || typeof responseData !== "object") {
        throw new Error("Invalid response format: Not an object");
      }
      if (!responseData.success) {
        throw new Error(responseData.message || "Failed to fetch employees");
      }
      const employees = responseData.data || [];
      console.log("Data type:", typeof employees);
      console.log("Employees length:", employees.length);
      if (employees.length > 0) {
        console.log("First employee ID type:", typeof employees[0].id);
        console.log("Processing employee:", employees[0]);
      }
      if (!Array.isArray(employees)) {
        throw new Error("Invalid response format: Data is not an array");
      }
      renderEmployeeCards(employees);
    } catch (error) {
      console.error("Error fetching employees:", error);
      showStatus("Failed to load employees: " + error.message, "error");
      employeeListContainer.innerHTML =
        '<p style="color: red; text-align: center;">Error loading employees. Please refresh.</p>';
    }
  }

  function renderEmployeeCards(employees) {
    employeeListContainer.innerHTML = "";
    if (employees.length === 0) {
      employeeListContainer.innerHTML =
        '<p style="text-align: center; color: #666;">No employees found.</p>';
      return;
    }
    employees.forEach((emp, index) => {
      const id = parseInt(emp.id, 10);
      const rate = parseFloat(emp.rate_per_hour) || 0;
      const paidLeave = parseInt(emp.annual_paid_leave_days, 10) || 15;
      const unpaidLeave = parseInt(emp.annual_unpaid_leave_days, 10) || 5;
      const sickLeave = parseInt(emp.annual_sick_leave_days, 10) || 10;
      let statusClass = "";
      const status = emp.status || "Active";
      if (status === "On Leave") {
        statusClass = "status-on-leave";
      } else if (status === "Active") {
        statusClass = "status-active";
      } else if (status === "Inactive") {
        statusClass = "status-inactive";
      }

      const formattedContact = formatPhoneForDisplay(emp.contact_number || "");
      let formattedEmergency = "N/A";
      if (
        emp.emergency_contact_name ||
        emp.emergency_contact_relationship ||
        emp.emergency_contact_phone
      ) {
        const name = emp.emergency_contact_name || "";
        const rel = emp.emergency_contact_relationship
          ? `(${emp.emergency_contact_relationship})`
          : "";
        const phone = formatPhoneForDisplay(emp.emergency_contact_phone || "");
        formattedEmergency = `${name} ${rel} - ${phone}`.trim();
      }

      const card = document.createElement("div");
      card.className = "employee-card minimized";
      card.setAttribute("data-id", id);
      const avatarSrc = emp.avatar_path
        ? BASE_PATH + "/" + emp.avatar_path
        : BASE_PATH + "/img/user.jpg";
      card.innerHTML = `
        <div class="card-index">${index + 1}</div>
        <div class="image-container employee-avatar">
          <img src="${avatarSrc}" alt="Employee Photo" class="employee-photo" onerror="this.src='${BASE_PATH}img/user.jpg'" />
        </div>
        <div class="employee-details">
          <p class="detail-item"><strong>Name:</strong> ${
            emp.first_name || ""
          } ${emp.last_name || ""}</p>
          <p class="detail-item"><strong>Job Position:</strong> ${
            emp.position_name || "N/A"
          }</p>
          <p class="detail-item"><strong>Department:</strong> ${
            emp.department_name || "N/A"
          }</p>
          <p class="expanded-item ${statusClass}"><strong>Status:</strong> ${status}</p>
          <div class="expanded-details" aria-hidden="true">
            <p class="expanded-item"><strong>Gender:</strong> ${
              emp.gender || "N/A"
            }</p>
            <p class="expanded-item"><strong>Marital Status:</strong> ${
              emp.marital_status || "Single"
            }</p>
            <p class="detail-item"><strong>Address:</strong> ${
              emp.address || "N/A"
            }</p>
            <p class="expanded-item"><strong>Email Address:</strong> ${
              emp.email || ""
            }</p>
            <p class="expanded-item"><strong>Rate per Hour:</strong> ₱${rate.toFixed(
              2
            )}</p>
            <p class="expanded-item"><strong>Rate per Day:</strong> ₱${(
              parseFloat(emp.rate_per_day) || 0
            ).toFixed(2)}</p>
            <p class="expanded-item"><strong>Contact Number:</strong> ${formattedContact}</p>
            <p class="expanded-item"><strong>Emergency Contact:</strong> ${formattedEmergency}</p>
            <p class="expanded-item"><strong>Annual Paid Leave Days:</strong> ${paidLeave}</p>
            <p class="expanded-item"><strong>Annual Unpaid Leave Days:</strong> ${unpaidLeave}</p>
            <p class="expanded-item"><strong>Annual Sick Leave Days:</strong> ${sickLeave}</p>
            <p class="expanded-item"><strong>Date Joined:</strong> ${
              emp.date_joined || "N/A"
            }</p>
          </div>
        </div>
        <div class="employee-actions">
          <button class="action-btn action-btn-update" title="Update info" aria-label="Update info">
            <img src="icons/update.png" alt="Update info" />
          </button>
          <button class="action-btn action-btn-delete" title="Delete" aria-label="Delete">
            <img src="icons/delete.png" alt="Delete" />
          </button>
          <button class="action-btn action-btn-show-more" title="Expand/Collapse details" aria-label="Expand or collapse details">
            <img src="icons/down-arrow.png" alt="Expand or collapse details" />
          </button>
        </div>
      `;
      employeeListContainer.appendChild(card);
    });
  }

  function openUpdateModal(employeeData) {
    const updateModal = document.getElementById("update-employee-modal");
    updateModal.setAttribute("aria-hidden", "false");
    const idInput = document.getElementById("update-employee-id");

    if (idInput) idInput.value = employeeData.id || "";
    document.getElementById("update-first-name").value =
      employeeData.first_name || "";
    document.getElementById("update-last-name").value =
      employeeData.last_name || "";
    document.getElementById("update-address").value =
      employeeData.address || "";
    document.getElementById("update-email").value = employeeData.email || "";
    document.getElementById("update-contact-number").value =
      formatPhoneForDisplay(employeeData.contact_number || "") || "";
    // Auto-set rate based on current job position
    const currentPosId = employeeData.job_position_id;
    document.getElementById("update-rate-per-hour").value =
      positionRateMap[currentPosId]?.rate_per_hour || 0.0;
    document.getElementById("update-rate-per-day").value =
      positionRateMap[currentPosId]?.rate_per_day || 0.0;
    document.getElementById("update-gender").value = employeeData.gender || "";
    document.getElementById("update-marital-status").value =
      employeeData.marital_status || "Single";
    document.getElementById("update-department").value =
      employeeData.department_id || "";
    document.getElementById("update-job-position").value = currentPosId || "";
    // Set leave days from employee's current data (not global settings)
    document.getElementById("update-annual-paid-leave-days").value =
      parseInt(employeeData.annual_paid_leave_days, 10) || 15;
    document.getElementById("update-annual-unpaid-leave-days").value =
      parseInt(employeeData.annual_unpaid_leave_days, 10) || 5;
    document.getElementById("update-annual-sick-leave-days").value =
      parseInt(employeeData.annual_sick_leave_days, 10) || 10;
    // Make them readonly in update modal
    document.getElementById("update-annual-paid-leave-days").readOnly = true;
    document.getElementById("update-annual-unpaid-leave-days").readOnly = true;
    document.getElementById("update-annual-sick-leave-days").readOnly = true;
    document.getElementById("update-emergency-name").value =
      employeeData.emergency_contact_name || "";
    document.getElementById("update-emergency-phone").value =
      formatPhoneForDisplay(employeeData.emergency_contact_phone || "") || "";
    document.getElementById("update-emergency-relationship").value =
      employeeData.emergency_contact_relationship || "";

    const previewImg = document.getElementById("update-avatar-preview-img");
    if (!previewImg) {
      console.error("Update avatar preview img not found!");
      return;
    }

    let avatarSrc;
    if (employeeData.avatar_path && employeeData.avatar_path.trim() !== "") {
      avatarSrc = employeeData.avatar_path;
    } else {
      avatarSrc = "img/user.jpg";
    }

    previewImg.src = "";
    previewImg.alt = "Loading employee avatar preview...";

    setTimeout(() => {
      previewImg.src = avatarSrc;
      previewImg.alt = "Employee avatar preview";
    }, 50);

    console.log("Update Modal: Setting avatar src to:", avatarSrc);
    console.log("Update Modal: DB avatar_path:", employeeData.avatar_path);
    console.log("Update Modal: Employee ID:", employeeData.id);

    previewImg.onerror = function () {
      console.warn(
        "Update avatar preview failed to load (ID:",
        employeeData.id,
        "), falling back to default"
      );
      this.src = "img/user.jpg";
      this.alt = "Default avatar preview";
    };

    previewImg.onload = function () {
      console.log(
        "Update avatar preview loaded successfully for ID:",
        employeeData.id
      );
    };

    const updateEmployeeForm = document.getElementById("update-employee-form");
    updateEmployeeForm.querySelector("input, select").focus();
  }

  function filterEmployees() {
    const searchTerm = searchInput.value.toLowerCase();
    const departmentFilter = filterDepartment.value;
    const jobPositionFilter = filterJobPosition.value;

    const employeeCards =
      employeeListContainer.querySelectorAll(".employee-card");

    employeeCards.forEach((card) => {
      const nameText = getDetailItemText(card, "Name:").toLowerCase();
      const departmentText = getDetailItemText(card, "Department:");
      const jobPositionText = getDetailItemText(card, "Job Position:");

      const name = nameText.split(":")[1]?.trim() || "";
      const department = departmentText.split(":")[1]?.trim() || "";
      const jobPosition = jobPositionText.split(":")[1]?.trim() || "";

      const matchesSearch = name.includes(searchTerm);
      const matchesDepartment =
        !departmentFilter || department === departmentFilter;
      const matchesJobPosition =
        !jobPositionFilter || jobPosition === jobPositionFilter;

      if (matchesSearch && matchesDepartment && matchesJobPosition) {
        card.style.display = "";
      } else {
        card.style.display = "none";
      }
    });
  }

  searchBtn.addEventListener("click", filterEmployees);
  searchInput.addEventListener("input", filterEmployees);
  filterDepartment.addEventListener("change", filterEmployees);
  filterJobPosition.addEventListener("change", filterEmployees);

  employeeListContainer.addEventListener("click", (event) => {
    const showMoreBtn = event.target.closest(".action-btn-show-more");
    if (showMoreBtn) {
      const employeeCard = showMoreBtn.closest(".employee-card");
      const expandedDetails = employeeCard.querySelector(".expanded-details");
      const iconImg = showMoreBtn.querySelector("img");

      if (expandedDetails.classList.contains("visible")) {
        expandedDetails.classList.remove("visible");
        expandedDetails.setAttribute("aria-hidden", "true");
        employeeCard.classList.add("minimized");
        if (iconImg) {
          iconImg.src = "icons/down-arrow.png";
          iconImg.alt = "Expand details";
        }
      } else {
        expandedDetails.classList.add("visible");
        expandedDetails.setAttribute("aria-hidden", "false");
        employeeCard.classList.remove("minimized");
        if (iconImg) {
          iconImg.src = "icons/up-arrow.png";
          iconImg.alt = "Collapse details";
        }
      }
    }
  });

  employeeListContainer.addEventListener("click", async (event) => {
    if (event.target.closest(".action-btn-update")) {
      const employeeCard = event.target.closest(".employee-card");
      const employeeId = employeeCard.dataset.id;
      console.log("Update info clicked for ID:", employeeId);

      try {
        const response = await fetch(
          `${API_BASE}?action=get_employee&id=${employeeId}`
        );
        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}));
          throw new Error(errorData.message || "Failed to fetch employee data");
        }
        const responseData = await response.json();
        if (!responseData.success) {
          throw new Error(
            responseData.message || "Failed to fetch employee data"
          );
        }
        openUpdateModal(responseData.data);
      } catch (error) {
        console.error("Error fetching employee for update:", error);
        showStatus(
          "Failed to load employee data for update: " + error.message,
          "error"
        );
      }
    } else if (event.target.closest(".action-btn-delete")) {
      const employeeCard = event.target.closest(".employee-card");
      const employeeId = employeeCard.dataset.id;
      console.log("Delete clicked for ID:", employeeId);
      const confirmed = await showConfirmation(
        "Are you sure you want to delete this employee? This action cannot be undone.",
        "Delete Employee",
        "red"
      );
      if (confirmed) {
        await deleteEmployee(employeeId);
      }
    }
  });

  async function deleteEmployee(id) {
    try {
      // Check if this employee is the last head admin
      const checkResponse = await fetch(
        `${API_BASE}?action=check_last_head_admin&id=${id}`
      );
      const checkResult = await checkResponse.json();
      if (!checkResult.success) {
        showStatus(
          "Failed to check permissions: " + checkResult.message,
          "error"
        );
        return;
      }
      if (checkResult.is_last_head_admin) {
        showStatus(
          "Cannot delete this employee as they are the last active head administrator. Please assign another head administrator first.",
          "error"
        );
        return;
      }

      const confirmed = await showConfirmation(
        "Are you sure you want to delete this employee? This action cannot be undone.",
        "Delete Employee",
        "red"
      );

      if (!confirmed) {
        return;
      }

      const formData = new FormData();
      formData.append("action", "delete_employee");
      formData.append("id", id);

      const response = await fetch(API_BASE, {
        method: "POST",
        body: formData,
      });
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        showStatus(
          result.message || "Employee deleted successfully.",
          "success"
        );
        fetchEmployees();
      } else {
        showStatus(
          "Error: " + (result.message || "Failed to delete employee"),
          "error"
        );
      }
    } catch (error) {
      console.error("Delete error:", error);
      showStatus("Failed to delete employee: " + error.message, "error");
    }
  }

  const addModal = document.getElementById("add-employee-modal");
  const addEmployeeBtn = document.getElementById("add-employee-btn");
  const addCloseButtons = addModal.querySelectorAll(".modal-close-btn");
  const addAvatarInput = document.getElementById("avatar-input");
  const addAvatarPreviewImg = document.getElementById("avatar-preview-img");
  const addEmployeeForm = document.getElementById("add-employee-form");
  const addUploadImageBtn = document.getElementById("upload-image-btn");
  const addContactInput = document.getElementById("contact-number");
  const addEmergencyPhoneInput = document.getElementById("emergency-phone");
  if (addContactInput)
    addContactInput.addEventListener("input", () =>
      autoFormatPhoneInput(addContactInput)
    );
  if (addEmergencyPhoneInput)
    addEmergencyPhoneInput.addEventListener("input", () =>
      autoFormatPhoneInput(addEmergencyPhoneInput)
    );

  addEmployeeBtn.addEventListener("click", async () => {
    // Add async
    // Wait for settings to load if not already loaded
    if (!schoolSettings.annual_paid_leave_days) {
      await loadSchoolSettings();
    }
    addModal.setAttribute("aria-hidden", "false");
    addEmployeeForm.reset();
    document.getElementById("marital-status").value = "Single";
    // Set leave days from schoolSettings (for new employees)
    document.getElementById("annual-paid-leave-days").value =
      schoolSettings.annual_paid_leave_days || 15;
    document.getElementById("annual-unpaid-leave-days").value =
      schoolSettings.annual_unpaid_leave_days || 5;
    document.getElementById("annual-sick-leave-days").value =
      schoolSettings.annual_sick_leave_days || 10;
    document.getElementById("rate-per-hour").value = 0.0;
    document.getElementById("emergency-name").value = "";
    document.getElementById("emergency-phone").value = "";
    document.getElementById("emergency-relationship").value = "";
    addAvatarPreviewImg.src = "img/user.jpg";
    addAvatarPreviewImg.alt = "Avatar preview";
    addEmployeeForm.querySelector("input, select").focus();
  });

  addCloseButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      addModal.setAttribute("aria-hidden", "true");
    });
  });

  addModal.addEventListener("click", (e) => {
    if (e.target === addModal) {
      addModal.setAttribute("aria-hidden", "true");
    }
  });

  addUploadImageBtn.addEventListener("click", () => {
    addAvatarInput.click();
  });

  addAvatarInput.addEventListener("change", () => {
    const file = addAvatarInput.files[0];
    if (file && file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        addAvatarPreviewImg.src = e.target.result;
        addAvatarPreviewImg.alt = "Selected avatar preview";
      };
      reader.readAsDataURL(file);
    } else {
      addAvatarPreviewImg.src = "img/user.jpg";
      addAvatarPreviewImg.alt = "Avatar preview";
    }
  });

  // Auto-populate rate on job position change in add modal
  document.getElementById("job-position").addEventListener("change", (e) => {
    const selectedId = e.target.value;
    document.getElementById("rate-per-hour").value =
      positionRateMap[selectedId]?.rate_per_hour || 0.0;
    document.getElementById("rate-per-day").value =
      positionRateMap[selectedId]?.rate_per_day || 0.0;
  });

  addEmployeeForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const firstName = document.getElementById("first-name").value.trim();
    const lastName = document.getElementById("last-name").value.trim();
    const email = document.getElementById("email").value.trim();
    const address = document.getElementById("address").value.trim();
    const gender = document.getElementById("gender").value;
    const maritalStatus = document.getElementById("marital-status").value;
    const contactNumber = document
      .getElementById("contact-number")
      .value.trim();
    const emergencyName = document
      .getElementById("emergency-name")
      .value.trim();
    const emergencyPhone = document
      .getElementById("emergency-phone")
      .value.trim();
    const emergencyRelationship = document
      .getElementById("emergency-relationship")
      .value.trim();
    const departmentId = document.getElementById("department").value;
    const jobPositionId = document.getElementById("job-position").value;
    const ratePerHour =
      parseFloat(document.getElementById("rate-per-hour").value) || 0;

    if (
      !firstName ||
      !lastName ||
      !email ||
      !address ||
      !gender ||
      !maritalStatus ||
      !contactNumber ||
      !emergencyName ||
      !emergencyPhone ||
      !emergencyRelationship ||
      !departmentId ||
      !jobPositionId ||
      ratePerHour < 0
    ) {
      showStatus("Please fill all required fields correctly.", "error");
      return;
    }
    if (!email.includes("@")) {
      showStatus("Please enter a valid email address.", "error");
      return;
    }
    if (!validatePhoneFormat(contactNumber)) {
      showStatus("Contact number must be in format: 09X XXXX XXXX", "error");
      document.getElementById("contact-number").focus();
      return;
    }
    if (!validatePhoneFormat(emergencyPhone)) {
      showStatus("Emergency phone must be in format: 09X XXXX XXXX", "error");
      document.getElementById("emergency-phone").focus();
      return;
    }

    const formData = new FormData(addEmployeeForm);
    formData.append("action", "add_employee");
    const cleanedContact = cleanPhoneNumber(contactNumber);
    const cleanedEmergencyPhone = cleanPhoneNumber(emergencyPhone);
    formData.set("contact_number", cleanedContact);
    formData.set("emergency_contact_phone", cleanedEmergencyPhone);
    const submitBtn = addEmployeeForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = "Adding...";
    submitBtn.disabled = true;
    console.log("Add FormData keys:", Array.from(formData.keys()));
    console.log("Add FormData values:", Object.fromEntries(formData.entries()));

    try {
      const response = await fetch(API_BASE, {
        method: "POST",
        body: formData,
      });
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        showStatus(result.message || "Employee added successfully.", "success");
        addModal.setAttribute("aria-hidden", "true");
        addEmployeeForm.reset();
        document.getElementById("marital-status").value = "Single";
        // Leave days are auto-filled, so no need to reset to hardcoded values
        document.getElementById("rate-per-hour").value = 0.0;
        document.getElementById("rate-per-day").value = 0.0;
        document.getElementById("emergency-name").value = "";
        document.getElementById("emergency-phone").value = "";
        document.getElementById("emergency-relationship").value = "";
        addAvatarPreviewImg.src = "img/user.jpg";
        fetchEmployees();
      } else {
        showStatus(
          "Error: " + (result.message || "Failed to add employee"),
          "error"
        );
      }
    } catch (error) {
      console.error("Add error:", error);
      showStatus("Failed to add employee: " + error.message, "error");
    } finally {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  const updateModal = document.getElementById("update-employee-modal");
  const updateAvatarInput = document.getElementById("update-avatar-input");
  const updateAvatarPreviewImg = document.getElementById(
    "update-avatar-preview-img"
  );
  const updateUploadImageBtn = document.getElementById(
    "update-upload-image-btn"
  );
  const updateEmployeeForm = document.getElementById("update-employee-form");
  const updateCloseButtons = updateModal.querySelectorAll(".modal-close-btn");
  const updateContactInput = document.getElementById("update-contact-number");
  const updateEmergencyPhoneInput = document.getElementById(
    "update-emergency-phone"
  );
  if (updateContactInput)
    updateContactInput.addEventListener("input", () =>
      autoFormatPhoneInput(updateContactInput)
    );
  if (updateEmergencyPhoneInput)
    updateEmergencyPhoneInput.addEventListener("input", () =>
      autoFormatPhoneInput(updateEmergencyPhoneInput)
    );

  updateCloseButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      updateModal.setAttribute("aria-hidden", "true");
    });
  });

  updateModal.addEventListener("click", (e) => {
    if (e.target === updateModal) {
      updateModal.setAttribute("aria-hidden", "true");
    }
  });

  updateUploadImageBtn.addEventListener("click", () => {
    updateAvatarInput.click();
  });

  updateAvatarInput.addEventListener("change", () => {
    const file = updateAvatarInput.files[0];
    if (file && file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        updateAvatarPreviewImg.src = e.target.result;
        updateAvatarPreviewImg.alt = "Selected avatar preview";
      };
      reader.readAsDataURL(file);
    } else {
      const idInput = document.getElementById("update-employee-id").value;
      if (idInput) {
        updateAvatarPreviewImg.src = "img/user.jpg";
      }
      updateAvatarPreviewImg.alt = "Avatar preview";
    }
  });

  // Auto-populate rate on job position change in update modal
  document
    .getElementById("update-job-position")
    .addEventListener("change", (e) => {
      const selectedId = e.target.value;
      document.getElementById("update-rate-per-hour").value =
        positionRateMap[selectedId]?.rate_per_hour || 0.0;
      document.getElementById("update-rate-per-day").value =
        positionRateMap[selectedId]?.rate_per_day || 0.0;
    });

  updateEmployeeForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const id = document.getElementById("update-employee-id")?.value;
    if (!id) {
      showStatus(
        "Employee ID not found. Please refresh and try again.",
        "error"
      );
      return;
    }

    const firstName = document.getElementById("update-first-name").value.trim();
    const lastName = document.getElementById("update-last-name").value.trim();
    const email = document.getElementById("update-email").value.trim();
    const address = document.getElementById("update-address").value.trim();
    const gender = document.getElementById("update-gender").value;
    const maritalStatus = document.getElementById(
      "update-marital-status"
    ).value;
    const contactNumber = document
      .getElementById("update-contact-number")
      .value.trim();
    const emergencyName = document
      .getElementById("update-emergency-name")
      .value.trim();
    const emergencyPhone = document
      .getElementById("update-emergency-phone")
      .value.trim();
    const emergencyRelationship = document
      .getElementById("update-emergency-relationship")
      .value.trim();
    const departmentId = document.getElementById("update-department").value;
    const jobPositionId = document.getElementById("update-job-position").value;
    const ratePerHour =
      parseFloat(document.getElementById("update-rate-per-hour").value) || 0;

    if (
      !firstName ||
      !lastName ||
      !email ||
      !address ||
      !gender ||
      !maritalStatus ||
      !departmentId ||
      !jobPositionId ||
      ratePerHour < 0
    ) {
      showStatus("Please fill all required fields correctly.", "error");
      return;
    }
    if (!email.includes("@")) {
      showStatus("Please enter a valid email address.", "error");
      return;
    }
    if (contactNumber && !validatePhoneFormat(contactNumber)) {
      showStatus("Contact number must be in format: 09X XXXX XXXX", "error");
      document.getElementById("update-contact-number").focus();
      return;
    }
    if (emergencyPhone && !validatePhoneFormat(emergencyPhone)) {
      showStatus("Emergency phone must be in format: 09X XXXX XXXX", "error");
      document.getElementById("update-emergency-phone").focus();
      return;
    }

    const formData = new FormData(updateEmployeeForm);
    formData.append("action", "edit_employee");
    formData.append("id", id);

    const cleanedContact = contactNumber ? cleanPhoneNumber(contactNumber) : "";
    const cleanedEmergencyPhone = emergencyPhone
      ? cleanPhoneNumber(emergencyPhone)
      : "";
    formData.set("contact_number", cleanedContact);
    formData.set("emergency_contact_phone", cleanedEmergencyPhone);

    const submitBtn = updateEmployeeForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = "Updating...";
    submitBtn.disabled = true;

    console.log("Update FormData keys:", Array.from(formData.keys()));
    console.log(
      "Update FormData values:",
      Object.fromEntries(formData.entries())
    );

    try {
      const response = await fetch(API_BASE, {
        method: "POST",
        body: formData,
      });
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        showStatus(
          result.message || "Employee updated successfully.",
          "success"
        );
        updateModal.setAttribute("aria-hidden", "true");
        updateEmployeeForm.reset();
        document.getElementById("update-marital-status").value = "Single";
        // Leave days are auto-filled, so no need to reset to hardcoded values
        document.getElementById("update-rate-per-hour").value = 0.0;
        document.getElementById("update-rate-per-day").value = 0.0;
        document.getElementById("update-emergency-name").value = "";
        document.getElementById("update-emergency-phone").value = "";
        document.getElementById("update-emergency-relationship").value = "";
        updateAvatarPreviewImg.src = "img/user.jpg";
        fetchEmployees();
      } else {
        showStatus(
          "Error: " + (result.message || "Failed to update employee"),
          "error"
        );
      }
    } catch (error) {
      console.error("Update error:", error);
      showStatus("Failed to update employee: " + error.message, "error");
    } finally {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const openModals = document.querySelectorAll('[aria-hidden="false"]');
      openModals.forEach((modal) => {
        modal.setAttribute("aria-hidden", "true");
        const form = modal.querySelector("form");
        if (form) form.reset();
      });
    }
  });

  loadSelectOptions();
  loadSchoolSettings();
  fetchEmployees();
});
