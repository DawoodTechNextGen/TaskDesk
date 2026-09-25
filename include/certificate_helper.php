<?php
// How long after a certificate is approved an intern's tasks are kept before the
// "Clean Up Old Tasks" action on the Completed Interns page may remove them.
if (!defined('CERT_TASK_RETENTION_DAYS')) {
    define('CERT_TASK_RETENTION_DAYS', 30);
}

/**
 * `certificate.approved_at` records when the certificate was actually approved
 * (`created_at` is when the row was first made, which can be long before approval).
 * It is added here on environments that don't have it yet. Rows approved before the
 * column existed are backfilled from `created_at`, the closest date we have for them.
 */
function ensureCertificateApprovedAtColumn($conn)
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $has = $conn->query("SHOW COLUMNS FROM `certificate` LIKE 'approved_at'");
        if ($has && $has->num_rows === 0) {
            $conn->query("ALTER TABLE `certificate` ADD COLUMN `approved_at` DATETIME NULL DEFAULT NULL AFTER `approve_status`");
            $conn->query("UPDATE `certificate` SET approved_at = created_at WHERE approve_status = 1 AND approved_at IS NULL");
        }
    } catch (\Throwable $e) {
        error_log('Certificate approved_at self-migration skipped (likely missing ALTER privilege): ' . $e->getMessage());
    }
}
