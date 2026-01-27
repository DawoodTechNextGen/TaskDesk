<?php
require "/include/connection.php";
// require "/home2/dawoodte/public_html/taskdesk/include/connection.php";

// Stop all tasks where status = 'working'
$current_time = date("Y-m-d") . " 23:59:59";
$today = date("Y-m-d");

$sql = "SELECT id, started_at, assign_to, due_date FROM tasks WHERE status = 'working'";
$result = $conn->query($sql);

while ($task = $result->fetch_assoc()) {
    $task_id = $task['id'];
    $started_at = $task['started_at'];
    $user_id = $task['assign_to'];
    $due_date = $task['due_date'];

    // calculate duration
    $duration = strtotime($current_time) - strtotime($started_at);

    // update time_logs
    $stmt = $conn->prepare("UPDATE time_logs SET end_time = ?, duration = ? 
            WHERE task_id = ? AND end_time IS NULL");
    $stmt->bind_param("sii", $current_time, $duration, $task_id);
    $stmt->execute();

    // Determine status based on due date
    // Convert due_date to just date part for comparison
    $due_date_only = date("Y-m-d", strtotime($due_date));

    if ($due_date_only < $today) {
        // If due date is already passed, mark as expired
        $status = "expired";
        $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL WHERE id = ?");
        $stmt2->bind_param("si", $status, $task_id);
        $stmt2->execute();
    } elseif ($due_date_only == $today) {
        // If due date is today, mark as completed (since it's end of day)
        $status = "complete";
        $completed_at = $current_time;

        $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL, completed_at = ? WHERE id = ?");
        $stmt2->bind_param("ssi", $status, $completed_at, $task_id);
        $stmt2->execute();
    } else {
        // If due date is in the future, mark as pending
        $status = "pending";

        $stmt2 = $conn->prepare("UPDATE tasks SET status = ?, started_at = NULL WHERE id = ?");
        $stmt2->bind_param("si", $status, $task_id);
        $stmt2->execute();
    }

    // update attendance (optional)
    // updateAttendance($conn, $user_id, $task_id, date("Y-m-d"), $duration);
}

$conn->close();
