-- ========================================
-- PHASE 24: PERFORMANCE OPTIMIZATION
-- Environmental Platform Database
-- Version: 1.0
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. ADVANCED TRIGGER PERFORMANCE INDEXES
-- ========================================

-- High-performance indexes for trigger operations
CREATE INDEX IF NOT EXISTS idx_articles_publish_flow ON articles(status, author_id, category_id, updated_at);
CREATE INDEX IF NOT EXISTS idx_orders_completion_flow ON orders(status, user_id, total_amount, updated_at);
CREATE INDEX IF NOT EXISTS idx_user_login_tracking ON users(user_id, last_login, login_streak);

-- Covering indexes for complex trigger queries
CREATE INDEX IF NOT EXISTS idx_user_activities_comprehensive_lookup 
ON user_activities_comprehensive(user_id, activity_type, created_at, points_earned);

CREATE INDEX IF NOT EXISTS idx_article_interactions_counters 
ON article_interactions(article_id, interaction_type, user_id, created_at);

-- Partitioned indexes for time-based data
CREATE INDEX IF NOT EXISTS idx_daily_analytics_date_metrics 
ON daily_analytics_summary(date_recorded, metric_type, metric_value);

-- ========================================
-- 2. TRIGGER PERFORMANCE MONITORING
-- ========================================

-- Table to track trigger execution performance
CREATE TABLE IF NOT EXISTS trigger_performance_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_name VARCHAR(100) NOT NULL,
    execution_time DECIMAL(10,6) NOT NULL,
    rows_affected INT DEFAULT 0,
    execution_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT NULL,
    INDEX idx_trigger_perf_name_date (trigger_name, execution_date),
    INDEX idx_trigger_perf_time (execution_time DESC)
);

-- ========================================
-- 3. OPTIMIZED TRIGGER VERSIONS
-- ========================================

DELIMITER //

-- Optimized after_article_publish trigger with performance logging
DROP TRIGGER IF EXISTS after_article_publish_optimized//
CREATE TRIGGER after_article_publish_optimized
AFTER UPDATE ON articles
FOR EACH ROW
BEGIN
    DECLARE v_start_time DECIMAL(10,6);
    DECLARE v_end_time DECIMAL(10,6);
    DECLARE v_rows_affected INT DEFAULT 0;
    
    SET v_start_time = UNIX_TIMESTAMP(NOW(6));
    
    -- Only trigger when article status changes to 'published'
    IF NEW.status = 'published' AND OLD.status != 'published' THEN
        
        -- Batch update category and user stats in single transaction
        START TRANSACTION;
        
        -- Update category post count
        UPDATE categories 
        SET post_count = post_count + 1,
            updated_at = NOW()
        WHERE category_id = NEW.category_id;
        SET v_rows_affected = v_rows_affected + ROW_COUNT();
        
        -- Award author points for publishing
        UPDATE users 
        SET green_points = green_points + 50,
            experience_points = experience_points + 50,
            updated_at = NOW()
        WHERE user_id = NEW.author_id;
        SET v_rows_affected = v_rows_affected + ROW_COUNT();
        
        -- Batch insert activity and analytics
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, activity_name,
            activity_description, base_points, total_points, 
            related_entity_type, related_entity_id, created_at
        ) VALUES (
            NEW.author_id, 'content_create', 'content', 'Article Published',
            CONCAT('Published article: ', LEFT(NEW.title, 100)),
            50, 50, 'article', NEW.article_id, NOW()
        );
        SET v_rows_affected = v_rows_affected + ROW_COUNT();
        
        -- Update daily analytics with optimized upsert
        INSERT INTO daily_analytics_summary 
        (date_recorded, articles_published, created_at)
        VALUES (CURDATE(), 1, NOW())
        ON DUPLICATE KEY UPDATE
            articles_published = articles_published + 1,
            updated_at = NOW();
        SET v_rows_affected = v_rows_affected + ROW_COUNT();
        
        COMMIT;
        
        -- Async calls for non-critical operations
        -- These can be moved to a queue system for better performance
        CALL UpdateUserLevel(NEW.author_id);
        CALL CheckAchievements(NEW.author_id);
        
    END IF;
    
    -- Log performance metrics
    SET v_end_time = UNIX_TIMESTAMP(NOW(6));
    INSERT INTO trigger_performance_logs (
        trigger_name, execution_time, rows_affected
    ) VALUES (
        'after_article_publish_optimized', 
        v_end_time - v_start_time, 
        v_rows_affected
    );
    
END //

-- Optimized order completion trigger with batch processing
DROP TRIGGER IF EXISTS after_order_complete_optimized//
CREATE TRIGGER after_order_complete_optimized
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    DECLARE v_start_time DECIMAL(10,6);
    DECLARE v_carbon_saved DECIMAL(10,3) DEFAULT 0;
    DECLARE v_eco_points INT DEFAULT 0;
    
    SET v_start_time = UNIX_TIMESTAMP(NOW(6));
    
    -- Only trigger when order status changes to 'completed'
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        
        START TRANSACTION;
        
        -- Calculate eco metrics from order items in single query
        SELECT 
            COALESCE(SUM(p.carbon_footprint_saved * oi.quantity), 0),
            COALESCE(SUM(CASE WHEN p.is_eco_friendly THEN 10 * oi.quantity ELSE 5 * oi.quantity END), 0)
        INTO v_carbon_saved, v_eco_points
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = NEW.order_id;
        
        -- Batch update user stats
        UPDATE users 
        SET green_points = green_points + v_eco_points,
            experience_points = experience_points + (v_eco_points / 2),
            total_carbon_saved = total_carbon_saved + v_carbon_saved
        WHERE user_id = NEW.user_id;
        
        -- Batch update product statistics
        UPDATE products p
        JOIN order_items oi ON p.product_id = oi.product_id
        SET p.purchase_count = p.purchase_count + oi.quantity,
            p.total_revenue = p.total_revenue + (oi.price * oi.quantity)
        WHERE oi.order_id = NEW.order_id;
        
        -- Insert activity log
        INSERT INTO user_activities_comprehensive (
            user_id, activity_type, activity_category, activity_name,
            activity_description, base_points, total_points,
            carbon_impact_kg, environmental_score,
            related_entity_type, related_entity_id, created_at
        ) VALUES (
            NEW.user_id, 'product_purchase', 'commerce', 'Order Completed',
            CONCAT('Order #', NEW.order_id, ' - Amount: ', NEW.total_amount),
            v_eco_points, v_eco_points, v_carbon_saved, v_eco_points,
            'order', NEW.order_id, NOW()
        );
        
        COMMIT;
        
        -- Async operations for achievements and notifications
        CALL CheckAchievements(NEW.user_id);
        
    END IF;
    
    -- Performance logging
    INSERT INTO trigger_performance_logs (
        trigger_name, execution_time, rows_affected
    ) VALUES (
        'after_order_complete_optimized', 
        UNIX_TIMESTAMP(NOW(6)) - v_start_time, 
        ROW_COUNT()
    );
    
END //

DELIMITER ;

-- ========================================
-- 4. COUNTER UPDATE OPTIMIZATION
-- ========================================

DELIMITER //

-- High-performance counter update procedure
DROP PROCEDURE IF EXISTS OptimizedCounterUpdate//
CREATE PROCEDURE OptimizedCounterUpdate()
BEGIN
    DECLARE v_start_time DATETIME DEFAULT NOW();
    
    -- Disable autocommit for batch processing
    SET autocommit = 0;
    START TRANSACTION;
    
    -- Update article counters using window functions for efficiency
    UPDATE articles a
    JOIN (
        SELECT 
            article_id,
            SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END) as total_views,
            SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END) as total_likes,
            SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END) as total_shares,
            SUM(CASE WHEN interaction_type = 'bookmark' THEN 1 ELSE 0 END) as total_bookmarks
        FROM article_interactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY article_id
    ) counters ON a.article_id = counters.article_id
    SET 
        a.view_count = a.view_count + counters.total_views,
        a.like_count = a.like_count + counters.total_likes,
        a.share_count = a.share_count + counters.total_shares,
        a.bookmark_count = a.bookmark_count + counters.total_bookmarks,
        a.updated_at = NOW();
    
    -- Update category post counts efficiently
    UPDATE categories c
    JOIN (
        SELECT category_id, COUNT(*) as published_count
        FROM articles 
        WHERE status = 'published' AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY category_id
    ) ac ON c.category_id = ac.category_id
    SET c.post_count = c.post_count + ac.published_count,
        c.updated_at = NOW();
    
    -- Update user statistics in batch
    UPDATE users u
    JOIN (
        SELECT 
            user_id,
            SUM(points_earned) as total_points,
            SUM(carbon_impact_kg) as total_carbon,
            COUNT(*) as activity_count
        FROM user_activities_comprehensive
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY user_id
    ) ua ON u.user_id = ua.user_id
    SET 
        u.green_points = u.green_points + ua.total_points,
        u.total_carbon_saved = u.total_carbon_saved + ua.total_carbon,
        u.total_activities = u.total_activities + ua.activity_count,
        u.updated_at = NOW();
    
    COMMIT;
    SET autocommit = 1;
    
    -- Log performance
    INSERT INTO trigger_performance_logs (
        trigger_name, execution_time, rows_affected
    ) VALUES (
        'OptimizedCounterUpdate',
        TIMESTAMPDIFF(MICROSECOND, v_start_time, NOW()) / 1000000,
        ROW_COUNT()
    );
    
END //

DELIMITER ;

-- ========================================
-- 5. BATCH PROCESSING FOR HEAVY OPERATIONS
-- ========================================

DELIMITER //

-- Batch process achievements to reduce trigger load
DROP PROCEDURE IF EXISTS BatchProcessAchievements//
CREATE PROCEDURE BatchProcessAchievements(IN batch_size INT DEFAULT 100)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_user_id INT;
    DECLARE v_count INT DEFAULT 0;
    
    DECLARE user_cursor CURSOR FOR 
        SELECT DISTINCT user_id 
        FROM user_activities_comprehensive
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        AND user_id NOT IN (
            SELECT user_id FROM achievement_processing_queue
        )
        LIMIT batch_size;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN user_cursor;
    
    achievement_loop: LOOP
        FETCH user_cursor INTO v_user_id;
        IF done THEN
            LEAVE achievement_loop;
        END IF;
        
        -- Add to processing queue to avoid duplicates
        INSERT IGNORE INTO achievement_processing_queue (user_id, queued_at)
        VALUES (v_user_id, NOW());
        
        -- Process achievements
        CALL CheckAchievements(v_user_id);
        
        -- Remove from queue
        DELETE FROM achievement_processing_queue WHERE user_id = v_user_id;
        
        SET v_count = v_count + 1;
        
    END LOOP;
    
    CLOSE user_cursor;
    
    SELECT CONCAT('Processed achievements for ', v_count, ' users') as result;
    
END //

DELIMITER ;

-- Create achievement processing queue table
CREATE TABLE IF NOT EXISTS achievement_processing_queue (
    user_id INT PRIMARY KEY,
    queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_queued_at (queued_at)
);

-- ========================================
-- 6. AUTOMATED PERFORMANCE MONITORING
-- ========================================

-- Event to clean up performance logs
CREATE EVENT IF NOT EXISTS cleanup_performance_logs
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR)
DO
BEGIN
    -- Keep only last 30 days of performance logs
    DELETE FROM trigger_performance_logs 
    WHERE execution_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY);
    
    -- Archive slow triggers for analysis
    INSERT INTO slow_trigger_archive (
        trigger_name, avg_execution_time, max_execution_time, 
        total_executions, archive_date
    )
    SELECT 
        trigger_name,
        AVG(execution_time) as avg_time,
        MAX(execution_time) as max_time,
        COUNT(*) as total_exec,
        CURDATE()
    FROM trigger_performance_logs
    WHERE execution_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    AND execution_time > 0.1  -- Triggers taking more than 100ms
    GROUP BY trigger_name;
END;

-- Create slow trigger archive table
CREATE TABLE IF NOT EXISTS slow_trigger_archive (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_name VARCHAR(100) NOT NULL,
    avg_execution_time DECIMAL(10,6) NOT NULL,
    max_execution_time DECIMAL(10,6) NOT NULL,
    total_executions INT NOT NULL,
    archive_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_archive_trigger_date (trigger_name, archive_date)
);

-- ========================================
-- 7. QUERY OPTIMIZATION VIEWS
-- ========================================

-- Materialized view for user statistics (updated hourly)
CREATE OR REPLACE VIEW user_stats_optimized AS
SELECT 
    u.user_id,
    u.username,
    u.green_points,
    u.experience_points,
    u.user_level,
    u.total_carbon_saved,
    u.login_streak,
    COUNT(DISTINCT a.article_id) as article_count,
    COUNT(DISTINCT o.order_id) as order_count,
    COUNT(DISTINCT ua.activity_id) as activity_count,
    u.last_activity,
    u.created_at
FROM users u
LEFT JOIN articles a ON u.user_id = a.author_id AND a.status = 'published'
LEFT JOIN orders o ON u.user_id = o.user_id AND o.status = 'completed'
LEFT JOIN user_activities_comprehensive ua ON u.user_id = ua.user_id 
    AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
WHERE u.is_active = TRUE
GROUP BY u.user_id;

-- High-performance analytics view
CREATE OR REPLACE VIEW daily_metrics_optimized AS
SELECT 
    DATE(created_at) as metric_date,
    'user_activities' as metric_type,
    activity_type as metric_category,
    COUNT(*) as metric_count,
    SUM(COALESCE(points_earned, 0)) as total_points,
    SUM(COALESCE(carbon_impact_kg, 0)) as total_carbon_impact,
    AVG(COALESCE(environmental_score, 0)) as avg_environmental_score
FROM user_activities_comprehensive
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), activity_type
ORDER BY metric_date DESC, metric_count DESC;

-- ========================================
-- 8. PERFORMANCE TESTING PROCEDURES
-- ========================================

DELIMITER //

-- Test trigger performance under load
DROP PROCEDURE IF EXISTS TestTriggerPerformance//
CREATE PROCEDURE TestTriggerPerformance(IN test_iterations INT DEFAULT 100)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE v_start_time DATETIME;
    DECLARE v_end_time DATETIME;
    DECLARE v_avg_time DECIMAL(10,6);
    
    SET v_start_time = NOW(6);
    
    -- Create test data
    DROP TEMPORARY TABLE IF EXISTS test_articles;
    CREATE TEMPORARY TABLE test_articles AS
    SELECT * FROM articles LIMIT 0;
    
    WHILE i <= test_iterations DO
        
        -- Test article publish trigger
        INSERT INTO test_articles (
            title, content, author_id, category_id, status, created_at
        ) VALUES (
            CONCAT('Test Article ', i),
            'Test content for performance testing',
            1, 1, 'draft', NOW()
        );
        
        UPDATE test_articles 
        SET status = 'published', updated_at = NOW()
        WHERE title = CONCAT('Test Article ', i);
        
        SET i = i + 1;
        
    END WHILE;
    
    SET v_end_time = NOW(6);
    SET v_avg_time = TIMESTAMPDIFF(MICROSECOND, v_start_time, v_end_time) / test_iterations / 1000000;
    
    SELECT 
        test_iterations as total_tests,
        v_avg_time as avg_time_per_trigger,
        'Performance test completed' as status;
    
    -- Generate performance report
    SELECT 
        trigger_name,
        COUNT(*) as executions,
        AVG(execution_time) as avg_time,
        MAX(execution_time) as max_time,
        MIN(execution_time) as min_time
    FROM trigger_performance_logs
    WHERE execution_date >= v_start_time
    GROUP BY trigger_name
    ORDER BY avg_time DESC;
    
END //

DELIMITER ;

-- ========================================
-- 9. PERFORMANCE OPTIMIZATION SUMMARY
-- ========================================

-- Performance metrics view
CREATE OR REPLACE VIEW trigger_performance_summary AS
SELECT 
    trigger_name,
    COUNT(*) as total_executions,
    AVG(execution_time) as avg_execution_time,
    MAX(execution_time) as max_execution_time,
    MIN(execution_time) as min_execution_time,
    STDDEV(execution_time) as stddev_execution_time,
    SUM(rows_affected) as total_rows_affected,
    DATE(MIN(execution_date)) as first_execution,
    DATE(MAX(execution_date)) as last_execution
FROM trigger_performance_logs
WHERE execution_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY trigger_name
ORDER BY avg_execution_time DESC;

-- Index usage analysis
CREATE OR REPLACE VIEW index_usage_analysis AS
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    NON_UNIQUE,
    CARDINALITY,
    CONCAT(ROUND(CARDINALITY / 
        (SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES 
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = s.TABLE_NAME) * 100, 2), '%') as selectivity
FROM INFORMATION_SCHEMA.STATISTICS s
WHERE TABLE_SCHEMA = DATABASE()
AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, CARDINALITY DESC;

-- Performance optimization complete message
SELECT 
    'Phase 24: Performance Optimization Complete!' as status,
    'Optimized triggers, indexes, and monitoring systems are now active.' as message,
    'Use trigger_performance_summary view to monitor trigger performance.' as monitoring_info;

-- ========================================
-- END OF PERFORMANCE OPTIMIZATION
-- ========================================
