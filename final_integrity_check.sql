-- FINAL VERIFICATION SCRIPT
-- Environmental Platform Database Integrity Check

USE environmental_platform;

-- ========================================
-- DATABASE OVERVIEW
-- ========================================

SELECT 'DATABASE OVERVIEW' as section;
SELECT 
    'environmental_platform' as database_name,
    COUNT(*) as total_tables,
    NOW() as check_date
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';

-- ========================================
-- PHASE 13 VERIFICATION
-- ========================================

SELECT 'PHASE 13 - ITEM EXCHANGE SYSTEM' as section;

-- Check exchange tables exist
SELECT table_name as exchange_tables 
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
AND table_name LIKE 'exchange%'
ORDER BY table_name;

-- Check sample data in exchange categories
SELECT 'Exchange Categories Sample:' as info;
SELECT category_id, category_name, eco_impact_score 
FROM exchange_categories 
LIMIT 5;

-- ========================================
-- PHASE 14 VERIFICATION  
-- ========================================

SELECT 'PHASE 14 - DONATION SYSTEM' as section;

-- Check donation tables exist
SELECT table_name as donation_tables
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
AND table_name LIKE 'donation%'
ORDER BY table_name;

-- Check sample data in donation organizations
SELECT 'Donation Organizations Sample:' as info;
SELECT organization_id, organization_name, organization_type 
FROM donation_organizations 
LIMIT 3;

-- Check sample data in donation campaigns
SELECT 'Donation Campaigns Sample:' as info;
SELECT campaign_id, LEFT(campaign_name, 40) as campaign_name, target_amount
FROM donation_campaigns 
LIMIT 3;

-- ========================================
-- FOREIGN KEY INTEGRITY CHECK
-- ========================================

SELECT 'FOREIGN KEY CONSTRAINTS' as section;

-- Count foreign key constraints
SELECT 
    COUNT(*) as total_foreign_keys
FROM information_schema.key_column_usage 
WHERE table_schema = 'environmental_platform' 
AND referenced_table_name IS NOT NULL;

-- List some key foreign key relationships
SELECT 
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage 
WHERE table_schema = 'environmental_platform' 
AND referenced_table_name IS NOT NULL
AND (table_name LIKE 'exchange%' OR table_name LIKE 'donation%')
ORDER BY table_name
LIMIT 10;

-- ========================================
-- DATA INTEGRITY SAMPLES
-- ========================================

SELECT 'DATA INTEGRITY SAMPLES' as section;

-- Check user connections
SELECT 'Users with green points:' as info;
SELECT user_id, username, green_points 
FROM users 
WHERE green_points > 0 
LIMIT 5;

-- Check environmental data
SELECT 'Environmental categories:' as info;
SELECT category_id, category_name 
FROM categories 
WHERE category_name LIKE '%môi trường%' OR category_name LIKE '%xanh%'
LIMIT 3;

-- ========================================
-- FINAL STATUS SUMMARY
-- ========================================

SELECT 'FINAL STATUS SUMMARY' as section;

SELECT 
    'Phase 1-12 Tables' as phase,
    42 as expected_tables,
    (SELECT COUNT(*) FROM information_schema.tables 
     WHERE table_schema = 'environmental_platform' 
     AND table_name NOT LIKE 'exchange%' 
     AND table_name NOT LIKE 'donation%') as actual_tables,
    CASE WHEN (SELECT COUNT(*) FROM information_schema.tables 
               WHERE table_schema = 'environmental_platform' 
               AND table_name NOT LIKE 'exchange%' 
               AND table_name NOT LIKE 'donation%') = 42 
         THEN '✅ PASS' 
         ELSE '❌ FAIL' 
    END as status

UNION ALL

SELECT 
    'Phase 13 Tables' as phase,
    6 as expected_tables,
    (SELECT COUNT(*) FROM information_schema.tables 
     WHERE table_schema = 'environmental_platform' 
     AND table_name LIKE 'exchange%') as actual_tables,
    CASE WHEN (SELECT COUNT(*) FROM information_schema.tables 
               WHERE table_schema = 'environmental_platform' 
               AND table_name LIKE 'exchange%') = 6 
         THEN '✅ PASS' 
         ELSE '❌ FAIL' 
    END as status

UNION ALL

SELECT 
    'Phase 14 Tables' as phase,
    5 as expected_tables,
    (SELECT COUNT(*) FROM information_schema.tables 
     WHERE table_schema = 'environmental_platform' 
     AND table_name LIKE 'donation%') as actual_tables,
    CASE WHEN (SELECT COUNT(*) FROM information_schema.tables 
               WHERE table_schema = 'environmental_platform' 
               AND table_name LIKE 'donation%') = 5 
         THEN '✅ PASS' 
         ELSE '❌ FAIL' 
    END as status

UNION ALL

SELECT 
    'Total Database' as phase,
    53 as expected_tables,
    (SELECT COUNT(*) FROM information_schema.tables 
     WHERE table_schema = 'environmental_platform') as actual_tables,
    CASE WHEN (SELECT COUNT(*) FROM information_schema.tables 
               WHERE table_schema = 'environmental_platform') = 53 
         THEN '🎉 COMPLETE' 
         ELSE '❌ INCOMPLETE' 
    END as status;
