-- Phase 23: Corrected Stored Procedures
-- Environmental Platform Database
-- Fixed column references for compatibility

USE environmental_platform;

-- Drop and recreate UpdateUserLevel
DROP PROCEDURE IF EXISTS UpdateUserLevel;

DELIMITER $$

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
    
    -- Get current green points
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
    
    -- Update user level and experience points
    UPDATE users 
    SET user_level = v_new_level,
        experience_points = v_green_points,
        updated_at = NOW()
    WHERE id = p_user_id;
    
    COMMIT;
    
    SELECT v_new_level as new_level, v_green_points as total_points;
    
END$$

-- Drop and recreate CalculatePointsAndRewards
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;

CREATE PROCEDURE CalculatePointsAndRewards(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_carbon_impact DECIMAL(10,2) DEFAULT 0.00
)
BEGIN
    DECLARE v_base_points INT DEFAULT 5;
    DECLARE v_bonus_points INT DEFAULT 0;
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_user_level INT DEFAULT 1;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user level for bonus calculation
    SELECT COALESCE(user_level, 1) INTO v_user_level
    FROM users 
    WHERE id = p_user_id;
    
    -- Calculate base points by activity type
    SET v_base_points = CASE p_activity_type
        WHEN 'recycling' THEN 10
        WHEN 'waste_classification' THEN 15
        WHEN 'eco_challenge' THEN 20
        WHEN 'community_activity' THEN 25
        WHEN 'education' THEN 8
        ELSE 5
    END;
    
    -- Level bonus (10% per level above 1)
    SET v_bonus_points = FLOOR(v_base_points * (v_user_level - 1) * 0.1);
    SET v_total_points = v_base_points + v_bonus_points;
    
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
    (p_user_id, p_activity_type, 'points_reward', CONCAT('Points for ', p_activity_type), v_base_points, v_total_points, NOW());
    
    COMMIT;
    
    SELECT v_total_points as points_awarded, v_base_points as base_points, v_bonus_points as bonus_points;
    
END$$

DELIMITER ;

SELECT 'Corrected stored procedures created successfully!' as status;
