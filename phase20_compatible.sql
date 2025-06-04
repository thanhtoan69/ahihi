-- PHASE 20: USER ACTIVITIES & ENGAGEMENT SYSTEM (Compatible Version)
-- Environmental Platform Database
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ========================================
-- 1. USER ACTIVITIES COMPREHENSIVE TRACKING
-- ========================================
CREATE TABLE IF NOT EXISTS user_activities_comprehensive (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Activity Classification
    activity_type VARCHAR(50) NOT NULL,
    activity_category VARCHAR(50) NOT NULL,
    activity_subcategory VARCHAR(100) DEFAULT NULL,
    activity_name VARCHAR(200) NOT NULL,
    activity_description TEXT DEFAULT NULL,
    
    -- Context & Metadata
    activity_context TEXT DEFAULT NULL,
    related_entity_type VARCHAR(50) DEFAULT 'none',
    related_entity_id INT DEFAULT NULL,
    
    -- Environmental Impact
    carbon_impact_kg DECIMAL(10,3) DEFAULT 0.000,
    environmental_score INT DEFAULT 0,
    sustainability_points INT DEFAULT 0,
    
    -- Engagement Metrics
    engagement_score DECIMAL(8,2) DEFAULT 0.00,
    quality_score DECIMAL(5,2) DEFAULT 0.00,
    difficulty_level VARCHAR(20) DEFAULT 'easy',
    effort_required VARCHAR(20) DEFAULT 'low',
    
    -- Points & Rewards
    base_points INT DEFAULT 0,
    bonus_points INT DEFAULT 0,
    streak_bonus INT DEFAULT 0,
    total_points INT DEFAULT 0,
    
    -- Session Information
    session_id VARCHAR(255) DEFAULT NULL,
    device_type VARCHAR(20) DEFAULT 'desktop',
    user_agent TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    
    -- Location Context
    activity_location_type VARCHAR(20) DEFAULT 'unknown',
    latitude DECIMAL(10, 6) DEFAULT NULL,
    longitude DECIMAL(10, 6) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    
    -- Timing & Duration
    activity_duration_seconds INT DEFAULT 0,
    time_of_day VARCHAR(20) DEFAULT NULL,
    
    -- Social Context
    is_collaborative BOOLEAN DEFAULT FALSE,
    collaboration_users TEXT DEFAULT NULL,
    social_multiplier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Goal Tracking
    contributes_to_goal BOOLEAN DEFAULT FALSE,
    goal_category VARCHAR(100) DEFAULT NULL,
    goal_progress_contribution DECIMAL(5,2) DEFAULT 0.00,
    
    -- Quality Indicators
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method VARCHAR(20) DEFAULT 'automatic',
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
    streak_type VARCHAR(50) NOT NULL,
    streak_category VARCHAR(50) NOT NULL,
    
    -- Current Streak Status
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    total_activities INT DEFAULT 0,
    
    -- Advanced Streak Mechanics
    freeze_cards_used INT DEFAULT 0,
    freeze_cards_remaining INT DEFAULT 3,
    grace_period_hours INT DEFAULT 24,
    last_activity_date DATE DEFAULT NULL,
    
    -- Streak Rewards & Bonuses
    milestone_rewards_earned INT DEFAULT 0,
    current_multiplier DECIMAL(3,2) DEFAULT 1.00,
    max_multiplier_reached DECIMAL(3,2) DEFAULT 1.00,
    bonus_points_earned INT DEFAULT 0,
    
    -- Environmental Impact Tracking
    total_carbon_saved_kg DECIMAL(10,3) DEFAULT 0.000,
    environmental_actions_count INT DEFAULT 0,
    sustainability_impact_score INT DEFAULT 0,
    
    -- Social & Community Impact
    influenced_users_count INT DEFAULT 0,
    community_contributions INT DEFAULT 0,
    collaborative_streaks INT DEFAULT 0,
    
    -- Break Analysis
    streak_breaks_count INT DEFAULT 0,
    longest_break_days INT DEFAULT 0,
    recovery_speed_avg_days DECIMAL(5,2) DEFAULT 0.00,
    
    -- Performance Tracking
    consistency_score DECIMAL(5,2) DEFAULT 0.00,
    improvement_rate DECIMAL(5,2) DEFAULT 0.00,
    performance_trend VARCHAR(20) DEFAULT 'stable',
    
    -- Timestamps
    streak_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_streak_type (user_id, streak_type),
    INDEX idx_current_streak (current_streak DESC),
    INDEX idx_last_activity (last_activity_date DESC),
    INDEX idx_performance (consistency_score DESC, improvement_rate DESC),
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- 3. USER ENGAGEMENT SCORES
-- ========================================
CREATE TABLE IF NOT EXISTS user_engagement_scores (
    score_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Time Period Context
    score_period VARCHAR(20) NOT NULL,
    period_start_date DATE NOT NULL,
    period_end_date DATE NOT NULL,
    
    -- Core Engagement Dimensions
    activity_frequency_score DECIMAL(5,2) DEFAULT 0.00,
    activity_diversity_score DECIMAL(5,2) DEFAULT 0.00,
    quality_score DECIMAL(5,2) DEFAULT 0.00,
    consistency_score DECIMAL(5,2) DEFAULT 0.00,
    innovation_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Social Engagement
    collaboration_score DECIMAL(5,2) DEFAULT 0.00,
    community_influence_score DECIMAL(5,2) DEFAULT 0.00,
    leadership_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Environmental Focus
    environmental_impact_score DECIMAL(5,2) DEFAULT 0.00,
    sustainability_commitment_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Composite Scores
    overall_engagement_score DECIMAL(6,2) DEFAULT 0.00,
    weighted_composite_score DECIMAL(6,2) DEFAULT 0.00,
    
    -- Ranking & Percentiles
    user_rank_in_period INT DEFAULT NULL,
    percentile_rank DECIMAL(5,2) DEFAULT 0.00,
    total_users_in_period INT DEFAULT 0,
    
    -- Trend Analysis
    score_change_from_previous DECIMAL(6,2) DEFAULT 0.00,
    trend_direction VARCHAR(20) DEFAULT 'stable',
    momentum_indicator DECIMAL(5,2) DEFAULT 0.00,
    
    -- Behavioral Insights
    primary_activity_focus VARCHAR(100) DEFAULT NULL,
    engagement_pattern VARCHAR(50) DEFAULT NULL,
    motivation_factors TEXT DEFAULT NULL,
    
    -- Risk Assessment
    churn_risk_score DECIMAL(5,2) DEFAULT 0.00,
    engagement_volatility DECIMAL(5,2) DEFAULT 0.00,
    
    -- Growth Potential
    growth_potential_score DECIMAL(5,2) DEFAULT 0.00,
    recommended_actions TEXT DEFAULT NULL,
    
    -- Timestamps
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_period (user_id, score_period, period_start_date),
    INDEX idx_overall_score (overall_engagement_score DESC),
    INDEX idx_ranking (user_rank_in_period ASC),
    INDEX idx_churn_risk (churn_risk_score DESC),
    INDEX idx_growth_potential (growth_potential_score DESC),
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
