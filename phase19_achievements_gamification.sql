-- ========================================
-- PHASE 19: ACHIEVEMENTS & GAMIFICATION SYSTEM
-- Environmental Platform Database
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. ACHIEVEMENT CATEGORIES & TYPES
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
    INDEX idx_slug (category_slug),
    INDEX idx_environmental_type (environmental_impact_category, is_active),
    INDEX idx_difficulty (difficulty_level, is_featured)
) ENGINE=InnoDB;

-- ========================================
-- 2. ACHIEVEMENTS MASTER TABLE
-- ========================================

CREATE TABLE achievements (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    achievement_code VARCHAR(100) UNIQUE NOT NULL,
    achievement_name VARCHAR(200) NOT NULL,
    achievement_slug VARCHAR(200) UNIQUE NOT NULL,
    category_id INT NOT NULL,
    
    -- Localization
    name_vi VARCHAR(200) NOT NULL,
    name_en VARCHAR(200) NOT NULL,
    description TEXT,
    description_vi TEXT,
    description_en TEXT,
    unlock_hint_vi TEXT,
    unlock_hint_en TEXT,
    
    -- Achievement Properties
    achievement_type ENUM('single', 'progressive', 'repeatable', 'daily', 'weekly', 'monthly', 'seasonal', 'hidden', 'secret') DEFAULT 'single',
    difficulty_level ENUM('trivial', 'easy', 'normal', 'hard', 'expert', 'legendary', 'mythic') DEFAULT 'normal',
    rarity_level ENUM('common', 'uncommon', 'rare', 'epic', 'legendary', 'mythic') DEFAULT 'common',
    
    -- Unlock Criteria (JSON Structure)
    unlock_criteria JSON NOT NULL,
    /*
    Example unlock_criteria structure:
    {
        "trigger_type": "carbon_saved",
        "required_value": 100,
        "time_period": "monthly",
        "conditions": {
            "user_level_min": 5,
            "previous_achievements": ["newcomer", "eco_starter"],
            "location_restrictions": [],
            "seasonal_restrictions": ["summer", "winter"]
        },
        "progression_steps": [
            {"step": 1, "value": 25, "reward_points": 50},
            {"step": 2, "value": 50, "reward_points": 100},
            {"step": 3, "value": 100, "reward_points": 200}
        ]
    }
    */
    
    -- Reward System
    points_reward INT NOT NULL DEFAULT 0,
    bonus_points_weekend DECIMAL(3,2) DEFAULT 1.00,
    bonus_points_holiday DECIMAL(3,2) DEFAULT 1.50,
    carbon_credits_reward DECIMAL(8,2) DEFAULT 0,
    experience_points INT DEFAULT 0,
    
    -- Special Rewards
    badge_image_url VARCHAR(255),
    certificate_template VARCHAR(255),
    unlock_items JSON, -- Special items/features unlocked
    social_media_template TEXT,
    email_notification_template TEXT,
    
    -- Achievement Behavior
    is_active BOOLEAN DEFAULT TRUE,
    is_hidden BOOLEAN DEFAULT FALSE,
    is_secret BOOLEAN DEFAULT FALSE,
    is_repeatable BOOLEAN DEFAULT FALSE,
    max_repeats INT DEFAULT NULL,
    cooldown_hours INT DEFAULT NULL,
    
    -- Progressive Achievement Settings
    has_progression BOOLEAN DEFAULT FALSE,
    max_progression_level INT DEFAULT 1,
    progression_formula VARCHAR(255), -- e.g., "value * level^2"
    
    -- Unlock Dependencies
    prerequisite_achievements JSON,
    mutually_exclusive_achievements JSON,
    seasonal_availability JSON,
    
    -- Environmental Impact
    environmental_category ENUM('carbon_reduction', 'waste_management', 'energy_saving', 'water_conservation', 'sustainable_transport', 'eco_shopping', 'community_action', 'education', 'advocacy') DEFAULT 'education',
    environmental_impact_multiplier DECIMAL(3,2) DEFAULT 1.00,
    sustainability_score_boost INT DEFAULT 0,
    
    -- Analytics & Performance
    total_unlocks INT DEFAULT 0,
    unlock_rate DECIMAL(5,4) DEFAULT 0,
    average_unlock_time_days DECIMAL(8,2) DEFAULT NULL,
    completion_difficulty ENUM('very_easy', 'easy', 'moderate', 'hard', 'very_hard', 'extreme') DEFAULT 'moderate',
    
    -- A/B Testing
    ab_test_group VARCHAR(50),
    ab_test_active BOOLEAN DEFAULT FALSE,
    conversion_rate DECIMAL(5,4) DEFAULT 0,
    
    -- Metadata
    created_by INT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    last_modified_by INT,
    version INT DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES achievement_categories(category_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (last_modified_by) REFERENCES users(user_id),
    
    INDEX idx_code (achievement_code),
    INDEX idx_slug (achievement_slug),
    INDEX idx_category_type (category_id, achievement_type),
    INDEX idx_difficulty_rarity (difficulty_level, rarity_level),
    INDEX idx_active_hidden (is_active, is_hidden),
    INDEX idx_environmental (environmental_category, environmental_impact_multiplier),
    INDEX idx_progression (has_progression, max_progression_level),
    INDEX idx_unlock_rate (unlock_rate DESC, total_unlocks DESC),
    
    FULLTEXT(achievement_name, description, name_vi, name_en)
) ENGINE=InnoDB;

-- ========================================
-- 3. USER ACHIEVEMENTS & PROGRESS TRACKING
-- ========================================

CREATE TABLE user_achievements (
    user_achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    -- Progress Tracking
    current_progress DECIMAL(15,4) DEFAULT 0,
    required_progress DECIMAL(15,4) NOT NULL,
    progress_percentage DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN required_progress = 0 THEN 100.00
            ELSE LEAST(100.00, (current_progress / required_progress * 100))
        END
    ) STORED,
    
    -- Achievement Status
    status ENUM('not_started', 'in_progress', 'completed', 'claimed', 'expired', 'locked') DEFAULT 'not_started',
    is_unlocked BOOLEAN DEFAULT FALSE,
    is_claimed BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- Progressive Achievement Data
    current_level INT DEFAULT 1,
    max_level_reached INT DEFAULT 1,
    total_repetitions INT DEFAULT 0,
    
    -- Timing Information
    started_at TIMESTAMP NULL,
    unlocked_at TIMESTAMP NULL,
    claimed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    last_progress_update TIMESTAMP NULL,
    
    -- Progress History (JSON Array)
    progress_history JSON,
    /*
    Example progress_history:
    [
        {
            "timestamp": "2025-06-01T10:00:00Z",
            "old_progress": 45.5,
            "new_progress": 67.2,
            "trigger_action": "waste_classified",
            "context": {"waste_type": "plastic", "weight": 2.1}
        }
    ]
    */
    
    -- Reward Information
    points_earned INT DEFAULT 0,
    bonus_points_earned INT DEFAULT 0,
    carbon_credits_earned DECIMAL(8,2) DEFAULT 0,
    experience_points_earned INT DEFAULT 0,
    
    -- Social & Sharing
    shared_count INT DEFAULT 0,
    likes_received INT DEFAULT 0,
    first_to_unlock BOOLEAN DEFAULT FALSE,
    rank_when_unlocked INT DEFAULT NULL,
    
    -- Achievement Context
    unlock_trigger_action VARCHAR(100),
    unlock_context JSON, -- Context data when achievement was unlocked
    completion_method ENUM('automatic', 'manual_claim', 'admin_grant', 'import') DEFAULT 'automatic',
    
    -- Streak & Timing Bonuses
    unlock_streak_bonus DECIMAL(3,2) DEFAULT 0,
    speed_bonus_percentage DECIMAL(5,2) DEFAULT 0,
    perfect_completion_bonus BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(achievement_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_user_progress (user_id, progress_percentage DESC),
    INDEX idx_achievement_unlocked (achievement_id, is_unlocked, unlocked_at),
    INDEX idx_unlocked_date (unlocked_at DESC),
    INDEX idx_featured (user_id, is_featured),
    INDEX idx_level_progress (current_level, progress_percentage),
    INDEX idx_first_unlock (first_to_unlock, unlocked_at)
) ENGINE=InnoDB;

-- ========================================
-- 4. LEADERBOARDS SYSTEM
-- ========================================

CREATE TABLE leaderboards (
    leaderboard_id INT PRIMARY KEY AUTO_INCREMENT,
    leaderboard_name VARCHAR(150) NOT NULL,
    leaderboard_slug VARCHAR(150) UNIQUE NOT NULL,
    
    -- Localization
    display_name_vi VARCHAR(150) NOT NULL,
    display_name_en VARCHAR(150) NOT NULL,
    description_vi TEXT,
    description_en TEXT,
    
    -- Leaderboard Configuration
    leaderboard_type ENUM('points', 'achievements', 'carbon_saved', 'waste_classified', 'social_impact', 'streak', 'custom') NOT NULL,
    ranking_metric VARCHAR(100) NOT NULL,
    ranking_formula TEXT, -- Custom SQL formula for ranking
    
    -- Time Period Settings
    period_type ENUM('all_time', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom_period') DEFAULT 'monthly',
    period_start TIMESTAMP NULL,
    period_end TIMESTAMP NULL,
    rolling_period_days INT DEFAULT NULL,
    
    -- Display Settings
    max_participants INT DEFAULT 100,
    show_rank_range VARCHAR(20) DEFAULT '1-100', -- e.g., "1-50", "top_10_percent"
    minimum_activity_required INT DEFAULT 1,
    minimum_points_required INT DEFAULT 0,
    
    -- Eligibility Criteria
    eligible_user_types JSON DEFAULT '["individual", "organization"]',
    location_restrictions JSON, -- Country/city restrictions
    achievement_requirements JSON, -- Required achievements to participate
    user_level_requirements JSON, -- Min/max user levels
    
    -- Prize & Rewards System
    has_prizes BOOLEAN DEFAULT FALSE,
    prize_structure JSON,
    /*
    Example prize_structure:
    {
        "1st": {"points": 1000, "carbon_credits": 50, "badge": "gold_medal", "special_items": ["eco_certificate"]},
        "2nd": {"points": 750, "carbon_credits": 30, "badge": "silver_medal"},
        "3rd": {"points": 500, "carbon_credits": 20, "badge": "bronze_medal"},
        "top_10": {"points": 250, "carbon_credits": 10},
        "participation": {"points": 50}
    }
    */
    
    -- Environmental Focus
    environmental_category ENUM('overall', 'carbon_footprint', 'waste_reduction', 'energy_efficiency', 'water_conservation', 'sustainable_transport', 'eco_community', 'green_learning') DEFAULT 'overall',
    sustainability_focus VARCHAR(100),
    
    -- Leaderboard Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    is_auto_generated BOOLEAN DEFAULT FALSE,
    
    -- Update Settings
    update_frequency ENUM('real_time', 'hourly', 'daily', 'weekly') DEFAULT 'daily',
    last_updated TIMESTAMP NULL,
    next_update_scheduled TIMESTAMP NULL,
    cache_duration_minutes INT DEFAULT 60,
    
    -- Analytics
    total_participants INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0,
    average_score DECIMAL(15,4) DEFAULT 0,
    competition_intensity ENUM('low', 'moderate', 'high', 'extreme') DEFAULT 'moderate',
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    
    INDEX idx_slug (leaderboard_slug),
    INDEX idx_type_period (leaderboard_type, period_type),
    INDEX idx_active_featured (is_active, is_featured),
    INDEX idx_period_dates (period_start, period_end),
    INDEX idx_environmental_category (environmental_category, is_active),
    INDEX idx_update_schedule (next_update_scheduled, update_frequency)
) ENGINE=InnoDB;

-- ========================================
-- 5. LEADERBOARD ENTRIES & RANKINGS
-- ========================================

CREATE TABLE leaderboard_entries (
    entry_id INT PRIMARY KEY AUTO_INCREMENT,
    leaderboard_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Ranking Information
    current_rank INT NOT NULL,
    previous_rank INT DEFAULT NULL,
    rank_change ENUM('up', 'down', 'same', 'new') DEFAULT 'new',
    rank_change_amount INT DEFAULT 0,
    best_rank_achieved INT DEFAULT NULL,
    worst_rank_this_period INT DEFAULT NULL,
    
    -- Score & Metrics
    current_score DECIMAL(15,4) NOT NULL DEFAULT 0,
    previous_score DECIMAL(15,4) DEFAULT 0,
    score_change DECIMAL(15,4) DEFAULT 0,
    highest_score_achieved DECIMAL(15,4) DEFAULT 0,
    
    -- Performance Metrics
    achievements_count INT DEFAULT 0,
    carbon_saved_kg DECIMAL(10,2) DEFAULT 0,
    waste_classified_kg DECIMAL(10,2) DEFAULT 0,
    social_interactions INT DEFAULT 0,
    days_active_this_period INT DEFAULT 0,
    
    -- Streak & Consistency
    current_streak_days INT DEFAULT 0,
    longest_streak_this_period INT DEFAULT 0,
    consistency_score DECIMAL(5,2) DEFAULT 0, -- 0-100 based on regular activity
    
    -- Participation Details
    first_entry_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    days_participated INT DEFAULT 1,
    total_contributions INT DEFAULT 0,
    
    -- Rewards & Recognition
    prizes_won JSON,
    badges_earned JSON,
    special_recognition JSON,
    
    -- Environmental Impact
    environmental_impact_score DECIMAL(10,2) DEFAULT 0,
    sustainability_contribution_rank INT DEFAULT NULL,
    green_influence_score DECIMAL(10,2) DEFAULT 0,
    
    -- Social Elements
    supporters_count INT DEFAULT 0, -- Other users cheering them on
    rivalry_relationships JSON, -- Friendly competitions with specific users
    team_affiliations JSON,
    
    -- Metadata
    entry_period VARCHAR(50), -- e.g., "2025-06", "2025-W23", "2025-Q2"
    calculation_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_rank_calculation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (leaderboard_id) REFERENCES leaderboards(leaderboard_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_leaderboard_user_period (leaderboard_id, user_id, entry_period),
    INDEX idx_leaderboard_rank (leaderboard_id, current_rank),
    INDEX idx_leaderboard_score (leaderboard_id, current_score DESC),
    INDEX idx_user_leaderboards (user_id, current_rank),
    INDEX idx_rank_changes (rank_change, rank_change_amount DESC),
    INDEX idx_period (entry_period, leaderboard_id),
    INDEX idx_environmental_impact (environmental_impact_score DESC),
    INDEX idx_consistency (consistency_score DESC, days_active_this_period DESC)
) ENGINE=InnoDB;

-- ========================================
-- 6. ACHIEVEMENT PROGRESS LOGS
-- ========================================

CREATE TABLE achievement_progress_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_achievement_id INT NOT NULL,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    -- Progress Change Details
    previous_progress DECIMAL(15,4) DEFAULT 0,
    new_progress DECIMAL(15,4) NOT NULL,
    progress_delta DECIMAL(15,4) GENERATED ALWAYS AS (new_progress - previous_progress) STORED,
    progress_percentage_before DECIMAL(5,2) DEFAULT 0,
    progress_percentage_after DECIMAL(5,2) NOT NULL,
    
    -- Trigger Information
    trigger_action VARCHAR(100) NOT NULL,
    trigger_source ENUM('user_action', 'system_event', 'admin_adjustment', 'import', 'api_call') DEFAULT 'user_action',
    trigger_context JSON,
    /*
    Example trigger_context:
    {
        "action_type": "waste_classification",
        "item_details": {
            "waste_category": "plastic",
            "weight_kg": 2.5,
            "accuracy_score": 0.95
        },
        "session_info": {
            "session_id": "sess_123",
            "device_type": "mobile",
            "location": "Ho Chi Minh City"
        }
    }
    */
    
    -- Achievement Level Changes
    level_before INT DEFAULT 1,
    level_after INT DEFAULT 1,
    level_changed BOOLEAN GENERATED ALWAYS AS (level_after != level_before) STORED,
    
    -- Bonus & Multipliers
    base_progress_value DECIMAL(15,4) NOT NULL,
    bonus_multipliers JSON,
    final_progress_value DECIMAL(15,4) NOT NULL,
    
    -- Environmental Impact
    carbon_impact_kg DECIMAL(10,4) DEFAULT 0,
    environmental_category VARCHAR(50),
    sustainability_contribution DECIMAL(8,2) DEFAULT 0,
    
    -- Session & Context
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type ENUM('desktop', 'mobile', 'tablet', 'api', 'unknown') DEFAULT 'unknown',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_achievement_id) REFERENCES user_achievements(user_achievement_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(achievement_id) ON DELETE CASCADE,
    
    INDEX idx_user_achievement (user_achievement_id, created_at DESC),
    INDEX idx_user_progress (user_id, created_at DESC),
    INDEX idx_achievement_logs (achievement_id, created_at DESC),
    INDEX idx_trigger_action (trigger_action, created_at DESC),
    INDEX idx_progress_delta (progress_delta DESC, created_at DESC),
    INDEX idx_environmental (environmental_category, carbon_impact_kg DESC)
) ENGINE=InnoDB;

-- ========================================
-- 7. GAMIFICATION RULES ENGINE
-- ========================================

CREATE TABLE gamification_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(150) NOT NULL,
    rule_code VARCHAR(100) UNIQUE NOT NULL,
    rule_type ENUM('achievement_unlock', 'point_calculation', 'level_progression', 'bonus_multiplier', 'streak_bonus', 'environmental_impact') NOT NULL,
    
    -- Rule Definition
    trigger_events JSON NOT NULL,
    /*
    Example trigger_events:
    {
        "event_types": ["waste_classification", "carbon_logging", "social_sharing"],
        "conditions": {
            "min_accuracy": 0.8,
            "min_value": 10,
            "time_constraints": {"hour_start": 6, "hour_end": 22},
            "day_constraints": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
    }
    */
    
    condition_logic TEXT NOT NULL, -- SQL-like condition logic
    action_definition JSON NOT NULL,
    /*
    Example action_definition:
    {
        "action_type": "award_points",
        "base_amount": 50,
        "multipliers": {
            "weekend": 1.5,
            "holiday": 2.0,
            "first_time": 2.0,
            "streak_bonus": "streak_days * 0.1"
        },
        "max_amount": 500,
        "achievement_updates": ["carbon_warrior", "eco_champion"]
    }
    */
    
    -- Rule Configuration
    priority_level INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    is_stackable BOOLEAN DEFAULT TRUE, -- Can combine with other rules
    max_applications_per_user_per_day INT DEFAULT NULL,
    max_applications_per_user_total INT DEFAULT NULL,
    
    -- Environmental Focus
    environmental_categories JSON DEFAULT '["general"]',
    sustainability_impact_weight DECIMAL(3,2) DEFAULT 1.00,
    carbon_efficiency_factor DECIMAL(5,4) DEFAULT 1.0000,
    
    -- Timing & Scheduling
    effective_start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    effective_end_date TIMESTAMP NULL,
    schedule_pattern JSON, -- Recurring schedule patterns
    
    -- A/B Testing
    ab_test_group VARCHAR(50),
    ab_test_percentage DECIMAL(5,2) DEFAULT 100.00,
    test_conversion_target VARCHAR(100),
    
    -- Analytics
    total_applications INT DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 0,
    average_impact_per_application DECIMAL(10,4) DEFAULT 0,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    
    INDEX idx_rule_code (rule_code),
    INDEX idx_rule_type (rule_type, is_active),
    INDEX idx_priority (priority_level DESC, rule_type),
    INDEX idx_active_dates (is_active, effective_start_date, effective_end_date),
    INDEX idx_environmental (environmental_categories(50), sustainability_impact_weight)
) ENGINE=InnoDB;

-- ========================================
-- 8. USER GAMIFICATION STATS
-- ========================================

CREATE TABLE user_gamification_stats (
    stats_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    
    -- Overall Progress
    total_achievements_unlocked INT DEFAULT 0,
    total_achievements_available INT DEFAULT 0,
    achievement_completion_rate DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN total_achievements_available = 0 THEN 0
            ELSE (total_achievements_unlocked / total_achievements_available * 100)
        END
    ) STORED,
    
    -- Points & Scoring
    lifetime_points_earned BIGINT DEFAULT 0,
    current_points_balance INT DEFAULT 0,
    points_spent_total BIGINT DEFAULT 0,
    highest_points_balance_ever INT DEFAULT 0,
    average_points_per_day DECIMAL(10,2) DEFAULT 0,
    
    -- Level & Experience
    current_level INT DEFAULT 1,
    experience_points_total BIGINT DEFAULT 0,
    experience_points_current_level INT DEFAULT 0,
    experience_points_to_next_level INT DEFAULT 100,
    level_progression_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- Achievement Statistics
    common_achievements INT DEFAULT 0,
    uncommon_achievements INT DEFAULT 0,
    rare_achievements INT DEFAULT 0,
    epic_achievements INT DEFAULT 0,
    legendary_achievements INT DEFAULT 0,
    mythic_achievements INT DEFAULT 0,
    secret_achievements_found INT DEFAULT 0,
    first_to_unlock_count INT DEFAULT 0,
    
    -- Streak & Consistency
    current_daily_streak INT DEFAULT 0,
    longest_daily_streak INT DEFAULT 0,
    current_weekly_streak INT DEFAULT 0,
    longest_weekly_streak INT DEFAULT 0,
    total_active_days INT DEFAULT 0,
    consistency_score DECIMAL(5,2) DEFAULT 0,
    
    -- Environmental Impact
    total_carbon_saved_kg DECIMAL(15,4) DEFAULT 0,
    total_waste_classified_kg DECIMAL(15,4) DEFAULT 0,
    environmental_impact_score DECIMAL(15,4) DEFAULT 0,
    sustainability_rank INT DEFAULT NULL,
    green_influence_score DECIMAL(10,2) DEFAULT 0,
    
    -- Social & Competition
    leaderboard_appearances INT DEFAULT 0,
    highest_leaderboard_rank INT DEFAULT NULL,
    current_leaderboard_positions JSON,
    social_achievements_unlocked INT DEFAULT 0,
    community_contributions INT DEFAULT 0,
    
    -- Performance Metrics
    average_achievement_unlock_time_days DECIMAL(8,2) DEFAULT NULL,
    completion_speed_rank ENUM('very_slow', 'slow', 'average', 'fast', 'very_fast') DEFAULT 'average',
    difficulty_preference ENUM('easy', 'moderate', 'hard', 'extreme') DEFAULT 'moderate',
    
    -- Engagement Patterns
    favorite_achievement_categories JSON,
    most_active_time_of_day TIME DEFAULT NULL,
    most_active_day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') DEFAULT NULL,
    seasonal_activity_pattern JSON,
    
    -- Rewards & Recognition
    total_badges_earned INT DEFAULT 0,
    special_titles_unlocked JSON,
    carbon_credits_earned DECIMAL(15,4) DEFAULT 0,
    certificates_earned INT DEFAULT 0,
    
    -- Gaming Behavior
    achievement_hunting_score DECIMAL(5,2) DEFAULT 0, -- How actively they pursue achievements
    completion_perfectionist_score DECIMAL(5,2) DEFAULT 0, -- Tendency to complete everything
    social_competitor_score DECIMAL(5,2) DEFAULT 0, -- Focus on leaderboards and competition
    environmental_champion_score DECIMAL(5,2) DEFAULT 0, -- Focus on environmental impact
    
    last_achievement_unlocked_at TIMESTAMP NULL,
    last_points_earned_at TIMESTAMP NULL,
    last_level_up_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_level (current_level DESC, experience_points_total DESC),
    INDEX idx_achievements (total_achievements_unlocked DESC, achievement_completion_rate DESC),
    INDEX idx_points (lifetime_points_earned DESC, current_points_balance DESC),
    INDEX idx_environmental_impact (environmental_impact_score DESC, total_carbon_saved_kg DESC),
    INDEX idx_consistency (consistency_score DESC, longest_daily_streak DESC),
    INDEX idx_ranking (sustainability_rank, green_influence_score DESC)
) ENGINE=InnoDB;

-- ========================================
-- 9. ACHIEVEMENT UNLOCK EVENTS
-- ========================================

CREATE TABLE achievement_unlock_events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    user_achievement_id INT NOT NULL,
    
    -- Event Details
    unlock_type ENUM('automatic', 'manual_trigger', 'admin_grant', 'system_correction', 'import') DEFAULT 'automatic',
    trigger_action VARCHAR(100),
    trigger_context JSON,
    
    -- Achievement Details at Unlock
    achievement_level_unlocked INT DEFAULT 1,
    total_progress_value DECIMAL(15,4) NOT NULL,
    unlock_speed_days DECIMAL(8,2) DEFAULT NULL,
    unlock_difficulty_rating ENUM('trivial', 'easy', 'normal', 'hard', 'extreme') DEFAULT 'normal',
    
    -- Rewards Granted
    points_awarded INT DEFAULT 0,
    bonus_points_awarded INT DEFAULT 0,
    carbon_credits_awarded DECIMAL(8,2) DEFAULT 0,
    experience_points_awarded INT DEFAULT 0,
    special_rewards JSON,
    
    -- Environmental Context
    environmental_impact_at_unlock DECIMAL(10,4) DEFAULT 0,
    carbon_saved_milestone BOOLEAN DEFAULT FALSE,
    waste_reduction_milestone BOOLEAN DEFAULT FALSE,
    community_impact_milestone BOOLEAN DEFAULT FALSE,
    
    -- Social & Recognition
    public_announcement BOOLEAN DEFAULT TRUE,
    social_sharing_triggered BOOLEAN DEFAULT FALSE,
    leaderboard_rank_at_unlock INT DEFAULT NULL,
    first_person_to_unlock BOOLEAN DEFAULT FALSE,
    unlock_celebration_level ENUM('none', 'small', 'medium', 'large', 'spectacular') DEFAULT 'medium',
    
    -- User State at Unlock
    user_level_at_unlock INT DEFAULT 1,
    total_achievements_at_unlock INT DEFAULT 0,
    user_streak_days_at_unlock INT DEFAULT 0,
    user_points_at_unlock INT DEFAULT 0,
    
    -- Performance Metrics
    session_duration_minutes INT DEFAULT NULL,
    actions_taken_this_session INT DEFAULT NULL,
    user_engagement_score DECIMAL(5,2) DEFAULT NULL,
    
    -- Notification & Communication
    notification_sent BOOLEAN DEFAULT FALSE,
    email_sent BOOLEAN DEFAULT FALSE,
    push_notification_sent BOOLEAN DEFAULT FALSE,
    social_media_posted BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(achievement_id) ON DELETE CASCADE,
    FOREIGN KEY (user_achievement_id) REFERENCES user_achievements(user_achievement_id) ON DELETE CASCADE,
    
    INDEX idx_user_unlocks (user_id, created_at DESC),
    INDEX idx_achievement_unlocks (achievement_id, created_at DESC),
    INDEX idx_unlock_type (unlock_type, created_at DESC),
    INDEX idx_first_unlock (first_person_to_unlock, created_at),
    INDEX idx_milestones (carbon_saved_milestone, waste_reduction_milestone, community_impact_milestone),
    INDEX idx_celebration_level (unlock_celebration_level, public_announcement),
    INDEX idx_environmental_impact (environmental_impact_at_unlock DESC, created_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- 10. SAMPLE DATA INSERTION
-- ========================================

-- Insert Achievement Categories
INSERT INTO achievement_categories (category_name, category_display_name, category_slug, display_name_vi, display_name_en, description_vi, description_en, icon_name, color_primary, environmental_impact_category, sustainability_weight) VALUES
('environmental_action', 'Environmental Action', 'environmental-action', 'Hành động Môi trường', 'Environmental Action', 'Thành tích liên quan đến các hành động bảo vệ môi trường', 'Achievements related to environmental protection actions', 'leaf', '#22c55e', 'carbon', 2.00),
('waste_management', 'Waste Management', 'waste-management', 'Quản lý Chất thải', 'Waste Management', 'Thành tích về phân loại và xử lý rác thải', 'Achievements for waste sorting and management', 'recycle', '#3b82f6', 'waste', 1.80),
('social_impact', 'Social Impact', 'social-impact', 'Tác động Xã hội', 'Social Impact', 'Thành tích về ảnh hưởng tích cực đến cộng đồng', 'Achievements for positive community influence', 'users', '#8b5cf6', 'social', 1.50),
('learning_growth', 'Learning & Growth', 'learning-growth', 'Học tập & Phát triển', 'Learning & Growth', 'Thành tích về việc học hỏi kiến thức môi trường', 'Achievements for environmental knowledge and learning', 'book-open', '#f59e0b', 'learning', 1.30),
('special_recognition', 'Special Recognition', 'special-recognition', 'Ghi nhận Đặc biệt', 'Special Recognition', 'Các thành tích đặc biệt và hiếm có', 'Special and rare achievements', 'award', '#dc2626', 'general', 3.00);

-- Insert Achievements
INSERT INTO achievements (achievement_code, achievement_name, achievement_slug, category_id, name_vi, name_en, description_vi, description_en, achievement_type, difficulty_level, rarity_level, unlock_criteria, points_reward, environmental_category, environmental_impact_multiplier, is_active) VALUES

-- Environmental Action Achievements
('ECO_NEWCOMER', 'Eco Newcomer', 'eco-newcomer', 1, 'Người mới quan tâm môi trường', 'Eco Newcomer', 'Hoàn thành đăng ký và thiết lập hồ sơ môi trường', 'Complete registration and set up environmental profile', 'single', 'trivial', 'common', '{"trigger_type": "profile_completion", "required_value": 1, "conditions": {"profile_fields_completed": 5}}', 100, 'education', 1.00, true),

('CARBON_SAVER_BRONZE', 'Carbon Saver Bronze', 'carbon-saver-bronze', 1, 'Người tiết kiệm Carbon Đồng', 'Carbon Saver Bronze', 'Tiết kiệm 50kg CO2 đầu tiên', 'Save your first 50kg of CO2', 'single', 'easy', 'common', '{"trigger_type": "carbon_saved", "required_value": 50, "time_period": "lifetime"}', 250, 'carbon_reduction', 1.50, true),

('CARBON_SAVER_SILVER', 'Carbon Saver Silver', 'carbon-saver-silver', 1, 'Người tiết kiệm Carbon Bạc', 'Carbon Saver Silver', 'Tiết kiệm 250kg CO2', 'Save 250kg of CO2', 'single', 'normal', 'uncommon', '{"trigger_type": "carbon_saved", "required_value": 250, "time_period": "lifetime", "conditions": {"prerequisite_achievements": ["CARBON_SAVER_BRONZE"]}}', 500, 'carbon_reduction', 1.75, true),

('CARBON_SAVER_GOLD', 'Carbon Saver Gold', 'carbon-saver-gold', 1, 'Người tiết kiệm Carbon Vàng', 'Carbon Saver Gold', 'Tiết kiệm 1000kg CO2', 'Save 1000kg of CO2', 'single', 'hard', 'rare', '{"trigger_type": "carbon_saved", "required_value": 1000, "time_period": "lifetime", "conditions": {"prerequisite_achievements": ["CARBON_SAVER_SILVER"]}}', 1000, 'carbon_reduction', 2.00, true),

-- Waste Management Achievements
('WASTE_CLASSIFIER_STARTER', 'Waste Classification Starter', 'waste-classifier-starter', 2, 'Người mới phân loại rác', 'Waste Classification Starter', 'Phân loại đúng 10 loại rác đầu tiên', 'Correctly classify your first 10 waste items', 'single', 'easy', 'common', '{"trigger_type": "waste_classified_correct", "required_value": 10, "conditions": {"accuracy_min": 0.8}}', 150, 'waste_management', 1.40, true),

('WASTE_EXPERT', 'Waste Management Expert', 'waste-expert', 2, 'Chuyên gia quản lý rác', 'Waste Management Expert', 'Phân loại đúng 500 loại rác với độ chính xác >90%', 'Correctly classify 500 waste items with >90% accuracy', 'single', 'hard', 'rare', '{"trigger_type": "waste_classified_correct", "required_value": 500, "conditions": {"accuracy_min": 0.9}}', 750, 'waste_management', 2.20, true),

('DAILY_WASTE_WARRIOR', 'Daily Waste Warrior', 'daily-waste-warrior', 2, 'Chiến binh rác hàng ngày', 'Daily Waste Warrior', 'Phân loại rác mỗi ngày trong 30 ngày liên tiếp', 'Classify waste every day for 30 consecutive days', 'single', 'normal', 'uncommon', '{"trigger_type": "daily_waste_streak", "required_value": 30}', 400, 'waste_management', 1.80, true),

-- Social Impact Achievements
('COMMUNITY_BUILDER', 'Community Builder', 'community-builder', 3, 'Người xây dựng cộng đồng', 'Community Builder', 'Tham gia 50 hoạt động cộng đồng', 'Participate in 50 community activities', 'single', 'normal', 'uncommon', '{"trigger_type": "community_participation", "required_value": 50}', 300, 'community_action', 1.60, true),

('ENVIRONMENTAL_INFLUENCER', 'Environmental Influencer', 'environmental-influencer', 3, 'Người có ảnh hưởng môi trường', 'Environmental Influencer', 'Có 100 người theo dõi hoạt động môi trường của bạn', 'Have 100 people following your environmental activities', 'single', 'hard', 'rare', '{"trigger_type": "followers_count", "required_value": 100, "conditions": {"follower_type": "environmental"}}', 600, 'advocacy', 2.00, true),

-- Learning & Growth Achievements
('QUIZ_CHAMPION', 'Environmental Quiz Champion', 'quiz-champion', 4, 'Nhà vô địch quiz môi trường', 'Environmental Quiz Champion', 'Hoàn thành 25 bài quiz với điểm số >80%', 'Complete 25 environmental quizzes with >80% score', 'single', 'normal', 'uncommon', '{"trigger_type": "quiz_completion", "required_value": 25, "conditions": {"score_min": 0.8}}', 350, 'education', 1.30, true),

('KNOWLEDGE_SEEKER', 'Knowledge Seeker', 'knowledge-seeker', 4, 'Người tìm kiếm tri thức', 'Knowledge Seeker', 'Đọc 100 bài viết về môi trường', 'Read 100 environmental articles', 'single', 'easy', 'common', '{"trigger_type": "articles_read", "required_value": 100}', 200, 'education', 1.20, true),

-- Special Recognition Achievement
('ECO_LEGEND', 'Eco Legend', 'eco-legend', 5, 'Huyền thoại Sinh thái', 'Eco Legend', 'Đạt được tất cả thành tích khác và có tác động môi trường xuất sắc', 'Achieve all other achievements and have exceptional environmental impact', 'single', 'legendary', 'mythic', '{"trigger_type": "all_achievements_completed", "required_value": 1, "conditions": {"min_achievements": 50, "min_carbon_saved": 5000, "min_community_impact": 1000}}', 5000, 'advocacy', 5.00, true);

-- Insert Sample Leaderboards
INSERT INTO leaderboards (leaderboard_name, leaderboard_slug, display_name_vi, display_name_en, description_vi, description_en, leaderboard_type, ranking_metric, period_type, max_participants, environmental_category, is_active, is_featured) VALUES
('Monthly Carbon Savers', 'monthly-carbon-savers', 'Người tiết kiệm Carbon hàng tháng', 'Monthly Carbon Savers', 'Bảng xếp hạng những người tiết kiệm CO2 nhiều nhất trong tháng', 'Ranking of top CO2 savers this month', 'carbon_saved', 'total_carbon_saved_kg', 'monthly', 100, 'carbon_footprint', true, true),
('Waste Classification Masters', 'waste-classification-masters', 'Bậc thầy phân loại rác', 'Waste Classification Masters', 'Xếp hạng những người phân loại rác giỏi nhất', 'Ranking of best waste classifiers', 'waste_classified', 'waste_classified_accuracy', 'weekly', 50, 'waste_reduction', true, true),
('Environmental Champions', 'environmental-champions', 'Nhà vô địch Môi trường', 'Environmental Champions', 'Tổng hợp xếp hạng tác động môi trường', 'Overall environmental impact ranking', 'custom', 'environmental_impact_score', 'all_time', 200, 'overall', true, true),
('Green Learning Leaders', 'green-learning-leaders', 'Người dẫn đầu học tập xanh', 'Green Learning Leaders', 'Xếp hạng những người học kiến thức môi trường tích cực nhất', 'Ranking of most active environmental learners', 'achievements', 'learning_achievements_count', 'quarterly', 75, 'green_learning', true, false);

-- Insert Sample Gamification Rules
INSERT INTO gamification_rules (rule_name, rule_code, rule_type, trigger_events, condition_logic, action_definition, priority_level, environmental_categories, sustainability_impact_weight, is_active) VALUES
('Weekend Bonus Points', 'WEEKEND_BONUS', 'point_calculation', '["waste_classification", "carbon_logging", "quiz_completion"]', 'DAYOFWEEK(NOW()) IN (1, 7)', '{"action_type": "multiply_points", "multiplier": 1.5, "max_bonus": 200}', 90, '["waste", "carbon", "learning"]', 1.20, true),
('First Time Achievement Bonus', 'FIRST_TIME_BONUS', 'achievement_unlock', '["achievement_unlock"]', 'first_achievement_in_category = TRUE', '{"action_type": "bonus_points", "bonus_amount": 100, "special_recognition": "first_in_category"}', 95, '["general"]', 1.50, true),
('Streak Multiplier', 'STREAK_MULTIPLIER', 'bonus_multiplier', '["daily_activity"]', 'user_streak_days >= 7', '{"action_type": "apply_multiplier", "formula": "1 + (streak_days * 0.05)", "max_multiplier": 3.0}', 85, '["general"]', 1.30, true),
('Environmental Impact Boost', 'ENVIRONMENTAL_BOOST', 'environmental_impact', '["carbon_saved", "waste_classified"]', 'environmental_impact > 10', '{"action_type": "boost_impact", "boost_factor": 1.25, "recognition_threshold": 50}', 100, '["carbon", "waste"]', 2.00, true);

-- ========================================
-- 11. INDEXES FOR PERFORMANCE OPTIMIZATION
-- ========================================

-- Additional performance indexes
CREATE INDEX idx_achievements_environmental_active ON achievements(environmental_category, is_active, points_reward DESC);
CREATE INDEX idx_user_achievements_progress_status ON user_achievements(user_id, status, progress_percentage DESC);
CREATE INDEX idx_leaderboard_entries_score_rank ON leaderboard_entries(leaderboard_id, current_score DESC, current_rank);
CREATE INDEX idx_progress_logs_trigger_environmental ON achievement_progress_logs(trigger_action, environmental_category, created_at DESC);
CREATE INDEX idx_unlock_events_milestone ON achievement_unlock_events(carbon_saved_milestone, waste_reduction_milestone, created_at DESC);
CREATE INDEX idx_gamification_stats_ranking ON user_gamification_stats(environmental_impact_score DESC, current_level DESC, total_achievements_unlocked DESC);

-- ========================================
-- 12. VIEWS FOR REPORTING & ANALYTICS
-- ========================================

-- User Achievement Summary View
CREATE VIEW user_achievement_summary AS
SELECT 
    u.user_id,
    u.username,
    ugs.total_achievements_unlocked,
    ugs.achievement_completion_rate,
    ugs.current_level,
    ugs.lifetime_points_earned,
    ugs.environmental_impact_score,
    ugs.longest_daily_streak,
    COUNT(CASE WHEN ua.status = 'completed' AND a.rarity_level = 'rare' THEN 1 END) as rare_achievements,
    COUNT(CASE WHEN ua.status = 'completed' AND a.rarity_level = 'epic' THEN 1 END) as epic_achievements,
    COUNT(CASE WHEN ua.status = 'completed' AND a.rarity_level = 'legendary' THEN 1 END) as legendary_achievements
FROM users u
LEFT JOIN user_gamification_stats ugs ON u.user_id = ugs.user_id
LEFT JOIN user_achievements ua ON u.user_id = ua.user_id
LEFT JOIN achievements a ON ua.achievement_id = a.achievement_id
WHERE u.is_active = TRUE
GROUP BY u.user_id, u.username, ugs.total_achievements_unlocked, ugs.achievement_completion_rate, 
         ugs.current_level, ugs.lifetime_points_earned, ugs.environmental_impact_score, ugs.longest_daily_streak;

-- Leaderboard Summary View
CREATE VIEW leaderboard_summary AS
SELECT 
    l.leaderboard_id,
    l.leaderboard_name,
    l.period_type,
    l.environmental_category,
    COUNT(le.entry_id) as total_participants,
    AVG(le.current_score) as average_score,
    MAX(le.current_score) as highest_score,
    MIN(le.current_score) as lowest_score,
    SUM(le.environmental_impact_score) as total_environmental_impact
FROM leaderboards l
LEFT JOIN leaderboard_entries le ON l.leaderboard_id = le.leaderboard_id
WHERE l.is_active = TRUE
GROUP BY l.leaderboard_id, l.leaderboard_name, l.period_type, l.environmental_category;

-- Achievement Performance View
CREATE VIEW achievement_performance AS
SELECT 
    a.achievement_id,
    a.achievement_code,
    a.achievement_name,
    a.difficulty_level,
    a.rarity_level,
    a.points_reward,
    ac.category_name,
    COUNT(ua.user_achievement_id) as total_attempts,
    COUNT(CASE WHEN ua.status = 'completed' THEN 1 END) as successful_completions,
    ROUND(COUNT(CASE WHEN ua.status = 'completed' THEN 1 END) / COUNT(ua.user_achievement_id) * 100, 2) as completion_rate,
    AVG(DATEDIFF(ua.unlocked_at, ua.started_at)) as avg_completion_days
FROM achievements a
JOIN achievement_categories ac ON a.category_id = ac.category_id
LEFT JOIN user_achievements ua ON a.achievement_id = ua.achievement_id
WHERE a.is_active = TRUE
GROUP BY a.achievement_id, a.achievement_code, a.achievement_name, a.difficulty_level, 
         a.rarity_level, a.points_reward, ac.category_name;

-- ========================================
-- 13. STORED PROCEDURES FOR GAMIFICATION
-- ========================================

DELIMITER //

-- Procedure to check and unlock achievements for a user
CREATE PROCEDURE CheckAndUnlockAchievements(
    IN p_user_id INT,
    IN p_trigger_action VARCHAR(100),
    IN p_trigger_context JSON
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_achievement_id INT;
    DECLARE v_unlock_criteria JSON;
    DECLARE v_points_reward INT;
    
    -- Cursor for achievements that might be unlocked
    DECLARE achievement_cursor CURSOR FOR
        SELECT a.achievement_id, a.unlock_criteria, a.points_reward
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.achievement_id = ua.achievement_id AND ua.user_id = p_user_id
        WHERE a.is_active = TRUE 
        AND (ua.user_achievement_id IS NULL OR ua.status != 'completed')
        AND JSON_EXTRACT(a.unlock_criteria, '$.trigger_type') = p_trigger_action;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN achievement_cursor;
    
    read_loop: LOOP
        FETCH achievement_cursor INTO v_achievement_id, v_unlock_criteria, v_points_reward;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Here you would implement the logic to check if achievement criteria are met
        -- This is a simplified version - actual implementation would parse JSON criteria
        -- and check against user data
        
        -- If criteria met, unlock achievement
        -- INSERT INTO achievement_unlock_events...
        -- UPDATE user_achievements...
        -- UPDATE user_gamification_stats...
        
    END LOOP;
    
    CLOSE achievement_cursor;
END //

-- Procedure to update user gamification statistics
CREATE PROCEDURE UpdateUserGamificationStats(IN p_user_id INT)
BEGIN
    DECLARE v_total_achievements INT DEFAULT 0;
    DECLARE v_total_points BIGINT DEFAULT 0;
    DECLARE v_current_level INT DEFAULT 1;
    
    -- Calculate total achievements unlocked
    SELECT COUNT(*) INTO v_total_achievements
    FROM user_achievements 
    WHERE user_id = p_user_id AND status = 'completed';
    
    -- Calculate total points earned
    SELECT COALESCE(SUM(points_earned), 0) INTO v_total_points
    FROM user_achievements 
    WHERE user_id = p_user_id AND status = 'completed';
    
    -- Calculate user level based on points (example: level = sqrt(points/1000))
    SET v_current_level = GREATEST(1, FLOOR(SQRT(v_total_points / 1000)));
    
    -- Update or insert user gamification stats
    INSERT INTO user_gamification_stats (
        user_id, 
        total_achievements_unlocked, 
        lifetime_points_earned, 
        current_level,
        updated_at
    ) VALUES (
        p_user_id, 
        v_total_achievements, 
        v_total_points, 
        v_current_level,
        NOW()
    ) ON DUPLICATE KEY UPDATE
        total_achievements_unlocked = v_total_achievements,
        lifetime_points_earned = v_total_points,
        current_level = v_current_level,
        updated_at = NOW();
END //

-- Procedure to update leaderboard rankings
CREATE PROCEDURE UpdateLeaderboardRankings(IN p_leaderboard_id INT)
BEGIN
    DECLARE v_ranking INT DEFAULT 1;
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_entry_id INT;
    DECLARE v_score DECIMAL(15,4);
    
    -- Cursor for entries ordered by score
    DECLARE ranking_cursor CURSOR FOR
        SELECT entry_id, current_score
        FROM leaderboard_entries 
        WHERE leaderboard_id = p_leaderboard_id
        ORDER BY current_score DESC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN ranking_cursor;
    
    ranking_loop: LOOP
        FETCH ranking_cursor INTO v_entry_id, v_score;
        IF done THEN
            LEAVE ranking_loop;
        END IF;
        
        -- Update rank
        UPDATE leaderboard_entries 
        SET previous_rank = current_rank,
            current_rank = v_ranking,
            rank_change = CASE 
                WHEN previous_rank IS NULL THEN 'new'
                WHEN current_rank < previous_rank THEN 'up'
                WHEN current_rank > previous_rank THEN 'down'
                ELSE 'same'
            END,
            rank_change_amount = COALESCE(ABS(current_rank - previous_rank), 0),
            updated_at = NOW()
        WHERE entry_id = v_entry_id;
        
        SET v_ranking = v_ranking + 1;
    END LOOP;
    
    CLOSE ranking_cursor;
END //

DELIMITER ;

-- ========================================
-- COMPLETION MESSAGE
-- ========================================

SELECT 
    'Phase 19: Achievements & Gamification System - COMPLETED!' as status,
    'Added 9 new tables for comprehensive gamification' as summary,
    NOW() as completed_at;

-- Show table count
SELECT COUNT(*) as total_tables 
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';
