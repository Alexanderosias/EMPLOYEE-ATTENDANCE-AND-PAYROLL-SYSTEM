document.addEventListener('DOMContentLoaded', () => {
  const forgotPasswordForm = document.getElementById('forgotPasswordForm');
  const userEmailInput = document.getElementById('userEmail');
  const sendButton = document.getElementById('sendButton');
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

  // --- Form Submission and Validation ---
  forgotPasswordForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const userEmail = userEmailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(userEmail)) {
      displayMessage('Please enter a valid email address.');
      return;
    }

    // Loading state
    sendButton.disabled = true;
    sendButton.innerHTML = `<div class="spinner"></div><span class="ml-2">Sending...</span>`;

    try {
      const response = await fetch('../views/forgot_password_handler.php?action=send_code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ email: userEmail })
      });
      const result = await response.json();

      if (result.success) {
        displayMessage('Verification code sent successfully.', false);
        // Redirect after delay
        setTimeout(() => {
          window.location.href = `enter_code.html?email=${encodeURIComponent(userEmail)}`;
        }, 2000);
      } else {
        displayMessage(result.message || 'Failed to send code.');
      }
    } catch (error) {
      console.error('Error:', error);
      displayMessage('An error occurred. Please try again.');
    } finally {
      // Reset button
      sendButton.disabled = false;
      sendButton.innerHTML = 'Send Code';
    }
  });
});