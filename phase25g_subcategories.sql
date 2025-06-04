-- Phase 25G: Sub-Categories Insertion
-- Electronics Sub-categories
SET @electronics_id = (SELECT category_id FROM exchange_categories WHERE category_slug = 'do-dien-tu');
INSERT INTO exchange_categories (category_name, category_slug, parent_category_id, description, eco_impact_score, is_active) VALUES
('Điện Thoại & Tablet', 'dien-thoai-tablet', @electronics_id, 'Smartphone, tablet và phụ kiện', 85, 1),
('Laptop & Máy Tính', 'laptop-may-tinh', @electronics_id, 'Laptop, PC và linh kiện máy tính', 90, 1),
('TV & Âm Thanh', 'tv-am-thanh', @electronics_id, 'Tivi, loa, tai nghe và thiết bị âm thanh', 80, 1),
('Máy Ảnh & Quay Phim', 'may-anh-quay-phim', @electronics_id, 'Máy ảnh, máy quay và phụ kiện', 85, 1),
('Đồ Gia Dụng Điện', 'do-gia-dung-dien', @electronics_id, 'Máy giặt, tủ lạnh và thiết bị gia đình', 75, 1),
('Phụ Kiện Điện Tử', 'phu-kien-dien-tu', @electronics_id, 'Cáp sạc, ốp lưng, bao da và phụ kiện', 70, 1);

-- Fashion Sub-categories  
SET @fashion_id = (SELECT category_id FROM exchange_categories WHERE category_slug = 'thoi-trang-phu-kien');
INSERT INTO exchange_categories (category_name, category_slug, parent_category_id, description, eco_impact_score, is_active) VALUES
('Thời Trang Nam', 'thoi-trang-nam', @fashion_id, 'Quần áo, giày dép nam', 70, 1),
('Thời Trang Nữ', 'thoi-trang-nu', @fashion_id, 'Quần áo, giày dép nữ', 70, 1),
('Giày Dép', 'giay-dep', @fashion_id, 'Giày, dép, sandal các loại', 65, 1),
('Túi Xách & Ví', 'tui-xach-vi', @fashion_id, 'Túi xách, balo, ví và phụ kiện', 70, 1),
('Đồng Hồ & Trang Sức', 'dong-ho-trang-suc', @fashion_id, 'Đồng hồ, nhẫn, dây chuyền', 75, 1),
('Kính Mắt', 'kinh-mat', @fashion_id, 'Kính cận, kính râm và phụ kiện', 65, 1);

-- Home & Furniture Sub-categories
SET @home_id = (SELECT category_id FROM exchange_categories WHERE category_slug = 'nha-cua-noi-that');
INSERT INTO exchange_categories (category_name, category_slug, parent_category_id, description, eco_impact_score, is_active) VALUES
('Nội Thất Phòng Khách', 'noi-that-phong-khach', @home_id, 'Sofa, bàn, tủ tivi và trang trí', 80, 1),
('Nội Thất Phòng Ngủ', 'noi-that-phong-ngu', @home_id, 'Giường, tủ quần áo, bàn trang điểm', 80, 1),
('Đồ Dùng Nhà Bếp', 'do-dung-nha-bep', @home_id, 'Nồi, chảo, ly tách và dụng cụ nấu ăn', 70, 1),
('Trang Trí Nội Thất', 'trang-tri-noi-that', @home_id, 'Tranh, đèn, gương và đồ trang trí', 65, 1),
('Dụng Cụ Làm Vườn', 'dung-cu-lam-vuon', @home_id, 'Xẻng, cào, vòi xịt và dụng cụ', 85, 1),
('Đồ Dùng Nhà Tắm', 'do-dung-nha-tam', @home_id, 'Khăn tắm, rèm, gương và phụ kiện', 60, 1);
