<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], [1, 3, 4])) {
    header('location:' . BASE_URL . 'login.php');
    exit;
}
include_once './include/connection.php';
$status = $_GET['status'] ?? 'all';

// Map status to readable title
$statusTitles = [
    'pending' => 'Pending Tasks',
    'working' => 'Tasks in Progress',
    'complete' => 'Completed Tasks',
    'pending_review' => 'Tasks Under Review',
    'approved' => 'Approved Tasks',
    'rejected' => 'Rejected Tasks',
    'needs_improvement' => 'Tasks Needing Improvement',
    'expired' => 'Expired Tasks',
    'all' => 'All Tasks'
];

$page_title = ($statusTitles[$status] ?? 'Task Management') . ' - TaskDesk';
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php" ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-8 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-7xl mx-auto">
                    <!-- Page Header -->
                    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-white"><?= $statusTitles[$status] ?? 'Task Management' ?></h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage and track intern progress effectively</p>
                        </div>
                        <?php if (in_array($_SESSION['user_role'], [1, 3])) : ?>
                        <a href="tasks_create.php" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-blue-500/20 active:scale-95">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create New Task
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Tasks Table Container -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6">
                            <div class="overflow-x-auto relative min-h-[400px]">
                                <!-- Table Loader -->
                                <div id="table-loader" style="display: none;" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 backdrop-blur-[1px] z-[100] flex items-center justify-center transition-opacity duration-300">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-10 h-10 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-400">Loading tasks...</span>
                                    </div>
                                </div>
                                <table id="tasksTable" class="w-full text-left border-collapse">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sr#</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task ID</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assign To</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned At</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-black dark:text-gray-300">
                                        <!-- Data populated by DataTables -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- Modals (Moved from index.php) -->
    <?php include_once "./include/taskModals.php"; ?>

    <?php include_once "./include/footerLinks.php"; ?>
    <script src="./assets/js/tasks-management.js?v=<?= time() ?>"></script>
    <script src="./assets/js/task-review.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentStatus = '<?= $status ?>';
            initTaskManagement(currentStatus);
        });
    </script>
</body>
</html>
