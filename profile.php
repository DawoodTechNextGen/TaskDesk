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
                <div class="max-w-6xl mx-auto">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        <!-- Settings Tabs -->
                        <div class="border-b dark:border-gray-600">
                            <nav class="flex -mb-px">
                                <a href="#"
                                    class="py-4 px-6 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                                    Account
                                </a>
                                <a href="#"
                                    class="py-4 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300">
                                    Security
                                </a>
                                <a href="#"
                                    class="py-4 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300">
                                    Notifications
                                </a>
                                <a href="#"
                                    class="py-4 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300">
                                    Billing
                                </a>
                            </nav>
                        </div>

                        <!-- Account Settings Form -->
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6 dark:text-white">Profile Information</h3>

                            <form class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="first-name"
                                            class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">First
                                            name</label>
                                        <input type="text" id="first-name" value="Sarah"
                                            class="w-full px-3 py-2 border border-gray-300 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="last-name"
                                            class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Last
                                            name</label>
                                        <input type="text" id="last-name" value="Johnson"
                                            class="w-full px-3 py-2 dark:text-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label for="email"
                                        class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Email
                                        address</label>
                                    <input type="email" id="email" value="sarah.johnson@example.com"
                                        class="w-full px-3 py-2 dark:text-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label for="bio"
                                        class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">Bio</label>
                                    <textarea id="bio" rows="3"
                                        class="w-full px-3 py-2 dark:text-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">UX designer with 5+ years of experience creating intuitive and engaging user experiences. Passionate about user-centered design and frontend development.</textarea>
                                </div>

                                <div>
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
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                </div>

                                <div class="flex justify-end space-x-3 pt-6 border-t">
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
                    </div>

                    <!-- Danger Zone -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mt-6 border dark:border-red-950 border-red-200 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2 text-red-600">Danger Zone</h3>
                            <p class="text-sm text-gray-500 mb-4">These actions are irreversible. Proceed with caution.
                            </p>

                            <div class="space-y-4">
                                <div
                                    class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-800 dark:bg-opacity-50 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Deactivate Account</h4>
                                        <p class="text-sm text-gray-500">Your account will be deactivated but not
                                            permanently deleted.</p>
                                    </div>
                                    <button
                                        class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Deactivate
                                    </button>
                                </div>

                                <div
                                    class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-800 dark:bg-opacity-50 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Delete Account</h4>
                                        <p class="text-sm text-gray-500">Permanently delete your account and all
                                            associated data.</p>
                                    </div>
                                    <button
                                        class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- Footer -->
           <?php include "./include/footer.php" ?>
        </div>
    </div>
    <?php include "./include/footerLinks.php" ?>
</body>

</html>