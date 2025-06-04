-- ========================================
-- ENVIRONMENTAL PLATFORM - PHASE 1: COMPLETE REMAINING TABLES
-- ========================================

USE environmental_platform;

-- ========================================
-- CREATE MISSING TABLES
-- ========================================

-- User preferences table for extended settings
CREATE TABLE IF NOT EXISTS user_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value JSON,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    -- Unique constraint for user-key combination
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    
    -- Indexes
    INDEX idx_user_category (user_id, category),
    INDEX idx_key_public (preference_key, is_public)
) ENGINE=InnoDB;

-- User verification codes table (for email, phone verification)
CREATE TABLE IF NOT EXISTS user_verification_codes (
    verification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    verification_type ENUM('email', 'phone', 'two_factor') NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 5,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_type (user_id, verification_type),
    INDEX idx_code_expires (code, expires_at),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ========================================
-- ADD SAMPLE DATA
-- ========================================

-- Insert additional sample users if not exists
INSERT IGNORE INTO users (
    username, 
    email, 
    password_hash, 
    first_name, 
    last_name, 
    user_type, 
    is_verified, 
    is_active,
    green_points,
    experience_points,
    user_level,
    city,
    district,
    bio
) VALUES 
('admin', 'admin@ecoplatform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 'admin', TRUE, TRUE, 10000, 50000, 10, 'Hà Nội', 'Ba Đình', 'Quản trị viên hệ thống'),
('eco_user', 'user@ecoplatform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Eco', 'User', 'individual', TRUE, TRUE, 500, 2500, 3, 'Hồ Chí Minh', 'Quận 1', 'Người yêu thích môi trường và sống xanh'),
('green_lover', 'green@ecoplatform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Green', 'Lover', 'individual', TRUE, TRUE, 750, 3750, 4, 'Đà Nẵng', 'Hải Châu', 'Chuyên gia về tái chế và năng lượng xanh'),
('earth_saver', 'earth@ecoplatform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Earth', 'Saver', 'individual', TRUE, TRUE, 1200, 6000, 5, 'Cần Thơ', 'Ninh Kiều', 'Nhà hoạt động môi trường'),
('eco_business', 'business@ecoplatform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Eco', 'Business', 'business', TRUE, TRUE, 2000, 10000, 7, 'Hà Nội', 'Hoàn Kiếm', 'Doanh nghiệp sản phẩm xanh');

-- Insert default user preferences
INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value, category) VALUES
(1, 'dashboard_layout', '{"layout": "grid", "widgets": ["carbon_tracker", "recent_activities", "achievements"]}', 'dashboard'),
(1, 'notification_frequency', '{"email": "daily", "push": "immediate", "sms": "never"}', 'notifications'),
(1, 'privacy_level', '{"profile_visibility": "public", "activity_tracking": true, "data_sharing": false}', 'privacy'),
(2, 'dashboard_layout', '{"layout": "list", "widgets": ["carbon_tracker", "leaderboard"]}', 'dashboard'),
(2, 'notification_frequency', '{"email": "weekly", "push": "daily", "sms": "never"}', 'notifications'),
(3, 'dashboard_layout', '{"layout": "grid", "widgets": ["achievements", "recent_activities"]}', 'dashboard'),
(4, 'privacy_level', '{"profile_visibility": "friends_only", "activity_tracking": true, "data_sharing": true}', 'privacy');

-- Insert sample sessions for testing
INSERT IGNORE INTO user_sessions (user_id, session_token, ip_address, device_type, browser, os, is_active, expires_at) VALUES
(1, 'admin_session_token_123456', '127.0.0.1', 'desktop', 'Chrome', 'Windows', TRUE, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(2, 'user_session_token_789012', '192.168.1.100', 'mobile', 'Safari', 'iOS', TRUE, DATE_ADD(NOW(), INTERVAL 12 HOUR)),
(3, 'green_session_token_345678', '192.168.1.101', 'tablet', 'Firefox', 'Android', TRUE, DATE_ADD(NOW(), INTERVAL 6 HOUR));

-- ========================================
-- CREATE VIEWS FOR USER MANAGEMENT
-- ========================================

-- View for active user summary
CREATE OR REPLACE VIEW active_users_summary AS
SELECT 
    u.user_id,
    u.username,
    u.email,
    u.first_name,
    u.last_name,
    u.user_type,
    u.green_points,
    u.experience_points,
    u.user_level,
    u.total_carbon_saved,
    u.exchange_rating,
    u.last_active,
    u.login_streak,
    CASE 
        WHEN u.last_active >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'active'
        WHEN u.last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'inactive'
        ELSE 'dormant'
    END as activity_status,
    COUNT(DISTINCT s.session_id) as active_sessions
FROM users u
LEFT JOIN user_sessions s ON u.user_id = s.user_id AND s.is_active = TRUE AND s.expires_at > NOW()
WHERE u.is_active = TRUE AND u.is_banned = FALSE
GROUP BY u.user_id;

-- View for user location distribution
CREATE OR REPLACE VIEW user_location_stats AS
SELECT 
    city,
    district,
    COUNT(*) as user_count,
    AVG(green_points) as avg_green_points,
    SUM(total_carbon_saved) as total_carbon_saved_city
FROM users 
WHERE is_active = TRUE AND city IS NOT NULL
GROUP BY city, district
ORDER BY user_count DESC;

-- ========================================
-- CREATE STORED PROCEDURES
-- ========================================

DELIMITER //

-- Drop procedures if they exist
DROP PROCEDURE IF EXISTS UpdateUserLevel//
DROP PROCEDURE IF EXISTS CleanExpiredSessions//
DROP PROCEDURE IF EXISTS CleanExpiredPasswordResets//

-- Procedure to update user level based on experience points
CREATE PROCEDURE UpdateUserLevel(IN p_user_id INT)
BEGIN
    DECLARE v_exp_points INT;
    DECLARE v_new_level INT;
    
    -- Get current experience points
    SELECT experience_points INTO v_exp_points 
    FROM users 
    WHERE user_id = p_user_id;
    
    -- Calculate new level (example: level = floor(sqrt(exp_points / 100)))
    SET v_new_level = FLOOR(SQRT(v_exp_points / 100)) + 1;
    
    -- Update user level
    UPDATE users 
    SET user_level = v_new_level 
    WHERE user_id = p_user_id;
    
    SELECT v_new_level as new_level, v_exp_points as experience_points;
END //

-- Procedure to clean expired sessions
CREATE PROCEDURE CleanExpiredSessions()
BEGIN
    -- Update expired sessions as inactive
    UPDATE user_sessions 
    SET is_active = FALSE 
    WHERE expires_at < NOW() AND is_active = TRUE;
    
    -- Delete old expired sessions (older than 30 days)
    DELETE FROM user_sessions 
    WHERE is_active = FALSE AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Return cleanup stats
    SELECT ROW_COUNT() as sessions_cleaned;
END //

-- Procedure to clean expired password reset tokens
CREATE PROCEDURE CleanExpiredPasswordResets()
BEGIN
    -- Delete expired password reset tokens
    DELETE FROM password_resets 
    WHERE expires_at < NOW() OR used = TRUE;
    
    -- Delete old verification codes
    DELETE FROM user_verification_codes 
    WHERE expires_at < NOW() OR is_used = TRUE;
    
    SELECT ROW_COUNT() as tokens_cleaned;
END //

-- Procedure to get user dashboard data
CREATE PROCEDURE GetUserDashboard(IN p_user_id INT)
BEGIN
    -- Get user basic info
    SELECT 
        user_id,
        username,
        first_name,
        last_name,
        green_points,
        experience_points,
        user_level,
        total_carbon_saved,
        exchange_rating,
        login_streak,
        last_active
    FROM users 
    WHERE user_id = p_user_id AND is_active = TRUE;
    
    -- Get user preferences
    SELECT 
        preference_key,
        preference_value,
        category
    FROM user_preferences 
    WHERE user_id = p_user_id;
    
    -- Get active sessions count
    SELECT COUNT(*) as active_sessions 
    FROM user_sessions 
    WHERE user_id = p_user_id AND is_active = TRUE AND expires_at > NOW();
END //

DELIMITER ;

-- ========================================
-- ENABLE EVENT SCHEDULER AND CREATE EVENTS
-- ========================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Drop events if they exist
DROP EVENT IF EXISTS clean_expired_sessions_event;
DROP EVENT IF EXISTS clean_expired_resets_event;

-- Event to clean expired sessions daily
CREATE EVENT clean_expired_sessions_event
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO CALL CleanExpiredSessions();

-- Event to clean expired password resets every 6 hours
CREATE EVENT clean_expired_resets_event
ON SCHEDULE EVERY 6 HOUR
STARTS (CURRENT_TIMESTAMP + INTERVAL 1 HOUR)
DO CALL CleanExpiredPasswordResets();

-- ========================================
-- SECURITY TRIGGERS
-- ========================================

DELIMITER //

-- Drop trigger if exists
DROP TRIGGER IF EXISTS after_user_login_update//

-- Trigger to log user login activity
CREATE TRIGGER after_user_login_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Check if last_login was updated (indicating a new login)
    IF NEW.last_login IS NOT NULL AND (OLD.last_login IS NULL OR NEW.last_login > OLD.last_login) THEN
        -- Update login streak
        IF DATE(NEW.last_login) = DATE(OLD.last_active) + INTERVAL 1 DAY THEN
            -- Consecutive day login
            UPDATE users 
            SET login_streak = login_streak + 1,
                longest_streak = GREATEST(longest_streak, login_streak + 1)
            WHERE user_id = NEW.user_id;
        ELSEIF DATE(NEW.last_login) > DATE(OLD.last_active) + INTERVAL 1 DAY THEN
            -- Gap in login, reset streak
            UPDATE users 
            SET login_streak = 1
            WHERE user_id = NEW.user_id;
        END IF;
    END IF;
END //

DELIMITER ;

-- ========================================
-- TEST THE SYSTEM
-- ========================================

-- Test stored procedures
CALL UpdateUserLevel(2);
CALL GetUserDashboard(1);

-- ========================================
-- DISPLAY FINAL INFORMATION
-- ========================================

-- Show all tables
SELECT 
    'Phase 1 Complete - Environmental Platform Core User System' as status,
    COUNT(DISTINCT TABLE_NAME) as total_tables
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'environmental_platform';

-- Show table details
SELECT 
    TABLE_NAME as table_name,
    TABLE_ROWS as estimated_rows,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 2) as size_kb
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'environmental_platform'
ORDER BY TABLE_NAME;

-- Show users summary
SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN user_type = 'admin' THEN 1 END) as admin_users,
    COUNT(CASE WHEN user_type = 'individual' THEN 1 END) as individual_users,
    COUNT(CASE WHEN user_type = 'business' THEN 1 END) as business_users,
    SUM(green_points) as total_green_points,
    AVG(green_points) as avg_green_points
FROM users 
WHERE is_active = TRUE;

-- Show active sessions
SELECT 
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_sessions,
    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as valid_sessions
FROM user_sessions;

SELECT 'Phase 1: Core User System - SETUP COMPLETED SUCCESSFULLY!' as final_status;
