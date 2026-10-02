-- =====================================================================
-- LIVE DATABASE MIGRATION: Campus Ambassadors, Internship Types, Universities
-- Run once on the live task_management database (phpMyAdmin > SQL tab).
-- Safe to run more than once: columns/tables are only added if missing and
-- every seed row uses INSERT IGNORE, so nothing is duplicated or overwritten.
-- (TaskDesk and the Node backend also apply this automatically when their DB
-- user has CREATE/ALTER rights; this file is for doing it by hand.)
-- =====================================================================

-- 1. Internship types shown on the registration form (0 = Task-Based, 1 = Learning-Based).
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

-- 2. registrations: allow "no internship type" (every type switched off).
ALTER TABLE `registrations` MODIFY `internship_type` INT(11) NULL DEFAULT NULL;

-- 3. registrations: ambassador referral code + university.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'ref_code') = 0,
  'ALTER TABLE `registrations` ADD COLUMN `ref_code` VARCHAR(20) NULL DEFAULT NULL, ADD INDEX `idx_registrations_ref_code` (`ref_code`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'university') = 0,
  'ALTER TABLE `registrations` ADD COLUMN `university` VARCHAR(191) NULL DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. users: Campus Ambassador fields (ambassadors are users.user_role = 7).
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referral_code') = 0,
  'ALTER TABLE `users` ADD COLUMN `referral_code` VARCHAR(20) NULL DEFAULT NULL, ADD UNIQUE KEY `uq_users_referral_code` (`referral_code`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'university') = 0,
  'ALTER TABLE `users` ADD COLUMN `university` VARCHAR(150) NULL DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Admin switches: may this ambassador see their students' full email / phone?
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'amb_show_email') = 0,
  'ALTER TABLE `users` ADD COLUMN `amb_show_email` TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN `amb_show_phone` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- An Intern can also be a Campus Ambassador on the same account. amb_active
-- switches only the ambassador side on/off; existing ambassadors copy their status.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'amb_active') = 0,
  'ALTER TABLE `users` ADD COLUMN `amb_active` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE `users` SET `amb_active` = (`status` = 1) WHERE `user_role` = 7 AND `referral_code` IS NOT NULL AND `amb_active` = 0 AND `status` = 1;

-- 5. Universities for the registration form dropdown (managed from universities.php).
CREATE TABLE IF NOT EXISTS `universities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_universities_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 104 major universities of Pakistan (same list as database/universities_pakistan.json).
INSERT IGNORE INTO `universities` (name, city, is_active) VALUES
('Quaid-i-Azam University', 'Islamabad', 1),
('National University of Sciences and Technology (NUST)', 'Islamabad', 1),
('COMSATS University Islamabad', 'Islamabad', 1),
('International Islamic University Islamabad', 'Islamabad', 1),
('Air University', 'Islamabad', 1),
('Bahria University', 'Islamabad', 1),
('National University of Modern Languages (NUML)', 'Islamabad', 1),
('Allama Iqbal Open University', 'Islamabad', 1),
('Capital University of Science and Technology (CUST)', 'Islamabad', 1),
('Riphah International University', 'Islamabad', 1),
('Pakistan Institute of Engineering and Applied Sciences (PIEAS)', 'Islamabad', 1),
('National University of Computer and Emerging Sciences (FAST-NUCES)', 'Islamabad', 1),
('Institute of Space Technology', 'Islamabad', 1),
('Shifa Tameer-e-Millat University', 'Islamabad', 1),
('PMAS Arid Agriculture University', 'Rawalpindi', 1),
('Fatima Jinnah Women University', 'Rawalpindi', 1),
('Rawalpindi Medical University', 'Rawalpindi', 1),
('University of the Punjab', 'Lahore', 1),
('University of Engineering and Technology (UET) Lahore', 'Lahore', 1),
('Lahore University of Management Sciences (LUMS)', 'Lahore', 1),
('Government College University Lahore', 'Lahore', 1),
('Information Technology University (ITU)', 'Lahore', 1),
('University of Management and Technology (UMT)', 'Lahore', 1),
('University of Central Punjab (UCP)', 'Lahore', 1),
('The University of Lahore', 'Lahore', 1),
('Lahore College for Women University', 'Lahore', 1),
('Kinnaird College for Women University', 'Lahore', 1),
('Forman Christian College (A Chartered University)', 'Lahore', 1),
('University of Education', 'Lahore', 1),
('King Edward Medical University', 'Lahore', 1),
('University of Health Sciences', 'Lahore', 1),
('University of Veterinary and Animal Sciences', 'Lahore', 1),
('Superior University', 'Lahore', 1),
('Lahore School of Economics', 'Lahore', 1),
('Beaconhouse National University', 'Lahore', 1),
('National College of Arts', 'Lahore', 1),
('Virtual University of Pakistan', 'Lahore', 1),
('Minhaj University', 'Lahore', 1),
('University of South Asia', 'Lahore', 1),
('University of Agriculture Faisalabad', 'Faisalabad', 1),
('Government College University Faisalabad', 'Faisalabad', 1),
('National Textile University', 'Faisalabad', 1),
('The University of Faisalabad', 'Faisalabad', 1),
('Bahauddin Zakariya University', 'Multan', 1),
('MNS University of Agriculture', 'Multan', 1),
('Muhammad Nawaz Sharif University of Engineering and Technology', 'Multan', 1),
('The Women University Multan', 'Multan', 1),
('The Islamia University of Bahawalpur', 'Bahawalpur', 1),
('University of Sargodha', 'Sargodha', 1),
('University of Gujrat', 'Gujrat', 1),
('University of Sialkot', 'Sialkot', 1),
('Government College Women University Sialkot', 'Sialkot', 1),
('University of Engineering and Technology (UET) Taxila', 'Taxila', 1),
('HITEC University', 'Taxila', 1),
('Ghazi University', 'Dera Ghazi Khan', 1),
('Khwaja Fareed University of Engineering and Information Technology', 'Rahim Yar Khan', 1),
('University of Sahiwal', 'Sahiwal', 1),
('University of Okara', 'Okara', 1),
('University of Jhang', 'Jhang', 1),
('University of Karachi', 'Karachi', 1),
('NED University of Engineering and Technology', 'Karachi', 1),
('Institute of Business Administration (IBA) Karachi', 'Karachi', 1),
('Aga Khan University', 'Karachi', 1),
('Dow University of Health Sciences', 'Karachi', 1),
('Sir Syed University of Engineering and Technology', 'Karachi', 1),
('Habib University', 'Karachi', 1),
('Shaheed Zulfikar Ali Bhutto Institute of Science and Technology (SZABIST)', 'Karachi', 1),
('Karachi Institute of Economics and Technology (KIET)', 'Karachi', 1),
('Hamdard University', 'Karachi', 1),
('Iqra University', 'Karachi', 1),
('DHA Suffa University', 'Karachi', 1),
('Jinnah Sindh Medical University', 'Karachi', 1),
('Mohammad Ali Jinnah University', 'Karachi', 1),
('Federal Urdu University of Arts, Science and Technology', 'Karachi', 1),
('University of Sindh', 'Jamshoro', 1),
('Mehran University of Engineering and Technology', 'Jamshoro', 1),
('Liaquat University of Medical and Health Sciences', 'Jamshoro', 1),
('Sindh Agriculture University', 'Tando Jam', 1),
('Quaid-e-Awam University of Engineering, Science and Technology', 'Nawabshah', 1),
('Shah Abdul Latif University', 'Khairpur', 1),
('Sukkur IBA University', 'Sukkur', 1),
('University of Peshawar', 'Peshawar', 1),
('University of Engineering and Technology (UET) Peshawar', 'Peshawar', 1),
('Institute of Management Sciences (IMSciences)', 'Peshawar', 1),
('Islamia College University', 'Peshawar', 1),
('Khyber Medical University', 'Peshawar', 1),
('The University of Agriculture Peshawar', 'Peshawar', 1),
('Shaheed Benazir Bhutto Women University', 'Peshawar', 1),
('City University of Science and Information Technology', 'Peshawar', 1),
('Abdul Wali Khan University', 'Mardan', 1),
('Hazara University', 'Mansehra', 1),
('Gomal University', 'Dera Ismail Khan', 1),
('Kohat University of Science and Technology', 'Kohat', 1),
('University of Malakand', 'Chakdara', 1),
('University of Swat', 'Swat', 1),
('Ghulam Ishaq Khan Institute of Engineering Sciences and Technology (GIKI)', 'Topi', 1),
('University of Science and Technology Bannu', 'Bannu', 1),
('University of Balochistan', 'Quetta', 1),
('Balochistan University of Information Technology, Engineering and Management Sciences (BUITEMS)', 'Quetta', 1),
('Sardar Bahadur Khan Women''s University', 'Quetta', 1),
('Lasbela University of Agriculture, Water and Marine Sciences', 'Uthal', 1),
('University of Azad Jammu and Kashmir', 'Muzaffarabad', 1),
('Mirpur University of Science and Technology (MUST)', 'Mirpur', 1),
('Karakoram International University', 'Gilgit', 1);
