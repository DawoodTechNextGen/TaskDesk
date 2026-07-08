<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], [1, 3, 4])) {
    header('location:' . BASE_URL . 'login.php');
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Intern Attendance - TaskDesk';
include_once "./include/headerLinks.php";
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php" ?>
            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-12 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <!-- Loader -->
                <div id="loader-container" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/10 backdrop-blur-[2px] transition-all duration-300">
                    <div class="flex flex-col items-center gap-4 p-8 rounded-3xl bg-white/80 dark:bg-gray-800/80 shadow-2xl border border-white dark:border-gray-700">
                        <div class="relative w-16 h-16">
                            <div class="absolute inset-0 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
                        </div>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-300 animate-pulse uppercase tracking-widest">Fetching Attendance...</p>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Intern Attendance</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Track and monitor your managed interns' attendance performance.</p>
                        </div>
                    </div>

                    <!-- Attendance Table Area -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 md:p-8">
                            <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Attendance Overview</h2>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table id="attendanceTable" class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 dark:bg-gray-900/20">
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Intern</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Technology</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Total Records</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Days Present</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Percentage</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Performance</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>
    <?php include_once "./include/footerLinks.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#attendanceTable tbody');
            const loader = document.getElementById('loader-container');

            fetch('controller/dashboard.php?action=supervisor_intern_attendance')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.interns.forEach(intern => {
                            const row = document.createElement('tr');
                            row.className = "hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors";
                            
                            const percentage = parseFloat(intern.attendance_percentage) || 0;
                            
                            row.innerHTML = `
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-9 w-9 bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 rounded-full flex items-center justify-center font-bold text-sm shadow-inner">
                                            ${intern.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">${intern.name}</div>
                                            <div class="text-xs text-gray-400 dark:text-gray-500">${intern.email}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm font-semibold text-gray-700 dark:text-gray-300">${intern.technology || 'N/A'}</td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm font-semibold text-gray-500 dark:text-gray-400">${intern.total_days}</td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm font-semibold text-gray-500 dark:text-gray-400">${intern.present_days}</td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <span class="px-2.5 py-1.5 inline-flex text-xs leading-5 font-bold rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                        ${percentage}%
                                    </span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="w-32 bg-gray-100 dark:bg-gray-700/50 rounded-full h-2.5 overflow-hidden border border-gray-100 dark:border-gray-800">
                                        <div class="bg-indigo-600 dark:bg-indigo-500 h-full rounded-full transition-all duration-500 shadow-[0_0_15px_rgba(79,70,229,0.4)]" style="width: ${percentage}%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm">
                                    <a href="attendance_intern.php?id=${intern.id}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-400 dark:hover:bg-indigo-900/50 transition-all cursor-pointer shadow-sm border border-indigo-100 dark:border-indigo-800">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <span>View Details</span>
                                    </a>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                        
                        setTimeout(() => {
                            $('#attendanceTable').DataTable({
                                responsive: true,
                                pageLength: 10,
                                ordering: true,
                                "language": {
                                    "search": "_INPUT_",
                                    "searchPlaceholder": "Filter interns...",
                                    "paginate": {
                                        "previous": '<i class="fas fa-chevron-left text-xs"></i>',
                                        "next": '<i class="fas fa-chevron-right text-xs"></i>'
                                    }
                                },
                                "dom": '<"flex flex-col md:flex-row md:items-center justify-between px-6 py-4 gap-4"f<"flex items-center gap-2 text-xs font-bold text-gray-400 font-bold"l>><"overflow-x-auto"t><"flex flex-col md:flex-row items-center justify-between px-6 py-8 gap-4"ip>',
                                "drawCallback": function() {
                                    // Style pagination
                                    $('.dataTables_paginate').addClass('flex items-center gap-2');
                                    $('.paginate_button').addClass('flex items-center justify-center w-8 h-8 rounded-xl border border-gray-100 dark:border-gray-700 text-sm font-bold transition-all duration-200 cursor-pointer');
                                    $('.paginate_button.current').addClass('bg-indigo-600 text-white border-indigo-600').removeClass('border-gray-100 dark:border-gray-700');
                                    $('.dataTables_info').addClass('text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest');
                                    $('.dataTables_length select').addClass('bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold p-2 focus:ring-0 cursor-pointer shadow-sm');
                                    $('.dataTables_filter input').addClass('bg-gray-50 dark:bg-gray-800 border-none rounded-[1.25rem] text-sm font-medium px-6 py-3 w-64 focus:ring-2 focus:ring-indigo-500/20 transition-all shadow-sm');
                                }
                            });
                            
                            // Hide loader with a slight fade
                            if(loader) {
                                loader.classList.add('opacity-0', 'pointer-events-none');
                                setTimeout(() => loader.style.display = 'none', 300);
                            }
                        }, 100);
                    } else {
                        if(loader) loader.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching attendance:', error);
                    if(loader) loader.style.display = 'none';
                });
        });
    </script>
</body>
</html>
