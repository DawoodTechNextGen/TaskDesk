
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisors Management - TaskDesk</title>
     <link rel="icon" type="image/png" sizes="32x32" href="http://localhost/task-management/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="http://localhost/task-management/assets/images/favicon-16x16.png">
    <link rel="stylesheet" href="http://localhost/task-management/assets/css/output.css">
    <link rel="stylesheet" href="http://localhost/task-management/assets/css/libs/datatables.css">
    <script src="http://localhost/task-management/assets/js/libs/jQuery.js"></script>
    <script src="http://localhost/task-management/assets/js/libs/dataTables.js"></script>
    <script src="http://localhost/task-management/assets/js/libs/ckeditor.js"></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
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
                                sidebar-link-border">
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
                                                    <li>
                                <a href="tech.php" onclick="window.location=this.href"
                                    class="flex items-center space-x-2 p-2 rounded-lg sidebar-link 
                                 sidebar-link-border">
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
                                  active-sidebar-link">
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
                                                                                            </ul>
                </div>
            </nav>
        </div>

        <!-- Sidebar Footer with User Profile and Theme Toggle -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3 flex-row">
                <div id="footer-profile" class="flex items-center space-x-2">
                    <div id="sidebar-short-name" class="h-8 w-8 bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-200 rounded-full flex items-center justify-center font-semibold">
                        AR                    </div>
                    <div class="sidebar-item">
                        <p id="sidebar-name" class="font-medium text-gray-800 dark:text-white text-sm">Abdul Rehman</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"> Web Development Admin                        </p>
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
    </aside>        <div class="flex-1 flex flex-col overflow-hidden">
             <header
     class="header-half fixed z-1 border-b border-gray-200 dark:border-gray-700 bg-white/10 dark:bg-gray-800/10 backdrop-blur-md ">
     <div class="flex items-center justify-between px-6 py-4">
         <div class="flex items-center space-x-4">
             <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Admin Dashboard</h1>
         </div>
         <div class="flex items-center align-middle space-x-4">
             <button id="dark-mode-toggle"
                 class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 focus:outline-none transition-colors">
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

             <div class="relative pe-2">
                 <button id="user-menu-button" class="group flex items-center space-x-1 focus:outline-none">
                     <span id="nav-name" class="text-gray-700 dark:text-gray-200 hidden md:inline text-sm">Abdul Rehman</span>
                     <div id="name-short" class="h-8 w-8 bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-200 rounded-full flex items-center justify-center font-semibold">
                         AR                     </div>
                     <span>
                        <svg id="userArrow" class="text-gray-700 w-4 h-4 dark:text-gray-200 transform transition-transform duration-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M5.70711 9.71069C5.31658 10.1012 5.31658 10.7344 5.70711 11.1249L10.5993 16.0123C11.3805 16.7927 12.6463 16.7924 13.4271 16.0117L18.3174 11.1213C18.708 10.7308 18.708 10.0976 18.3174 9.70708C17.9269 9.31655 17.2937 9.31655 16.9032 9.70708L12.7176 13.8927C12.3271 14.2833 11.6939 14.2832 11.3034 13.8927L7.12132 9.71069C6.7308 9.32016 6.09763 9.32016 5.70711 9.71069Z" fill="currentColor"></path> </g></svg>
                     </span>
                 </button>
                 <div id="user-menu"
                     class="hidden opacity-0 -translate-y-2 transition-all duration-300 absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-20 border border-gray-200 dark:border-gray-700">
                     <a href="profile.php"
                         class="flex px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                         <svg class="me-2" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                             <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                             <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                             <g id="SVGRepo_iconCarrier">
                                 <circle cx="12" cy="9" r="2" stroke="currentColor" stroke-width="1.5"></circle>
                                 <path d="M16 15C16 16.1046 16 17 12 17C8 17 8 16.1046 8 15C8 13.8954 9.79086 13 12 13C14.2091 13 16 13.8954 16 15Z" stroke="currentColor" stroke-width="1.5"></path>
                                 <path d="M3 10.4167C3 7.21907 3 5.62028 3.37752 5.08241C3.75503 4.54454 5.25832 4.02996 8.26491 3.00079L8.83772 2.80472C10.405 2.26824 11.1886 2 12 2C12.8114 2 13.595 2.26824 15.1623 2.80472L15.7351 3.00079C18.7417 4.02996 20.245 4.54454 20.6225 5.08241C21 5.62028 21 7.21907 21 10.4167C21 10.8996 21 11.4234 21 11.9914C21 14.4963 20.1632 16.4284 19 17.9041M3.19284 14C4.05026 18.2984 7.57641 20.5129 9.89856 21.5273C10.62 21.8424 10.9807 22 12 22C13.0193 22 13.38 21.8424 14.1014 21.5273C14.6796 21.2747 15.3324 20.9478 16 20.5328" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                             </g>
                         </svg>
                         Profile
                     </a>
                     <a href="logout.php"
                         class="flex px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                         <svg class="me-2" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="matrix(-1, 0, 0, 1, 0, 0)">
                             <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                             <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                             <g id="SVGRepo_iconCarrier">
                                 <path d="M15 12L2 12M2 12L5.5 9M2 12L5.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                 <path d="M9.00195 7C9.01406 4.82497 9.11051 3.64706 9.87889 2.87868C10.7576 2 12.1718 2 15.0002 2L16.0002 2C18.8286 2 20.2429 2 21.1215 2.87868C22.0002 3.75736 22.0002 5.17157 22.0002 8L22.0002 16C22.0002 18.8284 22.0002 20.2426 21.1215 21.1213C20.3531 21.8897 19.1752 21.9862 17 21.9983M9.00195 17C9.01406 19.175 9.11051 20.3529 9.87889 21.1213C10.5202 21.7626 11.4467 21.9359 13 21.9827" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                             </g>
                         </svg>Logout</a>
                 </div>
             </div>
         </div>
     </div>
 </header>
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Manage Supervisors</h2>
                    <button class="open-modal bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium"
                        data-modal="add-supervisor-modal">
                        Add Supervisor
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">All Supervisors</h2>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="supervisorsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Assigned Tech</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                        </table>
                    </div>
                </div>
            </main>
             <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 px-6">
     <div class="flex flex-col md:flex-row items-center justify-between">
         <p class="text-sm text-gray-500 dark:text-gray-400">Developed with <span style="color: red;">&#10084;</span> by <a href="https://logicblaze.co/" style="text-decoration:none;"><strong>LogicBlaze Technologies</strong></a></p>
         <div class="flex space-x-6 mt-4 md:mt-0 ">
             <a href="https://dawoodtechnextgen.org/terms-and-conditions/" target="_blank"
                 class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Terms</a>
             <a href="https://dawoodtechnextgen.org/privacy-policy/" target="_blank"
                 class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Privacy</a>
             <a href="https://dawoodtechnextgen.org/contact/" target="_blank"
                 class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Contact</a>
         </div>
     </div>
 </footer>        </div>
    </div>

    <!-- Add & Edit Modal -->
    <div id="supervisor-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold dark:text-gray-50 text-gray-950" id="modal-title">Add Supervisor</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="supervisor-form">
                <input type="hidden" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Full Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">
                        Password
                        <!-- <span class="text-xs text-gray-500">(Leave blank to keep current)</span> -->
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password-input"
                            class="w-full px-3 py-2 pr-12 border rounded-lg bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-gray-100"
                            placeholder="Enter password">

                        <!-- Show/Hide Toggle Button -->
                        <button
                            type="button"
                            id="toggle-password"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg id="eye-open" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.2">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path d="M4 12C4 12 5.6 7 12 7M12 7C18.4 7 20 12 20 12M12 7V4M18 5L16 7.5M6 5L8 7.5M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                                </g>
                            </svg>
                            <svg id="eye-closed" class="hidden" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 10C4 10 5.6 15 12 15M12 15C18.4 15 20 10 20 10M12 15V18M18 17L16 14.5M6 17L8 14.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Assigned Technology</label>
                    <select name="tech_id" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="">None</option>
                        <option value='4'>Ai / Machine Learning</option><option value='5'>Graphic Design</option><option value='3'>Mern Stack</option><option value='6'>Php Laravel</option><option value='2'>Web Development</option>                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const role = "1";
</script>

<script src="./assets/js/tailwind.js"></script>
<script src="./assets/js/script.js"></script>
<script src="./assets/js/searchable.js"></script>
    <script>
        const table = $('#supervisorsTable').DataTable({
            ordering: false,
            pageLength: 10,
            columnDefs: [{
                    targets: 4,
                    orderable: true
                } // disable sorting on Actions
            ]
        });

        async function loadSupervisors() {
            try {
                const res = await fetch('controller/user.php?action=get_supervisors');
                const json = await res.json();
                if (json.success) {
                    table.clear();
                    json.data.forEach(u => {
                        table.row.add([
                            u.id,
                            u.name,
                            u.email || '<em class="text-gray-400">No email</em>',
                            u.tech_name ? `<span class="text-indigo-600 font-medium">${u.tech_name}</span>` : '<em class="text-gray-400">Not assigned</em>',
                            `<button class="edit-supervisor text-blue-600 mr-3" 
                                    data-id="${u.id}" 
                                    data-name="${u.name}" 
                                    data-email="${u.email || ''}"
                                    data-tech="${u.tech_id || ''}"
                                    data-pass="${u.plain_password}">
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12V10.5M13.5 2H12C7.28595 2 4.92893 2 3.46447 3.46447C2.49073 4.43821 2.16444 5.80655 2.0551 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M16.652 3.45506L17.3009 2.80624C18.3759 1.73125 20.1188 1.73125 21.1938 2.80624C22.2687 3.88124 22.2687 5.62415 21.1938 6.69914L20.5449 7.34795M16.652 3.45506C16.652 3.45506 16.7331 4.83379 17.9497 6.05032C19.1662 7.26685 20.5449 7.34795 20.5449 7.34795M16.652 3.45506L10.6872 9.41993C10.2832 9.82394 10.0812 10.0259 9.90743 10.2487C9.70249 10.5114 9.52679 10.7957 9.38344 11.0965C9.26191 11.3515 9.17157 11.6225 8.99089 12.1646L8.41242 13.9M20.5449 7.34795L17.5625 10.3304M14.5801 13.3128C14.1761 13.7168 13.9741 13.9188 13.7513 14.0926C13.4886 14.2975 13.2043 14.4732 12.9035 14.6166C12.6485 14.7381 12.3775 14.8284 11.8354 15.0091L10.1 15.5876M10.1 15.5876L8.97709 15.9619C8.71035 16.0508 8.41626 15.9814 8.21744 15.7826C8.01862 15.5837 7.9492 15.2897 8.03811 15.0229L8.41242 13.9M10.1 15.5876L8.41242 13.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>
                            <button class="delete-supervisor text-red-600" data-id="${u.id}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path> <path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5M18.8334 8.5L18.6334 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>`
                        ]);
                    });
                    table.draw(false); // false = keep current page
                }
            } catch (err) {
                console.error("Failed to load supervisors:", err);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Open Add Modal
            document.querySelector('.open-modal').onclick = () => {
                document.getElementById('modal-title').textContent = 'Add Supervisor';
                document.getElementById('supervisor-form').reset();
                document.querySelector('[name="id"]').value = '';
                document.querySelector('[name="password"]').required = true;
                document.querySelector('[name="email"]').required = true;
                document.getElementById('supervisor-modal').classList.remove('hidden');
            };

            // Edit Supervisor
            document.addEventListener('click', e => {
                const editBtn = e.target.closest('.edit-supervisor');
                if (editBtn) {
                    document.getElementById('modal-title').textContent = 'Edit Supervisor';
                    document.querySelector('[name="id"]').value = editBtn.dataset.id;
                    document.querySelector('[name="name"]').value = editBtn.dataset.name;
                    document.querySelector('[name="email"]').value = editBtn.dataset.email;
                    document.querySelector('[name="tech_id"]').value = editBtn.dataset.tech || '';
                    document.querySelector('[name="password"]').required = false;
                    document.querySelector('[name="password"]').value = editBtn.dataset.pass;
                    document.querySelector('[name="email"]').required = true;
                    document.getElementById('supervisor-modal').classList.remove('hidden');
                }
            });

            // Submit Form (Add or Update)
            document.getElementById('supervisor-form').onsubmit = async e => {
                e.preventDefault();
                const fd = new FormData(e.target);
                fd.append('role', '3');
                fd.append('action', fd.get('id') ? 'update' : 'create');

                const res = await fetch('controller/user.php', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);

                if (json.success) {
                    document.querySelector('.close-modal').click();
                    loadSupervisors(); // Auto-refresh table
                }
            };

            // Delete Supervisor
            document.addEventListener('click', async e => {
                const delBtn = e.target.closest('.delete-supervisor');
                if (delBtn && confirm('Delete this supervisor permanently?')) {
                    const res = await fetch('controller/user.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'delete',
                            id: delBtn.dataset.id
                        })
                    });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    if (json.success) loadSupervisors();
                }
            });

            // Close Modal
            document.querySelectorAll('.close-modal').forEach(b => {
                b.onclick = () => b.closest('.modal').classList.add('hidden');
            });

            // Initial load
            loadSupervisors();
        });

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white font-medium shadow-lg animate-slide-in ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            }`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
        // Password Toggle Functionality
        document.getElementById('toggle-password')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password-input');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        });
    </script>
</body>

</html>