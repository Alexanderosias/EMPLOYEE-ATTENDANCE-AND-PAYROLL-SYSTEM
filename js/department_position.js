document.addEventListener('DOMContentLoaded', () => {
  // Simulated employee data (replace with real data or fetch from backend)
  const employees = [
    { name: "Francis Rivas", department: "Computer Engineering", jobPosition: "Instructor" },
    { name: "Alice Smith", department: "Math", jobPosition: "Developer" },
    { name: "Bob Johnson", department: "Physics", jobPosition: "Designer" },
    { name: "Carol Lee", department: "IT", jobPosition: "Developer" },
    { name: "David Kim", department: "Marketing", jobPosition: "Manager" },
    { name: "Eve Davis", department: "Computer Engineering", jobPosition: "Developer" },
    // Add more employees as needed
  ];

  // Modal helper functions
  function openModal(modal) {
    modal.setAttribute('aria-hidden', 'false');
    modal.querySelector('input').focus();
  }

  function closeModal(modal) {
    modal.setAttribute('aria-hidden', 'true');
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

  addDepartmentForm.addEventListener('submit', e => {
    e.preventDefault();
    const input = addDepartmentForm['departmentName'];
    const newDept = input.value.trim();
    if (newDept) {
      // Check for duplicates (case-insensitive)
      const exists = Array.from(departmentsList.children).some(li => li.textContent.replace(/\s*\(\d+\)$/, '').toLowerCase() === newDept.toLowerCase());
      if (exists) {
        alert('This department already exists.');
        return;
      }
      const li = document.createElement('li');
      li.textContent = newDept;
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
      const exists = Array.from(jobPositionsList.children).some(li => li.textContent.replace(/\s*\(\d+\)$/, '').toLowerCase() === newJob.toLowerCase());
      if (exists) {
        alert('This job position already exists.');
        return;
      }
      const li = document.createElement('li');
      li.textContent = newJob;
      jobPositionsList.appendChild(li);
      input.value = '';
      closeModal(addJobPositionModal);
      updateCounts();
    }
  });

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
      const deptName = li.textContent.replace(/\s*\(\d+\)$/, '').trim();
      const count = deptCounts[deptName] || 0;
      li.textContent = `${deptName} (${count})`;
    });

    // Update job position list items with counts
    Array.from(jobPositionsList.children).forEach(li => {
      const jobName = li.textContent.replace(/\s*\(\d+\)$/, '').trim();
      const count = jobCounts[jobName] || 0;
      li.textContent = `${jobName} (${count})`;
    });
  }

  // Initial count update
  updateCounts();
});