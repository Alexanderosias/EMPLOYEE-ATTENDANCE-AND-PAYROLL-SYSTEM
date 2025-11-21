// Shared script to update sidebar logo and app name from settings
async function updateSidebarFromSettings() {
  try {
    const response = await fetch("../views/settings_handler.php?action=load");
    if (!response.ok) {
      console.warn("Failed to load settings for sidebar update.");
      return;
    }
    const result = await response.json();
    if (result.success && result.data && result.data.system) {
      const { system_name, logo_path } = result.data.system; // Fixed: Access from result.data.system
      const sidebarLogo = document.getElementById("sidebarLogo");
      const sidebarAppName = document.getElementById("sidebarAppName");
      if (sidebarLogo && logo_path) {
        sidebarLogo.src = "../" + logo_path;
      }
      if (sidebarAppName) {
        sidebarAppName.textContent = system_name || "EAAPS Admin";
      }
    }
  } catch (error) {
    console.warn("Error updating sidebar from settings:", error);
  }
}

// Run on DOM ready
document.addEventListener("DOMContentLoaded", updateSidebarFromSettings);
