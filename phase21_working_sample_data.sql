-- ========================================
-- PHASE 21: WORKING SAMPLE DATA
-- Environmental Platform Reporting & Moderation System
-- ========================================

-- First, let's check for existing users to use as references
-- We'll use user IDs 1-5 which should exist from previous phases

-- ========================================
-- 1. SAMPLE REPORTS DATA
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
(2, 'article', 1, 3, 'environmental_misinformation', 'high', 
 'False claims about climate change', 
 'Article contains scientifically inaccurate information about global warming',
 'high', 1, 0.9200, 'high', 'escalated'),

-- Harassment report
(3, 'forum_post', 2, 4, 'harassment', 'high', 'Targeted harassment of user',
 'User is making personal attacks against other community members',
 'medium', 0, NULL, 'high', 'pending'),

-- Fake review report
(4, 'product_review', 3, 5, 'fake_reviews', 'medium', 'Suspicious product reviews',
 'Multiple reviews from same user with identical language patterns',
 'low', 1, 0.7800, 'normal', 'resolved'),

-- Privacy violation report
(5, 'user', 6, 6, 'privacy_violation', 'critical', 'Posting personal information',
 'User shared private contact details without consent',
 'none', 0, NULL, 'urgent', 'investigating');

-- ========================================
-- 2. MODERATION RULES DATA
-- ========================================

INSERT INTO moderation_rules (
    rule_name, rule_description, content_type, trigger_conditions,
    action_type, severity_threshold, ai_model_used, confidence_threshold,
    auto_action_enabled, human_review_required, rule_status
) VALUES
('Spam Detection', 'Detect repetitive promotional content', 'comment', 
 '{"keywords": ["buy now", "discount", "click here"], "repetition_threshold": 3}',
 'auto_flag', 'medium', 'spam_classifier_v2', 0.8000, 1, 0, 'active'),

('Environmental Misinformation', 'Flag false environmental claims', 'article',
 '{"climate_denial_keywords": ["climate hoax", "global warming fake"], "source_check": true}',
 'escalate', 'high', 'fact_checker_v1', 0.9000, 1, 1, 'active'),

('Harassment Detection', 'Identify harassment and bullying', 'all',
 '{"toxic_language": true, "personal_attacks": true, "context_analysis": true}',
 'human_review', 'high', 'toxicity_classifier', 0.7500, 0, 1, 'active'),

('Fake Review Detection', 'Identify suspicious review patterns', 'product_review',
 '{"language_similarity": 0.8, "posting_frequency": "high", "rating_patterns": "suspicious"}',
 'auto_flag', 'medium', 'review_authenticity', 0.8500, 1, 0, 'active');

-- ========================================
-- 3. COMMUNITY MODERATORS DATA  
-- ========================================

INSERT INTO community_moderators (
    user_id, moderator_level, specialization_areas, training_completed,
    certification_date, performance_score, total_cases_handled,
    accuracy_rate, community_trust_score, volunteer_status
) VALUES
(1, 'senior', 'environmental_content,spam_detection', 1, CURDATE(), 92.5, 150, 94.2, 96.0, 'active'),
(2, 'intermediate', 'harassment,community_guidelines', 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 88.7, 89, 91.5, 93.5, 'active'),
(3, 'junior', 'fake_reviews,basic_moderation', 1, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 85.3, 45, 87.8, 89.2, 'active'),
(4, 'trainee', 'general_moderation', 0, NULL, 0.0, 0, 0.0, 85.0, 'training');

-- ========================================
-- 4. MODERATION LOGS DATA
-- ========================================

INSERT INTO moderation_logs (
    report_id, moderator_id, action_taken, action_reason,
    before_state, after_state, ai_assistance_used, confidence_score,
    review_time_minutes, decision_factors, impact_assessment
) VALUES
(1, 1, 'content_flagged', 'Confirmed spam pattern', 
 '{"status": "published", "flags": 0}', '{"status": "flagged", "flags": 1}',
 1, 0.8500, 15, 'AI detection confirmed by manual review', 'Low impact - content hidden'),

(2, 2, 'content_removed', 'Environmental misinformation verified',
 '{"status": "published", "views": 1250}', '{"status": "removed", "reason": "misinformation"}',
 1, 0.9200, 45, 'Multiple expert sources contradicted claims', 'High impact - prevented spread of false info'),

(4, 1, 'user_warned', 'Pattern of fake reviews confirmed',
 '{"warnings": 0, "review_count": 15}', '{"warnings": 1, "review_count": 10, "restricted": true}',
 1, 0.7800, 30, 'Language analysis showed artificial patterns', 'Medium impact - user education provided');

-- ========================================
-- 5. MODERATION APPEALS DATA
-- ========================================

INSERT INTO moderation_appeals (
    report_id, appellant_user_id, appeal_reason, appeal_description,
    evidence_provided, appeal_status, assigned_reviewer_id,
    community_input_allowed, appeal_priority
) VALUES
(2, 3, 'factual_dispute', 'The information in my article is based on peer-reviewed research',
 'Links to 3 scientific studies supporting the claims', 'under_review', 2, 1, 'high'),

(4, 5, 'process_dispute', 'My reviews are genuine experiences with products I purchased',
 'Order confirmations and photos of products', 'pending', NULL, 1, 'medium');

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

-- ========================================
-- UPDATE EXISTING REPORTS WITH MODERATION ACTIVITY
-- ========================================

-- Update the spam report to show it was resolved
UPDATE reports SET 
    report_status = 'resolved',
    assigned_moderator_id = 1,
    reviewed_by = 1,
    reviewed_at = NOW(),
    action_taken = 'content_flagged',
    resolution_summary = 'Content flagged and user warned about spam policy',
    resolved_at = NOW()
WHERE report_id = 1;

-- Update environmental misinformation report  
UPDATE reports SET
    assigned_moderator_id = 2,
    escalated_to_admin = 1,
    escalated_by = 2,
    escalated_at = NOW(),
    escalation_reason = 'High severity environmental misinformation requiring expert review'
WHERE report_id = 2;
