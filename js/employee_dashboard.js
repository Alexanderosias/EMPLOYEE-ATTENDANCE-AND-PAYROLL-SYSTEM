document.addEventListener('DOMContentLoaded', () => {
  const API_BASE = '../views/employee_handler.php';

  // Load data into dashboard widgets
  loadTodayAttendance();
  loadMonthlySummary();
  loadWorkSchedule();
  loadPayrollSummary();
  loadLeaveRequests();
  loadProfileOverview();
  loadQRCode();
  loadNotifications();

  // Event listeners (only if elements exist on this page)
  const downloadPayslipBtn = document.getElementById('download-payslip');
  if (downloadPayslipBtn) {
    downloadPayslipBtn.addEventListener('click', () => {
      window.location.href = 'employee_payroll.php';
    });
  }

  const downloadQrBtn = document.getElementById('download-qr');
  if (downloadQrBtn) {
    downloadQrBtn.addEventListener('click', () => {
      const imgEl = document.getElementById('qr-image');
      if (!imgEl || !imgEl.src) {
        console.warn('QR image not loaded yet');
        return;
      }
      const link = document.createElement('a');
      link.href = imgEl.src;
      link.download = 'eaaps-qr-code.png';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

  const editProfileBtn = document.getElementById('edit-profile-btn');
  if (editProfileBtn) {
    editProfileBtn.addEventListener('click', () => {
      window.location.href = 'employee_profile.php';
    });
  }

  // Functions to load data
  async function loadTodayAttendance() {
    try {
      const response = await fetch(`${API_BASE}?action=today_attendance`);
      const result = await response.json();
      if (!result.success || !result.data) return;

      const data = result.data;
      const statusEl = document.getElementById('attendance-status');
      const timeInEl = document.getElementById('time-in');
      const timeOutEl = document.getElementById('time-out');
      const totalHoursEl = document.getElementById('total-hours');
      const lateEl = document.getElementById('late-status');
      const undertimeEl = document.getElementById('undertime-status');

      if (statusEl) statusEl.textContent = data.status || 'No record yet today';
      if (timeInEl) timeInEl.textContent = data.time_in || 'N/A';
      if (timeOutEl) timeOutEl.textContent = data.time_out || 'N/A';
      if (totalHoursEl) totalHoursEl.textContent = (data.total_hours ?? 0).toString();
      if (lateEl) lateEl.textContent = data.late ? 'Yes' : 'No';
      if (undertimeEl) undertimeEl.textContent = data.undertime ? 'Yes' : 'No';
    } catch (e) {
      console.error('Failed to load today attendance', e);
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
    try {
      const response = await fetch(`${API_BASE}?action=payroll_summary`);
      const result = await response.json();
      if (!result.success || !result.data) return;

      const data = result.data;
      const hoursEl = document.getElementById('payroll-hours');
      const grossEl = document.getElementById('gross-pay');
      const dedEl = document.getElementById('deductions');
      const netEl = document.getElementById('net-pay');

      if (hoursEl) hoursEl.textContent = data.total_hours ?? 0;
      if (grossEl) grossEl.textContent = data.gross_pay ?? '0.00';
      if (dedEl) dedEl.textContent = data.deductions ?? '0.00';
      if (netEl) netEl.textContent = data.net_pay ?? '0.00';
    } catch (e) {
      console.error('Failed to load payroll summary', e);
    }
  }

  async function loadLeaveRequests() {
    try {
      const response = await fetch(`${API_BASE}?action=leave_requests`);
      const result = await response.json();
      const list = document.getElementById('leave-requests');
      if (!list) return;

      if (!result.success) {
        list.innerHTML = '<p class="text-sm text-red-600">Failed to load leave requests.</p>';
        return;
      }

      const items = Array.isArray(result.data) ? result.data : [];
      if (items.length === 0) {
        list.innerHTML = '<p class="text-sm text-gray-500">No leave requests yet.</p>';
        return;
      }

      const limited = items.slice(0, 5);
      list.innerHTML = limited.map(req => {
        const status = (req.status || '').trim();
        let badgeClass = 'bg-gray-100 text-gray-700';
        if (status === 'Approved') badgeClass = 'bg-green-100 text-green-700';
        else if (status === 'Pending') badgeClass = 'bg-yellow-100 text-yellow-700';
        else if (status === 'Declined') badgeClass = 'bg-red-100 text-red-700';

        const typeLabel = req.type || 'Leave';
        const range = req.start_date && req.end_date
          ? `${req.start_date} to ${req.end_date}`
          : (req.start_date || '');

        return `
          <div class="flex items-center justify-between border-b last:border-b-0 py-1">
            <div class="text-xs text-gray-700">
              <div class="font-semibold">${typeLabel}</div>
              <div class="text-gray-500">${range}</div>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ${badgeClass}">${status || 'N/A'}</span>
          </div>
        `;
      }).join('');
    } catch (e) {
      const list = document.getElementById('leave-requests');
      if (list) {
        list.innerHTML = '<p class="text-sm text-red-600">Failed to load leave requests.</p>';
      }
      console.error('Failed to load leave requests', e);
    }
  }

  async function loadProfileOverview() {
    try {
      const response = await fetch(`${API_BASE}?action=profile_overview`);
      const result = await response.json();
      if (!result.success || !result.data) return;

      const data = result.data;
      const avatarEl = document.getElementById('profile-avatar');
      const nameEl = document.getElementById('profile-name');
      const idEl = document.getElementById('profile-id');
      const deptEl = document.getElementById('profile-dept');
      const posEl = document.getElementById('profile-pos');
      const contactEl = document.getElementById('profile-contact');

      if (avatarEl && data.avatar) avatarEl.src = data.avatar;
      if (nameEl) nameEl.textContent = data.name || '';
      if (idEl) idEl.textContent = data.id || '';
      if (deptEl) deptEl.textContent = data.department || 'N/A';
      if (posEl) posEl.textContent = data.position || 'N/A';
      if (contactEl) contactEl.textContent = data.contact || 'N/A';
    } catch (e) {
      console.error('Failed to load profile overview', e);
    }
  }

  async function loadQRCode() {
    try {
      const response = await fetch(`${API_BASE}?action=get_qr`);
      const result = await response.json();
      if (!result.success || !result.data) return;

      const imgEl = document.getElementById('qr-image');
      if (imgEl && result.data.qr_path) {
        imgEl.src = result.data.qr_path;
      }
    } catch (e) {
      console.error('Failed to load QR code', e);
    }
  }

  async function loadNotifications() {
    try {
      const response = await fetch(`${API_BASE}?action=notifications`);
      const result = await response.json();
      if (!result.success) return;

      const list = document.getElementById('notifications-list');
      if (!list) return;

      const items = Array.isArray(result.data) ? result.data : [];
      if (items.length === 0) {
        list.innerHTML = '<li class="text-gray-500 text-sm">No notifications.</li>';
        return;
      }

      list.innerHTML = items.map(notif => `<li>${notif.message}</li>`).join('');
    } catch (e) {
      console.error('Failed to load notifications', e);
    }
  }
});