<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

date_default_timezone_set('Asia/Karachi');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

/* =========================
   HELPERS
========================= */

function isWeekend($date)
{
    return date('N', strtotime($date)) >= 6;
}

function formatDuration($seconds)
{
    return gmdate("H:i:s", $seconds);
}

/* =========================
   START TIMER
========================= */

if ($data['action'] === 'start') {

    $user_id = $_SESSION['user_id'];
    $task_id = (int)$data['task_id'];
    $started_at = $data['started_at'];
    $today = date('Y-m-d');

    if (isWeekend($today)) {
        echo json_encode(["success" => false, "message" => "Cannot work on weekends"]);
        exit;
    }

    if (date('H:i:s') < '09:00:00') {
        echo json_encode(["success" => false, "message" => "Cannot start before 9 AM"]);
        exit;
    }

    // Check if user is frozen
    $freeze_stmt = $conn->prepare("SELECT freeze_status FROM users WHERE id = ?");
    $freeze_stmt->bind_param("i", $user_id);
    $freeze_stmt->execute();
    $freeze_stmt->bind_result($freeze_status);
    $freeze_stmt->fetch();
    $freeze_stmt->close();

    if ($freeze_status === 'frozen') {
        echo json_encode(["success" => false, "message" => "Cannot work during internship freeze period"]);
        exit;
    }

    if ($freeze_status === 'freeze_requested') {
        echo json_encode(["success" => false, "message" => "Your freeze request is pending approval"]);
        exit;
    }

    // Get User created date and internship type
    $stmt = $conn->prepare("SELECT created_at, internship_type, internship_duration FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_created_at, $internship_type, $internship_duration);
    $stmt->fetch();
    $stmt->close();

    // Check if internship duration is completed
    $start_date = new DateTime($user_created_at);
    $current_date = new DateTime($today);
    
    $duration_weeks = 12;
    if (!empty($internship_duration)) {
        if ($internship_duration === '4 weeks') $duration_weeks = 4;
        elseif ($internship_duration === '8 weeks') $duration_weeks = 8;
        elseif ($internship_duration === '12 weeks') $duration_weeks = 12;
    } else {
        $duration_weeks = ($internship_type == 0) ? 4 : 12;
    }
    // Calculate end date (start + duration)
    // We can use modify to add weeks
    $end_date = clone $start_date;
    $end_date->modify("+$duration_weeks weeks");

    if ($current_date > $end_date) {
        echo json_encode(["success" => false, "message" => "Internship duration ($duration_weeks weeks) completed. Attendance marked automatically."]);
        // Optionally ensure the last day was marked? 
        // User said: "dont mark his/her attendance" ... wait.
        // "when duration is complete then dont mark his/her attendance" -> restrict clock-in.
        exit;
    }

    // Check for Task Expiration first
    $exp_stmt = $conn->prepare("SELECT due_date, status FROM tasks WHERE id = ?");
    $exp_stmt->bind_param("i", $task_id);
    $exp_stmt->execute();
    $task_info = $exp_stmt->get_result()->fetch_assoc();
    $exp_stmt->close();

    if ($task_info && !empty($task_info['due_date'])) {
        $today_date = date('Y-m-d');
        if ($task_info['due_date'] < $today_date && $task_info['status'] !== 'complete') {
            // Self-correct database status if not already expired
            $conn->query("UPDATE tasks SET status = 'expired' WHERE id = $task_id");
            echo json_encode(["success" => false, "message" => "This task has expired (due date: " . $task_info['due_date'] . ") and cannot be started."]);
            exit;
        }
    }

    // Start task
    $stmt = $conn->prepare("UPDATE tasks SET status = 'working', started_at = ? WHERE id = ?");
    $stmt->bind_param("si", $started_at, $task_id);
    $stmt->execute();
    $stmt->close();

    // Insert time log
    $stmt = $conn->prepare("INSERT INTO time_logs (task_id, user_id, start_time) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $task_id, $user_id, $started_at);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true, "message" => "Timer started successfully"]);
    exit;
}

/* =========================
   STOP TIMER
========================= */

if ($data['action'] === 'stop') {

    $user_id = $_SESSION['user_id'];
    $task_id = (int)$data['task_id'];
    $stop_time = $data['stoped_at'];
    $today = date('Y-m-d');

    // 1. Fetch started_at and due_date
    $stmt = $conn->prepare("SELECT started_at, due_date FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $task_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task_data || !$task_data['started_at']) {
        echo json_encode(["success" => false, "message" => "Task not started"]);
        exit;
    }

    $started_at = $task_data['started_at'];
    $duration = strtotime($stop_time) - strtotime($started_at);

    // 2. Determine new status (pending or expired)
    $new_status = 'pending';
    if (!empty($task_data['due_date']) && $task_data['due_date'] < $today) {
        $new_status = 'expired';
    }

    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $task_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE time_logs 
        SET end_time = ?, duration = ?
        WHERE task_id = ? AND end_time IS NULL
    ");
    $stmt->bind_param("sii", $stop_time, $duration, $task_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true, "message" => "Timer stopped successfully"]);
    exit;
}

/* =========================
   COMPLETE TASK (STOP & SUBMIT)
========================= */

if ($data['action'] === 'complete') {
    $user_id = $_SESSION['user_id'];
    $task_id = (int)$data['task_id'];
    $stop_time = $data['stoped_at'];
    $github_repo = $data['github_repo'] ?? '';
    // Map live_view from frontend to live_url in DB
    $live_url = $data['live_view'] ?? ''; 
    $additional_notes = $data['additional_notes'] ?? '';
    $today = date('Y-m-d');

    // Check for Task Expiration first
    $exp_stmt = $conn->prepare("SELECT due_date, status FROM tasks WHERE id = ?");
    $exp_stmt->bind_param("i", $task_id);
    $exp_stmt->execute();
    $task_info = $exp_stmt->get_result()->fetch_assoc();
    $exp_stmt->close();

    if ($task_info && !empty($task_info['due_date'])) {
        $today_date = date('Y-m-d');
        if ($task_info['due_date'] < $today_date && $task_info['status'] !== 'complete') {
            // Self-correct database status
            $conn->query("UPDATE tasks SET status = 'expired' WHERE id = $task_id");
            echo json_encode(["success" => false, "message" => "This task has expired (due date: " . $task_info['due_date'] . ") and cannot be submitted."]);
            exit;
        }
    }

    // 1. Stop the timer (Logic similar to 'stop' action)
    $stmt = $conn->prepare("SELECT started_at FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($started_at);
    $stmt->fetch();
    $stmt->close();

    if ($started_at) {
        $duration = strtotime($stop_time) - strtotime($started_at);

        $stmt = $conn->prepare("
            UPDATE time_logs 
            SET end_time = ?, duration = ?
            WHERE task_id = ? AND end_time IS NULL
        ");
        $stmt->bind_param("sii", $stop_time, $duration, $task_id);
        $stmt->execute();
        $stmt->close();
    }

    // 2. Update task status AND submission details
    // Note: completed_at is set to NOW() when marking as pending_review
    // Status 'pending_review' means intern has submitted it.
    $stmt = $conn->prepare("UPDATE tasks SET status = 'pending_review', completed_at = NOW(), github_repo = ?, live_url = ?, additional_notes = ? WHERE id = ?");
    $stmt->bind_param("sssi", $github_repo, $live_url, $additional_notes, $task_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Task submitted for review"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to submit task"]);
    }
    $stmt->close();
    exit;
}

/* =========================
   GET TASK ATTENDANCE
========================= */

if ($data['action'] === 'get_task_attendance') {
    $task_id = (int)($data['task_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($task_id > 0) {
        $task_stmt = $conn->prepare("SELECT assign_to FROM tasks WHERE id = ?");
        $task_stmt->bind_param("i", $task_id);
        $task_stmt->execute();
        $task_res = $task_stmt->get_result()->fetch_assoc();
        if ($task_res) {
            $user_id = $task_res['assign_to'];
        }
        $task_stmt->close();
    }

    $stmt = $conn->prepare("
        SELECT date, total_work_seconds, status
        FROM attendance
        WHERE user_id = ?
        ORDER BY date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $total = 0;

    while ($r = $result->fetch_assoc()) {
        $rows[] = [
            "date" => $r['date'],
            "status" => $r['status'],
            "formatted_time" => formatDuration($r['total_work_seconds'])
        ];
        $total += $r['total_work_seconds'];
    }

    echo json_encode([
        "success" => true,
        "attendance" => $rows,
        "total_task_time" => formatDuration($total)
    ]);
    exit;
}
/* =========================
   GET TIME LOGS (TASK)
========================= */

if ($data['action'] === 'get') {

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Session expired"]);
        exit;
    }

    $task_id = (int)$data['task_id'];

    $stmt = $conn->prepare("
        SELECT start_time, end_time, duration
        FROM time_logs
        WHERE task_id = ?
        ORDER BY id DESC
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Query failed"]);
        exit;
    }

    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];

    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            "started_at" => $row['start_time'],
            "stopped_at" => $row['end_time'],
            "duration"   => (int)$row['duration']
        ];
    }

    if (count($logs) === 0) {
        echo json_encode(["success" => false, "message" => "No records found"]);
    } else {
        echo json_encode(["success" => true, "logs" => $logs]);
    }

    exit;
}

/* =========================
   CHECK ACTIVE TASKS
========================= */

if ($data['action'] === 'check_active') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND status = 'working' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(["success" => true, "has_active" => $result->num_rows > 0]);
    $stmt->close();
    exit;
}

/* =========================
   MARK AUTO ATTENDANCE
========================= */

if ($data['action'] === 'mark_auto_attendance') {
    $currentDate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    
    // Get all approved interns
    $stmt = $conn->prepare("SELECT id, created_at, internship_type, internship_duration FROM users WHERE status = 1 AND user_role = 2");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $today = new DateTime($currentDate);

    while ($user = $result->fetch_assoc()) {
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

        // If internship is complete, skip auto-attendance
        if ($today > $completion_date) {
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
            $duration = strtotime($now) - strtotime($checkInTime);
            
            // If duration is greater than 25,200 then just add 25,200 or if less then insert that duration
            if ($duration > 25200) {
                $duration = 25200;
            }
            
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
        $totalSeconds = (int)($sumResult['total_seconds'] ?? 0);
        $sumStmt->close();

        // 3. Update status of the master attendance record based on total seconds
        // status is absent if totalSeconds is less than 10,800, otherwise present
        $status = ($totalSeconds >= 10800) ? 'present' : 'absent';

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
    
    $stmt->close();
    echo json_encode(["success" => true, "message" => "Auto-attendance check completed"]);
    exit;
}

$conn->close();
