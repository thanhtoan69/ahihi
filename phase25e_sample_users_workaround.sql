-- ========================================
-- Phase 25E: Sample Users (Workaround for Triggers)
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Temporarily disable triggers
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- ========================================
-- CREATE SAMPLE USERS USING DIRECT VALUES
-- ========================================

-- Insert sample users with explicit values for all required fields
INSERT INTO users (
    user_id,
    username,
    email,
    password_hash,
    first_name,
    last_name,
    user_type,
    is_verified,
    green_points,
    experience_points,
    user_level,
    is_active,
    total_carbon_saved,
    login_streak,
    longest_streak,
    created_at,
    updated_at
)
VALUES
-- Use specific user_ids to avoid conflicts
(10, 'moderator', 'moderator@environmental-platform.vn', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Minh', 'Moderator', 'moderator', TRUE, 500, 1000, 5, TRUE, 50.00, 0, 0, NOW(), NOW()),

(11, 'content_creator', 'content@environmental-platform.vn', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Linh', 'Creator', 'individual', TRUE, 300, 600, 3, TRUE, 30.00, 0, 0, NOW(), NOW()),

(12, 'ecocompany', 'business@environmental-platform.vn', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Eco', 'Company', 'business', TRUE, 800, 1500, 6, TRUE, 120.00, 0, 0, NOW(), NOW()),

(13, 'greenorg', 'org@environmental-platform.vn', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Green', 'Organization', 'organization', TRUE, 700, 1400, 5, TRUE, 80.00, 0, 0, NOW(), NOW()),

(14, 'user1', 'user1@example.com', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Hoa', 'Nguyen', 'individual', TRUE, 150, 300, 2, TRUE, 15.00, 0, 0, NOW(), NOW()),

(15, 'user2', 'user2@example.com', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Tuan', 'Tran', 'individual', FALSE, 50, 100, 1, TRUE, 5.00, 0, 0, NOW(), NOW()),

(16, 'user3', 'user3@example.com', 
 '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 
 'Mai', 'Le', 'individual', TRUE, 200, 400, 2, TRUE, 25.00, 0, 0, NOW(), NOW())

ON DUPLICATE KEY UPDATE username = username;

-- Reset SQL mode
SET SQL_MODE=@OLD_SQL_MODE;

-- Display created users
SELECT 'SAMPLE USERS CREATED' as section;
SELECT user_id, username, user_type, is_verified, green_points 
FROM users 
WHERE user_id >= 10;
