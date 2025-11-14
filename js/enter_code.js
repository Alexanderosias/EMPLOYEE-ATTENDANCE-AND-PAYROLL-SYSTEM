document.addEventListener('DOMContentLoaded', () => {
  const verifyCodeForm = document.getElementById('verifyCodeForm');
  const codeInputs = document.querySelectorAll('.code-input');
  const verifyButton = document.getElementById('verifyButton');
  const codeError = document.getElementById('codeError');
  const emailDisplay = document.getElementById('emailDisplay');
  const countdownElement = document.getElementById('countdown');
  const resendLink = document.getElementById('resendLink');
  const animatedMessageBox = document.getElementById('animatedMessageBox');
  const messageText = document.getElementById('messageText');

  // Function to display and hide the animated message box
  const displayMessage = (message, isError = true) => {
    messageText.textContent = message;
    animatedMessageBox.classList.remove('hidden', 'bg-red-500', 'bg-green-500');

    if (isError) {
      animatedMessageBox.classList.add('bg-red-500');
    } else {
      animatedMessageBox.classList.add('bg-green-500');
    }

    // Show the message box with a transition
    setTimeout(() => {
      animatedMessageBox.classList.add('visible');
    }, 10);

    // Hide the message box after 5 seconds
    setTimeout(() => {
      animatedMessageBox.classList.remove('visible');
      // Add the 'hidden' class after the transition is complete
      setTimeout(() => {
        animatedMessageBox.classList.add('hidden');
      }, 500); // Matches the CSS transition duration
    }, 5000); // 5 seconds
  };

  // Get email from URL
  const urlParams = new URLSearchParams(window.location.search);
  const email = urlParams.get('email');
  if (email) {
    emailDisplay.textContent = email;
  } else {
    window.location.href = 'send_code.html';
  }

  let countdown = 0;
  let countdownInterval;

  // Check if countdown is active from localStorage
  const lastResend = localStorage.getItem(`resend_${email}`);
  if (lastResend) {
    const elapsed = Math.floor((Date.now() - parseInt(lastResend)) / 1000);
    if (elapsed < 60) {
      countdown = 60 - elapsed;
      startCountdown();
    }
  }

  // Start countdown
  function startCountdown() {
    resendLink.style.pointerEvents = 'none';
    resendLink.style.color = 'gray';
    countdownElement.style.visibility = 'visible';
    countdownInterval = setInterval(() => {
      countdown--;
      countdownElement.textContent = `Resend available in ${countdown} seconds`;
      if (countdown <= 0) {
        clearInterval(countdownInterval);
        countdownElement.style.visibility = 'hidden';
        resendLink.style.pointerEvents = 'auto';
        resendLink.style.color = '#38bdf8';
        localStorage.removeItem(`resend_${email}`);
      }
    }, 1000);
  }

  // Resend code
  resendLink.addEventListener('click', async (e) => {
    e.preventDefault();
    if (countdown > 0) return;

    // Disable button to prevent spam
    resendLink.disabled = true;
    resendLink.textContent = 'Sending...';

    try {
      const response = await fetch('../views/forgot_password_handler.php?action=send_code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ email: email })
      });
      const result = await response.json();
      if (result.success) {
        displayMessage('Code resent successfully.', false);
        countdown = 60;
        localStorage.setItem(`resend_${email}`, Date.now().toString());
        startCountdown();
      } else {
        displayMessage('Failed to resend code: ' + result.message, true);
      }
    } catch (error) {
      console.error('Error:', error);
      displayMessage('An error occurred.', true);
    } finally {
      // Re-enable button after 1 second
      setTimeout(() => {
        resendLink.disabled = false;
        resendLink.textContent = 'Resend Code';
      }, 1000);
    }
  });

  // Sequential inputs setup
  function setupSequentialInputs() {
    codeInputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        const value = e.target.value;
        if (value.length === 1 && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
          codeInputs[index - 1].focus();
        }
      });

      input.addEventListener('focus', () => {
        const firstEmptyIndex = Array.from(codeInputs).findIndex(i => i.value === '');
        if (index > firstEmptyIndex && firstEmptyIndex !== -1) {
          codeInputs[firstEmptyIndex].focus();
        }
      });
    });
  }

  setupSequentialInputs();

  // Form submission
  verifyCodeForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    codeError.classList.add('hidden');

    let fullCode = '';
    let isValid = true;
    codeInputs.forEach(input => {
      const value = input.value.trim();
      if (value.length !== 1 || !/^\d$/.test(value)) {
        isValid = false;
      }
      fullCode += value;
    });

    if (!isValid || fullCode.length !== 6) {
      codeError.classList.remove('hidden');
      return;
    }

    verifyButton.disabled = true;
    verifyButton.innerHTML = `<div class="spinner"></div><span class="ml-2">Verifying...</span>`;

    try {
      const response = await fetch('../views/forgot_password_handler.php?action=verify_code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ email: email, code: fullCode })
      });
      const result = await response.json();
      if (result.success) {
        localStorage.setItem('reset_allowed', 'true');
        window.location.href = `change_pass.html?email=${encodeURIComponent(email)}`;
      } else {
        codeError.textContent = result.message;
        codeError.classList.remove('hidden');

        setTimeout(() => {
          codeError.classList.add('hidden');
          codeError.textContent = '';
        }, 5000);
      }
    } catch (error) {
      console.error('Error:', error);
      codeError.textContent = 'An error occurred.';
      codeError.classList.remove('hidden');
    } finally {
      verifyButton.disabled = false;
      verifyButton.innerHTML = 'Verify Code';
    }
  });
});