const employeesData = [
      {
        id: "E001",
        name: "Francis Rivas",
        qrCodes: ["QR Code 1"],
        snapshots: [
          { date: "2025-09-15", time: "08:00 AM", image: "Snapshot 1" },
          { date: "2025-09-16", time: "09:30 AM", image: "Snapshot 2" },
          { date: "2025-09-17", time: "10:00 AM", image: "Snapshot 3" },
          { date: "2025-09-18", time: "11:00 AM", image: "Snapshot 4" },
          { date: "2025-09-19", time: "12:00 PM", image: "Snapshot 5" },
        ],
      },
      {
        id: "E002",
        name: "Adela Onlao",
        qrCodes: ["QR Code 1"],
        snapshots: [
          { date: "2025-09-15", time: "08:15 AM", image: "Snapshot 1" },
          { date: "2025-09-16", time: "11:00 AM", image: "Snapshot 2" },
          { date: "2025-09-17", time: "12:30 PM", image: "Snapshot 3" },
          { date: "2025-09-18", time: "01:00 PM", image: "Snapshot 4" },
        ],
      },
      {
        id: "E002",
        name: "Ciala Dismaya",
        qrCodes: ["QR Code 1"],
        snapshots: [
          { date: "2025-09-15", time: "08:15 AM", image: "Snapshot 1" },
          { date: "2025-09-16", time: "11:00 AM", image: "Snapshot 2" },
          { date: "2025-09-17", time: "12:30 PM", image: "Snapshot 3" },
          { date: "2025-09-18", time: "01:00 PM", image: "Snapshot 4" },
        ],
      },
    ];

    const employeesContainer = document.getElementById("employees-container");
    const modal = document.getElementById("snapshot-modal");
    const modalCloseBtn = document.getElementById("modal-close-btn");
    const modalEmployeeName = document.getElementById("modal-employee-name");
    const modalSnapshotsContainer = document.getElementById("modal-snapshots-container");
    const fullscreenOverlay = document.getElementById("fullscreen-overlay");
    const fullscreenImage = fullscreenOverlay.querySelector("img");

    function createEmployeeCard(employee) {
      const card = document.createElement("div");
      card.className = "employee-card";

      // Employee header
      const header = document.createElement("h3");
      header.className = "text-lg font-semibold";
      header.textContent = `${employee.name} (${employee.id})`;
      card.appendChild(header);

      // QR and snapshot preview row
      const row = document.createElement("div");
      row.className = "qr-snapshot-row";

      // QR Codes container (show all QR codes horizontally)
      const qrContainer = document.createElement("div");
      qrContainer.className = "qr-code";

      employee.qrCodes.forEach((qr) => {
        const qrText = document.createElement("div");
        qrText.textContent = qr;
        qrContainer.appendChild(qrText);
      });

      const saveBtn = document.createElement("button");
      saveBtn.className = "save-qr-btn";
      saveBtn.textContent = "Save QR";
      saveBtn.addEventListener("click", () => {
        alert(`Save QR code for ${employee.name}`);
        // Implement actual save logic here
      });
      qrContainer.appendChild(saveBtn);

      row.appendChild(qrContainer);

      // Snapshot preview (show first snapshot or placeholder)
      const preview = document.createElement("div");
      preview.className = "snapshot-preview";
      preview.textContent = employee.snapshots.length > 0 ? employee.snapshots[0].image : "No snapshots";
      preview.title = "Click to enlarge preview";
      preview.style.userSelect = "none";

      // Clicking preview opens fullscreen of first snapshot if exists
      preview.addEventListener("click", () => {
        if (employee.snapshots.length > 0) {
          openFullscreen(employee.snapshots[0].image);
        }
      });

      row.appendChild(preview);

      card.appendChild(row);

      // View All Snapshots button
      const viewAllBtn = document.createElement("button");
      viewAllBtn.className = "view-all-btn";
      viewAllBtn.textContent = `View All Snapshots (${employee.snapshots.length})`;
      viewAllBtn.disabled = employee.snapshots.length === 0;

      viewAllBtn.addEventListener("click", () => openModal(employee));

      card.appendChild(viewAllBtn);

      return card;
    }

    function openModal(employee) {
      modalEmployeeName.textContent = `${employee.name} (${employee.id})`;
      modalSnapshotsContainer.innerHTML = "";

      // Sort snapshots by date/time ascending
      const sortedSnapshots = [...employee.snapshots].sort((a, b) => {
        const dateA = new Date(`${a.date} ${a.time}`);
        const dateB = new Date(`${b.date} ${b.time}`);
        return dateA - dateB;
      });

      sortedSnapshots.forEach((snap) => {
        const snapCard = document.createElement("div");
        snapCard.className = "modal-snapshot-card";

        const snapImg = document.createElement("div");
        snapImg.className = "modal-snapshot-img";
        snapImg.textContent = snap.image;
        snapCard.appendChild(snapImg);

        const snapInfo = document.createElement("div");
        snapInfo.className = "modal-snapshot-info";
        snapInfo.innerHTML = `Date: ${snap.date}<br>Time: ${snap.time}`;
        snapCard.appendChild(snapInfo);

        const btns = document.createElement("div");
        btns.className = "modal-btns";

        const saveBtn = document.createElement("button");
        saveBtn.className = "save-snapshot-btn";
        saveBtn.textContent = "Save";
        saveBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          alert(`Save snapshot for ${employee.name} on ${snap.date} at ${snap.time}`);
          // Implement actual save logic here
        });
        btns.appendChild(saveBtn);

        const deleteBtn = document.createElement("button");
        deleteBtn.className = "delete-snapshot-btn";
        deleteBtn.textContent = "Delete";
        deleteBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          if (confirm(`Delete snapshot for ${employee.name} on ${snap.date} at ${snap.time}?`)) {
            // Remove from employee data
            employee.snapshots.splice(employee.snapshots.indexOf(snap), 1);
            // Remove from modal UI
            snapCard.remove();
            // Update preview and button on main card
            updateEmployeeCard(employee);
          }
        });
        btns.appendChild(deleteBtn);

        snapCard.appendChild(btns);

        // Clicking snapshot card image opens fullscreen
        snapCard.addEventListener("click", () => {
          openFullscreen(snap.image);
        });

        modalSnapshotsContainer.appendChild(snapCard);
      });

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

    // Fullscreen overlay open
    function openFullscreen(imageText) {
      fullscreenImage.textContent = imageText;
      fullscreenOverlay.style.display = "flex";
    }

    // Fullscreen overlay close
    fullscreenOverlay.addEventListener("click", () => {
      fullscreenOverlay.style.display = "none";
      fullscreenImage.src = "";
      fullscreenImage.textContent = "";
    });

    // Render all employees initially
    function renderEmployees() {
      employeesContainer.innerHTML = "";
      employeesData.forEach(emp => {
        const card = createEmployeeCard(emp);
        employeesContainer.appendChild(card);
      });
    }

    // Update employee card preview and button after snapshot deletion
    function updateEmployeeCard(employee) {
      // Find the card in DOM by employee id
      const cards = [...employeesContainer.children];
      const card = cards.find(c => c.querySelector("h3").textContent.includes(employee.id));
      if (!card) return;

      // Update snapshot preview
      const preview = card.querySelector(".snapshot-preview");
      if (employee.snapshots.length > 0) {
        preview.textContent = employee.snapshots[0].image;
      } else {
        preview.textContent = "No snapshots";
      }

      // Update view all button
      const viewAllBtn = card.querySelector(".view-all-btn");
      viewAllBtn.textContent = `View All Snapshots (${employee.snapshots.length})`;
      viewAllBtn.disabled = employee.snapshots.length === 0;
    }

    renderEmployees();