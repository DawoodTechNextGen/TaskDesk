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
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
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

                <div class="mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Intern Attendance</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track and monitor your managed interns' attendance performance.</p>
                    </div>

                    <!-- Attendance Table Area -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Attendance Overview</h2>
                        </div>
                        <div class="overflow-x-auto p-4">
                            <table id="attendanceTable" class="min-w-full">
                                <thead class="bg-indigo-200 dark:bg-indigo-500">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Intern</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Technology</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Total Records</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Days Present</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Percentage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Performance</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white text-black dark:bg-gray-800 dark:text-white text-xs">
                                </tbody>
                            </table>
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
                            row.className = "hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors";
                            
                            const percentage = intern.attendance_percentage;
                            let colorClass = 'text-red-600';
                            if (percentage >= 90) colorClass = 'text-green-600';
                            else if (percentage >= 75) colorClass = 'text-yellow-600';
                            
                            row.innerHTML = `
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold">
                                            ${intern.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium">${intern.name}</div>
                                            <div class="text-xs text-gray-500">${intern.email}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600 text-sm">${intern.technology || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600 text-sm">${intern.total_days}</td>
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600 text-sm">${intern.present_days}</td>
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                        ${percentage}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: ${percentage}%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap border border-gray-300 dark:border-gray-600 text-sm">
                                    <a href="attendance_intern.php?id=${intern.id}" class="text-indigo-600 hover:text-indigo-900 font-bold transition-colors">View Details</a>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                        
                        setTimeout(() => {
                            $('#attendanceTable').DataTable({
                                responsive: true,
                                pageLength: 10,
                                ordering: true,
                                "dom": '<"flex flex-col md:flex-row md:items-center justify-between px-6 py-4 gap-4"f<"flex items-center gap-2 text-xs font-bold text-gray-400 font-bold"l>><"overflow-x-auto"t><"flex flex-col md:flex-row items-center justify-between px-6 py-8 gap-4"ip>',
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
