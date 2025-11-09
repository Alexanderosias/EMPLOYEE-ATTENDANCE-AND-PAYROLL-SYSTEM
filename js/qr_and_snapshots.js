const API_BASE = '/eaaps/views/qr_snapshots.php';  // Backend endpoint

let employeesData = [];  // Will be populated from API

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
  header.textContent = `${employee.first_name} ${employee.last_name} (${employee.id})`;
  card.appendChild(header);

  // QR and snapshot preview row
  const row = document.createElement("div");
  row.className = "qr-snapshot-row";

  // QR Codes container
  const qrContainer = document.createElement("div");
  qrContainer.className = "qr-code";

  if (employee.qr_image_path) {
    const qrImg = document.createElement("img");
    qrImg.src = `${employee.qr_image_path}`;  // Adjusted path
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
      link.href = `${employee.qr_image_path}`;
      link.download = `qr_${employee.id}.png`;
      link.click();
    });
    qrContainer.appendChild(saveBtn);
  } else {
    qrContainer.textContent = "No QR Code";
  }

  row.appendChild(qrContainer);

  // Snapshot preview (show first snapshot or placeholder)
  const preview = document.createElement("div");
  preview.className = "snapshot-preview";
  if (employee.snapshots && employee.snapshots.length > 0 && employee.snapshots[0] && employee.snapshots[0].image_path) {
    const firstSnap = employee.snapshots[0];
    const img = document.createElement("img");
    img.src = `${firstSnap.image_path}`;  // Adjusted to match QR fix
    img.alt = "Snapshot Preview";
    img.style.width = "100px";
    img.style.height = "100px";
    img.style.objectFit = "cover";
    img.onerror = () => preview.textContent = "Snapshot not found";
    preview.appendChild(img);
  } else {
    preview.textContent = "No snapshots";
  }
  preview.title = "Click to enlarge preview";
  preview.style.userSelect = "none";

  // Clicking preview opens fullscreen of first snapshot if exists
  preview.addEventListener("click", () => {
    if (employee.snapshots && employee.snapshots.length > 0 && employee.snapshots[0] && employee.snapshots[0].image_path) {
      openFullscreen(`${employee.snapshots[0].image_path}`);
    }
  });

  row.appendChild(preview);

  card.appendChild(row);

  // View All Snapshots button
  const viewAllBtn = document.createElement("button");
  viewAllBtn.className = "view-all-btn";
  const snapCount = employee.snapshots ? employee.snapshots.length : 0;
  viewAllBtn.textContent = `View All Snapshots (${snapCount})`;
  viewAllBtn.disabled = snapCount === 0;

  viewAllBtn.addEventListener("click", () => openModal(employee));

  card.appendChild(viewAllBtn);

  return card;
}

function openModal(employee) {
  modalEmployeeName.textContent = `${employee.first_name} ${employee.last_name} (${employee.id})`;
  modalSnapshotsContainer.innerHTML = "";

  if (!employee.snapshots || employee.snapshots.length === 0) {
    modalSnapshotsContainer.innerHTML = "<p>No snapshots available.</p>";
  } else {
    // Sort snapshots by captured_at descending
    const sortedSnapshots = [...employee.snapshots].sort((a, b) => new Date(b.captured_at) - new Date(a.captured_at));

    sortedSnapshots.forEach((snap) => {
      if (!snap || !snap.image_path) return;  // Skip null snapshots
      const snapCard = document.createElement("div");
      snapCard.className = "modal-snapshot-card";

      const snapImg = document.createElement("img");
      snapImg.src = `/snapshots/${snap.image_path}`;  // Adjusted
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
        link.href = `/snapshots/${snap.image_path}`;
        link.download = `snapshot_${employee.id}_${new Date(snap.captured_at).getTime()}.png`;
        link.click();
      });
      btns.appendChild(saveBtn);

      snapCard.appendChild(btns);

      // Clicking snapshot opens fullscreen
      snapCard.addEventListener("click", () => {
        openFullscreen(`/snapshots/${snap.image_path}`);
      });

      modalSnapshotsContainer.appendChild(snapCard);
    });
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
  fullscreenImage.src = imageSrc;
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
  employeesData.forEach(emp => {
    const card = createEmployeeCard(emp);
    employeesContainer.appendChild(card);
  });
}

// Initialize
document.addEventListener('DOMContentLoaded', fetchEmployeesData);
