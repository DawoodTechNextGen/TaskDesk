<?php
// Standalone script to be executed by server cron job every 2 hours
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/connection.php';
require_once __DIR__ . '/include/notification_helper.php';
require_once __DIR__ . '/include/registration_helper.php';

date_default_timezone_set('Asia/Karachi');

function cronSendRegistrationEmails() {
    global $conn;

    // Fetch all candidates with status 'new'
    $sql = "SELECT id, name, email, internship_type FROM registrations WHERE status = 'new'";
    $result = $conn->query($sql);

    if (!$result) {
        return [
            'success' => false,
            'message' => 'Database query failed: ' . $conn->error
        ];
    }

    $sentCount = 0;
    $failedCount = 0;

    $waNumber = COMPANY_WHATSAPP;

    while ($row = $result->fetch_assoc()) {
        $candidate_id = (int)$row['id'];
        $candidate_name = $row['name'];
        $candidate_email = trim($row['email']);

        if (empty($candidate_email)) {
            error_log("Cron: Registration ID {$candidate_id} has no email address. Skipping.");
            continue;
        }

        $internshipType = (int)($row['internship_type'] ?? 0);
        $internTypeLabel = ($internshipType === 1) ? 'Learning Base Interns' : 'Task Base Interns';
        $waMessage = 'Interested in ' . $internTypeLabel;
        $waLink = 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMessage);

        $responseToken = ensureResponseToken($conn, $candidate_id);
        $emailSettings = getRegistrationEmailSettings($conn, $internshipType);
        $interestedLink = buildInterestedLink($emailSettings['link'], $responseToken);
        $notInterestedLink = rtrim(BASE_URL, '/') . '/registration_response.php?token=' . urlencode($responseToken) . '&action=not_interested';

        // Construct HTML email body matching company design, using the settings for this candidate's internship type
        $htmlContent = buildRegistrationEmailHtml($candidate_name, $emailSettings['body'], $waLink, $interestedLink, $notInterestedLink);

        $subject = $emailSettings['subject'];

        // Attempt Primary SMTP
        $emailSent = sendEmailPHPMailer($candidate_email, $candidate_name, $subject, $htmlContent, null, '', 'primary');
        if (!$emailSent) {
            // Fallback to Gmail SMTP
            $emailSent = sendEmailPHPMailer($candidate_email, $candidate_name, $subject, $htmlContent, null, '', 'gmail');
        }
        
        $email_status = $emailSent ? 1 : 2;
        
        // Update status to 'contact' and record email status
        $updateStmt = $conn->prepare("UPDATE registrations SET status = 'contact', email_status = ? WHERE id = ?");
        $updateStmt->bind_param('ii', $email_status, $candidate_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        if ($emailSent) {
            $sentCount++;
            error_log("Cron: Successfully notified and updated candidate ID {$candidate_id}.");
        } else {
            $failedCount++;
            error_log("Cron: Failed to send email to candidate ID {$candidate_id} ({$candidate_email}). Updated status to contact with failed email status.");
        }
    }
    
    return [
        'success' => true,
        'message' => "Cron process completed. Sent: {$sentCount}, Failed: {$failedCount}."
    ];
}

$response = cronSendRegistrationEmails();
echo json_encode($response);
?>
