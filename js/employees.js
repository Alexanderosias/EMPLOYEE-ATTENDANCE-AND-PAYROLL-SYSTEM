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

  // Elements for filtering
  const searchInput = document.getElementById('search-input');
  const searchBtn = document.getElementById('search-btn');
  const filterDepartment = document.getElementById('filter-department');
  const filterJobPosition = document.getElementById('filter-job-position');

  // Helper function to get detail item text by label
  function getDetailItemText(card, label) {
    const items = card.querySelectorAll('.detail-item');
    for (const item of items) {
      if (item.textContent.trim().startsWith(label)) {
        return item.textContent;
      }
    }
    return '';
  }

  // Filtering function
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

  // Attach event listeners for filtering
  searchBtn.addEventListener('click', filterEmployees);
  searchInput.addEventListener('input', filterEmployees);
  filterDepartment.addEventListener('change', filterEmployees);
  filterJobPosition.addEventListener('change', filterEmployees);

  // Existing employee card show more toggle
  employeeListContainer.addEventListener('click', (event) => {
    const showMoreBtn = event.target.closest('.action-btn-show-more');
    if (showMoreBtn) {
      const employeeCard = showMoreBtn.closest('.employee-card');
      const expandedDetails = employeeCard.querySelector('.expanded-details');
      const icon = showMoreBtn.querySelector('i');

      if (expandedDetails.classList.contains('visible')) {
        expandedDetails.classList.remove('visible');
        expandedDetails.setAttribute('aria-hidden', 'true');
        employeeCard.classList.add('minimized');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
      } else {
        expandedDetails.classList.add('visible');
        expandedDetails.setAttribute('aria-hidden', 'false');
        employeeCard.classList.remove('minimized');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
      }
    }
  });

  // Update and Delete buttons
  employeeListContainer.addEventListener('click', (event) => {
    if (event.target.closest('.action-btn-update')) {
      const employeeCard = event.target.closest('.employee-card');
      console.log("Update info clicked for:", employeeCard);

      // Extract employee data from the card to prefill the update form
      const employeeData = {
        name: employeeCard.querySelector('.employee-name')?.textContent.trim() || '',
        jobPosition: employeeCard.querySelector('.employee-job-position')?.textContent.trim() || '',
        department: employeeCard.querySelector('.employee-department')?.textContent.trim() || '',
        address: employeeCard.querySelector('.employee-address')?.textContent.trim() || '',
        gender: employeeCard.querySelector('.employee-gender')?.textContent.trim() || '',
        status: employeeCard.querySelector('.employee-status')?.textContent.trim() || '',
        email: employeeCard.querySelector('.employee-email')?.textContent.trim() || '',
        ratePerHour: employeeCard.querySelector('.employee-rate-per-hour')?.textContent.trim() || '',
        contactNumber: employeeCard.querySelector('.employee-contact-number')?.textContent.trim() || '',
        emergencyContact: employeeCard.querySelector('.employee-emergency-contact')?.textContent.trim() || '',
        schedules: employeeCard.querySelector('.employee-schedules')?.textContent.trim() || '',
        avatarUrl: employeeCard.querySelector('.employee-avatar img')?.src || ''
      };

      openUpdateModal(employeeData);

    } else if (event.target.closest('.action-btn-delete')) {
      const employeeCard = event.target.closest('.employee-card');
      console.log("Delete clicked for:", employeeCard);
      // Add your delete logic here
    }
  });

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
    addAvatarPreviewImg.src = 'https://placehold.co/100x100/cccccc/ffffff?text=Avatar';
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
      addAvatarPreviewImg.src = 'https://placehold.co/100x100/cccccc/ffffff?text=Avatar';
      addAvatarPreviewImg.alt = 'Avatar preview';
    }
  });

  addEmployeeForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const formData = new FormData(addEmployeeForm);

    for (const [key, value] of formData.entries()) {
      console.log('Add form:', key, value);
    }

    // TODO: Integrate with backend API here

    addModal.setAttribute('aria-hidden', 'true');
  });

  // Update Employee Modal Logic
  const updateModal = document.getElementById('update-employee-modal');
  const updateAvatarInput = document.getElementById('update-avatar-input');
  const updateAvatarPreviewImg = document.getElementById('update-avatar-preview-img');
  const updateUploadImageBtn = document.getElementById('update-upload-image-btn');
  const updateEmployeeForm = document.getElementById('update-employee-form');
  const updateCloseButtons = updateModal.querySelectorAll('.modal-close-btn');

  function openUpdateModal(employeeData) {
    updateModal.setAttribute('aria-hidden', 'false');

    // Prefill form fields with employeeData
    updateEmployeeForm.name.value = employeeData.name || '';
    updateEmployeeForm.jobPosition.value = employeeData.jobPosition || '';
    updateEmployeeForm.department.value = employeeData.department || '';
    updateEmployeeForm.address.value = employeeData.address || '';
    updateEmployeeForm.gender.value = employeeData.gender || '';
    updateEmployeeForm.status.value = employeeData.status || '';
    updateEmployeeForm.email.value = employeeData.email || '';
    updateEmployeeForm.ratePerHour.value = employeeData.ratePerHour || '';
    updateEmployeeForm.contactNumber.value = employeeData.contactNumber || '';
    updateEmployeeForm.emergencyContact.value = employeeData.emergencyContact || '';
    updateEmployeeForm.schedules.value = employeeData.schedules || '';

    // Set avatar preview if avlble
    if (employeeData.avatarUrl) {
      updateAvatarPreviewImg.src = employeeData.avatarUrl;
      updateAvatarPreviewImg.alt = 'Employee avatar preview';
    } else {
      updateAvatarPreviewImg.src = 'https://placehold.co/100x100/cccccc/ffffff?text=Avatar';
      updateAvatarPreviewImg.alt = 'Avatar preview';
    }

    updateEmployeeForm.querySelector('input, select').focus();
  }

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
      updateAvatarPreviewImg.src = 'https://placehold.co/100x100/cccccc/ffffff?text=Avatar';
      updateAvatarPreviewImg.alt = 'Avatar preview';
    }
  });

  updateEmployeeForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const formData = new FormData(updateEmployeeForm);

    for (const [key, value] of formData.entries()) {
      console.log('Update form:', key, value);
    }

    // TODO: Integrate with backend API here

    updateModal.setAttribute('aria-hidden', 'true');
  });
});
