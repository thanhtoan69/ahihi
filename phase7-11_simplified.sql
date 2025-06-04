-- ========================================
-- ENVIRONMENTAL PLATFORM - PHASE 7-11: SIMPLIFIED EXECUTION
-- Events, Petitions, E-commerce, Quiz & Gamification
-- Version: 3.0 Simplified Implementation
-- ========================================

USE environmental_platform;

SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- PHASE 7: EVENTS & ACTIVITIES SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    event_type ENUM('workshop', 'cleanup', 'conference', 'webinar', 'volunteer') NOT NULL,
    event_mode ENUM('online', 'offline', 'hybrid') DEFAULT 'offline',
    organizer_id INT NOT NULL,
    venue_name VARCHAR(255),
    venue_address TEXT,
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    max_participants INT DEFAULT 100,
    current_participants INT DEFAULT 0,
    registration_fee DECIMAL(10,2) DEFAULT 0,
    points_reward INT DEFAULT 10,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_organizer_date (organizer_id, start_date),
    INDEX idx_location (latitude, longitude),
    INDEX idx_status_date (status, start_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_status ENUM('registered', 'attended', 'cancelled', 'no_show') DEFAULT 'registered',
    check_in_time TIMESTAMP NULL,
    points_earned INT DEFAULT 0,
    feedback_rating INT DEFAULT 0,
    feedback_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (event_id, user_id),
    INDEX idx_user_status (user_id, registration_status),
    INDEX idx_event_checkin (event_id, check_in_time)
) ENGINE=InnoDB;

-- ========================================
-- PHASE 8: PETITIONS & CAMPAIGNS
-- ========================================

CREATE TABLE IF NOT EXISTS petitions (
    petition_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description LONGTEXT NOT NULL,
    target_authority VARCHAR(255) NOT NULL,
    creator_id INT NOT NULL,
    target_signatures INT DEFAULT 1000,
    current_signatures INT DEFAULT 0,
    category ENUM('environment', 'pollution', 'climate', 'wildlife', 'energy', 'transport', 'other') NOT NULL,
    status ENUM('draft', 'active', 'successful', 'closed', 'rejected') DEFAULT 'draft',
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    featured_image VARCHAR(255),
    location_scope ENUM('global', 'national', 'regional', 'local') DEFAULT 'local',
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_creator_status (creator_id, status),
    INDEX idx_category_featured (category, is_featured),
    INDEX idx_signatures (current_signatures DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS petition_signatures (
    signature_id INT PRIMARY KEY AUTO_INCREMENT,
    petition_id INT NOT NULL,
    user_id INT,
    signer_name VARCHAR(100) NOT NULL,
    signer_email VARCHAR(255),
    is_anonymous BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_method ENUM('email', 'phone', 'facebook', 'google') DEFAULT 'email',
    ip_address VARCHAR(45),
    location VARCHAR(100),
    comment TEXT,
    points_earned INT DEFAULT 5,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (petition_id) REFERENCES petitions(petition_id) ON DELETE CASCADE,
    INDEX idx_petition_verified (petition_id, is_verified),
    INDEX idx_user_petitions (user_id, signed_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- PHASE 9: E-COMMERCE & MARKETPLACE SETUP
-- ========================================

CREATE TABLE IF NOT EXISTS product_brands (
    brand_id INT PRIMARY KEY AUTO_INCREMENT,
    brand_name VARCHAR(100) UNIQUE NOT NULL,
    brand_slug VARCHAR(100) UNIQUE NOT NULL,
    logo_url VARCHAR(255),
    description TEXT,
    sustainability_score INT DEFAULT 50,
    certifications JSON,
    is_eco_friendly BOOLEAN DEFAULT FALSE,
    website_url VARCHAR(255),
    country_origin VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sustainability (sustainability_score DESC),
    INDEX idx_eco_friendly (is_eco_friendly, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sellers (
    seller_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    business_type ENUM('individual', 'small_business', 'corporation', 'ngo', 'cooperative') NOT NULL,
    business_license VARCHAR(100),
    tax_id VARCHAR(50),
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    sustainability_rating DECIMAL(3,2) DEFAULT 0,
    total_sales DECIMAL(15,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    eco_certification_level ENUM('none', 'bronze', 'silver', 'gold', 'platinum') DEFAULT 'none',
    status ENUM('pending', 'approved', 'suspended', 'banned') DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_user_seller (user_id),
    INDEX idx_status_rating (status, sustainability_rating DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    brand_id INT,
    product_name VARCHAR(255) NOT NULL,
    product_slug VARCHAR(255) NOT NULL,
    sku VARCHAR(100),
    description LONGTEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    category_id INT,
    product_type ENUM('physical', 'digital', 'service', 'donation') DEFAULT 'physical',
    eco_score INT DEFAULT 50,
    carbon_footprint_kg DECIMAL(10,3),
    recyclable_percentage INT DEFAULT 0,
    organic_certified BOOLEAN DEFAULT FALSE,
    fair_trade_certified BOOLEAN DEFAULT FALSE,
    local_sourced BOOLEAN DEFAULT FALSE,
    packaging_type ENUM('minimal', 'recyclable', 'biodegradable', 'plastic_free', 'standard') DEFAULT 'standard',
    weight_grams INT,
    dimensions JSON,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    track_inventory BOOLEAN DEFAULT TRUE,
    featured_image VARCHAR(255),
    gallery_images JSON,
    video_url VARCHAR(255),
    status ENUM('draft', 'active', 'out_of_stock', 'discontinued', 'pending_review') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    purchase_count INT DEFAULT 0,
    rating_average DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES sellers(seller_id),
    FOREIGN KEY (brand_id) REFERENCES product_brands(brand_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    UNIQUE KEY unique_seller_slug (seller_id, product_slug),
    INDEX idx_seller_status (seller_id, status),
    INDEX idx_category_featured (category_id, is_featured),
    INDEX idx_eco_score (eco_score DESC),
    INDEX idx_price_range (price, status)
) ENGINE=InnoDB;

-- ========================================
-- PHASE 10: E-COMMERCE REVIEWS & SHOPPING
-- ========================================

CREATE TABLE IF NOT EXISTS product_reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    review_text LONGTEXT,
    eco_rating INT CHECK (eco_rating >= 1 AND eco_rating <= 5),
    quality_rating INT CHECK (quality_rating >= 1 AND quality_rating <= 5),
    value_rating INT CHECK (value_rating >= 1 AND value_rating <= 5),
    sustainability_rating INT CHECK (sustainability_rating >= 1 AND sustainability_rating <= 5),
    pros JSON,
    cons JSON,
    verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_votes INT DEFAULT 0,
    total_votes INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_review (user_id, product_id),
    INDEX idx_product_rating (product_id, rating DESC),
    INDEX idx_user_reviews (user_id, created_at DESC),
    INDEX idx_verified_approved (verified_purchase, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shopping_carts (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_user_cart (user_id, added_at DESC),
    INDEX idx_session_cart (session_id, added_at DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    seller_id INT NOT NULL,
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'partial_refund') DEFAULT 'pending',
    payment_method ENUM('credit_card', 'bank_transfer', 'e_wallet', 'cod', 'green_points') DEFAULT 'credit_card',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    green_points_used INT DEFAULT 0,
    green_points_earned INT DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    carbon_offset_kg DECIMAL(10,3) DEFAULT 0,
    shipping_address JSON NOT NULL,
    billing_address JSON,
    delivery_notes TEXT,
    estimated_delivery DATE,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES sellers(seller_id),
    INDEX idx_user_orders (user_id, created_at DESC),
    INDEX idx_seller_orders (seller_id, order_status),
    INDEX idx_order_status (order_status, created_at DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    eco_score INT DEFAULT 0,
    carbon_footprint_kg DECIMAL(10,3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_order_items (order_id),
    INDEX idx_product_sales (product_id, created_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- PHASE 11: QUIZ & GAMIFICATION SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS quiz_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    category_slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    color_code VARCHAR(7),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    points_per_question INT DEFAULT 10,
    time_limit_seconds INT DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order),
    INDEX idx_difficulty (difficulty_level)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    question_text LONGTEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'matching', 'ordering') DEFAULT 'multiple_choice',
    options JSON NOT NULL,
    correct_answer JSON NOT NULL,
    explanation TEXT,
    difficulty_level ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    points_value INT DEFAULT 10,
    time_limit_seconds INT DEFAULT 30,
    image_url VARCHAR(255),
    video_url VARCHAR(255),
    hint TEXT,
    reference_links JSON,
    tags JSON,
    created_by INT NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    usage_count INT DEFAULT 0,
    correct_rate DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES quiz_categories(category_id),
    INDEX idx_category_difficulty (category_id, difficulty_level),
    INDEX idx_active_verified (is_active, is_verified),
    INDEX idx_correct_rate (correct_rate DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    session_type ENUM('practice', 'challenge', 'daily', 'tournament') DEFAULT 'practice',
    total_questions INT NOT NULL,
    answered_questions INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    total_points INT DEFAULT 0,
    bonus_points INT DEFAULT 0,
    time_taken_seconds INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'completed', 'abandoned', 'paused') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_user_category (user_id, category_id),
    INDEX idx_status_date (status, started_at DESC),
    INDEX idx_points (total_points DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_responses (
    response_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer JSON NOT NULL,
    is_correct BOOLEAN NOT NULL,
    points_earned INT DEFAULT 0,
    time_taken_seconds INT DEFAULT 0,
    attempts_count INT DEFAULT 1,
    hint_used BOOLEAN DEFAULT FALSE,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES quiz_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id),
    INDEX idx_session_question (session_id, question_id),
    INDEX idx_question_correct (question_id, is_correct),
    INDEX idx_user_performance (session_id, is_correct, points_earned)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA FOR PHASE 7-11
-- ========================================

-- Sample Events
INSERT IGNORE INTO events (title, slug, description, event_type, organizer_id, venue_name, start_date, end_date, max_participants, points_reward) VALUES
('Workshop Tái chế Sáng tạo', 'workshop-tai-che-sang-tao', 'Học cách biến rác thải thành những vật dụng hữu ích', 'workshop', 1, 'Trung tâm Môi trường TP.HCM', '2025-06-15 09:00:00', '2025-06-15 16:00:00', 30, 50),
('Dọn dẹp Bãi biển Vũng Tàu', 'don-dep-bai-bien-vung-tau', 'Hoạt động dọn dẹp và bảo vệ môi trường biển', 'cleanup', 1, 'Bãi biển Vũng Tàu', '2025-06-20 07:00:00', '2025-06-20 11:00:00', 100, 30),
('Hội nghị Khí hậu Việt Nam 2025', 'hoi-nghi-khi-hau-viet-nam-2025', 'Hội nghị quốc gia về biến đổi khí hậu', 'conference', 1, 'Trung tâm Hội nghị Quốc gia', '2025-07-01 08:00:00', '2025-07-03 17:00:00', 500, 100);

-- Sample Event Registrations
INSERT IGNORE INTO event_registrations (event_id, user_id, registration_status, points_earned) VALUES
(1, 1, 'registered', 0),
(1, 2, 'attended', 50),
(2, 2, 'registered', 0);

-- Sample Petitions
INSERT IGNORE INTO petitions (title, slug, description, target_authority, creator_id, target_signatures, category, status, start_date) VALUES
('Cấm sử dụng túi nilon một lần', 'cam-su-dung-tui-nilon-mot-lan', 'Kiến nghị cấm sử dụng túi nilon một lần để bảo vệ môi trường', 'Bộ Tài nguyên và Môi trường', 1, 10000, 'pollution', 'active', NOW()),
('Tăng diện tích cây xanh đô thị', 'tang-dien-tich-cay-xanh-do-thi', 'Yêu cầu tăng diện tích cây xanh trong các khu đô thị', 'UBND TP.HCM', 2, 5000, 'environment', 'active', NOW());

-- Sample Petition Signatures
INSERT IGNORE INTO petition_signatures (petition_id, user_id, signer_name, signer_email, is_verified, points_earned) VALUES
(1, 1, 'Admin User', 'admin@example.com', TRUE, 5),
(1, 2, 'Eco User', 'eco@example.com', TRUE, 5),
(2, 1, 'Admin User', 'admin@example.com', TRUE, 5);

-- Sample Product Brands
INSERT IGNORE INTO product_brands (brand_name, brand_slug, description, sustainability_score, is_eco_friendly) VALUES
('EcoViet', 'ecoviet', 'Thương hiệu sản phẩm xanh Việt Nam', 90, TRUE),
('GreenLife', 'greenlife', 'Sản phẩm hữu cơ cho cuộc sống xanh', 85, TRUE),
('SustainablePlus', 'sustainableplus', 'Giải pháp bền vững cho môi trường', 80, TRUE);

-- Sample Sellers
INSERT IGNORE INTO sellers (user_id, business_name, business_type, contact_email, contact_phone, sustainability_rating, eco_certification_level, status, approved_at) VALUES
(1, 'Green Shop Vietnam', 'small_business', 'shop@greenvietnam.com', '0901234567', 4.5, 'gold', 'approved', NOW()),
(2, 'Eco Store HCM', 'individual', 'eco@hcm.com', '0907654321', 4.2, 'silver', 'approved', NOW());

-- Sample Products
INSERT IGNORE INTO products (seller_id, brand_id, product_name, product_slug, description, price, category_id, eco_score, organic_certified, packaging_type, stock_quantity, status) VALUES
(1, 1, 'Túi vải tái chế', 'tui-vai-tai-che', 'Túi vải làm từ chai nhựa tái chế, thân thiện môi trường', 50000, 1, 85, FALSE, 'plastic_free', 100, 'active'),
(1, 2, 'Bàn chải tre tự nhiên', 'ban-chai-tre-tu-nhien', 'Bàn chải đánh răng làm từ tre 100% tự nhiên', 25000, 1, 95, TRUE, 'biodegradable', 200, 'active'),
(2, 3, 'Cốc giữ nhiệt inox', 'coc-giu-nhiet-inox', 'Cốc giữ nhiệt từ inox 304, có thể tái sử dụng', 120000, 1, 75, FALSE, 'minimal', 50, 'active');

-- Sample Quiz Categories
INSERT IGNORE INTO quiz_categories (category_name, category_slug, description, difficulty_level, points_per_question) VALUES
('Kiến thức Môi trường Cơ bản', 'kien-thuc-moi-truong-co-ban', 'Những kiến thức cơ bản về bảo vệ môi trường', 'beginner', 10),
('Tái chế và Xử lý Rác thải', 'tai-che-va-xu-ly-rac-thai', 'Kiến thức về tái chế và xử lý rác thải', 'intermediate', 15),
('Biến đổi Khí hậu', 'bien-doi-khi-hau', 'Hiểu biết về biến đổi khí hậu và tác động', 'advanced', 20);

-- Sample Quiz Questions  
INSERT IGNORE INTO quiz_questions (category_id, question_text, question_type, options, correct_answer, explanation, difficulty_level, points_value, created_by, is_verified, verified_by) VALUES
(1, 'Plastic mất bao lâu để phân hủy hoàn toàn trong tự nhiên?', 'multiple_choice', '["50-100 năm", "200-300 năm", "400-1000 năm", "Không bao giờ phân hủy"]', '["400-1000 năm"]', 'Plastic cần 400-1000 năm mới phân hủy hoàn toàn, gây ô nhiễm môi trường nghiêm trọng.', 'easy', 10, 1, TRUE, 1),
(2, 'Loại rác nào có thể tái chế thành năng lượng?', 'multiple_choice', '["Rác hữu cơ", "Rác nhựa", "Rác giấy", "Tất cả các loại trên"]', '["Tất cả các loại trên"]', 'Tất cả các loại rác đều có thể được chuyển hóa thành năng lượng qua các công nghệ khác nhau.', 'medium', 15, 1, TRUE, 1),
(3, 'Hiệu ứng nhà kính chủ yếu do khí nào gây ra?', 'multiple_choice', '["CO2", "CH4", "N2O", "Tất cả các loại trên"]', '["Tất cả các loại trên"]', 'CO2, CH4, và N2O đều là các khí nhà kính chính gây ra biến đổi khí hậu.', 'hard', 20, 1, TRUE, 1);

-- ========================================
-- FINAL STATISTICS AND VERIFICATION
-- ========================================

-- Update table counts
SET @total_tables = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'environmental_platform');

SELECT 'Phase 7-11 Complete!' as status;
SELECT 'Final Database Summary:' as info;
SELECT @total_tables as total_tables;
SELECT 
    (SELECT COUNT(*) FROM events) as events,
    (SELECT COUNT(*) FROM petitions) as petitions,
    (SELECT COUNT(*) FROM products) as products,
    (SELECT COUNT(*) FROM quiz_questions) as quiz_questions;

SET FOREIGN_KEY_CHECKS = 1;
