// js/employee_profile.js - Employee Profile Page Logic
let isEditMode = false;
let originalData = {};

// Load profile data on page load
document.addEventListener("DOMContentLoaded", () => {
  loadProfileData();
  setupPasswordToggles();
});

async function loadProfileData() {
  try {
    const response = await fetch(
      "../views/employee_profile_handler.php?action=get_profile"
    );
    const result = await response.json();

    if (!result.success) {
      showStatus(result.message || "Failed to load profile data", "error");
      return;
    }

    const data = result.data;
    originalData = { ...data };

    // Update profile header
    document.getElementById("profileImage").src =
      data.avatar_path || "../pages/img/user.jpg";
    document.getElementById(
      "fullName"
    ).textContent = `${data.first_name} ${data.last_name}`;
    document.getElementById("roleDisplay").textContent = "Employee";

    // Personal Information
    document.getElementById("firstName").value = data.first_name || "";
    document.getElementById("lastName").value = data.last_name || "";
    document.getElementById("email").value = data.email || "";
    document.getElementById("phoneNumber").value = data.phone_number || "";
    document.getElementById("address").value = data.address || "";
    document.getElementById("dateOfBirth").value = data.date_of_birth || "";
    document.getElementById("gender").value = data.gender || "";
    document.getElementById("civilStatus").value = data.civil_status || "";

    // Emergency Contact
    document.getElementById("emergencyContactName").value =
      data.emergency_contact_name || "";
    document.getElementById("emergencyContactPhone").value =
      data.emergency_contact_phone || "";

    // Employment Information (Read-only)
    document.getElementById("employeeId").value = data.employee_id || "";
    document.getElementById("dateHired").value = data.date_hired || "";
    document.getElementById("department").value = data.department_name || "N/A";
    document.getElementById("position").value = data.position_name || "N/A";
    document.getElementById("employmentStatus").value =
      data.employment_status || "";
  } catch (error) {
    console.error("Error loading profile:", error);
    showStatus("An error occurred while loading profile data", "error");
  }
}

function editProfile() {
  isEditMode = true;
  document.getElementById("profileCard").classList.add("edit-mode");

  // Make editable fields editable
  document.getElementById("phoneNumber").removeAttribute("readonly");
  document.getElementById("address").removeAttribute("readonly");
  document.getElementById("emergencyContactName").removeAttribute("readonly");
  document.getElementById("emergencyContactPhone").removeAttribute("readonly");

  // Toggle buttons
  document.querySelector(".btn-secondary").style.display = "none";
  document.querySelector(".btn-primary").style.display = "inline-flex";
}

async function saveProfile() {
  try {
    const formData = new FormData();
    formData.append("action", "update_profile");
    formData.append(
      "phone_number",
      document.getElementById("phoneNumber").value.trim()
    );
    formData.append("address", document.getElementById("address").value.trim());
    formData.append(
      "emergency_contact_name",
      document.getElementById("emergencyContactName").value.trim()
    );
    formData.append(
      "emergency_contact_phone",
      document.getElementById("emergencyContactPhone").value.trim()
    );

    const response = await fetch("../views/employee_profile_handler.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (!result.success) {
      showStatus(result.message || "Failed to update profile", "error");
      return;
    }

    showStatus(result.message || "Profile updated successfully", "success");
    cancelEdit();
    loadProfileData(); // Reload data
  } catch (error) {
    console.error("Error saving profile:", error);
    showStatus("An error occurred while saving profile", "error");
  }
}

function cancelEdit() {
  isEditMode = false;
  document.getElementById("profileCard").classList.remove("edit-mode");

  // Make fields readonly again
  document.getElementById("phoneNumber").setAttribute("readonly", true);
  document.getElementById("address").setAttribute("readonly", true);
  document
    .getElementById("emergencyContactName")
    .setAttribute("readonly", true);
  document
    .getElementById("emergencyContactPhone")
    .setAttribute("readonly", true);

  // Restore original values
  loadProfileData();

  // Toggle buttons
  document.querySelector(".btn-secondary").style.display = "inline-flex";
  document.querySelector(".btn-primary").style.display = "none";
}

function triggerImageUpload() {
  if (!isEditMode) {
    editProfile(); // Auto-enable edit mode when clicking avatar
  }
  document.getElementById("imageUpload").click();
}

// Handle avatar upload
document
  .getElementById("imageUpload")
  ?.addEventListener("change", async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith("image/")) {
      showStatus("Please select a valid image file", "error");
      return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      showStatus("Image size must be less than 5MB", "error");
      return;
    }

    try {
      const formData = new FormData();
      formData.append("action", "update_avatar");
      formData.append("avatar", file);

      const response = await fetch("../views/employee_profile_handler.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (!result.success) {
        showStatus(result.message || "Failed to upload avatar", "error");
        return;
      }

      // Update avatar image
      document.getElementById("profileImage").src =
        result.avatar_path + "?t=" + Date.now();
      showStatus(result.message || "Avatar updated successfully", "success");
    } catch (error) {
      console.error("Error uploading avatar:", error);
      showStatus("An error occurred while uploading avatar", "error");
    }
  });

async function changePassword() {
  const currentPassword = document
    .getElementById("currentPassword")
    .value.trim();
  const newPassword = document.getElementById("newPassword").value.trim();
  const confirmPassword = document
    .getElementById("confirmPassword")
    .value.trim();

  // Validation
  if (!currentPassword || !newPassword || !confirmPassword) {
    showStatus("All password fields are required", "error");
    return;
  }

  if (newPassword !== confirmPassword) {
    showStatus("New passwords do not match", "error");
    return;
  }

  if (newPassword.length < 6) {
    showStatus("New password must be at least 6 characters", "error");
    return;
  }

  if (newPassword === currentPassword) {
    showStatus("New password must be different from current password", "error");
    return;
  }

  try {
    const formData = new FormData();
    formData.append("action", "change_password");
    formData.append("current_password", currentPassword);
    formData.append("new_password", newPassword);
    formData.append("confirm_password", confirmPassword);

    const response = await fetch("../views/employee_profile_handler.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (!result.success) {
      showStatus(result.message || "Failed to change password", "error");
      return;
    }

    showStatus(result.message || "Password changed successfully", "success");

    // Clear password fields
    document.getElementById("currentPassword").value = "";
    document.getElementById("newPassword").value = "";
    document.getElementById("confirmPassword").value = "";
  } catch (error) {
    console.error("Error changing password:", error);
    showStatus("An error occurred while changing password", "error");
  }
}

function setupPasswordToggles() {
  const toggles = [
    { button: "currentPasswordToggle", input: "currentPassword" },
    { button: "newPasswordToggle", input: "newPassword" },
    { button: "confirmPasswordToggle", input: "confirmPassword" },
  ];

  toggles.forEach(({ button, input }) => {
    const toggleBtn = document.getElementById(button);
    const inputField = document.getElementById(input);

    if (toggleBtn && inputField) {
      toggleBtn.addEventListener("click", () => {
        const type =
          inputField.getAttribute("type") === "password" ? "text" : "password";
        inputField.setAttribute("type", type);
      });
    }
  });
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
