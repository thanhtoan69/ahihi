-- Phase 23: Final CheckAchievements Stored Procedure (Corrected)
-- Environmental Platform Database
-- Simple and compatible implementation

USE environmental_platform;

-- Drop existing procedure if it exists
DROP PROCEDURE IF EXISTS CheckAchievements;

DELIMITER $$

CREATE PROCEDURE CheckAchievements(
    IN p_user_id INT
)
BEGIN
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_current_level INT DEFAULT 1;
    DECLARE v_login_streak INT DEFAULT 0;
    DECLARE v_activity_count INT DEFAULT 0;
    DECLARE v_carbon_saved DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_achievement_id INT;
    DECLARE v_achievement_exists INT DEFAULT 0;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user stats
    SELECT 
        COALESCE(green_points, 0),
        COALESCE(user_level, 1),
        COALESCE(login_streak, 0),
        COALESCE(total_carbon_saved, 0.00)
    INTO v_total_points, v_current_level, v_login_streak, v_carbon_saved
    FROM users 
    WHERE id = p_user_id;
    
    -- Count user activities
    SELECT COUNT(*) INTO v_activity_count
    FROM user_activities_comprehensive
    WHERE user_id = p_user_id;
    
    -- Check for Points Milestones
    IF v_total_points >= 100 THEN
        SELECT achievement_id INTO v_achievement_id FROM achievements_enhanced WHERE achievement_name = 'Points Master' LIMIT 1;
        IF v_achievement_id IS NOT NULL THEN
            SELECT COUNT(*) INTO v_achievement_exists 
            FROM user_achievements 
            WHERE user_id = p_user_id AND achievement_id = v_achievement_id;
            
            IF v_achievement_exists = 0 THEN
                INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at)
                VALUES (p_user_id, v_achievement_id, NOW());
            END IF;
        END IF;
    END IF;
    
    -- Check for Level Achievements
    IF v_current_level >= 3 THEN
        SELECT achievement_id INTO v_achievement_id FROM achievements_enhanced WHERE achievement_name = 'Level Expert' LIMIT 1;
        IF v_achievement_id IS NOT NULL THEN
            SELECT COUNT(*) INTO v_achievement_exists 
            FROM user_achievements 
            WHERE user_id = p_user_id AND achievement_id = v_achievement_id;
            
            IF v_achievement_exists = 0 THEN
                INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at)
                VALUES (p_user_id, v_achievement_id, NOW());
            END IF;
        END IF;
    END IF;
    
    -- Check for Streak Achievements
    IF v_login_streak >= 7 THEN
        SELECT achievement_id INTO v_achievement_id FROM achievements_enhanced WHERE achievement_name = 'Streak Champion' LIMIT 1;
        IF v_achievement_id IS NOT NULL THEN
            SELECT COUNT(*) INTO v_achievement_exists 
            FROM user_achievements 
            WHERE user_id = p_user_id AND achievement_id = v_achievement_id;
            
            IF v_achievement_exists = 0 THEN
                INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at)
                VALUES (p_user_id, v_achievement_id, NOW());
            END IF;
        END IF;
    END IF;
    
    -- Check for Activity Count Achievements
    IF v_activity_count >= 10 THEN
        SELECT achievement_id INTO v_achievement_id FROM achievements_enhanced WHERE achievement_name = 'Activity Hero' LIMIT 1;
        IF v_achievement_id IS NOT NULL THEN
            SELECT COUNT(*) INTO v_achievement_exists 
            FROM user_achievements 
            WHERE user_id = p_user_id AND achievement_id = v_achievement_id;
            
            IF v_achievement_exists = 0 THEN
                INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at)
                VALUES (p_user_id, v_achievement_id, NOW());
            END IF;
        END IF;
    END IF;
    
    -- Check for Carbon Saving Achievements
    IF v_carbon_saved >= 50.00 THEN
        SELECT achievement_id INTO v_achievement_id FROM achievements_enhanced WHERE achievement_name = 'Carbon Saver' LIMIT 1;
        IF v_achievement_id IS NOT NULL THEN
            SELECT COUNT(*) INTO v_achievement_exists 
            FROM user_achievements 
            WHERE user_id = p_user_id AND achievement_id = v_achievement_id;
            
            IF v_achievement_exists = 0 THEN
                INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at)
                VALUES (p_user_id, v_achievement_id, NOW());
            END IF;
        END IF;
    END IF;
    
    COMMIT;
    
    -- Return newly earned achievements count
    SELECT COUNT(*) as new_achievements_count
    FROM user_achievements ua
    JOIN achievements_enhanced ae ON ua.achievement_id = ae.achievement_id
    WHERE ua.user_id = p_user_id 
    AND DATE(ua.earned_at) = CURDATE();
    
END$$

DELIMITER ;

-- Insert sample achievements if they don't exist
INSERT IGNORE INTO achievements_enhanced (achievement_name, achievement_slug, achievement_code, title_vi, title_en, description_vi, description_en, points_reward, icon_name, achievement_type, created_at) VALUES
('Points Master', 'points-master', 'PM001', 'Chuyên gia điểm', 'Points Master', 'Đạt được 100+ điểm xanh', 'Earned 100+ green points', 100, 'fa-star', 'one_time', NOW()),
('Level Expert', 'level-expert', 'LE001', 'Chuyên gia cấp độ', 'Level Expert', 'Đạt cấp độ 3 hoặc cao hơn', 'Reached level 3 or higher', 50, 'fa-trophy', 'one_time', NOW()),
('Streak Champion', 'streak-champion', 'SC001', 'Nhà vô địch streak', 'Streak Champion', 'Duy trì streak đăng nhập 7+ ngày', 'Maintained 7+ day login streak', 75, 'fa-fire', 'one_time', NOW()),
('Activity Hero', 'activity-hero', 'AH001', 'Anh hùng hoạt động', 'Activity Hero', 'Hoàn thành 10+ hoạt động', 'Completed 10+ activities', 25, 'fa-heart', 'one_time', NOW()),
('Carbon Saver', 'carbon-saver', 'CS001', 'Người tiết kiệm carbon', 'Carbon Saver', 'Tiết kiệm 50+ kg carbon', 'Saved 50+ kg of carbon', 100, 'fa-leaf', 'one_time', NOW());

SELECT 'CheckAchievements procedure created successfully!' as status;
