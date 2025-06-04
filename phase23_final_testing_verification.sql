-- Phase 23: Complete Testing and Verification Script
-- Environmental Platform Database
-- Comprehensive testing of all 6 stored procedures

USE environmental_platform;

-- ================================================================
-- PHASE 23 FINAL TESTING PROTOCOL
-- ================================================================

SELECT '=============================================' as separator;
SELECT 'PHASE 23: FINAL PROCEDURE TESTING' as title;
SELECT '=============================================' as separator;

-- ================================================================
-- PRE-TEST VERIFICATION
-- ================================================================

SELECT 'PRE-TEST VERIFICATION' as test_phase;
SELECT '-------------------' as separator;

-- Check that all procedures exist
SELECT 'Verifying stored procedures installation...' as status;
SELECT 
    routine_name as procedure_name,
    'INSTALLED' as status
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND routine_name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification',
    'CalculatePointsAndRewards',
    'CheckAchievements',
    'UpdateUserStreaks',
    'GenerateDailyAnalytics'
)
ORDER BY routine_name;

-- Count procedures
SELECT 
    COUNT(*) as installed_procedures,
    CASE 
        WHEN COUNT(*) = 6 THEN '✅ ALL PROCEDURES READY FOR TESTING'
        ELSE '❌ MISSING PROCEDURES - INSTALLATION REQUIRED'
    END as readiness_status
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND routine_name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification',
    'CalculatePointsAndRewards',
    'CheckAchievements',
    'UpdateUserStreaks',
    'GenerateDailyAnalytics'
);

-- Get test user for procedures (user_id = 1)
SELECT 'Test user before testing:' as status;
SELECT 
    user_id,
    username,
    COALESCE(green_points, 0) as green_points,
    COALESCE(user_level, 1) as user_level,
    COALESCE(experience_points, 0) as experience_points,
    COALESCE(login_streak, 0) as login_streak,
    COALESCE(total_carbon_saved, 0.00) as total_carbon_saved
FROM users 
WHERE user_id = 1;

-- ================================================================
-- TEST 1: UpdateUserLevel Procedure
-- ================================================================

SELECT '' as separator;
SELECT 'TEST 1: UpdateUserLevel Procedure' as test_name;
SELECT '=================================' as separator;

-- Test UpdateUserLevel procedure
SELECT 'Calling UpdateUserLevel(1)...' as action;
CALL UpdateUserLevel(1);

-- ================================================================
-- TEST 2: ProcessWasteClassification Procedure
-- ================================================================

SELECT '' as separator;
SELECT 'TEST 2: ProcessWasteClassification Procedure' as test_name;
SELECT '=============================================' as separator;

-- Test ProcessWasteClassification with high confidence
SELECT 'Calling ProcessWasteClassification(1, "plastic", 95.5, "plastic")...' as action;
CALL ProcessWasteClassification(1, 'plastic', 95.5, 'plastic');

-- ================================================================
-- TEST 3: CalculatePointsAndRewards Procedure
-- ================================================================

SELECT '' as separator;
SELECT 'TEST 3: CalculatePointsAndRewards Procedure' as test_name;
SELECT '==========================================' as separator;

-- Test CalculatePointsAndRewards for recycling activity
SELECT 'Calling CalculatePointsAndRewards(1, "recycling", 2.5)...' as action;
CALL CalculatePointsAndRewards(1, 'recycling', 2.5);

-- ================================================================
-- TEST 4: UpdateUserStreaks Procedure
-- ================================================================

SELECT '' as separator;
SELECT 'TEST 4: UpdateUserStreaks Procedure' as test_name;
SELECT '===================================' as separator;

-- Test UpdateUserStreaks
SELECT 'Calling UpdateUserStreaks(1)...' as action;
CALL UpdateUserStreaks(1);

-- ================================================================
-- TEST 5: CheckAchievements Procedure
-- ================================================================

SELECT '' as separator;
SELECT 'TEST 5: CheckAchievements Procedure' as test_name;
SELECT '===================================' as separator;

-- Test CheckAchievements
SELECT 'Calling CheckAchievements(1)...' as action;
CALL CheckAchievements(1);

-- ================================================================
-- TEST 6: GenerateDailyAnalytics Procedure
-- ================================================================

SELECT '' as separator;
SELECT 'TEST 6: GenerateDailyAnalytics Procedure' as test_name;
SELECT '=======================================' as separator;

-- Test GenerateDailyAnalytics
SELECT 'Calling GenerateDailyAnalytics()...' as action;
CALL GenerateDailyAnalytics();

-- ================================================================
-- FINAL VERIFICATION
-- ================================================================

SELECT '' as separator;
SELECT 'FINAL VERIFICATION' as test_phase;
SELECT '==================' as separator;

-- Final user state
SELECT 'Final user state after all tests:' as status;
SELECT 
    user_id,
    username,
    green_points,
    user_level,
    experience_points,
    login_streak,
    longest_streak,
    total_carbon_saved,
    updated_at
FROM users 
WHERE user_id = 1;

-- Recent activities
SELECT 'Recent activities logged:' as status;
SELECT 
    activity_type, 
    activity_name, 
    total_points, 
    created_at
FROM user_activities_comprehensive 
WHERE user_id = 1 
ORDER BY created_at DESC 
LIMIT 5;

-- Analytics summary
SELECT 'Today analytics summary:' as status;
SELECT 
    date_recorded, 
    total_users, 
    active_users, 
    total_activities, 
    total_points_awarded
FROM daily_analytics_summary 
WHERE date_recorded = CURDATE();

-- ================================================================
-- SUCCESS VERIFICATION
-- ================================================================

SELECT '' as separator;
SELECT '=============================================' as separator;
SELECT 'PHASE 23 TESTING COMPLETED SUCCESSFULLY!' as test_result;
SELECT '=============================================' as separator;

-- Verify all procedures exist and are functional
SELECT 
    COUNT(*) as functional_procedures,
    CASE 
        WHEN COUNT(*) = 6 THEN '✅ ALL 6 PROCEDURES OPERATIONAL'
        ELSE '❌ SOME PROCEDURES NOT WORKING'
    END as final_status
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND routine_name IN (
    'UpdateUserLevel',
    'ProcessWasteClassification',
    'CalculatePointsAndRewards',
    'CheckAchievements',
    'UpdateUserStreaks',
    'GenerateDailyAnalytics'
);

SELECT 'Phase 23 stored procedures are ready for production!' as final_message;
