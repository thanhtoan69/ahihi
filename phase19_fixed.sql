-- Phase 19: Achievements & Gamification - Fixed Version
-- Environmental Platform Database
-- ========================================

-- Set SQL mode to handle dates properly
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ========================================
-- 1. ACHIEVEMENTS ENHANCED
-- ========================================

CREATE TABLE IF NOT EXISTS achievements_enhanced (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Information
    achievement_key VARCHAR(100) UNIQUE NOT NULL,
    category_id INT NOT NULL,
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    instruction_vi TEXT,
    instruction_en TEXT,
    
    -- Achievement Properties
    achievement_type ENUM('one_time', 'repeatable', 'progressive') DEFAULT 'one_time',
    difficulty ENUM('easy', 'medium', 'hard', 'expert') DEFAULT 'medium',
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    
    -- Unlock Criteria (JSON format for flexibility)
    unlock_criteria JSON NOT NULL,
    
    -- Environmental Context
    environmental_category ENUM('carbon', 'waste', 'energy', 'water', 'social', 'learning') DEFAULT 'general',
    carbon_impact_threshold DECIMAL(10,2) DEFAULT NULL,
    waste_items_threshold INT DEFAULT NULL,
    
    -- Rewards
    points_reward INT DEFAULT 100,
    experience_reward INT DEFAULT 50,
    green_points_reward INT DEFAULT 25,
    badge_reward VARCHAR(100) DEFAULT NULL,
    
    -- Visibility & Access
    is_hidden BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    min_user_level INT DEFAULT 1,
    prerequisite_achievements JSON DEFAULT NULL,
    
    -- Metadata
    total_earned INT DEFAULT 0,
    completion_rate DECIMAL(5,2) DEFAULT 0.00,
    icon_url VARCHAR(500) DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_category (category_id),
    INDEX idx_type_difficulty (achievement_type, difficulty),
    INDEX idx_environmental (environmental_category),
    INDEX idx_active_featured (is_active, is_featured),
    INDEX idx_completion_rate (completion_rate),
    
    FOREIGN KEY (category_id) REFERENCES achievement_categories(category_id) ON DELETE CASCADE
);

-- ========================================
-- 2. USER ACHIEVEMENTS ENHANCED
-- ========================================

CREATE TABLE IF NOT EXISTS user_achievements_enhanced (
    user_achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    -- Progress Tracking
    current_progress DECIMAL(10,2) DEFAULT 0,
    target_progress DECIMAL(10,2) NOT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Completion Details
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    completion_count INT DEFAULT 0, -- For repeatable achievements
    
    -- Streak Information
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    last_streak_date DATE NULL DEFAULT NULL,
    
    -- Milestones (JSON for flexible milestone tracking)
    milestones_reached JSON DEFAULT NULL,
    milestone_rewards_claimed JSON DEFAULT NULL,
    
    -- Rewards
    total_points_earned INT DEFAULT 0,
    total_experience_earned INT DEFAULT 0,
    total_green_points_earned INT DEFAULT 0,
    
    -- Social Features
    is_showcased BOOLEAN DEFAULT FALSE,
    shared_count INT DEFAULT 0,
    celebration_shown BOOLEAN DEFAULT FALSE,
    
    -- Environmental Impact
    carbon_saved DECIMAL(10,2) DEFAULT 0,
    waste_items_processed INT DEFAULT 0,
    environmental_score DECIMAL(8,2) DEFAULT 0,
    
    -- Metadata
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_achievement (user_id, achievement_id),
    INDEX idx_completed (is_completed, completed_at),
    INDEX idx_progress (progress_percentage),
    INDEX idx_showcased (is_showcased),
    INDEX idx_environmental_impact (carbon_saved, waste_items_processed),
    
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements_enhanced(achievement_id) ON DELETE CASCADE
);

-- ========================================
-- 3. LEADERBOARDS ENHANCED
-- ========================================

CREATE TABLE IF NOT EXISTS leaderboards_enhanced (
    leaderboard_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Leaderboard Information
    leaderboard_name VARCHAR(150) NOT NULL,
    leaderboard_slug VARCHAR(150) UNIQUE NOT NULL,
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Type & Category
    leaderboard_type ENUM('global', 'regional', 'local', 'seasonal', 'challenge') DEFAULT 'global',
    metric_type ENUM('points', 'achievements', 'carbon_saved', 'waste_processed', 'streaks', 'green_points') DEFAULT 'points',
    
    -- Environmental Focus
    environmental_category ENUM('carbon', 'waste', 'energy', 'water', 'social', 'overall') DEFAULT 'overall',
    
    -- Time Period
    period_type ENUM('all_time', 'yearly', 'monthly', 'weekly', 'daily') DEFAULT 'all_time',
    reset_schedule ENUM('never', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'never',
    last_reset_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Geographic Scope
    region_code VARCHAR(10) DEFAULT NULL,
    city_name VARCHAR(100) DEFAULT NULL,
    
    -- Display Settings
    max_entries INT DEFAULT 100,
    min_score DECIMAL(10,2) DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Rewards
    top_1_reward JSON DEFAULT NULL,
    top_3_reward JSON DEFAULT NULL,
    top_10_reward JSON DEFAULT NULL,
    participation_reward JSON DEFAULT NULL,
    
    -- Metadata
    total_participants INT DEFAULT 0,
    icon_url VARCHAR(500) DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_type_category (leaderboard_type, environmental_category),
    INDEX idx_active_public (is_active, is_public),
    INDEX idx_period_reset (period_type, reset_schedule),
    INDEX idx_region (region_code, city_name)
);

-- ========================================
-- 4. LEADERBOARD ENTRIES ENHANCED
-- ========================================

CREATE TABLE IF NOT EXISTS leaderboard_entries_enhanced (
    entry_id INT PRIMARY KEY AUTO_INCREMENT,
    leaderboard_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Ranking Information
    current_rank INT NOT NULL,
    previous_rank INT DEFAULT NULL,
    rank_change INT DEFAULT 0,
    
    -- Score Details
    current_score DECIMAL(12,2) NOT NULL,
    previous_score DECIMAL(12,2) DEFAULT 0,
    score_change DECIMAL(12,2) DEFAULT 0,
    
    -- Environmental Metrics
    carbon_saved DECIMAL(10,2) DEFAULT 0,
    waste_items_processed INT DEFAULT 0,
    green_points_earned INT DEFAULT 0,
    achievements_unlocked INT DEFAULT 0,
    
    -- Performance Tracking
    consistency_score DECIMAL(5,2) DEFAULT 0,
    participation_days INT DEFAULT 0,
    streak_bonus DECIMAL(8,2) DEFAULT 0,
    
    -- Trend Analysis
    weekly_average DECIMAL(10,2) DEFAULT 0,
    monthly_average DECIMAL(10,2) DEFAULT 0,
    growth_rate DECIMAL(6,2) DEFAULT 0,
    
    -- Social Features
    is_highlighted BOOLEAN DEFAULT FALSE,
    shared_achievement BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    entry_date DATE NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_leaderboard_rank (leaderboard_id, current_rank),
    INDEX idx_user_performance (user_id, current_score),
    INDEX idx_environmental_metrics (carbon_saved, waste_items_processed),
    INDEX idx_entry_date (entry_date),
    INDEX idx_highlighted (is_highlighted),
    
    UNIQUE KEY unique_leaderboard_user_date (leaderboard_id, user_id, entry_date),
    FOREIGN KEY (leaderboard_id) REFERENCES leaderboards_enhanced(leaderboard_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================================
-- 5. BADGES SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS badges_system (
    badge_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Badge Information
    badge_name VARCHAR(100) UNIQUE NOT NULL,
    badge_slug VARCHAR(100) UNIQUE NOT NULL,
    title_vi VARCHAR(150) NOT NULL,
    title_en VARCHAR(150) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Badge Properties
    badge_category ENUM('achievement', 'milestone', 'special', 'seasonal', 'environmental') DEFAULT 'achievement',
    rarity ENUM('bronze', 'silver', 'gold', 'platinum', 'diamond') DEFAULT 'bronze',
    badge_type ENUM('single', 'tiered', 'collection') DEFAULT 'single',
    
    -- Visual Properties
    icon_url VARCHAR(500) NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    color_hex VARCHAR(7) DEFAULT '#888888',
    animation_type ENUM('none', 'glow', 'pulse', 'sparkle') DEFAULT 'none',
    
    -- Requirements
    unlock_criteria JSON NOT NULL,
    prerequisite_badges JSON DEFAULT NULL,
    
    -- Environmental Context
    environmental_impact DECIMAL(10,2) DEFAULT 0,
    carbon_threshold DECIMAL(10,2) DEFAULT NULL,
    
    -- Statistics
    total_awarded INT DEFAULT 0,
    rarity_score DECIMAL(5,2) DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_seasonal BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_category_rarity (badge_category, rarity),
    INDEX idx_active_featured (is_active, is_featured),
    INDEX idx_rarity_score (rarity_score),
    INDEX idx_seasonal (is_seasonal)
);

-- ========================================
-- 6. USER BADGES ENHANCED
-- ========================================

CREATE TABLE IF NOT EXISTS user_badges_enhanced (
    user_badge_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    
    -- Award Details
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    awarded_for VARCHAR(200) DEFAULT NULL, -- Specific achievement/action
    
    -- Progress for Tiered Badges
    tier_level INT DEFAULT 1,
    tier_progress DECIMAL(10,2) DEFAULT 0,
    max_tier_level INT DEFAULT 1,
    
    -- Display Preferences
    is_displayed BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    
    -- Social Features
    is_showcased BOOLEAN DEFAULT FALSE,
    share_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    
    -- Environmental Impact
    environmental_contribution DECIMAL(10,2) DEFAULT 0,
    impact_description TEXT DEFAULT NULL,
    
    -- Timestamps
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_badges (user_id, awarded_at),
    INDEX idx_displayed (is_displayed, display_order),
    INDEX idx_showcased (is_showcased),
    INDEX idx_primary (is_primary),
    
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges_system(badge_id) ON DELETE CASCADE
);

-- ========================================
-- 7. USER STREAKS GAMIFICATION
-- ========================================

CREATE TABLE IF NOT EXISTS user_streaks_gamification (
    streak_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Streak Type
    streak_type ENUM('daily_login', 'waste_disposal', 'quiz_participation', 'carbon_tracking', 'social_sharing', 'learning') NOT NULL,
    
    -- Current Streak
    current_streak INT DEFAULT 0,
    current_streak_start_date DATE NULL DEFAULT NULL,
    last_activity_date DATE NULL DEFAULT NULL,
    
    -- Records
    best_streak INT DEFAULT 0,
    best_streak_start_date DATE NULL DEFAULT NULL,
    best_streak_end_date DATE NULL DEFAULT NULL,
    
    -- Rewards
    streak_multiplier DECIMAL(3,2) DEFAULT 1.00,
    points_earned INT DEFAULT 0,
    
    -- Environmental Impact
    environmental_impact_total DECIMAL(10,2) DEFAULT 0,
    
    -- Streak Mechanics
    freeze_cards_available INT DEFAULT 3,
    freeze_cards_used INT DEFAULT 0,
    last_freeze_used DATE NULL DEFAULT NULL,
    
    -- Milestones
    milestones_reached JSON DEFAULT NULL,
    next_milestone INT DEFAULT 7, -- Next milestone target
    
    -- Statistics
    total_activities INT DEFAULT 0,
    total_days_active INT DEFAULT 0,
    consistency_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_type (user_id, streak_type),
    INDEX idx_current_streak (current_streak),
    INDEX idx_best_streak (best_streak),
    INDEX idx_last_activity (last_activity_date),
    
    UNIQUE KEY unique_user_streak_type (user_id, streak_type),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================================
-- 8. SEASONAL CHALLENGES
-- ========================================

CREATE TABLE IF NOT EXISTS seasonal_challenges (
    challenge_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Challenge Information
    challenge_name VARCHAR(150) NOT NULL,
    challenge_slug VARCHAR(150) UNIQUE NOT NULL,
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Type & Category
    challenge_type ENUM('seasonal', 'monthly', 'weekly', 'special_event') DEFAULT 'monthly',
    environmental_theme ENUM('carbon', 'waste', 'energy', 'water', 'general') DEFAULT 'general',
    
    -- Timing
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    
    -- Participation
    max_participants INT DEFAULT NULL,
    current_participants INT DEFAULT 0,
    
    -- Rewards
    completion_points INT DEFAULT 100,
    participation_points INT DEFAULT 25,
    special_badge_id INT DEFAULT NULL,
    
    -- Requirements
    completion_criteria JSON NOT NULL,
    difficulty_level ENUM('easy', 'medium', 'hard', 'expert') DEFAULT 'medium',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    registration_required BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    banner_image_url VARCHAR(500) DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_active_dates (is_active, start_date, end_date),
    INDEX idx_theme_type (environmental_theme, challenge_type),
    INDEX idx_featured (is_featured),
    
    FOREIGN KEY (special_badge_id) REFERENCES badges_system(badge_id) ON DELETE SET NULL
);

-- ========================================
-- 9. CHALLENGE PARTICIPATION
-- ========================================

CREATE TABLE IF NOT EXISTS challenge_participation (
    participation_id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Registration
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Progress
    current_progress DECIMAL(10,2) DEFAULT 0,
    target_progress DECIMAL(10,2) NOT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Completion
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    completion_rank INT DEFAULT NULL,
    
    -- Rewards
    points_earned INT DEFAULT 0,
    badge_earned BOOLEAN DEFAULT FALSE,
    special_rewards JSON DEFAULT NULL,
    
    -- Performance
    daily_progress JSON DEFAULT NULL, -- Track daily achievements
    best_day_performance DECIMAL(10,2) DEFAULT 0,
    consistency_score DECIMAL(5,2) DEFAULT 0,
    
    -- Environmental Impact
    environmental_contribution DECIMAL(10,2) DEFAULT 0,
    carbon_saved DECIMAL(8,2) DEFAULT 0,
    waste_processed INT DEFAULT 0,
    
    -- Social Features
    shared_progress BOOLEAN DEFAULT FALSE,
    encouraged_others INT DEFAULT 0,
    
    -- Timestamps
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_challenge_user (challenge_id, user_id),
    INDEX idx_completed (is_completed, completed_at),
    INDEX idx_progress (progress_percentage),
    INDEX idx_rank (completion_rank),
    
    UNIQUE KEY unique_challenge_participation (challenge_id, user_id),
    FOREIGN KEY (challenge_id) REFERENCES seasonal_challenges(challenge_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Sample Enhanced Achievements
INSERT IGNORE INTO achievements_enhanced (
    achievement_key, category_id, title_vi, title_en, description_vi, description_en,
    achievement_type, difficulty, rarity, unlock_criteria, environmental_category,
    points_reward, experience_reward, green_points_reward
) VALUES 
('first_waste_report', 1, 'Báo cáo đầu tiên', 'First Report', 
 'Gửi báo cáo chất thải đầu tiên của bạn', 'Submit your first waste report',
 'one_time', 'easy', 'common', 
 '{"action": "waste_report", "count": 1}', 'waste', 100, 50, 25),
 
('carbon_saver_bronze', 1, 'Tiết kiệm Carbon Đồng', 'Carbon Saver Bronze',
 'Tiết kiệm 50kg CO2 thông qua các hoạt động xanh', 'Save 50kg CO2 through green activities',
 'one_time', 'medium', 'uncommon',
 '{"action": "carbon_saved", "threshold": 50}', 'carbon', 200, 100, 50),

('quiz_master', 2, 'Bậc thầy Quiz', 'Quiz Master',
 'Hoàn thành 20 quiz về môi trường', 'Complete 20 environmental quizzes',
 'one_time', 'medium', 'rare',
 '{"action": "quiz_completed", "count": 20}', 'learning', 300, 150, 75),

('social_advocate', 3, 'Nhà vận động xã hội', 'Social Advocate',
 'Chia sẻ 10 bài viết về môi trường', 'Share 10 environmental posts',
 'one_time', 'easy', 'common',
 '{"action": "social_share", "count": 10}', 'social', 150, 75, 35);

-- Sample Badges
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
 '/images/badges/learning-champion.png', '#CD7F32');

-- Sample Seasonal Challenge
INSERT IGNORE INTO seasonal_challenges (
    challenge_name, challenge_slug, title_vi, title_en, description_vi, description_en,
    challenge_type, environmental_theme, start_date, end_date,
    completion_criteria, completion_points, participation_points
) VALUES 
('earth_month_2025', 'earth-month-2025', 'Tháng Trái Đất 2025', 'Earth Month 2025',
 'Thử thách đặc biệt trong tháng môi trường thế giới', 'Special challenge during world environment month',
 'seasonal', 'general', '2025-06-01 00:00:00', '2025-06-30 23:59:59',
 '{"waste_reports": 15, "carbon_saved": 25, "quiz_completed": 5}', 500, 50);

-- Reset SQL mode back to default
SET SESSION sql_mode = DEFAULT;
