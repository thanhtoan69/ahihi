<?php
/**
 * Environmental Social & Viral Admin Controller
 * 
 * Handles all admin interface functionality including dashboard, settings,
 * analytics display, and administrative controls for the social viral system.
 * 
 * @package Environmental_Social_Viral
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Social_Viral_Admin_Controller {
    
    private $analytics;
    private $sharing_manager;
    private $viral_engine;
    private $referral_system;
    private $content_generator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->analytics = Environmental_Social_Viral_Analytics::get_instance();
        $this->sharing_manager = Environmental_Social_Viral_Sharing_Manager::get_instance();
        $this->viral_engine = Environmental_Social_Viral_Engine::get_instance();
        $this->referral_system = Environmental_Social_Viral_Referral_System::get_instance();
        $this->content_generator = Environmental_Social_Viral_Content_Generator::get_instance();
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_env_social_save_settings', array($this, 'save_settings_ajax'));
        add_action('wp_ajax_env_social_test_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_env_social_reset_analytics', array($this, 'reset_analytics_data'));
        add_action('wp_ajax_env_social_export_data', array($this, 'export_analytics_data'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Social & Viral', 'environmental-social-viral'),
            __('Social & Viral', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral',
            array($this, 'dashboard_page'),
            'dashicons-share',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'env-social-viral',
            __('Dashboard', 'environmental-social-viral'),
            __('Dashboard', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'env-social-viral',
            __('Analytics', 'environmental-social-viral'),
            __('Analytics', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'env-social-viral',
            __('Sharing Settings', 'environmental-social-viral'),
            __('Sharing Settings', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral-sharing',
            array($this, 'sharing_settings_page')
        );
        
        add_submenu_page(
            'env-social-viral',
            __('Viral Engine', 'environmental-social-viral'),
            __('Viral Engine', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral-engine',
            array($this, 'viral_engine_page')
        );
        
        add_submenu_page(
            'env-social-viral',
            __('Referrals', 'environmental-social-viral'),
            __('Referrals', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral-referrals',
            array($this, 'referrals_page')
        );
        
        add_submenu_page(
            'env-social-viral',
            __('Content Generator', 'environmental-social-viral'),
            __('Content Generator', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral-content',
            array($this, 'content_generator_page')
        );
        
        add_submenu_page(
            'env-social-viral',
            __('Settings', 'environmental-social-viral'),
            __('Settings', 'environmental-social-viral'),
            'manage_options',
            'env-social-viral-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('env_social_viral_settings', 'env_social_viral_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        
        // Platform settings section
        add_settings_section(
            'env_social_platforms',
            __('Social Platform Settings', 'environmental-social-viral'),
            array($this, 'platforms_section_callback'),
            'env_social_viral_settings'
        );
        
        // Viral engine settings section
        add_settings_section(
            'env_viral_engine',
            __('Viral Engine Settings', 'environmental-social-viral'),
            array($this, 'viral_engine_section_callback'),
            'env_social_viral_settings'
        );
        
        // Referral settings section
        add_settings_section(
            'env_referral_system',
            __('Referral System Settings', 'environmental-social-viral'),
            array($this, 'referral_section_callback'),
            'env_social_viral_settings'
        );
        
        // Analytics settings section
        add_settings_section(
            'env_analytics_settings',
            __('Analytics Settings', 'environmental-social-viral'),
            array($this, 'analytics_section_callback'),
            'env_social_viral_settings'
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'env-social-viral') === false) {
            return;
        }
        
        wp_enqueue_script(
            'env-social-viral-admin',
            ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'chart-js'),
            ENV_SOCIAL_VIRAL_VERSION,
            true
        );
        
        wp_enqueue_style(
            'env-social-viral-admin',
            ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            ENV_SOCIAL_VIRAL_VERSION
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_localize_script('env-social-viral-admin', 'envSocialViralAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('env_social_viral_nonce'),
            'strings' => array(
                'saving' => __('Saving...', 'environmental-social-viral'),
                'saved' => __('Settings saved!', 'environmental-social-viral'),
                'error' => __('Error occurred', 'environmental-social-viral'),
                'confirm_reset' => __('Are you sure you want to reset analytics data?', 'environmental-social-viral'),
                'confirm_export' => __('Export analytics data?', 'environmental-social-viral')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $analytics_data = $this->analytics->get_analytics_data(array('period' => '30days'));
        $viral_stats = $this->viral_engine->get_viral_overview();
        $referral_stats = $this->referral_system->get_referral_overview();
        
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Social & Viral Dashboard', 'environmental-social-viral'); ?></h1>
            
            <div class="env-dashboard-grid">
                <!-- Quick Stats -->
                <div class="env-stats-grid">
                    <div class="env-stat-card">
                        <h3><?php _e('Total Shares', 'environmental-social-viral'); ?></h3>
                        <div class="stat-number"><?php echo number_format($analytics_data['shares']['total_shares']); ?></div>
                        <div class="stat-change positive">+12% <?php _e('from last month', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Click-through Rate', 'environmental-social-viral'); ?></h3>
                        <div class="stat-number"><?php echo $analytics_data['clicks']['click_through_rate']; ?>%</div>
                        <div class="stat-change positive">+3.2% <?php _e('from last month', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Viral Coefficient', 'environmental-social-viral'); ?></h3>
                        <div class="stat-number"><?php echo $viral_stats['avg_coefficient']; ?></div>
                        <div class="stat-change <?php echo $viral_stats['trend']; ?>"><?php echo $viral_stats['trend_percentage']; ?>% <?php _e('from last month', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-stat-card">
                        <h3><?php _e('Active Referrals', 'environmental-social-viral'); ?></h3>
                        <div class="stat-number"><?php echo number_format($referral_stats['active_referrals']); ?></div>
                        <div class="stat-change positive">+<?php echo $referral_stats['new_referrals']; ?> <?php _e('this week', 'environmental-social-viral'); ?></div>
                    </div>
                </div>
                
                <!-- Platform Performance Chart -->
                <div class="env-chart-container">
                    <h3><?php _e('Platform Performance', 'environmental-social-viral'); ?></h3>
                    <canvas id="platformPerformanceChart"></canvas>
                </div>
                
                <!-- Viral Content Trends -->
                <div class="env-chart-container">
                    <h3><?php _e('Viral Content Trends', 'environmental-social-viral'); ?></h3>
                    <canvas id="viralTrendsChart"></canvas>
                </div>
                
                <!-- Top Performing Content -->
                <div class="env-top-content">
                    <h3><?php _e('Top Performing Content', 'environmental-social-viral'); ?></h3>
                    <div class="content-list">
                        <?php foreach ($analytics_data['top_content'] as $content): ?>
                            <div class="content-item">
                                <div class="content-title"><?php echo esc_html($content->content_title); ?></div>
                                <div class="content-stats">
                                    <span class="shares"><?php echo number_format($content->total_shares); ?> <?php _e('shares', 'environmental-social-viral'); ?></span>
                                    <span class="clicks"><?php echo number_format($content->total_clicks); ?> <?php _e('clicks', 'environmental-social-viral'); ?></span>
                                    <span class="conversions"><?php echo number_format($content->total_conversions); ?> <?php _e('conversions', 'environmental-social-viral'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="env-recent-activity">
                    <h3><?php _e('Recent Activity', 'environmental-social-viral'); ?></h3>
                    <div class="activity-feed">
                        <?php $recent_activity = $this->get_recent_activity(); ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="<?php echo esc_attr($activity['icon']); ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text"><?php echo $activity['text']; ?></div>
                                    <div class="activity-time"><?php echo $activity['time']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="env-quick-actions">
                <h3><?php _e('Quick Actions', 'environmental-social-viral'); ?></h3>
                <div class="action-buttons">
                    <button class="button button-primary" onclick="generateContent()">
                        <?php _e('Generate Content', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button button-secondary" onclick="viewAnalytics()">
                        <?php _e('View Analytics', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button button-secondary" onclick="exportData()">
                        <?php _e('Export Data', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button button-secondary" onclick="scheduleContent()">
                        <?php _e('Schedule Content', 'environmental-social-viral'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Chart data
        const platformData = <?php echo json_encode($analytics_data['platform_performance']); ?>;
        const viralData = <?php echo json_encode($viral_stats['trend_data']); ?>;
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initializePlatformChart(platformData);
            initializeViralTrendsChart(viralData);
        });
        </script>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        $period = $_GET['period'] ?? '30days';
        $analytics_data = $this->analytics->get_analytics_data(array('period' => $period));
        
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Social & Viral Analytics', 'environmental-social-viral'); ?></h1>
            
            <!-- Period Filter -->
            <div class="env-period-filter">
                <label for="period-select"><?php _e('Period:', 'environmental-social-viral'); ?></label>
                <select id="period-select" onchange="changePeriod(this.value)">
                    <option value="24hours" <?php selected($period, '24hours'); ?>><?php _e('Last 24 Hours', 'environmental-social-viral'); ?></option>
                    <option value="7days" <?php selected($period, '7days'); ?>><?php _e('Last 7 Days', 'environmental-social-viral'); ?></option>
                    <option value="30days" <?php selected($period, '30days'); ?>><?php _e('Last 30 Days', 'environmental-social-viral'); ?></option>
                    <option value="90days" <?php selected($period, '90days'); ?>><?php _e('Last 90 Days', 'environmental-social-viral'); ?></option>
                    <option value="1year" <?php selected($period, '1year'); ?>><?php _e('Last Year', 'environmental-social-viral'); ?></option>
                </select>
            </div>
            
            <!-- Analytics Grid -->
            <div class="env-analytics-grid">
                <!-- Shares Analytics -->
                <div class="analytics-section">
                    <h3><?php _e('Sharing Analytics', 'environmental-social-viral'); ?></h3>
                    <div class="analytics-chart">
                        <canvas id="sharesChart"></canvas>
                    </div>
                    <div class="analytics-summary">
                        <div class="summary-item">
                            <strong><?php echo number_format($analytics_data['shares']['total_shares']); ?></strong>
                            <span><?php _e('Total Shares', 'environmental-social-viral'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Platform Breakdown -->
                <div class="analytics-section">
                    <h3><?php _e('Platform Breakdown', 'environmental-social-viral'); ?></h3>
                    <div class="analytics-chart">
                        <canvas id="platformBreakdownChart"></canvas>
                    </div>
                </div>
                
                <!-- Engagement Metrics -->
                <div class="analytics-section">
                    <h3><?php _e('Engagement Metrics', 'environmental-social-viral'); ?></h3>
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $analytics_data['clicks']['click_through_rate']; ?>%</div>
                            <div class="metric-label"><?php _e('Click-through Rate', 'environmental-social-viral'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $analytics_data['conversions']['conversion_rate']; ?>%</div>
                            <div class="metric-label"><?php _e('Conversion Rate', 'environmental-social-viral'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo number_format($analytics_data['user_engagement']['active_users']); ?></div>
                            <div class="metric-label"><?php _e('Active Users', 'environmental-social-viral'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Sharers -->
                <div class="analytics-section">
                    <h3><?php _e('Top Sharers', 'environmental-social-viral'); ?></h3>
                    <div class="top-sharers-list">
                        <?php foreach ($analytics_data['user_engagement']['top_sharers'] as $sharer): ?>
                            <div class="sharer-item">
                                <div class="sharer-info">
                                    <strong><?php echo get_user_meta($sharer->user_id, 'display_name', true); ?></strong>
                                    <span><?php echo number_format($sharer->total_shares); ?> <?php _e('shares', 'environmental-social-viral'); ?></span>
                                </div>
                                <div class="sharer-stats">
                                    <span><?php echo number_format($sharer->total_clicks); ?> <?php _e('clicks', 'environmental-social-viral'); ?></span>
                                    <span><?php echo number_format($sharer->total_conversions); ?> <?php _e('conversions', 'environmental-social-viral'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Export Options -->
            <div class="env-export-section">
                <h3><?php _e('Export Analytics', 'environmental-social-viral'); ?></h3>
                <div class="export-buttons">
                    <button class="button button-secondary" onclick="exportAnalytics('csv')">
                        <?php _e('Export as CSV', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button button-secondary" onclick="exportAnalytics('json')">
                        <?php _e('Export as JSON', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button button-secondary" onclick="exportAnalytics('pdf')">
                        <?php _e('Export as PDF', 'environmental-social-viral'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        const analyticsData = <?php echo json_encode($analytics_data); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeAnalyticsCharts(analyticsData);
        });
        </script>
        <?php
    }
    
    /**
     * Sharing settings page
     */
    public function sharing_settings_page() {
        $settings = get_option('env_social_viral_settings', array());
        
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Sharing Settings', 'environmental-social-viral'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('env_social_viral_settings');
                do_settings_sections('env_social_viral_settings');
                ?>
                
                <!-- Platform Configuration -->
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Enable Sharing Buttons', 'environmental-social-viral'); ?></th>
                            <td>
                                <label for="auto_add_buttons">
                                    <input type="checkbox" id="auto_add_buttons" name="env_social_viral_settings[auto_add_buttons]" value="1" <?php checked(1, $settings['auto_add_buttons'] ?? 1); ?> />
                                    <?php _e('Automatically add sharing buttons to content', 'environmental-social-viral'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Button Position', 'environmental-social-viral'); ?></th>
                            <td>
                                <select name="env_social_viral_settings[sharing_button_position]">
                                    <option value="top" <?php selected($settings['sharing_button_position'] ?? 'bottom', 'top'); ?>><?php _e('Top of content', 'environmental-social-viral'); ?></option>
                                    <option value="bottom" <?php selected($settings['sharing_button_position'] ?? 'bottom', 'bottom'); ?>><?php _e('Bottom of content', 'environmental-social-viral'); ?></option>
                                    <option value="both" <?php selected($settings['sharing_button_position'] ?? 'bottom', 'both'); ?>><?php _e('Both top and bottom', 'environmental-social-viral'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Button Style', 'environmental-social-viral'); ?></th>
                            <td>
                                <select name="env_social_viral_settings[sharing_button_style]">
                                    <option value="modern" <?php selected($settings['sharing_button_style'] ?? 'modern', 'modern'); ?>><?php _e('Modern', 'environmental-social-viral'); ?></option>
                                    <option value="classic" <?php selected($settings['sharing_button_style'] ?? 'modern', 'classic'); ?>><?php _e('Classic', 'environmental-social-viral'); ?></option>
                                    <option value="minimal" <?php selected($settings['sharing_button_style'] ?? 'modern', 'minimal'); ?>><?php _e('Minimal', 'environmental-social-viral'); ?></option>
                                    <option value="rounded" <?php selected($settings['sharing_button_style'] ?? 'modern', 'rounded'); ?>><?php _e('Rounded', 'environmental-social-viral'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Social Platform Settings -->
                <h3><?php _e('Social Platform Configuration', 'environmental-social-viral'); ?></h3>
                <div class="platform-settings">
                    <?php $this->render_platform_settings($settings); ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Viral engine page
     */
    public function viral_engine_page() {
        $viral_stats = $this->viral_engine->get_viral_overview();
        $settings = get_option('env_social_viral_settings', array());
        
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Viral Engine', 'environmental-social-viral'); ?></h1>
            
            <!-- Viral Overview -->
            <div class="viral-overview">
                <div class="viral-stat-card">
                    <h3><?php _e('Current Viral Coefficient', 'environmental-social-viral'); ?></h3>
                    <div class="viral-coefficient-display">
                        <span class="coefficient-value"><?php echo $viral_stats['current_coefficient']; ?></span>
                        <span class="coefficient-grade grade-<?php echo strtolower($viral_stats['grade']); ?>"><?php echo $viral_stats['grade']; ?></span>
                    </div>
                </div>
                
                <div class="viral-stat-card">
                    <h3><?php _e('Trending Content', 'environmental-social-viral'); ?></h3>
                    <div class="trending-count"><?php echo $viral_stats['trending_content_count']; ?></div>
                </div>
                
                <div class="viral-stat-card">
                    <h3><?php _e('Viral Threshold', 'environmental-social-viral'); ?></h3>
                    <div class="threshold-value"><?php echo ($settings['viral_threshold'] ?? 0.3) * 100; ?>%</div>
                </div>
            </div>
            
            <!-- Viral Content List -->
            <div class="viral-content-section">
                <h3><?php _e('Viral Content Analysis', 'environmental-social-viral'); ?></h3>
                <div class="viral-content-list">
                    <?php $viral_content = $this->viral_engine->get_viral_content_list(); ?>
                    <?php foreach ($viral_content as $content): ?>
                        <div class="viral-content-item">
                            <div class="content-info">
                                <h4><?php echo esc_html($content->content_title); ?></h4>
                                <div class="content-meta">
                                    <span class="content-type"><?php echo ucfirst($content->content_type); ?></span>
                                    <span class="viral-score">Viral Score: <?php echo $content->viral_score; ?></span>
                                </div>
                            </div>
                            <div class="viral-metrics">
                                <div class="metric">
                                    <span class="metric-label"><?php _e('Shares', 'environmental-social-viral'); ?></span>
                                    <span class="metric-value"><?php echo number_format($content->total_shares); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label"><?php _e('Viral Coefficient', 'environmental-social-viral'); ?></span>
                                    <span class="metric-value"><?php echo $content->viral_coefficient; ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label"><?php _e('Trending Score', 'environmental-social-viral'); ?></span>
                                    <span class="metric-value"><?php echo $content->trending_score; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Viral Engine Settings -->
            <div class="viral-engine-settings">
                <h3><?php _e('Viral Engine Configuration', 'environmental-social-viral'); ?></h3>
                <form method="post" action="options.php">
                    <?php settings_fields('env_social_viral_settings'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Viral Threshold', 'environmental-social-viral'); ?></th>
                                <td>
                                    <input type="number" name="env_social_viral_settings[viral_threshold]" value="<?php echo $settings['viral_threshold'] ?? 0.3; ?>" step="0.01" min="0" max="1" />
                                    <p class="description"><?php _e('Content with viral coefficient above this threshold is considered viral', 'environmental-social-viral'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Calculation Frequency', 'environmental-social-viral'); ?></th>
                                <td>
                                    <select name="env_social_viral_settings[calculation_frequency]">
                                        <option value="hourly" <?php selected($settings['calculation_frequency'] ?? 'daily', 'hourly'); ?>><?php _e('Hourly', 'environmental-social-viral'); ?></option>
                                        <option value="daily" <?php selected($settings['calculation_frequency'] ?? 'daily', 'daily'); ?>><?php _e('Daily', 'environmental-social-viral'); ?></option>
                                        <option value="weekly" <?php selected($settings['calculation_frequency'] ?? 'daily', 'weekly'); ?>><?php _e('Weekly', 'environmental-social-viral'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Enable Auto-Boost', 'environmental-social-viral'); ?></th>
                                <td>
                                    <label for="enable_auto_boost">
                                        <input type="checkbox" id="enable_auto_boost" name="env_social_viral_settings[enable_auto_boost]" value="1" <?php checked(1, $settings['enable_auto_boost'] ?? 1); ?> />
                                        <?php _e('Automatically boost viral content visibility', 'environmental-social-viral'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Referrals page
     */
    public function referrals_page() {
        $referral_stats = $this->referral_system->get_referral_overview();
        $settings = get_option('env_social_viral_settings', array());
        
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Referral System', 'environmental-social-viral'); ?></h1>
            
            <!-- Referral Overview -->
            <div class="referral-overview">
                <div class="referral-stat-card">
                    <h3><?php _e('Active Referrals', 'environmental-social-viral'); ?></h3>
                    <div class="stat-number"><?php echo number_format($referral_stats['active_referrals']); ?></div>
                </div>
                
                <div class="referral-stat-card">
                    <h3><?php _e('Total Conversions', 'environmental-social-viral'); ?></h3>
                    <div class="stat-number"><?php echo number_format($referral_stats['total_conversions']); ?></div>
                </div>
                
                <div class="referral-stat-card">
                    <h3><?php _e('Conversion Rate', 'environmental-social-viral'); ?></h3>
                    <div class="stat-number"><?php echo $referral_stats['conversion_rate']; ?>%</div>
                </div>
                
                <div class="referral-stat-card">
                    <h3><?php _e('Total Rewards Paid', 'environmental-social-viral'); ?></h3>
                    <div class="stat-number"><?php echo number_format($referral_stats['total_rewards']); ?></div>
                </div>
            </div>
            
            <!-- Top Referrers -->
            <div class="top-referrers-section">
                <h3><?php _e('Top Referrers', 'environmental-social-viral'); ?></h3>
                <div class="referrers-list">
                    <?php $top_referrers = $this->referral_system->get_top_referrers(); ?>
                    <?php foreach ($top_referrers as $referrer): ?>
                        <div class="referrer-item">
                            <div class="referrer-info">
                                <strong><?php echo get_user_meta($referrer->user_id, 'display_name', true); ?></strong>
                                <span class="referrer-code"><?php echo $referrer->referral_code; ?></span>
                            </div>
                            <div class="referrer-stats">
                                <span><?php echo number_format($referrer->total_visits); ?> <?php _e('visits', 'environmental-social-viral'); ?></span>
                                <span><?php echo number_format($referrer->total_conversions); ?> <?php _e('conversions', 'environmental-social-viral'); ?></span>
                                <span><?php echo number_format($referrer->total_rewards); ?> <?php _e('points earned', 'environmental-social-viral'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Referral Settings -->
            <div class="referral-settings">
                <h3><?php _e('Referral System Settings', 'environmental-social-viral'); ?></h3>
                <form method="post" action="options.php">
                    <?php settings_fields('env_social_viral_settings'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Enable Referral System', 'environmental-social-viral'); ?></th>
                                <td>
                                    <label for="referral_rewards_enabled">
                                        <input type="checkbox" id="referral_rewards_enabled" name="env_social_viral_settings[referral_rewards_enabled]" value="1" <?php checked(1, $settings['referral_rewards_enabled'] ?? 1); ?> />
                                        <?php _e('Enable referral tracking and rewards', 'environmental-social-viral'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Referral Reward Amount', 'environmental-social-viral'); ?></th>
                                <td>
                                    <input type="number" name="env_social_viral_settings[referral_reward_amount]" value="<?php echo $settings['referral_reward_amount'] ?? 10; ?>" min="0" />
                                    <select name="env_social_viral_settings[referral_reward_type]">
                                        <option value="points" <?php selected($settings['referral_reward_type'] ?? 'points', 'points'); ?>><?php _e('Points', 'environmental-social-viral'); ?></option>
                                        <option value="credits" <?php selected($settings['referral_reward_type'] ?? 'points', 'credits'); ?>><?php _e('Credits', 'environmental-social-viral'); ?></option>
                                        <option value="money" <?php selected($settings['referral_reward_type'] ?? 'points', 'money'); ?>><?php _e('Money', 'environmental-social-viral'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Referral Link Expiry', 'environmental-social-viral'); ?></th>
                                <td>
                                    <input type="number" name="env_social_viral_settings[referral_link_expiry]" value="<?php echo $settings['referral_link_expiry'] ?? 30; ?>" min="1" />
                                    <span><?php _e('days', 'environmental-social-viral'); ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Content generator page
     */
    public function content_generator_page() {
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Content Generator', 'environmental-social-viral'); ?></h1>
            
            <!-- Content Generation Form -->
            <div class="content-generator-form">
                <h3><?php _e('Generate Social Media Content', 'environmental-social-viral'); ?></h3>
                <form id="content-generator-form">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Content Type', 'environmental-social-viral'); ?></th>
                                <td>
                                    <select id="content-type" name="content_type">
                                        <option value="mixed"><?php _e('Mixed Content', 'environmental-social-viral'); ?></option>
                                        <option value="tip"><?php _e('Environmental Tip', 'environmental-social-viral'); ?></option>
                                        <option value="fact"><?php _e('Environmental Fact', 'environmental-social-viral'); ?></option>
                                        <option value="challenge"><?php _e('Challenge', 'environmental-social-viral'); ?></option>
                                        <option value="question"><?php _e('Question', 'environmental-social-viral'); ?></option>
                                        <option value="seasonal"><?php _e('Seasonal Content', 'environmental-social-viral'); ?></option>
                                        <option value="trending"><?php _e('Trending Topics', 'environmental-social-viral'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Target Platform', 'environmental-social-viral'); ?></th>
                                <td>
                                    <select id="target-platform" name="platform">
                                        <option value="general"><?php _e('General', 'environmental-social-viral'); ?></option>
                                        <option value="facebook"><?php _e('Facebook', 'environmental-social-viral'); ?></option>
                                        <option value="twitter"><?php _e('Twitter', 'environmental-social-viral'); ?></option>
                                        <option value="linkedin"><?php _e('LinkedIn', 'environmental-social-viral'); ?></option>
                                        <option value="instagram"><?php _e('Instagram', 'environmental-social-viral'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Personalization', 'environmental-social-viral'); ?></th>
                                <td>
                                    <label for="personalized-content">
                                        <input type="checkbox" id="personalized-content" name="personalized" value="1" />
                                        <?php _e('Generate personalized content based on user data', 'environmental-social-viral'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary" onclick="generateContent()">
                            <?php _e('Generate Content', 'environmental-social-viral'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Generated Content Display -->
            <div id="generated-content" class="generated-content-display" style="display: none;">
                <h3><?php _e('Generated Content', 'environmental-social-viral'); ?></h3>
                <div class="content-preview">
                    <div class="content-text"></div>
                    <div class="content-meta"></div>
                    <div class="content-actions">
                        <button class="button button-secondary" onclick="regenerateContent()">
                            <?php _e('Regenerate', 'environmental-social-viral'); ?>
                        </button>
                        <button class="button button-primary" onclick="scheduleContent()">
                            <?php _e('Schedule Post', 'environmental-social-viral'); ?>
                        </button>
                        <button class="button button-secondary" onclick="copyContent()">
                            <?php _e('Copy to Clipboard', 'environmental-social-viral'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Auto-Generation Settings -->
            <div class="auto-generation-settings">
                <h3><?php _e('Auto-Generation Settings', 'environmental-social-viral'); ?></h3>
                <form method="post" action="options.php">
                    <?php settings_fields('env_social_viral_settings'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Enable Auto-Generation', 'environmental-social-viral'); ?></th>
                                <td>
                                    <label for="auto_generate_content">
                                        <input type="checkbox" id="auto_generate_content" name="env_social_viral_settings[auto_generate_content]" value="1" <?php checked(1, get_option('env_social_viral_settings')['auto_generate_content'] ?? 0); ?> />
                                        <?php _e('Automatically generate daily social media content', 'environmental-social-viral'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Generation Schedule', 'environmental-social-viral'); ?></th>
                                <td>
                                    <select name="env_social_viral_settings[auto_generation_time]">
                                        <option value="08:00"><?php _e('8:00 AM', 'environmental-social-viral'); ?></option>
                                        <option value="12:00"><?php _e('12:00 PM', 'environmental-social-viral'); ?></option>
                                        <option value="18:00"><?php _e('6:00 PM', 'environmental-social-viral'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap env-social-viral-admin">
            <h1><?php _e('Social & Viral Settings', 'environmental-social-viral'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('env_social_viral_settings');
                do_settings_sections('env_social_viral_settings');
                submit_button();
                ?>
            </form>
            
            <!-- System Information -->
            <div class="system-info">
                <h3><?php _e('System Information', 'environmental-social-viral'); ?></h3>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Plugin Version', 'environmental-social-viral'); ?>:</strong></td>
                            <td><?php echo ENV_SOCIAL_VIRAL_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Database Version', 'environmental-social-viral'); ?>:</strong></td>
                            <td><?php echo get_option('env_social_viral_db_version', '1.0.0'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Total Shares Tracked', 'environmental-social-viral'); ?>:</strong></td>
                            <td><?php echo number_format($this->get_total_shares()); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Active Referrals', 'environmental-social-viral'); ?>:</strong></td>
                            <td><?php echo number_format($this->get_active_referrals_count()); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    // ...existing code continues...
    
    /**
     * Render platform settings
     */
    private function render_platform_settings($settings) {
        $platforms = array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'email' => 'Email'
        );
        
        foreach ($platforms as $key => $name) {
            $platform_settings = $settings['social_platforms'][$key] ?? array();
            ?>
            <div class="platform-setting">
                <h4><?php echo $name; ?></h4>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Enable', 'environmental-social-viral'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="env_social_viral_settings[social_platforms][<?php echo $key; ?>][enabled]" value="1" <?php checked(1, $platform_settings['enabled'] ?? 1); ?> />
                                    <?php printf(__('Enable %s sharing', 'environmental-social-viral'), $name); ?>
                                </label>
                            </td>
                        </tr>
                        <?php if (in_array($key, array('facebook', 'twitter', 'linkedin'))): ?>
                        <tr>
                            <th scope="row"><?php _e('API Configuration', 'environmental-social-viral'); ?></th>
                            <td>
                                <input type="text" name="env_social_viral_settings[social_platforms][<?php echo $key; ?>][app_id]" value="<?php echo esc_attr($platform_settings['app_id'] ?? ''); ?>" placeholder="<?php _e('App ID', 'environmental-social-viral'); ?>" />
                                <input type="password" name="env_social_viral_settings[social_platforms][<?php echo $key; ?>][app_secret]" value="<?php echo esc_attr($platform_settings['app_secret'] ?? ''); ?>" placeholder="<?php _e('App Secret', 'environmental-social-viral'); ?>" />
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }
    
    /**
     * Get recent activity
     */
    private function get_recent_activity() {
        global $wpdb;
        
        $activities = array();
        
        // Recent shares
        $recent_shares = $wpdb->get_results("
            SELECT content_title, platform, share_time 
            FROM {$wpdb->prefix}env_social_shares 
            ORDER BY share_time DESC 
            LIMIT 10
        ");
        
        foreach ($recent_shares as $share) {
            $activities[] = array(
                'icon' => 'fas fa-share-alt',
                'text' => sprintf(__('Content "%s" was shared on %s', 'environmental-social-viral'), 
                    esc_html($share->content_title), ucfirst($share->platform)),
                'time' => human_time_diff(strtotime($share->share_time)) . ' ago'
            );
        }
        
        // Sort by time
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 5);
    }
    
    /**
     * Get total shares count
     */
    private function get_total_shares() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_social_shares");
    }
    
    /**
     * Get active referrals count
     */
    private function get_active_referrals_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_referrals WHERE status = 'active'");
    }
    
    /**
     * Section callbacks
     */
    public function platforms_section_callback() {
        echo '<p>' . __('Configure social media platform settings and API credentials.', 'environmental-social-viral') . '</p>';
    }
    
    public function viral_engine_section_callback() {
        echo '<p>' . __('Configure viral coefficient calculation and trending content detection.', 'environmental-social-viral') . '</p>';
    }
    
    public function referral_section_callback() {
        echo '<p>' . __('Configure referral system settings and reward parameters.', 'environmental-social-viral') . '</p>';
    }
    
    public function analytics_section_callback() {
        echo '<p>' . __('Configure analytics tracking and data retention settings.', 'environmental-social-viral') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize each setting appropriately
        if (isset($input['auto_add_buttons'])) {
            $sanitized['auto_add_buttons'] = (bool) $input['auto_add_buttons'];
        }
        
        if (isset($input['sharing_button_position'])) {
            $sanitized['sharing_button_position'] = sanitize_text_field($input['sharing_button_position']);
        }
        
        if (isset($input['sharing_button_style'])) {
            $sanitized['sharing_button_style'] = sanitize_text_field($input['sharing_button_style']);
        }
        
        if (isset($input['viral_threshold'])) {
            $sanitized['viral_threshold'] = floatval($input['viral_threshold']);
        }
        
        if (isset($input['referral_reward_amount'])) {
            $sanitized['referral_reward_amount'] = intval($input['referral_reward_amount']);
        }
        
        if (isset($input['social_platforms'])) {
            $sanitized['social_platforms'] = $this->sanitize_platform_settings($input['social_platforms']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize platform settings
     */
    private function sanitize_platform_settings($platforms) {
        $sanitized_platforms = array();
        
        foreach ($platforms as $platform_key => $platform_data) {
            $sanitized_platforms[sanitize_key($platform_key)] = array(
                'enabled' => isset($platform_data['enabled']) ? (bool) $platform_data['enabled'] : false,
                'app_id' => isset($platform_data['app_id']) ? sanitize_text_field($platform_data['app_id']) : '',
                'app_secret' => isset($platform_data['app_secret']) ? sanitize_text_field($platform_data['app_secret']) : ''
            );
        }
        
        return $sanitized_platforms;
    }
    
    /**
     * AJAX Handlers
     */
    public function save_settings_ajax() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        $settings = $_POST['settings'] ?? array();
        $sanitized_settings = $this->sanitize_settings($settings);
        
        update_option('env_social_viral_settings', $sanitized_settings);
        
        wp_send_json_success(__('Settings saved successfully', 'environmental-social-viral'));
    }
    
    public function test_api_connection() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        $api_credentials = $_POST['credentials'] ?? array();
        
        // Test API connection
        $test_result = $this->sharing_manager->test_platform_api($platform, $api_credentials);
        
        if ($test_result) {
            wp_send_json_success(__('API connection successful', 'environmental-social-viral'));
        } else {
            wp_send_json_error(__('API connection failed', 'environmental-social-viral'));
        }
    }
    
    public function reset_analytics_data() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        global $wpdb;
        
        // Reset analytics tables
        $tables = array(
            $wpdb->prefix . 'env_social_shares',
            $wpdb->prefix . 'env_share_analytics',
            $wpdb->prefix . 'env_viral_metrics'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
        
        wp_send_json_success(__('Analytics data reset successfully', 'environmental-social-viral'));
    }
    
    public function export_analytics_data() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        
        $this->analytics->export_analytics_data();
    }
}
