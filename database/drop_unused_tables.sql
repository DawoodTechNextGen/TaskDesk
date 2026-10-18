-- Drops tables that no TaskDesk code uses any more (checked 2026-10-18):
--   chat_*              DoTalk chat, removed in 90c50d5; no new rows since 2026-07-21
--   push_subscriptions  DoTalk web push; no new rows since 2026-07-24
--   app_settings        editable registration email templates, removed in 49c6c68
--   email_queue         old email queue, removed in early 2026; empty
-- attendance_types stays: the dashboard attendance query still joins it.
--
-- Export these tables first if their data might be needed, then run on the
-- right database (phpMyAdmin SQL tab, or mysql -u ... dawoodte_task_desk < this file).

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `chat_group_members`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_groups`;
DROP TABLE IF EXISTS `chat_requests`;
DROP TABLE IF EXISTS `chat_rules`;
DROP TABLE IF EXISTS `push_subscriptions`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `email_queue`;

SET FOREIGN_KEY_CHECKS = 1;
