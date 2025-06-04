-- PHASE 13: ITEM EXCHANGE SYSTEM (Simplified)
USE environmental_platform;

-- Exchange Categories
CREATE TABLE exchange_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_slug VARCHAR(100) UNIQUE NOT NULL,
    parent_category_id INT DEFAULT NULL,
    description TEXT,
    eco_impact_score INT DEFAULT 50,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_category_id) REFERENCES exchange_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Exchange Posts  
CREATE TABLE exchange_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    exchange_type ENUM('give_away', 'exchange', 'lending', 'selling_cheap') NOT NULL,
    item_condition ENUM('new', 'like_new', 'good', 'fair', 'poor') NOT NULL,
    estimated_value DECIMAL(10,2) DEFAULT NULL,
    location_data JSON,
    images JSON,
    post_status ENUM('draft', 'active', 'reserved', 'completed', 'expired') DEFAULT 'active',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES exchange_categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Exchange Requests
CREATE TABLE exchange_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    requester_id INT NOT NULL,
    message TEXT,
    offered_items JSON,
    request_status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Exchange Matches (AI-powered matching)
CREATE TABLE exchange_matches (
    match_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id_1 INT NOT NULL,
    post_id_2 INT NOT NULL,
    compatibility_score DECIMAL(5,2) NOT NULL,
    distance_km DECIMAL(8,3),
    match_reasons JSON,
    status ENUM('suggested', 'viewed', 'contacted', 'completed') DEFAULT 'suggested',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id_1) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id_2) REFERENCES exchange_posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Exchange Favorites
CREATE TABLE exchange_favorites (
    favorite_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES exchange_posts(post_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_post (user_id, post_id)
) ENGINE=InnoDB;

-- Exchange Saved Searches
CREATE TABLE exchange_saved_searches (
    search_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    search_name VARCHAR(100) NOT NULL,
    search_criteria JSON,
    notification_enabled BOOLEAN DEFAULT TRUE,
    last_notified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert sample categories
INSERT INTO exchange_categories (category_name, category_slug, description, eco_impact_score) VALUES
('Đồ điện tử', 'electronics', 'Thiết bị điện tử cũ như điện thoại, máy tính', 85),
('Quần áo', 'clothing', 'Quần áo cũ còn đẹp', 70),
('Đồ gia dụng', 'household', 'Đồ dùng trong nhà', 60),
('Sách vở', 'books', 'Sách báo, tài liệu học tập', 90),
('Đồ chơi trẻ em', 'toys', 'Đồ chơi cho trẻ em', 65),
('Xe đạp', 'bicycles', 'Xe đạp cũ các loại', 95);
