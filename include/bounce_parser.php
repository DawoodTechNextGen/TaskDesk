<?php
/**
 * Parsing of delivery-failure bounces (Exim and MailBaby DSNs) for
 * cron_process_bounces.php.
 */

/** Header block of a raw message (or MIME part), with folded lines unfolded. */
function headerBlock($raw) {
    $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
    return preg_replace("/\r?\n[ \t]+/", ' ', $parts[0]);
}

function headerValue($headers, $name) {
    return preg_match('/^' . preg_quote($name, '/') . ':[ \t]*(.*)$/mi', $headers, $m) ? trim($m[1]) : '';
}

/**
 * Returns null when the message is not a permanent-failure bounce, otherwise
 * ['recipients' => [...], 'message_ids' => [...], 'reason' => string].
 */
function parseBounce($raw) {
    $headers = headerBlock($raw);
    $from = headerValue($headers, 'From');
    $contentType = headerValue($headers, 'Content-Type');

    $isDaemon = preg_match('/mailer-daemon|postmaster|mail delivery/i', $from);
    $isReport = stripos($contentType, 'report-type=delivery-status') !== false;
    if (!$isDaemon && !$isReport) {
        return null;
    }

    // Both Exim and MailBaby set X-Failed-Recipients on permanent failures only;
    // "delayed" warnings don't carry it. DSN parts are the fallback.
    $recipients = [];
    foreach (preg_split('/\s*,\s*/', headerValue($headers, 'X-Failed-Recipients')) as $r) {
        if ($r !== '') {
            $recipients[] = strtolower($r);
        }
    }
    if (!$recipients && preg_match_all('/^Final-Recipient:\s*rfc822;\s*(\S+)\s*\r?\n(?:[^\r\n]+\r?\n)*?Action:\s*failed/mi', $raw, $m)) {
        $recipients = array_map('strtolower', $m[1]);
    }
    if (!$recipients) {
        return null;
    }

    // The original Message-ID: References/In-Reply-To of the bounce, then any
    // Message-ID inside the returned headers part.
    $ids = [];
    foreach (['References', 'In-Reply-To'] as $h) {
        if (preg_match_all('/<[^>\s]+>/', headerValue($headers, $h), $m)) {
            $ids = array_merge($ids, $m[0]);
        }
    }
    $body = preg_split("/\r?\n\r?\n/", $raw, 2)[1] ?? '';
    if (preg_match_all('/^Message-ID:\s*(<[^>\s]+>)/mi', $body, $m)) {
        $ids = array_merge($ids, $m[1]);
    }

    $reason = '';
    if (preg_match('/^Diagnostic-Code:\s*(?:smtp;\s*)?(.+)$/mi', $raw, $m)) {
        $reason = trim($m[1]);
    } elseif (preg_match('/^\s*(\d{3}[ -].+)$/m', $body, $m)) {
        $reason = trim($m[1]);
    }

    return [
        'recipients' => array_values(array_unique($recipients)),
        'message_ids' => array_values(array_unique($ids)),
        'reason' => mb_substr($reason, 0, 500),
    ];
}

/** A Gmail resend can't help when the mailbox itself doesn't exist. */
function isInvalidAddress($reason) {
    return (bool)preg_match('/5\.1\.1|does not exist|no such user|user unknown|unknown user|mailbox unavailable|address rejected|recipient not found|NoSuchUser/i', $reason);
}
