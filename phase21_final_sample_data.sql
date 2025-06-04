-- ========================================
-- PHASE 21: FINAL WORKING SAMPLE DATA
-- Environmental Platform Reporting & Moderation System
-- ========================================

-- ========================================
-- 1. ADDITIONAL REPORTS DATA (4 new reports)
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
-- 2. MODERATION RULES DATA (4 rules)
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
    user_id, moderator_level, specialization_areas, total_reviews_completed,
    accuracy_rate, community_standing_score, status
) VALUES
(1, 'senior', '["environmental_content", "spam_detection", "misinformation"]', 150, 0.9420, 96.0, 'active');

-- ========================================
-- 4. MODERATION LOGS DATA (3 log entries)
-- ========================================

INSERT INTO moderation_logs (
    action_type, action_category, target_type, target_id, target_user_id,
    moderator_id, moderator_type, action_reason, action_description,
    previous_state, new_state, decision_confidence, decision_factors,
    is_automated, automation_confidence, processing_time_seconds
) VALUES
('content_remove', 'content_moderation', 'content', 1, 2,
 1, 'moderator', 'Confirmed spam pattern detected by AI system', 
 'Content flagged and hidden from public view due to repeated promotional posting',
 '{"status": "published", "flags": 0, "visibility": "public"}', 
 '{"status": "flagged", "flags": 1, "visibility": "hidden"}',
 85.0, 'AI detection confirmed by manual review, pattern matching evidence', 1, 0.8500, 15),

('user_warn', 'user_management', 'user', 2, 2,
 1, 'moderator', 'Environmental misinformation verified by expert review',
 'User warned about posting scientifically inaccurate environmental content',
 '{"warnings": 0, "content_count": 25, "reputation": 100}', 
 '{"warnings": 1, "content_count": 24, "reputation": 85}',
 92.0, 'Multiple expert sources contradicted claims, peer review confirmed inaccuracy', 0, NULL, 45),

('report_resolve', 'report_handling', 'report', 1, 2,
 1, 'moderator', 'Pattern of fake reviews confirmed through analysis',
 'Report resolved with user education and content restriction',
 '{"report_status": "pending", "priority": "normal"}', 
 '{"report_status": "resolved", "action_taken": "user_warned"}',
 78.0, 'Language analysis showed artificial patterns, posting frequency suspicious', 1, 0.7800, 30);

-- ========================================
-- 5. MODERATION APPEALS DATA (2 appeals)
-- ========================================

INSERT INTO moderation_appeals (
    moderation_log_id, appealing_user_id, appeal_type, appeal_category,
    appeal_title, appeal_description, new_evidence_provided,
    original_action, original_reason, appeal_status, priority_level,
    submission_deadline
) VALUES
(2, 2, 'content_removal', 'false_positive', 
 'Appeal for environmental article removal',
 'The information in my article is based on peer-reviewed research from reputable scientific journals',
 'Links to 3 peer-reviewed studies from Nature Climate Change and IPCC reports supporting the environmental claims made in the article',
 'content_removed', 'Environmental misinformation verified by expert review',
 'under_review', 'high', DATE_ADD(NOW(), INTERVAL 7 DAY)),

(3, 2, 'warning_issued', 'context_misunderstood',
 'Appeal for user warning on product reviews',
 'My reviews are genuine experiences with eco-friendly products I have personally purchased and used',
 'Order confirmations, delivery receipts, and photos of products in use with timestamps',
 'user_warned', 'Pattern of fake reviews confirmed through analysis',
 'submitted', 'normal', DATE_ADD(NOW(), INTERVAL 5 DAY));

-- ========================================
-- 6. MODERATION ANALYTICS DATA (3 daily reports)
-- ========================================

INSERT INTO moderation_analytics (
    analytics_period, period_start_date, period_end_date,
    total_reports_received, reports_resolved, reports_pending, reports_escalated,
    average_resolution_time_hours, content_removed_count, users_warned_count,
    total_moderators_active, ai_accuracy_rate, automated_resolutions_count,
    false_positive_rate, appeals_submitted, appeals_approved,
    community_trust_score, environmental_integrity_score,
    greenwashing_reports_count, environmental_misinformation_count
) VALUES
('daily', CURDATE(), CURDATE(), 
 25, 18, 4, 3, 4.5, 8, 5, 3, 0.8720, 12, 0.0830, 2, 0, 91.5, 88.2, 4, 6),

('daily', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY),
 32, 24, 5, 3, 3.8, 12, 7, 3, 0.8910, 18, 0.0670, 3, 1, 93.2, 89.5, 6, 8),

('daily', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY),
 28, 21, 3, 4, 5.2, 10, 4, 3, 0.8540, 15, 0.0910, 1, 0, 88.9, 87.1, 3, 5);

-- ========================================
-- 7. UPDATE EXISTING REPORTS WITH MODERATION ACTIVITY
-- ========================================

-- Update the original report to show resolution
UPDATE reports SET 
    report_status = 'resolved',
    assigned_moderator_id = 1,
    reviewed_by = 1,
    reviewed_at = NOW(),
    action_taken = 'content_flagged',
    resolution_summary = 'Content flagged and user warned about community guidelines',
    resolved_at = NOW()
WHERE report_id = 1;

-- Success confirmation
SELECT 'Phase 21 Comprehensive Sample Data Successfully Inserted!' as Status,
       'System ready for testing and validation' as Message;
