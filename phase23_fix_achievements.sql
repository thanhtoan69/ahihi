-- Fix CheckAchievements procedure with simple syntax
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
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Check achievements based on trigger type
    CASE p_trigger_type
        WHEN 'waste_classification' THEN
            -- Check waste classification achievements
            SELECT COUNT(*) INTO v_user_value
            FROM waste_classification_results 
            WHERE user_id = p_user_id AND is_correct = TRUE;
            
            -- Award achievements for 10, 50, 100 correct classifications
            IF v_user_value >= 10 AND NOT EXISTS (
                SELECT 1 FROM user_achievements_enhanced 
                WHERE user_id = p_user_id AND achievement_id = 1
            ) THEN
                INSERT INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (p_user_id, 1, v_user_value, 'unlocked', NOW(), 150);
                SET v_points_earned = v_points_earned + 150;
            END IF;
            
            IF v_user_value >= 50 AND NOT EXISTS (
                SELECT 1 FROM user_achievements_enhanced 
                WHERE user_id = p_user_id AND achievement_id = 2
            ) THEN
                INSERT INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (p_user_id, 2, v_user_value, 'unlocked', NOW(), 500);
                SET v_points_earned = v_points_earned + 500;
            END IF;
            
        WHEN 'article_read' THEN
            -- Check article reading achievements
            SELECT COUNT(*) INTO v_user_value
            FROM article_interactions 
            WHERE user_id = p_user_id AND interaction_type = 'view';
            
            IF v_user_value >= 5 AND NOT EXISTS (
                SELECT 1 FROM user_achievements_enhanced 
                WHERE user_id = p_user_id AND achievement_id = 3
            ) THEN
                INSERT INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (p_user_id, 3, v_user_value, 'unlocked', NOW(), 100);
                SET v_points_earned = v_points_earned + 100;
            END IF;
            
        WHEN 'quiz_completed' THEN
            -- Check quiz completion achievements
            SELECT COUNT(*) INTO v_user_value
            FROM quiz_sessions 
            WHERE user_id = p_user_id AND status = 'completed';
            
            IF v_user_value >= 3 AND NOT EXISTS (
                SELECT 1 FROM user_achievements_enhanced 
                WHERE user_id = p_user_id AND achievement_id = 4
            ) THEN
                INSERT INTO user_achievements_enhanced (
                    user_id, achievement_id, current_progress, 
                    unlock_status, unlocked_at, points_earned
                ) VALUES (p_user_id, 4, v_user_value, 'unlocked', NOW(), 200);
                SET v_points_earned = v_points_earned + 200;
            END IF;
            
    END CASE;
    
    -- Award points if any achievements were unlocked
    IF v_points_earned > 0 THEN
        UPDATE users 
        SET green_points = green_points + v_points_earned,
            experience_points = experience_points + ROUND(v_points_earned * 0.5),
            updated_at = NOW()
        WHERE user_id = p_user_id;
        
        -- Create notification
        INSERT INTO notifications (
            user_id, notification_type, title, message,
            is_read, created_at
        ) VALUES (
            p_user_id, 'achievement', 'Thành tựu mới!',
            CONCAT('Bạn đã mở khóa thành tựu mới và nhận được ', v_points_earned, ' điểm xanh!'),
            FALSE, NOW()
        );
    END IF;
    
    COMMIT;
    
    -- Return result
    SELECT 
        v_user_value as user_progress,
        v_points_earned as points_earned,
        'SUCCESS' as status;
        
END//

DELIMITER ;

-- Verify procedure was created
SELECT 'CheckAchievements procedure created successfully!' as result;
