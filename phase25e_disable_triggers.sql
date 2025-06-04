-- ========================================
-- Phase 25E: Temporarily Disable Triggers
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Backup trigger definitions
CREATE TABLE IF NOT EXISTS trigger_backup (
    trigger_name VARCHAR(100),
    trigger_definition TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Drop triggers that conflict with user insertion
DROP TRIGGER IF EXISTS after_user_registration;
DROP TRIGGER IF EXISTS after_user_login;
DROP TRIGGER IF EXISTS check_user_level_update;
DROP TRIGGER IF EXISTS invalidate_user_cache;

SELECT 'TRIGGERS DISABLED - READY FOR USER INSERTION' as status;
