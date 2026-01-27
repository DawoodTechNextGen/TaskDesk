<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header('location:' . BASE_URL . 'login.php');
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'My Attendance - TaskDesk';
include_once "./include/headerLinks.php";
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php" ?>
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">My Attendance</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Review your work hours grouped by task.</p>
                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <span id="weekInfo">Loading week info...</span>
                            </div>
                        </div>
                        <div id="overallStats" class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center space-x-6">
                            <div class="text-center">
                                <div id="attendancePercent" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">0%</div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider">Overall Attendance</div>
                            </div>
                        </div>
                    </div>

                    <!-- Task-Based Attendance -->
                    <div id="tasksContainer" class="space-y-6">
                        <!-- Tasks will be loaded here -->
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>
    <?php include_once "./include/footerLinks.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load overall stats
            fetch('controller/dashboard.php?action=intern_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                    if (data.success) {
                        document.getElementById('attendancePercent').textContent = data.attendance_percentage + '%';
                        
                        // Update Week Info
                        const weekInfo = document.getElementById('weekInfo');
                        if (data.current_week && data.total_weeks) {
                            weekInfo.textContent = `Week ${data.current_week} of ${data.total_weeks}`;
                        } else {
                             weekInfo.textContent = 'Week info unavailable';
                        }
                    }
                    }
                });

            // Load daily attendance history
            const attendanceContainer = document.getElementById('tasksContainer');
            attendanceContainer.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div></div>';
            
            fetch('controller/dashboard.php?action=intern_daily_history')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        let html = `
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Work Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tasks Worked On</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        `;
                        
                        data.history.forEach(day => {
                            const dateObj = new Date(day.date);
                            const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                            
                            let statusColor = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
                            let statusText = day.status;
                            
                            if (day.status === 'Present') {
                                statusColor = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                            } else if (day.is_weekend) {
                                statusColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                statusText = 'Weekend'; 
                            }
                            
                            // If user worked on weekend, keep it Present
                            if (day.status === 'Present') {
                                statusText = 'Present';
                                statusColor = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                            }

                            let taskList = '';
                            if (day.tasks && day.tasks.length > 0) {
                                taskList = day.tasks.map(t => 
                                    `<div class="text-sm"><span class="font-medium">${t.name}:</span> <span class="text-gray-500">${t.duration}</span></div>`
                                ).join('');
                            } else {
                                taskList = '<span class="text-xs text-gray-400">-</span>';
                            }

                            html += `
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        <div class="font-medium">${day.date}</div>
                                        <div class="text-xs text-gray-500">${dayName}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColor}">
                                            ${statusText}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        <div class="font-bold">${day.work_time}</div>
                                        <div class="w-24 bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 mt-1">
                                            <div class="h-1.5 rounded-full ${day.status === 'Present' ? 'bg-green-500' : 'bg-red-400'}" style="width: ${day.progress_percent}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        ${taskList}
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                        
                        attendanceContainer.innerHTML = html;
                    } else {
                         attendanceContainer.innerHTML = `
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-8 text-center border border-gray-100 dark:border-gray-700">
                                <p class="text-gray-500 dark:text-gray-400">No attendance records found.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching attendance:', error);
                    attendanceContainer.innerHTML = `
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center border border-red-200 dark:border-red-800">
                            <p class="text-red-600 dark:text-red-400">Error loading attendance data. Please try again.</p>
                        </div>
                    `;
                });
        });
    </script>
</body>
</html>
