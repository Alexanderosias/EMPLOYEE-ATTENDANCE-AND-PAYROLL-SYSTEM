const BASE_PATH = ""; // Change for deployment
const API_BASE = BASE_PATH + "/views/profile_handler.php";

let isEditMode = false;
let currentImageFile = null;
let userData = {}; // Store user data

// Fetch and populate profile data on load
async function loadProfile() {
  try {
    const response = await fetch(`${API_BASE}?action=get_profile`);
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    userData = result.data;
    // Populate fields
    document.getElementById("firstName").value = userData.first_name;
    document.getElementById("lastName").value = userData.last_name;
    document.getElementById("email").value = userData.email;
    document.getElementById("phone").value = userData.phone_number || "";
    document.getElementById("address").value = userData.address || "";
    document.getElementById("joinDate").value = new Date(userData.created_at)
      .toISOString()
      .split("T")[0];
    document.getElementById("department").value = userData.department_id || "";
    // Avatar
    const avatarSrc = userData.avatar_path
      ? BASE_PATH + "/" + userData.avatar_path // BASE_PATH is '', so '/uploads/avatars/...'
      : "img/user.jpg";
    document.getElementById("profileImage").src = avatarSrc;

    // Name and role
    document.getElementById(
      "fullName"
    ).textContent = `${userData.first_name} ${userData.last_name}`;
    // Parse and display roles
    let roles = [];
    try {
      roles = JSON.parse(userData.roles || "[]");
    } catch (e) {
      roles = [];
    }
    const roleMap = {
      employee: "Employee",
      admin: "Administrator",
      head_admin: "Head Administrator",
    };
    const roleDisplay = roles.map((r) => roleMap[r] || r).join(" & ");
    document.getElementById("roleDisplay").textContent = roleDisplay || "User";
    // Check if user can edit (only if NOT employee, and has admin or head_admin)
    const canEdit =
      !roles.includes("employee") &&
      (roles.includes("admin") || roles.includes("head_admin"));
    const hasEmployeeRole = roles.includes("employee");

    // Hide edit button and show note for linked users (employee role)
    const editButton = document.querySelector(".btn-secondary");
    const editNote = document.getElementById("editNote");
    if (hasEmployeeRole) {
      if (editButton) editButton.style.display = "none";
      if (editNote) editNote.style.display = "block";
    } else if (canEdit) {
      if (editButton) editButton.style.display = "inline-flex";
      if (editNote) editNote.style.display = "none";
    } else {
      // No roles or other cases: hide edit
      if (editButton) editButton.style.display = "none";
      if (editNote) editNote.style.display = "none";
    }
    // Populate department options
    await fetchDepartments();
  } catch (error) {
    console.error("Error loading profile:", error);
    showStatus("Failed to load profile.", "error");
  }
}

// Fetch departments for dropdown
async function fetchDepartments() {
  try {
    const response = await fetch(
      BASE_PATH + "/views/departments_handler.php?action=list_departments"
    );
    const result = await response.json();
    if (result.success) {
      const select = document.getElementById("department");
      select.innerHTML = '<option value="">Select Department</option>';
      result.data.forEach((dept) => {
        select.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
      });
      select.value = userData.department_id || "";
    }
  } catch (error) {
    console.error("Error fetching departments:", error);
  }
}

// Edit Profile (only if allowed)
function editProfile() {
  // Check if user can edit (only if NOT employee, and has admin or head_admin)
  let roles = [];
  try {
    roles = JSON.parse(userData.roles || "[]");
  } catch (e) {
    roles = [];
  }
  const canEdit =
    !roles.includes("employee") &&
    (roles.includes("admin") || roles.includes("head_admin"));
  if (!canEdit) {
    showStatus("You do not have permission to edit your profile.", "error");
    return;
  }
  isEditMode = true;
  const profileCard = document.getElementById("profileCard");
  profileCard.classList.add("edit-mode");
  // Enable editing for all inputs except joinDate
  const inputs = document.querySelectorAll(
    "#profileCard .form-group input:not(#joinDate), #profileCard .form-group select, #profileCard .form-group textarea"
  );
  inputs.forEach((input) => {
    input.removeAttribute("readonly");
    input.removeAttribute("disabled");
  });
  // Keep joinDate always readonly
  document.getElementById("joinDate").setAttribute("readonly", true);
  // Show upload overlay and enable avatar click
  document.querySelector(".upload-overlay").style.display = "flex";
  document.getElementById("profileImage").style.cursor = "pointer";
  // Update buttons
  document.querySelector(".btn-secondary").style.display = "none";
  document.querySelector(".btn-primary").style.display = "inline-flex";
  showStatus("Editing mode enabled.", "success");
}

// Save Profile
async function saveProfile() {
  const formData = new FormData();
  formData.append("firstName", document.getElementById("firstName").value);
  formData.append("lastName", document.getElementById("lastName").value);
  formData.append("email", document.getElementById("email").value);
  formData.append("phone", document.getElementById("phone").value);
  formData.append("address", document.getElementById("address").value);
  formData.append("departmentId", document.getElementById("department").value);

  if (currentImageFile) {
    formData.append("avatar", currentImageFile);
  }

  try {
    const response = await fetch(`${API_BASE}?action=update_profile`, {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);

    // Update UI
    const profileCard = document.getElementById("profileCard");
    profileCard.classList.remove("edit-mode");

    const inputs = document.querySelectorAll(
      "#profileCard .form-group input:not(#joinDate), #profileCard .form-group select, #profileCard .form-group textarea"
    );
    inputs.forEach((input) => {
      input.setAttribute("readonly", true);
      input.setAttribute("disabled", true);
    });

    document.getElementById("joinDate").setAttribute("readonly", true);
    document.querySelector(".upload-overlay").style.display = "none";
    document.getElementById("profileImage").style.cursor = "default";

    // Update name display
    document.getElementById("fullName").textContent = `${
      document.getElementById("firstName").value
    } ${document.getElementById("lastName").value}`;

    document.querySelector(".btn-secondary").style.display = "inline-flex";
    document.querySelector(".btn-primary").style.display = "none";

    showStatus("Profile updated successfully!", "success");
    currentImageFile = null;
    isEditMode = false;

    // Reload profile data to reflect changes
    loadProfile();
  } catch (error) {
    console.error("Error saving profile:", error);
    showStatus("Failed to update profile: " + error.message, "error");
  }
}

// Trigger image upload
function triggerImageUpload() {
  if (!isEditMode) return;
  document.getElementById("imageUpload").click();
}

// Handle image upload
document
  .getElementById("imageUpload")
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

      currentImageFile = file;
      const reader = new FileReader();
      reader.onload = function (e) {
        document.getElementById("profileImage").src = e.target.result;
        showStatus("Image selected. Click Save Changes to upload.", "success");
      };
      reader.readAsDataURL(file);
    }
  });

// Password toggles
const confirmPasswordToggle = document.getElementById("confirmPasswordToggle");
const currentPasswordToggle = document.getElementById("currentPasswordToggle");
const newPasswordToggle = document.getElementById("newPasswordToggle");

function togglePasswordVisibility(input, toggle) {
  const type = input.getAttribute("type") === "password" ? "text" : "password";
  input.setAttribute("type", type);

  const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
  const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.988 5.623a.9 4 4 0 010 .769 9.877 9.877 0 000 6.046c.381 2.385 1.503 4.266 3.048 5.485C9.37 19.262 10.9 19.5 12.067 19.5c1.167 0 2.697-.238 4.23-.782l.968-.34c.73-.243 1.408-.544 2.046-.902a1.012 1.012 0 00-.063-.035c-.158-.09-.313-.19-.462-.296l-1.07-1.1c-.26-.26-.54-.488-.83-.687a1.012 1.012 0 010-.639C16.64 10.51 16.64 12.49 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;

  toggle.innerHTML = type === "password" ? eyeOpen : eyeClosed;
}

confirmPasswordToggle.addEventListener("click", () =>
  togglePasswordVisibility(
    document.getElementById("confirmPassword"),
    confirmPasswordToggle
  )
);
currentPasswordToggle.addEventListener("click", () =>
  togglePasswordVisibility(
    document.getElementById("currentPassword"),
    currentPasswordToggle
  )
);
newPasswordToggle.addEventListener("click", () =>
  togglePasswordVisibility(
    document.getElementById("newPassword"),
    newPasswordToggle
  )
);

// Change password with validation
async function changePassword() {
  const currentPassword = document.getElementById("confirmPassword").value;
  const newPassword = document.getElementById("currentPassword").value;
  const confirmPassword = document.getElementById("newPassword").value;

  if (!currentPassword || !newPassword || !confirmPassword) {
    showStatus("All password fields are required.", "error");
    return;
  }
  if (newPassword !== confirmPassword) {
    showStatus("New passwords do not match.", "error");
    return;
  }
  if (newPassword.length < 8) {
    showStatus("Password must be at least 8 characters long.", "error");
    return;
  }
  if (!/\d/.test(newPassword)) {
    showStatus("Password must contain at least one number.", "error");
    return;
  }
  if (/[^a-zA-Z0-9]/.test(newPassword)) {
    showStatus("Password cannot contain special characters.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("currentPassword", currentPassword);
  formData.append("newPassword", newPassword);

  try {
    const response = await fetch(`${API_BASE}?action=change_password`, {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);

    showStatus("Password changed successfully!", "success");
    document.getElementById("confirmPassword").value = "";
    document.getElementById("currentPassword").value = "";
    document.getElementById("newPassword").value = "";
  } catch (error) {
    console.error("Error changing password:", error);
    showStatus("Failed to change password: " + error.message, "error");
  }
}

// Show status message
function showStatus(message, type) {
  const statusDiv = document.getElementById("status-message");
  statusDiv.textContent = message;
  statusDiv.className = `status-message ${type}`;
  statusDiv.classList.add("show");
  setTimeout(() => {
    statusDiv.classList.remove("show");
  }, 3000);
}

// Load profile on page load
document.addEventListener("DOMContentLoaded", loadProfile);
