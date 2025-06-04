-- ========================================
-- Waste Categories Configuration
-- Environmental Platform - Phase 5 Enhancement
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- UPDATE EXISTING WASTE CATEGORIES
-- ========================================

-- 1. Update Organic Waste (Hữu cơ)
UPDATE waste_categories
SET 
    category_name = 'Hữu cơ',
    category_code = 'ORG',
    category_type = 'organic',
    description = 'Chất thải hữu cơ phân hủy sinh học như thức ăn thừa, vỏ trái cây, lá cây. Cần thu gom riêng để làm phân compost hoặc sản xuất khí sinh học.',
    color_code = '#8BC34A',
    points_per_kg = 5.00,
    carbon_saved_per_kg = 0.42,
    sort_order = 1,
    is_active = TRUE
WHERE category_code = 'ORG';

-- 2. Update Plastic Recycling (Tái chế nhựa)
UPDATE waste_categories
SET 
    category_name = 'Tái chế nhựa',
    category_code = 'PLA',
    category_type = 'recyclable',
    description = 'Các loại nhựa có thể tái chế như chai nhựa, hộp nhựa, túi nhựa PE, PP. Cần rửa sạch, làm khô và tháo nhãn trước khi tái chế.',
    color_code = '#2196F3',
    points_per_kg = 15.00,
    carbon_saved_per_kg = 1.53,
    sort_order = 2,
    is_active = TRUE
WHERE category_code = 'PLA';

-- 3. Update Paper Recycling (Tái chế giấy)
UPDATE waste_categories
SET 
    category_name = 'Tái chế giấy',
    category_code = 'PAP',
    category_type = 'recyclable',
    description = 'Giấy và các sản phẩm từ giấy có thể tái chế như báo, tạp chí, hộp giấy, giấy văn phòng. Cần giữ khô ráo và loại bỏ tạp chất.',
    color_code = '#795548',
    points_per_kg = 10.00,
    carbon_saved_per_kg = 0.95,
    sort_order = 3,
    is_active = TRUE
WHERE category_code = 'PAP';

-- 4. Update Metal Recycling (Tái chế kim loại)
UPDATE waste_categories
SET 
    category_name = 'Tái chế kim loại',
    category_code = 'MET',
    category_type = 'recyclable',
    description = 'Kim loại có thể tái chế như lon nhôm, đồ hộp, vật dụng kim loại hỏng. Cần rửa sạch, làm khô và bẹp lon để tiết kiệm không gian.',
    color_code = '#9E9E9E',
    points_per_kg = 25.00,
    carbon_saved_per_kg = 4.50,
    sort_order = 4,
    is_active = TRUE
WHERE category_code = 'MET';

-- 5. Update Electronic Waste (Rác thải điện tử)
UPDATE waste_categories
SET 
    category_name = 'Rác thải điện tử',
    category_code = 'ELE',
    category_type = 'electronic',
    description = 'Các thiết bị điện tử và linh kiện không còn sử dụng như điện thoại, máy tính, pin, cáp sạc. Cần mang đến điểm thu gom e-waste chuyên dụng.',
    color_code = '#FF9800',
    points_per_kg = 50.00,
    carbon_saved_per_kg = 15.00,
    sort_order = 5,
    is_active = TRUE
WHERE category_code = 'ELE';

-- ========================================
-- ADD NEW WASTE CATEGORIES
-- ========================================

-- 6. Hazardous Waste (Rác thải nguy hại)
INSERT INTO waste_categories (
    category_name,
    category_code,
    category_type,
    description,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    sort_order,
    is_active
) VALUES (
    'Rác thải nguy hại',
    'HAZ',
    'hazardous',
    'Chất thải độc hại cần xử lý đặc biệt như hóa chất, thuốc trừ sâu, sơn, dung môi. Cần giữ nguyên trong hộp đựng ban đầu và mang đến điểm thu gom chuyên dụng.',
    '#F44336',
    30.00,
    5.00,
    6,
    TRUE
);

-- 7. Medical Waste (Rác thải y tế)
INSERT INTO waste_categories (
    category_name,
    category_code,
    category_type,
    description,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    sort_order,
    is_active
) VALUES (
    'Rác thải y tế',
    'MED',
    'medical',
    'Chất thải từ hoạt động y tế tại nhà như kim tiêm, băng gạc, thuốc hết hạn. Cần đựng trong hộp kín, dán nhãn rõ ràng và mang đến điểm thu gom chuyên dụng.',
    '#E91E63',
    35.00,
    4.00,
    7,
    TRUE
);

-- 8. General Waste (Rác thải thông thường)
INSERT INTO waste_categories (
    category_name,
    category_code,
    category_type,
    description,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    sort_order,
    is_active
) VALUES (
    'Rác thải thông thường',
    'GEN',
    'general',
    'Chất thải không thể tái chế hoặc phân hủy sinh học, thường phải chôn lấp hoặc đốt. Cần giảm thiểu tối đa lượng rác thải và đảm bảo phân loại đúng các loại rác có thể tái chế.',
    '#607D8B',
    2.00,
    0.10,
    8,
    TRUE
);

-- ========================================
-- VERIFICATION
-- ========================================

-- Display all waste categories after update
SELECT 
    category_id,
    category_name,
    category_code,
    category_type,
    points_per_kg,
    carbon_saved_per_kg,
    color_code,
    sort_order
FROM 
    waste_categories
ORDER BY 
    sort_order;
