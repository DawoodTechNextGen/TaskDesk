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
<?php include_once "./include/headerLinks.php"; ?>

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
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Approvals</h2>
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
                            <tbody class="bg-white text-black dark:bg-gray-800 dark:text-white divide-y divide-gray-200 dark:divide-gray-700" id="tasks">
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <!-- View Modal -->
            <div id="view-task-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
                <div
                    class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[3%] p-6 rounded-lg w-11/12 max-w-6xl relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                        class="w-5 h-6 absolute top-2 right-2 cursor-pointer close-btn">
                        <path fill-rule="evenodd"
                            d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-100 dark:border-gray-600">View Task</h2>
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
                                <textarea id="description" class="w-full p-3 rounded-lg focus:outline-none border border-gray-200 bg-gray-200 dark:bg-gray-700 dark:border-gray-600" readonly></textarea>
                            </div>
                            <div class="grid grid-cols-2">
                                <div class="hidden border-t border-gray-200 dark:border-gray-600" id="time-logs">
                                    <div class="flex justify-between items-center px-4">
                                        <h4 class="py-1 font-extrabold text-2xl text-center">Time Logs</h4>
                                        <span id="total-time">Total Time: </span>
                                    </div>
                                    <div class="container mx-auto p-4">
                                        <div class="overflow-x-auto shadow-md rounded-lg">
                                            <table class="min-w-full bg-white border text-gray-700 dark:border-gray-600 border-gray-200 dark:bg-gray-700 dark:text-white">
                                                <thead class="bg-gray-100 dark:bg-gray-800">
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
                                        <div class="max-h-[200px] overflow-y-auto custom-scrollbar mb-3 shadow-md rounded-lg">
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
                ordering: false
            });
            await getApprovals();
            await viewTask();
            await approve();
            await decline();
        });
        async function getApprovals() {
            try {
                const response = await fetch('controller/approval.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'getApprovals'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    const statusOrder = {
                        "0": 1,
                        "1": 2,
                        "2": 3
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
                            getStatusBadge((task.status == 0) ? 'pending' : (task.status == 1 ? 'Approved' : 'Declined')),
                            formatDateTime(task.created_at),
                            `<div class="flex gap-2">
                                <button class="open-modal text-yellow-600 me-2"
                                    data-modal="view-task-modal"
                                    data-id="${task.id}" 
                                    data-title="${task.title}" 
                                    data-description="${task.description}" 
                                    data-assign-by="${task.assign_by}">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 4.45962C9.91153 4.16968 10.9104 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C3.75612 8.07914 4.32973 7.43025 5 6.82137" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="1.5"></path> </g></svg>
                                </button>
                                ${task.status === 0 ? `
                                    <button class="bg-green-500 px-2 py-1 text-white rounded-sm"
                                    data-approve="approve-button" data-id="${task.id}">
                                        Approve
                                    </button>
                                    <button class="bg-red-500 px-2 py-1 text-white rounded-sm"
                                    data-decline="decline-button" data-id="${task.id}">
                                        Decline
                                    </button>
                                    ` : task.status === 1 ?
                                    `<button
                                        class="cursor-not-allowed bg-green-700 px-2 py-1 text-white rounded-sm" disabled>
                                        Approved
                                    </button>`: `<button class="cursor-not-allowed bg-red-500 px-2 py-1 text-white rounded-sm" disabled>
                                        Declined
                                    </button>`}
                            </div>`
                        ]);
                    });

                    dataTable.draw();
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
                document.getElementById('approval-logs').classList.add('hidden');

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
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors";

                                let startTd = `<td class="py-3 px-6 text-sm">${log.started_at}</td>`;

                                if (!log.stopped_at) {
                                    let startTime = new Date(log.started_at).getTime();
                                    let stopTd = `<td class="py-3 px-6 text-sm">-</td>`;

                                    let liveTd = document.createElement("td");
                                    liveTd.className = "py-3 px-6 text-sm live-counter";

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
                                <td class="py-3 px-6 text-sm">${log.stopped_at}</td>
                                <td class="py-3 px-6 text-sm">${formatDuration(log.duration)}</td>
                            `;
                                    totalTime += parseInt(log.duration, 10);
                                    tbody.appendChild(row);
                                }
                            });

                            totalTimeEl.innerText = `Total Time: ${formatDuration(totalTime)}`;
                        }

                        if (result.approvals.length > 0) {
                            document.getElementById('approval-logs').classList.remove('hidden');

                            result.approvals.forEach(approval => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors";

                                let email = `<td class="py-3 px-6 text-sm">${approval.email}</td>`;
                                let status = `<td class="py-3 px-6 text-sm">${getStatusBadge(approval.status)}</td>`;

                                row.innerHTML = email + status;
                                approvalsBody.appendChild(row);
                            });
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


        function approve() {
            document.getElementById("assignedTasksTable").addEventListener("click", async function(e) {
                const button = e.target.closest("[data-approve='approve-button']");
                if (!button) return;
                const id = button.dataset.id;
                console.log(id);
                try {
                    const response = await fetch('controller/approval.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'approve',
                            task_id: id,
                            status: 1,
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast('success', result.message);
                        getApprovals();
                    } else {
                        showToast('error', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Something went wrong!');
                }
            });
        }

        function decline() {
            document.getElementById("assignedTasksTable").addEventListener("click", async function(e) {
                const button = e.target.closest("[data-decline='decline-button']");
                if (!button) return;
                const id = button.dataset.id;
                console.log(id);
                try {
                    const response = await fetch('controller/approval.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'decline',
                            task_id: id,
                            status: 2,
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast('success', result.message);
                        getApprovals();
                    } else {
                        showToast('error', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Something went wrong!');
                }
            });
        }
        document.querySelectorAll('input[name="approval"]').forEach(radio => {
            const sendAprrovalsTo = document.getElementById('send-aprrovals-to');
            radio.addEventListener('change', function() {
                const radioValue = radio.value;
                radioValue === 'add-approval' ? sendAprrovalsTo.classList.remove('hidden') : sendAprrovalsTo.classList.add('hidden');
            });
        });
    </script>
</body>

</html>