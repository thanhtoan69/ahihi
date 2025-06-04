-- ========================================
-- Phase 25E: Assign Roles to Sample Users
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- ASSIGN ROLES TO SAMPLE USERS
-- ========================================

-- Get user IDs and role IDs first
SET @moderator_user_id = (SELECT user_id FROM users WHERE username = 'moderator');
SET @content_creator_user_id = (SELECT user_id FROM users WHERE username = 'content_creator');
SET @ecocompany_user_id = (SELECT user_id FROM users WHERE username = 'ecocompany');
SET @greenorg_user_id = (SELECT user_id FROM users WHERE username = 'greenorg');
SET @user1_user_id = (SELECT user_id FROM users WHERE username = 'user1');
SET @user2_user_id = (SELECT user_id FROM users WHERE username = 'user2');
SET @user3_user_id = (SELECT user_id FROM users WHERE username = 'user3');

SET @admin_role_id = (SELECT role_id FROM user_roles WHERE role_name = 'Administrator');
SET @moderator_role_id = (SELECT role_id FROM user_roles WHERE role_name = 'Moderator');
SET @content_creator_role_id = (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator');
SET @business_partner_role_id = (SELECT role_id FROM user_roles WHERE role_name = 'Business Partner');
SET @organization_role_id = (SELECT role_id FROM user_roles WHERE role_name = 'Organization');
SET @regular_user_role_id = (SELECT role_id FROM user_roles WHERE role_name = 'Regular User');

-- Assign roles to sample users
INSERT IGNORE INTO user_role_assignments (user_id, role_id) VALUES
(@moderator_user_id, @moderator_role_id),
(@content_creator_user_id, @content_creator_role_id),
(@ecocompany_user_id, @business_partner_role_id),
(@greenorg_user_id, @organization_role_id),
(@user1_user_id, @regular_user_role_id),
(@user2_user_id, @regular_user_role_id),
(@user3_user_id, @regular_user_role_id);

-- Display role assignments
SELECT 'ROLE ASSIGNMENTS COMPLETED' as section;
SELECT 
    u.username, 
    u.user_type, 
    r.role_name,
    u.is_verified,
    u.green_points
FROM 
    users u
JOIN 
    user_role_assignments ra ON u.user_id = ra.user_id
JOIN 
    user_roles r ON ra.role_id = r.role_id
WHERE 
    u.username IN ('admin', 'moderator', 'content_creator', 'ecocompany', 'greenorg', 'user1', 'user2', 'user3')
ORDER BY 
    r.permission_level DESC, u.username;
