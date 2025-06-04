-- ========================================
-- Phase 25F: Performance Indexes
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- 1. USER ACTIVITIES PERFORMANCE INDEXES
-- ========================================

-- Daily user activities index for performance monitoring
CREATE INDEX IF NOT EXISTS idx_user_activities_daily 
ON user_activities_comprehensive (
    DATE(created_at), 
    user_id, 
    activity_type
);

-- User activities by type and points for leaderboards
CREATE INDEX IF NOT EXISTS idx_user_activities_points 
ON user_activities_comprehensive (
    activity_type, 
    total_points DESC, 
    created_at DESC
);

-- User activities for individual user history
CREATE INDEX IF NOT EXISTS idx_user_activities_user_history 
ON user_activities_comprehensive (
    user_id, 
    created_at DESC, 
    activity_category
);

-- Activity category analytics index
CREATE INDEX IF NOT EXISTS idx_user_activities_category_analytics 
ON user_activities_comprehensive (
    activity_category, 
    DATE(created_at), 
    total_points
);

-- Weekly activity summaries
CREATE INDEX IF NOT EXISTS idx_user_activities_weekly 
ON user_activities_comprehensive (
    YEARWEEK(created_at), 
    user_id, 
    activity_type
);

-- ========================================
-- 2. CARBON FOOTPRINTS PERFORMANCE INDEXES
-- ========================================

-- Monthly carbon footprint tracking
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_monthly 
ON carbon_footprints (
    YEAR(recorded_date), 
    MONTH(recorded_date), 
    user_id
);

-- Carbon footprint by category for analytics
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_category 
ON carbon_footprints (
    category, 
    recorded_date, 
    carbon_amount
);

-- User carbon history index
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_user_history 
ON carbon_footprints (
    user_id, 
    recorded_date DESC, 
    category
);

-- Carbon reduction trends
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_trends 
ON carbon_footprints (
    recorded_date, 
    carbon_amount, 
    category
);

-- Daily carbon totals
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_daily 
ON carbon_footprints (
    DATE(recorded_date), 
    user_id, 
    carbon_amount
);

-- ========================================
-- 3. PRODUCTS ECO-FRIENDLY INDEXES
-- ========================================

-- Eco-friendly products discovery
CREATE INDEX IF NOT EXISTS idx_products_eco_friendly 
ON products (
    is_eco_friendly, 
    eco_rating DESC, 
    created_at DESC
);

-- Product sustainability metrics
CREATE INDEX IF NOT EXISTS idx_products_sustainability 
ON products (
    sustainability_score DESC, 
    category_id, 
    is_active
);

-- Green products by category
CREATE INDEX IF NOT EXISTS idx_products_green_category 
ON products (
    category_id, 
    is_eco_friendly, 
    eco_rating DESC,
    price ASC
);

-- Environmental impact index
CREATE INDEX IF NOT EXISTS idx_products_environmental_impact 
ON products (
    environmental_impact_score ASC, 
    is_eco_friendly, 
    created_at DESC
);

-- Carbon footprint of products
CREATE INDEX IF NOT EXISTS idx_products_carbon_footprint 
ON products (
    carbon_footprint_per_unit ASC, 
    category_id, 
    is_active
);

-- ========================================
-- 4. ORDERS MONTHLY ANALYTICS INDEXES
-- ========================================

-- Monthly orders analytics
CREATE INDEX IF NOT EXISTS idx_orders_monthly_analytics 
ON orders (
    YEAR(order_date), 
    MONTH(order_date), 
    order_status, 
    total_amount
);

-- Order performance by user
CREATE INDEX IF NOT EXISTS idx_orders_user_performance 
ON orders (
    user_id, 
    order_date DESC, 
    order_status
);

-- Order value analytics
CREATE INDEX IF NOT EXISTS idx_orders_value_analytics 
ON orders (
    order_date, 
    total_amount DESC, 
    order_status
);

-- Green orders tracking
CREATE INDEX IF NOT EXISTS idx_orders_green_tracking 
ON orders (
    is_eco_order, 
    order_date, 
    green_points_earned DESC
);

-- Daily order summaries
CREATE INDEX IF NOT EXISTS idx_orders_daily_summary 
ON orders (
    DATE(order_date), 
    order_status, 
    total_amount
);

-- ========================================
-- 5. USER PERFORMANCE INDEXES
-- ========================================

-- User ranking by green points
CREATE INDEX IF NOT EXISTS idx_users_green_ranking 
ON users (
    green_points DESC, 
    user_level DESC, 
    is_active
);

-- User experience and level tracking
CREATE INDEX IF NOT EXISTS idx_users_experience_level 
ON users (
    user_level DESC, 
    experience_points DESC, 
    created_at
);

-- Active users by type
CREATE INDEX IF NOT EXISTS idx_users_active_type 
ON users (
    user_type, 
    is_active, 
    last_login DESC
);

-- User engagement metrics
CREATE INDEX IF NOT EXISTS idx_users_engagement 
ON users (
    login_streak DESC, 
    longest_streak DESC, 
    is_active
);

-- Verified users index
CREATE INDEX IF NOT EXISTS idx_users_verified 
ON users (
    is_verified, 
    user_type, 
    created_at DESC
);

-- ========================================
-- 6. WASTE MANAGEMENT INDEXES
-- ========================================

-- Waste entries by date for daily tracking
CREATE INDEX IF NOT EXISTS idx_waste_entries_daily 
ON waste_entries (
    DATE(entry_date), 
    user_id, 
    waste_type
);

-- Waste reduction analytics
CREATE INDEX IF NOT EXISTS idx_waste_entries_reduction 
ON waste_entries (
    waste_type, 
    entry_date, 
    quantity_kg
);

-- User waste history
CREATE INDEX IF NOT EXISTS idx_waste_entries_user_history 
ON waste_entries (
    user_id, 
    entry_date DESC, 
    waste_type
);

-- Monthly waste summaries
CREATE INDEX IF NOT EXISTS idx_waste_entries_monthly 
ON waste_entries (
    YEAR(entry_date), 
    MONTH(entry_date), 
    waste_type, 
    quantity_kg
);

-- ========================================
-- 7. ANALYTICS AND REPORTING INDEXES
-- ========================================

-- Daily analytics summary
CREATE INDEX IF NOT EXISTS idx_daily_analytics_date 
ON daily_analytics_summary (
    analytics_date DESC, 
    metric_type
);

-- Performance metrics index
CREATE INDEX IF NOT EXISTS idx_performance_metrics 
ON performance_metrics (
    metric_date DESC, 
    metric_category, 
    metric_value
);

-- Environmental impact tracking
CREATE INDEX IF NOT EXISTS idx_environmental_impact_tracking 
ON environmental_impact_tracking (
    impact_date DESC, 
    impact_category, 
    impact_value
);

-- ========================================
-- 8. COMPOSITE INDEXES FOR COMPLEX QUERIES
-- ========================================

-- User activity with carbon impact
CREATE INDEX IF NOT EXISTS idx_user_activity_carbon_composite 
ON user_activities_comprehensive (
    user_id, 
    activity_type, 
    DATE(created_at), 
    total_points
);

-- Product discovery composite
CREATE INDEX IF NOT EXISTS idx_product_discovery_composite 
ON products (
    category_id, 
    is_eco_friendly, 
    price ASC, 
    eco_rating DESC
);

-- Order analytics composite
CREATE INDEX IF NOT EXISTS idx_order_analytics_composite 
ON orders (
    DATE(order_date), 
    order_status, 
    user_id, 
    total_amount
);

-- User engagement composite
CREATE INDEX IF NOT EXISTS idx_user_engagement_composite 
ON users (
    is_active, 
    user_type, 
    green_points DESC, 
    last_login DESC
);

-- ========================================
-- 9. FULL-TEXT SEARCH INDEXES
-- ========================================

-- Product search optimization
ALTER TABLE products 
ADD FULLTEXT(product_name, description) 
IF NOT EXISTS;

-- Article content search
ALTER TABLE articles 
ADD FULLTEXT(title, content) 
IF NOT EXISTS;

-- Event search optimization
ALTER TABLE events 
ADD FULLTEXT(event_name, description) 
IF NOT EXISTS;

-- ========================================
-- 10. SPECIALIZED PERFORMANCE INDEXES
-- ========================================

-- Real-time dashboard queries
CREATE INDEX IF NOT EXISTS idx_realtime_dashboard 
ON user_activities_comprehensive (
    DATE(created_at) = CURDATE(), 
    activity_type, 
    total_points DESC
);

-- Leaderboard performance
CREATE INDEX IF NOT EXISTS idx_leaderboard_performance 
ON users (
    green_points DESC, 
    experience_points DESC
) 
WHERE is_active = 1;

-- Recent activity feed
CREATE INDEX IF NOT EXISTS idx_recent_activity_feed 
ON user_activities_comprehensive (
    created_at DESC, 
    activity_type, 
    user_id
) 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Monthly environmental reports
CREATE INDEX IF NOT EXISTS idx_monthly_environmental_reports 
ON carbon_footprints (
    YEAR(recorded_date), 
    MONTH(recorded_date), 
    category, 
    carbon_amount
);

-- ========================================
-- 11. INDEX MAINTENANCE AND MONITORING
-- ========================================

-- Create index usage tracking table
CREATE TABLE IF NOT EXISTS index_usage_stats (
    table_name VARCHAR(100),
    index_name VARCHAR(100),
    usage_count INT DEFAULT 0,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (table_name, index_name)
);

-- ========================================
-- 12. VERIFICATION QUERIES
-- ========================================

SELECT 'PERFORMANCE INDEXES CREATION COMPLETED' as status;

-- Show created indexes for verification
SELECT 
    'USER ACTIVITIES INDEXES' as section,
    COUNT(*) as index_count
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'user_activities_comprehensive'
AND index_name LIKE 'idx_%';

SELECT 
    'CARBON FOOTPRINTS INDEXES' as section,
    COUNT(*) as index_count
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'carbon_footprints'
AND index_name LIKE 'idx_%';

SELECT 
    'PRODUCTS INDEXES' as section,
    COUNT(*) as index_count
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'products'
AND index_name LIKE 'idx_%';

SELECT 
    'ORDERS INDEXES' as section,
    COUNT(*) as index_count
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'orders'
AND index_name LIKE 'idx_%';

SELECT 
    'USERS INDEXES' as section,
    COUNT(*) as index_count
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'users'
AND index_name LIKE 'idx_%';

-- Performance improvement summary
SELECT 'PHASE 25F PERFORMANCE INDEXES SUMMARY' as section;
SELECT 
    'Total Performance Indexes Created' as metric,
    COUNT(*) as value
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%';

SELECT 'INDEX CREATION COMPLETED SUCCESSFULLY' as final_status;
