let currentLogoFile = null; // Track uploaded logo file
let settingsData = {}; // Store loaded settings

// Load settings on page load
async function loadSettings() {
  let response;
  let responseText = "";
  try {
    console.log(
      "Loading settings from ../views/settings_handler.php?action=load"
    );
    response = await fetch("../views/settings_handler.php?action=load");

    responseText = await response.text();

    if (!response.ok) {
      console.error(
        `HTTP Error: ${response.status} ${response.statusText}`,
        responseText
      );
      showStatus(
        `Failed to load settings: ${response.status} ${response.statusText}. Check if settings_handler.php exists at ../views/settings_handler.php.`,
        "error"
      );
      return;
    }

    const result = JSON.parse(responseText);
    console.log("Settings loaded:", result);

    if (result.success) {
      settingsData = result.data;
      populateForm();
      updateSidebar();
    } else {
      console.error("API Error:", result.message);
      showStatus("Failed to load settings: " + result.message, "error");
    }
  } catch (error) {
    console.error("Network or parsing error:", error);
    console.log("Raw response text:", responseText);
    showStatus(
      "Error loading settings: " +
        error.message +
        ". Check network or file path.",
      "error"
    );
  }
}

// Save payroll frequency per job role
async function saveRolePayrollSettings() {
  const container = document.getElementById("rolePayrollContainer");
  if (!container) return;
  const selects = container.querySelectorAll("select[data-role-id]");
  const map = {};
  selects.forEach((sel) => {
    const id = sel.getAttribute("data-role-id");
    if (id) map[id] = sel.value;
  });

  let response;
  let responseText = "";
  const formData = new FormData();
  formData.append("action", "save_role_payroll");
  formData.append("frequencies", JSON.stringify(map));

  try {
    response = await fetch("../views/settings_handler.php", {
      method: "POST",
      body: formData,
    });
    responseText = await response.text();
    if (!response.ok) {
      showStatus(`Failed to save: ${response.status} ${response.statusText}`, "error");
      return;
    }
    const result = JSON.parse(responseText);
    if (result.success) {
      showStatus(result.message || "Saved.", "success");
      // Reflect locally
      if (settingsData.payroll && Array.isArray(settingsData.payroll.roles)) {
        settingsData.payroll.roles = settingsData.payroll.roles.map((r) => ({
          ...r,
          payroll_frequency: map[r.id] ? map[r.id] : r.payroll_frequency,
        }));
      }
    } else {
      showStatus(result.message || "Failed to save.", "error");
    }
  } catch (e) {
    showStatus("Error saving: " + e.message, "error");
  }
}

function populateForm() {
  // System Info
  const system = settingsData.system || {};
  const systemNameElem = document.getElementById("systemName");
  if (systemNameElem)
    systemNameElem.value = system.system_name || "EAAPS Admin";
  if (system.logo_path) {
    const logoPreview = document.getElementById("logoPreview");
    if (logoPreview) logoPreview.src = "../" + system.logo_path;
  }

  // Time & Date Info
  const timeDate = settingsData.time_date || {};
  const autoLogoutElem = document.getElementById("autoLogoutTime");
  if (autoLogoutElem) {
    // Convert hours back to minutes for display
    const hours = timeDate.auto_logout_time_hours || 1;
    autoLogoutElem.value = Math.round(hours * 60);
  }
  const dateFormatElem = document.getElementById("dateFormat");
  if (dateFormatElem)
    dateFormatElem.value = timeDate.date_format || "DD/MM/YYYY";
  const graceInElem = document.getElementById("graceInMinutes");
  if (graceInElem)
    graceInElem.value = Number.isFinite(parseInt(timeDate.grace_in_minutes))
      ? parseInt(timeDate.grace_in_minutes)
      : 0;
  const graceOutElem = document.getElementById("graceOutMinutes");
  if (graceOutElem)
    graceOutElem.value = Number.isFinite(parseInt(timeDate.grace_out_minutes))
      ? parseInt(timeDate.grace_out_minutes)
      : 0;
  const companyHoursElem = document.getElementById("companyHoursPerDay");
  if (companyHoursElem)
    companyHoursElem.value = Number.isFinite(
      parseFloat(timeDate.company_hours_per_day)
    )
      ? parseFloat(timeDate.company_hours_per_day)
      : 8;

  // Leave Info
  const leave = settingsData.leave || {};
  const annualLeaveElem = document.getElementById("annualLeaveDays");
  if (annualLeaveElem)
    annualLeaveElem.value = leave.annual_paid_leave_days || 15;
  const unpaidLeaveElem = document.getElementById("unpaidLeaveDays");
  if (unpaidLeaveElem)
    unpaidLeaveElem.value = leave.annual_unpaid_leave_days || 5;
  const sickLeaveElem = document.getElementById("sickLeaveDays");
  if (sickLeaveElem) sickLeaveElem.value = leave.annual_sick_leave_days || 10;

  // Attendance Info
  const attendance = settingsData.attendance || {};
  const lateThresholdElem = document.getElementById("lateThreshold");
  if (lateThresholdElem)
    lateThresholdElem.value = attendance.late_threshold || 15;
  const undertimeElem = document.getElementById("undertimeThreshold");
  if (undertimeElem) undertimeElem.value = attendance.undertime_threshold || 30;
  const regularOvertimeElem = document.getElementById("regularOvertimeRate");
  if (regularOvertimeElem)
    regularOvertimeElem.value = attendance.regular_overtime || 1.25;
  const holidayOvertimeElem = document.getElementById("holidayOvertimeRate");
  if (holidayOvertimeElem)
    holidayOvertimeElem.value = attendance.holiday_overtime || 2;

  // Backup Info
  const backup = settingsData.backup || {};
  const backupFreqElem = document.getElementById("backupFrequency");
  if (backupFreqElem)
    backupFreqElem.value = backup.backup_frequency || "weekly";
  const sessionTimeoutElem = document.getElementById("sessionTimeout");
  if (sessionTimeoutElem)
    sessionTimeoutElem.value = backup.session_timeout_minutes || 30;

  // Payroll per role
  const payroll = settingsData.payroll || {};
  renderRolePayroll(Array.isArray(payroll.roles) ? payroll.roles : []);
}

function updateSidebar() {
  // Update sidebar logo and app name
  if (settingsData.system.logo_path) {
    document.getElementById("sidebarLogo").src =
      "../" + settingsData.system.logo_path;
  }
  document.getElementById("sidebarAppName").textContent =
    settingsData.system.system_name || "EAAPS Admin";
}

// Render payroll frequency selectors per job role
function renderRolePayroll(roles) {
  const container = document.getElementById("rolePayrollContainer");
  if (!container) return;
  container.innerHTML = "";

  if (!Array.isArray(roles) || roles.length === 0) {
    const p = document.createElement("p");
    p.className = "small-text";
    p.textContent = "No job roles found. Add roles in Departments and Positions.";
    container.appendChild(p);
    return;
  }

  const grid = document.createElement("div");
  grid.className = "form-row three-col";

  const options = [
    { v: "weekly", l: "Weekly" },
    { v: "biweekly", l: "Bi-Weekly" },
    { v: "semimonthly", l: "Semi-Monthly" },
    { v: "monthly", l: "Monthly" },
  ];

  roles.forEach((role) => {
    const group = document.createElement("div");
    group.className = "form-group";

    const label = document.createElement("label");
    label.textContent = role.name;
    group.appendChild(label);

    const select = document.createElement("select");
    select.setAttribute("data-role-id", String(role.id));
    options.forEach((opt) => {
      const o = document.createElement("option");
      o.value = opt.v;
      o.textContent = opt.l;
      select.appendChild(o);
    });
    select.value = (role.payroll_frequency || "monthly").toLowerCase();
    group.appendChild(select);

    grid.appendChild(group);
  });

  container.appendChild(grid);
}

// Logo Upload Functions
function triggerLogoUpload() {
  document.getElementById("logoUpload").click();
}

document
  .getElementById("logoUpload")
  .addEventListener("change", function (event) {
    const file = event.target.files[0];
    if (file) {
      if (!file.type.startsWith("image/")) {
        showStatus("Please select a valid image file.", "error");
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        showStatus("Image size must be less than 2MB.", "error");
        return;
      }
      currentLogoFile = file;
      const reader = new FileReader();
      reader.onload = function (e) {
        document.getElementById("logoPreview").src = e.target.result;
        showStatus("Logo selected. Click Save to upload.", "success");
      };
      reader.readAsDataURL(file);
    }
  });

// Save Functions
async function saveSystemInfo() {
  let response;
  let responseText = "";
  const formData = new FormData();
  formData.append("action", "save_system_info");
  formData.append("system_name", document.getElementById("systemName").value);
  if (currentLogoFile) formData.append("logo", currentLogoFile);

  try {
    console.log("Saving system info to ../views/settings_handler.php");
    response = await fetch("../views/settings_handler.php", {
      method: "POST",
      body: formData,
    });

    responseText = await response.text();

    if (!response.ok) {
      console.error(
        `HTTP Error: ${response.status} ${response.statusText}`,
        responseText
      );
      showStatus(
        `Failed to save: ${response.status} ${response.statusText}. Check server or file path.`,
        "error"
      );
      return;
    }

    const result = JSON.parse(responseText);
    console.log("Save result:", result);

    if (result.success) {
      showStatus(result.message, "success");
      currentLogoFile = null;
      // Update settings and sidebar
      settingsData.system.system_name = result.system_name;
      if (result.logo_path) settingsData.system.logo_path = result.logo_path;
      updateSidebar();
    } else {
      console.error("API Error:", result.message);
      showStatus("Failed to save: " + result.message, "error");
    }
  } catch (error) {
    console.error("Network or parsing error:", error);
    console.log("Raw response text:", responseText);
    showStatus(
      "Error saving: " + error.message + ". Check network or file path.",
      "error"
    );
  }
}

async function saveTimeDateSettings() {
  let response;
  let responseText = "";
  const minutes = parseInt(document.getElementById("autoLogoutTime").value);
  const dateFormatVal = document.getElementById("dateFormat").value;
  const graceInVal = parseInt(
    document.getElementById("graceInMinutes").value
  );
  const graceOutVal = parseInt(
    document.getElementById("graceOutMinutes").value
  );
  const companyHoursVal = parseFloat(
    document.getElementById("companyHoursPerDay").value
  );

  // 0 = disabled
  if (minutes === 0) {
    // Allow 0
  }
  // 1–9 = invalid
  else if (minutes < 10) {
    showStatus("Minimum auto logout time is 10 minutes.", "error");
    return;
  }

  if (graceInVal < 0 || graceInVal > 120) {
    showStatus("Grace Period (Time In) must be 0–120 minutes.", "error");
    return;
  }
  if (graceOutVal < 0 || graceOutVal > 120) {
    showStatus("Grace Period (Time Out) must be 0–120 minutes.", "error");
    return;
  }
  if (companyHoursVal < 1 || companyHoursVal > 24) {
    showStatus("Total Working Hours per Day must be 1–24 hours.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("action", "save_time_date");
  formData.append("auto_logout", minutes);
  formData.append("date_format", dateFormatVal);
  formData.append("grace_in", graceInVal);
  formData.append("grace_out", graceOutVal);
  formData.append("company_hours_per_day", companyHoursVal);

  try {
    console.log("Saving time & date settings to ../views/settings_handler.php");
    response = await fetch("../views/settings_handler.php", {
      method: "POST",
      body: formData,
    });

    responseText = await response.text();

    if (!response.ok) {
      console.error(
        `HTTP Error: ${response.status} ${response.statusText}`,
        responseText
      );
      showStatus(
        `Failed to save: ${response.status} ${response.statusText}. Check server or file path.`,
        "error"
      );
      return;
    }

    const result = JSON.parse(responseText);
    console.log("Save result:", result);

    if (result.success) {
      showStatus(result.message, "success");
      // Update local settings
      const decimal = minutes === 0 ? 0 : minutes / 60;
      settingsData.time_date = {
        auto_logout_time_hours: decimal,
        date_format: dateFormatVal,
        grace_in_minutes: graceInVal,
        grace_out_minutes: graceOutVal,
        company_hours_per_day: companyHoursVal,
      };
      // Auto-refresh to apply date format immediately
      setTimeout(() => location.reload(), 1000);
    } else {
      console.error("API Error:", result.message);
      showStatus("Failed to save: " + result.message, "error");
    }
  } catch (error) {
    console.error("Network or parsing error:", error);
    console.log("Raw response text:", responseText);
    showStatus(
      "Error saving: " + error.message + ". Check network or file path.",
      "error"
    );
  }
}

async function savePayrollLeaveSettings() {
  let response;
  let responseText = "";
  const formData = new FormData();
  formData.append("action", "save_leave");
  formData.append(
    "annual_paid_leave",
    document.getElementById("annualLeaveDays").value
  );
  formData.append(
    "annual_unpaid_leave",
    document.getElementById("unpaidLeaveDays").value
  );
  formData.append(
    "annual_sick_leave",
    document.getElementById("sickLeaveDays").value
  );

  try {
    console.log("Saving leave settings to ../views/settings_handler.php");
    response = await fetch("../views/settings_handler.php", {
      method: "POST",
      body: formData,
    });

    responseText = await response.text();

    if (!response.ok) {
      console.error(
        `HTTP Error: ${response.status} ${response.statusText}`,
        responseText
      );
      showStatus(
        `Failed to save: ${response.status} ${response.statusText}. Check server or file path.`,
        "error"
      );
      return;
    }

    const result = JSON.parse(responseText);
    console.log("Save result:", result);

    if (result.success) {
      showStatus(result.message, "success");
      // Refresh the page to fetch updated data
      setTimeout(() => location.reload(), 1000);
    } else {
      console.error("API Error:", result.message);
      showStatus("Failed to save: " + result.message, "error");
    }
  } catch (error) {
    console.error("Network or parsing error:", error);
    console.log("Raw response text:", responseText);
    showStatus(
      "Error saving: " + error.message + ". Check network or file path.",
      "error"
    );
  }
}

async function saveAttendanceSettings() {
  let response;
  let responseText = "";
  const formData = new FormData();
  formData.append("action", "save_attendance");
  formData.append(
    "late_threshold",
    document.getElementById("lateThreshold").value
  );
  formData.append(
    "undertime_threshold",
    document.getElementById("undertimeThreshold").value
  );
  formData.append(
    "regular_overtime",
    document.getElementById("regularOvertimeRate").value
  );
  formData.append(
    "holiday_overtime",
    document.getElementById("holidayOvertimeRate").value
  );

  try {
    console.log("Saving attendance settings to ../views/settings_handler.php");
    response = await fetch("../views/settings_handler.php", {
      method: "POST",
      body: formData,
    });

    responseText = await response.text();

    if (!response.ok) {
      console.error(
        `HTTP Error: ${response.status} ${response.statusText}`,
        responseText
      );
      showStatus(
        `Failed to save: ${response.status} ${response.statusText}. Check server or file path.`,
        "error"
      );
      return;
    }

    const result = JSON.parse(responseText);
    console.log("Save result:", result);

    if (result.success) {
      showStatus(result.message, "success");
      // Update local settings
      settingsData.attendance = {
        late_threshold: parseInt(
          document.getElementById("lateThreshold").value
        ),
        undertime_threshold: parseInt(
          document.getElementById("undertimeThreshold").value
        ),
        regular_overtime: parseFloat(
          document.getElementById("regularOvertimeRate").value
        ),
        holiday_overtime: parseFloat(
          document.getElementById("holidayOvertimeRate").value
        ),
      };
    } else {
      console.error("API Error:", result.message);
      showStatus("Failed to save: " + result.message, "error");
    }
  } catch (error) {
    console.error("Network or parsing error:", error);
    console.log("Raw response text:", responseText);
    showStatus(
      "Error saving: " + error.message + ". Check network or file path.",
      "error"
    );
  }
}

async function exportData() {
  window.open("../views/settings_handler.php?action=export_backup", "_blank");
  showStatus("Backup download started.", "success");
}

async function createBackup() {
  try {
    const response = await fetch(
      "../views/settings_handler.php?action=create_backup",
      { method: "POST" }
    );
    const result = await response.json();
    showStatus(result.message, result.success ? "success" : "error");
  } catch (error) {
    showStatus("Error creating backup.", "error");
  }
}

async function restoreBackup() {
  const confirmed = await showConfirmation(
    "Are you sure you want to restore from backup? This may overwrite current data.",
    "Restore Backup",
    "red"
  );

  if (!confirmed) return;

  try {
    const response = await fetch(
      "../views/settings_handler.php?action=restore_backup",
      { method: "POST" }
    );
    const result = await response.json();
    showStatus(result.message, result.success ? "success" : "error");
  } catch (error) {
    showStatus("Error restoring backup.", "error");
  }
}

function showStatus(message, type) {
  const statusDiv = document.getElementById("status-message");
  if (!statusDiv) return;

  statusDiv.textContent = message;
  statusDiv.className = `status-message ${type}`;
  statusDiv.classList.add("show");

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

    if (!confirmationModal) {
      console.error("Confirmation modal not found");
      resolve(false);
      return;
    }

    // Set message
    confirmationMessage.textContent = message;

    // Set button text and color
    confirmationConfirmBtn.textContent = confirmText;

    // Reset classes and add new ones based on color
    confirmationConfirmBtn.className = `px-4 py-2 text-white text-base font-medium rounded-md shadow-sm focus:outline-none focus:ring-2`;

    if (confirmColor === "red") {
      confirmationConfirmBtn.classList.add(
        "bg-red-600",
        "hover:bg-red-700",
        "focus:ring-red-500"
      );
    } else {
      confirmationConfirmBtn.classList.add(
        "bg-blue-600",
        "hover:bg-blue-700",
        "focus:ring-blue-500"
      );
    }

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
      document.removeEventListener("keydown", handleEscape);
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
      }
    };
    document.addEventListener("keydown", handleEscape);
  });
}

// Load settings on DOM ready
document.addEventListener("DOMContentLoaded", loadSettings);
