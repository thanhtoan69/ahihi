<?php
/**
 * Environmental Analytics & Reporting - Security and Performance Optimization
 * Adds caching, security improvements, and performance optimizations
 */

// File: includes/class-cache-manager.php
class Environmental_Cache_Manager {
    
    private $cache_prefix = 'env_analytics_';
    private $cache_expiry = 3600; // 1 hour default
    
    /**
     * Get cached data
     */
    public function get($key, $default = null) {
        $cache_key = $this->cache_prefix . $key;
        $cached_data = wp_cache_get($cache_key, 'env_analytics');
        
        if (false === $cached_data) {
            // Try transient as fallback
            $cached_data = get_transient($cache_key);
            if (false === $cached_data) {
                return $default;
            }
        }
        
        return $cached_data;
    }
    
    /**
     * Set cached data
     */
    public function set($key, $data, $expiry = null) {
        $cache_key = $this->cache_prefix . $key;
        $expiry = $expiry ?: $this->cache_expiry;
        
        // Set in object cache
        wp_cache_set($cache_key, $data, 'env_analytics', $expiry);
        
        // Set in transient as fallback
        set_transient($cache_key, $data, $expiry);
        
        return true;
    }
    
    /**
     * Delete cached data
     */
    public function delete($key) {
        $cache_key = $this->cache_prefix . $key;
        
        wp_cache_delete($cache_key, 'env_analytics');
        delete_transient($cache_key);
        
        return true;
    }
    
    /**
     * Clear all analytics cache
     */
    public function clear_all() {
        global $wpdb;
        
        // Clear transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        // Clear timeout transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $this->cache_prefix . '%'
            )
        );
        
        return true;
    }
    
    /**
     * Get analytics dashboard data with caching
     */
    public function get_dashboard_data($force_refresh = false) {
        $cache_key = 'dashboard_data';
        
        if (!$force_refresh) {
            $cached_data = $this->get($cache_key);
            if ($cached_data !== null) {
                return $cached_data;
            }
        }
        
        global $wpdb;
        
        $data = array(
            'total_events' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_analytics_events"),
            'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_user_sessions"),
            'total_conversions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_conversion_tracking"),
            'active_goals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_conversion_goals WHERE is_active = 1"),
            'recent_events' => $wpdb->get_results(
                "SELECT event_name, event_category, COUNT(*) as count 
                 FROM {$wpdb->prefix}env_analytics_events 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 GROUP BY event_name, event_category 
                 ORDER BY count DESC 
                 LIMIT 10"
            ),
            'top_pages' => $wpdb->get_results(
                "SELECT event_data, COUNT(*) as views 
                 FROM {$wpdb->prefix}env_analytics_events 
                 WHERE event_category = 'page_view' 
                   AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                 GROUP BY event_data 
                 ORDER BY views DESC 
                 LIMIT 10"
            ),
            'generated_at' => current_time('mysql')
        );
        
        $this->set($cache_key, $data, 900); // Cache for 15 minutes
        
        return $data;
    }
}

// Security enhancements
class Environmental_Security_Manager {
    
    /**
     * Sanitize and validate analytics data
     */
    public static function sanitize_analytics_data($data) {
        if (is_array($data)) {
            return array_map(array(self::class, 'sanitize_analytics_data'), $data);
        }
        
        if (is_string($data)) {
            // Remove any potentially harmful content
            $data = sanitize_text_field($data);
            $data = wp_strip_all_tags($data);
            
            // Limit length to prevent database issues
            if (strlen($data) > 1000) {
                $data = substr($data, 0, 1000);
            }
        }
        
        return $data;
    }
    
    /**
     * Validate nonce for AJAX requests
     */
    public static function verify_ajax_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'env_analytics_nonce')) {
            wp_die('Security check failed', 'Security Error', array('response' => 403));
        }
    }
    
    /**
     * Check user permissions
     */
    public static function check_admin_permissions() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Permission Error', array('response' => 403));
        }
    }
    
    /**
     * Rate limiting for tracking requests
     */
    public static function check_rate_limit($user_id = null, $limit = 100) {
        $user_id = $user_id ?: get_current_user_id();
        $cache_key = 'rate_limit_' . ($user_id ?: session_id());
        
        $requests = wp_cache_get($cache_key, 'env_analytics') ?: 0;
        
        if ($requests >= $limit) {
            return false;
        }
        
        wp_cache_set($cache_key, $requests + 1, 'env_analytics', 3600);
        
        return true;
    }
    
    /**
     * Sanitize SQL queries
     */
    public static function prepare_query($query, $args = array()) {
        global $wpdb;
        
        if (empty($args)) {
            return $query;
        }
        
        return $wpdb->prepare($query, $args);
    }
}

// Performance optimization utilities
class Environmental_Performance_Optimizer {
    
    /**
     * Optimize database queries with proper indexing
     */
    public static function optimize_database() {
        global $wpdb;
        
        $optimizations = array(
            // Add composite indexes for common queries
            "ALTER TABLE {$wpdb->prefix}env_analytics_events 
             ADD INDEX idx_user_event_time (user_id, event_category, created_at)",
            
            "ALTER TABLE {$wpdb->prefix}env_user_sessions 
             ADD INDEX idx_user_time (user_id, last_activity)",
            
            "ALTER TABLE {$wpdb->prefix}env_conversion_tracking 
             ADD INDEX idx_goal_user_time (goal_id, user_id, created_at)",
            
            "ALTER TABLE {$wpdb->prefix}env_user_behavior 
             ADD INDEX idx_user_segment_time (user_id, segment, last_updated)"
        );
        
        foreach ($optimizations as $query) {
            $wpdb->query($query);
        }
    }
    
    /**
     * Batch process analytics data
     */
    public static function batch_process_analytics($batch_size = 1000) {
        global $wpdb;
        
        $total_processed = 0;
        $offset = 0;
        
        do {
            $events = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}env_analytics_events 
                 WHERE processed = 0 
                 ORDER BY created_at ASC 
                 LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ));
            
            if (empty($events)) {
                break;
            }
            
            foreach ($events as $event) {
                // Process event data
                self::process_single_event($event);
                
                // Mark as processed
                $wpdb->update(
                    $wpdb->prefix . 'env_analytics_events',
                    array('processed' => 1),
                    array('id' => $event->id),
                    array('%d'),
                    array('%d')
                );
            }
            
            $total_processed += count($events);
            $offset += $batch_size;
            
            // Prevent memory issues
            if ($total_processed % 5000 === 0) {
                wp_cache_flush_group('env_analytics');
            }
            
        } while (count($events) === $batch_size);
        
        return $total_processed;
    }
    
    /**
     * Process single event for analytics
     */
    private static function process_single_event($event) {
        // Add any complex processing logic here
        // This could include ML analysis, pattern recognition, etc.
        
        // For now, just update timestamps and basic processing
        return true;
    }
    
    /**
     * Archive old data
     */
    public static function archive_old_data($days_to_keep = 365) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        // Archive events
        $archived_events = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}env_analytics_events_archive 
             SELECT * FROM {$wpdb->prefix}env_analytics_events 
             WHERE created_at < %s",
            $cutoff_date
        ));
        
        // Delete archived events
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}env_analytics_events 
             WHERE created_at < %s",
            $cutoff_date
        ));
        
        // Archive old sessions
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}env_user_sessions 
             WHERE last_activity < %s",
            $cutoff_date
        ));
        
        return $archived_events;
    }
}

// Add GDPR compliance utilities
class Environmental_GDPR_Manager {
    
    /**
     * Anonymize user data
     */
    public static function anonymize_user_data($user_id) {
        global $wpdb;
        
        // Anonymize events
        $wpdb->update(
            $wpdb->prefix . 'env_analytics_events',
            array(
                'user_id' => 0,
                'ip_address' => '0.0.0.0',
                'user_agent' => 'anonymized'
            ),
            array('user_id' => $user_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        // Anonymize sessions
        $wpdb->update(
            $wpdb->prefix . 'env_user_sessions',
            array(
                'user_id' => 0,
                'ip_address' => '0.0.0.0',
                'user_agent' => 'anonymized'
            ),
            array('user_id' => $user_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        // Anonymize behavior data
        $wpdb->update(
            $wpdb->prefix . 'env_user_behavior',
            array(
                'user_id' => 0,
                'personal_data' => null
            ),
            array('user_id' => $user_id),
            array('%d', '%s'),
            array('%d')
        );
        
        return true;
    }
    
    /**
     * Export user data
     */
    public static function export_user_data($user_id) {
        global $wpdb;
        
        $data = array(
            'events' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}env_analytics_events WHERE user_id = %d",
                $user_id
            )),
            'sessions' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}env_user_sessions WHERE user_id = %d",
                $user_id
            )),
            'behavior' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}env_user_behavior WHERE user_id = %d",
                $user_id
            )),
            'conversions' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}env_conversion_tracking WHERE user_id = %d",
                $user_id
            ))
        );
        
        return $data;
    }
    
    /**
     * Check consent status
     */
    public static function has_tracking_consent($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        
        if (!$user_id) {
            // Check cookie consent for anonymous users
            return isset($_COOKIE['env_analytics_consent']) && $_COOKIE['env_analytics_consent'] === '1';
        }
        
        return get_user_meta($user_id, 'env_analytics_consent', true) === '1';
    }
}

echo "Security and Performance Optimization Classes Created Successfully!\n";
echo "Key Features Added:\n";
echo "- Cache Manager for improved performance\n";
echo "- Security Manager with input sanitization and rate limiting\n";
echo "- Performance Optimizer with batch processing and archiving\n";
echo "- GDPR Manager for data privacy compliance\n";
?>
