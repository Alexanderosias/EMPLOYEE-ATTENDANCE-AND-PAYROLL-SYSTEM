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
  const btnFinalize = document.getElementById('btn-finalize');
  const periodDisplay = document.getElementById('period-display');

  const thirteenthYearInput = document.getElementById('thirteenth-year');
  const thirteenthAsOfInput = document.getElementById('thirteenth-as-of');
  const thirteenthBody = document.getElementById('thirteenth-body');
  const btnThirteenthRecalc = document.getElementById('btn-thirteenth-recalc');

  // Tables
  const roleNextBody = document.getElementById('role-next-payroll-body');
  const previewBody = document.getElementById('payroll-preview-body');
  const finalizedBody = document.getElementById('payroll-finalized-body');

  // Deductions
  const dedRows = document.getElementById('deduction-rows');
  const btnAddDed = document.getElementById('btn-add-deduction');

  const statusBox = document.getElementById('status-message');
  const btnRefreshFinalized = document.getElementById('btn-refresh-finalized');
  const btnMarkPeriodPaid = document.getElementById('btn-mark-period-paid');

  const rolesMeta = {};
  const rolePeriods = {};

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

  function initThirteenthDefaults() {
    const now = new Date();
    if (thirteenthYearInput) {
      thirteenthYearInput.value = now.getFullYear();
    }
    if (thirteenthAsOfInput) {
      const y = now.getFullYear();
      const m = pad(now.getMonth() + 1);
      const d = pad(now.getDate());
      thirteenthAsOfInput.value = `${y}-${m}-${d}`;
    }
  }

  function syncRoleFiltersFromSelection() {
    if (!roleFilter || !freqSelect) return;

    const roleId = parseInt(roleFilter.value || '0', 10) || 0;

    if (!roleId) {
      // All roles: show no specific frequency
      freqSelect.value = '';
      if (periodDisplay) {
        periodDisplay.textContent = '';
      }
      return;
    }

    const meta = rolesMeta[roleId];
    const freq = (meta && meta.payroll_frequency) ? String(meta.payroll_frequency).toLowerCase() : '';

    if (freq && freqSelect.querySelector(`option[value="${freq}"]`)) {
      freqSelect.value = freq;
    } else {
      freqSelect.value = '';
    }

    // Auto-compute end date based on current start date and role frequency
    let startVal = startInput?.value || '';
    if (!startVal) {
      // If no start yet, default to today
      const now = new Date();
      startVal = ymd(now);
      if (startInput) startInput.value = startVal;
    }

    let endVal = startVal;
    try {
      const parts = startVal.split('-').map(Number);
      if (parts.length === 3) {
        const y = parts[0];
        const m = parts[1] - 1; // JS months 0-11
        const d = parts[2];
        const sDate = new Date(y, m, d);
        let eDate = new Date(sDate.getTime());

        if (freq === 'weekly') {
          eDate.setDate(eDate.getDate() + 6);
        } else if (freq === 'bi-weekly') {
          eDate.setDate(eDate.getDate() + 13);
        } else if (freq === 'monthly') {
          // Last day of the same month as the start date
          eDate = new Date(y, m + 1, 0);
        } else {
          // daily or unknown: same day
        }

        endVal = ymd(eDate);
      }
    } catch (e) {
      endVal = startVal;
    }

    if (endInput) endInput.value = endVal;
    if (sumPeriod) sumPeriod.textContent = `${startVal || '—'} to ${endVal || '—'}`;

    if (periodDisplay) {
      const label = (freq || '').replace(/^./, c => c.toUpperCase());
      periodDisplay.textContent = label
        ? `${label} period: ${startVal} to ${endVal}`
        : `${startVal} to ${endVal}`;
    }
  }

  async function loadRoles() {
    try {
      const res = await fetch(`${API}?action=roles`);
      const out = await res.json();
      if (!out.success) return;
      const roles = Array.isArray(out.data) ? out.data : [];
      if (!roleFilter) return;
      const first = roleFilter.querySelector('option')?.outerHTML || '<option value="">All Roles</option>';

      roles.forEach(r => {
        rolesMeta[r.id] = {
          id: r.id,
          name: r.name,
          payroll_frequency: (r.payroll_frequency || '').toLowerCase()
        };
      });

      roleFilter.innerHTML = first + roles
        .map(r => `<option value="${r.id}" data-frequency="${(r.payroll_frequency || '').toLowerCase()}">${r.name}</option>`)
        .join('');

      if (dedRows) {
        dedRows.querySelectorAll('.deduction-row').forEach(initDeductionRow);
        applyRecurringTemplates();
      }

      syncRoleFiltersFromSelection();
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
      Object.keys(rolePeriods).forEach(k => { delete rolePeriods[k]; });
      list.forEach(r => {
        const id = parseInt(r.role_id, 10) || 0;
        if (!id) return;
        rolePeriods[id] = {
          frequency: (r.frequency || '').toLowerCase(),
          period_start: r.period_start,
          period_end: r.period_end
        };
      });
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

      // Update frequency/period display for current selection if any
      syncRoleFiltersFromSelection();
    } catch {
      if (roleNextBody) roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">Error</td></tr>';
    }
  }

  function collectDeductions() {
    const arr = [];
    if (!dedRows) return arr;
    dedRows.querySelectorAll('.deduction-row').forEach(row => {
      const typeSel = row.querySelector('.ded-type');
      const labelInp = row.querySelector('.ded-label');
      const amtInp = row.querySelector('.ded-amount');
      const scopeSel = row.querySelector('.ded-scope');
      const recur = row.querySelector('.ded-recurring');
      const rolesBox = row.querySelector('.ded-scope-roles');

      const type = typeSel ? (typeSel.value || '') : '';
      const label = labelInp ? (labelInp.value || '') : '';
      const amount = parseFloat(amtInp?.value || '0');
      let scope = scopeSel ? (scopeSel.value || 'per_employee') : 'per_employee';
      scope = scope.toLowerCase();
      const recurring = !!(recur && recur.checked);

      if (isNaN(amount) || amount <= 0) {
        return;
      }

      const roles = [];
      if (rolesBox) {
        rolesBox.querySelectorAll('input.ded-role-checkbox[data-role-id]').forEach(cb => {
          if (cb.checked) {
            const id = parseInt(cb.dataset.roleId || '0', 10);
            if (id > 0) roles.push(id);
          }
        });
      }

      arr.push({ type, label, amount, scope, recurring, roles });
    });
    return arr;
  }

  const RECURRING_KEY = 'eaaps_recurring_deductions_v1';

  function loadRecurringTemplates() {
    if (typeof window === 'undefined' || !window.localStorage) return [];
    try {
      const raw = window.localStorage.getItem(RECURRING_KEY);
      if (!raw) return [];
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  function saveRecurringTemplates() {
    if (typeof window === 'undefined' || !window.localStorage) return;
    try {
      const all = collectDeductions().filter(d => d && d.recurring);
      window.localStorage.setItem(RECURRING_KEY, JSON.stringify(all));
    } catch {
      // ignore storage errors
    }
  }

  function applyRecurringTemplates() {
    const templates = loadRecurringTemplates();
    if (!templates.length || !dedRows) return;

    const byType = {};
    templates.forEach(t => {
      if (!t || !t.type) return;
      const key = String(t.type).toLowerCase();
      if (!byType[key]) byType[key] = t;
    });

    dedRows.querySelectorAll('.deduction-row').forEach(row => {
      const typeField = row.querySelector('.ded-type');
      const typeVal = typeField ? String(typeField.value || '').toLowerCase() : '';
      if (!typeVal || !byType[typeVal]) return;

      const tpl = byType[typeVal];

      const labelInp = row.querySelector('.ded-label');
      if (labelInp) {
        labelInp.value = tpl.label || '';
      }

      const amtInp = row.querySelector('.ded-amount');
      if (amtInp) {
        const num = typeof tpl.amount === 'number' ? tpl.amount : parseFloat(tpl.amount || '0');
        amtInp.value = !isNaN(num) && num > 0 ? String(num) : '';
      }

      const scopeSel = row.querySelector('.ded-scope');
      if (scopeSel && tpl.scope) {
        scopeSel.value = tpl.scope;
      }

      const recurCb = row.querySelector('.ded-recurring');
      if (recurCb) {
        recurCb.checked = !!tpl.recurring;
      }

      const rolesBox = row.querySelector('.ded-scope-roles');
      if (rolesBox && Array.isArray(tpl.roles)) {
        const ids = tpl.roles.map(v => parseInt(v, 10)).filter(v => v > 0);
        rolesBox.querySelectorAll('input.ded-role-checkbox[data-role-id]').forEach(cb => {
          const id = parseInt(cb.dataset.roleId || '0', 10);
          cb.checked = ids.includes(id);
        });
      }

      initDeductionRow(row);
    });
  }

  function initDeductionRow(row) {
    if (!row) return;
    const scopeSel = row.querySelector('.ded-scope');
    const rolesBox = row.querySelector('.ded-scope-roles');
    const wrapper = row.querySelector('.ded-scope-wrapper');

    const updateVisibility = () => {
      if (!rolesBox || !scopeSel) return;
      const v = (scopeSel.value || '').toLowerCase();
      // If scope is not per_role, ensure panel is hidden
      if (v !== 'per_role') {
        rolesBox.style.display = 'none';
      }
    };

    if (scopeSel && !scopeSel._wiredScope) {
      scopeSel._wiredScope = true;
      scopeSel.addEventListener('change', updateVisibility);
    }

    if (rolesBox && !rolesBox._initialized && Object.keys(rolesMeta).length > 0) {
      rolesBox.innerHTML = '';
      Object.values(rolesMeta).forEach(r => {
        const labelEl = document.createElement('label');
        labelEl.style.display = 'inline-flex';
        labelEl.style.alignItems = 'center';
        labelEl.style.gap = '2px';
        labelEl.style.fontSize = '0.75rem';
        labelEl.style.marginRight = '4px';

        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'ded-role-checkbox';
        cb.dataset.roleId = String(r.id);
        cb.checked = true;

        labelEl.appendChild(cb);
        labelEl.appendChild(document.createTextNode(r.name));
        rolesBox.appendChild(labelEl);
      });
      rolesBox._initialized = true;
    }

    // Hover behavior: show floating panel only when scope = Per Role
    if (wrapper && !wrapper._wiredHover && rolesBox) {
      wrapper._wiredHover = true;
      wrapper.addEventListener('mouseenter', () => {
        if (!scopeSel) return;
        const v = (scopeSel.value || '').toLowerCase();
        if (v === 'per_role') {
          rolesBox.style.display = 'block';
        }
      });
      wrapper.addEventListener('mouseleave', () => {
        rolesBox.style.display = 'none';
      });
    }

    const removeBtn = row.querySelector('.btn-remove-row');
    if (removeBtn && !removeBtn._wiredRemove) {
      removeBtn._wiredRemove = true;
      removeBtn.addEventListener('click', () => {
        row.remove();
      });
    }

    updateVisibility();
  }

  function formatPhp(n) {
    const num = typeof n === 'string' ? parseFloat(n) : n;
    if (isNaN(num)) return '₱0.00';
    return '₱' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  async function loadFinalizedPayroll() {
    if (!finalizedBody) return;
    try {
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;

      finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">Loading...</td></tr>';

      if (!start || !end) {
        finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">Set a pay period first to load finalized records.</td></tr>';
        return;
      }

      const body = { start, end, role_id };
      const res = await fetch(`${API}?action=list_period_payroll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to load finalized payroll records');

      const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
      finalizedBody.innerHTML = '';

      if (!rows.length) {
        finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">No payroll records found for this period.</td></tr>';
        return;
      }

      rows.forEach((r) => {
        const tr = document.createElement('tr');
        const isPaid = String(r.paid_status || '').toLowerCase() === 'paid';
        const paidLabel = isPaid ? 'Paid' : 'Unpaid';
        const paymentDate = r.payment_date || '-';
        const actionHtml = isPaid
          ? '<span style="font-size:0.8rem; color:#6b7280;">—</span>'
          : `<button type="button" class="mark-paid-btn" data-payroll-id="${r.id}">Mark as Paid</button>`;

        tr.innerHTML = `
          <td>${r.employee}</td>
          <td>${r.role}</td>
          <td>${r.period}</td>
          <td>${formatPhp(r.net)}</td>
          <td>${paidLabel}</td>
          <td>${paymentDate}</td>
          <td>${actionHtml}</td>
        `;
        finalizedBody.appendChild(tr);
      });
    } catch (e) {
      if (finalizedBody) {
        finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#b91c1c;">Error loading finalized records</td></tr>';
      }
      show(e.message || 'Failed to load finalized payroll records', 'error');
    }
  }

  async function recalcPreview() {
    try {
      saveRecurringTemplates();
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

            const totalDed = formatPhp(r.deductions);
            const ph = r.philhealth !== undefined ? formatPhp(r.philhealth) : '₱0.00';
            const sss = r.sss !== undefined ? formatPhp(r.sss) : '₱0.00';
            const pg = r.pagibig !== undefined ? formatPhp(r.pagibig) : '₱0.00';
            const tax = r.tax !== undefined ? formatPhp(r.tax) : '₱0.00';
            const other = r.manual_other !== undefined ? formatPhp(r.manual_other) : '₱0.00';

            const hasBreakdown = r.philhealth !== undefined || r.sss !== undefined || r.pagibig !== undefined || r.tax !== undefined || r.manual_other !== undefined;

            const dedCell = hasBreakdown
              ? `${totalDed}<div style="font-size:0.75rem;color:#6b7280;margin-top:2px;">PH: ${ph} • SSS: ${sss} • Pag-IBIG: ${pg} • Tax: ${tax} • Other: ${other}</div>`
              : totalDed;

            tr.innerHTML = `
              <td>${r.employee}</td>
              <td>${r.role}</td>
              <td>${r.hours_days}</td>
              <td>${formatPhp(r.gross)}</td>
              <td>${dedCell}</td>
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

  async function recalcThirteenth() {
    if (!thirteenthBody) return;
    try {
      const yearVal = thirteenthYearInput?.value || '';
      const year = parseInt(yearVal, 10) || new Date().getFullYear();
      const as_of = thirteenthAsOfInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;

      const body = { year, as_of, role_id };
      const res = await fetch(`${API}?action=thirteenth_preview`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to compute 13th month');

      thirteenthBody.innerHTML = '';
      const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
      if (rows.length === 0) {
        thirteenthBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">No results</td></tr>';
        return;
      }

      rows.forEach((r) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.employee}</td>
          <td>${r.role}</td>
          <td>${formatPhp(r.basic_year)}</td>
          <td>${formatPhp(r.thirteenth)}</td>
        `;
        thirteenthBody.appendChild(tr);
      });

      show('13th month preview updated.');
    } catch (e) {
      if (thirteenthBody) {
        thirteenthBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#b91c1c;">Error loading 13th month data</td></tr>';
      }
      show(e.message || 'Failed to compute 13th month', 'error');
    }
  }

  async function finalizePayroll(force = false) {
    try {
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;
      if (!start || !end) {
        show('Start and end dates are required to finalize payroll.', 'error');
        return;
      }

      const label = `${start || '—'} to ${end || '—'}`;
      if (!window.confirm(`Finalize payroll for ${label}? This will save records for each employee in this period.`)) {
        return;
      }

      saveRecurringTemplates();

      const body = { start, end, role_id, deductions: collectDeductions(), force: !!force };
      const res = await fetch(`${API}?action=finalize_payroll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) {
        if (!force && out.code === 'EXISTING_PERIOD') {
          const extra = out.data && typeof out.data.count === 'number' ? ` (existing records: ${out.data.count})` : '';
          if (window.confirm((out.message || 'Payroll already exists for this period.') + `${extra}\n\nProceed and overwrite existing records?`)) {
            await finalizePayroll(true);
            return;
          }
        }
        throw new Error(out.message || 'Failed to finalize payroll');
      }

      show(out.message || 'Payroll finalized.');
      await recalcPreview();
      await loadFinalizedPayroll();
    } catch (e) {
      show(e.message || 'Failed to finalize payroll', 'error');
    }
  }

  function makeDeductionRow() {
    const div = document.createElement('div');
    div.className = 'deduction-row';
    div.innerHTML = `
      <select class="ded-type">
        <option>SSS</option>
        <option>PhilHealth</option>
        <option>Pag-IBIG</option>
        <option>Tax</option>
        <option>Loan</option>
        <option>Other</option>
      </select>
      <input type="text" class="ded-label" placeholder="e.g., Union Fee" />
      <input type="number" step="0.01" class="ded-amount" placeholder="0.00" />
      <div class="ded-scope-wrapper">
        <select class="ded-scope">
          <option value="per_employee">Per Employee</option>
          <option value="per_role">Per Role</option>
          <option value="global">Global</option>
        </select>
        <div class="ded-scope-roles"></div>
      </div>
      <label class="recurring"><input type="checkbox" class="ded-recurring" /> Yes</label>
      <button type="button" class="btn-remove-row">Remove</button>
    `;
    initDeductionRow(div);
    return div;
  }

  // Events
  btnRecalc && btnRecalc.addEventListener('click', () => {
    recalcPreview();
    loadFinalizedPayroll();
  });
  btnAddDed && btnAddDed.addEventListener('click', () => {
    if (!dedRows) return;
    const row = makeDeductionRow();
    dedRows.appendChild(row);
  });
  roleFilter && roleFilter.addEventListener('change', () => {
    syncRoleFiltersFromSelection();
    recalcPreview();
    recalcThirteenth();
    loadFinalizedPayroll();
  });

  btnFinalize && btnFinalize.addEventListener('click', () => finalizePayroll(false));

  btnRefreshFinalized && btnRefreshFinalized.addEventListener('click', loadFinalizedPayroll);

  btnMarkPeriodPaid && btnMarkPeriodPaid.addEventListener('click', async () => {
    const start = startInput?.value || '';
    const end = endInput?.value || '';
    const role_id = parseInt(roleFilter?.value || '0', 10) || 0;

    if (!start || !end) {
      show('Start and end dates are required to mark a period as Paid.', 'error');
      return;
    }

    const label = `${start || '—'} to ${end || '—'}`;
    if (!window.confirm(`Mark ALL Unpaid payrolls for ${label}${role_id ? ' (current role only)' : ''} as Paid?`)) {
      return;
    }

    try {
      const body = { start, end, role_id };
      const res = await fetch(`${API}?action=mark_period_paid`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to mark period as Paid');

      show(out.message || 'Payroll period marked as Paid.');
      await loadFinalizedPayroll();
    } catch (e) {
      show(e.message || 'Failed to mark period as Paid', 'error');
    }
  });

  finalizedBody && finalizedBody.addEventListener('click', async (ev) => {
    const target = ev.target;
    if (!(target instanceof HTMLElement)) return;
    const btn = target.closest('button[data-payroll-id]');
    if (!btn) return;
    const id = parseInt(btn.getAttribute('data-payroll-id') || '0', 10) || 0;
    if (!id) return;

    if (!window.confirm('Mark this payroll as Paid?')) {
      return;
    }

    try {
      const res = await fetch(`${API}?action=mark_payroll_paid`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to mark payroll as Paid');

      show(out.message || 'Payroll marked as Paid.');
      await loadFinalizedPayroll();
    } catch (e) {
      show(e.message || 'Failed to mark payroll as Paid', 'error');
    }
  });

  btnThirteenthRecalc && btnThirteenthRecalc.addEventListener('click', recalcThirteenth);

  // Wire initial deduction rows (first row)
  dedRows?.querySelectorAll('.deduction-row').forEach(initDeductionRow);

  // Init
  initDefaultPeriod();
  initThirteenthDefaults();
  loadRoles();
  loadNextPayrollPerRole();
  recalcPreview();
  recalcThirteenth();
  loadFinalizedPayroll();
});

