-- ========================================
-- Social Platforms Configuration
-- Environmental Platform - Phase 3 Enhancement
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Insert social platforms with proper configuration
INSERT INTO social_platforms (
    platform_name,
    platform_display_name,
    platform_icon,
    platform_color,
    share_url_template,
    points_per_share,
    is_active,
    sort_order
) VALUES 
-- Facebook
(
    'facebook',
    'Facebook',
    '/assets/icons/social/facebook.svg',
    '#1877F2',
    'https://www.facebook.com/sharer/sharer.php?u={url}&quote={title}',
    10,
    TRUE,
    1
),

-- Twitter/X
(
    'twitter',
    'Twitter/X',
    '/assets/icons/social/twitter-x.svg',
    '#000000',
    'https://twitter.com/intent/tweet?url={url}&text={title}&hashtags={hashtags}',
    8,
    TRUE,
    2
),

-- LinkedIn
(
    'linkedin',
    'LinkedIn',
    '/assets/icons/social/linkedin.svg',
    '#0A66C2',
    'https://www.linkedin.com/sharing/share-offsite/?url={url}&title={title}&summary={description}',
    15,
    TRUE,
    3
),

-- WhatsApp
(
    'whatsapp',
    'WhatsApp',
    '/assets/icons/social/whatsapp.svg',
    '#25D366',
    'https://api.whatsapp.com/send?text={title}%20-%20{url}',
    5,
    TRUE,
    4
),

-- Telegram
(
    'telegram',
    'Telegram',
    '/assets/icons/social/telegram.svg',
    '#26A5E4',
    'https://t.me/share/url?url={url}&text={title}',
    5,
    TRUE,
    5
);

-- Verify the inserted social platforms
SELECT 
    platform_id,
    platform_name,
    platform_display_name,
    platform_color,
    points_per_share,
    sort_order
FROM 
    social_platforms
ORDER BY 
    sort_order;
