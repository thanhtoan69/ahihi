-- ========================================
-- ENVIRONMENTAL PLATFORM - PHASE 3-6: COMPLETE SOCIAL & ENVIRONMENTAL FEATURES
-- Version: 3.0 Advanced Features
-- Features: Social Sharing, Environmental Data, Waste Management, Forums
-- ========================================

USE environmental_platform;

-- ========================================
-- PHASE 3: SOCIAL SHARING & VIRAL TRACKING SYSTEM
-- ========================================

-- Social Platforms Configuration
CREATE TABLE IF NOT EXISTS social_platforms (
    platform_id INT PRIMARY KEY AUTO_INCREMENT,
    platform_name VARCHAR(50) UNIQUE NOT NULL,
    platform_display_name VARCHAR(100) NOT NULL,
    platform_icon VARCHAR(255),
    platform_color VARCHAR(7),
    base_url VARCHAR(255),
    share_url_template TEXT,
    api_endpoint VARCHAR(255),
    api_key_encrypted VARCHAR(255),
    points_per_share INT DEFAULT 5,
    points_per_click INT DEFAULT 1,
    points_per_conversion INT DEFAULT 10,
    max_shares_per_day INT DEFAULT 10,
    max_shares_per_content INT DEFAULT 3,
    tracking_enabled BOOLEAN DEFAULT TRUE,
    requires_auth BOOLEAN DEFAULT FALSE,
    auth_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

-- Content Shares with Viral Tracking
CREATE TABLE IF NOT EXISTS content_shares (
    share_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content_type ENUM('article', 'event', 'exchange_post', 'donation_campaign', 'petition', 'product', 'forum_topic', 'achievement') NOT NULL,
    content_id INT NOT NULL,
    content_title VARCHAR(255) NOT NULL,
    content_url VARCHAR(500) NOT NULL,
    short_url VARCHAR(255),
    platform_id INT NOT NULL,
    platform_name VARCHAR(50) NOT NULL,
    share_url TEXT,
    share_text TEXT,
    custom_message TEXT,
    hashtags JSON,
    mentions JSON,
    campaign_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_info JSON,
    location_shared_from VARCHAR(100),
    referrer_url VARCHAR(500),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    utm_content VARCHAR(100),
    points_earned INT DEFAULT 0,
    bonus_points INT DEFAULT 0,
    click_count INT DEFAULT 0,
    unique_clicks INT DEFAULT 0,
    conversion_count INT DEFAULT 0,
    engagement_score DECIMAL(5, 2) DEFAULT 0,
    viral_coefficient DECIMAL(3, 2) DEFAULT 0,
    share_reach INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method ENUM('api_callback', 'pixel_tracking', 'url_redirect', 'manual', 'blockchain') DEFAULT 'url_redirect',
    verification_data JSON,
    share_status ENUM('pending', 'published', 'verified', 'failed', 'removed', 'spam') DEFAULT 'pending',
    quality_score DECIMAL(3, 2) DEFAULT 0,
    share_metadata JSON,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    verified_at TIMESTAMP NULL,
    last_tracked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES social_platforms(platform_id) ON DELETE CASCADE,
    INDEX idx_user_content (user_id, content_type, content_id),
    INDEX idx_platform_date (platform_id, shared_at),
    INDEX idx_content_shares (content_type, content_id, share_status),
    INDEX idx_verification_status (share_status, verification_method),
    INDEX idx_user_daily_shares (user_id, shared_at, platform_id),
    INDEX idx_viral_shares (viral_coefficient DESC, share_reach DESC),
    INDEX idx_short_url (short_url),
    UNIQUE KEY unique_user_content_platform_daily (user_id, content_type, content_id, platform_id, DATE(shared_at))
) ENGINE=InnoDB;

-- Share Clicks Analytics
CREATE TABLE IF NOT EXISTS share_clicks (
    click_id INT PRIMARY KEY AUTO_INCREMENT,
    share_id INT NOT NULL,
    clicked_user_id INT,
    visitor_id VARCHAR(255),
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    referrer_url VARCHAR(500),
    landing_page VARCHAR(500),
    country VARCHAR(50),
    city VARCHAR(100),
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
    browser VARCHAR(50),
    os VARCHAR(50),
    is_unique_visitor BOOLEAN DEFAULT TRUE,
    session_duration INT,
    pages_viewed INT DEFAULT 1,
    converted BOOLEAN DEFAULT FALSE,
    conversion_type ENUM('signup', 'purchase', 'download', 'subscribe', 'share', 'like', 'comment') NULL,
    conversion_value DECIMAL(10, 2) DEFAULT 0,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (share_id) REFERENCES content_shares(share_id) ON DELETE CASCADE,
    FOREIGN KEY (clicked_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_share_clicks (share_id, clicked_at DESC),
    INDEX idx_visitor_clicks (visitor_id, clicked_at DESC),
    INDEX idx_conversion_tracking (converted, conversion_type, clicked_at)
) ENGINE=InnoDB;

-- Share Campaigns for Marketing
CREATE TABLE IF NOT EXISTS share_campaigns (
    campaign_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_name VARCHAR(100) NOT NULL,
    campaign_description TEXT,
    campaign_type ENUM('viral', 'awareness', 'engagement', 'conversion', 'seasonal') DEFAULT 'awareness',
    target_content_types JSON,
    reward_structure JSON,
    bonus_multiplier DECIMAL(3, 2) DEFAULT 1.0,
    min_shares_for_bonus INT DEFAULT 5,
    max_bonus_per_user INT DEFAULT 1000,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    budget_allocated DECIMAL(12, 2) DEFAULT 0,
    budget_spent DECIMAL(12, 2) DEFAULT 0,
    target_shares INT DEFAULT 1000,
    actual_shares INT DEFAULT 0,
    target_reach INT DEFAULT 10000,
    actual_reach INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_campaign_dates (start_date, end_date, is_active),
    INDEX idx_campaign_type (campaign_type, is_active)
) ENGINE=InnoDB;

-- ========================================
-- PHASE 4: ENVIRONMENTAL DATA & MONITORING
-- ========================================

-- Environmental Data Sources
CREATE TABLE IF NOT EXISTS environmental_data_sources (
    source_id INT PRIMARY KEY AUTO_INCREMENT,
    source_name VARCHAR(100) NOT NULL,
    source_type ENUM('api', 'sensor', 'manual', 'import', 'calculated') NOT NULL,
    data_provider VARCHAR(100),
    api_endpoint VARCHAR(500),
    api_key_encrypted VARCHAR(255),
    update_frequency ENUM('realtime', 'hourly', 'daily', 'weekly', 'monthly') DEFAULT 'daily',
    data_format ENUM('json', 'xml', 'csv', 'binary') DEFAULT 'json',
    location_coverage ENUM('global', 'country', 'region', 'city', 'local') DEFAULT 'local',
    data_categories JSON,
    reliability_score DECIMAL(3, 2) DEFAULT 0.8,
    last_sync TIMESTAMP NULL,
    sync_status ENUM('active', 'error', 'disabled', 'maintenance') DEFAULT 'active',
    error_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_source_type (source_type, is_active),
    INDEX idx_sync_status (sync_status, last_sync)
) ENGINE=InnoDB;

-- Real-time Environmental Data
CREATE TABLE IF NOT EXISTS environmental_data (
    data_id INT PRIMARY KEY AUTO_INCREMENT,
    source_id INT NOT NULL,
    location_name VARCHAR(100),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    data_type ENUM('air_quality', 'water_quality', 'noise_level', 'temperature', 'humidity', 'uv_index', 'carbon_levels', 'pollution_index') NOT NULL,
    measurement_value DECIMAL(10, 3) NOT NULL,
    measurement_unit VARCHAR(20) NOT NULL,
    quality_level ENUM('excellent', 'good', 'moderate', 'poor', 'very_poor', 'hazardous') NULL,
    alert_level ENUM('normal', 'warning', 'critical') DEFAULT 'normal',
    raw_data JSON,
    processed_data JSON,
    confidence_score DECIMAL(3, 2) DEFAULT 1.0,
    measurement_timestamp TIMESTAMP NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES environmental_data_sources(source_id),
    INDEX idx_location_type_time (latitude, longitude, data_type, measurement_timestamp DESC),
    INDEX idx_alert_monitoring (alert_level, data_type, measurement_timestamp DESC),
    INDEX idx_quality_tracking (quality_level, data_type, location_name)
) ENGINE=InnoDB
PARTITION BY RANGE (YEAR(measurement_timestamp)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Carbon Footprints Personal Tracking
CREATE TABLE IF NOT EXISTS carbon_footprints (
    footprint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_category ENUM('transport', 'energy', 'waste', 'food', 'consumption', 'travel', 'housing') NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    activity_description TEXT,
    carbon_kg DECIMAL(10, 3) NOT NULL,
    carbon_saved_kg DECIMAL(10, 3) DEFAULT 0,
    activity_date DATE NOT NULL,
    location VARCHAR(100),
    distance_km DECIMAL(8, 2),
    quantity_value DECIMAL(10, 3),
    quantity_unit VARCHAR(20),
    calculation_method ENUM('standard', 'custom', 'api', 'ai_estimated') DEFAULT 'standard',
    calculation_factors JSON,
    verification_status ENUM('unverified', 'self_reported', 'verified', 'auto_calculated') DEFAULT 'self_reported',
    metadata JSON,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_activity_date (user_id, activity_date DESC),
    INDEX idx_category_date (activity_category, activity_date DESC),
    INDEX idx_carbon_saved (carbon_saved_kg DESC, activity_date DESC)
) ENGINE=InnoDB;

-- Carbon Reduction Goals
CREATE TABLE IF NOT EXISTS carbon_reduction_goals (
    goal_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    goal_description TEXT,
    goal_type ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') DEFAULT 'monthly',
    target_reduction_kg DECIMAL(10, 3) NOT NULL,
    current_reduction_kg DECIMAL(10, 3) DEFAULT 0,
    baseline_emissions_kg DECIMAL(10, 3),
    target_date DATE NOT NULL,
    categories_targeted JSON,
    progress_percentage DECIMAL(5, 2) DEFAULT 0,
    status ENUM('active', 'completed', 'failed', 'paused') DEFAULT 'active',
    reminder_frequency ENUM('daily', 'weekly', 'none') DEFAULT 'weekly',
    reward_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_target_date (target_date),
    INDEX idx_progress (progress_percentage DESC, status)
) ENGINE=InnoDB;

-- ========================================
-- PHASE 5: WASTE MANAGEMENT & RECYCLING
-- ========================================

-- Waste Categories for Classification
CREATE TABLE IF NOT EXISTS waste_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    category_name_en VARCHAR(50),
    category_code VARCHAR(10) UNIQUE NOT NULL,
    category_type ENUM('recyclable', 'organic', 'hazardous', 'general', 'electronic', 'medical') NOT NULL,
    description TEXT,
    description_en TEXT,
    handling_instructions TEXT,
    disposal_methods JSON,
    recycling_process TEXT,
    environmental_impact TEXT,
    icon_url VARCHAR(255),
    color_code VARCHAR(7),
    points_per_kg DECIMAL(5, 2) DEFAULT 10,
    carbon_saved_per_kg DECIMAL(5, 3) DEFAULT 0.5,
    examples JSON,
    common_mistakes JSON,
    tips JSON,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_code (category_code),
    INDEX idx_category_type (category_type),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

-- Detailed Waste Items Information
CREATE TABLE IF NOT EXISTS waste_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    item_name_en VARCHAR(100),
    category_id INT NOT NULL,
    subcategory VARCHAR(50),
    alternative_names JSON,
    description TEXT,
    material_composition JSON,
    recycling_instructions TEXT,
    disposal_method VARCHAR(100),
    environmental_impact_score INT DEFAULT 50,
    biodegradable_time VARCHAR(50),
    recycling_rate_percentage DECIMAL(5, 2),
    common_brands JSON,
    barcode_patterns JSON,
    image_urls JSON,
    identification_keywords JSON,
    ai_training_tags JSON,
    points_value INT DEFAULT 5,
    carbon_footprint_kg DECIMAL(8, 3),
    is_common BOOLEAN DEFAULT FALSE,
    difficulty_level ENUM('easy', 'medium', 'hard', 'expert') DEFAULT 'easy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES waste_categories(category_id),
    INDEX idx_category_common (category_id, is_common),
    INDEX idx_search_optimization (item_name, subcategory),
    FULLTEXT(item_name, description, identification_keywords)
) ENGINE=InnoDB;

-- Recycling Locations with Geospatial Data
CREATE TABLE IF NOT EXISTS recycling_locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(100) NOT NULL,
    location_type ENUM('recycling_center', 'collection_point', 'dropoff_station', 'mobile_unit', 'community_center') NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50),
    ward VARCHAR(50),
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(100),
    website_url VARCHAR(255),
    accepted_categories JSON NOT NULL,
    operating_hours JSON,
    special_instructions TEXT,
    capacity_status ENUM('low', 'medium', 'high', 'full') DEFAULT 'medium',
    equipment_available JSON,
    fees_structure JSON,
    contact_person VARCHAR(100),
    verification_status ENUM('unverified', 'pending', 'verified', 'suspended') DEFAULT 'pending',
    rating_average DECIMAL(3, 2) DEFAULT 0,
    review_count INT DEFAULT 0,
    total_waste_processed_kg DECIMAL(12, 3) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES recycling_locations(location_id),
    INDEX idx_geospatial (latitude, longitude),
    INDEX idx_city_type (city, location_type, is_active),
    INDEX idx_accepted_categories (accepted_categories(255)),
    SPATIAL INDEX idx_coordinates (POINT(latitude, longitude))
) ENGINE=InnoDB;

-- ========================================
-- PHASE 6: COMMUNITY FORUMS & DISCUSSIONS
-- ========================================

-- Forums Organization
CREATE TABLE IF NOT EXISTS forums (
    forum_id INT PRIMARY KEY AUTO_INCREMENT,
    forum_name VARCHAR(100) NOT NULL,
    forum_slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    forum_type ENUM('general', 'environmental', 'recycling', 'energy', 'transport', 'community', 'marketplace', 'help') DEFAULT 'general',
    parent_forum_id INT,
    icon_url VARCHAR(255),
    banner_image VARCHAR(255),
    rules TEXT,
    moderator_ids JSON,
    topic_count INT DEFAULT 0,
    post_count INT DEFAULT 0,
    last_post_id INT,
    last_activity TIMESTAMP NULL,
    sort_order INT DEFAULT 0,
    view_permission ENUM('public', 'members', 'verified', 'moderators') DEFAULT 'public',
    post_permission ENUM('everyone', 'members', 'verified', 'moderators') DEFAULT 'members',
    is_locked BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_forum_id) REFERENCES forums(forum_id) ON DELETE SET NULL,
    INDEX idx_parent_sort (parent_forum_id, sort_order),
    INDEX idx_type_active (forum_type, is_active),
    INDEX idx_featured (is_featured, sort_order)
) ENGINE=InnoDB;

-- Forum Topics/Discussions
CREATE TABLE IF NOT EXISTS forum_topics (
    topic_id INT PRIMARY KEY AUTO_INCREMENT,
    forum_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    topic_type ENUM('discussion', 'question', 'announcement', 'poll', 'guide', 'showcase') DEFAULT 'discussion',
    status ENUM('open', 'locked', 'closed', 'resolved', 'featured') DEFAULT 'open',
    priority ENUM('normal', 'high', 'urgent', 'sticky') DEFAULT 'normal',
    view_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    participant_count INT DEFAULT 1,
    like_count INT DEFAULT 0,
    last_post_id INT,
    last_post_at TIMESTAMP NULL,
    last_post_by INT,
    tags JSON,
    poll_data JSON,
    is_answered BOOLEAN DEFAULT FALSE,
    best_answer_post_id INT,
    featured_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forums(forum_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (last_post_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (best_answer_post_id) REFERENCES forum_posts(post_id) ON DELETE SET NULL,
    INDEX idx_forum_status (forum_id, status, last_post_at DESC),
    INDEX idx_author_topics (author_id, created_at DESC),
    INDEX idx_featured (priority DESC, featured_until DESC, last_post_at DESC),
    INDEX idx_answered (is_answered, topic_type),
    FULLTEXT(title, content, tags)
) ENGINE=InnoDB;

-- Forum Posts with Threading
CREATE TABLE IF NOT EXISTS forum_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    topic_id INT NOT NULL,
    parent_post_id INT,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('post', 'reply', 'quote', 'solution') DEFAULT 'post',
    like_count INT DEFAULT 0,
    dislike_count INT DEFAULT 0,
    quote_count INT DEFAULT 0,
    edit_count INT DEFAULT 0,
    is_best_answer BOOLEAN DEFAULT FALSE,
    is_highlighted BOOLEAN DEFAULT FALSE,
    is_reported BOOLEAN DEFAULT FALSE,
    report_count INT DEFAULT 0,
    moderation_status ENUM('approved', 'pending', 'flagged', 'hidden', 'deleted') DEFAULT 'approved',
    moderated_by INT,
    moderated_at TIMESTAMP NULL,
    moderation_reason TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    edited_at TIMESTAMP NULL,
    edited_by INT,
    edit_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (moderated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_topic_moderation (topic_id, moderation_status, created_at DESC),
    INDEX idx_author_posts (author_id, created_at DESC),
    INDEX idx_parent_posts (parent_post_id, created_at),
    INDEX idx_best_answers (is_best_answer, topic_id),
    FULLTEXT(content)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA FOR PHASE 3-6
-- ========================================

-- Insert social platforms
INSERT IGNORE INTO social_platforms (platform_name, platform_display_name, platform_color, share_url_template, points_per_share, sort_order) VALUES
('facebook', 'Facebook', '#1877F2', 'https://www.facebook.com/sharer/sharer.php?u={url}&quote={text}', 5, 1),
('twitter', 'Twitter/X', '#1DA1F2', 'https://twitter.com/intent/tweet?url={url}&text={text}&hashtags={hashtags}', 5, 2),
('linkedin', 'LinkedIn', '#0A66C2', 'https://www.linkedin.com/sharing/share-offsite/?url={url}', 8, 3),
('telegram', 'Telegram', '#26A5E4', 'https://t.me/share/url?url={url}&text={text}', 4, 4),
('zalo', 'Zalo', '#005AFF', 'https://zalo.me/share?url={url}&title={text}', 6, 5),
('pinterest', 'Pinterest', '#BD081C', 'https://pinterest.com/pin/create/button/?url={url}&description={text}', 6, 6);

-- Insert environmental data sources
INSERT IGNORE INTO environmental_data_sources (source_name, source_type, data_provider, update_frequency, data_categories, location_coverage) VALUES
('VN Air Quality API', 'api', 'Vietnam Environmental Administration', 'hourly', '["air_quality", "pollution_index"]', 'country'),
('Local Weather Stations', 'sensor', 'VN Meteorological Service', 'realtime', '["temperature", "humidity", "uv_index"]', 'city'),
('Water Quality Monitoring', 'sensor', 'Water Resources Department', 'daily', '["water_quality"]', 'region'),
('Noise Level Sensors', 'sensor', 'Environmental Protection Agency', 'hourly', '["noise_level"]', 'city');

-- Insert waste categories
INSERT IGNORE INTO waste_categories (category_name, category_name_en, category_code, category_type, description, color_code, points_per_kg, carbon_saved_per_kg) VALUES
('Nhựa', 'Plastic', 'PLA', 'recyclable', 'Các sản phẩm làm từ nhựa có thể tái chế', '#FF6B35', 15.0, 0.8),
('Giấy', 'Paper', 'PAP', 'recyclable', 'Giấy, báo, carton có thể tái chế', '#4ECDC4', 10.0, 0.6),
('Kim loại', 'Metal', 'MET', 'recyclable', 'Đồ kim loại như lon, sắt, nhôm', '#45B7D1', 25.0, 1.2),
('Thủy tinh', 'Glass', 'GLA', 'recyclable', 'Chai lọ thủy tinh có thể tái chế', '#96CEB4', 20.0, 0.9),
('Hữu cơ', 'Organic', 'ORG', 'organic', 'Rác thải hữu cơ, thức ăn thừa', '#FFEAA7', 5.0, 0.3),
('Điện tử', 'Electronic', 'ELE', 'electronic', 'Thiết bị điện tử cũ', '#6C5CE7', 50.0, 2.5),
('Nguy hại', 'Hazardous', 'HAZ', 'hazardous', 'Chất thải nguy hại như pin, hóa chất', '#E17055', 100.0, 0.1);

-- Insert waste items
INSERT IGNORE INTO waste_items (item_name, item_name_en, category_id, description, recycling_instructions, points_value, is_common) VALUES
('Chai nước nhựa', 'Plastic water bottle', 1, 'Chai đựng nước bằng nhựa PET', 'Rửa sạch, tháo nắp và nhãn', 3, TRUE),
('Báo cũ', 'Old newspaper', 2, 'Báo và tạp chí đã đọc', 'Để khô ráo, loại bỏ băng keo', 2, TRUE),
('Lon nước ngọt', 'Aluminum can', 3, 'Lon đồ uống bằng nhôm', 'Rửa sạch, có thể ép dẹp', 4, TRUE),
('Chai thủy tinh', 'Glass bottle', 4, 'Chai bia, rượu bằng thủy tinh', 'Rửa sạch, tháo nắp kim loại', 5, TRUE),
('Vỏ trái cây', 'Fruit peel', 5, 'Vỏ cam, chuối và trái cây khác', 'Có thể làm phân compost', 1, TRUE),
('Điện thoại cũ', 'Old mobile phone', 6, 'Smartphone không sử dụng', 'Xóa dữ liệu cá nhân trước khi bỏ', 20, FALSE),
('Pin AA', 'AA Battery', 7, 'Pin kiềm AA đã hết', 'Mang đến điểm thu gom chuyên dụng', 8, TRUE);

-- Insert sample forums
INSERT IGNORE INTO forums (forum_name, forum_slug, description, forum_type, sort_order, is_featured) VALUES
('Thảo luận chung', 'thao-luan-chung', 'Khu vực thảo luận chung về môi trường', 'general', 1, TRUE),
('Tái chế & Giảm rác', 'tai-che-giam-rac', 'Chia sẻ kinh nghiệm tái chế và giảm thiểu rác thải', 'recycling', 2, TRUE),
('Năng lượng xanh', 'nang-luong-xanh', 'Thảo luận về năng lượng tái tạo và tiết kiệm năng lượng', 'energy', 3, FALSE),
('Giao thông bền vững', 'giao-thong-ben-vung', 'Phương tiện giao thông thân thiện môi trường', 'transport', 4, FALSE),
('Marketplace xanh', 'marketplace-xanh', 'Mua bán trao đổi đồ cũ và sản phẩm xanh', 'marketplace', 5, TRUE),
('Hỗ trợ & Hướng dẫn', 'ho-tro-huong-dan', 'Khu vực hỏi đáp và hướng dẫn sử dụng', 'help', 6, FALSE);

-- Insert sample forum topics
INSERT IGNORE INTO forum_topics (forum_id, title, content, author_id, topic_type, status) VALUES
(1, 'Chào mừng đến với cộng đồng môi trường!', 'Đây là nơi chúng ta cùng nhau chia sẻ và học hỏi về bảo vệ môi trường. Hãy giới thiệu bản thân và chia sẻ những mục tiêu xanh của bạn!', 1, 'announcement', 'featured'),
(2, 'Cách tái chế chai nhựa thành chậu trồng cây', 'Mình muốn chia sẻ cách biến chai nhựa thành chậu trồng cây xinh xắn. Các bạn có kinh nghiệm gì tương tự không?', 2, 'guide', 'open'),
(3, 'Có nên lắp pin mặt trời cho nhà phố?', 'Gia đình mình đang cân nhắc lắp hệ thống pin mặt trời. Chi phí khoảng 100 triệu, có hiệu quả không các bạn?', 1, 'question', 'open'),
(4, 'Trao đổi: Xe đạp cũ lấy sách cũ', 'Mình có chiếc xe đạp còn tốt muốn đổi lấy sách. Có ai quan tâm không?', 2, 'discussion', 'open');

-- Insert sample forum posts
INSERT IGNORE INTO forum_posts (topic_id, author_id, content, post_type) VALUES
(1, 2, 'Chào mọi người! Mình là eco_user, đang học cách sống xanh hơn. Rất vui được tham gia cộng đồng này!', 'reply'),
(2, 1, 'Ý tưởng hay quá! Mình đã thử và kết quả rất tốt. Có thể khoan thêm lỗ thoát nước ở đáy cho an toàn.', 'reply'),
(3, 2, 'Theo mình nghiên cứu thì ROI khoảng 7-8 năm, nhưng góp phần giảm carbon footprint rất đáng kể.', 'reply'),
(4, 1, 'Xe đạp gì thế bạn? Mình có vài quyển sách về môi trường, có thể trao đổi được.', 'reply');

-- ========================================
-- ADVANCED FEATURES & ANALYTICS
-- ========================================

-- Content Sharing Analytics View
CREATE OR REPLACE VIEW v_sharing_analytics AS
SELECT 
    cs.content_type,
    cs.content_id,
    cs.content_title,
    sp.platform_display_name,
    COUNT(*) as total_shares,
    SUM(cs.click_count) as total_clicks,
    SUM(cs.conversion_count) as total_conversions,
    AVG(cs.engagement_score) as avg_engagement,
    AVG(cs.viral_coefficient) as avg_viral_coefficient,
    DATE(cs.shared_at) as share_date
FROM content_shares cs
JOIN social_platforms sp ON cs.platform_id = sp.platform_id
WHERE cs.share_status = 'verified'
GROUP BY cs.content_type, cs.content_id, cs.platform_id, DATE(cs.shared_at);

-- Environmental Impact View
CREATE OR REPLACE VIEW v_environmental_impact AS
SELECT 
    u.user_id,
    u.username,
    SUM(cf.carbon_saved_kg) as total_carbon_saved,
    COUNT(DISTINCT cf.activity_category) as categories_participated,
    AVG(cf.carbon_saved_kg) as avg_carbon_per_activity,
    MAX(cf.carbon_saved_kg) as max_single_impact,
    COUNT(*) as total_activities
FROM users u
LEFT JOIN carbon_footprints cf ON u.user_id = cf.user_id
WHERE cf.carbon_saved_kg > 0
GROUP BY u.user_id, u.username;

-- Forum Activity Summary View
CREATE OR REPLACE VIEW v_forum_activity AS
SELECT 
    f.forum_id,
    f.forum_name,
    COUNT(DISTINCT ft.topic_id) as total_topics,
    COUNT(DISTINCT fp.post_id) as total_posts,
    COUNT(DISTINCT ft.author_id) as unique_topic_authors,
    COUNT(DISTINCT fp.author_id) as unique_post_authors,
    MAX(fp.created_at) as last_activity
FROM forums f
LEFT JOIN forum_topics ft ON f.forum_id = ft.forum_id
LEFT JOIN forum_posts fp ON ft.topic_id = fp.topic_id
WHERE f.is_active = TRUE
GROUP BY f.forum_id, f.forum_name;

SELECT 'Phase 3-6 Complete: Social Sharing, Environmental Data, Waste Management & Forums!' as status;
SELECT 'Advanced Features Ready!' as result;
