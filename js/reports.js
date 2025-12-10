// Dummy report content for each report option
const PAYROLL_API = "../views/payroll_handler.php";

const reportData = {
  attendance: {
    daily: `Daily Attendance Report – list of employees present, absent, late, and on leave for the day.\n\nExample:\n- Present: 45\n- Absent: 5\n- Late: 3\n- On Leave: 2`,
    weekly: `Weekly Attendance Report – attendance summary per employee for the week.\n\nExample:\nEmployee A: 5 days present\nEmployee B: 4 days present, 1 day absent`,
    biweekly: `Bi-Monthly / 15-Day Report – often used in companies that compute payroll every 15 days.\n\nPeriod: Sep 1 - Sep 15\nSummary of attendance and absences.`,
    monthly: `Monthly Attendance Report – detailed log of attendance, tardiness, and absences.\n\nSeptember 2025 detailed logs per employee.`,
    yearly: `Yearly Attendance Summary – attendance overview for the entire year, useful for performance evaluation.\n\nYear 2025 attendance summary.`,
    employee: `Employee Attendance Record – individual employee’s attendance history.\n\nExample:\nEmployee A: 230 days present, 10 days absent.`
  },
  payroll: {
    register: `Payroll Register – breakdown of basic pay, overtime, holiday pay, deductions, and net pay for all employees for a selected period.`,
    payslip: `Payslip Report – printable payslips per employee for a selected cutoff or payroll period.`,
    overtime: `Overtime Report – list of overtime hours, rates, and corresponding pay per employee and period.`,
    deductions: `Deductions Report – summary of government and other deductions (PhilHealth, SSS, Pag-IBIG, tax, and manual deductions).`,
    government: `Government Contributions Report – PhilHealth, SSS, and Pag-IBIG contributions per employee and payroll period.`,
    tax: `Withholding Tax Report – income tax withheld per employee and payroll period.`,
    per_employee: `Employee Payroll History – detailed list of payroll runs, gross pay, deductions, and net pay per employee.`,
    per_department: `Department / Position Payroll Summary – totals of gross pay, deductions, and net pay grouped by department or job position.`,
    bonus: `13th Month / Bonus Report – computation and release summary for 13th month pay and other bonuses (if applicable).`,
    yearend: `Year-End Payroll Summary – total salary, deductions, and benefits per employee for the selected year.`
  },
  other: {
    leave: `Leave Report – details of approved, pending, and used leaves.`,
    deptpos: `Department/Position-wise Report – summary of attendance or payroll per department/position.`,
    performance: `Employee Performance Report (Attendance-Based) – insights on punctuality, absenteeism trends.`,
    audit: `Audit Report – logs of changes in attendance or payroll records for transparency.`
  }
};

const registerModal = document.getElementById("report-modal");
const registerModalBody = document.getElementById("report-modal-body");
const registerModalClose = document.getElementById("report-modal-close");
const registerModalPrint = document.getElementById("report-modal-print");

function openRegisterModal() {
  if (!registerModal) return;
  registerModal.classList.add("open");
  registerModal.setAttribute("aria-hidden", "false");
}

function closeRegisterModal() {
  if (!registerModal) return;
  registerModal.classList.remove("open");
  registerModal.setAttribute("aria-hidden", "true");
}

registerModalClose &&
  registerModalClose.addEventListener("click", () => {
    closeRegisterModal();
  });

registerModal &&
  registerModal.addEventListener("click", (e) => {
    if (e.target === registerModal) {
      closeRegisterModal();
    }
  });

registerModalPrint &&
  registerModalPrint.addEventListener("click", () => {
    if (!registerModalBody || !registerModalBody.hasChildNodes()) return;
    openRegisterModal();
    window.print();
  });

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && registerModal?.classList.contains("open")) {
    closeRegisterModal();
  }
});

// Helper to get report content by section and selected value
function getReportContent(section, key) {
  return reportData[section]?.[key] || "No data available for this report.";
}

async function generatePayrollRegisterReport(card, output, printBtn) {
  const startInput = card.querySelector(".report-start-date");
  const endInput = card.querySelector(".report-end-date");
  const start = startInput?.value || "";
  const end = endInput?.value || "";

  if (!start || !end) {
    alert("Please select Start Date and End Date for the payroll register.");
    printBtn.disabled = true;
    return;
  }

  if (!registerModal || !registerModalBody) {
    alert("Report preview is not available.");
    printBtn.disabled = true;
    return;
  }

  const toNum = (val) => {
    const s = String(val ?? "0").replace(/,/g, "");
    const n = parseFloat(s);
    return Number.isFinite(n) ? n : 0;
  };

  const formatAmount = (n) =>
    "₱" + (Number(n) || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  registerModalBody.innerHTML =
    '<div class="report-loading">Loading payroll register...</div>';
  openRegisterModal();

  try {
    const body = { start, end, role_id: 0, frequency: "" };
    const res = await fetch(`${PAYROLL_API}?action=list_period_payroll`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });

    const out = await res.json();
    if (!out.success) {
      throw new Error(out.message || "Failed to load payroll register");
    }

    const rows = Array.isArray(out.data?.rows) ? out.data.rows : [];
    if (!rows.length) {
      registerModalBody.innerHTML =
        '<div class="report-loading">No payroll records found for this period.</div>';
      printBtn.disabled = true;
      return;
    }

    let totalBasic = 0;
    let totalOT = 0;
    let totalHoliday = 0;
    let totalPhil = 0;
    let totalSSS = 0;
    let totalPagibig = 0;
    let totalOther = 0;
    let totalNet = 0;

    const parts = [];

    parts.push(`
      <div class="register-header">
        <div>
          <h2 class="register-title">Payroll Register</h2>
          <div class="register-subtitle">Period: ${start} to ${end}</div>
        </div>
        <div class="register-meta">
          <span>Employees: ${rows.length}</span>
        </div>
      </div>
      <div class="register-list">
    `);

    rows.forEach((r) => {
      const emp = String(r.employee || "");
      const dept = String(r.department || "");
      const role = String(r.role || "");

      const basic = toNum(r.basic_pay);
      const ot = toNum(r.overtime_pay);
      const holiday = toNum(r.holiday_pay);
      const phil = toNum(r.philhealth);
      const sss = toNum(r.sss);
      const pagibig = toNum(r.pagibig);
      const other = toNum(r.other);
      const net = toNum(r.net);

      totalBasic += basic;
      totalOT += ot;
      totalHoliday += holiday;
      totalPhil += phil;
      totalSSS += sss;
      totalPagibig += pagibig;
      totalOther += other;
      totalNet += net;

      const meta = [dept, role].filter(Boolean).join(" • ");

      parts.push(`
        <section class="register-employee-card">
          <header class="register-employee-header">
            <div class="register-emp-name">${emp}</div>
            <div class="register-emp-meta">${meta}</div>
          </header>
          <div class="register-amount-rows">
            <div class="register-amount-row">
              <div class="label">Basic Pay</div>
              <div class="value">${formatAmount(basic)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">Overtime</div>
              <div class="value">${formatAmount(ot)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">Holiday</div>
              <div class="value">${formatAmount(holiday)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">PhilHealth</div>
              <div class="value">${formatAmount(phil)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">SSS</div>
              <div class="value">${formatAmount(sss)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">Pag-IBIG</div>
              <div class="value">${formatAmount(pagibig)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">Other Deductions</div>
              <div class="value">${formatAmount(other)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">Net Pay</div>
              <div class="value">${formatAmount(net)}</div>
            </div>
            <div class="register-amount-row">
              <div class="label">Status</div>
              <div class="value register-status">${String(r.paid_status || "")}</div>
            </div>
          </div>
        </section>
      `);
    });

    parts.push(`
      </div>
      <section class="register-totals-card">
        <h3 class="register-totals-title">Totals</h3>
        <div class="register-totals-grid">
          <div class="label">Basic Pay</div>
          <div class="value">${formatAmount(totalBasic)}</div>
          <div class="label">Overtime</div>
          <div class="value">${formatAmount(totalOT)}</div>
          <div class="label">Holiday</div>
          <div class="value">${formatAmount(totalHoliday)}</div>
          <div class="label">PhilHealth</div>
          <div class="value">${formatAmount(totalPhil)}</div>
          <div class="label">SSS</div>
          <div class="value">${formatAmount(totalSSS)}</div>
          <div class="label">Pag-IBIG</div>
          <div class="value">${formatAmount(totalPagibig)}</div>
          <div class="label">Other Deductions</div>
          <div class="value">${formatAmount(totalOther)}</div>
          <div class="label">Net Pay</div>
          <div class="value">${formatAmount(totalNet)}</div>
        </div>
      </section>
    `);

    registerModalBody.innerHTML = parts.join("");
    printBtn.disabled = false;
  } catch (e) {
    registerModalBody.innerHTML = `<div class="report-loading">${
      e.message || "Failed to load payroll register"
    }</div>`;
    printBtn.disabled = true;
  }
}

// Setup event listeners for each report card
document.querySelectorAll('.report-card').forEach((card) => {
  const select = card.querySelector('select.frequency-select');
  const generateBtn = card.querySelector('button.generate-btn');
  const printBtn = card.querySelector('button.print-btn');
  const output = card.querySelector('pre.report-output');

  if (!select || !generateBtn || !printBtn || !output) return;

  generateBtn.addEventListener('click', async () => {
    const sectionId = card.id.split('-')[0]; // attendance, payroll, other
    const selectedValue = select.value;

    if (sectionId === 'payroll' && selectedValue === 'register') {
      await generatePayrollRegisterReport(card, output, printBtn);
      return;
    }

    output.textContent = "Generating report...";
    setTimeout(() => {
      output.textContent = getReportContent(sectionId, selectedValue);
      printBtn.disabled = false;
      printBtn.focus();
    }, 400);
  });

  printBtn.addEventListener('click', () => {
    const sectionId = card.id.split('-')[0];
    const selectedValue = select.value;

    if (sectionId === 'payroll' && selectedValue === 'register') {
      if (!registerModalBody || !registerModalBody.hasChildNodes()) {
        alert('Please generate the payroll register before printing.');
        return;
      }
      openRegisterModal();
      window.print();
      return;
    }

    if (!output.textContent.trim() || output.textContent.includes("Select a report")) {
      alert("Please generate the report before printing.");
      return;
    }

    // Add printable class to this card only
    card.classList.add("printable");

    const mediaQueryList = window.matchMedia('print');
    const removeAfterPrint = (mql) => {
      if (!mql.matches) {
        card.classList.remove("printable");
        mediaQueryList.removeListener(removeAfterPrint);
      }
    };
    mediaQueryList.addListener(removeAfterPrint);

    window.print();
  });
});