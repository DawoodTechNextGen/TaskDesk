-- Adds `certificate.approved_at`, used by the "Clean Up Old Tasks" action on the
-- Completed Interns page to find certificates approved more than a month ago.
--
-- Safe to skip: include/certificate_helper.php adds this automatically the first
-- time a certificate action runs. Run this only if the app's DB user has no ALTER
-- privilege and needs an admin to run it once.
--
-- NOTE: running this twice will error with "Duplicate column name" - that just
-- means it already ran; nothing else to do.

ALTER TABLE `certificate`
  ADD COLUMN `approved_at` DATETIME NULL DEFAULT NULL AFTER `approve_status`;

-- Certificates approved before this column existed: use the row's created_at.
UPDATE `certificate` SET approved_at = created_at WHERE approve_status = 1 AND approved_at IS NULL;
