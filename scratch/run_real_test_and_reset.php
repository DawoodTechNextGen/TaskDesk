<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

echo "1. Fetching candidates with status 'new' before cron run...\n";
$newIds = [];
$res = $conn->query("SELECT id FROM registrations WHERE status = 'new'");
while ($row = $res->fetch_assoc()) {
    $newIds[] = (int)$row['id'];
}
$count = count($newIds);
echo "Found {$count} new registrations to process.\n\n";

if ($count === 0) {
    echo "No new registrations found. Aborting.\n";
    exit;
}

echo "2. Executing cron_send_emails.php via PHP CLI...\n";
echo "--------------------------------------------------\n";

// Run cron script
$phpPath = 'C:\\xampp\\php\\php.exe';
$cronScript = __DIR__ . '/../cron_send_emails.php';
$output = shell_exec("\"$phpPath\" \"$cronScript\"");

echo "Cron Output:\n";
echo $output . "\n";
echo "--------------------------------------------------\n\n";

echo "3. Resetting status back to 'new' and email_status to 0 for processed candidates...\n";
$idList = implode(',', $newIds);
$updateSql = "UPDATE registrations 
              SET status = 'new', email_status = 0 
              WHERE id IN ($idList)";

if ($conn->query($updateSql)) {
    $affected = $conn->affected_rows;
    echo "Successfully restored {$affected} candidates back to 'new' status.\n";
} else {
    echo "Failed to restore status: " . $conn->error . "\n";
}
echo "\nTest Completed.\n";
