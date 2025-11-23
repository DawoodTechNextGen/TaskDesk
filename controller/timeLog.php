<?php
header("Content-Type: application/json");
session_start();
include_once "../include/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

// Helper function for duration formatting
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

// Function to check if date is weekend
function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    return ($dayOfWeek >= 6); // 6 = Saturday, 7 = Sunday
}

// Function to update attendance for specific task
function updateAttendance($conn, $user_id, $task_id, $date, $additional_seconds = 0) {
    // Check if attendance record exists for this task and date
    $stmt = $conn->prepare("SELECT id, total_work_seconds FROM attendance WHERE user_id = ? AND task_id = ? AND date = ?");
    $stmt->bind_param("iis", $user_id, $task_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_seconds = $additional_seconds;
    $status = 'absent';
    
    if ($result->num_rows > 0) {
        $attendance = $result->fetch_assoc();
        $total_seconds = $attendance['total_work_seconds'] + $additional_seconds;
    }
    
    // Determine status based on total work time
    if ($total_seconds >= 10800) { // 3 hours = 10800 seconds
        $status = 'present';
    }
    if ($total_seconds >= 14400) { // 4 hours = half day
        $status = 'half_day';
    }
    if ($total_seconds >= 28800) { // 8 hours = full day
        $status = 'present';
        // Cap at 8 hours
        $total_seconds = 28800;
    }
    
    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE attendance SET total_work_seconds = ?, status = ?, updated_at = NOW() WHERE user_id = ? AND task_id = ? AND date = ?");
        $stmt->bind_param("isiss", $total_seconds, $status, $user_id, $task_id, $date);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, task_id, date, total_work_seconds, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $user_id, $task_id, $date, $total_seconds, $status);
    }
    
    return $stmt->execute();
}

// Function to mark absent for skipped days between task creation and current date
function markSkippedDaysAsAbsent($conn, $user_id, $task_id, $task_created_date) {
    $current_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime($task_created_date));
    
    // Mark absent for all weekdays between task creation date and current date
    $date = $start_date;
    while (strtotime($date) <= strtotime($current_date)) {
        if (!isWeekend($date)) {
            // Check if attendance record doesn't exist for this task and date
            $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND task_id = ? AND date = ?");
            $check_stmt->bind_param("iis", $user_id, $task_id, $date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Insert absent record for this task
                $insert_stmt = $conn->prepare("INSERT INTO attendance (user_id, task_id, date, total_work_seconds, status) VALUES (?, ?, ?, 0, 'absent')");
                $insert_stmt->bind_param("iis", $user_id, $task_id, $date);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            
            $check_stmt->close();
        }
        $date = date('Y-m-d', strtotime($date . ' +1 day'));
    }
}

// Function to auto-complete overdue tasks
function autoCompleteOverdueTasks($conn) {
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE due_date < ? AND status NOT IN ('complete', 'cancelled')");
    $stmt->bind_param("s", $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($task = $result->fetch_assoc()) {
        // Auto-complete the task
        $update_stmt = $conn->prepare("UPDATE tasks SET status = 'complete', completed_at = ? WHERE id = ?");
        $update_stmt->bind_param("si", $current_time, $task['id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $stmt->close();
}

if ($data['action'] === 'start') {
    $user_id = $_SESSION['user_id'];
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    
    // Check if current time is before 9:00 AM
    if ($current_time < '09:00:00') {
        echo json_encode(["success" => false, "message" => "Cannot start timer before 9:00 AM"]);
        exit;
    }
    
    // Check if it's weekend
    if (isWeekend($current_date)) {
        echo json_encode(["success" => false, "message" => "Cannot work on weekends"]);
        exit;
    }
    
    $task_id = (int)$data['task_id'];
    $start_time = $data['started_at'];

    // Get task creation date for attendance tracking
    $stmt_task = $conn->prepare("SELECT created_at FROM tasks WHERE id = ?");
    $stmt_task->bind_param("i", $task_id);
    $stmt_task->execute();
    $stmt_task->bind_result($task_created_at);
    $stmt_task->fetch();
    $stmt_task->close();

    // Mark skipped days as absent for this specific task
    markSkippedDaysAsAbsent($conn, $user_id, $task_id, $task_created_at);
    
    // Auto-complete overdue tasks
    autoCompleteOverdueTasks($conn);
    
    $stmt_select = $conn->prepare("SELECT started_at FROM tasks WHERE id = ?");
    $stmt_select->bind_param("i", $task_id);
    $stmt_select->execute();
    $stmt_select->bind_result($started_at);
    $stmt_select->fetch();
    $stmt_select->close();
    
    $status = 'working';
    $stmt_update = $conn->prepare("UPDATE tasks SET started_at = ?, status = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $start_time, $status, $task_id);
    if (!$stmt_update->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to update tasks"]);
        $stmt_update->close();
        exit;
    }
    $stmt_update->close();

    $stmt = $conn->prepare("INSERT INTO time_logs (task_id, user_id, start_time) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $task_id, $user_id, $start_time);
    
    if ($stmt->execute()) {
        // SUCCESS: Timer started - Now update attendance
        $currentDate = date('Y-m-d');
        $attendanceCheckSql = "SELECT * FROM attendance WHERE user_id = ? AND date = ? AND task_id = ?";
        $attendanceCheckStmt = $conn->prepare($attendanceCheckSql);
        $attendanceCheckStmt->bind_param("isi", $_SESSION['user_id'], $currentDate, $task_id);
        $attendanceCheckStmt->execute();
        $attendanceResult = $attendanceCheckStmt->get_result();
        
        if ($attendanceResult->num_rows == 0) {
            // Create attendance record for this task
            $attendanceSql = "INSERT INTO attendance (user_id, task_id, date, status, total_work_seconds) VALUES (?, ?, ?, 'present', 0)";
            $attendanceStmt = $conn->prepare($attendanceSql);
            $attendanceStmt->bind_param("iis", $_SESSION['user_id'], $task_id, $currentDate);
            $attendanceStmt->execute();
            $attendanceStmt->close();
        } else {
            // Update to present if was absent for this task
            $attendanceUpdateSql = "UPDATE attendance SET status = 'present' WHERE user_id = ? AND date = ? AND task_id = ? AND status = 'absent'";
            $attendanceUpdateStmt = $conn->prepare($attendanceUpdateSql);
            $attendanceUpdateStmt->bind_param("isi", $_SESSION['user_id'], $currentDate, $task_id);
            $attendanceUpdateStmt->execute();
            $attendanceUpdateStmt->close();
        }
        
        $attendanceCheckStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Timer started successfully']);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to insert time log"]);
    }
    $stmt->close();
}

if ($data['action'] === 'stop') {
    $user_id = $_SESSION['user_id'];
    $current_date = date('Y-m-d');
    
    $task_id = (int)$data['task_id'];
    $stop_time = $data['stoped_at']; 

    $stmt_select = $conn->prepare("SELECT started_at FROM tasks WHERE id = ?");
    $stmt_select->bind_param("i", $task_id);
    $stmt_select->execute();
    $stmt_select->bind_result($started_at);
    $stmt_select->fetch();
    $stmt_select->close();

    if ($started_at === null) {
        echo json_encode(["success" => false, "message" => "Task has not been started"]);
        exit;
    }

    $status = 'pending';

    $duration = strtotime($stop_time) - strtotime($started_at);
    
    // Update attendance for this specific task
    updateAttendance($conn, $user_id, $task_id, $current_date, $duration);

    $stmt_update = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $status, $task_id);
    if (!$stmt_update->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to update tasks"]);
        $stmt_update->close();
        exit;
    }
    $stmt_update->close();

    $stmt = $conn->prepare("UPDATE time_logs SET end_time = ?, duration = ? WHERE task_id = ? AND end_time IS NULL");
    $stmt->bind_param("sii", $stop_time, $duration, $task_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Timer stopped successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update time log"]);
    }
    $stmt->close();
}

if ($data['action'] === 'get') {
    $task_id = (int)$data['task_id'];

    $stmt = $conn->prepare("SELECT start_time, end_time, duration FROM time_logs WHERE task_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            "started_at" => $row['start_time'],
            "stopped_at" => $row['end_time'],
            "duration"   => $row['duration']
        ];
    }
    if (!empty($logs)) {
        echo json_encode(["success" => true, "logs" => $logs]);
    } else {
        echo json_encode(["success" => false, "message" => "No records found"]);
    }

    $stmt->close();
}

if ($data['action'] === 'complete') {
    $user_id = $_SESSION['user_id'];
    $current_date = date('Y-m-d');
    
    $task_id = (int)$data['task_id'];
    $stop_time = $data['stoped_at'];
    $github_repo  = $data['github_repo'];
    $live_url = $data['live_view'];
    $additional_notes = $data['additional_notes'];

    $stmt_select = $conn->prepare("SELECT started_at FROM tasks WHERE id = ?");
    $stmt_select->bind_param("i", $task_id);
    $stmt_select->execute();
    $stmt_select->bind_result($started_at);
    $stmt_select->fetch();
    $stmt_select->close();

    if ($started_at === null) {
        echo json_encode(["success" => false, "message" => "Task has not been started"]);
        exit;
    }

    $status = 'complete';

    $duration = strtotime($stop_time) - strtotime($started_at);
    
    // Update attendance for this specific task
    updateAttendance($conn, $user_id, $task_id, $current_date, $duration);

    $stmt_update = $conn->prepare("UPDATE tasks SET status = ?, completed_at = ?, github_repo = ?, live_url = ?,
    additional_notes = ? WHERE id = ?");
    $stmt_update->bind_param("sssssi", $status, $stop_time, $github_repo, $live_url, $additional_notes, $task_id);
    if (!$stmt_update->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to update tasks"]);
        $stmt_update->close();
        exit;
    }
    $stmt_update->close();

    $stmt = $conn->prepare("UPDATE time_logs SET end_time = ?, duration = ? WHERE task_id = ? AND end_time IS NULL");
    $stmt->bind_param("sii", $stop_time, $duration, $task_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Task Completed successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update time log"]);
    }
    $stmt->close();
}

// New action to get task-specific attendance
if ($data['action'] === 'get_task_attendance') {
    $user_id = $_SESSION['user_id'];
    $task_id = isset($data['task_id']) ? (int)$data['task_id'] : null;
    
    if (!$task_id) {
        echo json_encode(["success" => false, "message" => "Task ID is required"]);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT date, total_work_seconds, status FROM attendance WHERE user_id = ? AND task_id = ? ORDER BY date DESC");
    $stmt->bind_param("ii", $user_id, $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $attendance = [];
    $total_task_time = 0;
    
    while ($row = $result->fetch_assoc()) {
        $attendance[] = [
            "date" => $row['date'],
            "total_work_seconds" => $row['total_work_seconds'],
            "status" => $row['status'],
            "formatted_time" => formatDuration($row['total_work_seconds'])
        ];
        $total_task_time += $row['total_work_seconds'];
    }
    
    echo json_encode([
        "success" => true, 
        "attendance" => $attendance,
        "total_task_time" => formatDuration($total_task_time)
    ]);
    $stmt->close();
}

// Add this function to handle automatic attendance marking
if ($data['action'] == 'mark_auto_attendance') {
    $currentDate = date('Y-m-d');
    
    // Check if attendance already exists for today
    $checkSql = "SELECT * FROM attendance WHERE user_id = ? AND date = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("is", $_SESSION['user_id'], $currentDate);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows == 0) {
        // No attendance record for today, mark as absent
        $insertSql = "INSERT INTO attendance (user_id, date, status, total_work_seconds) VALUES (?, ?, 'absent', 0)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("is", $_SESSION['user_id'], $currentDate);
        
        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Auto-marked as absent for today']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
        }
        $insertStmt->close();
    } else {
        echo json_encode(['success' => true, 'message' => 'Attendance already exists for today']);
    }
    $checkStmt->close();
    exit;
}

$conn->close();
?>