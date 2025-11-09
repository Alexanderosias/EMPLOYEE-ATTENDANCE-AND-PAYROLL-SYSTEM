let syncInterval = null;
let lastScanTime = 0;  // Timestamp for global 3s cooldown
const GLOBAL_COOLDOWN_MS = 3000;  // 3 seconds global

let scanner;

document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM loaded. Initializing app...');
  init();
});

async function init() {
  initScanner();
  await loadRecentLogs();
  await updateUnsyncedCount();

  if (isOnline()) {
    await fullSync();
    syncInterval = setInterval(fullSync, 30000);
  }

  window.addEventListener('online', () => {
    console.log('Back online: Syncing...');
    fullSync();
    if (!syncInterval) syncInterval = setInterval(fullSync, 30000);
  });

  window.addEventListener('offline', () => {
    console.log('Went offline: Pausing sync');
    if (syncInterval) {
      clearInterval(syncInterval);
      syncInterval = null;
    }
  });
}

function initScanner() {
  const readerDiv = document.getElementById('reader');
  const resultDiv = document.getElementById('result');
  if (!readerDiv) {
    console.error('Reader div not found!');
    if (resultDiv) resultDiv.innerHTML = 'Error: Reader container missing.';
    return;
  }

  if (resultDiv) resultDiv.innerHTML = 'Initializing scanner... Please wait.';

  try {
    console.log('Starting scanner initialization...');

    scanner = new Html5QrcodeScanner(
      "reader",
      {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        disableFlip: false,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
        formatsToSupport: [
          Html5QrcodeSupportedFormats.QR_CODE,
          Html5QrcodeSupportedFormats.DATA_MATRIX,
          Html5QrcodeSupportedFormats.CODE_39
        ]
      },
      false
    );

    scanner.render(
      async function onScanSuccess(decodedText, decodedResult) {
        console.log('QR DETECTED!', decodedText);

        const resultDiv = document.getElementById('result');
        if (!resultDiv) return;

        const now = Date.now();

        if (now - lastScanTime < GLOBAL_COOLDOWN_MS) {
          resultDiv.innerHTML = '<span style="color: orange;">Please wait before scanning again.</span>';
          return;
        }
        lastScanTime = now;

        resultDiv.innerHTML = '<span style="color: blue;">Processing scan...</span>';

        if (scanner) {
          scanner.pause(false);
          setTimeout(() => {
            if (scanner) scanner.resume();
          }, GLOBAL_COOLDOWN_MS);
        }

        // Capture snapshot
        const snapshot = await captureQRSnapshot();

        // Send to backend for validation and logging
        try {
          const formData = new FormData();
          formData.append('qr_data', decodedText);
          if (snapshot) formData.append('snapshot', snapshot);

          const response = await fetch('views/scanner_api.php?action=scan', {
            method: 'POST',
            body: formData
          });

          if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Network error' }));
            throw new Error(errorData.message || 'Failed to process scan');
          }

          const result = await response.json();
          if (!result.success) {
            throw new Error(result.message || 'Scan failed');
          }

          // Success: Display message and snapshot
          const data = result.data;
          const checkType = data.check_type;
          const statusMsg = checkType === 'in' ? 'Clocked In' : 'Clocked Out';
          const nowTime = new Date().toLocaleTimeString();

          resultDiv.innerHTML = `<span style="color: green; font-weight: bold;">Employee successfully ${statusMsg} at ${nowTime}!</span>`;

          if (snapshot) {
            const img = document.createElement("img");
            img.src = snapshot;  // Base64 preview
            img.style.width = "200px";
            img.style.marginTop = "10px";
            img.onerror = () => console.warn('Snapshot preview failed');
            resultDiv.appendChild(img);
          }

          await loadRecentLogs();
          await updateUnsyncedCount();
        } catch (error) {
          console.error('Scan processing failed:', error);
          resultDiv.innerHTML = `<span style="color: red; font-weight: bold;">${error.message}</span>`;
        }

        setTimeout(resetResult, 5000);  // Longer timeout for success
      },
      (error) => {
        const ignoreTypes = ['NotFoundError', 'NotAllowedError', 'QR code parse error'];
        const errorMsg = error.message || error.toString();
        if (!ignoreTypes.some(type => errorMsg.includes(type))) {
          console.warn('Scan error:', errorMsg);
        }
      }
    );

    resultDiv.innerHTML = 'Scan a QR code to log attendance. Hold steady in the box.';
  } catch (error) {
    console.error('Scanner init failed:', error);
    if (resultDiv) {
      resultDiv.innerHTML = '<span style="color: red;">Scanner failed: ' + error.message + '.</span>';
    }
  }
}

window.addEventListener('beforeunload', () => {
  if (scanner) scanner.clear();
  if (syncInterval) clearInterval(syncInterval);
});

function isOnline() {
  return navigator.onLine;
}

async function updateUnsyncedCount() {
  try {
    const res = await fetch('views/scanner_api.php?action=get_unsynced_count');
    if (!res.ok) return;
    const result = await res.json();
    if (result.success && result.data) {
      const count = result.data.count || 0;
      const callout = document.getElementById('callout-message');
      const span = document.getElementById('unsync-count');
      if (span) span.textContent = count;
      if (callout) callout.style.display = count > 0 ? 'block' : 'none';
    } else {
      console.warn('Unsynced count API failed:', result.message);
    }
  } catch (e) {
    console.error('Failed to update unsynced count:', e);
  }
}

async function syncAttendance() {
  try {
    const res = await fetch('views/scanner_api.php?action=sync_attendance');
    if (!res.ok) {
      console.warn('Sync API failed (status:', res.status, ')');
      return;
    }

    const result = await res.json();
    console.log('Sync API raw response:', result);  // Debug

    if (result.success) {
      console.log(`Synced ${result.syncedCount || 0} attendance logs`);
      await updateUnsyncedCount();
      await loadRecentLogs();
    } else {
      console.warn('Sync failed:', result.message);
    }
  } catch (e) {
    console.error('Attendance sync failed:', e);
  }
}

async function fullSync() {
  await syncAttendance();
}

async function loadRecentLogs() {
  const tbody = document.getElementById('recent-logs-body');
  if (!tbody) return;

  tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Loading...</td></tr>';

  try {
    const res = await fetch('views/scanner_api.php?action=get_recent_logs');
    if (!res.ok) throw new Error('Failed to fetch');

    const result = await res.json();
    if (!result.success) throw new Error(result.message);

    const logs = result.data || [];  // Default to empty array if undefined
    if (logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No logs.</td></tr>';
      return;
    }

    let rowsHtml = '';
    logs.slice(0, 7).forEach((log, index) => {
      const rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
      rowsHtml += `
        <tr class="${rowClass}">
          <td>${log.employee_name}</td>
          <td>${log.job_position_name}</td>
          <td style="color: green;">${log.time_in_formatted}</td>
          <td style="color: ${log.time_out ? 'green' : 'orange'};">${log.time_out_formatted}</td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;
  } catch (error) {
    console.error('Error loading logs:', error);
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Failed to load.</td></tr>';
  }
}

async function captureQRSnapshot() {
  try {
    const readerDiv = document.getElementById('reader');
    const video = readerDiv ? readerDiv.querySelector('video') : null;
    if (!video || video.paused || video.videoWidth === 0) return null;

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    return canvas.toDataURL('image/png');  // Return base64
  } catch (e) {
    console.error('Snapshot capture failed:', e);
    return null;
  }
}

function resetResult() {
  const resultDiv = document.getElementById('result');
  if (resultDiv) {
    resultDiv.innerHTML = 'Scan a QR code to log attendance. Hold steady in the box.';
  }
}
