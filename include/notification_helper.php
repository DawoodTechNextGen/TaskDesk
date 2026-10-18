<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Centralized notification function with 2-tier fallback:
 * 1. Server Email (Primary)
 * 2. Gmail (Fallback)
 *
 * WhatsApp is switched off for now: its API has been answering with HTTP 301
 * since March and the file endpoint is not configured, so every attempt only
 * added a 30s timeout. 'mbl_number' and 'whatsapp_msg' are still accepted so
 * callers don't need to change when it comes back. A primary send that the
 * relay rejects later is retried through Gmail by cron_process_bounces.php.
 * Pass 'gmail_first' => true to try Gmail before the primary server.
 */
function sendNotificationFallback($params) {
    $toEmail = $params['email'] ?? '';
    $toName = $params['name'] ?? '';
    $subject = $params['subject'] ?? 'DawoodTech NextGen';
    $htmlContent = $params['html_content'] ?? '';
    $pdfContent = $params['pdf_content'] ?? null;
    $pdfFileName = $params['pdf_filename'] ?? 'document.pdf';

    $results = [
        'primary_email' => ['attempted' => false, 'success' => false],
        'gmail_email' => ['attempted' => false, 'success' => false],
        'final_success' => false,
        'error_logs' => []
    ];

    // Primary first, then Gmail. 'gmail_first' swaps the order, for a resend to
    // an address the primary relay already bounced.
    $order = !empty($params['gmail_first']) ? ['gmail', 'primary'] : ['primary', 'gmail'];
    foreach ($order as $type) {
        $key = $type === 'primary' ? 'primary_email' : 'gmail_email';
        $label = $type === 'primary' ? 'Primary' : 'Gmail';
        $results[$key]['attempted'] = true;
        error_log("Attempting $label Email to: $toEmail");
        if (sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, $type)) {
            $results[$key]['success'] = true;
            $results['final_success'] = true;
            break;
        }
        $results['error_logs'][] = "$label Email failed. Check error_log for SMTP details.";
    }

    return $results;
}

/**
 * Same as sendNotificationFallback(), but the send happens after the JSON
 * response has already reached the browser. Two SMTP attempts (10s each)
 * used to keep the admin's request - and, through the
 * session lock, every other request of theirs - waiting on the network.
 */
function queueNotification($params) {
    static $registered = false;
    $GLOBALS['__queued_notifications'][] = $params;

    if (!$registered) {
        $registered = true;
        // Buffer the response so it can be sent with an explicit length on shutdown.
        ob_start();
        register_shutdown_function('flushResponseAndSendQueuedNotifications');
    }
}

function flushResponseAndSendQueuedNotifications() {
    $queue = $GLOBALS['__queued_notifications'] ?? [];

    $body = '';
    while (ob_get_level() > 0) {
        $body = ob_get_clean() . $body;
    }
    if (!headers_sent()) {
        header('Content-Encoding: none');
        header('Content-Length: ' . strlen($body));
        header('Connection: close');
    }
    echo $body;
    flush();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    ignore_user_abort(true);
    set_time_limit(0);

    // The controller closes its connection before shutdown, and the email
    // helper logs sends through the global $conn, so open a fresh one.
    try {
        $GLOBALS['conn'] = (new Database())->getConnection();
    } catch (Throwable $e) {
        $GLOBALS['conn'] = null;
    }

    foreach ($queue as $params) {
        try {
            $res = sendNotificationFallback($params);
            if (empty($res['final_success'])) {
                error_log('Queued notification to ' . ($params['email'] ?? '') . ' failed: ' . implode(' | ', $res['error_logs']));
            }
        } catch (Throwable $e) {
            error_log('Queued notification to ' . ($params['email'] ?? '') . ' threw: ' . $e->getMessage());
        }
    }
}

/**
 * False when the address is malformed or its domain has no MX/A record (e.g. a
 * placeholder like manager@taskdesk.com on a staff account). Sending there only
 * produces an "Address not found" bounce and uses up the hourly SMTP quota.
 */
function emailDomainCanReceive($email) {
    static $cache = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $domain = strtolower(substr(strrchr($email, '@'), 1));
    if (!isset($cache[$domain])) {
        $mx = @dns_get_record($domain, DNS_MX) ?: [];
        if ($mx) {
            // A "Null MX" (target ".") means the domain accepts no mail at all.
            $cache[$domain] = (bool)array_filter($mx, function ($r) {
                return trim($r['target'] ?? '', '. ') !== '';
            });
        } else {
            $cache[$domain] = checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
        }
    }
    return $cache[$domain];
}

function sendEmailPHPMailer($toEmail, $toName, $subject, $htmlContent, $pdfContent = null, $pdfFileName = 'Offer_Letter.pdf', $type = 'primary') {
    global $conn;

    if (!emailDomainCanReceive($toEmail)) {
        error_log("Skipping email to $toEmail: domain does not exist or cannot receive mail.");
        // Recorded once, on the Gmail attempt every caller makes after the
        // primary one, so a mistyped address shows up as "not delivered".
        if ($type === 'gmail') {
            logEmailOutbox($toEmail, $toName, $subject, $htmlContent, null, $pdfFileName, $type,
                ['sent' => false, 'message_id' => null, 'error' => 'Invalid email address: the domain does not exist or cannot receive mail']);
        }
        return false;
    }

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

    $res = deliverEmailSMTP($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, $type);

    // If successfully sent via primary SMTP, log it to the database
    if ($res['sent'] && $type === 'primary' && isset($conn) && $conn) {
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

    // A failed primary send is not recorded: every caller retries it through
    // Gmail straight away, and that attempt is the one that counts.
    if ($res['sent'] || $type === 'gmail') {
        logEmailOutbox($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, $type, $res);
    }

    return $res['sent'];
}

/**
 * Sends one email over the given SMTP account without any logging or limits.
 * Returns ['sent' => bool, 'message_id' => string|null, 'error' => string|null].
 */
function deliverEmailSMTP($toEmail, $toName, $subject, $htmlContent, $pdfContent = null, $pdfFileName = 'Offer_Letter.pdf', $type = 'primary') {
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
        return ['sent' => (bool)$sent, 'message_id' => $mail->getLastMessageID() ?: null, 'error' => null];
    } catch (Exception $e) {
        error_log("$type Email PHPMailer Error: " . $mail->ErrorInfo);
        return ['sent' => false, 'message_id' => null, 'error' => $mail->ErrorInfo];
    }
}

/**
 * The relay (MailBaby) can accept a primary send and reject it minutes later,
 * e.g. "550 classified as rSPAM". That rejection only comes back as a bounce
 * to no-reply@, so primary sends keep their content here for
 * cron_process_bounces.php to resend through Gmail. Gmail sends and failures
 * keep only the metadata.
 */
function ensureEmailOutboxTable($conn) {
    static $done = false;
    if ($done) {
        return;
    }
    $conn->query("CREATE TABLE IF NOT EXISTS `email_outbox` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `recipient_email` VARCHAR(255) NOT NULL,
        `recipient_name` VARCHAR(255) NULL,
        `subject` VARCHAR(255) NOT NULL,
        `html_content` MEDIUMTEXT NULL,
        `attachment` MEDIUMBLOB NULL,
        `attachment_name` VARCHAR(255) NULL,
        `transport` ENUM('primary','gmail') NOT NULL,
        `message_id` VARCHAR(255) NULL,
        `status` ENUM('sent','failed','bounced','resent','resend_failed') NOT NULL,
        `error` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_message_id` (`message_id`),
        KEY `idx_recipient_created` (`recipient_email`, `created_at`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

/**
 * True when the latest logged email with one of these subjects to this address
 * bounced or failed, so a resend should go through Gmail first.
 */
function lastEmailUndelivered($conn, $toEmail, array $subjects) {
    try {
        ensureEmailOutboxTable($conn);
        $placeholders = implode(',', array_fill(0, count($subjects), '?'));
        $stmt = $conn->prepare("SELECT status FROM email_outbox WHERE recipient_email = ? AND subject IN ($placeholders) ORDER BY id DESC LIMIT 1");
        $stmt->bind_param(str_repeat('s', count($subjects) + 1), $toEmail, ...$subjects);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && in_array($row['status'], ['failed', 'bounced', 'resend_failed'], true);
    } catch (Throwable $e) {
        error_log("lastEmailUndelivered: " . $e->getMessage());
        return false;
    }
}

function logEmailOutbox($toEmail, $toName, $subject, $htmlContent, $pdfContent, $pdfFileName, $type, $res) {
    global $conn;
    if (!isset($conn) || !$conn) {
        return;
    }
    try {
        ensureEmailOutboxTable($conn);
        $keepContent = $res['sent'] && $type === 'primary';
        $html = $keepContent ? $htmlContent : null;
        $pdf = $keepContent && $pdfContent ? $pdfContent : null;
        $pdfName = $pdf !== null ? $pdfFileName : null;
        $status = $res['sent'] ? 'sent' : 'failed';
        $subject = mb_substr($subject, 0, 255);
        $stmt = $conn->prepare("INSERT INTO email_outbox
            (recipient_email, recipient_name, subject, html_content, attachment, attachment_name, transport, message_id, status, error)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssssss', $toEmail, $toName, $subject, $html, $pdf, $pdfName, $type, $res['message_id'], $status, $res['error']);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log("Database Exception logging email_outbox: " . $e->getMessage());
    }
}
