<?php
/**
 * Subscriber Manager for Environmental Email Marketing
 *
 * @package EnvironmentalEmailMarketing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EEM Subscriber Manager Class
 */
class EEM_Subscriber_Manager {
    
    /**
     * Subscribe a user to email lists
     */
    public function subscribe($email, $name = '', $lists = array(), $source = 'website', $preferences = array()) {
        global $wpdb;
        
        if (!is_email($email)) {
            return false;
        }
        
        // Check if subscriber already exists
        $subscriber = $this->get_subscriber_by_email($email);
        
        if ($subscriber) {
            // Update existing subscriber
            $this->update_subscriber($subscriber->id, array(
                'name' => $name ?: $subscriber->name,
                'source' => $source,
                'status' => 'subscribed',
                'eco_preferences' => json_encode(array_merge(
                    json_decode($subscriber->eco_preferences, true) ?: array(),
                    $preferences
                )),
                'updated_at' => current_time('mysql')
            ));
            $subscriber_id = $subscriber->id;
        } else {
            // Create new subscriber
            $confirmation_token = wp_generate_password(32, false);
            $ip_address = $this->get_client_ip();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $subscriber_data = array(
                'email' => $email,
                'name' => $name,
                'status' => get_option('eem_double_optin', true) ? 'pending' : 'subscribed',
                'source' => $source,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'confirmation_token' => $confirmation_token,
                'environmental_score' => $this->calculate_initial_environmental_score($preferences),
                'eco_preferences' => json_encode($preferences),
                'gdpr_consent' => 1,
                'gdpr_consent_date' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
            
            $result = $wpdb->insert($wpdb->prefix . 'eem_subscribers', $subscriber_data);
            
            if (!$result) {
                return false;
            }
            
            $subscriber_id = $wpdb->insert_id;
        }
        
        // Subscribe to lists
        if (empty($lists)) {
            $lists = array('environmental_newsletter');
        }
        
        foreach ($lists as $list_slug) {
            $this->subscribe_to_list($subscriber_id, $list_slug);
        }
        
        // Send confirmation email if double opt-in is enabled
        if (get_option('eem_double_optin', true) && !$subscriber) {
            $this->send_confirmation_email($email, $confirmation_token);
        }
        
        // Send welcome email if already confirmed or single opt-in
        if (!get_option('eem_double_optin', true) || $subscriber) {
            $this->trigger_welcome_automation($subscriber_id, $lists);
        }
        
        // Update list subscriber counts
        $this->update_list_counts($lists);
        
        return $subscriber_id;
    }
    
    /**
     * Unsubscribe a user from email lists
     */
    public function unsubscribe($email, $lists = array()) {
        global $wpdb;
        
        $subscriber = $this->get_subscriber_by_email($email);
        if (!$subscriber) {
            return false;
        }
        
        if (empty($lists)) {
            // Unsubscribe from all lists
            $wpdb->update(
                $wpdb->prefix . 'eem_subscribers',
                array('status' => 'unsubscribed', 'updated_at' => current_time('mysql')),
                array('id' => $subscriber->id)
            );
            
            $wpdb->update(
                $wpdb->prefix . 'eem_subscriber_lists',
                array(
                    'status' => 'unsubscribed',
                    'unsubscribed_at' => current_time('mysql')
                ),
                array('subscriber_id' => $subscriber->id)
            );
        } else {
            // Unsubscribe from specific lists
            foreach ($lists as $list_slug) {
                $list = $this->get_list_by_slug($list_slug);
                if ($list) {
                    $wpdb->update(
                        $wpdb->prefix . 'eem_subscriber_lists',
                        array(
                            'status' => 'unsubscribed',
                            'unsubscribed_at' => current_time('mysql')
                        ),
                        array(
                            'subscriber_id' => $subscriber->id,
                            'list_id' => $list->id
                        )
                    );
                }
            }
            
            // Check if subscriber is still subscribed to any list
            $active_subscriptions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscriber_lists 
                 WHERE subscriber_id = %d AND status = 'subscribed'",
                $subscriber->id
            ));
            
            if ($active_subscriptions == 0) {
                $wpdb->update(
                    $wpdb->prefix . 'eem_subscribers',
                    array('status' => 'unsubscribed', 'updated_at' => current_time('mysql')),
                    array('id' => $subscriber->id)
                );
            }
        }
        
        // Update list subscriber counts
        $this->update_list_counts($lists);
        
        return true;
    }
    
    /**
     * Update subscriber preferences
     */
    public function update_preferences($email, $preferences) {
        global $wpdb;
        
        $subscriber = $this->get_subscriber_by_email($email);
        if (!$subscriber) {
            return false;
        }
        
        $current_preferences = json_decode($subscriber->eco_preferences, true) ?: array();
        $updated_preferences = array_merge($current_preferences, $preferences);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'eem_subscribers',
            array(
                'eco_preferences' => json_encode($updated_preferences),
                'environmental_score' => $this->calculate_environmental_score($subscriber->id, $updated_preferences),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscriber->id)
        );
        
        return $result !== false;
    }
    
    /**
     * Confirm subscriber email
     */
    public function confirm_subscription($token) {
        global $wpdb;
        
        $subscriber = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_subscribers WHERE confirmation_token = %s AND status = 'pending'",
            $token
        ));
        
        if (!$subscriber) {
            return false;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'eem_subscribers',
            array(
                'status' => 'subscribed',
                'confirmed_at' => current_time('mysql'),
                'confirmation_token' => '',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscriber->id)
        );
        
        if ($result) {
            // Update subscriber list statuses
            $wpdb->update(
                $wpdb->prefix . 'eem_subscriber_lists',
                array('status' => 'subscribed'),
                array('subscriber_id' => $subscriber->id, 'status' => 'pending')
            );
            
            // Trigger welcome automation
            $lists = $this->get_subscriber_lists($subscriber->id);
            $this->trigger_welcome_automation($subscriber->id, $lists);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get subscriber by email
     */
    public function get_subscriber_by_email($email) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_subscribers WHERE email = %s",
            $email
        ));
    }
    
    /**
     * Get subscriber by ID
     */
    public function get_subscriber_by_id($subscriber_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_subscribers WHERE id = %d",
            $subscriber_id
        ));
    }
    
    /**
     * Subscribe to specific list
     */
    private function subscribe_to_list($subscriber_id, $list_slug) {
        global $wpdb;
        
        $list = $this->get_list_by_slug($list_slug);
        if (!$list) {
            return false;
        }
        
        // Check if already subscribed
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_subscriber_lists 
             WHERE subscriber_id = %d AND list_id = %d",
            $subscriber_id, $list->id
        ));
        
        if ($existing) {
            // Update existing subscription
            $wpdb->update(
                $wpdb->prefix . 'eem_subscriber_lists',
                array(
                    'status' => get_option('eem_double_optin', true) ? 'pending' : 'subscribed',
                    'subscribed_at' => current_time('mysql'),
                    'unsubscribed_at' => null
                ),
                array('id' => $existing->id)
            );
        } else {
            // Create new subscription
            $wpdb->insert($wpdb->prefix . 'eem_subscriber_lists', array(
                'subscriber_id' => $subscriber_id,
                'list_id' => $list->id,
                'status' => get_option('eem_double_optin', true) ? 'pending' : 'subscribed',
                'subscribed_at' => current_time('mysql'),
                'subscription_method' => 'website'
            ));
        }
        
        return true;
    }
    
    /**
     * Get list by slug
     */
    private function get_list_by_slug($slug) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_lists WHERE slug = %s",
            $slug
        ));
    }
    
    /**
     * Get subscriber lists
     */
    private function get_subscriber_lists($subscriber_id) {
        global $wpdb;
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT l.slug FROM {$wpdb->prefix}eem_lists l
             INNER JOIN {$wpdb->prefix}eem_subscriber_lists sl ON l.id = sl.list_id
             WHERE sl.subscriber_id = %d AND sl.status = 'subscribed'",
            $subscriber_id
        ));
    }
    
    /**
     * Update subscriber
     */
    private function update_subscriber($subscriber_id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'eem_subscribers',
            $data,
            array('id' => $subscriber_id)
        );
    }
    
    /**
     * Calculate initial environmental score
     */
    private function calculate_initial_environmental_score($preferences) {
        $base_score = 25; // Starting score for new subscribers
        
        // Add points based on preferences
        $eco_interests = $preferences['eco_interests'] ?? array();
        $score_boost = count($eco_interests) * 5;
        
        // Add points for commitment level
        $commitment = $preferences['commitment_level'] ?? 'casual';
        switch ($commitment) {
            case 'very_committed':
                $score_boost += 25;
                break;
            case 'committed':
                $score_boost += 15;
                break;
            case 'moderate':
                $score_boost += 10;
                break;
            case 'casual':
                $score_boost += 5;
                break;
        }
        
        return min(100, $base_score + $score_boost);
    }
    
    /**
     * Calculate environmental score based on activity
     */
    private function calculate_environmental_score($subscriber_id, $preferences) {
        global $wpdb;
        
        // Get current score
        $current_subscriber = $this->get_subscriber_by_id($subscriber_id);
        $base_score = $current_subscriber ? $current_subscriber->environmental_score : 25;
        
        // Calculate activity-based score
        $activity_score = 0;
        
        // Email engagement score
        $email_engagement = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics 
             WHERE subscriber_id = %d AND event_type IN ('opened', 'clicked') 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $subscriber_id
        ));
        $activity_score += min(20, $email_engagement * 2);
        
        // Environmental actions score
        $environmental_actions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics 
             WHERE subscriber_id = %d AND environmental_action IS NOT NULL 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $subscriber_id
        ));
        $activity_score += min(30, $environmental_actions * 5);
        
        // Preferences score
        $eco_interests = $preferences['eco_interests'] ?? array();
        $preferences_score = count($eco_interests) * 3;
        
        // Commitment level score
        $commitment = $preferences['commitment_level'] ?? 'casual';
        $commitment_score = 0;
        switch ($commitment) {
            case 'very_committed':
                $commitment_score = 25;
                break;
            case 'committed':
                $commitment_score = 15;
                break;
            case 'moderate':
                $commitment_score = 10;
                break;
            case 'casual':
                $commitment_score = 5;
                break;
        }
        
        $total_score = $base_score + $activity_score + $preferences_score + $commitment_score;
        
        return min(100, max(0, $total_score));
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($email, $token) {
        $confirmation_url = add_query_arg(array(
            'eem_action' => 'confirm',
            'token' => $token
        ), home_url());
        
        $subject = sprintf(__('Please confirm your subscription to %s', 'environmental-email-marketing'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Please click the following link to confirm your subscription: %s', 'environmental-email-marketing'),
            $confirmation_url
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('eem_from_name', get_bloginfo('name')) . ' <' . get_option('eem_from_email', get_option('admin_email')) . '>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Trigger welcome automation
     */
    private function trigger_welcome_automation($subscriber_id, $lists) {
        $automation_engine = new EEM_Automation_Engine();
        $automation_engine->trigger_welcome_sequence_for_subscriber($subscriber_id, $lists);
    }
    
    /**
     * Update list subscriber counts
     */
    private function update_list_counts($lists = array()) {
        global $wpdb;
        
        if (empty($lists)) {
            // Update all lists
            $wpdb->query("
                UPDATE {$wpdb->prefix}eem_lists l
                SET subscriber_count = (
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}eem_subscriber_lists sl 
                    WHERE sl.list_id = l.id AND sl.status = 'subscribed'
                )
            ");
        } else {
            foreach ($lists as $list_slug) {
                $list = $this->get_list_by_slug($list_slug);
                if ($list) {
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscriber_lists 
                         WHERE list_id = %d AND status = 'subscribed'",
                        $list->id
                    ));
                    
                    $wpdb->update(
                        $wpdb->prefix . 'eem_lists',
                        array('subscriber_count' => $count),
                        array('id' => $list->id)
                    );
                }
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Get subscribers by list
     */
    public function get_subscribers_by_list($list_slug, $status = 'subscribed', $limit = null, $offset = 0) {
        global $wpdb;
        
        $list = $this->get_list_by_slug($list_slug);
        if (!$list) {
            return array();
        }
        
        $sql = "SELECT s.* FROM {$wpdb->prefix}eem_subscribers s
                INNER JOIN {$wpdb->prefix}eem_subscriber_lists sl ON s.id = sl.subscriber_id
                WHERE sl.list_id = %d AND sl.status = %s";
        
        $params = array($list->id, $status);
        
        if ($limit) {
            $sql .= " LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get subscribers by segment
     */
    public function get_subscribers_by_segment($segment_id) {
        global $wpdb;
        
        // Get segment conditions
        $segment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_segments WHERE id = %d",
            $segment_id
        ));
        
        if (!$segment) {
            return array();
        }
        
        $conditions = json_decode($segment->conditions, true);
        if (!$conditions) {
            return array();
        }
        
        // Build dynamic query based on conditions
        $where_clauses = array();
        $params = array();
        
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            
            switch ($field) {
                case 'environmental_score':
                    $where_clauses[] = "s.environmental_score $operator %d";
                    $params[] = $value;
                    break;
                    
                case 'days_since_signup':
                    $where_clauses[] = "DATEDIFF(NOW(), s.created_at) $operator %d";
                    $params[] = $value;
                    break;
                    
                case 'last_engagement':
                    $days = is_numeric($value) ? $value : 30;
                    $where_clauses[] = "s.last_sent >= DATE_SUB(NOW(), INTERVAL %d DAY)";
                    $params[] = $days;
                    break;
                    
                case 'status':
                    $where_clauses[] = "s.status = %s";
                    $params[] = $value;
                    break;
            }
        }
        
        if (empty($where_clauses)) {
            return array();
        }
        
        $sql = "SELECT s.* FROM {$wpdb->prefix}eem_subscribers s WHERE " . implode(' AND ', $where_clauses);
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Update subscriber environmental data
     */
    public function update_environmental_data($subscriber_id, $data) {
        global $wpdb;
        
        $update_data = array();
        
        if (isset($data['environmental_score'])) {
            $update_data['environmental_score'] = max(0, min(100, $data['environmental_score']));
        }
        
        if (isset($data['carbon_footprint'])) {
            $update_data['carbon_footprint'] = max(0, $data['carbon_footprint']);
        }
        
        if (isset($data['eco_preferences'])) {
            $current_subscriber = $this->get_subscriber_by_id($subscriber_id);
            $current_preferences = json_decode($current_subscriber->eco_preferences, true) ?: array();
            $updated_preferences = array_merge($current_preferences, $data['eco_preferences']);
            $update_data['eco_preferences'] = json_encode($updated_preferences);
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = current_time('mysql');
            
            return $wpdb->update(
                $wpdb->prefix . 'eem_subscribers',
                $update_data,
                array('id' => $subscriber_id)
            );
        }
        
        return false;
    }
    
    /**
     * Get subscriber statistics
     */
    public function get_subscriber_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total subscribers by status
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}eem_subscribers GROUP BY status"
        );
        
        foreach ($status_counts as $status) {
            $stats['by_status'][$status->status] = $status->count;
        }
        
        // Total subscribers
        $stats['total'] = array_sum($stats['by_status']);
        
        // Growth rate (last 30 days)
        $new_subscribers = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_subscribers 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $stats['growth_30_days'] = $new_subscribers;
        
        // Average environmental score
        $avg_score = $wpdb->get_var(
            "SELECT AVG(environmental_score) FROM {$wpdb->prefix}eem_subscribers 
             WHERE status = 'subscribed'"
        );
        $stats['avg_environmental_score'] = round($avg_score, 2);
        
        // Top sources
        $top_sources = $wpdb->get_results(
            "SELECT source, COUNT(*) as count FROM {$wpdb->prefix}eem_subscribers 
             WHERE status = 'subscribed' GROUP BY source ORDER BY count DESC LIMIT 5"
        );
        $stats['top_sources'] = $top_sources;
        
        return $stats;
    }
    
    /**
     * Export subscribers
     */
    public function export_subscribers($list_slug = null, $format = 'csv') {
        global $wpdb;
        
        if ($list_slug) {
            $subscribers = $this->get_subscribers_by_list($list_slug);
        } else {
            $subscribers = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}eem_subscribers WHERE status = 'subscribed'"
            );
        }
        
        if ($format === 'csv') {
            return $this->export_to_csv($subscribers);
        }
        
        return $subscribers;
    }
    
    /**
     * Export to CSV
     */
    private function export_to_csv($subscribers) {
        $csv_data = array();
        $csv_data[] = array('Email', 'Name', 'Status', 'Environmental Score', 'Carbon Footprint', 'Created At');
        
        foreach ($subscribers as $subscriber) {
            $csv_data[] = array(
                $subscriber->email,
                $subscriber->name,
                $subscriber->status,
                $subscriber->environmental_score,
                $subscriber->carbon_footprint,
                $subscriber->created_at
            );
        }
        
        return $csv_data;
    }
}
