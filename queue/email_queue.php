<?php
// queue/send_emails.php
define('PROCESSING', true);
require '../include/connection.php';
require '../vendor/autoload.php';
// require '/home2/dawoodte/public_html/taskdesk/include/connection.php';
// require '/home2/dawoodte/public_html/taskdesk/vendor/autoload.php';
ignore_user_abort(true);
set_time_limit(0);

ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
global $email_from; 
require_once dirname(__DIR__) . '/include/pdf_helper.php';
require_once dirname(__DIR__) . '/controller/PdfwhatsappApi.php';

function sendWelcomeEmailWithOfferLetter($toEmail, $name, $password, $tech_name, $pdfContent = null)
{
    $mail = new PHPMailer(true);

    try {
        // ... SMTP Settings ...
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        
        $mail->Timeout  = 10;
        $mail->Timelimit = 10;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to DawoodTech NextGen - Your Internship Offer Letter & Login Credentials';

        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+2 months'));
        $loginUrl = 'https://dawoodtechnextgen.org/taskdesk/login.php';

        $mailContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Segoe UI", Tahoma; line-height: 1.6; color: #333; max-width: 800px; margin: auto; }
                .header { background: linear-gradient(135deg, #deeafc, #c8dcfa); padding: 40px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 40px; background: #f9f9f9; }
                .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
                .credentials { background: #f0f7ff; border-left: 4px solid #3B81F6; }
                .btn-primary { background: linear-gradient(135deg, #3B81F6, #2563EB); color: white !important; padding: 14px 28px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 10px; }
                .footer { text-align: center; padding: 20px; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="' . BASE_URL . 'assets/images/logo.png" alt="DawoodTech NextGen" style="max-width:150px;margin-bottom:20px;">
                <p>Your Journey Begins Here</p>
            </div>
            <div class="content">
                <div class="card">
                    <h2 style="color:#3B81F6;">Dear ' . htmlspecialchars($name) . ',</h2>
                    <p>We are thrilled to welcome you to the DawoodTech NextGen family! You have been selected for our <strong>' . htmlspecialchars($tech_name) . '</strong> internship program.</p>
                    <div class="card credentials">
                        <h3>Your Login Credentials</h3>
                        <p><strong>Email:</strong> ' . htmlspecialchars($toEmail) . '</p>
                        <p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
                        <a style="text-decoration:none !important; color:white !important;" href="' . $loginUrl . '" class="btn-primary">Access TaskDesk</a>
                        <p style="font-size:14px;color:#777;">Please change your password after first login.</p>
                    </div>
                    <h3>Internship Details</h3>
                    <ul>
                        <li><strong>Duration:</strong> ' . $startDate . ' to ' . $endDate . ' (2 months)</li>
                        <li><strong>Technology:</strong> ' . htmlspecialchars($tech_name) . '</li>
                        <li><strong>Reporting Date:</strong> ' . $startDate . '</li>
                    </ul>
                    <h3>What to Expect</h3>
                    <ul>
                        <li>Real-world project experience</li>
                        <li>Mentorship from industry experts</li>
                        <li>Weekly workshops and activities</li>
                        <li>Certificate upon completion</li>
                    </ul>
                    <p>Your official offer letter is attached to this email.</p>
                </div>
            </div>
            <div class="footer">
                <p>Need help? Email us at support@dawoodtechnextgen.org</p>
                <p>© ' . date('Y') . ' DawoodTech NextGen</p>
            </div>
        </body>
        </html>';

        $mail->Body = $mailContent;

        if (!$pdfContent) {
            $pdfContent = generateOfferLetterHelper($name, $startDate, $endDate, $tech_name);
        }

        if ($pdfContent) {
            $fileName = 'Offer_Letter_' . preg_replace('/\s+/', '_', $name) . '.pdf';
            $mail->addStringAttachment($pdfContent, $fileName, 'base64', 'application/pdf');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Primary Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

function sendWelcomeEmailWithOfferLetterwithGmail($toEmail, $name, $password, $tech_name, $pdfContent = null)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = GMAIL_MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_MAIL_USERNAME;
        $mail->Password   = GMAIL_MAIL_PASSWORD;
        $mail->SMTPSecure = GMAIL_MAIL_ENCRYPTION;
        $mail->Port       = GMAIL_MAIL_PORT;
        
        $mail->Timeout  = 10;
        $mail->Timelimit = 10;

        $mail->setFrom(GMAIL_MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to DawoodTech NextGen - Your Internship Offer Letter & Login Credentials';

        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+2 months'));
        $loginUrl = 'https://dawoodtechnextgen.org/taskdesk/login.php';

        $mailContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Segoe UI", Tahoma; line-height: 1.6; color: #333; max-width: 800px; margin: auto; }
                .header { background: linear-gradient(135deg, #deeafc, #c8dcfa); padding: 40px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 40px; background: #f9f9f9; }
                .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
                .credentials { background: #f0f7ff; border-left: 4px solid #3B81F6; }
                .btn-primary { background: linear-gradient(135deg, #3B81F6, #2563EB); color: white !important; padding: 14px 28px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 10px; }
                .footer { text-align: center; padding: 20px; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="' . BASE_URL . 'assets/images/logo.png" alt="DawoodTech NextGen" style="max-width:150px;margin-bottom:20px;">
                <p>Your Journey Begins Here</p>
            </div>
            <div class="content">
                <div class="card">
                    <h2 style="color:#3B81F6;">Dear ' . htmlspecialchars($name) . ',</h2>
                    <p>We are thrilled to welcome you to the DawoodTech NextGen family! You have been selected for our <strong>' . htmlspecialchars($tech_name) . '</strong> internship program.</p>
                    <div class="card credentials">
                        <h3>Your Login Credentials</h3>
                        <p><strong>Email:</strong> ' . htmlspecialchars($toEmail) . '</p>
                        <p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
                        <a style="text-decoration:none !important; color:white !important;" href="' . $loginUrl . '" class="btn-primary">Access TaskDesk</a>
                        <p style="font-size:14px;color:#777;">Please change your password after first login.</p>
                    </div>
                    <h3>Internship Details</h3>
                    <ul>
                        <li><strong>Duration:</strong> ' . $startDate . ' to ' . $endDate . ' (2 months)</li>
                        <li><strong>Technology:</strong> ' . htmlspecialchars($tech_name) . '</li>
                        <li><strong>Reporting Date:</strong> ' . $startDate . '</li>
                    </ul>
                    <h3>What to Expect</h3>
                    <ul>
                        <li>Real-world project experience</li>
                        <li>Mentorship from industry experts</li>
                        <li>Weekly workshops and activities</li>
                        <li>Certificate upon completion</li>
                    </ul>
                    <p>Your official offer letter is attached to this email.</p>
                </div>
            </div>
            <div class="footer">
                <p>Need help? Email us at support@dawoodtechnextgen.org</p>
                <p>© ' . date('Y') . ' DawoodTech NextGen</p>
            </div>
        </body>
        </html>';

        $mail->Body = $mailContent;

        if (!$pdfContent) {
            $pdfContent = generateOfferLetterHelper($name, $startDate, $endDate, $tech_name);
        }

        if ($pdfContent) {
            $fileName = 'Offer_Letter_' . preg_replace('/\s+/', '_', $name) . '.pdf';
            $mail->addStringAttachment($pdfContent, $fileName, 'base64', 'application/pdf');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gmail fallback failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Process batch of emails (Cron mode)
$stmt = $conn->prepare("
    SELECT * FROM email_queue 
    WHERE status = 'pending' AND attempts < 5 
    ORDER BY created_at ASC 
    LIMIT 50
");
$stmt->execute();
$result = $stmt->get_result();

$processed = 0;
while ($job = $result->fetch_assoc()) {
    $startTime = microtime(true);
    echo "Processing Job ID: " . $job['id'] . "...\n";
    flush();

    $data = json_decode($job['data'], true);
    $jobId = $job['id'];
    $sent = false;
    $tempFile = null;

    echo "  - Starting Process...\n"; flush();

    // 0. Generate PDF once for all methods
    $startDate = date('Y-m-d');
    $endDate   = date('Y-m-d', strtotime('+2 months'));
    $pdfContent = generateOfferLetterHelper($data['name'], $startDate, $endDate, $data['tech_name']);

    // 1. Try WhatsApp first
    if (!empty($data['mbl_number']) && $pdfContent) {
        echo "  - Attempting WhatsApp API...\n"; flush();
        
        // Save PDF to temp for WhatsApp API to fetch
        $tempDir = dirname(__DIR__) . '/temp';
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
        $tempFile = $tempDir . '/Offer_Letter_' . $jobId . '_' . time() . '.pdf';
        file_put_contents($tempFile, $pdfContent);
        
        $publicFileUrl = BASE_URL . 'temp/' . basename($tempFile);
        $whatsappMsg = "Assalam-o-Alaikum " . $data['name'] . ",\n\n"
            . "🎉 *Congratulations!* You have been hired as a " . $data['tech_name'] . " Intern at DawoodTech NextGen.\n\n"
            . "🔐 *Your Login Credentials:*\n"
            . "📧 *Email:* " . $data['email'] . "\n"
            . "🔑 *Password:* " . $data['password'] . "\n"
            . "🌐 *TaskDesk:* https://dawoodtechnextgen.org/taskdesk/\n\n"
            . "Your official offer letter is following this message. Please change your password after your first login.\n\n"
            . "Best regards,\n"
            . "HR Department\n"
            . "*DawoodTech NextGen*";

        $res = whatsappFileApi($data['mbl_number'], $publicFileUrl, 'Offer_Letter.pdf', $whatsappMsg);
        if ($res['success']) {
            $sent = true;
            $email_from = 'From WhatsApp API';
            echo "  - WhatsApp Success.\n"; flush();
        } else {
            echo "  - WhatsApp Failed.\n"; flush();
        }
    }

    // 2. If WhatsApp failed or skipped, try Primary Mailer
    if (!$sent) {
        echo "  - Attempting Primary Mailer...\n"; flush();
        if (sendWelcomeEmailWithOfferLetter($data['email'], $data['name'], $data['password'], $data['tech_name'], $pdfContent)) {
            $sent = true;
            $email_from = 'From Server mailer';
            echo "  - Primary Mailer Success.\n"; flush();
        } 
        // 3. If Primary failed, try Gmail Mailer (Fallback)
        else {
            echo "  - Primary Failed. Attempting Gmail Mailer...\n"; flush();
            if (sendWelcomeEmailWithOfferLetterwithGmail($data['email'], $data['name'], $data['password'], $data['tech_name'], $pdfContent)) {
                $sent = true;
                $email_from = 'From Gmail mailer';
                echo "  - Gmail Mailer Success.\n"; flush();
            } else {
                echo "  - All Delivery Methods Failed.\n"; flush();
            }
        }
    }

    // CLEANUP: Always delete temp file if it was created
    if ($tempFile && file_exists($tempFile)) {
        unlink($tempFile);
        echo "  - Temp file deleted.\n"; flush();
    }

    // ---------------------------------------------------
    // CHECK DB CONNECTION before writing (Fixes "MySQL Gone Away")
    // ---------------------------------------------------
    if (!$conn->ping()) {
        echo "  - MySQL connection lost. Reconnecting...\n"; flush();
        $conn->close();
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }

    if ($sent) {
        // Success: Remove from queue
        $deleteStmt = $conn->prepare("DELETE FROM email_queue WHERE id = ?");
        $deleteStmt->bind_param('i', $jobId);
        $deleteStmt->execute();
        $deleteStmt->close();
        $processed++;
    } else {
        // Failure: Increment attempts so we don't loop forever
        echo "Failed to notify: " . $data['email'] . ". Incrementing attempts.\n";
        $updateStmt = $conn->prepare("UPDATE email_queue SET attempts = attempts + 1 WHERE id = ?");
        $updateStmt->bind_param('i', $jobId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Log duration
    $duration = microtime(true) - $startTime;
    echo "Job ID: $jobId completed in " . number_format($duration, 2) . " seconds.\n";
    flush();
}

echo "Queue processed: $processed notifications sent. last $email_from\n";
