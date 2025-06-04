-- ========================================
-- Waste Categories Verification Script
-- Environmental Platform - Phase 5 Verification
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- VERIFY WASTE CATEGORIES TABLE
-- ========================================

-- 1. Check table structure
SELECT 'TABLE STRUCTURE VERIFICATION' AS section;
DESCRIBE waste_categories;

-- 2. Check category types enum
SELECT 'CATEGORY TYPES VERIFICATION' AS section;
SHOW COLUMNS FROM waste_categories LIKE 'category_type';

-- 3. Check all waste categories
SELECT 'ALL WASTE CATEGORIES' AS section;
SELECT 
    category_id,
    category_name,
    CONCAT('(', category_code, ')') AS code,
    category_type,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    sort_order,
    is_active
FROM 
    waste_categories
ORDER BY 
    sort_order;

-- 4. Verify Vietnamese encoding
SELECT 'VIETNAMESE ENCODING VERIFICATION' AS section;
SELECT 
    category_code,
    category_name,
    HEX(category_name) AS hex_name,
    IF(
        HEX(category_name) LIKE '%C3%A1%' OR -- á
        HEX(category_name) LIKE '%C3%A0%' OR -- à
        HEX(category_name) LIKE '%E1%BA%A3%' OR -- ả
        HEX(category_name) LIKE '%E1%BA%A1%' OR -- ạ
        HEX(category_name) LIKE '%C6%A1%' OR -- ơ
        HEX(category_name) LIKE '%C3%B4%' OR -- ô
        HEX(category_name) LIKE '%C4%83%' OR -- ă
        HEX(category_name) LIKE '%C3%AA%' OR -- ê
        HEX(category_name) LIKE '%E1%BB%A7%' OR -- ủ
        HEX(category_name) LIKE '%E1%BB%87%', -- ệ
        'UTF-8 Encoding OK',
        'No Vietnamese characters found'
    ) AS encoding_status
FROM 
    waste_categories;

-- 5. Verify categories by type
SELECT 'CATEGORIES BY TYPE' AS section;
SELECT 
    category_type, 
    COUNT(*) AS count,
    GROUP_CONCAT(category_name SEPARATOR ', ') AS categories
FROM 
    waste_categories
GROUP BY 
    category_type
ORDER BY 
    count DESC;

-- 6. Check points distribution
SELECT 'POINTS DISTRIBUTION' AS section;
SELECT 
    MIN(points_per_kg) AS min_points,
    MAX(points_per_kg) AS max_points,
    AVG(points_per_kg) AS avg_points,
    SUM(points_per_kg) AS total_points
FROM 
    waste_categories;

-- 7. Check carbon savings distribution
SELECT 'CARBON SAVINGS DISTRIBUTION' AS section;
SELECT 
    MIN(carbon_saved_per_kg) AS min_carbon_saved,
    MAX(carbon_saved_per_kg) AS max_carbon_saved,
    AVG(carbon_saved_per_kg) AS avg_carbon_saved,
    SUM(carbon_saved_per_kg) AS total_carbon_saved
FROM 
    waste_categories;

-- 8. Verify color scheme is unique per category
SELECT 'COLOR SCHEME VERIFICATION' AS section;
SELECT 
    color_code,
    COUNT(*) as count,
    GROUP_CONCAT(category_name SEPARATOR ', ') AS categories
FROM 
    waste_categories
GROUP BY 
    color_code
HAVING 
    COUNT(*) > 1;

-- 9. Verify all necessary waste types are covered
SELECT 'WASTE TYPES COVERAGE' AS section;
SELECT 
    CASE 
        WHEN EXISTS (SELECT 1 FROM waste_categories WHERE category_type = 'organic') THEN 'YES'
        ELSE 'NO'
    END AS has_organic,
    CASE 
        WHEN EXISTS (SELECT 1 FROM waste_categories WHERE category_type = 'recyclable') THEN 'YES'
        ELSE 'NO'
    END AS has_recyclable,
    CASE 
        WHEN EXISTS (SELECT 1 FROM waste_categories WHERE category_type = 'hazardous') THEN 'YES'
        ELSE 'NO'
    END AS has_hazardous,
    CASE 
        WHEN EXISTS (SELECT 1 FROM waste_categories WHERE category_type = 'general') THEN 'YES'
        ELSE 'NO'
    END AS has_general,
    CASE 
        WHEN EXISTS (SELECT 1 FROM waste_categories WHERE category_type = 'electronic') THEN 'YES'
        ELSE 'NO'
    END AS has_electronic,
    CASE 
        WHEN EXISTS (SELECT 1 FROM waste_categories WHERE category_type = 'medical') THEN 'YES'
        ELSE 'NO'
    END AS has_medical;

-- 10. Check sort order integrity
SELECT 'SORT ORDER INTEGRITY' AS section;
SELECT 
    MIN(sort_order) AS min_sort_order,
    MAX(sort_order) AS max_sort_order,
    COUNT(DISTINCT sort_order) AS unique_sort_orders,
    COUNT(*) AS total_categories
FROM 
    waste_categories;

-- 11. Check for any disabled categories
SELECT 'DISABLED CATEGORIES' AS section;
SELECT 
    category_name,
    category_code,
    category_type
FROM 
    waste_categories
WHERE 
    is_active = FALSE
ORDER BY 
    category_type, category_name;

-- ========================================
-- VERIFICATION SUMMARY
-- ========================================

SELECT 'WASTE CATEGORIES VERIFICATION SUMMARY' AS section;
SELECT 
    COUNT(*) AS total_categories,
    SUM(CASE WHEN category_type = 'recyclable' THEN 1 ELSE 0 END) AS recyclable_count,
    SUM(CASE WHEN category_type = 'organic' THEN 1 ELSE 0 END) AS organic_count,
    SUM(CASE WHEN category_type = 'hazardous' THEN 1 ELSE 0 END) AS hazardous_count,
    SUM(CASE WHEN category_type = 'electronic' THEN 1 ELSE 0 END) AS electronic_count,
    SUM(CASE WHEN category_type = 'medical' THEN 1 ELSE 0 END) AS medical_count,
    SUM(CASE WHEN category_type = 'general' THEN 1 ELSE 0 END) AS general_count,
    SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) AS active_count,
    SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) AS inactive_count
FROM 
    waste_categories;

-- ========================================
-- END OF VERIFICATION SCRIPT
-- ========================================
