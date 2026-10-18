<?php
/**
 * The assessment invitation email (portal login for the candidate's attempt).
 * Sent by "Send Assessment" and again by "Resend Email" on the Assessment page
 * when it never reached the candidate (controller/registrations.php).
 */
require_once __DIR__ . '/notification_helper.php';

// email_outbox rows are matched on these subjects to show the delivery status.
const ASSESSMENT_INVITE_SUBJECT = 'Assessment Invitation - DawoodTech NextGen';
const ASSESSMENT_ATTEMPT_SUBJECT = 'New Assessment Attempt - DawoodTech NextGen';

function buildAssessmentInviteHtml($name, $email, $password, array $assessment) {
    $loginUrl = BASE_URL;
    return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #2563eb; text-align: center;'>Assessment Invitation 📝</h2>
                <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>As part of your application for the <strong>" . htmlspecialchars($assessment['tech_name']) . "</strong> internship, please complete the following assessment:</p>
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Assessment:</strong> " . htmlspecialchars($assessment['title']) . "</p>
                    <p><strong>Time Limit:</strong> " . (int)$assessment['duration_minutes'] . " minutes</p>
                    <p><strong>URL:</strong> <a href='$loginUrl'>$loginUrl</a></p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Password:</strong> <code style='background: #e5e7eb; padding: 2px 5px; border-radius: 3px;'>" . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . "</code></p>
                </div>
                <p style='color:#b91c1c;'><strong>Important:</strong> You get one attempt only and the timer cannot be paused once started. Please ensure a stable internet connection before you begin.</p>
                <p>Best regards,<br><strong>HR Department</strong><br>DawoodTech NextGen</p>
            </div>";
}

/**
 * Latest delivery status of the candidate's assessment emails, as an SQL
 * expression on registrations alias `r`. NULL when nothing was logged.
 */
function assessmentEmailStatusSql($conn) {
    $invite = $conn->real_escape_string(ASSESSMENT_INVITE_SUBJECT);
    $attempt = $conn->real_escape_string(ASSESSMENT_ATTEMPT_SUBJECT);
    return "(SELECT eo.status FROM email_outbox eo
             WHERE eo.recipient_email = r.email AND eo.subject IN ('$invite', '$attempt')
             ORDER BY eo.id DESC LIMIT 1)";
}

/**
 * Sends the invitation again for the candidate's current attempt with the same
 * login, without creating a new attempt. Only for attempts not started yet.
 * Returns the sendNotificationFallback() result.
 */
function resendAssessmentInviteEmail($conn, $registrationId) {
    $stmt = $conn->prepare("SELECT ca.status, r.name, r.email, u.plain_password, a.title, a.duration_minutes, t.name AS tech_name
        FROM candidate_assessments ca
        JOIN registrations r ON r.id = ca.registration_id
        LEFT JOIN users u ON u.id = ca.user_id
        JOIN assessments a ON a.id = ca.assessment_id
        LEFT JOIN technologies t ON t.id = a.technology_id
        WHERE ca.registration_id = ? ORDER BY ca.id DESC LIMIT 1");
    $stmt->bind_param('i', $registrationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('No assessment has been sent to this candidate yet.');
    }
    if ($row['status'] !== 'pending') {
        throw new Exception('The candidate has already started this assessment. Use "Resend" to set up a new attempt.');
    }
    if ($row['plain_password'] === null || $row['plain_password'] === '') {
        throw new Exception('The assessment login for this candidate no longer exists. Use "Resend" to set up a new attempt.');
    }

    return sendNotificationFallback([
        'email' => $row['email'],
        'name' => $row['name'],
        'subject' => ASSESSMENT_INVITE_SUBJECT,
        'html_content' => buildAssessmentInviteHtml($row['name'], $row['email'], $row['plain_password'], $row),
        'gmail_first' => lastEmailUndelivered($conn, $row['email'], [ASSESSMENT_INVITE_SUBJECT, ASSESSMENT_ATTEMPT_SUBJECT])
    ]);
}
