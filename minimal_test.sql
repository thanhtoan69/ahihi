-- Minimal test procedure
USE environmental_platform;

DROP PROCEDURE IF EXISTS MinimalTest;

DELIMITER $$

CREATE PROCEDURE MinimalTest(
    IN p_user_id INT
)
BEGIN
    SELECT 'Test successful' as result, p_user_id as input_id;
END$$

DELIMITER ;

-- Test it
CALL MinimalTest(1);
