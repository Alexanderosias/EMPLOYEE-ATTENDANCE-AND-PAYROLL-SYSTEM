let dateFormat = "DD/MM/YYYY"; // Default

async function loadDateFormat() {
  try {
    const response = await fetch("../views/settings_handler.php?action=load");
    const result = await response.json();
    if (result.success && result.data.time_date) {
      dateFormat = result.data.time_date.date_format || "DD/MM/YYYY";
    }
  } catch (error) {
    console.warn("Failed to load date format, using default.");
  }
}

function updateDateTime() {
  const now = new Date();
  const daysOfWeek = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ];
  const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  const dayName = daysOfWeek[now.getDay()];
  const monthName = months[now.getMonth()];
  const day = now.getDate();
  const year = now.getFullYear();

  let hours = now.getHours();
  const minutes = now.getMinutes().toString().padStart(2, "0");
  const seconds = now.getSeconds().toString().padStart(2, "0");
  const ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12;
  hours = hours ? hours : 12;

  const formattedTime = `${hours}:${minutes}:${seconds} ${ampm}`;

  let formattedDate;
  if (dateFormat === "MM/DD/YYYY") {
    formattedDate = `${monthName} ${day}, ${year}`;
  } else if (dateFormat === "YYYY-MM-DD") {
    formattedDate = `${year}-${(now.getMonth() + 1)
      .toString()
      .padStart(2, "0")}-${day.toString().padStart(2, "0")}`;
  } else {
    // DD/MM/YYYY
    formattedDate = `${dayName}, ${day} ${monthName} ${year}`;
  }

  document.getElementById(
    "current-datetime"
  ).textContent = `${formattedDate} at ${formattedTime}`;
}

// Load format and start updating
async function initTime() {
  await loadDateFormat();
  updateDateTime();
  setInterval(updateDateTime, 1000);
}

initTime();
