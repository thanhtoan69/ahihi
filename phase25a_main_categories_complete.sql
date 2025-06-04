-- ========================================
-- PHASE 25A: COMPLETE CATEGORIES SETUP
-- Environmental Platform - All Categories
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Insert remaining main article categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES 
('Nang Luong Tai Tao', 'Renewable Energy', 'nang-luong-tai-tao', 'Thong tin ve nang luong sach', '/assets/icons/renewable-energy.svg', '#FFD700', 'article', 1, 1, 'Nang Luong Tai Tao', 'Tin tuc ve nang luong tai tao', 2, NULL, 0, 'nang-luong-tai-tao', '/assets/banners/renewable-energy.jpg', 0),

('Tai Che', 'Recycling', 'tai-che', 'Huong dan tai che', '/assets/icons/recycling.svg', '#00CED1', 'article', 1, 1, 'Tai Che', 'Huong dan tai che', 3, NULL, 0, 'tai-che', '/assets/banners/recycling.jpg', 0),

('Bao Ton', 'Conservation', 'bao-ton', 'Bao ton thien nhien', '/assets/icons/conservation.svg', '#228B22', 'article', 1, 1, 'Bao Ton Thien Nhien', 'Tin tuc ve bao ton', 4, NULL, 0, 'bao-ton', '/assets/banners/conservation.jpg', 0),

('Bien Doi Khi Hau', 'Climate Change', 'bien-doi-khi-hau', 'Tin tuc ve bien doi khi hau', '/assets/icons/climate-change.svg', '#FF6347', 'article', 1, 1, 'Bien Doi Khi Hau', 'Cap nhat ve bien doi khi hau', 5, NULL, 0, 'bien-doi-khi-hau', '/assets/banners/climate-change.jpg', 0);

-- Insert main product categories  
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES 
('San Pham Xanh', 'Green Products', 'san-pham-xanh', 'Cac san pham than thien voi moi truong', '/assets/icons/green-products.svg', '#32CD32', 'product', 1, 1, 'San Pham Xanh', 'Mua sam san pham xanh', 1, NULL, 0, 'san-pham-xanh', '/assets/banners/green-products.jpg', 0),

('Thuc Pham Huu Co', 'Organic Food', 'thuc-pham-huu-co', 'Thuc pham huu co va an toan', '/assets/icons/organic-food.svg', '#9ACD32', 'product', 1, 1, 'Thuc Pham Huu Co', 'Thuc pham huu co an toan', 2, NULL, 0, 'thuc-pham-huu-co', '/assets/banners/organic-food.jpg', 0),

('Do Dung Sinh Thai', 'Eco-Friendly Items', 'do-dung-sinh-thai', 'Do dung than thien voi moi truong', '/assets/icons/eco-items.svg', '#20B2AA', 'product', 1, 1, 'Do Dung Sinh Thai', 'Do dung sinh thai', 3, NULL, 0, 'do-dung-sinh-thai', '/assets/banners/eco-items.jpg', 0),

('Thoi Trang Ben Vung', 'Sustainable Fashion', 'thoi-trang-ben-vung', 'Thoi trang ben vung va dao duc', '/assets/icons/sustainable-fashion.svg', '#DA70D6', 'product', 1, 1, 'Thoi Trang Ben Vung', 'Thoi trang ben vung', 4, NULL, 0, 'thoi-trang-ben-vung', '/assets/banners/sustainable-fashion.jpg', 0);

-- Insert main event categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES 
('Hoi Thao Moi Truong', 'Environmental Workshops', 'hoi-thao-moi-truong', 'Cac hoi thao ve moi truong', '/assets/icons/workshop.svg', '#4169E1', 'event', 1, 1, 'Hoi Thao Moi Truong', 'Tham gia hoi thao moi truong', 1, NULL, 0, 'hoi-thao-moi-truong', '/assets/banners/workshop.jpg', 0),

('Hoat Dong Cong Dong', 'Community Activities', 'hoat-dong-cong-dong', 'Cac hoat dong bao ve moi truong cong dong', '/assets/icons/community.svg', '#FF1493', 'event', 1, 1, 'Hoat Dong Cong Dong', 'Hoat dong cong dong', 2, NULL, 0, 'hoat-dong-cong-dong', '/assets/banners/community.jpg', 0),

('Trien Lam Xanh', 'Green Exhibitions', 'trien-lam-xanh', 'Trien lam ve cong nghe xanh', '/assets/icons/exhibition.svg', '#00FF7F', 'event', 1, 1, 'Trien Lam Xanh', 'Tham quan trien lam xanh', 3, NULL, 0, 'trien-lam-xanh', '/assets/banners/exhibition.jpg', 0),

('Khoa Hoc Truc Tuyen', 'Online Courses', 'khoa-hoc-truc-tuyen', 'Khoa hoc truc tuyen ve moi truong', '/assets/icons/online-course.svg', '#8A2BE2', 'event', 1, 1, 'Khoa Hoc Truc Tuyen', 'Tham gia khoa hoc truc tuyen', 4, NULL, 0, 'khoa-hoc-truc-tuyen', '/assets/banners/online-course.jpg', 0);

-- Insert main forum categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES 
('Thao Luan Chung', 'General Discussion', 'thao-luan-chung', 'Thao luan chung ve moi truong', '/assets/icons/discussion.svg', '#4682B4', 'forum', 1, 1, 'Thao Luan Chung', 'Dien dan thao luan chung', 1, NULL, 0, 'thao-luan-chung', '/assets/banners/discussion.jpg', 0),

('Hoi Dap Moi Truong', 'Environmental Q&A', 'hoi-dap-moi-truong', 'Hoi dap ve cac van de moi truong', '/assets/icons/qa.svg', '#FF6347', 'forum', 1, 1, 'Hoi Dap Moi Truong', 'Dien dan hoi dap', 2, NULL, 0, 'hoi-dap-moi-truong', '/assets/banners/qa.jpg', 0),

('Chia Se Kinh Nghiem', 'Experience Sharing', 'chia-se-kinh-nghiem', 'Chia se kinh nghiem song xanh', '/assets/icons/sharing.svg', '#32CD32', 'forum', 1, 1, 'Chia Se Kinh Nghiem', 'Dien dan chia se kinh nghiem', 3, NULL, 0, 'chia-se-kinh-nghiem', '/assets/banners/sharing.jpg', 0),

('Du An Cong Dong', 'Community Projects', 'du-an-cong-dong', 'Thao luan ve cac du an moi truong cong dong', '/assets/icons/projects.svg', '#9932CC', 'forum', 1, 1, 'Du An Cong Dong', 'Dien dan du an cong dong', 4, NULL, 0, 'du-an-cong-dong', '/assets/banners/projects.jpg', 0);

-- Verify main categories count
SELECT 'Main categories created!' as status;
SELECT category_type, COUNT(*) as count FROM categories WHERE level = 0 GROUP BY category_type;
