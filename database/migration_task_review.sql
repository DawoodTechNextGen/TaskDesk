-- Migration: Add Task Review Workflow Columns
-- Run this SQL in your phpMyAdmin or MySQL client
-- Date: 2026-01-26

-- Add review-related columns to tasks table
ALTER TABLE `tasks` 
ADD COLUMN `review_notes` TEXT NULL AFTER `additional_notes`,
ADD COLUMN `reviewed_at` DATETIME NULL AFTER `review_notes`,
ADD COLUMN `reviewed_by` INT NULL AFTER `reviewed_at`;

-- Update existing 'complete' tasks to 'pending_review' status
-- (Optional: only if you want existing completed tasks to require review)
-- UPDATE `tasks` SET `status` = 'pending_review' WHERE `status` = 'complete';

-- Note: The following statuses will now be used:
-- 'pending' - Task assigned but not started
-- 'working' - Task in progress
-- 'pending_review' - Task completed by intern, awaiting supervisor review
-- 'approved' - Task approved by supervisor
-- 'rejected' - Task rejected by supervisor
-- 'needs_improvement' - Supervisor requested improvements
-- 'expired' - Task past due date
