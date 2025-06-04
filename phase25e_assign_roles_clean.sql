-- ========================================
-- Phase 25E: Assign Roles (Clean)
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- ASSIGN ROLES TO SAMPLE USERS
-- ========================================

-- Assign roles using direct values
INSERT INTO user_role_assignments (user_id, role_id) VALUES
-- Admin user (already done, but ensure it exists)
(1, 1),
-- Moderator user
((SELECT user_id FROM users WHERE username = 'moderator'), (SELECT role_id FROM user_roles WHERE role_name = 'Moderator')),
-- Content Creator
((SELECT user_id FROM users WHERE username = 'content_creator'), (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator')),
-- Business Partner
((SELECT user_id FROM users WHERE username = 'ecocompany'), (SELECT role_id FROM user_roles WHERE role_name = 'Business Partner')),
-- Organization
((SELECT user_id FROM users WHERE username = 'greenorg'), (SELECT role_id FROM user_roles WHERE role_name = 'Organization')),
-- Regular Users
((SELECT user_id FROM users WHERE username = 'user1'), (SELECT role_id FROM user_roles WHERE role_name = 'Regular User')),
((SELECT user_id FROM users WHERE username = 'user2'), (SELECT role_id FROM user_roles WHERE role_name = 'Regular User')),
((SELECT user_id FROM users WHERE username = 'user3'), (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'))
ON DUPLICATE KEY UPDATE user_id = user_id;

SELECT 'ROLES ASSIGNED SUCCESSFULLY' as status;
