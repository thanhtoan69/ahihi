-- Phase 3-6 Simplified Execution (No Foreign Key Dependencies)
USE environmental_platform;

SET FOREIGN_KEY_CHECKS = 0;

-- Phase 3: Social Platforms
CREATE TABLE IF NOT EXISTS social_platforms (
    platform_id INT PRIMARY KEY AUTO_INCREMENT,
    platform_name VARCHAR(50) UNIQUE NOT NULL,
    platform_display_name VARCHAR(100) NOT NULL,
    platform_color VARCHAR(7),
    share_url_template TEXT,
    points_per_share INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Phase 3: Content Shares
CREATE TABLE IF NOT EXISTS content_shares (
    share_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content_type ENUM('article', 'event', 'product', 'forum_topic') NOT NULL,
    content_id INT NOT NULL,
    content_title VARCHAR(255) NOT NULL,
    platform_id INT NOT NULL,
    share_url TEXT,
    click_count INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    share_status ENUM('pending', 'published', 'verified') DEFAULT 'pending',
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_content (user_id, content_type, content_id),
    INDEX idx_platform_date (platform_id, shared_at)
) ENGINE=InnoDB;

-- Phase 4: Environmental Data Sources
CREATE TABLE IF NOT EXISTS environmental_data_sources (
    source_id INT PRIMARY KEY AUTO_INCREMENT,
    source_name VARCHAR(100) NOT NULL,
    source_type ENUM('api', 'sensor', 'manual', 'import') NOT NULL,
    data_provider VARCHAR(100),
    update_frequency ENUM('realtime', 'hourly', 'daily', 'weekly') DEFAULT 'daily',
    location_coverage ENUM('global', 'country', 'region', 'city', 'local') DEFAULT 'local',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Phase 4: Environmental Data
CREATE TABLE IF NOT EXISTS environmental_data (
    data_id INT PRIMARY KEY AUTO_INCREMENT,
    source_id INT NOT NULL,
    location_name VARCHAR(100),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    data_type ENUM('air_quality', 'water_quality', 'noise_level', 'temperature', 'humidity') NOT NULL,
    measurement_value DECIMAL(10, 3) NOT NULL,
    measurement_unit VARCHAR(20) NOT NULL,
    quality_level ENUM('excellent', 'good', 'moderate', 'poor', 'very_poor') NULL,
    measurement_timestamp TIMESTAMP NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location_type (latitude, longitude, data_type),
    INDEX idx_quality_tracking (quality_level, data_type)
) ENGINE=InnoDB;

-- Phase 4: Carbon Footprints
CREATE TABLE IF NOT EXISTS carbon_footprints (
    footprint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_category ENUM('transport', 'energy', 'waste', 'food', 'consumption') NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    carbon_kg DECIMAL(10, 3) NOT NULL,
    carbon_saved_kg DECIMAL(10, 3) DEFAULT 0,
    activity_date DATE NOT NULL,
    description TEXT,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_activity_date (user_id, activity_date DESC),
    INDEX idx_category_date (activity_category, activity_date DESC)
) ENGINE=InnoDB;

-- Phase 4: Carbon Reduction Goals
CREATE TABLE IF NOT EXISTS carbon_reduction_goals (
    goal_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    goal_type ENUM('daily', 'weekly', 'monthly', 'yearly') DEFAULT 'monthly',
    target_reduction_kg DECIMAL(10, 3) NOT NULL,
    current_reduction_kg DECIMAL(10, 3) DEFAULT 0,
    target_date DATE NOT NULL,
    progress_percentage DECIMAL(5, 2) DEFAULT 0,
    status ENUM('active', 'completed', 'failed', 'paused') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_status (user_id, status),
    INDEX idx_target_date (target_date)
) ENGINE=InnoDB;

-- Phase 5: Waste Categories
CREATE TABLE IF NOT EXISTS waste_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    category_code VARCHAR(10) UNIQUE NOT NULL,
    category_type ENUM('recyclable', 'organic', 'hazardous', 'general', 'electronic') NOT NULL,
    description TEXT,
    color_code VARCHAR(7),
    points_per_kg DECIMAL(5, 2) DEFAULT 10,
    carbon_saved_per_kg DECIMAL(5, 3) DEFAULT 0.5,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_type (category_type),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

-- Phase 5: Waste Items
CREATE TABLE IF NOT EXISTS waste_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    alternative_names JSON,
    description TEXT,
    recycling_instructions TEXT,
    environmental_impact_score INT DEFAULT 50,
    points_value INT DEFAULT 5,
    is_common BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_common (category_id, is_common),
    FULLTEXT(item_name, description)
) ENGINE=InnoDB;

-- Phase 5: Recycling Locations
CREATE TABLE IF NOT EXISTS recycling_locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(100) NOT NULL,
    location_type ENUM('recycling_center', 'collection_point', 'dropoff_station', 'mobile_unit') NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    phone_number VARCHAR(20),
    accepted_categories JSON NOT NULL,
    operating_hours JSON,
    rating_average DECIMAL(3, 2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_geospatial (latitude, longitude),
    INDEX idx_city_type (city, location_type, is_active)
) ENGINE=InnoDB;

-- Phase 6: Forums
CREATE TABLE IF NOT EXISTS forums (
    forum_id INT PRIMARY KEY AUTO_INCREMENT,
    forum_name VARCHAR(100) NOT NULL,
    forum_slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    forum_type ENUM('general', 'environmental', 'recycling', 'energy', 'transport', 'marketplace', 'help') DEFAULT 'general',
    topic_count INT DEFAULT 0,
    post_count INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_active (forum_type, is_active),
    INDEX idx_featured (is_featured, sort_order)
) ENGINE=InnoDB;

-- Phase 6: Forum Topics
CREATE TABLE IF NOT EXISTS forum_topics (
    topic_id INT PRIMARY KEY AUTO_INCREMENT,
    forum_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    topic_type ENUM('discussion', 'question', 'announcement', 'poll', 'guide') DEFAULT 'discussion',
    status ENUM('open', 'locked', 'closed', 'resolved', 'featured') DEFAULT 'open',
    view_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    is_answered BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_forum_status (forum_id, status, updated_at DESC),
    INDEX idx_author_topics (author_id, created_at DESC),
    FULLTEXT(title, content)
) ENGINE=InnoDB;

-- Phase 6: Forum Posts
CREATE TABLE IF NOT EXISTS forum_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    topic_id INT NOT NULL,
    parent_post_id INT,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('post', 'reply', 'quote', 'solution') DEFAULT 'post',
    like_count INT DEFAULT 0,
    is_best_answer BOOLEAN DEFAULT FALSE,
    moderation_status ENUM('approved', 'pending', 'flagged', 'hidden') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_topic_moderation (topic_id, moderation_status, created_at DESC),
    INDEX idx_author_posts (author_id, created_at DESC),
    FULLTEXT(content)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- Insert Sample Data
INSERT IGNORE INTO social_platforms (platform_name, platform_display_name, platform_color, points_per_share, sort_order) VALUES
('facebook', 'Facebook', '#1877F2', 5, 1),
('twitter', 'Twitter/X', '#1DA1F2', 5, 2),
('linkedin', 'LinkedIn', '#0A66C2', 8, 3),
('zalo', 'Zalo', '#005AFF', 6, 4);

INSERT IGNORE INTO environmental_data_sources (source_name, source_type, data_provider, location_coverage) VALUES
('VN Air Quality API', 'api', 'Vietnam Environmental Administration', 'country'),
('Local Weather Stations', 'sensor', 'VN Meteorological Service', 'city'),
('Water Quality Monitoring', 'sensor', 'Water Resources Department', 'region');

INSERT IGNORE INTO waste_categories (category_name, category_code, category_type, description, color_code, points_per_kg) VALUES
('Nhựa', 'PLA', 'recyclable', 'Các sản phẩm làm từ nhựa có thể tái chế', '#FF6B35', 15.0),
('Giấy', 'PAP', 'recyclable', 'Giấy, báo, carton có thể tái chế', '#4ECDC4', 10.0),
('Kim loại', 'MET', 'recyclable', 'Đồ kim loại như lon, sắt, nhôm', '#45B7D1', 25.0),
('Hữu cơ', 'ORG', 'organic', 'Rác thải hữu cơ, thức ăn thừa', '#FFEAA7', 5.0),
('Điện tử', 'ELE', 'electronic', 'Thiết bị điện tử cũ', '#6C5CE7', 50.0);

INSERT IGNORE INTO waste_items (item_name, category_id, description, recycling_instructions, points_value, is_common) VALUES
('Chai nước nhựa', 1, 'Chai đựng nước bằng nhựa PET', 'Rửa sạch, tháo nắp và nhãn', 3, TRUE),
('Báo cũ', 2, 'Báo và tạp chí đã đọc', 'Để khô ráo, loại bỏ băng keo', 2, TRUE),
('Lon nước ngọt', 3, 'Lon đồ uống bằng nhôm', 'Rửa sạch, có thể ép dẹp', 4, TRUE),
('Vỏ trái cây', 4, 'Vỏ cam, chuối và trái cây khác', 'Có thể làm phân compost', 1, TRUE),
('Điện thoại cũ', 5, 'Smartphone không sử dụng', 'Xóa dữ liệu cá nhân trước khi bỏ', 20, FALSE);

INSERT IGNORE INTO forums (forum_name, forum_slug, description, forum_type, sort_order, is_featured) VALUES
('Thảo luận chung', 'thao-luan-chung', 'Khu vực thảo luận chung về môi trường', 'general', 1, TRUE),
('Tái chế & Giảm rác', 'tai-che-giam-rac', 'Chia sẻ kinh nghiệm tái chế', 'recycling', 2, TRUE),
('Năng lượng xanh', 'nang-luong-xanh', 'Thảo luận về năng lượng tái tạo', 'energy', 3, FALSE),
('Marketplace xanh', 'marketplace-xanh', 'Mua bán trao đổi đồ cũ', 'marketplace', 4, TRUE);

INSERT IGNORE INTO forum_topics (forum_id, title, content, author_id, topic_type, status) VALUES
(1, 'Chào mừng đến với cộng đồng môi trường!', 'Đây là nơi chúng ta cùng nhau chia sẻ về bảo vệ môi trường.', 1, 'announcement', 'featured'),
(2, 'Cách tái chế chai nhựa thành chậu trồng cây', 'Chia sẻ cách biến chai nhựa thành chậu trồng cây xinh xắn.', 2, 'guide', 'open'),
(3, 'Có nên lắp pin mặt trời cho nhà phố?', 'Cân nhắc lắp hệ thống pin mặt trời, chi phí và hiệu quả như thế nào?', 1, 'question', 'open');

INSERT IGNORE INTO forum_posts (topic_id, author_id, content, post_type) VALUES
(1, 2, 'Chào mọi người! Rất vui được tham gia cộng đồng này!', 'reply'),
(2, 1, 'Ý tưởng hay! Có thể khoan thêm lỗ thoát nước ở đáy.', 'reply'),
(3, 2, 'ROI khoảng 7-8 năm, nhưng góp phần giảm carbon rất đáng kể.', 'reply');

-- Sample carbon footprint data
INSERT IGNORE INTO carbon_footprints (user_id, activity_category, activity_type, carbon_kg, carbon_saved_kg, activity_date, points_earned) VALUES
(1, 'transport', 'Đi xe bus thay vì xe máy', 2.5, 1.8, CURDATE(), 18),
(1, 'energy', 'Sử dụng đèn LED', 0.8, 0.5, CURDATE(), 5),
(2, 'waste', 'Tái chế chai nhựa', 0.0, 0.3, CURDATE(), 3),
(2, 'food', 'Ăn chay 1 ngày', 3.2, 2.1, CURDATE(), 21);

-- Sample shares
INSERT IGNORE INTO content_shares (user_id, content_type, content_id, content_title, platform_id, points_earned, share_status) VALUES
(1, 'article', 1, '10 Cách đơn giản để bảo vệ môi trường', 1, 5, 'verified'),
(2, 'forum_topic', 2, 'Cách tái chế chai nhựa', 2, 5, 'verified');

SELECT 'Phase 3-6 Complete!' as status;
SELECT 'Database Summary:' as info;
SELECT COUNT(*) as total_tables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'environmental_platform';
