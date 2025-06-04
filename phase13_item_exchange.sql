-- ========================================
-- PHASE 13: ITEM EXCHANGE SYSTEM
-- Complete item exchange, lending, and give-away platform
-- ========================================

USE environmental_platform;

SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- EXCHANGE CATEGORIES TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS exchange_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_slug VARCHAR(100) UNIQUE NOT NULL,
    parent_category_id INT DEFAULT NULL,
    description TEXT,
    category_icon VARCHAR(255),
    eco_impact_score INT DEFAULT 50, -- Environmental impact score for this category
    
    -- Category Rules
    requires_verification BOOLEAN DEFAULT FALSE,
    max_item_value DECIMAL(10,2) DEFAULT NULL,
    min_condition_rating INT DEFAULT 1,
    suggested_exchange_duration_days INT DEFAULT 30,
    
    -- Category Metrics
    total_items INT DEFAULT 0,
    total_exchanges INT DEFAULT 0,
    avg_success_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Sustainability Features
    carbon_footprint_reduction DECIMAL(10,3) DEFAULT 0, -- kg CO2 saved per exchange
    circular_economy_score INT DEFAULT 50, -- 1-100 score
    
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_category_id) REFERENCES exchange_categories(category_id) ON DELETE SET NULL,
    INDEX idx_parent_active (parent_category_id, is_active),
    INDEX idx_slug_active (category_slug, is_active),
    INDEX idx_eco_score (eco_impact_score DESC),
    INDEX idx_display_order (display_order, category_name)
) ENGINE=InnoDB;

-- ========================================
-- EXCHANGE POSTS TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS exchange_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    
    -- Post Basic Info
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    exchange_type ENUM('give_away', 'exchange', 'lending', 'selling_cheap') NOT NULL,
    item_condition ENUM('new', 'like_new', 'good', 'fair', 'poor') NOT NULL,
    condition_rating INT DEFAULT 5 CHECK (condition_rating BETWEEN 1 AND 10),
    
    -- Item Details
    item_brand VARCHAR(100),
    item_model VARCHAR(100),
    purchase_date DATE DEFAULT NULL,
    original_price DECIMAL(10,2) DEFAULT NULL,
    estimated_value DECIMAL(10,2) DEFAULT NULL,
    item_weight DECIMAL(8,3) DEFAULT NULL, -- kg
    item_dimensions JSON, -- {"length": 30, "width": 20, "height": 10} in cm
    
    -- Exchange Preferences
    preferred_exchange_items TEXT, -- What they want in return
    preferred_exchange_categories JSON, -- category_ids they're interested in
    exchange_value_range JSON, -- {"min": 50000, "max": 150000}
    lending_duration_days INT DEFAULT NULL, -- for lending type
    deposit_required DECIMAL(10,2) DEFAULT NULL, -- for lending
    
    -- Location and Logistics
    location_type ENUM('pickup_only', 'delivery_available', 'shipping_available', 'flexible') DEFAULT 'pickup_only',
    pickup_location JSON, -- {"address": "...", "lat": 10.123, "lng": 106.456, "notes": "..."}
    delivery_radius_km INT DEFAULT 0,
    shipping_cost DECIMAL(10,2) DEFAULT NULL,
    meet_public_place_only BOOLEAN DEFAULT TRUE,
    
    -- Media and Documentation
    images JSON, -- ["image1.jpg", "image2.jpg", ...]
    videos JSON, -- ["video1.mp4", ...]
    documents JSON, -- ["manual.pdf", "warranty.pdf", ...] for high-value items
    
    -- Verification and Trust
    verification_status ENUM('pending', 'verified', 'rejected', 'flagged') DEFAULT 'pending',
    verified_by INT DEFAULT NULL,
    verification_date TIMESTAMP NULL,
    verification_notes TEXT,
    trust_score DECIMAL(3,2) DEFAULT 5.0, -- 1-10 based on user history and item details
    
    -- Post Status and Management
    post_status ENUM('draft', 'active', 'reserved', 'completed', 'expired', 'removed') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    urgent BOOLEAN DEFAULT FALSE,
    auto_extend BOOLEAN DEFAULT FALSE,
    
    -- Timing
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT NULL,
    reserved_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Engagement Metrics
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    save_count INT DEFAULT 0,
    inquiry_count INT DEFAULT 0,
    
    -- Environmental Impact
    eco_points_reward INT DEFAULT 0,
    carbon_footprint_saved DECIMAL(10,3) DEFAULT 0,
    sustainability_tags JSON, -- ["recycled", "upcycled", "organic", "energy_efficient"]
    
    -- Safety and Moderation
    flagged_count INT DEFAULT 0,
    moderation_notes TEXT,
    safety_warnings JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES exchange_categories(category_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id),
    
    INDEX idx_user_status (user_id, post_status),
    INDEX idx_category_type (category_id, exchange_type, post_status),
    INDEX idx_location_search (post_status, location_type),
    INDEX idx_condition_value (item_condition, estimated_value),
    INDEX idx_posted_expires (posted_at DESC, expires_at),
    INDEX idx_featured_urgent (featured DESC, urgent DESC, posted_at DESC),
    INDEX idx_geo_search (category_id, post_status),
    INDEX idx_trust_verification (trust_score DESC, verification_status),
    
    -- Full-text search index
    FULLTEXT INDEX ft_search (title, description, preferred_exchange_items),
    FULLTEXT INDEX ft_item_details (item_brand, item_model)
) ENGINE=InnoDB;

-- ========================================
-- EXCHANGE REQUESTS TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS exchange_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    requester_id INT NOT NULL,
    poster_id INT NOT NULL, -- denormalized for performance
    
    -- Request Details
    request_type ENUM('inquiry', 'offer', 'counter_offer', 'booking') NOT NULL,
    message TEXT NOT NULL,
    offer_details JSON, -- {"items": [...], "cash_amount": 50000, "additional_notes": "..."}
    
    -- Offered Items (for exchange type)
    offered_items JSON, -- [{"title": "...", "description": "...", "images": [...], "value": 50000}]
    offered_cash_amount DECIMAL(10,2) DEFAULT 0,
    total_offer_value DECIMAL(10,2) DEFAULT 0,
    
    -- Meeting and Logistics
    proposed_meeting_location JSON, -- {"address": "...", "lat": 10.123, "lng": 106.456}
    proposed_meeting_times JSON, -- [{"date": "2025-06-15", "time_start": "14:00", "time_end": "16:00"}]
    delivery_method ENUM('pickup', 'delivery', 'shipping', 'meet_halfway') DEFAULT 'pickup',
    delivery_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Request Status
    request_status ENUM('pending', 'accepted', 'rejected', 'counter_offered', 'completed', 'cancelled') DEFAULT 'pending',
    response_deadline TIMESTAMP DEFAULT NULL,
    
    -- Communication
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    requester_read BOOLEAN DEFAULT TRUE,
    poster_read BOOLEAN DEFAULT FALSE,
    
    -- Trust and Safety
    requester_verified BOOLEAN DEFAULT FALSE,
    safety_concerns JSON DEFAULT NULL,
    reported_issues JSON DEFAULT NULL,
    
    -- Completion and Follow-up
    meeting_confirmed BOOLEAN DEFAULT FALSE,
    meeting_completed BOOLEAN DEFAULT FALSE,
    completion_confirmed_by_requester BOOLEAN DEFAULT FALSE,
    completion_confirmed_by_poster BOOLEAN DEFAULT FALSE,
    
    -- Ratings and Reviews
    requester_rating INT DEFAULT NULL CHECK (requester_rating BETWEEN 1 AND 5),
    poster_rating INT DEFAULT NULL CHECK (poster_rating BETWEEN 1 AND 5),
    requester_review TEXT DEFAULT NULL,
    poster_review TEXT DEFAULT NULL,
    
    -- Environmental Impact
    eco_impact_score INT DEFAULT 0,
    carbon_saved DECIMAL(10,3) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (post_id) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (poster_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_post_status (post_id, request_status),
    INDEX idx_requester_requests (requester_id, request_status, created_at DESC),
    INDEX idx_poster_requests (poster_id, request_status, created_at DESC),
    INDEX idx_status_timeline (request_status, created_at DESC),
    INDEX idx_completion_status (completion_confirmed_by_requester, completion_confirmed_by_poster),
    INDEX idx_ratings (requester_rating, poster_rating)
) ENGINE=InnoDB;

-- ========================================
-- EXCHANGE MATCHES (AI-POWERED MATCHING)
-- ========================================

CREATE TABLE IF NOT EXISTS exchange_matches (
    match_id INT PRIMARY KEY AUTO_INCREMENT,
    post1_id INT NOT NULL,
    post2_id INT NOT NULL,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    
    -- Matching Algorithm Results
    compatibility_score DECIMAL(5,3) DEFAULT 0, -- 0-1 score
    value_compatibility DECIMAL(5,3) DEFAULT 0,
    location_compatibility DECIMAL(5,3) DEFAULT 0,
    category_compatibility DECIMAL(5,3) DEFAULT 0,
    timing_compatibility DECIMAL(5,3) DEFAULT 0,
    user_preference_score DECIMAL(5,3) DEFAULT 0,
    
    -- Match Details
    match_type ENUM('exact_match', 'close_match', 'partial_match', 'suggestion') DEFAULT 'suggestion',
    match_reason TEXT,
    algorithm_version VARCHAR(20) DEFAULT '1.0',
    
    -- Distance and Logistics
    distance_km DECIMAL(8,3) DEFAULT NULL,
    estimated_travel_time_minutes INT DEFAULT NULL,
    
    -- Match Status
    match_status ENUM('suggested', 'viewed_by_user1', 'viewed_by_user2', 'contacted', 'negotiating', 'completed', 'expired') DEFAULT 'suggested',
    viewed_by_user1 BOOLEAN DEFAULT FALSE,
    viewed_by_user2 BOOLEAN DEFAULT FALSE,
    contacted_at TIMESTAMP NULL,
    
    -- Success Tracking
    resulted_in_exchange BOOLEAN DEFAULT FALSE,
    success_rating DECIMAL(3,2) DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 30 DAY),
    
    FOREIGN KEY (post1_id) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (post2_id) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user1_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_post_match (post1_id, post2_id),
    INDEX idx_user_matches (user1_id, match_status, compatibility_score DESC),
    INDEX idx_compatibility (compatibility_score DESC, match_status),
    INDEX idx_location_matches (distance_km, match_status),
    INDEX idx_expiry (expires_at, match_status)
) ENGINE=InnoDB;

-- ========================================
-- EXCHANGE FAVORITES AND SAVED SEARCHES
-- ========================================

CREATE TABLE IF NOT EXISTS exchange_favorites (
    favorite_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_post (user_id, post_id),
    INDEX idx_user_favorites (user_id, created_at DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exchange_saved_searches (
    search_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    search_name VARCHAR(100) NOT NULL,
    search_criteria JSON NOT NULL, -- {"category": 1, "exchange_type": "exchange", "location": {...}, "price_range": {...}}
    notification_enabled BOOLEAN DEFAULT TRUE,
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    new_results_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_searches (user_id, notification_enabled),
    INDEX idx_check_schedule (notification_enabled, last_checked)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA FOR EXCHANGE SYSTEM
-- ========================================

-- Sample Exchange Categories
INSERT IGNORE INTO exchange_categories (category_name, category_slug, description, category_icon, eco_impact_score, carbon_footprint_reduction, circular_economy_score) VALUES
('Đồ điện tử', 'do-dien-tu', 'Điện thoại, laptop, thiết bị công nghệ', '📱', 85, 15.5, 90),
('Quần áo & Phụ kiện', 'quan-ao-phu-kien', 'Thời trang, giày dép, túi xách', '👗', 75, 8.2, 85),
('Nội thất & Đồ gia dụng', 'noi-that-gia-dung', 'Bàn ghế, đồ trang trí, dụng cụ nhà bếp', '🪑', 70, 25.0, 80),
('Sách & Văn phòng phẩm', 'sach-van-phong-pham', 'Sách, tài liệu, dụng cụ học tập', '📚', 90, 5.5, 95),
('Thể thao & Giải trí', 'the-thao-giai-tri', 'Dụng cụ thể thao, đồ chơi, game', '⚽', 65, 12.0, 75),
('Xe cộ & Phương tiện', 'xe-co-phuong-tien', 'Xe đạp, xe máy, phụ tùng', '🚲', 80, 50.0, 85),
('Mẹ & Bé', 'me-va-be', 'Đồ em bé, đồ chơi trẻ em, sữa bột', '🍼', 85, 6.8, 90),
('Sức khỏe & Làm đẹp', 'suc-khoe-lam-dep', 'Mỹ phẩm, thiết bị chăm sóc sức khỏe', '💄', 60, 4.2, 70);

-- Sample Exchange Posts
INSERT IGNORE INTO exchange_posts (
    user_id, category_id, title, description, exchange_type, item_condition, condition_rating,
    estimated_value, preferred_exchange_items, location_type, pickup_location, images,
    post_status, eco_points_reward, sustainability_tags
) VALUES
(1, 1, 'iPhone 12 Pro - Đổi laptop học tập', 
 'iPhone 12 Pro 128GB, màu xanh navy, còn rất mới, fullbox. Muốn đổi lấy laptop để học lập trình.', 
 'exchange', 'like_new', 9, 15000000, 
 'Laptop học lập trình, RAM tối thiểu 8GB, SSD 256GB trở lên', 
 'pickup_only', '{"address": "Quận 1, TP.HCM", "lat": 10.7769, "lng": 106.7009, "notes": "Gặp tại trung tâm thương mại"}',
 '["iphone12_1.jpg", "iphone12_2.jpg", "iphone12_box.jpg"]', 'active', 150, '["reuse", "electronics"]'),

(2, 2, 'Tủ quần áo gỗ tự nhiên - Cho tặng', 
 'Tủ quần áo 3 cánh bằng gỗ tự nhiên, còn rất tốt. Do chuyển nhà nên cho tặng free.', 
 'give_away', 'good', 7, 2000000, 
 'Không cần đổi gì, chỉ cần đến lấy', 
 'pickup_only', '{"address": "Quận 7, TP.HCM", "lat": 10.7306, "lng": 106.7196, "notes": "Cần 2 người để khiêng"}',
 '["wardrobe_1.jpg", "wardrobe_2.jpg"]', 'active', 200, '["wood", "furniture_reuse", "zero_waste"]'),

(1, 4, 'Bộ sách lập trình 20 cuốn - Cho mượn', 
 'Bộ sách lập trình đầy đủ từ cơ bản đến nâng cao, cho mượn 3 tháng với tiền cọc.', 
 'lending', 'good', 8, 1500000, 
 'Tiền cọc 500k, trả lại khi hoàn thành', 
 'flexible', '{"address": "Quận 3, TP.HCM", "lat": 10.7769, "lng": 106.6925}',
 '["books_1.jpg", "books_2.jpg"]', 'active', 100, '["education", "knowledge_sharing"]'),

(2, 5, 'Xe đạp thể thao - Đổi đồ gia dụng', 
 'Xe đạp thể thao hiệu Giant, 21 tốc độ, màu đỏ. Muốn đổi lấy đồ gia dụng cho nhà mới.', 
 'exchange', 'good', 7, 3000000, 
 'Nồi cơm điện, lò vi sóng, bàn ăn hoặc ghế sofa', 
 'delivery_available', '{"address": "Quận Bình Thạnh, TP.HCM", "lat": 10.8014, "lng": 106.7108}',
 '["bike_1.jpg", "bike_2.jpg"]', 'active', 180, '["transport", "health", "eco_transport"]');

-- Sample Exchange Requests
INSERT IGNORE INTO exchange_requests (
    post_id, requester_id, poster_id, request_type, message, 
    offered_items, offered_cash_amount, total_offer_value, request_status
) VALUES
(1, 2, 1, 'offer', 'Mình có laptop Dell Inspiron 15, RAM 8GB, SSD 512GB, card đồ họa rời. Có thể đổi được không ạ?',
 '[{"title": "Dell Inspiron 15", "description": "RAM 8GB, SSD 512GB, GTX 1650", "value": 14000000}]',
 0, 14000000, 'pending'),

(2, 1, 2, 'inquiry', 'Chào bạn, tủ quần áo còn không? Mình có thể đến lấy cuối tuần này được không?',
 '[]', 0, 0, 'pending'),

(3, 2, 1, 'booking', 'Mình muốn mượn bộ sách này 2 tháng. Có thể gặp để ký thỏa thuận không?',
 '[]', 500000, 500000, 'accepted'),

(4, 1, 2, 'offer', 'Mình có bộ bàn ăn 4 ghế gỗ còn mới, giá trị khoảng 2.8tr. Có đổi được không?',
 '[{"title": "Bộ bàn ăn 4 ghế", "description": "Gỗ tự nhiên, còn mới 90%", "value": 2800000}]',
 200000, 3000000, 'counter_offered');

-- Sample Exchange Matches (AI Generated)
INSERT IGNORE INTO exchange_matches (
    post1_id, post2_id, user1_id, user2_id, compatibility_score, 
    value_compatibility, location_compatibility, match_type, match_reason, distance_km
) VALUES
(1, 4, 1, 2, 0.85, 0.90, 0.75, 'close_match', 'Cả hai đều muốn trao đổi công nghệ/giáo dục, giá trị tương đương', 5.2),
(2, 4, 2, 2, 0.70, 0.60, 0.95, 'partial_match', 'Cùng khu vực, có thể trao đổi đồ gia dụng', 3.1);

-- Sample Favorites
INSERT IGNORE INTO exchange_favorites (user_id, post_id, notes) VALUES
(1, 2, 'Tủ quần áo đẹp, có thể cần sau này'),
(2, 1, 'iPhone này có vẻ tốt, cân nhắc đổi'),
(1, 4, 'Xe đạp đẹp, giá hợp lý');

-- Sample Saved Searches
INSERT IGNORE INTO exchange_saved_searches (user_id, search_name, search_criteria) VALUES
(1, 'Laptop học lập trình', '{"category": 1, "exchange_type": "exchange", "keywords": "laptop programming", "max_value": 20000000}'),
(2, 'Đồ gia dụng miễn phí', '{"category": 3, "exchange_type": "give_away", "location_radius": 10}');

-- ========================================
-- VIEWS FOR EXCHANGE ANALYTICS
-- ========================================

-- Exchange Performance View
CREATE OR REPLACE VIEW exchange_performance AS
SELECT 
    ec.category_name,
    ec.category_id,
    COUNT(ep.post_id) as total_posts,
    COUNT(CASE WHEN ep.post_status = 'active' THEN 1 END) as active_posts,
    COUNT(CASE WHEN ep.post_status = 'completed' THEN 1 END) as completed_exchanges,
    COUNT(CASE WHEN ep.exchange_type = 'give_away' THEN 1 END) as give_aways,
    COUNT(CASE WHEN ep.exchange_type = 'exchange' THEN 1 END) as exchanges,
    COUNT(CASE WHEN ep.exchange_type = 'lending' THEN 1 END) as lendings,
    AVG(ep.estimated_value) as avg_item_value,
    SUM(ep.carbon_footprint_saved) as total_carbon_saved,
    SUM(ep.eco_points_reward) as total_eco_points_distributed,
    ROUND(COUNT(CASE WHEN ep.post_status = 'completed' THEN 1 END) * 100.0 / NULLIF(COUNT(ep.post_id), 0), 2) as success_rate_percentage
FROM exchange_categories ec
LEFT JOIN exchange_posts ep ON ec.category_id = ep.category_id
GROUP BY ec.category_id, ec.category_name
ORDER BY total_posts DESC;

-- User Exchange Activity View  
CREATE OR REPLACE VIEW user_exchange_activity AS
SELECT 
    u.user_id,
    u.username,
    u.green_points,
    COUNT(DISTINCT ep.post_id) as posts_created,
    COUNT(DISTINCT er.request_id) as requests_made,
    COUNT(CASE WHEN ep.post_status = 'completed' THEN 1 END) as successful_exchanges,
    SUM(ep.eco_points_reward) as eco_points_from_exchanges,
    SUM(ep.carbon_footprint_saved) as carbon_saved,
    AVG(er.requester_rating) as avg_rating_received,
    COUNT(DISTINCT ef.post_id) as items_favorited,
    COUNT(DISTINCT ess.search_id) as saved_searches
FROM users u
LEFT JOIN exchange_posts ep ON u.user_id = ep.user_id
LEFT JOIN exchange_requests er ON u.user_id = er.poster_id
LEFT JOIN exchange_favorites ef ON u.user_id = ef.user_id
LEFT JOIN exchange_saved_searches ess ON u.user_id = ess.user_id
GROUP BY u.user_id, u.username, u.green_points;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- STORED PROCEDURES FOR EXCHANGE SYSTEM
-- ========================================

DELIMITER //

-- Procedure to find matching exchange posts
CREATE PROCEDURE IF NOT EXISTS FindExchangeMatches(
    IN p_post_id INT,
    IN p_max_distance_km INT DEFAULT 50
)
BEGIN
    DECLARE p_user_id INT;
    DECLARE p_category_id INT;
    DECLARE p_exchange_type VARCHAR(20);
    DECLARE p_estimated_value DECIMAL(10,2);
    DECLARE p_lat DECIMAL(10,6);
    DECLARE p_lng DECIMAL(10,6);
    
    -- Get post details
    SELECT user_id, category_id, exchange_type, estimated_value,
           JSON_UNQUOTE(JSON_EXTRACT(pickup_location, '$.lat')),
           JSON_UNQUOTE(JSON_EXTRACT(pickup_location, '$.lng'))
    INTO p_user_id, p_category_id, p_exchange_type, p_estimated_value, p_lat, p_lng
    FROM exchange_posts 
    WHERE post_id = p_post_id;
    
    -- Find potential matches
    SELECT 
        ep2.post_id,
        ep2.title,
        ep2.user_id,
        ep2.estimated_value,
        u.username,
        
        -- Calculate compatibility scores
        CASE 
            WHEN ep2.category_id = p_category_id THEN 1.0
            WHEN ep2.category_id IN (SELECT category_id FROM exchange_categories WHERE parent_category_id = p_category_id) THEN 0.8
            ELSE 0.3
        END as category_compatibility,
        
        CASE 
            WHEN ABS(ep2.estimated_value - p_estimated_value) <= p_estimated_value * 0.2 THEN 1.0
            WHEN ABS(ep2.estimated_value - p_estimated_value) <= p_estimated_value * 0.5 THEN 0.7
            ELSE 0.3
        END as value_compatibility,
        
        -- Calculate distance if coordinates available
        CASE 
            WHEN p_lat IS NOT NULL AND p_lng IS NOT NULL 
                 AND JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lat')) IS NOT NULL
            THEN (
                6371 * acos(
                    cos(radians(p_lat)) * 
                    cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lat')) AS DECIMAL(10,6)))) *
                    cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lng')) AS DECIMAL(10,6))) - radians(p_lng)) +
                    sin(radians(p_lat)) * 
                    sin(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lat')) AS DECIMAL(10,6))))
                )
            )
            ELSE NULL
        END as distance_km
        
    FROM exchange_posts ep2
    JOIN users u ON ep2.user_id = u.user_id
    WHERE ep2.post_id != p_post_id
      AND ep2.user_id != p_user_id
      AND ep2.post_status = 'active'
      AND ep2.exchange_type IN ('exchange', 'give_away')
      AND (p_max_distance_km IS NULL OR (
          p_lat IS NOT NULL AND 
          JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lat')) IS NOT NULL AND
          (6371 * acos(
              cos(radians(p_lat)) * 
              cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lat')) AS DECIMAL(10,6)))) *
              cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lng')) AS DECIMAL(10,6))) - radians(p_lng)) +
              sin(radians(p_lat)) * 
              sin(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep2.pickup_location, '$.lat')) AS DECIMAL(10,6))))
          )) <= p_max_distance_km
      ))
    ORDER BY 
        (category_compatibility * 0.4 + value_compatibility * 0.4 + 
         CASE WHEN distance_km IS NULL THEN 0.2 ELSE GREATEST(0, 1 - distance_km/p_max_distance_km) * 0.2 END) DESC
    LIMIT 10;
END //

DELIMITER ;

-- ========================================
-- COMPLETION STATUS
-- ========================================

SELECT 'Phase 13: Item Exchange System - COMPLETED!' as status;
SELECT 
    'Created Tables:' as info,
    '- exchange_categories: Item categories with eco scoring' as table1,
    '- exchange_posts: Item listings with geolocation' as table2,
    '- exchange_requests: Negotiation and communication' as table3,
    '- exchange_matches: AI-powered matching system' as table4,
    '- exchange_favorites: User favorites and saved searches' as table5,
    '- exchange_saved_searches: Automated search alerts' as table6;

SELECT 
    'Features Implemented:' as features,
    '✓ Complete item exchange platform (give-away, exchange, lending)' as f1,
    '✓ Geolocation-based matching with distance calculation' as f2,
    '✓ AI-powered compatibility scoring algorithm' as f3,
    '✓ Multi-stage negotiation and communication system' as f4,
    '✓ Trust and verification system with ratings' as f5,
    '✓ Environmental impact tracking and rewards' as f6,
    '✓ Advanced search with saved alerts' as f7,
    '✓ Comprehensive analytics and reporting' as f8;
