<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/notification_helper.php';

echo "Global conn defined: " . (isset($GLOBALS['conn']) ? "YES" : "NO") . "\n";
echo "Class Database exists: " . (class_exists('Database') ? "YES" : "NO") . "\n";

// Run sendEmailPHPMailer with dummy data to see if logs table gets created
// We pass type as 'primary' but with fake parameters (it will fail to send, but should trigger the DB code)
echo "Attempting to call sendEmailPHPMailer...\n";
$res = sendEmailPHPMailer('test@test.com', 'Test User', 'Test Subject', 'Test Body', null, '', 'primary');
echo "Send result: " . ($res ? "SUCCESS" : "FAILED") . "\n";

// Check if email_sent_logs exists now
global $conn;
if (isset($conn)) {
    $result = $conn->query("SHOW TABLES LIKE 'email_sent_logs'");
    if ($result && $result->num_rows > 0) {
        echo "Table email_sent_logs EXISTS in database!\n";
    } else {
        echo "Table email_sent_logs DOES NOT exist in database!\n";
    }
} else {
    echo "Database connection \$conn is NULL or not set.\n";
}
?>
