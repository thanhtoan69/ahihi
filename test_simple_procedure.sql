-- Test simple procedure creation
USE environmental_platform;

DROP PROCEDURE IF EXISTS TestUserAccess;

DELIMITER $$

CREATE PROCEDURE TestUserAccess(
    IN p_user_id INT
)
BEGIN
    DECLARE v_points INT DEFAULT 0;
    
    SELECT green_points INTO v_points 
    FROM users 
    WHERE user_id = p_user_id;
    
    SELECT v_points as user_points;
END$$

DELIMITER ;

-- Test the procedure
CALL TestUserAccess(1);
