USE environmental_platform;

DELIMITER //

DROP PROCEDURE IF EXISTS CheckAchievements//

CREATE PROCEDURE CheckAchievements(
    IN p_user_id INT,
    IN p_trigger_type VARCHAR(50)
)
BEGIN
    DECLARE v_user_value INT DEFAULT 0;
    DECLARE v_points_earned INT DEFAULT 0;
    
    START TRANSACTION;
    
    CASE p_trigger_type
        WHEN 'waste_classification' THEN
            SELECT COUNT(*) INTO v_user_value
            FROM waste_classification_results 
            WHERE user_id = p_user_id AND is_correct = TRUE;
            
            IF v_user_value >= 10 THEN
                INSERT IGNORE INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (
                    p_user_id, 1, v_user_value, 'unlocked', NOW(), 150
                );
                SET v_points_earned = 150;
            END IF;
            
        WHEN 'article_read' THEN
            SELECT COUNT(*) INTO v_user_value
            FROM article_interactions 
            WHERE user_id = p_user_id AND interaction_type = 'view';
            
            IF v_user_value >= 5 THEN
                INSERT IGNORE INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (
                    p_user_id, 3, v_user_value, 'unlocked', NOW(), 100
                );
                SET v_points_earned = 100;
            END IF;
            
        WHEN 'quiz_completed' THEN
            SELECT COUNT(*) INTO v_user_value
            FROM quiz_sessions 
            WHERE user_id = p_user_id AND status = 'completed';
            
            IF v_user_value >= 3 THEN
                INSERT IGNORE INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (
                    p_user_id, 4, v_user_value, 'unlocked', NOW(), 200
                );
                SET v_points_earned = 200;
            END IF;
    END CASE;
    
    IF v_points_earned > 0 THEN
        UPDATE users 
        SET green_points = green_points + v_points_earned,
            experience_points = experience_points + ROUND(v_points_earned * 0.5),
            updated_at = NOW()
        WHERE user_id = p_user_id;
    END IF;
    
    COMMIT;
    
    SELECT v_user_value as user_progress, v_points_earned as points_earned, 'SUCCESS' as status;
        
END//

DELIMITER ;
