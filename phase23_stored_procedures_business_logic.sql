-- ================================================================
-- Phase 23: Stored Procedures & Business Logic Implementation
-- Environmental Platform Database Enhancement
-- 
-- Purpose: Create comprehensive business logic procedures for:
-- - User level calculation and management
-- - Waste classification processing with AI integration
-- - Achievement checking and awarding system
-- - Daily analytics generation and reporting
-- - Automated streak and points calculation
-- Date: June 3, 2025
-- ================================================================

USE environmental_platform;

-- ================================================================
-- PHASE 23: BUSINESS LOGIC STORED PROCEDURES
-- ================================================================

DELIMITER //

-- ----------------------------------------------------------------
-- 1. UPDATE USER LEVEL PROCEDURE
-- Purpose: Calculate and update user levels based on points and activities
-- ----------------------------------------------------------------
DROP PROCEDURE IF EXISTS UpdateUserLevel//

CREATE PROCEDURE UpdateUserLevel(IN p_user_id INT)
BEGIN
    DECLARE v_green_points INT DEFAULT 0;
    DECLARE v_experience_points INT DEFAULT 0;
    DECLARE v_current_level INT DEFAULT 1;
    DECLARE v_new_level INT DEFAULT 1;
    DECLARE v_total_activities INT DEFAULT 0;
    DECLARE v_achievements_count INT DEFAULT 0;
    DECLARE v_carbon_saved DECIMAL(10,2) DEFAULT 0;
    DECLARE v_level_changed BOOLEAN DEFAULT FALSE;
    DECLARE v_points_for_level INT DEFAULT 0;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get current user stats
    SELECT 
        COALESCE(green_points, 0),
        COALESCE(experience_points, 0),
        COALESCE(user_level, 1),
        COALESCE(total_carbon_saved, 0)
    INTO v_green_points, v_experience_points, v_current_level, v_carbon_saved
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate total activities
    SELECT COUNT(*) INTO v_total_activities
    FROM user_activities_comprehensive 
    WHERE user_id = p_user_id;
    
    -- Calculate achievements count
    SELECT COUNT(*) INTO v_achievements_count
    FROM user_achievements_enhanced 
    WHERE user_id = p_user_id;
    
    -- Calculate total points for level determination
    SET v_points_for_level = v_green_points + v_experience_points + 
                           (v_total_activities * 10) + 
                           (v_achievements_count * 50) +
                           (v_carbon_saved * 2);
    
    -- Determine new level based on points
    CASE 
        WHEN v_points_for_level >= 50000 THEN SET v_new_level = 10; -- Eco Master
        WHEN v_points_for_level >= 25000 THEN SET v_new_level = 9;  -- Eco Expert
        WHEN v_points_for_level >= 15000 THEN SET v_new_level = 8;  -- Green Leader
        WHEN v_points_for_level >= 10000 THEN SET v_new_level = 7;  -- Environmental Advocate
        WHEN v_points_for_level >= 7500 THEN SET v_new_level = 6;   -- Sustainability Champion
        WHEN v_points_for_level >= 5000 THEN SET v_new_level = 5;   -- Green Warrior
        WHEN v_points_for_level >= 3000 THEN SET v_new_level = 4;   -- Eco Enthusiast
        WHEN v_points_for_level >= 1500 THEN SET v_new_level = 3;   -- Environmental Student
        WHEN v_points_for_level >= 500 THEN SET v_new_level = 2;    -- Green Beginner
        ELSE SET v_new_level = 1;                                   -- Newcomer
    END CASE;
    
    -- Check if level changed
    IF v_new_level > v_current_level THEN
        SET v_level_changed = TRUE;
        
        -- Update user level
        UPDATE users 
        SET user_level = v_new_level,
            experience_points = v_experience_points + (v_new_level - v_current_level) * 100,
            updated_at = NOW()
        WHERE user_id = p_user_id;
        
        -- Log level up activity
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, activity_description,
            points_earned, metadata, created_at
        ) VALUES (
            p_user_id, 'level_up', 'achievement', 
            CONCAT('Level up from ', v_current_level, ' to ', v_new_level),
            (v_new_level - v_current_level) * 100,
            JSON_OBJECT(
                'old_level', v_current_level,
                'new_level', v_new_level,
                'total_points', v_points_for_level
            ),
            NOW()
        );
        
        -- Create notification for level up
        INSERT INTO notifications (
            user_id, notification_type, title, message, 
            metadata, is_read, created_at
        ) VALUES (
            p_user_id, 'level_up', 'Chúc mừng! Bạn đã lên cấp!',
            CONCAT('Bạn đã đạt cấp độ ', v_new_level, '! Tiếp tục phát triển để đạt cấp độ cao hơn.'),
            JSON_OBJECT(
                'new_level', v_new_level,
                'points_earned', (v_new_level - v_current_level) * 100
            ),
            FALSE, NOW()
        );
        
    END IF;
    
    -- Update user statistics regardless of level change
    UPDATE users 
    SET experience_points = v_experience_points,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    COMMIT;
    
    -- Return result
    SELECT 
        v_current_level as old_level,
        v_new_level as new_level,
        v_level_changed as level_changed,
        v_points_for_level as total_points,
        v_green_points as green_points,
        v_experience_points as experience_points,
        v_total_activities as total_activities,
        v_achievements_count as achievements_count;
        
END//

-- ----------------------------------------------------------------
-- 2. PROCESS WASTE CLASSIFICATION PROCEDURE
-- Purpose: Process AI waste classification results and award points
-- ----------------------------------------------------------------
DROP PROCEDURE IF EXISTS ProcessWasteClassification//

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
    DECLARE v_session_type VARCHAR(50);
    DECLARE v_daily_classifications INT DEFAULT 0;
    DECLARE v_accuracy_bonus INT DEFAULT 0;
    DECLARE v_speed_bonus INT DEFAULT 0;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get session type
    SELECT session_type INTO v_session_type
    FROM waste_classification_sessions 
    WHERE session_id = p_session_id;
    
    -- Check if classification is correct
    SET v_is_correct = (p_predicted_category = p_actual_category);
    
    -- Calculate base points
    CASE v_session_type
        WHEN 'practice' THEN SET v_points_earned = 5;
        WHEN 'challenge' THEN SET v_points_earned = 10;
        WHEN 'daily' THEN SET v_points_earned = 15;
        WHEN 'tournament' THEN SET v_points_earned = 20;
        ELSE SET v_points_earned = 5;
    END CASE;
    
    -- Accuracy bonus
    IF v_is_correct THEN
        CASE 
            WHEN p_confidence >= 0.95 THEN SET v_accuracy_bonus = v_points_earned * 2;
            WHEN p_confidence >= 0.85 THEN SET v_accuracy_bonus = v_points_earned;
            WHEN p_confidence >= 0.75 THEN SET v_accuracy_bonus = ROUND(v_points_earned * 0.5);
            ELSE SET v_accuracy_bonus = 0;
        END CASE;
    END IF;
    
    -- Speed bonus (for fast classifications)
    IF p_processing_time_ms <= 2000 AND v_is_correct THEN
        SET v_speed_bonus = 5;
    ELSEIF p_processing_time_ms <= 5000 AND v_is_correct THEN
        SET v_speed_bonus = 2;
    END IF;
    
    -- Total points
    SET v_points_earned = v_points_earned + v_accuracy_bonus + v_speed_bonus;
    
    -- Daily classification limit check
    SELECT COUNT(*) INTO v_daily_classifications
    FROM waste_classification_results 
    WHERE user_id = p_user_id 
    AND DATE(created_at) = CURDATE();
    
    -- Reduce points if too many classifications today (to prevent farming)
    IF v_daily_classifications > 50 THEN
        SET v_points_earned = GREATEST(1, ROUND(v_points_earned * 0.1));
    ELSEIF v_daily_classifications > 20 THEN
        SET v_points_earned = ROUND(v_points_earned * 0.5);
    END IF;
    
    -- Insert classification result
    INSERT INTO waste_classification_results (
        session_id, user_id, image_url, predicted_category, actual_category,
        confidence_score, is_correct, points_earned, processing_time_ms,
        metadata, created_at
    ) VALUES (
        p_session_id, p_user_id, p_image_url, p_predicted_category, p_actual_category,
        p_confidence, v_is_correct, v_points_earned, p_processing_time_ms,
        JSON_OBJECT(
            'base_points', v_points_earned - v_accuracy_bonus - v_speed_bonus,
            'accuracy_bonus', v_accuracy_bonus,
            'speed_bonus', v_speed_bonus,
            'daily_count', v_daily_classifications + 1,
            'session_type', v_session_type
        ),
        NOW()
    );
    
    -- Update user points
    UPDATE users 
    SET green_points = green_points + v_points_earned,
        experience_points = experience_points + ROUND(v_points_earned * 0.5),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_description,
        points_earned, metadata, created_at
    ) VALUES (
        p_user_id, 'waste_classification', 'environmental',
        CONCAT('Phân loại rác: ', p_predicted_category, 
               IF(v_is_correct, ' (Chính xác)', ' (Sai)')),
        v_points_earned,
        JSON_OBJECT(
            'predicted_category', p_predicted_category,
            'actual_category', p_actual_category,
            'confidence', p_confidence,
            'is_correct', v_is_correct,
            'processing_time_ms', p_processing_time_ms
        ),
        NOW()
    );
    
    -- Update session statistics
    UPDATE waste_classification_sessions 
    SET total_questions = total_questions + 1,
        correct_answers = correct_answers + IF(v_is_correct, 1, 0),
        total_points = total_points + v_points_earned,
        last_activity = NOW()
    WHERE session_id = p_session_id;
    
    -- Check for achievements
    CALL CheckAchievements(p_user_id, 'waste_classification');
    
    -- Update user level
    CALL UpdateUserLevel(p_user_id);
    
    COMMIT;
    
    -- Return result
    SELECT 
        v_points_earned as points_earned,
        v_is_correct as is_correct,
        v_accuracy_bonus as accuracy_bonus,
        v_speed_bonus as speed_bonus,
        p_confidence as confidence_score,
        v_daily_classifications + 1 as daily_classifications;
        
END//

-- ----------------------------------------------------------------
-- 3. CHECK ACHIEVEMENTS PROCEDURE
-- Purpose: Check and award achievements based on user activities
-- ----------------------------------------------------------------
DROP PROCEDURE IF EXISTS CheckAchievements//

CREATE PROCEDURE CheckAchievements(
    IN p_user_id INT,
    IN p_trigger_type VARCHAR(50)
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_achievement_id INT;
    DECLARE v_unlock_criteria JSON;
    DECLARE v_required_value INT;
    DECLARE v_user_value INT;
    DECLARE v_already_unlocked BOOLEAN;
    DECLARE v_points_reward INT;
    DECLARE v_achievement_name VARCHAR(255);
    
    -- Cursor for achievements to check
    DECLARE achievement_cursor CURSOR FOR
        SELECT achievement_id, achievement_name, unlock_criteria, points_reward
        FROM achievements_enhanced 
        WHERE is_active = TRUE
        AND JSON_CONTAINS(unlock_criteria->'$.trigger_types', CONCAT('"', p_trigger_type, '"'));
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    OPEN achievement_cursor;
    
    achievement_loop: LOOP
        FETCH achievement_cursor INTO v_achievement_id, v_achievement_name, v_unlock_criteria, v_points_reward;
        
        IF done THEN
            LEAVE achievement_loop;
        END IF;
        
        -- Check if user already has this achievement
        SELECT COUNT(*) > 0 INTO v_already_unlocked
        FROM user_achievements_enhanced 
        WHERE user_id = p_user_id AND achievement_id = v_achievement_id;
        
        IF NOT v_already_unlocked THEN
            -- Get required value from criteria
            SET v_required_value = JSON_UNQUOTE(JSON_EXTRACT(v_unlock_criteria, '$.required_value'));
            
            -- Calculate user's current value based on trigger type
            CASE p_trigger_type
                WHEN 'registration' THEN
                    SET v_user_value = 1;
                    
                WHEN 'waste_classification' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM waste_classification_results 
                    WHERE user_id = p_user_id AND is_correct = TRUE;
                    
                WHEN 'social_sharing' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM content_shares 
                    WHERE user_id = p_user_id;
                    
                WHEN 'carbon_logging' THEN
                    SELECT COALESCE(SUM(carbon_saved_kg), 0) INTO v_user_value
                    FROM carbon_footprints 
                    WHERE user_id = p_user_id;
                    
                WHEN 'quiz_complete' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM quiz_sessions 
                    WHERE user_id = p_user_id AND status = 'completed';
                    
                WHEN 'article_interaction' THEN
                    SELECT COUNT(DISTINCT article_id) INTO v_user_value
                    FROM article_interactions 
                    WHERE user_id = p_user_id;
                    
                WHEN 'forum_participation' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM forum_posts 
                    WHERE user_id = p_user_id;
                    
                WHEN 'event_attendance' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM event_registrations 
                    WHERE user_id = p_user_id AND attendance_status = 'attended';
                    
                WHEN 'donation_made' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM donations 
                    WHERE donor_id = p_user_id;
                    
                WHEN 'exchange_completed' THEN
                    SELECT COUNT(*) INTO v_user_value
                    FROM exchange_requests 
                    WHERE (requester_id = p_user_id OR owner_id = p_user_id) 
                    AND status = 'completed';
                    
                ELSE
                    SET v_user_value = 0;
            END CASE;
            
            -- Check if achievement should be unlocked
            IF v_user_value >= v_required_value THEN
                -- Award achievement
                INSERT INTO user_achievements_enhanced (
                    user_id, achievement_id, unlocked_at, progress_value,
                    metadata, created_at
                ) VALUES (
                    p_user_id, v_achievement_id, NOW(), v_user_value,
                    JSON_OBJECT(
                        'trigger_type', p_trigger_type,
                        'required_value', v_required_value,
                        'actual_value', v_user_value,
                        'unlock_timestamp', UNIX_TIMESTAMP(NOW())
                    ),
                    NOW()
                );
                
                -- Award points
                UPDATE users 
                SET green_points = green_points + v_points_reward,
                    experience_points = experience_points + ROUND(v_points_reward * 0.5),
                    updated_at = NOW()
                WHERE user_id = p_user_id;
                
                -- Log achievement activity
                INSERT INTO user_activities_comprehensive (
                    user_id, activity_type, activity_category, activity_description,
                    points_earned, metadata, created_at
                ) VALUES (
                    p_user_id, 'achievement_unlocked', 'achievement',
                    CONCAT('Mở khóa thành tựu: ', v_achievement_name),
                    v_points_reward,
                    JSON_OBJECT(
                        'achievement_id', v_achievement_id,
                        'achievement_name', v_achievement_name,
                        'trigger_type', p_trigger_type
                    ),
                    NOW()
                );
                
                -- Create notification
                INSERT INTO notifications (
                    user_id, notification_type, title, message,
                    metadata, is_read, created_at
                ) VALUES (
                    p_user_id, 'achievement', 'Thành tựu mới!',
                    CONCAT('Chúc mừng! Bạn đã mở khóa thành tựu "', v_achievement_name, 
                           '" và nhận được ', v_points_reward, ' điểm xanh!'),
                    JSON_OBJECT(
                        'achievement_id', v_achievement_id,
                        'points_reward', v_points_reward
                    ),
                    FALSE, NOW()
                );
                
            END IF;
        END IF;
        
    END LOOP;
    
    CLOSE achievement_cursor;
    COMMIT;
    
END//

-- ----------------------------------------------------------------
-- 4. GENERATE DAILY ANALYTICS PROCEDURE
-- Purpose: Generate comprehensive daily analytics and reports
-- ----------------------------------------------------------------
DROP PROCEDURE IF EXISTS GenerateDailyAnalytics//

CREATE PROCEDURE GenerateDailyAnalytics(IN p_analytics_date DATE)
BEGIN
    DECLARE v_total_users INT DEFAULT 0;
    DECLARE v_active_users INT DEFAULT 0;
    DECLARE v_new_users INT DEFAULT 0;
    DECLARE v_total_articles INT DEFAULT 0;
    DECLARE v_article_views INT DEFAULT 0;
    DECLARE v_total_carbon_saved DECIMAL(10,2) DEFAULT 0;
    DECLARE v_waste_classifications INT DEFAULT 0;
    DECLARE v_quiz_completions INT DEFAULT 0;
    DECLARE v_social_shares INT DEFAULT 0;
    DECLARE v_total_orders INT DEFAULT 0;
    DECLARE v_total_revenue DECIMAL(10,2) DEFAULT 0;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Set default date if not provided
    IF p_analytics_date IS NULL THEN
        SET p_analytics_date = CURDATE() - INTERVAL 1 DAY;
    END IF;
    
    -- Calculate user metrics
    SELECT COUNT(*) INTO v_total_users FROM users WHERE is_active = TRUE;
    
    SELECT COUNT(DISTINCT user_id) INTO v_active_users
    FROM user_sessions 
    WHERE DATE(last_activity) = p_analytics_date;
    
    SELECT COUNT(*) INTO v_new_users
    FROM users 
    WHERE DATE(created_at) = p_analytics_date;
    
    -- Calculate content metrics
    SELECT COUNT(*) INTO v_total_articles
    FROM articles 
    WHERE status = 'published';
    
    SELECT COUNT(*) INTO v_article_views
    FROM article_interactions 
    WHERE interaction_type = 'view' 
    AND DATE(created_at) = p_analytics_date;
    
    -- Calculate environmental metrics
    SELECT COALESCE(SUM(carbon_saved_kg), 0) INTO v_total_carbon_saved
    FROM carbon_footprints 
    WHERE DATE(created_at) = p_analytics_date;
    
    SELECT COUNT(*) INTO v_waste_classifications
    FROM waste_classification_results 
    WHERE DATE(created_at) = p_analytics_date;
    
    -- Calculate education metrics
    SELECT COUNT(*) INTO v_quiz_completions
    FROM quiz_sessions 
    WHERE status = 'completed' 
    AND DATE(started_at) = p_analytics_date;
    
    -- Calculate social metrics
    SELECT COUNT(*) INTO v_social_shares
    FROM content_shares 
    WHERE DATE(shared_at) = p_analytics_date;
    
    -- Calculate e-commerce metrics
    SELECT COUNT(*), COALESCE(SUM(total_amount), 0) 
    INTO v_total_orders, v_total_revenue
    FROM orders 
    WHERE DATE(created_at) = p_analytics_date;
    
    -- Insert or update daily analytics
    INSERT INTO daily_analytics_summary (
        analytics_date, total_users, active_users, new_users,
        total_articles, article_views, total_carbon_saved,
        waste_classifications, quiz_completions, social_shares,
        total_orders, total_revenue, created_at, updated_at
    ) VALUES (
        p_analytics_date, v_total_users, v_active_users, v_new_users,
        v_total_articles, v_article_views, v_total_carbon_saved,
        v_waste_classifications, v_quiz_completions, v_social_shares,
        v_total_orders, v_total_revenue, NOW(), NOW()
    ) ON DUPLICATE KEY UPDATE
        total_users = VALUES(total_users),
        active_users = VALUES(active_users),
        new_users = VALUES(new_users),
        total_articles = VALUES(total_articles),
        article_views = VALUES(article_views),
        total_carbon_saved = VALUES(total_carbon_saved),
        waste_classifications = VALUES(waste_classifications),
        quiz_completions = VALUES(quiz_completions),
        social_shares = VALUES(social_shares),
        total_orders = VALUES(total_orders),
        total_revenue = VALUES(total_revenue),
        updated_at = NOW();
    
    -- Calculate platform metrics
    INSERT INTO platform_metrics (
        metric_date, metric_type, metric_name, metric_value,
        metric_category, metadata, created_at
    ) VALUES
    (p_analytics_date, 'user', 'total_users', v_total_users, 'engagement', '{}', NOW()),
    (p_analytics_date, 'user', 'active_users', v_active_users, 'engagement', '{}', NOW()),
    (p_analytics_date, 'user', 'new_users', v_new_users, 'growth', '{}', NOW()),
    (p_analytics_date, 'content', 'article_views', v_article_views, 'engagement', '{}', NOW()),
    (p_analytics_date, 'environmental', 'carbon_saved', v_total_carbon_saved, 'impact', '{}', NOW()),
    (p_analytics_date, 'environmental', 'waste_classifications', v_waste_classifications, 'activity', '{}', NOW()),
    (p_analytics_date, 'education', 'quiz_completions', v_quiz_completions, 'learning', '{}', NOW()),
    (p_analytics_date, 'social', 'content_shares', v_social_shares, 'engagement', '{}', NOW()),
    (p_analytics_date, 'commerce', 'orders', v_total_orders, 'sales', '{}', NOW()),
    (p_analytics_date, 'commerce', 'revenue', v_total_revenue, 'financial', '{}', NOW())
    ON DUPLICATE KEY UPDATE
        metric_value = VALUES(metric_value),
        updated_at = NOW();
    
    COMMIT;
    
    -- Return analytics summary
    SELECT 
        p_analytics_date as analytics_date,
        v_total_users as total_users,
        v_active_users as active_users,
        v_new_users as new_users,
        v_article_views as article_views,
        v_total_carbon_saved as carbon_saved_kg,
        v_waste_classifications as waste_classifications,
        v_quiz_completions as quiz_completions,
        v_social_shares as social_shares,
        v_total_orders as orders,
        v_total_revenue as revenue,
        NOW() as generated_at;
        
END//

-- ----------------------------------------------------------------
-- 5. UPDATE USER STREAKS PROCEDURE
-- Purpose: Calculate and update user activity streaks
-- ----------------------------------------------------------------
DROP PROCEDURE IF EXISTS UpdateUserStreaks//

CREATE PROCEDURE UpdateUserStreaks(IN p_user_id INT)
BEGIN
    DECLARE v_last_login DATE;
    DECLARE v_current_streak INT DEFAULT 0;
    DECLARE v_longest_streak INT DEFAULT 0;
    DECLARE v_login_streak INT DEFAULT 0;
    DECLARE v_activity_streak INT DEFAULT 0;
    DECLARE v_carbon_streak INT DEFAULT 0;
    DECLARE v_quiz_streak INT DEFAULT 0;
    DECLARE v_bonus_points INT DEFAULT 0;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user's last login
    SELECT DATE(last_login), login_streak, longest_streak 
    INTO v_last_login, v_current_streak, v_longest_streak
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate login streak
    IF v_last_login = CURDATE() THEN
        -- User logged in today, keep current streak
        SET v_login_streak = v_current_streak;
    ELSEIF v_last_login = CURDATE() - INTERVAL 1 DAY THEN
        -- User logged in yesterday, increment streak
        SET v_login_streak = v_current_streak + 1;
    ELSE
        -- User didn't login yesterday, reset streak
        SET v_login_streak = 1;
    END IF;
    
    -- Update longest streak if current is longer
    IF v_login_streak > v_longest_streak THEN
        SET v_longest_streak = v_login_streak;
    END IF;
    
    -- Calculate activity streak (days with any activity)
    SELECT COUNT(*) INTO v_activity_streak
    FROM (
        SELECT DATE(created_at) as activity_date
        FROM user_activities_comprehensive 
        WHERE user_id = p_user_id 
        AND DATE(created_at) >= CURDATE() - INTERVAL 30 DAY
        GROUP BY DATE(created_at)
        ORDER BY activity_date DESC
    ) consecutive_days;
    
    -- Calculate carbon tracking streak
    SELECT COUNT(*) INTO v_carbon_streak
    FROM (
        SELECT DATE(created_at) as carbon_date
        FROM carbon_footprints 
        WHERE user_id = p_user_id 
        AND DATE(created_at) >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(created_at)
        ORDER BY carbon_date DESC
    ) carbon_days;
    
    -- Calculate quiz streak
    SELECT COUNT(*) INTO v_quiz_streak
    FROM (
        SELECT DATE(started_at) as quiz_date
        FROM quiz_sessions 
        WHERE user_id = p_user_id 
        AND status = 'completed'
        AND DATE(started_at) >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(started_at)
        ORDER BY quiz_date DESC
    ) quiz_days;
    
    -- Calculate bonus points for streaks
    SET v_bonus_points = 0;
    
    -- Login streak bonuses
    IF v_login_streak >= 30 THEN SET v_bonus_points = v_bonus_points + 100;
    ELSEIF v_login_streak >= 7 THEN SET v_bonus_points = v_bonus_points + 50;
    ELSEIF v_login_streak >= 3 THEN SET v_bonus_points = v_bonus_points + 20;
    END IF;
    
    -- Activity streak bonuses
    IF v_activity_streak >= 7 THEN SET v_bonus_points = v_bonus_points + 30;
    ELSEIF v_activity_streak >= 3 THEN SET v_bonus_points = v_bonus_points + 10;
    END IF;
    
    -- Update user streaks
    UPDATE users 
    SET login_streak = v_login_streak,
        longest_streak = v_longest_streak,
        green_points = green_points + v_bonus_points,
        last_login = NOW(),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Update advanced streaks table
    INSERT INTO user_streaks_advanced (
        user_id, streak_type, current_streak, longest_streak,
        last_activity_date, bonus_points_earned, metadata, updated_at
    ) VALUES 
    (p_user_id, 'login', v_login_streak, v_longest_streak, CURDATE(), v_bonus_points, '{}', NOW()),
    (p_user_id, 'activity', v_activity_streak, v_activity_streak, CURDATE(), 0, '{}', NOW()),
    (p_user_id, 'carbon_tracking', v_carbon_streak, v_carbon_streak, CURDATE(), 0, '{}', NOW()),
    (p_user_id, 'quiz_completion', v_quiz_streak, v_quiz_streak, CURDATE(), 0, '{}', NOW())
    ON DUPLICATE KEY UPDATE
        current_streak = VALUES(current_streak),
        longest_streak = GREATEST(longest_streak, VALUES(current_streak)),
        last_activity_date = VALUES(last_activity_date),
        bonus_points_earned = VALUES(bonus_points_earned),
        updated_at = VALUES(updated_at);
    
    -- Log streak activity if bonus points earned
    IF v_bonus_points > 0 THEN
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, activity_description,
            points_earned, metadata, created_at
        ) VALUES (
            p_user_id, 'streak_bonus', 'engagement',
            CONCAT('Streak bonus: ', v_login_streak, ' days login streak'),
            v_bonus_points,
            JSON_OBJECT(
                'login_streak', v_login_streak,
                'activity_streak', v_activity_streak,
                'carbon_streak', v_carbon_streak,
                'quiz_streak', v_quiz_streak
            ),
            NOW()
        );
    END IF;
    
    COMMIT;
    
    -- Return streak information
    SELECT 
        v_login_streak as login_streak,
        v_longest_streak as longest_streak,
        v_activity_streak as activity_streak,
        v_carbon_streak as carbon_tracking_streak,
        v_quiz_streak as quiz_completion_streak,
        v_bonus_points as bonus_points_earned;
        
END//

-- ----------------------------------------------------------------
-- 6. CALCULATE POINTS AND REWARDS PROCEDURE
-- Purpose: Calculate and award points for various user activities
-- ----------------------------------------------------------------
DROP PROCEDURE IF EXISTS CalculatePointsAndRewards//

CREATE PROCEDURE CalculatePointsAndRewards(
    IN p_user_id INT,
    IN p_activity_type VARCHAR(50),
    IN p_activity_value INT,
    IN p_metadata JSON
)
BEGIN
    DECLARE v_base_points INT DEFAULT 0;
    DECLARE v_multiplier DECIMAL(3,2) DEFAULT 1.0;
    DECLARE v_bonus_points INT DEFAULT 0;
    DECLARE v_total_points INT DEFAULT 0;
    DECLARE v_user_level INT DEFAULT 1;
    DECLARE v_daily_limit INT DEFAULT 0;
    DECLARE v_daily_count INT DEFAULT 0;
    
    -- Error handling
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get user level for multiplier
    SELECT user_level INTO v_user_level FROM users WHERE user_id = p_user_id;
    
    -- Set level-based multiplier
    SET v_multiplier = 1.0 + (v_user_level - 1) * 0.1;
    
    -- Calculate base points based on activity type
    CASE p_activity_type
        WHEN 'article_read' THEN 
            SET v_base_points = 5;
            SET v_daily_limit = 20;
        WHEN 'article_like' THEN 
            SET v_base_points = 2;
            SET v_daily_limit = 50;
        WHEN 'article_share' THEN 
            SET v_base_points = 10;
            SET v_daily_limit = 10;
        WHEN 'quiz_complete' THEN 
            SET v_base_points = 25;
            SET v_daily_limit = 5;
        WHEN 'carbon_log' THEN 
            SET v_base_points = 15;
            SET v_daily_limit = 10;
        WHEN 'waste_classify' THEN 
            SET v_base_points = 8;
            SET v_daily_limit = 30;
        WHEN 'forum_post' THEN 
            SET v_base_points = 12;
            SET v_daily_limit = 5;
        WHEN 'event_attend' THEN 
            SET v_base_points = 50;
            SET v_daily_limit = 2;
        WHEN 'donation_make' THEN 
            SET v_base_points = 100;
            SET v_daily_limit = 3;
        WHEN 'exchange_complete' THEN 
            SET v_base_points = 30;
            SET v_daily_limit = 5;
        WHEN 'review_write' THEN 
            SET v_base_points = 20;
            SET v_daily_limit = 3;
        ELSE 
            SET v_base_points = 1;
            SET v_daily_limit = 100;
    END CASE;
    
    -- Check daily activity count
    SELECT COUNT(*) INTO v_daily_count
    FROM user_activities_comprehensive 
    WHERE user_id = p_user_id 
    AND activity_type = p_activity_type 
    AND DATE(created_at) = CURDATE();
    
    -- Apply daily limit reduction
    IF v_daily_count >= v_daily_limit THEN
        SET v_base_points = GREATEST(1, ROUND(v_base_points * 0.1));
    ELSEIF v_daily_count >= ROUND(v_daily_limit * 0.7) THEN
        SET v_base_points = ROUND(v_base_points * 0.5);
    END IF;
    
    -- Apply activity value multiplier if provided
    IF p_activity_value > 0 THEN
        SET v_base_points = v_base_points * p_activity_value;
    END IF;
    
    -- Calculate bonus points based on metadata
    IF JSON_VALID(p_metadata) THEN
        -- Quality bonus
        IF JSON_EXTRACT(p_metadata, '$.quality_score') IS NOT NULL THEN
            SET v_bonus_points = v_bonus_points + 
                ROUND(v_base_points * JSON_EXTRACT(p_metadata, '$.quality_score') * 0.5);
        END IF;
        
        -- Speed bonus
        IF JSON_EXTRACT(p_metadata, '$.completion_time') IS NOT NULL THEN
            IF JSON_EXTRACT(p_metadata, '$.completion_time') <= 30 THEN
                SET v_bonus_points = v_bonus_points + 5;
            END IF;
        END IF;
        
        -- First time bonus
        IF JSON_EXTRACT(p_metadata, '$.is_first_time') = true THEN
            SET v_bonus_points = v_bonus_points + ROUND(v_base_points * 0.5);
        END IF;
    END IF;
    
    -- Apply level multiplier
    SET v_total_points = ROUND((v_base_points + v_bonus_points) * v_multiplier);
    
    -- Update user points
    UPDATE users 
    SET green_points = green_points + v_total_points,
        experience_points = experience_points + ROUND(v_total_points * 0.3),
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    -- Log activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category, activity_description,
        points_earned, metadata, created_at
    ) VALUES (
        p_user_id, p_activity_type, 'points_calculation',
        CONCAT('Points awarded for: ', p_activity_type),
        v_total_points,
        JSON_MERGE_PATCH(
            COALESCE(p_metadata, '{}'),
            JSON_OBJECT(
                'base_points', v_base_points,
                'bonus_points', v_bonus_points,
                'level_multiplier', v_multiplier,
                'daily_count', v_daily_count,
                'daily_limit', v_daily_limit
            )
        ),
        NOW()
    );
    
    -- Check for achievements
    CALL CheckAchievements(p_user_id, p_activity_type);
    
    -- Update user level
    CALL UpdateUserLevel(p_user_id);
    
    COMMIT;
    
    -- Return points calculation
    SELECT 
        v_base_points as base_points,
        v_bonus_points as bonus_points,
        v_multiplier as level_multiplier,
        v_total_points as total_points_awarded,
        v_daily_count + 1 as daily_activity_count,
        v_daily_limit as daily_activity_limit;
        
END//

DELIMITER ;

-- ================================================================
-- PHASE 23 COMPLETION TRACKING
-- ================================================================

-- Verify all procedures were created
SELECT 
    'Phase 23: Stored Procedures & Business Logic' as phase,
    'COMPLETED' as status,
    COUNT(*) as procedures_created,
    NOW() as completion_time
FROM information_schema.routines 
WHERE routine_schema = 'environmental_platform' 
AND routine_type = 'PROCEDURE'
AND routine_name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification', 
    'CheckAchievements',
    'GenerateDailyAnalytics',
    'UpdateUserStreaks',
    'CalculatePointsAndRewards'
);

-- Test procedures exist
SHOW PROCEDURE STATUS WHERE Name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification', 
    'CheckAchievements',
    'GenerateDailyAnalytics',
    'UpdateUserStreaks',
    'CalculatePointsAndRewards'
);

SELECT 'Phase 23 Stored Procedures Created Successfully!' as result;
