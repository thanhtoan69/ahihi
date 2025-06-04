-- ========================================
-- Social Platforms Configuration Update
-- Environmental Platform - Phase 3 Enhancement
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Update existing social platforms
UPDATE social_platforms 
SET 
    platform_icon = '/assets/icons/social/facebook.svg',
    platform_color = '#1877F2',
    share_url_template = 'https://www.facebook.com/sharer/sharer.php?u={url}&quote={title}',
    points_per_share = 10,
    sort_order = 1
WHERE platform_name = 'facebook';

UPDATE social_platforms 
SET 
    platform_icon = '/assets/icons/social/twitter-x.svg',
    platform_color = '#000000',
    share_url_template = 'https://twitter.com/intent/tweet?url={url}&text={title}&hashtags={hashtags}',
    points_per_share = 8,
    sort_order = 2
WHERE platform_name = 'twitter';

UPDATE social_platforms 
SET 
    platform_icon = '/assets/icons/social/linkedin.svg',
    platform_color = '#0A66C2',
    share_url_template = 'https://www.linkedin.com/sharing/share-offsite/?url={url}&title={title}&summary={description}',
    points_per_share = 15,
    sort_order = 3
WHERE platform_name = 'linkedin';

UPDATE social_platforms 
SET 
    platform_icon = '/assets/icons/social/zalo.svg',
    platform_color = '#0068FF',
    share_url_template = 'https://zalo.me/share?u={url}&t={title}',
    points_per_share = 12,
    sort_order = 6
WHERE platform_name = 'zalo';

-- Insert new social platforms
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

-- Verify the updated social platforms
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
