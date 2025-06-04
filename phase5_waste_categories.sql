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
    category_name_en = 'Organic Waste',
    category_code = 'ORG',
    category_type = 'organic',
    description = 'Chất thải hữu cơ phân hủy sinh học như thức ăn thừa, vỏ trái cây, lá cây',
    description_en = 'Biodegradable organic waste such as food scraps, fruit peels, leaves',
    handling_instructions = 'Thu gom riêng để làm phân compost hoặc khí sinh học',
    disposal_methods = '["Ủ phân compost", "Sản xuất khí sinh học", "Thu gom riêng"]',
    recycling_process = 'Ủ phân compost hoặc phân hủy kỵ khí để sản xuất khí sinh học',
    environmental_impact = 'Giảm lượng chất thải chôn lấp, tạo phân bón tự nhiên, giảm phát thải khí methane',
    icon_url = '/assets/icons/waste/organic.svg',
    color_code = '#8BC34A',
    points_per_kg = 5.00,
    carbon_saved_per_kg = 0.42,
    examples = '["Vỏ trái cây", "Rau củ thừa", "Bã cà phê", "Lá cây", "Thức ăn thừa", "Vỏ trứng"]',
    common_mistakes = '["Trộn lẫn với nhựa", "Trộn lẫn với kim loại", "Vứt bỏ cùng rác thông thường"]',
    tips = '["Để ráo nước trước khi bỏ vào thùng", "Cắt nhỏ để tăng tốc độ phân hủy", "Tránh bỏ thịt và sữa nếu ủ phân compost tại nhà"]',
    sort_order = 1,
    is_active = TRUE
WHERE category_code = 'ORG';

-- 2. Update Plastic Recycling (Tái chế nhựa)
UPDATE waste_categories
SET 
    category_name = 'Tái chế nhựa',
    category_name_en = 'Recyclable Plastic',
    category_code = 'PLA',
    category_type = 'recyclable',
    description = 'Các loại nhựa có thể tái chế như chai nhựa, hộp nhựa, túi nhựa PE, PP',
    description_en = 'Recyclable plastics such as plastic bottles, containers, PE/PP plastic bags',
    handling_instructions = 'Rửa sạch, làm khô, tháo nhãn và nắp (nếu có) trước khi tái chế',
    disposal_methods = '["Thu gom riêng", "Trung tâm tái chế", "Điểm thu mua phế liệu"]',
    recycling_process = 'Phân loại, nghiền, rửa, nóng chảy và tạo hạt nhựa mới để sản xuất sản phẩm tái chế',
    environmental_impact = 'Giảm rác thải nhựa ra môi trường, tiết kiệm tài nguyên, giảm ô nhiễm đại dương',
    icon_url = '/assets/icons/waste/plastic.svg',
    color_code = '#2196F3',
    points_per_kg = 15.00,
    carbon_saved_per_kg = 1.53,
    examples = '["Chai nước", "Hộp nhựa đựng thực phẩm", "Vỏ chai dầu gội", "Túi nhựa PE", "Ống hút PP", "Hộp sữa chua"]',
    common_mistakes = '["Không rửa sạch", "Bỏ lẫn nhựa không tái chế được", "Vứt lẫn với rác thải khác"]',
    tips = '["Kiểm tra mã tái chế ở đáy sản phẩm", "Nén chai để tiết kiệm không gian", "Tránh nhựa mỏng khó tái chế"]',
    sort_order = 2,
    is_active = TRUE
WHERE category_code = 'PLA';

-- 3. Update Paper Recycling (Tái chế giấy)
UPDATE waste_categories
SET 
    category_name = 'Tái chế giấy',
    category_name_en = 'Recyclable Paper',
    category_code = 'PAP',
    category_type = 'recyclable',
    description = 'Giấy và các sản phẩm từ giấy có thể tái chế như báo, tạp chí, hộp giấy, giấy văn phòng',
    description_en = 'Paper and paper products for recycling like newspapers, magazines, cardboard, office paper',
    handling_instructions = 'Giữ khô ráo, loại bỏ tạp chất như kẹp giấy, băng dính, giấy có phủ nhựa',
    disposal_methods = '["Thu gom riêng", "Trung tâm tái chế", "Điểm thu mua phế liệu"]',
    recycling_process = 'Nghiền thành bột giấy, loại bỏ mực và tạp chất, tạo ra giấy tái chế mới',
    environmental_impact = 'Giảm chặt phá rừng, tiết kiệm năng lượng và nước, giảm khí thải nhà kính',
    icon_url = '/assets/icons/waste/paper.svg',
    color_code = '#795548',
    points_per_kg = 10.00,
    carbon_saved_per_kg = 0.95,
    examples = '["Báo cũ", "Hộp giấy", "Giấy in", "Sách báo cũ", "Túi giấy", "Bìa carton"]',
    common_mistakes = '["Tái chế giấy dính dầu mỡ", "Giấy ướt", "Giấy có phủ nhựa"]',
    tips = '["Gấp hộp giấy để tiết kiệm không gian", "Loại bỏ băng dính và ghim kẹp", "Tách riêng giấy trắng để tái chế chất lượng cao"]',
    sort_order = 3,
    is_active = TRUE
WHERE category_code = 'PAP';

-- 4. Update Metal Recycling (Tái chế kim loại)
UPDATE waste_categories
SET 
    category_name = 'Tái chế kim loại',
    category_name_en = 'Recyclable Metal',
    category_code = 'MET',
    category_type = 'recyclable',
    description = 'Kim loại có thể tái chế như lon nhôm, đồ hộp, vật dụng kim loại hỏng',
    description_en = 'Recyclable metals such as aluminum cans, tin cans, broken metal items',
    handling_instructions = 'Rửa sạch, làm khô, tháo nhãn, bẹp lon để tiết kiệm không gian',
    disposal_methods = '["Thu gom riêng", "Trung tâm tái chế", "Điểm thu mua phế liệu"]',
    recycling_process = 'Phân loại, nghiền, nấu chảy và đúc thành các sản phẩm mới',
    environmental_impact = 'Tiết kiệm năng lượng đáng kể so với khai thác mới, giảm khai thác mỏ, giảm ô nhiễm nước',
    icon_url = '/assets/icons/waste/metal.svg',
    color_code = '#9E9E9E',
    points_per_kg = 25.00,
    carbon_saved_per_kg = 4.50,
    examples = '["Lon nước ngọt", "Lon thực phẩm", "Nắp chai kim loại", "Đồ hộp", "Vật dụng kim loại hỏng", "Ống nước kim loại"]',
    common_mistakes = '["Vứt lẫn với rác thải khác", "Không rửa sạch", "Không bẹp lon"]',
    tips = '["Dùng nam châm để kiểm tra loại kim loại", "Tách riêng nhôm và sắt nếu có thể", "Bẹp lon để tiết kiệm không gian"]',
    sort_order = 4,
    is_active = TRUE
WHERE category_code = 'MET';

-- 5. Update Electronic Waste (Rác thải điện tử)
UPDATE waste_categories
SET 
    category_name = 'Rác thải điện tử',
    category_name_en = 'Electronic Waste',
    category_code = 'ELE',
    category_type = 'electronic',
    description = 'Các thiết bị điện tử và linh kiện không còn sử dụng như điện thoại, máy tính, pin, cáp sạc',
    description_en = 'Discarded electronic devices and components like phones, computers, batteries, chargers',
    handling_instructions = 'Không vứt cùng rác thải sinh hoạt, mang đến điểm thu gom e-waste chuyên dụng',
    disposal_methods = '["Điểm thu gom e-waste", "Chương trình thu hồi của nhà sản xuất", "Trung tâm tái chế chuyên dụng"]',
    recycling_process = 'Tháo rời, tách kim loại quý, tái chế nhựa và kim loại, xử lý an toàn các thành phần độc hại',
    environmental_impact = 'Ngăn rò rỉ chất độc hại ra môi trường, thu hồi kim loại quý, giảm khai thác tài nguyên mới',
    icon_url = '/assets/icons/waste/electronic.svg',
    color_code = '#FF9800',
    points_per_kg = 50.00,
    carbon_saved_per_kg = 15.00,
    examples = '["Điện thoại cũ", "Máy tính hỏng", "Pin và ắc quy", "Cáp sạc", "Bóng đèn LED", "Thiết bị gia dụng nhỏ"]',
    common_mistakes = '["Vứt cùng rác thải thông thường", "Để lâu trong nhà không xử lý", "Tự tháo dỡ không đúng cách"]',
    tips = '["Xóa dữ liệu cá nhân trước khi thải bỏ", "Tìm chương trình thu hồi của nhà sản xuất", "Tận dụng các sự kiện thu gom e-waste"]',
    sort_order = 5,
    is_active = TRUE
WHERE category_code = 'ELE';

-- ========================================
-- ADD NEW WASTE CATEGORIES
-- ========================================

-- 6. Hazardous Waste (Rác thải nguy hại)
INSERT INTO waste_categories (
    category_name,
    category_name_en,
    category_code,
    category_type,
    description,
    description_en,
    handling_instructions,
    disposal_methods,
    recycling_process,
    environmental_impact,
    icon_url,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    examples,
    common_mistakes,
    tips,
    sort_order,
    is_active
) VALUES (
    'Rác thải nguy hại',
    'Hazardous Waste',
    'HAZ',
    'hazardous',
    'Chất thải độc hại cần xử lý đặc biệt như hóa chất, thuốc trừ sâu, sơn, dung môi',
    'Hazardous waste requiring special handling like chemicals, pesticides, paint, solvents',
    'Giữ nguyên trong hộp đựng ban đầu, không trộn lẫn, mang đến điểm thu gom chuyên dụng',
    '["Điểm thu gom chất thải nguy hại", "Sự kiện thu gom đặc biệt", "Không vứt vào hệ thống thoát nước"]',
    'Xử lý đặc biệt tại cơ sở chuyên dụng, vô hiệu hóa chất độc, chôn lấp an toàn',
    'Ngăn chặn ô nhiễm đất và nước ngầm, bảo vệ hệ sinh thái và sức khỏe con người',
    '/assets/icons/waste/hazardous.svg',
    '#F44336',
    30.00,
    5.00,
    '["Hóa chất tẩy rửa", "Thuốc trừ sâu", "Pin và ắc quy", "Sơn và dung môi", "Thuốc hết hạn", "Bóng đèn huỳnh quang"]',
    '["Đổ xuống cống rãnh", "Vứt cùng rác thải thông thường", "Chôn lấp tự phát"]',
    '["Giữ nguyên trong hộp gốc có nhãn", "Không trộn lẫn các loại hóa chất", "Lưu trữ nơi khô ráo, tránh xa trẻ em"]',
    6,
    TRUE
);

-- 7. Medical Waste (Rác thải y tế)
INSERT INTO waste_categories (
    category_name,
    category_name_en,
    category_code,
    category_type,
    description,
    description_en,
    handling_instructions,
    disposal_methods,
    recycling_process,
    environmental_impact,
    icon_url,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    examples,
    common_mistakes,
    tips,
    sort_order,
    is_active
) VALUES (
    'Rác thải y tế',
    'Medical Waste',
    'MED',
    'medical',
    'Chất thải từ hoạt động y tế tại nhà như kim tiêm, băng gạc, thuốc hết hạn',
    'Waste from home healthcare activities like needles, bandages, expired medications',
    'Đựng trong hộp kín, dán nhãn rõ ràng, không vứt cùng rác thải thông thường',
    '["Điểm thu gom chất thải y tế", "Nhà thuốc", "Cơ sở y tế", "Chương trình thu hồi thuốc"]',
    'Khử trùng, thiêu hủy hoặc xử lý đặc biệt tại cơ sở y tế',
    'Ngăn ngừa lây nhiễm bệnh, ngăn chất kháng sinh và hormone rò rỉ vào nguồn nước',
    '/assets/icons/waste/medical.svg',
    '#E91E63',
    35.00,
    4.00,
    '["Kim tiêm", "Băng gạc đã qua sử dụng", "Thuốc hết hạn", "Vật dụng y tế dùng một lần", "Que thử đường huyết", "Khẩu trang y tế"]',
    '["Vứt cùng rác thải thông thường", "Không đựng kim tiêm trong hộp kín", "Đổ thuốc xuống bồn cầu"]',
    '["Sử dụng hộp đựng vật sắc nhọn", "Tìm chương trình thu hồi thuốc tại nhà thuốc", "Dán nhãn rõ ràng"]',
    7,
    TRUE
);

-- 8. General Waste (Rác thải thông thường)
INSERT INTO waste_categories (
    category_name,
    category_name_en,
    category_code,
    category_type,
    description,
    description_en,
    handling_instructions,
    disposal_methods,
    recycling_process,
    environmental_impact,
    icon_url,
    color_code,
    points_per_kg,
    carbon_saved_per_kg,
    examples,
    common_mistakes,
    tips,
    sort_order,
    is_active
) VALUES (
    'Rác thải thông thường',
    'General Waste',
    'GEN',
    'general',
    'Chất thải không thể tái chế hoặc phân hủy sinh học, thường phải chôn lấp hoặc đốt',
    'Non-recyclable or non-biodegradable waste, usually sent to landfill or incineration',
    'Giảm thiểu tối đa lượng rác thải, đảm bảo phân loại đúng các loại rác có thể tái chế',
    '["Thu gom rác thải đô thị", "Chôn lấp", "Đốt rác phát điện"]',
    'Chôn lấp hợp vệ sinh hoặc đốt tại nhà máy phát điện từ rác',
    'Tạo ra khí nhà kính từ bãi chôn lấp, chiếm diện tích đất và có thể gây ô nhiễm đất',
    '/assets/icons/waste/general.svg',
    '#607D8B',
    2.00,
    0.10,
    '["Tã lót", "Bọc nilon bẩn", "Đồ dùng nhựa dùng một lần", "Túi đựng đồ ăn dính dầu mỡ", "Giấy vệ sinh", "Vật dụng hỗn hợp không thể tái chế"]',
    '["Vứt lẫn rác có thể tái chế", "Vứt lẫn rác hữu cơ", "Vứt lẫn rác nguy hại"]',
    '["Giảm sử dụng sản phẩm dùng một lần", "Mua sản phẩm có bao bì tái chế được", "Kiểm tra kỹ trước khi vứt bỏ"]',
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
    category_name_en,
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
