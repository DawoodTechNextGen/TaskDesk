-- Rejecting a candidate now deletes the users row that was created for their
-- assessment login. Keep the assessment attempt itself (score, answers,
-- report) by letting candidate_assessments.user_id go NULL instead of
-- blocking the delete.
-- Run once against the live `task_management` database (phpMyAdmin or `mysql` CLI).

ALTER TABLE candidate_assessments DROP FOREIGN KEY fk_ca_user;

ALTER TABLE candidate_assessments
  MODIFY user_id INT NULL,
  ADD CONSTRAINT fk_ca_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
