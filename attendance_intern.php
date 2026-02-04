<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], [1, 2, 3, 4])) {
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
            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-12 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-7xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">My Attendance</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Detailed overview of your work hours and internship progress.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                                </span>
                                <span id="weekInfo" class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Loading week...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Progress Card -->
                        <div class="relative overflow-hidden group bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>
                            <div class="flex flex-col h-full justify-between">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl text-indigo-600 dark:text-indigo-400">
                                        <i class="fas fa-tasks text-xl"></i>
                                    </div>
                                    <div id="attendancePercent" class="text-2xl font-black text-indigo-600 dark:text-indigo-400">0%</div>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Attendance Rate</p>
                                    <div class="mt-4 space-y-2">
                                        <div class="flex justify-between text-xs font-bold text-gray-500">
                                            <span>Internship Progress</span>
                                            <span id="progressText">0/0 Days</span>
                                        </div>
                                        <div class="h-5 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden border-2 border-gray-300 dark:border-gray-600 shadow-inner">
                                            <div id="progressBar" class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-1000 ease-out" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Present Card -->
                        <div class="relative overflow-hidden group bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-colors"></div>
                            <div class="flex flex-col h-full justify-between">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl text-emerald-600 dark:text-emerald-400">
                                        <i class="fas fa-check-double text-xl"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Present Days</p>
                                    <p id="totalPresent" class="text-4xl font-black text-gray-900 dark:text-white mt-1">0</p>
                                </div>
                            </div>
                        </div>

                        <!-- Absent Card -->
                        <div class="relative overflow-hidden group bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-rose-500/5 rounded-full blur-3xl group-hover:bg-rose-500/10 transition-colors"></div>
                            <div class="flex flex-col h-full justify-between">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="p-3 bg-rose-50 dark:bg-rose-900/30 rounded-2xl text-rose-600 dark:text-rose-400">
                                        <i class="fas fa-user-times text-xl"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Absent Days</p>
                                    <p id="totalAbsent" class="text-4xl font-black text-gray-900 dark:text-white mt-1">0</p>
                                </div>
                            </div>
                        </div>

                        <!-- Holidays Card -->
                        <div class="relative overflow-hidden group bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-colors"></div>
                            <div class="flex flex-col h-full justify-between">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-2xl text-amber-600 dark:text-amber-400">
                                        <i class="fas fa-coffee text-xl"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Weekend/Holidays</p>
                                    <p id="totalHolidays" class="text-4xl font-black text-gray-900 dark:text-white mt-1">0</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Table Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 md:p-8">
                            <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Daily History</h2>
                            </div>
                            
                            <div id="tasksContainer" class="min-h-[400px]">
                                <!-- Table will be injected here -->
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
            // Load overall stats
            const urlParamsForStats = new URLSearchParams(window.location.search);
            const targetUserIdForStats = urlParamsForStats.get('id');
            const statsUrl = targetUserIdForStats ? `controller/dashboard.php?action=intern_stats&target_userid=${targetUserIdForStats}` : 'controller/dashboard.php?action=intern_stats';

            fetch(statsUrl)
                .then(response => response.json())
                .then(data => {
                    console.log('Attendance Stats Data:', data);
                    if (data.success) {
                        document.getElementById('attendancePercent').textContent = data.attendance_percentage + '%';
                        
                        // Update Progress Text (Present Days / Total Working Days Passed)
                        const progressText = document.getElementById('progressText');
                        if (progressText) {
                            progressText.textContent = `${data.present_days}/${data.working_days_passed} Days`;
                        }
                        
                        // Update Progress Bar to match Attendance Percentage
                        const progressBar = document.getElementById('progressBar');
                        if (progressBar) {
                            const percent = parseFloat(data.attendance_percentage) || 0;
                            progressBar.style.width = percent + '%';
                            console.log('Progress Bar Updated:', percent + '%');
                        }
                        
                        // Update Week Info
                        const weekInfo = document.getElementById('weekInfo');
                        if (data.current_week && data.total_weeks) {
                            weekInfo.textContent = `Week ${data.current_week} of ${data.total_weeks}`;
                        } else {
                             weekInfo.textContent = 'Week info unavailable';
                        }
                    }
                });

            // Load daily attendance history
            const attendanceContainer = document.getElementById('tasksContainer');
            attendanceContainer.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div></div>';
            
            const urlParams = new URLSearchParams(window.location.search);
            const targetUserId = urlParams.get('id');
            const fetchUrl = targetUserId ? `controller/dashboard.php?action=intern_daily_history&target_userid=${targetUserId}` : 'controller/dashboard.php?action=intern_daily_history';
            
            fetch(fetchUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        // Calculate totals
                        let totalPresent = 0;
                        let totalAbsent = 0;
                        let totalHolidays = 0;
                        
                        data.history.forEach(day => {
                            if (day.is_weekend) {
                                totalHolidays++;
                            } else if (day.status === 'Present') {
                                totalPresent++;
                            } else {
                                totalAbsent++;
                            }
                        });
                        
                        // Update total stats
                        document.getElementById('totalPresent').textContent = totalPresent;
                        document.getElementById('totalAbsent').textContent = totalAbsent;
                        document.getElementById('totalHolidays').textContent = totalHolidays;
                        
                        let html = `
                            <div class="p-1 md:p-4 border border-gray-100 dark:border-gray-700/50 rounded-[2.5rem] bg-white/50 dark:bg-gray-800/50 backdrop-blur-xl shadow-sm">
                                <table id="attendanceTable" class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 dark:bg-gray-900/20">
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Date</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Status</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Work Duration</th>
                                            <th class="px-6 py-5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Activity Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        `;
                        
                        data.history.forEach(day => {
                            const dateObj = new Date(day.date);
                            const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                            const dayShort = dateObj.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                            
                            let statusConfig = {
                                color: 'from-rose-500/10 to-orange-500/10 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/30',
                                icon: 'fa-user-times',
                                text: day.status
                            };
                            
                            if (day.status === 'Present') {
                                statusConfig = {
                                    color: 'from-emerald-500/10 to-teal-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30',
                                    icon: 'fa-check-circle',
                                    text: 'Present'
                                };
                            } else if (day.is_weekend) {
                                statusConfig = {
                                    color: 'from-gray-500/10 to-slate-500/10 text-gray-600 dark:text-gray-400 border-gray-100 dark:border-gray-700/50',
                                    icon: 'fa-mug-hot',
                                    text: 'Holiday'
                                };
                            }
                            
                            // Ensure Present text if work duration exists
                            if (day.status === 'Present') {
                                statusConfig.text = 'Present';
                            }

                            let taskList = '';
                            if (day.tasks && day.tasks.length > 0) {
                                taskList = day.tasks.map(t => `
                                    <div class="group/task flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all duration-200">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-400"></div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200" title="${t.name}">${t.name.length > 23 ? t.name.substring(0, 23) + '...' : t.name}</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">${t.duration}</span>
                                    </div>
                                `).join('');
                                taskList = `<div class="space-y-1 max-w-xs">${taskList}</div>`;
                            } else {
                                taskList = '<span class="text-xs font-medium text-gray-400 dark:text-gray-600 italic px-2">No activity logged</span>';
                            }

                            html += `
                                <tr class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-all duration-300">
                                    <td class="px-6 py-6 whitespace-nowrap" data-order="${day.date}">
                                        <div class="flex flex-col">
                                            <span class="text-base font-bold text-gray-900 dark:text-white">${dayShort}</span>
                                            <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">${dayName}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 bg-gradient-to-br ${statusConfig.color}">
                                            <i class="fas ${statusConfig.icon} text-[10px]"></i>
                                            ${statusConfig.text}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-end gap-1.5">
                                                <span class="text-lg font-black text-gray-900 dark:text-white leading-none">${day.work_time.split(':')[0]}</span>
                                                <span class="text-xs font-bold text-gray-400 dark:text-gray-500 pb-0.5">hours</span>
                                                <span class="text-lg font-black text-gray-900 dark:text-white leading-none ml-1">${day.work_time.split(':')[1]}</span>
                                                <span class="text-xs font-bold text-gray-400 dark:text-gray-500 pb-0.5">mins</span>
                                            </div>
                                            <div class="w-32 bg-gray-100 dark:bg-gray-700/50 rounded-full h-2.5 overflow-hidden border border-gray-100 dark:border-gray-800">
                                                <div class="h-full bg-blue-600 dark:bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-1000" 
                                                    style="width: ${day.status === 'Present' ? '100' : (parseFloat(day.progress_percent) > 0 ? Math.max(parseFloat(day.progress_percent), 15) : 0)}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6">
                                        ${taskList}
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                        </tbody>
                                    </table>
                                </div>
                        `;
                        
                        attendanceContainer.innerHTML = html;
                        
                        // Initialize DataTables with Modern Styling
                        setTimeout(() => {
                            const table = $('#attendanceTable').DataTable({
                                "pageLength": 10,
                                "order": [[0, "desc"]],
                                "language": {
                                    "search": "_INPUT_",
                                    "searchPlaceholder": "Filter attendance...",
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
                        }, 100);
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
