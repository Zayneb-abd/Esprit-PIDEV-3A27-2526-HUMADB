-- Fix for SQLSTATE[42S22]: Column not found: 1054 Unknown column 't0.is_active' in 'field list'
-- This script adds the missing is_active column to the users table

-- Check if column exists and add it if it doesn't
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 NOT NULL;

-- For MySQL versions that don't support IF NOT EXISTS with ALTER TABLE:
-- First check if the column exists, then add it if it doesn't
-- SET @dbname = DATABASE();
-- SET @tablename = 'users';
-- SET @columnname = 'is_active';
-- SET @preparedStatement = (SELECT IF(
--   (
--     SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
--     WHERE 
--       (table_schema = @dbname)
--       AND (table_name = @tablename)
--       AND (column_name = @columnname)
--   ) > 0,
--   'SELECT 1',
--   CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TINYINT(1) DEFAULT 1 NOT NULL')
-- ));
-- PREPARE stmt FROM @preparedStatement;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

-- Update existing records to have is_active = 1 (default active state)
UPDATE users SET is_active = 1 WHERE is_active IS NULL;

-- Verify the column was added
DESCRIBE users;
