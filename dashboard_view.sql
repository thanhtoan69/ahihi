-- Dashboard Real-time Metrics View
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
    'Total Users' as metric_name,
    COUNT(*) as metric_value,
    'users' as metric_unit,
    CURDATE() as metric_date
FROM users;
