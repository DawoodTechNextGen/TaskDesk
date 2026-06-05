<?php
// Test SMTP script with full debug output
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/notification_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: text/plain');

$toEmail = $_GET['email'] ?? $argv[1] ?? '';
if (empty($toEmail)) {
    echo "Usage via CLI: php scratch/test_smtp.php <your-email-address>\n";
    echo "Usage via Browser: http://localhost/TaskDesk/scratch/test_smtp.php?email=<your-email-address>\n";
    exit(1);
}

echo "Testing SMTP Keys from .env to recipient: {$toEmail}\n";
echo "========================================\n\n";

function testSMTPDirect($toEmail, $type = 'primary') {
    echo "----------------------------------------\n";
    echo "Testing " . strtoupper($type) . " SMTP Config...\n";
    echo "----------------------------------------\n";
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 2; // Output detailed SMTP logs
        $mail->Debugoutput = 'echo';
        
        if ($type === 'primary') {
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        } else {
            $mail->Host       = GMAIL_MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = GMAIL_MAIL_USERNAME;
            $mail->Password   = GMAIL_MAIL_PASSWORD;
            $mail->SMTPSecure = GMAIL_MAIL_ENCRYPTION;
            $mail->Port       = GMAIL_MAIL_PORT;
            $mail->setFrom(GMAIL_MAIL_USERNAME, MAIL_FROM_NAME);
        }
        
        $mail->Timeout = 15;
        $mail->addAddress($toEmail, 'Test Recipient');
        $mail->isHTML(true);
        $mail->Subject = "SMTP Test - " . strtoupper($type);
        $mail->Body    = "<h3>SMTP Keys Test</h3><p>If you see this email, the " . strtoupper($type) . " SMTP configuration is working correctly!</p>";
        
        $sent = $mail->send();
        echo "\nRESULT: Success! Email sent via " . strtoupper($type) . " SMTP.\n";
        return true;
    } catch (Exception $e) {
        echo "\nRESULT: Failed! error: " . $mail->ErrorInfo . "\n";
        return false;
    }
}

// Test Primary
$primaryResult = testSMTPDirect($toEmail, 'primary');

echo "\n\n";

// Test Gmail Fallback
$gmailResult = testSMTPDirect($toEmail, 'gmail');

echo "\n========================================\n";
echo "Test Finished.\n";
echo "Primary SMTP: " . ($primaryResult ? "WORKING" : "FAILED") . "\n";
echo "Gmail SMTP: " . ($gmailResult ? "WORKING" : "FAILED") . "\n";
