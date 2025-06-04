-- ========================================
-- Social Platforms Configuration
-- Environmental Platform Database - Phase 3 Enhancement
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- ADD PLATFORM_ICON COLUMN IF NOT EXISTS
-- ========================================

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = 'environmental_platform' 
     AND table_name = 'social_platforms' 
     AND column_name = 'platform_icon') > 0,
    'SELECT 1',
    'ALTER TABLE social_platforms ADD COLUMN platform_icon VARCHAR(255) AFTER platform_display_name'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- SOCIAL PLATFORMS CONFIGURATION
-- ========================================

-- Facebook configuration
INSERT INTO social_platforms (
    platform_name, 
    platform_display_name, 
    platform_icon,
    platform_color, 
    share_url_template, 
    points_per_share, 
    is_active, 
    sort_order
) VALUES (
    'facebook',
    'Facebook',
    '/assets/icons/social/facebook.svg',
    '#1877F2',
    'https://www.facebook.com/sharer/sharer.php?u={url}&quote={title}',
    10,
    TRUE,
    1
) ON DUPLICATE KEY UPDATE
    platform_display_name = VALUES(platform_display_name),
    platform_icon = VALUES(platform_icon),
    platform_color = VALUES(platform_color),
    share_url_template = VALUES(share_url_template),
    points_per_share = VALUES(points_per_share),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- Twitter/X configuration
INSERT INTO social_platforms (
    platform_name, 
    platform_display_name, 
    platform_icon,
    platform_color, 
    share_url_template, 
    points_per_share, 
    is_active, 
    sort_order
) VALUES (
    'twitter',
    'Twitter/X',
    '/assets/icons/social/twitter-x.svg',
    '#000000',
    'https://twitter.com/intent/tweet?url={url}&text={title}&hashtags={hashtags}',
    8,
    TRUE,
    2
) ON DUPLICATE KEY UPDATE
    platform_display_name = VALUES(platform_display_name),
    platform_icon = VALUES(platform_icon),
    platform_color = VALUES(platform_color),
    share_url_template = VALUES(share_url_template),
    points_per_share = VALUES(points_per_share),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- LinkedIn configuration
INSERT INTO social_platforms (
    platform_name, 
    platform_display_name, 
    platform_icon,
    platform_color, 
    share_url_template, 
    points_per_share, 
    is_active, 
    sort_order
) VALUES (
    'linkedin',
    'LinkedIn',
    '/assets/icons/social/linkedin.svg',
    '#0A66C2',
    'https://www.linkedin.com/sharing/share-offsite/?url={url}&title={title}&summary={description}',
    15,
    TRUE,
    3
) ON DUPLICATE KEY UPDATE
    platform_display_name = VALUES(platform_display_name),
    platform_icon = VALUES(platform_icon),
    platform_color = VALUES(platform_color),
    share_url_template = VALUES(share_url_template),
    points_per_share = VALUES(points_per_share),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- WhatsApp configuration
INSERT INTO social_platforms (
    platform_name, 
    platform_display_name, 
    platform_icon,
    platform_color, 
    share_url_template, 
    points_per_share, 
    is_active, 
    sort_order
) VALUES (
    'whatsapp',
    'WhatsApp',
    '/assets/icons/social/whatsapp.svg',
    '#25D366',
    'https://api.whatsapp.com/send?text={title}%20-%20{url}',
    5,
    TRUE,
    4
) ON DUPLICATE KEY UPDATE
    platform_display_name = VALUES(platform_display_name),
    platform_icon = VALUES(platform_icon),
    platform_color = VALUES(platform_color),
    share_url_template = VALUES(share_url_template),
    points_per_share = VALUES(points_per_share),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- Telegram configuration
INSERT INTO social_platforms (
    platform_name, 
    platform_display_name, 
    platform_icon,
    platform_color, 
    share_url_template, 
    points_per_share, 
    is_active, 
    sort_order
) VALUES (
    'telegram',
    'Telegram',
    '/assets/icons/social/telegram.svg',
    '#26A5E4',
    'https://t.me/share/url?url={url}&text={title}',
    5,
    TRUE,
    5
) ON DUPLICATE KEY UPDATE
    platform_display_name = VALUES(platform_display_name),
    platform_icon = VALUES(platform_icon),
    platform_color = VALUES(platform_color),
    share_url_template = VALUES(share_url_template),
    points_per_share = VALUES(points_per_share),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- Zalo configuration (Vietnam-specific)
INSERT INTO social_platforms (
    platform_name, 
    platform_display_name, 
    platform_icon,
    platform_color, 
    share_url_template, 
    points_per_share, 
    is_active, 
    sort_order
) VALUES (
    'zalo',
    'Zalo',
    '/assets/icons/social/zalo.svg',
    '#0068FF',
    'https://zalo.me/share?u={url}&t={title}',
    12,
    TRUE,
    6
) ON DUPLICATE KEY UPDATE
    platform_display_name = VALUES(platform_display_name),
    platform_icon = VALUES(platform_icon),
    platform_color = VALUES(platform_color),
    share_url_template = VALUES(share_url_template),
    points_per_share = VALUES(points_per_share),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

-- ========================================
-- VERIFICATION OUTPUT
-- ========================================

SELECT '==== SOCIAL PLATFORMS CONFIGURATION COMPLETE ====' AS section;

-- Display configured platforms
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

-- Verification stats
SELECT COUNT(*) AS 'Total Social Platforms Configured' FROM social_platforms;
SELECT '🌐 Social Platform Integration Ready for Environmental Platform' AS status;
