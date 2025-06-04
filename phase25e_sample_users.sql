-- ========================================
-- Phase 25E: Sample Users with Roles
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- INSERT SAMPLE DATA
-- ========================================

-- Create sample users with different roles (using valid enum values)
INSERT IGNORE INTO users (
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
    total_carbon_saved
)
VALUES
-- Moderator (using 'moderator' enum value)
('moderator', 'moderator@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Minh', 'Moderator', 'moderator', TRUE, 500, 1000, 5, TRUE, 50.00),

-- Content Creator (using 'individual' enum value)
('content_creator', 'content@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Linh', 'Creator', 'individual', TRUE, 300, 600, 3, TRUE, 30.00),

-- Business Partner (using 'business' enum value)
('ecocompany', 'business@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Eco', 'Company', 'business', TRUE, 800, 1500, 6, TRUE, 120.00),

-- Organization (using 'organization' enum value)
('greenorg', 'org@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Green', 'Organization', 'organization', TRUE, 700, 1400, 5, TRUE, 80.00),

-- Regular Users (using 'individual' enum value)
('user1', 'user1@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Hoa', 'Nguyen', 'individual', TRUE, 150, 300, 2, TRUE, 15.00),
('user2', 'user2@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Tuan', 'Tran', 'individual', FALSE, 50, 100, 1, TRUE, 5.00),
('user3', 'user3@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Mai', 'Le', 'individual', TRUE, 200, 400, 2, TRUE, 25.00);

-- Assign roles to sample users
INSERT IGNORE INTO user_role_assignments (user_id, role_id)
VALUES
-- Moderator
((SELECT user_id FROM users WHERE username = 'moderator'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Moderator')),

-- Content Creator
((SELECT user_id FROM users WHERE username = 'content_creator'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator')),

-- Business Partner
((SELECT user_id FROM users WHERE username = 'ecocompany'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Business Partner')),

-- Organization
((SELECT user_id FROM users WHERE username = 'greenorg'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Organization')),

-- Regular Users
((SELECT user_id FROM users WHERE username = 'user1'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User')),
((SELECT user_id FROM users WHERE username = 'user2'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User')),
((SELECT user_id FROM users WHERE username = 'user3'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'));
