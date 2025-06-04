-- Phase 25A: Complete Categories Summary
-- Final overview of all 37 categories created

USE environmental_platform;

-- Show complete hierarchy by type
SELECT 
    CONCAT(
        CASE category_type
            WHEN 'article' THEN '📰 '
            WHEN 'product' THEN '🛍️ '
            WHEN 'event' THEN '🎪 '
            WHEN 'forum' THEN '💬 '
        END,
        UPPER(category_type), ' CATEGORIES'
    ) AS section,
    COUNT(*) AS total_count
FROM categories 
GROUP BY category_type
ORDER BY category_type;

SELECT '=====================================';

-- Article categories hierarchy
SELECT '📰 ARTICLE CATEGORIES (18 total)' AS section;
SELECT 
    category_id,
    CASE 
        WHEN level = 0 THEN CONCAT('▶️ ', name, ' (', name_en, ')')
        ELSE CONCAT('   └─ ', name, ' (', name_en, ')')
    END AS category_hierarchy
FROM categories 
WHERE category_type = 'article'
ORDER BY COALESCE(parent_id, category_id), level, category_id;

SELECT '=====================================';

-- Product categories hierarchy  
SELECT '🛍️ PRODUCT CATEGORIES (7 total)' AS section;
SELECT 
    category_id,
    CASE 
        WHEN level = 0 THEN CONCAT('▶️ ', name, ' (', name_en, ')')
        ELSE CONCAT('   └─ ', name, ' (', name_en, ')')
    END AS category_hierarchy
FROM categories 
WHERE category_type = 'product'
ORDER BY COALESCE(parent_id, category_id), level, category_id;

SELECT '=====================================';

-- Event categories hierarchy
SELECT '🎪 EVENT CATEGORIES (8 total)' AS section;
SELECT 
    category_id,
    CASE 
        WHEN level = 0 THEN CONCAT('▶️ ', name, ' (', name_en, ')')
        ELSE CONCAT('   └─ ', name, ' (', name_en, ')')
    END AS category_hierarchy
FROM categories 
WHERE category_type = 'event'
ORDER BY COALESCE(parent_id, category_id), level, category_id;

SELECT '=====================================';

-- Forum categories (all main level)
SELECT '💬 FORUM CATEGORIES (4 total)' AS section;
SELECT 
    category_id,
    CONCAT('▶️ ', name, ' (', name_en, ')') AS category_hierarchy
FROM categories 
WHERE category_type = 'forum'
ORDER BY category_id;

SELECT '=====================================';
SELECT 'PHASE 25A: BASIC CATEGORIES & CONFIGURATION';
SELECT '✅ STATUS: COMPLETED SUCCESSFULLY';
SELECT '📊 TOTAL CATEGORIES: 37';
SELECT '📅 COMPLETION DATE: June 4, 2025';
SELECT '=====================================';
