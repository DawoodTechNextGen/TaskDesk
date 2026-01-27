<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 3 && $_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4)) {
    header('location: login.php');
    exit;
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Freeze Requests - TaskDesk';
include "./include/headerLinks.php";
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-50 space-y-4"></div>
    <div class="flex h-screen overflow-hidden">
        <?php include "./include/sideBar.php" ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include "./include/header.php" ?>
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Internship Freeze Requests</h2>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table id="freezeRequestsTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-200 dark:bg-indigo-500">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Intern Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Technology</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Start Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">End Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Requested On</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white text-black dark:bg-gray-800 dark:text-white divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include "./include/footer.php" ?>
        </div>
    </div>

    <!-- Reason Modal -->
    <div id="reason-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-20 p-6 rounded-lg w-11/12 max-w-md relative">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-6 absolute top-2 right-2 cursor-pointer close-modal">
                <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
            <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-100 dark:border-gray-600">Freeze Reason</h2>
            <p id="reason-text" class="text-sm text-gray-700 dark:text-gray-300"></p>
            <div class="mt-4 flex justify-end">
                <button class="close-modal px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Close</button>
            </div>
        </div>
    </div>

    <?php include "./include/footerLinks.php" ?>
    <script>
        let dataTable;

        document.addEventListener('DOMContentLoaded', async function() {
            dataTable = $('#freezeRequestsTable').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true
            });
            await loadFreezeRequests();

            // Close modal handlers
            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('reason-modal').classList.add('hidden');
                });
            });
        });

        async function loadFreezeRequests() {
            try {
                const response = await fetch('controller/freeze.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'get_freeze_requests'})
                });

                const result = await response.json();
                if (result.success) {
                    dataTable.clear();
                    result.data.forEach(request => {
                        dataTable.row.add([
                            request.name,
                            request.email,
                            request.technology || 'N/A',
                            request.freeze_start_date,
                            request.freeze_end_date,
                            formatDateTime(request.freeze_requested_at),
                            `<div class="flex gap-2">
                                <button onclick="viewReason('${request.freeze_reason.replace(/'/g, "\\'")}')" 
                                    class="bg-blue-600 px-2 py-1 text-white rounded-sm text-xs">
                                    View Reason
                                </button>
                                <button onclick="approveFreeze(${request.id})" 
                                    class="bg-green-600 px-2 py-1 text-white rounded-sm text-xs">
                                    Approve
                                </button>
                                <button onclick="rejectFreeze(${request.id})" 
                                    class="bg-red-600 px-2 py-1 text-white rounded-sm text-xs">
                                    Reject
                                </button>
                            </div>`
                        ]);
                    });
                    dataTable.draw();
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('error', 'Failed to load freeze requests');
            }
        }

        function viewReason(reason) {
            document.getElementById('reason-text').textContent = reason;
            document.getElementById('reason-modal').classList.remove('hidden');
        }

        async function approveFreeze(userId) {
            if (!confirm('Are you sure you want to approve this freeze request?')) return;

            try {
                const response = await fetch('controller/freeze.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'approve_freeze',
                        user_id: userId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('success', result.message);
                    await loadFreezeRequests();
                } else {
                    showToast('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('error', 'Failed to approve freeze request');
            }
        }

        async function rejectFreeze(userId) {
            if (!confirm('Are you sure you want to reject this freeze request?')) return;

            try {
                const response = await fetch('controller/freeze.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'reject_freeze',
                        user_id: userId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('success', result.message);
                    await loadFreezeRequests();
                } else {
                    showToast('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('error', 'Failed to reject freeze request');
            }
        }
    </script>
</body>
</html>
