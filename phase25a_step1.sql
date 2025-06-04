-- Phase 25A: Step-by-step remaining categories
-- Add remaining sub-categories for Recycling (Tái Chế)

USE environmental_platform;

-- Tái Chế Nhựa (Plastic Recycling)
INSERT INTO categories 
(name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path) 
VALUES 
('Tái Chế Nhựa', 'Plastic Recycling', 'tai-che-nhua', 'Hướng dẫn tái chế các sản phẩm nhựa', '/assets/icons/plastic-recycling.png', '#FF6B35', 'article', 1, 0, 'Tái Chế Nhựa', 'Hướng dẫn tái chế nhựa hiệu quả', '["tái chế nhựa"]', 1, 12, 1, 'tai-che/tai-che-nhua');

-- Tái Chế Giấy (Paper Recycling)  
INSERT INTO categories 
(name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path) 
VALUES 
('Tái Chế Giấy', 'Paper Recycling', 'tai-che-giay', 'Thông tin về tái chế giấy', '/assets/icons/paper-recycling.png', '#8FBC8F', 'article', 1, 0, 'Tái Chế Giấy', 'Hướng dẫn tái chế giấy', '["tái chế giấy"]', 2, 12, 1, 'tai-che/tai-che-giay');

-- Tái Chế Kim Loại (Metal Recycling)
INSERT INTO categories 
(name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path) 
VALUES 
('Tái Chế Kim Loại', 'Metal Recycling', 'tai-che-kim-loai', 'Hướng dẫn tái chế kim loại', '/assets/icons/metal-recycling.png', '#708090', 'article', 1, 0, 'Tái Chế Kim Loại', 'Tái chế kim loại hiệu quả', '["tái chế kim loại"]', 3, 12, 1, 'tai-che/tai-che-kim-loai');

SELECT 'Added 3 sub-categories for Tái Chế' AS result;
