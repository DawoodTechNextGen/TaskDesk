<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../controller/PdfwhatsappApi.php';

/**
 * Centralized notification function with 3-tier fallback:
 * 1. WhatsApp (if number provided)
 * 2. Server Email (Primary)
 * 3. Gmail (Fallback)
 */
function sendNotificationFallback($params) {
    $toEmail = $params['email'] ?? '';
    $toName = $params['name'] ?? '';
    $toMbl = $params['mbl_number'] ?? '';
    $subject = $params['subject'] ?? 'DawoodTech NextGen';
    $message = $params['message'] ?? '';
    $htmlContent = $params['html_content'] ?? '';
    $pdfContent = $params['pdf_content'] ?? null;
    $pdfFileName = $params['pdf_filename'] ?? 'document.pdf';
    $whatsappMsg = $params['whatsapp_msg'] ?? $message;

    $results = [
        'whatsapp' => ['attempted' => false, 'success' => false],
        'primary_email' => ['attempted' => false, 'success' => false],
        'gmail_email' => ['attempted' => false, 'success' => false],
        'final_success' => false,
        'error_logs' => []
    ];

    // 1. Try WhatsApp
    if (!empty($toMbl)) {
        $results['whatsapp']['attempted'] = true;
        if ($pdfContent) {
            // WhatsApp with file requires a public URL
            $tempDir = dirname(__DIR__) . '/temp';
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                     $results['error_logs'][] = "Failed to create temp directory: $tempDir";
                }
            }
            
            $tempFileName = 'tmp_' . time() . '_' . uniqid() . '.pdf';
            $tempFile = $tempDir . '/' . $tempFileName;
            
            if (file_put_contents($tempFile, $pdfContent)) {
                // Ensure BASE_URL has trailing slash
                $baseUrlNormalized = rtrim(BASE_URL, '/') . '/';
                $publicFileUrl = $baseUrlNormalized . 'temp/' . $tempFileName;
                
                error_log("Attempting WhatsApp File. BASE_URL: " . BASE_URL . " | Normalized: " . $baseUrlNormalized);
                error_log("Public File URL: " . $publicFileUrl);
                
                $res = whatsappFileApi($toMbl, $publicFileUrl, $pdfFileName, $whatsappMsg);
                
                if ($res['success']) {
                    $results['whatsapp']['success'] = true;
                    $results['final_success'] = true;
                } else {
                    $results['error_logs'][] = "WhatsApp File failed: " . ($res['message'] ?? 'Unknown error');
                }
            } else {
                $results['error_logs'][] = "Failed to write temp file: $tempFile";
            }
        } else {
            $res = whatsappApi($toMbl, $whatsappMsg);
            if ($res['success']) {
                $results['whatsapp']['success'] = true;
                $results['final_success'] = true;
            } else {
                $results['error_logs'][] = "WhatsApp Text failed: " . ($res['message'] ?? 'Unknown error');
            }
        }
    }

    // 2. Fallback to Primary Email
    if (!$results['final_success']) {
        $results['primary_email']['attempted'] = true;
        error_log("Attempting Primary Email Fallback to: $toEmail");
        if (sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, 'primary')) {
            $results['primary_email']['success'] = true;
            $results['final_success'] = true;
        } else {
            $results['error_logs'][] = "Primary Email failed. Check error_log for SMTP details.";
        }
    }

    // 3. Fallback to Gmail
    if (!$results['final_success']) {
        $results['gmail_email']['attempted'] = true;
        error_log("Attempting Gmail Email Fallback to: $toEmail");
        if (sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, 'gmail')) {
            $results['gmail_email']['success'] = true;
            $results['final_success'] = true;
        } else {
            $results['error_logs'][] = "Gmail Fallback failed. Check error_log for SMTP details.";
        }
    }

    // 4. Final Cleanup (if temp file was created)
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }

    return $results;
}

function sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent = null, $pdfFileName = 'Offer_Letter.pdf', $type = 'primary') {
    global $conn;

    // Force connection setup if it is not available in the global scope
    if (!isset($conn) || !$conn) {
        if (class_exists('Database')) {
            $db = new Database();
            $conn = $db->getConnection();
        } else {
            $connectionFile = __DIR__ . '/connection.php';
            if (file_exists($connectionFile)) {
                include_once $connectionFile;
            }
        }
    }

    // Auto-create logs table if it doesn't exist
    if (isset($conn) && $conn) {
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS `email_sent_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `sender_email` VARCHAR(255) NOT NULL,
                `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            error_log("Database Error: Failed to create email_sent_logs table: " . $e->getMessage());
        }
    }

    // Check hourly limit for primary SMTP (30 emails per hour limit)
    if ($type === 'primary' && isset($conn) && $conn) {
        try {
            $sender = MAIL_USERNAME;
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM email_sent_logs WHERE sender_email = ? AND sent_at > NOW() - INTERVAL 1 HOUR");
            if ($stmt) {
                $stmt->bind_param('s', $sender);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $sentCount = $result['total'] ?? 0;
                $stmt->close();

                if ($sentCount >= 30) {
                    // Route directly to Gmail fallback
                    error_log("Primary SMTP hourly limit (30) reached. Sent in last hour: $sentCount. Routing this email to Gmail SMTP fallback.");
                    $type = 'gmail';
                }
            } else {
                error_log("Database Error: Failed to prepare SELECT stmt for hourly limit check: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("Database Exception checking hourly limit: " . $e->getMessage());
        }
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        // $mail->SMTPDebug = 2; // Enable for deep debugging if needed
        // $mail->Debugoutput = function($str, $level) { error_log("SMTP DEBUG: $str"); };

        if ($type === 'primary') {
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        } else {
            $mail->Host       = GMAIL_MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = GMAIL_MAIL_USERNAME;
            $mail->Password   = GMAIL_MAIL_PASSWORD;
            $mail->SMTPSecure = GMAIL_MAIL_ENCRYPTION;
            $mail->Port       = GMAIL_MAIL_PORT;
            $mail->setFrom(GMAIL_MAIL_USERNAME, MAIL_FROM_NAME);
        }

        $mail->Timeout  = 10; // Set to 10s to prevent AJAX timeouts in browser
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        
        // Embed logo as inline CID attachment only if referenced in HTML
        if (strpos($htmlContent, 'cid:logo_cid') !== false) {
            $logoPath = dirname(__DIR__) . '/assets/images/logo.png';
            if (file_exists($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'logo_cid', 'logo.png', 'base64', 'image/png');
            }
        }

        // Embed WhatsApp logo as inline CID attachment only if referenced in HTML
        if (strpos($htmlContent, 'cid:whatsapp_logo_cid') !== false) {
            $waLogoPath = dirname(__DIR__) . '/assets/images/whatsapp_logo.png';
            if (file_exists($waLogoPath)) {
                $mail->addEmbeddedImage($waLogoPath, 'whatsapp_logo_cid', 'whatsapp_logo.png', 'base64', 'image/png');
            }
        }
        
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;

        if ($pdfContent) {
            $mail->addStringAttachment($pdfContent, $pdfFileName, 'base64', 'application/pdf');
        }

        $sent = $mail->send();

        // If successfully sent via primary SMTP, log it to the database
        if ($sent && $type === 'primary' && isset($conn) && $conn) {
            try {
                $sender = MAIL_USERNAME;
                $stmtInsert = $conn->prepare("INSERT INTO email_sent_logs (sender_email) VALUES (?)");
                if ($stmtInsert) {
                    $stmtInsert->bind_param('s', $sender);
                    $stmtInsert->execute();
                    $stmtInsert->close();
                } else {
                    error_log("Database Error: Failed to prepare INSERT stmt for sent email log: " . $conn->error);
                }
            } catch (Exception $e) {
                error_log("Database Exception logging sent email: " . $e->getMessage());
            }
        }

        return $sent;
    } catch (Exception $e) {
        error_log("$type Email PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
