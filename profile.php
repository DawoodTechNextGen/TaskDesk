<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
} else {
    include_once './include/connection.php';
} ?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Profile - TaskDesk';
include "./include/headerLinks.php" ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-50 space-y-4">
        <!-- Toast templates will be inserted here dynamically -->
    </div>
    <div class="flex h-screen overflow-hidden">
        <!-- Modern Sidebar -->
        <?php include "./include/sideBar.php" ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include "./include/header.php" ?>
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-6xl mx-auto mb-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        <!-- Settings Tabs -->
                        <div class="border-b dark:border-gray-600">
                            <nav class="flex -mb-px">
                                <a href="#" data-target="personal-info"
                                    class="tab-btn py-4 px-6 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                                    Account
                                </a>

                                <a href="#" data-target="password-settings"
                                    class="tab-btn py-4 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    Security
                                </a>
                            </nav>

                        </div>

                        <!-- Account Settings Form -->
                        <div class="p-6 tab-content" id="personal-info">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 dark:text-white">Profile Information</h3>

                            <form class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name"
                                            class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Name</label>
                                        <input type="text" id="name" value="<?= $_SESSION['user_name'] ?>"
                                            readonly class="cursor-not-allowed bg-gray-200 dark:bg-gray-700 w-full px-3 py-2 dark:text-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="email"
                                            class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Email
                                            address</label>
                                        <input type="email" readonly id="email" value="<?= $_SESSION['user_email'] ?>"
                                            class="cursor-not-allowed w-full bg-gray-200 dark:bg-gray-700 px-3 py-2 dark:text-white border border-gray-300 rounded-md shadow-sm focus:outline-none">
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                                    <button type="button"
                                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="p-6 tab-content hidden" id="password-settings">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 dark:text-white">Change Password</h3>

                            <form class="space-y-6" id="settings">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">
                                        New Password
                                    </label>
                                    <input type="password" id="new-password" name="new-password" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Enter new password" />
                                </div>

                                <div>
                                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">
                                        Confirm Password
                                    </label>
                                    <input type="password" id="confirm-password" name="confirm-password" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Confirm new password" />
                                </div>
                                </div>
                                <div class="flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                                    <button type="submit"
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </main>
            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>
    <?php include "./include/footerLinks.php" ?>
    <script>
        // Profile edit functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all tabs
                    tabButtons.forEach(btn => {
                        btn.classList.remove('border-indigo-500', 'text-indigo-600');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    });

                    // Add active class to clicked tab
                    this.classList.remove('border-transparent', 'text-gray-500');
                    this.classList.add('border-indigo-500', 'text-indigo-600');

                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });

                    // Show selected tab content
                    const targetId = this.getAttribute('data-target');
                    document.getElementById(targetId).classList.remove('hidden');
                });
            });

            // Profile Information Edit Toggle
            const profileForm = document.querySelector('#personal-info form');
            const profileFields = profileForm.querySelectorAll('input, textarea');
            const editProfileBtn = document.createElement('button');

            // Create Edit Profile button
            editProfileBtn.type = 'button';
            editProfileBtn.className = 'inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500';
            editProfileBtn.textContent = 'Edit Profile';

            // Add Edit Profile button to the form
            const profileButtonContainer = profileForm.querySelector('.flex.justify-end');
            profileButtonContainer.innerHTML = '';
            profileButtonContainer.appendChild(editProfileBtn);

            // Store original values
            const originalValues = {};
            profileFields.forEach(field => {
                originalValues[field.id] = field.value;
            });

            // Edit Profile button click handler
            editProfileBtn.addEventListener('click', function() {
                // Enable all fields
                profileFields.forEach(field => {
                    field.removeAttribute('readonly');
                    field.classList.remove('cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-700');
                });

                // Show Save and Cancel buttons
                profileButtonContainer.innerHTML = `
                    <button type="button" id="cancelEditBtn" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="submit" id="saveEditBtn" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Changes
                    </button>
                `;

                // Add event listeners to the new buttons
                document.getElementById('cancelEditBtn').addEventListener('click', cancelProfileEdit);
                document.getElementById('saveEditBtn').addEventListener('click', saveProfileChanges);
            });

            // Cancel profile edit
            function cancelProfileEdit() {
                // Restore original values
                profileFields.forEach(field => {
                    field.value = originalValues[field.id];
                    field.setAttribute('readonly', true);
                    field.classList.add('cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-700');
                });

                // Show Edit Profile button again
                profileButtonContainer.innerHTML = '';
                profileButtonContainer.appendChild(editProfileBtn);
            }

            // OTP Modal state
            let otpMode = ''; // 'profile' or 'password'
            const otpModal = document.getElementById('otpModal');
            const otpForm = document.getElementById('otpForm');
            const otpCode = document.getElementById('otpCode');
            const otpMessage = document.getElementById('otpMessage');
            const closeOtpBtn = document.getElementById('closeOtpBtn');

            const openOtpModal = (mode) => {
                otpMode = mode;
                otpCode.value = '';
                otpMessage.classList.add('hidden');
                otpModal.classList.remove('hidden');
            };

            const closeOtpModal = () => {
                otpModal.classList.add('hidden');
            };

            closeOtpBtn.addEventListener('click', closeOtpModal);

            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const code = otpCode.value.trim();
                const url = otpMode === 'profile' ? 'controller/update_profile.php' : 'controller/update_password.php';

                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'verify_otp', otp: code })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Updated successfully', 'success');
                        closeOtpModal();
                        if (otpMode === 'profile') {
                            // Update name everywhere in header/sidebar
                            const newName = document.getElementById('name').value;
                            if (document.getElementById('nav-name')) document.getElementById('nav-name').textContent = newName;
                            if (document.getElementById('name-short')) document.getElementById('name-short').textContent = getInitials(newName);
                            if (document.getElementById('sidebar-name')) document.getElementById('sidebar-name').textContent = newName;
                            if (document.getElementById('sidebar-short-name')) document.getElementById('sidebar-short-name').textContent = getInitials(newName);
                            
                            // Update original values
                            profileFields.forEach(field => {
                                originalValues[field.id] = field.value;
                            });

                            // Return to view mode
                            cancelProfileEdit();
                        } else {
                            passwordForm.reset();
                        }
                    } else {
                        otpMessage.textContent = data.error || 'Invalid OTP code.';
                        otpMessage.className = 'mb-4 rounded-xl px-3 py-2 text-xs bg-red-105 text-red-700 dark:bg-red-900/50 dark:text-red-200';
                        otpMessage.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    console.error(err);
                    otpMessage.textContent = 'An error occurred. Please try again.';
                    otpMessage.className = 'mb-4 rounded-xl px-3 py-2 text-xs bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-200';
                    otpMessage.classList.remove('hidden');
                });
            });

            // Save profile changes
            function saveProfileChanges(e) {
                e.preventDefault();

                // Send AJAX request to update profile
                fetch('controller/update_profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            name: document.getElementById('name').value,
                            email: document.getElementById('email').value,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.otp_required) {
                                openOtpModal('profile');
                            } else {
                                showToast('Profile updated successfully', 'success');
                                
                                // Update name everywhere in header/sidebar
                                const newName = document.getElementById('name').value;
                                if (document.getElementById('nav-name')) document.getElementById('nav-name').textContent = newName;
                                if (document.getElementById('name-short')) document.getElementById('name-short').textContent = getInitials(newName);
                                if (document.getElementById('sidebar-name')) document.getElementById('sidebar-name').textContent = newName;
                                if (document.getElementById('sidebar-short-name')) document.getElementById('sidebar-short-name').textContent = getInitials(newName);
                                
                                // Update original values
                                profileFields.forEach(field => {
                                    originalValues[field.id] = field.value;
                                });

                                // Return to view mode
                                cancelProfileEdit();
                            }
                        } else {
                            showToast(data.error || 'Failed to update profile', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred while updating profile', 'error');
                    });
            }

            // Password Change Form Handling
            const passwordForm = document.getElementById('settings');

            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                // Validate passwords
                if (newPassword !== confirmPassword) {
                    showToast('Passwords do not match', 'error');
                    return;
                }

                if (newPassword.length < 6) {
                    showToast('Password must be at least 6 characters long', 'error');
                    return;
                }

                // Send AJAX request to update password
                fetch('controller/update_password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            new_password: newPassword,
                            confirm_password: confirmPassword
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.otp_required) {
                                openOtpModal('password');
                            } else {
                                showToast('Password updated successfully', 'success');
                                passwordForm.reset();
                            }
                        } else {
                            showToast(data.error || 'Failed to update password', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred while updating password', 'error');
                    });
            });

            // Toast notification function
            function showToast(message, type = 'info') {
                const toastContainer = document.getElementById('toast-container');
                const toastId = 'toast-' + Date.now();

                const bgColor = type === 'success' ? 'bg-green-500' :
                    type === 'error' ? 'bg-red-500' :
                    'bg-blue-500';

                const toast = document.createElement('div');
                toast.id = toastId;
                toast.className = `p-4 rounded-lg shadow-lg text-white ${bgColor} transition-all duration-300 transform translate-x-full`;
                toast.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="document.getElementById('${toastId}').remove()" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

                toastContainer.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 10);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (document.getElementById(toastId)) {
                        toast.classList.add('translate-x-full');
                        setTimeout(() => {
                            if (document.getElementById(toastId)) {
                                document.getElementById(toastId).remove();
                            }
                        }, 300);
                    }
                }, 5000);
            }
        });

        function getInitials(fullName) {
            if (!fullName) return '';
            const names = fullName.trim().split(' ');
            let initials = '';
            if (names.length === 1) {
                initials = names[0].charAt(0);
            } else {
                initials = names[0].charAt(0) + names[1].charAt(0);
            }
            return initials.toUpperCase();
        }
    </script>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Container -->
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-200 dark:border-gray-700 p-6 flex flex-col">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Verify Your Identity</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">A verification code (OTP) has been sent to your email. Please enter it below to confirm the changes.</p>
                </div>
                
                <form id="otpForm" class="space-y-4">
                    <div id="otpMessage" class="hidden rounded-xl px-3 py-2 text-xs"></div>
                    <div>
                        <label for="otpCode" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">6-Digit Code</label>
                        <input type="text" id="otpCode" required maxlength="6" pattern="\d{6}" class="w-full text-center tracking-widest text-lg font-bold px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="123456">
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <button type="button" id="closeOtpBtn" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition shadow">
                            Verify & Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>