-- Pre-existing schema gap found while testing the freeze-attendance fix:
-- auto-attendance.php, controller/attendance_action.php and controller/timeLog.php
-- already reference `attendance`.`status`, `check_in_time` and `check_out_time`,
-- but these columns do not exist on this database (local dump was never updated
-- after they were added elsewhere). Safe/idempotent to run on both local and live.
--
--   mysql -u root -p task_management < database/fix_attendance_columns.sql

ALTER TABLE `attendance`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT NULL AFTER `date`,
  ADD COLUMN IF NOT EXISTS `check_in_time` DATETIME DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `check_out_time` DATETIME DEFAULT NULL AFTER `check_in_time`;
