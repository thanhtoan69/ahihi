-- ========================================
-- ENVIRONMENTAL PLATFORM - PHASE 2: CONTENT MANAGEMENT SYSTEM
-- Version: 3.0 Complete CMS Implementation
-- Features: Categories, Articles, Interactions, SEO, Versioning
-- ========================================

USE environmental_platform;

-- ========================================
-- CONTENT CATEGORIES SYSTEM
-- ========================================

CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    banner_image_url VARCHAR(255),
    color_code VARCHAR(7),
    parent_id INT,
    category_type ENUM('article', 'product', 'forum', 'event', 'general') DEFAULT 'general',
    level TINYINT DEFAULT 0,
    path VARCHAR(255),
    sort_order INT DEFAULT 0,
    post_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_parent_sort (parent_id, sort_order),
    INDEX idx_slug (slug),
    INDEX idx_type_active (category_type, is_active),
    INDEX idx_path (path),
    INDEX idx_featured (is_featured, sort_order)
) ENGINE=InnoDB;

-- ========================================
-- ARTICLES SYSTEM WITH FULL CMS FEATURES
-- ========================================

CREATE TABLE articles (
    article_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(255),
    gallery_images JSON,
    author_id INT NOT NULL,
    category_id INT,
    article_type ENUM('article', 'guide', 'research', 'news', 'infographic', 'video', 'podcast') DEFAULT 'article',
    content_format ENUM('markdown', 'html', 'plain') DEFAULT 'html',
    reading_time INT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    view_count INT DEFAULT 0,
    unique_viewers INT DEFAULT 0,
    like_count INT DEFAULT 0,
    dislike_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    bookmark_count INT DEFAULT 0,
    status ENUM('draft', 'pending_review', 'published', 'archived', 'rejected') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    is_editors_pick BOOLEAN DEFAULT FALSE,
    featured_until TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    last_modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords JSON,
    tags JSON,
    related_articles JSON,
    external_links JSON,
    references JSON,
    carbon_saved_reading DECIMAL(10,3) DEFAULT 0,
    environmental_impact_score INT DEFAULT 0,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    version INT DEFAULT 1,
    previous_version_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (previous_version_id) REFERENCES articles(article_id) ON DELETE SET NULL,
    INDEX idx_status_published (status, published_at DESC),
    INDEX idx_category_status (category_id, status),
    INDEX idx_author_status (author_id, status),
    INDEX idx_featured (is_featured, featured_until),
    INDEX idx_type_status (article_type, status),
    INDEX idx_slug (slug),
    INDEX idx_views (view_count DESC),
    INDEX idx_likes (like_count DESC),
    INDEX idx_environmental_score (environmental_impact_score DESC),
    FULLTEXT(title, excerpt, content, tags)
) ENGINE=InnoDB;

-- ========================================
-- ARTICLE INTERACTIONS & ENGAGEMENT TRACKING
-- ========================================

CREATE TABLE article_interactions (
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

-- ========================================
-- ARTICLE COMMENTS SYSTEM
-- ========================================

CREATE TABLE article_comments (
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
    INDEX idx_parent_comment (parent_comment_id, created_at),
    INDEX idx_pinned_highlighted (is_pinned DESC, is_highlighted DESC, created_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- ARTICLE VERSIONING SYSTEM
-- ========================================

CREATE TABLE article_versions (
    version_id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    version_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    changes_description TEXT,
    edited_by INT NOT NULL,
    edit_reason ENUM('content_update', 'typo_fix', 'seo_optimization', 'factual_correction', 'layout_change') DEFAULT 'content_update',
    is_major_change BOOLEAN DEFAULT FALSE,
    word_count INT,
    characters_changed INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_article_version (article_id, version_number),
    INDEX idx_article_version (article_id, version_number DESC),
    INDEX idx_editor_date (edited_by, created_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- CONTENT TAGS SYSTEM
-- ========================================

CREATE TABLE content_tags (
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

CREATE TABLE article_tags (
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

-- ========================================
-- CONTENT MEDIA MANAGEMENT
-- ========================================

CREATE TABLE media_files (
    media_id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video', 'audio', 'document', 'archive') NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    dimensions_width INT,
    dimensions_height INT,
    duration_seconds INT,
    alt_text VARCHAR(255),
    caption TEXT,
    description TEXT,
    copyright_info VARCHAR(255),
    uploaded_by INT NOT NULL,
    usage_count INT DEFAULT 0,
    is_optimized BOOLEAN DEFAULT FALSE,
    optimization_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_type_date (file_type, created_at DESC),
    INDEX idx_uploader (uploaded_by, created_at DESC),
    INDEX idx_usage (usage_count DESC),
    FULLTEXT(alt_text, caption, description)
) ENGINE=InnoDB;

CREATE TABLE article_media (
    article_id INT NOT NULL,
    media_id INT NOT NULL,
    media_type ENUM('featured', 'gallery', 'inline', 'attachment') DEFAULT 'inline',
    position_order INT DEFAULT 0,
    caption_override TEXT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (article_id, media_id, media_type),
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media_files(media_id) ON DELETE CASCADE,
    INDEX idx_article_type (article_id, media_type, position_order),
    INDEX idx_media_usage (media_id, added_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- CONTENT SEO & ANALYTICS
-- ========================================

CREATE TABLE content_seo_data (
    seo_id INT PRIMARY KEY AUTO_INCREMENT,
    content_type ENUM('article', 'category', 'page') NOT NULL,
    content_id INT NOT NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords JSON,
    canonical_url VARCHAR(500),
    og_title VARCHAR(255),
    og_description TEXT,
    og_image VARCHAR(500),
    twitter_title VARCHAR(255),
    twitter_description TEXT,
    twitter_image VARCHAR(500),
    schema_markup JSON,
    robots_index BOOLEAN DEFAULT TRUE,
    robots_follow BOOLEAN DEFAULT TRUE,
    sitemap_priority DECIMAL(2,1) DEFAULT 0.5,
    sitemap_changefreq ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') DEFAULT 'weekly',
    focus_keyword VARCHAR(100),
    seo_score INT DEFAULT 0,
    readability_score INT DEFAULT 0,
    last_analyzed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_content (content_type, content_id),
    INDEX idx_seo_score (seo_score DESC),
    INDEX idx_focus_keyword (focus_keyword)
) ENGINE=InnoDB;

-- ========================================
-- VIEWS FOR CONTENT MANAGEMENT
-- ========================================

-- Popular Articles View
CREATE VIEW v_popular_articles AS
SELECT 
    a.*,
    c.name as category_name,
    u.username as author_name,
    u.first_name as author_first_name,
    u.last_name as author_last_name,
    (a.view_count * 0.4 + a.like_count * 0.3 + a.share_count * 0.2 + a.comment_count * 0.1) as popularity_score
FROM articles a
LEFT JOIN categories c ON a.category_id = c.category_id
LEFT JOIN users u ON a.author_id = u.user_id
WHERE a.status = 'published'
ORDER BY popularity_score DESC;

-- Category Statistics View
CREATE VIEW v_category_stats AS
SELECT 
    c.*,
    COUNT(a.article_id) as total_articles,
    AVG(a.view_count) as avg_views,
    SUM(a.view_count) as total_views,
    AVG(a.like_count) as avg_likes,
    COUNT(CASE WHEN a.status = 'published' THEN 1 END) as published_articles,
    COUNT(CASE WHEN a.status = 'draft' THEN 1 END) as draft_articles
FROM categories c
LEFT JOIN articles a ON c.category_id = a.category_id
GROUP BY c.category_id;

-- Author Performance View
CREATE VIEW v_author_performance AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.last_name,
    COUNT(a.article_id) as total_articles,
    COUNT(CASE WHEN a.status = 'published' THEN 1 END) as published_articles,
    AVG(a.view_count) as avg_views_per_article,
    SUM(a.view_count) as total_views,
    AVG(a.like_count) as avg_likes_per_article,
    SUM(a.like_count) as total_likes,
    AVG(a.environmental_impact_score) as avg_environmental_score
FROM users u
LEFT JOIN articles a ON u.user_id = a.author_id
WHERE u.user_type IN ('individual', 'organization', 'admin')
GROUP BY u.user_id;

-- ========================================
-- STORED PROCEDURES FOR CONTENT MANAGEMENT
-- ========================================

DELIMITER //

-- Update Article Statistics
CREATE PROCEDURE UpdateArticleStats(IN article_id INT)
BEGIN
    DECLARE total_views INT DEFAULT 0;
    DECLARE total_likes INT DEFAULT 0;
    DECLARE total_dislikes INT DEFAULT 0;
    DECLARE total_shares INT DEFAULT 0;
    DECLARE total_comments INT DEFAULT 0;
    DECLARE total_bookmarks INT DEFAULT 0;
    DECLARE unique_viewers_count INT DEFAULT 0;
    
    -- Get interaction counts
    SELECT 
        COUNT(CASE WHEN interaction_type = 'view' THEN 1 END),
        COUNT(CASE WHEN interaction_type = 'like' THEN 1 END),
        COUNT(CASE WHEN interaction_type = 'dislike' THEN 1 END),
        COUNT(CASE WHEN interaction_type = 'share' THEN 1 END),
        COUNT(CASE WHEN interaction_type = 'bookmark' THEN 1 END)
    INTO total_views, total_likes, total_dislikes, total_shares, total_bookmarks
    FROM article_interactions 
    WHERE article_interactions.article_id = article_id;
    
    -- Get unique viewers
    SELECT COUNT(DISTINCT user_id) 
    INTO unique_viewers_count
    FROM article_interactions 
    WHERE article_interactions.article_id = article_id AND interaction_type = 'view';
    
    -- Get comment count
    SELECT COUNT(*) 
    INTO total_comments
    FROM article_comments 
    WHERE article_comments.article_id = article_id AND status = 'approved';
    
    -- Update article statistics
    UPDATE articles SET
        view_count = total_views,
        unique_viewers = unique_viewers_count,
        like_count = total_likes,
        dislike_count = total_dislikes,
        share_count = total_shares,
        comment_count = total_comments,
        bookmark_count = total_bookmarks,
        updated_at = CURRENT_TIMESTAMP
    WHERE articles.article_id = article_id;
END //

-- Update Category Post Count
CREATE PROCEDURE UpdateCategoryPostCount(IN category_id INT)
BEGIN
    DECLARE post_count INT DEFAULT 0;
    
    SELECT COUNT(*) 
    INTO post_count
    FROM articles 
    WHERE articles.category_id = category_id AND status = 'published';
    
    UPDATE categories SET
        post_count = post_count,
        updated_at = CURRENT_TIMESTAMP
    WHERE categories.category_id = category_id;
END //

-- Calculate Reading Time
CREATE PROCEDURE CalculateReadingTime(IN article_id INT)
BEGIN
    DECLARE content_length INT DEFAULT 0;
    DECLARE words_count INT DEFAULT 0;
    DECLARE reading_time_minutes INT DEFAULT 0;
    
    SELECT CHAR_LENGTH(content) 
    INTO content_length
    FROM articles 
    WHERE articles.article_id = article_id;
    
    -- Estimate words (average 5 characters per word)
    SET words_count = content_length / 5;
    
    -- Calculate reading time (average 200 words per minute)
    SET reading_time_minutes = CEIL(words_count / 200);
    
    UPDATE articles SET
        reading_time = reading_time_minutes,
        updated_at = CURRENT_TIMESTAMP
    WHERE articles.article_id = article_id;
END //

DELIMITER ;

-- ========================================
-- TRIGGERS FOR AUTOMATED CONTENT MANAGEMENT
-- ========================================

-- Auto-update article stats on new interaction
DELIMITER //
CREATE TRIGGER tr_article_interaction_after_insert
AFTER INSERT ON article_interactions
FOR EACH ROW
BEGIN
    CALL UpdateArticleStats(NEW.article_id);
    
    -- Award points to user for interaction
    IF NEW.interaction_type = 'like' THEN
        UPDATE users SET green_points = green_points + 2 WHERE user_id = NEW.user_id;
    ELSEIF NEW.interaction_type = 'share' THEN
        UPDATE users SET green_points = green_points + 5 WHERE user_id = NEW.user_id;
    ELSEIF NEW.interaction_type = 'comment' THEN
        UPDATE users SET green_points = green_points + 3 WHERE user_id = NEW.user_id;
    END IF;
END //
DELIMITER ;

-- Auto-update category post count when article status changes
DELIMITER //
CREATE TRIGGER tr_article_status_update
AFTER UPDATE ON articles
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.category_id != NEW.category_id THEN
        IF OLD.category_id IS NOT NULL THEN
            CALL UpdateCategoryPostCount(OLD.category_id);
        END IF;
        IF NEW.category_id IS NOT NULL THEN
            CALL UpdateCategoryPostCount(NEW.category_id);
        END IF;
    END IF;
    
    -- Auto-calculate reading time on content update
    IF OLD.content != NEW.content THEN
        CALL CalculateReadingTime(NEW.article_id);
    END IF;
END //
DELIMITER ;

-- Auto-update tag usage count
DELIMITER //
CREATE TRIGGER tr_article_tag_after_insert
AFTER INSERT ON article_tags
FOR EACH ROW
BEGIN
    UPDATE content_tags SET usage_count = usage_count + 1 WHERE tag_id = NEW.tag_id;
END //

CREATE TRIGGER tr_article_tag_after_delete
AFTER DELETE ON article_tags
FOR EACH ROW
BEGIN
    UPDATE content_tags SET usage_count = usage_count - 1 WHERE tag_id = OLD.tag_id;
END //
DELIMITER ;

-- ========================================
-- SAMPLE DATA FOR PHASE 2 TESTING
-- ========================================

-- Insert sample categories
INSERT INTO categories (name, name_en, slug, description, color_code, category_type, sort_order, is_featured, seo_title, seo_description) VALUES
('Môi trường', 'Environment', 'moi-truong', 'Các bài viết về bảo vệ môi trường và phát triển bền vững', '#22c55e', 'article', 1, TRUE, 'Bài viết về Môi trường | Environmental Platform', 'Khám phá các bài viết về bảo vệ môi trường, phát triển bền vững và ý thức xanh'),
('Tái chế', 'Recycling', 'tai-che', 'Hướng dẫn tái chế và giảm thiểu rác thải', '#3b82f6', 'article', 2, TRUE, 'Tái chế và Giảm thiểu rác thải', 'Học cách tái chế hiệu quả và giảm thiểu rác thải trong cuộc sống hàng ngày'),
('Năng lượng xanh', 'Green Energy', 'nang-luong-xanh', 'Thông tin về năng lượng tái tạo và tiết kiệm năng lượng', '#eab308', 'article', 3, FALSE, 'Năng lượng xanh và Tái tạo', 'Tìm hiểu về các nguồn năng lượng tái tạo và cách tiết kiệm năng lượng'),
('Giao thông xanh', 'Green Transport', 'giao-thong-xanh', 'Phương tiện giao thông thân thiện với môi trường', '#06b6d4', 'article', 4, FALSE, 'Giao thông xanh và Bền vững', 'Khám phá các phương tiện giao thông xanh và giảm carbon footprint'),
('Nông nghiệp hữu cơ', 'Organic Farming', 'nong-nghiep-huu-co', 'Canh tác hữu cơ và nông nghiệp bền vững', '#84cc16', 'article', 5, FALSE, 'Nông nghiệp hữu cơ và Bền vững', 'Tìm hiểu về canh tác hữu cơ và các phương pháp nông nghiệp bền vững');

-- Insert sample content tags
INSERT INTO content_tags (tag_name, tag_slug, description, color_code, usage_count) VALUES
('bảo vệ môi trường', 'bao-ve-moi-truong', 'Tag về bảo vệ môi trường', '#22c55e', 0),
('tái chế', 'tai-che', 'Tag về tái chế rác thải', '#3b82f6', 0),
('tiết kiệm năng lượng', 'tiet-kiem-nang-luong', 'Tag về tiết kiệm năng lượng', '#eab308', 0),
('phát triển bền vững', 'phat-trien-ben-vung', 'Tag về phát triển bền vững', '#22c55e', 0),
('khí hậu', 'khi-hau', 'Tag về biến đổi khí hậu', '#ef4444', 0),
('carbon footprint', 'carbon-footprint', 'Tag về dấu chân carbon', '#6b7280', 0),
('organic', 'organic', 'Tag về sản phẩm hữu cơ', '#84cc16', 0),
('zero waste', 'zero-waste', 'Tag về lối sống không rác thải', '#8b5cf6', 0);

-- Insert sample articles
INSERT INTO articles (title, slug, excerpt, content, author_id, category_id, article_type, difficulty_level, status, is_featured, published_at, seo_title, seo_description, tags, environmental_impact_score) VALUES
(
    '10 Cách đơn giản để bảo vệ môi trường trong đời sống hàng ngày',
    '10-cach-don-gian-bao-ve-moi-truong',
    'Khám phá 10 cách thực tế và dễ thực hiện để bảo vệ môi trường ngay trong nhà bạn',
    '<h2>Giới thiệu</h2><p>Bảo vệ môi trường không chỉ là trách nhiệm của chính phủ hay các tổ chức lớn, mà còn là trách nhiệm của mỗi cá nhân chúng ta trong cuộc sống hàng ngày.</p><h2>10 Cách bảo vệ môi trường</h2><ol><li><strong>Tiết kiệm nước:</strong> Tắt vòi khi đánh răng, sửa chữa vòi bị rỉ</li><li><strong>Sử dụng túi vải:</strong> Thay thế túi nilon bằng túi vải có thể tái sử dụng</li><li><strong>Tái chế rác thải:</strong> Phân loại rác đúng cách</li><li><strong>Tiết kiệm điện:</strong> Tắt thiết bị điện khi không sử dụng</li><li><strong>Sử dụng giao thông công cộng:</strong> Giảm khí thải từ xe cá nhân</li></ol><h2>Kết luận</h2><p>Những hành động nhỏ này có thể tạo nên sự khác biệt lớn cho môi trường.</p>',
    1,
    1,
    'guide',
    'beginner',
    'published',
    TRUE,
    NOW(),
    '10 Cách đơn giản bảo vệ môi trường | Environmental Platform',
    'Khám phá 10 cách thực tế để bảo vệ môi trường trong đời sống hàng ngày. Hướng dẫn chi tiết và dễ thực hiện.',
    '["bảo vệ môi trường", "tiết kiệm năng lượng", "phát triển bền vững"]',
    85
),
(
    'Hướng dẫn tái chế rác thải tại nhà hiệu quả',
    'huong-dan-tai-che-rac-thai-tai-nha',
    'Học cách phân loại và tái chế rác thải đúng cách để giảm thiểu tác động đến môi trường',
    '<h2>Tại sao cần tái chế?</h2><p>Tái chế giúp giảm thiểu rác thải, tiết kiệm tài nguyên thiên nhiên và bảo vệ môi trường.</p><h2>Cách phân loại rác</h2><ul><li>Rác hữu cơ: Thức ăn thừa, vỏ trái cây</li><li>Rác tái chế: Giấy, nhựa, kim loại</li><li>Rác không tái chế: Rác y tế, pin</li></ul><h2>Quy trình tái chế</h2><p>1. Thu gom và phân loại<br>2. Làm sạch<br>3. Xử lý và tái chế</p>',
    2,
    2,
    'guide',
    'beginner',
    'published',
    FALSE,
    NOW(),
    'Hướng dẫn tái chế rác thải hiệu quả tại nhà',
    'Học cách phân loại và tái chế rác thải đúng cách để bảo vệ môi trường. Hướng dẫn chi tiết từ A-Z.',
    '["tái chế", "zero waste", "bảo vệ môi trường"]',
    75
),
(
    'Năng lượng mặt trời: Xu hướng tương lai của Việt Nam',
    'nang-luong-mat-troi-xu-huong-tuong-lai-viet-nam',
    'Tìm hiểu về tiềm năng và triển vọng phát triển năng lượng mặt trời tại Việt Nam',
    '<h2>Tiềm năng năng lượng mặt trời ở Việt Nam</h2><p>Việt Nam có điều kiện thuận lợi để phát triển năng lượng mặt trời với số giờ nắng cao và vị trí địa lý tốt.</p><h2>Lợi ích của năng lượng mặt trời</h2><ul><li>Sạch và tái tạo</li><li>Giảm chi phí điện dài hạn</li><li>Độc lập năng lượng</li></ul><h2>Thách thức và giải pháp</h2><p>Chi phí đầu tư ban đầu cao nhưng được bù đắp bởi lợi ích dài hạn.</p>',
    1,
    3,
    'research',
    'intermediate',
    'published',
    TRUE,
    NOW(),
    'Năng lượng mặt trời - Xu hướng tương lai Việt Nam',
    'Tìm hiểu tiềm năng và triển vọng phát triển năng lượng mặt trời tại Việt Nam. Phân tích chi tiết.',
    '["tiết kiệm năng lượng", "phát triển bền vững", "khí hậu"]',
    90
);

-- Insert sample article interactions
INSERT INTO article_interactions (article_id, user_id, interaction_type, session_duration_seconds, scroll_depth_percentage) VALUES
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
INSERT INTO article_comments (article_id, user_id, content, comment_type) VALUES
(1, 2, 'Bài viết rất hữu ích! Tôi sẽ áp dụng ngay những cách này.', 'comment'),
(1, 1, 'Cảm ơn bạn! Hy vọng chúng ta cùng bảo vệ môi trường.', 'comment'),
(2, 1, 'Thông tin về phân loại rác rất chi tiết và dễ hiểu.', 'comment'),
(3, 2, 'Việt Nam thực sự có tiềm năng lớn về năng lượng mặt trời.', 'comment');

-- Insert article tags
INSERT INTO article_tags (article_id, tag_id, added_by) VALUES
(1, 1, 1), (1, 3, 1), (1, 4, 1),
(2, 2, 2), (2, 8, 2), (2, 1, 2),
(3, 3, 1), (3, 4, 1), (3, 5, 1);

-- Update reading time for sample articles
CALL CalculateReadingTime(1);
CALL CalculateReadingTime(2);
CALL CalculateReadingTime(3);

-- ========================================
-- FINAL STATUS CHECK
-- ========================================

SELECT 'Phase 2 - Content Management System Complete!' as status;

SELECT 'New Tables Created:' as info;
SELECT TABLE_NAME, TABLE_COMMENT 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'environmental_platform' 
  AND TABLE_NAME IN ('categories', 'articles', 'article_interactions', 'article_comments', 'article_versions', 'content_tags', 'article_tags', 'media_files', 'article_media', 'content_seo_data');

SELECT 'Sample Content Summary:' as info;
SELECT 
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM articles) as total_articles,
    (SELECT COUNT(*) FROM article_interactions) as total_interactions,
    (SELECT COUNT(*) FROM article_comments) as total_comments,
    (SELECT COUNT(*) FROM content_tags) as total_tags;

SELECT 'Popular Articles:' as info;
SELECT title, view_count, like_count, share_count, environmental_impact_score 
FROM articles 
WHERE status = 'published' 
ORDER BY view_count DESC;

-- Show phase completion
SELECT '=== PHASE 2 COMPLETE ===' as result;
