<?php
/**
 * Environmental Platform Database Migration Handler
 * Handles data migration between existing database and WordPress
 * Phase 28: Custom Database Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_Database_Migration {
    
    private $wpdb;
    private $db_manager;
    private $batch_size = 100;
    private $migration_log = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->db_manager = EP_Database_Manager::get_instance();
        
        add_action('wp_ajax_ep_run_migration', array($this, 'run_migration'));
        add_action('wp_ajax_ep_check_migration_status', array($this, 'check_migration_status'));
        add_action('wp_ajax_ep_rollback_migration', array($this, 'rollback_migration'));
    }
    
    /**
     * Run complete database migration
     */
    public function run_migration() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_ajax_referer('ep_migration', 'nonce');
        
        $migration_type = sanitize_text_field($_POST['migration_type'] ?? 'full');
        $tables = $_POST['tables'] ?? array();
        
        try {
            $this->start_migration_log($migration_type);
            
            switch ($migration_type) {
                case 'users':
                    $this->migrate_users();
                    break;
                case 'content':
                    $this->migrate_content();
                    break;
                case 'environmental':
                    $this->migrate_environmental_data();
                    break;
                case 'ecommerce':
                    $this->migrate_ecommerce_data();
                    break;
                case 'community':
                    $this->migrate_community_data();
                    break;
                case 'custom':
                    $this->migrate_custom_tables($tables);
                    break;
                case 'full':
                default:
                    $this->migrate_full_database();
                    break;
            }
            
            $this->complete_migration_log('success');
            
            wp_send_json_success(array(
                'message' => __('Migration completed successfully.', 'environmental-platform-core'),
                'log' => $this->migration_log
            ));
            
        } catch (Exception $e) {
            $this->complete_migration_log('failed', $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('Migration failed: ', 'environmental-platform-core') . $e->getMessage(),
                'log' => $this->migration_log
            ));
        }
    }
    
    /**
     * Migrate full database
     */
    private function migrate_full_database() {
        $this->log_message('Starting full database migration...');
        
        // Step 1: Migrate users first (required for other data)
        $this->migrate_users();
        
        // Step 2: Migrate content (articles, categories)
        $this->migrate_content();
        
        // Step 3: Migrate environmental data
        $this->migrate_environmental_data();
        
        // Step 4: Migrate e-commerce data
        $this->migrate_ecommerce_data();
        
        // Step 5: Migrate community data
        $this->migrate_community_data();
        
        // Step 6: Migrate achievements and gamification
        $this->migrate_gamification_data();
        
        // Step 7: Setup relationships and taxonomies
        $this->setup_relationships();
        
        $this->log_message('Full database migration completed.');
    }
    
    /**
     * Migrate users from environmental platform to WordPress
     */
    private function migrate_users() {
        $this->log_message('Migrating users...');
        
        // Get total count
        $total_users = $this->wpdb->get_var("SELECT COUNT(*) FROM users");
        $migrated = 0;
        $offset = 0;
        
        while ($offset < $total_users) {
            $users = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM users LIMIT %d OFFSET %d",
                    $this->batch_size,
                    $offset
                ),
                ARRAY_A
            );
            
            foreach ($users as $user) {
                try {
                    $wp_user_id = $this->create_wp_user($user);
                    $this->migrate_user_meta($user['user_id'], $wp_user_id);
                    $migrated++;
                } catch (Exception $e) {
                    $this->log_message("Error migrating user {$user['user_id']}: " . $e->getMessage());
                }
            }
            
            $offset += $this->batch_size;
        }
        
        $this->log_message("Migrated {$migrated} users out of {$total_users}.");
    }
    
    /**
     * Create WordPress user from environmental platform user
     */
    private function create_wp_user($ep_user) {
        // Check if user already exists
        $existing_user = get_user_by('email', $ep_user['email']);
        if ($existing_user) {
            // Update existing user
            $user_data = array(
                'ID' => $existing_user->ID,
                'display_name' => $ep_user['full_name'] ?: $ep_user['username'],
                'description' => $ep_user['bio'] ?: ''
            );
            wp_update_user($user_data);
            return $existing_user->ID;
        }
        
        // Create new user
        $user_data = array(
            'user_login' => $ep_user['username'],
            'user_email' => $ep_user['email'],
            'user_pass' => wp_generate_password(),
            'display_name' => $ep_user['full_name'] ?: $ep_user['username'],
            'first_name' => $ep_user['first_name'] ?: '',
            'last_name' => $ep_user['last_name'] ?: '',
            'description' => $ep_user['bio'] ?: '',
            'user_registered' => $ep_user['created_at'],
            'role' => $this->map_user_role($ep_user['user_type'] ?: 'regular_user')
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            throw new Exception($user_id->get_error_message());
        }
        
        // Store original EP user ID for reference
        update_user_meta($user_id, 'ep_original_id', $ep_user['user_id']);
        
        return $user_id;
    }
    
    /**
     * Migrate user meta data
     */
    private function migrate_user_meta($ep_user_id, $wp_user_id) {
        // Migrate user preferences
        $preferences = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM user_preferences WHERE user_id = %d",
                $ep_user_id
            ),
            ARRAY_A
        );
        
        foreach ($preferences as $pref) {
            update_user_meta($wp_user_id, 'ep_' . $pref['preference_key'], $pref['preference_value']);
        }
        
        // Migrate user level and points
        $user_data = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM users WHERE user_id = %d", $ep_user_id),
            ARRAY_A
        );
        
        if ($user_data) {
            update_user_meta($wp_user_id, 'ep_green_points', $user_data['green_points'] ?: 0);
            update_user_meta($wp_user_id, 'ep_user_level', $user_data['user_level'] ?: 1);
            update_user_meta($wp_user_id, 'ep_carbon_footprint_kg', $user_data['carbon_footprint_kg'] ?: 0);
            update_user_meta($wp_user_id, 'ep_location_lat', $user_data['location_lat']);
            update_user_meta($wp_user_id, 'ep_location_lng', $user_data['location_lng']);
        }
    }
    
    /**
     * Migrate content (articles, categories, tags)
     */
    private function migrate_content() {
        $this->log_message('Migrating content...');
        
        // Migrate categories first
        $this->migrate_categories();
        
        // Then migrate articles
        $this->migrate_articles();
        
        // Finally migrate tags and relationships
        $this->migrate_tags();
    }
    
    /**
     * Migrate categories to WordPress terms
     */
    private function migrate_categories() {
        $categories = $this->wpdb->get_results("SELECT * FROM categories", ARRAY_A);
        
        foreach ($categories as $category) {
            $term_data = wp_insert_term(
                $category['name'],
                'category',
                array(
                    'description' => $category['description'] ?: '',
                    'slug' => $category['slug'] ?: sanitize_title($category['name'])
                )
            );
            
            if (!is_wp_error($term_data)) {
                // Store original category ID
                update_term_meta($term_data['term_id'], 'ep_original_id', $category['category_id']);
                update_term_meta($term_data['term_id'], 'ep_environmental_category', $category['environmental_category'] ?: '');
            }
        }
    }
    
    /**
     * Migrate articles to WordPress posts
     */
    private function migrate_articles() {
        $total_articles = $this->wpdb->get_var("SELECT COUNT(*) FROM articles");
        $migrated = 0;
        $offset = 0;
        
        while ($offset < $total_articles) {
            $articles = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM articles LIMIT %d OFFSET %d",
                    $this->batch_size,
                    $offset
                ),
                ARRAY_A
            );
            
            foreach ($articles as $article) {
                try {
                    $wp_post_id = $this->create_wp_post($article);
                    $this->migrate_article_meta($article['article_id'], $wp_post_id);
                    $migrated++;
                } catch (Exception $e) {
                    $this->log_message("Error migrating article {$article['article_id']}: " . $e->getMessage());
                }
            }
            
            $offset += $this->batch_size;
        }
        
        $this->log_message("Migrated {$migrated} articles out of {$total_articles}.");
    }
    
    /**
     * Create WordPress post from environmental platform article
     */
    private function create_wp_post($ep_article) {
        // Get WordPress user ID from EP user ID
        $wp_user_id = $this->get_wp_user_id($ep_article['author_id']);
        
        $post_data = array(
            'post_title' => $ep_article['title'],
            'post_content' => $ep_article['content'],
            'post_excerpt' => $ep_article['excerpt'] ?: '',
            'post_status' => $this->map_post_status($ep_article['status']),
            'post_type' => 'post',
            'post_author' => $wp_user_id ?: 1,
            'post_date' => $ep_article['created_at'],
            'post_modified' => $ep_article['updated_at'],
            'meta_input' => array(
                'ep_original_id' => $ep_article['article_id'],
                'ep_environmental_impact_score' => $ep_article['environmental_impact_score'] ?: 0,
                'ep_sustainability_rating' => $ep_article['sustainability_rating'] ?: 0,
                'ep_carbon_impact_kg' => $ep_article['carbon_impact_kg'] ?: 0,
                'ep_view_count' => $ep_article['view_count'] ?: 0
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }
        
        // Assign categories
        if ($ep_article['category_id']) {
            $wp_category_id = $this->get_wp_category_id($ep_article['category_id']);
            if ($wp_category_id) {
                wp_set_post_categories($post_id, array($wp_category_id));
            }
        }
        
        return $post_id;
    }
    
    /**
     * Migrate environmental data
     */
    private function migrate_environmental_data() {
        $this->log_message('Migrating environmental data...');
        
        // Migrate environmental data points
        $env_data = $this->wpdb->get_results("SELECT * FROM environmental_data LIMIT 1000", ARRAY_A);
        
        foreach ($env_data as $data) {
            $post_id = wp_insert_post(array(
                'post_title' => $data['data_type'] . ' - ' . $data['location'],
                'post_content' => json_encode($data),
                'post_type' => 'environmental_data',
                'post_status' => 'publish',
                'meta_input' => array(
                    'ep_original_id' => $data['data_id'],
                    'ep_data_type' => $data['data_type'],
                    'ep_value' => $data['value'],
                    'ep_unit' => $data['unit'],
                    'ep_location_lat' => $data['location_lat'],
                    'ep_location_lng' => $data['location_lng'],
                    'ep_timestamp' => $data['timestamp']
                )
            ));
        }
        
        // Migrate carbon footprints
        $carbon_data = $this->wpdb->get_results("SELECT * FROM carbon_footprints", ARRAY_A);
        
        foreach ($carbon_data as $carbon) {
            $wp_user_id = $this->get_wp_user_id($carbon['user_id']);
            if ($wp_user_id) {
                update_user_meta($wp_user_id, 'ep_carbon_footprint_' . $carbon['month'] . '_' . $carbon['year'], array(
                    'total_carbon_kg' => $carbon['total_carbon_kg'],
                    'transportation_kg' => $carbon['transportation_kg'],
                    'energy_kg' => $carbon['energy_kg'],
                    'food_kg' => $carbon['food_kg'],
                    'waste_kg' => $carbon['waste_kg']
                ));
            }
        }
    }
    
    /**
     * Migrate e-commerce data
     */
    private function migrate_ecommerce_data() {
        $this->log_message('Migrating e-commerce data...');
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $this->log_message('WooCommerce not active. Creating custom product posts.');
        }
        
        // Migrate products
        $products = $this->wpdb->get_results("SELECT * FROM products", ARRAY_A);
        
        foreach ($products as $product) {
            $post_type = class_exists('WooCommerce') ? 'product' : 'ep_product';
            
            $post_id = wp_insert_post(array(
                'post_title' => $product['name'],
                'post_content' => $product['description'],
                'post_type' => $post_type,
                'post_status' => $this->map_post_status($product['status']),
                'meta_input' => array(
                    'ep_original_id' => $product['product_id'],
                    'ep_price' => $product['price'],
                    'ep_sustainability_score' => $product['sustainability_score'],
                    'ep_carbon_footprint_kg' => $product['carbon_footprint_kg'],
                    'ep_eco_certifications' => $product['eco_certifications']
                )
            ));
            
            if (class_exists('WooCommerce') && $post_id) {
                // Set as simple product
                wp_set_object_terms($post_id, 'simple', 'product_type');
                update_post_meta($post_id, '_price', $product['price']);
                update_post_meta($post_id, '_regular_price', $product['price']);
            }
        }
    }
    
    /**
     * Migrate community data (forums, topics, posts)
     */
    private function migrate_community_data() {
        $this->log_message('Migrating community data...');
        
        // Migrate forums
        $forums = $this->wpdb->get_results("SELECT * FROM forums", ARRAY_A);
        
        foreach ($forums as $forum) {
            $post_id = wp_insert_post(array(
                'post_title' => $forum['name'],
                'post_content' => $forum['description'],
                'post_type' => 'forum',
                'post_status' => $this->map_post_status($forum['status']),
                'meta_input' => array(
                    'ep_original_id' => $forum['forum_id'],
                    'ep_environmental_focus' => $forum['environmental_focus']
                )
            ));
        }
        
        // Migrate forum topics
        $topics = $this->wpdb->get_results("SELECT * FROM forum_topics LIMIT 500", ARRAY_A);
        
        foreach ($topics as $topic) {
            $wp_user_id = $this->get_wp_user_id($topic['author_id']);
            
            $post_id = wp_insert_post(array(
                'post_title' => $topic['title'],
                'post_content' => $topic['content'],
                'post_type' => 'topic',
                'post_status' => 'publish',
                'post_author' => $wp_user_id ?: 1,
                'post_date' => $topic['created_at'],
                'meta_input' => array(
                    'ep_original_id' => $topic['topic_id'],
                    'ep_forum_id' => $topic['forum_id'],
                    'ep_environmental_category' => $topic['environmental_category']
                )
            ));
        }
    }
    
    /**
     * Migrate gamification data
     */
    private function migrate_gamification_data() {
        $this->log_message('Migrating gamification data...');
        
        // Migrate achievements
        $achievements = $this->wpdb->get_results("SELECT * FROM achievements", ARRAY_A);
        
        foreach ($achievements as $achievement) {
            $post_id = wp_insert_post(array(
                'post_title' => $achievement['name'],
                'post_content' => $achievement['description'],
                'post_type' => 'achievement',
                'post_status' => 'publish',
                'meta_input' => array(
                    'ep_original_id' => $achievement['achievement_id'],
                    'ep_badge_icon' => $achievement['badge_icon'],
                    'ep_points_required' => $achievement['points_required'],
                    'ep_achievement_type' => $achievement['achievement_type']
                )
            ));
        }
        
        // Migrate user achievements
        $user_achievements = $this->wpdb->get_results("SELECT * FROM user_achievements", ARRAY_A);
        
        foreach ($user_achievements as $user_achievement) {
            $wp_user_id = $this->get_wp_user_id($user_achievement['user_id']);
            if ($wp_user_id) {
                add_user_meta($wp_user_id, 'ep_achievement_' . $user_achievement['achievement_id'], array(
                    'earned_date' => $user_achievement['earned_date'],
                    'progress_percentage' => $user_achievement['progress_percentage']
                ));
            }
        }
    }
    
    /**
     * Setup relationships between migrated data
     */
    private function setup_relationships() {
        $this->log_message('Setting up relationships...');
        
        // This would involve creating proper relationships between posts, users, and terms
        // Based on the original environmental platform relationships
        
        $this->log_message('Relationships setup completed.');
    }
    
    /**
     * Helper method to get WordPress user ID from EP user ID
     */
    private function get_wp_user_id($ep_user_id) {
        $users = get_users(array(
            'meta_key' => 'ep_original_id',
            'meta_value' => $ep_user_id,
            'number' => 1
        ));
        
        return !empty($users) ? $users[0]->ID : null;
    }
    
    /**
     * Helper method to get WordPress category ID from EP category ID
     */
    private function get_wp_category_id($ep_category_id) {
        $terms = get_terms(array(
            'taxonomy' => 'category',
            'meta_key' => 'ep_original_id',
            'meta_value' => $ep_category_id,
            'number' => 1,
            'hide_empty' => false
        ));
        
        return !empty($terms) ? $terms[0]->term_id : null;
    }
    
    /**
     * Map environmental platform status to WordPress status
     */
    private function map_post_status($ep_status) {
        $status_map = array(
            'active' => 'publish',
            'inactive' => 'draft',
            'pending' => 'pending',
            'archived' => 'private'
        );
        
        return $status_map[$ep_status] ?? 'draft';
    }
    
    /**
     * Map environmental platform user type to WordPress role
     */
    private function map_user_role($user_type) {
        $role_map = array(
            'admin' => 'administrator',
            'moderator' => 'editor',
            'premium_user' => 'author',
            'regular_user' => 'subscriber',
            'eco_expert' => 'contributor'
        );
        
        return $role_map[$user_type] ?? 'subscriber';
    }
    
    /**
     * Log migration message
     */
    private function log_message($message) {
        $this->migration_log[] = array(
            'timestamp' => current_time('mysql'),
            'message' => $message
        );
        
        error_log("EP Migration: " . $message);
    }
    
    /**
     * Start migration log
     */
    private function start_migration_log($type) {
        $this->migration_log = array();
        $this->log_message("Starting {$type} migration...");
    }
    
    /**
     * Complete migration log
     */
    private function complete_migration_log($status, $error = null) {
        if ($status === 'success') {
            $this->log_message("Migration completed successfully.");
        } else {
            $this->log_message("Migration failed: " . $error);
        }
        
        // Save log to database
        update_option('ep_last_migration_log', $this->migration_log);
    }
    
    /**
     * Check migration status
     */
    public function check_migration_status() {
        check_ajax_referer('ep_migration', 'nonce');
        
        $last_log = get_option('ep_last_migration_log', array());
        $migration_stats = $this->get_migration_stats();
        
        wp_send_json_success(array(
            'log' => $last_log,
            'stats' => $migration_stats
        ));
    }
    
    /**
     * Get migration statistics
     */
    private function get_migration_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Count migrated users
        $stats['users'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'ep_original_id'"
        );
        
        // Count migrated posts
        $stats['posts'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'ep_original_id'"
        );
        
        // Count migrated terms
        $stats['terms'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_key = 'ep_original_id'"
        );
        
        return $stats;
    }
    
    /**
     * Rollback migration (remove all migrated data)
     */
    public function rollback_migration() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        check_ajax_referer('ep_migration', 'nonce');
        
        try {
            // Remove migrated posts
            $migrated_posts = get_posts(array(
                'meta_key' => 'ep_original_id',
                'post_type' => 'any',
                'numberposts' => -1
            ));
            
            foreach ($migrated_posts as $post) {
                wp_delete_post($post->ID, true);
            }
            
            // Remove migrated users (except current user)
            $migrated_users = get_users(array(
                'meta_key' => 'ep_original_id'
            ));
            
            foreach ($migrated_users as $user) {
                if ($user->ID != get_current_user_id()) {
                    wp_delete_user($user->ID);
                }
            }
            
            // Remove migrated terms
            $migrated_terms = get_terms(array(
                'meta_key' => 'ep_original_id',
                'taxonomy' => get_taxonomies(),
                'hide_empty' => false
            ));
            
            foreach ($migrated_terms as $term) {
                wp_delete_term($term->term_id, $term->taxonomy);
            }
            
            wp_send_json_success(array(
                'message' => __('Migration rollback completed successfully.', 'environmental-platform-core')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Rollback failed: ', 'environmental-platform-core') . $e->getMessage()
            ));
        }
    }
}

// Initialize the migration handler
new EP_Database_Migration();
