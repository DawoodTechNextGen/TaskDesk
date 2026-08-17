-- Adds support for the "Yes, I am Interested" / "Not Interested" buttons in
-- registration emails, and an app_settings table so the email text and the
-- payment/registration link can be edited from the admin UI (email_settings.php)
-- instead of being hardcoded in cron_send_emails.php / controller/registrations.php.
--
-- OPTIONAL: include/registration_helper.php now self-installs this exact schema
-- at runtime (ensureRegistrationResponseSchema(), called from getAppSetting/
-- saveAppSetting/ensureResponseToken) the first time it's needed, so deploying
-- to a new environment (e.g. production) requires no manual DB step. Run this
-- file by hand only if you want the schema in place ahead of time.
--
-- Uses a stored procedure instead of "ADD COLUMN IF NOT EXISTS" for MariaDB/MySQL
-- version compatibility (same pattern as fix_attendance_columns.sql). Safe to run
-- multiple times or on a database that already has the column.
--
--   mysql -u root -p task_management < database/registration_response_schema.sql

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS `add_registration_response_token`$$

CREATE PROCEDURE `add_registration_response_token`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'response_token'
    ) THEN
        ALTER TABLE `registrations` ADD COLUMN `response_token` VARCHAR(64) DEFAULT NULL AFTER `email_status`;
        ALTER TABLE `registrations` ADD UNIQUE KEY `uk_registrations_response_token` (`response_token`);
    END IF;
END$$

DELIMITER ;

CALL `add_registration_response_token`();

DROP PROCEDURE IF EXISTS `add_registration_response_token`;
