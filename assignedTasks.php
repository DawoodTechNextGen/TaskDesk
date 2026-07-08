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
    <script src="./assets/js/tasks-management.js?v=<?= time() ?>"></script>
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

            // Refresh every minute
            setInterval(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const currentStatus = urlParams.get('status') || null;
                getAssignedTasks(currentStatus, false);
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
                    const statusOrder = { "inprogress": 1, "needs_improvement": 2, "pending_review": 3, "complete": 4, "expired": 5 };
                    result.data.sort((a, b) => (statusOrder[a.status] || 99) - (statusOrder[b.status] || 99));

                    dataTable.clear();
                    result.data.forEach(task => {
                        const isExpired = (task.due_date && task.due_date < new Date().toISOString().split('T')[0] && ['inprogress', 'needs_improvement'].includes(task.status));
                        const displayStatus = isExpired ? 'expired' : task.status;

                        let titleHtml = `<div class="font-semibold text-gray-900 dark:text-white">${task.title}</div>`;

                        let statusHtml = `<div class="flex items-center gap-1.5 flex-wrap">
                            ${getStatusBadge(displayStatus)}`;
                        if (task.status === 'needs_improvement' && task.review_notes) {
                            const escapedNotes = task.review_notes.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                            statusHtml += `
                            <button onclick="toggleRequirements(${task.id}, this)" data-notes="${escapedNotes}" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all cursor-pointer shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1 transform hover:scale-105 active:scale-95" title="What's Required?" id="req-btn-${task.id}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>`;
                        }
                        statusHtml += `</div>`;

                        dataTable.row.add([
                            task.id,
                            titleHtml,
                            task.assign_by_name || task.assign_by || 'System',
                            statusHtml,
                            formatDateTime(task.created_at),
                            `<div class="flex gap-2 items-center flex-wrap md:flex-nowrap">
                                <button onclick="viewTaskDetails(${task.id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-all cursor-pointer shadow-sm border border-blue-200 dark:border-blue-800 focus:outline-none" title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>

                                ${displayStatus === 'expired' ? `
                                    <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded text-[10px] font-bold uppercase border border-red-200 dark:border-red-800">Expired</span>
                                ` : ''}

                                ${['inprogress', 'needs_improvement'].includes(task.status) && !isExpired ? `
                                    <button data-action="open-submit" data-id="${task.id}" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 text-white rounded text-xs font-bold transition-all shadow-sm">Complete</button>
                                ` : ''}

                                ${task.status === 'pending_review' ? `
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded text-[10px] font-bold uppercase border border-indigo-200 dark:border-indigo-800">Under Review</span>
                                ` : ''}

                                ${task.status === 'complete' || task.status === 'approved' ? `
                                    <span class="px-2 py-1 bg-stone-100 text-stone-700 dark:bg-stone-900/30 dark:text-stone-400 rounded text-[10px] font-bold uppercase border border-stone-200 dark:border-stone-800">Completed</span>
                                ` : ''}

                                ${task.status === 'rejected' ? `
                                    <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded text-[10px] font-bold uppercase border border-red-200 dark:border-red-800">Rejected</span>
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

                if (action === 'open-submit') openSubmissionModal(taskId);
            });

            // Submission Modal Actions
            document.querySelectorAll('.close-submission-modal').forEach(btn => {
                btn.addEventListener('click', () => document.getElementById('submission-modal').classList.add('hidden'));
            });

            document.getElementById('submit-task-btn').addEventListener('click', handleSubmitTask);
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

        function getFormattedNow() {
            const now = new Date();
            return now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, '0') + "-" + String(now.getDate()).padStart(2, '0') + " " +
                   String(now.getHours()).padStart(2, '0') + ":" + String(now.getMinutes()).padStart(2, '0') + ":" + String(now.getSeconds()).padStart(2, '0');
        }

        function toggleRequirements(taskId, btn) {
            const tr = btn.closest('tr');
            const row = dataTable.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.classList.remove('shown');
                btn.classList.remove('ring-2', 'ring-amber-500', 'ring-offset-2');
            } else {
                const notes = btn.getAttribute('data-notes');
                row.child(`
                    <div class="p-4 bg-amber-50/80 dark:bg-amber-950/20 border-l-4 border-amber-500 rounded-r-xl text-gray-800 dark:text-gray-200 text-xs shadow-inner transition-all duration-300">
                        <div class="flex justify-between items-center mb-2 pb-1.5 border-b border-amber-200/50 dark:border-amber-900/40">
                            <span class="font-bold text-amber-800 dark:text-amber-400 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                Supervisor's Improvement Instructions:
                            </span>
                        </div>
                        <div class="whitespace-pre-line leading-relaxed font-semibold">${notes}</div>
                    </div>
                `).show();
                tr.classList.add('shown');
                btn.classList.add('ring-2', 'ring-amber-500', 'ring-offset-2');
            }
        }
    </script>
</body>

</html>
