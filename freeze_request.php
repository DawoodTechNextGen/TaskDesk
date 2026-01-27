<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) { // Only interns
    header('location: login.php');
    exit;
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Freeze Internship - TaskDesk';
include "./include/headerLinks.php";
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-50 space-y-4"></div>
    <div class="flex h-screen overflow-hidden">
        <?php include "./include/sideBar.php" ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include "./include/header.php" ?>
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-4xl mx-auto my-5">
                    <!-- Freeze Status Card -->
                    <div id="freeze-status-card" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6 hidden">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Freeze Status</h3>
                                <p id="freeze-status-text" class="text-gray-600 dark:text-gray-300"></p>
                            </div>
                            <span id="freeze-status-badge" class="px-4 py-2 rounded-full text-sm font-semibold"></span>
                        </div>
                        <div id="freeze-dates" class="mt-4 text-sm text-gray-600 dark:text-gray-400 hidden">
                            <p><strong>From:</strong> <span id="freeze-start"></span></p>
                            <p><strong>To:</strong> <span id="freeze-end"></span></p>
                            <p><strong>Reason:</strong> <span id="freeze-reason"></span></p>
                        </div>
                    </div>

                    <!-- Request Freeze Form -->
                    <div id="freeze-request-form" class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Request Internship Freeze</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Temporarily pause your internship (e.g., during exams). Maximum 30 days.
                            </p>
                        </div>
                        <form id="freezeForm" class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="freeze_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Start Date *
                                    </label>
                                    <input type="date" id="freeze_start_date" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label for="freeze_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        End Date *
                                    </label>
                                    <input type="date" id="freeze_end_date" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div>
                                <label for="freeze_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Reason *
                                </label>
                                <textarea id="freeze_reason" rows="4" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-white"
                                    placeholder="Please explain why you need to freeze your internship..."></textarea>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="submit"
                                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
            <?php include "./include/footer.php" ?>
        </div>
    </div>
    <?php include "./include/footerLinks.php" ?>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            await loadFreezeStatus();
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('freeze_start_date').min = today;
            document.getElementById('freeze_end_date').min = today;

            // Update end date min when start date changes
            document.getElementById('freeze_start_date').addEventListener('change', function() {
                document.getElementById('freeze_end_date').min = this.value;
            });

            // Form submission
            document.getElementById('freezeForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const start = document.getElementById('freeze_start_date').value;
                const end = document.getElementById('freeze_end_date').value;
                const reason = document.getElementById('freeze_reason').value.trim();

                if (!start || !end || !reason) {
                    showToast('error', 'All fields are required');
                    return;
                }

                try {
                    const response = await fetch('controller/freeze.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            action: 'request_freeze',
                            freeze_start_date: start,
                            freeze_end_date: end,
                            freeze_reason: reason
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showToast('success', result.message);
                        document.getElementById('freezeForm').reset();
                        await loadFreezeStatus();
                    } else {
                        showToast('error', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', 'Something went wrong!');
                }
            });
        });

        async function loadFreezeStatus() {
            try {
                const response = await fetch('controller/freeze.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'get_freeze_status'})
                });

                const result = await response.json();
                if (result.success && result.data) {
                    const status = result.data.freeze_status;
                    const isComplete = result.data.is_internship_complete;
                    const statusCard = document.getElementById('freeze-status-card');
                    const statusBadge = document.getElementById('freeze-status-badge');
                    const statusText = document.getElementById('freeze-status-text');
                    const freezeDates = document.getElementById('freeze-dates');
                    const form = document.getElementById('freeze-request-form');

                    // If internship is complete, hide everything and show completion message
                    if (isComplete) {
                        statusCard.classList.remove('hidden');
                        statusBadge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                        statusBadge.textContent = 'Completed';
                        statusText.textContent = 'Your internship has been completed. Freeze functionality is not available.';
                        freezeDates.classList.add('hidden');
                        form.classList.add('hidden');
                        return;
                    }

                    if (status === 'active') {
                        statusCard.classList.add('hidden');
                        form.classList.remove('hidden');
                    } else {
                        statusCard.classList.remove('hidden');
                        
                        if (status === 'freeze_requested') {
                            statusBadge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                            statusBadge.textContent = 'Pending Approval';
                            statusText.textContent = 'Your freeze request is pending supervisor approval.';
                            form.classList.add('hidden');
                            freezeDates.classList.remove('hidden');
                        } else if (status === 'frozen') {
                            statusBadge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                            statusBadge.textContent = 'Frozen';
                            statusText.textContent = 'Your internship is currently frozen. You will be automatically resumed after the end date.';
                            form.classList.add('hidden');
                            freezeDates.classList.remove('hidden');
                        }

                        document.getElementById('freeze-start').textContent = result.data.freeze_start_date || 'N/A';
                        document.getElementById('freeze-end').textContent = result.data.freeze_end_date || 'N/A';
                        document.getElementById('freeze-reason').textContent = result.data.freeze_reason || 'N/A';
                    }
                }
            } catch (error) {
                console.error('Error loading freeze status:', error);
            }
        }
    </script>
</body>
</html>
