-- PHASE 2: Content Management System - Data Only
USE environmental_platform;

-- Add missing columns to articles table
ALTER TABLE articles 
ADD COLUMN IF NOT EXISTS article_type ENUM('article', 'guide', 'research', 'news') DEFAULT 'article' AFTER category_id,
ADD COLUMN IF NOT EXISTS difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner' AFTER article_type,
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN IF NOT EXISTS environmental_impact_score INT DEFAULT 0 AFTER like_count;

-- Create article_interactions table
CREATE TABLE IF NOT EXISTS article_interactions (
    interaction_id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    interaction_type ENUM('view', 'like', 'dislike', 'bookmark', 'share') NOT NULL,
    session_duration_seconds INT,
    scroll_depth_percentage INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_interaction (article_id, user_id, interaction_type)
) ENGINE=InnoDB;

-- Create article_comments table  
CREATE TABLE IF NOT EXISTS article_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create content_tags table
CREATE TABLE IF NOT EXISTS content_tags (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(50) UNIQUE NOT NULL,
    tag_slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Update existing categories with new data
UPDATE categories SET 
    name_en = 'Environment',
    description = 'Các bài viết về bảo vệ môi trường và phát triển bền vững',
    color_code = '#22c55e',
    is_featured = TRUE,
    seo_title = 'Bài viết về Môi trường | Environmental Platform',
    seo_description = 'Khám phá các bài viết về bảo vệ môi trường'
WHERE name = 'Môi trường';

UPDATE categories SET 
    name_en = 'Green Living',
    description = 'Lối sống xanh và bền vững',
    color_code = '#3b82f6',
    is_featured = TRUE,
    seo_title = 'Lối sống xanh | Environmental Platform',
    seo_description = 'Tìm hiểu về lối sống xanh và bền vững'
WHERE name = 'Lối sống xanh';

UPDATE categories SET 
    name_en = 'Eco Tips',
    description = 'Mẹo và thủ thuật sinh thái',
    color_code = '#eab308',
    is_featured = FALSE,
    seo_title = 'Mẹo sinh thái | Environmental Platform',
    seo_description = 'Các mẹo và thủ thuật bảo vệ môi trường'
WHERE name = 'Mẹo sinh thái';

-- Insert sample content tags
INSERT IGNORE INTO content_tags (tag_name, tag_slug, description, usage_count) VALUES
('bảo vệ môi trường', 'bao-ve-moi-truong', 'Tag về bảo vệ môi trường', 5),
('tái chế', 'tai-che', 'Tag về tái chế rác thải', 3),
('tiết kiệm năng lượng', 'tiet-kiem-nang-luong', 'Tag về tiết kiệm năng lượng', 4),
('phát triển bền vững', 'phat-trien-ben-vung', 'Tag về phát triển bền vững', 6),
('lối sống xanh', 'loi-song-xanh', 'Tag về lối sống xanh', 7),
('zero waste', 'zero-waste', 'Tag về lối sống không rác thải', 2);

-- Insert sample articles
INSERT INTO articles (title, slug, excerpt, content, author_id, category_id, article_type, difficulty_level, status, is_featured, environmental_impact_score, published_at) VALUES
(
    '10 Cách đơn giản để bảo vệ môi trường',
    '10-cach-don-gian-bao-ve-moi-truong',
    'Khám phá 10 cách thực tế và dễ thực hiện để bảo vệ môi trường ngay trong nhà bạn',
    '<h2>Giới thiệu</h2><p>Bảo vệ môi trường là trách nhiệm của tất cả chúng ta.</p><h2>10 Cách bảo vệ môi trường</h2><ol><li>Tiết kiệm nước</li><li>Sử dụng túi vải</li><li>Tái chế rác thải</li><li>Tiết kiệm điện</li><li>Sử dụng giao thông công cộng</li><li>Trồng cây xanh</li><li>Giảm sử dụng nhựa</li><li>Mua sắm thông minh</li><li>Sử dụng năng lượng tái tạo</li><li>Tham gia hoạt động môi trường</li></ol>',
    1,
    1,
    'guide',
    'beginner',
    'published',
    TRUE,
    85,
    NOW()
),
(
    'Hướng dẫn tái chế rác thải tại nhà',
    'huong-dan-tai-che-rac-thai-tai-nha',
    'Học cách phân loại và tái chế rác thải đúng cách để giảm thiểu tác động đến môi trường',
    '<h2>Tầm quan trọng của tái chế</h2><p>Tái chế giúp giảm thiểu rác thải và bảo vệ môi trường.</p><h2>Cách phân loại rác</h2><ul><li>Rác hữu cơ</li><li>Rác tái chế</li><li>Rác không tái chế</li></ul><h2>Quy trình tái chế tại nhà</h2><p>Hướng dẫn chi tiết từng bước tái chế hiệu quả.</p>',
    2,
    2,
    'guide',
    'beginner',
    'published',
    FALSE,
    75,
    NOW()
),
(
    'Lối sống Zero Waste - Bắt đầu từ đâu?',
    'loi-song-zero-waste-bat-dau-tu-dau',
    'Khám phá cách bắt đầu lối sống zero waste một cách thực tế và bền vững',
    '<h2>Zero Waste là gì?</h2><p>Zero Waste là triết lý sống hướng tới việc giảm thiểu rác thải tối đa.</p><h2>5 nguyên tắc Zero Waste</h2><ol><li>Refuse (Từ chối)</li><li>Reduce (Giảm thiểu)</li><li>Reuse (Tái sử dụng)</li><li>Recycle (Tái chế)</li><li>Rot (Ủ phân)</li></ol>',
    1,
    2,
    'article',
    'intermediate',
    'published',
    TRUE,
    90,
    NOW()
);

-- Insert sample interactions
INSERT IGNORE INTO article_interactions (article_id, user_id, interaction_type, session_duration_seconds, scroll_depth_percentage) VALUES
(1, 1, 'view', 180, 95),
(1, 2, 'view', 240, 100),
(1, 1, 'like', NULL, NULL),
(1, 2, 'bookmark', NULL, NULL),
(2, 1, 'view', 150, 80),
(2, 2, 'view', 200, 90),
(2, 2, 'like', NULL, NULL),
(3, 1, 'view', 300, 100),
(3, 2, 'view', 250, 85),
(3, 1, 'like', NULL, NULL),
(3, 2, 'share', NULL, NULL);

-- Insert sample comments
INSERT INTO article_comments (article_id, user_id, content) VALUES
(1, 2, 'Bài viết rất hữu ích! Tôi sẽ áp dụng ngay những cách này trong cuộc sống hàng ngày.'),
(1, 1, 'Cảm ơn bạn đã chia sẻ! Hy vọng chúng ta cùng nhau bảo vệ môi trường.'),
(2, 1, 'Thông tin về phân loại rác rất chi tiết và dễ hiểu. Cảm ơn tác giả!'),
(3, 2, 'Zero waste thực sự là xu hướng cần thiết. Bài viết rất cụ thể và thực tế.');

-- Update article statistics
UPDATE articles a SET 
    view_count = (SELECT COUNT(*) FROM article_interactions ai WHERE ai.article_id = a.article_id AND ai.interaction_type = 'view'),
    like_count = (SELECT COUNT(*) FROM article_interactions ai WHERE ai.article_id = a.article_id AND ai.interaction_type = 'like');

-- Show results
SELECT 'Phase 2 - Content Management System Complete!' as status;

SELECT 'Content Statistics:' as info;
SELECT 
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM articles WHERE status = 'published') as published_articles,
    (SELECT COUNT(*) FROM content_tags) as total_tags,
    (SELECT COUNT(*) FROM article_interactions) as total_interactions,
    (SELECT COUNT(*) FROM article_comments) as total_comments;

SELECT 'Popular Articles:' as info;
SELECT title, view_count, like_count, environmental_impact_score, is_featured 
FROM articles 
WHERE status = 'published' 
ORDER BY view_count DESC LIMIT 5;
