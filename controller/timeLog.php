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
   ATTENDANCE (USER + DATE)
========================= */

function ensureAttendanceRow($conn, $user_id, $date, $task_id)
{
    $sql = "
        INSERT INTO attendance (user_id, task_id, date, total_work_seconds, status)
        VALUES (?, ?, ?, 0, 'absent')
        ON DUPLICATE KEY UPDATE status = status, task_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $user_id, $task_id, $date, $task_id);
    $stmt->execute();
    $stmt->close();
}

function updateAttendanceTime($conn, $user_id, $date, $seconds, $task_id)
{
    $sql = "
        UPDATE attendance
        SET total_work_seconds = total_work_seconds + ?,
            status = CASE 
                WHEN total_work_seconds + ? >= 10800 THEN 'present'
                ELSE 'absent'
            END,
            task_id = ?
        WHERE user_id = ? AND date = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiis", $seconds, $seconds, $task_id, $user_id, $date);
    $stmt->execute();
    $stmt->close();
}

function markSkippedDaysAbsent($conn, $user_id, $user_created_at)
{
    $start = date('Y-m-d', strtotime($user_created_at));
    $today = date('Y-m-d');

    // Get the user's current active task (if any)
    $task_id = null;
    $task_stmt = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? AND status IN ('pending', 'working') ORDER BY created_at DESC LIMIT 1");
    $task_stmt->bind_param("i", $user_id);
    $task_stmt->execute();
    $task_result = $task_stmt->get_result();
    if ($task_row = $task_result->fetch_assoc()) {
        $task_id = $task_row['id'];
    }
    $task_stmt->close();

    // If no active task, we can't backfill (need a task_id)
    if (!$task_id) {
        return;
    }

    // Get freeze periods for this user
    $freeze_periods = [];
    $freeze_stmt = $conn->prepare("
        SELECT freeze_start_date, freeze_end_date 
        FROM users 
        WHERE id = ? 
        AND freeze_start_date IS NOT NULL 
        AND freeze_end_date IS NOT NULL
    ");
    $freeze_stmt->bind_param("i", $user_id);
    $freeze_stmt->execute();
    $freeze_result = $freeze_stmt->get_result();
    while ($freeze_row = $freeze_result->fetch_assoc()) {
        $freeze_periods[] = [
            'start' => $freeze_row['freeze_start_date'],
            'end' => $freeze_row['freeze_end_date']
        ];
    }
    $freeze_stmt->close();

    while ($start < $today) {
        // Check if this date is within a freeze period
        $is_frozen = false;
        foreach ($freeze_periods as $period) {
            if ($start >= $period['start'] && $start <= $period['end']) {
                $is_frozen = true;
                break;
            }
        }
        
        // Only mark attendance if not weekend and not frozen
        if (!isWeekend($start) && !$is_frozen) {
            ensureAttendanceRow($conn, $user_id, $start, $task_id);
        }
        $start = date('Y-m-d', strtotime($start . ' +1 day'));
    }
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

    // Back-fill absents safely
    markSkippedDaysAbsent($conn, $user_id, $user_created_at);

    // Ensure today's attendance row
    ensureAttendanceRow($conn, $user_id, $today, $task_id);

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

    $stmt = $conn->prepare("SELECT started_at FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($started_at);
    $stmt->fetch();
    $stmt->close();

    if (!$started_at) {
        echo json_encode(["success" => false, "message" => "Task not started"]);
        exit;
    }

    $duration = strtotime($stop_time) - strtotime($started_at);

    updateAttendanceTime($conn, $user_id, $today, $duration, $task_id);

    $stmt = $conn->prepare("UPDATE tasks SET status = 'pending' WHERE id = ?");
    $stmt->bind_param("i", $task_id);
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

    // 1. Stop the timer (Logic similar to 'stop' action)
    $stmt = $conn->prepare("SELECT started_at FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($started_at);
    $stmt->fetch();
    $stmt->close();

    if ($started_at) {
        $duration = strtotime($stop_time) - strtotime($started_at);
        updateAttendanceTime($conn, $user_id, $today, $duration, $task_id);

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
    // This is called periodically or at end of day to ensure rows exist
    $today = date('Y-m-d');
    
    // Get all active interns (role 2)
    $stmt = $conn->prepare("SELECT id, created_at FROM users WHERE user_role = 2 AND status = 'approved'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($user = $result->fetch_assoc()) {
        // Find most recent task for this user to link attendance
        $task_stmt = $conn->prepare("SELECT id FROM tasks WHERE assign_to = ? ORDER BY created_at DESC LIMIT 1");
        $task_stmt->bind_param("i", $user['id']);
        $task_stmt->execute();
        $task_res = $task_stmt->get_result()->fetch_assoc();
        $task_id = $task_res ? $task_res['id'] : 0;
        $task_stmt->close();
        
        if ($task_id > 0) {
            ensureAttendanceRow($conn, $user['id'], $today, $task_id);
        }
    }
    
    echo json_encode(["success" => true, "message" => "Auto-attendance check completed"]);
    exit;
}

$conn->close();
