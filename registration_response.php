<?php
// Public, no-login endpoint that candidates land on after clicking the
// "Yes, I am Interested" / "Not Interested" buttons in their registration email.
// Reached via a per-candidate response_token, not a session or registration ID,
// so it can be safely public.

require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/connection.php';
require_once __DIR__ . '/include/notification_helper.php';
require_once __DIR__ . '/include/registration_helper.php';

function renderMessagePage($title, $message, $isError = false) {
    $color = $isError ? '#DC2626' : '#16A34A';
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>" . htmlspecialchars($title) . "</title></head>
    <body style=\"margin:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#F8FAFC;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;\">
        <div style=\"max-width:480px;width:100%;background:#FFFFFF;border-radius:16px;padding:40px 32px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.05);border:1px solid #E2E8F0;\">
            <h2 style=\"color:$color;margin-top:0;\">" . htmlspecialchars($title) . "</h2>
            <p style=\"color:#334155;line-height:1.6;\">" . htmlspecialchars($message) . "</p>
        </div>
    </body></html>";
    exit;
}

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($token) || !in_array($action, ['interested', 'not_interested'], true)) {
    renderMessagePage('Invalid Link', 'This link is invalid or incomplete. Please contact us if you believe this is an error.', true);
}

$stmt = $conn->prepare("SELECT id, name, status FROM registrations WHERE response_token = ?");
$stmt->bind_param('s', $token);
$stmt->execute();
$registration = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registration) {
    renderMessagePage('Link Not Found', 'This link has expired or is no longer valid. Please contact us directly.', true);
}

if ($action === 'interested') {
    $paymentLink = trim(getAppSetting($conn, 'registration_interested_link', ''));

    if (empty($paymentLink)) {
        renderMessagePage('Thank You!', 'Thanks for confirming your interest, ' . $registration['name'] . '! Our team will reach out to you shortly with the next steps.');
    }

    header('Location: ' . $paymentLink);
    exit;
}

// action === 'not_interested'
if ($registration['status'] === 'rejected') {
    renderMessagePage('Already Recorded', 'We have already recorded your response. Thank you for letting us know.');
}

rejectRegistrationAndNotify($conn, $registration['id']);
logActivity('Registration Self-Rejected', "Candidate {$registration['name']} (ID {$registration['id']}) marked themselves as not interested via email link.");
renderMessagePage('Response Recorded', 'Thank you for letting us know, ' . $registration['name'] . '. We appreciate your time and wish you the best in your future endeavors.');
