-- Multiple bootcamps: the `bootcamps` table plus a bootcamp_id on every sign-up.
--
-- Run once on the LIVE bootcamp database (BOOTCAMP_DB in include/config.php,
-- `dawoodte_task_desk` in production) ONLY if the app's DB user has no
-- CREATE/ALTER privilege. Otherwise include/bootcamp_helper.php (TaskDesk) and
-- backend/config/ensureBootcampColumns.js (public form API) do all of this
-- automatically.
--
--   mysql -u <db_user> -p dawoodte_task_desk < database/bootcamps.sql
--
-- Sign-ups that existed before this keep a NULL bootcamp_id and show as
-- "Unassigned" on bootcamp_registrations.php.

CREATE TABLE IF NOT EXISTS `bootcamps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(120) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `highlights` TEXT NULL COMMENT 'one point per line',
  `mode` ENUM('online','onsite','hybrid') NOT NULL DEFAULT 'online',
  `status` ENUM('draft','upcoming','open','closed','completed') NOT NULL DEFAULT 'draft',
  `total_seats` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
  `fee` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '0 = free',
  `duration` VARCHAR(60) NULL,
  `schedule` VARCHAR(150) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `registration_start` DATETIME NULL,
  `registration_end` DATETIME NULL,
  `venue` VARCHAR(150) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_bootcamps_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `bootcamp_registrations`
  ADD COLUMN `bootcamp_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_bootcamp_reg_bootcamp` (`bootcamp_id`);

-- Email / WhatsApp / CNIC become unique per bootcamp instead of across the whole
-- table, so one student can join more than one bootcamp. The old single-column
-- keys are named uq_* (created by TaskDesk) or uniq_* (created by the Node.js
-- API) - drop whichever pair exists; an "Unknown key" error just means that name
-- isn't the one on this server.
ALTER TABLE `bootcamp_registrations` DROP INDEX `uniq_bootcamp_email`;
ALTER TABLE `bootcamp_registrations` DROP INDEX `uniq_bootcamp_mbl_number`;
ALTER TABLE `bootcamp_registrations` DROP INDEX `uniq_bootcamp_cnic`;
-- ...or, on a TaskDesk-created table:
-- ALTER TABLE `bootcamp_registrations` DROP INDEX `uq_bootcamp_email`;
-- ALTER TABLE `bootcamp_registrations` DROP INDEX `uq_bootcamp_mbl_number`;
-- ALTER TABLE `bootcamp_registrations` DROP INDEX `uq_bootcamp_cnic`;

ALTER TABLE `bootcamp_registrations`
  ADD UNIQUE KEY `uniq_bootcamp_reg_email` (`bootcamp_id`, `email`),
  ADD UNIQUE KEY `uniq_bootcamp_reg_mbl_number` (`bootcamp_id`, `mbl_number`),
  ADD UNIQUE KEY `uniq_bootcamp_reg_cnic` (`bootcamp_id`, `cnic`);

-- Completion + certificates: `completed` status (after enrolled) and the columns
-- for the emailed bootcamp certificate (certificate_status: 0 = not sent,
-- 1 = sent, 2 = failed). Also added automatically by include/bootcamp_helper.php.
ALTER TABLE `bootcamp_registrations`
  MODIFY `status` ENUM('new','contact','enrolled','completed','rejected') NOT NULL DEFAULT 'new',
  ADD COLUMN `completed_at` DATETIME NULL DEFAULT NULL AFTER `email_status`,
  ADD COLUMN `certificate_code` VARCHAR(20) NULL DEFAULT NULL AFTER `completed_at`,
  ADD COLUMN `certificate_status` TINYINT NOT NULL DEFAULT 0 AFTER `certificate_code`,
  ADD COLUMN `certificate_sent_at` DATETIME NULL DEFAULT NULL AFTER `certificate_status`,
  ADD UNIQUE KEY `uniq_bootcamp_reg_certificate_code` (`certificate_code`);
