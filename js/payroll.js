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
  const thirteenthBody = document.getElementById('thirteenth-body');
  const btnThirteenthRecalc = document.getElementById('btn-thirteenth-recalc');
  const thirteenthRoleSelect = document.getElementById('thirteenth-role');
  const thirteenthTotalBasic = document.getElementById('thirteenth-total-basic');
  const thirteenthTotalThirteenth = document.getElementById('thirteenth-total-13th');

  // Tables
  const roleNextBody = document.getElementById('role-next-payroll-body');
  const previewBody = document.getElementById('payroll-preview-body');
  const finalizedBody = document.getElementById('payroll-finalized-body');
  const thirteenthFinalizedBody = document.getElementById('thirteenth-finalized-body');

  // Pagination controls
  const roleNextPageSize = document.getElementById('role-next-page-size');
  const roleNextPagePrev = document.getElementById('role-next-page-prev');
  const roleNextPageNext = document.getElementById('role-next-page-next');
  const roleNextPageInfo = document.getElementById('role-next-page-info');

  const previewPageSize = document.getElementById('preview-page-size');
  const previewPagePrev = document.getElementById('preview-page-prev');
  const previewPageNext = document.getElementById('preview-page-next');
  const previewPageInfo = document.getElementById('preview-page-info');

  const finalizedPageSize = document.getElementById('finalized-page-size');
  const finalizedPagePrev = document.getElementById('finalized-page-prev');
  const finalizedPageNext = document.getElementById('finalized-page-next');
  const finalizedPageInfo = document.getElementById('finalized-page-info');

  const thirteenthPageSize = document.getElementById('thirteenth-page-size');
  const thirteenthPagePrev = document.getElementById('thirteenth-page-prev');
  const thirteenthPageNext = document.getElementById('thirteenth-page-next');
  const thirteenthPageInfo = document.getElementById('thirteenth-page-info');

  const thirteenthFinalizedPageSize = document.getElementById('thirteenth-finalized-page-size');
  const thirteenthFinalizedPagePrev = document.getElementById('thirteenth-finalized-page-prev');
  const thirteenthFinalizedPageNext = document.getElementById('thirteenth-finalized-page-next');
  const thirteenthFinalizedPageInfo = document.getElementById('thirteenth-finalized-page-info');

  // Deductions
  const dedRows = document.getElementById('deduction-rows');
  const btnAddDed = document.getElementById('btn-add-deduction');

  const statusBox = document.getElementById('status-message');
  const btnRefreshFinalized = document.getElementById('btn-refresh-finalized');
  const btnMarkPeriodPaid = document.getElementById('btn-mark-period-paid');
  const btnThirteenthFinalize = document.getElementById('btn-thirteenth-finalize');
  const btnThirteenthRefresh = document.getElementById('btn-thirteenth-refresh');
  const btnThirteenthMarkYearPaid = document.getElementById('btn-thirteenth-mark-year-paid');

  const btnExportPreview = document.getElementById('btn-export-preview');
  const btnExportFinalized = document.getElementById('btn-export-finalized');
  const btnExportThirteenthPreview = document.getElementById('btn-export-thirteenth-preview');
  const btnExportThirteenthFinalized = document.getElementById('btn-export-thirteenth-finalized');

  const payslipModal = document.getElementById('payslip-modal');
  const payslipBody = document.getElementById('payslip-body');
  const payslipClose = document.getElementById('payslip-close');
  const payslipPrint = document.getElementById('payslip-print');

  const rolesMeta = {};
  const rolePeriods = {};

  // Paging state
  let roleNextRows = [];
  let roleNextPage = 1;
  let previewRows = [];
  let previewPage = 1;
  let finalizedRows = [];
  let finalizedPage = 1;
  let thirteenthRows = [];
  let thirteenthPage = 1;
  let thirteenthFinalizedRows = [];
  let thirteenthFinalizedPage = 1;

  function show(msg, type = 'success') {
    if (!statusBox) return;
    statusBox.textContent = msg;
    statusBox.className = 'status-message ' + type;
    statusBox.classList.add('show');
    if (statusBox._hideTimer) clearTimeout(statusBox._hideTimer);
    statusBox._hideTimer = setTimeout(() => statusBox.classList.remove('show'), 2500);
  }

  function showConfirmation(message, confirmText = 'Confirm', confirmColor = 'blue') {
    return new Promise((resolve) => {
      const confirmationModal = document.getElementById('confirmation-modal');
      const confirmationMessage = document.getElementById('confirmation-message');
      const confirmationConfirmBtn = document.getElementById('confirmation-confirm-btn');
      const confirmationCancelBtn = document.getElementById('confirmation-cancel-btn');
      const confirmationCloseX = document.getElementById('confirmation-close-x');

      // Fallback to native confirm if modal elements are missing
      if (!confirmationModal || !confirmationMessage || !confirmationConfirmBtn || !confirmationCancelBtn || !confirmationCloseX) {
        const ok = window.confirm(message);
        resolve(!!ok);
        return;
      }

      confirmationMessage.textContent = message;
      confirmationConfirmBtn.textContent = confirmText;
      confirmationConfirmBtn.className = `px-4 py-2 bg-${confirmColor}-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-${confirmColor}-600 focus:outline-none focus:ring-2 focus:ring-${confirmColor}-300`;

      confirmationModal.classList.remove('hidden');
      confirmationModal.setAttribute('aria-hidden', 'false');

      const handleConfirm = () => {
        cleanup();
        resolve(true);
      };

      const handleCancel = () => {
        cleanup();
        resolve(false);
      };

      const cleanup = () => {
        confirmationModal.classList.add('hidden');
        confirmationModal.setAttribute('aria-hidden', 'true');
        confirmationConfirmBtn.removeEventListener('click', handleConfirm);
        confirmationCancelBtn.removeEventListener('click', handleCancel);
        confirmationCloseX.removeEventListener('click', handleCancel);
      };

      confirmationConfirmBtn.addEventListener('click', handleConfirm);
      confirmationCancelBtn.addEventListener('click', handleCancel);
      confirmationCloseX.addEventListener('click', handleCancel);

      const handleEscape = (e) => {
        if (e.key === 'Escape' && !confirmationModal.classList.contains('hidden')) {
          handleCancel();
          document.removeEventListener('keydown', handleEscape);
        }
      };
      document.addEventListener('keydown', handleEscape);
    });
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
  }

  function updatePeriodFromFrequency() {
    if (!freqSelect) return;

    const freq = String(freqSelect.value || '').toLowerCase();

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
          // unknown frequency: same day
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

  function syncRoleFiltersFromSelection() {
    if (!roleFilter || !freqSelect) return;

    const roleId = parseInt(roleFilter.value || '0', 10) || 0;
    const currentFreq = String(freqSelect.value || '').toLowerCase();

    if (!roleId) {
      // All roles: keep whatever frequency is selected and just update the period
      updatePeriodFromFrequency();
      return;
    }

    const meta = rolesMeta[roleId];
    const roleFreq = (meta && meta.payroll_frequency) ? String(meta.payroll_frequency).toLowerCase() : '';

    // If no frequency chosen yet, default to the role's configured frequency
    if (!currentFreq && roleFreq && freqSelect.querySelector(`option[value="${roleFreq}"]`)) {
      freqSelect.value = roleFreq;
    }

    // Always update the period based on the currently selected frequency
    updatePeriodFromFrequency();
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

      if (thirteenthRoleSelect) {
        const first13 = thirteenthRoleSelect.querySelector('option')?.outerHTML || '<option value="">All Roles</option>';
        thirteenthRoleSelect.innerHTML = first13 + roles
          .map(r => `<option value="${r.id}">${r.name}</option>`)
          .join('');
      }

      if (dedRows) {
        dedRows.querySelectorAll('.deduction-row').forEach(initDeductionRow);
        applyRecurringTemplates();
      }

      syncRoleFiltersFromSelection();
    } catch {}
  }

  async function loadNextPayrollPerRole() {
    if (!roleNextBody) return;
    try {
      const res = await fetch(`${API}?action=next_payroll_per_role`);
      const out = await res.json();

      roleNextRows = [];
      roleNextBody.innerHTML = '';

      if (!out.success) {
        roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">Failed to load</td></tr>';
        if (roleNextPageInfo) roleNextPageInfo.textContent = 'Page 1 of 1';
        if (roleNextPagePrev) roleNextPagePrev.disabled = true;
        if (roleNextPageNext) roleNextPageNext.disabled = true;
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

      roleNextRows = list;
      roleNextPage = 1;
      renderRoleNextPage();

      // Update frequency/period display for current selection if any
      syncRoleFiltersFromSelection();
    } catch {
      roleNextRows = [];
      if (roleNextBody) roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">Error</td></tr>';
      if (roleNextPageInfo) roleNextPageInfo.textContent = 'Page 1 of 1';
      if (roleNextPagePrev) roleNextPagePrev.disabled = true;
      if (roleNextPageNext) roleNextPageNext.disabled = true;
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
    const removeBtn = row.querySelector('.btn-remove-row');

    if (removeBtn) {
      removeBtn.onclick = () => {
        if (row.parentElement) {
          row.parentElement.removeChild(row);
        }
      };
    }

    const buildRoleCheckboxes = () => {
      if (!rolesBox) return;
      rolesBox.innerHTML = '';
      const ids = Object.keys(rolesMeta || {});
      if (!ids.length) return;
      ids.forEach((id) => {
        const meta = rolesMeta[id];
        if (!meta) return;
        const label = document.createElement('label');
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'ded-role-checkbox';
        cb.dataset.roleId = String(meta.id);
        label.appendChild(cb);
        label.appendChild(document.createTextNode(meta.name || ''));
        rolesBox.appendChild(label);
      });
    };

    buildRoleCheckboxes();

    const applyScopeVisibility = () => {
      if (!rolesBox || !scopeSel) return;
      const val = String(scopeSel.value || '').toLowerCase();
      rolesBox.style.display = val === 'per_role' ? 'block' : 'none';
    };

    if (scopeSel) {
      scopeSel.onchange = applyScopeVisibility;
      applyScopeVisibility();
    }
  }

  function formatPhp(n) {
    const num = typeof n === 'string' ? parseFloat(n) : n;
    if (isNaN(num)) return '₱0.00';
    return '₱' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function buildCsv(columns, rows) {
    const escape = (value) => {
      if (value === null || value === undefined) return '';
      const s = String(value);
      if (/[",\n]/.test(s)) {
        return '"' + s.replace(/"/g, '""') + '"';
      }
      return s;
    };

    const lines = [];
    lines.push(columns.map(c => escape(c.label)).join(','));
    rows.forEach(row => {
      const vals = columns.map(c => escape(c.value(row)));
      lines.push(vals.join(','));
    });
    return lines.join('\r\n');
  }

  function downloadCsv(filename, csv) {
    if (!csv) return;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function renderRoleNextPage() {
    if (!roleNextBody) return;
    const total = roleNextRows.length;
    const size = parseInt(roleNextPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil(total / size));

    if (!total) {
      roleNextBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">No data</td></tr>';
      if (roleNextPageInfo) roleNextPageInfo.textContent = 'Page 1 of 1';
      if (roleNextPagePrev) roleNextPagePrev.disabled = true;
      if (roleNextPageNext) roleNextPageNext.disabled = true;
      return;
    }

    if (roleNextPage < 1) roleNextPage = 1;
    if (roleNextPage > totalPages) roleNextPage = totalPages;

    const startIdx = (roleNextPage - 1) * size;
    const pageRows = roleNextRows.slice(startIdx, startIdx + size);

    roleNextBody.innerHTML = '';
    pageRows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.role_name}</td>
        <td>${(r.frequency || '').replace(/^./, c=>c.toUpperCase())}</td>
        <td>${r.next_payroll_date}</td>
        <td>${r.period_start} to ${r.period_end}</td>
      `;
      roleNextBody.appendChild(tr);
    });

    if (roleNextPageInfo) roleNextPageInfo.textContent = `Page ${roleNextPage} of ${totalPages}`;
    if (roleNextPagePrev) roleNextPagePrev.disabled = roleNextPage <= 1;
    if (roleNextPageNext) roleNextPageNext.disabled = roleNextPage >= totalPages;
  }

  function renderPreviewPage() {
    if (!previewBody) return;
    const total = previewRows.length;
    const size = parseInt(previewPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil(total / size));

    if (!total) {
      previewBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">No results</td></tr>';
      if (previewPageInfo) previewPageInfo.textContent = 'Page 1 of 1';
      if (previewPagePrev) previewPagePrev.disabled = true;
      if (previewPageNext) previewPageNext.disabled = true;
      return;
    }

    if (previewPage < 1) previewPage = 1;
    if (previewPage > totalPages) previewPage = totalPages;

    const startIdx = (previewPage - 1) * size;
    const pageRows = previewRows.slice(startIdx, startIdx + size);

    previewBody.innerHTML = '';
    pageRows.forEach(r => {
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

    if (previewPageInfo) previewPageInfo.textContent = `Page ${previewPage} of ${totalPages}`;
    if (previewPagePrev) previewPagePrev.disabled = previewPage <= 1;
    if (previewPageNext) previewPageNext.disabled = previewPage >= totalPages;
  }

  function renderFinalizedPage() {
    if (!finalizedBody) return;
    const total = finalizedRows.length;
    const size = parseInt(finalizedPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil(total / size));

    if (!total) {
      finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">No payroll records found for this period.</td></tr>';
      if (finalizedPageInfo) finalizedPageInfo.textContent = 'Page 1 of 1';
      if (finalizedPagePrev) finalizedPagePrev.disabled = true;
      if (finalizedPageNext) finalizedPageNext.disabled = true;
      return;
    }

    if (finalizedPage < 1) finalizedPage = 1;
    if (finalizedPage > totalPages) finalizedPage = totalPages;

    const startIdx = (finalizedPage - 1) * size;
    const pageRows = finalizedRows.slice(startIdx, startIdx + size);

    finalizedBody.innerHTML = '';
    pageRows.forEach((r, idx) => {
      const tr = document.createElement('tr');
      const isPaid = String(r.paid_status || '').toLowerCase() === 'paid';
      const paidLabel = isPaid ? 'Paid' : 'Unpaid';
      const paymentDate = r.payment_date || '-';
      const globalIndex = startIdx + idx;
      const payslipBtnHtml = `<button type="button" class="pagination-btn" data-finalized-index="${globalIndex}" style="padding:4px 10px; font-size:0.75rem; margin-left:4px;">View Payslip</button>`;
      const actionHtml = isPaid
        ? payslipBtnHtml
        : `<button type="button" class="mark-paid-btn" data-payroll-id="${r.id}">Mark as Paid</button>${payslipBtnHtml}`;

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

    if (finalizedPageInfo) finalizedPageInfo.textContent = `Page ${finalizedPage} of ${totalPages}`;
    if (finalizedPagePrev) finalizedPagePrev.disabled = finalizedPage <= 1;
    if (finalizedPageNext) finalizedPageNext.disabled = finalizedPage >= totalPages;
  }

  function renderThirteenthPreviewPage() {
    if (!thirteenthBody) return;
    const total = thirteenthRows.length;
    const size = parseInt(thirteenthPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil(total / size));

    if (!total) {
      thirteenthBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280;">No results</td></tr>';
      if (thirteenthPageInfo) thirteenthPageInfo.textContent = 'Page 1 of 1';
      if (thirteenthPagePrev) thirteenthPagePrev.disabled = true;
      if (thirteenthPageNext) thirteenthPageNext.disabled = true;
      return;
    }

    if (thirteenthPage < 1) thirteenthPage = 1;
    if (thirteenthPage > totalPages) thirteenthPage = totalPages;

    const startIdx = (thirteenthPage - 1) * size;
    const pageRows = thirteenthRows.slice(startIdx, startIdx + size);

    thirteenthBody.innerHTML = '';
    pageRows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.employee}</td>
        <td>${r.role}</td>
        <td>${formatPhp(r.basic_year)}</td>
        <td>${formatPhp(r.thirteenth)}</td>
      `;
      thirteenthBody.appendChild(tr);
    });

    if (thirteenthPageInfo) thirteenthPageInfo.textContent = `Page ${thirteenthPage} of ${totalPages}`;
    if (thirteenthPagePrev) thirteenthPagePrev.disabled = thirteenthPage <= 1;
    if (thirteenthPageNext) thirteenthPageNext.disabled = thirteenthPage >= totalPages;
  }

  function renderThirteenthFinalizedPage() {
    if (!thirteenthFinalizedBody) return;
    const total = thirteenthFinalizedRows.length;
    const size = parseInt(thirteenthFinalizedPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil(total / size));

    if (!total) {
      thirteenthFinalizedBody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#6b7280;">No 13th month records found for this year/role.</td></tr>';
      if (thirteenthFinalizedPageInfo) thirteenthFinalizedPageInfo.textContent = 'Page 1 of 1';
      if (thirteenthFinalizedPagePrev) thirteenthFinalizedPagePrev.disabled = true;
      if (thirteenthFinalizedPageNext) thirteenthFinalizedPageNext.disabled = true;
      return;
    }

    if (thirteenthFinalizedPage < 1) thirteenthFinalizedPage = 1;
    if (thirteenthFinalizedPage > totalPages) thirteenthFinalizedPage = totalPages;

    const startIdx = (thirteenthFinalizedPage - 1) * size;
    const pageRows = thirteenthFinalizedRows.slice(startIdx, startIdx + size);

    thirteenthFinalizedBody.innerHTML = '';
    pageRows.forEach(r => {
      const tr = document.createElement('tr');
      const isPaid = String(r.paid_status || '').toLowerCase() === 'paid';
      const paidLabel = isPaid ? 'Paid' : 'Unpaid';
      const paymentDate = r.payment_date || '-';
      const actionHtml = isPaid
        ? '<span style="font-size:0.8rem; color:#6b7280;">—</span>'
        : `<button type="button" class="mark-paid-btn" data-thirteenth-id="${r.id}">Mark as Paid</button>`;

      tr.innerHTML = `
        <td>${r.employee}</td>
        <td>${r.role}</td>
        <td>${r.year}</td>
        <td>${r.period}</td>
        <td>${formatPhp(r.thirteenth)}</td>
        <td>${paidLabel}</td>
        <td>${paymentDate}</td>
        <td>${actionHtml}</td>
      `;
      thirteenthFinalizedBody.appendChild(tr);
    });

    if (thirteenthFinalizedPageInfo) thirteenthFinalizedPageInfo.textContent = `Page ${thirteenthFinalizedPage} of ${totalPages}`;
    if (thirteenthFinalizedPagePrev) thirteenthFinalizedPagePrev.disabled = thirteenthFinalizedPage <= 1;
    if (thirteenthFinalizedPageNext) thirteenthFinalizedPageNext.disabled = thirteenthFinalizedPage >= totalPages;
  }

  async function loadFinalizedPayroll() {
    if (!finalizedBody) return;
    try {
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;
      const frequency = (freqSelect?.value || '').toLowerCase();

      finalizedRows = [];
      finalizedPage = 1;
      finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">Loading...</td></tr>';

      if (!start || !end) {
        finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#6b7280;">Set a pay period first to load finalized records.</td></tr>';
        if (finalizedPageInfo) finalizedPageInfo.textContent = 'Page 1 of 1';
        if (finalizedPagePrev) finalizedPagePrev.disabled = true;
        if (finalizedPageNext) finalizedPageNext.disabled = true;
        return;
      }

      const body = { start, end, role_id, frequency };
      const res = await fetch(`${API}?action=list_period_payroll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to load finalized payroll records');

      const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
      finalizedRows = rows;
      finalizedPage = 1;
      renderFinalizedPage();
    } catch (e) {
      finalizedRows = [];
      if (finalizedBody) {
        finalizedBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#b91c1c;">Error loading finalized records</td></tr>';
      }
      if (finalizedPageInfo) finalizedPageInfo.textContent = 'Page 1 of 1';
      if (finalizedPagePrev) finalizedPagePrev.disabled = true;
      if (finalizedPageNext) finalizedPageNext.disabled = true;
      show(e.message || 'Failed to load finalized payroll records', 'error');
    }
  }

  async function recalcPreview() {
    try {
      saveRecurringTemplates();
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;
      const frequency = (freqSelect?.value || '').toLowerCase();

      if (!start || !end) {
        show('Start and end dates are required to compute preview.', 'error');
        return;
      }

      if (sumPeriod) sumPeriod.textContent = `${start || '—'} to ${end || '—'}`;
      const body = { start, end, role_id, frequency, deductions: collectDeductions() };
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

      // Update table with paging
      if (previewBody) {
        const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
        previewRows = rows;
        previewPage = 1;
        renderPreviewPage();
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
      const role_id = parseInt(thirteenthRoleSelect?.value || '0', 10) || 0;

      const body = { year, role_id };
      const res = await fetch(`${API}?action=thirteenth_preview`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to compute 13th month');

      const summary = out.data?.summary || {};
      if (thirteenthTotalBasic) {
        thirteenthTotalBasic.textContent = formatPhp(summary.total_basic || 0);
      }
      if (thirteenthTotalThirteenth) {
        thirteenthTotalThirteenth.textContent = formatPhp(summary.total_thirteenth || 0);
      }

      const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
      thirteenthRows = rows;
      thirteenthPage = 1;
      renderThirteenthPreviewPage();

      show('13th month preview updated.');
    } catch (e) {
      if (thirteenthBody) {
        thirteenthBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#b91c1c;">Error loading 13th month data</td></tr>';
      }
      show(e.message || 'Failed to compute 13th month', 'error');
    }
  }

  async function finalizeThirteenth() {
    try {
      const yearVal = thirteenthYearInput?.value || '';
      const year = parseInt(yearVal, 10) || new Date().getFullYear();
      const role_id = parseInt(thirteenthRoleSelect?.value || '0', 10) || 0;

      if (!year) {
        show('Year is required to finalize 13th month.', 'error');
        return;
      }

      const label = `Year ${year}${role_id ? ' (current role only)' : ''}`;
      const confirmed = await showConfirmation(
        `Finalize 13th month for ${label}? This will save 13th month records for eligible employees.`,
        'Finalize 13th Month',
        'blue'
      );
      if (!confirmed) {
        return;
      }

      const body = { year, role_id };
      const res = await fetch(`${API}?action=thirteenth_finalize`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to finalize 13th month');

      show(out.message || '13th month finalized.');
      await recalcThirteenth();
      await loadThirteenthFinalized();
    } catch (e) {
      show(e.message || 'Failed to finalize 13th month', 'error');
    }
  }

  async function finalizePayroll(force = false) {
    try {
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      const role_id = parseInt(roleFilter?.value || '0', 10) || 0;
      const frequency = (freqSelect?.value || '').toLowerCase();
      if (!start || !end) {
        show('Start and end dates are required to finalize payroll.', 'error');
        return;
      }

      const label = `${start || '—'} to ${end || '—'}`;
      const confirmed = await showConfirmation(
        `Finalize payroll for ${label}? This will save records for each employee in this period.`,
        'Finalize Payroll',
        'blue'
      );
      if (!confirmed) {
        return;
      }

      saveRecurringTemplates();

      const body = { start, end, role_id, frequency, deductions: collectDeductions(), force: !!force };
      const res = await fetch(`${API}?action=finalize_payroll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) {
        if (!force && out.code === 'EXISTING_PERIOD') {
          const extra = out.data && typeof out.data.count === 'number' ? ` (existing records: ${out.data.count})` : '';
          const overwriteMsg = (out.message || 'Payroll already exists for this period.') + `${extra}\n\nProceed and overwrite existing records?`;
          const overwrite = await showConfirmation(overwriteMsg, 'Overwrite Payroll', 'red');
          if (overwrite) {
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

  async function loadThirteenthFinalized() {
    if (!thirteenthFinalizedBody) return;
    try {
      const yearVal = thirteenthYearInput?.value || '';
      const year = parseInt(yearVal, 10) || new Date().getFullYear();
      const role_id = parseInt(thirteenthRoleSelect?.value || '0', 10) || 0;

      if (!year) {
        thirteenthFinalizedBody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#6b7280;">Set a year to load 13th month records.</td></tr>';
        return;
      }

      thirteenthFinalizedBody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#6b7280;">Loading...</td></tr>';

      const body = { year, role_id };
      const res = await fetch(`${API}?action=list_thirteenth`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to load 13th month records');

      const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
      thirteenthFinalizedRows = rows.map(r => ({
        ...r,
        year: r.year || year,
      }));
      thirteenthFinalizedPage = 1;
      renderThirteenthFinalizedPage();
    } catch (e) {
      if (thirteenthFinalizedBody) {
        thirteenthFinalizedBody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#b91c1c;">Error loading 13th month records</td></tr>';
      }
      show(e.message || 'Failed to load 13th month records', 'error');
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
    loadFinalizedPayroll();
  });

  freqSelect && freqSelect.addEventListener('change', () => {
    updatePeriodFromFrequency();
    recalcPreview();
    loadFinalizedPayroll();
  });

  btnFinalize && btnFinalize.addEventListener('click', () => finalizePayroll(false));

  btnRefreshFinalized && btnRefreshFinalized.addEventListener('click', loadFinalizedPayroll);

  btnMarkPeriodPaid && btnMarkPeriodPaid.addEventListener('click', async () => {
    const start = startInput?.value || '';
    const end = endInput?.value || '';
    const role_id = parseInt(roleFilter?.value || '0', 10) || 0;
    const frequency = (freqSelect?.value || '').toLowerCase();

    if (!start || !end) {
      show('Start and end dates are required to mark a period as Paid.', 'error');
      return;
    }

    const label = `${start || '—'} to ${end || '—'}`;
    const confirmed = await showConfirmation(
      `Mark ALL Unpaid payrolls for ${label}${role_id ? ' (current role only)' : ''} as Paid?`,
      'Mark ALL as Paid',
      'blue'
    );
    if (!confirmed) {
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

    const confirmed = await showConfirmation('Mark this payroll as Paid?', 'Mark as Paid', 'blue');
    if (!confirmed) {
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

  finalizedBody && finalizedBody.addEventListener('click', (ev) => {
    const target = ev.target;
    if (!(target instanceof HTMLElement)) return;
    const btn = target.closest('button[data-finalized-index]');
    if (!btn) return;
    const idx = parseInt(btn.getAttribute('data-finalized-index') || '0', 10) || 0;
    const row = finalizedRows[idx];
    if (!row) return;
    if (!payslipModal || !payslipBody) return;

    const gross = parseFloat(row.gross || '0') || 0;
    const phil = parseFloat(row.philhealth || '0') || 0;
    const sss = parseFloat(row.sss || '0') || 0;
    const pagibig = parseFloat(row.pagibig || '0') || 0;
    const other = parseFloat(row.other || '0') || 0;
    const totalDeductions = phil + sss + pagibig + other;
    const net = parseFloat(row.net || '0') || 0;
    const periodLabel = row.period || '';
    const parts = periodLabel.split(' to ');
    const periodStart = parts[0] || '';
    const periodEnd = parts[1] || '';
    const payStatus = row.paid_status || '';
    const paymentDate = row.payment_date || '-';

    const grossStr = formatPhp(gross).replace('₱', '');
    const philStr = formatPhp(phil).replace('₱', '');
    const sssStr = formatPhp(sss).replace('₱', '');
    const pagibigStr = formatPhp(pagibig).replace('₱', '');
    const otherStr = formatPhp(other).replace('₱', '');
    const totalDedStr = formatPhp(totalDeductions).replace('₱', '');
    const netStr = formatPhp(net).replace('₱', '');

    payslipBody.innerHTML = `
      <div class="payslip-sheet">
        <div class="ps-header">
          <div class="ps-company-name">ASIAN DEVELOPMENT FOUNDATION COLLEGE</div>
          <div class="ps-company-address">P. Burgos Street, Tacloban City</div>
        </div>

        <div class="ps-meta">
          <div class="ps-meta-col">
            <div class="ps-field"><span class="label">Employee Name:</span><span class="value">${row.employee || ''}</span></div>
            <div class="ps-field"><span class="label">Position:</span><span class="value">${row.role || ''}</span></div>
            <div class="ps-field"><span class="label">Department:</span><span class="value"></span></div>
          </div>
          <div class="ps-meta-col">
            <div class="ps-field"><span class="label">Employee ID:</span><span class="value"></span></div>
            <div class="ps-field"><span class="label">Pay Period:</span><span class="value">${periodLabel}</span></div>
            <div class="ps-field"><span class="label">Cutoff:</span><span class="value">${periodEnd}</span></div>
          </div>
        </div>

        <div class="ps-main">
          <div class="ps-box">
            <div class="ps-box-title">EARNINGS</div>
            <div class="ps-line-item"><span class="caption">Basic Pay</span><span class="currency">₱</span><span class="amount">${grossStr}</span></div>
            <div class="ps-line-item"><span class="caption">Overtime</span><span class="currency">₱</span><span class="amount"></span></div>
            <div class="ps-line-item"><span class="caption">Holiday Pay</span><span class="currency">₱</span><span class="amount"></span></div>
            <div class="ps-line-item"><span class="caption">Allowances</span><span class="currency">₱</span><span class="amount"></span></div>
            <div class="ps-line-item"><span class="caption">Others</span><span class="currency">₱</span><span class="amount"></span></div>
          </div>
          <div class="ps-box">
            <div class="ps-box-title">DEDUCTIONS</div>
            <div class="ps-line-item"><span class="caption">PhilHealth</span><span class="currency">₱</span><span class="amount">${philStr}</span></div>
            <div class="ps-line-item"><span class="caption">SSS</span><span class="currency">₱</span><span class="amount">${sssStr}</span></div>
            <div class="ps-line-item"><span class="caption">Pag-IBIG</span><span class="currency">₱</span><span class="amount">${pagibigStr}</span></div>
            <div class="ps-line-item"><span class="caption">Other Deductions</span><span class="currency">₱</span><span class="amount">${otherStr}</span></div>
            <div class="ps-line-item"><span class="caption">Total Deductions</span><span class="currency">₱</span><span class="amount">${totalDedStr}</span></div>
          </div>
        </div>

        <div class="ps-totals">
          <div class="ps-field"><span class="label">GROSS PAY:</span><span class="currency">₱</span><span class="value amount">${grossStr}</span></div>
          <div class="ps-field"><span class="label">TOTAL DEDUCTIONS:</span><span class="currency">₱</span><span class="value amount">${totalDedStr}</span></div>
          <div class="ps-field ps-net"><span class="label">NET PAY:</span><span class="currency">₱</span><span class="value amount">${netStr}</span></div>
          <div class="ps-status-row">
            <span class="label">Status:</span><span class="value">${payStatus}</span>
            <span class="label">Payment Date:</span><span class="value">${paymentDate}</span>
          </div>
        </div>

        <div class="ps-signatories">
          <div class="ps-signature-block">
            <div class="line"></div>
            <div class="caption">Prepared By</div>
          </div>
          <div class="ps-signature-block">
            <div class="line"></div>
            <div class="caption">Approved By</div>
          </div>
          <div class="ps-signature-block">
            <div class="line"></div>
            <div class="caption">Released By</div>
          </div>
          <div class="ps-signature-block">
            <div class="line"></div>
            <div class="caption">Employee Signature</div>
          </div>
        </div>

        <div class="ps-notes">
          <div class="label">Notes / Remarks:</div>
          <div class="notes-line"></div>
          <div class="notes-line"></div>
        </div>
      </div>
    `;

    payslipModal.classList.add('open');
  });

  // Pagination events
  roleNextPageSize && roleNextPageSize.addEventListener('change', () => {
    roleNextPage = 1;
    renderRoleNextPage();
  });
  roleNextPagePrev && roleNextPagePrev.addEventListener('click', () => {
    if (roleNextPage > 1) {
      roleNextPage--;
      renderRoleNextPage();
    }
  });
  roleNextPageNext && roleNextPageNext.addEventListener('click', () => {
    const size = parseInt(roleNextPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil((roleNextRows.length || 0) / size));
    if (roleNextPage < totalPages) {
      roleNextPage++;
      renderRoleNextPage();
    }
  });

  previewPageSize && previewPageSize.addEventListener('change', () => {
    previewPage = 1;
    renderPreviewPage();
  });
  previewPagePrev && previewPagePrev.addEventListener('click', () => {
    if (previewPage > 1) {
      previewPage--;
      renderPreviewPage();
    }
  });
  previewPageNext && previewPageNext.addEventListener('click', () => {
    const size = parseInt(previewPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil((previewRows.length || 0) / size));
    if (previewPage < totalPages) {
      previewPage++;
      renderPreviewPage();
    }
  });

  finalizedPageSize && finalizedPageSize.addEventListener('change', () => {
    finalizedPage = 1;
    renderFinalizedPage();
  });
  finalizedPagePrev && finalizedPagePrev.addEventListener('click', () => {
    if (finalizedPage > 1) {
      finalizedPage--;
      renderFinalizedPage();
    }
  });
  finalizedPageNext && finalizedPageNext.addEventListener('click', () => {
    const size = parseInt(finalizedPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil((finalizedRows.length || 0) / size));
    if (finalizedPage < totalPages) {
      finalizedPage++;
      renderFinalizedPage();
    }
  });

  btnThirteenthRecalc && btnThirteenthRecalc.addEventListener('click', recalcThirteenth);
  btnThirteenthFinalize && btnThirteenthFinalize.addEventListener('click', finalizeThirteenth);
  btnThirteenthRefresh && btnThirteenthRefresh.addEventListener('click', loadThirteenthFinalized);

  btnThirteenthMarkYearPaid && btnThirteenthMarkYearPaid.addEventListener('click', async () => {
    const yearVal = thirteenthYearInput?.value || '';
    const year = parseInt(yearVal, 10) || new Date().getFullYear();
    const role_id = parseInt(thirteenthRoleSelect?.value || '0', 10) || 0;

    if (!year) {
      show('Year is required to mark 13th month as Paid.', 'error');
      return;
    }

    const label = `Year ${year}${role_id ? ' (current role only)' : ''}`;
    const confirmed = await showConfirmation(
      `Mark ALL Unpaid 13th month records for ${label} as Paid?`,
      'Mark ALL as Paid',
      'blue'
    );
    if (!confirmed) {
      return;
    }

    try {
      const body = { year, role_id };
      const res = await fetch(`${API}?action=mark_thirteenth_year_paid`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to mark 13th month year as Paid');

      show(out.message || '13th month records marked as Paid.');
      await loadThirteenthFinalized();
    } catch (e) {
      show(e.message || 'Failed to mark 13th month year as Paid', 'error');
    }
  });

  thirteenthFinalizedBody && thirteenthFinalizedBody.addEventListener('click', async (ev) => {
    const target = ev.target;
    if (!(target instanceof HTMLElement)) return;
    const btn = target.closest('button[data-thirteenth-id]');
    if (!btn) return;
    const id = parseInt(btn.getAttribute('data-thirteenth-id') || '0', 10) || 0;
    if (!id) return;

    const confirmed = await showConfirmation('Mark this 13th month record as Paid?', 'Mark as Paid', 'blue');
    if (!confirmed) {
      return;
    }

    try {
      const res = await fetch(`${API}?action=mark_thirteenth_paid`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });

      const out = await res.json();
      if (!out.success) throw new Error(out.message || 'Failed to mark 13th month record as Paid');

      show(out.message || '13th month record marked as Paid.');
      await loadThirteenthFinalized();
    } catch (e) {
      show(e.message || 'Failed to mark 13th month record as Paid', 'error');
    }
  });

  thirteenthPageSize && thirteenthPageSize.addEventListener('change', () => {
    thirteenthPage = 1;
    renderThirteenthPreviewPage();
  });
  thirteenthPagePrev && thirteenthPagePrev.addEventListener('click', () => {
    if (thirteenthPage > 1) {
      thirteenthPage--;
      renderThirteenthPreviewPage();
    }
  });
  thirteenthPageNext && thirteenthPageNext.addEventListener('click', () => {
    const size = parseInt(thirteenthPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil((thirteenthRows.length || 0) / size));
    if (thirteenthPage < totalPages) {
      thirteenthPage++;
      renderThirteenthPreviewPage();
    }
  });

  thirteenthFinalizedPageSize && thirteenthFinalizedPageSize.addEventListener('change', () => {
    thirteenthFinalizedPage = 1;
    renderThirteenthFinalizedPage();
  });
  thirteenthFinalizedPagePrev && thirteenthFinalizedPagePrev.addEventListener('click', () => {
    if (thirteenthFinalizedPage > 1) {
      thirteenthFinalizedPage--;
      renderThirteenthFinalizedPage();
    }
  });
  thirteenthFinalizedPageNext && thirteenthFinalizedPageNext.addEventListener('click', () => {
    const size = parseInt(thirteenthFinalizedPageSize?.value || '10', 10) || 10;
    const totalPages = Math.max(1, Math.ceil((thirteenthFinalizedRows.length || 0) / size));
    if (thirteenthFinalizedPage < totalPages) {
      thirteenthFinalizedPage++;
      renderThirteenthFinalizedPage();
    }
  });

  btnExportPreview && btnExportPreview.addEventListener('click', () => {
    if (!previewRows.length) {
      show('No preview data to export.', 'error');
      return;
    }
    const start = startInput?.value || '';
    const end = endInput?.value || '';
    const columns = [
      { label: 'Employee', value: r => r.employee || '' },
      { label: 'Role', value: r => r.role || '' },
      { label: 'Hours/Days', value: r => r.hours_days || '' },
      { label: 'Gross Pay', value: r => r.gross || '' },
      { label: 'PhilHealth', value: r => r.philhealth || '' },
      { label: 'SSS', value: r => r.sss || '' },
      { label: 'Pag-IBIG', value: r => r.pagibig || '' },
      { label: 'Tax', value: r => r.tax || '' },
      { label: 'Other Deductions', value: r => r.manual_other || '' },
      { label: 'Total Deductions', value: r => r.deductions || '' },
      { label: 'Net Pay', value: r => r.net || '' },
      { label: 'Status', value: r => r.status || '' },
    ];
    const csv = buildCsv(columns, previewRows);
    downloadCsv(`payroll_preview_${start || 'start'}_${end || 'end'}.csv`, csv);
  });

  btnExportFinalized && btnExportFinalized.addEventListener('click', () => {
    if (!finalizedRows.length) {
      show('No finalized payroll records to export.', 'error');
      return;
    }
    const start = startInput?.value || '';
    const end = endInput?.value || '';
    const columns = [
      { label: 'Employee', value: r => r.employee || '' },
      { label: 'Role', value: r => r.role || '' },
      { label: 'Period', value: r => r.period || '' },
      { label: 'Gross Pay', value: r => r.gross || '' },
      { label: 'PhilHealth', value: r => r.philhealth || '' },
      { label: 'SSS', value: r => r.sss || '' },
      { label: 'Pag-IBIG', value: r => r.pagibig || '' },
      { label: 'Other Deductions', value: r => r.other || '' },
      { label: 'Net Pay', value: r => r.net || '' },
      { label: 'Paid Status', value: r => r.paid_status || '' },
      { label: 'Payment Date', value: r => r.payment_date || '' },
    ];
    const csv = buildCsv(columns, finalizedRows);
    downloadCsv(`payroll_finalized_${start || 'start'}_${end || 'end'}.csv`, csv);
  });

  btnExportThirteenthPreview && btnExportThirteenthPreview.addEventListener('click', () => {
    if (!thirteenthRows.length) {
      show('No 13th month preview data to export.', 'error');
      return;
    }
    const yearVal = thirteenthYearInput?.value || '';
    const year = parseInt(yearVal, 10) || new Date().getFullYear();
    const columns = [
      { label: 'Employee', value: r => r.employee || '' },
      { label: 'Role', value: r => r.role || '' },
      { label: 'Total Basic (Year)', value: r => r.basic_year || '' },
      { label: '13th Month', value: r => r.thirteenth || '' },
    ];
    const csv = buildCsv(columns, thirteenthRows);
    downloadCsv(`thirteenth_preview_${year}.csv`, csv);
  });

  btnExportThirteenthFinalized && btnExportThirteenthFinalized.addEventListener('click', () => {
    if (!thirteenthFinalizedRows.length) {
      show('No finalized 13th month records to export.', 'error');
      return;
    }
    const yearVal = thirteenthYearInput?.value || '';
    const year = parseInt(yearVal, 10) || new Date().getFullYear();
    const columns = [
      { label: 'Employee', value: r => r.employee || '' },
      { label: 'Role', value: r => r.role || '' },
      { label: 'Year', value: r => r.year || year },
      { label: 'Period', value: r => r.period || '' },
      { label: '13th Month', value: r => r.thirteenth || '' },
      { label: 'Paid Status', value: r => r.paid_status || '' },
      { label: 'Payment Date', value: r => r.payment_date || '' },
    ];
    const csv = buildCsv(columns, thirteenthFinalizedRows);
    downloadCsv(`thirteenth_finalized_${year}.csv`, csv);
  });

  payslipClose && payslipClose.addEventListener('click', () => {
    if (payslipModal) {
      payslipModal.classList.remove('open');
    }
  });

  payslipPrint && payslipPrint.addEventListener('click', () => {
    window.print();
  });

  thirteenthYearInput && thirteenthYearInput.addEventListener('change', loadThirteenthFinalized);
  thirteenthRoleSelect && thirteenthRoleSelect.addEventListener('change', loadThirteenthFinalized);

  // Wire initial deduction rows (first row)
  dedRows?.querySelectorAll('.deduction-row').forEach(initDeductionRow);

  // Init
  initDefaultPeriod();
  initThirteenthDefaults();
  loadRoles();
  loadNextPayrollPerRole();
  recalcPreview();
  loadFinalizedPayroll();
  loadThirteenthFinalized();
});

