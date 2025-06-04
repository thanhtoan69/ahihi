-- ========================================
-- PHASE 21: CORRECTED SAMPLE DATA
-- Environmental Platform Reporting & Moderation System
-- ========================================

-- ========================================
-- 1. ADDITIONAL REPORTS DATA (matching existing schema)
-- ========================================

INSERT INTO reports (
    reporter_id, reported_content_type, reported_content_id, reported_user_id,
    report_category, report_severity, report_title, report_description,
    environmental_harm_level, auto_flagged, ai_confidence_score,
    priority_level, report_status
) VALUES
-- Spam report
(1, 'comment', 1, 2, 'spam', 'medium', 'Repeated promotional comments', 
 'User is posting identical promotional content across multiple articles', 
 'low', 1, 0.8500, 'normal', 'under_review'),

-- Environmental misinformation report  
(1, 'article', 1, 2, 'environmental_misinformation', 'high', 
 'False claims about climate change', 
 'Article contains scientifically inaccurate information about global warming',
 'high', 1, 0.9200, 'high', 'escalated'),

-- Harassment report
(1, 'forum_post', 2, 2, 'harassment', 'high', 'Targeted harassment of user',
 'User is making personal attacks against other community members',
 'medium', 0, NULL, 'high', 'pending'),

-- Fake review report
(1, 'product_review', 3, 2, 'fake_reviews', 'medium', 'Suspicious product reviews',
 'Multiple reviews from same user with identical language patterns',
 'low', 1, 0.7800, 'normal', 'resolved');

-- ========================================
-- 2. MODERATION RULES DATA (matching schema)
-- ========================================

INSERT INTO moderation_rules (
    rule_name, rule_description, rule_category, applies_to_content_types,
    trigger_conditions, action_thresholds, confidence_threshold, automatic_action,
    protects_environmental_integrity, prevents_greenwashing, created_by
) VALUES
('Spam Detection Rule', 'Detect repetitive promotional content', 'spam_detection', 
 '["comment", "forum_post", "forum_reply"]',
 '{"keywords": ["buy now", "discount", "click here"], "repetition_threshold": 3}',
 '{"confidence": 0.8, "frequency": 3}', 0.8000, 'flag_for_review', 0, 0, 1),

('Environmental Misinformation Detection', 'Flag false environmental claims', 'misinformation_detection',
 '["article", "forum_post", "comment"]',
 '{"climate_denial_keywords": ["climate hoax", "global warming fake"], "source_check": true}',
 '{"confidence": 0.9, "expert_review": true}', 0.9000, 'escalate_to_human', 1, 1, 1),

('Harassment Prevention', 'Identify harassment and bullying', 'harassment_prevention',
 '["comment", "forum_post", "forum_reply", "message"]',
 '{"toxic_language": true, "personal_attacks": true, "context_analysis": true}',
 '{"severity": "high", "immediate_action": true}', 0.7500, 'flag_for_review', 0, 0, 1),

('Greenwashing Detection', 'Identify false environmental claims in products', 'environmental_verification',
 '["product", "product_review", "article"]',
 '{"unsubstantiated_claims": true, "misleading_labels": true, "verification_required": true}',
 '{"confidence": 0.85, "expert_verification": true}', 0.8500, 'request_verification', 1, 1, 1);

-- ========================================
-- 3. COMMUNITY MODERATORS DATA (using existing user_id 1)
-- ========================================

INSERT INTO community_moderators (
    user_id, moderator_level, specialization_areas, training_completed,
    certification_date, performance_score, total_cases_handled,
    accuracy_rate, community_trust_score, volunteer_status
) VALUES
(1, 'senior', 'environmental_content,spam_detection', 1, CURDATE(), 92.5, 150, 94.2, 96.0, 'active');

-- ========================================
-- 4. MODERATION LOGS DATA
-- ========================================

INSERT INTO moderation_logs (
    report_id, moderator_id, action_taken, action_reason,
    before_state, after_state, ai_assistance_used, confidence_score,
    review_time_minutes, decision_factors, impact_assessment
) VALUES
(1, 1, 'content_flagged', 'Confirmed spam pattern detected by AI', 
 '{"status": "published", "flags": 0}', '{"status": "flagged", "flags": 1}',
 1, 0.8500, 15, 'AI detection confirmed by manual review', 'Low impact - content hidden from public view');

-- ========================================
-- 5. MODERATION APPEALS DATA
-- ========================================

INSERT INTO moderation_appeals (
    report_id, appellant_user_id, appeal_reason, appeal_description,
    evidence_provided, appeal_status, community_input_allowed, appeal_priority
) VALUES
(2, 2, 'factual_dispute', 'The information in my article is based on peer-reviewed research',
 'Links to 3 scientific studies supporting the environmental claims', 'pending', 1, 'high');

-- ========================================
-- 6. MODERATION ANALYTICS DATA
-- ========================================

INSERT INTO moderation_analytics (
    date_recorded, total_reports_received, reports_auto_resolved,
    reports_human_reviewed, average_resolution_time_hours,
    ai_accuracy_rate, false_positive_rate, community_satisfaction_score,
    environmental_content_flags, trust_score_impact
) VALUES
(CURDATE(), 25, 12, 13, 4.5, 87.2, 8.3, 91.5, 8, 2.1),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 32, 18, 14, 3.8, 89.1, 6.7, 93.2, 12, 1.8),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 28, 15, 13, 5.2, 85.4, 9.1, 88.9, 6, 2.5);

-- Success confirmation
SELECT 'Phase 21 Sample Data Insertion Complete!' as Status;
