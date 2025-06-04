-- ========================================
-- PHASE 15: AI/ML INFRASTRUCTURE (SIMPLIFIED)
-- Core AI/ML system for environmental platform
-- ========================================

USE environmental_platform;

-- ========================================
-- AI MODELS TABLE - Core model management
-- ========================================

CREATE TABLE ai_models (
    model_id INT PRIMARY KEY AUTO_INCREMENT,
    model_name VARCHAR(255) NOT NULL,
    model_type ENUM('classification', 'regression', 'clustering', 'recommendation', 'nlp', 'computer_vision') NOT NULL,
    framework ENUM('tensorflow', 'pytorch', 'scikit_learn', 'keras', 'custom') NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    environmental_focus ENUM('waste_classification', 'carbon_prediction', 'pollution_detection', 'sustainability_scoring') NOT NULL,
    accuracy DECIMAL(5,4) DEFAULT NULL,
    model_status ENUM('training', 'testing', 'deployed', 'deprecated') DEFAULT 'training',
    deployment_endpoint VARCHAR(500),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_model_type (model_type, environmental_focus),
    INDEX idx_status (model_status, created_at)
) ENGINE=InnoDB;

-- ========================================
-- AI PREDICTIONS TABLE - Store prediction results
-- ========================================

CREATE TABLE ai_predictions (
    prediction_id INT PRIMARY KEY AUTO_INCREMENT,
    model_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    input_data JSON NOT NULL,
    input_type ENUM('image', 'text', 'json', 'sensor_data') NOT NULL,
    prediction_result JSON NOT NULL,
    confidence_score DECIMAL(5,4),
    environmental_impact JSON,
    processing_time_ms INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_model_time (model_id, created_at),
    INDEX idx_user_predictions (user_id, created_at)
) ENGINE=InnoDB;

-- ========================================
-- AI TRAINING QUEUE - ML pipeline management
-- ========================================

CREATE TABLE ai_training_queue (
    queue_id INT PRIMARY KEY AUTO_INCREMENT,
    model_id INT NOT NULL,
    job_name VARCHAR(255) NOT NULL,
    job_type ENUM('training', 'retraining', 'validation', 'hyperparameter_tuning') NOT NULL,
    dataset_path VARCHAR(1000) NOT NULL,
    training_config JSON,
    job_status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    estimated_duration_minutes INT DEFAULT NULL,
    actual_duration_minutes INT DEFAULT NULL,
    created_by INT NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status_priority (job_status, priority),
    INDEX idx_model_jobs (model_id, created_at)
) ENGINE=InnoDB;

-- ========================================
-- AI EXPERIMENTS - Track model experiments
-- ========================================

CREATE TABLE ai_experiments (
    experiment_id INT PRIMARY KEY AUTO_INCREMENT,
    experiment_name VARCHAR(255) NOT NULL,
    model_id INT NOT NULL,
    experiment_config JSON NOT NULL,
    hyperparameters JSON,
    training_metrics JSON,
    validation_metrics JSON,
    test_metrics JSON,
    experiment_status ENUM('running', 'completed', 'failed', 'stopped') DEFAULT 'running',
    best_score DECIMAL(5,4) DEFAULT NULL,
    created_by INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_model_experiments (model_id, started_at),
    INDEX idx_best_score (best_score DESC)
) ENGINE=InnoDB;

-- ========================================
-- AI MODEL MONITORING - Production monitoring
-- ========================================

CREATE TABLE ai_model_monitoring (
    monitoring_id INT PRIMARY KEY AUTO_INCREMENT,
    model_id INT NOT NULL,
    monitoring_date DATE NOT NULL,
    total_predictions INT DEFAULT 0,
    average_confidence DECIMAL(5,4) DEFAULT NULL,
    average_processing_time_ms INT DEFAULT NULL,
    error_count INT DEFAULT 0,
    accuracy_drift DECIMAL(7,4) DEFAULT NULL,
    performance_metrics JSON,
    alert_level ENUM('normal', 'warning', 'critical') DEFAULT 'normal',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    UNIQUE KEY unique_model_date (model_id, monitoring_date),
    INDEX idx_monitoring_date (monitoring_date, alert_level),
    INDEX idx_model_performance (model_id, monitoring_date)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA - Vietnamese AI Models
-- ========================================

INSERT INTO ai_models (model_name, model_type, framework, model_version, environmental_focus, accuracy, model_status, created_by) VALUES
('Phân loại rác thải thông minh', 'classification', 'tensorflow', '1.0.0', 'waste_classification', 0.9250, 'deployed', 1),
('Dự đoán lượng carbon', 'regression', 'scikit_learn', '2.1.0', 'carbon_prediction', 0.8750, 'deployed', 1),
('Phát hiện ô nhiễm không khí', 'classification', 'pytorch', '1.5.0', 'pollution_detection', 0.9100, 'testing', 2),
('Điểm bền vững cộng đồng', 'regression', 'keras', '3.0.0', 'sustainability_scoring', 0.8900, 'deployed', 2),
('Nhận diện vật liệu tái chế', 'computer_vision', 'tensorflow', '2.0.0', 'waste_classification', 0.9400, 'deployed', 1);

-- Sample predictions
INSERT INTO ai_predictions (model_id, user_id, input_data, input_type, prediction_result, confidence_score, environmental_impact, processing_time_ms) VALUES
(1, 1, '{"image_url": "/uploads/waste1.jpg"}', 'image', '{"category": "recyclable", "material": "plastic"}', 0.9250, '{"carbon_saved": 0.5, "category": "plastic"}', 1250),
(2, 2, '{"energy_usage": 150, "transport": "motorbike"}', 'json', '{"carbon_footprint": 2.3}', 0.8750, '{"co2_kg": 2.3, "level": "medium"}', 800),
(1, 3, '{"image_url": "/uploads/waste2.jpg"}', 'image', '{"category": "organic", "compost": true}', 0.9100, '{"carbon_saved": 1.2, "category": "organic"}', 1100);

-- Sample training queue
INSERT INTO ai_training_queue (model_id, job_name, job_type, dataset_path, job_status, priority, created_by) VALUES
(1, 'Retrain waste classifier with new data', 'retraining', '/datasets/waste_2024_q1.zip', 'pending', 'high', 1),
(3, 'Pollution detection model tuning', 'hyperparameter_tuning', '/datasets/air_quality_hcm.csv', 'running', 'medium', 2);

-- Sample experiments
INSERT INTO ai_experiments (experiment_name, model_id, experiment_config, hyperparameters, training_metrics, experiment_status, best_score, created_by) VALUES
('Waste Classification V2', 1, '{"epochs": 50, "batch_size": 32}', '{"learning_rate": 0.001, "dropout": 0.3}', '{"accuracy": 0.92, "loss": 0.15}', 'completed', 0.9200, 1),
('Carbon Prediction Optimization', 2, '{"cv_folds": 5, "test_size": 0.2}', '{"n_estimators": 100, "max_depth": 10}', '{"mse": 0.25, "r2": 0.87}', 'completed', 0.8700, 2);

-- Sample monitoring data
INSERT INTO ai_model_monitoring (model_id, monitoring_date, total_predictions, average_confidence, average_processing_time_ms, error_count, alert_level) VALUES
(1, '2024-01-15', 1250, 0.9100, 1150, 2, 'normal'),
(2, '2024-01-15', 890, 0.8650, 850, 0, 'normal'),
(1, '2024-01-16', 1180, 0.9050, 1200, 5, 'warning');

-- ========================================
-- SUCCESS MESSAGE
-- ========================================

SELECT 'Phase 15: AI/ML Infrastructure completed successfully!' as Status,
       'Created 5 AI/ML tables with Vietnamese sample data' as Details;
