document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const loginButton = document.getElementById('loginButton');
  const passwordToggle = document.getElementById('passwordToggle');
  const animatedMessageBox = document.getElementById('animatedMessageBox');
  const messageText = document.getElementById('messageText');
  const buttonText = document.getElementById('buttonText');
  const buttonSpinner = document.getElementById('buttonSpinner');

  // --- Helper functions ---
  const showMessage = (message, isError = true) => {
    messageText.textContent = message;
    animatedMessageBox.classList.remove('hidden');
    animatedMessageBox.classList.toggle('bg-red-500', isError);
    animatedMessageBox.classList.toggle('bg-green-500', !isError);

    setTimeout(() => {
      animatedMessageBox.classList.add('visible');
    }, 10);

    setTimeout(() => {
      animatedMessageBox.classList.remove('visible');
      animatedMessageBox.addEventListener(
        'transitionend',
        () => {
          if (!animatedMessageBox.classList.contains('visible')) {
            animatedMessageBox.classList.add('hidden');
          }
        },
        { once: true }
      );
    }, 5000);
  };

  const showLoading = (isLoading) => {
    loginButton.disabled = isLoading;
    buttonText.textContent = isLoading ? 'Signing In...' : 'Sign In';
    buttonSpinner.classList.toggle('hidden', !isLoading);
  };

  // --- Password visibility toggle ---
  passwordToggle.addEventListener('click', () => {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    passwordToggle.innerHTML = isPassword
      ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0112 4.5c4.638 0 8.575 3.01 9.963 7.173a10.477 10.477 0 01-3.98 4.05M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
  });

  // --- Form submit ---
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();

    if (!email || !password) {
      showMessage('Please enter both email and password.');
      return;
    }

    showLoading(true);

    try {
      const formData = new FormData(loginForm);

      // In the fetch:
      const response = await fetch('views/login_handler.php', {
        method: 'POST',
        body: formData,
        credentials: 'include' // Required for PHP sessions
      });

      if (!response.ok) {
        throw new Error(`HTTP error: ${response.status}`);
      }

      const result = await response.json();

      if (result.status === 'success') {
        showMessage('Login successful!', false);
        setTimeout(() => {
          window.location.href = result.redirect;
        }, 800);
      } else {
        showMessage(result.message);
      }
    } catch (error) {
      console.error('Login error:', error);
      showMessage(`Login failed: ${error.message}`);
    } finally {
      showLoading(false);
    }
  });
});
