<?php
/**
 * Environmental Email Marketing - Automation Engine
 *
 * Handles automated email sequences, triggers, and scheduling
 *
 * @package Environmental_Email_Marketing
 * @subpackage Automation
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Automation_Engine {

    /**
     * Database manager instance
     *
     * @var EEM_Database_Manager
     */
    private $db_manager;

    /**
     * Subscriber manager instance
     *
     * @var EEM_Subscriber_Manager
     */
    private $subscriber_manager;

    /**
     * Campaign manager instance
     *
     * @var EEM_Campaign_Manager
     */
    private $campaign_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db_manager = new EEM_Database_Manager();
        $this->subscriber_manager = new EEM_Subscriber_Manager();
        $this->campaign_manager = new EEM_Campaign_Manager();
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Cron hooks for automation processing
        add_action('eem_process_automations', array($this, 'process_scheduled_automations'));
        add_action('eem_check_automation_triggers', array($this, 'check_automation_triggers'));
        
        // Trigger hooks for various environmental actions
        add_action('eem_subscriber_subscribed', array($this, 'trigger_welcome_sequence'), 10, 2);
        add_action('eem_petition_signed', array($this, 'trigger_petition_followup'), 10, 2);
        add_action('eem_quiz_completed', array($this, 'trigger_quiz_followup'), 10, 3);
        add_action('eem_eco_purchase_made', array($this, 'trigger_purchase_followup'), 10, 2);
        add_action('eem_event_registered', array($this, 'trigger_event_sequence'), 10, 2);
        
        // Behavioral triggers
        add_action('eem_email_opened', array($this, 'trigger_engagement_sequence'), 10, 2);
        add_action('eem_link_clicked', array($this, 'trigger_click_sequence'), 10, 3);
        add_action('eem_subscriber_inactive', array($this, 'trigger_reengagement_sequence'), 10, 2);
    }

    /**
     * Process scheduled automations
     */
    public function process_scheduled_automations() {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('automations');
        
        // Get active automations that need processing
        $automations = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE status = %s 
            AND (next_run <= %s OR next_run IS NULL)
            ORDER BY priority DESC, created_at ASC
            LIMIT 50
        ", 'active', current_time('mysql')));
        
        foreach ($automations as $automation) {
            $this->process_automation($automation);
        }
        
        // Process automation queue
        $this->process_automation_queue();
    }

    /**
     * Process individual automation
     *
     * @param object $automation Automation object
     */
    private function process_automation($automation) {
        global $wpdb;
        
        $automation_data = json_decode($automation->automation_data, true);
        $trigger_conditions = json_decode($automation->trigger_conditions, true);
        
        // Get subscribers who match trigger conditions
        $subscribers = $this->get_automation_subscribers($automation, $trigger_conditions);
        
        foreach ($subscribers as $subscriber) {
            $this->queue_automation_for_subscriber($automation, $subscriber, $automation_data);
        }
        
        // Update next run time
        $this->update_automation_next_run($automation);
    }

    /**
     * Get subscribers for automation based on trigger conditions
     *
     * @param object $automation Automation object
     * @param array $trigger_conditions Trigger conditions
     * @return array Array of subscriber objects
     */
    private function get_automation_subscribers($automation, $trigger_conditions) {
        global $wpdb;
        
        $subscribers_table = $this->db_manager->get_table_name('subscribers');
        $where_conditions = array();
        $where_values = array();
        
        // Base conditions
        $where_conditions[] = 's.status = %s';
        $where_values[] = 'active';
        
        // Segment conditions
        if (!empty($trigger_conditions['segments'])) {
            $segments_placeholders = implode(',', array_fill(0, count($trigger_conditions['segments']), '%d'));
            $where_conditions[] = "s.segment_id IN ($segments_placeholders)";
            $where_values = array_merge($where_values, $trigger_conditions['segments']);
        }
        
        // Environmental score conditions
        if (!empty($trigger_conditions['min_environmental_score'])) {
            $where_conditions[] = 's.environmental_score >= %d';
            $where_values[] = $trigger_conditions['min_environmental_score'];
        }
        
        if (!empty($trigger_conditions['max_environmental_score'])) {
            $where_conditions[] = 's.environmental_score <= %d';
            $where_values[] = $trigger_conditions['max_environmental_score'];
        }
        
        // Engagement conditions
        if (!empty($trigger_conditions['min_engagement_score'])) {
            $where_conditions[] = 's.engagement_score >= %d';
            $where_values[] = $trigger_conditions['min_engagement_score'];
        }
        
        // Subscription date conditions
        if (!empty($trigger_conditions['days_since_subscription'])) {
            $where_conditions[] = 'DATEDIFF(NOW(), s.created_at) >= %d';
            $where_values[] = $trigger_conditions['days_since_subscription'];
        }
        
        // Last email conditions
        if (!empty($trigger_conditions['days_since_last_email'])) {
            $where_conditions[] = '(s.last_email_sent IS NULL OR DATEDIFF(NOW(), s.last_email_sent) >= %d)';
            $where_values[] = $trigger_conditions['days_since_last_email'];
        }
        
        // Exclude subscribers already in this automation
        $automation_queue_table = $this->db_manager->get_table_name('automation_queue');
        $where_conditions[] = "s.id NOT IN (
            SELECT subscriber_id FROM {$automation_queue_table} 
            WHERE automation_id = %d AND status IN ('pending', 'processing')
        )";
        $where_values[] = $automation->id;
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT s.* FROM {$subscribers_table} s WHERE {$where_clause} LIMIT 100";
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }

    /**
     * Queue automation for specific subscriber
     *
     * @param object $automation Automation object
     * @param object $subscriber Subscriber object
     * @param array $automation_data Automation data
     */
    private function queue_automation_for_subscriber($automation, $subscriber, $automation_data) {
        global $wpdb;
        
        $queue_table = $this->db_manager->get_table_name('automation_queue');
        
        // Calculate send times for each email in the sequence
        $current_time = current_time('timestamp');
        $email_sequence = $automation_data['email_sequence'] ?? array();
        
        foreach ($email_sequence as $index => $email_data) {
            $delay_hours = $email_data['delay_hours'] ?? 0;
            $send_time = $current_time + ($delay_hours * HOUR_IN_SECONDS);
            
            $queue_data = array(
                'automation_id' => $automation->id,
                'subscriber_id' => $subscriber->id,
                'email_data' => json_encode($email_data),
                'sequence_step' => $index + 1,
                'scheduled_time' => date('Y-m-d H:i:s', $send_time),
                'status' => 'scheduled',
                'created_at' => current_time('mysql')
            );
            
            $wpdb->insert($queue_table, $queue_data);
        }
    }

    /**
     * Process automation queue
     */
    private function process_automation_queue() {
        global $wpdb;
        
        $queue_table = $this->db_manager->get_table_name('automation_queue');
        
        // Get scheduled emails ready to send
        $queue_items = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$queue_table}
            WHERE status = %s 
            AND scheduled_time <= %s
            ORDER BY scheduled_time ASC
            LIMIT 20
        ", 'scheduled', current_time('mysql')));
        
        foreach ($queue_items as $queue_item) {
            $this->process_queue_item($queue_item);
        }
    }

    /**
     * Process individual queue item
     *
     * @param object $queue_item Queue item object
     */
    private function process_queue_item($queue_item) {
        global $wpdb;
        
        $queue_table = $this->db_manager->get_table_name('automation_queue');
        
        // Update status to processing
        $wpdb->update(
            $queue_table,
            array('status' => 'processing', 'updated_at' => current_time('mysql')),
            array('id' => $queue_item->id)
        );
        
        try {
            $email_data = json_decode($queue_item->email_data, true);
            $subscriber = $this->subscriber_manager->get_subscriber_by_id($queue_item->subscriber_id);
            
            if (!$subscriber || $subscriber->status !== 'active') {
                throw new Exception('Subscriber not active or not found');
            }
            
            // Create campaign for this automation email
            $campaign_data = array(
                'name' => $email_data['subject'] . ' (Automation)',
                'subject' => $email_data['subject'],
                'content' => $email_data['content'],
                'template_id' => $email_data['template_id'] ?? null,
                'type' => 'automation',
                'automation_id' => $queue_item->automation_id,
                'environmental_theme' => $email_data['environmental_theme'] ?? 'nature_green'
            );
            
            // Send email
            $campaign_id = $this->campaign_manager->create_campaign($campaign_data);
            $send_result = $this->campaign_manager->send_to_subscriber($campaign_id, $subscriber);
            
            if ($send_result) {
                // Update queue item as completed
                $wpdb->update(
                    $queue_table,
                    array(
                        'status' => 'completed',
                        'sent_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $queue_item->id)
                );
                
                // Update subscriber's last email sent
                $this->subscriber_manager->update_last_email_sent($subscriber->id);
                
                // Log automation activity
                $this->log_automation_activity($queue_item->automation_id, $subscriber->id, 'email_sent', array(
                    'queue_item_id' => $queue_item->id,
                    'campaign_id' => $campaign_id,
                    'sequence_step' => $queue_item->sequence_step
                ));
                
            } else {
                throw new Exception('Failed to send email');
            }
            
        } catch (Exception $e) {
            // Update queue item as failed
            $wpdb->update(
                $queue_table,
                array(
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $queue_item->id)
            );
            
            // Log error
            error_log('EEM Automation Error: ' . $e->getMessage());
        }
    }

    /**
     * Trigger welcome sequence for new subscriber
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $subscription_data Subscription data
     */
    public function trigger_welcome_sequence($subscriber_id, $subscription_data = array()) {
        $this->trigger_automation_by_type('welcome', $subscriber_id, $subscription_data);
    }

    /**
     * Trigger petition follow-up sequence
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $petition_data Petition data
     */
    public function trigger_petition_followup($subscriber_id, $petition_data = array()) {
        // Update subscriber's environmental engagement
        $this->subscriber_manager->update_environmental_score($subscriber_id, 10);
        $this->trigger_automation_by_type('petition_followup', $subscriber_id, $petition_data);
    }

    /**
     * Trigger quiz follow-up sequence
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $quiz_data Quiz data
     * @param int $quiz_score Quiz score
     */
    public function trigger_quiz_followup($subscriber_id, $quiz_data = array(), $quiz_score = 0) {
        // Update subscriber's environmental score based on quiz performance
        $score_increase = max(5, $quiz_score / 10);
        $this->subscriber_manager->update_environmental_score($subscriber_id, $score_increase);
        
        $trigger_data = array_merge($quiz_data, array('quiz_score' => $quiz_score));
        $this->trigger_automation_by_type('quiz_followup', $subscriber_id, $trigger_data);
    }

    /**
     * Trigger purchase follow-up sequence
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $purchase_data Purchase data
     */
    public function trigger_purchase_followup($subscriber_id, $purchase_data = array()) {
        // Update subscriber's environmental score for eco-friendly purchase
        $eco_score_increase = $purchase_data['eco_score_increase'] ?? 15;
        $this->subscriber_manager->update_environmental_score($subscriber_id, $eco_score_increase);
        
        $this->trigger_automation_by_type('purchase_followup', $subscriber_id, $purchase_data);
    }

    /**
     * Trigger event registration sequence
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $event_data Event data
     */
    public function trigger_event_sequence($subscriber_id, $event_data = array()) {
        $this->subscriber_manager->update_environmental_score($subscriber_id, 8);
        $this->trigger_automation_by_type('event_registration', $subscriber_id, $event_data);
    }

    /**
     * Trigger engagement sequence based on email opens
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $engagement_data Engagement data
     */
    public function trigger_engagement_sequence($subscriber_id, $engagement_data = array()) {
        $this->subscriber_manager->update_engagement_score($subscriber_id, 5);
        
        // Check if subscriber qualifies for advanced content
        $subscriber = $this->subscriber_manager->get_subscriber_by_id($subscriber_id);
        if ($subscriber && $subscriber->engagement_score >= 50) {
            $this->trigger_automation_by_type('high_engagement', $subscriber_id, $engagement_data);
        }
    }

    /**
     * Trigger click sequence based on link clicks
     *
     * @param int $subscriber_id Subscriber ID
     * @param string $clicked_url Clicked URL
     * @param array $click_data Click data
     */
    public function trigger_click_sequence($subscriber_id, $clicked_url, $click_data = array()) {
        $this->subscriber_manager->update_engagement_score($subscriber_id, 10);
        
        // Trigger specific sequences based on clicked content
        if (strpos($clicked_url, 'petition') !== false) {
            $this->trigger_automation_by_type('petition_interest', $subscriber_id, $click_data);
        } elseif (strpos($clicked_url, 'shop') !== false || strpos($clicked_url, 'product') !== false) {
            $this->trigger_automation_by_type('shopping_interest', $subscriber_id, $click_data);
        } elseif (strpos($clicked_url, 'event') !== false) {
            $this->trigger_automation_by_type('event_interest', $subscriber_id, $click_data);
        }
    }

    /**
     * Trigger re-engagement sequence for inactive subscribers
     *
     * @param int $subscriber_id Subscriber ID
     * @param array $inactivity_data Inactivity data
     */
    public function trigger_reengagement_sequence($subscriber_id, $inactivity_data = array()) {
        $this->trigger_automation_by_type('reengagement', $subscriber_id, $inactivity_data);
    }

    /**
     * Trigger automation by type
     *
     * @param string $automation_type Automation type
     * @param int $subscriber_id Subscriber ID
     * @param array $trigger_data Trigger data
     */
    private function trigger_automation_by_type($automation_type, $subscriber_id, $trigger_data = array()) {
        global $wpdb;
        
        $automations_table = $this->db_manager->get_table_name('automations');
        
        // Get active automations of this type
        $automations = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$automations_table}
            WHERE trigger_type = %s 
            AND status = %s
            ORDER BY priority DESC
        ", $automation_type, 'active'));
        
        foreach ($automations as $automation) {
            $subscriber = $this->subscriber_manager->get_subscriber_by_id($subscriber_id);
            
            if (!$subscriber || $subscriber->status !== 'active') {
                continue;
            }
            
            // Check if subscriber matches automation conditions
            if ($this->subscriber_matches_automation($subscriber, $automation)) {
                $automation_data = json_decode($automation->automation_data, true);
                $this->queue_automation_for_subscriber($automation, $subscriber, $automation_data);
                
                // Log automation trigger
                $this->log_automation_activity($automation->id, $subscriber_id, 'triggered', $trigger_data);
            }
        }
    }

    /**
     * Check if subscriber matches automation conditions
     *
     * @param object $subscriber Subscriber object
     * @param object $automation Automation object
     * @return bool True if subscriber matches
     */
    private function subscriber_matches_automation($subscriber, $automation) {
        $trigger_conditions = json_decode($automation->trigger_conditions, true);
        
        // Check segment
        if (!empty($trigger_conditions['segments'])) {
            if (!in_array($subscriber->segment_id, $trigger_conditions['segments'])) {
                return false;
            }
        }
        
        // Check environmental score
        if (!empty($trigger_conditions['min_environmental_score'])) {
            if ($subscriber->environmental_score < $trigger_conditions['min_environmental_score']) {
                return false;
            }
        }
        
        if (!empty($trigger_conditions['max_environmental_score'])) {
            if ($subscriber->environmental_score > $trigger_conditions['max_environmental_score']) {
                return false;
            }
        }
        
        // Check engagement score
        if (!empty($trigger_conditions['min_engagement_score'])) {
            if ($subscriber->engagement_score < $trigger_conditions['min_engagement_score']) {
                return false;
            }
        }
        
        // Check subscription age
        if (!empty($trigger_conditions['days_since_subscription'])) {
            $days_subscribed = (time() - strtotime($subscriber->created_at)) / DAY_IN_SECONDS;
            if ($days_subscribed < $trigger_conditions['days_since_subscription']) {
                return false;
            }
        }
        
        // Check frequency limits
        if (!empty($trigger_conditions['max_emails_per_week'])) {
            $emails_this_week = $this->get_subscriber_email_count($subscriber->id, 7);
            if ($emails_this_week >= $trigger_conditions['max_emails_per_week']) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get subscriber email count for specified days
     *
     * @param int $subscriber_id Subscriber ID
     * @param int $days Number of days to check
     * @return int Number of emails sent
     */
    private function get_subscriber_email_count($subscriber_id, $days) {
        global $wpdb;
        
        $analytics_table = $this->db_manager->get_table_name('campaign_analytics');
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$analytics_table}
            WHERE subscriber_id = %d 
            AND event_type = 'sent'
            AND event_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $subscriber_id, $days));
    }

    /**
     * Update automation next run time
     *
     * @param object $automation Automation object
     */
    private function update_automation_next_run($automation) {
        global $wpdb;
        
        $automations_table = $this->db_manager->get_table_name('automations');
        $automation_data = json_decode($automation->automation_data, true);
        
        // Calculate next run based on frequency
        $frequency = $automation_data['frequency'] ?? 'daily';
        $next_run = null;
        
        switch ($frequency) {
            case 'hourly':
                $next_run = date('Y-m-d H:i:s', time() + HOUR_IN_SECONDS);
                break;
            case 'daily':
                $next_run = date('Y-m-d H:i:s', time() + DAY_IN_SECONDS);
                break;
            case 'weekly':
                $next_run = date('Y-m-d H:i:s', time() + WEEK_IN_SECONDS);
                break;
            case 'monthly':
                $next_run = date('Y-m-d H:i:s', time() + (30 * DAY_IN_SECONDS));
                break;
            case 'once':
                // Mark as completed for one-time automations
                $wpdb->update(
                    $automations_table,
                    array('status' => 'completed', 'updated_at' => current_time('mysql')),
                    array('id' => $automation->id)
                );
                return;
        }
        
        if ($next_run) {
            $wpdb->update(
                $automations_table,
                array('next_run' => $next_run, 'updated_at' => current_time('mysql')),
                array('id' => $automation->id)
            );
        }
    }

    /**
     * Log automation activity
     *
     * @param int $automation_id Automation ID
     * @param int $subscriber_id Subscriber ID
     * @param string $activity_type Activity type
     * @param array $activity_data Activity data
     */
    private function log_automation_activity($automation_id, $subscriber_id, $activity_type, $activity_data = array()) {
        global $wpdb;
        
        $analytics_table = $this->db_manager->get_table_name('campaign_analytics');
        
        $log_data = array(
            'automation_id' => $automation_id,
            'subscriber_id' => $subscriber_id,
            'event_type' => 'automation_' . $activity_type,
            'event_data' => json_encode($activity_data),
            'event_time' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($analytics_table, $log_data);
    }

    /**
     * Check automation triggers for environmental actions
     */
    public function check_automation_triggers() {
        // This method can be extended to check for specific environmental triggers
        // like seasonal campaigns, environmental events, etc.
        
        $this->check_seasonal_triggers();
        $this->check_environmental_events();
        $this->check_inactive_subscribers();
    }

    /**
     * Check seasonal triggers for environmental campaigns
     */
    private function check_seasonal_triggers() {
        $current_month = date('n');
        $seasonal_campaigns = array(
            3 => 'spring_environmental', // March - Spring environmental campaigns
            4 => 'earth_day', // April - Earth Day
            6 => 'world_environment_day', // June - World Environment Day
            9 => 'climate_week', // September - Climate Week
            10 => 'energy_saving', // October - Energy Saving Month
            12 => 'sustainable_holidays' // December - Sustainable Holidays
        );
        
        if (isset($seasonal_campaigns[$current_month])) {
            $this->trigger_seasonal_campaign($seasonal_campaigns[$current_month]);
        }
    }

    /**
     * Check for environmental events and news
     */
    private function check_environmental_events() {
        // This could integrate with environmental news APIs
        // or check for specific environmental events
        
        // For now, we'll implement a simple check for environmental alerts
        $environmental_alerts = get_option('eem_environmental_alerts', array());
        
        foreach ($environmental_alerts as $alert) {
            if ($alert['trigger_date'] === date('Y-m-d') && !$alert['triggered']) {
                $this->trigger_environmental_alert($alert);
                
                // Mark as triggered
                $alert['triggered'] = true;
                update_option('eem_environmental_alerts', $environmental_alerts);
            }
        }
    }

    /**
     * Check for inactive subscribers and trigger re-engagement
     */
    private function check_inactive_subscribers() {
        global $wpdb;
        
        $subscribers_table = $this->db_manager->get_table_name('subscribers');
        
        // Get subscribers who haven't opened an email in 30 days
        $inactive_subscribers = $wpdb->get_results("
            SELECT s.* FROM {$subscribers_table} s
            LEFT JOIN (
                SELECT subscriber_id, MAX(event_time) as last_open
                FROM {$this->db_manager->get_table_name('campaign_analytics')}
                WHERE event_type = 'opened'
                GROUP BY subscriber_id
            ) a ON s.id = a.subscriber_id
            WHERE s.status = 'active'
            AND (a.last_open IS NULL OR a.last_open < DATE_SUB(NOW(), INTERVAL 30 DAY))
            AND s.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            LIMIT 50
        ");
        
        foreach ($inactive_subscribers as $subscriber) {
            do_action('eem_subscriber_inactive', $subscriber->id, array(
                'days_inactive' => 30,
                'last_activity' => $subscriber->last_activity ?? null
            ));
        }
    }

    /**
     * Trigger seasonal campaign
     *
     * @param string $campaign_type Campaign type
     */
    private function trigger_seasonal_campaign($campaign_type) {
        // Trigger automation for seasonal environmental campaigns
        global $wpdb;
        
        $automations_table = $this->db_manager->get_table_name('automations');
        
        $automation = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$automations_table}
            WHERE trigger_type = %s AND status = %s
        ", $campaign_type, 'active'));
        
        if ($automation) {
            // Get all active subscribers for seasonal campaign
            $subscribers = $this->subscriber_manager->get_active_subscribers();
            
            foreach ($subscribers as $subscriber) {
                if ($this->subscriber_matches_automation($subscriber, $automation)) {
                    $automation_data = json_decode($automation->automation_data, true);
                    $this->queue_automation_for_subscriber($automation, $subscriber, $automation_data);
                }
            }
        }
    }

    /**
     * Trigger environmental alert
     *
     * @param array $alert Alert data
     */
    private function trigger_environmental_alert($alert) {
        // Send immediate environmental alerts to relevant subscribers
        $subscribers = $this->subscriber_manager->get_subscribers_by_interests($alert['interests'] ?? array());
        
        foreach ($subscribers as $subscriber) {
            $this->trigger_automation_by_type('environmental_alert', $subscriber->id, $alert);
        }
    }

    /**
     * Get automation statistics
     *
     * @param int $automation_id Automation ID
     * @return array Statistics
     */
    public function get_automation_stats($automation_id) {
        global $wpdb;
        
        $queue_table = $this->db_manager->get_table_name('automation_queue');
        $analytics_table = $this->db_manager->get_table_name('campaign_analytics');
        
        $stats = array();
        
        // Queue statistics
        $queue_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_queued,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing
            FROM {$queue_table}
            WHERE automation_id = %d
        ", $automation_id));
        
        $stats['queue'] = (array) $queue_stats;
        
        // Engagement statistics
        $engagement_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(CASE WHEN event_type LIKE 'automation_email_sent' THEN 1 ELSE 0 END) as emails_sent,
                SUM(CASE WHEN event_type = 'opened' THEN 1 ELSE 0 END) as opens,
                SUM(CASE WHEN event_type = 'clicked' THEN 1 ELSE 0 END) as clicks,
                SUM(CASE WHEN event_type = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribes
            FROM {$analytics_table}
            WHERE automation_id = %d
        ", $automation_id));
        
        $stats['engagement'] = (array) $engagement_stats;
        
        // Calculate rates
        $emails_sent = $stats['engagement']['emails_sent'] ?? 0;
        if ($emails_sent > 0) {
            $stats['rates'] = array(
                'open_rate' => round(($stats['engagement']['opens'] / $emails_sent) * 100, 2),
                'click_rate' => round(($stats['engagement']['clicks'] / $emails_sent) * 100, 2),
                'unsubscribe_rate' => round(($stats['engagement']['unsubscribes'] / $emails_sent) * 100, 2)
            );
        } else {
            $stats['rates'] = array('open_rate' => 0, 'click_rate' => 0, 'unsubscribe_rate' => 0);
        }
        
        return $stats;
    }

    /**
     * Pause automation
     *
     * @param int $automation_id Automation ID
     * @return bool Success status
     */
    public function pause_automation($automation_id) {
        global $wpdb;
        
        $automations_table = $this->db_manager->get_table_name('automations');
        
        $result = $wpdb->update(
            $automations_table,
            array('status' => 'paused', 'updated_at' => current_time('mysql')),
            array('id' => $automation_id)
        );
        
        return $result !== false;
    }

    /**
     * Resume automation
     *
     * @param int $automation_id Automation ID
     * @return bool Success status
     */
    public function resume_automation($automation_id) {
        global $wpdb;
        
        $automations_table = $this->db_manager->get_table_name('automations');
        
        $result = $wpdb->update(
            $automations_table,
            array('status' => 'active', 'updated_at' => current_time('mysql')),
            array('id' => $automation_id)
        );
        
        return $result !== false;
    }

    /**
     * Delete automation and cleanup related data
     *
     * @param int $automation_id Automation ID
     * @return bool Success status
     */
    public function delete_automation($automation_id) {
        global $wpdb;
        
        $automations_table = $this->db_manager->get_table_name('automations');
        $queue_table = $this->db_manager->get_table_name('automation_queue');
        $analytics_table = $this->db_manager->get_table_name('campaign_analytics');
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete automation
            $wpdb->delete($automations_table, array('id' => $automation_id));
            
            // Delete queue items
            $wpdb->delete($queue_table, array('automation_id' => $automation_id));
            
            // Delete analytics
            $wpdb->delete($analytics_table, array('automation_id' => $automation_id));
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
}
