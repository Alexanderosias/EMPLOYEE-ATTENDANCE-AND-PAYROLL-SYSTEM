document.addEventListener('DOMContentLoaded', () => {
  const API_BASE = '/EMPLOYEE ATTENDANCE AND PAYROLL SYSTEM/views/departments_positions.php';  // Backend endpoint

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
    modal.querySelector('form')?.reset();
  }

  // Delete confirmation modal elements
  const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
  const deleteConfirmationMessage = document.getElementById('delete-confirmation-message');
  const deleteWarningMessage = document.getElementById('delete-warning-message');
  const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
  let itemToDelete = null;
  let itemId = null;
  let itemType = null;  // 'department' or 'position'

  // Setup delete confirmation modal
  deleteConfirmationModal.querySelectorAll('.modal-close-btn').forEach(btn => {
    btn.addEventListener('click', () => closeModal(deleteConfirmationModal));
  });

  deleteConfirmationModal.addEventListener('click', e => {
    if (e.target === deleteConfirmationModal) closeModal(deleteConfirmationModal);
  });

  confirmDeleteBtn.addEventListener('click', () => {
    if (itemId && itemType) {
      deleteItem(itemId, itemType);
    }
  });

  // Delete function
  async function deleteItem(id, type) {
    try {
      const response = await fetch(`${API_BASE}?action=delete_${type}&id=${id}`, { method: 'DELETE' });
      const result = await response.json();
      if (result.success) {
        alert(result.message || 'Item deleted successfully.');
        closeModal(deleteConfirmationModal);
        if (type === 'department') {
          fetchDepartments();
        } else {
          fetchPositions();
        }
      } else {
        alert('Error: ' + result.message);
      }
    } catch (error) {
      console.error('Delete error:', error);
      alert('Failed to delete item.');
    }
    itemToDelete = null;
    itemId = null;
    itemType = null;
  }

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

  addDepartmentForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(addDepartmentForm);
    formData.append('action', 'add_department');

    try {
      const response = await fetch(API_BASE, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        alert(result.message || 'Department added successfully.');
        closeModal(addDepartmentModal);
        fetchDepartments();  // Refresh list
      } else {
        alert('Error: ' + result.message);
      }
    } catch (error) {
      console.error('Add department error:', error);
      alert('Failed to add department.');
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

  addJobPositionForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(addJobPositionForm);
    formData.append('action', 'add_position');

    try {
      const response = await fetch(API_BASE, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        alert(result.message || 'Job position added successfully.');
        closeModal(addJobPositionModal);
        fetchPositions();  // Refresh list
      } else {
        alert('Error: ' + result.message);
      }
    } catch (error) {
      console.error('Add position error:', error);
      alert('Failed to add job position.');
    }
  });

  // UPDATED: Function to create list item with delete button (disabled if employees assigned)
  function createListItem(id, text, count, type) {
    const li = document.createElement('li');
    li.setAttribute('data-id', id);

    const itemContent = document.createElement('span');
    itemContent.className = 'item-content';
    itemContent.textContent = `${text} (${count})`;

    const itemActions = document.createElement('div');
    itemActions.className = 'item-actions';

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-delete';
    deleteBtn.setAttribute('aria-label', `Delete ${text}`);

    const deleteImg = document.createElement('img');
    deleteImg.src = './icons/delete.png';  // Adjust path as needed
    deleteImg.alt = 'Delete';
    deleteImg.className = 'delete-icon';

    deleteBtn.appendChild(deleteImg);

    // FIXED: Disable delete if employees assigned (prevents interaction)
    const typeName = type === 'department' ? 'department' : 'job position';
    if (count > 0) {
      deleteBtn.disabled = true;
      deleteBtn.setAttribute('aria-disabled', 'true');
      deleteBtn.title = `Cannot delete this ${typeName} because it has ${count} employee(s) assigned. Please reassign employees first.`;
      // Optional: Add visual style (e.g., opacity: 0.5; cursor: not-allowed;) via CSS class 'disabled-delete'
      deleteBtn.classList.add('disabled-delete');  // Add CSS: .btn-delete.disabled-delete { opacity: 0.5; cursor: not-allowed; }
      // ALTERNATIVE: Hide button entirely - uncomment below
      // itemActions.style.display = 'none';  // Or don't append deleteBtn
    } else {
      // Enabled: Attach click listener to show confirmation
      deleteBtn.addEventListener('click', () => {
        showDeleteConfirmation(li, id, text, count, type);
      });
      deleteBtn.title = `Delete ${typeName} "${text}"`;
    }

    itemActions.appendChild(deleteBtn);
    li.appendChild(itemContent);
    li.appendChild(itemActions);

    return li;
  }

  // UPDATED: Function to show delete confirmation (only if count === 0)
  function showDeleteConfirmation(listItem, id, itemName, employeeCount, type) {
    const typeName = type === 'department' ? 'department' : 'job position';
    if (employeeCount > 0) {
      // Prevention: Don't open modal; show alert instead (redundant with disabled button, but safety)
      alert(`Cannot delete "${itemName}": This ${typeName} has ${employeeCount} employee(s) assigned. Please reassign them first.`);
      return;  // Exit early
    }

    // Proceed only if count === 0
    deleteConfirmationMessage.textContent = `Are you sure you want to delete the ${typeName} "${itemName}"?`;

    // No warning needed since count === 0, but keep for future
    deleteWarningMessage.style.display = 'none';

    itemToDelete = listItem;
    itemId = id;
    itemType = type;
    openModal(deleteConfirmationModal);
  }

  // Fetch and render departments
  async function fetchDepartments() {
    try {
      const response = await fetch(`${API_BASE}?action=list_departments`);
      const departments = await response.json();
      departmentsList.innerHTML = '';  // Clear
      if (departments.length === 0) {
        departmentsList.innerHTML = '<li>No departments found.</li>';
      } else {
        departments.forEach(dept => {
          const li = createListItem(dept.id, dept.name, dept.employee_count, 'department');
          departmentsList.appendChild(li);
        });
      }
    } catch (error) {
      console.error('Error fetching departments:', error);
      alert('Failed to load departments. Please refresh the page.');
      departmentsList.innerHTML = '<li>Error loading departments.</li>';
    }
  }

  // Fetch and render job positions
  async function fetchPositions() {
    try {
      const response = await fetch(`${API_BASE}?action=list_positions`);
      const positions = await response.json();
      jobPositionsList.innerHTML = '';  // Clear
      if (positions.length === 0) {
        jobPositionsList.innerHTML = '<li>No job positions found.</li>';
      } else {
        positions.forEach(pos => {
          const li = createListItem(pos.id, pos.name, pos.employee_count, 'position');
          jobPositionsList.appendChild(li);
        });
      }
    } catch (error) {
      console.error('Error fetching positions:', error);
      alert('Failed to load job positions. Please refresh the page.');
      jobPositionsList.innerHTML = '<li>Error loading job positions.</li>';
    }
  }

  // Initialize on load: Fetch and render lists
  fetchDepartments();
  fetchPositions();
});
