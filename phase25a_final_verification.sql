-- Phase 25A Final Verification Script
-- Environmental Platform Database - Basic Categories & Configuration

USE environmental_platform;

-- =============================================
-- FINAL VERIFICATION QUERIES
-- =============================================

SELECT '=== PHASE 25A FINAL VERIFICATION ===' AS verification_header;

-- 1. Total category count verification
SELECT 'Total Categories Count' AS metric, COUNT(*) AS value FROM categories;

-- 2. Categories by type breakdown
SELECT 'Categories by Type' AS section;
SELECT 
    category_type, 
    COUNT(*) AS total_count,
    SUM(CASE WHEN level = 0 THEN 1 ELSE 0 END) AS main_categories,
    SUM(CASE WHEN level = 1 THEN 1 ELSE 0 END) AS sub_categories
FROM categories 
GROUP BY category_type 
ORDER BY category_type;

-- 3. Hierarchy validation
SELECT 'Hierarchy Validation' AS section;
SELECT 
    'Main Categories (level 0, parent_id IS NULL)' AS category_level,
    COUNT(*) AS count
FROM categories 
WHERE level = 0 AND parent_id IS NULL
UNION ALL
SELECT 
    'Sub Categories (level 1, parent_id IS NOT NULL)' AS category_level,
    COUNT(*) AS count
FROM categories 
WHERE level = 1 AND parent_id IS NOT NULL;

-- 4. Parent-child relationship verification
SELECT 'Parent-Child Relationships' AS section;
SELECT 
    p.name AS parent_category,
    p.category_type,
    COUNT(c.category_id) AS sub_category_count
FROM categories p
LEFT JOIN categories c ON p.category_id = c.parent_id
WHERE p.level = 0
GROUP BY p.category_id, p.name, p.category_type
ORDER BY p.category_type, p.name;

-- 5. Complete hierarchy display
SELECT 'Complete Category Hierarchy' AS section;
SELECT 
    category_id,
    CASE 
        WHEN level = 0 THEN name
        ELSE CONCAT('  └─ ', name)
    END AS category_hierarchy,
    name_en,
    category_type,
    level,
    parent_id
FROM categories 
ORDER BY category_type, COALESCE(parent_id, category_id), level, category_id;

-- 6. Data quality checks
SELECT 'Data Quality Checks' AS section;

-- Check for missing required fields
SELECT 'Categories with missing name' AS check_type, COUNT(*) AS count
FROM categories WHERE name IS NULL OR name = ''
UNION ALL
SELECT 'Categories with missing slug' AS check_type, COUNT(*) AS count  
FROM categories WHERE slug IS NULL OR slug = ''
UNION ALL
SELECT 'Categories with missing category_type' AS check_type, COUNT(*) AS count
FROM categories WHERE category_type IS NULL
UNION ALL
SELECT 'Categories with invalid level' AS check_type, COUNT(*) AS count
FROM categories WHERE level NOT IN (0, 1)
UNION ALL
SELECT 'Sub-categories with missing parent_id' AS check_type, COUNT(*) AS count
FROM categories WHERE level = 1 AND parent_id IS NULL;

-- 7. SEO and content readiness
SELECT 'SEO and Content Readiness' AS section;
SELECT 
    'Categories with SEO title' AS metric,
    COUNT(*) AS count
FROM categories WHERE seo_title IS NOT NULL AND seo_title != ''
UNION ALL
SELECT 
    'Categories with SEO description' AS metric,
    COUNT(*) AS count  
FROM categories WHERE seo_description IS NOT NULL AND seo_description != ''
UNION ALL
SELECT 
    'Categories with SEO keywords' AS metric,
    COUNT(*) AS count
FROM categories WHERE seo_keywords IS NOT NULL AND seo_keywords != '';

-- 8. Category type distribution details
SELECT 'Detailed Category Breakdown by Type' AS section;
SELECT 
    category_type,
    level,
    COUNT(*) AS count,
    GROUP_CONCAT(name ORDER BY name SEPARATOR '; ') AS category_names
FROM categories 
GROUP BY category_type, level
ORDER BY category_type, level;

-- 9. Foreign key constraint verification
SELECT 'Foreign Key Constraint Verification' AS section;
SELECT 
    'Valid parent references' AS check_type,
    COUNT(*) AS count
FROM categories c1
WHERE c1.parent_id IS NOT NULL 
AND EXISTS (SELECT 1 FROM categories c2 WHERE c2.category_id = c1.parent_id);

-- 10. Final success confirmation
SELECT 
    CASE 
        WHEN COUNT(*) = 37 THEN '✅ PHASE 25A COMPLETED SUCCESSFULLY!'
        ELSE '❌ PHASE 25A INCOMPLETE - Missing Categories'
    END AS final_status
FROM categories;

SELECT 'Phase 25A verification completed' AS completion_message;
