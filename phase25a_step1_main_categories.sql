-- Phase 25A: Insert Categories - Step by Step
-- Environmental Platform Categories Setup

USE environmental_platform;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

-- Insert Main Article Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Môi Trường', 'Environment', 'moi-truong', 'Tin tức và thông tin về môi trường, ô nhiễm, bảo vệ thiên nhiên', '/assets/icons/environment.svg', '#2E8B57', 'article', 1, 1, 'Tin Tức Môi Trường - Bảo Vệ Thiên Nhiên', 'Cập nhật tin tức môi trường mới nhất, vấn đề ô nhiễm, biến đổi khí hậu và các giải pháp bảo vệ thiên nhiên', 'môi trường, tin tức, ô nhiễm, bảo vệ thiên nhiên', 1, NULL, 0, 'moi-truong', '/assets/banners/environment.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Năng Lượng Tái Tạo', 'Renewable Energy', 'nang-luong-tai-tao', 'Thông tin về năng lượng sạch, điện mặt trời, điện gió', '/assets/icons/renewable-energy.svg', '#FFD700', 'article', 1, 1, 'Năng Lượng Tái Tạo - Năng Lượng Sạch', 'Tin tức về năng lượng tái tạo, điện mặt trời, điện gió, công nghệ năng lượng sạch mới nhất', 'năng lượng tái tạo, điện mặt trời, điện gió, năng lượng sạch', 2, NULL, 0, 'nang-luong-tai-tao', '/assets/banners/renewable-energy.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Tái Chế', 'Recycling', 'tai-che', 'Hướng dẫn tái chế, quản lý chất thải, kinh tế tuần hoàn', '/assets/icons/recycling.svg', '#00CED1', 'article', 1, 1, 'Tái Chế - Quản Lý Chất Thải', 'Hướng dẫn tái chế, phân loại rác thải, quản lý chất thải và phát triển kinh tế tuần hoàn', 'tái chế, quản lý chất thải, phân loại rác, kinh tế tuần hoàn', 3, NULL, 0, 'tai-che', '/assets/banners/recycling.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Bảo Tồn', 'Conservation', 'bao-ton', 'Bảo tồn thiên nhiên, động vật hoang dã, rừng và biển', '/assets/icons/conservation.svg', '#228B22', 'article', 1, 1, 'Bảo Tồn Thiên Nhiên - Động Vật Hoang Dã', 'Tin tức về bảo tồn thiên nhiên, bảo vệ động vật hoang dã, rừng và hệ sinh thái biển', 'bảo tồn, thiên nhiên, động vật hoang dã, rừng, biển', 4, NULL, 0, 'bao-ton', '/assets/banners/conservation.jpg', 0);

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Biến Đổi Khí Hậu', 'Climate Change', 'bien-doi-khi-hau', 'Tin tức về biến đổi khí hậu, hiệu ứng nhà kính, thích ứng khí hậu', '/assets/icons/climate-change.svg', '#FF6347', 'article', 1, 1, 'Biến Đổi Khí Hậu - Hiệu Ứng Nhà Kính', 'Cập nhật về biến đổi khí hậu, hiệu ứng nhà kính, tác động và giải pháp thích ứng', 'biến đổi khí hậu, hiệu ứng nhà kính, thích ứng khí hậu', 5, NULL, 0, 'bien-doi-khi-hau', '/assets/banners/climate-change.jpg', 0);

-- Verify main categories created
SELECT 'Main Article Categories Created:' as status;
SELECT name, slug, category_type FROM categories WHERE category_type = 'article' AND level = 0;
