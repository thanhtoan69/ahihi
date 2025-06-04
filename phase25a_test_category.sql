-- Phase 25A: Insert Categories with JSON Format for SEO Keywords
-- Environmental Platform Categories Setup
USE environmental_platform;

-- Main Article Categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) 
VALUES ('Môi Trường', 'Environment', 'moi-truong', 'Tin tức về môi trường', '/assets/icons/environment.svg', '#2E8B57', 'article', 1, 1, 'Tin Tức Môi Trường', 'Cập nhật tin tức môi trường', '[]', 1, NULL, 0, 'moi-truong', '/assets/banners/environment.jpg', 0);

-- Verify the result
SELECT 'Category inserted successfully!' as status;
SELECT * FROM categories;
