-- Phase 23: Complete Stored Procedures Installation
-- Environmental Platform Database
-- Final installation of all 6 required business logic procedures
-- Compatible format without DELIMITER issues

USE environmental_platform;

-- ================================================================
-- PROCEDURE CLEANUP
-- ================================================================

-- Remove any existing procedures to ensure clean installation
DROP PROCEDURE IF EXISTS UpdateUserLevel;
DROP PROCEDURE IF EXISTS ProcessWasteClassification;
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards;
DROP PROCEDURE IF EXISTS CheckAchievements;
DROP PROCEDURE IF EXISTS UpdateUserStreaks;
DROP PROCEDURE IF EXISTS GenerateDailyAnalytics;

-- ================================================================
-- PROCEDURE 1: UpdateUserLevel
-- ================================================================

CREATE PROCEDURE UpdateUserLevel(p_user_id INT)
BEGIN
    DECLARE v_green_points INT DEFAULT 0;
    DECLARE v_new_level INT DEFAULT 1;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get current green points using user_id column
    SELECT COALESCE(green_points, 0) 
    INTO v_green_points 
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate level based on points thresholds
    SET v_new_level = CASE 
        WHEN v_green_points >= 2500 THEN 5  -- Expert
        WHEN v_green_points >= 1000 THEN 4  -- Advanced  
        WHEN v_green_points >= 500 THEN 3   -- Intermediate
        WHEN v_green_points >= 100 THEN 2   -- Novice
        ELSE 1                              -- Beginner
    END;
    
    -- Update user level and experience points
    UPDATE users 
    SET user_level = v_new_level,
        experience_points = v_green_points,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    COMMIT;
    
    -- Return level information
    SELECT 
        v_new_level as new_level, 
        v_green_points as total_points,
        'SUCCESS' as status;
END;

-- ================================================================
-- PROCEDURE 2: ProcessWasteClassification
-- ================================================================

CREATE PROCEDURE ProcessWasteClassification(
    p_user_id INT, 
    p_predicted_category VARCHAR(50), 
    p_confidence_score DECIMAL(5,2), 
    p_actual_category VARCHAR(50)
)
BEGIN
    DECLARE v_points_earned INT DEFAULT 5;
    DECLARE v_is_correct BOOLEAN DEFAULT TRUE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Check if classification is correct
    SET v_is_correct = (p_predicted_category = COALESCE(p_actual_category, p_predicted_category));
    
    -- Calculate points based on confidence and accuracy
    SET v_points_earned = CASE 
        WHEN p_confidence_score >= 90 AND v_is_correct THEN 15
        WHEN p_confidence_score >= 75 AND v_is_correct THEN 12
        WHEN p_confidence_score >= 60 AND v_is_correct THEN 8
        WHEN v_is_correct THEN 5
        ELSE 2  -- Incorrect classification still gets small reward for effort
    END;
    
    -- Update user points
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_points_earned,
        total_carbon_saved = COALESCE(total_carbon_saved, 0.00) + 0.1,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log the activity
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, 'waste_classification', 'ai_interaction', 
     CONCAT('AI Classification: ', p_predicted_category), 
     v_points_earned, v_points_earned, NOW());
    
    -- Update user level after points change
    CALL UpdateUserLevel(p_user_id);
    
    COMMIT;
    
    -- Return classification results
    SELECT 
        v_points_earned as points_earned, 
        p_confidence_score as confidence_score,
        v_is_correct as classification_correct,
        'SUCCESS' as status;
END;

-- ================================================================
-- PROCEDURE 3: CalculatePointsAndRewards
-- ================================================================

CREATE PROCEDURE CalculatePointsAndRewards(
    p_user_id INT, 
    p_activity_type VARCHAR(50), 
    p_carbon_impact DECIMAL(10,2)
)
BEGIN
    DECLARE v_base_points INT DEFAULT 5;
    DECLARE v_bonus_points INT DEFAULT 0;
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_user_level INT DEFAULT 1;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user level for bonus calculation
    SELECT COALESCE(user_level, 1) 
    INTO v_user_level 
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate base points by activity type
    SET v_base_points = CASE p_activity_type
        WHEN 'recycling' THEN 10
        WHEN 'waste_classification' THEN 15
        WHEN 'eco_challenge' THEN 20
        WHEN 'community_activity' THEN 25
        WHEN 'education' THEN 8
        WHEN 'article_read' THEN 5
        WHEN 'quiz_complete' THEN 25
        WHEN 'forum_post' THEN 15
        ELSE 5
    END;
    
    -- Level-based bonus (10% per level above 1)
    SET v_bonus_points = FLOOR(v_base_points * (v_user_level - 1) * 0.1);
    
    -- Calculate total points
    SET v_total_points = v_base_points + v_bonus_points;
    
    -- Update user points and carbon savings
    UPDATE users 
    SET green_points = COALESCE(green_points, 0) + v_total_points,
        experience_points = COALESCE(experience_points, 0) + v_total_points,
        total_carbon_saved = COALESCE(total_carbon_saved, 0.00) + COALESCE(p_carbon_impact, 0.00),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log the activity
    INSERT INTO user_activities_comprehensive 
    (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
    VALUES 
    (p_user_id, p_activity_type, 'reward', 
     CONCAT('Points for ', p_activity_type), 
     v_base_points, v_total_points, NOW());
    
    -- Update user level after points change
    CALL UpdateUserLevel(p_user_id);
    
    COMMIT;
    
    -- Return points calculation
    SELECT 
        v_base_points as base_points,
        v_bonus_points as bonus_points, 
        v_total_points as total_points_awarded,
        'SUCCESS' as status;
END;

-- ================================================================
-- PROCEDURE 4: CheckAchievements
-- ================================================================

CREATE PROCEDURE CheckAchievements(p_user_id INT)
BEGIN
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_current_level INT DEFAULT 1;
    DECLARE v_classification_count INT DEFAULT 0;
    DECLARE v_login_streak INT DEFAULT 0;
    DECLARE v_achievement_points INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user statistics
    SELECT 
        COALESCE(green_points, 0),
        COALESCE(user_level, 1),
        COALESCE(login_streak, 0)
    INTO v_total_points, v_current_level, v_login_streak
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Count waste classifications
    SELECT COUNT(*) 
    INTO v_classification_count
    FROM user_activities_comprehensive 
    WHERE user_id = p_user_id 
    AND activity_type = 'waste_classification';
    
    -- Award achievement points based on milestones
    SET v_achievement_points = 0;
    
    -- Points-based achievements
    IF v_total_points >= 2500 AND v_current_level = 5 THEN
        SET v_achievement_points = v_achievement_points + 100;
    ELSEIF v_total_points >= 1000 AND v_current_level >= 4 THEN
        SET v_achievement_points = v_achievement_points + 50;
    ELSEIF v_total_points >= 500 AND v_current_level >= 3 THEN
        SET v_achievement_points = v_achievement_points + 25;
    END IF;
    
    -- Classification-based achievements
    IF v_classification_count >= 100 THEN
        SET v_achievement_points = v_achievement_points + 75;
    ELSEIF v_classification_count >= 50 THEN
        SET v_achievement_points = v_achievement_points + 40;
    ELSEIF v_classification_count >= 10 THEN
        SET v_achievement_points = v_achievement_points + 15;
    END IF;
    
    -- Streak-based achievements
    IF v_login_streak >= 30 THEN
        SET v_achievement_points = v_achievement_points + 60;
    ELSEIF v_login_streak >= 7 THEN
        SET v_achievement_points = v_achievement_points + 25;
    ELSEIF v_login_streak >= 3 THEN
        SET v_achievement_points = v_achievement_points + 10;
    END IF;
    
    -- Award achievement bonus if any milestones were reached
    IF v_achievement_points > 0 THEN
        UPDATE users 
        SET green_points = green_points + v_achievement_points,
            experience_points = experience_points + v_achievement_points,
            updated_at = NOW()
        WHERE user_id = p_user_id;
        
        -- Log achievement
        INSERT INTO user_activities_comprehensive 
        (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
        VALUES 
        (p_user_id, 'achievement', 'gamification', 
         'Achievement Milestone Reached', 
         v_achievement_points, v_achievement_points, NOW());
    END IF;
    
    COMMIT;
    
    -- Return achievement information
    SELECT 
        v_achievement_points as achievement_points_awarded,
        v_classification_count as classifications_completed,
        v_login_streak as current_login_streak,
        v_current_level as current_level,
        'SUCCESS' as status;
END;

-- ================================================================
-- PROCEDURE 5: UpdateUserStreaks
-- ================================================================

CREATE PROCEDURE UpdateUserStreaks(p_user_id INT)
BEGIN
    DECLARE v_current_streak INT DEFAULT 0;
    DECLARE v_longest_streak INT DEFAULT 0;
    DECLARE v_last_login DATE;
    DECLARE v_today DATE;
    DECLARE v_bonus_points INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    SET v_today = CURDATE();
    
    -- Get current streak data
    SELECT 
        COALESCE(login_streak, 0),
        COALESCE(longest_streak, 0),
        DATE(last_login)
    INTO v_current_streak, v_longest_streak, v_last_login
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate new streak
    IF v_last_login IS NULL OR v_last_login < DATE_SUB(v_today, INTERVAL 1 DAY) THEN
        -- Reset streak if more than 1 day gap
        SET v_current_streak = 1;
    ELSEIF v_last_login = DATE_SUB(v_today, INTERVAL 1 DAY) THEN
        -- Consecutive day, increment streak
        SET v_current_streak = v_current_streak + 1;
    ELSEIF v_last_login = v_today THEN
        -- Same day, maintain streak
        SET v_current_streak = v_current_streak;
    ELSE
        -- Reset streak
        SET v_current_streak = 1;
    END IF;
    
    -- Update longest streak if needed
    IF v_current_streak > v_longest_streak THEN
        SET v_longest_streak = v_current_streak;
    END IF;
    
    -- Calculate streak bonus points
    IF v_current_streak >= 30 THEN 
        SET v_bonus_points = 100;
    ELSEIF v_current_streak >= 7 THEN 
        SET v_bonus_points = 50;
    ELSEIF v_current_streak >= 3 THEN 
        SET v_bonus_points = 20;
    ELSE 
        SET v_bonus_points = 0;
    END IF;
    
    -- Update user record
    UPDATE users 
    SET login_streak = v_current_streak,
        longest_streak = v_longest_streak,
        last_login = NOW(),
        green_points = green_points + v_bonus_points,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log streak bonus if awarded
    IF v_bonus_points > 0 THEN
        INSERT INTO user_activities_comprehensive 
        (user_id, activity_type, activity_category, activity_name, base_points, total_points, created_at)
        VALUES 
        (p_user_id, 'streak_bonus', 'engagement', 
         CONCAT('Login Streak Bonus - ', v_current_streak, ' days'), 
         v_bonus_points, v_bonus_points, NOW());
    END IF;
    
    COMMIT;
    
    -- Return streak information
    SELECT 
        v_current_streak as current_streak,
        v_longest_streak as longest_streak,
        v_bonus_points as bonus_points_awarded,
        'SUCCESS' as status;
END;

-- ================================================================
-- PROCEDURE 6: GenerateDailyAnalytics
-- ================================================================

CREATE PROCEDURE GenerateDailyAnalytics()
BEGIN
    DECLARE v_total_users INT DEFAULT 0;
    DECLARE v_active_users INT DEFAULT 0;
    DECLARE v_new_users INT DEFAULT 0;
    DECLARE v_total_activities INT DEFAULT 0;
    DECLARE v_total_points_awarded INT DEFAULT 0;
    DECLARE v_avg_user_level DECIMAL(3,2) DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Calculate daily metrics
    SELECT COUNT(*) INTO v_total_users FROM users;
    
    SELECT COUNT(DISTINCT user_id) 
    INTO v_active_users 
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = CURDATE();
    
    SELECT COUNT(*) 
    INTO v_new_users 
    FROM users 
    WHERE DATE(created_at) = CURDATE();
    
    SELECT COUNT(*) 
    INTO v_total_activities 
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = CURDATE();
    
    SELECT COALESCE(SUM(total_points), 0) 
    INTO v_total_points_awarded 
    FROM user_activities_comprehensive 
    WHERE DATE(created_at) = CURDATE();
    
    SELECT COALESCE(AVG(user_level), 1) 
    INTO v_avg_user_level 
    FROM users;
    
    -- Insert or update daily analytics summary
    INSERT INTO daily_analytics_summary 
    (date_recorded, total_users, active_users, new_users, total_activities, 
     total_points_awarded, avg_user_level, created_at)
    VALUES 
    (CURDATE(), v_total_users, v_active_users, v_new_users, v_total_activities, 
     v_total_points_awarded, v_avg_user_level, NOW())
    ON DUPLICATE KEY UPDATE
        total_users = v_total_users,
        active_users = v_active_users,
        new_users = v_new_users,
        total_activities = v_total_activities,
        total_points_awarded = v_total_points_awarded,
        avg_user_level = v_avg_user_level,
        updated_at = NOW();
    
    COMMIT;
    
    -- Return analytics summary
    SELECT 
        CURDATE() as analytics_date,
        v_total_users as total_users,
        v_active_users as active_users_today,
        v_new_users as new_users_today,
        v_total_activities as activities_today,
        v_total_points_awarded as points_awarded_today,
        v_avg_user_level as avg_user_level,
        'SUCCESS' as status;
END;

-- ================================================================
-- VERIFICATION AND COMPLETION
-- ================================================================

-- Verify all procedures were created successfully
SELECT 'PHASE 23 STORED PROCEDURES INSTALLATION COMPLETE!' as installation_status;

-- List all created procedures
SELECT 
    'Phase 23 Procedures Created:' as section,
    routine_name as procedure_name,
    created as created_date
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND routine_name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification',
    'CalculatePointsAndRewards',
    'CheckAchievements', 
    'UpdateUserStreaks',
    'GenerateDailyAnalytics'
)
ORDER BY routine_name;

-- Count verification
SELECT 
    COUNT(*) as total_phase23_procedures,
    CASE 
        WHEN COUNT(*) = 6 THEN '✅ ALL 6 PROCEDURES SUCCESSFULLY INSTALLED'
        ELSE '❌ SOME PROCEDURES MISSING'
    END as installation_result
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND routine_name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification',
    'CalculatePointsAndRewards',
    'CheckAchievements',
    'UpdateUserStreaks',
    'GenerateDailyAnalytics'
);

SELECT 'Ready for testing and production deployment!' as next_steps;
