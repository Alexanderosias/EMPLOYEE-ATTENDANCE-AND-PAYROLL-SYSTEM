const BASE_PATH = ""; // Change to '' for localhost:8000, or '/newpath' for Hostinger
const API_BASE = BASE_PATH + "/views/users_handler.php";

// FETCH USERS
let currentEntriesLimit = 5; // Default to 5
// FETCH USERS
async function fetchUsers() {
  try {
    const response = await fetch(`${API_BASE}?action=list_users`);
    if (!response.ok) throw new Error("Failed to fetch users");
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    renderUsersTable(result.data, currentEntriesLimit);
  } catch (error) {
    console.error("Error fetching users:", error);
    document.getElementById("usersTableBody").innerHTML =
      '<tr><td colspan="9" class="text-red-500">Failed to load users. Please try again.</td></tr>';
  }
}

let currentPage = 1;
let totalPages = 1;
// RENDER USERS TABLE
function renderUsersTable(users, limit) {
  console.log("Rendering users:", users); // Debug log
  const tbody = document.getElementById("usersTableBody");
  tbody.innerHTML = "";
  totalPages = Math.ceil(users.length / limit);
  const startIndex = (currentPage - 1) * limit;
  const endIndex = startIndex + limit;
  const paginatedUsers = users.slice(startIndex, endIndex);

  if (paginatedUsers.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="9" class="px-6 py-3 text-center text-gray-500">No users found.</td></tr>';
    updatePagination();
    return;
  }

  paginatedUsers.forEach((user) => {
    console.log("User roles_json:", user.roles_json); // Debug log
    const isActive = Number(user.is_active) === 1;
    const avatarSrc = user.avatar_path
      ? "/" + user.avatar_path
      : "img/user.jpg";

    // Parse and format roles
    let roles = [];
    try {
      roles = JSON.parse(user.roles_json || "[]");
    } catch (e) {
      console.error("Error parsing roles_json:", e);
      roles = [];
    }
    const roleMap = {
      employee: "Employee",
      admin: "Administrator",
      head_admin: "Head Administrator",
    };
    const roleDisplay =
      roles.length > 0 ? roles.map((r) => roleMap[r] || r).join(" & ") : "N/A";

    const row = document.createElement("tr");
    row.className = "hover:bg-gray-50";
    row.innerHTML = `
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
        <img src="${avatarSrc}" alt="Avatar" class="avatar-square" onerror="this.src='img/user.jpg'" />
      </td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
        user.first_name
      } ${user.last_name}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
        user.email
      }</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
        user.phone_number || "N/A"
      }</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${
        user.department_name || "N/A"
      }</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${roleDisplay}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${
        isActive ? "text-green-600" : "text-red-600"
      }">
        ${isActive ? "Active" : "Inactive"}
      </td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${new Date(
        user.created_at
      ).toLocaleDateString()}</td>
      <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
        <button class="text-blue-600 hover:text-blue-800 mr-2" onclick="editUser(${
          user.id
        })">Edit</button>
        <button class="text-red-600 hover:text-red-800" onclick="deleteUser(${
          user.id
        })">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });

  updatePagination();
}

// UPDATE PAGINATION
function updatePagination() {
  const pageInfo = document.getElementById("page-info");
  const prevBtn = document.getElementById("prev-page");
  const nextBtn = document.getElementById("next-page");
  pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
  prevBtn.disabled = currentPage === 1;
  nextBtn.disabled = currentPage === totalPages;
}

// PAGINATION EVENT LISTENERS
document.getElementById("prev-page").addEventListener("click", () => {
  if (currentPage > 1) {
    currentPage--;
    fetchUsers();
  }
});

document.getElementById("next-page").addEventListener("click", () => {
  if (currentPage < totalPages) {
    currentPage++;
    fetchUsers();
  }
});

// RESET PAGE ON LIMIT CHANGE
document.getElementById("entries-select").addEventListener("change", (e) => {
  currentEntriesLimit = parseInt(e.target.value);
  currentPage = 1; // Reset to page 1
  fetchUsers();
});

// MODAL ELEMENTS
const editUserModal = document.getElementById("edit-user-modal-overlay");
const editUserForm = document.getElementById("edit-user-form");
const editUserCancelBtn = document.getElementById("edit-user-cancel-btn");
const editDepartmentSelect = document.getElementById("edit-department");

const addUserModal = document.getElementById("add-user-modal-overlay");
const addUserForm = document.getElementById("add-user-form");
const addUserBtn = document.getElementById("addUserBtn");
const addUserCancelBtn = document.getElementById("add-user-cancel-btn");
const departmentSelect = document.getElementById("department");

// Avatar handling for Add Modal
const addAvatarInput = document.getElementById("add-avatar-input");
const addAvatarPreview = document.getElementById("add-avatar-preview");
const addUploadAvatarBtn = document.getElementById("add-upload-avatar-btn");

addUploadAvatarBtn.addEventListener("click", () => {
  addAvatarInput.click();
});

addAvatarInput.addEventListener("change", () => {
  const file = addAvatarInput.files[0];
  if (file && file.type.startsWith("image/")) {
    const reader = new FileReader();
    reader.onload = (e) => {
      addAvatarPreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    addAvatarPreview.src = "img/user.jpg";
  }
});

// Avatar handling for Edit Modal
const editAvatarInput = document.getElementById("edit-avatar-input");
const editAvatarPreview = document.getElementById("edit-avatar-preview");
const editUploadAvatarBtn = document.getElementById("edit-upload-avatar-btn");

editUploadAvatarBtn.addEventListener("click", () => {
  editAvatarInput.click();
});

editAvatarInput.addEventListener("change", () => {
  const file = editAvatarInput.files[0];
  if (file && file.type.startsWith("image/")) {
    const reader = new FileReader();
    reader.onload = (e) => {
      editAvatarPreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    editAvatarPreview.src = "img/user.jpg";
  }
});

// FETCH DEPARTMENTS
async function fetchDepartments(selectElement) {
  try {
    const response = await fetch(
      BASE_PATH + "/views/departments_handler.php?action=list_departments"
    );
    const result = await response.json();
    if (result.success) {
      selectElement.innerHTML = '<option value="">Select Department</option>';
      result.data.forEach((dept) => {
        selectElement.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
      });
    }
  } catch (error) {
    console.error("Error fetching departments:", error);
  }
}

// EDIT USER
async function editUser(id) {
  try {
    const response = await fetch(`${API_BASE}?action=get_user&id=${id}`);
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    const user = result.data;

    // Add null checks for all elements
    const firstNameElem = document.getElementById("edit-first-name");
    if (firstNameElem) firstNameElem.value = user.first_name || "";
    const lastNameElem = document.getElementById("edit-last-name");
    if (lastNameElem) lastNameElem.value = user.last_name || "";
    const emailElem = document.getElementById("edit-email");
    if (emailElem) emailElem.value = user.email || "";
    const phoneElem = document.getElementById("edit-phone");
    if (phoneElem) phoneElem.value = user.phone_number || "";
    const addressElem = document.getElementById("edit-address");
    if (addressElem) addressElem.value = user.address || "";
    const roleElem = document.getElementById("edit-role");
    if (roleElem) roleElem.value = user.role || "admin"; // Use the extracted role

    const editCheckbox = document.getElementById("edit-is-active");
    const editDisplayInput = document.getElementById("edit-is-active-display");
    if (editCheckbox && editDisplayInput) {
      const isActive = Number(user.is_active) === 1;
      editCheckbox.checked = isActive;
      editDisplayInput.value = isActive ? "Active" : "Inactive";
      editCheckbox.onchange = () => {
        editDisplayInput.value = editCheckbox.checked ? "Active" : "Inactive";
      };
    }

    await fetchDepartments(editDepartmentSelect);
    if (editDepartmentSelect)
      editDepartmentSelect.value = user.department_id || "";

    // Populate roles (from JSON)
    const userRoles = JSON.parse(user.roles || '["admin"]');
    document.querySelectorAll('input[name="edit-roles[]"]').forEach((cb) => {
      cb.checked = userRoles.includes(cb.value);
    });

    // Set avatar
    const avatarSrc = user.avatar_path
      ? BASE_PATH + "/" + user.avatar_path
      : "img/user.jpg";
    const avatarImg = document.getElementById("edit-avatar-preview");
    if (avatarImg) avatarImg.src = avatarSrc;

    // Check if user is linked to an employee
    const isLinked = !!user.linked_employee_id;
    const warningMsgId = "edit-user-warning";
    let warningMsg = document.getElementById(warningMsgId);

    if (isLinked) {
      // Create warning if not exists
      if (!warningMsg) {
        warningMsg = document.createElement("div");
        warningMsg.id = warningMsgId;
        warningMsg.className =
          "bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4";
        warningMsg.innerHTML =
          "<p class='font-bold'>Note:</p><p>Personal details and Avatar cannot be edited here because this user is linked to an employee. Please edit in the Employees page.</p>";
        editUserForm.insertBefore(warningMsg, editUserForm.firstChild);
      }
      // Disable personal fields
      if (firstNameElem) firstNameElem.disabled = true;
      if (lastNameElem) lastNameElem.disabled = true;
      if (emailElem) emailElem.disabled = true;
      if (phoneElem) phoneElem.disabled = true;
      if (addressElem) addressElem.disabled = true;
      if (editDepartmentSelect) editDepartmentSelect.disabled = true;

      // Disable avatar input
      const avatarInput = document.getElementById("edit-avatar-input");
      if (avatarInput) avatarInput.disabled = true;

      // Enforce Employee Role
      const empRoleCb = document.querySelector(
        'input[name="edit-roles[]"][value="employee"]'
      );
      if (empRoleCb) {
        empRoleCb.checked = true;
        empRoleCb.disabled = true;
      }
    } else {
      // Remove warning if exists
      if (warningMsg) warningMsg.remove();
      // Enable fields
      if (firstNameElem) firstNameElem.disabled = false;
      if (lastNameElem) lastNameElem.disabled = false;
      if (emailElem) emailElem.disabled = false;
      if (phoneElem) phoneElem.disabled = false;
      if (addressElem) addressElem.disabled = false;
      if (editDepartmentSelect) editDepartmentSelect.disabled = false;

      // Enable avatar input
      const avatarInput = document.getElementById("edit-avatar-input");
      if (avatarInput) avatarInput.disabled = false;

      // NEW: For non-linked users, disable Employee role checkbox
      const empRoleCb = document.querySelector(
        'input[name="edit-roles[]"][value="employee"]'
      );
      if (empRoleCb) {
        empRoleCb.disabled = true;
        empRoleCb.checked = false; // Ensure it's unchecked
      }

      // NEW: Enforce that at least one of Admin or Head Admin is selected
      const adminCb = document.querySelector(
        'input[name="edit-roles[]"][value="admin"]'
      );
      const headAdminCb = document.querySelector(
        'input[name="edit-roles[]"][value="head_admin"]'
      );

      const enforceAtLeastOneAdmin = () => {
        const adminChecked = adminCb.checked;
        const headAdminChecked = headAdminCb.checked;
        if (!adminChecked && !headAdminChecked) {
          // If both are unchecked, check Admin by default
          adminCb.checked = true;
        }
      };

      // Add event listeners to prevent both from being unchecked
      if (adminCb) {
        adminCb.addEventListener("change", enforceAtLeastOneAdmin);
      }
      if (headAdminCb) {
        headAdminCb.addEventListener("change", enforceAtLeastOneAdmin);
      }
    }

    editUserForm.dataset.userId = id;
    editUserModal.classList.add("active");
  } catch (error) {
    console.error("Error loading user:", error);
    showStatus("Failed to load user data.", "error");
  }
}

// DELETE USER
async function deleteUser(id) {
  const confirmed = await showConfirmation(
    "Are you sure you want to delete this user? This action cannot be undone.",
    "Delete User",
    "red"
  );

  if (!confirmed) return;

  if (id == currentUserId) {
    const selfDeleteConfirmed = await showConfirmation(
      "You are attempting to delete your own account. Are you certain you want to continue?",
      "Delete Account",
      "red"
    );

    if (!selfDeleteConfirmed) return;
  }

  try {
    const response = await fetch(`${API_BASE}?action=delete_user&id=${id}`, {
      method: "DELETE",
    });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    fetchUsers();
    showStatus("User deleted successfully.", "success");
  } catch (error) {
    console.error(error);
    showStatus("Failed to delete user: " + error.message, "error");
  }
}

// ADD USER MODAL
addUserBtn.onclick = async () => {
  await fetchDepartments(departmentSelect);
  const addCheckbox = document.getElementById("add-is-active");
  const addDisplayInput = document.getElementById("add-is-active-display");
  addCheckbox.checked = true;
  addDisplayInput.value = "Active";
  addCheckbox.onchange = () => {
    addDisplayInput.value = addCheckbox.checked ? "Active" : "Inactive";
  };
  addAvatarPreview.src = "img/user.jpg";
  addUserModal.classList.add("active");
  // Reset role checkboxes
  document
    .querySelectorAll('input[name="roles[]"]')
    .forEach((cb) => (cb.checked = false));
  document.querySelector('input[name="roles[]"][value="admin"]').checked = true; // Default

  // Disable employee role by default
  const empRoleCb = document.querySelector(
    'input[name="roles[]"][value="employee"]'
  );
  if (empRoleCb) empRoleCb.disabled = true;
};

// Email change handler for add modal
document.getElementById("email").addEventListener("blur", async () => {
  const email = document.getElementById("email").value.trim();
  if (!email) return;
  try {
    const response = await fetch(
      `${BASE_PATH}/views/employees.php?action=check_email&email=${encodeURIComponent(
        email
      )}`
    );
    const result = await response.json();

    if (result.user_exists) {
      showStatus("A user account with this email already exists.", "error");
      document.getElementById("addUserBtn").disabled = true; // Prevent submission
    } else {
      document.getElementById("addUserBtn").disabled = false;
    }

    const employeeExists = result.exists;
    const roleCheckboxes = document.querySelectorAll('input[name="roles[]"]');
    const employeeCb = document.querySelector(
      'input[name="roles[]"][value="employee"]'
    );
    const adminCb = document.querySelector(
      'input[name="roles[]"][value="admin"]'
    );
    const headAdminCb = document.querySelector(
      'input[name="roles[]"][value="head_admin"]'
    );

    if (employeeExists) {
      // Auto-populate fields
      const emp = result.data;
      if (emp) {
        document.getElementById("first-name").value = emp.first_name || "";
        document.getElementById("last-name").value = emp.last_name || "";
        document.getElementById("phone").value = emp.contact_number || "";
        document.getElementById("address").value = emp.address || "";
        // Set department if available
        const deptSelect = document.getElementById("department");
        if (deptSelect && emp.department_id) {
          deptSelect.value = emp.department_id;
        }
      }

      // Linked: Force Employee role
      employeeCb.checked = true;
      employeeCb.disabled = true; // Cannot be unchecked
      adminCb.disabled = false;
      headAdminCb.disabled = false;
    } else {
      // Not Linked: Disallow employee. Allow admin OR head_admin.
      employeeCb.disabled = true;
      employeeCb.checked = false;
      adminCb.disabled = false;
      headAdminCb.disabled = false;
    }
  } catch (error) {
    console.error("Error checking email:", error);
  }
});

// Enforce mutual exclusivity for Admin/Head Admin (Global)
const enforceMutualExclusivity = (checkboxName) => {
  document.querySelectorAll(`input[name="${checkboxName}"]`).forEach((cb) => {
    cb.addEventListener("change", () => {
      const adminCb = document.querySelector(
        `input[name="${checkboxName}"][value="admin"]`
      );
      const headAdminCb = document.querySelector(
        `input[name="${checkboxName}"][value="head_admin"]`
      );

      if (cb === adminCb && adminCb.checked) headAdminCb.checked = false;
      if (cb === headAdminCb && headAdminCb.checked) adminCb.checked = false;
    });
  });
};

enforceMutualExclusivity("roles[]");
enforceMutualExclusivity("edit-roles[]");

// Password toggle for Add Modal
const addPasswordToggle = document.getElementById("add-password-toggle");
const addPasswordInput = document.getElementById("add-password");

addPasswordToggle.addEventListener("click", () => {
  const type =
    addPasswordInput.getAttribute("type") === "password" ? "text" : "password";
  addPasswordInput.setAttribute("type", type);

  const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
  const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.988 5.623a.9 4 4 0 010 .769 9.877 9.877 0 000 6.046c.381 2.385 1.503 4.266 3.048 5.485C9.37 19.262 10.9 19.5 12.067 19.5c1.167 0 2.697-.238 4.23-.782l.968-.34c.73-.243 1.408-.544 2.046-.902a1.012 1.012 0 00-.063-.035c-.158-.09-.313-.19-.462-.296l-1.07-1.1c-.26-.26-.54-.488-.83-.687a1.012 1.012 0 010-.639C16.64 10.51 16.64 12.49 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;

  addPasswordToggle.innerHTML = type === "password" ? eyeOpen : eyeClosed;
});

// CLOSE MODALS
[editUserCancelBtn, addUserCancelBtn].forEach((btn) => {
  btn.onclick = () => {
    editUserModal.classList.remove("active");
    addUserModal.classList.remove("active");
    editUserForm.reset();
    addUserForm.reset();
    addAvatarPreview.src = "img/user.jpg";
    editAvatarPreview.src = "img/user.jpg";
  };
});

[editUserModal, addUserModal].forEach((modal) => {
  modal.onclick = (e) => {
    if (e.target === modal) {
      modal.classList.remove("active");
      editUserForm.reset();
      addUserForm.reset();
      addAvatarPreview.src = "img/user.jpg";
      editAvatarPreview.src = "img/user.jpg";
    }
  };
});

// SUBMIT ADD USER
addUserForm.onsubmit = async (e) => {
  e.preventDefault();

  // Password validation
  const passwordInput = document.getElementById("add-password");
  const password = passwordInput.value.trim();
  if (password.length < 8) {
    showStatus("Password must be at least 8 characters long.", "error");
    return;
  }
  if (!/\d/.test(password)) {
    showStatus("Password must contain at least one number.", "error");
    return;
  }
  if (/[^a-zA-Z0-9]/.test(password)) {
    showStatus("Password cannot contain special characters.", "error");
    return;
  }

  const formData = new FormData(addUserForm);
  const selectedRoles = Array.from(
    document.querySelectorAll('input[name="roles[]"]:checked')
  ).map((cb) => cb.value);

  if (selectedRoles.length === 0) {
    showStatus("Please select a role.", "error");
    return;
  }

  formData.set("roles", JSON.stringify(selectedRoles));
  formData.set(
    "isActive",
    document.getElementById("add-is-active").checked ? 1 : 0
  );

  try {
    const response = await fetch(`${API_BASE}?action=add_user`, {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    showStatus("User added successfully.", "success");
    addUserModal.classList.remove("active");
    addUserForm.reset();
    setTimeout(() => location.reload(), 1000);
  } catch (error) {
    console.error(error);
    showStatus(error.message || "Failed to add user.", "error");
  }
};

// SUBMIT EDIT USER
editUserForm.onsubmit = async (e) => {
  e.preventDefault();
  const userId = editUserForm.dataset.userId;
  const formData = new FormData(editUserForm);
  const selectedRoles = Array.from(
    document.querySelectorAll('input[name="edit-roles[]"]:checked')
  ).map((cb) => cb.value);
  formData.set("roles", JSON.stringify(selectedRoles));
  formData.set(
    "isActive",
    document.getElementById("edit-is-active").checked ? 1 : 0
  );

  try {
    const response = await fetch(
      `${API_BASE}?action=update_user&id=${userId}`,
      { method: "POST", body: formData }
    );
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    showStatus("User updated successfully.", "success");
    editUserModal.classList.remove("active");
    editUserForm.reset();
    setTimeout(() => location.reload(), 1000);
  } catch (error) {
    console.error("Update error:", error);
    showStatus("Failed to update user: " + error.message, "error");
  }
};

// SHOW STATUS MESSAGE
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

// INIT
document.addEventListener("DOMContentLoaded", () => {
  fetchUsers();
  // Add event listener for entries select
  document.getElementById("entries-select").addEventListener("change", (e) => {
    currentEntriesLimit = parseInt(e.target.value);
    fetchUsers(); // Re-fetch and re-render with new limit
  });
});
