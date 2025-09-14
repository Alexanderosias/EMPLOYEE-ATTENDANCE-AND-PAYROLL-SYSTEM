// --- UI Elements ---
        const payPeriodStartInput = document.getElementById('pay-period-start');
        const payPeriodEndInput = document.getElementById('pay-period-end');
        const viewPayrollBtn = document.getElementById('view-payroll-btn');
        const markAsPaidBtn = document.getElementById('mark-as-paid-btn');
        const payrollTableBody = document.getElementById('payroll-table-body');
        const totalGrossPaySpan = document.getElementById('total-gross-pay');
        const totalDeductionsSpan = document.getElementById('total-deductions');
        const totalNetPaySpan = document.getElementById('total-net-pay');
        const noRecordsMessage = document.getElementById('no-records-message');
        const payPeriodDisplayP = document.getElementById('pay-period-display');
        const statusMessageDiv = document.getElementById('status-message');

        // Tax settings UI elements
        const taxSettingsHeader = document.getElementById('tax-settings-header');
        const taxSettingsContent = document.getElementById('tax-settings-content');
        const toggleIcon = document.getElementById('toggle-icon');
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

        // --- Mock Employee Data (for demonstration) ---
        const employees = [
            { id: 'emp1', name: 'John Doe', monthlySalary: 35000 },
            { id: 'emp2', name: 'Jane Smith', monthlySalary: 32000 },
            { id: 'emp3', name: 'Peter Jones', monthlySalary: 45000 },
            { id: 'emp4', name: 'Mary Garcia', monthlySalary: 30000 }
        ];

        // --- Function to seed Firestore with default tax rates ---
        async function seedTaxRates() {
            const taxRatesRef = collection(db, `/artifacts/${appId}/public/data/tax_rates`);
            const sssDoc = doc(taxRatesRef, "sss");
            const sssSnapshot = await getDoc(sssDoc);

            if (!sssSnapshot.exists()) {
                console.log("Seeding tax rates...");
                const sssTable = [
                    { salaryRange: [0, 3249.99], sssContribution: 135 },
                    { salaryRange: [3250, 3749.99], sssContribution: 157.50 },
                    { salaryRange: [3750, 4249.99], sssContribution: 180 },
                    { salaryRange: [4250, 4749.99], sssContribution: 202.50 },
                    { salaryRange: [4750, 5249.99], sssContribution: 225 },
                    { salaryRange: [5250, 5749.99], sssContribution: 247.50 },
                    { salaryRange: [5750, 6249.99], sssContribution: 270 },
                    { salaryRange: [6250, 6749.99], sssContribution: 292.50 },
                    { salaryRange: [6750, 7249.99], sssContribution: 315 },
                    { salaryRange: [7250, 7749.99], sssContribution: 337.50 },
                    { salaryRange: [7750, 8249.99], sssContribution: 360 },
                    { salaryRange: [8250, 8749.99], sssContribution: 382.50 },
                    { salaryRange: [8750, 9249.99], sssContribution: 405 },
                    { salaryRange: [9250, 9749.99], sssContribution: 427.50 },
                    { salaryRange: [9750, 10249.99], sssContribution: 450 },
                    { salaryRange: [10250, 10749.99], sssContribution: 472.50 },
                    { salaryRange: [10750, 11249.99], sssContribution: 495 },
                    { salaryRange: [11250, 11749.99], sssContribution: 517.50 },
                    { salaryRange: [11750, 12249.99], sssContribution: 540 },
                    { salaryRange: [12250, 12749.99], sssContribution: 562.50 },
                    { salaryRange: [12750, 13249.99], sssContribution: 585 },
                    { salaryRange: [13250, 13749.99], sssContribution: 607.50 },
                    { salaryRange: [13750, 14249.99], sssContribution: 630 },
                    { salaryRange: [14250, 14749.99], sssContribution: 652.50 },
                    { salaryRange: [14750, 15249.99], sssContribution: 675 },
                    { salaryRange: [15250, 15749.99], sssContribution: 697.50 },
                    { salaryRange: [15750, 16249.99], sssContribution: 720 },
                    { salaryRange: [16250, 16749.99], sssContribution: 742.50 },
                    { salaryRange: [16750, 17249.99], sssContribution: 765 },
                    { salaryRange: [17250, 17749.99], sssContribution: 787.50 },
                    { salaryRange: [17750, 18249.99], sssContribution: 810 },
                    { salaryRange: [18250, 18749.99], sssContribution: 832.50 },
                    { salaryRange: [18750, 19249.99], sssContribution: 855 },
                    { salaryRange: [19250, 19749.99], sssContribution: 877.50 },
                    { salaryRange: [19750, 20249.99], sssContribution: 900 },
                    { salaryRange: [20250, 20749.99], sssContribution: 922.50 },
                    { salaryRange: [20750, 21249.99], sssContribution: 945 },
                    { salaryRange: [21250, 21749.99], sssContribution: 967.50 },
                    { salaryRange: [21750, 22249.99], sssContribution: 990 },
                    { salaryRange: [22250, 22749.99], sssContribution: 1012.50 },
                    { salaryRange: [22750, 23249.99], sssContribution: 1035 },
                    { salaryRange: [23250, 23749.99], sssContribution: 1057.50 },
                    { salaryRange: [23750, 24249.99], sssContribution: 1080 },
                    { salaryRange: [24250, 24749.99], sssContribution: 1102.50 },
                    { salaryRange: [24750, 25249.99], sssContribution: 1125 },
                    { salaryRange: [25250, 25749.99], sssContribution: 1147.50 },
                    { salaryRange: [25750, 26249.99], sssContribution: 1170 },
                    { salaryRange: [26250, 26749.99], sssContribution: 1192.50 },
                    { salaryRange: [26750, 27249.99], sssContribution: 1215 },
                    { salaryRange: [27250, 27749.99], sssContribution: 1237.50 },
                    { salaryRange: [27750, 28249.99], sssContribution: 1260 },
                    { salaryRange: [28250, 28749.99], sssContribution: 1282.50 },
                    { salaryRange: [28750, 29249.99], sssContribution: 1305 },
                    { salaryRange: [29250, 29749.99], sssContribution: 1327.50 },
                    { salaryRange: [29750, 30000], sssContribution: 1350 },
                    { salaryRange: [30000.01, Infinity], sssContribution: 1350 }
                ];

                await setDoc(sssDoc, { table: JSON.stringify(sssTable) });
                await setDoc(doc(taxRatesRef, "philhealth"), { rate: 0.05, floor: 10000, ceiling: 100000, fixedAmountFloor: 500, fixedAmountCeiling: 5000 });
                await setDoc(doc(taxRatesRef, "pagibig"), { employeeRate: 0.02, lowIncomeEmployeeRate: 0.01, lowIncomeThreshold: 1500 });
                console.log("Tax rates seeded successfully!");
            }

            // Fetch tax rates after seeding or if they already exist
            const fetchedSss = (await getDoc(sssDoc)).data();
            const fetchedPhilhealth = (await getDoc(doc(taxRatesRef, "philhealth"))).data();
            const fetchedPagibig = (await getDoc(doc(taxRatesRef, "pagibig"))).data();

            taxRates = {
                sss: JSON.parse(fetchedSss.table),
                philhealth: fetchedPhilhealth,
                pagibig: fetchedPagibig
            };

            // Populate the input fields
            philhealthRateInput.value = taxRates.philhealth.rate;
            philhealthFloorInput.value = taxRates.philhealth.floor;
            philhealthCeilingInput.value = taxRates.philhealth.ceiling;
            philhealthFixedFloorInput.value = taxRates.philhealth.fixedAmountFloor;
            philhealthFixedCeilingInput.value = taxRates.philhealth.fixedAmountCeiling;
            pagibigRateInput.value = taxRates.pagibig.employeeRate;
            pagibigLowRateInput.value = taxRates.pagibig.lowIncomeEmployeeRate;
            pagibigThresholdInput.value = taxRates.pagibig.lowIncomeThreshold;
            sssTableTextarea.value = JSON.stringify(taxRates.sss, null, 2);
        }

        // --- Calculation Functions ---
        function calculatePhilHealth(monthlySalary) {
            if (!taxRates) return 0;
            const { rate, floor, ceiling, fixedAmountFloor, fixedAmountCeiling } = taxRates.philhealth;
            if (monthlySalary <= floor) {
                return fixedAmountFloor;
            } else if (monthlySalary >= ceiling) {
                return fixedAmountCeiling;
            } else {
                return monthlySalary * rate;
            }
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
            const otherDeductions = 0; 
            const totalDeductions = philhealth + sss + pagibig + otherDeductions;
            const netPay = grossPay - totalDeductions;

            return { grossPay, philhealth, sss, pagibig, otherDeductions, totalDeductions, netPay };
        }

        // --- Helper Functions ---
        const formatDate = (date) => date.toISOString().split('T')[0];
        const daysInPeriod = (start, end) => (end - start) / (1000 * 60 * 60 * 24);

        async function getPayrollRun(startDate, endDate) {
            const payrollRunsRef = collection(db, `/artifacts/${appId}/users/${userId}/payroll_runs`);
            const q = query(payrollRunsRef, where('startDate', '==', startDate), where('endDate', '==', endDate));
            const querySnapshot = await getDocs(q);
            return querySnapshot.empty ? null : querySnapshot.docs[0];
        }

        async function markAsPaid(startDate, endDate) {
            try {
                const payrollRunDoc = await getPayrollRun(startDate, endDate);
                if (payrollRunDoc) {
                    await updateDoc(payrollRunDoc.ref, { paid: true });
                } else {
                    const payrollRunsRef = collection(db, `/artifacts/${appId}/users/${userId}/payroll_runs`);
                    await addDoc(payrollRunsRef, {
                        startDate: startDate,
                        endDate: endDate,
                        timestamp: new Date(),
                        paid: true,
                        skippedEmployees: []
                    });
                }
                statusMessageDiv.textContent = 'Payroll marked as paid successfully!';
                statusMessageDiv.classList.remove('hidden', 'text-red-600');
                statusMessageDiv.classList.add('text-green-600');
                markAsPaidBtn.disabled = true;
                setTimeout(() => statusMessageDiv.classList.add('hidden'), 3000);
            } catch (e) {
                console.error("Error marking payroll as paid: ", e);
                statusMessageDiv.textContent = 'Error marking payroll as paid.';
                statusMessageDiv.classList.remove('hidden', 'text-green-600');
                statusMessageDiv.classList.add('text-red-600');
            }
        }

        async function toggleEmployeeSkippedStatus(employeeId) {
            const startDate = payPeriodStartInput.value;
            const endDate = payPeriodEndInput.value;
            if (!startDate || !endDate) return;

            try {
                const payrollRunDoc = await getPayrollRun(startDate, endDate);
                let skippedEmployees = payrollRunDoc ? payrollRunDoc.data().skippedEmployees || [] : [];
                const docRef = payrollRunDoc ? payrollRunDoc.ref : collection(db, `/artifacts/${appId}/users/${userId}/payroll_runs`);

                const index = skippedEmployees.indexOf(employeeId);
                if (index > -1) {
                    skippedEmployees.splice(index, 1); // Remove from skipped list
                } else {
                    skippedEmployees.push(employeeId); // Add to skipped list
                }

                if (payrollRunDoc) {
                    await updateDoc(docRef, { skippedEmployees: skippedEmployees });
                } else {
                    await addDoc(docRef, {
                        startDate: startDate,
                        endDate: endDate,
                        timestamp: new Date(),
                        paid: false,
                        skippedEmployees: skippedEmployees
                    });
                }

                // Re-fetch and re-render the table
                fetchPayrollRecords(startDate, endDate);

            } catch (e) {
                console.error("Error toggling employee status: ", e);
                statusMessageDiv.textContent = 'Error toggling employee status.';
                statusMessageDiv.classList.remove('hidden', 'text-green-600');
                statusMessageDiv.classList.add('text-red-600');
            }
        }

        async function saveTaxRates() {
            try {
                const taxRatesRef = collection(db, `/artifacts/${appId}/public/data/tax_rates`);

                // Save PhilHealth rates
                const philhealthDoc = doc(taxRatesRef, "philhealth");
                await setDoc(philhealthDoc, {
                    rate: parseFloat(philhealthRateInput.value),
                    floor: parseFloat(philhealthFloorInput.value),
                    ceiling: parseFloat(philhealthCeilingInput.value),
                    fixedAmountFloor: parseFloat(philhealthFixedFloorInput.value),
                    fixedAmountCeiling: parseFloat(philhealthFixedCeilingInput.value)
                });

                // Save Pag-IBIG rates
                const pagibigDoc = doc(taxRatesRef, "pagibig");
                await setDoc(pagibigDoc, {
                    employeeRate: parseFloat(pagibigRateInput.value),
                    lowIncomeEmployeeRate: parseFloat(pagibigLowRateInput.value),
                    lowIncomeThreshold: parseFloat(pagibigThresholdInput.value)
                });

                // Save SSS rates (JSON string)
                const sssDoc = doc(taxRatesRef, "sss");
                try {
                    JSON.parse(sssTableTextarea.value); // Validate JSON
                    await setDoc(sssDoc, { table: sssTableTextarea.value });
                } catch (e) {
                    console.error("Invalid JSON for SSS table: ", e);
                    statusMessageDiv.textContent = 'Invalid JSON format for SSS table. Please correct and try again.';
                    statusMessageDiv.classList.remove('hidden', 'text-green-600');
                    statusMessageDiv.classList.add('text-red-600');
                    return;
                }

                statusMessageDiv.textContent = 'Tax rates saved successfully!';
                statusMessageDiv.classList.remove('hidden', 'text-red-600');
                statusMessageDiv.classList.add('text-green-600');
                setTimeout(() => statusMessageDiv.classList.add('hidden'), 3000);
                await seedTaxRates(); 
            } catch (e) {
                console.error("Error saving tax rates: ", e);
                statusMessageDiv.textContent = 'Error saving tax rates.';
                statusMessageDiv.classList.remove('hidden', 'text-green-600');
                statusMessageDiv.classList.add('text-red-600');
            }
        }

        // --- Main Functionality ---
        async function fetchPayrollRecords(startDate, endDate) {
            try {
                markAsPaidBtn.disabled = true;
                statusMessageDiv.classList.add('hidden');

                payrollTableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-gray-500 animate-pulse">Loading records...</td></tr>`;
                noRecordsMessage.classList.add('hidden');

                const payrollRunDoc = await getPayrollRun(startDate, endDate);
                const skippedEmployees = payrollRunDoc ? payrollRunDoc.data().skippedEmployees || [] : [];
                const isPaid = payrollRunDoc ? payrollRunDoc.data().paid || false : false;

                if (isPaid) {
                    statusMessageDiv.textContent = 'This pay period has already been processed and marked as paid.';
                    statusMessageDiv.classList.remove('hidden', 'text-green-600');
                    statusMessageDiv.classList.add('text-red-600');
                } else {
                    markAsPaidBtn.disabled = false;
                }

                const periodLengthInDays = daysInPeriod(new Date(startDate), new Date(endDate));
                const isMonthly = periodLengthInDays >= 28 && periodLengthInDays <= 31;

                const calculatedPayroll = employees.map(employee => {
                    const isSkipped = skippedEmployees.includes(employee.id);
                    if (isSkipped) {
                        return {
                            ...employee,
                            isSkipped: true
                        };
                    } else {
                        const payroll = calculatePayroll(employee, isMonthly);
                        return {
                            ...employee,
                            ...payroll,
                            isSkipped: false
                        };
                    }
                });

                renderTable(calculatedPayroll);
                if (calculatedPayroll.length > 0) {
                    markAsPaidBtn.disabled = isPaid;
                }

            } catch (error) {
                console.error("Error fetching payroll data:", error);
                payrollTableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-red-500">Failed to load data. See console.</td></tr>`;
            }
        }

        function renderTable(records) {
            payrollTableBody.innerHTML = '';

            if (records.length === 0) {
                noRecordsMessage.classList.remove('hidden');
                totalGrossPaySpan.textContent = '₱0.00';
                totalDeductionsSpan.textContent = '₱0.00';
                totalNetPaySpan.textContent = '₱0.00';
                return;
            }

            let totalGross = 0;
            let totalDeductions = 0;
            let totalNet = 0;

            records.forEach(record => {
                const row = document.createElement('tr');
                const rowClass = record.isSkipped ? 'bg-gray-200 text-gray-400' : 'bg-white text-gray-900';

                let rowContent;
                if (record.isSkipped) {
                    rowContent = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${rowClass}">${record.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">--</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                            <button class="bg-blue-600 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-700" data-employee-id="${record.id}">Mark as Included</button>
                        </td>
                    `;
                } else {
                    rowContent = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${rowClass}">${record.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">₱${record.grossPay.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">₱${record.philhealth.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">₱${record.sss.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">₱${record.pagibig.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${rowClass}">₱${record.otherDeductions.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-500 font-medium">₱${record.totalDeductions.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-700">₱${record.netPay.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                            <button class="bg-red-600 text-white px-3 py-1 rounded-md text-xs hover:bg-red-700" data-employee-id="${record.id}">Mark as Skipped</button>
                        </td>
                    `;
                    totalGross += record.grossPay;
                    totalDeductions += record.totalDeductions;
                    totalNet += record.netPay;
                }

                row.innerHTML = rowContent;
                payrollTableBody.appendChild(row);
            });

            totalGrossPaySpan.textContent = `₱${totalGross.toFixed(2)}`;
            totalDeductionsSpan.textContent = `₱${totalDeductions.toFixed(2)}`;
            totalNetPaySpan.textContent = `₱${totalNet.toFixed(2)}`;

            // Add event listeners to the new buttons
            document.querySelectorAll('#payroll-table-body button').forEach(button => {
                button.addEventListener('click', (e) => {
                    const employeeId = e.target.getAttribute('data-employee-id');
                    toggleEmployeeSkippedStatus(employeeId);
                });
            });
        }

        // --- Event Listeners and Initial Load ---
        document.addEventListener('DOMContentLoaded', async () => {
            // Authenticate with Firebase on load
            if (initialAuthToken) {
                await signInWithCustomToken(auth, initialAuthToken);
            } else {
                await signInAnonymously(auth);
            }
            userId = auth.currentUser?.uid || 'anonymous';

            await seedTaxRates(); 

            const today = new Date();
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

            payPeriodStartInput.value = formatDate(startOfMonth);
            payPeriodEndInput.value = formatDate(endOfMonth);
            payPeriodDisplayP.textContent = `Pay Period: ${startOfMonth.toLocaleDateString()} - ${endOfMonth.toLocaleDateString()}`;

            fetchPayrollRecords(formatDate(startOfMonth), formatDate(endOfMonth));
        });

        viewPayrollBtn.addEventListener('click', () => {
            const startDate = payPeriodStartInput.value;
            const endDate = payPeriodEndInput.value;
            payPeriodDisplayP.textContent = `Pay Period: ${new Date(startDate).toLocaleDateString()} - ${new Date(endDate).toLocaleDateString()}`;
            fetchPayrollRecords(startDate, endDate);
        });

        markAsPaidBtn.addEventListener('click', () => {
            const startDate = payPeriodStartInput.value;
            const endDate = payPeriodEndInput.value;
            if (startDate && endDate) {
                markAsPaid(startDate, endDate);
            } else {
                statusMessageDiv.textContent = 'Please select a valid date range first.';
                statusMessageDiv.classList.remove('hidden');
                statusMessageDiv.classList.add('text-red-600');
            }
        });

        taxSettingsHeader.addEventListener('click', () => {
            const isVisible = taxSettingsContent.style.display === 'block';
            taxSettingsContent.style.display = isVisible ? 'none' : 'block';
            toggleIcon.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
        });

        saveTaxRatesBtn.addEventListener('click', saveTaxRates);