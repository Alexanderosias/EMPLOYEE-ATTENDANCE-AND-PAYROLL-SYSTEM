const API_BASE = '/eaaps/views/users_handler.php';

async function fetchUsers() {
  try {
    const response = await fetch(`${API_BASE}?action=list_users`);
    if (!response.ok) throw new Error('Failed to fetch users');
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    renderUsersTable(result.data);
  } catch (error) {
    console.error('Error fetching users:', error);
    document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="9" class="text-red-500">Failed to load users. Please try again.</td></tr>';
  }
}

function renderUsersTable(users) {
  const tbody = document.getElementById('usersTableBody');
  tbody.innerHTML = '';

  if (users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-3 text-center text-gray-500">No users found.</td></tr>';
    return;
  }

  users.forEach(user => {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';
    row.innerHTML = `
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.id}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.first_name} ${user.last_name}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.email}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.phone_number || 'N/A'}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.department_name || 'N/A'}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${user.role}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold ${user.is_active ? 'text-green-600' : 'text-red-600'}">${user.is_active ? 'Active' : 'Inactive'}</td>
      <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">${new Date(user.created_at).toLocaleDateString()}</td>
      <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
        <button class="text-blue-600 hover:text-blue-800 mr-2" onclick="editUser(${user.id})">Edit</button>
        <button class="text-red-600 hover:text-red-800" onclick="deleteUser(${user.id})">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// Modal elements for edit
const editUserModal = document.getElementById('edit-user-modal-overlay');
const editUserForm = document.getElementById('edit-user-form');
const editUserCancelBtn = document.getElementById('edit-user-cancel-btn');
const editDepartmentSelect = document.getElementById('edit-department');

async function editUser(id) {
  try {
    const response = await fetch(`${API_BASE}?action=get_user&id=${id}`);
    const result = await response.json();
    if (result.success) {
      const user = result.data;
      // Populate form
      document.getElementById('edit-first-name').value = user.first_name;
      document.getElementById('edit-last-name').value = user.last_name;
      document.getElementById('edit-email').value = user.email;
      document.getElementById('edit-phone').value = user.phone_number || '';
      document.getElementById('edit-address').value = user.address || '';
      document.getElementById('edit-role').value = user.role;
      // Set department
      await fetchDepartmentsForEdit();
      document.getElementById('edit-department').value = user.department_id || '';
      // Store ID for update
      editUserForm.dataset.userId = id;
      editUserModal.classList.add('active');
    } else {
      alert('Failed to load user data: ' + result.message);
    }
  } catch (error) {
    console.error('Error loading user:', error);
    alert('An error occurred while loading user data.');
  }
}

async function fetchDepartmentsForEdit() {
  try {
    const response = await fetch('/eaaps/views/departments_handler.php?action=list_departments');
    const result = await response.json();
    if (result.success) {
      editDepartmentSelect.innerHTML = '<option value="">Select Department</option>';
      result.data.forEach(dept => {
        editDepartmentSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
      });
    }
  } catch (error) {
    console.error('Error fetching departments for edit:', error);
  }
}

// Close edit modal
editUserCancelBtn.addEventListener('click', () => {
  editUserModal.classList.remove('active');
  editUserForm.reset();
});

editUserModal.addEventListener('click', (e) => {
  if (e.target === editUserModal) {
    editUserModal.classList.remove('active');
    editUserForm.reset();
  }
});

// Submit edit form
editUserForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const userId = editUserForm.dataset.userId;
  const formData = new FormData(editUserForm);

  try {
    const response = await fetch(`${API_BASE}?action=update_user&id=${userId}`, {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.success) {
      alert('User updated successfully!');
      editUserModal.classList.remove('active');
      editUserForm.reset();
      fetchUsers();  // Refresh table
    } else {
      alert('Failed to update user: ' + result.message);
    }
  } catch (error) {
    console.error('Error updating user:', error);
    alert('An error occurred while updating the user.');
  }
});

async function deleteUser(id) {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    try {
      const response = await fetch(`${API_BASE}?action=delete_user&id=${id}`, { method: 'DELETE' });
      const result = await response.json();
      if (result.success) {
        alert('User deleted successfully.');
        fetchUsers();  // Refresh table
      } else {
        alert('Failed to delete user: ' + result.message);
      }
    } catch (error) {
      console.error('Error deleting user:', error);
      alert('An error occurred while deleting the user.');
    }
  }
}

// Modal elements
const addUserModal = document.getElementById('add-user-modal-overlay');
const addUserForm = document.getElementById('add-user-form');
const addUserBtn = document.getElementById('addUserBtn');
const addUserCancelBtn = document.getElementById('add-user-cancel-btn');
const departmentSelect = document.getElementById('department');

// Fetch departments for the dropdown
async function fetchDepartments() {
  try {
    const response = await fetch('/eaaps/views/departments_handler.php?action=list_departments');  // Create this PHP file if needed
    const result = await response.json();
    if (result.success) {
      departmentSelect.innerHTML = '<option value="">Select Department</option>';
      result.data.forEach(dept => {
        departmentSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
      });
    }
  } catch (error) {
    console.error('Error fetching departments:', error);
  }
}

// Open modal
addUserBtn.addEventListener('click', () => {
  fetchDepartments();  // Populate departments
  addUserModal.classList.add('active');
});

// Close modal
addUserCancelBtn.addEventListener('click', () => {
  addUserModal.classList.remove('active');
  addUserForm.reset();
});

addUserModal.addEventListener('click', (e) => {
  if (e.target === addUserModal) {
    addUserModal.classList.remove('active');
    addUserForm.reset();
  }
});

// Submit form
addUserForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(addUserForm);

  try {
    const response = await fetch(`${API_BASE}?action=add_user`, {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.success) {
      alert('User added successfully!');
      addUserModal.classList.remove('active');
      addUserForm.reset();
      fetchUsers();  // Refresh table
    } else {
      alert('Failed to add user: ' + result.message);
    }
  } catch (error) {
    console.error('Error adding user:', error);
    alert('An error occurred while adding the user.');
  }
});

// Initialize
document.addEventListener('DOMContentLoaded', fetchUsers);