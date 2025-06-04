-- ========================================
-- PHASE 17: ANALYTICS & REPORTING - FINAL VERIFICATION
-- ========================================

USE environmental_platform;

-- 1. Verify all new tables exist
SELECT 'ANALYTICS TABLES VERIFICATION' as section;
SELECT table_name, table_rows, data_length 
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
AND table_name IN ('user_analytics', 'platform_metrics', 'dashboard_widgets', 'report_templates', 'report_generation_history')
ORDER BY table_name;

-- 2. Verify table count increase
SELECT 'DATABASE STATUS' as section;
SELECT COUNT(*) as total_tables, 'Target: 66' as expected
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';

-- 3. Test user analytics data
SELECT 'USER ANALYTICS DATA' as section;
SELECT 
    ua.user_id,
    u.username,
    ua.content_type,
    ua.time_spent_seconds,
    ua.carbon_points_earned,
    ua.date
FROM user_analytics ua
JOIN users u ON ua.user_id = u.user_id
ORDER BY ua.created_at DESC;

-- 4. Test platform metrics
SELECT 'PLATFORM METRICS' as section;
SELECT 
    metric_name,
    metric_category,
    metric_value,
    daily_active_users,
    total_carbon_saved,
    metric_date
FROM platform_metrics
ORDER BY created_at DESC;

-- 5. Test dashboard widgets configuration
SELECT 'DASHBOARD WIDGETS' as section;
SELECT 
    widget_name,
    widget_type,
    dashboard_category,
    data_source,
    is_active
FROM dashboard_widgets
ORDER BY dashboard_category, widget_name;

-- 6. Test report templates
SELECT 'REPORT TEMPLATES' as section;
SELECT 
    template_name,
    report_type,
    schedule_frequency,
    is_active,
    created_by
FROM report_templates
ORDER BY report_type;

-- 7. Test user engagement summary view
SELECT 'USER ENGAGEMENT SUMMARY' as section;
SELECT 
    username,
    total_sessions,
    total_time_spent,
    total_carbon_points,
    last_activity
FROM user_engagement_summary
ORDER BY total_carbon_points DESC;

-- 8. Test environmental impact summary view
SELECT 'ENVIRONMENTAL IMPACT SUMMARY' as section;
SELECT 
    month,
    total_carbon_points,
    active_users,
    avg_carbon_per_user
FROM environmental_impact_summary
ORDER BY month DESC;

-- 9. Analytics performance test
SELECT 'ANALYTICS PERFORMANCE TEST' as section;
SELECT 
    'User Analytics' as table_name,
    COUNT(*) as record_count,
    AVG(time_spent_seconds) as avg_session_time,
    SUM(carbon_points_earned) as total_carbon_points
FROM user_analytics
UNION ALL
SELECT 
    'Platform Metrics' as table_name,
    COUNT(*) as record_count,
    AVG(metric_value) as avg_metric_value,
    SUM(total_carbon_saved) as total_carbon_saved
FROM platform_metrics;

-- 10. Real-time analytics simulation
SELECT 'REAL-TIME ANALYTICS SIMULATION' as section;
SELECT 
    CURDATE() as report_date,
    (SELECT COUNT(DISTINCT user_id) FROM user_analytics WHERE date = CURDATE()) as daily_active_users,
    (SELECT SUM(carbon_points_earned) FROM user_analytics WHERE date = CURDATE()) as daily_carbon_points,
    (SELECT AVG(time_spent_seconds) FROM user_analytics WHERE date = CURDATE()) as avg_session_duration,
    (SELECT COUNT(*) FROM dashboard_widgets WHERE is_active = TRUE) as active_widgets,
    (SELECT COUNT(*) FROM report_templates WHERE is_active = TRUE) as active_report_templates;

-- 11. System readiness check
SELECT 'SYSTEM READINESS CHECK' as section;
SELECT 
    'Analytics Infrastructure' as component,
    CASE WHEN EXISTS(SELECT 1 FROM user_analytics) THEN 'READY' ELSE 'NOT READY' END as status
UNION ALL
SELECT 
    'Dashboard System' as component,
    CASE WHEN EXISTS(SELECT 1 FROM dashboard_widgets) THEN 'READY' ELSE 'NOT READY' END as status
UNION ALL
SELECT 
    'Reporting Engine' as component,
    CASE WHEN EXISTS(SELECT 1 FROM report_templates) THEN 'READY' ELSE 'NOT READY' END as status
UNION ALL
SELECT 
    'Environmental Tracking' as component,
    CASE WHEN EXISTS(SELECT 1 FROM user_analytics WHERE carbon_points_earned > 0) THEN 'READY' ELSE 'NOT READY' END as status;

SELECT 'Phase 17 Analytics & Reporting System - Verification Complete!' as final_status;
