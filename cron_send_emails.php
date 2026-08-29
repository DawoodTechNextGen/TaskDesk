<?php
// Standalone script to be executed by server cron job every 2 hours
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/connection.php';
require_once __DIR__ . '/include/notification_helper.php';

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
    
    $emailMessage = "Thank you for applying for the DawoodTech NextGen Internship Program.\n\nTo proceed with your application, please reply on WhatsApp with the word \"Interested\".\n\nWe will then share the next steps and internship details.\n\nBest Regards,\nDawoodTech NextGen Team";
    $formattedMessage = nl2br(htmlspecialchars($emailMessage));
    $waNumber = COMPANY_WHATSAPP;
    $current_year = date('Y');
    
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
        
        // Construct HTML email body matching company design
        $htmlContent = "
        <style>
            @media screen and (max-width: 600px) {
                .email-container {
                    padding: 20px 10px !important;
                }
                .email-card {
                    border-radius: 12px !important;
                }
                .email-header {
                    padding: 20px 20px !important;
                }
                .email-body {
                    padding: 30px 20px !important;
                }
                .email-logo {
                    max-height: 40px !important;
                }
                .email-footer {
                    padding: 24px 20px !important;
                }
            }
        </style>
        <div class=\"email-container\" style=\"background-color: #F8FAFC; padding: 40px 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100%;\">
            <div class=\"email-card\" style=\"max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0;\">
                <!-- White Header with Logo and Blue bottom border -->
                <div class=\"email-header\" style=\"background-color: #FFFFFF; padding: 28px 32px; text-align: center; border-bottom: 4px solid #2563EB;\">
                    <img class=\"email-logo\" src=\"cid:logo_cid\" alt=\"DawoodTech NextGen\" style=\"max-height: 52px; width: auto; max-width: 100%; height: auto; display: inline-block;\">
                </div>
                <!-- Body Content -->
                <div class=\"email-body\" style=\"padding: 40px 32px; color: #0F172A; line-height: 1.6; font-size: 16px;\">
                    <div style=\"margin-bottom: 24px;\">
                        <span style=\"background-color: #E0E7FF; color: #2563EB; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block;\">NextGen Career Portal</span>
                    </div>
                    <p style=\"margin-top: 0; font-weight: 700; font-size: 20px; color: #1E293B; letter-spacing: -0.3px;\">Dear " . htmlspecialchars($candidate_name) . ",</p>
                    <div style=\"margin: 24px 0; color: #334155; border-left: 4px solid #2563EB; padding-left: 18px;\">
                        " . $formattedMessage . "
                    </div>
                    
                    <!-- Interactive WhatsApp Button -->
                    <div style=\"text-align: center; margin: 36px 0 24px 0;\">
                        <p style=\"font-size: 14px; color: #64748B; margin-bottom: 12px; font-weight: 500;\">Are you interested? Let's connect directly on WhatsApp:</p>
                        <a href=\"" . $waLink . "\" target=\"_blank\" style=\"background-color: #25D366; color: #FFFFFF; padding: 12px 30px; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-block; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25); text-align: center; vertical-align: middle;\">
                            <img src=\"cid:whatsapp_logo_cid\" alt=\"WhatsApp\" style=\"width: 18px; height: 18px; vertical-align: middle; margin-right: 8px; display: inline-block;\">
                            <span style=\"vertical-align: middle; display: inline-block;\">Message on WhatsApp</span>
                        </a>
                    </div>

                    <div style=\"margin-top: 32px; padding-top: 24px; border-top: 1px solid #E2E8F0; text-align: center;\">
                        <p style=\"margin: 0; font-size: 13px; color: #64748B;\">If you have any questions, please feel free to reach out to us on WhatsApp.</p>
                    </div>
                </div>
                <!-- Footer with Dark Slate matching the logo text -->
                <div class=\"email-footer\" style=\"background-color: #1E293B; padding: 28px 24px; text-align: center; font-size: 12px; color: #94A3B8; border-top: 1px solid #E2E8F0;\">
                    <p style=\"margin: 0 0 8px 0; font-weight: 600; color: #FFFFFF; font-size: 13px;\">DawoodTech NextGen</p>
                    <p style=\"margin: 0; font-size: 11px;\">&copy; " . $current_year . " DawoodTech. All rights reserved.</p>
                </div>
            </div>
        </div>";
        
        $subject = 'Application Update - DawoodTech NextGen';
        
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
