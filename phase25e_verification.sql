-- ========================================
-- Phase 25E: Verification Queries
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

SELECT 'ADMIN USER VERIFICATION' as section;
SELECT user_id, username, email, user_type, is_verified, user_level 
FROM users 
WHERE username = 'admin';

SELECT 'ROLES AND PERMISSIONS VERIFICATION' as section;
SELECT 
    r.role_name, 
    COUNT(rp.permission_id) AS permission_count
FROM 
    user_roles r
LEFT JOIN 
    user_role_permissions rp ON r.role_id = rp.role_id
GROUP BY 
    r.role_name
ORDER BY 
    r.permission_level DESC;

SELECT 'USER ROLE ASSIGNMENTS VERIFICATION' as section;
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
ORDER BY 
    r.permission_level DESC, u.username;
