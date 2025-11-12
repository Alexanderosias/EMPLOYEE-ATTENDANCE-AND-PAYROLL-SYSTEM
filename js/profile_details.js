const BASE_PATH = ''; // Change for deployment
const API_BASE = BASE_PATH + '/views/profile_handler.php';

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
    document.getElementById('firstName').value = userData.first_name;
    document.getElementById('lastName').value = userData.last_name;
    document.getElementById('email').value = userData.email;
    document.getElementById('phone').value = userData.phone_number || '';
    document.getElementById('address').value = userData.address || '';
    document.getElementById('joinDate').value = new Date(userData.created_at).toISOString().split('T')[0];
    document.getElementById('department').value = userData.department_id || '';

    // Avatar
    const avatarSrc = userData.avatar_path ? '/' + userData.avatar_path : 'icons/profile-picture.png';
    document.getElementById('profileImage').src = avatarSrc;

    // Name and role
    document.getElementById('fullName').textContent = `${userData.first_name} ${userData.last_name}`;
    document.getElementById('roleDisplay').textContent = userData.role === 'head_admin' ? 'Head Administrator' : 'Administrator';

    // Populate department options
    await fetchDepartments();
  } catch (error) {
    console.error('Error loading profile:', error);
    showStatus('Failed to load profile.', 'error');
  }
}

// Fetch departments for dropdown
async function fetchDepartments() {
  try {
    const response = await fetch(BASE_PATH + '/views/departments_handler.php?action=list_departments');
    const result = await response.json();
    if (result.success) {
      const select = document.getElementById('department');
      select.innerHTML = '<option value="">Select Department</option>';
      result.data.forEach(dept => {
        select.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
      });
      select.value = userData.department_id || '';
    }
  } catch (error) {
    console.error('Error fetching departments:', error);
  }
}

// Edit Profile
function editProfile() {
  isEditMode = true;
  const profileCard = document.getElementById('profileCard');
  profileCard.classList.add('edit-mode');

  // Enable editing for all inputs except joinDate
  const inputs = document.querySelectorAll('#profileCard .form-group input:not(#joinDate), #profileCard .form-group select, #profileCard .form-group textarea');
  inputs.forEach(input => {
    input.removeAttribute('readonly');
    input.removeAttribute('disabled');
  });

  // Keep joinDate always readonly
  document.getElementById('joinDate').setAttribute('readonly', true);

  // Show upload overlay and enable avatar click
  document.querySelector('.upload-overlay').style.display = 'flex';
  document.getElementById('profileImage').style.cursor = 'pointer';

  // Update buttons
  document.querySelector('.btn-secondary').style.display = 'none';
  document.querySelector('.btn-primary').style.display = 'inline-flex';

  showStatus('Editing mode enabled.', 'success');
}

// Save Profile
async function saveProfile() {
  const formData = new FormData();
  formData.append('firstName', document.getElementById('firstName').value);
  formData.append('lastName', document.getElementById('lastName').value);
  formData.append('email', document.getElementById('email').value);
  formData.append('phone', document.getElementById('phone').value);
  formData.append('address', document.getElementById('address').value);
  formData.append('departmentId', document.getElementById('department').value);

  if (currentImageFile) {
    formData.append('avatar', currentImageFile);
  }

  try {
    const response = await fetch(`${API_BASE}?action=update_profile`, { method: 'POST', body: formData });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);

    // Update UI
    const profileCard = document.getElementById('profileCard');
    profileCard.classList.remove('edit-mode');

    const inputs = document.querySelectorAll('#profileCard .form-group input:not(#joinDate), #profileCard .form-group select, #profileCard .form-group textarea');
    inputs.forEach(input => {
      input.setAttribute('readonly', true);
      input.setAttribute('disabled', true);
    });

    document.getElementById('joinDate').setAttribute('readonly', true);
    document.querySelector('.upload-overlay').style.display = 'none';
    document.getElementById('profileImage').style.cursor = 'default';

    // Update name display
    document.getElementById('fullName').textContent = `${document.getElementById('firstName').value} ${document.getElementById('lastName').value}`;

    document.querySelector('.btn-secondary').style.display = 'inline-flex';
    document.querySelector('.btn-primary').style.display = 'none';

    showStatus('Profile updated successfully!', 'success');
    currentImageFile = null;
    isEditMode = false;

    // Reload profile data to reflect changes
    loadProfile();
  } catch (error) {
    console.error('Error saving profile:', error);
    showStatus('Failed to update profile: ' + error.message, 'error');
  }
}

// Trigger image upload
function triggerImageUpload() {
  if (!isEditMode) return;
  document.getElementById('imageUpload').click();
}

// Handle image upload
document.getElementById('imageUpload').addEventListener('change', function (event) {
  const file = event.target.files[0];
  if (file) {
    if (!file.type.startsWith('image/')) {
      showStatus('Please select a valid image file.', 'error');
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      showStatus('Image size must be less than 2MB.', 'error');
      return;
    }

    currentImageFile = file;
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById('profileImage').src = e.target.result;
      showStatus('Image selected. Click Save Changes to upload.', 'success');
    };
    reader.readAsDataURL(file);
  }
});

// Change password
async function changePassword() {
  const currentPassword = document.getElementById('confirmPassword').value;
  const newPassword = document.getElementById('currentPassword').value;
  const confirmPassword = document.getElementById('newPassword').value;

  if (!currentPassword || !newPassword || !confirmPassword) {
    showStatus('All password fields are required.', 'error');
    return;
  }
  if (newPassword !== confirmPassword) {
    showStatus('New passwords do not match.', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('currentPassword', currentPassword);
  formData.append('newPassword', newPassword);

  try {
    const response = await fetch(`${API_BASE}?action=change_password`, { method: 'POST', body: formData });
    const result = await response.json();
    if (!result.success) throw new Error(result.message);

    showStatus('Password changed successfully!', 'success');
    document.getElementById('confirmPassword').value = '';
    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value = '';
  } catch (error) {
    console.error('Error changing password:', error);
    showStatus('Failed to change password: ' + error.message, 'error');
  }
}

// Show status message
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

// Load profile on page load
document.addEventListener('DOMContentLoaded', loadProfile);
