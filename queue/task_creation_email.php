<?php
// queue/send_emails.php
define('PROCESSING', true);
require '../include/connection.php';
require '../vendor/autoload.php';
// require '/home2/dawoodte/public_html/taskdesk/include/connection.php';
// require '/home2/dawoodte/public_html/taskdesk/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendWelcomeEmailWithOfferLetter($toEmail, $name, $password, $tech_name)
{
    $mail = new PHPMailer(true);

    try {

        // ---------------------------
        // SMTP Settings (from .env)
        // ---------------------------
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // Sender / Receiver
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $name);

        // Email Format
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to DawoodTech NextGen - Your Internship Offer Letter & Login Credentials';

        // ---------------------------
        // Internship dates
        // ---------------------------
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+2 months'));

        $loginUrl = 'https://dawoodtechnextgen.org/taskdesk/login.php';

        // ---------------------------
        // Email Template
        // ---------------------------
        $mailContent = '
        <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Task Assigned</title>
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.5;
            padding: 20px;
        }

        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        /* Minimal Header */
        .header {
            padding: 40px 40px 30px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 8px;
        }

        .tagline {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }

        .notification-badge {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        /* Main Content */
        .content {
            padding: 40px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
            color: #1e293b;
        }

        /* Task Card */
        .task-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border-left: 4px solid #3b82f6;
        }

        .task-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: white;
            font-size: 20px;
        }

        .task-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
        }

        .task-description {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        /* Essential Info */
        .info-grid {
            display: grid;
            gap: 16px;
            margin: 24px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 32px;
            height: 32px;
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: #1e293b;
        }

        .priority-tag {
            display: inline-block;
            padding: 4px 12px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Single CTA */
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        /* Simple Next Steps */
        .next-steps {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 32px;
        }

        .next-steps-title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .steps-list {
            list-style: none;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #475569;
        }

        .step-number {
            width: 24px;
            height: 24px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            flex-shrink: 0;
        }

        /* Minimal Footer */
        .footer {
            padding: 32px 40px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 13px;
        }

        .footer-links {
            margin: 20px 0;
        }

        .footer-links a {
            color: #64748b;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
        }

        .footer-links a:hover {
            color: #3b82f6;
        }

        /* Responsive */
        @media (max-width: 600px) {

            .content,
            .header {
                padding: 30px 24px;
            }

            .footer {
                padding: 24px;
            }

            .cta-button {
                display: block;
                width: 100%;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .task-card {
            animation: fadeIn 0.4s ease-out;
        }

        /* Print */
        @media print {
            .email-container {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <svg width="220px" height="50px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 641.5 138.99">
                <g id="Layer_2" data-name="Layer 2">
                    <path d="M166.61,317.91a50.62,50.62,0,0,1-14,34.7c-9.1,9.7-22.44,16.72-39.38,18.66H82.86V310.55h38.29v11.54H96.32v36.17h10.29a46.37,46.37,0,0,0,16.67-3,42,42,0,0,0,19.86-15.35,39.33,39.33,0,0,0,7-22.29c0-19.86-20.77-38.58-39.08-40.63H71v17.69h50.14V308h-78V294.69H58V264.56h55.22C142.72,264.56,166.61,291.84,166.61,317.91Z" transform="translate(-43.17 -264.56)" fill="#2775e9" />
                    <polygon points="37.88 45.99 37.88 106.71 24.41 106.71 24.41 57.54 0 57.54 0 45.99 37.88 45.99" fill="#2775e9" /><text transform="translate(137.28 87.25)" font-size="85" font-family="GothamRnd-Medium, Gotham Rounded" font-weight="500" letter-spacing="-0.06em">D<tspan x="61.2" y="0" letter-spacing="-0.07em">a</tspan>
                        <tspan x="104.72" y="0" letter-spacing="-0.08em">w</tspan>
                        <tspan x="170.51" y="0" letter-spacing="-0.06em">ood</tspan>
                        <tspan x="323.42" y="0" letter-spacing="-0.18em">T</tspan>
                        <tspan x="363.54" y="0" letter-spacing="-0.06em">ech</tspan>
                    </text><text transform="translate(494.44 121.5)" font-size="37" fill="#2775e9" stroke="#2775e9" stroke-miterlimit="10" font-family="Montserrat-Regular, Montserrat" letter-spacing="-0.06em">N<tspan x="27.79" y="0" letter-spacing="-0.08em">e</tspan>
                        <tspan x="47.29" y="0">xtGen</tspan>
                    </text>
                </g>
            </svg>
            <div class="tagline">TaskDesk</div>
            <div class="notification-badge">New Task</div>
        </div>

        <!-- Content -->
        <div class="content">
            <h2 class="greeting">Hi Alex,</h2>

            <p style="color: #64748b; margin-bottom: 24px;">
                A new task has been assigned to you:
            </p>

            <!-- Task Card -->
            <div class="task-card">
                <div class="task-icon">📋</div>

                <h3 class="task-title">Social Media Campaign Design</h3>

                <!-- Essential Info Only -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-icon">📅</div>
                        <div class="info-content">
                            <div class="info-label">Due Date</div>
                            <div class="info-value">Nov 15, 2023</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">👤</div>
                        <div class="info-content">
                            <div class="info-label">Assigned By</div>
                            <div class="info-value">Sarah Williams</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">⚡</div>
                        <div class="info-content">
                            <div class="info-label">Priority</div>
                            <div class="priority-tag">High</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single Clear CTA -->
            <div class="cta-container">
                <a href="https://dawoodtechnextgen.org/taskdesk/index.php" class="cta-button">
                    View Task Details
                </a>
            </div>

            <!-- Simple Next Steps -->
            <div class="next-steps">
                <div class="next-steps-title">
                    <span>📝</span>
                    <span>Next Steps</span>
                </div>
                <ul class="steps-list">
                    <li class="step-item">
                        <div class="step-number">1</div>
                        <div>Review task details</div>
                    </li>
                    <li class="step-item">
                        <div class="step-number">2</div>
                        <div>Ask questions if needed</div>
                    </li>
                    <li class="step-item">
                        <div class="step-number">3</div>
                        <div>Start working on it</div>
                    </li>
                </ul>
            </div>

            <!-- Closing -->
            <p style="margin-top: 32px; color: #64748b; font-size: 14px;">
                Questions? Your Supervisor Directly.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">

            <div style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                © 2025 DawoodTech NextGen.
            </div>
        </div>
    </div>

    <script>
        // Simple animation on load
        document.addEventListener("DOMContentLoaded", () => {
            const taskCard = document.querySelector(".task-card");
            taskCard.style.animationPlayState = "running";
        });
    </script>
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
        $data['tech_name']
    );

    if ($success) {
        $update = $conn->prepare("DELETE FROM email_queue WHERE id = ?");
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
