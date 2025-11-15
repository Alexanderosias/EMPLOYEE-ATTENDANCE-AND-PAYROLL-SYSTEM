document.addEventListener('DOMContentLoaded', () => {
  const BASE_PATH = '';
  const API_BASE = BASE_PATH + '/views/employee_handler.php';

  // Load data
  loadTodayAttendance();
  loadMonthlySummary();
  loadWorkSchedule();
  loadPayrollSummary();
  loadLeaveRequests();
  loadProfileOverview();
  loadQRCode();
  loadNotifications();

  // Event listeners
  document.getElementById('request-leave-btn').addEventListener('click', () => {
    document.getElementById('leave-modal').style.display = 'flex';
  });

  document.getElementById('leave-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'request_leave');
    const response = await fetch(API_BASE, { method: 'POST', body: formData });
    const result = await response.json();
    if (result.success) {
      alert('Leave requested successfully');
      document.getElementById('leave-modal').style.display = 'none';
      loadLeaveRequests();
    } else {
      alert('Error: ' + result.message);
    }
  });

  document.getElementById('download-payslip').addEventListener('click', () => {
    window.open(`${API_BASE}?action=download_payslip`, '_blank');
  });

  document.getElementById('download-qr').addEventListener('click', () => {
    window.open(`${API_BASE}?action=download_qr`, '_blank');
  });

  // Functions to load data
  async function loadTodayAttendance() {
    const response = await fetch(`${API_BASE}?action=today_attendance`);
    const result = await response.json();
    if (result.success) {
      document.getElementById('today-attendance').innerHTML = `
        <p>Status: ${result.data.status}</p>
        <p>Time-in: ${result.data.time_in || 'N/A'}</p>
        <p>Time-out: ${result.data.time_out || 'N/A'}</p>
        <p>Total Hours: ${result.data.total_hours}</p>
        <p>Late: ${result.data.late ? 'Yes' : 'No'}</p>
        <p>Undertime: ${result.data.undertime ? 'Yes' : 'No'}</p>
      `;
    }
  }

  async function loadMonthlySummary() {
    const response = await fetch(`${API_BASE}?action=monthly_summary`);
    const result = await response.json();
    if (result.success) {
      const data = result.data;
      document.getElementById('total-present').textContent = data.present;
      document.getElementById('total-absent').textContent = data.absent;
      document.getElementById('total-lates').textContent = data.lates;
      document.getElementById('total-undertime').textContent = data.undertime;
      document.getElementById('total-overtime').textContent = data.overtime;
      document.getElementById('working-days').textContent = data.working_days;

      // Chart
      const ctx = document.getElementById('attendance-chart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.days,
          datasets: [{
            label: 'Daily Hours',
            data: data.hours,
            backgroundColor: 'rgba(40, 167, 69, 0.5)',
          }]
        }
      });
    }
  }

  async function loadWorkSchedule() {
    const response = await fetch(`${API_BASE}?action=work_schedule`);
    const result = await response.json();
    if (result.success) {
      document.getElementById('work-schedule').innerHTML = `
        <p>Weekly Schedule: ${result.data.schedule}</p>
        <p>Rest Day: ${result.data.rest_day}</p>
        <p>Shift Time: ${result.data.shift_time}</p>
        <p>Department: ${result.data.department}</p>
        <p>Position: ${result.data.position}</p>
      `;
    }
  }

  async function loadPayrollSummary() {
    const response = await fetch(`${API_BASE}?action=payroll_summary`);
    const result = await response.json();
    if (result.success) {
      document.getElementById('payroll-summary').innerHTML = `
        <p>Total Hours: ${result.data.total_hours}</p>
        <p>Gross Pay: ₱${result.data.gross_pay}</p>
        <p>Deductions: ₱${result.data.deductions}</p>
        <p>Net Pay: ₱${result.data.net_pay}</p>
      `;
    }
  }

  async function loadLeaveRequests() {
    const response = await fetch(`${API_BASE}?action=leave_requests`);
    const result = await response.json();
    if (result.success) {
      const list = document.getElementById('leave-requests');
      list.innerHTML = result.data.map(req => `<p>${req.type}: ${req.status}</p>`).join('');
    }
  }

  async function loadProfileOverview() {
    const response = await fetch(`${API_BASE}?action=profile_overview`);
    const result = await response.json();
    if (result.success) {
      document.getElementById('profile-overview').innerHTML = `
        <img src="${result.data.avatar}" alt="Avatar" width="50">
        <p>Name: ${result.data.name}</p>
        <p>ID: ${result.data.id}</p>
        <p>Department: ${result.data.department}</p>
        <p>Position: ${result.data.position}</p>
        <p>Contact: ${result.data.contact}</p>
      `;
    }
  }

  async function loadQRCode() {
    const response = await fetch(`${API_BASE}?action=get_qr`);
    const result = await response.json();
    if (result.success) {
      document.getElementById('qr-code').innerHTML = `<img src="${result.data.qr_path}" alt="QR Code">`;
    }
  }

  async function loadNotifications() {
    const response = await fetch(`${API_BASE}?action=notifications`);
    const result = await response.json();
    if (result.success) {
      const list = document.getElementById('notifications-list');
      list.innerHTML = result.data.map(notif => `<li>${notif.message}</li>`).join('');
    }
  }
});