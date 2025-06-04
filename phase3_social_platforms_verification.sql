-- ========================================
-- Social Platforms Verification Script
-- Environmental Platform - Phase 3
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Show complete social platforms configuration
SELECT '==== SOCIAL PLATFORMS CONFIGURATION ====' AS section;

SELECT 
    platform_id,
    platform_name,
    platform_display_name,
    platform_icon,
    platform_color,
    share_url_template,
    points_per_share,
    is_active,
    sort_order
FROM 
    social_platforms
ORDER BY 
    sort_order;

-- Verify platform counts
SELECT '==== PLATFORMS COUNT ====' AS section;
SELECT COUNT(*) AS 'Total Social Platforms' FROM social_platforms;
SELECT COUNT(*) AS 'Active Social Platforms' FROM social_platforms WHERE is_active = TRUE;

-- Show share URL parameter usage
SELECT '==== SHARE URL PARAMETERS ====' AS section;
SELECT 
    platform_name,
    IF(share_url_template LIKE '%{url}%', 'Yes', 'No') AS 'Uses URL Parameter',
    IF(share_url_template LIKE '%{title}%', 'Yes', 'No') AS 'Uses Title Parameter',
    IF(share_url_template LIKE '%{description}%', 'Yes', 'No') AS 'Uses Description Parameter',
    IF(share_url_template LIKE '%{hashtags}%', 'Yes', 'No') AS 'Uses Hashtags Parameter'
FROM 
    social_platforms
ORDER BY 
    platform_name;

-- Check for missing values
SELECT '==== DATA QUALITY CHECK ====' AS section;
SELECT 
    'Platforms with missing icon' AS issue,
    COUNT(*) AS count
FROM 
    social_platforms
WHERE 
    platform_icon IS NULL OR platform_icon = ''
UNION ALL
SELECT 
    'Platforms with missing color' AS issue,
    COUNT(*) AS count
FROM 
    social_platforms
WHERE 
    platform_color IS NULL OR platform_color = ''
UNION ALL
SELECT 
    'Platforms with missing share URL' AS issue,
    COUNT(*) AS count
FROM 
    social_platforms
WHERE 
    share_url_template IS NULL OR share_url_template = '';

-- Final completion message
SELECT '==== SOCIAL PLATFORMS CONFIGURATION COMPLETE ====' AS section;
SELECT 
    CONCAT('✅ ', COUNT(*), ' Social Platforms Successfully Configured') AS status
FROM 
    social_platforms;

SELECT '🌐 Platform Integration Ready for Environmental Platform' AS message;
