-- ========================================
-- PHASE 16: WASTE CLASSIFICATION AI (SIMPLIFIED)
-- Hệ thống AI phân loại rác thải với gamification
-- ========================================

USE environmental_platform;

-- ========================================
-- WASTE CLASSIFICATION SESSIONS
-- ========================================

CREATE TABLE waste_classification_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    session_uuid VARCHAR(36) UNIQUE NOT NULL,
    session_type ENUM('image', 'text', 'barcode', 'sensor') NOT NULL DEFAULT 'image',
    device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'mobile',
    
    -- Input data
    input_data JSON NOT NULL,
    image_path VARCHAR(500),
    description_text TEXT,
    barcode_data VARCHAR(255),
    
    -- Session status
    session_status ENUM('started', 'processing', 'completed', 'failed') DEFAULT 'started',
    processing_time_ms INT DEFAULT 0,
    
    -- Gamification
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    points_potential INT DEFAULT 10,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_sessions (user_id, created_at DESC),
    INDEX idx_session_status (session_status),
    INDEX idx_session_type (session_type)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION RESULTS
-- ========================================

CREATE TABLE waste_classification_results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    model_id INT NOT NULL,
    
    -- Prediction results
    predicted_category VARCHAR(100) NOT NULL,
    predicted_material VARCHAR(100),
    confidence_score DECIMAL(5,4) NOT NULL,
    
    -- Detailed predictions
    prediction_details JSON,
    alternative_predictions JSON,
    
    -- Environmental impact
    is_recyclable BOOLEAN DEFAULT FALSE,
    carbon_saved_kg DECIMAL(8,4) DEFAULT 0.0000,
    disposal_method ENUM('recycle', 'compost', 'landfill', 'hazardous') NOT NULL,
    
    -- User feedback
    user_feedback_rating INT DEFAULT NULL, -- 1-5
    is_correct_classification BOOLEAN DEFAULT NULL,
    user_correction VARCHAR(255) DEFAULT NULL,
    
    -- Gamification
    points_earned INT DEFAULT 0,
    accuracy_bonus INT DEFAULT 0,
    
    -- Learning
    used_for_training BOOLEAN DEFAULT FALSE,
    expert_verified BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    feedback_updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (session_id) REFERENCES waste_classification_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE RESTRICT,
    
    INDEX idx_session_results (session_id),
    INDEX idx_model_performance (model_id, confidence_score DESC),
    INDEX idx_correct_classifications (is_correct_classification),
    INDEX idx_training_data (used_for_training)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION LEADERBOARD
-- ========================================

CREATE TABLE waste_classification_leaderboard (
    leaderboard_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    username_display VARCHAR(255) NOT NULL,
    
    -- Statistics
    total_classifications INT DEFAULT 0,
    correct_classifications INT DEFAULT 0,
    accuracy_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Points
    total_points INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    
    -- Performance
    average_processing_time_ms INT DEFAULT 0,
    categories_mastered JSON DEFAULT '[]',
    expertise_level ENUM('novice', 'learner', 'skilled', 'expert') DEFAULT 'novice',
    
    -- Time period
    period_type ENUM('daily', 'weekly', 'monthly', 'all_time') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Rankings
    global_rank INT DEFAULT NULL,
    
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_period (user_id, period_type, period_start),
    INDEX idx_leaderboard_ranking (period_type, total_points DESC),
    INDEX idx_accuracy_leaders (accuracy_percentage DESC)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION CHALLENGES
-- ========================================

CREATE TABLE waste_classification_challenges (
    challenge_id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_name VARCHAR(255) NOT NULL,
    challenge_slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    challenge_type ENUM('accuracy', 'speed', 'volume', 'streak') NOT NULL,
    
    -- Configuration
    target_value INT NOT NULL,
    difficulty_level ENUM('easy', 'medium', 'hard') NOT NULL,
    points_reward INT DEFAULT 0,
    
    -- Time period
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    challenge_status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    
    -- Participation
    max_participants INT DEFAULT NULL,
    current_participants INT DEFAULT 0,
    
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_active_challenges (challenge_status, start_date),
    INDEX idx_challenge_type (challenge_type)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION CHALLENGE PARTICIPATION
-- ========================================

CREATE TABLE waste_classification_challenge_participation (
    participation_id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Progress
    current_progress INT DEFAULT 0,
    target_progress INT NOT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Status
    participation_status ENUM('registered', 'in_progress', 'completed', 'failed') DEFAULT 'registered',
    completion_date TIMESTAMP NULL,
    points_earned INT DEFAULT 0,
    
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (challenge_id) REFERENCES waste_classification_challenges(challenge_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_challenge (user_id, challenge_id),
    INDEX idx_challenge_participants (challenge_id, participation_status)
) ENGINE=InnoDB;

-- ========================================
-- WASTE CLASSIFICATION FEEDBACK
-- ========================================

CREATE TABLE waste_classification_feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    result_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    
    feedback_type ENUM('rating', 'correction', 'suggestion') NOT NULL,
    rating ENUM('1', '2', '3', '4', '5') DEFAULT NULL,
    feedback_text TEXT,
    correct_category VARCHAR(100) DEFAULT NULL,
    
    -- Verification
    verified_by_expert BOOLEAN DEFAULT FALSE,
    expert_user_id INT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (result_id) REFERENCES waste_classification_results(result_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (expert_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_result_feedback (result_id),
    INDEX idx_user_feedback (user_id, created_at)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA
-- ========================================

-- Sample classification sessions
INSERT INTO waste_classification_sessions (user_id, session_uuid, session_type, input_data, session_status, processing_time_ms) VALUES
(1, UUID(), 'image', '{"image_url": "/uploads/plastic_bottle.jpg"}', 'completed', 1200),
(2, UUID(), 'text', '{"description": "Chai nhựa trong suốt có nắp xanh"}', 'completed', 800),
(1, UUID(), 'barcode', '{"barcode": "8934673123456"}', 'completed', 500);

-- Sample classification results
INSERT INTO waste_classification_results (session_id, model_id, predicted_category, predicted_material, confidence_score, is_recyclable, carbon_saved_kg, disposal_method, points_earned) VALUES
(1, 1, 'Chai nhựa PET', 'Plastic', 0.9500, TRUE, 0.5, 'recycle', 15),
(2, 1, 'Chai nhựa', 'Plastic', 0.8800, TRUE, 0.5, 'recycle', 12),
(3, 1, 'Sản phẩm nhựa', 'Plastic', 0.9200, TRUE, 0.4, 'recycle', 18);

-- Sample leaderboard data
INSERT INTO waste_classification_leaderboard (user_id, username_display, total_classifications, correct_classifications, accuracy_percentage, total_points, current_streak, period_type, period_start, period_end) VALUES
(1, 'EcoWarrior', 45, 42, 93.33, 675, 8, 'monthly', '2024-01-01', '2024-01-31'),
(2, 'GreenClassifier', 32, 28, 87.50, 420, 5, 'monthly', '2024-01-01', '2024-01-31');

-- Sample challenges
INSERT INTO waste_classification_challenges (challenge_name, challenge_slug, description, challenge_type, target_value, difficulty_level, points_reward, start_date, end_date, challenge_status, created_by) VALUES
('Thử thách độ chính xác', 'accuracy-challenge', 'Đạt độ chính xác 90% trong 30 lần phân loại', 'accuracy', 90, 'medium', 300, '2024-01-01 00:00:00', '2024-01-31 23:59:59', 'active', 1),
('Thử thách tốc độ', 'speed-challenge', 'Phân loại trong vòng 5 giây', 'speed', 5000, 'hard', 500, '2024-01-01 00:00:00', '2024-01-31 23:59:59', 'active', 1);

-- Sample challenge participation
INSERT INTO waste_classification_challenge_participation (challenge_id, user_id, current_progress, target_progress, progress_percentage, participation_status) VALUES
(1, 1, 27, 30, 90.00, 'in_progress'),
(2, 1, 12, 20, 60.00, 'in_progress'),
(1, 2, 19, 30, 63.33, 'in_progress');

-- Sample feedback
INSERT INTO waste_classification_feedback (result_id, user_id, feedback_type, rating, feedback_text) VALUES
(1, 1, 'rating', '5', 'Phân loại rất chính xác!'),
(2, 2, 'rating', '4', 'Tốt nhưng có thể cải thiện thêm'),
(3, 1, 'rating', '5', 'Tuyệt vời, app rất hữu ích');

-- ========================================
-- ANALYTICS VIEW
-- ========================================

CREATE VIEW waste_classification_summary AS
SELECT 
    DATE(wcs.created_at) as classification_date,
    wcs.session_type,
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN wcr.is_correct_classification = TRUE THEN 1 END) as correct_count,
    AVG(wcr.confidence_score) as avg_confidence,
    SUM(wcr.points_earned) as total_points,
    SUM(wcr.carbon_saved_kg) as total_carbon_saved
FROM waste_classification_sessions wcs
LEFT JOIN waste_classification_results wcr ON wcs.session_id = wcr.session_id
WHERE wcs.session_status = 'completed'
GROUP BY DATE(wcs.created_at), wcs.session_type
ORDER BY classification_date DESC;

SELECT 'Phase 16: Waste Classification AI completed successfully!' as Status,
       'Created 6 waste classification tables with gamification support' as Details;
