const resultContainer = document.getElementById('result');

function onScanSuccess(decodedText, decodedResult) {
    // decodedText = QR code content (JSON string)
    try {
        const data = JSON.parse(decodedText);

        const employeeId = data.employee_id;
        const firstName = data.first_name;
        const lastName = data.last_name;

        resultContainer.innerHTML = `Scanned: ${employeeId} - ${firstName} ${lastName}`;

        // Send to backend (attendance.php)
        fetch('attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `employee_id=${employeeId}&scan_type=IN&photo=`
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);
            resultContainer.innerHTML += `<br>${data.message}`;
        })
        .catch(err => console.error(err));

        // Clear display after 5 seconds to accept next QR code
        setTimeout(() => {
            resultContainer.innerHTML = "Scan a QR code to log attendance";
        }, 5000);

    } catch (e) {
        console.error("Invalid QR code", e);
        resultContainer.innerHTML = "Invalid QR code";

        // Clear error message after 5 seconds
        setTimeout(() => {
            resultContainer.innerHTML = "Scan a QR code to log attendance";
        }, 5000);
    }
}

// Webcam configuration
const html5QrcodeScanner = new Html5Qrcode("reader");

html5QrcodeScanner.start(
    { facingMode: "environment" }, // back camera
    {
        fps: 10,    // frames per second
        qrbox: 250  // scanning square
    },
    onScanSuccess
).catch(err => {
    console.error("Unable to start scanner", err);
});
