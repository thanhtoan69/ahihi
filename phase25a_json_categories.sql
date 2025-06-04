-- Phase 25A: Insert Categories with JSON Format for SEO Keywords
-- Environmental Platform Categories Setup
USE environmental_platform;

-- Clear existing categories if needed
DELETE FROM categories WHERE category_id > 0;

-- Main Article Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Môi Trường', 'Environment', 'moi-truong', 'Tin tức và thông tin về môi trường, ô nhiễm, bảo vệ thiên nhiên', '/assets/icons/environment.svg', '#2E8B57', 'article', 1, 1, 'Tin Tức Môi Trường - Bảo Vệ Thiên Nhiên', 'Cập nhật tin tức môi trường mới nhất', '["môi trường", "tin tức", "ô nhiễm", "bảo vệ thiên nhiên"]', 1, NULL, 0, 'moi-truong', '/assets/banners/environment.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Năng Lượng Tái Tạo', 'Renewable Energy', 'nang-luong-tai-tao', 'Thông tin về năng lượng sạch, điện mặt trời, điện gió', '/assets/icons/renewable-energy.svg', '#FFD700', 'article', 1, 1, 'Năng Lượng Tái Tạo', 'Tin tức về năng lượng tái tạo', '["năng lượng tái tạo", "điện mặt trời", "điện gió"]', 2, NULL, 0, 'nang-luong-tai-tao', '/assets/banners/renewable-energy.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Tái Chế', 'Recycling', 'tai-che', 'Hướng dẫn tái chế, quản lý chất thải, kinh tế tuần hoàn', '/assets/icons/recycling.svg', '#00CED1', 'article', 1, 1, 'Tái Chế - Quản Lý Chất Thải', 'Hướng dẫn tái chế', '["tái chế", "quản lý chất thải", "phân loại rác"]', 3, NULL, 0, 'tai-che', '/assets/banners/recycling.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Bảo Tồn', 'Conservation', 'bao-ton', 'Bảo tồn thiên nhiên, động vật hoang dã, rừng và biển', '/assets/icons/conservation.svg', '#228B22', 'article', 1, 1, 'Bảo Tồn Thiên Nhiên', 'Tin tức về bảo tồn thiên nhiên', '["bảo tồn", "thiên nhiên", "động vật hoang dã"]', 4, NULL, 0, 'bao-ton', '/assets/banners/conservation.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Biến Đổi Khí Hậu', 'Climate Change', 'bien-doi-khi-hau', 'Tin tức về biến đổi khí hậu, hiệu ứng nhà kính', '/assets/icons/climate-change.svg', '#FF6347', 'article', 1, 1, 'Biến Đổi Khí Hậu', 'Cập nhật về biến đổi khí hậu', '["biến đổi khí hậu", "hiệu ứng nhà kính", "thích ứng khí hậu"]', 5, NULL, 0, 'bien-doi-khi-hau', '/assets/banners/climate-change.jpg', 0);

-- Main Product Categories  
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Sản Phẩm Xanh', 'Green Products', 'san-pham-xanh', 'Các sản phẩm thân thiện với môi trường', '/assets/icons/green-products.svg', '#32CD32', 'product', 1, 1, 'Sản Phẩm Xanh', 'Mua sắm sản phẩm xanh', '["sản phẩm xanh", "thân thiện môi trường"]', 1, NULL, 0, 'san-pham-xanh', '/assets/banners/green-products.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Thực Phẩm Hữu Cơ', 'Organic Food', 'thuc-pham-huu-co', 'Thực phẩm hữu cơ và an toàn', '/assets/icons/organic-food.svg', '#9ACD32', 'product', 1, 1, 'Thực Phẩm Hữu Cơ', 'Thực phẩm hữu cơ an toàn', '["thực phẩm hữu cơ", "an toàn", "không hóa chất"]', 2, NULL, 0, 'thuc-pham-huu-co', '/assets/banners/organic-food.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Đồ Dùng Sinh Thái', 'Eco-Friendly Items', 'do-dung-sinh-thai', 'Đồ dùng thân thiện với môi trường', '/assets/icons/eco-items.svg', '#20B2AA', 'product', 1, 1, 'Đồ Dùng Sinh Thái', 'Đồ dùng sinh thái', '["đồ dùng sinh thái", "thân thiện môi trường"]', 3, NULL, 0, 'do-dung-sinh-thai', '/assets/banners/eco-items.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Thời Trang Bền Vững', 'Sustainable Fashion', 'thoi-trang-ben-vung', 'Thời trang bền vững và đạo đức', '/assets/icons/sustainable-fashion.svg', '#DA70D6', 'product', 1, 1, 'Thời Trang Bền Vững', 'Thời trang bền vững', '["thời trang bền vững", "đạo đức", "thân thiện môi trường"]', 4, NULL, 0, 'thoi-trang-ben-vung', '/assets/banners/sustainable-fashion.jpg', 0);

-- Main Event Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Hội Thảo Môi Trường', 'Environmental Workshops', 'hoi-thao-moi-truong', 'Các hội thảo về môi trường', '/assets/icons/workshop.svg', '#4169E1', 'event', 1, 1, 'Hội Thảo Môi Trường', 'Tham gia hội thảo môi trường', '["hội thảo môi trường", "workshop", "môi trường"]', 1, NULL, 0, 'hoi-thao-moi-truong', '/assets/banners/workshop.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Hoạt Động Cộng Đồng', 'Community Activities', 'hoat-dong-cong-dong', 'Các hoạt động bảo vệ môi trường cộng đồng', '/assets/icons/community.svg', '#FF1493', 'event', 1, 1, 'Hoạt Động Cộng Đồng', 'Hoạt động cộng đồng', '["hoạt động cộng đồng", "bảo vệ môi trường"]', 2, NULL, 0, 'hoat-dong-cong-dong', '/assets/banners/community.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Triển Lãm Xanh', 'Green Exhibitions', 'trien-lam-xanh', 'Triển lãm về công nghệ xanh', '/assets/icons/exhibition.svg', '#00FF7F', 'event', 1, 1, 'Triển Lãm Xanh', 'Tham quan triển lãm xanh', '["triển lãm xanh", "công nghệ xanh"]', 3, NULL, 0, 'trien-lam-xanh', '/assets/banners/exhibition.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Khóa Học Trực Tuyến', 'Online Courses', 'khoa-hoc-truc-tuyen', 'Khóa học trực tuyến về môi trường', '/assets/icons/online-course.svg', '#8A2BE2', 'event', 1, 1, 'Khóa Học Trực Tuyến', 'Tham gia khóa học trực tuyến', '["khóa học trực tuyến", "môi trường", "học online"]', 4, NULL, 0, 'khoa-hoc-truc-tuyen', '/assets/banners/online-course.jpg', 0);

-- Main Forum Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Thảo Luận Chung', 'General Discussion', 'thao-luan-chung', 'Thảo luận chung về môi trường', '/assets/icons/discussion.svg', '#4682B4', 'forum', 1, 1, 'Thảo Luận Chung', 'Diễn đàn thảo luận chung', '["thảo luận", "môi trường", "diễn đàn"]', 1, NULL, 0, 'thao-luan-chung', '/assets/banners/discussion.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Hỏi Đáp Môi Trường', 'Environmental Q&A', 'hoi-dap-moi-truong', 'Hỏi đáp về các vấn đề môi trường', '/assets/icons/qa.svg', '#FF6347', 'forum', 1, 1, 'Hỏi Đáp Môi Trường', 'Diễn đàn hỏi đáp', '["hỏi đáp", "môi trường", "câu hỏi"]', 2, NULL, 0, 'hoi-dap-moi-truong', '/assets/banners/qa.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Chia Sẻ Kinh Nghiệm', 'Experience Sharing', 'chia-se-kinh-nghiem', 'Chia sẻ kinh nghiệm sống xanh', '/assets/icons/sharing.svg', '#32CD32', 'forum', 1, 1, 'Chia Sẻ Kinh Nghiệm', 'Diễn đàn chia sẻ kinh nghiệm', '["chia sẻ", "kinh nghiệm", "sống xanh"]', 3, NULL, 0, 'chia-se-kinh-nghiem', '/assets/banners/sharing.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Dự Án Cộng Đồng', 'Community Projects', 'du-an-cong-dong', 'Thảo luận về các dự án môi trường cộng đồng', '/assets/icons/projects.svg', '#9932CC', 'forum', 1, 1, 'Dự Án Cộng Đồng', 'Diễn đàn dự án cộng đồng', '["dự án cộng đồng", "môi trường", "cộng đồng"]', 4, NULL, 0, 'du-an-cong-dong', '/assets/banners/projects.jpg', 0);

-- Verify the result
SELECT 'Main categories inserted successfully!' as status;
SELECT category_type, COUNT(*) as count FROM categories GROUP BY category_type;
