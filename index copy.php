<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login-signup.php');
} else {
    include_once './include/connection.php';
}

// Get dynamic data based on user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role == 1) {
    // Admin data
    $total_users = $conn->query("SELECT COUNT(id) as total FROM users WHERE status = 1")->fetch_assoc()['total'];
    $active_interns = $conn->query("SELECT COUNT(id) as total FROM users WHERE user_role = 2 AND status = 1")->fetch_assoc()['total'];
    $total_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks")->fetch_assoc()['total'];
    $total_tech = $conn->query("SELECT COUNT(id) as total FROM technologies")->fetch_assoc()['total'];

    // Additional admin stats
    $pending_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status = 'pending'")->fetch_assoc()['total'];
    $completed_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status = 'complete'")->fetch_assoc()['total'];
    $working_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status = 'working'")->fetch_assoc()['total'];
    $overdue_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status != 'complete' AND due_date < CURDATE()")->fetch_assoc()['total'];

    // Monthly task trends
    $monthly_tasks = $conn->query("SELECT 
        DATE_FORMAT(created_at, '%b') as month,
        COUNT(*) as task_count
        FROM tasks 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)")->fetch_all(MYSQLI_ASSOC);

    // Technology-wise task distribution
    $tech_tasks = $conn->query("SELECT 
        t.name as tech_name,
        COUNT(task.id) as task_count
        FROM technologies t
        LEFT JOIN users u ON t.id = u.tech_id
        LEFT JOIN tasks task ON u.id = task.assign_to OR u.id = task.created_by
        GROUP BY t.id
        ORDER BY task_count DESC
        LIMIT 5")->fetch_all(MYSQLI_ASSOC);
} elseif ($user_role == 2) {
    // Intern data
    $assigned_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE assign_to = ?");
    $assigned_tasks->bind_param("i", $user_id);
    $assigned_tasks->execute();
    $assigned_tasks = $assigned_tasks->get_result()->fetch_assoc()['total'];

    $completed_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE assign_to = ? AND status = 'complete'");
    $completed_tasks->bind_param("i", $user_id);
    $completed_tasks->execute();
    $completed_tasks = $completed_tasks->get_result()->fetch_assoc()['total'];

    $working_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE assign_to = ? AND status = 'working'");
    $working_tasks->bind_param("i", $user_id);
    $working_tasks->execute();
    $working_tasks = $working_tasks->get_result()->fetch_assoc()['total'];

    $pending_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE assign_to = ? AND status = 'pending'");
    $pending_tasks->bind_param("i", $user_id);
    $pending_tasks->execute();
    $pending_tasks = $pending_tasks->get_result()->fetch_assoc()['total'];
} elseif ($user_role == 3) {
    // Supervisor data
    $generated_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE created_by = ?");
    $generated_tasks->bind_param("i", $user_id);
    $generated_tasks->execute();
    $generated_tasks = $generated_tasks->get_result()->fetch_assoc()['total'];

    // Get supervisor's technology
    $supervisor_tech = $conn->prepare("SELECT tech_id FROM users WHERE id = ?");
    $supervisor_tech->bind_param("i", $user_id);
    $supervisor_tech->execute();
    $tech_result = $supervisor_tech->get_result();
    $tech_id = $tech_result->num_rows > 0 ? $tech_result->fetch_assoc()['tech_id'] : null;

    if ($tech_id) {
        $managed_interns = $conn->prepare("SELECT COUNT(id) as total FROM users WHERE user_role = 2 AND tech_id = ? AND status = 1");
        $managed_interns->bind_param("i", $tech_id);
        $managed_interns->execute();
        $managed_interns = $managed_interns->get_result()->fetch_assoc()['total'];
    } else {
        $managed_interns = 0;
    }

    $overdue_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE created_by = ? AND status != 'complete' AND due_date < CURDATE()");
    $overdue_tasks->bind_param("i", $user_id);
    $overdue_tasks->execute();
    $overdue_tasks = $overdue_tasks->get_result()->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<!-- headerLinks -->
<?php
$page_title = 'Dashboard - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4">
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

                <!-- Role-based Dashboard Sections -->
                <?php if ($user_role == 1): ?>
                    <!-- ==================== ADMIN DASHBOARD ==================== -->
                    <div class="mb-8">

                        <!-- Admin Stats Cards - Modern Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <!-- Total Users -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <div class="relative">
                                    <p class="text-blue-500 dark:text-blue-100 text-sm font-medium mb-2">Total Users</p>
                                    <h3 class="text-3xl font-bold mb-2 text-black dark:text-white"><?= $total_users ?></h3>
                                    <p class="text-blue-500 dark:text-blue-100 text-sm">Active members</p>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl text-white">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle> <path d="M18 9C19.6569 9 21 7.88071 21 6.5C21 5.11929 19.6569 4 18 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M6 9C4.34315 9 3 7.88071 3 6.5C3 5.11929 4.34315 4 6 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M17.1973 15C17.7078 15.5883 18 16.2714 18 17C18 19.2091 15.3137 21 12 21C8.68629 21 6 19.2091 6 17C6 14.7909 8.68629 13 12 13C12.3407 13 12.6748 13.0189 13 13.0553" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M20 19C21.7542 18.6153 23 17.6411 23 16.5C23 15.3589 21.7542 14.3847 20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M4 19C2.24575 18.6153 1 17.6411 1 16.5C1 15.3589 2.24575 14.3847 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>

                            <!-- Active Interns -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <div class="relative">
                                    <p class="text-green-500 dark:text-green-100 text-sm font-medium mb-2">Active Interns</p>
                                    <h3 class="text-3xl font-bold mb-2 text-black dark:text-white"><?= $active_interns ?></h3>
                                    <p class="text-green-500 dark:text-green-100 text-sm">Currently training</p>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>

                            <!-- Total Tasks -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <div class="relative">
                                    <p class="text-purple-500 dark:text-purple-100 text-sm font-medium mb-2">Total Tasks</p>
                                    <h3 class="text-black dark:text-white text-3xl font-bold mb-2"><?= $total_tasks ?></h3>
                                    <p class="text-purple-500 dark:text-purple-100 text-sm">All time tasks</p>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>

                            <!-- Technologies -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <div class="relative">
                                    <p class="text-orange-500 dark:text-orange-100 text-sm font-medium mb-2">Technologies</p>
                                    <h3 class="text-black dark:text-white text-3xl font-bold mb-2"><?= $total_tech ?></h3>
                                    <p class="text-orange-500 dark:text-orange-100 text-sm">Available stacks</p>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>
                        </div>

                        <!-- Task Status Overview Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Pending</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $pending_tasks ?></h3>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">In Progress</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $working_tasks ?></h3>
                                    </div>
                                    <div class="bg-yellow-100 dark:bg-yellow-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-green-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Completed</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $completed_tasks ?></h3>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-red-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Overdue</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $overdue_tasks ?></h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Charts Section -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
                            <!-- Task Status Distribution - Modern Donut Chart -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Task Status Distribution</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Real-time</span>
                                </div>
                                <div class="h-80 relative">
                                    <canvas id="taskStatusChart"></canvas>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-4" id="taskStatusLegend">
                                    <!-- Legend will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Monthly Task Trends - Modern Line Chart -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Monthly Task Trends</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Last 6 months</span>
                                </div>
                                <div class="h-80">
                                    <canvas id="monthlyTrendsChart"></canvas>
                                </div>
                                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4" id="monthlyTrendsStats">
                                    <!-- Stats will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Additional Charts Row -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- User Role Distribution - Modern Pie Chart -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">User Distribution</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">By Role</span>
                                </div>
                                <div class="h-80 relative">
                                    <canvas id="userRoleChart"></canvas>
                                </div>
                                <div class="mt-4 flex justify-center space-x-6" id="userRoleLegend">
                                    <!-- Legend will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Technology-wise Task Distribution -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Tasks by Technology</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Top 5</span>
                                </div>
                                <div class="h-80">
                                    <canvas id="techTaskChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity & Quick Actions -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Recent Users -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 lg:col-span-2">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Recent Users</h3>
                                    <a href="users.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">User</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Role</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Technology</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Status</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_users = $conn->query("SELECT u.name, u.user_role, t.name as tech_name, u.status, u.created_at
                                                            FROM users u 
                                                            LEFT JOIN technologies t ON u.tech_id = t.id 
                                                            WHERE u.user_role != 1
                                                            ORDER BY u.created_at DESC 
                                                            LIMIT 6");
                                            while ($user = $recent_users->fetch_assoc()):
                                            ?>
                                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td class="py-4">
                                                        <div class="flex items-center">
                                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-black dark:text-white text-sm font-bold mr-3">
                                                                <?= strtoupper(substr($user['name'], 0, 1) . substr(strstr($user['name'], ' '), 1, 1)) ?>
                                                            </div>
                                                            <span class="text-sm font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($user['name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?= $user['user_role'] == 1 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : ($user['user_role'] == 2 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300') ?>">
                                                            <?= $user['user_role'] == 1 ? 'Admin' : ($user['user_role'] == 2 ? 'Intern' : 'Supervisor') ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 text-sm text-gray-600 dark:text-gray-300"><?= $user['tech_name'] ?? 'N/A' ?></td>
                                                    <td class="py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $user['status'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' ?>">
                                                            <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 text-sm text-gray-500 dark:text-gray-400"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Quick Actions & System Overview -->
                            <div class="space-y-6">
                                <!-- Quick Actions -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Quick Actions</h3>
                                    <div class="space-y-3">
                                        <a href="users.php" class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 text-blue-700 dark:text-blue-300 rounded-xl hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-800/50 dark:hover:to-blue-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3 text-white">
                                                    <svg width="22px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle> <path d="M18 9C19.6569 9 21 7.88071 21 6.5C21 5.11929 19.6569 4 18 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M6 9C4.34315 9 3 7.88071 3 6.5C3 5.11929 4.34315 4 6 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M17.1973 15C17.7078 15.5883 18 16.2714 18 17C18 19.2091 15.3137 21 12 21C8.68629 21 6 19.2091 6 17C6 14.7909 8.68629 13 12 13C12.3407 13 12.6748 13.0189 13 13.0553" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M20 19C21.7542 18.6153 23 17.6411 23 16.5C23 15.3589 21.7542 14.3847 20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M4 19C2.24575 18.6153 1 17.6411 1 16.5C1 15.3589 2.24575 14.3847 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                                                </div>
                                                <span class="font-medium">Manage Users</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="technologies.php" class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 text-green-700 dark:text-green-300 rounded-xl hover:from-green-100 hover:to-green-200 dark:hover:from-green-800/50 dark:hover:to-green-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Manage Technologies</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="tasks.php" class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 text-purple-700 dark:text-purple-300 rounded-xl hover:from-purple-100 hover:to-purple-200 dark:hover:from-purple-800/50 dark:hover:to-purple-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">View All Tasks</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="reports.php" class="flex items-center justify-between p-3 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 text-orange-700 dark:text-orange-300 rounded-xl hover:from-orange-100 hover:to-orange-200 dark:hover:from-orange-800/50 dark:hover:to-orange-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Generate Reports</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- System Overview -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">System Overview</h3>
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">System Health</span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                Optimal
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Database</span>
                                            <span class="text-sm font-medium text-gray-800 dark:text-white">Connected</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Backup</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400"><?= date('M j, H:i') ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Uptime</span>
                                            <span class="text-sm font-medium text-gray-800 dark:text-white">99.9%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($user_role == 2): ?>
                    <!-- ==================== INTERN DASHBOARD ==================== -->
                    <div class="mb-8">
                        <!-- Intern Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <!-- Assigned Tasks -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Assigned Tasks</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $assigned_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed Tasks -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Completed</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $completed_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- In Progress -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">In Progress</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $working_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Tasks -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Pending</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $pending_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Charts -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Task Progress -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Task Progress</h3>
                                <div class="h-64">
                                    <canvas id="internTaskChart"></canvas>
                                </div>
                            </div>

                            <!-- Weekly Hours -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Weekly Hours</h3>
                                <div class="h-64">
                                    <canvas id="weeklyHoursChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Tasks & Performance -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Recent Tasks -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 lg:col-span-2">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Recent Tasks</h3>
                                <div class="space-y-4">
                                    <?php
                                    $recent_tasks = $conn->prepare("SELECT t.title, t.status, t.due_date, u.name as assigned_by 
                                                      FROM tasks t 
                                                      JOIN users u ON t.created_by = u.id 
                                                      WHERE t.assign_to = ? 
                                                      ORDER BY t.created_at DESC 
                                                      LIMIT 5");
                                    $recent_tasks->bind_param("i", $user_id);
                                    $recent_tasks->execute();
                                    $tasks_result = $recent_tasks->get_result();

                                    while ($task = $tasks_result->fetch_assoc()):
                                    ?>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($task['title']) ?></h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Assigned by: <?= htmlspecialchars($task['assigned_by']) ?></p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-block px-2 py-1 text-xs rounded-full <?=
                                                                                                            $task['status'] == 'complete' ? 'bg-green-100 text-green-800' : ($task['status'] == 'working' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                                                                                                            ?>">
                                                    <?= ucfirst($task['status']) ?>
                                                </span>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Due: <?= date('M d', strtotime($task['due_date'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- Performance Stats -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Performance</h3>
                                <div class="space-y-4">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2" id="completionRate">0%</div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Task Completion Rate</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2" id="avgCompletionTime">0d</div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Avg. Completion Time</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mb-2" id="totalHours">0h</div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Hours Logged</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($user_role == 3): ?>
                    <!-- ==================== SUPERVISOR DASHBOARD ==================== -->
                    <div class="mb-8">
                        <!-- Supervisor Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <!-- Total Generated Tasks -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Generated Tasks</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $generated_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Interns -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Managed Interns</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $managed_interns ?>
                                        </h3>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Tasks Completion Rate -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Completion Rate</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white" id="supervisorCompletionRate">0%</h3>
                                    </div>
                                    <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Overdue Tasks -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Overdue Tasks</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $overdue_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Team Performance -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Team Performance</h3>
                                <div class="h-64">
                                    <canvas id="teamPerformanceChart"></canvas>
                                </div>
                            </div>

                            <!-- Task Status Overview -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Task Status Overview</h3>
                                <div class="h-64">
                                    <canvas id="supervisorTaskChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Intern Performance & Recent Activity -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Top Performers -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Top Performers</h3>
                                <div class="space-y-3">
                                    <?php
                                    $top_performers = $conn->prepare("SELECT u.name, COUNT(t.id) as completed_tasks 
                                                      FROM users u 
                                                      LEFT JOIN tasks t ON u.id = t.assign_to AND t.status = 'complete'
                                                      WHERE u.user_role = 2 AND u.tech_id IN (
                                                          SELECT tech_id FROM users WHERE id = ?
                                                      )
                                                      GROUP BY u.id 
                                                      ORDER BY completed_tasks DESC 
                                                      LIMIT 5");
                                    $top_performers->bind_param("i", $user_id);
                                    $top_performers->execute();
                                    $performers_result = $top_performers->get_result();

                                    $rank = 1;
                                    while ($intern = $performers_result->fetch_assoc()):
                                    ?>
                                        <div class="flex items-center justify-between p-2">
                                            <div class="flex items-center">
                                                <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 text-xs rounded-full flex items-center justify-center mr-2">
                                                    <?= $rank++ ?>
                                                </span>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($intern['name']) ?></span>
                                            </div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400"><?= $intern['completed_tasks'] ?> tasks</span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- Recent Task Activity -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 lg:col-span-2">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Recent Task Activity</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <th class="text-left py-2 text-sm font-medium text-gray-600 dark:text-gray-300">Task</th>
                                                <th class="text-left py-2 text-sm font-medium text-gray-600 dark:text-gray-300">Assigned To</th>
                                                <th class="text-left py-2 text-sm font-medium text-gray-600 dark:text-gray-300">Status</th>
                                                <th class="text-left py-2 text-sm font-medium text-gray-600 dark:text-gray-300">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_activity = $conn->prepare("SELECT t.title, t.status, t.due_date, u.name as assign_to 
                                                              FROM tasks t 
                                                              JOIN users u ON t.assign_to = u.id 
                                                              WHERE t.created_by = ? 
                                                              ORDER BY t.updated_at DESC 
                                                              LIMIT 5");
                                            $recent_activity->bind_param("i", $user_id);
                                            $recent_activity->execute();
                                            $activity_result = $recent_activity->get_result();

                                            while ($task = $activity_result->fetch_assoc()):
                                            ?>
                                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                                    <td class="py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($task['title']) ?></td>
                                                    <td class="py-3 text-sm text-gray-600 dark:text-gray-300"><?= htmlspecialchars($task['assign_to']) ?></td>
                                                    <td class="py-3 text-sm">
                                                        <span class="px-2 py-1 rounded-full text-xs <?=
                                                                                                    $task['status'] == 'complete' ? 'bg-green-100 text-green-800' : ($task['status'] == 'working' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                                                                                                    ?>">
                                                            <?= ucfirst($task['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('M d, Y', strtotime($task['due_date'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($user_role == 3): ?>
                    <div class="mt-8">
                        <button class="open-modal bg-indigo-600 text-white px-4 py-2 rounded-lg m-2"
                            data-modal="create-task-modal">Create New Task</button>
                        <!-- Your Generate Tasks Table -->
                        <div
                            class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden transition-all duration-300 border border-gray-100 dark:border-gray-700">
                            <div
                                class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Your Generated Tasks</h2>
                            </div>
                            <div class="overflow-x-auto p-4">
                                <table id="tasksTable"
                                    class="min-w-full">
                                    <thead class="bg-indigo-200 dark:bg-indigo-500">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Id#
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Sr#
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Title
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Assign To
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Status
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Assign On
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white text-black dark:bg-gray-800 dark:text-white text-xs">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
            <?php if ($_SESSION['user_role'] == 3): ?>
                <!-- Create Task Modal -->
                <div id="create-task-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
                    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[3%] p-6 rounded-lg w-11/12 max-w-2xl relative max-h-[90vh] flex flex-col">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-5 h-6 absolute top-2 right-2 cursor-pointer close-btn">
                            <path fill-rule="evenodd"
                                d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-xl font-bold mb-4 pb-2 border-b border-gray-100 dark:border-gray-600">Create New Task</h2>

                        <div class="modal-body flex-1 overflow-y-auto custom-scrollbar px-2" style="overflow-y: auto !important;">
                            <!-- Inside create-task-modal -->
                            <form id="create-task" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Title:</label>
                                    <input type="text" id="title" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700" placeholder="Title here">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Technology:</label>
                                    <select id="technology_id" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                        <option value="">Select Technology</option>
                                        <?php
                                        $stmt = $conn->query("SELECT id, name FROM technologies ORDER BY name");
                                        while ($tech = $stmt->fetch_assoc()) {
                                            echo "<option value='{$tech['id']}'>{$tech['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Due Date:</label>
                                    <input type="date" id="due_date" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Assign To:</label>
                                    <select id="user_id" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                        <option value="">First select technology</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Description:</label>
                                    <div id="create-description-editor" class="min-h-[150px] dark:bg-gray-700 bg-gray-100"></div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer flex justify-end gap-3 pt-4 border-t mt-4">
                            <button type="button" class="close-modal px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-500">Cancel</button>
                            <button type="submit" form="create-task" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">Create Task</button>
                        </div>
                    </div>
                </div>

                <!-- Edit Task Modal -->
                <div id="edit-task-modal" class="modal hidden fixed inset-0 z-50 w-full h-full bg-black bg-opacity-50 backdrop-blur-sm">
                    <div class="animate-fadeIn modal-content bg-white dark:bg-gray-800 text-gray-800 dark:text-white mx-auto mt-[3%] p-6 rounded-lg w-11/12 max-w-4xl relative max-h-[90vh] flex flex-col">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-5 h-6 absolute top-2 right-2 cursor-pointer close-btn">
                            <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-xl font-bold mb-4 pb-2 border-b">Edit Task</h2>

                        <div class="modal-body flex-1 overflow-y-auto custom-scrollbar px-2" style="overflow-y: auto !important;">
                            <form id="edit-task" class="space-y-4">
                                <input type="hidden" id="edit_task_id">

                                <div>
                                    <label class="block text-sm font-medium mb-2">Title:</label>
                                    <input type="text" id="edit_title" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-2">Technology:</label>
                                        <select id="edit_technology_id" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                            <option value="">Select Technology</option>
                                            <?php
                                            $techs = $conn->query("SELECT id, name FROM technologies ORDER BY name");
                                            while ($t = $techs->fetch_assoc()) {
                                                echo "<option value='{$t['id']}'>{$t['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Due Date:</label>
                                        <input type="date" id="edit_due_date" class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Assign To:</label>
                                    <select id="edit_user_id" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                        <option value="">First select technology</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Description:</label>
                                    <div id="edit-description-editor" class="min-h-[200px]"></div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer flex justify-end gap-3 pt-4 border-t">
                            <button type="button" class="close-modal px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-500">Cancel</button>
                            <button type="submit" form="edit-task" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">Update Task</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- View Task Modal -->
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
                    <!-- <p class="mb-4 text-gray-600 dark:text-gray-300"></p> -->
                    <div class="modal-body max-h-[400px] overflow-y-auto custom-scrollbar" style="overflow-y: auto !important;">
                        <div class="view-data"></div>
                        <div class="grid grid-cols-1">
                            <div class="hidden border-t border-gray-200 dark:border-gray-600" id="time-logs">
                                <div class="flex justify-between items-center px-4">
                                    <h4 class="py-1 font-extrabold text-2xl text-center">Time Logs</h4>
                                    <span id="total-time">Total Time: </span>
                                </div>
                                <div class="container mx-auto p-4">
                                    <div class="max-h-[200px] overflow-y-auto custom-scrollbar mb-3 shadow-md rounded-lg">
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
                        </div>
                    </div>
                    <div class="modal-footer pt-2 view-urls">
                        <button type="button" class="close-modal px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-500">Close</button>
                    </div>
                </div>
            </div>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>
    
    <?php include_once "./include/footerLinks.php"; ?>

    <!-- Add Chart.js -->
    <script src="./assets/js/libs/chart.js"></script>
    <script>
        // Modern chart initialization with enhanced styling - COMPLETE FIXED VERSION
        document.addEventListener('DOMContentLoaded', function() {
            const userRole = <?= $user_role ?>;

            if (userRole === 1) {
                initializeAdminCharts();
            } else if (userRole === 2) {
                initializeInternCharts();
                loadInternPerformance();
            } else if (userRole === 3) {
                initializeSupervisorCharts();
                loadSupervisorStats();
            }
        });

        // Helper function to get theme colors
        function getThemeColors() {
            const isDarkMode = localStorage.getItem('darkMode');
            return {
                isDarkMode,
                textColor: (isDarkMode === 'disabled') ? '#374151' : '#fff',
                gridColor: (isDarkMode === 'disabled') ? '#E5E7EB' : '#374151',
                backgroundColor: (isDarkMode === 'disabled') ? '#fff' : '#1F2937'
            };
        }
        // Modern Admin Charts with Enhanced Styling - COMPLETE FIX
        function initializeAdminCharts() {
            const {
                isDarkMode,
                textColor,
                gridColor,
                backgroundColor
            } = getThemeColors();

            // Task Status Distribution - Modern Donut Chart
            fetch('controller/dashboard.php?action=admin_task_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
                        const taskStatusChart = new Chart(taskStatusCtx, {
                            type: 'doughnut',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    data: data.values,
                                    backgroundColor: [
                                        '#10B981', // Completed - Green
                                        '#F59E0B', // Working - Yellow
                                        '#EF4444', // Pending - Red
                                        '#6B7280' // Overdue - Gray
                                    ],
                                    borderWidth: 0,
                                    borderRadius: 8,
                                    spacing: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1,
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.label || '';
                                                const value = context.raw || 0;
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = Math.round((value / total) * 100);
                                                return `${label}: ${value} (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            },
                            plugins: [{
                                afterDraw: function(chart) {
                                    const ctx = chart.ctx;
                                    const width = chart.width;
                                    const height = chart.height;
                                    ctx.save();
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.font = 'bold 24px sans-serif';
                                    ctx.fillStyle = textColor;

                                    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    ctx.fillText(total, width / 2, height / 2);

                                    ctx.font = '12px sans-serif';
                                    ctx.fillStyle = isDarkMode ? '#9CA3AF' : '#6B7280';
                                    ctx.fillText('Total Tasks', width / 2, height / 2 + 24);
                                    ctx.restore();
                                }
                            }]
                        });

                        // Update legend
                        const legendContainer = document.getElementById('taskStatusLegend');
                        if (legendContainer) {
                            legendContainer.innerHTML = '';
                            data.labels.forEach((label, index) => {
                                const value = data.values[index];
                                const total = data.values.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);

                                const legendItem = document.createElement('div');
                                legendItem.className = 'flex items-center justify-between';
                                legendItem.innerHTML = `
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded mr-2" style="background-color: ${taskStatusChart.data.datasets[0].backgroundColor[index]}"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">${label}</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-800 dark:text-white">${value}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">${percentage}%</div>
                            </div>
                        `;
                                legendContainer.appendChild(legendItem);
                            });
                        }
                    }
                });

            // Monthly Task Trends - Modern Line Chart with Enhanced Features
            fetch('controller/dashboard.php?action=admin_monthly_trends')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');

                        // Create gradient for the chart
                        const gradient = monthlyTrendsCtx.createLinearGradient(0, 0, 0, 400);
                        if (isDarkMode) {
                            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
                            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');
                        } else {
                            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
                            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');
                        }

                        const chart = new Chart(monthlyTrendsCtx, {
                            type: 'line',
                            data: {
                                labels: data.months,
                                datasets: [{
                                    label: 'Tasks Created',
                                    data: data.tasks,
                                    borderColor: '#3B82F6',
                                    backgroundColor: gradient,
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointBackgroundColor: '#3B82F6',
                                    pointBorderColor: backgroundColor,
                                    pointBorderWidth: 2,
                                    pointRadius: 6,
                                    pointHoverRadius: 8,
                                    pointHoverBackgroundColor: '#3B82F6',
                                    pointHoverBorderColor: backgroundColor,
                                    pointHoverBorderWidth: 3
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1,
                                        padding: 12,
                                        cornerRadius: 8,
                                        displayColors: false,
                                        callbacks: {
                                            label: function(context) {
                                                return `Tasks: ${context.parsed.y}`;
                                            },
                                            title: function(context) {
                                                return `Month: ${context[0].label}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor,
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: textColor,
                                            padding: 10,
                                            callback: function(value) {
                                                return value;
                                            }
                                        },
                                        // title: {
                                        //     display: true,
                                        //     text: 'Number of Tasks',
                                        //     color: textColor,
                                        //     font: {
                                        //         weight: 'normal'
                                        //     }
                                        // }
                                    },
                                    x: {
                                        grid: {
                                            color: gridColor,
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: textColor,
                                            padding: 10
                                        },
                                        // title: {
                                        //     display: true,
                                        //     text: 'Months',
                                        //     color: textColor,
                                        //     font: {
                                        //         weight: 'normal'
                                        //     }
                                        // }
                                    }
                                },
                                elements: {
                                    line: {
                                        tension: 0.4
                                    }
                                }
                            }
                        });
                        
                        // Add custom statistics below the chart
                        updateMonthlyTrendsStats(data.tasks, data.months);
                    }
                })
                .catch(error => {
                    console.error('Error loading monthly trends:', error);
                    const chartContainer = document.getElementById('monthlyTrendsChart');
                    if (chartContainer) {
                        chartContainer.innerHTML = `
                    <div class="flex items-center justify-center h-full text-red-500">
                        <svg class="w-8 h-8 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Failed to load chart data</span>
                    </div>
                `;
                    }
                });

            // User Role Distribution - Modern Pie Chart
            fetch('controller/dashboard.php?action=admin_role_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
                        const userRoleChart = new Chart(userRoleCtx, {
                            type: 'pie',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    data: data.values,
                                    backgroundColor: ['#EF4444', '#10B981', '#3B82F6'], // Admin, Intern, Supervisor
                                    borderWidth: 0,
                                    borderRadius: 8,
                                    spacing: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1
                                    }
                                }
                            }
                        });

                        // Update legend
                        const legendContainer = document.getElementById('userRoleLegend');
                        if (legendContainer) {
                            legendContainer.innerHTML = '';
                            data.labels.forEach((label, index) => {
                                const value = data.values[index];
                                const total = data.values.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);

                                const legendItem = document.createElement('div');
                                legendItem.className = 'flex flex-col items-center';
                                legendItem.innerHTML = `
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 rounded mr-2" style="background-color: ${userRoleChart.data.datasets[0].backgroundColor[index]}"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">${label}</span>
                            </div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white">${value}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${percentage}%</div>
                        `;
                                legendContainer.appendChild(legendItem);
                            });
                        }
                    }
                });

            // Technology-wise Task Distribution
            fetch('controller/dashboard.php?action=admin_tech_tasks')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const techTaskCtx = document.getElementById('techTaskChart').getContext('2d');
                        new Chart(techTaskCtx, {
                            type: 'bar',
                            data: {
                                labels: data.technologies,
                                datasets: [{
                                    label: 'Tasks',
                                    data: data.tasks,
                                    backgroundColor: [
                                        'rgba(59, 130, 246, 0.8)',
                                        'rgba(16, 185, 129, 0.8)',
                                        'rgba(139, 92, 246, 0.8)',
                                        'rgba(245, 158, 11, 0.8)',
                                        'rgba(239, 68, 68, 0.8)'
                                    ],
                                    borderColor: [
                                        'rgb(59, 130, 246)',
                                        'rgb(16, 185, 129)',
                                        'rgb(139, 92, 246)',
                                        'rgb(245, 158, 11)',
                                        'rgb(239, 68, 68)'
                                    ],
                                    borderWidth: 2,
                                    borderRadius: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
        }

        // COMPLETE FIXED Intern Charts
        function initializeInternCharts() {
            const {
                isDarkMode,
                textColor,
                gridColor,
                backgroundColor
            } = getThemeColors();

            // Task Progress Chart
            fetch('controller/dashboard.php?action=intern_monthly_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const internTaskCtx = document.getElementById('internTaskChart').getContext('2d');
                        new Chart(internTaskCtx, {
                            type: 'bar',
                            data: {
                                labels: data.months,
                                datasets: [{
                                    label: 'Tasks Completed',
                                    data: data.tasks,
                                    backgroundColor: '#3B82F6',
                                    borderColor: '#2563EB',
                                    borderWidth: 2,
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });
                    }
                });

            // Weekly Hours Chart
            fetch('controller/dashboard.php?action=intern_weekly_hours')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const weeklyHoursCtx = document.getElementById('weeklyHoursChart').getContext('2d');

                        // Create gradient
                        const gradient = weeklyHoursCtx.createLinearGradient(0, 0, 0, 300);
                        if (isDarkMode) {
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
                            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.05)');
                        } else {
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
                            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
                        }

                        new Chart(weeklyHoursCtx, {
                            type: 'line',
                            data: {
                                labels: data.days,
                                datasets: [{
                                    label: 'Hours Worked',
                                    data: data.hours,
                                    borderColor: '#10B981',
                                    backgroundColor: gradient,
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointBackgroundColor: '#10B981',
                                    pointBorderColor: backgroundColor,
                                    pointBorderWidth: 2,
                                    pointRadius: 5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
        }

        // COMPLETE FIXED Supervisor Charts
        function initializeSupervisorCharts() {
            const {
                isDarkMode,
                textColor,
                gridColor,
                backgroundColor
            } = getThemeColors();

            // Team Performance
            fetch('controller/dashboard.php?action=supervisor_team_performance')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const teamPerformanceCtx = document.getElementById('teamPerformanceChart').getContext('2d');
                        new Chart(teamPerformanceCtx, {
                            type: 'radar',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Team Average',
                                    data: data.values,
                                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                                    borderColor: '#3B82F6',
                                    pointBackgroundColor: '#3B82F6',
                                    pointBorderColor: backgroundColor,
                                    pointBorderWidth: 2,
                                    pointRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    r: {
                                        angleLines: {
                                            color: gridColor
                                        },
                                        grid: {
                                            color: gridColor
                                        },
                                        pointLabels: {
                                            color: textColor,
                                            font: {
                                                size: 11
                                            }
                                        },
                                        ticks: {
                                            color: textColor,
                                            backdropColor: 'transparent'
                                        }
                                    }
                                }
                            }
                        });
                    }
                });

            // Task Status Overview
            fetch('controller/dashboard.php?action=supervisor_task_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const supervisorTaskCtx = document.getElementById('supervisorTaskChart').getContext('2d');
                        new Chart(supervisorTaskCtx, {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Tasks',
                                    data: data.values,
                                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280'],
                                    borderWidth: 0,
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: backgroundColor,
                                        titleColor: textColor,
                                        bodyColor: textColor,
                                        borderColor: gridColor,
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
        }

        // Function to update monthly trends statistics
        function updateMonthlyTrendsStats(tasks, months) {
            const statsContainer = document.getElementById('monthlyTrendsStats');

            if (!statsContainer) return;

            const totalTasks = tasks.reduce((sum, value) => sum + value, 0);
            const averageTasks = Math.round(totalTasks / tasks.length);
            const peakValue = Math.max(...tasks);
            const peakIndex = tasks.indexOf(peakValue);
            const peakMonth = months;

            // Calculate growth rate (current month vs previous month)
            const currentMonth = tasks[tasks.length - 1];
            const prevMonth = tasks[tasks.length - 2] || 0;
            const growthRate = prevMonth > 0 ?
                Math.round(((currentMonth - prevMonth) / prevMonth) * 100) : 0;

            statsContainer.innerHTML = `
        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${totalTasks}</div>
            <div class="text-sm text-blue-600 dark:text-blue-300">Total Tasks</div>
        </div>
        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">${averageTasks}</div>
            <div class="text-sm text-green-600 dark:text-green-300">Average/Month</div>
        </div>
        <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">${peakValue}</div>
            <div class="text-sm text-purple-600 dark:text-purple-300">Peak (${peakMonth})</div>
        </div>
        <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <div class="text-2xl font-bold ${growthRate >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                ${growthRate >= 0 ? '+' : ''}${growthRate}%
            </div>
            <div class="text-sm text-orange-600 dark:text-orange-300">Growth Rate</div>
        </div>
    `;
        }

        // Load performance stats
        async function loadInternPerformance() {
            try {
                const response = await fetch('controller/dashboard.php?action=intern_stats');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('completionRate').textContent = data.completion_rate + '%';
                    document.getElementById('avgCompletionTime').textContent = data.avg_completion_time + 'd';
                    document.getElementById('totalHours').textContent = data.total_hours + 'h';
                }
            } catch (error) {
                console.error('Error loading intern performance:', error);
            }
        }

        // Load supervisor stats
        async function loadSupervisorStats() {
            try {
                const response = await fetch('controller/dashboard.php?action=supervisor_stats');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('supervisorCompletionRate').textContent = data.completion_rate + '%';
                }
            } catch (error) {
                console.error('Error loading supervisor stats:', error);
            }
        }
    </script>
    <script>
        // Load users when technology is selected
        document.getElementById('technology_id').addEventListener('change', async function() {
            const techId = this.value;
            const userSelect = document.getElementById('user_id');

            userSelect.innerHTML = '<option value="">Loading...</option>';

            if (!techId) {
                userSelect.innerHTML = '<option value="">First select technology</option>';
                return;
            }

            try {
                const response = await fetch('controller/get_users_by_tech.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tech_id: techId
                    })
                });

                const result = await response.json();

                userSelect.innerHTML = '<option value="">Select User</option>';

                if (result.success && result.users.length > 0) {
                    result.users.forEach(user => {
                        const opt = new Option(user.name, user.id);
                        userSelect.add(opt);
                    });
                } else {
                    userSelect.innerHTML = '<option value="">No users found</option>';
                }
            } catch (err) {
                console.error(err);
                userSelect.innerHTML = '<option value="">Error loading users</option>';
            }
        });
    </script>
    <script>
        // Add these variables at the top
        let createEditor, editEditor;
        let dataTable;
        let liveInterval;

        document.getElementById('create-task').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Get form values
            const title = document.getElementById('title').value.trim();
            const description = createEditor?.getData?.().trim() || '';
            const user_id = document.getElementById('user_id').value;
            const due_date = document.getElementById('due_date').value;
            const technology_id = document.getElementById('technology_id').value;

            // Validation
            if (!title) {
                alert('Please enter a task title');
                return;
            }
            if (!description) {
                alert('Please enter a task description');
                return;
            }
            if (!technology_id) {
                alert('Please select a technology');
                return;
            }
            if (!user_id) {
                alert('Please select a user to assign');
                return;
            }
            if (!due_date) {
                alert('Please select a due date');
                return;
            }

            try {
                const response = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create',
                        title,
                        description,
                        user_id: parseInt(user_id), // send as integer
                        due_date // YYYY-MM-DD
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Close modal
                    document.querySelector('#create-task-modal .close-modal')?.click();

                    // Refresh task list
                    await getTasks();

                    // Success toast
                    showToast('success', result.message || 'Task created successfully!');

                    // Reset form
                    this.reset();
                    createEditor.setData(''); // Clear CKEditor

                    // Reset user dropdown (because it depends on technology)
                    const userSelect = document.getElementById('user_id');
                    userSelect.innerHTML = '<option value="">First select technology</option>';

                    // Optional: reset technology dropdown too (or keep it)
                    // document.getElementById('technology_id').selectedIndex = 0;

                } else {
                    showToast('error', result.message || 'Failed to create task');
                }
            } catch (error) {
                console.error('Create task error:', error);
                showToast('error', 'Network error. Please try again.');
            }
        });
        // Add this once, outside any function
        document.getElementById('technology_id').addEventListener('change', function() {
            const userSelect = document.getElementById('user_id');
            if (!this.value) {
                userSelect.innerHTML = '<option value="">First select technology</option>';
            }
        });
        document.addEventListener('DOMContentLoaded', async function() {
            dataTable = $('#tasksTable').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: false
            });

            // Initialize CKEditor for create modal
            try {
                createEditor = await ClassicEditor
                    .create(document.querySelector('#create-description-editor'), {
                        toolbar: [
                            'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'
                        ],
                        height: '150px',
                        minHeight: '150px'
                    });
            } catch (error) {
                console.error('Error initializing create editor:', error);
                // Fallback to textarea if CKEditor fails
                document.querySelector('#create-description-editor').innerHTML = '<textarea id="description-fallback" rows="4" class="text-sm w-full p-2 rounded-lg border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Task description"></textarea>';
            }

            // Initialize CKEditor for edit modal (empty initially)
            try {
                editEditor = await ClassicEditor
                    .create(document.querySelector('#edit-description-editor'), {
                        toolbar: [
                            'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'
                        ],
                        height: '200px',
                        minHeight: '200px'
                    });
            } catch (error) {
                console.error('Error initializing edit editor:', error);
                // Fallback to textarea if CKEditor fails
                document.querySelector('#edit-description-editor').innerHTML = '<textarea id="edit-description-fallback" rows="6" class="text-sm w-full p-2 rounded-lg border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Task description"></textarea>';
            }

            await getTasks();
            await viewTask();
            await editTask();
            await allTasks();
            await completeTasks();
            await workingTasks();
            await pendingTasks();
        });

        async function getTasks() {
            try {
                const response = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'get'
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
                        if (statusOrder[a.status] !== statusOrder[b.status]) {
                            return statusOrder[a.status] - statusOrder[b.status];
                        }
                        return new Date(b.created_at) - new Date(a.created_at);
                    });

                    dataTable.clear();
                    let count = 1;
                    const currentUserName = "<?php echo $_SESSION['user_name'] ?>";

                    result.data.forEach(task => {
                        dataTable.row.add([
                            count,
                            task.id,
                            task.title,
                            task.assign_to = task.assign_to == currentUserName ? 'Me' : task.assign_to,

                            getStatusBadge((task.status === 'complete') ?
                                (task.approval_status === 2 ? 'Declined' : task.status) :
                                task.status),
                            formatDateTime(task.created_at),
                            `
                        ${task.status == 'complete' ||task.status == 'working' ? '' : `
                            <button class="open-modal text-blue-600 me-2"
                            data-modal="edit-task-modal"
                            data-id="${task.id}"
                            data-title="${task.title}"
                            data-description="${task.description}"
                            data-assign-id="${task.assign_id}"
                            data-tech-id="${task.tech_id || ''}"
                            data-due-date="${task.due_date || ''}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4 22H8M20 22H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M13.8881 3.66293L14.6296 2.92142C15.8581 1.69286 17.85 1.69286 19.0786 2.92142C20.3071 4.14999 20.3071 6.14188 19.0786 7.37044L18.3371 8.11195M13.8881 3.66293C13.8881 3.66293 13.9807 5.23862 15.3711 6.62894C16.7614 8.01926 18.3371 8.11195 18.3371 8.11195M13.8881 3.66293L7.07106 10.4799C6.60933 10.9416 6.37846 11.1725 6.17992 11.4271C5.94571 11.7273 5.74491 12.0522 5.58107 12.396C5.44219 12.6874 5.33894 12.9972 5.13245 13.6167L4.25745 16.2417M18.3371 8.11195L14.9286 11.5204M11.5201 14.9289C11.0584 15.3907 10.8275 15.6215 10.5729 15.8201C10.2727 16.0543 9.94775 16.2551 9.60398 16.4189C9.31256 16.5578 9.00282 16.6611 8.38334 16.8675L5.75834 17.7426M5.75834 17.7426L5.11667 17.9564C4.81182 18.0581 4.47573 17.9787 4.2485 17.7515C4.02128 17.5243 3.94194 17.1882 4.04356 16.8833L4.25745 16.2417M5.75834 17.7426L4.25745 16.2417" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>
                            `}
                            <button class="open-modal text-amber-600" 
                            data-modal="view-task-modal" id="view-task" 
                                data-id="${task.id}" 
                                data-title="${task.title}" 
                                data-description="${task.description}" 
                                data-assign="${task.assign_to == currentUserName? 'Me': task.assign_to}" 
                                data-status="${task.status}" 
                                data-created="${formatDateTime(task.created_at)}"
                                data-started="${(task.started_at == null) ? 'Not started Yet' : formatDateTime(task.started_at)}"
                                data-completed="${(task.completed_at == null) ? null : formatDateTime(task.completed_at)}"
                                data-gitrepo="${task.github_repo??''}"
                                data-liveurl="${task.live_url??''}"
                                data-additionalmsg="${task.additional_notes??''}">
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 4.45962C9.91153 4.16968 10.9104 4 12 4C16.1819 4 19.028 6.49956 20.7251 8.70433C21.575 9.80853 22 10.3606 22 12C22 13.6394 21.575 14.1915 20.7251 15.2957C19.028 17.5004 16.1819 20 12 20C7.81811 20 4.97196 17.5004 3.27489 15.2957C2.42496 14.1915 2 13.6394 2 12C2 10.3606 2.42496 9.80853 3.27489 8.70433C3.75612 8.07914 4.32973 7.43025 5 6.82137" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="1.5"></path> </g></svg>
                            </button>`
                        ]);
                        count++
                    });
                    dataTable.draw();
                } else {
                    showToast('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                // alert('Something went wrong!');
            }
        }

        function viewTask() {
            document.getElementById("tasksTable").addEventListener("click", async function(e) {
                const button = e.target.closest(".open-modal[data-modal='view-task-modal']");
                if (!button) return;

                const modal = document.getElementById("view-task-modal");
                const tbody = document.getElementById('logs-body');
                const totalTimeEl = document.getElementById('total-time');

                // Clear previous data
                tbody.innerHTML = "";
                totalTimeEl.innerText = "Total Time: 00:00:00";
                document.getElementById('time-logs').classList.add('hidden');

                let totalTime = 0;
                clearInterval(liveInterval);

                const task_id = button.dataset.id;
                const title = button.dataset.title;
                const description = button.dataset.description;
                const assignTo = button.dataset.assign;
                const status = button.dataset.status;
                const created = button.dataset.created;
                const started_at = button.dataset.started;
                const completed_at = button.dataset.completed;
                const github_repo = button.dataset.gitrepo;
                const live_url = button.dataset.liveurl;
                const additional_msg = button.dataset.additionalmsg;

                const viewUrls = document.querySelector('.view-urls');
                if (github_repo !== '' || github_repo !== null) {
                    viewUrls.innerHTML = `
                                    <button type="button" class="close-modal px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-500">Close</button>
                                    ${github_repo ? `<a href="${github_repo}" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">View Github Repo</a>` : ''}
                                    ${live_url ? `<a href="${live_url}" target="_blank" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-500">View Live Web</a>` : ''}
                                    `;
                }
                // Update the view data
                modal.querySelector(".view-data").innerHTML = `
            <div class="grid grid-cols-2">
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Title:</label>
                    <span class="ms-2">${title}</span>
                </div>
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Assigned To:</label>
                    <span class="ms-2">${assignTo == "<?php echo $_SESSION['user_name'] ?>"?'me':assignTo}</span>
                </div>
            </div>
            <div class="grid grid-cols-2">
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Status:</label>
                    <span class="ms-2">${getStatusBadge(status)}</span>
                </div>
                <div class="mb-3 flex items-center col-span-2">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Created At:</label>
                    <span class="ms-2">${created}</span>
                </div>
            </div>
            <div class="">
                <div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Start Time:</label>
                    <span class="ms-2">${started_at}</span>
                </div>
            ${(status == 'complete')
                ? `<div class="mb-3 flex items-center">
                    <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Complete Time:</label>
                    <span class="ms-2">${completed_at}</span>
                </div>` 
                : ''}
            </div>
            ${(additional_msg && additional_msg.trim() !== '')?`<div  class="mb-3">
            <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Student Message:</label>
            <span class="block p-2 bg-gray-50 dark:bg-gray-600 text-black dark:text-gray-200">${additional_msg}</span>
            </div>`:''}
            <div class="mb-3">
                <label class="font-extrabold text-sm text-gray-700 dark:text-gray-300">Description:</label>
                <span class="block p-2 border-l-4 border-indigo-400 dark:border-indigo-300 bg-indigo-100 dark:bg-indigo-500 text-indigo-600 dark:text-indigo-200">${description}</span>
            </div>
        `;

                try {
                    const response = await fetch('controller/timeLog.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'get',
                            task_id: task_id
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        if (result.logs.length > 0) {
                            document.getElementById('time-logs').classList.remove('hidden');

                            result.logs.forEach(log => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:bg-gray-600 bg-white transition-colors";

                                let startTd = `<td class="py-3 px-6 text-sm">${formatDateTime(log.started_at)}</td>`;

                                if (!log.stopped_at) {
                                    let startTime = new Date(log.started_at).getTime();
                                    let stopTd = `<td class="py-3 px-6 text-sm">--</td>`;
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
                                <td class="py-3 px-6 text-sm">${formatDateTime(log.stopped_at)}</td>
                                <td class="py-3 px-6 text-sm">${formatDuration(log.duration)}</td>
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

                modal.classList.remove("hidden");
                modal.querySelectorAll(".close-btn, .close-modal").forEach(closeBtn => {
                    closeBtn.addEventListener("click", () => {
                        clearInterval(liveInterval);
                        modal.classList.add("hidden");
                    });
                });
            });
        }

        function editTask() {
            document.getElementById('edit_technology_id').addEventListener('change', async function() {
                const techId = this.value;
                const userSelect = document.getElementById('edit_user_id');

                if (!techId) {
                    userSelect.innerHTML = '<option value="">First select technology</option>';
                    return;
                }

                userSelect.innerHTML = '<option value="">Loading...</option>';
                try {
                    const res = await fetch('controller/get_users_by_tech.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            tech_id: techId
                        })
                    });
                    const result = await res.json();

                    userSelect.innerHTML = '<option value="">Select User</option>';
                    if (result.success) {
                        result.users.forEach(u => userSelect.add(new Option(u.name, u.id)));
                    } else {
                        userSelect.innerHTML = '<option value="">No users found</option>';
                    }
                } catch (err) {
                    userSelect.innerHTML = '<option value="">Error loading users</option>';
                }
            });
        }
        // Handle edit button click to populate the modal
        document.addEventListener('click', async function(e) {
            const editButton = e.target.closest('.open-modal[data-modal="edit-task-modal"]');
            if (!editButton) return;

            const taskId = editButton.dataset.id;
            const taskTitle = editButton.dataset.title;
            const taskDescription = editButton.dataset.description;
            const taskAssignId = editButton.dataset.assignId;
            const taskTechId = editButton.dataset.techId;
            const taskDueDate = editButton.dataset.dueDate;

            // Populate basic fields
            document.getElementById('edit_task_id').value = taskId;
            document.getElementById('edit_title').value = taskTitle;
            document.getElementById('edit_due_date').value = taskDueDate;

            // Set technology and trigger change to load users
            const techSelect = document.getElementById('edit_technology_id');
            techSelect.value = taskTechId || '';

            // Trigger change event to load users for this technology
            if (taskTechId) {
                const event = new Event('change');
                techSelect.dispatchEvent(event);

                // Wait a bit for users to load, then set the assigned user
                setTimeout(() => {
                    document.getElementById('edit_user_id').value = taskAssignId;
                }, 500);
            }

            // Set description in editor
            if (editEditor && editEditor.setData) {
                editEditor.setData(taskDescription);
            } else {
                // Fallback for textarea
                const fallbackTextarea = document.getElementById('edit-description-fallback');
                if (fallbackTextarea) {
                    fallbackTextarea.value = taskDescription;
                }
            }
        });
        // Handle technology change in edit modal
        document.getElementById('edit_technology_id').addEventListener('change', async function() {
            const techId = this.value;
            const userSelect = document.getElementById('edit_user_id');
            userSelect.innerHTML = '<option value="">Loading...</option>';

            if (!techId) return;

            const res = await fetch('controller/get_users_by_tech.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tech_id: techId
                })
            });
            const result = await res.json();

            userSelect.innerHTML = '<option value="">Select User</option>';
            if (result.success && result.users.length > 0) {
                result.users.forEach(u => userSelect.add(new Option(u.name, u.id)));
            } else {
                userSelect.innerHTML = '<option value="">No users found</option>';
            }
        });

        // Submit edit form
        document.getElementById('edit-task').addEventListener('submit', async function(e) {
            e.preventDefault();

            const id = document.getElementById('edit_task_id').value;
            const title = document.getElementById('edit_title').value.trim();
            const description = editEditor.getData().trim();
            const user_id = document.getElementById('edit_user_id').value;
            const due_date = document.getElementById('edit_due_date').value;

            if (!title || !description || !user_id) {
                alert('Please fill all required fields');
                return;
            }

            try {
                const res = await fetch('controller/task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update',
                        id,
                        title,
                        description,
                        user_id,
                        due_date: due_date || null
                    })
                });

                const result = await res.json();

                if (result.success) {
                    document.querySelector('#edit-task-modal .close-modal').click();
                    await getTasks();
                    showToast('success', 'Task updated successfully');
                } else {
                    showToast('error', result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Update failed');
            }
        });

        function allTasks() {
            document.getElementById("allTasks").addEventListener("click", async function(e) {
                const button = e.target.closest(".open-modal[data-modal='all-tasks-modal']");
                if (!button) return;

                const modal = document.getElementById("all-tasks-modal");

                const tbody = document.getElementById('all-tasks-body');
                try {
                    const response = await fetch('controller/task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'getAllTasks'
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        tbody.innerHTML = "";
                        let count = 1;

                        if (result.data.length === 0) {
                            let row = document.createElement("tr");
                            row.innerHTML = `
                                <td colspan="4" class="py-3 px-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                    No Task Found
                                </td>
                            `;
                            tbody.appendChild(row);
                        } else {
                            result.data.forEach(log => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors";

                                row.innerHTML = `
                                        <td class="py-3 px-6 text-sm">${count}</td>
                                        <td class="py-3 px-6 text-sm">${log.title}</td>
                                        <td class="py-3 px-6 text-sm">${(log.assign_to == "<?php echo $_SESSION['user_name'] ?>") ? 'me' :                      log.assign_to}</td>
                                        <td class="py-3 px-6 text-sm">${getStatusBadge(log.status)}</td>
                                    `;
                                count++;
                                tbody.appendChild(row);
                            });
                        }
                    }

                } catch (error) {
                    console.error("Error:", error);
                }

                if (modal) {
                    modal.classList.remove("hidden");

                    modal.querySelectorAll(".close-btn, .close-modal").forEach(closeBtn => {
                        closeBtn.addEventListener("click", () => {
                            if (typeof liveInterval !== "undefined") {
                                clearInterval(liveInterval);
                            }
                            modal.classList.add("hidden");
                        });
                    });
                }
            });
        }

        function completeTasks() {
            document.getElementById("completeTasks").addEventListener("click", async function(e) {
                const button = e.target.closest(".open-modal[data-modal='complete-tasks-modal']");
                if (!button) return;

                const modal = document.getElementById("complete-tasks-modal"); // match with HTML

                const tbody = document.getElementById('complete-tasks-body');
                try {
                    const response = await fetch('controller/task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'getCompleteTasks'
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        tbody.innerHTML = "";
                        let count = 1;

                        if (result.data.length === 0) {
                            let row = document.createElement("tr");
                            row.innerHTML = `
                                <td colspan="4" class="py-3 px-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                    No Completed Task
                                </td>
                            `;
                            tbody.appendChild(row);
                        } else {
                            result.data.forEach(log => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors";

                                row.innerHTML = `
                                        <td class="py-3 px-6 text-sm">${count}</td>
                                        <td class="py-3 px-6 text-sm">${log.title}</td>
                                        <td class="py-3 px-6 text-sm">${(log.assign_to == "<?php echo $_SESSION['user_name'] ?>") ? 'me' :                      log.assign_to}</td>
                                        <td class="py-3 px-6 text-sm">${getStatusBadge(log.status)}</td>
                                    `;
                                count++;
                                tbody.appendChild(row);
                            });
                        }
                    }

                } catch (error) {
                    console.error("Error:", error);
                }

                if (modal) {
                    modal.classList.remove("hidden");

                    modal.querySelectorAll(".close-btn, .close-modal").forEach(closeBtn => {
                        closeBtn.addEventListener("click", () => {
                            if (typeof liveInterval !== "undefined") {
                                clearInterval(liveInterval);
                            }
                            modal.classList.add("hidden");
                        });
                    });
                }
            });
        }

        function workingTasks() {
            document.getElementById("workingTasks").addEventListener("click", async function(e) {
                const button = e.target.closest(".open-modal[data-modal='working-tasks-modal']");
                if (!button) return;

                const modal = document.getElementById("working-tasks-modal");

                const tbody = document.getElementById('working-tasks-body');
                try {
                    const response = await fetch('controller/task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'getWorkingTasks'
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        tbody.innerHTML = "";
                        let count = 1;

                        if (result.data.length === 0) {
                            let row = document.createElement("tr");
                            row.innerHTML = `
                                <td colspan="4" class="py-3 px-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                    No Working Task
                                </td>
                            `;
                            tbody.appendChild(row);
                        } else {
                            result.data.forEach(log => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors";

                                row.innerHTML = `
                                        <td class="py-3 px-6 text-sm">${count}</td>
                                        <td class="py-3 px-6 text-sm">${log.title}</td>
                                        <td class="py-3 px-6 text-sm">${(log.assign_to == "<?php echo $_SESSION['user_name'] ?>") ? 'me' :                      log.assign_to}</td>
                                        <td class="py-3 px-6 text-sm">${getStatusBadge(log.status)}</td>
                                    `;
                                count++;
                                tbody.appendChild(row);
                            });
                        }
                    }

                } catch (error) {
                    console.error("Error:", error);
                }

                if (modal) {
                    modal.classList.remove("hidden");

                    modal.querySelectorAll(".close-btn, .close-modal").forEach(closeBtn => {
                        closeBtn.addEventListener("click", () => {
                            if (typeof liveInterval !== "undefined") {
                                clearInterval(liveInterval);
                            }
                            modal.classList.add("hidden");
                        });
                    });
                }
            });
        }

        function pendingTasks() {
            document.getElementById("pendingTasks").addEventListener("click", async function(e) {
                const button = e.target.closest(".open-modal[data-modal='pending-tasks-modal']");
                if (!button) return;

                const modal = document.getElementById("pending-tasks-modal");

                const tbody = document.getElementById('pending-tasks-body');
                try {
                    const response = await fetch('controller/task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'getPendingTasks'
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        tbody.innerHTML = "";
                        let count = 1;

                        if (result.data.length === 0) {
                            let row = document.createElement("tr");
                            row.innerHTML = `
                                <td colspan="4" class="py-3 px-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                    No Pending Task
                                </td>
                            `;
                            tbody.appendChild(row);
                        } else {
                            result.data.forEach(log => {
                                let row = document.createElement("tr");
                                row.className = "hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors";

                                row.innerHTML = `
                                        <td class="py-3 px-6 text-sm">${count}</td>
                                        <td class="py-3 px-6 text-sm">${log.title}</td>
                                        <td class="py-3 px-6 text-sm">${(log.assign_to == "<?php echo $_SESSION['user_name'] ?>") ? 'me' :                      log.assign_to}</td>
                                        <td class="py-3 px-6 text-sm">${getStatusBadge(log.status)}</td>
                                    `;
                                count++;
                                tbody.appendChild(row);
                            });
                        }
                    }
                } catch (error) {
                    console.error("Error:", error);
                }

                if (modal) {
                    modal.classList.remove("hidden");

                    modal.querySelectorAll(".close-btn, .close-modal").forEach(closeBtn => {
                        closeBtn.addEventListener("click", () => {
                            if (typeof liveInterval !== "undefined") {
                                clearInterval(liveInterval);
                            }
                            modal.classList.add("hidden");
                        });
                    });
                }
            });
        }

        // Add this CSS function to fix modal scrolling
        function addModalScrollStyles() {
            const style = document.createElement('style');
            style.textContent = `
            .modal-content {
                display: flex;
                flex-direction: column;
                max-height: 85vh;
            }
            .modal-body {
                flex: 1;
                overflow-y: hidden;
                max-height: calc(85vh - 120px);
            }
            .modal-footer {
                flex-shrink: 0;
                padding-top: 1rem;
                border-top: 1px solid #e5e7eb;
                background: white;
                position: sticky;
                bottom: 0;
            }
            .dark .modal-footer {
                border-top-color: #4b5563;
                background: #1f2937;
            }
            .ck-editor__editable {
                max-height: 200px !important;
                overflow-y: auto !important;
            }
        `;
            document.head.appendChild(style);
        }

        // Call this function when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            addModalScrollStyles();
        });
    </script>
</body>

</html>