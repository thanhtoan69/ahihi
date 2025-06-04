-- ================================================================
-- Phase 22: Analytics Views & Performance Implementation (FINAL WORKING VERSION)
-- Environmental Platform Database Enhancement
-- 
-- Purpose: Create comprehensive analytics views and performance indexes
-- Features: User engagement, content performance, environmental impact, marketplace insights
-- Date: June 2025
-- ================================================================

-- ================================================================
-- ANALYTICS VIEWS CREATION (WORKING VERSION WITH CORRECT COLUMN NAMES)
-- ================================================================

-- ----------------------------------------------------------------
-- 1. USER ENGAGEMENT SUMMARY VIEW (Working version)
-- Purpose: Comprehensive user activity and engagement metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW user_engagement_summary_final AS
SELECT 
    u.user_id,
    u.username,
    u.email,
    u.created_at as registration_date,
    
    -- Basic Activity Metrics
    COALESCE(session_stats.total_sessions, 0) as total_login_sessions,
    COALESCE(session_stats.last_activity, NULL) as last_activity_date,
    DATEDIFF(CURDATE(), u.created_at) as days_since_registration,
    
    -- Content Engagement
    COALESCE(content_stats.articles_interacted, 0) as articles_interacted,
    COALESCE(content_stats.likes_given, 0) as articles_liked,
    COALESCE(content_stats.bookmarks_made, 0) as bookmarks_made,
    COALESCE(content_stats.shares_made, 0) as content_shared,
    COALESCE(content_stats.comments_made, 0) as comments_made,
    
    -- Environmental Activities
    COALESCE(env_stats.carbon_calculations, 0) as carbon_footprint_calculations,
    COALESCE(env_stats.total_carbon_kg, 0) as total_carbon_tracked,
    COALESCE(env_stats.carbon_saved_kg, 0) as total_carbon_saved,
    
    -- Quiz Activities
    COALESCE(quiz_stats.quiz_sessions, 0) as environmental_quiz_sessions,
    COALESCE(quiz_stats.avg_quiz_score, 0) as avg_environmental_quiz_score,
    
    -- E-commerce Activities
    COALESCE(market_stats.orders_placed, 0) as marketplace_orders,
    COALESCE(market_stats.total_spent, 0) as total_marketplace_spending,
    COALESCE(market_stats.green_points_earned, 0) as green_points_from_purchases,
    
    -- Engagement Scoring
    CASE 
        WHEN DATEDIFF(CURDATE(), COALESCE(session_stats.last_activity, u.created_at)) <= 7 THEN 'Highly Active'
        WHEN DATEDIFF(CURDATE(), COALESCE(session_stats.last_activity, u.created_at)) <= 30 THEN 'Active'
        WHEN DATEDIFF(CURDATE(), COALESCE(session_stats.last_activity, u.created_at)) <= 90 THEN 'Moderate'
        ELSE 'Inactive'
    END as engagement_level,
    
    -- Total Engagement Score
    (COALESCE(session_stats.total_sessions, 0) * 2 + 
     COALESCE(content_stats.articles_interacted, 0) * 3 + 
     COALESCE(quiz_stats.quiz_sessions, 0) * 5 + 
     COALESCE(market_stats.orders_placed, 0) * 10) as total_engagement_score

FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_sessions,
        MAX(last_activity) as last_activity
    FROM user_sessions 
    GROUP BY user_id
) session_stats ON u.user_id = session_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT article_id) as articles_interacted,
        SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END) as likes_given,
        SUM(CASE WHEN interaction_type = 'bookmark' THEN 1 ELSE 0 END) as bookmarks_made,
        SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END) as shares_made,
        SUM(CASE WHEN interaction_type = 'comment' THEN 1 ELSE 0 END) as comments_made
    FROM article_interactions 
    GROUP BY user_id
) content_stats ON u.user_id = content_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as carbon_calculations,
        SUM(carbon_kg) as total_carbon_kg,
        SUM(carbon_saved_kg) as carbon_saved_kg
    FROM carbon_footprints
    GROUP BY user_id
) env_stats ON u.user_id = env_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as quiz_sessions,
        AVG(CASE WHEN total_questions > 0 THEN (correct_answers * 100.0 / total_questions) ELSE 0 END) as avg_quiz_score
    FROM quiz_sessions
    WHERE status = 'completed'
    GROUP BY user_id
) quiz_stats ON u.user_id = quiz_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as orders_placed,
        SUM(total_amount) as total_spent,
        SUM(green_points_earned) as green_points_earned
    FROM orders 
    WHERE order_status IN ('delivered', 'confirmed')
    GROUP BY user_id
) market_stats ON u.user_id = market_stats.user_id;

-- ----------------------------------------------------------------
-- 2. CONTENT PERFORMANCE VIEW (Working version)
-- Purpose: Article and content analytics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW content_performance_final AS
SELECT 
    a.article_id,
    a.title,
    a.category_id,
    a.author_id,
    a.created_at as publish_date,
    
    -- Interaction Metrics
    COALESCE(interaction_stats.total_views, 0) as total_views,
    COALESCE(interaction_stats.total_likes, 0) as total_likes,
    COALESCE(interaction_stats.total_dislikes, 0) as total_dislikes,
    COALESCE(interaction_stats.total_bookmarks, 0) as total_bookmarks,
    COALESCE(interaction_stats.total_shares, 0) as total_shares,
    COALESCE(interaction_stats.total_comments, 0) as total_comments,
    COALESCE(interaction_stats.unique_users, 0) as unique_users_engaged,
    
    -- Engagement Metrics
    CASE 
        WHEN COALESCE(interaction_stats.total_views, 0) > 0 
        THEN ROUND((COALESCE(interaction_stats.total_likes, 0) * 100.0 / interaction_stats.total_views), 2)
        ELSE 0 
    END as like_rate_percentage,
    
    CASE 
        WHEN COALESCE(interaction_stats.total_views, 0) > 0 
        THEN ROUND((COALESCE(interaction_stats.total_shares, 0) * 100.0 / interaction_stats.total_views), 2)
        ELSE 0 
    END as share_rate_percentage,
    
    -- Performance Score
    (COALESCE(interaction_stats.total_views, 0) * 1 + 
     COALESCE(interaction_stats.total_likes, 0) * 3 + 
     COALESCE(interaction_stats.total_shares, 0) * 5 + 
     COALESCE(interaction_stats.total_comments, 0) * 4) as performance_score,
     
    -- Content Age
    DATEDIFF(CURDATE(), a.created_at) as days_since_publish,
    
    -- Performance Category
    CASE 
        WHEN COALESCE(interaction_stats.total_views, 0) >= 1000 THEN 'High Performing'
        WHEN COALESCE(interaction_stats.total_views, 0) >= 500 THEN 'Good Performing'
        WHEN COALESCE(interaction_stats.total_views, 0) >= 100 THEN 'Average Performing'
        ELSE 'Low Performing'
    END as performance_category

FROM articles a
LEFT JOIN (
    SELECT 
        article_id,
        COUNT(*) as total_interactions,
        SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END) as total_views,
        SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END) as total_likes,
        SUM(CASE WHEN interaction_type = 'dislike' THEN 1 ELSE 0 END) as total_dislikes,
        SUM(CASE WHEN interaction_type = 'bookmark' THEN 1 ELSE 0 END) as total_bookmarks,
        SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END) as total_shares,
        SUM(CASE WHEN interaction_type = 'comment' THEN 1 ELSE 0 END) as total_comments,
        COUNT(DISTINCT user_id) as unique_users
    FROM article_interactions 
    GROUP BY article_id
) interaction_stats ON a.article_id = interaction_stats.article_id;

-- ----------------------------------------------------------------
-- 3. ENVIRONMENTAL IMPACT SUMMARY VIEW (Working version)
-- Purpose: Environmental metrics and impact tracking
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW environmental_impact_summary_final AS
SELECT 
    u.user_id,
    u.username,
    
    -- Carbon Footprint Metrics
    COALESCE(carbon_stats.total_entries, 0) as carbon_tracking_entries,
    COALESCE(carbon_stats.total_carbon_kg, 0) as total_carbon_tracked_kg,
    COALESCE(carbon_stats.total_saved_kg, 0) as total_carbon_saved_kg,
    COALESCE(carbon_stats.avg_monthly_carbon, 0) as avg_monthly_carbon_kg,
    COALESCE(carbon_stats.transport_carbon, 0) as transport_carbon_kg,
    COALESCE(carbon_stats.energy_carbon, 0) as energy_carbon_kg,
    COALESCE(carbon_stats.waste_carbon, 0) as waste_carbon_kg,
    COALESCE(carbon_stats.food_carbon, 0) as food_carbon_kg,
    COALESCE(carbon_stats.consumption_carbon, 0) as consumption_carbon_kg,
    
    -- Environmental Education
    COALESCE(quiz_stats.total_quizzes, 0) as environmental_quizzes_completed,
    COALESCE(quiz_stats.avg_score, 0) as avg_environmental_quiz_score,
    COALESCE(quiz_stats.total_points, 0) as environmental_education_points,
    
    -- Impact Scoring
    CASE 
        WHEN COALESCE(carbon_stats.total_saved_kg, 0) >= 100 THEN 'High Impact'
        WHEN COALESCE(carbon_stats.total_saved_kg, 0) >= 50 THEN 'Medium Impact'
        WHEN COALESCE(carbon_stats.total_saved_kg, 0) >= 10 THEN 'Low Impact'
        ELSE 'Beginning'
    END as environmental_impact_level,
    
    -- Green Score
    (COALESCE(carbon_stats.total_saved_kg, 0) * 10 + 
     COALESCE(quiz_stats.total_points, 0) * 0.1) as green_score

FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_entries,
        SUM(carbon_kg) as total_carbon_kg,
        SUM(carbon_saved_kg) as total_saved_kg,
        AVG(carbon_kg) as avg_monthly_carbon,
        SUM(CASE WHEN activity_category = 'transport' THEN carbon_kg ELSE 0 END) as transport_carbon,
        SUM(CASE WHEN activity_category = 'energy' THEN carbon_kg ELSE 0 END) as energy_carbon,
        SUM(CASE WHEN activity_category = 'waste' THEN carbon_kg ELSE 0 END) as waste_carbon,
        SUM(CASE WHEN activity_category = 'food' THEN carbon_kg ELSE 0 END) as food_carbon,
        SUM(CASE WHEN activity_category = 'consumption' THEN carbon_kg ELSE 0 END) as consumption_carbon
    FROM carbon_footprints 
    GROUP BY user_id
) carbon_stats ON u.user_id = carbon_stats.user_id

LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_quizzes,
        AVG(CASE WHEN total_questions > 0 THEN (correct_answers * 100.0 / total_questions) ELSE 0 END) as avg_score,
        SUM(total_points) as total_points
    FROM quiz_sessions 
    WHERE status = 'completed'
    GROUP BY user_id
) quiz_stats ON u.user_id = quiz_stats.user_id;

-- ----------------------------------------------------------------
-- 4. MARKETPLACE INSIGHTS VIEW (Working version)
-- Purpose: E-commerce and marketplace analytics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW marketplace_insights_final AS
SELECT 
    p.product_id,
    p.product_name,
    p.price,
    p.eco_score,
    p.category_id,
    p.seller_id,
    p.stock_quantity,
    p.status as product_status,
    p.created_at as product_launch_date,
    
    -- Sales Metrics
    COALESCE(sales_stats.total_orders, 0) as total_orders,
    COALESCE(sales_stats.total_revenue, 0) as total_revenue,
    COALESCE(sales_stats.avg_order_value, 0) as avg_order_value,
    COALESCE(sales_stats.unique_customers, 0) as unique_customers,
    
    -- Review Metrics
    COALESCE(review_stats.total_reviews, 0) as total_reviews,
    COALESCE(review_stats.avg_rating, 0) as avg_rating,
    
    -- Performance Metrics
    CASE 
        WHEN COALESCE(sales_stats.total_orders, 0) >= 50 THEN 'Best Seller'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 20 THEN 'Popular'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 5 THEN 'Moderate'
        ELSE 'Low Sales'
    END as sales_performance,
    
    -- Days since launch
    DATEDIFF(CURDATE(), p.created_at) as days_since_launch,
    
    -- Green Product Score
    (p.eco_score * 0.3 + 
     COALESCE(review_stats.avg_rating, 0) * 20 + 
     LEAST(COALESCE(sales_stats.total_orders, 0), 100)) as green_product_score

FROM products p
LEFT JOIN (
    SELECT 
        oi.product_id,
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(oi.quantity * oi.price) as total_revenue,
        AVG(oi.quantity * oi.price) as avg_order_value,
        COUNT(DISTINCT o.user_id) as unique_customers
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_status IN ('delivered', 'confirmed')
    GROUP BY oi.product_id
) sales_stats ON p.product_id = sales_stats.product_id

LEFT JOIN (
    SELECT 
        product_id,
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating
    FROM product_reviews 
    GROUP BY product_id
) review_stats ON p.product_id = review_stats.product_id;

-- ================================================================
-- PERFORMANCE OPTIMIZATION INDEXES (WORKING VERSION)
-- ================================================================

-- User Session Indexes
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_activity ON user_sessions(user_id, last_activity);
CREATE INDEX IF NOT EXISTS idx_user_sessions_device ON user_sessions(device_type, created_at);

-- Article Interaction Indexes
CREATE INDEX IF NOT EXISTS idx_article_interactions_user_type ON article_interactions(user_id, interaction_type);
CREATE INDEX IF NOT EXISTS idx_article_interactions_article_created ON article_interactions(article_id, created_at);
CREATE INDEX IF NOT EXISTS idx_article_interactions_type_date ON article_interactions(interaction_type, created_at);

-- Carbon Footprint Indexes
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_user_date ON carbon_footprints(user_id, activity_date);
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_category ON carbon_footprints(activity_category, created_at);

-- Quiz Session Indexes
CREATE INDEX IF NOT EXISTS idx_quiz_sessions_user_status ON quiz_sessions(user_id, status);
CREATE INDEX IF NOT EXISTS idx_quiz_sessions_type_date ON quiz_sessions(session_type, started_at);

-- Order Indexes
CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, order_status);
CREATE INDEX IF NOT EXISTS idx_orders_seller_date ON orders(seller_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status_date ON orders(order_status, created_at);

-- Product Indexes
CREATE INDEX IF NOT EXISTS idx_products_category_status ON products(category_id, status);
CREATE INDEX IF NOT EXISTS idx_products_eco_score ON products(eco_score, status);
CREATE INDEX IF NOT EXISTS idx_products_seller_status ON products(seller_id, status);

-- Order Items Indexes
CREATE INDEX IF NOT EXISTS idx_order_items_product ON order_items(product_id);

-- Product Reviews Indexes
CREATE INDEX IF NOT EXISTS idx_product_reviews_product_rating ON product_reviews(product_id, rating);

-- ================================================================
-- REAL-TIME DASHBOARD METRICS VIEW (WORKING VERSION)
-- ================================================================
CREATE OR REPLACE VIEW dashboard_real_time_metrics_final AS
SELECT 
    'Today Active Users' as metric_name,
    COUNT(DISTINCT user_id) as metric_value,
    'users' as metric_unit,
    CURDATE() as metric_date
FROM user_sessions 
WHERE DATE(last_activity) = CURDATE()

UNION ALL

SELECT 
    'Today Article Views' as metric_name,
    COUNT(*) as metric_value,
    'views' as metric_unit,
    CURDATE() as metric_date
FROM article_interactions 
WHERE interaction_type = 'view' AND DATE(created_at) = CURDATE()

UNION ALL

SELECT 
    'Today Carbon Tracked' as metric_name,
    ROUND(SUM(carbon_kg), 2) as metric_value,
    'kg CO2' as metric_unit,
    CURDATE() as metric_date
FROM carbon_footprints 
WHERE DATE(created_at) = CURDATE()

UNION ALL

SELECT 
    'Today Orders' as metric_name,
    COUNT(*) as metric_value,
    'orders' as metric_unit,
    CURDATE() as metric_date
FROM orders 
WHERE DATE(created_at) = CURDATE()

UNION ALL

SELECT 
    'Total Users' as metric_name,
    COUNT(*) as metric_value,
    'users' as metric_unit,
    CURDATE() as metric_date
FROM users;

-- ================================================================
-- DATA VALIDATION VIEW (WORKING VERSION)
-- ================================================================
CREATE OR REPLACE VIEW analytics_data_validation_final AS
SELECT 
    'Users with Sessions' as validation_check,
    COUNT(*) as total_count,
    'Valid user sessions' as description
FROM users u 
WHERE EXISTS (SELECT 1 FROM user_sessions us WHERE us.user_id = u.user_id)

UNION ALL

SELECT 
    'Articles with Interactions' as validation_check,
    COUNT(*) as total_count,
    'Articles that have user interactions' as description
FROM articles a 
WHERE EXISTS (SELECT 1 FROM article_interactions ai WHERE ai.article_id = a.article_id)

UNION ALL

SELECT 
    'Users with Carbon Data' as validation_check,
    COUNT(*) as total_count,
    'Users tracking carbon footprint' as description
FROM users u 
WHERE EXISTS (SELECT 1 FROM carbon_footprints cf WHERE cf.user_id = u.user_id)

UNION ALL

SELECT 
    'Products with Orders' as validation_check,
    COUNT(*) as total_count,
    'Products that have been ordered' as description
FROM products p 
WHERE EXISTS (SELECT 1 FROM order_items oi WHERE oi.product_id = p.product_id);

-- ================================================================
-- VERIFICATION QUERIES
-- ================================================================
SELECT 'Phase 22 Analytics Views Created Successfully' as status;
SELECT COUNT(*) as views_created FROM information_schema.views WHERE table_schema = 'environmental_platform' AND table_name LIKE '%_final';
SELECT 'Performance indexes optimized for analytics queries' as optimization_status;
