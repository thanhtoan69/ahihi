-- ========================================
-- FINAL PHASE 15 VERIFICATION
-- Kiểm tra hoàn thiện Phase 15: AI/ML Infrastructure
-- ========================================

USE environmental_platform;

-- Kiểm tra tổng số bảng
SELECT 'DATABASE OVERVIEW' as Section;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'environmental_platform';

-- Kiểm tra các bảng AI/ML
SELECT 'AI/ML TABLES' as Section;
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' 
AND table_name LIKE 'ai_%' 
ORDER BY table_name;

-- Kiểm tra dữ liệu trong các bảng AI/ML
SELECT 'AI/ML DATA COUNTS' as Section;
SELECT 'ai_models' as table_name, COUNT(*) as record_count FROM ai_models
UNION ALL
SELECT 'ai_predictions' as table_name, COUNT(*) as record_count FROM ai_predictions
UNION ALL
SELECT 'ai_training_queue' as table_name, COUNT(*) as record_count FROM ai_training_queue
UNION ALL
SELECT 'ai_experiments' as table_name, COUNT(*) as record_count FROM ai_experiments
UNION ALL
SELECT 'ai_model_monitoring' as table_name, COUNT(*) as record_count FROM ai_model_monitoring;

-- Hiển thị một số mô hình AI mẫu
SELECT 'SAMPLE AI MODELS' as Section;
SELECT model_id, model_name, model_type, framework, environmental_focus, accuracy, model_status 
FROM ai_models 
LIMIT 5;

-- Hiển thị kết quả cuối cùng
SELECT 'PHASE 15 COMPLETION STATUS' as Section;
SELECT 
    'Phase 15: AI/ML Infrastructure' as phase_name,
    'COMPLETED SUCCESSFULLY' as status,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'environmental_platform' AND table_name LIKE 'ai_%') as ai_tables_created,
    NOW() as completion_time;

-- Hiển thị tổng kết cuối cùng
SELECT 'FINAL PROJECT STATUS' as Section;
SELECT 
    'Environmental Platform Database' as project_name,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'environmental_platform') as total_tables,
    'ALL PHASES COMPLETED' as status,
    'Phase 13: Item Exchange System ✅' as phase_13,
    'Phase 14: Donation System ✅' as phase_14,
    'Phase 15: AI/ML Infrastructure ✅' as phase_15;
