-- Phase 23: Final Working Stored Procedures Implementation
-- Environmental Platform Database
-- Complete Business Logic Implementation

USE environmental_platform;

-- Clean up existing procedures
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;

DELIMITER $$

-- 1. UpdateUserLevel - Clean and Simple
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
    WHERE id = p_user_id;
    
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
    WHERE id = p_user_id;
    
    COMMIT;
    
    SELECT v_new_level as new_level, v_green_points as total_points;
    
END$$

-- 2. ProcessWasteClassification - Clean and Simple
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
    WHERE id = p_user_id;
    
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, 'waste_classification', 'ai_interaction', 'AI Waste Classification', v_points_earned, v_points_earned, NOW());
    
    COMMIT;
    
    SELECT v_points_earned as points_earned, p_confidence_score as confidence_score;
    
END$$

-- 3. CalculatePointsAndRewards - Clean and Simple
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
    WHERE id = p_user_id;
    
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, p_activity_type, 'reward', CONCAT('Points for ', p_activity_type), v_base_points, v_total_points, NOW());
    
    COMMIT;
    
    SELECT v_total_points as points_awarded, v_base_points as base_points;
    
END$$

DELIMITER ;

-- Verification
SELECT 'Phase 23 Final Procedures Created Successfully!' as status;

-- Show all procedures
SELECT 'Current Stored Procedures:' as info;
SELECT Name as procedure_name, Created as created_at
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
ORDER BY Name;
