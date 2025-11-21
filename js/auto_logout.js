let inactivityTimer;
let logoutTimeHours = 1; // Default, will be updated from settings

// Fetch auto logout time from settings (only for head_admin/admin)
async function loadAutoLogoutSetting() {
  try {
    const response = await fetch("../views/settings_handler.php?action=load");
    const result = await response.json();
    if (result.success && result.data.time_date) {
      logoutTimeHours = result.data.time_date.auto_logout_time_hours || 1;
      // Only apply to head_admin or admin, and only if not disabled (0)
      const userRole = getUserRole();
      if (
        (userRole === "head_admin" || userRole === "admin") &&
        logoutTimeHours > 0
      ) {
        startInactivityTimer();
      }
    }
  } catch (error) {
    console.warn("Failed to load auto logout setting:", error);
  }
}

// Function to get user role (set from PHP session)
function getUserRole() {
  // Assuming you set a global variable or data attribute from PHP
  return window.userRole || "employee"; // Default to employee if not set
}

// Start/reset inactivity timer
function startInactivityTimer() {
  clearTimeout(inactivityTimer);
  if (logoutTimeHours <= 0) return; // Don't start if disabled
  const timeoutMs = logoutTimeHours * 60 * 60 * 1000; // Convert hours to milliseconds
  inactivityTimer = setTimeout(() => {
    alert("You have been logged out due to inactivity.");
    window.location.href = "../index.html"; // Redirect to login
  }, timeoutMs);
}

// Reset timer on activity
function resetTimer() {
  if (logoutTimeHours > 0) {
    // Only reset if enabled
    startInactivityTimer();
  }
}

// Event listeners for activity
document.addEventListener("mousemove", resetTimer);
document.addEventListener("keypress", resetTimer);
document.addEventListener("click", resetTimer);
document.addEventListener("scroll", resetTimer);

// Load on page load
document.addEventListener("DOMContentLoaded", loadAutoLogoutSetting);
