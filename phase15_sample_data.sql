-- Add sample data for AI/ML system
USE environmental_platform;

-- Add AI models
INSERT INTO ai_models (model_name, model_type, framework, model_version, environmental_focus, accuracy, model_status, created_by) VALUES
('Phân loại rác thải thông minh', 'classification', 'tensorflow', '1.0.0', 'waste_classification', 0.9250, 'deployed', 1),
('Dự đoán lượng carbon', 'regression', 'scikit_learn', '2.1.0', 'carbon_prediction', 0.8750, 'deployed', 1),
('Phát hiện ô nhiễm không khí', 'classification', 'pytorch', '1.5.0', 'pollution_detection', 0.9100, 'testing', 2),
('Điểm bền vững cộng đồng', 'regression', 'keras', '3.0.0', 'sustainability_scoring', 0.8900, 'deployed', 2),
('Nhận diện vật liệu tái chế', 'computer_vision', 'tensorflow', '2.0.0', 'waste_classification', 0.9400, 'deployed', 1);

-- Add predictions
INSERT INTO ai_predictions (model_id, user_id, input_data, input_type, prediction_result, confidence_score, environmental_impact, processing_time_ms) VALUES
(1, 1, '{"image_url": "/uploads/waste1.jpg"}', 'image', '{"category": "recyclable", "material": "plastic"}', 0.9250, '{"carbon_saved": 0.5, "category": "plastic"}', 1250),
(2, 2, '{"energy_usage": 150, "transport": "motorbike"}', 'json', '{"carbon_footprint": 2.3}', 0.8750, '{"co2_kg": 2.3, "level": "medium"}', 800),
(1, 1, '{"image_url": "/uploads/waste2.jpg"}', 'image', '{"category": "organic", "compost": true}', 0.9100, '{"carbon_saved": 1.2, "category": "organic"}', 1100);

-- Add training queue
INSERT INTO ai_training_queue (model_id, job_name, job_type, dataset_path, job_status, priority, created_by) VALUES
(1, 'Retrain waste classifier with new data', 'retraining', '/datasets/waste_2024_q1.zip', 'pending', 'high', 1),
(3, 'Pollution detection model tuning', 'hyperparameter_tuning', '/datasets/air_quality_hcm.csv', 'running', 'medium', 2);

-- Add experiments
INSERT INTO ai_experiments (experiment_name, model_id, experiment_config, hyperparameters, training_metrics, experiment_status, best_score, created_by) VALUES
('Waste Classification V2', 1, '{"epochs": 50, "batch_size": 32}', '{"learning_rate": 0.001, "dropout": 0.3}', '{"accuracy": 0.92, "loss": 0.15}', 'completed', 0.9200, 1),
('Carbon Prediction Optimization', 2, '{"cv_folds": 5, "test_size": 0.2}', '{"n_estimators": 100, "max_depth": 10}', '{"mse": 0.25, "r2": 0.87}', 'completed', 0.8700, 2);

-- Add monitoring data
INSERT INTO ai_model_monitoring (model_id, monitoring_date, total_predictions, average_confidence, average_processing_time_ms, error_count, alert_level) VALUES
(1, '2024-01-15', 1250, 0.9100, 1150, 2, 'normal'),
(2, '2024-01-15', 890, 0.8650, 850, 0, 'normal'),
(1, '2024-01-16', 1180, 0.9050, 1200, 5, 'warning');

SELECT 'AI/ML sample data added successfully!' as Status;
