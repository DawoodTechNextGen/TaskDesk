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
$page_title = 'Assigned Tasks - DawoodTech Task Management System';
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
                    <div class="overflow-x-auto p-4">
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
            <!-- View Modal -->
            <div id="view-task-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
                <div
                    class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto my-auto p-6 rounded-lg w-11/12 max-w-6xl relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                        class="w-5 h-6 absolute top-2 right-2 cursor-pointer close-btn">
                        <path fill-rule="evenodd"
                            d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-100 dark:border-gray-600">View Task</h2>
                    <!-- <p class="mb-4 text-gray-600 dark:text-gray-300"></p> -->
                    <div class="modal-body max-h-[400px] overflow-y-auto px-2 custom-scrollbar">
                        <form id="view-task">
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-2" for="title">Title:</label>
                                <input type="text" id="title" class="w-full p-3 rounded-lg focus:outline-none border border-gray-200 bg-gray-200 dark:bg-gray-700 dark:border-gray-600"
                                    placeholder="Task title" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-2" for="title">Assign By:</label>
                                <input type="text" id="assign_by" class="w-full p-3 rounded-lg focus:outline-none border border-gray-200 bg-gray-200 dark:bg-gray-700 dark:border-gray-600"
                                    placeholder="Task title" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-2" for="description">Description:</label>
                                <textarea id="description" rows="3" class="overflow-y-auto w-full p-3 rounded-lg focus:outline-none border border-gray-200 bg-gray-200 dark:bg-gray-700 dark:border-gray-600" readonly></textarea>
                            </div>
                            <div class="grid grid-cols-1">
                                <div class="hidden border-t border-gray-200 dark:border-gray-600" id="time-logs">
                                    <div class="flex justify-between items-center px-4">
                                        <h4 class="py-1 font-extrabold text-2xl text-center">Time Logs</h4>
                                        <span id="total-time">Total Time: </span>
                                    </div>
                                    <div class="container mx-auto p-4">
                                        <div class="max-h-[200px] overflow-y-auto custom-scrollbar shadow-md rounded-lg mb-3">
                                            <table class="min-w-full bg-white border text-gray-700 dark:border-gray-600 border-gray-200 dark:bg-gray-700 dark:text-white">
                                                <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10">
                                                    <tr>
                                                        <th class="py-3 px-6 text-left text-sm font-semibold">Start Time</th>
                                                        <th class="py-3 px-6 text-left text-sm font-semibold">Stop Time</th>
                                                        <th class="py-3 px-6 text-left text-sm font-semibold">Duration</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="logs-body" class="text-gray-600 dark:bg-gray-700 dark:text-white">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="hidden border-t border-gray-200 dark:border-gray-600" id="approval-logs">
                                    <div class="flex justify-between items-center px-4">
                                        <h4 class="py-1 font-extrabold text-2xl text-center">Approvals</h4>
                                    </div>
                                    <div class="container mx-auto p-4">
                                        <div class="max-h-[200px] overflow-y-auto custom-scrollbar shadow-md rounded-lg mb-3">
                                            <table class="min-w-full bg-white border text-gray-700 dark:border-gray-600 border-gray-200 dark:bg-gray-700 dark:text-white">
                                                <thead class="bg-gray-100 dark:bg-gray-800">
                                                    <tr>
                                                        <th class="py-3 px-6 text-left text-sm font-semibold">Email</th>
                                                        <th class="py-3 px-6 text-left text-sm font-semibold">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="approvals-body" class="text-gray-600 dark:bg-gray-700 dark:text-white">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer pt-2">
                        <button type="button" class="close-modal px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-500">Close</button>
                    </div>
                </div>
            </div>

            <!-- Submission Modal -->
            <div id="submission-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
                <div
                    class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto my-auto p-6 rounded-lg w-11/12 max-w-2xl relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                        class="w-5 h-6 absolute top-2 right-2 cursor-pointer close-submission-modal">
                        <path fill-rule="evenodd"
                            d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-100 dark:border-gray-600">Submit Task</h2>
                    <div class="modal-body">
                        <form id="submission-form">
                            <input type="hidden" id="submission-task-id">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="github-repo">GitHub Repository Link *</label>
                                <input type="url" id="github-repo" class="w-full p-3 rounded-lg focus:outline-none border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"
                                    placeholder="https://github.com/username/repository" required>
                                <div id="github-error" class="text-red-500 text-sm mt-1 hidden">Please enter a valid GitHub URL</div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="live-view">Live View Link *</label>
                                <input type="url" id="live-view" class="w-full p-3 rounded-lg focus:outline-none border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"
                                    placeholder="https://your-project.com" required>
                                <div id="live-view-error" class="text-red-500 text-sm mt-1 hidden">Please enter a valid URL</div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="additional-notes">Additional Notes (Optional)</label>
                                <textarea id="additional-notes" rows="3" class="w-full p-3 rounded-lg focus:outline-none border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"
                                    placeholder="Any additional information about your submission..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer pt-4 flex justify-end space-x-3">
                        <button type="button" class="close-submission-modal px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</button>
                        <button type="button" id="submit-task-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-500">Submit Task</button>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>
    <?php include_once "./include/footerLinks.php"; ?>
    <script>
        let dataTable;

        document.addEventListener('DOMContentLoaded', async function() {
            dataTable = $('#assignedTasksTable').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true
            });
            await getAssignedTasks();
            await viewTask();
            await start();
            await stop();
            await complete();
            await setupSubmissionModal();
        });

        async function getAssignedTasks() {
            try {
                const response = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'getAssignedTask'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    const statusOrder = {
                        "working": 1,
                        "pending": 2,
                        "complete": 3
                    };
                    result.data.sort((a, b) => {
                        return statusOrder[a.status] - statusOrder[b.status];
                    });

                    dataTable.clear();
                    result.data.forEach(task => {
                        dataTable.row.add([
                            task.id,
                            task.title,
                            task.assign_by,
                            getStatusBadge((task.status === 'complete') ?
                                (task.approval_status === 2 ? 'Declined' : task.status) :
                                task.status
                            ),
                            formatDateTime(task.created_at),
                            `<div class="flex gap-2">
                                <button class="open-modal text-yellow-600 me-2"
                                    data-modal="view-task-modal"
                                    data-id="${task.id}" 
                                    data-title="${task.title}" 
                                    data-description="${task.description}" 
                                    data-assign-by="${task.assign_by}">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                        <g id="SVGRepo_iconCarrier"> 
                                            <path d="M9 4.45962C9.91153 4.16968 10.9104 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C3.75612 8.07914 4.32973 7.43025 5 6.82137" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> 
                                            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="1.5"></path> 
                                        </g>
                                    </svg>
                                </button>

                               ${task.status === 'pending' ? `
                                <button data-start="start-button" data-id="${task.id}" 
                                    class="bg-emerald-700 px-2 py-1 text-white rounded-sm start-btn">
                                    Start
                                </button>` : ''}


                                ${task.status === 'working' ? `
                                    <button data-stop="stop-button" data-id="${task.id}" 
                                        class="bg-red-600 px-2 py-1 text-white rounded-sm">
                                        Pause
                                    </button>
                                    <button data-complete="complete-button" data-id="${task.id}" 
                                        class="bg-sky-600 px-2 py-1 text-white rounded-sm open-submission-modal">
                                        Complete
                                    </button>
                                ` : ''}

                                ${task.status === 'complete' ? `
                                    <span class="cursor-not-allowed cursor-pointer bg-stone-600 px-2 py-1 text-white rounded-sm">
                                        Completed
                                    </span>
                                ` : ''}
                            </div>`
                        ]);
                    });

                    dataTable.draw();
                    const anyWorking = result.data.some(task => task.status === 'working');
                    if (anyWorking) {
                        document.querySelectorAll('.start-btn').forEach(btn => {
                            btn.disabled = true;
                            btn.classList.add('opacity-50', 'cursor-not-allowed');
                        });
                    }
                } else {
                    showToast('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Something went wrong!');
            }
        }

        let liveInterval;

        function viewTask() {
            document.getElementById("assignedTasksTable").addEventListener("click", async function(e) {
                const button = e.target.closest("[data-modal='view-task-modal']");
                if (!button) return;

                const modal = document.getElementById("view-task-modal");
                const tbody = document.getElementById('logs-body');
                const totalTimeEl = document.getElementById('total-time');
                const approvalsBody = document.getElementById('approvals-body');

                tbody.innerHTML = "";
                approvalsBody.innerHTML = "";
                totalTimeEl.innerText = "Total Time: 00:00:00";
                document.getElementById('time-logs').classList.add('hidden');

                let totalTime = 0;
                clearInterval(liveInterval);
                modal.querySelector("#title").value = button.dataset.title;
                modal.querySelector("#description").value = button.dataset.description;
                modal.querySelector("#assign_by").value = button.dataset.assignBy;

                try {
                    const response = await fetch('controller/timeLog.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'get',
                            task_id: button.dataset.id
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        if (result.logs.length > 0) {
                            document.getElementById('time-logs').classList.remove('hidden');

                            result.logs.forEach(log => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 bg-white transition-colors";

                                let startTd = `<td class="py-3 px-6 text-xs">${log.started_at}</td>`;

                                if (!log.stopped_at) {
                                    let startTime = new Date(log.started_at).getTime();
                                    let stopTd = `<td class="py-3 px-6 text-xs">--</td>`;

                                    let liveTd = document.createElement("td");
                                    liveTd.className = "py-3 px-6 text-xs live-counter";

                                    row.innerHTML = startTd + stopTd;
                                    row.appendChild(liveTd);
                                    tbody.appendChild(row);

                                    liveInterval = setInterval(() => {
                                        let now = Date.now();
                                        let diffSec = Math.floor((now - startTime) / 1000);

                                        liveTd.innerText = formatDuration(diffSec);
                                        totalTimeEl.innerText = `Total Time: ${formatDuration(totalTime + diffSec)}`;
                                    }, 1000);

                                } else {
                                    row.innerHTML = `
                                ${startTd}
                                <td class="py-3 px-6 text-xs">${log.stopped_at}</td>
                                <td class="py-3 px-6 text-xs">${formatDuration(log.duration)}</td>
                            `;
                                    totalTime += parseInt(log.duration, 10);
                                    tbody.appendChild(row);
                                }
                            });

                            totalTimeEl.innerText = `Total Time: ${formatDuration(totalTime)}`;
                        }
                    }
                } catch (error) {
                    console.error("Error:", error);
                }
            });

            document.querySelectorAll(".close-btn, .close-modal").forEach(btn => {
                btn.addEventListener("click", () => clearInterval(liveInterval));
            });
        }

        function setupSubmissionModal() {
            // Open submission modal when complete button is clicked
            document.getElementById("assignedTasksTable").addEventListener("click", function(e) {
                const button = e.target.closest(".open-submission-modal");
                if (!button) return;
                
                const taskId = button.dataset.id;
                document.getElementById('submission-task-id').value = taskId;
                
                // Reset form
                document.getElementById('submission-form').reset();
                document.getElementById('github-error').classList.add('hidden');
                document.getElementById('live-view-error').classList.add('hidden');
                
                // Show modal
                document.getElementById('submission-modal').classList.remove('hidden');
            });

            // Close submission modal
            document.querySelectorAll('.close-submission-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('submission-modal').classList.add('hidden');
                });
            });

            // Validate URLs
            document.getElementById('github-repo').addEventListener('input', function() {
                const errorDiv = document.getElementById('github-error');
                if (!this.value.includes('github.com')) {
                    errorDiv.classList.remove('hidden');
                } else {
                    errorDiv.classList.add('hidden');
                }
            });

            document.getElementById('live-view').addEventListener('input', function() {
                const errorDiv = document.getElementById('live-view-error');
                if (!this.value) {
                    errorDiv.classList.remove('hidden');
                } else {
                    errorDiv.classList.add('hidden');
                }
            });

            // Submit task
            document.getElementById('submit-task-btn').addEventListener('click', async function() {
                const taskId = document.getElementById('submission-task-id').value;
                const githubRepo = document.getElementById('github-repo').value;
                const liveView = document.getElementById('live-view').value;
                const additionalNotes = document.getElementById('additional-notes').value;

                // Validate inputs
                if (!githubRepo || !githubRepo.includes('github.com')) {
                    document.getElementById('github-error').classList.remove('hidden');
                    return;
                }

                if (!liveView) {
                    document.getElementById('live-view-error').classList.remove('hidden');
                    return;
                }
                 const now = new Date();
                const formattedDateTime = now.getFullYear() + "-" +
                    String(now.getMonth() + 1).padStart(2, '0') + "-" +
                    String(now.getDate()).padStart(2, '0') + " " +
                    String(now.getHours()).padStart(2, '0') + ":" +
                    String(now.getMinutes()).padStart(2, '0') + ":" +
                    String(now.getSeconds()).padStart(2, '0');
                try {
                    const response = await fetch('controller/timeLog.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'complete',
                            task_id: taskId,
                            github_repo: githubRepo,
                            live_view: liveView,
                            additional_notes: additionalNotes,
                            stoped_at: formattedDateTime
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast('success', result.message);
                        document.getElementById('submission-modal').classList.add('hidden');
                        getAssignedTasks();
                    } else {
                        showToast('error', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', 'Something went wrong!');
                }
            });
        }

        function start() {
            document.getElementById("assignedTasksTable").addEventListener("click", async function(e) {
                const button = e.target.closest("[data-start='start-button']");
                if (!button) return;
                // Prevent multiple active tasks
                const anyActive = document.querySelector('[data-stop="stop-button"]');
                if (anyActive) {
                    showToast('error', 'You can only work on one task at a time!');
                    return;
                }

                const id = button.dataset.id;
                const now = new Date();
                const formattedDateTime = now.getFullYear() + "-" +
                    String(now.getMonth() + 1).padStart(2, '0') + "-" +
                    String(now.getDate()).padStart(2, '0') + " " +
                    String(now.getHours()).padStart(2, '0') + ":" +
                    String(now.getMinutes()).padStart(2, '0') + ":" +
                    String(now.getSeconds()).padStart(2, '0');

                try {
                    const response = await fetch('controller/timeLog.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'start',
                            task_id: id,
                            started_at: formattedDateTime
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast('success', result.message);
                        getAssignedTasks();
                    } else {
                        showToast('error', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Something went wrong!');
                }
            });
        }

        function stop() {
            document.getElementById("assignedTasksTable").addEventListener("click", async function(e) {
                const button = e.target.closest("[data-stop='stop-button']");
                if (!button) return;
                const id = button.dataset.id;
                const now = new Date();
                const formattedDateTime = now.getFullYear() + "-" +
                    String(now.getMonth() + 1).padStart(2, '0') + "-" +
                    String(now.getDate()).padStart(2, '0') + " " +
                    String(now.getHours()).padStart(2, '0') + ":" +
                    String(now.getMinutes()).padStart(2, '0') + ":" +
                    String(now.getSeconds()).padStart(2, '0');

                try {
                    const response = await fetch('controller/timeLog.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'stop',
                            task_id: id,
                            stoped_at: formattedDateTime
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast('success', result.message);
                        getAssignedTasks();
                    } else {
                        showToast('error', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Something went wrong!');
                }
            });
        }

        function complete() {
            // This function is now handled by the submission modal
            // The complete functionality is moved to the submit-task-btn event listener
        }
    </script>
</body>

</html>