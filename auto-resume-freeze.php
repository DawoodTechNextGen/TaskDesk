<?php
/**
 * Auto-Resume Freeze Script
 * Run this daily via cron to automatically resume frozen internships after freeze period ends
 * Cron: 0 0 * * * php /path/to/auto-resume-freeze.php
 */


require "/home2/dawoodte/public_html/taskdesk/include/connection.php";
date_default_timezone_set('Asia/Karachi');

// Find users whose freeze period has ended
$stmt = $conn->prepare("
    UPDATE users 
    SET freeze_status = 'active',
        freeze_start_date = NULL,
        freeze_end_date = NULL,
        freeze_reason = NULL,
        freeze_requested_at = NULL,
        freeze_approved_by = NULL,
        freeze_approved_at = NULL
    WHERE freeze_status = 'frozen' 
    AND freeze_end_date < CURDATE()
");

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    error_log("Auto-resume: Resumed $affected frozen internships");
    echo "Success: Resumed $affected frozen internships\n";
} else {
    error_log("Auto-resume: Failed to resume frozen internships");
    echo "Error: Failed to resume frozen internships\n";
}

$stmt->close();
$conn->close();
