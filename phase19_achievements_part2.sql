-- ========================================
-- PHASE 19: ACHIEVEMENTS & GAMIFICATION SYSTEM (PART 2)
-- Environmental Platform Database
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 12. ENHANCED USER STREAKS SYSTEM
-- ========================================

DROP TABLE IF EXISTS user_streaks_enhanced;

CREATE TABLE user_streaks_enhanced (
    streak_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Streak Type & Category
    streak_type ENUM('login', 'activity', 'carbon_save', 'waste_classify', 'social_share', 'quiz_complete', 'custom') NOT NULL,
    streak_category VARCHAR(100),
    
    -- Current Streak Information
    current_streak INT DEFAULT 0,
    current_streak_start_date DATE NULL,
    last_activity_date DATE NULL,
    
    -- Historical Records
    best_streak INT DEFAULT 0,
    best_streak_start_date DATE NULL,
    best_streak_end_date DATE NULL,
    total_streak_days INT DEFAULT 0,
    
    -- Streak Maintenance
    streak_multiplier DECIMAL(3,2) DEFAULT 1.00,
    freeze_count INT DEFAULT 0, -- Streak freezes used
    max_freeze_allowed INT DEFAULT 3,
    last_freeze_used_date DATE NULL,
    
    -- Milestone Tracking
    milestone_achievements JSON, -- Streaks that earned achievements
    milestone_rewards JSON, -- Rewards earned at milestones
    next_milestone INT DEFAULT 7, -- Next milestone target
    
    -- Environmental Impact
    environmental_impact_total DECIMAL(10,2) DEFAULT 0,
    carbon_saved_through_streaks DECIMAL(10,2) DEFAULT 0,
    sustainability_bonus_earned INT DEFAULT 0,
    
    -- Motivation & Engagement
    motivation_messages JSON,
    encouragement_sent INT DEFAULT 0,
    comeback_attempts INT DEFAULT 0,
    
    -- Social Features
    is_public BOOLEAN DEFAULT TRUE,
    share_milestones BOOLEAN DEFAULT TRUE,
    celebration_shared BOOLEAN DEFAULT FALSE,
    
    -- Analytics
    average_streak_length DECIMAL(5,2) DEFAULT 0,
    streak_consistency_score DECIMAL(3,2) DEFAULT 0,
    improvement_trend ENUM('improving', 'stable', 'declining', 'new') DEFAULT 'new',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_streak_type (user_id, streak_type, streak_category),
    INDEX idx_current_streak (user_id, current_streak DESC),
    INDEX idx_best_streak (user_id, best_streak DESC),
    INDEX idx_streak_type (streak_type, current_streak DESC),
    INDEX idx_last_activity (last_activity_date DESC),
    INDEX idx_environmental_impact (environmental_impact_total DESC)
) ENGINE=InnoDB;

-- ========================================
-- 13. SEASONAL EVENTS & CHALLENGES
-- ========================================

CREATE TABLE seasonal_events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Event Information
    event_name VARCHAR(150) NOT NULL,
    event_slug VARCHAR(150) UNIQUE NOT NULL,
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Event Type & Category
    event_type ENUM('seasonal', 'holiday', 'awareness', 'challenge', 'campaign', 'special') DEFAULT 'seasonal',
    environmental_theme ENUM('earth_day', 'environment_week', 'recycle_month', 'energy_save', 'water_conservation', 'custom') DEFAULT 'custom',
    
    -- Timing
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    registration_start TIMESTAMP NULL,
    registration_end TIMESTAMP NULL,
    
    -- Participation
    max_participants INT DEFAULT NULL,
    current_participants INT DEFAULT 0,
    participation_requirements JSON,
    
    -- Rewards & Achievements
    event_achievements JSON,
    participation_rewards JSON,
    completion_rewards JSON,
    special_badges JSON,
    
    -- Visual & Branding
    event_image_url VARCHAR(255),
    banner_image_url VARCHAR(255),
    theme_color VARCHAR(7) DEFAULT '#10b981',
    celebration_animation VARCHAR(50),
    
    -- Social Features
    hashtag VARCHAR(100),
    social_sharing_enabled BOOLEAN DEFAULT TRUE,
    team_participation_allowed BOOLEAN DEFAULT FALSE,
    
    -- Status & Moderation
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    requires_approval BOOLEAN DEFAULT FALSE,
    
    -- Environmental Impact
    target_carbon_reduction DECIMAL(15,2) DEFAULT 0,
    target_waste_reduction DECIMAL(15,2) DEFAULT 0,
    actual_environmental_impact JSON,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_active_dates (is_active, start_date, end_date),
    INDEX idx_event_type (event_type, environmental_theme),
    INDEX idx_participation (current_participants, max_participants),
    FULLTEXT(event_name, title_vi, title_en, description_vi, description_en)
) ENGINE=InnoDB;

CREATE TABLE seasonal_event_participation (
    participation_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Participation Details
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    participation_status ENUM('registered', 'active', 'completed', 'withdrawn', 'disqualified') DEFAULT 'registered',
    
    -- Progress Tracking
    progress_data JSON,
    milestones_reached JSON,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- Performance Metrics
    points_earned INT DEFAULT 0,
    rank_position INT DEFAULT NULL,
    activities_completed INT DEFAULT 0,
    environmental_contribution DECIMAL(10,2) DEFAULT 0,
    
    -- Social Engagement
    team_id INT DEFAULT NULL,
    referral_count INT DEFAULT 0,
    social_shares INT DEFAULT 0,
    
    -- Rewards Earned
    badges_earned JSON,
    achievements_unlocked JSON,
    rewards_claimed JSON,
    
    -- Completion Data
    completed_at TIMESTAMP NULL,
    completion_certificate_url VARCHAR(255),
    
    FOREIGN KEY (event_id) REFERENCES seasonal_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_event_user (event_id, user_id),
    INDEX idx_event_status (event_id, participation_status),
    INDEX idx_user_events (user_id, joined_at DESC),
    INDEX idx_performance (event_id, points_earned DESC, rank_position)
) ENGINE=InnoDB;

-- ========================================
-- 14. GAMIFICATION ANALYTICS
-- ========================================

CREATE TABLE gamification_analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Time Period
    analytics_date DATE NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    
    -- User Engagement Metrics
    total_active_users INT DEFAULT 0,
    new_users_joined INT DEFAULT 0,
    users_with_achievements INT DEFAULT 0,
    users_with_streaks INT DEFAULT 0,
    
    -- Achievement Metrics
    total_achievements_unlocked INT DEFAULT 0,
    unique_achievements_unlocked INT DEFAULT 0,
    average_achievements_per_user DECIMAL(5,2) DEFAULT 0,
    most_popular_achievement_id INT,
    
    -- Points & Rewards
    total_points_awarded INT DEFAULT 0,
    total_green_points_awarded INT DEFAULT 0,
    average_points_per_user DECIMAL(8,2) DEFAULT 0,
    top_point_earner_id INT,
    
    -- Environmental Impact
    total_carbon_saved DECIMAL(15,2) DEFAULT 0,
    total_waste_classified DECIMAL(15,2) DEFAULT 0,
    environmental_activities_completed INT DEFAULT 0,
    sustainability_score_improvement DECIMAL(10,2) DEFAULT 0,
    
    -- Social Engagement
    social_shares_count INT DEFAULT 0,
    achievement_celebrations INT DEFAULT 0,
    leaderboard_views INT DEFAULT 0,
    
    -- Streaks & Consistency
    active_streaks_count INT DEFAULT 0,
    broken_streaks_count INT DEFAULT 0,
    average_streak_length DECIMAL(5,2) DEFAULT 0,
    
    -- Quality Metrics
    engagement_score DECIMAL(5,2) DEFAULT 0,
    retention_rate DECIMAL(5,2) DEFAULT 0,
    activity_diversity_score DECIMAL(5,2) DEFAULT 0,
    
    -- Leaderboard Performance
    leaderboard_changes INT DEFAULT 0,
    top_performers JSON,
    rank_improvements INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_date_period (analytics_date, period_type),
    INDEX idx_engagement (engagement_score DESC, retention_rate DESC),
    INDEX idx_environmental (total_carbon_saved DESC, total_waste_classified DESC)
) ENGINE=InnoDB;

-- ========================================
-- 15. USER GAMIFICATION PREFERENCES
-- ========================================

CREATE TABLE user_gamification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Achievement Notifications
    achievement_notifications BOOLEAN DEFAULT TRUE,
    achievement_sound_enabled BOOLEAN DEFAULT TRUE,
    achievement_animation_enabled BOOLEAN DEFAULT TRUE,
    show_achievement_progress BOOLEAN DEFAULT TRUE,
    
    -- Leaderboard Preferences
    participate_in_leaderboards BOOLEAN DEFAULT TRUE,
    show_in_public_leaderboards BOOLEAN DEFAULT TRUE,
    leaderboard_notification_enabled BOOLEAN DEFAULT TRUE,
    preferred_leaderboard_types JSON DEFAULT '["global", "regional"]',
    
    -- Social Sharing
    auto_share_achievements BOOLEAN DEFAULT FALSE,
    share_milestone_streaks BOOLEAN DEFAULT TRUE,
    preferred_share_platforms JSON DEFAULT '["facebook", "twitter"]',
    
    -- Motivation & Encouragement
    daily_motivation_enabled BOOLEAN DEFAULT TRUE,
    streak_reminder_enabled BOOLEAN DEFAULT TRUE,
    goal_reminder_frequency ENUM('never', 'daily', 'weekly', 'monthly') DEFAULT 'weekly',
    celebration_style ENUM('minimal', 'standard', 'enthusiastic') DEFAULT 'standard',
    
    -- Privacy Settings
    hide_progress_from_friends BOOLEAN DEFAULT FALSE,
    anonymous_leaderboard_participation BOOLEAN DEFAULT FALSE,
    hide_environmental_impact BOOLEAN DEFAULT FALSE,
    
    -- Difficulty & Challenge Level
    preferred_challenge_level ENUM('easy', 'medium', 'hard', 'adaptive') DEFAULT 'adaptive',
    skip_easy_achievements BOOLEAN DEFAULT FALSE,
    focus_environmental_achievements BOOLEAN DEFAULT TRUE,
    
    -- Notification Timing
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    weekend_notifications BOOLEAN DEFAULT TRUE,
    
    -- Personalization
    favorite_achievement_categories JSON,
    environmental_focus_areas JSON,
    custom_goals JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_preferences (user_id),
    INDEX idx_notification_preferences (achievement_notifications, leaderboard_notification_enabled),
    INDEX idx_participation_preferences (participate_in_leaderboards, show_in_public_leaderboards)
) ENGINE=InnoDB;

-- ========================================
-- 16. SAMPLE DATA INSERTION
-- ========================================

-- Insert Achievement Categories
INSERT INTO achievement_categories (
    category_name, category_display_name, category_slug, 
    display_name_vi, display_name_en, description_vi, description_en,
    icon_name, color_primary, environmental_impact_category, sort_order
) VALUES
('environmental_champion', 'Environmental Champion', 'environmental-champion',
 'Nhà vô địch môi trường', 'Environmental Champion', 
 'Những thành tích xuất sắc trong bảo vệ môi trường', 'Outstanding achievements in environmental protection',
 'leaf', '#22c55e', 'general', 1),
 
('carbon_warrior', 'Carbon Warrior', 'carbon-warrior',
 'Chiến binh Carbon', 'Carbon Warrior',
 'Tiết kiệm carbon và giảm khí thải', 'Carbon saving and emission reduction',
 'cloud', '#3b82f6', 'carbon', 2),
 
('waste_master', 'Waste Classification Master', 'waste-master',
 'Chuyên gia phân loại rác', 'Waste Classification Master',
 'Thành thạo trong phân loại và tái chế rác thải', 'Mastery in waste classification and recycling',
 'recycle', '#10b981', 'waste', 3),
 
('social_advocate', 'Social Environmental Advocate', 'social-advocate',
 'Nhà vận động môi trường xã hội', 'Social Environmental Advocate',
 'Chia sẻ và lan tỏa ý thức môi trường', 'Sharing and spreading environmental awareness',
 'users', '#8b5cf6', 'social', 4),
 
('learning_enthusiast', 'Environmental Learning Enthusiast', 'learning-enthusiast',
 'Người ham học môi trường', 'Environmental Learning Enthusiast',
 'Tích cực học hỏi kiến thức môi trường', 'Active learning about environmental knowledge',
 'book', '#f59e0b', 'learning', 5);

-- Insert Sample Achievements
INSERT INTO achievements_new (
    category_id, achievement_name, achievement_slug, achievement_code,
    title_vi, title_en, description_vi, description_en,
    achievement_type, trigger_events, unlock_criteria,
    points_reward, green_points, rarity_level, environmental_category
) VALUES
(1, 'First Steps', 'first-steps', 'FIRST_STEPS',
 'Những bước đầu tiên', 'First Steps',
 'Hoàn thành đăng ký và thiết lập hồ sơ', 'Complete registration and profile setup',
 'one_time', '["registration", "profile_complete"]', 
 '{"type": "and", "conditions": [{"event": "registration", "value": 1}, {"event": "profile_complete", "value": 1}]}',
 50, 25, 'common', 'general'),

(2, 'Carbon Saver', 'carbon-saver', 'CARBON_SAVER_10',
 'Người tiết kiệm Carbon', 'Carbon Saver',
 'Tiết kiệm được 10kg CO2', 'Save 10kg of CO2',
 'progressive', '["carbon_save"]',
 '{"type": "single", "conditions": [{"event": "carbon_save", "value": 10, "unit": "kg"}]}',
 100, 50, 'uncommon', 'carbon'),

(3, 'Waste Classifier Beginner', 'waste-classifier-beginner', 'WASTE_CLASS_10',
 'Người mới phân loại rác', 'Waste Classifier Beginner',
 'Phân loại đúng 10 loại rác thải', 'Correctly classify 10 waste items',
 'progressive', '["waste_classification"]',
 '{"type": "single", "conditions": [{"event": "waste_classification", "value": 10, "accuracy": 0.8}]}',
 75, 40, 'common', 'waste'),

(4, 'Social Sharer', 'social-sharer', 'SOCIAL_SHARE_5',
 'Người chia sẻ', 'Social Sharer',
 'Chia sẻ 5 bài viết về môi trường', 'Share 5 environmental articles',
 'progressive', '["social_sharing"]',
 '{"type": "single", "conditions": [{"event": "social_sharing", "value": 5, "category": "environmental"}]}',
 60, 30, 'common', 'social'),

(5, 'Quiz Master', 'quiz-master', 'QUIZ_MASTER_10',
 'Bậc thầy câu hỏi', 'Quiz Master',
 'Hoàn thành 10 bài quiz về môi trường', 'Complete 10 environmental quizzes',
 'progressive', '["quiz_complete"]',
 '{"type": "single", "conditions": [{"event": "quiz_complete", "value": 10, "score": 0.7}]}',
 120, 60, 'uncommon', 'learning');

-- Insert User Levels
INSERT INTO user_levels (
    level_number, experience_required, experience_from_previous,
    level_name_vi, level_name_en, level_description_vi, level_description_en,
    level_icon, level_color, environmental_tier, green_points_bonus
) VALUES
(1, 0, 0, 'Người mới', 'Newcomer', 'Bắt đầu hành trình xanh', 'Start your green journey', 'seedling', '#22c55e', 'newcomer', 0),
(2, 100, 100, 'Khám phá xanh', 'Green Explorer', 'Khám phá thế giới xanh', 'Explore the green world', 'leaf', '#16a34a', 'explorer', 10),
(3, 250, 150, 'Nhà hoạt động', 'Eco Activist', 'Tích cực hoạt động vì môi trường', 'Active for the environment', 'tree', '#15803d', 'advocate', 25),
(4, 500, 250, 'Chiến binh xanh', 'Green Warrior', 'Chiến đấu vì hành tinh xanh', 'Fight for a green planet', 'shield', '#166534', 'champion', 50),
(5, 1000, 500, 'Huyền thoại', 'Eco Legend', 'Huyền thoại bảo vệ môi trường', 'Environmental protection legend', 'crown', '#14532d', 'legend', 100);

-- Insert Badge Categories
INSERT INTO badge_categories (
    category_name, display_name_vi, display_name_en, 
    description_vi, description_en, icon_name, color_primary, sort_order
) VALUES
('milestone', 'Cột mốc', 'Milestone', 'Huy hiệu đạt cột mốc quan trọng', 'Badges for reaching important milestones', 'flag', '#3b82f6', 1),
('achievement', 'Thành tích', 'Achievement', 'Huy hiệu thành tích đặc biệt', 'Special achievement badges', 'trophy', '#f59e0b', 2),
('seasonal', 'Theo mùa', 'Seasonal', 'Huy hiệu sự kiện theo mùa', 'Seasonal event badges', 'calendar', '#8b5cf6', 3),
('special', 'Đặc biệt', 'Special', 'Huy hiệu đặc biệt hiếm', 'Rare special badges', 'star', '#ec4899', 4);

-- Insert Sample Leaderboards
INSERT INTO leaderboards_new (
    leaderboard_name, leaderboard_slug, display_name_vi, display_name_en,
    description_vi, description_en, leaderboard_type, ranking_metric,
    period_type, icon_name, environmental_focus
) VALUES
('Global Carbon Savers', 'global-carbon-savers', 'Bảng xếp hạng tiết kiệm Carbon toàn cầu', 'Global Carbon Savers Leaderboard',
 'Xếp hạng những người tiết kiệm carbon nhiều nhất', 'Ranking of top carbon savers worldwide',
 'global', 'carbon_saved', 'monthly', 'globe', 'carbon'),
 
('Waste Classification Masters', 'waste-masters', 'Bảng xếp hạng chuyên gia phân loại rác', 'Waste Classification Masters',
 'Xếp hạng những chuyên gia phân loại rác hàng đầu', 'Ranking of top waste classification experts',
 'global', 'waste_classified', 'weekly', 'recycle', 'waste'),
 
('Environmental Points Leaders', 'points-leaders', 'Bảng xếp hạng điểm môi trường', 'Environmental Points Leaders',
 'Xếp hạng theo tổng điểm môi trường tích lũy', 'Ranking by total environmental points accumulated',
 'global', 'points', 'all_time', 'trophy', 'general');

-- Insert Sample User Gamification Preferences
INSERT INTO user_gamification_preferences (
    user_id, achievement_notifications, show_achievement_progress,
    participate_in_leaderboards, daily_motivation_enabled,
    preferred_challenge_level, focus_environmental_achievements
) 
SELECT user_id, TRUE, TRUE, TRUE, TRUE, 'adaptive', TRUE 
FROM users 
WHERE user_type = 'individual' 
LIMIT 5;

-- ========================================
-- 17. VERIFICATION QUERIES
-- ========================================

-- Show Phase 19 table creation summary
SELECT 
    'Phase 19 Gamification Tables Created' as status,
    COUNT(*) as new_tables_created
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
AND (table_name LIKE '%achievement%' 
     OR table_name LIKE '%leaderboard%' 
     OR table_name LIKE '%badge%' 
     OR table_name LIKE '%streak%' 
     OR table_name LIKE '%gamification%'
     OR table_name LIKE '%seasonal%');

-- Show sample data counts
SELECT 'Achievement Categories' as item, COUNT(*) as count FROM achievement_categories
UNION ALL
SELECT 'Achievements', COUNT(*) FROM achievements_new
UNION ALL
SELECT 'User Levels', COUNT(*) FROM user_levels
UNION ALL
SELECT 'Badge Categories', COUNT(*) FROM badge_categories
UNION ALL
SELECT 'Leaderboards', COUNT(*) FROM leaderboards_new
UNION ALL
SELECT 'User Preferences', COUNT(*) FROM user_gamification_preferences;

-- Completion message
SELECT 
    'Phase 19: Achievements & Gamification System COMPLETED!' as message,
    NOW() as completed_at;
