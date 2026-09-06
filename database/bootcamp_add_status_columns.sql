-- Run this once on the LIVE bootcamp database (BOOTCAMP_DB in include/config.php,
-- e.g. `task_desk`) to add the two columns the reworked Bootcamp Enrollments
-- module needs. The table and its existing rows are untouched - this only adds
-- columns with safe defaults.
--
--   mysql -u <db_user> -p <bootcamp_db_name> < database/bootcamp_add_status_columns.sql
--
-- Safe to skip: include/bootcamp_helper.php adds these automatically the first
-- time any Bootcamp page or controller action runs. Run this only if you'd
-- rather do it yourself ahead of time, or if the app's DB user has no ALTER
-- privilege and needs an admin to run it once.
--
-- NOTE: running this twice will error with "Duplicate column name" - that just
-- means it already ran; nothing else to do.

ALTER TABLE `bootcamp_registrations`
  ADD COLUMN `status` ENUM('new','contact','enrolled','rejected') NOT NULL DEFAULT 'new' AFTER `cnic`;

ALTER TABLE `bootcamp_registrations`
  ADD COLUMN `email_status` TINYINT NOT NULL DEFAULT 0 AFTER `status`;
