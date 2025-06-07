<?php
/**
 * Content Manager Class
 * 
 * Manages content operations, editing interfaces, and content analytics
 * 
 * @package EnvironmentalAdminDashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Content_Manager {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_env_content_action', array($this, 'ajax_content_action'));
        add_filter('manage_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Content Management', 'env-admin-dashboard'),
            __('Environmental Content', 'env-admin-dashboard'),
            'manage_options',
            'env-content-management',
            array($this, 'render_content_management_page'),
            'dashicons-edit-large',
            25
        );
        
        add_submenu_page(
            'env-content-management',
            __('Content Analytics', 'env-admin-dashboard'),
            __('Analytics', 'env-admin-dashboard'),
            'manage_options',
            'env-content-analytics',
            array($this, 'render_content_analytics_page')
        );
    }
    
    /**
     * Render content management page
     */
    public function render_content_management_page() {
        ?>
        <div class="wrap env-content-management">
            <h1><?php _e('Environmental Content Management', 'env-admin-dashboard'); ?></h1>
            
            <div class="env-content-stats">
                <div class="env-stat-card">
                    <h3><?php _e('Total Posts', 'env-admin-dashboard'); ?></h3>
                    <span class="stat-number"><?php echo wp_count_posts()->publish; ?></span>
                </div>
                <div class="env-stat-card">
                    <h3><?php _e('Pages', 'env-admin-dashboard'); ?></h3>
                    <span class="stat-number"><?php echo wp_count_posts('page')->publish; ?></span>
                </div>
                <div class="env-stat-card">
                    <h3><?php _e('Comments', 'env-admin-dashboard'); ?></h3>
                    <span class="stat-number"><?php echo wp_count_comments()->approved; ?></span>
                </div>
            </div>
            
            <div class="env-content-actions">
                <h2><?php _e('Quick Actions', 'env-admin-dashboard'); ?></h2>
                <button class="button button-primary" onclick="envContentManager.bulkOptimizeImages()">
                    <?php _e('Optimize Images', 'env-admin-dashboard'); ?>
                </button>
                <button class="button" onclick="envContentManager.generateSitemap()">
                    <?php _e('Generate Sitemap', 'env-admin-dashboard'); ?>
                </button>
                <button class="button" onclick="envContentManager.analyzeContent()">
                    <?php _e('Analyze Content', 'env-admin-dashboard'); ?>
                </button>
            </div>
            
            <div class="env-recent-content">
                <h2><?php _e('Recent Content', 'env-admin-dashboard'); ?></h2>
                <?php $this->render_recent_content_table(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render content analytics page
     */
    public function render_content_analytics_page() {
        ?>
        <div class="wrap env-content-analytics">
            <h1><?php _e('Content Analytics', 'env-admin-dashboard'); ?></h1>
            
            <div class="env-analytics-grid">
                <div class="env-chart-container">
                    <h3><?php _e('Content Performance', 'env-admin-dashboard'); ?></h3>
                    <canvas id="contentPerformanceChart"></canvas>
                </div>
                
                <div class="env-chart-container">
                    <h3><?php _e('Engagement Metrics', 'env-admin-dashboard'); ?></h3>
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
            
            <div class="env-top-content">
                <h2><?php _e('Top Performing Content', 'env-admin-dashboard'); ?></h2>
                <?php $this->render_top_content_table(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent content table
     */
    private function render_recent_content_table() {
        $recent_posts = get_posts(array(
            'numberposts' => 10,
            'post_status' => 'publish'
        ));
        
        if (empty($recent_posts)) {
            echo '<p>' . __('No recent content found.', 'env-admin-dashboard') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Type', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Date', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Views', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Actions', 'env-admin-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_posts as $post): ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                            <?php echo esc_html($post->post_title); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($post->post_type); ?></td>
                    <td><?php echo get_the_date('Y-m-d H:i', $post); ?></td>
                    <td><?php echo get_post_meta($post->ID, '_env_view_count', true) ?: '0'; ?></td>
                    <td>
                        <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">
                            <?php _e('Edit', 'env-admin-dashboard'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render top content table
     */
    private function render_top_content_table() {
        global $wpdb;
        
        $top_posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_type, pm.meta_value as view_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
            WHERE p.post_status = %s
            ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
            LIMIT 10
        ", '_env_view_count', 'publish'));
        
        if (empty($top_posts)) {
            echo '<p>' . __('No content analytics available.', 'env-admin-dashboard') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Type', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Views', 'env-admin-dashboard'); ?></th>
                    <th><?php _e('Engagement', 'env-admin-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_posts as $post): ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                            <?php echo esc_html($post->post_title); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($post->post_type); ?></td>
                    <td><?php echo intval($post->view_count); ?></td>
                    <td>
                        <?php 
                        $comments = get_comments_number($post->ID);
                        echo $comments . ' ' . __('comments', 'env-admin-dashboard');
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Add custom columns to post list
     */
    public function add_custom_columns($columns) {
        $columns['env_views'] = __('Views', 'env-admin-dashboard');
        $columns['env_engagement'] = __('Engagement', 'env-admin-dashboard');
        return $columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'env_views':
                echo get_post_meta($post_id, '_env_view_count', true) ?: '0';
                break;
            case 'env_engagement':
                $comments = get_comments_number($post_id);
                $likes = get_post_meta($post_id, '_env_likes_count', true) ?: 0;
                echo $comments . ' / ' . $likes;
                break;
        }
    }
    
    /**
     * AJAX handler for content actions
     */
    public function ajax_content_action() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'optimize_images':
                $result = $this->optimize_images();
                break;
            case 'generate_sitemap':
                $result = $this->generate_sitemap();
                break;
            case 'analyze_content':
                $result = $this->analyze_content();
                break;
            default:
                $result = array('success' => false, 'message' => 'Invalid action');
        }
        
        wp_die(json_encode($result));
    }
    
    /**
     * Optimize images
     */
    private function optimize_images() {
        // Placeholder for image optimization logic
        return array(
            'success' => true,
            'message' => __('Image optimization completed', 'env-admin-dashboard'),
            'optimized_count' => 0
        );
    }
    
    /**
     * Generate sitemap
     */
    private function generate_sitemap() {
        // Placeholder for sitemap generation logic
        return array(
            'success' => true,
            'message' => __('Sitemap generated successfully', 'env-admin-dashboard')
        );
    }
    
    /**
     * Analyze content
     */
    private function analyze_content() {
        // Placeholder for content analysis logic
        return array(
            'success' => true,
            'message' => __('Content analysis completed', 'env-admin-dashboard'),
            'issues_found' => 0
        );
    }
}
