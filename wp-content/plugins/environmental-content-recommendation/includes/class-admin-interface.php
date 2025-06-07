<?php
/**
 * Admin Interface Class
 *
 * Handles admin dashboard for the Environmental Content Recommendation plugin
 * Provides settings management, analytics dashboard, and recommendation management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ECR_Admin_Interface {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Performance tracker instance
     */
    private $performance_tracker;
    
    /**
     * Recommendation engine instance
     */
    private $recommendation_engine;
    
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
        $this->performance_tracker = ECR_Performance_Tracker::get_instance();
        $this->recommendation_engine = ECR_Recommendation_Engine::get_instance();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ecr_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_ecr_update_settings', array($this, 'ajax_update_settings'));
        add_action('wp_ajax_ecr_force_recommendations', array($this, 'ajax_force_recommendations'));
        add_action('wp_ajax_ecr_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_ecr_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Content Recommendations', 'environmental-content-recommendation'),
            __('Recommendations', 'environmental-content-recommendation'),
            'manage_options',
            'ecr-recommendations',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'ecr-recommendations',
            __('Dashboard', 'environmental-content-recommendation'),
            __('Dashboard', 'environmental-content-recommendation'),
            'manage_options',
            'ecr-recommendations',
            array($this, 'render_dashboard')
        );
        
        // Analytics submenu
        add_submenu_page(
            'ecr-recommendations',
            __('Analytics', 'environmental-content-recommendation'),
            __('Analytics', 'environmental-content-recommendation'),
            'manage_options',
            'ecr-analytics',
            array($this, 'render_analytics')
        );
        
        // Settings submenu
        add_submenu_page(
            'ecr-recommendations',
            __('Settings', 'environmental-content-recommendation'),
            __('Settings', 'environmental-content-recommendation'),
            'manage_options',
            'ecr-settings',
            array($this, 'render_settings')
        );
        
        // User Behavior submenu
        add_submenu_page(
            'ecr-recommendations',
            __('User Behavior', 'environmental-content-recommendation'),
            __('User Behavior', 'environmental-content-recommendation'),
            'manage_options',
            'ecr-user-behavior',
            array($this, 'render_user_behavior')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ecr_settings', 'ecr_options');
        
        // General settings section
        add_settings_section(
            'ecr_general_settings',
            __('General Settings', 'environmental-content-recommendation'),
            array($this, 'general_settings_callback'),
            'ecr_general'
        );
        
        // Algorithm settings section
        add_settings_section(
            'ecr_algorithm_settings',
            __('Algorithm Settings', 'environmental-content-recommendation'),
            array($this, 'algorithm_settings_callback'),
            'ecr_algorithm'
        );
        
        // Display settings section
        add_settings_section(
            'ecr_display_settings',
            __('Display Settings', 'environmental-content-recommendation'),
            array($this, 'display_settings_callback'),
            'ecr_display'
        );
        
        // Performance settings section
        add_settings_section(
            'ecr_performance_settings',
            __('Performance Settings', 'environmental-content-recommendation'),
            array($this, 'performance_settings_callback'),
            'ecr_performance'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ecr-') === false && $hook !== 'toplevel_page_ecr-recommendations') {
            return;
        }
        
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'ecr-admin-styles',
            ECR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ECR_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'ecr-admin-scripts',
            ECR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chartjs'),
            ECR_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('ecr-admin-scripts', 'ecr_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecr_admin_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'environmental-content-recommendation'),
                'error' => __('An error occurred', 'environmental-content-recommendation'),
                'success' => __('Success', 'environmental-content-recommendation'),
                'confirm_clear_cache' => __('Are you sure you want to clear the cache?', 'environmental-content-recommendation'),
                'confirm_force_recommendations' => __('Are you sure you want to force regenerate recommendations?', 'environmental-content-recommendation')
            )
        ));
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap ecr-admin-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ecr-dashboard-stats">
                <div class="ecr-stat-card">
                    <h3><?php _e('Total Recommendations', 'environmental-content-recommendation'); ?></h3>
                    <div class="ecr-stat-number"><?php echo esc_html(number_format($stats['total_recommendations'])); ?></div>
                </div>
                
                <div class="ecr-stat-card">
                    <h3><?php _e('Click-through Rate', 'environmental-content-recommendation'); ?></h3>
                    <div class="ecr-stat-number"><?php echo esc_html(number_format($stats['ctr'], 2)); ?>%</div>
                </div>
                
                <div class="ecr-stat-card">
                    <h3><?php _e('Active Users', 'environmental-content-recommendation'); ?></h3>
                    <div class="ecr-stat-number"><?php echo esc_html(number_format($stats['active_users'])); ?></div>
                </div>
                
                <div class="ecr-stat-card">
                    <h3><?php _e('Engagement Score', 'environmental-content-recommendation'); ?></h3>
                    <div class="ecr-stat-number"><?php echo esc_html(number_format($stats['engagement_score'], 1)); ?></div>
                </div>
            </div>
            
            <div class="ecr-dashboard-charts">
                <div class="ecr-chart-container">
                    <h3><?php _e('Recommendation Performance (Last 30 Days)', 'environmental-content-recommendation'); ?></h3>
                    <canvas id="ecr-performance-chart"></canvas>
                </div>
                
                <div class="ecr-chart-container">
                    <h3><?php _e('Recommendation Types Distribution', 'environmental-content-recommendation'); ?></h3>
                    <canvas id="ecr-types-chart"></canvas>
                </div>
            </div>
            
            <div class="ecr-quick-actions">
                <h3><?php _e('Quick Actions', 'environmental-content-recommendation'); ?></h3>
                <div class="ecr-action-buttons">
                    <button id="ecr-force-recommendations" class="button button-primary">
                        <?php _e('Regenerate Recommendations', 'environmental-content-recommendation'); ?>
                    </button>
                    <button id="ecr-clear-cache" class="button">
                        <?php _e('Clear Cache', 'environmental-content-recommendation'); ?>
                    </button>
                    <button id="ecr-export-data" class="button">
                        <?php _e('Export Analytics Data', 'environmental-content-recommendation'); ?>
                    </button>
                </div>
            </div>
            
            <div class="ecr-recent-activity">
                <h3><?php _e('Recent Activity', 'environmental-content-recommendation'); ?></h3>
                <div id="ecr-recent-activity-list">
                    <?php $this->render_recent_activity(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics() {
        ?>
        <div class="wrap ecr-admin-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ecr-analytics-filters">
                <select id="ecr-date-range">
                    <option value="7"><?php _e('Last 7 days', 'environmental-content-recommendation'); ?></option>
                    <option value="30" selected><?php _e('Last 30 days', 'environmental-content-recommendation'); ?></option>
                    <option value="90"><?php _e('Last 90 days', 'environmental-content-recommendation'); ?></option>
                </select>
                
                <select id="ecr-metric-type">
                    <option value="impressions"><?php _e('Impressions', 'environmental-content-recommendation'); ?></option>
                    <option value="clicks"><?php _e('Clicks', 'environmental-content-recommendation'); ?></option>
                    <option value="conversions"><?php _e('Conversions', 'environmental-content-recommendation'); ?></option>
                    <option value="ctr"><?php _e('Click-through Rate', 'environmental-content-recommendation'); ?></option>
                </select>
                
                <button id="ecr-refresh-analytics" class="button">
                    <?php _e('Refresh', 'environmental-content-recommendation'); ?>
                </button>
            </div>
            
            <div class="ecr-analytics-charts">
                <div class="ecr-chart-container ecr-chart-large">
                    <h3><?php _e('Performance Trends', 'environmental-content-recommendation'); ?></h3>
                    <canvas id="ecr-trends-chart"></canvas>
                </div>
                
                <div class="ecr-analytics-grid">
                    <div class="ecr-chart-container">
                        <h3><?php _e('Device Types', 'environmental-content-recommendation'); ?></h3>
                        <canvas id="ecr-device-chart"></canvas>
                    </div>
                    
                    <div class="ecr-chart-container">
                        <h3><?php _e('Recommendation Positions', 'environmental-content-recommendation'); ?></h3>
                        <canvas id="ecr-position-chart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="ecr-analytics-tables">
                <div class="ecr-table-container">
                    <h3><?php _e('Top Performing Content', 'environmental-content-recommendation'); ?></h3>
                    <div id="ecr-top-content-table"></div>
                </div>
                
                <div class="ecr-table-container">
                    <h3><?php _e('User Segments Performance', 'environmental-content-recommendation'); ?></h3>
                    <div id="ecr-segments-table"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $options = get_option('ecr_options', array());
        ?>
        <div class="wrap ecr-admin-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php" id="ecr-settings-form">
                <?php
                settings_fields('ecr_settings');
                ?>
                
                <div class="ecr-settings-tabs">
                    <nav class="ecr-tab-nav">
                        <a href="#general" class="ecr-tab-link active"><?php _e('General', 'environmental-content-recommendation'); ?></a>
                        <a href="#algorithms" class="ecr-tab-link"><?php _e('Algorithms', 'environmental-content-recommendation'); ?></a>
                        <a href="#display" class="ecr-tab-link"><?php _e('Display', 'environmental-content-recommendation'); ?></a>
                        <a href="#performance" class="ecr-tab-link"><?php _e('Performance', 'environmental-content-recommendation'); ?></a>
                        <a href="#advanced" class="ecr-tab-link"><?php _e('Advanced', 'environmental-content-recommendation'); ?></a>
                    </nav>
                    
                    <!-- General Settings Tab -->
                    <div id="general" class="ecr-tab-content active">
                        <h2><?php _e('General Settings', 'environmental-content-recommendation'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Recommendations', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[enabled]" value="1" 
                                               <?php checked(isset($options['enabled']) ? $options['enabled'] : 1, 1); ?> />
                                        <?php _e('Enable content recommendations', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Track User Behavior', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[track_behavior]" value="1" 
                                               <?php checked(isset($options['track_behavior']) ? $options['track_behavior'] : 1, 1); ?> />
                                        <?php _e('Track user interactions for personalization', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Minimum Content Age', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="number" name="ecr_options[min_content_age]" 
                                           value="<?php echo esc_attr(isset($options['min_content_age']) ? $options['min_content_age'] : 1); ?>" 
                                           min="0" max="30" /> <?php _e('days', 'environmental-content-recommendation'); ?>
                                    <p class="description"><?php _e('Minimum age for content to be included in recommendations', 'environmental-content-recommendation'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Cache Duration', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <select name="ecr_options[cache_duration]">
                                        <option value="3600" <?php selected(isset($options['cache_duration']) ? $options['cache_duration'] : 3600, 3600); ?>>
                                            <?php _e('1 Hour', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="21600" <?php selected(isset($options['cache_duration']) ? $options['cache_duration'] : 3600, 21600); ?>>
                                            <?php _e('6 Hours', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="43200" <?php selected(isset($options['cache_duration']) ? $options['cache_duration'] : 3600, 43200); ?>>
                                            <?php _e('12 Hours', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="86400" <?php selected(isset($options['cache_duration']) ? $options['cache_duration'] : 3600, 86400); ?>>
                                            <?php _e('24 Hours', 'environmental-content-recommendation'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Algorithm Settings Tab -->
                    <div id="algorithms" class="ecr-tab-content">
                        <h2><?php _e('Algorithm Settings', 'environmental-content-recommendation'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Personalized Weight', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="range" name="ecr_options[weight_personalized]" 
                                           value="<?php echo esc_attr(isset($options['weight_personalized']) ? $options['weight_personalized'] : 0.3); ?>" 
                                           min="0" max="1" step="0.1" class="ecr-range-slider" />
                                    <span class="ecr-range-value"><?php echo esc_attr(isset($options['weight_personalized']) ? $options['weight_personalized'] : 0.3); ?></span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Similar Content Weight', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="range" name="ecr_options[weight_similar]" 
                                           value="<?php echo esc_attr(isset($options['weight_similar']) ? $options['weight_similar'] : 0.25); ?>" 
                                           min="0" max="1" step="0.1" class="ecr-range-slider" />
                                    <span class="ecr-range-value"><?php echo esc_attr(isset($options['weight_similar']) ? $options['weight_similar'] : 0.25); ?></span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Trending Weight', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="range" name="ecr_options[weight_trending]" 
                                           value="<?php echo esc_attr(isset($options['weight_trending']) ? $options['weight_trending'] : 0.2); ?>" 
                                           min="0" max="1" step="0.1" class="ecr-range-slider" />
                                    <span class="ecr-range-value"><?php echo esc_attr(isset($options['weight_trending']) ? $options['weight_trending'] : 0.2); ?></span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Environmental Weight', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="range" name="ecr_options[weight_environmental]" 
                                           value="<?php echo esc_attr(isset($options['weight_environmental']) ? $options['weight_environmental'] : 0.25); ?>" 
                                           min="0" max="1" step="0.1" class="ecr-range-slider" />
                                    <span class="ecr-range-value"><?php echo esc_attr(isset($options['weight_environmental']) ? $options['weight_environmental'] : 0.25); ?></span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Diversity Factor', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="range" name="ecr_options[diversity_factor]" 
                                           value="<?php echo esc_attr(isset($options['diversity_factor']) ? $options['diversity_factor'] : 0.3); ?>" 
                                           min="0" max="1" step="0.1" class="ecr-range-slider" />
                                    <span class="ecr-range-value"><?php echo esc_attr(isset($options['diversity_factor']) ? $options['diversity_factor'] : 0.3); ?></span>
                                    <p class="description"><?php _e('Higher values increase recommendation diversity', 'environmental-content-recommendation'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Display Settings Tab -->
                    <div id="display" class="ecr-tab-content">
                        <h2><?php _e('Display Settings', 'environmental-content-recommendation'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Default Recommendations Count', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="number" name="ecr_options[default_count]" 
                                           value="<?php echo esc_attr(isset($options['default_count']) ? $options['default_count'] : 5); ?>" 
                                           min="1" max="20" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Default Layout', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <select name="ecr_options[default_layout]">
                                        <option value="grid" <?php selected(isset($options['default_layout']) ? $options['default_layout'] : 'grid', 'grid'); ?>>
                                            <?php _e('Grid', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="list" <?php selected(isset($options['default_layout']) ? $options['default_layout'] : 'grid', 'list'); ?>>
                                            <?php _e('List', 'environmental-content-recommendation'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Show Environmental Badges', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[show_env_badges]" value="1" 
                                               <?php checked(isset($options['show_env_badges']) ? $options['show_env_badges'] : 1, 1); ?> />
                                        <?php _e('Display environmental impact badges', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Auto-inject Recommendations', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[auto_inject]" value="1" 
                                               <?php checked(isset($options['auto_inject']) ? $options['auto_inject'] : 0, 1); ?> />
                                        <?php _e('Automatically add recommendations to post content', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Performance Settings Tab -->
                    <div id="performance" class="ecr-tab-content">
                        <h2><?php _e('Performance Settings', 'environmental-content-recommendation'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Performance Tracking', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[enable_tracking]" value="1" 
                                               <?php checked(isset($options['enable_tracking']) ? $options['enable_tracking'] : 1, 1); ?> />
                                        <?php _e('Track recommendation performance metrics', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Data Retention Period', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <select name="ecr_options[data_retention]">
                                        <option value="30" <?php selected(isset($options['data_retention']) ? $options['data_retention'] : 90, 30); ?>>
                                            <?php _e('30 days', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="90" <?php selected(isset($options['data_retention']) ? $options['data_retention'] : 90, 90); ?>>
                                            <?php _e('90 days', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="180" <?php selected(isset($options['data_retention']) ? $options['data_retention'] : 90, 180); ?>>
                                            <?php _e('180 days', 'environmental-content-recommendation'); ?>
                                        </option>
                                        <option value="365" <?php selected(isset($options['data_retention']) ? $options['data_retention'] : 90, 365); ?>>
                                            <?php _e('1 year', 'environmental-content-recommendation'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Email Reports', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[email_reports]" value="1" 
                                               <?php checked(isset($options['email_reports']) ? $options['email_reports'] : 0, 1); ?> />
                                        <?php _e('Send weekly performance reports via email', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Report Email Address', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="email" name="ecr_options[report_email]" 
                                           value="<?php echo esc_attr(isset($options['report_email']) ? $options['report_email'] : get_option('admin_email')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Advanced Settings Tab -->
                    <div id="advanced" class="ecr-tab-content">
                        <h2><?php _e('Advanced Settings', 'environmental-content-recommendation'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Debug Mode', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ecr_options[debug_mode]" value="1" 
                                               <?php checked(isset($options['debug_mode']) ? $options['debug_mode'] : 0, 1); ?> />
                                        <?php _e('Enable debug logging', 'environmental-content-recommendation'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('API Rate Limit', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <input type="number" name="ecr_options[api_rate_limit]" 
                                           value="<?php echo esc_attr(isset($options['api_rate_limit']) ? $options['api_rate_limit'] : 100); ?>" 
                                           min="1" max="1000" /> <?php _e('requests per hour', 'environmental-content-recommendation'); ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Exclude Content Types', 'environmental-content-recommendation'); ?></th>
                                <td>
                                    <?php
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    $excluded_types = isset($options['excluded_types']) ? $options['excluded_types'] : array();
                                    
                                    foreach ($post_types as $post_type) {
                                        echo '<label><input type="checkbox" name="ecr_options[excluded_types][]" value="' . esc_attr($post_type->name) . '" ' . 
                                             checked(in_array($post_type->name, $excluded_types), true, false) . ' /> ' . 
                                             esc_html($post_type->label) . '</label><br>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render user behavior page
     */
    public function render_user_behavior() {
        ?>
        <div class="wrap ecr-admin-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ecr-behavior-overview">
                <div class="ecr-behavior-stats">
                    <div class="ecr-stat-card">
                        <h3><?php _e('Total Sessions', 'environmental-content-recommendation'); ?></h3>
                        <div class="ecr-stat-number" id="total-sessions">-</div>
                    </div>
                    
                    <div class="ecr-stat-card">
                        <h3><?php _e('Average Session Duration', 'environmental-content-recommendation'); ?></h3>
                        <div class="ecr-stat-number" id="avg-session-duration">-</div>
                    </div>
                    
                    <div class="ecr-stat-card">
                        <h3><?php _e('Most Active Users', 'environmental-content-recommendation'); ?></h3>
                        <div class="ecr-stat-number" id="active-users">-</div>
                    </div>
                </div>
            </div>
            
            <div class="ecr-behavior-charts">
                <div class="ecr-chart-container">
                    <h3><?php _e('User Activity Heatmap', 'environmental-content-recommendation'); ?></h3>
                    <canvas id="ecr-activity-heatmap"></canvas>
                </div>
                
                <div class="ecr-chart-container">
                    <h3><?php _e('Content Engagement by Category', 'environmental-content-recommendation'); ?></h3>
                    <canvas id="ecr-category-engagement"></canvas>
                </div>
            </div>
            
            <div class="ecr-behavior-tables">
                <div class="ecr-table-container">
                    <h3><?php _e('Top User Behaviors', 'environmental-content-recommendation'); ?></h3>
                    <div id="ecr-behavior-table"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array(
            'total_recommendations' => 0,
            'ctr' => 0,
            'active_users' => 0,
            'engagement_score' => 0
        );
        
        // Get total recommendations from last 30 days
        $total_recommendations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}ecr_recommendation_performance 
            WHERE created_at >= %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        $stats['total_recommendations'] = intval($total_recommendations);
        
        // Calculate CTR
        $impressions = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(impressions) 
            FROM {$wpdb->prefix}ecr_recommendation_performance 
            WHERE created_at >= %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        $clicks = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(clicks) 
            FROM {$wpdb->prefix}ecr_recommendation_performance 
            WHERE created_at >= %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        if ($impressions > 0) {
            $stats['ctr'] = ($clicks / $impressions) * 100;
        }
        
        // Get active users
        $active_users = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}ecr_user_behavior 
            WHERE timestamp >= %s
        ", date('Y-m-d H:i:s', strtotime('-7 days'))));
        
        $stats['active_users'] = intval($active_users);
        
        // Calculate engagement score (weighted average of various metrics)
        $stats['engagement_score'] = min(10, ($stats['ctr'] / 2) + ($stats['active_users'] / 10));
        
        return $stats;
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;
        
        $recent_activities = $wpdb->get_results($wpdb->prepare("
            SELECT ub.*, p.post_title 
            FROM {$wpdb->prefix}ecr_user_behavior ub
            LEFT JOIN {$wpdb->posts} p ON ub.content_id = p.ID
            WHERE ub.timestamp >= %s
            ORDER BY ub.timestamp DESC
            LIMIT 10
        ", date('Y-m-d H:i:s', strtotime('-24 hours'))));
        
        if (empty($recent_activities)) {
            echo '<p>' . __('No recent activity found.', 'environmental-content-recommendation') . '</p>';
            return;
        }
        
        echo '<ul class="ecr-activity-list">';
        foreach ($recent_activities as $activity) {
            $user = get_user_by('ID', $activity->user_id);
            $username = $user ? $user->user_login : __('Guest', 'environmental-content-recommendation');
            $action_text = $this->get_action_text($activity->action);
            $time_ago = human_time_diff(strtotime($activity->timestamp), current_time('timestamp'));
            
            echo '<li class="ecr-activity-item">';
            echo '<span class="ecr-activity-user">' . esc_html($username) . '</span> ';
            echo '<span class="ecr-activity-action">' . esc_html($action_text) . '</span> ';
            if ($activity->post_title) {
                echo '<span class="ecr-activity-content">"' . esc_html($activity->post_title) . '"</span> ';
            }
            echo '<span class="ecr-activity-time">' . sprintf(__('%s ago', 'environmental-content-recommendation'), $time_ago) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Get human-readable action text
     */
    private function get_action_text($action) {
        $actions = array(
            'view' => __('viewed', 'environmental-content-recommendation'),
            'click' => __('clicked on', 'environmental-content-recommendation'),
            'share' => __('shared', 'environmental-content-recommendation'),
            'like' => __('liked', 'environmental-content-recommendation'),
            'comment' => __('commented on', 'environmental-content-recommendation'),
            'scroll' => __('scrolled through', 'environmental-content-recommendation')
        );
        
        return isset($actions[$action]) ? $actions[$action] : $action;
    }
    
    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $date_range = intval($_POST['date_range'] ?? 30);
        $metric_type = sanitize_text_field($_POST['metric_type'] ?? 'impressions');
        
        $data = $this->performance_tracker->get_analytics_data($date_range, $metric_type);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for updating settings
     */
    public function ajax_update_settings() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $settings = $_POST['settings'] ?? array();
        
        // Sanitize settings
        $sanitized_settings = array();
        foreach ($settings as $key => $value) {
            $sanitized_settings[sanitize_key($key)] = sanitize_text_field($value);
        }
        
        update_option('ecr_options', $sanitized_settings);
        
        wp_send_json_success(array('message' => __('Settings updated successfully', 'environmental-content-recommendation')));
    }
    
    /**
     * AJAX handler for forcing recommendation regeneration
     */
    public function ajax_force_recommendations() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        // Clear existing recommendations
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ecr_user_recommendations");
        
        // Force regeneration by clearing cache and running background task
        wp_cache_flush();
        wp_schedule_single_event(time(), 'ecr_generate_recommendations');
        
        wp_send_json_success(array('message' => __('Recommendations regeneration started', 'environmental-content-recommendation')));
    }
    
    /**
     * AJAX handler for exporting data
     */
    public function ajax_export_data() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type'] ?? 'performance');
        
        $data = $this->performance_tracker->export_data($export_type);
        
        wp_send_json_success(array(
            'data' => $data,
            'filename' => 'ecr_' . $export_type . '_' . date('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('ecr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-content-recommendation'));
        }
        
        // Clear WordPress cache
        wp_cache_flush();
        
        // Clear plugin-specific cache
        delete_transient('ecr_similarity_cache');
        delete_transient('ecr_recommendations_cache');
        
        wp_send_json_success(array('message' => __('Cache cleared successfully', 'environmental-content-recommendation')));
    }
    
    /**
     * General settings section callback
     */
    public function general_settings_callback() {
        echo '<p>' . __('Configure general recommendation engine settings.', 'environmental-content-recommendation') . '</p>';
    }
    
    /**
     * Algorithm settings section callback
     */
    public function algorithm_settings_callback() {
        echo '<p>' . __('Adjust the weights for different recommendation algorithms.', 'environmental-content-recommendation') . '</p>';
    }
    
    /**
     * Display settings section callback
     */
    public function display_settings_callback() {
        echo '<p>' . __('Configure how recommendations are displayed to users.', 'environmental-content-recommendation') . '</p>';
    }
    
    /**
     * Performance settings section callback
     */
    public function performance_settings_callback() {
        echo '<p>' . __('Configure performance tracking and analytics settings.', 'environmental-content-recommendation') . '</p>';
    }
}

// Initialize admin interface
ECR_Admin_Interface::get_instance();
