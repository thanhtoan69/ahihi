-- Insert Sample Data for Phase 2
USE environmental_platform;

-- Insert sample articles
INSERT INTO articles (title, slug, excerpt, content, author_id, category_id, article_type, difficulty_level, status, is_featured, environmental_impact_score, published_at) VALUES
('10 Cách đơn giản để bảo vệ môi trường', '10-cach-don-gian-bao-ve-moi-truong', 'Khám phá 10 cách thực tế để bảo vệ môi trường', '<h2>Giới thiệu</h2><p>Bảo vệ môi trường là trách nhiệm của tất cả.</p><h2>10 Cách bảo vệ môi trường</h2><ol><li>Tiết kiệm nước</li><li>Sử dụng túi vải</li><li>Tái chế rác thải</li><li>Tiết kiệm điện</li><li>Giao thông công cộng</li></ol>', 1, 1, 'guide', 'beginner', 'published', TRUE, 85, NOW()),
('Hướng dẫn tái chế rác thải tại nhà', 'huong-dan-tai-che-rac-thai', 'Học cách tái chế hiệu quả', '<h2>Tái chế là gì?</h2><p>Tái chế giúp bảo vệ môi trường.</p><h2>Cách phân loại</h2><ul><li>Rác hữu cơ</li><li>Rác tái chế</li></ul>', 2, 2, 'guide', 'beginner', 'published', FALSE, 75, NOW()),
('Zero Waste - Bắt đầu từ đâu?', 'zero-waste-bat-dau', 'Khám phá lối sống zero waste', '<h2>Zero Waste</h2><p>Giảm thiểu rác thải tối đa.</p><h2>5 nguyên tắc</h2><ol><li>Refuse</li><li>Reduce</li><li>Reuse</li><li>Recycle</li><li>Rot</li></ol>', 1, 2, 'article', 'intermediate', 'published', TRUE, 90, NOW());

-- Insert interactions
INSERT INTO article_interactions (article_id, user_id, interaction_type, session_duration_seconds, scroll_depth_percentage) VALUES
(1, 1, 'view', 180, 95), (1, 2, 'view', 240, 100), (1, 1, 'like', NULL, NULL),
(2, 1, 'view', 150, 80), (2, 2, 'like', NULL, NULL),
(3, 1, 'view', 300, 100), (3, 2, 'share', NULL, NULL);

-- Insert comments
INSERT INTO article_comments (article_id, user_id, content) VALUES
(1, 2, 'Bài viết rất hữu ích!'), (2, 1, 'Rất chi tiết và dễ hiểu.'), (3, 2, 'Zero waste thực sự cần thiết.');

-- Update stats
UPDATE articles a SET view_count = (SELECT COUNT(*) FROM article_interactions ai WHERE ai.article_id = a.article_id AND ai.interaction_type = 'view'), like_count = (SELECT COUNT(*) FROM article_interactions ai WHERE ai.article_id = a.article_id AND ai.interaction_type = 'like');

SELECT 'Sample data inserted successfully!' as result;
