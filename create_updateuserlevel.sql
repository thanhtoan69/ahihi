-- Phase 23: Create All Required Procedures One by One
-- Environmental Platform Database

USE environmental_platform;

-- 1. Drop all existing procedures to start fresh
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;
DROP PROCEDURE IF EXISTS CheckAchievements;
DROP PROCEDURE IF EXISTS UpdateUserStreaks;
DROP PROCEDURE IF EXISTS GenerateDailyAnalytics;

-- Test creating UpdateUserLevel first
DELIMITER $$

CREATE PROCEDURE UpdateUserLevel(
    IN p_user_id INT
)
BEGIN
    DECLARE v_green_points INT DEFAULT 0;
    DECLARE v_new_level INT DEFAULT 1;
    
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
    
    SELECT v_new_level as new_level, v_green_points as total_points;
    
END$$

DELIMITER ;

SELECT 'UpdateUserLevel created successfully!' as status;
