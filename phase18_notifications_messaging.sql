-- ========================================
-- PHASE 18: NOTIFICATIONS & MESSAGING SYSTEM
-- Environmental Platform Database
-- Date: June 3, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. NOTIFICATION CHANNELS CONFIGURATION
-- ========================================

CREATE TABLE notification_channels (
    channel_id INT PRIMARY KEY AUTO_INCREMENT,
    channel_name VARCHAR(50) UNIQUE NOT NULL,
    channel_display_name VARCHAR(100) NOT NULL,
    channel_type ENUM('push', 'email', 'sms', 'in_app', 'webhook', 'slack', 'telegram') NOT NULL,
    
    -- Channel Configuration
    is_enabled BOOLEAN DEFAULT TRUE,
    api_endpoint VARCHAR(255),
    api_key_encrypted VARCHAR(255),
    webhook_url VARCHAR(500),
    
    -- Rate Limiting
    rate_limit_per_minute INT DEFAULT 60,
    rate_limit_per_hour INT DEFAULT 1000,
    rate_limit_per_day INT DEFAULT 10000,
    
    -- Channel Settings
    supports_rich_content BOOLEAN DEFAULT FALSE,
    supports_attachments BOOLEAN DEFAULT FALSE,
    supports_scheduling BOOLEAN DEFAULT TRUE,
    max_content_length INT DEFAULT 1000,
    
    -- Priority & Cost
    priority_level TINYINT DEFAULT 5,
    cost_per_notification DECIMAL(8,4) DEFAULT 0,
    
    -- Channel Status
    last_health_check TIMESTAMP NULL,
    health_status ENUM('healthy', 'warning', 'error', 'disabled') DEFAULT 'healthy',
    error_message TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_channel_type (channel_type, is_enabled),
    INDEX idx_health_status (health_status, last_health_check)
) ENGINE=InnoDB;

-- ========================================
-- 2. NOTIFICATION TEMPLATES
-- ========================================

CREATE TABLE notification_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    template_category ENUM('system', 'environmental', 'social', 'commerce', 'achievement', 'reminder', 'alert') NOT NULL,
    
    -- Template Content (Multi-language support)
    subject_template VARCHAR(255) NOT NULL,
    content_template LONGTEXT NOT NULL,
    content_html_template LONGTEXT,
    
    -- Rich Content Support
    supports_variables BOOLEAN DEFAULT TRUE,
    variable_definitions JSON,
    
    -- Personalization
    personalization_level ENUM('none', 'basic', 'advanced', 'ai_powered') DEFAULT 'basic',
    dynamic_content_rules JSON,
    
    -- Multi-Channel Support
    email_template LONGTEXT,
    sms_template TEXT,
    push_template TEXT,
    in_app_template LONGTEXT,
    
    -- Template Settings
    is_active BOOLEAN DEFAULT TRUE,
    priority ENUM('low', 'normal', 'high', 'urgent', 'critical') DEFAULT 'normal',
    auto_expire_hours INT DEFAULT NULL,
    
    -- A/B Testing
    ab_test_group VARCHAR(50),
    ab_test_active BOOLEAN DEFAULT FALSE,
    conversion_tracking BOOLEAN DEFAULT FALSE,
    
    -- Compliance & Legal
    requires_consent BOOLEAN DEFAULT FALSE,
    gdpr_compliant BOOLEAN DEFAULT TRUE,
    retention_days INT DEFAULT 365,
    
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_category_active (template_category, is_active),
    INDEX idx_ab_testing (ab_test_group, ab_test_active),
    FULLTEXT(subject_template, content_template)
) ENGINE=InnoDB;

-- ========================================
-- 3. COMPREHENSIVE NOTIFICATIONS TABLE
-- ========================================

CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Recipient Information
    recipient_id INT NOT NULL,
    recipient_type ENUM('user', 'group', 'admin', 'system') DEFAULT 'user',
    
    -- Sender Information
    sender_id INT DEFAULT NULL,
    sender_type ENUM('user', 'system', 'automated', 'ai') DEFAULT 'system',
    
    -- Notification Content
    notification_type ENUM('system', 'environmental_alert', 'social_interaction', 'achievement', 'reminder', 'message', 'promotion', 'warning', 'urgent') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    content_html LONGTEXT,
    
    -- Rich Content Support
    image_url VARCHAR(500),
    thumbnail_url VARCHAR(500),
    attachments JSON,
    action_buttons JSON,
    rich_content JSON,
    
    -- Environmental Context
    environmental_context JSON,
    carbon_impact_info JSON,
    green_action_related BOOLEAN DEFAULT FALSE,
    
    -- Notification Behavior
    priority ENUM('low', 'normal', 'high', 'urgent', 'critical') DEFAULT 'normal',
    urgency_level TINYINT DEFAULT 3,
    
    -- Delivery Settings
    channels JSON DEFAULT '["in_app"]',
    scheduled_for TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    -- Status Tracking
    status ENUM('pending', 'scheduled', 'sent', 'delivered', 'read', 'failed', 'expired', 'cancelled') DEFAULT 'pending',
    delivery_attempts INT DEFAULT 0,
    max_delivery_attempts INT DEFAULT 3,
    
    -- User Interaction
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    is_dismissed BOOLEAN DEFAULT FALSE,
    dismissed_at TIMESTAMP NULL,
    
    -- Action Tracking
    action_taken VARCHAR(100),
    action_taken_at TIMESTAMP NULL,
    click_count INT DEFAULT 0,
    conversion_tracked BOOLEAN DEFAULT FALSE,
    
    -- Related Content
    related_content_type ENUM('article', 'event', 'product', 'achievement', 'message', 'petition', 'exchange') DEFAULT NULL,
    related_content_id INT DEFAULT NULL,
    deep_link_url VARCHAR(500),
    
    -- Analytics & Tracking
    campaign_id VARCHAR(100),
    tracking_pixel_url VARCHAR(500),
    utm_parameters JSON,
    
    -- Delivery Information
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    delivery_channel VARCHAR(50),
    delivery_details JSON,
    
    -- Error Handling
    error_message TEXT,
    retry_count INT DEFAULT 0,
    last_retry_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes for Performance
    INDEX idx_recipient_status (recipient_id, status, created_at DESC),
    INDEX idx_recipient_unread (recipient_id, is_read, created_at DESC),
    INDEX idx_notification_type (notification_type, priority, created_at DESC),
    INDEX idx_scheduled_delivery (scheduled_for, status),
    INDEX idx_expires (expires_at, status),
    INDEX idx_environmental_context (green_action_related, notification_type),
    INDEX idx_related_content (related_content_type, related_content_id),
    INDEX idx_campaign_tracking (campaign_id, created_at),
    FULLTEXT(title, content)
) ENGINE=InnoDB;

-- ========================================
-- 4. COMPREHENSIVE MESSAGES TABLE
-- ========================================

CREATE TABLE conversations (
    conversation_id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_type ENUM('direct', 'group', 'announcement', 'support', 'environmental_project', 'exchange_negotiation') DEFAULT 'direct',
    
    -- Conversation Metadata
    title VARCHAR(255),
    description TEXT,
    
    -- Participants
    creator_id INT NOT NULL,
    participant_count INT DEFAULT 0,
    max_participants INT DEFAULT 100,
    
    -- Environmental Context
    is_environmental_focused BOOLEAN DEFAULT FALSE,
    environmental_topic ENUM('waste_management', 'carbon_reduction', 'renewable_energy', 'conservation', 'sustainability', 'climate_change') DEFAULT NULL,
    related_environmental_id INT DEFAULT NULL,
    
    -- Conversation Settings
    is_public BOOLEAN DEFAULT FALSE,
    requires_approval BOOLEAN DEFAULT FALSE,
    auto_archive_days INT DEFAULT 90,
    
    -- Status
    status ENUM('active', 'archived', 'locked', 'deleted') DEFAULT 'active',
    
    -- Analytics
    total_messages INT DEFAULT 0,
    last_message_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (creator_id) REFERENCES users(user_id),
    INDEX idx_conversation_type (conversation_type, status),
    INDEX idx_environmental_topic (is_environmental_focused, environmental_topic),
    INDEX idx_public_conversations (is_public, status, last_activity_at DESC),
    INDEX idx_creator (creator_id, created_at DESC)
) ENGINE=InnoDB;

CREATE TABLE conversation_participants (
    participant_id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Participant Role
    role ENUM('member', 'moderator', 'admin', 'observer') DEFAULT 'member',
    
    -- Permissions
    can_send_messages BOOLEAN DEFAULT TRUE,
    can_add_participants BOOLEAN DEFAULT FALSE,
    can_remove_participants BOOLEAN DEFAULT FALSE,
    can_edit_conversation BOOLEAN DEFAULT FALSE,
    
    -- Status
    status ENUM('active', 'muted', 'blocked', 'left') DEFAULT 'active',
    
    -- Activity Tracking
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_read_id INT DEFAULT NULL,
    unread_count INT DEFAULT 0,
    
    -- Notifications
    notification_preferences JSON DEFAULT '{"mentions": true, "all_messages": false}',
    
    -- Environmental Engagement
    green_contribution_score INT DEFAULT 0,
    environmental_expertise_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_conversation_participant (conversation_id, user_id),
    INDEX idx_conversation_participants (conversation_id, status),
    INDEX idx_user_conversations (user_id, status, last_read_at),
    INDEX idx_unread_messages (user_id, unread_count DESC)
) ENGINE=InnoDB;

CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    
    -- Sender Information
    sender_id INT NOT NULL,
    sender_type ENUM('user', 'system', 'bot', 'ai_assistant') DEFAULT 'user',
    
    -- Message Content
    message_type ENUM('text', 'image', 'file', 'link', 'location', 'poll', 'environmental_data', 'carbon_calculation', 'waste_report') DEFAULT 'text',
    content LONGTEXT NOT NULL,
    content_format ENUM('plain', 'markdown', 'html') DEFAULT 'plain',
    
    -- Rich Content
    attachments JSON,
    media_urls JSON,
    metadata JSON,
    
    -- Environmental Content
    environmental_data JSON,
    carbon_impact_data JSON,
    green_action_suggestion JSON,
    
    -- Message Threading
    parent_message_id INT DEFAULT NULL,
    thread_level INT DEFAULT 0,
    thread_count INT DEFAULT 0,
    
    -- Message Status
    status ENUM('sent', 'delivered', 'read', 'edited', 'deleted') DEFAULT 'sent',
    is_edited BOOLEAN DEFAULT FALSE,
    edit_count INT DEFAULT 0,
    last_edited_at TIMESTAMP NULL,
    
    -- Reactions & Engagement
    reaction_count JSON DEFAULT '{}',
    like_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    
    -- Message Priority & Special Flags
    is_pinned BOOLEAN DEFAULT FALSE,
    is_announcement BOOLEAN DEFAULT FALSE,
    is_urgent BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT FALSE,
    
    -- AI & Environmental Features
    ai_generated BOOLEAN DEFAULT FALSE,
    environmental_impact_score INT DEFAULT 0,
    sustainability_tips_count INT DEFAULT 0,
    
    -- Delivery & Read Tracking
    delivered_to JSON DEFAULT '[]',
    read_by JSON DEFAULT '[]',
    delivery_status ENUM('pending', 'delivered', 'failed') DEFAULT 'pending',
    
    -- Moderation
    is_flagged BOOLEAN DEFAULT FALSE,
    moderation_status ENUM('approved', 'pending', 'rejected', 'auto_approved') DEFAULT 'auto_approved',
    flagged_reason VARCHAR(255),
    
    -- Analytics
    view_count INT DEFAULT 0,
    engagement_score DECIMAL(5,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES messages(message_id) ON DELETE SET NULL,
    
    -- Indexes for Performance
    INDEX idx_conversation_messages (conversation_id, created_at DESC),
    INDEX idx_sender_messages (sender_id, created_at DESC),
    INDEX idx_message_threading (parent_message_id, thread_level, created_at),
    INDEX idx_environmental_content (environmental_impact_score, ai_generated),
    INDEX idx_message_status (status, moderation_status),
    INDEX idx_pinned_announcements (is_pinned, is_announcement, created_at DESC),
    FULLTEXT(content)
) ENGINE=InnoDB;

-- ========================================
-- 5. REAL-TIME MESSAGING SUPPORT
-- ========================================

CREATE TABLE message_delivery_status (
    delivery_id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    recipient_id INT NOT NULL,
    
    -- Delivery Information
    delivery_method ENUM('websocket', 'push_notification', 'email', 'sms') NOT NULL,
    delivery_status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
    
    -- Timestamps
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    
    -- Delivery Details
    device_id VARCHAR(255),
    delivery_channel VARCHAR(50),
    error_message TEXT,
    retry_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_message_delivery (message_id, delivery_status),
    INDEX idx_recipient_delivery (recipient_id, delivery_status, created_at),
    INDEX idx_real_time_tracking (delivery_method, delivery_status, sent_at)
) ENGINE=InnoDB;

CREATE TABLE real_time_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Session Information
    socket_id VARCHAR(255) UNIQUE NOT NULL,
    connection_type ENUM('websocket', 'sse', 'polling') DEFAULT 'websocket',
    
    -- Device & Location
    device_info JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- Session Status
    status ENUM('connected', 'disconnected', 'idle', 'away') DEFAULT 'connected',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Environmental Context
    current_environmental_activity VARCHAR(100),
    active_green_challenges JSON,
    
    -- Connection Details
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    disconnected_at TIMESTAMP NULL,
    session_duration INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_user_sessions (user_id, status),
    INDEX idx_socket_lookup (socket_id),
    INDEX idx_active_sessions (status, last_activity),
    INDEX idx_environmental_activity (current_environmental_activity, status)
) ENGINE=InnoDB;

-- ========================================
-- 6. NOTIFICATION PREFERENCES & SETTINGS
-- ========================================

CREATE TABLE user_notification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Channel Preferences
    email_enabled BOOLEAN DEFAULT TRUE,
    push_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    in_app_enabled BOOLEAN DEFAULT TRUE,
    
    -- Notification Type Preferences
    system_notifications BOOLEAN DEFAULT TRUE,
    environmental_alerts BOOLEAN DEFAULT TRUE,
    social_interactions BOOLEAN DEFAULT TRUE,
    achievements_notifications BOOLEAN DEFAULT TRUE,
    reminder_notifications BOOLEAN DEFAULT TRUE,
    promotional_notifications BOOLEAN DEFAULT FALSE,
    
    -- Environmental Specific
    carbon_milestone_alerts BOOLEAN DEFAULT TRUE,
    waste_classification_reminders BOOLEAN DEFAULT TRUE,
    green_challenge_notifications BOOLEAN DEFAULT TRUE,
    environmental_news_digest BOOLEAN DEFAULT TRUE,
    sustainability_tips BOOLEAN DEFAULT TRUE,
    
    -- Frequency Settings
    daily_digest_enabled BOOLEAN DEFAULT TRUE,
    weekly_summary_enabled BOOLEAN DEFAULT TRUE,
    instant_notifications BOOLEAN DEFAULT TRUE,
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    
    -- Delivery Preferences
    digest_frequency ENUM('daily', 'weekly', 'bi_weekly', 'monthly') DEFAULT 'weekly',
    preferred_language VARCHAR(10) DEFAULT 'vi',
    timezone VARCHAR(50) DEFAULT 'Asia/Ho_Chi_Minh',
    
    -- Advanced Settings
    ai_personalization_enabled BOOLEAN DEFAULT TRUE,
    location_based_alerts BOOLEAN DEFAULT TRUE,
    emergency_override BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id),
    INDEX idx_digest_settings (daily_digest_enabled, weekly_summary_enabled),
    INDEX idx_environmental_prefs (environmental_alerts, green_challenge_notifications)
) ENGINE=InnoDB;

-- ========================================
-- 7. MESSAGE REACTIONS & INTERACTIONS
-- ========================================

CREATE TABLE message_reactions (
    reaction_id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Reaction Information
    reaction_type ENUM('like', 'love', 'celebrate', 'support', 'eco_friendly', 'helpful', 'inspiring', 'thumbs_up', 'thumbs_down') NOT NULL,
    reaction_emoji VARCHAR(10),
    
    -- Environmental Context
    environmental_appreciation BOOLEAN DEFAULT FALSE,
    green_impact_recognition BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_message_reaction (message_id, user_id, reaction_type),
    INDEX idx_message_reactions (message_id, reaction_type),
    INDEX idx_environmental_reactions (environmental_appreciation, green_impact_recognition)
) ENGINE=InnoDB;

-- ========================================
-- 8. NOTIFICATION ANALYTICS
-- ========================================

CREATE TABLE notification_analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NOT NULL,
    
    -- Delivery Analytics
    delivery_success_rate DECIMAL(5,2) DEFAULT 0,
    average_delivery_time_seconds INT DEFAULT 0,
    channel_performance JSON,
    
    -- Engagement Analytics
    open_rate DECIMAL(5,2) DEFAULT 0,
    click_through_rate DECIMAL(5,2) DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    engagement_score DECIMAL(5,2) DEFAULT 0,
    
    -- Environmental Impact
    environmental_action_triggered BOOLEAN DEFAULT FALSE,
    carbon_impact_awareness_score INT DEFAULT 0,
    green_action_completion_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Device & Location Analytics
    device_breakdown JSON,
    location_breakdown JSON,
    time_zone_performance JSON,
    
    -- A/B Testing Results
    ab_test_variant VARCHAR(50),
    variant_performance JSON,
    statistical_significance BOOLEAN DEFAULT FALSE,
    
    -- User Feedback
    user_satisfaction_score DECIMAL(3,2) DEFAULT 0,
    feedback_count INT DEFAULT 0,
    positive_feedback_ratio DECIMAL(3,2) DEFAULT 0,
    
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    INDEX idx_notification_performance (notification_id, calculated_at),
    INDEX idx_environmental_impact (environmental_action_triggered, carbon_impact_awareness_score)
) ENGINE=InnoDB;

-- ========================================
-- 9. INSERT SAMPLE DATA
-- ========================================

-- Sample Notification Channels
INSERT INTO notification_channels (channel_name, channel_display_name, channel_type, supports_rich_content, supports_attachments) VALUES
('in_app', 'In-App Notifications', 'in_app', TRUE, TRUE),
('email', 'Email Notifications', 'email', TRUE, TRUE),
('push', 'Push Notifications', 'push', FALSE, FALSE),
('sms', 'SMS Messages', 'sms', FALSE, FALSE),
('webhook', 'Webhook Integration', 'webhook', TRUE, TRUE);

-- Sample Notification Templates
INSERT INTO notification_templates (template_name, template_category, subject_template, content_template, created_by) VALUES
('carbon_milestone', 'environmental', 'Chúc mừng! Bạn đã tiết kiệm {carbon_amount}kg CO2', 'Tuyệt vời! Bạn đã đạt mốc tiết kiệm {carbon_amount}kg CO2. Hành động xanh của bạn đang tạo ra tác động tích cực đến môi trường!', 1),
('waste_classification_achievement', 'achievement', 'Thành tích phân loại rác mới!', 'Bạn đã phân loại chính xác {item_count} loại rác và nhận được {points} điểm xanh!', 1),
('social_interaction', 'social', 'Có người tương tác với bài viết của bạn', '{user_name} đã {action} bài viết "{article_title}" của bạn.', 1),
('system_maintenance', 'system', 'Thông báo bảo trì hệ thống', 'Hệ thống sẽ được bảo trì từ {start_time} đến {end_time}. Chúng tôi xin lỗi vì sự bất tiện này.', 1),
('environmental_alert', 'environmental', 'Cảnh báo chất lượng không khí', 'Chất lượng không khí tại {location} đang ở mức {level}. Khuyến cáo: {recommendation}', 1);

-- Sample Conversations
INSERT INTO conversations (conversation_type, title, creator_id, is_environmental_focused, environmental_topic) VALUES
('group', 'Dự án tái chế chai nhựa', 1, TRUE, 'waste_management'),
('direct', NULL, 1, FALSE, NULL),
('environmental_project', 'Chiến dịch giảm carbon cộng đồng', 1, TRUE, 'carbon_reduction');

-- Sample Conversation Participants
INSERT INTO conversation_participants (conversation_id, user_id, role) VALUES
(1, 1, 'admin'),
(1, 2, 'member'),
(2, 1, 'member'),
(2, 2, 'member'),
(3, 1, 'moderator');

-- Sample Messages
INSERT INTO messages (conversation_id, sender_id, content, message_type, environmental_impact_score) VALUES
(1, 1, 'Chào mọi người! Hôm nay chúng ta sẽ thảo luận về dự án tái chế chai nhựa trong cộng đồng.', 'text', 8),
(1, 2, 'Tôi rất hào hứng tham gia! Có thể chia sẻ thêm về quy trình tái chế không?', 'text', 5),
(2, 1, 'Xin chào! Tôi muốn trao đổi về việc phân loại rác tại nhà.', 'text', 6),
(3, 1, 'Chúng ta cần lập kế hoạch chi tiết để giảm 50% lượng carbon phát thải trong tháng tới.', 'text', 9);

-- Sample Notifications
INSERT INTO notifications (recipient_id, notification_type, title, content, priority, green_action_related, related_content_type) VALUES
(1, 'achievement', 'Chúc mừng thành tích mới!', 'Bạn đã đạt được thành tích "Nhà phân loại rác chuyên nghiệp" với 100 lần phân loại chính xác!', 'normal', TRUE, 'achievement'),
(2, 'environmental_alert', 'Cảnh báo chất lượng không khí', 'Chất lượng không khí tại Hà Nội đang ở mức không tốt. Khuyến cáo hạn chế ra ngoài và sử dụng khẩu trang.', 'high', TRUE, NULL),
(1, 'social_interaction', 'Tương tác mới', 'Nguyễn Văn B đã thích bài viết "Hướng dẫn tái chế chai nhựa" của bạn.', 'normal', FALSE, 'article');

-- Sample User Notification Preferences
INSERT INTO user_notification_preferences (user_id, environmental_alerts, green_challenge_notifications, carbon_milestone_alerts) VALUES
(1, TRUE, TRUE, TRUE),
(2, TRUE, FALSE, TRUE);

-- Sample Message Reactions
INSERT INTO message_reactions (message_id, user_id, reaction_type, environmental_appreciation) VALUES
(1, 2, 'eco_friendly', TRUE),
(2, 1, 'helpful', TRUE),
(3, 2, 'support', FALSE),
(4, 1, 'inspiring', TRUE);

SELECT 'Phase 18: Notifications & Messaging System created successfully!' as Status;
