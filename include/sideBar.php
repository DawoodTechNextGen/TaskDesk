    <style>
        #registrations-arrow {
            color: #4B5563;
            /* Tailwind gray-700 hex */
        }

        .dark #registrations-arrow {
            color: #E5E7EB;
            /* Tailwind gray-200 hex */
        }
    </style>
    <aside id="sidebar"
        class="sidebar-expanded sidebar-transition bg-white dark:bg-gray-800 shadow-lg flex flex-col border-r border-gray-200 dark:border-gray-700">
        <!-- Sidebar Header with Logo and Toggle -->
        <div class="px-4 py-3 flex items-center justify-between flex-row">
            <a href="index.php">
                <div id="logo" class="flex items-center space-x-1 overflow-hidden">
                    <div>
                        <svg
                            class="w-8 h-8"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 246.43 217">
                            <g id="Layer_2" data-name="Layer 2">
                                <polygon
                                    points="75.83 92.24 75.83 217 49.69 217 49.69 117 0 117 0 92.24 75.83 92.24"
                                    fill="#3B82F6" />
                                <path
                                    d="M509.55,301.42A107.07,107.07,0,0,1,402.48,408.5H343.37V283.74h75.38V308.5H369.51v70.29h27.9a80.81,80.81,0,1,0,0-161.61H318.58V251.5H418.75v27.77H263.12V251.5h31v-60H399.63A109.92,109.92,0,0,1,509.55,301.42Z"
                                    transform="translate(-263.12 -191.5)"
                                    fill="#3B82F6" />
                            </g>
                        </svg>
                    </div>
                    <span id="logo-text"
                        class="text-2xl font-bold text-blue-500">TaskDesk</span>
                </div>
            </a>
            <button id="toggle-sidebar"
                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none transition-colors">

                <svg class="sidebar-open hidden" xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                    viewBox="0 0 24 24" fill="none">
                    <path
                        d="M21.97 15V9C21.97 4 19.97 2 14.97 2H8.96997C3.96997 2 1.96997 4 1.96997 9V15C1.96997 20 3.96997 22 8.96997 22H14.97C19.97 22 21.97 20 21.97 15Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    </path>
                    <path d="M7.96997 2V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round"></path>
                    <path d="M14.97 9.43994L12.41 11.9999L14.97 14.5599" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"
                        transform="scale(-1, 1) translate(-27.38, 0)"></path>
                </svg>
                <svg class="sidebar-close" xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                    viewBox="0 0 24 24" fill="none">
                    <path
                        d="M21.97 15V9C21.97 4 19.97 2 14.97 2H8.96997C3.96997 2 1.96997 4 1.96997 9V15C1.96997 20 3.96997 22 8.96997 22H14.97C19.97 22 21.97 20 21.97 15Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    </path>
                    <path d="M7.96997 2V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round"></path>
                    <path d="M14.97 9.43994L12.41 11.9999L14.97 14.5599" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        </div>

        <!-- Sidebar Content with Modern Navigation -->
        <div class="flex-1 overflow-y-auto py-4 custom-scrollbar">
            <nav>
                <div class="px-4 mb-6">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400 tracking-wider mb-2 px-1"
                        id="menu-title">Menu</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="index.php" onclick="window.location=this.href"
                                class="flex items-center space-x-2 p-2 rounded-lg sidebar-link
                                <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'index.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier">
                                            <path d="M22 12.2039V13.725C22 17.6258 22 19.5763 20.8284 20.7881C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.7881C2 19.5763 2 17.6258 2 13.725V12.2039C2 9.91549 2 8.77128 2.5192 7.82274C3.0384 6.87421 3.98695 6.28551 5.88403 5.10813L7.88403 3.86687C9.88939 2.62229 10.8921 2 12 2C13.1079 2 14.1106 2.62229 16.116 3.86687L18.116 5.10812C20.0131 6.28551 20.9616 6.87421 21.4808 7.82274" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M15 18H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        </g>
                                    </svg>
                                </div>
                                <span
                                    class="sidebar-item text-gray-700 dark:text-gray-200">Dashboard</span>
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 3 || $_SESSION['user_role'] == 4) { ?>
                        <li>
                            <a href="reports.php" onclick="window.location=this.href"
                                class="flex items-center space-x-2 p-2 rounded-lg sidebar-link
                                <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'reports.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7 17L7 13M12 17L12 9M17 17L17 5M3 20H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                                <span class="sidebar-item text-gray-700 dark:text-gray-200">Reports</span>
                            </a>
                        </li>
                        <?php } ?>
                        <?php if ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 4) { ?>
                            <li>
                                <?php
                                $currentPage = basename($_SERVER['SCRIPT_NAME']);
                                // List of all registration-related pages to keep parent active
                                $registrationPages = ['registrations.php', 'registrations_new.php', 'registrations_contact.php', 'registrations_interview.php', 'registrations_rejected.php'];
                                $isRegistrationsActive = in_array($currentPage, $registrationPages);
                                ?>
                                <button type="button" onclick="document.getElementById('registrations-submenu').classList.toggle('hidden'); document.getElementById('registrations-arrow').classList.toggle('rotate-180');"
                                    class="w-full flex items-center justify-between p-2 rounded-lg sidebar-link <?php echo $isRegistrationsActive ? 'active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="flex items-center space-x-2">
                                        <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path d="M2 12C2 15.7712 2 17.6569 3.17157 18.8284C4.34315 20 6.22876 20 10 20H14C17.7712 20 19.6569 20 20.8284 18.8284C22 17.6569 22 15.7712 22 12C22 11.0542 22.0185 10.7271 22 10M13 4H10C6.22876 4 4.34315 4 3.17157 5.17157C2.51839 5.82475 2.22937 6.69989 2.10149 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M6 8L7.66438 9.38699M15.8411 9.79908C14.0045 11.3296 13.0861 12.0949 12 12.0949C11.3507 12.0949 10.7614 11.8214 10 11.2744" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <circle cx="19" cy="5" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                                                </g>
                                            </svg>
                                        </div>
                                        <span class="sidebar-item text-gray-700 dark:text-gray-200">Registrations</span>
                                    </div>
                                    <svg
                                        id="registrations-arrow"
                                        class="w-4 h-4 transition-transform duration-200 <?php echo $isRegistrationsActive ? 'rotate-180' : '' ?>"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <ul id="registrations-submenu" class="<?php echo $isRegistrationsActive ? '' : 'hidden' ?> py-2 space-y-2">
                                    <li>
                                        <a href="registrations_new.php" class="flex items-center space-x-1 w-full p-2 text-gray-700 dark:text-gray-200 transition duration-75 rounded-lg pl-7 group hover:bg-gray-100 dark:hover:bg-gray-700 <?php echo ($currentPage == 'registrations_new.php') ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                                            <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                    <g id="SVGRepo_iconCarrier">
                                                        <path d="M12 6L12 8M12 8L12 10M12 8H9.99998M12 8L14 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M8 14H9M16 14H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M9 18H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M3 14V10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2 7.22876 2 11 2H13C16.7712 2 18.6569 2 19.8284 3.17157C20.4816 3.82476 20.7706 4.69989 20.8985 6M21 10V14C21 17.7712 21 19.6569 19.8284 20.8284C18.6569 22 16.7712 22 13 22H11C7.22876 22 5.34315 22 4.17157 20.8284C3.51839 20.1752 3.22937 19.3001 3.10149 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <span
                                                class="sidebar-item text-gray-700 dark:text-gray-200">New</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="registrations_contact.php" class="flex items-center space-x-1 w-full p-2 text-gray-700 dark:text-gray-200 transition duration-75 rounded-lg pl-7 group hover:bg-gray-100 dark:hover:bg-gray-700 <?php echo ($currentPage == 'registrations_contact.php') ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                                            <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                    <g id="SVGRepo_iconCarrier">
                                                        <path d="M4.00655 7.93309C3.93421 9.84122 4.41713 13.0817 7.6677 16.3323C8.45191 17.1165 9.23553 17.7396 10 18.2327M5.53781 4.93723C6.93076 3.54428 9.15317 3.73144 10.0376 5.31617L10.6866 6.4791C11.2723 7.52858 11.0372 8.90532 10.1147 9.8278C10.1147 9.8278 10.1147 9.8278 10.1147 9.8278C10.1146 9.82792 8.99588 10.9468 11.0245 12.9755C13.0525 15.0035 14.1714 13.8861 14.1722 13.8853C14.1722 13.8853 14.1722 13.8853 14.1722 13.8853C15.0947 12.9628 16.4714 12.7277 17.5209 13.3134L18.6838 13.9624C20.2686 14.8468 20.4557 17.0692 19.0628 18.4622C18.2258 19.2992 17.2004 19.9505 16.0669 19.9934C15.2529 20.0243 14.1963 19.9541 13 19.6111" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <span
                                                class="sidebar-item text-gray-700 dark:text-gray-200">Contact</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="registrations_interview.php" class="flex items-center space-x-1 w-full p-2 text-gray-700 dark:text-gray-200 transition duration-75 rounded-lg pl-7 group hover:bg-gray-100 dark:hover:bg-gray-700 <?php echo ($currentPage == 'registrations_interview.php') ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                                            <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                    <g id="SVGRepo_iconCarrier">
                                                        <path d="M14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C20.1752 21.4816 19.3001 21.7706 18 21.8985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M7 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M17 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M21.5 9H16.625H10.75M2 9H5.875" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M18 17C18 17.5523 17.5523 18 17 18C16.4477 18 16 17.5523 16 17C16 16.4477 16.4477 16 17 16C17.5523 16 18 16.4477 18 17Z" fill="currentColor"></path>
                                                        <path d="M18 13C18 13.5523 17.5523 14 17 14C16.4477 14 16 13.5523 16 13C16 12.4477 16.4477 12 17 12C17.5523 12 18 12.4477 18 13Z" fill="currentColor"></path>
                                                        <path d="M13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17Z" fill="currentColor"></path>
                                                        <path d="M13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13Z" fill="currentColor"></path>
                                                        <path d="M8 17C8 17.5523 7.55228 18 7 18C6.44772 18 6 17.5523 6 17C6 16.4477 6.44772 16 7 16C7.55228 16 8 16.4477 8 17Z" fill="currentColor"></path>
                                                        <path d="M8 13C8 13.5523 7.55228 14 7 14C6.44772 14 6 13.5523 6 13C6 12.4477 6.44772 12 7 12C7.55228 12 8 12.4477 8 13Z" fill="currentColor"></path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <span class="sidebar-item text-gray-700 dark:text-gray-200">Interview</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="registrations_rejected.php" class="flex items-center space-x-1 w-full p-2 text-gray-700 dark:text-gray-200 transition duration-75 rounded-lg pl-7 group hover:bg-gray-100 dark:hover:bg-gray-700 <?php echo ($currentPage == 'registrations_rejected.php') ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                                            <div class="sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                    <g id="SVGRepo_iconCarrier">
                                                        <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path>
                                                        <path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5M18.8334 8.5L18.6334 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <span class="sidebar-item text-gray-700 dark:text-gray-200">Rejected</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if ($_SESSION['user_role'] == 1) { ?>
                            <li>
                                <a href="tech.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'tech.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M6 4C6 5.10457 5.10457 6 4 6C2.89543 6 2 5.10457 2 4C2 2.89543 2.89543 2 4 2C5.10457 2 6 2.89543 6 4Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M6 20C6 21.1046 5.10457 22 4 22C2.89543 22 2 21.1046 2 20C2 18.8954 2.89543 18 4 18C5.10457 18 6 18.8954 6 20Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M14 20C14 21.1046 13.1046 22 12 22C10.8954 22 10 21.1046 10 20C10 18.8954 10.8954 18 12 18C13.1046 18 14 18.8954 14 20Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M14 4C14 5.10457 13.1046 6 12 6C10.8954 6 10 5.10457 10 4C10 2.89543 10.8954 2 12 2C13.1046 2 14 2.89543 14 4Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M22 4C22 5.10457 21.1046 6 20 6C18.8954 6 18 5.10457 18 4C18 2.89543 18.8954 2 20 2C21.1046 2 22 2.89543 22 4Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M12 6V13M12 18V16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M4 18V11M4 6V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M20 6V8C20 9.88562 20 10.8284 19.4142 11.4142C18.8284 12 17.8856 12 16 12H10M4 12H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M18 15V14.25C17.5858 14.25 17.25 14.5858 17.25 15H18ZM17.25 22C17.25 22.4142 17.5858 22.75 18 22.75C18.4142 22.75 18.75 22.4142 18.75 22H17.25ZM21.3604 22.3916C21.5766 22.7449 22.0384 22.8559 22.3916 22.6396C22.7449 22.4234 22.8559 21.9616 22.6396 21.6084L21.3604 22.3916ZM18 15.75H20.2857V14.25H18V15.75ZM18.75 18.5V15H17.25V18.5H18.75ZM21.25 16.75C21.25 17.3169 20.8038 17.75 20.2857 17.75V19.25C21.6612 19.25 22.75 18.1161 22.75 16.75H21.25ZM20.2857 15.75C20.8038 15.75 21.25 16.1831 21.25 16.75H22.75C22.75 15.3839 21.6612 14.25 20.2857 14.25V15.75ZM20.2857 17.75H19.8571V19.25H20.2857V17.75ZM19.8571 17.75H18V19.25H19.8571V17.75ZM19.2175 18.8916L21.3604 22.3916L22.6396 21.6084L20.4968 18.1084L19.2175 18.8916ZM17.25 18.5V22H18.75V18.5H17.25Z" fill="currentColor"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Tech</span>
                                </a>
                            </li>
                            <li>
                                <a href="supervisors.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'supervisors.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <circle cx="9" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                                                <path d="M15 9C16.6569 9 18 7.65685 18 6C18 4.34315 16.6569 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M5.88915 20.5843C6.82627 20.8504 7.88256 21 9 21C12.866 21 16 19.2091 16 17C16 14.7909 12.866 13 9 13C5.13401 13 2 14.7909 2 17C2 17.3453 2.07657 17.6804 2.22053 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M18 14C19.7542 14.3847 21 15.3589 21 16.5C21 17.5293 19.9863 18.4229 18.5 18.8704" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Surpervisors</span>
                                </a>
                            </li>
                        <?php }
                        if ($_SESSION['user_role'] == 1) {
                        ?>

                            <li>
                                <a href="managers.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'managers.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <circle cx="9" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                                                <path d="M15 9C16.6569 9 18 7.65685 18 6C18 4.34315 16.6569 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M5.88915 20.5843C6.82627 20.8504 7.88256 21 9 21C12.866 21 16 19.2091 16 17C16 14.7909 12.866 13 9 13C5.13401 13 2 14.7909 2 17C2 17.3453 2.07657 17.6804 2.22053 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M18 14C19.7542 14.3847 21 15.3589 21 16.5C21 17.5293 19.9863 18.4229 18.5 18.8704" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Managers</span>
                                </a>
                            </li>
                        <?php
                        } ?>
                        <?php if ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 3) { ?>
                            <li>
                                <a href="internees.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'internees.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <circle cx="9" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                                                <path d="M15 9C16.6569 9 18 7.65685 18 6C18 4.34315 16.6569 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M5.88915 20.5843C6.82627 20.8504 7.88256 21 9 21C12.866 21 16 19.2091 16 17C16 14.7909 12.866 13 9 13C5.13401 13 2 14.7909 2 17C2 17.3453 2.07657 17.6804 2.22053 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M18 14C19.7542 14.3847 21 15.3589 21 16.5C21 17.5293 19.9863 18.4229 18.5 18.8704" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Active Interns</span>
                                </a>
                            </li>
                            <li>
                                <a href="frozen_interns.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'frozen_interns.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                       <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path d="M12 2V18M12 22V18M12 18L15 21M12 18L9 21M15 3L12 6L9 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M3.33978 7.00042L6.80389 9.00042M6.80389 9.00042L17.1962 15.0004M6.80389 9.00042L5.70581 4.90234M6.80389 9.00042L2.70581 10.0985M17.1962 15.0004L20.6603 17.0004M17.1962 15.0004L21.2943 13.9023M17.1962 15.0004L18.2943 19.0985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M20.66 7.00042L17.1959 9.00042M17.1959 9.00042L6.80364 15.0004M17.1959 9.00042L18.294 4.90234M17.1959 9.00042L21.294 10.0985M6.80364 15.0004L3.33954 17.0004M6.80364 15.0004L2.70557 13.9023M6.80364 15.0004L5.70557 19.0985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                </g>
                                            </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Frozen Interns</span>
                                </a>
                            </li>
                            <li>
                                <a href="completed_interns.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'completed_interns.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="1.5" />
                                            <path d="M8 12L10.5 14.5L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Completed Interns</span>
                                </a>
                            </li>
                            <?php if ($_SESSION['user_role'] == 3) { ?>
                                <li>
                                    <a href="attendance_supervisor.php" onclick="window.location=this.href"
                                        class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                    <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'attendance_supervisor.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                        <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        </div>
                                        <span class="sidebar-item text-gray-700 dark:text-gray-200">Intern Attendance</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="freeze_management.php" onclick="window.location=this.href"
                                        class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                    <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'freeze_management.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                        <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path d="M12 2V18M12 22V18M12 18L15 21M12 18L9 21M15 3L12 6L9 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M3.33978 7.00042L6.80389 9.00042M6.80389 9.00042L17.1962 15.0004M6.80389 9.00042L5.70581 4.90234M6.80389 9.00042L2.70581 10.0985M17.1962 15.0004L20.6603 17.0004M17.1962 15.0004L21.2943 13.9023M17.1962 15.0004L18.2943 19.0985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M20.66 7.00042L17.1959 9.00042M17.1959 9.00042L6.80364 15.0004M17.1959 9.00042L18.294 4.90234M17.1959 9.00042L21.294 10.0985M6.80364 15.0004L3.33954 17.0004M6.80364 15.0004L2.70557 13.9023M6.80364 15.0004L5.70557 19.0985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                </g>
                                            </svg>
                                        </div>
                                        <span class="sidebar-item text-gray-700 dark:text-gray-200">Freeze Management</span>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php } ?>
                        <?php if ($_SESSION['user_role'] == 2) { ?>
                            <li id="update-new-tasks">
                                <a href="assignedTasks.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'assignedTasks.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div id="new-tasks" class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M21 16.0002C21 18.8286 21 20.2429 20.1213 21.1215C19.2426 22.0002 17.8284 22.0002 15 22.0002H9C6.17157 22.0002 4.75736 22.0002 3.87868 21.1215C3 20.2429 3 18.8286 3 16.0002V13.0002M16 4.00195C18.175 4.01406 19.3529 4.11051 20.1213 4.87889C21 5.75757 21 7.17179 21 10.0002V12.0002M8 4.00195C5.82497 4.01406 4.64706 4.11051 3.87868 4.87889C3.11032 5.64725 3.01385 6.82511 3.00174 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M9 17.5H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M8 3.5C8 2.67157 8.67157 2 9.5 2H14.5C15.3284 2 16 2.67157 16 3.5V4.5C16 5.32843 15.3284 6 14.5 6H9.5C8.67157 6 8 5.32843 8 4.5V3.5Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M8 14H9M16 14H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M17 10.5H15M12 10.5H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Assigned Tasks</span>
                                </a>
                            </li>
                            <?php if ($_SESSION['approval_status'] == 1) { ?>
                                <li id="certificate">
                                    <a href="generate_certificate.php" onclick="window.location=this.href"
                                        class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'generate_certificate.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                        <div id="new-tasks" class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <circle cx="12" cy="16" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                                                    <path d="M12 19.2599L9.73713 21.4293C9.41306 21.74 9.25102 21.8953 9.1138 21.9491C8.80111 22.0716 8.45425 21.9667 8.28977 21.7C8.21758 21.583 8.19509 21.3719 8.1501 20.9496C8.1247 20.7113 8.112 20.5921 8.07345 20.4922C7.98715 20.2687 7.80579 20.0948 7.57266 20.0121C7.46853 19.9751 7.3442 19.963 7.09553 19.9386C6.65512 19.8955 6.43491 19.8739 6.31283 19.8047C6.03463 19.647 5.92529 19.3145 6.05306 19.0147C6.10913 18.8832 6.27116 18.7278 6.59523 18.4171L8.07345 16.9999L9.1138 15.9596" stroke="currentColor" stroke-width="1.5"></path>
                                                    <path d="M12 19.2599L14.2629 21.4294C14.5869 21.7401 14.749 21.8954 14.8862 21.9492C15.1989 22.0717 15.5457 21.9668 15.7102 21.7001C15.7824 21.5831 15.8049 21.372 15.8499 20.9497C15.8753 20.7113 15.888 20.5921 15.9265 20.4923C16.0129 20.2688 16.1942 20.0949 16.4273 20.0122C16.5315 19.9752 16.6558 19.9631 16.9045 19.9387C17.3449 19.8956 17.5651 19.874 17.6872 19.8048C17.9654 19.6471 18.0747 19.3146 17.9469 19.0148C17.8909 18.8832 17.7288 18.7279 17.4048 18.4172L15.9265 17L15 16.0735" stroke="currentColor" stroke-width="1.5"></path>
                                                    <path d="M17.3197 17.9957C19.2921 17.9748 20.3915 17.8512 21.1213 17.1213C22 16.2426 22 14.8284 22 12V9M7 17.9983C4.82497 17.9862 3.64706 17.8897 2.87868 17.1213C2 16.2426 2 14.8284 2 12L2 8C2 5.17157 2 3.75736 2.87868 2.87868C3.75736 2 5.17157 2 8 2L16 2C18.8284 2 20.2426 2 21.1213 2.87868C21.6112 3.36857 21.828 4.02491 21.9239 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M9 6L15 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M7 9.5H9M17 9.5H12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                </g>
                                            </svg>
                                        </div>
                                        <span class="sidebar-item text-gray-700 dark:text-gray-200">Get Your Certificate</span>
                                    </a>
                                </li>
                            <?php } ?>
                            <li>
                                <a href="attendance_intern.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'attendance_intern.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">My Attendance</span>
                                </a>
                            </li>
                            <li>
                                <a href="freeze_request.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 <?php echo (basename($_SERVER['SCRIPT_NAME']) == 'freeze_request.php') ? ' active-sidebar-link' : 'sidebar-link-border' ?>">
                                    <div class="relative sidebar-icon w-6 text-center text-gray-500 dark:text-gray-400">
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M12 2V18M12 22V18M12 18L15 21M12 18L9 21M15 3L12 6L9 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M3.33978 7.00042L6.80389 9.00042M6.80389 9.00042L17.1962 15.0004M6.80389 9.00042L5.70581 4.90234M6.80389 9.00042L2.70581 10.0985M17.1962 15.0004L20.6603 17.0004M17.1962 15.0004L21.2943 13.9023M17.1962 15.0004L18.2943 19.0985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M20.66 7.00042L17.1959 9.00042M17.1959 9.00042L6.80364 15.0004M17.1959 9.00042L18.294 4.90234M17.1959 9.00042L21.294 10.0985M6.80364 15.0004L3.33954 17.0004M6.80364 15.0004L2.70557 13.9023M6.80364 15.0004L5.70557 19.0985" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <span class="sidebar-item text-gray-700 dark:text-gray-200">Freeze Request</span>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Sidebar Footer with User Profile and Theme Toggle -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3 flex-row">
                <div id="footer-profile" class="flex items-center space-x-2">
                    <div id="sidebar-short-name" class="h-8 w-8 bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-200 rounded-full flex items-center justify-center font-semibold">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1) . substr(strstr($_SESSION['user_name'], ' '), 1, 1)) ?>
                    </div>
                    <div class="sidebar-item">
                        <p id="sidebar-name" class="font-medium text-gray-800 dark:text-white text-sm"><?= $_SESSION['user_name']; ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"> <?php
                                                                                echo $_SESSION['tech'] . " " . (
                                                                                    $_SESSION['user_role'] == '1' ? 'Admin' : ($_SESSION['user_role'] == '2' ? 'Intern' : ($_SESSION['user_role'] == '3' ? 'Supervisor' : 'Manager'))
                                                                                );
                                                                                ?>
                        </p>
                    </div>
                </div>
                <button id="sidebar-theme-toggle"
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none transition-colors">
                    <svg class="size-6 moon dark:hidden" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M20.2496 14.1987C19.5326 14.3951 18.7782 14.5 18 14.5C13.3056 14.5 9.5 10.6944 9.5 5.99999C9.5 5.22185 9.60487 4.4674 9.80124 3.75043C6.15452 4.72095 3.46777 8.04578 3.46777 11.9981C3.46777 16.7114 7.28864 20.5323 12.0019 20.5323C15.9543 20.5323 19.2791 17.8455 20.2496 14.1987ZM20.5196 12.5328C19.7378 12.8346 18.8882 13 18 13C14.134 13 11 9.86598 11 5.99999C11 5.11181 11.1654 4.26226 11.4671 3.48047C11.6142 3.09951 11.7935 2.73464 12.0019 2.38923C12.0888 2.24526 12.1807 2.10466 12.2774 1.9677C12.1858 1.96523 12.094 1.96399 12.0019 1.96399C11.4758 1.96399 10.9592 2.00448 10.455 2.0825C5.64774 2.8264 1.96777 6.98251 1.96777 11.9981C1.96777 17.5398 6.46021 22.0323 12.0019 22.0323C17.0176 22.0323 21.1737 18.3523 21.9176 13.545C21.9956 13.0408 22.0361 12.5242 22.0361 11.9981C22.0361 11.906 22.0348 11.8141 22.0323 11.7226C21.8953 11.8193 21.7547 11.9112 21.6107 11.9981C21.2653 12.2065 20.9005 12.3858 20.5196 12.5328Z"
                            fill="currentColor"></path>
                        <path
                            d="M16.3333 5.33333L17.5 3L18.6667 5.33333L21 6.5L18.6667 7.66667L17.5 10L16.3333 7.66667L14 6.5L16.3333 5.33333Z"
                            fill="currentColor"></path>
                    </svg>
                    <svg class="size-6 sun dark:block" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12.0002 3.29071V1.76746M5.8418 18.1585L4.7647 19.2356M12.0002 22.2326V20.7093M19.2357 4.76456L18.1586 5.84166M20.7095 12H22.2327M18.1586 18.1584L19.2357 19.2355M1.76758 12H3.29083M4.76462 4.7645L5.84173 5.8416M15.7123 8.2877C17.7626 10.338 17.7626 13.6621 15.7123 15.7123C13.6621 17.7626 10.338 17.7626 8.2877 15.7123C6.23745 13.6621 6.23745 10.338 8.2877 8.2877C10.338 6.23745 13.6621 6.23745 15.7123 8.2877Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="square"
                            stroke-linejoin="round"></path>
                    </svg>
                </button>
            </div>
            <a href="logout.php"
                class="w-full flex items-center justify-center space-x-2 p-2 rounded-lg bg-red-600 hover:bg-red-700 transition-colors focus:ring-red-400">
                <svg class="me-2 text-white" width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="matrix(-1, 0, 0, 1, 0, 0)">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path d="M15 12L2 12M2 12L5.5 9M2 12L5.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M9.00195 7C9.01406 4.82497 9.11051 3.64706 9.87889 2.87868C10.7576 2 12.1718 2 15.0002 2L16.0002 2C18.8286 2 20.2429 2 21.1215 2.87868C22.0002 3.75736 22.0002 5.17157 22.0002 8L22.0002 16C22.0002 18.8284 22.0002 20.2426 21.1215 21.1213C20.3531 21.8897 19.1752 21.9862 17 21.9983M9.00195 17C9.01406 19.175 9.11051 20.3529 9.87889 21.1213C10.5202 21.7626 11.4467 21.9359 13 21.9827" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                    </g>
                </svg>
                <span class="sidebar-item text-white font-medium">Logout</span>
            </a>
        </div>
    </aside>