<?php
/**
 * Daily Database Backup Script
 * Runs via Windows Task Scheduler at Midnight (12:00 AM)
 * - Creates a compressed MySQL dump
 * - Emails it as attachment to admin
 * 
 * Run manually: php C:\xampp\htdocs\TaskDesk\backup_db.php
 */

// Load config (works from CLI too)
define('CLI_MODE', php_sapi_name() === 'cli');

// Load .env manually for CLI
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Database config
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'task_management';

// Email config — cPanel SMTP (primary)
$mail_host       = getenv('MAIL_HOST');
$mail_port       = getenv('MAIL_PORT') ?: 465;
$mail_username   = getenv('MAIL_USERNAME');
$mail_password   = getenv('MAIL_PASSWORD');
$mail_encryption = getenv('MAIL_ENCRYPTION') ?: 'ssl';
$mail_from_email = getenv('MAIL_FROM_EMAIL');
$mail_from_name  = getenv('MAIL_FROM_NAME') ?: 'DawoodTech NextGen';

// Backup destination email (admin)
define('BACKUP_RECIPIENT_EMAIL', 'code.learners.edu.pk@gmail.com'); // Admin backup email
define('BACKUP_RECIPIENT_NAME',  'DawoodTech Admin');

// -------------------------
// Step 1: Generate SQL dump
// -------------------------
$date       = date('Y-m-d');
$time       = date('H-i-s');
$backupDir  = __DIR__ . '/backups';
$backupFile = $backupDir . "/db_backup_{$db_name}_{$date}_{$time}.sql";
$gzipFile   = $backupFile . '.gz';

// Create backups directory if not exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Auto-detect OS and set mysqldump path
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows (XAMPP local)
    $mysqlDumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (!file_exists($mysqlDumpPath)) {
        $mysqlDumpPath = 'mysqldump'; // fallback to PATH
    }
} else {
    // Linux (cPanel server)
    $mysqlDumpPath = 'mysqldump'; // available in PATH on cPanel
    // Common cPanel paths as fallback
    foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/usr/local/mysql/bin/mysqldump'] as $path) {
        if (file_exists($path)) {
            $mysqlDumpPath = $path;
            break;
        }
    }
}

$passOption = !empty($db_pass) ? "-p" . escapeshellarg($db_pass) : '';
$command = "\"{$mysqlDumpPath}\" -h {$db_host} -u {$db_user} {$passOption} {$db_name} > " . escapeshellarg($backupFile) . " 2>&1";

logMessage("Starting daily backup for database: {$db_name}");
logMessage("Running mysqldump...");

exec($command, $output, $returnCode);

if ($returnCode !== 0 || !file_exists($backupFile) || filesize($backupFile) === 0) {
    logMessage("ERROR: mysqldump failed! Code: {$returnCode}");
    logMessage("Output: " . implode("\n", $output));
    sendBackupFailureEmail($mail_host, $mail_port, $mail_username, $mail_password, $mail_encryption, $mail_from_email, $mail_from_name, $db_name, $date, implode("\n", $output));
    exit(1);
}

$sqlFileSize = filesize($backupFile);
logMessage("SQL dump created: " . basename($backupFile) . " (" . formatBytes($sqlFileSize) . ")");

// -------------------------
// Step 2: Compress with gzip
// -------------------------
$compressed = false;
if (function_exists('gzopen')) {
    $gzHandle = gzopen($gzipFile, 'wb9'); // Max compression
    $sqlHandle = fopen($backupFile, 'rb');
    if ($gzHandle && $sqlHandle) {
        while (!feof($sqlHandle)) {
            gzwrite($gzHandle, fread($sqlHandle, 65536));
        }
        fclose($sqlHandle);
        gzclose($gzHandle);
        unlink($backupFile); // Remove uncompressed file
        $compressed = true;
        $finalFile = $gzipFile;
        logMessage("Compressed to: " . basename($gzipFile) . " (" . formatBytes(filesize($gzipFile)) . ")");
    }
}

if (!$compressed) {
    $finalFile = $backupFile;
    logMessage("Compression skipped (gzip not available), using raw SQL.");
}

// -------------------------
// Step 3: Send via Email
// -------------------------
logMessage("Sending backup email to: " . BACKUP_RECIPIENT_EMAIL);

$emailSent = sendBackupEmail(
    $mail_host, $mail_port, $mail_username, $mail_password,
    $mail_encryption, $mail_from_email, $mail_from_name,
    $finalFile, $db_name, $date, $sqlFileSize
);

if ($emailSent) {
    logMessage("✅ Backup email sent successfully!");
} else {
    logMessage("❌ Failed to send backup email. File saved locally: " . $finalFile);
}

// -------------------------
// Step 4: Cleanup old backups (keep last 7 days)
// -------------------------
$files = glob($backupDir . '/db_backup_*');
if ($files) {
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    $keepCount = 7;
    foreach (array_slice($files, $keepCount) as $oldFile) {
        unlink($oldFile);
        logMessage("Deleted old backup: " . basename($oldFile));
    }
}

logMessage("Backup process completed.");

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function sendBackupEmail($host, $port, $username, $password, $encryption, $fromEmail, $fromName, $filePath, $dbName, $date, $originalSize) {
    require_once __DIR__ . '/vendor/autoload.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = ($encryption === 'tls') ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                                                     : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)$port;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress(BACKUP_RECIPIENT_EMAIL, BACKUP_RECIPIENT_NAME);

        $mail->isHTML(true);
        $mail->Subject = "🗄️ Daily DB Backup — {$dbName} — {$date}";

        $fileSize    = formatBytes(filesize($filePath));
        $originalSz  = formatBytes($originalSize);
        $fileName    = basename($filePath);
        $backupTime  = date('d M Y, h:i A');

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
            <div style='background: #1e293b; padding: 20px 30px; text-align: center;'>
                <h2 style='color: #fff; margin: 0;'>🗄️ Daily Database Backup</h2>
                <p style='color: #94a3b8; margin: 5px 0 0 0;'>DawoodTech NextGen — Automated Backup</p>
            </div>
            <div style='padding: 30px;'>
                <p style='color: #1e293b;'>Your scheduled daily database backup has been completed successfully.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='background: #f1f5f9;'>
                        <td style='padding: 10px 15px; font-weight: bold; color: #475569; width: 140px;'>Database</td>
                        <td style='padding: 10px 15px; color: #1e293b;'>{$dbName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 15px; font-weight: bold; color: #475569;'>Backup Date</td>
                        <td style='padding: 10px 15px; color: #1e293b;'>{$backupTime}</td>
                    </tr>
                    <tr style='background: #f1f5f9;'>
                        <td style='padding: 10px 15px; font-weight: bold; color: #475569;'>File Name</td>
                        <td style='padding: 10px 15px; color: #1e293b; font-family: monospace; font-size: 13px;'>{$fileName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 15px; font-weight: bold; color: #475569;'>SQL Size</td>
                        <td style='padding: 10px 15px; color: #1e293b;'>{$originalSz}</td>
                    </tr>
                    <tr style='background: #f1f5f9;'>
                        <td style='padding: 10px 15px; font-weight: bold; color: #475569;'>Compressed</td>
                        <td style='padding: 10px 15px; color: #1e293b;'>{$fileSize}</td>
                    </tr>
                </table>
                <div style='background: #dcfce7; border-left: 4px solid #16a34a; padding: 12px 16px; border-radius: 4px; margin: 20px 0;'>
                    <p style='margin: 0; color: #15803d; font-weight: bold;'>✅ Backup file is attached to this email.</p>
                </div>
                <p style='color: #64748b; font-size: 13px; margin-top: 20px;'>This is an automated message. Please do not reply to this email.</p>
            </div>
            <div style='background: #f8fafc; padding: 15px 30px; text-align: center; border-top: 1px solid #e2e8f0;'>
                <p style='margin: 0; color: #94a3b8; font-size: 12px;'>© " . date('Y') . " DawoodTech NextGen. All rights reserved.</p>
            </div>
        </div>";

        // Attach backup file
        $mail->addAttachment($filePath, $fileName);

        $mail->send();
        return true;
    } catch (Exception $e) {
        logMessage("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

function sendBackupFailureEmail($host, $port, $username, $password, $encryption, $fromEmail, $fromName, $dbName, $date, $errorMsg) {
    require_once __DIR__ . '/vendor/autoload.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = ($encryption === 'tls') ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                                                     : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)$port;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress(BACKUP_RECIPIENT_EMAIL, BACKUP_RECIPIENT_NAME);
        $mail->isHTML(true);
        $mail->Subject = "❌ Backup FAILED — {$dbName} — {$date}";
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #fca5a5; border-radius: 10px; overflow: hidden;'>
            <div style='background: #dc2626; padding: 20px 30px; text-align: center;'>
                <h2 style='color: #fff; margin: 0;'>❌ Backup Failed!</h2>
            </div>
            <div style='padding: 30px;'>
                <p>The daily backup for <strong>{$dbName}</strong> on <strong>{$date}</strong> has <strong>FAILED</strong>.</p>
                <div style='background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px 16px; border-radius: 4px;'>
                    <p style='margin: 0; font-family: monospace; font-size: 13px; color: #991b1b;'>{$errorMsg}</p>
                </div>
                <p style='margin-top: 20px; color: #64748b;'>Please check the server immediately.</p>
            </div>
        </div>";
        $mail->send();
    } catch (Exception $e) {
        logMessage("Failed to send failure notification: " . $e->getMessage());
    }
}

function logMessage($msg) {
    $timestamp = date('Y-m-d H:i:s');
    $logLine   = "[{$timestamp}] {$msg}\n";
    echo $logLine;
    // Also write to log file
    $logFile = __DIR__ . '/backups/backup.log';
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
