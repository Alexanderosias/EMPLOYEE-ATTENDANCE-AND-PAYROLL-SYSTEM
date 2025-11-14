document.addEventListener('DOMContentLoaded', () => {
  // Check if access is allowed
  if (localStorage.getItem('reset_allowed') !== 'true') {
    window.location.href = 'send_code.html';
    return;
  }
  localStorage.removeItem('reset_allowed');
  
  const resetPasswordForm = document.getElementById('resetPasswordForm');
  const newPasswordInput = document.getElementById('newPassword');
  const confirmPasswordInput = document.getElementById('confirmPassword');
  const resetButton = document.getElementById('resetButton');
  const validationError = document.getElementById('validationError');
  const newPasswordToggle = document.getElementById('newPasswordToggle');
  const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
  const animatedMessageBox = document.getElementById('animatedMessageBox');
  const messageText = document.getElementById('messageText');
  let errorTimeoutId = null;

  // Get email from URL
  const urlParams = new URLSearchParams(window.location.search);
  const email = urlParams.get('email');
  if (!email) {
    window.location.href = 'send_code.html';
  }

  // Function to display message
  const displayMessage = (message, isError = true) => {
    messageText.textContent = message;
    animatedMessageBox.classList.remove('hidden', 'bg-red-500', 'bg-green-500');

    if (isError) {
      animatedMessageBox.classList.add('bg-red-500');
    } else {
      animatedMessageBox.classList.add('bg-green-500');
    }

    setTimeout(() => {
      animatedMessageBox.classList.add('visible');
    }, 10);

    setTimeout(() => {
      animatedMessageBox.classList.remove('visible');
      setTimeout(() => {
        animatedMessageBox.classList.add('hidden');
      }, 500);
    }, 5000);
  };

  // Function to toggle password visibility
  function togglePasswordVisibility(input, toggle) {
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);

    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;
    const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.988 5.623a.9 4 4 0 010 .769 9.877 9.877 0 000 6.046c.381 2.385 1.503 4.266 3.048 5.485C9.37 19.262 10.9 19.5 12.067 19.5c1.167 0 2.697-.238 4.23-.782l.968-.34c.73-.243 1.408-.544 2.046-.902a1.012 1.012 0 00-.063-.035c-.158-.09-.313-.19-.462-.296l-1.07-1.1c-.26-.26-.54-.488-.83-.687a1.012 1.012 0 010-.639C16.64 10.51 16.64 12.49 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`;

    toggle.innerHTML = type === 'password' ? eyeOpen : eyeClosed;
  }

  // Event listeners for password toggles
  newPasswordToggle.addEventListener('click', () => togglePasswordVisibility(newPasswordInput, newPasswordToggle));
  confirmPasswordToggle.addEventListener('click', () => togglePasswordVisibility(confirmPasswordInput, confirmPasswordToggle));

  // --- Form Submission and Validation ---
  resetPasswordForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Clear previous timeout and errors
    if (errorTimeoutId) {
      clearTimeout(errorTimeoutId);
    }
    validationError.classList.add('hidden');
    validationError.textContent = '';
    newPasswordInput.classList.remove('border-red-400');
    confirmPasswordInput.classList.remove('border-red-400');

    let isValid = true;
    let errorMessage = '';
    const newPassword = newPasswordInput.value.trim();
    const confirmPassword = confirmPasswordInput.value.trim();

    // 1. Validate if fields are empty
    if (newPassword === '') {
      errorMessage = 'Please enter your new password.';
      newPasswordInput.classList.add('border-red-400');
      isValid = false;
    } else if (confirmPassword === '') {
      errorMessage = 'Please confirm your new password.';
      confirmPasswordInput.classList.add('border-red-400');
      isValid = false;
    }

    // 2. Validate password complexity
    if (isValid && newPassword !== '') {
      if (newPassword !== confirmPassword) {
        errorMessage = 'Passwords do not match.';
        newPasswordInput.classList.add('border-red-400');
        confirmPasswordInput.classList.add('border-red-400');
        isValid = false;
      } else if (newPassword.length < 8) {
        errorMessage = 'Password must be at least 8 characters long.';
        newPasswordInput.classList.add('border-red-400');
        isValid = false;
      } else if (!/\d/.test(newPassword)) {
        errorMessage = 'Password must contain at least one number.';
        newPasswordInput.classList.add('border-red-400');
        isValid = false;
      } else if (/[^a-zA-Z0-9]/.test(newPassword)) {
        errorMessage = 'Password cannot contain special characters.';
        newPasswordInput.classList.add('border-red-400');
        isValid = false;
      }
    }

    if (!isValid) {
      validationError.textContent = errorMessage;
      validationError.classList.remove('hidden');

      errorTimeoutId = setTimeout(() => {
        validationError.classList.add('hidden');
        validationError.textContent = '';
      }, 5000);
    } else {
      // Submit to API
      resetButton.disabled = true;
      resetButton.innerHTML = `<div class="spinner"></div><span class="ml-2">Resetting...</span>`;

      try {
        const response = await fetch('../views/forgot_password_handler.php?action=reset_password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ email: email, password: newPassword })
        });
        const result = await response.json();
        if (result.success) {
          displayMessage('Password reset successfully!', false);
          setTimeout(() => {
            window.location.href = '../index.html'; // Redirect to login
          }, 2000);
        } else {
          displayMessage('Failed to reset password: ' + result.message, true);
        }
      } catch (error) {
        console.error('Error:', error);
        displayMessage('An error occurred.', true);
      } finally {
        resetButton.disabled = false;
        resetButton.innerHTML = 'Reset Password';
      }
    }
  });
});