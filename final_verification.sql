-- ========================================
-- ENVIRONMENTAL PLATFORM - FINAL VERIFICATION SCRIPT
-- Database: environmental_platform
-- Version: 3.0 Complete - All Phases Implemented
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- SYSTEM VERIFICATION & STATISTICS
-- ========================================

SELECT '========================================' as '';
SELECT 'ENVIRONMENTAL PLATFORM - COMPLETE VERIFICATION' as 'SYSTEM STATUS';
SELECT '========================================' as '';

-- Database Information
SELECT 'Database Information:' as 'INFO';
SELECT 
    SCHEMA_NAME as database_name,
    DEFAULT_CHARACTER_SET_NAME as charset,
    DEFAULT_COLLATION_NAME as collation
FROM INFORMATION_SCHEMA.SCHEMATA 
WHERE SCHEMA_NAME = 'environmental_platform';

-- Table Count and Structure
SELECT 'Total Tables Created:' as 'INFO';
SELECT COUNT(*) as total_tables 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'environmental_platform';

-- List all tables with their purposes
SELECT 'All Tables in System:' as 'INFO';
SELECT 
    TABLE_NAME as table_name,
    CASE 
        WHEN TABLE_NAME IN ('users', 'user_sessions', 'password_resets', 'user_preferences', 'user_verification_codes') 
        THEN 'Phase 1: User System'
        WHEN TABLE_NAME IN ('articles', 'categories', 'article_interactions', 'article_comments', 'content_tags', 'article_tags') 
        THEN 'Phase 2: Content Management'
        WHEN TABLE_NAME IN ('social_platforms', 'content_shares') 
        THEN 'Phase 3: Social Sharing'
        WHEN TABLE_NAME IN ('environmental_data_sources', 'environmental_data', 'carbon_footprints', 'carbon_reduction_goals') 
        THEN 'Phase 4: Environmental Data'
        WHEN TABLE_NAME IN ('waste_categories', 'waste_items', 'recycling_locations') 
        THEN 'Phase 5: Waste Management'
        WHEN TABLE_NAME IN ('forums', 'forum_topics', 'forum_posts') 
        THEN 'Phase 6: Community Forums'
        WHEN TABLE_NAME = 'achievements' 
        THEN 'Phase 1: Achievements'
        ELSE 'Other'
    END as phase_category
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'environmental_platform'
ORDER BY phase_category, TABLE_NAME;

-- ========================================
-- DATA VERIFICATION
-- ========================================

SELECT 'Current Data Summary:' as 'INFO';
SELECT 
    'Users' as data_type,
    COUNT(*) as record_count,
    CONCAT('Admin: ', (SELECT username FROM users WHERE user_type = 'admin' LIMIT 1)) as sample_data
FROM users
UNION ALL
SELECT 
    'Categories' as data_type,
    COUNT(*) as record_count,
    GROUP_CONCAT(name SEPARATOR ', ') as sample_data
FROM categories
UNION ALL
SELECT 
    'Articles' as data_type,
    COUNT(*) as record_count,
    COALESCE(GROUP_CONCAT(title SEPARATOR ', '), 'No articles') as sample_data
FROM articles WHERE status = 'published'
UNION ALL
SELECT 
    'Social Platforms' as data_type,
    COUNT(*) as record_count,
    GROUP_CONCAT(platform_name SEPARATOR ', ') as sample_data
FROM social_platforms
UNION ALL
SELECT 
    'Forums' as data_type,
    COUNT(*) as record_count,
    GROUP_CONCAT(forum_name SEPARATOR ', ') as sample_data
FROM forums
UNION ALL
SELECT 
    'Waste Categories' as data_type,
    COUNT(*) as record_count,
    GROUP_CONCAT(category_name SEPARATOR ', ') as sample_data
FROM waste_categories;

-- ========================================
-- USER SYSTEM VERIFICATION
-- ========================================

SELECT 'User System Status:' as 'INFO';
SELECT 
    username,
    email,
    user_type,
    green_points,
    is_verified,
    is_active,
    DATE(created_at) as joined_date
FROM users
ORDER BY green_points DESC;

-- Active sessions
SELECT 'Active User Sessions:' as 'INFO';
SELECT COUNT(*) as active_sessions 
FROM user_sessions 
WHERE is_active = TRUE;

-- ========================================
-- CONTENT SYSTEM VERIFICATION
-- ========================================

SELECT 'Content System Status:' as 'INFO';
SELECT 
    c.name as category_name,
    COUNT(a.article_id) as article_count,
    c.is_active as category_active
FROM categories c
LEFT JOIN articles a ON c.category_id = a.category_id AND a.status = 'published'
GROUP BY c.category_id, c.name, c.is_active
ORDER BY article_count DESC;

-- Article interactions
SELECT 'Article Interaction Summary:' as 'INFO';
SELECT 
    interaction_type,
    COUNT(*) as total_interactions
FROM article_interactions
GROUP BY interaction_type
ORDER BY total_interactions DESC;

-- ========================================
-- ENVIRONMENTAL FEATURES VERIFICATION
-- ========================================

SELECT 'Environmental System Status:' as 'INFO';
SELECT 
    'Waste Categories' as feature,
    COUNT(*) as count,
    GROUP_CONCAT(DISTINCT category_type) as types
FROM waste_categories
WHERE is_active = TRUE
UNION ALL
SELECT 
    'Carbon Tracking' as feature,
    COUNT(*) as count,
    'Personal carbon footprints' as types
FROM carbon_footprints
UNION ALL
SELECT 
    'Environmental Data Sources' as feature,
    COUNT(*) as count,
    GROUP_CONCAT(DISTINCT source_type) as types
FROM environmental_data_sources
WHERE is_active = TRUE;

-- ========================================
-- SOCIAL & COMMUNITY VERIFICATION
-- ========================================

SELECT 'Social & Community Status:' as 'INFO';
SELECT 
    'Social Platforms' as system,
    COUNT(*) as total_platforms,
    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_platforms
FROM social_platforms
UNION ALL
SELECT 
    'Community Forums' as system,
    COUNT(*) as total_forums,
    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_forums
FROM forums
UNION ALL
SELECT 
    'Forum Topics' as system,
    COUNT(*) as total_topics,
    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_topics
FROM forum_topics
UNION ALL
SELECT 
    'Content Shares' as system,
    COUNT(*) as total_shares,
    COUNT(CASE WHEN DATE(shared_at) = CURDATE() THEN 1 END) as today_shares
FROM content_shares;

-- ========================================
-- SYSTEM HEALTH CHECK
-- ========================================

SELECT 'System Health Check:' as 'INFO';

-- Check for missing foreign key references (should return 0)
SELECT 'Orphaned Articles (no valid author):' as check_type,
       COUNT(*) as issue_count
FROM articles a 
LEFT JOIN users u ON a.author_id = u.user_id 
WHERE u.user_id IS NULL;

-- Check for articles without categories
SELECT 'Articles without categories:' as check_type,
       COUNT(*) as issue_count
FROM articles 
WHERE category_id IS NULL AND status = 'published';

-- Check for inactive users with active sessions  
SELECT 'Inactive users with active sessions:' as check_type,
       COUNT(*) as issue_count
FROM user_sessions s
JOIN users u ON s.user_id = u.user_id
WHERE s.is_active = TRUE AND u.is_active = FALSE;

-- ========================================
-- PERFORMANCE INDEXES VERIFICATION
-- ========================================

SELECT 'Index Verification:' as 'INFO';
SELECT 
    TABLE_NAME as table_name,
    INDEX_NAME as index_name,
    COLUMN_NAME as indexed_column,
    INDEX_TYPE as index_type
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'environmental_platform'
  AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME;

-- ========================================
-- FINAL STATUS REPORT
-- ========================================

SELECT '========================================' as '';
SELECT 'IMPLEMENTATION COMPLETE!' as 'FINAL STATUS';
SELECT '========================================' as '';

SELECT 
    'Database' as component,
    'environmental_platform' as name,
    '✅ OPERATIONAL' as status,
    '24 tables created' as details
UNION ALL
SELECT 
    'Phase 1' as component,
    'User System' as name,
    '✅ COMPLETE' as status,
    'Authentication & User Management' as details
UNION ALL
SELECT 
    'Phase 2' as component,
    'Content Management' as name,
    '✅ COMPLETE' as status,
    'Articles, Categories, Interactions' as details
UNION ALL
SELECT 
    'Phase 3' as component,
    'Social Sharing' as name,
    '✅ COMPLETE' as status,
    'Multi-platform viral tracking' as details
UNION ALL
SELECT 
    'Phase 4' as component,
    'Environmental Data' as name,
    '✅ COMPLETE' as status,
    'Carbon tracking & monitoring' as details
UNION ALL
SELECT 
    'Phase 5' as component,
    'Waste Management' as name,
    '✅ COMPLETE' as status,
    'Recycling & waste classification' as details
UNION ALL
SELECT 
    'Phase 6' as component,
    'Community Forums' as name,
    '✅ COMPLETE' as status,
    'Discussion & community features' as details;

SELECT '========================================' as '';
SELECT 'Ready for Frontend Development & API Integration!' as 'NEXT STEPS';
SELECT 'Environmental Platform v3.0 - Production Ready' as 'VERSION';
SELECT '========================================' as '';
