document.addEventListener('DOMContentLoaded', () => {
  const BASE_PATH = '';
  const API_BASE = BASE_PATH + '/views/employee_handler.php';

  // Load data
  loadLeaveBalances();
  loadLeaveRequests();

  // Modal controls
  const modal = document.getElementById('leave-modal');
  const requestBtn = document.getElementById('request-leave-btn');
  const cancelBtn = document.getElementById('cancel-leave-btn');

  requestBtn.addEventListener('click', () => modal.classList.remove('hidden'));
  cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));

  // Form submission
  document.getElementById('leave-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'request_leave');

    try {
      const response = await fetch(API_BASE, { method: 'POST', body: formData });
      const result = await response.json();
      if (result.success) {
        alert('Leave requested successfully');
        modal.classList.add('hidden');
        loadLeaveRequests();
      } else {
        alert('Error: ' + result.message);
      }
    } catch (error) {
      alert('Error: ' + error.message);
    }
  });

  async function loadLeaveBalances() {
    const response = await fetch(`${API_BASE}?action=leave_balances`);
    const result = await response.json();
    if (result.success) {
      document.getElementById('paid-leave-balance').textContent = result.data.paid;
      document.getElementById('unpaid-leave-balance').textContent = result.data.unpaid;
      document.getElementById('sick-leave-balance').textContent = result.data.sick;
    }
  }

  async function loadLeaveRequests() {
    const response = await fetch(`${API_BASE}?action=leave_requests`);
    const result = await response.json();
    if (result.success) {
      const tbody = document.getElementById('leave-requests-table');
      tbody.innerHTML = result.data.map(req => `
        <tr>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${req.type}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${req.start_date}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${req.end_date}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${req.days}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${req.reason}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(req.status)}">
              ${req.status}
            </span>
          </td>
        </tr>
      `).join('');
    }
  }

  function getStatusClass(status) {
    switch (status) {
      case 'Approved': return 'bg-green-100 text-green-800';
      case 'Rejected': return 'bg-red-100 text-red-800';
      default: return 'bg-yellow-100 text-yellow-800';
    }
  }
});