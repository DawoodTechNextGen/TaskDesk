<?php
/**
 * Resends bounced primary-SMTP emails through Gmail.
 *
 * The primary server hands every email to the MailBaby relay, which can accept
 * it and reject it later ("550 classified as rSPAM"), or the recipient's server
 * can refuse it after the relay. Either way PHPMailer already reported success,
 * so the only trace is a bounce in the no-reply@ mailbox. This script reads
 * those bounces, finds the original email in email_outbox and sends it again
 * through Gmail.
 *
 * Cron (every 15 minutes):
 *   /usr/local/bin/php /home2/dawoodte/public_html/taskdesk/cron_process_bounces.php >> /home2/dawoodte/cron_process_bounces.log 2>&1
 * Add --dry-run to only report what would be done (mailbox opened read-only).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/connection.php';
require_once __DIR__ . '/include/notification_helper.php';
require_once __DIR__ . '/include/imap_client.php';
require_once __DIR__ . '/include/bounce_parser.php';

date_default_timezone_set('Asia/Karachi');

// Bounces are moved from INBOX to Trash by whoever reads the mailbox, so both
// are checked. A keyword marks a bounce as handled without touching \Seen.
const BOUNCE_FOLDERS = ['INBOX', 'INBOX.Trash'];
const BOUNCE_CHECKED_KEYWORD = 'TaskdeskBounceChecked';
const BOUNCE_LOOKBACK_DAYS = 3;
const OUTBOX_CONTENT_DAYS = 14;

$dryRun = in_array('--dry-run', $argv, true);

function logLine($msg) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
}

function findOutboxRow($conn, $recipient, array $messageIds) {
    foreach ($messageIds as $id) {
        $stmt = $conn->prepare("SELECT * FROM email_outbox WHERE message_id = ? AND recipient_email = ? AND transport = 'primary' LIMIT 1");
        $stmt->bind_param('ss', $id, $recipient);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return $row;
        }
    }
    // Some relays drop References; fall back to the latest primary send to
    // that address that hasn't been bounced yet.
    $stmt = $conn->prepare("SELECT * FROM email_outbox
        WHERE recipient_email = ? AND transport = 'primary' AND status = 'sent'
          AND created_at > NOW() - INTERVAL 7 DAY
        ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $recipient);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function updateOutbox($conn, $id, $status, $error) {
    $stmt = $conn->prepare("UPDATE email_outbox SET status = ?, error = ? WHERE id = ?");
    $stmt->bind_param('ssi', $status, $error, $id);
    $stmt->execute();
    $stmt->close();
}

function handleBounce($conn, array $bounce, $dryRun) {
    foreach ($bounce['recipients'] as $recipient) {
        $row = findOutboxRow($conn, $recipient, $bounce['message_ids']);
        if (!$row) {
            logLine("Bounce for $recipient has no matching email_outbox row (sent before logging started or via Gmail). Reason: {$bounce['reason']}");
            continue;
        }
        if ($row['status'] !== 'sent') {
            logLine("Outbox #{$row['id']} to $recipient already {$row['status']}, skipping.");
            continue;
        }

        $label = "outbox #{$row['id']} '{$row['subject']}' to $recipient";

        if (isInvalidAddress($bounce['reason'])) {
            logLine("Bounced (address does not exist), not resending $label. Reason: {$bounce['reason']}");
            if (!$dryRun) {
                updateOutbox($conn, $row['id'], 'bounced', $bounce['reason']);
            }
            continue;
        }

        if ($row['html_content'] === null) {
            logLine("Bounced but content was already purged, can't resend $label. Reason: {$bounce['reason']}");
            if (!$dryRun) {
                updateOutbox($conn, $row['id'], 'bounced', $bounce['reason']);
            }
            continue;
        }

        if ($dryRun) {
            logLine("[dry-run] Would resend via Gmail: $label. Reason: {$bounce['reason']}");
            continue;
        }

        $res = deliverEmailSMTP($row['recipient_email'], $row['recipient_name'], $row['subject'], $row['html_content'], $row['attachment'], $row['attachment_name'] ?: 'document.pdf', 'gmail');
        if ($res['sent']) {
            updateOutbox($conn, $row['id'], 'resent', 'Primary bounced: ' . $bounce['reason']);
            logLine("Resent via Gmail: $label. Primary bounce: {$bounce['reason']}");
        } else {
            updateOutbox($conn, $row['id'], 'resend_failed', 'Primary bounced: ' . $bounce['reason'] . ' | Gmail: ' . $res['error']);
            logLine("Gmail resend FAILED for $label: {$res['error']}");
        }
    }
}

ensureEmailOutboxTable($conn);

try {
    $imap = new ImapClient(getenv('IMAP_HOST') ?: MAIL_HOST, (int)(getenv('IMAP_PORT') ?: 993), MAIL_USERNAME, MAIL_PASSWORD);
} catch (Throwable $e) {
    logLine('Cannot open the bounce mailbox: ' . $e->getMessage());
    exit(1);
}

$since = date('d-M-Y', strtotime('-' . BOUNCE_LOOKBACK_DAYS . ' days'));
$checked = 0;
$bounces = 0;

foreach (BOUNCE_FOLDERS as $folder) {
    try {
        $imap->select($folder, $dryRun);
        $uids = $imap->search("SINCE $since UNKEYWORD " . BOUNCE_CHECKED_KEYWORD);
    } catch (Throwable $e) {
        logLine("Skipping $folder: " . $e->getMessage());
        continue;
    }

    foreach ($uids as $uid) {
        try {
            $raw = $imap->fetchRaw($uid);
            $bounce = parseBounce($raw);
            if ($bounce) {
                $bounces++;
                handleBounce($conn, $bounce, $dryRun);
            }
            if (!$dryRun) {
                $imap->addFlag($uid, BOUNCE_CHECKED_KEYWORD);
            }
            $checked++;
        } catch (Throwable $e) {
            // Left unflagged so the next run tries again
            logLine("Error on $folder UID $uid: " . $e->getMessage());
        }
    }
}

$imap->logout();

// Bounces arrive within minutes to a few days; after that the stored body and
// PDF are only taking space.
if (!$dryRun) {
    $conn->query("UPDATE email_outbox SET html_content = NULL, attachment = NULL
        WHERE html_content IS NOT NULL AND created_at < NOW() - INTERVAL " . OUTBOX_CONTENT_DAYS . " DAY");
}

logLine("Checked $checked message(s), $bounces bounce(s)" . ($dryRun ? ' [dry-run]' : '') . '.');
