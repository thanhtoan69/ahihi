-- PHASE 17: Analytics & Reporting System (Simplified)
USE environmental_platform;

-- Clean up test table
DROP TABLE IF EXISTS user_analytics_test;

-- ========================================
-- 1. USER ANALYTICS TABLE
-- ========================================

CREATE TABLE user_analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(100),
    
    -- Content Analytics
    content_type ENUM('article', 'product', 'forum', 'event', 'quiz', 'classification', 'exchange') NOT NULL,
    content_id INT,
    page_url VARCHAR(500),
    
    -- Behavior Metrics
    time_spent_seconds INT DEFAULT 0,
    scroll_depth_percentage INT DEFAULT 0,
    click_count INT DEFAULT 0,
    bounce_rate BOOLEAN DEFAULT FALSE,
    
    -- Environmental Metrics
    carbon_points_earned INT DEFAULT 0,
    green_actions_completed INT DEFAULT 0,
    waste_items_classified INT DEFAULT 0,
    
    -- Engagement Metrics
    shares_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    
    -- Device Data
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(50),
    
    -- Traffic Source
    traffic_source ENUM('direct', 'organic', 'social', 'referral', 'email') DEFAULT 'direct',
    
    -- Date Information
    date DATE NOT NULL,
    hour_of_day TINYINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_date_range (user_id, date),
    INDEX idx_content_analytics (content_type, content_id, date),
    INDEX idx_environmental_metrics (carbon_points_earned, green_actions_completed)
) ENGINE=InnoDB;

-- ========================================
-- 2. PLATFORM METRICS TABLE
-- ========================================

CREATE TABLE platform_metrics (
    metric_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Metric Information
    metric_name VARCHAR(100) NOT NULL,
    metric_category ENUM('user_engagement', 'content_performance', 'environmental_impact', 'revenue', 'technical') NOT NULL,
    metric_type ENUM('count', 'sum', 'average', 'percentage') NOT NULL,
    
    -- Metric Values
    metric_value DECIMAL(15,4) NOT NULL,
    previous_period_value DECIMAL(15,4) DEFAULT 0,
    target_value DECIMAL(15,4) DEFAULT 0,
    
    -- Environmental KPIs
    total_carbon_saved DECIMAL(12,4) DEFAULT 0,
    total_waste_classified INT DEFAULT 0,
    total_green_actions INT DEFAULT 0,
    
    -- User Engagement KPIs
    daily_active_users INT DEFAULT 0,
    monthly_active_users INT DEFAULT 0,
    average_session_duration INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Content Performance KPIs
    content_views INT DEFAULT 0,
    content_shares INT DEFAULT 0,
    content_engagement_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Revenue KPIs
    total_revenue DECIMAL(12,2) DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Temporal Data
    metric_date DATE NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_metric_date_category (metric_date, metric_category),
    INDEX idx_environmental_kpis (total_carbon_saved, total_waste_classified),
    UNIQUE KEY unique_metric_date (metric_name, metric_date, period_type)
) ENGINE=InnoDB;

-- ========================================
-- 3. DASHBOARD WIDGETS TABLE
-- ========================================

CREATE TABLE dashboard_widgets (
    widget_id INT PRIMARY KEY AUTO_INCREMENT,
    widget_name VARCHAR(100) NOT NULL,
    widget_type ENUM('chart', 'counter', 'gauge', 'table') NOT NULL,
    dashboard_category ENUM('overview', 'environmental', 'users', 'content', 'sales') NOT NULL,
    
    -- Widget Configuration
    data_source VARCHAR(100) NOT NULL,
    query_template TEXT NOT NULL,
    refresh_interval_minutes INT DEFAULT 15,
    
    -- Access Control
    required_role ENUM('admin', 'moderator', 'analyst', 'user') DEFAULT 'analyst',
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_dashboard_category (dashboard_category, is_active)
) ENGINE=InnoDB;

-- ========================================
-- 4. REPORT TEMPLATES TABLE
-- ========================================

CREATE TABLE report_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    report_type ENUM('user_engagement', 'environmental_impact', 'content_performance', 'revenue', 'operational') NOT NULL,
    
    -- Report Configuration
    report_description TEXT,
    sql_query LONGTEXT NOT NULL,
    output_format ENUM('pdf', 'excel', 'csv', 'html') DEFAULT 'pdf',
    
    -- Scheduling
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'on_demand') NOT NULL,
    schedule_time TIME DEFAULT '08:00:00',
    
    -- Recipients
    recipient_emails JSON,
    auto_send BOOLEAN DEFAULT TRUE,
    
    -- Template Settings
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_generated TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_schedule_active (schedule_frequency, is_active),
    INDEX idx_report_type (report_type)
) ENGINE=InnoDB;

-- ========================================
-- 5. REPORT GENERATION HISTORY TABLE
-- ========================================

CREATE TABLE report_generation_history (
    generation_id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    
    -- Generation Details
    report_title VARCHAR(255) NOT NULL,
    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,
    
    -- Generation Status
    status ENUM('pending', 'generating', 'completed', 'failed') DEFAULT 'pending',
    
    -- Output Details
    file_path VARCHAR(500),
    file_size_bytes BIGINT DEFAULT 0,
    
    -- Performance
    generation_time_seconds INT DEFAULT 0,
    
    -- Distribution
    sent_to_recipients BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES report_templates(template_id) ON DELETE CASCADE,
    INDEX idx_template_status (template_id, status)
) ENGINE=InnoDB;

-- ========================================
-- 6. INSERT SAMPLE DATA
-- ========================================

-- Sample Analytics Data
INSERT INTO user_analytics (user_id, content_type, content_id, time_spent_seconds, carbon_points_earned, green_actions_completed, date) VALUES
(1, 'article', 1, 300, 15, 2, CURDATE()),
(2, 'classification', 1, 180, 25, 1, CURDATE()),
(1, 'product', 1, 120, 5, 0, CURDATE());

-- Sample Platform Metrics
INSERT INTO platform_metrics (metric_name, metric_category, metric_type, metric_value, metric_date, daily_active_users, total_carbon_saved) VALUES
('Daily Active Users', 'user_engagement', 'count', 150, CURDATE(), 150, 0),
('Carbon Points Earned', 'environmental_impact', 'sum', 2450, CURDATE(), 0, 245.0),
('Average Session Time', 'user_engagement', 'average', 320, CURDATE(), 0, 0);

-- Sample Dashboard Widgets
INSERT INTO dashboard_widgets (widget_name, widget_type, dashboard_category, data_source, query_template) VALUES
('Daily Users', 'counter', 'overview', 'user_analytics', 'SELECT COUNT(DISTINCT user_id) FROM user_analytics WHERE date = CURDATE()'),
('Carbon Points', 'counter', 'environmental', 'user_analytics', 'SELECT SUM(carbon_points_earned) FROM user_analytics WHERE date = CURDATE()'),
('User Activity', 'chart', 'users', 'user_analytics', 'SELECT date, COUNT(DISTINCT user_id) FROM user_analytics GROUP BY date');

-- Sample Report Template
INSERT INTO report_templates (template_name, report_type, report_description, sql_query, schedule_frequency, recipient_emails, created_by) VALUES
('Daily Engagement', 'user_engagement', 'Daily user engagement summary', 
'SELECT COUNT(DISTINCT user_id) as users, AVG(time_spent_seconds) as avg_time FROM user_analytics WHERE date = CURDATE()', 
'daily', '["admin@ecoplatform.com"]', 1);

SELECT 'Phase 17: Analytics & Reporting created successfully!' as Status;
