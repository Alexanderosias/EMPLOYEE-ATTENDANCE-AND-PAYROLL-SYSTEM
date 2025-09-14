function updateDateTime() {
    const now = new Date();
    const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const dayName = daysOfWeek[now.getDay()];
    const monthName = months[now.getMonth()];
    const day = now.getDate();
    const year = now.getFullYear();

    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // The hour '0' should be '12'

    const formattedDate = `${dayName}, ${monthName} ${day}, ${year}`;
    const formattedTime = `${hours}:${minutes}:${seconds} ${ampm}`;

    document.getElementById('current-datetime').textContent = `${formattedDate} at ${formattedTime}`;
}

// Update time evry sec
setInterval(updateDateTime, 1000);

// Run on page immediately 
updateDateTime();