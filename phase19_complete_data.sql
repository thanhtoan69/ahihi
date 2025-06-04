-- Phase 19: Complete Sample Data with Categories
-- ========================================

-- Set SQL mode
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ========================================
-- FIRST: CREATE ACHIEVEMENT CATEGORIES
-- ========================================

INSERT IGNORE INTO achievement_categories (
    category_name, category_display_name, category_slug, 
    display_name_vi, display_name_en, description_vi, description_en,
    icon_name, color_primary, color_secondary, difficulty_level,
    environmental_impact_category, sort_order
) VALUES 
('environmental_action', 'Environmental Action', 'environmental-action',
 'Hành động Môi trường', 'Environmental Action', 
 'Thành tích cho các hành động bảo vệ môi trường', 'Achievements for environmental protection actions',
 'leaf', '#10b981', '#065f46', 'beginner', 'general', 1),

('carbon_reduction', 'Carbon Reduction', 'carbon-reduction',
 'Giảm thiểu Carbon', 'Carbon Reduction',
 'Thành tích tiết kiệm và giảm phát thải carbon', 'Achievements for carbon saving and emission reduction',
 'cloud', '#3b82f6', '#1e40af', 'intermediate', 'carbon', 2),

('waste_management', 'Waste Management', 'waste-management',
 'Quản lý Chất thải', 'Waste Management',
 'Thành tích xử lý và phân loại chất thải', 'Achievements for waste processing and classification',
 'recycle', '#f59e0b', '#d97706', 'intermediate', 'waste', 3),

('learning_education', 'Learning & Education', 'learning-education',
 'Học tập & Giáo dục', 'Learning & Education',
 'Thành tích học tập về môi trường', 'Educational achievements about environment',
 'book', '#8b5cf6', '#7c3aed', 'beginner', 'learning', 4),

('social_impact', 'Social Impact', 'social-impact',
 'Tác động Xã hội', 'Social Impact',
 'Thành tích ảnh hưởng và tuyên truyền xã hội', 'Achievements for social influence and advocacy',
 'users', '#ec4899', '#db2777', 'advanced', 'social', 5);

-- ========================================
-- SAMPLE ACHIEVEMENTS (Updated with correct category_ids)
-- ========================================

INSERT IGNORE INTO achievements_enhanced (
    achievement_key, category_id, title_vi, title_en, description_vi, description_en,
    achievement_type, difficulty, rarity, unlock_criteria, environmental_category,
    points_reward, experience_reward, green_points_reward
) VALUES 
('first_waste_report', 1, 'Báo cáo đầu tiên', 'First Report', 
 'Gửi báo cáo chất thải đầu tiên của bạn', 'Submit your first waste report',
 'one_time', 'easy', 'common', 
 '{"action": "waste_report", "count": 1}', 'waste', 100, 50, 25),
 
('carbon_saver_bronze', 2, 'Tiết kiệm Carbon Đồng', 'Carbon Saver Bronze',
 'Tiết kiệm 50kg CO2 thông qua các hoạt động xanh', 'Save 50kg CO2 through green activities',
 'one_time', 'medium', 'uncommon',
 '{"action": "carbon_saved", "threshold": 50}', 'carbon', 200, 100, 50),

('quiz_master', 4, 'Bậc thầy Quiz', 'Quiz Master',
 'Hoàn thành 20 quiz về môi trường', 'Complete 20 environmental quizzes',
 'one_time', 'medium', 'rare',
 '{"action": "quiz_completed", "count": 20}', 'learning', 300, 150, 75),

('social_advocate', 5, 'Nhà vận động xã hội', 'Social Advocate',
 'Chia sẻ 10 bài viết về môi trường', 'Share 10 environmental posts',
 'one_time', 'easy', 'common',
 '{"action": "social_share", "count": 10}', 'social', 150, 75, 35),

('waste_classifier_pro', 3, 'Chuyên gia Phân loại', 'Waste Classifier Pro',
 'Phân loại chính xác 100 loại chất thải', 'Correctly classify 100 waste items',
 'one_time', 'hard', 'epic',
 '{"action": "waste_classification", "count": 100, "accuracy": 90}', 'waste', 500, 250, 100),

('streak_champion', 1, 'Nhà vô địch Streak', 'Streak Champion',
 'Duy trì streak hoạt động 30 ngày liên tiếp', 'Maintain activity streak for 30 consecutive days',
 'one_time', 'hard', 'epic',
 '{"action": "daily_streak", "count": 30}', 'social', 500, 250, 100),

('eco_warrior_advanced', 1, 'Chiến binh Sinh thái Cao cấp', 'Advanced Eco Warrior',
 'Hoàn thành 100 hoạt động môi trường', 'Complete 100 environmental activities',
 'one_time', 'expert', 'legendary',
 '{"activities": 100, "carbon_saved": 500}', 'general', 1000, 500, 250),

('carbon_saver_silver', 2, 'Tiết kiệm Carbon Bạc', 'Carbon Saver Silver',
 'Tiết kiệm 200kg CO2 thông qua các hoạt động xanh', 'Save 200kg CO2 through green activities',
 'one_time', 'hard', 'rare',
 '{"action": "carbon_saved", "threshold": 200}', 'carbon', 400, 200, 100),

('learning_enthusiast', 4, 'Người đam mê Học tập', 'Learning Enthusiast',
 'Hoàn thành 5 khóa học về môi trường', 'Complete 5 environmental courses',
 'one_time', 'medium', 'uncommon',
 '{"action": "course_completed", "count": 5}', 'learning', 250, 125, 65);

-- ========================================
-- SAMPLE BADGES
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
 '/images/badges/carbon-hero.png', '#E5E4E2'),

('streak_master', 'streak-master', 'Bậc thầy Streak', 'Streak Master',
 'Duy trì hoạt động liên tục trong 365 ngày', 'Maintain activity for 365 consecutive days',
 'special', 'diamond', '{"daily_streak": 365}',
 '/images/badges/streak-master.png', '#B9F2FF'),

('first_steps', 'first-steps', 'Bước đầu tiên', 'First Steps',
 'Hoàn thành hoạt động đầu tiên trên nền tảng', 'Complete first activity on platform',
 'milestone', 'bronze', '{"first_activity": 1}',
 '/images/badges/first-steps.png', '#CD7F32');

-- ========================================
-- SAMPLE LEADERBOARDS
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
 'global', 'waste_processed', 'waste', 'weekly', 25, TRUE, TRUE),

('achievement_hunters', 'achievement-hunters', 'Thợ săn Thành tích', 'Achievement Hunters',
 'Người có nhiều thành tích nhất', 'Users with most achievements',
 'global', 'achievements', 'overall', 'all_time', 100, TRUE, TRUE),

('daily_streaks', 'daily-streaks', 'Streak hàng ngày', 'Daily Streaks',
 'Những người duy trì streak lâu nhất', 'Users with longest streaks',
 'global', 'streaks', 'social', 'all_time', 50, TRUE, TRUE);

-- ========================================
-- VERIFICATION
-- ========================================

SELECT 'Achievement Categories:' as info, COUNT(*) as count FROM achievement_categories
UNION ALL
SELECT 'Achievements Created:', COUNT(*) FROM achievements_enhanced
UNION ALL
SELECT 'Badges Created:', COUNT(*) FROM badges_system  
UNION ALL
SELECT 'Leaderboards Created:', COUNT(*) FROM leaderboards_enhanced
UNION ALL
SELECT 'Seasonal Challenges:', COUNT(*) FROM seasonal_challenges;

-- Reset SQL mode
SET SESSION sql_mode = DEFAULT;
