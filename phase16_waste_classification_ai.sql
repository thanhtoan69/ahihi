-- ========================================
-- PHASE 16: WASTE CLASSIFICATION AI
-- Advanced AI system for intelligent waste classification
-- Supports image, text, barcode, and sensor-based classification
-- ========================================

USE environmental_platform;

-- ========================================
-- WASTE CLASSIFICATION SESSIONS
-- Quản lý các phiên phân loại rác thải
-- ========================================

CREATE TABLE waste_classification_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Session Information
    user_id INT DEFAULT NULL, -- NULL for anonymous/guest users
    session_uuid VARCHAR(36) UNIQUE NOT NULL, -- UUID for tracking
    session_type ENUM('image', 'text', 'barcode', 'sensor', 'multi_modal') NOT NULL,
    device_type ENUM('mobile', 'tablet', 'desktop', 'iot_sensor', 'camera') DEFAULT 'mobile',
    
    -- Input Data
    input_type ENUM('single_image', 'multiple_images', 'text_description', 'barcode_scan', 'sensor_data', 'combined') NOT NULL,
    raw_input_data JSON NOT NULL, -- Store original input
    preprocessed_data JSON, -- Store processed input for AI
    
    -- Image specific data
    image_paths JSON, -- ["path1.jpg", "path2.jpg"]
    image_metadata JSON, -- {"resolution": "1920x1080", "format": "JPEG", "file_size": 2048}
    image_quality_score DECIMAL(3,2), -- 0.00 to 1.00
    
    -- Text specific data  
    description_text TEXT,
    keywords_extracted JSON, -- ["plastic", "bottle", "clear"]
    language_detected VARCHAR(10) DEFAULT 'vi', -- ISO language code
    
    -- Barcode specific data
    barcode_data VARCHAR(255),
    barcode_type ENUM('UPC', 'EAN', 'QR', 'Code128', 'DataMatrix') DEFAULT NULL,
    barcode_confidence DECIMAL(5,4),
    
    -- Location and Context
    location_data JSON, -- {"lat": 10.762622, "lng": 106.660172, "accuracy": 10}
    weather_context JSON, -- {"temperature": 28, "humidity": 85, "condition": "sunny"}
    time_context ENUM('morning', 'afternoon', 'evening', 'night') DEFAULT NULL,
    
    -- Session Management
    session_status ENUM('started', 'processing', 'completed', 'failed', 'abandoned') DEFAULT 'started',
    processing_start_time TIMESTAMP NULL,
    processing_end_time TIMESTAMP NULL,
    total_processing_time_ms INT DEFAULT 0,
    
    -- User Experience
    user_feedback_requested BOOLEAN DEFAULT FALSE,
    gamification_enabled BOOLEAN DEFAULT TRUE,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_user_sessions (user_id, created_at DESC),
    INDEX idx_session_type_status (session_type, session_status),
    INDEX idx_processing_time (total_processing_time_ms),
    INDEX idx_device_type (device_type, created_at),
    INDEX idx_uuid (session_uuid)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION RESULTS
-- Lưu trữ kết quả phân loại chi tiết
-- ========================================

CREATE TABLE waste_classification_results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Session Reference
    session_id INT NOT NULL,
    result_order INT DEFAULT 1, -- For multiple results per session
    
    -- AI Model Information
    model_id INT NOT NULL, -- Reference to ai_models table
    model_version VARCHAR(50) NOT NULL,
    algorithm_used ENUM('cnn', 'yolo', 'resnet', 'mobilenet', 'transformer', 'ensemble') NOT NULL,
    
    -- Classification Results
    predicted_category VARCHAR(100) NOT NULL, -- Main category
    predicted_subcategory VARCHAR(100), -- Sub-category if applicable
    predicted_material VARCHAR(100), -- Material type (plastic, metal, paper, etc.)
    predicted_brand VARCHAR(100), -- Brand recognition if applicable
    
    -- Confidence Scoring
    overall_confidence DECIMAL(5,4) NOT NULL, -- 0.0000 to 1.0000
    category_confidence DECIMAL(5,4) NOT NULL,
    material_confidence DECIMAL(5,4),
    brand_confidence DECIMAL(5,4),
    
    -- Detailed Predictions (Top 5)
    prediction_probabilities JSON NOT NULL, -- {"plastic_bottle": 0.95, "glass_bottle": 0.03, ...}
    alternative_predictions JSON, -- Top alternative classifications
    
    -- Environmental Impact
    carbon_footprint_kg DECIMAL(8,4) DEFAULT NULL, -- If disposed incorrectly
    carbon_saved_kg DECIMAL(8,4) DEFAULT NULL, -- If recycled correctly
    recyclability_score DECIMAL(3,2), -- 0.00 to 1.00
    biodegradability_days INT DEFAULT NULL, -- Days to biodegrade
    toxicity_level ENUM('none', 'low', 'medium', 'high', 'extreme') DEFAULT 'none',
    
    -- Recycling Information
    is_recyclable BOOLEAN DEFAULT FALSE,
    recycling_instructions TEXT,
    disposal_method ENUM('recycle', 'compost', 'landfill', 'hazardous_waste', 'e_waste', 'special_handling') NOT NULL,
    nearest_disposal_location_id INT DEFAULT NULL, -- Reference to recycling_locations
    
    -- Quality Assessment
    image_clarity_score DECIMAL(3,2), -- How clear was the input image
    item_visibility_score DECIMAL(3,2), -- How well the item was visible
    background_noise_level ENUM('low', 'medium', 'high') DEFAULT 'low',
    
    -- User Feedback
    user_feedback_rating INT DEFAULT NULL, -- 1-5 stars
    is_correct_classification BOOLEAN DEFAULT NULL, -- User confirmation
    user_correction TEXT DEFAULT NULL, -- If user provides correction
    feedback_timestamp TIMESTAMP NULL,
    
    -- Learning Data
    used_for_training BOOLEAN DEFAULT FALSE, -- Whether this data was used to retrain models
    training_weight DECIMAL(3,2) DEFAULT 1.00, -- Weight for training (higher for verified data)
    expert_verified BOOLEAN DEFAULT FALSE, -- Verified by environmental expert
    expert_notes TEXT DEFAULT NULL,
    
    -- Processing Details
    processing_nodes JSON, -- Which processing steps were used
    error_flags JSON, -- Any errors or warnings during processing
    debug_info JSON, -- Debug information for model improvement
    
    -- Gamification
    points_earned INT DEFAULT 0,
    accuracy_bonus_points INT DEFAULT 0,
    speed_bonus_points INT DEFAULT 0,
    streak_multiplier DECIMAL(3,2) DEFAULT 1.00,
    
    -- Timestamps
    predicted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    feedback_updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (session_id) REFERENCES waste_classification_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE RESTRICT,
    FOREIGN KEY (nearest_disposal_location_id) REFERENCES recycling_locations(location_id) ON DELETE SET NULL,
    
    INDEX idx_session_order (session_id, result_order),
    INDEX idx_model_performance (model_id, overall_confidence DESC),
    INDEX idx_correct_classifications (is_correct_classification, predicted_at),
    INDEX idx_feedback_pending (user_feedback_rating, feedback_timestamp),
    INDEX idx_training_data (used_for_training, expert_verified),
    INDEX idx_confidence_range (overall_confidence DESC, predicted_at),
    INDEX idx_category_performance (predicted_category, category_confidence DESC),
    INDEX idx_environmental_impact (carbon_saved_kg DESC, recyclability_score DESC)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION FEEDBACK
-- Thu thập phản hồi chi tiết từ người dùng
-- ========================================

CREATE TABLE waste_classification_feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Reference Data
    result_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    feedback_type ENUM('rating', 'correction', 'additional_info', 'report_error') NOT NULL,
    
    -- Feedback Content
    rating ENUM('1', '2', '3', '4', '5') DEFAULT NULL, -- Star rating
    is_helpful BOOLEAN DEFAULT NULL,
    correct_category VARCHAR(100) DEFAULT NULL, -- User's correction
    correct_material VARCHAR(100) DEFAULT NULL,
    
    -- Detailed Feedback
    feedback_text TEXT,
    improvement_suggestions TEXT,
    difficulty_level ENUM('very_easy', 'easy', 'medium', 'hard', 'very_hard') DEFAULT NULL,
    
    -- Context Information
    user_expertise_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    confidence_in_correction ENUM('low', 'medium', 'high') DEFAULT 'medium',
    
    -- Verification
    verified_by_expert BOOLEAN DEFAULT FALSE,
    expert_user_id INT DEFAULT NULL,
    verification_notes TEXT,
    verification_timestamp TIMESTAMP NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (result_id) REFERENCES waste_classification_results(result_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (expert_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_result_feedback (result_id, created_at),
    INDEX idx_user_feedback_history (user_id, created_at DESC),
    INDEX idx_expert_verification (verified_by_expert, verification_timestamp),
    INDEX idx_feedback_type (feedback_type, created_at)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION LEADERBOARD
-- Bảng xếp hạng cho gamification
-- ========================================

CREATE TABLE waste_classification_leaderboard (
    leaderboard_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- User Information
    user_id INT NOT NULL,
    username_display VARCHAR(255) NOT NULL, -- For display purposes
    
    -- Statistics
    total_classifications INT DEFAULT 0,
    correct_classifications INT DEFAULT 0,
    accuracy_percentage DECIMAL(5,2) DEFAULT 0.00, -- 0.00 to 100.00
    
    -- Points and Scoring
    total_points INT DEFAULT 0,
    accuracy_points INT DEFAULT 0,
    speed_points INT DEFAULT 0,
    streak_points INT DEFAULT 0,
    bonus_points INT DEFAULT 0,
    
    -- Streaks and Achievements
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    daily_streak_count INT DEFAULT 0,
    weekly_streak_count INT DEFAULT 0,
    
    -- Performance Metrics
    average_processing_time_ms INT DEFAULT 0,
    fastest_classification_ms INT DEFAULT NULL,
    total_time_spent_minutes INT DEFAULT 0,
    
    -- Categories Mastered
    categories_mastered JSON DEFAULT '[]', -- ["plastic", "metal", "paper"]
    expertise_level ENUM('novice', 'learner', 'skilled', 'expert', 'master') DEFAULT 'novice',
    specialization VARCHAR(100) DEFAULT NULL, -- User's best category
    
    -- Badges and Achievements
    badges_earned JSON DEFAULT '[]', -- ["speed_demon", "accuracy_ace", "eco_warrior"]
    last_badge_earned VARCHAR(100) DEFAULT NULL,
    last_badge_date TIMESTAMP NULL,
    
    -- Time Periods
    period_type ENUM('daily', 'weekly', 'monthly', 'all_time') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Rankings
    global_rank INT DEFAULT NULL,
    category_rank INT DEFAULT NULL,
    regional_rank INT DEFAULT NULL,
    
    -- Metadata
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_period (user_id, period_type, period_start),
    INDEX idx_leaderboard_ranking (period_type, total_points DESC, accuracy_percentage DESC),
    INDEX idx_user_performance (user_id, period_type, last_updated),
    INDEX idx_accuracy_leaders (accuracy_percentage DESC, total_classifications),
    INDEX idx_speed_leaders (average_processing_time_ms ASC, total_classifications),
    INDEX idx_streak_leaders (current_streak DESC, longest_streak DESC)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION CHALLENGES
-- Thử thách phân loại rác để tăng engagement
-- ========================================

CREATE TABLE waste_classification_challenges (
    challenge_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Challenge Information
    challenge_name VARCHAR(255) NOT NULL,
    challenge_slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    challenge_type ENUM('accuracy', 'speed', 'volume', 'streak', 'category_specific', 'community') NOT NULL,
    
    -- Challenge Configuration
    target_value INT NOT NULL, -- Target to achieve (accuracy %, speed ms, volume count, etc.)
    difficulty_level ENUM('easy', 'medium', 'hard', 'extreme') NOT NULL,
    category_filter VARCHAR(100) DEFAULT NULL, -- Specific waste category
    time_limit_minutes INT DEFAULT NULL,
    
    -- Rewards
    points_reward INT DEFAULT 0,
    badge_reward VARCHAR(100) DEFAULT NULL,
    special_reward_description TEXT DEFAULT NULL,
    
    -- Challenge Period
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern ENUM('daily', 'weekly', 'monthly') DEFAULT NULL,
    
    -- Participation
    max_participants INT DEFAULT NULL, -- NULL for unlimited
    current_participants INT DEFAULT 0,
    min_classifications_required INT DEFAULT 5,
    
    -- Status
    challenge_status ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming',
    featured BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    created_by INT NOT NULL, -- Admin who created the challenge
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_active_challenges (challenge_status, start_date, end_date),
    INDEX idx_challenge_type (challenge_type, difficulty_level),
    INDEX idx_featured_challenges (featured, start_date),
    INDEX idx_recurring_challenges (is_recurring, recurrence_pattern)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION CHALLENGE PARTICIPATION
-- Theo dõi sự tham gia thử thách
-- ========================================

CREATE TABLE waste_classification_challenge_participation (
    participation_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- References
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Progress Tracking
    current_progress INT DEFAULT 0,
    target_progress INT NOT NULL, -- Copy from challenge for tracking
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Performance Metrics
    classifications_count INT DEFAULT 0,
    accuracy_achieved DECIMAL(5,2) DEFAULT 0.00,
    best_speed_ms INT DEFAULT NULL,
    points_earned INT DEFAULT 0,
    
    -- Status
    participation_status ENUM('registered', 'in_progress', 'completed', 'failed', 'withdrawn') DEFAULT 'registered',
    completion_date TIMESTAMP NULL,
    reward_claimed BOOLEAN DEFAULT FALSE,
    reward_claimed_date TIMESTAMP NULL,
    
    -- Timestamps
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (challenge_id) REFERENCES waste_classification_challenges(challenge_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_challenge (user_id, challenge_id),
    INDEX idx_challenge_participants (challenge_id, participation_status),
    INDEX idx_user_challenges (user_id, participation_status, joined_at),
    INDEX idx_completed_challenges (participation_status, completion_date),
    INDEX idx_leaderboard_progress (challenge_id, current_progress DESC, accuracy_achieved DESC)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA - Vietnamese Waste Classification
-- ========================================

-- Insert sample classification sessions
INSERT INTO waste_classification_sessions (session_uuid, session_type, device_type, input_type, raw_input_data, session_status, difficulty_level, user_id) VALUES
(UUID(), 'image', 'mobile', 'single_image', '{"image_path": "/uploads/plastic_bottle.jpg", "description": "Chai nhựa trong suốt"}', 'completed', 'beginner', 1),
(UUID(), 'text', 'desktop', 'text_description', '{"description": "Hộp giấy carton màu nâu, có in chữ"}', 'completed', 'intermediate', 2),
(UUID(), 'barcode', 'mobile', 'barcode_scan', '{"barcode": "8934673123456", "type": "EAN"}', 'completed', 'beginner', 1),
(UUID(), 'multi_modal', 'tablet', 'combined', '{"image_path": "/uploads/can.jpg", "barcode": "1234567890123", "description": "Lon nhôm nước ngọt"}', 'processing', 'advanced', 2);

-- Insert sample classification results
INSERT INTO waste_classification_results (session_id, model_id, model_version, algorithm_used, predicted_category, predicted_material, overall_confidence, category_confidence, prediction_probabilities, carbon_saved_kg, recyclability_score, is_recyclable, disposal_method, points_earned) VALUES
(1, 1, '1.0.0', 'cnn', 'Chai nhựa', 'PET', 0.9500, 0.9500, '{"chai_nhua_pet": 0.95, "chai_thuy_tinh": 0.03, "binh_nhua": 0.02}', 0.5, 0.90, TRUE, 'recycle', 15),
(2, 1, '1.0.0', 'transformer', 'Giấy carton', 'Cardboard', 0.8800, 0.8800, '{"giay_carton": 0.88, "giay_bao_bi": 0.10, "giay_van_phong": 0.02}', 0.3, 0.95, TRUE, 'recycle', 12),
(3, 2, '2.1.0', 'ensemble', 'Lon kim loại', 'Aluminum', 0.9200, 0.9200, '{"lon_nhom": 0.92, "lon_thep": 0.06, "hop_kim_loai": 0.02}', 0.8, 0.98, TRUE, 'recycle', 18);

-- Insert sample feedback
INSERT INTO waste_classification_feedback (result_id, user_id, feedback_type, rating, is_helpful, feedback_text, user_expertise_level, confidence_in_correction) VALUES
(1, 1, 'rating', '5', TRUE, 'Phân loại rất chính xác, ứng dụng tuyệt vời!', 'beginner', 'high'),
(2, 2, 'correction', '4', TRUE, 'Đúng là giấy carton nhưng có thể thêm thông tin về loại carton cụ thể', 'intermediate', 'medium'),
(3, 1, 'rating', '5', TRUE, 'Nhanh và chính xác, rất hữu ích cho việc phân loại rác', 'beginner', 'high');

-- Insert sample leaderboard data
INSERT INTO waste_classification_leaderboard (user_id, username_display, total_classifications, correct_classifications, accuracy_percentage, total_points, current_streak, categories_mastered, expertise_level, period_type, period_start, period_end) VALUES
(1, 'EcoWarrior2024', 156, 142, 91.03, 2340, 12, '["plastic", "metal", "paper"]', 'skilled', 'monthly', '2024-01-01', '2024-01-31'),
(2, 'GreenClassifier', 89, 78, 87.64, 1456, 8, '["paper", "organic"]', 'learner', 'monthly', '2024-01-01', '2024-01-31');

-- Insert sample challenges
INSERT INTO waste_classification_challenges (challenge_name, challenge_slug, description, challenge_type, target_value, difficulty_level, points_reward, start_date, end_date, challenge_status, created_by) VALUES
('Thử thách độ chính xác', 'accuracy-challenge-jan', 'Đạt độ chính xác 95% trong 50 lần phân loại', 'accuracy', 95, 'hard', 500, '2024-01-01 00:00:00', '2024-01-31 23:59:59', 'active', 1),
('Thử thách tốc độ', 'speed-challenge-jan', 'Phân loại đúng trong vòng 3 giây', 'speed', 3000, 'medium', 300, '2024-01-01 00:00:00', '2024-01-31 23:59:59', 'active', 1),
('Chuyên gia nhựa', 'plastic-expert', 'Phân loại đúng 100 loại nhựa khác nhau', 'category_specific', 100, 'extreme', 1000, '2024-01-01 00:00:00', '2024-03-31 23:59:59', 'active', 1);

-- Insert sample challenge participation
INSERT INTO waste_classification_challenge_participation (challenge_id, user_id, current_progress, target_progress, progress_percentage, classifications_count, accuracy_achieved, participation_status) VALUES
(1, 1, 47, 50, 94.00, 47, 95.74, 'in_progress'),
(2, 1, 23, 50, 46.00, 23, 88.50, 'in_progress'),
(1, 2, 31, 50, 62.00, 31, 87.10, 'in_progress'),
(3, 2, 15, 100, 15.00, 15, 93.33, 'in_progress');

-- ========================================
-- ANALYTICS VIEWS FOR WASTE CLASSIFICATION
-- ========================================

CREATE VIEW waste_classification_analytics AS
SELECT 
    DATE_FORMAT(wcs.created_at, '%Y-%m-%d') as date,
    wcs.session_type,
    wcs.device_type,
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN wcs.session_status = 'completed' THEN 1 END) as completed_sessions,
    AVG(wcs.total_processing_time_ms) as avg_processing_time,
    AVG(wcr.overall_confidence) as avg_confidence,
    AVG(wcr.points_earned) as avg_points_earned,
    COUNT(CASE WHEN wcr.is_correct_classification = TRUE THEN 1 END) as correct_classifications,
    COUNT(CASE WHEN wcr.is_recyclable = TRUE THEN 1 END) as recyclable_items,
    SUM(wcr.carbon_saved_kg) as total_carbon_saved
FROM waste_classification_sessions wcs
LEFT JOIN waste_classification_results wcr ON wcs.session_id = wcr.session_id
WHERE wcs.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE_FORMAT(wcs.created_at, '%Y-%m-%d'), wcs.session_type, wcs.device_type
ORDER BY date DESC;

CREATE VIEW user_classification_performance AS
SELECT 
    u.user_id,
    u.username,
    COUNT(wcs.session_id) as total_sessions,
    COUNT(CASE WHEN wcr.is_correct_classification = TRUE THEN 1 END) as correct_classifications,
    ROUND((COUNT(CASE WHEN wcr.is_correct_classification = TRUE THEN 1 END) * 100.0 / COUNT(wcr.result_id)), 2) as accuracy_percentage,
    SUM(wcr.points_earned) as total_points,
    AVG(wcs.total_processing_time_ms) as avg_processing_time,
    MAX(wcl.current_streak) as current_streak,
    SUM(wcr.carbon_saved_kg) as total_carbon_saved
FROM users u
LEFT JOIN waste_classification_sessions wcs ON u.user_id = wcs.user_id
LEFT JOIN waste_classification_results wcr ON wcs.session_id = wcr.session_id
LEFT JOIN waste_classification_leaderboard wcl ON u.user_id = wcl.user_id AND wcl.period_type = 'all_time'
WHERE wcs.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY u.user_id, u.username
HAVING total_sessions > 0
ORDER BY accuracy_percentage DESC, total_points DESC;

-- ========================================
-- SUCCESS MESSAGE
-- ========================================

SELECT 'Phase 16: Waste Classification AI completed successfully!' as Status,
       'Created 6 specialized tables for AI waste classification with gamification' as Details;
