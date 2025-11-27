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
                                <!-- <a href="#" data-target="personal-info"
                                    class="tab-btn py-4 px-6 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                                    Account
                                </a> -->

                                <a href="#" data-target="password-settings"
                                    class="tab-btn py-4 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    Security
                                </a>
                            </nav>

                        </div>

                        <!-- Account Settings Form -->
                        <!-- <div class="p-6 tab-content" id="personal-info"> -->
                            <!-- <h3 class="text-lg font-medium text-gray-900 mb-6 dark:text-white">Profile Information</h3> -->

                            <!-- <form class="space-y-6"> -->
                                <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="last-name"
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
                                </div> -->

                                <!-- <div>
                                    <label for="bio"
                                        class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Bio</label>
                                    <textarea id="bio" rows="3"
                                        class="w-full px-3 py-2 dark:text-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">UX designer with 5+ years of experience creating intuitive and engaging user experiences. Passionate about user-centered design and frontend development.</textarea>
                                </div> -->

                                <!-- <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-white">Profile
                                        photo</label>
                                    <div class="flex items-center">
                                        <div class="relative mr-4">
                                            <img src="./assets/images/user.png" alt="Profile"
                                                class="h-16 w-16 rounded-full object-cover">
                                            <button
                                                class="absolute bottom-0 right-0 bg-indigo-600 text-white p-1 rounded-full hover:bg-indigo-700 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 14 14" fill="none">
                                                    <path
                                                        d="M8.03006 2.35161L2.23203 8.14964C2.00274 8.37893 1.78984 8.80472 1.7407 9.11591L1.42951 11.327C1.31486 12.1295 1.87174 12.6864 2.67428 12.5717L4.88533 12.2605C5.19652 12.2114 5.63878 11.9985 5.8517 11.7692L11.6496 5.97128C12.6487 4.9722 13.1237 3.80928 11.6496 2.33522C10.1919 0.877541 9.02914 1.35253 8.03006 2.35161Z"
                                                        stroke="white" stroke-width="1.5" stroke-miterlimit="10"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path
                                                        d="M7.19482 3.18695C7.68618 4.95582 9.06192 6.33156 10.8308 6.82291"
                                                        stroke="white" stroke-width="1.5" stroke-miterlimit="10"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div>
                                            <button type="button"
                                                class="bg-white dark:bg-gray-800  dark:text-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                Change
                                            </button>
                                            <button type="button"
                                                class="ml-3 bg-white dark:bg-gray-800 dark:text-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div> -->

                                <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="country"
                                            class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Country</label>
                                        <select id="country"
                                            class="w-full px-3 py-2 border bg-gray-50 border-gray-300 dark:text-white  dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                            <option>United States</option>
                                            <option>Canada</option>
                                            <option>Mexico</option>
                                        </select>
                                    </div>
                                </div> -->

                                <!-- <div class="flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                                    <button type="button"
                                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Save Changes
                                    </button>
                                </div>
                            </form> -->
                        <!-- </div> -->
                        <div class="p-6 tab-content" id="password-settings">
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
                                    <button type="button"
                                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Cancel
                                    </button>
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
            // const tabButtons = document.querySelectorAll('.tab-btn');
            // const tabContents = document.querySelectorAll('.tab-content');

            // tabButtons.forEach(button => {
            //     button.addEventListener('click', function(e) {
            //         e.preventDefault();

            //         // Remove active class from all tabs
            //         tabButtons.forEach(btn => {
            //             btn.classList.remove('border-indigo-500', 'text-indigo-600');
            //             btn.classList.add('border-transparent', 'text-gray-500');
            //         });

            //         // Add active class to clicked tab
            //         this.classList.remove('border-transparent', 'text-gray-500');
            //         this.classList.add('border-indigo-500', 'text-indigo-600');

            //         // Hide all tab contents
            //         tabContents.forEach(content => {
            //             content.classList.add('hidden');
            //         });

            //         // Show selected tab content
            //         const targetId = this.getAttribute('data-target');
            //         document.getElementById(targetId).classList.remove('hidden');
            //     });
            // });

            // Profile Information Edit Toggle
            // const profileForm = document.querySelector('#personal-info form');
            // const profileFields = profileForm.querySelectorAll('input, textarea');
            // const profileSaveBtn = profileForm.querySelector('button[type="submit"]');
            // const profileCancelBtn = profileForm.querySelector('button[type="button"]');
            // const editProfileBtn = document.createElement('button');

            // Create Edit Profile button
            // editProfileBtn.type = 'button';
            // editProfileBtn.className = 'inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500';
            // editProfileBtn.textContent = 'Edit Profile';

            // Add Edit Profile button to the form
            // const profileButtonContainer = profileForm.querySelector('.flex.justify-end');
            // profileButtonContainer.innerHTML = '';
            // profileButtonContainer.appendChild(editProfileBtn);

            // Store original values
            // const originalValues = {};
            // profileFields.forEach(field => {
            //     originalValues[field.id] = field.value;
            // });

            // Edit Profile button click handler
            // editProfileBtn.addEventListener('click', function() {
                // Enable all fields except email (as per requirement)
                // profileFields.forEach(field => {
                //     if (field.id !== 'email') {
                //         field.removeAttribute('readonly');
                //         field.classList.remove('cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-700');
                //     }
                // });

                // Show Save and Cancel buttons
        //         profileButtonContainer.innerHTML = `
        //     <button type="button" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        //         Cancel
        //     </button>
        //     <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        //         Save Changes
        //     </button>
        // `;

                // Add event listeners to the new buttons
            //     const newCancelBtn = profileButtonContainer.querySelector('button[type="button"]');
            //     const newSaveBtn = profileButtonContainer.querySelector('button[type="submit"]');

            //     newCancelBtn.addEventListener('click', cancelProfileEdit);
            //     newSaveBtn.addEventListener('click', saveProfileChanges);
            // });

            // Cancel profile edit
            // function cancelProfileEdit() {
                // Restore original values
                // profileFields.forEach(field => {
                //     field.value = originalValues[field.id];
                //     if (field.id !== 'email') {
                //         field.setAttribute('readonly', true);
                //         field.classList.add('cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-700');
                //     }
                // });

                // Show Edit Profile button again
            //     profileButtonContainer.innerHTML = '';
            //     profileButtonContainer.appendChild(editProfileBtn);
            // }

            // Save profile changes
            // function saveProfileChanges(e) {
            //     e.preventDefault();

            //     // Get form data
            //     const formData = new FormData();
            //     formData.append('name', document.getElementById('name').value);
            //     // Note: Email is not editable as per requirement

            //     // Send AJAX request to update profile
            //     fetch('controller/update_profile.php', {
            //             method: 'POST',
            //             headers: {
            //                 'Content-Type': 'application/json',
            //             },
            //             body: JSON.stringify({
            //                 name: document.getElementById('name').value,
            //             })
            //         })
            //         .then(response => response.json())
            //         .then(data => {
            //             if (data.success) {
            //                 showToast('Profile updated successfully', 'success');
            //                 // Update the readonly input value to the new name (in case it's not already)
            //                 document.getElementById('name').value = document.getElementById('name').value;
            //                 document.getElementById('nav-name').textContent = document.getElementById('name').value;
            //                 document.getElementById('name-short').textContent = getInitials(document.getElementById('name').value);

            //                 document.getElementById('sidebar-name').textContent = document.getElementById('name').value;
            //                 document.getElementById('sidebar-short-name').textContent = getInitials(document.getElementById('name').value);
            //                 // Update original values
            //                 profileFields.forEach(field => {
            //                     originalValues[field.id] = field.value;
            //                 });

            //                 // Return to view mode
            //                 cancelProfileEdit();
            //             } else {
            //                 showToast(data.error || 'Failed to update profile', 'error');
            //             }
            //         })
            //         .catch(error => {
            //             console.error('Error:', error);
            //             showToast('An error occurred while updating profile', 'error');
            //         });
            // }

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
                            showToast('Password updated successfully', 'success');
                            passwordForm.reset();
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
</body>

</html>