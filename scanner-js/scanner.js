// scanner-js/scanner.js - Complete Fixed Version
// Integrates: Scanner init, QR validation (pipe-delimited), cooldowns (global 3s + employee 3min), local DB logging (clock-in/out), snapshot capture, offline queue, Firebase sync via PHP, recent logs refresh.
// Assumes: PHP API handles log_attendance (insert/update based on existing today), sync_attendance (pushes to Firebase), get_last_log_time, etc.
// Firebase: Handled server-side via PHP (no client SDK needed unless you want direct client push).

// Global vars
let employees = [];  // Local employees for QR validation
let syncInterval = null;
let lastScanTime = 0;  // Timestamp for global 3s cooldown
const GLOBAL_COOLDOWN_MS = 3000;  // 3 seconds global
const EMPLOYEE_COOLDOWN_MS = 180000;  // 3 minutes per employee same day

// Global scanner reference (single one)
let scanner;

// Wait for page to load before initializing
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM loaded. Initializing app...');
  init();  // Calls initScanner, loadEmployees, loadRecentLogs, etc.
});

// Full app initialization
async function init() {
  await loadLocalEmployees();  // Load for validation
  initScanner();  // Start scanner
  await loadRecentLogs();  // Load recent table
  await updateUnsyncedCount();  // Update offline indicator

  if (isOnline()) {
    await fullSync();  // Initial sync
    syncInterval = setInterval(fullSync, 30000);  // Poll every 30s
  }

  // Online/offline listeners
  window.addEventListener('online', () => {
    console.log('Back online: Syncing...');
    fullSync();
    processAttendanceQueue();
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

// Initialize scanner with full onScanSuccess handler
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
    console.log('Starting scanner initialization (optimized for detection)...');

    // Optimized config for better QR parsing
    scanner = new Html5QrcodeScanner(
      "reader",
      {
        fps: 10,  // Balanced speed
        qrbox: { width: 250, height: 250 },  // Larger box for easier framing
        aspectRatio: 1.0,
        disableFlip: false,
        supportedScanTypes: [
          Html5QrcodeScanType.SCAN_TYPE_CAMERA
        ],
        formatsToSupport: [  // Broader support
          Html5QrcodeSupportedFormats.QR_CODE,
          Html5QrcodeSupportedFormats.DATA_MATRIX,
          Html5QrcodeSupportedFormats.CODE_39
        ]
      },
      false  // verboseScan: false (no spam)
    );

    console.log('Scanner object created. Rendering...');

    scanner.render(
      // Complete: Full onScanSuccess Handler (inside scanner.render in initScanner)
      async function onScanSuccess(decodedText, decodedResult) {
        console.log('*** QR DETECTED! *** Starting validation...', decodedText);

        const resultDiv = document.getElementById('result');
        if (!resultDiv) return;

        const now = Date.now();

        // Global cooldown check (3s since last scan)
        if (now - lastScanTime < GLOBAL_COOLDOWN_MS) {
          resultDiv.innerHTML = '<span style="color: orange;">Please wait before scanning again.</span>';
          return;
        }
        lastScanTime = now;

        // Immediate "Processing..." for speed
        resultDiv.innerHTML = '<span style="color: blue;">Processing scan...</span>';

        // Pause scanner for global cooldown duration
        if (scanner) {
          console.log('Pausing scanner for global cooldown...');
          scanner.pause(false);
          setTimeout(() => {
            if (scanner) {
              console.log('Resuming scanner after global cooldown...');
              scanner.resume();
            }
          }, GLOBAL_COOLDOWN_MS);
        }

        // Step 1: Parse QR and find employee (fast, sync)
        const employeeData = parseQRAndFindEmployee(decodedText);
        if (!employeeData || !employeeData.id) {
          console.warn('Invalid QR: No matching employee found.');
          resultDiv.innerHTML = '<span style="color: red; font-weight: bold;">Invalid QR Code! Employee not found.</span>';
          setTimeout(resetResult, 3000);  // Faster reset
          return;
        }

        const { id: employeeId, first_name, last_name, job_position_name } = employeeData;
        console.log('Valid employee found:', employeeId, first_name);

        const fullName = `${first_name} ${last_name}`.trim();

        // FIXED: Capture LOCAL time (matches current_time.js - no UTC)
        const localNow = new Date();  // Local time
        const today = localNow.getFullYear() + '-' +
          String(localNow.getMonth() + 1).padStart(2, '0') + '-' +
          String(localNow.getDate()).padStart(2, '0');  // YYYY-MM-DD local
        let localHours = localNow.getHours().toString().padStart(2, '0');
        let localMinutes = localNow.getMinutes().toString().padStart(2, '0');
        let localSeconds = localNow.getSeconds().toString().padStart(2, '0');
        const nowTime = `${localHours}:${localMinutes}:${localSeconds}`;  // 24h local HH:MM:SS

        console.log('Local scan time:', today, nowTime);  // Debug: Matches screen? (Remove after test)

        // Step 2: Parallel checks (cooldown + clocked-in) for speed
        const [tooRecent, isClockedInToday] = await Promise.all([
          checkEmployeeCooldown(employeeId),
          isEmployeeClockedInToday(employeeId)
        ]);

        if (tooRecent) {
          console.warn('Employee on cooldown.');
          resultDiv.innerHTML = '<span style="color: orange; font-weight: bold;">Please wait 3 minutes before scanning again today.</span>';
          setTimeout(resetResult, 3000);
          return;
        }

        const checkType = isClockedInToday ? 'out' : 'in';
        const timeField = checkType === 'in' ? 'time_in' : 'time_out';
        const statusMsg = checkType === 'in' ? 'Clocked In' : 'Clocked Out';

        // Step 3: Capture snapshot in parallel (non-blocking for log)
        const snapshotPathPromise = captureQRSnapshot();  // Starts now

        // Step 4: Prepare log data (use local time)
        const logData = {
          employee_id: employeeId,
          date: today,  // Local date string
          [timeField]: nowTime,  // Local time string (HH:MM:SS)
          qr_snapshot_path: null,  // Will update with path
          check_type: checkType,
          synced: 0
        };

        // Wait for snapshot, then log
        const snapshotPath = await snapshotPathPromise;
        logData.qr_snapshot_path = snapshotPath || null;

        const success = await logAttendance(logData);
        if (!success) {
          console.error('Failed to log attendance.');
          resultDiv.innerHTML = '<span style="color: red; font-weight: bold;">Logging failed. Try again or contact admin.</span>';
          setTimeout(resetResult, 3000);
          return;
        }

        // Step 5: UI Success + Preview Snapshot (immediate)
        resultDiv.innerHTML = `<span style="color: green; font-weight: bold;">${fullName} successfully ${statusMsg} at ${nowTime}!</span>`;

        // Preview snapshot if captured (async, non-blocking)
        if (snapshotPath) {
          const img = document.createElement("img");
          img.src = `./${snapshotPath}`;  // Relative URL (adjust if needed, e.g., full path like '/uploads/snapshots/file.png')
          img.style.width = "200px";
          img.style.marginTop = "10px";
          img.onerror = () => console.warn('Snapshot preview failed to load');
          resultDiv.appendChild(img);
        }

        console.log('Logged successfully. Action:', checkType);

        // Step 6: Queue and sync to Firebase (non-blocking)
        if (isOnline()) {
          fullSync().catch(e => console.error('Sync failed:', e));
        } else {
          queueAttendance(logData);
          const queuedSpan = document.createElement('span');
          queuedSpan.innerHTML = '<br><span style="color: blue;">Queued for sync when online.</span>';
          queuedSpan.style.fontSize = 'smaller';
          resultDiv.appendChild(queuedSpan);
        }

        // Step 7: Refresh recent logs table ONLY on success
        loadRecentLogs();

        // Reset UI after 3s (faster feedback)
        setTimeout(resetResult, 3000);
      },

      // Error handler (unchanged, minimal)
      (error) => {
        if (error) {
          const ignoreTypes = ['NotFoundError', 'NotAllowedError', 'QR code parse error'];
          const errorMsg = error.message || error.toString();
          if (!ignoreTypes.some(type => errorMsg.includes(type))) {
            console.warn('Scan error:', errorMsg);
          }
        }
      }
    );

    console.log('Scanner rendered successfully. Ready to scan.');
    resultDiv.innerHTML = 'Scan a QR code to log attendance. Hold steady in the box.';

    // Video diagnostic (one-time)
    setTimeout(() => {
      const video = document.getElementById('cameraPreview');
      if (video) {
        console.log('Video active. Playing:', !video.paused);
        const videos = readerDiv.querySelectorAll('video');
        console.log('Video count (should be 1):', videos.length);
      } else {
        console.warn('No video element found.');
      }
    }, 2000);

  } catch (error) {
    console.error('Scanner init failed:', error);
    if (resultDiv) {
      resultDiv.innerHTML = '<span style="color: red;">Scanner failed: ' + error.message + '. Check camera/permissions.</span>';
    }
  }
}

// Cleanup on page close
window.addEventListener('beforeunload', () => {
  if (scanner) {
    console.log('Cleaning up scanner...');
    scanner.clear();
  }
  if (syncInterval) clearInterval(syncInterval);
});

// ====== SUPPORTING FUNCTIONS ======
// Online/offline detection
function isOnline() {
  return navigator.onLine;
}

// Update unsynced count (show/hide callout)
async function updateUnsyncedCount() {
  try {
    const res = await fetch('backend/scanner_api.php?action=get_unsynced_count');
    if (!res.ok) return;
    const { data } = await res.json();
    const count = data.count || 0;
    const callout = document.getElementById('callout-message');
    const span = document.getElementById('unsync-count');
    if (span) span.textContent = count;
    if (callout) callout.style.display = count > 0 ? 'block' : 'none';
  } catch (e) {
    console.error('Failed to update unsynced count:', e);
  }
}

// Queue attendance for offline
function queueAttendance(logData) {
  let queue = JSON.parse(localStorage.getItem('attendanceQueue') || '[]');
  queue.push({ ...logData, queuedAt: Date.now() });
  localStorage.setItem('attendanceQueue', JSON.stringify(queue));
  console.log('Queued attendance:', logData);
}

// Process offline queue (batch insert local, then sync)
async function processAttendanceQueue() {
  if (!isOnline()) return;
  let queue = JSON.parse(localStorage.getItem('attendanceQueue') || '[]');
  if (queue.length === 0) return;

  try {
    const res = await fetch('backend/scanner_api.php?action=log_attendance_batch', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ logs: queue })
    });
    if (res.ok) {
      const { success } = await res.json();
      if (success) {
        localStorage.removeItem('attendanceQueue');
        console.log('Processed offline queue');
        await updateUnsyncedCount();
        await loadRecentLogs();
      }
    }
  } catch (e) {
    console.error('Failed to process queue:', e);
  }
}

// Load local employees for QR validation
async function loadLocalEmployees() {
  try {
    const res = await fetch('backend/scanner_api.php?action=get_local_employees');
    if (res.ok) {
      const { data } = await res.json();
      employees = data || [];
      console.log(`Loaded ${employees.length} local employees`);
    }
  } catch (e) {
    console.error('Failed to load local employees:', e);
    employees = [];
  }
}

// Pull employees from Firebase, update local DB (via PHP)
async function syncEmployees() {
  if (!isOnline()) return;
  try {
    const res = await fetch('backend/scanner_api.php?action=sync_employees');
    if (res.ok) {
      const { success } = await res.json();
      if (success) {
        await loadLocalEmployees();  // Reload for validation
        console.log('Employees pulled from Firebase');
      }
    }
  } catch (e) {
    console.error('Employee sync failed:', e);
  }
}

// Push unsynced attendance to Firebase (via PHP)
async function syncAttendance() {
  if (!isOnline()) return;
  try {
    const res = await fetch('backend/scanner_api.php?action=sync_attendance');
    if (res.ok) {
      const { success, syncedCount } = await res.json();
      if (success) {
        console.log(`Synced ${syncedCount} attendance logs to Firebase`);
        await updateUnsyncedCount();
        await loadRecentLogs();
      }
    }
  } catch (e) {
    console.error('Attendance sync failed:', e);
  }
}

// Full sync (employees + attendance + queue)
async function fullSync() {
  await syncEmployees();
  await syncAttendance();
  await processAttendanceQueue();
}

// Load and display 7 most recent logs in the table
async function loadRecentLogs() {
  const tbody = document.getElementById('recent-logs-body');
  if (!tbody) {
    console.error('Recent logs tbody not found');
    return;
  }

  // Show loading state
  tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Loading recent logs...</td></tr>';

  try {
    const res = await fetch('backend/scanner_api.php?action=get_recent_logs');
    if (!res.ok) throw new Error('Failed to fetch logs');

    const { data: logs } = await res.json();

    if (logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #666;">No recent logs found.</td></tr>';
      return;
    }

    // Build table rows (limit to 7)
    let rowsHtml = '';
    logs.slice(0, 7).forEach((log, index) => {
      const rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
      rowsHtml += `
        <tr class="${rowClass}" style="border-bottom: 1px solid #ddd;">
          <td style="padding: 8px; font-weight: semi-bold;">${log.employee_name}</td>
          <td style="padding: 8px;">${log.job_position_name}</td>
          <td style="padding: 8px; font-weight: bold; color: green;">${log.time_in_formatted}</td>
          <td style="padding: 8px; font-weight: bold; color: ${log.time_out ? 'green' : 'orange'};">
            ${log.time_out_formatted}
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = rowsHtml;
  } catch (error) {
    console.error('Error loading recent logs:', error);
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Failed to load logs. Check connection.</td></tr>';
  }
}

// Parse pipe-delimited QR and find local employee
function parseQRAndFindEmployee(qrText) {
  const parts = {};
  qrText.split('|').forEach(part => {
    const [key, value] = part.split(':');
    if (key && value) parts[key.trim().toLowerCase()] = value.trim();
  });

  const id = parseInt(parts.id);
  const first = (parts.first || '').replace(/[^a-zA-Z]/g, '');
  const last = (parts.last || '').replace(/[^a-zA-Z]/g, '');
  const position = parts.position || '';
  const joined = parts.joined || '';

  // Match exact ID or sanitized fields
  return employees.find(emp =>
    emp.id === id ||
    (emp.qr_first_name_sanitized === first &&
      emp.qr_last_name_sanitized === last &&
      emp.job_position_name === position &&
      emp.date_joined === joined)
  );
}

// Check if employee has a log today (for clock-in/out decision)
async function isEmployeeClockedInToday(employeeId) {
  try {
    const today = new Date().toISOString().split('T')[0];
    const res = await fetch(`backend/scanner_api.php?action=check_clocked_in&employee_id=${employeeId}&date=${today}`);
    if (!res.ok) return false;
    const { data } = await res.json();
    return data.clocked_in;  // true if time_in exists and time_out null today
  } catch (e) {
    console.error('Check clocked in error:', e);
    return false;
  }
}

// Check employee cooldown (3min since last log same day)
async function checkEmployeeCooldown(employeeId) {
  if (!isOnline()) return false;

  try {
    const res = await fetch(`backend/scanner_api.php?action=get_last_log_time&employee_id=${employeeId}`);
    if (!res.ok) return false;

    const response = await res.json();
    const data = response.data;
    if (!data || typeof data !== 'object') return false;

    const lastTimeStr = data.last_time;
    if (!lastTimeStr) return false;

    const lastTime = new Date(lastTimeStr).getTime();
    const now = Date.now();
    return (now - lastTime) < EMPLOYEE_COOLDOWN_MS;
  } catch (e) {
    console.error('Failed to check employee cooldown:', e);
    return false;
  }
}

// Updated: Check if employee clocked in today (with undefined data fallback)
async function isEmployeeClockedInToday(employeeId) {
  try {
    const today = new Date().toISOString().split('T')[0];
    const res = await fetch(`backend/scanner_api.php?action=check_clocked_in&employee_id=${employeeId}&date=${today}`);
    if (!res.ok) return false;

    const response = await res.json();
    const data = response.data;
    if (!data || typeof data !== 'object') return false;

    return !!data.clocked_in;
  } catch (e) {
    console.error('Check clocked in error:', e);
    return false;
  }
}

// Updated: Log attendance (with better error logging)
// Enhanced: Log attendance to local DB (with full debugging)
async function logAttendance(logData) {
  try {
    const formData = new FormData();
    Object.keys(logData).forEach(key => formData.append(key, logData[key] || ''));

    const res = await fetch('backend/scanner_api.php?action=log_attendance', {
      method: 'POST',
      body: formData
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const response = await res.json();
    const { success, data } = response;
    if (success && data && data.id) {
      logData.id = data.id;
      return true;
    }
    return false;
  } catch (error) {
    console.error('Log attendance error:', error);
    return false;
  }
}


// Updated: Sync attendance (handle undefined syncedCount)
async function syncAttendance() {
  if (!isOnline()) return;
  try {
    const res = await fetch('backend/scanner_api.php?action=sync_attendance');
    if (!res.ok) {
      console.warn('Sync API failed (status:', res.status, ')');
      return;
    }

    const response = await res.json();
    console.log('Sync API raw response:', response);  // Debug

    const { success, syncedCount = 0 } = response;  // Default to 0 if undefined
    if (success) {
      console.log(`Synced ${syncedCount} attendance logs to Firebase`);
      await updateUnsyncedCount();
      await loadRecentLogs();
    } else {
      console.warn('Sync failed:', response);
    }
  } catch (e) {
    console.error('Attendance sync failed:', e);
  }
}


// Capture QR snapshot (canvas to base64, then save via PHP)
async function captureQRSnapshot() {
  try {
    // Fixed: Query for video inside #reader (Html5Qrcode dynamic ID)
    const readerDiv = document.getElementById('reader');
    const video = readerDiv ? readerDiv.querySelector('video') : null;
    if (!video || video.paused || video.videoWidth === 0) {
      console.warn('No active video for snapshot');
      return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    const base64Data = canvas.toDataURL('image/png');

    // Save via PHP (returns path)
    const res = await fetch('backend/scanner_api.php?action=save_snapshot', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ snapshot: base64Data })
    });
    if (!res.ok) {
      console.error('Snapshot save failed:', res.status);
      return null;
    }
    const { success, data } = await res.json();
    if (success && data && data.path) {
      console.log('Snapshot saved:', data.path);  // Keep minimal log
      return data.path;
    }
    return null;
  } catch (e) {
    console.error('Snapshot capture failed:', e);
    return null;
  }
}

// Log attendance to local DB (insert new or update existing based on check_type)
async function logAttendance(logData) {
  try {
    const formData = new FormData();
    Object.keys(logData).forEach(key => formData.append(key, logData[key]));

    const res = await fetch('backend/scanner_api.php?action=log_attendance', {
      method: 'POST',
      body: formData
    });

    if (!res.ok) throw new Error('Log failed');

    const { success, data } = await res.json();
    if (success) {
      logData.id = data.id;  // Save log ID for potential queue/sync
      return true;
    }
    return false;
  } catch (error) {
    console.error('Log attendance error:', error);
    return false;
  }
}

// Helper function to reset result to default
function resetResult() {
  const resultDiv = document.getElementById('result');
  if (resultDiv) {
    resultDiv.innerHTML = 'Scan a QR code to log attendance. Hold steady in the box.';
  }
}

// Legacy takeSnapshot (if needed for older code; uses base64 directly)
function takeSnapshot(videoElement) {
  const canvas = document.createElement("canvas");
  canvas.width = videoElement.videoWidth || 640;
  canvas.height = videoElement.videoHeight || 480;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(videoElement, 0, 0);
  return canvas.toDataURL("image/png");
}

// Legacy savePhoto (if needed; saves base64 via PHP)
async function savePhoto(base64Data) {
  try {
    const res = await fetch('backend/scanner_api.php?action=save_photo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ photo: base64Data })
    });
    const { data } = await res.json();
    return data.path || null;
  } catch (e) {
    console.error('Photo save failed:', e);
    return null;
  }
}

// Legacy logAttendance (if needed; older version with base64 photo)
async function logAttendanceLegacy(logData) {
  try {
    const photoPath = await savePhoto(logData.photo);
    const res = await fetch('backend/scanner_api.php?action=log_attendance', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...logData, photo_path: photoPath })
    });
    return res.ok;
  } catch (e) {
    console.error('Legacy log failed:', e);
    return false;
  }
}
