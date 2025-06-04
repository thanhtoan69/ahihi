-- ========================================
-- PHASE 15: AI/ML INFRASTRUCTURE
-- Advanced AI/ML system for environmental platform
-- ========================================

USE environmental_platform;

-- ========================================
-- AI MODELS TABLE
-- Quản lý các mô hình AI/ML
-- ========================================

CREATE TABLE ai_models (
    model_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Model Basic Information
    model_name VARCHAR(255) NOT NULL,
    model_slug VARCHAR(255) UNIQUE NOT NULL,
    model_type ENUM('classification', 'regression', 'clustering', 'recommendation', 'nlp', 'computer_vision', 'time_series') NOT NULL,
    framework ENUM('tensorflow', 'pytorch', 'scikit_learn', 'keras', 'xgboost', 'lightgbm', 'custom') NOT NULL,
    
    -- Model Versioning
    model_version VARCHAR(50) NOT NULL,
    version_major INT NOT NULL DEFAULT 1,
    version_minor INT NOT NULL DEFAULT 0,
    version_patch INT NOT NULL DEFAULT 0,
    is_latest_version BOOLEAN DEFAULT TRUE,
    previous_version_id INT DEFAULT NULL,
    
    -- Model Description
    description LONGTEXT,
    purpose TEXT NOT NULL, -- What this model is designed to do
    target_variable VARCHAR(255), -- For supervised learning
    feature_columns JSON, -- List of input features
    expected_input_format JSON, -- {"image": {"width": 224, "height": 224}, "text": {"max_length": 512}}
    
    -- Environmental Application
    environmental_focus ENUM('waste_classification', 'carbon_prediction', 'pollution_detection', 'weather_forecast', 'sustainability_scoring', 'behavior_analysis') NOT NULL,
    impact_category ENUM('high', 'medium', 'low') DEFAULT 'medium',
    use_cases JSON, -- ["waste sorting", "carbon footprint prediction"]
    
    -- Model Performance Metrics
    accuracy DECIMAL(5,4) DEFAULT NULL, -- 0.0000 to 1.0000
    precision_score DECIMAL(5,4) DEFAULT NULL,
    recall_score DECIMAL(5,4) DEFAULT NULL,
    f1_score DECIMAL(5,4) DEFAULT NULL,
    auc_score DECIMAL(5,4) DEFAULT NULL,
    mae DECIMAL(10,6) DEFAULT NULL, -- Mean Absolute Error
    mse DECIMAL(10,6) DEFAULT NULL, -- Mean Squared Error
    r2_score DECIMAL(5,4) DEFAULT NULL, -- R-squared for regression
    
    -- Training Information
    training_dataset_size INT DEFAULT 0,
    validation_dataset_size INT DEFAULT 0,
    test_dataset_size INT DEFAULT 0,
    training_duration_seconds INT DEFAULT 0,
    epochs_trained INT DEFAULT 0,
    batch_size INT DEFAULT 32,
    learning_rate DECIMAL(10,8) DEFAULT NULL,
    
    -- Model Architecture
    model_architecture JSON, -- {"layers": [...], "parameters": 1234567}
    input_shape JSON, -- {"height": 224, "width": 224, "channels": 3}
    output_shape JSON, -- {"classes": 10, "probability": true}
    model_size_mb DECIMAL(10,2) DEFAULT NULL,
    parameter_count BIGINT DEFAULT NULL,
    
    -- Deployment Information
    model_status ENUM('training', 'testing', 'deployed', 'deprecated', 'failed') DEFAULT 'training',
    deployment_endpoint VARCHAR(500), -- API endpoint for the model
    model_file_path VARCHAR(1000), -- Path to saved model file
    config_file_path VARCHAR(1000), -- Path to model configuration
    weights_file_path VARCHAR(1000), -- Path to model weights
    
    -- Performance Monitoring
    inference_time_ms DECIMAL(8,3) DEFAULT NULL, -- Average inference time
    memory_usage_mb DECIMAL(10,2) DEFAULT NULL,
    cpu_utilization DECIMAL(5,2) DEFAULT NULL,
    gpu_utilization DECIMAL(5,2) DEFAULT NULL,
    requests_per_second DECIMAL(8,2) DEFAULT NULL,
    
    -- Model Lifecycle
    created_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    approval_date TIMESTAMP NULL,
    deployment_date TIMESTAMP NULL,
    last_retrained TIMESTAMP NULL,
    next_retrain_date TIMESTAMP NULL,
    
    -- Metadata
    tags JSON, -- ["waste", "classification", "mobile"]
    notes LONGTEXT,
    research_paper_url VARCHAR(500),
    github_repo_url VARCHAR(500),
    documentation_url VARCHAR(500),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (previous_version_id) REFERENCES ai_models(model_id) ON DELETE SET NULL,
    
    INDEX idx_model_name_version (model_name, model_version),
    INDEX idx_framework_type (framework, model_type),
    INDEX idx_environmental_focus (environmental_focus, model_status),
    INDEX idx_performance (accuracy DESC, f1_score DESC),
    INDEX idx_deployment_status (model_status, deployment_date),
    INDEX idx_latest_version (is_latest_version, created_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- AI PREDICTIONS TABLE
-- Lưu trữ kết quả dự đoán từ các mô hình AI
-- ========================================

CREATE TABLE ai_predictions (
    prediction_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Model and Request Information
    model_id INT NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    user_id INT DEFAULT NULL, -- NULL for system-generated predictions
    session_id VARCHAR(255), -- To group related predictions
    request_id VARCHAR(255) UNIQUE, -- External request identifier
    
    -- Input Data
    input_data JSON NOT NULL, -- The actual input sent to the model
    input_type ENUM('image', 'text', 'json', 'csv', 'audio', 'video', 'sensor_data') NOT NULL,
    input_source ENUM('user_upload', 'api_request', 'batch_processing', 'scheduled_task', 'mobile_app') DEFAULT 'user_upload',
    input_file_path VARCHAR(1000), -- Path to input file if applicable
    preprocessing_applied JSON, -- {"resize": [224, 224], "normalize": true}
    
    -- Prediction Results
    prediction_result JSON NOT NULL, -- The model's output/prediction
    confidence_score DECIMAL(5,4), -- Model's confidence in the prediction
    probability_distribution JSON, -- For classification: {"class1": 0.8, "class2": 0.2}
    prediction_category VARCHAR(255), -- Main predicted category/class
    prediction_value DECIMAL(15,6), -- For regression models
    
    -- Environmental Context
    environmental_impact JSON, -- {"carbon_saved": 1.5, "waste_category": "recyclable"}
    location_data JSON, -- {"lat": 10.123, "lng": 106.456, "city": "Ho Chi Minh"}
    weather_conditions JSON, -- Weather data if relevant
    time_context ENUM('morning', 'afternoon', 'evening', 'night') DEFAULT NULL,
    
    -- Performance Metrics
    processing_time_ms INT NOT NULL,
    model_accuracy_estimate DECIMAL(5,4), -- Estimated accuracy for this specific prediction
    human_feedback_score INT DEFAULT NULL, -- 1-5 rating from users
    is_correct BOOLEAN DEFAULT NULL, -- Human verification
    correction_provided TEXT, -- If prediction was wrong, what was the correct answer
    
    -- Prediction Metadata
    prediction_context ENUM('waste_classification', 'carbon_estimation', 'recommendation', 'anomaly_detection', 'forecasting') NOT NULL,
    business_impact ENUM('high', 'medium', 'low') DEFAULT 'medium',
    used_for_training BOOLEAN DEFAULT FALSE, -- Can this prediction be used for retraining
    flagged_for_review BOOLEAN DEFAULT FALSE,
    review_reason TEXT,
    
    -- API and Technical Details
    api_version VARCHAR(20) DEFAULT '1.0',
    client_info JSON, -- {"user_agent": "...", "app_version": "1.2.3"}
    request_headers JSON, -- Relevant headers from the request
    response_code INT DEFAULT 200,
    error_message TEXT, -- If prediction failed
    
    -- Feedback and Learning
    user_feedback TEXT,
    feedback_rating INT CHECK (feedback_rating BETWEEN 1 AND 5),
    feedback_provided_at TIMESTAMP NULL,
    expert_validation BOOLEAN DEFAULT NULL,
    expert_notes TEXT,
    validated_by INT DEFAULT NULL,
    validated_at TIMESTAMP NULL,
    
    -- Data Privacy
    contains_pii BOOLEAN DEFAULT FALSE, -- Personally Identifiable Information
    data_retention_days INT DEFAULT 365,
    anonymized BOOLEAN DEFAULT FALSE,
    gdpr_compliant BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT NULL, -- When this prediction becomes stale
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_model_user (model_id, user_id),
    INDEX idx_prediction_context (prediction_context, created_at DESC),
    INDEX idx_confidence_score (confidence_score DESC),
    INDEX idx_training_data (used_for_training, is_correct),
    INDEX idx_session_predictions (session_id, created_at),
    INDEX idx_feedback_review (flagged_for_review, human_feedback_score),
    INDEX idx_expiry (expires_at, created_at)
) ENGINE=InnoDB;

-- ========================================
-- AI TRAINING QUEUE TABLE
-- Quản lý pipeline training và retraining
-- ========================================

CREATE TABLE ai_training_queue (
    queue_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Training Job Information
    job_name VARCHAR(255) NOT NULL,
    job_type ENUM('initial_training', 'retraining', 'transfer_learning', 'fine_tuning', 'validation', 'testing') NOT NULL,
    model_id INT DEFAULT NULL, -- NULL for new models
    parent_model_id INT DEFAULT NULL, -- For transfer learning
    
    -- Training Configuration
    training_config JSON NOT NULL, -- Complete training configuration
    dataset_config JSON NOT NULL, -- Dataset information and paths
    hyperparameters JSON, -- {"learning_rate": 0.001, "batch_size": 32}
    training_framework ENUM('tensorflow', 'pytorch', 'scikit_learn', 'keras', 'xgboost', 'lightgbm', 'custom') NOT NULL,
    
    -- Resource Requirements
    required_gpu_memory_gb DECIMAL(6,2) DEFAULT NULL,
    required_ram_gb DECIMAL(6,2) DEFAULT NULL,
    required_storage_gb DECIMAL(8,2) DEFAULT NULL,
    estimated_training_hours DECIMAL(8,2) DEFAULT NULL,
    max_training_hours DECIMAL(8,2) DEFAULT 24,
    cpu_cores_required INT DEFAULT 4,
    gpu_required BOOLEAN DEFAULT FALSE,
    
    -- Queue Management
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('queued', 'running', 'paused', 'completed', 'failed', 'cancelled') DEFAULT 'queued',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    current_epoch INT DEFAULT 0,
    total_epochs INT DEFAULT 100,
    
    -- Scheduling
    scheduled_start_time TIMESTAMP DEFAULT NULL,
    actual_start_time TIMESTAMP NULL,
    estimated_completion_time TIMESTAMP NULL,
    actual_completion_time TIMESTAMP NULL,
    max_retries INT DEFAULT 3,
    retry_count INT DEFAULT 0,
    
    -- Resource Allocation
    assigned_worker_id VARCHAR(255), -- ID of the worker/server processing this job
    worker_hostname VARCHAR(255),
    worker_ip VARCHAR(45),
    gpu_device_id VARCHAR(50),
    process_id INT DEFAULT NULL,
    
    -- Training Progress
    current_loss DECIMAL(10,6) DEFAULT NULL,
    best_loss DECIMAL(10,6) DEFAULT NULL,
    current_accuracy DECIMAL(5,4) DEFAULT NULL,
    best_accuracy DECIMAL(5,4) DEFAULT NULL,
    learning_curve_data JSON, -- Store loss/accuracy history
    training_logs_path VARCHAR(1000),
    checkpoint_path VARCHAR(1000),
    
    -- Error Handling
    error_message LONGTEXT,
    error_code VARCHAR(50),
    stack_trace LONGTEXT,
    last_error_time TIMESTAMP NULL,
    
    -- Notifications
    notify_on_completion BOOLEAN DEFAULT TRUE,
    notification_email VARCHAR(255),
    notification_webhook_url VARCHAR(500),
    slack_channel VARCHAR(100),
    
    -- Training Results
    final_model_path VARCHAR(1000),
    training_metrics JSON, -- Final training metrics
    validation_metrics JSON, -- Final validation metrics
    test_metrics JSON, -- Final test metrics
    model_artifacts_path VARCHAR(1000), -- Path to all model artifacts
    
    -- Metadata
    created_by INT NOT NULL,
    cancelled_by INT DEFAULT NULL,
    cancellation_reason TEXT,
    tags JSON, -- ["experiment", "production", "research"]
    notes LONGTEXT,
    
    -- Environment Information
    training_environment JSON, -- {"python_version": "3.9", "cuda_version": "11.2"}
    dependencies JSON, -- List of package versions used
    docker_image VARCHAR(255),
    conda_environment VARCHAR(255),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_model_id) REFERENCES ai_models(model_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_status_priority (status, priority, created_at),
    INDEX idx_model_training (model_id, job_type, status),
    INDEX idx_worker_assignment (assigned_worker_id, status),
    INDEX idx_scheduled_jobs (scheduled_start_time, status),
    INDEX idx_completion_time (actual_completion_time DESC),
    INDEX idx_progress_monitoring (status, progress_percentage)
) ENGINE=InnoDB;

-- ========================================
-- AI MODEL EXPERIMENTS TABLE
-- Theo dõi các thí nghiệm và so sánh mô hình
-- ========================================

CREATE TABLE ai_experiments (
    experiment_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Experiment Information
    experiment_name VARCHAR(255) NOT NULL,
    experiment_description LONGTEXT,
    experiment_type ENUM('model_comparison', 'hyperparameter_tuning', 'feature_selection', 'data_augmentation', 'architecture_search') NOT NULL,
    research_question TEXT,
    hypothesis TEXT,
    
    -- Experiment Configuration
    base_model_id INT DEFAULT NULL,
    experiment_config JSON NOT NULL, -- Full experiment configuration
    dataset_versions JSON, -- Which datasets were used
    evaluation_metrics JSON, -- Which metrics to track
    success_criteria JSON, -- What defines success
    
    -- Experiment Status
    status ENUM('planned', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'planned',
    start_date TIMESTAMP DEFAULT NULL,
    end_date TIMESTAMP DEFAULT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Results
    best_model_id INT DEFAULT NULL,
    results_summary JSON, -- Key findings and metrics
    performance_comparison JSON, -- Comparison between different runs
    statistical_significance JSON, -- P-values, confidence intervals
    
    -- Reproducibility
    random_seed INT DEFAULT NULL,
    environment_snapshot JSON, -- Software versions, hardware specs
    code_version VARCHAR(255), -- Git commit hash
    data_version VARCHAR(255), -- Dataset version used
    
    -- Collaboration
    created_by INT NOT NULL,
    team_members JSON, -- List of team member IDs
    shared_with JSON, -- Who has access to this experiment
    
    -- Documentation
    methodology TEXT,
    conclusions TEXT,
    recommendations TEXT,
    future_work TEXT,
    published_paper_url VARCHAR(500),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (base_model_id) REFERENCES ai_models(model_id) ON DELETE SET NULL,
    FOREIGN KEY (best_model_id) REFERENCES ai_models(model_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_experiment_type (experiment_type, status),
    INDEX idx_created_by (created_by, created_at DESC),
    INDEX idx_status_dates (status, start_date, end_date)
) ENGINE=InnoDB;

-- ========================================
-- AI MODEL MONITORING TABLE
-- Giám sát hiệu suất mô hình trong production
-- ========================================

CREATE TABLE ai_model_monitoring (
    monitoring_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Model Information
    model_id INT NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    monitoring_date DATE NOT NULL,
    monitoring_hour TINYINT DEFAULT NULL, -- 0-23 for hourly monitoring
    
    -- Performance Metrics
    total_predictions INT DEFAULT 0,
    successful_predictions INT DEFAULT 0,
    failed_predictions INT DEFAULT 0,
    avg_confidence_score DECIMAL(5,4),
    avg_processing_time_ms DECIMAL(8,3),
    
    -- Accuracy Tracking
    human_verified_predictions INT DEFAULT 0,
    correct_predictions INT DEFAULT 0,
    accuracy_rate DECIMAL(5,4),
    precision_rate DECIMAL(5,4),
    recall_rate DECIMAL(5,4),
    f1_score DECIMAL(5,4),
    
    -- Data Drift Detection
    data_drift_score DECIMAL(5,4), -- 0 = no drift, 1 = complete drift
    feature_importance_changes JSON, -- Changes in feature importance
    distribution_changes JSON, -- Statistical changes in input distributions
    drift_alert_triggered BOOLEAN DEFAULT FALSE,
    
    -- Resource Usage
    avg_cpu_usage DECIMAL(5,2),
    avg_memory_usage_mb DECIMAL(10,2),
    avg_gpu_usage DECIMAL(5,2),
    peak_memory_usage_mb DECIMAL(10,2),
    
    -- Business Metrics
    user_satisfaction_score DECIMAL(3,2), -- Average user rating
    business_impact_score DECIMAL(5,2),
    cost_per_prediction DECIMAL(10,6),
    revenue_generated DECIMAL(12,2),
    
    -- Alerts and Issues
    alerts_triggered JSON, -- List of alerts triggered
    error_rate DECIMAL(5,4),
    timeout_rate DECIMAL(5,4),
    common_errors JSON, -- Most frequent error messages
    
    -- Recommendations
    retraining_recommended BOOLEAN DEFAULT FALSE,
    retraining_urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    performance_trend ENUM('improving', 'stable', 'degrading', 'unknown') DEFAULT 'unknown',
    
    -- Environmental Impact
    predictions_by_category JSON, -- {"waste": 100, "carbon": 50}
    environmental_impact_metrics JSON, -- {"carbon_saved": 1500, "waste_diverted": 200}
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES ai_models(model_id) ON DELETE CASCADE,
    
    INDEX idx_model_monitoring (model_id, monitoring_date, monitoring_hour),
    INDEX idx_performance_alerts (retraining_recommended, retraining_urgency),
    INDEX idx_accuracy_tracking (accuracy_rate DESC, monitoring_date DESC),
    INDEX idx_drift_detection (data_drift_score DESC, drift_alert_triggered),
    UNIQUE KEY unique_model_date_hour (model_id, monitoring_date, monitoring_hour)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA FOR AI/ML SYSTEM
-- ========================================

-- Insert sample AI models
INSERT INTO ai_models (model_name, model_slug, model_type, framework, model_version, description, purpose, environmental_focus, accuracy, created_by) VALUES
('WasteNet Classifier V2', 'wastenet-classifier-v2', 'classification', 'tensorflow', '2.1.0', 'Mô hình phân loại rác thải sử dụng computer vision để nhận dạng các loại rác khác nhau', 'Phân loại rác thải từ hình ảnh người dùng chụp', 'waste_classification', 0.9240, 1),
('CarbonPredict Pro', 'carbonpredict-pro', 'regression', 'pytorch', '1.3.2', 'Mô hình dự đoán lượng carbon footprint dựa trên hoạt động hàng ngày', 'Tính toán và dự đoán carbon footprint cá nhân', 'carbon_prediction', 0.8850, 1),
('EcoRecommend Engine', 'ecorecommend-engine', 'recommendation', 'scikit_learn', '1.0.5', 'Hệ thống gợi ý sản phẩm thân thiện môi trường cho người dùng', 'Gợi ý sản phẩm xanh phù hợp với sở thích người dùng', 'sustainability_scoring', 0.8120, 1),
('PollutionWatch AI', 'pollutionwatch-ai', 'time_series', 'tensorflow', '1.2.1', 'Mô hình dự báo mức độ ô nhiễm không khí theo thời gian', 'Dự báo chất lượng không khí và cảnh báo ô nhiễm', 'pollution_detection', 0.8975, 1),
('GreenBehavior Analyzer', 'greenbehavior-analyzer', 'clustering', 'scikit_learn', '2.0.0', 'Phân tích và nhóm hành vi xanh của người dùng', 'Phân loại người dùng theo mức độ thân thiện môi trường', 'behavior_analysis', 0.7650, 1);

-- Insert sample training queue jobs
INSERT INTO ai_training_queue (job_name, job_type, model_id, training_framework, priority, status, progress_percentage, created_by) VALUES
('WasteNet Retraining June 2025', 'retraining', 1, 'tensorflow', 'high', 'completed', 100.00, 1),
('CarbonPredict Hyperparameter Tuning', 'fine_tuning', 2, 'pytorch', 'medium', 'running', 67.50, 1),
('EcoRecommend Model Update', 'retraining', 3, 'scikit_learn', 'medium', 'queued', 0.00, 1),
('PollutionWatch Validation', 'validation', 4, 'tensorflow', 'low', 'completed', 100.00, 1),
('New Waste Classification Model', 'initial_training', NULL, 'tensorflow', 'high', 'queued', 0.00, 1);

-- Insert sample predictions
INSERT INTO ai_predictions (model_id, model_version, user_id, input_type, prediction_context, prediction_result, confidence_score, processing_time_ms, prediction_category) VALUES
(1, '2.1.0', 1, 'image', 'waste_classification', '{"category": "plastic", "subcategory": "bottle", "recyclable": true}', 0.9540, 245, 'plastic'),
(1, '2.1.0', 1, 'image', 'waste_classification', '{"category": "organic", "subcategory": "food_waste", "recyclable": false}', 0.8820, 189, 'organic'),
(2, '1.3.2', 1, 'json', 'carbon_estimation', '{"daily_footprint": 12.5, "weekly_estimate": 87.5, "recommendations": ["use_bike", "reduce_meat"]}', 0.8910, 156, 'medium_impact'),
(3, '1.0.5', 1, 'json', 'recommendation', '{"products": [{"id": 123, "score": 0.92}, {"id": 456, "score": 0.87}]}', 0.8650, 98, 'eco_products'),
(4, '1.2.1', NULL, 'sensor_data', 'forecasting', '{"aqi_forecast": [85, 92, 78, 65], "alert_level": "moderate"}', 0.8420, 312, 'air_quality');

-- Insert sample experiments
INSERT INTO ai_experiments (experiment_name, experiment_description, experiment_type, base_model_id, status, created_by) VALUES
('WasteNet Accuracy Improvement', 'Thí nghiệm cải thiện độ chính xác của mô hình phân loại rác bằng data augmentation', 'data_augmentation', 1, 'completed', 1),
('Carbon Prediction Hyperparameter Optimization', 'Tối ưu hóa hyperparameter cho mô hình dự đoán carbon footprint', 'hyperparameter_tuning', 2, 'running', 1),
('Multi-Model Comparison Study', 'So sánh hiệu suất của các mô hình khác nhau cho bài toán phân loại rác', 'model_comparison', NULL, 'planned', 1);

-- Insert sample monitoring data
INSERT INTO ai_model_monitoring (model_id, model_version, monitoring_date, total_predictions, successful_predictions, avg_confidence_score, avg_processing_time_ms, accuracy_rate) VALUES
(1, '2.1.0', CURDATE(), 1245, 1189, 0.8940, 198.5, 0.9240),
(2, '1.3.2', CURDATE(), 892, 876, 0.8650, 145.8, 0.8850),
(3, '1.0.5', CURDATE(), 2156, 2089, 0.8320, 89.2, 0.8120),
(4, '1.2.1', CURDATE(), 456, 445, 0.8510, 287.9, 0.8975),
(5, '2.0.0', CURDATE(), 678, 651, 0.7890, 156.3, 0.7650);

-- ========================================
-- ANALYTICS VIEWS FOR AI/ML SYSTEM
-- ========================================

-- AI Model Performance Overview
CREATE VIEW ai_model_performance_overview AS
SELECT 
    m.model_id,
    m.model_name,
    m.model_type,
    m.environmental_focus,
    m.accuracy,
    m.model_status,
    COUNT(p.prediction_id) as total_predictions_made,
    AVG(p.confidence_score) as avg_confidence,
    AVG(p.processing_time_ms) as avg_processing_time,
    SUM(CASE WHEN p.is_correct = TRUE THEN 1 ELSE 0 END) / COUNT(p.prediction_id) as real_world_accuracy,
    AVG(p.human_feedback_score) as avg_user_rating
FROM ai_models m
LEFT JOIN ai_predictions p ON m.model_id = p.model_id
WHERE m.model_status = 'deployed'
GROUP BY m.model_id;

-- Training Queue Status
CREATE VIEW training_queue_status AS
SELECT 
    status,
    COUNT(*) as job_count,
    AVG(progress_percentage) as avg_progress,
    SUM(estimated_training_hours) as total_estimated_hours,
    MIN(created_at) as oldest_job,
    MAX(created_at) as newest_job
FROM ai_training_queue
GROUP BY status;

-- Daily AI Predictions Summary
CREATE VIEW daily_ai_predictions_summary AS
SELECT 
    DATE(created_at) as prediction_date,
    prediction_context,
    COUNT(*) as total_predictions,
    AVG(confidence_score) as avg_confidence,
    AVG(processing_time_ms) as avg_processing_time,
    COUNT(CASE WHEN human_feedback_score >= 4 THEN 1 END) as positive_feedback_count
FROM ai_predictions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), prediction_context
ORDER BY prediction_date DESC;

-- ========================================
-- STORED PROCEDURES FOR AI/ML OPERATIONS
-- ========================================

DELIMITER //

-- Procedure to add a new prediction
CREATE PROCEDURE AddAIPrediction(
    IN p_model_id INT,
    IN p_model_version VARCHAR(50),
    IN p_user_id INT,
    IN p_input_data JSON,
    IN p_input_type VARCHAR(50),
    IN p_prediction_result JSON,
    IN p_confidence_score DECIMAL(5,4),
    IN p_processing_time_ms INT,
    IN p_prediction_context VARCHAR(50)
)
BEGIN
    DECLARE v_prediction_id INT;
    
    INSERT INTO ai_predictions (
        model_id, model_version, user_id, input_data, input_type,
        prediction_result, confidence_score, processing_time_ms, prediction_context
    ) VALUES (
        p_model_id, p_model_version, p_user_id, p_input_data, p_input_type,
        p_prediction_result, p_confidence_score, p_processing_time_ms, p_prediction_context
    );
    
    SET v_prediction_id = LAST_INSERT_ID();
    
    -- Update model usage statistics
    UPDATE ai_models 
    SET 
        updated_at = CURRENT_TIMESTAMP
    WHERE model_id = p_model_id;
    
    SELECT v_prediction_id as prediction_id;
END //

-- Procedure to queue a new training job
CREATE PROCEDURE QueueTrainingJob(
    IN p_job_name VARCHAR(255),
    IN p_job_type VARCHAR(50),
    IN p_model_id INT,
    IN p_training_config JSON,
    IN p_dataset_config JSON,
    IN p_framework VARCHAR(50),
    IN p_priority VARCHAR(10),
    IN p_created_by INT
)
BEGIN
    DECLARE v_queue_id INT;
    
    INSERT INTO ai_training_queue (
        job_name, job_type, model_id, training_config, dataset_config,
        training_framework, priority, created_by
    ) VALUES (
        p_job_name, p_job_type, p_model_id, p_training_config, p_dataset_config,
        p_framework, p_priority, p_created_by
    );
    
    SET v_queue_id = LAST_INSERT_ID();
    
    SELECT v_queue_id as queue_id;
END //

-- Procedure to update model performance metrics
CREATE PROCEDURE UpdateModelPerformance(
    IN p_model_id INT,
    IN p_accuracy DECIMAL(5,4),
    IN p_precision_score DECIMAL(5,4),
    IN p_recall_score DECIMAL(5,4),
    IN p_f1_score DECIMAL(5,4)
)
BEGIN
    UPDATE ai_models 
    SET 
        accuracy = p_accuracy,
        precision_score = p_precision_score,
        recall_score = p_recall_score,
        f1_score = p_f1_score,
        updated_at = CURRENT_TIMESTAMP
    WHERE model_id = p_model_id;
    
    SELECT 'Model performance updated successfully' as result;
END //

DELIMITER ;

-- ========================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ========================================

-- Additional performance indexes
CREATE INDEX idx_predictions_user_date ON ai_predictions(user_id, created_at DESC);
CREATE INDEX idx_predictions_model_confidence ON ai_predictions(model_id, confidence_score DESC);
CREATE INDEX idx_training_queue_priority ON ai_training_queue(priority, status, created_at);
CREATE INDEX idx_monitoring_model_date ON ai_model_monitoring(model_id, monitoring_date DESC);
CREATE INDEX idx_experiments_status ON ai_experiments(status, experiment_type);

COMMIT;
