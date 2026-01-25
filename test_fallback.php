<?php
// test_fallback.php
require_once 'include/config.php';
require_once 'include/connection.php';
require_once 'include/notification_helper.php';

header('Content-Type: text/plain');

echo "Starting Fallback Notification Test...\n";
echo "Note: WhatsApp is intentionally configured to fail (as per user request).\n\n";

$testData = [
    'email' => 'dawoodtechtest@gmail.com', // Replace with a test email you can check
    'name' => 'Test Candidate',
    'mbl_number' => '923061061544',
    'subject' => 'Fallback Test ' . date('Y-m-d H:i:s'),
    'html_content' => '<h1>Fallback Test</h1><p>If you see this, the email fallback worked!</p>',
    'whatsapp_msg' => 'Assalam-o-Alaikum, this is a test from the fallback system.'
];

$results = sendNotificationFallback($testData);

echo "Test Results:\n";
echo "-----------------\n";
echo "WhatsApp Attempted: " . ($results['whatsapp']['attempted'] ? 'Yes' : 'No') . "\n";
echo "WhatsApp Success: " . ($results['whatsapp']['success'] ? 'Yes' : 'No') . "\n";
echo "Primary Email Attempted: " . ($results['primary_email']['attempted'] ? 'Yes' : 'No') . "\n";
echo "Primary Email Success: " . ($results['primary_email']['success'] ? 'Yes' : 'No') . "\n";
echo "Gmail Fallback Attempted: " . ($results['gmail_email']['attempted'] ? 'Yes' : 'No') . "\n";
echo "Gmail Fallback Success: " . ($results['gmail_email']['success'] ? 'Yes' : 'No') . "\n";
echo "Final Overall Success: " . ($results['final_success'] ? 'Yes' : 'No') . "\n";

if (!empty($results['error_logs'])) {
    echo "\nError Logs:\n";
    foreach ($results['error_logs'] as $log) {
        echo "- $log\n";
    }
}

echo "\nTest Finished.\n";
