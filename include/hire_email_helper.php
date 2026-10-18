<?php
/**
 * The "you're hired" email: login credentials plus the offer letter PDF. Sent
 * when a candidate is hired (controller/registrations.php) and again from the
 * Interns page when it never reached the intern (controller/user.php).
 */
require_once __DIR__ . '/pdf_helper.php';
require_once __DIR__ . '/notification_helper.php';

// email_outbox rows are matched on this subject to show the delivery status.
const HIRE_EMAIL_SUBJECT = 'Hiring Confirmation & Offer Letter - DawoodTech NextGen';

function buildHireEmailHtml($name, $email, $password) {
    $loginUrl = BASE_URL;
    return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #2563eb; text-align: center;'>Congratulations! 🎉</h2>
                <p>Dear <strong>" . $name . "</strong>,</p>
                <p>We are thrilled to inform you that you have been hired at <strong>Dawood Tech NextGen</strong>.</p>
                <p>Please find your official offer letter attached to this email.</p>

                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>🔐 Your Login Credentials:</h3>
                    <p><strong>URL:</strong> <a href='$loginUrl'>$loginUrl</a></p>
                    <p><strong>Email:</strong> " . $email . "</p>
                    <p><strong>Password:</strong> <code style='background: #e5e7eb; padding: 2px 5px; border-radius: 3px;'>" . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . "</code></p>
                </div>

                <p>We look forward to your valuable contribution!</p>
                <p>Best regards,<br><strong>HR Department</strong><br>DawoodTech NextGen</p>
            </div>";
}

/**
 * Sends the hire email again for an existing intern, with their current
 * password and an offer letter dated from their internship start. Returns the
 * sendNotificationFallback() result.
 */
function resendHireEmail($conn, $userId) {
    $stmt = $conn->prepare("SELECT u.name, u.email, u.plain_password, u.internship_duration, u.created_at, t.name AS tech_name
        FROM users u LEFT JOIN technologies t ON t.id = u.tech_id
        WHERE u.id = ? AND u.user_role = 2");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $intern = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$intern) {
        throw new Exception('Intern not found');
    }
    if (empty($intern['email'])) {
        throw new Exception('This intern has no email address');
    }

    // created_at is the internship start date (reset on hire)
    $start = strtotime($intern['created_at']);
    $startDate = date('d-M-Y', $start);
    $endDate = $intern['internship_duration'] ? date('d-M-Y', strtotime('+' . $intern['internship_duration'], $start)) : $startDate;
    $pdfContent = generateOfferLetterHelper($intern['name'], $startDate, $endDate, $intern['tech_name'], $startDate);
    if (!$pdfContent) {
        throw new Exception('Could not generate the offer letter');
    }

    return sendNotificationFallback([
        'email' => $intern['email'],
        'name' => $intern['name'],
        'subject' => HIRE_EMAIL_SUBJECT,
        'html_content' => buildHireEmailHtml($intern['name'], $intern['email'], (string)$intern['plain_password']),
        'pdf_content' => $pdfContent,
        'pdf_filename' => 'Offer_Letter_' . $intern['name'] . '.pdf',
        'gmail_first' => lastEmailUndelivered($conn, $intern['email'], [HIRE_EMAIL_SUBJECT])
    ]);
}
