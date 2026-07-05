<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], [1, 4])) {
    header('location:' . BASE_URL . 'login.php');
    exit;
}
include_once './include/connection.php';
$page_title = 'Chat Request Approvals - TaskDesk';
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once "./include/headerLinks.php"; ?>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php" ?>
            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-8 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Chat Management</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Configure role-to-role chat permissions and monitor active intern conversations.</p>
                        </div>
                    </div>

                    <!-- Chat Access Rules Card -->
                    <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in duration-300">
                        <div class="p-6">
                            <div class="mb-4">
                                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Chat Access Rules & Permissions</h2>
                                <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">Define which roles are allowed to initiate direct chats with each other.</p>
                            </div>
                            
                            <form id="rulesForm" class="space-y-4">
                                <div id="rulesMessage" class="hidden rounded-xl px-4 py-3 text-sm"></div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition cursor-pointer select-none">
                                        <input type="checkbox" id="rule_admin_to_all" name="admin_to_all" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800 dark:text-white">Admin to All</p>
                                            <p class="text-xs text-gray-400">Allow Admin to chat with everyone</p>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition cursor-pointer select-none">
                                        <input type="checkbox" id="rule_supervisor_to_supervisor" name="supervisor_to_supervisor" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800 dark:text-white">Supervisor to Supervisor</p>
                                            <p class="text-xs text-gray-400">Allow Supervisors to chat with each other</p>
                                        </div>
                                    </label>

                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition cursor-pointer select-none">
                                        <input type="checkbox" id="rule_intern_to_intern" name="intern_to_intern" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800 dark:text-white">Intern to Intern</p>
                                            <p class="text-xs text-gray-400">Allow Interns to chat with other Interns</p>
                                        </div>
                                    </label>

                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition cursor-pointer select-none">
                                        <input type="checkbox" id="rule_intern_to_supervisor" name="intern_to_supervisor" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800 dark:text-white">Intern to Supervisor</p>
                                            <p class="text-xs text-gray-400">Allow Interns to chat with Supervisors</p>
                                        </div>
                                    </label>

                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition cursor-pointer select-none">
                                        <input type="checkbox" id="rule_supervisor_to_intern" name="supervisor_to_intern" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800 dark:text-white">Supervisor to Intern</p>
                                            <p class="text-xs text-gray-400">Allow Supervisors to chat with Interns</p>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition shadow">
                                        Save Rules
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Monitored Intern Chats Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in duration-300">
                        <div class="p-6">
                            <div class="mb-4">
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Monitored Intern Chats</h2>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">View active chat sessions between interns and monitor their conversations.</p>
                            </div>
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                                <table id="monitoredTable" class="min-w-full text-left divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Intern A</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Intern B</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Activity</th>
                                            <th class="px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="monitoredBody" class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Loading monitored chats...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Chat Logs -->
                    <div id="logsModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
                        
                        <!-- Modal container -->
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl flex flex-col h-[650px] border border-gray-200 dark:border-gray-700">
                                <!-- Header -->
                                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-white" id="modalTitle">Intern Chat Logs</h3>
                                    <button type="button" id="closeModalBtn" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                        <span class="sr-only">Close</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Messages List -->
                                <div class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900/50 space-y-4" id="logsContainer">
                                    <!-- Messages will be rendered here dynamically -->
                                </div>
                                
                                <!-- Footer -->
                                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/80 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                                    <button type="button" id="closeModalFooterBtn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white text-sm font-semibold rounded-xl transition">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
    <script src="./assets/js/chat-requests.js?v=<?= time() ?>"></script>
</body>
</html>
