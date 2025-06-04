-- ========================================
-- Phase 25D: Achievement System Setup
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- First, let's check if we need to modify the achievements table structure to support Vietnamese names
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = 'environmental_platform' 
     AND table_name = 'achievements' 
     AND column_name = 'title_vi') > 0,
    'SELECT 1',
    'ALTER TABLE achievements 
     ADD COLUMN title_vi VARCHAR(200) AFTER achievement_name,
     ADD COLUMN title_en VARCHAR(200) AFTER title_vi,
     ADD COLUMN description_vi TEXT AFTER description,
     ADD COLUMN description_en TEXT AFTER description_vi'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- BASIC ACHIEVEMENT DEFINITIONS
-- ========================================

INSERT INTO achievements (
    achievement_name,
    achievement_slug,
    title_vi,
    title_en,
    description,
    description_vi,
    description_en,
    category,
    difficulty,
    points_reward,
    badge_image,
    badge_color,
    unlock_criteria,
    is_active
) VALUES
-- 1. Newcomer Achievement
(
    'newcomer',
    'newcomer',
    'Người mới bắt đầu',
    'Newcomer',
    'Welcome to the environmental platform! Complete your first activity.',
    'Chào mừng đến với nền tảng môi trường! Hoàn thành hoạt động đầu tiên của bạn.',
    'Welcome to the environmental platform! Complete your first activity.',
    'environmental',
    'easy',
    50,
    '/assets/badges/newcomer.svg',
    '#4CAF50',
    '{"type": "login", "count": 1}',
    TRUE
),

-- 2. Waste Classifier Achievement
(
    'waste_classifier_basic',
    'waste-classifier-basic',
    'Nhà phân loại rác cơ bản',
    'Basic Waste Classifier',
    'Correctly classify 10 waste items using our AI classification system.',
    'Phân loại chính xác 10 loại rác thải bằng hệ thống phân loại AI của chúng tôi.',
    'Correctly classify 10 waste items using our AI classification system.',
    'environmental',
    'easy',
    100,
    '/assets/badges/waste_classifier_basic.svg',
    '#2196F3',
    '{"type": "waste_classification", "count": 10, "accuracy": 80}',
    TRUE
),

-- 3. Waste Classifier Intermediate
(
    'waste_classifier_intermediate',
    'waste-classifier-intermediate',
    'Nhà phân loại rác trung cấp',
    'Intermediate Waste Classifier',
    'Correctly classify 50 waste items with at least 85% accuracy.',
    'Phân loại chính xác 50 loại rác thải với độ chính xác ít nhất 85%.',
    'Correctly classify 50 waste items with at least 85% accuracy.',
    'environmental',
    'medium',
    250,
    '/assets/badges/waste_classifier_intermediate.svg',
    '#1976D2',
    '{"type": "waste_classification", "count": 50, "accuracy": 85}',
    TRUE
),

-- 4. Waste Classifier Advanced
(
    'waste_classifier_advanced',
    'waste-classifier-advanced',
    'Nhà phân loại rác nâng cao',
    'Advanced Waste Classifier',
    'Correctly classify 200 waste items with at least 90% accuracy.',
    'Phân loại chính xác 200 loại rác thải với độ chính xác ít nhất 90%.',
    'Correctly classify 200 waste items with at least 90% accuracy.',
    'environmental',
    'hard',
    500,
    '/assets/badges/waste_classifier_advanced.svg',
    '#0D47A1',
    '{"type": "waste_classification", "count": 200, "accuracy": 90}',
    TRUE
),

-- 5. Social Sharer Basic
(
    'social_sharer_basic',
    'social-sharer-basic',
    'Người chia sẻ cơ bản',
    'Basic Social Sharer',
    'Share 5 environmental articles or resources on social media.',
    'Chia sẻ 5 bài viết hoặc tài nguyên môi trường trên mạng xã hội.',
    'Share 5 environmental articles or resources on social media.',
    'social',
    'easy',
    75,
    '/assets/badges/social_sharer_basic.svg',
    '#FF9800',
    '{"type": "social_share", "count": 5}',
    TRUE
),

-- 6. Social Sharer Intermediate
(
    'social_sharer_intermediate',
    'social-sharer-intermediate',
    'Người chia sẻ trung cấp',
    'Intermediate Social Sharer',
    'Share 25 environmental articles or resources and get at least 50 reactions.',
    'Chia sẻ 25 bài viết hoặc tài nguyên môi trường và nhận được ít nhất 50 phản ứng.',
    'Share 25 environmental articles or resources and get at least 50 reactions.',
    'social',
    'medium',
    200,
    '/assets/badges/social_sharer_intermediate.svg',
    '#F57C00',
    '{"type": "social_share", "count": 25, "reactions": 50}',
    TRUE
),

-- 7. Social Sharer Advanced
(
    'social_sharer_advanced',
    'social-sharer-advanced',
    'Người chia sẻ nâng cao',
    'Advanced Social Sharer',
    'Share 100 environmental articles or resources and get at least 500 reactions.',
    'Chia sẻ 100 bài viết hoặc tài nguyên môi trường và nhận được ít nhất 500 phản ứng.',
    'Share 100 environmental articles or resources and get at least 500 reactions.',
    'social',
    'hard',
    400,
    '/assets/badges/social_sharer_advanced.svg',
    '#E65100',
    '{"type": "social_share", "count": 100, "reactions": 500}',
    TRUE
),

-- 8. Carbon Warrior Basic
(
    'carbon_warrior_basic',
    'carbon-warrior-basic',
    'Chiến binh Carbon cơ bản',
    'Basic Carbon Warrior',
    'Save 10kg of carbon emissions through platform activities.',
    'Tiết kiệm 10kg khí thải carbon thông qua các hoạt động trên nền tảng.',
    'Save 10kg of carbon emissions through platform activities.',
    'environmental',
    'easy',
    100,
    '/assets/badges/carbon_warrior_basic.svg',
    '#8BC34A',
    '{"type": "carbon_saved", "amount": 10}',
    TRUE
),

-- 9. Carbon Warrior Intermediate
(
    'carbon_warrior_intermediate',
    'carbon-warrior-intermediate',
    'Chiến binh Carbon trung cấp',
    'Intermediate Carbon Warrior',
    'Save 100kg of carbon emissions through platform activities.',
    'Tiết kiệm 100kg khí thải carbon thông qua các hoạt động trên nền tảng.',
    'Save 100kg of carbon emissions through platform activities.',
    'environmental',
    'medium',
    300,
    '/assets/badges/carbon_warrior_intermediate.svg',
    '#689F38',
    '{"type": "carbon_saved", "amount": 100}',
    TRUE
),

-- 10. Carbon Warrior Advanced
(
    'carbon_warrior_advanced',
    'carbon-warrior-advanced',
    'Chiến binh Carbon nâng cao',
    'Advanced Carbon Warrior',
    'Save 1000kg of carbon emissions through platform activities.',
    'Tiết kiệm 1000kg khí thải carbon thông qua các hoạt động trên nền tảng.',
    'Save 1000kg of carbon emissions through platform activities.',
    'environmental',
    'hard',
    800,
    '/assets/badges/carbon_warrior_advanced.svg',
    '#33691E',
    '{"type": "carbon_saved", "amount": 1000}',
    TRUE
),

-- 11. Eco Scholar Basic
(
    'eco_scholar_basic',
    'eco-scholar-basic',
    'Học giả Sinh thái cơ bản',
    'Basic Eco Scholar',
    'Complete 5 environmental quizzes with at least 70% score.',
    'Hoàn thành 5 bài kiểm tra môi trường với điểm số ít nhất 70%.',
    'Complete 5 environmental quizzes with at least 70% score.',
    'learning',
    'easy',
    100,
    '/assets/badges/eco_scholar_basic.svg',
    '#9C27B0',
    '{"type": "quiz_completion", "count": 5, "min_score": 70}',
    TRUE
),

-- 12. Eco Scholar Intermediate
(
    'eco_scholar_intermediate',
    'eco-scholar-intermediate',
    'Học giả Sinh thái trung cấp',
    'Intermediate Eco Scholar',
    'Complete 15 environmental quizzes with at least 80% score.',
    'Hoàn thành 15 bài kiểm tra môi trường với điểm số ít nhất 80%.',
    'Complete 15 environmental quizzes with at least 80% score.',
    'learning',
    'medium',
    250,
    '/assets/badges/eco_scholar_intermediate.svg',
    '#7B1FA2',
    '{"type": "quiz_completion", "count": 15, "min_score": 80}',
    TRUE
),

-- 13. Eco Scholar Advanced
(
    'eco_scholar_advanced',
    'eco-scholar-advanced',
    'Học giả Sinh thái nâng cao',
    'Advanced Eco Scholar',
    'Complete 30 environmental quizzes with at least 90% score.',
    'Hoàn thành 30 bài kiểm tra môi trường với điểm số ít nhất 90%.',
    'Complete 30 environmental quizzes with at least 90% score.',
    'learning',
    'hard',
    500,
    '/assets/badges/eco_scholar_advanced.svg',
    '#4A148C',
    '{"type": "quiz_completion", "count": 30, "min_score": 90}',
    TRUE
),

-- 14. Eco Community Member
(
    'eco_community_member',
    'eco-community-member',
    'Thành viên Cộng đồng Sinh thái',
    'Eco Community Member',
    'Participate in 3 community events or forums.',
    'Tham gia 3 sự kiện cộng đồng hoặc diễn đàn.',
    'Participate in 3 community events or forums.',
    'social',
    'easy',
    75,
    '/assets/badges/eco_community_member.svg',
    '#E91E63',
    '{"type": "community_participation", "count": 3}',
    TRUE
),

-- 15. Eco Community Contributor
(
    'eco_community_contributor',
    'eco-community-contributor',
    'Người đóng góp Cộng đồng Sinh thái',
    'Eco Community Contributor',
    'Create 10 forum posts or event comments with at least 5 likes each.',
    'Tạo 10 bài đăng diễn đàn hoặc bình luận sự kiện với ít nhất 5 lượt thích mỗi bài.',
    'Create 10 forum posts or event comments with at least 5 likes each.',
    'social',
    'medium',
    200,
    '/assets/badges/eco_community_contributor.svg',
    '#C2185B',
    '{"type": "forum_posts", "count": 10, "min_likes": 5}',
    TRUE
);

-- ========================================
-- VERIFY ACHIEVEMENT INSERTION
-- ========================================

SELECT 
    achievement_id,
    achievement_name,
    title_vi,
    title_en,
    category,
    difficulty,
    points_reward,
    badge_color
FROM 
    achievements
ORDER BY 
    category, 
    difficulty,
    achievement_id;
