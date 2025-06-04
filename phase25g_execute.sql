-- Phase 25G: Exchange Categories Execution
USE environmental_platform;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Insert main categories
INSERT INTO exchange_categories (category_name, category_slug, parent_category_id, description, eco_impact_score, is_active) VALUES
('Đồ Điện Tử', 'do-dien-tu', NULL, 'Thiết bị điện tử, công nghệ và phụ kiện', 85, 1),
('Thời Trang & Phụ Kiện', 'thoi-trang-phu-kien', NULL, 'Quần áo, giày dép, túi xách và phụ kiện thời trang', 70, 1),
('Nhà Cửa & Nội Thất', 'nha-cua-noi-that', NULL, 'Đồ nội thất, đồ gia dụng và trang trí nhà cửa', 75, 1),
('Sách & Giáo Dục', 'sach-giao-duc', NULL, 'Sách vở, giáo trình và tài liệu học tập', 90, 1),
('Đồ Chơi & Trẻ Em', 'do-choi-tre-em', NULL, 'Đồ chơi, quần áo trẻ em và đồ dùng cho bé', 80, 1),
('Thể Thao & Ngoài Trời', 'the-thao-ngoai-troi', NULL, 'Dụng cụ thể thao, đồ cắm trại và hoạt động ngoài trời', 85, 1),
('Làm Đẹp & Sức Khỏe', 'lam-dep-suc-khoe', NULL, 'Mỹ phẩm, dụng cụ làm đẹp và chăm sóc sức khỏe', 60, 1),
('Xe Cộ & Phương Tiện', 'xe-co-phuong-tien', NULL, 'Xe đạp, xe máy, ô tô và phụ tùng', 95, 1),
('Cây Cảnh & Làm Vườn', 'cay-canh-lam-vuon', NULL, 'Cây cảnh, hạt giống và dụng cụ làm vườn', 100, 1),
('Nghệ Thuật & Thủ Công', 'nghe-thuat-thu-cong', NULL, 'Đồ nghệ thuật, thủ công mỹ nghệ và vật liệu sáng tạo', 70, 1),
('Âm Nhạc & Nhạc Cụ', 'am-nhac-nhac-cu', NULL, 'Nhạc cụ, thiết bị âm thanh và phụ kiện âm nhạc', 75, 1),
('Thực Phẩm & Nấu Ăn', 'thuc-pham-nau-an', NULL, 'Dụng cụ nấu ăn, đồ gia vị và thực phẩm đóng gói', 65, 1),
('Văn Phòng & Kinh Doanh', 'van-phong-kinh-doanh', NULL, 'Đồ văn phòng, thiết bị kinh doanh và tài liệu', 80, 1),
('Sưu Tập & Đồ Cổ', 'suu-tap-do-co', NULL, 'Đồ sưu tập, đồ cổ và vật phẩm có giá trị', 85, 1),
('Linh Tinh & Khác', 'linh-tinh-khac', NULL, 'Các mặt hàng khác không thuộc danh mục trên', 50, 1);
