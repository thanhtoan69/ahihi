<?php
/**
 * Campaign Manager for Environmental Email Marketing
 *
 * @package EnvironmentalEmailMarketing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EEM Campaign Manager Class
 */
class EEM_Campaign_Manager {
    
    /**
     * Create a new campaign
     */
    public function create_campaign($data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'subject' => '',
            'preview_text' => '',
            'content' => '',
            'type' => 'newsletter',
            'status' => 'draft',
            'template_id' => null,
            'list_ids' => '',
            'environmental_theme' => 'general_sustainability',
            'sender_name' => get_option('eem_from_name', get_bloginfo('name')),
            'sender_email' => get_option('eem_from_email', get_option('admin_email')),
            'reply_to' => get_option('eem_reply_to', get_option('admin_email')),
            'track_opens' => 1,
            'track_clicks' => 1,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $campaign_data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($campaign_data['name']) || empty($campaign_data['subject']) || empty($campaign_data['content'])) {
            return false;
        }
        
        // Process environmental theme data
        if (!empty($campaign_data['environmental_theme'])) {
            $campaign_data['eco_impact_data'] = $this->generate_eco_impact_data($campaign_data['environmental_theme']);
        }
        
        // Insert campaign
        $result = $wpdb->insert($wpdb->prefix . 'eem_campaigns', $campaign_data);
        
        if ($result) {
            $campaign_id = $wpdb->insert_id;
            
            // Log campaign creation
            do_action('eem_campaign_created', $campaign_id, $campaign_data);
            
            return $campaign_id;
        }
        
        return false;
    }
    
    /**
     * Update campaign
     */
    public function update_campaign($campaign_id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        // Process environmental theme data if changed
        if (!empty($data['environmental_theme'])) {
            $data['eco_impact_data'] = $this->generate_eco_impact_data($data['environmental_theme']);
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'eem_campaigns',
            $data,
            array('id' => $campaign_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('eem_campaign_updated', $campaign_id, $data);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get campaign by ID
     */
    public function get_campaign($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eem_campaigns WHERE id = %d",
            $campaign_id
        ));
    }
    
    /**
     * Get campaigns with filters
     */
    public function get_campaigns($filters = array()) {
        global $wpdb;
        
        $where_clauses = array();
        $params = array();
        
        // Status filter
        if (!empty($filters['status'])) {
            $where_clauses[] = "status = %s";
            $params[] = $filters['status'];
        }
        
        // Type filter
        if (!empty($filters['type'])) {
            $where_clauses[] = "type = %s";
            $params[] = $filters['type'];
        }
        
        // Environmental theme filter
        if (!empty($filters['environmental_theme'])) {
            $where_clauses[] = "environmental_theme = %s";
            $params[] = $filters['environmental_theme'];
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "created_at >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "created_at <= %s";
            $params[] = $filters['date_to'];
        }
        
        // Build query
        $sql = "SELECT * FROM {$wpdb->prefix}eem_campaigns";
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        // Limit and offset
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . intval($filters['offset']);
            }
        }
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    /**
     * Schedule campaign
     */
    public function schedule_campaign($campaign_id, $scheduled_time) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign || $campaign->status !== 'draft') {
            return false;
        }
        
        // Validate schedule time
        if (strtotime($scheduled_time) <= time()) {
            return false;
        }
        
        // Calculate recipients
        $recipients = $this->get_campaign_recipients($campaign_id);
        $total_recipients = count($recipients);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'eem_campaigns',
            array(
                'status' => 'scheduled',
                'scheduled_at' => $scheduled_time,
                'total_recipients' => $total_recipients,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $campaign_id)
        );
        
        if ($result) {
            // Schedule WordPress cron job
            wp_schedule_single_event(strtotime($scheduled_time), 'eem_send_campaign', array($campaign_id));
            
            do_action('eem_campaign_scheduled', $campaign_id, $scheduled_time);
            return true;
        }
        
        return false;
    }
    
    /**
     * Send campaign immediately
     */
    public function send_campaign($campaign_id) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return false;
        }
        
        // Check if campaign can be sent
        if (!in_array($campaign->status, array('draft', 'scheduled'))) {
            return false;
        }
        
        // Update campaign status
        $wpdb->update(
            $wpdb->prefix . 'eem_campaigns',
            array(
                'status' => 'sending',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $campaign_id)
        );
        
        // Get recipients
        $recipients = $this->get_campaign_recipients($campaign_id);
        
        if (empty($recipients)) {
            $wpdb->update(
                $wpdb->prefix . 'eem_campaigns',
                array('status' => 'cancelled'),
                array('id' => $campaign_id)
            );
            return false;
        }
        
        // Send to recipients in batches
        $batch_size = apply_filters('eem_campaign_batch_size', 100);
        $batches = array_chunk($recipients, $batch_size);
        $total_sent = 0;
        $total_delivered = 0;
        
        foreach ($batches as $batch) {
            $batch_result = $this->send_to_batch($campaign, $batch);
            $total_sent += $batch_result['sent'];
            $total_delivered += $batch_result['delivered'];
            
            // Small delay between batches to avoid overwhelming the server
            usleep(100000); // 0.1 seconds
        }
        
        // Update campaign statistics
        $wpdb->update(
            $wpdb->prefix . 'eem_campaigns',
            array(
                'status' => 'sent',
                'sent_at' => current_time('mysql'),
                'total_recipients' => count($recipients),
                'total_sent' => $total_sent,
                'total_delivered' => $total_delivered,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $campaign_id)
        );
        
        do_action('eem_campaign_sent', $campaign_id, $total_sent, $total_delivered);
        
        return true;
    }
    
    /**
     * Send scheduled campaigns
     */
    public function send_scheduled_campaigns() {
        global $wpdb;
        
        $scheduled_campaigns = $wpdb->get_results(
            "SELECT id FROM {$wpdb->prefix}eem_campaigns 
             WHERE status = 'scheduled' AND scheduled_at <= NOW()"
        );
        
        foreach ($scheduled_campaigns as $campaign) {
            $this->send_campaign($campaign->id);
        }
    }
    
    /**
     * Get campaign recipients
     */
    private function get_campaign_recipients($campaign_id) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return array();
        }
        
        $recipients = array();
        
        // Get recipients from lists
        if (!empty($campaign->list_ids)) {
            $list_ids = explode(',', $campaign->list_ids);
            
            foreach ($list_ids as $list_id) {
                $list_recipients = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT s.* FROM {$wpdb->prefix}eem_subscribers s
                     INNER JOIN {$wpdb->prefix}eem_subscriber_lists sl ON s.id = sl.subscriber_id
                     WHERE sl.list_id = %d AND sl.status = 'subscribed' AND s.status = 'subscribed'",
                    $list_id
                ));
                
                $recipients = array_merge($recipients, $list_recipients);
            }
        }
        
        // Apply segment conditions if specified
        if (!empty($campaign->segment_conditions)) {
            $segment_conditions = json_decode($campaign->segment_conditions, true);
            if ($segment_conditions) {
                $recipients = $this->filter_recipients_by_segment($recipients, $segment_conditions);
            }
        }
        
        // Remove duplicates
        $unique_recipients = array();
        $seen_emails = array();
        
        foreach ($recipients as $recipient) {
            if (!in_array($recipient->email, $seen_emails)) {
                $unique_recipients[] = $recipient;
                $seen_emails[] = $recipient->email;
            }
        }
        
        return $unique_recipients;
    }
    
    /**
     * Filter recipients by segment conditions
     */
    private function filter_recipients_by_segment($recipients, $conditions) {
        $filtered = array();
        
        foreach ($recipients as $recipient) {
            $matches = true;
            
            foreach ($conditions as $condition) {
                $field = $condition['field'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                
                switch ($field) {
                    case 'environmental_score':
                        $recipient_value = $recipient->environmental_score;
                        break;
                    case 'days_since_signup':
                        $recipient_value = (time() - strtotime($recipient->created_at)) / DAY_IN_SECONDS;
                        break;
                    case 'carbon_footprint':
                        $recipient_value = $recipient->carbon_footprint;
                        break;
                    default:
                        continue 2;
                }
                
                if (!$this->evaluate_condition($recipient_value, $operator, $value)) {
                    $matches = false;
                    break;
                }
            }
            
            if ($matches) {
                $filtered[] = $recipient;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Evaluate segment condition
     */
    private function evaluate_condition($recipient_value, $operator, $condition_value) {
        switch ($operator) {
            case '=':
                return $recipient_value == $condition_value;
            case '!=':
                return $recipient_value != $condition_value;
            case '>':
                return $recipient_value > $condition_value;
            case '>=':
                return $recipient_value >= $condition_value;
            case '<':
                return $recipient_value < $condition_value;
            case '<=':
                return $recipient_value <= $condition_value;
            case 'contains':
                return strpos(strtolower($recipient_value), strtolower($condition_value)) !== false;
            default:
                return false;
        }
    }
    
    /**
     * Send campaign to batch of recipients
     */
    private function send_to_batch($campaign, $recipients) {
        $email_provider = eem()->get_email_provider();
        $sent_count = 0;
        $delivered_count = 0;
        
        foreach ($recipients as $recipient) {
            try {
                // Personalize content
                $personalized_content = $this->personalize_content($campaign->content, $recipient, $campaign);
                $personalized_subject = $this->personalize_subject($campaign->subject, $recipient, $campaign);
                
                // Prepare email data
                $email_data = array(
                    'to' => $recipient->email,
                    'to_name' => $recipient->name,
                    'subject' => $personalized_subject,
                    'content' => $personalized_content,
                    'from_name' => $campaign->sender_name,
                    'from_email' => $campaign->sender_email,
                    'reply_to' => $campaign->reply_to,
                    'track_opens' => $campaign->track_opens,
                    'track_clicks' => $campaign->track_clicks,
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $recipient->id
                );
                
                // Send email
                $result = $email_provider->send_email($email_data);
                
                if ($result) {
                    $sent_count++;
                    $delivered_count++;
                    
                    // Log email sent
                    $this->log_email_event($campaign->id, $recipient->id, 'sent');
                    
                    // Update subscriber last_sent
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->prefix . 'eem_subscribers',
                        array('last_sent' => current_time('mysql')),
                        array('id' => $recipient->id)
                    );
                }
                
            } catch (Exception $e) {
                // Log error
                error_log('EEM Campaign Send Error: ' . $e->getMessage());
            }
        }
        
        return array(
            'sent' => $sent_count,
            'delivered' => $delivered_count
        );
    }
    
    /**
     * Personalize email content
     */
    private function personalize_content($content, $recipient, $campaign) {
        // Basic personalizations
        $replacements = array(
            '{{subscriber_name}}' => $recipient->name ?: 'Environmental Champion',
            '{{subscriber_email}}' => $recipient->email,
            '{{environmental_score}}' => $recipient->environmental_score,
            '{{carbon_footprint}}' => $recipient->carbon_footprint,
            '{{campaign_subject}}' => $campaign->subject,
            '{{current_year}}' => date('Y'),
            '{{current_date}}' => date('F j, Y'),
            '{{site_name}}' => get_bloginfo('name'),
            '{{site_url}}' => home_url(),
        );
        
        // Environmental personalizations
        $eco_preferences = json_decode($recipient->eco_preferences, true) ?: array();
        
        if (!empty($eco_preferences['eco_interests'])) {
            $replacements['{{eco_interests}}'] = implode(', ', $eco_preferences['eco_interests']);
        }
        
        // Calculate environmental impact
        $carbon_saved = $this->calculate_carbon_saved($recipient);
        $replacements['{{carbon_saved}}'] = $carbon_saved;
        
        // Add tracking pixels and links
        $content = $this->add_tracking_elements($content, $campaign->id, $recipient->id);
        
        // Add unsubscribe link
        $unsubscribe_url = $this->generate_unsubscribe_url($recipient->email, $campaign->id);
        $replacements['{{unsubscribe_url}}'] = $unsubscribe_url;
        
        // Add preference center link
        $preference_url = $this->generate_preference_url($recipient->email);
        $replacements['{{preference_url}}'] = $preference_url;
        
        // Perform replacements
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Personalize email subject
     */
    private function personalize_subject($subject, $recipient, $campaign) {
        $replacements = array(
            '{{subscriber_name}}' => $recipient->name ?: 'Environmental Champion',
            '{{environmental_score}}' => $recipient->environmental_score,
            '{{site_name}}' => get_bloginfo('name'),
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $subject);
    }
    
    /**
     * Add tracking elements to content
     */
    private function add_tracking_elements($content, $campaign_id, $subscriber_id) {
        // Add open tracking pixel
        if (get_option('eem_track_opens', true)) {
            $tracking_pixel = $this->generate_tracking_pixel($campaign_id, $subscriber_id);
            $content .= $tracking_pixel;
        }
        
        // Add click tracking to links
        if (get_option('eem_track_clicks', true)) {
            $content = $this->add_click_tracking($content, $campaign_id, $subscriber_id);
        }
        
        return $content;
    }
    
    /**
     * Generate tracking pixel
     */
    private function generate_tracking_pixel($campaign_id, $subscriber_id) {
        $tracking_url = add_query_arg(array(
            'eem_action' => 'track_open',
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id,
            'token' => wp_create_nonce('eem_track_' . $campaign_id . '_' . $subscriber_id)
        ), home_url());
        
        return '<img src="' . $tracking_url . '" width="1" height="1" style="display:none;" alt="">';
    }
    
    /**
     * Add click tracking to links
     */
    private function add_click_tracking($content, $campaign_id, $subscriber_id) {
        // Find all links in content
        $pattern = '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i';
        
        return preg_replace_callback($pattern, function($matches) use ($campaign_id, $subscriber_id) {
            $original_url = $matches[1];
            
            // Skip tracking for certain URLs
            $skip_urls = array(
                'mailto:',
                'tel:',
                '#',
                'javascript:',
                home_url() . '?eem_action=unsubscribe',
                home_url() . '?eem_action=preferences'
            );
            
            foreach ($skip_urls as $skip) {
                if (strpos($original_url, $skip) === 0) {
                    return $matches[0];
                }
            }
            
            // Generate tracking URL
            $tracking_url = add_query_arg(array(
                'eem_action' => 'track_click',
                'campaign_id' => $campaign_id,
                'subscriber_id' => $subscriber_id,
                'url' => urlencode($original_url),
                'token' => wp_create_nonce('eem_track_' . $campaign_id . '_' . $subscriber_id)
            ), home_url());
            
            return str_replace($original_url, $tracking_url, $matches[0]);
        }, $content);
    }
    
    /**
     * Calculate carbon saved for subscriber
     */
    private function calculate_carbon_saved($recipient) {
        global $wpdb;
        
        // Calculate based on environmental actions
        $environmental_actions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics 
             WHERE subscriber_id = %d AND environmental_action IS NOT NULL",
            $recipient->id
        ));
        
        // Base calculation: each environmental action saves ~0.5kg CO2
        $base_carbon = $environmental_actions * 0.5;
        
        // Add bonus based on environmental score
        $score_bonus = ($recipient->environmental_score / 100) * 2;
        
        return round($base_carbon + $score_bonus, 1);
    }
    
    /**
     * Generate unsubscribe URL
     */
    private function generate_unsubscribe_url($email, $campaign_id) {
        return add_query_arg(array(
            'eem_action' => 'unsubscribe',
            'email' => urlencode($email),
            'campaign_id' => $campaign_id,
            'token' => wp_create_nonce('eem_unsubscribe_' . $email)
        ), home_url());
    }
    
    /**
     * Generate preference center URL
     */
    private function generate_preference_url($email) {
        return add_query_arg(array(
            'eem_action' => 'preferences',
            'email' => urlencode($email),
            'token' => wp_create_nonce('eem_preferences_' . $email)
        ), home_url());
    }
    
    /**
     * Log email event
     */
    private function log_email_event($campaign_id, $subscriber_id, $event_type, $event_data = array()) {
        global $wpdb;
        
        $analytics_data = array(
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id,
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($wpdb->prefix . 'eem_analytics', $analytics_data);
    }
    
    /**
     * Generate eco impact data
     */
    private function generate_eco_impact_data($environmental_theme) {
        $eco_data = array(
            'theme' => $environmental_theme,
            'carbon_impact' => 0,
            'environmental_benefits' => array(),
            'sustainability_tips' => array()
        );
        
        switch ($environmental_theme) {
            case 'climate_change':
                $eco_data['carbon_impact'] = -2.5; // kg CO2 saved per action
                $eco_data['environmental_benefits'] = array(
                    'Reduced greenhouse gas emissions',
                    'Climate action awareness',
                    'Renewable energy promotion'
                );
                $eco_data['sustainability_tips'] = array(
                    'Switch to renewable energy sources',
                    'Reduce energy consumption at home',
                    'Use public transportation or cycle'
                );
                break;
                
            case 'waste_reduction':
                $eco_data['carbon_impact'] = -1.8;
                $eco_data['environmental_benefits'] = array(
                    'Reduced landfill waste',
                    'Material conservation',
                    'Pollution prevention'
                );
                $eco_data['sustainability_tips'] = array(
                    'Practice the 3 Rs: Reduce, Reuse, Recycle',
                    'Choose products with minimal packaging',
                    'Compost organic waste'
                );
                break;
                
            case 'sustainable_products':
                $eco_data['carbon_impact'] = -3.2;
                $eco_data['environmental_benefits'] = array(
                    'Support for eco-friendly businesses',
                    'Reduced environmental footprint',
                    'Sustainable supply chain promotion'
                );
                $eco_data['sustainability_tips'] = array(
                    'Choose certified sustainable products',
                    'Support local and organic producers',
                    'Invest in durable, long-lasting items'
                );
                break;
                
            case 'water_conservation':
                $eco_data['carbon_impact'] = -1.2;
                $eco_data['environmental_benefits'] = array(
                    'Water resource preservation',
                    'Reduced water pollution',
                    'Energy savings from water treatment'
                );
                $eco_data['sustainability_tips'] = array(
                    'Fix leaks promptly',
                    'Install water-efficient fixtures',
                    'Collect rainwater for gardening'
                );
                break;
                
            default:
                $eco_data['carbon_impact'] = -2.0;
                $eco_data['environmental_benefits'] = array(
                    'General environmental awareness',
                    'Sustainable lifestyle promotion',
                    'Community environmental action'
                );
                $eco_data['sustainability_tips'] = array(
                    'Make small daily changes for the environment',
                    'Share environmental knowledge with others',
                    'Support environmental causes and organizations'
                );
        }
        
        return json_encode($eco_data);
    }
    
    /**
     * Delete campaign
     */
    public function delete_campaign($campaign_id) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return false;
        }
        
        // Don't delete campaigns that are currently sending
        if ($campaign->status === 'sending') {
            return false;
        }
        
        // Delete analytics data
        $wpdb->delete($wpdb->prefix . 'eem_analytics', array('campaign_id' => $campaign_id));
        
        // Delete A/B test data
        $wpdb->delete($wpdb->prefix . 'eem_ab_tests', array('campaign_id' => $campaign_id));
        
        // Delete campaign
        $result = $wpdb->delete($wpdb->prefix . 'eem_campaigns', array('id' => $campaign_id));
        
        if ($result) {
            do_action('eem_campaign_deleted', $campaign_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Duplicate campaign
     */
    public function duplicate_campaign($campaign_id, $new_name = null) {
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return false;
        }
        
        // Prepare data for new campaign
        $campaign_data = (array) $campaign;
        unset($campaign_data['id']);
        unset($campaign_data['status']);
        unset($campaign_data['scheduled_at']);
        unset($campaign_data['sent_at']);
        unset($campaign_data['total_recipients']);
        unset($campaign_data['total_sent']);
        unset($campaign_data['total_delivered']);
        unset($campaign_data['total_opens']);
        unset($campaign_data['total_clicks']);
        unset($campaign_data['total_bounces']);
        unset($campaign_data['total_complaints']);
        unset($campaign_data['total_unsubscribes']);
        
        $campaign_data['name'] = $new_name ?: ($campaign->name . ' (Copy)');
        $campaign_data['status'] = 'draft';
        $campaign_data['created_by'] = get_current_user_id();
        
        return $this->create_campaign($campaign_data);
    }
    
    /**
     * Get campaign statistics
     */
    public function get_campaign_stats($campaign_id) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return false;
        }
        
        $stats = array(
            'recipients' => $campaign->total_recipients,
            'sent' => $campaign->total_sent,
            'delivered' => $campaign->total_delivered,
            'opens' => $campaign->total_opens,
            'clicks' => $campaign->total_clicks,
            'bounces' => $campaign->total_bounces,
            'complaints' => $campaign->total_complaints,
            'unsubscribes' => $campaign->total_unsubscribes
        );
        
        // Calculate rates
        if ($stats['delivered'] > 0) {
            $stats['open_rate'] = round(($stats['opens'] / $stats['delivered']) * 100, 2);
            $stats['click_rate'] = round(($stats['clicks'] / $stats['delivered']) * 100, 2);
            $stats['bounce_rate'] = round(($stats['bounces'] / $stats['sent']) * 100, 2);
            $stats['unsubscribe_rate'] = round(($stats['unsubscribes'] / $stats['delivered']) * 100, 2);
        } else {
            $stats['open_rate'] = 0;
            $stats['click_rate'] = 0;
            $stats['bounce_rate'] = 0;
            $stats['unsubscribe_rate'] = 0;
        }
        
        // Environmental engagement score
        $environmental_engagement = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(sustainability_engagement) FROM {$wpdb->prefix}eem_analytics 
             WHERE campaign_id = %d AND sustainability_engagement > 0",
            $campaign_id
        ));
        
        $stats['environmental_engagement_score'] = round($environmental_engagement ?: 0, 2);
        
        return $stats;
    }
}
