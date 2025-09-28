document.addEventListener('DOMContentLoaded', () => {
  // FIXED: Encoded paths for spaces (recommend renaming folder to avoid %20)
  const API_BASE = '/EMPLOYEE%20ATTENDANCE%20AND%20PAYROLL%20SYSTEM/views/employees.php';
  const BASE_PATH = '/EMPLOYEE%20ATTENDANCE%20AND%20PAYROLL%20SYSTEM/';

  // NEW: Simple toast notification (replaces alert for better UX, including sync messages)
  function showToast(message, type = 'info') {
    // Create toast element if not exists
    let toast = document.getElementById('toast-notification');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast-notification';
      toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 12px 20px; 
        background: #333; color: white; border-radius: 4px; z-index: 1000; 
        display: none; min-width: 300px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        font-size: 14px; text-align: center;
      `;
      document.body.appendChild(toast);
    }

    // Set message and type (success: green, error: red, info: gray)
    toast.textContent = message;
    toast.style.background = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#333';
    toast.style.display = 'block';

    // Auto-hide after 4s (longer for sync messages)
    setTimeout(() => { toast.style.display = 'none'; }, 4000);
  }

  // NEW: Check and sync pending operations (call on load/online)
  async function checkAndSyncPending() {
    if (navigator.onLine) {
      try {
        const response = await fetch(`${API_BASE}?action=sync_pending`);
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        const result = await response.json();
        if (result.success) {
          if (result.data.synced > 0) {
            showToast(result.message, 'success');  // e.g., "Pending sync completed: 2 synced..."
          }
          // If all good, no toast for 0 synced
        } else {
          console.warn('Sync pending failed:', result.message);
          // Don't toast warnings – silent retry
        }
      } catch (error) {
        console.error('Sync pending error:', error);
        // Silent: Will retry on next online event
      }
    }
  }

  // NEW: Offline/online event listeners
  window.addEventListener('online', () => {
    showToast('Back online – syncing changes...', 'info');
    checkAndSyncPending();
  });
  window.addEventListener('offline', () => {
    showToast('Offline mode: Changes saved locally', 'info');
  });

  // Existing: Update datetime
  function updateDateTime() {
    const now = new Date();
    const options = {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    };
    document.getElementById('current-datetime').textContent = now.toLocaleDateString('en-US', options);
  }
  updateDateTime();
  setInterval(updateDateTime, 60000);

  const employeeListContainer = document.getElementById('employee-list-container');

  // Elements for filtering
  const searchInput = document.getElementById('search-input');
  const searchBtn = document.getElementById('search-btn');
  const filterDepartment = document.getElementById('filter-department');
  const filterJobPosition = document.getElementById('filter-job-position');

  // Global arrays for dynamic selects
  let departments = [];
  let jobPositions = [];

  // Helper function to get detail item text by label (searches both .detail-item and .expanded-item)
  function getDetailItemText(card, label) {
    const items = card.querySelectorAll('.detail-item, .expanded-item');
    for (const item of items) {
      if (item.textContent.trim().startsWith(label)) {
        return item.textContent;
      }
    }
    return '';
  }

  // Format phone for display (add spaces: 09305909175 -> 093 0590 9175)
  function formatPhoneForDisplay(cleanPhone) {
    if (!cleanPhone || typeof cleanPhone !== 'string') return '';
    const digits = cleanPhone.replace(/\D/g, '');  // Extract digits only
    if (digits.length !== 11) return cleanPhone;  // Fallback if invalid
    return `${digits.slice(0, 3)} ${digits.slice(3, 7)} ${digits.slice(7, 11)}`;
  }

  // Clean phone: Remove all non-digits for DB save
  function cleanPhoneNumber(phone) {
    return phone.replace(/\D/g, '');  // Keep only digits
  }

  // Auto-format phone input as user types (for contact_number and emergency_phone)
  function autoFormatPhoneInput(input) {
    let value = input.value.replace(/\s/g, '');  // Remove existing spaces
    let cursorPos = input.selectionStart;

    // Add first space after 3 digits
    if (value.length > 3) {
      value = value.slice(0, 3) + ' ' + value.slice(3);
      cursorPos++;  // Adjust for added space
    }

    // Add second space after 7 digits (3 + space + 4)
    if (value.length > 8) {  // 3 digits + space + 4 digits = 8 chars
      value = value.slice(0, 8) + ' ' + value.slice(8);
      if (cursorPos > 8) cursorPos++;  // Adjust if cursor after first space
    }

    // Limit to 11 digits
    const digitsOnly = value.replace(/\D/g, '').slice(0, 11);
    value = digitsOnly.slice(0, 3);
    if (digitsOnly.length > 3) value += ' ' + digitsOnly.slice(3, 7);
    if (digitsOnly.length > 7) value += ' ' + digitsOnly.slice(7, 11);

    input.value = value;

    // Restore cursor position (approximate)
    const newCursor = Math.min(cursorPos, input.value.length);
    input.setSelectionRange(newCursor, newCursor);
  }

  // Validate phone format (with spaces: 093 0590 9175)
  function validatePhoneFormat(phone) {
    const phoneRegex = /^\d{3} \d{4} \d{4}$/;
    return phoneRegex.test(phone);
  }

  // Fetch and populate departments and job positions for selects
  async function loadSelectOptions() {
    try {
      const [deptRes, posRes] = await Promise.all([
        fetch(`${API_BASE}?action=departments`),
        fetch(`${API_BASE}?action=positions`)
      ]);
      if (!deptRes.ok || !posRes.ok) throw new Error('Failed to load options');
      const deptData = await deptRes.json();
      const posData = await posRes.json();
      if (!deptData.success || !posData.success) throw new Error('Invalid options response');
      departments = deptData.data || [];
      jobPositions = posData.data || [];

      // Ensure arrays
      if (!Array.isArray(departments)) departments = [];
      if (!Array.isArray(jobPositions)) jobPositions = [];

      // Populate filter selects (use names for filtering)
      filterDepartment.innerHTML = '<option value="">All Departments</option>' + departments.map(d => `<option value="${d.name}">${d.name}</option>`).join('');
      filterJobPosition.innerHTML = '<option value="">All Job Positions</option>' + jobPositions.map(p => `<option value="${p.name}">${p.name}</option>`).join('');

      // Populate add modal selects (use IDs for submission)
      const addDeptSelect = document.getElementById('department');
      const addPosSelect = document.getElementById('job-position');
      addDeptSelect.innerHTML = '<option value="" disabled selected>Select department</option>' + departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
      addPosSelect.innerHTML = '<option value="" disabled selected>Select job position</option>' + jobPositions.map(p => `<option value="${p.id}">${p.name}</option>`).join('');

      // Populate update modal selects (same)
      const updateDeptSelect = document.getElementById('update-department');
      const updatePosSelect = document.getElementById('update-job-position');
      if (updateDeptSelect) updateDeptSelect.innerHTML = addDeptSelect.innerHTML;
      if (updatePosSelect) updatePosSelect.innerHTML = addPosSelect.innerHTML;
    } catch (error) {
      console.error('Error loading options:', error);
      showToast('Failed to load departments/positions. Please refresh.', 'error');
    }
  }

  // UPDATED: Fetch and render employees (added loading state & better error handling)
  async function fetchEmployees() {
    employeeListContainer.innerHTML = '<p style="text-align: center; color: #666;">Loading employees...</p>';  // Loading state

    try {
      const response = await fetch(`${API_BASE}?action=list_employees`);
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const responseData = await response.json();  // Full {success, data}
      console.log('Raw JSON response:', responseData);  // Debug: Full object
      if (!responseData || typeof responseData !== 'object') {
        throw new Error('Invalid response format: Not an object');
      }
      if (!responseData.success) {
        throw new Error(responseData.message || 'Failed to fetch employees');
      }
      const employees = responseData.data || [];  // Extract array
      console.log('Data type:', typeof employees);  // 'object' (array)
      console.log('Employees length:', employees.length);
      if (employees.length > 0) {
        console.log('First employee ID type:', typeof employees[0].id);  // Number or string
        console.log('Processing employee:', employees[0]);  // Log full object
      }
      if (!Array.isArray(employees)) {
        throw new Error('Invalid response format: Data is not an array');
      }
      renderEmployeeCards(employees);
    } catch (error) {
      console.error('Error fetching employees:', error);
      showToast('Failed to load employees: ' + error.message, 'error');
      employeeListContainer.innerHTML = '<p style="color: red; text-align: center;">Error loading employees. Please refresh.</p>';
    }
  }

  // UPDATED: Render employee cards (added type parsing; fixed avatar paths)
  function renderEmployeeCards(employees) {
    employeeListContainer.innerHTML = '';  // Clear existing
    if (employees.length === 0) {
      employeeListContainer.innerHTML = '<p style="text-align: center; color: #666;">No employees found.</p>';
      return;
    }
    employees.forEach((emp, index) => {
      // Parse types for safe display (handle string/numbers from PHP)
      const id = parseInt(emp.id, 10);
      const rate = parseFloat(emp.rate_per_hour) || 0;
      const paidLeave = parseInt(emp.annual_paid_leave_days, 10) || 15;
      const unpaidLeave = parseInt(emp.annual_unpaid_leave_days, 10) || 5;
      const sickLeave = parseInt(emp.annual_sick_leave_days, 10) || 10;

      // Determine status class for coloring
      let statusClass = '';
      const status = emp.status || 'Active';
      if (status === 'On Leave') {
        statusClass = 'status-on-leave';
      } else if (status === 'Active') {
        statusClass = 'status-active';
      } else if (status === 'Inactive') {
        statusClass = 'status-inactive';
      }

      // Format contact phone for display
      const formattedContact = formatPhoneForDisplay(emp.contact_number || '');

      // Format emergency: Name (Relationship) - Phone
      let formattedEmergency = 'N/A';
      if (emp.emergency_contact_name || emp.emergency_contact_relationship || emp.emergency_contact_phone) {
        const name = emp.emergency_contact_name || '';
        const rel = emp.emergency_contact_relationship ? `(${emp.emergency_contact_relationship})` : '';
        const phone = formatPhoneForDisplay(emp.emergency_contact_phone || '');
        formattedEmergency = `${name} ${rel} - ${phone}`.trim();
      }

      const card = document.createElement('div');
      card.className = 'employee-card minimized';
      card.setAttribute('data-id', id);
      // FIXED: Avatar path - Absolute from domain root (matches API_BASE and your layout: root/uploads/)
      const avatarSrc = emp.avatar_path ? BASE_PATH + emp.avatar_path : BASE_PATH + 'img/user.jpg';
      card.innerHTML = `
        <div class="card-index">${index + 1}</div>
        <div class="image-container employee-avatar">
          <img src="${avatarSrc}" alt="Employee Photo" class="employee-photo" onerror="this.src='${BASE_PATH}img/user.jpg'" />
        </div>
        <div class="employee-details">
          <p class="detail-item"><strong>Name:</strong> ${emp.first_name || ''} ${emp.last_name || ''}</p>
          <p class="detail-item"><strong>Job Position:</strong> ${emp.position_name || 'N/A'}</p>
          <p class="detail-item"><strong>Department:</strong> ${emp.department_name || 'N/A'}</p>
          <p class="expanded-item ${statusClass}"><strong>Status:</strong> ${status}</p>
          <div class="expanded-details" aria-hidden="true">
            <p class="expanded-item"><strong>Gender:</strong> ${emp.gender || 'N/A'}</p>
            <p class="expanded-item"><strong>Marital Status:</strong> ${emp.marital_status || 'Single'}</p>
            <p class="detail-item"><strong>Address:</strong> ${emp.address || 'N/A'}</p>
            <p class="expanded-item"><strong>Email Address:</strong> ${emp.email || ''}</p>
            <p class="expanded-item"><strong>Rate per Hour:</strong> ₱${rate.toFixed(2)}</p>
            <p class="expanded-item"><strong>Contact Number:</strong> ${formattedContact}</p>
            <p class="expanded-item"><strong>Emergency Contact:</strong> ${formattedEmergency}</p>
            <p class="expanded-item"><strong>Annual Paid Leave Days:</strong> ${paidLeave}</p>
            <p class="expanded-item"><strong>Annual Unpaid Leave Days:</strong> ${unpaidLeave}</p>
            <p class="expanded-item"><strong>Annual Sick Leave Days:</strong> ${sickLeave}</p>
            <p class="expanded-item"><strong>Date Joined:</strong> ${emp.date_joined || 'N/A'}</p>
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

  // UPDATED: Open update modal (fixed relative avatar path for preview; added focus)
  function openUpdateModal(employeeData) {
    const updateModal = document.getElementById('update-employee-modal');
    updateModal.setAttribute('aria-hidden', 'false');

    // Set hidden ID field
    const idInput = document.getElementById('update-employee-id');
    if (idInput) idInput.value = employeeData.id || '';

    // Prefill text fields (no status/date_joined)
    document.getElementById('update-first-name').value = employeeData.first_name || '';
    document.getElementById('update-last-name').value = employeeData.last_name || '';
    document.getElementById('update-address').value = employeeData.address || '';
    document.getElementById('update-email').value = employeeData.email || '';
    document.getElementById('update-contact-number').value = formatPhoneForDisplay(employeeData.contact_number || '') || '';
    document.getElementById('update-rate-per-hour').value = parseFloat(employeeData.rate_per_hour) || 0.00;

    // Set selects (no status; include marital)
    document.getElementById('update-gender').value = employeeData.gender || '';
    document.getElementById('update-marital-status').value = employeeData.marital_status || 'Single';
    document.getElementById('update-department').value = employeeData.department_id || '';
    document.getElementById('update-job-position').value = employeeData.job_position_id || '';

    // Set numbers for leave days
    document.getElementById('update-annual-paid-leave-days').value = parseInt(employeeData.annual_paid_leave_days, 10) || 15;
    document.getElementById('update-annual-unpaid-leave-days').value = parseInt(employeeData.annual_unpaid_leave_days, 10) || 5;
    document.getElementById('update-annual-sick-leave-days').value = parseInt(employeeData.annual_sick_leave_days, 10) || 10;

    // Set new emergency fields
    document.getElementById('update-emergency-name').value = employeeData.emergency_contact_name || '';
    document.getElementById('update-emergency-phone').value = formatPhoneForDisplay(employeeData.emergency_contact_phone || '') || '';
    document.getElementById('update-emergency-relationship').value = employeeData.emergency_contact_relationship || '';

    // FIXED: Avatar preview - Relative path to show current employee avatar (fallback to default)
    const previewImg = document.getElementById('update-avatar-preview-img');  // Ensure element exists
    if (!previewImg) {
      console.error('Update avatar preview img not found!');
      return;
    }

    let avatarSrc;
    if (employeeData.avatar_path && employeeData.avatar_path.trim() !== '') {
      // PRIORITY: Relative path (matches your root layout: uploads/ at same level as img/)
      avatarSrc = employeeData.avatar_path;  // e.g., 'uploads/avatars/emp_123.jpg'
      // ALTERNATIVE: If relative fails, use absolute: avatarSrc = BASE_PATH + employeeData.avatar_path;
    } else {
      avatarSrc = 'img/user.jpg';  // Relative default (works as per your HTML)
    }

    // Clear current src to avoid cache issues, then set new
    previewImg.src = '';  // Reset
    previewImg.alt = 'Loading employee avatar preview...';

    // Set the src after a tiny delay (ensures DOM/modal is fully visible)
    setTimeout(() => {
      previewImg.src = avatarSrc;
      previewImg.alt = 'Employee avatar preview';
    }, 50);  // 50ms delay - minimal, prevents race conditions

    // DEBUG: Log paths (check Console when opening modal; remove after testing)
    console.log('Update Modal: Setting avatar src to:', avatarSrc);
    console.log('Update Modal: DB avatar_path:', employeeData.avatar_path);
    console.log('Update Modal: Employee ID:', employeeData.id);

    // Add onerror handler: Fallback if current avatar fails to load (prevents broken image)
    previewImg.onerror = function () {
      console.warn('Update avatar preview failed to load (ID:', employeeData.id, '), falling back to default');
      this.src = 'img/user.jpg';  // Relative fallback
      this.alt = 'Default avatar preview';
    };

    // Optional: On successful load, log and clean up handler
    previewImg.onload = function () {
      console.log('Update avatar preview loaded successfully for ID:', employeeData.id);
      // Handler auto-removes after load
    };

    // Focus on first input
    const updateEmployeeForm = document.getElementById('update-employee-form');
    updateEmployeeForm.querySelector('input, select').focus();
  }

  // Filtering function (client-side on rendered cards)
  function filterEmployees() {
    const searchTerm = searchInput.value.toLowerCase();
    const departmentFilter = filterDepartment.value;
    const jobPositionFilter = filterJobPosition.value;

    const employeeCards = employeeListContainer.querySelectorAll('.employee-card');

    employeeCards.forEach(card => {
      const nameText = getDetailItemText(card, 'Name:').toLowerCase();
      const departmentText = getDetailItemText(card, 'Department:');
      const jobPositionText = getDetailItemText(card, 'Job Position:');

      const name = nameText.split(':')[1]?.trim() || '';
      const department = departmentText.split(':')[1]?.trim() || '';
      const jobPosition = jobPositionText.split(':')[1]?.trim() || '';

      const matchesSearch = name.includes(searchTerm);
      const matchesDepartment = !departmentFilter || department === departmentFilter;
      const matchesJobPosition = !jobPositionFilter || jobPosition === jobPositionFilter;

      if (matchesSearch && matchesDepartment && matchesJobPosition) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }

  // Event listeners for filtering
  searchBtn.addEventListener('click', filterEmployees);
  searchInput.addEventListener('input', filterEmployees);
  filterDepartment.addEventListener('change', filterEmployees);
  filterJobPosition.addEventListener('change', filterEmployees);

  // Employee card show more toggle with image icon swap
  employeeListContainer.addEventListener('click', (event) => {
    const showMoreBtn = event.target.closest('.action-btn-show-more');
    if (showMoreBtn) {
      const employeeCard = showMoreBtn.closest('.employee-card');
      const expandedDetails = employeeCard.querySelector('.expanded-details');
      const iconImg = showMoreBtn.querySelector('img');

      if (expandedDetails.classList.contains('visible')) {
        expandedDetails.classList.remove('visible');
        expandedDetails.setAttribute('aria-hidden', 'true');
        employeeCard.classList.add('minimized');
        if (iconImg) {
          iconImg.src = 'icons/down-arrow.png';
          iconImg.alt = 'Expand details';
        }
      } else {
        expandedDetails.classList.add('visible');
        expandedDetails.setAttribute('aria-hidden', 'false');
        employeeCard.classList.remove('minimized');
        if (iconImg) {
          iconImg.src = 'icons/up-arrow.png';
          iconImg.alt = 'Collapse details';
        }
      }
    }
  });

  // Update and Delete buttons
  employeeListContainer.addEventListener('click', async (event) => {
    if (event.target.closest('.action-btn-update')) {
      const employeeCard = event.target.closest('.employee-card');
      const employeeId = employeeCard.dataset.id;
      console.log("Update info clicked for ID:", employeeId);

      try {
        // Fetch full employee data for accurate IDs and values
        const response = await fetch(`${API_BASE}?action=get_employee&id=${employeeId}`);
        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}));
          throw new Error(errorData.message || 'Failed to fetch employee data');
        }
        const responseData = await response.json();
        if (!responseData.success) {
          throw new Error(responseData.message || 'Failed to fetch employee data');
        }
        openUpdateModal(responseData.data);
      } catch (error) {
        console.error('Error fetching employee for update:', error);
        showToast('Failed to load employee data for update: ' + error.message, 'error');
      }

    } else if (event.target.closest('.action-btn-delete')) {
      const employeeCard = event.target.closest('.employee-card');
      const employeeId = employeeCard.dataset.id;
      console.log("Delete clicked for ID:", employeeId);
      if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
        await deleteEmployee(employeeId);
      }
    }
  });

  // UPDATED: Delete employee function (with toast & error handling)
  async function deleteEmployee(id) {
    try {
      const formData = new FormData();
      formData.append('action', 'delete_employee');
      formData.append('id', id);

      const response = await fetch(API_BASE, {
        method: 'POST',
        body: formData
      });
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        showToast(result.message || 'Employee deleted successfully.', 'success');  // Includes sync status
        fetchEmployees();  // Refresh list
      } else {
        showToast('Error: ' + (result.message || 'Failed to delete employee'), 'error');
      }
    } catch (error) {
      console.error('Delete error:', error);
      showToast('Failed to delete employee: ' + error.message, 'error');
    }
  }

  // Add Employee Modal Logic - UPDATED for new emergency fields, loading, toast, validation
  const addModal = document.getElementById('add-employee-modal');
  const addEmployeeBtn = document.getElementById('add-employee-btn');
  const addCloseButtons = addModal.querySelectorAll('.modal-close-btn');
  const addAvatarInput = document.getElementById('avatar-input');
  const addAvatarPreviewImg = document.getElementById('avatar-preview-img');
  const addEmployeeForm = document.getElementById('add-employee-form');
  const addUploadImageBtn = document.getElementById('upload-image-btn');

  // Auto-format listeners for add modal phone inputs
  const addContactInput = document.getElementById('contact-number');
  const addEmergencyPhoneInput = document.getElementById('emergency-phone');
  if (addContactInput) addContactInput.addEventListener('input', () => autoFormatPhoneInput(addContactInput));
  if (addEmergencyPhoneInput) addEmergencyPhoneInput.addEventListener('input', () => autoFormatPhoneInput(addEmergencyPhoneInput));

  addEmployeeBtn.addEventListener('click', () => {
    addModal.setAttribute('aria-hidden', 'false');
    addEmployeeForm.reset();
    // Set defaults for optional/nullable fields (no status/date_joined - auto-handled by PHP)
    document.getElementById('marital-status').value = 'Single';
    document.getElementById('annual-paid-leave-days').value = 15;
    document.getElementById('annual-unpaid-leave-days').value = 5;
    document.getElementById('annual-sick-leave-days').value = 10;
    document.getElementById('rate-per-hour').value = 0.00;
    // Reset emergency fields to empty (required, user must fill)
    document.getElementById('emergency-name').value = '';
    document.getElementById('emergency-phone').value = '';
    document.getElementById('emergency-relationship').value = '';
    addAvatarPreviewImg.src = 'img/user.jpg';
    addAvatarPreviewImg.alt = 'Avatar preview';
    addEmployeeForm.querySelector('input, select').focus();
  });

  addCloseButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      addModal.setAttribute('aria-hidden', 'true');
    });
  });

  addModal.addEventListener('click', (e) => {
    if (e.target === addModal) {
      addModal.setAttribute('aria-hidden', 'true');
    }
  });

  addUploadImageBtn.addEventListener('click', () => {
    addAvatarInput.click();
  });

  addAvatarInput.addEventListener('change', () => {
    const file = addAvatarInput.files[0];
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = e => {
        addAvatarPreviewImg.src = e.target.result;
        addAvatarPreviewImg.alt = 'Selected avatar preview';
      };
      reader.readAsDataURL(file);
    } else {
      addAvatarPreviewImg.src = 'img/user.jpg';
      addAvatarPreviewImg.alt = 'Avatar preview';
    }
  });

  // UPDATED: Add form submit (loading state, validation, clean phones, toast)
  addEmployeeForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Client-side validation for NOT NULL fields (no status/date_joined - auto by PHP)
    const firstName = document.getElementById('first-name').value.trim();
    const lastName = document.getElementById('last-name').value.trim();
    const email = document.getElementById('email').value.trim();
    const address = document.getElementById('address').value.trim();
    const gender = document.getElementById('gender').value;
    const maritalStatus = document.getElementById('marital-status').value;
    const contactNumber = document.getElementById('contact-number').value.trim();
    const emergencyName = document.getElementById('emergency-name').value.trim();
    const emergencyPhone = document.getElementById('emergency-phone').value.trim();
    const emergencyRelationship = document.getElementById('emergency-relationship').value.trim();
    const departmentId = document.getElementById('department').value;
    const jobPositionId = document.getElementById('job-position').value;
    const ratePerHour = parseFloat(document.getElementById('rate-per-hour').value) || 0;

    if (!firstName || !lastName || !email || !address || !gender || !maritalStatus || !contactNumber || !emergencyName || !emergencyPhone || !emergencyRelationship || !departmentId || !jobPositionId || ratePerHour < 0) {
      showToast('Please fill all required fields correctly.', 'error');
      return;
    }
    if (!email.includes('@')) {
      showToast('Please enter a valid email address.', 'error');
      return;
    }
    // Phone format validation (with spaces)
    if (!validatePhoneFormat(contactNumber)) {
      showToast('09X XXXX XXXX', 'error');
      document.getElementById('contact-number').focus();
      return;
    }
    if (!validatePhoneFormat(emergencyPhone)) {
      showToast('09X XXXX XXXX', 'error');
      document.getElementById('emergency-phone').focus();
      return;
    }

    const formData = new FormData(addEmployeeForm);
    formData.append('action', 'add_employee');  // CRITICAL: Include action for PHP

    // Clean phones (remove spaces/non-digits) before sending - override to DB keys
    const cleanedContact = cleanPhoneNumber(contactNumber);
    const cleanedEmergencyPhone = cleanPhoneNumber(emergencyPhone);
    formData.set('contact_number', cleanedContact);
    formData.set('emergency_contact_phone', cleanedEmergencyPhone);

    // NEW: Loading state
    const submitBtn = addEmployeeForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Adding...';
    submitBtn.disabled = true;

    // DEBUG: Log FormData contents (remove after testing)
    console.log('Add FormData keys:', Array.from(formData.keys()));
    console.log('Add FormData values:', Object.fromEntries(formData.entries()));

    try {
      const response = await fetch(API_BASE, {
        method: 'POST',
        body: formData
      });
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        showToast(result.message || 'Employee added successfully.', 'success');  // Includes sync/queue status
        addModal.setAttribute('aria-hidden', 'true');
        addEmployeeForm.reset();
        // Reset defaults (no status/date_joined; clear emergency)
        document.getElementById('marital-status').value = 'Single';
        document.getElementById('annual-paid-leave-days').value = 15;
        document.getElementById('annual-unpaid-leave-days').value = 5;
        document.getElementById('annual-sick-leave-days').value = 10;
        document.getElementById('rate-per-hour').value = 0.00;
        document.getElementById('emergency-name').value = '';
        document.getElementById('emergency-phone').value = '';
        document.getElementById('emergency-relationship').value = '';
        addAvatarPreviewImg.src = 'img/user.jpg';
        fetchEmployees();  // Refresh list
      } else {
        showToast('Error: ' + (result.message || 'Failed to add employee'), 'error');
      }
    } catch (error) {
      console.error('Add error:', error);
      showToast('Failed to add employee: ' + error.message, 'error');
    } finally {
      // Reset loading
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  // Update Employee Modal Logic - UPDATED for new emergency fields, loading, toast, validation
  const updateModal = document.getElementById('update-employee-modal');
  const updateAvatarInput = document.getElementById('update-avatar-input');
  const updateAvatarPreviewImg = document.getElementById('update-avatar-preview-img');
  const updateUploadImageBtn = document.getElementById('update-upload-image-btn');
  const updateEmployeeForm = document.getElementById('update-employee-form');
  const updateCloseButtons = updateModal.querySelectorAll('.modal-close-btn');

  // Auto-format listeners for update modal phone inputs
  const updateContactInput = document.getElementById('update-contact-number');
  const updateEmergencyPhoneInput = document.getElementById('update-emergency-phone');
  if (updateContactInput) updateContactInput.addEventListener('input', () => autoFormatPhoneInput(updateContactInput));
  if (updateEmergencyPhoneInput) updateEmergencyPhoneInput.addEventListener('input', () => autoFormatPhoneInput(updateEmergencyPhoneInput));

  updateCloseButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      updateModal.setAttribute('aria-hidden', 'true');
    });
  });

  updateModal.addEventListener('click', (e) => {
    if (e.target === updateModal) {
      updateModal.setAttribute('aria-hidden', 'true');
    }
  });

  updateUploadImageBtn.addEventListener('click', () => {
    updateAvatarInput.click();
  });

  updateAvatarInput.addEventListener('change', () => {
    const file = updateAvatarInput.files[0];
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = e => {
        updateAvatarPreviewImg.src = e.target.result;
        updateAvatarPreviewImg.alt = 'Selected avatar preview';
      };
      reader.readAsDataURL(file);
    } else {
      // Reset to original employee avatar or default
      const idInput = document.getElementById('update-employee-id').value;
      if (idInput) {
        // Optionally refetch, but for simplicity, use default
        updateAvatarPreviewImg.src = 'img/user.jpg';
      }
      updateAvatarPreviewImg.alt = 'Avatar preview    ';
    }
  });

  // UPDATED: Update form submit (loading state, validation, clean phones, toast)
  updateEmployeeForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('update-employee-id')?.value;
    if (!id) {
      showToast('Employee ID not found. Please refresh and try again.', 'error');
      return;
    }

    // Client-side validation (no status/date_joined - not updated; emergency/contact optional if empty)
    const firstName = document.getElementById('update-first-name').value.trim();
    const lastName = document.getElementById('update-last-name').value.trim();
    const email = document.getElementById('update-email').value.trim();
    const address = document.getElementById('update-address').value.trim();
    const gender = document.getElementById('update-gender').value;
    const maritalStatus = document.getElementById('update-marital-status').value;
    const contactNumber = document.getElementById('update-contact-number').value.trim();
    const emergencyName = document.getElementById('update-emergency-name').value.trim();
    const emergencyPhone = document.getElementById('update-emergency-phone').value.trim();
    const emergencyRelationship = document.getElementById('update-emergency-relationship').value.trim();
    const departmentId = document.getElementById('update-department').value;
    const jobPositionId = document.getElementById('update-job-position').value;
    const ratePerHour = parseFloat(document.getElementById('update-rate-per-hour').value) || 0;

    if (!firstName || !lastName || !email || !address || !gender || !maritalStatus || !departmentId || !jobPositionId || ratePerHour < 0) {
      showToast('Please fill all required fields correctly.', 'error');
      return;
    }
    if (!email.includes('@')) {
      showToast('Please enter a valid email address.', 'error');
      return;
    }
    // Phone format validation (with spaces) - only if provided (optional for update)
    if (contactNumber && !validatePhoneFormat(contactNumber)) {
      showToast('09X XXXX XXXX', 'error');
      document.getElementById('update-contact-number').focus();
      return;
    }
    if (emergencyPhone && !validatePhoneFormat(emergencyPhone)) {
      showToast('09X XXXX XXXX', 'error');
      document.getElementById('update-emergency-phone').focus();
      return;
    }

    const formData = new FormData(updateEmployeeForm);
    formData.append('action', 'edit_employee');  // FIXED: Correct action for PHP
    formData.append('id', id);  // Ensure ID is sent

    // Clean phones (remove spaces/non-digits) before sending - use DB keys (no 'update-' prefix, consistent with add)
    const cleanedContact = contactNumber ? cleanPhoneNumber(contactNumber) : '';
    const cleanedEmergencyPhone = emergencyPhone ? cleanPhoneNumber(emergencyPhone) : '';
    formData.set('contact_number', cleanedContact);
    formData.set('emergency_contact_phone', cleanedEmergencyPhone);

    // NEW: Loading state
    const submitBtn = updateEmployeeForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;

    // DEBUG: Log FormData contents (remove after testing)
    console.log('Update FormData keys:', Array.from(formData.keys()));
    console.log('Update FormData values:', Object.fromEntries(formData.entries()));

    try {
      const response = await fetch(API_BASE, {
        method: 'POST',
        body: formData
      });
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const result = await response.json();
      if (result.success) {
        showToast(result.message || 'Employee updated successfully.', 'success');  // Includes sync/queue status
        updateModal.setAttribute('aria-hidden', 'true');
        updateEmployeeForm.reset();
        // Reset defaults (no status/date_joined; clear emergency)
        document.getElementById('update-marital-status').value = 'Single';
        document.getElementById('update-annual-paid-leave-days').value = 15;
        document.getElementById('update-annual-unpaid-leave-days').value = 5;
        document.getElementById('update-annual-sick-leave-days').value = 10;
        document.getElementById('update-rate-per-hour').value = 0.00;
        document.getElementById('update-emergency-name').value = '';
        document.getElementById('update-emergency-phone').value = '';
        document.getElementById('update-emergency-relationship').value = '';
        updateAvatarPreviewImg.src = 'img/user.jpg';
        fetchEmployees();  // Refresh list
      } else {
        showToast('Error: ' + (result.message || 'Failed to update employee'), 'error');
      }
    } catch (error) {
      console.error('Update error:', error);
      showToast('Failed to update employee: ' + error.message, 'error');
    } finally {
      // Reset loading
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  // NEW: Close modals on Escape key (global accessibility)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const openModals = document.querySelectorAll('[aria-hidden="false"]');
      openModals.forEach(modal => {
        modal.setAttribute('aria-hidden', 'true');
        const form = modal.querySelector('form');
        if (form) form.reset();
      });
    }
  });

  // Initialize on load: Load options, employees, and check pending sync
  loadSelectOptions();
  fetchEmployees();
  checkAndSyncPending();  // Initial sync if online
});
