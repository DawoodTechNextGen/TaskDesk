<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

date_default_timezone_set('Asia/Karachi');

echo "1. Fetching candidate IDs in 'new' status to track...\n";
$newIds = [];
$res = $conn->query("SELECT id FROM registrations WHERE status = 'new'");
while ($row = $res->fetch_assoc()) {
    $newIds[] = (int)$row['id'];
}
$count = count($newIds);
echo "Found {$count} candidates to test with.\n\n";

if ($count === 0) {
    echo "No new candidates found to process. Aborting.\n";
    exit;
}

// Just in case some got modified, ensure they are set to 'new' and email_status to 0
$idList = implode(',', $newIds);
$conn->query("UPDATE registrations SET status = 'new', email_status = 0 WHERE id IN ($idList)");

echo "Clearing email_sent_logs to reset hourly limit for this test...\n";
$conn->query("TRUNCATE TABLE email_sent_logs");


$iterations = 3;
echo "2. Simulating cron job running every 5 seconds (Total iterations: {$iterations})...\n";
echo "--------------------------------------------------------------------\n";

$phpPath = 'C:\\xampp\\php\\php.exe';
$cronScript = __DIR__ . '/../cron_send_emails.php';

for ($i = 1; $i <= $iterations; $i++) {
    $time = date('H:i:s');
    echo "[{$time}] Starting Iteration {$i}...\n";
    
    // Execute cron_send_emails.php
    $output = shell_exec("\"$phpPath\" \"$cronScript\"");
    
    echo "Cron Output:\n";
    echo $output . "\n";
    
    $timeDone = date('H:i:s');
    if ($i < $iterations) {
        echo "[{$timeDone}] Iteration {$i} done. Sleeping for 5 seconds...\n";
        sleep(5);
        echo "\n";
    } else {
        echo "[{$timeDone}] Iteration {$i} done.\n";
    }
}

echo "--------------------------------------------------------------------\n\n";
echo "3. Restoring candidate status back to 'new' and email_status to 0...\n";
$updateSql = "UPDATE registrations SET status = 'new', email_status = 0 WHERE id IN ($idList)";
if ($conn->query($updateSql)) {
    $affected = $conn->affected_rows;
    echo "Successfully restored {$affected} candidates back to 'new' status.\n";
} else {
    echo "Failed to restore status: " . $conn->error . "\n";
}

echo "\nSimulation Completed.\n";
?>
