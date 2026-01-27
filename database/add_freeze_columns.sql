-- Add freeze-related columns to users table
ALTER TABLE users 
ADD COLUMN freeze_status ENUM('active', 'freeze_requested', 'frozen') DEFAULT 'active' AFTER internship_type,
ADD COLUMN freeze_start_date DATE NULL AFTER freeze_status,
ADD COLUMN freeze_end_date DATE NULL AFTER freeze_start_date,
ADD COLUMN freeze_reason TEXT NULL AFTER freeze_end_date,
ADD COLUMN freeze_requested_at DATETIME NULL AFTER freeze_reason,
ADD COLUMN freeze_approved_by INT NULL AFTER freeze_requested_at,
ADD COLUMN freeze_approved_at DATETIME NULL AFTER freeze_approved_by;
