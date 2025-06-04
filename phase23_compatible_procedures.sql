-- Phase 23: Compatible Stored Procedures for Environmental Platform
-- Created for existing database structure
USE environmental_platform;

-- Drop existing procedures
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CheckAchievements;
DROP PROCEDURE IF EXISTS GenerateDailyAnalytics;
DROP PROCEDURE IF EXISTS UpdateUserStreaks;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;

DELIMITER //

-- ----------------------------------------------------------------
-- 1. UPDATE USER LEVEL PROCEDURE (Compatible Version)
-- ----------------------------------------------------------------
CREATE PROCEDURE UpdateUserLevel(IN p_user_id INT)
BEGIN
    DECLARE v_green_points INT DEFAULT 0;
    DECLARE v_current_level INT DEFAULT 1;
    DECLARE v_new_level INT DEFAULT 1;
    DECLARE v_level_changed BOOLEAN DEFAULT FALSE;
    
    -- Get current user stats
    SELECT COALESCE(green_points, 0), COALESCE(user_level, 1)
    INTO v_green_points, v_current_level
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate new level based on points
    -- Level 1: 0-99 points, Level 2: 100-499, Level 3: 500-999, etc.
    SET v_new_level = CASE 
        WHEN v_green_points < 100 THEN 1
        WHEN v_green_points < 500 THEN 2
        WHEN v_green_points < 1000 THEN 3
        WHEN v_green_points < 2500 THEN 4
        WHEN v_green_points < 5000 THEN 5
        WHEN v_green_points < 10000 THEN 6
        WHEN v_green_points < 25000 THEN 7
        WHEN v_green_points < 50000 THEN 8
        WHEN v_green_points < 100000 THEN 9
        ELSE 10
    END;
    
    -- Check if level changed
    IF v_new_level > v_current_level THEN
        SET v_level_changed = TRUE;
        
        -- Update user level and experience
        UPDATE users 
        SET user_level = v_new_level,
            experience_points = v_green_points,
            updated_at = NOW()
        WHERE user_id = p_user_id;
        
        -- Log level up activity
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, activity_name,
            base_points, total_points, created_at
        ) VALUES (
            p_user_id, 'level_up', 'achievement', 
            CONCAT('Level up from ', v_current_level, ' to ', v_new_level),
            (v_new_level - v_current_level) * 100,
            (v_new_level - v_current_level) * 100,
            NOW()
        );
    ELSE
        -- Update experience points regardless
        UPDATE users 
        SET experience_points = v_green_points,
            updated_at = NOW()
        WHERE user_id = p_user_id;
    END IF;
    
    -- Return result
    SELECT 
        v_current_level as old_level,
        v_new_level as new_level,
        v_level_changed as level_changed,
        v_green_points as total_points;
        
END//

-- ----------------------------------------------------------------
-- 2. PROCESS WASTE CLASSIFICATION PROCEDURE (Compatible Version)
-- ----------------------------------------------------------------
CREATE PROCEDURE ProcessWasteClassification(
    IN p_session_id INT,
    IN p_user_id INT,
    IN p_image_url VARCHAR(500),
    IN p_predicted_category VARCHAR(100),
    IN p_confidence DECIMAL(5,4),
    IN p_actual_category VARCHAR(100),
    IN p_processing_time_ms INT
)
BEGIN
    DECLARE v_points_earned INT DEFAULT 0;
    DECLARE v_is_correct BOOLEAN DEFAULT FALSE;
    DECLARE v_bonus_points INT DEFAULT 0;
    
    -- Check if classification is correct
    SET v_is_correct = (p_predicted_category = p_actual_category);
    
    -- Calculate base points
    SET v_points_earned = IF(v_is_correct, 10, 2);
    
    -- Accuracy bonus
    IF v_is_correct THEN
        CASE 
            WHEN p_confidence >= 0.95 THEN SET v_bonus_points = 10;
            WHEN p_confidence >= 0.85 THEN SET v_bonus_points = 5;
            WHEN p_confidence >= 0.75 THEN SET v_bonus_points = 2;
            ELSE SET v_bonus_points = 0;
        END CASE;
    END IF;
    
    -- Speed bonus (for fast classifications)
    IF p_processing_time_ms <= 2000 AND v_is_correct THEN
        SET v_bonus_points = v_bonus_points + 5;
    ELSEIF p_processing_time_ms <= 5000 AND v_is_correct THEN
        SET v_bonus_points = v_bonus_points + 2;
    END IF;
    
    -- Total points
    SET v_points_earned = v_points_earned + v_bonus_points;
    
    -- Update user points
    UPDATE users 
    SET green_points = green_points + v_points_earned,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_name,
        base_points, total_points, created_at
    ) VALUES (
        p_user_id, 'waste_classification', 'environmental',
        CONCAT('Waste classification: ', p_predicted_category, 
               IF(v_is_correct, ' (Correct)', ' (Incorrect)')),
        v_points_earned - v_bonus_points,
        v_points_earned,
        NOW()
    );
    
    -- Update user level after points change
    CALL UpdateUserLevel(p_user_id);
    
    -- Return result
    SELECT 
        v_points_earned as points_earned,
        v_is_correct as is_correct,
        v_bonus_points as bonus_points,
        p_confidence as confidence_score;
        
END//

-- ----------------------------------------------------------------
-- 3. CHECK ACHIEVEMENTS PROCEDURE (Compatible Version)
-- ----------------------------------------------------------------
CREATE PROCEDURE CheckAchievements(
    IN p_user_id INT,
    IN p_trigger_type VARCHAR(50)
)
BEGIN
    DECLARE v_user_value INT DEFAULT 0;
    DECLARE v_points_earned INT DEFAULT 0;
    
    -- Check achievements based on trigger type
    CASE p_trigger_type
        WHEN 'waste_classification' THEN
            -- Count waste classification activities
            SELECT COUNT(*) INTO v_user_value
            FROM user_activities_comprehensive 
            WHERE user_id = p_user_id 
            AND activity_type = 'waste_classification';
            
            -- Award achievements for 10, 50, 100 classifications
            IF v_user_value >= 10 AND v_user_value < 11 THEN
                SET v_points_earned = 150;
                INSERT INTO user_activities_comprehensive (
                    user_id, activity_type, activity_category, activity_name,
                    base_points, total_points, created_at
                ) VALUES (
                    p_user_id, 'achievement', 'gamification',
                    'Waste Classification Beginner - 10 classifications',
                    v_points_earned, v_points_earned, NOW()
                );
            ELSEIF v_user_value >= 50 AND v_user_value < 51 THEN
                SET v_points_earned = 500;
                INSERT INTO user_activities_comprehensive (
                    user_id, activity_type, activity_category, activity_name,
                    base_points, total_points, created_at
                ) VALUES (
                    p_user_id, 'achievement', 'gamification',
                    'Waste Classification Expert - 50 classifications',
                    v_points_earned, v_points_earned, NOW()
                );
            ELSEIF v_user_value >= 100 AND v_user_value < 101 THEN
                SET v_points_earned = 1000;
                INSERT INTO user_activities_comprehensive (
                    user_id, activity_type, activity_category, activity_name,
                    base_points, total_points, created_at
                ) VALUES (
                    p_user_id, 'achievement', 'gamification',
                    'Waste Classification Master - 100 classifications',
                    v_points_earned, v_points_earned, NOW()
                );
            END IF;
            
        WHEN 'level_up' THEN
            -- Check level-based achievements
            SELECT COALESCE(user_level, 1) INTO v_user_value
            FROM users WHERE user_id = p_user_id;
            
            IF v_user_value >= 5 THEN
                SET v_points_earned = 250;
                INSERT INTO user_activities_comprehensive (
                    user_id, activity_type, activity_category, activity_name,
                    base_points, total_points, created_at
                ) VALUES (
                    p_user_id, 'achievement', 'gamification',
                    CONCAT('Level ', v_user_value, ' Achievement'),
                    v_points_earned, v_points_earned, NOW()
                );
            END IF;
            
    END CASE;
    
    -- Award points if any achievements were unlocked
    IF v_points_earned > 0 THEN
        UPDATE users 
        SET green_points = green_points + v_points_earned,
            updated_at = NOW()
        WHERE user_id = p_user_id;
    END IF;
    
    -- Return result
    SELECT 
        v_user_value as user_progress,
        v_points_earned as points_earned,
        'SUCCESS' as status;
        
END//

-- ----------------------------------------------------------------
-- 4. GENERATE DAILY ANALYTICS PROCEDURE (Compatible Version)
-- ----------------------------------------------------------------
CREATE PROCEDURE GenerateDailyAnalytics(IN p_analytics_date DATE)
BEGIN
    DECLARE v_total_users INT DEFAULT 0;
    DECLARE v_active_users INT DEFAULT 0;
    DECLARE v_new_users INT DEFAULT 0;
    DECLARE v_total_activities INT DEFAULT 0;
    DECLARE v_waste_classifications INT DEFAULT 0;
    DECLARE v_level_ups INT DEFAULT 0;
    
    -- Set default date if not provided
    IF p_analytics_date IS NULL THEN
        SET p_analytics_date = CURDATE() - INTERVAL 1 DAY;
    END IF;
    
    -- Calculate user metrics
    SELECT COUNT(*) INTO v_total_users FROM users WHERE is_active = TRUE;
    
    SELECT COUNT(DISTINCT user_id) INTO v_active_users
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = p_analytics_date;
    
    SELECT COUNT(*) INTO v_new_users
    FROM users 
    WHERE DATE(created_at) = p_analytics_date;
    
    -- Calculate activity metrics
    SELECT COUNT(*) INTO v_total_activities
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = p_analytics_date;
    
    SELECT COUNT(*) INTO v_waste_classifications
    FROM user_activities_comprehensive 
    WHERE activity_type = 'waste_classification'
    AND DATE(created_at) = p_analytics_date;
    
    SELECT COUNT(*) INTO v_level_ups
    FROM user_activities_comprehensive 
    WHERE activity_type = 'level_up'
    AND DATE(created_at) = p_analytics_date;
    
    -- Return analytics summary
    SELECT 
        p_analytics_date as analytics_date,
        v_total_users as total_users,
        v_active_users as active_users,
        v_new_users as new_users,
        v_total_activities as total_activities,
        v_waste_classifications as waste_classifications,
        v_level_ups as level_ups,
        NOW() as generated_at;
        
END//

-- ----------------------------------------------------------------
-- 5. UPDATE USER STREAKS PROCEDURE (Compatible Version)
-- ----------------------------------------------------------------
CREATE PROCEDURE UpdateUserStreaks(IN p_user_id INT)
BEGIN
    DECLARE v_last_activity DATE;
    DECLARE v_current_streak INT DEFAULT 0;
    DECLARE v_new_streak INT DEFAULT 0;
    
    -- Get user's last activity date
    SELECT DATE(MAX(created_at)) INTO v_last_activity
    FROM user_activities_comprehensive 
    WHERE user_id = p_user_id;
    
    -- Get current streak
    SELECT COALESCE(login_streak, 0) INTO v_current_streak
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate new streak
    IF v_last_activity = CURDATE() THEN
        -- Activity today, increment streak
        SET v_new_streak = v_current_streak + 1;
    ELSEIF v_last_activity = CURDATE() - INTERVAL 1 DAY THEN
        -- Activity yesterday, maintain streak
        SET v_new_streak = v_current_streak;
    ELSE
        -- No recent activity, reset streak
        SET v_new_streak = 0;
    END IF;
    
    -- Update user streaks
    UPDATE users 
    SET login_streak = v_new_streak,
        longest_streak = GREATEST(COALESCE(longest_streak, 0), v_new_streak),
        last_login = NOW(),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Award streak bonus points
    IF v_new_streak > v_current_streak AND v_new_streak >= 7 THEN
        UPDATE users 
        SET green_points = green_points + (v_new_streak * 5)
        WHERE user_id = p_user_id;
        
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, activity_name,
            base_points, total_points, created_at
        ) VALUES (
            p_user_id, 'streak_bonus', 'engagement',
            CONCAT('Streak bonus for ', v_new_streak, ' days'),
            v_new_streak * 5, v_new_streak * 5, NOW()
        );
    END IF;
    
    -- Return result
    SELECT 
        v_current_streak as old_streak,
        v_new_streak as new_streak,
        v_last_activity as last_activity_date;
        
END//

-- ----------------------------------------------------------------
-- 6. CALCULATE POINTS AND REWARDS PROCEDURE (Compatible Version)
-- ----------------------------------------------------------------
CREATE PROCEDURE CalculatePointsAndRewards(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_activity_value INT,
    IN p_metadata JSON
)
BEGIN
    DECLARE v_base_points INT DEFAULT 0;
    DECLARE v_bonus_points INT DEFAULT 0;
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_user_level INT DEFAULT 1;
    DECLARE v_multiplier DECIMAL(3,2) DEFAULT 1.0;
    
    -- Get user level for multiplier
    SELECT COALESCE(user_level, 1) INTO v_user_level 
    FROM users WHERE user_id = p_user_id;
    
    -- Set level-based multiplier
    SET v_multiplier = 1.0 + (v_user_level - 1) * 0.1;
    
    -- Calculate base points based on activity type
    CASE p_activity_type
        WHEN 'article_read' THEN SET v_base_points = 5;
        WHEN 'quiz_complete' THEN SET v_base_points = 25;
        WHEN 'waste_classify' THEN SET v_base_points = 10;
        WHEN 'forum_post' THEN SET v_base_points = 15;
        WHEN 'event_attend' THEN SET v_base_points = 50;
        WHEN 'review_write' THEN SET v_base_points = 20;
        ELSE SET v_base_points = 5;
    END CASE;
    
    -- Apply activity value multiplier
    IF p_activity_value > 0 THEN
        SET v_base_points = v_base_points * p_activity_value;
    END IF;
    
    -- Apply level multiplier
    SET v_total_points = ROUND(v_base_points * v_multiplier);
    
    -- Update user points
    UPDATE users 
    SET green_points = green_points + v_total_points,
        experience_points = experience_points + ROUND(v_total_points * 0.3),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_name,
        base_points, total_points, created_at
    ) VALUES (
        p_user_id, p_activity_type, 'points_calculation',
        CONCAT('Points awarded for: ', p_activity_type),
        v_base_points, v_total_points, NOW()
    );
    
    -- Check for achievements
    CALL CheckAchievements(p_user_id, p_activity_type);
    
    -- Update user level
    CALL UpdateUserLevel(p_user_id);
    
    -- Return points calculation
    SELECT 
        v_base_points as base_points,
        v_multiplier as level_multiplier,
        v_total_points as total_points_awarded;
        
END//

DELIMITER ;

-- Update sample user data for testing
UPDATE users 
SET green_points = 250, 
    experience_points = 500, 
    user_level = 2,
    login_streak = 3,
    updated_at = NOW() 
WHERE user_id = 1;

SELECT 'Phase 23: Compatible Stored Procedures Created Successfully!' as result;
