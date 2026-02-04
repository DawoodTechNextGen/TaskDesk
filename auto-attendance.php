<?php
session_start();

require_once __DIR__ . "/include/connection.php";
// This file should be called by cron job
function markAutoAttendance() {
    global $conn;
    
    $currentDate = date('Y-m-d');
    
    // Get all approved interns
    $usersSql = "SELECT id, created_at, internship_type, internship_duration FROM users WHERE status = 1 AND user_role = 2"; 
    $usersResult = $conn->query($usersSql);
    
    $today = new DateTime($currentDate);

    while ($user = $usersResult->fetch_assoc()) {
        $userId = $user['id'];
        
        // Calculate completion date
        $start_date = new DateTime($user['created_at']);
        $duration_weeks = 12;
        if (!empty($user['internship_duration'])) {
            if ($user['internship_duration'] === '4 weeks') $duration_weeks = 4;
            elseif ($user['internship_duration'] === '8 weeks') $duration_weeks = 8;
            elseif ($user['internship_duration'] === '12 weeks') $duration_weeks = 12;
        } else {
            $duration_weeks = ($user['internship_type'] == 0) ? 4 : 12;
        }
        
        $completion_date = clone $start_date;
        $completion_date->modify("+$duration_weeks weeks");
        $completion_date->setTime(0, 0, 0);

        // If internship is complete, skip marking absent
        if ($today > $completion_date) {
            continue;
        }

        // Check if user has any time logs for today
        $timeLogSql = "SELECT id FROM time_logs WHERE user_id = ? AND DATE(start_time) = ? LIMIT 1";
        $timeLogStmt = $conn->prepare($timeLogSql);
        $timeLogStmt->bind_param("is", $userId, $currentDate);
        $timeLogStmt->execute();
        $timeLogResult = $timeLogStmt->get_result();
        
        // Check if attendance already exists for today
        $attendanceSql = "SELECT id FROM attendance WHERE user_id = ? AND date = ? LIMIT 1";
        $attendanceStmt = $conn->prepare($attendanceSql);
        $attendanceStmt->bind_param("is", $userId, $currentDate);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();
        
        // If no time logs and no attendance record, mark as absent
        if ($timeLogResult->num_rows == 0 && $attendanceResult->num_rows == 0) {
            $insertSql = "INSERT INTO attendance (user_id, task_id, date, status, total_work_seconds) VALUES (?, 0, ?, 'absent', 0)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("is", $userId, $currentDate);
            $insertStmt->execute();
            
            // Log the auto-attendance action
            error_log("Auto-marked user $userId as absent for $currentDate");
        }
    }
    
    return ['success' => true, 'message' => 'Auto-attendance completed'];
}

// Run the function
$result = markAutoAttendance();
echo json_encode($result);
?>