<?php
/**
 * Cron Job Handler
 * 
 * @package Environmental_Email_Marketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class EEM_Cron_Handler {
    
    /**
     * Initialize cron jobs
     */
    public static function init() {
        // Schedule cron events
        add_action('wp', array(__CLASS__, 'schedule_events'));
        
        // Hook cron actions
        add_action('eem_process_automation_queue', array(__CLASS__, 'process_automation_queue'));
        add_action('eem_process_campaign_queue', array(__CLASS__, 'process_campaign_queue'));
        add_action('eem_sync_providers', array(__CLASS__, 'sync_providers'));
        add_action('eem_cleanup_data', array(__CLASS__, 'cleanup_data'));
        add_action('eem_update_analytics', array(__CLASS__, 'update_analytics'));
        add_action('eem_process_webhooks', array(__CLASS__, 'process_webhooks'));
        
        // Add custom cron schedules
        add_filter('cron_schedules', array(__CLASS__, 'add_cron_schedules'));
    }
    
    /**
     * Schedule cron events
     */
    public static function schedule_events() {
        $settings = get_option('eem_settings', array());
        
        // Automation queue processing
        if (!wp_next_scheduled('eem_process_automation_queue')) {
            $frequency = $settings['automation_frequency'] ?? 'hourly';
            wp_schedule_event(time(), $frequency, 'eem_process_automation_queue');
        }
        
        // Campaign queue processing
        if (!wp_next_scheduled('eem_process_campaign_queue')) {
            wp_schedule_event(time(), 'eem_every_5_minutes', 'eem_process_campaign_queue');
        }
        
        // Provider synchronization
        if (!wp_next_scheduled('eem_sync_providers')) {
            wp_schedule_event(time(), 'daily', 'eem_sync_providers');
        }
        
        // Data cleanup
        if (!wp_next_scheduled('eem_cleanup_data')) {
            wp_schedule_event(time(), 'daily', 'eem_cleanup_data');
        }
        
        // Analytics updates
        if (!wp_next_scheduled('eem_update_analytics')) {
            wp_schedule_event(time(), 'eem_every_15_minutes', 'eem_update_analytics');
        }
        
        // Webhook processing
        if (!wp_next_scheduled('eem_process_webhooks')) {
            wp_schedule_event(time(), 'eem_every_5_minutes', 'eem_process_webhooks');
        }
    }
    
    /**
     * Add custom cron schedules
     */
    public static function add_cron_schedules($schedules) {
        $schedules['eem_every_5_minutes'] = array(
            'interval' => 300,
            'display' => 'Every 5 Minutes'
        );
        
        $schedules['eem_every_15_minutes'] = array(
            'interval' => 900,
            'display' => 'Every 15 Minutes'
        );
        
        $schedules['eem_every_30_minutes'] = array(
            'interval' => 1800,
            'display' => 'Every 30 Minutes'
        );
        
        return $schedules;
    }
    
    /**
     * Process automation queue
     */
    public static function process_automation_queue() {
        try {
            EEM_Logger::log('Starting automation queue processing', 'info');
            
            $automation_engine = EEM_Automation_Engine::get_instance();
            $processed = $automation_engine->process_queue();
            
            EEM_Logger::log("Processed {$processed} automation items", 'info');
            
            // Update last run timestamp
            update_option('eem_last_automation_run', time());
            
        } catch (Exception $e) {
            EEM_Logger::log('Automation queue processing failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Process campaign queue
     */
    public static function process_campaign_queue() {
        try {
            EEM_Logger::log('Starting campaign queue processing', 'info');
            
            $campaign_manager = EEM_Campaign_Manager::get_instance();
            $processed = $campaign_manager->process_queue();
            
            EEM_Logger::log("Processed {$processed} campaign items", 'info');
            
            // Update last run timestamp
            update_option('eem_last_campaign_run', time());
            
        } catch (Exception $e) {
            EEM_Logger::log('Campaign queue processing failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Synchronize with email providers
     */
    public static function sync_providers() {
        try {
            EEM_Logger::log('Starting provider synchronization', 'info');
            
            $settings = get_option('eem_settings', array());
            $provider_name = $settings['email_provider'] ?? 'wordpress';
            
            if ($provider_name === 'wordpress') {
                EEM_Logger::log('Skipping sync for WordPress provider', 'info');
                return;
            }
            
            $provider = self::get_provider_instance($provider_name);
            if (!$provider) {
                EEM_Logger::log("Provider {$provider_name} not found", 'error');
                return;
            }
            
            // Sync subscribers
            $subscriber_manager = EEM_Subscriber_Manager::get_instance();
            $subscribers = $subscriber_manager->get_subscribers(array(
                'status' => 'active',
                'limit' => 1000,
                'sync_pending' => true
            ));
            
            $synced = 0;
            foreach ($subscribers as $subscriber) {
                try {
                    $result = $provider->sync_subscriber($subscriber);
                    if ($result) {
                        $subscriber_manager->mark_synced($subscriber->id);
                        $synced++;
                    }
                } catch (Exception $e) {
                    EEM_Logger::log("Failed to sync subscriber {$subscriber->id}: " . $e->getMessage(), 'warning');
                }
            }
            
            EEM_Logger::log("Synced {$synced} subscribers with {$provider_name}", 'info');
            
            // Sync campaigns
            $campaign_manager = EEM_Campaign_Manager::get_instance();
            $campaigns = $campaign_manager->get_campaigns(array(
                'status' => 'completed',
                'sync_pending' => true,
                'limit' => 100
            ));
            
            $campaign_synced = 0;
            foreach ($campaigns as $campaign) {
                try {
                    $result = $provider->sync_campaign_stats($campaign);
                    if ($result) {
                        $campaign_manager->mark_synced($campaign->id);
                        $campaign_synced++;
                    }
                } catch (Exception $e) {
                    EEM_Logger::log("Failed to sync campaign {$campaign->id}: " . $e->getMessage(), 'warning');
                }
            }
            
            EEM_Logger::log("Synced {$campaign_synced} campaigns with {$provider_name}", 'info');
            
            // Update last sync timestamp
            update_option('eem_last_provider_sync', time());
            
        } catch (Exception $e) {
            EEM_Logger::log('Provider synchronization failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Clean up old data
     */
    public static function cleanup_data() {
        global $wpdb;
        
        try {
            EEM_Logger::log('Starting data cleanup', 'info');
            
            $settings = get_option('eem_settings', array());
            $retention_days = intval($settings['data_retention_days'] ?? 365);
            $analytics_retention = intval($settings['analytics_retention'] ?? 365);
            
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
            $analytics_cutoff = date('Y-m-d H:i:s', strtotime("-{$analytics_retention} days"));
            
            // Clean up old unsubscribed subscribers
            $unsubscribed_cleaned = $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->prefix}eem_subscribers 
                WHERE status = 'unsubscribed' 
                AND updated_at < %s
            ", $cutoff_date));
            
            // Clean up old analytics data
            $analytics_cleaned = $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->prefix}eem_analytics 
                WHERE created_at < %s
            ", $analytics_cutoff));
            
            // Clean up old log entries
            $logs_cleaned = $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->prefix}eem_logs 
                WHERE created_at < %s
            ", $analytics_cutoff));
            
            // Clean up orphaned data
            $orphaned_cleaned = $wpdb->query("
                DELETE sl FROM {$wpdb->prefix}eem_subscriber_lists sl
                LEFT JOIN {$wpdb->prefix}eem_subscribers s ON sl.subscriber_id = s.id
                WHERE s.id IS NULL
            ");
            
            // Optimize tables
            $tables = array(
                'eem_subscribers',
                'eem_campaigns',
                'eem_analytics',
                'eem_automation_queue',
                'eem_logs'
            );
            
            foreach ($tables as $table) {
                $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}{$table}");
            }
            
            EEM_Logger::log("Cleanup completed: {$unsubscribed_cleaned} subscribers, {$analytics_cleaned} analytics, {$logs_cleaned} logs, {$orphaned_cleaned} orphaned records", 'info');
            
            // Update last cleanup timestamp
            update_option('eem_last_cleanup', time());
            
        } catch (Exception $e) {
            EEM_Logger::log('Data cleanup failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Update analytics data
     */
    public static function update_analytics() {
        try {
            EEM_Logger::log('Starting analytics update', 'info');
            
            $analytics_tracker = EEM_Analytics_Tracker::get_instance();
            
            // Calculate daily summaries
            $analytics_tracker->calculate_daily_summary();
            
            // Update environmental impact metrics
            $analytics_tracker->update_environmental_metrics();
            
            // Update subscriber engagement scores
            $analytics_tracker->update_engagement_scores();
            
            // Generate performance insights
            $analytics_tracker->generate_insights();
            
            EEM_Logger::log('Analytics update completed', 'info');
            
            // Update last analytics run timestamp
            update_option('eem_last_analytics_update', time());
            
        } catch (Exception $e) {
            EEM_Logger::log('Analytics update failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Process webhook queue
     */
    public static function process_webhooks() {
        global $wpdb;
        
        try {
            EEM_Logger::log('Starting webhook processing', 'info');
            
            // Get pending webhooks
            $webhooks = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}eem_webhook_queue 
                WHERE status = 'pending' 
                AND scheduled_at <= %s
                ORDER BY scheduled_at ASC 
                LIMIT 50
            ", current_time('mysql')));
            
            $processed = 0;
            foreach ($webhooks as $webhook) {
                try {
                    $data = json_decode($webhook->data, true);
                    
                    // Process webhook based on provider
                    $result = self::process_webhook_data($webhook->provider, $webhook->event_type, $data);
                    
                    if ($result) {
                        $wpdb->update(
                            $wpdb->prefix . 'eem_webhook_queue',
                            array(
                                'status' => 'processed',
                                'processed_at' => current_time('mysql')
                            ),
                            array('id' => $webhook->id),
                            array('%s', '%s'),
                            array('%d')
                        );
                        $processed++;
                    } else {
                        // Mark as failed and increment retry count
                        $retry_count = intval($webhook->retry_count) + 1;
                        if ($retry_count >= 5) {
                            $status = 'failed';
                        } else {
                            $status = 'pending';
                        }
                        
                        $wpdb->update(
                            $wpdb->prefix . 'eem_webhook_queue',
                            array(
                                'status' => $status,
                                'retry_count' => $retry_count,
                                'scheduled_at' => date('Y-m-d H:i:s', strtotime('+' . (pow(2, $retry_count) * 5) . ' minutes'))
                            ),
                            array('id' => $webhook->id),
                            array('%s', '%d', '%s'),
                            array('%d')
                        );
                    }
                    
                } catch (Exception $e) {
                    EEM_Logger::log("Failed to process webhook {$webhook->id}: " . $e->getMessage(), 'warning');
                }
            }
            
            EEM_Logger::log("Processed {$processed} webhooks", 'info');
            
            // Update last webhook run timestamp
            update_option('eem_last_webhook_run', time());
            
        } catch (Exception $e) {
            EEM_Logger::log('Webhook processing failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Process webhook data based on provider and event type
     */
    private static function process_webhook_data($provider, $event_type, $data) {
        $subscriber_manager = EEM_Subscriber_Manager::get_instance();
        $analytics_tracker = EEM_Analytics_Tracker::get_instance();
        
        try {
            switch ($event_type) {
                case 'subscribe':
                case 'unsubscribe':
                    if (isset($data['email'])) {
                        $subscriber = $subscriber_manager->get_subscriber_by_email($data['email']);
                        if ($subscriber) {
                            $new_status = ($event_type === 'subscribe') ? 'active' : 'unsubscribed';
                            $subscriber_manager->update_subscriber_status($subscriber->id, $new_status);
                            
                            // Track event
                            $analytics_tracker->track_event($event_type, array(
                                'subscriber_id' => $subscriber->id,
                                'provider' => $provider,
                                'source' => 'webhook'
                            ));
                        }
                    }
                    break;
                    
                case 'bounce':
                case 'spam_complaint':
                    if (isset($data['email'])) {
                        $subscriber = $subscriber_manager->get_subscriber_by_email($data['email']);
                        if ($subscriber) {
                            $new_status = ($event_type === 'bounce' && $data['type'] === 'hard') ? 'bounced' : 'spam_complaint';
                            $subscriber_manager->update_subscriber_status($subscriber->id, $new_status);
                            
                            // Track event
                            $analytics_tracker->track_event($event_type, array(
                                'subscriber_id' => $subscriber->id,
                                'provider' => $provider,
                                'bounce_type' => $data['type'] ?? '',
                                'reason' => $data['reason'] ?? ''
                            ));
                        }
                    }
                    break;
                    
                case 'open':
                case 'click':
                    if (isset($data['email']) && isset($data['campaign_id'])) {
                        $subscriber = $subscriber_manager->get_subscriber_by_email($data['email']);
                        if ($subscriber) {
                            // Track event
                            $analytics_tracker->track_event($event_type, array(
                                'subscriber_id' => $subscriber->id,
                                'campaign_id' => $data['campaign_id'],
                                'provider' => $provider,
                                'url' => $data['url'] ?? '',
                                'user_agent' => $data['user_agent'] ?? ''
                            ));
                            
                            // Update subscriber engagement
                            $subscriber_manager->update_engagement($subscriber->id, $event_type);
                        }
                    }
                    break;
                    
                default:
                    EEM_Logger::log("Unknown webhook event type: {$event_type}", 'warning');
                    return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            EEM_Logger::log("Failed to process webhook data: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Get provider instance
     */
    private static function get_provider_instance($provider_name) {
        switch ($provider_name) {
            case 'mailchimp':
                return new EEM_Mailchimp_Provider();
            case 'sendgrid':
                return new EEM_SendGrid_Provider();
            case 'mailgun':
                return new EEM_Mailgun_Provider();
            case 'amazonses':
                return new EEM_Amazon_SES_Provider();
            default:
                return null;
        }
    }
    
    /**
     * Clear all scheduled events
     */
    public static function clear_scheduled_events() {
        $events = array(
            'eem_process_automation_queue',
            'eem_process_campaign_queue',
            'eem_sync_providers',
            'eem_cleanup_data',
            'eem_update_analytics',
            'eem_process_webhooks'
        );
        
        foreach ($events as $event) {
            wp_clear_scheduled_hook($event);
        }
    }
    
    /**
     * Get cron status
     */
    public static function get_cron_status() {
        $events = array(
            'eem_process_automation_queue' => 'Automation Queue',
            'eem_process_campaign_queue' => 'Campaign Queue',
            'eem_sync_providers' => 'Provider Sync',
            'eem_cleanup_data' => 'Data Cleanup',
            'eem_update_analytics' => 'Analytics Update',
            'eem_process_webhooks' => 'Webhook Processing'
        );
        
        $status = array();
        foreach ($events as $hook => $label) {
            $next_run = wp_next_scheduled($hook);
            $status[$hook] = array(
                'label' => $label,
                'next_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled',
                'is_scheduled' => (bool) $next_run
            );
        }
        
        return $status;
    }
}

// Initialize cron handler
EEM_Cron_Handler::init();
