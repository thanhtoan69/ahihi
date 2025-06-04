<?php
/**
 * Environmental Platform Content Migration
 * 
 * Handles content migration from existing database to WordPress post types
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Content_Migration {
    
    private $db_manager;
    private $batch_size = 50;
    private $migration_log = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_manager = EP_Database_Manager::get_instance();
        add_action('wp_ajax_ep_migrate_content', array($this, 'ajax_migrate_content'));
        add_action('wp_ajax_ep_get_migration_status', array($this, 'ajax_get_migration_status'));
    }
    
    /**
     * Migrate all content from existing database
     */
    public function migrate_all_content() {
        $this->log_migration('Starting full content migration');
        
        try {
            // Migrate articles from posts table
            $this->migrate_articles();
            
            // Migrate events from events table
            $this->migrate_events();
            
            // Migrate reports (if exists in database)
            $this->migrate_reports();
            
            // Migrate community posts
            $this->migrate_community_posts();
            
            // Migrate waste classifications
            $this->migrate_waste_classifications();
            
            // Migrate products
            $this->migrate_products();
            
            // Migrate educational resources
            $this->migrate_educational_resources();
            
            $this->log_migration('Content migration completed successfully');
            return true;
            
        } catch (Exception $e) {
            $this->log_migration('Migration failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrate articles from posts table
     */
    private function migrate_articles() {
        $this->log_migration('Starting articles migration');
        
        $posts = $this->db_manager->get_posts(array(
            'limit' => 1000,
            'type' => 'article'
        ));
        
        $migrated = 0;
        foreach ($posts as $post) {
            $wp_post_id = $this->create_wordpress_post($post, 'env_article');
            if ($wp_post_id) {
                $this->map_post_meta($post, $wp_post_id);
                $this->map_post_categories($post, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} articles");
    }
    
    /**
     * Migrate events from events table
     */
    private function migrate_events() {
        $this->log_migration('Starting events migration');
        
        $events = $this->db_manager->get_events(array(
            'limit' => 1000
        ));
        
        $migrated = 0;
        foreach ($events as $event) {
            $wp_post_id = $this->create_wordpress_post($event, 'env_event');
            if ($wp_post_id) {
                $this->map_event_meta($event, $wp_post_id);
                $this->map_event_categories($event, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} events");
    }
    
    /**
     * Migrate reports
     */
    private function migrate_reports() {
        $this->log_migration('Starting reports migration');
        
        // Check if reports table exists
        if (!$this->db_manager->table_exists('reports')) {
            $this->log_migration('Reports table not found, skipping');
            return;
        }
        
        $reports = $this->db_manager->query("SELECT * FROM reports LIMIT 1000");
        
        $migrated = 0;
        foreach ($reports as $report) {
            $wp_post_id = $this->create_wordpress_post($report, 'env_report');
            if ($wp_post_id) {
                $this->map_report_meta($report, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} reports");
    }
    
    /**
     * Migrate community posts
     */
    private function migrate_community_posts() {
        $this->log_migration('Starting community posts migration');
        
        // Check if user_posts table exists
        if (!$this->db_manager->table_exists('user_posts')) {
            $this->log_migration('User posts table not found, skipping');
            return;
        }
        
        $posts = $this->db_manager->query("SELECT * FROM user_posts LIMIT 1000");
        
        $migrated = 0;
        foreach ($posts as $post) {
            $wp_post_id = $this->create_wordpress_post($post, 'community_post');
            if ($wp_post_id) {
                $this->map_community_post_meta($post, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} community posts");
    }
    
    /**
     * Migrate waste classifications
     */
    private function migrate_waste_classifications() {
        $this->log_migration('Starting waste classifications migration');
        
        if (!$this->db_manager->table_exists('waste_categories')) {
            $this->log_migration('Waste categories table not found, skipping');
            return;
        }
        
        $waste_items = $this->db_manager->query("SELECT * FROM waste_categories LIMIT 1000");
        
        $migrated = 0;
        foreach ($waste_items as $waste) {
            $wp_post_id = $this->create_wordpress_post($waste, 'waste_class');
            if ($wp_post_id) {
                $this->map_waste_meta($waste, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} waste classifications");
    }
    
    /**
     * Migrate products
     */
    private function migrate_products() {
        $this->log_migration('Starting products migration');
        
        if (!$this->db_manager->table_exists('products')) {
            $this->log_migration('Products table not found, skipping');
            return;
        }
        
        $products = $this->db_manager->query("SELECT * FROM products LIMIT 1000");
        
        $migrated = 0;
        foreach ($products as $product) {
            $wp_post_id = $this->create_wordpress_post($product, 'eco_product');
            if ($wp_post_id) {
                $this->map_product_meta($product, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} products");
    }
    
    /**
     * Migrate educational resources
     */
    private function migrate_educational_resources() {
        $this->log_migration('Starting educational resources migration');
        
        if (!$this->db_manager->table_exists('educational_resources')) {
            $this->log_migration('Educational resources table not found, skipping');
            return;
        }
        
        $resources = $this->db_manager->query("SELECT * FROM educational_resources LIMIT 1000");
        
        $migrated = 0;
        foreach ($resources as $resource) {
            $wp_post_id = $this->create_wordpress_post($resource, 'edu_resource');
            if ($wp_post_id) {
                $this->map_resource_meta($resource, $wp_post_id);
                $migrated++;
            }
        }
        
        $this->log_migration("Migrated {$migrated} educational resources");
    }
    
    /**
     * Create WordPress post from existing data
     */
    private function create_wordpress_post($data, $post_type) {
        $post_data = array(
            'post_title'    => isset($data['title']) ? $data['title'] : (isset($data['name']) ? $data['name'] : 'Untitled'),
            'post_content'  => isset($data['content']) ? $data['content'] : (isset($data['description']) ? $data['description'] : ''),
            'post_excerpt'  => isset($data['excerpt']) ? $data['excerpt'] : '',
            'post_status'   => isset($data['status']) ? $this->map_status($data['status']) : 'publish',
            'post_type'     => $post_type,
            'post_author'   => isset($data['user_id']) ? $this->get_wp_user_id($data['user_id']) : 1,
            'post_date'     => isset($data['created_at']) ? $data['created_at'] : current_time('mysql'),
            'meta_input'    => array(
                '_original_id' => isset($data['id']) ? $data['id'] : 0,
                '_migrated_from_db' => true,
                '_migration_date' => current_time('mysql')
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            $this->log_migration('Failed to create post: ' . $post_id->get_error_message());
            return false;
        }
        
        return $post_id;
    }
    
    /**
     * Map post meta data
     */
    private function map_post_meta($data, $wp_post_id) {
        // Environmental score
        if (isset($data['environmental_score'])) {
            update_post_meta($wp_post_id, '_environmental_score', $data['environmental_score']);
        }
        
        // Carbon impact
        if (isset($data['carbon_impact'])) {
            update_post_meta($wp_post_id, '_carbon_impact', $data['carbon_impact']);
        }
        
        // Sustainability rating
        if (isset($data['sustainability_rating'])) {
            update_post_meta($wp_post_id, '_sustainability_rating', $data['sustainability_rating']);
        }
        
        // View count
        if (isset($data['views'])) {
            update_post_meta($wp_post_id, '_view_count', $data['views']);
        }
    }
    
    /**
     * Map event specific meta data
     */
    private function map_event_meta($event, $wp_post_id) {
        $this->map_post_meta($event, $wp_post_id);
        
        if (isset($event['event_date'])) {
            update_post_meta($wp_post_id, '_event_date', $event['event_date']);
        }
        
        if (isset($event['event_time'])) {
            update_post_meta($wp_post_id, '_event_time', $event['event_time']);
        }
        
        if (isset($event['location'])) {
            update_post_meta($wp_post_id, '_event_location', $event['location']);
        }
        
        if (isset($event['max_participants'])) {
            update_post_meta($wp_post_id, '_max_participants', $event['max_participants']);
        }
        
        if (isset($event['registration_required'])) {
            update_post_meta($wp_post_id, '_registration_required', $event['registration_required']);
        }
    }
    
    /**
     * Map report specific meta data
     */
    private function map_report_meta($report, $wp_post_id) {
        $this->map_post_meta($report, $wp_post_id);
        
        if (isset($report['report_type'])) {
            update_post_meta($wp_post_id, '_report_type', $report['report_type']);
        }
        
        if (isset($report['research_period'])) {
            update_post_meta($wp_post_id, '_research_period', $report['research_period']);
        }
        
        if (isset($report['methodology'])) {
            update_post_meta($wp_post_id, '_methodology', $report['methodology']);
        }
        
        if (isset($report['file_url'])) {
            update_post_meta($wp_post_id, '_report_file_url', $report['file_url']);
        }
    }
    
    /**
     * Map community post meta data
     */
    private function map_community_post_meta($post, $wp_post_id) {
        $this->map_post_meta($post, $wp_post_id);
        
        if (isset($post['likes_count'])) {
            update_post_meta($wp_post_id, '_likes_count', $post['likes_count']);
        }
        
        if (isset($post['shares_count'])) {
            update_post_meta($wp_post_id, '_shares_count', $post['shares_count']);
        }
        
        if (isset($post['is_featured'])) {
            update_post_meta($wp_post_id, '_is_featured', $post['is_featured']);
        }
    }
    
    /**
     * Map waste classification meta data
     */
    private function map_waste_meta($waste, $wp_post_id) {
        if (isset($waste['recyclable'])) {
            update_post_meta($wp_post_id, '_recyclable', $waste['recyclable']);
        }
        
        if (isset($waste['disposal_method'])) {
            update_post_meta($wp_post_id, '_disposal_method', $waste['disposal_method']);
        }
        
        if (isset($waste['hazard_level'])) {
            update_post_meta($wp_post_id, '_hazard_level', $waste['hazard_level']);
        }
        
        if (isset($waste['decomposition_time'])) {
            update_post_meta($wp_post_id, '_decomposition_time', $waste['decomposition_time']);
        }
    }
    
    /**
     * Map product meta data
     */
    private function map_product_meta($product, $wp_post_id) {
        $this->map_post_meta($product, $wp_post_id);
        
        if (isset($product['price'])) {
            update_post_meta($wp_post_id, '_price', $product['price']);
        }
        
        if (isset($product['eco_certification'])) {
            update_post_meta($wp_post_id, '_eco_certification', $product['eco_certification']);
        }
        
        if (isset($product['materials'])) {
            update_post_meta($wp_post_id, '_materials', $product['materials']);
        }
        
        if (isset($product['manufacturer'])) {
            update_post_meta($wp_post_id, '_manufacturer', $product['manufacturer']);
        }
    }
    
    /**
     * Map resource meta data
     */
    private function map_resource_meta($resource, $wp_post_id) {
        $this->map_post_meta($resource, $wp_post_id);
        
        if (isset($resource['difficulty_level'])) {
            update_post_meta($wp_post_id, '_difficulty_level', $resource['difficulty_level']);
        }
        
        if (isset($resource['duration'])) {
            update_post_meta($wp_post_id, '_duration', $resource['duration']);
        }
        
        if (isset($resource['resource_type'])) {
            update_post_meta($wp_post_id, '_resource_type', $resource['resource_type']);
        }
        
        if (isset($resource['file_url'])) {
            update_post_meta($wp_post_id, '_resource_file_url', $resource['file_url']);
        }
    }
    
    /**
     * Map post categories
     */
    private function map_post_categories($post, $wp_post_id) {
        if (isset($post['category_id'])) {
            // Get or create corresponding WordPress category
            $category = $this->get_or_create_category($post['category_id']);
            if ($category) {
                wp_set_post_terms($wp_post_id, array($category), 'env_category');
            }
        }
    }
    
    /**
     * Map event categories
     */
    private function map_event_categories($event, $wp_post_id) {
        if (isset($event['event_type'])) {
            $term = term_exists($event['event_type'], 'event_type');
            if (!$term) {
                $term = wp_insert_term($event['event_type'], 'event_type');
            }
            if (!is_wp_error($term)) {
                wp_set_post_terms($wp_post_id, array($term['term_id']), 'event_type');
            }
        }
    }
    
    /**
     * Get or create category
     */
    private function get_or_create_category($category_id) {
        // Get category from original database
        $category = $this->db_manager->query(
            "SELECT * FROM categories WHERE id = %d",
            $category_id
        );
        
        if (empty($category)) {
            return false;
        }
        
        $category = $category[0];
        
        // Check if already migrated
        $existing = get_terms(array(
            'taxonomy' => 'env_category',
            'meta_key' => '_original_id',
            'meta_value' => $category_id,
            'hide_empty' => false
        ));
        
        if (!empty($existing)) {
            return $existing[0]->term_id;
        }
        
        // Create new term
        $term = wp_insert_term(
            $category['name'],
            'env_category',
            array(
                'description' => isset($category['description']) ? $category['description'] : '',
                'slug' => sanitize_title($category['name'])
            )
        );
        
        if (!is_wp_error($term)) {
            update_term_meta($term['term_id'], '_original_id', $category_id);
            return $term['term_id'];
        }
        
        return false;
    }
    
    /**
     * Map status from original database to WordPress
     */
    private function map_status($status) {
        $status_map = array(
            'active' => 'publish',
            'inactive' => 'draft',
            'pending' => 'pending',
            'archived' => 'draft'
        );
        
        return isset($status_map[$status]) ? $status_map[$status] : 'publish';
    }
    
    /**
     * Get WordPress user ID from original user ID
     */
    private function get_wp_user_id($original_user_id) {
        $user = get_users(array(
            'meta_key' => '_original_user_id',
            'meta_value' => $original_user_id,
            'number' => 1
        ));
        
        return !empty($user) ? $user[0]->ID : 1;
    }
    
    /**
     * AJAX handler for content migration
     */
    public function ajax_migrate_content() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'ep_migration_nonce')) {
            wp_die('Unauthorized');
        }
        
        $type = sanitize_text_field($_POST['type']);
        
        switch ($type) {
            case 'articles':
                $result = $this->migrate_articles();
                break;
            case 'events':
                $result = $this->migrate_events();
                break;
            case 'all':
                $result = $this->migrate_all_content();
                break;
            default:
                wp_send_json_error('Invalid migration type');
        }
        
        wp_send_json_success(array(
            'message' => 'Migration completed',
            'log' => $this->migration_log
        ));
    }
    
    /**
     * AJAX handler for migration status
     */
    public function ajax_get_migration_status() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $status = array(
            'articles' => $this->get_migration_count('env_article'),
            'events' => $this->get_migration_count('env_event'),
            'reports' => $this->get_migration_count('env_report'),
            'community_posts' => $this->get_migration_count('community_post'),
            'products' => $this->get_migration_count('eco_product'),
            'resources' => $this->get_migration_count('edu_resource'),
            'waste_classifications' => $this->get_migration_count('waste_class')
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * Get migration count for post type
     */
    private function get_migration_count($post_type) {
        $count = wp_count_posts($post_type);
        return $count->publish + $count->draft + $count->pending;
    }
    
    /**
     * Log migration activity
     */
    private function log_migration($message) {
        $this->migration_log[] = array(
            'time' => current_time('mysql'),
            'message' => $message
        );
        
        // Also log to WordPress error log if debug is enabled
        if (WP_DEBUG_LOG) {
            error_log('EP Migration: ' . $message);
        }
    }
    
    /**
     * Get migration log
     */
    public function get_migration_log() {
        return $this->migration_log;
    }
    
    /**
     * Clear migration log
     */
    public function clear_migration_log() {
        $this->migration_log = array();
    }
}
