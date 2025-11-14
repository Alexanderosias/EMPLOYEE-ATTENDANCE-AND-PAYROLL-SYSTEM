const BASE_PATH = ''; // Change to '' for localhost:8000, or '/newpath' for Hostinger
const API_BASE = BASE_PATH + '/views/users_handler.php';

// FETCH USERS
async function fetchUsers() {
  try {
    const response = await fetch(`${API_BASE}?action=list_users`);
    if (!response.ok) throw new Error('Failed to fetch users');
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    renderUsersTable(result.data);
  } catch (error) {
    console.error('Error fetching users:', error);
    document.getElementById('usersTableBody').innerHTML =
      '<tr><td colspan="9" class="text-red-500">Failed to load users. Please try again.</td></tr>';
  }
}

// RENDER USERS TABLE
function renderUsersTable(users) {
  const tbody = document.getElementById('usersTableBody');
  tbody.innerHTML = '';
  if (users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-3 text-center text-gray-500">No users found.</td></tr>';
    return;
  }
  users.forEach(user => {
    const isActive = Number(user.is_active) === 1;
    const avatarSrc = user.avatar_path ? '/' + user.avatar_path : 'img/user.jpg';
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';
    row.innerHTML = `
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
        <img src="${avatarSrc}" alt="Avatar" class="avatar-square" onerror="this.src='img/user.jpg'" />
      </td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.first_name} ${user.last_name}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.email}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.phone_number || 'N/A'}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.department_name || 'N/A'}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.role}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${isActive ? 'text-green-600' : 'text-red-600'}">
        ${isActive ? 'Active' : 'Inactive'}
      </td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${new Date(user.created_at).toLocaleDateString()}</td>
      <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
        <button class="text-blue-600 hover:text-blue-800 mr-2" onclick="editUser(${user.id})">Edit</button>
        <button class="text-red-600 hover:text-red-800" onclick="deleteUser(${user.id})">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// MODAL ELEMENTS
const editUserModal = document.getElementById('edit-user-modal-overlay');
const editUserForm = document.getElementById('edit-user-form');
const editUserCancelBtn = document.getElementById('edit-user-cancel-btn');
const editDepartmentSelect = document.getElementById('edit-department');

const addUserModal = document.getElementById('add-user-modal-overlay');
const addUserForm = document.getElementById('add-user-form');
const addUserBtn = document.getElementById('addUserBtn');
const addUserCancelBtn = document.getElementById('add-user-cancel-btn');
const departmentSelect = document.getElementById('department');

// Avatar handling for Add Modal
const addAvatarInput = document.getElementById('add-avatar-input');
const addAvatarPreview = document.getElementById('add-avatar-preview');
const addUploadAvatarBtn = document.getElementById('add-upload-avatar-btn');

addUploadAvatarBtn.addEventListener('click', () => {
  addAvatarInput.click();
});

addAvatarInput.addEventListener('change', () => {
  const file = addAvatarInput.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      addAvatarPreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    addAvatarPreview.src = 'img/user.jpg';
  }
});

// Avatar handling for Edit Modal
const editAvatarInput = document.getElementById('edit-avatar-input');
const editAvatarPreview = document.getElementById('edit-avatar-preview');
const editUploadAvatarBtn = document.getElementById('edit-upload-avatar-btn');

editUploadAvatarBtn.addEventListener('click', () => {
  editAvatarInput.click();
});

editAvatarInput.addEventListener('change', () => {
  const file = editAvatarInput.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      editAvatarPreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    editAvatarPreview.src = 'img/user.jpg';
  }
});

// FETCH DEPARTMENTS
async function fetchDepartments(selectElement) {
  try {
    const response = await fetch(BASE_PATH + '/views/departments_handler.php?action=list_departments');
    const result = await response.json();
    if (result.success) {
      selectElement.innerHTML = '<option value="">Select Department</option>';
      result.data.forEach(dept => {
        selectElement.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
      });
    }
  } catch (error) {
    console.error('Error fetching departments:', error);
  }
}

// EDIT USER
async function editUser(id) {
  try {
    const response = await fetch(`${API_BASE}?action=get_user&id=${id}`);
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    const user = result.data;
    document.getElementById('edit-first-name').value = user.first_name;
    document.getElementById('edit-last-name').value = user.last_name;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-phone').value = user.phone_number || '';
    document.getElementById('edit-address').value = user.address || '';
    document.getElementById('edit-role').value = user.role;

    const editCheckbox = document.getElementById('edit-is-active');
    const editDisplayInput = document.getElementById('edit-is-active-display');
    const isActive = Number(user.is_active) === 1;
    editCheckbox.checked = isActive;
    editDisplayInput.value = isActive ? 'Active' : 'Inactive';
    editCheckbox.onchange = () => {
      editDisplayInput.value = editCheckbox.checked ? 'Active' : 'Inactive';
    };

    await fetchDepartments(editDepartmentSelect);
    editDepartmentSelect.value = user.department_id || '';

    // Set avatar
    const avatarSrc = user.avatar_path ? BASE_PATH + '/' + user.avatar_path : 'img/user.jpg';
    editAvatarPreview.src = avatarSrc;

    editUserForm.dataset.userId = id;
    editUserModal.classList.add('active');
  } catch (error) {
    console.error('Error loading user:', error);
    showStatus('Failed to load user data.', 'error');
  }
}

// DELETE USER
async function deleteUser(id) {
  if (!confirm('Are you sure you want to delete this user?')) return;
  try {
    const response = await fetch(`${API_BASE}?action=delete_user&id=${id}`, { method: 'DELETE' });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    fetchUsers();
    showStatus('User deleted successfully.', 'success');
  } catch (error) {
    console.error(error);
    showStatus('Failed to delete user.', 'error');
  }
}

// ADD USER MODAL
addUserBtn.onclick = async () => {
  await fetchDepartments(departmentSelect);
  const addCheckbox = document.getElementById('add-is-active');
  const addDisplayInput = document.getElementById('add-is-active-display');
  addCheckbox.checked = true;
  addDisplayInput.value = 'Active';
  addCheckbox.onchange = () => {
    addDisplayInput.value = addCheckbox.checked ? 'Active' : 'Inactive';
  };
  addAvatarPreview.src = 'img/user.jpg';
  addUserModal.classList.add('active');
};

// Password toggle for Add Modal
const addPasswordToggle = document.getElementById('add-password-toggle');
const addPasswordInput = document.getElementById('add-password');

addPasswordToggle.addEventListener('click', () => {
  const type = addPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  addPasswordInput.setAttribute('type', type);

  const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
  const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.988 5.623a.9 4 4 0 010 .769 9.877 9.877 0 000 6.046c.381 2.385 1.503 4.266 3.048 5.485C9.37 19.262 10.9 19.5 12.067 19.5c1.167 0 2.697-.238 4.23-.782l.968-.34c.73-.243 1.408-.544 2.046-.902a1.012 1.012 0 00-.063-.035c-.158-.09-.313-.19-.462-.296l-1.07-1.1c-.26-.26-.54-.488-.83-.687a1.012 1.012 0 010-.639C16.64 10.51 16.64 12.49 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;

  addPasswordToggle.innerHTML = type === 'password' ? eyeOpen : eyeClosed;
});

// CLOSE MODALS
[editUserCancelBtn, addUserCancelBtn].forEach(btn => {
  btn.onclick = () => {
    editUserModal.classList.remove('active');
    addUserModal.classList.remove('active');
    editUserForm.reset();
    addUserForm.reset();
    addAvatarPreview.src = 'img/user.jpg';
    editAvatarPreview.src = 'img/user.jpg';
  };
});

[editUserModal, addUserModal].forEach(modal => {
  modal.onclick = e => {
    if (e.target === modal) {
      modal.classList.remove('active');
      editUserForm.reset();
      addUserForm.reset();
      addAvatarPreview.src = 'img/user.jpg';
      editAvatarPreview.src = 'img/user.jpg';
    }
  };
});

// SUBMIT ADD USER
addUserForm.onsubmit = async e => {
  e.preventDefault();

  // Password validation
  const passwordInput = document.getElementById('add-password');
  const password = passwordInput.value.trim();
  if (password.length < 8) {
    showStatus('Password must be at least 8 characters long.', 'error');
    return;
  }
  if (!/\d/.test(password)) {
    showStatus('Password must contain at least one number.', 'error');
    return;
  }
  if (/[^a-zA-Z0-9]/.test(password)) {
    showStatus('Password cannot contain special characters.', 'error');
    return;
  }

  const formData = new FormData(addUserForm);
  const addCheckbox = document.getElementById('add-is-active');
  formData.set('isActive', addCheckbox.checked ? 1 : 0);

  try {
    const response = await fetch(`${API_BASE}?action=add_user`, { method: 'POST', body: formData });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    showStatus('User added successfully.', 'success');
    addUserModal.classList.remove('active');
    addUserForm.reset();
    fetchUsers();
  } catch (error) {
    console.error(error);
    showStatus('Failed to add user.', 'error');
  }
};

// SUBMIT EDIT USER
editUserForm.onsubmit = async e => {
  e.preventDefault();
  const userId = editUserForm.dataset.userId;
  const formData = new FormData(editUserForm);
  const editCheckbox = document.getElementById('edit-is-active');
  formData.set('isActive', editCheckbox.checked ? 1 : 0);

  try {
    const response = await fetch(`${API_BASE}?action=update_user&id=${userId}`, { method: 'POST', body: formData });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    showStatus('User updated successfully.', 'success');
    editUserModal.classList.remove('active');
    editUserForm.reset();
    fetchUsers();  // Refresh table to show updated avatar
  } catch (error) {
    console.error('Update error:', error);
    showStatus('Failed to update user: ' + error.message, 'error');
  }
};

// SHOW STATUS MESSAGE
function showStatus(message, type) {
  const statusDiv = document.getElementById('status-message');
  statusDiv.textContent = message;
  statusDiv.className = `status-message ${type}`;
  statusDiv.style.display = 'block';
  setTimeout(() => {
    statusDiv.style.display = 'none';
    statusDiv.className = 'status-message';
  }, 5000);
}

// INIT
document.addEventListener('DOMContentLoaded', fetchUsers);