<?php
/**
 * Environmental Item Exchange - Database Manager
 * 
 * Handles all database operations for the enhanced exchange system
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EIE_Database_Manager {
    
    private static $instance = null;
    private $wpdb;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp', array($this, 'sync_with_environmental_db'));
        add_action('save_post_item_exchange', array($this, 'sync_exchange_post'), 10, 2);
        add_action('eie_cleanup_expired_exchanges', array($this, 'cleanup_expired_exchanges'));
    }
    
    /**
     * Sync with environmental platform database
     */
    public function sync_with_environmental_db() {
        // Check if environmental_platform database exists
        $ep_db_exists = $this->wpdb->get_var("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'environmental_platform'");
        
        if ($ep_db_exists) {
            // Sync exchange data with environmental platform
            $this->sync_exchange_data();
        }
    }
    
    /**
     * Sync exchange data between WordPress and Environmental Platform
     */
    private function sync_exchange_data() {
        // Get WordPress exchanges
        $wp_exchanges = $this->get_wp_exchanges();
        
        foreach ($wp_exchanges as $exchange) {
            $this->sync_single_exchange($exchange);
        }
    }
    
    /**
     * Get WordPress exchanges
     */
    private function get_wp_exchanges() {
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_last_sync',
                    'value' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'compare' => '<',
                    'type' => 'DATETIME'
                )
            )
        );
        
        return get_posts($args);
    }
    
    /**
     * Sync single exchange with environmental platform
     */
    private function sync_single_exchange($wp_post) {
        // Prepare data for environmental platform
        $exchange_data = $this->prepare_exchange_data($wp_post);
        
        // Check if exchange exists in environmental platform
        $ep_exchange_id = $this->get_ep_exchange_id($wp_post->ID);
        
        if ($ep_exchange_id) {
            // Update existing exchange
            $this->update_ep_exchange($ep_exchange_id, $exchange_data);
        } else {
            // Create new exchange
            $ep_exchange_id = $this->create_ep_exchange($exchange_data);
            
            if ($ep_exchange_id) {
                // Store mapping
                update_post_meta($wp_post->ID, '_ep_exchange_id', $ep_exchange_id);
            }
        }
        
        // Update sync timestamp
        update_post_meta($wp_post->ID, '_last_sync', current_time('mysql'));
    }
    
    /**
     * Prepare exchange data for environmental platform
     */
    private function prepare_exchange_data($wp_post) {
        $meta = get_post_meta($wp_post->ID);
        
        $data = array(
            'title' => $wp_post->post_title,
            'description' => $wp_post->post_content,
            'user_id' => $wp_post->post_author,
            'category_id' => $this->get_ep_category_id($meta['_exchange_category'][0] ?? ''),
            'exchange_type' => $meta['_exchange_type'][0] ?? 'exchange',
            'item_condition' => $meta['_item_condition'][0] ?? 'good',
            'estimated_value' => floatval($meta['_estimated_value'][0] ?? 0),
            'location_data' => $this->prepare_location_data($meta),
            'images' => $this->prepare_images_data($wp_post->ID),
            'post_status' => $this->map_post_status($wp_post->post_status),
            'created_at' => $wp_post->post_date,
            'updated_at' => $wp_post->post_modified,
        );
        
        return $data;
    }
    
    /**
     * Get environmental platform category ID
     */
    private function get_ep_category_id($wp_category_slug) {
        // Map WordPress category to environmental platform category
        $category_map = array(
            'electronics' => 1,
            'clothing' => 2,
            'furniture' => 3,
            'books' => 4,
            'tools' => 5,
            'appliances' => 6,
            'sports' => 7,
            'toys' => 8,
            'other' => 9
        );
        
        return $category_map[$wp_category_slug] ?? 9;
    }
    
    /**
     * Prepare location data
     */
    private function prepare_location_data($meta) {
        $location = array();
        
        if (isset($meta['_exchange_latitude'][0]) && isset($meta['_exchange_longitude'][0])) {
            $location['lat'] = floatval($meta['_exchange_latitude'][0]);
            $location['lng'] = floatval($meta['_exchange_longitude'][0]);
        }
        
        if (isset($meta['_exchange_address'][0])) {
            $location['address'] = $meta['_exchange_address'][0];
        }
        
        if (isset($meta['_exchange_city'][0])) {
            $location['city'] = $meta['_exchange_city'][0];
        }
        
        return json_encode($location);
    }
    
    /**
     * Prepare images data
     */
    private function prepare_images_data($post_id) {
        $images = array();
        
        // Get featured image
        if (has_post_thumbnail($post_id)) {
            $images[] = wp_get_attachment_url(get_post_thumbnail_id($post_id));
        }
        
        // Get gallery images
        $gallery = get_post_meta($post_id, '_item_images', true);
        if (is_array($gallery)) {
            foreach ($gallery as $image_id) {
                $images[] = wp_get_attachment_url($image_id);
            }
        }
        
        return json_encode($images);
    }
    
    /**
     * Map WordPress post status to environmental platform status
     */
    private function map_post_status($wp_status) {
        $status_map = array(
            'publish' => 'active',
            'draft' => 'draft',
            'trash' => 'deleted',
            'private' => 'private'
        );
        
        return $status_map[$wp_status] ?? 'draft';
    }
    
    /**
     * Get environmental platform exchange ID
     */
    private function get_ep_exchange_id($wp_post_id) {
        return get_post_meta($wp_post_id, '_ep_exchange_id', true);
    }
    
    /**
     * Update exchange in environmental platform
     */
    private function update_ep_exchange($ep_exchange_id, $data) {
        $sql = "UPDATE environmental_platform.exchange_posts SET
                title = %s,
                description = %s,
                exchange_type = %s,
                item_condition = %s,
                estimated_value = %f,
                location_data = %s,
                images = %s,
                post_status = %s,
                updated_at = NOW()
                WHERE post_id = %d";
        
        return $this->wpdb->query($this->wpdb->prepare($sql,
            $data['title'],
            $data['description'],
            $data['exchange_type'],
            $data['item_condition'],
            $data['estimated_value'],
            $data['location_data'],
            $data['images'],
            $data['post_status'],
            $ep_exchange_id
        ));
    }
    
    /**
     * Create exchange in environmental platform
     */
    private function create_ep_exchange($data) {
        $sql = "INSERT INTO environmental_platform.exchange_posts
                (user_id, category_id, title, description, exchange_type, item_condition, 
                 estimated_value, location_data, images, post_status, created_at)
                VALUES (%d, %d, %s, %s, %s, %s, %f, %s, %s, %s, %s)";
        
        $result = $this->wpdb->query($this->wpdb->prepare($sql,
            $data['user_id'],
            $data['category_id'],
            $data['title'],
            $data['description'],
            $data['exchange_type'],
            $data['item_condition'],
            $data['estimated_value'],
            $data['location_data'],
            $data['images'],
            $data['post_status'],
            $data['created_at']
        ));
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Save exchange to user favorites
     */
    public static function save_exchange($user_id, $exchange_id) {
        global $wpdb;
        
        // Check if already saved
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}eie_favorites WHERE user_id = %d AND exchange_id = %d",
            $user_id, $exchange_id
        ));
        
        if ($existing) {
            return true; // Already saved
        }
        
        // Add to favorites
        $result = $wpdb->insert(
            $wpdb->prefix . 'eie_favorites',
            array(
                'user_id' => $user_id,
                'exchange_id' => $exchange_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get user's saved exchanges
     */
    public function get_user_saved_exchanges($user_id, $limit = 10) {
        $sql = "SELECT p.* FROM {$this->wpdb->posts} p
                JOIN {$this->wpdb->prefix}eie_favorites f ON p.ID = f.exchange_id
                WHERE f.user_id = %d AND p.post_type = 'item_exchange' AND p.post_status = 'publish'
                ORDER BY f.created_at DESC
                LIMIT %d";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $user_id, $limit));
    }
    
    /**
     * Get exchange statistics
     */
    public function get_exchange_stats($period = 'all_time') {
        $date_filter = '';
        
        switch ($period) {
            case 'today':
                $date_filter = "AND p.post_date >= '" . date('Y-m-d 00:00:00') . "'";
                break;
            case 'week':
                $date_filter = "AND p.post_date >= '" . date('Y-m-d 00:00:00', strtotime('-7 days')) . "'";
                break;
            case 'month':
                $date_filter = "AND p.post_date >= '" . date('Y-m-d 00:00:00', strtotime('-30 days')) . "'";
                break;
        }
        
        $sql = "SELECT 
                COUNT(*) as total_exchanges,
                COUNT(CASE WHEN pm.meta_value = 'give_away' THEN 1 END) as give_away_count,
                COUNT(CASE WHEN pm.meta_value = 'exchange' THEN 1 END) as exchange_count,
                COUNT(CASE WHEN pm.meta_value = 'lending' THEN 1 END) as lending_count,
                COUNT(CASE WHEN pm.meta_value = 'selling' THEN 1 END) as selling_count
                FROM {$this->wpdb->posts} p
                LEFT JOIN {$this->wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_exchange_type'
                WHERE p.post_type = 'item_exchange' AND p.post_status = 'publish'
                {$date_filter}";
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * Get popular categories
     */
    public function get_popular_categories($limit = 10) {
        $sql = "SELECT tt.term_id, t.name, t.slug, COUNT(*) as count
                FROM {$this->wpdb->term_relationships} tr
                JOIN {$this->wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN {$this->wpdb->terms} t ON tt.term_id = t.term_id
                JOIN {$this->wpdb->posts} p ON tr.object_id = p.ID
                WHERE tt.taxonomy = 'exchange_type' 
                AND p.post_type = 'item_exchange' 
                AND p.post_status = 'publish'
                GROUP BY tt.term_id, t.name, t.slug
                ORDER BY count DESC
                LIMIT %d";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $limit));
    }
    
    /**
     * Log user activity
     */
    public function log_activity($user_id, $exchange_id, $action_type, $action_data = null) {
        return $this->wpdb->insert(
            $wpdb->prefix . 'eie_analytics',
            array(
                'user_id' => $user_id,
                'exchange_id' => $exchange_id,
                'action_type' => $action_type,
                'action_data' => is_array($action_data) ? json_encode($action_data) : $action_data,
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
    
    /**
     * Cleanup expired exchanges
     */
    public function cleanup_expired_exchanges() {
        $expire_days = get_option('eie_auto_expire_days', 30);
        $expire_date = date('Y-m-d H:i:s', strtotime("-{$expire_days} days"));
        
        // Get expired exchanges
        $expired_exchanges = get_posts(array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'before' => $expire_date,
                    'inclusive' => true,
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => '_exchange_status',
                    'value' => 'active',
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1
        ));
        
        // Mark as expired
        foreach ($expired_exchanges as $exchange) {
            update_post_meta($exchange->ID, '_exchange_status', 'expired');
            
            // Send notification to owner
            $this->send_expiry_notification($exchange);
        }
        
        return count($expired_exchanges);
    }
    
    /**
     * Send expiry notification
     */
    private function send_expiry_notification($exchange) {
        if (!get_option('eie_email_notifications', true)) {
            return;
        }
        
        $author = get_user_by('id', $exchange->post_author);
        if (!$author) {
            return;
        }
        
        $subject = sprintf(__('Your exchange "%s" has expired', 'environmental-item-exchange'), $exchange->post_title);
        
        $message = sprintf(
            __('Hello %s,

Your exchange "%s" has expired and is no longer visible to other users.

You can renew it by editing the exchange and updating the expiry date.

View Exchange: %s

Best regards,
Environmental Platform Team', 'environmental-item-exchange'),
            $author->display_name,
            $exchange->post_title,
            get_permalink($exchange->ID)
        );
        
        wp_mail($author->user_email, $subject, $message);
    }
    
    /**
     * Get exchange view count
     */
    public function get_exchange_views($exchange_id) {
        return intval(get_post_meta($exchange_id, '_view_count', true));
    }
    
    /**
     * Increment exchange view count
     */
    public function increment_exchange_views($exchange_id) {
        $current_views = $this->get_exchange_views($exchange_id);
        update_post_meta($exchange_id, '_view_count', $current_views + 1);
        
        // Log the view
        $this->log_activity(get_current_user_id(), $exchange_id, 'view', array(
            'timestamp' => current_time('mysql'),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
        ));
    }
    
    /**
     * Search exchanges with advanced filters
     */
    public function search_exchanges($args = array()) {
        $defaults = array(
            'keyword' => '',
            'category' => '',
            'exchange_type' => '',
            'condition' => '',
            'location' => '',
            'radius' => 10,
            'min_value' => 0,
            'max_value' => 999999,
            'sort_by' => 'date',
            'order' => 'DESC',
            'posts_per_page' => 12,
            'paged' => 1
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => $args['posts_per_page'],
            'paged' => $args['paged'],
            'meta_query' => array('relation' => 'AND'),
            'tax_query' => array('relation' => 'AND')
        );
        
        // Keyword search
        if (!empty($args['keyword'])) {
            $query_args['s'] = $args['keyword'];
        }
        
        // Category filter
        if (!empty($args['category'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'exchange_type',
                'field' => 'slug',
                'terms' => $args['category']
            );
        }
        
        // Exchange type filter
        if (!empty($args['exchange_type'])) {
            $query_args['meta_query'][] = array(
                'key' => '_exchange_type',
                'value' => $args['exchange_type'],
                'compare' => '='
            );
        }
        
        // Condition filter
        if (!empty($args['condition'])) {
            $query_args['meta_query'][] = array(
                'key' => '_item_condition',
                'value' => $args['condition'],
                'compare' => '='
            );
        }
        
        // Price range filter
        if ($args['min_value'] > 0 || $args['max_value'] < 999999) {
            $query_args['meta_query'][] = array(
                'key' => '_estimated_value',
                'value' => array($args['min_value'], $args['max_value']),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            );
        }
        
        // Sorting
        switch ($args['sort_by']) {
            case 'price':
                $query_args['meta_key'] = '_estimated_value';
                $query_args['orderby'] = 'meta_value_num';
                break;
            case 'views':
                $query_args['meta_key'] = '_view_count';
                $query_args['orderby'] = 'meta_value_num';
                break;
            case 'title':
                $query_args['orderby'] = 'title';
                break;
            default:
                $query_args['orderby'] = 'date';
        }
        
        $query_args['order'] = $args['order'];
        
        return new WP_Query($query_args);
    }
    
    /**
     * Get related exchanges
     */
    public function get_related_exchanges($exchange_id, $limit = 5) {
        $exchange = get_post($exchange_id);
        if (!$exchange) {
            return array();
        }
        
        $categories = wp_get_post_terms($exchange_id, 'exchange_type', array('fields' => 'slugs'));
        $exchange_type = get_post_meta($exchange_id, '_exchange_type', true);
        
        $args = array(
            'post_type' => 'item_exchange',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => array($exchange_id),
            'meta_query' => array(
                array(
                    'key' => '_exchange_type',
                    'value' => $exchange_type,
                    'compare' => '='
                )
            )
        );
        
        if (!empty($categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'exchange_type',
                    'field' => 'slug',
                    'terms' => $categories
                )
            );
        }
        
        return get_posts($args);
    }
}

?>
