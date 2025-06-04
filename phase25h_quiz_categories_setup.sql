-- ========================================
-- PHASE 25H: QUIZ CATEGORIES CONFIGURATION
-- Environmental Platform Database
-- Educational Quiz Categories for Environmental Knowledge
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- CLEAR EXISTING DATA (IF ANY)
-- ========================================

-- Clear existing quiz categories to start fresh
DELETE FROM quiz_categories WHERE category_id > 0;
ALTER TABLE quiz_categories AUTO_INCREMENT = 1;

-- ========================================
-- COMPREHENSIVE ENVIRONMENTAL QUIZ CATEGORIES
-- ========================================

INSERT INTO quiz_categories (
    category_name, 
    category_slug, 
    description, 
    difficulty_level, 
    points_per_question, 
    is_active
) VALUES

-- ----------------------------------------------------------------
-- BEGINNER LEVEL CATEGORIES
-- ----------------------------------------------------------------

('Kiến Thức Môi Trường Cơ Bản', 'kien-thuc-moi-truong-co-ban', 
 'Những kiến thức cơ bản về bảo vệ môi trường, sinh thái và tài nguyên thiên nhiên dành cho người mới bắt đầu', 
 'beginner', 10, 1),

('Tái Chế và Phân Loại Rác', 'tai-che-phan-loai-rac', 
 'Hướng dẫn cách phân loại rác thải, tái chế đúng cách và xử lý chất thải hữu cơ', 
 'beginner', 10, 1),

('Tiết Kiệm Năng Lượng Tại Nhà', 'tiet-kiem-nang-luong-tai-nha', 
 'Các cách đơn giản để tiết kiệm điện, nước và năng lượng trong sinh hoạt hàng ngày', 
 'beginner', 10, 1),

('Bảo Vệ Động Vật và Thực Vật', 'bao-ve-dong-vat-thuc-vat', 
 'Hiểu về tầm quan trọng của đa dạng sinh học và cách bảo vệ các loài động thực vật', 
 'beginner', 10, 1),

-- ----------------------------------------------------------------
-- INTERMEDIATE LEVEL CATEGORIES  
-- ----------------------------------------------------------------

('Biến Đổi Khí Hậu', 'bien-doi-khi-hau', 
 'Nguyên nhân, hậu quả và giải pháp ứng phó với biến đổi khí hậu toàn cầu', 
 'intermediate', 15, 1),

('Năng Lượng Tái Tạo', 'nang-luong-tai-tao', 
 'Các loại năng lượng tái tạo: năng lượng mặt trời, gió, thủy điện và sinh khối', 
 'intermediate', 15, 1),

('Ô Nhiễm Môi Trường', 'o-nhiem-moi-truong', 
 'Các loại ô nhiễm không khí, nước, đất và cách phòng chống ô nhiễm môi trường', 
 'intermediate', 15, 1),

('Kinh Tế Xanh và Phát Triển Bền Vững', 'kinh-te-xanh-phat-trien-ben-vung', 
 'Mô hình kinh tế xanh, phát triển bền vững và tiêu dùng có trách nhiệm', 
 'intermediate', 15, 1),

('Nông Nghiệp Hữu Cơ', 'nong-nghiep-huu-co', 
 'Phương pháp canh tác hữu cơ, permaculture và nông nghiệp bền vững', 
 'intermediate', 15, 1),

-- ----------------------------------------------------------------
-- ADVANCED LEVEL CATEGORIES
-- ----------------------------------------------------------------

('Công Nghệ Xanh và Đổi Mới', 'cong-nghe-xanh-doi-moi', 
 'Các công nghệ thân thiện môi trường, đổi mới xanh và giải pháp công nghệ bền vững', 
 'advanced', 20, 1),

('Quản Lý Tài Nguyên Nước', 'quan-ly-tai-nguyen-nuoc', 
 'Bảo tồn, quản lý và sử dụng hiệu quả tài nguyên nước ngọt', 
 'advanced', 20, 1),

('Khí Thải và Carbon Footprint', 'khi-thai-carbon-footprint', 
 'Tính toán và giảm thiểu lượng khí thải carbon cá nhân và doanh nghiệp', 
 'advanced', 20, 1),

('Chính Sách Môi Trường', 'chinh-sach-moi-truong', 
 'Luật pháp, chính sách và quy định về bảo vệ môi trường ở Việt Nam và quốc tế', 
 'advanced', 20, 1),

('Sinh Thái Học Ứng Dụng', 'sinh-thai-hoc-ung-dung', 
 'Nguyên lý sinh thái học và ứng dụng trong bảo vệ môi trường và quản lý tự nhiên', 
 'advanced', 20, 1),

-- ----------------------------------------------------------------
-- EXPERT LEVEL CATEGORIES
-- ----------------------------------------------------------------

('Nghiên Cứu Khoa Học Môi Trường', 'nghien-cuu-khoa-hoc-moi-truong', 
 'Phương pháp nghiên cứu, giám sát và đánh giá tác động môi trường', 
 'expert', 25, 1),

('Quản Lý Rủi Ro Môi Trường', 'quan-ly-rui-ro-moi-truong', 
 'Đánh giá, dự báo và quản lý các rủi ro môi trường và thiên tai', 
 'expert', 25, 1),

('Công Nghệ Sinh Học Môi Trường', 'cong-nghe-sinh-hoc-moi-truong', 
 'Ứng dụng công nghệ sinh học trong xử lý ô nhiễm và bảo vệ môi trường', 
 'expert', 25, 1),

('Hệ Thống Thông Tin Địa Lý (GIS) Môi Trường', 'gis-moi-truong', 
 'Sử dụng GIS và viễn thám trong giám sát và quản lý môi trường', 
 'expert', 25, 1);

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Display all created categories
SELECT 'QUIZ CATEGORIES CREATED:' as 'Status';
SELECT 
    category_id,
    category_name,
    category_slug,
    difficulty_level,
    points_per_question,
    CASE 
        WHEN difficulty_level = 'beginner' THEN '🟢 Cơ bản'
        WHEN difficulty_level = 'intermediate' THEN '🟡 Trung cấp'
        WHEN difficulty_level = 'advanced' THEN '🟠 Nâng cao'
        WHEN difficulty_level = 'expert' THEN '🔴 Chuyên gia'
    END as level_display
FROM quiz_categories 
ORDER BY 
    FIELD(difficulty_level, 'beginner', 'intermediate', 'advanced', 'expert'),
    category_name;

-- Summary by difficulty level
SELECT 'CATEGORIES BY DIFFICULTY LEVEL:' as 'Summary';
SELECT 
    difficulty_level as 'Difficulty Level',
    COUNT(*) as 'Number of Categories',
    AVG(points_per_question) as 'Average Points',
    MIN(points_per_question) as 'Min Points',
    MAX(points_per_question) as 'Max Points'
FROM quiz_categories 
WHERE is_active = 1
GROUP BY difficulty_level
ORDER BY FIELD(difficulty_level, 'beginner', 'intermediate', 'advanced', 'expert');

-- Total count verification
SELECT 'PHASE 25H SUMMARY:' as 'Phase';
SELECT 
    COUNT(*) as 'Total Categories Created',
    COUNT(CASE WHEN difficulty_level = 'beginner' THEN 1 END) as 'Beginner Categories',
    COUNT(CASE WHEN difficulty_level = 'intermediate' THEN 1 END) as 'Intermediate Categories',
    COUNT(CASE WHEN difficulty_level = 'advanced' THEN 1 END) as 'Advanced Categories',
    COUNT(CASE WHEN difficulty_level = 'expert' THEN 1 END) as 'Expert Categories',
    AVG(points_per_question) as 'Average Points Per Question'
FROM quiz_categories 
WHERE is_active = 1;

-- ========================================
-- PHASE 25H COMPLETION STATUS
-- ========================================

SELECT 'PHASE 25H: QUIZ CATEGORIES CONFIGURATION - COMPLETED!' as status;
SELECT 
    'Environmental Quiz Categories System Created:' as info,
    '✓ 18 Comprehensive Categories' as categories,
    '✓ 4 Difficulty Levels' as levels,
    '✓ Progressive Point System' as scoring,
    '✓ Vietnamese Localization' as localization,
    '✓ Environmental Focus' as content;

SELECT 
    'Quiz Category Features:' as features,
    '• Basic Environmental Knowledge' as f1,
    '• Recycling & Waste Management' as f2,
    '• Climate Change & Energy' as f3,
    '• Green Technology & Policy' as f4,
    '• Advanced Scientific Research' as f5,
    '• Expert Level Environmental Science' as f6;
