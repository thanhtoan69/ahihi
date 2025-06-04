-- Data Validation View
CREATE OR REPLACE VIEW analytics_data_validation_final AS
SELECT 
    'Users with Sessions' as validation_check,
    COUNT(*) as total_count,
    'Valid user sessions' as description
FROM users u 
WHERE EXISTS (SELECT 1 FROM user_sessions us WHERE us.user_id = u.user_id)

UNION ALL

SELECT 
    'Articles with Interactions' as validation_check,
    COUNT(*) as total_count,
    'Articles that have user interactions' as description
FROM articles a 
WHERE EXISTS (SELECT 1 FROM article_interactions ai WHERE ai.article_id = a.article_id)

UNION ALL

SELECT 
    'Users with Carbon Data' as validation_check,
    COUNT(*) as total_count,
    'Users tracking carbon footprint' as description
FROM users u 
WHERE EXISTS (SELECT 1 FROM carbon_footprints cf WHERE cf.user_id = u.user_id);
