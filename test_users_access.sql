-- Test users table access from procedure
USE environmental_platform;

DROP PROCEDURE IF EXISTS TestUsersAccess;

DELIMITER $$

CREATE PROCEDURE TestUsersAccess(
    IN p_user_id INT
)
BEGIN
    DECLARE v_points INT DEFAULT 0;
    DECLARE v_username VARCHAR(50) DEFAULT '';
    
    SELECT green_points, username 
    INTO v_points, v_username
    FROM users 
    WHERE user_id = p_user_id;
    
    SELECT v_points as user_points, v_username as username, p_user_id as input_id;
END$$

DELIMITER ;

-- Test it
CALL TestUsersAccess(1);
