USE environmental_platform;

-- Drop test table first
DROP TABLE IF EXISTS waste_classification_test;

-- Create waste classification sessions table
CREATE TABLE waste_classification_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    session_type ENUM('image', 'text', 'barcode') NOT NULL DEFAULT 'image',
    input_data JSON NOT NULL,
    session_status ENUM('started', 'completed', 'failed') DEFAULT 'started',
    processing_time_ms INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_sessions (user_id, created_at)
) ENGINE=InnoDB;

-- Create waste classification results table  
CREATE TABLE waste_classification_results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    predicted_category VARCHAR(100) NOT NULL,
    confidence_score DECIMAL(5,4) NOT NULL,
    is_recyclable BOOLEAN DEFAULT FALSE,
    carbon_saved_kg DECIMAL(8,4) DEFAULT 0,
    points_earned INT DEFAULT 0,
    user_feedback_rating INT DEFAULT NULL,
    is_correct BOOLEAN DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES waste_classification_sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_results (session_id),
    INDEX idx_confidence (confidence_score DESC)
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO waste_classification_sessions (user_id, session_type, input_data, session_status, processing_time_ms, points_earned) VALUES
(1, 'image', '{"image": "bottle.jpg"}', 'completed', 1200, 15),
(2, 'text', '{"description": "plastic bottle"}', 'completed', 800, 12);

INSERT INTO waste_classification_results (session_id, predicted_category, confidence_score, is_recyclable, carbon_saved_kg, points_earned) VALUES
(1, 'Plastic Bottle', 0.9500, TRUE, 0.5, 15),
(2, 'Plastic Container', 0.8800, TRUE, 0.4, 12);

SELECT 'Phase 16: Basic Waste Classification AI created successfully!' as Status;
