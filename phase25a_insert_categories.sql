-- ========================================
-- PHASE 25A: INSERT BASIC CATEGORIES DATA
-- Environmental Platform - Content Categories Setup
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Clear existing categories to start fresh
DELETE FROM categories WHERE category_type IN ('article', 'product', 'event', 'forum');

-- ========================================
-- 2. INSERT MAIN ARTICLE CATEGORIES
-- ========================================

-- Main Article Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES

-- Environment Main Category
('Môi Trường', 'Environment', 'moi-truong', 'Tin tức và thông tin về môi trường, ô nhiễm, bảo vệ thiên nhiên', '/assets/icons/environment.svg', '#2E8B57', 'article', 1, 1, 'Tin Tức Môi Trường - Bảo Vệ Thiên Nhiên', 'Cập nhật tin tức môi trường mới nhất, vấn đề ô nhiễm, biến đổi khí hậu và các giải pháp bảo vệ thiên nhiên', 'môi trường, tin tức, ô nhiễm, bảo vệ thiên nhiên', 1, NULL, 0, 'moi-truong', '/assets/banners/environment.jpg', 0),

-- Renewable Energy Main Category
('Năng Lượng Tái Tạo', 'Renewable Energy', 'nang-luong-tai-tao', 'Thông tin về năng lượng sạch, điện mặt trời, điện gió', '/assets/icons/renewable-energy.svg', '#FFD700', 'article', 1, 1, 'Năng Lượng Tái Tạo - Năng Lượng Sạch', 'Tin tức về năng lượng tái tạo, điện mặt trời, điện gió, công nghệ năng lượng sạch mới nhất', 'năng lượng tái tạo, điện mặt trời, điện gió, năng lượng sạch', 2, NULL, 0, 'nang-luong-tai-tao', '/assets/banners/renewable-energy.jpg', 0),

-- Recycling Main Category
('Tái Chế', 'Recycling', 'tai-che', 'Hướng dẫn tái chế, quản lý chất thải, kinh tế tuần hoàn', '/assets/icons/recycling.svg', '#00CED1', 'article', 1, 1, 'Tái Chế - Quản Lý Chất Thải', 'Hướng dẫn tái chế, phân loại rác thải, quản lý chất thải và phát triển kinh tế tuần hoàn', 'tái chế, quản lý chất thải, phân loại rác, kinh tế tuần hoàn', 3, NULL, 0, 'tai-che', '/assets/banners/recycling.jpg', 0),

-- Conservation Main Category
('Bảo Tồn', 'Conservation', 'bao-ton', 'Bảo tồn thiên nhiên, động vật hoang dã, rừng và biển', '/assets/icons/conservation.svg', '#228B22', 'article', 1, 1, 'Bảo Tồn Thiên Nhiên - Động Vật Hoang Dã', 'Tin tức về bảo tồn thiên nhiên, bảo vệ động vật hoang dã, rừng và hệ sinh thái biển', 'bảo tồn, thiên nhiên, động vật hoang dã, rừng, biển', 4, NULL, 0, 'bao-ton', '/assets/banners/conservation.jpg', 0),

-- Climate Change Main Category
('Biến Đổi Khí Hậu', 'Climate Change', 'bien-doi-khi-hau', 'Tin tức về biến đổi khí hậu, hiệu ứng nhà kính, thích ứng khí hậu', '/assets/icons/climate-change.svg', '#FF6347', 'article', 1, 1, 'Biến Đổi Khí Hậu - Hiệu Ứng Nhà Kính', 'Cập nhật về biến đổi khí hậu, hiệu ứng nhà kính, tác động và giải pháp thích ứng', 'biến đổi khí hậu, hiệu ứng nhà kính, thích ứng khí hậu', 5, NULL, 0, 'bien-doi-khi-hau', '/assets/banners/climate-change.jpg', 0);

-- ========================================
-- 3. INSERT SUB-CATEGORIES FOR ARTICLES
-- ========================================

-- Get parent IDs for sub-categories
SET @env_id = (SELECT category_id FROM categories WHERE slug = 'moi-truong' AND category_type = 'article');
SET @energy_id = (SELECT category_id FROM categories WHERE slug = 'nang-luong-tai-tao' AND category_type = 'article');
SET @recycle_id = (SELECT category_id FROM categories WHERE slug = 'tai-che' AND category_type = 'article');
SET @conservation_id = (SELECT category_id FROM categories WHERE slug = 'bao-ton' AND category_type = 'article');
SET @climate_id = (SELECT category_id FROM categories WHERE slug = 'bien-doi-khi-hau' AND category_type = 'article');

-- Environment Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Ô Nhiễm Không Khí', 'Air Pollution', 'o-nhiem-khong-khi', 'Tin tức về ô nhiễm không khí, chất lượng không khí', '/assets/icons/air-pollution.svg', '#696969', 'article', 1, 0, 'Ô Nhiễm Không Khí', 'Tin tức về tình trạng ô nhiễm không khí và các giải pháp cải thiện chất lượng không khí', 'ô nhiễm không khí, chất lượng không khí', 11, @env_id, 1, 'moi-truong/o-nhiem-khong-khi', '/assets/banners/air-pollution.jpg', 0),
('Ô Nhiễm Nước', 'Water Pollution', 'o-nhiem-nuoc', 'Vấn đề ô nhiễm nguồn nước và giải pháp xử lý', '/assets/icons/water-pollution.svg', '#4682B4', 'article', 1, 0, 'Ô Nhiễm Nước', 'Tin tức về ô nhiễm nguồn nước và các biện pháp bảo vệ tài nguyên nước', 'ô nhiễm nước, tài nguyên nước', 12, @env_id, 1, 'moi-truong/o-nhiem-nuoc', '/assets/banners/water-pollution.jpg', 0),
('Ô Nhiễm Đất', 'Soil Pollution', 'o-nhiem-dat', 'Tình trạng ô nhiễm đất và phục hồi đất', '/assets/icons/soil-pollution.svg', '#8B4513', 'article', 1, 0, 'Ô Nhiễm Đất', 'Thông tin về ô nhiễm đất và các phương pháp phục hồi chất lượng đất', 'ô nhiễm đất, phục hồi đất', 13, @env_id, 1, 'moi-truong/o-nhiem-dat', '/assets/banners/soil-pollution.jpg', 0),

-- Renewable Energy Sub-categories
('Điện Mặt Trời', 'Solar Energy', 'dien-mat-troi', 'Công nghệ và ứng dụng điện mặt trời', '/assets/icons/solar.svg', '#FFA500', 'article', 1, 0, 'Điện Mặt Trời', 'Tin tức về công nghệ và ứng dụng năng lượng mặt trời', 'điện mặt trời, năng lượng mặt trời', 21, @energy_id, 1, 'nang-luong-tai-tao/dien-mat-troi', '/assets/banners/solar.jpg', 0),
('Điện Gió', 'Wind Energy', 'dien-gio', 'Công nghệ và phát triển điện gió', '/assets/icons/wind.svg', '#87CEEB', 'article', 1, 0, 'Điện Gió', 'Thông tin về công nghệ và phát triển năng lượng gió', 'điện gió, năng lượng gió', 22, @energy_id, 1, 'nang-luong-tai-tao/dien-gio', '/assets/banners/wind.jpg', 0),
('Thủy Điện', 'Hydroelectric', 'thuy-dien', 'Năng lượng thủy điện và thủy điện nhỏ', '/assets/icons/hydro.svg', '#00BFFF', 'article', 1, 0, 'Thủy Điện', 'Tin tức về năng lượng thủy điện và các dự án thủy điện', 'thủy điện, năng lượng nước', 23, @energy_id, 1, 'nang-luong-tai-tao/thuy-dien', '/assets/banners/hydro.jpg', 0),

-- Recycling Sub-categories
('Tái Chế Nhựa', 'Plastic Recycling', 'tai-che-nhua', 'Hướng dẫn tái chế các sản phẩm nhựa', '/assets/icons/plastic-recycling.svg', '#FF69B4', 'article', 1, 0, 'Tái Chế Nhựa', 'Hướng dẫn và tin tức về tái chế sản phẩm nhựa', 'tái chế nhựa, rác nhựa', 31, @recycle_id, 1, 'tai-che/tai-che-nhua', '/assets/banners/plastic-recycling.jpg', 0),
('Tái Chế Giấy', 'Paper Recycling', 'tai-che-giay', 'Quy trình tái chế giấy và sản phẩm giấy', '/assets/icons/paper-recycling.svg', '#DEB887', 'article', 1, 0, 'Tái Chế Giấy', 'Hướng dẫn tái chế giấy và các sản phẩm từ giấy', 'tái chế giấy, rác giấy', 32, @recycle_id, 1, 'tai-che/tai-che-giay', '/assets/banners/paper-recycling.jpg', 0),
('Tái Chế Kim Loại', 'Metal Recycling', 'tai-che-kim-loai', 'Tái chế kim loại và vật liệu kim loại', '/assets/icons/metal-recycling.svg', '#C0C0C0', 'article', 1, 0, 'Tái Chế Kim Loại', 'Thông tin về tái chế kim loại và vật liệu kim loại', 'tái chế kim loại, phế liệu', 33, @recycle_id, 1, 'tai-che/tai-che-kim-loai', '/assets/banners/metal-recycling.jpg', 0),

-- Conservation Sub-categories
('Bảo Vệ Rừng', 'Forest Protection', 'bao-ve-rung', 'Bảo vệ và phát triển rừng bền vững', '/assets/icons/forest.svg', '#006400', 'article', 1, 0, 'Bảo Vệ Rừng', 'Tin tức về bảo vệ và phát triển rừng bền vững', 'bảo vệ rừng, phát triển bền vững', 41, @conservation_id, 1, 'bao-ton/bao-ve-rung', '/assets/banners/forest.jpg', 0),
('Bảo Vệ Biển', 'Ocean Protection', 'bao-ve-bien', 'Bảo vệ hệ sinh thái biển và động vật biển', '/assets/icons/ocean.svg', '#008B8B', 'article', 1, 0, 'Bảo Vệ Biển', 'Thông tin về bảo vệ hệ sinh thái biển và sinh vật biển', 'bảo vệ biển, hệ sinh thái biển', 42, @conservation_id, 1, 'bao-ton/bao-ve-bien', '/assets/banners/ocean.jpg', 0),

-- Climate Change Sub-categories
('Giảm Khí Thải', 'Emission Reduction', 'giam-khi-thai', 'Các biện pháp giảm phát thải khí nhà kính', '/assets/icons/emission.svg', '#800080', 'article', 1, 0, 'Giảm Khí Thải', 'Tin tức về các biện pháp giảm phát thải khí nhà kính', 'giảm khí thải, khí nhà kính', 51, @climate_id, 1, 'bien-doi-khi-hau/giam-khi-thai', '/assets/banners/emission.jpg', 0);

-- ========================================
-- 4. INSERT MAIN PRODUCT CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES

-- Green Products
('Sản Phẩm Xanh', 'Green Products', 'san-pham-xanh', 'Các sản phẩm thân thiện với môi trường', '/assets/icons/green-products.svg', '#32CD32', 'product', 1, 1, 'Sản Phẩm Xanh - Thân Thiện Môi Trường', 'Mua sắm sản phẩm xanh, thân thiện với môi trường và bền vững', 'sản phẩm xanh, thân thiện môi trường', 1, NULL, 0, 'san-pham-xanh', '/assets/banners/green-products.jpg', 0),

-- Organic Food
('Thực Phẩm Hữu Cơ', 'Organic Food', 'thuc-pham-huu-co', 'Thực phẩm hữu cơ và an toàn', '/assets/icons/organic-food.svg', '#9ACD32', 'product', 1, 1, 'Thực Phẩm Hữu Cơ - An Toàn Sức Khỏe', 'Thực phẩm hữu cơ, an toàn và không hóa chất', 'thực phẩm hữu cơ, an toàn', 2, NULL, 0, 'thuc-pham-huu-co', '/assets/banners/organic-food.jpg', 0),

-- Eco-Friendly Items
('Đồ Dùng Sinh Thái', 'Eco-Friendly Items', 'do-dung-sinh-thai', 'Đồ dùng thân thiện với môi trường', '/assets/icons/eco-items.svg', '#20B2AA', 'product', 1, 1, 'Đồ Dùng Sinh Thái - Thân Thiện Môi Trường', 'Đồ dùng sinh thái, thân thiện với môi trường trong cuộc sống hàng ngày', 'đồ dùng sinh thái, thân thiện môi trường', 3, NULL, 0, 'do-dung-sinh-thai', '/assets/banners/eco-items.jpg', 0),

-- Sustainable Fashion
('Thời Trang Bền Vững', 'Sustainable Fashion', 'thoi-trang-ben-vung', 'Thời trang bền vững và đạo đức', '/assets/icons/sustainable-fashion.svg', '#DA70D6', 'product', 1, 1, 'Thời Trang Bền Vững - Đạo Đức', 'Thời trang bền vững, đạo đức và thân thiện với môi trường', 'thời trang bền vững, đạo đức', 4, NULL, 0, 'thoi-trang-ben-vung', '/assets/banners/sustainable-fashion.jpg', 0);

-- ========================================
-- 5. INSERT PRODUCT SUB-CATEGORIES
-- ========================================

-- Get parent IDs for product sub-categories
SET @green_prod_id = (SELECT category_id FROM categories WHERE slug = 'san-pham-xanh' AND category_type = 'product');
SET @organic_id = (SELECT category_id FROM categories WHERE slug = 'thuc-pham-huu-co' AND category_type = 'product');
SET @eco_items_id = (SELECT category_id FROM categories WHERE slug = 'do-dung-sinh-thai' AND category_type = 'product');
SET @fashion_id = (SELECT category_id FROM categories WHERE slug = 'thoi-trang-ben-vung' AND category_type = 'product');

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES

-- Green Products Sub-categories
('Dụng Cụ Làm Vườn', 'Gardening Tools', 'dung-cu-lam-vuon', 'Dụng cụ làm vườn thân thiện môi trường', '/assets/icons/gardening.svg', '#228B22', 'product', 1, 0, 'Dụng Cụ Làm Vườn Xanh', 'Dụng cụ làm vườn thân thiện với môi trường', 'dụng cụ làm vườn, thân thiện môi trường', 11, @green_prod_id, 1, 'san-pham-xanh/dung-cu-lam-vuon', '/assets/banners/gardening.jpg', 0),
('Năng Lượng Gia Đình', 'Home Energy', 'nang-luong-gia-dinh', 'Thiết bị năng lượng tái tạo cho gia đình', '/assets/icons/home-energy.svg', '#FFD700', 'product', 1, 0, 'Năng Lượng Gia Đình', 'Thiết bị năng lượng tái tạo cho gia đình', 'năng lượng gia đình, thiết bị', 12, @green_prod_id, 1, 'san-pham-xanh/nang-luong-gia-dinh', '/assets/banners/home-energy.jpg', 0),

-- Organic Food Sub-categories
('Rau Củ Hữu Cơ', 'Organic Vegetables', 'rau-cu-huu-co', 'Rau củ hữu cơ tươi ngon', '/assets/icons/vegetables.svg', '#8FBC8F', 'product', 1, 0, 'Rau Củ Hữu Cơ', 'Rau củ hữu cơ tươi ngon và an toàn', 'rau củ hữu cơ, tươi ngon', 21, @organic_id, 1, 'thuc-pham-huu-co/rau-cu-huu-co', '/assets/banners/vegetables.jpg', 0),
('Trái Cây Hữu Cơ', 'Organic Fruits', 'trai-cay-huu-co', 'Trái cây hữu cơ chất lượng cao', '/assets/icons/fruits.svg', '#FF6347', 'product', 1, 0, 'Trái Cây Hữu Cơ', 'Trái cây hữu cơ chất lượng cao và an toàn', 'trái cây hữu cơ, chất lượng', 22, @organic_id, 1, 'thuc-pham-huu-co/trai-cay-huu-co', '/assets/banners/fruits.jpg', 0),
('Thịt Hữu Cơ', 'Organic Meat', 'thit-huu-co', 'Thịt hữu cơ từ chăn nuôi bền vững', '/assets/icons/meat.svg', '#CD853F', 'product', 1, 0, 'Thịt Hữu Cơ', 'Thịt hữu cơ từ chăn nuôi bền vững', 'thịt hữu cơ, chăn nuôi bền vững', 23, @organic_id, 1, 'thuc-pham-huu-co/thit-huu-co', '/assets/banners/meat.jpg', 0),

-- Eco Items Sub-categories
('Túi Thân Thiện', 'Eco-Friendly Bags', 'tui-than-thien', 'Túi tái sử dụng thân thiện môi trường', '/assets/icons/eco-bags.svg', '#8B4513', 'product', 1, 0, 'Túi Thân Thiện Môi Trường', 'Túi tái sử dụng thân thiện với môi trường', 'túi thân thiện, tái sử dụng', 31, @eco_items_id, 1, 'do-dung-sinh-thai/tui-than-thien', '/assets/banners/eco-bags.jpg', 0),
('Đồ Gia Dụng Xanh', 'Green Household', 'do-gia-dung-xanh', 'Đồ gia dụng xanh và bền vững', '/assets/icons/household.svg', '#32CD32', 'product', 1, 0, 'Đồ Gia Dụng Xanh', 'Đồ gia dụng xanh và bền vững cho gia đình', 'đồ gia dụng xanh, bền vững', 32, @eco_items_id, 1, 'do-dung-sinh-thai/do-gia-dung-xanh', '/assets/banners/household.jpg', 0);

-- ========================================
-- 6. INSERT EVENT CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES

-- Main Event Categories
('Hội Thảo Môi Trường', 'Environmental Workshops', 'hoi-thao-moi-truong', 'Các hội thảo và workshop về môi trường', '/assets/icons/workshop.svg', '#4169E1', 'event', 1, 1, 'Hội Thảo Môi Trường', 'Tham gia các hội thảo về môi trường và bền vững', 'hội thảo môi trường, workshop', 1, NULL, 0, 'hoi-thao-moi-truong', '/assets/banners/workshop.jpg', 0),
('Hoạt Động Cộng Đồng', 'Community Activities', 'hoat-dong-cong-dong', 'Các hoạt động bảo vệ môi trường cộng đồng', '/assets/icons/community.svg', '#FF1493', 'event', 1, 1, 'Hoạt Động Cộng Đồng', 'Tham gia các hoạt động bảo vệ môi trường cộng đồng', 'hoạt động cộng đồng, bảo vệ môi trường', 2, NULL, 0, 'hoat-dong-cong-dong', '/assets/banners/community.jpg', 0),
('Triển Lãm Xanh', 'Green Exhibitions', 'trien-lam-xanh', 'Triển lãm về công nghệ xanh và sản phẩm bền vững', '/assets/icons/exhibition.svg', '#00FF7F', 'event', 1, 1, 'Triển Lãm Xanh', 'Tham quan triển lãm công nghệ xanh và sản phẩm bền vững', 'triển lãm xanh, công nghệ', 3, NULL, 0, 'trien-lam-xanh', '/assets/banners/exhibition.jpg', 0),
('Khóa Học Trực Tuyến', 'Online Courses', 'khoa-hoc-truc-tuyen', 'Khóa học trực tuyến về môi trường và bền vững', '/assets/icons/online-course.svg', '#8A2BE2', 'event', 1, 1, 'Khóa Học Trực Tuyến', 'Tham gia khóa học trực tuyến về môi trường', 'khóa học trực tuyến, môi trường', 4, NULL, 0, 'khoa-hoc-truc-tuyen', '/assets/banners/online-course.jpg', 0);

-- ========================================
-- 7. INSERT EVENT SUB-CATEGORIES
-- ========================================

-- Get parent IDs for event sub-categories
SET @workshop_id = (SELECT category_id FROM categories WHERE slug = 'hoi-thao-moi-truong' AND category_type = 'event');
SET @community_id = (SELECT category_id FROM categories WHERE slug = 'hoat-dong-cong-dong' AND category_type = 'event');
SET @exhibition_id = (SELECT category_id FROM categories WHERE slug = 'trien-lam-xanh' AND category_type = 'event');
SET @course_id = (SELECT category_id FROM categories WHERE slug = 'khoa-hoc-truc-tuyen' AND category_type = 'event');

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES

-- Workshop Sub-categories
('Workshop Tái Chế', 'Recycling Workshops', 'workshop-tai-che', 'Workshop hướng dẫn tái chế và DIY', '/assets/icons/recycle-workshop.svg', '#FF8C00', 'event', 1, 0, 'Workshop Tái Chế', 'Workshop hướng dẫn tái chế và làm đồ handmade', 'workshop tái chế, DIY', 11, @workshop_id, 1, 'hoi-thao-moi-truong/workshop-tai-che', '/assets/banners/recycle-workshop.jpg', 0),
('Hội Thảo Khí Hậu', 'Climate Seminars', 'hoi-thao-khi-hau', 'Hội thảo về biến đổi khí hậu', '/assets/icons/climate-seminar.svg', '#DC143C', 'event', 1, 0, 'Hội Thảo Khí Hậu', 'Hội thảo về biến đổi khí hậu và tác động', 'hội thảo khí hậu, biến đổi', 12, @workshop_id, 1, 'hoi-thao-moi-truong/hoi-thao-khi-hau', '/assets/banners/climate-seminar.jpg', 0),

-- Community Sub-categories
('Dọn Dẹp Môi Trường', 'Environmental Cleanup', 'don-dep-moi-truong', 'Hoạt động dọn dẹp môi trường cộng đồng', '/assets/icons/cleanup.svg', '#2E8B57', 'event', 1, 0, 'Dọn Dẹp Môi Trường', 'Tham gia hoạt động dọn dẹp môi trường cộng đồng', 'dọn dẹp môi trường, cộng đồng', 21, @community_id, 1, 'hoat-dong-cong-dong/don-dep-moi-truong', '/assets/banners/cleanup.jpg', 0),
('Trồng Cây Xanh', 'Tree Planting', 'trong-cay-xanh', 'Hoạt động trồng cây và tạo không gian xanh', '/assets/icons/tree-planting.svg', '#228B22', 'event', 1, 0, 'Trồng Cây Xanh', 'Tham gia hoạt động trồng cây và tạo không gian xanh', 'trồng cây, không gian xanh', 22, @community_id, 1, 'hoat-dong-cong-dong/trong-cay-xanh', '/assets/banners/tree-planting.jpg', 0),

-- Exhibition Sub-category
('Công Nghệ Xanh', 'Green Technology', 'cong-nghe-xanh', 'Triển lãm công nghệ xanh mới nhất', '/assets/icons/green-tech.svg', '#00CED1', 'event', 1, 0, 'Công Nghệ Xanh', 'Triển lãm công nghệ xanh và sáng tạo môi trường', 'công nghệ xanh, sáng tạo', 31, @exhibition_id, 1, 'trien-lam-xanh/cong-nghe-xanh', '/assets/banners/green-tech.jpg', 0);

-- ========================================
-- 8. INSERT FORUM CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES

-- Forum Categories
('Thảo Luận Chung', 'General Discussion', 'thao-luan-chung', 'Thảo luận chung về môi trường và cuộc sống xanh', '/assets/icons/discussion.svg', '#4682B4', 'forum', 1, 1, 'Thảo Luận Chung', 'Diễn đàn thảo luận chung về môi trường', 'thảo luận, môi trường', 1, NULL, 0, 'thao-luan-chung', '/assets/banners/discussion.jpg', 0),
('Hỏi Đáp Môi Trường', 'Environmental Q&A', 'hoi-dap-moi-truong', 'Hỏi đáp về các vấn đề môi trường', '/assets/icons/qa.svg', '#FF6347', 'forum', 1, 1, 'Hỏi Đáp Môi Trường', 'Diễn đàn hỏi đáp về các vấn đề môi trường', 'hỏi đáp, môi trường', 2, NULL, 0, 'hoi-dap-moi-truong', '/assets/banners/qa.jpg', 0),
('Chia Sẻ Kinh Nghiệm', 'Experience Sharing', 'chia-se-kinh-nghiem', 'Chia sẻ kinh nghiệm sống xanh', '/assets/icons/sharing.svg', '#32CD32', 'forum', 1, 1, 'Chia Sẻ Kinh Nghiệm', 'Diễn đàn chia sẻ kinh nghiệm sống xanh', 'chia sẻ, kinh nghiệm', 3, NULL, 0, 'chia-se-kinh-nghiem', '/assets/banners/sharing.jpg', 0),
('Dự Án Cộng Đồng', 'Community Projects', 'du-an-cong-dong', 'Thảo luận về các dự án môi trường cộng đồng', '/assets/icons/projects.svg', '#9932CC', 'forum', 1, 1, 'Dự Án Cộng Đồng', 'Diễn đàn thảo luận về dự án môi trường cộng đồng', 'dự án, cộng đồng', 4, NULL, 0, 'du-an-cong-dong', '/assets/banners/projects.jpg', 0);

-- ========================================
-- 9. VERIFICATION QUERIES
-- ========================================

-- Count categories by type and level
SELECT 
    category_type,
    level,
    COUNT(*) as count,
    GROUP_CONCAT(name SEPARATOR ', ') as category_names
FROM categories 
WHERE category_type IN ('article', 'product', 'event', 'forum')
GROUP BY category_type, level
ORDER BY category_type, level;

-- Show hierarchy structure
SELECT 
    c1.name as main_category,
    c1.category_type,
    c2.name as sub_category,
    c2.path
FROM categories c1
LEFT JOIN categories c2 ON c1.category_id = c2.parent_id
WHERE c1.level = 0 AND c1.category_type IN ('article', 'product', 'event', 'forum')
ORDER BY c1.category_type, c1.sort_order, c2.sort_order;

-- Total count
SELECT 
    category_type,
    COUNT(*) as total
FROM categories 
WHERE category_type IN ('article', 'product', 'event', 'forum')
GROUP BY category_type;

-- Success message
SELECT 'Phase 25A: Basic Categories & Configuration completed successfully!' as status,
       COUNT(*) as total_categories_created
FROM categories 
WHERE category_type IN ('article', 'product', 'event', 'forum');
