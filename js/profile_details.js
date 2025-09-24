let isEditMode = false;
    let currentImageFile = null;  // Track uploaded image file

    // Edit Profile function - excludes joinDate, enables image upload
    function editProfile() {
      isEditMode = true;
      const profileCard = document.getElementById('profileCard');
      profileCard.classList.add('edit-mode');

      // Enable editing for all inputs except joinDate
      const inputs = document.querySelectorAll('#profileCard .form-group input:not(#joinDate), #profileCard .form-group select, #profileCard .form-group textarea');
      inputs.forEach(input => {
        input.removeAttribute('readonly');
        input.removeAttribute('disabled');
      });

      // Keep joinDate always readonly
      document.getElementById('joinDate').setAttribute('readonly', true);

      // Show upload overlay and enable avatar click
      document.querySelector('.upload-overlay').style.display = 'flex';
      document.getElementById('profileImage').style.cursor = 'pointer';

      // Update buttons
      document.querySelector('.btn-secondary').style.display = 'none';
      document.querySelector('.btn-primary').style.display = 'inline-flex';

      showStatus('Editing mode enabled. You can now update your profile and upload a new image.', 'success');
    }

    // Save Profile function - handles image upload
    function saveProfile() {
      // Simulate save (replace with API call - include currentImageFile if needed)
      const profileCard = document.getElementById('profileCard');
      profileCard.classList.remove('edit-mode');

      // Disable editing
      const inputs = document.querySelectorAll('#profileCard .form-group input:not(#joinDate), #profileCard .form-group select, #profileCard .form-group textarea');
      inputs.forEach(input => {
        input.setAttribute('readonly', true);
        input.setAttribute('disabled', true);
      });

      // Keep joinDate readonly
      document.getElementById('joinDate').setAttribute('readonly', true);

      // Hide upload overlay and disable avatar click
      document.querySelector('.upload-overlay').style.display = 'none';
      document.getElementById('profileImage').style.cursor = 'default';

      // If image was uploaded, update preview (simulate save - in real app, upload to server)
      if (currentImageFile) {
        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById('profileImage').src = e.target.result;
        };
        reader.readAsDataURL(currentImageFile);
        showStatus('Profile and image updated successfully!', 'success');
        currentImageFile = null;  // Reset after "save"
      } else {
        showStatus('Profile updated successfully!', 'success');
      }

      // Update buttons
      document.querySelector('.btn-secondary').style.display = 'inline-flex';
      document.querySelector('.btn-primary').style.display = 'none';

      isEditMode = false;
    }

    // Trigger image upload on avatar click (only in edit mode)
    function triggerImageUpload() {
      if (!isEditMode) return;
      document.getElementById('imageUpload').click();
    }

    // Handle image upload and preview
    document.getElementById('imageUpload').addEventListener('change', function (event) {
      const file = event.target.files[0];
      if (file) {
        // Validate file type and size (e.g., < 2MB, image only)
        if (!file.type.startsWith('image/')) {
          showStatus('Please select a valid image file.', 'error');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {  // 2MB limit
          showStatus('Image size must be less than 2MB.', 'error');
          return;
        }

        currentImageFile = file;
        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById('profileImage').src = e.target.result;
          showStatus('Image selected. Click Save Changes to upload.', 'success');
        };
        reader.readAsDataURL(file);
      }
    });

    function changePassword() {
      // Simulate password change (replace with validation/API)
      const newPass = document.getElementById('newPassword').value;
      const confirmPass = document.getElementById('confirmPassword').value;
      if (newPass && newPass === confirmPass) {
        showStatus('Password updated successfully!', 'success');
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
      } else {
        showStatus('Passwords do not match or are empty.', 'error');
      }
    }

    function showStatus(message, type) {
      const statusDiv = document.getElementById('statusMessage');
      statusDiv.textContent = message;
      statusDiv.className = `status-message ${type}`;
      statusDiv.style.display = 'block';
      setTimeout(() => {
        statusDiv.style.display = 'none';
        statusDiv.className = 'status-message';
      }, 5000);
    }

    // Auto-hide success message on load if present
    window.addEventListener('load', () => {
      const successBox = document.getElementById('successMessageBox');
      if (successBox && successBox.style.display !== 'none') {
        showStatus(successBox.textContent, 'success');
        successBox.style.display = 'none';
      }
    });