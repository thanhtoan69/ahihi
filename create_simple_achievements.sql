USE environmental_platform;

DELIMITER $$

CREATE PROCEDURE CheckAchievements(
    IN p_user_id INT,
    IN p_trigger_type VARCHAR(50)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    SELECT COUNT(*) INTO v_count FROM users WHERE user_id = p_user_id;
    SELECT v_count as user_found, p_trigger_type as trigger_type;
END$$

DELIMITER ;
