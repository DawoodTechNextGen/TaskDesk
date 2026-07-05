<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id'])) {
    header('location:' . BASE_URL . 'login.php');
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
    $inprogress_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status = 'inprogress' AND (due_date >= CURDATE() OR due_date IS NULL)")->fetch_assoc()['total'];
    $completed_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status = 'complete'")->fetch_assoc()['total'];
    $expired_tasks = $conn->query("SELECT COUNT(id) as total FROM tasks WHERE status = 'expired' OR (status IN ('inprogress', 'needs_improvement') AND due_date < CURDATE())")->fetch_assoc()['total'];
    $contacted_last_24h = $conn->query("SELECT COUNT(id) as total FROM registrations WHERE status = 'contact' AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['total'];

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

    $inprogress_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE assign_to = ? AND status = 'inprogress' AND (due_date >= CURDATE() OR due_date IS NULL)");
    $inprogress_tasks->bind_param("i", $user_id);
    $inprogress_tasks->execute();
    $inprogress_tasks = $inprogress_tasks->get_result()->fetch_assoc()['total'];

    $expired_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE assign_to = ? AND (status = 'expired' OR (status IN ('inprogress', 'needs_improvement') AND due_date < CURDATE()))");
    $expired_tasks->bind_param("i", $user_id);
    $expired_tasks->execute();
    $expired_tasks = $expired_tasks->get_result()->fetch_assoc()['total'];

    // Fetch intern details for progress calculation
    $intern_details_query = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $intern_details_query->bind_param("i", $user_id);
    $intern_details_query->execute();
    $intern_details = $intern_details_query->get_result()->fetch_assoc();
    $joining_date = $intern_details['created_at'];
    $internship_type = $intern_details['internship_type'];
    $internship_duration = $intern_details['internship_duration'];
} elseif ($user_role == 3) {
    // Supervisor data
    $generated_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks WHERE created_by = ?");
    $generated_tasks->bind_param("i", $user_id);
    $generated_tasks->execute();
    $generated_tasks = $generated_tasks->get_result()->fetch_assoc()['total'];

    $managed_interns = $conn->prepare("SELECT COUNT(id) as total FROM users WHERE user_role = 2 AND status = 1 AND supervisor_id = ?");
    $managed_interns->bind_param("i", $user_id);
    $managed_interns->execute();
    $managed_interns = $managed_interns->get_result()->fetch_assoc()['total'];

    $expired_tasks = $conn->prepare("SELECT COUNT(id) as total FROM tasks 
        WHERE (assign_to IN (SELECT id FROM users WHERE supervisor_id = ?)) 
        AND (status = 'expired' OR (status IN ('inprogress', 'needs_improvement') AND due_date < CURDATE()))");
    $expired_tasks->bind_param("i", $user_id);
    $expired_tasks->execute();
    $expired_tasks = $expired_tasks->get_result()->fetch_assoc()['total'];

    // Fetch Monthly Earnings
    $monthly_earnings_query = $conn->prepare("SELECT 
        SUM(amount) as total,
        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as earned,
        SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as refunded
        FROM commissions WHERE supervisor_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $monthly_earnings_query->bind_param("i", $user_id);
    $monthly_earnings_query->execute();
    $earnings_res = $monthly_earnings_query->get_result()->fetch_assoc();
    $monthly_earnings = (int)($earnings_res['total'] ?? 0);
    $monthly_earned = (int)($earnings_res['earned'] ?? 0);
    $monthly_refunded = (int)($earnings_res['refunded'] ?? 0);
    $monthly_earnings_query->close();
} elseif ($user_role == 4) {
    // Manager Monthly Earnings
    $monthly_earnings_query = $conn->prepare("SELECT 
        SUM(amount) as total,
        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as earned,
        SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as refunded
        FROM commissions WHERE supervisor_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $monthly_earnings_query->bind_param("i", $user_id);
    $monthly_earnings_query->execute();
    $earnings_res = $monthly_earnings_query->get_result()->fetch_assoc();
    $monthly_earnings = (int)($earnings_res['total'] ?? 0);
    $monthly_earned = (int)($earnings_res['earned'] ?? 0);
    $monthly_refunded = (int)($earnings_res['refunded'] ?? 0);
    $monthly_earnings_query->close();
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

                <!-- DoTalk Quick Launch Banner -->
                <?php
                $unreadChatCount = 0;
                if (isset($conn) && isset($_SESSION['user_id'])) {
                    $currentUserId = $_SESSION['user_id'];
                    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM chat_messages WHERE receiver_id = ? AND is_read = 0");
                    if ($stmt) {
                        $stmt->bind_param('i', $currentUserId);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        $unreadChatCount = (int)$result['total'];
                        $stmt->close();
                    }
                }
                ?>
                <div class="mb-6 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 transition hover:shadow-sm duration-150 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-sky-500 text-white shadow-md shadow-sky-500/10 shrink-0">
                            <!-- Chat Icon SVG -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 10.742h.01m3.481 0h.01m3.481 0h.01M12 21a9.003 9.003 0 008.354-5.646 9.003 9.003 0 00-.01-7.048A9.003 9.003 0 0012 3a9 9 0 00-8.7 9H3.75a.75.75 0 01-.75-.75V8.25m18 0h.008v.008H21V8.25z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                                Connect with DoTalk
                                <?php if ($unreadChatCount > 0): ?>
                                    <span class="inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 animate-pulse">
                                        <?= $unreadChatCount ?> New
                                    </span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Launch the chat application in a new browser tab.</p>
                        </div>
                    </div>
                    <a href="<?= DOTALK_URL ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center justify-center rounded-xl bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 text-xs font-semibold transition duration-150 shadow-md shadow-sky-500/10 shrink-0">
                        Open Chat
                    </a>
                </div>

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
                                        <p class="text-teal-500 dark:text-teal-100 text-sm">Recent entries</p>
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
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">In Progress</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $inprogress_tasks ?></h3>
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
                            <a href="tasks.php?status=expired" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-red-500 hover:shadow-lg transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium group-hover:text-red-500 transition-colors">Expired</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $expired_tasks ?></h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-2 rounded-lg group-hover:bg-red-200 dark:group-hover:bg-red-800 transition-all">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                            <a href="registrations_contact.php" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-blue-500 hover:shadow-lg transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium group-hover:text-blue-500 transition-colors">Contacted (24h)</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white"><?= $contacted_last_24h ?></h3>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-all">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Redesigned Analytics Section -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Platform Activity & Payout Trends Card (2/3 width) -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 lg:col-span-2 flex flex-col justify-between">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white" id="trendChartTitle">Commission Payout Trends</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" id="trendChartSubtitle">Monthly aggregate commission payouts for the last 6 months</p>
                                    </div>
                                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-1 bg-gray-50 dark:bg-gray-900/50">
                                        <button id="btnCommissionTrends" class="px-4 py-1.5 rounded-md text-xs font-semibold transition-all bg-indigo-600 text-white shadow-sm" onclick="switchTrendChart('commission')">
                                            Commission Payouts
                                        </button>
                                        <button id="btnTaskTrends" class="px-4 py-1.5 rounded-md text-xs font-semibold transition-all text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-white" onclick="switchTrendChart('tasks')">
                                            Task Activity
                                        </button>
                                    </div>
                                </div>
                                <div class="h-80 w-full relative">
                                    <canvas id="unifiedTrendsChart"></canvas>
                                </div>
                                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 hidden" id="monthlyTrendsStatsContainer">
                                    <!-- Dynamic stats for tasks will load here when tasks is selected -->
                                </div>
                            </div>

                            <!-- Task Status Distribution - Modern Donut Chart (1/3 width) -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 flex flex-col justify-between">
                                <div class="flex justify-between items-center mb-6">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Task Operational Status</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Real-time status breakdown</p>
                                    </div>
                                </div>
                                <div class="h-64 relative">
                                    <canvas id="taskStatusChart"></canvas>
                                </div>
                                <div class="mt-6 grid grid-cols-2 gap-3" id="taskStatusLegend">
                                    <!-- Legend will be populated by JavaScript -->
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
                                                    <td class="py-4 text-sm text-gray-500 dark:text-gray-400"><?= date('j F Y', strtotime($user['created_at'])) ?></td>
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
                                            <span class="text-sm text-gray-500 dark:text-gray-400"><?= date('j F Y H:i') ?></span>
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
                                            <?= $inprogress_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <a href="assignedTasks.php?status=expired" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium group-hover:text-red-500 transition-colors">Expired</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $expired_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-3 rounded-lg group-hover:bg-red-200 dark:group-hover:bg-red-800 transition-all">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Internship Duration Progress -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700 mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Internship Duration Progress
                            </h3>
                            <?php
                            $start = new DateTime($joining_date);
                            $now = new DateTime();
                            
                            $weeks = 12; // Default
                            if (!empty($internship_duration)) {
                                if ($internship_duration === '4 weeks') $weeks = 4;
                                elseif ($internship_duration === '8 weeks') $weeks = 8;
                                elseif ($internship_duration === '12 weeks') $weeks = 12;
                            } else {
                                $weeks = ($internship_type == 0) ? 4 : 12;
                            }
                            
                            $end = clone $start;
                            $end->modify("+$weeks weeks");
                            
                            $total_days = $start->diff($end)->days;
                            $days_passed = $start->diff($now)->days;
                            if ($now < $start) $days_passed = 0;
                            
                            $progress = min(100, max(0, round(($days_passed / $total_days) * 100)));
                            $remaining = max(0, $total_days - $days_passed);
                            ?>
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-indigo-600 bg-indigo-200">
                                            <?= $progress ?>% Completed
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold inline-block text-indigo-600">
                                            <?= $remaining ?> Days Remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-indigo-100 dark:bg-gray-700 shadow-inner">
                                    <div style="width:<?= $progress ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-500 relative overflow-hidden">
                                        <!-- Subtle stripe animation -->
                                        <div class="absolute inset-0 opacity-20 bg-[linear-gradient(45deg,rgba(255,255,255,.15)_25%,transparent_25%,transparent_50%,rgba(255,255,255,.15)_50%,rgba(255,255,255,.15)_75%,transparent_75%,transparent)] bg-[length:1rem_1rem] animate-[move-stripe_1s_linear_infinite]"></div>
                                    </div>
                                </div>
                                <style>
                                @keyframes move-stripe {
                                    from { background-position: 1rem 0; }
                                    to { background-position: 0 0; }
                                }
                                </style>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Joined: <?= date('j F Y', strtotime($joining_date)) ?></span>
                                    <span>Ends: <?= $end->format('j F Y') ?></span>
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

                                            if (strtotime($dueDate) < strtotime($currentDate) && in_array($task['status'], ['inprogress', 'needs_improvement'])) {
                                                $statusText = 'Expired';
                                                $statusClass = 'bg-red-100 text-red-800';
                                            } elseif ($task['status'] === 'inprogress') {
                                                $statusText = 'In Progress';
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                            } elseif ($task['status'] === 'pending_review') {
                                                $statusText = 'Pending Review';
                                                $statusClass = 'bg-indigo-100 text-indigo-800';
                                            } elseif ($task['status'] === 'needs_improvement') {
                                                $statusText = 'Needs Improvement';
                                                $statusClass = 'bg-orange-100 text-orange-800';
                                            } elseif ($task['status'] === 'complete') {
                                                $statusText = 'Complete';
                                                $statusClass = 'bg-green-100 text-green-800';
                                            } else {
                                                $statusText = ucfirst($task['status']);
                                                $statusClass = 'bg-red-100 text-red-800';
                                            }
                                            ?>

                                            <div class="text-right">
                                                <span class="inline-block px-2 py-1 text-xs rounded-full <?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                                <p class="text-xs text-red-500 dark:text-gray-400 mt-1 font-medium">Due: <?= date('j F Y', strtotime($dueDate)) ?></p>
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
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Task Completion</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2" id="onTimeRate">0%</div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">On-Time Accuracy</p>
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
                        <!-- Supervisor Monthly Earnings Card (Isolated at Top) -->
                        <div class="mb-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                                <div class="relative z-10 flex items-center justify-between">
                                    <div>
                                        <p class="text-indigo-600 dark:text-indigo-400 text-sm font-medium mb-1">This Month Earning</p>
                                        <h3 class="text-3xl font-bold text-gray-800 dark:text-white"><?= number_format($monthly_earnings) ?> PKR</h3>
                                        <div class="flex items-center space-x-4 mt-2 text-xs">
                                            <span class="text-green-600 dark:text-green-400 font-semibold">Earned: +<?= number_format($monthly_earned) ?> PKR</span>
                                            <?php if ($monthly_refunded < 0): ?>
                                                <span class="text-red-600 dark:text-red-400 font-semibold">Refunded: <?= number_format($monthly_refunded) ?> PKR</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="bg-indigo-50 dark:bg-indigo-900/50 p-4 rounded-xl text-indigo-600 dark:text-indigo-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supervisor Stats Cards Grid -->
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
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">On-Time Accuracy</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white" id="supervisorOnTimeRate">0%</h3>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <a href="tasks.php?status=expired" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium group-hover:text-red-500 transition-colors">Expired Tasks</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= $expired_tasks ?>
                                        </h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-3 rounded-lg group-hover:bg-red-200 dark:group-hover:bg-red-800 transition-all">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
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
                                                        <?php
                                                        $isExpired = ($task['due_date'] < date('Y-m-d') && in_array($task['status'], ['inprogress', 'needs_improvement']));
                                                        $displayStatus = $isExpired ? 'expired' : $task['status'];
                                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                                        $statusLabel = ucfirst($displayStatus);

                                                        if ($displayStatus === 'expired') {
                                                            $statusClass = 'bg-red-100 text-red-800';
                                                            $statusLabel = 'Expired';
                                                        } elseif ($displayStatus === 'complete') {
                                                            $statusClass = 'bg-green-100 text-green-800';
                                                            $statusLabel = 'Complete';
                                                        } elseif ($displayStatus === 'pending_review') {
                                                            $statusClass = 'bg-indigo-100 text-indigo-800';
                                                            $statusLabel = 'Pending Review';
                                                        } elseif ($displayStatus === 'needs_improvement') {
                                                            $statusClass = 'bg-orange-100 text-orange-800';
                                                            $statusLabel = 'Needs Improvement';
                                                        } elseif ($displayStatus === 'rejected') {
                                                            $statusClass = 'bg-red-100 text-red-800';
                                                            $statusLabel = 'Rejected';
                                                        }
                                                        ?>
                                                        <span class="px-2 py-1 rounded-full text-xs <?= $statusClass ?>">
                                                            <?= $statusLabel ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3 text-sm text-gray-600 dark:text-gray-300"><?= date('j F Y', strtotime($task['due_date'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user_role == 4): ?>
                    <!-- ==================== MANAGER DASHBOARD ==================== -->
                    <div class="mb-8">
                        <!-- Manager Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                            <!-- Monthly Earnings -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-indigo-500 dark:text-indigo-300 text-sm font-medium">This Month Earning</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                                            <?= number_format($monthly_earnings) ?> PKR
                                        </h3>
                                        <div class="flex flex-col mt-1 text-[10px] space-y-0.5">
                                            <span class="text-green-600 dark:text-green-400 font-semibold">Earned: +<?= number_format($monthly_earned) ?> PKR</span>
                                            <?php if ($monthly_refunded < 0): ?>
                                                <span class="text-red-600 dark:text-red-400 font-semibold">Refunded: <?= number_format($monthly_refunded) ?> PKR</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-lg">
                                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Registrations -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <div class="relative">
                                    <p class="text-blue-500 dark:text-blue-100 text-sm font-medium mb-2">Total Registrations</p>
                                    <h3 class="text-3xl font-bold mb-2 text-black dark:text-white" id="totalRegistrations">0</h3>
                                    <p class="text-blue-500 dark:text-blue-100 text-sm">All time registrations</p>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl text-white">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"></circle>
                                            <path d="M18 9C19.6569 9 21 7.88071 21 6.5C21 5.11929 19.6569 4 18 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M6 9C4.34315 9 3 7.88071 3 6.5C3 5.11929 4.34315 4 6 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M17.1973 15C17.7078 15.5883 18 16.2714 18 17C18 19.2091 15.3137 21 12 21C8.68629 21 6 19.2091 6 17C6 14.7909 8.68629 13 12 13C12.3407 13 12.6748 13.0189 13 13.0553" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M20 19C21.7542 18.6153 23 17.6411 23 16.5C23 15.3589 21.7542 14.3847 20 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                            <path d="M4 19C2.24575 18.6153 1 17.6411 1 16.5C1 15.3589 2.24575 14.3847 4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>

                            <!-- New Registrations -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <a href="registrations.php?status=new" class="block">
                                    <div class="relative">
                                        <p class="text-green-500 dark:text-green-100 text-sm font-medium mb-2">New</p>
                                        <h3 class="text-3xl font-bold mb-2 text-black dark:text-white" id="newRegistrations">0</h3>
                                        <p class="text-green-500 dark:text-green-100 text-sm">Pending review</p>
                                    </div>
                                </a>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>

                            <!-- Contacted -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <a href="registrations.php?status=contact" class="block">
                                    <div class="relative">
                                        <p class="text-yellow-500 dark:text-yellow-100 text-sm font-medium mb-2">Contacted</p>
                                        <h3 class="text-3xl font-bold mb-2 text-black dark:text-white" id="contactedRegistrations">0</h3>
                                        <p class="text-yellow-500 dark:text-yellow-100 text-sm">In contact process</p>
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

                            <!-- Hired -->
                            <div class="rounded-2xl shadow-lg p-6 relative overflow-hidden bg-white dark:bg-gray-800">
                                <a href="registrations.php?status=hire" class="block">
                                    <div class="relative">
                                        <p class="text-teal-500 dark:text-teal-100 text-sm font-medium mb-2">Hired</p>
                                        <h3 class="text-3xl font-bold mb-2 text-black dark:text-white" id="hiredRegistrations">0</h3>
                                        <p class="text-teal-500 dark:text-teal-100 text-sm">Successfully hired</p>
                                    </div>
                                </a>
                                <div class="absolute top-4 right-4">
                                    <div class="bg-gray-400 dark:bg-white/20 p-3 rounded-xl">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-1 bg-gray-400 dark:bg-white/30"></div>
                            </div>
                        </div>

                        <!-- Registration Status Overview Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Total</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white" id="totalRegistrationsCount">0</h3>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">New</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white" id="newRegistrationsCount">0</h3>
                                    </div>
                                    <div class="bg-yellow-100 dark:bg-yellow-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-green-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Contacted</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white" id="contactedCount">0</h3>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 border-l-4 border-red-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-300 text-sm font-medium">Rejected</p>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white" id="rejectedCount">0</h3>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Charts Section -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
                            <!-- Registration Status Distribution -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Registration Status Distribution</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Real-time</span>
                                </div>
                                <div class="h-80 relative">
                                    <canvas id="registrationStatusChart"></canvas>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-4" id="registrationStatusLegend">
                                    <!-- Legend will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Monthly Registration Trends -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Monthly Registration Trends</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Last 6 months</span>
                                </div>
                                <div class="h-80">
                                    <canvas id="monthlyRegistrationsChart"></canvas>
                                </div>
                                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4" id="monthlyTrendsStats">
                                    <!-- Stats will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Additional Charts Row -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Technology-wise Registrations -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Registrations by Technology</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Top 5</span>
                                </div>
                                <div class="h-80 relative">
                                    <canvas id="techRegistrationChart"></canvas>
                                </div>
                                <div class="mt-4 flex justify-center space-x-6" id="techRegistrationLegend">
                                    <!-- Legend will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Internship Type Distribution -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Internship Type Distribution</h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">All time</span>
                                </div>
                                <div class="h-80">
                                    <canvas id="internshipTypeChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Registrations & Quick Actions -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Recent Registrations -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 lg:col-span-2">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Recent Registrations</h3>
                                    <a href="registrations.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                                </div>
                                <div class="overflow-x-auto custom-scrollbar">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Name</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Email</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Technology</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Status</th>
                                                <th class="text-left py-3 text-sm font-medium text-gray-600 dark:text-gray-300">Registered</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentRegistrationsTable">
                                            <!-- Data will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="space-y-6">
                                <!-- Quick Actions -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Quick Actions</h3>
                                    <div class="space-y-3">
                                        <a href="registrations.php?status=new" class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 text-blue-700 dark:text-blue-300 rounded-xl hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-800/50 dark:hover:to-blue-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3 text-white">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Review New</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="registrations.php?status=contact" class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 text-green-700 dark:text-green-300 rounded-xl hover:from-green-100 hover:to-green-200 dark:hover:from-green-800/50 dark:hover:to-green-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Contacted List</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="registrations.php?status=hire" class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 text-purple-700 dark:text-purple-300 rounded-xl hover:from-purple-100 hover:to-purple-200 dark:hover:from-purple-800/50 dark:hover:to-purple-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Hired Interns</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                        <a href="registrations.php?status=rejected" class="flex items-center justify-between p-3 bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 text-red-700 dark:text-red-300 rounded-xl hover:from-red-100 hover:to-red-200 dark:hover:from-red-800/50 dark:hover:to-red-700/50 transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium">Rejected List</span>
                                            </div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- System Overview -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Registration Overview</h3>
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Today's Registrations</span>
                                            <span class="text-sm font-medium text-gray-800 dark:text-white" id="todayRegistrations">0</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">This Week</span>
                                            <span class="text-sm font-medium text-gray-800 dark:text-white" id="weekRegistrations">0</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">This Month</span>
                                            <span class="text-sm font-medium text-gray-800 dark:text-white" id="monthRegistrations">0</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Hiring Rate</span>
                                            <span class="text-sm font-medium text-green-600 dark:text-green-400" id="hiringRate">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php if ($_SESSION['user_role'] == 3): ?>
                <!-- Supervisor Dashboard elements remain, but the detailed table is removed -->
            <?php endif; ?>

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
        <script src="assets/js/task-review.js"></script>
    <?php endif; ?>
    <?php if ($user_role == 4): ?>
        <script src="./assets/js/dashboard/manager-dashboard.js"></script>
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
            } else if (userRole === 4) {
                initializeManagerCharts();
            }
        });
    </script>
</body>

</html>
