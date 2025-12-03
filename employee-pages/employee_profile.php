<!-- <?php
        // require_once '../views/auth.php';

        // // Ensure employee is logged in
        // if ($_SESSION['role'] !== 'employee') {
        //     header('Location: ../index.html');
        //     exit;
        // }
        ?> -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - EAAPS</title>
    <link rel="icon" href="../pages/img/adfc_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../pages/css/dashboard.css">
    <link rel="stylesheet" href="../pages/css/profile_details.css">
    <link rel="stylesheet" href="css/status-message.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<style>
    #status-message {
        z-index: 1599;
    }
</style>

<body>
    <!-- Status Message (for feedback) -->
    <div id="status-message" class="status-message"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a class="sidebar-header" href="#">
                <img src="../pages/img/adfc_logo_by_jintokai_d4pchwp-fullview.png" alt="Logo" class="logo" />
                <span class="app-name">EAAPS Employee</span>
            </a>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="employee_dashboard.php">
                            <img src="../pages/icons/home.png" alt="Dashboard" class="icon" />
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="employee_attendance.php">
                            <img src="../pages/icons/clock.png" alt="Attendance" class="icon" />
                            Attendance
                        </a>
                    </li>
                    <li>
                        <a href="employee_schedule.php">
                            <img src="../pages/icons/calendar-deadline-date.png" alt="Schedule" class="icon" />
                            Schedule
                        </a>
                    </li>
                    <li>
                        <a href="employee_payroll.php">
                            <img src="../pages/icons/cash.png" alt="Payroll" class="icon" />
                            Payroll
                        </a>
                    </li>
                    <li>
                        <a href="employee_leave.php">
                            <img src="../pages/icons/swap.png" alt="Leave" class="icon" />
                            Leave
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <img src="../pages/icons/user.png" alt="Profile" class="icon" />
                            Profile
                        </a>
                    </li>
                </ul>
            </nav>
            <a class="logout-btn" href="../index.html">
                <img src="../pages/icons/sign-out-option.png" alt="Logout" class="logout-icon" />
                Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <h2>My Profile</h2>
                    </div>
                    <div>
                        <p id="current-datetime"></p>
                    </div>
                </div>
                <div class="bottom-border"></div>
            </header>

            <div class="scrollbar-container">
                <!-- Profile Overview Card -->
                <div class="profile-card" id="profileCard">
                    <div class="profile-header">
                        <div class="upload-overlay" onclick="triggerImageUpload()">
                            <img src="../pages/icons/camera.png" alt="Upload Photo" class="overlay-icon" />
                        </div>
                        <img id="profileImage" class="profile-avatar" src="../pages/icons/profile-picture.png" alt="Profile Picture" onclick="triggerImageUpload()" />
                        <input type="file" id="imageUpload" accept="image/*" />
                        <div class="profile-info">
                            <h3 id="fullName">Loading...</h3>
                            <p id="roleDisplay">Employee</p>
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div class="profile-section">
                        <h4>
                            <img src="../pages/icons/user-edit.png" alt="Personal Information" class="section-icon" />
                            Personal Information
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" readonly />
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" readonly />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" readonly />
                            </div>
                            <div class="form-group">
                                <label for="phoneNumber">Phone Number *</label>
                                <input type="tel" id="phoneNumber" readonly />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" rows="3" readonly></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dateOfBirth">Date of Birth</label>
                                <input type="date" id="dateOfBirth" readonly />
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <input type="text" id="gender" readonly />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="civilStatus">Civil Status</label>
                            <input type="text" id="civilStatus" readonly />
                        </div>
                    </div>

                    <!-- Emergency Contact Section -->
                    <div class="profile-section">
                        <h4>
                            <img src="../pages/icons/call.png" alt="Emergency Contact" class="section-icon" />
                            Emergency Contact
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergencyContactName">Contact Name</label>
                                <input type="text" id="emergencyContactName" readonly />
                            </div>
                            <div class="form-group">
                                <label for="emergencyContactPhone">Contact Phone</label>
                                <input type="tel" id="emergencyContactPhone" readonly />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="editProfile()">
                            <img src="../pages/icons/edit.png" alt="Edit" class="btn-icon" />
                            Edit Profile
                        </button>
                        <button class="btn btn-primary" onclick="saveProfile()" style="display: none;">
                            <img src="../pages/icons/save.png" alt="Save" class="btn-icon" />
                            Save Changes
                        </button>
                    </div>
                </div>

                <!-- Security Card -->
                <div class="profile-card">
                    <h4 style="color: var(--royal-blue); font-size: 1.25rem; margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center;">
                        <img src="../pages/icons/computer-security-shield.png" alt="Security" class="section-icon" style="margin-right: 0.5rem;" />
                        Security & Password
                    </h4>
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" placeholder="Enter current password" />
                        <button type="button" id="currentPasswordToggle" class="password-toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" placeholder="Enter new password" />
                            <button type="button" id="newPasswordToggle" class="password-toggle">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" placeholder="Confirm new password" />
                            <button type="button" id="confirmPasswordToggle" class="password-toggle">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.575 3.01 9.963 7.173a1.012 1.012 0 010 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.575-3.01-9.963-7.173z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="changePassword()" style="margin-top: 1rem;">
                        <img src="../pages/icons/key.png" alt="Update Password" class="btn-icon" />
                        Update Password
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/current_time.js"></script>
    <script src="../js/auto_logout.js"></script>
    <script>
        // Simple status helper (uses .show class from status-message.css)
        function showStatus(message, type = 'success') {
            const el = document.getElementById('status-message');
            if (!el) return;
            el.textContent = message;
            el.className = 'status-message ' + type;
            el.classList.add('show');
            if (el._hideTimer) clearTimeout(el._hideTimer);
            el._hideTimer = setTimeout(() => {
                el.classList.remove('show');
            }, 3500);
        }

        // Elements
        const profileImage = document.getElementById('profileImage');
        const imageUpload = document.getElementById('imageUpload');

        const firstNameEl = document.getElementById('firstName');
        const lastNameEl = document.getElementById('lastName');
        const emailEl = document.getElementById('email');
        const phoneEl = document.getElementById('phoneNumber');
        const addressEl = document.getElementById('address');
        const genderEl = document.getElementById('gender');
        const civilStatusEl = document.getElementById('civilStatus');
        const emergencyNameEl = document.getElementById('emergencyContactName');
        const emergencyPhoneEl = document.getElementById('emergencyContactPhone');
        const fullNameEl = document.getElementById('fullName');

        const editBtn = document.querySelector('.btn.btn-secondary');
        const saveBtn = document.querySelector('.btn.btn-primary');
        let isEditingProfile = false;

        async function loadProfile() {
            try {
                const res = await fetch('../views/employee_profile_handler.php?action=get_profile');
                const result = await res.json();
                if (!result.success) throw new Error(result.message || 'Failed to load profile');
                const d = result.data || {};

                // Populate fields
                firstNameEl.value = d.first_name || '';
                lastNameEl.value = d.last_name || '';
                emailEl.value = d.email || '';
                phoneEl.value = d.contact_number || d.phone_number || '';
                addressEl.value = d.address || '';
                genderEl.value = d.gender || '';
                civilStatusEl.value = d.marital_status || '';
                emergencyNameEl.value = d.emergency_contact_name || '';
                emergencyPhoneEl.value = d.emergency_contact_phone || '';
                if (fullNameEl) fullNameEl.textContent = `${firstNameEl.value} ${lastNameEl.value}`.trim() || 'Employee';
                if (profileImage && d.avatar_path) profileImage.src = d.avatar_path;
            } catch (e) {
                console.error('Load profile failed:', e);
                showStatus('Failed to load profile', 'error');
            }
        }

        function setEditable(editing) {
            const fields = [firstNameEl, lastNameEl, phoneEl, addressEl, emergencyNameEl, emergencyPhoneEl];
            fields.forEach((el) => el && (el.readOnly = !editing));
            if (editBtn && saveBtn) {
                editBtn.style.display = editing ? 'none' : 'inline-flex';
                saveBtn.style.display = editing ? 'inline-flex' : 'none';
            }
            isEditingProfile = editing;
        }

        function editProfile() {
            setEditable(true);
        }

        async function saveProfile() {
            try {
                const fd = new FormData();
                fd.append('action', 'update_profile');
                fd.append('first_name', firstNameEl.value.trim());
                fd.append('last_name', lastNameEl.value.trim());
                fd.append('phone_number', phoneEl.value.trim());
                fd.append('address', addressEl.value.trim());
                fd.append('emergency_contact_name', emergencyNameEl.value.trim());
                fd.append('emergency_contact_phone', emergencyPhoneEl.value.trim());

                const res = await fetch('../views/employee_profile_handler.php', { method: 'POST', body: fd });
                const result = await res.json();
                if (!result.success) throw new Error(result.message || 'Save failed');
                showStatus(result.message || 'Profile updated', 'success');
                setEditable(false);
                // Refresh UI and name
                await loadProfile();
            } catch (e) {
                console.error('Save profile failed:', e);
                showStatus(e.message, 'error');
            }
        }

        function triggerImageUpload() {
            if (!isEditingProfile) {
                showStatus('Tap Edit Profile first to change your avatar.', 'error');
                return;
            }
            if (imageUpload) imageUpload.click();
        }

        imageUpload && imageUpload.addEventListener('change', async (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            if (!isEditingProfile) {
                showStatus('Tap Edit Profile first to change your avatar.', 'error');
                e.target.value = '';
                return;
            }
            const fd = new FormData();
            fd.append('action', 'update_avatar');
            fd.append('avatar', file);
            try {
                const res = await fetch('../views/employee_profile_handler.php', { method: 'POST', body: fd });
                const result = await res.json();
                if (!result.success) throw new Error(result.message || 'Avatar update failed');
                if (profileImage && result.avatar_path) profileImage.src = result.avatar_path + '?v=' + Date.now();
                showStatus(result.message || 'Avatar updated', 'success');
            } catch (e) {
                console.error('Avatar upload failed:', e);
                showStatus(e.message, 'error');
            } finally {
                e.target.value = '';
            }
        });

        // Password visibility toggles
        function wirePasswordToggle(inputId, btnId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById(btnId);
            if (!input || !btn) return;
            btn.addEventListener('click', () => {
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        }
        wirePasswordToggle('currentPassword', 'currentPasswordToggle');
        wirePasswordToggle('newPassword', 'newPasswordToggle');
        wirePasswordToggle('confirmPassword', 'confirmPasswordToggle');

        async function changePassword() {
            const cur = document.getElementById('currentPassword').value;
            const nw = document.getElementById('newPassword').value;
            const conf = document.getElementById('confirmPassword').value;
            const fd = new FormData();
            fd.append('action', 'change_password');
            fd.append('current_password', cur);
            fd.append('new_password', nw);
            fd.append('confirm_password', conf);
            try {
                const res = await fetch('../views/employee_profile_handler.php', { method: 'POST', body: fd });
                const result = await res.json();
                if (!result.success) throw new Error(result.message || 'Password change failed');
                showStatus(result.message || 'Password changed', 'success');
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
            } catch (e) {
                showStatus(e.message, 'error');
            }
        }

        // Expose for buttons
        window.editProfile = editProfile;
        window.saveProfile = saveProfile;
        window.triggerImageUpload = triggerImageUpload;
        window.changePassword = changePassword;

        // Init
        document.addEventListener('DOMContentLoaded', async () => {
            setEditable(false);
            await loadProfile();
        });
    </script>

</body>

</html>