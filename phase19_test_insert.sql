-- Phase 19: Final Simple Data Insert
-- ========================================

-- Set SQL mode
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- Test simple insert without complex JSON
INSERT IGNORE INTO achievements_enhanced (
    achievement_name, achievement_slug, achievement_code, 
    title_vi, title_en, description_vi, description_en,
    achievement_type, unlock_criteria, environmental_category,
    points_reward, experience_points, green_points,
    icon_name, rarity_level, difficulty_rating, is_active
) VALUES 
('First Report', 'first-report', 'FIRST01', 
 'Báo cáo đầu tiên', 'First Report', 
 'Hoàn thành báo cáo đầu tiên', 'Complete first report',
 'one_time', '{}', 'waste', 
 100, 50, 25, 'clipboard', 'common', 1, 1);

-- Test badge insert
INSERT IGNORE INTO badges_system (
    badge_name, badge_slug, title_vi, title_en, description_vi, description_en,
    badge_category, rarity, unlock_criteria, icon_url, color_hex
) VALUES 
('starter', 'starter-badge', 'Người mới bắt đầu', 'Starter',
 'Huy hiệu cho người mới', 'Badge for beginners',
 'milestone', 'bronze', '{}', 
 '/images/starter.png', '#CD7F32');

-- Test leaderboard insert
INSERT IGNORE INTO leaderboards_enhanced (
    leaderboard_name, leaderboard_slug, title_vi, title_en, description_vi, description_en,
    leaderboard_type, metric_type, environmental_category, period_type,
    max_entries, is_public, is_active
) VALUES 
('test_leaderboard', 'test-board', 'Bảng xếp hạng thử nghiệm', 'Test Leaderboard',
 'Bảng xếp hạng để thử nghiệm', 'Test leaderboard',
 'global', 'points', 'overall', 'all_time', 100, 1, 1);

-- Final check
SELECT 'FINAL STATUS:' as status;
SELECT 'Achievements:' as type, COUNT(*) as count FROM achievements_enhanced
UNION ALL
SELECT 'Badges:', COUNT(*) FROM badges_system  
UNION ALL
SELECT 'Leaderboards:', COUNT(*) FROM leaderboards_enhanced
UNION ALL
SELECT 'Categories:', COUNT(*) FROM achievement_categories
UNION ALL
SELECT 'Challenges:', COUNT(*) FROM seasonal_challenges;

SET SESSION sql_mode = DEFAULT;
