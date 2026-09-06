-- Bootcamp enrollments: submissions from the public Bootcamp sign-up form.
--
-- Completely separate from the internship `registrations` table - the bootcamp
-- listing page (bootcamp_registrations.php) and its controller only ever read
-- this table.
--
-- This table also self-creates via CREATE TABLE IF NOT EXISTS in
-- include/bootcamp_helper.php, so running this file manually is optional
-- (useful for pre-provisioning on a live server, or for reference).
--
--   mysql -u root -p dawoodte_task_desk < database/bootcamp_registrations.sql
--
-- NOTE: this table lives in the Bootcamp sign-up form's own database, not the
-- TaskDesk one. That database name is BOOTCAMP_DB in include/config.php.

CREATE TABLE IF NOT EXISTS `bootcamp_registrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `mbl_number` VARCHAR(20) NOT NULL COMMENT 'WhatsApp, format +923001234567',
  `province` VARCHAR(50) NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `cnic` VARCHAR(15) NOT NULL COMMENT 'format 35201-1234567-1',
  `status` ENUM('new','contact','enrolled','rejected') NOT NULL DEFAULT 'new',
  `email_status` TINYINT NOT NULL DEFAULT 0 COMMENT '0=none, 1=email sent, 2=email failed, 3=contacted on WhatsApp',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_bootcamp_email` (`email`),
  UNIQUE KEY `uq_bootcamp_mbl_number` (`mbl_number`),
  UNIQUE KEY `uq_bootcamp_cnic` (`cnic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
