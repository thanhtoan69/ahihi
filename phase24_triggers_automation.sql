-- ================================================================
-- Phase 24: Triggers & Automation Implementation
-- Environmental Platform Database Enhancement
-- 
-- Purpose: Comprehensive trigger system for automation, counters, and activity logging
-- Features: Article publication, order completion, user login triggers, counter updates, activity logging
-- Dependencies: All previous phases (1-23) with complete database structure
-- Date: 2024
-- ================================================================

USE environmental_platform;

-- Set proper SQL modes for trigger creation
SET SESSION sql_mode = '';
SET foreign_key_checks = 0;

SELECT 'PHASE 24: TRIGGERS & AUTOMATION SYSTEM IMPLEMENTATION' AS implementation_title;
SELECT 'Creating comprehensive trigger system for automation and activity logging...' AS implementation_status;

-- ================================================================
-- CORE AUTOMATION TRIGGERS
-- ================================================================

-- ----------------------------------------------------------------
-- 1. ARTICLE PUBLICATION TRIGGER (after_article_publish)
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_article_publish;

DELIMITER $$
CREATE TRIGGER after_article_publish
    AFTER UPDATE ON articles
    FOR EACH ROW
BEGIN
    DECLARE v_points_awarded INT DEFAULT 0;
    DECLARE v_experience_awarded INT DEFAULT 0;
    DECLARE v_activity_id INT DEFAULT 0;
    
    -- Only trigger when article is being published (status changes to 'published')
    IF NEW.status = 'published' AND (OLD.status != 'published' OR OLD.status IS NULL) THEN
        
        -- Calculate points based on article quality and type
        SET v_points_awarded = CASE 
            WHEN NEW.article_type = 'research' THEN 100
            WHEN NEW.article_type = 'guide' THEN 75
            WHEN NEW.article_type = 'news' THEN 50
            ELSE 50
        END;
        
        -- Bonus for difficulty level
        SET v_points_awarded = v_points_awarded + CASE 
            WHEN NEW.difficulty_level = 'advanced' THEN 25
            WHEN NEW.difficulty_level = 'intermediate' THEN 15
            ELSE 10
        END;
        
        -- Bonus for environmental impact score
        IF NEW.environmental_impact_score >= 80 THEN 
            SET v_points_awarded = v_points_awarded + 25;
        ELSEIF NEW.environmental_impact_score >= 60 THEN 
            SET v_points_awarded = v_points_awarded + 15;
        END IF;
        
        SET v_experience_awarded = v_points_awarded;
        
        -- Update user points and experience
        UPDATE users 
        SET 
            green_points = green_points + v_points_awarded,
            experience_points = experience_points + v_experience_awarded,
            last_activity = NOW(),
            updated_at = NOW()
        WHERE user_id = NEW.author_id;
        
        -- Update category post count
        UPDATE categories 
        SET post_count = post_count + 1,
            updated_at = NOW()
        WHERE category_id = NEW.category_id;
        
        -- Log comprehensive activity
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, 
            base_points, bonus_points, total_points,
            related_entity_type, related_entity_id,
            activity_description, environmental_impact,
            created_at
        ) VALUES (
            NEW.author_id, 'content_create', 'content_management',
            50, v_points_awarded - 50, v_points_awarded,
            'article', NEW.article_id,
            CONCAT('Published article: ', LEFT(NEW.title, 100)),
            NEW.environmental_impact_score,
            NOW()
        );
        
        -- Update user level if needed
        CALL UpdateUserLevel(NEW.author_id);
        
        -- Check for achievements
        CALL CheckAchievements(NEW.author_id, 'content_create');
        
        -- Update user streaks
        CALL UpdateUserStreaks(NEW.author_id, 'content_publish');
        
    END IF;
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 2. ORDER COMPLETION TRIGGER (after_order_complete)
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_order_complete;

DELIMITER $$
CREATE TRIGGER after_order_complete
    AFTER UPDATE ON orders
    FOR EACH ROW
BEGIN
    DECLARE v_points_awarded INT DEFAULT 0;
    DECLARE v_experience_awarded INT DEFAULT 0;
    DECLARE v_eco_bonus INT DEFAULT 0;
    DECLARE v_order_total DECIMAL(10,2) DEFAULT 0;
    
    -- Only trigger when order status changes to 'completed' or 'delivered'
    IF NEW.order_status IN ('completed', 'delivered') AND 
       OLD.order_status NOT IN ('completed', 'delivered') THEN
        
        -- Get order total
        SET v_order_total = COALESCE(NEW.total_amount, 0);
        
        -- Calculate base points (1 point per 10 currency units)
        SET v_points_awarded = FLOOR(v_order_total / 10);
        
        -- Calculate eco-friendly bonus
        SELECT COALESCE(AVG(p.eco_score), 0) INTO v_eco_bonus
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = NEW.order_id;
        
        -- Apply eco bonus
        IF v_eco_bonus >= 80 THEN 
            SET v_points_awarded = v_points_awarded + 50;
        ELSEIF v_eco_bonus >= 60 THEN 
            SET v_points_awarded = v_points_awarded + 25;
        ELSEIF v_eco_bonus >= 40 THEN 
            SET v_points_awarded = v_points_awarded + 10;
        END IF;
        
        SET v_experience_awarded = v_points_awarded;
        
        -- Update user points and experience
        UPDATE users 
        SET 
            green_points = green_points + v_points_awarded,
            experience_points = experience_points + v_experience_awarded,
            last_activity = NOW(),
            updated_at = NOW()
        WHERE user_id = NEW.user_id;
        
        -- Log comprehensive activity
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category,
            base_points, bonus_points, total_points,
            related_entity_type, related_entity_id,
            activity_description, metadata,
            created_at
        ) VALUES (
            NEW.user_id, 'purchase_complete', 'e_commerce',
            FLOOR(v_order_total / 10), v_points_awarded - FLOOR(v_order_total / 10), v_points_awarded,
            'order', NEW.order_id,
            CONCAT('Completed eco-friendly order #', NEW.order_id),
            JSON_OBJECT('order_total', v_order_total, 'eco_score', v_eco_bonus),
            NOW()
        );
        
        -- Update seller statistics
        UPDATE users 
        SET green_points = green_points + FLOOR(v_order_total / 20),
            last_activity = NOW()
        WHERE user_id = NEW.seller_id AND NEW.seller_id IS NOT NULL;
        
        -- Update user level if needed
        CALL UpdateUserLevel(NEW.user_id);
        
        -- Check for achievements
        CALL CheckAchievements(NEW.user_id, 'purchase_complete');
        
        -- Update user streaks
        CALL UpdateUserStreaks(NEW.user_id, 'eco_purchase');
        
    END IF;
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 3. USER LOGIN TRIGGER (after_user_login)
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_user_login;

DELIMITER $$
CREATE TRIGGER after_user_login
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    DECLARE v_daily_points INT DEFAULT 5;
    DECLARE v_streak_bonus INT DEFAULT 0;
    DECLARE v_consecutive_days INT DEFAULT 0;
    DECLARE v_last_login_date DATE;
    DECLARE v_current_date DATE DEFAULT CURDATE();
    
    -- Only trigger when last_login is updated (login activity)
    IF NEW.last_login != OLD.last_login AND NEW.last_login IS NOT NULL THEN
        
        SET v_last_login_date = DATE(OLD.last_login);
        
        -- Calculate login streak
        IF v_last_login_date IS NULL THEN
            -- First login ever
            SET v_consecutive_days = 1;
        ELSEIF v_last_login_date = DATE_SUB(v_current_date, INTERVAL 1 DAY) THEN
            -- Consecutive day login
            SET v_consecutive_days = COALESCE(OLD.login_streak, 0) + 1;
        ELSEIF v_last_login_date = v_current_date THEN
            -- Same day login (no streak change)
            SET v_consecutive_days = COALESCE(OLD.login_streak, 1);
            SET v_daily_points = 0; -- No points for multiple logins same day
        ELSE
            -- Gap in login, reset streak
            SET v_consecutive_days = 1;
        END IF;
        
        -- Calculate streak bonus
        IF v_consecutive_days >= 30 THEN 
            SET v_streak_bonus = 50;
        ELSEIF v_consecutive_days >= 14 THEN 
            SET v_streak_bonus = 25;
        ELSEIF v_consecutive_days >= 7 THEN 
            SET v_streak_bonus = 10;
        ELSEIF v_consecutive_days >= 3 THEN 
            SET v_streak_bonus = 5;
        END IF;
        
        -- Update user statistics only if points to be awarded
        IF v_daily_points > 0 OR v_streak_bonus > 0 THEN
            UPDATE users 
            SET 
                green_points = green_points + v_daily_points + v_streak_bonus,
                experience_points = experience_points + v_daily_points + v_streak_bonus,
                login_streak = v_consecutive_days,
                longest_streak = GREATEST(longest_streak, v_consecutive_days),
                updated_at = NOW()
            WHERE user_id = NEW.user_id;
            
            -- Log login activity
            INSERT INTO user_activities_comprehensive (
                user_id, activity_type, activity_category,
                base_points, bonus_points, total_points,
                activity_description, metadata,
                created_at
            ) VALUES (
                NEW.user_id, 'login', 'user_engagement',
                v_daily_points, v_streak_bonus, v_daily_points + v_streak_bonus,
                CONCAT('Daily login - Streak: ', v_consecutive_days, ' days'),
                JSON_OBJECT('login_streak', v_consecutive_days, 'streak_bonus', v_streak_bonus),
                NOW()
            );
        ELSE
            -- Update streak without points
            UPDATE users 
            SET 
                login_streak = v_consecutive_days,
                longest_streak = GREATEST(longest_streak, v_consecutive_days),
                updated_at = NOW()
            WHERE user_id = NEW.user_id;
        END IF;
        
        -- Check for login-based achievements
        CALL CheckAchievements(NEW.user_id, 'login');
        
        -- Update user streaks
        CALL UpdateUserStreaks(NEW.user_id, 'daily_login');
        
    END IF;
END$$
DELIMITER ;

-- ================================================================
-- AUTOMATIC COUNTER UPDATE TRIGGERS
-- ================================================================

-- ----------------------------------------------------------------
-- 4. ARTICLE INTERACTION COUNTER TRIGGERS
-- ----------------------------------------------------------------

-- Drop existing triggers if exist
DROP TRIGGER IF EXISTS after_article_interaction_insert;
DROP TRIGGER IF EXISTS after_article_interaction_delete;

DELIMITER $$
CREATE TRIGGER after_article_interaction_insert
    AFTER INSERT ON article_interactions
    FOR EACH ROW
BEGIN
    DECLARE v_points INT DEFAULT 0;
    
    -- Update article counters
    UPDATE articles 
    SET 
        view_count = CASE WHEN NEW.interaction_type = 'view' THEN view_count + 1 ELSE view_count END,
        like_count = CASE WHEN NEW.interaction_type = 'like' THEN like_count + 1 ELSE like_count END,
        share_count = CASE WHEN NEW.interaction_type = 'share' THEN share_count + 1 ELSE share_count END,
        updated_at = NOW()
    WHERE article_id = NEW.article_id;
    
    -- Award points for engagement
    SET v_points = CASE 
        WHEN NEW.interaction_type = 'view' THEN 1
        WHEN NEW.interaction_type = 'like' THEN 5
        WHEN NEW.interaction_type = 'share' THEN 10
        WHEN NEW.interaction_type = 'bookmark' THEN 3
        ELSE 1
    END;
    
    -- Update user points
    UPDATE users 
    SET 
        green_points = green_points + v_points,
        experience_points = experience_points + v_points,
        last_activity = NOW()
    WHERE user_id = NEW.user_id;
    
    -- Log interaction activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category,
        base_points, total_points,
        related_entity_type, related_entity_id,
        activity_description,
        created_at
    ) VALUES (
        NEW.user_id, CONCAT('article_', NEW.interaction_type), 'content_engagement',
        v_points, v_points,
        'article', NEW.article_id,
        CONCAT('Article ', NEW.interaction_type, ' interaction'),
        NOW()
    );
END$$

CREATE TRIGGER after_article_interaction_delete
    AFTER DELETE ON article_interactions
    FOR EACH ROW
BEGIN
    -- Update article counters (decrement)
    UPDATE articles 
    SET 
        view_count = CASE WHEN OLD.interaction_type = 'view' AND view_count > 0 THEN view_count - 1 ELSE view_count END,
        like_count = CASE WHEN OLD.interaction_type = 'like' AND like_count > 0 THEN like_count - 1 ELSE like_count END,
        share_count = CASE WHEN OLD.interaction_type = 'share' AND share_count > 0 THEN share_count - 1 ELSE share_count END,
        updated_at = NOW()
    WHERE article_id = OLD.article_id;
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 5. USER REGISTRATION TRIGGER
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_user_registration;

DELIMITER $$
CREATE TRIGGER after_user_registration
    AFTER INSERT ON users
    FOR EACH ROW
BEGIN
    -- Award welcome bonus points
    UPDATE users 
    SET 
        green_points = 100,
        experience_points = 100,
        user_level = 1,
        login_streak = 0,
        longest_streak = 0
    WHERE user_id = NEW.user_id;
    
    -- Log registration activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category,
        base_points, total_points,
        activity_description,
        created_at
    ) VALUES (
        NEW.user_id, 'registration', 'user_management',
        100, 100,
        'New user registration welcome bonus',
        NOW()
    );
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 6. COMMENT ACTIVITY TRIGGER
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_comment_insert;

DELIMITER $$
CREATE TRIGGER after_comment_insert
    AFTER INSERT ON article_comments
    FOR EACH ROW
BEGIN
    DECLARE v_points INT DEFAULT 10;
    
    -- Award points for commenting
    UPDATE users 
    SET 
        green_points = green_points + v_points,
        experience_points = experience_points + v_points,
        last_activity = NOW()
    WHERE user_id = NEW.user_id;
    
    -- Log comment activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category,
        base_points, total_points,
        related_entity_type, related_entity_id,
        activity_description,
        created_at
    ) VALUES (
        NEW.user_id, 'comment_create', 'content_engagement',
        v_points, v_points,
        'article', NEW.article_id,
        'Posted article comment',
        NOW()
    );
    
    -- Notify article author (update their engagement metrics)
    UPDATE users 
    SET last_activity = NOW()
    WHERE user_id = (SELECT author_id FROM articles WHERE article_id = NEW.article_id);
END$$
DELIMITER ;

-- ================================================================
-- ACTIVITY LOGGING AUTOMATION
-- ================================================================

-- ----------------------------------------------------------------
-- 7. CARBON FOOTPRINT TRACKING TRIGGER
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_carbon_footprint_insert;

DELIMITER $$
CREATE TRIGGER after_carbon_footprint_insert
    AFTER INSERT ON carbon_footprints
    FOR EACH ROW
BEGIN
    DECLARE v_points INT DEFAULT 0;
    DECLARE v_carbon_saved DECIMAL(10,2) DEFAULT 0;
    
    -- Calculate points based on carbon reduction
    SET v_carbon_saved = ABS(NEW.carbon_kg);
    SET v_points = FLOOR(v_carbon_saved * 10); -- 10 points per kg saved
    
    -- Cap maximum points per activity
    IF v_points > 500 THEN SET v_points = 500; END IF;
    
    -- Award points for carbon tracking
    UPDATE users 
    SET 
        green_points = green_points + v_points,
        experience_points = experience_points + v_points,
        total_carbon_saved = total_carbon_saved + v_carbon_saved,
        last_activity = NOW()
    WHERE user_id = NEW.user_id;
    
    -- Log carbon tracking activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category,
        base_points, total_points,
        related_entity_type, related_entity_id,
        activity_description, environmental_impact,
        metadata,
        created_at
    ) VALUES (
        NEW.user_id, 'carbon_tracking', 'environmental_action',
        v_points, v_points,
        'carbon_footprint', NEW.footprint_id,
        CONCAT('Tracked carbon footprint: ', NEW.activity_category),
        FLOOR(v_carbon_saved),
        JSON_OBJECT('carbon_kg', NEW.carbon_kg, 'activity_category', NEW.activity_category),
        NOW()
    );
    
    -- Check for environmental achievements
    CALL CheckAchievements(NEW.user_id, 'carbon_tracking');
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 8. QUIZ COMPLETION TRIGGER
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS after_quiz_completion;

DELIMITER $$
CREATE TRIGGER after_quiz_completion
    AFTER UPDATE ON quiz_sessions
    FOR EACH ROW
BEGIN
    DECLARE v_points INT DEFAULT 0;
    DECLARE v_bonus INT DEFAULT 0;
    
    -- Only trigger when quiz is completed
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        
        -- Calculate base points
        SET v_points = CASE 
            WHEN NEW.session_type = 'assessment' THEN 30
            WHEN NEW.session_type = 'practice' THEN 20
            ELSE 15
        END;
        
        -- Performance bonus
        IF NEW.score >= 90 THEN 
            SET v_bonus = 20;
        ELSEIF NEW.score >= 80 THEN 
            SET v_bonus = 15;
        ELSEIF NEW.score >= 70 THEN 
            SET v_bonus = 10;
        END IF;
        
        -- Award points
        UPDATE users 
        SET 
            green_points = green_points + v_points + v_bonus,
            experience_points = experience_points + v_points + v_bonus,
            last_activity = NOW()
        WHERE user_id = NEW.user_id;
        
        -- Log quiz activity
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category,
            base_points, bonus_points, total_points,
            related_entity_type, related_entity_id,
            activity_description, metadata,
            created_at
        ) VALUES (
            NEW.user_id, 'quiz_complete', 'learning',
            v_points, v_bonus, v_points + v_bonus,
            'quiz_session', NEW.session_id,
            CONCAT('Completed ', NEW.session_type, ' quiz - Score: ', NEW.score, '%'),
            JSON_OBJECT('score', NEW.score, 'session_type', NEW.session_type),
            NOW()
        );
        
        -- Check for learning achievements
        CALL CheckAchievements(NEW.user_id, 'quiz_complete');
    END IF;
END$$
DELIMITER ;

-- ================================================================
-- SYSTEM OPTIMIZATION TRIGGERS
-- ================================================================

-- ----------------------------------------------------------------
-- 9. DAILY ANALYTICS AUTOMATION TRIGGER
-- ----------------------------------------------------------------

-- Create event scheduler for daily analytics (if not exists)
SET GLOBAL event_scheduler = ON;

-- Drop existing event if exists
DROP EVENT IF EXISTS daily_analytics_automation;

DELIMITER $$
CREATE EVENT daily_analytics_automation
    ON SCHEDULE EVERY 1 DAY
    STARTS TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '01:00:00')
    DO
BEGIN
    -- Generate daily analytics
    CALL GenerateDailyAnalytics();
    
    -- Log system activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category,
        activity_description, created_at
    ) VALUES (
        1, 'system_analytics', 'system_maintenance',
        'Daily analytics generation completed',
        NOW()
    );
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 10. USER LEVEL UPDATE AUTOMATION
-- ----------------------------------------------------------------

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS check_user_level_update;

DELIMITER $$
CREATE TRIGGER check_user_level_update
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    DECLARE v_new_level INT;
    
    -- Only check if experience points changed significantly
    IF NEW.experience_points - OLD.experience_points >= 10 THEN
        
        -- Calculate new level based on experience
        SET v_new_level = CASE 
            WHEN NEW.experience_points >= 10000 THEN 10
            WHEN NEW.experience_points >= 5000 THEN 9
            WHEN NEW.experience_points >= 2500 THEN 8
            WHEN NEW.experience_points >= 1500 THEN 7
            WHEN NEW.experience_points >= 1000 THEN 6
            WHEN NEW.experience_points >= 700 THEN 5
            WHEN NEW.experience_points >= 500 THEN 4
            WHEN NEW.experience_points >= 300 THEN 3
            WHEN NEW.experience_points >= 150 THEN 2
            ELSE 1
        END;
        
        -- Update level if changed
        IF v_new_level > OLD.user_level THEN
            UPDATE users 
            SET user_level = v_new_level
            WHERE user_id = NEW.user_id;
            
            -- Log level up activity
            INSERT INTO user_activities_comprehensive (
                user_id, activity_type, activity_category,
                base_points, total_points,
                activity_description, metadata,
                created_at
            ) VALUES (
                NEW.user_id, 'level_up', 'user_progression',
                50, 50,
                CONCAT('Leveled up to Level ', v_new_level),
                JSON_OBJECT('old_level', OLD.user_level, 'new_level', v_new_level),
                NOW()
            );
        END IF;
    END IF;
END$$
DELIMITER ;

-- ================================================================
-- MONTHLY ANALYTICS AUTOMATION
-- ================================================================

-- Drop existing event if exists
DROP EVENT IF EXISTS monthly_analytics_automation;

DELIMITER $$
CREATE EVENT monthly_analytics_automation
    ON SCHEDULE EVERY 1 MONTH
    STARTS TIMESTAMP(LAST_DAY(CURDATE()) + INTERVAL 1 DAY, '02:00:00')
    DO
BEGIN
    DECLARE v_month_year VARCHAR(7);
    SET v_month_year = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m');
    
    -- Insert monthly summary
    INSERT INTO monthly_analytics_summary (
        month_year, total_users, active_users, total_activities,
        total_points_awarded, total_articles, total_orders,
        created_at
    )
    SELECT
        v_month_year,
        COUNT(DISTINCT u.user_id),
        COUNT(DISTINCT CASE WHEN u.last_activity >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN u.user_id END),
        COALESCE(SUM(das.total_activities), 0),
        COALESCE(SUM(das.total_points_awarded), 0),
        COALESCE(SUM(das.articles_published), 0),
        COALESCE(SUM(das.orders_completed), 0),
        NOW()
    FROM users u
    CROSS JOIN (
        SELECT
            SUM(total_activities) as total_activities,
            SUM(total_points_awarded) as total_points_awarded,
            SUM(articles_published) as articles_published,
            SUM(orders_completed) as orders_completed
        FROM daily_analytics_summary
        WHERE date_recorded >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ) das;
END$$
DELIMITER ;

-- ================================================================
-- PERFORMANCE OPTIMIZATION TRIGGERS
-- ================================================================

-- ----------------------------------------------------------------
-- 11. CACHE INVALIDATION TRIGGERS
-- ----------------------------------------------------------------

-- Drop existing triggers if exist
DROP TRIGGER IF EXISTS invalidate_user_cache;
DROP TRIGGER IF EXISTS invalidate_article_cache;

DELIMITER $$
CREATE TRIGGER invalidate_user_cache
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    -- Update cache timestamp for user-related data
    IF NEW.green_points != OLD.green_points OR 
       NEW.user_level != OLD.user_level OR 
       NEW.experience_points != OLD.experience_points THEN
        
        -- Insert cache invalidation record
        INSERT INTO cache_invalidation_log (
            entity_type, entity_id, invalidation_reason, created_at
        ) VALUES (
            'user', NEW.user_id, 'user_stats_updated', NOW()
        );
    END IF;
END$$

CREATE TRIGGER invalidate_article_cache
    AFTER UPDATE ON articles
    FOR EACH ROW
BEGIN
    -- Update cache timestamp for article-related data
    IF NEW.view_count != OLD.view_count OR 
       NEW.like_count != OLD.like_count OR 
       NEW.share_count != OLD.share_count THEN
        
        -- Insert cache invalidation record
        INSERT INTO cache_invalidation_log (
            entity_type, entity_id, invalidation_reason, created_at
        ) VALUES (
            'article', NEW.article_id, 'article_stats_updated', NOW()
        );
    END IF;
END$$
DELIMITER ;

-- ================================================================
-- DATA CLEANUP AUTOMATION
-- ================================================================

-- Drop existing event if exists
DROP EVENT IF EXISTS weekly_data_cleanup;

DELIMITER $$
CREATE EVENT weekly_data_cleanup
    ON SCHEDULE EVERY 1 WEEK
    STARTS TIMESTAMP(CURDATE() + INTERVAL (7 - WEEKDAY(CURDATE())) DAY, '03:00:00')
    DO
BEGIN
    -- Cleanup old session data (older than 30 days)
    DELETE FROM user_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Cleanup old cache invalidation logs (older than 7 days)
    DELETE FROM cache_invalidation_log 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Cleanup old activity logs (keep only last 6 months)
    DELETE FROM user_activities_comprehensive 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Log cleanup activity
    INSERT INTO user_activities_comprehensive (
        user_id, activity_type, activity_category,
        activity_description, created_at
    ) VALUES (
        1, 'system_cleanup', 'system_maintenance',
        'Weekly data cleanup completed',
        NOW()
    );
END$$
DELIMITER ;

-- ================================================================
-- TRIGGER VERIFICATION & TESTING
-- ================================================================

-- Reset SQL mode and foreign key checks
SET foreign_key_checks = 1;
SET SESSION sql_mode = DEFAULT;

-- Display trigger creation results
SELECT 'PHASE 24 TRIGGER IMPLEMENTATION COMPLETED' AS implementation_status;

-- Show created triggers
SELECT 
    'TRIGGERS CREATED:' as status,
    COUNT(*) as trigger_count
FROM INFORMATION_SCHEMA.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'environmental_platform'
AND TRIGGER_NAME IN (
    'after_article_publish',
    'after_order_complete', 
    'after_user_login',
    'after_article_interaction_insert',
    'after_article_interaction_delete',
    'after_user_registration',
    'after_comment_insert',
    'after_carbon_footprint_insert',
    'after_quiz_completion',
    'check_user_level_update',
    'invalidate_user_cache',
    'invalidate_article_cache'
);

-- Show created events
SELECT 
    'AUTOMATION EVENTS CREATED:' as status,
    COUNT(*) as event_count
FROM INFORMATION_SCHEMA.EVENTS 
WHERE EVENT_SCHEMA = 'environmental_platform'
AND EVENT_NAME IN (
    'daily_analytics_automation',
    'monthly_analytics_automation',
    'weekly_data_cleanup'
);

-- List all triggers
SELECT 
    TRIGGER_NAME as trigger_name,
    EVENT_MANIPULATION as event_type,
    EVENT_OBJECT_TABLE as table_name,
    ACTION_TIMING as timing
FROM INFORMATION_SCHEMA.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'environmental_platform'
ORDER BY EVENT_OBJECT_TABLE, ACTION_TIMING;

SELECT 'Phase 24: Triggers & Automation - CORE IMPLEMENTATION COMPLETE!' as final_status;
SELECT 'Created comprehensive trigger system with automation, counters, and activity logging' as summary;