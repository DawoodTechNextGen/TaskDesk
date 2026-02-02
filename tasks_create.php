<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header('location:' . BASE_URL . 'login.php');
    exit;
}
include_once './include/connection.php';
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Create New Task - TaskDesk';
include_once "./include/headerLinks.php";
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <!-- Modern Sidebar -->
        <?php include_once "./include/sideBar.php"; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include_once "./include/header.php" ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-4xl mx-auto mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create New Task</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Assign a new task to one of your managed interns</p>
                            </div>
                            <a href="index.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        </div>

                        <form id="create-task-form" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Task Title</label>
                                    <input type="text" id="title" required 
                                        class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none" 
                                        placeholder="e.g. Implement Login API">
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Due Date</label>
                                    <input type="date" id="due_date" required 
                                        class="w-full p-3 rounded-xl border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white transition-all outline-none">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Assign To Intern</label>
                                <div class="searchable-wrapper relative w-full">
                                    <select id="user_id" class="searchable-select hidden">
                                        <option value="">Select Intern</option>
                                        <?php
                                        $userQuery = "SELECT id, name FROM users 
                                                      WHERE user_role = 2 
                                                        AND supervisor_id = $user_id 
                                                        AND freeze_status = 'active'
                                                        AND DATE_ADD(created_at, INTERVAL IF(internship_type = 0, 4, 12) WEEK) > NOW()
                                                      ORDER BY name ASC";
                                        $userResult = mysqli_query($conn, $userQuery);
                                        while ($user = mysqli_fetch_assoc($userResult)) {
                                            echo "<option value=\"{$user['id']}\">{$user['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="relative">
                                        <input type="text"
                                            class="searchable-input w-full p-3 pr-10 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white cursor-pointer transition-all outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Search and select an intern"
                                            autocomplete="off">
                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </div>
                                    <ul class="searchable-dropdown hidden absolute z-50 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl mt-2 shadow-xl max-h-60 overflow-y-auto custom-scrollbar"></ul>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Detailed Description</label>
                                <div id="description-editor" class="min-h-[250px] rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white"></div>
                            </div>

                            <div class="flex justify-end pt-6 border-t border-gray-100 dark:border-gray-700">
                                <button type="submit" 
                                    class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-blue-500/20 active:scale-95 flex items-center space-x-2">
                                    <span>Create Task</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
    <script src="./assets/js/libs/chart.js"></script>
    <script src="./assets/js/tasks-management.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initTaskCreation();
        });
    </script>
</body>
</html>
