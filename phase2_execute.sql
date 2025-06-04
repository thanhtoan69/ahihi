-- PHASE 2: Content Management System - Simple Execution
USE environmental_platform;

-- Update categories table
ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS name_en VARCHAR(100) AFTER name,
ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255) AFTER is_active,
ADD COLUMN IF NOT EXISTS seo_description TEXT AFTER seo_title,
ADD COLUMN IF NOT EXISTS seo_keywords JSON AFTER seo_description;

-- Create article_interactions table
CREATE TABLE IF NOT EXISTS article_interactions (
    interaction_id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    interaction_type ENUM('view', 'like', 'dislike', 'bookmark', 'share', 'comment', 'report') NOT NULL,
    interaction_value VARCHAR(255),
    session_duration_seconds INT,
    scroll_depth_percentage INT,
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_interaction (article_id, user_id, interaction_type),
    INDEX idx_article_type (article_id, interaction_type),
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_type_date (interaction_type, created_at)
) ENGINE=InnoDB;

-- Create article_comments table
CREATE TABLE IF NOT EXISTS article_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT,
    content TEXT NOT NULL,
    comment_type ENUM('comment', 'question', 'suggestion', 'correction') DEFAULT 'comment',
    like_count INT DEFAULT 0,
    dislike_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    is_highlighted BOOLEAN DEFAULT FALSE,
    is_pinned BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES article_comments(comment_id) ON DELETE CASCADE,
    INDEX idx_article_status (article_id, status, created_at DESC),
    INDEX idx_user_comments (user_id, created_at DESC),
    INDEX idx_parent_comment (parent_comment_id, created_at)
) ENGINE=InnoDB;

-- Create content_tags table
CREATE TABLE IF NOT EXISTS content_tags (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(50) UNIQUE NOT NULL,
    tag_slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    color_code VARCHAR(7),
    usage_count INT DEFAULT 0,
    is_trending BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (tag_slug),
    INDEX idx_usage (usage_count DESC),
    INDEX idx_trending (is_trending, usage_count DESC)
) ENGINE=InnoDB;

-- Create article_tags junction table
CREATE TABLE IF NOT EXISTS article_tags (
    article_id INT NOT NULL,
    tag_id INT NOT NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES content_tags(tag_id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_tag_articles (tag_id, added_at DESC),
    INDEX idx_added_by (added_by, added_at DESC)
) ENGINE=InnoDB;

-- Insert sample categories
INSERT INTO categories (name, name_en, slug, description, color_code, category_type, sort_order, is_featured, seo_title, seo_description) VALUES
('Môi trường', 'Environment', 'moi-truong', 'Các bài viết về bảo vệ môi trường và phát triển bền vững', '#22c55e', 'article', 1, TRUE, 'Bài viết về Môi trường | Environmental Platform', 'Khám phá các bài viết về bảo vệ môi trường, phát triển bền vững và ý thức xanh'),
('Tái chế', 'Recycling', 'tai-che', 'Hướng dẫn tái chế và giảm thiểu rác thải', '#3b82f6', 'article', 2, TRUE, 'Tái chế và Giảm thiểu rác thải', 'Học cách tái chế hiệu quả và giảm thiểu rác thải trong cuộc sống hàng ngày'),
('Năng lượng xanh', 'Green Energy', 'nang-luong-xanh', 'Thông tin về năng lượng tái tạo và tiết kiệm năng lượng', '#eab308', 'article', 3, FALSE, 'Năng lượng xanh và Tái tạo', 'Tìm hiểu về các nguồn năng lượng tái tạo và cách tiết kiệm năng lượng')
ON DUPLICATE KEY UPDATE
name_en = VALUES(name_en),
seo_title = VALUES(seo_title),
seo_description = VALUES(seo_description);

-- Insert sample content tags
INSERT INTO content_tags (tag_name, tag_slug, description, color_code, usage_count) VALUES
('bảo vệ môi trường', 'bao-ve-moi-truong', 'Tag về bảo vệ môi trường', '#22c55e', 0),
('tái chế', 'tai-che', 'Tag về tái chế rác thải', '#3b82f6', 0),
('tiết kiệm năng lượng', 'tiet-kiem-nang-luong', 'Tag về tiết kiệm năng lượng', '#eab308', 0),
('phát triển bền vững', 'phat-trien-ben-vung', 'Tag về phát triển bền vững', '#22c55e', 0);

-- Insert sample articles
INSERT INTO articles (title, slug, excerpt, content, author_id, category_id, article_type, difficulty_level, status, is_featured, published_at, environmental_impact_score) VALUES
(
    '10 Cách đơn giản để bảo vệ môi trường trong đời sống hàng ngày',
    '10-cach-don-gian-bao-ve-moi-truong',
    'Khám phá 10 cách thực tế và dễ thực hiện để bảo vệ môi trường ngay trong nhà bạn',
    '<h2>Giới thiệu</h2><p>Bảo vệ môi trường không chỉ là trách nhiệm của chính phủ hay các tổ chức lớn, mà còn là trách nhiệm của mỗi cá nhân chúng ta trong cuộc sống hàng ngày.</p><h2>10 Cách bảo vệ môi trường</h2><ol><li><strong>Tiết kiệm nước:</strong> Tắt vòi khi đánh răng, sửa chữa vòi bị rỉ</li><li><strong>Sử dụng túi vải:</strong> Thay thế túi nilon bằng túi vải có thể tái sử dụng</li><li><strong>Tái chế rác thải:</strong> Phân loại rác đúng cách</li><li><strong>Tiết kiệm điện:</strong> Tắt thiết bị điện khi không sử dụng</li><li><strong>Sử dụng giao thông công cộng:</strong> Giảm khí thải từ xe cá nhân</li></ol>',
    1,
    4,
    'guide',
    'beginner',
    'published',
    TRUE,
    NOW(),
    85
),
(
    'Hướng dẫn tái chế rác thải tại nhà hiệu quả',
    'huong-dan-tai-che-rac-thai-tai-nha',
    'Học cách phân loại và tái chế rác thải đúng cách để giảm thiểu tác động đến môi trường',
    '<h2>Tại sao cần tái chế?</h2><p>Tái chế giúp giảm thiểu rác thải, tiết kiệm tài nguyên thiên nhiên và bảo vệ môi trường.</p><h2>Cách phân loại rác</h2><ul><li>Rác hữu cơ: Thức ăn thừa, vỏ trái cây</li><li>Rác tái chế: Giấy, nhựa, kim loại</li><li>Rác không tái chế: Rác y tế, pin</li></ul>',
    2,
    5,
    'guide',
    'beginner',
    'published',
    FALSE,
    NOW(),
    75
);

-- Insert sample interactions
INSERT INTO article_interactions (article_id, user_id, interaction_type, session_duration_seconds, scroll_depth_percentage) VALUES
(1, 1, 'view', 180, 95),
(1, 2, 'view', 240, 100),
(1, 1, 'like', NULL, NULL),
(2, 1, 'view', 150, 80),
(2, 2, 'like', NULL, NULL);

-- Insert sample comments
INSERT INTO article_comments (article_id, user_id, content, comment_type) VALUES
(1, 2, 'Bài viết rất hữu ích! Tôi sẽ áp dụng ngay những cách này.', 'comment'),
(2, 1, 'Thông tin về phân loại rác rất chi tiết và dễ hiểu.', 'comment');

SELECT 'Phase 2 CMS Complete!' as status;
SELECT 'Tables created successfully' as result;
