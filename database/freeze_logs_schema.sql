-- Permanent history of every approved internship freeze period.
-- Used to: exclude frozen days from attendance % calculations,
-- extend internship completion dates, and keep a record after auto-resume clears
-- the live freeze_* columns on `users`.
--
-- This table also self-creates via CREATE TABLE IF NOT EXISTS the first time
-- controller/freeze.php runs, so running this file manually is optional
-- (useful for pre-provisioning on a live server, or for reference).
--
--   mysql -u root -p task_management < database/freeze_logs_schema.sql

CREATE TABLE IF NOT EXISTS `freeze_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days` INT NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_freeze_logs_user` (`user_id`),
  CONSTRAINT `fk_freeze_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_freeze_logs_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
