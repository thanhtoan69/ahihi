-- Phase 23: Simple Test Procedures
-- Environmental Platform Database

USE environmental_platform;

DELIMITER $$

-- Simple UpdateUserLevel
CREATE PROCEDURE UpdateUserLevel(
    IN p_user_id INT
)
BEGIN
    DECLARE v_green_points INT DEFAULT 0;
    DECLARE v_new_level INT DEFAULT 1;
    
    -- Get current green points using 'id' column
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
        updated_at = NOW()
    WHERE id = p_user_id;
    
    SELECT v_new_level as new_level, v_green_points as total_points;
    
END$$

-- Simple CalculatePointsAndRewards
CREATE PROCEDURE CalculatePointsAndRewards(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_carbon_impact DECIMAL(10,2) DEFAULT 0.00
)
BEGIN
    DECLARE v_points INT DEFAULT 10;
    
    -- Update user points
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_points,
        updated_at = NOW()
    WHERE id = p_user_id;
    
    SELECT v_points as points_awarded;
    
END$$

-- Simple ProcessWasteClassification
CREATE PROCEDURE ProcessWasteClassification(
    IN p_user_id INT,
    IN p_predicted_category VARCHAR(50),
    IN p_confidence_score DECIMAL(5,2),
    IN p_actual_category VARCHAR(50) DEFAULT NULL
)
BEGIN
    DECLARE v_points INT DEFAULT 15;
    
    -- Update user points
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_points,
        updated_at = NOW()
    WHERE id = p_user_id;
    
    SELECT v_points as points_earned;
    
END$$

DELIMITER ;

SELECT 'Simple test procedures created successfully!' as status;
