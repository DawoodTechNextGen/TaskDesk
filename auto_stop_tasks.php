<?php
require_once __DIR__ . "/include/connection.php";

// Set timezone
date_default_timezone_set('Asia/Karachi');

// Stop all tasks where status = 'working'
$current_time = date("Y-m-d H:i:s"); // Actual current time for log
$stop_time_record = date("Y-m-d") . " 23:59:59"; // Record as end of day
$today = date("Y-m-d");

$sql = "SELECT id, started_at, assign_to, due_date FROM tasks WHERE status = 'working'";
$result = $conn->query($sql);

while ($task = $result->fetch_assoc()) {
    $task_id = $task['id'];
    $started_at = $task['started_at'];
    $user_id = $task['assign_to'];
    $due_date = $task['due_date'];

    // calculate duration
    $duration = strtotime($stop_time_record) - strtotime($started_at);
    if ($duration < 0) $duration = 0;

    // 1. Update time_logs
    $stmt = $conn->prepare("UPDATE time_logs SET end_time = ?, duration = ? 
            WHERE task_id = ? AND end_time IS NULL");
    $stmt->bind_param("sii", $stop_time_record, $duration, $task_id);
    $stmt->execute();

    // 2. Update attendance table (This was missing!)
    // We use total_work_seconds and calculate status
    $att_stmt = $conn->prepare("
        UPDATE attendance 
        SET total_work_seconds = total_work_seconds + ?,
            status = CASE 
                WHEN total_work_seconds + ? >= 10800 THEN 'present'
                ELSE 'absent'
            END,
            task_id = ?
        WHERE user_id = ? AND date = ?
    ");
    $att_stmt->bind_param("iiiis", $duration, $duration, $task_id, $user_id, $today);
    $att_stmt->execute();

    // 3. Determine task status based on due date
    $due_date_only = date("Y-m-d", strtotime($due_date));

    if ($due_date_only < $today) {
        $status = "expired";
        $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL WHERE id = ?");
        $stmt2->bind_param("si", $status, $task_id);
        $stmt2->execute();
    } elseif ($due_date_only == $today) {
        $status = "complete";
        $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL, completed_at = ? WHERE id = ?");
        $stmt2->bind_param("ssi", $status, $stop_time_record, $task_id);
        $stmt2->execute();
    } else {
        $status = "pending";
        $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL WHERE id = ?");
        $stmt2->bind_param("si", $status, $task_id);
        $stmt2->execute();
    }

    error_log("Auto-stopped task $task_id for user $user_id. Duration added: $duration seconds.");
}

$conn->close();
?>
