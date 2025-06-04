-- ================================================================
-- Phase 24: Data Integrity & Caching System
-- Environmental Platform Database Enhancement
-- 
-- Purpose: Performance optimization, caching strategies, data integrity, partitioning
-- Features: Cache tables, indexes, partitioning, foreign key constraints, performance views
-- Dependencies: Phase 24 triggers and automation system
-- Date: 2024
-- ================================================================

USE environmental_platform;

-- Set proper SQL modes
SET SESSION sql_mode = '';
SET foreign_key_checks = 0;

SELECT 'PHASE 24: DATA INTEGRITY & CACHING SYSTEM IMPLEMENTATION' AS implementation_title;
SELECT 'Creating performance optimization and caching infrastructure...' AS implementation_status;

-- ================================================================
-- CACHE INFRASTRUCTURE TABLES
-- ================================================================

-- ----------------------------------------------------------------
-- 1. CACHE INVALIDATION LOG TABLE
-- ----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS cache_invalidation_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('user', 'article', 'product', 'order', 'category', 'global') NOT NULL,
    entity_id INT NULL,
    invalidation_reason VARCHAR(255) NOT NULL,
    cache_key VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cache_entity_type (entity_type, created_at),
    INDEX idx_cache_entity_id (entity_id, created_at),
    INDEX idx_cache_created (created_at)
) ENGINE=InnoDB COMMENT='Cache invalidation tracking for performance optimization';

-- ----------------------------------------------------------------
-- 2. MONTHLY ANALYTICS SUMMARY TABLE
-- ----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS monthly_analytics_summary (
    summary_id INT PRIMARY KEY AUTO_INCREMENT,
    month_year VARCHAR(7) NOT NULL,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_activities INT DEFAULT 0,
    total_points_awarded BIGINT DEFAULT 0,
    total_articles INT DEFAULT 0,
    articles_published INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    orders_completed INT DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    total_carbon_saved DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month_year (month_year),
    INDEX idx_monthly_summary_month (month_year),
    INDEX idx_monthly_summary_created (created_at)
) ENGINE=InnoDB COMMENT='Monthly aggregated analytics for performance optimization';

-- ================================================================
-- PERFORMANCE OPTIMIZATION INDEXES
-- ================================================================

-- ----------------------------------------------------------------
-- 3. USER PERFORMANCE INDEXES
-- ----------------------------------------------------------------

-- User activity and engagement indexes
CREATE INDEX IF NOT EXISTS idx_users_activity_level ON users(last_activity, user_level);
CREATE INDEX IF NOT EXISTS idx_users_points_level ON users(green_points, user_level);
CREATE INDEX IF NOT EXISTS idx_users_experience_level ON users(experience_points, user_level);
CREATE INDEX IF NOT EXISTS idx_users_login_streak ON users(login_streak, last_login);
CREATE INDEX IF NOT EXISTS idx_users_carbon_saved ON users(total_carbon_saved, user_level);
CREATE INDEX IF NOT EXISTS idx_users_type_status ON users(user_type, is_active, created_at);

-- User comprehensive activities indexes
CREATE INDEX IF NOT EXISTS idx_activities_user_type_date ON user_activities_comprehensive(user_id, activity_type, created_at);
CREATE INDEX IF NOT EXISTS idx_activities_category_date ON user_activities_comprehensive(activity_category, created_at);
CREATE INDEX IF NOT EXISTS idx_activities_points_date ON user_activities_comprehensive(total_points, created_at);
CREATE INDEX IF NOT EXISTS idx_activities_entity_type ON user_activities_comprehensive(related_entity_type, related_entity_id);
CREATE INDEX IF NOT EXISTS idx_activities_environmental_impact ON user_activities_comprehensive(environmental_impact, created_at);

-- ----------------------------------------------------------------
-- 4. CONTENT PERFORMANCE INDEXES
-- ----------------------------------------------------------------

-- Article performance indexes
CREATE INDEX IF NOT EXISTS idx_articles_author_status_date ON articles(author_id, status, created_at);
CREATE INDEX IF NOT EXISTS idx_articles_category_featured ON articles(category_id, is_featured, status);
CREATE INDEX IF NOT EXISTS idx_articles_impact_views ON articles(environmental_impact_score, view_count);
CREATE INDEX IF NOT EXISTS idx_articles_engagement_score ON articles(like_count, share_count, view_count);
CREATE INDEX IF NOT EXISTS idx_articles_type_difficulty ON articles(article_type, difficulty_level, status);
CREATE INDEX IF NOT EXISTS idx_articles_updated_status ON articles(updated_at, status);

-- Article interaction performance indexes  
CREATE INDEX IF NOT EXISTS idx_interactions_article_user_type ON article_interactions(article_id, user_id, interaction_type);
CREATE INDEX IF NOT EXISTS idx_interactions_type_date ON article_interactions(interaction_type, created_at);
CREATE INDEX IF NOT EXISTS idx_interactions_user_date ON article_interactions(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_interactions_session_duration ON article_interactions(session_duration_seconds, scroll_depth_percentage);

-- Category optimization indexes
CREATE INDEX IF NOT EXISTS idx_categories_post_count ON categories(post_count, is_active);
CREATE INDEX IF NOT EXISTS idx_categories_name_active ON categories(category_name, is_active);

-- ----------------------------------------------------------------
-- 5. E-COMMERCE PERFORMANCE INDEXES
-- ----------------------------------------------------------------

-- Order performance indexes
CREATE INDEX IF NOT EXISTS idx_orders_user_status_date ON orders(user_id, order_status, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_seller_status_amount ON orders(seller_id, order_status, total_amount);
CREATE INDEX IF NOT EXISTS idx_orders_status_amount_date ON orders(order_status, total_amount, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status, order_status);

-- Product performance indexes
CREATE INDEX IF NOT EXISTS idx_products_category_eco_score ON products(category_id, eco_score, status);
CREATE INDEX IF NOT EXISTS idx_products_seller_status_price ON products(seller_id, status, price);
CREATE INDEX IF NOT EXISTS idx_products_eco_score_rating ON products(eco_score, average_rating);
CREATE INDEX IF NOT EXISTS idx_products_stock_status ON products(stock_quantity, status);
CREATE INDEX IF NOT EXISTS idx_products_price_range ON products(price, status, category_id);

-- Order items optimization
CREATE INDEX IF NOT EXISTS idx_order_items_product_quantity ON order_items(product_id, quantity, price);
CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items(order_id, product_id);

-- ----------------------------------------------------------------
-- 6. ENVIRONMENTAL DATA INDEXES
-- ----------------------------------------------------------------

-- Carbon footprint indexes
CREATE INDEX IF NOT EXISTS idx_carbon_user_category_date ON carbon_footprints(user_id, activity_category, activity_date);
CREATE INDEX IF NOT EXISTS idx_carbon_category_kg_date ON carbon_footprints(activity_category, carbon_kg, created_at);
CREATE INDEX IF NOT EXISTS idx_carbon_date_kg ON carbon_footprints(activity_date, carbon_kg);

-- Quiz session indexes
CREATE INDEX IF NOT EXISTS idx_quiz_user_type_score ON quiz_sessions(user_id, session_type, score);
CREATE INDEX IF NOT EXISTS idx_quiz_type_status_date ON quiz_sessions(session_type, status, started_at);
CREATE INDEX IF NOT EXISTS idx_quiz_score_completion ON quiz_sessions(score, status, completed_at);

-- ================================================================
-- GEOGRAPHIC AND LOCATION INDEXES
-- ================================================================

-- ----------------------------------------------------------------
-- 7. GEOGRAPHIC OPTIMIZATION INDEXES
-- ----------------------------------------------------------------

-- Recycling location geographic indexes
CREATE INDEX IF NOT EXISTS idx_recycling_locations_coordinates ON recycling_locations(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_recycling_locations_city_type ON recycling_locations(city, location_type);
CREATE INDEX IF NOT EXISTS idx_recycling_locations_region_active ON recycling_locations(region, is_active);

-- User location indexes (if location fields exist)
-- Note: These will be created conditionally based on table structure
SET @sql_user_location = '
CREATE INDEX IF NOT EXISTS idx_users_location_city ON users(city, country);';

-- Execute conditionally if columns exist
SET @table_exists = (SELECT COUNT(*) FROM information_schema.columns 
                    WHERE table_schema = 'environmental_platform' 
                    AND table_name = 'users' 
                    AND column_name = 'city');

IF @table_exists > 0 THEN
    PREPARE stmt FROM @sql_user_location;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END IF;

-- ================================================================
-- CACHING VIEWS FOR PERFORMANCE
-- ================================================================

-- ----------------------------------------------------------------
-- 8. USER DASHBOARD CACHE VIEW
-- ----------------------------------------------------------------

CREATE OR REPLACE VIEW user_dashboard_cache AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.green_points,
    u.experience_points,
    u.user_level,
    u.login_streak,
    u.longest_streak,
    u.total_carbon_saved,
    u.last_activity,
    
    -- Activity metrics (cached for performance)
    COALESCE(activity_stats.total_activities, 0) as total_activities,
    COALESCE(activity_stats.activities_this_month, 0) as activities_this_month,
    COALESCE(activity_stats.last_activity_points, 0) as last_activity_points,
    
    -- Content metrics
    COALESCE(content_stats.articles_published, 0) as articles_published,
    COALESCE(content_stats.total_article_views, 0) as total_article_views,
    COALESCE(content_stats.total_article_likes, 0) as total_article_likes,
    
    -- Environmental impact
    COALESCE(env_stats.carbon_entries_count, 0) as carbon_entries_count,
    COALESCE(env_stats.avg_environmental_impact, 0) as avg_environmental_impact,
    
    -- E-commerce metrics
    COALESCE(ecommerce_stats.orders_count, 0) as orders_count,
    COALESCE(ecommerce_stats.total_spent, 0) as total_spent,
    
    -- Engagement score calculation
    (u.green_points * 0.4 + 
     u.experience_points * 0.3 + 
     COALESCE(activity_stats.total_activities, 0) * 5 + 
     COALESCE(content_stats.total_article_views, 0) * 2 +
     COALESCE(env_stats.carbon_entries_count, 0) * 10) as engagement_score,
    
    NOW() as cache_updated_at
    
FROM users u

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_activities,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as activities_this_month,
        COALESCE(MAX(total_points), 0) as last_activity_points
    FROM user_activities_comprehensive 
    GROUP BY user_id
) activity_stats ON u.user_id = activity_stats.user_id

LEFT JOIN (
    SELECT 
        author_id as user_id,
        COUNT(*) as articles_published,
        SUM(view_count) as total_article_views,
        SUM(like_count) as total_article_likes
    FROM articles 
    WHERE status = 'published'
    GROUP BY author_id
) content_stats ON u.user_id = content_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as carbon_entries_count,
        AVG(ABS(carbon_kg)) * 10 as avg_environmental_impact
    FROM carbon_footprints 
    GROUP BY user_id
) env_stats ON u.user_id = env_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as orders_count,
        SUM(total_amount) as total_spent
    FROM orders 
    WHERE order_status IN ('completed', 'delivered')
    GROUP BY user_id
) ecommerce_stats ON u.user_id = ecommerce_stats.user_id

WHERE u.is_active = TRUE;

-- ----------------------------------------------------------------
-- 9. ARTICLE PERFORMANCE CACHE VIEW
-- ----------------------------------------------------------------

CREATE OR REPLACE VIEW article_performance_cache AS
SELECT 
    a.article_id,
    a.title,
    a.author_id,
    a.category_id,
    a.article_type,
    a.difficulty_level,
    a.status,
    a.is_featured,
    a.view_count,
    a.like_count,
    a.share_count,
    a.environmental_impact_score,
    a.created_at,
    a.updated_at,
    
    -- Author information
    u.username as author_username,
    u.first_name as author_first_name,
    u.last_name as author_last_name,
    u.user_level as author_level,
    
    -- Category information
    c.category_name,
    c.description as category_description,
    
    -- Performance metrics
    COALESCE(interaction_stats.unique_viewers, 0) as unique_viewers,
    COALESCE(interaction_stats.engagement_rate, 0) as engagement_rate,
    COALESCE(comment_stats.comment_count, 0) as comment_count,
    COALESCE(comment_stats.recent_comments, 0) as recent_comments,
    
    -- Calculated engagement score
    (a.view_count * 1 + 
     a.like_count * 5 + 
     a.share_count * 10 + 
     COALESCE(comment_stats.comment_count, 0) * 8 +
     a.environmental_impact_score * 2) as total_engagement_score,
    
    -- Time-based metrics
    DATEDIFF(NOW(), a.created_at) as days_since_published,
    CASE 
        WHEN a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'new'
        WHEN a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'recent'
        WHEN a.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 'medium'
        ELSE 'old'
    END as content_age_category,
    
    NOW() as cache_updated_at
    
FROM articles a

LEFT JOIN users u ON a.author_id = u.user_id
LEFT JOIN categories c ON a.category_id = c.category_id

LEFT JOIN (
    SELECT 
        article_id,
        COUNT(DISTINCT user_id) as unique_viewers,
        CASE 
            WHEN COUNT(CASE WHEN interaction_type = 'view' THEN 1 END) > 0 
            THEN ROUND((COUNT(CASE WHEN interaction_type IN ('like', 'share', 'bookmark') THEN 1 END) * 100.0 / 
                       COUNT(CASE WHEN interaction_type = 'view' THEN 1 END)), 2)
            ELSE 0 
        END as engagement_rate
    FROM article_interactions 
    GROUP BY article_id
) interaction_stats ON a.article_id = interaction_stats.article_id

LEFT JOIN (
    SELECT 
        article_id,
        COUNT(*) as comment_count,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_comments
    FROM article_comments 
    WHERE status = 'approved'
    GROUP BY article_id
) comment_stats ON a.article_id = comment_stats.article_id

WHERE a.status = 'published';

-- ================================================================
-- DATA INTEGRITY CONSTRAINTS
-- ================================================================

-- ----------------------------------------------------------------
-- 10. ENHANCED FOREIGN KEY CONSTRAINTS
-- ----------------------------------------------------------------

-- Add foreign key constraints with proper cascading
-- Note: These will be added only if they don't already exist

-- User activities foreign keys
SET FOREIGN_KEY_CHECKS = 0;

-- Add user activities constraints if table exists and constraints don't exist
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints 
                         WHERE constraint_schema = 'environmental_platform' 
                         AND table_name = 'user_activities_comprehensive' 
                         AND constraint_name = 'fk_activities_user_id');

SET @sql_fk_activities = '
ALTER TABLE user_activities_comprehensive 
ADD CONSTRAINT fk_activities_user_id 
FOREIGN KEY (user_id) REFERENCES users(user_id) 
ON DELETE CASCADE ON UPDATE CASCADE;';

IF @constraint_exists = 0 THEN
    PREPARE stmt FROM @sql_fk_activities;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END IF;

-- Add cache invalidation constraints
SET @constraint_exists_cache = (SELECT COUNT(*) FROM information_schema.table_constraints 
                               WHERE constraint_schema = 'environmental_platform' 
                               AND table_name = 'cache_invalidation_log' 
                               AND constraint_name = 'chk_cache_entity_type');

SET @sql_chk_cache = '
ALTER TABLE cache_invalidation_log 
ADD CONSTRAINT chk_cache_entity_type 
CHECK (entity_type IN (\'user\', \'article\', \'product\', \'order\', \'category\', \'global\'));';

IF @constraint_exists_cache = 0 THEN
    PREPARE stmt FROM @sql_chk_cache;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END IF;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- TABLE PARTITIONING FOR PERFORMANCE
-- ================================================================

-- ----------------------------------------------------------------
-- 11. PARTITION USER ACTIVITIES BY DATE
-- ----------------------------------------------------------------

-- Note: MySQL partitioning requires specific setup and may not work on all configurations
-- This is an advanced optimization for high-volume environments

-- Create partitioned version of user activities for better performance
-- This would typically be done during initial table creation

-- For existing installations, we'll create an optimized archive strategy instead
CREATE TABLE IF NOT EXISTS user_activities_archive (
    activity_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_category VARCHAR(50) NOT NULL,
    base_points INT DEFAULT 0,
    bonus_points INT DEFAULT 0,
    total_points INT DEFAULT 0,
    related_entity_type VARCHAR(50) NULL,
    related_entity_id INT NULL,
    activity_description TEXT NULL,
    environmental_impact INT DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_archive_user_date (user_id, created_at),
    INDEX idx_archive_type_date (activity_type, created_at),
    INDEX idx_archive_archived (archived_at)
) ENGINE=InnoDB COMMENT='Archived user activities for historical data';

-- ================================================================
-- PERFORMANCE MONITORING VIEWS
-- ================================================================

-- ----------------------------------------------------------------
-- 12. SYSTEM PERFORMANCE MONITORING VIEW
-- ----------------------------------------------------------------

CREATE OR REPLACE VIEW system_performance_monitor AS
SELECT 
    'Database Performance Metrics' as metric_category,
    
    -- Table size metrics
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM articles WHERE status = 'published') as published_articles,
    (SELECT COUNT(*) FROM user_activities_comprehensive) as total_activities,
    (SELECT COUNT(*) FROM article_interactions) as total_interactions,
    (SELECT COUNT(*) FROM orders) as total_orders,
    
    -- Recent activity metrics
    (SELECT COUNT(*) FROM user_activities_comprehensive 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as activities_last_24h,
    (SELECT COUNT(*) FROM article_interactions 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as interactions_last_24h,
    (SELECT COUNT(*) FROM users 
     WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as active_users_24h,
    
    -- Performance indicators
    (SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) 
     FROM articles WHERE updated_at > created_at) as avg_article_processing_time,
    
    -- Cache metrics
    (SELECT COUNT(*) FROM cache_invalidation_log 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)) as cache_invalidations_last_hour,
    
    -- Data quality metrics
    (SELECT COUNT(*) FROM users WHERE green_points < 0) as users_negative_points,
    (SELECT COUNT(*) FROM articles WHERE view_count < 0) as articles_invalid_views,
    
    NOW() as metrics_generated_at;

-- ----------------------------------------------------------------
-- 13. CACHE PERFORMANCE VIEW
-- ----------------------------------------------------------------

CREATE OR REPLACE VIEW cache_performance_metrics AS
SELECT 
    entity_type,
    COUNT(*) as total_invalidations,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as invalidations_last_hour,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as invalidations_last_24h,
    AVG(TIMESTAMPDIFF(SECOND, LAG(created_at) OVER (PARTITION BY entity_type ORDER BY created_at), created_at)) as avg_invalidation_interval,
    MAX(created_at) as last_invalidation,
    
    -- Cache efficiency estimation
    CASE 
        WHEN COUNT(*) < 100 THEN 'efficient'
        WHEN COUNT(*) < 500 THEN 'moderate' 
        ELSE 'high_turnover'
    END as cache_efficiency_status
    
FROM cache_invalidation_log 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY entity_type
ORDER BY total_invalidations DESC;

-- ================================================================
-- AUTOMATED MAINTENANCE PROCEDURES
-- ================================================================

-- ----------------------------------------------------------------
-- 14. CACHE MAINTENANCE PROCEDURE
-- ----------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE OptimizeCachePerformance()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Archive old activities (older than 6 months)
    INSERT INTO user_activities_archive 
    SELECT *, NOW() as archived_at 
    FROM user_activities_comprehensive 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Delete archived activities from main table
    DELETE FROM user_activities_comprehensive 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Clean up old cache invalidation logs
    DELETE FROM cache_invalidation_log 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Update table statistics
    ANALYZE TABLE users, articles, user_activities_comprehensive, article_interactions;
    
    COMMIT;
    
    SELECT 'Cache optimization completed successfully' as status;
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 15. DATA INTEGRITY CHECK PROCEDURE
-- ----------------------------------------------------------------

DELIMITER $$
CREATE PROCEDURE CheckDataIntegrity()
BEGIN
    DECLARE v_error_count INT DEFAULT 0;
    
    -- Check for orphaned records
    SELECT COUNT(*) INTO v_error_count FROM articles a 
    LEFT JOIN users u ON a.author_id = u.user_id 
    WHERE u.user_id IS NULL;
    
    IF v_error_count > 0 THEN
        INSERT INTO cache_invalidation_log (entity_type, invalidation_reason, created_at)
        VALUES ('global', CONCAT('Found ', v_error_count, ' orphaned articles'), NOW());
    END IF;
    
    -- Check for negative counters
    SELECT COUNT(*) INTO v_error_count FROM articles 
    WHERE view_count < 0 OR like_count < 0 OR share_count < 0;
    
    IF v_error_count > 0 THEN
        UPDATE articles 
        SET view_count = GREATEST(view_count, 0),
            like_count = GREATEST(like_count, 0),
            share_count = GREATEST(share_count, 0)
        WHERE view_count < 0 OR like_count < 0 OR share_count < 0;
        
        INSERT INTO cache_invalidation_log (entity_type, invalidation_reason, created_at)
        VALUES ('global', CONCAT('Fixed ', v_error_count, ' negative counters'), NOW());
    END IF;
    
    -- Check user point consistency
    SELECT COUNT(*) INTO v_error_count FROM users 
    WHERE green_points < 0 OR experience_points < 0;
    
    IF v_error_count > 0 THEN
        UPDATE users 
        SET green_points = GREATEST(green_points, 0),
            experience_points = GREATEST(experience_points, 0)
        WHERE green_points < 0 OR experience_points < 0;
        
        INSERT INTO cache_invalidation_log (entity_type, invalidation_reason, created_at)
        VALUES ('global', CONCAT('Fixed ', v_error_count, ' negative user points'), NOW());
    END IF;
    
    SELECT 'Data integrity check completed' as status, v_error_count as issues_found;
END$$
DELIMITER ;

-- ================================================================
-- PERFORMANCE TESTING AND VERIFICATION
-- ================================================================

-- Reset SQL mode and foreign key checks
SET foreign_key_checks = 1;
SET SESSION sql_mode = DEFAULT;

-- Display implementation results
SELECT 'PHASE 24 DATA INTEGRITY & CACHING IMPLEMENTATION COMPLETED' AS implementation_status;

-- Show created indexes
SELECT 
    'PERFORMANCE INDEXES CREATED:' as status,
    COUNT(*) as index_count
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'environmental_platform'
AND INDEX_NAME LIKE 'idx_%'
AND INDEX_NAME NOT IN ('PRIMARY');

-- Show created views
SELECT 
    'PERFORMANCE VIEWS CREATED:' as status,
    COUNT(*) as view_count
FROM INFORMATION_SCHEMA.VIEWS 
WHERE TABLE_SCHEMA = 'environmental_platform'
AND TABLE_NAME IN (
    'user_dashboard_cache',
    'article_performance_cache', 
    'system_performance_monitor',
    'cache_performance_metrics'
);

-- Show created procedures
SELECT 
    'MAINTENANCE PROCEDURES CREATED:' as status,
    COUNT(*) as procedure_count
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform'
AND ROUTINE_NAME IN ('OptimizeCachePerformance', 'CheckDataIntegrity');

-- List all performance indexes
SELECT 
    TABLE_NAME as table_name,
    INDEX_NAME as index_name,
    NON_UNIQUE as is_unique,
    COLUMN_NAME as column_name
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'environmental_platform'
AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;

SELECT 'Phase 24: Data Integrity & Caching - IMPLEMENTATION COMPLETE!' as final_status;
SELECT 'Created comprehensive caching system, performance indexes, and data integrity checks' as summary;