document.addEventListener('DOMContentLoaded', () => {
  const API = '../views/payroll_handler.php';

  // Summary refs
  const sumGross = document.getElementById('sum-gross');
  const sumDed = document.getElementById('sum-deductions');
  const sumNet = document.getElementById('sum-net');
  const sumPeriod = document.getElementById('sum-period');

  // Controls
  const startInput = document.getElementById('period-start');
  const endInput = document.getElementById('period-end');
  const freqSelect = document.getElementById('freq-select');
  const roleFilter = document.getElementById('role-filter');
  const btnRecalc = document.getElementById('btn-recalc');

  // Tables
  const roleNextBody = document.getElementById('role-next-payroll-body');
  const previewBody = document.getElementById('payroll-preview-body');

  // Deductions
  const dedRows = document.getElementById('deduction-rows');
  const btnAddDed = document.getElementById('btn-add-deduction');

  const statusBox = document.getElementById('status-message');

  function show(msg, type = 'success') {
    if (!statusBox) return;
    statusBox.textContent = msg;
    statusBox.className = 'status-message ' + type;
    statusBox.classList.add('show');
    if (statusBox._hideTimer) clearTimeout(statusBox._hideTimer);
    statusBox._hideTimer = setTimeout(() => statusBox.classList.remove('show'), 2500);
  }

  function pad(n){ return n.toString().padStart(2,'0'); }
  function ymd(d){ return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }

  function initDefaultPeriod() {
    const now = new Date();
    const s = new Date(now.getFullYear(), now.getMonth(), now.getDate() <= 15 ? 1 : 16);
    const e = new Date(now.getFullYear(), now.getMonth(), now.getDate() <= 15 ? 15 : (new Date(now.getFullYear(), now.getMonth()+1, 0)).getDate());
    if (startInput) startInput.value = ymd(s);
    if (endInput) endInput.value = ymd(e);
    if (sumPeriod) sumPeriod.textContent = `${startInput.value} to ${endInput.value}`;
  }

  async function loadRoles() {
    try {
      const res = await fetch(`${API}?action=roles`);
      const out = await res.json();
      if (!out.success) return;
      const roles = Array.isArray(out.data) ? out.data : [];
      if (!roleFilter) return;
      const first = roleFilter.querySelector('option')?.outerHTML || '<option value="">All Roles</option>';
      roleFilter.innerHTML = first + roles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
    } catch {}
  }

  async function loadNextPayrollPerRole() {
    try {
      const res = await fetch(`${API}?action=next_payroll_per_role`);
      const out = await res.json();
      if (!roleNextBody) return;
      roleNextBody.innerHTML = '';
      if (!out.success) {
        roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">Failed to load</td></tr>';
        return;
      }
      const list = Array.isArray(out.data) ? out.data : [];
      if (list.length === 0) {
        roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">No data</td></tr>';
        return;
      }
      list.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.role_name}</td>
          <td>${(r.frequency || '').replace(/^./, c=>c.toUpperCase())}</td>
          <td>${r.next_payroll_date}</td>
          <td>${r.period_start} to ${r.period_end}</td>
        `;
        roleNextBody.appendChild(tr);
      });
    } catch {
      if (roleNextBody) roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">Error</td></tr>';
    }
  }

  function collectDeductions() {
    const arr = [];
    if (!dedRows) return arr;
    dedRows.querySelectorAll('.deduction-row').forEach(row => {
      const [typeSel, labelInp, amtInp, scopeSel] = row.querySelectorAll('select, input[type="text"], input[type="number"]');
      const recur = row.querySelector('input[type="checkbox"]');
      const type = typeSel ? (typeSel.value || '') : '';
      const label = labelInp ? (labelInp.value || '') : '';
      const amount = parseFloat(amtInp?.value || '0');
      const scope = scopeSel ? (scopeSel.value || 'Per Employee') : 'Per Employee';
      const recurring = !!(recur && recur.checked);
      if (!isNaN(amount) && amount > 0) arr.push({ type, label, amount, scope, recurring });
    });
    return arr;
  }

  function formatPhp(n) {
    const num = typeof n === 'string' ? parseFloat(n) : n;
    if (isNaN(num)) return '₱0.00';
    return '₱' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  async function recalcPreview() {
    try {
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;
      if (sumPeriod) sumPeriod.textContent = `${start || '—'} to ${end || '—'}`;
      const body = { start, end, role_id, deductions: collectDeductions() };
      const res = await fetch(`${API}?action=preview`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to compute');

      // Update summary
      const s = out.data?.summary || {};
      if (sumGross) sumGross.textContent = formatPhp(s.total_gross || 0);
      if (sumDed) sumDed.textContent = formatPhp(s.total_deductions || 0);
      if (sumNet) sumNet.textContent = formatPhp(s.total_net || 0);

      // Update table
      if (previewBody) {
        previewBody.innerHTML = '';
        const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
        if (rows.length === 0) {
          previewBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">No results</td></tr>';
        } else {
          rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${r.employee}</td>
              <td>${r.role}</td>
              <td>${r.hours_days}</td>
              <td>${formatPhp(r.gross)}</td>
              <td>${formatPhp(r.deductions)}</td>
              <td>${formatPhp(r.net)}</td>
              <td>${r.status || 'Included'}</td>
            `;
            previewBody.appendChild(tr);
          });
        }
      }
      show('Preview updated.');
    } catch (e) {
      show(e.message || 'Failed to compute', 'error');
    }
  }

  function makeDeductionRow() {
    const div = document.createElement('div');
    div.className = 'deduction-row';
    div.innerHTML = `
      <select>
        <option>SSS</option>
        <option>PhilHealth</option>
        <option>Pag-IBIG</option>
        <option>Tax</option>
        <option>Loan</option>
        <option>Other</option>
      </select>
      <input type="text" placeholder="e.g., Union Fee" />
      <input type="number" step="0.01" placeholder="0.00" />
      <select>
        <option>Per Employee</option>
        <option>Per Role</option>
        <option>Global</option>
      </select>
      <label class="recurring"><input type="checkbox" /> Yes</label>
      <button type="button" class="btn-remove-row">Remove</button>
    `;
    div.querySelector('.btn-remove-row').addEventListener('click', () => div.remove());
    return div;
  }

  // Events
  btnRecalc && btnRecalc.addEventListener('click', recalcPreview);
  btnAddDed && btnAddDed.addEventListener('click', () => { dedRows.appendChild(makeDeductionRow()); });
  roleFilter && roleFilter.addEventListener('change', recalcPreview);

  // Wire initial remove buttons (first row)
  dedRows?.querySelectorAll('.btn-remove-row').forEach(btn => btn.addEventListener('click', (e) => {
    const row = e.target.closest('.deduction-row');
    if (row) row.remove();
  }));

  // Init
  initDefaultPeriod();
  loadRoles();
  loadNextPayrollPerRole();
  recalcPreview();
});

