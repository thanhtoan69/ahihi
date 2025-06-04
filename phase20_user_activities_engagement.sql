-- ========================================
-- PHASE 20: USER ACTIVITIES & ENGAGEMENT SYSTEM
-- Environmental Platform Database
-- ========================================
-- Features:
-- - Comprehensive user activity tracking
-- - Advanced streak system with bonus calculations
-- - Multi-dimensional engagement scoring
-- - Activity categorization and analytics
-- - Habit building mechanics
-- - Social engagement tracking
-- - Environmental impact correlation
-- ========================================

-- Set SQL mode for compatibility
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ========================================
-- 1. USER ACTIVITIES COMPREHENSIVE TRACKING
-- ========================================

CREATE TABLE IF NOT EXISTS user_activities_comprehensive (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Activity Classification
    activity_type ENUM(
        'login', 'logout', 'content_view', 'content_create', 'content_share',
        'waste_report', 'waste_classification', 'carbon_tracking', 'quiz_participation',
        'forum_post', 'forum_reply', 'event_registration', 'event_attendance',
        'product_view', 'product_purchase', 'product_review', 'exchange_post',
        'donation', 'petition_sign', 'achievement_unlock', 'badge_earn',
        'streak_maintain', 'social_interaction', 'learning_completion', 'challenge_participation'
    ) NOT NULL,
    
    activity_category ENUM(
        'authentication', 'content', 'environmental', 'social', 'learning', 
        'commerce', 'community', 'achievement', 'engagement'
    ) NOT NULL,
    
    activity_subcategory VARCHAR(100) DEFAULT NULL,
    
    -- Activity Details
    activity_name VARCHAR(200) NOT NULL,
    activity_description TEXT DEFAULT NULL,
    
    -- Context & Metadata
    activity_context JSON DEFAULT NULL, -- Store detailed activity data
    related_entity_type ENUM(
        'article', 'product', 'event', 'forum_topic', 'quiz', 'waste_item',
        'exchange_post', 'donation', 'petition', 'achievement', 'badge',
        'challenge', 'user', 'none'
    ) DEFAULT 'none',
    related_entity_id INT DEFAULT NULL,
    
    -- Environmental Impact
    carbon_impact_kg DECIMAL(10,3) DEFAULT 0.000,
    environmental_score INT DEFAULT 0,
    sustainability_points INT DEFAULT 0,
    
    -- Engagement Metrics
    engagement_score DECIMAL(8,2) DEFAULT 0.00,
    quality_score DECIMAL(5,2) DEFAULT 0.00, -- 0-100 scale
    difficulty_level ENUM('easy', 'medium', 'hard', 'expert') DEFAULT 'easy',
    effort_required ENUM('low', 'medium', 'high', 'very_high') DEFAULT 'low',
    
    -- Points & Rewards
    base_points INT DEFAULT 0,
    bonus_points INT DEFAULT 0,
    streak_bonus INT DEFAULT 0,
    total_points INT GENERATED ALWAYS AS (base_points + bonus_points + streak_bonus) STORED,
    
    -- Session Information
    session_id VARCHAR(255) DEFAULT NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'app') DEFAULT 'desktop',
    user_agent TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    
    -- Location Context
    activity_location_type ENUM('home', 'work', 'event', 'public', 'unknown') DEFAULT 'unknown',
    latitude DECIMAL(10, 6) DEFAULT NULL,
    longitude DECIMAL(10, 6) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    
    -- Timing & Duration
    activity_duration_seconds INT DEFAULT 0,
    time_of_day ENUM('morning', 'afternoon', 'evening', 'night') AS (
        CASE 
            WHEN HOUR(created_at) BETWEEN 6 AND 11 THEN 'morning'
            WHEN HOUR(created_at) BETWEEN 12 AND 17 THEN 'afternoon'
            WHEN HOUR(created_at) BETWEEN 18 AND 22 THEN 'evening'
            ELSE 'night'
        END
    ) STORED,
    
    -- Social Context
    is_collaborative BOOLEAN DEFAULT FALSE,
    collaboration_users JSON DEFAULT NULL,
    social_multiplier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Goal Tracking
    contributes_to_goal BOOLEAN DEFAULT FALSE,
    goal_category VARCHAR(100) DEFAULT NULL,
    goal_progress_contribution DECIMAL(5,2) DEFAULT 0.00,
    
    -- Quality Indicators
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method ENUM('automatic', 'manual', 'peer', 'ai') DEFAULT 'automatic',
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_activity_type (user_id, activity_type, created_at),
    INDEX idx_activity_category (activity_category, created_at),
    INDEX idx_environmental_impact (carbon_impact_kg, environmental_score),
    INDEX idx_engagement_score (engagement_score DESC),
    INDEX idx_points_earned (total_points DESC),
    INDEX idx_session_tracking (session_id, created_at),
    INDEX idx_location_activities (latitude, longitude, activity_type),
    INDEX idx_verified_activities (is_verified, verified_at),
    INDEX idx_collaborative (is_collaborative, social_multiplier),
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- 2. USER STREAKS ADVANCED SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS user_streaks_advanced (
    streak_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Streak Type & Category
    streak_type ENUM(
        'daily_login', 'daily_activity', 'waste_reporting', 'carbon_tracking',
        'quiz_participation', 'forum_engagement', 'content_creation', 'content_sharing',
        'learning_progress', 'environmental_action', 'social_engagement', 'purchasing',
        'event_participation', 'achievement_hunting', 'community_contribution'
    ) NOT NULL,
    
    streak_category ENUM(
        'engagement', 'environmental', 'learning', 'social', 'content', 'commerce'
    ) NOT NULL,
    
    -- Current Streak Status
    current_streak_count INT DEFAULT 0,
    current_streak_start_date DATE NULL DEFAULT NULL,
    last_activity_date DATE NULL DEFAULT NULL,
    
    -- Streak Records
    best_streak_count INT DEFAULT 0,
    best_streak_start_date DATE NULL DEFAULT NULL,
    best_streak_end_date DATE NULL DEFAULT NULL,
    total_streak_days INT DEFAULT 0,
    
    -- Streak Mechanics
    required_daily_activities INT DEFAULT 1,
    streak_threshold_hours INT DEFAULT 24, -- Hours within which activity must occur
    grace_period_hours INT DEFAULT 6, -- Additional hours before streak breaks
    
    -- Freeze & Protection
    freeze_cards_available INT DEFAULT 3,
    freeze_cards_used INT DEFAULT 0,
    freeze_cards_earned INT DEFAULT 0,
    last_freeze_used_date DATE NULL DEFAULT NULL,
    auto_freeze_enabled BOOLEAN DEFAULT FALSE,
    
    -- Bonus Calculations
    base_points_per_day INT DEFAULT 10,
    streak_multiplier DECIMAL(4,2) DEFAULT 1.00,
    max_multiplier DECIMAL(4,2) DEFAULT 10.00,
    multiplier_increment DECIMAL(3,2) DEFAULT 0.10,
    
    -- Milestone System
    next_milestone_target INT DEFAULT 7,
    milestones_reached JSON DEFAULT NULL, -- [7, 14, 30, 60, 100, 365, etc.]
    milestone_rewards_claimed JSON DEFAULT NULL,
    
    -- Performance Metrics
    consistency_percentage DECIMAL(5,2) DEFAULT 0.00,
    average_daily_activities DECIMAL(5,2) DEFAULT 0.00,
    peak_performance_period VARCHAR(50) DEFAULT NULL,
    
    -- Environmental Impact Tracking
    environmental_impact_total DECIMAL(10,2) DEFAULT 0.00,
    carbon_saved_total DECIMAL(8,3) DEFAULT 0.000,
    waste_items_processed INT DEFAULT 0,
    learning_hours_completed DECIMAL(6,2) DEFAULT 0.00,
    
    -- Social & Community Impact
    social_influence_score DECIMAL(8,2) DEFAULT 0.00,
    community_contributions INT DEFAULT 0,
    peer_encouragements_given INT DEFAULT 0,
    peer_encouragements_received INT DEFAULT 0,
    
    -- Streak Difficulty & Effort
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    effort_investment ENUM('minimal', 'moderate', 'high', 'intensive') DEFAULT 'minimal',
    
    -- Rewards & Recognition
    total_points_earned INT DEFAULT 0,
    total_bonus_earned INT DEFAULT 0,
    badges_earned JSON DEFAULT NULL,
    special_recognition JSON DEFAULT NULL,
    
    -- Status & Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_paused BOOLEAN DEFAULT FALSE,
    pause_reason VARCHAR(200) DEFAULT NULL,
    paused_until DATE NULL DEFAULT NULL,
    
    -- Notification Preferences
    reminder_enabled BOOLEAN DEFAULT TRUE,
    reminder_time TIME DEFAULT '09:00:00',
    motivational_messages BOOLEAN DEFAULT TRUE,
    milestone_celebrations BOOLEAN DEFAULT TRUE,
    
    -- Analytics & Insights
    best_day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') DEFAULT NULL,
    best_time_of_day ENUM('morning', 'afternoon', 'evening', 'night') DEFAULT NULL,
    activity_patterns JSON DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_streak_type (user_id, streak_type),
    INDEX idx_current_streak (current_streak_count DESC),
    INDEX idx_best_streak (best_streak_count DESC),
    INDEX idx_last_activity (last_activity_date),
    INDEX idx_active_streaks (is_active, is_paused),
    INDEX idx_category_performance (streak_category, consistency_percentage DESC),
    INDEX idx_environmental_impact (environmental_impact_total DESC),
    INDEX idx_milestone_tracking (next_milestone_target, current_streak_count),
    
    -- Unique constraint
    UNIQUE KEY unique_user_streak (user_id, streak_type),
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- 3. ENGAGEMENT SCORING SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS user_engagement_scores (
    score_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Scoring Period
    period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'all_time') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Core Engagement Metrics
    login_frequency_score DECIMAL(6,2) DEFAULT 0.00,
    activity_diversity_score DECIMAL(6,2) DEFAULT 0.00,
    content_engagement_score DECIMAL(6,2) DEFAULT 0.00,
    social_interaction_score DECIMAL(6,2) DEFAULT 0.00,
    learning_engagement_score DECIMAL(6,2) DEFAULT 0.00,
    environmental_action_score DECIMAL(6,2) DEFAULT 0.00,
    
    -- Advanced Metrics
    consistency_score DECIMAL(6,2) DEFAULT 0.00,
    quality_score DECIMAL(6,2) DEFAULT 0.00,
    innovation_score DECIMAL(6,2) DEFAULT 0.00,
    leadership_score DECIMAL(6,2) DEFAULT 0.00,
    collaboration_score DECIMAL(6,2) DEFAULT 0.00,
    
    -- Composite Scores
    total_engagement_score DECIMAL(8,2) DEFAULT 0.00,
    weighted_score DECIMAL(8,2) DEFAULT 0.00,
    percentile_rank DECIMAL(5,2) DEFAULT 0.00,
    
    -- Activity Counts
    total_activities INT DEFAULT 0,
    unique_activity_types INT DEFAULT 0,
    high_impact_activities INT DEFAULT 0,
    collaborative_activities INT DEFAULT 0,
    
    -- Time Investment
    total_time_spent_minutes INT DEFAULT 0,
    average_session_duration DECIMAL(6,2) DEFAULT 0.00,
    peak_activity_time ENUM('morning', 'afternoon', 'evening', 'night') DEFAULT NULL,
    
    -- Environmental Impact
    carbon_impact_contribution DECIMAL(10,3) DEFAULT 0.000,
    environmental_leadership_score DECIMAL(6,2) DEFAULT 0.00,
    sustainability_advocate_score DECIMAL(6,2) DEFAULT 0.00,
    
    -- Social Impact
    content_creation_score DECIMAL(6,2) DEFAULT 0.00,
    content_sharing_score DECIMAL(6,2) DEFAULT 0.00,
    community_building_score DECIMAL(6,2) DEFAULT 0.00,
    mentorship_score DECIMAL(6,2) DEFAULT 0.00,
    
    -- Achievement Progress
    achievements_unlocked INT DEFAULT 0,
    badges_earned INT DEFAULT 0,
    milestones_reached INT DEFAULT 0,
    goals_completed INT DEFAULT 0,
    
    -- Behavioral Insights
    exploration_score DECIMAL(6,2) DEFAULT 0.00, -- Trying new features
    retention_score DECIMAL(6,2) DEFAULT 0.00, -- Regular return visits
    depth_score DECIMAL(6,2) DEFAULT 0.00, -- Deep feature usage
    advocacy_score DECIMAL(6,2) DEFAULT 0.00, -- Promoting platform
    
    -- Ranking & Comparison
    user_rank INT DEFAULT NULL,
    category_ranks JSON DEFAULT NULL, -- Rank in each category
    improvement_trend ENUM('improving', 'stable', 'declining', 'new') DEFAULT 'new',
    trend_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Scoring Metadata
    calculation_method VARCHAR(100) DEFAULT 'standard_v1',
    data_completeness_percentage DECIMAL(5,2) DEFAULT 100.00,
    confidence_level DECIMAL(5,2) DEFAULT 100.00,
    
    -- Timestamps
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_period (user_id, period_type, period_start),
    INDEX idx_engagement_score (total_engagement_score DESC),
    INDEX idx_period_ranking (period_type, user_rank),
    INDEX idx_environmental_leaders (environmental_leadership_score DESC),
    INDEX idx_social_leaders (community_building_score DESC),
    INDEX idx_trend_analysis (improvement_trend, trend_percentage),
    
    -- Unique constraint
    UNIQUE KEY unique_user_period (user_id, period_type, period_start),
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- 4. ACTIVITY PATTERNS & ANALYTICS
-- ========================================

CREATE TABLE IF NOT EXISTS user_activity_patterns (
    pattern_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Pattern Analysis Period
    analysis_period ENUM('weekly', 'monthly', 'quarterly') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Time-based Patterns
    most_active_day ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
    most_active_hour TINYINT, -- 0-23
    most_active_time_range ENUM('early_morning', 'morning', 'midday', 'afternoon', 'evening', 'night', 'late_night'),
    
    -- Activity Distribution
    weekday_activity_percentage DECIMAL(5,2) DEFAULT 0.00,
    weekend_activity_percentage DECIMAL(5,2) DEFAULT 0.00,
    morning_activity_percentage DECIMAL(5,2) DEFAULT 0.00,
    afternoon_activity_percentage DECIMAL(5,2) DEFAULT 0.00,
    evening_activity_percentage DECIMAL(5,2) DEFAULT 0.00,
    night_activity_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Activity Type Preferences
    preferred_activity_types JSON DEFAULT NULL,
    activity_type_distribution JSON DEFAULT NULL,
    favorite_categories JSON DEFAULT NULL,
    
    -- Session Patterns
    average_session_count_per_day DECIMAL(4,2) DEFAULT 0.00,
    average_session_duration_minutes DECIMAL(6,2) DEFAULT 0.00,
    longest_session_duration_minutes INT DEFAULT 0,
    shortest_session_duration_minutes INT DEFAULT 0,
    
    -- Engagement Patterns
    engagement_consistency_score DECIMAL(5,2) DEFAULT 0.00,
    activity_intensity_score DECIMAL(5,2) DEFAULT 0.00,
    exploration_behavior_score DECIMAL(5,2) DEFAULT 0.00,
    routine_adherence_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Environmental Patterns
    environmental_focus_score DECIMAL(5,2) DEFAULT 0.00,
    carbon_tracking_frequency DECIMAL(5,2) DEFAULT 0.00,
    waste_reporting_consistency DECIMAL(5,2) DEFAULT 0.00,
    green_purchasing_tendency DECIMAL(5,2) DEFAULT 0.00,
    
    -- Social Patterns
    social_engagement_frequency DECIMAL(5,2) DEFAULT 0.00,
    content_creation_frequency DECIMAL(5,2) DEFAULT 0.00,
    collaboration_tendency DECIMAL(5,2) DEFAULT 0.00,
    community_participation_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Learning Patterns
    learning_session_frequency DECIMAL(5,2) DEFAULT 0.00,
    quiz_participation_rate DECIMAL(5,2) DEFAULT 0.00,
    knowledge_seeking_score DECIMAL(5,2) DEFAULT 0.00,
    skill_development_focus JSON DEFAULT NULL,
    
    -- Behavioral Insights
    motivation_factors JSON DEFAULT NULL, -- What drives engagement
    barrier_indicators JSON DEFAULT NULL, -- What limits engagement
    peak_motivation_times JSON DEFAULT NULL,
    decline_warning_signals JSON DEFAULT NULL,
    
    -- Predictions & Recommendations
    predicted_churn_risk DECIMAL(5,2) DEFAULT 0.00,
    engagement_growth_potential DECIMAL(5,2) DEFAULT 0.00,
    recommended_activities JSON DEFAULT NULL,
    optimal_engagement_times JSON DEFAULT NULL,
    
    -- Pattern Confidence
    data_quality_score DECIMAL(5,2) DEFAULT 100.00,
    pattern_confidence DECIMAL(5,2) DEFAULT 0.00,
    sample_size INT DEFAULT 0,
    
    -- Timestamps
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_analysis_period (user_id, analysis_period, period_start),
    INDEX idx_activity_patterns (most_active_day, most_active_hour),
    INDEX idx_engagement_scores (engagement_consistency_score DESC),
    INDEX idx_churn_risk (predicted_churn_risk DESC),
    INDEX idx_growth_potential (engagement_growth_potential DESC),
    
    -- Unique constraint
    UNIQUE KEY unique_user_analysis (user_id, analysis_period, period_start),
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- 5. HABIT BUILDING SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS user_habit_tracking (
    habit_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Habit Definition
    habit_name VARCHAR(200) NOT NULL,
    habit_slug VARCHAR(200) NOT NULL,
    habit_description TEXT DEFAULT NULL,
    habit_type ENUM(
        'environmental_action', 'learning', 'social_engagement', 'content_creation',
        'carbon_reduction', 'waste_management', 'energy_saving', 'water_conservation',
        'sustainable_transportation', 'green_purchasing', 'community_participation'
    ) NOT NULL,
    
    -- Habit Parameters
    target_frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    target_count INT DEFAULT 1, -- How many times per frequency period
    minimum_threshold INT DEFAULT 1, -- Minimum to maintain habit
    
    -- Current Status
    current_streak_days INT DEFAULT 0,
    best_streak_days INT DEFAULT 0,
    total_completions INT DEFAULT 0,
    completion_rate DECIMAL(5,2) DEFAULT 0.00,
    
    -- Progress Tracking
    last_completion_date DATE NULL DEFAULT NULL,
    next_due_date DATE NULL DEFAULT NULL,
    weekly_completion_count INT DEFAULT 0,
    monthly_completion_count INT DEFAULT 0,
    
    -- Habit Strength Metrics
    habit_strength_score DECIMAL(5,2) DEFAULT 0.00, -- 0-100 scale
    automaticity_level ENUM('forming', 'developing', 'established', 'automatic') DEFAULT 'forming',
    difficulty_perception ENUM('very_easy', 'easy', 'moderate', 'hard', 'very_hard') DEFAULT 'moderate',
    motivation_level DECIMAL(3,1) DEFAULT 5.0, -- 1-10 scale
    
    -- Environmental Impact
    carbon_saved_per_completion DECIMAL(8,3) DEFAULT 0.000,
    total_carbon_saved DECIMAL(10,3) DEFAULT 0.000,
    environmental_impact_category VARCHAR(100) DEFAULT NULL,
    sustainability_points_per_completion INT DEFAULT 0,
    
    -- Rewards & Incentives
    points_per_completion INT DEFAULT 10,
    streak_bonus_multiplier DECIMAL(3,2) DEFAULT 1.00,
    milestone_rewards JSON DEFAULT NULL,
    
    -- Reminders & Support
    reminder_enabled BOOLEAN DEFAULT TRUE,
    reminder_time TIME DEFAULT '09:00:00',
    reminder_message TEXT DEFAULT NULL,
    accountability_partner_id INT DEFAULT NULL,
    
    -- Tracking Methods
    tracking_method ENUM('manual', 'automatic', 'photo_proof', 'location_based', 'sensor_data') DEFAULT 'manual',
    verification_required BOOLEAN DEFAULT FALSE,
    proof_type ENUM('none', 'photo', 'receipt', 'location', 'data_entry') DEFAULT 'none',
    
    -- Habit Context
    trigger_cues JSON DEFAULT NULL, -- What triggers the habit
    habit_stack VARCHAR(200) DEFAULT NULL, -- Habit stacking
    environment_setup TEXT DEFAULT NULL,
    
    -- Progress Milestones
    milestones_definition JSON DEFAULT NULL, -- [7, 21, 66, 100 days etc.]
    milestones_achieved JSON DEFAULT NULL,
    next_milestone_target INT DEFAULT 7,
    days_until_next_milestone INT DEFAULT NULL,
    
    -- Social Features
    is_public BOOLEAN DEFAULT FALSE,
    allow_encouragement BOOLEAN DEFAULT TRUE,
    share_progress BOOLEAN DEFAULT FALSE,
    community_challenges JSON DEFAULT NULL,
    
    -- Analytics
    best_completion_time TIME DEFAULT NULL,
    worst_day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') DEFAULT NULL,
    success_patterns JSON DEFAULT NULL,
    failure_patterns JSON DEFAULT NULL,
    
    -- Status & Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_paused BOOLEAN DEFAULT FALSE,
    pause_reason VARCHAR(200) DEFAULT NULL,
    paused_until DATE NULL DEFAULT NULL,
    
    -- Goal Integration
    related_goal_id INT DEFAULT NULL,
    contributes_to_achievement BOOLEAN DEFAULT FALSE,
    achievement_requirements JSON DEFAULT NULL,
    
    -- Timestamps
    start_date DATE NOT NULL,
    target_end_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_habits (user_id, is_active),
    INDEX idx_habit_type (habit_type, habit_strength_score DESC),
    INDEX idx_streak_tracking (current_streak_days DESC),
    INDEX idx_due_reminders (next_due_date, reminder_enabled),
    INDEX idx_environmental_impact (total_carbon_saved DESC),
    INDEX idx_public_habits (is_public, share_progress),
    INDEX idx_accountability (accountability_partner_id),
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (accountability_partner_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- 6. SAMPLE DATA & INITIAL SETUP
-- ========================================

-- Insert sample activity types and categories
INSERT IGNORE INTO user_activities_comprehensive (
    user_id, activity_type, activity_category, activity_name, activity_description,
    base_points, environmental_score, engagement_score
)
SELECT 1, 'login', 'authentication', 'Daily Login', 'User logged into the platform', 10, 0, 5.00
WHERE EXISTS (SELECT 1 FROM users WHERE user_id = 1)
LIMIT 1;

-- Insert sample streak tracking
INSERT IGNORE INTO user_streaks_advanced (
    user_id, streak_type, streak_category, current_streak_count, 
    base_points_per_day, next_milestone_target
)
SELECT 1, 'daily_login', 'engagement', 5, 10, 7
WHERE EXISTS (SELECT 1 FROM users WHERE user_id = 1)
LIMIT 1;

-- Insert sample engagement scores
INSERT IGNORE INTO user_engagement_scores (
    user_id, period_type, period_start, period_end,
    total_engagement_score, total_activities
)
SELECT 1, 'weekly', CURDATE() - INTERVAL 7 DAY, CURDATE(), 150.00, 25
WHERE EXISTS (SELECT 1 FROM users WHERE user_id = 1)
LIMIT 1;

-- Insert sample habit tracking
INSERT IGNORE INTO user_habit_tracking (
    user_id, habit_name, habit_slug, habit_description, habit_type,
    target_frequency, target_count, start_date,
    carbon_saved_per_completion, points_per_completion
)
SELECT 1, 'Daily Waste Sorting', 'daily-waste-sorting', 
       'Sort household waste into proper recycling categories', 'waste_management',
       'daily', 1, CURDATE(),
       0.500, 20
WHERE EXISTS (SELECT 1 FROM users WHERE user_id = 1)
LIMIT 1;

-- ========================================
-- 7. STORED PROCEDURES FOR ENGAGEMENT
-- ========================================

DELIMITER //

-- Procedure to record user activity
CREATE PROCEDURE RecordUserActivity(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_activity_name VARCHAR(200),
    IN p_base_points INT,
    IN p_context JSON
)
BEGIN
    DECLARE v_category VARCHAR(50);
    DECLARE v_engagement_score DECIMAL(8,2);
    DECLARE v_streak_bonus INT DEFAULT 0;
    
    -- Determine category based on activity type
    SET v_category = CASE 
        WHEN p_activity_type IN ('login', 'logout') THEN 'authentication'
        WHEN p_activity_type IN ('content_view', 'content_create', 'content_share') THEN 'content'
        WHEN p_activity_type IN ('waste_report', 'carbon_tracking') THEN 'environmental'
        WHEN p_activity_type IN ('forum_post', 'social_interaction') THEN 'social'
        WHEN p_activity_type IN ('quiz_participation', 'learning_completion') THEN 'learning'
        ELSE 'engagement'
    END;
    
    -- Calculate engagement score
    SET v_engagement_score = p_base_points * 0.5;
    
    -- Insert activity record
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_name,
        activity_context, base_points, engagement_score
    ) VALUES (
        p_user_id, p_activity_type, v_category, p_activity_name,
        p_context, p_base_points, v_engagement_score
    );
    
    -- Update user points
    UPDATE users SET 
        green_points = green_points + p_base_points + v_streak_bonus,
        experience_points = experience_points + FLOOR(p_base_points * 0.8),
        last_active = CURRENT_TIMESTAMP
    WHERE user_id = p_user_id;
    
END //

-- Procedure to update streak status
CREATE PROCEDURE UpdateUserStreaks(IN p_user_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_streak_type VARCHAR(50);
    DECLARE v_last_activity DATE;
    DECLARE v_current_streak INT;
    
    DECLARE streak_cursor CURSOR FOR 
        SELECT streak_type, last_activity_date, current_streak_count
        FROM user_streaks_advanced 
        WHERE user_id = p_user_id AND is_active = TRUE;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN streak_cursor;
    
    streak_loop: LOOP
        FETCH streak_cursor INTO v_streak_type, v_last_activity, v_current_streak;
        IF done THEN
            LEAVE streak_loop;
        END IF;
        
        -- Check if streak should continue or break
        IF v_last_activity < CURDATE() - INTERVAL 1 DAY THEN
            -- Streak broken
            UPDATE user_streaks_advanced SET
                current_streak_count = 0,
                current_streak_start_date = NULL
            WHERE user_id = p_user_id AND streak_type = v_streak_type;
        END IF;
        
    END LOOP;
    
    CLOSE streak_cursor;
    
END //

-- Procedure to calculate engagement scores
CREATE PROCEDURE CalculateEngagementScores(
    IN p_user_id INT,
    IN p_period_type VARCHAR(20)
)
BEGIN
    DECLARE v_period_start DATE;
    DECLARE v_period_end DATE;
    DECLARE v_total_score DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_activity_count INT DEFAULT 0;
    
    -- Set period dates
    SET v_period_end = CURDATE();
    SET v_period_start = CASE p_period_type
        WHEN 'daily' THEN CURDATE()
        WHEN 'weekly' THEN CURDATE() - INTERVAL 7 DAY
        WHEN 'monthly' THEN CURDATE() - INTERVAL 30 DAY
        ELSE CURDATE() - INTERVAL 7 DAY
    END;
    
    -- Calculate scores
    SELECT 
        SUM(engagement_score), COUNT(*)
    INTO v_total_score, v_activity_count
    FROM user_activities_comprehensive
    WHERE user_id = p_user_id 
        AND DATE(created_at) BETWEEN v_period_start AND v_period_end;
    
    -- Insert or update engagement scores
    INSERT INTO user_engagement_scores (
        user_id, period_type, period_start, period_end,
        total_engagement_score, total_activities
    ) VALUES (
        p_user_id, p_period_type, v_period_start, v_period_end,
        COALESCE(v_total_score, 0), COALESCE(v_activity_count, 0)
    ) ON DUPLICATE KEY UPDATE
        total_engagement_score = COALESCE(v_total_score, 0),
        total_activities = COALESCE(v_activity_count, 0),
        updated_at = CURRENT_TIMESTAMP;
        
END //

DELIMITER ;

-- ========================================
-- 8. VERIFICATION QUERIES
-- ========================================

SELECT 'PHASE 20 VERIFICATION: USER ACTIVITIES & ENGAGEMENT' as verification_title;

-- Check tables created
SELECT 'Tables Created:' as check_type;
SELECT 
    table_name,
    table_rows
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
    AND table_name IN (
        'user_activities_comprehensive',
        'user_streaks_advanced', 
        'user_engagement_scores',
        'user_activity_patterns',
        'user_habit_tracking'
    );

-- Check sample data
SELECT 'Sample Data Status:' as check_type;
SELECT 'Activities:' as data_type, COUNT(*) as count FROM user_activities_comprehensive
UNION ALL
SELECT 'Streaks:', COUNT(*) FROM user_streaks_advanced
UNION ALL
SELECT 'Engagement Scores:', COUNT(*) FROM user_engagement_scores
UNION ALL
SELECT 'Activity Patterns:', COUNT(*) FROM user_activity_patterns
UNION ALL
SELECT 'Habit Tracking:', COUNT(*) FROM user_habit_tracking;

-- Final table count
SELECT 'Final Database Status:' as status;
SELECT COUNT(*) as total_tables 
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';

-- Reset SQL mode
SET SESSION sql_mode = DEFAULT;
