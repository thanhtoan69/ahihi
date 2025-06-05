<?php
/**
 * Environmental Social Viral Sharing Manager
 * 
 * Handles social media sharing functionality with tracking
 */

class Environmental_Social_Viral_Sharing_Manager {
    
    private static $instance = null;
    private $supported_platforms;
    private $wpdb;
    private $tables;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $database = new Environmental_Social_Viral_Database();
        $this->tables = $database->get_all_tables();
        
        $this->supported_platforms = array(
            'facebook' => array(
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#1877f2',
                'share_url' => 'https://www.facebook.com/sharer/sharer.php?u=',
                'tracking_params' => array('utm_source' => 'facebook', 'utm_medium' => 'social')
            ),
            'twitter' => array(
                'name' => 'Twitter',
                'icon' => 'fab fa-twitter',
                'color' => '#1da1f2',
                'share_url' => 'https://twitter.com/intent/tweet?url=',
                'tracking_params' => array('utm_source' => 'twitter', 'utm_medium' => 'social')
            ),
            'linkedin' => array(
                'name' => 'LinkedIn',
                'icon' => 'fab fa-linkedin-in',
                'color' => '#0077b5',
                'share_url' => 'https://www.linkedin.com/sharing/share-offsite/?url=',
                'tracking_params' => array('utm_source' => 'linkedin', 'utm_medium' => 'social')
            ),
            'whatsapp' => array(
                'name' => 'WhatsApp',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25d366',
                'share_url' => 'https://api.whatsapp.com/send?text=',
                'tracking_params' => array('utm_source' => 'whatsapp', 'utm_medium' => 'social')
            ),
            'telegram' => array(
                'name' => 'Telegram',
                'icon' => 'fab fa-telegram-plane',
                'color' => '#0088cc',
                'share_url' => 'https://t.me/share/url?url=',
                'tracking_params' => array('utm_source' => 'telegram', 'utm_medium' => 'social')
            ),
            'email' => array(
                'name' => 'Email',
                'icon' => 'fas fa-envelope',
                'color' => '#666666',
                'share_url' => 'mailto:?subject=',
                'tracking_params' => array('utm_source' => 'email', 'utm_medium' => 'email')
            ),
            'copy' => array(
                'name' => 'Copy Link',
                'icon' => 'fas fa-copy',
                'color' => '#666666',
                'share_url' => '',
                'tracking_params' => array('utm_source' => 'copy', 'utm_medium' => 'direct')
            )
        );
    }
    
    /**
     * Generate sharing URL with tracking parameters
     */
    public function generate_share_url($content_id, $platform, $additional_params = array()) {
        $content_url = get_permalink($content_id);
        $post = get_post($content_id);
        
        if (!$content_url || !$post) {
            return false;
        }
        
        // Add tracking parameters
        $tracking_params = $this->get_tracking_parameters($platform, $content_id);
        $tracking_params = array_merge($tracking_params, $additional_params);
        
        $tracked_url = add_query_arg($tracking_params, $content_url);
        
        // Generate platform-specific share URL
        $share_url = $this->build_platform_share_url($platform, $tracked_url, $post);
        
        // Log the share URL generation
        $this->log_share_url_generation($content_id, $platform, $share_url);
        
        return $share_url;
    }
    
    /**
     * Build platform-specific share URL
     */
    private function build_platform_share_url($platform, $url, $post) {
        if (!isset($this->supported_platforms[$platform])) {
            return false;
        }
        
        $platform_config = $this->supported_platforms[$platform];
        $share_url = $platform_config['share_url'];
        
        switch ($platform) {
            case 'facebook':
                return $share_url . urlencode($url);
                
            case 'twitter':
                $text = $this->generate_twitter_text($post);
                return $share_url . urlencode($url) . '&text=' . urlencode($text);
                
            case 'linkedin':
                return $share_url . urlencode($url);
                
            case 'whatsapp':
                $text = $this->generate_whatsapp_text($post, $url);
                return $share_url . urlencode($text);
                
            case 'telegram':
                $text = $this->generate_telegram_text($post);
                return $share_url . urlencode($url) . '&text=' . urlencode($text);
                
            case 'email':
                $subject = $this->generate_email_subject($post);
                $body = $this->generate_email_body($post, $url);
                return $share_url . urlencode($subject) . '&body=' . urlencode($body);
                
            case 'copy':
                return $url;
                
            default:
                return $url;
        }
    }
    
    /**
     * Generate Twitter share text
     */
    private function generate_twitter_text($post) {
        $title = $post->post_title;
        $hashtags = $this->get_relevant_hashtags($post);
        
        // Limit to Twitter's character limit
        $max_length = 280 - strlen($hashtags) - 50; // Leave room for URL and hashtags
        
        if (strlen($title) > $max_length) {
            $title = substr($title, 0, $max_length - 3) . '...';
        }
        
        return $title . ' ' . $hashtags;
    }
    
    /**
     * Generate WhatsApp share text
     */
    private function generate_whatsapp_text($post, $url) {
        $title = $post->post_title;
        $excerpt = $this->get_post_excerpt($post, 100);
        
        return $title . "\n\n" . $excerpt . "\n\n" . $url;
    }
    
    /**
     * Generate Telegram share text
     */
    private function generate_telegram_text($post) {
        $title = $post->post_title;
        $excerpt = $this->get_post_excerpt($post, 150);
        
        return $title . "\n\n" . $excerpt;
    }
    
    /**
     * Generate email subject
     */
    private function generate_email_subject($post) {
        return sprintf(__('Check out: %s', 'environmental-social-viral'), $post->post_title);
    }
    
    /**
     * Generate email body
     */
    private function generate_email_body($post, $url) {
        $title = $post->post_title;
        $excerpt = $this->get_post_excerpt($post, 200);
        
        $body = sprintf(
            __("Hi,\n\nI thought you might be interested in this: %s\n\n%s\n\nRead more: %s\n\nBest regards", 'environmental-social-viral'),
            $title,
            $excerpt,
            $url
        );
        
        return $body;
    }
    
    /**
     * Get relevant hashtags for content
     */
    private function get_relevant_hashtags($post) {
        $hashtags = array('#environment', '#sustainability');
        
        // Get post categories and tags
        $categories = get_the_category($post->ID);
        $tags = get_the_tags($post->ID);
        
        foreach ($categories as $category) {
            $hashtags[] = '#' . sanitize_title($category->name);
        }
        
        if ($tags) {
            foreach ($tags as $tag) {
                $hashtags[] = '#' . sanitize_title($tag->name);
            }
        }
        
        // Limit hashtags
        $hashtags = array_slice(array_unique($hashtags), 0, 5);
        
        return implode(' ', $hashtags);
    }
    
    /**
     * Get post excerpt with length limit
     */
    private function get_post_excerpt($post, $length = 150) {
        $excerpt = $post->post_excerpt;
        
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($post->post_content, 25, '...');
        }
        
        if (strlen($excerpt) > $length) {
            $excerpt = substr($excerpt, 0, $length - 3) . '...';
        }
        
        return strip_tags($excerpt);
    }
    
    /**
     * Get tracking parameters for platform
     */
    private function get_tracking_parameters($platform, $content_id) {
        $base_params = array(
            'utm_campaign' => 'social_sharing',
            'utm_content' => $content_id,
            'utm_term' => get_current_user_id() ?: 'anonymous'
        );
        
        if (isset($this->supported_platforms[$platform]['tracking_params'])) {
            $base_params = array_merge($base_params, $this->supported_platforms[$platform]['tracking_params']);
        }
        
        return $base_params;
    }
    
    /**
     * Track social share
     */
    public function track_share($platform, $content_id, $content_type = 'post', $user_id = null, $additional_data = array()) {
        $share_data = array(
            'user_id' => $user_id ?: get_current_user_id(),
            'content_id' => $content_id,
            'content_type' => $content_type,
            'platform' => $platform,
            'share_url' => $this->generate_share_url($content_id, $platform),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? '',
            'share_data' => json_encode($additional_data),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert($this->tables['shares'], $share_data);
        
        if ($result) {
            $share_id = $this->wpdb->insert_id;
            
            // Update viral content metrics
            $this->update_viral_content_metrics($content_id, $content_type, 'share');
            
            // Update user sharing stats
            $this->update_user_sharing_stats($share_data['user_id'], $platform);
            
            // Trigger sharing hooks
            do_action('env_social_viral_share_tracked', $share_id, $share_data);
            
            return $share_id;
        }
        
        return false;
    }
    
    /**
     * Track share click
     */
    public function track_share_click($share_id, $additional_data = array()) {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->tables['shares']} 
                 SET click_count = click_count + 1, 
                     updated_at = %s 
                 WHERE id = %d",
                current_time('mysql'),
                $share_id
            )
        );
        
        if ($result) {
            // Get share data
            $share = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->tables['shares']} WHERE id = %d",
                    $share_id
                )
            );
            
            if ($share) {
                // Update viral content metrics
                $this->update_viral_content_metrics($share->content_id, $share->content_type, 'click');
                
                // Log click data
                $this->log_share_click($share_id, $additional_data);
                
                // Trigger click hooks
                do_action('env_social_viral_share_clicked', $share_id, $share, $additional_data);
            }
        }
        
        return $result;
    }
    
    /**
     * Track share conversion
     */
    public function track_share_conversion($share_id, $conversion_type = 'signup', $conversion_value = 0) {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->tables['shares']} 
                 SET conversion_count = conversion_count + 1, 
                     updated_at = %s 
                 WHERE id = %d",
                current_time('mysql'),
                $share_id
            )
        );
        
        if ($result) {
            // Get share data
            $share = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->tables['shares']} WHERE id = %d",
                    $share_id
                )
            );
            
            if ($share) {
                // Update viral content metrics
                $this->update_viral_content_metrics($share->content_id, $share->content_type, 'conversion');
                
                // Award sharing rewards
                $this->award_sharing_rewards($share, $conversion_type, $conversion_value);
                
                // Trigger conversion hooks
                do_action('env_social_viral_share_converted', $share_id, $share, $conversion_type, $conversion_value);
            }
        }
        
        return $result;
    }
    
    /**
     * Update viral content metrics
     */
    private function update_viral_content_metrics($content_id, $content_type, $action) {
        // Get or create viral content record
        $viral_content = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['viral_content']} 
                 WHERE content_id = %d AND content_type = %s",
                $content_id,
                $content_type
            )
        );
        
        if (!$viral_content) {
            // Create new viral content record
            $this->wpdb->insert(
                $this->tables['viral_content'],
                array(
                    'content_id' => $content_id,
                    'content_type' => $content_type,
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        // Update metrics based on action
        $update_data = array('last_viral_activity' => current_time('mysql'));
        
        switch ($action) {
            case 'share':
                $update_data['share_count'] = 'share_count + 1';
                break;
            case 'click':
                $update_data['click_count'] = 'click_count + 1';
                break;
            case 'conversion':
                $update_data['conversion_count'] = 'conversion_count + 1';
                break;
        }
        
        // Build update query
        $set_clauses = array();
        $values = array();
        
        foreach ($update_data as $field => $value) {
            if (strpos($value, '+') !== false) {
                $set_clauses[] = "{$field} = {$value}";
            } else {
                $set_clauses[] = "{$field} = %s";
                $values[] = $value;
            }
        }
        
        $values[] = $content_id;
        $values[] = $content_type;
        
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->tables['viral_content']} 
                 SET " . implode(', ', $set_clauses) . " 
                 WHERE content_id = %d AND content_type = %s",
                ...$values
            )
        );
    }
    
    /**
     * Update user sharing stats
     */
    private function update_user_sharing_stats($user_id, $platform) {
        if (!$user_id) return;
        
        $current_stats = get_user_meta($user_id, 'env_social_sharing_stats', true);
        if (!$current_stats) {
            $current_stats = array();
        }
        
        // Update platform-specific stats
        if (!isset($current_stats[$platform])) {
            $current_stats[$platform] = 0;
        }
        $current_stats[$platform]++;
        
        // Update total shares
        if (!isset($current_stats['total'])) {
            $current_stats['total'] = 0;
        }
        $current_stats['total']++;
        
        // Update last share date
        $current_stats['last_share'] = current_time('mysql');
        
        update_user_meta($user_id, 'env_social_sharing_stats', $current_stats);
    }
    
    /**
     * Award sharing rewards
     */
    private function award_sharing_rewards($share, $conversion_type, $conversion_value) {
        if (!$share->user_id) return;
        
        $settings = get_option('env_social_viral_settings', array());
        
        // Check if rewards are enabled
        if (empty($settings['sharing_rewards_enabled'])) {
            return;
        }
        
        // Calculate reward amount based on conversion
        $reward_amount = $this->calculate_sharing_reward($share, $conversion_type, $conversion_value);
        
        if ($reward_amount > 0) {
            // Award points or other rewards
            $this->award_user_points($share->user_id, $reward_amount, 'sharing_conversion');
        }
    }
    
    /**
     * Calculate sharing reward amount
     */
    private function calculate_sharing_reward($share, $conversion_type, $conversion_value) {
        $settings = get_option('env_social_viral_settings', array());
        $base_reward = $settings['share_conversion_reward'] ?? 5;
        
        // Platform multipliers
        $platform_multipliers = array(
            'facebook' => 1.0,
            'twitter' => 1.2,
            'linkedin' => 1.5,
            'whatsapp' => 0.8,
            'telegram' => 0.8,
            'email' => 1.3
        );
        
        $multiplier = $platform_multipliers[$share->platform] ?? 1.0;
        
        // Conversion type multipliers
        $conversion_multipliers = array(
            'signup' => 2.0,
            'purchase' => 3.0,
            'donation' => 2.5,
            'engagement' => 1.0
        );
        
        $conversion_multiplier = $conversion_multipliers[$conversion_type] ?? 1.0;
        
        return round($base_reward * $multiplier * $conversion_multiplier);
    }
    
    /**
     * Award user points (integrate with existing rewards system)
     */
    private function award_user_points($user_id, $amount, $reason) {
        // Check if voucher rewards plugin exists
        if (class_exists('Environmental_Voucher_Rewards_Engine')) {
            $reward_engine = new Environmental_Voucher_Rewards_Engine();
            $reward_engine->award_points($user_id, $amount, $reason);
        } else {
            // Fallback to user meta
            $current_points = get_user_meta($user_id, 'env_total_points', true) ?: 0;
            update_user_meta($user_id, 'env_total_points', $current_points + $amount);
        }
    }
    
    /**
     * Get sharing statistics for content
     */
    public function get_content_sharing_stats($content_id, $content_type = 'post', $period = '30days') {
        $period_sql = $this->get_period_sql_condition($period);
        
        $stats = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT platform, 
                        COUNT(*) as share_count,
                        SUM(click_count) as total_clicks,
                        SUM(conversion_count) as total_conversions
                 FROM {$this->tables['shares']} 
                 WHERE content_id = %d AND content_type = %s {$period_sql}
                 GROUP BY platform
                 ORDER BY share_count DESC",
                $content_id,
                $content_type
            )
        );
        
        return $stats;
    }
    
    /**
     * Get user sharing statistics
     */
    public function get_user_sharing_stats($user_id, $period = '30days') {
        $period_sql = $this->get_period_sql_condition($period);
        
        $stats = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT platform, 
                        COUNT(*) as share_count,
                        SUM(click_count) as total_clicks,
                        SUM(conversion_count) as total_conversions
                 FROM {$this->tables['shares']} 
                 WHERE user_id = %d {$period_sql}
                 GROUP BY platform
                 ORDER BY share_count DESC",
                $user_id
            )
        );
        
        return $stats;
    }
    
    /**
     * Get top shared content
     */
    public function get_top_shared_content($limit = 10, $period = '30days', $content_type = 'post') {
        $period_sql = $this->get_period_sql_condition($period);
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT content_id, content_type,
                        COUNT(*) as share_count,
                        SUM(click_count) as total_clicks,
                        SUM(conversion_count) as total_conversions
                 FROM {$this->tables['shares']} 
                 WHERE content_type = %s {$period_sql}
                 GROUP BY content_id, content_type
                 ORDER BY share_count DESC
                 LIMIT %d",
                $content_type,
                $limit
            )
        );
        
        return $results;
    }
    
    /**
     * Get period SQL condition
     */
    private function get_period_sql_condition($period) {
        switch ($period) {
            case '7days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1year':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "";
        }
    }
    
    /**
     * Get supported platforms
     */
    public function get_supported_platforms() {
        return $this->supported_platforms;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Log share URL generation
     */
    private function log_share_url_generation($content_id, $platform, $share_url) {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log("Environmental Social Viral: Share URL generated for content {$content_id} on {$platform}: {$share_url}");
        }
    }
    
    /**
     * Log share click
     */
    private function log_share_click($share_id, $additional_data) {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log("Environmental Social Viral: Share click tracked for share ID {$share_id}");
        }
    }
}
