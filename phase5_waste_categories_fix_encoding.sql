-- ========================================
-- Waste Categories Update - Fixed Encoding
-- Environmental Platform - Phase 5 Enhancement
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Fix Vietnamese names with proper encoding
UPDATE waste_categories SET category_name = 'Tái chế nhựa' WHERE category_code = 'PLA';
UPDATE waste_categories SET category_name = 'Tái chế giấy' WHERE category_code = 'PAP';
UPDATE waste_categories SET category_name = 'Tái chế kim loại' WHERE category_code = 'MET';
UPDATE waste_categories SET category_name = 'Hữu cơ' WHERE category_code = 'ORG';
UPDATE waste_categories SET category_name = 'Rác thải điện tử' WHERE category_code = 'ELE';
UPDATE waste_categories SET category_name = 'Rác thải nguy hại' WHERE category_code = 'HAZ';
UPDATE waste_categories SET category_name = 'Rác thải y tế' WHERE category_code = 'MED';
UPDATE waste_categories SET category_name = 'Rác thải thông thường' WHERE category_code = 'GEN';

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

-- Update descriptions with more detailed information
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
-- VERIFICATION
-- ========================================

-- Display all waste categories after update
SELECT 
    category_id,
    category_name,
    category_code,
    category_type,
    SUBSTRING(description, 1, 50) AS short_description,
    points_per_kg,
    carbon_saved_per_kg,
    color_code,
    sort_order
FROM 
    waste_categories
ORDER BY 
    sort_order;
