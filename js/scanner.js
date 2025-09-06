// Employees passed from PHP
// const employees = [...];  <-- already injected in page

function onScanSuccess(decodedText, decodedResult) {
  let scannedData;
  try {
    scannedData = JSON.parse(decodedText); // try to parse JSON
  } catch (e) {
    alert("Invalid QR Code!");
    return; // stop here
  }

  // Check if employee_id exists in employees list
  const validEmp = employees.find(emp => emp.employee_id == scannedData.employee_id);

  if (!validEmp) {
    alert("QR Code not registered for any employee!");
    return;
  }

  // ✅ Valid QR Code → continue
  document.getElementById("result").innerText =
    `✅ Employee Found: ${validEmp.first_name} ${validEmp.last_name} (ID: ${validEmp.employee_id})`;

  const video = document.querySelector("video");

  // Delay 1 second before taking snapshot
  setTimeout(() => {
    const snapshotData = takeSnapshot(video);

    // Preview snapshot
    const img = document.createElement("img");
    img.src = snapshotData;
    img.style.width = "200px";
    document.getElementById("result").appendChild(img);

    // Send data to backend
    fetch("views/attendance.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        employee_id: validEmp.employee_id,
        check_type: "in",
        timestamp: new Date().toISOString(),
        photo: snapshotData
      })
    }).then(res => res.text()).then(data => {
      console.log("Server Response:", data);
    });

  }, 1000);
}

function takeSnapshot(videoElement) {
  const canvas = document.createElement("canvas");
  canvas.width = videoElement.videoWidth;
  canvas.height = videoElement.videoHeight;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
  return canvas.toDataURL("image/png");
}

// Start scanner
const html5QrcodeScanner = new Html5QrcodeScanner("reader", {
  fps: 100,
  qrbox: 250
});
html5QrcodeScanner.render(onScanSuccess);
