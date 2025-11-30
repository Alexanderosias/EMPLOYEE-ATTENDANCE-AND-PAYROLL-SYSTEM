document.addEventListener("DOMContentLoaded", () => {
  const API_BASE = "../views/holidays_handler.php";

  // Elements
  const addHolidayBtn = document.getElementById("add-holiday-btn");
  const addEventBtn = document.getElementById("add-event-btn");
  const holidayModal = document.getElementById("add-holiday-modal");
  const eventModal = document.getElementById("add-event-modal");
  const holidayForm = document.getElementById("holiday-form");
  const eventForm = document.getElementById("event-form");
  const tableBody = document.getElementById("holidays-events-table");
  const searchInput = document.getElementById("search-input");
  const typeFilter = document.getElementById("type-filter");
  const dateStart = document.getElementById("date-range-start");
  const dateEnd = document.getElementById("date-range-end");

  // Confirmation modal elements
  const confirmationModal = document.getElementById("confirmation-modal");
  const confirmationMessage = document.getElementById("confirmation-message");
  const confirmationConfirmBtn = document.getElementById(
    "confirmation-confirm-btn"
  );
  const confirmationCancelBtn = document.getElementById(
    "confirmation-cancel-btn"
  );
  const confirmationCloseX = document.getElementById("confirmation-close-x");

  let editingItem = null; // { type: 'holiday'|'event', id: number }

  // Status message function
  function showStatus(message, type = "success") {
    const statusDiv = document.getElementById("status-message");
    statusDiv.textContent = message;
    statusDiv.className = `status-message ${type}`;
    statusDiv.classList.add("show");
    setTimeout(() => statusDiv.classList.remove("show"), 3000);
  }

  // Confirmation function
  function showConfirmation(
    message,
    confirmText = "Confirm",
    confirmColor = "blue"
  ) {
    return new Promise((resolve) => {
      confirmationMessage.textContent = message;
      confirmationConfirmBtn.textContent = confirmText;
      confirmationConfirmBtn.className = `px-4 py-2 bg-${confirmColor}-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-${confirmColor}-600 focus:outline-none focus:ring-2 focus:ring-${confirmColor}-300`;
      confirmationModal.classList.remove("hidden");
      confirmationModal.setAttribute("aria-hidden", "false");

      const handleConfirm = () => {
        cleanup();
        resolve(true);
      };
      const handleCancel = () => {
        cleanup();
        resolve(false);
      };
      const cleanup = () => {
        confirmationModal.classList.add("hidden");
        confirmationModal.setAttribute("aria-hidden", "true");
        confirmationConfirmBtn.removeEventListener("click", handleConfirm);
        confirmationCancelBtn.removeEventListener("click", handleCancel);
        confirmationCloseX.removeEventListener("click", handleCancel);
      };

      confirmationConfirmBtn.addEventListener("click", handleConfirm);
      confirmationCancelBtn.addEventListener("click", handleCancel);
      confirmationCloseX.addEventListener("click", handleCancel);

      const handleEscape = (e) => {
        if (
          e.key === "Escape" &&
          !confirmationModal.classList.contains("hidden")
        ) {
          handleCancel();
          document.removeEventListener("keydown", handleEscape);
        }
      };
      document.addEventListener("keydown", handleEscape);
    });
  }

  // Load data
  loadData();

  // Event listeners
  addHolidayBtn.addEventListener("click", () =>
    openModal(holidayModal, "Add Holiday")
  );
  addEventBtn.addEventListener("click", () =>
    openModal(eventModal, "Add Event")
  );

  // Modal close
  document
    .getElementById("close-holiday-modal-x")
    .addEventListener("click", () => closeModal(holidayModal));
  document
    .getElementById("close-event-modal-x")
    .addEventListener("click", () => closeModal(eventModal));
  document
    .getElementById("cancel-holiday-btn")
    .addEventListener("click", () => closeModal(holidayModal));
  document
    .getElementById("cancel-event-btn")
    .addEventListener("click", () => closeModal(eventModal));

  // Form submissions
  holidayForm.addEventListener("submit", (e) => handleSubmit(e, "holiday"));
  eventForm.addEventListener("submit", (e) => handleSubmit(e, "event"));

  // Filters
  searchInput.addEventListener("input", loadData);
  typeFilter.addEventListener("change", loadData);
  dateStart.addEventListener("change", loadData);
  dateEnd.addEventListener("change", loadData);

  // Functions
  function openModal(modal, title) {
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    modal.querySelector("h3").textContent = title;
  }

  function closeModal(modal) {
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
    modal.querySelector("form").reset();
    editingItem = null;
  }

  async function handleSubmit(e, type) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    // Client-side validation
    const name = data.get("name");
    const startDate = data.get("start_date");
    const endDate = data.get("end_date");
    let errors = [];

    if (!name) errors.push("Name is required.");
    if (!startDate || !endDate) errors.push("Dates are required.");
    if (new Date(startDate) > new Date(endDate))
      errors.push("Start date must be before or equal to end date.");

    if (type === "holiday") {
      const holidayType = data.get("type");
      if (!holidayType) errors.push("Type is required.");
    } else {
      const paid = data.get("paid");
      if (!paid) errors.push("Paid status is required.");
    }

    if (errors.length > 0) {
      showStatus(errors.join(" "), "error");
      return;
    }

    const action = editingItem ? `edit_${type}` : `add_${type}`;
    data.append("action", action);
    if (editingItem) data.append("id", editingItem.id);

    try {
      const response = await fetch(API_BASE, { method: "POST", body: data });
      const result = await response.json();
      if (result.success) {
        showStatus(
          `${type.charAt(0).toUpperCase() + type.slice(1)} ${
            editingItem ? "updated" : "added"
          } successfully.`,
          "success"
        );
        closeModal(type === "holiday" ? holidayModal : eventModal);
        loadData();
      } else {
        showStatus(result.message, "error");
      }
    } catch (error) {
      showStatus("Network error.", "error");
    }
  }

  async function loadData() {
    const params = new URLSearchParams({
      action: "list_all",
      search: searchInput.value,
      type: typeFilter.value,
      start_date: dateStart.value,
      end_date: dateEnd.value,
    });

    try {
      const response = await fetch(`${API_BASE}?${params}`);
      const result = await response.json();
      if (result.success) {
        renderTable(result.data);
      } else {
        showStatus(result.message, "error");
      }
    } catch (error) {
      showStatus("Failed to load data.", "error");
    }
  }

  function renderTable(data) {
    tableBody.innerHTML = data
      .map(
        (item, index) => `
      <tr class="hover:bg-blue-50 transition-colors rounded-lg">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${
          index + 1
        }</td>
        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900" title="${
          item.name
        }">${
          item.name.length > 15 ? item.name.substring(0, 15) + "..." : item.name
        }</td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getBadgeClass(
            item
          )}">${getBadgeText(item)}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(
          item.start_date
        )}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(
          item.end_date
        )}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" title="${
          item.description || ""
        }">${
          item.description
            ? item.description.substring(0, 15) +
              (item.description.length > 15 ? "..." : "")
            : ""
        }</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
          <button class="text-blue-600 hover:text-blue-900 mr-3 edit-btn" data-type="${
            item.table === "holidays" ? "holiday" : "event"
          }" data-id="${item.id}" title="Edit">
            <i class="fas fa-edit"></i>
          </button>
          <button class="text-red-600 hover:text-red-900 delete-btn" data-type="${
            item.table === "holidays" ? "holiday" : "event"
          }" data-id="${item.id}" title="Delete">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `
      )
      .join("");

    // Attach edit/delete listeners
    document.querySelectorAll(".edit-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const type = e.currentTarget.dataset.type;
        const id = e.currentTarget.dataset.id;
        editItem(type, id);
      });
    });

    document.querySelectorAll(".delete-btn").forEach((btn) => {
      btn.addEventListener("click", async (e) => {
        const type = e.currentTarget.dataset.type;
        const id = e.currentTarget.dataset.id;
        const confirmed = await showConfirmation(
          `Are you sure you want to delete this ${type}?`,
          "Delete",
          "red"
        );
        if (confirmed) deleteItem(type, id);
      });
    });
  }

  async function editItem(type, id) {
    try {
      const response = await fetch(`${API_BASE}?action=view_${type}&id=${id}`);
      const result = await response.json();
      if (result.success) {
        const item = result.data;
        editingItem = { type, id };
        if (type === "holiday") {
          document.getElementById("holiday-name").value = item.name;
          document.getElementById("holiday-type").value = item.type;
          document.getElementById("holiday-start").value = item.start_date;
          document.getElementById("holiday-end").value = item.end_date;
          openModal(holidayModal, "Edit Holiday");
        } else {
          document.getElementById("event-name").value = item.name;
          document.getElementById("event-start").value = item.start_date;
          document.getElementById("event-end").value = item.end_date;
          document.getElementById("event-paid").value = item.paid;
          document.getElementById("event-description").value = item.description;
          openModal(eventModal, "Edit Event");
        }
      } else {
        showStatus(result.message, "error");
      }
    } catch (error) {
      showStatus("Failed to load item.", "error");
    }
  }

  async function deleteItem(type, id) {
    try {
      const response = await fetch(
        `${API_BASE}?action=delete_${type}&id=${id}`,
        { method: "DELETE" }
      );
      const result = await response.json();
      if (result.success) {
        showStatus(
          `${
            type.charAt(0).toUpperCase() + type.slice(1)
          } deleted successfully.`,
          "success"
        );
        loadData();
      } else {
        showStatus(result.message, "error");
      }
    } catch (error) {
      showStatus("Failed to delete item.", "error");
    }
  }

  function getBadgeClass(item) {
    if (item.table === "holidays") {
      switch (item.type) {
        case "regular":
          return "bg-blue-100 text-blue-800";
        case "special_non_working":
          return "bg-teal-100 text-teal-800";
        case "special_working":
          return "bg-purple-100 text-purple-800";
      }
    } else {
      switch (item.paid) {
        case "yes":
          return "bg-green-100 text-green-800";
        case "partial":
          return "bg-yellow-100 text-yellow-800";
        case "no":
          return "bg-gray-100 text-gray-800";
      }
    }
  }

  function getBadgeText(item) {
    if (item.table === "holidays")
      return item.type.replace("_", " ").toUpperCase();
    return item.paid.toUpperCase();
  }

  function formatDate(date) {
    return new Date(date).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }
});
