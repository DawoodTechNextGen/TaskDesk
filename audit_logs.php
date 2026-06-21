<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';

// Fetch audit logs query
$logs_result = $conn->query("
    SELECT a.id, a.action, a.details, a.created_at, u.name as admin_name, u.email as admin_email 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'System Audit Logs - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                
                <!-- Title -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">System Audit Logs</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track and monitor important admin and manager actions across the platform.</p>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Activity Log History</h2>
                    </div>
                    
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="auditLogsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Performed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Action Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs dark:text-gray-100 text-gray-800">
                                <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                    <?php while ($row = $logs_result->fetch_assoc()): ?>
                                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['id']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($row['admin_name'] ?? 'System') ?>
                                                <div class="text-[10px] text-gray-400 font-normal"><?= htmlspecialchars($row['admin_email'] ?? '') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                                    <?= htmlspecialchars($row['action']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 font-medium max-w-xs break-words"><?= htmlspecialchars($row['details']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= date('j F Y, g:i A', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        $(document).ready(function() {
            $('#auditLogsTable').DataTable({
                ordering: true,
                pageLength: 25,
                order: [[4, 'desc']] // Order by Timestamp by default
            });
        });
    </script>
</body>

</html>
