-- ========================================
-- PHASE 19: ACHIEVEMENTS & GAMIFICATION SYSTEM (CONTINUATION)
-- Environmental Platform Database
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. DROP EXISTING TABLES IF NEEDED AND RECREATE
-- ========================================

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS achievement_progress;
DROP TABLE IF EXISTS user_achievements_new;
DROP TABLE IF EXISTS achievement_rewards;
DROP TABLE IF EXISTS achievement_dependencies;
DROP TABLE IF EXISTS achievements_new;
DROP TABLE IF EXISTS achievement_categories;

-- ========================================
-- 2. ACHIEVEMENT CATEGORIES & TYPES
-- ========================================

CREATE TABLE achievement_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    category_display_name VARCHAR(150) NOT NULL,
    category_slug VARCHAR(100) UNIQUE NOT NULL,
    
    -- Localization
    display_name_vi VARCHAR(150) NOT NULL,
    display_name_en VARCHAR(150) NOT NULL,
    description TEXT,
    description_vi TEXT,
    description_en TEXT,
    
    -- Visual Elements
    icon_name VARCHAR(100),
    icon_url VARCHAR(255),
    color_primary VARCHAR(7) DEFAULT '#10b981',
    color_secondary VARCHAR(7) DEFAULT '#065f46',
    badge_template VARCHAR(255),
    
    -- Category Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'expert', 'legendary') DEFAULT 'beginner',
    
    -- Achievement Configuration
    max_achievements_per_user INT DEFAULT NULL,
    unlock_requirements JSON,
    category_points_multiplier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Environmental Context
    environmental_impact_category ENUM('carbon', 'waste', 'energy', 'water', 'transport', 'social', 'learning', 'general') DEFAULT 'general',
    sustainability_weight DECIMAL(3,2) DEFAULT 1.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active_sort (is_active, sort_order),
    INDEX idx_environmental_category (environmental_impact_category),
    INDEX idx_difficulty (difficulty_level),
    INDEX idx_featured (is_featured, sort_order)
) ENGINE=InnoDB;

-- ========================================
-- 3. ACHIEVEMENTS TABLE (ENHANCED)
-- ========================================

CREATE TABLE achievements_new (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    
    -- Basic Information
    achievement_name VARCHAR(150) NOT NULL,
    achievement_slug VARCHAR(150) UNIQUE NOT NULL,
    achievement_code VARCHAR(50) UNIQUE NOT NULL,
    
    -- Localization
    title_vi VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    unlock_message_vi TEXT,
    unlock_message_en TEXT,
    
    -- Achievement Type & Mechanics
    achievement_type ENUM('one_time', 'repeatable', 'progressive', 'streak', 'seasonal', 'hidden', 'special') DEFAULT 'one_time',
    trigger_type ENUM('manual', 'automatic', 'scheduled', 'event_based') DEFAULT 'automatic',
    trigger_events JSON, -- ['carbon_save', 'waste_classify', 'social_share', etc.]
    
    -- Unlock Criteria
    unlock_criteria JSON NOT NULL, -- Complex criteria with AND/OR logic
    progress_tracking JSON, -- How progress is calculated
    reset_criteria JSON, -- When progress resets (for repeatable achievements)
    
    -- Rewards & Points
    points_reward INT DEFAULT 0,
    experience_points INT DEFAULT 0,
    green_points INT DEFAULT 0,
    bonus_multiplier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Visual & UI
    icon_name VARCHAR(100),
    icon_url VARCHAR(255),
    badge_image_url VARCHAR(255),
    banner_image_url VARCHAR(255),
    animation_type VARCHAR(50),
    celebration_effect VARCHAR(50),
    
    -- Rarity & Difficulty
    rarity_level ENUM('common', 'uncommon', 'rare', 'epic', 'legendary', 'mythic') DEFAULT 'common',
    difficulty_rating TINYINT DEFAULT 1, -- 1-10
    estimated_time_hours INT DEFAULT NULL,
    completion_percentage DECIMAL(5,2) DEFAULT 0, -- % of users who have this
    
    -- Visibility & Access
    is_active BOOLEAN DEFAULT TRUE,
    is_hidden BOOLEAN DEFAULT FALSE,
    is_secret BOOLEAN DEFAULT FALSE,
    unlock_level_required INT DEFAULT 1,
    prerequisite_achievements JSON,
    
    -- Timing & Availability
    available_from TIMESTAMP NULL,
    available_until TIMESTAMP NULL,
    seasonal_event VARCHAR(100),
    time_limit_hours INT DEFAULT NULL,
    
    -- Environmental Impact
    environmental_category VARCHAR(50),
    carbon_impact_kg DECIMAL(10,2) DEFAULT 0,
    sustainability_score INT DEFAULT 0,
    eco_tip TEXT,
    related_actions JSON,
    
    -- Social Features
    is_shareable BOOLEAN DEFAULT TRUE,
    share_message_template TEXT,
    celebration_message TEXT,
    
    -- Analytics & Tracking
    total_unlocks INT DEFAULT 0,
    total_attempts INT DEFAULT 0,
    average_completion_time_hours DECIMAL(8,2) DEFAULT NULL,
    first_unlocked_at TIMESTAMP NULL,
    last_unlocked_at TIMESTAMP NULL,
    
    -- Metadata
    created_by INT,
    reviewed_by INT,
    review_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    review_notes TEXT,
    version INT DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES achievement_categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_category_active (category_id, is_active),
    INDEX idx_type_rarity (achievement_type, rarity_level),
    INDEX idx_difficulty (difficulty_rating, rarity_level),
    INDEX idx_environmental (environmental_category, sustainability_score),
    INDEX idx_availability (available_from, available_until, is_active),
    INDEX idx_completion_stats (completion_percentage, total_unlocks),
    FULLTEXT(achievement_name, title_vi, title_en, description_vi, description_en)
) ENGINE=InnoDB;

-- ========================================
-- 4. USER ACHIEVEMENTS (ENHANCED TRACKING)
-- ========================================

CREATE TABLE user_achievements_new (
    user_achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    -- Progress Tracking
    current_progress JSON, -- Current progress toward achievement
    max_progress JSON, -- Required progress for completion
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    milestone_progress JSON, -- Intermediate milestones reached
    
    -- Completion Status
    is_completed BOOLEAN DEFAULT FALSE,
    completion_date TIMESTAMP NULL,
    completion_method ENUM('automatic', 'manual', 'import', 'admin') DEFAULT 'automatic',
    
    -- Timing Information
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    first_progress_at TIMESTAMP NULL,
    last_progress_at TIMESTAMP NULL,
    estimated_completion_date TIMESTAMP NULL,
    time_spent_hours DECIMAL(8,2) DEFAULT 0,
    
    -- Repetition & Streaks (for repeatable achievements)
    completion_count INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    last_reset_at TIMESTAMP NULL,
    
    -- Rewards Claimed
    points_earned INT DEFAULT 0,
    experience_earned INT DEFAULT 0,
    green_points_earned INT DEFAULT 0,
    bonus_applied DECIMAL(3,2) DEFAULT 1.00,
    rewards_claimed_at TIMESTAMP NULL,
    
    -- Social & Sharing
    is_shared BOOLEAN DEFAULT FALSE,
    shared_at TIMESTAMP NULL,
    share_platforms JSON,
    celebration_viewed BOOLEAN DEFAULT FALSE,
    
    -- Visibility & Privacy
    is_public BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    show_on_profile BOOLEAN DEFAULT TRUE,
    
    -- Analytics Data
    unlock_session_id VARCHAR(100),
    unlock_device_type VARCHAR(50),
    unlock_source VARCHAR(100), -- where the achievement was triggered
    unlock_context JSON, -- additional context data
    
    -- Verification & Quality
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method VARCHAR(50),
    verification_data JSON,
    quality_score DECIMAL(3,2) DEFAULT 1.00,
    
    -- Notes & Comments
    user_notes TEXT,
    admin_notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements_new(achievement_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_completed (user_id, is_completed, completion_date DESC),
    INDEX idx_achievement_completion (achievement_id, is_completed, completion_date),
    INDEX idx_progress (user_id, progress_percentage DESC),
    INDEX idx_streaks (user_id, current_streak DESC, best_streak DESC),
    INDEX idx_public_featured (is_public, is_featured, completion_date DESC),
    INDEX idx_completion_time (completion_date, time_spent_hours)
) ENGINE=InnoDB;

-- ========================================
-- 5. ACHIEVEMENT DEPENDENCIES & CHAINS
-- ========================================

CREATE TABLE achievement_dependencies (
    dependency_id INT PRIMARY KEY AUTO_INCREMENT,
    achievement_id INT NOT NULL,
    prerequisite_achievement_id INT NOT NULL,
    dependency_type ENUM('required', 'optional', 'alternative', 'chain') DEFAULT 'required',
    
    -- Dependency Logic
    is_strict BOOLEAN DEFAULT TRUE, -- Must be completed exactly
    minimum_completion_count INT DEFAULT 1,
    required_streak INT DEFAULT NULL,
    time_window_hours INT DEFAULT NULL,
    
    -- Chain Information
    chain_name VARCHAR(100),
    chain_position INT DEFAULT 1,
    chain_total_steps INT DEFAULT 1,
    
    -- Unlock Benefits
    unlock_bonus_multiplier DECIMAL(3,2) DEFAULT 1.00,
    unlock_bonus_points INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (achievement_id) REFERENCES achievements_new(achievement_id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_achievement_id) REFERENCES achievements_new(achievement_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_achievement_prerequisite (achievement_id, prerequisite_achievement_id),
    INDEX idx_achievement_deps (achievement_id, dependency_type),
    INDEX idx_prerequisite (prerequisite_achievement_id, dependency_type),
    INDEX idx_chain (chain_name, chain_position)
) ENGINE=InnoDB;

-- ========================================
-- 6. ACHIEVEMENT PROGRESS TRACKING
-- ========================================

CREATE TABLE achievement_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    -- Progress Details
    progress_type ENUM('increment', 'decrement', 'set_value', 'milestone', 'reset') DEFAULT 'increment',
    progress_key VARCHAR(100) NOT NULL, -- which aspect of the achievement
    previous_value DECIMAL(15,4) DEFAULT 0,
    current_value DECIMAL(15,4) DEFAULT 0,
    change_amount DECIMAL(15,4) DEFAULT 0,
    
    -- Context Information
    trigger_source VARCHAR(100), -- what caused this progress
    trigger_id INT, -- ID of the triggering entity
    trigger_data JSON, -- additional context
    session_id VARCHAR(100),
    
    -- Environmental Context
    carbon_impact DECIMAL(10,3) DEFAULT 0,
    environmental_category VARCHAR(50),
    sustainability_points INT DEFAULT 0,
    
    -- Quality & Verification
    is_verified BOOLEAN DEFAULT TRUE,
    verification_method VARCHAR(50),
    quality_score DECIMAL(3,2) DEFAULT 1.00,
    
    -- Metadata
    notes TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements_new(achievement_id) ON DELETE CASCADE,
    
    INDEX idx_user_achievement_progress (user_id, achievement_id, recorded_at DESC),
    INDEX idx_progress_type (progress_type, recorded_at DESC),
    INDEX idx_trigger_source (trigger_source, trigger_id, recorded_at),
    INDEX idx_environmental (environmental_category, recorded_at),
    INDEX idx_session (session_id, recorded_at)
) ENGINE=InnoDB;

-- ========================================
-- 7. ACHIEVEMENT REWARDS SYSTEM
-- ========================================

CREATE TABLE achievement_rewards (
    reward_id INT PRIMARY KEY AUTO_INCREMENT,
    achievement_id INT NOT NULL,
    
    -- Reward Types
    reward_type ENUM('points', 'badge', 'title', 'voucher', 'item', 'access', 'feature', 'discount', 'custom') NOT NULL,
    reward_category VARCHAR(50),
    
    -- Reward Details
    reward_name VARCHAR(150) NOT NULL,
    reward_description TEXT,
    reward_value JSON, -- Flexible reward configuration
    
    -- Delivery & Timing
    delivery_method ENUM('automatic', 'manual', 'email', 'notification', 'claim') DEFAULT 'automatic',
    delivery_delay_hours INT DEFAULT 0,
    expiry_hours INT DEFAULT NULL,
    
    -- Conditions
    is_active BOOLEAN DEFAULT TRUE,
    level_requirement INT DEFAULT 1,
    one_time_only BOOLEAN DEFAULT TRUE,
    
    -- Tracking
    total_claimed INT DEFAULT 0,
    total_issued INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (achievement_id) REFERENCES achievements_new(achievement_id) ON DELETE CASCADE,
    
    INDEX idx_achievement_type (achievement_id, reward_type),
    INDEX idx_category_active (reward_category, is_active),
    INDEX idx_delivery (delivery_method, is_active)
) ENGINE=InnoDB;

-- ========================================
-- 8. ENHANCED LEADERBOARDS SYSTEM
-- ========================================

DROP TABLE IF EXISTS leaderboards_new;

CREATE TABLE leaderboards_new (
    leaderboard_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Information
    leaderboard_name VARCHAR(150) NOT NULL,
    leaderboard_slug VARCHAR(150) UNIQUE NOT NULL,
    display_name_vi VARCHAR(200) NOT NULL,
    display_name_en VARCHAR(200) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Leaderboard Type & Configuration
    leaderboard_type ENUM('global', 'regional', 'category', 'seasonal', 'event', 'custom') DEFAULT 'global',
    ranking_metric ENUM('points', 'achievements', 'carbon_saved', 'waste_classified', 'social_impact', 'custom') NOT NULL,
    ranking_calculation JSON, -- How ranks are calculated
    
    -- Time Period
    period_type ENUM('all_time', 'yearly', 'quarterly', 'monthly', 'weekly', 'daily', 'custom') DEFAULT 'all_time',
    period_start TIMESTAMP NULL,
    period_end TIMESTAMP NULL,
    reset_frequency ENUM('never', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'never',
    last_reset_at TIMESTAMP NULL,
    next_reset_at TIMESTAMP NULL,
    
    -- Filtering & Eligibility
    eligibility_criteria JSON,
    geographic_scope JSON, -- countries/regions included
    user_type_filter SET('individual', 'organization', 'business') DEFAULT 'individual',
    minimum_level INT DEFAULT 1,
    minimum_activity_days INT DEFAULT 1,
    
    -- Visual & UI
    icon_name VARCHAR(100),
    icon_url VARCHAR(255),
    background_image VARCHAR(255),
    color_scheme VARCHAR(7) DEFAULT '#10b981',
    
    -- Rewards & Recognition
    top_positions_rewarded INT DEFAULT 10,
    participation_rewards BOOLEAN DEFAULT FALSE,
    achievement_rewards JSON,
    
    -- Social Features
    is_public BOOLEAN DEFAULT TRUE,
    allow_sharing BOOLEAN DEFAULT TRUE,
    social_message_template TEXT,
    
    -- Display Settings
    max_entries_displayed INT DEFAULT 100,
    show_user_rank BOOLEAN DEFAULT TRUE,
    show_progress_charts BOOLEAN DEFAULT TRUE,
    show_historical_data BOOLEAN DEFAULT TRUE,
    
    -- Status & Moderation
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    requires_moderation BOOLEAN DEFAULT FALSE,
    
    -- Analytics
    total_participants INT DEFAULT 0,
    view_count INT DEFAULT 0,
    engagement_score DECIMAL(5,2) DEFAULT 0,
    
    -- Environmental Context
    environmental_focus VARCHAR(50),
    sustainability_weight DECIMAL(3,2) DEFAULT 1.00,
    carbon_tracking_enabled BOOLEAN DEFAULT FALSE,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_type_period (leaderboard_type, period_type, is_active),
    INDEX idx_featured_active (is_featured, is_active),
    INDEX idx_reset_schedule (reset_frequency, next_reset_at),
    INDEX idx_environmental (environmental_focus, sustainability_weight),
    FULLTEXT(leaderboard_name, display_name_vi, display_name_en, description_vi, description_en)
) ENGINE=InnoDB;

-- ========================================
-- 9. LEADERBOARD ENTRIES
-- ========================================

CREATE TABLE leaderboard_entries (
    entry_id INT PRIMARY KEY AUTO_INCREMENT,
    leaderboard_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Ranking Information
    current_rank INT NOT NULL,
    previous_rank INT DEFAULT NULL,
    rank_change INT DEFAULT 0,
    best_rank INT DEFAULT NULL,
    worst_rank INT DEFAULT NULL,
    
    -- Score Details
    current_score DECIMAL(15,4) NOT NULL,
    previous_score DECIMAL(15,4) DEFAULT 0,
    score_change DECIMAL(15,4) DEFAULT 0,
    score_breakdown JSON, -- Detailed score components
    
    -- Environmental Metrics
    carbon_saved_kg DECIMAL(10,2) DEFAULT 0,
    waste_classified_kg DECIMAL(10,2) DEFAULT 0,
    environmental_actions INT DEFAULT 0,
    sustainability_score INT DEFAULT 0,
    
    -- Participation Statistics
    streak_days INT DEFAULT 0,
    active_days INT DEFAULT 0,
    total_activities INT DEFAULT 0,
    achievement_count INT DEFAULT 0,
    
    -- Time Tracking
    first_entry_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_rank_change_at TIMESTAMP NULL,
    
    -- Performance Metrics
    average_daily_score DECIMAL(10,4) DEFAULT 0,
    peak_performance_date DATE NULL,
    improvement_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Social & Sharing
    is_public BOOLEAN DEFAULT TRUE,
    share_count INT DEFAULT 0,
    celebration_count INT DEFAULT 0,
    
    -- Quality & Verification
    is_verified BOOLEAN DEFAULT TRUE,
    verification_level ENUM('unverified', 'basic', 'enhanced', 'premium') DEFAULT 'basic',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (leaderboard_id) REFERENCES leaderboards_new(leaderboard_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_leaderboard_user (leaderboard_id, user_id),
    INDEX idx_leaderboard_rank (leaderboard_id, current_rank),
    INDEX idx_leaderboard_score (leaderboard_id, current_score DESC),
    INDEX idx_user_performance (user_id, current_score DESC, current_rank),
    INDEX idx_environmental_metrics (carbon_saved_kg DESC, waste_classified_kg DESC),
    INDEX idx_activity (last_activity_at DESC, active_days DESC)
) ENGINE=InnoDB;

-- ========================================
-- 10. GAMIFICATION BADGES SYSTEM
-- ========================================

CREATE TABLE badge_categories (
    badge_category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    display_name_vi VARCHAR(150) NOT NULL,
    display_name_en VARCHAR(150) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Visual
    icon_name VARCHAR(100),
    color_primary VARCHAR(7) DEFAULT '#10b981',
    sort_order INT DEFAULT 0,
    
    -- Settings
    is_active BOOLEAN DEFAULT TRUE,
    max_badges_per_user INT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE badges (
    badge_id INT PRIMARY KEY AUTO_INCREMENT,
    badge_category_id INT NOT NULL,
    
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
    badge_shape ENUM('circle', 'square', 'shield', 'star', 'diamond', 'custom') DEFAULT 'circle',
    animation_type VARCHAR(50),
    
    -- Badge Properties
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
    rarity_percentage DECIMAL(5,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (badge_category_id) REFERENCES badge_categories(badge_category_id) ON DELETE RESTRICT,
    
    INDEX idx_category_level (badge_category_id, badge_level),
    INDEX idx_rarity_active (rarity, is_active),
    FULLTEXT(badge_name, title_vi, title_en, description_vi, description_en)
) ENGINE=InnoDB;

CREATE TABLE user_badges (
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
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user_display (user_id, is_displayed, display_order),
    INDEX idx_badge_awards (badge_id, awarded_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- 11. USER LEVEL & EXPERIENCE SYSTEM
-- ========================================

CREATE TABLE user_levels (
    level_id INT PRIMARY KEY AUTO_INCREMENT,
    level_number INT UNIQUE NOT NULL,
    
    -- Experience Requirements
    experience_required INT NOT NULL,
    experience_from_previous INT DEFAULT 0,
    
    -- Level Information
    level_name_vi VARCHAR(100) NOT NULL,
    level_name_en VARCHAR(100) NOT NULL,
    level_description_vi TEXT,
    level_description_en TEXT,
    
    -- Visual Elements
    level_icon VARCHAR(100),
    level_color VARCHAR(7) DEFAULT '#10b981',
    level_badge_url VARCHAR(255),
    
    -- Rewards & Bonuses
    green_points_bonus INT DEFAULT 0,
    unlock_features JSON,
    level_rewards JSON,
    
    -- Environmental Classification
    environmental_tier ENUM('newcomer', 'explorer', 'advocate', 'champion', 'legend', 'master') DEFAULT 'newcomer',
    sustainability_multiplier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Privileges
    max_daily_activities INT DEFAULT 10,
    max_social_shares INT DEFAULT 5,
    advanced_features_unlocked BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_level_number (level_number),
    INDEX idx_experience (experience_required),
    INDEX idx_environmental_tier (environmental_tier)
) ENGINE=InnoDB;

-- Continue with remaining tables...
