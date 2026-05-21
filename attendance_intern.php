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
<style>
    /* Backdrop fade-in / fade-out animations */
    @keyframes backdropFadeIn {
        from {
            background-color: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px);
        }
        to {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
    }

    @keyframes backdropFadeOut {
        from {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        to {
            background-color: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px);
        }
    }

    /* Modal content slide-out animation */
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(15px);
        }
    }

    .modal-backdrop-in {
        animation: backdropFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    .modal-backdrop-out {
        animation: backdropFadeOut 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    .modal-content-out {
        animation: fadeOut 0.25s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
</style>

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
                                        <div class="h-3 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden border-2 border-gray-300 dark:border-gray-600 shadow-inner">
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

    <!-- Attendance Log Modal -->
    <div id="attendance-log-modal" class="hidden fixed inset-0 z-[100] w-full h-full flex items-center justify-center p-4">
        <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-gray-700/50">
            <!-- Modal Header -->
            <div class="relative p-6 border-b border-gray-100 dark:border-gray-700/50 bg-gradient-to-r from-indigo-50/30 to-white dark:from-gray-800 dark:to-gray-800/80">
                <button type="button" class="close-modal absolute top-5 right-5 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="space-y-3 pr-6">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 id="modalDate" class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">May 20, 2026</h2>
                        <span id="modalStatusBadge" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-all duration-300">
                            <!-- Status Badge -->
                        </span>
                    </div>
                    <div id="modalDayYear" class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Wednesday</div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6 max-h-[350px] overflow-y-auto custom-scrollbar">
                <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-4">Check-in / Check-out Logs</h3>
                <div id="modalLogsContainer" class="space-y-4">
                    <!-- Logs list -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-6 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/50 flex justify-end">
                <button type="button" class="close-modal px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-bold text-sm transition-all duration-200 shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
    <?php include_once "./include/footerLinks.php"; ?>
    <script>
        let attendanceHistory = [];
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
                            progressText.textContent = `${data.present_days}/${data.total_working_days} Days`;
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
                        attendanceHistory = data.history;
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
                            const dayShort = formatDisplayDate(day.date);
                            
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
                                taskList = '<span class="text-xs font-medium text-gray-400 dark:text-gray-600 italic px-2">No activity</span>';
                            }

                            html += `
                                <tr class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 cursor-pointer transition-all duration-300" data-date="${day.date}">
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

        // Show attendance details modal on row click
        $(document).on('click', '#attendanceTable tbody tr', function(e) {
            // Avoid triggering when clicking links or buttons if any
            if (e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            const date = $(this).data('date');
            if (!date) return;
            
            const dayData = attendanceHistory.find(d => d.date === date);
            if (!dayData) return;
            
            // Set Date & Day
            const dateFormatted = formatDisplayDate(date);
            const dayName = parseDisplayDate(dayData.date).toLocaleDateString('en-US', { weekday: 'long' });
            
            document.getElementById('modalDate').textContent = dateFormatted;
            document.getElementById('modalDayYear').textContent = dayName;
            
            // Set Status Badge
            const badge = document.getElementById('modalStatusBadge');
            let badgeColor = '';
            let badgeIcon = '';
            let badgeText = dayData.status;
            
            if (dayData.status === 'Present') {
                badgeColor = 'from-emerald-500/10 to-teal-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30';
                badgeIcon = 'fa-check-circle';
                badgeText = 'Present';
            } else if (dayData.is_weekend) {
                badgeColor = 'from-gray-500/10 to-slate-500/10 text-gray-600 dark:text-gray-400 border-gray-100 dark:border-gray-700/50';
                badgeIcon = 'fa-mug-hot';
                badgeText = 'Holiday';
            } else {
                badgeColor = 'from-rose-500/10 to-orange-500/10 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/30';
                badgeIcon = 'fa-user-times';
            }
            
            badge.className = `inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 bg-gradient-to-br ${badgeColor}`;
            badge.innerHTML = `<i class="fas ${badgeIcon} text-[10px]"></i> ${badgeText}`;
            
            // Build logs HTML
            const logsContainer = document.getElementById('modalLogsContainer');
            if (dayData.logs && dayData.logs.length > 0) {
                let logsHtml = '<div class="relative pl-6 border-l-2 border-indigo-100 dark:border-indigo-900/50 space-y-6">';
                
                dayData.logs.forEach(log => {
                    const checkInFormatted = formatModalTime(log.check_in);
                    const checkOutFormatted = log.check_out ? formatModalTime(log.check_out) : '<span class="text-indigo-600 dark:text-indigo-400 font-bold animate-pulse">Active</span>';
                    const durationFormatted = log.check_out ? formatModalSecs(log.duration) : '-';
                    
                    logsHtml += `
                        <div class="relative">
                            <div class="absolute -left-[31px] top-1 bg-indigo-500 dark:bg-indigo-600 w-4 h-4 rounded-full border-4 border-white dark:border-gray-800 shadow-sm"></div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-700/30 hover:border-indigo-100 dark:hover:border-indigo-900/30 transition-colors shadow-sm">
                                <div class="flex items-center gap-4 flex-wrap">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Check In</span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">${checkInFormatted}</span>
                                    </div>
                                    <div class="text-gray-300 dark:text-gray-600">
                                        <i class="fas fa-long-arrow-alt-right text-lg"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Check Out</span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">${checkOutFormatted}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-start sm:items-end">
                                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Duration</span>
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">${durationFormatted}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                logsHtml += '</div>';
                logsContainer.innerHTML = logsHtml;
            } else {
                logsContainer.innerHTML = `
                    <div class="text-center py-10 bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                        <div class="p-3 bg-gray-100 dark:bg-gray-700/50 rounded-2xl inline-block text-gray-400 dark:text-gray-500 mb-3 shadow-inner">
                            <i class="fas fa-history text-2xl"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400">No activity sessions logged.</p>
                        <p class="text-xs text-gray-400 mt-1">This could be due to an absence or holiday.</p>
                    </div>
                `;
            }
            
            // Open the modal smoothly
            const modal = document.getElementById('attendance-log-modal');
            const modalContent = modal.querySelector('.modal-content');
            
            modal.classList.remove('hidden', 'modal-backdrop-out');
            modalContent.classList.remove('modal-content-out');
            modal.classList.add('modal-backdrop-in');
        });

        // Helper: Format datetime to standard Time string
        function formatModalTime(dateTimeStr) {
            if (!dateTimeStr) return '-';
            const dateObj = new Date(dateTimeStr);
            if (isNaN(dateObj.getTime())) return dateTimeStr; // Fallback
            return dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        }

        function formatDisplayDate(dateStr) {
            if (!dateStr) return 'N/A';
            return parseDisplayDate(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        function parseDisplayDate(dateStr) {
            const [year, month, day] = dateStr.substring(0, 10).split('-').map(Number);
            return new Date(year, month - 1, day);
        }

        // Helper: Format duration seconds to readable text
        function formatModalSecs(seconds) {
            if (seconds === null || seconds === undefined || isNaN(seconds)) return '-';
            if (seconds === 0) return '0m';
            const hrs = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            let result = '';
            if (hrs > 0) result += `${hrs}h `;
            if (mins > 0 || hrs === 0) result += `${mins}m`;
            return result.trim();
        }

        function closeModalSmoothly() {
            const modal = document.getElementById('attendance-log-modal');
            if (modal.classList.contains('hidden')) return;
            
            const modalContent = modal.querySelector('.modal-content');
            
            modal.classList.remove('modal-backdrop-in');
            modal.classList.add('modal-backdrop-out');
            modalContent.classList.add('modal-content-out');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('modal-backdrop-out');
                modalContent.classList.remove('modal-content-out');
            }, 280);
        }

        // Close modal smoothly on clicking close elements
        $(document).on('click', '#attendance-log-modal .close-modal, #attendance-log-modal .close-btn', function(e) {
            e.preventDefault();
            closeModalSmoothly();
        });
        
        // Close modal smoothly on clicking outside the modal content
        $(document).on('click', '#attendance-log-modal', function(e) {
            if (e.target === this) {
                closeModalSmoothly();
            }
        });
    </script>
</body>
</html>
