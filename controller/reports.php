<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

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
        foreach ($labels as $lbl) {
            $reg_counts[] = $results[$lbl] ?? 0;
        }
        $data['charts']['registrations'] = [
            'type' => 'line',
            'label' => 'New Registrations',
            'data' => $reg_counts,
            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
            'borderColor' => 'rgb(59, 130, 246)'
        ];

        // 2. Technology Distribution (Registrations)
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

        // 3. Overall Task Status (Admin/Manager sees all)
        $all_statuses = ['pending', 'working', 'complete', 'pending_review', 'approved', 'rejected', 'needs_improvement', 'expired'];
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

        // 4. Hiring Trends & Ratios (Last 6 Months)
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
        foreach ($labels as $index => $lbl) {
            $hired = $hired_results[$lbl] ?? 0;
            $total_reg = $reg_counts[$index];
            $hired_counts[] = $hired;
            $hiring_ratios[] = $total_reg > 0 ? round(($hired / $total_reg) * 100, 1) : 0;
        }

        $data['charts']['hiring_performance'] = [
            'type' => 'mixed', // Custom type to indicate dual-axis
            'labels' => $labels,
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Interns Hired',
                    'data' => $hired_counts,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'yAxisID' => 'y'
                ],
                [
                    'type' => 'line',
                    'label' => 'Hiring Ratio (%)',
                    'data' => $hiring_ratios,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1'
                ]
            ]
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
        foreach ($labels as $lbl) {
            $counts[] = $results[$lbl] ?? 0;
        }
        $data['charts']['team_performance'] = [
            'type' => 'bar',
            'label' => 'Tasks Completed',
            'data' => $counts,
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
        $data['charts']['attendance'] = [
            'type' => 'pie',
            'labels' => ['Present (>=3h)', 'Short/Absent'],
            'data' => [(int)($att_res['present'] ?? 0), (int)($att_res['late_absent'] ?? 0)]
        ];

        // 3. Task Status Distribution (Supervisor's interns only)
        $all_statuses = ['pending', 'working', 'complete', 'pending_review', 'approved', 'rejected', 'needs_improvement', 'expired'];
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

        // 3. Intern-wise Performance
        $intern_labels = [];
        $intern_tasks = [];
        $stmt = $conn->prepare("
            SELECT u.name, COUNT(t.id) as count 
            FROM users u 
            LEFT JOIN tasks t ON u.id = t.assign_to AND t.status = 'complete'
            WHERE u.supervisor_id = ? AND u.user_role = 2
            GROUP BY u.id
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $intern_res = $stmt->get_result();
        while ($row = $intern_res->fetch_assoc()) {
            $intern_labels[] = $row['name'];
            $intern_tasks[] = (int)$row['count'];
        }
        $data['charts']['intern_performance'] = [
            'type' => 'bar',
            'label' => 'Total Tasks Completed',
            'labels' => $intern_labels,
            'data' => $intern_tasks,
            'backgroundColor' => 'rgba(139, 92, 246, 0.6)',
            'borderColor' => 'rgb(139, 92, 246)'
        ];
    }

    echo json_encode($data);
}
?>
