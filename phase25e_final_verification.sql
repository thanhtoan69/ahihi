-- ========================================
-- Phase 25E: Final Verification
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- COMPREHENSIVE VERIFICATION QUERIES
-- ========================================

SELECT '=======================================' as section;
SELECT 'PHASE 25E VERIFICATION REPORT' as section;
SELECT '=======================================' as section;

SELECT 'ADMIN USER VERIFICATION' as section;
SELECT user_id, username, email, user_type, is_verified, user_level, green_points
FROM users 
WHERE username = 'admin';

SELECT '=======================================' as section;
SELECT 'ALL SAMPLE USERS VERIFICATION' as section;
SELECT user_id, username, user_type, is_verified, green_points, experience_points
FROM users 
ORDER BY user_id;

SELECT '=======================================' as section;
SELECT 'ROLES SYSTEM VERIFICATION' as section;
SELECT 
    r.role_name, 
    r.permission_level,
    COUNT(rp.permission_id) AS permission_count
FROM 
    user_roles r
LEFT JOIN 
    user_role_permissions rp ON r.role_id = rp.role_id
GROUP BY 
    r.role_name, r.permission_level
ORDER BY 
    r.permission_level DESC;

SELECT '=======================================' as section;
SELECT 'USER ROLE ASSIGNMENTS VERIFICATION' as section;
SELECT 
    u.username, 
    u.user_type, 
    r.role_name,
    r.permission_level,
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

SELECT '=======================================' as section;
SELECT 'PERMISSIONS SUMMARY' as section;
SELECT 
    p.permission_code,
    p.permission_name,
    COUNT(rp.role_id) as assigned_to_roles
FROM 
    user_permissions p
LEFT JOIN 
    user_role_permissions rp ON p.permission_id = rp.permission_id
GROUP BY 
    p.permission_code, p.permission_name
ORDER BY 
    assigned_to_roles DESC, p.permission_code;

SELECT '=======================================' as section;
SELECT 'PHASE 25E COMPLETION STATUS' as section;
SELECT 
    'ADMIN USER CREATED' as task,
    CASE WHEN EXISTS(SELECT 1 FROM users WHERE username = 'admin' AND user_type = 'admin') 
         THEN '✓ COMPLETED' 
         ELSE '✗ FAILED' 
    END as status
UNION ALL
SELECT 
    'ROLE SYSTEM CREATED' as task,
    CASE WHEN EXISTS(SELECT 1 FROM user_roles) AND 
              EXISTS(SELECT 1 FROM user_permissions) AND 
              EXISTS(SELECT 1 FROM user_role_permissions) AND 
              EXISTS(SELECT 1 FROM user_role_assignments)
         THEN '✓ COMPLETED' 
         ELSE '✗ FAILED' 
    END as status
UNION ALL
SELECT 
    'SAMPLE USERS CREATED' as task,
    CASE WHEN (SELECT COUNT(*) FROM users WHERE username IN ('moderator', 'content_creator', 'ecocompany', 'greenorg', 'user1', 'user2', 'user3')) = 7
         THEN '✓ COMPLETED' 
         ELSE '✗ FAILED' 
    END as status
UNION ALL
SELECT 
    'ROLE ASSIGNMENTS COMPLETED' as task,
    CASE WHEN (SELECT COUNT(*) FROM user_role_assignments) >= 8
         THEN '✓ COMPLETED' 
         ELSE '✗ FAILED' 
    END as status;

SELECT '=======================================' as section;
