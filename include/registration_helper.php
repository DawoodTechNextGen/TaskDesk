<?php
// Shared helpers for the registration "Yes, I am Interested / Not Interested" email
// flow: editable email settings (app_settings table), per-candidate response tokens,
// and the reject path shared between the admin UI and the public registration_response.php
// endpoint that candidates land on from their inbox.

// Self-installing schema: creates app_settings and adds registrations.response_token the
// first time any of these helpers run against a database that doesn't have them yet (same
// auto-create pattern as email_sent_logs in notification_helper.php). This means deploying
// this feature to a new environment (e.g. the live server) needs no manual SQL step - the
// table/column appear automatically on first use. database/registration_response_schema.sql
// still exists for anyone who prefers to run the migration by hand ahead of time.
function ensureRegistrationResponseSchema($conn) {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $conn->query("CREATE TABLE IF NOT EXISTS `app_settings` (
        `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columnCheck = $conn->query("SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'response_token'");

    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `registrations` ADD COLUMN `response_token` VARCHAR(64) DEFAULT NULL AFTER `email_status`");
        $conn->query("ALTER TABLE `registrations` ADD UNIQUE KEY `uk_registrations_response_token` (`response_token`)");
    }
}

function getAppSetting($conn, $key, $default = '') {
    ensureRegistrationResponseSchema($conn);
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
}

function saveAppSetting($conn, $key, $value) {
    ensureRegistrationResponseSchema($conn);
    $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param('ss', $key, $value);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// internship_type: 0 = Task Base Intern, 1 = Learning Base Intern (matches the `registrations`/`users` column).
function registrationTypeSuffix($internshipType) {
    return ((int)$internshipType === 1) ? 'learning_base' : 'task_base';
}

function registrationTypeLabel($internshipType) {
    return ((int)$internshipType === 1) ? 'Learning Base Internship' : 'Task Base Internship';
}

function defaultRegistrationEmailMessage($internshipType = 0) {
    $label = registrationTypeLabel($internshipType);
    return "<p>Thank you for applying for the DawoodTech NextGen $label Program.</p><p>Please let us know if you'd like to proceed by using the buttons below.</p>";
}

// Reads the email subject/body/interested-link for a given internship type,
// falling back to sensible defaults when the admin hasn't customized them yet.
function getRegistrationEmailSettings($conn, $internshipType) {
    $suffix = registrationTypeSuffix($internshipType);
    return [
        'subject' => getAppSetting($conn, "registration_email_subject_$suffix", 'Application Update - DawoodTech NextGen'),
        'body' => getAppSetting($conn, "registration_email_body_$suffix", defaultRegistrationEmailMessage($internshipType)),
        'link' => getAppSetting($conn, "registration_interested_link_$suffix", '')
    ];
}

function saveRegistrationEmailSettings($conn, $internshipType, $subject, $body, $link) {
    $suffix = registrationTypeSuffix($internshipType);
    saveAppSetting($conn, "registration_email_subject_$suffix", $subject);
    saveAppSetting($conn, "registration_email_body_$suffix", $body);
    saveAppSetting($conn, "registration_interested_link_$suffix", $link);
}

// Returns the candidate's response token, generating and persisting one on first use.
function ensureResponseToken($conn, $registrationId) {
    ensureRegistrationResponseSchema($conn);
    $stmt = $conn->prepare("SELECT response_token FROM registrations WHERE id = ?");
    $stmt->bind_param('i', $registrationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['response_token'])) {
        return $row['response_token'];
    }

    $token = bin2hex(random_bytes(24));
    $update = $conn->prepare("UPDATE registrations SET response_token = ? WHERE id = ?");
    $update->bind_param('si', $token, $registrationId);
    $update->execute();
    $update->close();

    return $token;
}

// The "Yes, I am Interested" button links straight to the configured payment/registration
// page whenever one is set, instead of routing through our own registration_response.php.
// That keeps the button working even if this app's own BASE_URL/server is unreachable -
// only the "Not Interested" button needs our server, since it has to update the DB status.
function buildInterestedLink($interestedLinkSetting, $responseToken) {
    if (!empty($interestedLinkSetting)) {
        return $interestedLinkSetting;
    }
    return rtrim(BASE_URL, '/') . '/registration_response.php?token=' . urlencode($responseToken) . '&action=interested';
}

// Builds the CTA email HTML shared by cron_send_emails.php and controller/registrations.php.
// $emailMessage is trusted HTML authored by an admin via the Quill editor on email_settings.php
// (bold, bullet/numbered lists, links) - not escaped, so it renders as real formatting in the email.
function buildRegistrationEmailHtml($candidateName, $emailMessage, $waLink, $interestedLink, $notInterestedLink) {
    $current_year = date('Y');

    return "
    <style>
        @media screen and (max-width: 600px) {
            .email-container { padding: 20px 10px !important; }
            .email-card { border-radius: 12px !important; }
            .email-header { padding: 20px 20px !important; }
            .email-body { padding: 30px 20px !important; }
            .email-logo { max-height: 40px !important; }
            .email-footer { padding: 24px 20px !important; }
        }
        .email-body-content p { margin: 0 0 14px 0; }
        .email-body-content p:last-child { margin-bottom: 0; }
        .email-body-content strong { color: #1E293B; }
        .email-body-content ul, .email-body-content ol { margin: 8px 0 16px 0; padding-left: 22px; }
        .email-body-content li { margin: 4px 0; }
        .email-body-content a { color: #2563EB; text-decoration: underline; word-break: break-word; }
    </style>
    <div class=\"email-container\" style=\"background-color: #F8FAFC; padding: 40px 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100%;\">
        <div class=\"email-card\" style=\"max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0;\">
            <div class=\"email-header\" style=\"background-color: #FFFFFF; padding: 28px 32px; text-align: center; border-bottom: 4px solid #2563EB;\">
                <img class=\"email-logo\" src=\"cid:logo_cid\" alt=\"DawoodTech NextGen\" style=\"max-height: 52px; width: auto; max-width: 100%; height: auto; display: inline-block;\">
            </div>
            <div class=\"email-body\" style=\"padding: 40px 32px; color: #0F172A; line-height: 1.6; font-size: 16px;\">
                <div style=\"margin-bottom: 24px;\">
                    <span style=\"background-color: #E0E7FF; color: #2563EB; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block;\">NextGen Career Portal</span>
                </div>
                <p style=\"margin-top: 0; font-weight: 700; font-size: 20px; color: #1E293B; letter-spacing: -0.3px;\">Dear " . htmlspecialchars($candidateName) . ",</p>
                <div class=\"email-body-content\" style=\"margin: 24px 0; color: #334155; border-left: 4px solid #2563EB; padding-left: 18px;\">
                    " . $emailMessage . "
                </div>

                <div style=\"text-align: center; margin: 32px 0 8px 0;\">
                    <p style=\"font-size: 14px; color: #64748B; margin-bottom: 14px; font-weight: 500;\">Are you interested in proceeding?</p>
                    <a href=\"" . $interestedLink . "\" target=\"_blank\" style=\"background-color: #16A34A; color: #FFFFFF; padding: 12px 26px; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-block; margin: 0 6px 10px 6px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);\">
                        Yes, I am Interested
                    </a>
                    <a href=\"" . $notInterestedLink . "\" target=\"_blank\" style=\"background-color: #FFFFFF; color: #64748B; padding: 12px 26px; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-block; margin: 0 6px 10px 6px; border: 1px solid #CBD5E1;\">
                        Not Interested
                    </a>
                </div>

                <div style=\"text-align: center; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #E2E8F0;\">
                    <p style=\"font-size: 13px; color: #94A3B8; margin-bottom: 10px;\">Have questions? Connect with us directly on WhatsApp:</p>
                    <a href=\"" . $waLink . "\" target=\"_blank\" style=\"background-color: #25D366; color: #FFFFFF; padding: 10px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; text-decoration: none; display: inline-block; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25);\">
                        <img src=\"cid:whatsapp_logo_cid\" alt=\"WhatsApp\" style=\"width: 16px; height: 16px; vertical-align: middle; margin-right: 8px; display: inline-block;\">
                        <span style=\"vertical-align: middle; display: inline-block;\">Message on WhatsApp</span>
                    </a>
                </div>
            </div>
            <div class=\"email-footer\" style=\"background-color: #1E293B; padding: 28px 24px; text-align: center; font-size: 12px; color: #94A3B8; border-top: 1px solid #E2E8F0;\">
                <p style=\"margin: 0 0 8px 0; font-weight: 600; color: #FFFFFF; font-size: 13px;\">DawoodTech NextGen</p>
                <p style=\"margin: 0; font-size: 11px;\">&copy; " . $current_year . " DawoodTech. All rights reserved.</p>
            </div>
        </div>
    </div>";
}

// Rejects a candidate and sends the rejection email. Shared by the admin "Reject"
// action (controller/registrations.php) and the public "Not Interested" email button
// (registration_response.php).
function rejectRegistrationAndNotify($conn, $id) {
    $stmt_fetch = $conn->prepare("
        SELECT r.name, r.email, r.remarks, t.name as technology
        FROM registrations r
        LEFT JOIN technologies t ON t.id = r.technology_id
        WHERE r.id = ?
    ");
    $stmt_fetch->bind_param('i', $id);
    $stmt_fetch->execute();
    $candidate = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$candidate) {
        return ['success' => false, 'message' => 'Candidate not found'];
    }

    $stmt = $conn->prepare("UPDATE registrations SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to reject candidate'];
    }
    $stmt->close();

    $tech_name = $candidate['technology'] ?? 'Jr Developer';
    $remarks = trim($candidate['remarks'] ?? '');

    if (!empty($remarks)) {
        $rejection_message = "
            <p>After careful review of your application, we regret to inform you that we will not be moving forward with your candidature at this time.</p>
            <p><strong>Feedback:</strong><br>" . nl2br(htmlspecialchars($remarks)) . "</p>
            <p>Please note that this decision was made after evaluating multiple applications, and it does not reflect negatively on your overall potential.</p>
        ";
    } else {
        $rejection_message = "
            <p>After careful review of your application, we regret to inform you that we will not be moving forward with your candidature at this time.</p>
            <p>Due to a high volume of applications, we are unable to provide individual feedback on this occasion.</p>
        ";
    }

    $subject = "Application Status Update - DawoodTech NextGen";
    $html_content = "
        <div style='font-family: Arial, sans-serif; line-height: 1.7; color: #333;'>
            <p>Dear " . htmlspecialchars($candidate['name']) . ",</p>
            <p>Thank you for your interest in the <strong>" . htmlspecialchars($tech_name) . " Internship</strong> at DawoodTech NextGen and for the time you invested in your application.</p>
            $rejection_message
            <p>We sincerely appreciate your interest in DawoodTech NextGen and encourage you to apply again in the future should a suitable opportunity arise.</p>
            <p style='color: #666; font-size: 0.9em; border-top: 1px solid #eee; padding-top: 12px; margin-top: 24px;'>
                <strong>Note:</strong> This is an automated message. Replies to this email will not be monitored.
            </p>
            <p>Kind regards,<br><strong>Hiring Team</strong><br>DawoodTech NextGen</p>
        </div>
    ";

    sendNotificationFallback([
        'email' => $candidate['email'],
        'name' => $candidate['name'],
        'subject' => $subject,
        'html_content' => $html_content
    ]);

    return ['success' => true, 'message' => 'Candidate rejected and notified via email'];
}
