-- ========================================
-- PHASE 21: REPORTING & MODERATION SYSTEM
-- Environmental Platform Database
-- ========================================
-- Features:
-- - Comprehensive content reporting system
-- - Advanced moderation workflows and audit trails
-- - Automated moderation with AI integration
-- - Escalation and resolution tracking
-- - Community moderation features
-- - Appeals and review system
-- - Moderation analytics and insights
-- ========================================

-- Set SQL mode for compatibility
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ========================================
-- 1. COMPREHENSIVE REPORTS SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Reporter Information
    reporter_id INT NOT NULL,
    reporter_ip_address VARCHAR(45) DEFAULT NULL,
    reporter_user_agent TEXT DEFAULT NULL,
    is_anonymous_report BOOLEAN DEFAULT FALSE,
    
    -- Reported Content/User Details
    reported_content_type ENUM(
        'user', 'article', 'comment', 'forum_post', 'forum_reply', 
        'product', 'product_review', 'event', 'petition', 'exchange_post',
        'message', 'waste_report', 'carbon_data', 'profile', 'other'
    ) NOT NULL,
    reported_content_id INT NOT NULL,
    reported_user_id INT DEFAULT NULL,
    reported_url VARCHAR(500) DEFAULT NULL,
    
    -- Report Classification
    report_category ENUM(
        'spam', 'harassment', 'hate_speech', 'misinformation', 'copyright',
        'inappropriate_content', 'scam', 'fake_profile', 'impersonation',
        'privacy_violation', 'environmental_misinformation', 'price_manipulation',
        'fake_reviews', 'illegal_content', 'community_guidelines', 'other'
    ) NOT NULL,
    
    report_subcategory VARCHAR(100) DEFAULT NULL,
    report_severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- Report Details
    report_title VARCHAR(255) NOT NULL,
    report_description TEXT NOT NULL,
    evidence_urls JSON DEFAULT NULL,
    evidence_files JSON DEFAULT NULL,
    evidence_screenshots JSON DEFAULT NULL,
    additional_context TEXT DEFAULT NULL,
    
    -- Environmental Platform Specific
    environmental_harm_level ENUM('none', 'low', 'medium', 'high', 'severe') DEFAULT 'none',
    affects_community_trust BOOLEAN DEFAULT FALSE,
    involves_minors BOOLEAN DEFAULT FALSE,
    requires_legal_review BOOLEAN DEFAULT FALSE,
    
    -- Status & Processing
    report_status ENUM(
        'pending', 'under_review', 'investigating', 'escalated', 
        'resolved', 'dismissed', 'duplicate', 'auto_resolved'
    ) DEFAULT 'pending',
    
    priority_level ENUM('low', 'normal', 'high', 'urgent', 'emergency') DEFAULT 'normal',
    auto_flagged BOOLEAN DEFAULT FALSE,
    ai_confidence_score DECIMAL(5,4) DEFAULT NULL,
    ai_recommended_action VARCHAR(100) DEFAULT NULL,
    
    -- Assignment & Review
    assigned_moderator_id INT DEFAULT NULL,
    assigned_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    resolution_notes TEXT DEFAULT NULL,
    
    -- Community Moderation
    community_votes_helpful INT DEFAULT 0,
    community_votes_not_helpful INT DEFAULT 0,
    community_moderator_id INT DEFAULT NULL,
    community_review_status ENUM('pending', 'approved', 'rejected', 'escalated') DEFAULT 'pending',
    
    -- Escalation Tracking
    escalated_to_admin BOOLEAN DEFAULT FALSE,
    escalation_reason TEXT DEFAULT NULL,
    escalated_at TIMESTAMP NULL DEFAULT NULL,
    escalated_by INT DEFAULT NULL,
    
    -- Resolution Details
    action_taken ENUM(
        'no_action', 'content_removed', 'content_edited', 'user_warned',
        'user_suspended', 'user_banned', 'content_flagged', 'account_restricted',
        'educational_notice', 'policy_clarification', 'other'
    ) DEFAULT 'no_action',
    
    resolution_summary TEXT DEFAULT NULL,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE DEFAULT NULL,
    
    -- Appeal Process
    appeal_submitted BOOLEAN DEFAULT FALSE,
    appeal_deadline TIMESTAMP NULL DEFAULT NULL,
    appeal_status ENUM('none', 'pending', 'under_review', 'approved', 'denied') DEFAULT 'none',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Performance Indexes
    INDEX idx_reporter_status (reporter_id, report_status, created_at),
    INDEX idx_content_type_id (reported_content_type, reported_content_id),
    INDEX idx_reported_user (reported_user_id, report_status),
    INDEX idx_status_priority (report_status, priority_level, created_at),
    INDEX idx_assigned_moderator (assigned_moderator_id, report_status),
    INDEX idx_category_severity (report_category, report_severity, created_at),
    INDEX idx_auto_flagged (auto_flagged, ai_confidence_score DESC),
    INDEX idx_resolution_tracking (resolved_at, action_taken),
    INDEX idx_escalation (escalated_to_admin, escalated_at),
    INDEX idx_appeal_status (appeal_status, appeal_deadline),
    
    -- Foreign Keys
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_moderator_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (community_moderator_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (escalated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- 2. MODERATION LOGS & AUDIT TRAILS
-- ========================================

CREATE TABLE IF NOT EXISTS moderation_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Action Details
    action_type ENUM(
        'content_review', 'content_remove', 'content_restore', 'content_edit',
        'user_warn', 'user_suspend', 'user_ban', 'user_unban', 'account_restrict',
        'report_assign', 'report_resolve', 'report_escalate', 'report_dismiss',
        'appeal_process', 'policy_update', 'automated_action', 'manual_override',
        'community_decision', 'ai_flag', 'bulk_action', 'emergency_action'
    ) NOT NULL,
    
    action_category ENUM(
        'content_moderation', 'user_management', 'report_handling', 'appeal_processing',
        'policy_enforcement', 'automated_moderation', 'community_moderation', 'admin_action'
    ) NOT NULL,
    
    -- Target Information
    target_type ENUM(
        'user', 'content', 'report', 'appeal', 'comment', 'review',
        'post', 'message', 'profile', 'system', 'bulk_targets'
    ) NOT NULL,
    target_id INT DEFAULT NULL,
    target_user_id INT DEFAULT NULL,
    affected_content_ids JSON DEFAULT NULL,
    
    -- Moderator Information
    moderator_id INT NOT NULL,
    moderator_type ENUM('admin', 'moderator', 'community_mod', 'ai_system', 'automated') NOT NULL,
    moderator_session_id VARCHAR(255) DEFAULT NULL,
    
    -- Action Context
    action_reason TEXT NOT NULL,
    action_description TEXT DEFAULT NULL,
    policy_violated VARCHAR(200) DEFAULT NULL,
    evidence_referenced JSON DEFAULT NULL,
    
    -- Before/After State
    previous_state JSON DEFAULT NULL,
    new_state JSON DEFAULT NULL,
    changed_fields JSON DEFAULT NULL,
    
    -- Decision Process
    decision_confidence DECIMAL(5,2) DEFAULT NULL,
    decision_factors TEXT DEFAULT NULL,
    community_input_considered BOOLEAN DEFAULT FALSE,
    ai_recommendation_followed BOOLEAN DEFAULT NULL,
    
    -- Impact Assessment
    affected_users_count INT DEFAULT 0,
    content_items_affected INT DEFAULT 0,
    severity_impact ENUM('minimal', 'moderate', 'significant', 'major', 'critical') DEFAULT 'minimal',
    
    -- Appeal & Review
    is_reversible BOOLEAN DEFAULT TRUE,
    appeal_eligible BOOLEAN DEFAULT TRUE,
    appeal_deadline TIMESTAMP NULL DEFAULT NULL,
    review_required BOOLEAN DEFAULT FALSE,
    review_by_date DATE DEFAULT NULL,
    
    -- Automation Details
    is_automated BOOLEAN DEFAULT FALSE,
    automation_rule_id INT DEFAULT NULL,
    automation_confidence DECIMAL(5,4) DEFAULT NULL,
    manual_review_triggered BOOLEAN DEFAULT FALSE,
    
    -- Performance Metrics
    processing_time_seconds INT DEFAULT NULL,
    escalation_triggered BOOLEAN DEFAULT FALSE,
    follow_up_actions_required JSON DEFAULT NULL,
    
    -- Environmental Platform Specific
    environmental_policy_impact BOOLEAN DEFAULT FALSE,
    community_trust_impact ENUM('none', 'positive', 'negative', 'mixed') DEFAULT 'none',
    green_credentials_affected BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    effective_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Performance Indexes
    INDEX idx_moderator_action (moderator_id, action_type, created_at),
    INDEX idx_target_tracking (target_type, target_id, created_at),
    INDEX idx_action_category (action_category, action_type, created_at),
    INDEX idx_automation (is_automated, automation_rule_id),
    INDEX idx_review_required (review_required, review_by_date),
    INDEX idx_appeal_eligible (appeal_eligible, appeal_deadline),
    INDEX idx_escalation (escalation_triggered, created_at),
    INDEX idx_user_actions (target_user_id, action_type, created_at),
    INDEX idx_severity_impact (severity_impact, created_at),
    
    -- Foreign Keys
    FOREIGN KEY (moderator_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (target_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- 3. AUTOMATED MODERATION RULES
-- ========================================

CREATE TABLE IF NOT EXISTS moderation_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Rule Definition
    rule_name VARCHAR(200) NOT NULL,
    rule_description TEXT NOT NULL,
    rule_category ENUM(
        'content_filtering', 'spam_detection', 'harassment_prevention',
        'misinformation_detection', 'environmental_verification', 'community_standards',
        'user_behavior', 'automated_response', 'escalation_trigger'
    ) NOT NULL,
    
    -- Target Scope
    applies_to_content_types JSON NOT NULL,
    applies_to_user_types JSON DEFAULT NULL,
    geographic_scope JSON DEFAULT NULL,
    language_scope JSON DEFAULT '["vi", "en"]',
    
    -- Rule Logic
    trigger_conditions JSON NOT NULL,
    action_thresholds JSON NOT NULL,
    confidence_threshold DECIMAL(5,4) DEFAULT 0.8000,
    
    -- Actions
    automatic_action ENUM(
        'flag_for_review', 'remove_content', 'restrict_user', 'send_warning',
        'escalate_to_human', 'request_verification', 'apply_label', 'no_action'
    ) NOT NULL,
    
    escalation_action ENUM(
        'human_review', 'admin_review', 'community_vote', 'expert_review', 'none'
    ) DEFAULT 'human_review',
    
    -- AI Integration
    ai_model_id INT DEFAULT NULL,
    ai_model_version VARCHAR(50) DEFAULT NULL,
    ml_algorithm_type VARCHAR(100) DEFAULT NULL,
    training_data_source VARCHAR(200) DEFAULT NULL,
    
    -- Performance Metrics
    true_positive_rate DECIMAL(5,4) DEFAULT NULL,
    false_positive_rate DECIMAL(5,4) DEFAULT NULL,
    accuracy_score DECIMAL(5,4) DEFAULT NULL,
    last_performance_review DATE DEFAULT NULL,
    
    -- Rule Status
    rule_status ENUM('active', 'inactive', 'testing', 'deprecated') DEFAULT 'testing',
    priority_order INT DEFAULT 100,
    execution_frequency ENUM('real_time', 'batch_hourly', 'batch_daily', 'on_demand') DEFAULT 'real_time',
    
    -- Effectiveness Tracking
    total_executions INT DEFAULT 0,
    successful_detections INT DEFAULT 0,
    false_positives INT DEFAULT 0,
    appeals_overturned INT DEFAULT 0,
    
    -- Environmental Platform Specific
    protects_environmental_integrity BOOLEAN DEFAULT FALSE,
    prevents_greenwashing BOOLEAN DEFAULT FALSE,
    verifies_sustainability_claims BOOLEAN DEFAULT FALSE,
    
    -- Management
    created_by INT NOT NULL,
    last_modified_by INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approval_date TIMESTAMP NULL DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_executed_at TIMESTAMP NULL DEFAULT NULL,
    next_review_date DATE DEFAULT NULL,
    
    -- Indexes
    INDEX idx_rule_status (rule_status, priority_order),
    INDEX idx_category_type (rule_category, applies_to_content_types(255)),
    INDEX idx_performance (accuracy_score DESC, false_positive_rate ASC),
    INDEX idx_execution_frequency (execution_frequency, last_executed_at),
    INDEX idx_ai_model (ai_model_id, ai_model_version),
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (last_modified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- 4. COMMUNITY MODERATION SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS community_moderators (
    community_mod_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Moderator Details
    user_id INT NOT NULL,
    moderator_level ENUM('volunteer', 'trusted', 'expert', 'senior', 'lead') DEFAULT 'volunteer',
    specialization_areas JSON DEFAULT NULL,
    moderation_scope JSON DEFAULT NULL,
    
    -- Qualifications & Training
    training_completed JSON DEFAULT NULL,
    certification_level VARCHAR(100) DEFAULT NULL,
    environmental_expertise_verified BOOLEAN DEFAULT FALSE,
    community_standing_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Performance Metrics
    total_reviews_completed INT DEFAULT 0,
    accuracy_rate DECIMAL(5,4) DEFAULT NULL,
    consistency_score DECIMAL(5,2) DEFAULT NULL,
    community_feedback_score DECIMAL(5,2) DEFAULT NULL,
    
    -- Activity Tracking
    reviews_this_month INT DEFAULT 0,
    reviews_this_week INT DEFAULT 0,
    average_review_time_minutes INT DEFAULT NULL,
    last_active_date DATE DEFAULT NULL,
    
    -- Status & Permissions
    status ENUM('active', 'inactive', 'suspended', 'probation', 'retired') DEFAULT 'active',
    permissions JSON DEFAULT NULL,
    can_escalate BOOLEAN DEFAULT TRUE,
    can_override_ai BOOLEAN DEFAULT FALSE,
    max_daily_reviews INT DEFAULT 50,
    
    -- Recognition & Rewards
    recognition_badges JSON DEFAULT NULL,
    contribution_points INT DEFAULT 0,
    community_endorsements INT DEFAULT 0,
    peer_recommendations INT DEFAULT 0,
    
    -- Application & Approval
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT DEFAULT NULL,
    approval_date TIMESTAMP NULL DEFAULT NULL,
    probation_end_date DATE DEFAULT NULL,
    
    -- Performance Reviews
    last_performance_review DATE DEFAULT NULL,
    next_performance_review DATE DEFAULT NULL,
    performance_notes TEXT DEFAULT NULL,
    improvement_plan TEXT DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_status (user_id, status),
    INDEX idx_moderator_level (moderator_level, status),
    INDEX idx_performance (accuracy_rate DESC, consistency_score DESC),
    INDEX idx_activity (status, last_active_date),
    INDEX idx_specialization (specialization_areas(255)),
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Unique constraint
    UNIQUE KEY unique_user_moderator (user_id)
) ENGINE=InnoDB;

-- ========================================
-- 5. APPEALS & REVIEW SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS moderation_appeals (
    appeal_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Appeal Details
    report_id INT DEFAULT NULL,
    moderation_log_id INT DEFAULT NULL,
    appealing_user_id INT NOT NULL,
    
    -- Appeal Type
    appeal_type ENUM(
        'content_removal', 'account_suspension', 'account_ban', 'content_flag',
        'warning_issued', 'restriction_applied', 'review_decision', 'automated_action'
    ) NOT NULL,
    
    appeal_category ENUM(
        'false_positive', 'context_misunderstood', 'policy_disagreement',
        'technical_error', 'bias_claim', 'new_evidence', 'changed_circumstances'
    ) NOT NULL,
    
    -- Appeal Content
    appeal_title VARCHAR(255) NOT NULL,
    appeal_description TEXT NOT NULL,
    new_evidence_provided TEXT DEFAULT NULL,
    supporting_documents JSON DEFAULT NULL,
    context_explanation TEXT DEFAULT NULL,
    
    -- Original Decision Details
    original_action VARCHAR(200) NOT NULL,
    original_reason TEXT NOT NULL,
    original_moderator_id INT DEFAULT NULL,
    original_decision_date TIMESTAMP NOT NULL,
    
    -- Appeal Processing
    appeal_status ENUM(
        'submitted', 'under_review', 'additional_info_requested',
        'approved', 'partially_approved', 'denied', 'withdrawn'
    ) DEFAULT 'submitted',
    
    priority_level ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Review Assignment
    assigned_reviewer_id INT DEFAULT NULL,
    reviewer_type ENUM('moderator', 'admin', 'panel', 'community', 'ai_assisted') DEFAULT 'moderator',
    assigned_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Review Process
    review_method ENUM('single_reviewer', 'panel_review', 'community_vote', 'ai_assisted') DEFAULT 'single_reviewer',
    requires_expert_review BOOLEAN DEFAULT FALSE,
    expert_reviewer_id INT DEFAULT NULL,
    
    -- Community Input
    community_voting_enabled BOOLEAN DEFAULT FALSE,
    community_votes_support INT DEFAULT 0,
    community_votes_oppose INT DEFAULT 0,
    community_comments_count INT DEFAULT 0,
    
    -- Decision & Resolution
    final_decision ENUM(
        'appeal_approved', 'appeal_denied', 'partial_approval', 'decision_modified',
        'original_maintained', 'escalated_further', 'withdrawn_by_user'
    ) DEFAULT NULL,
    
    resolution_explanation TEXT DEFAULT NULL,
    corrective_action_taken TEXT DEFAULT NULL,
    policy_clarification_provided TEXT DEFAULT NULL,
    
    -- Follow-up & Learning
    precedent_set BOOLEAN DEFAULT FALSE,
    policy_update_triggered BOOLEAN DEFAULT FALSE,
    training_case_added BOOLEAN DEFAULT FALSE,
    moderator_feedback_provided BOOLEAN DEFAULT FALSE,
    
    -- Timestamps & Deadlines
    submission_deadline TIMESTAMP NOT NULL,
    review_deadline TIMESTAMP NULL DEFAULT NULL,
    decision_deadline TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Indexes
    INDEX idx_appealing_user (appealing_user_id, appeal_status),
    INDEX idx_report_appeal (report_id, appeal_status),
    INDEX idx_status_priority (appeal_status, priority_level, created_at),
    INDEX idx_assigned_reviewer (assigned_reviewer_id, appeal_status),
    INDEX idx_deadlines (review_deadline, decision_deadline),
    INDEX idx_appeal_type (appeal_type, appeal_category),
    
    -- Foreign Keys
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE SET NULL,
    FOREIGN KEY (moderation_log_id) REFERENCES moderation_logs(log_id) ON DELETE SET NULL,
    FOREIGN KEY (appealing_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_reviewer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (original_moderator_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (expert_reviewer_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- 6. MODERATION ANALYTICS & INSIGHTS
-- ========================================

CREATE TABLE IF NOT EXISTS moderation_analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Time Period
    analytics_period ENUM('hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    period_start_date DATE NOT NULL,
    period_end_date DATE NOT NULL,
    
    -- Report Volume Metrics
    total_reports_received INT DEFAULT 0,
    reports_by_category JSON DEFAULT NULL,
    reports_by_severity JSON DEFAULT NULL,
    reports_by_content_type JSON DEFAULT NULL,
    
    -- Processing Metrics
    reports_resolved INT DEFAULT 0,
    reports_pending INT DEFAULT 0,
    reports_escalated INT DEFAULT 0,
    average_resolution_time_hours DECIMAL(8,2) DEFAULT NULL,
    median_resolution_time_hours DECIMAL(8,2) DEFAULT NULL,
    
    -- Action Metrics
    actions_taken JSON DEFAULT NULL,
    content_removed_count INT DEFAULT 0,
    users_warned_count INT DEFAULT 0,
    users_suspended_count INT DEFAULT 0,
    users_banned_count INT DEFAULT 0,
    
    -- Moderator Performance
    total_moderators_active INT DEFAULT 0,
    average_moderator_workload DECIMAL(8,2) DEFAULT NULL,
    moderator_accuracy_avg DECIMAL(5,4) DEFAULT NULL,
    community_moderators_active INT DEFAULT 0,
    
    -- Automation Effectiveness
    ai_accuracy_rate DECIMAL(5,4) DEFAULT NULL,
    automated_resolutions_count INT DEFAULT 0,
    false_positive_rate DECIMAL(5,4) DEFAULT NULL,
    human_override_rate DECIMAL(5,4) DEFAULT NULL,
    
    -- Appeals & Reviews
    appeals_submitted INT DEFAULT 0,
    appeals_approved INT DEFAULT 0,
    appeals_denied INT DEFAULT 0,
    appeal_success_rate DECIMAL(5,4) DEFAULT NULL,
    
    -- Community Impact
    community_trust_score DECIMAL(5,2) DEFAULT NULL,
    user_satisfaction_score DECIMAL(5,2) DEFAULT NULL,
    platform_safety_index DECIMAL(5,2) DEFAULT NULL,
    environmental_integrity_score DECIMAL(5,2) DEFAULT NULL,
    
    -- Trend Analysis
    report_volume_trend ENUM('increasing', 'decreasing', 'stable', 'volatile') DEFAULT 'stable',
    resolution_efficiency_trend ENUM('improving', 'declining', 'stable') DEFAULT 'stable',
    quality_trend ENUM('improving', 'declining', 'stable') DEFAULT 'stable',
    
    -- Environmental Platform Specific
    greenwashing_reports_count INT DEFAULT 0,
    environmental_misinformation_count INT DEFAULT 0,
    sustainability_claims_verified INT DEFAULT 0,
    eco_fraud_prevention_actions INT DEFAULT 0,
    
    -- Recommendations & Insights
    top_violation_types JSON DEFAULT NULL,
    improvement_recommendations JSON DEFAULT NULL,
    resource_allocation_suggestions JSON DEFAULT NULL,
    policy_update_recommendations JSON DEFAULT NULL,
    
    -- System Health
    moderation_system_health_score DECIMAL(5,2) DEFAULT NULL,
    escalation_backlog_size INT DEFAULT 0,
    critical_issues_pending INT DEFAULT 0,
    system_response_time_avg_ms INT DEFAULT NULL,
    
    -- Timestamps
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_period_analytics (analytics_period, period_start_date, period_end_date),
    INDEX idx_metrics_performance (average_resolution_time_hours, moderator_accuracy_avg),
    INDEX idx_trend_analysis (report_volume_trend, resolution_efficiency_trend),
    INDEX idx_system_health (moderation_system_health_score DESC),
    
    -- Unique constraint for period
    UNIQUE KEY unique_period (analytics_period, period_start_date, period_end_date)
) ENGINE=InnoDB;
