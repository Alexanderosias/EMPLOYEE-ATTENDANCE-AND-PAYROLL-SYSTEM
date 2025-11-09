// Toggle tax settings panel (FIX: Uses 'active' class for visibility)
        const taxSettingsHeader = document.getElementById('tax-settings-header');
        const taxSettingsContent = document.getElementById('tax-settings-content');
        const toggleIcon = document.getElementById('toggle-icon');

        taxSettingsHeader.addEventListener('click', () => {
            const expanded = taxSettingsHeader.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                taxSettingsContent.classList.remove('active');
                taxSettingsHeader.setAttribute('aria-expanded', 'false');
                toggleIcon.style.transform = 'rotate(0deg)';
            } else {
                taxSettingsContent.classList.add('active');
                taxSettingsHeader.setAttribute('aria-expanded', 'true');
                toggleIcon.style.transform = 'rotate(180deg)';
            }
        });

        taxSettingsHeader.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                taxSettingsHeader.click();
            }
        });

        // Elements for payroll summary
        const totalGrossPaySpan = document.getElementById('total-gross-pay');
        const totalDeductionsSpan = document.getElementById('total-deductions');
        const totalNetPaySpan = document.getElementById('total-net-pay');
        const payrollTableBody = document.getElementById('payroll-table-body');
        const payPeriodDisplayP = document.getElementById('pay-period-display');
        const markAsPaidBtn = document.getElementById('mark-as-paid-btn');
        const statusMessageDiv = document.getElementById('status-message');

        // Tax rate inputs
        const philhealthRateInput = document.getElementById('philhealth-rate');
        const philhealthFloorInput = document.getElementById('philhealth-floor');
        const philhealthCeilingInput = document.getElementById('philhealth-ceiling');
        const philhealthFixedFloorInput = document.getElementById('philhealth-fixed-floor');
        const philhealthFixedCeilingInput = document.getElementById('philhealth-fixed-ceiling');
        const pagibigRateInput = document.getElementById('pagibig-rate');
        const pagibigLowRateInput = document.getElementById('pagibig-low-rate');
        const pagibigThresholdInput = document.getElementById('pagibig-threshold');
        const sssTableTextarea = document.getElementById('sss-table');
        const saveTaxRatesBtn = document.getElementById('save-tax-rates-btn');

        // Mock employees data
        const employees = [
            { id: 'emp1', name: 'Francis Rivas', monthlySalary: 20000, isSkipped: false },
            { id: 'emp2', name: 'Adela Onlao', monthlySalary: 20000, isSkipped: false }
        ];

        // Default tax rates object
        let taxRates = null;

        // Utility functions
        function formatCurrency(num) {
            return `₱${num.toFixed(2)}`;
        }

        function calculatePhilHealth(monthlySalary) {
            if (!taxRates) return 0;
            const { rate, floor, ceiling, fixedAmountFloor, fixedAmountCeiling } = taxRates.philhealth;
            if (monthlySalary <= floor) return fixedAmountFloor;
            if (monthlySalary >= ceiling) return fixedAmountCeiling;
            return monthlySalary * rate;
        }

        function calculatePagIbig(monthlySalary) {
            if (!taxRates) return 0;
            const { employeeRate, lowIncomeEmployeeRate, lowIncomeThreshold } = taxRates.pagibig;
            const rate = monthlySalary > lowIncomeThreshold ? employeeRate : lowIncomeEmployeeRate;
            return monthlySalary * rate;
        }

        function calculateSSS(monthlySalary) {
            if (!taxRates) return 0;
            const sssTable = taxRates.sss;
            const entry = sssTable.find(row => monthlySalary >= row.salaryRange[0] && monthlySalary <= row.salaryRange[1]);
            return entry ? entry.sssContribution : 0;
        }

        function calculatePayroll(employee, isMonthly) {
            const grossPay = isMonthly ? employee.monthlySalary : employee.monthlySalary / 2;
            const philhealth = isMonthly ? calculatePhilHealth(employee.monthlySalary) : calculatePhilHealth(employee.monthlySalary) / 2;
            const sss = isMonthly ? calculateSSS(employee.monthlySalary) : calculateSSS(employee.monthlySalary) / 2;
            const pagibig = isMonthly ? calculatePagIbig(employee.monthlySalary) : calculatePagIbig(employee.monthlySalary) / 2;
            const otherDeductions = 0; // Placeholder
            const totalDeductions = philhealth + sss + pagibig + otherDeductions;
            const netPay = grossPay - totalDeductions;
            return { grossPay, philhealth, sss, pagibig, otherDeductions, totalDeductions, netPay };
        }

        // Render payroll table
        function renderTable(records) {
            payrollTableBody.innerHTML = '';

            if (records.length === 0) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 9;
                td.textContent = 'No payroll records found.';
                td.style.textAlign = 'center';
                td.style.padding = '1rem';
                tr.appendChild(td);
                payrollTableBody.appendChild(tr);
                totalGrossPaySpan.textContent = '₱0.00';
                totalDeductionsSpan.textContent = '₱0.00';
                totalNetPaySpan.textContent = '₱0.00';
                markAsPaidBtn.disabled = true;
                return;
            }

            let totalGross = 0;
            let totalDeductions = 0;
            let totalNet = 0;

            records.forEach(record => {
                const tr = document.createElement('tr');
                if (record.isSkipped) {
                    tr.classList.add('skipped');
                    tr.innerHTML = `
                        <td>${record.name}</td>
                        <td>--</td><td>--</td><td>--</td><td>--</td><td>--</td><td>--</td><td>--</td>
                        <td><button class="action-btn include-btn" data-id="${record.id}">Mark as Unpaid</button></td>
                    `;
                } else {
                    tr.innerHTML = `
                        <td>${record.name}</td>
                        <td>${formatCurrency(record.grossPay)}</td>
                        <td>${formatCurrency(record.philhealth)}</td>
                        <td>${formatCurrency(record.sss)}</td>
                        <td>${formatCurrency(record.pagibig)}</td>
                        <td>${formatCurrency(record.otherDeductions)}</td>
                        <td>${formatCurrency(record.totalDeductions)}</td>
                        <td>${formatCurrency(record.netPay)}</td>
                        <td><button class="action-btn skip-btn" data-id="${record.id}">Mark as Paid</button></td>
                    `;
                    totalGross += record.grossPay;
                    totalDeductions += record.totalDeductions;
                    totalNet += record.netPay;
                }
                payrollTableBody.appendChild(tr);
            });

            totalGrossPaySpan.textContent = formatCurrency(totalGross);
            totalDeductionsSpan.textContent = formatCurrency(totalDeductions);
            totalNetPaySpan.textContent = formatCurrency(totalNet);
            markAsPaidBtn.disabled = false;

            // Add event listeners for skip/include buttons
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const empId = btn.getAttribute('data-id');
                    toggleEmployeeSkip(empId);
                });
            });
        }

        // Toggle skip/include employee
        function toggleEmployeeSkip(empId) {
            const emp = employees.find(e => e.id === empId);
            if (!emp) return;
            emp.isSkipped = !emp.isSkipped;
            fetchPayrollRecords();
        }

        // Fetch payroll records and render
        function fetchPayrollRecords() {
            if (!taxRates) {
                // Initialize taxRates from inputs or defaults
                try {
                    taxRates = {
                        philhealth: {
                            rate: parseFloat(philhealthRateInput.value),
                            floor: parseFloat(philhealthFloorInput.value),
                            ceiling: parseFloat(philhealthCeilingInput.value),
                            fixedAmountFloor: parseFloat(philhealthFixedFloorInput.value),
                            fixedAmountCeiling: parseFloat(philhealthFixedCeilingInput.value)
                        },
                        pagibig: {
                            employeeRate: parseFloat(pagibigRateInput.value),
                            lowIncomeEmployeeRate: parseFloat(pagibigLowRateInput.value),
                            lowIncomeThreshold: parseFloat(pagibigThresholdInput.value)
                        },
                        sss: JSON.parse(sssTableTextarea.value)
                    };
                } catch {
                    taxRates = {
                        philhealth: { rate: 0.05, floor: 10000, ceiling: 100000, fixedAmountFloor: 500, fixedAmountCeiling: 5000 },
                        pagibig: { employeeRate: 0.02, lowIncomeEmployeeRate: 0.01, lowIncomeThreshold: 1500 },
                        sss: []
                    };
                }
            }

            // Assume monthly pay period for demo
            const isMonthly = true;

            const records = employees.map(emp => {
                if (emp.isSkipped) return { ...emp, isSkipped: true };
                const payroll = calculatePayroll(emp, isMonthly);
                return { ...emp, ...payroll, isSkipped: false };
            });

            renderTable(records);
            payPeriodDisplayP.textContent = `Pay Period: ${new Date().toLocaleDateString()}`;
        }

        // Save tax rates button handler
        saveTaxRatesBtn.addEventListener('click', () => {
            try {
                taxRates = {
                    philhealth: {
                        rate: parseFloat(philhealthRateInput.value),
                        floor: parseFloat(philhealthFloorInput.value),
                        ceiling: parseFloat(philhealthCeilingInput.value),
                        fixedAmountFloor: parseFloat(philhealthFixedFloorInput.value),
                        fixedAmountCeiling: parseFloat(philhealthFixedCeilingInput.value)
                    },
                    pagibig: {
                        employeeRate: parseFloat(pagibigRateInput.value),
                        lowIncomeEmployeeRate: parseFloat(pagibigLowRateInput.value),
                        lowIncomeThreshold: parseFloat(pagibigThresholdInput.value)
                    },
                    sss: JSON.parse(sssTableTextarea.value)
                };
                statusMessageDiv.textContent = 'Tax rates saved successfully!';
                statusMessageDiv.classList.add('success');
                statusMessageDiv.style.display = 'block';
                setTimeout(() => {
                    statusMessageDiv.style.display = 'none';
                    statusMessageDiv.classList.remove('success');
                }, 3000);
                fetchPayrollRecords();
            } catch (e) {
                statusMessageDiv.textContent = 'Invalid JSON format for SSS table. Please correct and try again.';
                statusMessageDiv.classList.remove('success');
                statusMessageDiv.style.display = 'block';
            }
        });

        // Mark as Paid button handler
        markAsPaidBtn.addEventListener('click', () => {
            markAsPaidBtn.disabled = true;
            statusMessageDiv.textContent = 'Payroll marked as paid successfully!';
            statusMessageDiv.classList.add('success');
            statusMessageDiv.style.display = 'block';
            setTimeout(() => {
                statusMessageDiv.style.display = 'none';
                statusMessageDiv.classList.remove('success');
            }, 3000);
        });

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', () => {
            fetchPayrollRecords();
        });