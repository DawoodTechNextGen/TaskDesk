<?php
// test_hire_notification.php
require_once 'include/config.php';
require_once 'include/connection.php';
require_once 'include/notification_helper.php';
require_once 'include/pdf_helper.php';

header('Content-Type: text/plain');

echo "Starting Hiring Notification Verification Test...\n";

// Mock Data
$candidateName = "Verification Test Candidate";
$candidateEmail = "dawoodtechtest@gmail.com"; // Use a test email
$candidateMbl = "923061061544";
$password = "TestPass123!";
$techName = "Full Stack Development";
$loginUrl = BASE_URL . 'login';

echo "1. Generating Test PDF Offer Letter...\n";
$startDate = date('d-M-Y');
$endDate = date('d-M-Y', strtotime($startDate . ' + 3 months'));
$pdfContent = generateOfferLetterHelper($candidateName, $startDate, $endDate, $techName);

if ($pdfContent) {
    echo "   - PDF generated successfully (" . strlen($pdfContent) . " bytes)\n";
} else {
    echo "   - FAILED: PDF generation failed.\n";
    exit;
}

echo "2. Preparing Messages...\n";
$whatsappMsg = "Assalam-o-Alaikum *$candidateName*,\n\n"
    . "🎉 *Congratulations! You have been hired at Dawood Tech NextGen!*\n\n"
    . "📄 Your official *Offer Letter* is attached to this message.\n\n"
    . "🔐 *Your Login Credentials:*\n"
    . "🌐 *Portal URL:* $loginUrl\n"
    . "📧 *Email:* $candidateEmail\n"
    . "🔑 *Password:* `$password` (Copy-paste this code)\n\n"
    . "Best regards,\n"
    . "HR Department\n"
    . "*Dawood Tech NextGen*";

$htmlEmail = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
    <h2 style='color: #2563eb; text-align: center;'>Congratulations! 🎉</h2>
    <p>Dear <strong>$candidateName</strong>,</p>
    <p>Please find your official offer letter attached.</p>
    <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
        <p><strong>URL:</strong> <a href='$loginUrl'>$loginUrl</a></p>
        <p><strong>Email:</strong> $candidateEmail</p>
        <p><strong>Password:</strong> <code>$password</code></p>
    </div>
</div>";

echo "3. Sending Fallback Notification...\n";
$results = sendNotificationFallback([
    'email' => $candidateEmail,
    'name' => $candidateName,
    'mbl_number' => $candidateMbl,
    'subject' => 'Hiring Confirmation & Offer Letter - Verification Test',
    'html_content' => $htmlEmail,
    'whatsapp_msg' => $whatsappMsg,
    'pdf_content' => $pdfContent,
    'pdf_filename' => 'Test_Offer_Letter.pdf'
]);

echo "\nVerification Results:\n";
echo "-----------------\n";
echo "WhatsApp Success: " . ($results['whatsapp']['success'] ? 'Yes' : 'No') . "\n";
echo "Primary Email Success: " . ($results['primary_email']['success'] ? 'Yes' : 'No') . "\n";
echo "Gmail Fallback Success: " . ($results['gmail_email']['success'] ? 'Yes' : 'No') . "\n";
echo "Final Overall Success: " . ($results['final_success'] ? 'Yes' : 'No') . "\n";

if (!empty($results['error_logs'])) {
    echo "\nLogs/Errors:\n";
    foreach ($results['error_logs'] as $log) {
        echo "- $log\n";
    }
}

echo "\nVerification Finished.\n";
