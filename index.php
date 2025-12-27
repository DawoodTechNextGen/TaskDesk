<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id'])) {
    header('location:'.BASE_URL.'login.php');
} else {
    include_once './include/connection.php';
}

// Get dynamic data based on user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role == 1) {
    // Admin data
    $total_users = $conn->query("SELECT COUNT(id) as total FROM users WHERE status = 1 AND user_role != 1")->fetch_assoc()['total'];
    $active_interns = $conn->query("SELECT COUNT(id) as total FROM users WHERE user_role = 2 AND status = 1")->fetch_assoc()['total'];
    $total_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks")->fetch_assoc()['total'];
    $total_tech = $conn->query("SELECT COUNT(id) as total FROM technologies")->fetch_assoc()['total'];
    // Count of new registrations (status = 'new')
    $new_registrations = $conn->query("SELECT COUNT(id) as total FROM registrations WHERE status = 'new'")->fetch_assoc()['total'];

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
        $managed_interns = $conn->prepare("SELECT COUNT(id) as total FROM users WHERE user_role = 2 AND status = 1");
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
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                            <!-- Total Users -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <div class="relative">
                                    <p class="text-blue-500 dark:text-blue-100 text-sm font-medium mb-2">Total Users</p>
                                    <h3 class="text-3xl font-bold mb-2 text-black dark:text-white"><?= $total_users ?></h3>
                                    <p class="text-blue-500 dark:text-blue-100 text-sm">Active members</p>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl text-white">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                                                <path d="M18 9C19.6569 9 21 7.88071 21 6.5C21 5.11929 19.6569 4 18 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M6 9C4.34315 9 3 7.88071 3 6.5C3 5.11929 4.34315 4 6 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M17.1973 15C17.7078 15.5883 18 16.2714 18 17C18 19.2091 15.3137 21 12 21C8.68629 21 6 19.2091 6 17C6 14.7909 8.68629 13 12 13C12.3407 13 12.6748 13.0189 13 13.0553" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M20 19C21.7542 18.6153 23 17.6411 23 16.5C23 15.3589 21.7542 14.3847 20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                <path d="M4 19C2.24575 18.6153 1 17.6411 1 16.5C1 15.3589 2.24575 14.3847 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            </g>
                                        </svg>
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

                            <!-- New Registrations -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <a href="registrations.php?status=new" class="block">
                                    <div class="relative">
                                        <p class="text-teal-500 dark:text-teal-100 text-sm font-medium mb-2">New Registrations</p>
                                        <h3 class="text-black dark:text-white text-3xl font-bold mb-2"><?= $new_registrations ?></h3>
                                        <p class="text-teal-500 dark:text-teal-100 text-sm">Recent signups (status: NEW)</p>
                                    </div>
                                </a>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2z" />
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
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Expire</p>
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
                                <div class="overflow-x-auto custom-scrollbar">
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
                                        <a href="supervisors.php" class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 text-blue-700 dark:text-blue-300 rounded-xl hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-800/50 dark:hover:to-blue-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3 text-white">
                                                    <svg width="22px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                        <g id="SVGRepo_iconCarrier">
                                                            <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                                                            <path d="M18 9C19.6569 9 21 7.88071 21 6.5C21 5.11929 19.6569 4 18 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path d="M6 9C4.34315 9 3 7.88071 3 6.5C3 5.11929 4.34315 4 6 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path d="M17.1973 15C17.7078 15.5883 18 16.2714 18 17C18 19.2091 15.3137 21 12 21C8.68629 21 6 19.2091 6 17C6 14.7909 8.68629 13 12 13C12.3407 13 12.6748 13.0189 13 13.0553" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path d="M20 19C21.7542 18.6153 23 17.6411 23 16.5C23 15.3589 21.7542 14.3847 20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path d="M4 19C2.24575 18.6153 1 17.6411 1 16.5C1 15.3589 2.24575 14.3847 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        </g>
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Manage Supervisors</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="tech.php" class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 text-green-700 dark:text-green-300 rounded-xl hover:from-green-100 hover:to-green-200 dark:hover:from-green-800/50 dark:hover:to-green-700/50 transition-all duration-200">
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
                                        <div class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-all duration-300 dark:bg-gray-700 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($task['title']) ?></h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Assigned by: <?= htmlspecialchars($task['assigned_by']) ?></p>
                                            </div>
                                            <?php
                                            $currentDate = date('Y-m-d');
                                            $dueDate = $task['due_date'];

                                            if (strtotime($dueDate) < strtotime($currentDate)) {
                                                $statusText = 'Expired';
                                                $statusClass = 'bg-red-100 text-red-800';
                                            } elseif ($task['status'] === 'pending') {
                                                $statusText = 'Pending';
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                            } else {
                                                // For complete, working, or any other status
                                                $statusText = ucfirst($task['status']);
                                                $statusClass = $task['status'] == 'complete' ? 'bg-green-100 text-green-800' : ($task['status'] == 'working' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                            }
                                            ?>

                                            <div class="text-right">
                                                <span class="inline-block px-2 py-1 text-xs rounded-full <?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                                <p class="text-xs text-red-500 dark:text-gray-400 mt-1">Due: <?= date('M d', strtotime($dueDate)) ?></p>
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
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Expire Tasks</p>
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
                                    $top_performers = $conn->prepare("SELECT 
                                                                        u.name,
                                                                        COUNT(t.id) AS completed_tasks,
                                                                        tech.name AS tech
                                                                    FROM users u
                                                                    LEFT JOIN tasks t 
                                                                        ON u.id = t.assign_to 
                                                                        AND t.status = 'complete'
                                                                    LEFT JOIN technologies tech 
                                                                        ON tech.id = u.tech_id
                                                                    WHERE u.user_role = 2
                                                                    GROUP BY u.id
                                                                    HAVING COUNT(t.id) > 0
                                                                    ORDER BY completed_tasks DESC
                                                                    LIMIT 5
                                                                    ");
                                    $top_performers->execute();
                                    $performers_result = $top_performers->get_result();

                                    $rank = 1;
                                    if ($performers_result->num_rows > 0) {
                                        while ($intern = $performers_result->fetch_assoc()):
                                    ?>
                                            <div>
                                                <div class="tooltip flex items-center justify-between p-2 bg-gray-100 dark:bg-gray-700 rounded-lg" data-tooltip="<?= $intern['completed_tasks'] ?> completed tasks">
                                                    <div class="flex items-center">
                                                        <span class="w-4 h-4 bg-blue-500 text-white text-xs rounded-full flex items-center justify-center mr-3">
                                                            <?= $rank++ ?>
                                                        </span>
                                                        <span class="text-sm font-medium dark:text-gray-50 text-gray-800"><?= $intern['name'] ?></span>
                                                    </div>
                                                    <span class="text-xs text-gray-400"><?= $intern['tech'] ?></span>
                                                </div>
                                            </div>

                                    <?php endwhile;
                                    } else {
                                        echo "<p class='text-red-600 dark:text-red-300'>No performers found.</p>";
                                    } ?>
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
                                                        <span class="px-2 py-1 rounded-full text-xs <?= ($task['due_date'] < date('Y-m-d') && $task['status'] !== 'complete') ? 'bg-red-100 text-red-800' : ($task['status'] === 'complete' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                            <?= ucfirst(($task['due_date'] < date('Y-m-d') && $task['status'] !== 'complete') ? 'expired' : $task['status']) ?>
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
                                    <input type="date" id="due_date" required class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 bg-gray-100 dark:text-gray-100 dark:bg-gray-700  text-gray-900 dark:text-gray-100">
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
    <?php if ($user_role == 1): ?>
        <script src="./assets/js/dashboard/admin-dashboard.js"></script>
    <?php elseif ($user_role == 2): ?>
        <script src="./assets/js/dashboard/intern-dashboard.js"></script>
    <?php elseif ($user_role == 3): ?>
        <script src="./assets/js/dashboard/supervisor-dashboard.js"></script>
    <?php endif; ?>

    <script>
        // Modern chart initialization with enhanced styling
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
    </script>
</body>

</html>