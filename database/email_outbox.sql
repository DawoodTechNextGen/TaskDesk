-- Every email sent through sendEmailPHPMailer() (include/notification_helper.php).
-- Primary sends keep their body and PDF so cron_process_bounces.php can resend
-- them through Gmail when the relay or the recipient's server bounces them.
-- The content is cleared after 14 days. The table is also created
-- automatically on first send, so running this is optional.
CREATE TABLE IF NOT EXISTS `email_outbox` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
