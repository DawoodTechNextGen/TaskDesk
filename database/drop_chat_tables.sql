-- Removes all leftover tables from the discontinued DoTalk chat integration.
-- Safe to run on both local (task_management) and live databases.
-- Run this against the correct database, e.g.:
--   mysql -u root -p task_management < database/drop_chat_tables.sql

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `chat_group_members`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_groups`;
DROP TABLE IF EXISTS `chat_requests`;
DROP TABLE IF EXISTS `chat_rules`;

SET FOREIGN_KEY_CHECKS = 1;
