-- ================================================================
-- Phase 22: Analytics Views & Performance Implementation (CORRECTED)
-- Environmental Platform Database Enhancement
-- 
-- Purpose: Create comprehensive analytics views and performance indexes
-- Features: User engagement, content performance, environmental impact, marketplace insights
-- Date: 2024
-- ================================================================

-- ================================================================
-- ANALYTICS VIEWS CREATION (UPDATED FOR EXISTING SCHEMA)
-- ================================================================

-- ----------------------------------------------------------------
-- 1. USER ENGAGEMENT SUMMARY VIEW (Updated for existing tables)
-- Purpose: Comprehensive user activity and engagement metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW user_engagement_summary_corrected AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    u.created_at as registration_date,
    
    -- Basic Activity Metrics
    COALESCE(uas.total_sessions, 0) as total_login_sessions,
    COALESCE(uas.last_activity, NULL) as last_activity_date,
    DATEDIFF(CURDATE(), u.created_at) as days_since_registration,
    
    -- Content Engagement
    COALESCE(content_stats.articles_interacted, 0) as articles_interacted,
    COALESCE(content_stats.comments_made, 0) as comments_made,
    COALESCE(content_stats.content_shared, 0) as content_shared,
    
    -- Environmental Activities
    COALESCE(env_stats.quiz_sessions, 0) as environmental_quiz_sessions,
    COALESCE(env_stats.avg_quiz_score, 0) as avg_environmental_quiz_score,
    COALESCE(env_stats.carbon_calculations, 0) as carbon_footprint_calculations,
    COALESCE(env_stats.waste_classifications, 0) as waste_classification_sessions,
    
    -- E-commerce Activities
    COALESCE(market_stats.orders_placed, 0) as marketplace_orders,
    COALESCE(market_stats.total_spent, 0) as total_marketplace_spending,
    COALESCE(market_stats.reviews_written, 0) as product_reviews_written,
    
    -- Social Engagement
    COALESCE(social_stats.forum_posts, 0) as community_forum_posts,
    COALESCE(social_stats.event_registrations, 0) as environmental_events_registered,
    COALESCE(social_stats.challenge_participations, 0) as challenge_participations,
    
    -- Engagement Scoring
    CASE 
        WHEN (COALESCE(uas.total_sessions, 0) + COALESCE(content_stats.articles_interacted, 0) + 
              COALESCE(env_stats.quiz_sessions, 0) + COALESCE(market_stats.orders_placed, 0)) >= 100 
        THEN 'eco_champion'
        WHEN (COALESCE(uas.total_sessions, 0) + COALESCE(content_stats.articles_interacted, 0) + 
              COALESCE(env_stats.quiz_sessions, 0) + COALESCE(market_stats.orders_placed, 0)) >= 50 
        THEN 'eco_enthusiast'
        WHEN (COALESCE(uas.total_sessions, 0) + COALESCE(content_stats.articles_interacted, 0) + 
              COALESCE(env_stats.quiz_sessions, 0) + COALESCE(market_stats.orders_placed, 0)) >= 20 
        THEN 'eco_explorer'
        WHEN (COALESCE(uas.total_sessions, 0) + COALESCE(content_stats.articles_interacted, 0) + 
              COALESCE(env_stats.quiz_sessions, 0) + COALESCE(market_stats.orders_placed, 0)) >= 5 
        THEN 'eco_beginner'
        ELSE 'inactive'
    END as user_engagement_level,
    
    -- Activity Trends
    COALESCE(recent_stats.sessions_last_30_days, 0) as sessions_last_30_days,
    COALESCE(recent_stats.interactions_last_30_days, 0) as interactions_last_30_days,
    
    -- User Value Score (weighted engagement metric)
    (COALESCE(uas.total_sessions, 0) * 1 + 
     COALESCE(content_stats.articles_interacted, 0) * 2 + 
     COALESCE(env_stats.quiz_sessions, 0) * 3 + 
     COALESCE(market_stats.orders_placed, 0) * 5 + 
     COALESCE(social_stats.forum_posts, 0) * 2) as user_value_score,
    
    NOW() as last_calculated
FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_sessions,
        MAX(last_activity) as last_activity
    FROM user_sessions 
    GROUP BY user_id
) uas ON u.id = uas.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT ai.article_id) as articles_interacted,
        COUNT(DISTINCT ac.id) as comments_made,
        COUNT(DISTINCT cs.id) as content_shared
    FROM users u2
    LEFT JOIN article_interactions ai ON u2.id = ai.user_id
    LEFT JOIN article_comments ac ON u2.id = ac.user_id
    LEFT JOIN content_shares cs ON u2.id = cs.user_id
    GROUP BY u2.id
) content_stats ON u.id = content_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT qs.id) as quiz_sessions,
        AVG(qs.score) as avg_quiz_score,
        COUNT(DISTINCT cf.id) as carbon_calculations,
        COUNT(DISTINCT wcs.id) as waste_classifications
    FROM users u3
    LEFT JOIN quiz_sessions qs ON u3.id = qs.user_id
    LEFT JOIN carbon_footprints cf ON u3.id = cf.user_id
    LEFT JOIN waste_classification_sessions wcs ON u3.id = wcs.user_id
    GROUP BY u3.id
) env_stats ON u.id = env_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT o.id) as orders_placed,
        SUM(o.total_amount) as total_spent,
        COUNT(DISTINCT pr.id) as reviews_written
    FROM users u4
    LEFT JOIN orders o ON u4.id = o.user_id
    LEFT JOIN product_reviews pr ON u4.id = pr.user_id
    GROUP BY u4.id
) market_stats ON u.id = market_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT fp.id) as forum_posts,
        COUNT(DISTINCT er.id) as event_registrations,
        COUNT(DISTINCT cp.id) as challenge_participations
    FROM users u5
    LEFT JOIN forum_posts fp ON u5.id = fp.user_id
    LEFT JOIN event_registrations er ON u5.id = er.user_id
    LEFT JOIN challenge_participation cp ON u5.id = cp.user_id
    GROUP BY u5.id
) social_stats ON u.id = social_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(CASE WHEN us2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as sessions_last_30_days,
        COUNT(CASE WHEN ai2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as interactions_last_30_days
    FROM users u6
    LEFT JOIN user_sessions us2 ON u6.id = us2.user_id
    LEFT JOIN article_interactions ai2 ON u6.id = ai2.user_id
    GROUP BY u6.id
) recent_stats ON u.id = recent_stats.user_id;

-- ----------------------------------------------------------------
-- 2. CONTENT PERFORMANCE VIEW (Updated for existing tables)
-- Purpose: Article and content performance analytics with engagement metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW content_performance_corrected AS
SELECT 
    a.id as article_id,
    a.title,
    a.slug,
    c.name as category_name,
    u.username as author_name,
    a.created_at as published_date,
    a.updated_at as last_updated,
    DATEDIFF(CURDATE(), a.created_at) as days_since_published,
    
    -- Content Metrics
    CHAR_LENGTH(a.content) as content_length,
    (CHAR_LENGTH(a.content) - CHAR_LENGTH(REPLACE(a.content, ' ', '')) + 1) as estimated_word_count,
    ROUND(CHAR_LENGTH(a.content) / 250, 1) as estimated_reading_time_minutes,
    
    -- Engagement Metrics
    COALESCE(engagement.total_interactions, 0) as total_interactions,
    COALESCE(engagement.total_comments, 0) as total_comments,
    COALESCE(engagement.total_shares, 0) as total_shares,
    COALESCE(engagement.unique_users, 0) as unique_users_engaged,
    
    -- Social Media Metrics
    COALESCE(social.total_social_shares, 0) as total_social_shares,
    
    -- Performance Calculations
    CASE 
        WHEN COALESCE(engagement.total_interactions, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(engagement.total_comments, 0) / COALESCE(engagement.total_interactions, 0)) * 100, 2)
    END as comment_rate_percentage,
    
    CASE 
        WHEN COALESCE(engagement.total_interactions, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(engagement.total_shares, 0) / COALESCE(engagement.total_interactions, 0)) * 100, 2)
    END as share_rate_percentage,
    
    -- Content Performance Tier
    CASE 
        WHEN COALESCE(engagement.total_interactions, 0) >= 500 AND 
             ((COALESCE(engagement.total_comments, 0) + COALESCE(engagement.total_shares, 0)) / COALESCE(engagement.total_interactions, 0)) >= 0.1 
        THEN 'viral'
        WHEN COALESCE(engagement.total_interactions, 0) >= 200 AND 
             ((COALESCE(engagement.total_comments, 0) + COALESCE(engagement.total_shares, 0)) / COALESCE(engagement.total_interactions, 0)) >= 0.05 
        THEN 'high_performing'
        WHEN COALESCE(engagement.total_interactions, 0) >= 50 AND 
             ((COALESCE(engagement.total_comments, 0) + COALESCE(engagement.total_shares, 0)) / COALESCE(engagement.total_interactions, 0)) >= 0.02 
        THEN 'good_performing'
        WHEN COALESCE(engagement.total_interactions, 0) >= 10 
        THEN 'average_performing'
        ELSE 'low_performing'
    END as performance_tier,
    
    -- Monthly Performance
    COALESCE(monthly.interactions_this_month, 0) as interactions_this_month,
    COALESCE(monthly.interactions_last_month, 0) as interactions_last_month,
    
    -- Growth Calculation
    CASE 
        WHEN COALESCE(monthly.interactions_last_month, 0) = 0 THEN 0
        ELSE ROUND(((COALESCE(monthly.interactions_this_month, 0) - COALESCE(monthly.interactions_last_month, 0)) / COALESCE(monthly.interactions_last_month, 0)) * 100, 2)
    END as month_over_month_growth_percentage,
    
    -- Content Engagement Score (weighted metric)
    (COALESCE(engagement.total_interactions, 0) * 1 + 
     COALESCE(engagement.total_comments, 0) * 5 + 
     COALESCE(engagement.total_shares, 0) * 10 + 
     COALESCE(social.total_social_shares, 0) * 15) as content_engagement_score,
    
    NOW() as last_calculated
FROM articles a
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN users u ON a.author_id = u.id
LEFT JOIN (
    SELECT 
        article_id,
        COUNT(*) as total_interactions,
        COUNT(DISTINCT user_id) as unique_users
    FROM article_interactions 
    GROUP BY article_id
) engagement_base ON a.id = engagement_base.article_id
LEFT JOIN (
    SELECT 
        a2.id as article_id,
        COALESCE(engagement_base.total_interactions, 0) as total_interactions,
        COALESCE(engagement_base.unique_users, 0) as unique_users,
        COUNT(DISTINCT ac.id) as total_comments,
        COUNT(DISTINCT cs.id) as total_shares
    FROM articles a2
    LEFT JOIN (
        SELECT 
            article_id,
            COUNT(*) as total_interactions,
            COUNT(DISTINCT user_id) as unique_users
        FROM article_interactions 
        GROUP BY article_id
    ) engagement_base ON a2.id = engagement_base.article_id
    LEFT JOIN article_comments ac ON a2.id = ac.article_id
    LEFT JOIN content_shares cs ON a2.id = cs.content_id AND cs.content_type = 'article'
    GROUP BY a2.id
) engagement ON a.id = engagement.article_id
LEFT JOIN (
    SELECT 
        content_id,
        COUNT(*) as total_social_shares
    FROM content_shares 
    WHERE content_type = 'article'
    GROUP BY content_id
) social ON a.id = social.content_id
LEFT JOIN (
    SELECT 
        ai.article_id,
        COUNT(CASE WHEN YEAR(ai.created_at) = YEAR(CURDATE()) AND MONTH(ai.created_at) = MONTH(CURDATE()) THEN 1 END) as interactions_this_month,
        COUNT(CASE WHEN YEAR(ai.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(ai.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1 END) as interactions_last_month
    FROM article_interactions ai
    GROUP BY ai.article_id
) monthly ON a.id = monthly.article_id
WHERE a.status = 'published';

-- ----------------------------------------------------------------
-- 3. ENVIRONMENTAL IMPACT SUMMARY VIEW (Updated for existing tables)
-- Purpose: Carbon footprint, waste classification, and environmental metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW environmental_impact_summary_corrected AS
SELECT 
    -- User Information
    u.id as user_id,
    u.username,
    u.created_at as user_registration_date,
    
    -- Carbon Footprint Analytics
    COALESCE(carbon_stats.total_calculations, 0) as carbon_calculations_count,
    COALESCE(carbon_stats.total_emissions_kg, 0) as total_carbon_emissions_kg,
    COALESCE(carbon_stats.avg_emissions, 0) as avg_carbon_emissions_kg,
    COALESCE(carbon_stats.transportation_emissions, 0) as transportation_carbon_kg,
    COALESCE(carbon_stats.energy_emissions, 0) as energy_carbon_kg,
    COALESCE(carbon_stats.food_emissions, 0) as food_carbon_kg,
    
    -- Waste Classification Analytics
    COALESCE(waste_stats.total_sessions, 0) as waste_classification_sessions,
    COALESCE(waste_stats.total_results, 0) as waste_classification_results,
    COALESCE(waste_stats.avg_accuracy, 0) as waste_classification_avg_accuracy,
    
    -- Environmental Quiz Performance
    COALESCE(quiz_stats.quiz_sessions_completed, 0) as environmental_quiz_sessions_completed,
    COALESCE(quiz_stats.avg_score, 0) as avg_environmental_quiz_score,
    COALESCE(quiz_stats.best_score, 0) as best_environmental_quiz_score,
    COALESCE(quiz_stats.total_questions_answered, 0) as total_quiz_questions_answered,
    
    -- Challenge and Goal Activities
    COALESCE(goals_stats.active_goals, 0) as active_carbon_reduction_goals,
    COALESCE(goals_stats.completed_goals, 0) as completed_carbon_reduction_goals,
    COALESCE(challenge_stats.challenge_participations, 0) as challenge_participations,
    
    -- Environmental Impact Categories
    CASE 
        WHEN COALESCE(carbon_stats.avg_emissions, 0) <= 500 THEN 'low_impact'
        WHEN COALESCE(carbon_stats.avg_emissions, 0) <= 1000 THEN 'moderate_impact'
        WHEN COALESCE(carbon_stats.avg_emissions, 0) <= 2000 THEN 'high_impact'
        ELSE 'very_high_impact'
    END as carbon_impact_category,
    
    -- Environmental Awareness Level
    CASE 
        WHEN COALESCE(quiz_stats.avg_score, 0) >= 90 THEN 'environmental_expert'
        WHEN COALESCE(quiz_stats.avg_score, 0) >= 75 THEN 'environmental_advocate'
        WHEN COALESCE(quiz_stats.avg_score, 0) >= 60 THEN 'environmentally_aware'
        WHEN COALESCE(quiz_stats.avg_score, 0) >= 40 THEN 'learning_environmental'
        ELSE 'environmental_beginner'
    END as environmental_knowledge_level,
    
    -- Environmental Engagement Score
    (COALESCE(carbon_stats.total_calculations, 0) * 5 + 
     COALESCE(waste_stats.total_sessions, 0) * 3 + 
     COALESCE(quiz_stats.quiz_sessions_completed, 0) * 10 + 
     COALESCE(challenge_stats.challenge_participations, 0) * 15 + 
     COALESCE(quiz_stats.avg_score, 0) * 2) as environmental_engagement_score,
    
    -- Recent Activity (last 30 days)
    COALESCE(recent_activity.carbon_calculations_last_30_days, 0) as carbon_calculations_last_30_days,
    COALESCE(recent_activity.waste_sessions_last_30_days, 0) as waste_sessions_last_30_days,
    COALESCE(recent_activity.quiz_sessions_last_30_days, 0) as quiz_sessions_last_30_days,
    
    NOW() as last_calculated
FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_calculations,
        SUM(carbon_footprint) as total_emissions_kg,
        AVG(carbon_footprint) as avg_emissions,
        SUM(transportation_emissions) as transportation_emissions,
        SUM(energy_emissions) as energy_emissions,
        SUM(food_emissions) as food_emissions
    FROM carbon_footprints 
    GROUP BY user_id
) carbon_stats ON u.id = carbon_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT wcs.id) as total_sessions,
        COUNT(DISTINCT wcr.id) as total_results,
        AVG(wcr.accuracy_score) as avg_accuracy
    FROM users u2
    LEFT JOIN waste_classification_sessions wcs ON u2.id = wcs.user_id
    LEFT JOIN waste_classification_results wcr ON wcs.id = wcr.session_id
    GROUP BY u2.id
) waste_stats ON u.id = waste_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT qs.id) as quiz_sessions_completed,
        AVG(qs.score) as avg_score,
        MAX(qs.score) as best_score,
        COUNT(DISTINCT qr.id) as total_questions_answered
    FROM users u3
    LEFT JOIN quiz_sessions qs ON u3.id = qs.user_id
    LEFT JOIN quiz_responses qr ON qs.id = qr.session_id
    GROUP BY u3.id
) quiz_stats ON u.id = quiz_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_goals,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_goals
    FROM carbon_reduction_goals 
    GROUP BY user_id
) goals_stats ON u.id = goals_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as challenge_participations
    FROM challenge_participation 
    GROUP BY user_id
) challenge_stats ON u.id = challenge_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(CASE WHEN cf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as carbon_calculations_last_30_days,
        COUNT(CASE WHEN wcs.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as waste_sessions_last_30_days,
        COUNT(CASE WHEN qs.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as quiz_sessions_last_30_days
    FROM users u4
    LEFT JOIN carbon_footprints cf ON u4.id = cf.user_id
    LEFT JOIN waste_classification_sessions wcs ON u4.id = wcs.user_id
    LEFT JOIN quiz_sessions qs ON u4.id = qs.user_id
    GROUP BY u4.id
) recent_activity ON u.id = recent_activity.user_id;

-- ----------------------------------------------------------------
-- 4. MARKETPLACE INSIGHTS VIEW (Updated for existing tables)
-- Purpose: E-commerce analytics with conversion rates and profitability
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW marketplace_insights_corrected AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    p.sku,
    c.name as category_name,
    s.company_name as seller_name,
    p.price,
    COALESCE(p.original_price, p.price) as original_price,
    (p.price - COALESCE(p.original_price * 0.7, p.price * 0.7)) as estimated_profit_margin,
    p.stock_quantity,
    p.created_at as product_launch_date,
    DATEDIFF(CURDATE(), p.created_at) as days_since_launch,
    
    -- Sales Performance
    COALESCE(sales_stats.total_orders, 0) as total_orders,
    COALESCE(sales_stats.total_quantity_sold, 0) as total_quantity_sold,
    COALESCE(sales_stats.total_revenue, 0) as total_revenue,
    COALESCE(sales_stats.avg_order_value, 0) as average_order_value,
    COALESCE(sales_stats.unique_customers, 0) as unique_customers_count,
    
    -- Customer Feedback
    COALESCE(reviews_stats.total_reviews, 0) as total_reviews,
    COALESCE(reviews_stats.avg_rating, 0) as average_rating,
    COALESCE(reviews_stats.five_star_reviews, 0) as five_star_reviews,
    COALESCE(reviews_stats.one_star_reviews, 0) as one_star_reviews,
    
    -- Inventory Metrics
    CASE 
        WHEN p.stock_quantity <= 5 THEN 'low_stock'
        WHEN p.stock_quantity <= 20 THEN 'medium_stock'
        ELSE 'high_stock'
    END as stock_status,
    
    -- Performance Categories
    CASE 
        WHEN COALESCE(sales_stats.total_orders, 0) >= 100 AND COALESCE(reviews_stats.avg_rating, 0) >= 4.5 THEN 'bestseller'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 50 AND COALESCE(reviews_stats.avg_rating, 0) >= 4.0 THEN 'high_performer'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 20 AND COALESCE(reviews_stats.avg_rating, 0) >= 3.5 THEN 'good_performer'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 5 THEN 'average_performer'
        ELSE 'underperformer'
    END as performance_category,
    
    -- Monthly Performance
    COALESCE(monthly_stats.orders_this_month, 0) as orders_this_month,
    COALESCE(monthly_stats.orders_last_month, 0) as orders_last_month,
    COALESCE(monthly_stats.revenue_this_month, 0) as revenue_this_month,
    COALESCE(monthly_stats.revenue_last_month, 0) as revenue_last_month,
    
    -- Growth Calculations
    CASE 
        WHEN COALESCE(monthly_stats.orders_last_month, 0) = 0 THEN 0
        ELSE ROUND(((COALESCE(monthly_stats.orders_this_month, 0) - COALESCE(monthly_stats.orders_last_month, 0)) / COALESCE(monthly_stats.orders_last_month, 0)) * 100, 2)
    END as order_growth_month_over_month,
    
    CASE 
        WHEN COALESCE(monthly_stats.revenue_last_month, 0) = 0 THEN 0
        ELSE ROUND(((COALESCE(monthly_stats.revenue_this_month, 0) - COALESCE(monthly_stats.revenue_last_month, 0)) / COALESCE(monthly_stats.revenue_last_month, 0)) * 100, 2)
    END as revenue_growth_month_over_month,
    
    -- Product Score (comprehensive performance metric)
    (COALESCE(sales_stats.total_orders, 0) * 10 + 
     COALESCE(reviews_stats.avg_rating, 0) * 20 + 
     COALESCE(sales_stats.total_revenue, 0) * 0.01) as product_performance_score,
    
    NOW() as last_calculated
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN sellers s ON p.seller_id = s.id
LEFT JOIN (
    SELECT 
        oi.product_id,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.quantity) as total_quantity_sold,
        SUM(oi.price * oi.quantity) as total_revenue,
        AVG(oi.price * oi.quantity) as avg_order_value,
        COUNT(DISTINCT o.user_id) as unique_customers
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN ('completed', 'delivered')
    GROUP BY oi.product_id
) sales_stats ON p.id = sales_stats.product_id
LEFT JOIN (
    SELECT 
        product_id,
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_reviews,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star_reviews
    FROM product_reviews 
    GROUP BY product_id
) reviews_stats ON p.id = reviews_stats.product_id
LEFT JOIN (
    SELECT 
        oi.product_id,
        COUNT(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) THEN 1 END) as orders_this_month,
        COUNT(CASE WHEN YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1 END) as orders_last_month,
        SUM(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) THEN oi.price * oi.quantity ELSE 0 END) as revenue_this_month,
        SUM(CASE WHEN YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN oi.price * oi.quantity ELSE 0 END) as revenue_last_month
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN ('completed', 'delivered')
    GROUP BY oi.product_id
) monthly_stats ON p.id = monthly_stats.product_id;

-- ================================================================
-- PERFORMANCE OPTIMIZATION INDEXES (UPDATED FOR EXISTING SCHEMA)
-- ================================================================

-- ----------------------------------------------------------------
-- User Engagement Indexes
-- ----------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_activity ON user_sessions(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_article_interactions_user_article ON article_interactions(user_id, article_id, created_at);
CREATE INDEX IF NOT EXISTS idx_article_comments_user_article ON article_comments(user_id, article_id, created_at);
CREATE INDEX IF NOT EXISTS idx_content_shares_user_content ON content_shares(user_id, content_type, created_at);

-- ----------------------------------------------------------------
-- Content Performance Indexes
-- ----------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_articles_performance_analysis ON articles(created_at, status, category_id);
CREATE INDEX IF NOT EXISTS idx_articles_author_content ON articles(author_id, created_at, status);
CREATE INDEX IF NOT EXISTS idx_article_interactions_analytics ON article_interactions(article_id, created_at, interaction_type);

-- ----------------------------------------------------------------
-- Environmental Impact Indexes
-- ----------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_user_analytics ON carbon_footprints(user_id, created_at, carbon_footprint);
CREATE INDEX IF NOT EXISTS idx_waste_classification_sessions_user ON waste_classification_sessions(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_waste_classification_results_session ON waste_classification_results(session_id, accuracy_score);
CREATE INDEX IF NOT EXISTS idx_quiz_sessions_user_score ON quiz_sessions(user_id, score, created_at);
CREATE INDEX IF NOT EXISTS idx_carbon_reduction_goals_user_status ON carbon_reduction_goals(user_id, status, created_at);

-- ----------------------------------------------------------------
-- Marketplace Performance Indexes
-- ----------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_products_marketplace_analytics ON products(created_at, price, stock_quantity);
CREATE INDEX IF NOT EXISTS idx_order_items_product_analytics ON order_items(product_id, created_at, quantity, price);
CREATE INDEX IF NOT EXISTS idx_orders_status_analytics ON orders(status, created_at, user_id);
CREATE INDEX IF NOT EXISTS idx_product_reviews_analytics ON product_reviews(product_id, rating, created_at);

-- ================================================================
-- DASHBOARD OPTIMIZATION VIEWS (UPDATED)
-- ================================================================

-- Real-time dashboard metrics view
CREATE OR REPLACE VIEW dashboard_real_time_metrics_corrected AS
SELECT 
    -- Today's Metrics
    (SELECT COUNT(DISTINCT user_id) FROM user_sessions WHERE DATE(created_at) = CURDATE()) as active_users_today,
    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
    (SELECT COUNT(*) FROM article_interactions WHERE DATE(created_at) = CURDATE()) as article_interactions_today,
    (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('completed', 'delivered')) as orders_today,
    (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('completed', 'delivered')) as revenue_today,
    
    -- Environmental Impact Today
    (SELECT COUNT(*) FROM carbon_footprints WHERE DATE(created_at) = CURDATE()) as carbon_calculations_today,
    (SELECT COUNT(*) FROM waste_classification_sessions WHERE DATE(created_at) = CURDATE()) as waste_sessions_today,
    (SELECT COUNT(*) FROM quiz_sessions WHERE DATE(created_at) = CURDATE()) as quiz_sessions_today,
    
    -- System Health
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_active_users,
    (SELECT COUNT(*) FROM articles WHERE status = 'published') as total_published_articles,
    (SELECT COUNT(*) FROM products) as total_products,
    
    NOW() as last_updated;

-- ================================================================
-- ANALYTICS DATA VALIDATION (UPDATED)
-- ================================================================

-- View to validate analytics data integrity
CREATE OR REPLACE VIEW analytics_data_validation_corrected AS
SELECT 
    'user_engagement_summary_corrected' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN user_value_score > 0 THEN 1 END) as records_with_engagement,
    AVG(user_value_score) as avg_engagement_score,
    NOW() as checked_at
FROM user_engagement_summary_corrected
UNION ALL
SELECT 
    'content_performance_corrected' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN content_engagement_score > 0 THEN 1 END) as records_with_engagement,
    AVG(content_engagement_score) as avg_engagement_score,
    NOW() as checked_at
FROM content_performance_corrected
UNION ALL
SELECT 
    'environmental_impact_summary_corrected' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN environmental_engagement_score > 0 THEN 1 END) as records_with_engagement,
    AVG(environmental_engagement_score) as avg_engagement_score,
    NOW() as checked_at
FROM environmental_impact_summary_corrected
UNION ALL
SELECT 
    'marketplace_insights_corrected' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN product_performance_score > 0 THEN 1 END) as records_with_engagement,
    AVG(product_performance_score) as avg_engagement_score,
    NOW() as checked_at
FROM marketplace_insights_corrected;

-- Final verification query
SELECT 
    'Phase 22: Analytics Views & Performance Implementation (CORRECTED)' as phase,
    'COMPLETED' as status,
    CONCAT(
        'Created 4 major analytics views with corrected schema, ',
        'performance indexes, and dashboard optimization system'
    ) as summary,
    NOW() as completed_at;
