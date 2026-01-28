<?php
session_start();

require "/home2/dawoodte/public_html/taskdesk/include/connection.php";
// This file should be called by cron job
function markAutoAttendance() {
    global $conn;
    
    $currentDate = date('Y-m-d');
    
    // Get all users
    $usersSql = "SELECT id FROM users WHERE status = 1 AND user_role = 2"; 
    $usersResult = $conn->query($usersSql);
    
    while ($user = $usersResult->fetch_assoc()) {
        $userId = $user['id'];
        
        // Check if user has any time logs for today
        $timeLogSql = "SELECT * FROM time_logs WHERE user_id = ? AND DATE(started_at) = ?";
        $timeLogStmt = $conn->prepare($timeLogSql);
        $timeLogStmt->bind_param("is", $userId, $currentDate);
        $timeLogStmt->execute();
        $timeLogResult = $timeLogStmt->get_result();
        
        // Check if attendance already exists for today
        $attendanceSql = "SELECT * FROM attendance WHERE user_id = ? AND date = ?";
        $attendanceStmt = $conn->prepare($attendanceSql);
        $attendanceStmt->bind_param("is", $userId, $currentDate);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();
        
        // If no time logs and no attendance record, mark as absent
        if ($timeLogResult->num_rows == 0 && $attendanceResult->num_rows == 0) {
            $insertSql = "INSERT INTO attendance (user_id, date, status, total_time, formatted_time) VALUES (?, ?, 'absent', 0, '00:00:00')";
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