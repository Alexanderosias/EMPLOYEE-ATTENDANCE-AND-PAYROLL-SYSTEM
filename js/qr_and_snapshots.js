function showStatus(message, type) {
  const statusDiv = document.getElementById("status-message");
  if (!statusDiv) {
    console.error("Status message div not found!");
    return;
  }
  statusDiv.textContent = message;
  statusDiv.className = `status-message ${type}`;
  statusDiv.classList.add("show");

  // Auto-hide after 3 seconds
  setTimeout(() => {
    statusDiv.classList.remove("show");
  }, 3000);
}

// Flexible confirmation modal function
function showConfirmation(
  message,
  confirmText = "Confirm",
  confirmColor = "blue"
) {
  return new Promise((resolve) => {
    const confirmationModal = document.getElementById("confirmation-modal");
    const confirmationMessage = document.getElementById("confirmation-message");
    const confirmationConfirmBtn = document.getElementById(
      "confirmation-confirm-btn"
    );
    const confirmationCancelBtn = document.getElementById(
      "confirmation-cancel-btn"
    );
    const confirmationCloseX = document.getElementById("confirmation-close-x");

    if (
      !confirmationModal ||
      !confirmationMessage ||
      !confirmationConfirmBtn ||
      !confirmationCancelBtn ||
      !confirmationCloseX
    ) {
      console.error("Confirmation modal elements not found");
      resolve(false);
      return;
    }

    // Set message
    confirmationMessage.textContent = message;

    // Set button text and color
    confirmationConfirmBtn.textContent = confirmText;
    confirmationConfirmBtn.className = `px-4 py-2 bg-${confirmColor}-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-${confirmColor}-600 focus:outline-none focus:ring-2 focus:ring-${confirmColor}-300`;

    // Show modal
    confirmationModal.classList.remove("hidden");
    confirmationModal.setAttribute("aria-hidden", "false");

    const cleanup = () => {
      confirmationModal.classList.add("hidden");
      confirmationModal.setAttribute("aria-hidden", "true");
      confirmationConfirmBtn.removeEventListener("click", handleConfirm);
      confirmationCancelBtn.removeEventListener("click", handleCancel);
      confirmationCloseX.removeEventListener("click", handleCancel);
      document.removeEventListener("keydown", handleEscape);
    };

    // Handle confirm
    const handleConfirm = () => {
      cleanup();
      resolve(true);
    };

    // Handle cancel
    const handleCancel = () => {
      cleanup();
      resolve(false);
    };

    const handleEscape = (e) => {
      if (
        e.key === "Escape" &&
        !confirmationModal.classList.contains("hidden")
      ) {
        handleCancel();
      }
    };

    // Attach event listeners
    confirmationConfirmBtn.addEventListener("click", handleConfirm);
    confirmationCancelBtn.addEventListener("click", handleCancel);
    confirmationCloseX.addEventListener("click", handleCancel);
    document.addEventListener("keydown", handleEscape);
  });
}

const BASE_PATH = ''; // Change to '' for localhost:8000, or '/newpath' for Hostinger
const API_BASE = BASE_PATH + '/views/qr_snapshots.php';

let employeesData = [];  // Will be populated from API
let filteredEmployeesData = [];  // To store filtered results

const employeesContainer = document.getElementById("employees-container");
const modal = document.getElementById("snapshot-modal");
const modalCloseBtn = document.getElementById("modal-close-btn");
const modalEmployeeName = document.getElementById("modal-employee-name");
const modalSnapshotsContainer = document.getElementById("modal-snapshots-container");
const fullscreenOverlay = document.getElementById("fullscreen-overlay");
const fullscreenImage = fullscreenOverlay.querySelector("img");

async function fetchEmployeesData() {
  try {
    const response = await fetch(`${API_BASE}?action=list_employees_qr_snapshots`);
    if (!response.ok) throw new Error('Failed to fetch data');
    const result = await response.json();
    if (!result.success) throw new Error(result.message);
    employeesData = result.data || [];
    filteredEmployeesData = [...employeesData];  // Copy for filtering
    renderEmployees();
  } catch (error) {
    console.error('Error fetching employees data:', error);
    showStatus('Failed to load data. Please try again.', 'error');
    employeesContainer.innerHTML = '<p class="text-red-500">Failed to load data. Please try again.</p>';
  }
}

function createEmployeeCard(employee) {
  const card = document.createElement("div");
  card.className = "employee-card";

  // Employee header
  const header = document.createElement("h3");
  header.className = "text-lg font-semibold";
  header.textContent = `${employee.first_name} ${employee.last_name}`;  // Removed ID
  card.appendChild(header);

  // QR and snapshot preview row
  const row = document.createElement("div");
  row.className = "qr-snapshot-row";

  // QR Codes container
  const qrContainer = document.createElement("div");
  qrContainer.className = "qr-code";

  if (employee.qr_image_path) {
    const qrImg = document.createElement("img");
    qrImg.src = `${BASE_PATH}/${employee.qr_image_path}`;
    qrImg.alt = "QR Code";
    qrImg.style.width = "100px";
    qrImg.style.height = "100px";
    qrImg.onerror = () => qrContainer.textContent = "QR Image not found";
    qrContainer.appendChild(qrImg);

    const saveBtn = document.createElement("button");
    saveBtn.className = "save-qr-btn";
    saveBtn.textContent = "Save QR";
    saveBtn.addEventListener("click", () => {
      const link = document.createElement('a');
      link.href = `${BASE_PATH}/${employee.qr_image_path}`;
      link.download = `${employee.first_name}_${employee.last_name}.png`;  // Employee name
      link.click();
    });
    qrContainer.appendChild(saveBtn);
  } else {
    qrContainer.textContent = "No QR Code";
  }

  row.appendChild(qrContainer);

  // Snapshot preview (use snapshot_path from attendance_logs if available, else first from snapshots)
  const preview = document.createElement("div");
  preview.className = "snapshot-preview";
  let previewSrc = null;
  let hasValidSnapshots = false;

  if (employee.snapshots && employee.snapshots.length > 0) {
    const validSnaps = employee.snapshots.filter(s => s && s.image_path);
    if (validSnaps.length > 0) {
      hasValidSnapshots = true;
      previewSrc = validSnaps[0].image_path;  // Use first valid snapshot
    }
  }

  if (hasValidSnapshots && previewSrc) {
    const img = document.createElement("img");
    img.src = `${BASE_PATH}/${previewSrc}`;
    img.alt = "Snapshot Preview";
    img.style.width = "100px";
    img.style.height = "100px";
    img.style.objectFit = "cover";
    img.onerror = () => {
      preview.innerHTML = "<p>Snapshot not found</p>";
    };
    preview.appendChild(img);
  } else {
    preview.textContent = "No snapshots";
  }

  preview.title = "Click to enlarge preview";
  preview.style.userSelect = "none";

  // Clicking preview opens fullscreen (only if there's a valid image)
  preview.addEventListener("click", () => {
    if (hasValidSnapshots && previewSrc) {
      openFullscreen(`${BASE_PATH}/${previewSrc}`);
    }
  });

  row.appendChild(preview);

  card.appendChild(row);

  // View All Snapshots button
  const viewAllBtn = document.createElement("button");
  viewAllBtn.className = "view-all-btn";
  const snapCount = employee.snapshots ? employee.snapshots.length : 0;
  viewAllBtn.textContent = `View All Snapshots (${snapCount})`;  // Should now be accurate
  viewAllBtn.disabled = snapCount === 0;

  viewAllBtn.addEventListener("click", () => openModal(employee));

  card.appendChild(viewAllBtn);

  return card;
}

function openModal(employee) {
  modalEmployeeName.textContent = `${employee.first_name} ${employee.last_name}`;  // Removed ID
  modalSnapshotsContainer.innerHTML = "";

  if (!employee.snapshots || employee.snapshots.length === 0) {
    modalSnapshotsContainer.innerHTML = "<p>No snapshots available.</p>";
  } else {
    const validSnapshots = employee.snapshots.filter(s => s && s.image_path && s.captured_at);
    const sortedSnapshots = validSnapshots.sort((a, b) => new Date(b.captured_at) - new Date(a.captured_at));

    if (sortedSnapshots.length === 0) {
      modalSnapshotsContainer.innerHTML = "<p>No valid snapshots available.</p>";
    } else {
      sortedSnapshots.forEach((snap) => {
        const snapCard = document.createElement("div");
        snapCard.className = "modal-snapshot-card";

        const snapImg = document.createElement("img");
        snapImg.src = `${BASE_PATH}/${snap.image_path}`;
        snapImg.alt = "Snapshot";
        snapImg.className = "modal-snapshot-img";
        snapImg.style.width = "150px";
        snapImg.style.height = "150px";
        snapImg.style.objectFit = "cover";
        snapImg.onerror = () => snapCard.innerHTML = "<p>Snapshot not found</p>";
        snapCard.appendChild(snapImg);

        const snapInfo = document.createElement("div");
        snapInfo.className = "modal-snapshot-info";
        snapInfo.innerHTML = `Captured: ${new Date(snap.captured_at).toLocaleString()}`;
        snapCard.appendChild(snapInfo);

        const btns = document.createElement("div");
        btns.className = "modal-btns";

        const saveBtn = document.createElement("button");
        saveBtn.className = "save-snapshot-btn";
        saveBtn.textContent = "Save";
        saveBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          const link = document.createElement('a');
          link.href = `${BASE_PATH}/${snap.image_path}`;
          link.download = `snapshot_${employee.first_name}_${employee.last_name}_${new Date(snap.captured_at).getTime()}.png`;
          link.click();
        });
        btns.appendChild(saveBtn);

        const deleteBtn = document.createElement("button");
        deleteBtn.className = "save-snapshot-btn";  // Same class as Save for consistent styling
        deleteBtn.style.backgroundColor = "#dc3545";  // Red background
        deleteBtn.style.color = "white";  // Ensure text is visible
        deleteBtn.textContent = "Delete";
        deleteBtn.addEventListener("click", async (e) => {
          e.stopPropagation();
          const confirmed = await showConfirmation(
            'Are you sure you want to delete this snapshot?',
            'Delete Snapshot',
            'red'
          );
          if (!confirmed) {
            return;
          }
          try {
            const response = await fetch(`${API_BASE}?action=delete_snapshot&id=${snap.id}`, { method: 'DELETE' });
            const result = await response.json();
            if (result.success) {
              showStatus('Snapshot deleted successfully.', 'success');
              // Reload the page to refresh all data and UI
              location.reload();
            } else {
              showStatus('Failed to delete snapshot: ' + (result.message || ''), 'error');
            }
          } catch (error) {
            console.error('Error deleting snapshot:', error);
            showStatus('An error occurred while deleting the snapshot.', 'error');
          }
        });
        btns.appendChild(deleteBtn);

        snapCard.appendChild(btns);

        // Clicking snapshot opens fullscreen
        snapCard.addEventListener("click", () => {
          openFullscreen(`${BASE_PATH}/${snap.image_path}`);  // Pass full path
        });

        modalSnapshotsContainer.appendChild(snapCard);
      });
    }
  }

  modal.classList.add("flex");
  modal.classList.remove("hidden");
}

function closeModal() {
  modal.classList.remove("flex");
  modal.classList.add("hidden");
}

modalCloseBtn.addEventListener("click", closeModal);
modal.addEventListener("click", (e) => {
  if (e.target === modal) closeModal();
});

// In openFullscreen
function openFullscreen(imageSrc) {
  fullscreenImage.src = imageSrc;  // No BASE_PATH
  fullscreenOverlay.style.display = "flex";
}

// Fullscreen overlay close
fullscreenOverlay.addEventListener("click", () => {
  fullscreenOverlay.style.display = "none";
  fullscreenImage.src = "";
});

// Render all employees
function renderEmployees() {
  employeesContainer.innerHTML = "";
  filteredEmployeesData.forEach(emp => {  // Use filtered data
    const card = createEmployeeCard(emp);
    employeesContainer.appendChild(card);
  });
}


function filterEmployees() {
  const searchTerm = document.getElementById('employee-search').value.toLowerCase();
  filteredEmployeesData = employeesData.filter(employee => {
    const fullName = `${employee.first_name} ${employee.last_name}`.toLowerCase();
    return fullName.includes(searchTerm);
  });
  renderEmployees();
}


// Initialize
document.addEventListener('DOMContentLoaded', () => {
  fetchEmployeesData();
  // Add search event listener
  document.getElementById('employee-search').addEventListener('input', filterEmployees);
});

