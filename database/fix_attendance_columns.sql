-- Pre-existing schema gap found while testing the freeze-attendance fix:
-- auto-attendance.php, controller/attendance_action.php and controller/timeLog.php
-- already reference `attendance`.`status`, `check_in_time` and `check_out_time`,
-- but these columns do not exist on this database (local dump was never updated
-- after they were added elsewhere).
--
-- Uses a stored procedure instead of "ADD COLUMN IF NOT EXISTS" because that
-- syntax needs MySQL 8.0.29+ / MariaDB 10.0.2+, which some shared-hosting
-- MySQL versions don't support. This version works on any MySQL/MariaDB and
-- is safe to run multiple times or on a database that already has some/all
-- of these columns.
--
-- Run via phpMyAdmin: open the SQL tab for the database and paste this whole
-- file, then Go. Or via CLI: mysql -u root -p task_management < database/fix_attendance_columns.sql

DELIMITER $$

DROP PROCEDURE IF EXISTS `add_attendance_columns`$$

CREATE PROCEDURE `add_attendance_columns`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'status'
    ) THEN
        ALTER TABLE `attendance` ADD COLUMN `status` VARCHAR(20) DEFAULT NULL AFTER `date`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'check_in_time'
    ) THEN
        ALTER TABLE `attendance` ADD COLUMN `check_in_time` DATETIME DEFAULT NULL AFTER `status`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'check_out_time'
    ) THEN
        ALTER TABLE `attendance` ADD COLUMN `check_out_time` DATETIME DEFAULT NULL AFTER `check_in_time`;
    END IF;
END$$

DELIMITER ;

CALL `add_attendance_columns`();

DROP PROCEDURE IF EXISTS `add_attendance_columns`;
