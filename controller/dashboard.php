<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

function getWorkingDays($startDate, $endDate) {
    if ($startDate > $endDate) return 0;
    $workingDays = 0;
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $dayOfWeek = (int)$currentDate->format('N');
        if ($dayOfWeek < 6) { // 1=Mon, 5=Fri. Excludes Sat(6) and Sun(7)
            $workingDays++;
        }
        $currentDate->modify('+1 day');
    }
    return $workingDays;
}

if ($action === 'admin_task_stats') {
    // Get task status distribution
    $stmt = $conn->query("
        SELECT
            status AS status_group,
            COUNT(*) as count
        FROM tasks
        GROUP BY status_group
    ");
    $total_tasks = 0;
    while ($row = $stmt->fetch_assoc()) {
        $task_stats[$row['status_group']] = $row['count'];
        $total_tasks += $row['count'];
    }

    $desired_order = ['pending', 'working', 'complete', 'expired', 'needs_improvement', 'pending_review', 'approved', 'rejected'];
    foreach ($desired_order as $status) {
        if (isset($task_stats[$status]) || in_array($status, ['pending', 'working', 'complete', 'expired'])) {
            $count = $task_stats[$status] ?? 0;
            $labels[] = ucfirst(str_replace('_', ' ', $status));
            $values[] = $count;
            $ratios[] = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
        }
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'ratios' => $ratios
    ]);
}

if ($action === 'admin_role_stats') {
    // Get user role distribution
    $stmt = $conn->query("SELECT user_role, COUNT(*) as count FROM users WHERE status = 1 GROUP BY user_role");
    $total_users = 0;
    $temp_stats = [];
    while ($row = $stmt->fetch_assoc()) {
        $temp_stats[$row['user_role']] = $row['count'];
        $total_users += $row['count'];
    }

    $role_map = [1 => 'Admins', 2 => 'Interns', 3 => 'Supervisors', 4 => 'Managers'];
    foreach ($role_map as $role_id => $role_name) {
        $count = $temp_stats[$role_id] ?? 0;
        $labels[] = $role_name;
        $values[] = $count;
        $ratios[] = $total_users > 0 ? round(($count / $total_users) * 100, 1) : 0;
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'ratios' => $ratios
    ]);
}

if ($action === 'intern_stats') {
    $requested_id = $_GET['target_userid'] ?? null;
    $calc_user_id = $user_id;
    if ($requested_id && in_array($user_role, [1, 3, 4])) {
        $calc_user_id = $requested_id;
    }
    
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'complete' AND (completed_at <= due_date OR due_date IS NULL) THEN 1 ELSE 0 END) as timely_completed_tasks
        FROM tasks WHERE assign_to = ?");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $completion_rate = $result['total_tasks'] > 0 ?
        round(($result['completed_tasks'] / $result['total_tasks']) * 100) : 0;
    
    $on_time_rate = $result['total_tasks'] > 0 ?
        round(($result['timely_completed_tasks'] / $result['total_tasks']) * 100) : 0;

    // Calculate average completion time (in days)
    $stmt = $conn->prepare("SELECT AVG(DATEDIFF(completed_at, created_at)) as avg_days 
                           FROM tasks WHERE assign_to = ? AND status = 'complete'");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $time_result = $stmt->get_result()->fetch_assoc();
    $avg_time = $time_result['avg_days'] ? round($time_result['avg_days']) : 0;

    // Calculate total hours
    $stmt = $conn->prepare("SELECT COALESCE(SUM(duration), 0) as total_seconds 
                           FROM time_logs WHERE user_id = ?");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $hours_result = $stmt->get_result()->fetch_assoc();
    $total_hours = round($hours_result['total_seconds'] / 3600);

    // Calculate attendance percentage and week info
    // Get user creation date, duration and type
    $stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $user_res = $stmt->get_result()->fetch_assoc();
    $created_at = new DateTime($user_res['created_at']);
    $created_at->setTime(0, 0, 0); // Normalize to start of day

    // Calculate completion date
    $duration_weeks = 12;
    if (!empty($user_res['internship_duration'])) {
        if ($user_res['internship_duration'] === '4 weeks') $duration_weeks = 4;
        elseif ($user_res['internship_duration'] === '8 weeks') $duration_weeks = 8;
        elseif ($user_res['internship_duration'] === '12 weeks') $duration_weeks = 12;
    } else {
        $duration_weeks = ($user_res['internship_type'] == 0) ? 4 : 12;
    }
    
    $completion_date = clone $created_at;
    $completion_date->modify("+$duration_weeks weeks");

    $now = new DateTime();
    $now->setTime(0, 0, 0); // Normalize to start of today

    // End date for calculation is earlier of today or completion date
    $calc_end_date = min($now, $completion_date);
    
    $total_days = getWorkingDays($created_at, $calc_end_date);
    
    // Calculate current week (cap at total weeks)
    $days_since_start = $created_at->diff($now)->days;
    $current_week = min(floor($days_since_start / 7) + 1, $duration_weeks);
    
    // Total weeks
    $total_weeks = $duration_weeks;
    
    // Cap current week at total weeks if needed, or let it exceed to show overtime? 
    // Usually "Week 5 of 4" is informative. Let's keep it real.

    // Count present days (>= 3 hours OR task completed)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT date) as present_days FROM (
            SELECT DATE(date) as date FROM attendance WHERE user_id = ? AND total_work_seconds >= 10800
            UNION
            SELECT DATE(completed_at) as date FROM tasks WHERE assign_to = ? AND status = 'complete'
        ) as combined_attendance
    ");
    $stmt->bind_param("ii", $calc_user_id, $calc_user_id);
    $stmt->execute();
    $attendance_result = $stmt->get_result()->fetch_assoc();
    $present_days = $attendance_result['present_days'];

    $attendance_percentage = $total_days > 0 ?
        round(($present_days / $total_days) * 100) : 0;

    echo json_encode([
        'success' => true,
        'completion_rate' => $completion_rate,
        'on_time_rate' => $on_time_rate,
        'avg_completion_time' => $avg_time,
        'total_hours' => $total_hours,
        'attendance_percentage' => $attendance_percentage,
        'present_days' => $present_days,
        'working_days_passed' => $total_days,
        'total_working_days' => $total_working_days = getWorkingDays($created_at, $completion_date),
        'internship_progress_percentage' => $total_working_days > 0 ? round(($total_days / $total_working_days) * 100) : 0,
        'current_week' => $current_week,
        'total_weeks' => $total_weeks
    ]);
}

if ($action === 'intern_monthly_stats') {
    $requested_id = $_GET['target_userid'] ?? null;
    $calc_user_id = $user_id;
    if ($requested_id && in_array($user_role, [1, 3, 4])) {
        $calc_user_id = $requested_id;
    }
    
    // Get monthly task completion data for the last 6 months
    $stmt = $conn->prepare("SELECT 
        DATE_FORMAT(completed_at, '%b') as month,
        COUNT(*) as tasks
        FROM tasks 
        WHERE assign_to = ? AND status = 'complete' 
        AND completed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY MONTH(completed_at), YEAR(completed_at)
        ORDER BY YEAR(completed_at), MONTH(completed_at)");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $months = [];
    $tasks = [];
    $percentages = [];

    while ($row = $result->fetch_assoc()) {
        $months[] = $row['month'];
        $current_tasks = $row['tasks'];
        $tasks[] = $current_tasks;
        
        $index = count($tasks) - 1;
        if ($index > 0) {
            $prev = $tasks[$index - 1];
            if ($prev > 0) {
                $percentages[] = round((($current_tasks - $prev) / $prev) * 100, 1);
            } else {
                $percentages[] = $current_tasks > 0 ? 100 : 0;
            }
        } else {
            $percentages[] = 0;
        }
    }

    echo json_encode([
        'success' => true,
        'months' => $months,
        'tasks' => $tasks,
        'percentages' => $percentages
    ]);
}

if ($action === 'intern_weekly_hours') {
    $requested_id = $_GET['target_userid'] ?? null;
    $calc_user_id = $user_id;
    if ($requested_id && in_array($user_role, [1, 3, 4])) {
        $calc_user_id = $requested_id;
    }
    
    // Get weekly hours for the current week
    $stmt = $conn->prepare("SELECT 
    DAYNAME(start_time) AS day,
    SUM(duration) AS total_seconds
FROM time_logs
WHERE user_id = ?
  AND YEAR(start_time) = YEAR(NOW())
  AND WEEK(start_time, 1) = WEEK(NOW(), 1)
GROUP BY DAYOFWEEK(start_time)
ORDER BY DAYOFWEEK(start_time);
");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $hours = array_fill(0, 7, 0);

    while ($row = $result->fetch_assoc()) {
        $day_index = array_search(substr($row['day'], 0, 3), $days);
        if ($day_index !== false) {
            $hours[$day_index] = round($row['total_seconds'] / 3600, 1);
        }
    }

    echo json_encode([
        'success' => true,
        'days' => $days,
        'hours' => $hours
    ]);
}

if ($action === 'supervisor_stats') {
    // Calculate completion rate for supervisor's tasks assigned to their managed interns
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'complete' AND (completed_at <= due_date OR due_date IS NULL) THEN 1 ELSE 0 END) as timely_completed_tasks
        FROM tasks 
        WHERE assign_to IN (SELECT id FROM users WHERE supervisor_id = ?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $completion_rate = $result['total_tasks'] > 0 ?
        round(($result['completed_tasks'] / $result['total_tasks']) * 100) : 0;
    
    $on_time_rate = $result['total_tasks'] > 0 ?
        round(($result['timely_completed_tasks'] / $result['total_tasks']) * 100) : 0;

    echo json_encode([
        'success' => true,
        'completion_rate' => $completion_rate,
        'on_time_rate' => $on_time_rate
    ]);
}

if ($action === 'supervisor_task_stats') {
    // First: Count statuses but exclude overdue pending tasks from 'pending'
        $sql = "SELECT
            status AS status_group,
            COUNT(*) as count
        FROM tasks";
    
    if ($user_role != 1 && $user_role != 4) {
        $sql .= " WHERE assign_to IN (SELECT id FROM users WHERE supervisor_id = ?)";
    } else {
        $sql .= " WHERE 1=1";
    }
    
    $sql .= " GROUP BY status_group";
    $stmt = $conn->prepare($sql);
    
    if ($user_role != 1 && $user_role != 4) {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $values = [];
    $ratios = [];
    $total_tasks = 0;

    // Initialize counts with zero
    $status_counts = [
        'complete' => 0,
        'working' => 0,
        'pending' => 0,
        'expired' => 0,
        'needs_improvement' => 0,
        'pending_review' => 0,
        'approved' => 0,
        'rejected' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $status_counts[$row['status_group']] = $row['count'];
        $total_tasks += $row['count'];
    }

    // Prepare labels and values in desired order
    $ordered_statuses = ['pending', 'working', 'complete', 'expired', 'needs_improvement', 'pending_review', 'approved', 'rejected'];
    foreach ($ordered_statuses as $status) {
        if (isset($status_counts[$status]) || in_array($status, ['pending', 'working', 'complete', 'expired'])) {
            $count = $status_counts[$status] ?? 0;
            $labels[] = ucfirst(str_replace('_', ' ', $status));
            $values[] = $count;
            $ratios[] = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
        }
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'ratios' => $ratios
    ]);
}
if ($action === 'supervisor_team_performance') {
    // Fetch technologies linked to interns managed by this supervisor
    $sql = "
SELECT 
    tech.id AS tech_id,
    tech.name AS tech_name,
    COUNT(t.id) AS used_in_tasks
FROM technologies tech
JOIN users u 
    ON u.tech_id = tech.id
JOIN tasks t 
    ON t.assign_to = u.id
WHERE u.supervisor_id = ?
GROUP BY tech.id, tech.name
ORDER BY tech.name
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $techRows = $stmt->get_result();

    $labels = [];
    $completionRates = [];
    $onTimeRates = [];

    while ($row = $techRows->fetch_assoc()) {

        $techId = $row['tech_id'];
        $labels[] = $row['tech_name'];

        // Calculate stats for tasks assigned to interns managed by this supervisor in this technology
        $sql2 = "
            SELECT 
                AVG(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) * 100 AS completion_rate,
                AVG(CASE WHEN status = 'complete' AND (completed_at <= due_date OR due_date IS NULL) THEN 1 ELSE 0 END) * 100 AS on_time_rate
            FROM tasks 
            WHERE assign_to IN (
                SELECT id FROM users WHERE tech_id = ? AND supervisor_id = ?
            )
        ";

        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("ii", $techId, $user_id);
        $stmt2->execute();
        $result = $stmt2->get_result()->fetch_assoc();

        $completionRates[] = round($result['completion_rate'] ?? 0);
        $onTimeRates[]     = round($result['on_time_rate'] ?? 0);
    }

    echo json_encode([
        "success" => true,
        "labels" => $labels,
        "completion" => $completionRates,
        "on_time" => $onTimeRates
    ]);
}




// Add these new actions to your existing dashboard.php file

if ($action === 'admin_monthly_trends') {
    // Get monthly task trends for the last 6 months
    $stmt = $conn->query("SELECT
    DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH), '%b') AS month,
    COALESCE(t.task_count, 0) AS task_count
FROM
    (
        SELECT 0 AS month_offset UNION ALL
        SELECT 1 UNION ALL
        SELECT 2 UNION ALL
        SELECT 3 UNION ALL
        SELECT 4 UNION ALL
        SELECT 5
    ) AS seq
LEFT JOIN
    (
        SELECT 
            YEAR(created_at) AS year,
            MONTH(created_at) AS month,
            COUNT(*) AS task_count
        FROM tasks
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
    ) AS t
    ON YEAR(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH)) = t.year
    AND MONTH(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH)) = t.month
ORDER BY
    YEAR(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH)),
    MONTH(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH));
");

    $months = [];
    $tasks = [];
    $percentages = [];

    while ($row = $stmt->fetch_assoc()) {
        $months[] = $row['month'];
        $current_tasks = $row['task_count'];
        $tasks[] = $current_tasks;
        
        $index = count($tasks) - 1;
        if ($index > 0) {
            $prev = $tasks[$index - 1];
            if ($prev > 0) {
                $percentages[] = round((($current_tasks - $prev) / $prev) * 100, 1);
            } else {
                $percentages[] = $current_tasks > 0 ? 100 : 0;
            }
        } else {
            $percentages[] = 0;
        }
    }

    echo json_encode([
        'success' => true,
        'months' => $months,
        'tasks' => $tasks,
        'percentages' => $percentages
    ]);
}

if ($action === 'admin_tech_tasks') {
    // Get technology-wise task distribution
    $stmt = $conn->query("SELECT 
    t.name as tech_name,
    COUNT(task.id) as task_count
FROM tasks task
JOIN users u ON task.assign_to = u.id
JOIN technologies t ON u.tech_id = t.id
GROUP BY t.id, t.name
ORDER BY task_count DESC
LIMIT 5");

    $technologies = [];
    $tasks = [];
    $ratios = [];
    $total_tasks = 0;

    $captured_data = [];
    while ($row = $stmt->fetch_assoc()) {
        $captured_data[] = $row;
        $total_tasks += $row['task_count'];
    }

    foreach ($captured_data as $row) {
        $technologies[] = $row['tech_name'];
        $count = $row['task_count'];
        $tasks[] = $count;
        $ratios[] = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
    }

    echo json_encode([
        'success' => true,
        'technologies' => $technologies,
        'tasks' => $tasks,
        'ratios' => $ratios
    ]);
}
// dashboard.php میں existing actions کے بعد اضافہ کریں

if ($action === 'manager_registration_stats') {
    // Get registration status distribution
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM registrations GROUP BY status");
    $reg_stats = [];
    $labels = [];
    $values = [];

    $status_names = [
        'new' => 'New',
        'contact' => 'Contacted',
        'hire' => 'Hired',
        'rejected' => 'Rejected'
    ];

    // Initialize all statuses with 0
    foreach ($status_names as $key => $name) {
        $labels[] = $name;
        $values[$key] = 0;
    }

    // Fill actual values
    while ($row = $stmt->fetch_assoc()) {
        if (isset($status_names[$row['status']])) {
            $values[$row['status']] = $row['count'];
        }
    }

    $total_registrations = 0;
    foreach($values as $v) $total_registrations += $v;

    $ordered_values = [
        $values['new'],
        $values['contact'],
        $values['hire'],
        $values['rejected']
    ];

    $ratios = [];
    foreach ($ordered_values as $val) {
        $ratios[] = $total_registrations > 0 ? round(($val / $total_registrations) * 100, 1) : 0;
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $ordered_values,
        'raw_values' => $values,
        'ratios' => $ratios
    ]);
}

if ($action === 'manager_monthly_registrations') {
    // Get monthly registration trends for the last 6 months
    $stmt = $conn->query("SELECT
        DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH), '%b') AS month,
        COALESCE(r.reg_count, 0) AS reg_count
    FROM
        (
            SELECT 0 AS month_offset UNION ALL
            SELECT 1 UNION ALL
            SELECT 2 UNION ALL
            SELECT 3 UNION ALL
            SELECT 4 UNION ALL
            SELECT 5
        ) AS seq
    LEFT JOIN
        (
            SELECT 
                YEAR(created_at) AS year,
                MONTH(created_at) AS month,
                COUNT(*) AS reg_count
            FROM registrations
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
        ) AS r
        ON YEAR(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH)) = r.year
        AND MONTH(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH)) = r.month
    ORDER BY
        YEAR(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH)),
        MONTH(DATE_SUB(CURDATE(), INTERVAL seq.month_offset MONTH));");

    $months = [];
    $registrations = [];
    $percentages = [];

    while ($row = $stmt->fetch_assoc()) {
        $months[] = $row['month'];
        $current_reg = $row['reg_count'];
        $registrations[] = $current_reg;
        
        $index = count($registrations) - 1;
        if ($index > 0) {
            $prev = $registrations[$index - 1];
            if ($prev > 0) {
                $percentages[] = round((($current_reg - $prev) / $prev) * 100, 1);
            } else {
                $percentages[] = $current_reg > 0 ? 100 : 0;
            }
        } else {
            $percentages[] = 0;
        }
    }

    echo json_encode([
        'success' => true,
        'months' => $months,
        'registrations' => $registrations,
        'percentages' => $percentages
    ]);
}

if ($action === 'manager_tech_registrations') {
    // Get technology-wise registration distribution
    $stmt = $conn->query("SELECT 
        t.name as tech_name,
        COUNT(r.id) as reg_count
    FROM registrations r
    LEFT JOIN technologies t ON r.technology_id = t.id
    GROUP BY t.id, t.name
    ORDER BY reg_count DESC
    LIMIT 5");

    $technologies = [];
    $registrations = [];
    $ratios = [];
    $total_registrations = 0;

    $captured_data = [];
    while ($row = $stmt->fetch_assoc()) {
        $captured_data[] = $row;
        $total_registrations += $row['reg_count'];
    }

    foreach ($captured_data as $row) {
        $technologies[] = $row['tech_name'] ?? 'Not Specified';
        $count = $row['reg_count'];
        $registrations[] = $count;
        $ratios[] = $total_registrations > 0 ? round(($count / $total_registrations) * 100, 1) : 0;
    }

    echo json_encode([
        'success' => true,
        'technologies' => $technologies,
        'registrations' => $registrations,
        'ratios' => $ratios
    ]);
}

if ($action === 'manager_internship_type_stats') {
    // Get internship type distribution
    $stmt = $conn->query("SELECT 
        internship_type,
        COUNT(*) as count
    FROM registrations
    GROUP BY internship_type");

    $labels = ['Free Intern', 'Paid Intern'];
    $values = [0, 0];

    while ($row = $stmt->fetch_assoc()) {
        $index = (int)$row['internship_type'];
        if ($index >= 0 && $index <= 1) {
            $values[$index] = $row['count'];
        }
    }

    $total = array_sum($values);
    $ratios = [];
    foreach ($values as $val) {
        $ratios[] = $total > 0 ? round(($val / $total) * 100, 1) : 0;
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'ratios' => $ratios
    ]);
}

if ($action === 'manager_recent_registrations') {
    // Get recent registrations
    $stmt = $conn->query("SELECT 
        r.name,
        r.email,
        r.status,
        DATE(r.created_at) as created_at,
        t.name as technology
    FROM registrations r
    LEFT JOIN technologies t ON r.technology_id = t.id
    ORDER BY r.created_at DESC
    LIMIT 6");

    $registrations = [];
    while ($row = $stmt->fetch_assoc()) {
        $registrations[] = $row;
    }

    echo json_encode([
        'success' => true,
        'registrations' => $registrations
    ]);
}

if ($action === 'manager_overview_stats') {
    // Get overview statistics
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $month_start = date('Y-m-01');
    
    // Today's registrations
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE DATE(created_at) = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $today_result = $stmt->get_result()->fetch_assoc();
    
    // This week's registrations
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE DATE(created_at) >= ?");
    $stmt->bind_param("s", $week_start);
    $stmt->execute();
    $week_result = $stmt->get_result()->fetch_assoc();
    
    // This month's registrations
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE DATE(created_at) >= ?");
    $stmt->bind_param("s", $month_start);
    $stmt->execute();
    $month_result = $stmt->get_result()->fetch_assoc();
    
    // Total registrations and hired
    $stmt = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'hire' THEN 1 ELSE 0 END) as hired
    FROM registrations");
    $total_result = $stmt->fetch_assoc();
    
    $hiring_rate = $total_result['total'] > 0 ? 
        round(($total_result['hired'] / $total_result['total']) * 100) : 0;

    echo json_encode([
        'success' => true,
        'today' => $today_result['count'],
        'week' => $week_result['count'],
        'month' => $month_result['count'],
        'total' => $total_result['total'],
        'hired' => $total_result['hired'],
        'hiring_rate' => $hiring_rate
    ]);
}

if ($action === 'manager_registration_counts') {
    // Get all registration counts by status
    $stmt = $conn->query("SELECT 
        status,
        COUNT(*) as count
    FROM registrations
    GROUP BY status");
    
    $counts = [
        'new' => 0,
        'contact' => 0,
        'hire' => 0,
        'rejected' => 0,
        'total' => 0
    ];
    
    while ($row = $stmt->fetch_assoc()) {
        $counts[$row['status']] = $row['count'];
        $counts['total'] += $row['count'];
    }

    echo json_encode([
        'success' => true,
        'counts' => $counts
    ]);
}

if ($action === 'supervisor_intern_attendance') {
    $sql = "SELECT 
                u.id, 
                u.name, 
                u.email,
                t.name as technology,
                u.created_at,
                COUNT(DISTINCT a.id) as total_attendance_rows,
                COUNT(DISTINCT CASE 
                    WHEN a.total_work_seconds >= 10800 OR task.id IS NOT NULL THEN a.date 
                    ELSE NULL 
                END) as present_days
            FROM users u
            LEFT JOIN technologies t ON u.tech_id = t.id
            LEFT JOIN attendance a ON u.id = a.user_id
            LEFT JOIN tasks task ON u.id = task.assign_to AND task.status = 'complete' AND DATE(task.completed_at) = DATE(a.date)
            WHERE u.user_role = 2";
            
    if ($user_role != 1 && $user_role != 4) {
        $sql .= " AND u.supervisor_id = ?";
    }
            
    $sql .= " GROUP BY u.id";
            
    $stmt = $conn->prepare($sql);
    
    if ($user_role != 1 && $user_role != 4) {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $interns = [];
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    while ($row = $result->fetch_assoc()) {
        $createdAt = new DateTime($row['created_at']);
        $createdAt->setTime(0, 0, 0);
        
        // Calculate total working days up to today
        $total_working_days = getWorkingDays($createdAt, $today);
        
        $row['total_days'] = $total_working_days;
        $row['attendance_percentage'] = $total_working_days > 0 
            ? round(($row['present_days'] / $total_working_days) * 100) 
            : 0;
        $interns[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'interns' => $interns
    ]);
}
if ($action === "intern_attendance_logs") {
    // Get tasks assigned to this intern with their attendance records
    $sql = "SELECT DISTINCT
                t.id as task_id,
                t.title as task_name,
                t.description as task_description,
                t.created_at as task_created,
                t.due_date,
                t.status as task_status
            FROM tasks t
            WHERE t.assign_to = ?
            ORDER BY t.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tasks_result = $stmt->get_result();
    
    $tasks = [];
    while ($task = $tasks_result->fetch_assoc()) {
        // Get attendance records for this specific task
        $attendance_sql = "SELECT 
                            date, 
                            total_work_seconds 
                        FROM attendance 
                        WHERE user_id = ? AND task_id = ?
                        ORDER BY date DESC";
        
        $att_stmt = $conn->prepare($attendance_sql);
        $att_stmt->bind_param("ii", $user_id, $task['task_id']);
        $att_stmt->execute();
        $att_result = $att_stmt->get_result();
        
        $daily_logs = [];
        $total_hours = 0;
        $present_days = 0;
        $total_days = 0;
        
        while ($log = $att_result->fetch_assoc()) {
            $hours = round($log['total_work_seconds'] / 3600, 2);
            $total_hours += $hours;
            $total_days++;
            
            $task_completed_date = ($task['task_status'] === 'complete' && $task['due_date']) ? date('Y-m-d', strtotime($task['completed_at'] ?? '')) : '';
            $is_present = $log['total_work_seconds'] >= 10800 || ($task_completed_date === $log['date']);
            if ($is_present) $present_days++;
            
            $daily_logs[] = [
                'date' => $log['date'],
                'work_time' => $hours . 'h',
                'status' => $is_present ? 'Present' : 'Absent',
                'progress_percent' => min(($hours / 3) * 100, 100)
            ];
        }
        
        $task['daily_logs'] = $daily_logs;
        $task['total_hours'] = round($total_hours, 2) . 'h';
        $task['attendance_rate'] = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;
        $tasks[] = $task;
    }
    
    echo json_encode([
        "success" => true,
        "tasks" => $tasks
    ]);
}

if ($action === "intern_daily_history") {
    // If Admin/Manager/Supervisor, they can view specific intern's history
    $requested_id = $_GET['target_userid'] ?? null;
    $target_user_id = $user_id; // default to self

    if ($requested_id && in_array($user_role, [1, 3, 4])) {
        $target_user_id = $requested_id;
    }
    
    // Use target_user_id for all queries below, but keep $user_id for session-based logic if needed
    // Actually, let's just override $user_id for this block locally
    $calc_user_id = $target_user_id;

    // Get user creation date and duration
    $stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $user_res = $stmt->get_result()->fetch_assoc();
    
    $start_date = new DateTime($user_res['created_at']);
    $start_date->setTime(0, 0, 0); // Start of day

    // Calculate completion date
    $duration_weeks = 12;
    if (!empty($user_res['internship_duration'])) {
        if ($user_res['internship_duration'] === '4 weeks') $duration_weeks = 4;
        elseif ($user_res['internship_duration'] === '8 weeks') $duration_weeks = 8;
        elseif ($user_res['internship_duration'] === '12 weeks') $duration_weeks = 12;
    } else {
        $duration_weeks = ($user_res['internship_type'] == 0) ? 4 : 12;
    }
    
    $completion_date = clone $start_date;
    $completion_date->modify("+$duration_weeks weeks");

    $today = new DateTime();
    $today->setTime(0, 0, 0);

    // End date is the earlier of today or completion date
    $end_display_date = min($today, $completion_date);
    
    // For DatePeriod, the end date is exclusive, so add 1 day to include the last day
    $iter_end_date = clone $end_display_date;
    $iter_end_date->modify('+1 day');

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start_date, $interval, $iter_end_date);



    // Get attendance records
    $att_sql = "SELECT date, total_work_seconds, status FROM attendance WHERE user_id = ?";
    $stmt = $conn->prepare($att_sql);
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $att_result = $stmt->get_result();
    
    $attendance_map = [];
    while ($row = $att_result->fetch_assoc()) {
        $attendance_map[$row['date']] = $row;
    }

    // Get tasks worked on per day
    $tasks_sql = "SELECT 
        DATE(tl.start_time) as date, 
        t.title as task_name,
        SUM(tl.duration) as duration
    FROM time_logs tl
    JOIN tasks t ON tl.task_id = t.id
    WHERE tl.user_id = ?
    GROUP BY DATE(tl.start_time), t.id";
    
    $stmt = $conn->prepare($tasks_sql);
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $tasks_result = $stmt->get_result();

    $tasks_map = [];
    while ($row = $tasks_result->fetch_assoc()) {
        $date = $row['date'];
        if (!isset($tasks_map[$date])) {
            $tasks_map[$date] = [];
        }
        $seconds = $row['duration'];
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        $duration_str = "$h hour: $m min: $s sec";
        $tasks_map[$date][] = [
            'name' => $row['task_name'],
            'duration' => $duration_str,
            'seconds' => $seconds
        ];
    }

    // Get completed tasks dates and titles
    $completed_tasks_sql = "SELECT DATE(completed_at) as date, title FROM tasks WHERE assign_to = ? AND status = 'complete'";
    $stmt = $conn->prepare($completed_tasks_sql);
    $stmt->bind_param("i", $calc_user_id);
    $stmt->execute();
    $completed_result = $stmt->get_result();
    $completed_dates = [];
    while ($row = $completed_result->fetch_assoc()) {
        $date = $row['date'];
        $completed_dates[$date] = true;
        
        // Add to tasks_map for display
        if (!isset($tasks_map[$date])) {
            $tasks_map[$date] = [];
        }
        
        // Check if this task is already in the list
        $found = false;
        foreach ($tasks_map[$date] as $t) {
            if ($t['name'] === $row['title']) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $tasks_map[$date][] = [
                'name' => $row['title'],
                'duration' => '(Completed)'
            ];
        }
    }

    $history = [];
    
    foreach ($period as $dt) {
        $date_str = $dt->format("Y-m-d");
        $is_weekend = (date('N', strtotime($date_str)) >= 6);
        
        $status = 'Absent';
        $total_seconds = 0;
        $work_time = '00:00:00';
        $progress = 0;

        if (isset($attendance_map[$date_str])) {
            $total_seconds = $attendance_map[$date_str]['total_work_seconds'];
            $status = ($attendance_map[$date_str]['status'] === 'Present') ? 'Present' : $status;
        }

        // Add task duration sum if not already included
        $task_seconds = 0;
        if (isset($tasks_map[$date_str])) {
            foreach ($tasks_map[$date_str] as $t) {
                if (isset($t['seconds'])) {
                    $task_seconds += $t['seconds'];
                }
            }
        }
        
        $total_seconds = max($total_seconds, $task_seconds);
        $work_time = gmdate("H:i:s", $total_seconds);
        $progress = min(($total_seconds / 10800) * 100, 100);
        
        if ($total_seconds >= 10800 || isset($completed_dates[$date_str])) {
            $status = 'Present';
        }

        // Logic override: If weekend and no work, skip or mark as 'Weekend' instead of Absent?
        // Requirement says "otherwise absent". usually weekends are not marked 'Absent' in a negative way, but technically they are.
        // Let's keep it simple: Present/Absent based on work. Maybe add a flag for weekend.
        
        $history[] = [
            'date' => $date_str,
            'status' => $status,
            'work_time' => $work_time,
            'progress_percent' => $progress,
            'is_weekend' => $is_weekend,
            'tasks' => $tasks_map[$date_str] ?? []
        ];
    }
    
    // Sort by date desc
    usort($history, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    echo json_encode([
        "success" => true,
        "history" => $history
    ]);
}