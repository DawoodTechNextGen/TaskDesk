-- CRITICAL FIX: Update Task Status Column
-- Your current ENUM definition is restricting the new values, causing blank statuses.
-- Run this IMMEDIATELY to fix the issue.

ALTER TABLE `tasks` 
MODIFY COLUMN `status` ENUM(
    'pending', 
    'working', 
    'complete', 
    'pending_review', 
    'approved', 
    'rejected', 
    'needs_improvement', 
    'expired'
) NOT NULL DEFAULT 'pending';

-- Optional: Fix any blank statuses that might have been created
-- UPDATE `tasks` SET `status` = 'pending_review' WHERE `status` = '';
