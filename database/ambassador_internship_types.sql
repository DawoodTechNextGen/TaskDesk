-- Campus Ambassadors + admin-controlled internship types.
-- TaskDesk applies all of this automatically (include/internship_type_helper.php,
-- ensureInternshipTypeSchema()); this file is for running it by hand on a server
-- where the app's DB user has no CREATE/ALTER privilege. Run each ALTER only if
-- the column does not exist yet.

-- Internship types offered on the public registration form (0 = Task-Based, 1 = Learning-Based).
-- Read by the Node.js registration backend (GET /api/internship-types).
CREATE TABLE IF NOT EXISTS `internship_types` (
  `type_value` TINYINT NOT NULL PRIMARY KEY,
  `label` VARCHAR(100) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `includes_title` VARCHAR(150) NOT NULL DEFAULT '',
  `includes_items` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `internship_types` (type_value, label, is_enabled, includes_title, includes_items, sort_order) VALUES
(0, 'Task-Based Internship', 1, 'Task-Based Internship Includes:',
 'Beginner-Friendly Learning Program\nPractice-Based Tasks & Assignments\nPlatform & Training Fee: PKR 1000\nBeginner-Level Real-World Projects\nWeekly Tasks & Progress Tracking\n6-Week Internship Completion Certificate\nCommunity Support & Mentor Guidance\nInternship Access & Onboarding Support', 1),
(1, 'Learning-Based Internship', 1, 'Learning-Based Internship Includes:',
 'Structured Learning-Based Program\nAffordable Learning & Training Fee\nReal-World Projects\nPortfolio & GitHub Setup\nMentor Support & Guidance\nJob & Freelancing Guidance\nVerified Internship Certificate\nTop Performers get Recommendation Letter', 2);

-- NULL = no internship type (every type switched off on the form).
ALTER TABLE `registrations` MODIFY `internship_type` INT(11) NULL DEFAULT NULL;

-- Referral code of the Campus Ambassador whose link the student registered through.
ALTER TABLE `registrations` ADD COLUMN `ref_code` VARCHAR(20) NULL DEFAULT NULL, ADD INDEX `idx_registrations_ref_code` (`ref_code`);

-- Campus Ambassador accounts (users.user_role = 7).
ALTER TABLE `users` ADD COLUMN `referral_code` VARCHAR(20) NULL DEFAULT NULL, ADD UNIQUE KEY `uq_users_referral_code` (`referral_code`);
ALTER TABLE `users` ADD COLUMN `university` VARCHAR(150) NULL DEFAULT NULL;

-- University dropdown on the registration form (managed from universities.php).
-- Seed it with database/universities_pakistan.json via "Import JSON" on that page.
CREATE TABLE IF NOT EXISTS `universities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_universities_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The university the student picked (saved as text).
ALTER TABLE `registrations` ADD COLUMN `university` VARCHAR(191) NULL DEFAULT NULL;
