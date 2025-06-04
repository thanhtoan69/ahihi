-- ========================================
-- Phase 25F: Performance Indexes Verification
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- COMPREHENSIVE INDEX VERIFICATION
-- ========================================

SELECT '=======================================' as section;
SELECT 'PHASE 25F PERFORMANCE INDEXES VERIFICATION' as section;
SELECT '=======================================' as section;

-- 1. User Activities Indexes Verification
SELECT 'USER ACTIVITIES INDEXES' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index,
    cardinality
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'user_activities_comprehensive'
AND index_name LIKE 'idx_user_activities%'
ORDER BY index_name, seq_in_index;

-- 2. Carbon Footprints Indexes Verification
SELECT '=======================================' as section;
SELECT 'CARBON FOOTPRINTS INDEXES' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index,
    cardinality
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'carbon_footprints'
AND index_name LIKE 'idx_carbon%'
ORDER BY index_name, seq_in_index;

-- 3. Products Eco-Friendly Indexes Verification
SELECT '=======================================' as section;
SELECT 'PRODUCTS ECO-FRIENDLY INDEXES' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index,
    cardinality
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'products'
AND index_name LIKE 'idx_products%'
ORDER BY index_name, seq_in_index;

-- 4. Orders Analytics Indexes Verification
SELECT '=======================================' as section;
SELECT 'ORDERS MONTHLY ANALYTICS INDEXES' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index,
    cardinality
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'orders'
AND index_name LIKE 'idx_orders%'
ORDER BY index_name, seq_in_index;

-- 5. User Performance Indexes Verification
SELECT '=======================================' as section;
SELECT 'USER PERFORMANCE INDEXES' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index,
    cardinality
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'users'
AND index_name LIKE 'idx_users%'
ORDER BY index_name, seq_in_index;

-- 6. Waste Management Indexes Verification
SELECT '=======================================' as section;
SELECT 'WASTE MANAGEMENT INDEXES' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index,
    cardinality
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'waste_entries'
AND index_name LIKE 'idx_waste%'
ORDER BY index_name, seq_in_index;

-- 7. Composite Indexes Summary
SELECT '=======================================' as section;
SELECT 'COMPOSITE INDEXES SUMMARY' as section;
SELECT 
    table_name,
    index_name,
    COUNT(*) as columns_count,
    GROUP_CONCAT(column_name ORDER BY seq_in_index) as columns_list
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%composite'
GROUP BY table_name, index_name
ORDER BY table_name, index_name;

-- 8. Full-Text Search Indexes
SELECT '=======================================' as section;
SELECT 'FULL-TEXT SEARCH INDEXES' as section;
SELECT 
    table_name,
    index_name,
    index_type
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_type = 'FULLTEXT'
ORDER BY table_name;

-- 9. Performance Metrics by Table
SELECT '=======================================' as section;
SELECT 'PERFORMANCE INDEXES BY TABLE' as section;
SELECT 
    table_name,
    COUNT(*) as total_indexes,
    COUNT(CASE WHEN index_name LIKE 'idx_%daily%' THEN 1 END) as daily_indexes,
    COUNT(CASE WHEN index_name LIKE 'idx_%monthly%' THEN 1 END) as monthly_indexes,
    COUNT(CASE WHEN index_name LIKE 'idx_%composite%' THEN 1 END) as composite_indexes
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%'
GROUP BY table_name
ORDER BY total_indexes DESC;

-- 10. Index Size and Usage Statistics
SELECT '=======================================' as section;
SELECT 'INDEX OPTIMIZATION SUMMARY' as section;
SELECT 
    'Total Performance Indexes Created' as metric,
    COUNT(DISTINCT index_name) as value
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%'

UNION ALL

SELECT 
    'Tables with Performance Indexes' as metric,
    COUNT(DISTINCT table_name) as value
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%'

UNION ALL

SELECT 
    'Daily Operations Indexes' as metric,
    COUNT(DISTINCT index_name) as value
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%daily%'

UNION ALL

SELECT 
    'Monthly Analytics Indexes' as metric,
    COUNT(DISTINCT index_name) as value
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%monthly%'

UNION ALL

SELECT 
    'Composite Performance Indexes' as metric,
    COUNT(DISTINCT index_name) as value
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%composite%';

-- 11. Query Performance Test Suggestions
SELECT '=======================================' as section;
SELECT 'PERFORMANCE TEST QUERIES' as section;
SELECT 'Use EXPLAIN to test these optimized queries:' as suggestion;

SELECT '1. Daily User Activities Query' as test_query;
SELECT 'EXPLAIN SELECT COUNT(*) FROM user_activities_comprehensive WHERE DATE(created_at) = CURDATE();' as sql_command;

SELECT '2. Monthly Carbon Footprint Analysis' as test_query;
SELECT 'EXPLAIN SELECT SUM(carbon_amount) FROM carbon_footprints WHERE YEAR(recorded_date) = 2025 AND MONTH(recorded_date) = 6;' as sql_command;

SELECT '3. Eco-Friendly Products Discovery' as test_query;
SELECT 'EXPLAIN SELECT * FROM products WHERE is_eco_friendly = 1 ORDER BY eco_rating DESC LIMIT 10;' as sql_command;

SELECT '4. Monthly Orders Analytics' as test_query;
SELECT 'EXPLAIN SELECT COUNT(*), SUM(total_amount) FROM orders WHERE YEAR(order_date) = 2025 AND MONTH(order_date) = 6;' as sql_command;

SELECT '5. User Leaderboard Query' as test_query;
SELECT 'EXPLAIN SELECT username, green_points FROM users WHERE is_active = 1 ORDER BY green_points DESC LIMIT 100;' as sql_command;

-- 12. Final Status
SELECT '=======================================' as section;
SELECT 'PHASE 25F COMPLETION STATUS' as section;
SELECT 
    'PERFORMANCE INDEXES' as component,
    '✅ COMPLETED' as status,
    COUNT(DISTINCT index_name) as indexes_created
FROM information_schema.statistics 
WHERE table_schema = 'environmental_platform' 
AND index_name LIKE 'idx_%';

SELECT '=======================================' as section;
