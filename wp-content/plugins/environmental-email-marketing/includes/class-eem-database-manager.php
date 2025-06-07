<?php
/**
 * Database Manager for Environmental Email Marketing
 *
 * @package EnvironmentalEmailMarketing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EEM Database Manager Class
 */
class EEM_Database_Manager {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Create all required database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Subscribers table
        $subscribers_table = $wpdb->prefix . 'eem_subscribers';
        $subscribers_sql = "CREATE TABLE $subscribers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            status enum('subscribed','unsubscribed','pending','bounced','complained') DEFAULT 'pending',
            source varchar(100) DEFAULT 'website',
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            confirmation_token varchar(255) DEFAULT '',
            confirmed_at datetime DEFAULT NULL,
            last_sent datetime DEFAULT NULL,
            bounce_count int(11) DEFAULT 0,
            complaint_count int(11) DEFAULT 0,
            environmental_score int(11) DEFAULT 0,
            carbon_footprint decimal(10,2) DEFAULT 0.00,
            eco_preferences longtext DEFAULT '',
            gdpr_consent tinyint(1) DEFAULT 0,
            gdpr_consent_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY source (source),
            KEY confirmed_at (confirmed_at),
            KEY environmental_score (environmental_score)
        ) $charset_collate;";
        
        // Lists table
        $lists_table = $wpdb->prefix . 'eem_lists';
        $lists_sql = "CREATE TABLE $lists_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT '',
            type enum('newsletter','campaign','automation','environmental') DEFAULT 'newsletter',
            status enum('active','inactive') DEFAULT 'active',
            double_optin tinyint(1) DEFAULT 1,
            welcome_email tinyint(1) DEFAULT 1,
            environmental_focus varchar(100) DEFAULT '',
            target_audience text DEFAULT '',
            subscriber_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY type (type),
            KEY status (status),
            KEY environmental_focus (environmental_focus)
        ) $charset_collate;";
        
        // Subscriber lists relationship table
        $subscriber_lists_table = $wpdb->prefix . 'eem_subscriber_lists';
        $subscriber_lists_sql = "CREATE TABLE $subscriber_lists_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscriber_id bigint(20) unsigned NOT NULL,
            list_id bigint(20) unsigned NOT NULL,
            status enum('subscribed','unsubscribed','pending') DEFAULT 'subscribed',
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at datetime DEFAULT NULL,
            subscription_method varchar(100) DEFAULT 'manual',
            PRIMARY KEY (id),
            UNIQUE KEY subscriber_list (subscriber_id, list_id),
            KEY subscriber_id (subscriber_id),
            KEY list_id (list_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Campaigns table
        $campaigns_table = $wpdb->prefix . 'eem_campaigns';
        $campaigns_sql = "CREATE TABLE $campaigns_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            preview_text varchar(500) DEFAULT '',
            content longtext NOT NULL,
            type enum('newsletter','promotional','environmental','automation','ab_test') DEFAULT 'newsletter',
            status enum('draft','scheduled','sending','sent','paused','cancelled') DEFAULT 'draft',
            template_id bigint(20) unsigned DEFAULT NULL,
            list_ids text DEFAULT '',
            segment_conditions longtext DEFAULT '',
            environmental_theme varchar(100) DEFAULT '',
            eco_impact_data longtext DEFAULT '',
            carbon_offset_info text DEFAULT '',
            sender_name varchar(255) DEFAULT '',
            sender_email varchar(255) DEFAULT '',
            reply_to varchar(255) DEFAULT '',
            track_opens tinyint(1) DEFAULT 1,
            track_clicks tinyint(1) DEFAULT 1,
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            total_recipients int(11) DEFAULT 0,
            total_sent int(11) DEFAULT 0,
            total_delivered int(11) DEFAULT 0,
            total_opens int(11) DEFAULT 0,
            total_clicks int(11) DEFAULT 0,
            total_bounces int(11) DEFAULT 0,
            total_complaints int(11) DEFAULT 0,
            total_unsubscribes int(11) DEFAULT 0,
            environmental_engagement_score decimal(5,2) DEFAULT 0.00,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY type (type),
            KEY scheduled_at (scheduled_at),
            KEY environmental_theme (environmental_theme),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Email templates table
        $templates_table = $wpdb->prefix . 'eem_templates';
        $templates_sql = "CREATE TABLE $templates_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type enum('campaign','automation','system') DEFAULT 'campaign',
            category varchar(100) DEFAULT '',
            content longtext NOT NULL,
            css_styles longtext DEFAULT '',
            environmental_theme varchar(100) DEFAULT '',
            green_elements longtext DEFAULT '',
            sustainability_features text DEFAULT '',
            thumbnail varchar(500) DEFAULT '',
            is_default tinyint(1) DEFAULT 0,
            usage_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY category (category),
            KEY environmental_theme (environmental_theme),
            KEY is_default (is_default)
        ) $charset_collate;";
        
        // Automation sequences table
        $automations_table = $wpdb->prefix . 'eem_automations';
        $automations_sql = "CREATE TABLE $automations_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            trigger_type enum('welcome','purchase','birthday','anniversary','environmental_action','petition_signed','quiz_completed','event_registered') NOT NULL,
            trigger_conditions longtext DEFAULT '',
            status enum('active','paused','draft') DEFAULT 'draft',
            environmental_trigger varchar(100) DEFAULT '',
            eco_action_points int(11) DEFAULT 0,
            sustainability_goal text DEFAULT '',
            steps longtext NOT NULL,
            total_entered int(11) DEFAULT 0,
            total_completed int(11) DEFAULT 0,
            conversion_rate decimal(5,2) DEFAULT 0.00,
            environmental_impact_score decimal(5,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_type (trigger_type),
            KEY status (status),
            KEY environmental_trigger (environmental_trigger)
        ) $charset_collate;";
        
        // Automation subscribers table
        $automation_subscribers_table = $wpdb->prefix . 'eem_automation_subscribers';
        $automation_subscribers_sql = "CREATE TABLE $automation_subscribers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            automation_id bigint(20) unsigned NOT NULL,
            subscriber_id bigint(20) unsigned NOT NULL,
            status enum('active','completed','stopped','paused') DEFAULT 'active',
            current_step int(11) DEFAULT 0,
            next_send_at datetime DEFAULT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            trigger_data longtext DEFAULT '',
            environmental_context longtext DEFAULT '',
            PRIMARY KEY (id),
            KEY automation_id (automation_id),
            KEY subscriber_id (subscriber_id),
            KEY status (status),
            KEY next_send_at (next_send_at)
        ) $charset_collate;";
        
        // Email analytics table
        $analytics_table = $wpdb->prefix . 'eem_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned DEFAULT NULL,
            automation_id bigint(20) unsigned DEFAULT NULL,
            subscriber_id bigint(20) unsigned NOT NULL,
            event_type enum('sent','delivered','opened','clicked','bounced','complained','unsubscribed') NOT NULL,
            event_data longtext DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            link_url varchar(2048) DEFAULT '',
            environmental_action varchar(100) DEFAULT '',
            sustainability_engagement decimal(5,2) DEFAULT 0.00,
            carbon_awareness_score int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY automation_id (automation_id),
            KEY subscriber_id (subscriber_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY environmental_action (environmental_action)
        ) $charset_collate;";
        
        // A/B test table
        $ab_tests_table = $wpdb->prefix . 'eem_ab_tests';
        $ab_tests_sql = "CREATE TABLE $ab_tests_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            test_name varchar(255) NOT NULL,
            test_type enum('subject','content','sender','send_time','environmental_theme') NOT NULL,
            variant_a longtext NOT NULL,
            variant_b longtext NOT NULL,
            variant_c longtext DEFAULT NULL,
            traffic_split varchar(20) DEFAULT '50/50',
            winner_criteria enum('open_rate','click_rate','conversion_rate','environmental_engagement') DEFAULT 'open_rate',
            test_duration_hours int(11) DEFAULT 24,
            status enum('running','completed','stopped') DEFAULT 'running',
            winner varchar(10) DEFAULT NULL,
            environmental_impact_variant varchar(10) DEFAULT NULL,
            sustainability_score_a decimal(5,2) DEFAULT 0.00,
            sustainability_score_b decimal(5,2) DEFAULT 0.00,
            sustainability_score_c decimal(5,2) DEFAULT 0.00,
            results longtext DEFAULT '',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY test_type (test_type),
            KEY status (status)
        ) $charset_collate;";
        
        // Segments table
        $segments_table = $wpdb->prefix . 'eem_segments';
        $segments_sql = "CREATE TABLE $segments_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            conditions longtext NOT NULL,
            environmental_criteria longtext DEFAULT '',
            sustainability_score_min decimal(5,2) DEFAULT NULL,
            sustainability_score_max decimal(5,2) DEFAULT NULL,
            carbon_footprint_range varchar(50) DEFAULT '',
            eco_interest_tags text DEFAULT '',
            behavioral_triggers text DEFAULT '',
            dynamic_update tinyint(1) DEFAULT 1,
            subscriber_count int(11) DEFAULT 0,
            last_calculated datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY dynamic_update (dynamic_update),
            KEY last_calculated (last_calculated),
            KEY sustainability_score_min (sustainability_score_min)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create tables
        dbDelta($subscribers_sql);
        dbDelta($lists_sql);
        dbDelta($subscriber_lists_sql);
        dbDelta($campaigns_sql);
        dbDelta($templates_sql);
        dbDelta($automations_sql);
        dbDelta($automation_subscribers_sql);
        dbDelta($analytics_sql);
        dbDelta($ab_tests_sql);
        dbDelta($segments_sql);
        
        // Add foreign key constraints
        $this->add_foreign_keys();
        
        // Insert default data
        $this->insert_default_data();
        
        // Update database version
        update_option('eem_db_version', self::DB_VERSION);
    }
    
    /**
     * Add foreign key constraints
     */
    private function add_foreign_keys() {
        global $wpdb;
        
        // Add constraints (MySQL only)
        if ($wpdb->db_version() >= '5.6') {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eem_subscriber_lists 
                         ADD CONSTRAINT fk_subscriber_lists_subscriber 
                         FOREIGN KEY (subscriber_id) REFERENCES {$wpdb->prefix}eem_subscribers(id) ON DELETE CASCADE");
            
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eem_subscriber_lists 
                         ADD CONSTRAINT fk_subscriber_lists_list 
                         FOREIGN KEY (list_id) REFERENCES {$wpdb->prefix}eem_lists(id) ON DELETE CASCADE");
            
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eem_automation_subscribers 
                         ADD CONSTRAINT fk_automation_subscribers_automation 
                         FOREIGN KEY (automation_id) REFERENCES {$wpdb->prefix}eem_automations(id) ON DELETE CASCADE");
            
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eem_automation_subscribers 
                         ADD CONSTRAINT fk_automation_subscribers_subscriber 
                         FOREIGN KEY (subscriber_id) REFERENCES {$wpdb->prefix}eem_subscribers(id) ON DELETE CASCADE");
            
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eem_analytics 
                         ADD CONSTRAINT fk_analytics_subscriber 
                         FOREIGN KEY (subscriber_id) REFERENCES {$wpdb->prefix}eem_subscribers(id) ON DELETE CASCADE");
            
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eem_ab_tests 
                         ADD CONSTRAINT fk_ab_tests_campaign 
                         FOREIGN KEY (campaign_id) REFERENCES {$wpdb->prefix}eem_campaigns(id) ON DELETE CASCADE");
        }
    }
    
    /**
     * Insert default data
     */
    private function insert_default_data() {
        global $wpdb;
        
        // Insert default lists
        $default_lists = array(
            array(
                'name' => 'Environmental Newsletter',
                'slug' => 'environmental_newsletter',
                'description' => 'Main environmental newsletter with sustainability tips and eco-news',
                'type' => 'environmental',
                'environmental_focus' => 'general_sustainability',
                'target_audience' => 'General environmental enthusiasts'
            ),
            array(
                'name' => 'Climate Action Updates',
                'slug' => 'climate_action',
                'description' => 'Updates on climate action initiatives and campaigns',
                'type' => 'environmental',
                'environmental_focus' => 'climate_change',
                'target_audience' => 'Climate activists and supporters'
            ),
            array(
                'name' => 'Eco Product Alerts',
                'slug' => 'eco_products',
                'description' => 'Notifications about new sustainable products and green deals',
                'type' => 'newsletter',
                'environmental_focus' => 'sustainable_products',
                'target_audience' => 'Eco-conscious consumers'
            ),
            array(
                'name' => 'Petition Campaign Updates',
                'slug' => 'petition_updates',
                'description' => 'Updates on environmental petitions and campaign progress',
                'type' => 'campaign',
                'environmental_focus' => 'environmental_advocacy',
                'target_audience' => 'Environmental advocates'
            ),
            array(
                'name' => 'Green Event Notifications',
                'slug' => 'green_events',
                'description' => 'Notifications about environmental events and workshops',
                'type' => 'newsletter',
                'environmental_focus' => 'environmental_education',
                'target_audience' => 'Event participants'
            )
        );
        
        foreach ($default_lists as $list) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eem_lists WHERE slug = %s",
                $list['slug']
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'eem_lists', $list);
            }
        }
        
        // Insert default email templates
        $this->insert_default_templates();
        
        // Insert default automation sequences
        $this->insert_default_automations();
        
        // Insert default segments
        $this->insert_default_segments();
    }
    
    /**
     * Insert default email templates
     */
    private function insert_default_templates() {
        global $wpdb;
        
        $default_templates = array(
            array(
                'name' => 'Environmental Newsletter Template',
                'type' => 'campaign',
                'category' => 'newsletter',
                'environmental_theme' => 'nature_green',
                'green_elements' => 'Leaf icons, earth colors, sustainability badges',
                'sustainability_features' => 'Carbon footprint tracker, eco-tips section, green actions CTA',
                'content' => $this->get_newsletter_template_content(),
                'css_styles' => $this->get_newsletter_template_styles(),
                'is_default' => 1
            ),
            array(
                'name' => 'Welcome Email Template',
                'type' => 'automation',
                'category' => 'welcome',
                'environmental_theme' => 'earth_blue',
                'green_elements' => 'Welcome tree graphic, eco-friendly colors',
                'sustainability_features' => 'Environmental commitment statement, eco-actions guide',
                'content' => $this->get_welcome_template_content(),
                'css_styles' => $this->get_welcome_template_styles(),
                'is_default' => 1
            ),
            array(
                'name' => 'Climate Action Campaign',
                'type' => 'campaign',
                'category' => 'climate',
                'environmental_theme' => 'climate_red',
                'green_elements' => 'Climate urgency graphics, action buttons',
                'sustainability_features' => 'Petition links, donation buttons, action tracker',
                'content' => $this->get_climate_template_content(),
                'css_styles' => $this->get_climate_template_styles(),
                'is_default' => 1
            ),
            array(
                'name' => 'Eco Product Promotion',
                'type' => 'campaign',
                'category' => 'products',
                'environmental_theme' => 'product_green',
                'green_elements' => 'Product sustainability badges, eco-ratings',
                'sustainability_features' => 'Sustainability scores, carbon offset info, eco-certifications',
                'content' => $this->get_product_template_content(),
                'css_styles' => $this->get_product_template_styles(),
                'is_default' => 1
            )
        );
        
        foreach ($default_templates as $template) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eem_templates WHERE name = %s",
                $template['name']
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'eem_templates', $template);
            }
        }
    }
    
    /**
     * Insert default automation sequences
     */
    private function insert_default_automations() {
        global $wpdb;
        
        $default_automations = array(
            array(
                'name' => 'Welcome to Environmental Platform',
                'trigger_type' => 'welcome',
                'environmental_trigger' => 'newsletter_signup',
                'eco_action_points' => 10,
                'sustainability_goal' => 'Introduce users to environmental platform and eco-actions',
                'status' => 'active',
                'steps' => json_encode(array(
                    array(
                        'step' => 1,
                        'type' => 'email',
                        'delay' => 0,
                        'template_id' => 2,
                        'subject' => 'Welcome to our Environmental Community! 🌱',
                        'environmental_focus' => 'welcome_introduction'
                    ),
                    array(
                        'step' => 2,
                        'type' => 'email',
                        'delay' => 3,
                        'subject' => 'Your First Environmental Actions - Start Today! 🌍',
                        'environmental_focus' => 'action_guidance'
                    ),
                    array(
                        'step' => 3,
                        'type' => 'email',
                        'delay' => 7,
                        'subject' => 'Track Your Environmental Impact 📊',
                        'environmental_focus' => 'impact_tracking'
                    )
                ))
            ),
            array(
                'name' => 'Post-Purchase Eco Engagement',
                'trigger_type' => 'purchase',
                'environmental_trigger' => 'eco_product_purchase',
                'eco_action_points' => 25,
                'sustainability_goal' => 'Increase engagement with eco-products and sustainability',
                'status' => 'active',
                'steps' => json_encode(array(
                    array(
                        'step' => 1,
                        'type' => 'email',
                        'delay' => 1,
                        'subject' => 'Thank you for choosing sustainability! 🌿',
                        'environmental_focus' => 'purchase_appreciation'
                    ),
                    array(
                        'step' => 2,
                        'type' => 'email',
                        'delay' => 7,
                        'subject' => 'How to maximize your eco-product impact 💚',
                        'environmental_focus' => 'usage_optimization'
                    )
                ))
            ),
            array(
                'name' => 'Petition Supporter Follow-up',
                'trigger_type' => 'petition_signed',
                'environmental_trigger' => 'petition_signature',
                'eco_action_points' => 15,
                'sustainability_goal' => 'Keep petition supporters engaged and informed',
                'status' => 'active',
                'steps' => json_encode(array(
                    array(
                        'step' => 1,
                        'type' => 'email',
                        'delay' => 0,
                        'subject' => 'Thank you for supporting environmental change! ✊',
                        'environmental_focus' => 'petition_confirmation'
                    ),
                    array(
                        'step' => 2,
                        'type' => 'email',
                        'delay' => 14,
                        'subject' => 'Petition Update: Your impact is growing! 📈',
                        'environmental_focus' => 'petition_progress'
                    )
                ))
            )
        );
        
        foreach ($default_automations as $automation) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eem_automations WHERE name = %s",
                $automation['name']
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'eem_automations', $automation);
            }
        }
    }
    
    /**
     * Insert default segments
     */
    private function insert_default_segments() {
        global $wpdb;
        
        $default_segments = array(
            array(
                'name' => 'Eco Enthusiasts',
                'environmental_criteria' => 'High engagement with environmental content',
                'sustainability_score_min' => 75.0,
                'eco_interest_tags' => 'sustainability,climate_change,renewable_energy',
                'behavioral_triggers' => 'frequent_newsletter_opens,petition_signatures,eco_product_purchases',
                'conditions' => json_encode(array(
                    array('field' => 'environmental_score', 'operator' => '>=', 'value' => 75),
                    array('field' => 'last_engagement', 'operator' => '<=', 'value' => '30 days'),
                    array('field' => 'eco_actions_count', 'operator' => '>=', 'value' => 5)
                ))
            ),
            array(
                'name' => 'Climate Action Champions',
                'environmental_criteria' => 'Focused on climate change initiatives',
                'sustainability_score_min' => 60.0,
                'eco_interest_tags' => 'climate_change,carbon_offset,renewable_energy',
                'behavioral_triggers' => 'climate_petition_signatures,carbon_tracking,renewable_interest',
                'conditions' => json_encode(array(
                    array('field' => 'climate_actions', 'operator' => '>=', 'value' => 3),
                    array('field' => 'petition_signatures', 'operator' => '>=', 'value' => 2),
                    array('field' => 'carbon_footprint_tracking', 'operator' => '=', 'value' => true)
                ))
            ),
            array(
                'name' => 'Sustainable Shoppers',
                'environmental_criteria' => 'Active in eco-product purchases',
                'sustainability_score_min' => 50.0,
                'eco_interest_tags' => 'eco_products,sustainable_shopping,green_lifestyle',
                'behavioral_triggers' => 'eco_product_purchases,product_reviews,sustainability_ratings',
                'conditions' => json_encode(array(
                    array('field' => 'eco_purchases_count', 'operator' => '>=', 'value' => 2),
                    array('field' => 'product_engagement', 'operator' => '>=', 'value' => 5),
                    array('field' => 'wishlist_eco_items', 'operator' => '>=', 'value' => 1)
                ))
            ),
            array(
                'name' => 'Environmental Newcomers',
                'environmental_criteria' => 'New to environmental platform',
                'sustainability_score_max' => 25.0,
                'eco_interest_tags' => 'beginner,learning,introduction',
                'behavioral_triggers' => 'recent_signup,minimal_engagement,learning_content_views',
                'conditions' => json_encode(array(
                    array('field' => 'days_since_signup', 'operator' => '<=', 'value' => 30),
                    array('field' => 'environmental_score', 'operator' => '<=', 'value' => 25),
                    array('field' => 'completed_actions', 'operator' => '<=', 'value' => 2)
                ))
            )
        );
        
        foreach ($default_segments as $segment) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}eem_segments WHERE name = %s",
                $segment['name']
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'eem_segments', $segment);
            }
        }
    }
    
    /**
     * Get newsletter template content
     */
    private function get_newsletter_template_content() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{campaign_subject}}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f9f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="background-color: #f4f9f4; padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: Arial, sans-serif;">🌱 Environmental Newsletter</h1>
                            <p style="color: #e8f5e8; margin: 10px 0 0 0; font-size: 16px;">Your weekly dose of sustainability</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #2d5a27; margin: 0 0 20px 0; font-size: 24px;">{{newsletter_title}}</h2>
                            <div style="color: #333333; font-size: 16px; line-height: 1.6;">
                                {{newsletter_content}}
                            </div>
                            
                            <!-- Environmental Tip -->
                            <div style="background-color: #e8f5e8; padding: 20px; border-radius: 6px; margin: 30px 0; border-left: 4px solid #4a7c59;">
                                <h3 style="color: #2d5a27; margin: 0 0 10px 0; font-size: 18px;">🌿 Eco Tip of the Week</h3>
                                <p style="margin: 0; color: #333333;">{{environmental_tip}}</p>
                            </div>
                            
                            <!-- Carbon Impact -->
                            <div style="text-align: center; margin: 30px 0;">
                                <h3 style="color: #2d5a27; margin: 0 0 15px 0;">Your Environmental Impact</h3>
                                <div style="display: inline-block; background-color: #f0f8f0; padding: 20px; border-radius: 50px; margin: 0 10px;">
                                    <strong style="color: #4a7c59; font-size: 24px;">{{carbon_saved}} kg</strong><br>
                                    <span style="color: #666; font-size: 14px;">CO₂ Saved</span>
                                </div>
                            </div>
                            
                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{action_url}}" style="background-color: #4a7c59; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">Take Environmental Action</a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fdf8; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                © {{current_year}} Environmental Platform | Committed to a sustainable future
                            </p>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">
                                <a href="{{unsubscribe_url}}" style="color: #4a7c59;">Unsubscribe</a> | 
                                <a href="{{preference_url}}" style="color: #4a7c59;">Update Preferences</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Get newsletter template styles
     */
    private function get_newsletter_template_styles() {
        return '.environmental-newsletter { font-family: Arial, sans-serif; }
.header-gradient { background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%); }
.eco-tip-box { background-color: #e8f5e8; border-left: 4px solid #4a7c59; }
.impact-circle { background-color: #f0f8f0; border-radius: 50px; }
.action-button { background-color: #4a7c59; border-radius: 25px; }
@media only screen and (max-width: 600px) {
    .newsletter-table { width: 100% !important; }
    .newsletter-content { padding: 20px !important; }
}';
    }
    
    /**
     * Get welcome template content
     */
    private function get_welcome_template_content() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Environmental Platform</title>
</head>
<body style="margin: 0; padding: 0; background-color: #e6f3ff;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="background-color: #e6f3ff; padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px;">
                    <!-- Header -->
                    <tr>
                        <td style="text-align: center; padding: 40px 30px 20px;">
                            <h1 style="color: #1e88e5; margin: 0; font-size: 32px;">🌍 Welcome to Our Environmental Community!</h1>
                            <p style="color: #666; margin: 15px 0 0 0; font-size: 18px;">Thank you for joining the movement for a sustainable future</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px 40px 40px;">
                            <p style="color: #333; font-size: 16px; line-height: 1.6;">Dear {{subscriber_name}},</p>
                            <p style="color: #333; font-size: 16px; line-height: 1.6;">
                                Welcome to our environmental platform! We are thrilled to have you join our community of eco-conscious individuals working together to create positive environmental change.
                            </p>
                            
                            <!-- Getting Started -->
                            <div style="background-color: #f0f8ff; padding: 25px; border-radius: 8px; margin: 25px 0;">
                                <h3 style="color: #1e88e5; margin: 0 0 15px 0;">🚀 Get Started</h3>
                                <ul style="color: #333; margin: 0; padding-left: 20px;">
                                    <li style="margin-bottom: 8px;">Complete your environmental profile</li>
                                    <li style="margin-bottom: 8px;">Set your sustainability goals</li>
                                    <li style="margin-bottom: 8px;">Explore eco-friendly products</li>
                                    <li style="margin-bottom: 8px;">Join environmental campaigns</li>
                                </ul>
                            </div>
                            
                            <!-- Action Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{platform_url}}" style="background-color: #1e88e5; color: #ffffff; padding: 15px 35px; text-decoration: none; border-radius: 30px; font-weight: bold; display: inline-block;">Start Your Environmental Journey</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Get welcome template styles
     */
    private function get_welcome_template_styles() {
        return '.welcome-email { background-color: #e6f3ff; }
.welcome-header { color: #1e88e5; }
.getting-started-box { background-color: #f0f8ff; border-radius: 8px; }
.start-journey-button { background-color: #1e88e5; border-radius: 30px; }';
    }
    
    /**
     * Get climate template content
     */
    private function get_climate_template_content() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Climate Action Urgent</title>
</head>
<body style="margin: 0; padding: 0; background-color: #fff5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="background-color: #fff5f5; padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                    <!-- Urgent Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">🚨 CLIMATE ACTION NEEDED</h1>
                            <p style="color: #ffebee; margin: 10px 0 0 0; font-size: 16px;">Every action counts - Act now!</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #d32f2f; margin: 0 0 20px 0;">{{campaign_title}}</h2>
                            <div style="color: #333; font-size: 16px; line-height: 1.6;">
                                {{campaign_content}}
                            </div>
                            
                            <!-- Action Required -->
                            <div style="background-color: #ffebee; padding: 20px; border-radius: 6px; margin: 25px 0; border-left: 4px solid #f44336;">
                                <h3 style="color: #d32f2f; margin: 0 0 10px 0;">⚡ Action Required</h3>
                                <p style="margin: 0; color: #333;">{{action_description}}</p>
                            </div>
                            
                            <!-- Multiple Action Buttons -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{petition_url}}" style="background-color: #f44336; color: #ffffff; padding: 15px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; margin: 5px;">Sign Petition</a>
                                <a href="{{donate_url}}" style="background-color: #ff9800; color: #ffffff; padding: 15px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; margin: 5px;">Donate Now</a>
                                <a href="{{share_url}}" style="background-color: #4caf50; color: #ffffff; padding: 15px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; margin: 5px;">Share Campaign</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Get climate template styles
     */
    private function get_climate_template_styles() {
        return '.climate-urgent { background-color: #fff5f5; }
.urgent-header { background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%); }
.action-required-box { background-color: #ffebee; border-left: 4px solid #f44336; }
.petition-button { background-color: #f44336; }
.donate-button { background-color: #ff9800; }
.share-button { background-color: #4caf50; }';
    }
    
    /**
     * Get product template content
     */
    private function get_product_template_content() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sustainable Products</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0fdf4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="background-color: #f0fdf4; padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 10px;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">🛍️ Sustainable Shopping</h1>
                            <p style="color: #dcfce7; margin: 10px 0 0 0; font-size: 16px;">Eco-friendly products for a better tomorrow</p>
                        </td>
                    </tr>
                    
                    <!-- Featured Product -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <div style="text-align: center; margin-bottom: 30px;">
                                <img src="{{product_image}}" alt="{{product_name}}" style="max-width: 300px; border-radius: 8px;">
                                <h2 style="color: #16a34a; margin: 20px 0 10px 0;">{{product_name}}</h2>
                                
                                <!-- Sustainability Rating -->
                                <div style="background-color: #f0fdf4; padding: 15px; border-radius: 8px; margin: 20px 0; display: inline-block;">
                                    <span style="color: #16a34a; font-weight: bold;">🌟 Sustainability Rating: {{sustainability_rating}}/5</span>
                                </div>
                            </div>
                            
                            <!-- Product Features -->
                            <div style="background-color: #f9fafb; padding: 25px; border-radius: 8px; margin: 25px 0;">
                                <h3 style="color: #16a34a; margin: 0 0 15px 0;">♻️ Eco Features</h3>
                                <ul style="color: #333; margin: 0; padding-left: 20px;">
                                    <li>{{eco_feature_1}}</li>
                                    <li>{{eco_feature_2}}</li>
                                    <li>{{eco_feature_3}}</li>
                                </ul>
                            </div>
                            
                            <!-- Environmental Impact -->
                            <div style="text-align: center; margin: 25px 0;">
                                <h3 style="color: #16a34a; margin: 0 0 15px 0;">Environmental Impact</h3>
                                <div style="display: inline-block; background-color: #ecfdf5; padding: 15px 25px; border-radius: 25px; margin: 0 10px;">
                                    <strong style="color: #16a34a;">-{{carbon_reduction}} kg CO₂</strong><br>
                                    <span style="color: #666; font-size: 14px;">Carbon Reduction</span>
                                </div>
                            </div>
                            
                            <!-- Shop Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{product_url}}" style="background-color: #16a34a; color: #ffffff; padding: 15px 40px; text-decoration: none; border-radius: 30px; font-weight: bold; display: inline-block;">Shop Sustainably</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Get product template styles
     */
    private function get_product_template_styles() {
        return '.product-email { background-color: #f0fdf4; }
.product-header { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); }
.sustainability-rating { background-color: #f0fdf4; border-radius: 8px; }
.eco-features-box { background-color: #f9fafb; border-radius: 8px; }
.impact-circle { background-color: #ecfdf5; border-radius: 25px; }
.shop-button { background-color: #16a34a; border-radius: 30px; }';
    }
    
    /**
     * Drop tables on uninstall
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'eem_analytics',
            $wpdb->prefix . 'eem_ab_tests',
            $wpdb->prefix . 'eem_automation_subscribers',
            $wpdb->prefix . 'eem_automations',
            $wpdb->prefix . 'eem_subscriber_lists',
            $wpdb->prefix . 'eem_campaigns',
            $wpdb->prefix . 'eem_templates',
            $wpdb->prefix . 'eem_segments',
            $wpdb->prefix . 'eem_lists',
            $wpdb->prefix . 'eem_subscribers'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove options
        delete_option('eem_db_version');
    }
}
