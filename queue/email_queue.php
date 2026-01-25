// queue/send_emails.php
define('PROCESSING', true);
require '../include/connection.php';
require '../vendor/autoload.php';
ignore_user_abort(true);
set_time_limit(0);

ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

require_once dirname(__DIR__) . '/include/pdf_helper.php';
require_once dirname(__DIR__) . '/include/notification_helper.php';

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
    
    // 0. Generate PDF once
    $startDate = date('Y-m-d');
    $endDate   = date('Y-m-d', strtotime('+2 months'));
    $pdfContent = generateOfferLetterHelper($data['name'], $startDate, $endDate, $data['tech_name']);

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

    $htmlContent = '
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
                <h2 style="color:#3B81F6;">Dear ' . htmlspecialchars($data['name']) . ',</h2>
                <p>We are thrilled to welcome you to the DawoodTech NextGen family! You have been selected for our <strong>' . htmlspecialchars($data['tech_name']) . '</strong> internship program.</p>
                <div class="card credentials">
                    <h3>Your Login Credentials</h3>
                    <p><strong>Email:</strong> ' . htmlspecialchars($data['email']) . '</p>
                    <p><strong>Password:</strong> ' . htmlspecialchars($data['password']) . '</p>
                    <a style="text-decoration:none !important; color:white !important;" href="https://dawoodtechnextgen.org/taskdesk/login.php" class="btn-primary">Access TaskDesk</a>
                    <p style="font-size:14px;color:#777;">Please change your password after first login.</p>
                </div>
                <h3>Internship Details</h3>
                <ul>
                    <li><strong>Duration:</strong> ' . $startDate . ' to ' . $endDate . ' (2 months)</li>
                    <li><strong>Technology:</strong> ' . htmlspecialchars($data['tech_name']) . '</li>
                    <li><strong>Reporting Date:</strong> ' . $startDate . '</li>
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

    // Call unified notification helper
    $res = sendNotificationFallback([
        'email' => $data['email'],
        'name' => $data['name'],
        'mbl_number' => $data['mbl_number'] ?? '',
        'subject' => 'Welcome to DawoodTech NextGen - Offer Letter & Login Credentials',
        'html_content' => $htmlContent,
        'pdf_content' => $pdfContent,
        'pdf_filename' => 'Offer_Letter_' . preg_replace('/\s+/', '_', $data['name']) . '.pdf',
        'whatsapp_msg' => $whatsappMsg
    ]);

    // CHECK DB CONNECTION before writing
    if (!$conn->ping()) {
        $conn->close();
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    if ($res['final_success']) {
        $deleteStmt = $conn->prepare("DELETE FROM email_queue WHERE id = ?");
        $deleteStmt->bind_param('i', $jobId);
        $deleteStmt->execute();
        $deleteStmt->close();
        $processed++;
        echo "  - Success.\n";
    } else {
        echo "  - All Delivery Methods Failed. Incrementing attempts.\n";
        $updateStmt = $conn->prepare("UPDATE email_queue SET attempts = attempts + 1 WHERE id = ?");
        $updateStmt->bind_param('i', $jobId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    $duration = microtime(true) - $startTime;
    echo "Job ID: $jobId completed in " . number_format($duration, 2) . " seconds.\n";
    flush();
}

echo "Queue processed: $processed notifications sent.\n";

