-- PHASE 19: FINAL VERIFICATION SCRIPT
-- Environmental Platform Database - Achievements & Gamification System
-- ========================================

SELECT '=== PHASE 19: ACHIEVEMENTS & GAMIFICATION VERIFICATION ===' as verification_report;

-- 1. Database Overview
SELECT 'DATABASE OVERVIEW:' as section;
SELECT 
    'Total Tables' as metric,
    COUNT(*) as value
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';

-- 2. Gamification Tables Verification
SELECT 'GAMIFICATION TABLES:' as section;
SELECT 
    table_name,
    CASE 
        WHEN table_name IN (
            'achievements_enhanced', 'user_achievements_enhanced', 
            'leaderboards_enhanced', 'leaderboard_entries_enhanced',
            'badges_system', 'user_badges_enhanced',
            'user_streaks_gamification', 'seasonal_challenges', 
            'challenge_participation'
        ) THEN '✅ CREATED'
        ELSE '❌ MISSING'
    END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
    AND table_name IN (
        'achievements_enhanced', 'user_achievements_enhanced', 
        'leaderboards_enhanced', 'leaderboard_entries_enhanced',
        'badges_system', 'user_badges_enhanced',
        'user_streaks_gamification', 'seasonal_challenges', 
        'challenge_participation'
    )
ORDER BY table_name;

-- 3. Data Population Status
SELECT 'DATA POPULATION STATUS:' as section;
SELECT 'Achievement Categories' as table_name, COUNT(*) as records FROM achievement_categories
UNION ALL
SELECT 'Achievements', COUNT(*) FROM achievements_enhanced
UNION ALL
SELECT 'Badges', COUNT(*) FROM badges_system  
UNION ALL
SELECT 'Leaderboards', COUNT(*) FROM leaderboards_enhanced
UNION ALL
SELECT 'User Streaks', COUNT(*) FROM user_streaks_gamification
UNION ALL
SELECT 'Seasonal Challenges', COUNT(*) FROM seasonal_challenges
UNION ALL
SELECT 'Challenge Participation', COUNT(*) FROM challenge_participation;

-- 4. Table Structure Verification
SELECT 'TABLE STRUCTURE VERIFICATION:' as section;

-- Check achievements_enhanced structure
SELECT 'achievements_enhanced columns:' as info, COUNT(*) as column_count
FROM information_schema.columns 
WHERE table_schema = 'environmental_platform' 
    AND table_name = 'achievements_enhanced';

-- Check badges_system structure  
SELECT 'badges_system columns:' as info, COUNT(*) as column_count
FROM information_schema.columns 
WHERE table_schema = 'environmental_platform' 
    AND table_name = 'badges_system';

-- Check leaderboards_enhanced structure
SELECT 'leaderboards_enhanced columns:' as info, COUNT(*) as column_count
FROM information_schema.columns 
WHERE table_schema = 'environmental_platform' 
    AND table_name = 'leaderboards_enhanced';

-- 5. Foreign Key Constraints
SELECT 'FOREIGN KEY CONSTRAINTS:' as section;
SELECT 
    constraint_name,
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'environmental_platform'
    AND table_name IN (
        'achievements_enhanced', 'user_achievements_enhanced',
        'leaderboards_enhanced', 'leaderboard_entries_enhanced',
        'badges_system', 'user_badges_enhanced',
        'user_streaks_gamification', 'seasonal_challenges',
        'challenge_participation'
    )
    AND referenced_table_name IS NOT NULL
ORDER BY table_name;

-- 6. Sample Data Verification
SELECT 'SAMPLE DATA VERIFICATION:' as section;

-- Show sample achievements if any exist
SELECT 'Sample Achievements:' as data_type;
SELECT 
    achievement_name,
    title_vi,
    achievement_type,
    environmental_category,
    points_reward
FROM achievements_enhanced 
LIMIT 3;

-- Show sample badges if any exist
SELECT 'Sample Badges:' as data_type;
SELECT 
    badge_name,
    title_vi,
    badge_category,
    rarity
FROM badges_system 
LIMIT 3;

-- Show seasonal challenges
SELECT 'Seasonal Challenges:' as data_type;
SELECT 
    challenge_name,
    title_vi,
    challenge_type,
    environmental_theme,
    start_date,
    end_date
FROM seasonal_challenges;

-- 7. Index Verification
SELECT 'INDEX VERIFICATION:' as section;
SELECT 
    table_name,
    index_name,
    column_name,
    seq_in_index
FROM information_schema.statistics
WHERE table_schema = 'environmental_platform'
    AND table_name IN (
        'achievements_enhanced', 'user_achievements_enhanced',
        'leaderboards_enhanced', 'leaderboard_entries_enhanced',
        'badges_system', 'user_badges_enhanced',
        'user_streaks_gamification', 'seasonal_challenges',
        'challenge_participation'
    )
ORDER BY table_name, index_name, seq_in_index;

-- 8. Final Status Summary
SELECT 'FINAL STATUS SUMMARY:' as section;
SELECT 
    'Phase 19 Status' as metric,
    CASE 
        WHEN (
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = 'environmental_platform' 
                AND table_name IN (
                    'achievements_enhanced', 'user_achievements_enhanced', 
                    'leaderboards_enhanced', 'leaderboard_entries_enhanced',
                    'badges_system', 'user_badges_enhanced',
                    'user_streaks_gamification', 'seasonal_challenges', 
                    'challenge_participation'
                )
        ) = 9 THEN '✅ PHASE 19 COMPLETED SUCCESSFULLY'
        ELSE '❌ PHASE 19 INCOMPLETE'
    END as status;

SELECT 
    'Database Readiness' as metric,
    CASE 
        WHEN (
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = 'environmental_platform'
        ) >= 88 THEN '✅ READY FOR PRODUCTION'
        ELSE '⚠️ NEEDS REVIEW'
    END as status;

-- End of verification
SELECT '=== PHASE 19 VERIFICATION COMPLETE ===' as verification_complete;
