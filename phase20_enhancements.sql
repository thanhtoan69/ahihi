-- Phase 20 Enhancement: Add comprehensive columns and create stored procedures
-- Add more columns to user_activities_comprehensive
ALTER TABLE user_activities_comprehensive 
ADD COLUMN activity_category VARCHAR(50) DEFAULT NULL,
ADD COLUMN activity_subcategory VARCHAR(100) DEFAULT NULL,
ADD COLUMN activity_name VARCHAR(200) DEFAULT NULL,
ADD COLUMN activity_description TEXT DEFAULT NULL,
ADD COLUMN carbon_impact_kg DECIMAL(10,3) DEFAULT 0.000,
ADD COLUMN environmental_score INT DEFAULT 0,
ADD COLUMN sustainability_points INT DEFAULT 0,
ADD COLUMN engagement_score DECIMAL(8,2) DEFAULT 0.00,
ADD COLUMN quality_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN base_points INT DEFAULT 0,
ADD COLUMN bonus_points INT DEFAULT 0,
ADD COLUMN streak_bonus INT DEFAULT 0,
ADD COLUMN total_points INT DEFAULT 0,
ADD COLUMN is_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN device_type VARCHAR(20) DEFAULT 'desktop',
ADD COLUMN session_id VARCHAR(255) DEFAULT NULL,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add comprehensive columns to user_streaks_advanced
ALTER TABLE user_streaks_advanced
ADD COLUMN streak_category VARCHAR(50) DEFAULT NULL,
ADD COLUMN longest_streak INT DEFAULT 0,
ADD COLUMN total_activities INT DEFAULT 0,
ADD COLUMN freeze_cards_remaining INT DEFAULT 3,
ADD COLUMN last_activity_date DATE DEFAULT NULL,
ADD COLUMN bonus_points_earned INT DEFAULT 0,
ADD COLUMN total_carbon_saved_kg DECIMAL(10,3) DEFAULT 0.000,
ADD COLUMN consistency_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN performance_trend VARCHAR(20) DEFAULT 'stable',
ADD COLUMN last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add comprehensive columns to user_engagement_scores
ALTER TABLE user_engagement_scores
ADD COLUMN period_start_date DATE DEFAULT NULL,
ADD COLUMN period_end_date DATE DEFAULT NULL,
ADD COLUMN activity_frequency_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN quality_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN consistency_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN environmental_impact_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN user_rank_in_period INT DEFAULT NULL,
ADD COLUMN percentile_rank DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN churn_risk_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN growth_potential_score DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add comprehensive columns to user_activity_patterns
ALTER TABLE user_activity_patterns
ADD COLUMN analysis_start_date DATE DEFAULT NULL,
ADD COLUMN analysis_end_date DATE DEFAULT NULL,
ADD COLUMN primary_activity_times TEXT DEFAULT NULL,
ADD COLUMN preferred_activity_types TEXT DEFAULT NULL,
ADD COLUMN engagement_decline_indicators TEXT DEFAULT NULL,
ADD COLUMN churn_risk_indicators TEXT DEFAULT NULL,
ADD COLUMN personalization_recommendations TEXT DEFAULT NULL,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add comprehensive columns to user_habit_tracking
ALTER TABLE user_habit_tracking
ADD COLUMN habit_description TEXT DEFAULT NULL,
ADD COLUMN target_frequency VARCHAR(50) DEFAULT 'daily',
ADD COLUMN environmental_focus BOOLEAN DEFAULT FALSE,
ADD COLUMN habit_start_date DATE DEFAULT CURDATE(),
ADD COLUMN longest_streak INT DEFAULT 0,
ADD COLUMN total_completions INT DEFAULT 0,
ADD COLUMN success_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN automaticity_level DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN has_accountability_partner BOOLEAN DEFAULT FALSE,
ADD COLUMN habit_status VARCHAR(20) DEFAULT 'active',
ADD COLUMN total_points_earned INT DEFAULT 0,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create stored procedure for recording user activities
DELIMITER //
CREATE PROCEDURE RecordUserActivity(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_activity_category VARCHAR(50),
    IN p_activity_name VARCHAR(200),
    IN p_carbon_impact DECIMAL(10,3),
    IN p_base_points INT
)
BEGIN
    DECLARE v_streak_bonus INT DEFAULT 0;
    DECLARE v_engagement_score DECIMAL(8,2) DEFAULT 0.00;
    
    -- Calculate engagement score based on activity type and carbon impact
    SET v_engagement_score = CASE 
        WHEN p_activity_category = 'environmental' THEN p_base_points * 1.5
        WHEN p_activity_category = 'social' THEN p_base_points * 1.2
        ELSE p_base_points 
    END;
    
    -- Calculate streak bonus (simplified)
    SET v_streak_bonus = CASE 
        WHEN p_carbon_impact > 0 THEN ROUND(p_base_points * 0.1)
        ELSE 0
    END;
    
    -- Insert activity record
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_name,
        carbon_impact_kg, environmental_score, base_points, 
        streak_bonus, total_points, engagement_score, is_verified
    ) VALUES (
        p_user_id, p_activity_type, p_activity_category, p_activity_name,
        p_carbon_impact, ROUND(p_carbon_impact * 100), p_base_points,
        v_streak_bonus, p_base_points + v_streak_bonus, v_engagement_score, TRUE
    );
    
    -- Update or create streak record
    INSERT INTO user_streaks_advanced (user_id, streak_type, current_streak, total_activities, last_activity_date)
    VALUES (p_user_id, p_activity_type, 1, 1, CURDATE())
    ON DUPLICATE KEY UPDATE 
        current_streak = current_streak + 1,
        total_activities = total_activities + 1,
        last_activity_date = CURDATE();
        
END//
DELIMITER ;

-- Create stored procedure for calculating engagement scores
DELIMITER //
CREATE PROCEDURE CalculateEngagementScores(IN p_user_id INT, IN p_period VARCHAR(20))
BEGIN
    DECLARE v_activity_count INT DEFAULT 0;
    DECLARE v_avg_quality DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_overall_score DECIMAL(6,2) DEFAULT 0.00;
    
    -- Calculate metrics for the user
    SELECT 
        COUNT(*), 
        AVG(COALESCE(quality_score, 50)), 
        SUM(COALESCE(total_points, 0))
    INTO v_activity_count, v_avg_quality, v_total_points
    FROM user_activities_comprehensive 
    WHERE user_id = p_user_id 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
    
    -- Calculate overall engagement score
    SET v_overall_score = (v_activity_count * 0.4) + (v_avg_quality * 0.3) + (v_total_points * 0.003);
    
    -- Insert or update engagement score
    INSERT INTO user_engagement_scores (
        user_id, score_period, overall_engagement_score, 
        activity_frequency_score, quality_score, 
        period_start_date, period_end_date
    ) VALUES (
        p_user_id, p_period, v_overall_score,
        LEAST(v_activity_count * 2, 100), v_avg_quality,
        DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE()
    )
    ON DUPLICATE KEY UPDATE
        overall_engagement_score = v_overall_score,
        activity_frequency_score = LEAST(v_activity_count * 2, 100),
        quality_score = v_avg_quality,
        updated_at = CURRENT_TIMESTAMP;
        
END//
DELIMITER ;
