-- Phase 23: Comprehensive Testing Script
-- Environmental Platform Database - Stored Procedures Testing
-- Testing all 6 business logic procedures

USE environmental_platform;

-- ===============================
-- PHASE 23 STORED PROCEDURES TESTING
-- ===============================

SELECT '========================================' as separator;
SELECT 'PHASE 23: STORED PROCEDURES TESTING' as title;
SELECT '========================================' as separator;

-- Test 1: Update a user's level
SELECT 'TEST 1: UpdateUserLevel Procedure' as test_name;
SELECT '=====================================' as separator;

-- Show user before update
SELECT 'User before UpdateUserLevel:' as status;
SELECT id, name, green_points, user_level, experience_points 
FROM users 
WHERE id = 1 LIMIT 1;

-- Call UpdateUserLevel
CALL UpdateUserLevel(1);

-- Show user after update
SELECT 'User after UpdateUserLevel:' as status;
SELECT id, name, green_points, user_level, experience_points 
FROM users 
WHERE id = 1 LIMIT 1;

SELECT '' as separator;

-- Test 2: Process waste classification
SELECT 'TEST 2: ProcessWasteClassification Procedure' as test_name;
SELECT '==============================================' as separator;

-- Show user points before
SELECT 'User points before waste classification:' as status;
SELECT id, name, green_points FROM users WHERE id = 1;

-- Call ProcessWasteClassification with high accuracy
CALL ProcessWasteClassification(1, 'plastic', 95.5, 'plastic_bottle');

-- Show user points after
SELECT 'User points after waste classification:' as status;
SELECT id, name, green_points FROM users WHERE id = 1;

SELECT '' as separator;

-- Test 3: Update user streaks
SELECT 'TEST 3: UpdateUserStreaks Procedure' as test_name;
SELECT '=====================================' as separator;

-- Show user streaks before
SELECT 'User streaks before update:' as status;
SELECT id, name, login_streak, longest_streak, last_login 
FROM users 
WHERE id = 1;

-- Call UpdateUserStreaks
CALL UpdateUserStreaks(1);

-- Show user streaks after
SELECT 'User streaks after update:' as status;
SELECT id, name, login_streak, longest_streak, last_login 
FROM users 
WHERE id = 1;

SELECT '' as separator;

-- Test 4: Calculate points and rewards
SELECT 'TEST 4: CalculatePointsAndRewards Procedure' as test_name;
SELECT '=============================================' as separator;

-- Show user points before
SELECT 'User points before rewards calculation:' as status;
SELECT id, name, green_points, experience_points FROM users WHERE id = 1;

-- Call CalculatePointsAndRewards for recycling activity
CALL CalculatePointsAndRewards(1, 'recycling', 2.5);

-- Show user points after
SELECT 'User points after rewards calculation:' as status;
SELECT id, name, green_points, experience_points FROM users WHERE id = 1;

SELECT '' as separator;

-- Test 5: Check achievements
SELECT 'TEST 5: CheckAchievements Procedure' as test_name;
SELECT '====================================' as separator;

-- Show user achievements before
SELECT 'User achievements before check:' as status;
SELECT COUNT(*) as total_achievements 
FROM user_achievements 
WHERE user_id = 1;

-- Call CheckAchievements
CALL CheckAchievements(1);

-- Show user achievements after
SELECT 'User achievements after check:' as status;
SELECT COUNT(*) as total_achievements 
FROM user_achievements 
WHERE user_id = 1;

-- Show earned achievements
SELECT 'Earned achievements:' as status;
SELECT ae.achievement_name, ae.title_en, ua.earned_at
FROM user_achievements ua
JOIN achievements_enhanced ae ON ua.achievement_id = ae.achievement_id
WHERE ua.user_id = 1
ORDER BY ua.earned_at DESC;

SELECT '' as separator;

-- Test 6: Generate daily analytics
SELECT 'TEST 6: GenerateDailyAnalytics Procedure' as test_name;
SELECT '==========================================' as separator;

-- Check analytics before
SELECT 'Analytics records before generation:' as status;
SELECT COUNT(*) as total_records FROM daily_analytics_summary;

-- Call GenerateDailyAnalytics
CALL GenerateDailyAnalytics();

-- Check analytics after
SELECT 'Analytics records after generation:' as status;
SELECT COUNT(*) as total_records FROM daily_analytics_summary;

-- Show latest analytics
SELECT 'Latest analytics summary:' as status;
SELECT * FROM daily_analytics_summary 
ORDER BY date_recorded DESC 
LIMIT 1;

SELECT '' as separator;

-- ===============================
-- COMPREHENSIVE TESTING SUMMARY
-- ===============================

SELECT '========================================' as separator;
SELECT 'TESTING SUMMARY & VERIFICATION' as title;
SELECT '========================================' as separator;

-- Verify all procedures exist
SELECT 'All Stored Procedures Status:' as status;
SELECT Name as procedure_name, Created as created_date
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND Name IN ('UpdateUserLevel', 'ProcessWasteClassification', 'CheckAchievements', 'GenerateDailyAnalytics', 'UpdateUserStreaks', 'CalculatePointsAndRewards')
ORDER BY Name;

-- Check if all 6 procedures are present
SELECT 'Procedure Count Verification:' as status;
SELECT COUNT(*) as installed_procedures, 
       CASE WHEN COUNT(*) = 6 THEN 'SUCCESS: All 6 procedures installed' 
            ELSE 'ERROR: Missing procedures' 
       END as verification_status
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'environmental_platform' 
AND ROUTINE_TYPE = 'PROCEDURE'
AND Name IN ('UpdateUserLevel', 'ProcessWasteClassification', 'CheckAchievements', 'GenerateDailyAnalytics', 'UpdateUserStreaks', 'CalculatePointsAndRewards');

-- Final user state
SELECT 'Final User State After All Tests:' as status;
SELECT id, name, green_points, user_level, experience_points, login_streak, total_carbon_saved
FROM users 
WHERE id = 1;

-- Test completion
SELECT '========================================' as separator;
SELECT 'PHASE 23 TESTING COMPLETED SUCCESSFULLY!' as completion_status;
SELECT '========================================' as separator;
