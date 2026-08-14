<?php
session_start();

require_once __DIR__ . "/include/connection.php";
require_once __DIR__ . "/include/internship_helper.php";
date_default_timezone_set('Asia/Karachi');

// This file should be called by cron job
function markAutoAttendance() {
    global $conn;

    $currentDate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $presentThresholdSeconds = 10800;

    // Get all approved interns
    $usersSql = "SELECT id, created_at, internship_type, internship_duration, freeze_status FROM users WHERE status = 1 AND user_role = 2";
    $usersResult = $conn->query($usersSql);

    $today = new DateTime($currentDate);

    while ($user = $usersResult->fetch_assoc()) {
        $userId = $user['id'];

        // Calculate completion date (extended by any approved freeze days)
        $completion_date = getInternshipCompletionDate($conn, $userId);

        // If internship is complete, skip auto-attendance
        if ($completion_date && $today > $completion_date) {
            continue;
        }

        // Frozen interns: mark today as "Freeze" (not present, not absent) and skip normal processing
        if (($user['freeze_status'] ?? 'active') === 'frozen') {
            $attendanceSql = "SELECT id FROM attendance WHERE user_id = ? AND date = ? LIMIT 1";
            $attendanceStmt = $conn->prepare($attendanceSql);
            $attendanceStmt->bind_param("is", $userId, $currentDate);
            $attendanceStmt->execute();
            $hasMaster = ($attendanceStmt->get_result()->num_rows > 0);
            $attendanceStmt->close();

            if ($hasMaster) {
                $updateFreezeSql = "UPDATE attendance SET status = 'freeze', attendance_type = 4 WHERE user_id = ? AND date = ?";
                $updateFreezeStmt = $conn->prepare($updateFreezeSql);
                $updateFreezeStmt->bind_param("is", $userId, $currentDate);
                $updateFreezeStmt->execute();
                $updateFreezeStmt->close();
            } else {
                $insertFreezeSql = "INSERT INTO attendance (user_id, task_id, date, status, total_work_seconds, attendance_type) VALUES (?, 0, ?, 'freeze', 0, 4)";
                $insertFreezeStmt = $conn->prepare($insertFreezeSql);
                $insertFreezeStmt->bind_param("is", $userId, $currentDate);
                $insertFreezeStmt->execute();
                $insertFreezeStmt->close();
            }

            continue;
        }

        // 1. Check if user is checked in but has not checked out today
        $dtlsSql = "SELECT id, check_in_time FROM attendance_dtls WHERE user_id = ? AND date = ? AND check_out_time IS NULL ORDER BY id DESC LIMIT 1";
        $dtlsStmt = $conn->prepare($dtlsSql);
        $dtlsStmt->bind_param("is", $userId, $currentDate);
        $dtlsStmt->execute();
        $dtlsResult = $dtlsStmt->get_result()->fetch_assoc();
        $dtlsStmt->close();

        if ($dtlsResult) {
            $dtlsId = $dtlsResult['id'];
            $checkInTime = $dtlsResult['check_in_time'];
            $duration = max(0, strtotime($now) - strtotime($checkInTime));

            $existingTotalSql = "SELECT COALESCE(SUM(duration), 0) as total_seconds FROM attendance_dtls WHERE user_id = ? AND date = ? AND check_out_time IS NOT NULL";
            $existingTotalStmt = $conn->prepare($existingTotalSql);
            $existingTotalStmt->bind_param("is", $userId, $currentDate);
            $existingTotalStmt->execute();
            $existingTotalResult = $existingTotalStmt->get_result()->fetch_assoc();
            $existingTotalSeconds = (int)($existingTotalResult['total_seconds'] ?? 0);
            $existingTotalStmt->close();

            $remainingSeconds = max(0, $presentThresholdSeconds - $existingTotalSeconds);
            $duration = min($duration, $remainingSeconds);
            
            // Update attendance_dtls
            $updateDtlsSql = "UPDATE attendance_dtls SET check_out_time = ?, duration = ? WHERE id = ?";
            $updateDtlsStmt = $conn->prepare($updateDtlsSql);
            $updateDtlsStmt->bind_param("sii", $now, $duration, $dtlsId);
            $updateDtlsStmt->execute();
            $updateDtlsStmt->close();
            
            // Log the auto-stop action
            error_log("Auto-stopped checked-in session for user $userId (duration: $duration seconds)");
        }

        // 2. Calculate the total daily duration from all checked-out sessions in attendance_dtls today
        $sumSql = "SELECT SUM(duration) as total_seconds FROM attendance_dtls WHERE user_id = ? AND date = ?";
        $sumStmt = $conn->prepare($sumSql);
        $sumStmt->bind_param("is", $userId, $currentDate);
        $sumStmt->execute();
        $sumResult = $sumStmt->get_result()->fetch_assoc();
        $totalSeconds = min((int)($sumResult['total_seconds'] ?? 0), $presentThresholdSeconds);
        $sumStmt->close();

        // 3. Update status of the master attendance record based on total seconds
        // status is absent if totalSeconds is less than 10,800, otherwise present
        $status = ($totalSeconds >= $presentThresholdSeconds) ? 'present' : 'absent';

        // Check if master attendance record exists
        $attendanceSql = "SELECT id FROM attendance WHERE user_id = ? AND date = ? LIMIT 1";
        $attendanceStmt = $conn->prepare($attendanceSql);
        $attendanceStmt->bind_param("is", $userId, $currentDate);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();
        $hasMaster = ($attendanceResult->num_rows > 0);
        $attendanceStmt->close();

        if ($hasMaster) {
            // Update master attendance table
            $updateMasterSql = "UPDATE attendance SET check_out_time = ?, total_work_seconds = ?, status = ?, attendance_type = ? WHERE user_id = ? AND date = ?";
            $updateMasterStmt = $conn->prepare($updateMasterSql);
            // If present, attendance_type is 1. If absent, attendance_type is 2.
            $type = ($status === 'present') ? 1 : 2;
            $updateMasterStmt->bind_param("sisiis", $now, $totalSeconds, $status, $type, $userId, $currentDate);
            $updateMasterStmt->execute();
            $updateMasterStmt->close();
        } else {
            // Check if user has any time logs for today
            $timeLogSql = "SELECT id FROM time_logs WHERE user_id = ? AND DATE(start_time) = ? LIMIT 1";
            $timeLogStmt = $conn->prepare($timeLogSql);
            $timeLogStmt->bind_param("is", $userId, $currentDate);
            $timeLogStmt->execute();
            $timeLogResult = $timeLogStmt->get_result();
            $timeLogStmt->close();

            // If no time logs and no master record, insert an absent record
            if ($timeLogResult->num_rows == 0) {
                $insertSql = "INSERT INTO attendance (user_id, task_id, date, status, total_work_seconds, attendance_type) VALUES (?, 0, ?, 'absent', 0, 2)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("is", $userId, $currentDate);
                $insertStmt->execute();
                $insertStmt->close();
                
                // Log the auto-attendance action
                error_log("Auto-marked user $userId as absent for $currentDate");
            }
        }
    }
    
    return ['success' => true, 'message' => 'Auto-attendance completed'];
}

// Run the function
$result = markAutoAttendance();
echo json_encode($result);
?>
