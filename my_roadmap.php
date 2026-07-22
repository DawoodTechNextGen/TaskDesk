<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header('location:' . BASE_URL . 'login.php');
    exit();
} else {
    include_once './include/connection.php';
}

$user_id = (int)$_SESSION['user_id'];

// Fetch intern details
$u_stmt = $conn->prepare("
    SELECT u.name, u.email, u.tech_id, u.internship_duration, t.name as tech_name 
    FROM users u 
    LEFT JOIN technologies t ON u.tech_id = t.id 
    WHERE u.id = ?
");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$intern = $u_stmt->get_result()->fetch_assoc();
$u_stmt->close();

$tech_id = (int)($intern['tech_id'] ?? 0);
$duration = $intern['internship_duration'] ?? '';
$tech_name = $intern['tech_name'] ?? 'Not Assigned';

// Fetch completed weeks
$completed_weeks = [];
$w_stmt = $conn->prepare("SELECT DISTINCT week_number FROM tasks WHERE assign_to = ? AND week_number > 0 AND status IN ('complete', 'approved')");
$w_stmt->bind_param("i", $user_id);
$w_stmt->execute();
$w_res = $w_stmt->get_result();
while ($w_row = $w_res->fetch_assoc()) {
    $completed_weeks[] = (int)$w_row['week_number'];
}
$w_stmt->close();

// Fetch active task week
$active_week = 0;
$act_stmt = $conn->prepare("SELECT week_number FROM tasks WHERE assign_to = ? AND status IN ('inprogress', 'needs_improvement') ORDER BY id DESC LIMIT 1");
$act_stmt->bind_param("i", $user_id);
$act_stmt->execute();
$act_res = $act_stmt->get_result()->fetch_assoc();
if ($act_res) {
    $active_week = (int)($act_res['week_number'] ?? 0);
}
$act_stmt->close();

// Fetch curriculum roadmap tasks
$roadmap_tasks = [];
if ($tech_id > 0 && !empty($duration)) {
    $c_stmt = $conn->prepare("SELECT id, week_number, title, description FROM curriculum_tasks WHERE tech_id = ? AND duration = ? ORDER BY week_number ASC");
    $c_stmt->bind_param("is", $tech_id, $duration);
    $c_stmt->execute();
    $roadmap_tasks = $c_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $c_stmt->close();
}

$total_weeks = count($roadmap_tasks);
$completed_count = count(array_intersect($completed_weeks, array_column($roadmap_tasks, 'week_number')));
$progress_percent = ($total_weeks > 0) ? round(($completed_count / $total_weeks) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Internship Roadmap - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include_once "./include/sideBar.php"; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Bar -->
            <?php include_once "./include/header.php"; ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                
                <!-- Page Header Title -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Your Internship Roadmap</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Technology Track: <span class="font-semibold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($tech_name) ?></span> (<?= htmlspecialchars($duration) ?>)
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 px-4 py-2.5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Progress:</div>
                        <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: <?= $progress_percent ?>%;"></div>
                        </div>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400"><?= $progress_percent ?>%</span>
                        <span class="text-[11px] text-gray-400"> (<?= $completed_count ?>/<?= $total_weeks ?> Weeks)</span>
                    </div>
                </div>

                <!-- Admin/Supervisor Style Card Container -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700 mb-8">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Curriculum Tasks & Weekly Timeline
                        </h3>
                    </div>

                    <div class="p-6">
                        <?php if (!empty($roadmap_tasks)): ?>
                            <div class="relative border-l-2 border-indigo-100 dark:border-gray-700 ml-4 md:ml-6 space-y-8">
                                <?php foreach ($roadmap_tasks as $rt): 
                                    $w_num = (int)$rt['week_number'];
                                    $is_done = in_array($w_num, $completed_weeks);
                                    $is_current = ($w_num === $active_week);
                                ?>
                                    <div class="relative pl-8 md:pl-10 group">
                                        <!-- Status Dot Indicator -->
                                        <?php if ($is_done): ?>
                                            <div class="absolute -left-[11px] top-1.5 w-5 h-5 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-800 flex items-center justify-center text-white shadow-md shadow-emerald-500/30">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        <?php elseif ($is_current): ?>
                                            <div class="absolute -left-[11px] top-1.5 w-5 h-5 rounded-full bg-indigo-600 border-2 border-white dark:border-gray-800 flex items-center justify-center text-white animate-pulse shadow-md shadow-indigo-600/30">
                                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="absolute -left-[11px] top-1.5 w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 border-2 border-white dark:border-gray-800 group-hover:border-indigo-400 transition-all"></div>
                                        <?php endif; ?>

                                        <!-- Milestone Box (Identical to Admin Curriculum Card) -->
                                        <div class="flex-1 w-full p-6 bg-white dark:bg-gray-800/90 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md hover:shadow-xl transition-all duration-300 relative overflow-hidden group-hover:border-indigo-400/50 dark:group-hover:border-indigo-500/40">
                                            <!-- Background gradient flare -->
                                            <div class="absolute -right-20 -top-20 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all duration-300"></div>
                                            
                                            <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4 gap-2 pb-3 border-b border-gray-100 dark:border-gray-700/50">
                                                <h4 class="font-bold text-gray-900 dark:text-white text-lg sm:text-xl flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full <?= $is_done ? 'bg-emerald-500' : ($is_current ? 'bg-indigo-500 animate-pulse' : 'bg-gray-300 dark:bg-gray-600') ?>"></span>
                                                    <?= htmlspecialchars($rt['title']) ?>
                                                </h4>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-950/80 dark:to-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full border border-indigo-200/50 dark:border-indigo-900/60 shadow-sm">Week <?= $w_num ?></span>
                                                    <?php if ($is_done): ?>
                                                        <span class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 text-xs font-bold px-3 py-1 rounded-full border border-emerald-200/50 dark:border-emerald-900/60">✓ Completed</span>
                                                    <?php elseif ($is_current): ?>
                                                        <span class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full border border-indigo-200/50 dark:border-indigo-900/60">In Progress</span>
                                                    <?php else: ?>
                                                        <span class="bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 text-xs font-semibold px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700">🔒 Upcoming</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="text-sm text-gray-600 dark:text-gray-400 prose dark:prose-invert max-w-none leading-relaxed">
                                                <?= $rt['description'] ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-950/50 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Roadmap Not Available</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                                    No curriculum roadmap has been configured for your technology track yet. Please contact your supervisor for details.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
</body>
</html>
