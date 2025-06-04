-- Test creation of first Phase 20 table
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

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
    activity_context JSON DEFAULT NULL,
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
    quality_score DECIMAL(5,2) DEFAULT 0.00,
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
