<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($action === 'admin_task_stats') {
    // Get task status distribution
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
    $task_stats = [];
    $labels = [];
    $values = [];

    while ($row = $stmt->fetch_assoc()) {
        $labels[] = ucfirst($row['status']);
        $values[] = $row['count'];
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);
}

if ($action === 'admin_role_stats') {
    // Get user role distribution
    $stmt = $conn->query("SELECT user_role, COUNT(*) as count FROM users WHERE status = 1 GROUP BY user_role");
    $role_stats = [];
    $labels = [];
    $values = [];

    while ($row = $stmt->fetch_assoc()) {
        $role_name = $row['user_role'] == 1 ? 'Admins' : ($row['user_role'] == 2 ? 'Interns' : 'Supervisors');
        $labels[] = $role_name;
        $values[] = $row['count'];
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);
}

if ($action === 'intern_stats') {
    // Calculate completion rate
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completed_tasks
        FROM tasks WHERE assign_to = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $completion_rate = $result['total_tasks'] > 0 ?
        round(($result['completed_tasks'] / $result['total_tasks']) * 100) : 0;

    // Calculate average completion time (in days)
    $stmt = $conn->prepare("SELECT AVG(DATEDIFF(completed_at, created_at)) as avg_days 
                           FROM tasks WHERE assign_to = ? AND status = 'complete'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $time_result = $stmt->get_result()->fetch_assoc();
    $avg_time = $time_result['avg_days'] ? round($time_result['avg_days']) : 0;

    // Calculate total hours
    $stmt = $conn->prepare("SELECT COALESCE(SUM(duration), 0) as total_seconds 
                           FROM time_logs WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $hours_result = $stmt->get_result()->fetch_assoc();
    $total_hours = round($hours_result['total_seconds'] / 3600);

    echo json_encode([
        'success' => true,
        'completion_rate' => $completion_rate,
        'avg_completion_time' => $avg_time,
        'total_hours' => $total_hours
    ]);
}

if ($action === 'intern_monthly_stats') {
    // Get monthly task completion data for the last 6 months
    $stmt = $conn->prepare("SELECT 
        DATE_FORMAT(completed_at, '%b') as month,
        COUNT(*) as tasks
        FROM tasks 
        WHERE assign_to = ? AND status = 'complete' 
        AND completed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY MONTH(completed_at), YEAR(completed_at)
        ORDER BY YEAR(completed_at), MONTH(completed_at)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $months = [];
    $tasks = [];

    while ($row = $result->fetch_assoc()) {
        $months[] = $row['month'];
        $tasks[] = $row['tasks'];
    }

    echo json_encode([
        'success' => true,
        'months' => $months,
        'tasks' => $tasks
    ]);
}

if ($action === 'intern_weekly_hours') {
    // Get weekly hours for the current week
    $stmt = $conn->prepare("SELECT 
        DAYNAME(FROM_UNIXTIME(UNIX_TIMESTAMP(start_time))) as day,
        SUM(duration) as total_seconds
        FROM time_logs 
        WHERE user_id = ? 
        AND YEARWEEK(start_time) = YEARWEEK(NOW())
        GROUP BY DAYOFWEEK(start_time)
        ORDER BY DAYOFWEEK(start_time)");
    $stmt->bind_param("i", $user_id);
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
    // Calculate completion rate for supervisor's tasks
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completed_tasks
        FROM tasks WHERE created_by = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $completion_rate = $result['total_tasks'] > 0 ?
        round(($result['completed_tasks'] / $result['total_tasks']) * 100) : 0;

    echo json_encode([
        'success' => true,
        'completion_rate' => $completion_rate
    ]);
}

if ($action === 'supervisor_task_stats') {
    // First: Count statuses but exclude overdue pending tasks from 'pending'
    $stmt = $conn->prepare("
        SELECT
            CASE 
                WHEN status = 'pending' AND due_date < CURDATE() THEN 'expired'
                ELSE status
            END AS status_group,
            COUNT(*) as count
        FROM tasks
        WHERE created_by = ?
        GROUP BY status_group
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $values = [];

    // Initialize counts with zero
    $status_counts = [
        'complete' => 0,
        'working' => 0,
        'pending' => 0,
        'expired' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $status_counts[$row['status_group']] = $row['count'];
    }

    // Prepare labels and values in desired order
    $labels = ['Complete', 'Working', 'Pending', 'Expire'];
    $values = [
        $status_counts['complete'],
        $status_counts['working'],
        $status_counts['pending'],
        $status_counts['expired']
    ];

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);
}


if ($action === 'supervisor_team_performance') {

    // Fetch technologies based on assigned user's tech_id for tasks created by supervisor
    $sql = "
SELECT 
    tech.id AS tech_id,
    tech.name AS tech_name,
    COUNT(t.id) AS used_in_tasks
FROM technologies tech
LEFT JOIN users u 
    ON u.tech_id = tech.id
LEFT JOIN tasks t 
    ON t.assign_to = u.id
GROUP BY tech.id, tech.name
ORDER BY tech.name
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $techRows = $stmt->get_result();

    $labels = [];
    $completionRates = [];
    $onTimeRates = [];

    while ($row = $techRows->fetch_assoc()) {

        $techId = $row['tech_id'];
        $labels[] = $row['tech_name'];

        // Calculate stats for tasks supervisor created, grouped by assigned user's tech_id
        $sql2 = "
            SELECT 
                AVG(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) * 100 AS completion_rate,
                AVG(CASE WHEN status = 'complete' AND completed_at <= due_date THEN 1 ELSE 0 END) * 100 AS on_time_rate
            FROM tasks 
            WHERE created_by = ?
              AND assign_to IN (
                    SELECT id FROM users WHERE tech_id = ?
              )
        ";

        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("ii", $user_id, $techId);
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

    while ($row = $stmt->fetch_assoc()) {
        $months[] = $row['month'];
        $tasks[] = $row['task_count'];
    }

    echo json_encode([
        'success' => true,
        'months' => $months,
        'tasks' => $tasks
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

    while ($row = $stmt->fetch_assoc()) {
        $technologies[] = $row['tech_name'];
        $tasks[] = $row['task_count'];
    }

    echo json_encode([
        'success' => true,
        'technologies' => $technologies,
        'tasks' => $tasks
    ]);
}
