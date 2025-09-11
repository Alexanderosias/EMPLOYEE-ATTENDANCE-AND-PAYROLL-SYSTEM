// dashboard.js

document.addEventListener('DOMContentLoaded', () => {
  const messageBox = document.getElementById('successMessageBox');
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');

  // --- Login success message ---
  const loginStatus = sessionStorage.getItem('loginStatus');
  if (loginStatus === 'success') {
    sessionStorage.removeItem('loginStatus');

    messageBox.style.display = 'block';

    setTimeout(() => {
      messageBox.classList.add('visible');
    }, 10);

    setTimeout(() => {
      messageBox.classList.remove('visible');
    }, 5000);

    messageBox.addEventListener(
      'transitionend',
      () => {
        if (!messageBox.classList.contains('visible')) {
          messageBox.style.display = 'none';
        }
      },
      { once: true }
    );
  }
});
