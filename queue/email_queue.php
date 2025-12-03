<?php
// queue/send_emails.php
define('PROCESSING', true);
require '../include/connection.php';
require '../vendor/autoload.php';
// require '/home2/dawoodte/public_html/taskdesk/include/connection.php';
// require '/home2/dawoodte/public_html/taskdesk/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// Include your functions
function generateOfferLetterPDF($name, $startDate, $endDate, $tech_name)
{
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $bgImage = BASE_URL . "assets/images/offerletter.png";

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        font-family: Arial, sans-serif;
        background-image: url(' . $bgImage . ');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
    }
    .content-box {
        margin: 200px 40px 40px 40px;
        padding: 20px 40px;
        height: 700px;
        background: transparent;
        font-size: 13px;
    }
</style>

        </head>
        <body>
            <div class="content-box">
                <div class="section" style="text-align:right;">
                    <strong>Date:</strong> ' . htmlspecialchars($startDate) . '
                </div>
                <div class="section">
                    <strong>To:</strong><br>
                    ' . htmlspecialchars($name) . '<br>
                    <strong>Designation:</strong> Intern – ' . htmlspecialchars($tech_name) . '<br>
                    DawoodTech NextGen
                </div>
                <div class="section title">
                    Internship Offer – ' . htmlspecialchars($tech_name) . '
                </div>
                <div class="section">
                    <p>Dear ' . htmlspecialchars($name) . ',</p>
                    <p>We are pleased to offer you an internship opportunity from 
                    <strong>' . $startDate . '</strong> to <strong>' . $endDate . '</strong> at <strong>DawoodTech NextGen</strong> as a 
                    <strong>' . htmlspecialchars($tech_name) . ' Intern</strong>.</p>
                    <pThis internship will provide you with the chance to enhance your skills, gain practical exposure, and contribute to real-world projects under professional guidance. We believe your dedication and efforts will add value to our team, and we look forward to your valuable contribution and growth during this program.</p>
                    <p>We are confident that this experience will be a stepping stone in your professional journey, equipping you with the knowledge and confidence to excel in your career.</p>
                    <p>We value our professional relationship and look forward to your continued support. Should you have any questions or require further details, please do not hesitate to contact us.</p>
                </div>
                <div class="signature">
                    <strong>Sincerely,</strong><br>
                    Qamar Naveed<br>
                    Founder<br>
                    <strong?>DawoodTech NextGen</strong><br><br>
                    <strong>Contact Information:</strong><br>
                    <strong>Phone: </strong>+92-311-7305346<br>
                    <strong>Email: </strong>info@dawoodtechnextgen.org<br>
                    <strong>Website: </strong>https://dawoodtechnextgen.org
                </div>
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        return null;
    }
}
function sendWelcomeEmailWithOfferLetter($toEmail, $name, $password, $tech_name)
{
    $mail = new PHPMailer(true);

    try {

        // ---------------------------
        // SMTP Settings
        // ---------------------------
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = '83769ecefdbd49';
        $mail->Password   = '57a469f363c058';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;

        // Sender / Receiver
        $mail->setFrom('no-reply@dawoodtechnextgen.org', 'DawoodTech NextGen HR');
        $mail->addAddress($toEmail, $name);

        // Email Format
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to DawoodTech NextGen - Your Internship Offer & Credentials';

        // ---------------------------
        // Internship dates
        // ---------------------------
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+2 months'));

        $loginUrl = 'https://dawoodtech.org/taskdesk/login';

        // ---------------------------
        // Email Template
        // ---------------------------
        $mailContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: "Segoe UI", Tahoma; line-height: 1.6; color: #333; max-width: 700px; margin: auto; }
                .header { background: linear-gradient(135deg, #3B81F6, #2563EB); color: white; padding: 40px; text-align:center; border-radius: 10px 10px 0 0; }
                .content { padding: 40px; background: #f9f9f9; }
                .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .credentials { background: #f0f7ff; border-left: 4px solid #3B81F6; }
                .btn-primary { background: linear-gradient(135deg, #3B81F6, #2563EB); color:white; padding:14px 28px; border-radius:8px; text-decoration:none; display:inline-block; margin-top:10px; }
                .footer { text-align:center; padding:20px; margin-top:20px; color:#666; font-size:14px; }
            </style>
        </head>
        <body>

            <div class="header">
                <h1>Welcome to DawoodTech NextGen!</h1>
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
                        <a href="' . $loginUrl . '" class="btn-primary">Access Your Dashboard</a>
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
        </html>
        ';

        $mail->Body = $mailContent;

        // ---------------------------
        // Attach PDF - Offer Letter
        // ---------------------------
        $pdfContent = generateOfferLetterPDF($name, $startDate, $endDate, $tech_name);

        if ($pdfContent) {
            $fileName = 'Offer_Letter_' . preg_replace('/\s+/', '_', $name) . '.pdf';
            $mail->addStringAttachment($pdfContent, $fileName, 'base64', 'application/pdf');
        }

        // Send Email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}


// Process up to 20 emails per run
$stmt = $conn->prepare("
    SELECT * FROM email_queue 
    WHERE status = 'pending' AND attempts < 5 
    ORDER BY created_at ASC 
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();

$processed = 0;
while ($job = $result->fetch_assoc()) {
    $data = json_decode($job['data'], true);

    $success = sendWelcomeEmailWithOfferLetter(
        $data['email'],
        $data['name'],
        $data['password'],
        2, // role
        $data['tech_name'],
        $data['tech_id']
    );

    if ($success) {
        $update = $conn->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->bind_param('i', $job['id']);
        $update->execute();
        $processed++;
    } else {
        $attempts = $job['attempts'] + 1;
        $update = $conn->prepare("UPDATE email_queue SET attempts = ?, status = 'failed', error = 'SMTP Error' WHERE id = ?");
        $update->bind_param('ii', $attempts, $job['id']);
        $update->execute();
    }
}

echo "Email queue processed: $processed emails sent.\n";
