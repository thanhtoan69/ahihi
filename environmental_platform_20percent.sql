-- ========================================
-- ENVIRONMENTAL PLATFORM - 20% DATABASE TABLES
-- Core Essential Tables Only
-- Database: MySQL 8.0+
-- ========================================

CREATE DATABASE IF NOT EXISTS environmental_platform 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE environmental_platform;

-- ========================================
-- 1. CORE USER & AUTHENTICATION SYSTEM
-- ========================================

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone_number VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    location VARCHAR(100),
    city VARCHAR(50),
    district VARCHAR(50),
    ward VARCHAR(50),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    avatar_url VARCHAR(255),
    bio TEXT,
    green_points INT DEFAULT 0,
    experience_points INT DEFAULT 0,
    user_level INT DEFAULT 1,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_streak INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    user_type ENUM('individual', 'organization', 'business', 'admin', 'moderator') DEFAULT 'individual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_location (city, district),
    INDEX idx_green_points (green_points DESC),
    FULLTEXT(username, first_name, last_name)
) ENGINE=InnoDB;

CREATE TABLE user_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ========================================
-- 2. CONTENT MANAGEMENT SYSTEM
-- ========================================

CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    color_code VARCHAR(7),
    parent_id INT,
    category_type ENUM('article', 'product', 'forum', 'event', 'general') DEFAULT 'general',
    sort_order INT DEFAULT 0,
    post_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_type_active (category_type, is_active)
) ENGINE=InnoDB;

CREATE TABLE articles (
    article_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(255),
    author_id INT NOT NULL,
    category_id INT,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    status ENUM('draft', 'pending_review', 'published', 'archived') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    INDEX idx_status_published (status, published_at DESC),
    INDEX idx_slug (slug),
    FULLTEXT(title, excerpt, content)
) ENGINE=InnoDB;

-- ========================================
-- 3. SOCIAL SHARING SYSTEM
-- ========================================

CREATE TABLE social_platforms (
    platform_id INT PRIMARY KEY AUTO_INCREMENT,
    platform_name VARCHAR(50) UNIQUE NOT NULL,
    platform_display_name VARCHAR(100) NOT NULL,
    platform_icon VARCHAR(255),
    platform_color VARCHAR(7),
    share_url_template TEXT,
    points_per_share INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE content_shares (
    share_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content_type ENUM('article', 'event', 'product', 'forum_topic') NOT NULL,
    content_id INT NOT NULL,
    content_title VARCHAR(255) NOT NULL,
    platform_id INT NOT NULL,
    share_url TEXT,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    clicks_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (platform_id) REFERENCES social_platforms(platform_id),
    INDEX idx_user_content (user_id, content_type, content_id),
    INDEX idx_platform_date (platform_id, shared_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- 4. ENVIRONMENTAL DATA
-- ========================================

CREATE TABLE carbon_footprints (
    footprint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_category ENUM('transport', 'energy', 'waste', 'food', 'consumption') NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    carbon_kg DECIMAL(10,3) NOT NULL,
    carbon_saved_kg DECIMAL(10,3) DEFAULT 0,
    activity_date DATE NOT NULL,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_user_category_month (user_id, activity_category, activity_date)
) ENGINE=InnoDB;

-- ========================================
-- 5. WASTE MANAGEMENT
-- ========================================

CREATE TABLE waste_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(10) UNIQUE NOT NULL,
    category_type ENUM('organic', 'recyclable', 'hazardous', 'electronic', 'medical', 'general') NOT NULL,
    description TEXT,
    color_code VARCHAR(7),
    points_per_kg INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE waste_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    alternative_names JSON,
    description TEXT,
    recycling_instructions TEXT,
    disposal_method VARCHAR(100),
    environmental_impact_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES waste_categories(category_id),
    FULLTEXT(item_name, alternative_names, description)
) ENGINE=InnoDB;

-- ========================================
-- 6. EVENTS SYSTEM
-- ========================================

CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description LONGTEXT,
    event_type ENUM('workshop', 'cleanup', 'conference', 'competition', 'exhibition') NOT NULL,
    organizer_id INT NOT NULL,
    venue_name VARCHAR(255),
    venue_address TEXT,
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    max_participants INT,
    current_participants INT DEFAULT 0,
    registration_fee DECIMAL(10,2) DEFAULT 0,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    featured_image VARCHAR(255),
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(user_id),
    INDEX idx_status_date (status, start_date),
    INDEX idx_location (latitude, longitude),
    FULLTEXT(title, description, venue_name)
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('registered', 'attended', 'cancelled', 'no_show') DEFAULT 'registered',
    registration_fee_paid DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    special_requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_registration_number (registration_number)
) ENGINE=InnoDB;

-- ========================================
-- 7. ACHIEVEMENTS & GAMIFICATION
-- ========================================

CREATE TABLE achievements (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    achievement_name VARCHAR(100) NOT NULL,
    achievement_slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    category ENUM('environmental', 'social', 'learning', 'commerce', 'special') NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard', 'legendary') DEFAULT 'medium',
    points_reward INT DEFAULT 0,
    badge_image VARCHAR(255),
    badge_color VARCHAR(7),
    unlock_criteria JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    total_unlocks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_active (category, is_active)
) ENGINE=InnoDB;

CREATE TABLE user_achievements (
    user_achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    current_progress INT DEFAULT 0,
    unlocked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (achievement_id) REFERENCES achievements(achievement_id),
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_unlocked (user_id, unlocked_at)
) ENGINE=InnoDB;

-- ========================================
-- 8. USER ACTIVITIES
-- ========================================

CREATE TABLE user_activities (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_category VARCHAR(50),
    entity_type VARCHAR(50),
    entity_id INT,
    points_earned INT DEFAULT 0,
    carbon_impact DECIMAL(10,3),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_user_type_date (user_id, activity_type, created_at DESC),
    INDEX idx_category_date (activity_category, created_at)
) ENGINE=InnoDB;

-- ========================================
-- 9. BASIC NOTIFICATIONS
-- ========================================

CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'achievement') DEFAULT 'info',
    entity_type VARCHAR(50),
    entity_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_user_unread (user_id, is_read, created_at DESC),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ========================================
-- INITIAL DATA
-- ========================================

-- Insert default categories
INSERT INTO categories (name, slug, description, icon_url, color_code, category_type, sort_order) VALUES
('Môi trường', 'moi-truong', 'Tin tức và kiến thức môi trường', 'leaf', '#22c55e', 'article', 1),
('Năng lượng tái tạo', 'nang-luong-tai-tao', 'Năng lượng sạch và bền vững', 'sun', '#f59e0b', 'article', 2),
('Tái chế', 'tai-che', 'Hướng dẫn tái chế và xử lý rác', 'recycle', '#3b82f6', 'article', 3),
('Sản phẩm xanh', 'san-pham-xanh', 'Sản phẩm thân thiện môi trường', 'shopping-bag', '#10b981', 'product', 4),
('Sự kiện', 'su-kien', 'Hoạt động cộng đồng', 'calendar', '#8b5cf6', 'event', 5);

-- Insert social platforms
INSERT INTO social_platforms (platform_name, platform_display_name, platform_icon, platform_color, share_url_template, points_per_share) VALUES
('facebook', 'Facebook', 'facebook', '#1877f2', 'https://www.facebook.com/sharer.php?u={url}', 10),
('twitter', 'Twitter/X', 'twitter', '#1da1f2', 'https://twitter.com/intent/tweet?url={url}&text={title}', 8),
('linkedin', 'LinkedIn', 'linkedin', '#0077b5', 'https://www.linkedin.com/sharing/share-offsite/?url={url}', 12),
('whatsapp', 'WhatsApp', 'whatsapp', '#25d366', 'https://api.whatsapp.com/send?text={title}%20{url}', 5),
('telegram', 'Telegram', 'telegram', '#0088cc', 'https://t.me/share/url?url={url}&text={title}', 5);

-- Insert waste categories
INSERT INTO waste_categories (category_name, category_code, category_type, description, color_code, points_per_kg) VALUES
('Hữu cơ', 'ORG', 'organic', 'Chất thải có thể phân hủy sinh học', '#22c55e', 5),
('Tái chế', 'REC', 'recyclable', 'Vật liệu có thể tái chế', '#3b82f6', 10),
('Nguy hại', 'HAZ', 'hazardous', 'Chất thải nguy hại cần xử lý đặc biệt', '#ef4444', 15),
('Điện tử', 'ELE', 'electronic', 'Rác thải điện tử', '#f59e0b', 20),
('Y tế', 'MED', 'medical', 'Rác thải y tế', '#dc2626', 25),
('Thông thường', 'GEN', 'general', 'Rác thải sinh hoạt thông thường', '#6b7280', 3);

-- Insert achievements
INSERT INTO achievements (achievement_name, achievement_slug, description, category, points_reward, unlock_criteria) VALUES
('Người mới bắt đầu', 'newcomer', 'Hoàn thành đăng ký tài khoản', 'special', 50, '{"trigger_types": ["registration"], "required_value": 1}'),
('Nhà phân loại rác', 'waste-classifier', 'Phân loại đúng 10 loại rác', 'environmental', 100, '{"trigger_types": ["waste_classification"], "required_value": 10}'),
('Người chia sẻ', 'social-sharer', 'Chia sẻ 5 bài viết lên mạng xã hội', 'social', 75, '{"trigger_types": ["social_sharing"], "required_value": 5}'),
('Nhà hoạt động carbon', 'carbon-warrior', 'Tiết kiệm 100kg CO2', 'environmental', 200, '{"trigger_types": ["carbon_logging"], "required_value": 100}'),
('Học giả môi trường', 'eco-scholar', 'Hoàn thành 10 bài quiz', 'learning', 150, '{"trigger_types": ["quiz_complete"], "required_value": 10}');

-- Insert sample admin user
INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, is_verified, green_points) VALUES
('admin', 'admin@ecoplatform.com', '$2y$10$YourHashedPasswordHere', 'Admin', 'System', 'admin', TRUE, 10000);

-- Add some sample waste items
INSERT INTO waste_items (item_name, category_id, alternative_names, description, recycling_instructions) VALUES
('Chai nhựa', 1, '["plastic bottle", "water bottle"]', 'Chai đựng nước bằng nhựa PET', 'Rửa sạch trước khi tái chế'),
('Pin cũ', 4, '["battery", "old battery"]', 'Pin lithium hoặc alkaline đã hết', 'Mang đến điểm thu gom pin cũ'),
('Giấy báo', 2, '["newspaper", "paper"]', 'Báo và tạp chí cũ', 'Để khô ráo trước khi tái chế'),
('Túi nilon', 6, '["plastic bag", "nylon bag"]', 'Túi nhựa sinh hoạt', 'Hạn chế sử dụng, tái sử dụng nhiều lần');

-- ========================================
-- DATABASE INFO
-- ========================================

SELECT 
    'Environmental Platform Database - 20% Core Tables' as name,
    COUNT(DISTINCT TABLE_NAME) as total_tables,
    DATABASE() as database_name,
    VERSION() as mysql_version,
    NOW() as setup_completed_at
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE();

-- ========================================
-- SETUP COMPLETE!
-- ========================================
/*
📊 Core Database Statistics (20%):
- Total Tables: 15 core tables
- Core Features: User Management, Content, Social Sharing, Environmental Data
- Essential Features: Events, Achievements, Activities, Notifications
- Performance: Proper indexes and foreign keys

🚀 Key Capabilities Included:
1. User registration and authentication
2. Content management with articles and categories  
3. Social sharing functionality
4. Carbon footprint tracking
5. Waste management and classification
6. Event management and registration
7. Achievement system and gamification
8. User activity tracking
9. Basic notification system

✅ Ready for basic functionality testing!

Next phase can include:
- E-commerce tables
- Forum system
- AI/ML infrastructure
- Analytics and reporting
- Advanced moderation tools
*/
