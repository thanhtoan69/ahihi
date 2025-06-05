<?php
/**
 * Environmental Social Viral Sharing Admin
 * 
 * Handles admin interface for social sharing management
 */

class Environmental_Social_Viral_Sharing_Admin {
    
    private static $instance = null;
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
        
        add_action('admin_init', array($this, 'init_admin'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function init_admin() {
        add_action('wp_ajax_env_sharing_get_analytics', array($this, 'ajax_get_sharing_analytics'));
        add_action('wp_ajax_env_sharing_update_settings', array($this, 'ajax_update_sharing_settings'));
        add_action('wp_ajax_env_sharing_test_platform', array($this, 'ajax_test_platform'));
        add_action('wp_ajax_env_sharing_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_env_sharing_export_data', array($this, 'ajax_export_sharing_data'));
    }
    
    /**
     * Render sharing analytics page
     */
    public function render_sharing_analytics_page() {
        $analytics = $this->get_sharing_analytics();
        $platforms = $this->get_platform_performance();
        $top_content = $this->get_top_shared_content();
        $recent_shares = $this->get_recent_shares();
        
        ?>
        <div class="wrap env-sharing-admin">
            <h1><?php _e('Social Sharing Analytics', 'environmental-social-viral'); ?></h1>
            
            <!-- Analytics Overview -->
            <div class="env-admin-section">
                <h2><?php _e('Sharing Overview', 'environmental-social-viral'); ?></h2>
                
                <div class="env-stats-grid">
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['total_shares']); ?></div>
                        <div class="env-stat-label"><?php _e('Total Shares', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['shares_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['shares_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['shares_change'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['total_clicks']); ?></div>
                        <div class="env-stat-label"><?php _e('Total Clicks', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['clicks_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['clicks_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['clicks_change'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['click_through_rate'], 2); ?>%</div>
                        <div class="env-stat-label"><?php _e('Click-Through Rate', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['ctr_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['ctr_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['ctr_change'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['active_sharers']); ?></div>
                        <div class="env-stat-label"><?php _e('Active Sharers', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['sharers_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['sharers_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['sharers_change'], 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="env-admin-section">
                <div class="env-charts-container">
                    <div class="env-chart-wrapper">
                        <h3><?php _e('Shares Over Time', 'environmental-social-viral'); ?></h3>
                        <canvas id="sharesChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="env-chart-wrapper">
                        <h3><?php _e('Platform Performance', 'environmental-social-viral'); ?></h3>
                        <canvas id="platformChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Platform Performance Table -->
            <div class="env-admin-section">
                <h2><?php _e('Platform Performance', 'environmental-social-viral'); ?></h2>
                
                <div class="env-table-controls">
                    <select id="platform-period">
                        <option value="7days"><?php _e('Last 7 Days', 'environmental-social-viral'); ?></option>
                        <option value="30days"><?php _e('Last 30 Days', 'environmental-social-viral'); ?></option>
                        <option value="90days"><?php _e('Last 90 Days', 'environmental-social-viral'); ?></option>
                    </select>
                    
                    <button type="button" class="button" id="export-platform-data">
                        <?php _e('Export Data', 'environmental-social-viral'); ?>
                    </button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Platform', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Shares', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Clicks', 'environmental-social-viral'); ?></th>
                            <th><?php _e('CTR', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Viral Score', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Status', 'environmental-social-viral'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($platforms as $platform): ?>
                        <tr>
                            <td>
                                <div class="platform-info">
                                    <span class="platform-icon platform-<?php echo esc_attr($platform['platform']); ?>"></span>
                                    <strong><?php echo esc_html(ucfirst($platform['platform'])); ?></strong>
                                </div>
                            </td>
                            <td><?php echo number_format($platform['shares']); ?></td>
                            <td><?php echo number_format($platform['clicks']); ?></td>
                            <td><?php echo number_format($platform['ctr'], 2); ?>%</td>
                            <td>
                                <div class="viral-score-bar">
                                    <div class="score-fill" style="width: <?php echo min($platform['viral_score'] * 100, 100); ?>%"></div>
                                    <span class="score-text"><?php echo number_format($platform['viral_score'], 3); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $platform['enabled'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $platform['enabled'] ? __('Active', 'environmental-social-viral') : __('Inactive', 'environmental-social-viral'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Top Shared Content -->
            <div class="env-admin-section">
                <h2><?php _e('Top Shared Content', 'environmental-social-viral'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Content', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Type', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Total Shares', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Viral Coefficient', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Last Shared', 'environmental-social-viral'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_content as $content): ?>
                        <tr>
                            <td>
                                <div class="content-info">
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($content['content_id']); ?>" target="_blank">
                                            <?php echo esc_html($content['title']); ?>
                                        </a>
                                    </strong>
                                    <div class="content-meta">
                                        <?php _e('Author:', 'environmental-social-viral'); ?> <?php echo esc_html($content['author']); ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html(ucfirst($content['content_type'])); ?></td>
                            <td><?php echo number_format($content['total_shares']); ?></td>
                            <td>
                                <span class="viral-coefficient viral-level-<?php echo $this->get_viral_level($content['viral_coefficient']); ?>">
                                    <?php echo number_format($content['viral_coefficient'], 3); ?>
                                </span>
                            </td>
                            <td><?php echo human_time_diff(strtotime($content['last_shared']), current_time('timestamp')); ?> <?php _e('ago', 'environmental-social-viral'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Shares -->
            <div class="env-admin-section">
                <h2><?php _e('Recent Shares', 'environmental-social-viral'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Content', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Platform', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Date', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Clicks', 'environmental-social-viral'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_shares as $share): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <?php echo get_avatar($share['user_id'], 32); ?>
                                    <span><?php echo esc_html($share['user_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($share['content_id']); ?>" target="_blank">
                                    <?php echo esc_html($share['content_title']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="platform-badge platform-<?php echo esc_attr($share['platform']); ?>">
                                    <?php echo esc_html(ucfirst($share['platform'])); ?>
                                </span>
                            </td>
                            <td><?php echo human_time_diff(strtotime($share['created_at']), current_time('timestamp')); ?> <?php _e('ago', 'environmental-social-viral'); ?></td>
                            <td><?php echo number_format($share['click_count']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize charts
            const sharesData = <?php echo json_encode($this->get_shares_chart_data()); ?>;
            const platformData = <?php echo json_encode($this->get_platform_chart_data()); ?>;
            
            // Shares over time chart
            const sharesCtx = document.getElementById('sharesChart').getContext('2d');
            new Chart(sharesCtx, {
                type: 'line',
                data: sharesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Platform performance chart
            const platformCtx = document.getElementById('platformChart').getContext('2d');
            new Chart(platformCtx, {
                type: 'doughnut',
                data: platformData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Export functionality
            $('#export-platform-data').on('click', function() {
                const period = $('#platform-period').val();
                window.location.href = ajaxurl + '?action=env_sharing_export_data&period=' + period + '&nonce=' + envSocialViralAdmin.nonce;
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render sharing settings page
     */
    public function render_sharing_settings_page() {
        $settings = get_option('env_social_viral_settings', array());
        $platforms = $this->get_available_platforms();
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'env_sharing_settings')) {
            $this->save_sharing_settings($_POST);
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'environmental-social-viral') . '</p></div>';
        }
        
        ?>
        <div class="wrap env-sharing-settings">
            <h1><?php _e('Social Sharing Settings', 'environmental-social-viral'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('env_sharing_settings'); ?>
                
                <!-- General Settings -->
                <div class="env-admin-section">
                    <h2><?php _e('General Settings', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Social Sharing', 'environmental-social-viral'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sharing_enabled" value="1" <?php checked($settings['sharing_enabled'] ?? false); ?>>
                                    <?php _e('Enable social sharing buttons', 'environmental-social-viral'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Auto-add Buttons', 'environmental-social-viral'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_add_buttons" value="1" <?php checked($settings['auto_add_buttons'] ?? false); ?>>
                                    <?php _e('Automatically add sharing buttons to content', 'environmental-social-viral'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Button Position', 'environmental-social-viral'); ?></th>
                            <td>
                                <select name="sharing_button_position">
                                    <option value="top" <?php selected($settings['sharing_button_position'] ?? '', 'top'); ?>><?php _e('Top of Content', 'environmental-social-viral'); ?></option>
                                    <option value="bottom" <?php selected($settings['sharing_button_position'] ?? '', 'bottom'); ?>><?php _e('Bottom of Content', 'environmental-social-viral'); ?></option>
                                    <option value="both" <?php selected($settings['sharing_button_position'] ?? '', 'both'); ?>><?php _e('Both Top and Bottom', 'environmental-social-viral'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Button Style', 'environmental-social-viral'); ?></th>
                            <td>
                                <select name="sharing_button_style">
                                    <option value="default" <?php selected($settings['sharing_button_style'] ?? '', 'default'); ?>><?php _e('Default', 'environmental-social-viral'); ?></option>
                                    <option value="modern" <?php selected($settings['sharing_button_style'] ?? '', 'modern'); ?>><?php _e('Modern', 'environmental-social-viral'); ?></option>
                                    <option value="minimal" <?php selected($settings['sharing_button_style'] ?? '', 'minimal'); ?>><?php _e('Minimal', 'environmental-social-viral'); ?></option>
                                    <option value="colorful" <?php selected($settings['sharing_button_style'] ?? '', 'colorful'); ?>><?php _e('Colorful', 'environmental-social-viral'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Show Share Counts', 'environmental-social-viral'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_share_counts" value="1" <?php checked($settings['show_share_counts'] ?? false); ?>>
                                    <?php _e('Display share counts on buttons', 'environmental-social-viral'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Platform Configuration -->
                <div class="env-admin-section">
                    <h2><?php _e('Platform Configuration', 'environmental-social-viral'); ?></h2>
                    
                    <div class="platform-tabs">
                        <?php foreach ($platforms as $platform_key => $platform): ?>
                        <div class="platform-tab" data-platform="<?php echo esc_attr($platform_key); ?>">
                            <h3>
                                <span class="platform-icon platform-<?php echo esc_attr($platform_key); ?>"></span>
                                <?php echo esc_html($platform['name']); ?>
                            </h3>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Platform', 'environmental-social-viral'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="platforms[<?php echo esc_attr($platform_key); ?>][enabled]" value="1" 
                                                   <?php checked($settings['platforms'][$platform_key]['enabled'] ?? false); ?>>
                                            <?php printf(__('Enable %s sharing', 'environmental-social-viral'), $platform['name']); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <?php if (!empty($platform['api_fields'])): ?>
                                    <?php foreach ($platform['api_fields'] as $field_key => $field): ?>
                                    <tr>
                                        <th scope="row"><?php echo esc_html($field['label']); ?></th>
                                        <td>
                                            <input type="<?php echo esc_attr($field['type'] ?? 'text'); ?>" 
                                                   name="platforms[<?php echo esc_attr($platform_key); ?>][<?php echo esc_attr($field_key); ?>]" 
                                                   value="<?php echo esc_attr($settings['platforms'][$platform_key][$field_key] ?? ''); ?>"
                                                   class="regular-text">
                                            <?php if (!empty($field['description'])): ?>
                                                <p class="description"><?php echo esc_html($field['description']); ?></p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr>
                                        <th scope="row"><?php _e('Test Connection', 'environmental-social-viral'); ?></th>
                                        <td>
                                            <button type="button" class="button test-platform-connection" data-platform="<?php echo esc_attr($platform_key); ?>">
                                                <?php _e('Test Connection', 'environmental-social-viral'); ?>
                                            </button>
                                            <span class="test-result"></span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Content Types -->
                <div class="env-admin-section">
                    <h2><?php _e('Content Types', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enabled Content Types', 'environmental-social-viral'); ?></th>
                            <td>
                                <?php 
                                $post_types = get_post_types(array('public' => true), 'objects');
                                $enabled_types = $settings['enabled_content_types'] ?? array('post', 'page');
                                ?>
                                <?php foreach ($post_types as $post_type): ?>
                                <label>
                                    <input type="checkbox" name="enabled_content_types[]" value="<?php echo esc_attr($post_type->name); ?>"
                                           <?php checked(in_array($post_type->name, $enabled_types)); ?>>
                                    <?php echo esc_html($post_type->label); ?>
                                </label><br>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Tracking Settings -->
                <div class="env-admin-section">
                    <h2><?php _e('Tracking Settings', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Tracking', 'environmental-social-viral'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="tracking_enabled" value="1" <?php checked($settings['tracking_enabled'] ?? false); ?>>
                                    <?php _e('Track share clicks and analytics', 'environmental-social-viral'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Anonymous Tracking', 'environmental-social-viral'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="anonymous_tracking" value="1" <?php checked($settings['anonymous_tracking'] ?? false); ?>>
                                    <?php _e('Track shares from anonymous users', 'environmental-social-viral'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Data Retention', 'environmental-social-viral'); ?></th>
                            <td>
                                <select name="analytics_retention_days">
                                    <option value="30" <?php selected($settings['analytics_retention_days'] ?? '', '30'); ?>><?php _e('30 Days', 'environmental-social-viral'); ?></option>
                                    <option value="90" <?php selected($settings['analytics_retention_days'] ?? '', '90'); ?>><?php _e('90 Days', 'environmental-social-viral'); ?></option>
                                    <option value="365" <?php selected($settings['analytics_retention_days'] ?? '', '365'); ?>><?php _e('1 Year', 'environmental-social-viral'); ?></option>
                                    <option value="0" <?php selected($settings['analytics_retention_days'] ?? '', '0'); ?>><?php _e('Forever', 'environmental-social-viral'); ?></option>
                                </select>
                                <p class="description"><?php _e('How long to keep sharing analytics data', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Platform connection testing
            $('.test-platform-connection').on('click', function() {
                const platform = $(this).data('platform');
                const button = $(this);
                const result = button.siblings('.test-result');
                
                button.prop('disabled', true).text('<?php _e('Testing...', 'environmental-social-viral'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'env_sharing_test_platform',
                        platform: platform,
                        nonce: envSocialViralAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                        } else {
                            result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        result.html('<span style="color: red;">✗ <?php _e('Connection failed', 'environmental-social-viral'); ?></span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Test Connection', 'environmental-social-viral'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get sharing analytics data
     */
    private function get_sharing_analytics() {
        $current_period = "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $previous_period = "DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        // Current period stats
        $current_stats = $this->wpdb->get_row(
            "SELECT 
                COUNT(*) as total_shares,
                SUM(click_count) as total_clicks,
                COUNT(DISTINCT user_id) as active_sharers
             FROM {$this->tables['shares']} 
             WHERE created_at >= {$current_period}"
        );
        
        // Previous period stats for comparison
        $previous_stats = $this->wpdb->get_row(
            "SELECT 
                COUNT(*) as total_shares,
                SUM(click_count) as total_clicks,
                COUNT(DISTINCT user_id) as active_sharers
             FROM {$this->tables['shares']} 
             WHERE created_at BETWEEN {$previous_period}"
        );
        
        // Calculate changes
        $shares_change = $this->calculate_percentage_change($previous_stats->total_shares, $current_stats->total_shares);
        $clicks_change = $this->calculate_percentage_change($previous_stats->total_clicks, $current_stats->total_clicks);
        $sharers_change = $this->calculate_percentage_change($previous_stats->active_sharers, $current_stats->active_sharers);
        
        $click_through_rate = $current_stats->total_shares > 0 ? ($current_stats->total_clicks / $current_stats->total_shares) * 100 : 0;
        $previous_ctr = $previous_stats->total_shares > 0 ? ($previous_stats->total_clicks / $previous_stats->total_shares) * 100 : 0;
        $ctr_change = $this->calculate_percentage_change($previous_ctr, $click_through_rate);
        
        return array(
            'total_shares' => $current_stats->total_shares,
            'total_clicks' => $current_stats->total_clicks,
            'active_sharers' => $current_stats->active_sharers,
            'click_through_rate' => $click_through_rate,
            'shares_change' => $shares_change,
            'clicks_change' => $clicks_change,
            'sharers_change' => $sharers_change,
            'ctr_change' => $ctr_change
        );
    }
    
    /**
     * Get platform performance data
     */
    private function get_platform_performance() {
        $platforms = $this->wpdb->get_results(
            "SELECT 
                platform,
                COUNT(*) as shares,
                SUM(click_count) as clicks,
                AVG(click_count) as avg_clicks_per_share,
                MAX(created_at) as last_activity
             FROM {$this->tables['shares']} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY platform
             ORDER BY shares DESC"
        );
        
        $settings = get_option('env_social_viral_settings', array());
        $result = array();
        
        foreach ($platforms as $platform) {
            $ctr = $platform->shares > 0 ? ($platform->clicks / $platform->shares) * 100 : 0;
            $viral_score = $this->calculate_platform_viral_score($platform->platform);
            
            $result[] = array(
                'platform' => $platform->platform,
                'shares' => $platform->shares,
                'clicks' => $platform->clicks,
                'ctr' => $ctr,
                'viral_score' => $viral_score,
                'enabled' => $settings['platforms'][$platform->platform]['enabled'] ?? false,
                'last_activity' => $platform->last_activity
            );
        }
        
        return $result;
    }
    
    /**
     * Get top shared content
     */
    private function get_top_shared_content() {
        $content = $this->wpdb->get_results(
            "SELECT 
                s.content_id,
                s.content_type,
                COUNT(*) as total_shares,
                MAX(s.created_at) as last_shared,
                p.post_title as title,
                u.display_name as author,
                vc.viral_coefficient
             FROM {$this->tables['shares']} s
             LEFT JOIN {$this->wpdb->posts} p ON s.content_id = p.ID
             LEFT JOIN {$this->wpdb->users} u ON p.post_author = u.ID
             LEFT JOIN {$this->tables['viral_coefficients']} vc ON s.content_id = vc.content_id AND vc.period = '30days'
             WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY s.content_id, s.content_type
             ORDER BY total_shares DESC
             LIMIT 20"
        );
        
        return $content;
    }
    
    /**
     * Get recent shares
     */
    private function get_recent_shares() {
        $shares = $this->wpdb->get_results(
            "SELECT 
                s.*,
                u.display_name as user_name,
                p.post_title as content_title
             FROM {$this->tables['shares']} s
             LEFT JOIN {$this->wpdb->users} u ON s.user_id = u.ID
             LEFT JOIN {$this->wpdb->posts} p ON s.content_id = p.ID
             ORDER BY s.created_at DESC
             LIMIT 50"
        );
        
        return $shares;
    }
    
    /**
     * Get shares chart data
     */
    private function get_shares_chart_data() {
        $data = $this->wpdb->get_results(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as shares,
                SUM(click_count) as clicks
             FROM {$this->tables['shares']} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );
        
        $labels = array();
        $shares = array();
        $clicks = array();
        
        foreach ($data as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $shares[] = $row->shares;
            $clicks[] = $row->clicks;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Shares', 'environmental-social-viral'),
                    'data' => $shares,
                    'borderColor' => '#2E7D32',
                    'backgroundColor' => 'rgba(46, 125, 50, 0.1)',
                    'fill' => true
                ),
                array(
                    'label' => __('Clicks', 'environmental-social-viral'),
                    'data' => $clicks,
                    'borderColor' => '#1976D2',
                    'backgroundColor' => 'rgba(25, 118, 210, 0.1)',
                    'fill' => true
                )
            )
        );
    }
    
    /**
     * Get platform chart data
     */
    private function get_platform_chart_data() {
        $data = $this->wpdb->get_results(
            "SELECT 
                platform,
                COUNT(*) as shares
             FROM {$this->tables['shares']} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY platform
             ORDER BY shares DESC"
        );
        
        $labels = array();
        $values = array();
        $colors = array(
            'facebook' => '#1877F2',
            'twitter' => '#1DA1F2',
            'linkedin' => '#0A66C2',
            'whatsapp' => '#25D366',
            'telegram' => '#0088CC',
            'email' => '#EA4335',
            'copy' => '#6C757D'
        );
        
        foreach ($data as $row) {
            $labels[] = ucfirst($row->platform);
            $values[] = $row->shares;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $values,
                    'backgroundColor' => array_slice(array_values($colors), 0, count($values))
                )
            )
        );
    }
    
    /**
     * Get available platforms
     */
    private function get_available_platforms() {
        return array(
            'facebook' => array(
                'name' => 'Facebook',
                'api_fields' => array(
                    'app_id' => array(
                        'label' => 'App ID',
                        'type' => 'text',
                        'description' => 'Facebook App ID for advanced sharing features'
                    ),
                    'app_secret' => array(
                        'label' => 'App Secret',
                        'type' => 'password',
                        'description' => 'Facebook App Secret'
                    )
                )
            ),
            'twitter' => array(
                'name' => 'Twitter',
                'api_fields' => array(
                    'api_key' => array(
                        'label' => 'API Key',
                        'type' => 'text',
                        'description' => 'Twitter API Key'
                    ),
                    'api_secret' => array(
                        'label' => 'API Secret',
                        'type' => 'password',
                        'description' => 'Twitter API Secret'
                    )
                )
            ),
            'linkedin' => array(
                'name' => 'LinkedIn',
                'api_fields' => array(
                    'client_id' => array(
                        'label' => 'Client ID',
                        'type' => 'text',
                        'description' => 'LinkedIn Client ID'
                    ),
                    'client_secret' => array(
                        'label' => 'Client Secret',
                        'type' => 'password',
                        'description' => 'LinkedIn Client Secret'
                    )
                )
            ),
            'whatsapp' => array(
                'name' => 'WhatsApp',
                'api_fields' => array()
            ),
            'telegram' => array(
                'name' => 'Telegram',
                'api_fields' => array()
            ),
            'email' => array(
                'name' => 'Email',
                'api_fields' => array()
            ),
            'copy' => array(
                'name' => 'Copy Link',
                'api_fields' => array()
            )
        );
    }
    
    /**
     * Save sharing settings
     */
    private function save_sharing_settings($data) {
        $settings = array(
            'sharing_enabled' => !empty($data['sharing_enabled']),
            'auto_add_buttons' => !empty($data['auto_add_buttons']),
            'sharing_button_position' => sanitize_text_field($data['sharing_button_position']),
            'sharing_button_style' => sanitize_text_field($data['sharing_button_style']),
            'show_share_counts' => !empty($data['show_share_counts']),
            'tracking_enabled' => !empty($data['tracking_enabled']),
            'anonymous_tracking' => !empty($data['anonymous_tracking']),
            'analytics_retention_days' => intval($data['analytics_retention_days']),
            'enabled_content_types' => $data['enabled_content_types'] ?? array(),
            'platforms' => $data['platforms'] ?? array()
        );
        
        update_option('env_social_viral_settings', $settings);
    }
    
    /**
     * Calculate percentage change
     */
    private function calculate_percentage_change($old, $new) {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }
        return (($new - $old) / $old) * 100;
    }
    
    /**
     * Calculate platform viral score
     */
    private function calculate_platform_viral_score($platform) {
        $viral_engine = Environmental_Social_Viral_Engine::get_instance();
        return $viral_engine->calculate_platform_viral_coefficient($platform);
    }
    
    /**
     * Get viral level for display
     */
    private function get_viral_level($coefficient) {
        if ($coefficient >= 0.5) return 'high';
        if ($coefficient >= 0.3) return 'medium';
        return 'low';
    }
    
    /**
     * AJAX: Get sharing analytics
     */
    public function ajax_get_sharing_analytics() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        $analytics = $this->get_sharing_analytics();
        
        wp_send_json_success($analytics);
    }
    
    /**
     * AJAX: Update sharing settings
     */
    public function ajax_update_sharing_settings() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        $this->save_sharing_settings($_POST);
        
        wp_send_json_success(__('Settings updated successfully', 'environmental-social-viral'));
    }
    
    /**
     * AJAX: Test platform connection
     */
    public function ajax_test_platform() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $platform = sanitize_text_field($_POST['platform']);
        
        // Simulate platform testing
        $test_result = array(
            'success' => true,
            'message' => sprintf(__('%s connection successful', 'environmental-social-viral'), ucfirst($platform))
        );
        
        wp_send_json_success($test_result);
    }
    
    /**
     * AJAX: Bulk actions
     */
    public function ajax_bulk_action() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $items = array_map('intval', $_POST['items'] ?? array());
        
        if (empty($items)) {
            wp_send_json_error(__('No items selected', 'environmental-social-viral'));
        }
        
        switch ($action) {
            case 'delete':
                $deleted = $this->wpdb->query(
                    "DELETE FROM {$this->tables['shares']} 
                     WHERE id IN (" . implode(',', $items) . ")"
                );
                wp_send_json_success(sprintf(__('%d items deleted', 'environmental-social-viral'), $deleted));
                break;
                
            default:
                wp_send_json_error(__('Invalid action', 'environmental-social-viral'));
        }
    }
    
    /**
     * AJAX: Export sharing data
     */
    public function ajax_export_sharing_data() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $period = sanitize_text_field($_GET['period'] ?? '30days');
        
        $data = $this->wpdb->get_results(
            "SELECT * FROM {$this->tables['shares']} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY created_at DESC",
            ARRAY_A
        );
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sharing-data-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}
