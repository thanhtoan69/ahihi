-- ========================================
-- PHASE 19: ACHIEVEMENTS & GAMIFICATION (SIMPLIFIED)
-- Environmental Platform Database
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. ENHANCED ACHIEVEMENTS TABLE
-- ========================================

-- Check if achievements table exists and rename if needed
CREATE TABLE IF NOT EXISTS achievements_enhanced (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Information
    achievement_name VARCHAR(150) NOT NULL,
    achievement_slug VARCHAR(150) UNIQUE NOT NULL,
    achievement_code VARCHAR(50) UNIQUE NOT NULL,
    
    -- Localization
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Achievement Type & Mechanics
    achievement_type ENUM('one_time', 'repeatable', 'progressive', 'streak', 'seasonal', 'hidden', 'special') DEFAULT 'one_time',
    trigger_type ENUM('manual', 'automatic', 'scheduled', 'event_based') DEFAULT 'automatic',
    trigger_events JSON,
    
    -- Unlock Criteria
    unlock_criteria JSON NOT NULL,
    progress_tracking JSON,
    
    -- Rewards & Points
    points_reward INT DEFAULT 0,
    experience_points INT DEFAULT 0,
    green_points INT DEFAULT 0,
    
    -- Visual & UI
    icon_name VARCHAR(100),
    badge_image_url VARCHAR(255),
    
    -- Rarity & Difficulty
    rarity_level ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    difficulty_rating TINYINT DEFAULT 1,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_hidden BOOLEAN DEFAULT FALSE,
    
    -- Environmental Context
    environmental_category VARCHAR(50),
    carbon_impact_kg DECIMAL(10,2) DEFAULT 0,
    
    -- Analytics
    total_unlocks INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_rarity (achievement_type, rarity_level),
    INDEX idx_environmental (environmental_category),
    INDEX idx_active (is_active, is_hidden),
    FULLTEXT(achievement_name, title_vi, title_en)
) ENGINE=InnoDB;

-- ========================================
-- 2. USER ACHIEVEMENTS PROGRESS
-- ========================================

CREATE TABLE IF NOT EXISTS user_achievements_enhanced (
    user_achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    -- Progress Tracking
    current_progress JSON,
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- Completion Status
    is_completed BOOLEAN DEFAULT FALSE,
    completion_date TIMESTAMP NULL,
    
    -- Timing
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_progress_at TIMESTAMP NULL,
    
    -- Streaks (for repeatable achievements)
    completion_count INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    
    -- Rewards
    points_earned INT DEFAULT 0,
    experience_earned INT DEFAULT 0,
    green_points_earned INT DEFAULT 0,
    
    -- Social
    is_shared BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_completed (user_id, is_completed, completion_date DESC),
    INDEX idx_progress (user_id, progress_percentage DESC)
) ENGINE=InnoDB;

-- ========================================
-- 3. ENHANCED LEADERBOARDS
-- ========================================

CREATE TABLE IF NOT EXISTS leaderboards_enhanced (
    leaderboard_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Information
    leaderboard_name VARCHAR(150) NOT NULL,
    leaderboard_slug VARCHAR(150) UNIQUE NOT NULL,
    display_name_vi VARCHAR(200) NOT NULL,
    display_name_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Configuration
    leaderboard_type ENUM('global', 'regional', 'category', 'seasonal', 'event') DEFAULT 'global',
    ranking_metric ENUM('points', 'achievements', 'carbon_saved', 'waste_classified', 'social_impact') NOT NULL,
    
    -- Time Period
    period_type ENUM('all_time', 'yearly', 'monthly', 'weekly', 'daily') DEFAULT 'all_time',
    period_start TIMESTAMP NULL,
    period_end TIMESTAMP NULL,
    
    -- Display Settings
    max_entries_displayed INT DEFAULT 100,
    is_public BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Environmental Focus
    environmental_focus VARCHAR(50),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_period (leaderboard_type, period_type),
    INDEX idx_active (is_active, is_public),
    FULLTEXT(leaderboard_name, display_name_vi, display_name_en)
) ENGINE=InnoDB;

-- ========================================
-- 4. LEADERBOARD ENTRIES
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
    current_score DECIMAL(15,4) NOT NULL,
    previous_score DECIMAL(15,4) DEFAULT 0,
    score_change DECIMAL(15,4) DEFAULT 0,
    
    -- Environmental Metrics
    carbon_saved_kg DECIMAL(10,2) DEFAULT 0,
    waste_classified_kg DECIMAL(10,2) DEFAULT 0,
    environmental_actions INT DEFAULT 0,
    
    -- Statistics
    streak_days INT DEFAULT 0,
    achievement_count INT DEFAULT 0,
    
    -- Timing
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (leaderboard_id) REFERENCES leaderboards_enhanced(leaderboard_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_leaderboard_user (leaderboard_id, user_id),
    INDEX idx_leaderboard_rank (leaderboard_id, current_rank),
    INDEX idx_leaderboard_score (leaderboard_id, current_score DESC)
) ENGINE=InnoDB;

-- ========================================
-- 5. BADGES SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS badges_system (
    badge_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Information
    badge_name VARCHAR(150) NOT NULL,
    badge_slug VARCHAR(150) UNIQUE NOT NULL,
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Visual Design
    badge_image_url VARCHAR(255),
    badge_icon VARCHAR(100),
    badge_color VARCHAR(7) DEFAULT '#10b981',
    
    -- Properties
    badge_level ENUM('bronze', 'silver', 'gold', 'platinum', 'diamond') DEFAULT 'bronze',
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    points_value INT DEFAULT 0,
    
    -- Unlock Criteria
    unlock_criteria JSON NOT NULL,
    auto_award BOOLEAN DEFAULT TRUE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_hidden BOOLEAN DEFAULT FALSE,
    
    -- Statistics
    total_awarded INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_level_rarity (badge_level, rarity),
    INDEX idx_active (is_active, is_hidden),
    FULLTEXT(badge_name, title_vi, title_en)
) ENGINE=InnoDB;

-- ========================================
-- 6. USER BADGES
-- ========================================

CREATE TABLE IF NOT EXISTS user_badges_enhanced (
    user_badge_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    
    -- Award Information
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    awarded_by ENUM('system', 'admin', 'achievement', 'event') DEFAULT 'system',
    award_reason TEXT,
    
    -- Display Settings
    is_displayed BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges_system(badge_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user_display (user_id, is_displayed, display_order),
    INDEX idx_badge_awards (badge_id, awarded_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- 7. USER STREAKS ENHANCED
-- ========================================

CREATE TABLE IF NOT EXISTS user_streaks_gamification (
    streak_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Streak Type
    streak_type ENUM('login', 'activity', 'carbon_save', 'waste_classify', 'social_share', 'quiz_complete') NOT NULL,
    
    -- Current Streak
    current_streak INT DEFAULT 0,
    current_streak_start_date DATE NULL,
    last_activity_date DATE NULL,
    
    -- Records
    best_streak INT DEFAULT 0,
    best_streak_start_date DATE NULL,
    best_streak_end_date DATE NULL,
    
    -- Rewards
    streak_multiplier DECIMAL(3,2) DEFAULT 1.00,
    points_earned INT DEFAULT 0,
    
    -- Environmental Impact
    environmental_impact_total DECIMAL(10,2) DEFAULT 0,
    
    -- Social
    is_public BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_streak_type (user_id, streak_type),
    INDEX idx_current_streak (user_id, current_streak DESC),
    INDEX idx_best_streak (user_id, best_streak DESC),
    INDEX idx_last_activity (last_activity_date DESC)
) ENGINE=InnoDB;

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
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- Environmental Goals
    target_carbon_reduction DECIMAL(15,2) DEFAULT 0,
    target_waste_reduction DECIMAL(15,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active_dates (is_active, start_date, end_date),
    INDEX idx_theme_type (environmental_theme, challenge_type),
    FULLTEXT(challenge_name, title_vi, title_en)
) ENGINE=InnoDB;

-- ========================================
-- 9. CHALLENGE PARTICIPATION
-- ========================================

CREATE TABLE IF NOT EXISTS challenge_participation (
    participation_id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Participation Status
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'withdrawn') DEFAULT 'active',
    
    -- Progress
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    activities_completed INT DEFAULT 0,
    
    -- Performance
    points_earned INT DEFAULT 0,
    rank_position INT DEFAULT NULL,
    environmental_contribution DECIMAL(10,2) DEFAULT 0,
    
    -- Completion
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (challenge_id) REFERENCES seasonal_challenges(challenge_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_challenge_user (challenge_id, user_id),
    INDEX idx_challenge_status (challenge_id, status),
    INDEX idx_user_challenges (user_id, joined_at DESC),
    INDEX idx_performance (challenge_id, points_earned DESC)
) ENGINE=InnoDB;

-- ========================================
-- 10. INSERT SAMPLE DATA
-- ========================================

-- Insert sample achievements
INSERT INTO achievements_enhanced (
    achievement_name, achievement_slug, achievement_code,
    title_vi, title_en, description_vi, description_en,
    achievement_type, trigger_events, unlock_criteria,
    points_reward, green_points, rarity_level, environmental_category
) VALUES
('First Steps', 'first-steps', 'FIRST_STEPS',
 'Những bước đầu tiên', 'First Steps',
 'Hoàn thành đăng ký tài khoản', 'Complete account registration',
 'one_time', '["registration"]', 
 '{"event": "registration", "value": 1}',
 50, 25, 'common', 'general'),

('Carbon Saver Bronze', 'carbon-saver-bronze', 'CARBON_BRONZE',
 'Tiết kiệm Carbon đồng', 'Carbon Saver Bronze',
 'Tiết kiệm 10kg CO2', 'Save 10kg CO2',
 'progressive', '["carbon_save"]',
 '{"event": "carbon_save", "value": 10}',
 100, 50, 'common', 'carbon'),

('Waste Classifier', 'waste-classifier', 'WASTE_CLASS',
 'Chuyên gia phân loại', 'Waste Classifier',
 'Phân loại đúng 20 loại rác', 'Correctly classify 20 waste items',
 'progressive', '["waste_classification"]',
 '{"event": "waste_classification", "value": 20}',
 150, 75, 'uncommon', 'waste'),

('Social Green Advocate', 'social-advocate', 'SOCIAL_ADV',
 'Nhà vận động xanh', 'Social Green Advocate',
 'Chia sẻ 10 bài viết môi trường', 'Share 10 environmental articles',
 'progressive', '["social_sharing"]',
 '{"event": "social_sharing", "value": 10}',
 120, 60, 'uncommon', 'social'),

('Quiz Champion', 'quiz-champion', 'QUIZ_CHAMP',
 'Nhà vô địch câu hỏi', 'Quiz Champion',
 'Hoàn thành 15 quiz với điểm cao', 'Complete 15 quizzes with high scores',
 'progressive', '["quiz_complete"]',
 '{"event": "quiz_complete", "value": 15, "min_score": 80}',
 200, 100, 'rare', 'learning');

-- Insert sample leaderboards
INSERT INTO leaderboards_enhanced (
    leaderboard_name, leaderboard_slug, display_name_vi, display_name_en,
    description_vi, description_en, leaderboard_type, ranking_metric,
    period_type, environmental_focus
) VALUES
('Global Green Points', 'global-green-points',
 'Bảng xếp hạng điểm xanh toàn cầu', 'Global Green Points Leaderboard',
 'Xếp hạng người dùng theo tổng điểm xanh', 'Ranking users by total green points',
 'global', 'points', 'monthly', 'general'),

('Carbon Heroes', 'carbon-heroes',
 'Anh hùng Carbon', 'Carbon Heroes',
 'Những người tiết kiệm carbon nhiều nhất', 'Top carbon savers',
 'global', 'carbon_saved', 'weekly', 'carbon'),

('Waste Warriors', 'waste-warriors',
 'Chiến binh rác thải', 'Waste Warriors',
 'Chuyên gia phân loại rác hàng đầu', 'Top waste classification experts',
 'global', 'waste_classified', 'monthly', 'waste');

-- Insert sample badges
INSERT INTO badges_system (
    badge_name, badge_slug, title_vi, title_en,
    description_vi, description_en, badge_level, rarity,
    unlock_criteria, points_value
) VALUES
('Green Starter', 'green-starter',
 'Người khởi đầu xanh', 'Green Starter',
 'Huy hiệu cho người mới bắt đầu', 'Badge for newcomers',
 'bronze', 'common',
 '{"achievements": ["first-steps"]}', 25),

('Carbon Guardian', 'carbon-guardian',
 'Người bảo vệ Carbon', 'Carbon Guardian',
 'Huy hiệu tiết kiệm carbon', 'Carbon saving badge',
 'silver', 'uncommon',
 '{"carbon_saved": 50}', 75),

('Eco Master', 'eco-master',
 'Bậc thầy sinh thái', 'Eco Master',
 'Huy hiệu chuyên gia môi trường', 'Environmental expert badge',
 'gold', 'rare',
 '{"achievements_count": 10, "level": 5}', 200);

-- Insert sample seasonal challenge
INSERT INTO seasonal_challenges (
    challenge_name, challenge_slug, title_vi, title_en,
    description_vi, description_en, challenge_type, environmental_theme,
    start_date, end_date, completion_points, participation_points,
    target_carbon_reduction
) VALUES
('June Green Month', 'june-green-2025',
 'Tháng xanh tháng 6', 'June Green Month',
 'Thử thách môi trường tháng 6 năm 2025', 'June 2025 environmental challenge',
 'monthly', 'general',
 '2025-06-01 00:00:00', '2025-06-30 23:59:59',
 500, 50, 1000.00);

-- ========================================
-- VERIFICATION & COMPLETION
-- ========================================

-- Count new gamification tables
SELECT 
    'Phase 19 Gamification Tables Created' as status,
    COUNT(*) as new_tables
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
AND (table_name LIKE '%enhanced%' 
     OR table_name LIKE '%gamification%' 
     OR table_name LIKE '%challenge%'
     OR table_name LIKE '%badges_system%');

-- Show sample data
SELECT 'Sample Achievements' as item, COUNT(*) as count FROM achievements_enhanced
UNION ALL
SELECT 'Sample Leaderboards', COUNT(*) FROM leaderboards_enhanced
UNION ALL
SELECT 'Sample Badges', COUNT(*) FROM badges_system
UNION ALL
SELECT 'Sample Challenges', COUNT(*) FROM seasonal_challenges;

SELECT 'Phase 19: Achievements & Gamification COMPLETED!' as message;
