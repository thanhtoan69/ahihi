# Database Structure Documentation

Generated on: 2025-06-08 04:50:13
Database: `environmental_platform`

## Table of Contents

- [Table: achievement_categories](#table-achievement-categories)
- [Table: achievement_processing_queue](#table-achievement-processing-queue)
- [Table: achievements_enhanced](#table-achievements-enhanced)
- [Table: ai_experiments](#table-ai-experiments)
- [Table: ai_model_monitoring](#table-ai-model-monitoring)
- [Table: ai_models](#table-ai-models)
- [Table: ai_predictions](#table-ai-predictions)
- [Table: ai_training_queue](#table-ai-training-queue)
- [Table: analytics_data_validation_final](#table-analytics-data-validation-final)
- [Table: article_comments](#table-article-comments)
- [Table: article_interactions](#table-article-interactions)
- [Table: article_tags](#table-article-tags)
- [Table: articles](#table-articles)
- [Table: badges_system](#table-badges-system)
- [Table: cache_invalidation_log](#table-cache-invalidation-log)
- [Table: carbon_footprints](#table-carbon-footprints)
- [Table: carbon_reduction_goals](#table-carbon-reduction-goals)
- [Table: categories](#table-categories)
- [Table: challenge_participation](#table-challenge-participation)
- [Table: community_moderators](#table-community-moderators)
- [Table: content_performance_final](#table-content-performance-final)
- [Table: content_shares](#table-content-shares)
- [Table: content_tags](#table-content-tags)
- [Table: conversation_participants](#table-conversation-participants)
- [Table: conversations](#table-conversations)
- [Table: daily_analytics_summary](#table-daily-analytics-summary)
- [Table: dashboard_real_time_metrics_final](#table-dashboard-real-time-metrics-final)
- [Table: dashboard_widgets](#table-dashboard-widgets)
- [Table: donation_campaigns](#table-donation-campaigns)
- [Table: donation_impact_reports](#table-donation-impact-reports)
- [Table: donation_organizations](#table-donation-organizations)
- [Table: donation_subscriptions](#table-donation-subscriptions)
- [Table: donations](#table-donations)
- [Table: environmental_data](#table-environmental-data)
- [Table: environmental_data_sources](#table-environmental-data-sources)
- [Table: environmental_impact_summary](#table-environmental-impact-summary)
- [Table: environmental_impact_summary_final](#table-environmental-impact-summary-final)
- [Table: event_registrations](#table-event-registrations)
- [Table: events](#table-events)
- [Table: exchange_categories](#table-exchange-categories)
- [Table: exchange_favorites](#table-exchange-favorites)
- [Table: exchange_matches](#table-exchange-matches)
- [Table: exchange_posts](#table-exchange-posts)
- [Table: exchange_requests](#table-exchange-requests)
- [Table: exchange_saved_searches](#table-exchange-saved-searches)
- [Table: forum_posts](#table-forum-posts)
- [Table: forum_topics](#table-forum-topics)
- [Table: forums](#table-forums)
- [Table: index_usage_analysis](#table-index-usage-analysis)
- [Table: leaderboard_entries_enhanced](#table-leaderboard-entries-enhanced)
- [Table: leaderboards_enhanced](#table-leaderboards-enhanced)
- [Table: marketplace_insights_final](#table-marketplace-insights-final)
- [Table: message_delivery_status](#table-message-delivery-status)
- [Table: message_reactions](#table-message-reactions)
- [Table: messages](#table-messages)
- [Table: moderation_analytics](#table-moderation-analytics)
- [Table: moderation_appeals](#table-moderation-appeals)
- [Table: moderation_logs](#table-moderation-logs)
- [Table: moderation_rules](#table-moderation-rules)
- [Table: monthly_analytics_summary](#table-monthly-analytics-summary)
- [Table: notification_analytics](#table-notification-analytics)
- [Table: notification_channels](#table-notification-channels)
- [Table: notification_templates](#table-notification-templates)
- [Table: notifications](#table-notifications)
- [Table: order_items](#table-order-items)
- [Table: orders](#table-orders)
- [Table: password_resets](#table-password-resets)
- [Table: petition_signatures](#table-petition-signatures)
- [Table: petitions](#table-petitions)
- [Table: platform_metrics](#table-platform-metrics)
- [Table: product_brands](#table-product-brands)
- [Table: product_reviews](#table-product-reviews)
- [Table: products](#table-products)
- [Table: quiz_categories](#table-quiz-categories)
- [Table: quiz_questions](#table-quiz-questions)
- [Table: quiz_responses](#table-quiz-responses)
- [Table: quiz_sessions](#table-quiz-sessions)
- [Table: real_time_sessions](#table-real-time-sessions)
- [Table: recycling_locations](#table-recycling-locations)
- [Table: report_generation_history](#table-report-generation-history)
- [Table: report_templates](#table-report-templates)
- [Table: reports](#table-reports)
- [Table: seasonal_challenges](#table-seasonal-challenges)
- [Table: sellers](#table-sellers)
- [Table: shopping_carts](#table-shopping-carts)
- [Table: slow_trigger_archive](#table-slow-trigger-archive)
- [Table: social_platforms](#table-social-platforms)
- [Table: test_simple](#table-test-simple)
- [Table: trigger_performance_logs](#table-trigger-performance-logs)
- [Table: trigger_performance_summary](#table-trigger-performance-summary)
- [Table: user_achievements_enhanced](#table-user-achievements-enhanced)
- [Table: user_activities_archive](#table-user-activities-archive)
- [Table: user_activities_comprehensive](#table-user-activities-comprehensive)
- [Table: user_activities_test](#table-user-activities-test)
- [Table: user_activity_patterns](#table-user-activity-patterns)
- [Table: user_analytics](#table-user-analytics)
- [Table: user_analytics_test](#table-user-analytics-test)
- [Table: user_badges_enhanced](#table-user-badges-enhanced)
- [Table: user_engagement_scores](#table-user-engagement-scores)
- [Table: user_engagement_summary](#table-user-engagement-summary)
- [Table: user_engagement_summary_final](#table-user-engagement-summary-final)
- [Table: user_habit_tracking](#table-user-habit-tracking)
- [Table: user_notification_preferences](#table-user-notification-preferences)
- [Table: user_permissions](#table-user-permissions)
- [Table: user_preferences](#table-user-preferences)
- [Table: user_role_assignments](#table-user-role-assignments)
- [Table: user_role_permissions](#table-user-role-permissions)
- [Table: user_roles](#table-user-roles)
- [Table: user_sessions](#table-user-sessions)
- [Table: user_streaks_advanced](#table-user-streaks-advanced)
- [Table: user_streaks_gamification](#table-user-streaks-gamification)
- [Table: user_verification_codes](#table-user-verification-codes)
- [Table: users](#table-users)
- [Table: voucher_campaigns](#table-voucher-campaigns)
- [Table: voucher_usage](#table-voucher-usage)
- [Table: vouchers](#table-vouchers)
- [Table: waste_categories](#table-waste-categories)
- [Table: waste_classification_results](#table-waste-classification-results)
- [Table: waste_classification_sessions](#table-waste-classification-sessions)
- [Table: waste_items](#table-waste-items)
- [Table: wp_actionscheduler_actions](#table-wp-actionscheduler-actions)
- [Table: wp_actionscheduler_claims](#table-wp-actionscheduler-claims)
- [Table: wp_actionscheduler_groups](#table-wp-actionscheduler-groups)
- [Table: wp_actionscheduler_logs](#table-wp-actionscheduler-logs)
- [Table: wp_commentmeta](#table-wp-commentmeta)
- [Table: wp_comments](#table-wp-comments)
- [Table: wp_ep_event_analytics](#table-wp-ep-event-analytics)
- [Table: wp_ep_event_checkins](#table-wp-ep-event-checkins)
- [Table: wp_ep_event_registrations](#table-wp-ep-event-registrations)
- [Table: wp_links](#table-wp-links)
- [Table: wp_options](#table-wp-options)
- [Table: wp_postmeta](#table-wp-postmeta)
- [Table: wp_posts](#table-wp-posts)
- [Table: wp_security_logs](#table-wp-security-logs)
- [Table: wp_term_relationships](#table-wp-term-relationships)
- [Table: wp_term_taxonomy](#table-wp-term-taxonomy)
- [Table: wp_termmeta](#table-wp-termmeta)
- [Table: wp_terms](#table-wp-terms)
- [Table: wp_usermeta](#table-wp-usermeta)
- [Table: wp_users](#table-wp-users)
- [Table: wp_wc_admin_note_actions](#table-wp-wc-admin-note-actions)
- [Table: wp_wc_admin_notes](#table-wp-wc-admin-notes)
- [Table: wp_wc_category_lookup](#table-wp-wc-category-lookup)
- [Table: wp_wc_customer_lookup](#table-wp-wc-customer-lookup)
- [Table: wp_wc_download_log](#table-wp-wc-download-log)
- [Table: wp_wc_order_addresses](#table-wp-wc-order-addresses)
- [Table: wp_wc_order_coupon_lookup](#table-wp-wc-order-coupon-lookup)
- [Table: wp_wc_order_operational_data](#table-wp-wc-order-operational-data)
- [Table: wp_wc_order_product_lookup](#table-wp-wc-order-product-lookup)
- [Table: wp_wc_order_stats](#table-wp-wc-order-stats)
- [Table: wp_wc_order_tax_lookup](#table-wp-wc-order-tax-lookup)
- [Table: wp_wc_orders](#table-wp-wc-orders)
- [Table: wp_wc_orders_meta](#table-wp-wc-orders-meta)
- [Table: wp_wc_product_attributes_lookup](#table-wp-wc-product-attributes-lookup)
- [Table: wp_wc_product_download_directories](#table-wp-wc-product-download-directories)
- [Table: wp_wc_product_meta_lookup](#table-wp-wc-product-meta-lookup)
- [Table: wp_wc_rate_limits](#table-wp-wc-rate-limits)
- [Table: wp_wc_reserved_stock](#table-wp-wc-reserved-stock)
- [Table: wp_wc_tax_rate_classes](#table-wp-wc-tax-rate-classes)
- [Table: wp_wc_webhooks](#table-wp-wc-webhooks)
- [Table: wp_woocommerce_api_keys](#table-wp-woocommerce-api-keys)
- [Table: wp_woocommerce_attribute_taxonomies](#table-wp-woocommerce-attribute-taxonomies)
- [Table: wp_woocommerce_downloadable_product_permissions](#table-wp-woocommerce-downloadable-product-permissions)
- [Table: wp_woocommerce_log](#table-wp-woocommerce-log)
- [Table: wp_woocommerce_order_itemmeta](#table-wp-woocommerce-order-itemmeta)
- [Table: wp_woocommerce_order_items](#table-wp-woocommerce-order-items)
- [Table: wp_woocommerce_payment_tokenmeta](#table-wp-woocommerce-payment-tokenmeta)
- [Table: wp_woocommerce_payment_tokens](#table-wp-woocommerce-payment-tokens)
- [Table: wp_woocommerce_sessions](#table-wp-woocommerce-sessions)
- [Table: wp_woocommerce_shipping_zone_locations](#table-wp-woocommerce-shipping-zone-locations)
- [Table: wp_woocommerce_shipping_zone_methods](#table-wp-woocommerce-shipping-zone-methods)
- [Table: wp_woocommerce_shipping_zones](#table-wp-woocommerce-shipping-zones)
- [Table: wp_woocommerce_tax_rate_locations](#table-wp-woocommerce-tax-rate-locations)
- [Table: wp_woocommerce_tax_rates](#table-wp-woocommerce-tax-rates)

---

## Table: achievement_categories

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `achievement_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_display_name` varchar(150) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `display_name_vi` varchar(150) NOT NULL,
  `display_name_en` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `description_vi` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `icon_name` varchar(100) DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `color_primary` varchar(7) DEFAULT '#10b981',
  `color_secondary` varchar(7) DEFAULT '#065f46',
  `badge_template` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `difficulty_level` enum('beginner','intermediate','advanced','expert','legendary') DEFAULT 'beginner',
  `max_achievements_per_user` int(11) DEFAULT NULL,
  `unlock_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`unlock_requirements`)),
  `category_points_multiplier` decimal(3,2) DEFAULT 1.00,
  `environmental_impact_category` enum('carbon','waste','energy','water','transport','social','learning','general') DEFAULT 'general',
  `sustainability_weight` decimal(3,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`),
  UNIQUE KEY `category_slug` (`category_slug`),
  KEY `idx_active_sort` (`is_active`,`sort_order`),
  KEY `idx_slug` (`category_slug`),
  KEY `idx_environmental_type` (`environmental_impact_category`,`is_active`),
  KEY `idx_difficulty` (`difficulty_level`,`is_featured`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```



## Table: achievement_processing_queue

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `achievement_processing_queue` (
  `user_id` int(11) NOT NULL,
  `queued_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `idx_queued_at` (`queued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#

## Table: achievements_enhanced

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `achievements_enhanced` (
  `achievement_id` int(11) NOT NULL AUTO_INCREMENT,
  `achievement_name` varchar(150) NOT NULL,
  `achievement_slug` varchar(150) NOT NULL,
  `achievement_code` varchar(50) NOT NULL,
  `title_vi` varchar(200) NOT NULL,
  `title_en` varchar(200) NOT NULL,
  `description_vi` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `achievement_type` enum('one_time','repeatable','progressive','streak','seasonal','hidden','special') DEFAULT 'one_time',
  `trigger_type` enum('manual','automatic','scheduled','event_based') DEFAULT 'automatic',
  `trigger_events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trigger_events`)),
  `unlock_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`unlock_criteria`)),
  `progress_tracking` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`progress_tracking`)),
  `points_reward` int(11) DEFAULT 0,
  `experience_points` int(11) DEFAULT 0,
  `green_points` int(11) DEFAULT 0,
  `icon_name` varchar(100) DEFAULT NULL,
  `badge_image_url` varchar(255) DEFAULT NULL,
  `rarity_level` enum('common','uncommon','rare','epic','legendary') DEFAULT 'common',
  `difficulty_rating` tinyint(4) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `is_hidden` tinyint(1) DEFAULT 0,
  `environmental_category` varchar(50) DEFAULT NULL,
  `carbon_impact_kg` decimal(10,2) DEFAULT 0.00,
  `total_unlocks` int(11) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`achievement_id`),
  UNIQUE KEY `achievement_slug` (`achievement_slug`),
  UNIQUE KEY `achievement_code` (`achievement_code`),
  KEY `idx_type_rarity` (`achievement_type`,`rarity_level`),
  KEY `idx_environmental` (`environmental_category`),
  KEY `idx_active` (`is_active`,`is_hidden`),
  FULLTEXT KEY `achievement_name` (`achievement_name`,`title_vi`,`title_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```



## Table: ai_experiments

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `ai_experiments` (
  `experiment_id` int(11) NOT NULL AUTO_INCREMENT,
  `experiment_name` varchar(255) NOT NULL,
  `model_id` int(11) NOT NULL,
  `experiment_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`experiment_config`)),
  `hyperparameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hyperparameters`)),
  `training_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`training_metrics`)),
  `validation_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_metrics`)),
  `test_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`test_metrics`)),
  `experiment_status` enum('running','completed','failed','stopped') DEFAULT 'running',
  `best_score` decimal(5,4) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`experiment_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_model_experiments` (`model_id`,`started_at`),
  KEY `idx_best_score` (`best_score`),
  CONSTRAINT `ai_experiments_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `ai_models` (`model_id`) ON DELETE CASCADE,
  CONSTRAINT `ai_experiments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```



## Table: ai_model_monitoring

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `ai_model_monitoring` (
  `monitoring_id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL,
  `monitoring_date` date NOT NULL,
  `total_predictions` int(11) DEFAULT 0,
  `average_confidence` decimal(5,4) DEFAULT NULL,
  `average_processing_time_ms` int(11) DEFAULT NULL,
  `error_count` int(11) DEFAULT 0,
  `accuracy_drift` decimal(7,4) DEFAULT NULL,
  `performance_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_metrics`)),
  `alert_level` enum('normal','warning','critical') DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`monitoring_id`),
  UNIQUE KEY `unique_model_date` (`model_id`,`monitoring_date`),
  KEY `idx_monitoring_date` (`monitoring_date`,`alert_level`),
  KEY `idx_model_performance` (`model_id`,`monitoring_date`),
  CONSTRAINT `ai_model_monitoring_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `ai_models` (`model_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```sql
CREATE TABLE `ai_models` (
  `model_id` int(11) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(255) NOT NULL,
  `model_type` enum('classification','regression','clustering','recommendation','nlp','computer_vision') NOT NULL,
  `framework` enum('tensorflow','pytorch','scikit_learn','keras','custom') NOT NULL,
  `model_version` varchar(50) NOT NULL,
  `environmental_focus` enum('waste_classification','carbon_prediction','pollution_detection','sustainability_scoring') NOT NULL,
  `accuracy` decimal(5,4) DEFAULT NULL,
  `model_status` enum('training','testing','deployed','deprecated') DEFAULT 'training',
  `deployment_endpoint` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`model_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_model_type` (`model_type`,`environmental_focus`),
  KEY `idx_status` (`model_status`,`created_at`),
  CONSTRAINT `ai_models_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_predictions` (
  `prediction_id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `input_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_data`)),
  `input_type` enum('image','text','json','sensor_data') NOT NULL,
  `prediction_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`prediction_result`)),
  `confidence_score` decimal(5,4) DEFAULT NULL,
  `environmental_impact` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`environmental_impact`)),
  `processing_time_ms` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`prediction_id`),
  KEY `idx_model_time` (`model_id`,`created_at`),
  KEY `idx_user_predictions` (`user_id`,`created_at`),
  CONSTRAINT `ai_predictions_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `ai_models` (`model_id`) ON DELETE CASCADE,
  CONSTRAINT `ai_predictions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_training_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL,
  `job_name` varchar(255) NOT NULL,
  `job_type` enum('training','retraining','validation','hyperparameter_tuning') NOT NULL,
  `dataset_path` varchar(1000) NOT NULL,
  `training_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`training_config`)),
  `job_status` enum('pending','running','completed','failed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `actual_duration_minutes` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`queue_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status_priority` (`job_status`,`priority`),
  KEY `idx_model_jobs` (`model_id`,`created_at`),
  CONSTRAINT `ai_training_queue_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `ai_models` (`model_id`) ON DELETE CASCADE,
  CONSTRAINT `ai_training_queue_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `article_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `comment_type` enum('comment','question','suggestion','correction') DEFAULT 'comment',
  `like_count` int(11) DEFAULT 0,
  `dislike_count` int(11) DEFAULT 0,
  `reply_count` int(11) DEFAULT 0,
  `is_highlighted` tinyint(1) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected','flagged') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `idx_article_status` (`article_id`,`status`,`created_at`),
  KEY `idx_user_comments` (`user_id`,`created_at`),
  KEY `idx_parent_comment` (`parent_comment_id`,`created_at`),
  KEY `idx_article_comments_user_article` (`user_id`,`article_id`,`created_at`),
  CONSTRAINT `article_comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE,
  CONSTRAINT `article_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `article_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `article_comments` (`comment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `article_interactions` (
  `interaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interaction_type` enum('view','like','dislike','bookmark','share','comment','report') NOT NULL,
  `interaction_value` varchar(255) DEFAULT NULL,
  `session_duration_seconds` int(11) DEFAULT NULL,
  `scroll_depth_percentage` int(11) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`interaction_id`),
  UNIQUE KEY `unique_interaction` (`article_id`,`user_id`,`interaction_type`),
  KEY `idx_article_type` (`article_id`,`interaction_type`),
  KEY `idx_user_date` (`user_id`,`created_at`),
  KEY `idx_type_date` (`interaction_type`,`created_at`),
  KEY `idx_article_interactions_user_article` (`user_id`,`article_id`,`created_at`),
  KEY `idx_article_interactions_analytics` (`article_id`,`created_at`,`interaction_type`),
  KEY `idx_article_interactions_user_type` (`user_id`,`interaction_type`),
  KEY `idx_article_interactions_article_created` (`article_id`,`created_at`),
  KEY `idx_article_interactions_type_date` (`interaction_type`,`created_at`),
  KEY `idx_interactions_article_user_type` (`article_id`,`user_id`,`interaction_type`),
  KEY `idx_interactions_type_date` (`interaction_type`,`created_at`),
  KEY `idx_interactions_user_date` (`user_id`,`created_at`),
  KEY `idx_interactions_session_duration` (`session_duration_seconds`,`scroll_depth_percentage`),
  KEY `idx_article_interactions_counters` (`article_id`,`interaction_type`,`user_id`,`created_at`),
  CONSTRAINT `article_interactions_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE,
  CONSTRAINT `article_interactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`article_id`,`tag_id`),
  KEY `idx_tag_articles` (`tag_id`,`added_at`),
  KEY `idx_added_by` (`added_by`,`added_at`),
  CONSTRAINT `article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE,
  CONSTRAINT `article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `content_tags` (`tag_id`) ON DELETE CASCADE,
  CONSTRAINT `article_tags_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `articles` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `article_type` enum('article','guide','research','news') DEFAULT 'article',
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `status` enum('draft','pending_review','published','archived') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `environmental_impact_score` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`article_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_status_published` (`status`,`published_at`),
  KEY `idx_slug` (`slug`),
  KEY `idx_articles_performance_analysis` (`created_at`,`status`,`category_id`),
  KEY `idx_articles_author_content` (`author_id`,`created_at`,`status`),
  KEY `idx_articles_author_date` (`author_id`,`created_at`),
  KEY `idx_articles_category_status` (`category_id`,`status`),
  KEY `idx_articles_author_status_date` (`author_id`,`status`,`created_at`),
  KEY `idx_articles_category_featured` (`category_id`,`is_featured`,`status`),
  KEY `idx_articles_impact_views` (`environmental_impact_score`,`view_count`),
  KEY `idx_articles_type_difficulty` (`article_type`,`difficulty_level`,`status`),
  CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `badges_system` (
  `badge_id` int(11) NOT NULL AUTO_INCREMENT,
  `badge_name` varchar(150) NOT NULL,
  `badge_slug` varchar(150) NOT NULL,
  `title_vi` varchar(200) NOT NULL,
  `title_en` varchar(200) NOT NULL,
  `description_vi` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `badge_image_url` varchar(255) DEFAULT NULL,
  `badge_icon` varchar(100) DEFAULT NULL,
  `badge_color` varchar(7) DEFAULT '#10b981',
  `badge_level` enum('bronze','silver','gold','platinum','diamond') DEFAULT 'bronze',
  `rarity` enum('common','uncommon','rare','epic','legendary') DEFAULT 'common',
  `points_value` int(11) DEFAULT 0,
  `unlock_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`unlock_criteria`)),
  `auto_award` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `is_hidden` tinyint(1) DEFAULT 0,
  `total_awarded` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`badge_id`),
  UNIQUE KEY `badge_slug` (`badge_slug`),
  KEY `idx_level_rarity` (`badge_level`,`rarity`),
  KEY `idx_active` (`is_active`,`is_hidden`),
  FULLTEXT KEY `badge_name` (`badge_name`,`title_vi`,`title_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


CREATE TABLE `cache_invalidation_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('user','article','product','order','category','global') NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `invalidation_reason` varchar(255) NOT NULL,
  `cache_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_cache_entity_type` (`entity_type`,`created_at`),
  KEY `idx_cache_entity_id` (`entity_id`,`created_at`),
  KEY `idx_cache_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cache invalidation tracking for performance optimization';

CREATE TABLE `carbon_footprints` (
  `footprint_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_category` enum('transport','energy','waste','food','consumption') NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `carbon_kg` decimal(10,3) NOT NULL,
  `carbon_saved_kg` decimal(10,3) DEFAULT 0.000,
  `activity_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`footprint_id`),
  KEY `idx_user_activity_date` (`user_id`,`activity_date`),
  KEY `idx_category_date` (`activity_category`,`activity_date`),
  KEY `idx_carbon_footprints_user_date` (`user_id`,`activity_date`),
  KEY `idx_carbon_footprints_category` (`activity_category`,`created_at`),
  KEY `idx_carbon_footprints_user_category` (`user_id`,`activity_category`),
  KEY `idx_carbon_user_category_date` (`user_id`,`activity_category`,`activity_date`),
  KEY `idx_carbon_category_kg_date` (`activity_category`,`carbon_kg`,`created_at`),
  KEY `idx_carbon_date_kg` (`activity_date`,`carbon_kg`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| footprint_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| activity_category | enum('transport','energy','waste','food','consumption') | NO | MUL |  |  |
| activity_type | varchar(100) | NO |  |  |  |
| carbon_kg | decimal(10,3) | NO |  |  |  |
| carbon_saved_kg | decimal(10,3) | YES |  | 0.000 |  |
| activity_date | date | NO | MUL |  |  |
| description | text | YES |  |  |  |
| points_earned | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: carbon_reduction_goals

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `carbon_reduction_goals` (
  `goal_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(100) NOT NULL,
  `goal_type` enum('daily','weekly','monthly','yearly') DEFAULT 'monthly',
  `target_reduction_kg` decimal(10,3) NOT NULL,
  `current_reduction_kg` decimal(10,3) DEFAULT 0.000,
  `target_date` date NOT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','failed','paused') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`goal_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_target_date` (`target_date`),
  KEY `idx_carbon_reduction_goals_user_status` (`user_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| goal_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| goal_name | varchar(100) | NO |  |  |  |
| goal_type | enum('daily','weekly','monthly','yearly') | YES |  | monthly |  |
| target_reduction_kg | decimal(10,3) | NO |  |  |  |
| current_reduction_kg | decimal(10,3) | YES |  | 0.000 |  |
| target_date | date | NO | MUL |  |  |
| progress_percentage | decimal(5,2) | YES |  | 0.00 |  |
| status | enum('active','completed','failed','paused') | YES |  | active |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: categories

**Row Count:** 37

### CREATE TABLE Statement

```sql
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `category_type` enum('article','product','forum','event','general') DEFAULT 'general',
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_keywords`)),
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 0,
  `path` varchar(500) DEFAULT NULL,
  `banner_image_url` varchar(255) DEFAULT NULL,
  `post_count` int(11) DEFAULT 0,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_type_active` (`category_type`,`is_active`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| category_id | int(11) | NO | PRI |  | auto_increment |
| name | varchar(100) | NO |  |  |  |
| name_en | varchar(100) | YES |  |  |  |
| slug | varchar(100) | NO | UNI |  |  |
| description | text | YES |  |  |  |
| icon_url | varchar(255) | YES |  |  |  |
| color_code | varchar(7) | YES |  |  |  |
| category_type | enum('article','product','forum','event','general') | YES | MUL | general |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| is_featured | tinyint(1) | YES |  | 0 |  |
| seo_title | varchar(255) | YES |  |  |  |
| seo_description | text | YES |  |  |  |
| seo_keywords | longtext | YES |  |  |  |
| sort_order | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| parent_id | int(11) | YES | MUL |  |  |
| level | int(11) | YES |  | 0 |  |
| path | varchar(500) | YES |  |  |  |
| banner_image_url | varchar(255) | YES |  |  |  |
| post_count | int(11) | YES |  | 0 |  |

---

## Table: challenge_participation

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `challenge_participation` (
  `participation_id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_progress` decimal(10,2) DEFAULT 0.00,
  `target_progress` decimal(10,2) NOT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completion_rank` int(11) DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `badge_earned` tinyint(1) DEFAULT 0,
  `special_rewards` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`special_rewards`)),
  `daily_progress` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`daily_progress`)),
  `best_day_performance` decimal(10,2) DEFAULT 0.00,
  `consistency_score` decimal(5,2) DEFAULT 0.00,
  `environmental_contribution` decimal(10,2) DEFAULT 0.00,
  `carbon_saved` decimal(8,2) DEFAULT 0.00,
  `waste_processed` int(11) DEFAULT 0,
  `shared_progress` tinyint(1) DEFAULT 0,
  `encouraged_others` int(11) DEFAULT 0,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`participation_id`),
  UNIQUE KEY `unique_challenge_participation` (`challenge_id`,`user_id`),
  KEY `idx_challenge_user` (`challenge_id`,`user_id`),
  KEY `idx_completed` (`is_completed`,`completed_at`),
  KEY `idx_progress` (`progress_percentage`),
  KEY `idx_rank` (`completion_rank`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `challenge_participation_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `seasonal_challenges` (`challenge_id`) ON DELETE CASCADE,
  CONSTRAINT `challenge_participation_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| participation_id | int(11) | NO | PRI |  | auto_increment |
| challenge_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | NO | MUL |  |  |
| registered_at | timestamp | NO |  | current_timestamp() |  |
| current_progress | decimal(10,2) | YES |  | 0.00 |  |
| target_progress | decimal(10,2) | NO |  |  |  |
| progress_percentage | decimal(5,2) | YES | MUL | 0.00 |  |
| is_completed | tinyint(1) | YES | MUL | 0 |  |
| completed_at | timestamp | YES |  |  |  |
| completion_rank | int(11) | YES | MUL |  |  |
| points_earned | int(11) | YES |  | 0 |  |
| badge_earned | tinyint(1) | YES |  | 0 |  |
| special_rewards | longtext | YES |  |  |  |
| daily_progress | longtext | YES |  |  |  |
| best_day_performance | decimal(10,2) | YES |  | 0.00 |  |
| consistency_score | decimal(5,2) | YES |  | 0.00 |  |
| environmental_contribution | decimal(10,2) | YES |  | 0.00 |  |
| carbon_saved | decimal(8,2) | YES |  | 0.00 |  |
| waste_processed | int(11) | YES |  | 0 |  |
| shared_progress | tinyint(1) | YES |  | 0 |  |
| encouraged_others | int(11) | YES |  | 0 |  |
| last_activity | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: community_moderators

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `community_moderators` (
  `community_mod_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `moderator_level` enum('volunteer','trusted','expert','senior','lead') DEFAULT 'volunteer',
  `specialization_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialization_areas`)),
  `moderation_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`moderation_scope`)),
  `training_completed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`training_completed`)),
  `certification_level` varchar(100) DEFAULT NULL,
  `environmental_expertise_verified` tinyint(1) DEFAULT 0,
  `community_standing_score` decimal(5,2) DEFAULT 0.00,
  `total_reviews_completed` int(11) DEFAULT 0,
  `accuracy_rate` decimal(5,4) DEFAULT NULL,
  `consistency_score` decimal(5,2) DEFAULT NULL,
  `community_feedback_score` decimal(5,2) DEFAULT NULL,
  `reviews_this_month` int(11) DEFAULT 0,
  `reviews_this_week` int(11) DEFAULT 0,
  `average_review_time_minutes` int(11) DEFAULT NULL,
  `last_active_date` date DEFAULT NULL,
  `status` enum('active','inactive','suspended','probation','retired') DEFAULT 'active',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `can_escalate` tinyint(1) DEFAULT 1,
  `can_override_ai` tinyint(1) DEFAULT 0,
  `max_daily_reviews` int(11) DEFAULT 50,
  `recognition_badges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recognition_badges`)),
  `contribution_points` int(11) DEFAULT 0,
  `community_endorsements` int(11) DEFAULT 0,
  `peer_recommendations` int(11) DEFAULT 0,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `last_performance_review` date DEFAULT NULL,
  `next_performance_review` date DEFAULT NULL,
  `performance_notes` text DEFAULT NULL,
  `improvement_plan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`community_mod_id`),
  UNIQUE KEY `unique_user_moderator` (`user_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_moderator_level` (`moderator_level`,`status`),
  KEY `idx_performance` (`accuracy_rate`,`consistency_score`),
  KEY `idx_activity` (`status`,`last_active_date`),
  KEY `idx_specialization` (`specialization_areas`(255)),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `community_moderators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `community_moderators_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| community_mod_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | UNI |  |  |
| moderator_level | enum('volunteer','trusted','expert','senior','lead') | YES | MUL | volunteer |  |
| specialization_areas | longtext | YES | MUL |  |  |
| moderation_scope | longtext | YES |  |  |  |
| training_completed | longtext | YES |  |  |  |
| certification_level | varchar(100) | YES |  |  |  |
| environmental_expertise_verified | tinyint(1) | YES |  | 0 |  |
| community_standing_score | decimal(5,2) | YES |  | 0.00 |  |
| total_reviews_completed | int(11) | YES |  | 0 |  |
| accuracy_rate | decimal(5,4) | YES | MUL |  |  |
| consistency_score | decimal(5,2) | YES |  |  |  |
| community_feedback_score | decimal(5,2) | YES |  |  |  |
| reviews_this_month | int(11) | YES |  | 0 |  |
| reviews_this_week | int(11) | YES |  | 0 |  |
| average_review_time_minutes | int(11) | YES |  |  |  |
| last_active_date | date | YES |  |  |  |
| status | enum('active','inactive','suspended','probation','retired') | YES | MUL | active |  |
| permissions | longtext | YES |  |  |  |
| can_escalate | tinyint(1) | YES |  | 1 |  |
| can_override_ai | tinyint(1) | YES |  | 0 |  |
| max_daily_reviews | int(11) | YES |  | 50 |  |
| recognition_badges | longtext | YES |  |  |  |
| contribution_points | int(11) | YES |  | 0 |  |
| community_endorsements | int(11) | YES |  | 0 |  |
| peer_recommendations | int(11) | YES |  | 0 |  |
| application_date | timestamp | NO |  | current_timestamp() |  |
| approved_by | int(11) | YES | MUL |  |  |
| approval_date | timestamp | YES |  |  |  |
| probation_end_date | date | YES |  |  |  |
| last_performance_review | date | YES |  |  |  |
| next_performance_review | date | YES |  |  |  |
| performance_notes | text | YES |  |  |  |
| improvement_plan | text | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: content_performance_final

**Row Count:** 1

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| article_id | int(11) | NO |  | 0 |  |
| title | varchar(255) | NO |  |  |  |
| category_id | int(11) | YES |  |  |  |
| author_id | int(11) | NO |  |  |  |
| publish_date | timestamp | NO |  | current_timestamp() |  |
| total_views | decimal(22,0) | YES |  |  |  |
| total_likes | decimal(22,0) | YES |  |  |  |
| total_shares | decimal(22,0) | YES |  |  |  |
| unique_users_engaged | bigint(21) | YES |  |  |  |

---

## Table: content_shares

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `content_shares` (
  `share_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content_type` enum('article','event','product','forum_topic') NOT NULL,
  `content_id` int(11) NOT NULL,
  `content_title` varchar(255) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `share_url` text DEFAULT NULL,
  `click_count` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `share_status` enum('pending','published','verified') DEFAULT 'pending',
  `shared_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`share_id`),
  KEY `idx_user_content` (`user_id`,`content_type`,`content_id`),
  KEY `idx_platform_date` (`platform_id`,`shared_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| share_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| content_type | enum('article','event','product','forum_topic') | NO |  |  |  |
| content_id | int(11) | NO |  |  |  |
| content_title | varchar(255) | NO |  |  |  |
| platform_id | int(11) | NO | MUL |  |  |
| share_url | text | YES |  |  |  |
| click_count | int(11) | YES |  | 0 |  |
| points_earned | int(11) | YES |  | 0 |  |
| share_status | enum('pending','published','verified') | YES |  | pending |  |
| shared_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: content_tags

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `content_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) NOT NULL,
  `tag_slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_trending` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_name` (`tag_name`),
  UNIQUE KEY `tag_slug` (`tag_slug`),
  KEY `idx_slug` (`tag_slug`),
  KEY `idx_usage` (`usage_count`),
  KEY `idx_trending` (`is_trending`,`usage_count`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| tag_id | int(11) | NO | PRI |  | auto_increment |
| tag_name | varchar(50) | NO | UNI |  |  |
| tag_slug | varchar(50) | NO | UNI |  |  |
| description | text | YES |  |  |  |
| color_code | varchar(7) | YES |  |  |  |
| usage_count | int(11) | YES | MUL | 0 |  |
| is_trending | tinyint(1) | YES | MUL | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: conversation_participants

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `conversation_participants` (
  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','moderator','admin','observer') DEFAULT 'member',
  `can_send_messages` tinyint(1) DEFAULT 1,
  `can_add_participants` tinyint(1) DEFAULT 0,
  `can_remove_participants` tinyint(1) DEFAULT 0,
  `can_edit_conversation` tinyint(1) DEFAULT 0,
  `status` enum('active','muted','blocked','left') DEFAULT 'active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_read_id` int(11) DEFAULT NULL,
  `unread_count` int(11) DEFAULT 0,
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{"mentions": true, "all_messages": false}' CHECK (json_valid(`notification_preferences`)),
  `green_contribution_score` int(11) DEFAULT 0,
  `environmental_expertise_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`participant_id`),
  UNIQUE KEY `unique_conversation_participant` (`conversation_id`,`user_id`),
  KEY `idx_conversation_participants` (`conversation_id`,`status`),
  KEY `idx_user_conversations` (`user_id`,`status`,`last_read_at`),
  KEY `idx_unread_messages` (`user_id`,`unread_count`),
  CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| participant_id | int(11) | NO | PRI |  | auto_increment |
| conversation_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | NO | MUL |  |  |
| role | enum('member','moderator','admin','observer') | YES |  | member |  |
| can_send_messages | tinyint(1) | YES |  | 1 |  |
| can_add_participants | tinyint(1) | YES |  | 0 |  |
| can_remove_participants | tinyint(1) | YES |  | 0 |  |
| can_edit_conversation | tinyint(1) | YES |  | 0 |  |
| status | enum('active','muted','blocked','left') | YES |  | active |  |
| joined_at | timestamp | NO |  | current_timestamp() |  |
| last_read_at | timestamp | NO |  | current_timestamp() |  |
| last_message_read_id | int(11) | YES |  |  |  |
| unread_count | int(11) | YES |  | 0 |  |
| notification_preferences | longtext | YES |  | '{"mentions": true, "all_messages": false}' |  |
| green_contribution_score | int(11) | YES |  | 0 |  |
| environmental_expertise_level | enum('beginner','intermediate','advanced','expert') | YES |  | beginner |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: conversations

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_type` enum('direct','group','announcement','support','environmental_project','exchange_negotiation') DEFAULT 'direct',
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `participant_count` int(11) DEFAULT 0,
  `max_participants` int(11) DEFAULT 100,
  `is_environmental_focused` tinyint(1) DEFAULT 0,
  `environmental_topic` enum('waste_management','carbon_reduction','renewable_energy','conservation','sustainability','climate_change') DEFAULT NULL,
  `related_environmental_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `requires_approval` tinyint(1) DEFAULT 0,
  `auto_archive_days` int(11) DEFAULT 90,
  `status` enum('active','archived','locked','deleted') DEFAULT 'active',
  `total_messages` int(11) DEFAULT 0,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`conversation_id`),
  KEY `idx_conversation_type` (`conversation_type`,`status`),
  KEY `idx_environmental_topic` (`is_environmental_focused`,`environmental_topic`),
  KEY `idx_public_conversations` (`is_public`,`status`,`last_activity_at`),
  KEY `idx_creator` (`creator_id`,`created_at`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| conversation_id | int(11) | NO | PRI |  | auto_increment |
| conversation_type | enum('direct','group','announcement','support','environmental_project','exchange_negotiation') | YES | MUL | direct |  |
| title | varchar(255) | YES |  |  |  |
| description | text | YES |  |  |  |
| creator_id | int(11) | NO | MUL |  |  |
| participant_count | int(11) | YES |  | 0 |  |
| max_participants | int(11) | YES |  | 100 |  |
| is_environmental_focused | tinyint(1) | YES | MUL | 0 |  |
| environmental_topic | enum('waste_management','carbon_reduction','renewable_energy','conservation','sustainability','climate_change') | YES |  |  |  |
| related_environmental_id | int(11) | YES |  |  |  |
| is_public | tinyint(1) | YES | MUL | 0 |  |
| requires_approval | tinyint(1) | YES |  | 0 |  |
| auto_archive_days | int(11) | YES |  | 90 |  |
| status | enum('active','archived','locked','deleted') | YES |  | active |  |
| total_messages | int(11) | YES |  | 0 |  |
| last_message_at | timestamp | YES |  |  |  |
| last_activity_at | timestamp | NO |  | current_timestamp() |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: daily_analytics_summary

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `daily_analytics_summary` (
  `summary_date` date NOT NULL,
  `total_active_users` int(11) DEFAULT 0,
  `new_user_registrations` int(11) DEFAULT 0,
  `total_article_views` int(11) DEFAULT 0,
  `total_articles_published` int(11) DEFAULT 0,
  `total_marketplace_orders` int(11) DEFAULT 0,
  `total_marketplace_revenue` decimal(15,2) DEFAULT 0.00,
  `total_carbon_calculations` int(11) DEFAULT 0,
  `total_waste_reports` int(11) DEFAULT 0,
  `total_environmental_quizzes` int(11) DEFAULT 0,
  `avg_environmental_quiz_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`summary_date`),
  KEY `idx_summary_date` (`summary_date`),
  KEY `idx_summary_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| summary_date | date | NO | PRI |  |  |
| total_active_users | int(11) | YES |  | 0 |  |
| new_user_registrations | int(11) | YES |  | 0 |  |
| total_article_views | int(11) | YES |  | 0 |  |
| total_articles_published | int(11) | YES |  | 0 |  |
| total_marketplace_orders | int(11) | YES |  | 0 |  |
| total_marketplace_revenue | decimal(15,2) | YES |  | 0.00 |  |
| total_carbon_calculations | int(11) | YES |  | 0 |  |
| total_waste_reports | int(11) | YES |  | 0 |  |
| total_environmental_quizzes | int(11) | YES |  | 0 |  |
| avg_environmental_quiz_score | decimal(5,2) | YES |  | 0.00 |  |
| created_at | timestamp | NO | MUL | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: dashboard_real_time_metrics_final

**Row Count:** 4

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| metric_name | varchar(20) | NO |  |  |  |
| metric_value | decimal(32,2) | YES |  |  |  |
| metric_unit | varchar(6) | NO |  |  |  |
| metric_date | date | NO |  | 0000-00-00 |  |

---

## Table: dashboard_widgets

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `dashboard_widgets` (
  `widget_id` int(11) NOT NULL AUTO_INCREMENT,
  `widget_name` varchar(100) NOT NULL,
  `widget_type` varchar(50) NOT NULL,
  `dashboard_category` varchar(50) NOT NULL,
  `data_source` varchar(100) NOT NULL,
  `query_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`widget_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| widget_id | int(11) | NO | PRI |  | auto_increment |
| widget_name | varchar(100) | NO |  |  |  |
| widget_type | varchar(50) | NO |  |  |  |
| dashboard_category | varchar(50) | NO |  |  |  |
| data_source | varchar(100) | NO |  |  |  |
| query_template | text | NO |  |  |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: donation_campaigns

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `donation_campaigns` (
  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `campaign_slug` varchar(255) NOT NULL,
  `campaign_type` enum('one_time','recurring','emergency','project_based') DEFAULT 'one_time',
  `description` longtext NOT NULL,
  `short_description` text DEFAULT NULL,
  `story` text DEFAULT NULL,
  `target_amount` decimal(15,2) NOT NULL,
  `current_amount` decimal(15,2) DEFAULT 0.00,
  `minimum_donation` decimal(10,2) DEFAULT 10000.00,
  `suggested_amounts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_amounts`)),
  `environmental_category` enum('reforestation','ocean_cleanup','renewable_energy','wildlife_protection','pollution_control','education','research') NOT NULL,
  `expected_impact` text DEFAULT NULL,
  `impact_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`impact_metrics`)),
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `is_time_limited` tinyint(1) DEFAULT 1,
  `featured_image_url` varchar(500) DEFAULT NULL,
  `gallery_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gallery_images`)),
  `video_url` varchar(500) DEFAULT NULL,
  `status` enum('draft','active','paused','completed','cancelled') DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `urgent` tinyint(1) DEFAULT 0,
  `allow_anonymous_donations` tinyint(1) DEFAULT 1,
  `progress_updates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`progress_updates`)),
  `expense_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expense_breakdown`)),
  `impact_reports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`impact_reports`)),
  `view_count` int(11) DEFAULT 0,
  `share_count` int(11) DEFAULT 0,
  `donor_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`campaign_id`),
  UNIQUE KEY `campaign_slug` (`campaign_slug`),
  KEY `idx_org_status` (`organization_id`,`status`),
  KEY `idx_category_active` (`environmental_category`,`status`),
  KEY `idx_featured_urgent` (`featured`,`urgent`,`status`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_target_progress` (`target_amount`,`current_amount`),
  CONSTRAINT `donation_campaigns_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `donation_organizations` (`organization_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| campaign_id | int(11) | NO | PRI |  | auto_increment |
| organization_id | int(11) | NO | MUL |  |  |
| campaign_name | varchar(255) | NO |  |  |  |
| campaign_slug | varchar(255) | NO | UNI |  |  |
| campaign_type | enum('one_time','recurring','emergency','project_based') | YES |  | one_time |  |
| description | longtext | NO |  |  |  |
| short_description | text | YES |  |  |  |
| story | text | YES |  |  |  |
| target_amount | decimal(15,2) | NO | MUL |  |  |
| current_amount | decimal(15,2) | YES |  | 0.00 |  |
| minimum_donation | decimal(10,2) | YES |  | 10000.00 |  |
| suggested_amounts | longtext | YES |  |  |  |
| environmental_category | enum('reforestation','ocean_cleanup','renewable_energy','wildlife_protection','pollution_control','education','research') | NO | MUL |  |  |
| expected_impact | text | YES |  |  |  |
| impact_metrics | longtext | YES |  |  |  |
| start_date | timestamp | NO |  | current_timestamp() |  |
| end_date | timestamp | YES | MUL |  |  |
| is_time_limited | tinyint(1) | YES |  | 1 |  |
| featured_image_url | varchar(500) | YES |  |  |  |
| gallery_images | longtext | YES |  |  |  |
| video_url | varchar(500) | YES |  |  |  |
| status | enum('draft','active','paused','completed','cancelled') | YES |  | draft |  |
| featured | tinyint(1) | YES | MUL | 0 |  |
| urgent | tinyint(1) | YES |  | 0 |  |
| allow_anonymous_donations | tinyint(1) | YES |  | 1 |  |
| progress_updates | longtext | YES |  |  |  |
| expense_breakdown | longtext | YES |  |  |  |
| impact_reports | longtext | YES |  |  |  |
| view_count | int(11) | YES |  | 0 |  |
| share_count | int(11) | YES |  | 0 |  |
| donor_count | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: donation_impact_reports

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `donation_impact_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_period_start` date NOT NULL,
  `report_period_end` date NOT NULL,
  `report_type` enum('quarterly','annual','project_completion','milestone') DEFAULT 'quarterly',
  `total_donations_period` decimal(15,2) NOT NULL,
  `total_expenses_period` decimal(15,2) NOT NULL,
  `administrative_costs` decimal(15,2) DEFAULT 0.00,
  `program_costs` decimal(15,2) NOT NULL,
  `impact_metrics_achieved` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`impact_metrics_achieved`)),
  `environmental_outcomes` text DEFAULT NULL,
  `beneficiaries_reached` int(11) DEFAULT 0,
  `expense_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expense_breakdown`)),
  `activities_completed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activities_completed`)),
  `challenges_faced` text DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `videos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`videos`)),
  `testimonials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`testimonials`)),
  `third_party_verification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`third_party_verification`)),
  `next_steps` text DEFAULT NULL,
  `future_funding_needs` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_campaign_period` (`campaign_id`,`report_period_end`),
  KEY `idx_status_published` (`status`,`published_date`),
  CONSTRAINT `donation_impact_reports_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`campaign_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| report_id | int(11) | NO | PRI |  | auto_increment |
| campaign_id | int(11) | NO | MUL |  |  |
| report_title | varchar(255) | NO |  |  |  |
| report_period_start | date | NO |  |  |  |
| report_period_end | date | NO |  |  |  |
| report_type | enum('quarterly','annual','project_completion','milestone') | YES |  | quarterly |  |
| total_donations_period | decimal(15,2) | NO |  |  |  |
| total_expenses_period | decimal(15,2) | NO |  |  |  |
| administrative_costs | decimal(15,2) | YES |  | 0.00 |  |
| program_costs | decimal(15,2) | NO |  |  |  |
| impact_metrics_achieved | longtext | YES |  |  |  |
| environmental_outcomes | text | YES |  |  |  |
| beneficiaries_reached | int(11) | YES |  | 0 |  |
| expense_breakdown | longtext | YES |  |  |  |
| activities_completed | longtext | YES |  |  |  |
| challenges_faced | text | YES |  |  |  |
| lessons_learned | text | YES |  |  |  |
| photos | longtext | YES |  |  |  |
| videos | longtext | YES |  |  |  |
| testimonials | longtext | YES |  |  |  |
| third_party_verification | longtext | YES |  |  |  |
| next_steps | text | YES |  |  |  |
| future_funding_needs | decimal(15,2) | YES |  | 0.00 |  |
| status | enum('draft','published','archived') | YES | MUL | draft |  |
| published_date | timestamp | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: donation_organizations

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `donation_organizations` (
  `organization_id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_name` varchar(255) NOT NULL,
  `organization_slug` varchar(255) NOT NULL,
  `organization_type` enum('ngo','charity','foundation','environmental_group','community_group') NOT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `mission_statement` text DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `environmental_focus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`environmental_focus`)),
  `impact_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`impact_areas`)),
  `sdg_goals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sdg_goals`)),
  `verification_status` enum('pending','verified','rejected','suspended') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verification_date` timestamp NULL DEFAULT NULL,
  `tax_exempt_status` tinyint(1) DEFAULT 0,
  `transparency_score` decimal(3,2) DEFAULT 0.00,
  `total_donations_received` decimal(15,2) DEFAULT 0.00,
  `total_projects_funded` int(11) DEFAULT 0,
  `administrative_percentage` decimal(5,2) DEFAULT 0.00,
  `logo_url` varchar(500) DEFAULT NULL,
  `cover_image_url` varchar(500) DEFAULT NULL,
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `is_active` tinyint(1) DEFAULT 1,
  `accepts_donations` tinyint(1) DEFAULT 1,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`organization_id`),
  UNIQUE KEY `organization_slug` (`organization_slug`),
  UNIQUE KEY `registration_number` (`registration_number`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_status_active` (`verification_status`,`is_active`),
  KEY `idx_env_focus` (`environmental_focus`(100)),
  KEY `idx_transparency` (`transparency_score`),
  KEY `idx_featured` (`featured`,`verification_status`),
  CONSTRAINT `donation_organizations_ibfk_1` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| organization_id | int(11) | NO | PRI |  | auto_increment |
| organization_name | varchar(255) | NO |  |  |  |
| organization_slug | varchar(255) | NO | UNI |  |  |
| organization_type | enum('ngo','charity','foundation','environmental_group','community_group') | NO |  |  |  |
| registration_number | varchar(100) | YES | UNI |  |  |
| contact_email | varchar(255) | NO |  |  |  |
| contact_phone | varchar(20) | YES |  |  |  |
| website_url | varchar(500) | YES |  |  |  |
| address | text | YES |  |  |  |
| description | longtext | YES |  |  |  |
| mission_statement | text | YES |  |  |  |
| established_year | year(4) | YES |  |  |  |
| environmental_focus | longtext | YES | MUL |  |  |
| impact_areas | longtext | YES |  |  |  |
| sdg_goals | longtext | YES |  |  |  |
| verification_status | enum('pending','verified','rejected','suspended') | YES | MUL | pending |  |
| verified_by | int(11) | YES | MUL |  |  |
| verification_date | timestamp | YES |  |  |  |
| tax_exempt_status | tinyint(1) | YES |  | 0 |  |
| transparency_score | decimal(3,2) | YES | MUL | 0.00 |  |
| total_donations_received | decimal(15,2) | YES |  | 0.00 |  |
| total_projects_funded | int(11) | YES |  | 0 |  |
| administrative_percentage | decimal(5,2) | YES |  | 0.00 |  |
| logo_url | varchar(500) | YES |  |  |  |
| cover_image_url | varchar(500) | YES |  |  |  |
| documents | longtext | YES |  |  |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| accepts_donations | tinyint(1) | YES |  | 1 |  |
| featured | tinyint(1) | YES | MUL | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: donation_subscriptions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `donation_subscriptions` (
  `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_user_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `frequency` enum('monthly','quarterly','yearly') NOT NULL,
  `status` enum('active','paused','cancelled','failed') DEFAULT 'active',
  `payment_method` enum('bank_transfer','credit_card','e_wallet') NOT NULL,
  `payment_token` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `next_payment_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `total_payments_made` int(11) DEFAULT 0,
  `total_amount_donated` decimal(15,2) DEFAULT 0.00,
  `failed_payment_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`subscription_id`),
  KEY `idx_user_status` (`donor_user_id`,`status`),
  KEY `idx_next_payment` (`next_payment_date`,`status`),
  KEY `idx_campaign_active` (`campaign_id`,`status`),
  CONSTRAINT `donation_subscriptions_ibfk_1` FOREIGN KEY (`donor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `donation_subscriptions_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`campaign_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| subscription_id | int(11) | NO | PRI |  | auto_increment |
| donor_user_id | int(11) | NO | MUL |  |  |
| campaign_id | int(11) | NO | MUL |  |  |
| amount | decimal(12,2) | NO |  |  |  |
| frequency | enum('monthly','quarterly','yearly') | NO |  |  |  |
| status | enum('active','paused','cancelled','failed') | YES |  | active |  |
| payment_method | enum('bank_transfer','credit_card','e_wallet') | NO |  |  |  |
| payment_token | varchar(255) | YES |  |  |  |
| start_date | date | NO |  |  |  |
| next_payment_date | date | NO | MUL |  |  |
| end_date | date | YES |  |  |  |
| last_payment_date | date | YES |  |  |  |
| total_payments_made | int(11) | YES |  | 0 |  |
| total_amount_donated | decimal(15,2) | YES |  | 0.00 |  |
| failed_payment_count | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: donations

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `donor_user_id` int(11) DEFAULT NULL,
  `donation_code` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) DEFAULT 'VND',
  `donation_type` enum('one_time','monthly','yearly') DEFAULT 'one_time',
  `donor_name` varchar(255) DEFAULT NULL,
  `donor_email` varchar(255) DEFAULT NULL,
  `donor_phone` varchar(20) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `payment_method` enum('bank_transfer','credit_card','e_wallet','cash','crypto') NOT NULL,
  `payment_gateway` varchar(50) DEFAULT NULL,
  `payment_transaction_id` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_confirmation_date` timestamp NULL DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_frequency` enum('monthly','quarterly','yearly') DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `recurring_end_date` date DEFAULT NULL,
  `parent_donation_id` int(11) DEFAULT NULL,
  `is_corporate_donation` tinyint(1) DEFAULT 0,
  `corporate_matching_eligible` tinyint(1) DEFAULT 0,
  `matched_amount` decimal(12,2) DEFAULT 0.00,
  `employer_name` varchar(255) DEFAULT NULL,
  `dedication_type` enum('none','in_honor','in_memory') DEFAULT 'none',
  `dedication_name` varchar(255) DEFAULT NULL,
  `donation_message` text DEFAULT NULL,
  `public_comment` text DEFAULT NULL,
  `tax_deductible` tinyint(1) DEFAULT 1,
  `receipt_requested` tinyint(1) DEFAULT 1,
  `receipt_sent` tinyint(1) DEFAULT 0,
  `receipt_sent_date` timestamp NULL DEFAULT NULL,
  `eco_points_earned` int(11) DEFAULT 0,
  `estimated_carbon_offset` decimal(10,3) DEFAULT 0.000,
  `donor_opted_for_updates` tinyint(1) DEFAULT 1,
  `thank_you_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`donation_id`),
  UNIQUE KEY `donation_code` (`donation_code`),
  KEY `parent_donation_id` (`parent_donation_id`),
  KEY `idx_campaign_status` (`campaign_id`,`payment_status`),
  KEY `idx_donor_user` (`donor_user_id`,`payment_status`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_recurring` (`is_recurring`,`next_payment_date`),
  KEY `idx_corporate` (`is_corporate_donation`,`payment_status`),
  KEY `idx_donation_code` (`donation_code`),
  KEY `idx_amount_date` (`amount`,`created_at`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`campaign_id`) ON DELETE CASCADE,
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`donor_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `donations_ibfk_3` FOREIGN KEY (`parent_donation_id`) REFERENCES `donations` (`donation_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| donation_id | int(11) | NO | PRI |  | auto_increment |
| campaign_id | int(11) | NO | MUL |  |  |
| donor_user_id | int(11) | YES | MUL |  |  |
| donation_code | varchar(50) | NO | UNI |  |  |
| amount | decimal(12,2) | NO | MUL |  |  |
| currency | char(3) | YES |  | VND |  |
| donation_type | enum('one_time','monthly','yearly') | YES |  | one_time |  |
| donor_name | varchar(255) | YES |  |  |  |
| donor_email | varchar(255) | YES |  |  |  |
| donor_phone | varchar(20) | YES |  |  |  |
| is_anonymous | tinyint(1) | YES |  | 0 |  |
| payment_method | enum('bank_transfer','credit_card','e_wallet','cash','crypto') | NO |  |  |  |
| payment_gateway | varchar(50) | YES |  |  |  |
| payment_transaction_id | varchar(255) | YES |  |  |  |
| payment_reference | varchar(255) | YES |  |  |  |
| payment_status | enum('pending','processing','completed','failed','cancelled','refunded') | YES |  | pending |  |
| payment_date | timestamp | YES | MUL |  |  |
| payment_confirmation_date | timestamp | YES |  |  |  |
| is_recurring | tinyint(1) | YES | MUL | 0 |  |
| recurring_frequency | enum('monthly','quarterly','yearly') | YES |  |  |  |
| next_payment_date | date | YES |  |  |  |
| recurring_end_date | date | YES |  |  |  |
| parent_donation_id | int(11) | YES | MUL |  |  |
| is_corporate_donation | tinyint(1) | YES | MUL | 0 |  |
| corporate_matching_eligible | tinyint(1) | YES |  | 0 |  |
| matched_amount | decimal(12,2) | YES |  | 0.00 |  |
| employer_name | varchar(255) | YES |  |  |  |
| dedication_type | enum('none','in_honor','in_memory') | YES |  | none |  |
| dedication_name | varchar(255) | YES |  |  |  |
| donation_message | text | YES |  |  |  |
| public_comment | text | YES |  |  |  |
| tax_deductible | tinyint(1) | YES |  | 1 |  |
| receipt_requested | tinyint(1) | YES |  | 1 |  |
| receipt_sent | tinyint(1) | YES |  | 0 |  |
| receipt_sent_date | timestamp | YES |  |  |  |
| eco_points_earned | int(11) | YES |  | 0 |  |
| estimated_carbon_offset | decimal(10,3) | YES |  | 0.000 |  |
| donor_opted_for_updates | tinyint(1) | YES |  | 1 |  |
| thank_you_sent | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: environmental_data

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `environmental_data` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_id` int(11) NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `data_type` enum('air_quality','water_quality','noise_level','temperature','humidity') NOT NULL,
  `measurement_value` decimal(10,3) NOT NULL,
  `measurement_unit` varchar(20) NOT NULL,
  `quality_level` enum('excellent','good','moderate','poor','very_poor') DEFAULT NULL,
  `measurement_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`data_id`),
  KEY `idx_location_type` (`latitude`,`longitude`,`data_type`),
  KEY `idx_quality_tracking` (`quality_level`,`data_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| data_id | int(11) | NO | PRI |  | auto_increment |
| source_id | int(11) | NO |  |  |  |
| location_name | varchar(100) | YES |  |  |  |
| latitude | decimal(10,6) | YES | MUL |  |  |
| longitude | decimal(10,6) | YES |  |  |  |
| data_type | enum('air_quality','water_quality','noise_level','temperature','humidity') | NO |  |  |  |
| measurement_value | decimal(10,3) | NO |  |  |  |
| measurement_unit | varchar(20) | NO |  |  |  |
| quality_level | enum('excellent','good','moderate','poor','very_poor') | YES | MUL |  |  |
| measurement_timestamp | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| recorded_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: environmental_data_sources

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `environmental_data_sources` (
  `source_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_name` varchar(100) NOT NULL,
  `source_type` enum('api','sensor','manual','import') NOT NULL,
  `data_provider` varchar(100) DEFAULT NULL,
  `update_frequency` enum('realtime','hourly','daily','weekly') DEFAULT 'daily',
  `location_coverage` enum('global','country','region','city','local') DEFAULT 'local',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`source_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| source_id | int(11) | NO | PRI |  | auto_increment |
| source_name | varchar(100) | NO |  |  |  |
| source_type | enum('api','sensor','manual','import') | NO |  |  |  |
| data_provider | varchar(100) | YES |  |  |  |
| update_frequency | enum('realtime','hourly','daily','weekly') | YES |  | daily |  |
| location_coverage | enum('global','country','region','city','local') | YES |  | local |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: environmental_impact_summary

**Row Count:** 1

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| month | varchar(7) | YES |  |  |  |
| total_carbon_points | decimal(32,0) | YES |  |  |  |
| active_users | bigint(21) | NO |  | 0 |  |
| avg_carbon_per_user | decimal(14,4) | YES |  |  |  |

---

## Table: environmental_impact_summary_final

**Row Count:** 2

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| user_id | int(11) | NO |  | 0 |  |
| username | varchar(50) | NO |  |  |  |
| carbon_tracking_entries | bigint(21) | YES |  |  |  |
| total_carbon_tracked_kg | decimal(32,3) | YES |  |  |  |
| total_carbon_saved_kg | decimal(32,3) | YES |  |  |  |
| environmental_quizzes_completed | bigint(21) | YES |  |  |  |
| avg_environmental_quiz_score | decimal(22,9) | YES |  |  |  |

---

## Table: event_registrations

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_status` enum('registered','attended','cancelled','no_show') DEFAULT 'registered',
  `check_in_time` timestamp NULL DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `feedback_rating` int(11) DEFAULT 0,
  `feedback_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `unique_user_event` (`event_id`,`user_id`),
  KEY `idx_user_status` (`user_id`,`registration_status`),
  KEY `idx_event_checkin` (`event_id`,`check_in_time`),
  CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| registration_id | int(11) | NO | PRI |  | auto_increment |
| event_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | NO | MUL |  |  |
| registration_status | enum('registered','attended','cancelled','no_show') | YES |  | registered |  |
| check_in_time | timestamp | YES |  |  |  |
| points_earned | int(11) | YES |  | 0 |  |
| feedback_rating | int(11) | YES |  | 0 |  |
| feedback_comment | text | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: events

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('workshop','cleanup','conference','webinar','volunteer') NOT NULL,
  `event_mode` enum('online','offline','hybrid') DEFAULT 'offline',
  `organizer_id` int(11) NOT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `venue_address` text DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `max_participants` int(11) DEFAULT 100,
  `current_participants` int(11) DEFAULT 0,
  `registration_fee` decimal(10,2) DEFAULT 0.00,
  `points_reward` int(11) DEFAULT 10,
  `status` enum('draft','published','cancelled','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| event_id | int(11) | NO | PRI |  | auto_increment |
| title | varchar(255) | NO |  |  |  |
| slug | varchar(255) | NO | UNI |  |  |
| description | text | YES |  |  |  |
| event_type | enum('workshop','cleanup','conference','webinar','volunteer') | NO |  |  |  |
| event_mode | enum('online','offline','hybrid') | YES |  | offline |  |
| organizer_id | int(11) | NO |  |  |  |
| venue_name | varchar(255) | YES |  |  |  |
| venue_address | text | YES |  |  |  |
| latitude | decimal(10,6) | YES |  |  |  |
| longitude | decimal(10,6) | YES |  |  |  |
| start_date | datetime | NO |  |  |  |
| end_date | datetime | NO |  |  |  |
| max_participants | int(11) | YES |  | 100 |  |
| current_participants | int(11) | YES |  | 0 |  |
| registration_fee | decimal(10,2) | YES |  | 0.00 |  |
| points_reward | int(11) | YES |  | 10 |  |
| status | enum('draft','published','cancelled','completed') | YES |  | draft |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: exchange_categories

**Row Count:** 41

### CREATE TABLE Statement

```sql
CREATE TABLE `exchange_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `eco_impact_score` int(11) DEFAULT 50,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_slug` (`category_slug`),
  KEY `parent_category_id` (`parent_category_id`),
  CONSTRAINT `exchange_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `exchange_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| category_id | int(11) | NO | PRI |  | auto_increment |
| category_name | varchar(100) | NO |  |  |  |
| category_slug | varchar(100) | NO | UNI |  |  |
| parent_category_id | int(11) | YES | MUL |  |  |
| description | text | YES |  |  |  |
| eco_impact_score | int(11) | YES |  | 50 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: exchange_favorites

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `exchange_favorites` (
  `favorite_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `unique_user_post` (`user_id`,`post_id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `exchange_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_favorites_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `exchange_posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| favorite_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| post_id | int(11) | NO | MUL |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: exchange_matches

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `exchange_matches` (
  `match_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id_1` int(11) NOT NULL,
  `post_id_2` int(11) NOT NULL,
  `compatibility_score` decimal(5,2) NOT NULL,
  `distance_km` decimal(8,3) DEFAULT NULL,
  `match_reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`match_reasons`)),
  `status` enum('suggested','viewed','contacted','completed') DEFAULT 'suggested',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`match_id`),
  KEY `post_id_1` (`post_id_1`),
  KEY `post_id_2` (`post_id_2`),
  CONSTRAINT `exchange_matches_ibfk_1` FOREIGN KEY (`post_id_1`) REFERENCES `exchange_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_matches_ibfk_2` FOREIGN KEY (`post_id_2`) REFERENCES `exchange_posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| match_id | int(11) | NO | PRI |  | auto_increment |
| post_id_1 | int(11) | NO | MUL |  |  |
| post_id_2 | int(11) | NO | MUL |  |  |
| compatibility_score | decimal(5,2) | NO |  |  |  |
| distance_km | decimal(8,3) | YES |  |  |  |
| match_reasons | longtext | YES |  |  |  |
| status | enum('suggested','viewed','contacted','completed') | YES |  | suggested |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: exchange_posts

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `exchange_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `exchange_type` enum('give_away','exchange','lending','selling_cheap') NOT NULL,
  `item_condition` enum('new','like_new','good','fair','poor') NOT NULL,
  `estimated_value` decimal(10,2) DEFAULT NULL,
  `location_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`location_data`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `post_status` enum('draft','active','reserved','completed','expired') DEFAULT 'active',
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `exchange_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `exchange_categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| post_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| category_id | int(11) | NO | MUL |  |  |
| title | varchar(255) | NO |  |  |  |
| description | text | NO |  |  |  |
| exchange_type | enum('give_away','exchange','lending','selling_cheap') | NO |  |  |  |
| item_condition | enum('new','like_new','good','fair','poor') | NO |  |  |  |
| estimated_value | decimal(10,2) | YES |  |  |  |
| location_data | longtext | YES |  |  |  |
| images | longtext | YES |  |  |  |
| post_status | enum('draft','active','reserved','completed','expired') | YES |  | active |  |
| view_count | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: exchange_requests

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `exchange_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `offered_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`offered_items`)),
  `request_status` enum('pending','accepted','rejected','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `post_id` (`post_id`),
  KEY `requester_id` (`requester_id`),
  CONSTRAINT `exchange_requests_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `exchange_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| request_id | int(11) | NO | PRI |  | auto_increment |
| post_id | int(11) | NO | MUL |  |  |
| requester_id | int(11) | NO | MUL |  |  |
| message | text | YES |  |  |  |
| offered_items | longtext | YES |  |  |  |
| request_status | enum('pending','accepted','rejected','completed') | YES |  | pending |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: exchange_saved_searches

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `exchange_saved_searches` (
  `search_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `search_name` varchar(100) NOT NULL,
  `search_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`search_criteria`)),
  `notification_enabled` tinyint(1) DEFAULT 1,
  `last_notified` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`search_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `exchange_saved_searches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| search_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| search_name | varchar(100) | NO |  |  |  |
| search_criteria | longtext | YES |  |  |  |
| notification_enabled | tinyint(1) | YES |  | 1 |  |
| last_notified | timestamp | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: forum_posts

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `forum_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `parent_post_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `post_type` enum('post','reply','quote','solution') DEFAULT 'post',
  `like_count` int(11) DEFAULT 0,
  `is_best_answer` tinyint(1) DEFAULT 0,
  `moderation_status` enum('approved','pending','flagged','hidden') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`),
  KEY `idx_topic_moderation` (`topic_id`,`moderation_status`,`created_at`),
  KEY `idx_author_posts` (`author_id`,`created_at`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| post_id | int(11) | NO | PRI |  | auto_increment |
| topic_id | int(11) | NO | MUL |  |  |
| parent_post_id | int(11) | YES |  |  |  |
| author_id | int(11) | NO | MUL |  |  |
| content | text | NO | MUL |  |  |
| post_type | enum('post','reply','quote','solution') | YES |  | post |  |
| like_count | int(11) | YES |  | 0 |  |
| is_best_answer | tinyint(1) | YES |  | 0 |  |
| moderation_status | enum('approved','pending','flagged','hidden') | YES |  | approved |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: forum_topics

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `forum_topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `forum_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `topic_type` enum('discussion','question','announcement','poll','guide') DEFAULT 'discussion',
  `status` enum('open','locked','closed','resolved','featured') DEFAULT 'open',
  `view_count` int(11) DEFAULT 0,
  `reply_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `is_answered` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`topic_id`),
  KEY `idx_forum_status` (`forum_id`,`status`,`updated_at`),
  KEY `idx_author_topics` (`author_id`,`created_at`),
  FULLTEXT KEY `title` (`title`,`content`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| topic_id | int(11) | NO | PRI |  | auto_increment |
| forum_id | int(11) | NO | MUL |  |  |
| title | varchar(255) | NO | MUL |  |  |
| content | text | NO |  |  |  |
| author_id | int(11) | NO | MUL |  |  |
| topic_type | enum('discussion','question','announcement','poll','guide') | YES |  | discussion |  |
| status | enum('open','locked','closed','resolved','featured') | YES |  | open |  |
| view_count | int(11) | YES |  | 0 |  |
| reply_count | int(11) | YES |  | 0 |  |
| like_count | int(11) | YES |  | 0 |  |
| is_answered | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: forums

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `forums` (
  `forum_id` int(11) NOT NULL AUTO_INCREMENT,
  `forum_name` varchar(100) NOT NULL,
  `forum_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `forum_type` enum('general','environmental','recycling','energy','transport','marketplace','help') DEFAULT 'general',
  `topic_count` int(11) DEFAULT 0,
  `post_count` int(11) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`forum_id`),
  UNIQUE KEY `forum_slug` (`forum_slug`),
  KEY `idx_type_active` (`forum_type`,`is_active`),
  KEY `idx_featured` (`is_featured`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| forum_id | int(11) | NO | PRI |  | auto_increment |
| forum_name | varchar(100) | NO |  |  |  |
| forum_slug | varchar(100) | NO | UNI |  |  |
| description | text | YES |  |  |  |
| forum_type | enum('general','environmental','recycling','energy','transport','marketplace','help') | YES | MUL | general |  |
| topic_count | int(11) | YES |  | 0 |  |
| post_count | int(11) | YES |  | 0 |  |
| sort_order | int(11) | YES |  | 0 |  |
| is_featured | tinyint(1) | YES | MUL | 0 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: index_usage_analysis

**Row Count:** 848

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| TABLE_NAME | varchar(64) | NO |  |  |  |
| INDEX_NAME | varchar(64) | NO |  |  |  |
| NON_UNIQUE | bigint(1) | NO |  |  |  |
| CARDINALITY | bigint(21) | YES |  |  |  |
| selectivity | varchar(29) | YES |  |  |  |

---

## Table: leaderboard_entries_enhanced

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `leaderboard_entries_enhanced` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `leaderboard_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_rank` int(11) NOT NULL,
  `previous_rank` int(11) DEFAULT NULL,
  `rank_change` int(11) DEFAULT 0,
  `current_score` decimal(15,4) NOT NULL,
  `previous_score` decimal(15,4) DEFAULT 0.0000,
  `score_change` decimal(15,4) DEFAULT 0.0000,
  `carbon_saved_kg` decimal(10,2) DEFAULT 0.00,
  `waste_classified_kg` decimal(10,2) DEFAULT 0.00,
  `environmental_actions` int(11) DEFAULT 0,
  `streak_days` int(11) DEFAULT 0,
  `achievement_count` int(11) DEFAULT 0,
  `last_activity_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `unique_leaderboard_user` (`leaderboard_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_leaderboard_rank` (`leaderboard_id`,`current_rank`),
  KEY `idx_leaderboard_score` (`leaderboard_id`,`current_score`),
  CONSTRAINT `leaderboard_entries_enhanced_ibfk_1` FOREIGN KEY (`leaderboard_id`) REFERENCES `leaderboards_enhanced` (`leaderboard_id`) ON DELETE CASCADE,
  CONSTRAINT `leaderboard_entries_enhanced_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| entry_id | int(11) | NO | PRI |  | auto_increment |
| leaderboard_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | NO | MUL |  |  |
| current_rank | int(11) | NO |  |  |  |
| previous_rank | int(11) | YES |  |  |  |
| rank_change | int(11) | YES |  | 0 |  |
| current_score | decimal(15,4) | NO |  |  |  |
| previous_score | decimal(15,4) | YES |  | 0.0000 |  |
| score_change | decimal(15,4) | YES |  | 0.0000 |  |
| carbon_saved_kg | decimal(10,2) | YES |  | 0.00 |  |
| waste_classified_kg | decimal(10,2) | YES |  | 0.00 |  |
| environmental_actions | int(11) | YES |  | 0 |  |
| streak_days | int(11) | YES |  | 0 |  |
| achievement_count | int(11) | YES |  | 0 |  |
| last_activity_at | timestamp | NO |  | current_timestamp() |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: leaderboards_enhanced

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `leaderboards_enhanced` (
  `leaderboard_id` int(11) NOT NULL AUTO_INCREMENT,
  `leaderboard_name` varchar(150) NOT NULL,
  `leaderboard_slug` varchar(150) NOT NULL,
  `display_name_vi` varchar(200) NOT NULL,
  `display_name_en` varchar(200) NOT NULL,
  `description_vi` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `leaderboard_type` enum('global','regional','category','seasonal','event') DEFAULT 'global',
  `ranking_metric` enum('points','achievements','carbon_saved','waste_classified','social_impact') NOT NULL,
  `period_type` enum('all_time','yearly','monthly','weekly','daily') DEFAULT 'all_time',
  `period_start` timestamp NULL DEFAULT NULL,
  `period_end` timestamp NULL DEFAULT NULL,
  `max_entries_displayed` int(11) DEFAULT 100,
  `is_public` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `environmental_focus` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`leaderboard_id`),
  UNIQUE KEY `leaderboard_slug` (`leaderboard_slug`),
  KEY `idx_type_period` (`leaderboard_type`,`period_type`),
  KEY `idx_active` (`is_active`,`is_public`),
  FULLTEXT KEY `leaderboard_name` (`leaderboard_name`,`display_name_vi`,`display_name_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| leaderboard_id | int(11) | NO | PRI |  | auto_increment |
| leaderboard_name | varchar(150) | NO | MUL |  |  |
| leaderboard_slug | varchar(150) | NO | UNI |  |  |
| display_name_vi | varchar(200) | NO |  |  |  |
| display_name_en | varchar(200) | NO |  |  |  |
| description_vi | text | YES |  |  |  |
| description_en | text | YES |  |  |  |
| leaderboard_type | enum('global','regional','category','seasonal','event') | YES | MUL | global |  |
| ranking_metric | enum('points','achievements','carbon_saved','waste_classified','social_impact') | NO |  |  |  |
| period_type | enum('all_time','yearly','monthly','weekly','daily') | YES |  | all_time |  |
| period_start | timestamp | YES |  |  |  |
| period_end | timestamp | YES |  |  |  |
| max_entries_displayed | int(11) | YES |  | 100 |  |
| is_public | tinyint(1) | YES |  | 1 |  |
| is_active | tinyint(1) | YES | MUL | 1 |  |
| environmental_focus | varchar(50) | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: marketplace_insights_final

**Row Count:** 0

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| product_id | int(11) | NO |  | 0 |  |
| product_name | varchar(255) | NO |  |  |  |
| price | decimal(10,2) | NO |  |  |  |
| eco_score | int(11) | YES |  | 50 |  |
| category_id | int(11) | YES |  |  |  |
| seller_id | int(11) | NO |  |  |  |
| stock_quantity | int(11) | YES |  | 0 |  |
| product_status | enum('draft','active','out_of_stock','discontinued') | YES |  | draft |  |
| product_launch_date | timestamp | NO |  | current_timestamp() |  |
| total_orders | bigint(21) | YES |  |  |  |
| total_revenue | decimal(42,2) | YES |  |  |  |
| avg_order_value | decimal(24,6) | YES |  |  |  |
| unique_customers | bigint(21) | YES |  |  |  |
| total_reviews | bigint(21) | YES |  |  |  |
| avg_rating | decimal(14,4) | YES |  |  |  |
| sales_performance | varchar(11) | YES |  |  |  |
| days_since_launch | int(7) | YES |  |  |  |
| green_product_score | decimal(25,4) | YES |  |  |  |

---

## Table: message_delivery_status

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `message_delivery_status` (
  `delivery_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `delivery_method` enum('websocket','push_notification','email','sms') NOT NULL,
  `delivery_status` enum('pending','sent','delivered','read','failed') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `delivery_channel` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`delivery_id`),
  KEY `idx_message_delivery` (`message_id`,`delivery_status`),
  KEY `idx_recipient_delivery` (`recipient_id`,`delivery_status`,`created_at`),
  KEY `idx_real_time_tracking` (`delivery_method`,`delivery_status`,`sent_at`),
  CONSTRAINT `message_delivery_status_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `message_delivery_status_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| delivery_id | int(11) | NO | PRI |  | auto_increment |
| message_id | int(11) | NO | MUL |  |  |
| recipient_id | int(11) | NO | MUL |  |  |
| delivery_method | enum('websocket','push_notification','email','sms') | NO | MUL |  |  |
| delivery_status | enum('pending','sent','delivered','read','failed') | YES |  | pending |  |
| sent_at | timestamp | YES |  |  |  |
| delivered_at | timestamp | YES |  |  |  |
| read_at | timestamp | YES |  |  |  |
| device_id | varchar(255) | YES |  |  |  |
| delivery_channel | varchar(50) | YES |  |  |  |
| error_message | text | YES |  |  |  |
| retry_count | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: message_reactions

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `message_reactions` (
  `reaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('like','love','celebrate','support','eco_friendly','helpful','inspiring','thumbs_up','thumbs_down') NOT NULL,
  `reaction_emoji` varchar(10) DEFAULT NULL,
  `environmental_appreciation` tinyint(1) DEFAULT 0,
  `green_impact_recognition` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reaction_id`),
  UNIQUE KEY `unique_user_message_reaction` (`message_id`,`user_id`,`reaction_type`),
  KEY `user_id` (`user_id`),
  KEY `idx_message_reactions` (`message_id`,`reaction_type`),
  KEY `idx_environmental_reactions` (`environmental_appreciation`,`green_impact_recognition`),
  CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| reaction_id | int(11) | NO | PRI |  | auto_increment |
| message_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | NO | MUL |  |  |
| reaction_type | enum('like','love','celebrate','support','eco_friendly','helpful','inspiring','thumbs_up','thumbs_down') | NO |  |  |  |
| reaction_emoji | varchar(10) | YES |  |  |  |
| environmental_appreciation | tinyint(1) | YES | MUL | 0 |  |
| green_impact_recognition | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: messages

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('user','system','bot','ai_assistant') DEFAULT 'user',
  `message_type` enum('text','image','file','link','location','poll','environmental_data','carbon_calculation','waste_report') DEFAULT 'text',
  `content` longtext NOT NULL,
  `content_format` enum('plain','markdown','html') DEFAULT 'plain',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `media_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_urls`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `environmental_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`environmental_data`)),
  `carbon_impact_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`carbon_impact_data`)),
  `green_action_suggestion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`green_action_suggestion`)),
  `parent_message_id` int(11) DEFAULT NULL,
  `thread_level` int(11) DEFAULT 0,
  `thread_count` int(11) DEFAULT 0,
  `status` enum('sent','delivered','read','edited','deleted') DEFAULT 'sent',
  `is_edited` tinyint(1) DEFAULT 0,
  `edit_count` int(11) DEFAULT 0,
  `last_edited_at` timestamp NULL DEFAULT NULL,
  `reaction_count` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`reaction_count`)),
  `like_count` int(11) DEFAULT 0,
  `reply_count` int(11) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_announcement` tinyint(1) DEFAULT 0,
  `is_urgent` tinyint(1) DEFAULT 0,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `ai_generated` tinyint(1) DEFAULT 0,
  `environmental_impact_score` int(11) DEFAULT 0,
  `sustainability_tips_count` int(11) DEFAULT 0,
  `delivered_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`delivered_to`)),
  `read_by` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`read_by`)),
  `delivery_status` enum('pending','delivered','failed') DEFAULT 'pending',
  `is_flagged` tinyint(1) DEFAULT 0,
  `moderation_status` enum('approved','pending','rejected','auto_approved') DEFAULT 'auto_approved',
  `flagged_reason` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `engagement_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_conversation_messages` (`conversation_id`,`created_at`),
  KEY `idx_sender_messages` (`sender_id`,`created_at`),
  KEY `idx_message_threading` (`parent_message_id`,`thread_level`,`created_at`),
  KEY `idx_environmental_content` (`environmental_impact_score`,`ai_generated`),
  KEY `idx_message_status` (`status`,`moderation_status`),
  KEY `idx_pinned_announcements` (`is_pinned`,`is_announcement`,`created_at`),
  FULLTEXT KEY `content` (`content`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`message_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| message_id | int(11) | NO | PRI |  | auto_increment |
| conversation_id | int(11) | NO | MUL |  |  |
| sender_id | int(11) | NO | MUL |  |  |
| sender_type | enum('user','system','bot','ai_assistant') | YES |  | user |  |
| message_type | enum('text','image','file','link','location','poll','environmental_data','carbon_calculation','waste_report') | YES |  | text |  |
| content | longtext | NO | MUL |  |  |
| content_format | enum('plain','markdown','html') | YES |  | plain |  |
| attachments | longtext | YES |  |  |  |
| media_urls | longtext | YES |  |  |  |
| metadata | longtext | YES |  |  |  |
| environmental_data | longtext | YES |  |  |  |
| carbon_impact_data | longtext | YES |  |  |  |
| green_action_suggestion | longtext | YES |  |  |  |
| parent_message_id | int(11) | YES | MUL |  |  |
| thread_level | int(11) | YES |  | 0 |  |
| thread_count | int(11) | YES |  | 0 |  |
| status | enum('sent','delivered','read','edited','deleted') | YES | MUL | sent |  |
| is_edited | tinyint(1) | YES |  | 0 |  |
| edit_count | int(11) | YES |  | 0 |  |
| last_edited_at | timestamp | YES |  |  |  |
| reaction_count | longtext | YES |  | '{}' |  |
| like_count | int(11) | YES |  | 0 |  |
| reply_count | int(11) | YES |  | 0 |  |
| is_pinned | tinyint(1) | YES | MUL | 0 |  |
| is_announcement | tinyint(1) | YES |  | 0 |  |
| is_urgent | tinyint(1) | YES |  | 0 |  |
| is_encrypted | tinyint(1) | YES |  | 0 |  |
| ai_generated | tinyint(1) | YES |  | 0 |  |
| environmental_impact_score | int(11) | YES | MUL | 0 |  |
| sustainability_tips_count | int(11) | YES |  | 0 |  |
| delivered_to | longtext | YES |  | '[]' |  |
| read_by | longtext | YES |  | '[]' |  |
| delivery_status | enum('pending','delivered','failed') | YES |  | pending |  |
| is_flagged | tinyint(1) | YES |  | 0 |  |
| moderation_status | enum('approved','pending','rejected','auto_approved') | YES |  | auto_approved |  |
| flagged_reason | varchar(255) | YES |  |  |  |
| view_count | int(11) | YES |  | 0 |  |
| engagement_score | decimal(5,2) | YES |  | 0.00 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: moderation_analytics

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `moderation_analytics` (
  `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
  `analytics_period` enum('hourly','daily','weekly','monthly','quarterly','yearly') NOT NULL,
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `total_reports_received` int(11) DEFAULT 0,
  `reports_by_category` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reports_by_category`)),
  `reports_by_severity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reports_by_severity`)),
  `reports_by_content_type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reports_by_content_type`)),
  `reports_resolved` int(11) DEFAULT 0,
  `reports_pending` int(11) DEFAULT 0,
  `reports_escalated` int(11) DEFAULT 0,
  `average_resolution_time_hours` decimal(8,2) DEFAULT NULL,
  `median_resolution_time_hours` decimal(8,2) DEFAULT NULL,
  `actions_taken` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`actions_taken`)),
  `content_removed_count` int(11) DEFAULT 0,
  `users_warned_count` int(11) DEFAULT 0,
  `users_suspended_count` int(11) DEFAULT 0,
  `users_banned_count` int(11) DEFAULT 0,
  `total_moderators_active` int(11) DEFAULT 0,
  `average_moderator_workload` decimal(8,2) DEFAULT NULL,
  `moderator_accuracy_avg` decimal(5,4) DEFAULT NULL,
  `community_moderators_active` int(11) DEFAULT 0,
  `ai_accuracy_rate` decimal(5,4) DEFAULT NULL,
  `automated_resolutions_count` int(11) DEFAULT 0,
  `false_positive_rate` decimal(5,4) DEFAULT NULL,
  `human_override_rate` decimal(5,4) DEFAULT NULL,
  `appeals_submitted` int(11) DEFAULT 0,
  `appeals_approved` int(11) DEFAULT 0,
  `appeals_denied` int(11) DEFAULT 0,
  `appeal_success_rate` decimal(5,4) DEFAULT NULL,
  `community_trust_score` decimal(5,2) DEFAULT NULL,
  `user_satisfaction_score` decimal(5,2) DEFAULT NULL,
  `platform_safety_index` decimal(5,2) DEFAULT NULL,
  `environmental_integrity_score` decimal(5,2) DEFAULT NULL,
  `report_volume_trend` enum('increasing','decreasing','stable','volatile') DEFAULT 'stable',
  `resolution_efficiency_trend` enum('improving','declining','stable') DEFAULT 'stable',
  `quality_trend` enum('improving','declining','stable') DEFAULT 'stable',
  `greenwashing_reports_count` int(11) DEFAULT 0,
  `environmental_misinformation_count` int(11) DEFAULT 0,
  `sustainability_claims_verified` int(11) DEFAULT 0,
  `eco_fraud_prevention_actions` int(11) DEFAULT 0,
  `top_violation_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_violation_types`)),
  `improvement_recommendations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`improvement_recommendations`)),
  `resource_allocation_suggestions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resource_allocation_suggestions`)),
  `policy_update_recommendations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`policy_update_recommendations`)),
  `moderation_system_health_score` decimal(5,2) DEFAULT NULL,
  `escalation_backlog_size` int(11) DEFAULT 0,
  `critical_issues_pending` int(11) DEFAULT 0,
  `system_response_time_avg_ms` int(11) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`analytics_id`),
  UNIQUE KEY `unique_period` (`analytics_period`,`period_start_date`,`period_end_date`),
  KEY `idx_period_analytics` (`analytics_period`,`period_start_date`,`period_end_date`),
  KEY `idx_metrics_performance` (`average_resolution_time_hours`,`moderator_accuracy_avg`),
  KEY `idx_trend_analysis` (`report_volume_trend`,`resolution_efficiency_trend`),
  KEY `idx_system_health` (`moderation_system_health_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| analytics_id | int(11) | NO | PRI |  | auto_increment |
| analytics_period | enum('hourly','daily','weekly','monthly','quarterly','yearly') | NO | MUL |  |  |
| period_start_date | date | NO |  |  |  |
| period_end_date | date | NO |  |  |  |
| total_reports_received | int(11) | YES |  | 0 |  |
| reports_by_category | longtext | YES |  |  |  |
| reports_by_severity | longtext | YES |  |  |  |
| reports_by_content_type | longtext | YES |  |  |  |
| reports_resolved | int(11) | YES |  | 0 |  |
| reports_pending | int(11) | YES |  | 0 |  |
| reports_escalated | int(11) | YES |  | 0 |  |
| average_resolution_time_hours | decimal(8,2) | YES | MUL |  |  |
| median_resolution_time_hours | decimal(8,2) | YES |  |  |  |
| actions_taken | longtext | YES |  |  |  |
| content_removed_count | int(11) | YES |  | 0 |  |
| users_warned_count | int(11) | YES |  | 0 |  |
| users_suspended_count | int(11) | YES |  | 0 |  |
| users_banned_count | int(11) | YES |  | 0 |  |
| total_moderators_active | int(11) | YES |  | 0 |  |
| average_moderator_workload | decimal(8,2) | YES |  |  |  |
| moderator_accuracy_avg | decimal(5,4) | YES |  |  |  |
| community_moderators_active | int(11) | YES |  | 0 |  |
| ai_accuracy_rate | decimal(5,4) | YES |  |  |  |
| automated_resolutions_count | int(11) | YES |  | 0 |  |
| false_positive_rate | decimal(5,4) | YES |  |  |  |
| human_override_rate | decimal(5,4) | YES |  |  |  |
| appeals_submitted | int(11) | YES |  | 0 |  |
| appeals_approved | int(11) | YES |  | 0 |  |
| appeals_denied | int(11) | YES |  | 0 |  |
| appeal_success_rate | decimal(5,4) | YES |  |  |  |
| community_trust_score | decimal(5,2) | YES |  |  |  |
| user_satisfaction_score | decimal(5,2) | YES |  |  |  |
| platform_safety_index | decimal(5,2) | YES |  |  |  |
| environmental_integrity_score | decimal(5,2) | YES |  |  |  |
| report_volume_trend | enum('increasing','decreasing','stable','volatile') | YES | MUL | stable |  |
| resolution_efficiency_trend | enum('improving','declining','stable') | YES |  | stable |  |
| quality_trend | enum('improving','declining','stable') | YES |  | stable |  |
| greenwashing_reports_count | int(11) | YES |  | 0 |  |
| environmental_misinformation_count | int(11) | YES |  | 0 |  |
| sustainability_claims_verified | int(11) | YES |  | 0 |  |
| eco_fraud_prevention_actions | int(11) | YES |  | 0 |  |
| top_violation_types | longtext | YES |  |  |  |
| improvement_recommendations | longtext | YES |  |  |  |
| resource_allocation_suggestions | longtext | YES |  |  |  |
| policy_update_recommendations | longtext | YES |  |  |  |
| moderation_system_health_score | decimal(5,2) | YES | MUL |  |  |
| escalation_backlog_size | int(11) | YES |  | 0 |  |
| critical_issues_pending | int(11) | YES |  | 0 |  |
| system_response_time_avg_ms | int(11) | YES |  |  |  |
| calculated_at | timestamp | NO |  | current_timestamp() |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: moderation_appeals

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `moderation_appeals` (
  `appeal_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) DEFAULT NULL,
  `moderation_log_id` int(11) DEFAULT NULL,
  `appealing_user_id` int(11) NOT NULL,
  `appeal_type` enum('content_removal','account_suspension','account_ban','content_flag','warning_issued','restriction_applied','review_decision','automated_action') NOT NULL,
  `appeal_category` enum('false_positive','context_misunderstood','policy_disagreement','technical_error','bias_claim','new_evidence','changed_circumstances') NOT NULL,
  `appeal_title` varchar(255) NOT NULL,
  `appeal_description` text NOT NULL,
  `new_evidence_provided` text DEFAULT NULL,
  `supporting_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_documents`)),
  `context_explanation` text DEFAULT NULL,
  `original_action` varchar(200) NOT NULL,
  `original_reason` text NOT NULL,
  `original_moderator_id` int(11) DEFAULT NULL,
  `original_decision_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `appeal_status` enum('submitted','under_review','additional_info_requested','approved','partially_approved','denied','withdrawn') DEFAULT 'submitted',
  `priority_level` enum('low','normal','high','urgent') DEFAULT 'normal',
  `assigned_reviewer_id` int(11) DEFAULT NULL,
  `reviewer_type` enum('moderator','admin','panel','community','ai_assisted') DEFAULT 'moderator',
  `assigned_at` timestamp NULL DEFAULT NULL,
  `review_method` enum('single_reviewer','panel_review','community_vote','ai_assisted') DEFAULT 'single_reviewer',
  `requires_expert_review` tinyint(1) DEFAULT 0,
  `expert_reviewer_id` int(11) DEFAULT NULL,
  `community_voting_enabled` tinyint(1) DEFAULT 0,
  `community_votes_support` int(11) DEFAULT 0,
  `community_votes_oppose` int(11) DEFAULT 0,
  `community_comments_count` int(11) DEFAULT 0,
  `final_decision` enum('appeal_approved','appeal_denied','partial_approval','decision_modified','original_maintained','escalated_further','withdrawn_by_user') DEFAULT NULL,
  `resolution_explanation` text DEFAULT NULL,
  `corrective_action_taken` text DEFAULT NULL,
  `policy_clarification_provided` text DEFAULT NULL,
  `precedent_set` tinyint(1) DEFAULT 0,
  `policy_update_triggered` tinyint(1) DEFAULT 0,
  `training_case_added` tinyint(1) DEFAULT 0,
  `moderator_feedback_provided` tinyint(1) DEFAULT 0,
  `submission_deadline` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `review_deadline` timestamp NULL DEFAULT NULL,
  `decision_deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`appeal_id`),
  KEY `idx_appealing_user` (`appealing_user_id`,`appeal_status`),
  KEY `idx_report_appeal` (`report_id`,`appeal_status`),
  KEY `idx_status_priority` (`appeal_status`,`priority_level`,`created_at`),
  KEY `idx_assigned_reviewer` (`assigned_reviewer_id`,`appeal_status`),
  KEY `idx_deadlines` (`review_deadline`,`decision_deadline`),
  KEY `idx_appeal_type` (`appeal_type`,`appeal_category`),
  KEY `moderation_log_id` (`moderation_log_id`),
  KEY `original_moderator_id` (`original_moderator_id`),
  KEY `expert_reviewer_id` (`expert_reviewer_id`),
  CONSTRAINT `moderation_appeals_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE SET NULL,
  CONSTRAINT `moderation_appeals_ibfk_2` FOREIGN KEY (`moderation_log_id`) REFERENCES `moderation_logs` (`log_id`) ON DELETE SET NULL,
  CONSTRAINT `moderation_appeals_ibfk_3` FOREIGN KEY (`appealing_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `moderation_appeals_ibfk_4` FOREIGN KEY (`assigned_reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `moderation_appeals_ibfk_5` FOREIGN KEY (`original_moderator_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `moderation_appeals_ibfk_6` FOREIGN KEY (`expert_reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| appeal_id | int(11) | NO | PRI |  | auto_increment |
| report_id | int(11) | YES | MUL |  |  |
| moderation_log_id | int(11) | YES | MUL |  |  |
| appealing_user_id | int(11) | NO | MUL |  |  |
| appeal_type | enum('content_removal','account_suspension','account_ban','content_flag','warning_issued','restriction_applied','review_decision','automated_action') | NO | MUL |  |  |
| appeal_category | enum('false_positive','context_misunderstood','policy_disagreement','technical_error','bias_claim','new_evidence','changed_circumstances') | NO |  |  |  |
| appeal_title | varchar(255) | NO |  |  |  |
| appeal_description | text | NO |  |  |  |
| new_evidence_provided | text | YES |  |  |  |
| supporting_documents | longtext | YES |  |  |  |
| context_explanation | text | YES |  |  |  |
| original_action | varchar(200) | NO |  |  |  |
| original_reason | text | NO |  |  |  |
| original_moderator_id | int(11) | YES | MUL |  |  |
| original_decision_date | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| appeal_status | enum('submitted','under_review','additional_info_requested','approved','partially_approved','denied','withdrawn') | YES | MUL | submitted |  |
| priority_level | enum('low','normal','high','urgent') | YES |  | normal |  |
| assigned_reviewer_id | int(11) | YES | MUL |  |  |
| reviewer_type | enum('moderator','admin','panel','community','ai_assisted') | YES |  | moderator |  |
| assigned_at | timestamp | YES |  |  |  |
| review_method | enum('single_reviewer','panel_review','community_vote','ai_assisted') | YES |  | single_reviewer |  |
| requires_expert_review | tinyint(1) | YES |  | 0 |  |
| expert_reviewer_id | int(11) | YES | MUL |  |  |
| community_voting_enabled | tinyint(1) | YES |  | 0 |  |
| community_votes_support | int(11) | YES |  | 0 |  |
| community_votes_oppose | int(11) | YES |  | 0 |  |
| community_comments_count | int(11) | YES |  | 0 |  |
| final_decision | enum('appeal_approved','appeal_denied','partial_approval','decision_modified','original_maintained','escalated_further','withdrawn_by_user') | YES |  |  |  |
| resolution_explanation | text | YES |  |  |  |
| corrective_action_taken | text | YES |  |  |  |
| policy_clarification_provided | text | YES |  |  |  |
| precedent_set | tinyint(1) | YES |  | 0 |  |
| policy_update_triggered | tinyint(1) | YES |  | 0 |  |
| training_case_added | tinyint(1) | YES |  | 0 |  |
| moderator_feedback_provided | tinyint(1) | YES |  | 0 |  |
| submission_deadline | timestamp | NO |  | 0000-00-00 00:00:00 |  |
| review_deadline | timestamp | YES | MUL |  |  |
| decision_deadline | timestamp | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| reviewed_at | timestamp | YES |  |  |  |
| resolved_at | timestamp | YES |  |  |  |

---

## Table: moderation_logs

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `moderation_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('content_review','content_remove','content_restore','content_edit','user_warn','user_suspend','user_ban','user_unban','account_restrict','report_assign','report_resolve','report_escalate','report_dismiss','appeal_process','policy_update','automated_action','manual_override','community_decision','ai_flag','bulk_action','emergency_action') NOT NULL,
  `action_category` enum('content_moderation','user_management','report_handling','appeal_processing','policy_enforcement','automated_moderation','community_moderation','admin_action') NOT NULL,
  `target_type` enum('user','content','report','appeal','comment','review','post','message','profile','system','bulk_targets') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `affected_content_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`affected_content_ids`)),
  `moderator_id` int(11) NOT NULL,
  `moderator_type` enum('admin','moderator','community_mod','ai_system','automated') NOT NULL,
  `moderator_session_id` varchar(255) DEFAULT NULL,
  `action_reason` text NOT NULL,
  `action_description` text DEFAULT NULL,
  `policy_violated` varchar(200) DEFAULT NULL,
  `evidence_referenced` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_referenced`)),
  `previous_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_state`)),
  `new_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_state`)),
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_fields`)),
  `decision_confidence` decimal(5,2) DEFAULT NULL,
  `decision_factors` text DEFAULT NULL,
  `community_input_considered` tinyint(1) DEFAULT 0,
  `ai_recommendation_followed` tinyint(1) DEFAULT NULL,
  `affected_users_count` int(11) DEFAULT 0,
  `content_items_affected` int(11) DEFAULT 0,
  `severity_impact` enum('minimal','moderate','significant','major','critical') DEFAULT 'minimal',
  `is_reversible` tinyint(1) DEFAULT 1,
  `appeal_eligible` tinyint(1) DEFAULT 1,
  `appeal_deadline` timestamp NULL DEFAULT NULL,
  `review_required` tinyint(1) DEFAULT 0,
  `review_by_date` date DEFAULT NULL,
  `is_automated` tinyint(1) DEFAULT 0,
  `automation_rule_id` int(11) DEFAULT NULL,
  `automation_confidence` decimal(5,4) DEFAULT NULL,
  `manual_review_triggered` tinyint(1) DEFAULT 0,
  `processing_time_seconds` int(11) DEFAULT NULL,
  `escalation_triggered` tinyint(1) DEFAULT 0,
  `follow_up_actions_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`follow_up_actions_required`)),
  `environmental_policy_impact` tinyint(1) DEFAULT 0,
  `community_trust_impact` enum('none','positive','negative','mixed') DEFAULT 'none',
  `green_credentials_affected` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `effective_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_moderator_action` (`moderator_id`,`action_type`,`created_at`),
  KEY `idx_target_tracking` (`target_type`,`target_id`,`created_at`),
  KEY `idx_action_category` (`action_category`,`action_type`,`created_at`),
  KEY `idx_automation` (`is_automated`,`automation_rule_id`),
  KEY `idx_review_required` (`review_required`,`review_by_date`),
  KEY `idx_appeal_eligible` (`appeal_eligible`,`appeal_deadline`),
  KEY `idx_escalation` (`escalation_triggered`,`created_at`),
  KEY `idx_user_actions` (`target_user_id`,`action_type`,`created_at`),
  KEY `idx_severity_impact` (`severity_impact`,`created_at`),
  CONSTRAINT `moderation_logs_ibfk_1` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `moderation_logs_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| log_id | int(11) | NO | PRI |  | auto_increment |
| action_type | enum('content_review','content_remove','content_restore','content_edit','user_warn','user_suspend','user_ban','user_unban','account_restrict','report_assign','report_resolve','report_escalate','report_dismiss','appeal_process','policy_update','automated_action','manual_override','community_decision','ai_flag','bulk_action','emergency_action') | NO |  |  |  |
| action_category | enum('content_moderation','user_management','report_handling','appeal_processing','policy_enforcement','automated_moderation','community_moderation','admin_action') | NO | MUL |  |  |
| target_type | enum('user','content','report','appeal','comment','review','post','message','profile','system','bulk_targets') | NO | MUL |  |  |
| target_id | int(11) | YES |  |  |  |
| target_user_id | int(11) | YES | MUL |  |  |
| affected_content_ids | longtext | YES |  |  |  |
| moderator_id | int(11) | NO | MUL |  |  |
| moderator_type | enum('admin','moderator','community_mod','ai_system','automated') | NO |  |  |  |
| moderator_session_id | varchar(255) | YES |  |  |  |
| action_reason | text | NO |  |  |  |
| action_description | text | YES |  |  |  |
| policy_violated | varchar(200) | YES |  |  |  |
| evidence_referenced | longtext | YES |  |  |  |
| previous_state | longtext | YES |  |  |  |
| new_state | longtext | YES |  |  |  |
| changed_fields | longtext | YES |  |  |  |
| decision_confidence | decimal(5,2) | YES |  |  |  |
| decision_factors | text | YES |  |  |  |
| community_input_considered | tinyint(1) | YES |  | 0 |  |
| ai_recommendation_followed | tinyint(1) | YES |  |  |  |
| affected_users_count | int(11) | YES |  | 0 |  |
| content_items_affected | int(11) | YES |  | 0 |  |
| severity_impact | enum('minimal','moderate','significant','major','critical') | YES | MUL | minimal |  |
| is_reversible | tinyint(1) | YES |  | 1 |  |
| appeal_eligible | tinyint(1) | YES | MUL | 1 |  |
| appeal_deadline | timestamp | YES |  |  |  |
| review_required | tinyint(1) | YES | MUL | 0 |  |
| review_by_date | date | YES |  |  |  |
| is_automated | tinyint(1) | YES | MUL | 0 |  |
| automation_rule_id | int(11) | YES |  |  |  |
| automation_confidence | decimal(5,4) | YES |  |  |  |
| manual_review_triggered | tinyint(1) | YES |  | 0 |  |
| processing_time_seconds | int(11) | YES |  |  |  |
| escalation_triggered | tinyint(1) | YES | MUL | 0 |  |
| follow_up_actions_required | longtext | YES |  |  |  |
| environmental_policy_impact | tinyint(1) | YES |  | 0 |  |
| community_trust_impact | enum('none','positive','negative','mixed') | YES |  | none |  |
| green_credentials_affected | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| effective_at | timestamp | NO |  | current_timestamp() |  |
| expires_at | timestamp | YES |  |  |  |

---

## Table: moderation_rules

**Row Count:** 4

### CREATE TABLE Statement

```sql
CREATE TABLE `moderation_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(200) NOT NULL,
  `rule_description` text NOT NULL,
  `rule_category` enum('content_filtering','spam_detection','harassment_prevention','misinformation_detection','environmental_verification','community_standards','user_behavior','automated_response','escalation_trigger') NOT NULL,
  `applies_to_content_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`applies_to_content_types`)),
  `applies_to_user_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applies_to_user_types`)),
  `geographic_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`geographic_scope`)),
  `language_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["vi", "en"]' CHECK (json_valid(`language_scope`)),
  `trigger_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`trigger_conditions`)),
  `action_thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`action_thresholds`)),
  `confidence_threshold` decimal(5,4) DEFAULT 0.8000,
  `automatic_action` enum('flag_for_review','remove_content','restrict_user','send_warning','escalate_to_human','request_verification','apply_label','no_action') NOT NULL,
  `escalation_action` enum('human_review','admin_review','community_vote','expert_review','none') DEFAULT 'human_review',
  `ai_model_id` int(11) DEFAULT NULL,
  `ai_model_version` varchar(50) DEFAULT NULL,
  `ml_algorithm_type` varchar(100) DEFAULT NULL,
  `training_data_source` varchar(200) DEFAULT NULL,
  `true_positive_rate` decimal(5,4) DEFAULT NULL,
  `false_positive_rate` decimal(5,4) DEFAULT NULL,
  `accuracy_score` decimal(5,4) DEFAULT NULL,
  `last_performance_review` date DEFAULT NULL,
  `rule_status` enum('active','inactive','testing','deprecated') DEFAULT 'testing',
  `priority_order` int(11) DEFAULT 100,
  `execution_frequency` enum('real_time','batch_hourly','batch_daily','on_demand') DEFAULT 'real_time',
  `total_executions` int(11) DEFAULT 0,
  `successful_detections` int(11) DEFAULT 0,
  `false_positives` int(11) DEFAULT 0,
  `appeals_overturned` int(11) DEFAULT 0,
  `protects_environmental_integrity` tinyint(1) DEFAULT 0,
  `prevents_greenwashing` tinyint(1) DEFAULT 0,
  `verifies_sustainability_claims` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `last_modified_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_rule_status` (`rule_status`,`priority_order`),
  KEY `idx_category_type` (`rule_category`,`applies_to_content_types`(255)),
  KEY `idx_performance` (`accuracy_score`,`false_positive_rate`),
  KEY `idx_execution_frequency` (`execution_frequency`,`last_executed_at`),
  KEY `idx_ai_model` (`ai_model_id`,`ai_model_version`),
  KEY `created_by` (`created_by`),
  KEY `last_modified_by` (`last_modified_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `moderation_rules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `moderation_rules_ibfk_2` FOREIGN KEY (`last_modified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `moderation_rules_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| rule_id | int(11) | NO | PRI |  | auto_increment |
| rule_name | varchar(200) | NO |  |  |  |
| rule_description | text | NO |  |  |  |
| rule_category | enum('content_filtering','spam_detection','harassment_prevention','misinformation_detection','environmental_verification','community_standards','user_behavior','automated_response','escalation_trigger') | NO | MUL |  |  |
| applies_to_content_types | longtext | NO |  |  |  |
| applies_to_user_types | longtext | YES |  |  |  |
| geographic_scope | longtext | YES |  |  |  |
| language_scope | longtext | YES |  | '["vi", "en"]' |  |
| trigger_conditions | longtext | NO |  |  |  |
| action_thresholds | longtext | NO |  |  |  |
| confidence_threshold | decimal(5,4) | YES |  | 0.8000 |  |
| automatic_action | enum('flag_for_review','remove_content','restrict_user','send_warning','escalate_to_human','request_verification','apply_label','no_action') | NO |  |  |  |
| escalation_action | enum('human_review','admin_review','community_vote','expert_review','none') | YES |  | human_review |  |
| ai_model_id | int(11) | YES | MUL |  |  |
| ai_model_version | varchar(50) | YES |  |  |  |
| ml_algorithm_type | varchar(100) | YES |  |  |  |
| training_data_source | varchar(200) | YES |  |  |  |
| true_positive_rate | decimal(5,4) | YES |  |  |  |
| false_positive_rate | decimal(5,4) | YES |  |  |  |
| accuracy_score | decimal(5,4) | YES | MUL |  |  |
| last_performance_review | date | YES |  |  |  |
| rule_status | enum('active','inactive','testing','deprecated') | YES | MUL | testing |  |
| priority_order | int(11) | YES |  | 100 |  |
| execution_frequency | enum('real_time','batch_hourly','batch_daily','on_demand') | YES | MUL | real_time |  |
| total_executions | int(11) | YES |  | 0 |  |
| successful_detections | int(11) | YES |  | 0 |  |
| false_positives | int(11) | YES |  | 0 |  |
| appeals_overturned | int(11) | YES |  | 0 |  |
| protects_environmental_integrity | tinyint(1) | YES |  | 0 |  |
| prevents_greenwashing | tinyint(1) | YES |  | 0 |  |
| verifies_sustainability_claims | tinyint(1) | YES |  | 0 |  |
| created_by | int(11) | NO | MUL |  |  |
| last_modified_by | int(11) | YES | MUL |  |  |
| approved_by | int(11) | YES | MUL |  |  |
| approval_date | timestamp | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| last_executed_at | timestamp | YES |  |  |  |
| next_review_date | date | YES |  |  |  |

---

## Table: monthly_analytics_summary

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `monthly_analytics_summary` (
  `summary_id` int(11) NOT NULL AUTO_INCREMENT,
  `month_year` varchar(7) NOT NULL,
  `total_users` int(11) DEFAULT 0,
  `active_users` int(11) DEFAULT 0,
  `new_users` int(11) DEFAULT 0,
  `total_activities` int(11) DEFAULT 0,
  `total_points_awarded` bigint(20) DEFAULT 0,
  `total_articles` int(11) DEFAULT 0,
  `articles_published` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `orders_completed` int(11) DEFAULT 0,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `total_carbon_saved` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`summary_id`),
  UNIQUE KEY `unique_month_year` (`month_year`),
  KEY `idx_monthly_summary_month` (`month_year`),
  KEY `idx_monthly_summary_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Monthly aggregated analytics for performance optimization';
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| summary_id | int(11) | NO | PRI |  | auto_increment |
| month_year | varchar(7) | NO | UNI |  |  |
| total_users | int(11) | YES |  | 0 |  |
| active_users | int(11) | YES |  | 0 |  |
| new_users | int(11) | YES |  | 0 |  |
| total_activities | int(11) | YES |  | 0 |  |
| total_points_awarded | bigint(20) | YES |  | 0 |  |
| total_articles | int(11) | YES |  | 0 |  |
| articles_published | int(11) | YES |  | 0 |  |
| total_orders | int(11) | YES |  | 0 |  |
| orders_completed | int(11) | YES |  | 0 |  |
| total_revenue | decimal(15,2) | YES |  | 0.00 |  |
| total_carbon_saved | decimal(12,2) | YES |  | 0.00 |  |
| created_at | timestamp | NO | MUL | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: notification_analytics

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `notification_analytics` (
  `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `delivery_success_rate` decimal(5,2) DEFAULT 0.00,
  `average_delivery_time_seconds` int(11) DEFAULT 0,
  `channel_performance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channel_performance`)),
  `open_rate` decimal(5,2) DEFAULT 0.00,
  `click_through_rate` decimal(5,2) DEFAULT 0.00,
  `conversion_rate` decimal(5,2) DEFAULT 0.00,
  `engagement_score` decimal(5,2) DEFAULT 0.00,
  `environmental_action_triggered` tinyint(1) DEFAULT 0,
  `carbon_impact_awareness_score` int(11) DEFAULT 0,
  `green_action_completion_rate` decimal(5,2) DEFAULT 0.00,
  `device_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_breakdown`)),
  `location_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`location_breakdown`)),
  `time_zone_performance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`time_zone_performance`)),
  `ab_test_variant` varchar(50) DEFAULT NULL,
  `variant_performance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_performance`)),
  `statistical_significance` tinyint(1) DEFAULT 0,
  `user_satisfaction_score` decimal(3,2) DEFAULT 0.00,
  `feedback_count` int(11) DEFAULT 0,
  `positive_feedback_ratio` decimal(3,2) DEFAULT 0.00,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`analytics_id`),
  KEY `idx_notification_performance` (`notification_id`,`calculated_at`),
  KEY `idx_environmental_impact` (`environmental_action_triggered`,`carbon_impact_awareness_score`),
  CONSTRAINT `notification_analytics_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| analytics_id | int(11) | NO | PRI |  | auto_increment |
| notification_id | int(11) | NO | MUL |  |  |
| delivery_success_rate | decimal(5,2) | YES |  | 0.00 |  |
| average_delivery_time_seconds | int(11) | YES |  | 0 |  |
| channel_performance | longtext | YES |  |  |  |
| open_rate | decimal(5,2) | YES |  | 0.00 |  |
| click_through_rate | decimal(5,2) | YES |  | 0.00 |  |
| conversion_rate | decimal(5,2) | YES |  | 0.00 |  |
| engagement_score | decimal(5,2) | YES |  | 0.00 |  |
| environmental_action_triggered | tinyint(1) | YES | MUL | 0 |  |
| carbon_impact_awareness_score | int(11) | YES |  | 0 |  |
| green_action_completion_rate | decimal(5,2) | YES |  | 0.00 |  |
| device_breakdown | longtext | YES |  |  |  |
| location_breakdown | longtext | YES |  |  |  |
| time_zone_performance | longtext | YES |  |  |  |
| ab_test_variant | varchar(50) | YES |  |  |  |
| variant_performance | longtext | YES |  |  |  |
| statistical_significance | tinyint(1) | YES |  | 0 |  |
| user_satisfaction_score | decimal(3,2) | YES |  | 0.00 |  |
| feedback_count | int(11) | YES |  | 0 |  |
| positive_feedback_ratio | decimal(3,2) | YES |  | 0.00 |  |
| calculated_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: notification_channels

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `notification_channels` (
  `channel_id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_name` varchar(50) NOT NULL,
  `channel_display_name` varchar(100) NOT NULL,
  `channel_type` enum('push','email','sms','in_app','webhook','slack','telegram') NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `api_key_encrypted` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `rate_limit_per_minute` int(11) DEFAULT 60,
  `rate_limit_per_hour` int(11) DEFAULT 1000,
  `rate_limit_per_day` int(11) DEFAULT 10000,
  `supports_rich_content` tinyint(1) DEFAULT 0,
  `supports_attachments` tinyint(1) DEFAULT 0,
  `supports_scheduling` tinyint(1) DEFAULT 1,
  `max_content_length` int(11) DEFAULT 1000,
  `priority_level` tinyint(4) DEFAULT 5,
  `cost_per_notification` decimal(8,4) DEFAULT 0.0000,
  `last_health_check` timestamp NULL DEFAULT NULL,
  `health_status` enum('healthy','warning','error','disabled') DEFAULT 'healthy',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`channel_id`),
  UNIQUE KEY `channel_name` (`channel_name`),
  KEY `idx_channel_type` (`channel_type`,`is_enabled`),
  KEY `idx_health_status` (`health_status`,`last_health_check`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| channel_id | int(11) | NO | PRI |  | auto_increment |
| channel_name | varchar(50) | NO | UNI |  |  |
| channel_display_name | varchar(100) | NO |  |  |  |
| channel_type | enum('push','email','sms','in_app','webhook','slack','telegram') | NO | MUL |  |  |
| is_enabled | tinyint(1) | YES |  | 1 |  |
| api_endpoint | varchar(255) | YES |  |  |  |
| api_key_encrypted | varchar(255) | YES |  |  |  |
| webhook_url | varchar(500) | YES |  |  |  |
| rate_limit_per_minute | int(11) | YES |  | 60 |  |
| rate_limit_per_hour | int(11) | YES |  | 1000 |  |
| rate_limit_per_day | int(11) | YES |  | 10000 |  |
| supports_rich_content | tinyint(1) | YES |  | 0 |  |
| supports_attachments | tinyint(1) | YES |  | 0 |  |
| supports_scheduling | tinyint(1) | YES |  | 1 |  |
| max_content_length | int(11) | YES |  | 1000 |  |
| priority_level | tinyint(4) | YES |  | 5 |  |
| cost_per_notification | decimal(8,4) | YES |  | 0.0000 |  |
| last_health_check | timestamp | YES |  |  |  |
| health_status | enum('healthy','warning','error','disabled') | YES | MUL | healthy |  |
| error_message | text | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: notification_templates

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `notification_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `template_category` enum('system','environmental','social','commerce','achievement','reminder','alert') NOT NULL,
  `subject_template` varchar(255) NOT NULL,
  `content_template` longtext NOT NULL,
  `content_html_template` longtext DEFAULT NULL,
  `supports_variables` tinyint(1) DEFAULT 1,
  `variable_definitions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variable_definitions`)),
  `personalization_level` enum('none','basic','advanced','ai_powered') DEFAULT 'basic',
  `dynamic_content_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dynamic_content_rules`)),
  `email_template` longtext DEFAULT NULL,
  `sms_template` text DEFAULT NULL,
  `push_template` text DEFAULT NULL,
  `in_app_template` longtext DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `priority` enum('low','normal','high','urgent','critical') DEFAULT 'normal',
  `auto_expire_hours` int(11) DEFAULT NULL,
  `ab_test_group` varchar(50) DEFAULT NULL,
  `ab_test_active` tinyint(1) DEFAULT 0,
  `conversion_tracking` tinyint(1) DEFAULT 0,
  `requires_consent` tinyint(1) DEFAULT 0,
  `gdpr_compliant` tinyint(1) DEFAULT 1,
  `retention_days` int(11) DEFAULT 365,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `template_name` (`template_name`),
  KEY `created_by` (`created_by`),
  KEY `idx_category_active` (`template_category`,`is_active`),
  KEY `idx_ab_testing` (`ab_test_group`,`ab_test_active`),
  FULLTEXT KEY `subject_template` (`subject_template`,`content_template`),
  CONSTRAINT `notification_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| template_id | int(11) | NO | PRI |  | auto_increment |
| template_name | varchar(100) | NO | UNI |  |  |
| template_category | enum('system','environmental','social','commerce','achievement','reminder','alert') | NO | MUL |  |  |
| subject_template | varchar(255) | NO | MUL |  |  |
| content_template | longtext | NO |  |  |  |
| content_html_template | longtext | YES |  |  |  |
| supports_variables | tinyint(1) | YES |  | 1 |  |
| variable_definitions | longtext | YES |  |  |  |
| personalization_level | enum('none','basic','advanced','ai_powered') | YES |  | basic |  |
| dynamic_content_rules | longtext | YES |  |  |  |
| email_template | longtext | YES |  |  |  |
| sms_template | text | YES |  |  |  |
| push_template | text | YES |  |  |  |
| in_app_template | longtext | YES |  |  |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| priority | enum('low','normal','high','urgent','critical') | YES |  | normal |  |
| auto_expire_hours | int(11) | YES |  |  |  |
| ab_test_group | varchar(50) | YES | MUL |  |  |
| ab_test_active | tinyint(1) | YES |  | 0 |  |
| conversion_tracking | tinyint(1) | YES |  | 0 |  |
| requires_consent | tinyint(1) | YES |  | 0 |  |
| gdpr_compliant | tinyint(1) | YES |  | 1 |  |
| retention_days | int(11) | YES |  | 365 |  |
| created_by | int(11) | NO | MUL |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: notifications

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('user','group','admin','system') DEFAULT 'user',
  `sender_id` int(11) DEFAULT NULL,
  `sender_type` enum('user','system','automated','ai') DEFAULT 'system',
  `notification_type` enum('system','environmental_alert','social_interaction','achievement','reminder','message','promotion','warning','urgent') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `action_buttons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_buttons`)),
  `rich_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rich_content`)),
  `environmental_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`environmental_context`)),
  `carbon_impact_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`carbon_impact_info`)),
  `green_action_related` tinyint(1) DEFAULT 0,
  `priority` enum('low','normal','high','urgent','critical') DEFAULT 'normal',
  `urgency_level` tinyint(4) DEFAULT 3,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["in_app"]' CHECK (json_valid(`channels`)),
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','scheduled','sent','delivered','read','failed','expired','cancelled') DEFAULT 'pending',
  `delivery_attempts` int(11) DEFAULT 0,
  `max_delivery_attempts` int(11) DEFAULT 3,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_dismissed` tinyint(1) DEFAULT 0,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `action_taken` varchar(100) DEFAULT NULL,
  `action_taken_at` timestamp NULL DEFAULT NULL,
  `click_count` int(11) DEFAULT 0,
  `conversion_tracked` tinyint(1) DEFAULT 0,
  `related_content_type` enum('article','event','product','achievement','message','petition','exchange') DEFAULT NULL,
  `related_content_id` int(11) DEFAULT NULL,
  `deep_link_url` varchar(500) DEFAULT NULL,
  `campaign_id` varchar(100) DEFAULT NULL,
  `tracking_pixel_url` varchar(500) DEFAULT NULL,
  `utm_parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`utm_parameters`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `delivery_channel` varchar(50) DEFAULT NULL,
  `delivery_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_details`)),
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `sender_id` (`sender_id`),
  KEY `idx_recipient_status` (`recipient_id`,`status`,`created_at`),
  KEY `idx_recipient_unread` (`recipient_id`,`is_read`,`created_at`),
  KEY `idx_notification_type` (`notification_type`,`priority`,`created_at`),
  KEY `idx_scheduled_delivery` (`scheduled_for`,`status`),
  KEY `idx_expires` (`expires_at`,`status`),
  KEY `idx_environmental_context` (`green_action_related`,`notification_type`),
  KEY `idx_related_content` (`related_content_type`,`related_content_id`),
  KEY `idx_campaign_tracking` (`campaign_id`,`created_at`),
  FULLTEXT KEY `title` (`title`,`content`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| notification_id | int(11) | NO | PRI |  | auto_increment |
| recipient_id | int(11) | NO | MUL |  |  |
| recipient_type | enum('user','group','admin','system') | YES |  | user |  |
| sender_id | int(11) | YES | MUL |  |  |
| sender_type | enum('user','system','automated','ai') | YES |  | system |  |
| notification_type | enum('system','environmental_alert','social_interaction','achievement','reminder','message','promotion','warning','urgent') | NO | MUL |  |  |
| title | varchar(255) | NO | MUL |  |  |
| content | longtext | NO |  |  |  |
| content_html | longtext | YES |  |  |  |
| image_url | varchar(500) | YES |  |  |  |
| thumbnail_url | varchar(500) | YES |  |  |  |
| attachments | longtext | YES |  |  |  |
| action_buttons | longtext | YES |  |  |  |
| rich_content | longtext | YES |  |  |  |
| environmental_context | longtext | YES |  |  |  |
| carbon_impact_info | longtext | YES |  |  |  |
| green_action_related | tinyint(1) | YES | MUL | 0 |  |
| priority | enum('low','normal','high','urgent','critical') | YES |  | normal |  |
| urgency_level | tinyint(4) | YES |  | 3 |  |
| channels | longtext | YES |  | '["in_app"]' |  |
| scheduled_for | timestamp | YES | MUL |  |  |
| expires_at | timestamp | YES | MUL |  |  |
| status | enum('pending','scheduled','sent','delivered','read','failed','expired','cancelled') | YES |  | pending |  |
| delivery_attempts | int(11) | YES |  | 0 |  |
| max_delivery_attempts | int(11) | YES |  | 3 |  |
| is_read | tinyint(1) | YES |  | 0 |  |
| read_at | timestamp | YES |  |  |  |
| is_dismissed | tinyint(1) | YES |  | 0 |  |
| dismissed_at | timestamp | YES |  |  |  |
| action_taken | varchar(100) | YES |  |  |  |
| action_taken_at | timestamp | YES |  |  |  |
| click_count | int(11) | YES |  | 0 |  |
| conversion_tracked | tinyint(1) | YES |  | 0 |  |
| related_content_type | enum('article','event','product','achievement','message','petition','exchange') | YES | MUL |  |  |
| related_content_id | int(11) | YES |  |  |  |
| deep_link_url | varchar(500) | YES |  |  |  |
| campaign_id | varchar(100) | YES | MUL |  |  |
| tracking_pixel_url | varchar(500) | YES |  |  |  |
| utm_parameters | longtext | YES |  |  |  |
| sent_at | timestamp | YES |  |  |  |
| delivered_at | timestamp | YES |  |  |  |
| delivery_channel | varchar(50) | YES |  |  |  |
| delivery_details | longtext | YES |  |  |  |
| error_message | text | YES |  |  |  |
| retry_count | int(11) | YES |  | 0 |  |
| last_retry_at | timestamp | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: order_items

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `idx_order_items_product` (`product_id`),
  KEY `idx_order_items_order_product` (`order_id`,`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| item_id | int(11) | NO | PRI |  | auto_increment |
| order_id | int(11) | NO | MUL |  |  |
| product_id | int(11) | NO | MUL |  |  |
| product_name | varchar(255) | NO |  |  |  |
| quantity | int(11) | NO |  |  |  |
| unit_price | decimal(10,2) | NO |  |  |  |
| total_price | decimal(10,2) | NO |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: orders

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `green_points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_user_status` (`user_id`,`order_status`),
  KEY `idx_orders_seller_date` (`seller_id`,`created_at`),
  KEY `idx_orders_status_date` (`order_status`,`created_at`),
  KEY `idx_orders_user_date` (`user_id`,`created_at`),
  KEY `idx_orders_user_status_date` (`user_id`,`order_status`,`created_at`),
  KEY `idx_orders_seller_status_amount` (`seller_id`,`order_status`,`total_amount`),
  KEY `idx_orders_status_amount_date` (`order_status`,`total_amount`,`created_at`),
  KEY `idx_orders_payment_status` (`payment_status`,`order_status`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_id | int(11) | NO | PRI |  | auto_increment |
| order_number | varchar(50) | NO | UNI |  |  |
| user_id | int(11) | NO | MUL |  |  |
| seller_id | int(11) | NO | MUL |  |  |
| order_status | enum('pending','confirmed','processing','shipped','delivered','cancelled') | YES | MUL | pending |  |
| payment_status | enum('pending','paid','failed','refunded') | YES | MUL | pending |  |
| subtotal | decimal(10,2) | NO |  |  |  |
| total_amount | decimal(10,2) | NO |  |  |  |
| green_points_earned | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: password_resets

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `reset_token` (`reset_token`),
  KEY `user_id` (`user_id`),
  KEY `idx_token_expires` (`reset_token`,`expires_at`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| reset_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| reset_token | varchar(255) | NO | UNI |  |  |
| expires_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| used | tinyint(1) | YES |  | 0 |  |
| ip_address | varchar(45) | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| used_at | timestamp | YES |  |  |  |

---

## Table: petition_signatures

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `petition_signatures` (
  `signature_id` int(11) NOT NULL AUTO_INCREMENT,
  `petition_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `signer_name` varchar(100) NOT NULL,
  `signer_email` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `points_earned` int(11) DEFAULT 5,
  `signed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`signature_id`),
  KEY `petition_id` (`petition_id`),
  CONSTRAINT `petition_signatures_ibfk_1` FOREIGN KEY (`petition_id`) REFERENCES `petitions` (`petition_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| signature_id | int(11) | NO | PRI |  | auto_increment |
| petition_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | YES |  |  |  |
| signer_name | varchar(100) | NO |  |  |  |
| signer_email | varchar(255) | YES |  |  |  |
| is_verified | tinyint(1) | YES |  | 0 |  |
| points_earned | int(11) | YES |  | 5 |  |
| signed_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: petitions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `petitions` (
  `petition_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `target_authority` varchar(255) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `target_signatures` int(11) DEFAULT 1000,
  `current_signatures` int(11) DEFAULT 0,
  `category` enum('environment','pollution','climate','wildlife','energy','transport','other') NOT NULL,
  `status` enum('draft','active','successful','closed','rejected') DEFAULT 'draft',
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `location_scope` enum('global','national','regional','local') DEFAULT 'local',
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `share_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`petition_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_creator_status` (`creator_id`,`status`),
  KEY `idx_category_featured` (`category`,`is_featured`),
  KEY `idx_signatures` (`current_signatures`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| petition_id | int(11) | NO | PRI |  | auto_increment |
| title | varchar(255) | NO |  |  |  |
| slug | varchar(255) | NO | UNI |  |  |
| description | longtext | NO |  |  |  |
| target_authority | varchar(255) | NO |  |  |  |
| creator_id | int(11) | NO | MUL |  |  |
| target_signatures | int(11) | YES |  | 1000 |  |
| current_signatures | int(11) | YES | MUL | 0 |  |
| category | enum('environment','pollution','climate','wildlife','energy','transport','other') | NO | MUL |  |  |
| status | enum('draft','active','successful','closed','rejected') | YES |  | draft |  |
| start_date | datetime | NO |  |  |  |
| end_date | datetime | YES |  |  |  |
| featured_image | varchar(255) | YES |  |  |  |
| location_scope | enum('global','national','regional','local') | YES |  | local |  |
| is_featured | tinyint(1) | YES |  | 0 |  |
| view_count | int(11) | YES |  | 0 |  |
| share_count | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: platform_metrics

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `platform_metrics` (
  `metric_id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_category` varchar(50) NOT NULL,
  `metric_value` decimal(15,4) NOT NULL,
  `metric_date` date NOT NULL,
  `daily_active_users` int(11) DEFAULT 0,
  `total_carbon_saved` decimal(12,4) DEFAULT 0.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`metric_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| metric_id | int(11) | NO | PRI |  | auto_increment |
| metric_name | varchar(100) | NO |  |  |  |
| metric_category | varchar(50) | NO |  |  |  |
| metric_value | decimal(15,4) | NO |  |  |  |
| metric_date | date | NO |  |  |  |
| daily_active_users | int(11) | YES |  | 0 |  |
| total_carbon_saved | decimal(12,4) | YES |  | 0.0000 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: product_brands

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `product_brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `brand_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sustainability_score` int(11) DEFAULT 50,
  `is_eco_friendly` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `brand_name` (`brand_name`),
  UNIQUE KEY `brand_slug` (`brand_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| brand_id | int(11) | NO | PRI |  | auto_increment |
| brand_name | varchar(100) | NO | UNI |  |  |
| brand_slug | varchar(100) | NO | UNI |  |  |
| description | text | YES |  |  |  |
| sustainability_score | int(11) | YES |  | 50 |  |
| is_eco_friendly | tinyint(1) | YES |  | 0 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: product_reviews

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` longtext DEFAULT NULL,
  `verified_purchase` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  KEY `idx_product_reviews_analytics` (`product_id`,`rating`,`created_at`),
  KEY `idx_product_reviews_product_rating` (`product_id`,`rating`),
  KEY `idx_product_reviews_user` (`user_id`,`created_at`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| review_id | int(11) | NO | PRI |  | auto_increment |
| product_id | int(11) | NO | MUL |  |  |
| user_id | int(11) | NO | MUL |  |  |
| rating | int(11) | NO |  |  |  |
| review_text | longtext | YES |  |  |  |
| verified_purchase | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: products

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_slug` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `eco_score` int(11) DEFAULT 50,
  `organic_certified` tinyint(1) DEFAULT 0,
  `stock_quantity` int(11) DEFAULT 0,
  `status` enum('draft','active','out_of_stock','discontinued') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `brand_id` (`brand_id`),
  KEY `idx_products_marketplace_analytics` (`created_at`,`price`,`stock_quantity`),
  KEY `idx_products_category_status` (`category_id`,`status`),
  KEY `idx_products_eco_score` (`eco_score`,`status`),
  KEY `idx_products_seller_status` (`seller_id`,`status`),
  KEY `idx_products_price_status` (`price`,`status`),
  KEY `idx_products_category_eco_score` (`category_id`,`eco_score`,`status`),
  KEY `idx_products_seller_status_price` (`seller_id`,`status`,`price`),
  KEY `idx_products_stock_status` (`stock_quantity`,`status`),
  KEY `idx_products_price_range` (`price`,`status`,`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`),
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `product_brands` (`brand_id`),
  CONSTRAINT `products_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| product_id | int(11) | NO | PRI |  | auto_increment |
| seller_id | int(11) | NO | MUL |  |  |
| brand_id | int(11) | YES | MUL |  |  |
| product_name | varchar(255) | NO |  |  |  |
| product_slug | varchar(255) | NO |  |  |  |
| description | longtext | YES |  |  |  |
| price | decimal(10,2) | NO | MUL |  |  |
| category_id | int(11) | YES | MUL |  |  |
| eco_score | int(11) | YES | MUL | 50 |  |
| organic_certified | tinyint(1) | YES |  | 0 |  |
| stock_quantity | int(11) | YES | MUL | 0 |  |
| status | enum('draft','active','out_of_stock','discontinued') | YES |  | draft |  |
| created_at | timestamp | NO | MUL | current_timestamp() |  |

---

## Table: quiz_categories

**Row Count:** 18

### CREATE TABLE Statement

```sql
CREATE TABLE `quiz_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `points_per_question` int(11) DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`),
  UNIQUE KEY `category_slug` (`category_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| category_id | int(11) | NO | PRI |  | auto_increment |
| category_name | varchar(100) | NO | UNI |  |  |
| category_slug | varchar(100) | NO | UNI |  |  |
| description | text | YES |  |  |  |
| difficulty_level | enum('beginner','intermediate','advanced','expert') | YES |  | beginner |  |
| points_per_question | int(11) | YES |  | 10 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: quiz_questions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `quiz_questions` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `question_text` longtext NOT NULL,
  `question_type` enum('multiple_choice','true_false','fill_blank') DEFAULT 'multiple_choice',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`correct_answer`)),
  `explanation` text DEFAULT NULL,
  `difficulty_level` enum('easy','medium','hard') DEFAULT 'medium',
  `points_value` int(11) DEFAULT 10,
  `created_by` int(11) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`question_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `quiz_categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| question_id | int(11) | NO | PRI |  | auto_increment |
| category_id | int(11) | NO | MUL |  |  |
| question_text | longtext | NO |  |  |  |
| question_type | enum('multiple_choice','true_false','fill_blank') | YES |  | multiple_choice |  |
| options | longtext | NO |  |  |  |
| correct_answer | longtext | NO |  |  |  |
| explanation | text | YES |  |  |  |
| difficulty_level | enum('easy','medium','hard') | YES |  | medium |  |
| points_value | int(11) | YES |  | 10 |  |
| created_by | int(11) | NO |  |  |  |
| is_verified | tinyint(1) | YES |  | 0 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: quiz_responses

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `quiz_responses` (
  `response_id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`user_answer`)),
  `is_correct` tinyint(1) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`response_id`),
  KEY `session_id` (`session_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `quiz_responses_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| response_id | int(11) | NO | PRI |  | auto_increment |
| session_id | int(11) | NO | MUL |  |  |
| question_id | int(11) | NO | MUL |  |  |
| user_answer | longtext | NO |  |  |  |
| is_correct | tinyint(1) | NO |  |  |  |
| points_earned | int(11) | YES |  | 0 |  |
| answered_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: quiz_sessions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `quiz_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `session_type` enum('practice','challenge','daily','tournament') DEFAULT 'practice',
  `total_questions` int(11) NOT NULL,
  `correct_answers` int(11) DEFAULT 0,
  `total_points` int(11) DEFAULT 0,
  `status` enum('active','completed','abandoned') DEFAULT 'active',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`session_id`),
  KEY `idx_quiz_sessions_user_status` (`user_id`,`status`),
  KEY `idx_quiz_sessions_type_date` (`session_type`,`started_at`),
  KEY `idx_quiz_sessions_user_category` (`user_id`,`category_id`),
  KEY `idx_quiz_type_status_date` (`session_type`,`status`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| session_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| category_id | int(11) | NO |  |  |  |
| session_type | enum('practice','challenge','daily','tournament') | YES | MUL | practice |  |
| total_questions | int(11) | NO |  |  |  |
| correct_answers | int(11) | YES |  | 0 |  |
| total_points | int(11) | YES |  | 0 |  |
| status | enum('active','completed','abandoned') | YES |  | active |  |
| started_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: real_time_sessions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `real_time_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `socket_id` varchar(255) NOT NULL,
  `connection_type` enum('websocket','sse','polling') DEFAULT 'websocket',
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('connected','disconnected','idle','away') DEFAULT 'connected',
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_environmental_activity` varchar(100) DEFAULT NULL,
  `active_green_challenges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`active_green_challenges`)),
  `connected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `disconnected_at` timestamp NULL DEFAULT NULL,
  `session_duration` int(11) DEFAULT 0,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `socket_id` (`socket_id`),
  KEY `idx_user_sessions` (`user_id`,`status`),
  KEY `idx_socket_lookup` (`socket_id`),
  KEY `idx_active_sessions` (`status`,`last_activity`),
  KEY `idx_environmental_activity` (`current_environmental_activity`,`status`),
  CONSTRAINT `real_time_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| session_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| socket_id | varchar(255) | NO | UNI |  |  |
| connection_type | enum('websocket','sse','polling') | YES |  | websocket |  |
| device_info | longtext | YES |  |  |  |
| ip_address | varchar(45) | YES |  |  |  |
| user_agent | text | YES |  |  |  |
| status | enum('connected','disconnected','idle','away') | YES | MUL | connected |  |
| last_activity | timestamp | NO |  | current_timestamp() |  |
| current_environmental_activity | varchar(100) | YES | MUL |  |  |
| active_green_challenges | longtext | YES |  |  |  |
| connected_at | timestamp | NO |  | current_timestamp() |  |
| disconnected_at | timestamp | YES |  |  |  |
| session_duration | int(11) | YES |  | 0 |  |

---

## Table: recycling_locations

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `recycling_locations` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('recycling_center','collection_point','dropoff_station','mobile_unit') NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `accepted_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`accepted_categories`)),
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `rating_average` decimal(3,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`location_id`),
  KEY `idx_geospatial` (`latitude`,`longitude`),
  KEY `idx_city_type` (`city`,`location_type`,`is_active`),
  KEY `idx_recycling_locations_coordinates` (`latitude`,`longitude`),
  KEY `idx_recycling_locations_city_type` (`city`,`location_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| location_id | int(11) | NO | PRI |  | auto_increment |
| location_name | varchar(100) | NO |  |  |  |
| location_type | enum('recycling_center','collection_point','dropoff_station','mobile_unit') | NO |  |  |  |
| address | varchar(255) | NO |  |  |  |
| city | varchar(50) | NO | MUL |  |  |
| latitude | decimal(10,6) | NO | MUL |  |  |
| longitude | decimal(10,6) | NO |  |  |  |
| phone_number | varchar(20) | YES |  |  |  |
| accepted_categories | longtext | NO |  |  |  |
| operating_hours | longtext | YES |  |  |  |
| rating_average | decimal(3,2) | YES |  | 0.00 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: report_generation_history

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `report_generation_history` (
  `generation_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `file_path` varchar(500) DEFAULT NULL,
  `generation_time_seconds` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`generation_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `report_generation_history_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`template_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| generation_id | int(11) | NO | PRI |  | auto_increment |
| template_id | int(11) | NO | MUL |  |  |
| report_title | varchar(255) | NO |  |  |  |
| status | varchar(20) | YES |  | pending |  |
| file_path | varchar(500) | YES |  |  |  |
| generation_time_seconds | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: report_templates

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `report_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `sql_query` longtext NOT NULL,
  `schedule_frequency` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`template_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `report_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| template_id | int(11) | NO | PRI |  | auto_increment |
| template_name | varchar(100) | NO |  |  |  |
| report_type | varchar(50) | NO |  |  |  |
| sql_query | longtext | NO |  |  |  |
| schedule_frequency | varchar(20) | NO |  |  |  |
| created_by | int(11) | NO | MUL |  |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: reports

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) NOT NULL,
  `reporter_ip_address` varchar(45) DEFAULT NULL,
  `reporter_user_agent` text DEFAULT NULL,
  `is_anonymous_report` tinyint(1) DEFAULT 0,
  `reported_content_type` enum('user','article','comment','forum_post','forum_reply','product','product_review','event','petition','exchange_post','message','waste_report','carbon_data','profile','other') NOT NULL,
  `reported_content_id` int(11) NOT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reported_url` varchar(500) DEFAULT NULL,
  `report_category` enum('spam','harassment','hate_speech','misinformation','copyright','inappropriate_content','scam','fake_profile','impersonation','privacy_violation','environmental_misinformation','price_manipulation','fake_reviews','illegal_content','community_guidelines','other') NOT NULL,
  `report_subcategory` varchar(100) DEFAULT NULL,
  `report_severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `report_title` varchar(255) NOT NULL,
  `report_description` text NOT NULL,
  `evidence_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_urls`)),
  `evidence_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_files`)),
  `evidence_screenshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_screenshots`)),
  `additional_context` text DEFAULT NULL,
  `environmental_harm_level` enum('none','low','medium','high','severe') DEFAULT 'none',
  `affects_community_trust` tinyint(1) DEFAULT 0,
  `involves_minors` tinyint(1) DEFAULT 0,
  `requires_legal_review` tinyint(1) DEFAULT 0,
  `report_status` enum('pending','under_review','investigating','escalated','resolved','dismissed','duplicate','auto_resolved') DEFAULT 'pending',
  `priority_level` enum('low','normal','high','urgent','emergency') DEFAULT 'normal',
  `auto_flagged` tinyint(1) DEFAULT 0,
  `ai_confidence_score` decimal(5,4) DEFAULT NULL,
  `ai_recommended_action` varchar(100) DEFAULT NULL,
  `assigned_moderator_id` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `community_votes_helpful` int(11) DEFAULT 0,
  `community_votes_not_helpful` int(11) DEFAULT 0,
  `community_moderator_id` int(11) DEFAULT NULL,
  `community_review_status` enum('pending','approved','rejected','escalated') DEFAULT 'pending',
  `escalated_to_admin` tinyint(1) DEFAULT 0,
  `escalation_reason` text DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalated_by` int(11) DEFAULT NULL,
  `action_taken` enum('no_action','content_removed','content_edited','user_warned','user_suspended','user_banned','content_flagged','account_restricted','educational_notice','policy_clarification','other') DEFAULT 'no_action',
  `resolution_summary` text DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `appeal_submitted` tinyint(1) DEFAULT 0,
  `appeal_deadline` timestamp NULL DEFAULT NULL,
  `appeal_status` enum('none','pending','under_review','approved','denied') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_reporter_status` (`reporter_id`,`report_status`,`created_at`),
  KEY `idx_content_type_id` (`reported_content_type`,`reported_content_id`),
  KEY `idx_reported_user` (`reported_user_id`,`report_status`),
  KEY `idx_status_priority` (`report_status`,`priority_level`,`created_at`),
  KEY `idx_assigned_moderator` (`assigned_moderator_id`,`report_status`),
  KEY `idx_category_severity` (`report_category`,`report_severity`,`created_at`),
  KEY `idx_auto_flagged` (`auto_flagged`,`ai_confidence_score`),
  KEY `idx_resolution_tracking` (`resolved_at`,`action_taken`),
  KEY `idx_escalation` (`escalated_to_admin`,`escalated_at`),
  KEY `idx_appeal_status` (`appeal_status`,`appeal_deadline`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `community_moderator_id` (`community_moderator_id`),
  KEY `escalated_by` (`escalated_by`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`assigned_moderator_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_5` FOREIGN KEY (`community_moderator_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_6` FOREIGN KEY (`escalated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| report_id | int(11) | NO | PRI |  | auto_increment |
| reporter_id | int(11) | NO | MUL |  |  |
| reporter_ip_address | varchar(45) | YES |  |  |  |
| reporter_user_agent | text | YES |  |  |  |
| is_anonymous_report | tinyint(1) | YES |  | 0 |  |
| reported_content_type | enum('user','article','comment','forum_post','forum_reply','product','product_review','event','petition','exchange_post','message','waste_report','carbon_data','profile','other') | NO | MUL |  |  |
| reported_content_id | int(11) | NO |  |  |  |
| reported_user_id | int(11) | YES | MUL |  |  |
| reported_url | varchar(500) | YES |  |  |  |
| report_category | enum('spam','harassment','hate_speech','misinformation','copyright','inappropriate_content','scam','fake_profile','impersonation','privacy_violation','environmental_misinformation','price_manipulation','fake_reviews','illegal_content','community_guidelines','other') | NO | MUL |  |  |
| report_subcategory | varchar(100) | YES |  |  |  |
| report_severity | enum('low','medium','high','critical') | YES |  | medium |  |
| report_title | varchar(255) | NO |  |  |  |
| report_description | text | NO |  |  |  |
| evidence_urls | longtext | YES |  |  |  |
| evidence_files | longtext | YES |  |  |  |
| evidence_screenshots | longtext | YES |  |  |  |
| additional_context | text | YES |  |  |  |
| environmental_harm_level | enum('none','low','medium','high','severe') | YES |  | none |  |
| affects_community_trust | tinyint(1) | YES |  | 0 |  |
| involves_minors | tinyint(1) | YES |  | 0 |  |
| requires_legal_review | tinyint(1) | YES |  | 0 |  |
| report_status | enum('pending','under_review','investigating','escalated','resolved','dismissed','duplicate','auto_resolved') | YES | MUL | pending |  |
| priority_level | enum('low','normal','high','urgent','emergency') | YES |  | normal |  |
| auto_flagged | tinyint(1) | YES | MUL | 0 |  |
| ai_confidence_score | decimal(5,4) | YES |  |  |  |
| ai_recommended_action | varchar(100) | YES |  |  |  |
| assigned_moderator_id | int(11) | YES | MUL |  |  |
| assigned_at | timestamp | YES |  |  |  |
| reviewed_by | int(11) | YES | MUL |  |  |
| reviewed_at | timestamp | YES |  |  |  |
| resolution_notes | text | YES |  |  |  |
| community_votes_helpful | int(11) | YES |  | 0 |  |
| community_votes_not_helpful | int(11) | YES |  | 0 |  |
| community_moderator_id | int(11) | YES | MUL |  |  |
| community_review_status | enum('pending','approved','rejected','escalated') | YES |  | pending |  |
| escalated_to_admin | tinyint(1) | YES | MUL | 0 |  |
| escalation_reason | text | YES |  |  |  |
| escalated_at | timestamp | YES |  |  |  |
| escalated_by | int(11) | YES | MUL |  |  |
| action_taken | enum('no_action','content_removed','content_edited','user_warned','user_suspended','user_banned','content_flagged','account_restricted','educational_notice','policy_clarification','other') | YES |  | no_action |  |
| resolution_summary | text | YES |  |  |  |
| follow_up_required | tinyint(1) | YES |  | 0 |  |
| follow_up_date | date | YES |  |  |  |
| appeal_submitted | tinyint(1) | YES |  | 0 |  |
| appeal_deadline | timestamp | YES |  |  |  |
| appeal_status | enum('none','pending','under_review','approved','denied') | YES | MUL | none |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| resolved_at | timestamp | YES | MUL |  |  |

---

## Table: seasonal_challenges

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `seasonal_challenges` (
  `challenge_id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_name` varchar(150) NOT NULL,
  `challenge_slug` varchar(150) NOT NULL,
  `title_vi` varchar(200) NOT NULL,
  `title_en` varchar(200) NOT NULL,
  `description_vi` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `challenge_type` enum('seasonal','monthly','weekly','special_event') DEFAULT 'monthly',
  `environmental_theme` enum('carbon','waste','energy','water','general') DEFAULT 'general',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `max_participants` int(11) DEFAULT NULL,
  `current_participants` int(11) DEFAULT 0,
  `completion_points` int(11) DEFAULT 100,
  `participation_points` int(11) DEFAULT 25,
  `special_badge_id` int(11) DEFAULT NULL,
  `completion_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`completion_criteria`)),
  `difficulty_level` enum('easy','medium','hard','expert') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `registration_required` tinyint(1) DEFAULT 0,
  `banner_image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`challenge_id`),
  UNIQUE KEY `challenge_slug` (`challenge_slug`),
  KEY `idx_active_dates` (`is_active`,`start_date`,`end_date`),
  KEY `idx_theme_type` (`environmental_theme`,`challenge_type`),
  KEY `idx_featured` (`is_featured`),
  KEY `special_badge_id` (`special_badge_id`),
  CONSTRAINT `seasonal_challenges_ibfk_1` FOREIGN KEY (`special_badge_id`) REFERENCES `badges_system` (`badge_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| challenge_id | int(11) | NO | PRI |  | auto_increment |
| challenge_name | varchar(150) | NO |  |  |  |
| challenge_slug | varchar(150) | NO | UNI |  |  |
| title_vi | varchar(200) | NO |  |  |  |
| title_en | varchar(200) | NO |  |  |  |
| description_vi | text | YES |  |  |  |
| description_en | text | YES |  |  |  |
| challenge_type | enum('seasonal','monthly','weekly','special_event') | YES |  | monthly |  |
| environmental_theme | enum('carbon','waste','energy','water','general') | YES | MUL | general |  |
| start_date | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| end_date | timestamp | NO |  | 0000-00-00 00:00:00 |  |
| max_participants | int(11) | YES |  |  |  |
| current_participants | int(11) | YES |  | 0 |  |
| completion_points | int(11) | YES |  | 100 |  |
| participation_points | int(11) | YES |  | 25 |  |
| special_badge_id | int(11) | YES | MUL |  |  |
| completion_criteria | longtext | NO |  |  |  |
| difficulty_level | enum('easy','medium','hard','expert') | YES |  | medium |  |
| is_active | tinyint(1) | YES | MUL | 1 |  |
| is_featured | tinyint(1) | YES | MUL | 0 |  |
| registration_required | tinyint(1) | YES |  | 0 |  |
| banner_image_url | varchar(500) | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: sellers

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `sellers` (
  `seller_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_type` enum('individual','small_business','corporation','ngo','cooperative') NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `sustainability_rating` decimal(3,2) DEFAULT 0.00,
  `status` enum('pending','approved','suspended','banned') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`seller_id`),
  UNIQUE KEY `unique_user_seller` (`user_id`),
  CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| seller_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | UNI |  |  |
| business_name | varchar(255) | NO |  |  |  |
| business_type | enum('individual','small_business','corporation','ngo','cooperative') | NO |  |  |  |
| contact_email | varchar(255) | NO |  |  |  |
| sustainability_rating | decimal(3,2) | YES |  | 0.00 |  |
| status | enum('pending','approved','suspended','banned') | YES |  | pending |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: shopping_carts

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `shopping_carts` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `shopping_carts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| cart_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | YES |  |  |  |
| product_id | int(11) | NO | MUL |  |  |
| quantity | int(11) | NO |  | 1 |  |
| unit_price | decimal(10,2) | NO |  |  |  |
| total_price | decimal(10,2) | NO |  |  |  |
| added_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: slow_trigger_archive

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `slow_trigger_archive` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `trigger_name` varchar(100) NOT NULL,
  `avg_execution_time` decimal(10,6) NOT NULL,
  `max_execution_time` decimal(10,6) NOT NULL,
  `total_executions` int(11) NOT NULL,
  `archive_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`archive_id`),
  KEY `idx_archive_trigger_date` (`trigger_name`,`archive_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| archive_id | int(11) | NO | PRI |  | auto_increment |
| trigger_name | varchar(100) | NO | MUL |  |  |
| avg_execution_time | decimal(10,6) | NO |  |  |  |
| max_execution_time | decimal(10,6) | NO |  |  |  |
| total_executions | int(11) | NO |  |  |  |
| archive_date | date | NO |  |  |  |
| created_at | datetime | YES |  | current_timestamp() |  |

---

## Table: social_platforms

**Row Count:** 6

### CREATE TABLE Statement

```sql
CREATE TABLE `social_platforms` (
  `platform_id` int(11) NOT NULL AUTO_INCREMENT,
  `platform_name` varchar(50) NOT NULL,
  `platform_display_name` varchar(100) NOT NULL,
  `platform_icon` varchar(255) DEFAULT NULL,
  `platform_color` varchar(7) DEFAULT NULL,
  `share_url_template` text DEFAULT NULL,
  `points_per_share` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`platform_id`),
  UNIQUE KEY `platform_name` (`platform_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| platform_id | int(11) | NO | PRI |  | auto_increment |
| platform_name | varchar(50) | NO | UNI |  |  |
| platform_display_name | varchar(100) | NO |  |  |  |
| platform_icon | varchar(255) | YES |  |  |  |
| platform_color | varchar(7) | YES |  |  |  |
| share_url_template | text | YES |  |  |  |
| points_per_share | int(11) | YES |  | 5 |  |
| is_active | tinyint(1) | YES |  | 1 |  |
| sort_order | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: test_simple

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `test_simple` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI |  | auto_increment |
| name | varchar(100) | YES |  |  |  |

---

## Table: trigger_performance_logs

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `trigger_performance_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `trigger_name` varchar(100) NOT NULL,
  `execution_time` decimal(10,6) NOT NULL,
  `rows_affected` int(11) DEFAULT 0,
  `execution_date` datetime DEFAULT current_timestamp(),
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_trigger_perf_name_date` (`trigger_name`,`execution_date`),
  KEY `idx_trigger_perf_time` (`execution_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| log_id | int(11) | NO | PRI |  | auto_increment |
| trigger_name | varchar(100) | NO | MUL |  |  |
| execution_time | decimal(10,6) | NO | MUL |  |  |
| rows_affected | int(11) | YES |  | 0 |  |
| execution_date | datetime | YES |  | current_timestamp() |  |
| error_message | text | YES |  |  |  |

---

## Table: trigger_performance_summary

**Row Count:** 0

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| trigger_name | varchar(100) | NO |  |  |  |
| total_executions | bigint(21) | NO |  | 0 |  |
| avg_execution_time | decimal(14,10) | YES |  |  |  |
| max_execution_time | decimal(10,6) | YES |  |  |  |
| min_execution_time | decimal(10,6) | YES |  |  |  |
| stddev_execution_time | double(26,10) | YES |  |  |  |
| total_rows_affected | decimal(32,0) | YES |  |  |  |
| first_execution | date | YES |  |  |  |
| last_execution | date | YES |  |  |  |

---

## Table: user_achievements_enhanced

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_achievements_enhanced` (
  `user_achievement_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `current_progress` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`current_progress`)),
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `is_completed` tinyint(1) DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_progress_at` timestamp NULL DEFAULT NULL,
  `completion_count` int(11) DEFAULT 0,
  `current_streak` int(11) DEFAULT 0,
  `best_streak` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `experience_earned` int(11) DEFAULT 0,
  `green_points_earned` int(11) DEFAULT 0,
  `is_shared` tinyint(1) DEFAULT 0,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_achievement_id`),
  UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_id`),
  KEY `idx_user_completed` (`user_id`,`is_completed`,`completion_date`),
  KEY `idx_progress` (`user_id`,`progress_percentage`),
  CONSTRAINT `user_achievements_enhanced_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| user_achievement_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| achievement_id | int(11) | NO |  |  |  |
| current_progress | longtext | YES |  |  |  |
| progress_percentage | decimal(5,2) | YES |  | 0.00 |  |
| is_completed | tinyint(1) | YES |  | 0 |  |
| completion_date | timestamp | YES |  |  |  |
| started_at | timestamp | NO |  | current_timestamp() |  |
| last_progress_at | timestamp | YES |  |  |  |
| completion_count | int(11) | YES |  | 0 |  |
| current_streak | int(11) | YES |  | 0 |  |
| best_streak | int(11) | YES |  | 0 |  |
| points_earned | int(11) | YES |  | 0 |  |
| experience_earned | int(11) | YES |  | 0 |  |
| green_points_earned | int(11) | YES |  | 0 |  |
| is_shared | tinyint(1) | YES |  | 0 |  |
| is_public | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: user_activities_archive

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_activities_archive` (
  `activity_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_category` varchar(50) NOT NULL,
  `base_points` int(11) DEFAULT 0,
  `bonus_points` int(11) DEFAULT 0,
  `total_points` int(11) DEFAULT 0,
  `related_entity_type` varchar(50) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `environmental_impact` int(11) DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `idx_archive_user_date` (`user_id`,`created_at`),
  KEY `idx_archive_type_date` (`activity_type`,`created_at`),
  KEY `idx_archive_archived` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archived user activities for historical data';
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| activity_id | bigint(20) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| activity_type | varchar(50) | NO | MUL |  |  |
| activity_category | varchar(50) | NO |  |  |  |
| base_points | int(11) | YES |  | 0 |  |
| bonus_points | int(11) | YES |  | 0 |  |
| total_points | int(11) | YES |  | 0 |  |
| related_entity_type | varchar(50) | YES |  |  |  |
| related_entity_id | int(11) | YES |  |  |  |
| activity_description | text | YES |  |  |  |
| environmental_impact | int(11) | YES |  | 0 |  |
| metadata | longtext | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| archived_at | timestamp | NO | MUL | current_timestamp() |  |

---

## Table: user_activities_comprehensive

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `user_activities_comprehensive` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activity_category` varchar(50) DEFAULT NULL,
  `activity_name` varchar(200) DEFAULT NULL,
  `base_points` int(11) DEFAULT 0,
  `total_points` int(11) DEFAULT 0,
  PRIMARY KEY (`activity_id`),
  KEY `idx_activities_user_type_date` (`user_id`,`activity_type`,`created_at`),
  KEY `idx_activities_category_date` (`activity_category`,`created_at`),
  KEY `idx_activities_points_date` (`total_points`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| activity_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| activity_type | varchar(50) | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| activity_category | varchar(50) | YES | MUL |  |  |
| activity_name | varchar(200) | YES |  |  |  |
| base_points | int(11) | YES |  | 0 |  |
| total_points | int(11) | YES | MUL | 0 |  |

---

## Table: user_activities_test

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_activities_test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI |  | auto_increment |
| name | varchar(100) | YES |  |  |  |

---

## Table: user_activity_patterns

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `user_activity_patterns` (
  `pattern_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `analysis_period` varchar(20) DEFAULT NULL,
  `behavioral_consistency_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pattern_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_activity_patterns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| pattern_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| analysis_period | varchar(20) | YES |  |  |  |
| behavioral_consistency_score | decimal(5,2) | YES |  | 0.00 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: user_analytics

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `user_analytics` (
  `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `time_spent_seconds` int(11) DEFAULT 0,
  `carbon_points_earned` int(11) DEFAULT 0,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`analytics_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_analytics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| analytics_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| content_type | varchar(50) | YES |  |  |  |
| time_spent_seconds | int(11) | YES |  | 0 |  |
| carbon_points_earned | int(11) | YES |  | 0 |  |
| date | date | NO |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: user_analytics_test

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_analytics_test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_field` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI |  | auto_increment |
| test_field | varchar(50) | YES |  |  |  |

---

## Table: user_badges_enhanced

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_badges_enhanced` (
  `user_badge_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `awarded_by` enum('system','admin','achievement','event') DEFAULT 'system',
  `award_reason` text DEFAULT NULL,
  `is_displayed` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`user_badge_id`),
  UNIQUE KEY `unique_user_badge` (`user_id`,`badge_id`),
  KEY `idx_user_display` (`user_id`,`is_displayed`,`display_order`),
  KEY `idx_badge_awards` (`badge_id`,`awarded_at`),
  CONSTRAINT `user_badges_enhanced_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_badges_enhanced_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges_system` (`badge_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| user_badge_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| badge_id | int(11) | NO | MUL |  |  |
| awarded_at | timestamp | NO |  | current_timestamp() |  |
| awarded_by | enum('system','admin','achievement','event') | YES |  | system |  |
| award_reason | text | YES |  |  |  |
| is_displayed | tinyint(1) | YES |  | 1 |  |
| display_order | int(11) | YES |  | 0 |  |
| is_featured | tinyint(1) | YES |  | 0 |  |

---

## Table: user_engagement_scores

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `user_engagement_scores` (
  `score_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `score_period` varchar(20) DEFAULT NULL,
  `overall_engagement_score` decimal(6,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activity_frequency_score` decimal(5,2) DEFAULT 0.00,
  `quality_score` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`score_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_engagement_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| score_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| score_period | varchar(20) | YES |  |  |  |
| overall_engagement_score | decimal(6,2) | YES |  | 0.00 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| activity_frequency_score | decimal(5,2) | YES |  | 0.00 |  |
| quality_score | decimal(5,2) | YES |  | 0.00 |  |

---

## Table: user_engagement_summary

**Row Count:** 2

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| user_id | int(11) | NO |  | 0 |  |
| username | varchar(50) | NO |  |  |  |
| total_sessions | bigint(21) | NO |  | 0 |  |
| total_time_spent | decimal(32,0) | YES |  |  |  |
| total_carbon_points | decimal(32,0) | YES |  |  |  |
| last_activity | timestamp | YES |  | current_timestamp() |  |

---

## Table: user_engagement_summary_final

**Row Count:** 2

### CREATE TABLE Statement

```sql
;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| user_id | int(11) | NO |  | 0 |  |
| username | varchar(50) | NO |  |  |  |
| email | varchar(100) | NO |  |  |  |
| registration_date | timestamp | NO |  | current_timestamp() |  |
| total_login_sessions | bigint(21) | YES |  |  |  |
| last_activity_date | timestamp | YES |  |  |  |
| days_since_registration | int(7) | YES |  |  |  |

---

## Table: user_habit_tracking

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `user_habit_tracking` (
  `habit_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `habit_name` varchar(200) DEFAULT NULL,
  `habit_category` varchar(100) DEFAULT NULL,
  `current_streak` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`habit_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_habit_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| habit_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| habit_name | varchar(200) | YES |  |  |  |
| habit_category | varchar(100) | YES |  |  |  |
| current_streak | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: user_notification_preferences

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `user_notification_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `push_enabled` tinyint(1) DEFAULT 1,
  `sms_enabled` tinyint(1) DEFAULT 0,
  `in_app_enabled` tinyint(1) DEFAULT 1,
  `system_notifications` tinyint(1) DEFAULT 1,
  `environmental_alerts` tinyint(1) DEFAULT 1,
  `social_interactions` tinyint(1) DEFAULT 1,
  `achievements_notifications` tinyint(1) DEFAULT 1,
  `reminder_notifications` tinyint(1) DEFAULT 1,
  `promotional_notifications` tinyint(1) DEFAULT 0,
  `carbon_milestone_alerts` tinyint(1) DEFAULT 1,
  `waste_classification_reminders` tinyint(1) DEFAULT 1,
  `green_challenge_notifications` tinyint(1) DEFAULT 1,
  `environmental_news_digest` tinyint(1) DEFAULT 1,
  `sustainability_tips` tinyint(1) DEFAULT 1,
  `daily_digest_enabled` tinyint(1) DEFAULT 1,
  `weekly_summary_enabled` tinyint(1) DEFAULT 1,
  `instant_notifications` tinyint(1) DEFAULT 1,
  `quiet_hours_start` time DEFAULT '22:00:00',
  `quiet_hours_end` time DEFAULT '08:00:00',
  `digest_frequency` enum('daily','weekly','bi_weekly','monthly') DEFAULT 'weekly',
  `preferred_language` varchar(10) DEFAULT 'vi',
  `timezone` varchar(50) DEFAULT 'Asia/Ho_Chi_Minh',
  `ai_personalization_enabled` tinyint(1) DEFAULT 1,
  `location_based_alerts` tinyint(1) DEFAULT 1,
  `emergency_override` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `unique_user_preferences` (`user_id`),
  KEY `idx_digest_settings` (`daily_digest_enabled`,`weekly_summary_enabled`),
  KEY `idx_environmental_prefs` (`environmental_alerts`,`green_challenge_notifications`),
  CONSTRAINT `user_notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| preference_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | UNI |  |  |
| email_enabled | tinyint(1) | YES |  | 1 |  |
| push_enabled | tinyint(1) | YES |  | 1 |  |
| sms_enabled | tinyint(1) | YES |  | 0 |  |
| in_app_enabled | tinyint(1) | YES |  | 1 |  |
| system_notifications | tinyint(1) | YES |  | 1 |  |
| environmental_alerts | tinyint(1) | YES | MUL | 1 |  |
| social_interactions | tinyint(1) | YES |  | 1 |  |
| achievements_notifications | tinyint(1) | YES |  | 1 |  |
| reminder_notifications | tinyint(1) | YES |  | 1 |  |
| promotional_notifications | tinyint(1) | YES |  | 0 |  |
| carbon_milestone_alerts | tinyint(1) | YES |  | 1 |  |
| waste_classification_reminders | tinyint(1) | YES |  | 1 |  |
| green_challenge_notifications | tinyint(1) | YES |  | 1 |  |
| environmental_news_digest | tinyint(1) | YES |  | 1 |  |
| sustainability_tips | tinyint(1) | YES |  | 1 |  |
| daily_digest_enabled | tinyint(1) | YES | MUL | 1 |  |
| weekly_summary_enabled | tinyint(1) | YES |  | 1 |  |
| instant_notifications | tinyint(1) | YES |  | 1 |  |
| quiet_hours_start | time | YES |  | 22:00:00 |  |
| quiet_hours_end | time | YES |  | 08:00:00 |  |
| digest_frequency | enum('daily','weekly','bi_weekly','monthly') | YES |  | weekly |  |
| preferred_language | varchar(10) | YES |  | vi |  |
| timezone | varchar(50) | YES |  | Asia/Ho_Chi_Minh |  |
| ai_personalization_enabled | tinyint(1) | YES |  | 1 |  |
| location_based_alerts | tinyint(1) | YES |  | 1 |  |
| emergency_override | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: user_permissions

**Row Count:** 28

### CREATE TABLE Statement

```sql
CREATE TABLE `user_permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` text DEFAULT NULL,
  `permission_code` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_name` (`permission_name`),
  UNIQUE KEY `permission_code` (`permission_code`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| permission_id | int(11) | NO | PRI |  | auto_increment |
| permission_name | varchar(100) | NO | UNI |  |  |
| permission_description | text | YES |  |  |  |
| permission_code | varchar(100) | NO | UNI |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: user_preferences

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preference_value`)),
  `category` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `unique_user_preference` (`user_id`,`preference_key`),
  KEY `idx_user_category` (`user_id`,`category`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| preference_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| preference_key | varchar(100) | NO |  |  |  |
| preference_value | longtext | YES |  |  |  |
| category | varchar(50) | YES |  | general |  |
| is_public | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: user_role_assignments

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `user_role_assignments` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `user_role_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_role_assignments_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `user_role_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| user_id | int(11) | NO | PRI |  |  |
| role_id | int(11) | NO | PRI |  |  |
| assigned_by | int(11) | YES | MUL |  |  |
| assigned_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: user_role_permissions

**Row Count:** 56

### CREATE TABLE Statement

```sql
CREATE TABLE `user_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `user_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `user_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `user_permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| role_id | int(11) | NO | PRI |  |  |
| permission_id | int(11) | NO | PRI |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: user_roles

**Row Count:** 6

### CREATE TABLE Statement

```sql
CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `permission_level` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| role_id | int(11) | NO | PRI |  | auto_increment |
| role_name | varchar(50) | NO | UNI |  |  |
| role_description | text | YES |  |  |  |
| permission_level | int(11) | NO |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: user_sessions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_user_active` (`user_id`,`is_active`),
  KEY `idx_token` (`session_token`),
  KEY `idx_user_sessions_user_activity` (`user_id`,`created_at`),
  KEY `idx_user_sessions_device` (`device_type`,`created_at`),
  KEY `idx_user_sessions_active` (`is_active`,`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| session_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| session_token | varchar(255) | NO | UNI |  |  |
| ip_address | varchar(45) | YES |  |  |  |
| user_agent | text | YES |  |  |  |
| device_type | enum('desktop','mobile','tablet','unknown') | YES | MUL | unknown |  |
| is_active | tinyint(1) | YES | MUL | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| last_activity | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| expires_at | timestamp | YES |  |  |  |

---

## Table: user_streaks_advanced

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `user_streaks_advanced` (
  `streak_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `streak_type` varchar(50) DEFAULT NULL,
  `current_streak` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `streak_category` varchar(50) DEFAULT NULL,
  `longest_streak` int(11) DEFAULT 0,
  PRIMARY KEY (`streak_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_streaks_advanced_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| streak_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| streak_type | varchar(50) | YES |  |  |  |
| current_streak | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| streak_category | varchar(50) | YES |  |  |  |
| longest_streak | int(11) | YES |  | 0 |  |

---

## Table: user_streaks_gamification

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_streaks_gamification` (
  `streak_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `streak_type` enum('login','activity','carbon_save','waste_classify','social_share','quiz_complete') NOT NULL,
  `current_streak` int(11) DEFAULT 0,
  `current_streak_start_date` date DEFAULT NULL,
  `last_activity_date` date DEFAULT NULL,
  `best_streak` int(11) DEFAULT 0,
  `best_streak_start_date` date DEFAULT NULL,
  `best_streak_end_date` date DEFAULT NULL,
  `streak_multiplier` decimal(3,2) DEFAULT 1.00,
  `points_earned` int(11) DEFAULT 0,
  `environmental_impact_total` decimal(10,2) DEFAULT 0.00,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`streak_id`),
  UNIQUE KEY `unique_user_streak_type` (`user_id`,`streak_type`),
  KEY `idx_current_streak` (`user_id`,`current_streak`),
  KEY `idx_best_streak` (`user_id`,`best_streak`),
  KEY `idx_last_activity` (`last_activity_date`),
  CONSTRAINT `user_streaks_gamification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| streak_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| streak_type | enum('login','activity','carbon_save','waste_classify','social_share','quiz_complete') | NO |  |  |  |
| current_streak | int(11) | YES |  | 0 |  |
| current_streak_start_date | date | YES |  |  |  |
| last_activity_date | date | YES | MUL |  |  |
| best_streak | int(11) | YES |  | 0 |  |
| best_streak_start_date | date | YES |  |  |  |
| best_streak_end_date | date | YES |  |  |  |
| streak_multiplier | decimal(3,2) | YES |  | 1.00 |  |
| points_earned | int(11) | YES |  | 0 |  |
| environmental_impact_total | decimal(10,2) | YES |  | 0.00 |  |
| is_public | tinyint(1) | YES |  | 1 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| updated_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |

---

## Table: user_verification_codes

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `user_verification_codes` (
  `verification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `verification_type` enum('email','phone','two_factor') NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 5,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`verification_id`),
  KEY `idx_user_type` (`user_id`,`verification_type`),
  CONSTRAINT `user_verification_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| verification_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | NO | MUL |  |  |
| verification_type | enum('email','phone','two_factor') | NO |  |  |  |
| code | varchar(10) | NO |  |  |  |
| expires_at | timestamp | NO |  | current_timestamp() | on update current_timestamp() |
| attempts | int(11) | YES |  | 0 |  |
| max_attempts | int(11) | YES |  | 5 |  |
| is_used | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |
| used_at | timestamp | YES |  |  |  |

---

## Table: users

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `green_points` int(11) DEFAULT 0,
  `user_type` enum('individual','organization','business','admin','moderator') DEFAULT 'individual',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `experience_points` int(11) DEFAULT 0,
  `user_level` int(11) DEFAULT 1,
  `login_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `total_carbon_saved` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_type_active` (`user_type`,`is_active`),
  KEY `idx_users_created` (`created_at`),
  KEY `idx_users_points_level` (`green_points`,`user_level`),
  KEY `idx_users_experience_level` (`experience_points`,`user_level`),
  KEY `idx_users_login_streak` (`login_streak`,`last_login`),
  KEY `idx_users_carbon_saved` (`total_carbon_saved`,`user_level`),
  KEY `idx_users_type_status` (`user_type`,`is_active`,`created_at`),
  KEY `idx_user_login_tracking` (`user_id`,`last_login`,`login_streak`),
  KEY `idx_users_streak_optimization` (`login_streak`,`last_login`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```



## Table: voucher_campaigns

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `voucher_campaigns` (
  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(255) NOT NULL,
  `campaign_slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `campaign_type` enum('seasonal','promotional','loyalty','referral','environmental','milestone') NOT NULL,
  `created_by` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `campaign_status` enum('draft','active','paused','completed','cancelled') DEFAULT 'draft',
  `auto_apply` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`campaign_id`),
  UNIQUE KEY `campaign_slug` (`campaign_slug`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `voucher_campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure



## Table: voucher_usage

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `voucher_usage` (
  `usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `usage_type` enum('claimed','applied','used','refunded','expired_unused') NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `original_order_amount` decimal(10,2) DEFAULT 0.00,
  `final_order_amount` decimal(10,2) DEFAULT 0.00,
  `auto_applied` tinyint(1) DEFAULT 0,
  `application_source` enum('manual','auto','recommendation','reminder') DEFAULT 'manual',
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usage_id`),
  KEY `voucher_id` (`voucher_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `voucher_usage_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`voucher_id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


## Table: vouchers

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `vouchers` (
  `voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) DEFAULT NULL,
  `voucher_code` varchar(50) NOT NULL,
  `voucher_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount','free_shipping','buy_x_get_y','green_points_multiplier','cashback') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `total_usage_limit` int(11) DEFAULT NULL,
  `current_usage` int(11) DEFAULT 0,
  `can_stack_with_others` tinyint(1) DEFAULT 0,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `auto_apply` tinyint(1) DEFAULT 0,
  `voucher_status` enum('draft','active','paused','expired','exhausted','cancelled') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `voucher_code` (`voucher_code`),
  KEY `campaign_id` (`campaign_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `voucher_campaigns` (`campaign_id`) ON DELETE SET NULL,
  CONSTRAINT `vouchers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| voucher_id | int(11) | NO | PRI |  | auto_increment |
| campaign_id | int(11) | YES | MUL |  |  |
| voucher_code | varchar(50) | NO | UNI |  |  |
| voucher_name | varchar(255) | NO |  |  |  |
| description | text | YES |  |  |  |
| discount_type | enum('percentage','fixed_amount','free_shipping','buy_x_get_y','green_points_multiplier','cashback') | NO |  |  |  |
| discount_value | decimal(10,2) | NO |  |  |  |
| max_discount_amount | decimal(10,2) | YES |  |  |  |
| min_order_amount | decimal(10,2) | YES |  | 0.00 |  |
| total_usage_limit | int(11) | YES |  |  |  |
| current_usage | int(11) | YES |  | 0 |  |
| can_stack_with_others | tinyint(1) | YES |  | 0 |  |
| valid_from | datetime | NO |  |  |  |
| valid_until | datetime | NO |  |  |  |
| auto_apply | tinyint(1) | YES |  | 0 |  |
| voucher_status | enum('draft','active','paused','expired','exhausted','cancelled') | YES |  | draft |  |
| created_by | int(11) | NO | MUL |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: waste_categories

**Row Count:** 8

### CREATE TABLE Statement

```sql
CREATE TABLE `waste_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `category_code` varchar(10) NOT NULL,
  `category_type` enum('recyclable','organic','hazardous','general','electronic','medical') NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `points_per_kg` decimal(5,2) DEFAULT 10.00,
  `carbon_saved_per_kg` decimal(5,3) DEFAULT 0.500,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`),
  UNIQUE KEY `category_code` (`category_code`),
  KEY `idx_category_type` (`category_type`),
  KEY `idx_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| category_id | int(11) | NO | PRI |  | auto_increment |
| category_name | varchar(50) | NO | UNI |  |  |
| category_code | varchar(10) | NO | UNI |  |  |
| category_type | enum('recyclable','organic','hazardous','general','electronic','medical') | NO | MUL |  |  |
| description | text | YES |  |  |  |
| color_code | varchar(7) | YES |  |  |  |
| points_per_kg | decimal(5,2) | YES |  | 10.00 |  |
| carbon_saved_per_kg | decimal(5,3) | YES |  | 0.500 |  |
| is_active | tinyint(1) | YES | MUL | 1 |  |
| sort_order | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: waste_classification_results

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `waste_classification_results` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `predicted_category` varchar(100) NOT NULL,
  `confidence_score` decimal(5,4) NOT NULL,
  `is_recyclable` tinyint(1) DEFAULT 0,
  `carbon_saved_kg` decimal(8,4) DEFAULT 0.0000,
  `points_earned` int(11) DEFAULT 0,
  `user_feedback_rating` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`result_id`),
  KEY `idx_session_results` (`session_id`),
  KEY `idx_confidence` (`confidence_score`),
  CONSTRAINT `waste_classification_results_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `waste_classification_sessions` (`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| result_id | int(11) | NO | PRI |  | auto_increment |
| session_id | int(11) | NO | MUL |  |  |
| predicted_category | varchar(100) | NO |  |  |  |
| confidence_score | decimal(5,4) | NO | MUL |  |  |
| is_recyclable | tinyint(1) | YES |  | 0 |  |
| carbon_saved_kg | decimal(8,4) | YES |  | 0.0000 |  |
| points_earned | int(11) | YES |  | 0 |  |
| user_feedback_rating | int(11) | YES |  |  |  |
| is_correct | tinyint(1) | YES |  |  |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: waste_classification_sessions

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `waste_classification_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_type` enum('image','text','barcode') NOT NULL DEFAULT 'image',
  `input_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_data`)),
  `session_status` enum('started','completed','failed') DEFAULT 'started',
  `processing_time_ms` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`session_id`),
  KEY `idx_user_sessions` (`user_id`,`created_at`),
  KEY `idx_waste_classification_sessions_user` (`user_id`,`created_at`),
  CONSTRAINT `waste_classification_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| session_id | int(11) | NO | PRI |  | auto_increment |
| user_id | int(11) | YES | MUL |  |  |
| session_type | enum('image','text','barcode') | NO |  | image |  |
| input_data | longtext | NO |  |  |  |
| session_status | enum('started','completed','failed') | YES |  | started |  |
| processing_time_ms | int(11) | YES |  | 0 |  |
| points_earned | int(11) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: waste_items

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `waste_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `alternative_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alternative_names`)),
  `description` text DEFAULT NULL,
  `recycling_instructions` text DEFAULT NULL,
  `environmental_impact_score` int(11) DEFAULT 50,
  `points_value` int(11) DEFAULT 5,
  `is_common` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `idx_category_common` (`category_id`,`is_common`),
  FULLTEXT KEY `item_name` (`item_name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| item_id | int(11) | NO | PRI |  | auto_increment |
| item_name | varchar(100) | NO | MUL |  |  |
| category_id | int(11) | NO | MUL |  |  |
| alternative_names | longtext | YES |  |  |  |
| description | text | YES |  |  |  |
| recycling_instructions | text | YES |  |  |  |
| environmental_impact_score | int(11) | YES |  | 50 |  |
| points_value | int(11) | YES |  | 5 |  |
| is_common | tinyint(1) | YES |  | 0 |  |
| created_at | timestamp | NO |  | current_timestamp() |  |

---

## Table: wp_actionscheduler_actions

**Row Count:** 10

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_actionscheduler_actions` (
  `action_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hook` varchar(191) NOT NULL,
  `status` varchar(20) NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `args` varchar(191) DEFAULT NULL,
  `schedule` longtext DEFAULT NULL,
  `group_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `extended_args` varchar(8000) DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `hook_status_scheduled_date_gmt` (`hook`(163),`status`,`scheduled_date_gmt`),
  KEY `status_scheduled_date_gmt` (`status`,`scheduled_date_gmt`),
  KEY `scheduled_date_gmt` (`scheduled_date_gmt`),
  KEY `args` (`args`),
  KEY `group_id` (`group_id`),
  KEY `last_attempt_gmt` (`last_attempt_gmt`),
  KEY `claim_id_status_scheduled_date_gmt` (`claim_id`,`status`,`scheduled_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| action_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| hook | varchar(191) | NO | MUL |  |  |
| status | varchar(20) | NO | MUL |  |  |
| scheduled_date_gmt | datetime | YES | MUL | 0000-00-00 00:00:00 |  |
| scheduled_date_local | datetime | YES |  | 0000-00-00 00:00:00 |  |
| priority | tinyint(3) unsigned | NO |  | 10 |  |
| args | varchar(191) | YES | MUL |  |  |
| schedule | longtext | YES |  |  |  |
| group_id | bigint(20) unsigned | NO | MUL | 0 |  |
| attempts | int(11) | NO |  | 0 |  |
| last_attempt_gmt | datetime | YES | MUL | 0000-00-00 00:00:00 |  |
| last_attempt_local | datetime | YES |  | 0000-00-00 00:00:00 |  |
| claim_id | bigint(20) unsigned | NO | MUL | 0 |  |
| extended_args | varchar(8000) | YES |  |  |  |

---

## Table: wp_actionscheduler_claims

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_actionscheduler_claims` (
  `claim_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`claim_id`),
  KEY `date_created_gmt` (`date_created_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| claim_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| date_created_gmt | datetime | YES | MUL | 0000-00-00 00:00:00 |  |

---

## Table: wp_actionscheduler_groups

**Row Count:** 5

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_actionscheduler_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `slug` (`slug`(191))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| group_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| slug | varchar(255) | NO | MUL |  |  |

---

## Table: wp_actionscheduler_logs

**Row Count:** 28

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_actionscheduler_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `log_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `log_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_id`),
  KEY `action_id` (`action_id`),
  KEY `log_date_gmt` (`log_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| log_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| action_id | bigint(20) unsigned | NO | MUL |  |  |
| message | text | NO |  |  |  |
| log_date_gmt | datetime | YES | MUL | 0000-00-00 00:00:00 |  |
| log_date_local | datetime | YES |  | 0000-00-00 00:00:00 |  |

---

## Table: wp_commentmeta

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| meta_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| comment_id | bigint(20) unsigned | NO | MUL | 0 |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | longtext | YES |  |  |  |

---

## Table: wp_comments

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10)),
  KEY `woo_idx_comment_type` (`comment_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| comment_ID | bigint(20) unsigned | NO | PRI |  | auto_increment |
| comment_post_ID | bigint(20) unsigned | NO | MUL | 0 |  |
| comment_author | tinytext | NO |  |  |  |
| comment_author_email | varchar(100) | NO | MUL |  |  |
| comment_author_url | varchar(200) | NO |  |  |  |
| comment_author_IP | varchar(100) | NO |  |  |  |
| comment_date | datetime | NO |  | 0000-00-00 00:00:00 |  |
| comment_date_gmt | datetime | NO | MUL | 0000-00-00 00:00:00 |  |
| comment_content | text | NO |  |  |  |
| comment_karma | int(11) | NO |  | 0 |  |
| comment_approved | varchar(20) | NO | MUL | 1 |  |
| comment_agent | varchar(255) | NO |  |  |  |
| comment_type | varchar(20) | NO | MUL | comment |  |
| comment_parent | bigint(20) unsigned | NO | MUL | 0 |  |
| user_id | bigint(20) unsigned | NO |  | 0 |  |

---

## Table: wp_ep_event_analytics

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_ep_event_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `metric_name` varchar(50) NOT NULL,
  `metric_value` decimal(15,4) NOT NULL,
  `recorded_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_metric` (`event_id`,`metric_name`,`recorded_date`),
  KEY `event_id` (`event_id`),
  KEY `recorded_date` (`recorded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| event_id | bigint(20) unsigned | NO | MUL |  |  |
| metric_name | varchar(50) | NO |  |  |  |
| metric_value | decimal(15,4) | NO |  |  |  |
| recorded_date | date | NO | MUL |  |  |

---

## Table: wp_ep_event_checkins

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_ep_event_checkins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registration_id` bigint(20) unsigned NOT NULL,
  `check_in_time` datetime DEFAULT current_timestamp(),
  `check_in_method` varchar(20) DEFAULT 'manual',
  `checked_in_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  KEY `check_in_time` (`check_in_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| registration_id | bigint(20) unsigned | NO | MUL |  |  |
| check_in_time | datetime | YES | MUL | current_timestamp() |  |
| check_in_method | varchar(20) | YES |  | manual |  |
| checked_in_by | bigint(20) unsigned | YES |  |  |  |
| notes | text | YES |  |  |  |

---

## Table: wp_ep_event_registrations

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_ep_event_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'registered',
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `check_in_date` datetime DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_event` (`user_id`,`event_id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| event_id | bigint(20) unsigned | NO | MUL |  |  |
| user_id | bigint(20) unsigned | NO | MUL |  |  |
| registration_date | datetime | YES |  | current_timestamp() |  |
| status | varchar(20) | YES | MUL | registered |  |
| payment_status | varchar(20) | YES |  | pending |  |
| payment_amount | decimal(10,2) | YES |  | 0.00 |  |
| check_in_date | datetime | YES |  |  |  |
| qr_code | varchar(255) | YES |  |  |  |
| notes | text | YES |  |  |  |

---

## Table: wp_links

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| link_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| link_url | varchar(255) | NO |  |  |  |
| link_name | varchar(255) | NO |  |  |  |
| link_image | varchar(255) | NO |  |  |  |
| link_target | varchar(25) | NO |  |  |  |
| link_description | varchar(255) | NO |  |  |  |
| link_visible | varchar(20) | NO | MUL | Y |  |
| link_owner | bigint(20) unsigned | NO |  | 1 |  |
| link_rating | int(11) | NO |  | 0 |  |
| link_updated | datetime | NO |  | 0000-00-00 00:00:00 |  |
| link_rel | varchar(255) | NO |  |  |  |
| link_notes | mediumtext | NO |  |  |  |
| link_rss | varchar(255) | NO |  |  |  |

---

## Table: wp_options

**Row Count:** 376

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=460 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| option_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| option_name | varchar(191) | NO | UNI |  |  |
| option_value | longtext | NO |  |  |  |
| autoload | varchar(20) | NO | MUL | yes |  |

---

## Table: wp_postmeta

**Row Count:** 67

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| meta_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| post_id | bigint(20) unsigned | NO | MUL | 0 |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | longtext | YES |  |  |  |

---

## Table: wp_posts

**Row Count:** 22

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| ID | bigint(20) unsigned | NO | PRI |  | auto_increment |
| post_author | bigint(20) unsigned | NO | MUL | 0 |  |
| post_date | datetime | NO |  | 0000-00-00 00:00:00 |  |
| post_date_gmt | datetime | NO |  | 0000-00-00 00:00:00 |  |
| post_content | longtext | NO |  |  |  |
| post_title | text | NO |  |  |  |
| post_excerpt | text | NO |  |  |  |
| post_status | varchar(20) | NO |  | publish |  |
| comment_status | varchar(20) | NO |  | open |  |
| ping_status | varchar(20) | NO |  | open |  |
| post_password | varchar(255) | NO |  |  |  |
| post_name | varchar(200) | NO | MUL |  |  |
| to_ping | text | NO |  |  |  |
| pinged | text | NO |  |  |  |
| post_modified | datetime | NO |  | 0000-00-00 00:00:00 |  |
| post_modified_gmt | datetime | NO |  | 0000-00-00 00:00:00 |  |
| post_content_filtered | longtext | NO |  |  |  |
| post_parent | bigint(20) unsigned | NO | MUL | 0 |  |
| guid | varchar(255) | NO |  |  |  |
| menu_order | int(11) | NO |  | 0 |  |
| post_type | varchar(20) | NO | MUL | post |  |
| post_mime_type | varchar(100) | NO |  |  |  |
| comment_count | bigint(20) | NO |  | 0 |  |

---

## Table: wp_security_logs

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_security_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime DEFAULT current_timestamp(),
  `event_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `severity` varchar(20) DEFAULT 'info',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `timestamp` (`timestamp`),
  KEY `severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) | NO | PRI |  | auto_increment |
| timestamp | datetime | YES | MUL | current_timestamp() |  |
| event_type | varchar(50) | NO | MUL |  |  |
| message | text | NO |  |  |  |
| severity | varchar(20) | YES | MUL | info |  |
| ip_address | varchar(45) | YES |  |  |  |
| user_agent | text | YES |  |  |  |

---

## Table: wp_term_relationships

**Row Count:** 11

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| object_id | bigint(20) unsigned | NO | PRI | 0 |  |
| term_taxonomy_id | bigint(20) unsigned | NO | PRI | 0 |  |
| term_order | int(11) | NO |  | 0 |  |

---

## Table: wp_term_taxonomy

**Row Count:** 19

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| term_taxonomy_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| term_id | bigint(20) unsigned | NO | MUL | 0 |  |
| taxonomy | varchar(32) | NO | MUL |  |  |
| description | longtext | NO |  |  |  |
| parent | bigint(20) unsigned | NO |  | 0 |  |
| count | bigint(20) | NO |  | 0 |  |

---

## Table: wp_termmeta

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| meta_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| term_id | bigint(20) unsigned | NO | MUL | 0 |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | longtext | YES |  |  |  |

---

## Table: wp_terms

**Row Count:** 19

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| term_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| name | varchar(200) | NO | MUL |  |  |
| slug | varchar(200) | NO | MUL |  |  |
| term_group | bigint(10) | NO |  | 0 |  |

---

## Table: wp_usermeta

**Row Count:** 16

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| umeta_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| user_id | bigint(20) unsigned | NO | MUL | 0 |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | longtext | YES |  |  |  |

---

## Table: wp_users

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| ID | bigint(20) unsigned | NO | PRI |  | auto_increment |
| user_login | varchar(60) | NO | MUL |  |  |
| user_pass | varchar(255) | NO |  |  |  |
| user_nicename | varchar(50) | NO | MUL |  |  |
| user_email | varchar(100) | NO | MUL |  |  |
| user_url | varchar(100) | NO |  |  |  |
| user_registered | datetime | NO |  | 0000-00-00 00:00:00 |  |
| user_activation_key | varchar(255) | NO |  |  |  |
| user_status | int(11) | NO |  | 0 |  |
| display_name | varchar(250) | NO |  |  |  |

---

## Table: wp_wc_admin_note_actions

**Row Count:** 91

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_admin_note_actions` (
  `action_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `query` longtext NOT NULL,
  `status` varchar(255) NOT NULL,
  `actioned_text` varchar(255) NOT NULL,
  `nonce_action` varchar(255) DEFAULT NULL,
  `nonce_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `note_id` (`note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| action_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| note_id | bigint(20) unsigned | NO | MUL |  |  |
| name | varchar(255) | NO |  |  |  |
| label | varchar(255) | NO |  |  |  |
| query | longtext | NO |  |  |  |
| status | varchar(255) | NO |  |  |  |
| actioned_text | varchar(255) | NO |  |  |  |
| nonce_action | varchar(255) | YES |  |  |  |
| nonce_name | varchar(255) | YES |  |  |  |

---

## Table: wp_wc_admin_notes

**Row Count:** 63

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_admin_notes` (
  `note_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `locale` varchar(20) NOT NULL,
  `title` longtext NOT NULL,
  `content` longtext NOT NULL,
  `content_data` longtext DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `source` varchar(200) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_reminder` datetime DEFAULT NULL,
  `is_snoozable` tinyint(1) NOT NULL DEFAULT 0,
  `layout` varchar(20) NOT NULL DEFAULT '',
  `image` varchar(200) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `icon` varchar(200) NOT NULL DEFAULT 'info',
  PRIMARY KEY (`note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| note_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| name | varchar(255) | NO |  |  |  |
| type | varchar(20) | NO |  |  |  |
| locale | varchar(20) | NO |  |  |  |
| title | longtext | NO |  |  |  |
| content | longtext | NO |  |  |  |
| content_data | longtext | YES |  |  |  |
| status | varchar(200) | NO |  |  |  |
| source | varchar(200) | NO |  |  |  |
| date_created | datetime | NO |  | 0000-00-00 00:00:00 |  |
| date_reminder | datetime | YES |  |  |  |
| is_snoozable | tinyint(1) | NO |  | 0 |  |
| layout | varchar(20) | NO |  |  |  |
| image | varchar(200) | YES |  |  |  |
| is_deleted | tinyint(1) | NO |  | 0 |  |
| is_read | tinyint(1) | NO |  | 0 |  |
| icon | varchar(200) | NO |  | info |  |

---

## Table: wp_wc_category_lookup

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_category_lookup` (
  `category_tree_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`category_tree_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| category_tree_id | bigint(20) unsigned | NO | PRI |  |  |
| category_id | bigint(20) unsigned | NO | PRI |  |  |

---

## Table: wp_wc_customer_lookup

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_customer_lookup` (
  `customer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `username` varchar(60) NOT NULL DEFAULT '',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_last_active` timestamp NULL DEFAULT NULL,
  `date_registered` timestamp NULL DEFAULT NULL,
  `country` char(2) NOT NULL DEFAULT '',
  `postcode` varchar(20) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `state` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| customer_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| user_id | bigint(20) unsigned | YES | UNI |  |  |
| username | varchar(60) | NO |  |  |  |
| first_name | varchar(255) | NO |  |  |  |
| last_name | varchar(255) | NO |  |  |  |
| email | varchar(100) | YES | MUL |  |  |
| date_last_active | timestamp | YES |  |  |  |
| date_registered | timestamp | YES |  |  |  |
| country | char(2) | NO |  |  |  |
| postcode | varchar(20) | NO |  |  |  |
| city | varchar(100) | NO |  |  |  |
| state | varchar(100) | NO |  |  |  |

---

## Table: wp_wc_download_log

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_download_log` (
  `download_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_ip_address` varchar(100) DEFAULT '',
  PRIMARY KEY (`download_log_id`),
  KEY `permission_id` (`permission_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| download_log_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| timestamp | datetime | NO | MUL |  |  |
| permission_id | bigint(20) unsigned | NO | MUL |  |  |
| user_id | bigint(20) unsigned | YES |  |  |  |
| user_ip_address | varchar(100) | YES |  |  |  |

---

## Table: wp_wc_order_addresses

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_order_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `address_type` varchar(20) DEFAULT NULL,
  `first_name` text DEFAULT NULL,
  `last_name` text DEFAULT NULL,
  `company` text DEFAULT NULL,
  `address_1` text DEFAULT NULL,
  `address_2` text DEFAULT NULL,
  `city` text DEFAULT NULL,
  `state` text DEFAULT NULL,
  `postcode` text DEFAULT NULL,
  `country` text DEFAULT NULL,
  `email` varchar(320) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_type_order_id` (`address_type`,`order_id`),
  KEY `order_id` (`order_id`),
  KEY `email` (`email`(191)),
  KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| order_id | bigint(20) unsigned | NO | MUL |  |  |
| address_type | varchar(20) | YES | MUL |  |  |
| first_name | text | YES |  |  |  |
| last_name | text | YES |  |  |  |
| company | text | YES |  |  |  |
| address_1 | text | YES |  |  |  |
| address_2 | text | YES |  |  |  |
| city | text | YES |  |  |  |
| state | text | YES |  |  |  |
| postcode | text | YES |  |  |  |
| country | text | YES |  |  |  |
| email | varchar(320) | YES | MUL |  |  |
| phone | varchar(100) | YES | MUL |  |  |

---

## Table: wp_wc_order_coupon_lookup

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_order_coupon_lookup` (
  `order_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `discount_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_id`,`coupon_id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_id | bigint(20) unsigned | NO | PRI |  |  |
| coupon_id | bigint(20) | NO | PRI |  |  |
| date_created | datetime | NO | MUL | 0000-00-00 00:00:00 |  |
| discount_amount | double | NO |  | 0 |  |

---

## Table: wp_wc_order_operational_data

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_order_operational_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `created_via` varchar(100) DEFAULT NULL,
  `woocommerce_version` varchar(20) DEFAULT NULL,
  `prices_include_tax` tinyint(1) DEFAULT NULL,
  `coupon_usages_are_counted` tinyint(1) DEFAULT NULL,
  `download_permission_granted` tinyint(1) DEFAULT NULL,
  `cart_hash` varchar(100) DEFAULT NULL,
  `new_order_email_sent` tinyint(1) DEFAULT NULL,
  `order_key` varchar(100) DEFAULT NULL,
  `order_stock_reduced` tinyint(1) DEFAULT NULL,
  `date_paid_gmt` datetime DEFAULT NULL,
  `date_completed_gmt` datetime DEFAULT NULL,
  `shipping_tax_amount` decimal(26,8) DEFAULT NULL,
  `shipping_total_amount` decimal(26,8) DEFAULT NULL,
  `discount_tax_amount` decimal(26,8) DEFAULT NULL,
  `discount_total_amount` decimal(26,8) DEFAULT NULL,
  `recorded_sales` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `order_key` (`order_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| order_id | bigint(20) unsigned | YES | UNI |  |  |
| created_via | varchar(100) | YES |  |  |  |
| woocommerce_version | varchar(20) | YES |  |  |  |
| prices_include_tax | tinyint(1) | YES |  |  |  |
| coupon_usages_are_counted | tinyint(1) | YES |  |  |  |
| download_permission_granted | tinyint(1) | YES |  |  |  |
| cart_hash | varchar(100) | YES |  |  |  |
| new_order_email_sent | tinyint(1) | YES |  |  |  |
| order_key | varchar(100) | YES | MUL |  |  |
| order_stock_reduced | tinyint(1) | YES |  |  |  |
| date_paid_gmt | datetime | YES |  |  |  |
| date_completed_gmt | datetime | YES |  |  |  |
| shipping_tax_amount | decimal(26,8) | YES |  |  |  |
| shipping_total_amount | decimal(26,8) | YES |  |  |  |
| discount_tax_amount | decimal(26,8) | YES |  |  |  |
| discount_total_amount | decimal(26,8) | YES |  |  |  |
| recorded_sales | tinyint(1) | YES |  |  |  |

---

## Table: wp_wc_order_product_lookup

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_order_product_lookup` (
  `order_item_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variation_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `product_qty` int(11) NOT NULL,
  `product_net_revenue` double NOT NULL DEFAULT 0,
  `product_gross_revenue` double NOT NULL DEFAULT 0,
  `coupon_amount` double NOT NULL DEFAULT 0,
  `tax_amount` double NOT NULL DEFAULT 0,
  `shipping_amount` double NOT NULL DEFAULT 0,
  `shipping_tax_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_item_id | bigint(20) unsigned | NO | PRI |  |  |
| order_id | bigint(20) unsigned | NO | MUL |  |  |
| product_id | bigint(20) unsigned | NO | MUL |  |  |
| variation_id | bigint(20) unsigned | NO |  |  |  |
| customer_id | bigint(20) unsigned | YES | MUL |  |  |
| date_created | datetime | NO | MUL | 0000-00-00 00:00:00 |  |
| product_qty | int(11) | NO |  |  |  |
| product_net_revenue | double | NO |  | 0 |  |
| product_gross_revenue | double | NO |  | 0 |  |
| coupon_amount | double | NO |  | 0 |  |
| tax_amount | double | NO |  | 0 |  |
| shipping_amount | double | NO |  | 0 |  |
| shipping_tax_amount | double | NO |  | 0 |  |

---

## Table: wp_wc_order_stats

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_order_stats` (
  `order_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_paid` datetime DEFAULT '0000-00-00 00:00:00',
  `date_completed` datetime DEFAULT '0000-00-00 00:00:00',
  `num_items_sold` int(11) NOT NULL DEFAULT 0,
  `total_sales` double NOT NULL DEFAULT 0,
  `tax_total` double NOT NULL DEFAULT 0,
  `shipping_total` double NOT NULL DEFAULT 0,
  `net_total` double NOT NULL DEFAULT 0,
  `returning_customer` tinyint(1) DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `date_created` (`date_created`),
  KEY `customer_id` (`customer_id`),
  KEY `status` (`status`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_id | bigint(20) unsigned | NO | PRI |  |  |
| parent_id | bigint(20) unsigned | NO |  | 0 |  |
| date_created | datetime | NO | MUL | 0000-00-00 00:00:00 |  |
| date_created_gmt | datetime | NO |  | 0000-00-00 00:00:00 |  |
| date_paid | datetime | YES |  | 0000-00-00 00:00:00 |  |
| date_completed | datetime | YES |  | 0000-00-00 00:00:00 |  |
| num_items_sold | int(11) | NO |  | 0 |  |
| total_sales | double | NO |  | 0 |  |
| tax_total | double | NO |  | 0 |  |
| shipping_total | double | NO |  | 0 |  |
| net_total | double | NO |  | 0 |  |
| returning_customer | tinyint(1) | YES |  |  |  |
| status | varchar(200) | NO | MUL |  |  |
| customer_id | bigint(20) unsigned | NO | MUL |  |  |

---

## Table: wp_wc_order_tax_lookup

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_order_tax_lookup` (
  `order_id` bigint(20) unsigned NOT NULL,
  `tax_rate_id` bigint(20) unsigned NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `shipping_tax` double NOT NULL DEFAULT 0,
  `order_tax` double NOT NULL DEFAULT 0,
  `total_tax` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_id`,`tax_rate_id`),
  KEY `tax_rate_id` (`tax_rate_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_id | bigint(20) unsigned | NO | PRI |  |  |
| tax_rate_id | bigint(20) unsigned | NO | PRI |  |  |
| date_created | datetime | NO | MUL | 0000-00-00 00:00:00 |  |
| shipping_tax | double | NO |  | 0 |  |
| order_tax | double | NO |  | 0 |  |
| total_tax | double | NO |  | 0 |  |

---

## Table: wp_wc_orders

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_orders` (
  `id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `tax_amount` decimal(26,8) DEFAULT NULL,
  `total_amount` decimal(26,8) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `billing_email` varchar(320) DEFAULT NULL,
  `date_created_gmt` datetime DEFAULT NULL,
  `date_updated_gmt` datetime DEFAULT NULL,
  `parent_order_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_method_title` text DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `date_created` (`date_created_gmt`),
  KEY `customer_id_billing_email` (`customer_id`,`billing_email`(171)),
  KEY `billing_email` (`billing_email`(191)),
  KEY `type_status_date` (`type`,`status`,`date_created_gmt`),
  KEY `parent_order_id` (`parent_order_id`),
  KEY `date_updated` (`date_updated_gmt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  |  |
| status | varchar(20) | YES | MUL |  |  |
| currency | varchar(10) | YES |  |  |  |
| type | varchar(20) | YES | MUL |  |  |
| tax_amount | decimal(26,8) | YES |  |  |  |
| total_amount | decimal(26,8) | YES |  |  |  |
| customer_id | bigint(20) unsigned | YES | MUL |  |  |
| billing_email | varchar(320) | YES | MUL |  |  |
| date_created_gmt | datetime | YES | MUL |  |  |
| date_updated_gmt | datetime | YES | MUL |  |  |
| parent_order_id | bigint(20) unsigned | YES | MUL |  |  |
| payment_method | varchar(100) | YES |  |  |  |
| payment_method_title | text | YES |  |  |  |
| transaction_id | varchar(100) | YES |  |  |  |
| ip_address | varchar(100) | YES |  |  |  |
| user_agent | text | YES |  |  |  |
| customer_note | text | YES |  |  |  |

---

## Table: wp_wc_orders_meta

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_orders_meta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_key_value` (`meta_key`(100),`meta_value`(82)),
  KEY `order_id_meta_key_meta_value` (`order_id`,`meta_key`(100),`meta_value`(82))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| order_id | bigint(20) unsigned | YES | MUL |  |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | text | YES |  |  |  |

---

## Table: wp_wc_product_attributes_lookup

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_product_attributes_lookup` (
  `product_id` bigint(20) NOT NULL,
  `product_or_parent_id` bigint(20) NOT NULL,
  `taxonomy` varchar(32) NOT NULL,
  `term_id` bigint(20) NOT NULL,
  `is_variation_attribute` tinyint(1) NOT NULL,
  `in_stock` tinyint(1) NOT NULL,
  PRIMARY KEY (`product_or_parent_id`,`term_id`,`product_id`,`taxonomy`),
  KEY `is_variation_attribute_term_id` (`is_variation_attribute`,`term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| product_id | bigint(20) | NO | PRI |  |  |
| product_or_parent_id | bigint(20) | NO | PRI |  |  |
| taxonomy | varchar(32) | NO | PRI |  |  |
| term_id | bigint(20) | NO | PRI |  |  |
| is_variation_attribute | tinyint(1) | NO | MUL |  |  |
| in_stock | tinyint(1) | NO |  |  |  |

---

## Table: wp_wc_product_download_directories

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_product_download_directories` (
  `url_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`url_id`),
  KEY `url` (`url`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| url_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| url | varchar(256) | NO | MUL |  |  |
| enabled | tinyint(1) | NO |  | 0 |  |

---

## Table: wp_wc_product_meta_lookup

**Row Count:** 3

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_product_meta_lookup` (
  `product_id` bigint(20) NOT NULL,
  `sku` varchar(100) DEFAULT '',
  `global_unique_id` varchar(100) DEFAULT '',
  `virtual` tinyint(1) DEFAULT 0,
  `downloadable` tinyint(1) DEFAULT 0,
  `min_price` decimal(19,4) DEFAULT NULL,
  `max_price` decimal(19,4) DEFAULT NULL,
  `onsale` tinyint(1) DEFAULT 0,
  `stock_quantity` double DEFAULT NULL,
  `stock_status` varchar(100) DEFAULT 'instock',
  `rating_count` bigint(20) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_sales` bigint(20) DEFAULT 0,
  `tax_status` varchar(100) DEFAULT 'taxable',
  `tax_class` varchar(100) DEFAULT '',
  PRIMARY KEY (`product_id`),
  KEY `virtual` (`virtual`),
  KEY `downloadable` (`downloadable`),
  KEY `stock_status` (`stock_status`),
  KEY `stock_quantity` (`stock_quantity`),
  KEY `onsale` (`onsale`),
  KEY `min_max_price` (`min_price`,`max_price`),
  KEY `sku` (`sku`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| product_id | bigint(20) | NO | PRI |  |  |
| sku | varchar(100) | YES | MUL |  |  |
| global_unique_id | varchar(100) | YES |  |  |  |
| virtual | tinyint(1) | YES | MUL | 0 |  |
| downloadable | tinyint(1) | YES | MUL | 0 |  |
| min_price | decimal(19,4) | YES | MUL |  |  |
| max_price | decimal(19,4) | YES |  |  |  |
| onsale | tinyint(1) | YES | MUL | 0 |  |
| stock_quantity | double | YES | MUL |  |  |
| stock_status | varchar(100) | YES | MUL | instock |  |
| rating_count | bigint(20) | YES |  | 0 |  |
| average_rating | decimal(3,2) | YES |  | 0.00 |  |
| total_sales | bigint(20) | YES |  | 0 |  |
| tax_status | varchar(100) | YES |  | taxable |  |
| tax_class | varchar(100) | YES |  |  |  |

---

## Table: wp_wc_rate_limits

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_rate_limits` (
  `rate_limit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rate_limit_key` varchar(200) NOT NULL,
  `rate_limit_expiry` bigint(20) unsigned NOT NULL,
  `rate_limit_remaining` smallint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rate_limit_id`),
  UNIQUE KEY `rate_limit_key` (`rate_limit_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| rate_limit_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| rate_limit_key | varchar(200) | NO | UNI |  |  |
| rate_limit_expiry | bigint(20) unsigned | NO |  |  |  |
| rate_limit_remaining | smallint(10) | NO |  | 0 |  |

---

## Table: wp_wc_reserved_stock

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_reserved_stock` (
  `order_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `stock_quantity` double NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`order_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_id | bigint(20) | NO | PRI |  |  |
| product_id | bigint(20) | NO | PRI |  |  |
| stock_quantity | double | NO |  | 0 |  |
| timestamp | datetime | NO |  | 0000-00-00 00:00:00 |  |
| expires | datetime | NO |  | 0000-00-00 00:00:00 |  |

---

## Table: wp_wc_tax_rate_classes

**Row Count:** 2

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_tax_rate_classes` (
  `tax_rate_class_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`tax_rate_class_id`),
  UNIQUE KEY `slug` (`slug`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| tax_rate_class_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| name | varchar(200) | NO |  |  |  |
| slug | varchar(200) | NO | UNI |  |  |

---

## Table: wp_wc_webhooks

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_wc_webhooks` (
  `webhook_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(200) NOT NULL,
  `name` text NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `delivery_url` text NOT NULL,
  `secret` text NOT NULL,
  `topic` varchar(200) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `api_version` smallint(4) NOT NULL,
  `failure_count` smallint(10) NOT NULL DEFAULT 0,
  `pending_delivery` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`webhook_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| webhook_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| status | varchar(200) | NO |  |  |  |
| name | text | NO |  |  |  |
| user_id | bigint(20) unsigned | NO | MUL |  |  |
| delivery_url | text | NO |  |  |  |
| secret | text | NO |  |  |  |
| topic | varchar(200) | NO |  |  |  |
| date_created | datetime | NO |  | 0000-00-00 00:00:00 |  |
| date_created_gmt | datetime | NO |  | 0000-00-00 00:00:00 |  |
| date_modified | datetime | NO |  | 0000-00-00 00:00:00 |  |
| date_modified_gmt | datetime | NO |  | 0000-00-00 00:00:00 |  |
| api_version | smallint(4) | NO |  |  |  |
| failure_count | smallint(10) | NO |  | 0 |  |
| pending_delivery | tinyint(1) | NO |  | 0 |  |

---

## Table: wp_woocommerce_api_keys

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_api_keys` (
  `key_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `permissions` varchar(10) NOT NULL,
  `consumer_key` char(64) NOT NULL,
  `consumer_secret` char(43) NOT NULL,
  `nonces` longtext DEFAULT NULL,
  `truncated_key` char(7) NOT NULL,
  `last_access` datetime DEFAULT NULL,
  PRIMARY KEY (`key_id`),
  KEY `consumer_key` (`consumer_key`),
  KEY `consumer_secret` (`consumer_secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| key_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| user_id | bigint(20) unsigned | NO |  |  |  |
| description | varchar(200) | YES |  |  |  |
| permissions | varchar(10) | NO |  |  |  |
| consumer_key | char(64) | NO | MUL |  |  |
| consumer_secret | char(43) | NO | MUL |  |  |
| nonces | longtext | YES |  |  |  |
| truncated_key | char(7) | NO |  |  |  |
| last_access | datetime | YES |  |  |  |

---

## Table: wp_woocommerce_attribute_taxonomies

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_attribute_taxonomies` (
  `attribute_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_name` varchar(200) NOT NULL,
  `attribute_label` varchar(200) DEFAULT NULL,
  `attribute_type` varchar(20) NOT NULL,
  `attribute_orderby` varchar(20) NOT NULL,
  `attribute_public` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`attribute_id`),
  KEY `attribute_name` (`attribute_name`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| attribute_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| attribute_name | varchar(200) | NO | MUL |  |  |
| attribute_label | varchar(200) | YES |  |  |  |
| attribute_type | varchar(20) | NO |  |  |  |
| attribute_orderby | varchar(20) | NO |  |  |  |
| attribute_public | int(1) | NO |  | 1 |  |

---

## Table: wp_woocommerce_downloadable_product_permissions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_downloadable_product_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `download_id` varchar(36) NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `order_key` varchar(200) NOT NULL,
  `user_email` varchar(200) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `downloads_remaining` varchar(9) DEFAULT NULL,
  `access_granted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access_expires` datetime DEFAULT NULL,
  `download_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_id`),
  KEY `download_order_key_product` (`product_id`,`order_id`,`order_key`(16),`download_id`),
  KEY `download_order_product` (`download_id`,`order_id`,`product_id`),
  KEY `order_id` (`order_id`),
  KEY `user_order_remaining_expires` (`user_id`,`order_id`,`downloads_remaining`,`access_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| permission_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| download_id | varchar(36) | NO | MUL |  |  |
| product_id | bigint(20) unsigned | NO | MUL |  |  |
| order_id | bigint(20) unsigned | NO | MUL | 0 |  |
| order_key | varchar(200) | NO |  |  |  |
| user_email | varchar(200) | NO |  |  |  |
| user_id | bigint(20) unsigned | YES | MUL |  |  |
| downloads_remaining | varchar(9) | YES |  |  |  |
| access_granted | datetime | NO |  | 0000-00-00 00:00:00 |  |
| access_expires | datetime | YES |  |  |  |
| download_count | bigint(20) unsigned | NO |  | 0 |  |

---

## Table: wp_woocommerce_log

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_log` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `level` smallint(4) NOT NULL,
  `source` varchar(200) NOT NULL,
  `message` longtext NOT NULL,
  `context` longtext DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| log_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| timestamp | datetime | NO |  |  |  |
| level | smallint(4) | NO | MUL |  |  |
| source | varchar(200) | NO |  |  |  |
| message | longtext | NO |  |  |  |
| context | longtext | YES |  |  |  |

---

## Table: wp_woocommerce_order_itemmeta

**Row Count:** 9

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_order_itemmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `meta_key` (`meta_key`(32))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| meta_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| order_item_id | bigint(20) unsigned | NO | MUL |  |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | longtext | YES |  |  |  |

---

## Table: wp_woocommerce_order_items

**Row Count:** 1

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_order_items` (
  `order_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_name` text NOT NULL,
  `order_item_type` varchar(200) NOT NULL DEFAULT '',
  `order_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| order_item_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| order_item_name | text | NO |  |  |  |
| order_item_type | varchar(200) | NO |  |  |  |
| order_id | bigint(20) unsigned | NO | MUL |  |  |

---

## Table: wp_woocommerce_payment_tokenmeta

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_payment_tokenmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_token_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `payment_token_id` (`payment_token_id`),
  KEY `meta_key` (`meta_key`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| meta_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| payment_token_id | bigint(20) unsigned | NO | MUL |  |  |
| meta_key | varchar(255) | YES | MUL |  |  |
| meta_value | longtext | YES |  |  |  |

---

## Table: wp_woocommerce_payment_tokens

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_payment_tokens` (
  `token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gateway_id` varchar(200) NOT NULL,
  `token` text NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `type` varchar(200) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`token_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| token_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| gateway_id | varchar(200) | NO |  |  |  |
| token | text | NO |  |  |  |
| user_id | bigint(20) unsigned | NO | MUL | 0 |  |
| type | varchar(200) | NO |  |  |  |
| is_default | tinyint(1) | NO |  | 0 |  |

---

## Table: wp_woocommerce_sessions

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_sessions` (
  `session_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(32) NOT NULL,
  `session_value` longtext NOT NULL,
  `session_expiry` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_key` (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| session_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| session_key | char(32) | NO | UNI |  |  |
| session_value | longtext | NO |  |  |  |
| session_expiry | bigint(20) unsigned | NO |  |  |  |

---

## Table: wp_woocommerce_shipping_zone_locations

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_shipping_zone_locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint(20) unsigned NOT NULL,
  `location_code` varchar(200) NOT NULL,
  `location_type` varchar(40) NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `zone_id` (`zone_id`),
  KEY `location_type_code` (`location_type`(10),`location_code`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| location_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| zone_id | bigint(20) unsigned | NO | MUL |  |  |
| location_code | varchar(200) | NO |  |  |  |
| location_type | varchar(40) | NO | MUL |  |  |

---

## Table: wp_woocommerce_shipping_zone_methods

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_shipping_zone_methods` (
  `zone_id` bigint(20) unsigned NOT NULL,
  `instance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `method_id` varchar(200) NOT NULL,
  `method_order` bigint(20) unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| zone_id | bigint(20) unsigned | NO |  |  |  |
| instance_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| method_id | varchar(200) | NO |  |  |  |
| method_order | bigint(20) unsigned | NO |  |  |  |
| is_enabled | tinyint(1) | NO |  | 1 |  |

---

## Table: wp_woocommerce_shipping_zones

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_shipping_zones` (
  `zone_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(200) NOT NULL,
  `zone_order` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| zone_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| zone_name | varchar(200) | NO |  |  |  |
| zone_order | bigint(20) unsigned | NO |  |  |  |

---

## Table: wp_woocommerce_tax_rate_locations

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_tax_rate_locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_code` varchar(200) NOT NULL,
  `tax_rate_id` bigint(20) unsigned NOT NULL,
  `location_type` varchar(40) NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `tax_rate_id` (`tax_rate_id`),
  KEY `location_type_code` (`location_type`(10),`location_code`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| location_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| location_code | varchar(200) | NO |  |  |  |
| tax_rate_id | bigint(20) unsigned | NO | MUL |  |  |
| location_type | varchar(40) | NO | MUL |  |  |

---

## Table: wp_woocommerce_tax_rates

**Row Count:** 0

### CREATE TABLE Statement

```sql
CREATE TABLE `wp_woocommerce_tax_rates` (
  `tax_rate_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tax_rate_country` varchar(2) NOT NULL DEFAULT '',
  `tax_rate_state` varchar(200) NOT NULL DEFAULT '',
  `tax_rate` varchar(8) NOT NULL DEFAULT '',
  `tax_rate_name` varchar(200) NOT NULL DEFAULT '',
  `tax_rate_priority` bigint(20) unsigned NOT NULL,
  `tax_rate_compound` int(1) NOT NULL DEFAULT 0,
  `tax_rate_shipping` int(1) NOT NULL DEFAULT 1,
  `tax_rate_order` bigint(20) unsigned NOT NULL,
  `tax_rate_class` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`tax_rate_id`),
  KEY `tax_rate_country` (`tax_rate_country`),
  KEY `tax_rate_state` (`tax_rate_state`(2)),
  KEY `tax_rate_class` (`tax_rate_class`(10)),
  KEY `tax_rate_priority` (`tax_rate_priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Table Structure

| Column | Type | Null | Key | Default | Extra |
|--------|------|------|-----|---------|-------|
| tax_rate_id | bigint(20) unsigned | NO | PRI |  | auto_increment |
| tax_rate_country | varchar(2) | NO | MUL |  |  |
| tax_rate_state | varchar(200) | NO | MUL |  |  |
| tax_rate | varchar(8) | NO |  |  |  |
| tax_rate_name | varchar(200) | NO |  |  |  |
| tax_rate_priority | bigint(20) unsigned | NO | MUL |  |  |
| tax_rate_compound | int(1) | NO |  | 0 |  |
| tax_rate_shipping | int(1) | NO |  | 1 |  |
| tax_rate_order | bigint(20) unsigned | NO |  |  |  |
| tax_rate_class | varchar(200) | NO | MUL |  |  |

---

## Summary

- **Total Tables:** 174
- **Database:** `environmental_platform`
- **Export Date:** 2025-06-08 04:50:14

