<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in and is an intern
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header("Location: login.php");
    exit();
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

$page_title = "Internship Roadmap";
include 'include/header.php';
?>

<div class="flex h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden">
    <!-- Sidebar -->
    <?php include 'include/sideBar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Navbar -->
        <?php include 'include/navbar.php'; ?>

        <!-- Main Body Content -->
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-5xl mx-auto space-y-6">

                <!-- Header Banner -->
                <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 rounded-2xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/15 backdrop-blur-md rounded-full text-xs font-semibold tracking-wide uppercase mb-3">
                                <span>🚀 <?= htmlspecialchars($tech_name) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($duration) ?> Program</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">Your Internship Roadmap</h1>
                            <p class="mt-2 text-indigo-100 text-sm max-w-xl">
                                Follow your structured weekly learning curriculum, track your milestone completions, and excel in your tech career.
                            </p>
                        </div>

                        <!-- Progress Circle / Card -->
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/15 text-center min-w-[160px]">
                            <div class="text-xs uppercase tracking-wider text-indigo-200 font-semibold mb-1">Overall Progress</div>
                            <div class="text-3xl font-black"><?= $progress_percent ?>%</div>
                            <div class="mt-2 w-full bg-white/20 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-400 h-full rounded-full transition-all duration-500" style="width: <?= $progress_percent ?>%;"></div>
                            </div>
                            <div class="text-[11px] text-indigo-100 mt-1.5 font-medium">
                                <?= $completed_count ?> of <?= $total_weeks ?> Weeks Completed
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Curriculum Roadmap Cards -->
                <?php if (!empty($roadmap_tasks)): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-8 flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Curriculum Weekly Milestones
                        </h2>

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

                                    <!-- Milestone Box -->
                                    <div class="bg-gray-50 dark:bg-gray-900/50 border <?= $is_current ? 'border-indigo-300 dark:border-indigo-600/60 ring-2 ring-indigo-500/10' : 'border-gray-100 dark:border-gray-800' ?> rounded-xl p-5 md:p-6 transition-all duration-300 hover:shadow-md">
                                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                            <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                                <span>Week <?= $w_num ?>:</span>
                                                <span class="text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($rt['title']) ?></span>
                                            </h3>

                                            <div>
                                                <?php if ($is_done): ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-800/50">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Completed
                                                    </span>
                                                <?php elseif ($is_current): ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 px-3 py-1 rounded-full border border-indigo-200 dark:border-indigo-800/50">
                                                        <span class="w-2 h-2 rounded-full bg-indigo-600 dark:bg-indigo-400 animate-ping"></span>
                                                        In Progress
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700">
                                                        🔒 Locked / Upcoming
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                            <?= nl2br($rt['description']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-100 dark:border-gray-700">
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
        </main>
    </div>
</div>

<?php include 'include/footer.php'; ?>
