-- Phase 39: AI Integration & Waste Classification Database Tables
-- Environmental Data Dashboard Plugin

-- Table for storing AI classification results
CREATE TABLE IF NOT EXISTS `wp_env_ai_classifications` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `image_hash` varchar(64) DEFAULT NULL,
    `category` varchar(100) NOT NULL,
    `subcategory` varchar(100) DEFAULT NULL,
    `confidence_score` decimal(3,2) NOT NULL DEFAULT 0.00,
    `ai_response` longtext DEFAULT NULL,
    `disposal_recommendations` longtext DEFAULT NULL,
    `processing_time` decimal(5,3) DEFAULT NULL,
    `api_provider` varchar(50) DEFAULT 'openai',
    `model_version` varchar(50) DEFAULT NULL,
    `status` enum('pending','completed','failed','reviewing') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `category` (`category`),
    KEY `confidence_score` (`confidence_score`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`),
    UNIQUE KEY `user_image_hash` (`user_id`, `image_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing user feedback on classifications
CREATE TABLE IF NOT EXISTS `wp_env_classification_feedback` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `classification_id` bigint(20) unsigned NOT NULL,
    `user_id` bigint(20) unsigned NOT NULL,
    `rating` tinyint(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `feedback_type` enum('helpful','incorrect','incomplete','excellent') DEFAULT NULL,
    `comments` text DEFAULT NULL,
    `is_correct_classification` boolean DEFAULT NULL,
    `suggested_category` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `classification_id` (`classification_id`),
    KEY `user_id` (`user_id`),
    KEY `rating` (`rating`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `fk_feedback_classification` FOREIGN KEY (`classification_id`) REFERENCES `wp_env_ai_classifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for gamification points and levels
CREATE TABLE IF NOT EXISTS `wp_env_user_gamification` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `total_points` bigint(20) unsigned NOT NULL DEFAULT 0,
    `level` int(11) unsigned NOT NULL DEFAULT 1,
    `level_progress` decimal(3,2) NOT NULL DEFAULT 0.00,
    `classifications_count` int(11) unsigned NOT NULL DEFAULT 0,
    `accuracy_rate` decimal(3,2) NOT NULL DEFAULT 0.00,
    `streak_days` int(11) unsigned NOT NULL DEFAULT 0,
    `longest_streak` int(11) unsigned NOT NULL DEFAULT 0,
    `last_activity_date` date DEFAULT NULL,
    `weekly_classifications` int(11) unsigned NOT NULL DEFAULT 0,
    `monthly_classifications` int(11) unsigned NOT NULL DEFAULT 0,
    `total_feedback_given` int(11) unsigned NOT NULL DEFAULT 0,
    `helpful_feedback_count` int(11) unsigned NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `total_points` (`total_points`),
    KEY `level` (`level`),
    KEY `streak_days` (`streak_days`),
    KEY `last_activity_date` (`last_activity_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for achievements and badges
CREATE TABLE IF NOT EXISTS `wp_env_achievements` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `achievement_key` varchar(100) NOT NULL,
    `name` varchar(200) NOT NULL,
    `description` text NOT NULL,
    `icon` varchar(100) DEFAULT NULL,
    `category` enum('milestone','diversity','precision','consistency','timing','community','special') NOT NULL,
    `points_reward` int(11) unsigned NOT NULL DEFAULT 0,
    `requirement_type` enum('count','percentage','streak','score','time') NOT NULL,
    `requirement_value` int(11) unsigned NOT NULL,
    `requirement_data` json DEFAULT NULL,
    `is_active` boolean NOT NULL DEFAULT TRUE,
    `sort_order` int(11) unsigned NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `achievement_key` (`achievement_key`),
    KEY `category` (`category`),
    KEY `is_active` (`is_active`),
    KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for user achievements
CREATE TABLE IF NOT EXISTS `wp_env_user_achievements` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `achievement_id` bigint(20) unsigned NOT NULL,
    `progress` decimal(5,2) NOT NULL DEFAULT 0.00,
    `is_completed` boolean NOT NULL DEFAULT FALSE,
    `completed_at` timestamp NULL DEFAULT NULL,
    `notified` boolean NOT NULL DEFAULT FALSE,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_achievement` (`user_id`, `achievement_id`),
    KEY `user_id` (`user_id`),
    KEY `achievement_id` (`achievement_id`),
    KEY `is_completed` (`is_completed`),
    KEY `completed_at` (`completed_at`),
    CONSTRAINT `fk_user_achievements_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `wp_env_achievements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for daily/weekly challenges
CREATE TABLE IF NOT EXISTS `wp_env_challenges` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `challenge_key` varchar(100) NOT NULL,
    `name` varchar(200) NOT NULL,
    `description` text NOT NULL,
    `type` enum('daily','weekly','monthly','special') NOT NULL,
    `category` varchar(100) DEFAULT NULL,
    `target_value` int(11) unsigned NOT NULL,
    `points_reward` int(11) unsigned NOT NULL DEFAULT 0,
    `bonus_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `is_active` boolean NOT NULL DEFAULT TRUE,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `challenge_key_date` (`challenge_key`, `start_date`),
    KEY `type` (`type`),
    KEY `is_active` (`is_active`),
    KEY `start_date` (`start_date`),
    KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for user challenge progress
CREATE TABLE IF NOT EXISTS `wp_env_user_challenges` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `challenge_id` bigint(20) unsigned NOT NULL,
    `progress` int(11) unsigned NOT NULL DEFAULT 0,
    `is_completed` boolean NOT NULL DEFAULT FALSE,
    `completed_at` timestamp NULL DEFAULT NULL,
    `points_earned` int(11) unsigned NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_challenge` (`user_id`, `challenge_id`),
    KEY `user_id` (`user_id`),
    KEY `challenge_id` (`challenge_id`),
    KEY `is_completed` (`is_completed`),
    KEY `completed_at` (`completed_at`),
    CONSTRAINT `fk_user_challenges_challenge` FOREIGN KEY (`challenge_id`) REFERENCES `wp_env_challenges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for AI service configuration and monitoring
CREATE TABLE IF NOT EXISTS `wp_env_ai_service_config` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `service_name` varchar(100) NOT NULL,
    `api_endpoint` varchar(500) NOT NULL,
    `api_key_hash` varchar(255) DEFAULT NULL,
    `model_name` varchar(100) DEFAULT NULL,
    `max_requests_per_minute` int(11) unsigned NOT NULL DEFAULT 60,
    `max_requests_per_day` int(11) unsigned NOT NULL DEFAULT 1000,
    `timeout_seconds` int(11) unsigned NOT NULL DEFAULT 30,
    `is_active` boolean NOT NULL DEFAULT TRUE,
    `last_used_at` timestamp NULL DEFAULT NULL,
    `total_requests` bigint(20) unsigned NOT NULL DEFAULT 0,
    `successful_requests` bigint(20) unsigned NOT NULL DEFAULT 0,
    `failed_requests` bigint(20) unsigned NOT NULL DEFAULT 0,
    `average_response_time` decimal(5,3) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `service_name` (`service_name`),
    KEY `is_active` (`is_active`),
    KEY `last_used_at` (`last_used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for API usage tracking and rate limiting
CREATE TABLE IF NOT EXISTS `wp_env_ai_usage_log` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `service_name` varchar(100) NOT NULL,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `request_type` varchar(50) NOT NULL,
    `response_time` decimal(5,3) DEFAULT NULL,
    `tokens_used` int(11) unsigned DEFAULT NULL,
    `cost_estimate` decimal(8,4) DEFAULT NULL,
    `success` boolean NOT NULL DEFAULT FALSE,
    `error_message` text DEFAULT NULL,
    `request_date` date NOT NULL,
    `request_hour` tinyint(2) unsigned NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `service_name` (`service_name`),
    KEY `user_id` (`user_id`),
    KEY `request_date` (`request_date`),
    KEY `request_hour` (`request_hour`),
    KEY `success` (`success`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default achievements
INSERT INTO `wp_env_achievements` (`achievement_key`, `name`, `description`, `icon`, `category`, `points_reward`, `requirement_type`, `requirement_value`, `requirement_data`) VALUES
-- Milestone Achievements
('first_classification', 'First Step', 'Complete your first waste classification', '🏁', 'milestone', 50, 'count', 1, NULL),
('classifications_10', 'Getting Started', 'Complete 10 waste classifications', '📸', 'milestone', 100, 'count', 10, NULL),
('classifications_50', 'Waste Detective', 'Complete 50 waste classifications', '🔍', 'milestone', 300, 'count', 50, NULL),
('classifications_100', 'Classification Expert', 'Complete 100 waste classifications', '🎯', 'milestone', 500, 'count', 100, NULL),
('classifications_500', 'Eco Champion', 'Complete 500 waste classifications', '🏆', 'milestone', 1000, 'count', 500, NULL),

-- Diversity Achievements
('category_explorer', 'Category Explorer', 'Classify items from 5 different waste categories', '🌍', 'diversity', 200, 'count', 5, '{"type": "unique_categories"}'),
('recycling_expert', 'Recycling Expert', 'Classify 25 recyclable items correctly', '♻️', 'diversity', 250, 'count', 25, '{"category": "recyclable"}'),
('hazardous_handler', 'Hazardous Handler', 'Classify 10 hazardous waste items correctly', '⚠️', 'diversity', 300, 'count', 10, '{"category": "hazardous"}'),
('organic_specialist', 'Organic Specialist', 'Classify 30 organic waste items correctly', '🌱', 'diversity', 200, 'count', 30, '{"category": "organic"}'),

-- Precision Achievements
('accuracy_80', 'Sharp Eye', 'Maintain 80% accuracy over 20 classifications', '👁️', 'precision', 300, 'percentage', 80, '{"min_classifications": 20}'),
('accuracy_90', 'Precision Master', 'Maintain 90% accuracy over 50 classifications', '🎯', 'precision', 500, 'percentage', 90, '{"min_classifications": 50}'),
('high_confidence', 'Confidence Booster', 'Get 20 classifications with >90% AI confidence', '💪', 'precision', 250, 'count', 20, '{"min_confidence": 0.9}'),

-- Consistency Achievements
('daily_classifier', 'Daily Classifier', 'Classify at least 1 item for 7 consecutive days', '📅', 'consistency', 400, 'streak', 7, '{"type": "daily"}'),
('weekly_warrior', 'Weekly Warrior', 'Classify at least 5 items per week for 4 weeks', '💪', 'consistency', 600, 'streak', 4, '{"type": "weekly", "min_per_period": 5}'),
('streak_30', 'Month Long Champion', 'Maintain a 30-day classification streak', '🔥', 'consistency', 800, 'streak', 30, '{"type": "daily"}'),

-- Timing Achievements
('early_bird', 'Early Bird', 'Complete 10 classifications before 9 AM', '🌅', 'timing', 150, 'count', 10, '{"time_before": "09:00"}'),
('night_owl', 'Night Owl', 'Complete 10 classifications after 9 PM', '🦉', 'timing', 150, 'count', 10, '{"time_after": "21:00"}'),
('speed_demon', 'Speed Demon', 'Complete 5 classifications in under 30 seconds each', '⚡', 'timing', 200, 'count', 5, '{"max_time": 30}'),

-- Community Achievements
('feedback_giver', 'Helpful Community Member', 'Provide feedback on 20 classifications', '🤝', 'community', 200, 'count', 20, '{"type": "feedback"}'),
('quality_feedback', 'Quality Contributor', 'Have 15 of your feedback marked as helpful', '⭐', 'community', 300, 'count', 15, '{"type": "helpful_feedback"}'),
('teaching_moment', 'Educator', 'Help improve 10 incorrect classifications through feedback', '📚', 'community', 400, 'count', 10, '{"type": "correction_feedback"}'),

-- Special Achievements
('perfectionist', 'Perfectionist', 'Get 10 consecutive classifications correct', '💎', 'special', 500, 'streak', 10, '{"type": "correct_streak"}'),
('comeback_kid', 'Comeback Kid', 'Improve accuracy by 20% over 30 classifications', '🚀', 'special', 350, 'percentage', 20, '{"type": "improvement", "window": 30}'),
('waste_wizard', 'Waste Wizard', 'Achieve Level 10 in the gamification system', '🧙‍♂️', 'special', 1000, 'count', 10, '{"type": "level"}');

-- Insert default challenges (these would be regenerated daily/weekly by the system)
INSERT INTO `wp_env_challenges` (`challenge_key`, `name`, `description`, `type`, `target_value`, `points_reward`, `start_date`, `end_date`) VALUES
('daily_classifier_today', 'Daily Classification Goal', 'Classify 3 waste items today', 'daily', 3, 50, CURDATE(), CURDATE()),
('weekly_diversity', 'Weekly Diversity Challenge', 'Classify items from 4 different categories this week', 'weekly', 4, 200, DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY));

-- Insert default AI service configuration
INSERT INTO `wp_env_ai_service_config` (`service_name`, `api_endpoint`, `model_name`, `max_requests_per_minute`, `max_requests_per_day`) VALUES
('openai_gpt4_vision', 'https://api.openai.com/v1/chat/completions', 'gpt-4-vision-preview', 20, 500),
('openai_gpt4', 'https://api.openai.com/v1/chat/completions', 'gpt-4', 60, 1000);

-- Create indexes for better performance
CREATE INDEX idx_classifications_user_date ON wp_env_ai_classifications(user_id, created_at);
CREATE INDEX idx_classifications_category_confidence ON wp_env_ai_classifications(category, confidence_score);
CREATE INDEX idx_gamification_leaderboard ON wp_env_user_gamification(total_points DESC, level DESC);
CREATE INDEX idx_achievements_category_active ON wp_env_achievements(category, is_active);
CREATE INDEX idx_user_achievements_progress ON wp_env_user_achievements(user_id, is_completed, progress);
CREATE INDEX idx_challenges_active_date ON wp_env_challenges(is_active, start_date, end_date);
CREATE INDEX idx_usage_log_date_service ON wp_env_ai_usage_log(request_date, service_name);

-- Create views for common queries
CREATE OR REPLACE VIEW vw_user_leaderboard AS
SELECT 
    u.ID as user_id,
    u.display_name,
    u.user_email,
    g.total_points,
    g.level,
    g.classifications_count,
    g.accuracy_rate,
    g.streak_days,
    g.longest_streak,
    RANK() OVER (ORDER BY g.total_points DESC) as rank_by_points,
    RANK() OVER (ORDER BY g.level DESC, g.level_progress DESC) as rank_by_level
FROM wp_users u
JOIN wp_env_user_gamification g ON u.ID = g.user_id
WHERE g.total_points > 0
ORDER BY g.total_points DESC;

CREATE OR REPLACE VIEW vw_classification_stats AS
SELECT 
    DATE(created_at) as date,
    category,
    COUNT(*) as total_classifications,
    AVG(confidence_score) as avg_confidence,
    COUNT(DISTINCT user_id) as unique_users,
    SUM(CASE WHEN confidence_score >= 0.8 then 1 else 0 end) as high_confidence_count
FROM wp_env_ai_classifications 
WHERE status = 'completed'
GROUP BY DATE(created_at), category;

-- Add triggers for automatic gamification updates
DELIMITER $$

CREATE TRIGGER tr_classification_gamification_update
AFTER INSERT ON wp_env_ai_classifications
FOR EACH ROW
BEGIN
    -- Update user gamification stats
    INSERT INTO wp_env_user_gamification (user_id, classifications_count, last_activity_date)
    VALUES (NEW.user_id, 1, CURDATE())
    ON DUPLICATE KEY UPDATE
        classifications_count = classifications_count + 1,
        last_activity_date = CURDATE(),
        updated_at = CURRENT_TIMESTAMP;
        
    -- Update weekly and monthly counters
    UPDATE wp_env_user_gamification 
    SET 
        weekly_classifications = weekly_classifications + 1,
        monthly_classifications = monthly_classifications + 1
    WHERE user_id = NEW.user_id;
END$$

CREATE TRIGGER tr_feedback_gamification_update
AFTER INSERT ON wp_env_classification_feedback
FOR EACH ROW
BEGIN
    -- Update feedback count
    INSERT INTO wp_env_user_gamification (user_id, total_feedback_given)
    VALUES (NEW.user_id, 1)
    ON DUPLICATE KEY UPDATE
        total_feedback_given = total_feedback_given + 1,
        updated_at = CURRENT_TIMESTAMP;
END$$

DELIMITER ;
