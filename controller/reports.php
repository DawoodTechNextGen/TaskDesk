<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";
require_once __DIR__ . '/../include/bootcamp_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Reports module: Collaborators need at least read access; reports has no write
// actions of its own, so they always see the same reports an Admin does.
requireModuleView(MODULE_REPORTS);
if ((int)$user_role === ROLE_COLLABORATOR) {
    $user_role = ROLE_ADMIN;
}

// Helper to get last 6 months labels
function getLast6MonthsLabels() {
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('M Y', strtotime("-$i months"));
    }
    return $months;
}

if ($action === 'get_report_data') {
    $data = [
        'success' => true,
        'months' => getLast6MonthsLabels(),
        'charts' => []
    ];

    if ($user_role == 1 || $user_role == 4) { // Admin or Manager
        // 1. Monthly Registration Trends (Last 6 Months)
        $reg_trends = array_fill(0, 6, 0);
        $stmt = $conn->query("
            SELECT 
                DATE_FORMAT(created_at, '%b %Y') as month,
                COUNT(*) as count,
                DATE_FORMAT(created_at, '%Y-%m') as sort_key
            FROM registrations 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ");
        $results = [];
        while ($row = $stmt->fetch_assoc()) {
            $results[$row['month']] = (int)$row['count'];
        }
        $labels = getLast6MonthsLabels();
        $reg_counts = [];
        $reg_percentages = []; // Store percentage change for each month
        foreach ($labels as $index => $lbl) {
            $current = $results[$lbl] ?? 0;
            $reg_counts[] = $current;
            
            // Calculate percentage change from previous month
            if ($index > 0) {
                $previous = $reg_counts[$index - 1];
                if ($previous > 0) {
                    $change = round((($current - $previous) / $previous) * 100, 1);
                } else {
                    $change = $current > 0 ? 100 : 0; // If previous was 0 and current > 0, show 100% increase
                }
                $reg_percentages[] = $change;
            } else {
                $reg_percentages[] = 0; // First month has no comparison
            }
        }
        $data['charts']['registrations'] = [
            'type' => 'line',
            'label' => 'New Internship Registration',
            'data' => $reg_counts,
            'percentages' => $reg_percentages, // Add percentage data
            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
            'borderColor' => 'rgb(59, 130, 246)'
        ];

        // 2. Monthly Bootcamp Registration Trends (Last 6 Months) - separate
        // table/database from internship registrations above (see
        // include/bootcamp_helper.php), same shape as the chart above.
        $stmt = $conn->query("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') as month,
                COUNT(*) as count,
                DATE_FORMAT(created_at, '%Y-%m') as sort_key
            FROM " . BOOTCAMP_TABLE . "
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ");
        $bootcamp_results = [];
        while ($row = $stmt->fetch_assoc()) {
            $bootcamp_results[$row['month']] = (int)$row['count'];
        }
        $bootcamp_reg_counts = [];
        $bootcamp_reg_percentages = [];
        foreach ($labels as $index => $lbl) {
            $current = $bootcamp_results[$lbl] ?? 0;
            $bootcamp_reg_counts[] = $current;

            if ($index > 0) {
                $previous = $bootcamp_reg_counts[$index - 1];
                if ($previous > 0) {
                    $change = round((($current - $previous) / $previous) * 100, 1);
                } else {
                    $change = $current > 0 ? 100 : 0;
                }
                $bootcamp_reg_percentages[] = $change;
            } else {
                $bootcamp_reg_percentages[] = 0;
            }
        }
        $data['charts']['bootcamp_registrations'] = [
            'type' => 'line',
            'label' => 'New Bootcamp Registration',
            'data' => $bootcamp_reg_counts,
            'percentages' => $bootcamp_reg_percentages,
            'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
            'borderColor' => 'rgb(139, 92, 246)'
        ];

        // 3. Hiring Trends & Ratios (Last 6 Months) - placed right below the
        // Registration row so the two conversion charts (Hiring / Enrolled) land
        // together on the very next row.
        $hired_results = [];
        $stmt = $conn->query("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') as month,
                COUNT(*) as count,
                DATE_FORMAT(created_at, '%Y-%m') as sort_key
            FROM registrations
            WHERE status = 'hire' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ");
        while ($row = $stmt->fetch_assoc()) {
            $hired_results[$row['month']] = (int)$row['count'];
        }

        $hired_counts = [];
        $hiring_ratios = [];
        $hired_percentages = []; // MoM change in hired count
        $ratio_changes = []; // MoM change in hiring ratio
        foreach ($labels as $index => $lbl) {
            $hired = $hired_results[$lbl] ?? 0;
            $total_reg = $reg_counts[$index];
            $hired_counts[] = $hired;
            $current_ratio = $total_reg > 0 ? round(($hired / $total_reg) * 100, 1) : 0;
            $hiring_ratios[] = $current_ratio;

            // Calculate MoM percentage change for hired count
            if ($index > 0) {
                $prev_hired = $hired_counts[$index - 1];
                if ($prev_hired > 0) {
                    $hired_percentages[] = round((($hired - $prev_hired) / $prev_hired) * 100, 1);
                } else {
                    $hired_percentages[] = $hired > 0 ? 100 : 0;
                }

                // Calculate change in hiring ratio
                $prev_ratio = $hiring_ratios[$index - 1];
                $ratio_changes[] = round($current_ratio - $prev_ratio, 1); // Absolute difference
            } else {
                $hired_percentages[] = 0;
                $ratio_changes[] = 0;
            }
        }

        $data['charts']['hiring_performance'] = [
            'type' => 'mixed', // Custom type to indicate dual-axis
            'labels' => $labels,
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Interns Hired',
                    'data' => $hired_counts,
                    'percentages' => $hired_percentages, // Add MoM percentage
                    'registrations' => $reg_counts, // Add total registrations for context
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'yAxisID' => 'y'
                ],
                [
                    'type' => 'line',
                    'label' => 'Hiring Ratio (%)',
                    'data' => $hiring_ratios,
                    'changes' => $ratio_changes, // Add ratio changes
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1'
                ]
            ]
        ];

        // 4. Enrolled Trends & Ratios (Last 6 Months) - Bootcamp's equivalent of
        // Hiring Performance above: how many bootcamp registrations reached
        // `enrolled` and what share of that month's bootcamp registrations that is.
        $enrolled_results = [];
        $stmt = $conn->query("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') as month,
                COUNT(*) as count,
                DATE_FORMAT(created_at, '%Y-%m') as sort_key
            FROM " . BOOTCAMP_TABLE . "
            WHERE status = 'enrolled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ");
        while ($row = $stmt->fetch_assoc()) {
            $enrolled_results[$row['month']] = (int)$row['count'];
        }

        $enrolled_counts = [];
        $enrolled_ratios = [];
        $enrolled_percentages = []; // MoM change in enrolled count
        $enrolled_ratio_changes = []; // MoM change in enrollment ratio
        foreach ($labels as $index => $lbl) {
            $enrolled = $enrolled_results[$lbl] ?? 0;
            $total_bootcamp_reg = $bootcamp_reg_counts[$index];
            $enrolled_counts[] = $enrolled;
            $current_ratio = $total_bootcamp_reg > 0 ? round(($enrolled / $total_bootcamp_reg) * 100, 1) : 0;
            $enrolled_ratios[] = $current_ratio;

            if ($index > 0) {
                $prev_enrolled = $enrolled_counts[$index - 1];
                if ($prev_enrolled > 0) {
                    $enrolled_percentages[] = round((($enrolled - $prev_enrolled) / $prev_enrolled) * 100, 1);
                } else {
                    $enrolled_percentages[] = $enrolled > 0 ? 100 : 0;
                }

                $prev_ratio = $enrolled_ratios[$index - 1];
                $enrolled_ratio_changes[] = round($current_ratio - $prev_ratio, 1);
            } else {
                $enrolled_percentages[] = 0;
                $enrolled_ratio_changes[] = 0;
            }
        }

        $data['charts']['enrolled_performance'] = [
            'type' => 'mixed', // Custom type to indicate dual-axis
            'labels' => $labels,
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Bootcamp Enrolled',
                    'data' => $enrolled_counts,
                    'percentages' => $enrolled_percentages,
                    'registrations' => $bootcamp_reg_counts, // Total bootcamp registrations for context
                    'backgroundColor' => 'rgba(139, 92, 246, 0.6)',
                    'borderColor' => 'rgb(139, 92, 246)',
                    'yAxisID' => 'y'
                ],
                [
                    'type' => 'line',
                    'label' => 'Enrollment Ratio (%)',
                    'data' => $enrolled_ratios,
                    'changes' => $enrolled_ratio_changes,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1'
                ]
            ]
        ];

        // Shared palette for the two city charts below (bar charts need colors
        // passed in explicitly - tech_distribution's pie further down gets its
        // colors from the JS side instead).
        $city_chart_colors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#06B6D4', '#6366F1', '#84CC16', '#F97316'
        ];

        // 5. Top Cities - Internship Registrations. Placed right after the two
        // registration-trend charts (and before tech_distribution) so it lands
        // on the same row as the Bootcamp cities chart below it in the 2-column
        // report grid.
        $intern_city_labels = [];
        $intern_city_counts = [];
        $stmt = $conn->query("
            SELECT city, COUNT(*) as count
            FROM registrations
            WHERE city IS NOT NULL AND city <> ''
            GROUP BY city
            ORDER BY count DESC
            LIMIT 8
        ");
        while ($row = $stmt->fetch_assoc()) {
            $intern_city_labels[] = $row['city'];
            $intern_city_counts[] = (int)$row['count'];
        }
        $data['charts']['internship_cities'] = [
            'type' => 'bar',
            'label' => 'Internship Applicants',
            'labels' => $intern_city_labels,
            'data' => $intern_city_counts,
            'backgroundColor' => array_slice($city_chart_colors, 0, count($intern_city_labels))
        ];

        // 6. Top Cities - Bootcamp Enrollments (separate table/database - see
        // include/bootcamp_helper.php)
        $bootcamp_city_labels = [];
        $bootcamp_city_counts = [];
        $stmt = $conn->query("
            SELECT city, COUNT(*) as count
            FROM " . BOOTCAMP_TABLE . "
            WHERE city IS NOT NULL AND city <> ''
            GROUP BY city
            ORDER BY count DESC
            LIMIT 8
        ");
        while ($row = $stmt->fetch_assoc()) {
            $bootcamp_city_labels[] = $row['city'];
            $bootcamp_city_counts[] = (int)$row['count'];
        }
        $data['charts']['bootcamp_cities'] = [
            'type' => 'bar',
            'label' => 'Bootcamp Applicants',
            'labels' => $bootcamp_city_labels,
            'data' => $bootcamp_city_counts,
            'backgroundColor' => array_slice($city_chart_colors, 0, count($bootcamp_city_labels))
        ];

        // 7. Overall Task Status (Admin/Manager sees all)
        $all_statuses = ['inprogress', 'complete', 'pending_review', 'approved', 'rejected', 'needs_improvement', 'expired'];
        $status_data = array_fill_keys($all_statuses, 0);
        
        $stmt = $conn->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
        $total_tasks = 0;
        while ($row = $stmt->fetch_assoc()) {
            if (isset($status_data[$row['status']])) {
                $status_data[$row['status']] = (int)$row['count'];
                $total_tasks += $row['count'];
            }
        }
        
        $status_labels = [];
        $status_counts = [];
        $status_ratios = [];
        foreach ($status_data as $status => $count) {
            $status_labels[] = ucfirst(str_replace('_', ' ', $status));
            $status_counts[] = $count;
            $status_ratios[] = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
        }

        $data['charts']['task_status'] = [
            'type' => 'doughnut',
            'labels' => $status_labels,
            'data' => $status_counts,
            'ratios' => $status_ratios // Send ratios for tooltips
        ];

        // 8. Global Team Task Completion (Last 6 Months) - placed right after
        // Task Status so the two task-related charts land in the same row.
        $stmt = $conn->query("
            SELECT
                DATE_FORMAT(completed_at, '%b %Y') as month,
                COUNT(*) as count,
                DATE_FORMAT(completed_at, '%Y-%m') as sort_key
            FROM tasks
            WHERE status = 'complete'
            AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ");
        $results = [];
        while ($row = $stmt->fetch_assoc()) {
            $results[$row['month']] = (int)$row['count'];
        }
        $counts = [];
        $percentages = [];
        foreach ($labels as $index => $lbl) {
            $current = $results[$lbl] ?? 0;
            $counts[] = $current;
            if ($index > 0) {
                $previous = $counts[$index - 1];
                $percentages[] = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : ($current > 0 ? 100 : 0);
            } else {
                $percentages[] = 0;
            }
        }
        $data['charts']['team_performance'] = [
            'type' => 'bar',
            'label' => 'Total Tasks Completed',
            'data' => $counts,
            'percentages' => $percentages,
            'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
            'borderColor' => 'rgb(16, 185, 129)'
        ];

        // 9. Technology Distribution (Registrations)
        $tech_labels = [];
        $tech_counts = [];
        $stmt = $conn->query("
            SELECT t.name, COUNT(r.id) as count
            FROM registrations r
            JOIN technologies t ON r.technology_id = t.id
            GROUP BY t.id
            ORDER BY count DESC
            LIMIT 5
        ");
        while ($row = $stmt->fetch_assoc()) {
            $tech_labels[] = $row['name'];
            $tech_counts[] = (int)$row['count'];
        }
        $data['charts']['tech_distribution'] = [
            'type' => 'pie',
            'labels' => $tech_labels,
            'data' => $tech_counts
        ];

        // --- NEW: Global versions of Supervisor charts for Admin/Manager ---

        // 10. Global Attendance Summary
        $stmt = $conn->query("
            SELECT 
                SUM(CASE WHEN total_work_seconds >= 10800 THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN total_work_seconds < 10800 THEN 1 ELSE 0 END) as late_absent
            FROM attendance 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        ");
        $att_res = $stmt->fetch_assoc();
        $present = (int)($att_res['present'] ?? 0);
        $absent = (int)($att_res['late_absent'] ?? 0);
        $total_att = $present + $absent;
        $data['charts']['attendance'] = [
            'type' => 'pie',
            'labels' => ['Present (>=3h)', 'Short/Absent'],
            'data' => [$present, $absent],
            'ratios' => [
                $total_att > 0 ? round(($present / $total_att) * 100, 1) : 0,
                $total_att > 0 ? round(($absent / $total_att) * 100, 1) : 0
            ]
        ];

        // 11. Global Task Approval Rate
        $approval_data = array_fill_keys(['approved', 'rejected', 'needs_improvement', 'pending_review'], 0);
        $stmt = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM tasks 
            WHERE status IN ('approved', 'rejected', 'needs_improvement', 'pending_review')
            GROUP BY status
        ");
        $total_reviewed = 0;
        while ($row = $stmt->fetch_assoc()) {
            if (isset($approval_data[$row['status']])) {
                $approval_data[$row['status']] = (int)$row['count'];
                $total_reviewed += $row['count'];
            }
        }
        $approval_counts = [];
        $approval_ratios = [];
        foreach ($approval_data as $status => $count) {
            $approval_counts[] = $count;
            $approval_ratios[] = $total_reviewed > 0 ? round(($count / $total_reviewed) * 100, 1) : 0;
        }
        $data['charts']['task_approval_rate'] = [
            'type' => 'doughnut',
            'labels' => ['Approved', 'Rejected', 'Needs Improvement', 'Pending Review'],
            'data' => $approval_counts,
            'ratios' => $approval_ratios
        ];

    } elseif ($user_role == 3) { // Supervisor
        // 1. Team Task Completion Trends (Last 6 Months)
        $completed_counts = array_fill(0, 6, 0);
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(completed_at, '%b %Y') as month,
                COUNT(*) as count,
                DATE_FORMAT(completed_at, '%Y-%m') as sort_key
            FROM tasks 
            WHERE assign_to IN (SELECT id FROM users WHERE supervisor_id = ?) 
            AND status = 'complete'
            AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $results = [];
        while ($row = $res->fetch_assoc()) {
            $results[$row['month']] = (int)$row['count'];
        }
        $labels = getLast6MonthsLabels();
        $data['months'] = $labels;
        $counts = [];
        $percentages = [];
        foreach ($labels as $index => $lbl) {
            $current = $results[$lbl] ?? 0;
            $counts[] = $current;
            
            // Calculate MoM percentage change
            if ($index > 0) {
                $previous = $counts[$index - 1];
                if ($previous > 0) {
                    $percentages[] = round((($current - $previous) / $previous) * 100, 1);
                } else {
                    $percentages[] = $current > 0 ? 100 : 0;
                }
            } else {
                $percentages[] = 0;
            }
        }
        $data['charts']['team_performance'] = [
            'type' => 'bar',
            'label' => 'Tasks Completed',
            'data' => $counts,
            'percentages' => $percentages,
            'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
            'borderColor' => 'rgb(16, 185, 129)'
        ];

        // 2. Attendance Summary
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN total_work_seconds >= 10800 THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN total_work_seconds < 10800 THEN 1 ELSE 0 END) as late_absent
            FROM attendance 
            WHERE user_id IN (SELECT id FROM users WHERE supervisor_id = ?)
            AND date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $att_res = $stmt->get_result()->fetch_assoc();
        $present = (int)($att_res['present'] ?? 0);
        $absent = (int)($att_res['late_absent'] ?? 0);
        $total_att = $present + $absent;
        $att_ratios = [
            $total_att > 0 ? round(($present / $total_att) * 100, 1) : 0,
            $total_att > 0 ? round(($absent / $total_att) * 100, 1) : 0
        ];
        $data['charts']['attendance'] = [
            'type' => 'pie',
            'labels' => ['Present (>=3h)', 'Short/Absent'],
            'data' => [$present, $absent],
            'ratios' => $att_ratios
        ];

        // 3. Task Status Distribution (Supervisor's interns only)
        $all_statuses = ['inprogress', 'complete', 'pending_review', 'approved', 'rejected', 'needs_improvement', 'expired'];
        $status_data = array_fill_keys($all_statuses, 0);
        
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as count 
            FROM tasks 
            WHERE assign_to IN (SELECT id FROM users WHERE supervisor_id = ?)
            GROUP BY status
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $total_tasks = 0;
        while ($row = $res->fetch_assoc()) {
            if (isset($status_data[$row['status']])) {
                $status_data[$row['status']] = (int)$row['count'];
                $total_tasks += $row['count'];
            }
        }
        
        $status_labels = [];
        $status_counts = [];
        $status_ratios = [];
        foreach ($status_data as $status => $count) {
            $status_labels[] = ucfirst(str_replace('_', ' ', $status));
            $status_counts[] = $count;
            $status_ratios[] = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
        }
        
        $data['charts']['task_status'] = [
            'type' => 'doughnut',
            'labels' => $status_labels,
            'data' => $status_counts,
            'ratios' => $status_ratios
        ];

        // 4. Task Approval Rate - Quality Metrics
        $approval_categories = ['approved', 'rejected', 'needs_improvement', 'pending_review'];
        $approval_data = array_fill_keys($approval_categories, 0);
        
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as count 
            FROM tasks 
            WHERE assign_to IN (SELECT id FROM users WHERE supervisor_id = ?)
            AND status IN ('approved', 'rejected', 'needs_improvement', 'pending_review')
            GROUP BY status
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $approval_res = $stmt->get_result();
        $total_reviewed = 0;
        while ($row = $approval_res->fetch_assoc()) {
            if (isset($approval_data[$row['status']])) {
                $approval_data[$row['status']] = (int)$row['count'];
                $total_reviewed += $row['count'];
            }
        }
        
        $approval_labels = [];
        $approval_counts = [];
        $approval_ratios = [];
        foreach ($approval_data as $status => $count) {
            $approval_labels[] = ucfirst(str_replace('_', ' ', $status));
            $approval_counts[] = $count;
            $approval_ratios[] = $total_reviewed > 0 ? round(($count / $total_reviewed) * 100, 1) : 0;
        }
        
        $data['charts']['task_approval_rate'] = [
            'type' => 'doughnut',
            'label' => 'Task Approval Rate',
            'labels' => $approval_labels,
            'data' => $approval_counts,
            'ratios' => $approval_ratios
        ];
    }

    echo json_encode($data);
}
?>
