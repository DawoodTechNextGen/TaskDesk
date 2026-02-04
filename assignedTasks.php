<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login-signup.php');
} else {
    include_once './include/connection.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<!-- headerLinks -->
<?php
$page_title = 'Assigned Tasks - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-100 space-y-4">
        <!-- Toast templates will be inserted here dynamically -->
    </div>
    <div class="flex h-screen overflow-hidden">
        <!-- Modern Sidebar -->
        <?php include_once "./include/sideBar.php"; ?>
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include_once "./include/header.php" ?>
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div
                    class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden transition-all duration-300 border border-gray-100 dark:border-gray-700">
                    <div
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Tasks Assigned to you</h2>
                    </div>
                    <div class="overflow-x-auto p-4 relative min-h-[300px]">
                        <!-- Table Loader -->
                        <div id="assigned-table-loader" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 backdrop-blur-[1px] z-10 flex items-center justify-center transition-opacity duration-300">
                            <div class="flex flex-col items-center">
                                <svg class="w-10 h-10 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-400">Loading your tasks...</span>
                            </div>
                        </div>
                        <table id="assignedTasksTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-200 dark:bg-indigo-500 text-white ">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Sr#</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Title</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Assign By</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Assign On</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white text-black dark:bg-gray-800 dark:text-white divide-y divide-gray-200 dark:divide-gray-700 text-xs" id="tasks">
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/taskModals.php"; ?>
            <!-- Footer -->
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>
    <?php include_once "./include/footerLinks.php"; ?>
    <script src="./assets/js/tasks-management.js"></script>
    <script>
        let dataTable;
        const tech = "<?php echo $_SESSION['tech'] ?? '' ?>";

        document.addEventListener('DOMContentLoaded', async function() {
            const urlParams = new URLSearchParams(window.location.search);
            const initialStatus = urlParams.get('status') || null;

            dataTable = $('#assignedTasksTable').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true,
                order: [[0, 'desc']]
            });

            await getAssignedTasks(initialStatus);
            setupEventListeners();
            setupAutoStopTimer();
            setupAutoAttendance();
            updateStartButtonAvailability();

            // Refresh every minute
            setInterval(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const currentStatus = urlParams.get('status') || null;
                getAssignedTasks(currentStatus, false);
                updateStartButtonAvailability();
            }, 60000);
        });

        async function getAssignedTasks(status = null, showLoader = true) {
            const loader = document.getElementById('assigned-table-loader');
            if (showLoader && loader) {
                loader.classList.remove('hidden', 'opacity-0');
            }

            try {
                const response = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'getAssignedTask', status: status })
                });

                const result = await response.json();
                if (result.success) {
                    const statusOrder = { "working": 1, "pending": 2, "pending_review": 3, "complete": 4, "expired": 5 };
                    result.data.sort((a, b) => (statusOrder[a.status] || 99) - (statusOrder[b.status] || 99));

                    dataTable.clear();
                    result.data.forEach(task => {
                        const isExpired = (task.due_date && task.due_date < new Date().toISOString().split('T')[0] && task.status !== 'complete' && task.status !== 'approved');
                        const displayStatus = isExpired ? 'expired' : task.status;

                        dataTable.row.add([
                            task.id,
                            task.title,
                            task.assign_by_name || task.assign_by || 'System',
                            getStatusBadge(displayStatus),
                            formatDateTime(task.created_at),
                            `<div class="flex gap-2 items-center">
                                <button onclick="viewTaskDetails(${task.id})" class="text-blue-600 hover:text-blue-800 transition-colors" title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>

                                ${displayStatus === 'expired' ? `
                                    <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded text-[10px] font-bold uppercase border border-red-200 dark:border-red-800">Expired</span>
                                ` : ''}

                                ${task.status === 'pending' && !isExpired ? `
                                    <button data-action="start" data-id="${task.id}" class="bg-emerald-600 hover:bg-emerald-700 px-3 py-1 text-white rounded text-xs font-bold transition-all shadow-sm">Start</button>
                                ` : ''}

                                ${task.status === 'working' ? `
                                    <button data-action="pause" data-id="${task.id}" class="bg-amber-600 hover:bg-amber-700 px-3 py-1 text-white rounded text-xs font-bold transition-all shadow-sm">Pause</button>
                                    <button data-action="open-submit" data-id="${task.id}" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 text-white rounded text-xs font-bold transition-all shadow-sm">Complete</button>
                                ` : ''}

                                ${task.status === 'pending_review' ? `
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded text-[10px] font-bold uppercase border border-indigo-200 dark:border-indigo-800">Under Review</span>
                                ` : ''}

                                ${task.status === 'complete' || task.status === 'approved' ? `
                                    <span class="px-2 py-1 bg-stone-100 text-stone-700 dark:bg-stone-900/30 dark:text-stone-400 rounded text-[10px] font-bold uppercase border border-stone-200 dark:border-stone-800">Completed</span>
                                ` : ''}
                            </div>`
                        ]);
                    });
                    dataTable.draw();
                }
            } catch (error) {
                console.error('Error loading tasks:', error);
            } finally {
                if (loader) {
                    loader.classList.add('opacity-0');
                    setTimeout(() => loader.classList.add('hidden'), 300);
                }
            }
        }

        function setupEventListeners() {
            const table = document.getElementById("assignedTasksTable");
            
            table.addEventListener("click", async function(e) {
                const button = e.target.closest("button[data-action]");
                if (!button) return;

                const action = button.dataset.action;
                const taskId = button.dataset.id;

                if (action === 'start') handleStart(taskId);
                else if (action === 'pause') handleStop(taskId);
                else if (action === 'open-submit') openSubmissionModal(taskId);
            });

            // Submission Modal Actions
            document.querySelectorAll('.close-submission-modal').forEach(btn => {
                btn.addEventListener('click', () => document.getElementById('submission-modal').classList.add('hidden'));
            });

            document.getElementById('submit-task-btn').addEventListener('click', handleSubmitTask);
        }

        async function handleStart(id) {
            if (!await checkTimeRestrictions()) return;

            // Check for other active tasks
            const response = await fetch('controller/timeLog.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check_active' })
            });
            const check = await response.json();
            if (check.has_active) {
                showToast('error', 'You already have an active task timer running!');
                return;
            }

            try {
                const response = await fetch('controller/timeLog.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'start', task_id: id, started_at: getFormattedNow() })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('success', 'Task started successfully');
                    getAssignedTasks();
                } else showToast('error', result.message);
            } catch (e) { showToast('error', 'Failed to start task'); }
        }

        async function handleStop(id) {
            try {
                const response = await fetch('controller/timeLog.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'stop', task_id: id, stoped_at: getFormattedNow() })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('success', 'Task paused successfully');
                    getAssignedTasks();
                } else showToast('error', result.message);
            } catch (e) { showToast('error', 'Failed to pause task'); }
        }

        function openSubmissionModal(id) {
            document.getElementById('submission-task-id').value = id;
            document.getElementById('submission-form').reset();
            document.getElementById('github-error').classList.add('hidden');
            document.getElementById('live-view-error').classList.add('hidden');
            document.getElementById('submission-modal').classList.remove('hidden');
        }

        async function handleSubmitTask() {
            const taskId = document.getElementById('submission-task-id').value;
            const github = document.getElementById('github-repo').value;
            const live = document.getElementById('live-view').value;
            const notes = document.getElementById('additional-notes').value;

            const isTechRestricted = (tech === 'Ai / Machine Learning' || tech === 'Graphic Design');

            if (!isTechRestricted) {
                if (!github.includes('github.com')) {
                    document.getElementById('github-error').classList.remove('hidden');
                    return;
                }
                if (!live) {
                    document.getElementById('live-view-error').classList.remove('hidden');
                    return;
                }
            }

            try {
                const response = await fetch('controller/timeLog.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'complete',
                        task_id: taskId,
                        github_repo: github,
                        live_view: live,
                        additional_notes: notes,
                        stoped_at: getFormattedNow()
                    })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('success', 'Task submitted for review');
                    document.getElementById('submission-modal').classList.add('hidden');
                    getAssignedTasks();
                } else showToast('error', result.message);
            } catch (e) { showToast('error', 'Submission failed'); }
        }

        async function checkTimeRestrictions() {
            const now = new Date();
            const hour = now.getHours();
            const day = now.getDay();

            if (hour >= 0 && hour < 9) {
                showToast('error', 'Cannot start timer between 12:00 AM and 9:00 AM');
                return false;
            }
            if (day === 0 || day === 6) {
                showToast('error', 'Cannot work on weekends');
                return false;
            }
            return true;
        }

        function updateStartButtonAvailability() {
            const now = new Date();
            const hour = now.getHours();
            const day = now.getDay();
            const isRestricted = (hour >= 0 && hour < 9) || (day === 0 || day === 6);

            document.querySelectorAll('button[data-action="start"]').forEach(btn => {
                if (isRestricted) {
                    btn.disabled = true;
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        }

        function setupAutoStopTimer() {
            const now = new Date();
            const midnight = new Date();
            midnight.setHours(23, 59, 0, 0);
            if (midnight - now > 0) {
                setTimeout(() => {
                    const stopBtn = document.querySelector('button[data-action="pause"]');
                    if (stopBtn) stopBtn.click();
                }, midnight - now);
            }
        }

        function setupAutoAttendance() {
            const now = new Date();
            const target = new Date();
            target.setHours(23, 58, 0, 0);
            let delay = target - now;
            if (delay < 0) delay += 24 * 60 * 60 * 1000;
            setTimeout(() => {
                fetch('controller/timeLog.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_auto_attendance' })
                });
                setInterval(() => {
                    fetch('controller/timeLog.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'mark_auto_attendance' })
                    });
                }, 24 * 60 * 60 * 1000);
            }, delay);
        }

        function getFormattedNow() {
            const now = new Date();
            return now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, '0') + "-" + String(now.getDate()).padStart(2, '0') + " " +
                   String(now.getHours()).padStart(2, '0') + ":" + String(now.getMinutes()).padStart(2, '0') + ":" + String(now.getSeconds()).padStart(2, '0');
        }
    </script>
</body>

</html>