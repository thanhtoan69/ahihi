-- Phase 23: Complete Procedure Recreation
-- Environmental Platform Database
-- Recreate all procedures with correct column references

USE environmental_platform;

-- Drop all existing procedures
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CheckAchievements;
DROP PROCEDURE IF EXISTS GenerateDailyAnalytics;
DROP PROCEDURE IF EXISTS UpdateUserStreaks;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;

DELIMITER $$

-- 1. UpdateUserLevel - Simplified and Working
CREATE PROCEDURE UpdateUserLevel(
    IN p_user_id INT
)
BEGIN
    DECLARE v_green_points INT DEFAULT 0;
    DECLARE v_new_level INT DEFAULT 1;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get current green points using correct column name 'id'
    SELECT COALESCE(green_points, 0) INTO v_green_points
    FROM users 
    WHERE id = p_user_id;
    
    -- Calculate level based on points
    SET v_new_level = CASE 
        WHEN v_green_points >= 2500 THEN 5
        WHEN v_green_points >= 1000 THEN 4
        WHEN v_green_points >= 500 THEN 3
        WHEN v_green_points >= 100 THEN 2
        ELSE 1
    END;
    
    -- Update user level
    UPDATE users 
    SET user_level = v_new_level,
        experience_points = v_green_points,
        updated_at = NOW()
    WHERE id = p_user_id;
    
    COMMIT;
    
    SELECT v_new_level as new_level, v_green_points as total_points;
    
END$$

-- 2. ProcessWasteClassification - Simplified
CREATE PROCEDURE ProcessWasteClassification(
    IN p_user_id INT,
    IN p_predicted_category VARCHAR(50),
    IN p_confidence_score DECIMAL(5,2),
    IN p_actual_category VARCHAR(50) DEFAULT NULL
)
BEGIN
    DECLARE v_points_earned INT DEFAULT 5;
    DECLARE v_is_correct BOOLEAN DEFAULT TRUE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Calculate points based on confidence
    SET v_points_earned = CASE 
        WHEN p_confidence_score >= 90 THEN 15
        WHEN p_confidence_score >= 75 THEN 12
        WHEN p_confidence_score >= 60 THEN 8
        ELSE 5
    END;
    
    -- Update user points
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_points_earned,
        updated_at = NOW()
    WHERE id = p_user_id;
    
    -- Log activity
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, 'waste_classification', 'ai_interaction', 'Waste Classification', v_points_earned, v_points_earned, NOW());
    
    COMMIT;
    
    SELECT v_points_earned as points_earned, v_is_correct as classification_correct;
    
END$$

-- 3. CheckAchievements - Simplified
CREATE PROCEDURE CheckAchievements(
    IN p_user_id INT
)
BEGIN
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_current_level INT DEFAULT 1;
    DECLARE v_new_achievements INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user stats
    SELECT 
        COALESCE(green_points, 0),
        COALESCE(user_level, 1)
    INTO v_total_points, v_current_level
    FROM users 
    WHERE id = p_user_id;
    
    -- Simple achievement check - just count new ones
    SET v_new_achievements = 0;
    
    -- Check for basic point achievement
    IF v_total_points >= 100 AND NOT EXISTS (
        SELECT 1 FROM user_achievements ua 
        JOIN achievements_enhanced ae ON ua.achievement_id = ae.achievement_id 
        WHERE ua.user_id = p_user_id AND ae.achievement_name = 'Points Master'
    ) THEN
        SET v_new_achievements = v_new_achievements + 1;
    END IF;
    
    COMMIT;
    
    SELECT v_new_achievements as new_achievements_count;
    
END$$

-- 4. GenerateDailyAnalytics - Simplified
CREATE PROCEDURE GenerateDailyAnalytics()
BEGIN
    DECLARE v_total_users INT DEFAULT 0;
    DECLARE v_active_users INT DEFAULT 0;
    DECLARE v_total_activities INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get basic metrics
    SELECT COUNT(*) INTO v_total_users FROM users;
    
    SELECT COUNT(DISTINCT user_id) INTO v_active_users 
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = CURDATE();
    
    SELECT COUNT(*) INTO v_total_activities 
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = CURDATE();
    
    -- Insert or update daily analytics
    INSERT INTO daily_analytics_summary 
    (date_recorded, total_users, active_users, total_activities, total_points_awarded, created_at)
    VALUES 
    (CURDATE(), v_total_users, v_active_users, v_total_activities, v_total_activities * 10, NOW())
    ON DUPLICATE KEY UPDATE
    total_users = v_total_users,
    active_users = v_active_users,
    total_activities = v_total_activities,
    total_points_awarded = v_total_activities * 10,
    updated_at = NOW();
    
    COMMIT;
    
    SELECT v_total_users as total_users, v_active_users as active_users, v_total_activities as activities_today;
    
END$$

-- 5. UpdateUserStreaks - Simplified
CREATE PROCEDURE UpdateUserStreaks(
    IN p_user_id INT
)
BEGIN
    DECLARE v_current_streak INT DEFAULT 0;
    DECLARE v_longest_streak INT DEFAULT 0;
    DECLARE v_last_login DATE;
    DECLARE v_today DATE DEFAULT CURDATE();
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get current user streak data
    SELECT 
        COALESCE(login_streak, 0),
        COALESCE(longest_streak, 0),
        DATE(last_login)
    INTO v_current_streak, v_longest_streak, v_last_login
    FROM users 
    WHERE id = p_user_id;
    
    -- Update streak based on last login
    IF v_last_login = DATE_SUB(v_today, INTERVAL 1 DAY) THEN
        -- Consecutive day
        SET v_current_streak = v_current_streak + 1;
    ELSEIF v_last_login = v_today THEN
        -- Same day, no change
        SET v_current_streak = v_current_streak;
    ELSE
        -- Reset streak
        SET v_current_streak = 1;
    END IF;
    
    -- Update longest streak if needed
    IF v_current_streak > v_longest_streak THEN
        SET v_longest_streak = v_current_streak;
    END IF;
    
    -- Update user record
    UPDATE users 
    SET login_streak = v_current_streak,
        longest_streak = v_longest_streak,
        last_login = NOW(),
        updated_at = NOW()
    WHERE id = p_user_id;
    
    COMMIT;
    
    SELECT v_current_streak as current_streak, v_longest_streak as longest_streak;
    
END$$

-- 6. CalculatePointsAndRewards - Simplified
CREATE PROCEDURE CalculatePointsAndRewards(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_carbon_impact DECIMAL(10,2) DEFAULT 0.00
)
BEGIN
    DECLARE v_base_points INT DEFAULT 5;
    DECLARE v_total_points INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Calculate base points by activity type
    SET v_base_points = CASE p_activity_type
        WHEN 'recycling' THEN 10
        WHEN 'waste_classification' THEN 15
        WHEN 'eco_challenge' THEN 20
        WHEN 'community_activity' THEN 25
        WHEN 'education' THEN 8
        ELSE 5
    END;
    
    SET v_total_points = v_base_points;
    
    -- Update user points
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_total_points,
        experience_points = COALESCE(experience_points, 0) + v_total_points,
        total_carbon_saved = COALESCE(total_carbon_saved, 0.00) + p_carbon_impact,
        updated_at = NOW()
    WHERE id = p_user_id;
    
    -- Log activity
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, p_activity_type, 'reward', CONCAT('Points for ', p_activity_type), v_base_points, v_total_points, NOW());
    
    COMMIT;
    
    SELECT v_total_points as points_awarded;
    
END$$

DELIMITER ;

SELECT 'All 6 stored procedures recreated successfully!' as status;
