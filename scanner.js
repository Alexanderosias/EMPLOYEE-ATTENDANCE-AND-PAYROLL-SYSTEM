// Initialize QR Scanner
function onScanSuccess(decodedText, decodedResult) {
  // Show scanned result
  document.getElementById("result").innerText =
    `QR Scanned: ${decodedText}`;

  // Access the video element used by html5-qrcode
  const video = document.querySelector("video");

  // Delay 1 second before taking snapshot
  setTimeout(() => {
    const snapshotData = takeSnapshot(video);

    // Preview snapshot on page
    const img = document.createElement("img");
    img.src = snapshotData;
    img.style.width = "200px";
    document.getElementById("result").appendChild(img);

    // Send data to backend (example via fetch)
    fetch("attendance.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        employee_id: decodedText,   // QR contains employee_id
        check_type: "in",           // or "out"
        timestamp: new Date().toISOString(),
        photo: snapshotData         // Base64 image
      })
    }).then(res => res.text()).then(data => {
      console.log("Server Response:", data);
    });

  }, 1000); // wait 1 second
}

function takeSnapshot(videoElement) {
  const canvas = document.createElement("canvas");
  canvas.width = videoElement.videoWidth;
  canvas.height = videoElement.videoHeight;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
  return canvas.toDataURL("image/png"); // Base64 string
}

// Start scanner
const html5QrcodeScanner = new Html5QrcodeScanner("reader", {
  fps: 10,
  qrbox: 250
});
html5QrcodeScanner.render(onScanSuccess);
