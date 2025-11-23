<?php
$user_id = $row['user_id'];
$today = date("Y-m-d");

// Total working seconds
$total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(duration) AS total_seconds 
    FROM time_logs 
    WHERE user_id='$user_id' AND DATE(started_at)='$today'
"))['total_seconds'];

if (!$total) $total = 0;

$status = ($total >= 3 * 3600) ? 'present' : 'absent';

// Update attendance
mysqli_query($conn, "
    INSERT INTO attendance (user_id, date, total_seconds, status)
    VALUES ('$user_id', '$today', '$total', '$status')
    ON DUPLICATE KEY UPDATE total_seconds='$total', status='$status'
");
