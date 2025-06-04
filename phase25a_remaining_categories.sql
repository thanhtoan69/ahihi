-- Phase 25A: Complete Remaining Categories & Sub-categories
-- Environmental Platform Database

USE environmental_platform;

-- =============================================
-- REMAINING SUB-CATEGORIES FOR EXISTING MAIN CATEGORIES
-- =============================================

-- Sub-categories for "Tái Chế" (category_id: 12)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Tái Chế Nhựa', 'Plastic Recycling', 'tai-che/tai-che-nhua', 
 'Hướng dẫn và thông tin về tái chế các sản phẩm nhựa', 
 '/assets/icons/plastic-recycling.png', '#FF6B35', 'article', 
 1, 0, 'Tái Chế Nhựa - Hướng Dẫn Và Thông Tin', 
 'Tìm hiểu cách tái chế nhựa hiệu quả, phân loại nhựa và quy trình tái chế',
 '["tái chế nhựa", "plastic recycling", "phân loại nhựa", "môi trường"]',
 1, 12, 1, 'tai-che/tai-che-nhua'),

('Tái Chế Giấy', 'Paper Recycling', 'tai-che/tai-che-giay', 
 'Thông tin về tái chế giấy và các sản phẩm giấy', 
 '/assets/icons/paper-recycling.png', '#8FBC8F', 'article', 
 1, 0, 'Tái Chế Giấy - Quy Trình Và Lợi Ích', 
 'Hướng dẫn tái chế giấy, quy trình sản xuất giấy tái chế và lợi ích môi trường',
 '["tái chế giấy", "paper recycling", "giấy tái chế", "bảo vệ rừng"]',
 2, 12, 1, 'tai-che/tai-che-giay'),

('Tái Chế Kim Loại', 'Metal Recycling', 'tai-che/tai-che-kim-loai', 
 'Hướng dẫn tái chế kim loại và các hợp kim', 
 '/assets/icons/metal-recycling.png', '#708090', 'article', 
 1, 0, 'Tái Chế Kim Loại - Quy Trình Và Ứng Dụng', 
 'Tìm hiểu về tái chế kim loại, phân loại kim loại và quy trình tái chế hiệu quả',
 '["tái chế kim loại", "metal recycling", "phân loại kim loại", "tái sử dụng"]',
 3, 12, 1, 'tai-che/tai-che-kim-loai');

-- Sub-categories for "Bảo Tồn" (category_id: 13)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Bảo Vệ Rừng', 'Forest Protection', 'bao-ton/bao-ve-rung', 
 'Thông tin về bảo vệ và bảo tồn rừng', 
 '/assets/icons/forest-protection.png', '#228B22', 'article', 
 1, 0, 'Bảo Vệ Rừng - Bảo Tồn Tài Nguyên Thiên Nhiên', 
 'Tìm hiểu về bảo vệ rừng, phòng chống cháy rừng và bảo tồn đa dạng sinh học',
 '["bảo vệ rừng", "forest protection", "bảo tồn", "đa dạng sinh học"]',
 1, 13, 1, 'bao-ton/bao-ve-rung'),

('Bảo Vệ Đại Dương', 'Ocean Protection', 'bao-ton/bao-ve-dai-duong', 
 'Bảo vệ môi trường biển và đại dương', 
 '/assets/icons/ocean-protection.png', '#0077BE', 'article', 
 1, 0, 'Bảo Vệ Đại Dương - Bảo Tồn Hệ Sinh Thái Biển', 
 'Thông tin về bảo vệ đại dương, chống ô nhiễm biển và bảo tồn sinh vật biển',
 '["bảo vệ đại dương", "ocean protection", "ô nhiễm biển", "sinh vật biển"]',
 2, 13, 1, 'bao-ton/bao-ve-dai-duong');

-- Sub-categories for "Biến Đổi Khí Hậu" (category_id: 14)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Giảm Phát Thải', 'Emission Reduction', 'bien-doi-khi-hau/giam-phat-thai', 
 'Các biện pháp giảm phát thải khí nhà kính', 
 '/assets/icons/emission-reduction.png', '#DC143C', 'article', 
 1, 0, 'Giảm Phát Thải - Chống Biến Đổi Khí Hậu', 
 'Tìm hiểu các biện pháp giảm phát thải carbon và khí nhà kính hiệu quả',
 '["giảm phát thải", "emission reduction", "khí nhà kính", "carbon footprint"]',
 1, 14, 1, 'bien-doi-khi-hau/giam-phat-thai'),

('Thích Ứng Khí Hậu', 'Climate Adaptation', 'bien-doi-khi-hau/thich-ung-khi-hau', 
 'Các chiến lược thích ứng với biến đổi khí hậu', 
 '/assets/icons/climate-adaptation.png', '#FF8C00', 'article', 
 1, 0, 'Thích Ứng Khí Hậu - Chiến Lược Sinh Tồn', 
 'Thông tin về các biện pháp thích ứng với tác động của biến đổi khí hậu',
 '["thích ứng khí hậu", "climate adaptation", "biến đổi khí hậu", "sinh tồn"]',
 2, 14, 1, 'bien-doi-khi-hau/thich-ung-khi-hau');

-- =============================================
-- REMAINING SUB-CATEGORIES FOR PRODUCT CATEGORIES
-- =============================================

-- Sub-categories for "Sản Phẩm Xanh" (category_id: 15)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Điện Tử Xanh', 'Green Electronics', 'san-pham-xanh/dien-tu-xanh', 
 'Sản phẩm điện tử thân thiện với môi trường', 
 '/assets/icons/green-electronics.png', '#32CD32', 'product', 
 1, 0, 'Điện Tử Xanh - Công Nghệ Bền Vững', 
 'Khám phá các sản phẩm điện tử tiết kiệm năng lượng và thân thiện môi trường',
 '["điện tử xanh", "green electronics", "tiết kiệm năng lượng", "công nghệ xanh"]',
 1, 15, 1, 'san-pham-xanh/dien-tu-xanh'),

('Mỹ Phẩm Tự Nhiên', 'Natural Cosmetics', 'san-pham-xanh/my-pham-tu-nhien', 
 'Mỹ phẩm từ nguyên liệu tự nhiên', 
 '/assets/icons/natural-cosmetics.png', '#FFB6C1', 'product', 
 1, 0, 'Mỹ Phẩm Tự Nhiên - Làm Đẹp An Toàn', 
 'Sản phẩm mỹ phẩm từ thiên nhiên, không hóa chất độc hại',
 '["mỹ phẩm tự nhiên", "natural cosmetics", "organic beauty", "không độc hại"]',
 2, 15, 1, 'san-pham-xanh/my-pham-tu-nhien');

-- Sub-categories for "Thực Phẩm Hữu Cơ" (category_id: 16)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Rau Củ Hữu Cơ', 'Organic Vegetables', 'thuc-pham-huu-co/rau-cu-huu-co', 
 'Rau củ được trồng theo phương pháp hữu cơ', 
 '/assets/icons/organic-vegetables.png', '#7CFC00', 'product', 
 1, 0, 'Rau Củ Hữu Cơ - Dinh Dưỡng Tự Nhiên', 
 'Rau củ quả hữu cơ tươi ngon, an toàn và giàu dinh dưỡng',
 '["rau củ hữu cơ", "organic vegetables", "thực phẩm sạch", "nông nghiệp hữu cơ"]',
 1, 16, 1, 'thuc-pham-huu-co/rau-cu-huu-co'),

('Thịt Hữu Cơ', 'Organic Meat', 'thuc-pham-huu-co/thit-huu-co', 
 'Thịt từ chăn nuôi hữu cơ', 
 '/assets/icons/organic-meat.png', '#CD853F', 'product', 
 1, 0, 'Thịt Hữu Cơ - Chăn Nuôi Bền Vững', 
 'Sản phẩm thịt từ chăn nuôi hữu cơ, không kháng sinh và hormone',
 '["thịt hữu cơ", "organic meat", "chăn nuôi sạch", "không kháng sinh"]',
 2, 16, 1, 'thuc-pham-huu-co/thit-huu-co');

-- =============================================
-- ADDITIONAL MAIN CATEGORIES
-- =============================================

-- Additional Event Categories
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Triển Lãm Xanh', 'Green Exhibition', 'trien-lam-xanh', 
 'Các triển lãm về sản phẩm và công nghệ xanh', 
 '/assets/icons/green-exhibition.png', '#9ACD32', 'event', 
 1, 1, 'Triển Lãm Xanh - Showcase Công Nghệ Xanh', 
 'Tham gia các triển lãm về sản phẩm xanh, công nghệ môi trường và bền vững',
 '["triển lãm xanh", "green exhibition", "công nghệ xanh", "sản phẩm môi trường"]',
 3, NULL, 0, 'trien-lam-xanh'),

('Khóa Học Trực Tuyến', 'Online Courses', 'khoa-hoc-truc-tuyen', 
 'Các khóa học trực tuyến về môi trường', 
 '/assets/icons/online-courses.png', '#4169E1', 'event', 
 1, 1, 'Khóa Học Trực Tuyến - Giáo Dục Môi Trường', 
 'Tham gia các khóa học trực tuyến về bảo vệ môi trường và phát triển bền vững',
 '["khóa học trực tuyến", "online courses", "giáo dục môi trường", "học tập"]',
 4, NULL, 0, 'khoa-hoc-truc-tuyen');

-- Additional Forum Categories  
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Chia Sẻ Kinh Nghiệm', 'Experience Sharing', 'chia-se-kinh-nghiem', 
 'Chia sẻ kinh nghiệm thực tế về bảo vệ môi trường', 
 '/assets/icons/experience-sharing.png', '#20B2AA', 'forum', 
 1, 1, 'Chia Sẻ Kinh Nghiệm - Học Hỏi Từ Thực Tế', 
 'Chia sẻ và học hỏi kinh nghiệm thực tế trong việc bảo vệ môi trường',
 '["chia sẻ kinh nghiệm", "experience sharing", "thực tế", "học hỏi"]',
 3, NULL, 0, 'chia-se-kinh-nghiem'),

('Dự Án Cộng Đồng', 'Community Projects', 'du-an-cong-dong', 
 'Thảo luận về các dự án môi trường cộng đồng', 
 '/assets/icons/community-projects.png', '#FF69B4', 'forum', 
 1, 1, 'Dự Án Cộng Đồng - Hành Động Tập Thể', 
 'Thảo luận và tổ chức các dự án bảo vệ môi trường trong cộng đồng',
 '["dự án cộng đồng", "community projects", "hành động tập thể", "môi trường"]',
 4, NULL, 0, 'du-an-cong-dong');

-- =============================================
-- SUB-CATEGORIES FOR NEW EVENT CATEGORIES
-- =============================================

-- Sub-categories for "Hội Thảo Môi Trường" (category_id: 18)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Hội Thảo Khoa Học', 'Scientific Conference', 'hoi-thao-moi-truong/hoi-thao-khoa-hoc', 
 'Các hội thảo khoa học về môi trường', 
 '/assets/icons/scientific-conference.png', '#4682B4', 'event', 
 1, 0, 'Hội Thảo Khoa Học - Nghiên Cứu Môi Trường', 
 'Tham gia các hội thảo khoa học về nghiên cứu và bảo vệ môi trường',
 '["hội thảo khoa học", "scientific conference", "nghiên cứu môi trường"]',
 1, 18, 1, 'hoi-thao-moi-truong/hoi-thao-khoa-hoc'),

('Hội Thảo Chính Sách', 'Policy Workshop', 'hoi-thao-moi-truong/hoi-thao-chinh-sach', 
 'Hội thảo về chính sách môi trường', 
 '/assets/icons/policy-workshop.png', '#DAA520', 'event', 
 1, 0, 'Hội Thảo Chính Sách - Luật Môi Trường', 
 'Thảo luận về các chính sách và luật pháp bảo vệ môi trường',
 '["hội thảo chính sách", "policy workshop", "luật môi trường", "chính sách"]',
 2, 18, 1, 'hoi-thao-moi-truong/hoi-thao-chinh-sach');

-- Sub-categories for "Hoạt Động Cộng Đồng" (category_id: 19)
INSERT INTO categories (
    name, name_en, slug, description, icon_url, color_code, category_type, 
    is_active, is_featured, seo_title, seo_description, seo_keywords, 
    sort_order, parent_id, level, path
) VALUES 
('Dọn Dẹp Môi Trường', 'Environmental Cleanup', 'hoat-dong-cong-dong/don-dep-moi-truong', 
 'Các hoạt động dọn dẹp và làm sạch môi trường', 
 '/assets/icons/environmental-cleanup.png', '#32CD32', 'event', 
 1, 0, 'Dọn Dẹp Môi Trường - Hành Động Vì Môi Trường', 
 'Tham gia các hoạt động dọn dẹp bãi biển, công viên và khu vực công cộng',
 '["dọn dẹp môi trường", "environmental cleanup", "tình nguyện", "làm sạch"]',
 1, 19, 1, 'hoat-dong-cong-dong/don-dep-moi-truong'),

('Trồng Cây Xanh', 'Tree Planting', 'hoat-dong-cong-dong/trong-cay-xanh', 
 'Các hoạt động trồng cây và phủ xanh', 
 '/assets/icons/tree-planting.png', '#228B22', 'event', 
 1, 0, 'Trồng Cây Xanh - Phủ Xanh Trái Đất', 
 'Tham gia các hoạt động trồng cây, tạo không gian xanh trong cộng đồng',
 '["trồng cây xanh", "tree planting", "phủ xanh", "cây xanh"]',
 2, 19, 1, 'hoat-dong-cong-dong/trong-cay-xanh');

SELECT 'Phase 25A: All remaining categories have been inserted successfully!' AS status;
