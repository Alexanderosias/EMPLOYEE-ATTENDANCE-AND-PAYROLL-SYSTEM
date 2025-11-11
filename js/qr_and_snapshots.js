const API_BASE = '/eaaps/views/qr_snapshots.php';  // Backend endpoint
const BASE_PATH = '/eaaps';

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
    qrImg.src = `/eaaps/${employee.qr_image_path}`;
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
      link.href = `/eaaps/${employee.qr_image_path}`;
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
    img.src = `/eaaps/${previewSrc}`;
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
      openFullscreen(`/eaaps/${previewSrc}`);
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
        snapImg.src = `/eaaps/${snap.image_path}`;
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
          link.href = `/eaaps/${snap.image_path}`;
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
          if (confirm(`Are you sure you want to delete this snapshot?`)) {
            try {
              const response = await fetch(`${API_BASE}?action=delete_snapshot&id=${snap.id}`, { method: 'DELETE' });
              const result = await response.json();
              if (result.success) {
                alert('Snapshot deleted successfully.');
                // Reload the page to refresh all data and UI
                location.reload();
              } else {
                alert('Failed to delete snapshot: ' + result.message);
              }
            } catch (error) {
              console.error('Error deleting snapshot:', error);
              alert('An error occurred while deleting the snapshot.');
            }
          }
        });
        btns.appendChild(deleteBtn);

        snapCard.appendChild(btns);

        // Clicking snapshot opens fullscreen
        snapCard.addEventListener("click", () => {
          openFullscreen(`/eaaps/${snap.image_path}`);  // Pass full path
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

