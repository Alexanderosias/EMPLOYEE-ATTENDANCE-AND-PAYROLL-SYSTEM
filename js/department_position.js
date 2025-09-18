document.addEventListener('DOMContentLoaded', () => {
  // Simulated employee data (replace with real data or fetch from backend)
  const employees = [
    { name: "Francis Rivas", department: "Computer Engineering", jobPosition: "Instructor" },
    { name: "Adela Onlao", department: "Computer Engineering", jobPosition: "Instructor" },
    // Add more employees as needed
  ];

  // Modal helper functions
  function openModal(modal) {
    modal.setAttribute('aria-hidden', 'false');
    const input = modal.querySelector('input');
    if (input) {
      input.focus();
    }
  }

  function closeModal(modal) {
    modal.setAttribute('aria-hidden', 'true');
  }

  // Delete confirmation modal elements
  const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
  const deleteConfirmationMessage = document.getElementById('delete-confirmation-message');
  const deleteWarningMessage = document.getElementById('delete-warning-message');
  const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
  let itemToDelete = null;
  let itemType = null;

  // Setup delete confirmation modal
  deleteConfirmationModal.querySelectorAll('.modal-close-btn').forEach(btn => {
    btn.addEventListener('click', () => closeModal(deleteConfirmationModal));
  });

  deleteConfirmationModal.addEventListener('click', e => {
    if (e.target === deleteConfirmationModal) closeModal(deleteConfirmationModal);
  });

  confirmDeleteBtn.addEventListener('click', () => {
    if (itemToDelete) {
      itemToDelete.remove();
      closeModal(deleteConfirmationModal);
      updateCounts();
      itemToDelete = null;
      itemType = null;
    }
  });

  // Departments
  const addDepartmentBtn = document.getElementById('add-department-btn');
  const addDepartmentModal = document.getElementById('add-department-modal');
  const addDepartmentForm = document.getElementById('add-department-form');
  const departmentsList = document.getElementById('departments-list');

  addDepartmentBtn.addEventListener('click', () => openModal(addDepartmentModal));

  addDepartmentModal.querySelectorAll('.modal-close-btn').forEach(btn => {
    btn.addEventListener('click', () => closeModal(addDepartmentModal));
  });

  addDepartmentModal.addEventListener('click', e => {
    if (e.target === addDepartmentModal) closeModal(addDepartmentModal);
  });

  addDepartmentForm.addEventListener('submit', e => {
    e.preventDefault();
    const input = addDepartmentForm['departmentName'];
    const newDept = input.value.trim();
    if (newDept) {
      // Check for duplicates (case-insensitive)
      const exists = Array.from(departmentsList.children).some(li => {
        const itemContent = li.querySelector('.item-content');
        const text = itemContent ? itemContent.textContent : li.textContent;
        return text.replace(/\s*\(\d+\)$/, '').toLowerCase() === newDept.toLowerCase();
      });
      if (exists) {
        alert('This department already exists.');
        return;
      }
      const li = createListItem(newDept, 'department');
      departmentsList.appendChild(li);
      input.value = '';
      closeModal(addDepartmentModal);
      updateCounts();
    }
  });

  // Job Positions
  const addJobPositionBtn = document.getElementById('add-job-position-btn');
  const addJobPositionModal = document.getElementById('add-job-position-modal');
  const addJobPositionForm = document.getElementById('add-job-position-form');
  const jobPositionsList = document.getElementById('job-positions-list');

  addJobPositionBtn.addEventListener('click', () => openModal(addJobPositionModal));

  addJobPositionModal.querySelectorAll('.modal-close-btn').forEach(btn => {
    btn.addEventListener('click', () => closeModal(addJobPositionModal));
  });

  addJobPositionModal.addEventListener('click', e => {
    if (e.target === addJobPositionModal) closeModal(addJobPositionModal);
  });

  addJobPositionForm.addEventListener('submit', e => {
    e.preventDefault();
    const input = addJobPositionForm['jobPositionName'];
    const newJob = input.value.trim();
    if (newJob) {
      // Check for duplicates (case-insensitive)
      const exists = Array.from(jobPositionsList.children).some(li => {
        const itemContent = li.querySelector('.item-content');
        const text = itemContent ? itemContent.textContent : li.textContent;
        return text.replace(/\s*\(\d+\)$/, '').toLowerCase() === newJob.toLowerCase();
      });
      if (exists) {
        alert('This job position already exists.');
        return;
      }
      const li = createListItem(newJob, 'jobPosition');
      jobPositionsList.appendChild(li);
      input.value = '';
      closeModal(addJobPositionModal);
      updateCounts();
    }
  });

  // Function to create list item with delete button
  function createListItem(text, type) {
    const li = document.createElement('li');

    const itemContent = document.createElement('span');
    itemContent.className = 'item-content';
    itemContent.textContent = text;

    const itemActions = document.createElement('div');
    itemActions.className = 'item-actions';

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-delete';
    deleteBtn.setAttribute('aria-label', `Delete ${text}`);

    // Replace icon with image
    const deleteImg = document.createElement('img');
    deleteImg.src = './icons/delete.png';  // Adjust path as needed
    deleteImg.alt = 'Delete';
    deleteImg.className = 'delete-icon'; // Optional: for styling (size, cursor, etc.)

    deleteBtn.appendChild(deleteImg);

    deleteBtn.addEventListener('click', () => {
      showDeleteConfirmation(li, text, type);
    });

    itemActions.appendChild(deleteBtn);
    li.appendChild(itemContent);
    li.appendChild(itemActions);

    return li;
  }


  // Function to show delete confirmation
  function showDeleteConfirmation(listItem, itemName, type) {
    const typeName = type === 'department' ? 'department' : 'job position';
    const employeeCount = getEmployeeCount(itemName, type);

    deleteConfirmationMessage.textContent = `Are you sure you want to delete the ${typeName} "${itemName}"?`;

    if (employeeCount > 0) {
      deleteWarningMessage.textContent = `Warning: This ${typeName} has ${employeeCount} employee(s) assigned to it. Deleting it may affect employee records.`;
      deleteWarningMessage.style.display = 'block';
    } else {
      deleteWarningMessage.style.display = 'none';
    }

    itemToDelete = listItem;
    itemType = type;
    openModal(deleteConfirmationModal);
  }

  // Function to get employee count for a department or job position
  function getEmployeeCount(itemName, type) {
    if (type === 'department') {
      return employees.filter(emp => emp.department === itemName).length;
    } else {
      return employees.filter(emp => emp.jobPosition === itemName).length;
    }
  }

  // Function to update counts next to departments and job positions
  function updateCounts() {
    // Count employees per department
    const deptCounts = {};
    employees.forEach(emp => {
      const dept = emp.department || "Unassigned";
      deptCounts[dept] = (deptCounts[dept] || 0) + 1;
    });

    // Count employees per job position
    const jobCounts = {};
    employees.forEach(emp => {
      const job = emp.jobPosition || "Unassigned";
      jobCounts[job] = (jobCounts[job] || 0) + 1;
    });

    // Update department list items with counts
    Array.from(departmentsList.children).forEach(li => {
      const itemContent = li.querySelector('.item-content');
      if (itemContent) {
        const deptName = itemContent.textContent.replace(/\s*\(\d+\)$/, '').trim();
        const count = deptCounts[deptName] || 0;
        itemContent.textContent = `${deptName} (${count})`;
      }
    });

    // Update job position list items with counts
    Array.from(jobPositionsList.children).forEach(li => {
      const itemContent = li.querySelector('.item-content');
      if (itemContent) {
        const jobName = itemContent.textContent.replace(/\s*\(\d+\)$/, '').trim();
        const count = jobCounts[jobName] || 0;
        itemContent.textContent = `${jobName} (${count})`;
      }
    });
  }

  // Function to convert existing list items to new format with delete buttons
  function convertExistingItems() {
    // Convert departments
    Array.from(departmentsList.children).forEach(li => {
      if (!li.querySelector('.item-content')) {
        const text = li.textContent.trim();
        const newLi = createListItem(text, 'department');
        li.parentNode.replaceChild(newLi, li);
      }
    });

    // Convert job positions
    Array.from(jobPositionsList.children).forEach(li => {
      if (!li.querySelector('.item-content')) {
        const text = li.textContent.trim();
        const newLi = createListItem(text, 'jobPosition');
        li.parentNode.replaceChild(newLi, li);
      }
    });
  }

  // Convert existing items and update counts
  convertExistingItems();
  updateCounts();
});