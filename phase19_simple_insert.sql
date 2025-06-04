-- Phase 19: Simple Sample Data Insert
-- ========================================

-- Set SQL mode
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ========================================
-- INSERT SAMPLE ACHIEVEMENTS
-- ========================================

INSERT IGNORE INTO achievements_enhanced (
    achievement_name, achievement_slug, achievement_code, 
    title_vi, title_en, description_vi, description_en,
    achievement_type, unlock_criteria, environmental_category,
    points_reward, experience_points, green_points,
    icon_name, rarity_level, difficulty_rating, is_active
) VALUES 
('First Waste Report', 'first-waste-report', 'FIRST_WASTE', 
 'Báo cáo đầu tiên', 'First Report', 
 'Gửi báo cáo chất thải đầu tiên của bạn', 'Submit your first waste report',
 'one_time', '{"action": "waste_report", "count": 1}', 'waste', 
 100, 50, 25, 'clipboard', 'common', 1, 1),
 
('Carbon Saver Bronze', 'carbon-saver-bronze', 'CARBON_BRONZE', 
 'Tiết kiệm Carbon Đồng', 'Carbon Saver Bronze',
 'Tiết kiệm 50kg CO2 thông qua các hoạt động xanh', 'Save 50kg CO2 through green activities',
 'one_time', '{"action": "carbon_saved", "threshold": 50}', 'carbon', 
 200, 100, 50, 'leaf', 'uncommon', 2, 1),

('Quiz Master', 'quiz-master', 'QUIZ_MASTER', 
 'Bậc thầy Quiz', 'Quiz Master',
 'Hoàn thành 20 quiz về môi trường', 'Complete 20 environmental quizzes',
 'one_time', '{"action": "quiz_completed", "count": 20}', 'learning', 
 300, 150, 75, 'brain', 'rare', 3, 1),

('Social Advocate', 'social-advocate', 'SOCIAL_ADV', 
 'Nhà vận động xã hội', 'Social Advocate',
 'Chia sẻ 10 bài viết về môi trường', 'Share 10 environmental posts',
 'one_time', '{"action": "social_share", "count": 10}', 'social', 
 150, 75, 35, 'share', 'common', 1, 1),

('Eco Warrior', 'eco-warrior', 'ECO_WARRIOR', 
 'Chiến binh Sinh thái', 'Eco Warrior',
 'Hoàn thành 50 hoạt động môi trường', 'Complete 50 environmental activities',
 'one_time', '{"activities": 50, "carbon_saved": 200}', 'general', 
 500, 250, 100, 'shield', 'epic', 4, 1);

-- ========================================
-- INSERT SAMPLE BADGES
-- ========================================

INSERT IGNORE INTO badges_system (
    badge_name, badge_slug, title_vi, title_en, description_vi, description_en,
    badge_category, rarity, unlock_criteria, icon_url, color_hex
) VALUES 
('eco_warrior', 'eco-warrior', 'Chiến binh Sinh thái', 'Eco Warrior',
 'Huy hiệu dành cho những người bảo vệ môi trường tích cực', 'Badge for active environmental protectors',
 'environmental', 'gold', '{"achievements": 10, "carbon_saved": 100}', 
 '/images/badges/eco-warrior.png', '#00A86B'),
 
('waste_master', 'waste-master', 'Bậc thầy Chất thải', 'Waste Master',
 'Chuyên gia phân loại và xử lý chất thải', 'Expert in waste classification and processing',
 'achievement', 'silver', '{"waste_reports": 50, "accuracy": 95}',
 '/images/badges/waste-master.png', '#C0C0C0'),

('learning_champion', 'learning-champion', 'Nhà vô địch Học tập', 'Learning Champion',
 'Hoàn thành xuất sắc các khóa học môi trường', 'Excellent completion of environmental courses',
 'milestone', 'bronze', '{"courses_completed": 5, "average_score": 80}',
 '/images/badges/learning-champion.png', '#CD7F32'),

('carbon_hero', 'carbon-hero', 'Anh hùng Carbon', 'Carbon Hero',
 'Giảm thiểu 1000kg CO2 cho môi trường', 'Reduce 1000kg CO2 for the environment',
 'environmental', 'platinum', '{"carbon_saved": 1000}',
 '/images/badges/carbon-hero.png', '#E5E4E2');

-- ========================================
-- INSERT SAMPLE LEADERBOARDS
-- ========================================

INSERT IGNORE INTO leaderboards_enhanced (
    leaderboard_name, leaderboard_slug, title_vi, title_en, description_vi, description_en,
    leaderboard_type, metric_type, environmental_category, period_type,
    max_entries, is_public, is_active
) VALUES 
('global_points', 'global-points', 'Bảng xếp hạng Điểm toàn cầu', 'Global Points Leaderboard',
 'Xếp hạng người dùng theo tổng điểm kiếm được', 'Rank users by total points earned',
 'global', 'points', 'overall', 'all_time', 100, TRUE, TRUE),

('monthly_carbon', 'monthly-carbon', 'Carbon hàng tháng', 'Monthly Carbon Saver',
 'Top người tiết kiệm carbon nhiều nhất trong tháng', 'Top carbon savers of the month',
 'global', 'carbon_saved', 'carbon', 'monthly', 50, TRUE, TRUE),

('waste_warriors', 'waste-warriors', 'Chiến binh Chất thải', 'Waste Warriors',
 'Những người xử lý chất thải hiệu quả nhất', 'Most efficient waste processors',
 'global', 'waste_processed', 'waste', 'weekly', 25, TRUE, TRUE);

-- ========================================
-- INSERT SAMPLE USER STREAKS
-- ========================================

-- Insert only if users exist
INSERT IGNORE INTO user_streaks_gamification (
    user_id, streak_type, current_streak, best_streak, 
    streak_multiplier, points_earned, total_activities
)
SELECT 1, 'daily_login', 15, 30, 1.50, 450, 45
WHERE EXISTS (SELECT 1 FROM users WHERE user_id = 1)
UNION ALL
SELECT 1, 'waste_disposal', 8, 20, 1.20, 240, 28
WHERE EXISTS (SELECT 1 FROM users WHERE user_id = 1);

-- ========================================
-- FINAL VERIFICATION
-- ========================================

SELECT 'DATABASE STATUS AFTER PHASE 19' as info;

SELECT 'Total Tables:' as info, COUNT(*) as count 
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';

SELECT 'Gamification Data Summary:' as info;

SELECT 'Achievement Categories:' as table_name, COUNT(*) as records FROM achievement_categories
UNION ALL
SELECT 'Achievements:', COUNT(*) FROM achievements_enhanced
UNION ALL
SELECT 'Badges:', COUNT(*) FROM badges_system  
UNION ALL
SELECT 'Leaderboards:', COUNT(*) FROM leaderboards_enhanced
UNION ALL
SELECT 'User Streaks:', COUNT(*) FROM user_streaks_gamification
UNION ALL
SELECT 'Seasonal Challenges:', COUNT(*) FROM seasonal_challenges;

-- Show sample achievement data
SELECT 'SAMPLE ACHIEVEMENTS:' as info;
SELECT 
    achievement_name,
    title_vi,
    achievement_type,
    environmental_category,
    points_reward,
    rarity_level
FROM achievements_enhanced 
LIMIT 3;

SELECT 'SAMPLE BADGES:' as info;
SELECT 
    badge_name,
    title_vi,
    badge_category,
    rarity
FROM badges_system 
LIMIT 3;

-- Reset SQL mode
SET SESSION sql_mode = DEFAULT;
