-- Complete Phase 19: Missing Tables
-- ========================================

-- Set SQL mode to handle dates properly
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

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
-- INSERT SAMPLE DATA
-- ========================================

-- Sample Seasonal Challenge
INSERT IGNORE INTO seasonal_challenges (
    challenge_name, challenge_slug, title_vi, title_en, description_vi, description_en,
    challenge_type, environmental_theme, start_date, end_date,
    completion_criteria, completion_points, participation_points
) VALUES 
('earth_month_2025', 'earth-month-2025', 'Tháng Trái Đất 2025', 'Earth Month 2025',
 'Thử thách đặc biệt trong tháng môi trường thế giới', 'Special challenge during world environment month',
 'seasonal', 'general', '2025-06-01 00:00:00', '2025-06-30 23:59:59',
 '{"waste_reports": 15, "carbon_saved": 25, "quiz_completed": 5}', 500, 50),

('summer_green_challenge', 'summer-green-2025', 'Thử thách Xanh Mùa Hè', 'Summer Green Challenge',
 'Cùng nhau bảo vệ môi trường trong mùa hè 2025', 'Together protect environment in summer 2025',
 'seasonal', 'carbon', '2025-07-01 00:00:00', '2025-08-31 23:59:59',
 '{"carbon_saved": 100, "daily_activities": 30, "social_shares": 10}', 750, 75);

-- Reset SQL mode
SET SESSION sql_mode = DEFAULT;
