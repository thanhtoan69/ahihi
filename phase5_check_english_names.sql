-- ========================================
-- Waste Categories English Names Check
-- Environmental Platform - Phase 5 Verification
-- Date: June 5, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Check if English name column exists
SELECT COUNT(*) AS column_exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE table_schema = 'environmental_platform' 
AND table_name = 'waste_categories' 
AND column_name = 'category_name_en';

-- If the column exists, check which categories have English names
SELECT 
    category_id,
    category_code,
    category_name,
    category_name_en,
    IF(category_name_en IS NULL OR category_name_en = '', 'Missing', 'Present') AS english_name_status
FROM 
    waste_categories
ORDER BY 
    sort_order;
