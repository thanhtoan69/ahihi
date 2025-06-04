-- ================================================================
-- Phase 22: Analytics Views & Performance Implementation
-- Environmental Platform Database Enhancement
-- 
-- Purpose: Create comprehensive analytics views and performance indexes
-- Features: User engagement, content performance, environmental impact, marketplace insights
-- Date: 2024
-- ================================================================

-- Enable performance query log for optimization
SET @old_log_output = @@global.log_output;
SET @old_general_log = @@global.general_log;
SET GLOBAL log_output = 'TABLE';
SET GLOBAL general_log = ON;

-- ================================================================
-- ANALYTICS VIEWS CREATION
-- ================================================================

-- ----------------------------------------------------------------
-- 1. USER ENGAGEMENT SUMMARY VIEW
-- Purpose: Comprehensive user activity and engagement metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW user_engagement_summary AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    u.created_at as registration_date,
    
    -- Basic Activity Metrics
    COALESCE(uas.total_logins, 0) as total_logins,
    COALESCE(uas.last_login, NULL) as last_login_date,
    DATEDIFF(CURDATE(), u.created_at) as days_since_registration,
    
    -- Content Engagement
    COALESCE(content_stats.articles_read, 0) as articles_read,
    COALESCE(content_stats.articles_shared, 0) as articles_shared,
    COALESCE(content_stats.comments_made, 0) as comments_made,
    COALESCE(content_stats.articles_liked, 0) as articles_liked,
    
    -- Environmental Activities
    COALESCE(env_stats.quizzes_taken, 0) as environmental_quizzes_taken,
    COALESCE(env_stats.avg_quiz_score, 0) as avg_environmental_quiz_score,
    COALESCE(env_stats.waste_reports, 0) as waste_classification_reports,
    COALESCE(env_stats.carbon_calculations, 0) as carbon_footprint_calculations,
    
    -- E-commerce Activities
    COALESCE(market_stats.orders_placed, 0) as marketplace_orders,
    COALESCE(market_stats.total_spent, 0) as total_marketplace_spending,
    COALESCE(market_stats.reviews_written, 0) as product_reviews_written,
    COALESCE(market_stats.wishlist_items, 0) as wishlist_items_count,
    
    -- Social Engagement
    COALESCE(social_stats.forum_posts, 0) as community_forum_posts,
    COALESCE(social_stats.events_attended, 0) as environmental_events_attended,
    COALESCE(social_stats.groups_joined, 0) as environmental_groups_joined,
    
    -- Engagement Scoring
    CASE 
        WHEN (COALESCE(uas.total_logins, 0) + COALESCE(content_stats.articles_read, 0) + 
              COALESCE(env_stats.quizzes_taken, 0) + COALESCE(market_stats.orders_placed, 0)) >= 100 
        THEN 'eco_champion'
        WHEN (COALESCE(uas.total_logins, 0) + COALESCE(content_stats.articles_read, 0) + 
              COALESCE(env_stats.quizzes_taken, 0) + COALESCE(market_stats.orders_placed, 0)) >= 50 
        THEN 'eco_enthusiast'
        WHEN (COALESCE(uas.total_logins, 0) + COALESCE(content_stats.articles_read, 0) + 
              COALESCE(env_stats.quizzes_taken, 0) + COALESCE(market_stats.orders_placed, 0)) >= 20 
        THEN 'eco_explorer'
        WHEN (COALESCE(uas.total_logins, 0) + COALESCE(content_stats.articles_read, 0) + 
              COALESCE(env_stats.quizzes_taken, 0) + COALESCE(market_stats.orders_placed, 0)) >= 5 
        THEN 'eco_beginner'
        ELSE 'inactive'
    END as user_engagement_level,
    
    -- Activity Trends
    COALESCE(recent_stats.logins_last_30_days, 0) as logins_last_30_days,
    COALESCE(recent_stats.articles_read_last_30_days, 0) as articles_read_last_30_days,
    
    -- User Value Score (weighted engagement metric)
    (COALESCE(uas.total_logins, 0) * 1 + 
     COALESCE(content_stats.articles_read, 0) * 2 + 
     COALESCE(env_stats.quizzes_taken, 0) * 3 + 
     COALESCE(market_stats.orders_placed, 0) * 5 + 
     COALESCE(social_stats.forum_posts, 0) * 2) as user_value_score,
    
    NOW() as last_calculated
FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_logins,
        MAX(login_time) as last_login
    FROM user_activity_sessions 
    GROUP BY user_id
) uas ON u.id = uas.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT ar.article_id) as articles_read,
        COUNT(DISTINCT als.article_id) as articles_shared,
        COUNT(DISTINCT c.id) as comments_made,
        COUNT(DISTINCT al.article_id) as articles_liked
    FROM users u2
    LEFT JOIN article_readings ar ON u2.id = ar.user_id
    LEFT JOIN article_social_shares als ON u2.id = als.user_id
    LEFT JOIN comments c ON u2.id = c.user_id
    LEFT JOIN article_likes al ON u2.id = al.user_id
    GROUP BY u2.id
) content_stats ON u.id = content_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT eq.id) as quizzes_taken,
        AVG(eq.score) as avg_quiz_score,
        COUNT(DISTINCT wr.id) as waste_reports,
        COUNT(DISTINCT cf.id) as carbon_calculations
    FROM users u3
    LEFT JOIN environmental_quizzes eq ON u3.id = eq.user_id
    LEFT JOIN waste_classification_reports wr ON u3.id = wr.user_id
    LEFT JOIN carbon_footprint_calculations cf ON u3.id = cf.user_id
    GROUP BY u3.id
) env_stats ON u.id = env_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT o.id) as orders_placed,
        SUM(o.total_amount) as total_spent,
        COUNT(DISTINCT pr.id) as reviews_written,
        COUNT(DISTINCT w.product_id) as wishlist_items
    FROM users u4
    LEFT JOIN orders o ON u4.id = o.user_id
    LEFT JOIN product_reviews pr ON u4.id = pr.user_id
    LEFT JOIN wishlists w ON u4.id = w.user_id
    GROUP BY u4.id
) market_stats ON u.id = market_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(DISTINCT fp.id) as forum_posts,
        COUNT(DISTINCT ea.event_id) as events_attended,
        COUNT(DISTINCT ugm.group_id) as groups_joined
    FROM users u5
    LEFT JOIN forum_posts fp ON u5.id = fp.user_id
    LEFT JOIN event_attendees ea ON u5.id = ea.user_id
    LEFT JOIN user_group_memberships ugm ON u5.id = ugm.user_id
    GROUP BY u5.id
) social_stats ON u.id = social_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(CASE WHEN uas2.login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as logins_last_30_days,
        COUNT(CASE WHEN ar2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as articles_read_last_30_days
    FROM users u6
    LEFT JOIN user_activity_sessions uas2 ON u6.id = uas2.user_id
    LEFT JOIN article_readings ar2 ON u6.id = ar2.user_id
    GROUP BY u6.id
) recent_stats ON u.id = recent_stats.user_id;

-- ----------------------------------------------------------------
-- 2. CONTENT PERFORMANCE VIEW
-- Purpose: Article and content performance analytics with engagement metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW content_performance AS
SELECT 
    a.id as article_id,
    a.title,
    a.slug,
    c.name as category_name,
    u.username as author_name,
    a.published_date,
    a.updated_at as last_updated,
    DATEDIFF(CURDATE(), a.published_date) as days_since_published,
    
    -- Content Metrics
    CHAR_LENGTH(a.content) as content_length,
    (CHAR_LENGTH(a.content) - CHAR_LENGTH(REPLACE(a.content, ' ', '')) + 1) as estimated_word_count,
    ROUND(CHAR_LENGTH(a.content) / 250, 1) as estimated_reading_time_minutes,
    
    -- Engagement Metrics
    COALESCE(engagement.total_views, 0) as total_views,
    COALESCE(engagement.unique_readers, 0) as unique_readers,
    COALESCE(engagement.total_likes, 0) as total_likes,
    COALESCE(engagement.total_comments, 0) as total_comments,
    COALESCE(engagement.total_shares, 0) as total_shares,
    COALESCE(engagement.avg_reading_time, 0) as avg_reading_time_seconds,
    
    -- Social Media Metrics
    COALESCE(social.facebook_shares, 0) as facebook_shares,
    COALESCE(social.twitter_shares, 0) as twitter_shares,
    COALESCE(social.linkedin_shares, 0) as linkedin_shares,
    COALESCE(social.total_social_shares, 0) as total_social_shares,
    
    -- Performance Calculations
    CASE 
        WHEN COALESCE(engagement.total_views, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(engagement.total_likes, 0) / COALESCE(engagement.total_views, 0)) * 100, 2)
    END as like_rate_percentage,
    
    CASE 
        WHEN COALESCE(engagement.total_views, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(engagement.total_comments, 0) / COALESCE(engagement.total_views, 0)) * 100, 2)
    END as comment_rate_percentage,
    
    CASE 
        WHEN COALESCE(engagement.total_views, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(engagement.total_shares, 0) / COALESCE(engagement.total_views, 0)) * 100, 2)
    END as share_rate_percentage,
    
    -- Content Performance Tier
    CASE 
        WHEN COALESCE(engagement.total_views, 0) >= 10000 AND 
             ((COALESCE(engagement.total_likes, 0) + COALESCE(engagement.total_comments, 0) + COALESCE(engagement.total_shares, 0)) / COALESCE(engagement.total_views, 0)) >= 0.1 
        THEN 'viral'
        WHEN COALESCE(engagement.total_views, 0) >= 5000 AND 
             ((COALESCE(engagement.total_likes, 0) + COALESCE(engagement.total_comments, 0) + COALESCE(engagement.total_shares, 0)) / COALESCE(engagement.total_views, 0)) >= 0.05 
        THEN 'high_performing'
        WHEN COALESCE(engagement.total_views, 0) >= 1000 AND 
             ((COALESCE(engagement.total_likes, 0) + COALESCE(engagement.total_comments, 0) + COALESCE(engagement.total_shares, 0)) / COALESCE(engagement.total_views, 0)) >= 0.02 
        THEN 'good_performing'
        WHEN COALESCE(engagement.total_views, 0) >= 100 
        THEN 'average_performing'
        ELSE 'low_performing'
    END as performance_tier,
    
    -- Monthly Performance
    COALESCE(monthly.views_this_month, 0) as views_this_month,
    COALESCE(monthly.views_last_month, 0) as views_last_month,
    
    -- Growth Calculation
    CASE 
        WHEN COALESCE(monthly.views_last_month, 0) = 0 THEN 0
        ELSE ROUND(((COALESCE(monthly.views_this_month, 0) - COALESCE(monthly.views_last_month, 0)) / COALESCE(monthly.views_last_month, 0)) * 100, 2)
    END as month_over_month_growth_percentage,
    
    -- SEO Metrics
    CASE WHEN a.meta_description IS NOT NULL AND CHAR_LENGTH(a.meta_description) BETWEEN 120 AND 160 THEN 'optimized' ELSE 'needs_optimization' END as meta_description_status,
    CASE WHEN CHAR_LENGTH(a.title) BETWEEN 30 AND 60 THEN 'optimized' ELSE 'needs_optimization' END as title_length_status,
    
    -- Content Engagement Score (weighted metric)
    (COALESCE(engagement.total_views, 0) * 1 + 
     COALESCE(engagement.total_likes, 0) * 5 + 
     COALESCE(engagement.total_comments, 0) * 10 + 
     COALESCE(engagement.total_shares, 0) * 15 + 
     COALESCE(social.total_social_shares, 0) * 20) as content_engagement_score,
    
    NOW() as last_calculated
FROM articles a
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN users u ON a.author_id = u.id
LEFT JOIN (
    SELECT 
        article_id,
        COUNT(*) as total_views,
        COUNT(DISTINCT user_id) as unique_readers,
        AVG(reading_time_seconds) as avg_reading_time
    FROM article_readings 
    GROUP BY article_id
) engagement_base ON a.id = engagement_base.article_id
LEFT JOIN (
    SELECT 
        a2.id as article_id,
        COALESCE(engagement_base.total_views, 0) as total_views,
        COALESCE(engagement_base.unique_readers, 0) as unique_readers,
        COALESCE(engagement_base.avg_reading_time, 0) as avg_reading_time,
        COUNT(DISTINCT al.user_id) as total_likes,
        COUNT(DISTINCT c.id) as total_comments,
        COUNT(DISTINCT ass.id) as total_shares
    FROM articles a2
    LEFT JOIN (
        SELECT 
            article_id,
            COUNT(*) as total_views,
            COUNT(DISTINCT user_id) as unique_readers,
            AVG(reading_time_seconds) as avg_reading_time
        FROM article_readings 
        GROUP BY article_id
    ) engagement_base ON a2.id = engagement_base.article_id
    LEFT JOIN article_likes al ON a2.id = al.article_id
    LEFT JOIN comments c ON a2.id = c.article_id
    LEFT JOIN article_social_shares ass ON a2.id = ass.article_id
    GROUP BY a2.id
) engagement ON a.id = engagement.article_id
LEFT JOIN (
    SELECT 
        article_id,
        COUNT(CASE WHEN platform = 'facebook' THEN 1 END) as facebook_shares,
        COUNT(CASE WHEN platform = 'twitter' THEN 1 END) as twitter_shares,
        COUNT(CASE WHEN platform = 'linkedin' THEN 1 END) as linkedin_shares,
        COUNT(*) as total_social_shares
    FROM article_social_shares 
    GROUP BY article_id
) social ON a.id = social.article_id
LEFT JOIN (
    SELECT 
        ar.article_id,
        COUNT(CASE WHEN YEAR(ar.created_at) = YEAR(CURDATE()) AND MONTH(ar.created_at) = MONTH(CURDATE()) THEN 1 END) as views_this_month,
        COUNT(CASE WHEN YEAR(ar.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(ar.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1 END) as views_last_month
    FROM article_readings ar
    GROUP BY ar.article_id
) monthly ON a.id = monthly.article_id
WHERE a.status = 'published';

-- ----------------------------------------------------------------
-- 3. ENVIRONMENTAL IMPACT SUMMARY VIEW
-- Purpose: Carbon footprint, waste classification, and environmental metrics
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW environmental_impact_summary AS
SELECT 
    -- User Information
    u.id as user_id,
    u.username,
    u.created_at as user_registration_date,
    
    -- Carbon Footprint Analytics
    COALESCE(carbon_stats.total_calculations, 0) as carbon_calculations_count,
    COALESCE(carbon_stats.total_emissions_kg, 0) as total_carbon_emissions_kg,
    COALESCE(carbon_stats.avg_monthly_emissions, 0) as avg_monthly_carbon_emissions_kg,
    COALESCE(carbon_stats.transportation_emissions, 0) as transportation_carbon_kg,
    COALESCE(carbon_stats.energy_emissions, 0) as energy_carbon_kg,
    COALESCE(carbon_stats.food_emissions, 0) as food_carbon_kg,
    COALESCE(carbon_stats.waste_emissions, 0) as waste_carbon_kg,
    
    -- Waste Classification Analytics
    COALESCE(waste_stats.total_reports, 0) as waste_classification_reports,
    COALESCE(waste_stats.recyclable_items, 0) as recyclable_items_identified,
    COALESCE(waste_stats.organic_items, 0) as organic_waste_items,
    COALESCE(waste_stats.hazardous_items, 0) as hazardous_waste_items,
    COALESCE(waste_stats.general_waste_items, 0) as general_waste_items,
    COALESCE(waste_stats.accuracy_rate, 0) as waste_classification_accuracy_rate,
    
    -- Environmental Quiz Performance
    COALESCE(quiz_stats.quizzes_completed, 0) as environmental_quizzes_completed,
    COALESCE(quiz_stats.avg_score, 0) as avg_environmental_quiz_score,
    COALESCE(quiz_stats.best_score, 0) as best_environmental_quiz_score,
    COALESCE(quiz_stats.climate_change_score, 0) as climate_change_quiz_avg_score,
    COALESCE(quiz_stats.recycling_score, 0) as recycling_quiz_avg_score,
    COALESCE(quiz_stats.energy_efficiency_score, 0) as energy_efficiency_quiz_avg_score,
    
    -- Sustainability Actions
    COALESCE(actions_stats.eco_challenges_completed, 0) as eco_challenges_completed,
    COALESCE(actions_stats.green_tips_followed, 0) as green_tips_followed,
    COALESCE(actions_stats.sustainable_purchases, 0) as sustainable_marketplace_purchases,
    
    -- Environmental Impact Categories
    CASE 
        WHEN COALESCE(carbon_stats.avg_monthly_emissions, 0) <= 500 THEN 'low_impact'
        WHEN COALESCE(carbon_stats.avg_monthly_emissions, 0) <= 1000 THEN 'moderate_impact'
        WHEN COALESCE(carbon_stats.avg_monthly_emissions, 0) <= 2000 THEN 'high_impact'
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
    
    -- Carbon Savings Potential (estimated monthly savings in kg CO2)
    CASE 
        WHEN COALESCE(actions_stats.eco_challenges_completed, 0) >= 10 THEN COALESCE(carbon_stats.avg_monthly_emissions, 0) * 0.3
        WHEN COALESCE(actions_stats.eco_challenges_completed, 0) >= 5 THEN COALESCE(carbon_stats.avg_monthly_emissions, 0) * 0.2
        WHEN COALESCE(actions_stats.eco_challenges_completed, 0) >= 1 THEN COALESCE(carbon_stats.avg_monthly_emissions, 0) * 0.1
        ELSE 0
    END as estimated_monthly_carbon_savings_kg,
    
    -- Waste Diversion Rate (percentage of waste properly classified for recycling)
    CASE 
        WHEN COALESCE(waste_stats.total_reports, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(waste_stats.recyclable_items, 0) / COALESCE(waste_stats.total_reports, 0)) * 100, 2)
    END as waste_diversion_percentage,
    
    -- Environmental Engagement Score
    (COALESCE(carbon_stats.total_calculations, 0) * 5 + 
     COALESCE(waste_stats.total_reports, 0) * 3 + 
     COALESCE(quiz_stats.quizzes_completed, 0) * 10 + 
     COALESCE(actions_stats.eco_challenges_completed, 0) * 15 + 
     COALESCE(quiz_stats.avg_score, 0) * 2) as environmental_engagement_score,
    
    -- Recent Activity (last 30 days)
    COALESCE(recent_activity.carbon_calculations_last_30_days, 0) as carbon_calculations_last_30_days,
    COALESCE(recent_activity.waste_reports_last_30_days, 0) as waste_reports_last_30_days,
    COALESCE(recent_activity.quizzes_last_30_days, 0) as environmental_quizzes_last_30_days,
    
    NOW() as last_calculated
FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_calculations,
        SUM(total_emissions_kg) as total_emissions_kg,
        AVG(total_emissions_kg) as avg_monthly_emissions,
        SUM(transportation_emissions) as transportation_emissions,
        SUM(energy_emissions) as energy_emissions,
        SUM(food_emissions) as food_emissions,
        SUM(waste_emissions) as waste_emissions
    FROM carbon_footprint_calculations 
    GROUP BY user_id
) carbon_stats ON u.id = carbon_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as total_reports,
        COUNT(CASE WHEN predicted_category = 'recyclable' THEN 1 END) as recyclable_items,
        COUNT(CASE WHEN predicted_category = 'organic' THEN 1 END) as organic_items,
        COUNT(CASE WHEN predicted_category = 'hazardous' THEN 1 END) as hazardous_items,
        COUNT(CASE WHEN predicted_category = 'general' THEN 1 END) as general_waste_items,
        AVG(confidence_score) as accuracy_rate
    FROM waste_classification_reports 
    GROUP BY user_id
) waste_stats ON u.id = waste_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(*) as quizzes_completed,
        AVG(score) as avg_score,
        MAX(score) as best_score,
        AVG(CASE WHEN quiz_type = 'climate_change' THEN score END) as climate_change_score,
        AVG(CASE WHEN quiz_type = 'recycling' THEN score END) as recycling_score,
        AVG(CASE WHEN quiz_type = 'energy_efficiency' THEN score END) as energy_efficiency_score
    FROM environmental_quizzes 
    GROUP BY user_id
) quiz_stats ON u.id = quiz_stats.user_id
LEFT JOIN (
    SELECT 
        u2.id as user_id,
        COUNT(DISTINCT ec.id) as eco_challenges_completed,
        COUNT(DISTINCT gt.id) as green_tips_followed,
        COUNT(DISTINCT o.id) as sustainable_purchases
    FROM users u2
    LEFT JOIN eco_challenge_completions ec ON u2.id = ec.user_id
    LEFT JOIN user_green_tip_actions gt ON u2.id = gt.user_id
    LEFT JOIN orders o ON u2.id = o.user_id 
    LEFT JOIN products p ON o.id = p.id 
    WHERE p.sustainability_rating >= 4 OR p.eco_friendly = 1
    GROUP BY u2.id
) actions_stats ON u.id = actions_stats.user_id
LEFT JOIN (
    SELECT 
        user_id,
        COUNT(CASE WHEN cfc.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as carbon_calculations_last_30_days,
        COUNT(CASE WHEN wcr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as waste_reports_last_30_days,
        COUNT(CASE WHEN eq.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as quizzes_last_30_days
    FROM users u3
    LEFT JOIN carbon_footprint_calculations cfc ON u3.id = cfc.user_id
    LEFT JOIN waste_classification_reports wcr ON u3.id = wcr.user_id
    LEFT JOIN environmental_quizzes eq ON u3.id = eq.user_id
    GROUP BY u3.id
) recent_activity ON u.id = recent_activity.user_id;

-- ----------------------------------------------------------------
-- 4. MARKETPLACE INSIGHTS VIEW
-- Purpose: E-commerce analytics with conversion rates and profitability
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW marketplace_insights AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    p.sku,
    c.name as category_name,
    s.company_name as seller_name,
    p.price,
    p.cost_price,
    (p.price - p.cost_price) as profit_margin,
    CASE 
        WHEN p.price > 0 THEN ROUND(((p.price - p.cost_price) / p.price) * 100, 2)
        ELSE 0
    END as profit_margin_percentage,
    p.stock_quantity,
    p.sustainability_rating,
    p.eco_friendly,
    p.created_at as product_launch_date,
    DATEDIFF(CURDATE(), p.created_at) as days_since_launch,
    
    -- Sales Performance
    COALESCE(sales_stats.total_orders, 0) as total_orders,
    COALESCE(sales_stats.total_quantity_sold, 0) as total_quantity_sold,
    COALESCE(sales_stats.total_revenue, 0) as total_revenue,
    COALESCE(sales_stats.avg_order_value, 0) as average_order_value,
    COALESCE(sales_stats.unique_customers, 0) as unique_customers_count,
    
    -- Product Views and Conversion
    COALESCE(views_stats.total_views, 0) as total_product_views,
    COALESCE(views_stats.unique_viewers, 0) as unique_product_viewers,
    CASE 
        WHEN COALESCE(views_stats.total_views, 0) = 0 THEN 0
        ELSE ROUND((COALESCE(sales_stats.total_orders, 0) / COALESCE(views_stats.total_views, 0)) * 100, 2)
    END as conversion_rate_percentage,
    
    -- Customer Feedback
    COALESCE(reviews_stats.total_reviews, 0) as total_reviews,
    COALESCE(reviews_stats.avg_rating, 0) as average_rating,
    COALESCE(reviews_stats.five_star_reviews, 0) as five_star_reviews,
    COALESCE(reviews_stats.one_star_reviews, 0) as one_star_reviews,
    
    -- Wishlist and Social Metrics
    COALESCE(engagement_stats.wishlist_adds, 0) as wishlist_additions,
    COALESCE(engagement_stats.social_shares, 0) as social_shares,
    COALESCE(engagement_stats.questions_asked, 0) as customer_questions,
    
    -- Inventory Metrics
    CASE 
        WHEN p.stock_quantity <= 5 THEN 'low_stock'
        WHEN p.stock_quantity <= 20 THEN 'medium_stock'
        ELSE 'high_stock'
    END as stock_status,
    
    CASE 
        WHEN COALESCE(sales_stats.total_quantity_sold, 0) = 0 THEN 999
        ELSE ROUND(p.stock_quantity / (COALESCE(sales_stats.total_quantity_sold, 0) / GREATEST(DATEDIFF(CURDATE(), p.created_at), 1)) * 30, 0)
    END as estimated_days_of_inventory,
    
    -- Performance Categories
    CASE 
        WHEN COALESCE(sales_stats.total_orders, 0) >= 100 AND COALESCE(reviews_stats.avg_rating, 0) >= 4.5 THEN 'bestseller'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 50 AND COALESCE(reviews_stats.avg_rating, 0) >= 4.0 THEN 'high_performer'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 20 AND COALESCE(reviews_stats.avg_rating, 0) >= 3.5 THEN 'good_performer'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 5 THEN 'average_performer'
        WHEN COALESCE(views_stats.total_views, 0) >= 100 THEN 'high_interest_low_conversion'
        ELSE 'underperformer'
    END as performance_category,
    
    -- Sustainability Score
    CASE 
        WHEN p.eco_friendly = 1 AND p.sustainability_rating >= 4 THEN 'highly_sustainable'
        WHEN p.eco_friendly = 1 OR p.sustainability_rating >= 3 THEN 'moderately_sustainable'
        WHEN p.sustainability_rating >= 2 THEN 'somewhat_sustainable'
        ELSE 'needs_improvement'
    END as sustainability_category,
    
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
     COALESCE(views_stats.total_views, 0) * 0.1 + 
     (CASE WHEN p.price > 0 THEN ((p.price - p.cost_price) / p.price) * 100 ELSE 0 END) * 1 + 
     p.sustainability_rating * 15) as product_performance_score,
    
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
        COUNT(*) as total_views,
        COUNT(DISTINCT user_id) as unique_viewers
    FROM product_views 
    GROUP BY product_id
) views_stats ON p.id = views_stats.product_id
LEFT JOIN (
    SELECT 
        product_id,
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_reviews,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star_reviews
    FROM product_reviews 
    WHERE status = 'approved'
    GROUP BY product_id
) reviews_stats ON p.id = reviews_stats.product_id
LEFT JOIN (
    SELECT 
        p2.id as product_id,
        COUNT(DISTINCT w.user_id) as wishlist_adds,
        COUNT(DISTINCT pss.id) as social_shares,
        COUNT(DISTINCT pq.id) as questions_asked
    FROM products p2
    LEFT JOIN wishlists w ON p2.id = w.product_id
    LEFT JOIN product_social_shares pss ON p2.id = pss.product_id
    LEFT JOIN product_questions pq ON p2.id = pq.product_id
    GROUP BY p2.id
) engagement_stats ON p.id = engagement_stats.product_id
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
) monthly_stats ON p.id = monthly_stats.product_id
WHERE p.status = 'active';

-- ================================================================
-- PERFORMANCE OPTIMIZATION INDEXES
-- ================================================================

-- ----------------------------------------------------------------
-- User Engagement Indexes
-- ----------------------------------------------------------------
CREATE INDEX idx_user_activity_sessions_user_login ON user_activity_sessions(user_id, login_time);
CREATE INDEX idx_user_activity_sessions_monthly ON user_activity_sessions(login_time);
CREATE INDEX idx_article_readings_user_article ON article_readings(user_id, article_id, created_at);
CREATE INDEX idx_article_social_shares_user ON article_social_shares(user_id, shared_at);
CREATE INDEX idx_comments_user_article ON comments(user_id, article_id, created_at);
CREATE INDEX idx_article_likes_user_article ON article_likes(user_id, article_id);

-- ----------------------------------------------------------------
-- Content Performance Indexes
-- ----------------------------------------------------------------
CREATE INDEX idx_articles_published_performance ON articles(published_date, status, category_id);
CREATE INDEX idx_articles_author_performance ON articles(author_id, published_date, status);
CREATE INDEX idx_article_readings_article_performance ON article_readings(article_id, created_at, reading_time_seconds);
CREATE INDEX idx_article_social_shares_platform ON article_social_shares(article_id, platform, shared_at);
CREATE INDEX idx_articles_content_analysis ON articles(CHAR_LENGTH(content), published_date);

-- ----------------------------------------------------------------
-- Environmental Impact Indexes
-- ----------------------------------------------------------------
CREATE INDEX idx_carbon_footprint_user_emissions ON carbon_footprint_calculations(user_id, total_emissions_kg, created_at);
CREATE INDEX idx_carbon_footprint_monthly ON carbon_footprint_calculations(created_at, total_emissions_kg);
CREATE INDEX idx_waste_classification_user_category ON waste_classification_reports(user_id, predicted_category, confidence_score);
CREATE INDEX idx_environmental_quizzes_user_score ON environmental_quizzes(user_id, score, quiz_type, created_at);
CREATE INDEX idx_eco_challenge_completions_user ON eco_challenge_completions(user_id, completed_at);

-- ----------------------------------------------------------------
-- Marketplace Performance Indexes
-- ----------------------------------------------------------------
CREATE INDEX idx_products_performance_metrics ON products(status, created_at, price, sustainability_rating);
CREATE INDEX idx_order_items_product_performance ON order_items(product_id, created_at, quantity, price);
CREATE INDEX idx_orders_completion_status ON orders(status, created_at, user_id);
CREATE INDEX idx_product_views_analytics ON product_views(product_id, user_id, viewed_at);
CREATE INDEX idx_product_reviews_rating_status ON product_reviews(product_id, rating, status, created_at);
CREATE INDEX idx_wishlists_product_user ON wishlists(product_id, user_id, added_at);

-- ----------------------------------------------------------------
-- Time-based Analytics Indexes
-- ----------------------------------------------------------------
CREATE INDEX idx_daily_analytics_date ON article_readings(DATE(created_at));
CREATE INDEX idx_weekly_analytics_date ON orders(YEAR(created_at), WEEK(created_at));
CREATE INDEX idx_monthly_analytics_date ON user_activity_sessions(YEAR(login_time), MONTH(login_time));

-- ----------------------------------------------------------------
-- Cross-Platform Analytics Indexes
-- ----------------------------------------------------------------
CREATE INDEX idx_user_comprehensive_engagement ON users(created_at, status);
CREATE INDEX idx_content_cross_platform ON articles(category_id, published_date, status);
CREATE INDEX idx_marketplace_sustainability ON products(eco_friendly, sustainability_rating, status);

-- ================================================================
-- MATERIALIZED VIEW SIMULATION - DAILY ANALYTICS SUMMARY
-- ================================================================

-- Create daily analytics summary table for faster dashboard queries
CREATE TABLE IF NOT EXISTS daily_analytics_summary (
    summary_date DATE PRIMARY KEY,
    total_active_users INT DEFAULT 0,
    new_user_registrations INT DEFAULT 0,
    total_article_views INT DEFAULT 0,
    total_articles_published INT DEFAULT 0,
    total_marketplace_orders INT DEFAULT 0,
    total_marketplace_revenue DECIMAL(15,2) DEFAULT 0,
    total_carbon_calculations INT DEFAULT 0,
    total_waste_reports INT DEFAULT 0,
    total_environmental_quizzes INT DEFAULT 0,
    avg_environmental_quiz_score DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_summary_date (summary_date),
    INDEX idx_summary_created (created_at)
);

-- ================================================================
-- ANALYTICS HELPER PROCEDURES
-- ================================================================

-- Procedure to refresh daily analytics summary
DELIMITER //
CREATE PROCEDURE RefreshDailyAnalyticsSummary(IN target_date DATE)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    INSERT INTO daily_analytics_summary (
        summary_date,
        total_active_users,
        new_user_registrations,
        total_article_views,
        total_articles_published,
        total_marketplace_orders,
        total_marketplace_revenue,
        total_carbon_calculations,
        total_waste_reports,
        total_environmental_quizzes,
        avg_environmental_quiz_score
    ) VALUES (
        target_date,
        (SELECT COUNT(DISTINCT user_id) FROM user_activity_sessions WHERE DATE(login_time) = target_date),
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM article_readings WHERE DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM articles WHERE DATE(published_date) = target_date AND status = 'published'),
        (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = target_date AND status IN ('completed', 'delivered')),
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = target_date AND status IN ('completed', 'delivered')),
        (SELECT COUNT(*) FROM carbon_footprint_calculations WHERE DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM waste_classification_reports WHERE DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM environmental_quizzes WHERE DATE(created_at) = target_date),
        (SELECT AVG(score) FROM environmental_quizzes WHERE DATE(created_at) = target_date)
    ) ON DUPLICATE KEY UPDATE
        total_active_users = VALUES(total_active_users),
        new_user_registrations = VALUES(new_user_registrations),
        total_article_views = VALUES(total_article_views),
        total_articles_published = VALUES(total_articles_published),
        total_marketplace_orders = VALUES(total_marketplace_orders),
        total_marketplace_revenue = VALUES(total_marketplace_revenue),
        total_carbon_calculations = VALUES(total_carbon_calculations),
        total_waste_reports = VALUES(total_waste_reports),
        total_environmental_quizzes = VALUES(total_environmental_quizzes),
        avg_environmental_quiz_score = VALUES(avg_environmental_quiz_score),
        updated_at = CURRENT_TIMESTAMP;
    
    COMMIT;
END //
DELIMITER ;

-- ================================================================
-- DASHBOARD OPTIMIZATION VIEWS
-- ================================================================

-- Real-time dashboard metrics view
CREATE OR REPLACE VIEW dashboard_real_time_metrics AS
SELECT 
    -- Today's Metrics
    (SELECT COUNT(DISTINCT user_id) FROM user_activity_sessions WHERE DATE(login_time) = CURDATE()) as active_users_today,
    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
    (SELECT COUNT(*) FROM article_readings WHERE DATE(created_at) = CURDATE()) as article_views_today,
    (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('completed', 'delivered')) as orders_today,
    (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('completed', 'delivered')) as revenue_today,
    
    -- This Month vs Last Month
    (SELECT COUNT(DISTINCT user_id) FROM user_activity_sessions WHERE YEAR(login_time) = YEAR(CURDATE()) AND MONTH(login_time) = MONTH(CURDATE())) as active_users_this_month,
    (SELECT COUNT(DISTINCT user_id) FROM user_activity_sessions WHERE YEAR(login_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(login_time) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))) as active_users_last_month,
    
    -- Environmental Impact Today
    (SELECT COUNT(*) FROM carbon_footprint_calculations WHERE DATE(created_at) = CURDATE()) as carbon_calculations_today,
    (SELECT COUNT(*) FROM waste_classification_reports WHERE DATE(created_at) = CURDATE()) as waste_reports_today,
    (SELECT COUNT(*) FROM environmental_quizzes WHERE DATE(created_at) = CURDATE()) as environmental_quizzes_today,
    
    -- System Health
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_active_users,
    (SELECT COUNT(*) FROM articles WHERE status = 'published') as total_published_articles,
    (SELECT COUNT(*) FROM products WHERE status = 'active') as total_active_products,
    
    NOW() as last_updated;

-- ================================================================
-- ANALYTICS DATA VALIDATION
-- ================================================================

-- View to validate analytics data integrity
CREATE OR REPLACE VIEW analytics_data_validation AS
SELECT 
    'user_engagement_summary' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN user_value_score > 0 THEN 1 END) as records_with_engagement,
    AVG(user_value_score) as avg_engagement_score,
    NOW() as checked_at
FROM user_engagement_summary
UNION ALL
SELECT 
    'content_performance' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN content_engagement_score > 0 THEN 1 END) as records_with_engagement,
    AVG(content_engagement_score) as avg_engagement_score,
    NOW() as checked_at
FROM content_performance
UNION ALL
SELECT 
    'environmental_impact_summary' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN environmental_engagement_score > 0 THEN 1 END) as records_with_engagement,
    AVG(environmental_engagement_score) as avg_engagement_score,
    NOW() as checked_at
FROM environmental_impact_summary
UNION ALL
SELECT 
    'marketplace_insights' as view_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN product_performance_score > 0 THEN 1 END) as records_with_engagement,
    AVG(product_performance_score) as avg_engagement_score,
    NOW() as checked_at
FROM marketplace_insights;

-- Restore original logging settings
SET GLOBAL log_output = @old_log_output;
SET GLOBAL general_log = @old_general_log;

-- ================================================================
-- PHASE 22 COMPLETION LOG
-- ================================================================
INSERT INTO database_migrations (
    phase_number, 
    phase_name, 
    description, 
    status, 
    executed_at
) VALUES (
    22, 
    'Analytics Views & Performance', 
    'Created comprehensive analytics views: user_engagement_summary, content_performance, environmental_impact_summary, marketplace_insights. Added 25+ performance indexes and daily analytics summary system.', 
    'completed', 
    NOW()
);

-- Final verification query
SELECT 
    'Phase 22: Analytics Views & Performance Implementation' as phase,
    'COMPLETED' as status,
    CONCAT(
        'Created 4 major analytics views, ',
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'environmental_platform' AND INDEX_NAME LIKE 'idx_%'), 
        ' performance indexes, and dashboard optimization system'
    ) as summary,
    NOW() as completed_at;