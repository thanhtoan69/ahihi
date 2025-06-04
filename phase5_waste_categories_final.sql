-- ========================================
-- Waste Categories Final Configuration
-- Environmental Platform - Phase 5 Enhancement
-- Date: June 5, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- ENSURE PROPER COLUMN STRUCTURE
-- ========================================

-- Add English name column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = 'environmental_platform' 
     AND table_name = 'waste_categories' 
     AND column_name = 'category_name_en') > 0,
    'SELECT 1',
    'ALTER TABLE waste_categories ADD COLUMN category_name_en VARCHAR(100) NULL AFTER category_name'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add English description column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = 'environmental_platform' 
     AND table_name = 'waste_categories' 
     AND column_name = 'description_en') > 0,
    'SELECT 1',
    'ALTER TABLE waste_categories ADD COLUMN description_en TEXT NULL AFTER description'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- FIX VIETNAMESE NAMES AND DESCRIPTIONS
-- ========================================

-- Fix Vietnamese names with proper encoding
UPDATE waste_categories SET category_name = 'Tái chế nhựa' WHERE category_code = 'PLA';
UPDATE waste_categories SET category_name = 'Tái chế giấy' WHERE category_code = 'PAP';
UPDATE waste_categories SET category_name = 'Tái chế kim loại' WHERE category_code = 'MET';
UPDATE waste_categories SET category_name = 'Hữu cơ' WHERE category_code = 'ORG';
UPDATE waste_categories SET category_name = 'Rác thải điện tử' WHERE category_code = 'ELE';
UPDATE waste_categories SET category_name = 'Rác thải nguy hại' WHERE category_code = 'HAZ';
UPDATE waste_categories SET category_name = 'Rác thải y tế' WHERE category_code = 'MED';
UPDATE waste_categories SET category_name = 'Rác thải thông thường' WHERE category_code = 'GEN';

-- ========================================
-- ADD ENGLISH NAMES AND DESCRIPTIONS
-- ========================================

-- Add English names for all categories
UPDATE waste_categories SET category_name_en = 'Plastic Recycling' WHERE category_code = 'PLA';
UPDATE waste_categories SET category_name_en = 'Paper Recycling' WHERE category_code = 'PAP';
UPDATE waste_categories SET category_name_en = 'Metal Recycling' WHERE category_code = 'MET';
UPDATE waste_categories SET category_name_en = 'Organic Waste' WHERE category_code = 'ORG';
UPDATE waste_categories SET category_name_en = 'Electronic Waste' WHERE category_code = 'ELE';
UPDATE waste_categories SET category_name_en = 'Hazardous Waste' WHERE category_code = 'HAZ';
UPDATE waste_categories SET category_name_en = 'Medical Waste' WHERE category_code = 'MED';
UPDATE waste_categories SET category_name_en = 'General Waste' WHERE category_code = 'GEN';

-- Add English descriptions for all categories
UPDATE waste_categories 
SET description_en = 'Biodegradable organic waste such as food scraps, fruit peels, leaves. Collect separately for composting or biogas production.'
WHERE category_code = 'ORG';

UPDATE waste_categories 
SET description_en = 'Recyclable plastics like bottles, containers, PE/PP bags. Wash clean, dry, and remove labels before recycling.'
WHERE category_code = 'PLA';

UPDATE waste_categories 
SET description_en = 'Paper and paper products for recycling including newspapers, magazines, cardboard boxes, office paper. Keep dry and remove contaminants.'
WHERE category_code = 'PAP';

UPDATE waste_categories 
SET description_en = 'Recyclable metals like aluminum cans, tin cans, broken metal items. Wash clean, dry, and flatten cans to save space.'
WHERE category_code = 'MET';

UPDATE waste_categories 
SET description_en = 'Discarded electronic devices and components like phones, computers, batteries, charging cables. Take to dedicated e-waste collection points.'
WHERE category_code = 'ELE';

UPDATE waste_categories 
SET description_en = 'Hazardous waste requiring special handling like chemicals, pesticides, paint, solvents. Keep in original containers and take to dedicated collection points.'
WHERE category_code = 'HAZ';

UPDATE waste_categories 
SET description_en = 'Waste from home healthcare activities like needles, bandages, expired medications. Store in sealed containers, label clearly, take to dedicated collection points.'
WHERE category_code = 'MED';

UPDATE waste_categories 
SET description_en = 'Non-recyclable or non-biodegradable waste that typically goes to landfill or incineration. Minimize this waste type as much as possible and ensure proper sorting.'
WHERE category_code = 'GEN';

-- ========================================
-- UPDATE COLORS AND SORT ORDER
-- ========================================

-- Update colors with a consistent color scheme
UPDATE waste_categories SET color_code = '#2196F3' WHERE category_code = 'PLA';
UPDATE waste_categories SET color_code = '#795548' WHERE category_code = 'PAP';
UPDATE waste_categories SET color_code = '#9E9E9E' WHERE category_code = 'MET';
UPDATE waste_categories SET color_code = '#8BC34A' WHERE category_code = 'ORG';
UPDATE waste_categories SET color_code = '#FF9800' WHERE category_code = 'ELE';
UPDATE waste_categories SET color_code = '#F44336' WHERE category_code = 'HAZ';
UPDATE waste_categories SET color_code = '#E91E63' WHERE category_code = 'MED';
UPDATE waste_categories SET color_code = '#607D8B' WHERE category_code = 'GEN';

-- Update sort order for a logical sequence
UPDATE waste_categories SET sort_order = 1 WHERE category_code = 'ORG';
UPDATE waste_categories SET sort_order = 2 WHERE category_code = 'PLA';
UPDATE waste_categories SET sort_order = 3 WHERE category_code = 'PAP';
UPDATE waste_categories SET sort_order = 4 WHERE category_code = 'MET';
UPDATE waste_categories SET sort_order = 5 WHERE category_code = 'ELE';
UPDATE waste_categories SET sort_order = 6 WHERE category_code = 'HAZ';
UPDATE waste_categories SET sort_order = 7 WHERE category_code = 'MED';
UPDATE waste_categories SET sort_order = 8 WHERE category_code = 'GEN';

-- ========================================
-- UPDATE DESCRIPTIONS IN VIETNAMESE
-- ========================================

UPDATE waste_categories 
SET description = 'Chất thải hữu cơ phân hủy sinh học như thức ăn thừa, vỏ trái cây, lá cây. Thu gom riêng để làm phân compost hoặc khí sinh học.'
WHERE category_code = 'ORG';

UPDATE waste_categories 
SET description = 'Các loại nhựa có thể tái chế như chai nhựa, hộp nhựa, túi nhựa PE, PP. Rửa sạch, làm khô, tháo nhãn trước khi tái chế.'
WHERE category_code = 'PLA';

UPDATE waste_categories 
SET description = 'Giấy và các sản phẩm từ giấy có thể tái chế như báo, tạp chí, hộp giấy, giấy văn phòng. Giữ khô ráo, loại bỏ tạp chất.'
WHERE category_code = 'PAP';

UPDATE waste_categories 
SET description = 'Kim loại có thể tái chế như lon nhôm, đồ hộp, vật dụng kim loại hỏng. Rửa sạch, làm khô, bẹp lon để tiết kiệm không gian.'
WHERE category_code = 'MET';

UPDATE waste_categories 
SET description = 'Các thiết bị điện tử và linh kiện không còn sử dụng như điện thoại, máy tính, pin, cáp sạc. Mang đến điểm thu gom e-waste chuyên dụng.'
WHERE category_code = 'ELE';

UPDATE waste_categories 
SET description = 'Chất thải độc hại cần xử lý đặc biệt như hóa chất, thuốc trừ sâu, sơn, dung môi. Giữ nguyên trong hộp đựng ban đầu, mang đến điểm thu gom chuyên dụng.'
WHERE category_code = 'HAZ';

UPDATE waste_categories 
SET description = 'Chất thải từ hoạt động y tế tại nhà như kim tiêm, băng gạc, thuốc hết hạn. Đựng trong hộp kín, dán nhãn rõ ràng, mang đến điểm thu gom chuyên dụng.'
WHERE category_code = 'MED';

UPDATE waste_categories 
SET description = 'Chất thải không thể tái chế hoặc phân hủy sinh học, thường phải chôn lấp hoặc đốt. Giảm thiểu tối đa lượng rác thải, đảm bảo phân loại đúng.'
WHERE category_code = 'GEN';

-- ========================================
-- ENSURE CATEGORY ICONS ARE SET
-- ========================================

-- Set icon URLs if they're not already set
UPDATE waste_categories SET icon_url = '/assets/icons/waste/organic.svg' WHERE category_code = 'ORG' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/plastic.svg' WHERE category_code = 'PLA' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/paper.svg' WHERE category_code = 'PAP' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/metal.svg' WHERE category_code = 'MET' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/electronic.svg' WHERE category_code = 'ELE' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/hazardous.svg' WHERE category_code = 'HAZ' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/medical.svg' WHERE category_code = 'MED' AND (icon_url IS NULL OR icon_url = '');
UPDATE waste_categories SET icon_url = '/assets/icons/waste/general.svg' WHERE category_code = 'GEN' AND (icon_url IS NULL OR icon_url = '');

-- ========================================
-- VERIFY FINAL RESULTS
-- ========================================

-- Display complete waste categories with both languages
SELECT 
    category_id,
    category_code AS code,
    category_name AS name_vi,
    category_name_en AS name_en,
    category_type AS type,
    color_code AS color,
    points_per_kg AS points,
    carbon_saved_per_kg AS carbon_saved,
    sort_order
FROM 
    waste_categories
ORDER BY 
    sort_order;
