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

    while ($start < $today) {
        if (!isWeekend($start)) {
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

    // Get User created date
    $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_created_at);
    $stmt->fetch();
    $stmt->close();

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
   GET TASK ATTENDANCE
========================= */

if ($data['action'] === 'get_task_attendance') {

    $user_id = $_SESSION['user_id'];

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

$conn->close();
