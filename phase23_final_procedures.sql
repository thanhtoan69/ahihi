-- Phase 23: Final Working Stored Procedures
-- Simplified and tested for compatibility
USE environmental_platform;

-- Create missing tables if needed
CREATE TABLE IF NOT EXISTS daily_analytics_summary (
    analytics_date DATE PRIMARY KEY,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_activities INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Drop all existing procedures first
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CheckAchievements;
DROP PROCEDURE IF EXISTS GenerateDailyAnalytics;
DROP PROCEDURE IF EXISTS UpdateUserStreaks;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;

DELIMITER //

-- 1. Simple UpdateUserLevel procedure
CREATE PROCEDURE UpdateUserLevel(IN p_user_id INT)
BEGIN
    DECLARE v_points INT DEFAULT 0;
    DECLARE v_new_level INT DEFAULT 1;
    
    SELECT COALESCE(green_points, 0) INTO v_points FROM users WHERE user_id = p_user_id;
    
    SET v_new_level = CASE 
        WHEN v_points < 100 THEN 1
        WHEN v_points < 500 THEN 2
        WHEN v_points < 1000 THEN 3
        WHEN v_points < 2500 THEN 4
        ELSE 5
    END;
    
    UPDATE users SET user_level = v_new_level, updated_at = NOW() WHERE user_id = p_user_id;
    
    SELECT v_new_level as new_level, v_points as total_points;
END//

-- 2. Simple ProcessWasteClassification procedure
CREATE PROCEDURE ProcessWasteClassification(
    IN p_session_id INT,
    IN p_user_id INT,
    IN p_image_url VARCHAR(500),
    IN p_predicted_category VARCHAR(100),
    IN p_confidence DECIMAL(5,4),
    IN p_actual_category VARCHAR(100),
    IN p_processing_time_ms INT
)
BEGIN
    DECLARE v_points INT DEFAULT 0;
    DECLARE v_is_correct BOOLEAN;
    
    SET v_is_correct = (p_predicted_category = p_actual_category);
    SET v_points = IF(v_is_correct, 15, 5);
    
    UPDATE users SET green_points = green_points + v_points WHERE user_id = p_user_id;
    
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_name, 
        base_points, total_points
    ) VALUES (
        p_user_id, 'waste_classification', 'environmental',
        CONCAT('Classified: ', p_predicted_category), v_points, v_points
    );
    
    SELECT v_points as points_earned, v_is_correct as is_correct;
END//

-- 3. Simple CheckAchievements procedure
CREATE PROCEDURE CheckAchievements(IN p_user_id INT, IN p_trigger_type VARCHAR(50))
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_bonus INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_count 
    FROM user_activities_comprehensive 
    WHERE user_id = p_user_id AND activity_type = p_trigger_type;
    
    IF v_count >= 10 THEN SET v_bonus = 100; END IF;
    IF v_count >= 25 THEN SET v_bonus = 250; END IF;
    IF v_count >= 50 THEN SET v_bonus = 500; END IF;
    
    IF v_bonus > 0 THEN
        UPDATE users SET green_points = green_points + v_bonus WHERE user_id = p_user_id;
    END IF;
    
    SELECT v_count as total_activities, v_bonus as bonus_points;
END//

-- 4. Simple GenerateDailyAnalytics procedure
CREATE PROCEDURE GenerateDailyAnalytics(IN p_analytics_date DATE)
BEGIN
    DECLARE v_total_users INT;
    DECLARE v_active_users INT;
    DECLARE v_new_users INT;
    DECLARE v_total_activities INT;
    
    IF p_analytics_date IS NULL THEN SET p_analytics_date = CURDATE(); END IF;
    
    SELECT COUNT(*) INTO v_total_users FROM users WHERE is_active = 1;
    SELECT COUNT(DISTINCT user_id) INTO v_active_users FROM user_activities_comprehensive WHERE DATE(created_at) = p_analytics_date;
    SELECT COUNT(*) INTO v_new_users FROM users WHERE DATE(created_at) = p_analytics_date;
    SELECT COUNT(*) INTO v_total_activities FROM user_activities_comprehensive WHERE DATE(created_at) = p_analytics_date;
    
    INSERT INTO daily_analytics_summary (
        analytics_date, total_users, active_users, new_users, total_activities
    ) VALUES (
        p_analytics_date, v_total_users, v_active_users, v_new_users, v_total_activities
    ) ON DUPLICATE KEY UPDATE
        total_users = v_total_users,
        active_users = v_active_users,
        new_users = v_new_users,
        total_activities = v_total_activities,
        updated_at = NOW();
    
    SELECT p_analytics_date as date, v_total_users as total_users, v_active_users as active_users, v_new_users as new_users, v_total_activities as total_activities;
END//

-- 5. Simple UpdateUserStreaks procedure
CREATE PROCEDURE UpdateUserStreaks(IN p_user_id INT)
BEGIN
    DECLARE v_streak INT DEFAULT 0;
    
    SELECT COALESCE(login_streak, 0) INTO v_streak FROM users WHERE user_id = p_user_id;
    SET v_streak = v_streak + 1;
    
    UPDATE users SET 
        login_streak = v_streak,
        longest_streak = GREATEST(COALESCE(longest_streak, 0), v_streak),
        last_login = NOW()
    WHERE user_id = p_user_id;
    
    SELECT v_streak as current_streak;
END//

-- 6. Simple CalculatePointsAndRewards procedure
CREATE PROCEDURE CalculatePointsAndRewards(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_activity_value INT,
    IN p_metadata JSON
)
BEGIN
    DECLARE v_points INT DEFAULT 5;
    
    CASE p_activity_type
        WHEN 'article_read' THEN SET v_points = 5;
        WHEN 'quiz_complete' THEN SET v_points = 25;
        WHEN 'waste_classify' THEN SET v_points = 10;
        WHEN 'forum_post' THEN SET v_points = 15;
        ELSE SET v_points = 5;
    END CASE;
    
    IF p_activity_value > 0 THEN SET v_points = v_points * p_activity_value; END IF;
    
    UPDATE users SET green_points = green_points + v_points WHERE user_id = p_user_id;
    
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_name, 
        base_points, total_points
    ) VALUES (
        p_user_id, p_activity_type, 'reward',
        CONCAT('Points for: ', p_activity_type), v_points, v_points
    );
    
    SELECT v_points as points_awarded;
END//

DELIMITER ;

SELECT 'Phase 23: All 6 Stored Procedures Created Successfully!' as result;
