<?php
include_once "./include/connection.php";

// Stop all tasks where status = 'working'
$current_time = date("Y-m-d") . " 23:59:59";

$sql = "SELECT id, started_at, assign_to FROM tasks WHERE status = 'working'";
$result = $conn->query($sql);

while ($task = $result->fetch_assoc()) {

    $task_id = $task['id'];
    $started_at = $task['started_at'];
    $user_id = $task['assign_to'];

    // calculate duration
    $duration = strtotime($current_time) - strtotime($started_at);

    // update time_logs
    $stmt = $conn->prepare("UPDATE time_logs SET end_time = ?, duration = ? 
        WHERE task_id = ? AND end_time IS NULL");
    $stmt->bind_param("sii", $current_time, $duration, $task_id);
    $stmt->execute();

    // update task status
    $status = "pending";
    $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL WHERE id = ?");
    $stmt2->bind_param("si", $status, $task_id);
    $stmt2->execute();

    // update attendance (optional)
    // updateAttendance($conn, $user_id, $task_id, date("Y-m-d"), $duration);
}

$conn->close();
