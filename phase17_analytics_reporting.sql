-- ========================================
-- PHASE 17: ANALYTICS & REPORTING SYSTEM
-- Environmental Platform Database
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. USER ANALYTICS TABLE - Comprehensive Behavior Tracking
-- ========================================

CREATE TABLE user_analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(100),
    
    -- Page/Content Analytics
    page_url VARCHAR(500),
    page_title VARCHAR(255),
    content_type ENUM('article', 'product', 'forum', 'event', 'quiz', 'classification', 'exchange') NOT NULL,
    content_id INT,
    
    -- User Behavior Metrics
    time_spent_seconds INT DEFAULT 0,
    scroll_depth_percentage INT DEFAULT 0,
    click_count INT DEFAULT 0,
    bounce_rate BOOLEAN DEFAULT FALSE,
    conversion_type VARCHAR(50),
    conversion_value DECIMAL(10,2) DEFAULT 0,
    
    -- Environmental Impact Metrics
    carbon_points_earned INT DEFAULT 0,
    green_actions_completed INT DEFAULT 0,
    environmental_score_change INT DEFAULT 0,
    waste_items_classified INT DEFAULT 0,
    
    -- Engagement Metrics
    shares_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    bookmarks_count INT DEFAULT 0,
    
    -- Device & Location Data
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(50),
    operating_system VARCHAR(50),
    screen_resolution VARCHAR(20),
    user_location_city VARCHAR(100),
    user_location_country VARCHAR(100),
    
    -- Traffic Source
    traffic_source ENUM('direct', 'organic', 'social', 'referral', 'email', 'paid') DEFAULT 'direct',
    referrer_url VARCHAR(500),
    campaign_name VARCHAR(100),
    
    -- Temporal Data
    date DATE NOT NULL,
    hour_of_day TINYINT,
    day_of_week TINYINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    -- Indexes for Performance
    INDEX idx_user_date_range (user_id, date),
    INDEX idx_content_analytics (content_type, content_id, date),
    INDEX idx_session_tracking (session_id, created_at),
    INDEX idx_environmental_metrics (carbon_points_earned, green_actions_completed),
    INDEX idx_engagement_metrics (shares_count, likes_count, comments_count),
    INDEX idx_traffic_source (traffic_source, date),
    INDEX idx_device_analytics (device_type, browser, date)
) ENGINE=InnoDB;

-- ========================================
-- 2. PLATFORM METRICS TABLE - KPI Monitoring
-- ========================================

CREATE TABLE platform_metrics (
    metric_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Metric Information
    metric_name VARCHAR(100) NOT NULL,
    metric_category ENUM('user_engagement', 'content_performance', 'environmental_impact', 'revenue', 'technical', 'social') NOT NULL,
    metric_type ENUM('count', 'sum', 'average', 'percentage', 'ratio') NOT NULL,
    
    -- Metric Values
    metric_value DECIMAL(15,4) NOT NULL,
    previous_period_value DECIMAL(15,4) DEFAULT 0,
    change_percentage DECIMAL(8,4) DEFAULT 0,
    target_value DECIMAL(15,4) DEFAULT 0,
    threshold_warning DECIMAL(15,4) DEFAULT 0,
    threshold_critical DECIMAL(15,4) DEFAULT 0,
    
    -- Metric Details
    metric_description TEXT,
    calculation_method TEXT,
    data_source VARCHAR(100),
    
    -- Environmental KPIs
    total_carbon_saved DECIMAL(12,4) DEFAULT 0,
    total_waste_classified INT DEFAULT 0,
    total_green_actions INT DEFAULT 0,
    eco_product_sales DECIMAL(12,2) DEFAULT 0,
    
    -- User Engagement KPIs
    daily_active_users INT DEFAULT 0,
    monthly_active_users INT DEFAULT 0,
    user_retention_rate DECIMAL(5,2) DEFAULT 0,
    average_session_duration INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Content Performance KPIs
    content_views INT DEFAULT 0,
    content_shares INT DEFAULT 0,
    content_engagement_rate DECIMAL(5,2) DEFAULT 0,
    viral_coefficient DECIMAL(8,4) DEFAULT 0,
    
    -- Revenue KPIs
    total_revenue DECIMAL(12,2) DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0,
    customer_lifetime_value DECIMAL(10,2) DEFAULT 0,
    
    -- Technical KPIs
    page_load_time_ms INT DEFAULT 0,
    error_rate DECIMAL(5,2) DEFAULT 0,
    uptime_percentage DECIMAL(5,2) DEFAULT 99.99,
    
    -- Temporal Data
    metric_date DATE NOT NULL,
    metric_hour TINYINT,
    period_type ENUM('hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'daily',
    
    -- Status & Alert
    status ENUM('normal', 'warning', 'critical') DEFAULT 'normal',
    alert_triggered BOOLEAN DEFAULT FALSE,
    alert_message TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for Performance
    INDEX idx_metric_date_category (metric_date, metric_category),
    INDEX idx_metric_type_period (metric_type, period_type),
    INDEX idx_status_alerts (status, alert_triggered),
    INDEX idx_environmental_kpis (total_carbon_saved, total_waste_classified),
    INDEX idx_user_kpis (daily_active_users, monthly_active_users),
    INDEX idx_revenue_kpis (total_revenue, conversion_rate),
    UNIQUE KEY unique_metric_date (metric_name, metric_date, period_type)
) ENGINE=InnoDB;

-- ========================================
-- 3. REAL-TIME DASHBOARD METRICS
-- ========================================

CREATE TABLE dashboard_widgets (
    widget_id INT PRIMARY KEY AUTO_INCREMENT,
    widget_name VARCHAR(100) NOT NULL,
    widget_type ENUM('chart', 'counter', 'gauge', 'table', 'map', 'trend') NOT NULL,
    dashboard_category ENUM('overview', 'environmental', 'users', 'content', 'sales', 'technical') NOT NULL,
    
    -- Widget Configuration
    data_source VARCHAR(100) NOT NULL,
    query_template TEXT NOT NULL,
    refresh_interval_minutes INT DEFAULT 15,
    chart_type VARCHAR(50),
    display_options JSON,
    
    -- Widget Position & Size
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    width_units INT DEFAULT 4,
    height_units INT DEFAULT 3,
    
    -- Access Control
    required_role ENUM('admin', 'moderator', 'analyst', 'user') DEFAULT 'analyst',
    is_public BOOLEAN DEFAULT FALSE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_dashboard_category (dashboard_category, is_active),
    INDEX idx_widget_type (widget_type),
    INDEX idx_refresh_schedule (refresh_interval_minutes, last_updated)
) ENGINE=InnoDB;

-- ========================================
-- 4. AUTOMATED REPORTS CONFIGURATION
-- ========================================

CREATE TABLE report_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    report_type ENUM('user_engagement', 'environmental_impact', 'content_performance', 'revenue', 'operational', 'custom') NOT NULL,
    
    -- Report Configuration
    report_description TEXT,
    sql_query LONGTEXT NOT NULL,
    output_format ENUM('pdf', 'excel', 'csv', 'html', 'json') DEFAULT 'pdf',
    
    -- Scheduling
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'on_demand') NOT NULL,
    schedule_day_of_week TINYINT,
    schedule_day_of_month TINYINT,
    schedule_time TIME DEFAULT '08:00:00',
    
    -- Recipients & Distribution
    recipient_emails JSON,
    recipient_roles JSON,
    auto_send BOOLEAN DEFAULT TRUE,
    
    -- Chart & Visualization Settings
    include_charts BOOLEAN DEFAULT TRUE,
    chart_configurations JSON,
    
    -- Template Settings
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_generated TIMESTAMP NULL,
    next_generation TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_schedule_active (schedule_frequency, is_active, next_generation),
    INDEX idx_report_type (report_type),
    INDEX idx_creator (created_by)
) ENGINE=InnoDB;

-- ========================================
-- 5. REPORT GENERATION HISTORY
-- ========================================

CREATE TABLE report_generation_history (
    generation_id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    
    -- Generation Details
    report_title VARCHAR(255) NOT NULL,
    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,
    
    -- Generation Status
    status ENUM('pending', 'generating', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    progress_percentage INT DEFAULT 0,
    
    -- Output Details
    file_path VARCHAR(500),
    file_size_bytes BIGINT DEFAULT 0,
    output_format VARCHAR(10),
    
    -- Performance Metrics
    generation_time_seconds INT DEFAULT 0,
    data_rows_processed INT DEFAULT 0,
    charts_generated INT DEFAULT 0,
    
    -- Distribution
    sent_to_recipients BOOLEAN DEFAULT FALSE,
    email_sent_count INT DEFAULT 0,
    download_count INT DEFAULT 0,
    
    -- Error Handling
    error_message TEXT,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    
    -- Timestamps
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES report_templates(template_id) ON DELETE CASCADE,
    INDEX idx_template_status (template_id, status),
    INDEX idx_generation_date (created_at),
    INDEX idx_status_pending (status, created_at)
) ENGINE=InnoDB;

-- ========================================
-- 6. USER ENGAGEMENT SUMMARY VIEW
-- ========================================

CREATE VIEW user_engagement_summary AS
SELECT 
    u.user_id,
    u.username,
    u.user_type,
    
    -- Activity Metrics
    COUNT(DISTINCT ua.analytics_id) as total_sessions,
    COALESCE(SUM(ua.time_spent_seconds), 0) as total_time_spent,
    COALESCE(AVG(ua.time_spent_seconds), 0) as avg_session_duration,
    COALESCE(SUM(ua.click_count), 0) as total_clicks,
    COALESCE(AVG(ua.scroll_depth_percentage), 0) as avg_scroll_depth,
    
    -- Engagement Metrics
    COALESCE(SUM(ua.shares_count), 0) as total_shares,
    COALESCE(SUM(ua.likes_count), 0) as total_likes,
    COALESCE(SUM(ua.comments_count), 0) as total_comments,
    COALESCE(SUM(ua.bookmarks_count), 0) as total_bookmarks,
    
    -- Environmental Impact
    COALESCE(SUM(ua.carbon_points_earned), 0) as total_carbon_points,
    COALESCE(SUM(ua.green_actions_completed), 0) as total_green_actions,
    COALESCE(SUM(ua.waste_items_classified), 0) as total_waste_classified,
    
    -- Recent Activity
    MAX(ua.created_at) as last_activity,
    COUNT(DISTINCT DATE(ua.created_at)) as active_days_last_30,
    
    -- User Profile Data
    u.green_points,
    u.total_carbon_saved,
    u.user_level,
    u.join_date
    
FROM users u
LEFT JOIN user_analytics ua ON u.user_id = ua.user_id 
    AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
WHERE u.is_active = TRUE
GROUP BY u.user_id, u.username, u.user_type, u.green_points, u.total_carbon_saved, u.user_level, u.join_date;

-- ========================================
-- 7. CONTENT PERFORMANCE VIEW
-- ========================================

CREATE VIEW content_performance AS
SELECT 
    a.article_id,
    a.title,
    a.slug,
    c.name as category_name,
    u.username as author_name,
    
    -- View Metrics
    a.view_count,
    a.unique_viewers,
    COALESCE(SUM(ua.time_spent_seconds), 0) as total_reading_time,
    COALESCE(AVG(ua.time_spent_seconds), 0) as avg_reading_time,
    COALESCE(AVG(ua.scroll_depth_percentage), 0) as avg_scroll_depth,
    
    -- Engagement Metrics
    a.like_count,
    a.share_count,
    a.comment_count,
    a.bookmark_count,
    
    -- Performance Ratios
    CASE 
        WHEN a.view_count > 0 THEN (a.like_count / a.view_count) * 100 
        ELSE 0 
    END as like_rate,
    CASE 
        WHEN a.view_count > 0 THEN (a.share_count / a.view_count) * 100 
        ELSE 0 
    END as share_rate,
    CASE 
        WHEN a.view_count > 0 THEN (a.comment_count / a.view_count) * 100 
        ELSE 0 
    END as comment_rate,
    
    -- Environmental Impact
    a.carbon_saved_reading,
    a.environmental_impact_score,
    
    -- Publication Data
    a.published_at,
    a.status,
    a.is_featured
    
FROM articles a
JOIN users u ON a.author_id = u.user_id
LEFT JOIN categories c ON a.category_id = c.category_id
LEFT JOIN user_analytics ua ON ua.content_type = 'article' 
    AND ua.content_id = a.article_id
WHERE a.status = 'published'
GROUP BY a.article_id, a.title, a.slug, c.name, u.username, 
         a.view_count, a.unique_viewers, a.like_count, a.share_count, 
         a.comment_count, a.bookmark_count, a.carbon_saved_reading, 
         a.environmental_impact_score, a.published_at, a.status, a.is_featured;

-- ========================================
-- 8. ENVIRONMENTAL IMPACT SUMMARY VIEW
-- ========================================

CREATE VIEW environmental_impact_summary AS
SELECT 
    DATE_FORMAT(ua.date, '%Y-%m') as month,
    
    -- Carbon Impact
    SUM(ua.carbon_points_earned) as total_carbon_points,
    COUNT(DISTINCT ua.user_id) as active_users,
    SUM(ua.green_actions_completed) as total_green_actions,
    SUM(ua.waste_items_classified) as total_waste_classified,
    
    -- User Engagement in Environmental Activities
    AVG(ua.environmental_score_change) as avg_environmental_score_change,
    COUNT(DISTINCT CASE WHEN ua.green_actions_completed > 0 THEN ua.user_id END) as users_with_green_actions,
    
    -- Content Engagement
    SUM(CASE WHEN ua.content_type IN ('article', 'guide') THEN ua.shares_count ELSE 0 END) as environmental_content_shares,
    
    -- Calculated Metrics
    CASE 
        WHEN COUNT(DISTINCT ua.user_id) > 0 
        THEN SUM(ua.carbon_points_earned) / COUNT(DISTINCT ua.user_id) 
        ELSE 0 
    END as avg_carbon_points_per_user,
    
    CASE 
        WHEN COUNT(DISTINCT ua.user_id) > 0 
        THEN SUM(ua.green_actions_completed) / COUNT(DISTINCT ua.user_id) 
        ELSE 0 
    END as avg_green_actions_per_user
    
FROM user_analytics ua
WHERE ua.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(ua.date, '%Y-%m')
ORDER BY month DESC;

-- ========================================
-- 9. INSERT SAMPLE DATA
-- ========================================

-- Sample Dashboard Widgets
INSERT INTO dashboard_widgets (widget_name, widget_type, dashboard_category, data_source, query_template, chart_type) VALUES
('Daily Active Users', 'counter', 'overview', 'user_analytics', 'SELECT COUNT(DISTINCT user_id) FROM user_analytics WHERE date = CURDATE()', 'number'),
('Carbon Points Earned Today', 'counter', 'environmental', 'user_analytics', 'SELECT SUM(carbon_points_earned) FROM user_analytics WHERE date = CURDATE()', 'number'),
('Weekly User Engagement', 'chart', 'users', 'user_analytics', 'SELECT DATE(date) as day, COUNT(DISTINCT user_id) as users FROM user_analytics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(date)', 'line'),
('Top Content Performance', 'table', 'content', 'content_performance', 'SELECT title, view_count, like_count, share_count FROM content_performance ORDER BY view_count DESC LIMIT 10', 'table'),
('Environmental Impact Trend', 'chart', 'environmental', 'user_analytics', 'SELECT DATE(date) as day, SUM(carbon_points_earned) as carbon_points FROM user_analytics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(date)', 'area');

-- Sample Report Templates
INSERT INTO report_templates (template_name, report_type, report_description, sql_query, schedule_frequency, recipient_emails, created_by) VALUES
('Daily User Engagement Report', 'user_engagement', 'Daily summary of user activities and engagement metrics', 
'SELECT DATE(created_at) as date, COUNT(DISTINCT user_id) as active_users, AVG(time_spent_seconds) as avg_session_time FROM user_analytics WHERE date = CURDATE() - INTERVAL 1 DAY', 
'daily', '["admin@ecoplatform.com"]', 1),

('Weekly Environmental Impact Report', 'environmental_impact', 'Weekly environmental impact and carbon savings summary',
'SELECT DATE_FORMAT(date, "%Y-%u") as week, SUM(carbon_points_earned) as total_carbon_points, SUM(green_actions_completed) as green_actions FROM user_analytics WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE_FORMAT(date, "%Y-%u")',
'weekly', '["environmental@ecoplatform.com", "admin@ecoplatform.com"]', 1),

('Monthly Content Performance Report', 'content_performance', 'Monthly analysis of content engagement and performance',
'SELECT title, view_count, like_count, share_count, comment_count FROM content_performance WHERE published_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY view_count DESC',
'monthly', '["content@ecoplatform.com"]', 1);

-- Sample Platform Metrics for Today
INSERT INTO platform_metrics (metric_name, metric_category, metric_type, metric_value, metric_date, metric_description) VALUES
('Daily Active Users', 'user_engagement', 'count', 150, CURDATE(), 'Number of unique users active today'),
('Total Carbon Points Earned', 'environmental_impact', 'sum', 2450, CURDATE(), 'Total carbon points earned by all users today'),
('Average Session Duration', 'user_engagement', 'average', 320, CURDATE(), 'Average session duration in seconds'),
('Content Shares Today', 'content_performance', 'count', 85, CURDATE(), 'Total number of content shares today'),
('Waste Items Classified', 'environmental_impact', 'count', 127, CURDATE(), 'Number of waste items classified by AI today'),
('User Retention Rate', 'user_engagement', 'percentage', 78.5, CURDATE(), '7-day user retention rate'),
('Page Load Time', 'technical', 'average', 1250, CURDATE(), 'Average page load time in milliseconds'),
('Conversion Rate', 'revenue', 'percentage', 3.2, CURDATE(), 'Daily conversion rate for eco-products');

-- ========================================
-- 10. SUCCESS MESSAGE
-- ========================================

SELECT 'Phase 17: Analytics & Reporting System created successfully!' as Status,
       'Tables: user_analytics, platform_metrics, dashboard_widgets, report_templates, report_generation_history' as Tables_Created,
       'Views: user_engagement_summary, content_performance, environmental_impact_summary' as Views_Created;
