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
    $subject = $params['subject'] ?? 'Notification from DawoodTech NextGen';
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
            // WhatsApp with file requires a public URL or local path depending on API
            // For now, we use the logic from email_queue.php: save to temp
            $tempDir = dirname(__DIR__) . '/temp';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $tempFile = $tempDir . '/tmp_' . time() . '_' . uniqid() . '.pdf';
            file_put_contents($tempFile, $pdfContent);
            
            $publicFileUrl = BASE_URL . 'temp/' . basename($tempFile);
            $res = whatsappFileApi($toMbl, $publicFileUrl, $pdfFileName, $whatsappMsg);
            
            // Cleanup temp file after a short delay or immediately if API is synchronous
            // Note: If API is async, it might need the file to persist. 
            // But usually, curl_exec waits for response.
            if (file_exists($tempFile)) unlink($tempFile);

            if ($res['success']) {
                $results['whatsapp']['success'] = true;
                $results['final_success'] = true;
            } else {
                $results['error_logs'][] = "WhatsApp File failed: " . ($res['response'] ?? 'Unknown error');
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
        if (sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, 'primary')) {
            $results['primary_email']['success'] = true;
            $results['final_success'] = true;
        } else {
            $results['error_logs'][] = "Primary Email failed.";
        }
    }

    // 3. Fallback to Gmail
    if (!$results['final_success']) {
        $results['gmail_email']['attempted'] = true;
        if (sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, 'gmail')) {
            $results['gmail_email']['success'] = true;
            $results['final_success'] = true;
        } else {
            $results['error_logs'][] = "Gmail Fallback failed.";
        }
    }

    return $results;
}

/**
 * Internal helper for PHPMailer
 */
function sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent = null, $pdfFileName = 'Offer_Letter.pdf', $type = 'primary') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
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

        $mail->Timeout  = 10;
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;

        if ($pdfContent) {
            $mail->addStringAttachment($pdfContent, $pdfFileName, 'base64', 'application/pdf');
        }

        return $mail->send();
    } catch (Exception $e) {
        error_log("$type Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
