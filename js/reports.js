// Dummy report content for each report option
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
        register: `Payroll Register Report – breakdown of gross pay, deductions, and net pay for all employees.`,
        payslip: `Payslip Report – generates payslips for employees (individual format).`,
        overtime: `Overtime Report – list of overtime hours and corresponding pay.`,
        deductions: `Deductions Report – deductions like taxes.`,
        bonus: `13th Month / Bonus Report – computation and release summary (if applicable).`,
        yearend: `Year-End Payroll Summary – total salary, deductions, and benefits per employee.`
      },
      other: {
        leave: `Leave Report – details of approved, pending, and used leaves.`,
        deptpos: `Department/Position-wise Report – summary of attendance or payroll per department/position.`,
        performance: `Employee Performance Report (Attendance-Based) – insights on punctuality, absenteeism trends.`,
        audit: `Audit Report – logs of changes in attendance or payroll records for transparency.`
      }
    };

    // Helper to get report content by section and selected value
    function getReportContent(section, key) {
      return reportData[section]?.[key] || "No data available for this report.";
    }

    // Setup event listeners for each report card
    document.querySelectorAll('.report-card').forEach(card => {
      const select = card.querySelector('select.frequency-select');
      const generateBtn = card.querySelector('button.generate-btn');
      const printBtn = card.querySelector('button.print-btn');
      const output = card.querySelector('pre.report-output');

      generateBtn.addEventListener('click', () => {
        const sectionId = card.id.split('-')[0]; // attendance, payroll, other
        const selectedValue = select.value;
        output.textContent = "Generating report...";
        setTimeout(() => {
          output.textContent = getReportContent(sectionId, selectedValue);
          printBtn.disabled = false;
          printBtn.focus();
        }, 400);
      });

      printBtn.addEventListener('click', () => {
        if (!output.textContent.trim() || output.textContent.includes("Select a report")) {
          alert("Please generate the report before printing.");
          return;
        }
        // Add printable class to this card only
        card.classList.add("printable");
        window.print();
        // Remove printable class after print dialog closes
        const mediaQueryList = window.matchMedia('print');
        mediaQueryList.addListener(function mqlListener(mql) {
          if (!mql.matches) {
            card.classList.remove("printable");
            mediaQueryList.removeListener(mqlListener);
          }
        });
      });
    });