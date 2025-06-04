-- ========================================
-- Phase 25E: Insert Sample Users (Clean)
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- INSERT SAMPLE USERS
-- ========================================

INSERT INTO users (
    username,
    email,
    password_hash,
    first_name,
    last_name,
    user_type,
    is_verified,
    green_points,
    experience_points,
    user_level,
    is_active,
    total_carbon_saved,
    login_streak,
    longest_streak
)
VALUES
('moderator', 'moderator@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Minh', 'Moderator', 'moderator', TRUE, 500, 1000, 5, TRUE, 50.00, 0, 0),
('content_creator', 'content@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Linh', 'Creator', 'individual', TRUE, 300, 600, 3, TRUE, 30.00, 0, 0),
('ecocompany', 'business@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Eco', 'Company', 'business', TRUE, 800, 1500, 6, TRUE, 120.00, 0, 0),
('greenorg', 'org@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Green', 'Organization', 'organization', TRUE, 700, 1400, 5, TRUE, 80.00, 0, 0),
('user1', 'user1@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Hoa', 'Nguyen', 'individual', TRUE, 150, 300, 2, TRUE, 15.00, 0, 0),
('user2', 'user2@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Tuan', 'Tran', 'individual', FALSE, 50, 100, 1, TRUE, 5.00, 0, 0),
('user3', 'user3@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Mai', 'Le', 'individual', TRUE, 200, 400, 2, TRUE, 25.00, 0, 0);

SELECT 'SAMPLE USERS INSERTED SUCCESSFULLY' as status;
SELECT user_id, username, user_type, is_verified, green_points FROM users WHERE username IN ('moderator', 'content_creator', 'ecocompany', 'greenorg', 'user1', 'user2', 'user3');
