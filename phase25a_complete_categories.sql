-- ========================================
-- PHASE 25A: COMPLETE CATEGORIES SETUP
-- Environmental Platform - All Categories
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- INSERT MAIN ARTICLE CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Moi Truong', 'Environment', 'moi-truong', 'Tin tuc va thong tin ve moi truong, o nhiem, bao ve thien nhien', '/assets/icons/environment.svg', '#2E8B57', 'article', 1, 1, 'Tin Tuc Moi Truong - Bao Ve Thien Nhien', 'Cap nhat tin tuc moi truong moi nhat, van de o nhiem, bien doi khi hau va cac giai phap bao ve thien nhien', 'moi truong, tin tuc, o nhiem, bao ve thien nhien', 1, NULL, 0, 'moi-truong', '/assets/banners/environment.jpg', 0),

('Nang Luong Tai Tao', 'Renewable Energy', 'nang-luong-tai-tao', 'Thong tin ve nang luong sach, dien mat troi, dien gio', '/assets/icons/renewable-energy.svg', '#FFD700', 'article', 1, 1, 'Nang Luong Tai Tao - Nang Luong Sach', 'Tin tuc ve nang luong tai tao, dien mat troi, dien gio, cong nghe nang luong sach moi nhat', 'nang luong tai tao, dien mat troi, dien gio, nang luong sach', 2, NULL, 0, 'nang-luong-tai-tao', '/assets/banners/renewable-energy.jpg', 0),

('Tai Che', 'Recycling', 'tai-che', 'Huong dan tai che, quan ly chat thai, kinh te tuan hoan', '/assets/icons/recycling.svg', '#00CED1', 'article', 1, 1, 'Tai Che - Quan Ly Chat Thai', 'Huong dan tai che, phan loai rac thai, quan ly chat thai va phat trien kinh te tuan hoan', 'tai che, quan ly chat thai, phan loai rac, kinh te tuan hoan', 3, NULL, 0, 'tai-che', '/assets/banners/recycling.jpg', 0),

('Bao Ton', 'Conservation', 'bao-ton', 'Bao ton thien nhien, dong vat hoang da, rung va bien', '/assets/icons/conservation.svg', '#228B22', 'article', 1, 1, 'Bao Ton Thien Nhien - Dong Vat Hoang Da', 'Tin tuc ve bao ton thien nhien, bao ve dong vat hoang da, rung va he sinh thai bien', 'bao ton, thien nhien, dong vat hoang da, rung, bien', 4, NULL, 0, 'bao-ton', '/assets/banners/conservation.jpg', 0),

('Bien Doi Khi Hau', 'Climate Change', 'bien-doi-khi-hau', 'Tin tuc ve bien doi khi hau, hieu ung nha kinh, thich ung khi hau', '/assets/icons/climate-change.svg', '#FF6347', 'article', 1, 1, 'Bien Doi Khi Hau - Hieu Ung Nha Kinh', 'Cap nhat ve bien doi khi hau, hieu ung nha kinh, tac dong va giai phap thich ung', 'bien doi khi hau, hieu ung nha kinh, thich ung khi hau', 5, NULL, 0, 'bien-doi-khi-hau', '/assets/banners/climate-change.jpg', 0);

-- ========================================
-- INSERT ARTICLE SUB-CATEGORIES
-- ========================================

-- Get parent IDs for article sub-categories
SET @env_id = (SELECT category_id FROM categories WHERE slug = 'moi-truong' AND category_type = 'article');
SET @energy_id = (SELECT category_id FROM categories WHERE slug = 'nang-luong-tai-tao' AND category_type = 'article');
SET @recycle_id = (SELECT category_id FROM categories WHERE slug = 'tai-che' AND category_type = 'article');
SET @conservation_id = (SELECT category_id FROM categories WHERE slug = 'bao-ton' AND category_type = 'article');
SET @climate_id = (SELECT category_id FROM categories WHERE slug = 'bien-doi-khi-hau' AND category_type = 'article');

-- Environment Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('O Nhiem Khong Khi', 'Air Pollution', 'o-nhiem-khong-khi', 'Tin tuc ve o nhiem khong khi, chat luong khong khi', '/assets/icons/air-pollution.svg', '#696969', 'article', 1, 0, 'O Nhiem Khong Khi', 'Tin tuc ve tinh trang o nhiem khong khi va cac giai phap cai thien chat luong khong khi', 'o nhiem khong khi, chat luong khong khi', 11, @env_id, 1, 'moi-truong/o-nhiem-khong-khi', '/assets/banners/air-pollution.jpg', 0),

('O Nhiem Nuoc', 'Water Pollution', 'o-nhiem-nuoc', 'Van de o nhiem nguon nuoc va giai phap xu ly', '/assets/icons/water-pollution.svg', '#4682B4', 'article', 1, 0, 'O Nhiem Nuoc', 'Tin tuc ve o nhiem nguon nuoc va cac bien phap bao ve tai nguyen nuoc', 'o nhiem nuoc, tai nguyen nuoc', 12, @env_id, 1, 'moi-truong/o-nhiem-nuoc', '/assets/banners/water-pollution.jpg', 0),

('O Nhiem Dat', 'Soil Pollution', 'o-nhiem-dat', 'Tinh trang o nhiem dat va phuc hoi dat', '/assets/icons/soil-pollution.svg', '#8B4513', 'article', 1, 0, 'O Nhiem Dat', 'Thong tin ve o nhiem dat va cac phuong phap phuc hoi chat luong dat', 'o nhiem dat, phuc hoi dat', 13, @env_id, 1, 'moi-truong/o-nhiem-dat', '/assets/banners/soil-pollution.jpg', 0);

-- Renewable Energy Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Dien Mat Troi', 'Solar Energy', 'dien-mat-troi', 'Cong nghe va ung dung dien mat troi', '/assets/icons/solar.svg', '#FFA500', 'article', 1, 0, 'Dien Mat Troi', 'Tin tuc ve cong nghe va ung dung nang luong mat troi', 'dien mat troi, nang luong mat troi', 21, @energy_id, 1, 'nang-luong-tai-tao/dien-mat-troi', '/assets/banners/solar.jpg', 0),

('Dien Gio', 'Wind Energy', 'dien-gio', 'Cong nghe va phat trien dien gio', '/assets/icons/wind.svg', '#87CEEB', 'article', 1, 0, 'Dien Gio', 'Thong tin ve cong nghe va phat trien nang luong gio', 'dien gio, nang luong gio', 22, @energy_id, 1, 'nang-luong-tai-tao/dien-gio', '/assets/banners/wind.jpg', 0),

('Thuy Dien', 'Hydroelectric', 'thuy-dien', 'Nang luong thuy dien va thuy dien nho', '/assets/icons/hydro.svg', '#00BFFF', 'article', 1, 0, 'Thuy Dien', 'Tin tuc ve nang luong thuy dien va cac du an thuy dien', 'thuy dien, nang luong nuoc', 23, @energy_id, 1, 'nang-luong-tai-tao/thuy-dien', '/assets/banners/hydro.jpg', 0);

-- Recycling Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Tai Che Nhua', 'Plastic Recycling', 'tai-che-nhua', 'Huong dan tai che cac san pham nhua', '/assets/icons/plastic-recycling.svg', '#FF69B4', 'article', 1, 0, 'Tai Che Nhua', 'Huong dan va tin tuc ve tai che san pham nhua', 'tai che nhua, rac nhua', 31, @recycle_id, 1, 'tai-che/tai-che-nhua', '/assets/banners/plastic-recycling.jpg', 0),

('Tai Che Giay', 'Paper Recycling', 'tai-che-giay', 'Quy trinh tai che giay va san pham giay', '/assets/icons/paper-recycling.svg', '#DEB887', 'article', 1, 0, 'Tai Che Giay', 'Huong dan tai che giay va cac san pham tu giay', 'tai che giay, rac giay', 32, @recycle_id, 1, 'tai-che/tai-che-giay', '/assets/banners/paper-recycling.jpg', 0),

('Tai Che Kim Loai', 'Metal Recycling', 'tai-che-kim-loai', 'Tai che kim loai va vat lieu kim loai', '/assets/icons/metal-recycling.svg', '#C0C0C0', 'article', 1, 0, 'Tai Che Kim Loai', 'Thong tin ve tai che kim loai va vat lieu kim loai', 'tai che kim loai, phe lieu', 33, @recycle_id, 1, 'tai-che/tai-che-kim-loai', '/assets/banners/metal-recycling.jpg', 0);

-- Conservation Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Bao Ve Rung', 'Forest Protection', 'bao-ve-rung', 'Bao ve va phat trien rung ben vung', '/assets/icons/forest.svg', '#006400', 'article', 1, 0, 'Bao Ve Rung', 'Tin tuc ve bao ve va phat trien rung ben vung', 'bao ve rung, phat trien ben vung', 41, @conservation_id, 1, 'bao-ton/bao-ve-rung', '/assets/banners/forest.jpg', 0),

('Bao Ve Bien', 'Ocean Protection', 'bao-ve-bien', 'Bao ve he sinh thai bien va dong vat bien', '/assets/icons/ocean.svg', '#008B8B', 'article', 1, 0, 'Bao Ve Bien', 'Thong tin ve bao ve he sinh thai bien va sinh vat bien', 'bao ve bien, he sinh thai bien', 42, @conservation_id, 1, 'bao-ton/bao-ve-bien', '/assets/banners/ocean.jpg', 0);

-- Climate Change Sub-categories
INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('Giam Khi Thai', 'Emission Reduction', 'giam-khi-thai', 'Cac bien phap giam phat thai khi nha kinh', '/assets/icons/emission.svg', '#800080', 'article', 1, 0, 'Giam Khi Thai', 'Tin tuc ve cac bien phap giam phat thai khi nha kinh', 'giam khi thai, khi nha kinh', 51, @climate_id, 1, 'bien-doi-khi-hau/giam-khi-thai', '/assets/banners/emission.jpg', 0);

-- ========================================
-- INSERT MAIN PRODUCT CATEGORIES
-- ========================================

INSERT INTO categories (name, name_en, slug, description, icon_url, color_code, category_type, is_active, is_featured, seo_title, seo_description, seo_keywords, sort_order, parent_id, level, path, banner_image_url, post_count) VALUES
('San Pham Xanh', 'Green Products', 'san-pham-xanh', 'Cac san pham than thien voi moi truong', '/assets/icons/green-products.svg', '#32CD32', 'product', 1, 1, 'San Pham Xanh - Than Thien Moi Truong', 'Mua sam san pham xanh, than thien voi moi truong va ben vung', 'san pham xanh, than thien moi truong', 1, NULL, 0, 'san-pham-xanh', '/assets/banners/green-products.jpg', 0),

('Thuc Pham Huu Co', 'Organic Food', 'thuc-pham-huu-co', 'Thuc pham huu co va an toan', '/assets/icons/organic-food.svg', '#9ACD32', 'product', 1, 1, 'Thuc Pham Huu Co - An Toan Suc Khoe', 'Thuc pham huu co, an toan va khong hoa chat', 'thuc pham huu co, an toan', 2, NULL, 0, 'thuc-pham-huu-co', '/assets/banners/organic-food.jpg', 0),

('Do Dung Sinh Thai', 'Eco-Friendly Items', 'do-dung-sinh-thai', 'Do dung than thien voi moi truong', '/assets/icons/eco-items.svg', '#20B2AA', 'product', 1, 1, 'Do Dung Sinh Thai - Than Thien Moi Truong', 'Do dung sinh thai, than thien voi moi truong trong cuoc song hang ngay', 'do dung sinh thai, than thien moi truong', 3, NULL, 0, 'do-dung-sinh-thai', '/assets/banners/eco-items.jpg', 0),

('Thoi Trang Ben Vung', 'Sustainable Fashion', 'thoi-trang-ben-vung', 'Thoi trang ben vung va dao duc', '/assets/icons/sustainable-fashion.svg', '#DA70D6', 'product', 1, 1, 'Thoi Trang Ben Vung - Dao Duc', 'Thoi trang ben vung, dao duc va than thien voi moi truong', 'thoi trang ben vung, dao duc', 4, NULL, 0, 'thoi-trang-ben-vung', '/assets/banners/sustainable-fashion.jpg', 0);

-- Success Message
SELECT 'Phase 25A Categories Setup Completed!' as status, COUNT(*) as total_categories FROM categories WHERE category_type IN ('article', 'product', 'event', 'forum');
