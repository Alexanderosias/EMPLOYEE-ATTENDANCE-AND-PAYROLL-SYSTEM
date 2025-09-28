document.addEventListener('DOMContentLoaded', () => {
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
  const API_BASE = '/EMPLOYEE ATTENDANCE AND PAYROLL SYSTEM/views/employees.php';  // Backend endpoint

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

  // Validate phone format (098-765-4321)
  function validatePhoneFormat(phone) {
    const phoneRegex = /^\d{3}-\d{3}-\d{4}$/;
    return phoneRegex.test(phone);
  }

  // Validate emergency contact (extracts phone part, e.g., "Name: 098-765-4321")
  function validateEmergencyContactFormat(contact) {
    // Extract potential phone (look for XXX-XXX-XXXX pattern)
    const phoneMatch = contact.match(/\d{3}-\d{3}-\d{4}/);
    if (!phoneMatch) return false;
    return validatePhoneFormat(phoneMatch[0]);
  }

  // Fetch and populate departments and job positions for selects
  async function loadSelectOptions() {
    try {
      const [deptRes, posRes] = await Promise.all([
        fetch(`${API_BASE}?action=departments`),
        fetch(`${API_BASE}?action=positions`)
      ]);
      if (!deptRes.ok || !posRes.ok) throw new Error('Failed to load options');
      departments = await deptRes.json();
      jobPositions = await posRes.json();

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
      alert('Failed to load departments/positions. Please refresh.');
    }
  }

  // Fetch and render employees
  async function fetchEmployees() {
    try {
      const response = await fetch(`${API_BASE}?action=list_employees`);
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }
      const employees = await response.json();
      if (!Array.isArray(employees)) throw new Error('Invalid response format');
      renderEmployeeCards(employees);
    } catch (error) {
      console.error('Error fetching employees:', error);
      alert('Failed to load employees: ' + error.message);
      employeeListContainer.innerHTML = '<p style="color: red; text-align: center;">Error loading employees. Please refresh.</p>';
    }
  }

  // Render employee cards dynamically (status with colors; date_joined & status displayed but not editable in modals)
  function renderEmployeeCards(employees) {
    employeeListContainer.innerHTML = '';  // Clear existing
    employees.forEach((emp, index) => {
      // Determine status class for coloring
      let statusClass = '';
      if (emp.status === 'On Leave') {
        statusClass = 'status-on-leave';
      } else if (emp.status === 'Active') {
        statusClass = 'status-active';
      } else if (emp.status === 'Inactive') {
        statusClass = 'status-inactive';
      }

      const card = document.createElement('div');
      card.className = 'employee-card minimized';
      card.setAttribute('data-id', emp.id);
      card.innerHTML = `
        <div class="card-index">${index + 1}</div>
        <div class="image-container employee-avatar">
          <img src="${emp.avatar_path || 'img/user.jpg'}" alt="Employee Photo" class="employee-photo" />
        </div>
        <div class="employee-details">
          <p class="detail-item"><strong>Name:</strong> ${emp.first_name} ${emp.last_name}</p>
          <p class="detail-item"><strong>Job Position:</strong> ${emp.position_name || 'N/A'}</p>
          <p class="detail-item"><strong>Department:</strong> ${emp.department_name || 'N/A'}</p>
          <p class="detail-item"><strong>Address:</strong> ${emp.address || 'N/A'}</p>
          <div class="expanded-details" aria-hidden="true">
            <p class="expanded-item"><strong>Gender:</strong> ${emp.gender || 'N/A'}</p>
            <p class="expanded-item"><strong>Marital Status:</strong> ${emp.marital_status || 'Single'}</p>
            <p class="expanded-item ${statusClass}"><strong>Status:</strong> ${emp.status || 'N/A'}</p>
            <p class="expanded-item"><strong>Email Address:</strong> ${emp.email}</p>
            <p class="expanded-item"><strong>Rate per Hour:</strong> ₱${emp.rate_per_hour || 0}</p>
            <p class="expanded-item"><strong>Contact Number:</strong> ${emp.contact_number || 'N/A'}</p>
            <p class="expanded-item"><strong>Emergency Contact:</strong> ${emp.emergency_contact || 'N/A'}</p>
            <p class="expanded-item"><strong>Date Joined:</strong> ${emp.date_joined || 'N/A'}</p>
            <p class="expanded-item"><strong>Annual Paid Leave Days:</strong> ${emp.annual_paid_leave_days || 15}</p>
            <p class="expanded-item"><strong>Annual Unpaid Leave Days:</strong> ${emp.annual_unpaid_leave_days || 5}</p>
            <p class="expanded-item"><strong>Annual Sick Leave Days:</strong> ${emp.annual_sick_leave_days || 10}</p>
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
        if (!response.ok) throw new Error('Failed to fetch employee data');
        const employeeData = await response.json();
        openUpdateModal(employeeData);
      } catch (error) {
        console.error('Error fetching employee for update:', error);
        alert('Failed to load employee data for update: ' + error.message);
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

  // Delete employee function
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
        alert(result.message || 'Employee deleted successfully.');
        fetchEmployees();  // Refresh list
      } else {
        alert('Error: ' + (result.message || 'Failed to delete employee'));
      }
    } catch (error) {
      console.error('Delete error:', error);
      alert('Failed to delete employee: ' + error.message);
    }
  }

  // Add Employee Modal Logic
  const addModal = document.getElementById('add-employee-modal');
  const addEmployeeBtn = document.getElementById('add-employee-btn');
  const addCloseButtons = addModal.querySelectorAll('.modal-close-btn');
  const addAvatarInput = document.getElementById('avatar-input');
  const addAvatarPreviewImg = document.getElementById('avatar-preview-img');
  const addEmployeeForm = document.getElementById('add-employee-form');
  const addUploadImageBtn = document.getElementById('upload-image-btn');

  addEmployeeBtn.addEventListener('click', () => {
    addModal.setAttribute('aria-hidden', 'false');
    addEmployeeForm.reset();
    // Set defaults for optional/nullable fields (no status/date_joined - auto-handled by PHP)
    document.getElementById('marital-status').value = 'Single';
    document.getElementById('annual-paid-leave-days').value = 15;
    document.getElementById('annual-unpaid-leave-days').value = 5;
    document.getElementById('annual-sick-leave-days').value = 10;
    document.getElementById('rate-per-hour').value = 0.00;
    addAvatarPreviewImg.src = 'img/user.jpg';
    addAvatarPreviewImg.alt = 'Avatar preview';
    addEmployeeForm.querySelector('input, select').focus();
  });

  addClose