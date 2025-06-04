-- Phase 16 Final Verification Script
USE environmental_platform;

-- 1. Verify table count
SELECT 'DATABASE STATUS' as section, 
       COUNT(*) as total_tables,
       'Expected: 60' as expected
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform';

-- 2. Verify new waste classification tables
SELECT 'WASTE CLASSIFICATION TABLES' as section;
SHOW TABLES LIKE 'waste_classification%';

-- 3. Verify table structures
SELECT 'TABLE STRUCTURES' as section;
DESCRIBE waste_classification_sessions;
DESCRIBE waste_classification_results;

-- 4. Verify sample data
SELECT 'SAMPLE DATA VERIFICATION' as section;
SELECT 
    s.session_id,
    s.session_type,
    s.session_status,
    s.points_earned,
    r.predicted_category,
    r.confidence_score,
    r.is_recyclable,
    r.carbon_saved_kg
FROM waste_classification_sessions s
JOIN waste_classification_results r ON s.session_id = r.session_id;

-- 5. Verify indexes
SELECT 'INDEX VERIFICATION' as section;
SHOW INDEX FROM waste_classification_sessions;
SHOW INDEX FROM waste_classification_results;

-- 6. Verify foreign key relationships
SELECT 'FOREIGN KEY VERIFICATION' as section;
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'environmental_platform'
AND TABLE_NAME LIKE 'waste_classification%'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 7. Performance test query
SELECT 'PERFORMANCE TEST' as section;
SELECT 
    COUNT(*) as total_sessions,
    AVG(confidence_score) as avg_confidence,
    SUM(points_earned) as total_points,
    SUM(carbon_saved_kg) as total_carbon_saved
FROM waste_classification_sessions s
JOIN waste_classification_results r ON s.session_id = r.session_id;

SELECT 'Phase 16 Verification Complete!' as status;
