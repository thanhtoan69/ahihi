<?php
/**
 * Environmental Social Viral Dashboard Admin
 * 
 * Handles admin interface for viral analytics and dashboard
 */

class Environmental_Social_Viral_Dashboard_Admin {
    
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
        add_action('wp_ajax_env_viral_get_content_metrics', array($this, 'ajax_get_content_metrics'));
        add_action('wp_ajax_env_viral_update_coefficients', array($this, 'ajax_update_coefficients'));
        add_action('wp_ajax_env_viral_get_trending', array($this, 'ajax_get_trending_content'));
        add_action('wp_ajax_env_viral_export_metrics', array($this, 'ajax_export_viral_metrics'));
        add_action('wp_ajax_env_viral_reset_data', array($this, 'ajax_reset_viral_data'));
    }
    
    /**
     * Render viral dashboard page
     */
    public function render_viral_dashboard_page() {
        $metrics = $this->get_viral_metrics_summary();
        $trending_content = $this->get_trending_content();
        $viral_leaders = $this->get_viral_content_leaders();
        $platform_performance = $this->get_platform_viral_performance();
        
        ?>
        <div class="wrap env-viral-dashboard">
            <h1><?php _e('Viral Analytics Dashboard', 'environmental-social-viral'); ?></h1>
            
            <!-- Viral Metrics Overview -->
            <div class="env-admin-section">
                <h2><?php _e('Viral Performance Overview', 'environmental-social-viral'); ?></h2>
                
                <div class="env-stats-grid">
                    <div class="env-stat-card viral-metric">
                        <div class="env-stat-number"><?php echo number_format($metrics['avg_viral_coefficient'], 3); ?></div>
                        <div class="env-stat-label"><?php _e('Average Viral Coefficient', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $metrics['coefficient_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $metrics['coefficient_trend'] >= 0 ? '+' : ''; ?><?php echo number_format($metrics['coefficient_trend'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card viral-metric">
                        <div class="env-stat-number"><?php echo number_format($metrics['viral_content_count']); ?></div>
                        <div class="env-stat-label"><?php _e('Viral Content Items', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-sub"><?php _e('Coefficient > 0.3', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-stat-card viral-metric">
                        <div class="env-stat-number"><?php echo number_format($metrics['total_viral_shares']); ?></div>
                        <div class="env-stat-label"><?php _e('Total Viral Shares', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $metrics['shares_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $metrics['shares_trend'] >= 0 ? '+' : ''; ?><?php echo number_format($metrics['shares_trend'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card viral-metric">
                        <div class="env-stat-number"><?php echo number_format($metrics['viral_reach']); ?></div>
                        <div class="env-stat-label"><?php _e('Estimated Viral Reach', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-sub"><?php _e('Based on coefficients', 'environmental-social-viral'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Viral Coefficient Trends -->
            <div class="env-admin-section">
                <div class="env-charts-container">
                    <div class="env-chart-wrapper">
                        <h3><?php _e('Viral Coefficient Trends', 'environmental-social-viral'); ?></h3>
                        <div class="chart-controls">
                            <select id="coefficient-period">
                                <option value="7days"><?php _e('Last 7 Days', 'environmental-social-viral'); ?></option>
                                <option value="30days" selected><?php _e('Last 30 Days', 'environmental-social-viral'); ?></option>
                                <option value="90days"><?php _e('Last 90 Days', 'environmental-social-viral'); ?></option>
                            </select>
                        </div>
                        <canvas id="coefficientChart" width="600" height="300"></canvas>
                    </div>
                    
                    <div class="env-chart-wrapper">
                        <h3><?php _e('Content Virality Distribution', 'environmental-social-viral'); ?></h3>
                        <canvas id="viralityDistributionChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Trending Content -->
            <div class="env-admin-section">
                <h2><?php _e('Trending Content', 'environmental-social-viral'); ?></h2>
                
                <div class="trending-controls">
                    <select id="trending-period">
                        <option value="1day"><?php _e('Last 24 Hours', 'environmental-social-viral'); ?></option>
                        <option value="7days" selected><?php _e('Last 7 Days', 'environmental-social-viral'); ?></option>
                        <option value="30days"><?php _e('Last 30 Days', 'environmental-social-viral'); ?></option>
                    </select>
                    
                    <button type="button" class="button" id="refresh-trending">
                        <?php _e('Refresh', 'environmental-social-viral'); ?>
                    </button>
                </div>
                
                <div class="trending-content-grid">
                    <?php foreach ($trending_content as $content): ?>
                    <div class="trending-item" data-content-id="<?php echo esc_attr($content['content_id']); ?>">
                        <div class="trending-header">
                            <h4>
                                <a href="<?php echo get_edit_post_link($content['content_id']); ?>" target="_blank">
                                    <?php echo esc_html($content['title']); ?>
                                </a>
                            </h4>
                            <span class="trending-badge trending-level-<?php echo $this->get_trending_level($content['trending_score']); ?>">
                                <?php echo $this->get_trending_label($content['trending_score']); ?>
                            </span>
                        </div>
                        
                        <div class="trending-metrics">
                            <div class="metric">
                                <span class="metric-label"><?php _e('Viral Coefficient:', 'environmental-social-viral'); ?></span>
                                <span class="metric-value viral-coefficient-<?php echo $this->get_viral_level($content['viral_coefficient']); ?>">
                                    <?php echo number_format($content['viral_coefficient'], 3); ?>
                                </span>
                            </div>
                            
                            <div class="metric">
                                <span class="metric-label"><?php _e('Shares:', 'environmental-social-viral'); ?></span>
                                <span class="metric-value"><?php echo number_format($content['total_shares']); ?></span>
                            </div>
                            
                            <div class="metric">
                                <span class="metric-label"><?php _e('Growth Rate:', 'environmental-social-viral'); ?></span>
                                <span class="metric-value growth-rate"><?php echo number_format($content['growth_rate'], 1); ?>%</span>
                            </div>
                        </div>
                        
                        <div class="trending-chart">
                            <canvas class="trending-sparkline" data-content="<?php echo esc_attr($content['content_id']); ?>"></canvas>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Viral Content Leaders -->
            <div class="env-admin-section">
                <h2><?php _e('Top Viral Content', 'environmental-social-viral'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Rank', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Content', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Author', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Viral Coefficient', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Total Shares', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Reach Multiplier', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Actions', 'environmental-social-viral'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viral_leaders as $index => $content): ?>
                        <tr class="viral-content-row" data-content-id="<?php echo esc_attr($content['content_id']); ?>">
                            <td>
                                <div class="rank-badge rank-<?php echo $index + 1; ?>">
                                    #<?php echo $index + 1; ?>
                                </div>
                            </td>
                            <td>
                                <div class="content-info">
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($content['content_id']); ?>" target="_blank">
                                            <?php echo esc_html($content['title']); ?>
                                        </a>
                                    </strong>
                                    <div class="content-meta">
                                        <?php echo esc_html(ucfirst($content['content_type'])); ?> • 
                                        <?php echo human_time_diff(strtotime($content['last_updated']), current_time('timestamp')); ?> ago
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="author-info">
                                    <?php echo get_avatar($content['author_id'], 32); ?>
                                    <span><?php echo esc_html($content['author_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="viral-coefficient-display">
                                    <div class="coefficient-bar">
                                        <div class="coefficient-fill coefficient-level-<?php echo $this->get_viral_level($content['viral_coefficient']); ?>" 
                                             style="width: <?php echo min($content['viral_coefficient'] * 100, 100); ?>%"></div>
                                    </div>
                                    <span class="coefficient-value"><?php echo number_format($content['viral_coefficient'], 3); ?></span>
                                </div>
                            </td>
                            <td><?php echo number_format($content['total_shares']); ?></td>
                            <td>
                                <span class="reach-multiplier"><?php echo number_format($content['reach_multiplier'], 1); ?>x</span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="#" class="view-details" data-content="<?php echo esc_attr($content['content_id']); ?>">
                                        <?php _e('Details', 'environmental-social-viral'); ?>
                                    </a>
                                    <a href="#" class="boost-content" data-content="<?php echo esc_attr($content['content_id']); ?>">
                                        <?php _e('Boost', 'environmental-social-viral'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Platform Viral Performance -->
            <div class="env-admin-section">
                <h2><?php _e('Platform Viral Performance', 'environmental-social-viral'); ?></h2>
                
                <div class="platform-viral-grid">
                    <?php foreach ($platform_performance as $platform): ?>
                    <div class="platform-viral-card">
                        <div class="platform-header">
                            <span class="platform-icon platform-<?php echo esc_attr($platform['platform']); ?>"></span>
                            <h4><?php echo esc_html(ucfirst($platform['platform'])); ?></h4>
                        </div>
                        
                        <div class="platform-metrics">
                            <div class="metric-row">
                                <span class="metric-label"><?php _e('Avg Viral Coefficient:', 'environmental-social-viral'); ?></span>
                                <span class="metric-value viral-coefficient-<?php echo $this->get_viral_level($platform['avg_coefficient']); ?>">
                                    <?php echo number_format($platform['avg_coefficient'], 3); ?>
                                </span>
                            </div>
                            
                            <div class="metric-row">
                                <span class="metric-label"><?php _e('Viral Content:', 'environmental-social-viral'); ?></span>
                                <span class="metric-value"><?php echo number_format($platform['viral_content_count']); ?></span>
                            </div>
                            
                            <div class="metric-row">
                                <span class="metric-label"><?php _e('Growth Rate:', 'environmental-social-viral'); ?></span>
                                <span class="metric-value growth-rate"><?php echo number_format($platform['growth_rate'], 1); ?>%</span>
                            </div>
                        </div>
                        
                        <div class="platform-trend">
                            <canvas class="platform-trend-chart" data-platform="<?php echo esc_attr($platform['platform']); ?>"></canvas>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Viral Engine Controls -->
            <div class="env-admin-section">
                <h2><?php _e('Viral Engine Controls', 'environmental-social-viral'); ?></h2>
                
                <div class="engine-controls">
                    <div class="control-group">
                        <h4><?php _e('Calculation Settings', 'environmental-social-viral'); ?></h4>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Viral Threshold', 'environmental-social-viral'); ?></th>
                                <td>
                                    <input type="number" step="0.001" min="0" max="1" 
                                           value="<?php echo esc_attr($this->get_viral_threshold()); ?>" 
                                           id="viral-threshold" class="small-text">
                                    <p class="description"><?php _e('Minimum coefficient to consider content viral', 'environmental-social-viral'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Time Decay Factor', 'environmental-social-viral'); ?></th>
                                <td>
                                    <input type="number" step="0.01" min="0" max="1" 
                                           value="<?php echo esc_attr($this->get_time_decay_factor()); ?>" 
                                           id="time-decay-factor" class="small-text">
                                    <p class="description"><?php _e('How much to reduce coefficient over time (0.01 = 1% per day)', 'environmental-social-viral'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Platform Weights', 'environmental-social-viral'); ?></th>
                                <td>
                                    <div class="platform-weights">
                                        <?php $weights = $this->get_platform_weights(); ?>
                                        <?php foreach ($weights as $platform => $weight): ?>
                                        <div class="weight-input">
                                            <label><?php echo esc_html(ucfirst($platform)); ?>:</label>
                                            <input type="number" step="0.1" min="0" max="10" 
                                                   value="<?php echo esc_attr($weight); ?>" 
                                                   data-platform="<?php echo esc_attr($platform); ?>" 
                                                   class="platform-weight small-text">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="control-buttons">
                            <button type="button" class="button button-primary" id="save-viral-settings">
                                <?php _e('Save Settings', 'environmental-social-viral'); ?>
                            </button>
                            
                            <button type="button" class="button" id="recalculate-coefficients">
                                <?php _e('Recalculate All Coefficients', 'environmental-social-viral'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <h4><?php _e('Data Management', 'environmental-social-viral'); ?></h4>
                        
                        <div class="data-actions">
                            <button type="button" class="button" id="export-viral-data">
                                <?php _e('Export Viral Data', 'environmental-social-viral'); ?>
                            </button>
                            
                            <button type="button" class="button button-secondary" id="reset-viral-data">
                                <?php _e('Reset All Data', 'environmental-social-viral'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize charts
            ENV_ViralDashboard.init();
            
            // Control handlers
            $('#save-viral-settings').on('click', function() {
                ENV_ViralDashboard.saveSettings();
            });
            
            $('#recalculate-coefficients').on('click', function() {
                ENV_ViralDashboard.recalculateCoefficients();
            });
            
            $('#export-viral-data').on('click', function() {
                ENV_ViralDashboard.exportData();
            });
            
            $('#reset-viral-data').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to reset all viral data? This cannot be undone.', 'environmental-social-viral'); ?>')) {
                    ENV_ViralDashboard.resetData();
                }
            });
            
            // Period change handlers
            $('#coefficient-period, #trending-period').on('change', function() {
                ENV_ViralDashboard.updateCharts();
            });
            
            $('#refresh-trending').on('click', function() {
                ENV_ViralDashboard.refreshTrending();
            });
        });
        
        // Viral Dashboard JavaScript Object
        var ENV_ViralDashboard = {
            init: function() {
                this.initCharts();
                this.bindEvents();
            },
            
            initCharts: function() {
                // Initialize coefficient trends chart
                const coefficientData = <?php echo json_encode($this->get_coefficient_chart_data()); ?>;
                const coefficientCtx = document.getElementById('coefficientChart').getContext('2d');
                
                new Chart(coefficientCtx, {
                    type: 'line',
                    data: coefficientData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 1
                            }
                        }
                    }
                });
                
                // Initialize virality distribution chart
                const distributionData = <?php echo json_encode($this->get_virality_distribution_data()); ?>;
                const distributionCtx = document.getElementById('viralityDistributionChart').getContext('2d');
                
                new Chart(distributionCtx, {
                    type: 'bar',
                    data: distributionData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                
                // Initialize trending sparklines
                this.initSparklines();
            },
            
            initSparklines: function() {
                $('.trending-sparkline').each(function() {
                    const contentId = $(this).data('content');
                    // Initialize sparkline charts for trending content
                });
            },
            
            bindEvents: function() {
                $('.view-details').on('click', this.viewContentDetails);
                $('.boost-content').on('click', this.boostContent);
            },
            
            saveSettings: function() {
                const settings = {
                    viral_threshold: $('#viral-threshold').val(),
                    time_decay_factor: $('#time-decay-factor').val(),
                    platform_weights: {}
                };
                
                $('.platform-weight').each(function() {
                    const platform = $(this).data('platform');
                    settings.platform_weights[platform] = $(this).val();
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'env_viral_update_settings',
                        settings: settings,
                        nonce: envSocialViralAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            ENV_ViralDashboard.showNotice('<?php _e('Settings saved successfully', 'environmental-social-viral'); ?>', 'success');
                        }
                    }
                });
            },
            
            recalculateCoefficients: function() {
                const button = $('#recalculate-coefficients');
                button.prop('disabled', true).text('<?php _e('Calculating...', 'environmental-social-viral'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'env_viral_update_coefficients',
                        nonce: envSocialViralAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            ENV_ViralDashboard.showNotice('<?php _e('Coefficients recalculated successfully', 'environmental-social-viral'); ?>', 'success');
                            location.reload();
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Recalculate All Coefficients', 'environmental-social-viral'); ?>');
                    }
                });
            },
            
            exportData: function() {
                window.location.href = ajaxurl + '?action=env_viral_export_metrics&nonce=' + envSocialViralAdmin.nonce;
            },
            
            resetData: function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'env_viral_reset_data',
                        nonce: envSocialViralAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            ENV_ViralDashboard.showNotice('<?php _e('Data reset successfully', 'environmental-social-viral'); ?>', 'success');
                            location.reload();
                        }
                    }
                });
            },
            
            updateCharts: function() {
                // Update charts based on period selection
                location.reload();
            },
            
            refreshTrending: function() {
                const period = $('#trending-period').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'env_viral_get_trending',
                        period: period,
                        nonce: envSocialViralAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update trending content display
                            $('.trending-content-grid').html(response.data.html);
                        }
                    }
                });
            },
            
            viewContentDetails: function(e) {
                e.preventDefault();
                const contentId = $(this).data('content');
                // Open content details modal
            },
            
            boostContent: function(e) {
                e.preventDefault();
                const contentId = $(this).data('content');
                // Implement content boosting functionality
            },
            
            showNotice: function(message, type) {
                const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
                $('.wrap').prepend(notice);
                setTimeout(function() {
                    notice.fadeOut();
                }, 3000);
            }
        };
        </script>
        <?php
    }
    
    /**
     * Get viral metrics summary
     */
    private function get_viral_metrics_summary() {
        // Current period metrics
        $current_metrics = $this->wpdb->get_row(
            "SELECT 
                AVG(viral_coefficient) as avg_coefficient,
                COUNT(CASE WHEN viral_coefficient >= 0.3 THEN 1 END) as viral_content_count,
                SUM(CASE WHEN viral_coefficient >= 0.3 THEN share_count ELSE 0 END) as viral_shares
             FROM {$this->tables['viral_coefficients']} 
             WHERE period = '30days' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // Previous period for comparison
        $previous_metrics = $this->wpdb->get_row(
            "SELECT 
                AVG(viral_coefficient) as avg_coefficient,
                SUM(CASE WHEN viral_coefficient >= 0.3 THEN share_count ELSE 0 END) as viral_shares
             FROM {$this->tables['viral_coefficients']} 
             WHERE period = '30days' AND updated_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // Calculate trends
        $coefficient_trend = $this->calculate_percentage_change($previous_metrics->avg_coefficient, $current_metrics->avg_coefficient);
        $shares_trend = $this->calculate_percentage_change($previous_metrics->viral_shares, $current_metrics->viral_shares);
        
        // Estimate viral reach
        $viral_reach = $current_metrics->viral_shares * $current_metrics->avg_coefficient * 100;
        
        return array(
            'avg_viral_coefficient' => $current_metrics->avg_coefficient ?: 0,
            'viral_content_count' => $current_metrics->viral_content_count ?: 0,
            'total_viral_shares' => $current_metrics->viral_shares ?: 0,
            'viral_reach' => $viral_reach,
            'coefficient_trend' => $coefficient_trend,
            'shares_trend' => $shares_trend
        );
    }
    
    /**
     * Get trending content
     */
    private function get_trending_content() {
        $trending = $this->wpdb->get_results(
            "SELECT 
                vc.content_id,
                vc.viral_coefficient,
                p.post_title as title,
                vm.trending_score,
                vm.growth_rate,
                COUNT(s.id) as total_shares
             FROM {$this->tables['viral_coefficients']} vc
             LEFT JOIN {$this->tables['viral_metrics']} vm ON vc.content_id = vm.content_id
             LEFT JOIN {$this->wpdb->posts} p ON vc.content_id = p.ID
             LEFT JOIN {$this->tables['shares']} s ON vc.content_id = s.content_id AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             WHERE vc.period = '7days' 
             AND vm.trending_score > 0.5
             GROUP BY vc.content_id
             ORDER BY vm.trending_score DESC
             LIMIT 12"
        );
        
        return $trending;
    }
    
    /**
     * Get viral content leaders
     */
    private function get_viral_content_leaders() {
        $leaders = $this->wpdb->get_results(
            "SELECT 
                vc.content_id,
                vc.content_type,
                vc.viral_coefficient,
                p.post_title as title,
                p.post_author as author_id,
                u.display_name as author_name,
                COUNT(s.id) as total_shares,
                vc.updated_at as last_updated,
                (vc.viral_coefficient * COUNT(s.id)) as reach_multiplier
             FROM {$this->tables['viral_coefficients']} vc
             LEFT JOIN {$this->wpdb->posts} p ON vc.content_id = p.ID
             LEFT JOIN {$this->wpdb->users} u ON p.post_author = u.ID
             LEFT JOIN {$this->tables['shares']} s ON vc.content_id = s.content_id
             WHERE vc.period = '30days' AND vc.viral_coefficient >= 0.1
             GROUP BY vc.content_id
             ORDER BY vc.viral_coefficient DESC, total_shares DESC
             LIMIT 50"
        );
        
        return $leaders;
    }
    
    /**
     * Get platform viral performance
     */
    private function get_platform_viral_performance() {
        $platforms = $this->wpdb->get_results(
            "SELECT 
                s.platform,
                AVG(vc.viral_coefficient) as avg_coefficient,
                COUNT(CASE WHEN vc.viral_coefficient >= 0.3 THEN 1 END) as viral_content_count,
                (COUNT(CASE WHEN s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) - 
                 COUNT(CASE WHEN s.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END)) /
                NULLIF(COUNT(CASE WHEN s.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END), 0) * 100 as growth_rate
             FROM {$this->tables['shares']} s
             LEFT JOIN {$this->tables['viral_coefficients']} vc ON s.content_id = vc.content_id AND vc.period = '7days'
             WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY s.platform
             ORDER BY avg_coefficient DESC"
        );
        
        return $platforms;
    }
    
    /**
     * Get coefficient chart data
     */
    private function get_coefficient_chart_data() {
        $data = $this->wpdb->get_results(
            "SELECT 
                DATE(updated_at) as date,
                AVG(viral_coefficient) as avg_coefficient,
                MAX(viral_coefficient) as max_coefficient,
                COUNT(CASE WHEN viral_coefficient >= 0.3 THEN 1 END) as viral_count
             FROM {$this->tables['viral_coefficients']} 
             WHERE period = '1day' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(updated_at)
             ORDER BY date ASC"
        );
        
        $labels = array();
        $avg_coefficients = array();
        $max_coefficients = array();
        $viral_counts = array();
        
        foreach ($data as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $avg_coefficients[] = floatval($row->avg_coefficient);
            $max_coefficients[] = floatval($row->max_coefficient);
            $viral_counts[] = intval($row->viral_count);
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Average Coefficient', 'environmental-social-viral'),
                    'data' => $avg_coefficients,
                    'borderColor' => '#2E7D32',
                    'backgroundColor' => 'rgba(46, 125, 50, 0.1)',
                    'fill' => true,
                    'yAxisID' => 'y'
                ),
                array(
                    'label' => __('Max Coefficient', 'environmental-social-viral'),
                    'data' => $max_coefficients,
                    'borderColor' => '#FF6F00',
                    'backgroundColor' => 'rgba(255, 111, 0, 0.1)',
                    'fill' => false,
                    'yAxisID' => 'y'
                ),
                array(
                    'label' => __('Viral Content Count', 'environmental-social-viral'),
                    'data' => $viral_counts,
                    'borderColor' => '#1976D2',
                    'backgroundColor' => 'rgba(25, 118, 210, 0.3)',
                    'type' => 'bar',
                    'yAxisID' => 'y1'
                )
            )
        );
    }
    
    /**
     * Get virality distribution data
     */
    private function get_virality_distribution_data() {
        $distribution = $this->wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN viral_coefficient >= 0.7 THEN 'Very High (0.7+)'
                    WHEN viral_coefficient >= 0.5 THEN 'High (0.5-0.7)'
                    WHEN viral_coefficient >= 0.3 THEN 'Medium (0.3-0.5)'
                    WHEN viral_coefficient >= 0.1 THEN 'Low (0.1-0.3)'
                    ELSE 'Very Low (0-0.1)'
                END as category,
                COUNT(*) as count
             FROM {$this->tables['viral_coefficients']} 
             WHERE period = '30days'
             GROUP BY category
             ORDER BY count DESC"
        );
        
        $labels = array();
        $values = array();
        $colors = array('#4CAF50', '#FF9800', '#2196F3', '#FFC107', '#9E9E9E');
        
        foreach ($distribution as $row) {
            $labels[] = $row->category;
            $values[] = $row->count;
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values))
                )
            )
        );
    }
    
    /**
     * Get viral threshold setting
     */
    private function get_viral_threshold() {
        $settings = get_option('env_social_viral_settings', array());
        return $settings['viral_threshold'] ?? 0.3;
    }
    
    /**
     * Get time decay factor
     */
    private function get_time_decay_factor() {
        $settings = get_option('env_social_viral_settings', array());
        return $settings['time_decay_factor'] ?? 0.01;
    }
    
    /**
     * Get platform weights
     */
    private function get_platform_weights() {
        $settings = get_option('env_social_viral_settings', array());
        return $settings['platform_weights'] ?? array(
            'facebook' => 1.0,
            'twitter' => 0.8,
            'linkedin' => 0.6,
            'whatsapp' => 1.2,
            'telegram' => 0.7,
            'email' => 0.5,
            'copy' => 0.3
        );
    }
    
    /**
     * Get trending level
     */
    private function get_trending_level($score) {
        if ($score >= 0.8) return 'hot';
        if ($score >= 0.6) return 'trending';
        return 'rising';
    }
    
    /**
     * Get trending label
     */
    private function get_trending_label($score) {
        if ($score >= 0.8) return __('🔥 Hot', 'environmental-social-viral');
        if ($score >= 0.6) return __('📈 Trending', 'environmental-social-viral');
        return __('⬆️ Rising', 'environmental-social-viral');
    }
    
    /**
     * Get viral level
     */
    private function get_viral_level($coefficient) {
        if ($coefficient >= 0.5) return 'high';
        if ($coefficient >= 0.3) return 'medium';
        return 'low';
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
     * AJAX: Get content metrics
     */
    public function ajax_get_content_metrics() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $content_id = intval($_POST['content_id']);
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        
        $viral_engine = Environmental_Social_Viral_Engine::get_instance();
        $metrics = $viral_engine->get_content_viral_stats($content_id, $period);
        
        wp_send_json_success($metrics);
    }
    
    /**
     * AJAX: Update coefficients
     */
    public function ajax_update_coefficients() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $viral_engine = Environmental_Social_Viral_Engine::get_instance();
        $viral_engine->calculate_viral_coefficients();
        
        wp_send_json_success(__('Coefficients updated successfully', 'environmental-social-viral'));
    }
    
    /**
     * AJAX: Get trending content
     */
    public function ajax_get_trending_content() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $period = sanitize_text_field($_POST['period'] ?? '7days');
        $trending = $this->get_trending_content();
        
        ob_start();
        foreach ($trending as $content) {
            // Render trending content HTML
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Export viral metrics
     */
    public function ajax_export_viral_metrics() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        $data = $this->wpdb->get_results(
            "SELECT * FROM {$this->tables['viral_coefficients']} 
             ORDER BY updated_at DESC",
            ARRAY_A
        );
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="viral-metrics-' . date('Y-m-d') . '.csv"');
        
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
    
    /**
     * AJAX: Reset viral data
     */
    public function ajax_reset_viral_data() {
        check_ajax_referer('env_social_viral_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-social-viral'));
        }
        
        // Reset viral data tables
        $this->wpdb->query("TRUNCATE TABLE {$this->tables['viral_coefficients']}");
        $this->wpdb->query("TRUNCATE TABLE {$this->tables['viral_metrics']}");
        $this->wpdb->query("TRUNCATE TABLE {$this->tables['viral_content']}");
        
        wp_send_json_success(__('Viral data reset successfully', 'environmental-social-viral'));
    }
}
