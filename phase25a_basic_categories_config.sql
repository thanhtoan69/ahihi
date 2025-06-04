-- ========================================
-- PHASE 25A: BASIC CATEGORIES & CONFIGURATION
-- Environmental Platform - Content Categories Setup
-- Generated: June 3, 2025
-- ========================================

USE environmental_platform;

-- Clear existing categories if needed (optional - uncomment if needed)
-- DELETE FROM categories WHERE category_id > 0;
-- ALTER TABLE categories AUTO_INCREMENT = 1;

-- ========================================
-- 1. MAIN ARTICLE CATEGORIES
-- ========================================

-- Parent Categories for Articles
INSERT INTO categories (name, name_en, slug, description, icon_url, banner_image_url, color_code, parent_id, category_type, level, path, sort_order, is_featured, is_active, seo_title, seo_description, seo_keywords) VALUES
-- Main Environmental Category
('Môi trường', 'Environment', 'moi-truong', 'Tin tức, kiến thức và thông tin về bảo vệ môi trường, sinh thái và phát triển bền vững', '/icons/leaf.svg', '/banners/environment.jpg', '#22c55e', NULL, 'article', 0, '/moi-truong', 1, TRUE, TRUE, 'Tin tức môi trường - Kiến thức sinh thái', 'Cập nhật tin tức môi trường mới nhất, kiến thức về sinh thái và bảo vệ thiên nhiên', '["môi trường", "sinh thái", "bảo vệ thiên nhiên", "phát triển bền vững"]'),

-- Energy Category
('Năng lượng tái tạo', 'Renewable Energy', 'nang-luong-tai-tao', 'Thông tin về năng lượng sạch, năng lượng tái tạo và công nghệ xanh', '/icons/sun.svg', '/banners/renewable-energy.jpg', '#f59e0b', NULL, 'article', 0, '/nang-luong-tai-tao', 2, TRUE, TRUE, 'Năng lượng tái tạo - Công nghệ xanh', 'Tin tức và kiến thức về năng lượng mặt trời, gió, thủy điện và các công nghệ năng lượng sạch', '["năng lượng tái tạo", "năng lượng mặt trời", "năng lượng gió", "công nghệ xanh"]'),

-- Recycling Category
('Tái chế', 'Recycling', 'tai-che', 'Hướng dẫn tái chế, xử lý rác thải và kinh tế tuần hoàn', '/icons/recycle.svg', '/banners/recycling.jpg', '#3b82f6', NULL, 'article', 0, '/tai-che', 3, TRUE, TRUE, 'Tái chế - Xử lý rác thải bền vững', 'Hướng dẫn tái chế rác thải, phân loại rác và xây dựng kinh tế tuần hoàn', '["tái chế", "xử lý rác thải", "phân loại rác", "kinh tế tuần hoàn"]'),

-- Conservation Category
('Bảo tồn thiên nhiên', 'Nature Conservation', 'bao-ton-thien-nhien', 'Bảo vệ động thực vật, rừng và đa dạng sinh học', '/icons/tree.svg', '/banners/conservation.jpg', '#059669', NULL, 'article', 0, '/bao-ton-thien-nhien', 4, TRUE, TRUE, 'Bảo tồn thiên nhiên - Đa dạng sinh học', 'Thông tin về bảo vệ động thực vật, rừng và duy trì đa dạng sinh học', '["bảo tồn", "động vật hoang dã", "rừng", "đa dạng sinh học"]'),

-- Climate Change Category
('Biến đổi khí hậu', 'Climate Change', 'bien-doi-khi-hau', 'Thông tin về biến đổi khí hậu và các giải pháp ứng phó', '/icons/globe.svg', '/banners/climate-change.jpg', '#dc2626', NULL, 'article', 0, '/bien-doi-khi-hau', 5, TRUE, TRUE, 'Biến đổi khí hậu - Ứng phó và giải pháp', 'Tin tức về biến đổi khí hậu, tác động và các giải pháp ứng phó', '["biến đổi khí hậu", "nóng lên toàn cầu", "khí nhà kính", "ứng phó khí hậu"]');

-- ========================================
-- 2. SUB-CATEGORIES FOR ARTICLES
-- ========================================

-- Environment Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, parent_id, category_type, level, path, sort_order, is_active) VALUES
-- Environment subcategories
('Ô nhiễm không khí', 'Air Pollution', 'o-nhiem-khong-khi', 'Thông tin về ô nhiễm không khí và các giải pháp cải thiện', '/icons/wind.svg', '#ef4444', 1, 'article', 1, '/moi-truong/o-nhiem-khong-khi', 1, TRUE),
('Ô nhiễm nước', 'Water Pollution', 'o-nhiem-nuoc', 'Vấn đề ô nhiễm nguồn nước và bảo vệ tài nguyên nước', '/icons/droplet.svg', '#06b6d4', 1, 'article', 1, '/moi-truong/o-nhiem-nuoc', 2, TRUE),
('Ô nhiễm đất', 'Soil Pollution', 'o-nhiem-dat', 'Tình trạng ô nhiễm đất và phương pháp phục hồi', '/icons/mountain.svg', '#a3a3a3', 1, 'article', 1, '/moi-truong/o-nhiem-dat', 3, TRUE),
('Quản lý rác thải', 'Waste Management', 'quan-ly-rac-thai', 'Hệ thống quản lý và xử lý rác thải hiệu quả', '/icons/trash.svg', '#f97316', 1, 'article', 1, '/moi-truong/quan-ly-rac-thai', 4, TRUE),

-- Renewable Energy subcategories
('Năng lượng mặt trời', 'Solar Energy', 'nang-luong-mat-troi', 'Công nghệ và ứng dụng năng lượng mặt trời', '/icons/sun.svg', '#fbbf24', 2, 'article', 1, '/nang-luong-tai-tao/nang-luong-mat-troi', 1, TRUE),
('Năng lượng gió', 'Wind Energy', 'nang-luong-gio', 'Phát triển và ứng dụng năng lượng gió', '/icons/wind-turbine.svg', '#60a5fa', 2, 'article', 1, '/nang-luong-tai-tao/nang-luong-gio', 2, TRUE),
('Năng lượng thủy điện', 'Hydroelectric Power', 'nang-luong-thuy-dien', 'Thủy điện và năng lượng từ nước', '/icons/water.svg', '#3b82f6', 2, 'article', 1, '/nang-luong-tai-tao/nang-luong-thuy-dien', 3, TRUE),
('Năng lượng sinh khối', 'Biomass Energy', 'nang-luong-sinh-khoi', 'Năng lượng từ sinh khối và chất thải hữu cơ', '/icons/leaf.svg', '#84cc16', 2, 'article', 1, '/nang-luong-tai-tao/nang-luong-sinh-khoi', 4, TRUE),

-- Recycling subcategories
('Tái chế nhựa', 'Plastic Recycling', 'tai-che-nhua', 'Hướng dẫn tái chế các loại nhựa', '/icons/bottle.svg', '#3b82f6', 3, 'article', 1, '/tai-che/tai-che-nhua', 1, TRUE),
('Tái chế giấy', 'Paper Recycling', 'tai-che-giay', 'Quy trình tái chế giấy và các sản phẩm từ giấy', '/icons/file.svg', '#a3a3a3', 3, 'article', 1, '/tai-che/tai-che-giay', 2, TRUE),
('Tái chế kim loại', 'Metal Recycling', 'tai-che-kim-loai', 'Tái chế kim loại và hợp kim', '/icons/cpu.svg', '#6b7280', 3, 'article', 1, '/tai-che/tai-che-kim-loai', 3, TRUE),
('Tái chế điện tử', 'Electronics Recycling', 'tai-che-dien-tu', 'Xử lý và tái chế thiết bị điện tử', '/icons/smartphone.svg', '#8b5cf6', 3, 'article', 1, '/tai-che/tai-che-dien-tu', 4, TRUE);

-- ========================================
-- 3. PRODUCT CATEGORIES
-- ========================================

-- Main Product Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, banner_image_url, color_code, parent_id, category_type, level, path, sort_order, is_featured, is_active, seo_title, seo_description) VALUES
-- Green Products Main Category
('Sản phẩm xanh', 'Green Products', 'san-pham-xanh', 'Sản phẩm thân thiện với môi trường và bền vững', '/icons/shopping-bag.svg', '/banners/green-products.jpg', '#10b981', NULL, 'product', 0, '/san-pham-xanh', 1, TRUE, TRUE, 'Sản phẩm xanh - Mua sắm bền vững', 'Khám phá các sản phẩm thân thiện môi trường, organic và bền vững'),

-- Organic Food Category
('Thực phẩm hữu cơ', 'Organic Food', 'thuc-pham-huu-co', 'Thực phẩm hữu cơ và sạch', '/icons/apple.svg', '/banners/organic-food.jpg', '#84cc16', NULL, 'product', 0, '/thuc-pham-huu-co', 2, TRUE, TRUE, 'Thực phẩm hữu cơ - Organic Food', 'Thực phẩm hữu cơ tươi ngon, an toàn cho sức khỏe'),

-- Eco-friendly Items
('Đồ dùng sinh thái', 'Eco-friendly Items', 'do-dung-sinh-thai', 'Đồ dùng thân thiện với môi trường', '/icons/leaf.svg', '/banners/eco-items.jpg', '#059669', NULL, 'product', 0, '/do-dung-sinh-thai', 3, TRUE, TRUE, 'Đồ dùng sinh thái - Eco Items', 'Đồ dùng thân thiện môi trường cho cuộc sống xanh'),

-- Renewable Energy Products
('Thiết bị năng lượng tái tạo', 'Renewable Energy Devices', 'thiet-bi-nang-luong-tai-tao', 'Thiết bị và công nghệ năng lượng tái tạo', '/icons/battery.svg', '/banners/energy-devices.jpg', '#f59e0b', NULL, 'product', 0, '/thiet-bi-nang-luong-tai-tao', 4, FALSE, TRUE, 'Thiết bị năng lượng tái tạo', 'Thiết bị năng lượng mặt trời, gió và các công nghệ xanh');

-- Product Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, parent_id, category_type, level, path, sort_order, is_active) VALUES
-- Green Products subcategories
('Mỹ phẩm tự nhiên', 'Natural Cosmetics', 'my-pham-tu-nhien', 'Mỹ phẩm từ thiên nhiên, không hóa chất', '/icons/heart.svg', '#ec4899', (SELECT category_id FROM categories WHERE slug = 'san-pham-xanh'), 'product', 1, '/san-pham-xanh/my-pham-tu-nhien', 1, TRUE),
('Quần áo bền vững', 'Sustainable Clothing', 'quan-ao-ben-vung', 'Thời trang bền vững và thân thiện môi trường', '/icons/shirt.svg', '#8b5cf6', (SELECT category_id FROM categories WHERE slug = 'san-pham-xanh'), 'product', 1, '/san-pham-xanh/quan-ao-ben-vung', 2, TRUE),
('Túi và bao bì sinh thái', 'Eco Bags & Packaging', 'tui-bao-bi-sinh-thai', 'Túi và bao bì có thể phân hủy', '/icons/shopping-bag.svg', '#06b6d4', (SELECT category_id FROM categories WHERE slug = 'san-pham-xanh'), 'product', 1, '/san-pham-xanh/tui-bao-bi-sinh-thai', 3, TRUE),

-- Organic Food subcategories
('Rau củ hữu cơ', 'Organic Vegetables', 'rau-cu-huu-co', 'Rau củ quả hữu cơ tươi sạch', '/icons/carrot.svg', '#84cc16', (SELECT category_id FROM categories WHERE slug = 'thuc-pham-huu-co'), 'product', 1, '/thuc-pham-huu-co/rau-cu-huu-co', 1, TRUE),
('Thực phẩm chế biến hữu cơ', 'Processed Organic Food', 'thuc-pham-che-bien-huu-co', 'Thực phẩm chế biến hữu cơ', '/icons/package.svg', '#f97316', (SELECT category_id FROM categories WHERE slug = 'thuc-pham-huu-co'), 'product', 1, '/thuc-pham-huu-co/thuc-pham-che-bien-huu-co', 2, TRUE),

-- Eco Items subcategories
('Đồ gia dụng tre', 'Bamboo Household Items', 'do-gia-dung-tre', 'Đồ gia dụng làm từ tre', '/icons/utensils.svg', '#a3a3a3', (SELECT category_id FROM categories WHERE slug = 'do-dung-sinh-thai'), 'product', 1, '/do-dung-sinh-thai/do-gia-dung-tre', 1, TRUE),
('Chai lọ tái sử dụng', 'Reusable Containers', 'chai-lo-tai-su-dung', 'Chai lọ có thể tái sử dụng', '/icons/bottle.svg', '#3b82f6', (SELECT category_id FROM categories WHERE slug = 'do-dung-sinh-thai'), 'product', 1, '/do-dung-sinh-thai/chai-lo-tai-su-dung', 2, TRUE);

-- ========================================
-- 4. EVENT CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, banner_image_url, color_code, parent_id, category_type, level, path, sort_order, is_featured, is_active, seo_title, seo_description) VALUES
-- Main Event Categories
('Sự kiện môi trường', 'Environmental Events', 'su-kien-moi-truong', 'Hội thảo, triển lãm và hoạt động bảo vệ môi trường', '/icons/calendar.svg', '/banners/environmental-events.jpg', '#8b5cf6', NULL, 'event', 0, '/su-kien-moi-truong', 1, TRUE, TRUE, 'Sự kiện môi trường - Hoạt động xanh', 'Tham gia các sự kiện, hội thảo về môi trường và phát triển bền vững'),

('Hội thảo giáo dục', 'Educational Workshops', 'hoi-thao-giao-duc', 'Khóa học và workshop về môi trường', '/icons/book.svg', '/banners/workshops.jpg', '#3b82f6', NULL, 'event', 0, '/hoi-thao-giao-duc', 2, TRUE, TRUE, 'Hội thảo giáo dục môi trường', 'Tham gia các khóa học và workshop về bảo vệ môi trường'),

('Hoạt động cộng đồng', 'Community Activities', 'hoat-dong-cong-dong', 'Các hoạt động tình nguyện bảo vệ môi trường', '/icons/users.svg', '/banners/community.jpg', '#10b981', NULL, 'event', 0, '/hoat-dong-cong-dong', 3, TRUE, TRUE, 'Hoạt động cộng đồng xanh', 'Tham gia các hoạt động tình nguyện bảo vệ môi trường'),

('Triển lãm xanh', 'Green Exhibitions', 'trien-lam-xanh', 'Triển lãm sản phẩm và công nghệ xanh', '/icons/building.svg', '/banners/exhibitions.jpg', '#f59e0b', NULL, 'event', 0, '/trien-lam-xanh', 4, FALSE, TRUE, 'Triển lãm xanh - Green Expo', 'Khám phá các triển lãm sản phẩm và công nghệ thân thiện môi trường');

-- Event Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, parent_id, category_type, level, path, sort_order, is_active) VALUES
-- Environmental Events subcategories
('Dọn dẹp môi trường', 'Environmental Cleanup', 'don-dep-moi-truong', 'Hoạt động dọn dẹp bãi biển, rừng, sông', '/icons/trash.svg', '#ef4444', (SELECT category_id FROM categories WHERE slug = 'su-kien-moi-truong'), 'event', 1, '/su-kien-moi-truong/don-dep-moi-truong', 1, TRUE),
('Trồng cây xanh', 'Tree Planting', 'trong-cay-xanh', 'Hoạt động trồng cây và tạo rừng', '/icons/tree.svg', '#22c55e', (SELECT category_id FROM categories WHERE slug = 'su-kien-moi-truong'), 'event', 1, '/su-kien-moi-truong/trong-cay-xanh', 2, TRUE),
('Chiến dịch tuyên truyền', 'Awareness Campaigns', 'chien-dich-tuyen-truyen', 'Chiến dịch nâng cao nhận thức môi trường', '/icons/megaphone.svg', '#f97316', (SELECT category_id FROM categories WHERE slug = 'su-kien-moi-truong'), 'event', 1, '/su-kien-moi-truong/chien-dich-tuyen-truyen', 3, TRUE),

-- Workshop subcategories
('Khóa học tái chế', 'Recycling Courses', 'khoa-hoc-tai-che', 'Học cách tái chế và làm đồ handmade', '/icons/recycle.svg', '#3b82f6', (SELECT category_id FROM categories WHERE slug = 'hoi-thao-giao-duc'), 'event', 1, '/hoi-thao-giao-duc/khoa-hoc-tai-che', 1, TRUE),
('Workshop nông nghiệp hữu cơ', 'Organic Farming Workshop', 'workshop-nong-nghiep-huu-co', 'Học cách trồng trọt hữu cơ', '/icons/sprout.svg', '#84cc16', (SELECT category_id FROM categories WHERE slug = 'hoi-thao-giao-duc'), 'event', 1, '/hoi-thao-giao-duc/workshop-nong-nghiep-huu-co', 2, TRUE);

-- ========================================
-- 5. FORUM CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, banner_image_url, color_code, parent_id, category_type, level, path, sort_order, is_featured, is_active, seo_title, seo_description) VALUES
-- Main Forum Categories
('Thảo luận chung', 'General Discussion', 'thao-luan-chung', 'Thảo luận tổng quát về môi trường', '/icons/message-circle.svg', '/banners/discussion.jpg', '#6b7280', NULL, 'forum', 0, '/thao-luan-chung', 1, TRUE, TRUE, 'Diễn đàn thảo luận môi trường', 'Nơi chia sẻ và thảo luận về các vấn đề môi trường'),

('Hỏi đáp chuyên gia', 'Expert Q&A', 'hoi-dap-chuyen-gia', 'Đặt câu hỏi và nhận tư vấn từ chuyên gia', '/icons/help-circle.svg', '/banners/expert-qa.jpg', '#8b5cf6', NULL, 'forum', 0, '/hoi-dap-chuyen-gia', 2, TRUE, TRUE, 'Hỏi đáp chuyên gia môi trường', 'Đặt câu hỏi và nhận lời khuyên từ các chuyên gia môi trường'),

('Chia sẻ kinh nghiệm', 'Experience Sharing', 'chia-se-kinh-nghiem', 'Chia sẻ kinh nghiệm sống xanh', '/icons/share.svg', '/banners/sharing.jpg', '#10b981', NULL, 'forum', 0, '/chia-se-kinh-nghiem', 3, TRUE, TRUE, 'Chia sẻ kinh nghiệm sống xanh', 'Chia sẻ tips và kinh nghiệm sống thân thiện với môi trường'),

('Góc sáng tạo', 'Creative Corner', 'goc-sang-tao', 'Chia sẻ ý tưởng sáng tạo về môi trường', '/icons/lightbulb.svg', '/banners/creative.jpg', '#f59e0b', NULL, 'forum', 0, '/goc-sang-tao', 4, FALSE, TRUE, 'Góc sáng tạo môi trường', 'Chia sẻ các ý tưởng sáng tạo bảo vệ môi trường');

-- ========================================
-- 6. UPDATE CATEGORY COUNTS AND PATHS
-- ========================================

-- Update post counts for all categories (set to 0 initially)
UPDATE categories SET post_count = 0;

-- Update paths for sub-categories
UPDATE categories c1 
JOIN categories c2 ON c1.parent_id = c2.category_id 
SET c1.path = CONCAT(c2.path, '/', c1.slug) 
WHERE c1.parent_id IS NOT NULL;

-- Update level for sub-categories
UPDATE categories SET level = 1 WHERE parent_id IS NOT NULL;
UPDATE categories SET level = 0 WHERE parent_id IS NULL;

-- ========================================
-- 7. SEO AND METADATA UPDATES
-- ========================================

-- Update SEO keywords for main categories
UPDATE categories SET seo_keywords = JSON_ARRAY('môi trường', 'ô nhiễm', 'sinh thái', 'bảo vệ thiên nhiên') 
WHERE slug = 'moi-truong';

UPDATE categories SET seo_keywords = JSON_ARRAY('năng lượng tái tạo', 'năng lượng sạch', 'solar', 'wind power') 
WHERE slug = 'nang-luong-tai-tao';

UPDATE categories SET seo_keywords = JSON_ARRAY('tái chế', 'xử lý rác', 'recycle', 'kinh tế tuần hoàn') 
WHERE slug = 'tai-che';

UPDATE categories SET seo_keywords = JSON_ARRAY('bảo tồn', 'đa dạng sinh học', 'động vật hoang dã', 'rừng') 
WHERE slug = 'bao-ton-thien-nhien';

UPDATE categories SET seo_keywords = JSON_ARRAY('biến đổi khí hậu', 'nóng lên toàn cầu', 'climate change') 
WHERE slug = 'bien-doi-khi-hau';

-- ========================================
-- 8. VERIFICATION QUERIES
-- ========================================

-- Check category hierarchy
SELECT 
    c1.name as parent_category,
    c2.name as sub_category,
    c2.category_type,
    c2.level,
    c2.path
FROM categories c1
RIGHT JOIN categories c2 ON c1.category_id = c2.parent_id
ORDER BY c1.sort_order, c2.sort_order;

-- Count categories by type
SELECT 
    category_type,
    COUNT(*) as total_categories,
    COUNT(CASE WHEN parent_id IS NULL THEN 1 END) as main_categories,
    COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as sub_categories
FROM categories 
WHERE is_active = TRUE
GROUP BY category_type;

-- Display all categories with their details
SELECT 
    category_id,
    name,
    slug,
    category_type,
    level,
    CASE WHEN parent_id IS NULL THEN 'Main Category' ELSE 'Sub Category' END as hierarchy,
    color_code,
    is_featured,
    sort_order
FROM categories 
WHERE is_active = TRUE
ORDER BY category_type, level, sort_order;

-- ========================================
-- PHASE 25A COMPLETION SUMMARY
-- ========================================

SELECT 
    'PHASE 25A: BASIC CATEGORIES & CONFIGURATION' as phase_name,
    'COMPLETED SUCCESSFULLY' as status,
    COUNT(*) as total_categories_created,
    NOW() as completion_time
FROM categories;

-- Show category statistics
SELECT 
    'Article Categories' as type, COUNT(*) as count FROM categories WHERE category_type = 'article' AND is_active = TRUE
UNION ALL
SELECT 
    'Product Categories' as type, COUNT(*) as count FROM categories WHERE category_type = 'product' AND is_active = TRUE
UNION ALL
SELECT 
    'Event Categories' as type, COUNT(*) as count FROM categories WHERE category_type = 'event' AND is_active = TRUE
UNION ALL
SELECT 
    'Forum Categories' as type, COUNT(*) as count FROM categories WHERE category_type = 'forum' AND is_active = TRUE;

-- ========================================
-- END OF PHASE 25A
-- ========================================

/*
📊 PHASE 25A COMPLETION SUMMARY:

✅ CREATED CATEGORIES:
- Article Categories: 17 (5 main + 12 sub)
- Product Categories: 11 (4 main + 7 sub)  
- Event Categories: 9 (4 main + 5 sub)
- Forum Categories: 4 (4 main)

🎯 FEATURES IMPLEMENTED:
- Vietnamese names and descriptions
- SEO-friendly slugs
- Color-coded categories
- Icon assignments
- Banner image paths
- Category hierarchy (parent-child)
- Path generation
- Sort ordering
- Featured category flags
- Multi-language support (Vietnamese/English)

🔧 CONFIGURATION:
- All categories are active by default
- Proper category types assigned
- Level-based hierarchy
- SEO metadata included
- Post count initialization

Ready for content population! 🚀
*/
