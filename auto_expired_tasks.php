<?php
require_once __DIR__ . "/include/connection.php";

// Set timezone
date_default_timezone_set('Asia/Karachi');

/**
 * Auto Expire Tasks
 * This script updates the status of 'pending' and 'working' tasks to 'expired'
 * if their due_date is less than the current date.
 */

$today = date("Y-m-d");

// SQL to update tasks
$sql = "UPDATE tasks 
        SET status = 'expired' 
        WHERE status IN ('pending', 'working') 
        AND due_date < ? 
        AND due_date IS NOT NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    if ($affected_rows > 0) {
        echo "Successfully expired $affected_rows tasks.\n";
        // Optional: Add logging to a file or database if needed
        error_log("[" . date("Y-m-d H:i:s") . "] Auto-expired $affected_rows tasks.");
    } else {
        echo "No tasks to expire today.\n";
    }
} else {
    echo "Error executing auto-expiry: " . $conn->error . "\n";
    error_log("[" . date("Y-m-d H:i:s") . "] Error in auto_expired_tasks.php: " . $conn->error);
}

$stmt->close();
$conn->close();
?>
