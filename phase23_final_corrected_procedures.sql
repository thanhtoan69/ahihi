-- Phase 23: Final Corrected Stored Procedures
-- Environmental Platform Database
-- Using correct column name 'user_id' instead of 'id'

USE environmental_platform;

-- Clean up existing procedures
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;
DROP PROCEDURE IF EXISTS CheckAchievements;
DROP PROCEDURE IF EXISTS UpdateUserStreaks;

DELIMITER $$

-- 1. UpdateUserLevel - Corrected with user_id
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
    
    SELECT COALESCE(green_points, 0) INTO v_green_points
    FROM users 
    WHERE user_id = p_user_id;
    
    SET v_new_level = CASE 
        WHEN v_green_points >= 2500 THEN 5
        WHEN v_green_points >= 1000 THEN 4
        WHEN v_green_points >= 500 THEN 3
        WHEN v_green_points >= 100 THEN 2
        ELSE 1
    END;
    
    UPDATE users 
    SET user_level = v_new_level,
        experience_points = v_green_points,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    COMMIT;
    
    SELECT v_new_level as new_level, v_green_points as total_points;
    
END$$

-- 2. ProcessWasteClassification - Corrected with user_id
CREATE PROCEDURE ProcessWasteClassification(
    IN p_user_id INT,
    IN p_predicted_category VARCHAR(50),
    IN p_confidence_score DECIMAL(5,2),
    IN p_actual_category VARCHAR(50) DEFAULT NULL
)
BEGIN
    DECLARE v_points_earned INT DEFAULT 5;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    SET v_points_earned = CASE 
        WHEN p_confidence_score >= 90 THEN 15
        WHEN p_confidence_score >= 75 THEN 12
        WHEN p_confidence_score >= 60 THEN 8
        ELSE 5
    END;
    
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_points_earned,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, 'waste_classification', 'ai_interaction', 'AI Waste Classification', v_points_earned, v_points_earned, NOW());
    
    COMMIT;
    
    SELECT v_points_earned as points_earned, p_confidence_score as confidence_score;
    
END$$

-- 3. CalculatePointsAndRewards - Corrected with user_id
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
    
    SET v_base_points = CASE p_activity_type
        WHEN 'recycling' THEN 10
        WHEN 'waste_classification' THEN 15
        WHEN 'eco_challenge' THEN 20
        WHEN 'community_activity' THEN 25
        WHEN 'education' THEN 8
        ELSE 5
    END;
    
    SET v_total_points = v_base_points;
    
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_total_points,
        experience_points = COALESCE(experience_points, 0) + v_total_points,
        total_carbon_saved = COALESCE(total_carbon_saved, 0.00) + p_carbon_impact,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, p_activity_type, 'reward', CONCAT('Points for ', p_activity_type), v_base_points, v_total_points, NOW());
    
    COMMIT;
    
    SELECT v_total_points as points_awarded, v_base_points as base_points;
    
END$$

-- 4. CheckAchievements - Corrected with user_id
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
    
    SELECT 
        COALESCE(green_points, 0),
        COALESCE(user_level, 1)
    INTO v_total_points, v_current_level
    FROM users 
    WHERE user_id = p_user_id;
    
    SET v_new_achievements = 0;
    
    IF v_total_points >= 100 THEN
        SET v_new_achievements = v_new_achievements + 1;
    END IF;
    
    COMMIT;
    
    SELECT v_new_achievements as new_achievements_count;
    
END$$

-- 5. UpdateUserStreaks - Corrected with user_id
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
    
    SELECT 
        COALESCE(login_streak, 0),
        COALESCE(longest_streak, 0),
        DATE(last_login)
    INTO v_current_streak, v_longest_streak, v_last_login
    FROM users 
    WHERE user_id = p_user_id;
    
    IF v_last_login = DATE_SUB(v_today, INTERVAL 1 DAY) THEN
        SET v_current_streak = v_current_streak + 1;
    ELSEIF v_last_login = v_today THEN
        SET v_current_streak = v_current_streak;
    ELSE
        SET v_current_streak = 1;
    END IF;
    
    IF v_current_streak > v_longest_streak THEN
        SET v_longest_streak = v_current_streak;
    END IF;
    
    UPDATE users 
    SET login_streak = v_current_streak,
        longest_streak = v_longest_streak,
        last_login = NOW(),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    COMMIT;
    
    SELECT v_current_streak as current_streak, v_longest_streak as longest_streak;
    
END$$

DELIMITER ;

-- Verification
SELECT 'Phase 23 Final Corrected Procedures Created Successfully!' as status;
SELECT 'All procedures now use correct user_id column name.' as note;
